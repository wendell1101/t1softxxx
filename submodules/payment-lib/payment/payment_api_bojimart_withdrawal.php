<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * BOJIMART
 *
 * * BOJIMART_WITHDRAWAL_PAYMENT_API, ID: 624
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://api.ezcash88.com/service.asmx?WSDL
 * * Account: ## User ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */

class Payment_api_bojimart_withdrawal extends Abstract_payment_api {

    const CALLBACK_STATUS_SUCCESS = '1';
    const CALLBACK_STATUS_FAILED = '2';
    const RETURN_SUCCESS_CODE = 'success';
    private $soapClient;

    public function getPlatformCode() {
        return BOJIMART_WITHDRAWAL_PAYMENT_API;
    }

    public function getPrefix() {
        return 'bojimart_withdrawal';
    }

    public function generatePaymentUrlForm($orderId, $playerId, $amount, $orderDateTime, $playerPromoId = null, $enabledSecondUrl = true, $bankId = null) {}
	public function directPay($order = null) {
		return array('success' => false);
	}


    public function getWithdrawUrl() {
        return $this->getSystemInfo('url');
    }

    private $errMsg = 'Payment failed';
    public function getWithdrawParams($bank, $accNum, $name, $amount, $transId) {
        $paramsBasic = array();
        $params = array();
        $this->CI->load->model(array('wallet_model', 'player_model'));

        # Get player contact number
        $order    = $this->CI->wallet_model->getWalletAccountByTransactionCode($transId);
        $player   = $this->CI->player->getPlayerById($order['playerId']);
        $bankInfo = $this->getBOJIMARTBankInfo();
        if(!array_key_exists($bank, $bankInfo)) {
            $this->utils->error_log("========================bojimart withdrawl bank whose bankTypeId=[$bank] is not supported by bojimart withdrawl");
            return array('success' => false, 'message' => 'Bank not supported by bojimart withdrawl');
        }

        //Appendix 1: Add user
        $hash_key            = array('client', 'username');
        $post_key            = array('client', 'username', 'hashdata');

		$params = array();
        $params['client']    = $this->getSystemInfo("account");
        $params['username']  = $player['username'];
        $params['hashdata']  = $this->sign($params, $hash_key);
        $post['Data']   = $this->PostString($params, $post_key);

        $oper = "AddUser";
        $res  = $this->PostTarget($oper, $post);

        //Appendix 2: Submitting withdrawal record
        $hash_key = array('client', 'username', 'amount','withdrawalid','bankaccountno','bankholder','bankname', 'groupid', 'callbackurl');
        $post_key = array('client', 'username', 'amount','withdrawalid','bankaccountno','bankholder','bankname', 'groupid', 'callbackurl','hashdata');

		$params['amount']        = $this->convertAmountToCurrency($amount);
		$params['withdrawalid']  = $transId;
		$params['bankaccountno'] = $accNum;
		$params['bankholder']    = $name;
		$params['bankname']      = $bankInfo[$bank];
		$params['groupid']       = "0";
        $params['callbackurl']   = $this->getNotifyUrl($transId);


        $params['hashdata'] = $this->sign($params,$hash_key);
        $post['Data'] = $this->PostString($params, $post_key);

        return $post;
    }

    public function submitWithdrawRequest($bank, $accNum, $name, $amount, $transId) {
		$result = array('success' => false, 'message' => 'payment failed');
        if(!$this->isAllowWithdraw()) {
            $result['message'] = lang("Withdraw not allowed with this API");
            $this->utils->debug_log($result);
            return $result;
        }

        $params = $this->getWithdrawParams($bank, $accNum, $name, $amount, $transId);

        if(isset($params['success'])) {
            if($params['success'] == false) {
                $result['message'] = $params['message'];
                $this->utils->debug_log($result);
                return $result;
            }
        }

		$oper   = "Withdraw";
		$res    = $this->PostTarget($oper, $params);
		$result = $this->dealWithResult($res->WithdrawResult);
        $this->utils->debug_log("=========================bojimart withdrawal result Array", $result);

        $decodedResult = $this->decodeResult($result);
        $this->utils->debug_log("Decoded Result", $decodedResult);
        return $decodedResult;
    }

