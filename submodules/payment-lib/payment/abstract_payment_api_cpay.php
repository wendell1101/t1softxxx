<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * CPAY
 *
 * * CPAY_PAYMENT_API,            ID: 686
 * * CPAY_QUICKPAY_PAYMENT_API,   ID: 687
 * * CPAY_WEIXIN_PAYMENT_API,     ID: 688
 * * CPAY_ALIPAY_PAYMENT_API,     ID: 689
 * * CPAY_QQPAY_PAYMENT_API,      ID: 690
 * * CPAY_JDPAY_PAYMENT_API,      ID: 691
 * * CPAY_WITHDRAWAL_PAYMENT_API, ID: 878
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 * * Extra Info { "cpay_priv_key" }
 *
 * Field Values:
 * * URL: https://api.dobopay.com/v1/api/scanpay
 * * Account: ## User ID ##
 * * Key: ## Secret Key ##
 * * Extra Info: { "cpay_priv_key" : " ## Private Key ## "}
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
abstract class Abstract_payment_api_cpay extends Abstract_payment_api {
    //H5支付類型
    const CHANNELTYPE_WEIXIN_H5 = 'hw';
    const CHANNELTYPE_ALIPAY_H5 = 'ha';

    //扫码類型
    const SCANTYPE_ALIPAY    = 'ap';   //支付宝
    const SCANTYPE_ALIPAY_H5 = 'aph5'; //支付宝H5
    const SCANTYPE_WEIXIN    = 'wp';   //微信
    const SCANTYPE_WEIXIN_H5 = 'wph5'; //微信H5

    //支付类型
    const PAYTYPE_ALIPAY     = 'DB0001';
    const PAYTYPE_ALIPAY_WAP = 'DB0002';
    const PAYTYPE_ALIPAY_H5  = 'DB0003';
    const PAYTYPE_WEIXIN     = 'DB0005';
    const PAYTYPE_WEIXIN_H5  = 'DB00011';
    const PAYTYPE_ONLINEBANK = 'DB00014';

	const RETURN_SUCCESS_CODE = 'ok';
    const RETURN_FAILED_CODE = 'failed';

	const REQUEST_QRCODE_SUCCESS = 'success';
    const PAY_RESULT_SUCCESS = '1'; //[网银/扫碼]订单状态: 0充值中，1充值成功，2充值失败
    const PAT_RESULT_QUICKPAY_SUCCESS = '3'; //[快捷H5]订单状态3:付款成功,4:付款失敗

	# Implement these for specific pay type
	protected abstract function configParams(&$params, $direct_pay_extra_info);
	protected abstract function processPaymentUrlForm($params);

    public function getSecretInfoList() {
        $secretsInfo = array('live_key', 'live_secret', 'sandbox_key', 'sandbox_secret', 'cpay_priv_key');
        return $secretsInfo;
    }

	public function generatePaymentUrlForm($orderId, $playerId, $amount, $orderDateTime, $playerPromoId = null, $enabledSecondUrl = true, $bankId = null) {
		if ($this->shouldRedirect($enabledSecondUrl)) {
			$url = $this->CI->utils->getPaymentUrl($this->getSystemInfo('second_url'), $this->getPlatformCode(), $amount, $playerId, $playerPromoId, false, $bankId, $orderId);
			$result = array('success' => true, 'type' => self::REDIRECT_TYPE_URL, 'url' => $url);
			return $result;
		}

		$order = $this->CI->sale_order->getSaleOrderById($orderId);
        $params['usercode'] = $this->getSystemInfo("account");
        $params['customno'] = $order->secure_id;
        $params['productname'] = 'Deposit';
        $params['money'] = $this->convertAmountToCurrency($this->randAmount($amount));
        $params['sendtime'] = $orderDateTime->format('Ymdhis');
        $params['notifyurl'] = $this->getNotifyUrl($orderId);
        $params['buyerip'] = $this->utils->getIP();
        $params['userdomain'] = $this->CI->utils->site_url_with_http();
        $params['currency'] = "RMB";
		$this->configParams($params, $order->direct_pay_extra_info);
		$params['sign'] = $this->sign($params);

		$this->CI->utils->debug_log("=====================cpay generatePaymentUrlForm", $params);

        $cipherStr = json_encode($params, JSON_UNESCAPED_SLASHES);
        $cipherData = $this->getCipher($cipherStr);
        $params['cipherData'] = $cipherData;

		return $this->processPaymentUrlForm($params);
	}

