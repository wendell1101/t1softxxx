<?php
require_once dirname(__FILE__) . '/abstract_payment_api_xinpay.php';

/**
 * xinpay 取款
 *
 * * XINPAY_WITHDRAWAL_PAYMENT_API, ID: 5224
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://tl.7xinpy.com/withdraw/singleOrder
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##g
 *
 * * Extra Info:
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_xinpay_withdrawal extends Abstract_payment_api_xinpay {
    const ORDER_STATUS_SUCCESS = "SUCCESS";
    const ORDER_STATUS_UNKNOW = "UNKNOW";
    const QUERY_STATUS_SUCCESS = "SUCCESS";


    public function getPlatformCode() {
        return XINPAY_WITHDRAWAL_PAYMENT_API;
    }

    public function getPrefix() {
        return 'xinpay_withdrawal';
    }

    # Implement abstract function but do nothing
    protected function configParams(&$params, $direct_pay_extra_info) {}

    protected function processPaymentUrlForm($params) {}

    public function getWithdrawUrl() {
        return $this->getSystemInfo('url');
    }

    public function getWithdrawParams($bank, $accNum, $name, $amount, $transId) {
        $this->CI->load->model(array('wallet_model', 'player_model', 'playerbankdetails'));

        $playerBankDetails = $this->CI->playerbankdetails->getBankDetailsByBankAccount($bank, $accNum);
        $this->utils->debug_log("Get playerBankDetails using [$bank] + [$accNum]", $playerBankDetails);
        if(!empty($playerBankDetails)){
            $province    = empty($playerBankDetails['province'])    ? "无" : $playerBankDetails['province'];
            $city        = empty($playerBankDetails['city'])        ? "无" : $playerBankDetails['city'];
            $bankBranch  = empty($playerBankDetails['branch'])      ? "无" : $playerBankDetails['branch'];
            $bankAddress = empty($playerBankDetails['bankAddress']) ? "无" : $playerBankDetails['bankAddress'];
        } else {
            $bankBranch  = '无';
            $province    = '无';
            $city        = '无';
            $bankAddress = '无';
        }

        $params = array();
        $params['mer_no'] = $this->getSystemInfo('account');
        $params['mer_order_no'] = $transId;
        $params['acc_type'] = "1"; #(1对私， 2对公
        $params['acc_no'] = $accNum;
        $params['acc_name'] = $name;
        $params['order_amount'] = $this->convertAmountToCurrency($amount);
        $params['province'] = $province;
        $params['city'] = $city;
        $params['sign'] = $this->sign($params);

        $this->CI->utils->debug_log('======================================xinpay getWithdrawParams :', $params);
        return $params;
    }

    public function submitWithdrawRequest($bank, $accNum, $name, $amount, $transId) {
        $result = array('success' => false, 'message' => 'payment failed');
        if(!$this->isAllowWithdraw()) {
            $result['message'] = lang("Withdraw not allowed with this API");
            $this->utils->debug_log('======================================xinpay submitWithdrawRequest result: ',$result);
            return $result;
        }
        # look up bank code
        $bankInfo = $this->getBankInfo();
        if(!array_key_exists($bank, $bankInfo)) {
            $this->utils->error_log("========================xinpay withdrawal bank whose bankTypeId=[$bank] is not supported by xinpay");
            return array('success' => false, 'message' => 'Bank not supported by xinpay');
        }

        $params = $this->getWithdrawParams($bank, $accNum, $name, $amount, $transId);
        $url = $this->getSystemInfo('url');

        list($response, $response_result) = $this->submitPostForm($url, $params, true, $transId, true);

        $decodedResult = $this->decodeResult($response);
        $decodedResult['response_result'] = $response_result;
        return $decodedResult;
    }


     public function decodeResult($resultString, $queryAPI = false) {
        if(is_array($resultString) && isset($resultString['lock']) && isset($resultString['msg'])) {
            $result = array('success' => false, 'message' => $resultString['msg'], 'lock' => true);
            return $result;
        }

        $result = json_decode($resultString, true);
        $this->utils->debug_log("=========================xinpay json_decode result string", $result);

        if($queryAPI){
				if($result['query_status'] == self::QUERY_STATUS_SUCCESS){
					$message = 'Xinpay payment response successful, result Code:'.$result['query_err_code'].", Desc:".$result['query_err_msg'];
					return array('success' => true, 'message' => $message);
				}else if($result['query_status'] == self::ORDER_STATUS_UNKNOW){
                    $message = "Xinpay payment response processing, Code:".$result['query_err_code'].", Desc:".$result['query_err_msg'];
                    return array('success' => false, 'message' => $message);
                }else{
					$message = "Xinpay payment failed for Code:".$result['query_err_code'].", Desc:".$result['query_err_msg'];
					$this->CI->wallet_model->withdrawalAPIReturnFailure($result['mer_order_no'], $message);
					return array('success' => false, 'message' => $message);
				}
        }
        else{
            if($result['status'] == self::ORDER_STATUS_SUCCESS) {
                $message = 'Xinpay request successful!';
                return array('success' => true, 'message' => $message);
            }
            else {
                if($result['err_msg'] == '' || $result['err_msg'] == false) {
                    $result['err_msg'] = "未知错误";
                }

                $message = "Xinpay withdrawal response, Code: ".$result['err_code'].", Desc:".$result['err_msg'];
                return array('success' => false, 'message' => $message);
            }
        }
    }

    public function checkWithdrawStatus($transId) {
        $params = array();
        $params['request_no'] = date('YmdHis')."1";
        $params['request_time'] = date('YmdHis');
        $params['mer_no'] = $this->getSystemInfo("account");
        $params['mer_order_no'] = $transId;
        $params['sign'] = $this->sign($params);

        $url = $this->getSystemInfo('check_withdraw_status_url');
        $response = $this->submitPostForm($url, $params,true, $transId);
        $decodedResult = $this->decodeResult($response, true);

        return $decodedResult;
    }

    public function getOrderIdFromParameters($params) {
        if(empty($params)){
            $raw_post_data = file_get_contents('php://input', 'r');
            $params = json_decode($raw_post_data, true);
        }

        $transId = null;

        if (isset($params['orders'][0]['mer_order_no'])) {
            $trans_id = $params['orders'][0]['mer_order_no'];

            $this->CI->load->model(array('wallet_model'));
            $walletAccount = $this->CI->wallet_model->getWalletAccountByTransactionCode($trans_id);

            if(!empty($walletAccount)){
                $transId = $walletAccount['transactionCode'];
            }else{
                $this->utils->debug_log('====================================xinpay callbackOrder transId is empty when getOrderIdFromParameters', $params);
            }
        }
        else {
            $this->utils->debug_log('====================================xinpay callbackOrder cannot get any transId when getOrderIdFromParameters', $params);
        }
        return $transId;
    }

    public function callbackFromServer($transId, $params) {
        $result = array('success' => false, 'message' => 'Payment failed');

        if(empty($params)){
            $raw_post_data = file_get_contents('php://input', 'r');
            $params = json_decode($raw_post_data, true);
        }

        $order = $this->CI->wallet_model->getWalletAccountByTransactionCode($transId);
        if (!$this->checkCallbackOrder($order, $params)) {
            return $result;
        }

        if($result['orders'][0]['result'] == self::ORDER_STATUS_SUCCESS) {
            $this->utils->debug_log('=========================xinpay withdrawal payment was successful: trade ID [%s]', $params['orders'][0]['mer_order_no']);

            $msg = sprintf('xinpay withdrawal was successful: trade ID [%s]',$params['orders'][0]['mer_order_no']);
            $this->CI->wallet_model->withdrawalAPIReturnSuccess($transId, $msg);

            $result['message'] = self::ORDER_STATUS_SUCCESS;
            $result['success'] = true;
        } else {
            $realStateDesc = $params['orders'][0]['mer_order_no'];
            $this->errMsg = '['.$realStateDesc.']';
            $msg = sprintf('xinpay withdrawal payment was not successful: '.$this->errMsg);

            $result['message'] = $msg;
        }

        return $result;
    }


     public function checkCallbackOrder($order, $fields) {
        $requiredFields = array('status', 'mer_no', 'mer_order_no','acc_type','acc_no','acc_name','order_amount');

        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("======================xinpay withdrawal checkCallbackOrder missing parameter: [$f]", $fields);
                return false;
            }
        }

        if ($fields['sign']!=$this->validateSign($fields)) {
            $this->writePaymentErrorLog('=========================xinpay withdrawal checkCallback signature Error',$fields);
            return false;
        }

        if ($fields['mer_order_no'] != $order['transactionCode']) {
            $this->writePaymentErrorLog("========================xinpay checkCallbackOrder type2 order IDs do not match, expected [$order->secure_id]", $fields);
            return false;
        }

        if ($fields['status'] != self::ORDER_STATUS_SUCCESS) {
            $this->writePaymentErrorLog("======================xinpay checkCallbackOrder Payment status is not success", $fields);
            return false;
        }

        # everything checked ok
        return true;
    }

    # -- info --
    public function getBankInfo() {
        $bankInfo = array();
        $bankInfoArr = $this->getSystemInfo("xinpay_bank_info");
        if(!empty($bankInfoArr)) {
            foreach($bankInfoArr as $bankInfoItem) {
                $bankInfo[$bankInfoItem[0]] = $bankInfoItem[1];
            }
            $this->utils->debug_log("==================getting xinpay bank info from extra_info: ", $bankInfo);
        } else {
            $bankInfo = array(
                '1' => array('name' => '工商银行', 'code' => 'ICBC'),
                '2' => array('name' => '招商银行', 'code' => 'CMB'),
                '3' => array('name' => '建设银行', 'code' => 'CCB'),
                '4' => array('name' => '农业银行', 'code' => 'ABC'),
                '5' => array('name' => '交通银行', 'code' => 'COMM'),
                '6' => array('name' => '中国银行', 'code' => 'BOC'),
                '8' => array('name' => '广东发展银行', 'code' => 'GDB'),
                '10' => array('name' => '中信银行', 'code' => 'CNCB'),
                '11' => array('name' => '民生银行', 'code' => 'CMBC'),
                '12' => array('name' => '中国邮政储蓄银行', 'code' => 'PSBC'),
                '13' => array('name' => '兴业银行', 'code' => 'CIB'),
                '14' => array('name' => '华夏银行', 'code' => 'HXB'),
                '15' => array('name' => '平安银行', 'code' => 'PAB'),
                '20' => array('name' => '光大银行', 'code' => 'CEB'),
            );
            $this->utils->debug_log("=======================getting xinpay bank info from code: ", $bankInfo);
        }
        return $bankInfo;
    }



    # -- signatures --
    private function sign($params) {
        $signStr = $this->createSignStr($params);
        $sign = md5($signStr);
        return $sign;
    }

    private function createSignStr($params) {
        ksort($params);
        $signStr = '';
        foreach($params as $key => $value) {
            if(empty($value) || $key == 'key') {
                continue;
            }
            $signStr .= "$key=$value&";
        }
        return $signStr.'key='.$this->getSystemInfo('key');
    }

    private function validateSign($params) {
        ksort($params);
        $signStr = '';
        foreach($params as $key => $value) {
            if( ($key == 'sign') || (empty($value)) ) {
                continue;
            }
            $signStr .= "$key=$value&";
        }
        $signStr .= 'key='.$this->getSystemInfo('key');
        $sign = md5($signStr);
        if($params['sign'] == $sign){
            return true;
        }
        else{
            return false;
        }
    }
}


