<?php
require_once dirname(__FILE__) . '/abstract_payment_api_goopago.php';
/**
 *
 * anteepay
 *
 * * GOOPAGO_PAYMENT_API, ID: 6280
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: 
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_goopago extends Abstract_payment_api_goopago {

    public function getPlatformCode() {
        return GOOPAGO_PAYMENT_API;
    }

    public function getPrefix() {
        return 'goopago';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {
        $extraInfo = json_decode($direct_pay_extra_info, true);
        $params['payment_method'] = "br_auto";

        // Just send the "br_auto" channel.
        // if(!empty($extraInfo['payment_method'])){
        //     $params['payment_method'] = $extraInfo['payment_method'];
        // }
    }

    protected function processPaymentUrlForm($params) {

        return $this->processPaymentUrlFormPost($params);
    }

    public function getPlayerInputInfo() {
        return array(
            array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),

            // Just send the "br_auto" channel.
            // array('name' => 'payment_method', 'type' => 'list_custom', 'label_lang' => 'Channel',
            //       'list' => $this->getSystemInfo('channel')
            // ),
        );
    }
}
