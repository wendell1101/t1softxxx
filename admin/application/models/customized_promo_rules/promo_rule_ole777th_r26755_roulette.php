<?php if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

require_once dirname(__FILE__) .'/abstract_promo_rule.php';

/**
 *
 * OGP-26755
 *
 * 每日能申請 daily_spin_limits 次
 * 以累單筆存款金額款計算可遊戲次數(當日)
 * withdrawal condition = bonus amount * turnover
 * pre_chech_cms_id: 與該優惠合計當日申請次數
 * max_amount_limit: 符合存款金額499 ~ 2998
 * roulette_exclusion_promo_list: 排除有綁定list內優惠的存款
 *
condition:
{
    "class": "promo_rule_ole777th_r26755_roulette",
    "allowed_date": {
        "start": "",
        "end": ""
    },
    "release_date": {
        "start": "",
        "end": ""
    },
    "daily_spin_limits": 5,
    "turnover": 3,
    "spin_conditions":{
        "type"  : "deposit",
        "per_amount": 499,
        "earn"  : 1
    },
    "rouletteName": "r26755",
    "cmsId":18,
    "pre_chech_cms_id":19,
    "max_amount_limit":2998
}
*/
class Promo_rule_ole777th_r26755_roulette extends Abstract_promo_rule
{
    public function init($playerId, $promorule, $playerBonusAmount = null, $depositAmount=null)
    {
        parent::init($playerId, $promorule, $playerBonusAmount, $depositAmount);
    }

    public function getClassName()
    {
        return 'Promo_rule_ole777th_r26755_roulette';
    }

    public function getRetentionTime($description)
    {
        $found_promo_setting = false;
        $retention_time = 0;
        if(isset($description['retention_time'])) {
            $retention_time = $description['retention_time'];
            $found_promo_setting = true;
            $this->utils->debug_log(__METHOD__." Found in description",['retention_time'=>$retention_time]);
        }

        if(!$found_promo_setting) {
            $config_key = $description['rouletteName'];
            $retention_time_config = $this->utils->getConfig('roulette_retention_time');
            $retention_time = 0;
            if($retention_time_config) {
                $retention_time = isset($retention_time_config[$config_key]) ? $retention_time_config[$config_key] : ( isset($retention_time_config['default'])? $retention_time_config['default']: 0);
                $this->utils->debug_log(__METHOD__." Use Config",['config' => $retention_time_config,'retention_time'=>$retention_time]);
            }
        }
        return $retention_time;
    }

    /**
     * run bonus condition checker
     * @param  array $description original description in rule
     * @param  array $extra_info
     * @param  boolean $dry_run
     * @return  array ['success'=> success, 'message'=> errorMessageLang]
     */
    protected function runBonusConditionChecker($description, &$extra_info, $dry_run)
    {
        $success = false;
        $errorMessageLang = null;
        $allowed_date = $description['allowed_date'];
        $today_start = $this->get_date_type(self::DATE_TODAY_START);
        $now_date = $this->get_date_type(self::TO_TYPE_NOW);
        if (!$this->utils->isTimeoutNow($today_start, $this->getRetentionTime($description))) {
            $today_start = $this->get_date_type(self::DATE_YESTERDAY_START);
        }

        $this->appendToDebugLog('runBonusConditionChecker date params start', ['today_start'=>$today_start, 'now_date'=>$now_date, 'description'=>$description, 'extra_info' => $extra_info]);

        $fromDate = !empty($allowed_date['start']) ? $allowed_date['start'] : $today_start;
        $toDate = !empty($allowed_date['end']) ? $allowed_date['end'] : $now_date;
        $today = $this->utils->getTodayForMysql();

        $this->appendToDebugLog('runBonusConditionChecker date params end', ['description' => $description, 'fromDate' => $fromDate, 'toDate' => $toDate]);

        if ($this->process_mock('today', $today)) {
            //use mock data
            $this->appendToDebugLog('use mock today', ['today'=>$today]);
        }

        $result = $this->checkCustomizeBonusCondition($fromDate, $toDate, $extra_info, $description, $errorMessageLang, $dry_run);

        if (array_key_exists('bonus_amount', $result)) {
            unset($result['bonus_amount']);
        }

        if (array_key_exists('deposit_amount', $result)) {
            unset($result['deposit_amount']);
        }

        return $result;
    }

