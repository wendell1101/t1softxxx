<?php
require_once dirname(__FILE__) . '/abstract_payment_api_gaotongpay.php';
/**
 * GAOTONGPAY 高通/易收付
 *
 * * GAOTONGPAY_WEIXIN_H5_PAYMENT_API, ID: 5042
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://pay.ipsqs.com/PayBank.aspx
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_gaotongpay_weixin_h5 extends Abstract_payment_api_gaotongpay {

    public function getPlatformCode() {
        return GAOTONGPAY_WEIXIN_H5_PAYMENT_API;
    }

    public function getPrefix() {
        return 'gaotongpay_weixin_h5';
    }

    public function getBankType($direct_pay_extra_info){
        return $this->getSystemInfo("mobile_banktype", parent::BANK_TYPE_WEIXIN_WAP);
    }

    # Hide bank list dropdown
    public function getPlayerInputInfo() {
        return array(
            array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
        );
    }
}