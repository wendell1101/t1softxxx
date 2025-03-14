<?php
require_once dirname(__FILE__) . '/abstract_payment_api_realpay.php';

/**
 * realpay
 *
 * * REALPAY_PAYMENT_API, ID: 5184
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://119.29.115.76/preCreate
 * * Account: ## User ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_realpay extends Abstract_payment_api_realpay {

    public function getPlatformCode() {
        return REALPAY_PAYMENT_API;
    }

    public function getPrefix() {
        return 'realpay';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {
        $params['head']['biz'] = $this->getSystemInfo('biz');
    }

    # Hide bank list dropdown
    public function getPlayerInputInfo() {
        return array(
            array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
        );
    }

    protected function processPaymentUrlForm($params) {
        return $this->processPaymentUrlFormPost($params);
    }
}