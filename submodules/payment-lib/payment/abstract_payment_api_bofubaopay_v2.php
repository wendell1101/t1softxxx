<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';
/**
 * BOFUBAOPAY_V2
 *
 *
 * * BOFUBAOPAY_V2_ALIPAY_PAYMENT_API, ID: 637
 * * BOFUBAOPAY_V2_WEIXIN_PAYMENT_API, ID: 639
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL:https://www.bofubaopay_v2api.com/
 * * Account: ## User ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
abstract class Abstract_payment_api_bofubaopay_v2 extends Abstract_payment_api {

	const P_CHANNEL_ALIPAY = '1';
    const P_CHANNEL_WEIXIN = '2';
	const RETURN_SUCCESS_CODE = '200';
	const RETURN_FAIL_CODE = '200';

	public function __construct($params = null) {
		parent::__construct($params);
	}

	# Implement these for specific pay type
	protected abstract function configParams(&$params, $direct_pay_extra_info);
	protected abstract function processPaymentUrlForm($params);

	# -- override common API functions --
	## Constructs an URL so that the caller can redirect / invoke it to make payment through this API
	## See controllers/redirect.php for detail.
	##
	## Retuns a hash containing these fields:
	## array(
	##	'success' => true,
	##	'type' => self::REDIRECT_TYPE_FORM,  ## constants defined in abstract_payment_api.php
	##	'url' => $info['url'],
	##	'params' => $params,
	##	'post' => true
	## );
	public function generatePaymentUrlForm($orderid, $playerId, $amount, $orderDateTime, $playerPromoId = null, $enabledSecondUrl = true, $bankId = null) {
		if ($this->shouldRedirect($enabledSecondUrl)) {
			$url = $this->CI->utils->getPaymentUrl($this->getSystemInfo('second_url'), $this->getPlatformCode(), $amount, $playerId, $playerPromoId, false, $bankId, $orderid);
			$result = array('success' => true, 'type' => self::REDIRECT_TYPE_URL, 'url' => $url);
			return $result;
		}

		$order = $this->CI->sale_order->getSaleOrderById($orderid);

		$params = array();
        $params['uid'] = $this->getSystemInfo('account');
        $params['price'] = $amount;
        $params['notify_url'] = $this->getNotifyUrl($orderid);
        $params['return_url'] = $this->getReturnUrl($orderid);
        $params['orderid'] = $order->secure_id;
        $params['orderuid'] = $order->secure_id;
        $params['goodsname'] = 'despost';
        $params['token'] = $this->getSystemInfo('key');
        $this->configParams($params, $order->direct_pay_extra_info);

		$params['key'] = $this->sign($params);
		unset($params['token']);
		$this->CI->utils->debug_log("=====================bofubaopay_v2 generatePaymentUrlForm", $params);

		return $this->processPaymentUrlForm($params);
	}

	protected function processPaymentUrlFormQRCode($params) {

		$postString = json_encode($params);
		$this->CI->utils->debug_log("=====================bofubaopay_v2 postString", $postString);
		$curlConn = curl_init();
		curl_setopt($curlConn, CURLOPT_URL, $this->getSystemInfo('url'));
		curl_setopt($curlConn, CURLOPT_CUSTOMREQUEST, "POST");
		curl_setopt($curlConn, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
		curl_setopt($curlConn, CURLOPT_CONNECTTIMEOUT, 30);
		curl_setopt($curlConn, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MSIE 5.01; Windows NT 5.0)');
		curl_setopt($curlConn, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curlConn, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($curlConn, CURLOPT_SSL_VERIFYHOST, FALSE);
		curl_setopt($curlConn, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($curlConn, CURLOPT_AUTOREFERER, 1);
		curl_setopt($curlConn, CURLOPT_POSTFIELDS, $postString);

		# Need to specify the referer when doing CURL submit. since we use redirect 2nd url, we can take the HTTP_HOST
		curl_setopt($curlConn, CURLOPT_REFERER, "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]");

		$curlResult = curl_exec($curlConn);
		$curlSuccess = (curl_errno($curlConn) == 0);

		$this->CI->utils->debug_log('curlSuccess', $curlSuccess, $curlResult);

		$errorMsg = null;
		if($curlSuccess) {
			$result = json_decode($curlResult, true);

			$qrCodeUrl = urldecode($result['url']);
			$this->CI->utils->debug_log('============================bofubaopay_v2 Result to be QRCODE address', $qrCodeUrl);
			if(!$qrCodeUrl) {
				$curlSuccess = false;
			}

			if(array_key_exists('respDesc', $result)) {
				$errorMsg = $result['respDesc'];
			} elseif (array_key_exists('respDesc', $result)) {
				$errorMsg = $result['respDesc'];
			}
		} else {
			# curl error
			$errorMsg = curl_error($curlConn);
		}

		curl_close($curlConn);

		if($curlSuccess) {
			return array(
				'success' => true,
				'type' => self::REDIRECT_TYPE_QRCODE,
				'url' => $qrCodeUrl,
				'post' => true
			);
		} else {
			return array(
				'success' => false,
				'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
				'message' => $errorMsg
			);
		}
	}

    public function checkDepositStatus($secureId) {
        $param = array();
        $param['uid'] = $this->getSystemInfo('account');
        $param['orderid'] = $secureId;
        $param['r'] = date('Y-m-d H:i:s');
        $param['key'] = $this->checkDepositStatusSign($param);

        $checkDepositURL = $this->getSystemInfo('url').'/api/getStatusByOrderId';
        $response = $this->submitPostForm($checkDepositURL, $param, false, $param['orderid']);

        return $this->decodeBofubapayV2DepositStatusResult($response);
    }

    public function decodeBofubapayV2DepositStatusResult($response){
        if(empty($response)){
            $this->CI->utils->debug_log('========================bofubaopay_v2 checkDepositStatus unknown result: ', $response);
            return [
                'success' => FALSE,
                'message' => 'Unknown response data'
            ];
        }

        $json_data = json_decode($response, TRUE);

        if(!isset($json_data['msg']) || !isset($json_data['data']) || !isset($json_data['code'])){
            $this->utils->error_log("========================bofubaopay_v2 checkDepositStatus response lost the necessary info.", $json_data);
            return [
                'success' => FALSE,
                'message' => 'Lost the necessary info'
            ];
        }


        $process_status = array('success' => $success,'message' => $message);
        $this->utils->debug_log('========================bofubaopay_v2 decodeBofubapayV2DepositStatusResult process status: ', $process_status);

        return [
            'success' => $success,
            'message' => $message
        ];
    }

	# Display QRCode get from curl

	## This will be called when the payment is async, API server calls our callback page
	## When that happens, we perform verifications and necessary database updates to mark the payment as successful
	## Reference: sample code, callback.php
	public function callbackFromServer($orderid, $params) {
		$response_result_id = parent::callbackFromServer($orderid, $params);
		return $this->callbackFrom('server', $orderid, $params, $response_result_id);
	}

	## This will be called when user redirects back to our page from payment API
	public function callbackFromBrowser($orderid, $params) {
		$response_result_id = parent::callbackFromBrowser($orderid, $params);
		return $this->callbackFrom('browser', $orderid, $params, $response_result_id);
	}

	# $source can be 'server' or 'browser'
	private function callbackFrom($source, $orderid, $params, $response_result_id) {
		$result = array('success' => false, 'next_url' => null, 'message' => lang('error.payment.failed'));
		$order = $this->CI->sale_order->getSaleOrderById($orderid);
		$processed = false;

		if($source == 'server' ){
			$raw_post_data = file_get_contents('php://input', 'r');
			$this->CI->utils->debug_log("=====================bofubaopay_v2 raw_post_data", $raw_post_data);
			$params = json_decode($raw_post_data, true);
			$this->CI->utils->debug_log("=====================bofubaopay_v2 params", $params);


			if (!$order || !$this->checkCallbackOrder($order, $params, $processed)) {
				return $result;
			}
		}
		# Update order payment status and balance
		$success = true;

		# Update player balance based on order status
		# if it's STATUS_SETTLED or STATUS_BROWSER_CALLBACK, put log, and ignore
		$orderStatus = $this->CI->sale_order->getSaleOrderStatusById($orderid);
		if ($orderStatus == Sale_order::STATUS_BROWSER_CALLBACK || $orderStatus == Sale_order::STATUS_SETTLED) {
			$this->CI->utils->debug_log('callbackFrom' . ucfirst($source) . ', already get callback for order:' . $order->id, $params);
			if ($source == 'server' && $order->status == Sale_order::STATUS_BROWSER_CALLBACK) {
				$this->CI->sale_order->setStatusToSettled($orderid);
			}
		} else {
			# update player balance
			$this->CI->sale_order->updateExternalInfo($order->id, $params['reqseq'], null, null, null, $response_result_id);
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
			$result['return_error'] = self::RETURN_FAIL_CODE  ;
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
            'orderid', 'reqseq', 'price', 'serviceprice', 'key'
        );

		foreach ($requiredFields as $f) {
			if (!array_key_exists($f, $fields)) {
				$this->writePaymentErrorLog("=====================bofubaopay_v2 checkCallbackOrder missing parameter: [$f]", $fields);
				return false;
			}
		}

		# is signature authentic?
		if (!$this->validateSign($fields)) {
			$this->writePaymentErrorLog('=====================bofubaopay_v2 checkCallbackOrder signature Error', $fields);
			return false;
		}

		$processed = true; # processed is set to true once the signature verification pass

		if ($fields['orderid'] != $order->secure_id ) {
			$this->writePaymentErrorLog('=====================bofubaopay_v2 checkCallbackOrder payment was not successful', $fields);
			return false;
		}
		$CheckPrice=$this->convertAmountToCurrency($order->amount);
		$CheckPriceLimit=abs($CheckPrice - $fields['price']);
		if ($CheckPriceLimit > 3 ) {
			$this->writePaymentErrorLog("=====================bofubaopay_v2 checkCallbackOrder payment amounts do not match, expected  [$CheckPrice] - [".$fields['price']."] =[$CheckPriceLimit]", $fields);
			return false;
		}

		# everything checked ok
		return true;
	}

	public function directPay($order = null) {
		return array('success' => false); # direct pay not supported by this API
	}

	# -- Private functions --
	# After payment is complete, the gateway will invoke this URL asynchronously
	private function getNotifyUrl($orderid) {
		return parent::getCallbackUrl('/callback/process/' . $this->getPlatformCode() . '/' . $orderid);
	}

	## After payment is complete, the gateway will send redirect back to this URL
	private function getReturnUrl($orderid) {
		return parent::getCallbackUrl('/callback/browser/success/' . $this->getPlatformCode() . '/' . $orderid);
	}

	## Format the amount value for the API
	protected function convertAmountToCurrency($amount) {
		$amount = number_format($amount*100, 2, '.', '');

		if($amount%10 == 0){
			$amount= $amount - rand(1,3);
		}

		return $amount;
	}

	# -- signatures --
	private function sign($params) {
        $keys = array( 'goodsname', 'istype', 'notify_url', 'orderid','orderuid','price','token','uid');

		$signStr = "";
		foreach($keys as $key) {
			if (array_key_exists($key, $params)) {
				$signStr .= $params[$key];
			}
		}

		$sign = md5($signStr);
		return $sign;
	}

	private function validateSign($params) {
		$params['token'] = $this->getSystemInfo('key');
		$keys = array('reqseq', 'orderid', 'price', 'serviceprice', 'orderuid','token');

		$signStr = "";
		foreach($keys as $key) {
			if (array_key_exists($key, $params)) {
				$signStr .= $params[$key];
			}
		}

		$sign = md5($signStr);
		unset($params['token']);

		return strcasecmp($sign, $params['key']) === 0;
	}

    private function checkDepositStatusSign($params) {
        $params['token'] = $this->getSystemInfo('key');
        $signStr = "";
        foreach($params as  $key=>$val) {
            $signStr .= $val;
        }
        $sign = md5($signStr);

        return $sign;
    }
}
