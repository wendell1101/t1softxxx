<?php
require_once dirname(__FILE__) . '/abstract_payment_api_paybus.php';

/**
 * paybus
 *
 * * PAYBUS_PAYMENT_API, ID: 6562
 *
 * Field Values:
 * * URL: https://pay2-open.kyriandev.com/payment/deposit
 * * Account: ## User ID ##
 * * Key: ## Secret Key ##
 *
 * @category Payment
 * @copyright 2013-2023 tot
 */
class Payment_api_paybus_larkpay_ewallet extends Abstract_payment_api_paybus {

    const CHANNEL_LARKPAY_EWALLET = 'larkpay.EWALLET';

    public function getPlatformCode() {
        return PAYBUS_LARKPAY_EWALLET_PAYMENT_API;
    }

    public function getPrefix() {
        return 'paybus_larkpay_ewallet';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {
        $bankCodeUse=$this->getSystemInfo('bankCodeUse');
        $channel_input = null;
		if(!empty($direct_pay_extra_info)) {
	        $extraInfo = json_decode($direct_pay_extra_info, true);
	        if(!empty($extraInfo['field_required_bank_code'])&&$bankCodeUse){
                $params['channel_input'] = json_decode(json_encode([
                    self::CHANNEL_LARKPAY_EWALLET => array(
                        "returnUrl" => $this->getSystemInfo('returnUrl'),
                        "wayCode"   => $extraInfo['field_required_bank_code']
                    )
                ]));
	        }
		}
    }

    # Hide bank list dropdown
    public function getPlayerInputInfo() {
        $getPlayerInputInfo= array(
			array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
	   	);
		if($this->getSystemInfo('bankCodeUse')){
			$newArray = array('name' => 'field_required_bank_code', 'type' => 'list', 'label_lang' => 'pay.bank','list' => $this->getBankList());
			array_push($getPlayerInputInfo, $newArray);			
		}
        return $getPlayerInputInfo;
    }

    protected function processPaymentUrlForm($params) {
        return $this->processPaymentUrlFormPost($params);
    }

    protected function getBankListInfoFallback() {
        $banklist=$this->getSystemInfo('bank_list');
        if(empty($banklist)){
            $banklist=array(
                array('label' => "EWALLET DANA", 'value' => 'EWALLET_DANA'),
                array('label' => "EWALLET GOPAY", 'value' => 'EWALLET_GOPAY'),
                array('label' => "EWALLET LINKAJA", 'value' => 'EWALLET_LINKAJA'),
                array('label' => "EWALLET OVO", 'value' => 'EWALLET_OVO'),
                array('label' => "EWALLET SHOPEEPAY", 'value' => 'EWALLET_SHOPEEPAY')
            );
        }
        return $banklist;
    }
    
}