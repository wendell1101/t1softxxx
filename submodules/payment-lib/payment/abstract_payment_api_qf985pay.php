<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * qf985pay
 *
 * QF985PAY_PAYMENT_API: 170
 * QF985PAY_ALIPAY_PAYMENT_API: 171
 * QF985PAY_WEIXIN_PAYMENT_API: 172
 *
 *
 * Required Fields:
 * * URL
 *
 * Field Values:
 * URL:
 *   bank: http://nw.985pay.com/api/remit.action
 *   alipay: http://nw.985pay.com/api/pay.action
 *   weixin: http://nw.985pay.com/api/pay.action
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
abstract class Abstract_payment_api_qf985pay extends Abstract_payment_api
{
    const NETWAY_ZFB = 'ZFB';
    const NETWAY_ZFB_WAP = 'ZFB_WAP';
    const NETWAY_WX = 'WX';

    const NETWAY = '';

    const RETURN_SUCCESS_CODE = '0';
    const RETURN_FAIL_CODE = '0';

	static function json_encode($input){
		if(is_string($input)){
			$text = $input;
			$text = str_replace('\\', '\\\\', $text);
			$text = str_replace(
				array("\r", "\n", "\t", "\""),
				array('\r', '\n', '\t', '\\"'),
				$text);
			return '"' . $text . '"';
		}else if(is_array($input) || is_object($input)){
			$arr = array();
			$is_obj = is_object($input) || (array_keys($input) !== range(0, count($input) - 1));
			foreach($input as $k=>$v){
				if($is_obj){
					$arr[] = self::json_encode($k) . ':' . self::json_encode($v);
				}else{
					$arr[] = self::json_encode($v);
				}
			}
			if($is_obj){
				return '{' . join(',', $arr) . '}';
			}else{
				return '[' . join(',', $arr) . ']';
			}
		}else{
			return $input . '';
		}
	}

    public function __construct($params = null)
    {
        parent::__construct($params);
    }

    # Returns one of the constants defined above: CHANNEL_XXX
    abstract public function getChannelId();

    protected function _sign(&$params, $secret)
    {
        if(isset($params['sign'])){
            $sign_str = $params['sign'];
            unset($params['sign']);
        }
		ksort($params);
        $sign_meta = static::json_encode($params) . $secret;
		$sign = md5($sign_meta);

		$params['sign'] = strtoupper($sign);
    }

    protected function _verifySign($params, $secret)
    {
        $sign_str = '';
        if(isset($params['sign'])){
            $sign_str = $params['sign'];
            unset($params['sign']);
        }else{
            return FALSE;
        }

        $this->_sign($params, $secret);

        return ($params['sign'] === strtoupper($sign_str)) ? TRUE : FALSE;
    }

    public function getOrderIdFromParameters($params)
    {
		$orderId = NULL;
		//for fixed return url on browser
		if (isset($params['orderNum'])) {
			$outTradeNo = $params['orderNum'];

			$this->CI->load->model(array('sale_order'));
			$order = $this->CI->sale_order->getSaleOrderBySecureId($outTradeNo);

			$orderId = (!empty($order)) ? $order->id : NULL;
		}

		return $orderId;
    }

    /**
     *
     * detail: a static bank list information
     *
     * note: Reference: sample code, Mobaopay.Config.php
     *
     * @return array
     */
    public function getBankListInfoFallback()
    {
        return array(
            array('label' => '中国银行', 'value' => 'BOC'),
            array('label' => '中国农业银行', 'value' => 'ABC'),
            array('label' => '中国工商银行', 'value' => 'ICBC'),
            array('label' => '中国建设银行', 'value' => 'CCB'),
            array('label' => '交通银行', 'value' => 'BCM'),
            array('label' => '中国招商银行', 'value' => 'CMB'),
            array('label' => '中国光大银行', 'value' => 'CEB'),
            array('label' => '中国民生银行', 'value' => 'CMBC'),
            array('label' => '华夏银行', 'value' => 'HXB'),
            array('label' => '兴业银行', 'value' => 'CIB'),
            array('label' => '中信银行', 'value' => 'CNCB'),
            array('label' => '上海浦东发展银行', 'value' => 'SPDB'),
            array('label' => '中国邮政储蓄银行', 'value' => 'PSBC'),

        );
    }

    protected function getBankId($direct_pay_extra_info)
    {
        # overwritten in qrcode implementation
        if (!empty($direct_pay_extra_info)) {
            $extraInfo = json_decode($direct_pay_extra_info, true);
            if (!empty($extraInfo) && array_key_exists('bank', $extraInfo)) {
                return $extraInfo['bank'];
            } else {
                return null;
            }
        }

        return null;
    }