    protected function process($params) {

        $cipherData['cipherData'] = $params['cipherData'];
        $submit = json_encode($cipherData, JSON_UNESCAPED_SLASHES);
        $this->CI->utils->debug_log('=====================cpay process submit', $submit);

        $url = $this->getSystemInfo('url');

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $submit);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->getTimeoutSecond());
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->getConnectTimeout());
        $this->setCurlProxyOptions($ch);

        $response    = curl_exec($ch);
        $errCode     = curl_errno($ch);
        $error       = curl_error($ch);
        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $header      = substr($response, 0, $header_size);
        $content     = substr($response, $header_size);
        $statusCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $this->CI->utils->debug_log('url', $url, 'params', $submit , 'response', $response, 'errCode', $errCode, 'error', $error, 'statusCode', $statusCode);

        #save response result
        $response_result_id = $this->submitPreprocess($params, $content, $url, $response, array('errCode' => $errCode, 'error' => $error, 'statusCode' => $statusCode), $params['customno']);
        $this->CI->utils->debug_log('=====================cpay process content', $content);

        $content = json_decode($content, true);
        $this->CI->utils->debug_log('=====================cpay process json decoded content', $content);

        return $content;
    }

	protected function processPaymentUrlFormPost($content) {
        $msg = lang('Invalidte API response');
        if($content['success']) {
            return array(
                'success' => true,
                'type' => self::REDIRECT_TYPE_URL,
                'url' => $content['data']['scanurl']
            );
        }
        else {
            if($content['resultMsg']) {
                $msg = $content['resultMsg'];
            }

            return array(
                'success' => false,
                'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
                'message' => $msg
            );
        }
	}

    protected function processPaymentUrlFormQRCode($content) {
        $msg = lang('Invalidte API response');
        if($content['success']) {
            return array(
                'success' => true,
                'type' => self::REDIRECT_TYPE_QRCODE,
                'url' => $content['data']['qrurl']
            );
        }
        else {
            if($content['resultMsg']) {
                $msg = $content['resultMsg'];
            }

            return array(
                'success' => false,
                'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
                'message' => $msg
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

        if($source == 'server'){
            if (empty($params)) {
                $raw_post_data = file_get_contents('php://input', 'r');
                $params = json_decode($raw_post_data,true);
            }

            $this->CI->utils->debug_log("=====================cpay callbackFromServer params", $params);
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
            $this->CI->sale_order->updateExternalInfo($order->id, $params['orderNo'], null, null, null, $response_result_id);
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
		$requiredFields = array(
            'orderNo', 'customNo', 'userCode', 'money', 'currency', 'status', 'sign'
		);

		foreach ($requiredFields as $f) {
			if (!array_key_exists($f, $fields)) {
				$this->writePaymentErrorLog("=====================cpay missing parameter: [$f]", $fields);
				return false;
			}
		}

        # is signature authentic?
        if (!$this->validateSign($fields)) {
            $this->writePaymentErrorLog('=====================cpay checkCallbackOrder signature Error', $fields);
            return false;
        }

        $processed = true; # processed is set to true once the signature verification pass

        if ($fields['status'] != self::PAY_RESULT_SUCCESS) {
            $this->writePaymentErrorLog('=====================cpay checkCallbackOrder payment was not successful', $fields);
            return false;
        }

        if ($this->randAmountOverLimit($order->amount, floatval($fields['money']))) {
            $this->writePaymentErrorLog("=====================cpay checkCallbackOrder payment amounts do not match, expected [$order->amount]", $fields);
            return false;
        }

        if ($fields['customNo'] != $order->secure_id) {
            $this->writePaymentErrorLog("=====================cpay checkCallbackOrder order IDs do not match, expected [$order->secure_id]", $fields);
            return false;
        }

        # everything checked ok
        return true;
	}

	public function directPay($order = null) {
		return array('success' => false); # direct pay not supported by this API
	}

	public function getBankListInfoFallback() {
		return array(
            array('label' => '工商银行', 'value' => 'ICBC'),
            array('label' => '建设银行', 'value' => 'CCB'),
            array('label' => '农业银行', 'value' => 'ABC'),
            array('label' => '招商银行', 'value' => 'CMB'),
            array('label' => '交通银行', 'value' => 'COMM'),
            array('label' => '中国银行', 'value' => 'BOC'),
            array('label' => '光大银行', 'value' => 'CEB'),
            array('label' => '民生银行', 'value' => 'CMBC'),
            array('label' => '中信银行', 'value' => 'CITIC'),
            array('label' => '兴业银行', 'value' => 'CIB'),
            array('label' => '广东发展银行', 'value' => 'GDB'),
            array('label' => '浦东发展银行', 'value' => 'SPDB'),
            array('label' => '平安银行', 'value' => 'SZPAB'),
            array('label' => '华夏银行', 'value' => 'HXB'),
            array('label' => '宁波银行', 'value' => 'NBCB'),
            array('label' => '北京银行', 'value' => 'BCCB'),
            array('label' => '东亚银行', 'value' => 'HKBEA'),
            array('label' => '上海银行', 'value' => 'BOS'),
            array('label' => '中国邮政储蓄银行', 'value' => 'PSBC'),
            array('label' => '南京银行', 'value' => 'NJCB'),
            array('label' => '渤海银行', 'value' => 'CBHB'),
            array('label' => '成都银行', 'value' => 'BOCD'),
            array('label' => '上海市农村商业银行', 'value' => 'SHRCB'),
            array('label' => '广州市农村商业银行', 'value' => 'GNXS'),
            array('label' => '汉口银行', 'value' => 'HKBCHINA'),
            array('label' => '晋商银行', 'value' => 'SXJS'),
            array('label' => '珠海市农村商业银行', 'value' => 'ZHNX'),
            array('label' => '温州银行', 'value' => 'WZCB'),
            array('label' => '尧都农村商业银行', 'value' => 'YDXH'),
            array('label' => '顺德农村商业银行', 'value' => 'SDE')
		);
	}

	# -- Private functions --
	private function getNotifyUrl($orderId) {
		return parent::getCallbackUrl('/callback/process/' . $this->getPlatformCode() . '/' . $orderId);
	}

	private function getReturnUrl($orderId) {
		return parent::getCallbackUrl('/callback/browser/success/' . $this->getPlatformCode() . '/' . $orderId);
	}

	protected function convertAmountToCurrency($amount) {
		return number_format($amount, 2, '.', '');
	}

    public function sign($params) {
        $keys = array('usercode', 'customno', 'scantype', 'notifyurl', 'money', 'sendtime', 'buyerip');
        $signStr = "";
        foreach($keys as $key) {
            if (array_key_exists($key, $params)) {
                $signStr .= $params[$key] . '|';
            }
        }
        $signStr .= $this->getSystemInfo('key');
        $sign = md5($signStr);
        return $sign;
    }

    public function validateSign($params) {
        $keys = array('orderNo', 'customNo', 'resultMsg', 'bankCode', 'userCode', 'money', 'currency', 'status', 'orderType');
        $signStr = "";
        foreach($keys as $key) {
            if (array_key_exists($key, $params)) {
                if($key == 'money'){
                    $signStr .= number_format($params[$key], 3, '.', '') . '|'; #注意money字段必须保留三位小数
                }
                else{
                    $signStr .= $params[$key] . '|';
                }
            }
        }
        $signStr .= $this->getSystemInfo('key');
        $sign = md5($signStr);
        return strcasecmp($sign, $params['sign']) === 0;
    }

	protected function getPrivKey() {
		$cpay_priv_key = $this->getSystemInfo('cpay_priv_key');

        $priv_key = '-----BEGIN RSA PRIVATE KEY-----' . PHP_EOL . chunk_split($cpay_priv_key, 64, PHP_EOL) . '-----END RSA PRIVATE KEY-----' . PHP_EOL;
        return openssl_get_privatekey($priv_key);
	}

    public function getCipher($cipherStr){
        $split_arr = str_split($cipherStr, 117);

        $cipherData = '';
        for ($i = 0; $i < count($split_arr); $i++) {
            openssl_private_encrypt($split_arr[$i], $temp, $this->getPrivKey());
            $cipherData .= $temp;
        }
        return base64_encode($cipherData);
    }
}
