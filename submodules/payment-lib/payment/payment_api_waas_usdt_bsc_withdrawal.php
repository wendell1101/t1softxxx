<?php
require_once dirname(__FILE__) . '/payment_api_waas_usdt_withdrawal.php';
/**
 * WAAS_USDT
 *
 * * WAAS_USDT_BSC_WITHDRAWAL_PAYMENT_API, ID: 6040
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 * * URL
 *
 * Field Values:
 * * Account: ## APP ID ##
 * * Key: ## APP KEY ##
 * * Secret: ## APP SECRET ##
 * * URL: https://www.waas_usdt.com/oss/wallet/cre_propay_order
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_waas_usdt_bsc_withdrawal extends Payment_api_waas_usdt_withdrawal {
    protected function configParams(&$params, $direct_pay_extra_info){}
    protected function processPaymentUrlForm($params){}

    public function getPlatformCode() {
        return WAAS_USDT_BSC_WITHDRAWAL_PAYMENT_API;
    }

    public function getPrefix() {
        return 'waas_usdt_bsc_withdrawal';
    }
}