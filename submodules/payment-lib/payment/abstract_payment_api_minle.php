<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * MINLE 民乐
 *
 *
 * * MINLE_PAYMENT_API, ID: 485
 * * MINLE_QUICKPAY_PAYMENT_API, ID: 486
 *
 * * MINLEPAY_WEIXIN_PAYMENT_API, ID: 545
 * * MINLEPAY_ALIPAY_PAYMENT_API, ID: 546
 * * MINLEPAY_QQPAY_PAYMENT_API, ID: 547
 * * MINLEPAY_QUICKPAY_PAYMENT_API, ID: 548
 * * MINLEPAY_JDPAY_PAYMENT_API, ID: 549
 * * MINLEPAY_UNIONPAY_PAYMENT_API, ID: 601
 * * MINLEPAY_UNIONPAY_2_PAYMENT_API, ID: 5526
 * * MINLEPAY_UNIONPAY_2_H5_PAYMENT_API, ID: 5527
 *
 * Required Fields:
 * * URL: https://minlepay.com/api/pay
 * * Account
 * * Key
 *
 * * Account: ## User ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
abstract class Abstract_payment_api_minle extends Abstract_payment_api {

    const TYPE_WEIXIN = 'WXCODE';
    const TYPE_WEIXIN_H5 = 'WXH5';
    const TYPE_ALIPAY = 'ALICODE';
    const TYPE_ALIPAY_H5 = 'ALIWAP';
    const TYPE_QQPAY = 'QQCODE';
    const TYPE_QQPAY_H5 = 'QQWAP';
    const TYPE_QUICKPAY = 'QUICK';
    const TYPE_JDPAY = 'JDCODE';
    const TYPE_UNIONPAY = 'YLCODE';
    const TYPE_UNIONPAY_CL = 'CLOUDCODE';
    const TYPE_UNIONPAY_CL_H5 = 'CLOUDH5';
    const TYPE_PAY = 'BANK';

    //渠道类型： 1：PC端 , 2：手机端
    const PAY_CHANNEL_PC = '1';
    const PAY_CHANNEL_WAP = '2';

    const CARD_TYPE = '0'; //0:仅允许使用借记卡支付;1:仅允许使用信用卡支付;2:借记卡和信用卡都能进行支付

	const RETURN_SUCCESS_CODE = 'SUCCESS';
	const TRADE_STATUS_SUCCESS = '1'; //支付状态 0：未支付,1：已支付,2：支付失败

	public function __construct($params = null) {
		parent::__construct($params);
	}

	# Implement these for specific pay type
	protected abstract function configParams(&$params, $direct_pay_extra_info);
	protected abstract function processPaymentUrlForm($params);

	public function generatePaymentUrlForm($orderId, $playerId, $amount, $orderDateTime, $playerPromoId = null, $enabledSecondUrl = true, $bankId = null) {
		if ($this->shouldRedirect($enabledSecondUrl)) {
			$url = $this->CI->utils->getPaymentUrl($this->getSystemInfo('second_url'), $this->getPlatformCode(), $amount, $playerId, $playerPromoId, false, $bankId, $orderId);
			$result = array('success' => true, 'type' => self::REDIRECT_TYPE_URL, 'url' => $url);
			return $result;
		}

		$order = $this->CI->sale_order->getSaleOrderById($orderId);

        $params['mch_id'] = $this->getSystemInfo('account');
		$params['notify_url'] = $this->getNotifyUrl($orderId);
        $params['out_trade_no'] = $order->secure_id;
        $params['body'] = 'deposit';
        $params['total_fee'] = $this->convertAmountToCurrency($amount);
		$this->configParams($params, $order->direct_pay_extra_info);

		if($params['type'] == self::TYPE_QUICKPAY || $params['type'] == self::TYPE_WEIXIN_H5 || $params['type'] == self::TYPE_ALIPAY_H5 || $params['type'] == self::TYPE_QQPAY_H5 || $params['type'] == self::TYPE_UNIONPAY_CL_H5 || $params['type'] == self::TYPE_PAY){
			$params['back_url'] = $this->getReturnUrl($orderId);
		}

		$params['sign'] = $this->sign($params);

		$this->CI->utils->debug_log("=====================minle generatePaymentUrlForm", $params);

        return $this->processPaymentUrlForm($params);
	}

	 protected function processPaymentUrlFormQRCode($params) {
		$post_data = json_encode($params);
		$url = $this->getSystemInfo('url');

		$this->CI->utils->debug_log('=====================minle qrcode scan url', $url);
		$this->CI->utils->debug_log('=====================minle qrcode post data', $post_data);

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);

		$header = array(
			'Content-Type: application/json; charset=utf-8',
			'Content-Length: ' . strlen($post_data),
		);

		curl_setopt($ch, CURLOPT_HTTPHEADER, $header);

		$fullResponse = curl_exec($ch);
		$errCode = curl_errno($ch);
		$error = curl_error($ch);
		$statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

		curl_close($ch);

		$response = json_decode($fullResponse, true);
		$this->CI->utils->debug_log('=====================minle qrcode response', $response);

		$response_result_id = $this->submitPreprocess($params, $response, $url, $fullResponse, array('errCode' => $errCode, 'error' => $error, 'statusCode' => $statusCode), $params['out_trade_no']);

		$msg = lang('Invalidte API response');

