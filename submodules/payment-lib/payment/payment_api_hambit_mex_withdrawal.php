<?php
require_once dirname(__FILE__) . '/abstract_payment_api_hambit.php';

/**
 * HAMBIT取款
 *
 * * HAMBIT_WITHDRAWAL_PAYMENT_API, ID: 6316
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: 
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 * * Extra Info:
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_hambit_mex_withdrawal extends abstract_payment_api_hambit {

	public function getPlatformCode() {
		return HAMBIT_MEX_WITHDRAWAL_PAYMENT_API;
	}

	public function getPrefix() {
		return 'hambit_mex_withdrawal';
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
        $this->utils->debug_log("==================hambit_mex withdraw get playerBankDetails using");

        $this->CI->load->model(array('wallet_model', 'player_model', 'playerbankdetails'));
        $playerBankDetails = $this->CI->playerbankdetails->getBankDetailsByBankAccount($bank, $accNum);

        $this->utils->debug_log("==================hambit_mex withdraw get playerBankDetails using [$bank] + [$accNum]", $playerBankDetails);
        $bankInfo = $this->getBankInfo();
        $bankCode = $bankInfo[$bank]['name'];


        $params['access_key'] = $this->getSystemInfo("account");
        $params['timestamp'] = number_format(microtime(true) * 1000, 0, '', '');
        $params['nonce'] = $this->createUUID();

        $params['currencyAmount'] = $this->convertAmountToCurrency($amount);
        $params['channelType'] = "BANK";
        $params['externalOrderId'] = $transId;
        $params['accountName'] = $name;
        $params['accountId'] = $accNum;
        $params['bankName'] = $bankCode;//
        $params['accountType'] = $this->getSystemInfo("accountType");
        $params['notifyUrl'] = $this->getNotifyUrl($transId);
        $params['remark'] = "hambit_mex_withdrawal";
        $params['sign']         = $this->sign($params);

        $this->CI->utils->debug_log('=========================hambit_mex withdrawal paramStr before sign', $params);
        return $params;
    }

	public function submitWithdrawRequest($bank, $accNum, $name, $amount, $transId) {
        $result = array('success' => false, 'message' => 'payment failed');
        $this->CI->load->model('playerbankdetails');

        if(!$this->isAllowWithdraw()) {
            $result['message'] = lang("Withdraw not allowed with this API");
            $this->utils->debug_log($result);
            return $result;
        }

        $playerBankDetails = $this->CI->playerbankdetails->getBankDetailsByBankAccount($bank, $accNum);
        $playerId = $playerBankDetails['playerId'];
        $validationResults = $this->checkWalletaccountPlayerId($playerId, $transId);
        if (!$validationResults['success']) {
            $this->utils->debug_log("===========hambit_mex", ["result" => $validationResults]);
            return $validationResults;
        }

        $params = $this->getWithdrawParams($bank, $accNum, $name, $amount, $transId);
        $url = $this->getWithdrawUrl();

        $this->_custom_curl_header = array(
            'Content-Type:application/json',
            'access_key:'.$params['access_key'],
            'timestamp: ' . $params['timestamp'],
            'nonce: ' . $params['nonce'],
            'sign: ' . $params['sign'],
        );
        $unset_params = ['access_key','timestamp','nonce','sign'];
        foreach ($unset_params as $key) {
            unset($params[$key]);
        }


        list($content, $response_result) = $this->submitPostForm($url, $params, true, $transId, true);

        $decodedResult = $this->decodeResult($content);

        $decodedResult['response_result'] = $response_result;

        $this->CI->utils->debug_log('======================================hambit_mex submitWithdrawRequest url: ', $url );
        $this->CI->utils->debug_log('======================================hambit_mex submitWithdrawRequest params: ', $params);
        $this->CI->utils->debug_log('======================================hambit_mex submitWithdrawRequest decoded Result', $decodedResult);
        
        return $decodedResult;
    }

	public function decodeResult($resultString, $queryAPI = false) {
        if(is_array($resultString) && isset($resultString['lock']) && isset($resultString['msg'])) {
            $result = array('success' => false, 'message' => $resultString['msg'], 'lock' => true);
            return $result;
        }
        $result = json_decode($resultString, true);
        $this->utils->debug_log("=========================hambit_mex json_decode result", $result);
        if(!empty($result) && isset($result)){
            if(isset($result['code']) && $result['code'] == self::REPONSE_CODE_SUCCESS ){
                return array('success' => true, 'message' => 'hambit_mex withdrawal request successful.');
            }else if(isset($result['error']) && !empty($result['error'])){
                $errorMsg = $result['error'];
                return array('success' => false, 'message' => $errorMsg);
            }else{
                return array('success' => false, 'message' => 'hambit_mex withdrawal exist errors');
            }
        }else{
            return array('success' => false, 'message' => 'hambit_mex withdrawal exist errors');
        }
    }

	public function getNotifyUrl($orderId) {
        return parent::getCallbackUrl('/callback/process/' . $this->getPlatformCode() . '/' . $orderId);
    }

    public function getReturnUrl($orderId) {
        return parent::getCallbackUrl('/callback/browser/success/' . $this->getPlatformCode() . '/' . $orderId);
    }

    public function callbackFromServer($transId, $params) {
        $response_result_id = parent::callbackFromServer($transId, $params);
        $result = array('success' => false, 'message' => 'Payment failed');
        $order = $this->CI->wallet_model->getWalletAccountByTransactionCode($transId);

        if (empty($params)) {
            $raw_post_data = file_get_contents('php://input', 'r');
            $this->CI->utils->debug_log("=====================hambit_mex withdrawal callbackFromServer raw_post_data", $raw_post_data);
            $params = json_decode($raw_post_data,true);
            $this->CI->utils->debug_log("=====================hambit_mex withdrawal callbackFromServer json_decode params", $params);
        }

        if (!$this->checkCallbackOrder($order, $params)) {
            return $result;
        }

        if($params['orderStatus'] == self::WITHDRAWAL_CALLBACK_SUCCESS_CODE) {
            $msg = sprintf('hambit_mex withdrawal was successful: trade ID [%s]', $params['merOrderNo']);
            $this->CI->wallet_model->withdrawalAPIReturnSuccess($transId, $msg);
            $result['message'] = self::RETURN_SUCCESS_CODE;
            $result['success'] = true;
        }
        else {
            $msg = sprintf('hambit_mex withdrawal was not success: [%s]', $params['orderStatus']);
            $this->writePaymentErrorLog($msg, $params);
            $result['message'] = $msg;
        }

        return $result;
    }

    public function checkCallbackOrder($order, $fields, &$processed = false)
    {
        $head_params =[
            "access_key"=>$this->getSystemInfo('access_key'),//本幾紀錄商户号
            "timestamp"=>$_SERVER["HTTP_TIMESTAMP"],
            "nonce"=>$_SERVER["HTTP_NONCE"],
            "sign"=>$_SERVER["HTTP_SIGN"],
        ];

        $requiredFields = array('currencyType','externalOrderId', 'orderAmount', 'orderStatusCode', 'orderStatus');

        $this->CI->utils->debug_log("=========================hambit_mex checkCallback detailData", $fields);

        foreach ($requiredFields as $f) {
           if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("=======================hambit_mex withdrawal checkCallbackOrder missing parameter: [$f]", $fields);
                return false;
            }
        }

        if($fields['currencyType']!="MXN"){
            $this->writePaymentErrorLog("=======================hambit withdrawal checkCallbackOrder currencyType is error", $fields);
            return false;
        }

        if($fields['orderStatusCode']!=self::WITHDRAWAL_CALLBACK_SUCCESS_CODE){
            $this->writePaymentErrorLog("=======================hambit_mex withdrawal checkCallbackOrder orderStatusCode is not success", $fields);
            return false;
        }
        
        # is signature authentic?
        if (!$this->validateSign($fields, $head_params)) {
           $this->writePaymentErrorLog('=====================hambit_mex withdrawal checkCallbackOrder Signature Error', $fields);
           return false;
        }

        $processed = true; # processed is set to true once the signature verification pass

        if ($fields['externalOrderId'] != $order['transactionCode']) {
            $this->writePaymentErrorLog("======================hambit_mex withdrawal checkCallbackOrder order IDs do not match, expected ".$order['transactionCode'], $fields);
            return false;
        }

        if ($fields['orderAmount']  != $this->convertAmountToCurrency($order['amount'])) {
            $this->writePaymentErrorLog("======================hambit_mex withdrawal checkCallbackOrder payment amount is wrong, expected <= ". $order['amount'], $fields);
            return false;
        }

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
            $this->utils->debug_log("==================getting happypay bank info from extra_info: ", $bankInfo);
        } else  {
            $bankInfo = array(
                '1' => array('name' => 'BANCOMEXT', 'code' => '37006'),
                '2' => array('name' => 'BANJERCITO', 'code' => '37019'),    
                '3' => array('name' => 'BANSEFI', 'code' => '37166'),
                '4' => array('name' => 'BANAMEX', 'code' => '40002'),
                '5' => array('name' => 'SANTANDER', 'code' => '40014'),
                '6' => array('name' => 'BAJIO', 'code' => '40030'),
                '7' => array('name' => 'MIFEL', 'code' => '40042'),
                '8' => array('name' => 'BANREGIO', 'code' => '40058'),
                '10' => array('name' => 'BANSI', 'code' => '40060'),
                '11' => array('name' => 'BANORTE', 'code' => '40072'),
                '12' => array('name' => 'MUFG', 'code' => '40108'),
                '13' => array('name' => 'BMONEX', 'code' => '40112'),
                '14' => array('name' => 'CREDIT SUISSE', 'code' => '40126'),
                '15' => array('name' => 'AUTOFIN', 'code' => '40128'),
                '17' => array('name' => 'COMPARTAMOS', 'code' => '40130'),
                '18' => array('name' => 'ACTINVER', 'code' => '40133'),
                '20' => array('name' => 'BANCOPPEL', 'code' => '40137'),
                '24' => array('name' => 'CONSUBANCO', 'code' => '40140'),
                '25' => array('name' => 'CIBANCO', 'code' => '40143'),
                '25' => array('name' => 'BANKAOOL', 'code' => '40147'),
                '26' => array('name' => 'INMOBILIARIO', 'code' => '40150'),
                '27' => array('name' => 'BANCREA', 'code' => '40152'),
                '28' => array('name' => 'ICBC', 'code' => '40155'),
                '29' => array('name' => 'SHINHAN', 'code' => '40157'),
                '30' => array('name' => 'BANCO S3', 'code' => '40160'),
                '31' => array('name' => 'GBM', 'code' => '90601'),
                '32' => array('name' => 'VALUE', 'code' => '90605'),
                '33' => array('name' => 'MULTIVA CBOLSA	', 'code' => '90613'),
                '34' => array('name' => 'VALMEX', 'code' => '90617'),
                '35' => array('name' => 'CB INTERCAM', 'code' => '90630'),
                '36' => array('name' => 'FINCOMUN', 'code' => '90634'),
                '37' => array('name' => 'REFORMA', 'code' => '90642'),
                '38' => array('name' => 'EVERCORE', 'code' => '90648'),
                '39' => array('name' => 'KUSPIT', 'code' => '90653'),
                '40' => array('name' => 'ASP INTEGRA OPC', 'code' => '90659'),
                '41' => array('name' => 'CAJA POP MEXICA', 'code' => '90677'),
                '42' => array('name' => 'CAJA TELEFONIST', 'code' => '90683'),
                '43' => array('name' => 'FONDO (FIRA)', 'code' => '90685'),
                '44' => array('name' => 'HSBC', 'code' => '40021'),
                '45' => array('name' => 'CoDi Valida', 'code' => '90903'),
                '46' => array('name' => 'BANAMEX2', 'code' => '91802'),
                '47' => array('name' => 'BANOBRAS', 'code' => '37009'),
                '48' => array('name' => 'NAFIN', 'code' => '37135'),
                '49' => array('name' => 'HIPOTECARIA FED', 'code' => '37168'),
                '50' => array('name' => 'BBVA MEXICO', 'code' => '40012'),
                '51' => array('name' => 'FOMPED', 'code' => '90689'),
                '52' => array('name' => 'INBURSA', 'code' => '40036'),
                '53' => array('name' => 'SCOTIABANK', 'code' => '40044'),
                '54' => array('name' => 'INVEX', 'code' => '40059'),
                '55' => array('name' => 'AFIRME', 'code' => '40062'),
                '56' => array('name' => 'BANK OF AMERICA', 'code' => '40106'),
                '57' => array('name' => 'JP MORGAN', 'code' => '40110'),
                '58' => array('name' => 'VE POR MAS	', 'code' => '40113'),
                '59' => array('name' => 'AZTECA', 'code' => '40127'),
                '60' => array('name' => 'BARCLAYS', 'code' => '40129'),
                '61' => array('name' => 'MULTIVA', 'code' => '40132'),
                '62' => array('name' => 'INTERCAM BANCO	', 'code' => '40136'),
                '63' => array('name' => 'ABC CAPITAL', 'code' => '40138'),
                '64' => array('name' => 'VOLKSWAGEN', 'code' => '40141'),
                '65' => array('name' => 'BBASE', 'code' => '40145'),
                '66' => array('name' => 'PAGATODO', 'code' => '40148'),
                '67' => array('name' => 'DONDE', 'code' => '40151'),
                '68' => array('name' => 'BANCO FINTERRA	', 'code' => '40154'),
                '69' => array('name' => 'SABADELL', 'code' => '40156'),
                '70' => array('name' => 'MIZUHO BANK', 'code' => '40158'),
                '71' => array('name' => 'MONEXCB', 'code' => '90600'),
                '72' => array('name' => 'MASARI', 'code' => '90602'),
                '73' => array('name' => 'VECTOR', 'code' => '90608'),
                '74' => array('name' => 'FINAMEX', 'code' => '90616'),
                '75' => array('name' => 'PROFUTURO', 'code' => '90620'),
                '76' => array('name' => 'CI BOLSA', 'code' => '90631'),
                '77' => array('name' => 'AKALA', 'code' => '90638'),
                '78' => array('name' => 'STP', 'code' => '90646'),
                '79' => array('name' => 'CREDICAPITAL', 'code' => '90652'),
                '80' => array('name' => 'UNAGRA', 'code' => '90656'),
                '81' => array('name' => 'LIBERTAD', 'code' => '90670'),
                '82' => array('name' => 'CRISTOBAL COLON	', 'code' => '90680'),
                '83' => array('name' => 'TRANSFER', 'code' => '90684'),
                '84' => array('name' => 'INVERCAP', 'code' => '90686'),
                '85' => array('name' => 'INDEVAL', 'code' => '90902'),
                '86' => array('name' => 'ARCUS', 'code' => '90706'),
            );
            $this->utils->debug_log("=======================getting aipay bank info from code: ", $bankInfo);
        }
        return $bankInfo;
    }

}
