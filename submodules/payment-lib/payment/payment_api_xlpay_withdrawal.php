<?php
require_once dirname(__FILE__) . '/abstract_payment_api_fwpay.php';

/**
 * xlpay取款
 *
 * * XLPAY_WITHDRAWAL_PAYMENT_API, ID: 6065
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://www.xlzf123.com/api
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 * * Extra Info:
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_xlpay_withdrawal extends Abstract_payment_api_fwpay {
	const CALLBACK_SUCCESS = "success";
    const REQUEST_SUCCESS = "success";

	public function getPlatformCode() {
		return XLPAY_WITHDRAWAL_PAYMENT_API;
	}

	public function getPrefix() {
		return 'xlpay_withdrawal';
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
		$this->CI->load->model(array('wallet_model', 'player_model', 'playerbankdetails'));

        # look up bank code
        $bankInfo = $this->getHuidpayBankInfo();
        if(!array_key_exists($bank, $bankInfo)) {
            $this->utils->error_log("========================xlpay withdrawal bank whose bankTypeId=[$bank] is not supported by aipay");
            return array('success' => false, 'message' => 'Bank not supported by aipay');
            $bank = '无';
        }
        $bankno = $bankInfo[$bank]['code']; //开户行名称
        $bank = $bankInfo[$bank]['name'];   //开户行名称
        
        # but if we cannot look up those info, will leave the fields blank
        $playerBankDetails = $this->CI->playerbankdetails->getBankDetailsByBankAccount($bank, $accNum);
        $this->utils->debug_log("==================xlpay withdrawal get playerBankDetails using [$bank] + [bankno] + [$accNum]", $playerBankDetails);

		$params = array();
        $params['do']              = 'payment';
        $params['client_id']       = $this->getSystemInfo("account");
        $params['money']           = $this->convertAmountToCurrency($amount); //元
        $params['client_order_id'] = $transId;
        $params['callback_url']    = $this->getNotifyUrl($transId);
        $params['time']            = $this->getMillisecond();
        $params['bank_id']         = $bankno;
        $params['name']            = $name;
        $params['account']         = $accNum;
        $params['token']           = $this->sign($params);

		$this->CI->utils->debug_log('=========================xlpay withdrawal paramStr before sign', $params);
		return $params;
	}

	public function submitWithdrawRequest($bank, $accNum, $name, $amount, $transId) {
        $result = array('success' => false, 'message' => 'payment failed');

        if(!$this->isAllowWithdraw()) {
            $result['message'] = lang("Withdraw not allowed with this API");
            $this->utils->debug_log($result);
            return $result;
        }
        $params = $this->getWithdrawParams($bank, $accNum, $name, $amount, $transId);
        $url = $this->getWithdrawUrl();

        list($content, $response_result) = $this->submitPostForm($url, $params, false, $transId, true);

        $decodedResult = $this->decodeResult($content);
        $decodedResult['response_result'] = $response_result;

        $this->CI->utils->debug_log('======================================xlpay submitWithdrawRequest url: ', $url );
        $this->CI->utils->debug_log('======================================xlpay submitWithdrawRequest params: ', $params);
        $this->CI->utils->debug_log('======================================xlpay submitWithdrawRequest response ', $response_result);
        $this->CI->utils->debug_log('======================================xlpay submitWithdrawRequest decoded Result', $decodedResult);

        return $decodedResult;
    }

    public function decodeResult($resultString, $queryAPI = false) {
        if(is_array($resultString) && isset($resultString['lock']) && isset($resultString['msg'])) {
            $result = array('success' => false, 'message' => $resultString['msg'], 'lock' => true);
            return $result;
        }
        $result = json_decode($resultString, true);
        $this->utils->debug_log("=========================xlpay json_decode result", $result);

        if(isset($result['status'])) {
            if($result['status'] == self::REQUEST_SUCCESS) {
                $message = "xlpay withdrawal response successful, status:[".$result['status']."]: ".$result['fn_order_id'];
                return array('success' => true, 'message' => $message);
            }
            $message = "xlpay withdrawal response failed. [".$result['status']."]: ".$result['fn_order_id'];
            return array('success' => false, 'message' => $message);

        }
        elseif(isset($result['info'])){
            $message = 'xlpay withdrawal response: '.$result['info'];
            return array('success' => false, 'message' => $message);
        }
        return array('success' => false, 'message' => "xlpay decoded fail.");
    }

	private function getNotifyUrl($orderId) {
        return parent::getCallbackUrl('/callback/process/' . $this->getPlatformCode() . '/' . $orderId);
    }

    private function getReturnUrl($orderId) {
        return parent::getCallbackUrl('/callback/browser/success/' . $this->getPlatformCode() . '/' . $orderId);
    }

    protected function convertAmountToCurrency($amount) {
        return number_format($amount, 2, '.', '');
    }

    public function callbackFromServer($transId, $params) {
        $response_result_id = parent::callbackFromServer($transId, $params);
        if(empty($params) || is_null($params)){
            $raw_post_data = file_get_contents('php://input', 'r');
            $params = json_decode($raw_post_data, true);
        }

        $result = array('success' => false, 'message' => 'Payment failed');
        $order = $this->CI->wallet_model->getWalletAccountByTransactionCode($transId);
        $this->CI->utils->debug_log('=========================xlpay process withdrawalResult order id', $transId);
        $this->CI->utils->debug_log("=========================xlpay checkCallback params", $params);

        if (!$this->checkCallbackOrder($order, $params)) {
            return $result;
        }

        if($params['status'] == self::CALLBACK_SUCCESS) {
            $msg = sprintf('xlpay withdrawal was successful: trade ID [%s]', $params['client_order_id']);
            $this->CI->wallet_model->withdrawalAPIReturnSuccess($transId, $msg);
            $result['json_result'] = ['status' => self::RETURN_SUCCESS_CODE];
            $result['success'] = true;
        }
        else {
            $msg = sprintf('xlpay withdrawal was not success: [%s]', $params['status']);
            $this->writePaymentErrorLog($msg, $params);
            $result['message'] = $msg;
        }

        return $result;
    }

    public function checkCallbackOrder($order, $fields, &$processed = false)
    {
        $requiredFields = array('fn_order_id', 'client_order_id', 'status', 'money', 'token');

        $this->CI->utils->debug_log("=========================xlpay checkCallback detailData", $fields);

        foreach ($requiredFields as $f) {
           if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("=======================xlpay withdrawal checkCallbackOrder missing parameter: [$f]", $fields);
                return false;
            }
        }

        # is signature authentic?
        if (!$this->verifySignature($fields)) {
               $this->writePaymentErrorLog('=====================xlpay withdrawal checkCallbackOrder Signature Error', $fields);
               return false;
        }

        $processed = true; # processed is set to true once the signature verification pass

        if ($fields['status'] != self::CALLBACK_SUCCESS) {
            $this->writePaymentErrorLog("======================xlpay withdrawal checkCallbackOrder Payment status is not success", $fields);
            return false;
        }

        if ($fields['client_order_id'] != $order['transactionCode']) {
            $this->writePaymentErrorLog("======================xlpay withdrawal checkCallbackOrder order IDs do not match, expected ".$order['transactionCode'], $fields);
            return false;
        }

        if ($fields['money']  != $order['amount']) {
            $this->writePaymentErrorLog("======================xlpay withdrawal checkCallbackOrder payment amount is wrong, expected <= ". $order['amount'], $fields);
            return false;
        }

        return true;
    }

    public function getHuidpayBankInfo() {
        $bankInfo = array();
        $bankInfoArr = $this->getSystemInfo("xlpay_bank_info");
        if(!empty($bankInfoArr)) {
            foreach($bankInfoArr as $system_bank_type_id => $bankInfoItem) {
                $bankInfo[$system_bank_type_id] = array('name' => $bankInfoItem['name'], 'code' => $bankInfoItem['code']);
            }
            $this->utils->debug_log("==================getting aipay bank info from extra_info: ", $bankInfo);
        } else {
            $bankInfo = array(
                '1' => array('name' => '中国工商银行', 'code' => '1'),
                '2' => array('name' => '招商银行', 'code' => '6'),    
                '3' => array('name' => '中国建设银行', 'code' => '2'),
                '4' => array('name' => '中国农业银行', 'code' => '3'),
                '5' => array('name' => '交通银行', 'code' => '5'),
                '6' => array('name' => '中国银行', 'code' => '4'),
                // '7' => array('name' => '深圳发展银行', 'code' => 'SDB'),
                '8' => array('name' => '广发银行', 'code' => '15'),
                '10' => array('name' => '中信银行', 'code' => '7'),
                '11' => array('name' => '中国民生银行', 'code' => '8'),
                '12' => array('name' => '中国邮政储蓄银行', 'code' => '11'),
                '13' => array('name' => '兴业银行', 'code' => '9'),
                '14' => array('name' => '华夏银行', 'code' => '14'),
                '15' => array('name' => '平安银行', 'code' => '13'),
                '17' => array('name' => '广州银行', 'code' => '69'),
                '18' => array('name' => '南京银行', 'code' => '132'),
                '20' => array('name' => '中国光大银行', 'code' => '12'),
                '24' => array('name' => '浦发银行', 'code' => '10'),
            );
            $this->utils->debug_log("=======================getting xlpay bank info from code: ", $bankInfo);
        }
        return $bankInfo;
    }
}
