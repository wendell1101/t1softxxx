<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * ALLBANK 
 *
 *
 *
 * @category Payment
 * @copyright 2013-2024 tot
 */
abstract class Abstract_payment_api_allbank_pesonet extends Abstract_payment_api {
	const ALLBANK_PESONET = 'PNETPTR';
	const ALLBANK_SOA = 'ACCOUNT-SOA';
	const REPONSE_CODE_SUCCESS = '0';
	const RESULT_CODE_SUCCESS = 'DS07';
	const RETURN_SUCCESS_CODE = 'SUCCESS';
	const RETURN_FAILED_CODE = 'failed';

	# Implement these for specific pay type
	protected abstract function configParams(&$params, $direct_pay_extra_info);
	protected abstract function processPaymentUrlForm($params);

	public function generatePaymentUrlForm($orderId, $playerId, $amount, $orderDateTime, $playerPromoId = null, $enabledSecondUrl = true, $bankId = null) {
		if ($this->shouldRedirect($enabledSecondUrl)) {
			$url = $this->CI->utils->getPaymentUrl($this->getSystemInfo('second_url'), $this->getPlatformCode(), $amount, $playerId, $playerPromoId, false, $bankId, $orderId);
			$result = array('success' => true, 'type' => self::REDIRECT_TYPE_URL, 'url' => $url);
			return $result;
		}

		$playerDepositBankList = $this->CI->playerbankdetails->getPlayerDepositBankList($playerId);
		$this->utils->debug_log('=====================allbank playerDepositBankList', $playerDepositBankList);
		$bankCode = $this->getSystemInfo("deposit_bank_code", "Allbank");
		$playerBankAccountNumber = '';
		if (is_array($playerDepositBankList)) {
            foreach ($playerDepositBankList as $key => $value) {
                if ($playerDepositBankList[$key]['bank_code'] == $bankCode) {
                    $playerBankAccountNumber = $playerDepositBankList[$key]['bankAccountNumber'];
					if ($playerDepositBankList[$key]['isDefault'] == '1'){
						break;
					}
                }
            }
        }

		if (empty($playerBankAccountNumber)) {
			$msg = lang('please add Allbank deposit account first');
			return array('success' => false, 'type' => self::REDIRECT_TYPE_ERROR, 'message' => $msg);
		}

		$date = new DateTime();
		$order = $this->CI->sale_order->getSaleOrderById($orderId);
		$playerDetails = $this->CI->player_model->getPlayerDetails($playerId);
        $lastname = (isset($playerDetails[0]) && !empty($playerDetails[0]['lastName'])) ? $playerDetails[0]['lastName'] : 'none';

		$params['id'] = $this->getSystemInfo("account");
		$params['tdt'] = date_format($date, 'Y-m-d\TH:i:s.vP');
		$params['amt'] = $this->convertAmountToCurrency($amount);
		$params['acctno'] = $playerBankAccountNumber;
		$params['dbk'] = $order->payment_branch_name;
		$params['acctno2'] = $order->payment_account_number;
		$params['ln'] = $lastname;
		$params['ref_id'] = $order->secure_id;

		$this->configParams($params, $order->direct_pay_extra_info);

		$params['token'] = $this->sign($params);

		$info = json_decode($order->direct_pay_extra_info, true);
        $info['card_number'] = $params['acctno'];
        $info = json_encode($info);
        $this->CI->sale_order->updateSaleOrderDirectPayExtraInfoById($orderId, $info);

		$this->CI->utils->debug_log("=====================allbank generatePaymentUrlForm", $params);
		return $this->processPaymentUrlForm($params);
	}

