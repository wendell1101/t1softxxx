<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * PAYSAFE
 *
 * * PAYSAFE_CREDITCARD_PAYMENT_API, ID: 5012
 *
 * Required Fields:
 *
 * * URL
 * * Account - Merchant ID
 * * Key - Sha key
 *
 * Field Values:
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
abstract class Abstract_payment_api_paysafe extends Abstract_payment_api {

	const RETURN_SUCCESS_CODE = 'success';
	const RETURN_FAILED_CODE = 'failed';
	const TRADE_STATUS_SUCCESS = 'success';

	public function __construct($params = null) {
		parent::__construct($params);
        $authorization = base64_encode($this->getSystemInfo('key'));
        $this->_custom_curl_header = [ 'Content-Type: application/json', 'Authorization: Basic '. $authorization];
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

		$order = $this->CI->sale_order->getSaleOrderById($orderId);

		$params = array();
        $params['saleOrder'] = $order;  //in order to save external_order_id

		$params['merchantRefNum'] = $order->secure_id;
		$params['currencyCode'] = $this->getSystemInfo('currency',"USD");
		$params['totalAmount'] = $this->convertAmountToCurrency($amount);
		$params['callback'] = [
		    [
                'format' => 'json',
                'rel' => 'on_success',
                'retries' => '5',
                'returnKeys' => ['id', 'transaction.amount', 'transaction.authCode', 'transaction.merchantRefNum', 'transaction.status', 'transaction.errorCode'],
                'synchronous' => true,
                'uri' => $this->getNotifyUrl($orderId),
            ]
        ];

		$this->configParams($params, $order->direct_pay_extra_info);
		$this->CI->utils->debug_log('======================================paysafe generatePaymentUrlForm: ', $params);

		return $this->processPaymentUrlForm($params);
	}

	# Submit POST
	protected function processPaymentUrlFormPost($params) {
	    $saleOrder = $params['saleOrder'];
	    unset($params['saleOrder']);

		$url = $this->getSystemInfo('url');
        $result_json = $this->submitPostForm($url, $params, true, $params['merchantRefNum']);
        $result = json_decode($result_json,true);

        $msg = lang('Invalidate API response');

        if(empty($result)){
            return array(
                'success' => false,
                'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
                'message' => $msg
            );
        }


        if(!empty($result['link']) && !empty($result['totalAmount'])) {
            $this->CI->load->model(['sale_order','transactions']);
            //validate response aomunt with origin amount
            if(FALSE !== $validateResult = $this->validateResult($result, $params)){
                //decline order
                $this->CI->sale_order->declineSaleOrder($saleOrder->id, $validateResult['msg'], false);
                $this->CI->transactions->createDeclinedDepositTransaction($saleOrder, null, Transactions::MANUAL);
            }else{
                $this->CI->sale_order->updateExternalInfo($saleOrder->id, $result['id'], null, null, null, null);
                return array(
                    'success' => true,
                    'type' => self::REDIRECT_TYPE_URL,
                    'url' => $result['link']['0']['uri']
                );
            }
        }

        if(!empty($result['error'])){
            $msg = $result['error']['message'];
        }

        return array(
            'success' => false,
            'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
            'message' => $msg
        );
	}

	private function validateResult($result, $params){
	    $response = FALSE;
        $err_msg = NULL;
        if($result['merchantRefNum'] != $params['merchantRefNum']){
            $err_msg = "order id  [ ". $params['merchantRefNum'] ." ] not match response id [ ". $result['merchantRefNum'] ." ]";
            $response = ['msg' => $err_msg];
            $this->CI->utils->debug_log("======================================paysafe ".$err_msg);
            return $response;
        }

        if($result['currencyCode'] != $params['currencyCode']){
            $err_msg = "order currency  [ ". $params['currencyCode'] ." ] not match response currency [ ". $result['currencyCode'] ." ]";
            $response = ['msg' => $err_msg];
            $this->CI->utils->debug_log("======================================paysafe ".$err_msg);
            return $response;
        }

	    if($result['totalAmount'] != $params['totalAmount']){
            $err_msg = "order amount  [ ". $params['totalAmount'] ." ] not match response amount [ ". $result['totalAmount'] ." ]";
            $response = ['msg' => $err_msg];
            $this->CI->utils->debug_log("======================================paysafe ".$err_msg);
            return $response;
        }

        return $response;
    }

