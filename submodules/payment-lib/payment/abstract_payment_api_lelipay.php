<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * lelipay
 *
 * * LELIPAY_PAYMENT_API, ID: 6256
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL:
 * * Account: ## User ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2023 tot
 */
abstract class Abstract_payment_api_lelipay extends Abstract_payment_api {
    const CALLBACK_SUCCESS     = 10;
    const RETURN_SUCCESS_CODE  = 'success';
    const REPONSE_CODE_SUCCESS = '0000';
    const RESULT_CODE_PROCESSING = '01';
    const RESULT_CODE_SUCCESS = '10';
    const RESULT_CODE_FAILED = '20';
    const RESULT_CODE_OTHERS = '30';

    public function __construct($params = null) {
        parent::__construct($params);
        $this->_custom_curl_header = array('application/x-www-form-urlencoded; charset=utf-8');
    }

    # Implement these to specify pay type
    protected abstract function configParams(&$params, $direct_pay_extra_info);
    protected abstract function processPaymentUrlForm($params);

    public function generatePaymentUrlForm($orderId, $playerId, $amount, $orderDateTime, $playerPromoId = null, $enabledSecondUrl = true, $bankId = null) {
        if ($this->shouldRedirect($enabledSecondUrl)) {
            $url = $this->CI->utils->getPaymentUrl($this->getSystemInfo('second_url'), $this->getPlatformCode(), $amount, $playerId, $playerPromoId, false, $bankId, $orderId);
            $result = array('success' => true, 'type' => self::REDIRECT_TYPE_URL, 'url' => $url);
            return $result;
        }

        $order = $this->CI->sale_order->getSaleOrderById($orderId);
        $playerDetails = $this->CI->player_model->getPlayerDetails($playerId);
        $firstname = (isset($playerDetails[0]) && !empty($playerDetails[0]['firstName'])) ? $playerDetails[0]['firstName']  : '无名';

        $params = array();
        $params['txnType']       = '01';
        $params['txnSubType']    = '21';
        $params['secpVer']       = 'icp3-1.1';
        $params['secpMode']      = 'perm';
        $params['macKeyId']      = $this->getSystemInfo("account");
        $params['orderDate']     = date('Ymd');
        $params['orderTime']     = date('Hms');
        $params['merId']         = $this->getSystemInfo("account");
        $params['orderId']       = $order->secure_id;
        $params['pageReturnUrl'] = $this->getReturnUrl($orderId);
        $params['notifyUrl']     = $this->getNotifyUrl($orderId);
        $params['productTitle']  = 'deposit';
        $params['txnAmt']        = $this->convertAmountToCurrency($amount);
        $params['currencyCode']  = '156';
        $params['accName']       = $firstname;
        $params['bankNum']       = $this->getBankCode($order->direct_pay_extra_info);
        $params['timeStamp']     = date('YmdHms');
        $params['sthtml']        = 1;
        $params['mac']           = $this->sign($params);

        $this->CI->utils->debug_log("=====================lelipay  generatePaymentUrlForm", $params);

        return $this->processPaymentUrlForm($params);
    }

    public abstract function getBankCode($direct_pay_extra_info);

