<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * PRINCE
 *
 * * 'PRINCE_PAYMENT_API', ID 5947
 * * 'PRINCE_MOMO_PAYMENT_API', ID 5948
 * * 'PRINCE_ZALO_PAYMENT_API', ID 5949
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
abstract class Abstract_payment_api_prince extends Abstract_payment_api {
	const PAYWAY_BANK	   = '907';
    const PAYWAY_MOMO	   = '923';
    const PAYWAY_ZALO	   = '921';
	const RETURN_SUCCESS_CODE = 'success';
    const RETURN_FAILED_CODE = 'FAIL';
    const RESULT_STATUS_SUCCESS = '10000';

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
		$params['uid'] = $this->getSystemInfo("account");
		$params['orderid'] = $order->secure_id;
		$this->configParams($params, $order->direct_pay_extra_info);
		$params['notify_url'] = $this->getNotifyUrl($orderId);
		$params['return_url'] = $this->getReturnUrl($orderId);
		$params['amount'] = $this->convertAmountToCurrency($amount); //元
        $params['userip'] = $this->getClientIP();
        $params['timestamp'] = time();
        $params['custom'] = 'deposit';
		$params['sign'] = $this->sign($params);
		$this->CI->utils->debug_log("=====================prince generatePaymentUrlForm", $params);

		return $this->processPaymentUrlForm($params);
	}

    protected function processPaymentUrlFormPost($params) {
        $response = $this->submitPostForm($this->getSystemInfo('url'), $params, false, $params['orderid']);
        $response = json_decode($response, true);
        $this->CI->utils->debug_log('=====================findpay processPaymentUrlFormPost response', $response);

        if(isset($response['status']) && $response['status'] == self::RESULT_STATUS_SUCCESS) {
        	if(isset($response['result']) && !empty($response['result'])){
        		if(isset($response['result']['payurl']) && !empty($response['result']['payurl'])){
        			return array(
		                'success' => true,
		                'type' => self::REDIRECT_TYPE_URL,
		                'url' => $response['result']['payurl'],
		            );
        		}
        	}
        }
        else if(isset($response['status']) && !empty($response['status'])) {
            return array(
                'success' => false,
                'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
                'message' => $response['status']
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
        }else{
        	$resultData = json_decode($params['result'],true);
        }

        if($source == 'server'){
            $this->CI->utils->debug_log('=======================prince callbackFromServer server callbackFrom', $params);
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
            $this->CI->sale_order->updateExternalInfo($order->id, $resultData['orderid'], null, null, null, $response_result_id);
			if ($source == 'browser') {
				$success = $this->CI->sale_order->browserCallbackSaleOrder($order->id, 'auto broswer callback ' . $this->getPlatformCode(), false);
			} elseif ($source == 'server') {
				$this->approveSaleOrder($order->id, 'auto server callback ' . $this->getPlatformCode(), false);
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

		$requiredFields = array('status','sign','amount','orderid');

		$resultData = json_decode($fields['result'],true);

		foreach ($requiredFields as $f) {
			if($f == 'status' || $f == 'sign'){
				if (!array_key_exists($f, $fields)) {
					$this->writePaymentErrorLog("=====================prince missing parameter: [$f]", $fields);
					return false;
				}
			}else{
				if (!array_key_exists($f, $resultData)) {
					$this->writePaymentErrorLog("=====================prince missing parameter: [$f]", $fields);
					return false;
				}
			}
		}

		if ($fields['status'] != self::RESULT_STATUS_SUCCESS) {
			$this->writePaymentErrorLog("=====================prince Payment was not successful", $fields);
			return false;
		}

		if ($this->convertAmountToCurrency($order->amount) != $resultData['amount']) {
			$this->writePaymentErrorLog("=====================prince Payment amounts do not match, expected [$order->amount]", $fields);
			return false;
		}

        if ($resultData['orderid'] != $order->secure_id) {
            $this->writePaymentErrorLog("=====================prince checkCallbackOrder order IDs do not match, expected [$order->secure_id]", $fields);
            return false;
        }

        # is signature authentic?
        if (!$this->verifySignature($fields)) {
            $this->writePaymentErrorLog('=====================prince checkCallbackOrder verify signature Error', $fields);
            return false;
        }


        $processed = true; # processed is set to true once the signature verification pass

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
        $sign=strtoupper(md5($signStr));

		return $sign;
	}

    public function verifySignature($data) {
	    $callback_sign = $data['sign'];
	    $resultData = json_decode($data['result'],true);
	    $resultData = json_encode($resultData);
	    $signStr ='result='.$resultData.'&status='.$data['status'].'&key='.$this->getSystemInfo('key');
        $sign=strtoupper(md5($signStr));
        return $sign == $data['sign'];
    }

    public function createSignStr($params) {
    	ksort($params);
       	$signStr='';
		foreach ($params as $key => $value) {
			if(is_null($value) || is_null($value) || $key == 'sign'){
				continue;
			}
			$signStr .= $key."=".$value."&";
		}
		$signStr .= 'key='. $this->getSystemInfo('key');
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
