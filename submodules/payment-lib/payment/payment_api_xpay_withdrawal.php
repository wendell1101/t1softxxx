<?php

require_once dirname(__FILE__) . '/abstract_payment_api_xpay.php';
/**
 * XPAY
 *
 * * XPAY_WITHDRAWAL_PAYMENT_API, ID: 5472
 *
 * Required Fields:
 * * Account
 * * URL
 * * ApiKey
 *
 * Field Values:
 * * Account: ## Merchant ID ##
 * * URL: https://www.paymentonline515.com/withdrawal.php
 * * ApiKey: ## Api Key ##
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_xpay_withdrawal extends Abstract_payment_api_xpay
{
     const FUNDOUT_STATUS_SUCCESS = '000';
     const FUNDOUT_STATUS_FAIL = '111';
     const FUNDOUT_STATUS_PENDING = '001';
     const FUNDOUT_STATUS_REJECTED = '112';
     public function getPlatformCode()
     {
          return XPAY_WITHDRAWAL_PAYMENT_API;
     }
     public function getPrefix()
     {
          return 'xpay_withdrawal';
     }
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
               $this->utils->debug_log($result);
               return $result;
          }
          if (!array_key_exists($bank, $this->getBankInfo())) {
               $this->utils->error_log("========================xpay submitWithdrawRequest bank whose bankTypeId=[$bank] is not supported by xpay");
               return array('success' => false, 'message' => 'Bank not supported by Xpay');
          }
          $params = $this->getWithdrawParams($bank, $accNum, $name, $amount, $transId);
          $url = $this->getWithdrawUrl();

          list($response, $response_result) = $this->submitPostForm($url, $params, false, $transId, true);
          $decodedResult = $this->decodeResult($response);
          $decodedResult['response_result'] = $response_result;

          $this->CI->utils->debug_log('======================================xpay submitWithdrawRequest url: ', $url);
          $this->CI->utils->debug_log('======================================xpay submitWithdrawRequest response', $response);
          $this->CI->utils->debug_log('======================================xpay submitWithdrawRequest decoded Result', $decodedResult);

          return $decodedResult;
     }

     public function getWithdrawUrl()
     {
          return $this->getSystemInfo('url');
     }

     public function getWithdrawParams($bank, $accNum, $name, $amount, $transId)
     {
          $this->CI->load->model(array('wallet_model', 'player_model', 'playerbankdetails'));

          $params = array();
          $order = $this->CI->wallet_model->getWalletAccountByTransactionCode($transId);
          $playerId = $order['playerId'];
          $player = $this->CI->player->getPlayerById($playerId);
          $username = $player['username'];
          $playerBankDetails = $this->CI->playerbankdetails->getBankDetailsByBankAccount($bank, $accNum);
          if (!empty($playerBankDetails)) {
               $province = $playerBankDetails['province'];
               $city = $playerBankDetails['city'];
               $bankBranch = $playerBankDetails['branch'];
               $bankSubBranch = $playerBankDetails['branch'];
          } else {
               $province = 'empty';
               $city = 'empty';
               $bankBranch = 'empty';
               $bankSubBranch = 'empty';
          }

          $province = empty($province) ? "empty" : $province;
          $city = empty($city) ? "empty" : $city;
          $bankBranch = empty($bankBranch) ? "empty" : $bankBranch;
          $bankSubBranch = empty($bankSubBranch) ? "empty" : $bankSubBranch;

          $bankInfo = $this->getBankInfo();
          $bankCode = $bankInfo[$bank]['code'];
          $bankName = $bankInfo[$bank]['name'];

          $params['partner_code'] = $this->getSystemInfo('account');
          $params['partner_orderid'] = $transId;
          $params['member_id'] = $playerId;
          $params['currency'] = $this->getSystemInfo('currency');
          $params['amount'] = $this->convertAmountToCurrency($amount);
          $params['account_name'] = $name;
          $params['account_number'] = $accNum;
          $params['bank_province'] = $province;
          $params['bank_city'] = $city;
          $params['bank_branch'] = $bankSubBranch;
          $params['bank_code'] = $bankCode;
          $params['notify_url'] = $this->getNotifyUrl($transId);

          $hash_keys = array(
               'partner_code',
               'partner_orderid',
               'member_id',
               'currency',
               'amount',
               'account_name',
               'account_number',
               'bank_province',
               'bank_city',
               'bank_branch',
               'bank_code',
               'notify_url'
          );
          $params['sign'] = $this->sign($params, $hash_keys);

          return $params;
     }

     public function decodeResult($resultString, $queryAPI = false)
     {
          if (is_array($resultString) && isset($resultString['lock']) && isset($resultString['msg'])) {
               $result = array('success' => false, 'message' => $resultString['msg'], 'lock' => true);
               return $result;
          }

          $result = $this->parseResultXML($resultString, true);

          if (isset($result['error_code'])) {
               $message = $result['error_code'] . ':' . $result['error_description'];
               $return = array(
                    'success' => false,
                    'message' => $message,
                    'payment_fail' => false
               );
               $this->utils->debug_log("=========================xpay api response error decoded", $result);
          } else {
               if (!$queryAPI) {
                    if (isset($result['status'])) {
                         switch ($result['status']) {
                              case self::FUNDOUT_STATUS_SUCCESS:
                                   $message = 'code=> ' . $result['status'] . 'Xpay withdrawal Success.';
                                   $return = array('success' => true, 'message' => $message);
                                   break;
                              case self::FUNDOUT_STATUS_PENDING:
                                   $message = 'code=> ' . $result['status'] . 'Xpay withdrawal Pending.';
                                   $return = array('success' => true, 'message' => $message);
                                   break;
                              case self::FUNDOUT_STATUS_REJECTED:
                                   $message = 'code=> ' . $result['status'] . 'Xpay withdrawal Rejected.';
                                   $return = array('success' => false, 'message' => $message, 'payment_fail' => true);
                                   break;
                              case self::FUNDOUT_STATUS_FAIL:
                                   $message = 'code=> ' . $result['status'] . 'Xpay withdrawal Fail.';
                                   $return = array('success' => false, 'message' => $message, 'payment_fail' => true);
                                   break;
                              default:
                                   $message = 'Xpay withdrawal Fail.';
                                   $return = array('success' => false, 'message' => $message);
                                   break;
                         }
                    }
               } else {
                    $checkWithdrawOrder = $result['items']['item'];
                    $this->CI->utils->debug_log('======================================xpay checkWithdrawStatus Order ', $checkWithdrawOrder);
                    switch ($checkWithdrawOrder['status']) {
                         case self::FUNDOUT_STATUS_SUCCESS:
                              $message = 'code=>' . $checkWithdrawOrder['status'] . 'Xpay withdrawal Success.';
                              $return = array('success' => true, 'message' => $message);
                              break;
                         case self::FUNDOUT_STATUS_PENDING:
                              $message = 'code=>' . $checkWithdrawOrder['status'] . 'Xpay withdrawal Pending.';
                              $return = array('success' => false, 'message' => $message);
                              break;
                         case self::FUNDOUT_STATUS_REJECTED:
                              $message = 'code=>' . $checkWithdrawOrder['status'] . 'Xpay withdrawal Rejected.';
                              $return = array('success' => false, 'message' => $message, 'payment_fail' => true);
                              break;
                         case self::FUNDOUT_STATUS_FAIL:
                              $message = 'code=>' . $checkWithdrawOrder['status'] . 'Xpay withdrawal Fail.';
                              $return = array('success' => false, 'message' => $message, 'payment_fail' => true);
                              break;
                         default:
                              $message = 'Xpay withdrawal Fail.';
                              $return = array('success' => false, 'message' => $message);
                              break;
                    }
               }
          }
          return $return;
     }

     public function checkWithdrawStatus($transId)
     {
          $params = array();
          $params['partner_code'] = $this->getSystemInfo("account");
          $params['currency'] = $this->getSystemInfo("currency");
          $params['orderid'] = $transId;
          $params['sign'] = $this->sign($params, array('partner_code', 'currency', 'orderid'));

          $url = $this->getSystemInfo('check_withdraw_status_url');
          $response = $this->submitPostForm($url, $params, false, $transId);
          $decodedResult = $this->decodeResult($response, true);

          return $decodedResult;
     }

     private function getNotifyUrl($orderId)
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
               $this->utils->debug_log("==================getting xpay bank info from extra_info: ", $bankInfo);
          } else {
               $currencytype = $this->getSystemInfo('currency');
               switch ($currencytype) {
                    case 'CNY':
                         $bankInfo = array(
                              '1' => array('name' => '工商银行', 'code' => 'ICBC'),
                              '2' => array('name' => '招商银行', 'code' => 'CMB'),
                              '3' => array('name' => '建设银行', 'code' => 'CCB'),
                              '4' => array('name' => '农业银行', 'code' => 'ABC'),
                              '5' => array('name' => '交通银行', 'code' => 'BCOM'),
                              '6' => array('name' => '中国银行', 'code' => 'BOC'),
                              '7' => array('name' => '深圳发展银行', 'code' => 'SDB'),
                              '8' => array('name' => '广东发展银行', 'code' => 'GDB'),
                              '10' => array('name' => '中信银行', 'code' => 'CITIC'),
                              '11' => array('name' => '民生银行', 'code' => 'CMBC'),
                              '12' => array('name' => '邮储银行', 'code' => 'PSBC'),
                              '13' => array('name' => '兴业银行', 'code' => 'CIB'),
                              '14' => array('name' => '华夏银行', 'code' => 'HXB'),
                              '15' => array('name' => '平安银行', 'code' => 'PABC'),
                              '18' => array('name' => '南京银行', 'code' => 'BON'),
                              '20' => array('name' => '光大银行', 'code' => 'CEB'),
                              '24' => array('name' => '上海浦东发展银行', 'code' => 'SPDB'),
                              '26' => array('name' => '广东发展银行', 'code' => 'GDB'),
                              '27' => array('name' => '上海浦东发展银行', 'code' => 'SPDB'),
                              '29' => array('name' => '北京银行', 'code' => 'BOB'),
                              '31' => array('name' => '上海银行', 'code' => 'SHB'),
                              '33' => array('name' => '北京农商', 'code' => 'BJRCB')
                         );
                         break;
                    case 'THB':
                         $bankInfo = array(
                              '52' => array('name' => 'Krung Thai Bank', 'code' => 'KTB'),
                              '51' => array('name' => 'Siam Commercial Bank', 'code' => 'SCB'),
                         );
                         break;
               }
               $this->utils->debug_log("==================getting xpay bank info from code: ", $bankInfo);
          }
          return $bankInfo;
     }
     public function sign($params, $hash_keys)
     {
          $signStr = '';
          foreach ($hash_keys as $key) {
               if (array_key_exists($key, $params)) {
                    $signStr .= "$key=" . $params[$key] . "&";
               }
          }
          $sign = sha1($signStr . "key=" . $this->getSystemInfo('key'));
          $sign = strtoupper($sign);
          return $sign;
     }

     public function validateSign($params)
     {
          $signStr = '';
          foreach ($params as $key => $value) {
               if ($key == 'sign') {
                    continue;
               }
               $signStr .= "$key=" . $value . "&";
          }
          $sign = sha1($signStr . "key=" . $this->getSystemInfo('key'));
          $sign = strtoupper($sign);
          if ($sign == $params['sign']) {
               return true;
          } else {
               return false;
          }
     }

     public function callbackFromServer($transId, $params)
     {
          $response_result_id = parent::callbackFromServer($transId, $params);

          if (empty($params) || is_null($params)) {
               $raw_post_data = file_get_contents('php://input', 'r');
               $params = json_decode($raw_post_data, true);
          }
          $result = array('success' => false, 'message' => 'Payment failed');

          $this->utils->debug_log("==========================xpay checkCallback params", $params);
          $order = $this->CI->wallet_model->getWalletAccountByTransactionCode($transId);

          if (!$this->checkCallbackOrder($order, $params)) {
               return $result;
          }

          $result['message'] = '<xml><billno>' . $params['billno'] . '</billno><status>OK</status></xml>';

          if ($params['status'] == self::FUNDOUT_STATUS_SUCCESS) {
               $this->utils->debug_log('==========================xpay withdrawal payment was successful: trade ID [%s]', $params['billno']);

               $msg = 'xpay withdrawal success';
               $this->CI->wallet_model->withdrawalAPIReturnSuccess($transId, $msg);
               $result['success'] = true;
          } else {
               if ($params['status'] == self::FUNDOUT_STATUS_FAIL) {
                    $msg = 'xpay withdrawal fail code=>' . $params['error_code'] . ' : ' . $params['error_description'];
                    $this->utils->debug_log('==========================xpay withdrawal payment was fail', $params);
                    $this->CI->wallet_model->withdrawalAPIReturnFailure($transId, $msg);
                    $result['return_error'] = '<xml><billno>' . $params['billno'] . '</billno><status>OK</status></xml>';
               }
          }
          return $result;
     }
     public function checkCallbackOrder($order, $fields)
     {
          $requiredFields = array('partner_orderid', 'amount', 'account_name', 'account_number', 'bank_code', 'status', 'billno', 'sign');

          foreach ($requiredFields as $f) {
               if (!array_key_exists($f, $fields)) {
                    $this->writePaymentErrorLog("=======================xpay withdrawal checkCallbackOrder missing parameter: [$f]", $fields);
                    return false;
               }
          }

          if (!$this->validateSign($fields)) {
               $this->writePaymentErrorLog('==========================xpay withdrawal checkCallback signature Error', $fields);
               return false;
          }

          $orderAmount = $this->convertAmountToCurrency($order['amount']);

          if ($fields['amount'] != $orderAmount) {
               $this->writePaymentErrorLog('=========================xpay withdrawal checkCallbackOrder payment amount is wrong, expected => [' . $orderAmount ."]", $fields['amount']);
               return false;
          }

          if ($fields['partner_orderid'] != $order['transactionCode']) {
               $this->writePaymentErrorLog('=========================xpay withdrawal checkCallbackOrder order IDs do not match, expected =>' . $order['transactionCode'], $fields);
               return false;
          }

          # everything checked ok
          return true;
     }
     protected function parseResultXML($resultXml)
     {
          $obj = simplexml_load_string($resultXml);
          $arr = $this->CI->utils->xmlToArray($obj);
          return $arr;
     }

     protected function convertAmountToCurrency($amount)
     {
          return number_format($amount*100, 0, '.', '');
     }
}
