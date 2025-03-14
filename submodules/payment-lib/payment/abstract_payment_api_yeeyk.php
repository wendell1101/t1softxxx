<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * YeeYK
 *
 * * YEEYK_PAYMENT_API, ID: 169
 * * YEEYK_ALIPAY_PAYMENT_API, ID: 170
 * * YEEYK_WEIXIN_PAYMENT_API, ID: 171
 *
 * Required Fields:
 * * URL
 *
 * Field Values:
 * * URL:
 *   live : https://www.yeeyk.com/yeex-iface-app/acquiring
 *   sandbox: http://www.yeeyk.com/yeex-iface-app/acquiring
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
abstract class Abstract_payment_api_yeeyk extends Abstract_payment_api
{

    const CHANNEL_BANK = 1;
    const CHANNEL_ALIPAY = 4;
    const CHANNEL_WEIXIN = 3;
    // const SDK_VERSION = '3.1.3';
    // const REQUEST_TYPE_WEB = 0;
    // const REQUEST_TYPE_WAP = 1;
    // const RETURN_SUCCESS_CODE = 'success';
    // const RETURN_FAIL_CODE = 'fail';
    // const P_ERRORCODE_PAYMENT_SUCCESS = 0;

    const BZTYPE_PROFESSION = 'PROFESSION';
    const BZTYPE_SDK = 'SDK';
    const BZTYPE_STANDARD = 'STANDARD';

	const CURRENCY_TYPE = 'CNY';
	const NEED_RESPONSE = '1';

	const p9_SAF = '0';

	const CALLBACK_RESULT_SUCCESS = 'SUCCESS';
	const CALLBACK_RESULT_FAIL = 'FAIL';

    const TRANSACTIONFEEONPLAYER = 'player';
    const TRANSACTIONFEEONOPERATOR = 'operator';

	protected $_merchantNo;
	protected $_merchantKey;

    public function __construct($params = null)
    {
        parent::__construct($params);

		$this->_merchantNo = $this->getSystemInfo('key');
		$this->_merchantKey = $this->getSystemInfo('secret');
    }

    # Returns one of the constants defined above: CHANNEL_XXX
    abstract public function getChannelId();

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
    public function generatePaymentUrlForm($orderId, $playerId, $amount, $orderDateTime, $playerPromoId = null, $enabledSecondUrl = true, $bankId = null)
    {
        if ($this->shouldRedirect($enabledSecondUrl)) {
            $url = $this->CI->utils->getPaymentUrl($this->getSystemInfo('second_url'), $this->getPlatformCode(), $amount, $playerId, $playerPromoId, false, $bankId, $orderId);
            $result = array('success' => true, 'type' => self::REDIRECT_TYPE_URL, 'url' => $url);
            return $result;
        }

        $order = $this->CI->sale_order->getSaleOrderById($orderId);
        $extraInfo = $this->CI->utils->decodeJson($order->direct_pay_extra_info);
        $this->CI->utils->debug_log("generatePaymentUrlForm : extraInfo", $extraInfo);
        //$this->CI->utils->debug_log("yeeyk order info from " . __FUNCTION__, var_export((array)$order));
        $params = array();
        $params['bizType'] = self::BZTYPE_PROFESSION;
        $params['merchantNo'] = $this->_merchantNo;
        $params['merchantOrderNo'] = $order->secure_id;
        $params['requestAmount'] = $this->convertAmountToCurrency($amount);
        $params['url'] = $this->getNotifyUrl($orderId);
        $params['cardAmt'] = $extraInfo["cardAmt"];
        $params['cardNo'] = $extraInfo["cardNo"];
        $params['cardPwd'] = $extraInfo["cardPwd"];
        $params['cardCode'] = $extraInfo["cardCode"];
        $params['productName'] = ' ';
        $params['productType'] = ' ';
        $params['productDesc'] = ' ';
        $params['extInfo'] = $this->getSystemInfo('transaction_fee_on'); //saved on extra_info
        $params['hmac'] = $this->getReqHmacString($params['bizType'], $params['merchantOrderNo'], $params['requestAmount'], self::CURRENCY_TYPE,
				$params['productName'], $params['productType'], $params['productDesc'], $params['url'],
				$params['cardAmt'], $params['cardNo'], $params['cardPwd'],
				$params['extInfo'], $params['cardCode'], self::NEED_RESPONSE
		);
        $this->CI->utils->debug_log('generatePaymentUrlForm : POST params', var_export($params, true));
        //print_r($params);
		$jsonResultRaw = $this->submitPostForm($this->getSystemInfo('url'), $params, false, $params['merchantOrderNo']);

        $this->CI->utils->debug_log('generatePaymentUrlForm : submitPostForm return', $jsonResultRaw);

		if(empty($jsonResultRaw)) {
            $this->CI->utils->debug_log('generatePaymentUrlForm : submitPostForm return Empty Error');
			return array(
				'success' => false,
				'type' => self::REDIRECT_TYPE_ERROR,
				'message' => lang('Invalidte API response'),
			);
		}

		$resp = $this->CI->utils->decodeJson($jsonResultRaw);

        $this->CI->utils->debug_log('generatePaymentUrlForm : after decode json ', $resp);

		//check status first
		if(empty($resp) || !isset($resp['code']) || ((int)$resp['code'] !== 0)){
            $this->CI->utils->debug_log('generatePaymentUrlForm : submitPostForm return Code Error');
			//wrong status
			return array(
				'success' => false,
				'type' => self::REDIRECT_TYPE_ERROR,
				'message' => lang('Original error message').': '.$resp['message'],
			);
		}

		# validate signature
		if($this->getRespHmacString(@$resp['bizType'], @$resp['merchantOrderNo'], @$resp['code'], @$resp['message']) != @$resp['hmac']) {
            $this->CI->utils->debug_log('generatePaymentUrlForm : submitPostForm return getRespHmacString Error');
			return array(
				'success' => false,
				'type' => self::REDIRECT_TYPE_ERROR,
				'message' => lang('Invalid Signature'),
			);
		}

        $this->CI->utils->debug_log('generatePaymentUrlForm : submitPostForm return success');

        $style_data['hide_payment_account']=true;
        return array(
        	'success' => true,
        	'type' => self::REDIRECT_TYPE_STATIC,
            'data' => $order,
            'style_data' => $style_data,
        );
    }

