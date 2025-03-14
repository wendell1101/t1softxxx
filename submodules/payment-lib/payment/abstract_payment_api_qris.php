<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * QRIS
 *
 * * QRIS_PAYMENT_API, ID: 6367
 *
 * Required Fields:
 *
 * * URL:https://qris.otomatis.vip/api/generate
 * * Account - Merchant ID
 * * Key - Signing key
 * * Extra Info
 *
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
abstract class Abstract_payment_api_qris extends Abstract_payment_api {
const REPONSE_CODE_SUCCESS = true;
const ORDER_STATUS_SUCCESS = "success";
const RETURN_SUCCESS_CODE = "SUCCESS";
const RETURN_FAILED_CODE = "FAIL";


    # Implement these for specific pay type
    protected abstract function configParams(&$params, $direct_pay_extra_info);
    protected abstract function processPaymentUrlForm($params);

    public function __construct($params = null) {
        parent::__construct($params);
        $this->_custom_curl_header = array('Content-Type:application/json');
    }

    public function generatePaymentUrlForm($orderId, $playerId, $amount, $orderDateTime, $playerPromoId = null, $enabledSecondUrl = true, $bankId = null) {
        if ($this->shouldRedirect($enabledSecondUrl)) {
            $url = $this->CI->utils->getPaymentUrl($this->getSystemInfo('second_url'), $this->getPlatformCode(), $amount, $playerId, $playerPromoId, false, $bankId, $orderId);
            $result = array('success' => true, 'type' => self::REDIRECT_TYPE_URL, 'url' => $url);
            return $result;
        }
        $order = $this->CI->sale_order->getSaleOrderById($orderId);
        // $playerDetails = $this->CI->player_model->getPlayerDetails($playerId);
        // $firstname = (isset($playerDetails[0]) && !empty($playerDetails[0]['firstName']))     ? $playerDetails[0]['firstName']     : 'no firstName';
        $playeUsername = $this->CI->player_model->getUsernameById($playerId);
        $params['username']   = $playeUsername;
        $params['amount'] 	  = (int)$this->convertAmountToCurrency($amount);
        $params['uuid'] 	  = $this->getSystemInfo("uuid");
        $params['expire'] 	  = $this->getSystemInfo("expire");
        $params['orderNo']    = $order->secure_id;
        $this->CI->utils->debug_log("=====================qris generatePaymentUrlForm", $params);
        return $this->processPaymentUrlForm($params);
    }

    # Display QRCode get from curl
    protected function processPaymentUrlFormPost($params) {
        $response = $this->submitPostForm($this->getSystemInfo('url'), $params, true, $params['orderNo']);
        $response = json_decode($response,true);
        $this->CI->utils->debug_log('========================================qris processPaymentUrlFormPost response json to array', $response);

        $msg = lang('Invalidate API response');
        if( isset($response['status']) && $response['status'] == self::REPONSE_CODE_SUCCESS && !empty($response['trx_id']) ){
            $order = $this->CI->sale_order->getSaleOrderBySecureId($params['orderNo']);
            $this->CI->sale_order->updateExternalInfo($order->id, $response['trx_id']);
            if(isset($response['data']) && !empty($response['data'])){
                return array(
                    'success' => true,
                    'type' => self::REDIRECT_TYPE_QRCODE,
                    'url' => $response['data']
                );
            }else{
                return array(
                    'success' => false,
                    'type' => self::REDIRECT_TYPE_ERROR,
                    'message' => $msg."callback:".$response['msg']
                );
            }
        }else {
            if(isset($response['error']) && !empty($response['error'])) {
                $msg = $response['error'];
            }
            return array(
                'success' => false,
                'type' => self::REDIRECT_TYPE_ERROR,
                'message' => $msg."callback:"
            );
        }
    }

    /**
     * detail: This will be called when the payment is async, API server calls our callback page,
     * When that happens, we perform verifications and necessary database updates to mark the payment as successful
     *
     * @param int $orderId order id
     * @param array $params
     * @return array
     */
    public function callbackFromServer($orderId, $params) {
        $response_result_id = parent::callbackFromServer($orderId, $params);
        return $this->callbackFrom('server', $orderId, $params, $response_result_id);
    }

    /**
     * detail: This will be called when user redirects back to our page from payment API
     *
     * @param int $orderId order id
     * @param array $params
     * @return array
     */
    public function callbackFromBrowser($orderId, $params) {
        $response_result_id = parent::callbackFromBrowser($orderId, $params);
        return $this->callbackFrom('browser', $orderId, $params, $response_result_id);
    }

    # $source can be 'server' or 'browser'
    private function callbackFrom($source, $orderId, $params, $response_result_id) {
        $result = array('success' => false, 'next_url' => null, 'message' => lang('error.payment.failed'));
        $order = $this->CI->sale_order->getSaleOrderById($orderId);
        $processed = false;

        if(empty($params)){
            $raw_post_data = file_get_contents('php://input', 'r');
            $params = json_decode($raw_post_data, true);
        }

        $this->CI->utils->debug_log("=====================qris callbackFrom $source params", $params);

        if($source == 'server' ){
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
            if(isset($params['trx_id'])){
                $external_id = $params['trx_id'];
            }else{
                $external_id = $order->secure_id;
            }
            $this->CI->sale_order->updateExternalInfo($order->id, $external_id, null, null, null, $response_result_id);
            if ($source == 'browser') {
                $success = $this->CI->sale_order->browserCallbackSaleOrder($order->id, 'auto broswer callback ' . $this->getPlatformCode(), false);
            } elseif ($source == 'server') {
                $this->approveSaleOrder($order->id, 'auto server callback ' . $this->getPlatformCode(), false);
            }
        }

        $result['success'] = $success;
        if ($success) {
            $result['message'] = self::RETURN_SUCCESS_CODE;
        } else {
            $result['return_error'] = self::RETURN_FAILED_CODE;
        }

        if ($source == 'browser') {
            $result['next_url'] = $this->getPlayerBackUrl();
            $result['go_success_page'] = true;
        }

        return $result;
    }

