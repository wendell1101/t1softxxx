<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';
/**
 * BVVPAY
 *
 * * BVVPAY_WITHDRAWAL_PAYMENT_API, ID: 5157
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://api.bvvpay.com/api/withdraw
 * * Account: ## User ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_bvvpay_withdrawal extends Abstract_payment_api {
    const RETURN_STATUS_SUCCESS = 'success';
    const RETURN_STATUS_FAILED  = 'error';

    const CALLBACK_STATUS_SUCCESS = 3;
    const CALLBACK_STATUS_FAILED  = 4;
    const RETURN_SUCCESS_CODE     = 'ok';

    public function getPlatformCode() {
        return BVVPAY_WITHDRAWAL_PAYMENT_API;
    }

    public function getPrefix() {
        return 'bvvpay_withdrawal';
    }

    public function generatePaymentUrlForm($orderId, $playerId, $amount, $orderDateTime, $playerPromoId = NULL, $enabledSecondUrl = true, $bankId = NULL) {}
    public function directPay($order) {}

    public function submitWithdrawRequest($bank, $accNum, $name, $amount, $transId) {
        $result = array('success' => false, 'message' => 'payment failed');

        if(!$this->isAllowWithdraw()) {
            $result['message'] = lang("Withdraw not allowed with this API");
            return $result;
        }
        if(!array_key_exists($bank, $this->getBankInfo())) {
            $this->utils->error_log("========================bvvpay submitWithdrawRequest bank whose bankTypeId=[$bank] is not supported by bvvpay");
            return array('success' => false, 'message' => 'Bank not supported by bvvpay');
        }

        $params = $this->getWithdrawParams($bank, $accNum, $name, $amount, $transId);
        $url = $this->getWithdrawUrl();

        list($response, $response_result) = $this->submitPostForm($url, $params, false, $transId, true);

        $decodedResult = $this->decodeResult($response);
        $decodedResult['response_result'] = $response_result;

        $this->CI->utils->debug_log('======================================bvvpay submitWithdrawRequest url: ', $url );
        $this->CI->utils->debug_log('======================================bvvpay submitWithdrawRequest params: ', $params);
        $this->CI->utils->debug_log('======================================bvvpay submitWithdrawRequest response ', $response);
        $this->CI->utils->debug_log('======================================bvvpay submitWithdrawRequest decoded Result', $decodedResult);

        return $decodedResult;
    }

    public function getWithdrawUrl() {
        return $this->getSystemInfo('url');
    }

    public function getWithdrawParams($bank, $accNum, $name, $amount, $transId) {
        $this->CI->load->model(array('wallet_model', 'player_model', 'playerbankdetails'));
        # look up bank code
        $bankInfo = $this->getBankInfo();

        $params = array();
        $params['usercode']  = $this->getSystemInfo('account');
        $params['customno']  = $transId;
        $params['money']     = $this->convertAmountToCurrency($amount);
        $params['bankcode']  = $bankInfo[$bank];
        $params['realname']  = $name;
        $params['idcard']    = '123456789123456789';
        $params['cardno']    = $accNum;
        $params['notifyurl'] = $this->getNotifyUrl($transId);
        $params['sendtime']  = date('YmdHis');
        $params['buyerip']   = $this->getClientIP();
        $params['sign']      = $this->sign($params);

        return $params;
    }

    public function decodeResult($resultString, $queryAPI = false) {
        if(is_array($resultString) && isset($resultString['lock']) && isset($resultString['msg'])) {
            $result = array('success' => false, 'message' => $resultString['msg'], 'lock' => true);
            return $result;
        }
        if(!is_null(json_decode($resultString))){
            $resultString = json_decode($resultString, true);
            $this->CI->utils->debug_log('==============bvvpay decodeResult json decoded', $resultString);
        }

        if(isset($resultString['success'])) {
            if($resultString['success']) {
                $message = "Bvvpay withdrawal response successful, transId: ". $resultString['data']['orderno'];
                return array('success' => true, 'message' => $message);
            }
            $message = "Bvvpay withdrawal response failed. [".$resultString['resultCode']."]: ".$resultString['resultMsg'];
            return array('success' => false, 'message' => $message);

        }
        elseif($resultString){
            $message = 'API response: '.$resultString;
            return array('success' => false, 'message' => $message);
        }

        return array('success' => false, 'message' => "Bvvpay decoded fail.");
    }

    public function callbackFromServer($transId, $params) {
        $response_result_id = parent::callbackFromServer($transId, $params);
        $result = array('success' => false, 'message' => 'Payment failed');
        $order = $this->CI->wallet_model->getWalletAccountByTransactionCode($transId);

        if (!$this->checkCallbackOrder($order, $params)) {
            return $result;
        }

        $statusCode = $params['status'];
        if($statusCode == self::CALLBACK_STATUS_SUCCESS) {
            $msg = "Bvvpay withdrawal success!";
            $this->CI->wallet_model->withdrawalAPIReturnSuccess($transId, $msg);

            $result['message'] = self::RETURN_SUCCESS_CODE;
            $result['success'] = true;
        }
        else if($statusCode == self::CALLBACK_STATUS_FAILED){
            $msg = "Bvvpay withdrawal failed.";
            $this->CI->wallet_model->withdrawalAPIReturnFailure($transId, $msg);
            $result['message'] = $msg;
        }
        else {
            $msg = "Bvvpay withdrawal response [".$params['resultcode']."]: ". $params['resultmsg'];
            $this->writePaymentErrorLog($msg, $fields);
            $result['message'] = $msg;
        }

        return $result;
    }

    private function checkCallbackOrder($order, $fields) {
        $requiredFields = array(
            'orderno', 'usercode', 'tjmoney', 'customno', 'sign'
        );
        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("======================bvvpay withdrawal checkCallbackOrder missing parameter: [$f]", $fields);
                return false;
            }
        }

        if (!$this->verifySign($fields)) {
            $this->writePaymentErrorLog('=========================bvvpay withdrawal checkCallback signature Error', $fields);
            return false;
        }

        if ($fields['tjmoney'] != $this->convertAmountToCurrency($order['amount'])) {
            $this->writePaymentErrorLog('=========================bvvpay withdrawal checkCallbackOrder payment amount is wrong, expected =>'. $order['amount'], $fields);
            return false;
        }

        if ($fields['customno'] != $order['transactionCode']) {
            $this->writePaymentErrorLog('=========================bvvpay withdrawal checkCallbackOrder order IDs do not match, expected =>'. $order['transactionCode'], $fields);
            return false;
        }

        # everything checked ok
        return true;
    }

    public function getBankInfo() {
        $bankInfo = array();
        $bankInfoArr = $this->getSystemInfo("bvvpay_bank_info");
        if(!empty($bankInfoArr)) {
            foreach($bankInfoArr as $bankInfoItem) {
                $bankInfo[$bankInfoItem[0]] = $bankInfoItem[1];
            }
            $this->utils->debug_log("==================getting bvvpay bank info from extra_info: ", $bankInfo);
        } else {
            $bankInfo = array(
                '1' => 'ICBC',
                '2' => 'CMB',
                '3' => 'CCB',
                '4' => 'ABC',
                '5' => 'COMM',
                '6' => 'BOC',
                '7' => 'SZDB',
                '8' => 'CGB',
                '9' => 'DRCB',
                '10' => 'CITIC',
                '11' => 'CMBC',
                '12' => 'PSBC',
                '13' => 'CIB',
                '14' => 'HXB',
                '15' => 'PAB',
                '16' => 'GXNX',
                '17' => 'GZB',
                '18' => 'NJCB',
                '19' => 'GRCB',
                '20' => 'CEB',
                '24' => 'SPDB',
                '26' => 'CGB',
                '27' => 'SPDB',
                '28' => 'HKBEA',
                '29' => 'BJB',
                '30' => 'TCCB',
                '31' => 'BOSH',
                '26' => 'CGB',
                '27' => 'SPDB',
                '28' => 'HKBEA',
                '29' => 'BJB',
                '30' => 'TCCB',
                '31' => 'BOSH',
                '32' => 'SRCB',
                '33' => 'BJRCB',
                '37' => 'CSCB',
                '39' => 'BOCD1',
                '40' => 'CQCB',
                '43' => 'BODY',
                '44' => 'BODG',
                '45' => 'GZBANK',
                '48' => 'HZCB',
                '49' => 'HEBB',
                '50' => 'BOHL',
                '51' => 'BOHZ',
                '52' => 'BOIM',
                '53' => 'JSBC',
                '54' => 'JXB',
                '55' => 'BOJL',
                '57' => 'BOJN',
                '59' => 'JJBANK',
                '60' => 'BOK',
                '61' => 'LFB',
                '62' => 'LZYH',
                '64' => 'BOLZ',
                '65' => 'BOLY',
                '66' => 'NCB',
                '67' => 'NBCB',
                '68' => 'BONX',
                '69' => 'QDCCB',
                '70' => 'BOQH',
                '71' => 'QZCCB',
                '72' => 'BORZ',
                '73' => 'BOSX',
                '74' => 'BOSZ',
                '76' => 'TZB',
                '77' => 'BOWF',
                '78' => 'WZCB',
                '79' => 'XACB',
                '80' => 'BOYK',
                '81' => 'BOZZ',
                '83' => 'BSB',
                '85' => 'CAB',
                '86' => 'CBHB',
                '88' => 'CRBZ',
                '89' => 'CZB',
                '97' => 'DZB',
                '100' => 'EGB',
                '102' => 'FDB',
                '103' => 'FHB',
                '104' => 'GDHXB',
                '105' => 'GNB',
                '106' => 'GBGB',
                '107' => 'GLB',
                '110' => 'HKB',
                '111' => 'HRBCB',
                '115' => 'HBC',
                '116' => 'HSB',
                '117' => 'BOJS',
                '120' => 'LSB',
                '122' => 'BOLS',
                '123' => 'LJB',
                '125' => 'NCCCB',
                '128' => 'NCBANK',
                '131' => 'QLB',
                '132' => 'QSB',
                '133' => 'SJB',
                '134' => 'XHB',
                '141' => 'WHCCB',
                '143' => 'XMCCB',
                '145' => 'YTB',
                '147' => 'CZCB',
                '148' => 'MTCB',
                '149' => 'ZJTLCB',
                '158' => 'CDRCB',
                '185' => 'KSNX',
                '198' => 'RCBOZ',
                '203' => 'SZRCB',
                '205' => 'TCRCB',
                '211' => 'WHRCB',
            );
            $this->utils->debug_log("=======================getting bvvpay bank info from code: ", $bankInfo);
        }
        return $bankInfo;
    }

    # -- signing --
    public function sign($params) {
        $keys = array('usercode', 'customno', 'bankcode', 'cardno', 'idcard', 'money', 'sendtime', 'buyerip');
        $signStr = "";
        foreach($keys as $key) {
            if (array_key_exists($key, $params)) {
                $signStr .= $params[$key] . '|';
            }
        }
        $signStr .= $this->getSystemInfo('key');
        $sign = md5($signStr);
       
        return $sign;
    }

    public function verifySign($params){
        $keys = array('usercode', 'orderno', 'customno', 'bankcode', 'cardno', 'realname', 'idcard', 'tjmoney', 'money', 'status');
        $signStr = "";
        foreach($keys as $key) {
            if (array_key_exists($key, $params)) {
                $signStr .= $params[$key] . '|';
            }
        }
        $signStr .= $this->getSystemInfo('key');
        $sign = md5($signStr);

        if($sign == $params["sign"]){
            return true;
        } else {
            return false;
        }
    }

    private function getNotifyUrl($orderId) {
        return parent::getCallbackUrl('/callback/process/' . $this->getPlatformCode() . '/' . $orderId);
    }

    public function convertAmountToCurrency($amount) {
        return number_format($amount, 2, '.', '');
    }
}