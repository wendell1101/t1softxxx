<?php
require_once dirname(__FILE__) . '/abstract_payment_api_corepay.php';

/**
 * COREPAY_WITHDRAWAL
 *
 * * COREPAY_WITHDRAWAL_PAYMENT_API, ID: 6251
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://vippay.corepaypro.com/trade/pay
 * * Account: ## User ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2023 tot
 */
class Payment_api_corepay_withdrawal extends Abstract_payment_api_corepay {

    public function getPlatformCode() {
        return COREPAY_WITHDRAWAL_PAYMENT_API;
    }

    public function getPrefix() {
        return 'corepay_withdrawal';
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
            $this->utils->error_log("========================corepay withdrawal bank whose bankTypeId=[$bank] is not supported by corepay");
            return array('success' => false, 'message' => 'Bank not supported by corepay');
        }

        $params = $this->getWithdrawParams($bank, $accNum, $name, $amount, $transId);
        $url = $this->getWithdrawUrl();

        list($response, $response_result) = $this->submitPostForm($url, $params, true, $transId, true);

        $decodedResult = $this->decodeResult($response);
        $decodedResult['response_result'] = $response_result;

        $this->CI->utils->debug_log('======================================corepay submitWithdrawRequest url: ', $url );
        $this->CI->utils->debug_log('======================================corepay submitWithdrawRequest response', $response);
        $this->CI->utils->debug_log('======================================corepay submitWithdrawRequest decoded Result', $decodedResult);

        return $decodedResult;
    }

    public function getWithdrawUrl() {
        return $this->getSystemInfo('url');
    }

    public function getWithdrawParams($bank, $accNum, $name, $amount, $transId) {
        $this->CI->load->model(array('wallet_model', 'player_model', 'playerbankdetails'));

        $playerBankDetails = $this->CI->playerbankdetails->getBankDetailsByBankAccount($bank, $accNum);
        $this->utils->debug_log("===============================corepay Get playerBankDetails using [$bank] + [$accNum]", $playerBankDetails);

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

        $unSignParams = array();
        $unSignParams['mch_order_no'] = $transId;
        $unSignParams['amount']       = $this->convertAmountToCurrency($amount);
        $unSignParams['bank_code']    = $bankInfo[$bank]['code'];
        $unSignParams['bank_card']    = $accNum;
        $unSignParams['user_name']    = $lastname.' '.$firstname;
        $unSignParams['user_mobile']  = $dialingCode.$phone;
        $unSignParams['mch_id']       = $this->getSystemInfo("account");
        $unSignParams['appid']        = $this->getSystemInfo("app_id");
        $signStr = $this->encrypt($unSignParams);

        $params = array();
        $params['data'] = array (
               'partner_key' => $this->getSystemInfo("key"),
               'en_data' => $signStr
        );

        $this->CI->utils->debug_log("=========================corepay getWithdrawParams unSignParams", $unSignParams);
        $this->CI->utils->debug_log('=========================corepay getWithdrawParams params', $params);

        return $params;
    }

    public function decodeResult($resultString, $queryAPI = false) {
        if(is_array($resultString) && isset($resultString['lock']) && isset($resultString['msg'])) {
            $result = array('success' => false, 'message' => $resultString['msg'], 'lock' => true);
            return $result;
        }
        $result = json_decode($resultString, true);
        $this->utils->debug_log("=========================corepay json_decode result", $result);

        if (isset($result['code'])) {
            if($result['code'] == self::REPONSE_CODE_SUCCESS) {
                $message = "corepay withdrawal response successful, code:".$result['code'];
                return array('success' => true, 'message' => $message);
            }
            $message = "corepay withdrawal response failed. ErrorMessage: ".$result['message'];
            return array('success' => false, 'message' => $message);

        }
        elseif($result['message']){
            $message = 'corepay withdrawal response: '.$result['message'];
            return array('success' => false, 'message' => $message);
        }
        return array('success' => false, 'message' => "corepay decoded fail.");
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
        $this->utils->debug_log("==================getting bankInfoArr: ", $bankInfoArr);

        if(!empty($bankInfoArr)) {
            foreach($bankInfoArr as $system_bank_type_id => $bankInfoItem) {
                if(isset($bankInfoItem['name'])){
                    $bankInfo[$system_bank_type_id]['name'] = $bankInfoItem['name'];
                }
                if(isset($bankInfoItem['code'])){
                    $bankInfo[$system_bank_type_id]['code'] = $bankInfoItem['code'];
                }
            }
            $this->utils->debug_log("==================getting corepay bank info from extra_info: ", $bankInfo);
        } else {
            $bankInfo = array(
                "28" =>  array('name' => "Bangkok Bank", 'code' => 'BBL'),
                "29" =>  array('name' => "Krung Thai Bank", 'code' => 'KTB'),
                "30" =>  array('name' => "Siam Commercial Bank", 'code' => 'SCB'),
                "31" =>  array('name' => "Kasikorn Bank", 'code' => 'KBANK'),
                "32" =>  array('name' => "Thai Military Bank", 'code' => 'TMB'),
                "33" =>  array('name' => "Krungsri Ayudhaya Bank", 'code' => 'BAY'), 
                "34" =>  array('name' => "CIMB Bank", 'code' => 'CIMB'),
                "35" =>  array('name' => "Citibank National Association", 'code' => 'CITI'),
                "36" =>  array('name' => "The Hongkong and Shanghai Banking Corporation Limited", 'code' => 'HSBC'),
                "37" =>  array('name' => "Kiatnakin Bank", 'code' => 'KKB'),
                "38" =>  array('name' => "STANDARD CHARTERED BANK", 'code' => 'SCBT'),
                "39" =>  array('name' => "Thanachart Bank", 'code' => 'TBNK'),
                "40" =>  array('name' => "UOB BANK", 'code' => 'UOB')
            );
            $this->utils->debug_log("=======================getting corepay bank info from code: ", $bankInfo);
        }
        return $bankInfo;
    }
}