    ## This will be called when the payment is async, API server calls our callback page
    ## When that happens, we perform verifications and necessary database updates to mark the payment as successful
    ## Reference: sample code, callback.php
    public function callbackFromServer($orderId, $params)
    {
        $this->CI->utils->debug_log('callbackFromServer : ', 'orderId : ' . $orderId, 'params : ' . var_export($params, true));
        $response_result_id = parent::callbackFromServer($orderId, $params);
        return $this->callbackFrom('server', $orderId, $params, $response_result_id);
    }

    ## This will be called when user redirects back to our page from payment API
    public function callbackFromBrowser($orderId, $params)
    {
        $this->CI->utils->debug_log('callbackFromBrowser : ', 'orderId : ' . $orderId, 'params : ' . var_export($params, true));
        $response_result_id = parent::callbackFromBrowser($orderId, $params);
        return $this->callbackFrom('browser', $orderId, $params, $response_result_id);
    }

	# $source can be 'server' or 'browser'
	private function callbackFrom($source, $orderId, $params, $response_result_id) {
        $this->CI->utils->debug_log('callbackFrom enter; source : ' . $source);
        $this->CI->utils->debug_log('callbackFrom enter; orderId : ' . $orderId, 'params' . var_export($params, true));
		$result = array('success' => false, 'next_url' => null, 'message' => static::CALLBACK_RESULT_FAIL);

        $order = $this->CI->sale_order->getSaleOrderById($orderId);

        $processed = false;

        if (!$order || !$this->checkCallbackOrder($order, $params, $processed)) {
            return $result;
        }

        $this->CI->utils->debug_log('checkCallbackOrder true');
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
			//$this->CI->sale_order->updateExternalInfo($order->id,
			//	$params['P_SMPayId'], '',
			//	null, null, $response_result_id);
			if ($source == 'browser') {
    			$success = $this->CI->sale_order->browserCallbackSaleOrder($order->id, 'auto broswer callback ' . $this->getPlatformCode(), false);
			} elseif ($source == 'server') {
                if ($params['result'] == "FAIL") {
                    $this->CI->utils->debug_log('server callback response fail, add reason to and set status to failed to ' . $orderId);
                    $reason= $params['cardReturnInfo'];
                    $show_reason_to_player=false;
                    $this->CI->sale_order->declineSaleOrder($orderId, $reason, $show_reason_to_player);
                    $success = false;
                } else {
                    $transactionfee = 0;
                    $needtransactionfee = false;
                    if ($params['extInfo'] == self::TRANSACTIONFEEONPLAYER) {
                        $needtransactionfee = true;
                        $this->CI->utils->debug_log('transaction on player');
                        $cardinfo = $this->getSystemInfo('cardinfo');
                        $cardPercent = 0;
                        foreach($cardinfo as $card) {
                            if ($card['cardCode'] == $params['cardCode']) {
                                $cardPercent = $card['cardPercent'];
                                break;
                            }
                        }
                        $transactionfee = (float)$params['successAmount'] * ((float)$cardPercent / 100);
                        $fixAmount = (float)$params['successAmount'] - $transactionfee;
                        $this->CI->sale_order->fixOrderAmount($order->id, $fixAmount, 'transactionfee amount fixed');
                    }
                    $this->approveSaleOrder($order->id, 'auto server callback ' . $this->getPlatformCode(), false);
                    if ($needtransactionfee) {
                        $this->CI->utils->debug_log('need transactionfee');
                        //reload order info
                        $order = $this->CI->sale_order->getSaleOrderById($orderId);
                        $this->CI->load->model(['transactions','users']);
                        $adminUserId = $this->CI->users->getSuperAdminId();
                        if (!$this->CI->transactions->createTransactionFee($transactionfee, $order->id, $adminUserId, $order->player_id, $order->transaction_id)) {
                            $success = false;
                        }
                    }
                }
            }
		}

