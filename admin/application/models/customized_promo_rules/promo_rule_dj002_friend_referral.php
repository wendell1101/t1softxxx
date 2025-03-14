<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

require_once dirname(__FILE__) .'/abstract_promo_rule.php';

/**
 *
 * Friend Referral
 *
 * OGP-16681

c2. *Bonus Condition

{
    "class": "promo_rule_dj002_friend_referral",
    "referral_bonus_settings": {
    	"0":0,
    	"1":0,
    	"2":20,
    	"3":20,
    	"4":20,
		"5":50,
    	"6":50,
    	"7":50,
    	"8":50,
    	"9":50,
    	"10":100
    },
	"bonus_max":100,
	"start_date_time": "2020-03-01 00:00:00",
 	"last_date_time": "2020-03-31 23:59:59",
	"min_deposit_amount":100,
	"msg_zero_bonus": "尚未符合紅利資格",
	"inform": "本月您已推荐 : %d 位玩家"
}

3. *Bonus Release
{
    "class": "promo_rule_dj002_friend_referral",
    "referral_bonus_settings": {
    	"0":0,
    	"1":0,
    	"2":20,
    	"3":20,
    	"4":20,
		"5":50,
    	"6":50,
    	"7":50,
    	"8":50,
    	"9":50,
    	"10":100
    },
	"bonus_max":100,
	"start_date_time": "2020-03-01 00:00:00",
 	"last_date_time": "2020-03-31 23:59:59",
	"min_deposit_amount":100,
	"msg_zero_bonus": "尚未符合紅利資格",
	"inform": "本月您已推荐 : %d 位玩家"
}

P.S. The following settings for RD / QA test.
"start_date_time": "2020-03-01 00:00:00",
"last_date_time": "2020-03-31 23:59:59",

mock:
{
    "min_deposit_amount": 100
}

 *
 *
 */
class Promo_rule_dj002_friend_referral extends Abstract_promo_rule{

	public function init($playerId, $promorule, $playerBonusAmount = null, $depositAmount=null){
		parent::init($playerId, $promorule, $playerBonusAmount, $depositAmount);
	}

	public function getClassName(){
		return 'Promo_rule_dj002_friend_referral';
	}

