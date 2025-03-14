<?php
require_once dirname(__FILE__) . '/abstract_payment_api_paybus.php';

/**
 * paybus
 *
 * * PAYBUS_DGMAYA_PAYMENT_API, ID: 6458
 *
 * Field Values:
 * * URL: https://pay2-open.kyriandev.com/payment/deposit
 * * Account: ## User ID ##
 * * Key: ## Secret Key ##
 *
 * @category Payment
 * @copyright 2013-2024 tot
 */
class Payment_api_paybus_dgmaya extends Abstract_payment_api_paybus {

    public function getPlatformCode() {
        return PAYBUS_DGMAYA_PAYMENT_API;
    }

    public function getPrefix() {
        return 'paybus_dgmaya';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {
        $params['channel_input'] = [
            self::CHANNEL_DGMAYA => null,
        ]; 
    }

    # Hide bank list dropdown
    public function getPlayerInputInfo() {
        return array(
            array(
                'name' => 'deposit_amount',
                'type' => 'float_amount', 
                'label_lang' => 'cashier.09'
            ),
        );
    }

    protected function processPaymentUrlForm($params) {
        return $this->processPaymentUrlFormPost($params);
    }
}