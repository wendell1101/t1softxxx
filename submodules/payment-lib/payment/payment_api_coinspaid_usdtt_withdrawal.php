<?php
require_once dirname(__FILE__) . '/abstract_payment_api_coinspaid.php';
/**
 * coinspaid_usdtt
 *
 * * COINSPAID_USDTT_WITHDRAWAL_PAYMENT_API, ID: 6180
 * *
 * Required Fields:
 * * URL
 *
 * Field Values:
 * * URL:https://app.cryptoprocessing.com/api/v2/withdrawal/crypto

 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_coinspaid_usdtt_withdrawal extends Abstract_payment_api_coinspaid {
    const CALLBACK_TYPE_SEND = "withdrawal_exchange";

    public function getPlatformCode() {
        return COINSPAID_USDTT_WITHDRAWAL_PAYMENT_API;
    }

    public function getPrefix() {
        return 'coinspaid_usdtt_withdrawal';
    }

    protected function getCoin() {
        return $this->getSystemInfo('currency', self::COIN_USDTT);;
    }

    public function submitWithdrawRequest($bank, $accNum, $name, $amount, $transId, $data=null) {
        $result = array('success' => false, 'message' => 'payment failed');

        if(!$this->isAllowWithdraw()) {
            $result['message'] = lang("Withdraw not allowed with this API");
            return $result;
        }

        if(!array_key_exists($bank, $this->getBankInfo())) {
            $this->CI->utils->debug_log('======================================coinspaid_usdtt withdrawal bank whose bankTypeId=[$bank] is not supported by coinspaid_usdtt');
            return array('success' => false, 'message' => 'Bank not supported by coinspaid_usdtt');
        }

        $params = $this->getWithdrawParams($bank, $accNum, $name, $amount, $transId);
        $walletAccountId = $params['walletaccount_id'];
        unset($params['walletaccount_id']);

        $withdrawal_url = $this->getSystemInfo('url').'/withdrawal/crypto';
        $this->CI->utils->debug_log('=====================coinspaid_usdtt withdrawal generatePaymentUrlForm params', $params, $withdrawal_url);

        $this->processHeaders($params);
        list($response, $response_result) = $this->submitPostForm($withdrawal_url, $params, true, $transId, true);

        $response = json_decode($response, true);
        $this->CI->utils->debug_log('=====================coinspaid_usdtt withdrawal response', $response);

        $decodedResult = $this->decodeResult($response);
        $decodedResult['response_result'] = $response_result;

        if($decodedResult['success']){
            $set_data = $response['data'];
            $this->CI->wallet_model->setExtraInfoByTransactionCode($transId, $set_data['id']);
        }
        $this->CI->utils->debug_log('========================coinspaid_usdtt withdrawal submitWithdrawRequest decoded Result', $decodedResult);

        return $decodedResult;
    }

        public function getWithdrawParams($bank, $accNum, $name, $amount, $transId) {
        $this->CI->load->model(array('wallet_model', 'player_model', 'playerbankdetails'));
        # look up bank code
        $bankInfo = $this->getBankInfo();
        $bankCode = $bankInfo[$bank];

        $order = $this->CI->wallet_model->getWalletAccountByTransactionCode($transId);
        $currency  = $this->getSystemInfo('currency');
        $targetCurrency = $this->getSystemInfo('targetCurrency');

        $params = array();
        $params['foreign_id'] = $transId;
        $params['amount']     = $this->convertAmountToCurrency($amount);
        $params['currency']   = $currency;
        $params['convert_to'] = $targetCurrency;
        $params['address']    = $accNum;
        $params['walletaccount_id'] = $order['walletAccountId'];

        $this->CI->utils->debug_log('=========================coinspaid_usdtt getWithdrawParams params', $params);
        return $params;
    }

    public function decodeResult($result) {
        if(is_array($result) && isset($result['lock']) && isset($result['msg'])) {
            $result = array('success' => false, 'message' => $result['msg'], 'lock' => true);
            return $result;
        }
        $this->utils->debug_log("=========================coinspaid_usdtt withdrawal decodeResult", $result);

        if (isset($result['data'])) {
            if($result['data']['status'] == self::WITHDRAWAL_RESULT_CODE){
                $txid    = $result['data']['foreign_id'];
                $sender_amount = $result['data']['sender_amount'];
                $bitcoin = $result['data']['receiver_amount'];
                $message = "coinspaid usdtt withdrawal successful. Txid: ". $txid .", Transfered: ".$bitcoin.", sender_amount: ".$sender_amount;

                return array('success' => true, 'message' => $message);
            }else {
                $message = "coinspaid usdtt response failed. [".json_encode($result)."]: ";
                return array('success' => false, 'message' => $message);
            }
        } else {
            if (isset($result['errors'])) {
                $message = "coinspaid usdtt response failed. [".json_encode($result)."]: ";
                return array('success' => false, 'message' => $message);
            }
            return array('success' => false, 'message' => lang("Connect coinspaid-express failed"));
        }
    }

    public function getBankInfo() {
        $bankInfo = array();
        $bankInfoArr = $this->getSystemInfo("withdrawal_bank_info");
        if(!empty($bankInfoArr)) {
            foreach($bankInfoArr as $system_bank_type_id => $bankInfoItem) {
                $bankInfo[$system_bank_type_id] = $bankInfoItem;
            }
            $this->utils->debug_log("==================getting withdrawal coinspaid_usdtt bank info from extra_info: ", $bankInfo);
        } else {
            $bankInfo = array(
                '42'  => 'usdtt',
            );
            $this->utils->debug_log("==================getting withdrawal coinspaid_usdtt bank info from code: ", $bankInfo);
        }
        return $bankInfo;
    }
}