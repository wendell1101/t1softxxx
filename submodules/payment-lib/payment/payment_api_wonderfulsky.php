<?php
require_once dirname(__FILE__) . '/abstract_payment_api_wonderfulsky.php';
/**
 * WONDERFULSKY 天空付
 *
 * * WONDERFULSKY_PAYMENT_API, ID: 913
 *
 * Required Fields:
 * * Account
 * * URL
 * * Extra Info
 *
 * Field Values:
 * * Account: ## Merchant ID ##
 * * URL: https://api.wonderfulsky.com.cn/service
 * * Extra Info:
 * > {
 * >    "wonderfulsky_priv_key": "## Private Key ##",
 * >    "wonderfulsky_pub_key": "## Public Key ##"
 * > }
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_wonderfulsky extends Abstract_payment_api_wonderfulsky
{
     public function getPlatformCode()
     {
          return WONDERFULSKY_PAYMENT_API;
     }

     public function getPrefix()
     {
          return 'wonderfulsky';
     }

     public function getBankType($direct_pay_extra_info)
     {
      if (!empty($direct_pay_extra_info)) {
          $extraInfo = json_decode($direct_pay_extra_info, true);
      if (!empty($extraInfo)) {
        return array_key_exists('bank', $extraInfo) ? $extraInfo['bank'] : $extraInfo['bank_type'];
          }
      } else {
        return parent::getBankType($direct_pay_extra_info);
      }
     }

     protected function configParams(&$params, $direct_pay_extra_info)
     {
        $params['channel'] = $this->getBankType($direct_pay_extra_info);
     }

     protected function processPaymentUrlForm($params)
     {
          return $this->processPaymentUrlFormPost($params);
     }

    public function getBankListInfoFallback() {
      return array(
          array('label' => '中國農業銀行', 'value' => 'ABC'),
          array('label' => '北京農商銀行', 'value' => 'BJRCB'),
          array('label' => '中國銀行', 'value' => 'BOC'),
          array('label' => '中國光大銀行', 'value' => 'CEB'),
          array('label' => '興業銀行', 'value' => 'CIB'),
          array('label' => '中信銀行', 'value' => 'CITIC'),
          array('label' => '中國民生銀行', 'value' => 'CMBC'),
          array('label' => '中國工商銀行', 'value' => 'ICBC'),
          array('label' => '平安銀行', 'value' => 'SPABANK'),
          array('label' => '浦發銀行', 'value' => 'SPDB'),
          array('label' => '中國郵政儲蓄銀行', 'value' => 'PSBC'),
          array('label' => '南京銀行', 'value' => 'NJCB'),
          array('label' => '交通銀行', 'value' => 'COMM'),
          array('label' => '招商銀行', 'value' => 'CMB'),
          array('label' => '中國建設銀行', 'value' => 'CCB'),
          array('label' => '北京銀行', 'value' => 'BJBANK'),
          array('label' => '華夏銀行', 'value' => 'HXB'),
          array('label' => '廣發銀行', 'value' => 'GDB'),
          array('label' => '東亞銀行', 'value' => 'HKBEA'),
    );
  }
}
