<?php
require_once dirname(__FILE__) . '/abstract_payment_api_bitgo.php';
/**
 * BITGO
 *
 * * BITGO_BTC_WITHDRAWAL_PAYMENT_API, ID: 5107
 * *
 * Required Fields:
 * * URL
 *
 * Field Values:
 * * URL: http://localhost:3080/

 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_bitgo_btc_withdrawal extends Abstract_payment_api_bitgo {
    public function getPlatformCode() {
        return BITGO_BTC_WITHDRAWAL_PAYMENT_API;
    }

    public function getPrefix() {
        return 'bitgo_btc_withdrawal';
    }

    protected function getCoin() {
        return $this->getSystemInfo('coin', self::COIN_BTC);;
    }

    public function submitWithdrawRequest($bank, $accNum, $name, $amount, $transId, $data=null) {
        $result = array('success' => false, 'message' => 'payment failed');

        if(!$this->isAllowWithdraw()) {
            $result['message'] = lang("Withdraw not allowed with this API");
            return $result;
        }

        if(!array_key_exists($bank, $this->getBankInfo())) {
            $this->CI->utils->debug_log('======================================bitgo_btc withdrawal bank whose bankTypeId=[$bank] is not supported by bitgo_btc');
            return array('success' => false, 'message' => 'Bank not supported by bitgo_btc');
        }

        $order = $this->CI->wallet_model->getWalletAccountByTransactionCode($transId);
        #----Unlock----
            $unlock_url = $this->getSystemInfo('url').'user/unlock';

            $params = array();
            $params['otp'] = $data;
            $response = $this->processCurl($unlock_url, $this->token, $params, $transId);
            if($response){
                if(isset($response['error'])){
                    $message = "Unlock Bitgo failed. [".$response['name']."]: ".$response['error'];
                    return array('success' => false, 'message' => $message);
                }
                else{
                    $expires = $response['session']['unlock']['expires'];
                }
            }
            else{
                $message = lang("Connect bitgo-express failed");
                return array('success' => false, 'message' => $message);
            }
            $this->CI->utils->debug_log('=====================bitgo_btc submitWithdrawRequest expires', $expires);

        #----Send Transaction----
            $send_transaction_url = $this->getSystemInfo('url').$this->coin.'/wallet/'.$this->wallet_id.'/sendcoins';

            $params = array();
            $params['address'] = $accNum;
            if ($this->getSystemInfo("use_realtime_currency")) {
                # code...
            }
            else{
               $params['amount'] = $this->currencyFormat($order['extra_info'], self::SATOSHI);
            }
            $params['walletPassphrase'] = $this->password;
            $response = $this->processCurl($send_transaction_url, $this->token, $params, $transId);
            $decodedResult = $this->decodeResult($response);
            if($decodedResult['success']){
                $this->CI->wallet_model->setExtraInfoByTransactionCode($transId, $response['transfer']['id']);
            }
            $this->CI->utils->debug_log('======================================bitgo_btc submitWithdrawRequest decoded Result', $decodedResult);

        return $decodedResult;
    }

    public function decodeResult($result) {
        $this->utils->debug_log("=========================bitgo_btc decodeResult json decoded", $result);

        if(isset($result['error'])){
            $message = "Bitgo btc response failed. [".$result['name']."]: ".$result['error'];
            return array('success' => false, 'message' => $message);
        }
        else if(isset($result['transfer']['txid'])) {
            $txid    = $result['transfer']['txid'];
            $bitcoin = abs($result['transfer']['value']);
            $fee     = $result['transfer']['feeString'];
            $message = "Bitgo btc withdrawal successful. Txid: ". $txid .", Transfered: ".$bitcoin."/satoshis (Incl. ".$fee."/satoshis Fee)";

            return array('success' => true, 'message' => $message);
        }

        return array('success' => false, 'message' => lang("Connect bitgo-express failed"));
    }

    public function getBankInfo() {
        $bankInfo = array();
        $bankInfoArr = $this->getSystemInfo("bitgo_btc_bank_info");
        if(!empty($bankInfoArr)) {
            foreach($bankInfoArr as $system_bank_type_id => $bankInfoItem) {
                $bankInfo[$system_bank_type_id] = $bankInfoItem;
            }
            $this->utils->debug_log("==================getting bitgo_btc bank info from extra_info: ", $bankInfo);
        } else {
            $bankInfo = array(
                '46'  => 'BTC',
            );
            $this->utils->debug_log("=======================getting bitgo_btc bank info from code: ", $bankInfo);
        }
        return $bankInfo;
    }
}