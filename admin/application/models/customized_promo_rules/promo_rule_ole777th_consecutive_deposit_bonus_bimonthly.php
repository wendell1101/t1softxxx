<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

require_once dirname(__FILE__) .'/abstract_promo_rule.php';

/**
 *
 * 月优惠
 * 连续 [ 2个月] 存款最低 [2000THB/月] , 可以获得 [ 300THB] Bonus
 * 连续 [ 4个月] 存款最低 [2000THB/月] , 可以获得 [ 500THB] Bonus
 * 连续 [ 6个月] 存款最低 [2000THB/月] , 可以获得 [ 800THB] Bonus
 * 连续 [ 8个月] 存款最低 [2000THB/月] , 可以获得 [1200THB] Bonus
 * 连续 [10个月] 存款最低 [2000THB/月] , 可以获得 [1600THB] Bonus
 * 连续 [12个月] 存款最低 [2000THB/月] , 可以获得 [2000THB] Bonus
 *
 * 完成 以上所有条件的玩家，进入下个阶段:
 * 接下来的连续[6个月] 存款最低 [2000THB/月] , 可以获得 [2000THB] Bonus 并无法再次参与2,4,6,8,10,12个月的优惠。
 *
 * 提款条件: Bonus金额的1倍流水
 *
 * 中断的情况：重新计算
 *
 * 例子:
 * 02月份存款 2000 THB， 3月份存款 1000 THB， 不符合条件。
 * 04月份存款 2000 THB， 5月份存款 3000 THB， 有符合条件。 获得 300THB Bonus ;
 * 06月份存款 2000 THB， 7月份存款 2000 THB， 有符合条件。 获得 500THB Bonus ；
 * 08月份存款 1000 THB， 9月份存款 2000 THB， 不符合条件。
 * 10月份存款 2000 THB，                     有符合条件。 获得 300THB Bonus ;
 *
 *


* Bonus condition && Bonus release:
{
    "class": "promo_rule_ole777th_consecutive_deposit_bonus_bimonthly",
    "bimonthly_total_deposit": 2000,
    "bonus_settings": [
        {"bonus_amount":  300},
        {"bonus_amount":  600},
        {"bonus_amount":  900},
        {"bonus_amount": 1200},
        {"bonus_amount": 1600},
        {"bonus_amount": 2000},
        {"bonus_amount": 2000}
    ]
}

舉個例子:
假設優惠開始日期: 2022 5/1

1. #每個月都有存2000
[05月存款2000], 06/01會檢查 `上次發放日期(N/A)`, 發現找不到記錄, 並且距離優惠開始日只有一個月 => 因此忽略此玩家
[06月存款2000], 07/01會檢查 `上次發放日期(N/A)`, 發現找不到記錄, 並且距離優惠開始日已滿兩個月, 就會檢查上兩個月(05,06)存款 => 發現符合資格並發放優惠
[07月存款2000], 08/01會檢查 `上次發放日期(7/1)`, 距離上次發放日僅有ㄧ個月 => 因此忽略此玩家
[08月存款2000], 09/01會檢查 `上次發放日期(7/1)`, 距離上次發放日已有兩個月, 就會檢查發放後的兩個月(07,08月)存款 => 發現符合資格並發放優惠
[09月存款2000], 10/01會檢查 `上次發放日期(9/1)`, 距離上次發放日僅有ㄧ個月 => 因此忽略此玩家
[10月存款2000], 11/01會檢查 `上次發放日期(9/1)`, 距離上次發放日已有兩個月, 就會檢查發放後的兩個月(09,10月)存款 => 發現符合資格並發放優惠
以此類推...etc

2. #有中斷的情況
[05月存款2000], 06/01會檢查 `上次發放日期(N/A)`, 發現找不到記錄, 並且距離優惠開始日只有一個月 => 因此忽略此玩家
[06月存款2000], 07/01會檢查 `上次發放日期(N/A)`, 發現找不到記錄, 並且距離優惠開始日已滿兩個月, 就會檢查 05,06月存款 => 發現符合資格並發放優惠
[07月存款2000], 08/01會檢查 `上次發放日期(7/1)`, 距離上次發放日只有一個月 => 因此忽略此玩家
[08月存款1000], 09/01會檢查 `上次發放日期(7/1)`, 距離上次發放日已有兩個月, 就會檢查`發放後的兩個月`(07,08月)存款
                                           => 發現不符合資格後, 再檢查`申請日的前兩個月`(08,07月)存款
                                           => 依舊不符合資格
                                           => 因此忽略此玩家
[09月存款2000], 10/01會檢查 `上次發放日期(7/1)`, 距離上次發放日已有兩個月, 就會視為`中斷的情況`, 就會檢查`申請日的前兩個月`(09,08月)存款
                                           => 發現不符合資格
                                           => 因此忽略此玩家
[10月存款2000], 11/01會檢查 `上次發放日期(7/1)`, 距離上次發放日已有兩個月, 就會視為`中斷的情況`, 就會檢查`申請日的前兩個月`(10,09月)存款,
                                           => 發現符合資格並發放優惠
以此類推...etc

 */

