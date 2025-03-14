<?php
require_once dirname(__FILE__) . '/abstract_payment_api_xxpay.php';

/**
 * xxpay 取款
 *
 * * XXPAY_WITHDRAWAL_PAYMENT_API, ID: 5897
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://business.xxpay.cash/Payment_Dfpay_add.html
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 * * Extra Info:
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_xxpay_withdrawal extends Abstract_payment_api_xxpay {

    const PAY_RESULT_SUCCESS_CODE = '0';

	public function getPlatformCode() {
		return XXPAY_WITHDRAWAL_PAYMENT_API;
	}

	public function getPrefix() {
		return 'xxpay_withdrawal';
	}

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
        # look up bank code
        $bankInfo = $this->getBankInfo();
        if(!array_key_exists($bank, $bankInfo)) {
            $this->utils->error_log("========================xxpay withdrawal bank whose bankTypeId=[$bank] is not supported by xxpay");
            return array('success' => false, 'message' => 'Bank not supported by xxpay');
        }

        $params = array();
        $params['pay_memberid'] = $this->getSystemInfo('account');
        $params['pay_out_trade_no'] = $transId;
        $params['pay_money'] = $this->convertAmountToCurrency($amount);
        $params['pay_bankname'] = $bankInfo[$bank]['code'];
        $params['pay_subbranch'] = $bankBranch;
        $params['pay_accountname'] = $name;
        $params['pay_cardnumber'] = $accNum;
        $params['pay_province'] = $province;
        $params['pay_city'] = $city;
        $params['pay_notifyurl'] = $this->getNotifyUrl($transId);
        $params['pay_md5sign'] = $this->sign($params);

        $this->CI->utils->debug_log('======================================xxpay getWithdrawParams :', $params);
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

		if(isset($params['success'])) {
			if($params['success'] == false) {
				$result['message'] = $params['message'];
				$this->utils->debug_log($result);
				return $result;
			}
		}

		$this->CI->utils->debug_log('======================================xxpay submitWithdrawRequest params: ', $params);

		$url = $this->getSystemInfo('url');

        list($response, $response_result) = $this->submitPostForm($url, $params, false, $transId, true);

        $decodedResult = $this->decodeResult($response);
        $decodedResult['response_result'] = $response_result;

        $this->CI->utils->debug_log('======================================xxpay submitWithdrawRequest url: ', $url );
        $this->CI->utils->debug_log('======================================xxpay submitWithdrawRequest response', $response);
        $this->CI->utils->debug_log('======================================xxpay submitWithdrawRequest decoded Result', $decodedResult);
		return $decodedResult;
	}

	public function decodeResult($resultString, $queryAPI = false) {
        if(is_array($resultString) && isset($resultString['lock']) && isset($resultString['msg'])) {
            $result = array('success' => false, 'message' => $resultString['msg'], 'lock' => true);
            return $result;
        }
        $result = json_decode($resultString, true);
        $this->utils->debug_log("=========================xxpay json_decode result", $result);

        if(isset($result['code'])) {
            if($result['code'] == self::PAY_RESULT_SUCCESS_CODE) {
                $message = "xxpay withdrawal response successful, code:[".$result['code']."]: ".$result['msg'];
                return array('success' => true, 'message' => $message);
            }
            $message = "xxpay withdrawal response failed. [".$result['code']."]: ".$result['msg'];
            return array('success' => false, 'message' => $message);

        }
        elseif(isset($result['msg'])){
            $message = 'xxpay withdrawal response: '.$result['msg'];
            return array('success' => false, 'message' => $message);
        }
        return array('success' => false, 'message' => "xxpay decoded fail.");
    }

	public function callbackFromServer($transId, $params) {
		$response_result_id = parent::callbackFromServer($transId, $params);
		if(empty($params) || is_null($params)){
			$raw_post_data = file_get_contents('php://input', 'r');
        	$params = json_decode($raw_post_data, true);
		}
        $result = array('success' => false, 'message' => 'Payment failed');

        $this->utils->debug_log('=========================xxpay process withdrawalResult order id', $transId);

        $result = $params;

        $this->utils->debug_log("=========================xxpay checkCallback params", $params);

        $order = $this->CI->wallet_model->getWalletAccountByTransactionCode($transId);

        if (!$this->checkCallbackOrder($order, $params)) {
            return $result;
        }

        if($result['returncode'] == self::PAY_RESULT_SUCCESS) {
            $this->utils->debug_log('=========================xxpay withdrawal payment was successful: trade ID [%s]', $params['orderid']);

            $msg = sprintf('xxpay withdrawal was successful: trade ID [%s]',$params['orders'][0]['mer_order_no']);
            $this->CI->wallet_model->withdrawalAPIReturnSuccess($transId, $msg);

            $result['message'] = self::RETURN_SUCCESS_CODE;
            $result['success'] = true;
       }else {
            $realStateDesc = $params['orderid'];
            $this->errMsg = '['.$realStateDesc.']';
            $msg = sprintf('xxpay withdrawal payment was not successful: '.$this->errMsg);
            $this->writePaymentErrorLog($msg, $params);

            $result['message'] = $msg;
        }

        return $result;
    }

    public function checkCallbackOrder($order, $fields) {
        $requiredFields = array('orderid', 'amount', 'returncode','sign');

        foreach ($requiredFields as $f) {
        	if (!array_key_exists($f, $fields)) {
        		$this->writePaymentErrorLog("======================xxpay withdrawal checkCallbackOrder missing parameter: [$f]", $fields);
        		return false;
        	}
        }

        if ($fields['sign']!=$this->validateSign($fields)) {
        	$this->writePaymentErrorLog('=========================xxpay withdrawal checkCallback signature Error',$fields);
        	return false;
        }

        if ($fields['orderid'] != $order['transactionCode']) {
            $this->writePaymentErrorLog("========================xxpay checkCallbackOrder type2 order IDs do not match, expected [$order->secure_id]", $fields);
            return false;
        }

    	if ($fields['returncode'] != self::PAY_RESULT_SUCCESS) {
            $this->writePaymentErrorLog("======================xxpay checkCallbackOrder Payment status is not success", $fields);
            return false;
        }

        if ($fields['amount'] != $this->convertAmountToCurrency($order['amount'])) {
            $this->writePaymentErrorLog('=========================xxpay withdrawal checkCallbackOrder payment amount is wrong, expected =>'. $order['amount'], $fields);
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
            foreach($bankInfoArr as $system_bank_type_id => $bankInfoItem) {
                if(isset($bankInfoItem['name'])){
                    $bankInfo[$system_bank_type_id]['name'] = $bankInfoItem['name'];
                }
                if(isset($bankInfoItem['code'])){
                    $bankInfo[$system_bank_type_id]['code'] = $bankInfoItem['code'];
                }
            }
            $this->utils->debug_log("==================getting xxpay bank info from extra_info: ", $bankInfo);
        } else  {
            $bankInfo = array(
                '1' => array('name' => '工商银行', 'code' => '中国工商银行'),
                '2' => array('name' => '招商银行', 'code' => '招商银行'),
                '3' => array('name' => '建设银行', 'code' => '中国建设银行'),
                '4' => array('name' => '农业银行', 'code' => '中国农业银行'),
                '5' => array('name' => '交通银行', 'code' => '交通银行'),
                '6' => array('name' => '中国银行', 'code' => '中国银行'),
                // '7' => array('name' => '深圳发展银行', 'code' => 'SDB'),
                '8' => array('name' => '广发银行', 'code' => '广发银行'),
                '10' => array('name' => '中信银行', 'code' => '中信银行'),
                '11' => array('name' => '民生银行', 'code' => '中国民生银行'),
                '12' => array('name' => '中国邮政银行', 'code' => '中国邮政储蓄银行'),
                '13' => array('name' => '兴业银行', 'code' => '兴业银行'),
                '14' => array('name' => '华夏银行', 'code' => '华夏银行'),
                '15' => array('name' => '平安银行', 'code' => '平安银行'),
                '17' => array('name' => '广州银行', 'code' => '广州银行'),
                //'18' => array('name' => '南京银行', 'code' => 'NJCB'),
                '20' => array('name' => '光大银行', 'code' => '中国光大银行'),
                '24' => array('name' => '浦发银行', 'code' => '上海浦东发展银行'),
                '25' => array('name' => '北京银行', 'code' => '北京银行'),
                '25' => array('name' => '上海银行', 'code' => '上海银行'),
                // '26' => array('name' => '苏州银行', 'code' => 'BSZ'),
                '27' => array('name' => '桂林银行', 'code' => '桂林银行'),
                // '28' => array('name' => '广西农村信用社', 'code' => 'GX966888'),
                '29' => array('name' => '郑州银行', 'code' => '郑州银行'),
                // '30' => array('name' => '四川天府銀行', 'code' => 'TFB'),
                '31' => array('name' => '宁波銀行', 'code' => '宁波银行'),
                // '32' => array('name' => '江蘇銀行', 'code' => 'JSBCHINA'),
                '33' => array('name' => '浙江泰隆商业银行', 'code' => '浙商银行'),
            );
            $this->utils->debug_log("=======================getting aipay bank info from code: ", $bankInfo);
        }
        return $bankInfo;
    }


    /**
     * detail: getting the signature
     *
     * @param array $data
     * @return  string
     */
    public function sign($params) {
        $signStr = $this->createSignStr($params);
        $sign = strtoupper(md5($signStr));

        return $sign;

    }

    public function validateSign($params) {
        $signStr = $this->createSignStr($params);
        $sign = strtoupper(md5($signStr));
        if($params['sign'] == $sign)
            return true;
        else
            return false;
    }

    public function createSignStr($params)
    {
        ksort($params);
        $signStr = '';
        foreach ($params as $key => $value) {
           if ($key == 'pay_md5sign' || $key == 'attach' || $key == 'sign') {
                continue;
           }
           $signStr .= "$key=$value&";
        }
        return $signStr . "key=" . $this->getSystemInfo('key');
    }


}

