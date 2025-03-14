<?php
require_once dirname(__FILE__) . '/abstract_payment_api_allbank_instapay.php';

/**
 *
 * * ALLBANK_INSTAPAY_WITHDRAWAL_PAYMENT_API, ID: 6574
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 *
 * @category Payment
 * @copyright 2013-2024 tot
 */
class Payment_api_allbank_instapay_withdrawal extends Abstract_payment_api_allbank_instapay {
    const ALLBANK_INSTAPAY_WITHDRAWAL = 'IPAYPTR';

	public function getPlatformCode() {
		return ALLBANK_INSTAPAY_WITHDRAWAL_PAYMENT_API;
	}

	public function getPrefix() {
		return 'allbank_instapay_withdrawal';
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
        $playerBankDetails = $this->CI->playerbankdetails->getBankDetailsByBankAccount($bank, $accNum);

        $this->utils->debug_log("==================allbank withdraw get playerBankDetails using [$bank] + [$accNum]", $playerBankDetails);

        if(!empty($playerBankDetails)){
            $playerId = $playerBankDetails['playerId'];
            $playerDetails = $this->CI->player_model->getPlayerDetails($playerId);
            $lastname   = (isset($playerDetails[0]) && !empty($playerDetails[0]['lastName'])) ? $playerDetails[0]['lastName'] : 'none';
        }

        $bankInfo = $this->getBankInfo();
        $bankCode = $bankInfo[$bank]['code'];

        $date = new DateTime();
		$params = array();

        $params['id'] = $this->getSystemInfo("account");
		$params['tdt'] = date_format($date, 'Y-m-d\TH:i:s.vP');
        $params['cmd'] = self::ALLBANK_INSTAPAY_WITHDRAWAL;
        $params['acctno'] = $this->getSystemInfo("source_acctno");
		$params['amt'] = $this->convertAmountToCurrency($amount);
		$params['dbk'] = $bankCode;
		$params['acctno2'] = $accNum;
		$params['ln'] = $lastname;
		$params['ref_id'] = $transId;
        $params['token'] = $this->sign($params);

        $this->CI->wallet_model->setExtraInfoByTransactionCode($transId, $params['acctno']);
		$this->CI->utils->debug_log('=========================allbank getWithdrawParams params', $params);
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
            $this->utils->error_log("========================allbank submitWithdrawRequest bank whose bankTypeId=[$bank] is not supported by paybus");
            return array('success' => false, 'message' => 'Bank not supported by allbank');
        }

        $params = $this->getWithdrawParams($bank, $accNum, $name, $amount, $transId);
        list($content, $response_result) = $this->processCurl($params, true);

        $decodedResult = $this->decodeResult($content);
        $decodedResult['response_result'] = $response_result;

        $this->CI->utils->debug_log('======================================allbank submitWithdrawRequest params: ', $params);
        $this->CI->utils->debug_log('======================================allbank submitWithdrawRequest response ', $response_result);
        $this->CI->utils->debug_log('======================================allbank submitWithdrawRequest decoded Result', $decodedResult);

        return $decodedResult;
    }

    protected function processCurl($params, $return_all=false){

        $post_xml_data = $this->array2xml($params);
		$this->CI->utils->debug_log('=====================allbank post_xml_data', $post_xml_data);
        $url = $this->getWithdrawUrl();

		$curlConn = curl_init();
		$curlData = array();
		$curlData[CURLOPT_POST] = true;
		$curlData[CURLOPT_URL] = $url;
		$curlData[CURLOPT_RETURNTRANSFER] = true;
		$curlData[CURLOPT_TIMEOUT] = 120;
		$curlData[CURLOPT_POSTFIELDS] = $post_xml_data;
		$curlData[CURLOPT_HTTPHEADER] = [ "Content-Type: text/xml", "SoapAction:'http://tempuri.org/iWebInterface/wb_Get_Info'"];

		curl_setopt_array($curlConn, $curlData);

		curl_setopt($curlConn, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curlConn, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($curlConn, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($curlConn, CURLOPT_SSL_VERIFYHOST, false);

		$response    = curl_exec($curlConn);
		$errCode     = curl_errno($curlConn);
        $error       = curl_error($curlConn);
        $statusCode  = curl_getinfo($curlConn, CURLINFO_HTTP_CODE);

        $curlSuccess = ($errCode == 0);
        $response_result_id = $this->submitPreprocess($params, $response, $this->getSystemInfo('url'), $response, array('errCode' => $errCode, 'error' => $error, 'statusCode' => $statusCode), $params['ref_id']);

		$response = $this->parseResultXML($response);
		$this->CI->utils->debug_log('=====================allbank processCurl response', $response);

        if($return_all){
            $response_result = [
                $params, $response, $url, $response, ['errCode' => $errCode, 'error' => $error, 'statusCode' => $statusCode], $params['ref_id']
            ];
            return array($response, $response_result);
        }
        return $response;
    }

    public function decodeResult($result) {
        if(isset($result['ReturnCode'])) {
            if($result['ReturnCode'] == self::REPONSE_CODE_SUCCESS) {
                $message = "allbank withdrawal response successful, code:[".$result['ReturnCode']."]: ".$result['ErrorMsg'];
                return array('success' => true, 'message' => $message);
            }
            $message = "allbank withdrawal response failed. [".$result['ReturnCode']."]: ".$result['ErrorMsg'];
            return array('success' => false, 'message' => $message);
        }
        return array('success' => false, 'message' => "allbank decoded fail.");
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
            $this->utils->debug_log("==================getting allbank bank info from extra_info: ", $bankInfo);
        } else  {
            $bankInfo =  array(
                "65" => [
                    "name" => "Unionbank",
                    "code" => "0041"
                ]
            );
            $this->utils->debug_log("=======================getting allbank bank info from code: ", $bankInfo);
        }
        return $bankInfo;
    }

    protected function convertAmountToCurrency($amount) {
        return number_format($amount, 2, '.', '');
    }
    
}
