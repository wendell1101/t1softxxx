<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * BEEEPAY 必支付-出款
 * https://portal.beeepay.com/merchant/login
 *
 * * BEEEPAY_WITHDRAWAL_PAYMENT_API, ID: 357
 *
 * Required Fields:
 * 
 * * URL
 * * Account
 * * Extra Info
 *
 * Field Values:
 * 
 * * https://transfer.beeepay.com/gateway/transfer
 * * Extra Info
 * > {
 * >	"beeepay_aes_key" : "## AES KEY ##"
 * > }
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_beeepay_withdrawal extends Abstract_payment_api {
	public function getPlatformCode() {
		return BEEEPAY_WITHDRAWAL_PAYMENT_API;
	}

	public function getPrefix() {
		return 'beeepay_withdrawal';
	}

	/**
	 * detail: override common API functions
	 *
	 * @return void
	 */
	public function generatePaymentUrlForm($orderId, $playerId, $amount, $orderDateTime, $playerPromoId = null, $enabledSecondUrl = true, $bankId = null) {
		return $this->returnUnimplemented();
	}

	# APIs with withdraw function need to implement these methods
	## This function returns the URL to submit withdraw request to
	public function getWithdrawUrl() {
		return $this->getSystemInfo('url');
	}

	## This function returns the params to be submitted to the withdraw URL
	## Note that $bank param is the bank_type ID in database, we compare it with the supported bank_codes by this API
	private $errMsg = 'Payment failed'; # This variable is used to store error message that's available upon submit
	private $iv = '0000000000000000'; //固定向量，请勿改动

	public function getWithdrawParams($bank, $accNum, $name, $amount, $transId) {
		$params = array();
		$this->CI->load->model(array('wallet_model', 'player_model', 'playerbankdetails'));

        //基本参数
        $params["partner_id"]     = $this->getSystemInfo("account");
        $params["version"]        = "V4.0.1";
        $params["service_name"]   = "PTY_TRANSFER_PAY_CARD"; //固定值 请勿随意乱改
        $params["input_charset"]  = "UTF-8";
        $params["sign_type"]      = "MD5";

        //业务参数
        /////////////// AES加密部分参数, 缺一不可 ↓↓↓ ///////////
        $biz_content["tran_amount"]         = $amount;
        $biz_content["bank_account_name"]   = $name;
        $biz_content["bank_account_cardno"] = $accNum;

		# look up bank code
		$bankInfo = $this->getBeeepayBankInfo();
		if(!array_key_exists($bank, $bankInfo)) {
			$this->utils->error_log("========================beeepay withdraw bank whose bankTypeId=[$bank] is not supported by beeepay");
			return array('success' => false, 'message' => 'Bank not supported by beeepay');
		}

		$biz_content["bank_code"] = $bankInfo[$bank]['code']; # bank SN mapping

		# look up bank detail from playerbankdetails table, using bank_type ID and accountNumber
		# but if we cannot look up those info, will leave the fields blank
		$playerBankDetails = $this->CI->playerbankdetails->getBankDetailsByBankAccount($bank, $accNum);
		$this->utils->debug_log("=========================beeepay get playerBankDetails using [$bank] + [$accNum]", $playerBankDetails);

		if(!empty($playerBankDetails)){
			$biz_content['bank_province'] = $playerBankDetails['province'];
			$biz_content['bank_city'] = $playerBankDetails['city'];
			$biz_content["bank_branch"] = $playerBankDetails['branch'];
		} 
		else {
			$biz_content['bank_province'] = '无';
			$biz_content['bank_city'] = '无';
			$biz_content["bank_branch"] = '无';
		}

		$biz_content['bank_province'] = empty($biz_content['bank_province']) ? "无" : $biz_content['bank_province'];
		$biz_content['bank_city'] = empty($biz_content['bank_city']) ? "无" : $biz_content['bank_city'];
		$biz_content['bank_branch'] = empty($biz_content['bank_branch']) ? "无" : $biz_content['bank_branch'];
        /////////////// AES加密部分参数, 缺一不可 ↑↑↑ ///////////

        $params['biz_content']    = $this->encrypt(json_encode($biz_content));	//AES encrypt
        $params["out_trade_no"]   = $transId;
        $params["out_trade_time"] = date("Y-m-d H:i:s");
        $params["notify_url"]     = $this->getNotifyUrl($transId); # Invokes callBackFromServer
        $params["extend_param"]   = "withdrawal";


        $params["sign"] = $this->sign($params);

		$this->utils->debug_log("=========================beeepay submit withdrawal order Params: ", $params);
		return $params;
	}

	public function decodeResult($resultString) {
		$result = json_decode($resultString, true);
		$this->utils->debug_log("=========================beeepay decoded result string", $result);

		if($result['respCode'] == 'RESPONSE_SUCCESS') {
			$message = $result['respMessage']. ', order id: '.$result['respResult']['order_sn'];
			return array('success' => true, 'message' => $message);
		} 
		else if($result['respMessage']) {
			$this->errMsg = '['.$result['respCode'].']: '.$result['respMessage'];
		}
		else {
			$this->errMsg = 'beeepay payment failed for unknown reason';
		} 

		return array('success' => false, 'message' => $this->errMsg);
	}	

	# $source can be 'server' or 'browser'
	private function callbackFrom($source, $orderId, $params, $response_result_id) {
		$this->utils->debug_log('callbackFrom' . ucfirst($source) . ': [' . $orderId .'], params:', $params);

		$this->utils->debug_log('=========================beeepay process withdrawalResult order id', $orderId);

		$this->utils->debug_log("=========================beeepay checkCallback params", $params);
		
		$result = array('success' => false, 'message' => 'Payment failed');

		$walletAccount=$this->CI->wallet_model->getWalletAccountByTransactionCode($orderId);

		if (!$this->verifySign($params)) {
			$this->writePaymentErrorLog('=========================beeepay withdrawal checkCallback signature Error', $params);
			$result = ['success' => false, 'message' => "beeepay withdrawal checkCallback signature Error"];
			return $result;
		}

		$status_arr = array("-1", "0", "1", "2", "3", "4", "5", "-9");

	    for($i = 0; $i< count($status_arr); $i++){
			if (array_key_exists($params['transfer_status'], $status_arr)) {
				break;
			}
			$this->writePaymentErrorLog('=========================beeepay withdrawal checkCallback transfer status not right', $params);
			$result = ['success' => false, 'message' => "beeepay withdrawal checkCallback transfer status not right"];
			return $result;			
	    }

		$walletAccount=$this->CI->wallet_model->getWalletAccountByTransactionCode($params['out_trade_no']);


		if ($params['transfer_status'] == "1") {
			$msg = sprintf('beeepay payment was successful: trade ID [%s]', $params['order_sn']);

			$this->CI->wallet_model->withdrawalAPIReturnSuccess($orderId, $msg);
			$result['message'] = 'SUCCESS';
			$result['success'] = true;
			//$result['json_result'] = array("status" => "success");			
		} else {
			$msg = sprintf('=========================beeepay withdrawal payment was not successful: transfer status [%s]', $params['transfer_status']);

			switch ($params['transfer_status']) {
				case '-1':	//代付失败				
					$result['message'] = 'beeepay withdrawal payment was not successful, transfer status: 代付失败'.", status is ".$params['transfer_status'];
					$result['success'] = false;

					$this->CI->wallet_model->withdrawalAPIReturnFailure($orderId, $msg);			
					break;
				
				case '0':	//已提交
					$result['message'] = 'beeepay withdrawal payment was not successful, transfer status: 已提交'.", status is ".$params['transfer_status'];	
					$result['success'] = false;
					break;

				case '2':	//已初审
					$result['message'] = 'beeepay withdrawal payment was not successful, transfer status: 已初审'.", status is ".$params['transfer_status'];	
					$result['success'] = false;
					break;
				
				case '3':	//已复审
					$result['message'] = 'beeepay withdrawal payment was not successful, transfer status: 已复审'.", status is ".$params['transfer_status'];	
					$result['success'] = false;
					break;

				case '4':	//已提交至银行
					$result['message'] = 'beeepay withdrawal payment was not successful, transfer status: 已提交至银行'.", status is ".$params['transfer_status'];	
					$result['success'] = false;
					break;

				case '5':	//等待退款
					$result['message'] = 'beeepay withdrawal payment was not successful, transfer status: 等待退款'.", status is ".$params['transfer_status'];	
					$result['success'] = false;
					break;																		

				case '-9':	//代付异常				
					$result['message'] = 'beeepay withdrawal payment was not successful, transfer status: 代付异常'.", status is ".$params['transfer_status'];
					$result['success'] = false;

					$this->CI->wallet_model->withdrawalAPIReturnFailure($orderId, $msg);			
					break;

				default:
					{
						$result['message'] = "beeepay withdrawal payment Skip and keep waiting";				
						$result['success'] = false;				
					}
			}
			
			$this->writePaymentErrorLog($msg, $params);
		}

		return $result;
	}

	public function callbackFromServer($orderId, $params) {
		$response_result_id = parent::callbackFromServer($orderId, $params);
		return $this->callbackFrom('server', $orderId, $params, $response_result_id);
	}

	public function callbackFromBrowser($transId, $params) {
		return array('success' => false, 'next_url' => null, 'message' => 'Error: not implemented');
	}

	# -- Private functions --
	/**
	 * detail: After payment is complete, the gateway will invoke this URL asynchronously
	 *
	 * @param int $orderId
	 * @return void
	 */
	private function getNotifyUrl($orderId) {
		return parent::getCallbackUrl('/callback/process/' . $this->getPlatformCode() . '/' . $orderId);
	}

	/**
	 * detail: After payment is complete, the gateway will send redirect back to this URL
	 *
	 * @param int $orderId
	 * @return void
	 */
	private function getReturnUrl($orderId) {
		return parent::getCallbackUrl('/callback/browser/success/' . $this->getPlatformCode() . '/' . $orderId);
	}

	protected function convertAmountToCurrency($amount) {
		return number_format($amount, 2, '.', '');
	}

    /**
     * 签名参数组装排序字符串
     * @param $params
     * @return string
     */
	protected function getSignContent($params) {
		ksort($params);
		$params["sign"] = null; //移除sign，不参与加密

		$stringToBeSigned = "";
		$i = 0;
		foreach ($params as $k => $v) {
			if (false === $this->checkEmpty($v) && "@" != substr($v, 0, 1)) {

				// 转换成目标字符集
				$v = $this->characet($v, $params["input_charset"]);

				if ($i == 0) {
					$stringToBeSigned .= "$k" . "=" . "$v";
				} else {
					$stringToBeSigned .= "&" . "$k" . "=" . "$v";
				}
				$i++;
			}
		}

		unset ($k, $v);
		return $stringToBeSigned;
	}

	/**
	* 校验$value是否非空
	*  if not set ,return true;
	*    if is null , return true;
	**/
	protected function checkEmpty($value) {
		if(!isset($value))
			return true;
		if($value === null)
			return true;
		if(trim($value) === "")
			return true;

		return false;
	}

	/**
	 * 转换字符集编码
	 * @param $data
	 * @param $targetCharset
	 * @return string
	 */
	function characet($data, $targetCharset) {
		if(!empty($data)) {
			$fileType = $targetCharset;
			if(strcasecmp($fileType, $targetCharset) != 0) {
			$data = mb_convert_encoding($data, $targetCharset, $fileType);
			//$data = iconv($fileType, $targetCharset.'//IGNORE', $data);
			}
		}
		return $data;
	}	
	/**
	 * MD5 加签
	 * @param $params
	 * @param $secretKey
	 */
	protected function sign($params){
		$signStr = $this->getSignContent($params) ."&key=". $this->getSystemInfo('key');
		$sign = strtoupper(md5($signStr));


		return $sign;
	}

	/**
	 * MD5 验签
	 * @param $params
	 */
	protected function verifySign($params){
		if($this->sign($params) == $params["sign"]){
			return true;
		} else {
			return false;
		}
	}

	/******************************************* AES加解密专用 ***************************************************/

	/**
     * 加密方法
     * @param string $str
     * @return string
     */
	protected function encrypt($data){
		$screct_key = $this->getSystemInfo("beeepay_aes_key");
		$encrypted = openssl_encrypt($data, 'aes-128-cbc', base64_decode($screct_key), OPENSSL_RAW_DATA, $this->iv);
		return base64_encode($encrypted);
	}

	/**
     * 解密方法
     * @param string $str
     * @return string
     */
	protected function decrypt($data){
		$screct_key = $this->getSystemInfo("beeepay_aes_key");
		$encrypted = base64_decode($data);
		return openssl_decrypt($encrypted, 'aes-128-cbc', base64_decode($screct_key), OPENSSL_RAW_DATA, $this->iv);
	}

	public function getBeeepayBankInfo() {
		$bankInfo = array();
		$bankInfoArr = $this->getSystemInfo("beeepay_bank_info");
		if(!empty($bankInfoArr)) {
			foreach($bankInfoArr as $bankInfoItem) {
				$bankInfo[$bankInfoItem[0]] = array('name' => $bankInfoItem[1], 'code' => $bankInfoItem[2]);
			}
			$this->utils->debug_log("==================getting beeepay bank info from extra_info: ", $bankInfo);
		} else {
			$bankInfo = array(
				'1' => array('name' => '工商银行', 'code' => 'BANK_ICBC'),
				'2' => array('name' => '招商银行', 'code' => 'BANK_CMB'),	
				'3' => array('name' => '建设银行', 'code' => 'BANK_CCB'),
				'4' => array('name' => '农业银行', 'code' => 'BANK_ABC '),
				'5' => array('name' => '交通银行', 'code' => 'BANK_BOCOM '),
				'6' => array('name' => '中国银行', 'code' => 'BANK_BOC'),
				'8' => array('name' => '广东发展银行', 'code' => 'BANK_GDB'),
				'10' => array('name' => '中信银行', 'code' => 'BANK_CITIC'),
				'11' => array('name' => '民生银行', 'code' => 'BANK_CMBC'),
				'12' => array('name' => '中国邮政储蓄', 'code' => 'BANK_PSBC'),
				'13' => array('name' => '兴业银行', 'code' => 'BANK_CIB'),
				'14' => array('name' => '华夏银行', 'code' => 'BANK_HXBC'),
				'15' => array('name' => '平安银行', 'code' => 'BANK_PAB'),
				'20' => array('name' => '光大银行', 'code' => 'BANK_CEB'),
			);
			$this->utils->debug_log("=======================getting beeepay bank info from code: ", $bankInfo);
		}
		return $bankInfo;
	}

	public function directPay($order = null) {
		return array('success' => false); # direct pay not supported by this API
	}		
}
