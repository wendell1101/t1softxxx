<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';
/**
 * AAPAY
 *
 * * AAPAY_ALIPAY_PAYMENT_API, ID: 5529
 *
 * Required Fields:
 *
 * * URL
 * * Account - ## Merchant ID ##
 * * Key - ## API Key ##
 *
 * Field Values:
 *
 * * URL: http://api.tcpay.info/diamond/html/buy_opt.html
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
abstract class Abstract_payment_api_aapay extends Abstract_payment_api {

    const PAYTYPE_ALIPAY_BANK = "alipayBank";
    const PAYTYPE_ALIPAY      = "alipay";
    const PAYTYPE_WEIXIN      = "wepay";
    const PAYTYPE_BANK        = "bank";

	const RETURN_SUCCESS_CODE = 'success';
	const RETURN_FAIL_CODE    = 'fail';
	const CALLBACK_SUCCESS    = '2';

	public function __construct($params = null) {
		parent::__construct($params);
	}

	public function generatePaymentUrlForm($orderId, $playerId, $amount, $orderDateTime, $playerPromoId = null, $enabledSecondUrl = true, $bankId = null) {
		if ($this->shouldRedirect($enabledSecondUrl)) {
			$url = $this->CI->utils->getPaymentUrl($this->getSystemInfo('second_url'), $this->getPlatformCode(), $amount, $playerId, $playerPromoId, false, $bankId, $orderId);
			$result = array('success' => true, 'type' => self::REDIRECT_TYPE_URL, 'url' => $url);
			return $result;
		}

		$this->CI->load->model('player');
		$order    = $this->CI->sale_order->getSaleOrderById($orderId);
		$player   = $this->CI->player->getPlayerById($playerId);
		$username = $player['username'];

		$params = array();
		$params['tradeNo']      = $order->secure_id;
		$params['apiKey']       = $this->getSystemInfo("account");
		$params['type']         = 'buy';
		$params['amount']       = $this->convertAmountToCurrency($amount);
		$params['userId']       = $username;
		$params['operSysType']  = 'Android';
		$params['timeStamp']    = $this->getMillisecond();
		$params['platform']     = $this->getSystemInfo("platform");
		$params['payNotifyUrl'] = $this->getNotifyUrl($orderId);
		$params['bcMerchantPublicKey'] = $this->getSystemInfo("bcMerchantPublicKey");
		$params['sign']         = $this->sign($params);
		$this->configParams($params, $order->direct_pay_extra_info);
		$this->CI->utils->debug_log("=====================abbpay generatePaymentUrlForm", $params);

		return $this->processPaymentUrlForm($params);
	}

	# Submit GET form
	protected function processPaymentUrlFormPost($params) {
		$url = $this->getSystemInfo('url');
		return array(
			'success' => true,
			'type' => self::REDIRECT_TYPE_FORM,
			'url' => $url,
			'params' => $params,
			'post' => false
		);
	}

	public function getMillisecond() {
        list($s1, $s2) = explode(' ', microtime());
        return (float)sprintf('%.0f', (floatval($s1) + floatval($s2)) * 1000);
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

        $raw_post_data = file_get_contents('php://input', 'r');
        $params = json_decode($raw_post_data, true);

        $this->CI->utils->debug_log("=====================aapay callbackFrom $source params", $params);

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
            $this->CI->sale_order->updateExternalInfo($order->id, $params['tradeNo'], '', null, null, $response_result_id);
            if ($source == 'browser') {
                $success = $this->CI->sale_order->browserCallbackSaleOrder($order->id, 'auto broswer callback ' . $this->getPlatformCode(), false);
            } elseif ($source == 'server') {
                $this->approveSaleOrder($order->id, 'auto server callback ' . $this->getPlatformCode(), false);
            }
        }

		$result['success'] = $success;
        if ($success) {
			$resultContent=[
					'status'=> self::RETURN_SUCCESS_CODE,
                ];
            $result['message'] = json_encode($resultContent);
		} else {
			$resultContent=[
                    'status'=> self::RETURN_FAIL_CODE,
                ];
            $result['return_error'] = json_encode($resultContent);
		}

        if ($source == 'browser') {
            $result['next_url'] = $this->getPlayerBackUrl();
            $result['go_success_page'] = true;
        }

        return $result;
    }

	private function checkCallbackOrder($order, $fields, &$processed) {
		# does all required fields exist?
		$requiredFields = array(
			'tradeNo', 'orderNo', 'amount', 'origin', 'quantity', 'offerOrderNo', 'type', 'userId', 'status', 'msg', 'operSysType', 'rate', 'createTime', 'endTime', 'sign'
		);
		foreach ($requiredFields as $f) {
			if (!array_key_exists($f, $fields)) {
				$this->writePaymentErrorLog("=========================aapay checkCallbackOrder missing parameter: [$f]", $fields);
				return false;
			}
		}

		# is signature authentic?
		if (!$this->validateSign($fields)) {
			$this->writePaymentErrorLog("=========================aapay checkCallbackOrder Signature Error", $fields);
			return false;
		}

		$processed = true; # processed is set to true once the signature verification pass

        if ($fields['status'] != self::CALLBACK_SUCCESS) {
        	$payStatus = $fields['status'];
            $this->writePaymentErrorLog("=========================aapay checkCallbackOrder returncode was not successful,  payStatus is [$payStatus]", $fields);
            return false;
        }

		if ($fields['tradeNo'] != $order->secure_id) {
			$this->writePaymentErrorLog("=========================aapay checkCallbackOrder Order IDs do not match, expected [$expectedOrderId]", $fields);
			return false;
		}

		if ($this->convertAmountToCurrency($fields['amount']) != $this->convertAmountToCurrency($order->amount)) {
			$this->writePaymentErrorLog("=========================aapay checkCallbackOrder amount is wrong, expected [$order->amount]", $fields);
			return false;
		}

		# everything checked ok
		return true;
	}

	public function directPay($order = null) {
		return array('success' => false); # direct pay not supported by this API
	}

	private function convertAmountToCurrency($amount) {
		return number_format($amount, 2, '.', '');
	}

	private function getNotifyUrl($orderId) {
		return parent::getCallbackUrl('/callback/process/' . $this->getPlatformCode() . '/' . $orderId);
	}

	private function getReturnUrl($orderId) {
		return parent::getCallbackUrl('/callback/browser/success/' . $this->getPlatformCode() . '/' . $orderId);
	}

	# -- signing --
	private function sign($params) {
		$signStr = $this->createSignStr($params);
		$sign = md5($signStr);
		return $sign;
	}

	private function validateSign($params) {
		return $params['sign'] == $this->sign($params);
	}

	private function createSignStr($params) {
		ksort($params);
		$signStr = '';
		foreach($params as $key => $value) {
			if($key == 'sign' || $key == 'payType'|| $key == 'origin' || empty($value)) {
				continue;
			}
			$signStr .= "$key=$value&";
		}

		return $signStr."key=".$this->getSystemInfo('key');
	}
}
