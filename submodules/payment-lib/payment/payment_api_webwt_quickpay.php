<?php
require_once dirname(__FILE__) . '/abstract_payment_api_webwt.php';

/**
 * WEBWT 瀚银
 * *
 * * WEBWT_QUICKPAY_PAYMENT_API, ID: 5319
 *
 * Required Fields:
 * * URL: http://yl.xincheng-sh.com/webwt/bankPay/orderPay.do 
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
class Payment_api_webwt_quickpay extends Abstract_payment_api_webwt {

    public function getPlatformCode() {
        return WEBWT_QUICKPAY_PAYMENT_API;
    }

    public function getPrefix() {
        return 'webwt_quickpay';
    }

	protected function configParams(&$params, $direct_pay_extra_info) {
        if(!empty($direct_pay_extra_info)) {
            $extraInfo = json_decode($direct_pay_extra_info, true);
            if(!empty($extraInfo['card_number'])){
                $params['bankCardNo'] = $extraInfo['card_number']; 
            }
        }

    }

    # Hide bank selection drop-down
    public function getPlayerInputInfo() {
        return array(
            array('name' => 'card_number', 'type' => 'number', 'label_lang' => 'cashier.player.bank_num'), 
            array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
        );
    }

    protected function processPaymentUrlForm($params) {
        return $this->processPaymentUrlFormPost($params);
    }
}
