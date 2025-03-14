<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * XQIANGPAY 小強
 *
 * * XQIANGPAY_ALIPAY_API, ID: 487
 * * XQIANGPAY_QQPAY_PAYMENT_API, ID: 488
 * * XQIANGPAY_WEIXIN_PAYMENT_API, ID: 489
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
 * @copyright 2017-2022 tot
 */
abstract class Abstract_payment_api_xqiangpay extends Abstract_payment_api {

	const STATECODE = '2';
	const PAYTYPE_ALIPAY = 'DFYzfb';
	const PAYTYPE_SHUANGQIAN = 'ShuangQian';
	const PAYTYPE_WEIXIN = 'WXDFY';
	const PAYTYPE_WEIXIN_H5 = 'WxSm';
	const PAYTYPE_GOPAY = 'Gopay';
    const PAYTYPE_GOPAY_WAP = 'Gopaywap';
    const PAYTYPE_TOKYOPAY = 'JdPay';

	# Implement these for specific pay type
	protected abstract function configParams(&$params, $direct_pay_extra_info);
	protected abstract function processPaymentUrlForm($params);


	/**
	 * detail: Constructs an URL so that the caller can redirect / invoke it to make payment through this API, See controllers/redirect.php for detail.
	 *
	 * @param int $orderId order id
	 * @param int $playerId player id
	 * @param float $amount amount
	 * @param string $orderDateTime
	 * @param int $playerPromoId
	 * @param string $enabledSecondUrl
	 * @param int $bankId
	 * @return array
	 */
	public function generatePaymentUrlForm($orderId, $playerId, $amount, $orderDateTime, $playerPromoId = null, $enabledSecondUrl = true, $bankId = null) {
		if ($this->shouldRedirect($enabledSecondUrl)) {
			$url = $this->CI->utils->getPaymentUrl($this->getSystemInfo('second_url'), $this->getPlatformCode(), $amount, $playerId, $playerPromoId, false, $bankId, $orderId);
			$result = array('success' => true, 'type' => self::REDIRECT_TYPE_URL, 'url' => $url);
			return $result;
		}

		$order = $this->CI->sale_order->getSaleOrderById($orderId);



		$data = array(
            //请求参数
            'request' => array(
                'version'         => '1.0' , //版本号
                'serialID'        => 'XQ'.date("YmdHis").mt_rand(1000,9999) , //请求序列号
                'submitTime'      => date("YmdHis") ,      //订单提交时间
                'failureTime'     => '', //订单失效时间
                'customerIP'      => $this->getClientIP() , //客户下单域名及IP 可空 例:www.xxx.com[xxx.xxx.xxx.xxx]
                'orderDetails'    => '', //订单明细信息
                'totalAmount'     => $this->convertAmountToCurrency($amount) , //订单总金额
                'type'            => '1000' , //交易类型 1000: 即时支付（默认）
				'buyerMarked'	  => '',
                'payType'         => '',
				'orgCode'         => '',
				'currencyCode'    => '1', //交易币种 1:人民币(默认) 可空
				'directFlag'      => '1', //是否直连 0:非直连(默认) 1:直连
				'borrowingMarked' => '0', //资金来源借贷标识 可空 0:无特殊要求(默认) 1:只借机 2:只贷记
				'couponFlag'      => '1', //优惠卷标识 1:可用(默认) 0:不可用 可空
				'platformID'      => '', //平台商ID 可空
				'returnUrl'       => $this->getReturnUrl($orderId), //商户回调地址
                'noticeUrl'       => $this->getNotifyUrl($orderId) , //商户通知地址
                'partnerID'       => $this->getSystemInfo("account") ,  //商户ID
				'remark'          => 'CEC', //扩展字段
                'charset'         => '1', //编码方式 1:UTF-8(固定值)
                'signType'        => '2' //签名类型 1:RSA 2:MD5(目前只只支持MD5)
            ),
            //订单明细参数
            'orderDetails' => array(
                'orderID'     => $order->secure_id , //订单号
                'orderAmount' => $this->convertAmountToCurrency($amount), //订单明细金额
                'displayName' => '' , //下单商户显示名 可空
                'goodsName'   => 'depost' , //商品名称
                'goodsCount'  => '1' //商品数量
            ),
    	);
		$params  = $data['request'];
		$orderDetails = $this->orderDetails($data['orderDetails']);
		$params['orderDetails'] = $orderDetails;
		$this->configParams($params, $order->direct_pay_extra_info);

		$sign = $this->sign($params);
		$params['signMsg'] = $sign;


		$this->CI->utils->debug_log("=====================xqiangpay generatePaymentUrlForm", $params);

		return $this->processPaymentUrlForm($params);

	}

