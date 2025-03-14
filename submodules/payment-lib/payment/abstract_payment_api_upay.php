<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * upay
 *
 * * 'UPAY_USDT_PAYMENT_API', ID 6182
 * * 'UPAY_USDT_WITHDRAWAL_PAYMENT_API', ID: 6183
 * *
 * Required Fields:
 *
 * * URL
 * * Account - Merchant ID
 * * Key - Secret key
 * * Extra Info
 *  http://16.162.87.159:1235
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
abstract class Abstract_payment_api_upay extends Abstract_payment_api {

   	const PROTOCOL_TYPE_TRC = 'trc20';
   	const PROTOCOL_TYPE_ERC = 'erc20';
	const RETURN_SUCCESS_CODE = 0;
	// const CALLBACK_SUCCESS_CODE = 1;
    const RETURN_SUCCESS = 'SUCCESS';
    const RETURN_FAILED = 'ERROR';

	public function __construct($params = null) {
        parent::__construct($params);
        $this->_custom_curl_header = array('Content-Type:application/json');
    }

	# Implement these for specific pay type
	protected abstract function configParams(&$params, $direct_pay_extra_info);
	protected abstract function processPaymentUrlForm($params);

	public function generatePaymentUrlForm($orderId, $playerId, $amount, $orderDateTime, $playerPromoId = null, $enabledSecondUrl = true, $bankId = null) {
		if ($this->shouldRedirect($enabledSecondUrl)) {
			$url = $this->CI->utils->getPaymentUrl($this->getSystemInfo('second_url'), $this->getPlatformCode(), $amount, $playerId, $playerPromoId, false, $bankId, $orderId);
			$result = array('success' => true, 'type' => self::REDIRECT_TYPE_URL, 'url' => $url);
			return $result;
		}

		$order = $this->CI->sale_order->getSaleOrderById($orderId);
		$today = $this->CI->utils->getTodayForMysql();
		$userID = $this->getSystemInfo('upay_userid', strtotime($today));
        $fix_amount = 0;

		$this->CI->utils->debug_log("=====================upay generatePaymentUrlForm today", $today, 'userID', $userID);

        $params = array();
		$params['userID']      = (int)$userID;
        $params['orderNo']     = $order->secure_id;
        $params['html']        = false;
        $params['noRedirect']  = true;
        $params['lang'] 	   = 'en';
        $params['callbackURL'] = $this->getNotifyUrl($orderId);
        $this->configParams($params, $order->direct_pay_extra_info);

        $submit['sign'] = $this->sign($params);
        $submit['data'] = $params;

        $end_time = $order->player_submit_datetime;
        $check_time = $this->getSystemInfo('check_time', '20');
        $start_time = date('Y-m-d H:i:s',strtotime("-".$check_time." minutes" ,strtotime($end_time)));
        $this->CI->utils->debug_log("=======================upay",'start_time', $start_time, 'end_time', $end_time,'check_time', $check_time);

		do {
			$fix_amount = $this->fixAmountToCurrency($amount);
			$order_by_amt = $this->CI->sale_order->getSaleOrderByPlayerIdAndAmount($playerId, sale_order::STATUS_PROCESSING, $fix_amount, $start_time, $end_time);
			$this->CI->utils->debug_log("=======================upay getSaleOrderByPlayerIdAndAmount order_by_amt", $order_by_amt);
		} while (!empty($order_by_amt));

		if ($fix_amount > 0) {
			$notes = $order->notes . "request diff amount to " . $fix_amount .", origin was: " . $amount;
			$this->CI->sale_order->fixOrderAmount($order->id, $fix_amount, $notes);
			$this->CI->utils->debug_log('=======================upay fixAmountToCurrency Payment diff amount', $notes);
		}

        $submit['amount'] = $fix_amount;
        $submit['orderId'] = $orderId;

		$this->CI->utils->debug_log("=====================upay generatePaymentUrlForm", $submit);
		return $this->processPaymentUrlForm($submit);
    }