    # -- override common API functions --
    ## Constructs an URL so that the caller can redirect / invoke it to make payment through this API
    ## See controllers/redirect.php for detail.
    ##
    ## Retuns a hash containing these fields:
    ## array(
    ##  'success' => true,
    ##  'type' => self::REDIRECT_TYPE_FORM,  ## constants defined in abstract_payment_api.php
    ##  'url' => $info['url'],
    ##  'params' => $params,
    ##  'post' => true
    ## );
    public function generatePaymentUrlForm($orderId, $playerId, $amount, $orderDateTime, $playerPromoId = null, $enabledSecondUrl = true, $bankId = null)
    {
        if ($this->shouldRedirect($enabledSecondUrl)) {
            $url = $this->CI->utils->getPaymentUrl($this->getSystemInfo('second_url'), $this->getPlatformCode(), $amount, $playerId, $playerPromoId, false, $bankId, $orderId);
            $result = array('success' => true, 'type' => self::REDIRECT_TYPE_URL, 'url' => $url);
            return $result;
        }

        $order = $this->CI->sale_order->getSaleOrderById($orderId);
        $direct_pay_extra_info = $order->direct_pay_extra_info;

		$params = array();
		$params['merNo'] = $this->getSystemInfo('key');
		$params['netway'] = static::NETWAY;
		$params['random'] = (string) rand(1000,9999);
		$params['orderNum'] = $order->secure_id;
		$params['amount'] = $this->convertAmountToCurrency($amount) . ""; // convert to string
		$params['goodsName'] = 'deposit';
		$params['callBackUrl'] = $this->getNotifyUrl($orderId);
		$params['callBackViewUrl'] = $this->getReturnUrl($orderId);
        $this->_sign($params, $this->getSystemInfo('secret'));

		$data = static::json_encode($params);

		$post_data = array('data'=>$data);

        $url = $this->getSystemInfo('url');
        $result_arr = $this->submitPostForm($url, $post_data, false, $params['orderNum']);
        $this->CI->utils->debug_log("Form return", $result_arr, 'params', $params);

        $result_arr = json_decode($result_arr, true);
        // //  Dummy results
        // $result_arr = json_decode('{"merNo":"Mer000001","msg":"提交成功","orderNum":"201612120132343123uI","qrcodeUrl":"https://qr.alipay.com/bax13213","sign":"4A0FB7DDD59C4D1C1BBB52D8473B729B","stateCode":"00"}', TRUE);
        // $this->_sign($result_arr, $this->getSystemInfo('secret'));
        if (empty($result_arr)) {
            return array(
                'success' => false,
                'type' => self::REDIRECT_TYPE_ERROR,
                'message' => lang('Invalidte API response'),
            );
        }

        //check status first
        if (empty($result_arr) || !isset($result_arr['stateCode']) || ((int)$result_arr['stateCode'] !== 0)) {
            //wrong status
            return array(
                'success' => false,
                'type' => self::REDIRECT_TYPE_ERROR,
                'message' => lang('Original error message').': '. @$result_arr['msg'],
            );
        }

		# Verify the signature
		if(!$this->_verifySign($result_arr, $this->getSystemInfo('secret'))) {
			$this->utils->error_log("Signature verification failed for MD5 data: [$result_arr]");
			$result = array('success' => false);
			$result['return_status'] = self::REDIRECT_TYPE_ERROR;
			return $result;
		}

        $result = [
            'success' => TRUE
        ];
        switch(static::NETWAY){
            case self::NETWAY_WX:
            case self::NETWAY_ZFB:
            case self::NETWAY_ZFB_WAP:
            default:
                $result['type'] = self::REDIRECT_TYPE_QRCODE;
                $result['url'] = $result_arr['qrcodeUrl'];
            break;
        }

        return $result;
    }

    ## This will be called when the payment is async, API server calls our callback page
    ## When that happens, we perform verifications and necessary database updates to mark the payment as successful
    ## Reference: sample code, callback.php
    public function callbackFromServer($orderId, $params)
    {
        $response_result_id = parent::callbackFromServer($orderId, $params);
        return $this->callbackFrom('server', $orderId, $params, $response_result_id);
    }

    ## This will be called when user redirects back to our page from payment API
    public function callbackFromBrowser($orderId, $params)
    {
        $response_result_id = parent::callbackFromBrowser($orderId, $params);
        return array('success' => TRUE,'response_result_id' => $response_result_id);
        //return $this->callbackFrom('browser', $orderId, $params, $response_result_id);
    }

    # $source can be 'server' or 'browser'
    private function callbackFrom($source, $orderId, $params, $response_result_id)
    {
        $result = array('success' => false, 'return_status' => static::RETURN_FAIL_CODE);
        $order = $this->CI->sale_order->getSaleOrderById($orderId);
        $processed = false;

        if (!$order || !$this->checkCallbackOrder($order, $params, $processed)) {
            return $result;
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
            $this->CI->sale_order->updateExternalInfo($order->id, $params['outTradeNo'], null, null, null, $response_result_id);
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
            $result['return_status'] = self::RETURN_FAIL_CODE;
        }

        return $result;
    }

    ## Validates whether the callback from API contains valid info and matches with the order
    ## Reference: code sample, callback.php
    private function checkCallbackOrder($order, $fields, &$processed = false)
    {
        $requiredFields = array(
            'merNo', 'netway', 'orderNum', 'amount', 'goodsName', 'payResult', 'payDate'
        );

        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("Missing parameter: [$f]", $fields);
                return false;
            }
        }

        # is signature authentic?
        if (!$this->_verifySign($fields, $this->getSystemInfo('secret'))) {
            $this->writePaymentErrorLog('Signature Error', $fields);
            return false;
        }

        $processed = true; # processed is set to true once the signature verification pass

        if ($this->convertAmountToCurrency($order->amount) !=
            $this->convertAmountToCurrency($fields['amount'] / 100)
        ) {
            $this->writePaymentErrorLog("Payment amounts do not match, expected [$order->amount]", $fields);
            return false;
        }

        # everything checked ok
        return true;
    }

    public function directPay($order = null)
    {
        return array('success' => false); # direct pay not supported by this API
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
        return number_format($amount * 100, 2, '.', '');
    }

    # -- signatures --
    private function getCustormId($playerId, $P_UserId)
    {
        return $playerId.'_'.md5($P_UserId.'|'.$this->getSystemInfo('key').'|'.$playerId);
    }
}