    /**
     * generate withdrawal condition
     * @param  array $description original description in rule
     * @param  array $extra_info
     * @param  boolean $dry_run
     * @return  array ['success'=> success, 'message'=> errorMessageLang, 'withdrawal_condition_amount'=> withdrawal condition amount]
     */
    protected function generateWithdrawalCondition($description, &$extra_info, $dry_run)
    {
        $success = false;
        $errorMessageLang = null;
        $withdrawal_condition_amount = 0;

        $result = $this->releaseBonus($description, $extra_info, $dry_run);

        $times = $description['turnover'];
        $bonus_amount = $result['bonus_amount'];
        $this->appendToDebugLog('get bonus_amount and times', ['bonus_amount'=>$bonus_amount, 'times'=>$times]);

        if ($times > 0) {
            $withdrawal_condition_amount = $bonus_amount * $times;
            $success = $withdrawal_condition_amount > 0;
        } else {
            $errorMessageLang='Lost bet_condition_times in settings';
        }

        $result=['success'=>$success, 'message'=>$errorMessageLang, 'withdrawal_condition_amount'=>round($withdrawal_condition_amount, 2)];
        return $result;
    }

    /**
     * generate transfer condition
     * @param  array $description original description in rule
     * @param  array $extra_info exchange data
     * @param  boolean $dry_run
     * @return  array ['success'=> success, 'message_lang'=> errorMessageLang, 'withdrawal_condition_amount'=> withdrawal condition amount]
     */
    protected function generateTransferCondition($description, &$extra_info, $dry_run)
    {
        return $this->returnUnimplemented();
    }

    /**
     * release bonus
     * @param  array $description original description in rule
     * @param  array $extra_info
     * @param  boolean $dry_run
     * @return  array ['success'=> success, 'message'=> errorMessageLang, 'bonus_amount'=> bonus amount]
     */
    protected function releaseBonus($description, &$extra_info, $dry_run)
    {

        $this->appendToDebugLog('releaseBonus', ['extra_info'=>$extra_info]);
        $success = false;
        $errorMessageLang = null;
        $bonus_amount = 0;
        $allowed_date = $description['allowed_date'];
        $today_start = $this->get_date_type(self::DATE_TODAY_START);
        $now_date = $this->get_date_type(self::TO_TYPE_NOW);
        if (!$this->utils->isTimeoutNow($today_start, $this->getRetentionTime($description))) {
            $today_start = $this->get_date_type(self::DATE_YESTERDAY_START);
        }

        $fromDate = !empty($allowed_date['start']) ? $allowed_date['start'] : $today_start;
        $toDate = !empty($allowed_date['end']) ? $allowed_date['end'] : $now_date;
        $today = $this->utils->getTodayForMysql();

        if ($this->process_mock('today', $today)) {
            //use mock data
            $this->appendToDebugLog('use mock today', ['today'=>$today]);
        }

        $request = $this->checkCustomizeBonusCondition($fromDate, $toDate, $extra_info, $description, $errorMessageLang, $dry_run);

        if ($request['success']) {
            return $request;
        }
        $result =['success' => $success, 'message' => $errorMessageLang, 'bonus_amount' => $bonus_amount];
        return $result;
    }

