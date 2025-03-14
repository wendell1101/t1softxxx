<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * RPN
 *
 * * RPN_PAYMENT_API, ID: 811
 * * RPN_QUICKPAY_PAYMENT_API, ID: 812
 * * RPN_QQPAY_PAYMENT_API, ID: 813
 * * RPN_ALIPAY_PAYMENT_API, ID: 814
 * * RPN_UNIONPAY_PAYMENT_API, ID: 815
 * * RPN_UNIONPAY_H5_PAYMENT_API, ID: 847
 * * RPN_WITHDRAWAL_PAYMENT_API, ID: 816
 * * RPN_WEIXIN_PAYMENT_API, ID: 5295
 * * RPN_2_ALIPAY_PAYMENT_API, ID: 5417
 * * RPN_P2P_PAYMENT_API, ID: 5585
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://deposit.paylomo.net/pay.php
 * * Account: ## User ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
abstract class Abstract_payment_api_rpn extends Abstract_payment_api {
    const ORDER_STATUS_PROCESSING = 1;
    const ORDER_STATUS_SUCCESS = 3;
    const RETURN_SUCCESS_CODE = "Success";
    const RETURN_SUCCESS_CODE_ver_1_1 = "[Success]";

    public function __construct($params = null) {
        parent::__construct($params);
    }

    public function getBankType($direct_pay_extra_info) {
        return ''; # Default return empty banktype, redirect to bank selection page
    }
    protected abstract function configParams(&$params, $direct_pay_extra_info);

    # -- override common API functions --
    ## Constructs an URL so that the caller can redirect / invoke it to make payment through this API
    ## See controllers/redirect.php for detail.
    ##
    ## Retuns a hash containing these fields:
    ## array(
    ##  'success' => true,
    ##  'type' => self::REDIRECT_TYPE_FORM,  ## constants defined in abstract_payment_api.php
    ##  'url' => $info['url'],
    ##  'params' => $params,
    ##  'post' => true
    ## );
    public function generatePaymentUrlForm($orderId, $playerId, $amount, $orderDateTime, $playerPromoId = null, $enabledSecondUrl = true, $bankId = null) {
        if ($this->shouldRedirect($enabledSecondUrl)) {
            $url = $this->CI->utils->getPaymentUrl($this->getSystemInfo('second_url'), $this->getPlatformCode(), $amount, $playerId, $playerPromoId, false, $bankId, $orderId);
            $result = array('success' => true, 'type' => self::REDIRECT_TYPE_URL, 'url' => $url);
            return $result;
        }

        $order = $this->CI->sale_order->getSaleOrderById($orderId);

        $params = array();
        $params['version']      = '1.0'; # fixed value
        $params['sign_type']    = 'MD5'; # fixed value
        $params['mid']          = $this->getSystemInfo('account');
        $params['return_url']   = $this->getReturnUrl($orderId);
        $params['notify_url']   = $this->getNotifyUrl($orderId);
        $params['order_id']     = $order->secure_id;
        $params['order_amount'] = $this->convertAmountToCurrency($amount,$order->created_at);
        $params['order_time']   = date('YmdHis');
        $this->configParams($params, $order->direct_pay_extra_info);
        if( $params['version']  == '1.1'){
            $player = $this->CI->player->getPlayerById($playerId);
            $params['user_id']      = $playerId;
            $params['user_name']    = $player['username'];
        }
        $params['signature']    = $this->sign($params);

        $this->CI->utils->debug_log('=====================rpn generatePaymentUrlForm params', $params);

        return $this->processPaymentUrlForm($params);
    }

