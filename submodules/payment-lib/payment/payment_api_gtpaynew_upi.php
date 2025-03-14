<?php
require_once dirname(__FILE__) . '/abstract_payment_api_gtpaynew.php';
/**
 * gtpaynew
 *
 * * GTPAYNEW_UPI_PAYMENT_API, ID: 5881
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://interface.grummy.com/api/pay/apply
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_gtpaynew_upi extends Abstract_payment_api_gtpaynew {

    public function getPlatformCode() {
        return GTPAYNEW_UPI_PAYMENT_API;
    }

    public function getPrefix() {
        return 'gtpaynew_upi';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {
        $params['payType'] = $this->getSystemInfo('payType',self::PAYTYPE_UPI);
    }

    public function getPlayerInputInfo() {
        return array(
            array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
        );
    }

    protected function processPaymentUrlForm($params) {
        return $this->processPaymentUrlFormRedirect($params);
    }
}