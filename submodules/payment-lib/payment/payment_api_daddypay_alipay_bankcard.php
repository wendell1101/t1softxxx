<?php
require_once dirname(__FILE__) . '/payment_api_daddypay_bankcard.php';

/**
 *
 * DaddyPay Alipay Bankcard 支付宝轉银行卡充值
 *
 * DADDYPAY_ALIPAY_BANKCARD_PAYMENT_API, ID: 634
 *
 * Required Fields:
 * * URL
 * * Key - signing key
 * * Extra Info
 *
 * Field Values:
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_daddypay_alipay_bankcard extends Payment_api_daddypay_bankcard {

    public function getPlatformCode() {
        return DADDYPAY_ALIPAY_BANKCARD_PAYMENT_API;
    }

    public function getPrefix() {
        return 'daddypay_alipay_bankcard';
    }

    public function getName() {
        return 'daddypay';
    }

    protected function getBankId($direct_pay_extra_info) {
        return parent::BANKID_ALIPAY_BANKCARD;
    }

    public function getPlayerInputInfo() {
        if($this->getSystemInfo("hide_bank_list")) {    // for only using alipay to online_bank
            return array(
                array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
            );
        }

        $hint = $this->getSystemInfo("player_name_hint", lang('cashier.player.name.for_alipay.hint'));
        $bankName = '';
        $playerId = $this->CI->authentication->getPlayerId();
        $playerDetails = $this->getPlayerDetails($playerId);
        $firstname = (isset($playerDetails[0]) && !empty($playerDetails[0]['firstName'])) ? $playerDetails[0]['firstName'] : '';
        $lastname =  (isset($playerDetails[0]) && !empty($playerDetails[0]['lastName']))  ? $playerDetails[0]['lastName']  : '';
        $bankName = $lastname.$firstname;
        return array(
            array('name' => 'player_name', 'type' => 'text', 'value' => $bankName, 'label_lang' => 'cashier.player.name.for_alipay', 'hint' => $hint),
            array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
        );
    }

    public function getNote($playerId, $direct_pay_extra_info){
        $note = '';
        $playerDetails = $this->getPlayerDetails($playerId);
        $this->CI->utils->debug_log("==================================daddypay getNote playerDetails", $playerDetails);

        $firstname = (isset($playerDetails[0]) && !empty($playerDetails[0]['firstName'])) ? $playerDetails[0]['firstName'] : '';
        $lastname =  (isset($playerDetails[0]) && !empty($playerDetails[0]['lastName']))  ? $playerDetails[0]['lastName']  : '';
        $note = $lastname.$firstname;

        if(!empty($direct_pay_extra_info)) {
            $extraInfo = json_decode($direct_pay_extra_info, true);
            if(!empty($extraInfo['player_name'])){  //using alipay(bank_id=30) transfer to online_bank
                $note = $extraInfo['player_name'];
            }
        }

        return $note;
    }
}
