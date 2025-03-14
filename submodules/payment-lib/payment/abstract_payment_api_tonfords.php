<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * TONFORDS 通付宝
 *
 * * TONFORDS_ALIPAY_PAYMENT_API, ID: 5613
 * * TONFORDS_ALIPAY_H5_PAYMENT_API, ID: 5614
 * * TONFORDS_WEIXIN_PAYMENT_API, ID: 5615
 * * TONFORDS_WEIXIN_H5_PAYMENT_API ID: 5616
 *
 * Required Fields:
 *
 * * URL:https://api.payin.gsfdc88886789.com/order.php
 * * Account - Merchant ID
 * * Key - Signing key
 * * Extra Info
 *
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
abstract class Abstract_payment_api_tonfords extends Abstract_payment_api
{
    const PAYMENT_WAY_ALIPAY = '1';
    const PAYMENT_WAY_WEIXIN = '2';
    const PAYMENT_WAY_ALIPAY_H5 = '3';
    const PAYMENT_WAY_WEIXIN_H5 = '4';

    const RESULT_CODE_SUCCESS = '200';
    const RESULT_CODE_ORDER_EXIST = '300';

    const RETURN_SUCCESS_CODE = '200';
    const RETURN_SUCCESS_MSG = 'success';
    const RETURN_FAILED_CODE = '500';
    const RETURN_FAILED_MSG = 'failed';
    const CALLBACK_STATIC_SUCCESS = '200';

    # Implement these for specific pay type
    abstract protected function configParams(&$params, $direct_pay_extra_info);
    abstract protected function processPaymentUrlForm($params);

    public function generatePaymentUrlForm($orderId, $playerId, $amount, $orderDateTime, $playerPromoId = null, $enabledSecondUrl = true, $bankId = null)
    {
        if ($this->shouldRedirect($enabledSecondUrl)) {
            $url = $this->CI->utils->getPaymentUrl($this->getSystemInfo('second_url'), $this->getPlatformCode(), $amount, $playerId, $playerPromoId, false, $bankId, $orderId);
            $result = array('success' => true, 'type' => self::REDIRECT_TYPE_URL, 'url' => $url);
            return $result;
        }
        $order = $this->CI->sale_order->getSaleOrderById($orderId);
   
        $params['mid'] = $this->getSystemInfo("account");
        $params['oid'] = $order->secure_id;
        $params['amount'] = $this->convertAmountToCurrency($amount);
        $params['notify_url'] = $this->getNotifyUrl($orderId);
        //$params['ip'] = $this->getClientIp();
   
        $this->configParams($params, $order->direct_pay_extra_info);
        $params['sign'] = $this->sign($params);
        $this->CI->utils->debug_log("=====================tonfords generatePaymentUrlForm", $params);

        return $this->processPaymentUrlForm($params);
    }

