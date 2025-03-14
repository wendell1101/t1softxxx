<?php
require_once dirname(__FILE__) . '/payment_api_onewallet_qrpay.php';

/**
 * ONEWALLET
 *
 * * ONEWALLET_QRPAY_PAYMENT_API, ID: 5683
 * * ONEWALLET_QRPAY_2_PAYMENT_API, ID: 5684
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
class Payment_api_onewallet_qrpay_2 extends Payment_api_onewallet_qrpay {

    public function getPlatformCode() {
        return ONEWALLET_QRPAY_2_PAYMENT_API;
    }

    public function getPrefix() {
        return 'onewallet_qrpay_2';
    }
}
