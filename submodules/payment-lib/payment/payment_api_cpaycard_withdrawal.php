<?php
require_once dirname(__FILE__) . '/abstract_payment_api_cpaycard.php';

/**
 * CPAYCARD
 * http://ncompany.cpay.life
 * * CPAYCARD_WITHDRAWAL_PAYMENT_API, ID: 5667
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 * * Extra Info
 *
 * Field Values:
 * * URL: http://open.cpay.life/api/cash
 * * Account: ## Merchant ID ##
 * * Key: ## App Key ##
 * * Secret: ## Secret Key ##
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_cpaycard_withdrawal extends Abstract_payment_api_cpaycard
{
    const CALLBACK_FAILED  = -1;

    public function getPlatformCode()
    {
        return CPAYCARD_WITHDRAWAL_PAYMENT_API;
    }

    public function getPrefix()
    {
        return 'cpaycard_withdrawal';
    }


    public function getWithdrawParams($bank, $accNum, $name, $amount, $transId)
    {
        $this->CI->load->model(array('wallet_model', 'player_model', 'playerbankdetails'));
        # look up bank code
        $bankInfo = $this->getBankInfo();
        $bankCode = $bankInfo[$bank]['code'];

        $order = $this->CI->wallet_model->getWalletAccountByTransactionCode($transId);

        $dateTimeString = $order['dwDateTime'];
        $datetime = new DateTime($dateTimeString);
        $params = array();
        $params['psd_order_id']   = $transId;
        $params['psd_order_time'] = $datetime->format('YmdHis');
        $params['order_amount']   = $this->convertAmountToCurrency($amount);
        $params['detail_list'] = array(
            "bank_code" => $bankCode,
            "card_id" => $accNum,
            "name" =>  $name
        );

        $this->CI->utils->debug_log("=====================cpaycard getWithdrawParams", $params);

        return $params;
    }

    public function submitWithdrawRequest($bank, $accNum, $name, $amount, $transId)
    {
        $result = array('success' => false, 'message' => 'Payment failed');

        if (!$this->isAllowWithdraw()) {
            $result['message'] = lang("Withdraw not allowed with this API");
            return $result;
        }
        if(!array_key_exists($bank, $this->getBankInfo())) {
            $this->utils->error_log("========================cpaycard submitWithdrawRequest bank whose bankTypeId=[$bank] is not supported by cpaycard");
            return array('success' => false, 'message' => 'Bank not supported by cpaycard');
            $bank = '无';
        }

        $params = $this->getWithdrawParams($bank, $accNum, $name, $amount, $transId);
        $params['detail_list'] = json_encode($params['detail_list'], JSON_UNESCAPED_SLASHES);

        list($content, $response_result) = $this->processCurl($params,$transId,true);

        $decodedResult = $this->decodeResult($content);
        $decodedResult['response_result'] = $response_result;

        $this->CI->utils->debug_log('======================================cpaycard submitWithdrawRequest decoded Result', $decodedResult);

        $this->CI->utils->debug_log('=====================cpaycard submitWithdrawRequest content', $content);

        $decodedResult = $this->decodeResult($content);

        return $decodedResult;
    }

    public function decodeResult($resultString, $queryAPI = false)
    {
        if(is_array($resultString) && isset($resultString['lock']) && isset($resultString['msg'])) {
            $result = array('success' => false, 'message' => $resultString['msg'], 'lock' => true);
            return $result;
        }
        if(!is_null(json_decode($resultString))){
            $resultString = json_decode($resultString, true);
            $this->CI->utils->debug_log('==============cpaycard decodeResult json decoded', $resultString);
        }

        if(isset($resultString['state'])) {
            if($resultString['state'] == self::CALLBACK_SUCCESS) {
                $message = "cpaycard withdrawal response successful, transId: ". $resultString['psd_order_id'];
                return array('success' => true, 'message' => $message);
            }
            $message = "cpaycard withdrawal response failed. [".$resultString['error']."]: ".$resultString['msg'];
            return array('success' => false, 'message' => $message);

        }
        elseif($resultString['msg']){
            $message = 'API response: '.$resultString['msg'];
            return array('success' => false, 'message' => $message);
        }

        return array('success' => false, 'message' => "cpaycard decoded fail.");
    }



    public function callbackFromServer($transId, $params)
    {
        $response_result_id = parent::callbackFromServer($transId, $params);
        return $this->callbackFrom('server', $transId, $params, $response_result_id);
    }

    public function callbackFrom($source, $transId, $params, $response_result_id)
    {
        $result = array('success' => false, 'message' => 'Payment failed');
        $order = $this->CI->wallet_model->getWalletAccountByTransactionCode($transId);
        $this->CI->utils->debug_log('=========================cpaycard process withdrawalResult transId', $transId);
        $this->CI->utils->debug_log("=========================cpaycard checkCallback params", $params);

        if (empty($params)) {
            $raw_post_data = file_get_contents('php://input', 'r');
            $this->CI->utils->debug_log("=====================cpaycard callbackFromServer raw_post_data", $raw_post_data);
        }

        $params = json_decode($raw_post_data, true);
        $this->CI->utils->debug_log("=====================cpaycard callbackFromServer json_decode params", $params);

        if (!$this->checkCallbackOrder($order, $params)) {
            return $result;
        }

        if ($params['state'] == self::CALLBACK_SUCCESS) {
            $msg = sprintf('Cpaycard withdrawal payment was successful: trade ID [%s]', $params['cpay_order_id']);
            $this->CI->wallet_model->withdrawalAPIReturnSuccess($transId, $msg);
            $result['success'] = true;
            $result['message'] = self::RETURN_SUCCESS_CODE;
        } elseif ($params['status'] == self::CALLBACK_FAILED) {
            $msg = sprintf('Cpaycard withdrawal payment was failed: status code [%s], '.$params['desc'], $params['state']);
            $this->writePaymentErrorLog($msg, $fields);
            $this->CI->wallet_model->withdrawalAPIReturnFailure($transId, $msg);
            $result['message'] = $msg;
        } else {
            $msg = sprintf('Cpay withdrawal payment was not successful: status code [%s], '.$params['desc'], $params['state']);
            $this->writePaymentErrorLog($msg, $fields);
            $result['message'] = $msg;
        }

        return $result;
    }

    private function checkCallbackOrder($order, $fields)
    {
        # does all required fields exist in the header?
        $requiredFields = array(
            'cpay_order_id', 'psd_order_id', 'company_id', 'order_amount', 'reward_amount', 'state', 'desc'
        );
        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("======================cpaycard withdrawal checkCallbackOrder missing parameter: [$f]", $fields);
                return false;
            }
        }

        if (!$this->verifySign($fields)) {
            $this->writePaymentErrorLog('=========================cpaycard withdrawal checkCallback signature Error', $fields);
            return false;
        }

        $check_amount = $this->convertAmountToCurrency($order->amount);
        if ($fields['order_amount'] != $check_amount) {
            $this->writePaymentErrorLog("=====================cpaycard Payment amounts do not match, expected [$order->amount]", $fields);
            return false;
        }

        if ($fields['psd_order_id'] != $order->secure_id) {
            $this->writePaymentErrorLog("======================cpaycard checkCallbackOrder order IDs do not match, expected [$order->secure_id]", $fields);
            return false;
        }

        # everything checked ok
        return true;
    }

    private function getNotifyUrl($orderId)
    {
        return parent::getCallbackUrl('/callback/process/' . $this->getPlatformCode() . '/' . $orderId);
    }

    public function getBankInfo()
    {
        $bankInfo = array();
        $bankInfoArr = $this->getSystemInfo("withdrawal_bank_info");
        if (!empty($bankInfoArr)) {
            foreach ($bankInfoArr as $system_bank_type_id => $bankInfoItem) {
                if (isset($bankInfoItem['name'])) {
                    $bankInfo[$system_bank_type_id]['name'] = $bankInfoItem['name'];
                }
                if (isset($bankInfoItem['code'])) {
                    $bankInfo[$system_bank_type_id]['code'] = $bankInfoItem['code'];
                }
            }
            $this->utils->debug_log("==================getting cpaycard bank info from extra_info: ", $bankInfo);
        } else {
            $bankInfo = array(
                '1'  => array('name' => '工商银行', 'code' => 'ICBC'),
                '2'  => array('name' => '招商银行', 'code' => 'CMB'),
                '3'  => array('name' => '建设银行', 'code' => 'CCB'),
                '4'  => array('name' => '农业银行', 'code' => 'ABC'),
                '5'  => array('name' => '交通银行', 'code' => 'COMM'),
                '6'  => array('name' => '中国银行', 'code' => 'BOC'),
                // '8'  => array('name' => '广东发展银行', 'code' => 'GDB'),
                '10' => array('name' => '中信银行  ', 'code' => 'CITIC'),
                '11' => array('name' => '民生银行', 'code' => 'CMBC'),
                '12' => array('name' => '中国邮政储蓄银行', 'code' => 'PSBC'),
                '13' => array('name' => '兴业银行', 'code' => 'CIB'),
                '14' => array('name' => '华夏银行', 'code' => 'HXB'),
                // '15' => array('name' => '平安银行', 'code' => 'SZPAB'),
                // '18' => array('name' => '南京银行', 'code' => 'NJCB'),
                // '19' => array('name' => '广州农商银行', 'code' => 'GNXS'),
                '20' => array('name' => '光大银行', 'code' => 'CEB'),
            );
            $this->utils->debug_log("=======================getting cpaycard bank info from code: ", $bankInfo);
        }
        return $bankInfo;
    }


    public function sign($params)
    {
        ksort($params);
        $signStr = '';
        foreach ($params as $key => $value) {
            if ($key == 'sign') {
                continue;
            }
            $signStr .= "$key=$value&";
        }
        $signStr =$signStr .'secret_key='.$this->getSystemInfo('secret');

        $token = md5($signStr);
        return $token;
    }

    public function verifySign($params)
    {
        $token = $this->sign($params);
        $headers = $this->CI->input->request_headers();
        $headers_token = $headers['token'];
        if($token == $headers_token){
            return true;
        }
        else{
            return false;
        }
    }


}
