<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 *
 * external api mode
 *
 *
 * Field Values:
 * * URL
 * * Key
 * * Extra Info:
 * `
 * {
 *
 * }
 * `
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
abstract class Abstract_payment_api_external_deposit extends Abstract_payment_api {

	const FIELD_PLAYER_ID='_playerId';
	const FIELD_AMOUNT='_amount';
	const FIELD_EXTERNAL_ORDER_ID='_external_order_id';

	const DEFAULT_TIMEOUT= 10 * 60; // 10 minutes

	public function __construct($params = null) {
		parent::__construct($params);

		$this->white_ip_list= $this->getSystemInfo('white_ip_list', []);
		$this->key= $this->getSystemInfo('key', null);
		$this->timeout= $this->getSystemInfo('timeout', self::DEFAULT_TIMEOUT);
	}

	/**
	 * don't use this
	 *
	 */
	public function generatePaymentUrlForm($orderId, $playerId, $amount, $orderDateTime, $playerPromoId = null, $enabledSecondUrl = true, $bankId = null) {

		return $this->returnUnimplemented();

	}

	public function validateWhiteIp($ip){
		return empty($this->white_ip_list) || in_array($ip, $this->white_ip_list);
	}

	/**
	 *
	 * validate callback parameters
	 *
	 * @param  array $params
	 * @return array ['success'=>, 'processed_info'=>]
	 */
	abstract public function validateCallbackParameters($params);

	/**
	 * validate sign from post parameters
	 * @param  int $orderId
	 * @param  array $params
	 * @return boolean
	 */
	abstract public function validateSign($params);

	// public function callbackFromServer($orderId, $params){
	// 	return parent::callbackFromServer($orderId, $params);
	// }

	## This will be called when user redirects back to our page from payment API
	// public function callbackFromBrowser($orderId, $params) {
	// 	return $this->returnUnimplemented();
	// }

	public function directPay($order = null) {
		return $this->returnUnimplemented();
	}

	# Config in extra_info will overwrite this one
	public function getBankListInfoFallback() {
		return [];
	}

}
