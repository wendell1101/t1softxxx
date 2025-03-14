<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';
/**
 * beamo
 *
 * * BEAMO_PAYMENT_API, ID: 6204
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://developer.beamo.com/cn/reference/checkout-redirect.html
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
abstract class Abstract_payment_api_beamo extends Abstract_payment_api {

    const CURRENCY = "USD";
    const EXPIRE_TIME  = 1800;

    const DEPOSIT_CRYPTO_TYPE = ["TRANSFER_CRYPTO"];
    const DEPOSIT_CRYPTO_WALLETS_TYPE = ["METAMASK", "COINBASE", "WALLET_CONNECT", "PHANTOM", "SOLFLARE", "GLOW", "SOLLET", "SOLONG"];
    const DEPOSIT_CRYPTO_CREDIT_CARD_TYPE =["MOONPAY"];
    const DEPOSIT_UPI_TYPE = ["ONRAMP_MONEY", "ONMETA_IN"];

    const RESULT_PRODUCT_PAGE_SUCCESS_CODE = "SUCCEEDED";
	const RETURN_SUCCESS_CODE = 'success';
	const PAY_RESULT_SUCCESS = 'PAID';

	# Implement these for specific pay type
	protected abstract function configParams(&$params, $direct_pay_extra_info);
	protected abstract function processPaymentUrlForm($params);

	public function generatePaymentUrlForm($orderId, $playerId, $amount, $orderDateTime, $playerPromoId = null, $enabledSecondUrl = true, $bankId = null) {
		if ($this->shouldRedirect($enabledSecondUrl)) {
			$url = $this->CI->utils->getPaymentUrl($this->getSystemInfo('second_url'), $this->getPlatformCode(), $amount, $playerId, $playerPromoId, false, $bankId, $orderId);
			$result = array('success' => true, 'type' => self::REDIRECT_TYPE_URL, 'url' => $url);
			return $result;
		}

		$order = $this->CI->sale_order->getSaleOrderById($orderId);
        $playerDetails = $this->CI->player_model->getPlayerDetails($playerId);
        $email = (isset($playerDetails[0]) && !empty($playerDetails[0]['email'])) ? $playerDetails[0]['email'] : '';

        $params = array();
        $params['out_trade_no']     = $order->secure_id;
        $params['amount']           = $this->convertAmountToCurrency($amount);
        $params['return_url']       = $this->getReturnUrl($orderId);
        $params['bilingEmail']      = $email;
        $this->configParams($params, $order->direct_pay_extra_info);
		$this->CI->utils->debug_log("====================beamo generatePaymentUrlForm", $params);
		return $this->processPaymentUrlForm($params);
	}

    public function generateProdParams($params) {

        $getImageUrl = $this->getSystemInfo('image_url');
        if ($getImageUrl == "system") {
            $order = $this->CI->sale_order->getSaleOrderBySecureId($params['out_trade_no']);
            $this->CI->load->model(array('payment_account'));
            $payment_account_id = $this->CI->payment_account->getPaymentAccountIdBySystemId($this->getPlatformCode());
            if (!empty($payment_account_id)) {
                $payment_account = $this->CI->payment_account->getPaymentAccountWithVIPRule($payment_account_id, $order->player_id);
                if (!empty($payment_account)) {
                    $getImageUrl = $payment_account->account_image_url;
                }
            }
        }

        $prodParams = array();
        $prodParams['name']     = $params['out_trade_no'];
        $prodParams['currency'] = $this->getSystemInfo('currency', self::CURRENCY);
        $prodParams['amount']   = $params['amount'];
        $prodParams['images']   = [$getImageUrl];

        $this->CI->utils->debug_log("====================beamo generateProdParams", $prodParams);
        return $prodParams;
    }

    public function generatePaymentPageParams($params) {
        $paymentPageParams = array();
        $lineItems['product'] = $params['product_id'];
        $lineItems['quantity']   = 1;
        $paymentPageParams['lineItems'][]        = $lineItems;
        $paymentPageParams['clientReferenceId']  = $params['out_trade_no'];
        $paymentPageParams['successReturnUrl']   = $params['return_url'];
        if($this->getSystemInfo('allow_callback_amount_diff')){
            $paymentPageParams['settings']['skipPreview'] = true;
            $paymentPageParams['settings']['flexibleAmount'] = true;
            $paymentPageParams['settings']['enabledPaymentMethods'] = $params['enabledPaymentMethods'];
            $paymentPageParams['settings']['expireInSeconds'] = $this->getSystemInfo('allow_expire_seconds', self::EXPIRE_TIME);
        }
        if(!empty($params['bilingEmail'])){
            $paymentPageParams['bilingEmail']  = $params['bilingEmail'];
        }
        $this->CI->utils->debug_log("====================beamo generatePaymentPageParams", $paymentPageParams);
        return $paymentPageParams;
    }

    protected function processPaymentUrlFormRedirect($params) {
        $out_trade_no     = $params['out_trade_no'];

        //step 1:
        $product_url      = $this->getSystemInfo('product_url');
        $product_params   = $this->generateProdParams($params);
        $product_response = $this->processCurl($product_params, $product_url, $out_trade_no);

        if(isset($product_response['status']) && !empty($product_response['status']) && $product_response['status'] == self::RESULT_PRODUCT_PAGE_SUCCESS_CODE){
            $order = $this->CI->sale_order->getSaleOrderBySecureId($params['out_trade_no']);
            $this->CI->sale_order->updateExternalInfo($order->id, $params['out_trade_no']);
            if(isset($product_response['data']['id'])){
                $params['product_id'] = $product_response['data']['id'];
            }else{
                return array(
                    'success' => false,
                    'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
                    'message' => 'Not exist product ID'
                );
            }
        }elseif (isset($product_response['errorMessage']) && !empty($product_response['errorMessage'])) {
            return array(
                'success' => false,
                'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
                'message' => $product_response['errorMessage']
            );
        }else {
            return array(
                'success' => false,
                'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
                'message' => lang('Invalidte API response')
            );
        }

        //step 2:
        $payment_url      = $this->getSystemInfo('url');
        $payment_params   = $this->generatePaymentPageParams($params);
        $payment_response = $this->processCurl($payment_params, $payment_url, $out_trade_no);

        if(isset($payment_response['status']) && !empty($payment_response['status']) && $payment_response['status'] == self::RESULT_PRODUCT_PAGE_SUCCESS_CODE){
            $order = $this->CI->sale_order->getSaleOrderBySecureId($params['out_trade_no']);
            $this->CI->sale_order->updateExternalInfo($order->id, $params['out_trade_no']);
            if(isset($payment_response['data']['url'])){
                return array(
                    'success' => true,
                    'type' => self::REDIRECT_TYPE_URL,
                    'url' => $payment_response['data']['url'],
                );
            }else{
                return array(
                    'success' => false,
                    'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
                    'message' => lang('Invalidte API response')
                );
            }
        }else {
            return array(
                'success' => false,
                'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
                'message' => lang('Invalidte API response')
            );
        }
    }

	public function callbackFromServer($orderId, $params) {
		$response_result_id = parent::callbackFromServer($orderId, $params);
		return $this->callbackFrom('server', $orderId, $params, $response_result_id);
	}

	public function callbackFromBrowser($orderId, $params) {
		$response_result_id = parent::callbackFromBrowser($orderId, $params);
		return $this->callbackFrom('browser', $orderId, $params, $response_result_id);
	}

      # Callback URI: /callback/fixed_process/<payment_id>
    public function getOrderIdFromParameters($flds) {
        $this->CI->utils->debug_log('=====================beamo getOrderIdFromParameters flds', $flds);

        if(empty($flds)){
            $raw_post_data = file_get_contents('php://input', 'r');
            $flds = json_decode($raw_post_data ,true);
            $this->utils->debug_log('======beamo getOrderIdFromParameters raw_post flds ' , $flds);
        }

        if (isset($flds['data']['clientReferenceId'])) {
            $this->CI->load->model(array('sale_order','wallet_model'));
            if(substr($flds['data']['clientReferenceId'], 0, 1) == 'D'){
                $order = $this->CI->sale_order->getSaleOrderBySecureId($flds['data']['clientReferenceId']);
                return $order->id;
            }else{
                return $flds['data']['clientReferenceId'];
            }
        }
        else {
            $this->utils->debug_log('=====================beamo callbackOrder cannot get any order_id when getOrderIdFromParameters', $flds);
            return;
        }
    }

    private function callbackFrom($source, $orderId, $params, $response_result_id) {
        $result = array('success' => false, 'next_url' => null, 'message' => lang('error.payment.failed'));
        $order = $this->CI->sale_order->getSaleOrderById($orderId);
        $processed = false;

        $this->CI->utils->debug_log("=====================beamo callbackFrom $source params", $params);

        if($source == 'server'){
            if (empty($params)) {
                $raw_post_data = file_get_contents('php://input', 'r');
                $this->CI->utils->debug_log("=====================beamo raw_post_data", $raw_post_data);
                $params = json_decode($raw_post_data,true);
                $this->CI->utils->debug_log("=====================beamo json_decode data", $params);
            }

            if(substr($params['data']['clientReferenceId'] , 0, 1) == 'W'){
                $result = $this->isWithdrawal($params, $params['data']['clientReferenceId']);
                $this->CI->utils->debug_log('=======================beamo callbackFrom clientReferenceId', $params['data']['clientReferenceId']);
                return $result;
            }

            if (!$order || !$this->checkCallbackOrder($order, $params, $processed)) {
                return $result;
            }
        }

        # Update order payment status and balance
        $success = true;

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
            $this->CI->sale_order->updateExternalInfo($order->id, null, null, null, null, $response_result_id);
            if ($source == 'browser') {
                $success = $this->CI->sale_order->browserCallbackSaleOrder($order->id, 'auto broswer callback ' . $this->getPlatformCode(), false);
            } elseif ($source == 'server') {
                $this->approveSaleOrder($order->id, 'auto server callback ' . $this->getPlatformCode(), false);
            }
        }

        $result['success'] = $success;
        if ($processed) {
            $result['message'] = self::RETURN_SUCCESS_CODE;
        } else {
            $result['return_error'] = 'Error';
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
        if (!$this->checkCallbackWithdrawalOrder($order, $params, $processed)) {
            return $result;
        }

        if($params['data']['status'] == self::PAY_RESULT_SUCCESS) {
            $this->utils->debug_log('=====================beamo withdrawal payment was successful: trade ID [%s]', $params['txseq']);
            $msg = sprintf('beamo withdrawal was successful: trade ID [%s]',$params['data']['clientReferenceId']);
            $this->CI->wallet_model->withdrawalAPIReturnSuccess($orderId, $msg);
            $result['success'] = true;
            $result['message'] = self::RETURN_SUCCESS_CODE;
        }else {
            $msg = sprintf('beamo withdrawal payment was not successful  trade ID [%s] ',$params['data']['clientReferenceId']);
            $this->debug_log($msg, $params);
            $result['success'] = false;
            $result['message'] = $msg;
        }

        return $result;
    }

    private function checkCallbackWithdrawalOrder($order, $params, &$processed = false) {
        $requiredFields = array('amount', 'clientReferenceId', 'status');
        $this->CI->utils->debug_log("=====================beamo json_decode params", $params);
        $cryptolOrder = $this->CI->wallet_model->getCryptoWithdrawalOrderById($order['walletAccountId']);

        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $params['data'])) {
                $this->writePaymentErrorLog("=====================beamo missing parameter: [$f]", $params);
                return false;
            }
        }

        $processed = true; # processed is set to true once the signature verification pass

        # is signature authentic?
        $params['data']['signature'] = $params['signature'];
        if (!$this->validateSign($params['data'])) {
            $this->writePaymentErrorLog("=====================beamo checkCallbackOrder verify signature Error", $params);
            return false;
        }

        if($cryptolOrder['transfered_crypto'] == 0 || $params['data']['amount'] == 0){
            $this->writePaymentErrorLog("=====================beamo withdrawal checkCallbackOrder Payment crypto amounts is null");
            return false;
        }

        if ($params['data']['amount'] != $cryptolOrder['transfered_crypto']){
            $this->writePaymentErrorLog('======================beamo withdrawal checkCallbackOrder payment amount is wrong, expected =>'. $cryptolOrder['transfered_crypto'], $params);
            return false;
        }

        if ($params['data']['clientReferenceId'] != $order['transactionCode']) {
            $this->writePaymentErrorLog('======================beamo withdrawal checkCallbackOrder order IDs do not match, expected =>'. $order['transactionCode'], $params);
            return false;
        }

        if ($params['data']['status'] != self::PAY_RESULT_SUCCESS) {
            $this->writePaymentErrorLog("======================beamo checkCallbackOrder Payment status is not success", $params);
            return false;
        }

        # everything checked ok
        return true;
    }

	private function checkCallbackOrder($order, $params, &$processed = false) {
		$requiredFields = array('amount', 'clientReferenceId', 'status', 'currency');
        $this->CI->utils->debug_log("=====================beamo json_decode params", $params);

		foreach ($requiredFields as $f) {
			if (!array_key_exists($f, $params['data'])) {
				$this->writePaymentErrorLog("=====================beamo missing parameter: [$f]", $params);
				return false;
			}
		}

        # is signature authentic?
        $params['data']['signature'] = $params['signature'];
        if (!$this->validateSign($params['data'])) {
            $this->writePaymentErrorLog("=====================beamo checkCallbackOrder verify signature Error", $params);
            return false;
        }

        $processed = true; # processed is set to true once the signature verification pass


        $amount = $this->convertAmountToCurrency($order->amount);

        if(isset($params['data']['flexibleAmount'])) {
            if($params['data']['flexibleAmount']['requested'] != $amount) {
                $this->writePaymentErrorLog("=====================beamo checkCallbackOrder Payment flexible requested amount does not match the original submitted amount, expected [$amount]", $params);
                return false;
            }
            else {  // means $params['data']['flexibleAmount']['requested'] == $amount
                if($params['data']['flexibleAmount']['received'] != $params['data']['flexibleAmount']['requested']) {
                    $allow_callback_amount_diff = $this->getSystemInfo('allow_callback_amount_diff');
                    $only_allow_received_amount_is_smaller = $this->getSystemInfo('only_allow_received_amount_is_smaller');

                    if($allow_callback_amount_diff) {
                        $convert_multiplier = $this->getSystemInfo('convert_multiplier', 1);

                        if(!$only_allow_received_amount_is_smaller) {
                            $this->CI->utils->debug_log("======================beamo checkCallbackOrder Payment allow_callback_amount_diff no matter what", $params);
                            $notes = $order->notes . " | callback diff amount, origin was: " . ($params['data']['flexibleAmount']['requested'] / $convert_multiplier);
                            $this->CI->sale_order->fixOrderAmount($order->id, $params['data']['flexibleAmount']['received'] / $convert_multiplier, $notes);
                        }
                        else {
                            if ($params['data']['flexibleAmount']['received'] <= $params['data']['flexibleAmount']['requested']){
                                $this->CI->utils->debug_log("======================beamo checkCallbackOrder Payment allow_callback_amount_diff and flexible received amount is smaller than flexible requested amount", $params);
                                $notes = $order->notes . " | callback diff amount, origin was: " . ($params['data']['flexibleAmount']['requested'] / $convert_multiplier);
                                $this->CI->sale_order->fixOrderAmount($order->id, $params['data']['flexibleAmount']['received'] / $convert_multiplier, $notes);
                            }
                            else {
                                $this->writePaymentErrorLog("=====================beamo checkCallbackOrder Payment flexible received amount is not smaller than flexible requested amount but only allow received amount is smaller", $params);
                                return false;
                            }
                        }
                    }
                    else {
                        $this->writePaymentErrorLog("=====================beamo checkCallbackOrder Payment flexible received amount is different from flexible requested amount and not allow callback amount diff", $params);
                        return false;
                    }
                }
            }
        }
        else if($params['data']['amount'] != $this->convertAmountToCurrency($order->amount)) {
            $this->writePaymentErrorLog("=====================beamo checkCallbackOrder Payment amount do not match, expected [$amount]", $params);
            return false;
        }

        if ($params['data']['clientReferenceId'] != $order->secure_id) {
            $this->writePaymentErrorLog("=====================beamo checkCallbackOrder order IDs do not match, expected [$order->secure_id]", $params);
            return false;
        }

        if ($params['data']['status'] != self::PAY_RESULT_SUCCESS) {
            $this->writePaymentErrorLog("======================beamo checkCallbackOrder Payment status is not success", $params);
            return false;
        }

		# everything checked ok
		return true;
	}

	public function directPay($order = null) {
		return array('success' => false); # direct pay not supported by this API
	}

	private function getNotifyUrl($orderId) {
		return parent::getCallbackUrl('/callback/process/' . $this->getPlatformCode() . '/' . $orderId);
	}

	private function getReturnUrl($orderId) {
		return parent::getCallbackUrl('/callback/browser/success/' . $this->getPlatformCode() . '/' . $orderId);
	}

    protected function convertAmountToCurrency($amount) {
        $convert_multiplier = $this->getSystemInfo('convert_multiplier', 1);
        return number_format($amount * $convert_multiplier, 0, '.', '');
    }

    protected function validateSign($params) {
        if($this->getSystemInfo('allow_callback_amount_diff')){
            $this->sort_array($params);
            $signature = $params['signature'];
            unset($params['signature']);
            $webhookSecret = $this->getSystemInfo('callback_key');
            $sign = base64_encode(hash_hmac('sha256', json_encode($params), $webhookSecret, true));
            if ( $signature == $sign ) {
                return true;
            } else {
                return false;
            }
        }else{
            ksort($params);
            $signature = $params['signature'];
            unset($params['signature']);
            $webhookSecret = $this->getSystemInfo('callback_key');
            $sign = base64_encode(hash_hmac('sha256', json_encode($params), $webhookSecret, true));
            if ( $signature == $sign ) {
                return true;
            } else {
                return false;
            }
        }
    }

    public function sort_array(&$array) {
        ksort($array);
        foreach ($array as &$value) {
            if (is_array($value)) {
                $this->sort_array($value);
            }
        }
    }

    public function processCurl($params, $url, $out_trade_no, $return_all=false) {
        $ch = curl_init();
        $apiKey   = $this->getSystemInfo('key');
        $username = $apiKey;
        $password = $apiKey;
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
        curl_setopt($ch, CURLOPT_USERPWD, $username . ":" . $password);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLINFO_HEADER_OUT, TRUE);
        $headers = [
            'Content-Type: application/json',
        ];
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $this->setCurlProxyOptions($ch);

        $response    = curl_exec($ch);
        $errCode     = curl_errno($ch);
        $error       = curl_error($ch);
        $statusCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        $this->CI->utils->debug_log('url', $url, 'params', $params , 'response', $response, 'errCode', $errCode, 'error', $error, 'statusCode', $statusCode);
        $response_result_id = $this->submitPreprocess($params, $response, $url, $response, array('errCode' => $errCode, 'error' => $error, 'statusCode' => $statusCode), $out_trade_no);

        $this->CI->utils->debug_log('=====================beamo processCurl response', $response);

        if($return_all){
            $response_result = [
                $params, $response, $url, $response, ['errCode' => $errCode, 'error' => $error, 'statusCode' => $statusCode], $params['transaction_id']
            ];
            return array($response, $response_result);
        }

        $response = json_decode($response, true);

        $this->CI->utils->debug_log('=====================beamo processCurl decoded response', $response);

        return $response;
    }
}
