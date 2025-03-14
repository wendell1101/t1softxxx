<?php
require_once dirname(__FILE__) . '/payment_api_flash_alipay_h5.php';

/**
 * FLASH  Flashpay
 * *
 * * FLASH_ALIPAY_H5_2_PAYMENT_API, ID: 5705
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
class Payment_api_flash_alipay_h5_2 extends Payment_api_flash_alipay_h5 {

    public function getPlatformCode() {
        return FLASH_ALIPAY_H5_2_PAYMENT_API;
    }

    public function getPrefix() {
        return 'flash_alipay_h5_2';
    }
}
