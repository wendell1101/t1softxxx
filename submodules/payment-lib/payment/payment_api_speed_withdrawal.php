<?php
require_once dirname(__FILE__) . '/abstract_payment_api_speed.php';

/**
 * SPEED_WITHDRAWAL
 *
 * * SPEED_WITHDRAWAL_PAYMENT_API, ID: 
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://mgp-pay.com:8443/api/defray/V2
 * * Account: ## User ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2023 tot
 */
class Payment_api_speed_withdrawal extends Abstract_payment_api_speed {

    public function getPlatformCode() {
        return SPEED_WITHDRAWAL_PAYMENT_API;
    }

    public function getPrefix() {
        return 'speed_withdrawal';
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

        # look up bank code
        $bankInfo = $this->getBankInfo();
        if(!array_key_exists($bank, $bankInfo)) {
            $this->utils->error_log("========================speed withdrawal bank whose bankTypeId=[$bank] is not supported by speed");
            return array('success' => false, 'message' => 'Bank not supported by speed');
        }

        $params = $this->getWithdrawParams($bank, $accNum, $name, $amount, $transId);
        $url = $this->getWithdrawUrl();

        list($response, $response_result) = $this->submitPostForm($url, $params, true, $transId, true);

        $decodedResult = $this->decodeResult($response);
        $decodedResult['response_result'] = $response_result;

        $this->CI->utils->debug_log('======================================speed submitWithdrawRequest url: ', $url );
        $this->CI->utils->debug_log('======================================speed submitWithdrawRequest response', $response);
        $this->CI->utils->debug_log('======================================speed submitWithdrawRequest decoded Result', $decodedResult);

        return $decodedResult;
    }

    public function getWithdrawUrl() {
        return $this->getSystemInfo('url');
    }

    public function getWithdrawParams($bank, $accNum, $name, $amount, $transId) {
        $this->CI->load->model(array('wallet_model', 'player_model', 'playerbankdetails'));

        $playerBankDetails = $this->CI->playerbankdetails->getBankDetailsByBankAccount($bank, $accNum);
        $this->utils->debug_log("===============================speed Get playerBankDetails using [$bank] + [$accNum]", $playerBankDetails);

        if(!empty($playerBankDetails)){
            $playerId = $playerBankDetails['playerId'];
            $playerDetails = $this->CI->player_model->getPlayerDetails($playerId);
            $firstname  = (isset($playerDetails[0]) && !empty($playerDetails[0]['firstName']))     ? $playerDetails[0]['firstName'] : 'no firstName';
            $lastname   = (isset($playerDetails[0]) && !empty($playerDetails[0]['lastName']))      ? $playerDetails[0]['lastName'] : 'no lastName';
            $phone      = (isset($playerDetails[0]) && !empty($playerDetails[0]['contactNumber'])) ? $playerDetails[0]['contactNumber'] : 'none';
        }

        $order = $this->CI->wallet_model->getWalletAccountByTransactionCode($transId);
		$playerId = $order['playerId'];
		$player = $this->CI->player_model->getPlayerDetailsById($playerId);
        $player = get_object_vars($player);
        $dialingCode = (isset($player['dialing_code']) && !empty($player['dialing_code'])) ? $player['dialing_code'] : '';

        $order = $this->CI->wallet_model->getWalletAccountByTransactionCode($transId);
        $bankInfo = $this->getBankInfo();
        $this->CI->utils->debug_log('=========================speed bankInfo ', $bankInfo);
        $this->CI->utils->debug_log('=========================speed bankInfo ', $bankInfo[$bank]);

        $Params = array();
        $Params['version'] = "V2";
        $Params['signType'] = "MD5";
        $Params['merchantNo'] = $this->getSystemInfo("account");
        $Params['date'] = date("YmdHis");
        $Params['channleType'] = "3";
        $Params['orderNo'] = $transId;
        $Params['bizAmt'] = $amount;
        $Params['accName'] = $firstname.$lastname;
        $Params['bankCode'] = $bankInfo[$bank]['code'];
        $Params['cardNo'] = $accNum;
        $Params['bankBranchName'] = $bankInfo[$bank]['name'];
        $Params['noticeUrl'] = $this->getNotifyUrl($transId);
        $Params['sign'] = $this->sign($Params);

        $this->CI->utils->debug_log('=========================speed getWithdrawParams params', $Params);

        return $Params;
    }

