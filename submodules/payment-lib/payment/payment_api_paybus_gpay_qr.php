<?php
require_once dirname(__FILE__) . '/abstract_payment_api_paybus.php';

/**
 * paybus
 *
 * * define('PAYBUS_GPAY_QR_PAYMENT_API', 6648);
 *
 * Field Values:
 * * URL: https://stg-open.paybus.io
 * * Account: ## User ID ##
 * * Key: ## Secret Key ##
 * 
 * gpay.qr: { 
 *      uid*: string
 *          string  商家下單用戶的唯一身份標識字符串，不能帶有特殊字符
 *      payBank*: string
 *          enum 指定提供的收款銀行，目前只支持SCB、KBANK、BBL、KTB、TTB、GSB、BAY、BAAC
 *          Allowed: SCB┃KBANK┃BBL┃KTB┃TTB┃GSB┃BAY┃BAAC
 *      payAcc: string
 *          付款銀行卡號，用於驗證是否是下單用戶付款，無需驗證則不必提供
 * }
 *
 * @category Payment
 * @copyright 2013-2024 tot
 */
class Payment_api_paybus_gpay_qr extends Abstract_payment_api_paybus {

    const CHANNEL_TYPE = 'gpay.qr';
    private $playerId;
    private $bankCodeUse;
    private $accountNumberUse;

    public function __construct($params = null) {
        parent::__construct($params);
        $this->playerId = null;
        $this->bankCodeUse = $this->getSystemInfo('bankCodeUse');
        $this->accountNumberUse = $this->getSystemInfo('accountNumberUse');
    }
    public function getPlatformCode() {
        return PAYBUS_GPAY_QR_PAYMENT_API;
    }

    public function getPrefix() {
        return 'paybus_gpay_qr';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {
        $playerDetails = $params['playerDetails'];
        $username  = (isset($playerDetails[0]) && !empty($playerDetails[0]['username']))      ? $playerDetails[0]['username']      : '';
        $sendDataArr = array(
            "uid" => $username,
        );
        $extraInfo = json_decode($direct_pay_extra_info, true);
        if($this->bankCodeUse){
            $sendDataArr['payBank'] = $extraInfo['field_required_bank_code'];
        }
        if($this->accountNumberUse){
            $sendDataArr['payAcc'] = $extraInfo['field_required_player_bank_list'];
        }
        $params['channel_input'] = json_decode(json_encode([
            self::CHANNEL_TYPE => $sendDataArr
        ]));
    }

    # Hide bank list dropdown
    public function getPlayerInputInfo() {
        $getPlayerInputInfo = array(
            array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09')
	   	);
        if($this->bankCodeUse){
			$newArray = array('name' => 'field_required_bank_code', 'type' => 'list', 'label_lang' => 'pay.bank','list' => $this->getBankList());
			array_push($getPlayerInputInfo, $newArray);			
		}
		if ($this->accountNumberUse) {
            $bankAccount = $this->getBankAccount();
            $newArray = array('name' => 'field_required_player_bank_list', 'type' => 'player_bank_list', 'select_lang'=> 'Please Select Bank', 'label_lang' => 'cashier.player.bank_num', 'list' => $bankAccount, 'default_option_value' => $bankAccount[0]['bankAccountNumber'], 'disabled_btn' => 'disabled');
			array_unshift($getPlayerInputInfo, $newArray);
		}
		return $getPlayerInputInfo;
    }

    protected function processPaymentUrlForm($params) {
        return $this->processPaymentUrlFormPost($params);
    }

    protected function getBankListInfoFallback() {
        // "bank_list": {
        //      "SCB": "SCB",
        //      "KBANK": "KBANK",
        //      "BBL": "BBL",
        //      "KTB": "KTB",
        //      "TTB": "TTB",
        //      "GSB": "GSB",
        //      "BAY": "BAY",
        //      "BAAC": "BAAC"
        // } 
        $banklist=$this->getSystemInfo('bank_list');
        if(empty($banklist)){
            $banklist=array(
                array('label' => "SCB", 'value' => 'SCB'),
                array('label' => "KBANK", 'value' => 'KBANK'),
                array('label' => "BBL", 'value' => 'BBL'),
                array('label' => "KTB", 'value' => 'KTB'),
                array('label' => "TTB", 'value' => 'TTB'),
                array('label' => "GSB", 'value' => 'GSB'),
                array('label' => "BAY", 'value' => 'BAY'),
                array('label' => "BAAC", 'value' => 'BAAC')
            );
        }
        return $banklist;
    }
    

    protected function getBankAccount() {
        $bankAccountFullName = '';
        $bankAccountNumber = '';
        $this->CI->load->model('playerbankdetails');
        $default_bank_details = $this->CI->playerbankdetails->getDefaultBankDetail($this->playerId);

        if (count($default_bank_details['deposit']) > 0) {
            $bankAccountNumber = $default_bank_details['deposit'][0]['bankAccountNumber'];
            $bankAccountFullName = $default_bank_details['deposit'][0]['bankAccountFullName'];
        }

        $banklist[] = array(
            'bankName' => $bankAccountFullName,
            'bankAccountNumber' => $bankAccountNumber,
            'bankCode' => $bankAccountNumber,
        );
        return $banklist;
    }

    public function initPlayerPaymentInfo($player_id) {
		$this->playerId = $player_id;
	}
}
