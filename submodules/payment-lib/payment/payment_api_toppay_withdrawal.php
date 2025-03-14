<?php
require_once dirname(__FILE__) . '/abstract_payment_api_pix_toppay.php';

/**
 * TOPPAY_WITHDRAWAL
 *
 * * TOPPAY_WITHDRAWAL_PAYMENT_API, ID: 6283
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
class Payment_api_toppay_withdrawal extends Abstract_payment_api_pix_toppay {
    public function __construct($params = null) {
        parent::__construct($params);
        $this->_custom_curl_header = array('accept:application/json','Content-Type:application/json');	}

    public function getPlatformCode() {
        return TOPPAY_WITHDRAWAL_PAYMENT_API;
    }

    public function getPrefix() {
        return 'toppay_withdrawal';
    }

    # Implement abstract function but do nothing
    protected function configParams(&$params, $direct_pay_extra_info) {}
    protected function processPaymentUrlForm($params) {}
    public function submitWithdrawRequest($bank, $accNum, $name, $amount, $transId) {
        $this->CI->load->model('playerbankdetails');

        $result = array('success' => false, 'message' => 'payment failed');

        if(!$this->isAllowWithdraw()) {
            $result['message'] = lang("Withdraw not allowed with this API");
            return $result;
        }

        if(!array_key_exists($bank, $this->getBankInfo())) {
            $this->utils->error_log("========================toppays submitWithdrawRequest bank whose bankTypeId=[$bank] is not supported by toppay");
            return array('success' => false, 'message' => 'Bank not supported by toppay');
        }

        $playerBankDetails = $this->CI->playerbankdetails->getBankDetailsByBankAccount($bank, $accNum);
        $playerId = $playerBankDetails['playerId'];
        $validationResults = $this->checkWalletaccountPlayerId($playerId, $transId);

        if (!$validationResults['success']) {
            $this->utils->debug_log("===========toppay", ["result" => $validationResults]);
            return $validationResults;
        }

        $params = $this->getWithdrawParams($bank, $accNum, $name, $amount, $transId);
        $url = $this->getWithdrawUrl();

        list($response, $response_result) = $this->submitPostForm($url, $params, true, $transId, true);

        $decodedResult = $this->decodeResult($response);
        $decodedResult['response_result'] = $response_result;

        $this->CI->utils->debug_log('======================================toppay submitWithdrawRequest url: ', $url );
        $this->CI->utils->debug_log('======================================toppay submitWithdrawRequest response', $response);
        $this->CI->utils->debug_log('======================================toppay submitWithdrawRequest decoded Result', $decodedResult);

        return $decodedResult;
    }

    public function getWithdrawUrl() {
        return $this->getSystemInfo('url');
    }

    public function getWithdrawParams($bank, $accNum, $name, $amount, $transId) {
        $this->CI->load->model(array('wallet_model', 'player_model', 'playerbankdetails'));
        $this->CI->load->library([ 'ifsc_razorpay_lib' ]);

        $playerBankDetails = $this->CI->playerbankdetails->getBankDetailsByBankAccount($bank, $accNum);
        $this->utils->debug_log("===============================toppay Get playerBankDetails using [$bank] + [$accNum]", $playerBankDetails);
        $bankInfo = $this->getBankInfo();
        $bankCode = $bankInfo[$bank]['code'];

        $firstname  = "no firstName";
        $lastname   = "no lastName";
        $pixNumber  = "none";
        $phone      = "none";
        $email      = "none";

        if (!empty($playerBankDetails)) {
            $playerId = $playerBankDetails['playerId'];
            $playerDetails = $this->CI->player_model->getPlayerDetails($playerId);

            if (!empty($playerDetails[0])) {
                $firstname = isset($playerDetails[0]['firstName']) ?$playerDetails[0]['firstName'] :$firstname;
                $lastname = isset($playerDetails[0]['lastName']) ?$playerDetails[0]['lastName'] :$lastname;
                $pixNumber = isset($playerDetails[0]['pix_number']) ?$playerDetails[0]['pix_number'] :$pixNumber;
                $phone = isset($playerDetails[0]['contactNumber']) ?$playerDetails[0]['contactNumber'] :$phone;
                $email = isset($playerDetails[0]['email']) ?$playerDetails[0]['email'] :$email;
            }
        }

        $bank_name = $this->findBankName($bank);
        $params = array();
        $params['merchant_no']             = $this->getSystemInfo("account");
        $params['out_trade_no']            = $transId;
        $params['description']             = self::DESCRIPT;
        $params['pay_amount']              = $this->convertAmountToCurrency($amount);
        $params['name']                    = $lastname.$firstname;;
        $params['cpf']                     = $pixNumber;
        $params['pix_type']                = $bankCode;
        $params['dict_key']                = $this->checkAccount($bank_name,$pixNumber,$phone,$email,$accNum);
        $params['notify_url']              = $this->getNotifyUrl($transId);
        $params['sign']                    = $this->sign($params);
        $this->CI->utils->debug_log('=========================toppay getWithdrawParams params', $params);
        return $params;
    }

    public function decodeResult($resultString, $queryAPI = false) {
        if(is_array($resultString) && isset($resultString['lock']) && isset($resultString['msg'])) {
            $result = array('Success' => false, 'message' => $resultString['msg'], 'lock' => true);
            return $result;
        }
        $result = json_decode($resultString, true);
        $this->utils->debug_log("=========================toppay json_decode result", $result);

        if (isset($result['code'])&&isset($result['msg'])) {
            if($result['code'] == self::REQUEST_SUCCESS_CODE) {
                $message = "toppay withdrawal response successful, TrackingNumber:".$result['msg'];
                return array('success' => true, 'message' => $message);
            }
            $message = "toppay withdrawal response failed. ErrorMessage: ".$result['msg'];
            return array('success' => false, 'message' => $message);
        }
        elseif($result['code']!="0"){
            $message = 'toppay withdrawal response: '.$result['msg'];
            return array('success' => false, 'message' => $message);
        }
        return array('success' => false, 'message' => "toppay decoded fail.");
    }

    protected function findBankName($bank_id) {
        $bank_row = $this->CI->banktype->getBankTypeById($bank_id);
        $bank_name = lang($bank_row->bankName);

        return $bank_name;
    }

    public function callbackFromServer($transId, $params) {
        $response_result_id = parent::callbackFromServer($transId, $params);
        $raw_post_data = file_get_contents('php://input', 'r');
        $this->CI->utils->debug_log("=====================toppay raw_post_data", $raw_post_data);
        $params = json_decode($raw_post_data, true);

        $result = array('success' => false, 'message' => 'Payment failed');

        $this->CI->utils->debug_log("=========================toppay callbackFromServer params", $params);

        $order = $this->CI->wallet_model->getWalletAccountByTransactionCode($transId);

        if (!$this->checkCallbackOrder($order, $params)) {
            return $result;
        }

        if ($params['status'] == self::RETURN_CALLBACK_SUCCESS_STATUS) {
            $msg = sprintf('toppay withdrawal success: trade ID [%s]', $params['out_trade_no']);
            $this->CI->wallet_model->withdrawalAPIReturnSuccess($transId, $msg);
            $result['message'] = self::RETURN_SUCCESS_CODE;
            $result['success'] = true;
        }else {
            $msg = sprintf("toppay withdrawal payment unsuccessful or pending: status=%s", $params['status']);
            $this->writePaymentErrorLog($msg, $params);
            $result['message'] = $msg;
            if(null!==$this->getSystemInfo("allow_auto_decline")
            &&$this->getSystemInfo("allow_auto_decline") == true){
                $msg = sprintf("toppay withdrawal payment unsuccessful auto decline: status=%s", $params['status']);
                $this->CI->wallet_model->withdrawalAPIReturnFailure($transId, $msg);
            }
        }
        return $result;
    }

    private function checkCallbackOrder($order, $fields) {
        $requiredFields = array(
            'merchant_no', 'out_trade_no', 'trade_no', 'pay_amount', 'fee_amount', 'status',"sign"
        );
        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("======================toppay withdrawal checkCallbackOrder missing parameter: [$f]", $fields);
                return false;
            }
        }
        if (isset($fields['failure_reason'])) {
            $this->writePaymentErrorLog('=====================toppay withdrawal checkCallbackOrder failure_reason Error =>'. $fields['failure_reason'],  $fields['status']);
            return false;
        }
        # is signature authentic?
        if (!$this->validateSign($fields)) {
            $this->writePaymentErrorLog('=====================toppay withdrawal checkCallbackOrder Signature Error', $fields['sign']);
            return false;
        }
        if ($fields['status']!= self::RETURN_CALLBACK_SUCCESS_STATUS) {
            $this->writePaymentErrorLog('=========================toppay withdrawal checkCallbackOrder code is wrong, expected =>'. $fields['status'], $fields);
            return false;
        }
        if ($fields['pay_amount'] != $this->convertAmountToCurrency($order['amount'])) {
            $this->writePaymentErrorLog('=========================toppay withdrawal checkCallbackOrder payment amount is wrong, expected =>'. $order['amount'], $fields);
            return false;
        }

        if ($fields['out_trade_no'] != $order['transactionCode']) {
            $this->writePaymentErrorLog('=========================toppay withdrawal checkCallbackOrder order IDs do not match, expected =>'. $order['transactionCode'], $fields);
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
            $this->utils->debug_log("==================getting toppay bank info from extra_info: ", $bankInfo);
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

    private function checkAccount($bank_name, $pixNumber, $phone, $email, $accNum) {
        switch ($bank_name) {
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