<?php
require_once dirname(__FILE__) . '/abstract_payment_api_nopay.php';
/**
 * NOPAY
 *
 * * NOPAY_USDC_PAYMENT_API, ID: 6333
 *
 * Required Fields:
 * * Account
 * * URL
 * * Extra Info
 *
 * Field Values:
 * * Account: ## Merchant ID ##
 * * URL: 
 * * Extra Info:
 * > {
 * >    "protocol": TRC20/ERC20,
 * > }
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_nopay_usdc extends Abstract_payment_api_nopay {

    public function getPlatformCode() {
        return NOPAY_USDC_PAYMENT_API;
    }

    public function getPrefix() {
        return 'nopay_usdc';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {
        $params['coin'] = self::COIN_USDC;
        $params['protocol'] = $this->getSystemInfo("protocol", "TRC20");

        if(!empty($direct_pay_extra_info)) {
	        $extraInfo = json_decode($direct_pay_extra_info, true);
	        if(!empty($extraInfo['field_required_protocol'])){
	        	$params['protocol'] = $extraInfo['field_required_protocol'];
	        }
	    }
    }

    public function getPlayerInputInfo() {
        return array(
            array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
            array('name' => 'field_required_protocol', 'type' => 'list', 'label_lang' => 'protocol','list' => $this->getBankList())
        );
    }

    protected function processPaymentUrlForm($params) {
        return $this->processPaymentUrlFormPost($params);
    }

    protected function getBankListInfoFallback() {
		return array(
            array('label' => 'ERC20', 'value' => 'ERC20'),
			array('label' => 'TRC20', 'value' => 'TRC20')
        );
    }
}
