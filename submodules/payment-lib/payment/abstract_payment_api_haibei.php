<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * haibei  海貝
 *
 * * 'HAIBEI_PAYMENT_API', ID 963
 *
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
abstract class Abstract_payment_api_haibei extends Abstract_payment_api {

	const DEFAULT_CURRENCY = 'CNY';
    const PAYTYPE_ONLINEBANK = "1003";

	const RETURN_SUCCESS_CODE = 'SUCCESS';
    const RETURN_FAILED_CODE = 'FAIL';
    const REQUEST_SUCCESS = '0';
	const PAY_RESULT_SUCCESS = 'SUCCESS';

	# Implement these for specific pay type
	protected abstract function configParams(&$params, $direct_pay_extra_info);
	protected abstract function processPaymentUrlForm($params);

    public function getSecretInfoList() {
        $secretsInfo = array('live_key', 'live_secret', 'sandbox_key', 'sandbox_secret', 'ehking_pub_key');
        return $secretsInfo;
    }

	public function generatePaymentUrlForm($orderId, $playerId, $amount, $orderDateTime, $playerPromoId = null, $enabledSecondUrl = true, $bankId = null) {
		if ($this->shouldRedirect($enabledSecondUrl)) {
			$url = $this->CI->utils->getPaymentUrl($this->getSystemInfo('second_url'), $this->getPlatformCode(), $amount, $playerId, $playerPromoId, false, $bankId, $orderId);
			$result = array('success' => true, 'type' => self::REDIRECT_TYPE_URL, 'url' => $url);
			return $result;
		}

		$order = $this->CI->sale_order->getSaleOrderById($orderId);

		$params = array();
        $params['merchantId'] 	= $this->getSystemInfo('ehking_merchantId');
		$params['orderAmount'] = $this->convertAmountToCurrency($amount);
		$params['orderCurrency'] = self::DEFAULT_CURRENCY;
		$params['requestId'] = $order->secure_id;
		$params['notifyUrl'] = $this->getNotifyUrl($orderId);
		$params['callbackUrl'] = $this->getReturnUrl($orderId);
		$params['clientIp'] = $this->getClientIP();
		$params['productDetails'] = array(
			array(
				"name" => 'Deposit',
				"quantity" => "1",
				"amount" => $params['orderAmount'],
				"receiver" => "",
				"description" => "Deposit"
			)
		);
		$params['payer'] = (object)[];
		$this->configParams($params, $order->direct_pay_extra_info);
		$params['hmac'] = $this->sign($params);
		$this->CI->utils->debug_log("=====================haibei generatePaymentUrlForm", $params);

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

   	public function postForm($params) {
		try {
			$url = $this->getSystemInfo('url');
			$encryptKey = $this->encryptKey($params);
			$post_data = $encryptKey['data'];
			$curl = curl_init($this->absoluteUrl($url));
			curl_setopt($curl,CURLOPT_HEADER, 1 ); // 过滤HTTP头
			curl_setopt($curl,CURLOPT_HTTPHEADER,array(
				'Content-Type: application/vnd.paystack-v2.0+json',
				'encryptKey: '.$encryptKey['encryptKey'],
            	'merchantId: '.$encryptKey['merchantId'],
            	'requestId: '.$encryptKey['requestId']
			));

			curl_setopt($curl,CURLOPT_RETURNTRANSFER, 1);// 显示输出结果
			curl_setopt($curl, CURLOPT_NOBODY, 1);
			curl_setopt($curl,CURLOPT_POST,true); // post传输数据
			curl_setopt($curl,CURLOPT_POSTFIELDS,$post_data);// post传输数据

			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);//SSL证书认证
			curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);//严格认证

			$this->CI->utils->debug_log("============================haibei POSTing fields", $params);

			$responseText = curl_exec($curl);
			if (curl_errno($curl) || $responseText === false) {
				$this->CI->utils->error_log("============================haibei Curl error: ", curl_errno($curl));
				curl_close($curl);
			}
			curl_close($curl);

			preg_match_all('/(encryptKey|merchantId|data"):(\s+|")([^"\s]+)/s',$responseText,$m);
       		list($encryptKey, $merchantId, $data) = $m[3];
        	$responsedata = array("data" =>$data,"encryptKey"=>$encryptKey,"merchantId"=>$merchantId);

        	$this->CI->utils->debug_log("============================haibei postForm responseText : ", $responseText);
        	$this->CI->utils->debug_log("============================haibei postForm responsedata : ", $responsedata);

			$data = $this->checkHmac($responsedata);
			$this->CI->utils->debug_log("============================haibei postForm data: ", $data);

			$msg = lang('Invalidate API response');

	        if(!empty($data['redirectUrl'])) {
	            return array(
	                'success' => true,
	                'type' => self::REDIRECT_TYPE_URL,
	                'url' => $data['redirectUrl'],
	            );
	        }else {
	            if(!empty($data['status'])) {
	                $msg = $data['status'];
	            }
	            return array(
	                'success' => false,
	                'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
	                'message' => $msg
	            );
	        }

		} catch (Exception $e) {
			$this->CI->utils->error_log('============================haibei POST failed', $e);
			return '';
		}
	}

	public function encryptKey($params) {
        /*
        * 生成16位随机数（AES秘钥）
        */
        $str1='ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz1234567890';
        $randStr = str_shuffle($str1);//打乱字符串
        $rands= substr($randStr,0,16);

		$jsondecode= json_encode($params,true);
		$screct_key = $rands;
        $str = trim($jsondecode);
        $str = $this->addPKCS7Padding($jsondecode);
        $iv = @mcrypt_create_iv(@mcrypt_get_iv_size(MCRYPT_RIJNDAEL_128,MCRYPT_MODE_ECB),MCRYPT_RAND);
        $encrypt_str =  @mcrypt_encrypt(MCRYPT_RIJNDAEL_128, $screct_key, $str, MCRYPT_MODE_ECB, $iv);
        $date = base64_encode($encrypt_str);

        openssl_public_encrypt($rands,$encryptKey, $this->getPubKey(),OPENSSL_PKCS1_PADDING);
        $encryptKey = base64_encode($encryptKey);

        $result = array(
            "data"       => $date,
            "encryptKey" => $encryptKey,
            "merchantId" => $params['merchantId']
        );

        if(!is_null($params['requestId'])){
            $result['requestId'] = $params['requestId'];
        }

        return $result;
	}

	private function getPubKey() {
		$ehking_pub_key = $this->getSystemInfo('ehking_pub_key');

		$pem = chunk_split($ehking_pub_key,64,"\n");//转换为pem格式的公钥
        $pem = "-----BEGIN PUBLIC KEY-----\n".$pem."-----END PUBLIC KEY-----\n";
        $publicKey = openssl_pkey_get_public($pem);//获取公钥内容
		return $publicKey;
	}

	public function addPKCS7Padding($source){
        $source = trim($source);
        $block = @mcrypt_get_block_size('rijndael-128', 'ecb');
        $pad = $block - (strlen($source) % $block);
        if ($pad <= $block) {
            $char = chr($pad);
            $source .= str_repeat($char, $pad);
        }
        return $source;
    }

    public function checkHmac($data)
    {
        /*
         * RSA公钥解密
         *
         */
        $encryptKey =$data['encryptKey'];
        $pi_key =  openssl_pkey_get_public($this->getPubKey());
        openssl_public_decrypt(base64_decode($encryptKey),$decrypted,$pi_key);


        /*
         * AES解密
         *
         */

        $date = base64_decode($data['data']);
        $screct_key = $decrypted;
        $iv = @mcrypt_create_iv(@mcrypt_get_iv_size(MCRYPT_RIJNDAEL_128,MCRYPT_MODE_ECB),MCRYPT_RAND);
        $encrypt_str =  @mcrypt_decrypt(MCRYPT_RIJNDAEL_128, $screct_key, $date, MCRYPT_MODE_ECB, $iv);
        $encrypt_str = preg_replace('/[\x00-\x1F]/','', $encrypt_str);
        $encrypt_str = json_decode($encrypt_str,true);


        /*
         * 去除空值的元素
         */

        function clearBlank($arr)
        {
            function odd($var)
            {
                return($var<>'');//return true or false
            }
            return (array_filter($arr, "odd"));
        }

        function array_remove_empty(& $arr, $trim = true){
            foreach ($arr as $key => $value) {
                if (is_array($value)) {
                    array_remove_empty($arr[$key]);
                } else {
                    $value = trim($value);
                    if ($value == '') {
                        unset($arr[$key]);
                    } elseif ($trim) {
                        $arr[$key] = $value;
                    }
                }
            }
        }
        $encrypt_str = clearBlank($encrypt_str);
        return $encrypt_str;
	}

	private function absoluteUrl($url, $param=null)
    {
        if ($param !== null) {
            $parse = parse_url($url);

            $port = '';
            if ( ($parse['scheme'] == 'http') && ( empty($parse['port']) || $parse['port'] == 80) ){
                $port = '';
            }else{
                $port = $parse['port'];
            }
            $url = $parse['scheme'].'//'.$parse['host'].$port. $parse['path'];

            if(!empty($parse['query'])){
                parse_str($parse['query'], $output);
                $param = array_merge($output, $param);
            }
            $url .= '?'. http_build_query($param);
        }
        $this->CI->utils->debug_log("============================haibei absoluteUrl url", $url);
        return $url;
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
        $result = array('success' => false, 'next_url' => null, 'message' => lang('error.payment.failed'));
        $order = $this->CI->sale_order->getSaleOrderById($orderId);
        $processed = false;

        $this->CI->utils->debug_log("=====================haibei callbackFrom $source params", $params);

        if($source == 'server'){
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
            $this->CI->sale_order->updateExternalInfo($order->id, $params['requestId'], null, null, null, $response_result_id);
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
		$requiredFields = array('merchantId', 'requestId','serialNumber','totalRefundCount','totalRefundAmount','orderCurrency','orderAmount','status','completeDateTime','hmac');

		foreach ($requiredFields as $f) {
			if (!array_key_exists($f, $fields)) {
				$this->writePaymentErrorLog("=====================haibei missing parameter: [$f]", $fields);
				return false;
			}
		}

		if ($fields['status'] != self::PAY_RESULT_SUCCESS) {
			$payStatus = $fields['status'];
			$this->writePaymentErrorLog("=====================haibei Payment was not successful, payStatus is [$payStatus]", $fields);
			return false;
		}

		if ($this->convertAmountToCurrency($order->amount) != floatval( $fields['orderAmount'] )) {
			$this->writePaymentErrorLog("=====================haibei Payment amounts do not match, expected [$order->amount]", $fields);
			return false;
		}

        if ($fields['requestId'] != $order->secure_id) {
            $this->writePaymentErrorLog("========================haibei checkCallbackOrder order IDs do not match, expected [$order->secure_id]", $fields);
            return false;
        }

        # is signature authentic?
        if (!$this->verifySignature($fields)) {
            $this->writePaymentErrorLog('=======================haibei checkCallbackOrder verify signature Error', $fields);
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
            array('label'=>'工商银行', 'value' => 'BANK_CARD-B2C-ICBC-P2P'),
			array('label'=>'中国银行', 'value' => 'BANK_CARD-B2C-BOC-P2P'),
			array('label'=>'交通银行', 'value' => 'BANK_CARD-B2C-BOCO-P2P'),
			array('label'=>'建设银行', 'value' => 'BANK_CARD-B2C-CCB-P2P'),
			array('label'=>'平安银行', 'value' => 'BANK_CARD-B2C-PINGANBANK-P2P'),
			array('label'=>'光大银行', 'value' => 'BANK_CARD-B2C-CEB-P2P'),
			array('label'=>'民生银行', 'value' => 'BANK_CARD-B2C-CMBC-P2P'),
			array('label'=>'农业银行', 'value' => 'BANK_CARD-B2C-ABC-P2P'),
			array('label'=>'广发银行', 'value' => 'BANK_CARD-B2C-GDB-P2P'),
			array('label'=>'招商银行', 'value' => 'BANK_CARD-B2C-CMBCHINA-P2P'),
			array('label'=>'中信银行', 'value' => 'BANK_CARD-B2C-ECITIC-P2P'),
			array('label'=>'邮政储蓄银行', 'value' => 'BANK_CARD-B2C-POST-P2P'),
			array('label'=>'深圳发展银行', 'value' => 'BANK_CARD-B2C-SDB-P2P'),
			array('label'=>'北京银行', 'value' => 'BANK_CARD-B2C-BCCB-P2P'),
			array('label'=>'上海银行', 'value' => 'BANK_CARD-B2C-SHB-P2P'),
			array('label'=>'浦发银行', 'value' => 'BANK_CARD-B2C-SPDB-P2P'),
			array('label'=>'兴业银行', 'value' => 'BANK_CARD-B2C-CIB-P2P'),
			array('label'=>'华夏银行', 'value' => 'BANK_CARD-B2C-HXB-P2P'),

			# WEIXIN
			array('label'=>'微信直连', 'value' => 'SCANCODE-WEIXIN_PAY-P2P'),
		);
	}

    # -- Private functions --
    # After payment is complete, the gateway will invoke this URL asynchronously
    private function getNotifyUrl($orderId) {
        return parent::getCallbackUrl('/callback/process/' . $this->getPlatformCode() . '/' . $orderId);
    }

    ## After payment is complete, the gateway will send redirect back to this URL
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

	# -- private helper functions --

	/**
	 * detail: getting the signature
	 *
	 * @param array $data
	 * @return	string
	 */
	public function sign($params) {
		$strb = "";
		$keySequence = array(
			# defines the order of the param value in the signing string
			//ignore payer, always empty
			'merchantId', 'orderAmount', 'orderCurrency', 'requestId', 'notifyUrl', 'callbackUrl', 'remark', 'paymentModeCode', 'productDetails', 'bankCard', 'cashierVersion', 'forUse', 'merchantUserId', 'bindCardId', 'clientIp', 'timeout'
		);
		asort($keySequence);
		foreach ($keySequence as $key) {
			if(!array_key_exists($key, $params)) {
				continue;
			}
			$val = $params[$key];
			if (empty($val)) {
				continue;
			}
			if (is_array($val)) {
				# the only sub-array we use is productDetail, and there is only one product
				$productDetailKeySequence = array('name', 'quantity', 'amount', 'receiver', 'description');
				$substrb = "";
				asort($productDetailKeySequence);
				foreach($productDetailKeySequence as $subKey) {
					if(!empty($val[0][$subKey])) {
						$substrb .= $val[0][$subKey].'#';
					}
				}
				$strb .= $substrb;
			}
			else {
				$strb .= $val.'#';
			}
		}

		return hash_hmac("md5", $strb, $this->getSystemInfo('key'));
	}

    public function verifySignature($data) {
	    $callback_sign = $data['hmac'];
        $strb = "";
		$keySequence = array(
			# defines the order of the param value in the signing string
			'merchantId', 'requestId', 'serialNumber', 'totalRefundCount', 'totalRefundAmount',
			'orderCurrency', 'orderAmount', 'status', 'completeDateTime', 'remark'
		);
		foreach ($keySequence as $key) {
			if(!array_key_exists($key, $data)) {
				continue;
			}
			$val = $data[$key];
			$strb .= $val.'#';
		}
		$sign = hash_hmac("md5", $strb, $this->getSystemInfo('key'));//md5($strb);
        return $sign == $callback_sign;
    }
}
