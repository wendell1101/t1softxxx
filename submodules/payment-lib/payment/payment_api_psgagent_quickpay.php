<?php
require_once dirname(__FILE__) . '/abstract_payment_api_psgagent.php';

/**
 *
 * * PSGAGENT_QUICKPAY_PAYMENT_API', ID: 5305
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
class Payment_api_psgagent_quickpay extends Abstract_payment_api_psgagent {

    public function getPlatformCode() {
        return PSGAGENT_QUICKPAY_PAYMENT_API;
    }

    public function getPrefix() {
        return 'psgagent_quickpay';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {
        $params['bizType'] = self::BIZTYPE_WEIXIN;
        $params['flowId'] = 'Deposit';
        $params['commodityName'] = 'Deposit';

        $params['accountName'] = ''; #ex: lastname firstname
        $params['veriCode']    = '';
        $params['phoneNo']     = '';
        $params['cerdType']    = '';
        $params['cerdId']      = '';
        $params['acctNo']      = '';
        $params['cvn2']        = '';
        $params['expDate']     = '';
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