		$result['success'] = $success;
		if ($success) {
			$result['message'] = self::CALLBACK_RESULT_SUCCESS;
		} else {
			$result['message'] = self::CALLBACK_RESULT_FAIL;
		}

		if ($source == 'browser') {
			$result['next_url'] = $this->getPlayerBackUrl();
			$result['go_success_page'] = true;
		}

		return $result;
	}

    private function checkCallbackOrder($order, $params, &$processed = false) {
        $processed = true;
        $this->utils->debug_log('checkCallbackOrder', $order, $params, $processed);
        if ($this->CheckHmac($params['bizType'], $params['result'], $params['merchantNo'], $params['merchantOrderNo'], $params['successAmount'],
            $params['cardCode'], $params['noticeType'], $params['extInfo'], $params['cardNo'], $params['cardStatus'], $params['cardReturnInfo'],
            $params['cardIsbalance'], $params['cardBalance'], $params['cardSuccessAmount'], $params['hmac'])) {
            return true;
        } else {
            return false;
        }
    }

    public function directPay($order = null)
    {
        return array('success' => false); # direct pay not supported by this API
    }

    # Hide banklist by default, as this API does not support bank selection during form submit
    public function getPlayerInputInfo()
    {
        return array(
            array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
        );
    }

    # -- Private functions --
    # After payment is complete, the gateway will invoke this URL asynchronously
    private function getNotifyUrl($orderId)
    {
        return parent::getCallbackUrl('/callback/process/' . $this->getPlatformCode() . '/' . $orderId);
    }

    ## After payment is complete, the gateway will send redirect back to this URL
    private function getReturnUrl($orderId)
    {
        return parent::getCallbackUrl('/callback/browser/success/' . $this->getPlatformCode() . '/' . $orderId);
    }

    ## Format the amount value for the API
    protected function convertAmountToCurrency($amount)
    {
        return number_format($amount, 2, '.', '');
    }

    public function getReqHmacString($bizType, $p2_Order, $p3_Amt, $p4_Cur, $p5_Pid, $p6_Pcat, $p7_Pdesc, $p8_Url, $cardAmt, $cardNo, $cardPwd, $pa_MP, $pd_FrpId, $pr_NeedResponse)
    {
        //global $p0_Cmd;
        $p9_SAF = static::p9_SAF;

        #进行签名处理，一定按照文档中标明的签名顺序进行
        $sbOld = "";
        #加入业务类型
        //$sbOld = $sbOld.$p0_Cmd;
        $sbOld = $sbOld . $bizType;
        #加入商户编号
        $sbOld = $sbOld . $this->_merchantNo;
        #加入商户订单号
        $sbOld = $sbOld . $p2_Order;
        #加入支付金额
        $sbOld = $sbOld . $p3_Amt;
        #加入商户接收支付成功数据的地址
        $sbOld = $sbOld . $p8_Url;
        #卡面额组
        $sbOld = $sbOld . $cardAmt;
        #卡号组
        $sbOld = $sbOld . $cardNo;
        #卡密组
        $sbOld = $sbOld . $cardPwd;
        #加入交易币种
        //$sbOld = $sbOld.$p4_Cur;
        #加入支付通道编码
        $sbOld = $sbOld . $pd_FrpId;
        #加入商品名称
        $sbOld = $sbOld . $p5_Pid;
        #加入商品分类
        $sbOld = $sbOld . $p6_Pcat;
        #加入商品描述
        $sbOld = $sbOld . $p7_Pdesc;
        #加入送货地址标识
        //$sbOld = $sbOld . $p9_SAF;
        #加入商户扩展信息
        $sbOld = $sbOld . $pa_MP;
        #加入是否需要应答机制
        //$sbOld = $sbOld . $pr_NeedResponse;


        return $this->HmacMd5($sbOld, $this->_merchantKey);
    }

	public function getRespHmacString($bizType, $p2_Order, $code, $message)
	{
        $p9_SAF = static::p9_SAF;

        #进行签名处理，一定按照文档中标明的签名顺序进行
        $sbOld = "";
        #加入业务类型
        //$sbOld = $sbOld.$p0_Cmd;
        $sbOld = $sbOld . $bizType;
        #加入商户订单号
        $sbOld = $sbOld . $p2_Order;
        #
        $sbOld = $sbOld . $code;
        #
        $sbOld = $sbOld . $message;

   

        return $this->HmacMd5Resp($sbOld, $this->_merchantKey);
	}

    public function getCallbackHmacString($bizType, $result, $merchantNo, $merchantOrderNo, $successAmount, $cardCode, $noticeType, $extInfo,
		$cardNo, $cardStatus, $cardReturnInfo, $cardIsbalance, $cardBalance, $cardSuccessAmount)
    {
        #取得加密前的字符串
        $sbOld = "";
        #
        $sbOld = $sbOld . $bizType;
        #
        $sbOld = $sbOld . $result;
        #
        $sbOld = $sbOld . $merchantNo;
        #
        $sbOld = $sbOld . $merchantOrderNo;
        #
        $sbOld = $sbOld . $successAmount;
        #
        $sbOld = $sbOld . $cardCode;
        #
        $sbOld = $sbOld . $noticeType;
        #
        $sbOld = $sbOld . $extInfo;
        #
        $sbOld = $sbOld . $cardNo;
        #
        $sbOld = $sbOld . $cardStatus;
        #
        $sbOld = $sbOld . $cardReturnInfo;
        #
        $sbOld = $sbOld . $cardIsbalance;
        #
        $sbOld = $sbOld . $cardBalance;
        #
        $sbOld = $sbOld . $cardSuccessAmount;

    
        return $this->HmacMd5Resp($sbOld, $this->_merchantKey);
    }

    public function CheckHmac($bizType, $result, $merchantNo, $merchantOrderNo, $successAmount, $cardCode, $noticeType, $extInfo,
		$cardNo, $cardStatus, $cardReturnInfo, $cardIsbalance, $cardBalance, $cardSuccessAmount, $hmac)
    {
        if ($hmac == $this->getCallbackHmacString($bizType, $result, $merchantNo, $merchantOrderNo, $successAmount, $cardCode, $noticeType, $extInfo,
		$cardNo, $cardStatus, $cardReturnInfo, $cardIsbalance, $cardBalance, $cardSuccessAmount)) {
            return true;
        } else {
            return false;
        }
    }


    public function HmacMd5($data, $key)
    {
        // RFC 2104 HMAC implementation for php.
        // Creates an md5 HMAC.
        // Eliminates the need to install mhash to compute a HMAC
        // Hacked by Lance Rushing(NOTE: Hacked means written)

        //需要配置环境支持iconv，否则中文参数不能正常处理
       
        $key = iconv("GB2312", "UTF-8//IGNORE", $key);

        $data = iconv("GB2312", "UTF-8//IGNORE", $data);
     

        $b = 64; // byte length for md5
        if (strlen($key) > $b) {
            $key = pack("H*", md5($key));
        }
        $key = str_pad($key, $b, chr(0x00));
        $ipad = str_pad('', $b, chr(0x36));
        $opad = str_pad('', $b, chr(0x5c));
        $k_ipad = $key ^ $ipad;
        $k_opad = $key ^ $opad;

        return md5($k_opad . pack("H*", md5($k_ipad . $data)));
    }

    public function HmacMd5Resp($data, $key)
    {
        // RFC 2104 HMAC implementation for php.
        // Creates an md5 HMAC.
        // Eliminates the need to install mhash to compute a HMAC
        // Hacked by Lance Rushing(NOTE: Hacked means written)

        //需要配置环境支持iconv，否则中文参数不能正常处理
        // ** Resp 先不轉iconv
        // $this->utils->debug_log('HmacMd5 key : ' . $key . ' data : ' . $data);
        // $key = iconv("GB2312", "UTF-8//IGNORE", $key);
        // $this->utils->debug_log('HmacMd5 iconv key : ' . $key);
        // $data = iconv("GB2312", "UTF-8//IGNORE", $data);
        // $this->utils->debug_log('HmacMd5 iconv data : ' . $data);

        $b = 64; // byte length for md5
        if (strlen($key) > $b) {
            $key = pack("H*", md5($key));
        }
        $key = str_pad($key, $b, chr(0x00));
        $ipad = str_pad('', $b, chr(0x36));
        $opad = str_pad('', $b, chr(0x5c));
        $k_ipad = $key ^ $ipad;
        $k_opad = $key ^ $opad;

        return md5($k_opad . pack("H*", md5($k_ipad . $data)));
    }



}