    public function decodeResult($resultString, $transId = null, $queryAPI = false) {
        $result = array('success' => false, 'message' => 'Payment failed');
        $this->utils->debug_log("========================bojimart decodeResult resultString", $resultString);

		if($queryAPI){
	        if($resultString['status'] == '1') {
                $result['success'] = true;
	            $result['message'] = sprintf('Bojimart withdrawal payment was successful: trade ID [%s]', $resultString['transaction_id']);
	        }
	        else if($resultString['status'] == '0') {
	            $result['message'] = 'Bojimart response status: ['.$resultString['status'].']: pending';
	        }
	        else if($resultString['status'] == '2') {
	            $result['message'] = 'Bojimart response status: ['.$resultString['status'].']: rejected';
	            $this->CI->wallet_model->withdrawalAPIReturnFailure($transId, $result['message']);
	        }
	        else if($resultString['status'] == '3') {
	            $result['message'] = 'Bojimart response status: ['.$resultString['status'].']: processing';
	        }
	        else {
	            $result['message'] = 'Bojimart response : '.$resultString['info'];
	        }

	        return $result;
		}
		else{
			if(isset($resultString['success'])) {
				$returnCode = $resultString['success'];
				if($returnCode == true) {
					return array('success' => true, 'message' => $resultString['info']);
				}
				return array('success' => false, 'message' => $resultString['info']);

			}
			else{
				$message = 'Bojimart withdrawal decode fail. API response: '.$resultString;
				return array('success' => false, 'message' => $message);
			}
		}
    }

    public function checkWithdrawStatus($transId) {
		$params_key = array('client', 'withdrawalid');
		$post_key   = array('client', 'withdrawalid', 'hashdata');


        $params = array();
        $params['client'] = $this->getSystemInfo("account");
        $params['withdrawalid'] = $transId;
        $params['hashdata'] = $this->sign($params, $params_key);


        $post['Data'] = $this->PostString($params, $post_key);
		$oper   = "CheckWithdrawalStatusByWithdrawalId";
		$res    = $this->PostTarget($oper, $post);
		$result = $this->dealWithMixResult($res->CheckWithdrawalStatusByWithdrawalIdResult);


        $this->CI->utils->debug_log('========================bojimart checkWithdrawStatus deal result', $result);
        $decodedResult = $this->decodeResult($result, $transId, true);

        return $decodedResult;
    }

    public function callbackFromServer($transId, $params) {
        $result = array('success' => false, 'message' => 'Payment failed');
        $response_result_id = parent::callbackFromServer($transId, $params);
        $order = $this->CI->wallet_model->getWalletAccountByTransactionCode($transId);
        $this->CI->utils->debug_log('=========================bojimart process withdrawalResult transId', $transId);
        $this->CI->utils->debug_log('=========================bojimart process withdrawalResult params', $params);

        $params = json_decode($params["result"], true);
        $this->CI->utils->debug_log('=========================bojimart process withdrawalResult json_decode params', $params);

        if (!$this->checkCallbackOrder($order, $params)) {
            return $result;
        }

        if ($params['status'] == self::CALLBACK_STATUS_SUCCESS) {
            $msg = sprintf('Bojimart withdrawal Payment was successful: trade ID [%s]', $params['orderId']);
            $this->CI->wallet_model->withdrawalAPIReturnSuccess($transId, $msg);
            $result['message'] = self::RETURN_SUCCESS_CODE;
            $result['success'] = true;
        } else if($params['status'] == self::CALLBACK_STATUS_FAILED){
            $msg = sprintf('Bojimart withdrawal payment was not successful: status code [%s].', $params['status']);
            $this->writePaymentErrorLog($msg, $params);
            $this->CI->wallet_model->withdrawalAPIReturnFailure($transId, $msg);
            $result['message'] = $msg;
        } else {
            $msg = sprintf('Bojimart withdrawal payment was not successful: status code [%s].', $params['status']);
            $this->writePaymentErrorLog($msg, $params);
            $result['message'] = $msg;
        }

        return $result;
    }

