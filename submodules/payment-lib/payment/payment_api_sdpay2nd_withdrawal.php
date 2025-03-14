<?php
require_once dirname(__FILE__) . '/abstract_payment_api_sdpay2nd.php';
require_once dirname(__FILE__) . '/../ProxySoapClient.php';

/**
 * SDPay_2ND 速达支付 2ND
 * http://www.sdsystem.hk
 *
 * Required Fields:
 *
 * * URL
 * * Account
 * * Extra Info
 *
 *
 * Field Values:
 *
 * * URL: https://deposit2.sdapayapi.com/9001/ApplyForABank.asmx
 * * Account: The 'ID' value (e.g. twinbet), not the merchant code (e.g. RH000001).
 * * Extra Info
 * > {
 * >     "sdpay_key1": "## RSA key 1 ##",
 * >     "sdpay_key2": "## RSA key 2 ##"
 * > }
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_sdpay2nd_withdrawal extends Abstract_payment_api_sdpay2nd {
	public function getPlatformCode() {
		return SDPAY2ND_WITHDRAWAL_PAYMENT_API;
	}

	public function getPrefix() {
		return 'sdpay2nd_withdrawal';
	}

	# Implement abstract function but do nothing
	protected function configParams(&$params, $direct_pay_extra_info) {}
	protected function processPaymentUrlForm($params) {}

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

	public function getOrderIdFromParameters($flds) {

		$arr = $this->recheckParameters($flds);
		$this->CI->utils->debug_log('====================sdpay2nd getOrderIdFromParameters Error', $flds,$arr);

		$secure_id = $arr["SerialNumber"];
        $this->CI->load->model(array('wallet_model'));
        $walletAccount = $this->CI->wallet_model->getWalletAccountByTransactionCode($secure_id);

        $orderId = $walletAccount['transactionCode'];

        return $orderId;
	}

	public function recheckParameters($flds) {

        $encryptedData = $flds["HiddenField1"];
		$decrypt = new SDPay_Decrypt($this->getSystemInfo('sdpay_key1'), $this->getSystemInfo('sdpay_key2'));
		$xml = $decrypt->decryptData($encryptedData);
        // $secure_id = $arr["SerialNumber"];
        if($xml === false){
			$flds = $_REQUEST;
			$encryptedData = $flds["HiddenField1"];
			$decrypt = new SDPay_Decrypt($this->getSystemInfo('sdpay_key1'), $this->getSystemInfo('sdpay_key2'));
			$xml = $decrypt->decryptData($encryptedData);
			$arr = (array)simplexml_load_string($xml);
			$this->utils->debug_log("=========================sdpay2nd recheckParameters recheck ", $xml, $arr, $decrypt, $flds);
			return $arr;
        }else{
			$arr = (array)simplexml_load_string($xml);
			return $arr;
			$this->utils->debug_log("=========================sdpay2nd recheckParameters ", $xml, $arr, $decrypt);
        }
	}

	public function getWithdrawParams($bank, $accNum, $name, $amount, $transId) {
		$params = array();
		$this->CI->load->model(array('wallet_model', 'player_model', 'playerbankdetails'));

		# Get player contact number
		$order = $this->CI->wallet_model->getWalletAccountByTransactionCode($transId);

		$playerId = $order['playerId'];
		$player = $this->CI->player->getPlayerById($playerId);

		$username = $player['username'];
		$params['uid'] = $username;
		$params['time'] = time();
		$params['amount'] = $this->convertAmountToCurrency($amount);
		$params['order_id'] = $transId;

		# look up bank code
		$bankInfo = $this->getSDPayBankInfo();
		if(!array_key_exists($bank, $bankInfo)) {
			$this->utils->error_log("========================sdpay2nd withdraw bank whose bankTypeId=[$bank] is not supported by sdpay");
			return array('success' => false, 'message' => 'Bank not supported by sdpay');
		}

		$params['to_bank_flag'] = $bankInfo[$bank]['code']; # bank SN mapping
		$params['to_cardnumber'] = $accNum;
		$params['to_username'] = $name;

		# look up bank detail from playerbankdetails table, using bank_type ID and accountNumber
		# but if we cannot look up those info, will leave the fields blank
		$playerBankDetails = $this->CI->playerbankdetails->getBankDetailsByBankAccount($bank, $accNum);
		$this->utils->debug_log("=========================sdpay2nd get playerBankDetails using [$bank] + [$accNum]", $playerBankDetails);

		return $params;
	}

	public function submitWithdrawRequest($bank, $accNum, $name, $amount, $transId) {
		$result = array('success' => false, 'message' => 'payment failed');
		$success = false;
		$message = 'payment failed';
		/*if(!$this->isAllowWithdraw()) {
			$result['message'] = lang("Withdraw not allowed with this API");
			$this->utils->debug_log($result);
			return $result;
		}*/

		$url = $this->getSystemInfo('url');

		$data = $this->getWithdrawParams($bank, $accNum, $name, $amount, $transId);

        $merchantid = $this->getSystemInfo("sdpay_merchantId");
        $key1 = $this->getSystemInfo("sdpay_key1");
        $key2 = $this->getSystemInfo("sdpay_key2");

        $xml = "<TransferInfomation>"
            ."<Id>0</Id>"
            ."<IntoAccount>".$data["to_cardnumber"]."</IntoAccount>"
            ."<IntoName>".$data["to_username"]."</IntoName>"
            ."<IntoBank1>".$data["to_bank_flag"]."</IntoBank1>"
            ."<IntoBank2></IntoBank2>"
            ."<IntoAmount>".$data['amount']."</IntoAmount>"
            // ."<TransferNote></TransferNote>"
            // ."<RecordsState>0</RecordsState>"
            ."<SerialNumber>".$data['order_id']."</SerialNumber>"
            ."</TransferInfomation>";

        $mencrypt = new SDPay_MEncrypt($key1,$key2);
        $des = $mencrypt->encryptData($xml);
        $this->utils->debug_log("=========================sdpay2nd_withdrawal submit begin xml", [ $xml]);
        $this->utils->debug_log("=========================sdpay2nd_withdrawal submit begin des", [ $des]);
        $this->utils->debug_log("=========================sdpay2nd_withdrawal submit begin url", [ $url]);

        try {

			$options = array();
			if( $this->getSystemInfo("call_http_proxy_host") ){
				$options["call_http_proxy_host"] = $this->getSystemInfo("call_http_proxy_host");
				$options["call_http_proxy_port"] = $this->getSystemInfo("call_http_proxy_port");
				$options["ignore_ssl_verify"] = true;
			}

			$soap = new ProxySoapClient($url."?WSDL", $options);

			$this->utils->debug_log("=========================sdpay2nd_withdrawal soap ",$soap);
			$loginaccount = $this->getSystemInfo('LoginAccount');
            $params = array(
                    'LoginAccount' => $loginaccount,
                    'GetFundInfo' => $des
                    );
            $this->utils->debug_log("=========================sdpay2nd_withdrawal params ",$params);
            $ret_code = $soap->GetFund($loginaccount,$des);

            $this->utils->debug_log("=========================sdpay2nd_withdrawal submit trigged", [ $ret_code ]);

            if ( $ret_code > 0 ){
                $success = true;
                $message = "[SDPay2ND] RecordID: ".strval($ret_code);
            }
            else if ( $ret_code < 0 ) {
                switch( $ret_code ){
                    case -11 :
                        {
                            $success = false;
                            $message = "[SDPay2ND] Submit amount is too low";
                        }
                        break;

                    case -12 :
                        {
                            $success = false;
                            $message = "[SDPay2ND] Incomplete information";
                        }
                        break;

                    case -13 :
                        {
                            $success = false;
                            $message = "[SDPay2ND] Branches need";
                        }
                        break;

                    case -14 :
                        {
                            $success = false;
                            $message = "[SDPay2ND] Beneficiary Bank is invalid";
                        }
                        break;

                    case -15 :
                        {
                            $success = false;
                            $message = "[SDPay2ND] Duplicate Order No.";
                        }
                        break;

                    case -16 :
                        {
                            $success = false;
                            $message = "[SDPay2ND] IP address is not in the whitelist";
                        }
                        break;

                    case -1 :
                    default :
                        {
                            $success = false;
                            $message = "[SDPay2ND] Error with unknown cause";
                        }
                        break;
                }
            }
            else {
                // not defined
                $success = false;
                $message = "[SDPay2ND] Error undefined";
            }
        } catch (SoapFault $exception) {
            $success = false;
            $this->utils->debug_log("====================================== exception->getMessage()", $exception->getMessage());
            $message = "[SoapClient] ".$exception->getMessage();
        }

		$result = array('success' => $success, 'message' => $message);
		return $result;
	}

	public function getSDPayBankInfo() {
		$bankInfo = array();
		$bankInfoArr = $this->getSystemInfo("sdpay_bank_info");
		if(!empty($bankInfoArr)) {
			foreach($bankInfoArr as $bankInfoItem) {
				$bankInfo[$bankInfoItem[0]] = array('name' => $bankInfoItem[1], 'code' => $bankInfoItem[2]);
			}
			$this->utils->debug_log("==================getting sdpay bank info from extra_info: ", $bankInfo);
		} else {
            $bankInfo = array(
                    '1'  => array('name' => '中国工商银行' , 'code' => 'ICBC') ,
                    '2'  => array('name' => '招商银行'     , 'code' => 'CMB')  ,
                    '3'  => array('name' => '中国建设银行' , 'code' => 'CCB')  ,
                    '4'  => array('name' => '中国农业银行' , 'code' => 'ABC')  ,
                    '5'  => array('name' => '交通银行'     , 'code' => 'COMM')  ,
                    '6'  => array('name' => '中国银行'     , 'code' => 'BOC')  ,
                    '7'  => array('name' => '深圳发展银行' , 'code' => 'SDB')  ,
                    '8'  => array('name' => '广东发展银行' , 'code' => 'GDB')  ,
                    '10' => array('name' => '中信银行'     , 'code' => 'CITIC') ,
                    '11' => array('name' => '中国民生银行' , 'code' => 'CMBC') ,
                    '12' => array('name' => '中国邮政储蓄' , 'code' => 'PSBC') ,
                    '13' => array('name' => '兴业银行'     , 'code' => 'CIB')  ,
                    '14' => array('name' => '华夏银行'     , 'code' => 'HXB')  ,
                    '15' => array('name' => '平安银行'     , 'code' => 'PAB')  ,
                    '17' => array('name' => '广州银行'     , 'code' => 'GZB')  ,
                    '20' => array('name' => '中国光大银行' , 'code' => 'CEB')  ,
                    );
			$this->utils->debug_log("=======================getting sdpay bank info from code: ", $bankInfo);
		}
		return $bankInfo;
	}

    public function callbackFromServer($orderId, $params) {
        $response_result_id = $this->callbackPreprocess($orderId, $params, 'server');

        if(empty($orderId) || is_null($orderId)){
			$params = $_REQUEST;
		}

        $encryptedData = $params["HiddenField1"];
        $decrypt = new SDPay_Decrypt($this->getSystemInfo('sdpay_key1'), $this->getSystemInfo('sdpay_key2'));
        $xml = $decrypt->decryptData($encryptedData);
        if($xml === false){
        	$params = $_REQUEST;
        	$encryptedData = $params["HiddenField1"];
        	$decrypt = new SDPay_Decrypt($this->getSystemInfo('sdpay_key1'), $this->getSystemInfo('sdpay_key2'));
        	$xml = $decrypt->decryptData($encryptedData);
        	$arr = (array)simplexml_load_string($xml);
        	//$orderId = $arr["SerialNumber"];
        	$params = array_merge( $params, $arr );
        	$this->CI->utils->debug_log('==============sdpay2nd process withdrawalResult recheck starting...id', $orderId, $params );
        }else{
        	$arr = (array)simplexml_load_string($xml);
	        //$orderId = $arr["SerialNumber"];
	        $params = array_merge( $params, $arr );
	        $this->CI->utils->debug_log('==============sdpay2nd process withdrawalResult starting...id', $orderId, $params );
        }

        $walletAccount=$this->CI->wallet_model->getWalletAccountByTransactionCode($params["SerialNumber"]);

        if (
                $this->convertAmountToCurrency($walletAccount['amount']) !=
                $this->convertAmountToCurrency(floatval($params['IntoAmount'] ))
           ) {
            $this->writePaymentErrorLog("==============sdfpay withdrawal checkCallback payment amounts do not match, expected ".$walletAccount['amount'], $params);
            $result = ['success' => false, 'message' => 'sdpay withdrawal checkCallback payment amounts do not match'];
            return $result;
        }

        $result = array('success' => false, 'message' => 'Payment failed');

        switch( strval($params['RecordsState']) ){
            case "2": // successful
                {
                    $msg = sprintf('sdpay payment was successful: trade ID [%s]', $params['SerialNumber']);
                    $amount = $this->convertAmountToCurrency(floatval($params['IntoAmount']));
                    $this->CI->wallet_model->withdrawalAPIReturnSuccess($orderId, $msg, NULL, $amount);

                    $RETURN_SUCCESS_TEXT='<span id="resultLable">Success</span>';

                    $result['message'] = $RETURN_SUCCESS_TEXT;
                    $result['success'] = true;
                }
                break;

            case "0": // untreated
            case "3": // failure
            case "4": // others
                {
                    $msg = sprintf('sdpay withdrawal payment was failed [%s]', $params['RecordsState']);
                    $this->writePaymentErrorLog($msg, $params);

                    $errmsg = "[SDPay2ND]".$params["Tip"]." RecordsState:".$params['RecordsState'];
                    $this->CI->wallet_model->withdrawalAPIReturnFailure($orderId, $errmsg );

                    $result['message'] = $msg;
                    $result['success'] = false;
                }
                break;

            case "1": // being processed
            default:
                {
                    $result['message'] = "Skip and keep waiting";
                    $result['success'] = false;
                }
        }

        return $result;
    }
}

