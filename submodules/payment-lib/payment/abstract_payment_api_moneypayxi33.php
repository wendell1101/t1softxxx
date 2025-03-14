<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * *  MONEYPAYXI33
 * *
 * * 'MONEYPAYXI33_ALIPAY_PAYMENT_API', ID 5056
 * *
 * Required Fields:
 *
 * * URL
 * * Account - Merchant ID
 * * Key - Signing key
 * * Extra Info
 *
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
abstract class Abstract_payment_api_moneypayxi33 extends Abstract_payment_api {

    const CHANNEL_CODE_ALIPAY	  = '1'; //目前只支持支付宝
    const CHANNEL_CODE_WEIXIN	  = '2'; //微信
	const CHANNEL_CODE_ONLINEBANK = '3'; //网关支付

    const REQUEST_SUCCESS = '200';
	const PAY_RESULT_SUCCESS = '00';

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

		$params['timestamp'] = date('Ymdhis');
		$params['api_version'] = '1.6';
		$params['notify_url'] = $this->getNotifyUrl($orderId);
		$params['company_id'] = $this->getSystemInfo("account");
		$params['player_id'] = $this->uuid();;
		$params['company_order_id'] = $order->secure_id;
		$params['amount_money'] = $this->convertAmountToCurrency($amount); //元
		$this->configParams($params, $order->direct_pay_extra_info);
		$params['sign'] = $this->sign($params);

