<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * Emerchant Payment
 *
 * * EMERCHANT_UNIONPAY_PAYMENT_API, ID: 5438
 * * EMERCHANT_EXPRESS_PAYMENT_API, ID: 5439
 * * EMERCHANT_QRPAYMENT_PAYMENT_API, ID: 5440
 * * EMERCHANT_ALIPAY_PAYMENT_API, ID: 5441
 * * EMERCHANT_WEIXIN_PAYMENT_API, ID: 5442
 * * EMERCHANT_WITHDRAWAL_PAYMENT_API, ID: 5543
 *
 * Required Fields:
 *
 * * URL
 *
 * Field Values:
 *
 * * Extra Info:
 * > {
 * >    "terminal_token" : "## Terminal Token ##",
 * >    "auth_username" : "## Username for http basic auth ##",
 * >    "auth_password" : "## Password for http basic auth ##"
 * > }
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */


abstract class Abstract_payment_api_emerchantpay extends Abstract_payment_api {

    const TRANS_TYPE_ONLINEBANK = 'online_banking';
    const BANK_CODE_QUICKPAY = 'QUICKPAY';
    const PAY_TYPE_QR = 'qr_payment';
    const RETURN_STATUS_SUCCESS = 'approved';
    const RETURN_STATUS_FAILED = 'error';

    public function __construct($params = null) {
        parent::__construct($params);
    }

	# Implement these for specific pay type
	protected abstract function configParams(&$params, $direct_pay_extra_info);
	protected abstract function processPaymentUrlForm($params);


	public function getSecretInfoList() {
		$secretsInfo = array('live_key', 'live_secret', 'sandbox_key', 'sandbox_secret', 'auth_username', 'auth_password');
		return $secretsInfo;
    }

    public function generatePaymentUrlForm($orderId, $playerId, $amount, $orderDateTime, $playerPromoId = null, $enabledSecondUrl = true, $bankId = null) {
        if ($this->shouldRedirect($enabledSecondUrl)) {
            $url = $this->CI->utils->getPaymentUrl($this->getSystemInfo('second_url'), $this->getPlatformCode(), $amount, $playerId, $playerPromoId, false, $bankId, $orderId);
            $result = array('success' => true, 'type' => self::REDIRECT_TYPE_URL, 'url' => $url);
            return $result;
        }

        $order         = $this->CI->sale_order->getSaleOrderById($orderId);
        $player        = $this->CI->player_model->getPlayer(array('playerId' => $playerId));
        $playerDetails = $this->CI->player_model->getPlayerDetails($playerId);
		$username      = (isset($playerDetails[0]) && !empty($playerDetails[0]['username']))  	  ? $playerDetails[0]['username']	   : 'no username';
		$firstname     = (isset($playerDetails[0]) && !empty($playerDetails[0]['firstName'])) 	  ? $playerDetails[0]['firstName']	   : 'no firstName';
		$lastname      = (isset($playerDetails[0]) && !empty($playerDetails[0]['lastName']))  	  ? $playerDetails[0]['lastName']	   : 'no lastname';
		$email         = (isset($playerDetails[0]) && !empty($playerDetails[0]['email']))     	  ? $playerDetails[0]['email'] 		   : 'test@testing.com';
        $telephone     = (isset($playerDetails[0]) && !empty($playerDetails[0]['contactNumber'])) ? $playerDetails[0]['contactNumber'] : '+12063582043';
        $address       = (isset($playerDetails[0]) && !empty($playerDetails[0]['address']))       ? $playerDetails[0]['address']       : 'no address';
        $city          = (isset($playerDetails[0]) && !empty($playerDetails[0]['city']))          ? $playerDetails[0]['city']          : 'no city';
        $zipcode       = (isset($playerDetails[0]) && !empty($playerDetails[0]['zipcode']))       ? $playerDetails[0]['zipcode']       : '1000';
        $country       = (isset($playerDetails[0]) && !empty($playerDetails[0]['country']))       ? $playerDetails[0]['country']       : 'CN';

        $params = array();
        $this->configParams($params, $order->direct_pay_extra_info);
        $params['transaction_id'] = $order->secure_id;
        $params['usage'] = 'Deposit';
        $params['remote_ip'] = $this->getClientIp();
        $params['notification_url'] = $this->getNotifyUrl($orderId);
        $params['return_success_url'] = $this->getReturnUrl($orderId);
        $params['return_failure_url'] = $this->getReturnFailUrl($orderId);
        $params['return_cancel_url'] = $this->getReturnFailUrl($orderId);
        $params['amount'] = (int)$this->convertAmountToCurrency($amount,$orderDateTime);
        $params['currency'] = $this->getSystemInfo('currency','CNY');
        $params['customer_email'] = $email;
        $params['customer_phone'] = $telephone;
        $params['billing_address']['first_name'] = $firstname;
        $params['billing_address']['last_name'] = $lastname;
        $params['billing_address']['address1'] = $address;
        $params['billing_address']['address2'] = 'no address2';
        $params['billing_address']['zip_code'] = $zipcode;
        $params['billing_address']['city'] = $city;
        $params['billing_address']['state'] = 'no state';
        $params['billing_address']['country'] = $country;

        $this->CI->utils->debug_log('=========================emerchantpay generatePaymentUrlForm', $params);

        return $this->processPaymentUrlForm($params);
    }

