<?php
require_once dirname(__FILE__) . '/abstract_payment_api_prince.php';

/**
 *
 * PRINCE_MOMO
 *
 *
 * * 'PRINCE_MOMO_PAYMENT_API', ID 5948
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://api.prince77.org/api/create
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_prince_momo extends Abstract_payment_api_prince {

    public function getPlatformCode() {
        return PRINCE_MOMO_PAYMENT_API;
    }

    public function getPrefix() {
        return 'prince_momo';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {
        $params['channel'] = self::PAYWAY_MOMO;
    }

    protected function processPaymentUrlForm($params) {
        return $this->processPaymentUrlFormPost($params);
    }

    # Hide bank selection drop-down
    public function getPlayerInputInfo() {
        return array(
            array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
        );
    }

}
