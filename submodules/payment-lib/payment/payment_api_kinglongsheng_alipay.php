<?php
require_once dirname(__FILE__) . '/abstract_payment_api_kinglongshengpay.php';

/**
 *
 * * KINGLONGSHENG_ALIPAY_PAYMENT_API, ID: 701
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: kinglongsheng
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_kinglongsheng_alipay extends Abstract_payment_api_kinglongshengpay {

    public function getPlatformCode() {
        return KINGLONGSHENG_ALIPAY_PAYMENT_API;
    }

    public function getPrefix() {
        return 'kinglongsheng_alipay';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {
        //$params['pc_PayType'] = self::PAYTYPE_ALIPAY;
        return null;
    }

    # Hide bank selection drop-down
    public function getPlayerInputInfo() {
        return array(
            array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
        );
    }

    protected function processPaymentUrlForm($params) {
        return $this->processPaymentUrlFormQRCode($params);
    }
}
