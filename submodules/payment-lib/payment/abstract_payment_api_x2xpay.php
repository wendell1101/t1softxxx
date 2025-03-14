<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';
/**
 * Payment API implementation x2xpay
 */
abstract class Abstract_payment_api_x2xpay extends Abstract_payment_api {
    const RETURN_SUCCESS_CODE = '0000';
    const RETURN_FAILED_CODE = 'FAILED';
    const ERROR_CODES = array(
            "0000" => "成功",
            "0001" => "失败，交易失败",
            "0002" => "失败，系统未开放或暂时关闭，请稍后再试",
            "0003" => "失败，交易通讯超时，请发起查询交易",
            "0004" => "未定，交易状态未明，请稍后查询",
            "0005" => "未定，交易状态未明，请稍后查询",
            "0010" => "失败，报文格式错误",
            "0011" => "失败，验证失败",
            "0012" => "失败，重复交易",
            "0013" => "失败，报文交易要素缺失",
            "0020" => "失败，二维码已失效",
            "0021" => "失败，支付次数超限",
            "0022" => "失败，二维码状态错误",
            "0023" => "失败，无此二维码",
            "0030" => "失败，交易未通过",
            "0031" => "失败，商户状态不正确",
            "0032" => "失败，无此交易权限",
            "0033" => "失败，交易金额超限",
            "0034" => "失败，无此交易",
            "0037" => "失败，已超过最大查询次数或操作过于频繁",
            "0038" => "失败，风险受限",
            "0039" => "失败，交易不在受理时间范围内",
            "0060" => "失败，交易失败，详情请咨询您的发卡行",
            "0061" => "失败，输入的卡号无效，请确认后输入",
            "0062" => "失败，交易失败，发卡银行不支持该商户，请更换其他银行卡",
            "0063" => "失败，卡状态不正确",
            "0064" => "失败，卡上的余额不足",
            "0065" => "失败，输入的密码、有效期或CVN2有误，交易失败",
            "0066" => "失败，持卡人身份信息或手机号输入不正确，验证失败",
            "0067" => "失败，密码输入次数超限",
            "0068" => "失败，您的银行卡暂不支持该业务，请向您的银行或95516咨询",
            "0099" => "失败，通用错误",
            "0100" => "未定，等待交易",
            "00A6" => "失败，有缺陷的成功",
            "PR05" => "未定，审核中",
            "PR99" => "失败，审核失败",
            );
    private $info;
    public function __construct($params = null) {
        parent::__construct($params);
        // Populate $info with the following keys
        // url, key, account, secret, system_info
        $this->info = $this->getInfoByEnv();
    }

    protected abstract function configParams(&$params, $direct_pay_extra_info);

    public function getSecretInfoList() {
        $secretsInfo = array('live_key', 'live_secret', 'sandbox_key', 'sandbox_secret', 'x2xpay_priv_key');
        return $secretsInfo;
    }

