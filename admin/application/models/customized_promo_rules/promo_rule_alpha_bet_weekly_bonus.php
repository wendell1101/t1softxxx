<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

require_once dirname(__FILE__) .'/abstract_promo_rule.php';

/**
 * Weekly Rebate up to 20%
 * OGP-33467
 * 
 * Slots Weekly Rebate
 * 。Bet on any of the SLOTS or ARCADE games
 * 。Get 1.00% percent of your weekly net loss back in cash.
 * 
 * Example:
 * [Slots and Arcade] Games Weekly Netloss =  100,000 PHP
 * Rebate amount  = 100,000 X 1.00% = 1,000 PHP
 * WC = 1x
 * 
 * -
 * 
 * Live Casino Weekly Rebate
 * 。Bet on any of the LIVE CASINO games
 * 。Get 0.50% percent of your weekly net loss back in cash.
 * 
 * Example:
 * [Live Casino] Weekly Netloss =  100,000 PHP
 * Rebate amount  = 100,000 X 0.50% = 500 PHP
 * WC = 1x
 * 
 * -
 * Sports Weekly Rebate
 * 。Bet on any of the SPORTS
 * 。Get 0.80% percent of your weekly net loss back in cash.
 * [Sports] Netloss =  100,000 PHP
 * Rebate amount  = 100,000 X 0.80% = 800 PHP
 * WC = 1x
 * 
 * 投注計算: ㄧ ~ 日
 * 每週一排程自動發放(15:00)
 * 1倍取款條件
 * Netloss = last week total bet
 * 
 * Bonus condition && Bonus release:

{
    "class": "promo_rule_alpha_bet_weekly_bonus",
    "promo_cms_id": $promo_cms_id,
    "min_bonus": 100, // optional
    "bonus_percentage": 1,
    "start": "Y-m-d H:i:s", // for QA test
    "end": "Y-m-d H:i:s"  // for QA test
}

$config['auto_apply_and_release_bonus_for_alpha_bet_weekly_promocms_id'] = [
    // $promo_cms_id => game_tag_id
    $promo_cms_id => [1,12], //Slots and Arcade
    $promo_cms_id => 4, //Live Casino(Live Dealer)
    $promo_cms_id => 15, //Sports
];

 *
 */
class Promo_rule_alpha_bet_weekly_bonus extends Abstract_promo_rule{

	public function init($playerId, $promorule, $playerBonusAmount = null, $depositAmount=null){
		parent::init($playerId, $promorule, $playerBonusAmount, $depositAmount);
	}

	public function getClassName(){
		return 'Promo_rule_alpha_bet_weekly_bonus';
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

        $gameType = null;
        if(!empty($extra_info['custom_promo_game_type_ids'])){
            $gameType = $extra_info['custom_promo_game_type_ids'];
            $this->appendToDebugLog('game type from cronjob preload', ['list'=>$gameType]);
        }else{
            $promo_cms_id = $description['promo_cms_id'];
            $promo_settings = $this->utils->getConfig('auto_apply_and_release_bonus_for_alpha_bet_weekly_promocms_id');
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
                $this->appendToDebugLog('game type from game tag', ['list'=>$gameType]);
            }
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

        #get game log bet
        $playerTotalBetWinLoss = $this->callHelper('getPlayerTotalBetWinLoss',
            [$fromDate, $toDate, 'total_player_game_day', 'date', null, $gameType]
        );

        $this->appendToDebugLog('getPlayerTotalBetWinLoss: ', $playerTotalBetWinLoss);
        $total_bet = $playerTotalBetWinLoss['total_bet'];

        if(empty($total_bet)){
            $errorMessageLang = 'promo_rule.common.error';
            return ['success' => $success, 'message' => $errorMessageLang];
        }

        $bonusPercentage = $description['bonus_percentage'];
        $bonus_amount = $total_bet * ($bonusPercentage / 100);

        $min_bonus = !empty($description['min_bonus']) ? $description['min_bonus'] : 0;
        if(!empty($min_bonus) && ($bonus_amount < $min_bonus)){
            $bonus_amount = 0;
        }

        if(empty($bonus_amount)){
            $this->appendToDebugLog("total bet not enough, then bonus amount less than {$min_bonus}: ", ['bonus_amount' => $bonus_amount, 'bonus_percentage' => $bonusPercentage, 'total_bet' => $total_bet]);
            $errorMessageLang = 'promo.total_bet_not_met';
            return ['success' => $success, 'message' => $errorMessageLang];
        }

        $this->appendToDebugLog('calculate bonus amount: ', ['bonus_amount' => $bonus_amount, 'bonus_percentage' => $bonusPercentage, 'total_bet' => $total_bet]);

        $success = true;

        return ['success' => $success, 'message' => $errorMessageLang, 'bonus_amount' => $bonus_amount];
    }
}
