<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';
/**
 * GPAY
 *
 * * GPAY_ALIPAY_PAYMENT_API, ID: 5513
 * * GPAY_ALIPAY_H5_PAYMENT_API, ID: 5514
 *
 * *
 * Required Fields:
 * * Account
 * * Key
 * * URL
 * * Extra Info
 *
 * Field Values:
 * * Account: ## Merchant ID ##
 * * Key: ## Live Key ##
 * * URL: https://cfpay.xyz/inject/newOrderPayByAccesser
 * * Extra Info:
 * > {
 * >	    "gpay_auth_token": "",
 * >	    "gpay_iid": "",
 * >	    "gpay_app_id": "",
 * >	    "gpay_app_token":""
 * > }
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
abstract class Abstract_payment_api_gpay extends Abstract_payment_api
{
     const PAYTYPE_WEIXIN = '1';
     const PAYTYPE_ALIPAY = '2';
     const PAYTYPE_ALIPAY_H5 = '2';
     const PAYTYPE_UNIONPAY = '3';

     const RESULT_CODE_SUCCESS = "1000";
     const RESULT_CODE = array(
          '1000' => '此应用可以使用！',
          '1011' => '不存在此商户或者认证未通过',
          '1012' => '此应用处于暂停状态，不能使用',
          '1013' => '此应用已被删除，不能再使用',
          '1014' => '此应用不存在！',
          '1015' => '此订单号已使用，请更换新的订单号！',
          '1016' => '此金额通道目前拥塞，请提交其他充值金额！',
          '1017' => '参数值为空或者参数不全',
          '1018' => '数值不准确',
          '1019' => '签名不正确',
          '1020' => '此商户订单号已使用，请更换新的商户订单号！'
     );

     const CALLBACK_SUCCESS = '1';
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

          $params = array();
          $params['sid'] = $this->getSystemInfo("account");
          $params['authToken'] = $this->getSystemInfo('gpay_auth_token');
          $this->configParams($params, $order->direct_pay_extra_info); // $params['payType'];
          $params['appId'] = $this->getSystemInfo('gpay_app_id');
          $params['appToken'] = $this->getSystemInfo('gpay_app_token');
          $params['iid'] = $this->getSystemInfo('gpay_iid');
          $params['shopOrderNum'] = $order->secure_id;
          $params['point'] = $this->convertAmountToCurrency($amount);
          $params['skCallbackUrl'] = $this->getNotifyUrl($orderId);
          $params['sign'] = $this->sign($params);

          $this->CI->utils->debug_log('=====================gpay generatePaymentUrlForm params', $params);

          return $this->processPaymentUrlForm($params);
     }

     # Implement processPaymentUrlForm
     protected function processPaymentUrlFormRedirect($params)
     {
          return array(
               'success' => true,
               'type' => self::REDIRECT_TYPE_FORM,
               'url' => $this->getSystemInfo('url'),
               'params' => $params,
               'post' => true
          );
     }

     protected function processPaymentUrlFormPost($params)
     {
          $response = $this->submitPostForm($this->getSystemInfo('url'), $params, false, $params['shopOrderNum']);
          $response = json_decode($response, true);
          $this->CI->utils->debug_log('=====================gpay processPaymentUrlFormPost response', $response);

          if ($response['results'] == self::RESULT_CODE_SUCCESS && isset($response['payLink'])) {
               if ($this->validateSign($response)) {
                    return array(
                         'success' => true,
                         'type' => self::REDIRECT_TYPE_URL,
                         'url' => $response['payLink']
                    );
               } else {
                    return array(
                         'success' => false,
                         'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
                         'message' => lang('Validate Sign Error')
                    );
               }
          } elseif (isset($response['results'])) {
               return array(
                    'success' => false,
                    'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
                    'message' => 'Response result: [' . $response['results'] . ']' . $response['info']
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

     private function callbackFrom($source, $orderId, $params, $response_result_id)
     {
          $result = array('success' => false, 'next_url' => null, 'message' => lang('error.payment.failed'));
          $order = $this->CI->sale_order->getSaleOrderById($orderId);
          $processed = false;

          $this->CI->utils->debug_log("=====================gpay callbackFrom $source params", $params);

          if ($source == 'server') {
               if (!$order || !$this->checkCallbackOrder($order, $params, $processed)) {
                    $result['return_error'] = json_encode(['results' => '1019', 'info' => "回调失败"]);
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
               if (isset($params['orderNum'])) {
                    $this->CI->sale_order->updateExternalInfo($order->id, $params['orderNum'], null, null, null, $response_result_id);
               }

               if ($source == 'browser') {
                    $success = $this->CI->sale_order->browserCallbackSaleOrder($order->id, 'auto broswer callback ' . $this->getPlatformCode(), false);
               } elseif ($source == 'server') {
                    $this->approveSaleOrder($order->id, 'auto server callback ' . $this->getPlatformCode(), false);
               }
          }

          $result['success'] = $success;
          if ($processed) {
               $result['message'] = json_encode(['results' => self::RESULT_CODE_SUCCESS, 'info' => "OK"]);
          } else {
               $result['return_error'] = json_encode(['results' => '1000', 'info' => "OK"]);
          }

          if ($source == 'browser') {
               $result['next_url'] = $this->getPlayerBackUrl();
               $result['go_success_page'] = true;
          }

          return $result;
     }

     private function checkCallbackOrder($order, $fields, &$processed = false)
     {
          $requiredFields = array('appId', 'orderNum', 'shopOrderNum', 'point', 'status', 'sign');
          $status = $fields['status'];
          $amount = $fields['point'];
          $secure_id = $fields['shopOrderNum'];

          foreach ($requiredFields as $f) {
               if (!array_key_exists($f, $fields)) {
                    $this->writePaymentErrorLog("=====================gpay checkCallbackOrder Missing parameter: [$f]", $fields);
                    return false;
               }
          }

          # is signature authentic?
          if (!$this->validateSign($fields)) {
               $this->writePaymentErrorLog('=====================gpay checkCallbackOrder Signature Error', $fields);
               return false;
          }

          $processed = true; # processed is set to true once the signature verification pass
          if ($status != self::CALLBACK_SUCCESS) {
               $this->writePaymentErrorLog("======================gpay checkCallbackOrder Payment status is not success", $fields);
               return false;
          }

          if ($amount != $this->convertAmountToCurrency($order->amount)) {
               $this->writePaymentErrorLog("======================gpay checkCallbackOrder Payment amount is wrong, expected [$order->amount]", $fields);
               return false;
          }

          if ($secure_id != $order->secure_id) {
               $this->writePaymentErrorLog("======================gpay checkCallbackOrder order IDs do not match, expected [$order->secure_id]", $fields);
               return false;
          }

          # everything checked ok
          return true;
     }

     public function directPay($order = null)
     {
          return array('success' => false); # direct pay not supported by this API
     }

     # -- signing --
     protected function sign($params)
     {
          $signStr = $this->createSignStr($params);
          $sign = strtoupper(md5($signStr));
          return $sign;
     }

     protected function validateSign($params)
     {
          $signStr = $this->createSignStr($params);
          $sign = strtoupper(md5($signStr));

          return $sign == $params['sign'];
     }

     protected function createSignStr($params)
     {
          ksort($params);
          $signStr = '';
          foreach ($params as $key => $value) {
               if ($key == "signMethod" || $key == "signature" || $key == "sign") {
                    continue;
               }
               $signStr .= "$key=$value&";
          }
          $signStr = $signStr . "key=" . $this->getSystemInfo('key'); // wYU02v6794
          return $signStr;
     }

     # -- Private functions --
     protected function getNotifyUrl($orderId)
     {
          return parent::getCallbackUrl('/callback/process/' . $this->getPlatformCode() . '/' . $orderId);
     }

     protected function getReturnUrl($orderId)
     {
          return parent::getCallbackUrl('/callback/browser/success/' . $this->getPlatformCode() . '/' . $orderId);
     }

     protected function convertAmountToCurrency($amount)
     {
          return number_format($amount, 2, '.', '');
     }
}