		if(isset($response['qr_code']) || isset($response['url'])) {
			$response_url = isset($response['qr_code'])?$response['qr_code']:$response['url'];
			return array(
				'success' => true,
				'type' => self::REDIRECT_TYPE_QRCODE,
				'url' => $response_url
			);
		}
		else {
			if(isset($response['error_msg'])) {
				$msg = $response['error_msg'];
			}

			return array(
				'success' => false,
				'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
				'message' => $msg
			);
		}
	 }

	# Display Quickpay get from curl
	protected function processPaymentUrlFormPost($post_data) {
	    $postUrl = $this->getSystemInfo('url');
        $this->CI->utils->debug_log('=====================minle FormPost scan url', $this->getSystemInfo('url'));
        $this->CI->utils->debug_log('=====================minle FormPost data', $post_data);
        $received = $this->submitPostForm($postUrl, $post_data, false, $post_data['out_trade_no']);
        $this->CI->utils->debug_log('========================================minle received response', $received);
        $response = json_decode($received,true);

        return array(
            'success' => true,
            'type' => self::REDIRECT_TYPE_URL,
            'url' => $response['pay_url'],
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

        $this->CI->utils->debug_log("=====================minle callbackFrom $source params", $params);

		if($source == 'server'){
			$raw_post_data = file_get_contents('php://input');
			$flds = json_decode($raw_post_data, true);
			$params = array_merge( $params, $flds );

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
			$this->CI->sale_order->updateExternalInfo($order->id, $params['out_trade_no'], null, null, null, $response_result_id);
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
			$result['return_error'] = self::RETURN_FAILED_CODE;
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
			'pay_status', 'out_trade_no', 'total_fee'
		);

		foreach ($requiredFields as $f) {
			if (!array_key_exists($f, $fields)) {
				$this->writePaymentErrorLog("=====================minle checkCallbackOrder missing parameter: [$f]", $fields);
				return false;
			}
		}

		if ($fields['pay_status'] != self::TRADE_STATUS_SUCCESS) {
			$this->writePaymentErrorLog('=======================minle checkCallbackOrder payment was not successful', $fields);
			return false;
		}

		if (!$this->CI->utils->compareResultFloat($this->convertAmountToCurrency($order->amount),'=', $fields['total_fee'])) {
		 	$this->writePaymentErrorLog("=====================minle checkCallbackOrder payment amounts do not match, expected [$order->amount]", $fields);
		 	return false;
		}

        if ($fields['out_trade_no'] != $order->secure_id) {
            $this->writePaymentErrorLog("=====================minle checkCallbackOrder payment , Order IDs do not match, expected [$order->secure_id]", $fields);
            return false;
        }

        # is signature authentic?
        if (!$this->validateSign($fields)) {
            $this->writePaymentErrorLog('=====================minle checkCallbackOrder signature Error', $fields);
			return false;
        }

        $processed = true; # processed is set to true once the signature verification pass


		# everything checked ok
		return true;
	}

	public function directPay($order = null) {
		return array('success' => false); # direct pay not supported by this API
	}

    public function getBankListInfoFallback() {
        return array(
            array('label' => '中国工商银行' , 'value' => 'ICBC'),
            array('label' => '中国农业银行' , 'value' => 'ABC'),
            array('label' => '中国建设银行' , 'value' => 'CCB'),
            array('label' => '中国银行' , 'value' => 'BOC'),
            array('label' => '浦发银行' , 'value' => 'SPDB'),
            array('label' => '光大银行' , 'value' => 'CEB'),
            array('label' => '平安银行' , 'value' => 'PINGAN'),
            array('label' => '兴业银行' , 'value' => 'CIB'),
            array('label' => '邮政储蓄银行' , 'value' => 'POST'),
            array('label' => '中信银行' , 'value' => 'ECITIC'),
            array('label' => '华夏银行' , 'value' => 'HXB'),
            array('label' => '招商银行' , 'value' => 'CMBCHINA'),
            array('label' => '广发银行' , 'value' => 'CGB'),
            array('label' => '北京银行' , 'value' => 'BCCB'),
            array('label' => '上海银行' , 'value' => 'SHB'),
            array('label' => '民生银行' , 'value' => 'CMBC'),
            array('label' => '交通银行' , 'value' => 'BOCO'),
            array('label' => '北京农村商业银行' , 'value' => 'BJRCB')
        );
    }

    protected function getBankId($bankType) {

        $bankList = $this->getBankListInfoFallback();
        foreach ($bankList as $list) {
            if ($list['value'] == $bankType) {
                return $list['value'];
            }
        }
    }

	# -- Private functions --
	# After payment is complete, the gateway will invoke this URL asynchronously
	protected function getNotifyUrl($orderId) {
		return parent::getCallbackUrl('/callback/process/' . $this->getPlatformCode() . '/' . $orderId);
	}

	## After payment is complete, the gateway will send redirect back to this URL
	protected function getReturnUrl($orderId) {
		return parent::getCallbackUrl('/callback/browser/success/' . $this->getPlatformCode() . '/' . $orderId);
	}

	## Format the amount value for the API
	protected function convertAmountToCurrency($amount) {
		return number_format($amount, 0, '.', '');
	}

	private function sign($params) {
        $signStr = $this->createSignStr($params);
		$sign = md5($signStr);
		return $sign;
	}

    private function createSignStr($params) {
		ksort($params);
		$signStr = '';
        foreach($params as $key => $value) {
			if( $key == 'error_msg' || $key == 'error_code' || $key == 'sign'){
				continue;
			}
            $signStr.=$key."=".$value."&";
		}
        $signStr = rtrim($signStr, '&').$this->getSystemInfo('key');
		return $signStr;
    }

	##validate whether callback signature is correspond with sign of callback biz_conent or not
	private function validateSign($params){
        $signStr = $this->createSignStr($params);
		$sign = md5($signStr);

		if($params['sign'] == $sign){
			return true;
		}
		else{
			return false;
		}
	}
}
