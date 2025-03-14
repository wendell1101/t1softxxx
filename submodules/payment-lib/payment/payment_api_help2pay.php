<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';
/**
 * Help2Pay
 *
 * HELP2PAY_PAYMENT_API, ID: 135
 *
 * Required Fields:
 *
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 *
 * * URL: https://api.racethewind.net/MerchantTransfer
 * * Account: ## Merchant ID ##
 * * Key: ## Security Code ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_help2pay extends Abstract_payment_api {

	const ORDER_STATUS_SUCCESS  = '000';
	const ORDER_STATUS_FAILED   = '001';
	const ORDER_STATUS_REJECTED = '007';
	const ORDER_STATUS_CANCELED = '008';
	const ORDER_STATUS_APPROVED = '006';
	const ORDER_STATUS_PENDING 	= '009';

	const RETURN_SUCCESS_CODE = 'success';

	public function __construct($params = null) {
		parent::__construct($params);
	}

	public function getPlatformCode() {
		return HELP2PAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'help2pay';
	}

	public function getBankCode($direct_pay_extra_info){
        $this->utils->debug_log('direct_pay_extra_info', $direct_pay_extra_info);
        if (!empty($direct_pay_extra_info)) {
            $extraInfo = json_decode($direct_pay_extra_info, true);
            if (!empty($extraInfo)) {
                return array_key_exists('bank', $extraInfo) ? $extraInfo['bank'] : $extraInfo['bank_type'];
            }
        }
	}

	public function generatePaymentUrlForm($orderId, $playerId, $amount, $orderDateTime, $playerPromoId = null, $enabledSecondUrl = true, $bankId = null) {
		if ($this->shouldRedirect($enabledSecondUrl)) {
			$url = $this->CI->utils->getPaymentUrl($this->getSystemInfo('second_url'), $this->getPlatformCode(), $amount, $playerId, $playerPromoId, false, $bankId, $orderId);
			$result = array('success' => true, 'type' => self::REDIRECT_TYPE_URL, 'url' => $url);
			return $result;
		}

		$order = $this->CI->sale_order->getSaleOrderById($orderId);

		$params = array();
		$params['Merchant']  = $this->getSystemInfo('account');
		$params['Bank']      = $this->getBankCode($order->direct_pay_extra_info);
		$params['Currency']  = $this->getSystemInfo('currency','IDR');
		$params['Customer']  = $playerId;
		$params['Reference'] = $order->secure_id;
		$params['Amount']    = $this->convertAmountToCurrency($amount);
		$params['Datetime']  = $orderDateTime->format('Y-m-d h:i:sA');
		$params['timestamp'] = $orderDateTime->format('YmdHis');
		$params['FrontURI']  = $this->getReturnUrl($orderId);
		$params['BackURI']   = $this->getNotifyUrl($orderId);
		$params['Language']  = $this->getSystemInfo('language','en-us');
		$params['ClientIP']  = $this->getClientIP();
		$params['Key']       = $this->sign($params);
        unset($params['timestamp']);

		return array(
			'success' => true,
			'type' => self::REDIRECT_TYPE_FORM,
			'url' => $this->getSystemInfo('url'),
			'params' => $params,
			'post' => true,
		);
	}

	public function callbackFromServer($orderId, $params) {
		$response_result_id = parent::callbackFromServer($orderId, $params);
		return $this->callbackFrom('server', $orderId, $params, $response_result_id);
	}

	public function callbackFromBrowser($orderId, $params) {
		$response_result_id = parent::callbackFromBrowser($orderId, $params);
		return $this->callbackFrom('browser', $orderId, $params, $response_result_id);
	}

	private function callbackFrom($source, $orderId, $params, $response_result_id) {
		$result = array('success' => false, 'next_url' => null, 'message' => lang('error.payment.failed'));
		$order = $this->CI->sale_order->getSaleOrderById($orderId);
		$processed = false;

		if($source == 'server'){
			if (!$order || !$this->checkCallbackOrder($order, $params, $processed)) {
				return $result;
			}
		}

		# Update order payment status and balance
		$success = true;

		# Update player balance based on order status
		# if it's STATUS_SETTLED or STATUS_BROWSER_CALLBACK, put log, and ignore
		$orderStatus = $this->CI->sale_order->getSaleOrderStatusById($orderId);
		if ($orderStatus == Sale_order::STATUS_BROWSER_CALLBACK || $orderStatus == Sale_order::STATUS_SETTLED) {
			$this->CI->utils->debug_log('callbackFrom' . ucfirst($source) . ', already get callback for order:' . $order->id, $params);
			if ($source == 'server' && $order->status == Sale_order::STATUS_BROWSER_CALLBACK) {
				$this->CI->sale_order->setStatusToSettled($orderId);
			}
		} else {
			# update player balance
			$this->CI->sale_order->updateExternalInfo($order->id,
				$params['ID'], '', # help2pay deposit ID
				null, null, $response_result_id);
			if ($source == 'browser') {
				$success = $this->CI->sale_order->browserCallbackSaleOrder($order->id, 'auto broswer callback ' . $this->getPlatformCode(), false);
			} elseif ($source == 'server') {
				#redirect to success/fail page according to return params
				if($params['Status'] == self::ORDER_STATUS_FAILED || $params['Status'] == self::ORDER_STATUS_REJECTED || $params['Status'] == self::ORDER_STATUS_CANCELED ){
					$this->CI->sale_order->declineSaleOrder($order->id, 'auto server callback declined ' . $this->getPlatformCode(), false);
				}
				else if($params['Status'] == self::ORDER_STATUS_SUCCESS || $params['Status'] == self::ORDER_STATUS_APPROVED){
					$this->approveSaleOrder($order->id, 'auto server callback ' . $this->getPlatformCode(), false);
				}else if ($params['Status'] == self::ORDER_STATUS_PENDING) {
					$saleOrder = $this->CI->sale_order->getSaleOrderWithPlayerById($order->id);
					$this->CI->sale_order->appendNotes($saleOrder->id,'help2pay reply order pending');
				}
			}
		}

		$result['success'] = $success;
		if ($success) {
			$result['message'] = self::RETURN_SUCCESS_CODE;
		} else {
			$result['return_error'] = 'Error';
		}

		if ($source == 'browser') {
			#redirect to success/fail page according to return params
			if($params['Status'] == self::ORDER_STATUS_FAILED || $params['Status'] == self::ORDER_STATUS_REJECTED || $params['Status'] == self::ORDER_STATUS_CANCELED){
				$result['success'] = false;
				$result['message'] = lang('error.payment.failed');
			}
			$result['next_url'] = $this->getPlayerBackUrl();
		}

		return $result;
	}


	private function checkCallbackOrder($order, $fields, &$processed = false) {
		$requiredFields = array(
			'ID', 'Amount', 'Currency', 'Customer', 'Merchant', 'Reference', 'Status', 'Key'
		);
        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("=====================help2pay checkCallbackOrder Missing parameter: [$f]", $fields);
                return false;
            }
        }

		# is signature authentic?
        if (!$this->verifySignature($fields)) {
            $this->writePaymentErrorLog('=====================help2pay checkCallbackOrder Signature Error', $fields);
            return false;
        }

		$processed = true; # processed is set to true once the signature verification pass

        $amount = $this->convertAmountToCurrency($order->amount);
        if ($amount != floatval($fields['Amount'])) {
            $this->writePaymentErrorLog("======================help2pay checkCallbackOrder Payment amount is wrong, expected [$amount]", $fields);
            return false;
        }

        if ($fields['Reference'] != $order->secure_id) {
            $this->writePaymentErrorLog("======================help2pay checkCallbackOrder order IDs do not match, expected [$order->secure_id]", $fields);
            return false;
        }

		# everything checked ok
		return true;
	}

	# Config in extra_info will overwrite this one
	public function getBankListInfoFallback() {
		$currency  = $this->getSystemInfo('currency','IDR');
		switch ($currency) {
			case 'IDR':
				return array(
					array('label' => 'Bank Central Asia', 'value' => 'BCA'),
					array('label' => 'Bank Negara Indonesia', 'value' => 'BNI'),
					array('label' => 'Bank Rakyat Indonesia', 'value' => 'BRI'),
					array('label' => 'Mandiri Bank', 'value' => 'MDR'),
					array('label' => 'CIMB Niaga', 'value' => 'CIMBN'),
				);
				break;
			case 'THB':
				return array(
					array('label' => lang('_json: {"1": "Karsikorn Bank (K-Bank)" , "2": "Karsikorn Bank (K-Bank)", "6": "ธนาคารกสิกร"}'), 'value' => 'KKR'),
					array('label' => lang('_json: {"1": "Bangkok Bank" , "2": "Bangkok Bank", "6": "ธนาคารกรุงเทพ"}'), 'value' => 'BBL'),
					array('label' => lang('_json: {"1": "Siam Commercial Bank" , "2": "Siam Commercial Bank", "6": "ธนาคารไทยพาณิชย์"}'), 'value' => 'SCB'),
					array('label' => lang('_json: {"1": "Krung Thai Bank" , "2": "Krung Thai Bank", "6": "ธนาคารกรุงไทย"}'), 'value' => 'KTB'),
					array('label' => lang('_json: {"1": "Bank of Ayudhya (Krungsri)" , "2": "Bank of Ayudhya (Krungsri)", "6": "ธนาคารกรุงศรีอยุธยา"}'), 'value' => 'BOA'),
					array('label' => lang('_json: {"1": "Government Savings Bank" , "2": "Government Savings Bank", "6": "ธนาคารออมสิน"}'), 'value' => 'GSB'),
					array('label' => lang('_json: {"1": "TMB Bank Public Company Limited" , "2": "TMB Bank Public Company Limited", "6": "ธนาคารทหารไทย"}'), 'value' => 'TMB'),
					array('label' => lang('_json: {"1": "CIMB Thai" , "2": "CIMB Thai", "6": "ธนาคาร ซีไอเอ็มบี ไทย"}'), 'value' => 'CIMBT'),
					array('label' => lang('_json: {"1": "Kiatnakin Bank" , "2": "Kiatnakin Bank", "6": "ธนาคารเกียรตินาคิน"}'), 'value' => 'KNK'),
				);
				break;
			case 'MYR':
				return array(
					array('label' => 'Maybank Berhad', 'value' => 'MBB'),
					array('label' => 'Public Bank Berhad', 'value' => 'PBB'),
					array('label' => 'CIMB Bank Berhad', 'value' => 'CIMB'),
					array('label' => 'Hong Leong Bank Berhad', 'value' => 'HLB'),
					array('label' => 'RHB Banking Group', 'value' => 'RHB'),
					array('label' => 'AmBank Group', 'value' => 'AMB'),
					array('label' => 'Bank Islam Malaysia', 'value' => 'BIMB'),
				);
				break;
			case 'VND':
				return array(
					array('label' => 'Techcombank', 'value' => 'TCB'),
					array('label' => 'Sacombank', 'value' => 'SACOM'),
					array('label' => 'Vietcombank', 'value' => 'VCB'),
					array('label' => 'Asia Commercial Bank', 'value' => 'ACB'),
					array('label' => 'DongA Bank', 'value' => 'DAB'),
					array('label' => 'Vietinbank', 'value' => 'VTB'),
					array('label' => 'Bank for Investment and Development of Vietnam', 'value' => 'BIDV'),
					array('label' => 'Eximbank Vietnam', 'value' => 'EXIM'),
				);
				break;
			case 'PHP':
				return array(
					array('label' => 'Banco de Oro', 'value' => 'BDO'),
					array('label' => 'MetroBank', 'value' => 'MTB'),
				);
				break;
			default:
				return array();
				break;
		}
	}

	public function directPay($order = null) {
		return array('success' => false); # direct pay not supported by this API
	}

	# -- Private functions --
	private function getNotifyUrl($orderId) {
		return parent::getCallbackUrl('/callback/process/' . $this->getPlatformCode() . '/' . $orderId);
	}

	private function getReturnUrl($orderId) {
		return parent::getCallbackUrl('/callback/browser/success/' . $this->getPlatformCode() . '/' . $orderId);
	}

	protected function convertAmountToCurrency($amount) {
		$convert_rate = 1;
		if($this->CI->utils->getConfig('fix_currency_conversion_rate')){
			$convert_rate = $this->CI->utils->getConfig('fix_currency_conversion_rate');
			$this->writePaymentErrorLog("======================help2pay convertAmountToCurrency fix_currency_conversion_rate", $convert_rate);
		}
		if(!empty($this->getSystemInfo('convert_multiplier'))){
			$convert_rate = $this->getSystemInfo('convert_multiplier');
		}

        return number_format($amount * $convert_rate, 2, '.', '');
	}

    # -- signatures --
	public function sign($data) {
		$signStr =
			$data['Merchant'].$data['Reference'].$data['Customer'].$data['Amount'].
			$data['Currency'].$data['timestamp'].
			$this->getSystemInfo('key').
			$data['ClientIP'];
		$sign = md5($signStr);
		$this->utils->debug_log("=====================help2pay Signing string: [$signStr], md5: [$sign]");
		return strtolower($sign);
	}

	public function signCallback($data) {
		$signStr =
			$data['Merchant'].$data['Reference'].$data['Customer'].$data['Amount'].
			$data['Currency'].$data['Status'].
			$this->getSystemInfo('key');
		$sign = md5($signStr);
		$this->utils->debug_log("=====================help2pay Signing callback string: [$signStr], md5: [$sign]");
		return strtolower($sign);
	}

	public function verifySignature($data) {
		$mySign = $this->signCallback($data);
		if (strcasecmp($mySign, $data['Key']) === 0) {
			return true;
		} else {
			return false;
		}
	}


	private $_time;

	private function getDate() {
		if(!$this->_time) {
			$this->_time = time();
		}
		return date('Y-m-d h:i:sA', $this->_time);
	}

	private function getTimestamp() {
		if(!$this->_time) {
			$this->_time = time();
		}
		return date('YmdHis', $this->_time);
	}
}
