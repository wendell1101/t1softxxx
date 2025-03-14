<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';
/**
 * YONGPAY
 *
 * * YONGPAY_PAYMENT_API, ID: 798
 * * YONGPAY_ALIPAY_PAYMENT_API, ID: 800
 * * YONGPAY_ALIPAY_WAP_PAYMENT_API, ID: 804
 * * YONGPAY_QQPAY_PAYMENT_API, ID: 801
 * * YONGPAY_QQPAY_WAP_PAYMENT_API, ID: 805
 * * YONGPAY_WEIXIN_PAYMENT_API, ID: 802
 * * YONGPAY_WEIXIN_WAP_PAYMENT_API, ID: 806
 * * YONGPAY_JDPAY_PAYMENT_API, ID: 803
 * * YONGPAY_JDPAY_WAP_PAYMENT_API, ID: 807
 * * YONGPAY_QUICKPAY_PAYMENT_API, ID: 799
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://pay.spay888.net/load
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
abstract class Abstract_payment_api_yongpay extends Abstract_payment_api {

	const DEFAULTNANK_BANK = 'BANK';
    const DEFAULTNANK_QUICKPAY = 'BANK_WAP';
	const DEFAULTNANK_UNIONPAY = 'YL_EXPRESS';
	const DEFAULTNANK_ALIPAY = 'ALIPAY';
	const DEFAULTNANK_ALIPAY_WAP = 'ALIPAY_WAP';
	const DEFAULTNANK_WEIXIN = 'WECHAT';
	const DEFAULTNANK_WEIXIN_WAP = 'WECHAT_WAP';
	const DEFAULTNANK_QQPAY = 'QQ';
    const DEFAULTNANK_QQPAY_WAP = 'QQ_WAP';
	const DEFAULTNANK_JDPAY = 'JD';
    const DEFAULTNANK_JDPAY_WAP = 'JD_WAP';

	const RETURN_SUCCESS_CODE = 'SUCCESS';
    const RECALL_SUCCESS_CODE = '2';
    const CHECK_SUCCESS_CODE = '00';


	public function __construct($params = null) {
		parent::__construct($params);
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

		$params = array();
		$params['pay_memberid']      = $this->getSystemInfo("account");
		$params['pay_orderid']       =  $order->secure_id;
		$params['pay_amount']        = $this->convertAmountToCurrency($amount);
		$params['pay_applydate']     =  date("YmdHis");
		$this->configParams($params, $order->direct_pay_extra_info);
		$params['pay_notifyurl']     = $this->getNotifyUrl($orderId);
		//$params['pay_callbackurl'] = $this->getReturnUrl($orderId);
		$params['pay_md5sign']       = $this->sign($params);

		$this->CI->utils->debug_log('=========================yongpay generatePaymentUrlForm', $params);
		return $this->processPaymentUrlForm($params);
	}


	protected function processPaymentUrlFormPost($params) {
		return array(
			'success' => true,
			'type' => self::REDIRECT_TYPE_FORM,
			'url' => $this->getSystemInfo('url'),
			'params' => $params,
			'post' => true
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

        $this->CI->utils->debug_log("=====================yongpay callbackFrom $source params", $params);

        if($source == 'server'){
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
            $this->CI->sale_order->updateExternalInfo($order->id, $params['orderid'], '', null, null, $response_result_id);
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

	private function checkCallbackOrder($order, $fields, &$processed) {
		# does all required fields exist?
		$requiredFields = array(
			'memberid', 'orderid', 'amount', 'datetime', 'returncode', 'reserved', 'sign'
		);
		foreach ($requiredFields as $f) {
			if (!array_key_exists($f, $fields)) {
				$this->writePaymentErrorLog("=========================yongpay checkCallbackOrder missing parameter: [$f]", $fields);
				return false;
			}
		}

		# is signature authentic?
		if ($fields['sign'] != $this->validateSign($fields)) {
			$this->writePaymentErrorLog('=========================yongpay checkCallbackOrder validateSign Error', $fields);
			return false;
		}

		$processed = true; # processed is set to true once the signature verification pass

		if ($fields['returncode'] != self::RECALL_SUCCESS_CODE) {
			$this->writePaymentErrorLog('=========================yongpay checkCallbackOrder result['.$fields['v_result'].'] payment was not successful', $fields);
			return false;
		}

		if ($this->convertAmountToCurrency($order->amount) != $fields['amount']) {
			$this->writePaymentErrorLog("=========================yongpay checkCallbackOrder payment amounts do not match, expected [$order->amount]", $fields);
			return false;
		}

		if ($fields['orderid'] != $order->secure_id) {
			$this->writePaymentErrorLog("=========================yongpay checkCallbackOrder order IDs do not match, expected [$order->secure_id]", $fields);
			return false;
		}

		# everything checked ok
		return true;
	}

	public function directPay($order = null) {
		return array('success' => false); # direct pay not supported by this API
	}

	# -- private helper functions --
	protected function getBankListInfoFallback() {
		return array(
            array('label' => '工商银行', 'value' => 'icbc'),
            array('label' => '农业银行', 'value' => 'abc'),
            array('label' => '招商银行', 'value' => 'cmb'),
            array('label' => '建设银行', 'value' => 'ccb'),
            array('label' => '民生银行', 'value' => 'cmbc'),
            array('label' => '中信银行', 'value' => 'ecitic'),
            array('label' => '交通银行', 'value' => 'comm'),
            array('label' => '光大银行', 'value' => 'ceb'),
            array('label' => '北京银行', 'value' => 'bccb'),
            array('label' => '平安银行', 'value' => 'pingan'),
            array('label' => '上海浦东发展银行', 'value' => 'spdb'),
            array('label' => '广发银行', 'value' => 'gdb'),
            array('label' => '渤海银行', 'value' => 'cbnb'),
            array('label' => '宁波银行', 'value' => 'nb'),
            array('label' => '南京银行', 'value' => 'njcb'),
            array('label' => '浙江银行', 'value' => 'cz'),
            array('label' => '上海银行', 'value' => 'bos'),
            array('label' => '华夏银行', 'value' => 'hxb'),
			array('label' => '杭州银行', 'value' => 'hccb')
		);
	}

	public function convertAmountToCurrency($amount) {
		return number_format($amount, 2, '.', '');
	}

	public function getNotifyUrl($orderId) {
		return parent::getCallbackUrl('/callback/process/' . $this->getPlatformCode() . '/' . $orderId);
	}

	private function getReturnUrl($orderId) {
		return parent::getCallbackUrl('/callback/browser/success/' . $this->getPlatformCode() . '/' . $orderId);
	}

	# -- signing --
	public function sign($data) {
        $sign_key = array(
			'pay_memberid', 'pay_orderid', 'pay_amount', 'pay_applydate', 'pay_channelCode', 'pay_bankcode', 'pay_notifyurl'
		);
		$signStr = $this->createSignStr($data,$sign_key);
		$signStr .= "key=" .$this->getSystemInfo('key');

		$sign = strtoupper(md5($signStr));

		return $sign;
    }

	private function validateSign($data) {
        $sign_key = array(
			'amount', 'datetime', 'memberid', 'orderid', 'returncode'
		);
		$origin_sign = $data['sign'];
		$signStr = $this->createSignStr($data, $sign_key);
		$signStr .= "key=" .$this->getSystemInfo('key');

		$sign = strtoupper(md5($signStr));

		return $sign;
	}

	public function createSignStr($params, $sign_key) {
        $signStr = "";
        foreach ($sign_key as $key) {
			if (array_key_exists($key, $params)) {
				$signStr .= $key."^".$params[$key]."&";
			}
		}

		return $signStr;
	}
}