    private function checkCustomizeBonusCondition($fromDate, $toDate, &$extra_info, $description, &$errorMessageLang, $dry_run = false)
    {
        $success = false;
        $bonus_amount = isset($extra_info['bonus_amount']) ? $extra_info['bonus_amount'] : 0;
        $deposit_amount = 0;
        $promoRuleId = $this->promorule['promorulesId'];
        $release_date = $description['release_date'];
        $errorMessageLang = '';
        $username = $this->getPlayerInfoById($this->playerId)['username'];
        $rouletteName = $description['rouletteName'];
        $cmsId = $description['cmsId'];
        $pre_chech_cms_id = isset($description['pre_chech_cms_id']) ? $description['pre_chech_cms_id'] : null;
        $max_amount_limit = isset($description['max_amount_limit']) ? $description['max_amount_limit'] : 2998;
        $exclusion_promo_list = isset($description['roulette_exclusion_promo_list']) ? $description['roulette_exclusion_promo_list'] : [];

        $make_up_spins_list = isset($description['make_up_spins_list']) ? $description['make_up_spins_list'] : [];
        $make_up_limit = isset($description['make_up_limit']) ? $description['make_up_limit'] : 5;

        $this->appendToDebugLog('checkCustomizeBonusCondition', ['fromDate'=>$fromDate, 'toDate'=>$toDate, 'description'=>$description, 'username' => $username, 'cmsId' => $cmsId, 'extra_info' => $extra_info]);

        try{

            $is_roulette_api = (isset($extra_info['is_roulette_api']) && $extra_info['is_roulette_api'] == true) ? true: false;
            $spin_token = isset($extra_info['player_token'])? $extra_info['player_token']: false;

            if(! $dry_run && ( !$is_roulette_api || !$spin_token)){
                throw new Exception("Unable to apply.");
            }

            if ($fromDate != $this->get_date_type(self::DATE_YESTERDAY_START)) {
                $release_date['start'] = $fromDate;
                $release_date['end'] = $toDate;
            }

            //get today promo count
            $api_name = 'roulette_api_' . $rouletteName;
            $this->load->library('roulette/'.$api_name);
            $roulette_api = $this->$api_name;
            $verify_res = $roulette_api->verifyRouletteSpinTimes($this->playerId, $cmsId);
            $checkReleasedBonusToday = key_exists('used_times', $verify_res) ? $verify_res['used_times'] : 0;

            //check token $singStr = $username.$used_times.$roulette_name.$promo_cms_id;
            $sign_str = $username.$checkReleasedBonusToday.$rouletteName.$cmsId;
            $sign_token = md5(strtolower($sign_str));
            $remain_times = key_exists('remain_times', $verify_res) ? $verify_res['remain_times'] : 0;
            $total_times = key_exists('total_times', $verify_res) ? $verify_res['total_times'] : 0;

            $this->appendToDebugLog('sign token', ['player_id' => $this->playerId, 'sign_str'=>$sign_str, 'sign_token'=>$sign_token, 'verify_res'=>$verify_res]);

            if (!$dry_run && ($sign_token != $spin_token)) {
                throw new Exception("Unable to apply because missing token.");
            }

            // $spin_conditions = isset($description['spin_conditions'])? $description['spin_conditions'] : false;

            if (!empty($verify_res)) {
                //get today deposit amount
                // $verification_amount_type = isset($spin_conditions['type'])? $spin_conditions['type'] : false;
                // $verification_amount = 0;
                // $times_by_amount = 0;

                // $per_amount = isset($spin_conditions['per_amount'])? $spin_conditions['per_amount'] : 499;
                // $per_earn = isset($spin_conditions['earn'])? $spin_conditions['earn'] : 1;

                // switch ($verification_amount_type) {

                //     case 'deposit':
                //         $deposit_list = $this->callHelper('listDepositTransactions', [$this->playerId, $fromDate, $toDate, $per_amount, $max_amount_limit]);
                //         break;

                //     case 'bet':
                //     default:
                //         // list($betting_amount) = $this->callHelper('getBetsAndDepositByDate', [$fromDate, $toDate]);
                //         // $verification_amount = (int) $betting_amount;
                //         break;
                // }

                // if (count($deposit_list) > 0) {
                //     foreach ($deposit_list as $deposit) {
                //         if (!empty($deposit->player_promo_id) || !is_null($deposit->player_promo_id)) {
                //             $data = $this->CI->promorules->getPromoRulesId($deposit->player_promo_id);
                //             $deposit_ruleid = $data['promorulesId'];
                //             if ($promoRuleId != $deposit_ruleid) {
                //                 $this->appendToDebugLog(__METHOD__,'continue promo', ['player_promo_id'=>$deposit->player_promo_id, 'deposit'=>$deposit, 'deposit_ruleid'=>$deposit_ruleid, 'promorulesId' => $promoRuleId]);
                //                 continue;
                //             }
                //         }
                //         $times_by_amount += floor(($deposit->amount / $per_amount));
                //     }
                // }

                // $earn_times = $times_by_amount * $per_earn;
                $available_spin = ($remain_times >= 1) ? true : false;

                $this->appendToDebugLog('checkCustomizeBonusCondition check params detail', ['player_id' => $this->playerId, 'release_date' => $release_date, 'checkReleasedBonusToday' => $checkReleasedBonusToday, 'promoRuleId' => $promoRuleId, 'available_spin' => $available_spin, 'total_times' => $total_times, 'remain_times' => $remain_times]);

                if (!$available_spin) {
                    throw new Exception("Don't have available spin. Today total available_spin :[$available_spin], total Spin:[$total_times], Applied:[$checkReleasedBonusToday]");
                }

                if (! is_null($pre_chech_cms_id)) {
                    // list($promoruleChech, $promoChechCmsSettingId) = $this->CI->promorules->getByCmsPromoCodeOrId($pre_chech_cms_id);
                    // $checkReleasedBonusTodayByChechCmsId = $this->callHelper('count_approved_promo', [$promoruleChech['promorulesId'], self::DATE_TYPE_TODAY]);
                    $verify_res = $roulette_api->verifyRouletteSpinTimes($this->playerId, $pre_chech_cms_id);
                    $checkReleasedBonusTodayByChechCmsId = key_exists('used_times', $verify_res) ? $verify_res['used_times'] : 0;

                    $this->appendToDebugLog('pre chech other cmsId', ['pre_chech_cms_id' => $pre_chech_cms_id, 'checkReleasedBonusTodayByChechCmsId' => $checkReleasedBonusTodayByChechCmsId, 'promoRuleId' => $promoRuleId, 'verify_res' => $verify_res]);

                    $checkReleasedBonusToday = $checkReleasedBonusToday + $checkReleasedBonusTodayByChechCmsId;
                }

                $daily_spin_limits = $description['daily_spin_limits'];

                if (in_array($username, $make_up_spins_list)) {
                    $isReleasedBonusToday = $this->callHelper('get_request_count_player_promo',
                        [
                            $this->playerId,
                            $pre_chech_cms_id,
                            $this->get_date_type(self::DATE_TODAY_START)
                        ]);
                    $checkReleasedBonusToday = $checkReleasedBonusToday - $checkReleasedBonusTodayByChechCmsId;
                    $daily_spin_limits = $make_up_limit;

                    $this->appendToDebugLog('player in list', ['isReleasedBonusToday' => $isReleasedBonusToday, 'checkReleasedBonusToday' => $checkReleasedBonusToday, 'daily_spin_limits' => $daily_spin_limits]);
                }
                
                if (!($daily_spin_limits > $checkReleasedBonusToday)) {
                    throw new Exception("Reach the daily spin, Limit:[$daily_spin_limits], Apply:[$checkReleasedBonusToday]");
                }
            } else {
                throw new Exception("Empty verify Roulette SpinTimes.");
            }

            $success = true;

        }catch (Exception $e){

            $success = false;
            $errorMessageLang = $e->getMessage();
            $result['continue_process_after_script'] = FALSE;

        } finally {
            $result=[
                'success' => $success,
                'message' => $errorMessageLang,
                'bonus_amount' => $bonus_amount,
                'deposit_amount' => $deposit_amount
            ];

            return $result;
        }
    }
}