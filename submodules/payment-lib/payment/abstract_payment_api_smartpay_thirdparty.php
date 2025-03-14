<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * SMARTPAY_THIRDPARTY
 *
 * * SMARTPAY_THIRDPARTY_PAYMENT_API, ID: 5738
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://210.3.82.117:9980/api/thirdPartyDeposit
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *p
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
abstract class Abstract_payment_api_smartpay_thirdparty extends Abstract_payment_api {
    const RESULT_CODE_SUCCESS = 'SUCCEEDED';
    const RETURN_SUCCESS = "OK";
    const RETURN_FAILED = 'FAILED';
    const ORDER_STATUS_SUCCESS = 'SUCCESS';
    const ORDER_STATUS_REJECTED = 'REJECT';

    public function __construct($params = null) {
        parent::__construct($params);
        $this->_custom_curl_header = ["Content-Type: application/json"];
    }

    protected abstract function configParams(&$params, $direct_pay_extra_info);
    protected abstract function processPaymentUrlForm($params);

    public function getSecretInfoList() {
        $secretsInfo = array('live_key', 'live_secret', 'sandbox_key', 'sandbox_secret', 'api_user_name', 'api_password');
        return $secretsInfo;
    }

    public function generatePaymentUrlForm($orderId, $playerId, $amount, $orderDateTime, $playerPromoId = null, $enabledSecondUrl = true, $bankId = null) {
        if ($this->shouldRedirect($enabledSecondUrl)) {
            $url = $this->CI->utils->getPaymentUrl($this->getSystemInfo('second_url'), $this->getPlatformCode(), $amount, $playerId, $playerPromoId, false, $bankId, $orderId);
            $result = array('success' => true, 'type' => self::REDIRECT_TYPE_URL, 'url' => $url);
            return $result;
        }

        $order = $this->CI->sale_order->getSaleOrderById($orderId);
        $player = $this->CI->player->getPlayerById($playerId);

        $params = array();
        $params['txseq'] = $order->secure_id;
        $params['username'] = $player['username'];
        $params['amount'] = $this->convertAmountToCurrency($amount);

        $playerLevel = $this->getExistCodeByMappingArr($this->getSystemInfo('mapping_player_level_code_arr'),$player['levelId']);
        $dispatchLevel = $this->getExistCodeByMappingArr($this->getSystemInfo('mapping_dispatch_level_code_arr'),$player['dispatch_account_level_id']);

        $params['playerLevel'] = $playerLevel;
        $params['dispatchedGroup'] = $dispatchLevel;
        $params['brand'] = $this->getSystemInfo('brand');

        $this->configParams($params, $order->direct_pay_extra_info);
        $this->CI->utils->debug_log("=====================smartpaythirdparty generatePaymentUrlForm params", $params);
        return $this->processPaymentUrlForm($params);
    }

    protected function processPaymentUrlFormForRedirect($params) {
        $url = $this->getSystemInfo('url');
        $encrypt_params = $this->encrypt(json_encode($params));

        if(is_null($params['playerLevel'])) {
            $msg = 'player vip level is not supported by smartpaythirdparty';
            return array(
                'success' => false,
                'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
                'message' => $msg
            );
        }
        if(is_null($params['dispatchedGroup'])) {
            $msg = 'player dispatch level is not supported by smartpaythirdparty';
            return array(
                'success' => false,
                'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
                'message' => $msg
            );
        }

        $response = $this->submitPostForm($url, $encrypt_params, true, $params['txseq']);
        $this->CI->utils->debug_log('=====================smartpaythirdparty processPaymentUrlFormURL response', $response);

        $response = $this->decrypt($response);
        $decode_data = json_decode($response,true);
        $this->CI->utils->debug_log('=====================smartpaythirdparty processPaymentUrlFormURL response to json', $decode_data);

        $collection_text_transfer = '';
        $collection_text = $this->getSystemInfo("collection_text_transfer", array(''));
        if(is_array($collection_text)){
            $collection_text_transfer = $collection_text;
        }

        $msg = lang('Invalidate API response');
		if($decode_data['resultCode'] == self::RESULT_CODE_SUCCESS) {
            $data = array(
                lang('pay.bankname') => $decode_data['returnObject']['bankName'],
                lang('pay.locatn').lang('pay.bankname') => $decode_data['returnObject']['localeBankName'],
                lang('pay.payment_account_name') => $decode_data['returnObject']['accountName'],
                lang('pay.locatn').lang('pay.payment_account_name') => $decode_data['returnObject']['localeAccountName'],
                lang('pay.payment_account') => $decode_data['returnObject']['accountNo'],
                lang('Deposit Amount') => $decode_data['returnObject']['randomAmount'],
            );

			return array(
                'success' => true,
                'type' => self::REDIRECT_TYPE_STATIC,
                'data' => $data,
                'collection_text_transfer' =>  $collection_text_transfer,
            );
        }else {
            if(!empty($decode_data['resultDescription'])) {
                $msg = "[".$decode_data['resultCode']."] : ".$decode_data['resultDescription'];
            }
            return array(
                'success' => false,
                'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
                'message' => $msg
            );
        }
    }

