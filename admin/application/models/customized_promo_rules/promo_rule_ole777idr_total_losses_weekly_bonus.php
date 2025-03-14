<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

require_once dirname(__FILE__) .'/abstract_promo_rule.php';

/**
 * WEEKLY CASHBACK SPORTSBOOK
 * OGP-25981
 *
 * 投注計算: ㄧ ~ 日
 * 每週一排程自動發放(14:00)
 * 不与返水共享流水
 * 獎金<30 不發放
 * 3倍取款條件
 *
 * Example:
 * Member total loss =  IDR 2,000
 * Total Win = IDR 500
 * Total Bonus = IDR 50
 * Total rebate = IDR 50

 * (Total Loss - Total Win) - (Total Bonus within a week + rebate within a week) = Total Losses
 * Total Losses = (2000 - 500) - (50 + 50) = IDR 1,400
 * Cashback of Losses (new member 3%) = IDR 1400 x 3% = IDR 42

 * 獎金計算
 * - total bonus whithin a week => 只看優惠產生的獎金
 * - Total Loss / Total Win => game type 的 total loss / total win
 * - rebate within a week => 週返水總計(不區分game type)
 * - 獎金取到小數點後兩位

 * Bonus condition && Bonus release:

{
    "class": "promo_rule_ole777idr_total_losses_weekly_bonus",
    "min_bonus_amount": 30,
    "bonus_settings": {
        "VIP0" : {"bonus_percentage":     3, "max_bonus_amount":  300},
        "VIP1" : {"bonus_percentage":     4, "max_bonus_amount":  500},
        "VIP2" : {"bonus_percentage":     4, "max_bonus_amount":  500},
        "VIP3" : {"bonus_percentage":     4, "max_bonus_amount":  500},
        "VIP4" : {"bonus_percentage":     5, "max_bonus_amount":  800},
        "VIP5" : {"bonus_percentage":     5, "max_bonus_amount":  800},
        "VIP6" : {"bonus_percentage":   6.5, "max_bonus_amount": 1000},
        "VIP7" : {"bonus_percentage":  7.77, "max_bonus_amount": 2500},
        "VIP8" : {"bonus_percentage":  8.77, "max_bonus_amount": 3777},
        "VIP9" : {"bonus_percentage": 10.77, "max_bonus_amount": 5777}
    }
}

$config['auto_apply_and_release_bonus_for_ole777idr_total_losses_weekly_promocms_id'] = [
    // promo_cms_id => game_tag_id
    16897 => 15, //sports
    16898 => 1, //slot
    16899 => 3, //fishing
];

 *
 */
class Promo_rule_ole777idr_total_losses_weekly_bonus extends Abstract_promo_rule{

	public function init($playerId, $promorule, $playerBonusAmount = null, $depositAmount=null){
		parent::init($playerId, $promorule, $playerBonusAmount, $depositAmount);
	}

	public function getClassName(){
		return 'Promo_rule_ole777idr_total_losses_weekly_bonus';
	}