    public function generatePaymentUrlForm($orderId, $playerId, $amount, $orderDateTime, $playerPromoId = null, $enabledSecondUrl = true, $bankId = null) {
        // For second url redirection
        if ($this->shouldRedirect($enabledSecondUrl)) {
            $url = $this->CI->utils->getPaymentUrl($this->getSystemInfo('second_url'), $this->getPlatformCode(), $amount, $playerId, $playerPromoId, false, $bankId, $orderId);
            $result = array('success' => true, 'type' => self::REDIRECT_TYPE_URL, 'url' => $url);
            return $result;
        }

        $order = $this->CI->sale_order->getSaleOrderById($orderId);
        $direct_pay_extra_info = $order->direct_pay_extra_info;
        $sysinfo = $this->getAllSystemInfo();

        $params['appId'] = "DEFAULT";                         // 应用标志 appId String(32) 是 固定填写DEFAULT
        $params['version'] = "1.0";                           // 版本号 version String(32) 是 固定填写1.0
        //$params['proxyMchid'] = "1.0";                      // 接口发起方代码 proxyMchid String(64) 否 调用接口的第三方代理商ＩＤ
        $params['mchid'] = $this->getSystemInfo("mchid");     // 商户代码 mchid String(64) 是 商户代码
        $params['reqNo'] = $order->secure_id;                 // 请求流水 reqNo String(32) 是 保证唯一
        $params['backURL'] = $this->getNotifyUrl($orderId);   // 异步通知URL backURL String(128) 否 异步通知的接收地址，当条码、扫码支付条件满足时，平台会向此地址推送交易信息
        //$params['total_fee'] = $this->convertAmountToCurrency($amount); // 异步通知URL backURL String(128) 否 异步通知的接收地址，当条码、扫码支付条件满足时，平台会向此地址推送交易信息
        $params['total_fee'] = $amount*100; // 异步通知URL backURL String(128) 否 异步通知的接收地址，当条码、扫码支付条件满足时，平台会向此地址推送交易信息

        $this->configParams($params, $order->direct_pay_extra_info);

        $params['sign'] = $this->sign($params); // 签名 sign String(128) 是 对报文字段的签名

        $data = array(
                "head"=>array(
                    "appId" => $params["appId"],
                    "version" => $params["version"],
                    "reqType" => $params["reqType"],
                    "mchid" => $params["mchid"],
                    "reqNo" => $params["reqNo"],
                    "channel" => $params["channel"],
                    "backURL" => $params["backURL"],
                    "sign" => $params["sign"],
                    ),
                "data"=>array(
                    "total_fee" => $params["total_fee"],
                    ),
                );
		$params["data"] = $this->array2xml( $data, "xml" );

		$this->CI->utils->debug_log('request parameters === ', $params );

		return $this->processPaymentUrlFormQRCode($params);
    }