    # Callback URI: /callback/fixed_process/<payment_id>
    public function getOrderIdFromParameters($flds) {
        $this->CI->utils->debug_log("=====================smartpaythirdparty getOrderIdFromParameters", $flds);
        if(empty($flds) || is_null($flds) || is_array($flds)){
            $raw_post_data = file_get_contents('php://input', 'r');
            $flds = $raw_post_data;
		}
        $flds = $this->decrypt($flds);
        $flds = json_decode($flds,true);
        $this->CI->utils->debug_log("=====================smartpaythirdparty getOrderIdFromParameters decrypted", $flds);

        if (isset($flds['txseq'])) {
            $this->CI->load->model(array('sale_order','wallet_model'));
            if(substr($flds['txseq'],0,1) == 'D'){
                $order = $this->CI->sale_order->getSaleOrderBySecureId($flds['txseq']);
                return $order->id;
            }else{
                return $flds['txseq'];
            }
        }
        else {
            $this->utils->debug_log('=====================smartpaythirdparty callbackOrder cannot get any order_id when getOrderIdFromParameters', $flds);
            return;
        }
    }

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
        $this->CI->utils->debug_log('========================smartpaythirdparty callbackFrom in Function callbackFrom', $params);
		if(empty($params) || is_null($params) || is_array($params)){
            $raw_post_data = file_get_contents('php://input', 'r');
            $params = $raw_post_data;
		}
        $params = $this->decrypt($params);
        $params = json_decode($params, true);
        $this->CI->utils->debug_log('========================smartpaythirdparty callbackFrom in Function callbackFrom decrypted', $params);

        $result = array('success' => false, 'next_url' => null, 'message' => lang('error.payment.failed'));

        if(substr($params['txseq'] , 0, 1) == 'W'){
            $result = $this->isWithdrawal($params, $params['txseq']);
            $this->CI->utils->debug_log('=======================smartpaythirdparty callbackFrom txseq', $params['txseq']);
            return $result;
        }

        $order = $this->CI->sale_order->getSaleOrderById($orderId);

        $processed = false;

        if($source == 'server'){
            $this->CI->utils->debug_log('========================smartpaythirdparty callbackFromServer server callbackFrom', $params);
            if (!$order || !$this->checkCallbackOrder($order, $params, $processed)) {
                return $result;
            }
        }

		# Update order payment status and balance
		$success=true;
		# Update player balance based on order status
		# if it's STATUS_SETTLED or STATUS_BROWSER_CALLBACK, put log, and ignore

        $orderStatus = $this->CI->sale_order->getSaleOrderStatusById($orderId);
        if ($orderStatus == Sale_order::STATUS_BROWSER_CALLBACK || $orderStatus == Sale_order::STATUS_SETTLED) {
            $this->CI->utils->debug_log('callbackFrom' . ucfirst($source) . ', already get callback for order:' . $order->id, $params);
            if ($source == 'server' && $order->status == Sale_order::STATUS_BROWSER_CALLBACK) {
                $this->CI->sale_order->setStatusToSettled($orderId);
            }
        } else {
            # update player balance
            $this->CI->sale_order->updateExternalInfo($order->id,
                $params['txseq'], 'Third Party Payment (No Bank Order Number)', # no info available
                null, null, $response_result_id);
            if ($source == 'browser') {
                $success = $this->CI->sale_order->browserCallbackSaleOrder($order->id, 'auto broswer callback ' . $this->getPlatformCode(), false);
            } elseif ($source == 'server') {
                if($params['status'] == self::ORDER_STATUS_REJECTED){
                    $this->CI->sale_order->declineSaleOrder($order->id, 'auto server callback declined ' . $this->getPlatformCode(), false);
                }
                else if($params['status'] == self::ORDER_STATUS_SUCCESS){
                    $this->approveSaleOrder($order->id, 'auto server callback ' . $this->getPlatformCode(), false);
                }
            }
        }

        $result['success'] = $success;
        if ($success) {
            $result['message'] = self::RETURN_SUCCESS;
        } else {
            $result['message'] = $processed ? self::RETURN_SUCCESS : self::RETURN_FAILED;
        }

        if ($source == 'browser') {
            $result['next_url'] = $this->getPlayerBackUrl();
            $result['go_success_page'] = true;
        }

