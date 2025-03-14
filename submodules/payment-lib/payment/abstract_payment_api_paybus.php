<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * paybus
 * * PAYBUS_PAYMENT_API, ID: 6336
 * * PAYBUS_ZPAYS_PIX_PAYMENT_API, ID: 6337
 * * PAYBUS_MOCKPAY1_PIX_WITHDRAWAL_PAYMENT_API, ID: 6338
 *
 *
 * Field Values:
 * * URL: https://pay2-open.kyriandev.com/payment/deposit
 * * Account: ## User ID ##
 * * Key: ## Secret Key ##
 *
 * @property sale_order $sale_order
 * @property wallet_model $wallet_model
 *
 * @category Payment
 * @copyright 2013-2023 tot
 */
abstract class Abstract_payment_api_paybus extends Abstract_payment_api {
    const CHANNEL_ZPAYS_PIX    = 'zpays.pix';
    const CHANNEL_MOCKPAY1_PIX = 'mockpay1.pix';
    const CHANNEL_BCPAY_PIX    = 'bcpay.pix';
    const CHANNEL_WEDPAY_BANK_QR = 'wedpay.bank-qr';
    const CHANNEL_WEDPAY_GATEWAY = 'wedpay.gateway';
    const CHANNEL_WEDPAY_WITHDRAWAL = 'wedpay.bank';
    const CHANNEL_WWWPAGO_PIX  = 'wwwpago.pix';
    const CHANNEL_HPAY_GCASH    = 'hpay.gcash';
    const CHANNEL_HPAY_PAYMAYA    = 'hpay.paymaya';
    const CHANNEL_CLOUDPAY_GASH    = 'cloudpay.gcash';
    const CHANNEL_CLOUDPAY_PAYMAYA = 'cloudpay.paymaya';
    const CHANNEL_BIOPAY_VNBANK = 'biopay.vnbank';
    const CHANNEL_BIOPAY_VNBANKQR = 'biopay.vnbankqr';
    const CHANNEL_BIOPAY_VNDIRECT = 'biopay.vndirect';
    const CHANNEL_BIOPAY_VNMOMO = 'biopay.vnmomo';
    const CHANNEL_BIOPAY_VNVTPAY = 'biopay.vnvtpay';
    const CHANNEL_BIOPAY_VNZALO = 'biopay.vnzalo';
    const CHANNEL_BIOPAY_BANK_WITHDRAWAL = 'biopay.bank';
    const CHANNEL_WINPAY_PIX    = 'winpay.pix';
    const CHANNEL_ASIAPAY_BANKQR = 'asiapay.bank_qr';
    const CHANNEL_ASIAPAY_BANKTRANSFER = 'asiapay.bank_transfer';
    const CHANNEL_ASIAPAY_CARDPC = 'asiapay.card_pc';
    const CHANNEL_ASIAPAY_MOMOQR = 'asiapay.momo_qr';
    const CHANNEL_ASIAPAY_QRPRO = 'asiapay.QR_PRO';
    const CHANNEL_ASIAPAY_USDTQR = 'asiapay.usdt_qr';
    const CHANNEL_ASIAPAY_VIETTELQR = 'asiapay.viettel_qr';
    const CHANNEL_ASIAPAY_ZALOQR = 'asiapay.zalo_qr';
    const CHANNEL_ONEFORPAY_QRCODE = 'oneforpay.QRCODE';
    const CHANNEL_ONEFORPAY_BANK = 'oneforpay.BANK';
    const CHANNEL_ONEFORPAY_WALLET = 'oneforpay.WALLET';
    const CHANNEL_ONEFORPAY_BANK_WITHDRAWAL = 'oneforpay.bank';
    const CHANNEL_ONEDPAY_BMR = 'onedpay.bmr';
    const CHANNEL_ONEDPAY_BQR = 'onedpay.bqr';
    const CHANNEL_ONEDPAY_BTR = 'onedpay.btr';
    const CHANNEL_ONEDPAY_MOMO = 'onedpay.momo';
    const CHANNEL_ONEDPAY_ZALO = 'onedpay.zalo';
    const CHANNEL_ONEDPAY_VTTL = 'onedpay.vttl';
    const CHANNEL_ONEDPAY_WD_WITHDRAWAL = 'onedpay.wd';
    const CHANNEL_MTPAY_QRCODE = 'mtpay.bank_qr';
    const CHANNEL_MTPAY_BANK_TRANSFER = 'mtpay.bank_transfer';
    const CHANNEL_MTPAY_MOMO = 'mtpay.momo';
    const CHANNEL_MTPAY_VTMONEY = 'mtpay.vtmoney';
    const CHANNEL_MTPAY_ZALO = 'mtpay.zalo';
    const CHANNEL_MTPAY_BANK_WITHDRAWAL = 'mtpay.bank';
    const CHANNEL_MEGAPAY_CACA = 'megapay.caca';
    const CHANNEL_MEGAPAY_QRIS = 'megapay.qris';
    const CHANNEL_ONEWALLET_QRPAY = 'onewallet.qrpay';
    const CHANNEL_ONEWALLET_TRUEWALLET = 'onewallet.truewallet';
    const CHANNEL_ONEWALLET_BANK_WITHDRAWAL = 'onewallet.bank';
    const CHANNEL_XFTPAY_USDT = 'xftpay.usdt';
    const CHANNEL_XFTPAY_USDT_WITHDRAWAL = 'xftpay.usdt';
    const CHANNEL_DEPAY_GCASH_DIRECT = 'depay.gcash_direct';
    const CHANNEL_DEPAY_GCASH_QR = 'depay.gcash_qr';
    const CHANNEL_DEPAY_MAYA_DIRECT = 'depay.maya_direct';
    const CHANNEL_DEPAY_GCASH_WITHDRAWAL = 'depay.gcash';
    const CHANNEL_DEPAY_BANK_WITHDRAWAL = 'depay.bank';
    const CHANNEL_GOLDPAY_GCASH_DIRECT = 'goldpay.gcash_direct';
    const CHANNEL_GOLDPAY_GCASH_QR = 'goldpay.gcash_qr';
    const CHANNEL_GOLDPAY_MAYA = 'goldpay.maya';
    const CHANNEL_GOLDPAY_GCASH_WITHDRAWAL = 'goldpay.gcash';
    const CHANNEL_GOLDPAY_MAYA_WITHDRAWAL = 'goldpay.maya';
    const CHANNEL_ASPAY_AMUSINO_VERCEL_APP = 'aspay.amusino_vercel_app';
    const CHANNEL_ASPAY_BANK_WITHDRAWAL = 'aspay.bank';
    const CHANNEL_PHPGO_GCASH_DIRECT = 'phpgo.gcash_direct';
    const CHANNEL_PHPGO_GCASH_QR = 'phpgo.gcash_qr';
    const CHANNEL_PHPGO_MAYA_DIRECT = 'phpgo.maya_direct';
    const CHANNEL_PHPGO_BANK_WITHDRAWAL = 'phpgo.bank';
    const CHANNEL_PHPGO_GCASH_WITHDRAWAL = 'phpgo.gcash';
    const CHANNEL_DGMAYA = 'dgmaya.maya';
    const CHANNEL_DGMAYA_WITHDRAWAL = 'dgmaya.bank';
    const CHANNEL_KZPAY_DEPOSIT = 'kzpay.deposit';
    const CHANNEL_KZPAY_WITHDRAWAL = 'kzpay.withdraw';
    const CHANNEL_MBMPAY_VNCARD = 'mbmpay.vncard';
    const CHANNEL_MBMPAY_VNCARD_WITHDRAWAL = 'mbmpay.vncard';
    const CHANNEL_HEROPAY_BANK_8000 = 'heropay.bank_8000';
    const CHANNEL_HEROPAY_BANK_8004 = 'heropay.bank_8004';
    const CHANNEL_HEROPAY_BANK_WITHDRAWAL = 'heropay.bank';
    const CHANNEL_SPAY_PROMPTPAY = 'spay.promptpay';
    const CHANNEL_SPAY_TRUEMONEY = 'spay.truemoney';
    const CHANNEL_SPAY_P2PDEPOSIT = 'spay.p2pdeposit';
    const CHANNEL_SPAY_WITHDRAWAL = 'spay.bank';
    const CHANNEL_PAYGA_GCASH = 'payga.gcash';
    const CHANNEL_PAYGA_PAYMAYA = 'payga.paymaya';
    const CHANNEL_PAYGA_GRABPAY = 'payga.grabpay';
    const CHANNEL_PAYGA_PHQR = 'payga.phqr';
    const CHANNEL_PAYGA_GCASH_WITHDRAWAL = 'payga.gcash';
    const CHANNEL_PAYGA_PAYMAYA_WITHDRAWAL = 'payga.paymaya';
    const CHANNEL_SUNPAY_BANKQR = 'sunpay.bank_qr';
    const CHANNEL_SUNPAY_BANKTRANSFER = 'sunpay.bank_transfer';
    const CHANNEL_SUNPAY_MOMOQR = 'sunpay.momo_qr';
    const CHANNEL_SUNPAY_QRSUN = 'sunpay.QR_SUN';
    const CHANNEL_SUNPAY_VIETTELQR = 'sunpay.viettel_qr';
    const CHANNEL_SUNPAY_ZALOQR = 'sunpay.zalo_qr';
    const CHANNEL_SUNPAY_BANK_WITHDRAWAL = 'sunpay.bank';
    const CHANNEL_PANPAY_BANK = 'panpay.bank';
    const CHANNEL_PANPAY_WALLET = 'panpay.wallet';
    const CHANNEL_PANPAY_BANK_WITHDRAWAL = 'panpay.bank';
    const CHANNEL_HEROPAY_BANK_8002 = 'heropay.bank_8002';
    const CHANNEL_NETZPAY_QRIS = 'netzpay.QRIS';

