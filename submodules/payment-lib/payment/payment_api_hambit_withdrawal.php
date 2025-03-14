<?php
require_once dirname(__FILE__) . '/abstract_payment_api_hambit.php';

/**
 * HAMBIT取款
 *
 * * HAMBIT_WITHDRAWAL_PAYMENT_API, ID: 6316
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: 
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 * * Extra Info:
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_hambit_withdrawal extends abstract_payment_api_hambit {

	public function getPlatformCode() {
		return HAMBIT_WITHDRAWAL_PAYMENT_API;
	}

	public function getPrefix() {
		return 'hambit_withdrawal';
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

        $this->utils->debug_log("==================hambit withdraw get playerBankDetails using [$bank] + [$accNum]", $playerBankDetails);
        $bankInfo = $this->getBankInfo();
        $bankCode = $bankInfo[$bank]['code'];

        $firstname  = "no firstName";
        $lastname   = "no lastName";
        $pix_number  = "none";
        $phone      = "none";
        $email      = "none";

        if(!empty($playerBankDetails)){
            $playerId = $playerBankDetails['playerId'];
            $playerDetails = $this->CI->player_model->getPlayerDetails($playerId);
            $pix_number  = (isset($playerDetails[0]) && !empty($playerDetails[0]['pix_number']))? $playerDetails[0]['pix_number'] : 'none';
            $phone      = (isset($playerDetails[0]) && !empty($playerDetails[0]['contactNumber'])) ? $playerDetails[0]['contactNumber'] : 'none';
            $email      = (isset($playerDetails[0]) && !empty($playerDetails[0]['email'])) ? $playerDetails[0]['email'] : 'none';
        }

        $params['currencyAmount'] = $this->convertAmountToCurrency($amount);
        $params['channelType'] = "PIX";
        $params['externalOrderId'] = $transId;
        $params['personIdType'] = $this->getSystemInfo("personIdType");//
        $params['personId'] = $accNum;
        $params['personName'] = $firstname." ".$lastname;
        $params['accountType'] = $bankCode;//
        $params['accountId'] = $this->checkAccount($bankCode,$pix_number,$phone,$email,$accNum);
        $params['remark'] = "withdrawal";
        $params['notifyUrl'] = $this->getNotifyUrl($transId);
        #head params
        $params['access_key'] = $this->getSystemInfo("account");
        $params['timestamp'] =  number_format(microtime(true) * 1000, 0, '', '');
        $params['nonce'] = $this->createUUID();

        $params['sign'] = $this->sign($params);

		$this->CI->utils->debug_log('=========================hambit withdrawal paramStr before sign', $params);
		return $params;
	}

	public function submitWithdrawRequest($bank, $accNum, $name, $amount, $transId) {
        $result = array('success' => false, 'message' => 'payment failed');
        $this->CI->load->model('playerbankdetails');

        if(!$this->isAllowWithdraw()) {
            $result['message'] = lang("Withdraw not allowed with this API");
            $this->utils->debug_log($result);
            return $result;
        }

        $playerBankDetails = $this->CI->playerbankdetails->getBankDetailsByBankAccount($bank, $accNum);
        $playerId = $playerBankDetails['playerId'];
        $validationResults = $this->checkWalletaccountPlayerId($playerId, $transId);
        if (!$validationResults['success']) {
            $this->utils->debug_log("===========hambit", ["result" => $validationResults]);
            return $validationResults;
        }

        $params = $this->getWithdrawParams($bank, $accNum, $name, $amount, $transId);
        $url = $this->getWithdrawUrl();

        $this->_custom_curl_header = array(
            'Content-Type:application/json',
            'access_key:'.$params['access_key'],
            'timestamp: ' . $params['timestamp'],
            'nonce: ' . $params['nonce'],
            'sign: ' . $params['sign'],
        );
        $unset_params = ['access_key','timestamp','nonce','sign'];
        foreach ($unset_params as $key) {
            unset($params[$key]);
        }


        list($content, $response_result) = $this->submitPostForm($url, $params, true, $transId, true);

        $decodedResult = $this->decodeResult($content);

        $decodedResult['response_result'] = $response_result;

        $this->CI->utils->debug_log('======================================hambit submitWithdrawRequest url: ', $url );
        $this->CI->utils->debug_log('======================================hambit submitWithdrawRequest params: ', $params);
        $this->CI->utils->debug_log('======================================hambit submitWithdrawRequest decoded Result', $decodedResult);
        
        return $decodedResult;
    }

	public function decodeResult($resultString, $queryAPI = false) {
        if(is_array($resultString) && isset($resultString['lock']) && isset($resultString['msg'])) {
            $result = array('success' => false, 'message' => $resultString['msg'], 'lock' => true);
            return $result;
        }
        $result = json_decode($resultString, true);
        $this->utils->debug_log("=========================hambit json_decode result", $result);
        if(!empty($result) && isset($result)){
            if(isset($result['code']) && $result['code'] == self::REPONSE_CODE_SUCCESS ){
                return array('success' => true, 'message' => 'hambit withdrawal request successful.');
            }else if(isset($result['error']) && !empty($result['error'])){
                $errorMsg = $result['error'];
                return array('success' => false, 'message' => $errorMsg);
            }else{
                return array('success' => false, 'message' => 'hambit withdrawal exist errors');
            }
        }else{
            return array('success' => false, 'message' => 'hambit withdrawal exist errors');
        }
    }

	public function getNotifyUrl($orderId) {
        return parent::getCallbackUrl('/callback/process/' . $this->getPlatformCode() . '/' . $orderId);
    }

    public function getReturnUrl($orderId) {
        return parent::getCallbackUrl('/callback/browser/success/' . $this->getPlatformCode() . '/' . $orderId);
    }

    public function callbackFromServer($transId, $params) {
        $response_result_id = parent::callbackFromServer($transId, $params);
        $result = array('success' => false, 'message' => 'Payment failed');
        $order = $this->CI->wallet_model->getWalletAccountByTransactionCode($transId);

        if (empty($params)) {
            $raw_post_data = file_get_contents('php://input', 'r');
            $this->CI->utils->debug_log("=====================hambit withdrawal callbackFromServer raw_post_data", $raw_post_data);
            $params = json_decode($raw_post_data,true);
            $this->CI->utils->debug_log("=====================hambit withdrawal callbackFromServer json_decode params", $params);
        }

        if (!$this->checkCallbackOrder($order, $params)) {
            return $result;
        }

        if($params['orderStatusCode'] == self::WITHDRAWAL_CALLBACK_ORDER_STATUS_CODE) {
            $msg = sprintf('hambit withdrawal was successful: trade ID [%s]', $params['externalOrderId']);
            $this->CI->wallet_model->withdrawalAPIReturnSuccess($transId, $msg);
            $return_message=[
                "code"=>self::REPONSE_CODE_SUCCESS,
                "success"=>true,
            ];
            $result['message'] = json_encode($return_message);
            $result['success'] = true;
        }
        else {
            $msg = sprintf('hambit withdrawal was not success: [%s]', $params['orderStatusCode']);
            $this->writePaymentErrorLog($msg, $params);
            $return_message=[
                "code"=>self::REPONSE_CODE_SUCCESS,
                "success"=>true,
            ];
            $result['message'] = json_encode($return_message);
            $result['success'] = true;        
        }

        return $result;
    }

    public function checkCallbackOrder($order, $fields, &$processed = false)
    {
        $head_params =[
            "access_key"=>$this->getSystemInfo('access_key'),//本幾紀錄商户号
            "timestamp"=>$_SERVER["HTTP_TIMESTAMP"],
            "nonce"=>$_SERVER["HTTP_NONCE"],
            "sign"=>$_SERVER["HTTP_SIGN"],
        ];

        $requiredFields = array('currencyType','externalOrderId', 'orderStatusCode', 'orderAmount');

        $this->CI->utils->debug_log("=========================hambit checkCallback detailData", $fields);

        foreach ($requiredFields as $f) {
           if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("=======================hambit withdrawal checkCallbackOrder missing parameter: [$f]", $fields);
                return false;
            }
        }

        if($fields['currencyType']!="BRL"){
            $this->writePaymentErrorLog("=======================hambit withdrawal checkCallbackOrder currencyType is error", $fields);
            return false;
        }
        
        # is signature authentic?
        if (!$this->validateSign($fields, $head_params)) {
           $this->writePaymentErrorLog('=====================hambit withdrawal checkCallbackOrder Signature Error', $fields);
           return false;
        }

        $processed = true; # processed is set to true once the signature verification pass

        if ($fields['externalOrderId'] != $order['transactionCode']) {
            $this->writePaymentErrorLog("======================hambit withdrawal checkCallbackOrder order IDs do not match, expected ".$order['transactionCode'], $fields);
            return false;
        }

        if ($fields['orderAmount']  != $this->convertAmountToCurrency($order['amount'])) {
            $this->writePaymentErrorLog("======================hambit withdrawal checkCallbackOrder payment amount is wrong, expected <= ". $order['amount'], $fields);
            return false;
        }

        return true;
    }
    private function getBankInfo(){
        $bankInfo = array();
        $bankInfoArr = $this->getSystemInfo("withdrawal_bank_info");
        if(!empty($bankInfoArr)) {
            foreach($bankInfoArr as $system_bank_type_id => $bankInfoItem) {
                if(isset($bankInfoItem['name'])){
                    $bankInfo[$system_bank_type_id]['name'] = $bankInfoItem['name'];
                }
                if(isset($bankInfoItem['code'])){
                    $bankInfo[$system_bank_type_id]['code'] = $bankInfoItem['code'];
                }
            }
            $this->utils->debug_log("==================getting hambit bank info from extra_info: ", $bankInfo);
        } else  {
            $bankInfo = array(
                '47' => array('name' => 'CPF', 'code' => 'CPF'),
                '48' => array('name' => 'EMAIL', 'code' => 'EMAIL'),
                '49' => array('name' => 'PHONE', 'code' => 'PHONE'),
            );
            $this->utils->debug_log("=======================getting hambit bank info from code: ", $bankInfo);
        }
        return $bankInfo;
    }
    private function checkAccount($bank_name, $pixNumber, $phone, $email, $accNum) {
        switch ($bank_name) {
            case "CPF":
                return $pixNumber;
                break;
            case "EMAIL":
                return $email;
                break;
            case "PHONE":
                return $phone;
                break;
            default:
                return $accNum;
                break;
        }
    }
}
