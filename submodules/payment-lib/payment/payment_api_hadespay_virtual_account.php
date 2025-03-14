<?php
require_once dirname(__FILE__) . '/abstract_payment_api_hadespay_virtual_account.php';
/**
 *
 * * HADESPAY_VIRTUAL_ACCOUNT_PAYMENT_API, ID: 6595
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://ib.brazil-pix.com/open-api/pay/payment
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2024 tot
 */
class Payment_api_hadespay_virtual_account extends Abstract_payment_api_hadespay_virtual_account {

    public function getPlatformCode() {
        return HADESPAY_VIRTUAL_ACCOUNT_PAYMENT_API;
    }

    public function getPrefix() {
        return 'hadespay_virtual_account';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {
        $bankCodeUse=$this->getSystemInfo('bankCodeUse');
		if(!empty($direct_pay_extra_info)) {
	        $extraInfo = json_decode($direct_pay_extra_info, true);
	        if(!empty($extraInfo['field_required_bank_code'])&&$bankCodeUse){
	        	$params['bankCode'] = $extraInfo['field_required_bank_code'];
	        }
		}
    }

    public function getPlayerInputInfo() {
        $getPlayerInputInfo =  array(
            array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
        );
        if($this->getSystemInfo('bankCodeUse')){
			$newArray = array('name' => 'field_required_bank_code', 'type' => 'list', 'label_lang' => 'pay.bank','list' => $this->getBankList());
			array_push($getPlayerInputInfo, $newArray);
		}

        return $getPlayerInputInfo;
    }

    protected function processPaymentUrlForm($params) {
        return $this->processPaymentUrlFormRedirect($params);
    }

    protected function getBankListInfoFallback() {
        $banklist=$this->getSystemInfo('bank_list');
        if(empty($banklist)){
            $banklist=array(
                array('label' => "Mandiri", 'value' => '008'),
                array('label' => "BCA", 'value' => '014'),
                array('label' => "BRI", 'value' => '002'),
                array('label' => "BNI", 'value' => '009'),
                array('label' => "Permata", 'value' => '013'),
                array('label' => "Danamon", 'value' => '011'),
                array('label' => "CIMB", 'value' => '022'),
                array('label' => "Sahabat Sampoerna", 'value' => '153')
            );
        }
        return $banklist;
    }
}