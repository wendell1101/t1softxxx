<?php
require_once dirname(__FILE__) . '/abstract_payment_api_asiadragon.php';
/**
 * ASIADRAGON
 *
 * * ASIADRAGON_PAYMENT_API, ID: 5927
 *
 * Required Fields:
 * * URL
 * * Account    (merchant id)
 * * extra_info.merchant_priv_key
 * * extra_info.platform_public_key
 *
 * Field Values:
 * * URL        http://api.asia-pay8.com/api/unifiedorder
 * * Account    ## merchant id #
 * * extra_info.merchant_priv_key       ## merchant private key ##
 * * extra_info.platform_public_key     ## platform public key ##
 *
 * @see         abstract_payment_api_asiadragon.php
 * @category    Payment
 * @copyright   2022 tot
 */
class Payment_api_asiadragon extends Abstract_payment_api_asiadragon {

    public function getPlatformCode() {
        return ASIADRAGON_PAYMENT_API;
    }

    public function getPrefix() {
        return 'asiadragon';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {
        // $params['scantype']   = self::PAY_SCANTYPE_DEFAULT;
    }

    public function getPlayerInputInfo() {
        return array(
            array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
        );
    }

    protected function processPaymentUrlForm($params) {
        return $this->processPaymentUrlFormPost($params);
    }
}