    const REPONSE_CODE_SUCCESS = 10;
    const RETURN_SUCCESS_CODE  = 'OK';
    const CALLBACK_SUCCESS     = [20,21];

    public function __construct($params = null) {
        parent::__construct($params);
        $this->_custom_curl_header = array('Content-Type:application/json');
        $this->sale_order = $this->CI->sale_order;
    }

    # Implement these to specify pay type
    protected abstract function configParams(&$params, $direct_pay_extra_info);
    protected abstract function processPaymentUrlForm($params);

    public function generatePaymentUrlForm($orderId, $playerId, $amount, $orderDateTime, $playerPromoId = null, $enabledSecondUrl = true, $bankId = null) {
        if ($this->shouldRedirect($enabledSecondUrl)) {
            $url = $this->CI->utils->getPaymentUrl($this->getSystemInfo('second_url'), $this->getPlatformCode(), $amount, $playerId, $playerPromoId, false, $bankId, $orderId);
            $result = array('success' => true, 'type' => self::REDIRECT_TYPE_URL, 'url' => $url);
            return $result;
        }

        $order = $this->sale_order->getSaleOrderById($orderId);
        $playerDetails = $this->CI->player_model->getPlayerDetails($playerId);

        $params = array();
        $params['client_id']    = $order->secure_id;
        $params['amount']       = (float)$this->convertAmountToCurrency($amount);
        $params['playerDetails'] = $playerDetails;

        //OGP-33204
        if ($this->getSystemInfo('input_with_callbackurl')) {
            $params['callback_url'] = $this->getNotifyUrl($orderId);
        }
        $this->configParams($params, $order->direct_pay_extra_info);

        unset($params['playerDetails']);

        if ($this->getSystemInfo('binding_id')) {
            $params['binding_id'] = $this->getSystemInfo('binding_id');
        }

        $params['sale_order_id']     = $orderId;
        $params['callback_url'] = $this->getNotifyUrl($orderId);

        $this->CI->utils->debug_log("=====================paybus generatePaymentUrlForm", $params);

        return $this->processPaymentUrlForm($params);
    }

