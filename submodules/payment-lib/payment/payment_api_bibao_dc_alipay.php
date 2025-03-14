<?php
require_once dirname(__FILE__) . '/abstract_payment_api_bibao_otc.php';

/**
 *
 * * BIBAO_DC_ALIPAY_PAYMENT_API', ID: 5222
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key

 * Field Values:
 * * URL: dora-elb-public
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_bibao_dc_alipay extends Abstract_payment_api_bibao_otc {

    public function getPlatformCode() {
        return BIBAO_DC_ALIPAY_PAYMENT_API;
    }

    public function getPrefix() {
        return 'bibao_dc_alipay';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {
        $params['PayMethods']  = self::PAY_METHODS_ALIPAY;
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