class Promo_rule_ole777th_consecutive_deposit_bonus_bimonthly extends Abstract_promo_rule{

    const CHECK_NEXT_N_MONTH_DEPOSIT_TYPE = 'next';
    const CHECK_LAST_N_MONTH_DEPOSIT_TYPE = 'last';

	public function init($playerId, $promorule, $playerBonusAmount = null, $depositAmount=null){
		parent::init($playerId, $promorule, $playerBonusAmount, $depositAmount);
	}

	public function getClassName(){
		return 'Promo_rule_ole777th_consecutive_deposit_bonus_bimonthly';
	}

    public function checkMonthDeposit($description, $cliam_period, $bimonthly_total_deposit, $type, $lastPlayerPromo = null){
	    $result = true;
        $first_time = ' '.Utils::FIRST_TIME;
        $last_time = ' '.Utils::LAST_TIME;

        $nMonth = 2;

        if($cliam_period == 'day') {
            // for test
            $nMonth = 1;

            // for test specify day
            if(!empty($description['nDay'])){
                $nMonth = $description['nDay'];
            }
        }

        if(!empty($lastPlayerPromo['dateApply'])){
            $lastDateApply = $lastPlayerPromo['dateApply'];
        }

        if( ($type == self::CHECK_NEXT_N_MONTH_DEPOSIT_TYPE)){
            if($cliam_period == 'day'){
                // check last day deposit met confition or not (only for QA test)
                $cnt = $nMonth;
                $periodStart = date('Y-m-d', strtotime("+{$cnt} {$cliam_period}", strtotime($lastDateApply))) . $first_time;
                $periodEnd = date('Y-m-d', strtotime("+{$cnt} {$cliam_period}", strtotime($lastDateApply))) . $last_time;
                $periodTotalDeposit = $this->callHelper('sum_deposit_amount', [$periodStart, $periodEnd, 0]);
                $this->appendToDebugLog('check last '.$cnt.' day deposit condition', [
                    'from' => $periodStart, 'to' => $periodEnd, 'total deposit' => $periodTotalDeposit
                ]);

                if($periodTotalDeposit < $bimonthly_total_deposit) {
                    $result = false;
                }
            }else{
                $cnt = $nMonth;
                for($i=0; $i<$nMonth; $i++){
                    if(empty($i)){
                        $periodStart = date('Y-m-01', strtotime($lastDateApply)) . $first_time;
                        $periodEnd = date('Y-m-t', strtotime($lastDateApply)) . $last_time;
                    }else {
                        $periodStart = date('Y-m-01', strtotime("+{$i} {$cliam_period}", strtotime($lastDateApply))) . $first_time;
                        $periodEnd = date('Y-m-t', strtotime("+{$i} {$cliam_period}", strtotime($lastDateApply))) . $last_time;
                    }

                    $periodTotalDeposit = $this->callHelper('sum_deposit_amount', [$periodStart, $periodEnd, 0]);
                    $this->appendToDebugLog('check next '.$cnt.' month condition, deposit not met', [
                        'from' => $periodStart, 'to' => $periodEnd, 'total deposit' => $periodTotalDeposit
                    ]);

                    if($periodTotalDeposit < $bimonthly_total_deposit){
                        $result = false;
                        break;
                    }
                }
            }
        }elseif ($type == self::CHECK_LAST_N_MONTH_DEPOSIT_TYPE){
            if($cliam_period == 'day'){
                // check last day deposit met confition or not (only for QA test)
                $cnt = $nMonth;
                $periodStart = date('Y-m-d', strtotime("-{$cnt} {$cliam_period}")) . $first_time;
                $periodEnd = date('Y-m-d', strtotime("-{$cnt} {$cliam_period}")) . $last_time;
                $periodTotalDeposit = $this->callHelper('sum_deposit_amount', [$periodStart, $periodEnd, 0]);
                $this->appendToDebugLog('check last '.$cnt.' day deposit condition', [
                    'from' => $periodStart, 'to' => $periodEnd, 'total deposit' => $periodTotalDeposit
                ]);

                if($periodTotalDeposit < $bimonthly_total_deposit) {
                    $result = false;
                }
            }else{
                // check last 2 month deposit met condition or not
                $cnt = $nMonth;
                for($i=1; $i<=$nMonth; $i++){
                    $periodStart = date('Y-m-01', strtotime("-{$i} {$cliam_period}")) . $first_time;
                    $periodEnd = date('Y-m-t', strtotime("-{$i} {$cliam_period}")) . $last_time;

                    $periodTotalDeposit = $this->callHelper('sum_deposit_amount', [$periodStart, $periodEnd, 0]);
                    $this->appendToDebugLog('check last '.$cnt.' month deposit condition', [
                        'from' => $periodStart, 'to' => $periodEnd, 'total deposit' => $periodTotalDeposit
                    ]);

                    if($periodTotalDeposit < $bimonthly_total_deposit) {
                        $result = false;
                        break;
                    }
                }
            }
        }

        $this->appendToDebugLog('date period', ['check deposit' => $type . ' ' . $nMonth . ' ' . $cliam_period, 'result' => $result]);

        return $result;
	}

