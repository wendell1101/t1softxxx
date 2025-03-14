<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';
/**
 * ZHIFU 知付
 *
 * * ZHIFU_PAYMENT_API, ID: 5245
 * * ZHIFU_ALIPAY_PAYMENT_API, ID: 943
 * * ZHIFU_ALIPAY_H5_PAYMENT_API, ID: 5246
 * * ZHIFU_QUICKPAY_PAYMENT_API, ID: 5247
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://api.aido88.cn/api_deposit.shtml
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
abstract class Abstract_payment_api_zhifu extends Abstract_payment_api {

    const BANKCODE_ALIPAY   = 'ALIPAY';
    const BANKCODE_WEIXIN   = 'WEIXIN';
    const BANKCODE_QQPAY    = 'QQ';
    const BANKCODE_UNIONPAY = 'UNIONPAY';

    const MODE_ONLINEBANK = '1';
    const MODE_BANKCARD   = '2';
    const MODE_WEIXIN     = '4';
    const MODE_WEIXIN_H5  = '5';
    const MODE_ALIPAY     = '8';
    const MODE_ALIPAY_H5  = '9';
    const MODE_QQPAY      = '16';
    const MODE_QQPAY_H5   = '17';
    const MODE_QUICKPAY   = '32';
    const MODE_UNIONPAY   = '33';

	const RETURN_SUCCESS_CODE = 'success';
	const PAY_RESULT_SUCCESS = '1';

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

		$params['CustomerId'] = $order->secure_id;
        $this->configParams($params, $order->direct_pay_extra_info);
		$params['Money'] = $this->convertAmountToCurrency($amount);
		$params['UserId'] = $this->getSystemInfo("account");
		$params['Message'] = 'Deposit';
		$params['CallBackUrl'] = $this->getNotifyUrl($orderId);
		$params['ReturnUrl'] = $this->getReturnUrl($orderId);

		$params['Sign'] = $this->sign($params);
		$this->CI->utils->debug_log("=====================zhifu generatePaymentUrlForm", $params);

		return $this->processPaymentUrlForm($params);
	}

    protected function processPaymentUrlFormQRCode($params) {
        $response = $this->submitPostForm($this->getSystemInfo('url'), $params, false, $params['CustomerId']);
        $response = json_decode($response, true);
        $this->CI->utils->debug_log('=====================zhifu processPaymentUrlFormQRCode response', $response);

        if(isset($response['Status']) && $response['Status']){
            return array(
                'success' => true,
                'type' => self::REDIRECT_TYPE_QRCODE,
                'url' => $response['Data']['Url'],
            );
        }
        else if(isset($response['Code'])) {
            return array(
                'success' => false,
                'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
                'message' => '['.$response['Code'].']: '.$response['Msg']
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

    protected function processPaymentUrlFormRedirect($params) {
        $response = $this->submitPostForm($this->getSystemInfo('url'), $params, false, $params['CustomerId']);
        $response = json_decode($response, true);
        $this->CI->utils->debug_log('=====================zhifu processPaymentUrlFormQRCode response', $response);

        if(isset($response['Status']) && $response['Status']){
            return array(
                'success' => true,
                'type' => self::REDIRECT_TYPE_URL,
                'url' => $response['Data']['Url'],
            );
        }
        else if(isset($response['Code'])) {
            return array(
                'success' => false,
                'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
                'message' => '['.$response['Code'].']: '.$response['Msg']
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

    private function callbackFrom($source, $orderId, $params, $response_result_id) {
        $result = array('success' => false, 'next_url' => null, 'message' => lang('error.payment.failed'));
        $order = $this->CI->sale_order->getSaleOrderById($orderId);
        $processed = false;

        $this->CI->utils->debug_log("=====================zhifu callbackFrom $source params", $params);

        if($source == 'server'){
            if (empty($params)) {
                $raw_post_data = file_get_contents('php://input', 'r');
                $this->CI->utils->debug_log("=====================zhifu raw_post_data", $raw_post_data);
                $params = json_decode($raw_post_data,true);
                $this->CI->utils->debug_log("=====================zhifu json_decode params", $params);
            }
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
            $this->CI->sale_order->updateExternalInfo($order->id, $params['CustomerId'], null, null, null, $response_result_id);
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
		$requiredFields = array('CustomerId', 'OrderId', 'Money', 'Status', 'Time', 'Message', 'Type', 'Sign');

		foreach ($requiredFields as $f) {
			if (!array_key_exists($f, $fields)) {
				$this->writePaymentErrorLog("=====================zhifu missing parameter: [$f]", $fields);
				return false;
			}
		}

        # is signature authentic?
        if (!$this->validateSign($fields)) {
            $this->writePaymentErrorLog("=====================zhifu checkCallbackOrder verify signature Error", $fields);
            return false;
        }

        $processed = true; # processed is set to true once the signature verification pass

		if ($fields['Status'] != self::PAY_RESULT_SUCCESS) {
			$this->writePaymentErrorLog("=====================zhifu checkCallbackOrder Payment was not successful", $fields);
			return false;
		}

		if ($fields['Money'] != $this->convertAmountToCurrency($order->amount)) {
			$this->writePaymentErrorLog("=====================zhifu checkCallbackOrder Payment amounts do not match, expected [$order->amount]", $fields);
			return false;
		}

        if ($fields['CustomerId'] != $order->secure_id) {
            $this->writePaymentErrorLog("=====================zhifu checkCallbackOrder order IDs do not match, expected [$order->secure_id]", $fields);
            return false;
        }

		# everything checked ok
		return true;
	}

	public function directPay($order = null) {
		return array('success' => false); # direct pay not supported by this API
	}

	private function getNotifyUrl($orderId) {
		return parent::getCallbackUrl('/callback/process/' . $this->getPlatformCode() . '/' . $orderId);
	}

	private function getReturnUrl($orderId) {
		return parent::getCallbackUrl('/callback/browser/success/' . $this->getPlatformCode() . '/' . $orderId);
	}

	protected function convertAmountToCurrency($amount) {
		return number_format($amount, 2, '.', '');
	}

	# -- private helper functions --
    private function sign($params) {
        $signStr = $this->createSignStr($params);
        $sign = md5($signStr);
    
        return $sign;
    }

    private function createSignStr($params) {
        ksort($params);
        $signStr = '';
        foreach ($params as $key => $value) {
            if( ($key == 'Sign') || (empty($value)) ) {
                continue;
            }
            $signStr .= $key."=".urlencode($value)."&";

        }
        $signStr .= 'Key='. $this->getSystemInfo('key');
        return $signStr;
    }

    private function validateSign($params) {
        $signStr = $this->createSignStr($params);
        $sign = md5($signStr);
        if($params['Sign'] == $sign){
            return true;
        }
        else{
         
            return false;
        }
    }
}
