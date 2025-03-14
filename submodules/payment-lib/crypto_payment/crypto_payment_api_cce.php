<?php
require_once APPPATH . '/libraries/crypto_payment/abstract_crypto_payment_api_cce.php';

/**
 *
 * * BIBAO_DC_ALIPAY_PAYMENT_API', ID: 5222
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key

 * Field Values:
 * * URL: dora-elb-public
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Crypto_payment_api_cce extends Abstract_crypto_payment_api_cce {
    public function getPrefix() {
        return 'cce';
    }
}
