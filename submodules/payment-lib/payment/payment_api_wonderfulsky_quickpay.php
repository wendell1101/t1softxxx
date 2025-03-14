<?php
require_once dirname(__FILE__) . '/abstract_payment_api_wonderfulsky.php';
/**
 * WONDERFULSKY 天空付
 *
 * * WONDERFULSKY_QUICKPAY_PAYMENT_API, ID: 998
 *
 * Required Fields:
 * * Account
 * * URL
 * * Extra Info
 *
 * Field Values:
 * * Account: ## Merchant ID ##
 * * URL: https://api.wonderfulsky.com.cn/service
 * * Extra Info:
 * > {
 * >    "wonderfulsky_priv_key": "## Private Key ##",
 * >    "wonderfulsky_pub_key": "## Public Key ##"
 * > }
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_wonderfulsky_quickpay extends Abstract_payment_api_wonderfulsky {

    public function getPlatformCode() {
        return WONDERFULSKY_QUICKPAY_PAYMENT_API;
    }

    public function getPrefix() {
        return 'wonderfulsky_quickpay';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {
        $params['channel'] = self::CHANNEL_QUICKPAY;

    }

    public function getPlayerInputInfo() {
        return array(
            array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
        );
    }

    protected function processPaymentUrlForm($params) {
        if($this->getSystemInfo('is_redirect', false)){
            return $this->processPaymentUrlFormRedirect($params);
        }
        return $this->processPaymentUrlFormPost($params);
    }
}
