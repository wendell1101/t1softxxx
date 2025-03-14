<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * KOLAPAY
 *
 * * 'KOLAPAY_BANKCARD_PAYMENT_API', ID 6142
 * * 'KOLAPAY_ONLINEBANK_PAYMENT_API', ID 6143
 * * 'KOLAPAY_QRCODE_PAYMENT_API', ID 6144
 * * 'KOLAPAY_ZALO_PAYMENT_API', ID 6145
 * * 'KOLAPAY_MOMO_PAYMENT_API', ID 6146
 * * 'KOLAPAY_VIETTEL_PAYMENT_API', ID 6147
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
abstract class Abstract_payment_api_kolapay extends Abstract_payment_api {
	const PAYWAY_ONLINEBANK	   = 'VNDIRECT';
	const PAYWAY_BANKCARD  	   = 'VNBANK';
	const PAYWAY_QRCODE  	   = 'VNBANKQR';
    const PAYWAY_MOMO	       = 'VNMOMO';
    const PAYWAY_ZALO	       = 'VNZALO';
    const PAYWAY_VIETTEL	   = 'VNVTPAY';

	const RETURN_SUCCESS_CODE = 'success';
    const CALLBACK_SUCCESS = '1';
    const RESULT_STATUS_SUCCESS = '200';

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
		$this->CI->load->model('player');
		$params['mchId'] = $this->getSystemInfo("account");
		$params['outTradeNo'] = $order->secure_id;
		$params['totalAmount'] = $this->convertAmountToCurrency($amount); //元
		$this->configParams($params, $order->direct_pay_extra_info);
		$params['notifyUrl'] = $this->getNotifyUrl($orderId);
		$params['backUrl'] = $this->getReturnUrl($orderId);
        $params['bankCode'] = 'OTHER';
		$params['sign'] = $this->sign($params);
		$this->CI->utils->debug_log("=====================KOLAPAY generatePaymentUrlForm", $params);

		return $this->processPaymentUrlForm($params);
	}

    protected function processPaymentUrlFormPost($params) {
        $response = $this->submitPostForm($this->getSystemInfo('url'), $params, false, $params['outTradeNo']);
        $response = json_decode($response, true);
        $this->CI->utils->debug_log('=====================KOLAPAY processPaymentUrlFormPost response', $response);

        if(isset($response['code']) && $response['code'] == self::RESULT_STATUS_SUCCESS) {
        	if(isset($response['data']) && !empty($response['data'])){
        		if(isset($response['data']['payUrl']) && !empty($response['data']['payUrl'])){
        			return array(
		                'success' => true,
		                'type' => self::REDIRECT_TYPE_URL,
		                'url' => $response['data']['payUrl'],
		            );
        		}
        	}
        }
        else if(isset($response['msg']) && !empty($response['msg'])) {
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
        $this->CI->utils->debug_log("=====================kola callbackFrom $source params", $params);

        if($source == 'server' ){
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
            $this->CI->sale_order->updateExternalInfo($order->id, $params['order_id'], null, null, null, $response_result_id);
            if ($source == 'browser') {
                $success = $this->CI->sale_order->browserCallbackSaleOrder($order->id, 'auto broswer callback ' . $this->getPlatformCode(), false);
            } elseif ($source == 'server') {
                $this->approveSaleOrder($order->id, 'auto server callback ' . $this->getPlatformCode(), false);
            }
        }

        $result['success'] = $success;
        if ($processed) {
            $result['message'] = self::RETURN_SUCCESS_CODE;
        }

        if ($source == 'browser') {
            $result['next_url'] = $this->getPlayerBackUrl();
            $result['go_success_page'] = true;
        }

        return $result;
    }

    ## Validates whether the callback from API contains valid info and matches with the order
    ## Reference: code sample, callback.php
    private function checkCallbackOrder($order, $fields, &$processed = false) {
        $requiredFields = array(
            'merchant_id','order_id','amount','isPaid','sign'
        );

        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("=====================kola checkCallbackOrder Missing parameter: [$f]", $fields);
                return false;
            }
        }

        # is signature authentic?
        if (!$this->validateSign($fields)) {
            $this->writePaymentErrorLog('=====================kola checkCallbackOrder Signature Error', $fields);
            return false;
        }

        $processed = true; # processed is set to true once the signature verification pass

        if ($fields['isPaid'] != self::CALLBACK_SUCCESS) {
            $this->writePaymentErrorLog("=========================kola checkCallbackOrder returncode was not successful", $fields);
            return false;
        }

		if ( $this->convertAmountToCurrency($order->amount) != floatval($fields['amount'])) {
			$this->writePaymentErrorLog("=====================kola Payment amounts do not match, expected [$order->amount]", $fields);
			return false;
		}

        if ($fields['order_id'] != $order->secure_id) {
            $this->writePaymentErrorLog("======================kola checkCallbackOrder order IDs do not match, expected [$order->secure_id]", $fields);
            return false;
        }

        # everything checked ok
        return true;
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
		$convert_multiplier = $this->getSystemInfo('convert_multiplier', 1);
        return number_format($amount * $convert_multiplier, 0, '.', '') ;
	}

	public function sign($params) {
		$signStr =  $this->createSignStr($params);
        $sign = md5($signStr);

		return $sign;
	}

	public function validateSign($params) {
        $data = $params;
        $signStr = $this->createSignStr($data);
        $sign = md5($signStr);
        if($params['sign'] == $sign){
            return true;
        }
        else{
            return false;
        }
    }

    public function createSignStr($params) {
    	ksort($params);
       	$signStr='';
		foreach ($params as $key => $value) {
			if($key == 'sign'){
				continue;
			}
            if($key == 'isDone' || $key == 'isRefund'){
                if($value){
                    $value = 'true';
                }else{
                    $value = 'false';
                }
            }
			$signStr .= $key."=".$value."&";
		}
		$signStr .= 'sign='. $this->getSystemInfo('key');
		return $signStr;
	}

	protected function getBankListInfoFallback() {
		return array(
			array('value' => '1548', 'label' => 'VIB'),
			array('value' => '1549', 'label' => 'VPB'),
			array('value' => '2001', 'label' => 'BIDV'),
			array('value' => '2002', 'label' => 'VietinBank(CTG)'),
			array('value' => '2003', 'label' => 'SHB'),
			array('value' => '2004', 'label' => 'ABBANK'),
			array('value' => '2005', 'label' => 'AGRIBANK'),
			array('value' => '2006', 'label' => 'Vietcombank'),
			array('value' => '2007', 'label' => 'Techcom'),
			array('value' => '2008', 'label' => 'ACB'),
			array('value' => '2009', 'label' => 'SCB'),
			array('value' => '2011', 'label' => 'MBBANK'),
			array('value' => '2012', 'label' => 'EIB'),
			array('value' => '2020', 'label' => 'STB'),
			array('value' => '2031', 'label' => 'DongABank'),
			array('value' => '2032', 'label' => 'GPBank'),
			array('value' => '2033', 'label' => 'Saigonbank'),
			array('value' => '2034', 'label' => 'PG Bank'),
			array('value' => '2035', 'label' => 'Oceanbank'),
			array('value' => '2036', 'label' => 'NamABank'),
			array('value' => '2037', 'label' => 'TPB'),
			array('value' => '2038', 'label' => 'HDB'),
			array('value' => '2039', 'label' => 'VAB')
		);
	}
}
