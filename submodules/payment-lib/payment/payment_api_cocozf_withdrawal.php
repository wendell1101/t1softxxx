<?php
require_once dirname(__FILE__) . '/abstract_payment_api_cocozf.php';

/**
 * cocozf 取款
 *
 * * COCOZF_WITHDRAWAL_PAYMENT_API, ID: 5208
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://pay.cocozf.com/ctp_xa/view/server/aotori/propayTrans.php
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 * * Extra Info:
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_cocozf_withdrawal extends Abstract_payment_api_cocozf {
	const RESULT_STATUS_SUCCESS = "0000";

	const PAYMENT_STATUS_SUCCESS = "0000";
	const PAYMENT_STATUS_PROCESSING = "0001";

	public function getPlatformCode() {
		return COCOZF_WITHDRAWAL_PAYMENT_API;
	}

	public function getPrefix() {
		return 'cocozf_withdrawal';
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
            $this->utils->error_log("========================cocozf withdrawal bank whose bankTypeId=[$bank] is not supported by cocozf");
            return array('success' => false, 'message' => 'Bank not supported by cocozf');
        }


        $params = array();
        $params['merchantId'] = $this->getSystemInfo('account');
        $params['merReqNo'] = $transId;
        $params['amt'] = $this->convertAmountToCurrency($amount);
        $params['pubOrPri'] = "0";
        $params['cardName'] = $name;
        $params['cardId'] = $this->randomNum(18);
        $params['pan'] = $accNum;
        $params['telphone'] = $this->randomNum(12);
        $params['unionNo'] = $bankInfo[$bank]['unionNo'];
        $params['bankCode'] = $bankInfo[$bank]['code'];
        $params['bankName'] = $bankInfo[$bank]['name'];
        $params['bank_branch_name'] = $bankBranch;
        $params['city'] = $city;
        $params['province'] = $province;
        $params['sign'] = $this->sign($params);


        $this->CI->utils->debug_log('======================================cocozf getWithdrawParams :', $params);
        return $params;
    }

	public function getOrderIdFromParameters($params) {
		$this->utils->debug_log('====================================cocozf callbackOrder params', $params);
		if(empty($params) || is_null($params)){
			$raw_post_data = file_get_contents('php://input', 'r');
        	$params = json_decode($raw_post_data, true);
		}

		$transId = null;
		//for fixed return url on browser
		if (isset($params['orders'][0]['mer_order_no'])) {
			$trans_id = $params['orders'][0]['mer_order_no'];

			$this->CI->load->model(array('wallet_model'));
	        $walletAccount = $this->CI->wallet_model->getWalletAccountByTransactionCode($trans_id);

			if(!empty($walletAccount)){
               	$transId = $walletAccount['transactionCode'];
            }else{
            	$this->utils->debug_log('====================================cocozf callbackOrder transId is empty when getOrderIdFromParameters', $params);
            }
		}
		else {
			$this->utils->debug_log('====================================cocozf callbackOrder cannot get any transId when getOrderIdFromParameters', $params);
		}
		return $transId;
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

		$this->CI->utils->debug_log('======================================cocozf submitWithdrawRequest params: ', $params);

		$url = $this->getSystemInfo('url');

        list($response, $response_result) = $this->submitPostForm($url, $params, true, $transId, true);

        $decodedResult = $this->decodeResult($response);
        $decodedResult['response_result'] = $response_result;

        $this->CI->utils->debug_log('======================================cocozf submitWithdrawRequest url: ', $url );
        $this->CI->utils->debug_log('======================================cocozf submitWithdrawRequest response', $response);
        $this->CI->utils->debug_log('======================================cocozf submitWithdrawRequest decoded Result', $decodedResult);
		return $decodedResult;
	}

	 public function decodeResult($resultString, $queryAPI = false) {
		if(is_array($resultString) && isset($resultString['lock']) && isset($resultString['msg'])) {
            $result = array('success' => false, 'message' => $resultString['msg'], 'lock' => true);
            return $result;
        }
        $this->utils->debug_log("=========================cocozf decodeResult resultString", $resultString);
        $resultString_chr = strchr($resultString, "{");
		$response = json_decode($resultString_chr, true);
		$this->utils->debug_log("=========================cocozf json_decode result string", $response);

		$respCode = $response['respCode'];
		$respDesc = $response['respDesc'];

        if($respCode == self::RESULT_STATUS_SUCCESS || $respCode == self::PAYMENT_STATUS_PROCESSING ) {
            if($queryAPI){
            	$payCode = $response['payCode'];
            	$payDesc = $response['payDesc'];
                $result = array('success' => false, 'message' => 'Cocozf check status decoded fail.', 'payment_fail' => false);
                if($payCode == self::PAYMENT_STATUS_SUCCESS){
                    $result['success'] = true;
                    $result['message'] = "Cocozf withdrawal success! [".$payCode."]".$payDesc;
                }
                elseif($payCode == self::PAYMENT_STATUS_PROCESSING){
                    $result['message'] = "Cocozf withdrawal still processing. [".$payCode."]".$payDesc;
                }
                else{
                    $result['payment_fail'] = true;
                    $result['message'] = "Cocozf withdrawal failed. [".$payCode."]".$payDesc;
                }
            }
            else{
                if($respCode == self::PAYMENT_STATUS_PROCESSING || $respCode == self::PAYMENT_STATUS_SUCCESS){
                    $result['success'] = true;
                    $result['message'] = "Cocozf withdrawal response success! [".$respCode."]".$respDesc;
                }
                else{
                    $result['message'] = "Cocozf withdrawal response [".$respCode."]".$respDesc;
                }
            }
        }
        elseif(isset($result['respCode'])){
            $result['message'] = "Cocozf withdrawal response failed. [".$respCode."]: ".$respDesc;
        }

		return $result;
	}

	public function checkWithdrawStatus($transId) {
        $params = array();
		$params['merchantId'] = $this->getSystemInfo("account");
		$params['merReqNo'] = $transId;
		$params['oMerReqNo'] = $transId;
		$params['sign'] = $this->sign($params);

		$url = $this->getSystemInfo('check_withdraw_status_url', 'http://pay.cocozf.com/ctp_xa/view/server/aotori/queryTrans.php');
		$response = $this->submitPostForm($url, $params,true, $transId);
		$decodedResult = $this->decodeResult($response, true);

		$this->CI->utils->debug_log('======================================cocozf checkWithdrawStatus params: ', $params);
		$this->CI->utils->debug_log('======================================cocozf checkWithdrawStatus url: ', $url );
		$this->CI->utils->debug_log('======================================cocozf checkWithdrawStatus result: ', $response );
		$this->CI->utils->debug_log('======================================cocozf checkWithdrawStatus decoded Result', $decodedResult);

		return $decodedResult;
    }

	public function callbackFromServer($transId, $params) {
		$response_result_id = parent::callbackFromServer($transId, $params);
		if(empty($params) || is_null($params)){
			$raw_post_data = file_get_contents('php://input', 'r');
        	$params = json_decode($raw_post_data, true);
		}
        $result = array('success' => false, 'message' => 'Payment failed');

        $this->utils->debug_log('=========================cocozf process withdrawalResult order id', $transId);

        $result = $params;

        $this->utils->debug_log("=========================cocozf checkCallback params", $params);

        $order = $this->CI->wallet_model->getWalletAccountByTransactionCode($transId);

        if (!$this->checkCallbackOrder($order, $params)) {
            return $result;
        }

        if($result['orders'][0]['result'] == self::RETURN_SUCCESS_CODE) {
            $this->utils->debug_log('=========================cocozf withdrawal payment was successful: trade ID [%s]', $params['orders'][0]['mer_order_no']);

            $msg = sprintf('cocozf withdrawal was successful: trade ID [%s]',$params['orders'][0]['mer_order_no']);
            $this->CI->wallet_model->withdrawalAPIReturnSuccess($transId, $msg);

            $result['message'] = self::RETURN_SUCCESS;
            $result['success'] = true;
       }else {
            $realStateDesc = $params['orders'][0]['mer_order_no'];
            $this->errMsg = '['.$realStateDesc.']';
            $msg = sprintf('cocozf withdrawal payment was not successful: '.$this->errMsg);
            $this->writePaymentErrorLog($msg, $params);

            $result['message'] = $msg;
        }

        return $result;
    }

    public function checkCallbackOrder($order, $fields) {
        $requiredFields = array('respCode', 'respDesc', 'merReqNo','serverRspNo');

        foreach ($requiredFields as $f) {
        	if (!array_key_exists($f, $fields)) {
        		$this->writePaymentErrorLog("======================cocozf withdrawal checkCallbackOrder missing parameter: [$f]", $fields);
        		return false;
        	}
        }

        if ($fields['sign']!=$this->validateSign($fields)) {
        	$this->writePaymentErrorLog('=========================cocozf withdrawal checkCallback signature Error',$fields);
        	return false;
        }

        if ($fields['merReqNo'] != $order['transactionCode']) {
            $this->writePaymentErrorLog("========================cocozf checkCallbackOrder type2 order IDs do not match, expected [$order->secure_id]", $fields);
            return false;
        }

    	if ($fields['serverRspNo'] != self::RETURN_SUCCESS_CODE) {
            $this->writePaymentErrorLog("======================cocozf checkCallbackOrder Payment status is not success", $fields);
            return false;
        }

        # everything checked ok
        return true;
    }

 # -- info --
    public function getBankInfo() {
        $bankInfo = array();
        $bankInfoArr = $this->getSystemInfo("cocozf_bank_info");
        if(!empty($bankInfoArr)) {
            foreach($bankInfoArr as $bankInfoItem) {
                $bankInfo[$bankInfoItem[0]] = $bankInfoItem[1];
            }
            $this->utils->debug_log("==================getting cocozf bank info from extra_info: ", $bankInfo);
        } else {
            $bankInfo = array(
				'1' => array('name' => '工商银行', 'code' => 'ICBC', 'unionNo' => '102100099996'),
				'2' => array('name' => '招商银行', 'code' => 'CMB', 'unionNo' => '308584000013'),
				'3' => array('name' => '建设银行', 'code' => 'CCB', 'unionNo' => '105100000017'),
				'4' => array('name' => '农业银行', 'code' => 'ABC', 'unionNo' => '103100000026'),
				'5' => array('name' => '交通银行', 'code' => 'COMM', 'unionNo' => '301290000007'),
				'6' => array('name' => '中国银行', 'code' => 'BOC', 'unionNo' => '104100000004'),
				//'7' => array('name' => '深圳发展银行', 'code' => 'SDB', 'unionNo' => ''),
				//'8' => array('name' => '广东发展银行', 'code' => 'GDB', 'unionNo' => ''),
				'9' => array('name' => '东莞农商银行', 'code' => 'DRCBANK', 'unionNo' => '402602000018'),
				'10' => array('name' => '中信银行', 'code' => 'CITIC', 'unionNo' => '302100011000'),
				'11' => array('name' => '民生银行', 'code' => 'CMBC', 'unionNo' => '305100000013'),
				'12' => array('name' => '中国邮政储蓄银行', 'code' => 'PSBC', 'unionNo' => '403100000004'),
				'13' => array('name' => '兴业银行', 'code' => 'CIB', 'unionNo' => '309391000011'),
				'14' => array('name' => '华夏银行', 'code' => 'HXB', 'unionNo' => '304100040000'),
				'15' => array('name' => '平安银行', 'code' => 'SZPAB', 'unionNo' => '307584007998'),
				//'16' => array('name' => '广西农村信用社', 'code' => 'GX966888', 'unionNo' => ''),
				'17' => array('name' => '广州银行', 'code' => 'GZCB', 'unionNo' => '313581003284'),
				'18' => array('name' => '南京银行', 'code' => 'NJCB', 'unionNo' => '313301008887'),
                '19' => array('name' => '广州农商银行', 'code' => 'GNXS', 'unionNo' => '314581000011'),
				'20' => array('name' => '光大银行', 'code' => 'CEB', 'unionNo' => '303100000006'),
				'88' => array('name' => '北京银行', 'code' => 'BCCB', 'unionNo' => '313100000013'),
            );
            $this->utils->debug_log("=======================getting cocozf bank info from code: ", $bankInfo);
        }
        return $bankInfo;
    }

	public function randomNum($length) {
	    $str="12345678901234567890";
	    $result=substr(str_shuffle($str),0,$length);
	    return $result;
	}

	public function sign($params) {
        ksort($params);//升序排序

        //生成待签名字符串
        $srcData = "";
        foreach ($params as $key => $val) {
            if($val === null || $val === "" ){
                //值为空的跳过，不参与加密
                continue;
            }
            $srcData .= "$key=$val" . "&";
        }
        $srcData = substr($srcData, 0, strlen($srcData) - 1);
		$srcData .= $this->getSystemInfo('key');
        $sign=md5($srcData);
		
		return $sign;
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
		$signStr = rtrim($signStr, '&');
		$sign = md5($signStr.$this->getSystemInfo('key'));
		if($params['sign'] == $sign){
			return true;
		}
		else{
			
			return false;
		}
	}
}

