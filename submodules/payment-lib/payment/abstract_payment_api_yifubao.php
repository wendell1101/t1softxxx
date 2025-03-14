<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * YIFUBAO 一付宝
 *
 * * 'YIFUBAO_ALIPAY_PAYMENT_API', ID 5357
 * * 'YIFUBAO_ALIPAY_H5_PAYMENT_API', ID 5358
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
abstract class Abstract_payment_api_yifubao extends Abstract_payment_api {

    const PAY_METHOD_ALIPAY= '10003';
    const PAY_METHOD_ALIPAY_H5= '10008';

    const RETURN_SUCCESS_CODE = '10000';
    const RETURN_SUCCESS = 'SUCCESS';
    const RETURN_FAILED = 'ERROR';


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

        $params = array();
        $params['merchantNo'] = $this->getSystemInfo("account");
        $params['requestNo'] = $order->secure_id;
        $params['money'] = $this->convertAmountToCurrency($amount); //分
        $params['payDate'] = date("YmdHis");
        $this->configParams($params, $order->direct_pay_extra_info);  //$params['payMethod']
		$params['pageUrl'] = $this->getReturnUrl($orderId);
        $params['backUrl'] = $this->getNotifyUrl($orderId);
		$params['remark'] = "Deposit";

        $params['sign'] = $this->sign($params);

        
    
		$this->CI->utils->debug_log("=====================yifubao generatePaymentUrlForm", $params);

		return $this->processPaymentUrlForm($params);
    }

	
	# Submit POST form
	protected function processPaymentUrlFormPost($params) {
	    $url = $this->getSystemInfo('url');
	    $this->CI->utils->debug_log("=====================yifubao processPaymentUrlFormPost URL", $url);
		return array(
			'success' => true,
			'type' => self::REDIRECT_TYPE_FORM,
			'url' => $url,
			'params' => $params,
			'post' => true,
		);
	}


    # Display QRCode get from curl
    protected function processPaymentUrlFormQRCode($params) {
    	$url = $this->getSystemInfo('url');
        $this->CI->utils->debug_log('=====================dada processPaymentUrlFormQRcode scan url',$url);

		$response = $this->submitPostForm($url, $params, false, $params['requestNo']);
		$this->CI->utils->debug_log('========================================dada processPaymentUrlFormQRcode received response', $response);
		
		$decode_data = json_decode($response,true);
        $this->CI->utils->debug_log('========================================dada processPaymentUrlFormQRcode response[1] json to array', $decode_data);
		$msg = lang('Invalidte API response');


		if(isset($decode_data['payUrl'])) {
            return array(
                'success' => true,
                'type' => self::REDIRECT_TYPE_URL,
                'url' => $decode_data['payUrl'],
            );
        }else {
            if(!empty($decode_data['msg'])){
                $msg = $decode_data['code'].":".$decode_data['msg'];
            }
            return array(
                'success' => false,
                'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
                'message' => $msg
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
	private function callbackFrom($source, $orderId, $params, $response_result_id) {
        $this->CI->utils->debug_log('=======================yifubao callbackFrom in Function callbackFrom', $params);

        $result = array('success' => false, 'next_url' => null, 'message' => lang('error.payment.failed'));
        $order = $this->CI->sale_order->getSaleOrderById($orderId);
        $processed = false;

        if($source == 'server'){
            $this->CI->utils->debug_log('=======================yifubao callbackFromServer server callbackFrom', $params);
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
			$result['message'] = self::RETURN_SUCCESS;
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
        
		$requiredFields = array('code','money','requestNo','msg','payNo');

		foreach ($requiredFields as $f) {
			if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("=====================yifubao missing parameter: [$f]", $fields);
				return false;
			}
		}
		# is signature authentic?
		if (!$this->validateSign($fields)) {
			$this->writePaymentErrorLog('=====================yifubao checkCallbackOrder Signature Error', $fields);
			return false;
		}

		$processed = true; # processed is set to true once the signature verification pass

		if ($fields['code'] != self::RETURN_SUCCESS_CODE) {
			$payStatus = $fields['code'];
			$this->writePaymentErrorLog("=====================yifubao Payment was not successful, payStatus is [$payStatus]", $fields);
			return false;
		}

		if ($fields['money'] != $this->convertAmountToCurrency($order->amount)) {
            $this->writePaymentErrorLog("======================yifubao checkCallbackOrder Payment amount is wrong, expected [$order->amount]", $fields);
            return false;
        }

        if ($fields['requestNo'] != $order->secure_id) {
            $this->writePaymentErrorLog("========================yifubao checkCallbackOrder order IDs do not match, expected [$order->secure_id]", $fields);
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
		return number_format($amount * 100, 0, '.', '');
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

			$signStr.=$key."=".$value."&";
        }  
		$signStr .= "key=".$this->getSystemInfo('key');
		return $signStr;
    }

	# -- 驗簽 --
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


