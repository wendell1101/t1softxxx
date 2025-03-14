<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

require_once dirname(__FILE__) .'/abstract_promo_rule.php';

/**
 * Daily Cashback slots & mini games (Game Type)
 * OGP-29527
 *
 * Game Type: slots & mini games
 * 撈昨日的Game Type Total Revenue
 * 排程自動發放: 每天中午12:00
 * WC = Bonus x 1
 *
 * Formulation:
 * Total Cashback = (Total revenue * Tier Cashback%) - Other Bonuses Received
 *
 *
 * Bonus condition && Bonus release:
{
    "class": "promo_rule_t1bet_total_revenue_daily_bonus",
    "promo_cms_id": "promo_cms_id",
    "allowed_date": {
        "start": "",
        "end": ""
    },
    "minimum_revenue": 100,
    "bonus_settings": [
        {"percentage": 5, "min_revenue" :   100, "max_revenue" :  5000},
        {"percentage": 6, "min_revenue" :  5000, "max_revenue" : 20000},
        {"percentage": 7, "min_revenue" : 20000, "max_revenue" :    -1}
    ]
}
 *
 *
 *
$config['auto_apply_and_release_bonus_for_t1bet_total_revenue_daily_promocms_id'] = [
    "promo_cms_id" => [slots & mini game type ids]
];
 *
 *
 */
class Promo_rule_t1bet_total_revenue_daily_bonus extends Abstract_promo_rule{

	public function init($playerId, $promorule, $playerBonusAmount = null, $depositAmount=null){
		parent::init($playerId, $promorule, $playerBonusAmount, $depositAmount);
	}

	public function getClassName(){
		return 'Promo_rule_t1bet_total_revenue_daily_bonus';
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
        $result = $this->checkCustomizeBonusCondition($description, $extra_info, $errorMessageLang);

        return $result;
    }

    private function checkCustomizeBonusCondition($description, &$extra_info, &$errorMessageLang){
        $success = false;
        $bonus_amount = 0;
        $gameType = null;
        $bonus_settings = $description['bonus_settings'];
        $promo_cms_id = $description['promo_cms_id'];
        $minimum_revenue = $description['minimum_revenue'];
        $promo_settings = $this->utils->getConfig('auto_apply_and_release_bonus_for_t1bet_total_revenue_daily_promocms_id');

        if(!empty($promo_settings[$promo_cms_id])){
            $gameType = $promo_settings[$promo_cms_id];
        }

        $fromDate = $this->get_date_type(self::DATE_YESTERDAY_START);
        $toDate = $this->get_date_type(self::DATE_YESTERDAY_END);
        if(!empty($description['allowed_date']['start']) && !empty($description['allowed_date']['end'])){
            $fromDate = $description['allowed_date']['start'];
            $toDate = $description['allowed_date']['end'];
        }

        $today = $this->utils->getTodayForMysql();
        $this->appendToDebugLog('runBonusConditionChecker check date', ['fromDate'=>$fromDate, 'toDate'=>$toDate, 'today'=>$today]);

        if(empty($gameType)){
            $errorMessageLang = 'missing allow game type';
            return ['success' => $success, 'message' => $errorMessageLang];
        }

        #get game log win / loss
        $playerTotalBetWinLoss = $this->callHelper('getPlayerTotalBetWinLoss', [$fromDate, $toDate, 'total_player_game_day', 'date', null, $gameType]);
        $this->appendToDebugLog('original player total bet win loss: ', $playerTotalBetWinLoss);

        $totalWin = $playerTotalBetWinLoss['total_win'];
        $totalLoss = $playerTotalBetWinLoss['total_loss'];

        $mockTotalWin = null;
        if($this->process_mock('totalWin', $mockTotalWin)){
            $totalWin = $mockTotalWin;
        }
        $mockTotalLoss = null;
        if($this->process_mock('totalLoss', $mockTotalLoss)){
            $totalLoss = $mockTotalLoss;
        }

        #get total revenue
        $totalRevenue = $totalLoss - $totalWin;
        $this->appendToDebugLog('total revenue', [
            "formula" => "{$totalLoss}[total loss] - {$totalWin}[total win]",
            "result"  => $totalRevenue
        ]);

        #get total bonus
        $totalBonus = $this->callHelper('totalReleasedBonusByPlayerAndDateTime', [$this->playerId, $fromDate, $toDate]);
        $this->appendToDebugLog('original total bonus yesterday', ['result' => $totalBonus]);

        $mockTotalBonus = null;
        if($this->process_mock('totalBonus', $mockTotalBonus)){
            $totalBonus = $mockTotalBonus;
        }

        if($totalRevenue < $minimum_revenue){
            $errorMessageLang = 'total revenue is less than [' . $minimum_revenue . ']';
            return ['success' => $success, 'message' => $errorMessageLang];
        }

        #start check bonus conditions
        foreach ($bonus_settings as $setting) {
            if($setting['min_revenue']<=$totalRevenue && ($totalRevenue<$setting['max_revenue'] || $setting['max_revenue']<0)) {
                //max_revenue<0 means no limit
                $bonus_amount = ($totalRevenue * ($setting['percentage'] / 100)) - $totalBonus;
            }
        }

        if($bonus_amount <= 0){
            $errorMessageLang = "bonus amount is less than min bonus amount or equal to zero";
            return ['success' => $success, 'message' => $errorMessageLang];
        }

        $success = true;

        return ['success' => $success, 'message' => $errorMessageLang, 'bonus_amount' => $bonus_amount];
    }
}
