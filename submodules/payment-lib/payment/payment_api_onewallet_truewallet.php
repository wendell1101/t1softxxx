<?php
require_once dirname(__FILE__) . '/abstract_payment_api_onewallet.php';

/**
 * ONEWALLET
 *
 * * ONEWALLET_TRUEWALLET_PAYMENT_API, ID: 5962
 *
 * Required Fields:
 * * URL
 * * Key
 *
 * Field Values:
 * * URL: https://api-tg.100scrop.tech/11-dca/SH/sendPay
 * * Key: ## Secret Key ##
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_onewallet_truewallet extends Abstract_payment_api_onewallet {

    public function getPlatformCode() {
        return ONEWALLET_TRUEWALLET_PAYMENT_API;
    }

    public function getPrefix() {
        return 'onewallet_truewallet';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {
        $params['order_type'] = self::ORDER_TYPE_TRUEWALLET;
        $this->utils->debug_log('direct_pay_extra_info', $direct_pay_extra_info);
        if (!empty($direct_pay_extra_info)) {
            $extraInfo = json_decode($direct_pay_extra_info, true);
            if (!empty($extraInfo)) {
                $params['bank_code'] = array_key_exists('bank', $extraInfo) ? $extraInfo['bank'] : $extraInfo['bank_type'];
            }
        }
    }

    protected function processPaymentUrlForm($params) {
        return $this->processPaymentUrlFormRedirect($params);
    }

    public function getBankListInfoFallback() {
        return array(
            array('label' => 'Bangkok Bank', 'value' => 'BBL'),
            array('label' => 'Kasikorn Bank', 'value' => 'KB'),
            array('label' => 'Siam Commercial Bank', 'value' => 'SCB'),
            array('label' => 'Krung Thai Bank', 'value' => 'KTB'),
            array('label' => 'Bank of Ayudhya', 'value' => 'BAY'),
        );
    }
}
