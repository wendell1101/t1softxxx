<?php
require_once dirname(__FILE__) . '/abstract_payment_api_ddbill.php';

/**
 * DDBILL 多得宝
 * https://merchants.ddbill.com
 *
 * DDBILL_UNIONPAY_PAYMENT_API, ID: 609
 *
 * Required Fields:
 *
 * * URL
 * * Account - Merchant Code
 * * ExtraInfo - pub key and priv key
 *
 * Field Values:
 *
 * * URL: https://api.ddbill.com/gateway/api/scanpay
 * * Extra Info:
 * > {
 * > 	"ddbill_priv_key" : "## pem formatted private key (escaped) ##",
 * > 	"ddbill_pub_key" : "## pem formatted public key (escaped) ##",
 * > }
 *
 *
 * @category Payment
 * @copyright 2022 tot
 */
class Payment_api_ddbill_unionpay extends Abstract_payment_api_ddbill {

    public function getPlatformCode() {
        return DDBILL_UNIONPAY_PAYMENT_API;
    }

    public function getPrefix() {
        return 'ddbill_unionpay';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {
        $params['service_type'] = 'ylpay_scan';
        $params['interface_version'] = 'V3.3';
        $params['client_ip'] = $this->getClientIp();
        unset($params['input_charset']);
        unset($params['return_url']);
    }

    protected function processPaymentUrlForm($params) {
        return $this->processPaymentUrlFormQRCode($params);
    }

    # Hide bank list dropdown
    public function getPlayerInputInfo() {
        return array(
            array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
        );
    }
}
