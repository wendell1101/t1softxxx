<?php

require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * PaySec
 * http://www.paysec.com/
 *
 * PAYSEC_PAYMENT_API, ID: 47
 *
 * Prerequisites :
 * * CompanyID
 * * Merchant Key
 * * Return Url
 * * PaySec Dashboard System Credentials
 *
 * General behaviors include :
 *
 * * Generate payment form
 * * Creating sign posting
 * * Getting sales order
 * * Verification of signature
 *
 * Required Fields:
 *
 * * URL
 * * Account – company ID
 * * key – merchant key
 * * secret – signature key
 * * Extra Info
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot-Bryson
 */

abstract Class Abstract_payment_api_paysec extends Abstract_payment_api
{
	const QRCODE_REPONSE_CODE_SUCCESS = 'SUCCESS';
	const PAYMENT_TOKEN_RESPONSE_FAILED = 'FAILURE';
	const RETURN_SUCCESS_CODE = 'OK';
	const RETURN_FAILED_CODE  = 'FAILED';
	const CALLBACK_STATUS_FAILED = 'FAILED';

    const FIELD_VERSION  = "1.0";
    const FIELD_COUNTRY  = 'CN';
    const FIELD_CURRENCY = 'CNY';
    const FIELD_CHANNEL_CODE_QQ = 'QQP';
    const FIELD_CHANNEL_CODE_BANKTRANSFER = 'BANK_TRANSFER';
    const FIELD_CHANNEL_CODE_QUICK = 'QUICKPAY';
    const FIELD_CHANNEL_CODE_WECHAT_V2 = 'WECHAT';
    const FIELD_CHANNEL_CODE_QQPAY_V2 = 'QQPAY';

    const API_REQUEST = 'rq';
    const API_RESPONSE = 'rs';

    public $payType;

    private $info;
    public $standardRequireParam = [
        'WEBBANK'   => ['version', 'v_currency', 'v_amount', 'CID', 'signature', 'v_CartID', 'v_bank_code','v_callbackurl'],
        'BANKTRANS' => ['version', 'merchantCode', 'signature', 'channelCode', 'bankCode', 'notifyURL', 'returnURL', 'orderAmount','orderTime', 'cartId', 'currency'],
        'QUICKPAY' 	=> ['version', 'merchantCode', 'signature', 'channelCode', 'bankCode', 'notifyURL', 'returnURL', 'orderAmount','orderTime', 'cartId', 'currency'],
        'WECHAT_V2' => ['version', 'merchantCode', 'signature', 'channelCode', 'notifyURL', 'returnURL', 'orderAmount','orderTime', 'cartId', 'currency'],
        'QQPAY_V2'  => ['version', 'merchantCode', 'signature', 'channelCode', 'notifyURL', 'returnURL', 'orderAmount','orderTime', 'cartId', 'currency'],
        'IDR'   	=> ['version', 'v_currency', 'v_amount', 'CID', 'signature', 'v_CartID','v_callbackurl', 'v_temp_return_url'],
        'QQPAY'     => ['version', 'merchantCode', 'signature', 'notifyURL', 'orderAmount', 'orderTime', 'productName', 'cartId', 'currency', 'channelCode'],
        'ALIPAY'    => ['version', 'merchantCode', 'signature', 'notifyURL', 'orderAmount', 'orderTime', 'productName', 'cartId', 'currency'],
        'WECHATPAY' => ['version', 'merchantCode', 'signature', 'notifyURL', 'orderAmount', 'orderTime', 'productName', 'cartId', 'currency']
    ];

    public $standardSignatureParamsSequence = [
        'WEBBANK|IDR' => ['CID', 'v_CartID', 'v_amount', 'v_currency'],
        'BANKTRANS|WECHAT_V2|QQPAY_V2|QUICKPAY' => ['cartId', 'orderAmount', 'currency', 'merchantCode', 'version'],
        'QQPAY|ALIPAY|WECHATPAY' => ['merchantCode', 'cartId', 'orderAmount', 'currency']
    ];

    public $standerResponseVerifyParams = [
        'WEBBANK|IDR' => ['mid', 'oid', 'cartid', 'amt', 'cur', 'status', 'signature'],
        'BANKTRANS|WECHAT_V2|QQPAY_V2'   => ['status', 'statusMessage', 'transactionReference', 'currency', 'orderAmount', 'orderTime', 'completedTime', 'version', 'signature'],
        'ALIPAY|WECHATPAY|QQPAY'   => ['merchantCode', 'reference', 'cartId', 'amount', 'currency', 'status', 'signature']
    ];

    public $standardResponseSignatureParamsSequence = [
        'WEBBANK|IDR' => ['mid', 'oid', 'cartid', 'amt', 'cur', 'status'],
        'BANKTRANS|WECHAT_V2|QQPAY_V2|QUICKPAY' => ['cartId', 'orderAmount', 'currency', 'merchantCode', 'version', 'status'],
        'ALIPAY|WECHATPAY|QQPAY' => ['merchantCode', 'reference', 'cartId', 'amount', 'currency', 'status']
    ];

	public function __construct($params = null)
    {
		parent::__construct($params);
		# Populate $info with the following keys
		# url, key, account, secret, system_info
		$this->info = $this->getInfoByEnv();
	}

    # Implement these to specify pay type
	protected abstract function configParams(&$params, $direct_pay_extra_info);
    protected abstract function processPaymentUrlForm($params);

	public function generatePaymentUrlForm($orderId, $playerId, $amount, $orderDateTime, $playerPromoId = null, $enabledSecondUrl = true, $bankId = null) {
        # For second url redirection
		if ($this->shouldRedirect($enabledSecondUrl)) {
			$url = $this->CI->utils->getPaymentUrl($this->getSystemInfo('second_url'), $this->getPlatformCode(), $amount, $playerId, $playerPromoId, false, $bankId, $orderId);
			$result = array('success' => true, 'type' => self::REDIRECT_TYPE_URL, 'url' => $url);
			return $result;
		}

		$order   = $this->CI->sale_order->getSaleOrderById($orderId);
        $direct_pay_extra_info = $order->direct_pay_extra_info;
        $playerDetails = $this->getPlayerDetails($playerId);

        $firstname = (isset($playerDetails[0]) && !empty($playerDetails[0]['firstName'])) ? $playerDetails[0]['firstName'] : 'no firstName';
        $lastname =  (isset($playerDetails[0]) && !empty($playerDetails[0]['lastName']))  ? $playerDetails[0]['lastName']  : 'no lastName';
        $emailAddr = (isset($playerDetails[0]) && !empty($playerDetails[0]['email']))     ? $playerDetails[0]['email']     : 'no email';
        $contactNumber = (isset($playerDetails[0]) && !empty($playerDetails[0]['contactNumber']))     ? $playerDetails[0]['contactNumber']     : 'no contactNumber';

        # read some parameters from config
        $params['cartId'] = $order->secure_id;
        $params['version']  = self::FIELD_VERSION;
        $params['currency'] = $this->getSystemInfo('currency') ? $this->getSystemInfo('currency') : self::FIELD_CURRENCY;
        $params['channelCode'] = self::FIELD_CHANNEL_CODE_QQ;
        $params['notifyURL']   = $this->getNotifyUrl($order->id);
        $params['returnURL']   = $this->getReturnUrl($order->id);
        $params['productName'] = 'pro_' . $order->id;
        $params['orderTime']   = time() * 1000;
        $params['merchantCode'] = $this->getSystemInfo('account');
        $params['orderAmount']  = $this->convertAmountToCurrency($order->amount);

        $params['CID']        = $params['merchantCode'];
        $params['v_amount']   = $params['orderAmount'];
        $params['v_currency'] = $this->getSystemInfo('currency') ? $this->getSystemInfo('currency') : self::FIELD_CURRENCY;
        $params['v_CartID']   = $params['cartId'];
        $params['v_callbackurl'] = $params['notifyURL'];
        $params['v_temp_return_url'] = $params['returnURL'];

        if (!empty($direct_pay_extra_info)) {
			$extraInfo = json_decode($direct_pay_extra_info, true);
			if (!empty($extraInfo) && isset($extraInfo['bank'])) {
				$params['v_bank_code'] = $extraInfo['bank'];
				$params['bankCode'] = $extraInfo['bank'];
			}
		}

        $this->configParams($params, $order->direct_pay_extra_info);

        # sign param
		$params['signature'] = $this->sign($params);

        # choose Correct params
        $params = $this->chooseCorrectParams($params);

        if($this->payType == 'WEBBANK' || $this->payType == 'IDR' ) {
			# get payment token
			$token = $this->getPaymentToken($params);

			$this->utils->debug_log('template notifyUrl', $params['notifyURL'], 'orderId', $orderId, 'secure_id', $params['cartId']);
			$this->utils->debug_log('=========================paysec request params', $params);

			$tokenArr = ($this->getSystemInfo('currency') == 'IDR') ? ['token' => $token, 'mode' => 'HTML'] : ['token' => $token];
			return $this->processPaymentUrlForm($tokenArr);
		}
		else {
			$header = array(
				"version" => $params['version'],
				"merchantCode" => $params['merchantCode'],
				"signature" => $params['signature']
			);
			if( (isset($params['channelCode'])) && (($this->payType != 'BANKTRANS') && ($this->payType != 'WECHAT_V2') && ($this->payType != 'QQPAY_V2') && ($this->payType != 'QUICKPAY')) ) {
				$header['channelCode'] = $params['channelCode'];
			}

			unset($params['version']);
			unset($params['merchantCode']);
			unset($params['signature']);
			if( (isset($params['channelCode'])) && (($this->payType != 'BANKTRANS') && ($this->payType != 'WECHAT_V2') && ($this->payType != 'QQPAY_V2') && ($this->payType != 'QUICKPAY')) ) {
				unset($params['channelCode']);
			}

			$body = array();

			foreach ($params as $key => $value) {
				$body[$key] = $value;
			}

			$pass_player_info = ($this->getSystemInfo('pass_player_info'))?true:false;
			if($pass_player_info){
				$cardNumber = '';
		        if(!empty($direct_pay_extra_info)) {
		            $extraInfo = json_decode($direct_pay_extra_info, true);
		            if(!empty($extraInfo['player_bank_num'])){  //using alipay(bank_id=30) transfer to online_bank
		                $playerBankNum = $extraInfo['player_bank_num'];
		            }
		        }

                $body['customerInfo'] = array(
                    "address" => array(
                                    "email" => $emailAddr,
                                    "phone" => $contactNumber,
                                ),
                    "cardHolderFirstName" => $firstname,
                    "cardHolderLastName" => $lastname
                );
            }

			$finalParams = array(
				"header" => $header,
				"body" => $body
			);

			if( ($this->payType == 'BANKTRANS') || ($this->payType != 'WECHAT_V2') || ($this->payType != 'QQPAY_V2') ) {
				$token = $this->getPaymentToken($finalParams);

				if(is_array($token)) {
					return $token;
				}

				$this->utils->debug_log('template notifyUrl', $params['notifyURL'], 'orderId', $orderId, 'secure_id', $params['cartId']);
				$this->utils->debug_log('=========================paysec request params', $finalParams);

				$tokenArr = ['token' => $token];
				return $this->processPaymentUrlForm($tokenArr);
			}

			$this->utils->debug_log('=========================paysec non-token request params', $finalParams);

			return $this->processPaymentUrlForm($finalParams);
		}
	}

    # Submit POST form
	protected function processPaymentUrlFormPost($params) {
		return array(
			'success' => true,
			'type'    => self::REDIRECT_TYPE_FORM,
			'url'     => $this->getSystemInfo('url'),
			'params'  => $params,
			'post'    => true,
		);
	}

	# Display QRCode get from curl
	protected function processPaymentUrlFormQRCode($params) {
		$url = $this->getSystemInfo('url');

        $ch = curl_init();
        $curlData = array();
		$curlData[CURLOPT_POST] = true;
		$curlData[CURLOPT_URL]  = $url;
		$curlData[CURLOPT_RETURNTRANSFER] = true;
		$curlData[CURLOPT_TIMEOUT] = 20;
		$curlData[CURLOPT_POSTFIELDS] = json_encode($params);
        $curlData[CURLOPT_HTTPHEADER] = 'Content-Type: application/json; charset=utf-8';
		curl_setopt_array($ch, $curlData);

		$response = curl_exec($ch);
		$errCode = curl_errno($ch);
		$error = curl_error($ch);
		$header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
		$header = substr($response, 0, $header_size);
		$content = substr($response, $header_size);
		$statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);

		$response_result_id = $this->submitPreprocess($params, $content, $url, $response, array('errCode' => $errCode, 'error' => $error, 'statusCode' => $statusCode));
		$response = json_decode($response, true);
		$this->CI->utils->debug_log('=====================paysec decoded response', $response);

		$msg = lang('Invalidate API response');

		if($response['status'] == self::QRCODE_REPONSE_CODE_SUCCESS && isset($response['qrCode'])) {
			return array(
				'success' => true,
				'type' => self::REDIRECT_TYPE_QRCODE_MODAL,
				'url' => $response['qrCode']
			);
		}
		else {
			if(isset($response['statusMessage'])) {
				$msg = $response['statusMessage'];
			}

			return array(
				'success' => false,
				'type' => self::REDIRECT_TYPE_ERROR_MODAL, # will be redirected to a view for error display
				'message' => $msg
			);
		}
	}

    private function getPaymentToken($params) {
        $tokenUrl = $this->getSystemInfo('token_url');

        if($this->getSystemInfo('returnQrcodeOnly') || $this->getSystemInfo('isBanktransfer')) {
			$ch = curl_init();

			curl_setopt($ch, CURLOPT_URL, $tokenUrl);
			curl_setopt($ch, CURLOPT_HEADER, true);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
			curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json') );
			curl_setopt($ch, CURLOPT_POSTFIELDS, $this->CI->utils->encodeJson($params) );
			curl_setopt($ch, CURLOPT_TIMEOUT, $this->getTimeoutSecond());
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->getConnectTimeout());

			$response = curl_exec($ch);
			$errCode = curl_errno($ch);
			$error = curl_error($ch);
			$header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
			$header = substr($response, 0, $header_size);
			$content = substr($response, $header_size);
			$statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
			curl_close($ch);

			$response_result_id = $this->submitPreprocess($params, $content, $tokenUrl, $response, array('errCode' => $errCode, 'error' => $error, 'statusCode' => $statusCode), $params['body']['cartId']);
			$response = json_decode($content, true);

			$this->CI->utils->debug_log('=====================paysec getToken response', $response);
			$this->CI->utils->debug_log('url', $tokenUrl, 'params', $params , 'response', $response, 'errCode', $errCode, 'error', $error, 'statusCode', $statusCode);


			$msg = lang('Invalidte API response');

			if($response['header']['status'] == self::PAYMENT_TOKEN_RESPONSE_FAILED) {
				if(isset($response['header']['statusMessage']['statusMessage'])) {
					$msg = $response['header']['statusMessage']['statusMessage'];
				}

				return array(
					'success' => false,
					'type' => self::REDIRECT_TYPE_ERROR,
					'message' => $msg
				);
			}

	 		return $response['body']['token'];
        }
		else {
			try {
				$response = \Httpful\Request::post($tokenUrl)
					->method(\Httpful\Http::POST)
					->expectsJson()
					->body($params)
					->sendsType(\Httpful\Mime::FORM)
					->send();

				if (!$response->hasErrors()) {
					$this->utils->debug_log('====================paysec getToken ' . $this->payType . ' Response', $response->body);
					if ($this->payType == 'WEBBANK' || $this->payType == 'IDR') {
						$token = $response->body->token;
					} else {
						$token = $response->body->body->token;
					}
					return $token;
				} else {
					$this->utils->error_log('getToken ' . $this->payType . ' error code', $response->code, $response->body);
					$this->_errorMsg = $response->body->message;
				}

			} catch (Exception $e) {
				$this->utils->error_log('getToken ' . $this->payType . ' get token failed', $e);
			}
		}
    }

	## This will be called when the payment is async, API server calls our callback page
	## When that happens, we perform verifications and necessary database updates to mark the payment as successful
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
		$this->utils->debug_log('=================paysec callbackFrom' . ucfirst($source) . ': [' . $orderId .'], params:', $params);

		$result = array('success' => false, 'next_url' => null, 'message' => lang('error.payment.failed'));
		$order = $this->CI->sale_order->getSaleOrderById($orderId);

		if ( (!is_array($params) || empty($params)) && $source == 'server') {
			$flds = json_decode(file_get_contents('php://input'), true);
			$params = array_merge($params, $flds);
			$this->utils->debug_log('=================paysec json callback decode', $params);
		}

		$processed = false;

		if($source == 'browser') {
			$processed = true;
		}
		else {
			if(isset($params['status'])) {
				if($params['status'] == self::RETURN_FAILED_CODE){
					$result['return_error_msg'] = self::RETURN_SUCCESS_CODE;
					$this->utils->debug_log('=================paysec response to the callback with failed status', $result, $params);

					return $result;
				}
			}

			if (!$order || !$this->checkCallbackOrder($order, $params, $processed) ) {
				return $result;
			}
		}

		# Update order payment status and balance
		$this->CI->sale_order->startTrans();
		# Update player balance based on order status
		# if it's STATUS_SETTLED or STATUS_BROWSER_CALLBACK, put log, and ignore
		$orderStatus = $this->CI->sale_order->getSaleOrderStatusById($orderId);
		if ($orderStatus == Sale_order::STATUS_BROWSER_CALLBACK || $orderStatus == Sale_order::STATUS_SETTLED) {
			$this->CI->utils->debug_log('callbackFrom' . ucfirst($source) . ', already get callback for order:' . $order->id, $params);
			if ($source == 'server' && $order->status == Sale_order::STATUS_BROWSER_CALLBACK) {
				$this->CI->sale_order->setStatusToSettled($orderId);
			}
		} else {
			$external_order_id = isset($params['body']['reference']) 	? $params['body']['reference'] 	  : '';
			$external_order_id = isset($params['oid']) 				 	? $params['oid'] 				  : $external_order_id;
			$external_order_id = isset($params['transactionReference']) ? $params['transactionReference'] : $external_order_id;

			# update player balance
			$this->CI->sale_order->updateExternalInfo($order->id, $external_order_id, null, null, null, $response_result_id);
			if ($source == 'browser') {
				$success = $this->CI->sale_order->browserCallbackSaleOrder($order->id, 'auto broswer callback ' . $this->getPlatformCode(), false);
			} elseif ($source == 'server') {
				$direct_pay_extra_info = $order->direct_pay_extra_info;
				$player_bank_name = '';
				$player_bank_num = '';
				$card_holder_name = '';
				$player_email = '';
				$player_phone = '';

				if (!empty($direct_pay_extra_info)) {
					$extraInfo = json_decode($direct_pay_extra_info, true);
					if (!empty($extraInfo)) {
						$player_bank_name = isset($extraInfo['bank']) ? $extraInfo['bank'] : '';
						$player_bank_num = isset($extraInfo['player_bank_num']) ? $extraInfo['player_bank_num'] : '';
						$player_first_name = isset($extraInfo['player_first_name']) ? $extraInfo['player_first_name'] : '';
						$player_last_name = isset($extraInfo['player_last_name']) ? $extraInfo['player_last_name'] : '';
						$card_holder_name = $player_last_name.' '.$player_first_name;
						$player_email = isset($extraInfo['player_email']) ? $extraInfo['player_email'] : '';
						$player_phone = isset($extraInfo['player_phone']) ? $extraInfo['player_phone'] : '';
					}

					$this->utils->debug_log('=================paysec order extraInfo', $extraInfo);
				}

				$extra_note = 'auto server callback ' . $this->getPlatformCode().
								'| Player Bank Name   : ' . $player_bank_name.
								'| Player Bank Number : ' . $player_bank_num.
								'| Player Email 	  : ' . $player_email.
								'| Player Phone 	  : ' . $player_phone.
								'| Cardholder Name    : ' . $card_holder_name;
				$this->approveSaleOrder($order->id, $extra_note, false);
			}
		}

		$success = $this->CI->sale_order->endTransWithSucc();
		$result['success'] = $success;
		if ($success) {
			$result['message'] = self::RETURN_SUCCESS_CODE;
		} else {
			$result['return_error'] = $processed ? self::RETURN_SUCCESS_CODE : self::RETURN_FAILED_CODE;
		}
		if ($source == 'browser') {
			#redirect to success/fail page according to return params
            if($params['status'] == self::CALLBACK_STATUS_FAILED){
                $this->CI->utils->debug_log("========================paysec callbackFrom browser status return FAILED", $params);
                $result['success'] = false;
                $result['message'] = lang('error.payment.failed');
            }
            $result['next_url'] = $this->getPlayerBackUrl();
		}
		return $result;
	}

	private function checkCallbackOrder($order, $fields, &$processed = false)
    {
		if(isset($fields['body']) && is_array($fields['body'])) {
			$fields = $fields['body'];
		}

		$amount  = (isset($fields['amt'])) ? $fields['amt'] : ((isset($fields['amount'])) ? $fields['amount'] : 0);
		$amount  = (isset($fields['orderAmount'])) ?  $fields['orderAmount'] : $amount;
        $orderId = (isset($fields['cartid'])) ? $fields['cartid'] : ((isset($fields['cartId'])) ? $fields['cartId'] : 0);

		$requiredFields = [];
		foreach ($this->standerResponseVerifyParams as $type => $rule) {
            if (in_array($this->payType, explode('|', $type))) {
                $requiredFields = $rule;
            }
        }

		foreach ($requiredFields as $f) {
			if (!array_key_exists($f, $fields)) {
				$this->writePaymentErrorLog("=================paysec checkCallbackOrder missing parameter: [$f]", $fields);
				return false;
			}
		}
		# is signature authentic?
		if (!$this->validateSign($fields, $fields['signature'])) {
			$this->writePaymentErrorLog('=================paysec checkCallbackOrder signature Error', $fields);
			return false;
		}
		$processed = true; # processed is set to true once the signature verification pass
		# check parameter values: orderStatus, tradeAmt, orderNo, merchNo
		# is payment successful?
		if ($fields['status'] !== 'SUCCESS') {
			$this->writePaymentErrorLog('P=================paysec checkCallbackOrder payment was not successful', $fields);
			return false;
		}
		# does amount match?
		$check_amount = $this->convertAmountToCurrency($order->amount);
        if($this->getSystemInfo('use_usd_currency')) {
            $check_amount = $this->convertAmountToCurrency($this->gameAmountToDBByCurrency($order->amount, $this->utils->getTodayForMysql(),'USD','CNY') );
        }

		if ( abs($check_amount - number_format($amount, 2, '.', '') ) > 1) {
			$compare_amount = number_format($amount, 2, '.', '');
			$this->writePaymentErrorLog("=================paysec checkCallbackOrder payment amounts do not match, expected [$check_amount] but it's [$compare_amount]", $fields);
			return false;
		}

		# does order_no match?
		if ($orderId != $order->secure_id) {
			$this->writePaymentErrorLog("=================paysec checkCallbackOrder order IDs do not match, expected [$order->secure_id]", $fields);
			return false;
		}
		# everything checked ok
		return true;
	}

	public function directPay($order = null)
    {
		return array('success' => false); # direct pay not supported by this API
	}

	public function getNotifyUrl($orderId)
	{
		return parent::getCallbackUrl('/callback/process/' . $this->getPlatformCode() . '/' . $orderId);
	}

	public function getReturnUrl($orderId)
	{
		return $this->getBrowserCallbackUrl('/callback/browser/success/' . $this->getPlatformCode() . '/' . $orderId);
	}

	public function getBrowserCallbackUrl($uri) {
		return $this->CI->utils->site_url_with_http($uri, $this->getSystemInfo('browser_callback_host'));
	}

	## Format the amount value for the API
	protected function convertAmountToCurrency($amount)
    {
    	$convert_multiplier = $this->getSystemInfo('convert_multiplier') ? $this->getSystemInfo('convert_multiplier') : 1;
        return number_format($amount * $convert_multiplier, 2, '.', '');
	}

	# -- private helper functions --
	/**
	 * @name	generate signature
	 * @param	sourceData
	 * @return	signature
	 */
	public function sign($params, $action = self::API_REQUEST)
    {
        $preEncodeStr = $this->buildEncodeStr($params, $action);
		$signature = md5(strtoupper($preEncodeStr));

		return $signature;
	}

	private function validateSign($params, $signature)
    {
		$mySign = $this->sign($params, self::API_RESPONSE);
		if (strcasecmp($mySign, $signature) === 0) {
			return true;
		} else {
			return false;
		}
	}

	public function buildEncodeStr($params, $action)
    {
        $standardSignatureParams = [];
        if ($action == self::API_REQUEST) {
            $standardSignatureParams = $this->standardSignatureParamsSequence;
        } elseif ($action == self::API_RESPONSE) {
            $standardSignatureParams = $this->standardResponseSignatureParamsSequence;
        }

        $_rule = [];
        foreach ($standardSignatureParams as $type => $rule) {
            if (in_array($this->payType, explode('|', $type))) {
                $_rule = $rule;
            }
        }

        $encodeArray = [];
        $encodeArray[] = $this->getSystemInfo('secret');
        foreach ($_rule as $_params) {
            if (isset($params[$_params])) {
                $encodeArray[] = $params[$_params];
            }
        }

        return str_replace(array('.', ','), array('', ''), implode(";", $encodeArray));
    }

    private function chooseCorrectParams($params)
    {
        $standardRequireParam = $this->standardRequireParam[$this->payType];

        $_params = [];
        foreach ($standardRequireParam as $key) {
            if (isset($params[$key])) {
                $_params[$key] = $params[$key];
            }
        }

        return $_params;
    }

    public function getPlayerDetails($playerId) {
        $this->CI->load->model(array('player_model'));
        $player = $this->CI->player_model->getPlayerDetails($playerId);

        return $player;
    }
}