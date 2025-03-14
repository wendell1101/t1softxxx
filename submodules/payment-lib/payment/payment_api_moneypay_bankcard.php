<?php
require_once dirname(__FILE__) . '/abstract_payment_api_moneypay.php';

/**
 *
 * * MONEYPAY_BANKCARD_PAYMENT_API', ID: 887
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 
 * Field Values:
 * * URL: dora-elb-public
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_moneypay_bankcard extends Abstract_payment_api_moneypay {

    public function getPlatformCode() {
        return MONEYPAY_BANKCARD_PAYMENT_API;
    }

    public function getPrefix() {
        return 'moneypay_bankcard';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {
        
    }

    public function getPlayerInputInfo() {

        $bankName = '';
        $playerId = $this->CI->authentication->getPlayerId();
        $playerDetails = $this->getPlayerDetails($playerId);
        $firstname = (isset($playerDetails[0]) && !empty($playerDetails[0]['firstName'])) ? $playerDetails[0]['firstName'] : '';
        $lastname =  (isset($playerDetails[0]) && !empty($playerDetails[0]['lastName']))  ? $playerDetails[0]['lastName']  : '';
        $bankName = $lastname.$firstname;

        return array(
            array('name' => 'player_account_name', 'type' => 'text', 'label_lang' => 'cashier.player.player_account_name','value' => $bankName),
            array('name' => 'player_account_num', 'type' => 'number', 'label_lang' => 'cashier.player.player_account_num'),
            array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
        );
    }

    public function getAccName($playerId, $direct_pay_extra_info){
        
        $playerDetails = $this->getPlayerDetails($playerId);
        $this->CI->utils->debug_log("==================================moneypay getBankName playerDetails", $playerDetails);
        $extraInfo = json_decode($direct_pay_extra_info, true);
        
        if(!empty($direct_pay_extra_info)) {
            $extraInfo = json_decode($direct_pay_extra_info, true);
            if(!empty($playerDetails[0]['username'])){  //using alipay(bank_id=30) transfer to online_bank
                $accName = $playerDetails[0]['username'];
            }
        }
        return $accName;
    }

    public function getBankAcc($playerId, $direct_pay_extra_info) {
        
        $this->utils->debug_log('direct_pay_extra_info', $direct_pay_extra_info);
        $extraInfo = json_decode($direct_pay_extra_info, true);
        $playerBankNum = $extraInfo['player_account_num'];
        $this->CI->utils->debug_log("==================================moneypay getBankAcc", $playerBankNum);
        return $playerBankNum;
    }

    public function getBankName($playerId, $direct_pay_extra_info){
        $BankName = '';
        $playerDetails = $this->getPlayerDetails($playerId);
        $this->CI->utils->debug_log("==================================moneypay getBankName playerDetails", $playerDetails);

        $firstname = (isset($playerDetails[0]) && !empty($playerDetails[0]['firstName'])) ? $playerDetails[0]['firstName'] : '';
        $lastname =  (isset($playerDetails[0]) && !empty($playerDetails[0]['lastName']))  ? $playerDetails[0]['lastName']  : '';
        $BankName = $lastname.$firstname;

        return $BankName;
    }

    protected function processPaymentUrlForm($params) {
        return $this->handlePaymentFormResponse($params);
    }
}
