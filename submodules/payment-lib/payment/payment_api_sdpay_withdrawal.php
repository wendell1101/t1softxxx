<?php
require_once dirname(__FILE__) . '/abstract_payment_api_sdpay.php';
/**
 * SDPAY
 *
 * * SDPAY_WITHDRAWAL_PAYMENT_API, ID: 5504
 * *
 *
 * Required Fields:
 * * Account
 * * URL
 * * Extra Info
 *
 * Field Values:
 * * Account: ## Merchant ID ##
 * * URL: https://apimt.pr0pay.com/withdraw/merchant/{## Merchant ID ##}/transaction
 * * Extra Info:
 * > {
 * >    "sdpay_pub_key": "## Platform Public Key ##",
 * >    "sdpay_priv_key": "## Merchant Private Key ##"
 * > }
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_sdpay_withdrawal extends Abstract_payment_api_sdpay
{
     const WITHDRAWAL_INITIAL = 0;
     const WITHDRAWAL_PROCESSING = 1;
     const WITHDRAWAL_SUCCESS = 2;
     const WITHDRAWAL_FAIL = 3;
     const SERVER_CALLBACK_SUCCESS = "success";

     public function getPlatformCode()
     {
          return SDPAY_WITHDRAWAL_PAYMENT_API;
     }

     public function getPrefix()
     {
          return 'sdpay_withdrawal';
     }

     # Implement abstract function but do nothing
     protected function configParams(&$params, $direct_pay_extra_info)
     {
     }
     protected function processPaymentUrlForm($params)
     {
     }

     public function submitWithdrawRequest($bank, $accNum, $name, $amount, $transId)
     {
          $result = array('success' => false, 'message' => 'payment failed');

          if (!$this->isAllowWithdraw()) {
               $result['message'] = lang("Withdraw not allowed with this API");
               return $result;
          }
          if (!array_key_exists($bank, $this->getBankInfo())) {
               $this->utils->error_log("========================sdpay submitWithdrawRequest bank whose bankTypeId=[$bank] is not supported by sdpay");
               return array('success' => false, 'message' => 'Bank not supported by sdpay');
          }
          $params = $this->getWithdrawParams($bank, $accNum, $name, $amount, $transId);
          $this->CI->utils->debug_log('======================================sdpay submitWithdrawRequest params:', $params);
          if (isset($params['success'])) {
               if ($params['success'] == false) {
                    $result['message'] = $params['message'];
                    $this->utils->debug_log($result);
                    return $result;
               }
          }

          $url = $this->getSystemInfo('url');

          list($content, $response_result) = $this->submitPostForm($url, $params, false, $transId, true);
          $decodedResult = $this->decodeResult($content);
          $decodedResult['response_result'] = $response_result;

          $this->CI->utils->debug_log('======================================sdpay submitWithdrawRequest url: ', $url);
          $this->CI->utils->debug_log('======================================sdpay submitWithdrawRequest response', $response_result);
          $this->CI->utils->debug_log('======================================sdpay submitWithdrawRequest decoded Result', $decodedResult);

          return $decodedResult;
     }

     public function getWithdrawUrl()
     {
          return $this->getSystemInfo('url');
     }

     public function getWithdrawParams($bank, $accNum, $name, $amount, $transId)
     {
          $this->CI->load->model(array('wallet_model', 'player_model', 'playerbankdetails'));

          $bankInfo = $this->getBankInfo();
          $bankName = $bankInfo[$bank];

          $playerBankDetails = $this->CI->playerbankdetails->getBankDetailsByBankAccount($bank, $accNum);
          $this->utils->debug_log("Get playerBankDetails using [$bank] + [$accNum]", $playerBankDetails);
          if (!empty($playerBankDetails)) {
               $province = empty($playerBankDetails['province']) ? "11" : $playerBankDetails['province'];
               $city = empty($playerBankDetails['city']) ? "1100" : $playerBankDetails['city'];
               $bankBranch = empty($playerBankDetails['branch']) ? "NONE" : $playerBankDetails['branch'];
               $bankAddress = empty($playerBankDetails['bankAddress']) ? "NONE" : $playerBankDetails['bankAddress'];
          } else {
               $bankBranch = 'NONE';
               $province = '11';
               $city = '1100';
               $bankAddress = 'NONE';
          }
          $order = $this->CI->wallet_model->getWalletAccountByTransactionCode($transId);

          $params = array();
          $params['intoAccount'] = $accNum;
          $params['intoName'] = $name;
          $params['intoBank'] = $bankInfo[$bank]['name'];
          $params['intoBankBranch'] = $bankBranch;
          $params['intoBankCode'] = $bankInfo[$bank]['code'];
          $params['intoProvince'] = '11';
          $params['intoCity'] = '1100';
          $params['intoAmount'] = $this->convertAmountToCurrency($amount, $order['dwDateTime']);
          $params['serialNumber'] = $transId;
          $params['merchantCode'] = $this->getSystemInfo('account');
          $params['remark'] = 'staging withdrawal test';
          $params['asyncURL'] = $this->getNotifyUrl($transId);
          $params['requestTime'] = date("YmdHis");
          $params['content'] = $this->sign($params, $params['merchantCode'] . $params['intoAccount'] . $params['intoAmount']);

          $this->CI->utils->debug_log('=========================sdpay getWithdrawParams params', $params);
          return $params;
     }

     public function decodeResult($resultString, $queryAPI = false)
     {
          if (is_array($resultString) && isset($resultString['lock']) && isset($resultString['msg'])) {
               $result = array('success' => false, 'message' => $resultString['msg'], 'lock' => true);
               return $result;
          }
          $this->utils->debug_log("=========================sdpay decodeResult resultString", $resultString);

          $callback_params = json_decode($resultString, true);
          if ($callback_params['status'] != self::SERVER_CALLBACK_SUCCESS) {
               $message = 'Sdpay withdrawal status: ' . $callback_params['status'] . ', errorCode: ' . $callback_params['code'] . ', message: ' . $callback_params['message'];
               $result = array('success' => false, 'message' => $message);
               $this->writePaymentErrorLog("======================sdpay withdrawal status is not success. Status: " . $callback_params['status'], $callback_params);
               return $result;
          }
          $content = json_decode($callback_params['results'])->securityCode;
          $params = $this->sdpayDecodeResponse($content, true);

          $this->utils->debug_log("=========================sdpay withdrawal decodeResult", $params);

          if (!$queryAPI) {
               switch ($params['orderStatus']) {
                    case self::WITHDRAWAL_SUCCESS:
                         $message = 'code=> ' . $params['orderStatus'] . 'sdpay withdrawal Success.';
                         $result = array('success' => true, 'message' => $message);
                         break;
                    case self::WITHDRAWAL_PROCESSING:
                    case self::WITHDRAWAL_INITIAL:
                         $message = 'code=> ' . $params['orderStatus'] . 'sdpay withdrawal Pending.';
                         $result = array('success' => true, 'message' => $message);
                         break;
                    case self::WITHDRAWAL_FAIL:
                         $message = 'code=> ' . $params['orderStatus'] . 'sdpay withdrawal Fail.';
                         $result = array('success' => false, 'message' => $message, 'return_error' => $message, 'payment_fail' => true);
                         break;
                    default:
                         $message = 'sdpay withdrawal Fail.';
                         $result = array('success' => false, 'message' => $message, 'return_error' => $message);
                         break;
               }
          } else {
               switch ($params['orderStatus']) {
                    case self::WITHDRAWAL_SUCCESS:
                         $message = 'code=> ' . $params['orderStatus'] . 'sdpay withdrawal Success.';
                         $result = array('success' => true, 'message' => $message);
                         break;
                    case self::WITHDRAWAL_PROCESSING:
                    case self::WITHDRAWAL_INITIAL:
                         $message = 'code=> ' . $params['orderStatus'] . 'sdpay withdrawal Pending.';
                         $result = array('success' => false, 'message' => $message);
                         break;
                    case self::WITHDRAWAL_FAIL:
                         $message = 'code=> ' . $params['orderStatus'] . 'sdpay withdrawal Fail.';
                         $result = array('success' => false, 'message' => $message, 'return_error' => $message, 'payment_fail' => true);
                         break;
                    default:
                         $message = 'sdpay withdrawal Fail.';
                         $result = array('success' => false, 'message' => $message, 'return_error' => $message);
                         break;
               }
          }
          return $result;
     }

     public function callbackFromServer($transId, $params)
     {
          $response_result_id = parent::callbackFromServer($transId, $params);

          if (empty($params) || is_null($params)) {
               $raw_post_data = file_get_contents('php://input', 'r');
               $raw_post_data = explode('=', urldecode($raw_post_data));
               if (is_array($raw_post_data)) {
                    $params = json_decode($raw_post_data[1], true);
               } else {
                    $result = array('success' => false, 'message' => 'callback error');
                    return $result;
               }
          }
          $result = array('success' => false, 'message' => 'Payment failed');
          $params = json_decode($params['content'], true);
          $this->utils->debug_log("==========================sdpay withdrawal callbackFromServer callbackFromServer params", $params);

          $order = $this->CI->wallet_model->getWalletAccountByTransactionCode($transId);
          $content = json_decode($params['results'])->securityCode;
          $contentParams = $this->sdpayDecodeResponse($content);
          $this->utils->debug_log("=========================sdpay withdrawal callbackFromServer decodeResult", $contentParams);
          $response = array();
          if ($contentParams) {
               $response = array(
                    'code' => '200',
                    'message' => '操作失敗',
                    'status' => 'error',
                    'results' => $this->sign(
                         array(
                              'serialNumber' => $contentParams['serialNumber'],
                              'merchantCode' => $contentParams['merchantCode'],
                              'orderStatus' => $contentParams['orderStatus']
                         ),
                         $contentParams['merchantCode'] . $contentParams['serialNumber'] . $contentParams['orderStatus'] . 'error'
                    )
               );
          }
          if ($params['status'] != self::SERVER_CALLBACK_SUCCESS) {
               $result['return_error'] = json_encode($response);
               $this->utils->debug_log('==========================sdpay withdrawal callback error response', $result);
               return $result;
          }
          if (!$this->checkCallbackOrder($order, $contentParams, $processed)) {
               $result['return_error'] = json_encode($response);
               $this->utils->debug_log('==========================sdpay withdrawal callback error response', $result);
               return $result;
          }
          if ($processed) {
               $response = array(
                    'code' => '200',
                    'message' => '操作成功',
                    'status' => 'success',
                    'results' => $this->sign(
                         array(
                              'serialNumber' => $contentParams['serialNumber'],
                              'merchantCode' => $contentParams['merchantCode'],
                              'orderStatus' => $contentParams['orderStatus']
                         ),
                         $contentParams['merchantCode'] . $contentParams['serialNumber'] . $contentParams['orderStatus'] . 'success'
                    )
               );

               switch ($contentParams['orderStatus']) {
                    case self::WITHDRAWAL_SUCCESS:
                    case self::RESULT_CODE_FINAL:
                         $this->utils->debug_log('==========================sdpay withdrawal payment was successful: trade ID [%s]', $contentParams['serialNumber']);

                         $msg = sprintf('sdpay withdrawal was successful: trade ID [%s]', $contentParams['serialNumber']);
                         $this->CI->wallet_model->withdrawalAPIReturnSuccess($transId, $msg);

                         $result['message'] = json_encode($response);
                         $result['success'] = true;
                         break;
                    case self::WITHDRAWAL_PROCESSING:
                    case self::WITHDRAWAL_INITIAL:
                         $result['message'] = 'code=> ' . $contentParams['orderStatus'] . 'sdpay withdrawal Pending.';
                         break;

                    case self::WITHDRAWAL_FAIL:
                         $msg = sprintf('sdpay withdrawal payment was not successful  trade ID [%s] ', $contentParams['serialNumber']);
                         $this->writePaymentErrorLog($msg, $contentParams);
                         $this->CI->wallet_model->withdrawalAPIReturnFailure($transId, $msg);
                         $result['return_error'] = json_encode($response);
                         break;
               }
               $this->utils->debug_log('==========================sdpay withdrawal callback success response', $result);
          } else {
               $result['return_error'] = json_encode($response);
               $this->utils->debug_log('==========================sdpay withdrawal callback error response', $result);
          }

          return $result;
     }

     public function checkCallbackOrder($order, $fields, &$processed = false)
     {
          $requiredFields = array('serialNumber', 'merchantCode', 'intoAmount', 'signature', 'orderStatus');

          $status = $fields['orderStatus'];
          $secure_id = $fields['serialNumber'];
          $account = $fields['merchantCode'];
          $amount = $fields['intoAmount'];

          foreach ($requiredFields as $f) {
               if (!array_key_exists($f, $fields)) {
                    $this->writePaymentErrorLog("=======================sdpay withdrawal checkCallbackOrder missing parameter: [$f]", $fields);
                    return false;
               }
          }

          # is signature authentic?
          if (!$this->validateSign($fields)) {
               $this->writePaymentErrorLog('=====================sdpay withdrawal checkCallbackOrder Signature Error', $fields);
               return false;
          }

          $processed = true; # processed is set to true once the signature verification pass
          if ($account != $this->getSystemInfo('account')) {
               $this->writePaymentErrorLog("======================sdpay withdrawal checkCallbackOrder merchantCode do not match expected :" . $this->getSystemInfo('account'), $fields);
               return false;
          }

          if ($secure_id != $order['transactionCode']) {
               $this->writePaymentErrorLog("======================sdpay withdrawal checkCallbackOrder order IDs do not match, expected [" . $order['transactionCode'] . " ]", $fields);
               return false;
          }

          $check_amount = $this->convertAmountToCurrency($order['amount'], $order['dwDateTime']);
          if ($amount != $check_amount) {
               $this->writePaymentErrorLog("=========================sdpay withdrawal checkCallbackOrder payment amount is wrong [ RMB: $amount ], expected => [ RMB: $check_amount ]", $fields);
               return false;
          }

          return true;
     }
     protected function getNotifyUrl($orderId)
     {
          return parent::getCallbackUrl('/callback/process/' . $this->getPlatformCode() . '/' . $orderId);
     }

     public function getBankInfo()
     {
          $bankInfo = array();
          $bankInfoArr = $this->getSystemInfo("withdrawal_bank_info");
          if (!empty($bankInfoArr)) {
               foreach ($bankInfoArr as $system_bank_type_id => $bankInfoItem) {
                    $bankInfo[$system_bank_type_id] = array(
                         'name' => $bankInfoItem['name'],
                         'code' => $bankInfoItem['code']
                    );
               }
               $this->utils->debug_log("==================getting sdpay bank info from extra_info: ", $bankInfo);
          } else {
               $bankInfo = array(
                    '2' => array('name' => '招商银行', 'code' => 'CMB'),
                    '6' => array('name' => '中国银行', 'code' => 'BOC'),
                    '7' => array('name' => '深圳发展银行', 'code' => 'SDB'),
                    '8' => array('name' => '广东发展银行', 'code' => 'GDB'),
                    '13' => array('name' => '兴业银行', 'code' => 'CIB'),
                    '12' => array('name' => '中国邮政储蓄银行', 'code' => 'PSBC')
               );
               $this->utils->debug_log("=======================getting sdpay bank info from code: ", $bankInfo);
          }
          return $bankInfo;
     }

     protected function validateSign($params)
     {
          $valid = false;
          $signSource = $params["merchantCode"] . $params["serialNumber"] . $params["orderStatus"];
          $signature = $this->create_send_privatesign($signSource, $this->getPrivKey());
          $signatureKey = $params["signature"];
          $ok = openssl_verify($signSource, base64_decode($signatureKey), $this->getPubKey(), OPENSSL_ALGO_MD5);
          if ($ok == 1) {
               $valid = true;
          }
          return $valid;
     }
}
