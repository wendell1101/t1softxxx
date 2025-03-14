<?php
require_once dirname(__FILE__) . '/abstract_payment_api_p2p.php';

/**
 * p2p
 *
 * * P2P_WITHDRAWAL_PAYMENT_API, ID: 6111
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://www.tdaypay.com/gateway/base/biz
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 * * Extra Info:
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_p2p_withdrawal extends Abstract_payment_api_p2p {

	public function getPlatformCode() {
		return P2P_WITHDRAWAL_PAYMENT_API;
	}

	public function getPrefix() {
		return 'p2p_withdrawal';
	}

	# Implement abstract function but do nothing
	protected function configParams(&$params, $direct_pay_extra_info) {}

	/**
	 * detail: override common API functionsh
	 *
	 * @return void
	 */
	public function generatePaymentUrlForm($orderId, $playerId, $amount, $orderDateTime, $playerPromoId = null, $enabledSecondUrl = true, $bankId = null) {
		return $this->returnUnimplemented();
	}

	public function processPaymentUrlForm($params) {
		return $this->returnUnimplemented();
	}

	# APIs with withdraw function need to implement these methods
	## This function returns the URL to submit withdraw request to
	public function getWithdrawUrl() {
		return $this->getSystemInfo('url');
	}

    public function getWithdrawParams($bank, $accNum, $name, $amount, $transId) {
        $bankInfo = $this->getBankInfo();

        $this->CI->load->model(array('wallet_model', 'player_model', 'playerbankdetails'));
        $playerBankDetails = $this->CI->playerbankdetails->getBankDetailsByBankAccount($bank, $accNum);

        $this->utils->debug_log("==================p2p withdraw get playerBankDetails using [$bank] + [$accNum]", $playerBankDetails);

        if(!empty($playerBankDetails)){
            $province = empty($playerBankDetails['province']) ? "无" : $playerBankDetails['province'];
        } else {
            $province = '无';
        }

        $params = [];
        $bank_data['corderid']      = $transId;
        $bank_data['money']         = $this->convertAmountToCurrency($amount);
        $bank_data['bankname']      = $bankInfo[$bank]['name'];
        $bank_data['bankusername']  = $name;
        $bank_data['bankcode']      = $accNum;
        $bank_data['bankaddress']   = $province;

        $params['merchant_id'] = $this->getSystemInfo("account");
        $params['notifyurl']   = $this->getNotifyUrl($transId);
        $params['userip']      = $this->getClientIp();
        $params['data']        = urlencode('['.json_encode($bank_data).']');
        $params['sign']        = $this->sign($params);

        $this->CI->utils->debug_log('========================p2p getWithdrawParams params: ', $params);

        return $params;
    }

	public function submitWithdrawRequest($bank, $accNum, $name, $amount, $transId) {
        $result = array('success' => false, 'message' => 'payment failed');

        if(!$this->isAllowWithdraw()) {
            $result['message'] = lang("Withdraw not allowed with this API");
            return $result;
        }
        if(!array_key_exists($bank, $this->getBankInfo())) {
            $this->utils->error_log("========================p2p submitWithdrawRequest bank whose bankTypeId=[$bank] is not supported by p2p");
            return array('success' => false, 'message' => 'Bank not supported by bvvpay');
        }

        $params = $this->getWithdrawParams($bank, $accNum, $name, $amount, $transId);
        $url = $this->getWithdrawUrl();
        list($response, $response_result) = $this->submitPostForm($url, $params, false, $transId, true);

        $decodedResult = $this->decodeResult($response);
        $decodedResult['response_result'] = $response_result;

        $this->CI->utils->debug_log('======================================p2p submitWithdrawRequest url: ', $url );
        $this->CI->utils->debug_log('======================================p2p submitWithdrawRequest params: ', $params);
        $this->CI->utils->debug_log('======================================p2p submitWithdrawRequest response ', $response);
        $this->CI->utils->debug_log('======================================p2p submitWithdrawRequest decoded Result', $decodedResult);

        return $decodedResult;
    }

    public function decodeResult($resultString, $queryAPI = false) {
        if(is_array($resultString) && isset($resultString['lock']) && isset($resultString['msg'])) {
            $result = array('success' => false, 'message' => $resultString['msg'], 'lock' => true);
            return $result;
        }
        $result = json_decode($resultString, true);
        $this->utils->debug_log("=========================p2p json_decode result", $result);
        if(isset($result['status']) && $result['status'] == self::RESULT_CODE_SUCCESS) {
           $message = "p2p withdrawal response successful";
            return array('success' => true, 'message' => $message);
        }else if(isset($result['message']) && !empty($result['message'])){
            return array('success' => false, 'message' => $result['message']);
        }
        else{
            $message = 'p2p withdrawal response failed.';
            return array('success' => false, 'message' => $message);
        }

        return array('success' => false, 'message' => "p2p decoded fail.");
    }

	public function getNotifyUrl($orderId) {
        return parent::getCallbackUrl('/callback/process/' . $this->getPlatformCode() . '/' . $orderId);
    }

    public function getReturnUrl($orderId) {
        return parent::getCallbackUrl('/callback/browser/success/' . $this->getPlatformCode() . '/' . $orderId);
    }

    public function callbackFromServer($transId, $params) {
        $response_result_id = parent::callbackFromServer($transId, $params);
        if(empty($params) || is_null($params)){
            $raw_post_data = file_get_contents('php://input', 'r');
            $params = json_decode($raw_post_data, true);
        }

        $result = array('success' => false, 'message' => 'Payment failed');
        $order = $this->CI->wallet_model->getWalletAccountByTransactionCode($transId);
        $this->CI->utils->debug_log('=========================p2p process withdrawalResult order id', $transId);
        $this->CI->utils->debug_log("=========================p2p checkCallback params", $params);

        if (!$this->checkCallbackOrder($order, $params)) {
            return $result;
        }

        if($params['status'] == self::CALLBACK_SUCCESS) {
            $msg = sprintf('p2p withdrawal was successful: trade ID [%s]', $params['corderid']);
            $this->CI->wallet_model->withdrawalAPIReturnSuccess($transId, $msg);
        }

        $result['message'] = self::RETURN_SUCCESS_CODE;
        $result['success'] = true;

        return $result;
    }

    private function checkCallbackOrder($order, $fields) {
        # does all required fields exist in the header?
        $requiredFields = array('merchant_id', 'money', 'corderid','status');

        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("======================p2p withdrawal checkCallbackOrder missing parameter: [$f]", $fields);
                return false;
            }
        }

        if (!$this->validateSign($fields)) {
            $this->writePaymentErrorLog('=======================p2p checkCallbackOrder verify signature Error', $fields);
            return false;
        }

        if ($fields['money'] != $this->convertAmountToCurrency($order['amount'])) {
            $this->writePaymentErrorLog('=========================p2p withdrawal checkCallbackOrder payment amount is wrong, expected =>'. $order['amount'], $fields);
            return false;
        }

        if ($fields['corderid'] != $order['transactionCode']) {
            $this->writePaymentErrorLog('=========================p2p withdrawal checkCallbackOrder order IDs do not match, expected =>'. $order['transactionCode'], $fields);
            return false;
        }
        # everything checked ok
        return true;
    }

      # -- bankinfo --
    public function getBankInfo() {
        $bankInfo = array();
        $bankInfoArr = $this->getSystemInfo("withdrawal_bank_info");
        if(!empty($bankInfoArr)) {
            foreach($bankInfoArr as $bankInfoItem) {
                $bankInfo[$bankInfoItem[0]] = $bankInfoItem[1];
            }
            $this->utils->debug_log("==================getting p2p bank info from extra_info: ", $bankInfo);
        } else  {
            $bankInfo = array(
                '1' => array('name' => '中国工商银行', 'code' => 'ICBC'),
                '2' => array('name' => '招商银行', 'code' => 'CMB'),
                '3' => array('name' => '中国建设银行', 'code' => 'CCB'),
                '4' => array('name' => '中国农业银行', 'code' => 'ABC'),
                '5' => array('name' => '交通银行', 'code' => 'COMM'),
                '6' => array('name' => '中国银行', 'code' => 'BOC'),
                '8' => array('name' => '广发银行', 'code' => 'GDB'),
                '10' => array('name' => '中信银行', 'code' => 'CITIC'),
                '11' => array('name' => '中国民生银行', 'code' => 'CMBC'),
                '12' => array('name' => '中国邮政储蓄银行', 'code' => 'PSBC'),
                '13' => array('name' => '兴业银行', 'code' => 'CIB'),
                '14' => array('name' => '华夏银行', 'code' => 'HXB'),
                '15' => array('name' => '平安银行', 'code' => 'SZPAB'),
                '18' => array('name' => '南京银行', 'code' => 'NJCB'),
                '20' => array('name' => '中国光大银行', 'code' => 'CEB'),
                '32' => array('name' => '上海浦东发展银行', 'code' => 'SPDB'),
            );
            $this->utils->debug_log("=======================getting p2p bank info from code: ", $bankInfo);
        }
        return $bankInfo;
    }

    public function sign($params) {
        $signStr = $this->createSignStr($params);
        $sign = md5($signStr);

        return $sign;
    }

    public function createSignStr($params) {
        $signStrArray = ['merchant_id','notifyurl','userip','data'];
        $signStr = '';
        foreach ($signStrArray as $value) {
            if( $value == 'data'){
                $params[$value] = urldecode($params[$value]);
            }
            $signStr .= $params[$value];
        }
        $signStr .= $this->getSystemInfo('key');
        return $signStr;
    }

    public function validateSign($params) {
        $signStrArray = ['merchant_id','corderid','money'];
        $signStr = '';
        foreach ($signStrArray as $value) {
            $signStr .= $params[$value];
        }
        $signStr .= $this->getSystemInfo('key');
        $sign = strtoupper(md5($signStr));
        if($params['sign'] == $sign){
            return true;
        }
        else{
            return false;
        }
    }
}
