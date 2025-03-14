<?php
require_once dirname(__FILE__) . '/abstract_payment_api_scratchcard.php';

/**
 * SCRATCHCARD
 * *
 * * SCRATCHCARD_PAYMENT_API, ID: 5322
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://27.72.146.73:8080/api/CardCallBack/CreateCardRequestV2
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_scratchcard extends Abstract_payment_api_scratchcard {

	public function getPlatformCode() {
		return SCRATCHCARD_PAYMENT_API;
	}

	public function getPrefix() {
		return 'scratchcard';
	}

    protected function configParams(&$params, $direct_pay_extra_info) {
        if(!empty($direct_pay_extra_info)) {
            $extraInfo = json_decode($direct_pay_extra_info, true);
            if(!empty($extraInfo['cardNumber'])){
                $params['cardNumber'] = $extraInfo['cardNumber'];
            }
            if(!empty($extraInfo['serialNumber'])){
                $params['serialNumber'] = $extraInfo['serialNumber'];
            }
            if(!empty($extraInfo['provider'])){
                $params['provider'] = $extraInfo['provider'];
            }
            if(!empty($extraInfo['provider'])){
                $params['provider'] = $extraInfo['provider'];
            }
            if(!empty($extraInfo['cardValue'])){
                $params['cardValue'] = $extraInfo['cardValue'];
            }
        }
    }

    public function getPlayerInputInfo() {
        return array(
            array('name' => 'provider', 'type' => 'list', 'label_lang' => 'cashier.scratchcard.provider',
                'list' => ['VTT'=>'Viettel', 'VNP'=>'Vinaphone', 'VMS'=> 'MobiFone']
            ),
            array('name' => 'cardValue', 'type' => 'list', 'label_lang' => 'cashier.scratchcard.cardValue',
                'list' => ['100000'=>'100000', '200000'=>'200000', '500000'=> '500000']),
            array('name' => 'deposit_amount', 'type' => 'hidden', 'value' => ''),
            array('name' => 'cardNumber', 'type' => 'number', 'label_lang' => 'cashier.scratchcard.cardNumber'),
            array('name' => 'serialNumber', 'type' => 'number', 'label_lang' => 'cashier.scratchcard.serialNumber'),
        );
    }
}