    public function decodeResult($resultString, $queryAPI = false) {
        if(is_array($resultString) && isset($resultString['lock']) && isset($resultString['msg'])) {
            $result = array('success' => false, 'message' => $resultString['msg'], 'lock' => true);
            return $result;
        }
        $result = json_decode($resultString, true);
        $this->utils->debug_log("=========================speed json_decode result", $result);

        if (isset($result['code'])) {
            if($result['code'] == self::REPONSE_CODE_SUCCESS) {
                $message = "speed withdrawal response successful, code:".$result['code'];
                return array('success' => true, 'message' => $message);
            }
            $message = "speed withdrawal response failed. ErrorMessage: ".$result['message'];
            return array('success' => false, 'message' => $message);

        }
        elseif($result['message']){
            $message = 'speed withdrawal response: '.$result['message'];
            return array('success' => false, 'message' => $message);
        }
        return array('success' => false, 'message' => "speed decoded fail.");
    }

    public function callbackFromBrowser($transId, $params) {
        return array('success' => false, 'next_url' => null, 'message' => 'Error: not implemented');
    }

    # -- Private functions --
    # After payment is complete, the gateway will invoke this URL asynchronously
    public function getNotifyUrl($orderId) {
        return parent::getCallbackUrl('/callback/process/' . $this->getPlatformCode() . '/' . $orderId);
    }

    # -- info --
    public function getBankInfo() {
        $bankInfo = array();
        $bankInfoArr = $this->getSystemInfo("withdrawal_bank_info");
        $this->utils->debug_log("==================speed getting bankInfoArr: ", $bankInfoArr);

        if(!empty($bankInfoArr)) {
            foreach($bankInfoArr as $system_bank_type_id => $bankInfoItem) {
                if(isset($bankInfoItem['name'])){
                    $bankInfo[$system_bank_type_id]['name'] = $bankInfoItem['name'];
                }
                if(isset($bankInfoItem['code'])){
                    $bankInfo[$system_bank_type_id]['code'] = $bankInfoItem['code'];
                }
            }
            $this->utils->debug_log("==================speed getting speed bank info from extra_info: ", $bankInfo);
        } else {
            $bankInfo =  array(
                "28"=>  array(
                    "name"=>  "BANGKOK BANK PUBLIC COMPANY LTD",
                    "code"=>  "T_BBL"
                ),
                "29"=>  array(
                    "name"=>  "KRUNG THAI BANK PUBLIC COMPANY LTD",
                    "code"=>  "T_KTB"
                ),
                "30"=>  array(
                    "name"=>  "Siam Commercial Bank",
                    "code"=>  "T_TH_SCB"
                ),
                "31"=>  array(
                    "name"=>  "KASIKORNBANK",
                    "code"=>  "T_KBANK"
                ),
                "34"=>  array(
                    "name"=>  "CIMB THAI BANK PUBLIC COMPANY LTD",
                    "code"=>  "T_CIMB"
                ),
                "35"=>  array(
                    "name"=>  "CITI BANK N.A",
                    "code"=>  "T_TH_CITI"
                ),
                "37"=>  array(
                    "name"=>  "KIATNAKIN BANK PUBLIC COMPANY LTD",
                    "code"=>  "T_KKP"
                ),
                "38"=>  array(
                    "name"=>  "STANDARD CHARTERED BANK (THAI) PCL",
                    "code"=>  "T_SCBT"
                ),
                "56"=>  array(
                    "name"=>  "SUMITOMO MITSUI BANGKING CORPORATION",
                    "code"=>  "T_SMBC"
                ),
                "58"=>  array(
                    "name"=>  "GOVERNMENT SAVING BANK",
                    "code"=>  "T_GSB"
                ),
                "59"=>  array(
                    "name"=>  "GOVERNMENT HOUSING BANK",
                    "code"=>  "T_GHBA"
                ),
                "61"=>  array(
                    "name"=>  "BANK FOR AGRICULTURE AND AGRICULTURAL CO-OPERATIVES",
                    "code"=>  "014"
                ),
                "62"=>  array(
                    "name"=>  "MIZUHO CORPORATE BANK",
                    "code"=>  "015"
                ),
                "63"=>  array(
                    "name"=>  "ISLAMIC BANK OF THAILAND",
                    "code"=>  "T_ISBT"
                ),
                "65"=>  array(
                    "name"=>  "INDUSTRIAL AND COMMERCIAL BANK OF CHINA (THAI)PCL",
                    "code"=>  "T_TH_ICBC"
                ),
                "66"=>  array(
                    "name"=>  "THE THAI CREDIT RETAIL BANK",
                    "code"=>  "T_TCRB"
                )
            )
            ;
            $this->utils->debug_log("=======================getting speed bank info from code: ", $bankInfo);
        }
        return $bankInfo;
    }

