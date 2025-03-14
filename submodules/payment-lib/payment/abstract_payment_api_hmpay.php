<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';
/**
 * HMPAY 汇美支付
 *
 * * HMPAY_PAYMENT_API, ID: 5850
 * * HMPAY_ALIPAY_PACKET_H5_PAYMENT_API, ID: 5855
 * * HMPAY_ALIPAY_H5_PAYMENT_API, ID: 5856
 * * HMPAY_UNIONPAY_PAYMENT_API, ID: 5857
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://www.hmpay1.com:9578/interface/chargebank.aspx
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
abstract class Abstract_payment_api_hmpay extends Abstract_payment_api {
	const ORDERTYPE_TYPE_ONLINEBANK       = 'KUAIJIE';
    const ORDERTYPE_TYPE_ALIPAY_PACKET_H5 = 'ALIPAY';
    const ORDERTYPE_TYPE_ALIPAY_H5        = 'ALIWAP';
    const ORDERTYPE_TYPE_UNIONPAY         = 'VISAWAP';

	const RETURN_SUCCESS_CODE = 'ok';
	const ORDER_STATUS_SUCCESS = '1';

	public function __construct($params = null) {
		parent::__construct($params);
	}

    public function generatePaymentUrlForm($orderId, $playerId, $amount, $orderDateTime, $playerPromoId = null, $enabledSecondUrl = true, $bankId = null) {
        if ($this->shouldRedirect($enabledSecondUrl)) {
            $url = $this->CI->utils->getPaymentUrl($this->getSystemInfo('second_url'), $this->getPlatformCode(), $amount, $playerId, $playerPromoId, false, $bankId, $orderId);
            $result = array('success' => true, 'type' => self::REDIRECT_TYPE_URL, 'url' => $url);
            return $result;
        }

        $order = $this->CI->sale_order->getSaleOrderById($orderId);

        $params = array();
        $params['vsion'] = '1';
        $params['orderid'] = $order->secure_id;
        $params['value'] = $this->convertAmountToCurrency($amount);
        $params['parter'] = $this->getSystemInfo('account');
        $this->configParams($params, $order->direct_pay_extra_info);//
        $params['callbackurl'] = $this->getNotifyUrl($orderId);
        $params['hrefbackurl'] = $this->getReturnUrl($orderId);
        $params['sign'] = $this->sign($params);

        $this->CI->utils->debug_log('=====================hmpay generatePaymentUrlForm params', $params);

        return $this->processPaymentUrlForm($params);
    }

    protected function processPaymentUrlFormRedirect($params) {
		return array(
			'success' => true,
			'type' => self::REDIRECT_TYPE_FORM,
			'url' => $this->getSystemInfo('url'),
			'params' => $params,
			'post' => false, # sent using GET
		);
    }

    public function callbackFromServer($orderId, $params) {
        $response_result_id = parent::callbackFromServer($orderId, $params);
        return $this->callbackFrom('server', $orderId, $params, $response_result_id);
    }

    public function callbackFromBrowser($orderId, $params) {
        $response_result_id = parent::callbackFromBrowser($orderId, $params);
        return $this->callbackFrom('browser', $orderId, $params, $response_result_id);
    }

    private function callbackFrom($source, $orderId, $params, $response_result_id) {
        $result = array('success' => false, 'next_url' => null, 'message' => lang('error.payment.failed'));
        $order = $this->CI->sale_order->getSaleOrderById($orderId);
        $processed = false;

        $this->CI->utils->debug_log("=====================hmpay callbackFrom $source params", $params);

        if(empty($params)){
            $raw_post_data = file_get_contents('php://input', 'r');
            $params = json_decode($raw_post_data, true);
        }

        $this->CI->utils->debug_log("=====================hmpay callbackFrom raw_post_data params", $params);

        #----Get Transaction info----
        if(isset($params['orderid'])){
            $flds = [];
            $flds['orderid'] = mb_convert_encoding($params['orderid'], 'UTF-8', 'UTF-8');
            $flds['action'] = 'query';
            $flds['type'] = '1';
            $flds['parter'] = mb_convert_encoding($params['parter'], 'UTF-8', 'UTF-8');
            $flds['sign'] = $this->checkOrderSign($flds);
            $this->CI->utils->debug_log("=====================hmpay callbackFrom get response", $flds);
            $response = $this->submitGetForm($this->getSystemInfo('url'), $flds, true, $order->secure_id, false);
            $this->CI->utils->debug_log("=====================hmpay callbackFrom get response encode before", $response);
            $response = json_decode($response);
            $this->CI->utils->debug_log("=====================hmpay callbackFrom get response encode after", $response);
        }else{
            $this->writePaymentErrorLog('=====================hmpay callbackFromServer Callback params error', $params);
            $result['return_error'] = lang('hmpay Get Transaction Failed');
            return $result;
        }

        if($source == 'server' && !empty($response)){
            if (!$order || !$this->checkCallbackOrder($order, $response, $processed)) {
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
            $this->CI->sale_order->updateExternalInfo($order->id, $params['orderid'], null, null, null, $response_result_id);
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

	private function checkCallbackOrder($order, $fields, &$processed = false) {

        $requiredFields = array(
            'orderid','ordamt','ordstate','sign','payamt'
        );

        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("=====================hmpay Missing parameter: [$f]", $fields);
                return false;
            }
        }

        # is signature authentic?
        if (!$this->validateSign($fields)) {
            $this->writePaymentErrorLog('=====================hmpay Signature Error', $fields);
            return false;
        }

        if ($fields['ordstate'] != self::ORDER_STATUS_SUCCESS) {
            $this->writePaymentErrorLog('=====================hmpay Payment was not successful', $fields);
            return false;
        }

        $check_amount = $this->convertAmountToCurrency($order->amount);

        if ($fields['payamt'] != $check_amount) {
            $this->writePaymentErrorLog("======================hmpay Payment amount is wrong, expected <= ". $check_amount, $fields);
            return false;
        }

        // if ($fields['orderid'] != $order->secure_id) {
        //     $this->writePaymentErrorLog("=====================hmpay checkCallbackOrder order IDs do not match, expected [$order->secure_id]", $fields);
        //     return false;
        // }

        $processed = true; # processed is set to true once the signature verification pass

        # everything checked ok
        return true;
    }

    private function sign($params) {
        $signStr = "";
        $signStr = "value=".$params['value']."&parter=".$params['parter']."&type=".$params['type']."&orderid=".$params['orderid']."&callbackurl=".$params['callbackurl'];
        $signStr .= $this->getSystemInfo('key');
        $sign = md5($signStr);
        return $sign;
    }

    private function checkOrderSign($params) {
        $signStr = "";
        $signStr = "parter=".$params['parter']."&type=".$params['type']."&orderid=".$params['orderid']."&key=".$this->getSystemInfo('key');
        $sign = md5($signStr);
        return $sign;
    }

    private function validateSign($params) {
        $signStr = "";
        $signStr = "ordamt=".$params['ordamt']."&payamt=".$params['payamt']."&ordstate=".$params['ordstate']."&sysorderid=".$params['sysorderid']."&key=".$this->getSystemInfo('key');
        $sign = md5($signStr);
        return strcasecmp($sign, $params['sign']) === 0;
    }

    protected function getBankListInfoFallback() {
        return array(

            array('label' => '中国银行', 'value' => '963'),
            array('label' => '中国工商银行', 'value' => '967'),
            array('label' => '浦东发展银行', 'value' => '977'),
            array('label' => '中国农业银行', 'value' => '964'),
            array('label' => '招商银行', 'value' => '970'),
            array('label' => '中国建设银行', 'value' => '965'),
            array('label' => '兴业银行', 'value' => '972'),
            array('label' => '广东发展银行', 'value' => '985'),
            array('label' => '深圳发展银行', 'value' => '974'),
            array('label' => '民生银行', 'value' => '980'),
            array('label' => '光大银行', 'value' => '986'),
            array('label' => '中信银行', 'value' => '962'),
            array('label' => '交通银行', 'value' => '981'),
            array('label' => '华夏银行', 'value' => '982'),
            array('label' => '宁波银行', 'value' => '900'),
            array('label' => '平安银行', 'value' => '978'),
            array('label' => '上海银行', 'value' => '975'),
            array('label' => '南京银行', 'value' => '979'),
            array('label' => '渤海银行', 'value' => '988'),
            array('label' => '东亚银行', 'value' => '987'),
            array('label' => '北京银行', 'value' => '989'),
            array('label' => '浙江稠州商业银行', 'value' => '969'),
            array('label' => '北京农村商业银行', 'value' => '990'),
            array('label' => '中国邮政储蓄银行', 'value' => '971'),

            // array('label' => '浙商银行', 'value' => '968'),
            // array('label' => '顺德农村信用合作社', 'value' => '973'),
            // array('label' => '上海农村商业银行', 'value' => '976'),
            // array('label' => '杭州银行', 'value' => '983'),
            // array('label' => '广州市农村信用社|广州市商业银行', 'value' => '984'),
        );
    }

    public function directPay($order = null) {
        return array('success' => false); # direct pay not supported by this API
    }

    # -- Private functions --
    protected function getNotifyUrl($orderId) {
        return parent::getCallbackUrl('/callback/process/' . $this->getPlatformCode() . '/' . $orderId);
    }

    protected function getReturnUrl($orderId) {
        return parent::getCallbackUrl('/callback/browser/success/' . $this->getPlatformCode() . '/' . $orderId);
    }

    protected function convertAmountToCurrency($amount) {
        return number_format($amount, 0, '.', '');
    }
}
