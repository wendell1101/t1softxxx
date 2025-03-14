<?php
require_once dirname(__FILE__) . '/payment_api_onewallet.php';

/**
 * ONEWALLET
 *
 * * ONEWALLET_PAYMENT_API, ID: 5675
 * * ONEWALLET_2_PAYMENT_API, ID: 5682
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
class Payment_api_onewallet_2 extends Payment_api_onewallet {

    public function getPlatformCode() {
        return ONEWALLET_2_PAYMENT_API;
    }

    public function getPrefix() {
        return 'onewallet_2';
    }
}