	protected function runPromo($description){
		$this->appendToDebugLog(__METHOD__.'() at line:'. __LINE__ );

		// defaults
		$msg4zeroBonus = '未符合红利資格';
		$msg4joined = '您已申请过优惠活动';
		$informStr  = '本月您已推荐 : %d 位玩家';
		$min_amount = 100; // Condition: Referred Player's career deposit must be greater than 100
		$bonus_level_max = 100;
		$bonus_level = [];
		$bonus_level['0'] = 0;
		$bonus_level['1'] = 0;
		$bonus_level['2'] = 20;
		$bonus_level['3'] = 20;
		$bonus_level['4'] = 20;
		$bonus_level['5'] = 50;
		$bonus_level['6'] = 50;
		$bonus_level['7'] = 50;
		$bonus_level['8'] = 50;
		$bonus_level['9'] = 50;
		$bonus_level['10'] = 100;

		// count times during this month.
		$first_datetime_of_month=$this->callHelper('get_date_type', [self::DATE_THIS_MONTH_START]);
		$last_datetime_of_month=$this->callHelper('get_date_type', [self::DATE_THIS_MONTH_END]);
		$first_date_of_month =  explode(' ', $first_datetime_of_month)[0];
		$last_date_of_month = explode(' ', $last_datetime_of_month)[0];

		// loading
		if( isset($description['referral_bonus_settings']) ){
			$bonus_level = $description['referral_bonus_settings'];
		}
		if( isset($description['bonus_max']) ){
			$bonus_level_max = $description['bonus_max'];
		}
		if( isset($description['min_deposit_amount']) ){
			$min_amount = $description['min_deposit_amount'];
		}
		if( isset($description['msg_zero_bonus']) ){
			$msg4zeroBonus = $description['msg_zero_bonus']; // 未符合红利資格
		}
		if( isset($description['msg_joined']) ){
			$msg4joined = $description['msg_joined']; // 本月您已參加
		}
		if( isset($description['inform']) ){
			$informStr = $description['inform']; // '本月您已推荐 : %d 位玩家'
		}

		if( isset($description['start_date_time']) ){
			$first_datetime_of_month=$description['start_date_time'];
			$first_date_of_month =  explode(' ', $first_datetime_of_month)[0];
		}

		if( isset($description['last_date_time']) ){
			$last_datetime_of_month=$description['last_date_time'];
			$last_date_of_month =  explode(' ', $last_datetime_of_month)[0];
		}

		if($this->process_mock('min_deposit_amount', $min_deposit_amount)){
			//use mock data
			$min_amount = $min_deposit_amount;
			$this->appendToDebugLog(__METHOD__.'() use mock min_deposit_amount', ['min_deposit_amount'=>$min_deposit_amount]);
		}

		// defined
		// $min_amount = 100; // Condition: Referred Player's career deposit must be greater than 100
		$counter = 0;
		$player_id_list_str = "";

		$start_datetime = $first_datetime_of_month;
		$start_date = $first_date_of_month;

		$end_datetime = $last_datetime_of_month;
		$end_date = $last_date_of_month;
		$joined_rows = $this->get_rows_on_this_promo_during_dates($start_datetime, $end_datetime);
		$joined_bonus_times = count($joined_rows);

		$this->appendToDebugLog('joined_bonus_times:'. $joined_bonus_times);

		// Get the referred player id list.
		if($joined_bonus_times > 0){
			$lastIndex = $joined_bonus_times -1;
			$newDate = new DateTime( $joined_rows[$lastIndex]['dateApply'] );
			$end_date = $newDate->format('Y-m-d');
		}

		$player_id_list_str = $this->get_referred_player_id_list_in_currect_month($start_date, $end_date);
		$this->appendToDebugLog('start_date111:'. $start_date );
		$this->appendToDebugLog('end_date111:'. $end_date );
		$this->appendToDebugLog('player_id_list_str:'. $player_id_list_str );

		// Get the counter of "Total Deposit Amount" greater than 100.
		$counter = $this->get_counter_by_player_total_deposit_with_min( $player_id_list_str, $min_amount );

		// calc release_bonus
		$release_bonus = 0;
		if( isset($bonus_level[$counter]) ){
			$release_bonus = $bonus_level[$counter];
		}else{
			$release_bonus = $bonus_level_max;
		}

		// $informStr = '本月您已推荐 : %d 位玩家'; //  will replace to empty after sprintf().
		$inform = sprintf($informStr, $counter);

		$this->appendToDebugLog('release_bonus:'. $release_bonus );
		$this->appendToDebugLog('inform:'. $inform );

		if( empty($joined_bonus_times) ){
			if($release_bonus > 0){
				$success = true;
				$message = '';
			}else{
				$success = false;
				$message = $msg4zeroBonus;
			}
		}else{
			$success = false;
			$message = $msg4joined;
		}

		$return =[];
		$return['success'] = $success;
		$return['message'] = $message;
		$return['inform'] = $inform;

		$return['bonus_amount'] = $release_bonus;
		// $return['errorMessageLang'] = $errorMessageLang;

		return $return;
		// // for 2. *Bonus Condition
		// var result={ "success": true, "message": null, "inform": inform }; // inform for OGP-16400
		// result;

		// // for 3. *Bonus Release
		// var errorMessageLang = null;
		// var result={ "bonus_amount": release_bonus, "errorMessageLang": errorMessageLang };
		// result;
	} // EOF runPromo

	/**
	 * run bonus condition checker
	 * @param  array $description original description in rule
	 * @param  array $extra_info
	 * @param  boolean $dry_run
	 * @return  array ['success'=> success, 'message'=> errorMessageLang]
	 */
	protected function runBonusConditionChecker($description, &$extra_info, $dry_run){
		$success=false;
		$errorMessageLang=null;
		$this->appendToDebugLog(__METHOD__.'() at line:'. __LINE__ );
		$this->appendToDebugLog( 'description:::::'. var_export($description, true));
		// $today=$this->utils->getTodayForMysql();
		// if($this->process_mock('min_amount', $min_amount)){
		// 	//use mock data
		// 	$this->appendToDebugLog(__METHOD__.'87use mock min_amount', ['min_amount'=>$min_amount]);
		// }

		$resultRunPromo = $this->runPromo($description);


		// $bonus_settings=$description['bonus_settings'];

		// //check date
		// $allowed_date=$description['allowed_date'];
		// $this->appendToDebugLog('check allowed_date', ['allowed_date'=>$allowed_date]);
		// $d=new DateTime($today);
		// $currentDate=$d->format('Y-m-d');
		// $thisMonthFrom=$d->format('Y-m').'-01 '.Utils::FIRST_TIME;
		// $thisMonthTo=$d->format('Y-m-t').' '.Utils::LAST_TIME;

		// $minDate=$d->format('Y-m-').$allowed_date['start'];
		// $maxDate=$d->format('Y-m-').$allowed_date['end'];

		// $d=new DateTime($d->format('Y-m').'-01');
		// $d->modify('-1 month');
		// $fromDate=$d->format('Y-m').'-01 '.Utils::FIRST_TIME;
		// $toDate=$d->format('Y-m-t').' '.Utils::LAST_TIME;

		// if($currentDate>=$minDate && $currentDate<=$maxDate){
		// 	$availableLevelId=$this->searchAvailableLevelId($bonus_settings, $fromDate, $toDate, $extra_info, $description, $errorMessageLang);
		// 	$success=!empty($availableLevelId);
		// }else{
		// 	$errorMessageLang='Not right date';
		// }

		// $success=true;
		// $errorMessageLang = __METHOD__.'dbg116';
		$continue_process_after_script = false;
		$result=[	'success'=>$resultRunPromo['success']
					, 'message' => $resultRunPromo['message']
					, 'inform' => $resultRunPromo['inform']
					, 'continue_process_after_script' => $continue_process_after_script ];

		return $result;
	} // EOF runBonusConditionChecker