	protected function processPaymentUrlFormQRCode($params) {
		$curlConn = curl_init();
		$curlData = array();
		$curlData[CURLOPT_POST] = true;
		$curlData[CURLOPT_URL] = $this->getSystemInfo('url');
		$this->CI->utils->debug_log('url === ', $this->getSystemInfo('url') );
		$curlData[CURLOPT_RETURNTRANSFER] = true;
		$curlData[CURLOPT_TIMEOUT] = 120;
		$curlData[CURLOPT_POSTFIELDS] = $params['data'];
        $curlData[CURLOPT_HTTPHEADER] = [ "Content-type: text/xml;charset='utf-8'" ];
		$this->CI->utils->debug_log('x2xpay xml request content === ', $params['data'] );
		curl_setopt_array($curlConn, $curlData);

		curl_setopt($curlConn, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curlConn, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($curlConn, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($curlConn, CURLOPT_SSL_VERIFYHOST, false);

		// Need to specify the referer when doing CURL submit. since we use redirect 2nd url, we can take the HTTP_HOST
		curl_setopt($curlConn, CURLOPT_REFERER, "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]");

		$curlResult = curl_exec($curlConn);
		$curlSuccess = (curl_errno($curlConn) == 0);

		$this->CI->utils->debug_log('xml response === ', $curlSuccess, $curlResult);


		$errorMsg=null;
		if($curlSuccess) {
			$xmlResult = $this->parseResultXML($curlResult);
			$isVerified = $this->verify($curlResult);

			if ($isVerified) {
				$qrCodeUrl = isset($xmlResult["data"]["pay_url"]) ? $xmlResult["data"]["pay_url"] : NULL;

				if(!$qrCodeUrl) {
					$curlSuccess = false;
					$errorCode = $xmlResult["head"]["respCd"];
					$errorMsg = self::ERROR_CODES[$errorCode];
				}
			}
		} else {
			// curl error
			$errorMsg = curl_error($curlConn);
		}

		curl_close($curlConn);

		if($curlSuccess) {
			return array(
					'success' => true,
					'type' => self::REDIRECT_TYPE_QRCODE,
					'url' => $qrCodeUrl,
					);
		} else {
			return array(
					'success' => false,
					'type' => self::REDIRECT_TYPE_ERROR,
					'message' => $errorMsg
					);
		}
	}

    // This will be called when the payment is async, API server calls our callback page
    // When that happens, we perform verifications and necessary database updates to mark the payment as successful
    // Reference: sample code, callback.php
    public function callbackFromServer($orderId, $params) {
        $response_result_id = parent::callbackFromServer($orderId, $params);
        return $this->callbackFrom('server', $orderId, $params, $response_result_id);
    }
    // This will be called when user redirects back to our page from payment API
    public function callbackFromBrowser($orderId, $params) {
        $response_result_id = parent::callbackFromBrowser($orderId, $params);
        return $this->callbackFrom('browser', $orderId, $params, $response_result_id);
    }
    // $source can be 'server' or 'browser'
    private function callbackFrom($source, $orderId, $params, $response_result_id) {
        $result = array('success' => false, 'next_url' => null, 'message' => lang('error.payment.failed'));
        $order = $this->CI->sale_order->getSaleOrderById($orderId);

        // is signature authentic?
        $raw_xml_data = file_get_contents("php://input");
        if (!$this->verify( $raw_xml_data )) {
            $this->writePaymentErrorLog('Signature Error', $fields);
            return false;
        }

        $xmldata = $this->parseResultXML($raw_xml_data);
        foreach( $xmldata["head"] as $k => $v ){
            $params[$k] = $v;
        }
        foreach( $xmldata["data"] as $k => $v ){
            $params[$k] = $v;
        }

        $processed = false;
        if (!$order || !$this->checkCallbackOrder($order, $params, $processed)) {
            return $result;
        }
        // Update order payment status and balance
        $this->CI->sale_order->startTrans();
        // Update player balance based on order status
        // if it's STATUS_SETTLED or STATUS_BROWSER_CALLBACK, put log, and ignore
        $orderStatus = $this->CI->sale_order->getSaleOrderStatusById($orderId);
        if ($orderStatus == Sale_order::STATUS_BROWSER_CALLBACK || $orderStatus == Sale_order::STATUS_SETTLED) {
            $this->CI->utils->debug_log('callbackFrom' . ucfirst($source) . ', already get callback for order:' . $order->id, $params);
            if ($source == 'server' && $order->status == Sale_order::STATUS_BROWSER_CALLBACK) {
                $this->CI->sale_order->setStatusToSettled($orderId);
            }
        } else {
            // update player balance
            $this->CI->sale_order->updateExternalInfo($order->id,
                    $params['tCode'], '', // only platform order id exist. Reference: documentation section 2.4.2
                    null, null, $response_result_id);
            if ($source == 'browser') {
                $success = $this->CI->sale_order->browserCallbackSaleOrder($order->id, 'auto broswer callback ' . $this->getPlatformCode(), false);
            } elseif ($source == 'server') {
                $this->approveSaleOrder($order->id, 'auto server callback ' . $this->getPlatformCode(), false);
            }
        }
        $success = $this->CI->sale_order->endTransWithSucc();
        $result['success'] = $success;
        if ($success) {
            $result['message'] = self::RETURN_SUCCESS_CODE;
        } else {
            $result['return_error'] = $processed ? self::RETURN_SUCCESS_CODE : self::RETURN_FAILED_CODE;
        }
        if ($source == 'browser') {
            $result['next_url'] = $this->getPlayerBackUrl();
            $result['go_success_page'] = true;
        }
        return $result;
    }

    private function checkCallbackOrder($order, $fields, &$processed = false) {
        $requiredFields = array(
                'respCd',
                'reqNo',
                'respNo',
                'sign',
                'cCode',
                'tType',
                'allFee',
                'processFee',
                'lastFee',
                'tStatus',
                'tNo',
                'tCode',
                'tCreateTime',
                );
        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("Missing parameter: [$f]", $fields);
                return false;
            }
        }
        $processed = true; // processed is set to true once the signature verification pass
        // check parameter values: orderStatus, tradeAmt, orderNo, merchNo
        // is payment successful?
        if ($fields['tStatus'] !== '1') {
            $this->writePaymentErrorLog('Payment was not successful', $fields);
            return false;
        }

        if ($fields['tType'] !== '0') {
            $this->writePaymentErrorLog('Payment type not match, expected deposit', $fields);
            return false;
        }
        // does amount match?
        if ( strval($order->amount * 100) !== strval($fields['allFee']) ) {
            $this->writePaymentErrorLog("Payment amounts do not match, expected [$order->amount]", $fields);
            return false;
        }
        // does order_no match?
        if ($fields['reqNo'] !== $order->secure_id) {
            $this->writePaymentErrorLog("Order IDs do not match, expected [$order->secure_id]", $fields);
            return false;
        }
        // everything checked ok
        return true;
    }
    public function directPay($order = null) {
        return array('success' => false); // direct pay not supported by this API
    }

