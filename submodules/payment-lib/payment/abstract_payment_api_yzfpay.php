<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';
require_once dirname(__FILE__) . '/../ProxySoapClient.php';

/**
 * ABB
 *
 * * 'YZFPAY_ALIPAY_PAYMENT_API', ID 5145
 * * 'YZFPAY_WEIXIN_PAYMENT_API', ID 5153
 * Required Fields:
 *
 * * URL
 * * Account - Merchant ID
 * * Key - Signing key
 * * Extra Info
 *
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
abstract class Abstract_payment_api_yzfpay extends Abstract_payment_api {

    const OPTTYPE_ALIPAY = '10'; //支付宝
    const OPTTYPE_ALIPAY_H5 = '20'; //支付宝H5
    const OPTTYPE_WEIXIN = '5'; //微信
    const OPTTYPE_WEIXIN_H5 = '25'; //微信H5
    const OPTTYPE_STATIC_QRCODE = '50'; // 固碼

	const RETURN_SUCCESS_CODE = '1';
    const RETURN_FAILED_CODE = 'FAIL';
    const REQUEST_SUCCESS = '1';
	const PAY_RESULT_SUCCESS = '1';
    const CALLBACK_SUCCESS = '1';


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

		$params['OrgNo'] = $this->getSystemInfo("account");
		$params['TradNo'] = $order->secure_id.'1234567890';
		$params['PayMoney'] = $this->convertAmountToCurrency($amount); //分
		$params['Code'] = 'yzfpay'; #亿支付提现(存款)

		$this->configParams($params, $order->direct_pay_extra_info);
		$params['SecretValue'] = $this->sign($params);

		$this->CI->utils->debug_log("=====================yzfpay generatePaymentUrlForm", $params);

		return $this->processPaymentUrlForm($params);
	}

	# Submit POST form
	protected function processPaymentUrlFormPost($params) {

	    $url = $this->getSystemInfo('url');
	    $this->CI->utils->debug_log("=====================yzfpay processPaymentUrlFormPost URL", $url);
		return array(
			'success' => true,
			'type' => self::REDIRECT_TYPE_FORM,
			'url' => $url,
			'params' => $params,
			'post' => true,
		);
	}

    # Display QRCode get from curl
    protected function processPaymentUrlFormQRCode($params) {

        $jsonStr = json_encode($params);
        $fields = array(
                'json' => $jsonStr
                );
        $orderID = substr($params['TradNo'],0,13);
    	$url = $this->getSystemInfo('url').'/PayRecordOpt';
    	$this->CI->utils->debug_log('=====================yzfpay processPaymentUrlFormQRcode scan url',$url);
        $response = $this->submitPostForm($url, $fields, false, $orderID);
        $this->CI->utils->debug_log('=====================yzfpay processPaymentUrlFormQRcode received response', $response);
        $json_data = $this->xmlexploJson($response);
        $this->CI->utils->debug_log('=====================yzfpay processPaymentUrlFormQRcode json_data', $json_data);

        $msg = lang('Invalidte API response');

		if(!empty($json_data['SuccessMsg']) && ($json_data['State'] == self::REQUEST_SUCCESS)) {
			if($this->CI->utils->is_mobile()){
				return array(
	                'success' => true,
	                'type' => self::REDIRECT_TYPE_URL,
	                'url' => $json_data['SuccessMsg'],
	            );
			}else{
				return array(
	                'success' => true,
	                'type' => self::REDIRECT_TYPE_QRCODE,
	                'url' => $json_data['SuccessMsg'],
	            );
			}
        }else {
            if(!empty($json_data['Msg'])) {
                $msg = $json_data['Msg'];
            }
            return array(
                'success' => false,
                'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
                'message' => $msg
            );
        }
    }

    protected function processPaymentUrlFormURL($params) {

        $jsonStr = json_encode($params);
        $fields = array(
                'json' => $jsonStr
                );
        $orderID = substr($params['TradNo'],0,13);
    	$url = $this->getSystemInfo('url').'/PayRecordOpt';
    	$this->CI->utils->debug_log('=====================yzfpay processPaymentUrlFormQRcode scan url',$url);
        $response = $this->submitPostForm($url, $fields, false, $orderID);
        $this->CI->utils->debug_log('=====================yzfpay processPaymentUrlFormQRcode received response', $response);
        $json_data = $this->xmlexploJson($response);
        $this->CI->utils->debug_log('=====================yzfpay processPaymentUrlFormQRcode json_data', $json_data);

        $msg = lang('Invalidte API response');

		if(!empty($json_data['SuccessMsg']) && ($json_data['State'] == self::REQUEST_SUCCESS)) {
			return array(
                'success' => true,
                'type' => self::REDIRECT_TYPE_QRCODE,
                'url' => $json_data['SuccessMsg'],
            );
        }else {
            if(!empty($json_data['Msg'])) {
                $msg = $json_data['Msg'];
            }
            return array(
                'success' => false,
                'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
                'message' => $msg
            );
        }
    }

    public function checkDepositStatus($secureId) {
        $param = array();
        $param['OrgNo'] = $this->getSystemInfo('account');
        $param['OptType'] = $this->CI->utils->is_mobile() ? self::OPTTYPE_ALIPAY: self::OPTTYPE_ALIPAY_H5;
        $param['No'] = '';
        $param['MemberNo'] = '';
        $param['TradNo'] = $secureId.'1234567890';

        $jsonStr = json_encode($param);
        $fields = array(
                'json' => $jsonStr
                );
        $this->CI->utils->debug_log('====================================== yzfpay checkDepositStatus params: ', $param);
        $checkDepositURL = $this->getSystemInfo('url').'/PayRecordDetail';

        $this->CI->utils->debug_log('====================================== yzfpay checkDepositStatus url: ', $checkDepositURL);

        $response = $this->submitPostForm($checkDepositURL, $fields, false, $secureId);

        $this->CI->utils->debug_log('====================================== yzfpay checkDepositStatus result: ', $response );

        return $this->decodeyzfpayDepositStatusResult($response);
    }

    public function decodeyzfpayDepositStatusResult($response){
        if(empty($response)){
            $this->CI->utils->debug_log('======================================yzfpay checkDepositStatus unknown result: ', $response);
            return [
                'success' => FALSE,
                'message' => 'Unknown response data'
            ];
        }
        $json_data = $this->xmlexploJson($response);
        $returnOrderId = $json_data['Model']['TradNo'];
        $returnStatus = $json_data['Model']['StateName'];
        if(!empty($json_data['Model']['TradNo']) && (!empty($json_data['Model']['StateName']))){

            $message = "yzfpay payment success orderId:".$returnOrderId.", Status: ".$returnStatus;
			return array('success' => true, 'message' => $message);

        }else{
			$message = "yzfpay payment failed orderId:".$returnOrderId.", Status: ".$returnStatus;
			return array('success' => false, 'message' => $message);
		}


    }

    # Callback URI: /callback/fixed_process/<payment_id>
    public function getOrderIdFromParameters($flds) {
        $this->utils->debug_log('=============================yzfpay getOrderIdFromParameters flds', $flds);
        if(empty($flds) || is_null($flds)){
            $raw_post_data = file_get_contents('php://input', 'r');
            $flds = json_decode($raw_post_data, true);
        }
    	$jsonDecode = json_decode($flds['json'],true);
    	$this->utils->debug_log('=====================yzfpay getOrderIdFromParameters jsonDecode', $jsonDecode);
    	$orderID = substr($jsonDecode['TradNo'],0,13);
        $callbackID = $jsonDecode['TradNo'];
        if(!empty($jsonDecode['OptType'])){
            if($jsonDecode['OptType'] == self::OPTTYPE_STATIC_QRCODE){
                $this->CI->load->model(array('sale_order'));
                $order = $this->CI->sale_order->getSaleOrderByBankOrderId($callbackID);
                $orderId = $order->id;
                $this->utils->debug_log('=================yzfpay getOrderIdFromParameters getSaleOrderByBankOrderId', $order ,$orderId);
            }
        }

        if (isset($orderID)) {
            if(substr($orderID, 0, 1) == 'D'){
                $this->CI->load->model(array('sale_order'));
                $order = $this->CI->sale_order->getSaleOrderBySecureId($orderID);
                $orderId = $order->id;

            }elseif(substr($orderID, 0, 1) == 'W'){
                $trans_id = $orderID;

                $this->CI->load->model(array('wallet_model'));
                $walletAccount = $this->CI->wallet_model->getWalletAccountByTransactionCode($trans_id);

                if(!empty($walletAccount)){
                    $orderId = $walletAccount['transactionCode'];
                }else{
                    $this->utils->debug_log('================================yzfpay callbackOrder transId is empty when getOrderIdFromParameters', $flds);
                }
            }else{
                $this->utils->debug_log('===========================yzfpay callbackOrder direction is not out or in when getOrderIdFromParameters', $flds);
            }
        }
        else{
            $this->utils->debug_log('=====================yzfpay callbackOrder cannot get any order_id when getOrderIdFromParameters', $flds);
        }
        return $orderId;
    }

	/**
	 * detail: This will be called when the payment is async, API server calls our callback page,
	 * When that happens, we perform verifications and necessary database updates to mark the payment as successful
	 *
	 * @param int $orderId order id
	 * @param array $params
	 * @return array
	 */
	public function callbackFromServer($orderId, $params) {
		$response_result_id = parent::callbackFromServer($orderId, $params);

        if(empty($params) || is_null($params)){
            $raw_post_data = file_get_contents('php://input', 'r');
            $params = json_decode($raw_post_data, true);
        }
        $result = array('success' => false, 'message' => 'Payment failed');
        $this->utils->debug_log('=========================yzfpay process callbackFromServer withdrawalResult order id and params', $orderId, $params);

        $jsonDecode = json_decode($params['json'],true);
        $orderID = substr($jsonDecode['TradNo'],0,13);


        if(substr($orderID, 0, 1) == 'W'){

        $order = $this->CI->wallet_model->getWalletAccountByTransactionCode($orderId);

            if (!$this->checkCallbackOrderWithdrawal($order, $params)) {
                return $result;
            }

            if($jsonDecode['State'] == self::CALLBACK_SUCCESS) {
                $this->utils->debug_log('=========================yzfpay withdrawal  was successful: trade ID [%s]', $orderID);

                $msg = sprintf('yzfpay withdrawal was successful: trade ID [%s]', $orderID);
                $this->CI->wallet_model->withdrawalAPIReturnSuccess($orderId, $msg);

                $result['message'] = self::CALLBACK_SUCCESS;
                $result['success'] = true;
            }else {
               $realStateDesc = $orderID;
                $this->errMsg = '['.$realStateDesc.']';
                $msg = sprintf('======================yzfpay withdrawal was not successful: '.$this->errMsg);
                $this->writePaymentErrorLog($msg, $params);

                $result['message'] = $msg;
            }
            return $result;
        }
		return $this->callbackFrom('server', $orderId, $params, $response_result_id);
	}

	/**
	 * detail: This will be called when user redirects back to our page from payment API
	 *
	 * @param int $orderId order id
	 * @param array $params
	 * @return array
	 */
	public function callbackFromBrowser($orderId, $params) {
		$response_result_id = parent::callbackFromBrowser($orderId, $params);
		return $this->callbackFrom('browser', $orderId, $params, $response_result_id);
	}

	# $source can be 'server' or 'browser'
	private function callbackFrom($source, $orderId, $params, $response_result_id) {
		if(empty($params) || is_null($params)){
			$raw_post_data = file_get_contents('php://input', 'r');
        	$params = json_decode($raw_post_data, true);
		}
        $this->CI->utils->debug_log('=======================yzfpay callbackFrom in Function callbackFrom', $params);

        $result = array('success' => false, 'next_url' => null, 'message' => lang('error.payment.failed'));
        $order = $this->CI->sale_order->getSaleOrderById($orderId);
        $processed = false;

        $jsonDecode = json_decode($params['json'],true);
        $orderID = substr($jsonDecode['TradNo'],0,13);
        $this->CI->utils->debug_log('=======================yzfpay callbackFromServer server jsonDecode', $jsonDecode);

        if($source == 'server'){
            $this->CI->utils->debug_log('=======================yzfpay callbackFromServer server callbackFrom', $params);

            if(!$order) {
                return $result;
            }

            if(!empty($jsonDecode['OptType'])) {
                if($jsonDecode['OptType'] == self::OPTTYPE_STATIC_QRCODE) {
                    if(!$this->checkCallbackOrderStaticQrcode($order, $params, $processed)) {
                        return $result;
                    }
                }
            }
            elseif (!$this->checkCallbackOrder($order, $params, $processed)) {
                 $this->utils->debug_log('=============================3');
                return $result;
            }
        }


		# Update order payment status and balance
		$success=true;

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
            $this->CI->sale_order->updateExternalInfo($order->id,
                $orderID, 'Third Party Payment (No Bank Order Number)', # no info available
                null, null, $response_result_id);
			if ($source == 'browser') {
				$success = $this->CI->sale_order->browserCallbackSaleOrder($order->id, 'auto broswer callback ' . $this->getPlatformCode(), false);
			} elseif ($source == 'server') {
				$this->approveSaleOrder($order->id, 'auto server callback ' . $this->getPlatformCode(), false);
			}
		}

		$result['success'] = $success;
		if ($success) {
			$result['message'] = self::RETURN_SUCCESS_CODE;
		} else {
			$result['message'] = $processed ? self::RETURN_SUCCESS_CODE : self::RETURN_FAILED_CODE;
		}

		if ($source == 'browser') {
			$result['next_url'] = $this->getPlayerBackUrl();
			$result['go_success_page'] = true;
		}

		return $result;
	}

	/**
	 * detail: Validates whether the callback from API contains valid info and matches with the order
	 *
	 * @return boolean
	 */

	private function checkCallbackOrder($order, $fields, &$processed = false) {

		$requiredFields = array('json','sign');

		foreach ($requiredFields as $f) {
			if (!array_key_exists($f, $fields)) {
				$this->writePaymentErrorLog("=================yzfpay missing parameter: [$f]", $fields);
				return false;
			}
		}
		$jsonDecode = json_decode($fields['json'],true);
		$orderID = substr($jsonDecode['TradNo'],0,13);

		if ($jsonDecode['State'] != self::PAY_RESULT_SUCCESS) {
			$payStatus = $jsonDecode['State'];
			$this->writePaymentErrorLog("=====================yzfpay Payment was not successful, payStatus is [$payStatus]", $jsonDecode);
			return false;
		}

		if ( $this->convertAmountToCurrency($order->amount) != floatval( $jsonDecode['PayMoney'] )
		) {
			$this->writePaymentErrorLog("=====================yzfpay Payment amounts do not match, expected [$order->amount]", $jsonDecode);
			return false;
		}

        if ($orderID != $order->secure_id) {
            $this->writePaymentErrorLog("=====================yzfpay checkCallbackOrder order IDs do not match, expected [$order->secure_id]", $jsonDecode);
            return false;
        }

        # is signature authentic?
        if (!$this->verifySignature($fields)) {
            $this->writePaymentErrorLog('=====================yzfpay checkCallbackOrder verify signature Error', $fields);
            return false;
        }

		$processed = true; # processed is set to true once the signature verification pass

		# everything checked ok
		return true;
	}

    private function checkCallbackOrderStaticQrcode($order, $fields, &$processed = false) {

        $requiredFields = array('json','sign');

        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("=================yzfpay checkCallbackOrderStaticQrcode missing parameter: [$f]", $fields);
                return false;
            }
        }
        $jsonDecode = json_decode($fields['json'],true);

        if ($jsonDecode['State'] != self::PAY_RESULT_SUCCESS) {
            $payStatus = $jsonDecode['State'];
            $this->writePaymentErrorLog("=====================yzfpay checkCallbackOrderStaticQrcode Payment was not successful, payStatus is [$payStatus]", $jsonDecode);
            return false;
        }

        if ( $this->convertAmountToCurrency($order->amount) != floatval( $jsonDecode['PayMoney'] )
        ) {
            $this->writePaymentErrorLog("=====================yzfpay checkCallbackOrderStaticQrcodePayment amounts do not match, expected [$order->amount]", $jsonDecode);
            return false;
        }


        $callbackID = substr($order->bank_order_id,-23);
        if ($jsonDecode['TradNo'] != $callbackID) {
            $this->writePaymentErrorLog("=====================yzfpay checkCallbackOrderStaticQrcode order IDs do not match, expected [$callbackID]", $jsonDecode);
            return false;
        }

        # is signature authentic?
        if (!$this->verifySignature($fields)) {
            $this->writePaymentErrorLog('=====================yzfpay checkCallbackOrderStaticQrcode verify signature Error', $fields);
            return false;
        }

        $processed = true; # processed is set to true once the signature verification pass

        # everything checked ok
        return true;
    }

    public function checkCallbackOrderWithdrawal($order, $fields) {
        # does all required fields exist in the header?
        $requiredFields = array('json','sign');

        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("==================yzfpay checkCallbackOrderWithdrawal missing parameter: [$f]", $fields);
                return false;
            }
        }
        $jsonDecode = json_decode($fields['json'],true);
        $orderID = substr($jsonDecode['TradNo'],0,13);

        if ($orderID != $order['transactionCode']) {
            $this->writePaymentErrorLog("======================yzfpay checkCallbackOrderWithdrawal type2 order IDs do not match, expected [$order->secure_id]", $jsonDecode);
            return false;
        }

        if ($jsonDecode['State'] != self::CALLBACK_SUCCESS) {
            $this->writePaymentErrorLog("======================yzfpay checkCallbackOrderWithdrawal Payment status is not success", $jsonDecode);
            return false;
        }

        if ($jsonDecode['PayMoney'] != $this->convertAmountToCurrency($order['amount'])) {
            $this->writePaymentErrorLog('======================yzfpay checkCallbackOrderWithdrawal payment amount is wrong, expected =>'. $order['amount'], $jsonDecode);
            return false;
        }

        if (!$this->verifySignature($fields)) {
            $this->writePaymentErrorLog('======================yzfpay checkCallbackOrderWithdrawal signature Error', $fields);
            return false;
        }

        # everything checked ok
        return true;
    }

	public function directPay($order = null) {
		return array('success' => false); # direct pay not supported by this API
	}

	public function getBankListInfoFallback() {
		return array(
            array('label' => '工商银行', 'value' => '1102'),
            array('label' => '农业银行', 'value' => '1103'),
            // array('label' => '中国银行', 'value' => 'BOC'),
            array('label' => '建设银行', 'value' => '1105'),
            // array('label' => '农业发展银行', 'value' => '203'),
            array('label' => '交通银行', 'value' => '1301'),
            // array('label' => '中信银行', 'value' => 'ECITIC'),
            array('label' => '光大银行', 'value' => '1311'),
            array('label' => '华夏银行', 'value' => '1304'),
            array('label' => '民生银行', 'value' => '1305'),
            array('label' => '广发银行', 'value' => '1460'),
            // array('label' => '平安银行', 'value' => 'PINGAN'),
            array('label' => '招商银行', 'value' => '1308'),
            // array('label' => '兴业银行', 'value' => 'CIB'),
            // array('label' => '浦发银行', 'value' => 'SPDB'),
            array('label' => '北京银行', 'value' => '1313'),
            // array('label' => '恒丰银行', 'value' => '315'),
            // array('label' => '浙商银行', 'value' => '316'),
            // array('label' => '渤海银行', 'value' => 'BHB'),
            array('label' => '上海银行', 'value' => '1310'),
            array('label' => '邮政储蓄银行', 'value' => '1312'),
            // array('label' => '徽商银行', 'value' => '440'),
            // array('label' => '广州市商业银行', 'value' => 'GRCBANK')
		);
	}

	# -- Private functions --
	/**
	 * detail: After payment is complete, the gateway will invoke this URL asynchronously
	 *
	 * @param int $orderId
	 * @return void
	 */
	private function getNotifyUrl($orderId) {
		return parent::getCallbackUrl('/callback/process/' . $this->getPlatformCode() . '/' . $orderId);
	}

	/**
	 * detail: After payment is complete, the gateway will send redirect back to this URL
	 *
	 * @param int $orderId
	 * @return void
	 */
	private function getReturnUrl($orderId) {
		return parent::getCallbackUrl('/callback/browser/success/' . $this->getPlatformCode() . '/' . $orderId);
	}

	/**
	 * detail: Format the amount value for the API
	 *
	 * @param float $amount
	 * @return float
	 */
	protected function convertAmountToCurrency($amount) {
		return number_format($amount*100, 0, '.', '');
	}

	# -- private helper functions --

	/**
	 * detail: getting the signature
	 *
	 * @param array $data
	 * @return	string
	 */

    public function xmlexploJson($response){
        preg_match('/(<string xmlns="http:\/\/www\.sys\.com\/">)([\s\S]*)<\/string>/', $response, $xmlexplo);
        $this->utils->debug_log("========================yzfpay checkDepositStatus response xmlexplo ", $xmlexplo);
        $json_data = json_decode($xmlexplo['2'], true);
        $this->utils->debug_log("========================yzfpay checkDepositStatus response json_data ", $json_data);
        return $json_data;
    }
	public function sign($params) {
		$Today = date('Ymd');
		$signStr = $params['OrgNo'].$Today.$this->getSystemInfo('key');
        $sign=strtoupper(md5($signStr));
	
		return $sign;
	}

	public function verifySignature($data) {
	    $callback_sign = $data['sign'];
        $signStr = $this->getSystemInfo('account').$this->getSystemInfo('key');
        $sign=strtoupper(md5($signStr));
    
        return (strcasecmp($sign, $callback_sign) !== 0)?false:true;
    }
}