    public function checkMonthAccumulateDeposit($description, $cliam_period, $bimonthly_total_deposit, $lastPlayerPromo){
        $monthSinceLastApplyPromo = 0;

        //最後一個獎金要連續的Month(Day), 如果有設定lastBonusAccumulateDay則代表最後一個獎金要連續的Month(Day)
        //$lastBonusAccumulateMonth = !empty($description['lastBonusAccumulateDay']) ? $description['lastBonusAccumulateDay'] : 6;
        $lastBonusAccumulateMonth = !empty($description['lastBonusAccumulateDay']) ? $description['lastBonusAccumulateDay'] : 3;
        $lastDateApply = $lastPlayerPromo['dateApply'];
        $result = ['success' => false, 'monthSinceLastApplyPromo' => $monthSinceLastApplyPromo, 'metDepositCondition' => false];

        if($cliam_period == 'day'){
            // need test
            $lastApplyMonth = date('Y-m-d', strtotime($lastDateApply));
            $currentMonth = date('Y-m-d');
        }else{
            $lastApplyMonth = date('Y-m', strtotime($lastDateApply));
            $currentMonth = date('Y-m');
        }

        $diff_days = $this->utils->dateDiff($lastApplyMonth, $currentMonth);
        if(!empty($diff_days)){
            $diff_num = explode(' ', $diff_days);
            $result['monthSinceLastApplyPromo'] = $monthSinceLastApplyPromo = $diff_num[0];
        }

        $this->appendToDebugLog("{$cliam_period} since last apply promo", ['diff days' => $diff_days, "{$cliam_period} since last apply promo" => $monthSinceLastApplyPromo]);

        //預設間隔超過3個月, 視為中斷
        if($monthSinceLastApplyPromo > $lastBonusAccumulateMonth){
            return $result;
        }

        $first_time = ' '.Utils::FIRST_TIME;
        $last_time = ' '.Utils::LAST_TIME;
        $finialMonth = $lastBonusAccumulateMonth - 1;

        for ($i=0; $i<$monthSinceLastApplyPromo; $i++){
            if($monthSinceLastApplyPromo >= $i){
                if($cliam_period == 'day'){
                    if(empty($i)){
                        $periodStart = date('Y-m-d', strtotime($lastDateApply)) . $first_time;
                        $periodEnd = date('Y-m-d', strtotime($lastDateApply)) . $last_time;
                    }else{
                        $periodStart = date('Y-m-d', strtotime("+{$i} {$cliam_period}", strtotime($lastDateApply))) . $first_time;
                        $periodEnd = date('Y-m-d', strtotime("+{$i} {$cliam_period}", strtotime($lastDateApply))) . $last_time;
                    }
                }else{
                    if(empty($i)){
                        $periodStart = date('Y-m-01', strtotime($lastDateApply)) . $first_time;
                        $periodEnd = date('Y-m-t', strtotime($lastDateApply)) . $last_time;
                    }else{
                        $periodStart = date('Y-m-01', strtotime("+{$i} {$cliam_period}", strtotime($lastDateApply))) . $first_time;
                        $periodEnd = date('Y-m-t', strtotime("+{$i} {$cliam_period}", strtotime($lastDateApply))) . $last_time;
                    }
                }

                $monthTotalDeposit = $this->callHelper('sum_deposit_amount', [$periodStart, $periodEnd, 0]);
                $this->appendToDebugLog('check next ' . $lastBonusAccumulateMonth . ' months, the ' . ($i+1) . ' month\'s deposit condition', [
                    'from' => $periodStart, 'to' => $periodEnd, 'total deposit' => $monthTotalDeposit
                ]);

                if ($monthTotalDeposit < $bimonthly_total_deposit) {
                    $result['metDepositCondition'] = false;
                    break;
                }

                $result['metDepositCondition'] = true;

                // if satisfied all bonus condition in acculmulate condition
                if($i == $finialMonth){
                    $result['success'] = true;
                }

            }
        }

        return $result;
	}

