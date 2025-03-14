<?php
require_once dirname(__FILE__) . '/abstract_payment_api_paybus.php';

/**
 * paybus
 *
 * * PAYBUS_PAYMENT_API, ID: 6560
 *
 * Field Values:
 * * URL: https://pay2-open.kyriandev.com/payment/deposit
 * * Account: ## User ID ##
 * * Key: ## Secret Key ##
 *
 * @category Payment
 * @copyright 2013-2023 tot
 */
class Payment_api_paybus_larkpay_bank extends Abstract_payment_api_paybus {

    const CHANNEL_LARKPAY_BANK = 'larkpay.BANK';

    public function getPlatformCode() {
        return PAYBUS_LARKPAY_BANK_PAYMENT_API;
    }

    public function getPrefix() {
        return 'paybus_larkpay_bank';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {
        $bankCodeUse=$this->getSystemInfo('bankCodeUse');
        $channel_input = null;
		if(!empty($direct_pay_extra_info)) {
	        $extraInfo = json_decode($direct_pay_extra_info, true);
	        if(!empty($extraInfo['field_required_bank_code'])&&$bankCodeUse){
                $params['channel_input'] = json_decode(json_encode([
                    self::CHANNEL_LARKPAY_BANK => array(
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
                array('label' => "BANK BCA", 'value' => 'BANK_BCA'),
                array('label' => "BANK BNI", 'value' => 'BANK_BNI'),
                array('label' => "BANK BRI", 'value' => 'BANK_BRI'),
                array('label' => "BANK BSS", 'value' => 'BANK_BSS'),
                array('label' => "BANK CIMB", 'value' => 'BANK_CIMB'),
                array('label' => "BANK MANDIRI", 'value' => 'BANK_MANDIRI'),
                array('label' => "BANK MAY", 'value' => 'BANK_MAY'),
                array('label' => "BANK PERMATA", 'value' => 'BANK_PERMATA')
            );
        }
        return $banklist;
    }
    
}