<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * FKTPAY 福卡通支付
 *
 * * FKTPAY_PAYMENT_API, ID: 579
 * * FKTPAY_ALIPAY_PAYMENT_API, ID: 580
 * * FKTPAY_WEIXIN_PAYMENT_API, ID: 581
 * * FKTPAY_QQPAY_PAYMENT_API, ID: 582
 * * FKTPAY_JDPAY_PAYMENT_API, ID: 583
 *
 * Required Fields:
 *
 * * URL
 * * Account - Merchant ID
 * * Key - Sha key
 *
 * Field Values:
 *
 * * Extra Info:
 * > {
 * >    "sellerEmail" : "## Seller email address, system will show you when the merchant opens ##"
 * > }
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */

abstract class Abstract_payment_api_fktpay extends Abstract_payment_api {

	const PAYTYPE_BANK   = '1';
    const PAYTYPE_ALIPAY = '3';
    const PAYTYPE_QQPAY  = '5';
    const PAYTYPE_WEIXIN = '2';
	const PAYTYPE_JDPAY  = '6';

	protected $cipher = MCRYPT_RIJNDAEL_128;
    protected $mode = MCRYPT_MODE_ECB;
    protected $pad_method = NULL;
    protected $secret_key = '';
    protected $iv = '';

	const RETURN_SUCCESS_CODE = 'success';


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

		$params = array();
		$params['input_charset'] = "UTF-8";
        $params['inform_url'] = $this->getNotifyUrl($orderId);
        $params['return_url']=$this->getReturnUrl($orderId);
        $params['merchant_code'] =  $this->getSystemInfo("account");
        $params['order_no'] = $order->secure_id;
        $params['order_time'] = date('Y-m-d H:i:s');
        $params['customer_ip'] = $this->getClientIp();
		$this->configParams($params, $order->direct_pay_extra_info);
		$params['order_amount'] = $this->AESkey($amount);
		$params['sign'] = $this->sign($params);

		$this->CI->utils->debug_log('=========================fktpay generatePaymentUrlForm', $params);
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



	## This will be called when the payment is async, API server calls our callback page
	## When that happens, we perform verifications and necessary database updates to mark the payment as successful
	## Reference: sample code, callback.php
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
		$this->utils->debug_log('callbackFrom' . ucfirst($source) . ': [' . $orderId .'], params:', $params);

		$result = array('success' => false, 'next_url' => null, 'message' => lang('error.payment.failed'));
		$order = $this->CI->sale_order->getSaleOrderById($orderId);

		if (!$order) {
			$this->utils->error_log("Order ID [$orderId] not found.");
			return $result;
		}

		$callbackValid = false;
		$paymentSuccessful = $this->checkCallbackOrder($order, $params, $callbackValid); # $callbackValid is also assigned

		# Do not print success msg if callback fails integrity check
		if(!$callbackValid) {
			return $result;
		}

		# Do not proceed to update order status if payment failed, but still print success msg as callback response
		if(!$paymentSuccessful) {
			$result['trade_status'] = self::RETURN_SUCCESS_CODE;
			return $result;
		}