    //get token

    public function getPaymentToken(){
        $tokenUrl = $this->getSystemInfo('token_url');
        $merchantCode = $this->getSystemInfo("account");
        $timestamp= time();

        $params['merchant_code'] = $merchantCode;
        $params['timestamp']     = $timestamp;

        $requestPath = 'GET /token?merchant_code=' .$merchantCode . '&timestamp=' . $timestamp;
        $requestBody = '';
        $sign = $this->sign($requestPath, $requestBody);

        $this->CI->utils->debug_log('========================================paybus getPaymentToken sign', $sign);

        $this->_custom_curl_header = array(
            'x-sign:'. $sign,
            'Content-Type:application/json'
        );

        return $this->submitGetForm($tokenUrl, $params);
    }

    protected function processPaymentUrlFormPost($params) {
        $sale_order_id = $params['sale_order_id'];
        unset($params['sale_order_id']);
        $token = $this->getPaymentToken();
        $token = json_decode($token,true);
        $this->CI->utils->debug_log('========================================paybus processPaymentUrlFormPost token', $token);

        $requestPath = 'POST /payment/deposit';
        $requestBody = json_encode($params);
        $sign = $this->sign($requestPath, $requestBody);

        if(empty($token['token'])){
            return array(
                'success' => false,
                'type' => self::REDIRECT_TYPE_ERROR,
                'message' => lang('Invalidate API response')
            );
        }
        $this->_custom_curl_header = array(
            'x-token:'.$token['token'],
            'x-sign:'. $sign,
            'Content-Type:application/json'
        );

        $response = $this->submitPostForm($this->getSystemInfo('url'), $params, true, $params['client_id']);
        $response = json_decode($response,true);
        $this->CI->utils->debug_log('========================================paybus processPaymentUrlFormPost response json to array', $response);

        $msg = lang('Invalidate API response');
        if( isset($response['order_status']) && $response['order_status'] == self::REPONSE_CODE_SUCCESS ){

            if(isset($response['order_id'])){
                $order_id = $response['order_id'];
                $this->updateSaleOrderWithPaybusOrderId($sale_order_id,$order_id);
            }

            list($externalId, $paybusID) = $this->getSaleOrderExternalId($response);
            if(isset($externalId)){
                $this->sale_order->updateExternalInfo($sale_order_id, $externalId);
            }
            if(isset($order_id) && method_exists($this->sale_order, 'updatePaybusOrderId')){
                $this->sale_order->updatePaybusOrderId($sale_order_id, $paybusID);
            }

            if(isset($response['deposit_location']['url']) && !empty($response['deposit_location']['url'])){
                return array(
                    'success' => true,
                    'type' => self::REDIRECT_TYPE_URL,
                    'url' => $response['deposit_location']['url'],
                    'disableIframe' => $this->getSystemInfo('disable_iframe')
                );
            }else{
                return array(
                    'success' => false,
                    'type' => self::REDIRECT_TYPE_ERROR,
                    'message' => $msg
                );
            }
        }else {

            if (isset($response['is_success'])) {
                $msg = $response['error']['message'];
            }

            if(isset($response['extra_message']) && !empty($response['extra_message'])) {
                $msg = $response['extra_message'];
            }
            return array(
                'success' => false,
                'type' => self::REDIRECT_TYPE_ERROR,
                'message' => $msg
            );
        }
    }

