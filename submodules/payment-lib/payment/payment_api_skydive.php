<?php
require_once dirname(__FILE__) . '/abstract_payment_api_skydive.php';

/**
 * skydive payment API 5783
 */
class Payment_api_skydive extends Abstract_payment_api_skydive{

    public function __construct($params = NULL){
        parent::__construct($params);

        # Populate $info with the following keys
        # url, key, account, secret, system_info
        $this->info = $this->getInfoByEnv();
    }

    # -- implementation of abstract functions --
    public function getPlatformCode(){
        return SKYDIVE_PAYMENT_API;
    }

    public function getPrefix(){
        return 'skydive';
    }

    # -- override common API functions --
    public function getSecretInfoList() {
        $secretsInfo = array('live_key', 'live_secret', 'sandbox_key', 'sandbox_secret', 'test_private_key', 'test_public_key');
        return $secretsInfo;
    }

    ## Constructs an URL so that the caller can redirect / invoke it to make payment through this API
    ## See controllers/redirect.php for detail.
    ##
    ## Retuns a hash containing these fields:
    ## array(
    ##	'success' => true,
    ##	'type' => self::REDIRECT_TYPE_FORM,  ## constants defined in abstract_payment_api.php
    ##	'url' => $info['url'],
    ##	'params' => $params,
    ##	'post' => true
    ## );
    public function generatePaymentUrlForm($orderId, $playerId, $amount, $orderDateTime, $playerPromoId = NULL, $enabledSecondUrl = TRUE, $bankId = NULL){
        $this->CI->utils->debug_log("Invoked in skydive payment API", $orderId, $playerId, $amount, $orderDateTime, $playerPromoId, $enabledSecondUrl, $bankId);

        $order = $this->CI->sale_order->getSaleOrderById($orderId);
        $username = $this->CI->player_model->getUsernameById($playerId);

        if ($this->getSystemInfo("cancel_reserv_transfer")) {
            $params = array();
            $params['username'] = $username;
            $url = $this->getSystemInfo('cancelReservTransferUrl','https://services.missilegroup.com/autodeposit/cancel_reserv_transfer');

            // $this->_custom_curl_header = ["Content-Type: application/json"];
            $response = $this->curl_del($url, $params);
            $response = json_decode($response, true);
            $this->CI->utils->debug_log('=====================skydive cancelReservTransfer api response', $response);
        }

        $callResult = $this->callReservTransferApi($username, $order);
        if (!empty($callResult['result'])) {
            $processed = true;
            $this->CI->utils->debug_log('====================skydive verifyFixedProcess callResult is success', $callResult);
        } else if(isset($callResult['error_message'])) {
            $this->CI->utils->debug_log("=====================skydive verifyFixedProcess callResult is failed", $callResult);
            return array(
                'success' => false,
                'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
                'message' => $callResult['error_code'].': '.$callResult['error_message']
            );
        }

        $new_order = $this->CI->sale_order->getSaleOrderById($orderId);

        $data = array();
        $data['Amount'] = number_format($new_order->amount, 2, '.', '');
        $data['browser_callback_url'] = $this->getOrderBrowserCallbackUrl($orderId);
        $data['server_callback_url'] = $this->getOrderServerCallbackUrl($orderId);

        if ($this->getSystemInfo("show_all", false)) {
            $allInfo = $this->getAllSystemInfo();
            foreach ($allInfo as $key => $value) {
                # add space to avoid lang key match
                $data[' '.$key] = $value;
            }
        }

        $collection_text_transfer = '';
        $collection_text = $this->getSystemInfo("collection_text_transfer", array(''));
        if(is_array($collection_text)){
            $collection_text_transfer = $collection_text;
        }

        $is_not_display_recharge_instructions = $this->getSystemInfo('is_not_display_recharge_instructions');

        $custom_bank_info = $this->getSystemInfo("custom_bank_info",[]);

        // $getBankInfoByPlayer = $this->getBankInfoByPlayer($order->direct_pay_extra_info);

        // $data['getBankInfoByPlayer'] = $getBankInfoByPlayer;

        // $this->CI->utils->debug_log('=====================skydive getBankInfoByPlayer ', $getBankInfoByPlayer);

        $default_option_value = $this->getSystemInfo("default_option_value",self::DEFAULT_BANK);

        if (!empty($custom_bank_info)) {
            foreach ($custom_bank_info as $key => $value) {
                $bank_id = $value['id'];
                $data['account name'] = $value['name'];
                $data['account no'] = $value['account_no'];
                $data['bank name'] = $value['bank']['name'];
                $data['bank code'] = $value['bank']['bank_code'];

                // if ($getBankInfoByPlayer == $data['bank code'].'-'.$data['account no']) {
                //     break;
                // }

                if ($default_option_value == $bank_id) {
                    break;
                }
            }
        }

        $url = $this->getSystemInfo("url");
        $this->submitPreprocess($data, null, $url, null , [], $order->secure_id);

        $this->CI->utils->debug_log('=====================skydive generatePaymentUrlForm data', $data, $custom_bank_info,$default_option_value);

        unset($data['browser_callback_url']);
        unset($data['server_callback_url']);
        return array(
            'success' => true,
            'type' => self::REDIRECT_TYPE_STATIC,
            'data' => $data,
            'collection_text_transfer' => $collection_text_transfer,
            'is_not_display_recharge_instructions' => $is_not_display_recharge_instructions
        );
    }

    public function curl_del($url,$params)
    {
        // $url = $this->__url.$path;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);
        // $result = json_decode($result);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $this->CI->utils->debug_log('=====================skydive cancelReservTransfer api curl_del', $result, $httpCode);

        return $result;
    }

    public function directPay($order = NULL){
        $this->CI->utils->debug_log("Invoked in skydive payment API", $order);
        return $this->returnUnimplemented();
    }

    // public function getMerchantBank(){
    //     $custom_bank_info = $this->getSystemInfo("custom_bank_info",[]);
    //     $merchantBankList = [];

    //     if (!empty($custom_bank_info)) {
    //         foreach ($custom_bank_info as $key => $value) {
    //             $data = [];
    //             $data['bankAccountNumber'] = $value['account_no'];
    //             $data['bankName'] = $value['bank']['name'];
    //             $data['bankCode'] = $value['bank']['bank_code'];
    //             $merchantBankList[] = $data;
    //         }
    //     }

    //     return $merchantBankList;
    // }

    // protected function getBankInfoByPlayer($direct_pay_extra_info){
    //     $decode_direct_pay_extra_info = json_decode($direct_pay_extra_info,true);
    //     $bankInfo = $decode_direct_pay_extra_info['player_bank_list'];
    //     return $bankInfo;
    // }

    public function getPlayerInputInfo() {
        return array(
            array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
        );
    }

    // public function getPlayerInputInfo() {
    //     return array(
    //         array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
    //         array('name' => 'player_bank_list', 'type' => 'player_bank_list', 'select_lang'=>'Please Select Bank' ,'label_lang' => 'cashier.player.bank_num' ,'list' => $this->getMerchantBank(), 'default_option_value' => $this->getSystemInfo('default_option_value')),
    //     );
    // }

    protected function configParams(&$params, $direct_pay_extra_info) {
        $this->CI->utils->debug_log('direct_pay_extra_info', $direct_pay_extra_info);
    }

    protected function processPaymentUrlForm($params) {
    }


}
