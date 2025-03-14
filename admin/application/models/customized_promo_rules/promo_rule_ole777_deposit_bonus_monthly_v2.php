<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

require_once dirname(__FILE__) .'/abstract_promo_rule.php';

/**
 *
 * 存送优惠
 *
 * OGP-16736
 *
 * 10-17号内最高的一笔有效存款
 * 如果升级报表里的最后一条记录并且是这个月之前的
 * 存在这个纪录就列出小于等于这个级别的所有级别
 * 并且计算上月存款与上月流水是否符合现有级别
 * 不符合就再检查上一个级别是否符合资格

* Bonus condition && Bonus release:
{
    "class": "Promo_rule_ole777_deposit_bonus_monthly_v2",
    "allowed_date": {
        "start": "10",
        "end": "17"
    },
    "bonus_settings": {
        "VIP1": {"percentage": 30, "max_bonus": 277, "min_bets": 250000, "min_deposit": 25000, "peroid_max_deposit": 925},
        "VIP2": {"percentage": 30, "max_bonus": 777, "min_bets": 1000000, "min_deposit": 100000, "peroid_max_deposit": 2600},
        "VIP3": {"percentage": 35, "max_bonus": 1177, "min_bets": 2500000, "min_deposit": 250000, "peroid_max_deposit": 3633},
        "VIP4": {"percentage": 35, "max_bonus": 2777, "min_bets": 6000000, "min_deposit": 600000, "peroid_max_deposit": 7935},
        "VIP5": {"percentage": 37, "max_bonus": 5777, "min_bets": 15000000, "min_deposit": 1500000, "peroid_max_deposit": 15615},
        "VIP6": {"percentage": 40, "max_bonus": 10000, "min_bets": 25000000, "min_deposit": 2500000, "peroid_max_deposit": 25000},
    }
}

* Withdraw Condition (can add times):
{
    "bet_condition_times": 10
}

* QA Testing (can add specific date):
{
    "force_date": true,
    "minDate": "2020-03-10",
    "maxDate": "2020-03-17",
    "fromDate": "2020-02-01",
    "toDate": "2020-02-29"
}

* Promo Manager Mock For Class:
{
    "today":"2020-03-10",
    "getLastUpgradeLevelOrCurrentLevel":"4",
    "getBetsAndDepositByDate": [250000,25000],
    "getTopDepositByDate": 920
}
 *
 *
 */
class Promo_rule_ole777_deposit_bonus_monthly_v2 extends Abstract_promo_rule{

	public function init($playerId, $promorule, $playerBonusAmount = null, $depositAmount=null){
		parent::init($playerId, $promorule, $playerBonusAmount, $depositAmount);
	}

	public function getClassName(){
		return 'Promo_rule_ole777_deposit_bonus_monthly_v2';
	}

