<?php
require_once dirname(__FILE__) . '/abstract_payment_api_lelipay.php';

/**
 *   LELIPAY_WITHDRAWAL
 *
 * * LELIPAY_WITHDRAWAL_PAYMENT_API, ID: 6257
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL:
 * * Account: ## User ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2023 tot
 */
class Payment_api_lelipay_withdrawal extends Abstract_payment_api_lelipay {

    public function getPlatformCode() {
        return LELIPAY_WITHDRAWAL_PAYMENT_API;
    }

    public function getPrefix() {
        return 'lelipay_withdrawal';
    }

    # Implement abstract function but do nothing
    public function getBankCode($direct_pay_extra_info) {}

    # Implement abstract function but do nothing
    protected function configParams(&$params, $direct_pay_extra_info) {}
    protected function processPaymentUrlForm($params) {}
    public function submitWithdrawRequest($bank, $accNum, $name, $amount, $transId) {
        $result = array('success' => false, 'message' => 'payment failed');

        if(!$this->isAllowWithdraw()) {
            $result['message'] = lang("Withdraw not allowed with this API");
            return $result;
        }

        # look up bank code
		$bankInfo = $this->getBankInfo();
		if(!array_key_exists($bank, $bankInfo)) {
			$this->utils->error_log("========================lelipay withdrawl bank whose bankTypeId=[$bank] is not supported by lelipay withdrawl");
            $result['message'] = 'Bank not supported by lelipay withdrawl';
            return $result;
		}

        $params = $this->getWithdrawParams($bank, $accNum, $name, $amount, $transId);
        $url = $this->getWithdrawUrl();

        list($response, $response_result) = $this->submitPostForm($url, $params, false, $transId, true);

        $decodedResult = $this->decodeResult($response);
        $decodedResult['response_result'] = $response_result;

        $this->CI->utils->debug_log('======================================lelipay submitWithdrawRequest url: ', $url );
        $this->CI->utils->debug_log('======================================lelipay submitWithdrawRequest response', $response);
        $this->CI->utils->debug_log('======================================lelipay submitWithdrawRequest decoded Result', $decodedResult);

        return $decodedResult;
    }

    public function getWithdrawUrl() {
        return $this->getSystemInfo('url');
    }

    public function getWithdrawParams($bank, $accNum, $name, $amount, $transId) {
        $this->CI->load->model(array('wallet_model', 'player_model', 'playerbankdetails'));

        $playerBankDetails = $this->CI->playerbankdetails->getBankDetailsByBankAccount($bank, $accNum);
        $this->utils->debug_log("===============================lelipay Get playerBankDetails using [$bank] + [$accNum]", $playerBankDetails);

		$bankInfo = $this->getBankInfo();

        if(!empty($playerBankDetails)){
            $playerId = $playerBankDetails['playerId'];
            $playerDetails = $this->CI->player_model->getPlayerDetails($playerId);
            $firstname  = (isset($playerDetails[0]) && !empty($playerDetails[0]['firstName'])) ? $playerDetails[0]['firstName'] : '无名';
        }

        $order = $this->CI->wallet_model->getWalletAccountByTransactionCode($transId);

        $params = array();
        $params['txnType']       = '52';
        $params['txnSubType']    = '10';
        $params['secpVer']       = 'icp3-1.1';
        $params['secpMode']      = 'perm';
        $params['macKeyId']      = $this->getSystemInfo("account");
        $params['orderDate']     = date('Ymd');
        $params['orderTime']     = date('Hms');
        $params['merId']         = $this->getSystemInfo("account");
        $params['orderId']       = $transId;
        $params['txnAmt']        = $this->convertAmountToCurrency($amount);
        $params['currencyCode']  = '156';
        $params['accName']       = $firstname;
        $params['accNum']        = $accNum;
        $params['bankNum']       = $bankInfo[$bank]['code'];
        $params['bankName']      = $bankInfo[$bank]['name'];
        $params['notifyUrl']     = $this->getNotifyUrl($transId);
        $params['timeStamp']     = date('YmdHms');
        $params['mac']           = $this->sign($params);

        $this->CI->utils->debug_log('=========================lelipay getWithdrawParams params', $params);
        return $params;
    }

