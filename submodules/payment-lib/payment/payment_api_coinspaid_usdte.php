<?php
require_once dirname(__FILE__) . '/abstract_payment_api_coinspaid.php';
/**
 * coinspaid usdte
 *
 * * COINSPAID_USDTE_PAYMENT_API, ID: 6176
 * *
 * Required Fields:
 * * URL
 *
 * Field Values:
 * * URL: https://app.cryptoprocessing.com/api/v2

 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_coinspaid_usdte extends Abstract_payment_api_coinspaid {

    public function getPlatformCode() {
        return COINSPAID_USDTE_PAYMENT_API;
    }

    public function getPrefix() {
        return 'coinspaid_usdte';
    }

    protected function getCoin() {
        return $this->getSystemInfo('currency', self::COIN_USDTE);
    }

    public function generatePaymentUrlForm($orderId, $playerId, $amount, $orderDateTime, $playerPromoId = null, $enabledSecondUrl = true, $bankId = null) {
        if ($this->shouldRedirect($enabledSecondUrl)) {
            $url = $this->CI->utils->getPaymentUrl($this->getSystemInfo('second_url'), $this->getPlatformCode(), $amount, $playerId, $playerPromoId, false, $bankId, $orderId);
            $result = array('success' => true, 'type' => self::REDIRECT_TYPE_URL, 'url' => $url);
            return $result;
        }

        $order     = $this->CI->sale_order->getSaleOrderById($orderId);
        $secure_id = $order->secure_id;
        $currency  = $this->getCoin();
        $targetCurrency = $this->targetCurrency;

        $rate = 0;
        $crypto = 0;
        $exchangeRate = 0;

        #----Create Address----
            #https://app.sandbox.cryptoprocessing.com/api/v2/addresses/take
            $create_address_url = $this->getSystemInfo('url').'/addresses/take';
            $params = array();
            $params['foreign_id']     = $secure_id;
            $params['currency']       = $currency;
            $params['convert_to']     = $targetCurrency;
            $this->processHeaders($params);
            $this->CI->utils->debug_log('=====================coinspaid usdte generatePaymentUrlForm params', $params);

            $response = $this->submitPostForm($create_address_url, $params, true, $secure_id);
            $response = json_decode($response, true);
            $this->CI->utils->debug_log('=====================coinspaid usdte response', $response);

            if($response){
                if(isset($response['data'])){
                    $cryptoRate = json_decode($this->getCryptoRate($params), true);
                    $this->CI->utils->debug_log('=====================coinspaid usdte cryptoRate', $cryptoRate);
                    if (!empty($cryptoRate)) {
                        $rate = $cryptoRate['data'][0]['rate_to'];
                    }

                    $address = $response['data']['address'];
                    $params['amount'] = $amount;

                    $calculateCrypto = json_decode($this->getCryptoCalculate($params), true);
                    $this->CI->utils->debug_log('=====================coinspaid usdte calculateCrypto', $calculateCrypto);

                    if (isset($calculateCrypto['errors'])) {
                        return array(
                            'success' => false,
                            'type' => self::REDIRECT_TYPE_ERROR,
                            'message' => lang('coinspaid Calculate Crypto Failed').': ['.json_encode($calculateCrypto).']'
                        );
                    }

                    $crypto = $calculateCrypto['data']['receiver_amount'];
                    $this->CI->sale_order->createCryptoDepositOrder($orderId, $crypto, $rate, null, null, $currency);
                    $deposit_notes = 'Wallet address: '.$address.' | '. $currency  .' Real Rate: '.$rate . '|' . 'Bitcoin: ' . $crypto;
                    $this->utils->debug_log('=====================coinspaid usdte deposit_notes', $deposit_notes);
                    $this->CI->sale_order->updateExternalInfo($order->id, $address, '', $crypto);
                    $this->CI->sale_order->appendNotes($order->id, $deposit_notes);

                    $handle['address'] = $address;
                    $handle['crypto'] = $crypto;
                    $handle['current_rate'] = $rate;
                    $handle['qrcode'] = '<img src="' . QRCODEPATH . urlencode($address). '" width="200" />';
                }
                else{
                    return array(
                        'success' => false,
                        'type' => self::REDIRECT_TYPE_ERROR,
                        'message' => lang('coinspaid Create Address Failed').': ['.json_encode($response).']'
                    );
                }
            }
            else{
                return array(
                    'success' => false,
                    'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
                    'message' => lang('Connect coinspaid-express failed')
                );
            }

        return $this->handlePaymentFormResponse($handle);
    }

    protected function handlePaymentFormResponse($handle) {
        $success = true;
        $data = array();
        $data['Sent to Address']    = $handle['address'];
        $data['Tether USD (Erc20)'] = $handle['crypto'];
        $data['Rate']               = '1 '. $this->getCoin() .' ~ ' .$handle['current_rate'] . ' ' .$this->targetCurrency;
        $data['Address qrcode']     = $handle['qrcode'];
        $this->CI->utils->debug_log("=====================coinspaid usdte handlePaymentFormResponse params", $data);

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
}
