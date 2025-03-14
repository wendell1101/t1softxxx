<?php
require_once dirname(__FILE__) . '/abstract_payment_api_paymerocrypto.php';
/**
 * PAYMEROCRYPTO_LTC
 *
 * * PAYMEROCRYPTO_LTC_WITHDRAWAL_PAYMENT_API, ID: 5835
 * *
 * Required Fields:
 * * URL
 *
 * Field Values:
 * * URL: https://service-api.paymero.io/v1/crypto/

 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_paymerocrypto_ltc_withdrawal extends Abstract_payment_api_paymerocrypto {
    public function getPlatformCode() {
        return PAYMEROCRYPTO_LTC_WITHDRAWAL_PAYMENT_API;
    }

    public function getPrefix() {
        return 'paymerocrypto_ltc_withdrawal';
    }

    protected function getCoin() {
        return $this->getSystemInfo('coin', self::COIN_LTC);;
    }

    public function submitWithdrawRequest($bank, $accNum, $name, $amount, $transId, $data=null) {
        $result = array('success' => false, 'message' => 'payment failed');

        if(!$this->isAllowWithdraw()) {
            $result['message'] = lang("Withdraw not allowed with this API");
            return $result;
        }

        if(!array_key_exists($bank, $this->getBankInfo())) {
            $this->CI->utils->debug_log('======================================paymerocrypto_ltc withdrawal bank whose bankTypeId=[$bank] is not supported by paymerocrypto_ltc');
            return array('success' => false, 'message' => 'Bank not supported by paymerocrypto_ltc');
        }

        $order = $this->CI->wallet_model->getWalletAccountByTransactionCode($transId);
        $currency  = $this->getCoin();
        $targetCurrency = $this->targetCurrency;
        #----Create Withdrawal----
        #https://service-api.paymero.io/v1/crypto/withdrawal
            $withdrawal_url = $this->getSystemInfo('url').$this->paymeny_type;

            $params = array();
            $params['targetCurrency'] = $currency;
            $params['sourceCurrency'] = $targetCurrency;
            $params['cashierCurrency'] = $targetCurrency;
            $params['cashierAmount']  = $amount;
            $params['address']        = $accNum;;
            $params['notifyUrl']      = $this->getNotifyUrl($transId);
            $params['externalId']     = $transId;

            $this->CI->utils->debug_log('=====================paymerocrypto_ltc withdrawal generatePaymentUrlForm params', $params, $withdrawal_url);

            list($response, $response_result) = $this->submitPostForm($withdrawal_url, $params, true, $transId, true);

            $response = json_decode($response, true);

            $this->CI->utils->debug_log('=====================paymerocrypto_ltc withdrawal response', $response);

            $decodedResult = $this->decodeResult($response);
            $decodedResult['response_result'] = $response_result;


            if($decodedResult['success']){
                $this->CI->wallet_model->setExtraInfoByTransactionCode($transId, $response['data']['id']);
            }
            $this->CI->utils->debug_log('======================================paymerocrypto_ltc withdrawal submitWithdrawRequest decoded Result', $decodedResult);

        return $decodedResult;
    }

    public function decodeResult($result) {
        if(is_array($result) && isset($result['lock']) && isset($result['msg'])) {
            $result = array('success' => false, 'message' => $result['msg'], 'lock' => true);
            return $result;
        }
        $this->utils->debug_log("=========================paymerocrypto_ltc withdrawal decodeResult", $result);

        if (isset($result['status'])) {
            if($result['status'] == self::RETURN_SUCCESS_CODE){
                $txid    = $result['data']['id'];
                $bitcoin = abs($result['data']['targetAmount']);
                $message = "paymerocrypto btc withdrawal successful. Txid: ". $txid .", Transfered: ".$bitcoin;

                return array('success' => true, 'message' => $message);
            }
            elseif($result['status'] == self::RETURN_ERROR_CODE) {
                $message = "paymerocrypto btc response failed. [".json_encode($result['data'])."]: ";
                return array('success' => false, 'message' => $message);
            }
        } else {
            if (isset($result['message'])) {
                $message = "paymerocrypto btc response failed. [".$result['message']."]: ";
                return array('success' => false, 'message' => $message);
            }
            return array('success' => false, 'message' => lang("Connect paymerocrypto-express failed"));
        }
    }

    public function getBankInfo() {
        $bankInfo = array();
        $bankInfoArr = $this->getSystemInfo("paymerocrypto_ltc_bank_info");
        if(!empty($bankInfoArr)) {
            foreach($bankInfoArr as $system_bank_type_id => $bankInfoItem) {
                $bankInfo[$system_bank_type_id] = $bankInfoItem;
            }
            $this->utils->debug_log("==================getting withdrawal paymerocrypto_ltc bank info from extra_info: ", $bankInfo);
        } else {
            $bankInfo = array(
                '227'  => 'LTC',
            );
            $this->utils->debug_log("=======================getting withdrawal paymerocrypto_ltc bank info from code: ", $bankInfo);
        }
        return $bankInfo;
    }

    # -- Private functions --
    private function getNotifyUrl($orderId) {
        return parent::getCallbackUrl('/callback/process/' . $this->getPlatformCode() . '/' . $orderId);
    }
}