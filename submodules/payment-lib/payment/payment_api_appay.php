<?php
require_once dirname(__FILE__) . '/abstract_payment_api_appay.php';
/**
 * APPAY
 *
 * * APPAY_PAYMENT_API, ID: 5925
 *
 * Required Fields:
 * * URL
 * * Account    (merchant id)
 * * Key        (md5key)
 *
 * Field Values:
 * * URL: ?
 * * Account    ## merchant id ##
 * * Key        ## md5key ##
 *
 * @see         abstract_payment_api_appay.php
 * @category    Payment
 * @copyright   2022 tot
 */
class Payment_api_appay extends Abstract_payment_api_appay {

    public function getPlatformCode() {
        return APPAY_PAYMENT_API;
    }

    public function getPrefix() {
        return 'appay';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {
        $params['scantype']   = self::PAY_SCANTYPE_DEFAULT;
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