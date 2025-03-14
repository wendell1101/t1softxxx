<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * applepay
 *
 * * APPLEPAY_PAYMENT_API, ID: 5847
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://mapay168.com/api_server/receive_add.php
 * * Account: ## User ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
abstract class Abstract_payment_api_applepay extends Abstract_payment_api
{
    const ORDER_STATUS_SUCCESS = "1";
    const CALLBACK_SUCCESS = "4";
    const REQUEST_STATUS_PENDING = "PENDING";
    const RETURN_SUCCESS_CODE = "1";
    const PAY_METHODS_ONLINE_BANK = 4;


    public function __construct($params = null)
    {
        parent::__construct($params);
        $this->_custom_curl_header = array('application/x-www-form-urlencoded');
    }
    abstract protected function configParams(&$params, $direct_pay_extra_info);
    abstract protected function processPaymentUrlForm($params);

    public function generatePaymentUrlForm($orderId, $playerId, $amount, $orderDateTime, $playerPromoId = null, $enabledSecondUrl = true, $bankId = null)
    {
        if ($this->shouldRedirect($enabledSecondUrl)) {
            $url = $this->CI->utils->getPaymentUrl($this->getSystemInfo('second_url'), $this->getPlatformCode(), $amount, $playerId, $playerPromoId, false, $bankId, $orderId);
            $result = array('success' => true, 'type' => self::REDIRECT_TYPE_URL, 'url' => $url);
            return $result;
        }

        $order = $this->CI->sale_order->getSaleOrderById($orderId);

        $params = array();
        $params['store_guid'] = $this->getSystemInfo("account");
        $params['money']      = $this->convertAmountToCurrency($amount);
        $params['tradeno']    = $order->secure_id;
        $this->configParams($params, $order->direct_pay_extra_info);
        $params['notify_url'] = $this->getNotifyUrl($orderId);
        $params['token']      = $this->sign($params);

        $this->CI->utils->debug_log('=====================applepay generatePaymentUrlForm params', $params);

        return $this->processPaymentUrlForm($params);
    }

    protected function processPaymentUrlFormPost($params)
    {
        $url = $this->getSystemInfo('url');
        $response = $this->submitPostForm($url, $params, false, $params['tradeno']);
        $decode_data = json_decode($response, true);
        $this->CI->utils->debug_log('========================================dada processPaymentUrlFormQRcode response json to array', $decode_data);
        $msg = lang('Invalidate API response');

        if (isset($decode_data['result'])) {
            if ($decode_data['result'] == self::ORDER_STATUS_SUCCESS) {
                return array(
                    'success' => true,
                    'type' => self::REDIRECT_TYPE_URL,
                    'url' => $decode_data['url'],
                );
            }
        } else {
            return array(
                'success' => false,
                'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
                'message' => $msg
            );
        }
    }

    protected function handlePaymentFormResponse($params)
    {
        $response = $this->submitPostForm($this->getSystemInfo('url'), $params, false);
        $response = json_decode($response, true);
        $this->CI->utils->debug_log('=====================ss processPaymentUrlFormPost response', $response);

        if (isset($response['result'])) {
            if ($response['result'] == self::ORDER_STATUS_SUCCESS) {
                $data = array();
                $data['cashier.67']   = $response['receive_inbankname'];
                $data['cashier.68']   = $response['receive_inname'];
                $data['cashier.69']   = $response['receive_inbanknum'];
                $data['Amount']   = $response['receive_money'];
                // $data['Beneficiary Bank']   = $this->getBankName($response['CollectingBankID']);

                $collection_text_transfer = '';
                $collection_text = $this->getSystemInfo("collection_text_transfer", array(''));
                if (is_array($collection_text)) {
                    $collection_text_transfer = $collection_text;
                }
                $is_not_display_recharge_instructions = $this->getSystemInfo('is_not_display_recharge_instructions');

                return array(
                    'success' => true,
                    'type' => self::REDIRECT_TYPE_STATIC,
                    'data' => $data,
                    'hide_timeout' => true,
                    'collection_text_transfer' => $collection_text_transfer,
                    'is_not_display_recharge_instructions' => $is_not_display_recharge_instructions
                );
            } else {
                return array(
                    'success' => false,
                    'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
                    'message' => $response['result'],
                );
            }
        } else {
            return array(
                'success' => false,
                'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
                'message' => lang('Invalidate API response')
            );
        }
    }

    public function callbackFromServer($orderId, $params)
    {
        $response_result_id = parent::callbackFromServer($orderId, $params);
        return $this->callbackFrom('server', $orderId, $params, $response_result_id);
    }

    ## This will be called when user redirects back to our page from payment API
    public function callbackFromBrowser($orderId, $params)
    {
        $response_result_id = parent::callbackFromBrowser($orderId, $params);
        return $this->callbackFrom('browser', $orderId, $params, $response_result_id);
    }

