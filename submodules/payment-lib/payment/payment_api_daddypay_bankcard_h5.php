<?php
require_once dirname(__FILE__) . '/abstract_payment_api_daddypay.php';

/**
 *
 * DaddyPay Bankcard H5 银行卡充值
 *
 * DADDYPAY_BANKCARD_H5_PAYMENT_API, ID: 761
 * Required Fields:
 * * URL
 * * Key - signing key
 * * Extra Info
 *
 * Field Values:
 *
 * * URL (sandbox): http://52.69.65.224/Mownecum_2_API_Live/Deposit?format=json
 * * Extra Info
 * > {
 * >     "daddypay_company_id" : "## company id ##",
 * >     "bank_list": {
 * >         "ICBC" : "_json: { \"1\": \"ICBC\", \"2\": \"中国工商银行\"}",
 * >         "CMB" : "_json: { \"1\": \"CMB\", \"2\": \"招商银行\"}",
 * >         "CCB" : "_json: { \"1\": \"CCB\", \"2\": \"中国建设银行\"}",
 * >         "ABC" : "_json: { \"1\": \"ABC\", \"2\": \"中国农业银行\"}",
 * >         "BOC" : "_json: { \"1\": \"BOC\", \"2\": \"中国银行\"}",
 * >         "BCM" : "_json: { \"1\": \"BCM\", \"2\": \"交通银行\"}",
 * >         "CMBC" : "_json: { \"1\": \"CMBC\", \"2\": \"中国民生银行\"}",
 * >         "ECC" : "_json: { \"1\": \"ECC\", \"2\": \"中信银行\"}",
 * >         "SPDB" : "_json: { \"1\": \"SPDB\", \"2\": \"上海浦东发展银行\"}",
 * >         "PSBC" : "_json: { \"1\": \"PSBC\", \"2\": \"邮政储汇\"}",
 * >         "CEB" : "_json: { \"1\": \"CEB\", \"2\": \"中国光大银行\"}",
 * >         "PINGAN" : "_json: { \"1\": \"PINGAN\", \"2\": \"平安银行 （原深圳发展银行）\"}",
 * >         "CGB" : "_json: { \"1\": \"CGB\", \"2\": \"广发银行股份有限公司\"}",
 * >         "HXB" : "_json: { \"1\": \"HXB\", \"2\": \"华夏银行\"}",
 * >         "CIB" : "_json: { \"1\": \"CIB\", \"2\": \"福建兴业银行\"}",
 * >         "TENPAY" : "_json: { \"1\": \"TENPAY\", \"2\": \"财付通\"}"
 * >     }
 * > }
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_daddypay_bankcard_h5 extends Abstract_payment_api_daddypay {

    public function getPlatformCode() {
        return DADDYPAY_BANKCARD_H5_PAYMENT_API;
    }

    public function getPrefix() {
        return 'daddypay_bankcard_h5';
    }

    public function getName() {
        return 'DADDYPAY_BANKCARD_H5';
    }

    protected function getBankId($direct_pay_extra_info) {
        # overwritten in qrcode implementation
        if (!empty($direct_pay_extra_info) && !empty($this->getSystemInfo("bank_list"))) {
            $extraInfo = json_decode($direct_pay_extra_info, true);
            if (!empty($extraInfo) && array_key_exists('bank', $extraInfo)) {
                $bank = array_key_exists('bank', $extraInfo) ? $extraInfo['bank'] : $extraInfo['bank_type'];
            }
        }else{
            $bank = $this->getSystemInfo("daddypay_bank_id");
        }

        $bankId = $this->getBankName($bank);
        return $bankId;
    }

    protected function getBankName($bankType,$returnLabel=false) {
        $bankList = $this->getBankListInfoFallback();
        foreach ($bankList as $list) {
            if ($list['value'] == strtoupper($bankType)) {
                $bankName = ($returnLabel) ? $list['label'] : $list['value'];
                return $bankName;
            }
        }
    }

    public function getDepositMode() {
        return parent::DEPOSIT_MODE_BANKCARD;
    }

    public function getNoteModel($bankId) {
        if( ($this->getSystemInfo("use_note_model_fp")) && ($bankId != '30') ) {
            if($this->getSystemInfo("use_order_num_as_note")){
                return parent::NOTE_MODEL_PLATFORM;
            }else{
                return parent::NOTE_MODEL_DP;
            }
        }
        return parent::NOTE_MODEL_PLATFORM;
    }

    public function shouldShowEmail($resp, $params){

        $success=false;
        if(isset($this->bank_include_email[$resp['collection_bank_id']])){
            $success=$this->bank_include_email[$resp['collection_bank_id']]==$params['estimated_payment_bank'];
        }
        return $success;
    }

    public function handlePaymentFormResponse($resp, $params) {
        $data = array();
        $collection_text = !empty($this->getSystemInfo("collection_text_transfer"))?$this->getSystemInfo("collection_text_transfer"):'';
        $collection_text_exist = isset($collection_text) ? true : false ;
        $collection_text_transfer = null;

        $success=$resp['status']=='1';
        $bankName = $this->getBankName(@$resp['collection_bank_id'],true);
        $data['Beneficiary Bank'] = $bankName;
        $data['Beneficiary Account'] = @$resp['bank_card_num'];
        $data['Beneficiary Name'] = @$resp['bank_acc_name'];
        $data['Deposit Amount'] = @$resp['amount'];

        $style_data['hide_payment_account']=true;


        if($collection_text_exist && isset($collection_text) && !empty($collection_text)){
            if(is_array($collection_text)){
                $collection_text_transfer = $collection_text;
                $this->CI->utils->debug_log("==================================daddypay handlePaymentFormResponse collection_text_transfer", $collection_text_transfer);
            }
        }

        if($this->shouldShowEmail($resp, $params)){
            $data['Email'] = @$resp['email'];
        }
        $data['Beneficiary Bank Address'] = @$resp['issuing_bank_address'];
        $data['Beneficiary note'] = @$resp['note'];
        $this->CI->utils->debug_log("==================================daddypay handlePaymentFormResponse params", $data);
        return array(
            'success' => $success,
            'type' => self::REDIRECT_TYPE_STATIC,
            'data' => $data,
            'style_data' => $style_data,
            'collection_text_transfer' => $collection_text_transfer
        );
    }

    public function getPlayerInputInfo() {
        if($this->getSystemInfo("hide_bank_list")) {	// for only using alipay to online_bank
            return array(
                array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
            );
        }

        if(!empty($this->getSystemInfo("bank_list"))){
            $bankList =
                array(
                    'name' => 'bank_list', 'type' => 'bank_list', 'label_lang' => 'cashier.81',	'external_system_id' => $this->getPlatformCode(),
                    'bank_list' => $this->getBankList(), 'bank_tree' => $this->getBankListTree(), 'bank_list_default' => $this->getSystemInfo('bank_list_default')
                );

            return array(
                $bankList,
                array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
            );
        }

        return array(
            array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
        );
    }

    protected function configParams(&$params, $direct_pay_extra_info) {
        $params['terminal'] = $this->utils->is_mobile() ? '2' : '1';
    }

}
