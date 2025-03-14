<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

require_once dirname(__FILE__) .'/abstract_promo_rule.php';

/**
 *
 *
js condition:
{
    "class": "promo_rule_t1t_common_brazil_daily_rescue",
    "triggered":{ "cron": "0 8 * * *" },
    "allowed_date": {
        "start": "2022-10-02 00:00:00",
        "end": "2022-10-03 23:59:59"
    },
    "bonus_settings": [
        {"min_loss": 100, "bonus_amount": 5},
        {"min_loss": 300, "bonus_amount": 15},
        {"min_loss": 500, "bonus_amount": 25},

        {"min_loss": 1000, "bonus_amount": 50},
        {"min_loss": 3000, "bonus_amount": 150},
        {"min_loss": 6000, "bonus_amount": 300},

        {"min_loss": 10000, "bonus_amount": 500},
        {"min_loss": 30000, "bonus_amount": 1500},
        {"min_loss": 50000, "bonus_amount": 2500},

        {"min_loss": 100000, "bonus_amount": 5000}
    ]
}

P.S.
@.bonus_settings.bet_condition_rate Not used and removed them.
@.allowed_date.start and @.allowed_date.end , that are for the period of the player's loss amount in the method, "checkCustomizeBounsCondition".
@.triggered.cron The string should referenced to $config['all_cron_jobs']['cronjob_XXXX']['cron'].
 The string, "0 8 * * *", its means every day 08:00 am.


Promo Manager Mock For Class:(in dryrun_promo page of SBE)
{
    "getPlayerTotalBetWinLoss": {"total_bet": 110, "total_win": 220, "total_loss": 330}
}

That for directly specify the turnover amounts of mapping bonus.
 */
class Promo_rule_t1t_common_brazil_daily_rescue extends Abstract_promo_rule{

	public function init($playerId, $promorule, $playerBonusAmount = null, $depositAmount=null){
		parent::init($playerId, $promorule, $playerBonusAmount, $depositAmount);
	}

	public function getClassName(){
		return 'Promo_rule_t1t_common_brazil_daily_rescue';
	}

	/**
	 * run bonus condition checker
	 * @param  array $description original description in rule
	 * @param  array $extra_info
	 * @param  boolean $dry_run
	 * @return  array ['success'=> success, 'message'=> errorMessageLang]
	 */
	protected function runBonusConditionChecker($description, &$extra_info, $dry_run){
		$errorMessageLang=null;
        $result = $this->checkCustomizeBounsCondition($description, $extra_info, $errorMessageLang);

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
		$errorMessageLang=null;
        $result = $this->checkCustomizeBounsCondition($description, $extra_info, $errorMessageLang);

		return $result;
	}

	private function checkCustomizeBounsCondition($description, &$extra_info, &$errorMessageLang){

        $success = false;
        $bonus_amount = 0;
        $game_type = empty($description['game_type'])? null: $description['game_type'] ;
        $bonus_settings = $description['bonus_settings'];

        $fromDate = $this->callHelper('get_date_type', [self::DATE_YESTERDAY_START]);
        $toDate = $this->callHelper('get_date_type', [self::DATE_YESTERDAY_END]);
        if(!empty($description['allowed_date']['start']) && !empty($description['allowed_date']['end'])){
            $fromDate = $description['allowed_date']['start'];
            $toDate = $description['allowed_date']['end'];
        }

        #get game log win / loss
        $playerTotalBetWinLoss = $this->callHelper('getPlayerTotalBetWinLoss',[ $fromDate // #1
                                                                                , $toDate // #2
                                                                                , 'total_player_game_day' // #3
                                                                                , 'date' // #4
                                                                                , null  // #5
                                                                                , $game_type // #6
                                                                            ]);

        // $netloss = abs($totalWin - $totalLoss);
        $this->appendToDebugLog('getPlayerTotalBetWinLoss.fromDate_toDate: ', [$fromDate , $toDate]);
        $this->appendToDebugLog('getPlayerTotalBetWinLoss: ', $playerTotalBetWinLoss);
        $totalBet = $playerTotalBetWinLoss['total_bet'];
        $totalWin = $playerTotalBetWinLoss['total_win'];
        $totalLoss = $playerTotalBetWinLoss['total_loss'];

        if (!empty($bonus_settings)) {

            usort($bonus_settings, function ($a, $b) {
                return $a['min_loss'] > $b['min_loss'];
            }); // order by min_loss asc

            $totalLossMeetMin = null;
            $bonus_setting_latest_meet = [];
            foreach ($bonus_settings as $list) {
                if($totalLoss >= $list['min_loss']){
                    $totalLossMeetMin = true;
                    $bonus_setting_latest_meet = $list;
                }else{
                    $totalLossMeetMin = false;
                }
                if($totalLossMeetMin == false){
                    break;
                }
            }
            if(!empty($bonus_setting_latest_meet)){
                $bonus_amount = $bonus_setting_latest_meet['bonus_amount'];
            }
        }



        $this->appendToDebugLog('166.bonus_amount: ', [$bonus_amount
                                // , 'bet_condition_rate:'
                                // , $bet_condition_rate
                                , 'getPlayerTotalBetWinLoss: '
                                , $playerTotalBetWinLoss
                            ]);


        if(empty($totalLoss)){
            $errorMessageLang = 'promo_rule.common.error';
            $this->appendToDebugLog('Player Do Not Have Loss');
            return ['success' => $success, 'message' => $errorMessageLang];
        }


        $success = true;
        return ['success' => $success
            , 'message' => $errorMessageLang
            , 'bonus_amount' => $bonus_amount
        ];
	}

}
