<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * LGVPAY 彩世商付
 *
 * * LGVPAY_PAYMENT_API, ID: 436
 * * LGVPAY_ALIPAY_PAYMENT_API, ID: 341
 * * LGVPAY_WEIXIN_PAYMENT_API, ID: 342
 *
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
abstract class Abstract_payment_api_lgvpay extends Abstract_payment_api {
	const GATEWAY_BANKS = 'banks';
	const GATEWAY_ALIPAY = 'alipay';
	const GATEWAY_WEIXIN = 'weixin';
	const GATEWAY_QQPAY = 'qq';
	const GATEWAY_JDPAY = 'jd';
	const GATEWAY_UNIONPAY = 'unionpay';
	const QRCODE_REPONSE_CODE_SUCCESS = '000000';
	const ORDER_STATUS_SUCCESS = '2';
	const RETURN_SUCCESS_CODE = 'OK';
	const RETURN_FAILED_CODE = 'faile';

	# Implement these for specific pay type
	protected abstract function configParams(&$params, $direct_pay_extra_info);
	protected abstract function processPaymentUrlForm($params);

	/**
	 * detail: Constructs an URL so that the caller can redirect / invoke it to make payment through this API, See controllers/redirect.php for detail.
	 *
	 * @param int $orderId order id
	 * @param int $playerId player id
	 * @param float $amount amount
	 * @param string $orderDateTime
	 * @param int $playerPromoId
	 * @param string $enabledSecondUrl
	 * @param int $bankId
	 * @return array
	 */
	public function generatePaymentUrlForm($orderId, $playerId, $amount, $orderDateTime, $playerPromoId = null, $enabledSecondUrl = true, $bankId = null) {
		if ($this->shouldRedirect($enabledSecondUrl)) {
			$url = $this->CI->utils->getPaymentUrl($this->getSystemInfo('second_url'), $this->getPlatformCode(), $amount, $playerId, $playerPromoId, false, $bankId, $orderId);
			$result = array('success' => true, 'type' => self::REDIRECT_TYPE_URL, 'url' => $url);
			return $result;
		}

		$order = $this->CI->sale_order->getSaleOrderById($orderId);

		$params = array();
		$params['order_no'] = $order->secure_id;
		$params['return_url'] = $this->getReturnUrl($orderId);
		$params['amount'] = $this->convertAmountToCurrency($amount);
		$params['ip'] = $this->getClientIp();
		$this->configParams($params, $order->direct_pay_extra_info);

		return $this->processPaymentUrlForm($params);
	}

	# Submit POST form
	protected function processPaymentUrlFormPost($params, $url) {
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
		$response = $this->submitPostForm($this->getSystemInfo('url'), $params, false, $params['order_no']);
		$response = json_decode($response, true);

		$msg = lang('Invalidate API response');
		if($response['code'] == self::QRCODE_REPONSE_CODE_SUCCESS) {
			return array(
				'success' => true,
				'type' => self::REDIRECT_TYPE_QRCODE,
				'url' => $response['value']['qrcodeUrl']
			);
		}
		else {
			if($response['msg']) {
				$msg = $response['msg'];
			}

			return array(
				'success' => false,
				'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
				'message' => $msg
			);
		}
	}

