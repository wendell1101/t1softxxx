<?php
require_once dirname(__FILE__) . '/payment_api_flash_alipay.php';

/**
 * FLASH  Flashpay
 * *
 * * FLASH_ALIPAY_2_PAYMENT_API, ID: 5704
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://188.166.206.92:8080/payments/qr
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_flash_alipay_2 extends Payment_api_flash_alipay {

    public function getPlatformCode() {
        return FLASH_ALIPAY_2_PAYMENT_API;
    }

    public function getPrefix() {
        return 'flash_alipay_2';
    }
}
