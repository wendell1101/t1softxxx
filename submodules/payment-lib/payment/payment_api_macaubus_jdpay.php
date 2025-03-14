<?php
require_once dirname(__FILE__) . '/abstract_payment_api_macaubus.php';

/**
 *
 * * MACAUBUS_JDPAY_PAYMENT_API', ID: 787
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
class Payment_api_macaubus_jdpay extends Abstract_payment_api_macaubus {

    public function getPlatformCode() {
        return MACAUBUS_JDPAY_PAYMENT_API;
    }

    public function getPrefix() {
        return 'macaubus_jdpay';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {
        $params['pay_code'] = self::SCANTYPE_JDPAY;
    }

    # Hide bank selection drop-down
    public function getPlayerInputInfo() {
        return array(
            array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
        );
    }

    protected function processPaymentUrlForm($params) {
        return $this->processPaymentUrlFormPost($params);
    }
}