    # Submit POST form
    protected function processPaymentUrlFormPost($params) {
        $xml_object = new SimpleXMLElement("<?xml version='1.0' encoding='utf-8'?><wpf_payment></wpf_payment>");
        $xml = $this->CI->utils->arrayToXml($params, $xml_object);
        $xml = strtr($xml, array("\n" => '',"\r" => ''));
        $responseXml = $this->submitXml($xml, $params['transaction_id']);
        $this->CI->utils->debug_log("=====================emerchantpay XML response", $responseXml);
        $response = $this->parseResultXML($responseXml);

        if($response['status'] != self::RETURN_STATUS_FAILED && array_key_exists('redirect_url', $response)) {
            return array(
                'success' => true,
                'type' => self::REDIRECT_TYPE_URL,
                'url' => $response['redirect_url'],
            );
        } else {
            return array(
                'success' => false,
                'type' => self::REDIRECT_TYPE_ERROR,
                'message' => '['.$response['status'].']: '.$response['technical_message']
            );
        }
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
        $result = array('success' => false, 'next_url' => null, 'message' => lang('error.payment.failed'));
        $order = $this->CI->sale_order->getSaleOrderById($orderId);
        $processed = false;

        $this->CI->utils->debug_log("=====================emerchantpay callbackFrom $source params", $params);

        if($source == 'server'){
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
            $this->CI->sale_order->updateExternalInfo($order->id, null, null, null, null, $response_result_id);
            if ($source == 'browser') {
                $success = $this->CI->sale_order->browserCallbackSaleOrder($order->id, 'auto broswer callback ' . $this->getPlatformCode(), false);
            } elseif ($source == 'server') {
                $this->approveSaleOrder($order->id, 'auto server callback ' . $this->getPlatformCode(), false);
            }
        }

        $result['success'] = $success;
        if ($processed) {
            $xml_object = new SimpleXMLElement("<?xml version='1.0' encoding='utf-8'?><notification_echo></notification_echo>");
            $params = array(
                'wpf_unique_id' => $params['wpf_unique_id']
            );
            $xml = $this->CI->utils->arrayToXml($params, $xml_object);
            $result['message'] = $xml;
        } else {
            $result['return_error'] = 'Error';
        }

        if ($source == 'browser') {
            $result['next_url'] = $this->getPlayerBackUrl();
            $result['go_success_page'] = true;
        }

        return $result;
    }