	/**
	 * generate withdrawal condition
	 * @param  array $description original description in rule
	 * @param  array $extra_info
	 * @param  boolean $dry_run
	 * @return  array ['success'=> success, 'message'=> errorMessageLang, 'withdrawal_condition_amount'=> withdrawal condition amount]
	 */
	protected function generateWithdrawalCondition($description, &$extra_info, $dry_run){

		// // @todo ref. by admin/application/models/customized_promo_rules/promo_rule_ole777_deposit_bonus_monthly.php
		// $result=['success'=>$success, 'message'=>$errorMessageLang, 'withdrawal_condition_amount'=>round($withdrawal_condition_amount, 2)];
		// return $result;
		$this->appendToDebugLog(__METHOD__.'() at line:'. __LINE__ );
		return $this->returnUnimplemented();
	} // EOF generateWithdrawalCondition

    /**
     * generate transfer condition
     * @param  array $description original description in rule
     * @param  array $extra_info exchange data
     * @param  boolean $dry_run
     * @return  array ['success'=> success, 'message_lang'=> errorMessageLang, 'withdrawal_condition_amount'=> withdrawal condition amount]
     */
    protected function generateTransferCondition($description, &$extra_info, $dry_run){
		$this->appendToDebugLog(__METHOD__.'() at line:'. __LINE__ );
        return $this->returnUnimplemented();
    } // EOF generateTransferCondition

	/**
	 * release bonus
	 * @param  array $description original description in rule
	 * @param  array $extra_info
	 * @param  boolean $dry_run
	 * @return  array ['success'=> success, 'message'=> errorMessageLang, 'bonus_amount'=> bonus amount]
	 */
	protected function releaseBonus($description, &$extra_info, $dry_run){
		$success=false;
		// $errorMessageLang=null;
		$bonus_amount=0;
		$this->appendToDebugLog(__METHOD__.'() at line:'. __LINE__ );

		$this->appendToDebugLog( 'description:::::'. var_export($description, true));


		$resultRunPromo = $this->runPromo($description);

		// $bonus_settings=$description['bonus_settings'];
		// $allowed_date=$description['allowed_date'];

        // $today=$this->utils->getTodayForMysql();
        // if($this->process_mock('min_amount', $min_amount)){
        //     //use mock data
        //     $this->appendToDebugLog(__METHOD__.'168use mock min_amount', ['min_amount'=>$min_amount]);
        // }

        // $d=new DateTime($today);
        // $d=new DateTime($d->format('Y-m').'-01');
        // $d->modify('-1 month');
        // $fromDate=$d->format('Y-m').'-01 '.Utils::FIRST_TIME;
        // $toDate=$d->format('Y-m-t').' '.Utils::LAST_TIME;

		// $availableLevelId=$this->searchAvailableLevelId($bonus_settings, $fromDate, $toDate, $extra_info, $description, $errorMessageLang);
		// $this->appendToDebugLog('calc bonus', ['bonus_settings'=>$bonus_settings,
		// 	'allowed_date'=>$allowed_date, 'availableLevelId'=>$availableLevelId]);
		// if(!empty($availableLevelId)){
		// 	if(array_key_exists($availableLevelId, $bonus_settings)){
		// 		$setting=$bonus_settings[$availableLevelId];
		// 		$bonus_amount=$setting['fixed_bonus'];
		// 		$success=true;
		// 		$this->appendToDebugLog('get bonus setting', ['bonus_settings'=>$setting, 'levelId'=>$availableLevelId, 'bonus_amount'=>$bonus_amount]);
		// 	}else{
		// 		$errorMessageLang='Not right group level';
		// 	}
		// }

		// $bonus_amount = 1;
		// $success = true;
		// $errorMessageLang = 'dbg190';
		$result=[	'success' => $resultRunPromo['success']
				, 'message'=>$resultRunPromo['message']
				, 'inform' => $resultRunPromo['inform']
				, 'bonus_amount' => $resultRunPromo['bonus_amount'] ];
		return $result;
	}// EOF releaseBonus