    protected function fixAmountToCurrency($amount) {
		$fix_amount = 0;
        $fix_amount_range = $this->getSystemInfo('fix_amount_range');
        if(!empty($fix_amount_range)){
            $rand_mode = $this->getSystemInfo('amount_rand_mode') ? $this->getSystemInfo('amount_rand_mode') : "plus";
            $random_decimal_places = $this->getSystemInfo('random_decimal_places') ? $this->getSystemInfo('random_decimal_places') : 3;

            if(!is_array($fix_amount_range) && !array_key_exists('min', $fix_amount_range) && !array_key_exists('max', $fix_amount_range)){
                $fix_amount_range = array('min' => 0.001, 'max'=> 0.999);

            }
            $min = $fix_amount_range['min'];
            $max = $fix_amount_range['max'];

            $rand_amount = $min+lcg_value()*(abs($max-$min));

            if($rand_mode == 'minus'){
                $fix_amount = $amount - number_format($rand_amount, $random_decimal_places, '.', '');
            }
            else if($rand_mode == 'plus'){
                $fix_amount = $amount + number_format($rand_amount, $random_decimal_places, '.', '');
            }

            $this->CI->utils->debug_log("=======================upay fixAmountToCurrency rand_amount",$rand_amount, 'amount', $amount, 'fix_amount', $fix_amount);

            return $fix_amount;
        }else{
            return $amount;
        }
    }

	# Submit URL form
	protected function handlePaymentFormResponse($params) {
		$amount = $params['amount'];
		$orderId = $params['orderId'];

        $handle['app_id']  = $this->getSystemInfo('app_id');
        $handle['base']    = $this->getSystemInfo('base');
        $handle['symbols'] = $this->getSystemInfo('symbols');
        $handle['protocol'] = $params['data']['protocol'];

		$rate = 0;
        // $cryptoRate = json_decode($this->getCryptoRate($handle), true);
        // $this->CI->utils->debug_log('=====================upay cryptoRate', $cryptoRate);
        // if (!empty($cryptoRate)) {
        //     $rate = $cryptoRate['rates']['BRL'];
        // }

        list($crypto, $cryptoRate) = $this->CI->utils->convertCryptoCurrency($amount, 'USDT', 'USDT', 'deposit');
        $this->CI->utils->debug_log('=====================upay cryptoRate', $cryptoRate);
        if (!empty($cryptoRate)) {
            $rate = $cryptoRate;
        }

	    $crypto = doubleval(number_format($amount/$rate, 6, '.', ''));
	    $params['data']['amount'] = $crypto;
		unset($params['orderId']);
		unset($params['amount']);
        $response = $this->submitPostForm($this->getSystemInfo('url'), $params, true, $params['data']['orderNo']);
        $response = json_decode($response, true);
        $this->CI->utils->debug_log('=====================upay processPaymentUrlFormURL response json to array', $response);
		$msg = lang('Invalidate API response');

        $collection_text_transfer = '';
        $collection_text = $this->getSystemInfo("collection_text_transfer", array(''));
        if(is_array($collection_text)){
            $collection_text_transfer = $collection_text;
        }
        $is_not_display_recharge_instructions = $this->getSystemInfo('is_not_display_recharge_instructions');

		if(!empty($response['data']) && ($response['code'] == self::RETURN_SUCCESS_CODE)) {
			//create Crypto Order,
			$currency = $handle['base'].$handle['protocol'];
			$address = $response['data'];
			$this->CI->sale_order->createCryptoDepositOrder($orderId, $crypto, $rate, null, null, $this->getSystemInfo('currency'));
			$deposit_notes = 'Wallet address: '.$address.' | '. $currency  .' Real Rate: '.$rate . '|' . 'Tether USD '.'('.$handle['protocol'].'): ' . $crypto;
			$this->utils->debug_log('=====================upay deposit_notes', $deposit_notes);
			$this->CI->sale_order->updateExternalInfo($orderId, $address);
			$this->CI->sale_order->appendNotes($orderId, $deposit_notes);

			$data = array();
			$data['Sent to Address'] = $address;
			$data['Tether USD '.'('.$handle['protocol'].')'] = $crypto;
			$data['Rate'] = '1 '. $handle['base']. ' ' . $handle['protocol'] .' = ' . $rate . ' ' .$handle['symbols'];
			$data['Address qrcode'] = '<img src="' . QRCODEPATH . urlencode($address). '" width="200" />';

		    return array(
                'success' => true,
                'type' => self::REDIRECT_TYPE_STATIC,
                'data' => $data,
                'collection_text_transfer' => $collection_text_transfer,
                'is_not_display_recharge_instructions' => $is_not_display_recharge_instructions
            );
        }else {
            if(!empty($response['code'])) {
                $msg = json_encode($response);
            }
            return array(
                'success' => false,
                'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
                'message' => $msg
            );
        }
    }

