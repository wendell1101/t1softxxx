<?php
require_once dirname(__FILE__) . '/abstract_payment_api_smartpay.php';
/**
 * SMARTPAY
 *
 * * SMARTPAY_PAYMENT_API, ID: 5645
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
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_smartpay extends Abstract_payment_api_smartpay {

    public function getPlatformCode() {
        return SMARTPAY_PAYMENT_API;
    }

    public function getPrefix() {
        return 'smartpay';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {
        $this->utils->debug_log('direct_pay_extra_info', $direct_pay_extra_info);

        if (!empty($direct_pay_extra_info)) {
            $extraInfo = json_decode($direct_pay_extra_info, true);
            if(!empty($extraInfo['player_bank_list'])){
                $memberBank = explode('-',$extraInfo['player_bank_list']);
                $params['memberBank'] = $memberBank[0];
                $params['memberAccount'] = $memberBank[1];
                $this->utils->debug_log('memberBank', $memberBank);
            }
            if(!empty($extraInfo['collection_bank'])){
                $collectionBank = explode('-',$extraInfo['collection_bank']);
                $params['collectionBank'] = $collectionBank[0];
                $params['collectionAccount'] = $collectionBank[1];
            }
        }
    }

    public function getPlayerInputInfo() {
        return array(
            array('name' => 'player_bank_list', 'type' => 'player_bank_list', 'select_lang'=>'Please Select Bank' ,'label_lang' => 'pay.acctnumber' ,'list' => $this->getPlayerBank()),
            array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09') ,
            array('name' => 'collection_bank', 'type' => 'list', 'label_lang' => 'cashier.smartpay.collection_bank' ,'list' => $this->getSystemInfo('Bank-Account')),
            );
    }

    protected function processPaymentUrlForm($params) {
        return $this->processPaymentUrlFormForRedirect($params);
    }
}