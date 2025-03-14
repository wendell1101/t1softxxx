<?php
require_once dirname(__FILE__) . '/abstract_payment_api_gm.php';

/**
 * GMStone
 * http://www.gmstoneft.com
 *
 * * GM_JDPAY_PAYMENT_API, ID: 614
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://www.master-egg.cn/GateWay/ReceiveBank.aspx
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_gm_jdpay extends Abstract_payment_api_gm {

    public function getPlatformCode() {
        return GM_JDPAY_PAYMENT_API;
    }

    public function getPrefix() {
        return 'gm_jdpay';
    }

    public function getBankType($direct_pay_extra_info) {
        return parent::BANK_TYPE_JDPAY;
    }

    protected function configParams(&$params, $direct_pay_extra_info) {
        $this->utils->debug_log('direct_pay_extra_info', $direct_pay_extra_info);
    }

    # Hide bank list dropdown
    public function getPlayerInputInfo() {
        return array(
            array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
        );
    }

}