	/**
	 * run bonus condition checker
	 * @param  array $description original description in rule
	 * @param  array $extra_info
	 * @param  boolean $dry_run
	 * @return  array ['success'=> success, 'message'=> errorMessageLang, 'continue_process_after_script' => FALSE]
	 */
	protected function runBonusConditionChecker($description, &$extra_info, $dry_run){
		$success=false;
		$errorMessageLang=null;
        $availableLevelId = null;
        $topDepositAmount = 0;

		$result=['success'=>$success, 'message'=>$errorMessageLang, 'continue_process_after_script' => FALSE];

		$today=$this->utils->getTodayForMysql();
		if($this->process_mock('today', $today)){
			//use mock data
			$this->appendToDebugLog('use mock today', ['today'=>$today]);
		}

        $bonus_settings=$description['bonus_settings'];

        //check date
        $allowed_date=$description['allowed_date'];
        $this->appendToDebugLog('check allowed_date', ['allowed_date'=>$allowed_date]);
        $d=new DateTime($today);
        $currentDate=$d->format('Y-m-d');
        $minDate=$d->format('Y-m-').$allowed_date['start'];
        $maxDate=$d->format('Y-m-').$allowed_date['end'];

        $d=new DateTime($d->format('Y-m').'-01');
        $d->modify('-1 month');
        $fromDate=$d->format('Y-m').'-01 '.Utils::FIRST_TIME;
        $toDate=$d->format('Y-m-t').' '.Utils::LAST_TIME;

        if(!empty($description['force_date'])){
            $this->appendToDebugLog('origin date result', ['minDate'=>$minDate, 'maxDate'=>$maxDate, 'fromDate'=>$fromDate, 'toDate'=>$toDate, 'currentDate'=>$currentDate]);
            list($minDate, $maxDate, $fromDate, $toDate, $currentDate) = $this->fetchTestDate($description,$minDate, $maxDate, $fromDate, $toDate);//for qa testing
            $this->appendToDebugLog('fetchTestDate result', ['minDate'=>$minDate, 'maxDate'=>$maxDate, 'fromDate'=>$fromDate, 'toDate'=>$toDate, 'currentDate'=>$currentDate]);
        }

        if($currentDate>=$minDate && $currentDate<=$maxDate){
            $availableLevelId=$this->searchAvailableLevelId($bonus_settings, $fromDate, $toDate, $extra_info, $description, $errorMessageLang);

            if(!empty($availableLevelId)){
                $topDepositAmount=$this->getTopDepositInApplyPeriod($minDate, $maxDate, $errorMessageLang);
                $this->appendToDebugLog('get getTopDepositInApplyPeriod result', ['topDepositAmount'=>$topDepositAmount, 'errorMessageLang'=>$errorMessageLang]);
            }else{
                $this->appendToDebugLog('get searchAvailableLevelId failed', ['availableLevelId'=>$availableLevelId, 'errorMessageLang'=>$errorMessageLang]);
            }

            $success=!empty($availableLevelId) && !empty($topDepositAmount);
        }else{
            $errorMessageLang='非申请时间，请于每月10~17日间申请。';
        }
        $this->appendToDebugLog('condition result', ['availableLevelId'=>$availableLevelId, 'topDepositAmount'=>$topDepositAmount]);
        $result=['success'=>$success, 'message'=>$errorMessageLang, 'continue_process_after_script' => FALSE];
		return $result;
	}