    # QRCode implementation can overwrite this function to supply QRCode page
    protected function processPaymentUrlForm($params) {
        $url = $this->getSystemInfo('url');

        $use_static = $this->getSystemInfo('use_static');
        if($use_static){
            $response = $this->submitPostForm($url, $params, false, $params['order_id']);
            $response = json_decode($response, true);

            if(isset($response['status']) && $response['status']=='success'){
                $order = $this->CI->sale_order->getSaleOrderBySecureId($params['order_id']);
                $this->CI->sale_order->updateExternalInfo($order->id, $response['order_id']);

                $data['Beneficiary Name']          = $response['bankaccount'];
                $data['Beneficiary Account']       = $response['bankcardno'];
                $data['Beneficiary Bank']          = $response['bankname'];
                $data['Beneficiary Bank Branch']   = $response['banksub'];
                $data['Beneficiary Bank Province'] = $response['province'];
                $data['Beneficiary Bank City']     = $response['city'];
                $data['Beneficiary Amount']        = $response['check_amount']/100;
                $data['Beneficiary note']          = $response['order_id'];

                $collection_text_transfer = $this->getSystemInfo('collection_text_transfer');
                $is_not_display_recharge_instructions = $this->getSystemInfo('is_not_display_recharge_instructions');
                return array(
                    'success' => true,
                    'type' => self::REDIRECT_TYPE_STATIC,
                    'data' => $data,
                    'collection_text_transfer' => $collection_text_transfer,
                    'is_not_display_recharge_instructions' => $is_not_display_recharge_instructions
                );
            } else {
                return array(
                    'success' => false,
                    'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
                    'message' => lang('Invalidte API response')
                );
            }

        }

        return array(
            'success' => true,
            'type' => self::REDIRECT_TYPE_FORM,
            'url' => $url,
            'params' => $params,
            'post' => true,
        );
    }

    ## This will be called when the payment is async, API server calls our callback page
    ## When that happens, we perform verifications and necessary database updates to mark the payment as successful
    ## Reference: sample code, callback.php
    public function callbackFromServer($orderId, $params) {
        $response_result_id = parent::callbackFromServer($orderId, $params);
        return $this->callbackFrom('server', $orderId, $params, $response_result_id);
    }

    ## This will be called when user redirects back to our page from payment API
    public function callbackFromBrowser($orderId, $params) {
        $response_result_id = parent::callbackFromBrowser($orderId, $params);
        return $this->callbackFrom('browser', $orderId, $params, $response_result_id);
    }

    # $source can be 'server' or 'browser'
    private function callbackFrom($source, $orderId, $params, $response_result_id) {
        $result = array('success' => false, 'next_url' => null, 'message' => lang('error.payment.failed'));
        $order = $this->CI->sale_order->getSaleOrderById($orderId);
        $processed = false;

        $this->CI->utils->debug_log("=====================rpn callbackFrom $source params", $params);

        if($source == 'server' ){
            if (!$order || !$this->checkCallbackOrder($order, $params, $processed)) {
                return $result;
            }
        }

        # Update order payment status and balance
        $success = true;

        # Update player balance based on order status
        # if it's STATUS_SETTLED or STATUS_BROWSER_CALLBACK, put log, and ignore
        $orderStatus = $this->CI->sale_order->getSaleOrderStatusById($orderId);
        if ($orderStatus == Sale_order::STATUS_BROWSER_CALLBACK || $orderStatus == Sale_order::STATUS_SETTLED) {
            $this->CI->utils->debug_log('callbackFrom' . ucfirst($source) . ', already get callback for order:' . $order->id, $params);
            if ($source == 'server' && $order->status == Sale_order::STATUS_BROWSER_CALLBACK) {
                $this->CI->sale_order->setStatusToSettled($orderId);
            }
        } else {
            # update player balance
            $this->CI->sale_order->updateExternalInfo($order->id, $params['deal_id'], '', null, null, $response_result_id);
            if ($source == 'browser') {
                $this->CI->sale_order->browserCallbackSaleOrder($order->id, 'auto broswer callback '.$this->getPlatformCode().', result: '. $params['pay_result'], false);
            } elseif ($source == 'server') {
                $this->approveSaleOrder($order->id, 'auto server callback ' . $this->getPlatformCode(), false);
            }
        }

        $result['success'] = $success;
        if ($processed) {
            $result['message'] = self::RETURN_SUCCESS_CODE;
            if( $this->getSystemInfo('version') == '1.1'){
                $result['message'] = self::RETURN_SUCCESS_CODE_ver_1_1;
            }
        } else {
            $result['return_error'] = 'Error';
        }

        if ($source == 'browser') {
            if ($params['pay_result'] == self::ORDER_STATUS_SUCCESS) {
                $result['success'] = true;
                $result['message'] = self::RETURN_SUCCESS_CODE;
            } elseif ($params['pay_result'] == self::ORDER_STATUS_PROCESSING) {
                $result['success'] = false;
                $result['message'] = 'The transaction is processing';
            } else {
                $result['success'] = false;
                $result['message'] = 'Unknown Status';
            }
            $result['next_url'] = $this->getPlayerBackUrl();
        }

        return $result;
    }

