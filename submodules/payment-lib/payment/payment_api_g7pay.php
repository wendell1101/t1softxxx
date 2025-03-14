<?php
require_once dirname(__FILE__) . '/abstract_payment_api_g7pay.php';

/**
 *
 * nyypay
 *
 *
 * * 'NYYPAY_MOMO_PAYMENT_API', ID 6124
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://api.nyypay77.org/api/create
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_g7pay extends Abstract_payment_api_g7pay {


    public function getPlatformCode() {
        return G7PAY_PAYMENT_API;
    }

    public function getPrefix() {
        return 'g7pay';
    }

	protected function configParams(&$params, $direct_pay_extra_info) {
    }

    protected function processPaymentUrlForm($params) {
        return $this->processPaymentUrlFormPost($params);
    }

    public function getPlayerInputInfo() {
        return array(
            array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
        );
    }




	/*

	██╗  ██╗███████╗██╗     ██████╗ ███████╗██████╗     ██╗   ██╗████████╗██╗██╗     ███████╗
	██║  ██║██╔════╝██║     ██╔══██╗██╔════╝██╔══██╗    ██║   ██║╚══██╔══╝██║██║     ██╔════╝
	███████║█████╗  ██║     ██████╔╝█████╗  ██████╔╝    ██║   ██║   ██║   ██║██║     ███████╗
	██╔══██║██╔══╝  ██║     ██╔═══╝ ██╔══╝  ██╔══██╗    ██║   ██║   ██║   ██║██║     ╚════██║
	██║  ██║███████╗███████╗██║     ███████╗██║  ██║    ╚██████╔╝   ██║   ██║███████╗███████║
	╚═╝  ╚═╝╚══════╝╚══════╝╚═╝     ╚══════╝╚═╝  ╚═╝     ╚═════╝    ╚═╝   ╚═╝╚══════╝╚══════╝

	Helper Utils
	*/

	protected function setupPayAndBillingStr($class_name){
		$this->payStr = self::getPayStrFromClassName( $class_name );
		$this->payUpperStr = strtoupper( $this->payStr );
		$this->billingStr = self::getBillingStrFromClassName( $class_name );
		$this->billingUpperStr = strtoupper( $this->billingStr );
	}


	 /**
	 * 此物件自帶的 Extra Info 用來設定成預設值
	 *
	 */
	protected function selfExtraInfoDefault(){

		$ExtraInfoDefault = array();
		$ExtraInfoDefault['callback_host'] = '';
		// $ExtraInfoDefault['pay_productname'] = 'Deposit';
		/**
		 * pay_bankcode_list
		 * deposit 用，渠道代碼
		 */
        $ExtraInfoDefault['pay_bankcode_list'] = array();

        // source from https://tiancit990124.com/docs/collection/bank
		$ExtraInfoDefault['pay_bankcode_list']['BBL'] = 'Bangkok Bank Public Company Limited (BBL)'; // #1 Bangkok Bank Public Company Limited (BBL)

		if( empty($this->pay_bankcode_list) ){
			$this->pay_bankcode_list = $ExtraInfoDefault['pay_bankcode_list'];
		}
		$this->pay_bankcode_list = $this->getSystemInfo("pay_bankcode_list", $ExtraInfoDefault['pay_bankcode_list']);

		/**
		 * action_uri_list
		 * deposit 用，渠道網關
		 */
		$ExtraInfoDefault['action_uri_list'] = array();
		$ExtraInfoDefault['action_uri_list']['PROMPTPAY'] = 'https://tiancit990124.com/api/transaction'; //PROMPTPAY 6137

		$this->action_uri_list = $this->getSystemInfo("action_uri_list", $ExtraInfoDefault['action_uri_list']);
		if( empty($this->action_uri_list) ){
			$this->action_uri_list = $ExtraInfoDefault['action_uri_list'];
		}


		return $ExtraInfoDefault;
	}

	/**
	 * Get extra_info json string
	 * @return string json srting for <extra_info> of xml.
	 */
	public function getExtraInfoStr(){

		$selfExtraInfoDefault = $this->selfExtraInfoDefault();
		$extra_infoStr = json_encode($selfExtraInfoDefault, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);
		return $extra_infoStr;
	} // EOF getExtraInfoStr


    /**
	 * 取得金流商，銀行/渠道代號字串
	 * 依照正規表示式取特定位置的金流商字串。
	 * 參考 https://regex101.com/r/dQ2aaJ/1/
	 *
	 * 支援「Payment_api_yangpay_weixin_qr」輸入，
	 *
	 *
	 * @todo move to helper.
	 * @param string $className 物件的名稱
	 * @return string 金流商字串
	 */
	static function getPayStrFromClassName($classNameStr = ''){
		$re = '/Abstract_payment_api_(?P<pay_name>.+)/'; // Ref. to https://regex101.com/r/dQ2aaJ/1/
		// $classNameStr = '// Abstract_payment_api_yangpay
		// // Payment_api_yangpay_weixin
		// // Payment_api_yangpay_weixin_qr';

		if( is_string($classNameStr ) ){
			preg_match($re, $classNameStr, $matches);
		}else{
			// disable for static
			// $this->CI->utils->debug_log("=====================getPayStrFromClassName.func_get_args", func_get_args());
			$matches = array();
		}


		// display the Warning while not found.
		// Severity: Warning  --> preg_match() expects parameter 2 to be string, object given /home/vagrant/Code/og/submodules/payment-lib/payment/abstract_payment_api_yangpay.php 478

		// Print the entire match result
		$return = '';

		if( ! empty($matches) && $matches['pay_name'] ){
			$return = $matches['pay_name'];
		}else{
			// bsfcn =  getBillingStrFromClassName
			$matches4bsfcn = self::getBillingStrFromClassName($classNameStr, true);
			// disable for static
			// $this->CI->utils->debug_log("=====================getBillingStrFromClassName.matches4bsfcn", $matches4bsfcn);
			if( ! empty($matches4bsfcn) && $matches4bsfcn['pay_name'] ){
				$return = $matches4bsfcn['pay_name'];
			}
		}
		// disable for static
		// $this->CI->utils->debug_log("=====================getPayStrFromClassName.return", $return);
		return $return;
	} // EOF getPayStrFromClassName

	/**
	 * 取得金流商代號字串
	 * 依照正規表示式取特定位置的渠道字串。
	 * 參考： https://regex101.com/r/dQ2aaJ/2/
	 *
	 * @todo move to helper.
	 * @param string $className 物件的名稱
	 * @return string|array  銀行/渠道代號字串
	 */
	static function getBillingStrFromClassName($classNameStr = '',$getMatches = false){
		$re = '/Payment_api_(?P<pay_name>[^_]+)_(?P<billing_name>.*)/';

		if( is_string($classNameStr ) ){
			preg_match($re, $classNameStr, $matches);
		}else{
			$matches = array();
			// disable for static
			// $this->CI->utils->debug_log("=====================getBillingStrFromClassName.func_get_args", func_get_args());
		}

		if($getMatches){
			$return = $matches;
		}else{
			// Print the entire match result
			$return = $matches['billing_name'];
		}

		// disable for static
		// $this->CI->utils->debug_log("=====================getBillingStrFromClassName.return", $return);
		return $return;
	} // EOF getBillingStrFromClassName

	/**
	 * Add CData into Node of xml
	 *
	 * Scaffolding Utils
	 *
	 * @param string $cdata_text The content of Node.
	 * @param string $nodeName The node name, ex: "my_name" eq. <my_name>.
	 * @param SimpleXMLElement &$xml A SimpleXMLElement object
	 * @return void The param, $xml will add a CData of Node.
	 */
	static function addCData2xmlNode($cdata_text, $nodeName, &$xml) {
		$xml->$nodeName = NULL; // VERY IMPORTANT! We need a node where to append
		$node = dom_import_simplexml($xml->$nodeName);
		$no   = $node->ownerDocument;
		$node->appendChild($no->createCDATASection($cdata_text));
	}


}