    protected function processPaymentUrlFormPost($params)
    {
        $url = $this->getSystemInfo('url');
        $response = $this->submitPostForm($url, $params, false, $params['oid']);
        $decodeData = json_decode($response, true);
        $this->CI->utils->debug_log('=====================tonfords processPaymentUrlFormQRcode response json to array', $decodeData);
        $msg = lang('Invalidate API response');

        if (!empty($decodeData['status']) && ($decodeData['status'] == self::RESULT_CODE_SUCCESS)) {
            return array(
                    'success' => true,
                    'type' => self::REDIRECT_TYPE_URL,
                    'url' => $decodeData['url'],
                );
        } else {
            if ($decodeData['status'] == self::RESULT_CODE_ORDER_EXIST) {
                $msg = lang('Order submitted');
            }
            return array(
                'success' => false,
                'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
                'message' => $msg
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
    public function callbackFromServer($orderId, $params)
    {
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
    public function callbackFromBrowser($orderId, $params)
    {
        $response_result_id = parent::callbackFromBrowser($orderId, $params);
        return $this->callbackFrom('browser', $orderId, $params, $response_result_id);
    }

    # $source can be 'server' or 'browser'
    private function callbackFrom($source, $orderId, $params, $response_result_id)
    {
        $result = array('success' => false, 'next_url' => null, 'message' => lang('error.payment.failed'));
        $order = $this->CI->sale_order->getSaleOrderById($orderId);
        $processed = false;

        if (empty($params)) {
            $raw_post_data = file_get_contents('php://input', 'r');
            $params = json_decode($raw_post_data, true);
        }

        if ($source == 'server') {
            $this->CI->utils->debug_log('=======================tonfords callbackFromServer server callbackFrom', $params);
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
            $this->CI->sale_order->updateExternalInfo($order->id, $params['oid'], null, null, null, $response_result_id);
            if ($source == 'browser') {
                $success = $this->CI->sale_order->browserCallbackSaleOrder($order->id, 'auto broswer callback ' . $this->getPlatformCode(), false);
            } elseif ($source == 'server') {
                $this->approveSaleOrder($order->id, 'auto server callback ' . $this->getPlatformCode(), false);
            }
        }

        $result['success'] = $success;
        if ($success) {
            $resultContent = [
                'status' => self::RETURN_SUCCESS_CODE,
                'msg' => self::RETURN_SUCCESS_MSG
            ];
            $result['message'] = json_encode($resultContent);
        } else {
            $resultContent = [
                'code' => self::RETURN_FAILED_CODE,
                'msg' => self::RETURN_FAILED_MSG,
            ];
            $result['message'] = json_encode($resultContent);
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

    private function checkCallbackOrder($order, $fields, &$processed = false)
    {
        $requiredFields = array(
            'status', 'oid', 'amount', 'time','sign'
        );

        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("=====================tonfords missing parameter: [$f]", $fields);
                return false;
            }
        }

        # is signature authentic?
        if (!$this->callbackSign($fields)) {
            $this->writePaymentErrorLog('=======================tonfords checkCallbackOrder verify signature Error', $fields);
            return false;
        }

        $processed = true; # processed is set to true once the signature verification pass

        if ($fields['status'] != self::CALLBACK_STATIC_SUCCESS) {
            $payStatus = $fields['status'];
            $this->writePaymentErrorLog("=====================tonfords Payment was not successful, payStatus is [$payStatus]", $fields);
            return false;
        }

        if ($this->convertAmountToCurrency($order->amount) != $fields['amount']) {
            $this->writePaymentErrorLog("=====================tonfords Payment amounts do not match, expected [$order->amount]", $fields);
            return false;
        }

        if ($fields['oid'] != $order->secure_id) {
            $this->writePaymentErrorLog("========================tonfords checkCallbackOrder order IDs do not match, expected [$order->secure_id]", $fields);
            return false;
        }

        # everything checked ok
        return true;
    }

    public function directPay($order = null)
    {
        return array('success' => false); # direct pay not supported by this API
    }

    # -- Private functions --
    /**
     * detail: After payment is complete, the gateway will invoke this URL asynchronously
     *
     * @param int $orderId
     * @return void
     */
    private function getNotifyUrl($orderId)
    {
        return parent::getCallbackUrl('/callback/process/' . $this->getPlatformCode() . '/' . $orderId);
    }

    /**
     * detail: After payment is complete, the gateway will send redirect back to this URL
     *
     * @param int $orderId
     * @return void
     */
    private function getReturnUrl($orderId)
    {
        return parent::getCallbackUrl('/callback/browser/success/' . $this->getPlatformCode() . '/' . $orderId);
    }

    /**
     * detail: Format the amount value for the API
     *
     * @param float $amount
     * @return float
     */
    protected function convertAmountToCurrency($amount)
    {
        return number_format($amount, 2, '.', '');//元
    }

    # -- private helper functions --

    /**
     * detail: getting the signature
     *
     * @param array $data
     * @return	string
     */
    public function sign($params)
    {
        ksort($params);
        $signStr = '';
        foreach ($params as $key => $val) {
            if ($val==='' || $key=='sign') {
                continue;
            }
            $signStr .= $key.'='.$val.'&';
        }
        $signStr .= 'key='.$this->getSystemInfo('key');
        $sign = hash('sha256', $signStr);
        return $sign;
    }


    ##validate whether callback signature is correspond with sign of callback biz_conent or not
    private function callbackSign($params)
    {
        ksort($params);
        $signStr = '';
        foreach ($params as $key => $val) {
            if ($val==='' || $key=='sign') {
                continue;
            }
            $signStr .= $key.'='.$val.'&';
        }
        $signStr .= 'key='.$this->getSystemInfo('key');
        $sign = hash('sha256', $signStr);
        return $sign == $params['sign'];
    }
}