    # Display QRCode get from curl
    protected function processPaymentUrlFormPost($params) {
        $response = $this->submitPostForm($this->getSystemInfo('url'), $params, false, $params['orderId']);

        $this->CI->utils->debug_log('========================================lelipay processPaymentUrlFormPost response', $response);

        if(strpos($response, '<html>') !== false){
            return array(
                'success' => true,
                'type' => self::REDIRECT_TYPE_HTML, # will be redirected to a view for error display
                'html' => $response,
            );
        }

        $response = json_decode($response,true);
        $this->CI->utils->debug_log('========================================lelipay processPaymentUrlFormPost response json to array', $response);
        if(isset($response['txnStatus']) && $response['txnStatus'] == self::RESULT_CODE_PROCESSING) {
            return array(
                'success' => true,
                'type' => self::REDIRECT_TYPE_URL,
                'url' => $response['extInfo'],
            );
        }
        else if($response['txnStatus'] == self::RESULT_CODE_FAILED && isset($response['respMsg'])) {
            return array(
                'success' => false,
                'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
                'message' => $response['respMsg']
            );
        } else {
            return array(
                'success' => false,
                'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
                'message' => lang('Invalidate API response')
            );
        }

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

        $raw_post_data = file_get_contents('php://input', 'r');
        $this->CI->utils->debug_log("=====================lelipay raw_post_data", $raw_post_data);
        parse_str($raw_post_data ,$params);
        $this->CI->utils->debug_log("========================lelipay parse_str params", $params);

        $this->CI->utils->debug_log("=====================lelipay callbackFrom $source params", $params);

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
            $this->CI->sale_order->updateExternalInfo($order->id, $params['orderId'], '', null, null, $response_result_id);
            if ($source == 'browser') {
                $success = $this->CI->sale_order->browserCallbackSaleOrder($order->id, 'auto broswer callback ' . $this->getPlatformCode(), false);
            } elseif ($source == 'server') {
                $this->approveSaleOrder($order->id, 'auto server callback ' . $this->getPlatformCode(), false);
            }
        }

        $result['success'] = $success;
        if ($processed) {
            $result['message'] = self::RETURN_SUCCESS_CODE;
        }

        if ($source == 'browser') {
            $result['next_url'] = $this->getPlayerBackUrl();
            $result['go_success_page'] = true;
        }

