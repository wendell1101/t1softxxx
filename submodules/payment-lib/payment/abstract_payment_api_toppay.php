<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';
/**
 * TOPPAY
 *
 *
 * * TOPPAY_UNIONPAY_PAYMENT_API, ID: 5546
 * * TOPPAY_ALIPAY_PAYMENT_API, ID: 5607
 * * TOPPAY_WEIXIN_PAYMENT_API, ID: 5608
 *
 * Required Fields:
 *
 * * URL
 * * Account - ## Merchant ID ##
 * * Key - ## API Key ##
 *
 * Field Values:
 *
 * * URL: http://api.toppay168.com/Pay_Index.html
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
abstract class Abstract_payment_api_toppay extends Abstract_payment_api
{
     const BANKCODE_UNIONPAY = "9015";
     const BANKCODE_ALIPAY = "9006";
     const BANKCODE_WEIXIN = "9002";

     const RETURN_SUCCESS_CODE = 'OK';
     const CALLBACK_SUCCESS = '00';

     public function __construct($params = null)
     {
          parent::__construct($params);
     }

     public function generatePaymentUrlForm($orderId, $playerId, $amount, $orderDateTime, $playerPromoId = null, $enabledSecondUrl = true, $bankId = null)
     {
          if ($this->shouldRedirect($enabledSecondUrl)) {
               $url = $this->CI->utils->getPaymentUrl($this->getSystemInfo('second_url'), $this->getPlatformCode(), $amount, $playerId, $playerPromoId, false, $bankId, $orderId);
               $result = array('success' => true, 'type' => self::REDIRECT_TYPE_URL, 'url' => $url);
               return $result;
          }

          $order = $this->CI->sale_order->getSaleOrderById($orderId);

          $params = array();
          $params['pay_memberid'] = $this->getSystemInfo("account");
          $params['pay_orderid'] = $order->secure_id;
          $params['pay_amount'] = $this->convertAmountToCurrency($amount);
          $params['pay_applydate'] = $orderDateTime->format('Y-m-d H:i:s');
          $this->configParams($params, $order->direct_pay_extra_info); // $params['pay_bankcode'];
          $params['pay_notifyurl'] = $this->getNotifyUrl($orderId);
          $params['pay_callbackurl'] = $this->getReturnUrl($orderId);
          $params['pay_md5sign'] = $this->sign($params);
          if ($this->getSystemInfo("pay_productname")) {
               $params['pay_productname'] = $this->getSystemInfo("pay_productname");
          }
          $this->CI->utils->debug_log('=====================toppay generatePaymentUrlForm params', $params);

          return $this->processPaymentUrlForm($params);
     }

     # Implement processPaymentUrlForm
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

          $this->CI->utils->debug_log("=====================toppay callbackFrom $source params", $params);

          if ($source == 'server') {
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
               $this->CI->sale_order->updateExternalInfo($order->id, $params['transaction_id'], '', null, null, $response_result_id);
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

     private function checkCallbackOrder($order, $fields, &$processed)
     {
          # does all required fields exist?
          $requiredFields = array('memberid', 'orderid', 'amount', 'returncode', 'sign');
          foreach ($requiredFields as $f) {
               if (!array_key_exists($f, $fields)) {
                    $this->writePaymentErrorLog("=========================toppay checkCallbackOrder missing parameter: [$f]", $fields);
                    return false;
               }
          }

          # is signature authentic?
          if (!$this->validateSign($fields)) {
               $this->writePaymentErrorLog("=========================toppay checkCallbackOrder Signature Error", $fields);
               return false;
          }

          $processed = true; # processed is set to true once the signature verification pass
          if ($fields['returncode'] != self::CALLBACK_SUCCESS) {
               $this->writePaymentErrorLog("=========================toppay checkCallbackOrder returncode was not successful", $fields);
               return false;
          }

          if ($fields['orderid'] != $order->secure_id) {
               $this->writePaymentErrorLog("=========================toppay checkCallbackOrder Order IDs do not match, expected [$expectedOrderId]", $fields);
               return false;
          }

          if ($fields['amount'] != $this->convertAmountToCurrency($order->amount)) {
               $this->writePaymentErrorLog("=========================toppay checkCallbackOrder amount is wrong, expected [$order->amount]", $fields);
               return false;
          }

          # everything checked ok
          return true;
     }

     public function directPay($order = null)
     {
          return array('success' => false); # direct pay not supported by this API
     }

     private function convertAmountToCurrency($amount)
     {
          return number_format($amount, 2, '.', '');
     }

     private function getNotifyUrl($orderId)
     {
          return parent::getCallbackUrl('/callback/process/' . $this->getPlatformCode() . '/' . $orderId);
     }

     private function getReturnUrl($orderId)
     {
          return parent::getCallbackUrl('/callback/browser/success/' . $this->getPlatformCode() . '/' . $orderId);
     }

     # -- signing --
     private function sign($params)
     {
          $signStr = $this->createSignStr($params);
          $sign = strtoupper(md5($signStr));
          return $sign;
     }

     private function validateSign($params)
     {
          $paramsToSing = array(
               'memberid' => $params['memberid'],
               'orderid' => $params['orderid'],
               'amount' => $params['amount'],
               'transaction_id' => $params['transaction_id'],
               'datetime' => $params['datetime'],
               'returncode' => $params['returncode']
          );
          return $params['sign'] == $this->sign($paramsToSing);
     }

     private function createSignStr($params)
     {
          // ksort($params);
          $signStr = '';
          foreach ($params as $key => $value) {
               if ($key == 'sign' || $key == 'pay_md5sign' || $key == 'attach') {
                    continue;
               }
               $signStr .= "$key=$value&";
          }
          return $signStr . "key=" . $this->getSystemInfo('key');
     }
}
