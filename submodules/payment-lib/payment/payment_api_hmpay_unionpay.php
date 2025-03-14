<?php
require_once dirname(__FILE__) . '/abstract_payment_api_hmpay.php';

/**
 * hmpay
 *
 * * HMPAY_UNIONPAY_PAYMENT_API, ID: 5857
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://www.hmpay1.com:9578/interface/chargebank.aspx
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_hmpay_unionpay extends Abstract_payment_api_hmpay {

    public function getPlatformCode() {
        return HMPAY_UNIONPAY_PAYMENT_API;
    }

    public function getPrefix() {
        return 'hmpay_unionpay';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {
        $params['type'] = $this->getSystemInfo('type',self::ORDERTYPE_TYPE_UNIONPAY);

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
