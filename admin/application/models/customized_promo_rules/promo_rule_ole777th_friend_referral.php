<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

require_once dirname(__FILE__) .'/abstract_promo_rule.php';

/**
 * OGP-29821
 * 好友推薦獎金

condition:
{
    "class": "promo_rule_ole777th_friend_referral",
	"allowed_date": {
        "start": "2023-02-16",
        "end": "2023-02-25"
    },
    "referred_on": {
		"start": "2023-02-16",
        "end": "2033-02-25"
    },
    "betting_date": {
		"start": "2023-02-16",
        "end": "2033-02-25"
    },
    "bonus_settings": {
        "percentage" : {"min_bet": 0, "bonus_amount": 0.001},
    }
}

 *
 *
 */
class Promo_rule_ole777th_friend_referral extends Abstract_promo_rule{

	public function init($playerId, $promorule, $playerBonusAmount = null, $depositAmount=null){
		parent::init($playerId, $promorule, $playerBonusAmount, $depositAmount);
	}

	public function getClassName(){
		return 'Promo_rule_ole777th_friend_referral';
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

	public function get_referred_list_by_referred_on($start=null, $end=null){
		$this->load->model(array('player_friend_referral'));
		$rows = $this->player_friend_referral->getPlayerReferralList($this->playerId, null, $start, $end);
		$referred_player_id_list = $this->callHelper('array_pluck', [$rows, 'invitedUserId']);
		return $referred_player_id_list;
	}

	public function getPlayersBetByDate($invitedPlayerId, $fromDate, $toDate, $gamePlatformId = null, $gameTypeId = null){
	    $this->load->model(['total_player_game_day']);
	    return $this->total_player_game_day->getPlayerTotalBettingAmountByPlayer($invitedPlayerId, $fromDate, $toDate, $gamePlatformId, $gameTypeId);

	}

	private function checkCustomizeBounsCondition($description, &$extra_info, &$errorMessageLang){
		$success = false;
        $minDate = null;
        $maxDate = null;
        $errorMessageLang = null;
        $bonus_amount = 0;
        $summary_total_bet_on_referred_player = 0;
        $game_type = isset($description['game_type'])? $description['game_type'] : null;;
        $allowed_date = isset($description['allowed_date'])? $description['allowed_date'] : null;
        $betting_date = isset($description['betting_date'])? $description['betting_date'] : null;
        $referred_on = isset($description['referred_on'])? $description['referred_on'] : null;
        $bonus_settings = $description['bonus_settings'];

        if(!empty($betting_date)){
        	$betting_from = isset($betting_date['start'])? $betting_date['start'] : null;
    		$betting_to = isset($betting_date['end'])? $betting_date['end'] : null;
        }else{
        	$betting_from = $this->callHelper('get_date_type', [self::DATE_YESTERDAY_START]);
        	$betting_to = $this->callHelper('get_date_type', [self::DATE_YESTERDAY_END]);
        }

        if(!empty($referred_on)){
        	$referred_from = isset($referred_on['start'])? $referred_on['start'] : null;
    		$referred_to = isset($referred_on['end'])? $referred_on['end'] : null;
        }else{
        	$referred_from = null;
    		$referred_to = null;
        }

        if(!empty($allowed_date['start']) && !empty($allowed_date['end'])){
        	$today = $this->utils->getTodayForMysql();
            $minDate = $this->utils->formatDateForMysql(new DateTime($allowed_date['start']));
            $maxDate = $this->utils->formatDateForMysql(new DateTime($allowed_date['end']));
            $date = new DateTime($today);
        	$currentDate = $date->format('Y-m-d');
            if( $currentDate < $minDate && $currentDate > $maxDate ){
            	$errorMessageLang = 'Not right date';
            	return ['success' => $success, 'message' => $errorMessageLang];
            }
        }

        $referred_player_id_list = $this->get_referred_list_by_referred_on($referred_from, $referred_to);
        $this->appendToDebugLog('search invitedPlayerIds in referred player id list', ['invitedPlayerId'=>$referred_player_id_list]);
        if( is_array($referred_player_id_list) && !empty($referred_player_id_list)){
        	foreach ($referred_player_id_list as $referred_player_id){
        		$referred_player_id_of_total_bet = $this->getPlayersBetByDate($referred_player_id, $betting_from, $betting_to, null, $game_type);
        		$summary_total_bet_on_referred_player = $summary_total_bet_on_referred_player + $referred_player_id_of_total_bet;
        		$this->appendToDebugLog('conculated total bet in referred player id list', ['invitedPlayerTotalBets'=>$referred_player_id_of_total_bet, 'summaryTotalBet'=>$summary_total_bet_on_referred_player ]);
        	}

        	if (!empty($bonus_settings)) {
	    		foreach ($bonus_settings as $bonus_types => $list) {
	            	if(isset($list['min_bet']) && isset($list['bonus_amount'])){
		            	switch ($bonus_types) {
		            		case 'fix_amount':
		            			if($summary_total_bet_on_referred_player > $list['min_bet']){
				                    $success = true;
				                    $bonus_amount = $list['bonus_amount'];
				                }else{
				                	$errorMessageLang = 'promo.total_bet_not_met';
				                }
		            		break;
		            		case 'percentage':
		            			if($summary_total_bet_on_referred_player > $list['min_bet']){
				                    $success = true;
				                    $bonus_amount = $summary_total_bet_on_referred_player * $list['bonus_amount'];
				                }else{
				                	$errorMessageLang = 'promo.total_bet_not_met';
				                }
		            		break;
		            	}
		        	}else{
		        		$errorMessageLang = 'Bonus condition does not set min_bet or bonus_amount';
		        	}
	            }
	        }else{
	        	$errorMessageLang = 'Bonus condition does not set bonus_settings';
	        }
    	}else{
    		$errorMessageLang = 'referred friend is empty';
    	}

       	if(!$success){
            return ['success' => $success, 'message' => $errorMessageLang];
        }

        return ['success' => $success, 'message' => $errorMessageLang, 'bonus_amount' => $bonus_amount];
	}
}