    private function getNotifyUrl($orderId) {
        return parent::getCallbackUrl('/callback/process/' . $this->getPlatformCode() . '/' . $orderId);
    }

    private function getReturnUrl($orderId) {
        return parent::getCallbackUrl('/callback/browser/success/' . $this->getPlatformCode() . '/' . $orderId);
    }
    // Format the amount value for the API
    protected function convertAmountToCurrency($amount) {
        return number_format($amount, 2, '.', '');
    }
    // -- private helper functions --
    /**
     * @name	生成签名
     * @param	sourceData
     * @return	签名数据
     */
    public function sign($params) {
        $privkey = $this->getAllSystemInfo()['x2xpay_priv_key'];

        $keys = array(
                "appId" ,
                "backURL" ,
                "channel" ,
                "mchid" ,
                "reqNo" ,
                "reqType" ,
                "total_fee" ,
                "version" ,
                );

        $signStr = "";
        foreach( $keys as $key ){
            $signStr .= $params[$key];

        }

        openssl_sign($signStr, $sign_info, $privkey, "md5WithRSAEncryption");
        
        $signature = base64_encode($sign_info);
        return $signature;
    }
    /*
     * @name	验证签名
     * @param	data 原数据
     * @return
     */
    private function verify($resultXml) {
        $pubkey = $this->getSystemInfo('x2xpay_platform_pub_key');

        $xmldata = $this->parseResultXML($resultXml);
        $respCd = $xmldata["head"]["respCd"];
        $respMsg = $xmldata["head"]["respMsg"];
        $reqNo = $xmldata["head"]["reqNo"];
        $respNo = $xmldata["head"]["respNo"];
        $sign = $xmldata["head"]["sign"];

        $sortk = array();
        if( isset($xmldata["head"]) ){
            foreach( $xmldata["head"] as $k => $v ){
                if( $k != "sign" ) {
                    $sortk[$k] = $v;
                }
            }
        }

        if( isset($xmldata["data"]) ){
            foreach( $xmldata["data"] as $k => $v ){
                if( $k != "sign" ) {
                    $sortk[$k] = $v;
                }
            }
        }

        ksort( $sortk );

        $signStr = "";
        foreach( $sortk as $k => $v ){
            $signStr .= strval($v);
        }

        $sign = base64_decode( $sign );

        $ret = openssl_verify($signStr, $sign, $pubkey, "md5WithRSAEncryption");

        return ( $ret === 1 );
    
    }

	public function parseResultXML($resultXml) {
        $result = NULL;
		$obj=simplexml_load_string($resultXml);
		$arr=$this->CI->utils->xmlToArray($obj);
		$this->CI->utils->debug_log('arr === ', $arr);

        if( isset( $arr["head"] ) ){
            $result = $arr;
        }

        return $result;
	}

	public function array2xml( $data, $root="xml" ){

		$xml_data = new SimpleXMLElement('<?xml version="1.0"?><'.$root.'></'.$root.'>');

		function array_to_xml( $data, &$xml_data ) {
			foreach( $data as $key => $value ) {
				if( is_numeric($key) ){
					$key = 'item'.$key; //dealing with <0/>..<n/> issues
				}
				if( is_array($value) ) {
					$subnode = $xml_data->addChild($key);
					array_to_xml($value, $subnode);
				} else {
					$xml_data->addChild($key,htmlspecialchars($value));
				}
			}
		}

		array_to_xml($data,$xml_data);

		return $xml_data->saveXML();
	}
}