	private function orderDetails($data){
        $str = '';
        foreach($data as $key=>$val){
            $str .= $val.",";
        }
        return rtrim($str,",");
    }

	# Submit POST form
	protected function processPaymentUrlFormPost($params) {
		$url = $this->getSystemInfo('url');
		$this->CI->utils->debug_log('=====================xqiangpay post url', $url);

		return array(
			'success' => true,
			'type' => self::REDIRECT_TYPE_FORM,
			'url' => $url,
			'params' => $params,
			'post' => true ,
		);
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
			$this->CI->sale_order->updateExternalInfo($order->id, $params['orderNo'], null, null, null, $response_result_id);
			if ($source == 'browser') {
				$success = $this->CI->sale_order->browserCallbackSaleOrder($order->id, 'auto broswer callback ' . $this->getPlatformCode(), false);
			} elseif ($source == 'server') {
				$this->approveSaleOrder($order->id, 'auto server callback ' . $this->getPlatformCode(), false);
			}
		}

		$result['success'] = $success;
		if ($success) {
			$result['message'] = 'success';
		} else {
			$result['return_error'] = $processed ? 'success' : 'fail';
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
		$requiredFields = array(
			'orderID' ,
			'resultCode',
			'stateCode',
			'orderAmount',
			'payAmount',
			'acquiringTime',
			'completeTime',
			'orderNo',
			'partnerID',
			'remark',
			'charset',
			'signType',
			'signMsg'
		);

		foreach ($requiredFields as $f) {
			if (!array_key_exists($f, $fields)) {
				$this->writePaymentErrorLog("=====================xqiangpay missing parameter: [$f]", $fields);
				return false;
			}
		}

		$callbackSign = $this->createParamStr($fields);

		# is signature authentic?
		if ($fields['signMsg'] != $callbackSign) {
			$this->writePaymentErrorLog("=====================xqiangpay check callback sign error, signature is [$callbackSign], match? ", $fields);
			return false;
		}

		if ($fields['stateCode'] != self::STATECODE) {
			$payStatus = $fields['stateCode'];
			$this->writePaymentErrorLog("=====================xqiangpay Payment was not successful, payStatus is [$payStatus]", $fields);
			return false;
		}

		if ( $this->convertAmountToCurrency($order->amount) != floatval( $fields['orderAmount'] ) ) {
			$this->writePaymentErrorLog("=====================xqiangpay Payment amounts do not match, expected [$order->amount]", $fields);
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

	private function Assemble($arr){
        $str = '';

        foreach($arr as $key=>$val){
            $str .= $key."=".$val."&";
        }
        return rtrim($str,"&");
    }

	private function sign($data){
		$data['pkey'] = $this->getSystemInfo('md5key');
		//加入密串
		//数组进行组装
		$str = $this->Assemble($data);
		//加密
		$sign = md5($str);
		return $sign;
    }

	public function createParamStr($params) {
		$data = array(
			'orderID'  		=> $params['orderID'] ,
			'resultCode'  	=> $params['resultCode'] ,
			'stateCode'  	=> $params['stateCode'] ,
			'orderAmount'  	=> $params['orderAmount'] ,
			'payAmount'  	=> $params['payAmount'] ,
			'acquiringTime' => $params['acquiringTime'] ,
			'completeTime'  => $params['completeTime'] ,
			'orderNo'  		=> $params['orderNo'] ,
			'partnerID'  	=> $params['partnerID'] ,
			'remark'  		=> $params['remark'] ,
			'charset'  		=> $params['charset'] ,
			'signType'  	=> $params['signType'] ,
			'pkey'			=> $this->getSystemInfo("md5key")
		);


      	//加密
		$str = '';
		$str = $this->Assemble($data); //进行组装

		$finalStr = md5($str);

		return $finalStr;
	}
}
