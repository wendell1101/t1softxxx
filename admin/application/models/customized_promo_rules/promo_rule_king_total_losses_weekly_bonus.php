<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

require_once dirname(__FILE__) .'/abstract_promo_rule.php';

/**
 *
 * OGP-31871
 * 
 * 每日ㄧ00:00:00 排程發放一次
 * 
 * 規則：
 * 際損失=負盈利, 負盈利 = 輸-贏, 負盈利 > 0 才允許派彩
 * 計算公式: 實際損失*VIP比例
 * 計算週期為：週一00:00:00至每週日23:59:59
 * 
 * 獎金最高10000BRL
 * 
 * 該週期內, 無投注 or 有盈利（贏-輸>0）=> 不派彩
 *
condition:
{
    "class": "promo_rule_king_total_losses_weekly_bonus",
	"allowed_date": {
        "start": "2023-12-04",
        "end": "2023-12-10"
    },
	"max_bonus": 10000,
	"bonus_settings": {
        "VIP1"  : {"bonus_percentage":  6},
        "VIP2"  : {"bonus_percentage":  7},
        "VIP3"  : {"bonus_percentage":  8},
        "VIP4"  : {"bonus_percentage":  9},
        "VIP5"  : {"bonus_percentage": 10},
        "VIP6"  : {"bonus_percentage": 11},
        "VIP7"  : {"bonus_percentage": 12},
        "VIP8"  : {"bonus_percentage": 13},
        "VIP9"  : {"bonus_percentage": 14},
        "VIP10" : {"bonus_percentage": 15},
        "VIP11" : {"bonus_percentage": 25}
    }
}
*
*
*/
class Promo_rule_king_total_losses_weekly_bonus extends Abstract_promo_rule{

	public function init($playerId, $promorule, $playerBonusAmount = null, $depositAmount=null){
		parent::init($playerId, $promorule, $playerBonusAmount, $depositAmount);
	}

	public function getClassName(){
		return 'promo_rule_king_total_losses_weekly_bonus';
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
		$result = $this->checkCustomizeBonusCondition($description, $extra_info, $errorMessageLang);

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
		$request = $this->checkCustomizeBonusCondition($description, $extra_info, $errorMessageLang);

        return $request;
	}
	
	private function checkCustomizeBonusCondition($description, &$extra_info, &$errorMessageLang){
		$success = false;
	    $bonus_amount = 0;
		
		$bonus_setting = $description['bonus_settings'];
		$max_bonus = $description['max_bonus'];
		$levelId = $this->levelId;
		
		$_extra_info['week_start'] = 'monday';
		$fromDate = $this->get_date_type(self::DATE_LAST_WEEK_START, $_extra_info);
		$toDate = $this->get_date_type(self::DATE_LAST_WEEK_END, $_extra_info);
		if(!empty($description['allowed_date']['start']) && !empty($description['allowed_date']['end'])){
			$fromDate = $description['allowed_date']['start'];
            $toDate = $description['allowed_date']['end'];
		}
		
		$today = $this->utils->getTodayForMysql();
		$this->appendToDebugLog('checkCustomizeBonusCondition check date', ['allowed_bet_from' => $fromDate, 'allowed_bet_to' => $toDate, 'today' => $today]);

	    #get game log win / loss
        $playerTotalBetWinLoss = $this->callHelper('getPlayerTotalBetWinLoss',[$fromDate, $toDate, 'total_player_game_day', 'date']);
		$totalWin = $playerTotalBetWinLoss['total_win'];
		$totalLoss = $playerTotalBetWinLoss['total_loss'];

        $netloss = $totalWin - $totalLoss;
	    $this->appendToDebugLog('checkCustomizeBonusCondition getPlayerTotalBetWinLoss', ['getPlayerTotalBetWinLoss', $playerTotalBetWinLoss, 'netloss' => $netloss]);
		if($netloss >= 0){
			$errorMessageLang =  'promo_rule.common.error';
	    	return ['success' => $success, 'message' => $errorMessageLang];
		}

        if(!array_key_exists($levelId, $bonus_setting)){
            $errorMessageLang = 'promo_custom.not_in_allowed_vip_level';
            return ['success' => $success, 'message' => $errorMessageLang];
        }

		$totalNetloss = abs($netloss);
		$success = true;

		$setting = $bonus_setting[$levelId];
		$percentage = $setting['bonus_percentage'] / 100;
		$bonus_amount = $totalNetloss * $percentage;
		$this->appendToDebugLog('checkCustomizeBonusCondition bonus amount', ['total net loss' => $totalNetloss, 'percentage' => $percentage]);
		
		if($bonus_amount >= $max_bonus){
			$bonus_amount = $max_bonus;
		}

		return ['success' => $success, 'message' => $errorMessageLang, 'bonus_amount' => $bonus_amount];
	}
}
