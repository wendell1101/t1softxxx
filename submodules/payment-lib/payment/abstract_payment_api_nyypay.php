<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * NYYPAY
 *
 * * 'NYYPAY_PAYMENT_API', ID 6121
 * * 'NYYPAY_ONLINEBANK_PAYMENT_API', ID 6122
 * * 'NYYPAY_QRCODE_PAYMENT_API', ID 6123
 * * 'NYYPAY_MOMO_PAYMENT_API', ID 6124
 * * 'NYYPAY_WITHDRAWAL_PAYMENT_API', ID 6132
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
abstract class Abstract_payment_api_nyypay extends Abstract_payment_api {
	const PAYWAY_PROXY  	   = 'C2C_PROXY';
	const PAYWAY_ONLINEBANK	   = 'C2C_DIRECT';
	const PAYWAY_QRCODE  	   = 'C2C_QR';
    const PAYWAY_MOMO	       = 'W2W_MOMO';
    const PAYWAY_WITHDRAWAL    = 'C2C_DIRECT';
	const RETURN_SUCCESS_CODE 	  = 'SUCCESS';
    const CALLBACK_STATUS_SUCCESS = 'COMPLETED';
    const RESULT_STATUS_SUCCESS   = 'SUCCESS';

    public function __construct($params = null) {
		parent::__construct($params);
		$this->_custom_curl_header = array('application/x-www-form-urlencoded');
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
        $firstname = (isset($playerDetails[0]) && !empty($playerDetails[0]['firstName']))     ? $playerDetails[0]['firstName']     : 'no firstName';
        $lastname  = (isset($playerDetails[0]) && !empty($playerDetails[0]['lastName']))      ? $playerDetails[0]['lastName']      : 'no lastName';

		$this->CI->load->model('player');
		$params['merchantCode'] = $this->getSystemInfo("account");
		$this->configParams($params, $order->direct_pay_extra_info);
		$params['signType'] = 'MD5';
		$params['timestamp'] = date('YmdHis');
		$params['merchantOrderNo'] = $order->secure_id;
		$params['orderAmount'] = $this->convertAmountToCurrency($amount); //元
		$params['accountName'] = $firstname.' '.$lastname;
		$params['notifyUrl'] = $this->getNotifyUrl($orderId);
		$params['sign'] = $this->sign($params);
		$this->CI->utils->debug_log("=====================nyypay generatePaymentUrlForm", $params);

		return $this->processPaymentUrlForm($params);
	}

