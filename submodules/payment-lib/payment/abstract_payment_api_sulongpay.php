<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';
/**
 * SULONGPAY 
 *
 * * SULONGPAY_ALIPAY_PAYMENT_API,      ID: 5271
 * * SULONGPAY_ALIPAY_H5_PAYMENT_API,   ID: 5272
 * * SULONGPAY_WEIXIN_PAYMENT_API,      ID: 5273
 * * SULONGPAY_PAYMENT_API,             ID: 5274
 * * SULONGPAY_QQPAY_PAYMENT_API,       ID: 5275 
 * * SULONGPAY_QQPAY_H5_PAYMENT_API,    ID: 5276 
 * * SULONGPAY_UNIONPAY_PAYMEPNT_API,   ID: 5277
 * * SULONGPAY_UNIONPAY_H5_PAYMENT_API, ID: 5278
 *
 * Required Fields:
 * * Account
 * * Extra Info
 *
 * Field Values:
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 * * Extra Info:
 * > {  
 * >    "gateway_url": "http://pay.sulongpay.com/gateway/payment"
 * > }
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
abstract class Abstract_payment_api_sulongpay extends Abstract_payment_api {

	
	const PAYTYPE_ALIPAY    = 'alipay';
	const PAYTYPE_WEIXIN    = 'wechat';
	const PAYTYPE_QQPAY     = 'qq';
    const PAYTYPE_UNIONPAY  = 'code';
    const PAYTYPE_BANK      = 'bank';


	const RETURN_SUCCESS_CODE = '00';

	const RETURN_PAYSTATUS_SUCCESS_CODE = '1';
	const RETURN_SUCCESS = 'success';


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
		$params['customerid'] = $this->getSystemInfo("account");
		$params['orderid'] = $order->secure_id;
		$params['total_fee'] = $this->convertAmountToCurrency($amount);
		$this->configParams($params, $order->direct_pay_extra_info);
		$params['notify_url'] = $this->getNotifyUrl($orderId);
        $params['returnUrl'] = $this->getReturnUrl($orderId);
		$params['nonce_str'] = $this->getRandomString(32);
		$params['sign'] = $this->sign($params);

        $this->CI->utils->debug_log('=========================sulongpay generatePaymentUrlForm', $params);
   
		return $this->processPaymentUrlForm($params);
	}

	# Submit POST form
	protected function processPaymentUrlFormPost($params) {
        return array(
            'success' => true,
            'type' => self::REDIRECT_TYPE_FORM,
            'url' => $this->getSystemInfo('gateway_url'),
            'params' => $params,
            'post' => true,
        );
    }



    # Display QRCode get from curl
    protected function processPaymentUrlFormQRCode($params) { 
        $response = $this->curl_post($this->getSystemInfo('url'), $params);
        $response = $this->xml_parser($response);
        $this->CI->utils->debug_log('=====================sulongpay processPaymentUrlFormQRCode response', $response);

        if($response['retcode'] == self::RETURN_SUCCESS_CODE) {
			if( $params['trade_type'] == self::PAYTYPE_ALIPAY){
				return array(
					'success' => true,
					'type' => self::REDIRECT_TYPE_URL,
					'url' => $response['data']['payurl']
				);
			}
			else{
				return array(
					'success' => true,
					'type' => self::REDIRECT_TYPE_QRCODE,
					'url' => $response['data']['payurl']
				);
			}
        }
        else if($response['retcode'] != self::RETURN_SUCCESS_CODE) {
            return array(
                'success' => false,
                'type' => self::REDIRECT_TYPE_ERROR,
                'message' => $response['retcode'].': '.$response['retmsg']
            );
        }
        else {
            return array(
                'success' => false,
                'type' => self::REDIRECT_TYPE_ERROR,
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
        $this->CI->utils->debug_log('=======================sulongpay callbackFrom in Function callbackFrom', $params);

        $result = array('success' => false, 'next_url' => null, 'message' => lang('error.payment.failed'));
        $order = $this->CI->sale_order->getSaleOrderById($orderId);
        $processed = false;

        if($source == 'server'){
			$raw_post_data = file_get_contents('php://input', 'r'); 
			$this->CI->utils->debug_log("=====================sulongpay raw_post_data", $raw_post_data);

			$params = $this->xml_parser($raw_post_data);
			$this->CI->utils->debug_log("=====================sulongpay xml_parser params", $params);

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
                $params['order_no'], $params['bank_code'],
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
			$result['message'] = "FAIL";
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
		$requiredFields = array('customerid','orderid','nonce_str','pay_status','total_fee','sign');

		foreach ($requiredFields as $f) {
			if (!array_key_exists($f, $fields['data'])) {
                $this->writePaymentErrorLog("=====================sulongpay missing parameter: [$f]", $fields['data']);
				return false;
			}
		}
		# is signature authentic?
		
		if (!$this->validateSign($fields['data'])) {
			$this->writePaymentErrorLog('=====================sulongpay checkCallbackOrder Signature Error', $fields['data']);
			return false;
		}

		$processed = true; # processed is set to true once the signature verification pass

		if ($fields['data']['pay_status'] != self::RETURN_PAYSTATUS_SUCCESS_CODE) {
			$payStatus = $fields['data']['pay_status'];
			$this->writePaymentErrorLog("=====================sulongpay Payment was not successful, payStatus is [$payStatus]", $fields['data']);
			return false;
		}

		if ($fields['data']['total_fee'] != $this->convertAmountToCurrency($order->amount)) {
            $this->writePaymentErrorLog("======================sulongpay checkCallbackOrder Payment amount is wrong, expected [$order->amount]", $fields['data']);
            return false;
        }

        if ($fields['data']['orderid'] != $order->secure_id) {
            $this->writePaymentErrorLog("========================sulongpay checkCallbackOrder order IDs do not match, expected [$order->secure_id]", $fields['data']);
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
			array('value' => 'ICBC', 'label' =>  '工商银行' ),
			array('value' => 'CMB', 'label' =>  '招商银行' ),
			array('value' => 'CCB', 'label' =>  '建设银行' ),
			array('value' => 'ABC', 'label' =>  '农业银行' ),
			array('value' => 'BOCOM', 'label' =>  '交通银行' ),
			array('value' => 'BOC', 'label' =>  '中国银行' ),
			array('value' => 'SDB', 'label' =>  '深圳发展银行' ),
			array('value' => 'GDB', 'label' =>  '广东发展银行' ),
			// array('value' => 'DRCBANK', 'label' =>  '东莞农商银行' ),
			array('value' => 'CITIC', 'label' =>  '中信银行' ),
			array('value' => 'CMBC', 'label' =>  '民生银行' ),
			array('value' => 'PSBC', 'label' =>  '中国邮政储蓄银行' ),
			array('value' => 'CIB', 'label' =>  '兴业银行' ),
			array('value' => 'HXB', 'label' =>  '华夏银行' ),
			array('value' => 'PINAN', 'label' =>  '平安银行' ),
			// array('value' => 'GX966888', 'label' =>  '广西农村信用社' ),
			array('value' => 'GZCB', 'label' =>  '广州银行' ),
			// array('value' => 'NJCB', 'label' =>  '南京银行' ),
			// array('value' => 'GNXS', 'label' =>  '广州农商银行' ),
			array('value' => 'CEB', 'label' =>  '光大银行' )
		);
	}

	protected function convertAmountToCurrency($amount) {
		return number_format($amount, 0);
	}

	private function getNotifyUrl($orderId) {
		return parent::getCallbackUrl('/callback/process/' . $this->getPlatformCode() . '/' . $orderId);
	}

	private function getReturnUrl($orderId) {
		return parent::getCallbackUrl('/callback/browser/success/' . $this->getPlatformCode() . '/' . $orderId);
	}
    

    # -- 生成随机字符串 --
    private function getRandomString($len) {
        $chars = array('a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p', 'q', 'r', 's', 't', 'u', 'v', 'w', 'x', 'y', 'z', "A", "B", "C", "D", "E", "F", "G", "H", "I", "J", "K", "L", "M", "N", "O", "P", "Q", "R", "S", "T", "U", "V", "W", "X", "Y", "Z", "0", "1", "2", "3", "4", "5", "6", "7", "8", "9");
        $charsLen = count($chars) - 1;
        shuffle($chars);
        $output = "";
        for ($i = 0; $i < $len; $i++) {
            $output .= $chars[mt_rand(0, $charsLen)];
        }
        $output = substr(md5(md5(uniqid()) . md5(microtime()) . md5($output)), 0, $len);
        return $output;
    }

	# -- signatures --
     private function sign($params) {
        $signStr = $this->createSignStr($params);
        $sign = strtoupper(md5($signStr));
        return $sign;
    }

    private function createSignStr($params) {
		if( $params['trade_type'] == self::PAYTYPE_UNIONPAY || $params['trade_type'] == self::PAYTYPE_QQPAY){
			$params = array('customerid'=>$params['customerid'],'orderid'=>$params['orderid'],'total_fee'=>$params['total_fee'],'trade_type'=>$params['trade_type'],'notify_url'=>$params['notify_url'],'nonce_str'=>$params['nonce_str'],'buyername'=>$params['buyername'],'subject'=>$params['subject'],'client_ip'=>$params['client_ip']);
		}
		else{
			$params = array('customerid'=>$params['customerid'],'orderid'=>$params['orderid'],'total_fee'=>$params['total_fee'],'trade_type'=>$params['trade_type'],'notify_url'=>$params['notify_url'],'nonce_str'=>$params['nonce_str']);
		}
        $params = array_filter($params);
        ksort($params); 

		$signStr = '';
        foreach ($params as $key => $val) {
            if($val === null ){ 
                
                continue;
            }
			$signStr = urldecode(http_build_query($params)) . "&key=".$this->getSystemInfo('key');
        }

		return $signStr;
    }

    private function validateSign($params) {
        $keys = array('customerid'=>$params['customerid'],'orderid'=>$params['orderid'],'nonce_str'=>$params['nonce_str'],'pay_status'=>$params['pay_status'],'time'=>$params['time'],'total_fee'=>$params['total_fee']);
        $keys = array_filter($keys);
		ksort($keys);
		
        $signStr = '';
        foreach ($keys as $key => $val) {
            if($val === null ){
                
                continue;
            }
			$signStr = urldecode(http_build_query($keys)) . "&key=".$this->getSystemInfo('key');
        }

		$sign = strtoupper(md5($signStr));
		if($params['sign'] == $sign){
			return true;
		}
		else{
			$this->writePaymentErrorLog("===================sulongpay Validating Signing [$signStr], signature is [$sign], match? ", $params['sign']);
			return false;
		}
	}


    // 解析xml格式数据
    public function xml_parser($str) {
        $this->CI->utils->debug_log('=====================sulongpay xml_parser str', $str);
        //禁止引用外部xml实体
        libxml_disable_entity_loader(true);
        // 获取到的post请求过来的参数转数组
        $params = json_decode(json_encode(simplexml_load_string($str, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
        return $params;
        $this->CI->utils->debug_log('=====================sulongpay xml_parser params', $params);
    }

    /**
     * 数据XML编码
     * @param mixed $data 数据
     * @return string
     */
    private function data_to_xml($data) {
        $xml = '';
        foreach ($data as $key => $val) {
            is_numeric($key) && $key = "item id=\"$key\"";
            $xml .= "<$key>";
            $xml .= ( is_array($val) || is_object($val)) ? data_to_xml($val) : "<![CDATA[" . $val . "]]>";
            list($key, ) = explode(' ', $key);
            $xml .= "</$key>";
        }
        return $xml;
    }

    /**
     * XML编码
     * @param mixed $data 数据
     * @param string $encoding 数据编码
     * @param string $root 根节点名
     * @return string
     */
    private function xml_encode($data, $encoding = 'utf-8', $root = 'xml') {
        $xml = '<xml>';
        $xml .= $this->data_to_xml($data);
        $xml .= '</' . $root . '>';
        return $xml;
    }
    



    # -- curl_post --
    private function curl_post($url, $params) {
            $xml = $this->xml_encode($params);// 转换为xml
            $this->CI->utils->debug_log('=====================sulongpay curl_post xml', $xml);

            // 模拟提交数据函数
            $curl = curl_init(); // 启动一个CURL会话
            $header = array(
                "Content-Type: text/xml; charset=utf-8"
            );
            curl_setopt($curl, CURLOPT_URL, $url); // 要访问的地址
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0); // 对认证证书来源的检查
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2); // 从证书中检查SSL加密算法是否存在
            curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
            curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1); // 使用自动跳转
            curl_setopt($curl, CURLOPT_AUTOREFERER, 1); // 自动设置Referer
            curl_setopt($curl, CURLOPT_POST, 1); // 发送`一个常规的Post请求
            curl_setopt($curl, CURLOPT_POSTFIELDS, $xml); // Post提交的数据包
            curl_setopt($curl, CURLOPT_TIMEOUT, 30); // 设置超时限制防止死循环
            curl_setopt($curl, CURLOPT_HEADER, 0); // 显示返回的Header区域内容
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1); // 获取的信息以文件流的形式返回

            $response = curl_exec($curl);
            $errCode = curl_errno($curl);
            $error = curl_error($curl);
			$header_size = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
			$header = substr($response, 0, $header_size);
			$content = substr($response, $header_size);
            $statusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

            curl_close($curl);

            $this->CI->utils->debug_log('url', $url, 'params', $params , 'response', $response, 'errCode', $errCode, 'error', $error, 'statusCode', $statusCode);
            
            #save response result
            $response_result_id = $this->submitPreprocess($params, $content, $url, $response, array('errCode' => $errCode, 'error' => $error, 'statusCode' => $statusCode),$params['orderid']);
            return $response;
		}
}