    public function checkLast2MonthDepositMetCondition($description, $cliam_period, $bimonthly_total_deposit){
        return $this->checkMonthDeposit($description, $cliam_period, $bimonthly_total_deposit, self::CHECK_LAST_N_MONTH_DEPOSIT_TYPE);
    }

    public function checkNext2MonthDepositMetCondition($description, $cliam_period, $bimonthly_total_deposit, $last_player_promo){
	    return $this->checkMonthDeposit($description, $cliam_period, $bimonthly_total_deposit, self::CHECK_NEXT_N_MONTH_DEPOSIT_TYPE, $last_player_promo);
    }

    public function checkNext6MonthDepositMetCondition($description, $cliam_period, $bimonthly_total_deposit, $lastPlayerPromo){
        return $this->checkMonthAccumulateDeposit($description, $cliam_period, $bimonthly_total_deposit, $lastPlayerPromo);
    }

    public function checkNext3MonthDepositMetCondition($description, $cliam_period, $bimonthly_total_deposit, $lastPlayerPromo){
	    return $this->checkMonthAccumulateDeposit($description, $cliam_period, $bimonthly_total_deposit, $lastPlayerPromo);
    }

    public function getCurrentConsecutiveTimes($cliam_period, $bonus_settings, $allPlayerPromo){
        $current_consecutive_times = 0;

	    if( ($cliam_period == 'day') && (count($bonus_settings) == 2) ){
            //only for test promo with 2 bonus settings
            $firstBonusAmount   = !empty($bonus_settings[0]['bonus_amount']) ? $bonus_settings[0]['bonus_amount'] : -1;
            $secondBonusAmount  = !empty($bonus_settings[1]['bonus_amount']) ? $bonus_settings[1]['bonus_amount'] : -1;

            $totalRecord = count($allPlayerPromo);
            $lastPlayerPromo = $allPlayerPromo[$totalRecord - 1];
            $lastPromoBonusAmount = $lastPlayerPromo['bonusAmount'];
            $current_consecutive_times = 0;

            $this->appendToDebugLog('last player promo bonus amount', ['result' => $lastPromoBonusAmount]);

            if($lastPromoBonusAmount == $secondBonusAmount){
                $current_consecutive_times = 2;
            }else if($lastPromoBonusAmount == $firstBonusAmount){
                $current_consecutive_times = 1;
            }
        }elseif(count($bonus_settings) == 7) {
            //for live env
            $firstBonusAmount   = !empty($bonus_settings[0]['bonus_amount']) ? $bonus_settings[0]['bonus_amount'] : -1;
            $secondBonusAmount  = !empty($bonus_settings[1]['bonus_amount']) ? $bonus_settings[1]['bonus_amount'] : -1;
            $thirdBonusAmount   = !empty($bonus_settings[2]['bonus_amount']) ? $bonus_settings[2]['bonus_amount'] : -1;
            $fourthBonusAmount  = !empty($bonus_settings[3]['bonus_amount']) ? $bonus_settings[3]['bonus_amount'] : -1;
            $fifthBonusAmount   = !empty($bonus_settings[4]['bonus_amount']) ? $bonus_settings[4]['bonus_amount'] : -1;
            $sixthBonusAmount   = !empty($bonus_settings[5]['bonus_amount']) ? $bonus_settings[5]['bonus_amount'] : -1;
            $seventhBonusAmount = !empty($bonus_settings[6]['bonus_amount']) ? $bonus_settings[6]['bonus_amount'] : -1;

            $totalRecord = count($allPlayerPromo);
            $lastPlayerPromo = $allPlayerPromo[$totalRecord - 1];
            $lastPromoBonusAmount = $lastPlayerPromo['bonusAmount'];
            $secondLastPromoBonusAmount = 0;
            $thirdLastPromoBonusAmount = 0;
            $current_consecutive_times = 0;

            $this->appendToDebugLog('last player promo bonus amount', ['result' => $lastPromoBonusAmount]);

            if(!empty($allPlayerPromo[$totalRecord - 2])){
                $secondLastPlayerPromo = $allPlayerPromo[$totalRecord - 2];
                $secondLastPromoBonusAmount = $secondLastPlayerPromo['bonusAmount'];
                $this->appendToDebugLog('second last promo bonus amount', ['result' => $secondLastPromoBonusAmount]);
            }

            if(!empty($allPlayerPromo[$totalRecord - 3])){
                $thirdLastPlayerPromo = $allPlayerPromo[$totalRecord - 3];
                $thirdLastPromoBonusAmount = $thirdLastPlayerPromo['bonusAmount'];
                $this->appendToDebugLog('third last promo bonus amount', ['result' => $thirdLastPromoBonusAmount]);
            }

            if( ($lastPromoBonusAmount == $seventhBonusAmount) && ($secondLastPromoBonusAmount == $sixthBonusAmount) && ($thirdLastPromoBonusAmount == $fifthBonusAmount) ){
                $current_consecutive_times = 7;
            }else if( ($lastPromoBonusAmount == $sixthBonusAmount) && ($secondLastPromoBonusAmount == $fifthBonusAmount) ) {
                $current_consecutive_times = 6;
            }else if($lastPromoBonusAmount == $fifthBonusAmount){
                $current_consecutive_times = 5;
            }else if($lastPromoBonusAmount == $fourthBonusAmount){
                $current_consecutive_times = 4;
            }else if($lastPromoBonusAmount == $thirdBonusAmount){
                $current_consecutive_times = 3;
            }else if($lastPromoBonusAmount == $secondBonusAmount){
                $current_consecutive_times = 2;
            }else if($lastPromoBonusAmount == $firstBonusAmount){
                $current_consecutive_times = 1;
            }
        }

        $this->appendToDebugLog('get current consecutive times', ['total bonus setting' => count($bonus_settings), 'current consecutive times' => $current_consecutive_times]);

        return $current_consecutive_times;
	}