    protected function processPaymentUrlFormPost($params) {
        $response = $this->submitPostForm($this->getSystemInfo('url'), $params, false, $params['merchantOrderNo']);
        $response = json_decode($response, true);
        $this->CI->utils->debug_log('=====================nyypay processPaymentUrlFormPost response', $response);

        if(isset($response['code']) && $response['code'] == self::RESULT_STATUS_SUCCESS) {
        	if(isset($response['depositUrl']) && !empty($response['depositUrl'])){
        		if(isset($response['depositUrl']) && !empty($response['depositUrl'])){
        			return array(
		                'success' => true,
		                'type' => self::REDIRECT_TYPE_URL,
		                'url' => $response['depositUrl'],
		            );
        		}
        	}
        }
        else if(isset($response['message']) && !empty($response['message'])) {
            return array(
                'success' => false,
                'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
                'message' => $response['message']
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

        if($source == 'server'){
            if(empty($params)){
                $raw_post_data = file_get_contents('php://input', 'r');
                $params = json_decode($raw_post_data, true);
            }
            $this->CI->utils->debug_log('=======================nyypay callbackFromServer server callbackFrom', $params);
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
            $this->CI->sale_order->updateExternalInfo($order->id, $params['merchantOrderNo'], null, null, null, $response_result_id);
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

        $requiredFields = array('orderStatus', 'orderAmount','merchantOrderNo','sign');

        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("=====================nyypay missing parameter: [$f]", $fields);
                return false;
            }
        }
         # is signature authentic?
        if (!$this->validateSign($fields)) {
            $this->writePaymentErrorLog('=====================nyypay checkCallbackOrder verify signature Error', $fields);
            return false;
        }

        if ( $this->convertAmountToCurrency($order->amount) != floatval( $fields['orderAmount'] ) ) {
            $this->writePaymentErrorLog("=====================nyypay Payment amounts do not match, expected [$order->amount]", $fields);
            return false;
        }

        if ($fields['merchantOrderNo'] != $order->secure_id) {
            $this->writePaymentErrorLog("=====================nyypay checkCallbackOrder order IDs do not match, expected [$order->secure_id]", $fields);
            return false;
        }

        if ($fields['orderStatus'] != self::CALLBACK_STATUS_SUCCESS) {
            $this->writePaymentErrorLog('=======================nyypay checkCallbackOrder payment was not successful', $fields);
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
        $sign = md5($signStr);
		return $sign;
	}

	public function createSignStr($params) {
    	ksort($params);
       	$signStr='';
		foreach ($params as $key => $value) {
			if($key == 'sign'){
				continue;
			}
			$signStr .= "$value";
		}
		$signStr = $signStr.$this->getSystemInfo('key');
		return $signStr;
	}

    protected function validateSign($params) {
        ksort($params);
        $signStr = '';
        foreach($params as $key => $value) {
            if($key == 'sign'){
                continue;
            }
            $signStr .= "$value";
        }
        $sign = md5($signStr.$this->getSystemInfo('key'));
        if($params['sign'] == $sign){
            return true;
        }
        else{
            return false;
        }
    }

	protected function getBankListInfoFallback() {
		return array(
			array('value' => "VIB",  'label' =>"VIB"),
			array('value' => "AGB",  'label' =>"AGB"),
			array('value' => "SAIGONBANK",  'label' =>"SAIGONBANK"),
			array('value' => "VRB",  'label' =>"VRB"),
			array('value' => "ABB",  'label' =>"ABB"),
			array('value' => "BVB",  'label' =>"BVB"),
			array('value' => "PBVN",  'label' =>"PBVN"),
			array('value' => "PVB",  'label' =>"PVB"),
			array('value' => "KLB",  'label' =>"KLB"),
			array('value' => "WOO",  'label' =>"WOO"),
			array('value' => "MB",  'label' =>"MB"),
			array('value' => "ACB",  'label' =>"ACB"),
			array('value' => "SC",  'label' =>"SC"),
			array('value' => "PGB",  'label' =>"PGB"),
			array('value' => "VCCB",  'label' =>"VCCB"),
			array('value' => "VAB",  'label' =>"VAB"),
			array('value' => "EXIM",  'label' =>"EXIM"),
			array('value' => "OJB",  'label' =>"OJB"),
			array('value' => "STB",  'label' =>"STB"),
			array('value' => "NAB",  'label' =>"NAB"),
			array('value' => "IVB",  'label' =>"IVB"),
			array('value' => "LPB",  'label' =>"LPB"),
			array('value' => "IBKHCMC",  'label' =>"IBKHCMC"),
			array('value' => "BAB",  'label' =>"BAB"),
			array('value' => "MSB",  'label' =>"MSB"),
			array('value' => "VCB",  'label' =>"VCB"),
			array('value' => "COB",  'label' =>"COB"),
			array('value' => "GPB",  'label' =>"GPB"),
			array('value' => "IBKHANOI",  'label' =>"IBKHANOI"),
			array('value' => "BIDV",  'label' =>"BIDV"),
			array('value' => "SEAB",  'label' =>"SEAB"),
			array('value' => "HLB",  'label' =>"HLB"),
			array('value' => "DAB",  'label' =>"DAB"),
			array('value' => "SHBVN",  'label' =>"SHBVN"),
			array('value' => "HDB",  'label' =>"HDB"),
			array('value' => "CIMB",  'label' =>"CIMB"),
			array('value' => "SHB",  'label' =>"SHB"),
			array('value' => "SCB",  'label' =>"SCB"),
			array('value' => "UOB",  'label' =>"UOB"),
			array('value' => "VPB",  'label' =>"VPB"),
			array('value' => "TPB",  'label' =>"TPB"),
			array('value' => "TCB",  'label' =>"TCB"),
			array('value' => "NCB",  'label' =>"NCB"),
			array('value' => "VTB",  'label' =>"VTB"),
			array('value' => "VB",  'label' =>"VB"),
			array('value' => "OMLLVCB",  'label' =>"OMLLVCB"),
			array('value' => "OCB",  'label' =>"OCB"),
			array('value' => "CFC",  'label' =>"CFC"),
		);
	}
}
