<?php
require_once dirname(__FILE__) . '/abstract_payment_api_rpn.php';
/**
 * RPN
 *
 * * RPN_WITHDRAWAL_V2_PAYMENT_API, ID: 5142
 * * RPN_WITHDRAWAL_V2_2_PAYMENT_API, ID: 5143
 * * RPN_WITHDRAWAL_V2_3_PAYMENT_API, ID: 5144
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://query.rpnpay.com/payout.php
 * * Account: ## User ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_rpn_withdrawal_v2 extends Abstract_payment_api_rpn {
    const RETURN_STATUS_SUCCESS    = 20;

    const CALLBACK_STATUS_PROCESSING = 20;
    const CALLBACK_STATUS_SUCCESS    = 30;
    const CALLBACK_STATUS_FAILED     = 25;
    const CALLBACK_STATUS_CANCELED   = 40;
    const CALLBACK_STATUS_REFUNDED   = 50;


    const RETURN_SUCCESS_CODE = "ok";

    public function getPlatformCode() {
        return RPN_WITHDRAWAL_V2_PAYMENT_API;
    }

    public function getPrefix() {
        return 'rpn_withdrawal_v2';
    }

    # Implement abstract function but do nothing
    protected function configParams(&$params, $direct_pay_extra_info) {}


    public function submitWithdrawRequest($bank, $accNum, $name, $amount, $transId) {
        $result = array('success' => false, 'message' => 'payment failed');

        if(!$this->isAllowWithdraw()) {
            $result['message'] = lang("Withdraw not allowed with this API");
            return $result;
        }
        if(!array_key_exists($bank, $this->getRPNBankInfo())) {
            $this->utils->error_log("========================RPN submitWithdrawRequest bank whose bankTypeId=[$bank] is not supported by rpn");
            return array('success' => false, 'message' => 'Bank not supported by RPN');
            $bank = '无';
        }

        $data = $this->getWithdrawParams($bank, $accNum, $name, $amount, $transId);
        $param['Withdrawal'] = json_encode($data);
        $url = $this->getWithdrawUrl();
        list($response, $response_result) = $this->submitPostForm($url, $param, false, $transId, true);

        $decodedResult = $this->decodeResult($response);
        $decodedResult['response_result'] = $response_result;

        $this->CI->utils->debug_log('======================================RPN submitWithdrawRequest url: ', $url );
        $this->CI->utils->debug_log('======================================RPN submitWithdrawRequest param: ', $param);
        $this->CI->utils->debug_log('======================================RPN submitWithdrawRequest response ', $response);
        $this->CI->utils->debug_log('======================================RPN submitWithdrawRequest decoded Result', $decodedResult);

        return $decodedResult;
    }

    public function submitPostForm($url, $params, $postJson=false, $orderSecureId=NULL, $return_all=false) {
		try {
			$ch = curl_init();

			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_HEADER, true);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

            if(!empty($this->_custom_curl_header)){
                curl_setopt($ch, CURLOPT_HTTPHEADER, $this->_custom_curl_header);
            }

			if($postJson){
				curl_setopt($ch, CURLOPT_POSTFIELDS, $this->CI->utils->encodeJson($params) );
			}else{
				curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params) );
			}

            $this->setCurlProxyOptions($ch);

			curl_setopt($ch, CURLOPT_TIMEOUT, $this->getTimeoutSecond());
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->getConnectTimeout());

			$response = curl_exec($ch);

			# If Extra Info has 'gb2312' set to execute encoding
			$response_encode = $this->getSystemInfo("response_encode") ? $this->getSystemInfo("response_encode") : "";
			if(!empty($response_encode)) {
				$response = iconv("UTF-8", $response_encode."//IGNORE", $response);
			}

			$response_encode = $this->getSystemInfo("response_mb_convert_encode") ? $this->getSystemInfo("response_mb_convert_encode") : "";
			if(!empty($response_encode)) {
				$response = mb_convert_encoding($response, 'UTF-8', $response_encode);
			}

			$this->CI->utils->debug_log('=========================RPN submitPostForm curl content ', $response);

			$errCode = curl_errno($ch);
			$error = curl_error($ch);
			$header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
			$header = substr($response, 0, $header_size);
			$content = substr($response, $header_size);

			$statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
			$last_url = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);

			$statusText = $errCode . ':' . $error;
			curl_close($ch);

			$this->CI->utils->debug_log('url', $url, 'params', $params , 'response', $response, 'errCode', $errCode, 'error', $error, 'statusCode', $statusCode);

			#withdrawal lock processing
			if(substr($orderSecureId, 0, 1) == 'W' && $errCode == '28') {	//curl_errno means timeout
				// $content = '{"lock": true, "msg": "Ready to lock processing withdrawal order. curl error message: errCode = '.$errCode.' - '.$error.'" }';
				$content = array('lock' => true, 'msg' => 'Ready to lock processing withdrawal order. curl error message: errCode = '.$errCode.' - '.$error);
			}

			$response_result_content = is_array($content) ? json_encode($content) : $content;

			#save response result
			$response_result_id = $this->submitPreprocess($params, $response_result_content, $url, $response, ['errCode' => $errCode, 'error' => $error, 'statusCode' => $statusCode], $orderSecureId);

			if($return_all){
				$response_result = [
					$params, $response_result_content, $url, $response, ['errCode' => $errCode, 'error' => $error, 'statusCode' => $statusCode], $orderSecureId
				];
				$this->CI->utils->debug_log('=========================RPN submitPostForm return_all response_result', $response_result);
				return array($content, $response_result);
			}

			return $content;
		} catch (Exception $e) {
			$this->CI->utils->error_log('POST failed', $e);
		}
    }

    protected function getTimeoutSecond() {
        $this->CI->utils->debug_log('==============RPN http_timeout',$this->getSystemInfo('http_timeout'));
        return $this->getSystemInfo('http_timeout');
	}

	protected function getConnectTimeout() {
        $this->CI->utils->debug_log('==============RPN http_timeout',$this->getSystemInfo('connect_timeout'));
        return $this->getSystemInfo('connect_timeout');
	}

    public function getWithdrawUrl() {
        return $this->getSystemInfo('url');
    }

    public function getWithdrawParams($bank, $accNum, $name, $amount, $transId) {
        $this->CI->load->model(array('wallet_model', 'player_model', 'playerbankdetails'));
        # look up bank code
        $bankInfo = $this->getRPNBankInfo();
        $bankName = $bankInfo[$bank];   //銀行名稱

        # look up bank detail
        $playerBankDetails = $this->CI->playerbankdetails->getBankDetailsByBankAccount($bank, $accNum);
        $this->utils->debug_log("Get playerBankDetails using [$bank] + [$accNum]", $playerBankDetails);
        if(!empty($playerBankDetails)){
            $bankBranch = empty($playerBankDetails['branch']) ? "无" : $playerBankDetails['branch'];
            $province = empty($playerBankDetails['province']) ? "无" : $playerBankDetails['province'];
            $city = empty($playerBankDetails['city']) ? "无" : $playerBankDetails['city'];
        } else {
            $bankBranch = '无';
            $province = '无';
            $city = '无';
        }

        $order = $this->CI->wallet_model->getWalletAccountByTransactionCode($transId);
        $params = array (
          "Mid" => $this->getSystemInfo('account'),
          "OrderSno" => $transId,
          "Encryption" => "1",
          "Frontend" => $this->getReturnUrl($transId),
          "Backend" => $this->getNotifyUrl($transId),
          "Currency" => "156",
          "BankName" => $bankName,
          "SubBranch" => $bankBranch,
          "BankAccountName" => $name,
          "BankCardNo" => $accNum,
          "Province" => $province,
          "Area" => $city,
          "Amount" => $this->convertAmountToCurrency($amount,$order['dwDateTime']),
        );
        $params['Sign'] = $this->sign($params);
        return $params;
    }

    public function decodeResult($resultString, $queryAPI = false) {
        if(is_array($resultString) && isset($resultString['lock']) && isset($resultString['msg'])) {
            $result = array('success' => false, 'message' => $resultString['msg'], 'lock' => true);
            return $result;
        }
        #different return type
        if(!is_null(json_decode($resultString))){
            $resultString = json_decode($resultString, true);
            $this->CI->utils->debug_log('==============RPN submitWithdrawRequest decodeResult json decoded', $resultString);
        }
        $success = false;
        $message = "RPN withdrawal decode fail.";
        if($queryAPI){
            #orderId|amount|status|order_time|signMsg
            $result = explode("|", $resultString);
            $this->CI->utils->debug_log('==============RPN checkWithdrawStatus decode result', $result);
            if(count($result) > 1){
                $returnCode = $result[2];
                if($returnCode == self::CALLBACK_STATUS_SUCCESS) {
                    $success = true;
                    $message = "RPN withdrawal success!";
                }
                else{
                    if($returnCode == self::CALLBACK_STATUS_FAILED || $returnCode == self::CALLBACK_STATUS_CANCELED || $returnCode == self::CALLBACK_STATUS_REFUNDED){
                        $message = "RPN withdrawal failed. Status Code: ".$returnCode;
                        $this->CI->wallet_model->withdrawalAPIReturnFailure($result[0], $message);
                    }
                    else if($returnCode == self::CALLBACK_STATUS_PROCESSING){
                        $message = "RPN withdrawal processing. Status Code: ".$returnCode;
                    }
                }

            }
            else if($resultString){
                $message = $resultString;
            }
            return array('success' => $success, 'message' => $message);
        }
        else{
            if(isset($resultString['RespCode'])) {
                $returnCode = $resultString['RespCode'];
                $returnDesc = urldecode($resultString['RespMsg']);
                if($returnCode == self::RETURN_STATUS_SUCCESS) {
                    $message = "RPN ".$returnDesc.", WSno: ". $resultString['WSno'];
                    return array('success' => true, 'message' => $message);
                }
                $message = "RPN withdrawal response failed, Code: ".$returnCode.", Desc: ".$returnDesc;
                return array('success' => false, 'message' => $message);

            }
            else{
                $message = $message.' API response: '.$resultString;
                return array('success' => false, 'message' => $message);
            }
        }
    }

    public function checkWithdrawStatus($transId) {
        $params = array();
        $params['mid']     = $this->getSystemInfo('account');
        $params['orderId'] = $transId;
        $params['start_order_time'] = '';
        $params['end_order_time'] = '';
        $params['sign'] = $this->checkWithdrawSign($params);
        $url = $this->getSystemInfo('check_status_url', 'https://query.rpnpay.com/query.php?r=payment/payout');
        $response = $this->submitPostForm($url, $params, false, $transId);
        $decodedResult = $this->decodeResult($response, true);

        $this->CI->utils->debug_log('======================================RPN checkWithdrawStatus params: ', $params);
        $this->CI->utils->debug_log('======================================RPN checkWithdrawStatus url: ', $url);
        $this->CI->utils->debug_log('======================================RPN checkWithdrawStatus response: ', $response);
        $this->CI->utils->debug_log('======================================RPN checkWithdrawStatus decoded Result', $decodedResult);
        return $decodedResult;
    }

    public function callbackFromServer($transId, $params) {
        $response_result_id = parent::callbackFromServer($transId, $params);

        $result = array('success' => false, 'message' => 'Payment failed');
        $order = $this->CI->wallet_model->getWalletAccountByTransactionCode($transId);
        if(empty($params)){
            $raw_post_data = file_get_contents('php://input', 'r');
            $this->CI->utils->debug_log("=====================RPN raw_post_data", $raw_post_data);
            $params = json_decode($raw_post_data, true);
            $this->CI->utils->debug_log("=====================RPN json_decode params", $params);
        }

        if (!$this->checkCallbackOrder($order, $params)) {
            return $result;
        }

        if ($params['RespCode'] == self::CALLBACK_STATUS_SUCCESS) {
            $msg = sprintf('RPN withdrawal Payment was successful: trade ID [%s]', $params['OrderSno']);
            $this->CI->wallet_model->withdrawalAPIReturnSuccess($transId, $msg);
            $result['message'] = self::RETURN_SUCCESS_CODE;
            $result['success'] = true;
        } else if($params['RespCode'] == self::CALLBACK_STATUS_FAILED || $params['RespCode'] == self::CALLBACK_STATUS_CANCELED || $params['RespCode'] == self::CALLBACK_STATUS_REFUNDED){
            $msg = sprintf('RPN withdrawal payment failed, [%s]: '.$params['RespMsg'], $params['RespCode']);
            $this->writePaymentErrorLog($msg, $params);
            $this->CI->wallet_model->withdrawalAPIReturnFailure($transId, $msg);
            $result['message'] = $msg;
        } else {
            $msg = sprintf('RPN withdrawal payment was not successful, [%s]: '.$params['RespMsg'], $params['RespCode']);
            $this->writePaymentErrorLog($msg, $params);
            $result['message'] = $msg;
        }

        return $result;
    }

    private function checkCallbackOrder($order, $fields) {
        # does all required fields exist in the header?
        $requiredFields = array(
            'RespCode', 'WSno', 'OrderSno', 'Amount', 'Sign'
        );
        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("======================RPN withdrawal checkCallbackOrder missing parameter: [$f]", $fields);
                return false;
            }
        }

        if (!$this->validateSign($fields)) {
            $this->writePaymentErrorLog('=========================RPN withdrawal checkCallback signature Error', $fields['Sign']);
            return false;
        }

        $amount = $this->convertAmountToCurrency($order['amount'],$order['dwDateTime']);
        if ($fields['Amount'] != $amount) {
            $diff = abs($fields['Amount'] - $amount);
            $limit = $this->getSystemInfo('rate_diff_allowance', 1);
            if($diff > $limit){
                $this->writePaymentErrorLog("======================RPN withdrawal checkCallbackOrder payment amount is wrong, expected [". $amount. "]", $fields['amount']);
                return false;
            }
        }

        if ($fields['OrderSno'] != $order['transactionCode']) {
            $this->writePaymentErrorLog("======================RPN withdrawal checkCallbackOrder order IDs do not match, expected [". $order['transactionCode']. "]", $fields);
            return false;
        }

        # everything checked ok
        return true;
    }

    public function getRPNBankInfo() {
        $bankInfo = array();
        $bankInfoArr = $this->getSystemInfo("rpn_bank_info");
        if(!empty($bankInfoArr)) {
            foreach($bankInfoArr as $bankInfoItem) {
                $bankInfo[$bankInfoItem[0]] = $bankInfoItem[1];
            }
            $this->utils->debug_log("==================getting RPN bank info from extra_info: ", $bankInfo);
        } else {
            $bankInfo = array(
                '1' => '中国工商银行',
                '2' => '招商银行',
                '3' => '中国建设银行',
                '4' => '中国农业银行',
                '5' => '交通银行',
                '6' => '中国银行',
                '8' => '广发银行',
                '10' => '中信银行',
                '11' => '中国民生银行',
                '12' => '中国邮政储蓄银行',
                '13' => '兴业银行',
                '14' => '华夏银行',
                '15' => '平安银行',
                '20' => '中国光大银行',
                '24' => '浦发银行'
            );
            $this->utils->debug_log("=======================getting RPN bank info from code: ", $bankInfo);
        }
        return $bankInfo;
    }

    # -- signatures --
    # Reference: PHP Demo
    private function sign($params) {
        $signStr = $this->createSignStr($params);
        $sign = md5($signStr);
        $this->CI->utils->debug_log("=======================RPN Signing [$signStr], signature is", $sign);
        return $sign;
    }

    private function createSignStr($params) {
        $keys = array('Mid', 'OrderSno', 'Amount');
        $signStr = "";
        foreach($keys as $key) {
            if (array_key_exists($key, $params)) {
                $signStr .= $params[$key].'|';
            }
        }
        $signStr .= $this->getSystemInfo('key');
        return $signStr;
    }

    private function checkWithdrawSign($params) {
        $signStr = '';
        foreach($params as $key => $value) {
            $signStr .= "$key=$value";
        }

        $signStr .= "key=".$this->getSystemInfo('key');
        $sign = md5($signStr);
        $this->CI->utils->debug_log("=======================RPN checkWithdrawSign [$signStr], signature is", $sign);
        return $sign;
    }

    private function validateSign($params) {
        $keys = array('RespCode', 'WSno', 'Amount');
        $signStr = "";
        foreach($keys as $key) {
            if (array_key_exists($key, $params)) {
                $signStr .= $params[$key].'|';
            }
        }
        $signStr .= $this->getSystemInfo('key');
        $sign = md5($signStr);

        if($params['Sign'] == $sign)
            return true;
        else{
            $this->writePaymentErrorLog("=======================RPN validateSign signStr [$signStr], signature is [$sign]", $params['Sign']);
            return false;
        }
    }

    # -- Private functions --
    # After payment is complete, the gateway will invoke this URL asynchronously
    public function getNotifyUrl($orderId) {
        return parent::getCallbackUrl('/callback/process/' . $this->getPlatformCode() . '/' . $orderId);
    }

    ## After payment is complete, the gateway will send redirect back to this URL
    public function getReturnUrl($orderId) {
        return parent::getCallbackUrl('/callback/browser/success/' . $this->getPlatformCode() . '/' . $orderId);
    }

	protected function convertAmountToCurrency($amount, $orderDateTime) {
		if($this->getSystemInfo('use_usd_currency')){
			if(is_string($orderDateTime)){
				$orderDateTime = DateTime::createFromFormat('Y-m-d H:i:s', $orderDateTime);
			}
			$amount = $this->gameAmountToDBByCurrency($amount, $this->utils->getTimeForMysql($orderDateTime),'USD','CNY');
			$this->CI->utils->debug_log('=====================RPN convertAmountToCurrency use_usd_currency', $amount);
		}
		return number_format($amount, 2, '.', '');
	}
}