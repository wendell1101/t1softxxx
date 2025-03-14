<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';
/**
 * SDPAY
 *
 * * SDPAY_BANKCARD_PAYMENT_API, ID: 5500
 * * SDPAY_UNIONPAY_PAYMENT_API, ID: 5501
 * * SDPAY_QUICKPAY_PAYMENT_API, ID: 5502
 * *
 *
 * Required Fields:
 * * Account
 * * URL
 * * Extra Info
 *
 * Field Values:
 * * Account: ## Merchant ID ##
 * * URL: https://apimt.pr0pay.com/deposit/merchant/{## Merchant ID ##}/transaction
 * * Extra Info:
 * > {
 * >    "sdpay_pub_key": "## Platform Public Key ##",
 * >    "sdpay_priv_key": "## Merchant Private Key ##",
 * >    "use_usd_currency" : true
 * > }
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
abstract class Abstract_payment_api_sdpay extends Abstract_payment_api
{
     const BANK_CARD_PAY = 'BANK_CARD_PAY'; // P2P
     const UNIONPAY_QRCODE_PAY = 'UNIONPAY_QRCODE_PAY';
     const QUICK_PAY = 'QUICK_PAY';

     const WECHAT_QRCODE_PAY = 'WECHAT_QRCODE_PAY';
     const ALIPAY_QRCODE_PAY = 'ALIPAY_QRCODE_PAY';
     const B2C_CREDIT_PAY = 'B2C_CREDIT_PAY';
     const B2C_DEBIT_PAY = 'B2C_DEBIT_PAY';
     const B2B_PAY = 'B2B_PAY';
     const WECHAT_H5_PAY = 'WECHAT_H5_PAY';
     const ALIPAY_H5_PAY = 'ALIPAY_H5_PAY';
     const ALIPAY_BANK_CARD_PAY = 'ALIPAY_BANK_CARD_PAY';

     const RESULT_CODE_SUCCESS = 2;
     const RESULT_CODE_FINAL = 5; //結算也是成功的一種
     const SERVER_CALLBACK_SUCCESS = "success";

     public function __construct($params = null)
     {
          parent::__construct($params);
     }

     # Implement these to specify pay type
     abstract protected function configParams(&$params, $direct_pay_extra_info);
     abstract protected function processPaymentUrlForm($params);

     public function getSecretInfoList() {
        $secretsInfo = array('live_key', 'live_secret', 'sandbox_key', 'sandbox_secret', 'sdpay_pub_key', 'sdpay_priv_key');
        return $secretsInfo;
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

          $params['merchantCode'] = $this->getSystemInfo('account');
          $params['intoAmount'] = $this->convertAmountToCurrency($amount, $order->created_at);
          $this->configParams($params, $order->direct_pay_extra_info); // get $params['paymentTypeCode'] = "";
          $params['bankCode'] = $params['bankCode'] ?: null;
          $params['asyncURL'] = $this->getNotifyUrl($orderId);
          $params['syncURL'] = $this->getReturnUrl($orderId);
          $params['requestTime'] = date_format($orderDateTime, "YmdHis");
          $params['serialNumber'] = $order->secure_id;
          $params['userCode'] = $playerId;
          $params['content'] = $this->sign($params, $params['merchantCode'] . $params['intoAmount']);

          $this->CI->utils->debug_log("=====================sdpay generatePaymentUrlForm content :" . $params['content'], $params);

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

     protected function processPaymentUrlFormRedirect($params)
     {
          $this->_custom_curl_header = ["accept:application/json"];
          $url = $this->getSystemInfo('url');
          $response = $this->submitPostForm($url, $params, false, $params['serialNumber']);
          $decoded = json_decode($response, true);

          $this->CI->utils->debug_log('=====================sdpay processPaymentUrlFormRedirect received response', $response);
          $this->CI->utils->debug_log('=====================sdpay processPaymentUrlFormRedirect response json to array', $decoded);

          if (isset($decoded['status']) && $decoded['status'] == 'success') {
               return array(
                    'success' => true,
                    'type' => self::REDIRECT_TYPE_URL,
                    'url' => $decoded['results']
               );
          } elseif (isset($decoded['status']) && $decoded['status'] != 'success') {
               return array(
                    'success' => false,
                    'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
                    'message' => $decoded['message']
               );
          } else {
               return array(
                    'success' => false,
                    'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
                    'message' => lang('Invalidate API response')
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

          $this->CI->utils->debug_log("=====================sdpay callbackFrom $source params", $params);

          if ($source == 'server') {
               $callback_params = json_decode($params['content'], true);

               $content = json_decode($callback_params['results'])->securityCode;
               $params = $this->sdpayDecodeResponse($content);
               $this->CI->utils->debug_log("=====================sdpay callbackFrom $source decode params", $params);
               $response = array();
               if ($params) {
                    $response = array(
                         'code' => '200',
                         'message' => '回調失敗',
                         'status' => 'error',
                         'results' => $this->sign(
                              array(
                                   'serialNumber' => $params['serialNumber'],
                                   'merchantCode' => $params['merchantCode'],
                                   'orderStatus' => $params['orderStatus']
                              ),
                              $params['merchantCode'] . $params['serialNumber'] . $params['orderStatus']
                         )
                    );
               }
               if ($callback_params['status'] != self::SERVER_CALLBACK_SUCCESS) {
                    $this->writePaymentErrorLog("======================sdpay checkCallbackOrder Payment status is not success. orderStatusCode: " . $callback_params['status'], $callback_params);
                    $result['return_error'] = json_encode($response);
                    $this->utils->debug_log('==========================sdpay callback error response', $result);
                    return $result;
               }
               if (!$order || !$this->checkCallbackOrder($order, $params, $processed)) {
                    $result['return_error'] = json_encode($response);
                    $this->utils->debug_log('==========================sdpay callback error response', $result);
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
               if (isset($params['serialNumber'])) {
                    $this->CI->sale_order->updateExternalInfo($order->id, $params['serialNumber'], '', null, null, $response_result_id);
               }

               if ($source == 'browser') {
                    $success = $this->CI->sale_order->browserCallbackSaleOrder($order->id, 'auto broswer callback ' . $this->getPlatformCode(), false);
               } elseif ($source == 'server') {
                    $this->approveSaleOrder($order->id, 'auto server callback ' . $this->getPlatformCode(), false);
               }
          }

          $result['success'] = $success;
          if ($processed) {
               $response = array(
                    'code' => '200',
                    'message' => '接受成功',
                    'status' => 'success',
                    'results' => $this->sign(
                         array(
                              'serialNumber' => $params['serialNumber'],
                              'merchantCode' => $params['merchantCode'],
                              'orderStatus' => $params['orderStatus']
                         ),
                         $params['merchantCode'] . $params['serialNumber'] . $params['orderStatus']
                    )
               );
               $result['message'] = json_encode($response);
               $this->utils->debug_log('==========================sdpay callback success response', $result);
          } else {
               $result['return_error'] = json_encode($response);
               $this->utils->debug_log('==========================sdpay callback error response', $result);
          }

          if ($source == 'browser') {
               $result['next_url'] = $this->getPlayerBackUrl();
               $result['go_success_page'] = true;
          }

          return $result;
     }

     private function checkCallbackOrder($order, $fields, &$processed = false)
     {
          $requiredFields = array('merchantCode', 'serialNumber', 'orderStatus', 'intoAmount');
          $status = $fields['orderStatus'];
          $secure_id = $fields['serialNumber'];
          $account = $fields['merchantCode'];
          $amount = $fields['intoAmount'];

          foreach ($requiredFields as $f) {
               if (!array_key_exists($f, $fields)) {
                    $this->writePaymentErrorLog("=====================sdpay checkCallbackOrder Missing parameter: [$f]", $fields);
                    return false;
               }
          }

          # is signature authentic?
          if (!$this->validateSign($fields)) {
               $this->writePaymentErrorLog('=====================sdpay checkCallbackOrder Signature Error', $fields);
               return false;
          }

          $processed = true; # processed is set to true once the signature verification pass
          if ($account != $this->getSystemInfo('account')) {
               $this->writePaymentErrorLog("======================sdpay checkCallbackOrder merchantCode do not match expected :" . $this->getSystemInfo('account'), $fields);
               return false;
          }

          if ($secure_id != $order->secure_id) {
               $this->writePaymentErrorLog("======================sdpay checkCallbackOrder order IDs do not match, expected [$order->secure_id]", $fields);
               return false;
          }

          if ($status != self::RESULT_CODE_SUCCESS && $status != self::RESULT_CODE_FINAL) {
               $this->writePaymentErrorLog("======================sdpay checkCallbackOrder Payment status is not success. orderStatusCode: [$status]", $fields);
               return false;
          }
          $check_amount = $this->convertAmountToCurrency($order->amount, $order->created_at);
          if ($amount != $check_amount) {
               $this->writePaymentErrorLog("=========================sdpay checkCallbackOrder payment amount is wrong [ RMB: $amount ], expected => [ RMB: $check_amount ]", $fields);
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
     protected function sign($params, $signatureSrc)
     {
          $signatureKey = $this->create_send_privatesign($signatureSrc, $this->getPrivKey());
          $params['signature'] = $signatureKey;
          $params['securityCode'] = json_encode($params);
          $data1 = $this->create_send_publicsign($params['securityCode'], $this->getPubKey());
          $des['securityCode'] = $data1;
          $sign = base64_encode(json_encode($des));
          return $sign;
     }

     protected function validateSign($params)
     {
          $valid = false;
          $signSource = $params["merchantCode"] . $params["serialNumber"] . $params["orderStatus"];
          $signatureKey = $params["signature"];
          $ok = openssl_verify($signSource, base64_decode($signatureKey), $this->getPubKey(), OPENSSL_ALGO_MD5);
          if ($ok == 1) {
               $valid = true;
          }
          return $valid;
     }

     public function sdpayDecodeResponse($res)
     {
          $content = base64_decode($res);
          $decrypted = "";
          $priv_key = $this->getPrivKey();
          $decrypted = $this->private_decrypt(base64_decode($content), $priv_key);
          return json_decode($decrypted, true);
     }

     protected function create_send_privatesign($args, $private_key)
     {
          $signStr = $args;
          $pkeyid = openssl_get_privatekey($private_key);
          openssl_sign($signStr, $sign_info, $pkeyid, OPENSSL_ALGO_MD5);
          $sign = base64_encode($sign_info);
          return $sign;
     }

     protected function create_send_publicsign($data, $public_key)
     {
          $encryptData = "";
          foreach (str_split($data, 117) as $chunk) {
               if (openssl_public_encrypt($chunk, $encrypted, $public_key)) {
                    $encryptData .= $encrypted;
               }
          }
          return base64_encode($encryptData);
     }

     protected function private_decrypt($data, $private_key)
     {
          $content_tmp = "";
          foreach (str_split($data, 128) as $chunk) {
               if (openssl_private_decrypt($chunk, $decrypted, $private_key)) {
                    $content_tmp .= $decrypted;
               }
          }
          return $content_tmp;
     }

     protected function getPubKey()
     {
          $sdpay_pub_key = $this->getSystemInfo('sdpay_pub_key');
          $pub_key = '-----BEGIN PUBLIC KEY-----' . PHP_EOL . chunk_split($sdpay_pub_key, 64, PHP_EOL) . '-----END PUBLIC KEY-----' . PHP_EOL;
          return openssl_pkey_get_public($pub_key);
     }

     protected function getPrivKey()
     {
          $sdpay_priv_key = $this->getSystemInfo('sdpay_priv_key');
          $priv_key = '-----BEGIN RSA PRIVATE KEY-----' . PHP_EOL . chunk_split($sdpay_priv_key, 64, PHP_EOL) . '-----END RSA PRIVATE KEY-----' . PHP_EOL;

          return $priv_key;
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

     protected function convertAmountToCurrency($amount, $requestDateTime)
     {
          if ($this->getSystemInfo('use_usd_currency')) {
               if (is_string($requestDateTime)) {
                    $requestDateTime = DateTime::createFromFormat('Y-m-d H:i:s', $requestDateTime);
               }
               $sqlDateTime = $this->utils->getTimeForMysql($requestDateTime);
               $amount = $this->gameAmountToDBByCurrency($amount, $sqlDateTime, 'USD', 'CNY');
               $this->CI->utils->debug_log("=====================sdpay currency requestDateTime (getTimeForMysql) :" . $sqlDateTime);
          }

          $convert_multiplier = $this->getSystemInfo('convert_multiplier', 1);
          return number_format($amount * $convert_multiplier, 2, '.', '');
     }

     protected function getBankListInfoFallback()
     {
          return array(
               array('label' => '中国农业银行', 'value' => 'ABC'),
               array('label' => '广东发展银行', 'value' => 'GDB'),
               array('label' => '浙江泰隆商业银行', 'value' => 'ZJTLCB'),
               array('label' => '大连银行', 'value' => 'BODL'),
               array('label' => '深圳发展银行', 'value' => 'SDB'),
               array('label' => '重庆银行', 'value' => 'CQCB'),
               array('label' => '中国银行', 'value' => 'BOC'),
               array('label' => '上海农村商业银行', 'value' => 'SRCB'),
               array('label' => '江苏银行', 'value' => 'JSB'),
               array('label' => '招商银行', 'value' => 'CMB'),

               array('label' => '中信银行', 'value' => 'CITIC'),
               array('label' => '兴业银行', 'value' => 'CIB'),
               array('label' => '中国光大银行', 'value' => 'CEB'),
               array('label' => '中国邮政储蓄银行', 'value' => 'PSBC'),
               array('label' => '建设银行', 'value' => 'CCB'),
               array('label' => '上海银行', 'value' => 'BOS'),
               array('label' => '天津银行', 'value' => 'TCCB'),
               array('label' => '浙商银行', 'value' => 'CZB'),
               array('label' => '平安银行', 'value' => 'PINAN'),
               array('label' => '北京农村商业银行', 'value' => 'BJRCB'),

               array('label' => '厦门银行', 'value' => 'XMCCB'),
               array('label' => '交通银行', 'value' => 'BOCOM'),
               array('label' => '华夏银行', 'value' => 'HXB'),
               array('label' => '广州银行', 'value' => 'GZCB'),
               array('label' => '中国民生银行', 'value' => 'CMBC'),
               array('label' => '宁波银行', 'value' => 'NBCB'),
               array('label' => '工商银行', 'value' => 'ICBC'),
               array('label' => '杭州银行', 'value' => 'HCCB'),
               array('label' => '上海浦东发展银行', 'value' => 'SPDB'),
               array('label' => '台州银行', 'value' => 'TZB'),

               array('label' => '北京银行', 'value' => 'BCCB'),
               array('label' => '济宁银行', 'value' => 'JNB'),
               array('label' => '南京银行', 'value' => 'NJCB'),
               array('label' => '东亚银行', 'value' => 'BEA'),
               array('label' => '广发银行', 'value' => 'CGB'),
               array('label' => '浦发银行', 'value' => 'SPDB_'),
               array('label' => '渤海银行', 'value' => 'CBHB'),
               array('label' => '东莞银行', 'value' => 'BOD'),
               array('label' => '恒丰银行', 'value' => 'EGB')
          );
     }
}