    public function getCryptoRate($params){
        #https://openexchangerates.org/api/latest.json?app_id=e0a5f19955a541fa859d1869fe86fa6a&base=USD&symbols=BRL
        $crypto_url = $this->getSystemInfo('crypto_rate_url');
        $data['app_id']  = $params['app_id'];
        $data['base']    = $params['base'];
        $data['symbols'] = $params['symbols'];
        return $this->submitGetForm($crypto_url, $data, true, null);
    }

    public function getCryptoCalculate($params){
        #https://openexchangerates.org/api/convert/100/USD/BRL?app_id=e0a5f19955a541fa859d1869fe86fa6a&prettyprint=false' \
        $crypto_url = $this->getSystemInfo('crypto_convert_url').'/'. $params['amount'] .'/'. $params['base'] .'/' . $params['symbols'];
        $data['app_id'] = $params['app_id'];
        $data['prettyprint'] = false;
        return $this->submitGetForm($crypto_url, $data, true, null);
    }

	/**
	 * detail: This will be called when the payment is async, API server calls our callback page,
	 * When that happens, we perform verifications and necessary database updates to mark the payment as successful
	 *
	 * @param int $orderId order id
	 * @param array $params
	 * @return array
	 */
	public function callbackFromServer($orderId, $params) {
		$response_result_id = parent::callbackFromServer($orderId, $params);
		return $this->callbackFrom('server', $orderId, $params, $response_result_id);
	}

	/**
	 * detail: This will be called when user redirects back to our page from payment API
	 *
	 * @param int $orderId order id
	 * @param array $params
	 * @return array
	 */
	public function callbackFromBrowser($orderId, $params) {
		$response_result_id = parent::callbackFromBrowser($orderId, $params);
		return $this->callbackFrom('browser', $orderId, $params, $response_result_id);
	}

