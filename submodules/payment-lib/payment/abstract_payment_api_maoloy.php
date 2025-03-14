<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';
/**
 * MAOLOY 仁信支付
 *
 * * MAOLOY_PAYMENT_API, ID: 203
 * * MAOLOY_ALIPAY_PAYMENT_API, ID: 204
 * * MAOLOY_WEIXIN_PAYMENT_API, ID: 205
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://get.yichigo.com/online/gateway
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
abstract class Abstract_payment_api_maoloy extends Abstract_payment_api {
	const BANK_TYPE_WEIXIN     = 'WEIXIN';
	const BANK_TYPE_WEIXIN_WAP = 'WEIXINWAP';
	const BANK_TYPE_ALIPAY     = 'ALIPAY';
	const BANK_TYPE_ALIPAY_WAP = 'ALIPAYWAP';

	const RETURN_SUCCESS_CODE = 'ok';
	const CALLBACK_SUCCESS = '1';

	public function __construct($params = null) {
		parent::__construct($params);
	}

	public abstract function getBankType($direct_pay_extra_info);

	public function generatePaymentUrlForm($orderId, $playerId, $amount, $orderDateTime, $playerPromoId = null, $enabledSecondUrl = true, $bankId = null) {
		if ($this->shouldRedirect($enabledSecondUrl)) {
			$url = $this->CI->utils->getPaymentUrl($this->getSystemInfo('second_url'), $this->getPlatformCode(), $amount, $playerId, $playerPromoId, false, $bankId, $orderId);
			$result = array('success' => true, 'type' => self::REDIRECT_TYPE_URL, 'url' => $url);
			return $result;
		}

		$order = $this->CI->sale_order->getSaleOrderById($orderId);

        $params = array();
		$params['version']     = '3.0';
		$params['method']      = 'Qs.online.interface';
		$params['partner']     = $this->getSystemInfo("account");
		$params['banktype']    = $this->getBankType($order->direct_pay_extra_info);
		$params['paymoney']    = $this->convertAmountToCurrency($amount);
		$params['ordernumber'] = $order->secure_id;
		$params['callbackurl'] = $this->getNotifyUrl($orderId);
		$params['hrefbackurl'] = $this->getReturnUrl($orderId);
		$params['sign']        = $this->sign($params);
        $this->CI->utils->debug_log('=====================maoloy generatePaymentUrlForm params', $params);

		return array(
			'success' => true,
			'type' => self::REDIRECT_TYPE_FORM,
			'url' => $this->getSystemInfo('url'),
			'params' => $params,
			'post' => true,
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

        $this->CI->utils->debug_log("=====================maoloy callbackFrom $source params", $params);

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
            $this->CI->sale_order->updateExternalInfo($order->id, $params['sysnumber'], '', null, null, $response_result_id);
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
            'partner', 'ordernumber', 'orderstatus', 'paymoney', 'sysnumber', 'sign'
        );

        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("=====================maoloy checkCallbackOrder Missing parameter: [$f]", $fields);
                return false;
            }
        }

        # is signature authentic?
        if (!$this->validateSign($fields)) {
            $this->writePaymentErrorLog('=====================maoloy checkCallbackOrder Signature Error', $fields);
            return false;
        }

        $processed = true; # processed is set to true once the signature verification pass

		if ($fields['orderstatus'] != self::CALLBACK_SUCCESS) {
			$this->writePaymentErrorLog("======================522zf checkCallbackOrder Payment status is not success", $fields);
			return false;
		}

        if ($fields['paymoney'] != $this->convertAmountToCurrency($order->amount)) {
            $this->writePaymentErrorLog("======================maoloy checkCallbackOrder Payment amount is wrong, expected [$order->amount]", $fields);
            return false;
        }

        if ($fields['ordernumber'] != $order->secure_id) {
            $this->writePaymentErrorLog("======================maoloy checkCallbackOrder order IDs do not match, expected [$order->secure_id]", $fields);
            return false;
        }

        # everything checked ok
        return true;
    }

	public function directPay($order = null) {
		return array('success' => false); # direct pay not supported by this API
	}

	protected function getBankListInfoFallback() {
		return array(
			array('label' => '微信', 'value' => 'WEIXIN'),
			array('label' => '微信手机', 'value' => 'WEIXINWAP'),
			array('label' => '支付宝', 'value' => 'ALIPAY'),
			array('label' => '财付通', 'value' => 'TENPAY'),
			array('label' => '工商银行', 'value' => 'ICBC'),
			array('label' => '农业银行', 'value' => 'ABC'),
			array('label' => '建设银行', 'value' => 'CCB'),
			array('label' => '中国银行', 'value' => 'BOC'),
			array('label' => '招商银行', 'value' => 'CMB'),
			array('label' => '北京银行', 'value' => 'BCCB'),
			array('label' => '交通银行', 'value' => 'BOCO'),
			array('label' => '兴业银行', 'value' => 'CIB'),
			array('label' => '南京银行', 'value' => 'NJCB'),
			array('label' => '民生银行', 'value' => 'CMBC'),
			array('label' => '光大银行', 'value' => 'CEB'),
			array('label' => '平安银行', 'value' => 'PINGANBANK'),
			array('label' => '渤海银行', 'value' => 'CBHB'),
			array('label' => '东亚银行', 'value' => 'HKBEA'),
			array('label' => '宁波银行', 'value' => 'NBCB'),
			array('label' => '中信银行', 'value' => 'CTTIC'),
			array('label' => '广发银行', 'value' => 'GDB'),
			array('label' => '上海银行', 'value' => 'SHB'),
			array('label' => '上海浦东发展银行', 'value' => 'SPDB'),
			array('label' => '中国邮政', 'value' => 'PSBS'),
			array('label' => '华夏银行', 'value' => 'HXB'),
			array('label' => '北京农村商业银行', 'value' => 'BJRCB'),
			array('label' => '上海农商银行', 'value' => 'SRCB'),
			array('label' => '深圳发展银行', 'value' => 'SDB'),
			array('label' => '浙江稠州商业银行', 'value' => 'CZB'),
		);
	}

	# -- Private functions --
	private function getNotifyUrl($orderId) {
		return parent::getCallbackUrl('/callback/process/' . $this->getPlatformCode() . '/' . $orderId);
	}

	private function getReturnUrl($orderId) {
		return parent::getCallbackUrl('/callback/browser/success/' . $this->getPlatformCode() . '/' . $orderId);
	}

	protected function convertAmountToCurrency($amount) {
		return number_format($amount, 2, '.', '');
	}

	# -- signature --
	private function prepareSign($data) {
		$array = array();
		$keys = array('version', 'method', 'partner', 'banktype', 'paymoney', 'ordernumber', 'callbackurl');
		foreach ($keys as $key) {
			array_push($array, $key . '=' . $data[$key]);
		}
		return implode($array, '&');
	}

	public function sign($data) {
		$dataStr = $this->prepareSign($data);
		$signature = MD5($dataStr . $this->getSystemInfo('key'));
		return $signature;
	}

	public function prepareVerify($data) {
		$array = array();
		$keys = array('partner', 'ordernumber', 'orderstatus', 'paymoney');
		foreach ($keys as $key) {
			array_push($array, $key . '=' . $data[$key]);
		}
		return implode($array, '&');
	}

	public function verify($data, $signature) {
		$dataStr = $this->prepareVerify($data);
		$original = $dataStr . $this->getSystemInfo('key');
		$signature = MD5($original);

		if ($data['sign']== $signature) {
			return true;
		} else {
			return false;
		}
	}
}