    public function callbackFromServer($transId, $params) {
        $response_result_id = parent::callbackFromServer($transId, $params);
        $result = array('success' => false, 'message' => 'Payment failed');
        $order = $this->CI->wallet_model->getWalletAccountByTransactionCode($transId);

        if (empty($params)) {
            $raw_post_data = file_get_contents('php://input', 'r');
            $this->CI->utils->debug_log("=====================speed withdrawal callbackFromServer raw_post_data", $raw_post_data);
            $params = json_decode($raw_post_data,true);
            $this->CI->utils->debug_log("=====================speed withdrawal callbackFromServer json_decode params", $params);
        }

        if (!$this->checkCallbackOrder($order, $params)) {
            return $result;
        }

        if($params['status'] == self::ORDER_STATUS_SUCCESS) {
            $msg = sprintf('speed withdrawal was successful: trade ID [%s]', $params['orderNo']);
            $this->withdrawalSuccess($transId, $msg);
            $result['message'] = self::RETURN_SUCCESS_CODE;
            $result['success'] = true;
        }
        else {
            $msg = sprintf('speed withdrawal was not success: [%s]', $params['orderStatus']);
            $this->writePaymentErrorLog($msg, $params);
            $result['message'] = $msg;
        }

        return $result;
    }

    public function checkCallbackOrder($order, $fields, &$processed = false)
    {
        $requiredFields = array('orderNo','orderAmt', 'bizAmt', 'status', 'version',"sign");

        $this->CI->utils->debug_log("=========================speed checkCallback detailData", $fields);

        foreach ($requiredFields as $f) {
           if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("=======================speed withdrawal checkCallbackOrder missing parameter: [$f]", $fields);
                return false;
            }
        }

        # is signature authentic?
        if (!$this->validateSign($fields)) {
           $this->writePaymentErrorLog('=====================speed withdrawal checkCallbackOrder Signature Error', $fields);
           return false;
        }

        $processed = true; # processed is set to true once the signature verification pass

        if ($fields['orderNo'] != $order['transactionCode']) {
            $this->writePaymentErrorLog("======================speed withdrawal checkCallbackOrder order IDs do not match, expected ".$order['transactionCode'], $fields);
            return false;
        }

        if ($fields['orderAmt']  != $this->convertAmountToCurrency($order['amount'])) {
            $this->writePaymentErrorLog("======================speed withdrawal checkCallbackOrder payment amount is wrong, expected <= ". $order['amount'], $fields);
            return false;
        }

        return true;
    }
}