    public function decodeResult($resultString, $queryAPI = false) {
        if(is_array($resultString) && isset($resultString['lock']) && isset($resultString['msg'])) {
            $result = array('success' => false, 'message' => $resultString['msg'], 'lock' => true);
            return $result;
        }
        $result = json_decode($resultString, true);
        $this->utils->debug_log("=========================lelipay json_decode result", $result);

        if (isset($result['respCode'])) {
            if($result['respCode'] == self::REPONSE_CODE_SUCCESS) {
                $message = "lelipay withdrawal response successful, orderId:".$result['orderId'];
                return array('success' => true, 'message' => $message);
            }
            $message = "lelipay withdrawal response failed. ErrorMessage: ".$result['respMsg'];
            return array('success' => false, 'message' => $message);

        }
        elseif($result['respMsg']){
            $message = 'lelipay withdrawal response: '.$result['respMsg'];
            return array('success' => false, 'message' => $message);
        }
        return array('success' => false, 'message' => "lelipay decoded fail.");
    }

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
            $this->utils->debug_log("=========================lelipay bank info from extra_info: ", $bankInfo);
        } else {
			$bankInfo = array(
				'1'   => array('name' => '工商银行', 'code' => '01020000'),
				'2'   => array('name' => '招商银行', 'code' => '03080000'),
				'3'   => array('name' => '建设银行', 'code' => '01050000'),
				'4'   => array('name' => '农业银行', 'code' => '01030000'),
				'5'   => array('name' => '交通银行', 'code' => '03010000'),
				'6'   => array('name' => '中国银行', 'code' => '01040000'),
				'10'  => array('name' => '中信银行', 'code' => '03020000'),
                '12'  => array('name' => '邮储银行', 'code' => '04030000'),
				'13'  => array('name' => '兴业银行', 'code' => '03090000'),
				'14'  => array('name' => '华夏银行', 'code' => '03040000'),
				'15'  => array('name' => '平安银行', 'code' => '03070000'),
				'17'  => array('name' => '广州银行', 'code' => '04135810'),
                '18'  => array('name' => '南京银行', 'code' => '03133201'),
				'20'  => array('name' => '光大银行', 'code' => '03030000'),
				'21'  => array('name' => '广发银行', 'code' => '03060000'),
				'29'  => array('name' => '北京银行', 'code' => '03131000'),
                '30'  => array('name' => '天津银行', 'code' => '04341100'),
                '31'  => array('name' => '上海银行', 'code' => '03130000'),
                '33'  => array('name' => '北京农村商业银行', 'code' => '04020011'),
                '40'  => array('name' => '重庆银行', 'code' => '04416530'),
                '41'  => array('name' => '大连银行', 'code' => '04202220'),
                '44'  => array('name' => '东莞银行', 'code' => '04256020'),
                '48'  => array('name' => '杭州银行', 'code' => '03133301'),
                '51'  => array('name' => '湖州银行', 'code' => '04753360'),
                '53'  => array('name' => '江苏银行', 'code' => '05083000'),
                '54'  => array('name' => '嘉兴银行', 'code' => '04703350'),
                '55'  => array('name' => '吉林银行', 'code' => '04512420'),
                '58'  => array('name' => '锦州银行', 'code' => '04392270'),
                '59'  => array('name' => '九江银行', 'code' => '04544240'),
                '61'  => array('name' => '廊坊银行', 'code' => '04721460'),
                '62'  => array('name' => '兰州银行', 'code' => '04478210'),
                '64'  => array('name' => '柳州银行', 'code' => '04956140'),
                '67'  => array('name' => '宁波银行', 'code' => '04083320'),
                '68'  => array('name' => '宁夏银行', 'code' => '04369800'),
                '71'  => array('name' => '泉州银行', 'code' => '04643970'),
                '74'  => array('name' => '苏州银行', 'code' => '04213050'),
                '76'  => array('name' => '台州银行', 'code' => '04593450'),
                '77'  => array('name' => '潍坊银行', 'code' => '04624580'),
                '83'  => array('name' => '包商银行', 'code' => '04791920'),
                '89'  => array('name' => '浙商银行', 'code' => '03160000'),
                '93'  => array('name' => '重庆三峡银行', 'code' => '05426900'),
                '103' => array('name' => '福建海峡银行', 'code' => '04053910'),
                '105' => array('name' => '广东南粤银行', 'code' => '64895910'),
                '107' => array('name' => '桂林银行', 'code' => '04916170'),
                '110' => array('name' => '汉口银行', 'code' => '04145210'),
                '111' => array('name' => '哈尔滨银行', 'code' => '04422610'),
                '116' => array('name' => '徽商银行', 'code' => '04403600'),
                '143' => array('name' => '厦门银行', 'code' => '04023930'),
                '149' => array('name' => '浙江泰隆商业银行', 'code' => '04733450'),
                '159' => array('name' => '重庆农村商业银行', 'code' => '15136900')
			);
			$this->utils->debug_log("=======================getting lelipay bank info from code: ", $bankInfo);
		}
		return $bankInfo;
	}

    public function callbackFromServer($transId, $params) {
        $response_result_id = parent::callbackFromServer($transId, $params);

        $raw_post_data = file_get_contents('php://input', 'r');
        $this->CI->utils->debug_log("=====================lelipay raw_post_data", $raw_post_data);
        parse_str($raw_post_data ,$params);
        $this->CI->utils->debug_log("========================lelipay parse_str params", $params);

        $result = array('success' => false, 'message' => 'Payment failed');

        $this->CI->utils->debug_log('=========================lelipay callbackFromServer transId', $transId);
        $this->CI->utils->debug_log("=========================lelipay callbackFromServer params", $params);

        $order = $this->CI->wallet_model->getWalletAccountByTransactionCode($transId);

        if (!$this->checkCallbackOrder($order, $params)) {
            return $result;
        }

        if ($params['txnStatus'] == self::CALLBACK_SUCCESS) {
            $msg = sprintf('lelipay withdrawal success: trade ID [%s]', $params['orderId']);
            $this->CI->wallet_model->withdrawalAPIReturnSuccess($transId, $msg);
            $result['message'] = self::RETURN_SUCCESS_CODE;
            $result['success'] = true;
        }else {
            $msg = sprintf("lelipay withdrawal payment unsuccessful or pending: status=%s", $params['txnStatus']);
            $this->writePaymentErrorLog($msg, $params);
            $result['message'] = $msg;
        }

        return $result;
    }

    private function checkCallbackOrder($order, $fields) {
        $requiredFields = array(
            'txnAmt', 'txnStatus', 'mac', 'orderId'
        );

        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("======================lelipay withdrawal checkCallbackOrder missing parameter: [$f]", $fields);
                return false;
            }
        }

        # is signature authentic?
        if (!$this->validateSign($fields)) {
            $this->writePaymentErrorLog('=====================lelipay withdrawal checkCallbackOrder Signature Error', $fields['mac']);
            return false;
        }

        if ($fields['txnAmt'] != $this->convertAmountToCurrency($order['amount'])) {
            $this->writePaymentErrorLog('=========================lelipay withdrawal checkCallbackOrder payment amount is wrong, expected =>'. $order['amount'], $fields);
            return false;
        }

        if ($fields['orderId'] != $order['transactionCode']) {
            $this->writePaymentErrorLog('=========================lelipay withdrawal checkCallbackOrder order IDs do not match, expected =>'. $order['transactionCode'], $fields);
            return false;
        }

        # everything checked ok
        return true;
    }

    public function callbackFromBrowser($transId, $params) {
        return array('success' => false, 'next_url' => null, 'message' => 'Error: not implemented');
    }

    # -- Private functions --
    # After payment is complete, the gateway will invoke this URL asynchronously
    public function getNotifyUrl($orderId) {
        return parent::getCallbackUrl('/callback/process/' . $this->getPlatformCode() . '/' . $orderId);
    }
}