<?php
require_once dirname(__FILE__) . '/abstract_payment_api_luxpag.php';

/**
 * luxpag取款
 *
 * * LUXPAG_WITHDRAWAL_PAYMENT_API, ID: 5937
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://www.transfersmile.com/api.v1.html#pix_payout
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 * * Extra Info:
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_luxpag_withdrawal extends Abstract_payment_api_luxpag {
	const CALLBACK_SUCCESS = 'PAID';
	const REQUEST_SUCCESS = 200;
	const RETURN_SUCCESS_CODE = 'success';

	public function getPlatformCode() {
		return LUXPAG_WITHDRAWAL_PAYMENT_API;
	}

	public function getPrefix() {
		return 'luxpag_withdrawal';
	}

	# Implement abstract function but do nothing
	protected function configParams(&$params, $direct_pay_extra_info) {}

	/**
	 * detail: override common API functionsh
	 *
	 * @return void
	 */
	public function generatePaymentUrlForm($orderId, $playerId, $amount, $orderDateTime, $playerPromoId = null, $enabledSecondUrl = true, $bankId = null) {
		return $this->returnUnimplemented();
	}

	public function processPaymentUrlForm($params) {
		return $this->returnUnimplemented();
	}

	# APIs with withdraw function need to implement these methods
	## This function returns the URL to submit withdraw request to
	public function getWithdrawUrl() {
		return $this->getSystemInfo('url');
	}

	public function getWithdrawParams($bank, $accNum, $name, $amount, $transId) {
		$this->CI->load->model(array('wallet_model', 'player_model', 'playerbankdetails'));
        $playerBankDetails = $this->CI->playerbankdetails->getBankDetailsByBankAccount($bank, $accNum);

        $this->utils->debug_log("==================luxpag withdraw get playerBankDetails using [$bank] + [$accNum]", $playerBankDetails);

        if(!empty($playerBankDetails)){
            $playerId = $playerBankDetails['playerId'];
            $playerDetails = $this->CI->player_model->getPlayerDetails($playerId);
            $pix_number  = (isset($playerDetails[0]) && !empty($playerDetails[0]['pix_number']))? $playerDetails[0]['pix_number'] : 'none';
        }

		$params = array();
		$params['amount'] = $this->convertAmountToCurrency($amount);
		$params['source_currency'] = 'BRL';
		$params['arrival_currency'] = 'BRL';
		$params['fee_bear'] = $this->getSystemInfo("fee_bear");
		$params['name'] = $name;
		$params['document_id'] = $pix_number;
		$params['pix_type'] = 'CPF';
		$params['pix_key'] = $pix_number;
        $params['notify_url']     = $this->getNotifyUrl($transId);
		$params['custom_code'] = $transId;
		$this->CI->utils->debug_log('=========================luxpag withdrawal paramStr before sign', $params);
		return $params;
	}

	public function submitWithdrawRequest($bank, $accNum, $name, $amount, $transId) {
		$result = array('success' => false, 'message' => 'payment failed');
		if(!$this->isAllowWithdraw()) {
			$result['message'] = lang("Withdraw not allowed with this API");
			$this->utils->debug_log($result);
			return $result;
		}

		$params = $this->getWithdrawParams($bank, $accNum, $name, $amount, $transId);
        list($content, $response_result) = $this->processCurl($params, true);
        $this->CI->utils->debug_log('=====================luxpag submitWithdrawRequest received response', $content);
        $decodedResult = $this->decodeResult($content);
        $decodedResult['response_result'] = $response_result;

		return $decodedResult;

	}

	public function decodeResult($resultString, $queryAPI = false) {
        $result = json_decode($resultString, true);
        $this->utils->debug_log("=========================luxpag json_decode result", $result);
        if(!empty($result) && isset($result)){
            if(!empty($result['code']) && isset($result['code']) && $result['code'] == self::REQUEST_SUCCESS ){
                return array('success' => true, 'message' => 'luxpag withdrawal request successful.');
            }else if(isset($result['msg']) && !empty($result['msg'])){
                $errorMsg = $result['msg'];
                return array('success' => false, 'message' => $errorMsg);
            }
            else{
                return array('success' => false, 'message' => 'luxpag withdrawal exist errors');
            }
        }else{
            return array('success' => false, 'message' => 'luxpag withdrawal exist errors');
        }
    }

	private function getNotifyUrl($orderId) {
        return parent::getCallbackUrl('/callback/process/' . $this->getPlatformCode() . '/' . $orderId);
    }

    private function getReturnUrl($orderId) {
        return parent::getCallbackUrl('/callback/browser/success/' . $this->getPlatformCode() . '/' . $orderId);
    }

    protected function convertAmountToCurrency($amount) {
        return number_format($amount, 2, '.', '');
    }

	public function sign($params) {
		ksort($params);
		$signStr = '';
		foreach($params as $key=>$value){
		    $signStr.=$key.'='.$value.'&';
		}
		$signature = md5(rtrim($signStr,'&').$this->getSystemInfo('key'));
        return $signature;
	}

	public function verifySignature($data) {
	    $callback_sign = $data['sign'];
        $signStr = $this->getSystemInfo('account').$this->getSystemInfo('key');
        $sign=strtoupper(md5($signStr));
        return strcasecmp($sign, $callback_sign) === 0;
    }

    public function processCurl($params, $return_all=false){
        $ch = curl_init();
        $url = $this->getSystemInfo('url');
        $merchantId = $this->getSystemInfo("account");
        $signature = $this->sign($params);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLINFO_HEADER_OUT, TRUE);

        $headers = [
            'Content-Type: application/json',
            'merchantId: '.$merchantId ,
            'Authorization: '.$signature
        ];
        $this->CI->utils->debug_log(__METHOD__, 'headers', $headers);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $this->setCurlProxyOptions($ch);
        $response    = curl_exec($ch);
        $errCode     = curl_errno($ch);
        $error       = curl_error($ch);
        $statusCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $header = substr($response, 0, $header_size);
        $responseStr = substr($response, $header_size);
        curl_close($ch);
        #save response result
        $this->CI->utils->debug_log('url', $url, 'params', $params , 'response', $response, 'errCode', $errCode, 'error', $error, 'statusCode', $statusCode);

        $response_result_id = $this->submitPreprocess($params, $response, $url, $response, array('errCode' => $errCode, 'error' => $error, 'statusCode' => $statusCode), $params['custom_code']);

        if($return_all){
            $response_result = [
                $params, $response, $url, $response, ['errCode' => $errCode, 'error' => $error, 'statusCode' => $statusCode], $params['custom_code']
            ];
            return array($response, $response_result);
        }
        return $response;
    }

    public function callbackFromServer($transId, $params) {
        $response_result_id = parent::callbackFromServer($transId, $params);
        if(empty($params) || is_null($params)){
            $raw_post_data = file_get_contents('php://input', 'r');
            $params = json_decode($raw_post_data, true);
        }

        $result = array('success' => false, 'message' => 'Payment failed');
        $order = $this->CI->wallet_model->getWalletAccountByTransactionCode($transId);
        $this->CI->utils->debug_log('=========================luxpag process withdrawalResult order id', $transId);
        $this->CI->utils->debug_log("=========================luxpag checkCallback params", $params);

        if (!$this->checkCallbackOrder($order, $params)) {
            return $result;
        }

        if($params['status'] == self::CALLBACK_SUCCESS) {
            $msg = sprintf('luxpag withdrawal was successful: trade ID [%s]', $params['custom_code']);
            $this->CI->wallet_model->withdrawalAPIReturnSuccess($transId, $msg);
            $result['message'] = self::RETURN_SUCCESS_CODE;
            $result['success'] = true;
        }
        else {
            $msg = sprintf('luxpag withdrawal was not success: [%s]', $params['status']);
            $this->writePaymentErrorLog($msg, $params);
            $result['message'] = $msg;
        }

        return $result;
    }

    private function checkCallbackOrder($order, $fields) {
        # does all required fields exist in the header?
    $requiredFields = array(
            'status', 'custom_code', 'payoutId'
        );
        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("======================luxpag withdrawal checkCallbackOrder missing parameter: [$f]", $fields);
                return false;
            }
        }

        if ($fields['custom_code'] != $order['transactionCode']) {
            $this->writePaymentErrorLog('=========================luxpag withdrawal checkCallbackOrder order IDs do not match, expected =>'. $order['transactionCode'], $fields);
            return false;
        }

        # everything checked ok
        return true;
    }
}
