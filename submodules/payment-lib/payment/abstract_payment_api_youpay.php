<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * youpay 友付
 *
 * * YOUPAY_ALIPAY_PAYMENT_API, ID: 5327
 * * YOUPAY_ALIPAY_H5_PAYMENT_API, ID: 5327
 * * YOUPAY_PAYMENT_API, ID: 5337
 * * YOUPAY_WEIXIN_PAYMENT_API, ID: 5338
 * * YOUPAY_WEIXIN_H5_PAYMENT_API, ID: 5339
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
abstract class Abstract_payment_api_youpay extends Abstract_payment_api {
	const PAYTYPE_ALIPAY      = 'pay.alipay.native'; #支付宝扫码
    const PAYTYPE_WEIXIN      = 'pay.weixin.native'; #微信扫码
    const PAYTYPE_QQPAY       = 'pay.qq.native'; #QQ钱包扫码
    const PAYTYPE_UNIONPAY    = 'pay.union.native'; #银联扫码
    const PAYTYPE_JDPAY       = 'pay.jd.native'; #京东扫码
    const PAYTYPE_ALIPAY_WAP  = 'pay.alipay.wap'; #支付宝h5
    const PAYTYPE_WEIXIN_WAP  = 'pay.weixin.wap'; #微信h5
    const PAYTYPE_QQPAY_WAP   = 'pay.qq.wap'; #QQh5

	const RETURN_SUCCESS_CODE = 'success';
    const RETURN_FAILED_CODE  = 'FAIL';
    const REQUEST_SUCCESS     = 'SUCCESS';
	const PAY_RESULT_SUCCESS  = 'pay_success';

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

		$params['version']     = '2.0';
		$params['charset']     = 'UTF-8';
		$params['spid']        = $this->getSystemInfo("account");
		$params['spbillno']    = $order->secure_id;
		$params['tranAmt']     = $this->convertAmountToCurrency($amount); //分
		$params['backUrl']     = $this->getReturnUrl($orderId);
		$params['notifyUrl']   = $this->getNotifyUrl($orderId);
		$params['productName'] = 'Deposit';
		$params['signType']    = 'MD5';
		$this->configParams($params, $order->direct_pay_extra_info);
		$params['sign']        = $this->sign($params);
		$this->CI->utils->debug_log("=====================youpay generatePaymentUrlForm", $params);

