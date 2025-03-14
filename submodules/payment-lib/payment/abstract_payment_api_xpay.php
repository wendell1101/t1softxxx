<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * Xpay
 *
 * * XPAY_PAYMENT_API, ID: 5461
 * * XPAY_WITHDRAWAL_PAYMENT_API, ID: 5472
 * *
 * Required Fields:
 * * Account
 * * URL
 * * ApiKey
 *
 * Field Values:
 * * Account: ## Merchant ID ##
 * * URL: https://www.paymentonline515.com/payment.php
 * * URL: https://www.paymentonline515.com/withdrawal.php
 * * ApiKey: ## Api Key ##
 *  * * Extra Info:
 * > {
 * >    "currency": "THB",
 * > }
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */

abstract class Abstract_payment_api_xpay extends Abstract_payment_api
{
     const XPAY_ALIPAY = 'ALI'; //支付宝
     const XPAY_ALIPAY_H5 = 'ALIH5'; //支付宝直连
     const XPAY_QQPAY = 'QQPAY'; //QQ 扫码
     const XPAY_QQPAY_H5 = 'QQH5'; //QQ 直连
     const XPAY_WECHAT = 'WCP'; //微信支付
     const XPAY_WECHAT_BARCODE = 'WCB'; //微信条码
     const XPAY_WECHAT_UNION_QRCODE = 'MUP'; //银联扫码
     const XPAY_WECHAT_UNION_FASTPAY = 'DUP'; //银联快捷
     const XPAY_WECHAT_UNION_H5 = 'HUP'; //银联闪付
     const XPAY_WECHAT_JINGDONG_QRCODE = 'JDPAY'; //京东扫码
     const XPAY_WECHAT_JINGDONG_H5 = 'JDH5'; //京东直连

     const TRANSACTION_SUCCESS = '000';
     const TRANSACTION_FAIL = '111';
     const TRANSACTION_PENDING = '001';
     const TRANSACTION_BANK_PAYMENT_SUCCESS = '002';
     const TRANSACTION_BANK_PAYMENT_FAIL = '003';
     const TRANSACTION_INVALID_1 = '006';
     const TRANSACTION_INVALID_2 = '007';

     const FUNDOUT_STATUS_SUCCESS = '000';
     const FUNDOUT_STATUS_FAIL = '111';
     const FUNDOUT_STATUS_PENDING = '001';
     const FUNDOUT_STATUS_REJECTED = '112';

     const RETURN_SUCCESS_CODE = 'SUCCESS';

     private $dataEncrypt;

     public function __construct($params = null)
     {
          parent::__construct($params);
          $this->dataEncrypt = new DataEncrypt();
     }

     abstract protected function configParams(&$params, $direct_pay_extra_info);
     abstract protected function processPaymentUrlForm($params);

