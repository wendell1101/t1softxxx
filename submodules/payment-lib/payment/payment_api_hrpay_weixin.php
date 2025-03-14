<?php
require_once dirname(__FILE__) . '/abstract_payment_api_hrpay.php';

/**
 * HRPAY 华仁
 * http://www.hr-pay.com
 *
 * * HRPAY_WEIXIN_PAYMENT_API, ID: 148
 *
 * Required Fields:
 *
 * * URL
 * * Account - Merchant ID
 * * Key - MD5 key
 * * ExtraInfo - include pub key and priv key
 *
 * Field Values:
 *
 * * URL: http://api.hr-pay.com/PayInterface.aspx
 * * Extra Info:
 * > {
 * > 	"hrpay_priv_key" : "## path to merchant's private key ##",
 * > 	"hrpay_pub_key" : "## path to API's public key ##",
 * > }
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_hrpay_weixin extends Abstract_payment_api_hrpay {
	public function getPlatformCode() {
		return HRPAY_WEIXIN_PAYMENT_API;
	}

	public function getPrefix() {
		return 'hrpay_weixin';
	}

	# Ref: Documentation page 1
	protected function getPageCode() {
		return parent::PAGECODE_WEIXIN;
	}

	# Specify special param
	protected function configParams(&$params, $direct_pay_extra_info) {
		$params['v_app'] = ''; # if this is given 'app', the url will return the QRCode data instead of redirecting
	}

	# Hide bank selection drop-down
	public function getPlayerInputInfo() {
		return array(
			array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
		);
	}

	protected function processPaymentUrlForm($postParams) {
		$url = $this->getSystemInfo('url');
		$url = $url.'?'.http_build_query($postParams);

		# --- TEMP DEBUG LOGIC ---
		// $ch = curl_init();

  //       curl_setopt($ch, CURLOPT_URL, $url);
  //       curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  //       curl_setopt($ch, CURLOPT_HEADER, 0);

  //       $result = curl_exec($ch);
  //       $this->utils->debug_log("curl result", $result);
		// $decryptedData = $this->decrypt($result);
		// $this->utils->debug_log("Decrypted: ", $decryptedData);
		# --- End DEBUG LOGIC ---

		return array('success' => true, 'type' => self::REDIRECT_TYPE_URL, 'url' => $url);
	}
}
