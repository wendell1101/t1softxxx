<?php
require_once dirname(__FILE__) . '/abstract_payment_api_prince.php';

/**
 *
 * prince
 *
 *
 * * 'PRINCE_PAYMENT_API', ID 5947
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://api.prince77.org/api/create
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_prince extends Abstract_payment_api_prince {

	public function getPlatformCode() {
		return PRINCE_PAYMENT_API;
	}

	public function getPrefix() {
		return 'prince';
	}

    protected function configParams(&$params, $direct_pay_extra_info) {
        $this->utils->debug_log('direct_pay_extra_info', $direct_pay_extra_info);
        if (!empty($direct_pay_extra_info)) {
            $extraInfo = json_decode($direct_pay_extra_info, true);
            if (!empty($extraInfo)) {
                $bank = array_key_exists('bank', $extraInfo) ? $extraInfo['bank'] : $extraInfo['bank_type'];

            }
        }
        $params["channel"] = self::PAYWAY_BANK;
        // $params['bank_id'] = $bank;
    }

    protected function processPaymentUrlForm($params) {
        return $this->processPaymentUrlFormPost($params);
    }

    public function getPlayerInputInfo() {
        return array(
            array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
        );
    }

}