class SDPay_MEncrypt{
	public $key;
	public $iv;

	function __construct ($key, $iv)
	{
		if (empty($key) || empty($iv)) {
			echo 'key and iv is not valid';
			exit();
		}
		$this->key = $key;
		$this->iv = $iv;
	}


	public  function encryptData($value){
		$md5hash = md5($this->GetMac().date("Y-m-d h:m:s"));
		$value=$value;

		return $this->encrypt($value).$md5hash;
	}
	public  function decryptData($value){
		$des = $this->decrypt($value);
		return substr($des, 0,strlen($des)-32);
	}
	public function encrypt ($value)
	{
		$td = @mcrypt_module_open(MCRYPT_3DES, '', MCRYPT_MODE_CBC, '');
		$key = base64_decode($this->key);
		$iv = base64_decode($this->iv);
		$value = $this->PaddingPKCS7($value);
		@mcrypt_generic_init($td, $key, $iv);
		$ret = base64_encode(@mcrypt_generic($td, $value));
		@mcrypt_generic_deinit($td);
		@mcrypt_module_close($td);
		return $ret;
	}

	public function decrypt ($value)
	{
		$td = @mcrypt_module_open(MCRYPT_3DES, '', MCRYPT_MODE_CBC, '');
		$iv = base64_decode($this->iv);
		$key = base64_decode($this->key);
		@mcrypt_generic_init($td, $key, $iv);
		$ret = trim(@mdecrypt_generic($td, base64_decode($value)));
		$ret = $this->UnPaddingPKCS7($ret);
		@mcrypt_generic_deinit($td);
		@mcrypt_module_close($td);
		return $ret;
	}