        return $result;
    }

    ## Validates whether the callback from API contains valid info and matches with the order
    ## Reference: code sample, callback.php
    private function checkCallbackOrder($order, $fields, &$processed = false) {
        $requiredFields = array(
            'txnAmt', 'txnStatus', 'mac', 'orderId'
        );

        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("=====================lelipay checkCallbackOrder Missing parameter: [$f]", $fields);
                return false;
            }
        }

        # is signature authentic?
        if (!$this->validateSign($fields)) {
            $this->writePaymentErrorLog('=====================lelipay checkCallbackOrder Signature Error', $fields['mac']);
            return false;
        }

        $processed = true; # processed is set to true once the signature verification pass

        if ($fields['txnStatus'] != self::CALLBACK_SUCCESS) {
            $this->writePaymentErrorLog("======================lelipay checkCallbackOrder Payment status is not success", $fields);
            return false;
        }

        if ($fields['txnAmt'] != $this->convertAmountToCurrency($order->amount)) {
            $this->writePaymentErrorLog("=====================lelipay Payment amounts do not match, expected [$order->amount]", $fields);
            return false;
        }

        if ($fields['orderId'] != $order->secure_id) {
            $this->writePaymentErrorLog("======================lelipay checkCallbackOrder order IDs do not match, expected [$order->secure_id]", $fields);
            return false;
        }

        # everything checked ok
        return true;
    }

    public function directPay($order = null) {
        return array('success' => false); # direct pay not supported by this API
    }

    /**
	 *
	 * detail: a static bank list information
	 *
	 * @return array
	 */
	public function getBankListInfoFallback() {
		return array(
			array('label' => '工商银行', 'value' => '01020000'),
			array('label' => '农业银行', 'value' => '01030000'),
			array('label' => '中国银行', 'value' => '01040000'),
			array('label' => '建设银行', 'value' => '01050000'),
			array('label' => '交通银行', 'value' => '03010000'),
			array('label' => '中信银行', 'value' => '03020000'),
			array('label' => '光大银行', 'value' => '03030000'),
			array('label' => '华夏银行', 'value' => '03040000'),
			array('label' => '民生银行', 'value' => '03050000'),
			array('label' => '广发银行', 'value' => '03060000'),
			array('label' => '平安银行', 'value' => '03070000'),
			array('label' => '招商银行', 'value' => '03080000'),
			array('label' => '兴业银行', 'value' => '03090000'),
			array('label' => '浦发银行', 'value' => '03100000'),
			array('label' => '恒丰银行', 'value' => '03110000'),
			array('label' => '上海银行', 'value' => '03130000'),
			array('label' => '北京银行', 'value' => '03131000'),
			array('label' => '南京银行', 'value' => '03133201'),
            array('label' => '杭州银行', 'value' => '03133301'),
			array('label' => '浙商银行', 'value' => '03160000'),
			array('label' => '北京农村商业银行', 'value' => '04020011'),
			array('label' => '上海农商银行', 'value' => '04020031'),
			array('label' => '厦门银行', 'value' => '04023930'),
			array('label' => '邮储银行', 'value' => '04030000'),
			array('label' => '福建海峡银行', 'value' => '04053910'),
			array('label' => '宁波银行', 'value' => '04083320'),
			array('label' => '广州银行', 'value' => '04135810'),
			array('label' => '汉口银行', 'value' => '04145210'),
			array('label' => '大连银行', 'value' => '04202220'),
			array('label' => '苏州银行', 'value' => '04213050'),
			array('label' => '东莞银行', 'value' => '04256020'),
			array('label' => '天津银行', 'value' => '04341100'),
            array('label' => '宁夏银行', 'value' => '04369800'),
			array('label' => '锦州银行', 'value' => '04392270'),
			array('label' => '徽商银行', 'value' => '04403600'),
            array('label' => '重庆银行', 'value' => '04416530'),
			array('label' => '哈尔滨银行', 'value' => '04422610'),
			array('label' => '兰州银行', 'value' => '04478210'),
            array('label' => '江西银行', 'value' => '04484210'),
			array('label' => '吉林银行', 'value' => '04512420'),
			array('label' => '九江银行', 'value' => '04544240'),
            array('label' => '台州银行', 'value' => '04593450'),
            array('label' => '潍坊银行', 'value' => '04624580'),
			array('label' => '泉州银行', 'value' => '04643970'),
			array('label' => '嘉兴银行', 'value' => '04703350'),
            array('label' => '廊坊银行', 'value' => '04721460'),
            array('label' => '浙江泰隆商业银行', 'value' => '04733450'),
			array('label' => '湖州银行', 'value' => '04753360'),
			array('label' => '包商银行', 'value' => '04791920'),
            array('label' => '桂林银行', 'value' => '04916170'),
            array('label' => '柳州银行', 'value' => '04956140'),
			array('label' => '江苏银行', 'value' => '05083000'),
			array('label' => '重庆三峡银行', 'value' => '05426900'),
            array('label' => '晋中银行', 'value' => '05591750'),
			array('label' => '宁波通商银行', 'value' => '05803320'),
			array('label' => '江苏银行', 'value' => '05083000'),
            array('label' => '邯郸市商业银行', 'value' => '05171270'),
            array('label' => '昆山农信社', 'value' => '14023052'),
			array('label' => '江苏省农村信用社联合社', 'value' => '14243000'),
			array('label' => '吴江农商行', 'value' => '14283054'),
            array('label' => '浙江省农村信用社', 'value' => '14293300'),
            array('label' => '广西农村信用社', 'value' => '14436100'),
			array('label' => '吉林农村信用社', 'value' => '14452400'),
			array('label' => '安徽省农村信用社联合社', 'value' => '14473600'),
            array('label' => '海南省农村信用社', 'value' => '14486400'),
            array('label' => '重庆农村商业银行', 'value' => '15136900'),
			array('label' => '富滇银行', 'value' => '64667310'),
			array('label' => '广东南粤银行', 'value' => '64895910'),
		);
	}

    # -- signatures --
    # Reference: PHP Demo
    public function sign($params) {
        $sign = $this->createSignStr($params);
        return $sign;
    }

    public function createSignStr($params) {
        ksort($params);
        $signStr = '';
        foreach($params as $key => $value) {
            if($key == 'mac' || !isset($value)) {
                continue;
            }
            $signStr .= "$key=$value&";
        }
        $signStr .= 'k='.$this->getSystemInfo('key');
        return md5($signStr);
    }

    public function validateSign($params) {
        $signature = $params['mac'];
        unset($params['mac']);
        $sign = $this->sign($params);
        if ( $signature == $sign ) {
            return true;
        } else {
            return false;
        }
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
    protected function convertAmountToCurrency($amount) {
        return number_format($amount * 100, 0, '.', '');
    }

}