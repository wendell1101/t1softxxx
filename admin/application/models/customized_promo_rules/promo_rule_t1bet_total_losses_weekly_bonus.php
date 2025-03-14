<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

require_once dirname(__FILE__) .'/abstract_promo_rule.php';

/**
 * WEEKLY CASHBACK LIVE CASINO
 * OGP-29528
 *
 * 投注計算: ㄧ ~ 日
 * 每週一排程自動發放(待確認)
 *
 * 1倍取款條件

 * Bonus condition && Bonus release:
{
    "class": "promo_rule_t1bet_total_losses_weekly_bonus",
    "min_bonus_amount": 0,
    "bonus_settings": {
        "bonus_percentage" : {
            "3": {"min_loss" : 100, "max_loss" : 3000 },
            "4": {"min_loss" : 3001, "max_loss" : 10000 },
            "5": {"min_loss" : 10001, "max_loss" : 50000 },
            "6": {"min_loss" : 50001}
        }
    }
}
$config['auto_apply_and_release_bonus_for_t1bet_total_losses_weekly_promocms_id'] = [
    // promo_cms_id => game_type_id
    16897 => [19,31,36,53,56,196,317,320,439,590,653], //sports
];

 *
 */
class Promo_rule_t1bet_total_losses_weekly_bonus extends Abstract_promo_rule{

	public function init($playerId, $promorule, $playerBonusAmount = null, $depositAmount=null){
		parent::init($playerId, $promorule, $playerBonusAmount, $depositAmount);
	}

	public function getClassName(){
		return 'Promo_rule_t1bet_total_losses_weekly_bonus';
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
        $success = false;
        $bonus_amount = 0;
        $bonus_percentage = 0;
        $gameType = null;
        $bonus_settings = $description['bonus_settings'];
        $min_bonus_amount = $description['min_bonus_amount'];
        $promo_cms_id = $description['promo_cms_id'];
        $promo_settings = $this->utils->getConfig('auto_apply_and_release_bonus_for_t1bet_total_losses_weekly_promocms_id');

        if(!empty($promo_settings[$promo_cms_id])){
            $gameType = $promo_settings[$promo_cms_id];
        }

        $_extra_info = [];
        $_extra_info['week_start'] = 'monday';
        $fromDate = !empty($description['start']) ? $description['start'] : $this->get_date_type(self::DATE_LAST_WEEK_START, $_extra_info);
        $toDate = !empty($description['end']) ? $description['end'] : $this->get_date_type(self::DATE_LAST_WEEK_END, $_extra_info);

        $today = $this->utils->getTodayForMysql();
        $this->appendToDebugLog('runBonusConditionChecker check date', ['fromDate'=>$fromDate, 'toDate'=>$toDate, 'today'=>$today]);

        if(empty($gameType)){
            $errorMessageLang = 'missing allow game type';
            return ['success' => $success, 'message' => $errorMessageLang];
        }

        #get game log win / loss
        $playerTotalBetWinLoss = $this->callHelper('getPlayerTotalBetWinLoss',
            [$fromDate, $toDate, 'total_player_game_day', 'date', null, $gameType]
        );

        $this->appendToDebugLog('getPlayerTotalBetWinLoss: ', $playerTotalBetWinLoss);
        $totalBet = $playerTotalBetWinLoss['total_bet'];
        $totalWin = $playerTotalBetWinLoss['total_win'];
        $totalLoss = $playerTotalBetWinLoss['total_loss'];

        if(empty($totalBet)){
            $errorMessageLang = 'promo_rule.common.error';
            $this->appendToDebugLog('Player Do Not Have Bet in Game Type');
            return ['success' => $success, 'message' => $errorMessageLang];
        }

        $gameRevenue = ($totalLoss - $totalWin);

        #get total bonus
        $totalBonus = $this->callHelper('totalReleasedBonusByPlayerAndDateTime', [$this->playerId, $fromDate, $toDate]);

        if($this->process_mock('gameRevenue', $gameRevenue)){
            $this->appendToDebugLog('use mock today', ['gameRevenue' => $gameRevenue]);
        }

        if($this->process_mock('totalBonus', $totalBonus)){
            $this->appendToDebugLog('use mock today', ['totalBonus' => $totalBonus]);
        }

        if($gameRevenue <= 0){
            $errorMessageLang = 'total losses is less than or equal to zero: [' . $gameRevenue . ']';
            return ['success' => $success, 'message' => $errorMessageLang, 'bonus_amount' => $bonus_amount];
        }

        #start check bonus conditions
        foreach ($bonus_settings['bonus_percentage'] as $percentage => $amountScope) {
            if(isset($amountScope['min_loss']) && isset($amountScope['max_loss'])){
                if($gameRevenue >= $amountScope['min_loss'] && $gameRevenue <= $amountScope['max_loss']){
                    $bonus_amount = ($gameRevenue * ($percentage / 100)) - $totalBonus;
                    $bonus_percentage = $percentage;
                }
            }
            #Upper Bound
            if(isset($amountScope['min_loss']) && !isset($amountScope['max_loss'])){
                if($gameRevenue >= $amountScope['min_loss']){
                    $bonus_amount = ($gameRevenue * ($percentage / 100)) - $totalBonus;
                    $bonus_percentage = $percentage;
                }
            }
        }

        $this->appendToDebugLog('calculate bonus amount: ', [
            'bonus_amount' => $bonus_amount, 'bonus_percentage' => $bonus_percentage
        ]);

        if($bonus_amount <= $min_bonus_amount){
            $errorMessageLang = 'bonus amount is less than min bonus amount or equal to zero: ['. $bonus_amount . ']';
            return ['success' => $success, 'message' => $errorMessageLang, 'bonus_amount' => $bonus_amount];
        }

        $success = true;

        return ['success' => $success, 'message' => $errorMessageLang, 'bonus_amount' => $bonus_amount];
    }
}
