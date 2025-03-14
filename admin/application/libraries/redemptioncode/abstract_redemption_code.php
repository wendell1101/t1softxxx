<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

/**
 * 
 * Abstract_redemption_code library class 
 * @property CI_Controller $ci
 * @property CI_Loader $load
 * @property Utils $utils
 * @property Static_redemption_code_model $static_redemption_code_model
 * @property Redemption_code_model $redemption_code_model
 */
abstract class Abstract_redemption_code
{
	public $redemption_code;
	function __construct()
	{
		$this->ci =& get_instance();
        $this->load = $this->ci->load;
        $this->utils = $this->ci->utils;

		$this->load->model(array('static_redemption_code_model', 'redemption_code_model'));
		$this->static_redemption_code_model = $this->ci->static_redemption_code_model;
		$this->redemption_code_model = $this->ci->redemption_code_model;
	}

    abstract public function setRedemptionCode($redemption_code, &$extra_info);
	abstract public function getCodeDetails();
    abstract public function validCodeTypeRepeatable();
    abstract public function validBonusRules();
    public function isCodeTypeRepeatable($typerule)
    {
        return $typerule['bonusApplicationLimitRule'] == Promorules::BONUS_APPLICATION_LIMIT_RULE_NO_LIMIT ||
            ($typerule['bonusApplicationLimitRule'] == Promorules::BONUS_APPLICATION_LIMIT_RULE_LIMIT_COUNT &&
                $typerule['bonusApplicationLimitRuleCnt'] > 1);
    }
}