    /**
     * run bonus condition checker
     * @param  array $description original description in rule
     * @param  array $extra_info
     * @param  boolean $dry_run
     * @return  array ['success'=> success, 'message'=> errorMessageLang]
     */
    protected function runBonusConditionChecker($description, &$extra_info, $dry_run){
        $errorMessageLang = null;
        $result = $this->checkCustomizeBonusCondition($description, $errorMessageLang);

        if(array_key_exists('bonus_amount',$result)){
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
        $errorMessageLang = null;
        $result = $this->checkCustomizeBonusCondition($description, $errorMessageLang);

        return $result;
    }

    private function checkCustomizeBonusCondition($description, &$errorMessageLang){
        $this->load->model('game_type_model');
        $this->load->library(['og_utility']);

        $success = false;
        $bonus_amount = 0;

        $bonus_settings = $description['bonus_settings'];

        $gameType = null;
        $promo_cms_id = $description['promo_cms_id'];
        $promo_settings = $this->utils->getConfig('auto_apply_and_release_bonus_for_ole777idr_total_losses_weekly_promocms_id');
        if(!empty($promo_settings[$promo_cms_id])){
            $game_tag_id = $promo_settings[$promo_cms_id];
            if(empty($game_tag_id)){
                $errorMessageLang = 'missing allow game tag';
                return ['success' => $success, 'message' => $errorMessageLang];
            }

            $game_type_raw_list = $this->game_type_model->getAllGameTypeListWithTag($game_tag_id);
            if(!empty($game_type_raw_list)){
                $gameType = $this->og_utility->array_pluck($game_type_raw_list, 'id');
            }
            $this->appendToDebugLog('game type', ['list'=>$gameType]);
        }

        $_extra_info = [];
        $_extra_info['week_start'] = 'monday';
        $fromDate = !empty($description['start']) ? $description['start'] : $this->get_date_type(self::DATE_LAST_WEEK_START, $_extra_info);
        $toDate = !empty($description['end']) ? $description['end'] : $this->get_date_type(self::DATE_LAST_WEEK_END, $_extra_info);

        $today = $this->utils->getTodayForMysql();
        $this->appendToDebugLog('runBonusConditionChecker check date', ['fromDate'=>$fromDate, 'toDate'=>$toDate, 'today'=>$today]);

        if(!array_key_exists($this->levelId, $bonus_settings)){
            $errorMessageLang = 'Not right group level';
            return ['success' => $success, 'message' => $errorMessageLang];
        }

        if(empty($gameType)){
            $errorMessageLang = 'missing allow game type';
            return ['success' => $success, 'message' => $errorMessageLang];
        }

        #get game log win / loss
        $playerTotalBetWinLoss = $this->callHelper('getPlayerTotalBetWinLoss',
            [$fromDate, $toDate, 'total_player_game_day', 'date', null, $gameType]
        );

        $this->appendToDebugLog('getPlayerTotalBetWinLoss: ', $playerTotalBetWinLoss);
        $totalWin = $playerTotalBetWinLoss['total_win'];
        $totalLoss = $playerTotalBetWinLoss['total_loss'];

        #get total bonus
        $totalBonus=$this->callHelper('totalReleasedBonusByPlayerAndDateTime', [$this->playerId, $fromDate, $toDate]);

        #get total cashback
        $rebate = $this->callHelper('sum_cashback_amount', [$fromDate, $toDate]);

        // (Total Loss - Total Win) - (Total Bonus within a week + rebate within a week) = Total Losses
        $totalLosses = ($totalLoss - $totalWin) - ($totalBonus + $rebate);
        $this->appendToDebugLog('(totalLoss - totalWin) - (totalBonus + rebate)', [
            'totalLoss' => $totalLoss, 'totalWin' => $totalWin,
            'totalBonus' => $totalBonus, 'rebate' => $rebate,
            'result' => $totalLosses
        ]);

        if($totalLosses <= 0){
            $totalLosses = 0;
            $errorMessageLang = 'total losses is less than or equal to zero: [' . $totalLosses . ']';
            return ['success' => $success, 'message' => $errorMessageLang, 'bonus_amount' => $bonus_amount];
        }

        // Bonus Amount = Losses Total Losses x (VIP bonus percentage %) = IDR 1400 x 3% = IDR 42
        $min_bonus_amount = $description['min_bonus_amount'];
        $bonus_percentage = $bonus_settings[$this->levelId]['bonus_percentage'];
        $max_bonus_amount = $bonus_settings[$this->levelId]['max_bonus_amount'];

        $bonus_amount = $totalLosses * ($bonus_percentage / 100);
        $this->appendToDebugLog('calculate bonus amount: ', [
            'bonus_amount' => $bonus_amount, 'bonus_percentage' => $bonus_percentage, 'max_bonus_amount' => $max_bonus_amount
        ]);

        if($bonus_amount < $min_bonus_amount){
            $errorMessageLang = 'bonus amount is less than min bonus amount: ['. $bonus_amount . ']';
            return ['success' => $success, 'message' => $errorMessageLang, 'bonus_amount' => $bonus_amount];
        }

        if($bonus_amount > $max_bonus_amount){
            $bonus_amount = $max_bonus_amount;
        }

        $success = true;

        return ['success' => $success, 'message' => $errorMessageLang, 'bonus_amount' => $bonus_amount];
    }
}
