<?php
require_once dirname(__FILE__) . '/abstract_payment_api_bibao.php';
/**
 * BIBAO
 *
 * * BIBAO_DC_PAYMENT_API, ID: 5176
 * *
 * Required Fields:
 * * URL
 *
 * Field Values:
 * * URL: http://opoutox.gosafepp.com/api/

 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_bibao_dc extends Abstract_payment_api_bibao {

    public function getPlatformCode() {
        return BIBAO_DC_PAYMENT_API;
    }

    public function getPrefix() {
        return 'bibao_dc';
    }

    protected function getCoincode() {
        return $this->getSystemInfo('coincode', self::COIN_DC);
    }

    public function generatePaymentUrlForm($orderId, $playerId, $amount, $orderDateTime, $playerPromoId = null, $enabledSecondUrl = true, $bankId = null) {
        if ($this->shouldRedirect($enabledSecondUrl)) {
            $url = $this->CI->utils->getPaymentUrl($this->getSystemInfo('second_url'), $this->getPlatformCode(), $amount, $playerId, $playerPromoId, false, $bankId, $orderId);
            $result = array('success' => true, 'type' => self::REDIRECT_TYPE_URL, 'url' => $url);
            return $result;
        }

        $order     = $this->CI->sale_order->getSaleOrderById($orderId);
        $secure_id = $order->secure_id;
        $this->CI->load->model('player');
        $player    = $this->CI->player->getPlayerById($playerId);
        $username  = $player['username'];

        #----Create AddUser----
            #api url/MerCode/coin/AddUser
            $create_adduser_url = $this->getSystemInfo('url').$this->mercode.'/coin/AddUser';

            $params = array();
            $params['MerCode']   = $this->mercode;
            $params['Timestamp'] = $this->getMillisecond();
            $params['UserName']  = $username;
            $formData = $this->buildFormData($params);
            $param = $this->desEncrypt($formData,$this->deskey);
            $key   = $this->md5KeyB($params,$this->keyA,$this->keyB,$this->keyC,false);
            $postData = [
                "param"=>$param,
                "key"=>$key
            ];
            $response = $this->submitPostForm($create_adduser_url, $postData, false, $secure_id);
            $decodeData = json_decode($response,true);
            $this->utils->debug_log('=====================Bibao submitPostForm create_adduser_url response', $decodeData);
            if($decodeData['Success']){
                #----Get Address----
                #api url/MerCode/coin/GetAddress
                $create_getaddress_url = $this->getSystemInfo('url').$this->mercode.'/coin/GetAddress';
                
                $data = array();
                $data['MerCode']   = $this->mercode;
                $data['Timestamp'] = $this->getMillisecond();
                $data['UserType']  = $this->getSystemInfo('UserType', '1');
                $data['UserName']  = $username;
                $data['CoinCode']  = $this->coincode;
                $this->utils->debug_log('================================================= Bibao data', $data);
                $formData = $this->buildFormData($data);
                $param = $this->desEncrypt($formData,$this->deskey);
                $key   = $this->md5KeyB($data,$this->keyA,$this->keyB,$this->keyC,true);
                $postData = [
                    "param"=>$param,
                    "key"=>$key
                ];

                $response_address = $this->submitPostForm($create_getaddress_url, $postData, false, $secure_id);
                $decodeData = json_decode($response_address,true);
                $this->utils->debug_log('=====================Bibao submitPostForm create_getaddress_url response_address', $decodeData);
                if(!$decodeData['Success']){
                    return array(
                        'success' => false,
                        'type' => self::REDIRECT_TYPE_ERROR,
                        'message' => lang('Bibao Create GetAddress Failed').': ['.$decodeData['Message'].']'.$decodeData['Success']
                    );
                }else{
                    $coincode = $decodeData['Data']['CoinCode'];
                    $address  = $decodeData['Data']['Address'];

                    $deposit_notes = ' coincode: '.$coincode.' | address: '.$address;
                    $this->utils->debug_log('=====================Bibao deposit_notes', $deposit_notes);

                    $handle['Address'] = $address;
                    $handle['CoinCode'] = $coincode;
                }
            }
            else{
                return array(
                    'success' => false,
                    'type' => self::REDIRECT_TYPE_ERROR,
                    'message' => lang('Bibao Create AddUser Failed').': ['.$decodeData['Message'].']'.$decodeData['Success']
                );
            }

        return $this->handlePaymentFormResponse($handle);
    }

    protected function handlePaymentFormResponse($handle) {
        $success = true;
        $data = array();
        $data['Sent to Address'] = $handle['Address'];
        $data['CoinCode']        = $handle['CoinCode'];
        // $data['Rate']            = $handle['player_rate'];
        $this->CI->utils->debug_log("=====================Bibao handlePaymentFormResponse params", $data);

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