     public function generatePaymentUrlForm($orderId, $playerId, $amount, $orderDateTime, $playerPromoId = null, $enabledSecondUrl = true, $bankId = null)
     {
          if ($this->shouldRedirect($enabledSecondUrl)) {
               $url = $this->CI->utils->getPaymentUrl($this->getSystemInfo('second_url'), $this->getPlatformCode(), $amount, $playerId, $playerPromoId, false, $bankId, $orderId);
               $result = array(
                    'success' => true,
                    'type' => self::REDIRECT_TYPE_URL,
                    'url' => $url
               );
               return $result;
          }

          $order = $this->CI->sale_order->getSaleOrderById($orderId);
          $this->configParams($params, $order->direct_pay_extra_info);

          $data = array(
               'MerchantID' => $this->getSystemInfo("account"), // 7-letter Merchant ID in Xpay
               'CustID' => $playerId,
               'CustIP' => $this->getSystemInfo('send_cusip') ? $this->getClientIP() : null, //客户下单域名及IP 可空 例: 127.1.2.3
               'Curr' => $this->getSystemInfo('currency'), //3-letter currency code according to ISO 4217
               'Amount' => $this->convertAmountToCurrency($amount), // 例: 10.00
               'RefID' => $orderId,
               'TransTime' => $orderDateTime->format('Y-m-d H:i:s'), //YYYY-MM-DD HH:MM:SS
               'ReturnURL' => $this->getReturnUrl($orderId), //商户回调地址
               'RequestURL' => $this->getNotifyUrl($orderId), //商户通知地址
               'BankCode' => $params['subIssuingBank'] ?: null
          );
          $ganerateKeys = array('MerchantID', 'CustID', 'CustIP', 'Curr', 'Amount', 'RefID', 'TransTime', 'ReturnURL', 'RequestURL', 'BankCode');

          $params['Data'] = $this->generateDataStr($data, $ganerateKeys);
          $params['Remarks'] = "";
          $params['EncryptText'] = $this->generateEncryptText($data, $ganerateKeys, $params['Remarks']);

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

     protected function processPaymentUrlFormUrl($params)
     {
          $response = $this->submitPostForm($this->getSystemInfo('url'), $params, false, $params['TransID']);
          $this->CI->utils->debug_log('=====================xpay processPaymentUrlFormUrl received response', $response);
          $response = json_decode($response, true);
          $this->CI->utils->debug_log('=====================xpay processPaymentUrlFormUrl json to array', $response);

          if ($response['result'] && isset($response['data']['payment_code'])) {
               return array(
                    'success' => true,
                    'type' => self::REDIRECT_TYPE_URL,
                    'url' => $this->getSystemInfo('reponse_url') . $response['data']['payment_code']
               );
          } elseif (!$response['result']) {
               return array(
                    'success' => false,
                    'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
                    'message' => 'Return code: ' . $response['errcode'] . '=> ' . $response['data']
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
          $result = array(
               'success' => false,
               'next_url' => null,
               'message' => lang('error.payment.failed')
          );
          $order = $this->CI->sale_order->getSaleOrderById($orderId);
          if (!$order) {
               return $result;
          }
          $processed = false;
          $xpayResposeData = array();
          $decryptStr = $this->dataEncrypt->DecryptData($params['Data']);
          parse_str($decryptStr, $xpayResposeData);
          $result['message'] = $xpayResposeData['TransID'] . "||" . $xpayResposeData['ValidationKey'];

          if ($xpayResposeData['EncryptText'] != $params['EncryptText']) {
               return $result;
          }
          $this->CI->utils->debug_log("=====================xpay callbackFrom $source params", $params);

          if ($source == 'server') {
               $raw_post_data = file_get_contents('php://input', 'r');
               $this->CI->utils->debug_log("=====================xpay raw_post_data", $raw_post_data);
               parse_str($raw_post_data, $params);
               $this->CI->utils->debug_log("=====================xpay json_decode params", $params);
               if (!$order || !$this->checkCallbackOrder($order, $xpayResposeData, $processed)) {
                    $result['return_error'] = $xpayResposeData['TransID'] . "||" . $xpayResposeData['ValidationKey'];
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
               $this->CI->sale_order->updateExternalInfo($order->id, $xpayResposeData['TransID'], null, null, null, $response_result_id);
               if ($source == 'browser') {
                    $success = $this->CI->sale_order->browserCallbackSaleOrder($order->id, 'auto broswer callback ' . $this->getPlatformCode(), false);
               } elseif ($source == 'server') {
                    $this->approveSaleOrder($order->id, 'auto server callback ' . $this->getPlatformCode(), false);
               }
          }

          $result['success'] = $success;
          if ($processed) {
               $result['message'] = $xpayResposeData['TransID'] . "||" . $xpayResposeData['ValidationKey'];
          } else {
               $result['return_error'] = $xpayResposeData['TransID'] . "||" . $xpayResposeData['ValidationKey'];
          }

          if ($source == 'browser') {
               $result['next_url'] = $this->getPlayerBackUrl();
               $result['go_success_page'] = true;
          }
          return $result;
     }

     protected function convertAmountToCurrency($amount)
     {
          return number_format($amount, 2, '.', '');
     }

     private function getNotifyUrl($orderId)
     {
          return parent::getCallbackUrl('/callback/process/' . $this->getPlatformCode() . '/' . $orderId);
     }

     protected function getReturnUrl($orderId)
     {
          return parent::getCallbackUrl('/callback/browser/success/' . $this->getPlatformCode() . '/' . $orderId);
     }

     private function checkCallbackOrder($order, $fields, &$processed = false)
     {
          $requiredFields = array('RefID', 'Curr', 'Amount', 'Status', 'TransID', 'ValidationKey');

          foreach ($requiredFields as $f) {
               if (!array_key_exists($f, $fields)) {
                    $this->writePaymentErrorLog("=====================xpay missing parameter: [$f]", $fields);
                    return false;
               }
          }

          if (!$fields['Status'] || ($fields['Status'] != self::TRANSACTION_SUCCESS && $fields['Status'] != self::TRANSACTION_BANK_PAYMENT_SUCCESS)) {
               $this->writePaymentErrorLog("=====================xpay Payment Transaction Status fail", $fields);
               return false;
          }

          if ($this->convertAmountToCurrency($order->amount) != floatval($fields['Amount'])) {
               $this->writePaymentErrorLog("=====================xpay Payment amounts do not match, expected [$order->amount]", $fields);
               return false;
          }

          $lastAmount = abs($this->convertAmountToCurrency($order->amount) - floatval($fields['Amount']));
          if ($lastAmount >= 1) {
               $this->writePaymentErrorLog("=====================xpay Payment payAmount do not match, expected [$order->amount]", $fields);
               return false;
          }

          $processed = true; # processed is set to true once the signature verification pass
          # everything checked ok
          return true;
     }

     public function directPay($order = null)
     {
          return array('success' => false);
     }

     private function generateDataStr($data, $generateKeys)
     {
          # MerchantID=Value&CustID=Value&CustIP=Value&Curr=Value&Amount=Value&RefID=Value5678&TransTime=Value&ReturnURL=Value&RequestURL=Value&BankCode=Value
          # For the null value just leave it empty
          $sbQuery = '';
          foreach ($generateKeys as $key) {
               $sbQuery .= "&$key=$data[$key]";
          }
          $sbQuery = substr($sbQuery, 1);
          $strQuery = $this->dataEncrypt->EncryptData($sbQuery);
          return $strQuery;
     }

     private function generateEncryptText($data, $generateKeys, $remark = null)
     {
          #send: [MerchantEncryptKey]:[MerchantID],[CustID],[CustIP],[Curr],[Amount],[RefID],[TransTime],[ReturnURL],[RequestURL],[BankCode],[Remarks]
          #get : [MerchantEncryptKey]:[RefID],[Curr],[Amount],[Status],[TransID],[ValidationKey]
          # Remark: For the null value just leave it empty between the commas
          $apiKey = $this->getSystemInfo('key');
          $encryptStr = "$apiKey:";
          foreach ($generateKeys as $key) {
               $encryptStr .= "$data[$key],";
          }
          $strTemp = $encryptStr . $remark;
          $encryptStr = strtoupper(md5(utf8_encode($strTemp)));
          return $encryptStr;
     }

     protected function getBankListInfoFallback()
     {
          $currencytype = $this->getSystemInfo('currency');
          switch ($currencytype) {
               case 'CNY':
                    return array(
                         array('label' => '中国农业银行', 'value' => 'ABC'),
                         array('label' => '交通银行', 'value' => 'BCOM'),
                         array('label' => '中国银行', 'value' => 'BOC'),
                         array('label' => '中国建设银行', 'value' => 'CCB'),
                         array('label' => '光大银行', 'value' => 'CEB'),
                         array('label' => '兴业银行', 'value' => 'CIB'),
                         array('label' => '中信银行', 'value' => 'CITIC'),
                         array('label' => '招商银行', 'value' => 'CMB'),
                         array('label' => '民生银行', 'value' => 'CMBC'),
                         array(
                              'label' => '中国邮政储蓄银行',
                              'value' => 'CPSRB'
                         ),
                         array('label' => '广东发展银行', 'value' => 'GDB'),
                         array('label' => '中国华夏银行', 'value' => 'HXB'),
                         array('label' => '工商银行', 'value' => 'ICBC'),
                         array('label' => '浦东发展银行', 'value' => 'SPDB'),
                         array('label' => '平安银行', 'value' => 'SZCB'),
                         array(
                              'label' => '北京农村商业银行',
                              'value' => 'BJRCB'
                         ),
                         array('label' => '北京银行', 'value' => 'BOBJ'),
                         array('label' => '南京银行', 'value' => 'NJB'),
                         array('label' => '上海银行', 'value' => 'SHB'),
                         array(
                              'label' => '上海农村商业银行',
                              'value' => 'SHRCC'
                         ),
                         array('label' => '宁波银行', 'value' => 'NBCB')
                    );
                    break;
               case 'THB':
                    return array(
                         array('label' => 'Krung Thai Bank', 'value' => 'KTB'),
                         array(
                              'label' => 'Siam Commercial Bank',
                              'value' => 'SCB'
                         ),
                         array('label' => 'Bangkok bank', 'value' => 'BBL'),
                         array('label' => 'Kasikorn Bank', 'value' => 'KBANK'),
                         array(
                              'label' => 'Krungsri Thai bank',
                              'value' => 'BAY'
                         ),
                         array('label' => 'Thai QR Payment', 'value' => 'THQR'),
                         array('label' => 'UOB Bank', 'value' => 'UOB')
                    );
                    break;
               case 'IDR':
                    return array(
                         array(
                              'label' => 'Virtual Account IDR',
                              'value' => 'IDRV'
                         )
                    );
                    break;
               case 'VND':
                    return array(
                         array('label' => 'Vietcombank', 'value' => 'VCB'),
                         array('label' => 'Vietinbank', 'value' => 'VTB'),
                         array(
                              'label' => 'VAsia Commercial Bank',
                              'value' => 'ACB'
                         ),
                         array('label' => 'Eximbank', 'value' => 'EXIM'),
                         array('label' => 'Sacombank', 'value' => 'SCM'),
                         array('label' => 'Techcombank', 'value' => 'TCB'),
                         array('label' => 'DongA Bank', 'value' => 'DAB'),
                         array('label' => 'BIDV Bank', 'value' => 'BIDV')
                    );
                    break;
               default:
                    break;
          }
     }
}

// Xpay DataEncrypt function
class DataEncrypt
{
     private $strSplit = array("", "g", "h", "G", "k", "g", "J", "K", "I", "h", "i", "j", "H");
     private $n = 0;
     private $strTemp = "";

     public function EncryptData($ToEncrypt)
     {
          //??
          if ($ToEncrypt != "" && $ToEncrypt != null) {
               //$temp = System.Text.Encoding.Default.GetBytes(ToEncrypt.Replace("\r\n", ""));
               //$temp = ord($ToEncrypt);
               //$ToEncrypt = utf8_encode("MerchantID=US00001&CustID=22006&Curr=CNY&Amount=1.00&RefID=1714521627&TransTime=2011-04-14 05:21:05&ReturnURL=http://www.payment-quickpay.com/QuickPay_Staging/MerchantReceive.aspx&RequestURL=&BankCode=&PGateway=");
               $ToEncrypt = utf8_encode($ToEncrypt);
               $ToEncrypt = str_Replace("\r\n", "", $ToEncrypt);

               //-- ASCII Table and Description -- http://www.asciitable.com/
               //$aryTemp = unpack("H*", $ToEncrypt);
               $aryTemp = unpack('C*', $ToEncrypt);

               $strTemp = implode(',', $aryTemp);
               $aryTemp = explode(',', $strTemp);

               //echo "count(aryTemp) = " . count($aryTemp) . "</br>";

               for ($i = 0; $i < count($aryTemp); $i++) {
                    //$number = $aryTemp[$i];  //??ASCII?
                    $number = (int) $aryTemp[$i];
                    //echo "number = " . $number . "</br>";
                    $this->EncryptToInt16($number);
                    $strEncryptedData = $this->EncryptToInt16($number);
                    //echo "[" . $i ."] = " . $strEncryptedData . "</br>";

                    $this->strTemp = $this->strTemp . $strEncryptedData;
               }
          }

          return $this->strTemp;
     }

     public function EncryptToInt16($number)
     {
          //??????
          //if (n == 12)
          //    n = 0;
          //n++;
          //return Convert.ToString(number, 16) + strSplit[n];
          if ($this->n == 12) {
               $this->n = 1;
          } else {
               $this->n++;
          }

          $mychar = "H";

          try {
               $mychar = $this->strSplit[$this->n];
          } catch (Exception $e) {
               $this->n = 1;
          }

          $mychar = $this->strSplit[$this->n];

          //return Convert.ToString(number, 16) + mychar;
          return strval(dechex($number) . $mychar);
     }

     public function DecryptData($ToDecrypt)
     {
          $this->strTemp = '';
          if ($ToDecrypt != "" && $ToDecrypt != null) {
               $ToDecrypt = str_replace("\r\n", "", $ToDecrypt);
               $ToDecrypt = str_replace(" ", ",", $ToDecrypt);
               $ToDecrypt = str_replace("g", ",", $ToDecrypt);
               $ToDecrypt = str_replace("G", ",", $ToDecrypt);
               $ToDecrypt = str_replace("H", ",", $ToDecrypt);
               $ToDecrypt = str_replace("h", ",", $ToDecrypt);
               $ToDecrypt = str_replace("K", ",", $ToDecrypt);
               $ToDecrypt = str_replace("k", ",", $ToDecrypt);
               $ToDecrypt = str_replace("J", ",", $ToDecrypt);
               $ToDecrypt = str_replace("j", ",", $ToDecrypt);
               $ToDecrypt = str_replace("I", ",", $ToDecrypt);
               $ToDecrypt = str_replace("i", ",", $ToDecrypt);
               $ToDecrypt = strtoupper($ToDecrypt);
               $aryTemp = explode(",", $ToDecrypt);

               for ($i = 0; $i < count($aryTemp); $i++) {
                    $number = (int) hexdec($aryTemp[$i]);
                    $strDecryptedData = $this->decryptToString($number);
                    $this->strTemp = $this->strTemp . $strDecryptedData;
               }
          }
          return $this->strTemp;
     }

     public function decryptToString($number)
     {
          return strval(chr($number));
     }

     private function EncryptToString($number)
     {
          //???ASCII??????
          //return Convert.ToChar(Convert.ToInt16(number, 16)).ToString();
          return $number;
     }
}
