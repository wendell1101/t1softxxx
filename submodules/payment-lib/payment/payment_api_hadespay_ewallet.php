<?php
require_once dirname(__FILE__) . '/abstract_payment_api_hadespay_ewallet.php';
/**
 *
 * * HADESPAY_EWALLET_PAYMENT_API, ID: 6597
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://ib.brazil-pix.com/open-api/pay/payment
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2024 tot
 */
class Payment_api_hadespay_ewallet extends Abstract_payment_api_hadespay_ewallet {

    public function getPlatformCode() {
        return HADESPAY_EWALLET_PAYMENT_API;
    }

    public function getPrefix() {
        return 'hadespay_ewallet';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {
        $extraInfo = json_decode($direct_pay_extra_info, true);
        if(!empty($extraInfo['payment_method'])){
            $params['channel'] = $extraInfo['payment_method'];
        }
    }

    public function getPlayerInputInfo() {
        $getPlayerInputInfo =  array(
            array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
        );
        $newArray = array('name' => 'payment_method', 'type' => 'list_custom', 'label_lang' => 'Channel',
              'list' => $this->getSystemInfo('channel')
        );
        array_push($getPlayerInputInfo, $newArray);
        return $getPlayerInputInfo;
    }

    protected function processPaymentUrlForm($params) {
        return $this->processPaymentUrlFormRedirect($params);
    }
}