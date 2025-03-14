<?php
require_once dirname(__FILE__) . '/base_model.php';
/**
 *
 * @category Payment Model
 * @copyright 2013-2022 tot
 */
class Financial_account_setting extends BaseModel {

    protected $tableName = 'player_financial_account_rules';

    const PAYMENT_TYPE_FLAG_BANK    = 1;
    const PAYMENT_TYPE_FLAG_EWALLET = 2;
    const PAYMENT_TYPE_FLAG_CRYPTO  = 3;
    const PAYMENT_TYPE_FLAG_API     = 4;
    const PAYMENT_TYPE_FLAG_PIX     = 5;

    const FIELD_NAME         = 1;
    const FIELD_PHONE        = 2;
    const FIELD_BANK_BRANCH  = 3;
    const FIELD_BANK_AREA    = 4;
    const FIELD_BANK_ADDRESS = 5;
    const FIELD_NETWROK     = 6;
    const FIELD_OTP_VERIFY   = 7;

    function __construct() {
        parent::__construct();
    }

    public function getPaymentTypeAllFlagsKV(){
        $paymentTypes = array(
            self::PAYMENT_TYPE_FLAG_BANK    => array('lang_key' => "pay.payment_type_bank"),
            self::PAYMENT_TYPE_FLAG_EWALLET => array('lang_key' => "pay.payment_type_ewallet"),
            self::PAYMENT_TYPE_FLAG_CRYPTO  => array('lang_key' => "pay.payment_type_crypto"),
            self::PAYMENT_TYPE_FLAG_API     => array('lang_key' => "pay.payment_type_api"),
            self::PAYMENT_TYPE_FLAG_PIX     => array('lang_key' => "pay.payment_type_pix"),
        );

        foreach ($paymentTypes as $key => $value) {
            $paymentTypes[$key] = lang($value['lang_key']);
        }
        return $paymentTypes;
    }

    public function getPlayerFinancialAccountRules() {
        $this->db->from($this->tableName);
        return $this->runMultipleRowArray();
    }

    public function getPlayerFinancialAccountRulesByPaymentAccountFlag($flag) {
        if($flag == self::PAYMENT_TYPE_FLAG_PIX){
            $flag = self::PAYMENT_TYPE_FLAG_BANK;
        }

        if (!empty($flag)) {
            $this->db->from($this->tableName)->where('payment_type_flag', $flag);
            return $this->runOneRowArray();
        }
        return null;
    }

    public function getFieldShowByPaymentAccountFlag($flag) {
        if($flag == self::PAYMENT_TYPE_FLAG_PIX){
            $flag = self::PAYMENT_TYPE_FLAG_BANK;
        }

        if (!empty($flag)) {
            $this->db->select('field_show')->from($this->tableName)->where('payment_type_flag', $flag);
            $field_show = $this->runOneRowOneField('field_show');
            return explode(',', $field_show);
        }
        return null;
    }

    public function updatePlayerFinancialAccountRules($type, $data) {
        $this->db->where('payment_type_flag', $type);
        $this->db->update($this->tableName, $data);
    }

    public function getPaymentFlagCode() {
        $res = [
            self::PAYMENT_TYPE_FLAG_BANK    => 'bank' ,
            self::PAYMENT_TYPE_FLAG_EWALLET => 'ewallet' ,
            self::PAYMENT_TYPE_FLAG_CRYPTO  => 'crypto' ,
            self::PAYMENT_TYPE_FLAG_API     => 'api'
        ];

        return $res;
    }
}

/////end of file///////
