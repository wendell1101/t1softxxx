<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * YC888PAY
 *
 * * 'YC888PAY_PAYMENT_API', ID 6028
 * * 'YC888PAY_ALIPAY_PAYMENT_API', ID 6029
 * * 'YC888PAY_WITHDRAWAL_PAYMENT_API', ID 6030
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
abstract class Abstract_payment_api_yc888pay extends Abstract_payment_api {

    const PAYWAY_ALIPAY	   = 'bank_auto'; //支付宝
    const PAYWAY_BANK	   = 'bankpay';
	const RETURN_SUCCESS_CODE = 'success';
    const RETURN_FAILED_CODE = 'FAIL';
    const REQUEST_SUCCESS = '1';
    const ORDER_STATUS_SUCCESS = '1';

	# Implement these for specific pay type
	protected abstract function configParams(&$params, $direct_pay_extra_info);
	protected abstract function processPaymentUrlForm($params);

	public function generatePaymentUrlForm($orderId, $playerId, $amount, $orderDateTime, $playerPromoId = null, $enabledSecondUrl = true, $bankId = null) {
		if ($this->shouldRedirect($enabledSecondUrl)) {
			$url = $this->CI->utils->getPaymentUrl($this->getSystemInfo('second_url'), $this->getPlatformCode(), $amount, $playerId, $playerPromoId, false, $bankId, $orderId);
			$result = array('success' => true, 'type' => self::REDIRECT_TYPE_URL, 'url' => $url);
			return $result;
		}
		$playerDetails = $this->getPlayerDetails($playerId);
        $firstname = (isset($playerDetails[0]) && !empty($playerDetails[0]['firstName']))     ? $playerDetails[0]['firstName']     : 'no firstName';

		$order = $this->CI->sale_order->getSaleOrderById($orderId);
		$this->CI->load->model('player');
		$params['userid'] = $this->getSystemInfo("account");
		$params['orderno'] = $order->secure_id;
		$params['desc'] = 'deposit';
		$params['amount'] = $this->convertAmountToCurrency($amount); //元
		$params['notifyurl'] = $this->getNotifyUrl($orderId);
		$params['backurl'] = $this->getReturnUrl($orderId);
		$this->configParams($params, $order->direct_pay_extra_info);
		$params['acname'] = $firstname;
		$params['userip'] = $this->getClientIP();
		$params['currency'] = $this->getSystemInfo("currency");
		$params['sign'] = $this->sign($params);
		$this->CI->utils->debug_log("=====================yc888pay generatePaymentUrlForm", $params);

		return $this->processPaymentUrlForm($params);
	}

    protected function processPaymentUrlFormPost($params) {
        $response = $this->submitPostForm($this->getSystemInfo('url'), $params, false, $params['orderno']);
        $this->CI->utils->debug_log('=====================yc888pay processPaymentUrlFormURL received response', $response);
        $response = json_decode($response,true);
        $this->CI->utils->debug_log('=====================yc888pay processPaymentUrlFormURL json to array', $response);

        $msg = lang('Invalidte API response');

		if(isset($response['status']) && ($response['status'] == self::REQUEST_SUCCESS)) {
            return array(
                'success' => true,
                'type' => self::REDIRECT_TYPE_URL,
                'url' => $response['payurl']
            );
        }
        else {
            if(isset($response['error'])) {
                $msg = $response['error'];
            }
            return array(
                'success' => false,
                'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
                'message' => $msg
            );
        }
    }