		return $result;
	}

    #For Withdrawal
    public function isWithdrawal($params, $orderId){
        $result = array('success' => false, 'message' => 'Payment failed');
        $processed = false;
        $order = $this->CI->wallet_model->getWalletAccountByTransactionCode($orderId);
        if (!$this->checkCallbackOrder($order, $params, $processed)) {
            return $result;
        }

        if($params['status'] == self::ORDER_STATUS_SUCCESS) {
            $this->utils->debug_log('=====================smartpaythirdparty withdrawal payment was successful: trade ID [%s]', $params['txseq']);
            $msg = sprintf('smartpaythirdparty withdrawal was successful: trade ID [%s]',$params['txseq']);
            $this->CI->wallet_model->withdrawalAPIReturnSuccess($orderId, $msg);
            $result['success'] = true;
            $result['message'] = self::RETURN_SUCCESS;
        }else {
            $msg = sprintf('smartpaythirdparty withdrawal payment was not successful  trade ID [%s] ',$params['txseq']);
            $this->debug_log($msg, $params);
            $result['success'] = false;
            $result['message'] = $msg;
        }

        return $result;
    }

    private function checkCallbackOrder($order, $fields, &$processed = false) {
        $this->CI->utils->debug_log("=====================smartpaythirdparty checkCallbackOrder", $fields);
        $requiredFields = array(
            'txseq', 'amount', 'status'
        );

        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("=====================smartpaythirdparty checkCallbackOrder Missing parameter: [$f]", $fields);
                return false;
            }
        }

        $processed = true;

        if( substr($fields['txseq'],0,1) == 'D'){
            $check_amount = $this->convertAmountToCurrency($order->amount);
            $order_id = $order->secure_id;
        }else{
            $check_amount = $this->convertAmountToCurrency($order['amount']);
            $order_id = $order['transactionCode'];
        }

        if ($fields['amount'] != $check_amount) {
            if($this->getSystemInfo('allow_callback_amount_diff')){
                $diffAmount = abs($check_amount - floatval( $fields['amount']));
                if ($diffAmount >= 1) {
                    $this->writePaymentErrorLog("=====================smartpaythirdparty checkCallbackOrder Payment amounts ordAmt - payAmt > 1, expected [$order->amount]", $fields ,$diffAmount);
                    return false;
                }
                $this->CI->utils->debug_log("=====================smartpaythirdparty checkCallbackOrder amount not match expected [$order->amount]");
                $notes = $order->notes . " | callback diff amount, origin was: " . $check_amount;
                $this->CI->sale_order->fixOrderAmount($order->id, $fields['amount'], $notes);
            }
            else{
                $this->writePaymentErrorLog("=====================smartpaythirdparty checkCallbackOrder Payment amounts do not match, expected [$order->amount]", $fields);
                return false;
            }
        }

        if ($fields['txseq'] != $order_id) {
            $this->writePaymentErrorLog("======================smartpaythirdparty checkCallbackOrder order IDs do not match, expected [$order->secure_id]", $fields);
            return false;
        }
        # everything checked ok
        return true;
    }

	public function directPay($order = null) {
		return array('success' => false); # direct pay not supported by this API
	}

	# -- Private functions --
	/**
	 * detail: After payment is complete, the gateway will invoke this URL asynchronously
	 *
	 * @param int $orderId
	 * @return void
	 */
	private function getNotifyUrl($orderId) {
		return parent::getCallbackUrl('/callback/process/' . $this->getPlatformCode() . '/' . $orderId);
	}

	/**
	 * detail: After payment is complete, the gateway will send redirect back to this URL
	 *
	 * @param int $orderId
	 * @return void
	 */
	private function getReturnUrl($orderId) {
		return parent::getCallbackUrl('/callback/browser/success/' . $this->getPlatformCode() . '/' . $orderId);
	}

	/**
	 * detail: Format the amount value for the API
	 *
	 * @param float $amount
	 * @return float
	 */
    protected function convertAmountToCurrency($amount) {
        return number_format($amount, 2, '.', '');
    }

    # -- 加密 --
	protected function encrypt($data){
        $iv = $this->getSystemInfo('smartpaythirdparty_iv');
        $key = $this->getSystemInfo('key');

        $encrypted = openssl_encrypt($data, 'AES-128-CBC', $key, OPENSSL_RAW_DATA, $iv);
		return base64_encode($encrypted);
	}

    # -- 解密 --
    protected function decrypt($data){
        $iv = $this->getSystemInfo('smartpaythirdparty_iv');
        $key = $this->getSystemInfo('key');

        $decrypted = openssl_decrypt(base64_decode($data), 'AES-128-CBC', $key, OPENSSL_RAW_DATA, $iv);
        return $decrypted;
    }

}