<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * DINPAY 智付
 * http://www.dinpay.com
 *
 * DINPAY_PAYMENT_API, ID: 27
 * dinpay_weixin_PAYMENT_API, ID: 243
 *
 * Required Fields:
 *
 * * URL
 * * Secret - secret key
 * * Extra Info
 *
 * Field Values:
 *
 * * URL: https://pay.dinpay.com/gateway?input_charset=UTF-8
 * * Extra Info:
 * > {
 * >     "dinpay_merchant_code": "##merchant code##",
 * >     "dinpay_service_type": "direct_pay",
 * >     "dinpay_sign_type": "RSA-S",
 * >     "dinpay_interface_version": "V3.0",
 * >     "dinpay_input_charset": "UTF-8",
 * >     "dinpay_merchant_private_key_path": "/path_to/private_key.pem",
 * >     "dinpay_api_public_key_path": "/path_to/public_key.pem",
 * >     "dinpay_weixin_url": "https://api.dinpay.com/gateway/api/weixin"
 * > }
 * >
 * > for MD5
 * > use secret field
 * >
 * > {
 * >     "dinpay_merchant_code": "##merchant code##",
 * >     "dinpay_service_type": "direct_pay",
 * >     "dinpay_sign_type": "MD5",
 * >     "dinpay_interface_version": "V3.0",
 * >     "dinpay_input_charset": "UTF-8",
 * >     "dinpay_merchant_private_key_path": "/path_to/private_key.pem",
 * >     "dinpay_api_public_key_path": "/path_to/public_key.pem",
 * >     "dinpay_weixin_url": "https://api.dinpay.com/gateway/api/weixin"
 * > }
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
abstract class Abstract_payment_api_dinpay extends Abstract_payment_api {
	const RESP_CODE_SUCCESS = "SUCCESS";

	const CALLBACK_FIELD_ORDER_ID = 'order_no';
	const CALLBACK_FIELD_RESULT_CODE = 'trade_status';
	const CALLBACK_FIELD_AMOUNT = 'order_amount';
	const CALLBACK_FIELD_MERCHANT_CODE = 'merchant_code';
	const CALLBACK_FIELD_SIGNAURE = 'sign';
	const CALLBACK_FIELD_EXTERNAL_ORDER_ID = 'trade_no';
	const CALLBACK_FIELD_BANK_ORDER_ID = 'bank_seq_no';

	const CALLBACK_INFO_FIELD_MERCHANT = 'secret';

	const SUCCESS_CODE_LIST = array('SUCCESS');

	const RETURN_SUCCESS_CODE = 'SUCCESS';
	const RETURN_FAILED_CODE = 'FAILED';

	const REQUEST_DEFAULT_PRODUCT_NAME = '商品';

	const REDO_FLAG = 1;

	public function __construct($params = null) {
		parent::__construct($params);
	}

	# Implement these for specific pay type
	protected abstract function configParams(&$params, $direct_pay_extra_info);
	protected abstract function processPaymentUrlForm($params);

	public function generatePaymentUrlForm($orderId, $playerId, $amount, $orderDateTime, $playerPromoId = null, $enabledSecondUrl = true, $bankId = null) {
		# DINPAY checks the request domain, so we have to use second url to submit the request
		if ($this->shouldRedirect($enabledSecondUrl)) {
			$url = $this->CI->utils->getPaymentUrl($this->getSystemInfo('second_url'), $this->getPlatformCode(), $amount, $playerId, $playerPromoId, false, $bankId, $orderId);
			$result = array('success' => true, 'type' => self::REDIRECT_TYPE_URL, 'url' => $url);
			return $result;
		}

		$info = $this->getInfoByEnv();

		# params taken from config in external_system
		$paramNames = array('merchant_code', 'service_type', 'interface_version', 'sign_type', 'input_charset');
		$params = array();
		foreach ($paramNames as $p) {
			$params[$p] = $this->getSystemInfo("dinpay_$p");
		}

		# other params
		## Note: If the notify/return urls are not called, check the DINPAY
		## control panel to see whether there's setting that overwrites these
		$params['notify_url'] = $this->getNotifyUrl($orderId);
		$params['return_url'] = $this->getReturnUrl($orderId);

		# order-related params
		$order = $this->CI->sale_order->getSaleOrderById($orderId);
		$params['order_no'] = $order->secure_id;
		$params['order_time'] = $orderDateTime->format('Y-m-d H:i:s');
		$params['order_amount'] = $this->convertAmountToCurrency($amount);
		$params['product_name'] = self::REQUEST_DEFAULT_PRODUCT_NAME;
		$params['redo_flag'] = self::REDO_FLAG;

		$this->configParams($params, $order->direct_pay_extra_info);

		$params['sign'] = $this->createRequestSign($params);

		$this->CI->utils->debug_log("============================dinpay_generatePaymentUrlForm", $params);

		return $this->processPaymentUrlForm($params);
	}