	public function callbackFromServer($orderId, $params) {
		$response_result_id = parent::callbackFromServer($orderId, $params);
		return $this->callbackFrom('server', $orderId, $params, $response_result_id);
	}

	public function callbackFromBrowser($orderId, $params) {
		$response_result_id = parent::callbackFromBrowser($orderId, $params);
		return $this->callbackFrom('browser', $orderId, $params, $response_result_id);
	}

	# $source can be 'server' or 'browser'
    private function callbackFrom($source, $orderId, $params, $response_result_id) {
        $result = array('success' => false, 'next_url' => null, 'message' => lang('error.payment.failed'));
        $order = $this->CI->sale_order->getSaleOrderById($orderId);
        $processed = false;

        if($source == 'server'){
            $this->CI->utils->debug_log('=======================paysafe callbackFromServer server callbackFrom', $params);
            if (empty($params)) {
                $raw_post_data = file_get_contents('php://input');
                $this->CI->utils->debug_log("=====================paysafe callbackFromServer raw_post_data", $raw_post_data);
                $params = json_decode($raw_post_data,true);
                $this->CI->utils->debug_log("=====================paysafe callbackFromServer json_decode params", $params);
            }
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
            $this->CI->sale_order->updateExternalInfo($order->id, $params['id'], null, null, null, $response_result_id);
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
            $result['message'] = $processed ? self::RETURN_SUCCESS_CODE : self::RETURN_FAILED_CODE;
        }

        if ($source == 'browser') {
            $result['next_url'] = $this->getPlayerBackUrl();
            $result['go_success_page'] = true;
        }

        return $result;
    }

	# returns true if callback is valid and payment is successful
	# sets the $callbackValid parameter if callback is valid
	private function checkCallbackOrder($order, $fields, &$callbackValid) {
		# does all required fields exist?
		$requiredFields = array(
            'id','transaction.amount','transaction.merchantRefNum','transaction.status'
		);
		foreach ($requiredFields as $f) {
			if (!array_key_exists($f, $fields)) {
				$this->writePaymentErrorLog("=======================paysafe checkCallbackOrder missing parameter: [$f]", $fields);
				return false;
			}
		}

        if ($fields['id'] != $order->external_order_id) {
            $this->writePaymentErrorLog("=======================paysafe checkCallbackOrder external order ID do not match, expected [$order->external_order_id]", $fields);
            return false;
        }

		$callbackValid = true;

		if ($this->convertAmountToCurrency($order->amount) != $fields['transaction.amount']) {
			$this->writePaymentErrorLog("=======================paysafe checkCallbackOrder payment amounts do not match, expected [$order->amount]", $fields);
			return false;
		}

        if ($fields['transaction.merchantRefNum'] != $order->secure_id) {
            $this->writePaymentErrorLog("=======================paysafe checkCallbackOrder order IDs do not match, expected [$order->secure_id]", $fields);
            return false;
        }

        if ($fields['transaction.status'] != self::TRADE_STATUS_SUCCESS) {
            $this->writePaymentErrorLog('=======================paysafe checkCallbackOrder payment was not successful', $fields);
            return false;
        }

		# everything checked ok
		return true;
	}

	public function directPay($order = null) {
		return array('success' => false); # direct pay not supported by this API
	}

	private function convertAmountToCurrency($amount) {
		return number_format($amount*100, 0, '.', '');
	}

	private function getNotifyUrl($orderId) {
		return parent::getCallbackUrl('/callback/process/' . $this->getPlatformCode() . '/' . $orderId);
	}

}