		# We can respond with ack to callback now
		$success = true;
		$result['message'] = self::RETURN_SUCCESS_CODE;

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
			$this->CI->sale_order->updateExternalInfo($order->id, $params['trade_no'], null, null, $response_result_id);
			if ($source == 'browser') {
				$success = $this->CI->sale_order->browserCallbackSaleOrder($order->id, 'auto broswer callback ' . $this->getPlatformCode(), false);
			} elseif ($source == 'server') {
				$success = $this->approveSaleOrder($order->id, 'auto server callback ' . $this->getPlatformCode(), false);
			}
		}

		# This $success marks whether the order status update is successful
		$result['success'] = $success;

		if ($source == 'browser') {
			$result['next_url'] = $this->getPlayerBackUrl();
			$result['go_success_page'] = true;
		}

		return $result;
	}

	# returns true if callback is valid and payment is successful
	# sets the $callbackValid parameter if callback is valid
    private function checkCallbackOrder($order, $fields, &$processed = false) {
        $requiredFields = array(
            'merchant_code', 'order_no', 'order_amount', 'order_time', 'trade_status', 'trade_no', 'return_params' , 'sign'
        );
        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("=========================fktpay checkCallbackOrder Missing parameter: [$f]", $fields);
                return false;
            }
        }
        $callbackSign = $this->sign($fields);

		# is signature authentic?
		if ($fields['sign'] != $callbackSign) {
			$this->writePaymentErrorLog("=====================fktpay check callback sign error, signature is [$callbackSign], match? ", $fields);
			return false;
		}

        if (
            $this->convertAmountToCurrency($order->amount) !==
            $this->convertAmountToCurrency(floatval($fields['order_amount']))
        ) {
            $this->writePaymentErrorLog("=========================fktpay checkCallbackOrder Payment amounts do not match, expected [$order->amount]", $fields);
            return false;
        }
        # does order_no match?
        if ($fields['order_no'] !== $order->secure_id) {
            $this->writePaymentErrorLog("=========================fktpay checkCallbackOrder Order IDs do not match, expected [$order->secure_id]", $fields);
            return false;
        }
        # everything checked ok
        return true;
    }

	public function directPay($order = null) {
		return array('success' => false); # direct pay not supported by this API
	}

	# -- private helper functions --
	protected function getBankListInfoFallback() {
		return array(
			array('label' => '中国农业银行', 'value' => 'ABC'),
			array('label' => '中国银行', 'value' => 'BOC'),
			array('label' => '交通银行', 'value' => 'BOCOM'),
			array('label' => '中国建设银行', 'value' => 'CCB'),
			array('label' => '中国工商银行', 'value' => 'ICBC'),
			array('label' => '中国邮政储蓄银行', 'value' => 'PSBC'),
			array('label' => '招商银行', 'value' => 'CMBC'),
			array('label' => '浦发银行', 'value' => 'SPDB'),
			array('label' => '中国光大银行', 'value' => 'CEBBANK'),
			array('label' => '中信银行', 'value' => 'ECITIC'),
			array('label' => '平安银行', 'value' => 'PINGAN'),
			array('label' => '中国民生银行', 'value' => 'CMBCS'),
			array('label' => '华夏银行', 'value' => 'HXB'),
			array('label' => '广发银行', 'value' => 'CGB'),
			array('label' => '北京银行', 'value' => 'BCCB'),
            array('label' => '上海银行', 'value' => 'BOS'),
            array('label' => '兴业银行', 'value' => 'CIB')
		);
	}

	private function convertAmountToCurrency($amount) {
		return number_format($amount, 2, '.', '');;
	}

	private function getNotifyUrl($orderId) {
		return parent::getCallbackUrl('/callback/process/' . $this->getPlatformCode() . '/' . $orderId);
	}

	private function getReturnUrl($orderId) {
		return parent::getCallbackUrl('/callback/browser/success/' . $this->getPlatformCode() . '/' . $orderId);
	}


	# -- signing --
	public function sign($data) {

        ksort($data);
        $KEY_1 = $this->getSystemInfo('key');
		$dataStr = $this->createSignStr($data);

		$signature = MD5($dataStr);
        return $signature;
    }

	private function createSignStr($params) {
		ksort($params);
		$signStr = '';
		$KEY_1 = $this->getSystemInfo('key');
		foreach($params as $key => $value) {
			if(empty($value) || $key == 'sign') {
				continue;
			}
			$signStr .= $key."=".$value."&";
		}
		$signStr .="key=".$KEY_1;
		return $signStr;
	}

	# -- AES KEY --
	private function AESkey($amount) {
		$keyStr = $this->getSystemInfo('key');
		$plainText = number_format($amount, 2, '.', '');

		$this->set_key($keyStr);
		$this->require_pkcs5();
		$encText = $this->encrypt($plainText);
		return $encText;
	}

	public function set_cipher($cipher) {
        $this->cipher = $cipher;
    }

    public function set_mode($mode) {
        $this->mode = $mode;
    }

    public function set_iv($iv) {
        $this->iv = $iv;
    }

    public function set_key($key) {
        $this->secret_key = $key;
    }

    public function require_pkcs5() {
        $this->pad_method = 'pkcs5';
    }

    protected function pad_or_unpad($str, $ext) {
        if ( is_null($this->pad_method) ) {
            return $str;
        } else {
            $func_name = __CLASS__ . '::' . $this->pad_method . '_' . $ext . 'pad';
            if ( is_callable($func_name) ) {
                $size = @mcrypt_get_block_size($this->cipher, $this->mode);
                return call_user_func($func_name, $str, $size);
            }
        }
        return $str;
    }

    protected function pad($str) {
        return $this->pad_or_unpad($str, '');
    }

    protected function unpad($str) {
        return $this->pad_or_unpad($str, 'un');
    }

    public function encrypt($str) {
        $str = $this->pad($str);
        $td = @mcrypt_module_open($this->cipher, '', $this->mode, '');

        if (empty($this->iv)) {
            $iv = @mcrypt_create_iv(@mcrypt_enc_get_iv_size($td), MCRYPT_RAND);
        } else {
            $iv = $this->iv;
        }

        @mcrypt_generic_init($td, hex2bin($this->secret_key), $iv);
        $cyper_text = @mcrypt_generic($td, $str);
        $rt = strtoupper(bin2hex($cyper_text));
        @mcrypt_generic_deinit($td);
        @mcrypt_module_close($td);

        return $rt;
    }

    public function decrypt($str) {
        $td = @mcrypt_module_open($this->cipher, '', $this->mode, '');

        if (empty($this->iv)) {
            $iv = @mcrypt_create_iv(@mcrypt_enc_get_iv_size($td), MCRYPT_RAND);
        }
        else {
            $iv = $this->iv;
        }

        @mcrypt_generic_init($td, $this->secret_key, $iv);
        $decrypted_text = @mdecrypt_generic($td, base64_decode($str));
        $rt = $decrypted_text;
        @mcrypt_generic_deinit($td);
        @mcrypt_module_close($td);

        return $this->unpad($rt);
    }

    public static function hex2bin($hexdata) {
        $bindata = '';
        $length = strlen($hexdata);
        for ($i=0; $i< $length; $i += 2) {
            $bindata .= chr(hexdec(substr($hexdata, $i, 2)));
        }
        return $bindata;
    }

    public static function pkcs5_pad($text, $blocksize) {
        $pad = $blocksize - (strlen($text) % $blocksize);
        return $text . str_repeat(chr($pad), $pad);
    }

    public static function pkcs5_unpad($text) {
        $pad = ord($text[strlen($text) - 1]);
        if ($pad > strlen($text)) return false;
        if (strspn($text, chr($pad), strlen($text) - $pad) != $pad) return false;
        return substr($text, 0, -1 * $pad);
    }
}