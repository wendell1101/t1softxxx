<?php
require_once dirname(__FILE__) . '/abstract_payment_api_hbepay.php';

/** 
 *
 * HBEPAY 汇宝
 * 
 * 
 * * 'HBEPAY_UNIONPAY_PAYMENT_API', ID 5308
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
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_hbepay_unionpay extends Abstract_payment_api_hbepay {

	public function getPlatformCode() {
		return HBEPAY_UNIONPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'hbepay_unionpay';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {

        $content = array(
            'service' => 'S1010',
            'out_trade_no' => $params['out_trade_no'],
            'amount' => $params['amount'] ,
            'china_bank_scan_type' => 'china_bank_scan_sm',
            'subject' => 'Deposit',
            'sub_body' => 'deposit',
            'notify_url' => $params['notify_url'],
        );
        
        $params['content'] = $this->encrypt($content);
        $params['sign_type'] = 'SHA1WITHRSA';
        $params['sign'] = $this->RSAsign($content);

	}

	protected function processPaymentUrlForm($params) {

		return $this->processPaymentUrlFormQRCode($params);
	}

	public function getPlayerInputInfo() {
        
        return array(
             array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
        );
    }


    
}