		$this->CI->utils->debug_log("=====================moneypayxi33 generatePaymentUrlForm", $params);
		return $this->processPaymentUrlForm($params);
	}

    # Display QRCode get from curl
    protected function processPaymentUrlFormQRCode($params) {
    	$url = $this->getSystemInfo('url');
        $response = $this->submitPostForm($url, $params, true, $params['company_order_id']);
        $decode_data = json_decode($response,true);

        $msg = lang('Invalidate API response');
    	if(!empty($decode_data['data']['scalper_url']) && ($decode_data['code'] == self::REQUEST_SUCCESS)) {
            return array(
                'success' => true,
                'type' => self::REDIRECT_TYPE_URL,
                'url' => $decode_data['data']['scalper_url'],
            );
        }else {
            if(!empty($decode_data['msg'])) {
                $msg = $decode_data['msg'];
            }
            return array(
                'success' => false,
                'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
                'message' => $msg
            );
        }
    }

    public function uuid(){
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
        return vsprintf('%s%s-%s-%s', str_split(bin2hex($data), 4));
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
            $this->CI->utils->debug_log('=======================moneypayxi33 callbackFromServer server callbackFrom', $params);
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
            $this->CI->sale_order->updateExternalInfo($order->id, $params['company_order_id'], null, null, null, $response_result_id);
			if ($source == 'browser') {
				$success = $this->CI->sale_order->browserCallbackSaleOrder($order->id, 'auto broswer callback ' . $this->getPlatformCode(), false);
			} elseif ($source == 'server') {
				$this->approveSaleOrder($order->id, 'auto server callback ' . $this->getPlatformCode(), false);
			}
		}

		$result['success'] = $success;
		if ($success) {
			$resultContent=[
					'code'=>'200',
                    'msg'=>'handle_callback_success',
                    'data'=> null,
                ];
            $result['message'] = json_encode($resultContent);
		} else {
			$resultContent=[
                    'code'=>'-9999',
                    'msg'=>'handle_callback_error',
                    'data'=> null,
                ];
            $result['message'] = json_encode($resultContent);
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

		$requiredFields = array('timestamp', 'sign','api_version','company_id','player_id','company_order_id','trade_no','original_amount','actual_amount','operating_time');

		foreach ($requiredFields as $f) {
			if (!array_key_exists($f, $fields)) {
				$this->writePaymentErrorLog("=====================moneypayxi33 missing parameter: [$f]", $fields);
				return false;
			}
		}

        # is signature authentic?
        if (!$this->verifySignature($fields)) {
            $this->writePaymentErrorLog('=======================moneypayxi33 checkCallbackOrder verify signature Error', $fields);
            return false;
        }

        $processed = true; # processed is set to true once the signature verification pass
		if ( $this->convertAmountToCurrency($order->amount) != floatval( $fields['original_amount'] )
		) {
			$this->writePaymentErrorLog("=====================moneypayxi33 Payment amounts do not match, expected [$order->amount]", $fields);
			return false;
		}

        if ($fields['company_order_id'] != $order->secure_id) {
            $this->writePaymentErrorLog("========================moneypayxi33 checkCallbackOrder order IDs do not match, expected [$order->secure_id]", $fields);
            return false;
        }

		# everything checked ok
		return true;
	}

	public function directPay($order = null) {
		return array('success' => false); # direct pay not supported by this API
	}

	public function getBankListInfoFallback() {
		return array(
            array('label' => '中国工商银行', 'value' => 'ICBC'),
            array('label' => '中国农业银行', 'value' => 'ABC'),
            array('label' => '中国银行', 'value' => 'BOC'),
            array('label' => '中国建设银行', 'value' => 'CCB'),
            array('label' => '交通银行', 'value' => 'COMM'),
            array('label' => '中信银行', 'value' => 'CITIC'),
            array('label' => '光大银行', 'value' => 'CEB'),
            array('label' => '华夏银行', 'value' => 'HXB'),
            array('label' => '中国民生银行', 'value' => 'CMBC'),
            array('label' => '广东发展银行', 'value' => 'GBD'),
            array('label' => '深圳发展银行', 'value' => 'SDB'),
            array('label' => '平安银行', 'value' => 'SPAB'),
            array('label' => '中国招商银行', 'value' => 'CMB'),
            array('label' => '兴业银行', 'value' => 'CIB'),
            array('label' => '浦发银行', 'value' => 'SPDB'),
            array('label' => '北京银行', 'value' => 'BJB'),
            array('label' => '上海银行', 'value' => 'SHB'),
            array('label' => '中国邮政', 'value' => 'PSBC'),
		);
	}

	# -- Private functions --
	/**
	 * detail: After payment is complete, the gateway will invoke this URL asynchronously
	 *
	 * @param int $orderId
	 * @return void
	 */
	private function getNotifyUrl($orderId) {
		return parent::getCallbackUrl('/callback/process/' . $this->getPlatformCode() . '/' . $orderId);
	}

	/**
	 * detail: After payment is complete, the gateway will send redirect back to this URL
	 *
	 * @param int $orderId
	 * @return void
	 */
	private function getReturnUrl($orderId) {
		return parent::getCallbackUrl('/callback/browser/success/' . $this->getPlatformCode() . '/' . $orderId);
	}

	/**
	 * detail: Format the amount value for the API
	 *
	 * @param float $amount
	 * @return float
	 */
	protected function convertAmountToCurrency($amount) {
		return number_format($amount, 2, '.', '');
	}

	# -- private helper functions --

	/**
	 * detail: getting the signature
	 *
	 * @param array $data
	 * @return	string
	 */
	public function sign($params) {
		$signStr = $this->createSignStr($params);
        $sign=strtoupper(md5($signStr));
		return $sign;
	}

    public function verifySignature($data) {
	    $callback_sign = $data['sign'];
        $signStr =  $this->createSignStr($data);
        $sign=strtoupper(md5($signStr));
        return (strcasecmp($sign, $callback_sign) !== 0)?false:true;
    }

    private function createSignStr($params) {
    	ksort($params);
       	$signStr='';
		foreach ($params as $key => $value) {
			if(is_null($value) || empty($value) || $key == 'sign'){
				continue;
			}
			$signStr .= $key."=".$value."&";
		}
		$signStr .= 'api_Key='. $this->getSystemInfo('key');
		return $signStr;
	}
}
