<?php
require_once dirname(__FILE__) . '/abstract_payment_api_beamo.php';
/**
 * beamo_USDc
 *
 * * beamo_USDc_WITHDRAWAL_PAYMENT_API, ID: 5883
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 * * URL
 *
 * Field Values:
 * * Account: ## APP ID ##
 * * Key: ## APP KEY ##
 * * Secret: ## APP SECRET ##
 * * URL: https://www.beamo_usdc.com/oss/wallet/cre_propay_order
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_beamo_withdrawal extends Abstract_payment_api_beamo {
    protected function configParams(&$params, $direct_pay_extra_info){}
    protected function processPaymentUrlForm($params){}

    public function getPlatformCode() {
        return BEAMO_WITHDRAWAL_PAYMENT_API;
    }

    public function getPrefix() {
        return 'beamo_withdrawal';
    }

    public function getWithdrawUrl() {
        return $this->getSystemInfo('url');
    }

    public function submitWithdrawRequest($bank, $accNum, $name, $amount, $transId) {
        $result = array('success' => false, 'message' => 'payment failed');

        if(!$this->isAllowWithdraw()) {
            $result['message'] = lang("Withdraw not allowed with this API");
            return $result;
        }

        $data = $this->CI->playerbankdetails->getBankCodeByBankType($bank);
        if(strpos(strtoupper($data['bank_code']), 'USDC') === false){
            $this->utils->error_log("========================beamo_usdc submitWithdrawRequest bank whose bank code is not supported by beamo_usdc");
            return array('success' => false, 'message' => 'Bank not supported by beamo_usdc');
        }

        $url                             = $this->getWithdrawUrl();
        $params                          = $this->getWithdrawParams($bank, $accNum, $name, $amount, $transId);
        list($content, $response_result) = $this->processCurl($params, $url, $transId, true);
        $decodedResult = $this->decodeResult($content);
        $decodedResult['response_result'] = $response_result;

        $this->CI->utils->debug_log('======================================beamo_usdc submitWithdrawRequest url: ', $url );
        $this->CI->utils->debug_log('======================================beamo_usdc submitWithdrawRequest params: ', $params);
        $this->CI->utils->debug_log('======================================beamo_usdc submitWithdrawRequest content', $content);
        $this->CI->utils->debug_log('======================================beamo_usdc submitWithdrawRequest decoded Result', $decodedResult);

        return $decodedResult;
    }

    public function decodeResult($resultString, $queryAPI = false) {
        if(is_array($resultString) && isset($resultString['lock']) && isset($resultString['msg'])) {
            $result = array('success' => false, 'message' => $resultString['msg'], 'lock' => true);
            return $result;
        }

        $result = json_decode($resultString, true);
        $this->utils->debug_log("=========================beamo_usdc json_decode result", $result);
        if(!empty($result) && isset($result)){
            if(isset($result['status']) && !empty($result['status']) && $result['status'] == self::RESULT_PRODUCT_PAGE_SUCCESS_CODE){
                return array('success' => true, 'message' => 'beamo_usdc request successful.');
            }else if(!empty($result['errorMessage'])){
                $errorMsg = $result['errorMessage'];
                return array('success' => false, 'message' => $errorMsg);
            }
            else{
                return array('success' => false, 'message' => 'beamo withdrawal exist errors');
            }
        }else{
            return array('success' => false, 'message' => 'beamo withdrawal exist errors');
        }
    }

    public function getWithdrawParams($bank, $accNum, $name, $amount, $transId) {
        $this->CI->load->model(array('wallet_model', 'player_model', 'playerbankdetails'));
        $playerBankDetails = $this->CI->playerbankdetails->getBankDetailsByBankAccount($bank, $accNum);
        $this->utils->debug_log("==================beamo_usdc withdraw get playerBankDetails using [$bank] + [$accNum]", $playerBankDetails);

        if(!empty($playerBankDetails)){
            $playerId = $playerBankDetails['playerId'];
            $playerDetails = $this->CI->player_model->getPlayerDetails($playerId);
            $email = (isset($playerDetails[0]) && !empty($playerDetails[0]['email']))? $playerDetails[0]['email']  : 'none';
        }

        # look up bank code
        $wallet_account_id = $this->CI->wallet_model->getWalletaccountIdByTransactionCode($transId);
        $cryptolOrder = $this->CI->wallet_model->getCryptoWithdrawalOrderById($wallet_account_id);

        if(empty($cryptolOrder) && !is_array($cryptolOrder)){
            $this->utils->debug_log("=========================beamo_usdc crypto order not exists", $transId);
            return array('success' => false, 'message' => 'crypto order not exists');
        }

        $params = array();
        $params['network']                = $this->getSystemInfo("network");
        $params['token']                  = $this->getSystemInfo("crypto_currency_code");
        $params['amount']                 = $this->convertAmountToCurrencyForWithdraw($cryptolOrder['transfered_crypto']);
        $params['clientReferenceId']      = $transId;
        $params['recipientEmail']         = $email;

        return $params;
    }

    protected function convertAmountToCurrencyForWithdraw($amount) {
        $convert_multiplier = $this->getSystemInfo('convert_multiplier', 1);
        return number_format($amount * $convert_multiplier, 0, '.', '');
    }
}