    public function checkPlayerkDepositCondition($description, $cliam_period, $bimonthly_total_deposit, $lastPlayerPromo, $current_consecutive_times, &$errorMessageLang){
        $success = false;
        $met_consecutive_times = 0;

        $bonus_settings = $description['bonus_settings'];
        $lastMonthAllowApplyDate = date('Y-m-'.$description['allowed_day'], strtotime("-1 {$cliam_period}"));
        $lastPlayerPromoDate = date('Y-m-d', strtotime($lastPlayerPromo['dateApply']));
        $this->appendToDebugLog('checkPlayerkDepositCondition last promo date apply', ['last date apply' => $lastPlayerPromoDate]);

        $checkLastBonusDepositConfition = false; // last bonus need to accumulate for specify month, no need to check first month after last apply promo
        $metLastConsecutiveTimes = count($bonus_settings)-1;

        if($metLastConsecutiveTimes == $current_consecutive_times){
            $checkLastBonusDepositConfition = true;
        }

        if(!$checkLastBonusDepositConfition){
            if($lastPlayerPromoDate == $lastMonthAllowApplyDate){
                $errorMessageLang = 'promo.dont_allow_not_within_claim_time'; //距離上次申請只有一個月, 未達申請資格
                $this->appendToDebugLog('checkPlayerkDepositCondition not valid apply date', ['last player promo date' => $lastPlayerPromoDate, 'last allow apply date' => $lastMonthAllowApplyDate, 'success' => $success, 'errorMessageLang' => $errorMessageLang]);
                return [$success, $met_consecutive_times];
            }
        }

        if( ($cliam_period == 'day') && (count($bonus_settings) == 2) ){
            // for test env with 2 settings
            switch($current_consecutive_times){
                case 1:
                    $result = $this->checkNext3MonthDepositMetCondition($description, $cliam_period, $bimonthly_total_deposit, $lastPlayerPromo);
                    $this->appendToDebugLog('checkNext3MonthDepositMetCondition result', ['result' => $result]);

                    if($result['success'] && $result['metDepositCondition']){
                        $success = true;
                        $met_consecutive_times = $current_consecutive_times + 1;
                    }elseif($result['metDepositCondition']){
                        $errorMessageLang = 'promo_custom.deposit_donot_match_the_requirement';
                    }else{
                        $this->appendToDebugLog('check next 2 month deposit condition failed, need to check last 1 day dpeosit condition');
                        $last2MonthDepositMetCondition = $this->checkLast2MonthDepositMetCondition($description, $cliam_period, $bimonthly_total_deposit);
                        if($last2MonthDepositMetCondition){
                            $success = true;
                            $met_consecutive_times = 1;
                        }else{
                            $errorMessageLang = 'promo_custom.deposit_donot_match_the_requirement';
                        }
                    }
                    break;
                case 2:
                    $this->appendToDebugLog('player had fully applied 2 times of this promo, can not apply forever');
                    $errorMessageLang = 'notify.83';
                    break;
                default:
                    $errorMessageLang = 'promo_rule.common.error';
                    break;
            }
        }else{
            // for live env
            switch ($current_consecutive_times){
                case 1: // 已申請過 連續2個月的優惠獎金, 已申請1次
                case 2: // 已申請過 連續2/4個月的優惠獎金, 已連續申請2次
                case 3: // 已申請過 連續2/4/6個月的優惠獎金, 已連續申請3次
                case 4: // 已申請過 連續2/4/6/8個月的優惠獎金, 已連續申請4次
                case 5: // 已申請過 連續2/4/6/8/10個月的優惠獎金, 已連續申請5次

                    // 檢查上次領獎金的2個月存款最低資格, 否則視為中斷, 重頭來過
                    $next2MonthDepositMetCondition = $this->checkNext2MonthDepositMetCondition($description, $cliam_period, $bimonthly_total_deposit, $lastPlayerPromo);
                    if($next2MonthDepositMetCondition){
                        $success = true;
                        $met_consecutive_times = $current_consecutive_times + 1;
                    }else{
                        $this->appendToDebugLog('check Next 2 Month Deposit', ['Met Condition' => $next2MonthDepositMetCondition, 'met consecutive times' => $met_consecutive_times, 'need to check last 2 month deposit condition', true]);
                        // 檢查申請日的月存款與申請日上月存款
                        $last2MonthDepositMetCondition = $this->checkLast2MonthDepositMetCondition($description, $cliam_period, $bimonthly_total_deposit);
                        if($last2MonthDepositMetCondition){
                            $success = true;
                            $met_consecutive_times = 1;
                        }else{
                            $errorMessageLang = 'promo_custom.deposit_donot_match_the_requirement';
                        }
                    }
                    break;
                case 6: // 已申請過 連續2/4/6/8/10/12個月的優惠獎金, 已連續申請6次

                    // 檢查上次領獎金後的6個月存款是否滿足, 否則重頭來過
                    // $next6MonthDepositMetCondition = $this->checkNext6MonthDepositMetCondition($description, $cliam_period, $bimonthly_total_deposit, $lastPlayerPromo);
                    // $this->appendToDebugLog('checkNext6MonthDepositMetCondition result', ['result' => $next6MonthDepositMetCondition]);

                    // 檢查上次領獎金後的3個月存款是否滿足, 否則重頭來過
                    $result = $this->checkNext3MonthDepositMetCondition($description, $cliam_period, $bimonthly_total_deposit, $lastPlayerPromo);
                    $this->appendToDebugLog('checkNext3MonthDepositMetCondition result', ['result' => $result]);

                    if ($result['success'] && $result['metDepositCondition']) {
                        $success = true;
                        $met_consecutive_times = $current_consecutive_times + 1;
                    } elseif ($result['metDepositCondition']){
                        $errorMessageLang = 'promo_custom.deposit_donot_match_the_requirement';
                    } else {
                        $this->appendToDebugLog('check next 3 month deposit condition failed, need to check last 2 month dpeosit condition');
                        $last2MonthDepositMetCondition = $this->checkLast2MonthDepositMetCondition($description, $cliam_period, $bimonthly_total_deposit);
                        if($last2MonthDepositMetCondition){
                            $success = true;
                            $met_consecutive_times = 1;
                        }else{
                            $errorMessageLang = 'promo_custom.deposit_donot_match_the_requirement';
                        }
                    }

                    break;
                case 7:  // 已申請過 連續2/4/6/8/10/12/15個月的優惠獎金, 已連續申請7次
                    $this->appendToDebugLog('player had fully applied 7 times of this promo, can not apply forever');
                    $errorMessageLang = 'notify.83';
                    break;
                default:
                    $errorMessageLang = 'promo_rule.common.error';
                    break;
            }
        }


        $this->appendToDebugLog('checkPlayerDepositCondition result', [
            'success' => $success,
            'errorMessageLang' => $errorMessageLang,
            'current_consecutive_times' => $current_consecutive_times,
            'met_consecutive_times' => $met_consecutive_times
        ]);

        return [$success, $met_consecutive_times];
    }