    ## Validates whether the callback from API contains valid info and matches with the order
    ## Reference: code sample, callback.php
    private function checkCallbackOrder($order, $fields, &$processed = false) {

        $requiredFields = array(
            'order_id', 'order_time', 'order_amount', 'deal_id', 'deal_time', 'pay_amount', 'pay_result', 'signature'
        );

        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("=====================rpn Missing parameter: [$f]", $fields);
                return false;
            }
        }

        # is signature authentic?
        if (!$this->validateSign($fields)) {
            $this->writePaymentErrorLog('=====================rpn Signature Error', $fields);
            return false;
        }

        $processed = true; # processed is set to true once the signature verification pass

        if ($fields['pay_result'] != self::ORDER_STATUS_SUCCESS) {
            $this->writePaymentErrorLog('=====================rpn Payment was not successful', $fields);
            return false;
        }

        $check_amount = $this->convertAmountToCurrency($order->amount, $order->created_at);

        if ($fields['pay_amount'] != $check_amount) {
            $this->writePaymentErrorLog("======================rpn Payment amount is wrong, expected <= ". $check_amount, $fields);
            return false;
        }
        # everything checked ok
        return true;
    }

    public function directPay($order = null) {
        return array('success' => false); # direct pay not supported by this API
    }

    # -- signatures --
    # Reference: PHP Demo
    private function sign($params) {
        $signStr = $this->createSignStr($params);
        $sign = md5($signStr);
        return $sign;
    }

    private function createSignStr($params) {
        $signStr = '';
        foreach($params as $key => $value) {
            if(empty($value) || $key == 'signature' || $key == 'ext' || $key == 'SignValue') {
                continue;
            }
            $signStr .= "$key=$value|";
        }
        $signStr .= "key=".$this->getSystemInfo('key');
        return $signStr;
    }

    private function validateSign($params) {
        $sign = $this->sign($params);
        if($params['signature'] == $sign)
            return true;
        else
            return false;
    }

    # -- Private functions --
    # After payment is complete, the gateway will invoke this URL asynchronously
    private function getNotifyUrl($orderId) {
        return parent::getCallbackUrl('/callback/process/' . $this->getPlatformCode() . '/' . $orderId);
    }

    ## After payment is complete, the gateway will send redirect back to this URL
    private function getReturnUrl($orderId) {
        return parent::getCallbackUrl('/callback/browser/success/' . $this->getPlatformCode() . '/' . $orderId);
    }

    ## Format the amount value for the API
    protected function convertAmountToCurrency($amount, $orderDateTime) {
        if($this->getSystemInfo('use_usd_currency')){
            if(is_string($orderDateTime)){
                $orderDateTime = DateTime::createFromFormat('Y-m-d H:i:s', $orderDateTime);
            }
            $amount = $this->gameAmountToDBByCurrency($amount, $this->utils->getTimeForMysql($orderDateTime),'USD','CNY');
            $this->CI->utils->debug_log('=====================rpn convertAmountToCurrency use_usd_currency', $amount);
        }
        return number_format($amount * 100, 0, '.', '');
    }
}

