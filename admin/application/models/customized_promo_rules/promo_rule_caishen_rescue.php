<?php if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

require_once dirname(__FILE__) .'/abstract_promo_rule.php';

/**
 *
 * caishen 救援金
 *
 *

OGP-17079

>=100 and <1000 => 1%
>=1000 and <10000 => 2%
>=10000 => 3%

max bonus => 188888

withdrawal condition: 1x bet amount

每天限一次发放奖金
与余额无关
-result-bonus>=100
超过奖金限额部分将自动去掉


救援金计算方式:
玩家在老虎机/捕鱼/棋牌游戏中总输值减总赢值减玩家获得的红利彩金=实际亏损金额

总输值-总赢值-红利彩金≥100

仅限申请当日的救援金。
例：玩家当日获得红利彩金78元，当日在老虎机/捕鱼/棋牌游戏中负盈利10078元，则可申请获得救援金为（人民币亏损金额10078-78*救援彩金比例3%=300元）

奖金金额不高于当日总存款金额的1-3%,与亏损比例相同（若当日无存款则无法申请）

>=min_lossing and <max_lossing

condition:
{
    "class": "promo_rule_caishen_rescue",
    "min_lossing": 100,
    "bonus_settings": [
        {"min_lossing": 100, "max_lossing": 1000, "bonus_percentage": 1},
        {"min_lossing": 1000, "max_lossing": 10000, "bonus_percentage": 2},
        {"min_lossing": 10000, "max_lossing": -1, "bonus_percentage": 3}
    ],
    "max_bonus": 188888
}

mock:
{
    "today": "2018-09-20",
    "totalDepositByPlayerAndDateTime": 10000,
    "getTotalAmountFromHourlyReportByPlayerAndDateTime": [1000,-1000,0,1000],
    "totalReleasedBonusByPlayerAndDateTime": 100
}

decline
{
    "totalDepositByPlayerAndDateTime": 150,
    "getTotalAmountFromHourlyReportByPlayerAndDateTime": [150,-150,0,150],
    "totalReleasedBonusByPlayerAndDateTime": 100
}

 *
 *
 */
class Promo_rule_caishen_rescue extends Abstract_promo_rule{

    public function init($playerId, $promorule, $playerBonusAmount = null, $depositAmount=null){
        parent::init($playerId, $promorule, $playerBonusAmount, $depositAmount);
    }

    public function getClassName(){
        return 'Promo_rule_caishen_rescue';
    }

    /**
     * run bonus condition checker
     * @param  array $description original description in rule
     * @param  array $extra_info
     * @param  boolean $dry_run
     * @return  array ['success'=> success, 'message'=> errorMessageLang]
     */
    protected function runBonusConditionChecker($description, &$extra_info, $dry_run){
        $success=false;
        $errorMessageLang=null;
        $bonusAmount=0;
        $today=$this->utils->getTodayForMysql();
        if($this->process_mock('today', $today)){
            //use mock data
            $this->appendToDebugLog('use mock today', ['today'=>$today]);
        }
        $min_lossing=$description['min_lossing'];
        $bonus_settings=$description['bonus_settings'];
        $max_bonus=$description['max_bonus'];

        $success=$this->processBonusRule($today, $min_lossing, $bonus_settings, $max_bonus, $bonusAmount, $errorMessageLang);

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

        return $this->returnUnimplemented();
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
        $bonusAmount=0;
        $min_lossing=$description['min_lossing'];
        $bonus_settings=$description['bonus_settings'];
        $max_bonus=$description['max_bonus'];
        $today=$this->utils->getTodayForMysql();
        if($this->process_mock('today', $today)){
            //use mock data
            $this->appendToDebugLog('use mock today', ['today'=>$today]);
        }

        $success=$this->processBonusRule($today, $min_lossing, $bonus_settings, $max_bonus, $bonusAmount, $errorMessageLang);

        $result=['success'=>$success, 'message'=>$errorMessageLang, 'bonus_amount'=>$bonusAmount];
        return $result;
    }

    private function processBonusRule($today, $min_lossing, $bonus_settings, $max_bonus, &$bonusAmount, &$errorMessageLang){
        $success=false;
        $startDatetime=$today.' '.Utils::FIRST_TIME;
        $endDatetime=$today.' '.Utils::LAST_TIME;
        //available deposit
        $totalDepositToday=$this->callHelper('totalDepositByPlayerAndDateTime',
            [$this->playerId, $startDatetime, $endDatetime]);

        $this->appendToDebugLog('totalDepositToday: '.$totalDepositToday,
            ['today'=>$today, 'playerId'=>$this->playerId, 'startDatetime'=>$startDatetime, 'endDatetime'=>$endDatetime]);

        if($totalDepositToday>0){
            list($totalBet, $totalResult, $totalWin, $totalLoss)=$this->callHelper('getTotalAmountFromHourlyReportByPlayerAndDateTime',
                [$this->playerId, $startDatetime, $endDatetime]);
            $this->appendToDebugLog('getTotalAmountFromHourlyReportByPlayerAndDateTime',
                ['totalBet'=>$totalBet, 'totalResult'=>$totalResult, 'totalWin'=>$totalWin, 'totalLoss'=>$totalLoss]);
            //get total bonus
            $totalBonus=$this->callHelper('totalReleasedBonusByPlayerAndDateTime',
                [$this->playerId, $startDatetime, $endDatetime]);
            $this->appendToDebugLog('totalReleasedBonusByPlayerAndDateTime: '.$totalBonus);
            $totalReallyLossing=-$totalResult-$totalBonus;
            $this->appendToDebugLog('totalReallyLossing: '.$totalReallyLossing, ['totalResult'=>$totalResult, 'totalBonus'=>$totalBonus]);
            if($totalReallyLossing >= $min_lossing){
                $found=false;
                $percentage=0;
                foreach ($bonus_settings as $setting) {
                    if($setting['min_lossing']<=$totalReallyLossing &&
                        //max_lossing<0 means no limit
                        ($totalReallyLossing<$setting['max_lossing'] || $setting['max_lossing']<0)){
                        //found level
                        $percentage=$setting['bonus_percentage']/100;
                        $bonusAmount=$totalReallyLossing*$percentage;
                        $found=true;
                        break;
                    }
                }
                if($found){
                    $this->appendToDebugLog('$bonusAmount > $totalDepositToday*$percentage',
                        ['bonusAmount'=>$bonusAmount, 'totalDepositToday'=>$totalDepositToday, 'percentage'=>$percentage]);
                    //check bonus limit
                    if($bonusAmount > $totalDepositToday*$percentage){
                        $this->appendToDebugLog('adjust bonus amount, because ',
                            ['bonusAmount'=>$bonusAmount, 'totalDepositToday'=>$totalDepositToday, 'percentage'=>$percentage]);
                        $bonusAmount=$totalDepositToday*$percentage;
                    }
                    $this->appendToDebugLog('$bonusAmount > $max_bonus',
                        ['bonusAmount'=>$bonusAmount, 'max_bonus'=>$max_bonus]);
                    //check max bonus
                    if($bonusAmount > $max_bonus){
                        $this->appendToDebugLog('adjust bonus amount, because max bonus',
                            ['bonusAmount'=>$bonusAmount, 'max_bonus'=>$max_bonus]);
                        $bonusAmount=$max_bonus;
                    }
                    $success=$bonusAmount>0;
                }else{
                    $errorMessageLang='Not fit any bonus setting';
                }
            }else{
                $errorMessageLang='No enough lossing';
            }
        }else{
            $errorMessageLang='No available deposit';
        }

        return $success;
    }

}
