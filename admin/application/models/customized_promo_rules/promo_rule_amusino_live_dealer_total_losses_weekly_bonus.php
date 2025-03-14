<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

require_once dirname(__FILE__) .'/abstract_promo_rule.php';

/*
 * Weekly Total Losses Bonus
 * OGP-33824
 * 
 * Live Casino Weekly Total Losses Bonus
 * 
 * 規則：
 * Netloss = 負盈利, 負盈利 = 輸-贏, 負盈利 > 0 才允許派彩
 * 計算公式: 實際損失 * VIP比例
 * 計算週期為：週一00:00:00至每週日23:59:59
 * 
 * 
 * 該週期內, 無投注 or 有盈利（贏-輸>0）=> 不派彩
 *
 * Bonus condition && Bonus release:
 {
    "class": "promo_rule_amusino_live_dealer_total_losses_weekly_bonus",
    "game_tag_id": "4",
    "start": "2024-06-28 00:00:00", for QA test
    "end": "2024-06-28 23:59:59", for QA test
	"bonus_settings": {
        "1" : {"bonus_percentage":  5},
        "2" : {"bonus_percentage":  5},
        "3" : {"bonus_percentage":  5}
    }
}
 *
 */
class Promo_rule_amusino_live_dealer_total_losses_weekly_bonus extends Abstract_promo_rule{
    
	public function init($playerId, $promorule, $playerBonusAmount = null, $depositAmount=null){
		parent::init($playerId, $promorule, $playerBonusAmount, $depositAmount);
	}

	public function getClassName(){
		return 'promo_rule_amusino_live_dealer_total_losses_weekly_bonus';        
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

    private function checkCustomizeBonusCondition($description, &$errorMessageLang)
    {
        $this->load->model('game_type_model');
        $this->load->library(['og_utility']);

        $success = false;
        $gameType = null;
        $promoGameTagId = $description['game_tag_id'];
        if(empty($promoGameTagId)){
            $errorMessageLang = 'missing allow game tag';
            return ['success' => $success, 'message' => $errorMessageLang];
        }

        $gameTypeRawList = $this->game_type_model->getAllGameTypeListWithTag($promoGameTagId);
        if(empty($gameTypeRawList)){
            $errorMessageLang = 'missing allow game type';
            return ['success' => $success, 'message' => $errorMessageLang];
        }

        $gameType = $this->og_utility->array_pluck($gameTypeRawList, 'id');
        $this->appendToDebugLog('game type from game tag', ['list'=>$gameType]);

        $_extra_info = [];
        $_extra_info['week_start'] = 'monday';
        $fromDate = !empty($description['start']) ? $description['start'] : $this->get_date_type(self::DATE_LAST_WEEK_START, $_extra_info);
        $toDate = !empty($description['end']) ? $description['end'] : $this->get_date_type(self::DATE_LAST_WEEK_END, $_extra_info);
        $today = $this->utils->getTodayForMysql();
        $this->appendToDebugLog('runBonusConditionChecker check date', ['fromDate'=>$fromDate, 'toDate'=>$toDate, 'today'=>$today]);

        #get game log win / loss by game type
        $playerTotalBetWinLoss = $this->callHelper('getPlayerTotalBetWinLoss',
            [$fromDate, $toDate, 'total_player_game_day', 'date', null, $gameType]
        );
		$totalWin = $playerTotalBetWinLoss['total_win'];
		$totalLoss = $playerTotalBetWinLoss['total_loss'];
        $netLoss = $totalWin - $totalLoss;
	    $this->appendToDebugLog('checkCustomizeBonusCondition getPlayerTotalBetWinLoss', ['getPlayerTotalBetWinLoss', $playerTotalBetWinLoss, 'netLoss' => $netLoss]);
		if($netLoss >= 0){
			$errorMessageLang =  'promo_rule.common.error';
	    	return ['success' => $success, 'message' => $errorMessageLang];
		}

        #get bonus setting by level
        $levelId = $this->levelId;
        $bonusSetting = $description['bonus_settings'];
        if(!array_key_exists($levelId, $bonusSetting)){
            $errorMessageLang = 'promo_custom.not_in_allowed_vip_level';
            return ['success' => $success, 'message' => $errorMessageLang];
        }

		$success = true;
		$setting = $bonusSetting[$levelId];
		$percentage = $setting['bonus_percentage'] / 100;
		$bonus_amount = abs($netLoss) * $percentage;
		$this->appendToDebugLog('checkCustomizeBonusCondition bonus amount', ['total net loss' => abs($netLoss), 'percentage' => $percentage]);
		
		return ['success' => $success, 'message' => $errorMessageLang, 'bonus_amount' => $bonus_amount];
    }
}
