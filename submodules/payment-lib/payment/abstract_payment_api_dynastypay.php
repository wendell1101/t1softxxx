<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';
/**
 * DYNASTYPAY
 *
 *
 * * DYNASTYPAY_ALIPAY_PAYMENT_API, ID: 5491
 * * DYNASTYPAY_WEIXIN_PAYMENT_API, ID: 5492
 * * DYNASTYPAY_UNIONPAY_PAYMENT_API, ID: 5493
 * * DYNASTYPAY_QUICKPAY_PAYMENT_API, ID: 5494
 *
 * Required Fields:
 *
 * * URL
 * * Account - Merchant ID
 *
 * Field Values:
 *
 * * Extra Info:
 * > {
 * >    "HashKey" : "## HashKey  ##",
 * >	   "HashIV" : "## HashIV ##"
 * > }
 *http://www.dynastypays.com/AliPayment.php
 *http://www.dynastypays.com/WecahtPayment.php
 *http://www.dynastypays.com/YsfPayment.php
 *http://www.dynastypays.com/UnionPayment.php
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */

abstract class Abstract_payment_api_dynastypay extends Abstract_payment_api
{
     const PAYTYPE_ALIPAY = 'AliPayment.php'; #支付寶
     const PAYTYPE_WEIXIN = 'WecahtPayment.php'; #微信
     const PAYTYPE_UNIONPAY = 'UnionPayment.php'; #銀聯掃碼
     const PAYTYPE_QUICKPAY = 'YsfPayment.php'; #銀聯快捷
     const RESULT_CODE_SUCCESS = "SUCCESS";
     const CALLBACK_SUCCESS = '1';
     const RETURN_SUCCESS_CODE = 'SUCCESS';
     public function __construct($params = null)
     {
          parent::__construct($params);
     }

     # Implement these for specific pay type
     abstract protected function configParams(&$params, $direct_pay_extra_info);
     abstract protected function processPaymentUrlForm($params);

     public function getSecretInfoList() {
          $secretsInfo = array('live_key', 'live_secret', 'sandbox_key', 'sandbox_secret', 'HashKey');
          return $secretsInfo;
     }

     public function generatePaymentUrlForm($orderId, $playerId, $amount, $orderDateTime, $playerPromoId = null, $enabledSecondUrl = false, $bankId = null)
     {

          $order = $this->CI->sale_order->getSaleOrderById($orderId);

          $params = array();
          $params['HashKey'] = $this->getSystemInfo('HashKey'); #厂商 HashKey
          $params['HashIV'] = $this->getSystemInfo('HashIV'); #厂商 HashIV
          $params['MerTradeID'] = $order->secure_id; #店家交易编号
          $params['MerProductID'] = $orderId; #店家商品代号
          $params['MerUserID'] = $playerId; #店家消费者 ID
          $params['Amount'] = $amount; #交易金额
          $params['TradeDesc'] = "-"; #交易描述
          $params['ItemName'] = "deposit"; #商品名称
          $params['VerifyCode'] = $this->sign($params);

          $this->CI->utils->debug_log("=====================dynastypay generatePaymentUrlForm", $params);

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

     # Callback URI: /callback/fixed_process/<payment_api_id>
     public function getOrderIdFromParameters($flds)
     {
          if (isset($flds['MerTradeID'])) {
               $this->CI->load->model(array('sale_order'));
               $order = $this->CI->sale_order->getSaleOrderBySecureId($flds['MerTradeID']);
               return $order->id;
          } else {
               $this->utils->debug_log('=====================dynastypay callbackOrder cannot get any order_id when getOrderIdFromParameters', $flds);
               return;
          }
     }
     private function callbackFrom($source, $orderId, $params, $response_result_id)
     {
          $result = array('success' => false, 'next_url' => null, 'message' => lang('error.payment.failed'));
          $order = $this->CI->sale_order->getSaleOrderById($orderId);
          $processed = false;

          $this->CI->utils->debug_log("=====================dynastypay callbackFrom $source params", $params);

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
               if (isset($params['MerTradeID'])) {
                    $this->CI->sale_order->updateExternalInfo($order->id, $params['MerTradeID'], '', null, null, $response_result_id);
               }

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
     private function checkCallbackOrder($order, $fields, &$processed = false)
     {
          $requiredFields = array('RtnCode', 'MerTradeID', 'MerUserID', 'Amount', 'Validate');

          foreach ($requiredFields as $f) {
               if (!array_key_exists($f, $fields)) {
                    $this->writePaymentErrorLog("=====================dynastypay checkCallbackOrder Missing parameter: [$f]", $fields);
                    return false;
               }
          }

          $status = $fields['RtnCode'];
          $amount = $fields['Amount'];
          $secure_id = $fields['MerTradeID'];

          # is signature authentic?
          if (!$this->validateSign($fields)) {
               $this->writePaymentErrorLog('=====================dynastypay checkCallbackOrder Signature Error', $fields);
               return false;
          }
          $processed = true; # processed is set to true once the signature verification pass
          if ($status != self::CALLBACK_SUCCESS) {
               $this->writePaymentErrorLog("======================dynastypay checkCallbackOrder Payment status is not success", $fields);
               return false;
          }

          if ($amount != $this->convertAmountToCurrency($order->amount)) {
               $this->writePaymentErrorLog("======================onepay checkCallbackOrder Payment amount is wrong, expected [$order->amount]", $fields);
               return false;
          }

          if ($secure_id != $order->secure_id) {
               $this->writePaymentErrorLog("======================onepay checkCallbackOrder order IDs do not match, expected [$order->secure_id]", $fields);
               return false;
          }

          # everything checked ok
          return true;
     }

     public function directPay($order = null)
     {
          return array('success' => false); # direct pay not supported by this API
     }

     protected function sign($params)
     {
          # MD5(MerTradeID=店家交易编号&MerProductID=店家商品代号&MerUserID=店家消费者 ID&Amount=交易金 额&ValidateKey=商户验证码)
          $signStr = $this->createSignStr($params);
          $sign = md5($signStr);
          return $sign;
     }

     protected function validateSign($params)
     {
          # MD5("ValidateKey=ASDWDWDF&HashKey=FEFRGFEFWEF&RtnCode=1&TradeID=20151202001&UserID=Karl01&Money=30")
          $signStr = $this->createSignStr($params, true);
          $sign = md5($signStr);

          return $sign == $params['Validate'];
     }
     protected function createSignStr($params, $validate = false)
     {
          $signStr = '';
          if (!$validate) {
               $signStr =
                    'MerTradeID=' .
                    $params['MerTradeID'] .
                    '&MerProductID=' .
                    $params['MerProductID'] .
                    '&MerUserID=' .
                    $params['MerUserID'] .
                    '&Amount=' .
                    $params['Amount'] .
                    '&ValidateKey=' .
                    $this->getSystemInfo('key');
          } else {
               $signStr =
                    'ValidateKey=' .
                    $this->getSystemInfo('key') .
                    '&HashKey=' .
                    $this->getSystemInfo('HashKey') .
                    '&RtnCode=' .
                    $params['RtnCode'] .
                    '&TradeID=' .
                    $params['MerTradeID'] .
                    '&UserID=' .
                    $params['MerUserID'] .
                    '&Money=' .
                    $params['Amount'];
          }
          return $signStr;
     }

     ## Format the amount value for the API
     protected function convertAmountToCurrency($amount)
     {
          return number_format($amount, 0, '.', '');
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
     protected function getReturnUrl($orderId)
     {
          return parent::getCallbackUrl('/callback/fixed_process/' . $this->getPlatformCode());
     }
}