    /**
     * generate withdrawal condition
     * @param  array $description original description in rule
     * @param  array $extra_info
     * @param  boolean $dry_run
     * @return  array ['success'=> success, 'message'=> errorMessageLang, 'withdrawal_condition_amount'=> withdrawal condition amount]
     */
    protected function generateWithdrawalCondition($description, &$extra_info, $dry_run){
        $success = false;
        $errorMessageLang = null;
        $withdrawal_condition_amount = 0;

        $result = $this->releaseBonus($description, $extra_info, $dry_run);


        $times = $description['bet_condition_times'];
        $bonus_amount = $result['bonus_amount'];
        $deposit_amount = $result['deposit_amount'];
        $this->appendToDebugLog('get bonus_amount and deposit_amount and times', ['bonus_amount'=>$bonus_amount, 'deposit_amount'=>$deposit_amount, 'times'=>$times]);

        if($times > 0){
            $withdrawal_condition_amount = ($bonus_amount + $deposit_amount) * $times;
            $success = $withdrawal_condition_amount > 0;
        }else{
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
    protected function generateTransferCondition($description, &$extra_info, $dry_run){
        return $this->returnUnimplemented();
    }

    /**
     * release bonus
     * @param  array $description original description in rule
     * @param  array $extra_info
     * @param  boolean $dry_run
     * @return  array ['success'=> success, 'message'=> errorMessageLang, 'bonus_amount'=> bonus amount]
     */
    protected function releaseBonus($description, &$extra_info, $dry_run){
        $success=false;
        $errorMessageLang=null;
        $bonus_amount=0;
        $availableLevelId = null;
        $topDepositAmount = 0;

        $bonus_settings=$description['bonus_settings'];
        $allowed_date=$description['allowed_date'];

        $today=$this->utils->getTodayForMysql();
        if($this->process_mock('today', $today)){
            //use mock data
            $this->appendToDebugLog('use mock today', ['today'=>$today]);
        }

        $d=new DateTime($today);
        $d=new DateTime($d->format('Y-m').'-01');
        $d->modify('-1 month');
        $fromDate=$d->format('Y-m').'-01 '.Utils::FIRST_TIME;
        $toDate=$d->format('Y-m-t').' '.Utils::LAST_TIME;

        $d=new DateTime($today);
        $minDate=$d->format('Y-m-').$allowed_date['start'];
        $maxDate=$d->format('Y-m-').$allowed_date['end'];

        if(!empty($description['force_date'])){
            $this->appendToDebugLog('origin date result', ['minDate'=>$minDate, 'maxDate'=>$maxDate, 'fromDate'=>$fromDate, 'toDate'=>$toDate]);
            list($minDate, $maxDate, $fromDate, $toDate, $currentDate) = $this->fetchTestDate($description,$minDate, $maxDate, $fromDate, $toDate);//for qa testing
            $this->appendToDebugLog('fetchTestDate result', ['minDate'=>$minDate, 'maxDate'=>$maxDate, 'fromDate'=>$fromDate, 'toDate'=>$toDate]);
        }

        $availableLevelId=$this->searchAvailableLevelId($bonus_settings, $fromDate, $toDate, $extra_info, $description, $errorMessageLang);
        $topDepositAmount=$this->getTopDepositInApplyPeriod($minDate, $maxDate, $errorMessageLang);
        $this->appendToDebugLog('release bonus data',['allowed_date'=>$allowed_date, 'availableLevelId'=>$availableLevelId, 'topDepositAmount'=>$topDepositAmount]);

        if(!empty($availableLevelId) && !empty($topDepositAmount)){
            $setting=$bonus_settings[$availableLevelId];
            if($topDepositAmount>=$setting['peroid_max_deposit']){
                $bonus_amount=$setting['max_bonus'];
            }else{
                $bonus_amount=($setting['percentage']/100)*$topDepositAmount;
                if($bonus_amount>$setting['max_bonus']){
                    $bonus_amount=$setting['max_bonus'];
                }
            }
            $success=true;
            $this->appendToDebugLog('calc bonus', ['setting'=>$setting, 'bonus_amount'=>$bonus_amount]);
        }

        $result=['success'=>$success, 'message'=>$errorMessageLang, 'bonus_amount'=>$bonus_amount, 'deposit_amount'=>$topDepositAmount];
        return $result;
    }

    private function getTopDepositInApplyPeriod($minDate, $maxDate, &$errorMessageLang){
        $amount = 0;
        $topDepositAmount = $this->callHelper('getTopDepositByDate', [$minDate, $maxDate]);
        $this->appendToDebugLog('getTopDepositByDate',['single_max_deposit'=>$topDepositAmount]);

        if(!empty($topDepositAmount)){
            $amount = $topDepositAmount;
        }else{
            $errorMessageLang = 'No deposit record in apply period';
            $this->appendToDebugLog('No deposit record in apply period');
        }

        return $amount;
    }

    private function searchAvailableLevelId($bonus_settings, $fromDate, $toDate, &$extra_info, $description, &$errorMessageLang){
        $success = false;
        $availableLevelId=null;
        $endAt=$this->callHelper('getLastMonthEndTime', []); //取得上個月最後一天
        if(!empty($description['force_date']) && !empty($description['endAt'])){
            $endAt = $description['endAt']; //取得註冊時間 ～ 特定時間點的 最新level id
            $this->appendToDebugLog('use customized endAt to getLastUpgradeLevelOrCurrentLevel', ['endAt'=>$endAt]);
        }
        $maxLevelId=$this->callHelper('getLastUpgradeLevelOrCurrentLevel',[$endAt]); //取得至上個月底最新的levelId
        $this->appendToDebugLog('search level id getLastUpgradeLevelOrCurrentLevel', ['endAt'=>$endAt, 'maxLevelId'=>$maxLevelId]);
        $levelIdList=$this->callHelper('getLevelIdListDownToFirstLevel',[$maxLevelId]); //取得當前level 降冪排列 到 此vip group 的第一個level的清單
        $this->appendToDebugLog('getLevelIdListDownToFirstLevel',['levelIdList'=>$levelIdList]);

        if(!empty($levelIdList)){
            $existLevel=false;
            foreach ($levelIdList as $levelId) {
                if(array_key_exists($levelId, $bonus_settings)){
                    $existLevel=true;
                    break;
                }
            }
            $this->appendToDebugLog('existLevel',['existLevel'=>$existLevel]);
            if($existLevel){
                //check bets and deposit
                list($bets, $deposit)=$this->callHelper('getBetsAndDepositByDate', [$fromDate, $toDate]);
                $this->appendToDebugLog('getBetsAndDepositByDate',['bets'=>$bets, 'deposit'=>$deposit]);
                //high level to low level
                foreach ($levelIdList as $levelId) {
                    if(array_key_exists($levelId, $bonus_settings)){
                        $setting=$bonus_settings[$levelId];
                        $min_bets=$setting['min_bets'];
                        $min_deposit=$setting['min_deposit'];
                        $this->appendToDebugLog('check bets and deposit', [
                            'playerId'=>$this->playerId, 'fromDate'=>$fromDate, 'toDate'=>$toDate,
                            'bets'=>$bets, 'deposit'=>$deposit,
                            'min_bets'=>$min_bets, 'min_deposit'=>$min_deposit,
                        ]);
                        if($bets>=$min_bets){
                            if($deposit>=$min_deposit){
                                $availableLevelId=$levelId;
                                $success=true;
                                if(isset($description['contact_live_chat_to_apply']) && $description['contact_live_chat_to_apply']){
                                    $button = '<a href="javascript:void(0)" onclick="' . $this->utils->getLiveChatOnClick() . '">' . lang('ole777_promo.contact_live_chat_to_apply') . '</a>';
                                    $extra_info['contact_live_chat_to_apply'] = $button;
                                }
                            }else{
                                $errorMessageLang = '上月流水或存款未达标准，无法申请优惠。';
                            }
                        }else{
                            $errorMessageLang = '上月流水或存款未达标准，无法申请优惠。';
                        }

                        if(!$success){
                            $this->appendToDebugLog('ignore level because not reach condition', [
                                'endAt'=>$endAt, 'maxLevelId'=>$maxLevelId, 'levelId'=>$levelId, 'last month bets < '.$min_bets=>$bets, 'last month deposit < '.$min_deposit=>$deposit, 'errorMessageLang'=>$errorMessageLang]);
                        }else{
                            $errorMessageLang = null;
                            $this->appendToDebugLog('found success level',['success'=>$success, 'errorMessageLang'=>$errorMessageLang, 'maxLevelId'=>$maxLevelId, 'levelId'=>$levelId]);
                            break;
                        }
                    }else{
                        $this->appendToDebugLog('not exist this level in setting', ['levelId'=>$levelId]);
                    }
                } // foreach ($levelIdList as $levelId)
            }else{ //if($existLevel){
                $errorMessageLang = 'Not right group level';
                $this->appendToDebugLog('never find exist level');
            }
        }else{
            $errorMessageLang = 'Not right group level';
        }

        return $availableLevelId;
    }

    private function fetchTestDate($description, $minDate, $maxDate, $fromDate, $toDate){
        $minDate = !empty($description['minDate']) ? $description['minDate'] : $minDate;
        $maxDate = !empty($description['maxDate']) ? $description['maxDate'] : $maxDate;
        $fromDate = !empty($description['fromDate']) ? $description['fromDate'] : $fromDate;
        $toDate = !empty($description['toDate']) ? $description['toDate'] : $toDate;
        $currentDateTime = !empty($description['currentDate']) ? $description['currentDate'] : $this->utils->getNowForMysql();
        return [$minDate, $maxDate, $fromDate, $toDate, $currentDateTime];
    }
}
