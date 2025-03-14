<?php
require_once dirname(__FILE__) . '/abstract_payment_api_wpay.php';

/**
 * WPAY_WITHDRAWAL
 *
 * * WPAY_WITHDRAWAL_PAYMENT_API, ID: 6271
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: 
 * * Account: ## User ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2023 tot
 */
class Payment_api_wpay_withdrawal extends Abstract_payment_api_wpay {
    public function __construct($params = null) {
        parent::__construct($params);
        $this->_custom_curl_header = array('accept:application/json','Content-Type:application/json');	}

    public function getPlatformCode() {
        return WPAY_WITHDRAWAL_PAYMENT_API;
    }

    public function getPrefix() {
        return 'wpay_withdrawal';
    }

    # Implement abstract function but do nothing
    protected function configParams(&$params, $direct_pay_extra_info) {}
    protected function processPaymentUrlForm($params) {}
    public function submitWithdrawRequest($bank, $accNum, $name, $amount, $transId) {
        $result = array('success' => false, 'message' => 'payment failed');

        if(!$this->isAllowWithdraw()) {
            $result['message'] = lang("Withdraw not allowed with this API");
            return $result;
        }

        if(!array_key_exists($bank, $this->getBankInfo())) {
            $this->utils->error_log("========================wpays submitWithdrawRequest bank whose bankTypeId=[$bank] is not supported by wpay");
            return array('success' => false, 'message' => 'Bank not supported by wpay');
        }

        $params = $this->getWithdrawParams($bank, $accNum, $name, $amount, $transId);
        $url = $this->getWithdrawUrl();

        list($response, $response_result) = $this->submitPostForm($url, $params, true, $transId, true);

        $decodedResult = $this->decodeResult($response);
        $decodedResult['response_result'] = $response_result;

        $this->CI->utils->debug_log('======================================wpay submitWithdrawRequest url: ', $url );
        $this->CI->utils->debug_log('======================================wpay submitWithdrawRequest response', $response);
        $this->CI->utils->debug_log('======================================wpay submitWithdrawRequest decoded Result', $decodedResult);

        return $decodedResult;
    }

    public function getWithdrawUrl() {
        return $this->getSystemInfo('url');
    }

    public function getWithdrawParams($bank, $accNum, $name, $amount, $transId) {
        $this->CI->load->model(array('wallet_model', 'player_model', 'playerbankdetails'));
        $this->CI->load->library([ 'ifsc_razorpay_lib' ]);

        $playerBankDetails = $this->CI->playerbankdetails->getBankDetailsByBankAccount($bank, $accNum);
        $this->utils->debug_log("===============================wpay Get playerBankDetails using [$bank] + [$accNum]", $playerBankDetails);
        $bankInfo = $this->getBankInfo();
        $bankCode = $bankInfo[$bank]['code'];

        if(!empty($playerBankDetails)){
            $playerId = $playerBankDetails['playerId'];
            $playerDetails = $this->CI->player_model->getPlayerDetails($playerId);

            $firstname  = (isset($playerDetails[0]) && !empty($playerDetails[0]['firstName']))     ? $playerDetails[0]['firstName'] : 'no firstName';
            $lastname   = (isset($playerDetails[0]) && !empty($playerDetails[0]['lastName']))      ? $playerDetails[0]['lastName'] : 'no lastName';
            $pixNumber  = (isset($playerDetails[0]) && !empty($playerDetails[0]['pix_number']))? $playerDetails[0]['pix_number'] : 'none';
            $phone      = (isset($playerDetails[0]) && !empty($playerDetails[0]['contactNumber'])) ? $playerDetails[0]['contactNumber'] : 'none';
            $email      = (isset($playerDetails[0]) && !empty($playerDetails[0]['email'])) ? $playerDetails[0]['email'] : 'none';

        }

        $order = $this->CI->wallet_model->getWalletAccountByTransactionCode($transId);
        $bank_name = $this->findBankName($bank);

        $params = array();
        $params['money']              = intval($this->convertAmountToCurrency($amount));
        $params['account']            = $this->checkAccount($bank_name,$pixNumber,$phone,$email,$accNum);

        $params['name']               = $lastname.' '.$firstname;
        $params['bank']               = (substr($bank_name,0,3) == 'PIX')?"pix":$bank_name;

        $params['postscript']         = 'postscript';
        $params['apiCode']            = $this->getSystemInfo("account");
        $params['orderNum']           = $transId;
        $params['time']               = time();
        $params['notifyUrl']          = $this->getNotifyUrl($transId);
        $params['bankCode']           = $bankCode; //巴西通道需填入 ex:cpf | cnpj | email | phone | evp
        $params['sign']               = $this->sign($params);
        

        // $this->CI->utils->debug_log('=========================wpay getWithdrawParams params', $params);
        return $params;
    }

    public function decodeResult($resultString, $queryAPI = false) {
        if(is_array($resultString) && isset($resultString['lock']) && isset($resultString['msg'])) {
            $result = array('Success' => false, 'message' => $resultString['msg'], 'lock' => true);
            return $result;
        }
        $result = json_decode($resultString, true);
        $this->utils->debug_log("=========================wpay json_decode result", $result);

        if (isset($result['code'])&&isset($result['msg'])) {
            if($result['code'] == self::REQUEST_SUCCESS_CODE) {
                $message = "wpay withdrawal response successful, TrackingNumber:".$result['msg'];
                return array('success' => true, 'message' => $message);
            }
            $message = "wpay withdrawal response failed. ErrorMessage: ".$result['msg'];
            return array('success' => false, 'message' => $message);
        }
        elseif($result['code']!="0"){
            $message = 'wpay withdrawal response: '.$result['msg'];
            return array('success' => false, 'message' => $message);
        }
        return array('success' => false, 'message' => "wpay decoded fail.");
    }

