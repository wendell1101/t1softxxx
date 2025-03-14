<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 *
 * * CUSTOMIZED_DEPOSIT_PAGE_PAYMENT_API, ID: 646
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: juxin
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_customized_deposit_page extends Abstract_payment_api {

    public function __construct($params = null) {
        parent::__construct($params);
    }

	public function getPlatformCode() {
		return CUSTOMIZED_DEPOSIT_PAGE_PAYMENT_API;
	}

	public function getPrefix() {
		return 'customized_deposit_page';
	}

    public function getName() {
        return 'CUSTOMIZED_DEPOSIT_PAGE';
    }

    public function generatePaymentUrlForm($orderId = null, $playerId = null, $amount = null, $orderDateTime = null, $playerPromoId = null, $enabledSecondUrl = true, $bankId = null) {
        return $this->returnUnimplemented();
    }

    public function directPay($order) {
        //no direct pay
        return array('success' => false);
    }

}
