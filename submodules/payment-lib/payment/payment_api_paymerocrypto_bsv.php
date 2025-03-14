<?php
require_once dirname(__FILE__) . '/abstract_payment_api_paymerocrypto.php';
/**
 * PAYMEROCRYPTO BSV
 *
 * * PAYMEROCRYPTO_BSV_PAYMENT_API, ID: 5822
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
class Payment_api_paymerocrypto_bsv extends Abstract_payment_api_paymerocrypto {

    public function getPlatformCode() {
        return PAYMEROCRYPTO_BSV_PAYMENT_API;
    }

    public function getPrefix() {
        return 'paymerocrypto_bsv';
    }

    protected function getCoin() {
        return $this->getSystemInfo('currency', self::COIN_BSV);
    }

    public function generatePaymentUrlForm($orderId, $playerId, $amount, $orderDateTime, $playerPromoId = null, $enabledSecondUrl = true, $bankId = null) {
        if ($this->shouldRedirect($enabledSecondUrl)) {
            $url = $this->CI->utils->getPaymentUrl($this->getSystemInfo('second_url'), $this->getPlatformCode(), $amount, $playerId, $playerPromoId, false, $bankId, $orderId);
            $result = array('success' => true, 'type' => self::REDIRECT_TYPE_URL, 'url' => $url);
            return $result;
        }

        // $this->_custom_curl_header = array('Content-Type: application/json');
        $order     = $this->CI->sale_order->getSaleOrderById($orderId);
        $secure_id = $order->secure_id;
        $currency  = $this->getCoin();
        $targetCurrency = $this->targetCurrency;

        #get rate
        $rate = 0;
        $crypto = 0;
        $external_order_id = null;
        $cryptoRate = $this->getCryptoRate();
        $cryptoRate = json_decode($cryptoRate);

        if (isset($cryptoRate->status)) {
            if ($cryptoRate->status == self::RETURN_SUCCESS_CODE) {
                $exchangeRate = $cryptoRate->data->exchangeRate;
                $rate = $cryptoRate->data->reverseRate;

                $this->utils->debug_log('=====================get cryptoRate bsv success rate', $exchangeRate,$rate);
            } else {
                $this->utils->debug_log('=====================get cryptoRate bsv fail');
            }
        }else{
            $this->utils->debug_log('=====================not get cryptoRate bsv');
        }

        #----Create Address----
            #https://service-api.paymero.io/v1/crypto/deposit/receiving-address
            $create_address_url = $this->getSystemInfo('url').$this->paymeny_type.'/receiving-address';

            $params = array();
            $params['currency']       = $currency;
            $params['targetCurrency'] = $targetCurrency;
            $params['cashierCurrency']= $targetCurrency;
            $params['externalId']     = $secure_id;
            $params['notifyUrl']      = $this->getNotifyUrl($orderId);
            $this->CI->utils->debug_log('=====================paymerocrypto bsv generatePaymentUrlForm params', $params);

            $response = $this->submitPostForm($create_address_url, $params, true, $secure_id);
            $response = json_decode($response, true);
            $this->CI->utils->debug_log('=====================paymerocrypto bsv response', $response);

            if($response){

                if($response['status'] == self::RETURN_SUCCESS_CODE){
                    $address = $response['data']['address'];
                    $external_order_id = $response['data']['id'];
                    $crypto = number_format($amount/$exchangeRate, 8, '.', '');
                    $deposit_notes = 'Wallet address: '.$address.' | '. $currency  .' Real Rate: '.$rate . '|' . 'Bitcoin: ' . $crypto;

                    $this->utils->debug_log('=====================paymerocrypto bsv deposit_notes', $deposit_notes);
                    $this->CI->sale_order->updateExternalInfo($order->id, $address, '', $crypto);
                    $this->CI->sale_order->appendNotes($order->id, $deposit_notes);

                    $handle['address'] = $address;
                    $handle['crypto'] = $crypto;
                    $handle['current_rate'] = $rate;
                }
                else{
                    return array(
                        'success' => false,
                        'type' => self::REDIRECT_TYPE_ERROR,
                        'message' => lang('paymerocrypto Create Address Failed').': ['.$response['status'].']'.$response['message']
                    );
                }
            }
            else{
                return array(
                    'success' => false,
                    'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
                    'message' => lang('Connect paymerocrypto-express failed')
                );
            }

        return $this->handlePaymentFormResponse($handle);
    }

    protected function handlePaymentFormResponse($handle) {
        $success = true;
        $data = array();
        $data['Sent to Address'] = $handle['address'];
        $data['Bitcoin SV']      = $handle['crypto'];
        $data['Rate']            = $handle['current_rate'];
        $this->CI->utils->debug_log("=====================paymerocrypto bsv handlePaymentFormResponse params", $data);

        $collection_text_transfer = '';
        $collection_text = $this->getSystemInfo("collection_text_transfer", array(''));
        if(is_array($collection_text)){
            $collection_text_transfer = $collection_text;
        }
        $is_not_display_recharge_instructions = $this->getSystemInfo('is_not_display_recharge_instructions');

        return array(
            'success' => $success,
            'type' => self::REDIRECT_TYPE_STATIC,
            'data' => $data,
            'collection_text_transfer' => $collection_text_transfer,
            'is_not_display_recharge_instructions' => $is_not_display_recharge_instructions
        );
    }

    # -- Private functions --
    private function getNotifyUrl($orderId) {
        return parent::getCallbackUrl('/callback/process/' . $this->getPlatformCode() . '/' . $orderId);
    }

    private function getReturnUrl($orderId) {
        return parent::getCallbackUrl('/callback/browser/success/' . $this->getPlatformCode() . '/' . $orderId);
    }
}
