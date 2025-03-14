<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * MONEYPAY
 *
 * * 'MONEYPAY_BANKCARD_PAYMENT_API', ID 887
 *
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
abstract class Abstract_payment_api_moneypay extends Abstract_payment_api {

	const RETURN_SUCCESS_CODE = '1';
    const RETURN_FAILED_CODE = '2';
    const REQUEST_SUCCESS = '0';
	const PAY_RESULT_SUCCESS = '1';

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

		$params['clientNumber'] = $this->getSystemInfo("account");
		$params['userAccount'] = $this->getAccName($playerId, $order->direct_pay_extra_info);
		$params['userName'] = $this->getBankName($playerId, $order->direct_pay_extra_info);
		$params['last4digies'] = $this->getBankAcc($playerId, $order->direct_pay_extra_info);
		$params['amount'] = $this->convertAmountToCurrency($amount); //元
		$params['clientOrderNumber'] = $order->secure_id;

		$this->configParams($params, $order->direct_pay_extra_info);
		$params['verifyText'] = $this->sign($params);
		$this->CI->utils->debug_log("=====================moneypay generatePaymentUrlForm", $params);

		return $this->processPaymentUrlForm($params);
	}

	# Submit POST form
	protected function processPaymentUrlFormPost($params) {
	    $url = $this->getSystemInfo('url');
	    $params_encode = json_encode($params);
		return array(
			'success' => true,
			'type' => self::REDIRECT_TYPE_FORM,
			'url' => $url,
			'params' => $params_encode,
			'post' => true,
		);

	}

	 public function handlePaymentFormResponse($params) {
        $url = $this->getSystemInfo('url');
		$response = $this->submitPostForm($this->getSystemInfo('url'), $params, true, $params['clientOrderNumber']);
        $response = json_decode($response, true);

        $data['Name'] = @$response['bankInfo']['name'];
        $data['Bank'] = @$response['bankInfo']['bankName'];
        $data['branch'] = @$response['bankInfo']['branch'];
        $data['Account'] = @$response['bankInfo']['account'];

        $collection_text_transfer = $this->getSystemInfo('collection_text_transfer');
        $is_not_display_recharge_instructions = $this->getSystemInfo('is_not_display_recharge_instructions');

		if(!empty($response['bankInfo']) && ($response['error'] == self::REQUEST_SUCCESS)) {
            return array(
                'success' => true,
                'type' => self::REDIRECT_TYPE_STATIC,
                'data' => $data,
                'collection_text_transfer' => $collection_text_transfer,
                'is_not_display_recharge_instructions' => $is_not_display_recharge_instructions
            );
        }else {
            if(!empty($response['error'])) {
            	$realStateDesc = $this->getMappingErrorMsg($response['error']);
                $msg = $response['error'];
            }
            return array(
                'success' => false,
                'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
                'message' => 'Code:'.$msg.", Desc: ".$realStateDesc
            );
        }
    }

    private function getMappingErrorMsg($state) {
		$msg = "";
		switch ($state) {
			case '0':
				$msg = "没有错误";
				break;

			case '1':
				$msg = "商户号不正确";
				break;

			case '2':
				$msg = "客户号不正确";
				break;

			case '3':
				$msg = "输入金额不正确";
				break;

			case '4':
				$msg = "验正文本不正确";
				break;

			case '5':
				$msg = "银行账号格式不正确";
				break;

			case '6':
				$msg = "登录密码不正确";
				break;

			case '7':
				$msg = "商户资金不足";
				break;

			case '8':
				$msg = "系统错误";
				break;
			case '9':
				$msg = "交易单号不正确";
				break;

			default:
				$msg = "找不到交易单号";
				break;
		}
		return $msg;
	}

    # Callback URI: /callback/fixed_process/<payment_id>
	public function getOrderIdFromParameters($params) {
		$raw_post_data = file_get_contents('php://input', 'r');
        $flds = json_decode($raw_post_data, true);

		$orderId = null;
		//for fixed return url on browser
		if (isset($flds['clientOrderNumber'])) {
			$secure_id = $flds['clientOrderNumber'];

			$this->CI->load->model(array('sale_order'));
			$order = $this->CI->sale_order->getSaleOrderBySecureId($secure_id);

			if(!empty($order)){
               	$orderId = $order->id;
            }else{
            	$this->utils->debug_log('====================================dsdfpay callbackOrder order_id is empty when getOrderIdFromParameters', $flds);
            }
		}
		else {
			$this->utils->debug_log('====================================dsdfpay callbackOrder cannot get any order_id when getOrderIdFromParameters', $flds);
		}
		return $orderId;
	}

	public function callbackstatus(){

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
        $this->CI->utils->debug_log("=====================moneypay callbackFrom $source params", $params);

        if($source == 'server'){
			$raw_post_data = file_get_contents('php://input', 'r');
	        $flds = json_decode($raw_post_data, true);
	        $params = array_merge( $params, $flds );
            $this->CI->utils->debug_log('=======================moneypay raw_post', $params);
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
            $this->CI->sale_order->updateExternalInfo($order->id, $params['outOrderNo'], null, null, null, $response_result_id);
			if ($source == 'browser') {
				$success = $this->CI->sale_order->browserCallbackSaleOrder($order->id, 'auto broswer callback ' . $this->getPlatformCode(), false);
			} elseif ($source == 'server') {
				$this->approveSaleOrder($order->id, 'auto server callback ' . $this->getPlatformCode(), false);
			}
		}

		$result['success'] = $success;
		if ($success) {
			$resultContent=[
                    'clientNumber'=>$params['clientNumber'],
                    'tranId'=>$params['tranId'],
                    'isRecieved'=>1,
                    'verifyText'=>$this->callbacksign($params)
                ];
            $result['message'] = json_encode($resultContent,true);
		} else {
			$resultContent=[
					'clientNumber'=>$params['clientNumber'],
                    'company_order_no'=>$params['tranId'],
                    'isRecieved'=>2,
                    'verifyText'=>$this->callbacksign($params)
                ];
            $result['message'] = json_encode($resultContent,true);
		}

		return $result;
	}

	/**
	 * detail: Validates whether the callback from API contains valid info and matches with the order
	 *
	 * @return boolean
	 */

	private function checkCallbackOrder($order, $fields, &$processed = false) {
		$requiredFields = array(
			'clientNumber', 'tranId', 'clientOrderNumber', 'c_amount', 'state', 'verifyText'
		);

		foreach ($requiredFields as $f) {
			if (!array_key_exists($f, $fields)) {
				$this->writePaymentErrorLog("=====================moneypay missing parameter: [$f]", $fields);
				return false;
			}
		}

		if ($fields['state'] != self::PAY_RESULT_SUCCESS) {
			$payStatus = $fields['state'];
			$this->writePaymentErrorLog("=====================moneypay Payment was not successful, payStatus is [$payStatus]", $fields);
			return false;
		}

		if ( $this->convertAmountToCurrency($order->amount) != floatval( $fields['c_amount'] )
		) {
			$this->writePaymentErrorLog("=====================moneypay Payment amounts do not match, expected [$order->amount]", $fields);
			return false;
		}

        if ($fields['clientOrderNumber'] != $order->secure_id) {
            $this->writePaymentErrorLog("========================moneypay checkCallbackOrder order IDs do not match, expected [$order->secure_id]", $fields);
            return false;
        }

        # is signature authentic?
        if (!$this->verifySignature($fields)) {
            $this->writePaymentErrorLog('=======================moneypay checkCallbackOrder verify signature Error', $fields);
            return false;
        }

		$processed = true; # processed is set to true once the signature verification pass

		# everything checked ok
		return true;
	}

	public function directPay($order = null) {
		return array('success' => false); # direct pay not supported by this API
	}

	public function getBankListInfoFallback() {
		return array(
            array('label' => '工商银行', 'value' => 'ICBC'),
            array('label' => '农业银行', 'value' => 'ABC'),
            array('label' => '中国银行', 'value' => 'BOC'),
            array('label' => '建设银行', 'value' => 'CCB'),
            // array('label' => '农业发展银行', 'value' => '203'),
            array('label' => '交通银行', 'value' => 'COMM'),
            array('label' => '中信银行', 'value' => 'CITIC'),
            array('label' => '光大银行', 'value' => 'CEB'),
            array('label' => '华夏银行', 'value' => 'HXBANK'),
            array('label' => '民生银行', 'value' => 'CMBC'),
            array('label' => '广发银行', 'value' => 'GDB'),
            array('label' => '平安银行', 'value' => 'SPABANK'),
            array('label' => '招商银行', 'value' => 'CMB'),
            array('label' => '兴业银行', 'value' => 'CIB'),
            array('label' => '浦发银行', 'value' => 'SPDB'),
            array('label' => '北京银行', 'value' => 'BJBANK'),
            array('label' => '渤海银行', 'value' => 'BHB'),
            array('label' => '上海银行', 'value' => 'SHBANK'),
            array('label' => '邮储银行', 'value' => 'PSBC'),
            array('label' => '广州市商业银行', 'value' => 'GRCBANK')
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
	public function sign($params) {
        $data_keys = array(
			'clientNumber','userAccount','userName','last4digies','amount'
		);
		$signStr = '';
		foreach($data_keys as  $value) {
			$signStr .= $params[$value];
		}
		$signStr .= $this->getSystemInfo('key');
        $sign=md5($signStr);
		return $sign;
	}

    public function verifySignature($data) {
	   	$data_keys = array(
			'clientNumber','clientOrderNumber','tranId','state'
		);
		$callback_sign = $data['verifyText'];

		$signStr = '';
		foreach($data_keys as $value) {

			$signStr .= $data[$value];
		}
		$signStr .= $this->getSystemInfo('key');
        $sign = strtoupper(md5($signStr));
        return $sign == $callback_sign;
    }

    public function callbacksign($params) {
	    $data_keys = array(

			'clientNumber','tranId','isReceived'

		);
		$signStr = '';
		foreach($data_keys as  $value) {

			$signStr .= $params[$value];
		}
		$signStr .= $this->getSystemInfo('key');
        $sign=md5($signStr);
		return $sign;
    }

    public function getPlayerDetails($playerId) {
        $this->CI->load->model(array('player_model'));
        $player = $this->CI->player_model->getPlayerDetails($playerId);
        return $player;
    }
}