	# Submit POST form
	protected function processPaymentUrlFormPost($params) {

		$post_xml_data = $this->array2xml($params);
		$this->CI->utils->debug_log('=====================allbank post_xml_data', $post_xml_data);

		$curlConn = curl_init();
		$curlData = array();
		$curlData[CURLOPT_POST] = true;
		$curlData[CURLOPT_URL] = $this->getSystemInfo('url');
		$curlData[CURLOPT_RETURNTRANSFER] = true;
		$curlData[CURLOPT_TIMEOUT] = 120;
		$curlData[CURLOPT_POSTFIELDS] = $post_xml_data;
		$curlData[CURLOPT_HTTPHEADER] = [ "Content-Type: text/xml", "SoapAction:'http://tempuri.org/iWebInterface/wb_Get_Info'"];

		curl_setopt_array($curlConn, $curlData);

		curl_setopt($curlConn, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curlConn, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($curlConn, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($curlConn, CURLOPT_SSL_VERIFYHOST, false);

		$response    = curl_exec($curlConn);
		$errCode     = curl_errno($curlConn);
        $error       = curl_error($curlConn);
        $statusCode  = curl_getinfo($curlConn, CURLINFO_HTTP_CODE);

        $curlSuccess = ($errCode == 0);
        $response_result_id = $this->submitPreprocess($params, $response, $this->getSystemInfo('url'), $response, array('errCode' => $errCode, 'error' => $error, 'statusCode' => $statusCode), $params['ref_id']);

		$response = $this->parseResultXML($response);
		$this->CI->utils->debug_log('=====================allbank processPaymentUrlFormPost response', $response);
		
		$msg = lang('Invalidate API response');

		if ($response['ReturnCode'] == self::REPONSE_CODE_SUCCESS) {
			
			$order = $this->CI->sale_order->getSaleOrderBySecureId($params['ref_id']);
			$data = array();
			$data['pay.sale_order_id'] = $params['ref_id'];
            $data['financial_account.bankaccount.walletaddress'] = $params['acctno'];
            $data['collection.label.6'] = $order->created_at;
            $data['collection.label.7'] = $order->timeout_at;

			$collection_text_transfer = '';
			$collection_text = $this->getSystemInfo("collection_text_transfer", array(''));
			if(is_array($collection_text)){
				$collection_text_transfer = $collection_text;
			}
			$is_not_display_recharge_instructions = $this->getSystemInfo('is_not_display_recharge_instructions');

			return array(
				'success' => true,
				'type' => self::REDIRECT_TYPE_STATIC,
				'data' => $data,
				'collection_text_transfer' => $collection_text_transfer,
				'is_not_display_recharge_instructions' => $is_not_display_recharge_instructions
			);

		} else {

			if (isset($response['ErrorMsg'])) {
				$msg = $response['ErrorMsg'];
			}

			return array(
				'success' => false,
				'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
				'message' => $msg
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
	public function callbackFrom($source, $orderId, $params, $response_result_id) {
		$result = array('success' => false, 'next_url' => null, 'message' => lang('error.payment.failed'));
		$this->utils->debug_log('=================allbank callbackFrom' . ucfirst($source) . ': [' . $orderId .'], params:', $params);

		$order = $this->CI->sale_order->getSaleOrderById($orderId);
		$processed = false;

		// if(empty($params)){
            $raw_post_data = file_get_contents('php://input', 'r');
            $params = json_decode($raw_post_data, true);
        // }

        if($source == 'server'){
            $this->CI->utils->debug_log('=======================allbank callbackFromServer server callbackFrom', $params);

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
			$this->CI->sale_order->updateExternalInfo($order->id, $params['sender_reference_id'], null, null, null, $response_result_id);
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

	# Callback URI: /callback/fixed_process/<payment_id>
    public function getOrderIdFromParameters($flds) {
        $this->CI->utils->debug_log('=====================allbank getOrderIdFromParameters flds', $flds);

		$raw_post_data = file_get_contents('php://input', 'r');
		$flds = json_decode($raw_post_data ,true);
		$this->utils->debug_log('======allbank getOrderIdFromParameters raw_post flds', $flds);

        if (isset($flds['sender_reference_id'])) {
			$this->CI->load->model(array('sale_order'));
			$order = $this->CI->sale_order->getSaleOrderBySecureId($flds['sender_reference_id']);
			return $order->id;
        } else {
            $this->utils->debug_log('=====================allbank getOrderIdFromParameters cannot get ref_no', $flds);
            return;
        }
    }

	/**
	 * detail: Validates whether the callback from API contains valid info and matches with the order
	 *
	 * @return boolean
	 */
	public function checkCallbackOrder($order, $fields, &$processed = false) {
		$requiredFields = array(
			'payment_datetime', 'bank_reference', 'sender_reference_id', 'status');

		foreach ($requiredFields as $f) {
			if (!array_key_exists($f, $fields)) {
				$this->writePaymentErrorLog("=====================allbank missing parameter: [$f]", $fields);
				return false;
			}
		}

		$headers = $this->CI->input->request_headers();
		$this->CI->utils->debug_log('=======================allbank callbackFromServer headers', $headers);

		if(!isset($headers['X-Alb-Signature'])) {
            $this->writePaymentErrorLog('=====================allbank checkCallbackOrder x-alb-signature not found', null);
            return false;
        }

        // # is signature authentic?
        $callbackSign = $headers['X-Alb-Signature'];
        if (!$this->validateSign( $callbackSign, $fields)) {
            $this->writePaymentErrorLog('=====================allbank checkCallbackOrder Signature Error', $callbackSign);
            return false;
        }

		if ($fields['status'] != self::RESULT_CODE_SUCCESS) {
			$resultCode = $fields['status'];
			$this->writePaymentErrorLog("=====================allbank Payment was not successful, resultCode is [$resultCode]", $fields);
			return false;
		}

		//check callback amount
		$allow_callback_amount_check = $this->getSystemInfo('allow_callback_amount_check', true);
		if ($allow_callback_amount_check) {
			$card_number = '';
			$direct_pay_extra_info = $order->direct_pay_extra_info;
			$extraInfo = json_decode($direct_pay_extra_info, true);
			if (!empty($extraInfo['card_number'])){
				$card_number = $extraInfo['card_number'];
			}
			if (!$this->checkDepositAmount($fields['bank_reference'], $this->convertAmountToCurrency($order->amount), $card_number, $order->secure_id)) {
				$this->writePaymentErrorLog("=====================allbank checkCallbackOrder checkDepositAmount failed", $fields);
				return false;
			}
		}
		
		$processed = true; # processed is set to true once the signature verification pass

		# everything checked ok
		return true;
	}

	protected function checkDepositAmount($bank_reference, $amount, $card_number, $secure_id) {
		$date = new DateTime();
		$params = array();
		$params['id'] = $this->getSystemInfo("account");
		$params['tdt'] = date_format($date, 'Y-m-d\TH:i:s.vP');
		$params['cmd'] = self::ALLBANK_SOA;
		$params['acctno'] = $card_number;
		$params['ds'] = $date->modify( 'first day of this month')->format('Y/m/d');
		$params['de'] = $date->modify('last day of this month')->format('Y/m/d');
		$params['trans_idcode'] = '0';
		$params['token'] = $this->sign($params);

		$post_xml_data = $this->array2xml($params);
		$this->CI->utils->debug_log('=====================allbank post_xml_data', $post_xml_data);

		$curlConn = curl_init();
		$curlData = array();
		$curlData[CURLOPT_POST] = true;
		$curlData[CURLOPT_URL] = $this->getSystemInfo('url');
		$curlData[CURLOPT_RETURNTRANSFER] = true;
		$curlData[CURLOPT_TIMEOUT] = 120;
		$curlData[CURLOPT_POSTFIELDS] = $post_xml_data;
		$curlData[CURLOPT_HTTPHEADER] = [ "Content-Type: text/xml", "SoapAction:'http://tempuri.org/iWebInterface/wb_Get_Info'"];

		curl_setopt_array($curlConn, $curlData);

		curl_setopt($curlConn, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curlConn, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($curlConn, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($curlConn, CURLOPT_SSL_VERIFYHOST, false);

		$response    = curl_exec($curlConn);
		$errCode     = curl_errno($curlConn);
        $error       = curl_error($curlConn);
        $statusCode  = curl_getinfo($curlConn, CURLINFO_HTTP_CODE);

        $curlSuccess = ($errCode == 0);
		$response_result_id = $this->submitPreprocess($params, $response, $this->getSystemInfo('url'), $response, array('errCode' => $errCode, 'error' => $error, 'statusCode' => $statusCode), $secure_id);

		$response = $this->parseSOAResultXML($response);
		$this->CI->utils->debug_log('=====================allbank checkDepositAmount response', $response);

		if ($response['account_info']['ReturnCode'] == self::REPONSE_CODE_SUCCESS) {
			$transactions = $response['transactions'];
			foreach ($transactions as $transaction) {
				if ($transaction['bankReference'] === strval($bank_reference) && $transaction['tranAmount'] == $amount) {
					return true;
				}
			}
			return false;
		} else {
			return false;
		}
	}

	public function directPay($order = null) {
		return array('success' => false); # direct pay not supported by this API
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
		$accessID = $this->getSystemInfo('account');
		$secretKey = $this->getSystemInfo('key');
		$signStr = $accessID.$secretKey.$params['tdt'];
		$sign = hash('sha1', $signStr);
		return $sign;
	}

	public function validateSign($callback_sign, $params) {
		$secretKey = $this->getSystemInfo('secret');
		if (isset($params['amount'])) {
			$params['amount'] = number_format($params['amount'], 2, '.', '');
		}
		$jsonParams = json_encode($params);
		$jsonParams = preg_replace('/("amount":)"([^"]+)"/', '$1$2', $jsonParams);
		$signStr = base64_encode(hash_hmac('sha256', $jsonParams , $secretKey));
		return $signStr == $callback_sign;
    }

	public function array2xml($values){
		if (!is_array($values) || count($values) <= 0) {
		    return false;
		}
		$xml = "<Account.Info";
		foreach ($values as $key => $val) {
			$xml .= " " .$key . "=" . "'" . $val . "'";
		}
		$xml .= " />";
		return $xml;
	}

	public function parseResultXML($resultXml) {	
		$result = NULL;
		$xmlString = '<root>' . $resultXml . '</root>';
		$xmlObject = simplexml_load_string($xmlString, 'SimpleXMLElement', LIBXML_NOCDATA);
		$arr = get_object_vars($xmlObject->{"Account.Info"});
		$result = $arr['@attributes'];
		$this->CI->utils->debug_log(' =========================allbank parseResultXML arr', $result);
		return $result;
	}

	public function parseSOAResultXML($resultXml) {
		$result = [];
		$transactions = [];
		$xmlString = '<root>' . $resultXml . '</root>';
		$xmlObject = simplexml_load_string($xmlString, 'SimpleXMLElement', LIBXML_NOCDATA);

		$accountInfoAttributes = get_object_vars($xmlObject->{"Account.Info"})['@attributes'];
		$result['account_info'] = $accountInfoAttributes;

		if ($accountInfoAttributes['ReturnCode'] != self::REPONSE_CODE_SUCCESS) {
			return $result;
		}

		foreach ($xmlObject->{"Account.Info"}->SOA->i as $transaction) {
			$transactions[] = get_object_vars($transaction)['@attributes'];
		}
		$result['transactions'] = $transactions;
		$this->CI->utils->debug_log('=========================allbank parseSOAResultXML result', $result);
		return $result;
	}
}
