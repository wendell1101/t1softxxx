<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * tt888
 *
 * * TT888_PAYMENT_API, ID 5966
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
abstract class Abstract_payment_api_tt888 extends Abstract_payment_api {

    const PAY_METHOD_BANK_CARD = '929';
    const RESULT_STATUS_SUCCESS = '200';
    const CALLBAK_STATUS_SUCCESS = '00';
    const RETURN_SUCCESS = 'OK';

    public function __construct($params = null) {
        parent::__construct($params);
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
		$playerDetails = $this->CI->player_model->getPlayerDetails($playerId);
		$firstname = (isset($playerDetails[0]) && !empty($playerDetails[0]['firstName'])) ? $playerDetails[0]['firstName'] : 'no firstName';
    $params = array();
    $params['pay_memberid'] = $this->getSystemInfo("account");
    $params['pay_orderid'] = $order->secure_id;
    $params['pay_applydate'] = date('Y-m-d H:i:s');
    $this->configParams($params, $order->direct_pay_extra_info);
    $params['pay_notifyurl'] = $this->getNotifyUrl($orderId);
    $params['pay_callbackurl'] = $this->getReturnUrl($orderId);
    $params['pay_amount'] = $this->convertAmountToCurrency($amount);
    $params['pay_md5sign'] = $this->sign($params);
    $params['return_type'] = 'json';
    $params['username'] = $firstname;

		$this->CI->utils->debug_log("=====================tt888 generatePaymentUrlForm", $params);

		return $this->processPaymentUrlForm($params);
  }

	protected function processPaymentUrlFormPost($params) {
        $response = $this->submitPostForm($this->getSystemInfo('url'), $params, false, $params['pay_orderid']);
        $response = json_decode($response, true);
        $this->CI->utils->debug_log('=====================tt888 processPaymentUrlFormPost response', $response);

        if(isset($response['code']) && $response['code'] == self::RESULT_STATUS_SUCCESS){
        	if(isset($response['url']) && !empty($response['url']))
            return array(
                'success' => true,
                'type' => self::REDIRECT_TYPE_URL,
                'url' => $response['url'],
            );
        }
        else if(isset($response['msg'])) {
            return array(
                'success' => false,
                'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
                'message' => $response['msg']
            );
        }
        else {
            return array(
                'success' => false,
                'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
                'message' => lang('Invalidte API response')
            );
        }
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
	private function callbackFrom($source, $orderId, $params, $response_result_id){
      $result = array('success' => false, 'next_url' => null, 'message' => lang('error.payment.failed'));
      $order = $this->CI->sale_order->getSaleOrderById($orderId);
      $processed = false;

      $this->CI->utils->debug_log("=====================dynastypay callbackFrom $source params", $params);

      if ($source == 'server') {
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
           if (isset($params['orderid'])) {
                $this->CI->sale_order->updateExternalInfo($order->id, $params['orderid'], '', null, null, $response_result_id);
           }

           if ($source == 'browser') {
                $success = $this->CI->sale_order->browserCallbackSaleOrder($order->id, 'auto broswer callback ' . $this->getPlatformCode(), false);
           } elseif ($source == 'server') {
                $this->approveSaleOrder($order->id, 'auto server callback ' . $this->getPlatformCode(), false);
           }
      }

      $result['success'] = $success;
      if ($processed) {
           $result['message'] = self::RETURN_SUCCESS;
      } else {
           $result['return_error'] = 'Error';
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

		$requiredFields = array('orderid','amount','returncode','sign');

		foreach ($requiredFields as $f) {
			if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("=====================tt888 missing parameter: [$f]", $fields);
				return false;
			}
		}
		# is signature authentic?
		if (!$this->validateSign($fields)) {
			$this->writePaymentErrorLog('=====================tt888 checkCallbackOrder Signature Error', $fields);
			return false;
		}

		$processed = true; # processed is set to true once the signature verification pass

		if ($fields['returncode'] != self::CALLBAK_STATUS_SUCCESS) {
			$this->writePaymentErrorLog("=====================tt888 Payment was not successful, status is ", $fields);
			return false;
		}

		if ($fields['amount'] != $this->convertAmountToCurrency($order->amount)) {
      $this->writePaymentErrorLog("======================tt888 checkCallbackOrder Payment amount is wrong, expected [$order->amount]", $fields);
      return false;
    }

    if ($fields['orderid'] != $order->secure_id) {
        $this->writePaymentErrorLog("========================tt888 checkCallbackOrder order IDs do not match, expected [$order->secure_id]", $fields);
        return false;
    }

		# everything checked ok
		return true;
	}

	public function directPay($order = null) {
		return array('success' => false); # direct pay not supported by this API
    }


	# -- amount --
	protected function convertAmountToCurrency($amount) {
		return number_format($amount, 2, '.', '');
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
    private function sign($params) {
		$signStr = $this->createSignStr($params);
		$sign = strtoupper(md5($signStr));

		return $sign;
	}

    private function createSignStr($params) {

      ksort($params);
  		$signStr = '';
          foreach($params as $key => $value) {
          	if( ($key == 'sign')) {
          		continue;
          	}
  			$signStr.=$key."=".$value."&";
          }
  		$signStr .= "key=".$this->getSystemInfo('key');
  		return $signStr;
    }

    public function validateSign($params) {
		ksort($params);
		$signStr = '';
		foreach($params as $key => $value) {
			if( ($key == 'sign') || (empty($value)) ) {
				continue;
			}
			$signStr .= "$key=$value&";
		}
		$signStr .= "key=".$this->getSystemInfo('key');
		$sign = strtoupper(md5($signStr));
		if($params['sign'] == $sign){
			return true;
		}
		else{

			return false;
		}
	}
}
