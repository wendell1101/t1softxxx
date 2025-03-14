<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * LFTPAY
 *
 * * LFTPAY_WITHDRAWAL_PAYMENT_API, ID: 5620
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://apic.lftpay.cc
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */

class Payment_api_lftpay_withdrawal extends Abstract_payment_api {
    const RESP_SUCCESS_CODE = 100000;
    const CALLBACK_SUCCESS = 0;
    const CALLBACK_FAILED = 1;
    const CALLBACK_PROGRESSING = 2;
    const CALLBACK_CLOSED = 3;
    const CALLBACK_PENDING = 4;
    const CALLBACK_PROCESSING = 5;
    const CALLBACK_MSG_SUCCESS = 'success';
    const ORDER_TYPE_REVERSE = 3;
    const ORDERTYPE_REVERSE_MSG = 'This order is a reverse order';

    public function getPlatformCode() {
        return LFTPAY_WITHDRAWAL_PAYMENT_API;
    }

    public function getPrefix() {
        return 'lftpay_withdrawal';
    }

    public function __construct($params = null) {
        parent::__construct($params);
        $this->_custom_curl_header = array('application/x-www-form-urlencoded');
    }

    # Implement abstract function but do nothing
    public function generatePaymentUrlForm($orderId, $playerId, $amount, $orderDateTime, $playerPromoId = NULL, $enabledSecondUrl = true, $bankId = NULL) {}
    public function directPay($order) {}

    public function getWithdrawUrl() {
        return $this->getSystemInfo('url');
    }

    public function getWithdrawParams($bank, $accNum, $name, $amount, $transId) {
        $this->CI->load->model(array('wallet_model', 'player_model', 'playerbankdetails'));

        # look up bank code
        $bankInfo = $this->getBankInfo();
        $playerBankDetails = $this->CI->playerbankdetails->getBankDetailsByBankAccount($bank, $accNum);
        $this->utils->debug_log("Get playerBankDetails using [$bank] + [$accNum]", $playerBankDetails);
        if(!empty($playerBankDetails)){
            $bankBranch  = empty($playerBankDetails['branch'])      ? "无" : $playerBankDetails['branch'];
        } else {
            $bankBranch  = '无';
        }

        $order = $this->CI->wallet_model->getWalletAccountByTransactionCode($transId);

        $params = array();
        $params['account'] = $this->getSystemInfo("account");
        $params['business'] = $name;  //收款人
        $params['businessBank'] = $bankInfo[$bank]['name'];
        $params['businessCard'] = $accNum;
        $params['businessDescription'] = "";
        $params['businessPhone'] = "";
        $params['money'] = $this->convertAmountToCurrency($amount); //元
        $params['notifyUrl'] = $this->getNotifyUrl($transId);
        $params['reverseUrl'] = $this->getNotifyUrl($transId);
        $params['shOrderId'] = $transId;
        $params['sign'] = $this->sign($params);

        return $params;
    }

    public function submitWithdrawRequest($bank, $accNum, $name, $amount, $transId) {
        $result = array('success' => false, 'message' => 'payment failed');

        if(!$this->isAllowWithdraw()) {
            $result['message'] = lang("Withdraw not allowed with this API");
            $this->utils->debug_log($result);
            return $result;
        }
        if(!array_key_exists($bank, $this->getBankInfo())) {
            $this->utils->error_log("========================lftpay withdrawal bank whose bankTypeId=[$bank] is not supported by lftpay");
            return array('success' => false, 'message' => 'Bank not supported by lftpay');
        }

        $params = $this->getWithdrawParams($bank, $accNum, $name, $amount, $transId);
        $this->CI->utils->debug_log('========================lftpay submitWithdrawRequest params:', $params);
        if(isset($params['success'])) {
			if($params['success'] == false) {
				$result['message'] = $params['message'];
				$this->utils->debug_log($result);
				return $result;
			}
        }

        $url = $this->getSystemInfo('url');

        list($content, $response_result) = $this->requestPost($url, $params, $transId);

        $decodedResult = $this->decodeResult($content);
        $this->CI->utils->debug_log('=========================lftpay submitWithdrawRequest decoded Result', $decodedResult);
        $decodedResult['response_result'] = $response_result;

        return $decodedResult;
    }