    ## This will be called when the payment is async, API server calls our callback page
    ## When that happens, we perform verifications and necessary database updates to mark the payment as successful
    ## Reference: sample code, callback.php
    public function callbackFromServer($orderId, $params) {
        $response_result_id = parent::callbackFromServer($orderId, $params);
        return $this->callbackFrom('server', $orderId, $params, $response_result_id);
    }

    ## This will be called when user redirects back to our page from payment API
    public function callbackFromBrowser($orderId, $params) {
        $response_result_id = parent::callbackFromBrowser($orderId, $params);
        return $this->callbackFrom('browser', $orderId, $params, $response_result_id);
    }

    # $source can be 'server' or 'browser'
    private function callbackFrom($source, $orderId, $params, $response_result_id) {
        $result = array('success' => false, 'next_url' => null, 'message' => lang('error.payment.failed'));
        $order = $this->sale_order->getSaleOrderById($orderId);
        $processed = false;

        $this->CI->utils->debug_log("=====================paybus callbackFrom $source params", $params);

        if(empty($params)){
            $raw_post_data = file_get_contents('php://input', 'r');
            $this->CI->utils->debug_log("========================paybus raw_post_data", $raw_post_data);
            $params = json_decode($raw_post_data, true);
            $this->CI->utils->debug_log("========================paybus json_decode params", $params);
        }

        if($source == 'server' ){
            if (!$order || !$this->checkCallbackOrder($order, $params, $processed)) {
                return $result;
            }
        }

        # Update order payment status and balance
        $success = true;

        # Update player balance based on order status
        # if it's STATUS_SETTLED or STATUS_BROWSER_CALLBACK, put log, and ignore
        $orderStatus = $this->sale_order->getSaleOrderStatusById($orderId);
        if ($orderStatus == Sale_order::STATUS_BROWSER_CALLBACK || $orderStatus == Sale_order::STATUS_SETTLED) {
            $this->CI->utils->debug_log('callbackFrom' . ucfirst($source) . ', already get callback for order:' . $order->id, $params);
            if ($source == 'server' && $order->status == Sale_order::STATUS_BROWSER_CALLBACK) {
                $this->sale_order->setStatusToSettled($orderId);
            }
        } else {
            # update player balance
            $this->sale_order->updateExternalInfo($order->id, null, '', null, null, $response_result_id);
            if ($source == 'browser') {
                $success = $this->sale_order->browserCallbackSaleOrder($order->id, 'auto broswer callback ' . $this->getPlatformCode(), false);
            } elseif ($source == 'server') {
                $this->approveSaleOrder($order->id, 'auto server callback ' . $this->getPlatformCode(), false);
            }
        }

        $result['success'] = $success;
        if ($processed) {
            $result['message'] = self::RETURN_SUCCESS_CODE;
        }

        if ($source == 'browser') {
            $result['next_url'] = $this->getPlayerBackUrl();
            $result['go_success_page'] = true;
        }

        return $result;
    }