	private function PaddingPKCS7 ($data)
	{
		$block_size = @mcrypt_get_block_size(MCRYPT_DES, MCRYPT_MODE_CBC);
		$padding_char = $block_size - (strlen($data) % $block_size);
		$data .= str_repeat(chr($padding_char), $padding_char);
		return $data;
	}

	private function UnPaddingPKCS7($text)
	{
		$pad = ord($text[strlen($text) - 1]);
		if ($pad > strlen($text)) {
			return false;
		}
		if (strspn($text, chr($pad), strlen($text) - $pad) != $pad) {
			return false;
		}
		return substr($text, 0, - 1 * $pad);
	}
	function GetMd5Hash($input){
		return sha1($input);
	}
	function GetMac(){
		return	date("Y-m-d h:m:s").rand();
	}
}

	class SDPay_Decrypt{
	public $key;
	public $iv;

	function __construct ($key, $iv)
	{
		if (empty($key) || empty($iv)) {
			echo 'key and iv is not valid';
			exit();
		}
		$this->key = $key;
		$this->iv = $iv;
	}

	public  function decryptData($value){
		$des = $this->decrypt(substr($value,0,-32));
		return $des;
	}

	public function decrypt ($value)
	{
		$td = @mcrypt_module_open(MCRYPT_3DES, '', MCRYPT_MODE_CBC, '');
		$iv = base64_decode($this->iv);
		$key = base64_decode($this->key);
		@mcrypt_generic_init($td, $key, $iv);
		$ret = trim(@mdecrypt_generic($td, base64_decode($value)));
		$ret = $this->UnPaddingPKCS7($ret);
		@mcrypt_generic_deinit($td);
		@mcrypt_module_close($td);
		return $ret;
	}

	private function PaddingPKCS7 ($data)
	{
		$block_size = @mcrypt_get_block_size(MCRYPT_DES, MCRYPT_MODE_CBC);
		$padding_char = $block_size - (strlen($data) % $block_size);
		$data .= str_repeat(chr($padding_char), $padding_char);
		return $data;
	}

	private function UnPaddingPKCS7($text)
	{
		$pad = ord($text[strlen($text) - 1]);
		if ($pad > strlen($text)) {
			return false;
		}
		if (strspn($text, chr($pad), strlen($text) - $pad) != $pad) {
			return false;
		}
		return substr($text, 0, - 1 * $pad);
	}
	function GetMd5Hash($input){
		return sha1($input);
	}
	function GetMac(){
		return	date("Y-m-d h:m:s").rand();
	}
}
