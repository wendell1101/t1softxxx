<?php
require_once dirname(__FILE__) . '/abstract_payment_api_yftpay.php';
/**
 * YFTPAY YFT支付 - 银联快捷 911 (快捷支付)
 * Ref. to Payment_api_bohaipay_alipay
 *
 * YFTPAY_QUICKPAY_PAYMENT_API, ID: XXX
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values: (http://admin.og.local/payment_api/viewPaymentApi/)
 * * URL:
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_yftpay_quickpay extends Abstract_payment_api_yftpay {

	/**
	 * Reference by submodules/core-lib/application/config/apis.php
	 */
	public function getPlatformCode() {
		// return TEMPLATE_ALIPAY_PAYMENT_API; // TEST
		return YFTPAY_QUICKPAY_PAYMENT_API;
	}

	public function getPrefix() {
		$preFix = array();
		$preFix[] = $this->getPayStrFromClassName( "". get_class($this) );
		$preFix[] = $this->getBillingStrFromClassName( "". get_class($this) );
		$preFixStr = implode('_', $preFix);
$this->CI->utils->debug_log("=====================YFTPAY getPrefix", $preFixStr);
		return $preFixStr; // 'yangpay_quickpay'; // @todo 待驗證
	}



	protected function configParams(&$params, $direct_pay_extra_info) {
		// frome getSystemInfo().
		$billingStr = strtoupper( $this->getBillingStrFromClassName( get_class($this) ) );
$this->CI->utils->debug_log("=====================YFTPAY billingStr", $billingStr);
		$params['pay_bank_id'] = $this->pay_bankcode_list[ $billingStr ];

		if($params['pay_bank_id'] == ''){ // default, should be self::PAY_BANKCODE_ALIPAY.
			$params['pay_bank_id'] = self::pay_bankcode_list[$billingStr];
		}
	}

	protected function processPaymentUrlForm($params) {
		return $this->processPaymentUrlFormPost($params);
	}

	/**
	 * generate xml string for update into "external_system_list.xml".
	 * access external_system_list.xml by Scaffolding
	 */
	public function genXML(){
		$xml = new SimpleXMLElement('<ROW/>');

		// depence by payment and billing channel
		$xml->addChild('id', $this->getPlatformCode());
		$xml->addChild('system_name', strtoupper( $this->getPrefix() ). '_PAYMENT_API');// 'YANGPAY_WEIXIN_QR_PAYMENT_API');

		/// fixed fields,
		$xml->addChild('note', 'NULL');
		$xml->addChild('last_sync_datetime', 'NULL');
		$xml->addChild('last_sync_id', 'NULL');
		$xml->addChild('last_sync_details', 'NULL');
		$xml->addChild('system_type', SYSTEM_PAYMENT);

		// depence by payment and billing channel
		// $xml->addChild('live_url', 'http://wx.vkoov.com/Pay_Index.html'); // orig
		$live_url = $this->action_uri_list[ strtoupper($this->getBillingStrFromClassName( get_class($this) )) ];
		$xml->addChild('live_url', $live_url );

		// depence by payment and billing channel
		$xml->addChild('sandbox_url', 'NULL');
		$xml->addChild('live_key', '## Key ##');
		$xml->addChild('live_secret', 'NULL');
		$xml->addChild('sandbox_key', 'NULL');
		$xml->addChild('sandbox_secret', 'NULL');
		$xml->addChild('live_mode', 1);  // fixed, 1
		$xml->addChild('second_url', 'NULL');
		$xml->addChild('sandbox_account', 'NULL');
		$xml->addChild('live_account', '## Merchant ID ##');

		// depence by payment and billing channel
		// <system_code>yangpay_unionpay</system_code>
		$system_code = array();
		$system_code[] = $this->getPayStrFromClassName( get_class($this) );
		$system_code[] = $this->getBillingStrFromClassName( get_class($this) );
		$system_codeStr = implode('_', $system_code);
		$xml->addChild('system_code', $system_codeStr);

		/// fixed fields,
		$xml->addChild('status', 1);

		// depence by payment and billing channel
		$xml->addChild('class_name', strtolower(get_class($this)) );

		/// fixed fields,
		$xml->addChild('local_path', 'payment');
		$xml->addChild('manager', 'payment_manager');
		$xml->addChild('game_platform_rate', 1); // fixed, not used in payment.

		// depence by payment and billing channel
		$this->addCData2xmlNode($this->getExtraInfoStr(), 'extra_info', $xml);
		$xml->addChild('sandbox_extra_info', 'NULL');

		/// fixed fields,
		$xml->addChild('allow_deposit_withdraw', 1); // fixed

		$dom = dom_import_simplexml($xml)->ownerDocument;
		$dom->formatOutput = true;

		// $dom = dom_import_simplexml($customXML);
		// echo $dom->ownerDocument->saveXML($dom->ownerDocument->documentElement);

		// return $dom->saveXML();
		/* return preg_replace( "/<\?xml.+?\?>/", "", $dom->saveXML() ); */
		$t_xml = new DOMDocument();
		$t_xml->loadXML($dom->saveXML());
		$xml_out = $t_xml->saveXML($t_xml->documentElement);
		return $xml_out;
	}
	// ========= 以下程式碼尚未驗證 =========
	# Hide bank selection drop-down
	public function getPlayerInputInfo() {
		return array(
			array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
		);
	}
}