    /**
     * detail: Validates whether the callback from API contains valid info and matches with the order
     *
     * @return boolean
     */

    private function checkCallbackOrder($order, $fields, &$processed)
    {
        # does all required fields exist?
        $requiredFields = array('amount', 'terminal_id', 'merchant_id', 'status', 'trx_id');
        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                    $this->writePaymentErrorLog("=========================qris checkCallbackOrder missing parameter: [$f]", $fields);
                    return false;
            }
        }

        $processed = true; # processed is set to true once the signature verification pass
        if (($fields['status'] != self::ORDER_STATUS_SUCCESS) ) {
            $this->writePaymentErrorLog("=========================qris checkCallbackOrder returncode was not successful", $fields);
           return false;
        }

        if ($fields['amount'] != $this->convertAmountToCurrency($order->amount)) {
            #because player need to enter amount at Alipay
            if($this->getSystemInfo('allow_callback_amount_diff')){
                $this->CI->utils->debug_log('=====================qris amount not match expected [$order->amount]');
                $notes = $order->notes . " | callback diff amount, origin was: " . $order->amount;
                $this->CI->sale_order->fixOrderAmount($order->id, $fields['amount'], $notes);
            }
            else{
                $this->writePaymentErrorLog("======================qris checkCallbackOrder Payment amount is wrong, expected [$order->amount]", $fields);
                return false;
            }
        }
        
        $checkStatusParams = [
            'uuid' => $fields['merchant_id'],
            'client' => $this->getSystemInfo('merchant_active')['client'],
        ];

        $submitPostCheckStatus=$this->submitPostCheckStatus($this->getSystemInfo('check_url').$fields['trx_id'], $checkStatusParams);

        if($submitPostCheckStatus['status'] != self::ORDER_STATUS_SUCCESS){
            $this->writePaymentErrorLog("=========================qris checkCallbackOrder submitPostCheckStatus failed", $submitPostCheckStatus['status']);
            return false;
        }

      # everything checked ok
      return true;
    }

    public function directPay($order = null) {
        return array('success' => false); # direct pay not supported by this API
    }

    # Callback URI: /callback/fixed_process/<payment_id>
    public function getOrderIdFromParameters($flds) {
        if(empty($flds)){
            $raw_post_data = file_get_contents('php://input', 'r');
            $flds = json_decode($raw_post_data ,true);
            $this->utils->debug_log('======qris getOrderIdFromParameters raw_post flds ' , $flds);
        }

        if(isset($flds['trx_id'])) {
            $order = $this->CI->sale_order->getSaleOrderByExternalOrderId($flds['trx_id']);
            return $order->id;
        }
        else {
            $this->utils->debug_log('=====================qris getOrderIdFromParameters cannot get ref_no', $flds);
            return;
        }

        // $raw_post_data = file_get_contents('php://input', 'r');
        // $flds = json_decode($raw_post_data, true);

        // if (isset($flds['trx_id'])) {

        //     if(isset($flds['created_at']) ){
        //         $target_table_handle=explode('T',$flds['created_at']);
        //         $target_table_handle=str_replace('-',"",$target_table_handle[0]);
        //         $target_table="resp_".$target_table_handle;
        //     }
        //     $this->utils->debug_log('=====================qris target_table', $target_table);

        //     $order_id=$this->getOrderIdFormResp($flds['trx_id'], $target_table);
        //     if(empty($order_id)){
        //         $this->utils->debug_log('=====================qris callbackOrder cannot get any order_id when getOrderIdFromParameters', $flds);
        //         return;
        //     }
        //     return $order_id;
        // }
        // else {
        //     $this->utils->debug_log('=====================qris callbackOrder cannot get any order_id when getOrderIdFromParameters', $flds);
        //     return;
        // }
    }

    /**
     * detail: Format the amount value for the API
     *
     * @param float $amount
     * @return float
     */

    protected function convertAmountToCurrency($amount) {
        $convert_multiplier = $this->getSystemInfo('convert_multiplier', 1);
        $return_amount = number_format($amount * $convert_multiplier,2, '.', '') ;

        return $return_amount ;
    }

    protected function getOrderIdFormResp($trx_id, $target_table) {
        $order = $this->CI->response_result->getOrderIdFormResp($trx_id, $target_table, $this->getPlatformCode());
        return $order;
    }


	protected function submitPostCheckStatus($url, $params) {
        $this->CI->utils->debug_log("=====================qris submitPostCheckStatus==============", $url, $params);

		try {
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_HEADER, true);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));

            if(!empty($this->_custom_curl_header)){
                curl_setopt($ch, CURLOPT_HTTPHEADER, $this->_custom_curl_header);
            }

            $this->setCurlProxyOptions($ch);

			curl_setopt($ch, CURLOPT_TIMEOUT, $this->getTimeoutSecond());
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->getConnectTimeout());

			$response = curl_exec($ch);

			$errCode = curl_errno($ch);
			$error = curl_error($ch);
			$header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
			$header = substr($response, 0, $header_size);
			$content = substr($response, $header_size);
			curl_close($ch);
			$response_result_content = json_decode($content,true);
			$this->CI->utils->debug_log('========================= qris submitPostForm curl content ', $response_result_content['status']);
			return $response_result_content;
		} catch (Exception $e) {
			$this->CI->utils->error_log('POST failed', $e);
		}
	}

}
