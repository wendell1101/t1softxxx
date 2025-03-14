<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

require_once dirname(__FILE__) .'/abstract_promo_rule.php';

/**
 * CASHBACK: SPORTS WEEKLY CASHBACK
 * OGP-29529
 *
 * 投注計算: ㄧ ~ 日
 * 每週一 23:00 (GMT+8) 排程自動發放
 *
 * 1倍取款條件

 * Bonus condition && Bonus release:
{
    "class": "promo_rule_t1bet_sports_total_losses_weekly_bonus",
    "min_bonus_amount": 0,
    "promo_cms_id": ,
    "bonus_settings": {
        "bonus_percentage" : {
            "2": {"min_loss" : 500, "max_loss" : 10000 },
            "3": {"min_loss" : 10001, "max_loss" : 50000 },
            "4": {"min_loss" : 50001, "max_loss" : 100000 },
            "5": {"min_loss" : 100000}
        }
    }
}

$config['auto_apply_and_release_bonus_for_t1bet_sports_total_losses_weekly_promocms_id'] = [
    // promo_cms_id => game_type_id
    16897 => [1167,1243], //sports
];

 *
 */
class Promo_rule_t1bet_sports_total_losses_weekly_bonus extends Abstract_promo_rule{

	public function init($playerId, $promorule, $playerBonusAmount = null, $depositAmount=null){
		parent::init($playerId, $promorule, $playerBonusAmount, $depositAmount);
	}

	public function getClassName(){
		return 'Promo_rule_t1bet_sports_total_losses_weekly_bonus';
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
        $promo_settings = $this->utils->getConfig('auto_apply_and_release_bonus_for_t1bet_sports_total_losses_weekly_promocms_id');

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
        $totalLoss = $playerTotalBetWinLoss['total_loss'];
        $totalWin = $playerTotalBetWinLoss['total_win'];

        #get total bonus
        $totalBonus = $this->callHelper('totalReleasedBonusByPlayerAndDateTime', [$this->playerId, $fromDate, $toDate]);
        $this->appendToDebugLog('PlayerTotalBonus: ', $totalBonus);

        if($this->process_mock('totalLoss', $totalLoss)){
            $this->appendToDebugLog('use mock today', ['totalLoss' => $totalLoss]);
        }

        if($this->process_mock('totalWin', $totalWin)){
            $this->appendToDebugLog('use mock today', ['totalWin' => $totalWin]);
        }

        if($this->process_mock('totalBonus', $totalBonus)){
            $this->appendToDebugLog('use mock today', ['totalBonus' => $totalBonus]);
        }

        #get total revenue
        $totalRevenue = $totalLoss - $totalWin;
        $this->appendToDebugLog('total revenue', [
            "formula" => "{$totalLoss}[total loss] - {$totalWin}[total win]",
            "result"  => $totalRevenue
        ]);

        if($totalRevenue <= 0){
            $errorMessageLang = 'total revenue  is less than or equal to zero: [' . $totalRevenue . ']';
            return ['success' => $success, 'message' => $errorMessageLang, 'bonus_amount' => $bonus_amount];
        }

        #start check bonus conditions
        foreach ($bonus_settings['bonus_percentage'] as $percentage => $amountScope) {
            if(isset($amountScope['min_loss']) && isset($amountScope['max_loss'])){
                if($totalRevenue >= $amountScope['min_loss'] && $totalRevenue <= $amountScope['max_loss']){
                    $bonus_amount = ($totalRevenue * ($percentage / 100)) - $totalBonus;
                    $bonus_percentage = $percentage;
                }
            }
            #Upper Bound
            if(isset($amountScope['min_loss']) && !isset($amountScope['max_loss'])){
                if($totalRevenue >= $amountScope['min_loss']){
                    $bonus_amount = ($totalRevenue * ($percentage / 100)) - $totalBonus;
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
