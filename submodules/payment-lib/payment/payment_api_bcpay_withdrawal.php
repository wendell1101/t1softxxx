<?php
require_once dirname(__FILE__) . '/abstract_payment_api_bcpay.php';

/**
 * BCPAY取款
 *
 * * BCPAY_WITHDRAWAL_PAYMENT_API, ID: 6086
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://api.betcatpay.com/api/v1/payout/order/create
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 * * Extra Info:
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_bcpay_withdrawal extends abstract_payment_api_bcpay {

	public function getPlatformCode() {
		return BCPAY_WITHDRAWAL_PAYMENT_API;
	}

	public function getPrefix() {
		return 'bcpay_withdrawal';
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

        $this->utils->debug_log("==================BCpay withdraw get playerBankDetails using [$bank] + [$accNum]", $playerBankDetails);

        $bankInfo = $this->getBankInfo();
        $bankCode = $bankInfo[$bank]['code'];

        $firstname  = "no firstName";
        $lastname   = "no lastName";
        $pix_number  = "none";
        $email      = "none";

        if(!empty($playerBankDetails)){
            $playerId = $playerBankDetails['playerId'];
            $playerDetails = $this->CI->player_model->getPlayerDetails($playerId);
            $pix_number  = (isset($playerDetails[0]) && !empty($playerDetails[0]['pix_number']))? $playerDetails[0]['pix_number'] : 'none';
            $email      = (isset($playerDetails[0]) && !empty($playerDetails[0]['email'])) ? $playerDetails[0]['email'] : 'none';
        }

        $detailInfo['bankCode']    = $bankCode;
        $getAccount =$this->getAccount($bankCode, $pix_number,$email, $accNum);
        if(!$getAccount['success']){
            return $getAccount;
        }
        $detailInfo['accountNo']   = $getAccount['accNum'];
        $detailInfo['accountName'] = $firstname.$lastname;
        $detailInfo['document']   = $pix_number;
		$params = array();
        $params['appId']        = $this->getSystemInfo("account");
        $params['merOrderNo']   = $transId;
        $params['currency']     = $this->getSystemInfo("currency");
        $params['amount']       = $this->convertAmountToCurrency($amount);
        $params['notifyUrl']    = $this->getNotifyUrl($transId);
        $params['extra']        = $detailInfo;
        $params['sign']         = $this->sign($params);

		$this->CI->utils->debug_log('=========================BCpay withdrawal paramStr before sign', $params);
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
            $this->utils->debug_log("===========BCpay", ["result" => $validationResults]);
            return $validationResults;
        }

        $params = $this->getWithdrawParams($bank, $accNum, $name, $amount, $transId);

        //params has fail
        if(isset($params['success'])&&!$params['success']){
            $result['message'] = $params['message'];
            return $result;
        }
        
        $url = $this->getWithdrawUrl();

        list($content, $response_result) = $this->submitPostForm($url, $params, true, $transId, true);

        $decodedResult = $this->decodeResult($content);


        $decodedResult['response_result'] = $response_result;

        $this->CI->utils->debug_log('======================================BCpay submitWithdrawRequest url: ', $url );
        $this->CI->utils->debug_log('======================================BCpay submitWithdrawRequest params: ', $params);
        $this->CI->utils->debug_log('======================================BCpay submitWithdrawRequest decoded Result', $decodedResult);
        
        return $decodedResult;
    }

	public function decodeResult($resultString, $queryAPI = false) {
        if(is_array($resultString) && isset($resultString['lock']) && isset($resultString['msg'])) {
            $result = array('success' => false, 'message' => $resultString['msg'], 'lock' => true);
            return $result;
        }
        $result = json_decode($resultString, true);
        $this->utils->debug_log("=========================BCpay json_decode result", $result);
        if(!empty($result) && isset($result)){
            if(isset($result['code']) && $result['code'] == self::REPONSE_CODE_SUCCESS ){
                return array('success' => true, 'message' => 'bcpay withdrawal request successful.');
            }else if(isset($result['error']) && !empty($result['error'])){
                $errorMsg = $result['error'];
                return array('success' => false, 'message' => $errorMsg);
            }else{
                return array('success' => false, 'message' => 'BCpay withdrawal exist errors');
            }
        }else{
            return array('success' => false, 'message' => 'BCpay withdrawal exist errors');
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
            $this->CI->utils->debug_log("=====================BCpay withdrawal callbackFromServer raw_post_data", $raw_post_data);
            $params = json_decode($raw_post_data,true);
            $this->CI->utils->debug_log("=====================BCpay withdrawal callbackFromServer json_decode params", $params);
        }

        if (!$this->checkCallbackOrder($order, $params)) {
            return $result;
        }

        if($params['orderStatus'] == self::ORDER_STATUS_SUCCESS_1 || $params['orderStatus'] == self::ORDER_STATUS_SUCCESS_2) {
            $msg = sprintf('BCpay withdrawal was successful: trade ID [%s]', $params['merOrderNo']);
            $this->CI->wallet_model->withdrawalAPIReturnSuccess($transId, $msg);
            $result['message'] = self::RETURN_SUCCESS_CODE;
            $result['success'] = true;
        }
        else {
            $msg = sprintf('BCpay withdrawal was not success: [%s]', $params['orderStatus']);
            $this->writePaymentErrorLog($msg, $params);
            $result['message'] = $msg;
        }

        return $result;
    }

    public function checkCallbackOrder($order, $fields, &$processed = false)
    {
        $requiredFields = array('orderStatus','orderNo', 'merOrderNo', 'amount', 'sign');

        $this->CI->utils->debug_log("=========================BCpay checkCallback detailData", $fields);

        foreach ($requiredFields as $f) {
           if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("=======================BCpay withdrawal checkCallbackOrder missing parameter: [$f]", $fields);
                return false;
            }
        }

        # is signature authentic?
        if (!$this->validateSign($fields)) {
           $this->writePaymentErrorLog('=====================BCpay withdrawal checkCallbackOrder Signature Error', $fields);
           return false;
        }

        $processed = true; # processed is set to true once the signature verification pass

        if ($fields['merOrderNo'] != $order['transactionCode']) {
            $this->writePaymentErrorLog("======================BCpay withdrawal checkCallbackOrder order IDs do not match, expected ".$order['transactionCode'], $fields);
            return false;
        }

        if ($fields['amount']  != $this->convertAmountToCurrency($order['amount'])) {
            $this->writePaymentErrorLog("======================BCpay withdrawal checkCallbackOrder payment amount is wrong, expected <= ". $order['amount'], $fields);
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
            $this->utils->debug_log("==================BCppay bank info from extra_info: ", $bankInfo);
        } else  {
            $bankInfo = array(
                '47' => array('name' => 'CPF', 'code' => 'CPF'),
                '48' => array('name' => 'EMAIL', 'code' => 'EMAIL'),
                '49' => array('name' => 'PHONE', 'code' => 'PHONE'),
            );
            $this->utils->debug_log("=======================BCppay bank info from code: ", $bankInfo);
        }
        return $bankInfo;
    }
    private function getAccount($bank_name, $pixNumber,$email, $accNum) {
        $result=[
            '$accNum'=>"",
            'success'=>true,
            'message'=>lang('account checked')
        ];
        switch ($bank_name) {
            case "CPF":
                $result['accNum']=$pixNumber;
                return $result;
                break;
            case "EMAIL":
                $result['accNum']=$email;
                return $result;
                break;
            case "PHONE":
                $check_phone=$this->getCountryCode($bank_name,$accNum);
                $result=$check_phone;
                return $result;
                break;
            default:
                $result['accNum']=$accNum;
                return $result;
                break;
        }
    }
    private function getCountryCode($bankCode, $accNum) {
        $check_result=[
            'accNum'=>null,
            'success'=>false, 
            'message'=>lang('Country code is not allowed')
        ];
        $allow_country_code=$this->getSystemInfo('allow_country_code');
        if($allow_country_code){
            $this->utils->debug_log("==================BCpay allow_country_code ",$this->getSystemInfo('allow_country_code'));
            //大小寫不敏感比較
            if(strcasecmp($bankCode, 'phone') == 0){
                if( !empty($this->getSystemInfo("country_code")) ){
                    $country = $this->getSystemInfo("country_code");
                }else if (!empty($this->CI->utils->getConfig('enable_default_dialing_code')['Brazil'])){
                    $country = $this->CI->utils->getConfig('enable_default_dialing_code')['Brazil'];
                }
                if(empty($country)){
                    $check_result['message']=lang('Please set the country code in the system setting');
                }else {
                    if(strpos($country, '+') === false){
                        $check_result['accNum'] = '+'.$country.$accNum;
                    }else{
                        $check_result['accNum'] = $country.$accNum;
                    }
                    $check_result['success']= true; 
                    $check_result['message']= lang('Country code is allowed');             
                }
            }
        } else{
            $check_result=[
                'accNum'=>$accNum,
                'success'=>true, 
                'message'=>null
            ];
        }
        return $check_result;
    }
}