    ## Validates whether the callback from API contains valid info and matches with the order
    ## Reference: code sample, callback.php
    private function checkCallbackOrder($order, $fields, &$processed = false) {
        $requiredFields = array(
            'client_id', 'order_id', 'order_status', 'amount', 'platform_callback_amount'
        );

        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("=====================paybus checkCallbackOrder Missing parameter: [$f]", $fields);
                return false;
            }
        }

        $headers = $this->CI->input->request_headers();
        $this->CI->utils->debug_log("=====================paybus checkCallbackOrder headers", $headers);
        $requestPath = 'POST '.'/callback/process/' . $this->getPlatformCode() . '/' . $order->id;
        $requestBody = json_encode($fields);

        if(!isset($headers['X-Sign'])) {
            $this->writePaymentErrorLog('=====================paybus checkCallbackOrder X-Sign not found', null);
            return false;
        } 

        # is signature authentic?
        $callbackSign = $headers['X-Sign'];
        if (!$this->validateSign($requestPath, $requestBody, $callbackSign)) {
            $this->writePaymentErrorLog('=====================paybus checkCallbackOrder Signature Error', $callbackSign);
            return false;
        }

        $callbackSuccess = !empty($this->getSystemInfo('callback_success')) ? $this->getSystemInfo('callback_success') : self::CALLBACK_SUCCESS;

        if (!in_array($fields['order_status'], $callbackSuccess)) {
            $base_msg = !empty($fields['extra_message'])?$fields['extra_message']:lang('checkCallbackOrder Payment status is not success');
            $this->writePaymentErrorLog("======================paybus ".$base_msg, $fields);
            return false;
        }

        $amount = $this->convertAmountToCurrency($order->amount);
        $callbackAmount = $this->getSystemInfo('use_platform_callback_amount', false) ? $fields['platform_callback_amount'] : $fields['amount'];
        $this->CI->utils->debug_log("=====================paybus checkCallbackOrder amount", $amount, 'callbackAmount',$callbackAmount);

        if ($callbackAmount != $amount) {
            if ($this->getSystemInfo('allow_callback_amount_diff')) {
                $percentage = $this->getSystemInfo('diff_amount_percentage', null);
                $limitAmount = $this->getSystemInfo('diff_limit_amount', null);

                $percentageAmt = !empty($percentage) ? $amount * ($percentage / 100) : null;
                $diffAmtPercentage = !empty($percentageAmt) ? abs($amount - $callbackAmount) : null;

                $this->CI->utils->debug_log("=====================paybus checkCallbackOrder amount details", 'percentage', $percentage, 'limitAmount', $limitAmount, 'percentageAmt', $percentageAmt, 'diffAmtPercentage', $diffAmtPercentage);

                if ($percentageAmt !== null && $diffAmtPercentage > $percentageAmt) {
                    $this->writePaymentErrorLog("=====================paybus checkCallbackOrder Payment amounts ordAmt - payAmt > $percentage Percentage, expected [$amount] callbackAmount [$callbackAmount] diffAmtPercentage [$diffAmtPercentage]", $fields);
                    return false;
                }

                $diffAmount = abs($amount - $callbackAmount);
                if ($limitAmount !== null && $diffAmount >= $limitAmount) {
                    $this->writePaymentErrorLog("=====================paybus checkCallbackOrder Payment amounts ordAmt - payAmt > limit $limitAmount, expected [$amount] callbackAmount [$callbackAmount] diffAmount [$diffAmount]", $fields);
                    return false;
                }

                if ($this->getSystemInfo('convert_callback_diff_amount')) {
                    $callbackAmount = $callbackAmount / $this->getSystemInfo('convert_multiplier', 1);
                }

                $notes = $order->notes . " | callback diff amount, origin was: " . $amount;
                $this->CI->sale_order->fixOrderAmount($order->id, $callbackAmount, $notes);
            } else {
                $this->writePaymentErrorLog("======================paybus checkCallbackOrder amount not match expected [$amount] callback amount [$callbackAmount]", $fields);
                return false;
            }
        }

        if ($fields['client_id'] != $order->secure_id) {
            $this->writePaymentErrorLog("======================paybus checkCallbackOrder order IDs do not match, expected [$order->secure_id]", $fields);
            return false;
        }

        $processed = true; # processed is set to true once the signature verification pass

        # everything checked ok
        return true;
    }

    public function directPay($order = null) {
        return array('success' => false); # direct pay not supported by this API
    }

    # -- signatures --
    # Reference: PHP Demo
    public function sign($requestPath, $requestBody) {
        $signStr = $requestPath . "\n" . $requestBody;
        $this->CI->utils->debug_log('========================================paybus request signStr', $signStr);

        openssl_sign($signStr, $sign_info, $this->getPrivKey(), OPENSSL_ALGO_SHA256);
		$sign = base64_encode($sign_info);
        return $sign;
    }

    public function validateSign($requestPath, $requestBody, $callback_sign) {
        $signStr = $requestPath . "\n" . $requestBody;

        $this->CI->utils->debug_log('========================================paybus callback signStr', $signStr);
        $pubkey = $this->getPubKey();
        if(empty($pubkey)){
            $this->writePaymentErrorLog("=====================paybus validateSign Public key not found", null);
            return false;
        }
        $sign = base64_decode($callback_sign);
		$valid = openssl_verify($signStr, $sign, $this->getPubKey(), OPENSSL_ALGO_SHA256);
		return $valid == 1 ? true : false;
    }

    # -- Private functions --
    # After payment is complete, the gateway will invoke this URL asynchronously
    private function getNotifyUrl($orderId) {
        return parent::getCallbackUrl('/callback/process/' . $this->getPlatformCode() . '/' . $orderId);
    }

    ## After payment is complete, the gateway will send redirect back to this URL
    private function getReturnUrl($orderId) {
        return parent::getCallbackUrl('/callback/browser/success/' . $this->getPlatformCode() . '/' . $orderId);
    }

    ## Format the amount value for the API
    protected function convertAmountToCurrency($amount) {
        $convert_multiplier = $this->getSystemInfo('convert_multiplier', 1);
        return number_format($amount * $convert_multiplier, 2, '.', '');
    }

    private function getPubKey() {
        $paybus_pub_key = $this->getSystemInfo('paybus_pub_key');
        if(empty($paybus_pub_key)){
            return null;
        }
        $pub_key = '-----BEGIN PUBLIC KEY-----' . PHP_EOL . chunk_split($paybus_pub_key, 64, PHP_EOL) . '-----END PUBLIC KEY-----' . PHP_EOL;
        return openssl_get_publickey($pub_key);
    }

    private function getPrivKey() {
        $paybus_priv_key = $this->getSystemInfo('paybus_priv_key');
        $priv_key = '-----BEGIN RSA PRIVATE KEY-----' . PHP_EOL . chunk_split($paybus_priv_key, 64, PHP_EOL) . '-----END RSA PRIVATE KEY-----' . PHP_EOL;
        return openssl_get_privatekey($priv_key);
    }

    /**
     * generateFullName
     * return trim string
     * @param mixed $inputNameArr ["firstname", "lastname" ...] 
     * @return string
     */
    protected function generateFullName($inputNameArr){
        $full_name = '';
        $outputNameArr = [];
        foreach($inputNameArr as $key=>$item){
            if(empty($item)){
                continue;
            }
            $outputNameArr[] = trim($item);
        }
        $full_name = join(' ', $outputNameArr);
        return $full_name;
    }
    protected function updateSaleOrderWithPaybusOrderId($id,$order_id){
        $sale_ordr_direct_pay_extra_info=$this->sale_order->getSaleOrdrDirectPayExtraInfoById($id);
        $direct_pay_extra_info=json_decode($sale_ordr_direct_pay_extra_info,true);
        $direct_pay_extra_info['paybus_order_id'] = $order_id;
        $updateDirectPayExtraInfo = json_encode($direct_pay_extra_info);
        $this->sale_order->updateSaleOrderDirectPayExtraInfoById($id, $updateDirectPayExtraInfo);
    }
    protected function updateWalletaccountExtraInfo($transactionCode,$order_id,$platform_id){
        $externalId = null;
        $paybusID = null;
        $this->CI->load->model('wallet_model');
        $walletaccountId=$this->CI->wallet_model->getWalletaccountIdByTransactionCode($transactionCode);
        $getWalletAccountObject=$this->CI->wallet_model->getWalletAccountObject($walletaccountId);
        $extraInfo=json_decode($getWalletAccountObject['extra_info'],true);

        if(is_array($extraInfo)){
            $extraInfo['paybus_order_id'] = $order_id;
            $extraInfo['platform_id']=$platform_id;
        }else{
            $newExtraInfo=array();
            if(!empty($extraInfo)&&$extraInfo!==null){
                $newExtraInfo['origin']=$extraInfo;
            }
            $newExtraInfo['paybus_order_id'] = $order_id;
            $newExtraInfo['platform_id']=$platform_id;
            $extraInfo=$newExtraInfo;
        }

        $extraInfoStr=json_encode($extraInfo);

        $this->CI->wallet_model->setExtraInfoByTransactionCode($transactionCode, $extraInfoStr);
        
        //set withdrawal external id
        if(!empty($platform_id)){
            $externalId = $platform_id;
        } else if(!empty($order_id)){
            $externalId = $order_id;
        }

        if(!empty($order_id)){
            $paybusID = $order_id;
        }
        $this->CI->wallet_model->setExternalOrderIdByTransactionCode($transactionCode, $externalId, $paybusID);
    }
    private function getSaleOrderExternalId($response) {
        $externalId = null;
        $paybusID = null;
        if(!empty($response['platform_id'])){
            $externalId = $response['platform_id'];
        } else if(!empty($response['order_id'])){
            $externalId = $response['order_id'];
        }

        if (!empty($response['order_id'])) {
            $paybusID = $response['order_id'];
        }
        return [$externalId, $paybusID];
    }

}