	protected function processPaymentUrlFormPost($params) {
		return array(
			'success' => true,
			'type' => self::REDIRECT_TYPE_FORM,
			'url' => $this->getSystemInfo("url"),
			'params' => $params,
			'post' => true,
		);
	}

	protected function processPaymentUrlFormQRCode($params) {
		$weixinUrl = $this->getSystemInfo("url");

		# CURL post the data to Dinpay
		$postString = http_build_query($params);
		$curlConn = curl_init($weixinUrl);
		curl_setopt($curlConn, CURLOPT_CONNECTTIMEOUT, 30);
		curl_setopt($curlConn, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)");
		curl_setopt($curlConn, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curlConn, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($curlConn, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($curlConn, CURLOPT_POSTFIELDS, $postString);

		# Need to specify the referer when doing CURL submit. since we use redirect 2nd url, we can take the HTTP_HOST
		curl_setopt($curlConn, CURLOPT_REFERER, "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]");

		$curlResult = curl_exec($curlConn);
		$curlSuccess = (curl_errno($curlConn) == 0);

		$this->CI->utils->debug_log('===============================dinpay_weixin curlSuccess', $curlSuccess, $curlResult);

		$errorMsg=null;
		if($curlSuccess) {
			# parses return XML result into array, validate it, and get QRCode URL
			## Parse xml array
			$xmlResult = $this->parseResultXML($curlResult);

			## Flatten the parsed xml array
			$result = $this->flattenResult($xmlResult);

			## Validate result data
			$curlSuccess = $this->validateResult($result);

			if ($curlSuccess) {
				## All good, return with qrcode link
				$qrCodeUrl = $result['qrcode'];

				if(!$qrCodeUrl) {
					$curlSuccess = false;
				}
			}

			if(array_key_exists('resp_desc', $result)) {
				$errorMsg = $result['resp_desc'];
			}
		} else {
			# curl error
			$errorMsg = curl_error($curlConn);
		}

		curl_close($curlConn);

		$this->CI->utils->debug_log('=================================dinpay_weixin errorMsg', $errorMsg);

		if($curlSuccess) {
			return array(
				'success' => true,
				'type' => self::REDIRECT_TYPE_QRCODE,
				'url' => $qrCodeUrl,
			);
		} else {
			$this->utils->error_log("==================================dinpay_weixin payment failed ".$errorMsg, "Post String", $postString, "Result", @$curlResult, "Msg", @$errorMsg);

			return array(
				'success' => false,
				'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
				'message' => $errorMsg
			);
		}
	}

	public function parseResultXML($resultXml) {
		$obj=simplexml_load_string($resultXml);
		$arr=$this->CI->utils->xmlToArray($obj);

		$this->CI->utils->debug_log('arr', $arr);

		if(isset($arr['dinpay'])){
			return $arr;
		}else{
			return ['dinpay'=>$arr];
		}
	}

	public function flattenResult($xmlResult) {
		$this->CI->utils->debug_log('=========================dinpay_weixin xmlResult', $xmlResult);

		$response = $xmlResult["dinpay"]["response"];
		$param = array();

		$param['resp_code'] = $response['resp_code'];
		$param['resp_desc'] = $response['resp_desc'];

		# Only when the call success does the response contain qrcode
		if (strcmp($param['resp_code'], self::RESP_CODE_SUCCESS) === 0) {
			$param['interface_version'] = $response['interface_version'];
			$param['merchant_code'] = $response['merchant_code'];
			$param['order_amount'] = $response['order_amount'];
			$param['order_no'] = $response['order_no'];
			$param['order_time'] = $response['order_time'];
			$param['trade_no'] = $response['trade_no'];
			$param['trade_time'] = $response['trade_time'];

			$param['qrcode'] = $response['qrcode'];
			$param['resp_code'] = $response['resp_code'];
			$param['result_code'] = $response['result_code'];
			$param['sign'] = $response['sign'];
			$param['sign_type'] = $response['sign_type'];
		}

		return $param;
	}

	private function validateResult($param) {
		# validate success code
		if (strcmp($param['resp_code'], self::RESP_CODE_SUCCESS) !== 0) {
			$this->utils->error_log("===================dinpay_weixin payment failed, resp_code = [".$param['resp_code']."], resp_msg = [".$param['resp_desc']."], Params: ", $param);
			return false;
		}

		return true;
	}

	## Override the implementation of callbackFromServer to add in signature verification
	## and perform necessary database updates to mark the payment as successful
	public function callbackFromServer($orderId, $callbackExtraInfo) {
		$response_result_id = parent::callbackFromServer($orderId, $callbackExtraInfo);

		$rlt = array('success' => false, 'next_url' => null, 'message' => 'failed');
		//query order
		$ord = $this->CI->sale_order->getSaleOrderById($orderId);
		if ($ord) {
			$processed = false;
			if ($this->checkCallbackOrder($ord, $callbackExtraInfo, $processed, self::FROM_SERVER)) {
				$success = true;
				//save to player balance
				//check order status, if it's STATUS_SETTLED or STATUS_BROWSER_CALLBACK, put log, and ignore
				$orderStatus = $this->CI->sale_order->getSaleOrderStatusById($orderId);
				if ($orderStatus == Sale_order::STATUS_BROWSER_CALLBACK || $orderStatus == Sale_order::STATUS_SETTLED) {
					$this->CI->utils->debug_log('=================callbackFromServer, already get callback for order:' . $ord->id, $callbackExtraInfo);
					if ($ord->status == Sale_order::STATUS_BROWSER_CALLBACK) {
						$this->CI->sale_order->setStatusToSettled($orderId);
					}
				} else {
					$this->CI->sale_order->updateExternalInfo($ord->id, @$callbackExtraInfo[self::CALLBACK_FIELD_EXTERNAL_ORDER_ID],
						@$callbackExtraInfo[self::CALLBACK_FIELD_BANK_ORDER_ID], null, null, $response_result_id);
					$this->approveSaleOrder($ord->id, 'auto server callback ' . $this->getPlatformCode(), false);
				}

				$rlt['success'] = $success;
				if ($success) {
					$rlt['message'] = self::RETURN_SUCCESS_CODE;

				} else {
					if ($processed) {
						$rlt['return_error'] = self::RETURN_SUCCESS_CODE;
					} else {
						$rlt['return_error'] = self::RETURN_FAILED_CODE;
					}
				}
			}
		}
		return $rlt;
	}

	public function callbackFromBrowser($orderId, $callbackExtraInfo) {
		//must call
		$response_result_id = parent::callbackFromBrowser($orderId, $callbackExtraInfo);

		$rlt = array('success' => false, 'next_url' => null, 'message' => lang('error.payment.failed'));
		//query order
		$ord = $this->CI->sale_order->getSaleOrderById($orderId);
		if ($ord) {
			$processed = false;
			if ($this->checkCallbackOrder($ord, $callbackExtraInfo, $processed, self::FROM_BROWSER)) {

				$success = true;

				$orderStatus = $this->CI->sale_order->getSaleOrderStatusById($orderId);
				//save to player balance
				//check order status, if it's STATUS_SETTLED or STATUS_BROWSER_CALLBACK, put log, and ignore
				if ($orderStatus == Sale_order::STATUS_BROWSER_CALLBACK || $orderStatus == Sale_order::STATUS_SETTLED) {
					$this->CI->utils->debug_log('callbackFromBrowser, already get callback for order:' . $ord->id, $callbackExtraInfo);
				} else {
					//update sale order
					$this->CI->sale_order->updateExternalInfo($ord->id, @$callbackExtraInfo[self::CALLBACK_FIELD_EXTERNAL_ORDER_ID],
						@$callbackExtraInfo[self::CALLBACK_FIELD_BANK_ORDER_ID], null, null, $response_result_id);
					$success = $this->CI->sale_order->browserCallbackSaleOrder($ord->id, 'auto broswer callback ' . $this->getPlatformCode(), false);
				}

				$rlt['success'] = $success;
				$rlt['next_url'] = $this->getPlayerBackUrl();
				$rlt['go_success_page'] = true;
			}
		}
		return $rlt;
	}

	## directPay not supported by this API
	## Example: payment_api_loadcard.php
	public function directPay($order = null) {
		return array('success' => false); # direct pay not supported by this API
	}

	# Private functions
	## After payment is complete, the gateway will invoke this URL asynchronously
	private function getNotifyUrl($orderId) {
		return parent::getCallbackUrl('/callback/process/' . $this->getPlatformCode() . '/' . $orderId);
	}

	## After payment is complete, the gateway will send redirect back to this URL
	private function getReturnUrl($orderId) {
		return parent::getCallbackUrl('/callback/browser/success/' . $this->getPlatformCode() . '/' . $orderId);
	}

	## Returns the bank code of given ID. Optional.
	private function getBankCode($bankId) {
		if (empty($bankId)) {
			return '';
		}
		$this->CI->load->model(array('bank_list'));
		return $this->CI->bank_list->getBankShortCodeById($bankId);
	}

	private function checkCallbackOrder($ord, $flds, &$processed = false, $from = self::FROM_SERVER) {
		$info = $this->getInfoByEnv();

		$success = false;
		if (isset($flds[self::CALLBACK_FIELD_SIGNAURE])) {
			$signature = @$flds[self::CALLBACK_FIELD_SIGNAURE];
			$success = ($from == self::FROM_SERVER) ? $this->isValidatedServerCallbackSign($flds) : $this->isValidatedBrowserCallbackSign($flds);
			if (!$success) {
				$this->writePaymentErrorLog('================signaure is wrong', $flds);
			}
			$processed = $success;
		}

		//check respCode
		if ($success) {
			$success = in_array(@$flds[self::CALLBACK_FIELD_RESULT_CODE], self::SUCCESS_CODE_LIST);
			if (!$success) {
				$this->writePaymentErrorLog(self::CALLBACK_FIELD_RESULT_CODE . ' is not ', self::SUCCESS_CODE_LIST, $flds);
			}
		}

		if ($success) {
			//check amount, order id, mercode
			if (isset($flds[self::CALLBACK_FIELD_AMOUNT])) {
				$success = $this->convertAmountToCurrency($ord->amount) ==
				$this->convertAmountToCurrency(floatval($flds[self::CALLBACK_FIELD_AMOUNT]));
			}
			if ($success) {
				$success = $ord->secure_id == $flds[self::CALLBACK_FIELD_ORDER_ID];
				if ($success) {
					$success = $info['system_info']['dinpay_merchant_code'] == $flds[self::CALLBACK_FIELD_MERCHANT_CODE];
					if ($success) {
					} else {
						$this->writePaymentErrorLog('================merchant code is wrong', $flds);
					}
				} else {
					$this->writePaymentErrorLog('===================order id is wrong', $flds);
				}

			} else {
				$this->writePaymentErrorLog('======================amount is wrong', $flds);
			}
		}
		return $success;
	}

	protected function convertAmountToCurrency($amount) {
		return number_format($amount, 2, '.', '');
	}

	public function createRequestSign($params) {
		$info = $this->getInfoByEnv();

		if (@$info['system_info']['dinpay_sign_type'] == 'MD5') {
			$original = $this->getParamStringForSigning($params) . '&key=' . $info['secret'];
			//should be upper
			return md5($original);
		} else {
			//RSA-S
			$original = $this->getParamStringForSigning($params);
			$privateKey = openssl_pkey_get_private($info['system_info']['dinpay_merchant_private_key_path']);
			openssl_sign($original, $sign_info, $privateKey, OPENSSL_ALGO_MD5);
			return base64_encode($sign_info);
		}

	}

	public function isValidatedBrowserCallbackSign($params) {
		return $this->isValidatedServerCallbackSign($params);
	}

	public function isValidatedServerCallbackSign($params) {
		$info = $this->getInfoByEnv();

		if (@$info['system_info']['dinpay_sign_type'] == 'MD5') {
			$original = $this->getParamStringForSigning($params) . '&key=' . $info['secret'];

			//should be upper
			return strtolower(md5($original)) == strtolower($params['sign']);
		} else {
			//RSA-S
			$original = $this->getParamStringForSigning($params);
			$publicKey = openssl_get_publickey($info['system_info']['dinpay_api_public_key_path']);

			if (openssl_verify($original, base64_decode($params['sign']), $publicKey, OPENSSL_ALGO_MD5)) {
				return true;
			} else {
				return false;
			}
		}
	}

	## Given an array of parameters, returns a string that is used for the signing function
	private function getParamStringForSigning($params) {
		# as defined in document, some parameters not involved in signing
		$skipKeys = array('sign', 'sign_type', 'qrcode', 'resp_code', 'result_code');
		$paramKeys = array_keys($params);
		# parameters need to be sorted alphabetically by their names
		sort($paramKeys, SORT_STRING);
		$paramsForSign = array();

		foreach ($paramKeys as $aParamKey) {
			# Skip parameters not participating in signing
			if (in_array($aParamKey, $skipKeys)) {
				continue;
			}
			# Skip parameters with empty values
			if (empty($params[$aParamKey])) {
				continue;
			}

			$paramsForSign[$aParamKey] = $params[$aParamKey];
		}

		$original = urldecode(http_build_query($paramsForSign));
		return $original;
	}


	public function getBankListInfo() {

		return array(
			array(
				'label' => '微信支付', 'value' => 'WeChatPay',
			),
			array(
				'label' => '农业银行', 'value' => 'ABC',
			),
			array(
				'label' => '工商银行', 'value' => 'ICBC',
			),
			array(
				'label' => '建设银行', 'value' => 'CCB',
			),
			array(
				'label' => '交通银行', 'value' => 'BCOM',
			),
			array(
				'label' => '中国银行', 'value' => 'BOC',
			),
			array(
				'label' => '招商银行', 'value' => 'CMB',
			),
			array(
				'label' => '民生银行', 'value' => 'CMBC',
			),
			array(
				'label' => '光大银行', 'value' => 'CEBB',
			),
			array(
				'label' => '北京银行', 'value' => 'BOB',
			),
			array(
				'label' => '上海银行', 'value' => 'SHB',
			),
			array(
				'label' => '宁波银行', 'value' => 'NBB',
			),
			array(
				'label' => '华夏银行', 'value' => 'HXB',
			),
			array(
				'label' => '兴业银行', 'value' => 'CIB',
			),
			array(
				'label' => '中国邮政', 'value' => 'PSBC',
			),
			array(
				'label' => '平安银行', 'value' => 'SPABANK',
			),
			array(
				'label' => '浦发银行', 'value' => 'SPDB',
			),
			array(
				'label' => '中信银行', 'value' => 'ECITIC',
			),
		);
	}
}

////END OF FILE//////////////////
