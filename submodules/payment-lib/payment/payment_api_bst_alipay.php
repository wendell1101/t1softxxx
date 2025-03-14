<?php
require_once dirname(__FILE__) . '/abstract_payment_api_bst.php';

/**
 *
 * BST
 *
 *
 * * 'BST_ALIPAY_PAYMENT_API', ID 5945
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://61.142.254.8:9090/api/v1/frontend/transactions/create
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_bst_alipay extends Abstract_payment_api_bst {

	public function getPlatformCode() {
		return BST_ALIPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'BST_ALIPAY';
	}

    protected function configParams(&$params, $direct_pay_extra_info) {

    }

    protected function processPaymentUrlForm($params) {
        return $this->processPaymentUrlFormPost($params);
    }

    # Hide bank selection drop-down
    public function getPlayerInputInfo() {
        return array(
            array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
        );
    }

}
