<?php
require_once dirname(__FILE__) . '/abstract_payment_api_bitgo.php';
/**
 * BITGO
 *
 * * BITGO_ETH_PAYMENT_API, ID: 5134
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
class Payment_api_bitgo_eth extends Abstract_payment_api_bitgo {
    const WAIT = "address pending on-chain initialization";

    public function getPlatformCode() {
        return BITGO_ETH_PAYMENT_API;
    }

    public function getPrefix() {
        return 'bitgo_eth';
    }

    protected function getCoin() {
        return $this->getSystemInfo('coin', self::COIN_ETH);;
    }

    public function generatePaymentUrlForm($orderId, $playerId, $amount, $orderDateTime, $playerPromoId = null, $enabledSecondUrl = true, $bankId = null) {
        if ($this->shouldRedirect($enabledSecondUrl)) {
            $url = $this->CI->utils->getPaymentUrl($this->getSystemInfo('second_url'), $this->getPlatformCode(), $amount, $playerId, $playerPromoId, false, $bankId, $orderId);
            $result = array('success' => true, 'type' => self::REDIRECT_TYPE_URL, 'url' => $url);
            return $result;
        }

        $order     = $this->CI->sale_order->getSaleOrderById($orderId);
        $secure_id = $order->secure_id;
        $currency  = $this->getSystemInfo('currency', 'USD');

        #----Create Address----
            #api/v2/:coin/wallet/:walletId/address
            $create_address_url = $this->getSystemInfo('url').$this->coin.'/wallet/'.$this->wallet_id.'/address';

            $params = array();
            $params['chain']       = 0;
            $params['label']       = $secure_id;
            $params['lowPriority'] = false;
            $response = $this->processCurl($create_address_url, $this->token, $params, $secure_id);
            if($response){
                if(isset($response['error'])){
                    return array(
                        'success' => false,
                        'type' => self::REDIRECT_TYPE_ERROR,
                        'message' => lang('Bitgo Create Address Failed').': ['.$response['name'].']'.$response['error']
                    );
                }
                else{
                    $addressOrId = $response['id'];
                    list($crypto, $rate) = $this->CI->utils->convertCryptoCurrency($amount, $currency, strtoupper($this->coin));

                    $custom_deposit_rate = $this->CI->config->item('custom_deposit_rate');
                    $crypto      = number_format($crypto * $custom_deposit_rate, 8, '.', '');
                    $player_rate = number_format($rate   * $custom_deposit_rate, 8, '.', '');

                    $deposit_notes = $this->coin.': '.$crypto.' | '.strtoupper($this->coin).' Real Rate: '.$rate.' | Custom Deposit Rate: '.$custom_deposit_rate;
                    $this->utils->debug_log('=====================bitgo deposit_notes', $deposit_notes);
                    $this->CI->sale_order->updateExternalInfo($order->id, null, $addressOrId, $this->currencyFormat($crypto, self::WEI));
                    $this->CI->sale_order->appendNotes($order->id, $deposit_notes);

                    $handle['addressOrId'] = $addressOrId;
                    $handle['crypto'] = $crypto;
                    $handle['player_rate'] = $player_rate;
                }
            }
            else{
                return array(
                    'success' => false,
                    'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
                    'message' => lang('Connect bitgo-express failed')
                );
            }

        return $this->handlePaymentFormResponse($handle);
    }

    protected function handlePaymentFormResponse($handle) {
        $data = array();
        $button = '<button type="button" class="btn btn-primary" id="getExternalApi_btn">'.lang("Click to get address").'</button>';
        $data['Get Address ID']  = $handle['addressOrId'];
        $data['Sent to Address'] = $button;
        $data['Ether']           = $handle['crypto'];
        $data['Rate']            = $handle['player_rate'];
        $this->CI->utils->debug_log("=====================bitgo handlePaymentFormResponse params", $data);

        $collection_text_transfer = '';
        $collection_text = $this->getSystemInfo("collection_text_transfer", array(''));
        if(is_array($collection_text)){
            $collection_text_transfer = $collection_text;
        }
        $is_not_display_recharge_instructions = $this->getSystemInfo('is_not_display_recharge_instructions');

        return array(
            'success' => true,
            'type' => self::REDIRECT_TYPE_STATIC,
            'getExternalApi_btn' => true,
            'data' => $data,
            'collection_text_transfer' => $collection_text_transfer,
            'is_not_display_recharge_instructions' => $is_not_display_recharge_instructions,
        );
    }

    #player center page, click #getExternalApi_btn
    public function getExternalApiResponseByOrder($saleOrder){
        $response = $this->getWalletAddressByAddressOrId($saleOrder);
        $this->CI->utils->debug_log("=====================bitgo getExternalApiResponseByOrder response", $response);
        $success = false;
        if($response){
            if(isset($response['error'])){
                $message = lang('Bitgo Get Address Failed').': '.$response['error'];
                if($response['error'] == self::WAIT){
                    $message = lang('Wait for confirmation, please wait 30 seconds to try again');
                }
            }
            else{
                $success = true;
                $message = $response['address'];
                $this->CI->sale_order->updateExternalInfo($saleOrder->id, $response['address']);
            }
        }
        else{
            $message = lang('Connect bitgo-express failed');
        }
        return array('success' => $success, 'message' => $message);
    }

    #incase player close deposit page before getting addrress
    public function checkDepositStatus($secureId) {
        $saleOrder = $this->CI->sale_order->getSaleOrderBySecureId($secureId);
        $response = $this->getWalletAddressByAddressOrId($saleOrder);
        $this->CI->utils->debug_log("=====================bitgo checkDepositStatus response", $response);
        if($response){
            if(isset($response['error'])){
                $message = lang('Bitgo Get Address Failed').': '.$response['error'];
                if($response['error'] == self::WAIT){
                    $message = lang('Wait for confirmation, please wait 30 seconds to try again');
                }
            }
            else{
                $message = lang('External ID updated.');
            }
        }
        else{
            $message = lang('Connect bitgo-express failed');
        }
        #always false because this is not going to approve the order, just to get address
        return array('success' => false, 'message' => $message);
    }

    public function getWalletAddressByAddressOrId($saleOrder){
        #https://www.bitgo.com/api/v2/#create-wallet-address
        #creating a new Ethereum address requires a blockchain transaction
        #----Get Wallet Address----
        #api/v2/:coin/wallet/:walletId/address/:addressOrId
        $get_address_url = $this->getSystemInfo('url').$this->coin.'/wallet/'.$this->wallet_id.'/address/'.$saleOrder->bank_order_id;
        $response = $this->processCurl($get_address_url, $this->token, null, $saleOrder->secure_id, false);
        return $response;
    }
}
