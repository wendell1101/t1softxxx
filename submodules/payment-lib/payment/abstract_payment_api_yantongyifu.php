<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * yantongyifu 易付
 *
 * * YANTONGYIFU_PAYMENT_API, ID 5066
 * * YANTONGYIFU_ALIPAY_PAYMENT_API, ID 5067
 * * YANTONGYIFU_ALIPAY_H5_PAYMENT_API, ID 5068
 * * YANTONGYIFU_QUICKPAY_PAYMENT_API, ID 5069
 * * YANTONGYIFU_QUICKPAY_H5_PAYMENT_API, ID 5070
 * Required Fields:
 *
 * * URL http:// 212.64.89.203:8889/tran/cashier/pay.ac
 * * Account - Merchant ID
 * * Key - Signing key
 * * Extra Info
 *
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
abstract class Abstract_payment_api_yantongyifu extends Abstract_payment_api {

	const TRANTYPE_ONLINEBANK = '0601'; #网关支付
    const TRANTYPE_ALIPAY	  = '0502'; #支付宝
    const TRANTYPE_ALIPAY_H5  = '0506'; #支付宝H5(返回json) 0505(网页表单提交)
    const TRANTYPE_WEIXIN	  = '0402'; #微信
    const TRANTYPE_WEIXIN_H5  = '0406'; #微信H5(返回json) 0405(网页表单提交)
    const TRANTYPE_QQPAY	  = '0702'; #QQ支付
	const TRANTYPE_JDPAY      = '0902'; #京东钱包
	const TRANTYPE_JDPAY_H5   = '0906'; #京东钱包H5(返回json) 0905(网页表单提交)
	const TRANTYPE_UNIONAPAY  = '0802'; #银联扫码
	const TRANTYPE_QUICKPAY   = '0302'; #快捷扫码

	const RETURN_SUCCESS_CODE = 'SC000000';
    const RETURN_FAILED_CODE  = 'FAIL';
    const REQUEST_SUCCESS     = '000000';
	const PAY_RESULT_SUCCESS  = '01';

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

		$params['version']     = '2.1';
		$params['orgNo']       = $this->getSystemInfo("orgNo");
		$params['custId']      = $this->getSystemInfo("account");
		$params['custOrderNo'] = $order->secure_id;
		$params['payAmt']      = $this->convertAmountToCurrency($amount);
		$params['backUrl']     = $this->getNotifyUrl($orderId);
		$params['frontUrl']    = $this->getReturnUrl($orderId);
		$params['goodsName']   = 'deposit';
		$params['buyIp']       = $this->getClientIp();
		$this->configParams($params, $order->direct_pay_extra_info);
		$params['sign']        = $this->sign($params);

		$this->CI->utils->debug_log("=====================yantongyifu generatePaymentUrlForm", $params);