    function requestPost($url, $params, $orderSecureId){
        if (empty($url) || empty($params)) {
            return false;
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL,$url);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));

        $this->setCurlProxyOptions($ch);

		curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->getConnectTimeout());

        $response = curl_exec($ch);
        $this->CI->utils->debug_log('=========================requestPost curl response ', $response);

        $errCode = curl_errno($ch);
        $error = curl_error($ch);
        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

		$statusText = $errCode . ':' . $error;
        curl_close($ch);

        #withdrawal lock processing
        if(substr($orderSecureId, 0, 1) == 'W' && $errCode == '28') {
            $response = array('lock' => true, 'msg' => 'Ready to lock processing withdrawal order. curl error message: errCode = '.$errCode.' - '.$error);
        }

        $response_result_content = is_array($response) ? json_encode($response) : $response;

        #save response result
        $response_result_id = $this->submitPreprocess($params, $response_result_content, $url, $response, ['errCode' => $errCode, 'error' => $error, 'statusCode' => $statusCode], $orderSecureId);
        $response_result = [
            $params, $response_result_content, $url, $response, ['errCode' => $errCode, 'error' => $error, 'statusCode' => $statusCode], $orderSecureId
        ];

        $this->CI->utils->debug_log('=========================requestPost response_result', $response_result);
        return array($response, $response_result);
    }

    public function decodeResult($resultString, $queryAPI = false) {
        $result = json_decode($resultString, true);
        $this->utils->debug_log("=========================lftpay decodeResult result", $result);

        $respCode = $result['code'];
        $resultMsg = $result['message'];
        $this->utils->debug_log("=========================lftpay decodeResult resultMsg", $resultMsg);

        if($respCode == self::RESP_SUCCESS_CODE) {
            $message = "lftpay request successful.";
            return array('success' => true, 'message' => $message);
        }
        else {
            if($resultMsg == '' || $resultMsg == false) {
                $this->utils->error_log("========================lftpay return UNKNOWN ERROR!");
                $resultMsg = "未知错误";
            }

            $message = "lftpay withdrawal response, Code: [ ".$respCode." ] , Msg: ".$resultMsg;
            return array('success' => false, 'message' => $message);
        }
    }

    public function callbackFromServer($transId, $params) {
        $response_result_id = parent::callbackFromServer($transId, $params);
        if(empty($params) || is_null($params)){
            $raw_post_data = file_get_contents('php://input', 'r');
            $params = json_decode($raw_post_data, true);
        }
        $result = array('success' => false, 'message' => 'Payment failed');

        $this->utils->debug_log("==========================lftpay checkCallback params", $params);

        $order = $this->CI->wallet_model->getWalletAccountByTransactionCode($transId);

        if (!$this->checkCallbackOrder($order, $params)) {
            return $result;
        }

        if($params['data']['type'] == self::ORDER_TYPE_REVERSE && isset($params['data']['czOrderId'])){
            $this->utils->debug_log('=========================lftpay withdrawal payment was reversed: trade ID [%s]', $params['data']['shOrderId']);
            $this->paymentExceptionOrder($order,$params,$response_result_id);
            $msg = sprintf('lftpay withdrawal payment was reversed: trade ID [%s]',$params['data']['shOrderId']);
            $result['message'] = self::CALLBACK_MSG_SUCCESS;
            $result['success'] = true;

        }else{
            if(($params['data']['state'] == self::CALLBACK_SUCCESS)){
                $this->utils->debug_log('=========================lftpay withdrawal payment was successful: trade ID [%s]', $params['data']['shOrderId']);
                $msg = sprintf('lftpay withdrawal was successful: trade ID [%s]',$params['data']['shOrderId']);
                $this->CI->wallet_model->withdrawalAPIReturnSuccess($transId, $msg);
                $result['message'] = self::CALLBACK_MSG_SUCCESS;
                $result['success'] = true;

            }else if($params['data']['state'] == self::CALLBACK_PENDING || $params['data']['state'] == self::CALLBACK_PROGRESSING || $params['data']['state'] == self::CALLBACK_PROCESSING){
                $this->utils->debug_log('=========================lftpay withdrawal payment was not successful: trade ID [%s] , status is [%s]', $params['data']['shOrderId'], $params['data']['state']);
                $msg = sprintf('lftpay withdrawal payment was not successful: trade ID [%s] , status is [%s]',$params['data']['shOrderId'], $params['data']['state']);
                $result['message'] = self::CALLBACK_MSG_SUCCESS;
                $result['success'] = true;

            }else if($params['data']['state'] == self::CALLBACK_FAILED || $params['data']['state'] != self::CALLBACK_CLOSED){
                $this->utils->debug_log('=========================lftpay withdrawal payment was failed: trade ID [%s] , status is [%s]', $params['data']['shOrderId'], $params['data']['state']);
                $msg = sprintf('lftpay withdrawal was failed: trade ID [%s] , status is [%s]',$params['data']['shOrderId'], $params['data']['state']);
                $this->CI->wallet_model->withdrawalAPIReturnFailure($transId, $msg);
                $result['message'] = $msg;

            }
            else {
                $msg = sprintf('lftpay withdrawal payment was not successful: trade ID [%s]',$params['data']['shOrderId']);
                $this->debug_log($msg, $params);
                $result['message'] = $msg;
            }
        }

        return $result;
    }

    public function checkCallbackOrder($order, $fields) {
        $requiredFields = array('shOrderId','money','sign');
        $data = $fields['data'];
        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $data)) {
                $this->writePaymentErrorLog("=======================lftpay withdrawal checkCallbackOrder missing parameter: [$f]", $data);
                return false;
            }
        }

        if ($data['sign'] != $this->validateSign($data)) {
            $this->writePaymentErrorLog('==========================lftpay withdrawal checkCallback signature Error',$fields);
            return false;
        }

		if ($fields['code'] != self::RESP_SUCCESS_CODE) {
			$this->writePaymentErrorLog("==========================lftpay withdrawal checkCallback status is not success", $fields);
			return false;
		}

        if (abs($data['money']) != $this->convertAmountToCurrency($order['amount'])) {
            $this->writePaymentErrorLog('=========================lftpay withdrawal checkCallbackOrder payment amount is wrong, expected =>'. $order['amount'], $fields);
            return false;
        }

        if ($data['shOrderId'] != $order['transactionCode']) {
            $this->writePaymentErrorLog('=========================lftpay withdrawal checkCallbackOrder order IDs do not match, expected =>'. $order['transactionCode'], $fields);
            return false;
        }
        # everything checked ok
        return true;
    }


    # -- signatures --
    public function sign($params) {
        $signStr = $this->createSignStr($params);
        $sign = strtoupper(md5($signStr));
        return $sign;
    }


    private function validateSign($params){
        $signStr = $this->createSignStr($params);
        $sign = strtoupper(md5($signStr));
        if($params['sign'] == $sign){
            return true;
        }
        else{
            return false;
        }
    }

    private function createSignStr($params) {
        ksort($params);
        $signStr = '';
        foreach($params as $key => $value) {
			if($key == 'sign' || $key == 'czOrderId'){
				continue;
            }
               $signStr .= "$key=$value&";
        }

        return $signStr.'key='.$this->getSystemInfo('key');
    }

	# -- amount --
	protected function convertAmountToCurrency($amount) {
        return number_format($amount, 2);
    }

    # -- bankinfo --
    public function getBankInfo() {
        $bankInfo = array();
        $bankInfoArr = $this->getSystemInfo("withdrawal_bank_info");
        if(!empty($bankInfoArr)) {
            foreach($bankInfoArr as $system_bank_type_id => $bankInfoItem) {
                if(isset($bankInfoItem['name'])){
                    $bankInfo[$system_bank_type_id]['name'] = $bankInfoItem['name'];
                }
                if(isset($bankInfoItem['code'])){
                    $bankInfo[$system_bank_type_id]['code'] = $bankInfoItem['code'];
                }
            }
            $this->utils->debug_log("==================getting lftpay bank info from extra_info: ", $bankInfo);
        } else {
            $bankInfo = array(
                '1' => array('name' => '中国工商银行'),
                '2' => array('name' => '招商银行'),
                '3' => array('name' => '中国建设银行'),
                '4' => array('name' => '中国农业银行'),
                '5' => array('name' => '交通银行'),
                '6' => array('name' => '中国银行'),
                '9' => array('name' => '东莞农村商业银行'),
                '10' => array('name' => '中信银行'),
                '11' => array('name' => '中国民生银行'),
                '12' => array('name' => '中国邮政储蓄银行'),
                '13' => array('name' => '兴业银行'),
                '14' => array('name' => '华夏银行'),
                '15' => array('name' => '平安银行'),
                '17' => array('name' => '广州银行'),
                '18' => array('name' => '南京银行'),
                '19' => array('name' => '广州农商银行'),
                '20' => array('name' => '中国光大银行'),
                '26' => array('name' => '广发银行'),
                '27' => array('name' => '上海浦东发展银行'),
                '28' => array('name' => '东亚银行'),
                '29' => array('name' => '北京银行'),
                '30' => array('name' => '天津银行'),
                '31' => array('name' => '上海银行'),
                '32' => array('name' => '上海农商银行'),
                '33' => array('name' => '北京农商银行'),
                '34' => array('name' => '中国农业发展银行'),
            );

            $this->utils->debug_log("=======================getting lftpay bank info from code: ", $bankInfo);
        }
        return $bankInfo;
    }

    protected function getNotifyUrl($orderId) {
        return parent::getCallbackUrl('/callback/process/' . $this->getPlatformCode() . '/' . $orderId);
    }

    private function paymentExceptionOrder($order,$params,$response_result_id) {
        $this->CI->load->model(['sale_order']);
        $external_system_id = $this->getPlatformCode();
        $amount = $params['data']['money'];
        $external_order_id = $params['data']['orderId']; //平台冲正订单号
        $external_order_datetime = '';
        $player_bank_name = $params['data']['businessBank'];
        $player_bank_account_name = $params['data']['business'];
        $player_bank_account_number = $params['data']['businessCard'];
        $player_bank_address = '';
        $collection_bank_name = '';
        $collection_bank_account_name = '';
        $collection_bank_account_number = '';
        $collection_bank_address = '';
        $saleOrderId = null;
        $withdrawal_order_id = $order['walletAccountId'];
        $remarks = self::ORDERTYPE_REVERSE_MSG;
        //write to exception order
        $exception_order_id=$this->CI->sale_order->createExceptionDeposit($external_system_id, $amount, $external_order_id,
            $external_order_datetime, $response_result_id,
            $player_bank_name, $player_bank_account_name, $player_bank_account_number, $player_bank_address,
            $collection_bank_name, $collection_bank_account_name, $collection_bank_account_number, $collection_bank_address,
            $params, $saleOrderId , $withdrawal_order_id, $remarks);
        $message = self::ORDERTYPE_REVERSE_MSG;
    }
}