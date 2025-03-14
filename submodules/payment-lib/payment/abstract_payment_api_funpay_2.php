<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * FUNPAY_2
 *
 * * FUNPAY_2_ALIPAY_PAYMENT_API, ID: 5217
 * * FUNPAY_2_ALIPAY_H5_PAYMENT_API, ID: 5218
 * * FUNPAY_2_UNIONPAY_PAYMENT_API, ID: 5219
 * * FUNPAY_2_WITHDRAWAL_PAYMENT_API, ID: 5220
 * * FUNPAY_2_UNIONPAY_H5_PAYMENT_API, ID: 5356
 * * FUNPAY_2_UNIONPAY_2_PAYMENT_API, ID: 5360
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://paypaul.385mall.top/onlinepay/
 * * Account: ## User ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
abstract class Abstract_payment_api_funpay_2 extends Abstract_payment_api
{
     const SERVICE_ALIPAY = "0010";
     const SERVICE_UNIONPAY = "010800";
     const PAYTYPE_ALIPAY = "22";
     const TRANSCODE_MOBILE = "001";
     const SERVICE_WEIXIN = "0002"; //微信
     const PAYTYPE_WEIXIN = "30"; //微信H5(跳转支付界面)

     const RESULT_CODE_SUCCESS = "520000";

     const CALLBACK_STATUS_SUCCESS = "4";
     const CALLBACK_STATUS_FAILED = "5";
     const CALLBACK_STATUS_REFUNDED = "9";

     const RETURN_FAIL_CODE = 'FAIL';
     const RETURN_SUCCESS_CODE = 'SUCCESS';

     public function __construct($params = null)
     {
          parent::__construct($params);
     }

     # Implement these to specify pay type
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
          $player = $this->CI->player->getPlayerById($playerId);

          $params = array();
          $params['merchantId'] = $this->getSystemInfo('account');
          $params['amount'] = $this->convertAmountToCurrency($amount);
          $params['orderId'] = $order->secure_id;
          $params['requestIp'] = $this->getClientIp();
          $params['orderTime'] = $orderDateTime->format('YmdHis');
          $params['goodsName'] = $order->secure_id;
          $params['goodsDesc'] = "deposit";
          $this->configParams($params, $order->direct_pay_extra_info);
          $params['notifyUrl'] = $this->getNotifyUrl($orderId);
          $params['returnUrl'] = $this->getReturnUrl($orderId);
          $params['sign'] = $this->sign($params);
          $this->CI->utils->debug_log('=====================funpay_2 generatePaymentUrlForm params', $params);

