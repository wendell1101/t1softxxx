<?php
require_once dirname(__FILE__) . '/abstract_payment_api_paybus.php';

/**
 * paybus
 *
 * * PAYBUS_PAYMENT_API, ID: 6396
 *
 * Field Values:
 * * URL: https://pay2-open.kyriandev.com/payment/deposit
 * * Account: ## User ID ##
 * * Key: ## Secret Key ##
 *
 * @category Payment
 * @copyright 2013-2023 tot
 */
class Payment_api_paybus_biopay_vnbank extends Abstract_payment_api_paybus {

    public function getPlatformCode() {
        return PAYBUS_BIOPAY_VNBANK_PAYMENT_API;
    }

    public function getPrefix() {
        return 'paybus_biopay_vnbank';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {
        $this->CI->utils->debug_log("=====================paybus configParams", $params);

        $backUrl=["backUrl"=>$params['callback_url']];
        unset($params['callback_url']);
        $params['channel_input'] = json_decode(json_encode([self::CHANNEL_BIOPAY_VNBANK => $backUrl]));
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