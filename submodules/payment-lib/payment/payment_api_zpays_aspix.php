<?php
require_once dirname(__FILE__) . '/abstract_payment_api_zpays.php';
/**
 *
 * * ZPAYS_PAYMENT_API, ID: 6199
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://api.zm-pay.com/api/pay/create_order
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_zpays_aspix extends Abstract_payment_api_zpays {
    public function getPlatformCode() {
        return ZPAYS_ASPIX_PAYMENT_API;
    }

    public function getPrefix() {
        return 'zpays_aspix';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {
        $params['productId'] =  $this->getSystemInfo("productId", self::CHANNEL_TYPE_ASPIX);
    }

    public function getPlayerInputInfo() {
        return array(
            array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
        );
    }

    protected function processPaymentUrlForm($params) {
        return $this->processPaymentUrlFormQRCode($params);
    }
}