    protected function findBankName($bank_id) {
        $bank_row = $this->CI->banktype->getBankTypeById($bank_id);
        $bank_name = lang($bank_row->bankName);

        return $bank_name;
    }

    public function callbackFromServer($transId, $params) {
        $response_result_id = parent::callbackFromServer($transId, $params);
        // if (empty($params)) {
            $raw_post_data = file_get_contents('php://input', 'r');
            $this->CI->utils->debug_log("=====================wpay raw_post_data", $raw_post_data);
            $params = json_decode($raw_post_data, true);
            // $this->CI->utils->debug_log("=====================wpay json_decode params", $params);
        // }

        $result = array('success' => false, 'message' => 'Payment failed');

        $this->CI->utils->debug_log('=========================wpay callbackFromServer transId', $transId);
        $this->CI->utils->debug_log("=========================wpay callbackFromServer params", $params);

        $order = $this->CI->wallet_model->getWalletAccountByTransactionCode($transId);

        if (!$this->checkCallbackOrder($order, $params)) {
            return $result;
        }
        
        if ($params['status'] == self::P_ERRORCODE_PAYMENT_SUCCESS) {
            $msg = sprintf('wpay withdrawal success: trade ID [%s]', $params['order']);
            $this->CI->wallet_model->withdrawalAPIReturnSuccess($transId, $msg);
            $result['message'] = self::RETURN_SUCCESS_CODE;
            $result['success'] = true;

        }else {
            $msg = sprintf("wpay withdrawal payment unsuccessful or pending: status=%s", $params['status']);
            $this->writePaymentErrorLog($msg, $params);
            $result['message'] = $msg;
            if(null!==$this->getSystemInfo("allow_auto_decline")
            &&$this->getSystemInfo("allow_auto_decline") == true){
                $msg = sprintf("wpay withdrawal payment unsuccessful auto decline: status=%s", $params['status']);
                $this->CI->wallet_model->withdrawalAPIReturnFailure($transId, $msg);
            }
        }

        $this->CI->utils->debug_log("=========================wpay callbackFromServer result", $result);

        return $result;
    }

    private function checkCallbackOrder($order, $fields) {
        $requiredFields = array(
            'order', 'money', 'charge', 'rel_money', 'time', 'status', 'sign'
        );

        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("======================wpay withdrawal checkCallbackOrder missing parameter: [$f]", $fields);
                return false;
            }
        }

        # is signature authentic?
        if (!$this->validateSign($fields)) {
            $this->writePaymentErrorLog('=====================wpay withdrawal checkCallbackOrder Signature Error', $fields['sign']);
            return false;
        }

        if ($fields['money'] != $this->convertAmountToCurrency($order['amount'])) {
            $this->writePaymentErrorLog('=========================wpay withdrawal checkCallbackOrder payment amount is wrong, expected =>'. $order['money'], $fields);
            return false;
        }

        if ($fields['order'] != $order['transactionCode']) {
            $this->writePaymentErrorLog('=========================wpay withdrawal checkCallbackOrder order IDs do not match, expected =>'. $order['transactionCode'], $fields);
            return false;
        }

        # everything checked ok
        return true;
    }

    public function callbackFromBrowser($transId, $params) {
        return array('success' => false, 'next_url' => null, 'message' => 'Error: not implemented');
    }

    # -- Private functions --
    # After payment is complete, the gateway will invoke this URL asynchronously
    public function getNotifyUrl($orderId) {
        return parent::getCallbackUrl('/callback/process/' . $this->getPlatformCode() . '/' . $orderId);
    }
    protected function sign($params){
        $keys = array('time','notifyUrl','money','orderNum');
		$signStr = $this->getSystemInfo('key');
		foreach($keys as $key) {
			$signStr .= $params[$key];
		}
		$result=md5(md5(md5($signStr)));
        return $result;
    }
	private function validateSign($params) {
        // MD5(order+money+charge+rel_money+apikey)
		$keys = array('order','money','charge','rel_money');
		$signStr = "";
		$result=false;
		foreach($keys as $key) {
			$signStr .= $params[$key];
		}
		$signStr .= $this->getSystemInfo('key');
		$sign = md5($signStr);
		if($sign === $params['sign']){
			$result=true;
		}
		return $result;
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
            $this->utils->debug_log("==================getting wpay bank info from extra_info: ", $bankInfo);
        } else  {
            $bankInfo = array(
                '30' => array('name' => 'CPF', 'code' => 'cpf'),
                '31' => array('name' => 'EMAIL', 'code' => 'email'),
                '32' => array('name' => 'PHONE', 'code' => 'phone'),
            );
            $this->utils->debug_log("=======================getting aipay bank info from code: ", $bankInfo);
        }
        return $bankInfo;
    }

    private function checkAccount($bank_name,$pixNumber,$phone,$email,$accNum){
        switch($bank_name){
            case "PIX_CPF":
            return $pixNumber;
                break;
            case "PIX_EMAIL":
                return $email;
                break;
            case "PIX_PHONE":
            return $phone;
                break;
            default:
            return $accNum;
            break;
        }
    }
}