    private function checkCallbackOrder($order, $fields, &$processed = false) {
        $requiredFields = array(
            'cmd', 'clientId', 'orderId', 'amount', 'status', 'hashData'
        );

        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("=====================bojimart Missing parameter: [$f]", $fields);
                return false;
            }
        }

        if (!$this->validateSign($fields)) {
            $this->writePaymentErrorLog('=====================bojimart Signature Error', $fields);
            return false;
        }

        $processed = true; # processed is set to true once the signature verification pass

        $check_amount = $this->convertAmountToCurrency($order['amount']);
        if ($fields['amount'] != $check_amount) {
            $this->writePaymentErrorLog("======================bojimart Payment amount is wrong, expected <= ". $check_amount, $fields);
            return false;
        }

        if ($fields['orderId'] != $order['transactionCode']) {
            $this->writePaymentErrorLog("======================bojimart Payment order IDs do not match, expected [". $order['transactionCode']. "]", $fields);
            return false;
        }

        # everything checked ok
        return true;
    }

    public function getBOJIMARTBankInfo() {
        $bankInfo = array();
        $bankInfoArr = $this->getSystemInfo("bojimart_bank_info");
        if(!empty($bankInfoArr)) {
			foreach($bankInfoArr as $bankInfoItem) {
				$bankInfo[$bankInfoItem[0]] = $bankInfoItem[1];
			}
            $this->utils->debug_log("==================getting bojimart bank info from extra_info: ", $bankInfo);
        } else {
            $bankInfo = array(
                '1' => '中国工商银行',
                '2' => '招商银行',
                '3' => '中国建设银行',
                '4' => '中国农业银行',
                '5' => '交通银行',
                '6' => '中国银行',
                '7' => '深圳发展银行',
                '8' => '广发银行',
                '10' => '中信银行',
                '11' => '中国民生银行',
                '12' => '中国邮政储蓄银行',
                '13' => '兴业银行',
                '14' => '华夏银行',
                '15' => '平安银行',
                '17' => '广州银行',
                '18' => '南京银行',
                '20' => '中国光大银行',
                '24' => '浦发银行',
            );

            $this->utils->debug_log("==================getting bojimart bank info from code: ", $bankInfo);
        }
        return $bankInfo;
    }

    public function getNotifyUrl($orderId) {
        return parent::getCallbackUrl('/callback/process/' . $this->getPlatformCode() . '/' . $orderId);
    }

    public function convertAmountToCurrency($amount) {
        return number_format($amount, 2, '.', '');;
    }

    public function sign($params, $params_key) {
        $sign_str = '';
        foreach ($params_key as $key) {
            if(array_key_exists($key, $params)) {
                $sign_str .= $params[$key];
            }
        }
		$sign_str .= $this->getSystemInfo("key");
        $sign = md5($sign_str);

        return $sign;
    }

    private function validateSign($params) {
        $keys = array('cmd', 'clientId', 'orderId', 'amount');
        $sign_str = '';
        foreach ($keys as $key) {
            if(array_key_exists($key, $params)) {
                $sign_str .= $key.'=['.$params[$key].']';
            }
        }
        $sign_str .= 'clientKey=['.$this->getSystemInfo("key").']';
        $sign = md5($sign_str);



        if($params['hashData'] == $sign)
            return true;
        else
            return false;
    }

    public function PostString($params, $params_key) {
        $data_str = '';
        foreach ($params_key as $key) {
            if(array_key_exists($key, $params)) {
                $data_str .= $key.'='.$params[$key].',';
            }
        }
        $data_str = rtrim($data_str, ',');

        
        return $data_str;
    }

    public function PostTarget($oper, $post) {
    	$url = $this->getWithdrawUrl();
        $this->utils->debug_log('========================bojimart PostTarget url', $url);
        $this->utils->debug_log('========================bojimart ['.$oper.'] PostTarget post', $post);

        $this->createSoapClient($url);
        try{
            $res = $this->soapClient->__soapCall($oper, array($post));
        } catch (SoapFault $fault){
            $res = 'Construct Soap Server Failure : '. $fault->faultstring;
        }

        $this->utils->debug_log('========================bojimart ['.$oper.'] PostTarget result: ', $res);
        return $res;
    }

    private function dealWithResult($result){
        $return = [];
        $tempArr = explode(":",$result);

        if($tempArr[0] == "S"){
            $return['success'] = true;
        }else{
            $return['success'] = false;
        }
        $return['info'] = $tempArr[1];
        return $return;
    }

    private function dealWithMixResult($result){
        $return = [];
        $tempArr = explode(":",$result);
        $tempArrLen = count($tempArr);
        switch ($tempArrLen) {
            case 2:
                if($tempArr[0] == "S"){
                    $return['success'] = 'S';
                    $return['info'] = "";
                    $return['status'] = $tempArr[1];
                }else{
                    $return['success'] = 'E';
                    $return['info'] = $tempArr[1];
                    $return['status'] = "";
                }
                $return['transaction_id'] = "";
            break;
            case 3:
                if($tempArr[0] == "S"){
                    $return['success'] = true;
                    $return['info'] = "";
                    $return['status'] = 1;
                    $return['transaction_id'] = $tempArr[2];
                }else{
                    $this->utils->debug_log('========================bojimart dealWithMixResult ERROR', $result);
                    exit();
                }
            break;
            default:
                $this->utils->debug_log('========================bojimart dealWithMixResult ERROR', $result);
                exit();
            break;
        }

        return $return;
    }

    protected function createSoapClient($url) {
        $opts = array(
            'ssl' => array(
                'SNI_enabled' => false,
                'verify_peer'=>false,
                'verify_peer_name'=>false
            )
        );

        if($this->getSystemInfo('call_http_proxy_host')) {
            $options = array(
                'compression' => SOAP_COMPRESSION_ACCEPT | SOAP_COMPRESSION_GZIP,
                'save_response' => true,
                'exceptions' => true,
                'ignore_ssl_verify' => $this->getSystemInfo('ignore_ssl_verify'),
                'call_http_proxy_host' => $this->getSystemInfo('call_http_proxy_host'),
                'call_http_proxy_port' => $this->getSystemInfo('call_http_proxy_port'),
                'call_socks5_proxy' => $this->getSystemInfo('call_socks5_proxy'),
                'encoding' => 'UTF-8',
                'verifypeer' => false,
                'verifyhost' => false,
                'soap_version' => SOAP_1_2,
                'trace' => true,
                "connection_timeout" => 30,
                'stream_context' => stream_context_create($opts),
                'cache_wsdl' => WSDL_CACHE_NONE
            );
            $this->soapClient = new ProxySoapClient($url, $options);
        }
        else {
            $options = array(
                'compression' => SOAP_COMPRESSION_ACCEPT | SOAP_COMPRESSION_GZIP,
                'save_response' => true,
                'exceptions' => true,
                'encoding' => 'UTF-8',
                'verifypeer' => false,
                'verifyhost' => false,
                'soap_version' => SOAP_1_2,
                'trace' => true,
                "connection_timeout" => 30,
                'stream_context' => stream_context_create($opts),
                'cache_wsdl' => WSDL_CACHE_NONE
            );
            $this->soapClient = new SoapClient($url, $options);
        }
    }

}