	private function searchAvailableLevelId($bonus_settings, $fromDate, $toDate, &$extra_info, $description, &$errorMessageLang){
        $success = false;
	    $availableLevelId=null;
		$endAt=$this->callHelper('getLastMonthEndTime', []);
		$maxLevelId=$this->callHelper('getLastUpgradeLevelOrCurrentLevel',[$endAt]);
		$this->appendToDebugLog('search level id getLastUpgradeLevelOrCurrentLevel', ['endAt'=>$endAt, 'maxLevelId'=>$maxLevelId]);
		$levelIdList=$this->callHelper('getLevelIdListDownToFirstLevel',[$maxLevelId]);
		$this->appendToDebugLog('getLevelIdListDownToFirstLevel',['levelIdList'=>$levelIdList]);
		if(!empty($levelIdList)){
			$existLevel=false;
			foreach ($levelIdList as $levelId) {
				if(array_key_exists($levelId, $bonus_settings)){
					$existLevel=true;
					break;
				}
			}
			if($existLevel){
				//check bets and deposit
				list($bets, $deposit)=$this->callHelper('getBetsAndDepositByDate', [$fromDate, $toDate]);
				//high level to low level
				foreach ($levelIdList as $levelId) {
					if(array_key_exists($levelId, $bonus_settings)){
						$setting=$bonus_settings[$levelId];
						$min_bets=$setting['min_bets'];
						$min_deposit=$setting['min_deposit'];
						$this->appendToDebugLog('check bets and deposit', [
							'playerId'=>$this->playerId, 'fromDate'=>$fromDate, 'toDate'=>$toDate,
							'bets'=>$bets, 'deposit'=>$deposit,
							'min_bets'=>$min_bets, 'min_deposit'=>$min_deposit,
						]);
						if($bets>=$min_bets){
							if($deposit>=$min_deposit){
								$availableLevelId=$levelId;
								$success=true;
								if(isset($description['contact_live_chat_to_apply']) && $description['contact_live_chat_to_apply']){
                                    $button = '<a href="javascript:void(0)" onclick="' . $this->utils->getLiveChatOnClick() . '">' . lang('ole777_promo.contact_live_chat_to_apply') . '</a>';
                                    $extra_info['contact_live_chat_to_apply'] = $button;
                                }
							}else{
                                $errorMessageLang = 'No enough deposit';
                            }
						}else{
                            $errorMessageLang = 'No enough betting amount';
                        }
						if(!$success){
							$this->appendToDebugLog('ignore level because no', [
								'endAt'=>$endAt, 'maxLevelId'=>$maxLevelId, 'levelId'=>$levelId]);
						}else{
                            //clear
                            $errorMessageLang = null;

							//quit
							$this->appendToDebugLog('found success level');
							break;
						}
					}else{
						$this->appendToDebugLog('not exist this level in setting', [
							'levelId'=>$levelId
						]);
					}
				} // foreach ($levelIdList as $levelId)
			}else{ //if($existLevel){
                $errorMessageLang = 'Not right group level';
				$this->appendToDebugLog('never find exist level');
			}
		}else{
            $errorMessageLang = 'Not right group level';
        }

		return $availableLevelId;
	} // EOF searchAvailableLevelId