	# $source can be 'server' or 'browser'
	private function callbackFrom($source, $orderId, $params, $response_result_id) {
        $result = array('success' => false, 'next_url' => null, 'message' => lang('error.payment.failed'));
        $order = $this->CI->sale_order->getSaleOrderById($orderId);
        $processed = false;

        if(empty($params)){
			$raw_post_data = file_get_contents('php://input', 'r');
			$params = json_decode($raw_post_data, true);
        }
        if($source == 'server'){
            $this->CI->utils->debug_log('=======================upay callbackFromServer server callbackFrom', $params);
            if (!$order || !$this->checkCallbackOrder($order, $params, $processed)) {
                return $result;
            }
        }

		# Update order payment status and balance
		$success=true;

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
			'', '', # no info available
			null, null, $response_result_id);
			if ($source == 'browser') {
				$success = $this->CI->sale_order->browserCallbackSaleOrder($order->id, 'auto broswer callback ' . $this->getPlatformCode(), false);
			} elseif ($source == 'server') {
				$this->approveSaleOrder($order->id, 'auto server callback ' . $this->getPlatformCode(), false);
			}
		}

		$result['success'] = $success;
		if ($success) {
			$result['json_result'] = ['code' => self::RETURN_SUCCESS_CODE, 'msg' => self::RETURN_SUCCESS];
		} else {
			$result['return_error'] = $processed ? self::RETURN_SUCCESS : self::RETURN_FAILED;
		}

		if ($source == 'browser') {
			$result['next_url'] = $this->getPlayerBackUrl();
			$result['go_success_page'] = true;
		}

		return $result;
	}

	/**
	 * detail: Validates whether the callback from API contains valid info and matches with the order
	 *
	 * @return boolean
	 */

	private function checkCallbackOrder($order, $fields, &$processed = false) {
		$requiredFields = array('OrderNo','amount','sign');

		$checkFields = $fields['data'];
		$checkFields['OrderNo'] = $fields['extend']['OrderNo'];
		$checkFields['sign'] = $fields['sign'];
		foreach ($requiredFields as $f) {
			if (!array_key_exists($f, $checkFields)) {
                $this->writePaymentErrorLog("=====================upay missing parameter: [$f]", $checkFields);
				return false;
			}
		}
		# is signature authentic?
		if (!$this->validateSign($fields)) {
			$this->writePaymentErrorLog('=====================upay checkCallbackOrder Signature Error', $fields);
			return false;
		}

		$cryptoDetails = $this->CI->sale_order->getCryptoDepositOrderBySaleOrderId($order->id);
        $crypto = $cryptoDetails->received_crypto;
        // $currency = $cryptoDetails->crypto_currency;

        if ($fields['data']['amount'] != $crypto) {
            $this->writePaymentErrorLog("=====================upay checkCallbackOrder received crypto is wrong, expected [$crypto]", $fields);
            return false;
        }

		if ($fields['extend']['OrderNo'] != $order->secure_id) {
            $this->writePaymentErrorLog("========================upay checkCallbackOrder order IDs do not match, expected [$order->secure_id]", $fields);
            return false;
		}

		if (!empty($fields['data']['reason'])) {
            $this->writePaymentErrorLog("========================upay checkCallbackOrder reason is not empty, expected", $fields);
            return false;
		}

		$processed = true; # processed is set to true once the signature verification pass

		// if ($fields['data']['amount'] != $this->convertAmountToCurrency($order->amount)) {
		// 	if ($this->getSystemInfo('allow_callback_amount_diff')) {
        //         $diffAmount = abs($this->convertAmountToCurrency($order->amount) - floatval($fields['amount']));
        //         if ($diffAmount >= 1) {
        //             $this->writePaymentErrorLog("=====================upay checkCallbackOrder Payment amounts ordAmt - payAmount > 1, expected [$order->amount]", $fields, $diffAmount);
        //             return false;
        //         }

        //         $notes = $order->notes . " | callback diff amount, origin was: " . $order->amount;
        //         $this->CI->sale_order->fixOrderAmount($order->id, $fields['data']['amount'], $notes);
        //         $this->CI->utils->debug_log('=======================upay checkCallbackOrder Payment diff amount', $notes);

        //     }else {
		// 		$this->writePaymentErrorLog("=====================upay checkCallbackOrder Payment amounts do not match, expected [$order->amount]", $fields);
        //         return false;
        //     }
        // }

		# everything checked ok
		return true;
	}

	public function directPay($order = null) {
		return array('success' => false); # direct pay not supported by this API
    }

	protected function convertAmountToCurrency($amount) {
        $convert_multiplier = $this->getSystemInfo('convert_multiplier', 1);
        return number_format($amount * $convert_multiplier, 3, '.', '') ;
    }

	# -- notifyURL --
	public function getNotifyUrl($orderId) {
		return parent::getCallbackUrl('/callback/process/' . $this->getPlatformCode() . '/' . $orderId);
	}

    # -- returnURL --
	public function getReturnUrl($orderId) {
		return parent::getCallbackUrl('/callback/browser/success/' . $this->getPlatformCode() . '/' . $orderId);
	}

	# -- signatures --
    public function sign($params) {
		$signStr = $this->createSignStr($params);
		$sign = md5($signStr);
		$this->CI->utils->debug_log('=======================upay sign', $sign);

		return $sign;
	}

    public function createSignStr($params) {
		$signStr = '';
		ksort($params);
        foreach($params as $key => $value) {
			if( ($key == 'sign') || ($key == 'html') || ($key == 'noRedirect') || ($key == 'lang') || ($key == 'tradeKind')) {
				continue;
			}

			$signStr.=$key."=".$value."&";
		}
		$signStr .= 'key='.$this->getSystemInfo('key');

		$this->CI->utils->debug_log('=======================upay signStr', $signStr);
		return $signStr;
    }

    public function validateSign($params) {
		$signStr = $this->createSignStr($params['data']);
		$sign = md5($signStr);

		if($params['sign'] == $sign){
			return true;
		}
		else{
			return false;
		}
	}
}