		return $this->processPaymentUrlForm($params);
	}

	# Submit POST form
	protected function processPaymentUrlFormPost($params) {

		$url = $this->getSystemInfo('url');
		$this->CI->utils->debug_log("=====================yantongyifu processPaymentUrlFormPost URL", $url);
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

        $response = $this->submitPostForm($this->getSystemInfo('url'), $params, false, $params['custOrderNo']);
        $this->CI->utils->debug_log('=====================yantongyifu processPaymentUrlFormQRcode received response', $response);
        $response = json_decode($response,true);
        $this->CI->utils->debug_log('=====================yantongyifu processPaymentUrlFormQRcode json to array', $response);

		if(!empty($response['busContent']) && ($response['code'] == self::REQUEST_SUCCESS)) {
            return array(
                'success' => true,
                'type' => self::REDIRECT_TYPE_URL,
                'url' => $response['busContent'],
            );
        }else if($response['msg']) {
            return array(
                'success' => false,
                'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
                'message' => 'Return code: '.$response['code'].'=> '.$response['msg']
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

    protected function processPaymentUrlEchoHtml($params) {

        $response = $this->submitPostForm($this->getSystemInfo('url'), $params, false, $params['custOrderNo']);
        $this->CI->utils->debug_log('=====================yantongyifu processPaymentUrlFormQRcode received response', $response);
        $response = json_decode($response,true);
        $this->CI->utils->debug_log('=====================yantongyifu processPaymentUrlFormQRcode json to array', $response);

		if(!empty($response['busContent']) && ($response['code'] == self::REQUEST_SUCCESS)) {
			echo $response['busContent'];
        }else if($response['msg']) {
            return array(
                'success' => false,
                'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
                'message' => 'Return code: '.$response['code'].'=> '.$response['msg']
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
	private function callbackFrom($source, $orderId, $params, $response_result_id) {

        $this->CI->utils->debug_log('=======================yantongyifu callbackFrom in Function callbackFrom', $params);

        $result = array('success' => false, 'next_url' => null, 'message' => lang('error.payment.failed'));
        $order = $this->CI->sale_order->getSaleOrderById($orderId);
        $processed = false;

        if($source == 'server'){

        	if(empty($params)){
        		$raw_post_data = file_get_contents('php://input', 'r');
				$this->CI->utils->debug_log('=======================yantongyifu callbackFrom raw_post_data input R', $raw_post_data);
		    	$params = json_decode($raw_post_data, true);
        	}
        	
            $this->CI->utils->debug_log('=======================yantongyifu callbackFromServer server callbackFrom', $params);
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
            $this->CI->sale_order->updateExternalInfo($order->id, $params['custOrderNo'], '', null, null, $response_result_id);
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

	/**
	 * detail: Validates whether the callback from API contains valid info and matches with the order
	 *
	 * @return boolean
	 */

	private function checkCallbackOrder($order, $fields, &$processed = false) {

		$requiredFields = array('version', 'orgNo', 'custId', 'custOrderNo', 'prdOrdNo', 'ordAmt', 'ordTime', 'payAmt', 'ordStatus', 'sign');

		foreach ($requiredFields as $f) {
			if (!array_key_exists($f, $fields)) {
				$this->writePaymentErrorLog("=================yantongyifu checkCallbackOrder missing parameter: [$f]", $fields);
				return false;
			}
		}

		# is signature authentic?
        if (!$this->validateSign($fields)) {
            $this->writePaymentErrorLog('=====================yantongyifu checkCallbackOrder verify signature Error', $fields);
            return false;
        }

		if ($fields['ordStatus'] != self::PAY_RESULT_SUCCESS) {
			$payStatus = $fields['ordStatus'];
			$this->writePaymentErrorLog("=====================yantongyifu checkCallbackOrder Payment was not successful, payStatus is [$payStatus]", $fields);
			return false;
		}

		if ($fields['payAmt'] != $this->convertAmountToCurrency($order->amount)) {
            #because player need to enter amount at Alipay
            if($this->getSystemInfo('allow_callback_amount_diff')){
            	$diffAmount = abs($this->convertAmountToCurrency($order->amount) - floatval( $fields['payAmt']));
				if ($diffAmount >= 1) {
					$this->writePaymentErrorLog("=====================yantongyifu checkCallbackOrder Payment amounts ordAmt - payAmount > 1, expected [$order->amount]", $fields ,$diffAmount);
					return false;
				}
                $this->CI->utils->debug_log('=====================yantongyifu checkCallbackOrder amount not match expected [$order->amount]');
                $notes = $order->notes . " | callback diff amount, origin was: " . $order->amount;
                $this->CI->sale_order->fixOrderAmount($order->id, $fields['payAmt'], $notes);
            }
            else{
                $this->writePaymentErrorLog("=====================yantongyifu checkCallbackOrder Payment amounts do not match, expected [$order->amount]", $fields);
                return false;
            }
		}

        if ($fields['custOrderNo'] != $order->secure_id) {
            $this->writePaymentErrorLog("=====================yantongyifu checkCallbackOrder order IDs do not match, expected [$order->secure_id]", $fields);
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
            array('label' => '工商银行', 'value' => '工商银行'),
            array('label' => '农业银行', 'value' => '农业银行'),
            array('label' => '中国银行', 'value' => '中国银行'),
            array('label' => '建设银行', 'value' => '建设银行'),
            // array('label' => '农业发展银行', 'value' => '203'),
            array('label' => '交通银行', 'value' => '交通银行 '),
            array('label' => '中信银行', 'value' => '中信银行'),
            array('label' => '光大银行', 'value' => '光大银行'),
            array('label' => '华夏银行', 'value' => '华夏银行'),
            array('label' => '民生银行', 'value' => '民生银行'),
            array('label' => '广东发展银行 ', 'value' => '广东发展银行'),
            array('label' => '平安银行', 'value' => '平安银行'),
            array('label' => '招商银行', 'value' => '招商银行'),
            array('label' => '兴业银行', 'value' => '兴业银行'),
            array('label' => '上海浦东发展银行', 'value' => '上海浦东发展银行 '),
            // array('label' => '北京银行', 'value' => '1313'),
            // array('label' => '恒丰银行', 'value' => '315'),
            // array('label' => '浙商银行', 'value' => '316'),
            // array('label' => '渤海银行', 'value' => 'BHB'),
            // array('label' => '上海银行', 'value' => '1310'),
            array('label' => '中国邮政储蓄银行', 'value' => '中国邮政储蓄银行'),
            // array('label' => '徽商银行', 'value' => '440'),
            // array('label' => '广州市商业银行', 'value' => 'GRCBANK')
		);
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
            if($value == null || $key == 'sign') {
                continue;
            }
            $signStr .= "$key=$value&";
        }
        $signStr .= 'key='.$this->getSystemInfo('key');
        return $signStr;
    }

    private function validateSign($params) {
        $sign = $this->sign($params);
        if($params['sign'] == $sign){
            return true;
        }
        else{
            $this->utils->debug_log("===================yantongyifu validateSign signature is [$sign], match? ", $params['sign']);
            return false;
        }
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
		return number_format($amount*100, 0, '.', '');
	}
}
