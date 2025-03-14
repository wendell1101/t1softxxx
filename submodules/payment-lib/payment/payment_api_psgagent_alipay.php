<?php
require_once dirname(__FILE__) . '/abstract_payment_api_psgagent.php';

/**
 *
 * * PSGAGENT_ALIPAY_PAYMENT_API', ID: 5299
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
class Payment_api_psgagent_alipay extends Abstract_payment_api_psgagent {

    public function getPlatformCode() {
        return PSGAGENT_ALIPAY_PAYMENT_API;
    }

    public function getPrefix() {
        return 'psgagent_alipay';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {
        if($this->CI->utils->is_mobile()) {
            $params['bizType'] = self::BIZTYPE_ALIPAY_H5;
        }
        else {
            $params['bizType'] = self::BIZTYPE_ALIPAY;
        }
        $params['subject'] = 'Deposit';
    }

    # Hide bank selection drop-down
    public function getPlayerInputInfo() {
        return array(
            array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
        );
    }

    protected function processPaymentUrlForm($params) {
        return $this->processPaymentUrlFormRedirect($params);
    }
}
