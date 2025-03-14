<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

require_once dirname(__FILE__) .'/abstract_promo_rule.php';

/**
 * 負盈利 = 輸-贏
 * 負盈利 > 0 且 負盈利 > 0.5U 才派發
 * 公式: (輸-贏) * VIP比例
 * 週期：當天00:00:00 至 當天23:59:59
 * 白金1 = 3%, 白金2 = 3%, 白金3 = 3%, 白金4 = 3%, 
 * 鑽石1 = 5%, 鑽石2 = 5%, 鑽石3 = 6%, 鑽石4 = 7%, 
 * 最強王者 = 8%
 
{
    "class": "promo_rule_amusino_slot_machine_rescue_daily",
    "game_tag_id": $game_tag_id,
    "minimum_limit" : 0.5,
    "start": "Y-m-d H:i:s", // for QA test
    "end": "Y-m-d H:i:s",  // for QA test
    "bonus_settings": {
        "1":{ "percentage": 3 },
        "2":{ "percentage": 3 },
        "3":{ "percentage": 3 },
        "4":{ "percentage": 3 },
        "5":{ "percentage": 5 },
        "6":{ "percentage": 5 },
        "7":{ "percentage": 6 },
        "8":{ "percentage": 7 },
        "9":{ "percentage": 8 }
    }
}

$config['auto_apply_and_release_bonus_for_amusino_slot_machine_rescue_daily_promocms_id'] = [

];

 *
 */
class Promo_rule_amusino_slot_machine_rescue_daily extends Abstract_promo_rule{

	public function init($playerId, $promorule, $playerBonusAmount = null, $depositAmount=null){
		parent::init($playerId, $promorule, $playerBonusAmount, $depositAmount);
	}

	public function getClassName(){
		return 'Promo_rule_amusino_slot_machine_rescue_daily';
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
		$bonus_setting = $description['bonus_settings'];

        // get game type
        $gameType = null;
        $game_tag_id = $description['game_tag_id'];
        if(empty($game_tag_id)){
            $errorMessageLang = 'missing allow game tag';
            return ['success' => $success, 'message' => $errorMessageLang];
        }

        $game_type_raw_list = $this->game_type_model->getAllGameTypeListWithTag($game_tag_id);
        if(!empty($game_type_raw_list)){
            $gameType = $this->og_utility->array_pluck($game_type_raw_list, 'id');
        }
        $this->appendToDebugLog('game type from game tag', ['list'=>$gameType]);
       

        $fromDate = !empty($description['start']) ? $description['start'] : $this->get_date_type(self::DATE_YESTERDAY_START);
        $toDate = !empty($description['end']) ? $description['end'] : $this->get_date_type(self::DATE_YESTERDAY_END);

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
        $totalWin = $playerTotalBetWinLoss['total_win'];
		$totalLoss = $playerTotalBetWinLoss['total_loss'];
        $netLoss = $totalWin - $totalLoss;
        $this->appendToDebugLog('checkCustomizeBonusCondition getPlayerTotalBetWinLoss', ['getPlayerTotalBetWinLoss', $playerTotalBetWinLoss, 'netLoss' => $netLoss]);

        if($netLoss >= 0){
            $errorMessageLang = 'promo_rule.common.error';
            return ['success' => $success, 'message' => $errorMessageLang];
        }        

        #get bonus setting by level
        $levelId = $this->levelId;
        if(!array_key_exists($levelId, $bonus_setting)){
            $errorMessageLang = 'promo_custom.not_in_allowed_vip_level';
            return ['success' => $success, 'message' => $errorMessageLang];
        }
        
        $bonusPercentage = $bonus_setting[$levelId]['percentage'] / 100;
        $bonusAmount = abs($netLoss) * $bonusPercentage;
        $this->appendToDebugLog('calculate bonus amount: ', ['bonus_amount' => $bonusAmount, 'bonus_percentage' => $bonusPercentage, 'netLoss' => $netLoss]);

        if($bonusAmount < $description['minimum_limit']){
            $errorMessageLang = 'promo_rule.common.error';
            return ['success' => $success, 'message' => $errorMessageLang];
        }
        
        $success = true;

        return ['success' => $success, 'message' => $errorMessageLang, 'bonus_amount' => $bonusAmount];
    }
}