          return $this->processPaymentUrlForm($params);
     }

     protected function processPaymentUrlFormPost($params)
     {
          return array(
               'success' => true,
               'type' => self::REDIRECT_TYPE_FORM,
               'url' => $this->getSystemInfo('url'),
               'params' => $params,
               'post' => true
          );
     }

     protected function processPaymentUrlFormQRCode($params)
     {
          $response = $this->submitPostForm($this->getSystemInfo('url'), $params, true, $params['orderId']);
          $response = json_decode($response, true);
          $this->CI->utils->debug_log('=====================funpay_2 processPaymentUrlFormPost response', $response);

          if ($response['code'] == self::RESULT_CODE_SUCCESS) {
               return array(
                    'success' => true,
                    'type' => self::REDIRECT_TYPE_QRCODE,
                    'url' => $response['bankUrl']
               );
          } elseif (isset($response['message'])) {
               return array(
                    'success' => false,
                    'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
                    'message' => '[' . $response['code'] . ']: ' . $response['message']
               );
          } else {
               return array(
                    'success' => false,
                    'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
                    'message' => lang('Invalidte API response')
               );
          }
     }

     public function callbackFromServer($orderId, $params)
     {
          $response_result_id = parent::callbackFromServer($orderId, $params);
          return $this->callbackFrom('server', $orderId, $params, $response_result_id);
     }

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

          $this->CI->utils->debug_log("=====================funpay_2 callbackFrom $source params", $params);

          if ($source == 'server') {
               if (empty($params)) {
                    $raw_post_data = file_get_contents('php://input', 'r');
                    $this->CI->utils->debug_log("=====================funpay_2 raw_post_data", $raw_post_data);
                    $params = json_decode($raw_post_data, true);
                    $this->CI->utils->debug_log("=====================funpay_2 json_decode params", $params);
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
                    if ($params['status'] == self::CALLBACK_STATUS_FAILED || $params['status'] == self::CALLBACK_STATUS_REFUNDED) {
                         $this->CI->sale_order->declineSaleOrder($order->id, 'auto server callback declined ' . $this->getPlatformCode(), false);
                    } elseif ($params['status'] == self::CALLBACK_STATUS_SUCCESS) {
                         $this->approveSaleOrder($order->id, 'auto server callback ' . $this->getPlatformCode(), false);
                    }
               }
          }

          $result['success'] = $success;
          if ($processed) {
               $result['message'] = self::RETURN_SUCCESS_CODE;
          } else {
               $result['return_error'] = self::RETURN_FAIL_CODE;
          }

          if ($source == 'browser') {
               $result['next_url'] = $this->getPlayerBackUrl();
               $result['go_success_page'] = true;
          }

          return $result;
     }

     ## Validates whether the callback from API contains valid info and matches with the order
     ## Reference: code sample, callback.php
     private function checkCallbackOrder($order, $fields, &$processed = false)
     {
          $requiredFields = array('status', 'orderId', 'amount', 'sign');

          foreach ($requiredFields as $f) {
               if (!array_key_exists($f, $fields)) {
                    $this->writePaymentErrorLog("=====================funpay_2 checkCallbackOrder Missing parameter: [$f]", $fields);
                    return false;
               }
          }

          # is signature authentic?
          if (!$this->validateSign($fields)) {
               $this->writePaymentErrorLog('=====================funpay_2 checkCallbackOrder Signature Error', $fields);
               return false;
          }

          $processed = true; # processed is set to true once the signature verification pass
          if ($fields['amount'] != $this->convertAmountToCurrency($order->amount)) {
               $this->writePaymentErrorLog("=====================funpay_2 checkCallbackOrder amounts do not match, expected [$order->amount]", $fields);
               return false;
          }

          if ($fields['orderId'] != $order->secure_id) {
               $this->writePaymentErrorLog("======================funpay_2 checkCallbackOrder order IDs do not match, expected [$order->secure_id]", $fields);
               return false;
          }

          # everything checked ok
          return true;
     }

     public function directPay($order = null)
     {
          return array('success' => false); # direct pay not supported by this API
     }

     # -- signatures --
     protected function sign($params)
     {
          $signStr = $this->createSignStr($params);
          $sign = strtoupper(md5($signStr));
          return $sign;
     }

     private function createSignStr($params)
     {
          ksort($params);
          $signStr = '';
          foreach ($params as $key => $value) {
               if (is_null($value) || $key == 'sign') {
                    continue;
               }
               $signStr .= "$key=$value&";
          }
          return $signStr . 'key=' . $this->getSystemInfo('key');
     }

     private function validateSign($params)
     {
          ksort($params);
          $signStr = '';
          foreach ($params as $key => $value) {
               if (is_null($value) || $key == 'sign' || $key == 'random') {
                    continue;
               }
               $signStr .= "$key=$value&";
          }
          $sign = strtoupper(md5($signStr . 'key=' . $this->getSystemInfo('key')));
          if ($params['sign'] == $sign) {
               return true;
          } else {
               return false;
          }
     }

     # -- Private functions --
     # After payment is complete, the gateway will invoke this URL asynchronously
     private function getNotifyUrl($orderId)
     {
          return parent::getCallbackUrl('/callback/process/' . $this->getPlatformCode() . '/' . $orderId);
     }

     ## After payment is complete, the gateway will send redirect back to this URL
     private function getReturnUrl($orderId)
     {
          return parent::getCallbackUrl('/callback/browser/success/' . $this->getPlatformCode() . '/' . $orderId);
     }

     ## Format the amount value for the API
     protected function convertAmountToCurrency($amount)
     {
          return number_format($amount, 2, '.', '');
     }
}