		return $this->processPaymentUrlForm($params);
	}

	# Submit POST form
	protected function processPaymentUrlFormPost($params) {
	    $post_xml_data = $this->array2xml($params);
		$this->CI->utils->debug_log('=====================youpay post_xml_data', $post_xml_data);
		$postData = array('req_data' => $post_xml_data);

		return array(
			'success' => true,
			'type' => self::REDIRECT_TYPE_FORM,
			'url' => $this->getSystemInfo('url'),
			'params' => $postData,
			'post' => true,
		);
	}

    # Display QRCode get from curl
    protected function processPaymentUrlFormQRCode($params) {
        $response = $this->submitPostForm($this->getSystemInfo('url'), $params, false, $params['pay_orderid']);
        $this->CI->utils->debug_log('========================================youpay processPaymentUrlFormQRcode received response', $response);
        $decode_data = json_decode($response,true);
        $this->CI->utils->debug_log('========================================youpay processPaymentUrlFormQRcode response[1] json to array', $decode_data);
        $msg = lang('Invalidte API response');

        if(!empty($decode_data['returncode']) && ($decode_data['code'] == self::REQUEST_SUCCESS)) {
            return array(
                'success' => true,
                'type' => self::REDIRECT_TYPE_QRCODE,
                'url' => $decode_data['qrcode'],
            );
        }else {
            if(!empty($decode_data['retMsg'])) {
                $msg = $decode_data['retMsg'];
            }
            return array(
                'success' => false,
                'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
                'message' => $msg
            );
        }
    }

    protected function processPaymentUrlRedirect($params) {
		$this->CI->utils->debug_log('=========================youpay quickpay scan url', $this->getSystemInfo('url'));

		$post_xml_data = $this->array2xml($params);
		$this->CI->utils->debug_log('=========================youpay quickpay post_xml_data', $post_xml_data);

		$curlConn = curl_init();
		$curlData = array();
		$curlData[CURLOPT_POST] = true;
		$curlData[CURLOPT_URL] = $this->getSystemInfo('url');
		$curlData[CURLOPT_RETURNTRANSFER] = true;
		$curlData[CURLOPT_TIMEOUT] = 120;
		$curlData[CURLOPT_POSTFIELDS] = $post_xml_data;
        $curlData[CURLOPT_HTTPHEADER] = [ "Content-type: text/xml;charset='utf-8'" ];
		curl_setopt_array($curlConn, $curlData);

		curl_setopt($curlConn, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curlConn, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($curlConn, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($curlConn, CURLOPT_SSL_VERIFYHOST, false);

		// Need to specify the referer when doing CURL submit. since we use redirect 2nd url, we can take the HTTP_HOST
		// curl_setopt($curlConn, CURLOPT_REFERER, "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]");

		$response = curl_exec($curlConn);
		$errCode     = curl_errno($curlConn);
        $error       = curl_error($curlConn);
        $statusCode  = curl_getinfo($curlConn, CURLINFO_HTTP_CODE);

        $curlSuccess = ($errCode == 0);
        $response_result_id = $this->submitPreprocess($params, $response, $this->getSystemInfo('url'), $response, array('errCode' => $errCode, 'error' => $error, 'statusCode' => $statusCode), $params['spbillno']);

		$this->CI->utils->debug_log('=====================youpay quickpay xml response', $curlSuccess, $response);

		$response = $this->parseResultXML($response);

		$this->CI->utils->debug_log('=====================youpay quickpay response', $response);

		if($response['retcode'] == self::QRCODE_REPONSE_CODE_SUCCESS) {
			return array(
				'success' => true,
				'type' => self::REDIRECT_TYPE_URL,
				'url' => $response['codeUrl']
			);
		}
		else if(isset($response['retmsg'])) {
			return array(
                'success' => false,
                'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
                'message' => 'Error Code: '.$response['retcode']. ', '.$response['retmsg']
            );
		}else{
			return array(
				'success' => false,
				'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
				'message' => lang('Invalidte API response')
			);
		}
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
        $this->CI->utils->debug_log('=======================youpay callbackFrom in Function callbackFrom', $params);

        $result = array('success' => false, 'next_url' => null, 'message' => lang('error.payment.failed'));
        $order = $this->CI->sale_order->getSaleOrderById($orderId);
        $processed = false;

        if($source == 'server'){
        	$raw_post_data = file_get_contents('php://input', 'r');
			$this->CI->utils->debug_log("=====================youpay callbackFromServer raw_post_data", $raw_post_data);
			$params = $this->parseResultXML($raw_post_data);
            $this->CI->utils->debug_log('=====================youpay callbackFromServer server callbackFrom', $params);

            if (!$order || !$this->checkCallbackOrder($order, $params, $processed)) {
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
            $this->CI->sale_order->updateExternalInfo($order->id, $params['spbillno'], '', null, null, $response_result_id);
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
		$requiredFields = array(
			'retcode', 'retmsg','version','charset','spid','spbillno','transactionId','outTransactionId', 'tranAmt', 'payAmt', 'result', 'sign'
		);

		foreach ($requiredFields as $f) {
			if (!array_key_exists($f, $fields)) {
				$this->writePaymentErrorLog("=====================youpay missing parameter: [$f]", $fields);
				return false;
			}
		}

		# is signature authentic?
        if (!$this->verifySignature($fields)) {
            $this->writePaymentErrorLog('=====================youpay checkCallbackOrder verify signature Error', $fields);
            return false;
        }

		if ($fields['result'] != self::PAY_RESULT_SUCCESS) {
			$payStatus = $fields['result'];
			$this->writePaymentErrorLog("=====================youpay Payment was not successful, payStatus is [$payStatus]", $fields);
			return false;
		}

		if ($fields['payAmt'] != $this->convertAmountToCurrency($order->amount)) {
            #because player need to enter amount at Alipay
            if($this->getSystemInfo('allow_callback_amount_diff')){
            	$diffAmount = abs($this->convertAmountToCurrency($order->amount) - floatval( $fields['payAmt']));
				if ($diffAmount >= 1) {
					$this->writePaymentErrorLog("=====================youpay checkCallbackOrder Payment amounts ordAmt - payAmt > 1, expected [$order->amount]", $fields ,$diffAmount);
					return false;
				}
                $this->CI->utils->debug_log('=====================youpay checkCallbackOrder amount not match expected [$order->amount]');
                $notes = $order->notes . " | callback diff amount, origin was: " . $order->amount;
                $this->CI->sale_order->fixOrderAmount($order->id, $fields['payAmt'], $notes);
            }
            else{
                $this->writePaymentErrorLog("=====================youpay checkCallbackOrder Payment amounts do not match, expected [$order->amount]", $fields);
                return false;
         	}
        }

        if ($fields['spbillno'] != $order->secure_id) {
            $this->writePaymentErrorLog("=====================youpay checkCallbackOrder order IDs do not match, expected [$order->secure_id]", $fields);
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
            array('label' => '工商银行', 'value' => '1001'),
            array('label' => '农业银行', 'value' => '1002'),
            array('label' => '中国银行', 'value' => '1003'),
            array('label' => '建设银行', 'value' => '1004'),
            // array('label' => '农业发展银行', 'value' => '203'),
            array('label' => '交通银行', 'value' => '1005'),
            array('label' => '中信银行', 'value' => '1007'),
            array('label' => '光大银行', 'value' => '1008'),
            array('label' => '华夏银行', 'value' => '1009'),
            array('label' => '民生银行', 'value' => '1010'),
            array('label' => '广发银行', 'value' => '1017'),
            array('label' => '平安银行', 'value' => '1011'),
            array('label' => '招商银行', 'value' => '1012'),
            // array('label' => '兴业银行', 'value' => '309'),
            array('label' => '浦发银行', 'value' => '1014'),
            array('label' => '北京银行', 'value' => '1016'),
            // array('label' => '恒丰银行', 'value' => '315'),
            // array('label' => '浙商银行', 'value' => '316'),
            // array('label' => '渤海银行', 'value' => '318'),
            array('label' => '上海银行', 'value' => '1025'),
            array('label' => '邮储银行', 'value' => '1006'),
            // array('label' => '徽商银行', 'value' => '440'),
            // array('label' => '广州市商业银行', 'value' => '441')
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
		return number_format($amount*100, 2, '.', '');
	}

	# -- private helper functions --

	/**
	 * detail: getting the signature
	 *
	 * @param array $data
	 * @return	string
	 */
	public function sign($params) {
        $signStr = $this->createSignStr($params);
        $sign=strtoupper(md5($signStr));
	
		return $sign;

	}

    public function verifySignature($data) {
	    $callback_sign = $data['sign'];
        $signStr = $this->createSignStr($data);
        $sign=strtoupper(md5($signStr));
    
        return (strcasecmp($sign, $callback_sign) !== 0)?false:true;
    }

    public function createSignStr($params) {
    	ksort($params);
        $signStr = '';
        foreach($params as $key => $value) {
            if($value == null || $key == 'sign' || $key == 'signType') {
                continue;
            }
            $signStr .= "$key=$value&";
        }
        $signStr .= 'key='.$this->getSystemInfo('key');
    	return $signStr;
	}

	public function array2xml($values){
		if (!is_array($values) || count($values) <= 0) {
		    return false;
		}

		$xml = "<xml>";
		foreach ($values as $key => $val) {
			if (is_numeric($val)) {
				$xml .= "<" . $key . ">" . $val . "</" . $key . ">";
			} else {
				$xml .= "<" . $key . "><![CDATA[" . $val . "]]></" . $key . ">";
			}
		}
		$xml .= "</xml>";
		return $xml;
	}

	public function parseResultXML($resultXml) {
		$result = NULL;
		$obj = simplexml_load_string($resultXml);
		$arr = $this->CI->utils->xmlToArray($obj);
		$this->CI->utils->debug_log('====================youpay parseResultXML', $arr);
		$result = $arr;

		return $result;
	}
}
