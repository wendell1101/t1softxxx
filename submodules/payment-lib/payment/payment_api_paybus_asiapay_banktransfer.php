<?php
require_once dirname(__FILE__) . '/abstract_payment_api_paybus.php';

/**
 * paybus
 *
 * * PAYBUS_PAYMENT_API, ID: 6389
 *
 * Field Values:
 * * URL: https://pay2-open.kyriandev.com/payment/deposit
 * * Account: ## User ID ##
 * * Key: ## Secret Key ##
 *
 * @category Payment
 * @copyright 2013-2023 tot
 */
class Payment_api_paybus_asiapay_banktransfer extends Abstract_payment_api_paybus {

    public function getPlatformCode() {
        return PAYBUS_ASIAPAY_BANKTRANSFER_PAYMENT_API;
    }

    public function getPrefix() {
        return 'paybus_asiapay_banktransfer';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {
        $bankCodeUse=$this->getSystemInfo('bankCodeUse');
        $channel_input = null;
		if(!empty($direct_pay_extra_info)) {
	        $extraInfo = json_decode($direct_pay_extra_info, true);
            $channel_input['code']=$extraInfo['field_required_bank_code'];
	        if(!empty($extraInfo['field_required_bank_code'])&&$bankCodeUse){
                $params['channel_input'] = json_decode(json_encode([
                    self::CHANNEL_ASIAPAY_BANKTRANSFER => $channel_input
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
                array('label' => "VP BANK", 'value' => 'VP'),
                array('label' => "ACB BANK", 'value' => 'ACB'),
                array('label' => "BIDV BANK", 'value' => 'BIDV'),
                array('label' => "VIETTIN BANK", 'value' => 'VTB'),
                array('label' => "MB BANK", 'value' => 'MB'),
                array('label' => "EXIM BANK", 'value' => 'EXB'),
                array('label' => "SACOM", 'value' => 'SAC'),
                array('label' => "TECHCOM BANK", 'value' => 'TCB'),
                array('label' => "VIETCOM BANK", 'value' => 'VCB'),
                array('label' => "DONGA BANK", 'value' => 'DAB'),
                array('label' => "VIB BANK", 'value' => 'VIB'),
                array('label' => "MSB BANK", 'value' => 'MSB'),
                array('label' => "SHB BACNK", 'value' => 'SHB')                
            );
        }
        return $banklist;
    }
}