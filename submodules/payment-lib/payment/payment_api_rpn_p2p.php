<?php
require_once dirname(__FILE__) . '/abstract_payment_api_rpn.php';

/**
 * RPN
 *
 * * RPN_P2P_PAYMENT_API, ID: 5585
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://deposit.paylomo.net/pay.php
 * * Account: ## User ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_rpn_p2p extends Abstract_payment_api_rpn {
	const RETURN_SUCCESS_CODE = "[Success]";

	public function getPlatformCode() {
		return RPN_P2P_PAYMENT_API;
	}

	public function getPrefix() {
		return 'rpn_p2p';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
		$params['version'] = '1.1'; # fixed value
		unset($params['return_url']);
		if(!empty($direct_pay_extra_info)) {
	        $extraInfo = json_decode($direct_pay_extra_info, true);
	        if(!empty($extraInfo['field_required_card_number'])){
	        	$params['user_cardno'] = $extraInfo['field_required_card_number'];
	        }
	    }
	}

	public function getPlayerInputInfo() {
		return array(
			array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
			array('name' => 'field_required_card_number', 'type' => 'number', 'label_lang' => 'cashier.player.bank_num'),
		);
	}
}