    # returns true if callback is valid and payment is successful
    # sets the $callbackValid parameter if callback is valid
    private function checkCallbackOrder($order, $fields, &$processed = false) {
        $requiredFields = array('signature', 'wpf_status', 'wpf_transaction_id', 'payment_transaction_amount','wpf_unique_id');
        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("=========================emerchantpay checkCallbackOrder Missing parameter: [$f]", $fields);
                return false;
            }
        }

        # is signature authentic?
        if (!$this->validateSign($fields)) {
            $this->writePaymentErrorLog('=======================emerchantpay checkCallbackOrder verify signature Error', $fields);
            return false;
        }

		$processed = true; # processed is set to true once the signature verification pass


		if ($fields['wpf_status'] != self::RETURN_STATUS_SUCCESS) {
			$payStatus = $fields['wpf_status'];
			$this->writePaymentErrorLog("=====================emerchantpay Payment was not successful, payStatus is [$payStatus]", $fields);
			return false;
		}

        if ($fields['wpf_transaction_id'] != $order->secure_id) {
            $this->writePaymentErrorLog("========================emerchantpay checkCallbackOrder order IDs do not match, expected [$order->secure_id]", $fields);
            return false;
        }

		$check_amount = $this->convertAmountToCurrency($order->amount, $order->created_at);
		if ( $check_amount != floatval( $fields['payment_transaction_amount'] )) {
			$this->writePaymentErrorLog("=====================emerchantpay Payment amounts do not match, expected [$check_amount]", $fields);
			return false;
		}

		# everything checked ok
		return true;
	}

    public function directPay($order = null) {
        return array('success' => false); # direct pay not supported by this API
    }

    # -- private helper functions --
	## Format the amount value for the API
	protected function convertAmountToCurrency($amount, $orderDateTime) {
		if($this->getSystemInfo('use_usd_currency')){
			if(is_string($orderDateTime)){
				$orderDateTime = DateTime::createFromFormat('Y-m-d H:i:s', $orderDateTime);
			}
			$amount = $this->gameAmountToDBByCurrency($amount, $this->utils->getTimeForMysql($orderDateTime),'USD','CNY');
			$this->CI->utils->debug_log('=====================emerchantpay convertAmountToCurrency use_usd_currency', $amount);
        }

        return number_format($amount * 100, 0, '.', '');
	}

    protected function getNotifyUrl($orderId) {
        return parent::getCallbackUrl('/callback/process/' . $this->getPlatformCode() . '/' . $orderId);
    }

    protected function getReturnUrl($orderId) {
        return parent::getCallbackUrl('/callback/browser/success/' . $this->getPlatformCode() . '/' . $orderId);
    }

    protected function getReturnFailUrl($orderId) {
        return parent::getCallbackUrl('/callback/show_error/' . $this->getPlatformCode() . '/' . $orderId);
    }

    public function submitXml($xml, $orderSecureId, $return_all=false) {
        ## Ref: section 8.2 WPF API


        $url = $this->getSystemInfo('url');
        if(!empty($this->getSystemInfo('terminal_token'))){
            $url = $this->getSystemInfo('url').$this->getSystemInfo('terminal_token');
        }

        $username = $this->getSystemInfo('auth_username');
        $password  = $this->getSystemInfo('auth_password');
        $this->CI->utils->debug_log('=========================submitXml url', $url);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            "Content-Type: application/xml")
        );
        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLOPT_USERPWD, $username . ":" . $password);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        $this->setCurlProxyOptions($ch);
        $response    = curl_exec($ch);
        $statusCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $errCode     = curl_errno($ch);
        $error       = curl_error($ch);
        curl_close($ch);
        $this->CI->utils->debug_log('url', $url, 'params', $xml , 'response', $response,
            'errCode', $errCode, 'error', $error, 'statusCode', $statusCode);


        #withdrawal lock processing
        if(substr($orderSecureId, 0, 1) == 'W' && $errCode == '28') {
            $response = array('lock' => true, 'msg' => 'Ready to lock processing withdrawal order. curl error message: errCode = '.$errCode.' - '.$error);
        }

        #save response result
        $response_result_id = $this->submitPreprocess($xml, $response, $url, $response,
            array('errCode' => $errCode, 'error' => $error, 'statusCode' => $statusCode),
            $orderSecureId);

        if($return_all){
            $response_result = [
                $xml, $response, $url, $response, ['errCode' => $errCode, 'error' => $error, 'statusCode' => $statusCode], $orderSecureId
            ];
            $this->CI->utils->debug_log('=========================submitXml return_all response_result', $response_result);
            return array($response, $response_result);
        }
        return $response;
    }

    public function parseResultXML($resultXml) {
        $this->CI->utils->debug_log('=========================parseResultXML resultXml', $resultXml);
        $resultXml = strstr($resultXml, "<");
        $this->CI->utils->debug_log('=========================parseResultXML strstr', $resultXml);

        $obj = simplexml_load_string($resultXml);
        $arr = $this->CI->utils->xmlToArray($obj);
        $this->CI->utils->debug_log('=========================emerchantpay parseResultXML', $arr);

		return $arr;
    }

    protected function validateSign($params) {
        $signStr = $this->createSignStr($params);
        $sign = hash('sha512',$signStr);

		if($params['signature'] == $sign){
			return true;
		}
		else{
			return false;
		}
    }

    protected function createSignStr($params) {
        $signStr = '';
        $signStr = $params['wpf_unique_id'].$this->getSystemInfo('auth_password');
        return $signStr;
    }
}