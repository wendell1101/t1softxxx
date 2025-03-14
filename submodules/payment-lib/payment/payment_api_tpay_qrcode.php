<?php
require_once dirname(__FILE__) . '/abstract_payment_api_tpay.php';
/**
 * TPAY
 *
 * * TPAY_PAYMENT_API_QRCODE, ID: 5726
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://api.tapexdd12.com/app/pay/pay.php
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_tpay_qrcode extends Abstract_payment_api_tpay {

	public function getPlatformCode() {
		return TPAY_QRCODE_PAYMENT_API;
	}

	public function getPrefix() {
		return 'tpay_qrcode';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {}

    public function getPlayerInputInfo() {
        $type = $this->getSystemInfo('float_amount_limit')? 'float_amount_limit' : 'float_amount' ;
        $float_amount_limit_msg = $this->getSystemInfo('float_amount_limit_msg')?$this->getSystemInfo('float_amount_limit_msg'):'請輸入上方金額';
        if($type == 'float_amount_limit'){
            return array(
                array('name' => 'deposit_amount', 'type' => $type, 'label_lang' => 'cashier.09', 'float_amount_limit' => $this->getSystemInfo('float_amount_limit'), 'float_amount_limit_msg' => $float_amount_limit_msg),
            );
        }else{
            return array(
                array('name' => 'deposit_amount', 'type' => $type, 'label_lang' => 'cashier.09'),
            );
        }
    }

}