<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * PATEK
 *
 * * PATEK_PAYMENT_API, ID: 5715
 *
 * Required Fields:
 *
 * * URL
 * * Account - Merchant ID
 * * Key - Sha key
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
abstract class Abstract_payment_api_patek extends Abstract_payment_api {

	const PAYTYPE_QRCODE = 'promptpay';
	const RETURN_SUCCESS_CODE  = 'success';
	const ORDER_STATUS_SUCCESS = '1';

	public function __construct($params = null) {
		parent::__construct($params);
	}

	# Implement these to specify pay type
	protected abstract function configParams(&$params, $direct_pay_extra_info);
	protected abstract function processPaymentUrlForm($params);

	public function generatePaymentUrlForm($orderId, $playerId, $amount, $orderDateTime, $playerPromoId = null, $enabledSecondUrl = true, $bankId = null) {
		if ($this->shouldRedirect($enabledSecondUrl)) {
			$url = $this->CI->utils->getPaymentUrl($this->getSystemInfo('second_url'), $this->getPlatformCode(), $amount, $playerId, $playerPromoId, false, $bankId, $orderId);
			$result = array('success' => true, 'type' => self::REDIRECT_TYPE_URL, 'url' => $url);
			return $result;
		}

		$order = $this->CI->sale_order->getSaleOrderById($orderId);
		$playerDetails = $this->CI->player_model->getPlayerDetails($playerId);
		$username      = (isset($playerDetails[0]) && !empty($playerDetails[0]['username']))  	  ? $playerDetails[0]['username']	   : 'no username';

		$params = array();
		$params['amount'] = $this->convertAmountToCurrency($amount);
		$params['merchant'] = $this->getSystemInfo("account");
		$this->configParams($params, $order->direct_pay_extra_info);
		$params['outtradeno'] = $order->secure_id;
		$params['remark'] = 'deposit';
		$params['notifyurl'] = $this->getNotifyUrl($orderId);
		$params['returnurl'] = $this->getReturnUrl($orderId);
		$params['payername'] = $username;
		$params['returndataformat'] = 'clienthtml';
		$params['sign'] = $this->sign($params);

		$this->CI->utils->debug_log('======================================patek generatePaymentUrlForm: ', $params);
		return $this->processPaymentUrlForm($params);
	}

    # Submit POST form
	protected function processPaymentUrlFormPost($params) {
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

	# $source can be 'server' or 'browser'
    private function callbackFrom($source, $orderId, $params, $response_result_id) {
        $result = array('success' => false, 'next_url' => null, 'message' => lang('error.payment.failed'));
        $order = $this->CI->sale_order->getSaleOrderById($orderId);
        $processed = false;

        $this->CI->utils->debug_log("=====================patek callbackFrom $source params", $params);

        if(empty($params)){
        	$raw_post_data = file_get_contents('php://input', 'r');
            $params = json_decode($raw_post_data,true);
            $this->CI->utils->debug_log("=====================patek raw_post_data", $raw_post_data);
            $this->CI->utils->debug_log("=====================patek json_decode params", $params);
        }

        if($source == 'server' ){
            if (!$order || !$this->checkCallbackOrder($order, $params, $processed)) {
                return $result;
            }
        }

        $success = true;

        $orderStatus = $this->CI->sale_order->getSaleOrderStatusById($orderId);
        if ($orderStatus == Sale_order::STATUS_BROWSER_CALLBACK || $orderStatus == Sale_order::STATUS_SETTLED) {
            $this->CI->utils->debug_log('callbackFrom' . ucfirst($source) . ', already get callback for order:' . $order->id, $params);
            if ($source == 'server' && $order->status == Sale_order::STATUS_BROWSER_CALLBACK) {
                $this->CI->sale_order->setStatusToSettled($orderId);
            }
        } else {
            # update player balance
            $this->CI->sale_order->updateExternalInfo($order->id, $params['outtradeno'], '', null, null, $response_result_id);
            if ($source == 'browser') {
                $success = $this->CI->sale_order->browserCallbackSaleOrder($order->id, 'auto broswer callback ' . $this->getPlatformCode(), false);
            } elseif ($source == 'server') {
                $this->approveSaleOrder($order->id, 'auto server callback ' . $this->getPlatformCode(), false);
            }
        }

        $result['success'] = $success;
        if ($processed) {
            $result['message'] = self::RETURN_SUCCESS_CODE;
        } else {
            $result['return_error'] = 'Error';
        }

        if ($source == 'browser') {
            $result['next_url'] = $this->getPlayerBackUrl();
            $result['go_success_page'] = true;
        }

        return $result;
    }

    private function checkCallbackOrder($order, $fields, &$processed = false) {

        $requiredFields = array(
            'outtradeno','amount','ramount', 'status','sign'
        );

        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("=====================patek Missing parameter: [$f]", $fields);
                return false;
            }
        }

        # is signature authentic?
        if (!$this->validateSign($fields)) {
            $this->writePaymentErrorLog('=====================patek Signature Error', $fields);
            return false;
        }

        if ($fields['status'] != self::ORDER_STATUS_SUCCESS) {
            $this->writePaymentErrorLog('=====================patek Payment was not successful', $fields);
            return false;
        }

        $check_amount = $this->convertAmountToCurrency($order->amount);

        if ($fields['ramount'] != $check_amount) {
            if($this->getSystemInfo('allow_callback_amount_diff')){
                if(abs($check_amount - $fields['ramount']) < 1){
                    $this->CI->utils->debug_log("=====================patek amount not match expected [$order->amount]");
                    $notes = $order->notes . " | callback diff amount, origin was: " . $order->amount;
                    $this->CI->sale_order->fixOrderAmount($order->id, $fields['ramount'], $notes);
                }
            }
            else{
                $this->writePaymentErrorLog("=====================patek Payment amounts do not match, expected [$order->amount]", $fields);
                return false;
            }
        }

        if ($fields['outtradeno'] != $order->secure_id) {
            $this->writePaymentErrorLog("=====================patek checkCallbackOrder order IDs do not match, expected [$order->secure_id]", $fields);
            return false;
        }

        $processed = true; # processed is set to true once the signature verification pass

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

    private function createSignStr($params) {
        $signStr = '';
        ksort($params);
        foreach($params as $key => $value) {
            if( $key == 'sign') {
                continue;
            }
            $encodeValue=strtolower(urlencode($value));
            $signStr .= "$key=$encodeValue&";
        }
        $signStr .= "secret=".$this->getSystemInfo('key');
        return $signStr;
    }

    private function validateSign($params) {
        $sign = $this->sign($params);
        if($params['sign'] == $sign)
            return true;
        else
            return false;
    }
}