	/**
	 * Get the Rows, the Player joined the promo records
	 *
	 * @param string $from The start datetime string of during dates, ex:"2020-03-17 18:12:11"
	 * @param string $to The end datetime string of during dates, ex:"2020-03-17 18:12:11"
	 * @param string $transaction_status_list_str The transaction_status Field for where sentence.
	 * @return array The rows array.
	 */
	public function get_rows_on_this_promo_during_dates($from = null, $to = null, $transaction_status_list_str = null){
		$delimiter = ',';

		//player, promo rule id
		$playerId=$this->playerId;
		$promorulesId=$this->promorule['promorulesId'];

		$this->load->model(['player_promo']);

		if( empty($from) ){
			$from=$this->callHelper('get_date_type', [self::DATE_TODAY_START]);
			//$from=$this->get_date_type(self::DATE_TODAY_START);
		}
		if( empty($to) ){
			$to=$this->callHelper('get_date_type', [self::DATE_TODAY_END]);
			// $to=$this->get_date_type(self::DATE_TODAY_END);
		}

		if( empty($transaction_status_list_str) ){

			$transaction_status_list = [
				Player_promo::TRANS_STATUS_FINISHED_WITHDRAW_CONDITION,
				Player_promo::TRANS_STATUS_APPROVED_WITHOUT_RELEASE_BONUS,
				Player_promo::TRANS_STATUS_APPROVED
			];
			$transaction_status_list_str = implode($delimiter, $transaction_status_list);
		}else{
			$transaction_status_list = explode($delimiter, $transaction_status_list_str);
		}

		$thePlayerDuplicatePromoRowsArray = [];
		$transferSubwalletId = null;
		$playerPromoId = null;
		$playerPromoReleasedCount=$this->player_promo->countPlayerDuplicatePromo($playerId, $promorulesId, $transaction_status_list, $from, $to, $transferSubwalletId, $playerPromoId, $thePlayerDuplicatePromoRowsArray);
// $this->debug_log('debug_log_get thePlayerDuplicatePromoRowsArray: '. var_export($thePlayerDuplicatePromoRowsArray, true));
		return $thePlayerDuplicatePromoRowsArray;
	}// EOF get_rows_on_this_promo_during_dates

	/**
	 * get the referred player_id list by currect player during this month
	 *
	 * @param string $start_date The start date string , ex:"2020-03-14". default, the first day of currect month.
	 * @param string $end_date The end date string , ex:"2020-03-31". default, the last day of currect month.
	 * @return string The playerId list string with delimiter=",".
	 */
	public function get_referred_player_id_list_in_currect_month( $start_date = null, $end_date= null){
		$delimiter = ',';
		// $this->load->library(array('og_utility'));
		$this->load->model(array('player_friend_referral'));
		if( empty($start_date) ){
			$start_date = $this->get_first_date_of_month();
		}
		if( empty($end_date) ){
			$end_date = $this->get_last_date_of_month();
		}
// $start_date ='2018-08-17'; // test
		$rows = $this->player_friend_referral->getReferredByPlayerIdWithDateRange($this->playerId, $start_date, $end_date);
		$referred_player_id_list = $this->callHelper('array_pluck', [$rows, 'invitedPlayerId']);

		// $referred_player_id_list = [];
		$referred_player_id_list_str = implode($delimiter, $referred_player_id_list);
		return $referred_player_id_list_str;
	}// EOf get_referred_player_id_list_in_currect_month

	/**
	 * Get a Counter for The players' total_deposit have greater than $min_amount.
	 *
	 * Total Deposit Amount at "Account Info" tab of SBE > "userInformation".
	 *
	 * @param integer $min_amount the min amount of total_deposit of each player.
	 * @param string $player_id_list_str The string for player_id_list ,  $delimiter is ",".
	 * @return void
	 */
	public function get_counter_by_player_total_deposit_with_min($player_id_list_str, $min_amount) {
		$delimiter = ',';
		$counter=0;
		if($this->process_mock('counter_by_player_total_deposit_with_min', $counter)){
			return $counter;
		}
// // $min_amount = 0; // test
// $this->debug_log('debug_log_get player_id_list_str: '. var_export($player_id_list_str, true));
		$this->load->model(array('transactions'));
		if( ! empty($player_id_list_str) ){
			$player_id_list = explode($delimiter, $player_id_list_str);
		}else{
			$player_id_list = [];
		}
// $this->debug_log('debug_log_get player_id_list: '. var_export($player_id_list, true));
		foreach($player_id_list as $keyNumbe => $curr_player_id){
			$sum = $this->transactions->getPlayerTotalDeposits($curr_player_id);
			if($sum >= $min_amount ){
				$counter++;
			}
		}
// $this->debug_log('debug_log_get counter: '. var_export($counter, true));
		return $this->returnAmount($counter);

	}// EOF get_counter_by_player_total_deposit_with_min

	private function returnAmount($amt) {
		if (!$amt) {
			$amt = 0;
		}

		return $this->utils->roundCurrencyForShow($amt);
	}// EOF returnAmount
}