    # $source can be 'server' or 'browser'
    private function callbackFrom($source, $orderId, $params, $response_result_id)
    {
        $result = array('success' => false, 'next_url' => null, 'message' => lang('error.payment.failed'));
        $order = $this->CI->sale_order->getSaleOrderById($orderId);
        $processed = false;
        if ($source == 'server') {
            if (empty($params)) {
                $raw_post_data = file_get_contents('php://input', 'r');
                $this->CI->utils->debug_log("========================applepay raw_post_data", $raw_post_data);
                $params = json_decode($raw_post_data, true);
                $this->CI->utils->debug_log("========================applepay json_decode params", $params);
            }
            if (!$order || !$this->checkCallbackOrder($order, $params, $processed)) {
                return $result;
            }
        }

        $this->CI->utils->debug_log("=====================applepay callbackFrom $source params", $params);

        $success = true;

        $orderStatus = $this->CI->sale_order->getSaleOrderStatusById($orderId);
        if ($orderStatus == Sale_order::STATUS_BROWSER_CALLBACK || $orderStatus == Sale_order::STATUS_SETTLED) {
            $this->CI->utils->debug_log('callbackFrom' . ucfirst($source) . ', already get callback for order:' . $order->id);
            if ($source == 'server' && $order->status == Sale_order::STATUS_BROWSER_CALLBACK) {
                $this->CI->sale_order->setStatusToSettled($orderId);
            }
        } else {
            # update player balance
            $this->CI->sale_order->updateExternalInfo($order->id, $params['receive_tradeno'], null, null, null, $response_result_id);
            if ($source == 'browser') {
                $success = $this->CI->sale_order->browserCallbackSaleOrder($order->id, 'auto broswer callback ' . $this->getPlatformCode(), false);
            } elseif ($source == 'server') {
                $this->approveSaleOrder($order->id, 'auto server callback ' . $this->getPlatformCode(), false);
            }
        }

        $result['success'] = $success;
        if ($processed) {
            $result['message'] = self::RETURN_SUCCESS_CODE;
        } else {
            $result['return_error'] = 'Error';
        }

        if ($source == 'browser') {
            $result['next_url'] = $this->getPlayerBackUrl();
            $result['go_success_page'] = true;
        }

        return $result;
    }

    private function checkCallbackOrder($order, $fields, &$processed = false)
    {
        $requiredFields = array(
            'result','receive_state', 'receive_tradeno','receive_money','receive_inmoney','check'
        );

        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("=====================applepay Missing parameter: [$f]", $fields);
                return false;
            }
        }

        if (!$this->validateSign($fields)) {
            $this->writePaymentErrorLog('=====================applepay checkCallbackOrder Signature Error', $fields);
            return false;
        }

        $processed = true; # processed is set to true once the signature verification pass

        if ($fields['result'] != self::ORDER_STATUS_SUCCESS) {
            $this->writePaymentErrorLog("======================applepay checkCallbackTransaction Payment post callback search status is not success", $fields);
            return false;
        }

        if ($fields['receive_state'] != self::CALLBACK_SUCCESS) {
            $this->writePaymentErrorLog("======================applepay checkCallbackTransaction Payment status is not success", $fields);
            return false;
        }

        if ($fields['receive_money'] != $this->convertAmountToCurrency($order->amount)) {
            $this->writePaymentErrorLog("======================applepay checkCallbackTransaction Payment amount is wrong, expected [$order->amount]", $fields);
            return false;
        }

        if ($fields['receive_tradeno'] != $order->secure_id) {
            $this->writePaymentErrorLog("======================applepay checkCallbackTransaction order IDs do not match, expected [$order->secure_id]", $fields);
            return false;
        }

        # everything checked ok
        return true;
    }

    public function directPay($order = null)
    {
        return array('success' => false); # direct pay not supported by this API
    }

    # -- signatures --
    # Reference: PHP Demo
    private function sign($params)
    {
        $signStr = $this->createSignStr($params);
        $sign = md5($signStr);
        return $sign;
    }

    private function createSignStr($params)
    {
        $signStr = '';
        $signStr = $params['store_guid'].'_'.$params['tradeno'].'_'.$this->getSystemInfo('key').'_'.$params['money'];
        return $signStr;
    }

    private function validateSign($params)
    {
        $signStr = '';
        $signStr = $this->getSystemInfo("account").$params['receive_id'].$this->getSystemInfo('key');
        $sign = md5($signStr);
        if ($params['check'] == $sign) {
            return true;
        } else {
            return false;
        }
    }

    # -- Private functions --
    # After payment is complete, the gateway will invoke this URL asynchronously
    private function getNotifyUrl($orderId)
    {
        return parent::getCallbackUrl('/callback/process/' . $this->getPlatformCode() . '/' . $orderId);
    }

    ## After payment is complete, the gateway will send redirect back to this URL
    private function getReturnUrl($orderId)
    {
        return parent::getCallbackUrl('/callback/browser/success/' . $this->getPlatformCode() . '/' . $orderId);
    }

    public function convertAmountToCurrency($amount)
    {
        return number_format($amount, 2, '.', '') ;
    }
}
