<?php
require_once dirname(__FILE__) . '/abstract_payment_api_yangpay.php';

/**
 *
 * YANGPAY 洋洋支付 - 银联扫码 926
 *
 *
 * * 'YANGPAY_UNIONPAY_PAYMENT_API', ID 5447
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL:
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_yangpay_unionpay extends Abstract_payment_api_yangpay {

	public function __construct($params = null){

		// 必要放此處，取得該支付商、渠道的物件名。
		$class_name = get_class($this);
		$this->setupPayAndBillingStr( $class_name );

		parent::__construct($params);

	}

	/**
	 * Reference by submodules/core-lib/application/config/apis.php
	 */
	public function getPlatformCode() {
		// return YANGPAY_UNIONPAY_PAYMENT_API;
		return constant($this->payUpperStr.'_'.$this->billingUpperStr.'_PAYMENT_API');
	}

	public function getPrefix() {
		$preFix = array();
		$preFix[] = $this->payStr;
		$preFix[] = $this->billingStr;
		$preFixStr = implode('_', $preFix);
$this->CI->utils->debug_log("=====================YANGPAY getPrefix", $preFixStr);
		return $preFixStr; // 'yangpay_unionpay'; // @todo 待驗證
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
		// $params['pay_bankcode'] = self::PAY_BANKCODE_ALIPAY;

		// frome getSystemInfo().
		// $billingStr = strtoupper( $this->getBillingStrFromClassName( get_class($this) ) );
		$billingStr = $this->billingUpperStr;
$this->CI->utils->debug_log("=====================YANGPAY billingStr", $billingStr);
		$params['pay_bankcode'] = $this->pay_bankcode_list[ $billingStr ];

		if($params['pay_bankcode'] == ''){ // default, should be self::PAY_BANKCODE_ALIPAY.
			$params['pay_bankcode'] = self::pay_bankcode_list[$billingStr];
		}
	}

	protected function processPaymentUrlForm($params) {
		return $this->processPaymentUrlFormPost($params);
	}

	/**
	 * =========================
	 *  Scaffolding Utils
	 * =========================
	 */

	/**
	 * generate xml string for update into "external_system_list.xml".
	 * access external_system_list.xml by Scaffolding
	 */
	public function genXML(){

		$selfExtraInfoDefault = $this->selfExtraInfoDefault();

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
		// $live_url = $this->action_uri_list[ $this->billingUpperStr ]; // 不使用資料庫的設定。
		$live_url = $selfExtraInfoDefault['action_uri_list'][ $this->billingUpperStr ];
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
		$system_code[] = $this->payStr; // $this->getPayStrFromClassName( get_class($this) );
		$system_code[] = $this->billingStr; // $this->getBillingStrFromClassName( get_class($this) );
		$system_codeStr = implode('_', $system_code);
		$xml->addChild('system_code', $system_codeStr);

		/// fixed fields,
		$xml->addChild('status', 1);

		// depence by payment and billing channel
		$xml->addChild('class_name', strtolower( get_class($this) ) );

		/// fixed fields,
		$xml->addChild('local_path', 'payment');
		$xml->addChild('manager', 'payment_manager');
		$xml->addChild('game_platform_rate', 1); // fixed, not used in payment.

		// depence by payment and billing channel
		$this->addCData2xmlNode($this->getExtraInfoStr(), 'extra_info', $xml);
		$xml->addChild('sandbox_extra_info', 'NULL');

		/// depence by payment and billing channel
		$xml->addChild('allow_deposit_withdraw', self::ALLOW_DEPOSIT); // self::ALLOW_DEPOSIT OR self::ALLOW_WITHDRAW

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
	} // EOF genXML


	// ========= 以下程式碼尚未驗證 =========
	public function getPlayerInputInfo() {

        return array(
             array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
        );
    }

}