	# Display QRCode get from curl
	protected function processPaymentUrlGetMethod($params) {
		$url = $this->getSystemInfo('url');

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0);
        curl_setopt($ch, CURLOPT_TIMEOUT_MS, 10000); //timeout in milliseconds
        //############
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSLCERT, $this->getSystemInfo('pem_path'));
        curl_setopt($ch, CURLOPT_CAINFO, $this->getSystemInfo('crt_path'));
        //###########

		$response = curl_exec($ch);
		$errCode = curl_errno($ch);
		$error = curl_error($ch);
		$header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
		$header = substr($response, 0, $header_size);
		$content = substr($response, $header_size);

		$statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		$last_url = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);

		$statusText = $errCode . ':' . $error;
		curl_close($ch);

		$this->CI->utils->debug_log('url', $url, 'response', $response, 'errCode', $errCode, 'error', $error, 'statusCode', $statusCode);

		$result = json_decode($response, true);
		$this->CI->utils->debug_log('=====================lgvpay get method result', $result);

		$method_data = $this->get_payment_setting($result);
		$check_input_gateway_exist = $this->checkGatewayExist($params['gateway'], $method_data);

		if($check_input_gateway_exist) {
			return $this->processPaymentUrlForwardOrder($params);
		}
		else {
			return array(
				'success' => false,
				'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
				'message' => 'Failed to get the gateway: "'. $params['gateway'] .'" when getting available gateways.'
			);
		}
	}

	public function checkGatewayExist($input_gateway, $available_gateways_arr) {
		if(in_array( $input_gateway, $available_gateways_arr['gate_ways'])) {
			return true;
		}
		return false;
	}

	public function processPaymentUrlForwardOrder($params) {
		$url = $this->getSystemInfo('forward_url');
        $postData = http_build_query($params);

        $msg = lang('Invalidate API response');

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0);
        curl_setopt($ch, CURLOPT_TIMEOUT_MS, 10000); //timeout in milliseconds
        //############
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSLCERT, $this->getSystemInfo('pem_path'));
        curl_setopt($ch, CURLOPT_CAINFO, $this->getSystemInfo('crt_path'));

		$response = curl_exec($ch);
		$errCode = curl_errno($ch);
		$error = curl_error($ch);
		$header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
		$header = substr($response, 0, $header_size);
		$content = substr($response, $header_size);

		$statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		$last_url = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);

		$statusText = $errCode . ':' . $error;
		curl_close($ch);

		$this->CI->utils->debug_log('url', $url, 'response', $response, 'errCode', $errCode, 'error', $error, 'statusCode', $statusCode);

		$result = json_decode($response, true);

		if(is_array($result)) {
			if(!$result['ok']) {
				$msg = $result['error'];
			}
			else {
				$final_url = $result['data']['url'];

				return $this->processPaymentUrlFormPost($result['data']['form'], $final_url);
			}
		}

		return array(
			'success' => false,
			'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
			'message' => $msg
		);
	}

    /**
     * 获取目前开启的所有支付渠道 平台调用
     * @param bool $mobile
     * @return string
     * @throws Exception
     */
    public function get_payment_setting($result, $mobile = false) {
        if (!empty($result) && !isset($result['error_msg'])) {
            $payment_response = $result;

            //#################################
            //获取到第三方 开启的 数据
            if (!is_null($payment_response)) {
                $payment_response_data = $payment_response['data'];
                $final_data['gate_ways'] = [];
                $final_data['gate_ways_cnname'] = [];
                $final_data['gate_ways_image'] = [];
                foreach ($payment_response_data as $key1 => $value1) {
                    if (isset($value1['gateway'])) {
                        array_push($final_data['gate_ways'], $value1['gateway']);
                        if ($value1['gateway'] == 'banks') {
                            //loop banks array
                            foreach ($value1['banks'] as $key2 => $value2) {
                                $value2['currency_min'] = isset($value1['limits']['min']) ? $value1['limits']['min'] : 0;
                                $value2['currency_max'] = isset($value1['limits']['max']) ? $value1['limits']['max'] : 0;
                                $value2['tips'] = $value1['tips'];
                                unset($value2['limits']);
                                $final_data['payment_setting_data']['banks'][$value2['code']] = $value2;
                            }
                        } else {
                            $value1['currency_min'] = isset($value1['limits']['min']) ? $value1['limits']['min'] : 0;
                            $value1['currency_max'] = isset($value1['limits']['max']) ? $value1['limits']['max'] : 0;
                            unset($value1['limits']);
                            $final_data['payment_setting_data'][$value1['gateway']] = $value1;
                        }
                    }
                    array_push($final_data['gate_ways_cnname'], $value1['name']);
                    $final_data['available_gateway_and_name'] = array_combine($final_data['gate_ways'], $final_data['gate_ways_cnname']);
                }
                unset($final_data['gate_ways_cnname'], $final_data['gate_ways_image']);
                $this->CI->utils->debug_log('=====================lgvpay final_data', $final_data);

                return $final_data;
            } else {
				return array(
					'success' => false,
					'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
					'message' => lang('Invalidate API response')
				);
            }
            //##############################################
        }
        else {
			return array(
				'success' => false,
				'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
				'message' => lang('Invalidate API response')
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
		$result = array('success' => false, 'next_url' => null, 'message' => lang('error.payment.failed'));
		$order = $this->CI->sale_order->getSaleOrderById($orderId);
		$processed = false;

		$raw_post_data = file_get_contents('php://input', 'r');
		$flds = json_decode($raw_post_data, true);
		$params = array_merge( $params, $flds );

		if (!$order || !$this->checkCallbackOrder($order, $params, $processed)) {
			return $result;
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
			$this->CI->sale_order->updateExternalInfo($order->id, $params['outOid'], null, null, null, $response_result_id);
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
			$result['return_error'] = $processed ? self::RETURN_SUCCESS_CODE : self::RETURN_FAILED_CODE;
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
			'outOid', 'merchantCode', 'mgroupCode', 'payAmount', 'orderStatus', 'platformOid', 'timestamp'
		);

		foreach ($requiredFields as $f) {
			if (!array_key_exists($f, $fields)) {
				$this->writePaymentErrorLog("=====================lgvpay missing parameter: [$f]", $fields);
				return false;
			}
		}

		$callbackSign = $this->sign($fields, false, false);

		# is signature authentic?
		if ($fields['sign'] != $callbackSign) {
			$this->writePaymentErrorLog("=====================lgvpay check callback sign error, signature is [$callbackSign], match? ", $fields['sign']);
			return false;
		}

		if ($fields['orderStatus'] != self::ORDER_STATUS_SUCCESS) {
			$payStatus = $fields['orderStatus'];
			$this->writePaymentErrorLog("=====================lgvpay Payment was not successful, payStatus is [$payStatus]", $fields);
			return false;
		}

		if (
			($this->convertAmountToCurrency($order->amount) != floatval( $fields['tranAmount'] )) &&
			($this->convertAmountToCurrency($order->amount) != floatval( $fields['transAmount'] ))
		) {
			$this->writePaymentErrorLog("=====================lgvpay Payment amounts do not match, expected [$order->amount]", $fields);
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
			array('label' => '中国银行', 'value' => 'BKCH'),
			array('label' => '工商银行', 'value' => 'ICBK'),
			array('label' => '建设银行', 'value' => 'PCBC'),
			array('label' => '中信银行', 'value' => 'CIBK'),
			array('label' => '民生银行', 'value' => 'MSBC'),
			array('label' => '兴业银行', 'value' => 'FJIB'),
			array('label' => '广发银行', 'value' => 'GDBK'),
			array('label' => '农业银行', 'value' => 'ABOC'),
			array('label' => '交通银行', 'value' => 'COMM'),
			array('label' => '北京银行', 'value' => 'BJCN'),
			array('label' => '浦发银行', 'value' => 'SPDB'),
			array('label' => '平安银行', 'value' => 'SZDB'),
			array('label' => '上海银行', 'value' => 'BOSH'),
			array('label' => '招商银行', 'value' => 'CMBC'),
			array('label' => '光大银行', 'value' => 'EVER'),
			array('label' => '中国邮政储蓄银行', 'value' => 'PSBC'),
			array('label' => '华夏银行', 'value' => 'HXBK'),
			array('label' => '北京农村商业银行', 'value' => 'BRCB'),
			array('label' => '广州银行', 'value' => 'GZCB')
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
		return number_format($amount, 2, '.', '');
	}

	# -- private helper functions --

	/**
	 * detail: getting the signature
	 *
	 * @param array $data
	 * @return	string
	 */
	public function sign($params) {
		$md5key = "key=".$this->getSystemInfo('key');

		if($params['bankCode'] || $params['payCardType'] ) {
			$data = array(
				"outOid", "merchantCode", "mgroupCode", "transAmount", "goodsName", "goodsDesc", "terminalType", "bankCode", "userType", "cardType",
				"payCardType", "payAmount", "curType", "tranAmount", "orderStatus", "platformOid", "timestamp"	//callback params
			);
		}
		else {
			$data = array(
				"busType", "goodDesc", "goodName", "goodNum", "merchantCode", "mgroupCode", "outOid", "payAmount", "payType",
				"tranAmount", "orderStatus", "platformOid", "timestamp", "extend1", "extend2", "extend3"	//callback params
			);
		}

	    sort($data);

	    $arr = array();
	    for($i = 0; $i< count($data); $i++){
			if (array_key_exists($data[$i], $params)) {
				$arr[$i] = $data[$i].'='.$params[$data[$i]];
			}
	    }
	    $signStr = implode('&', $arr);
	    $signStr .= '&'.$md5key;

	    $sign = strtoupper(md5($signStr));
		return $sign;
	}
}