    public function getConsecutiveBonusAmount($bonus_settings, $met_consecutive_times = 1){
        $bonusAmount = 0;
        if(!empty($bonus_settings[$met_consecutive_times-1])){
            $bonusAmount = $bonus_settings[$met_consecutive_times-1]['bonus_amount'];
        }
        return $bonusAmount;
    }

	/**
	 * run bonus condition checker
	 * @param  array $description original description in rule
	 * @param  array $extra_info
	 * @param  boolean $dry_run
	 * @return  array ['success'=> success, 'message'=> errorMessageLang, 'continue_process_after_script' => FALSE]
	 */
	protected function runBonusConditionChecker($description, &$extra_info, $dry_run){
		$errorMessageLang=null;

        $result = $this->checkCustomizeBonusCondition($description, $errorMessageLang);

        if (array_key_exists('bonus_amount', $result)) {
            unset($result['bonus_amount']);
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
        $errorMessageLang=null;
        $result = $this->checkCustomizeBonusCondition($description, $errorMessageLang);

        return $result;
    }

    private function checkCustomizeBonusCondition($description, &$errorMessageLang){
        $success = false;
        $bonus_amount = 0;
        $bimonthly_total_deposit = $description['bimonthly_total_deposit'];
        $bonus_settings = $description['bonus_settings'];

        $today = $this->callHelper('get_date_type', [self::DATE_TYPE_TODAY]);
        $d = new DateTime($today);
        $currentDate = $d->format('Y-m-d');

        $allowed_day = $description['allowed_day'];
        $allowDate = $d->format('Y-m-') . $allowed_day;
        $this->appendToDebugLog('check allowed_day', ['allowed_day' => $allowed_day]);

        $cliam_period = 'month';
        if(!empty($description['cliam_period']) && ($description['cliam_period'] == 'day')){
            $cliam_period = 'day';
        }

        //first apply date not allow, only allow apply at least 2 months after promo start
        $appPeriodStart = $this->promorule['applicationPeriodStart'];
        $appStart = new DateTime($appPeriodStart);
        $appStartDate = $appStart->format('Y-m-d');
        $firstMonthSincePromoStart = $appStart->modify('+1 month');
        $firstMonthSincePromoStartDate = $firstMonthSincePromoStart->format('Y-m-d');

//        if(!empty($description['firstMonthSincePromoStartDate'])){
//            $firstMonthSincePromoStart = new DateTime($description['firstMonthSincePromoStartDate']);
//            $firstMonthSincePromoStartDate = $firstMonthSincePromoStart->format('Y-m-d');
//        }

        $this->appendToDebugLog('validate date', [
            'today' => $currentDate,
            'is today allow apply date' => ($currentDate == $allowDate),
            'promorule start on' => $appStartDate,
            'player not allow apply before first month' => $firstMonthSincePromoStartDate,
            'date less or equal one month after start' => ($currentDate <= $firstMonthSincePromoStartDate)
        ]);

        if($currentDate == $allowDate){
            if($currentDate <= $firstMonthSincePromoStartDate){
                $errorMessageLang = "First Month Can't Apply Promo";
            }else{
                $allPlayerPromo = $this->callHelper('get_all_released_player_promo', [$this->promorulesId, null]);
                $this->appendToDebugLog('start check player deposit', ['player first apply promo' => empty($allPlayerPromo)]);

                if(!empty($allPlayerPromo)){
                    $allPlayerPromo = array_reverse($allPlayerPromo);
                    $this->appendToDebugLog('start check player deposit', ['player promo history' => $allPlayerPromo]);

                    $currentConsecutiveTimes = $this->getCurrentConsecutiveTimes($cliam_period, $bonus_settings, $allPlayerPromo);

                    if(!empty($currentConsecutiveTimes) ){
                        $lastPlayerPromo = end($allPlayerPromo);
                        list($success, $met_consecutive_times) = $this->checkPlayerkDepositCondition($description, $cliam_period, $bimonthly_total_deposit, $lastPlayerPromo, $currentConsecutiveTimes, $errorMessageLang);

                        if($success){
                            $bonus_amount = $this->getConsecutiveBonusAmount($bonus_settings, $met_consecutive_times);
                        }
                    }else{
                        $errorMessageLang = 'consecutive time not valid';
                    }
                }else{
                    $currentAndLastMonthDepositMetCondition = $this->checkLast2MonthDepositMetCondition($description, $cliam_period, $bimonthly_total_deposit);
                    if($currentAndLastMonthDepositMetCondition){
                        $bonus_amount = $this->getConsecutiveBonusAmount($bonus_settings);
                        if(!empty($bonus_amount)){
                            $success = true;
                        }
                    }else{
                        $errorMessageLang = 'promo_custom.deposit_donot_match_the_requirement';
                    }
                }
            }
        }else{
            $errorMessageLang = 'Not right date';
        }

        return ['success' => $success, 'message' => $errorMessageLang, 'bonus_amount' => $bonus_amount];

    }
}