	public function callbackFromServer($orderId, $params) {
		$response_result_id = parent::callbackFromServer($orderId, $params);
		return $this->callbackFrom('server', $orderId, $params, $response_result_id);
	}

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
            $this->CI->utils->debug_log('=======================yc888pay callbackFromServer server callbackFrom', $params);
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
            $this->CI->sale_order->updateExternalInfo($order->id, $params['orderno'], null, null, null, $response_result_id);
			if ($source == 'browser') {
				$success = $this->CI->sale_order->browserCallbackSaleOrder($order->id, 'auto broswer callback ' . $this->getPlatformCode(), false);
			} elseif ($source == 'server') {
				if($params['status'] == self::ORDER_STATUS_SUCCESS){
                    $this->approveSaleOrder($order->id, 'auto server callback ' . $this->getPlatformCode(), false);
                }
			}
		}

		$result['success'] = $success;
		if ($success) {
			$result['message'] = self::RETURN_SUCCESS_CODE;
		} else {
			$result['message'] = $processed ? self::RETURN_SUCCESS_CODE : self::RETURN_FAILED_CODE;
		}

		if ($source == 'browser') {
			$result['next_url'] = $this->getPlayerBackUrl();
			$result['go_success_page'] = true;
		}

		return $result;
	}

	private function checkCallbackOrder($order, $fields, &$processed = false) {

		$requiredFields = array('currency','userid','orderno', 'amount','realamount','status','sign');

		foreach ($requiredFields as $f) {
			if (!array_key_exists($f, $fields)) {
				$this->writePaymentErrorLog("=====================yc888pay missing parameter: [$f]", $fields);
				return false;
			}
		}

		if ($this->convertAmountToCurrency($order->amount) != $fields['amount']) {
			$this->writePaymentErrorLog("=====================yc888pay Payment amounts do not match, expected [$order->amount]", $fields);
			return false;
		}

        if ($fields['orderno'] != $order->secure_id) {
            $this->writePaymentErrorLog("=====================yc888pay checkCallbackOrder order IDs do not match, expected [$order->secure_id]", $fields);
            return false;
        }
        # is signature authentic?
        if (!$this->verifySignature($fields)) {
            $this->writePaymentErrorLog('=====================yc888pay checkCallbackOrder verify signature Error', $fields);
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
            array('label' => '中国工商银行', 'value' => '中国工商银行'),
            array('label' => '光大银行', 'value' => '光大银行'),
            array('label' => '兴业银行', 'value' => '兴业银行'),
            array('label' => '中国银行', 'value' => '中国银行'),
            array('label' => '民生银行', 'value' => '民生银行'),
            array('label' => '中国农业银行', 'value' => '中国农业银行'),
            array('label' => '南京银行', 'value' => '南京银行'),
            array('label' => '交通银行', 'value' => '交通银行'),
            array('label' => '渤海银行', 'value' => '渤海银行'),
            array('label' => '中信银行', 'value' => '中信银行'),
            array('label' => '平安银行', 'value' => '平安银行'),
            array('label' => '招商银行', 'value' => '招商银行'),
            array('label' => '邮储银行', 'value' => '邮储银行'),
            array('label' => '广西北部湾银行', 'value' => '广西北部湾银行'),
		);
	}

	# -- Private functions --
	private function getNotifyUrl($orderId) {
		return parent::getCallbackUrl('/callback/process/' . $this->getPlatformCode() . '/' . $orderId);
	}

	private function getReturnUrl($orderId) {
		return parent::getCallbackUrl('/callback/browser/success/' . $this->getPlatformCode() . '/' . $orderId);
	}

	protected function convertAmountToCurrency($amount) {
		return number_format($amount, 2, '.', '');
	}

	public function sign($params) {
		$data = [
			'userid' => $params["userid"],
			'orderno'=> $params["orderno"],
			'amount' => $params["amount"],
			'notifyurl' => $params["notifyurl"]
		];
		$signStr =  $this->createSignStr($data);
        $sign = md5($signStr);
		return $sign;
	}

    public function verifySignature($params) {
    	$data = [
			'currency' => $params["currency"],
			'status'=> $params["status"],
			'userid' => $params["userid"],
			'orderno' => $params["orderno"],
			'amount' => $params["amount"]
		];
        $signStr =  $this->createSignStr($data);
        $sign = md5($signStr);
        return $sign == $params['sign'];
    }

    public function createSignStr($data) {
        $signStr = '';
        foreach($data as $key => $value) {
            if($value == null || $key == 'sign') {
                continue;
            }
            $signStr .= "$value";
        }
        $signStr .= $this->getSystemInfo('key');

		return $signStr;
	}

	public function getPlayerDetails($playerId) {
        $this->CI->load->model(array('player_model'));
        $player = $this->CI->player_model->getPlayerDetails($playerId);
        return $player;
    }

}
