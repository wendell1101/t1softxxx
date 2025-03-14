<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

class group_level_lib {


    const ENFORCED_CONDITION_RESULT_REASON_LAST_CHANGED_GEADE_RESET_IF_MET_HAD_MET_LOG = 'Since entering the level, the conditions had been met once.';
    const ENFORCED_CONDITION_RESULT_REASON_LAST_CHANGED_GEADE_RESET_IF_MET_HAD_MET_LOG_IN_MULTIPLE_LEVEL_UPGRADE = 'Under multiple level upgrade, since entering the level, the conditions had been met once.';


    public $detailsOfMethod_in_WithForeachMultipleDBWithoutSuper;

    public function __construct() {
		$this->ci = &get_instance();
		$this->ci->load->model([ 'player_model', 'group_level' ]);
		$this->ci->load->library([ 'utils' ]);

        $this->utils = $this->ci->utils;

        /// key-value, method name VS. details
        // WFMDWS = With Foreach Multiple DB Without Super
        $this->detailsOfMethod_in_WFMDWS = [];
        $this->detailsOfMethod_in_WFMDWS['getPlayerTotalBetWinLossWithForeachMultipleDBWithoutSuper'] = null;


	}

    /**
     * Get the downgrade action by the followings,
     * - Dongrade condition result
     * - Dongrade switch for enable/disable
     * - Downgrade Maintain result
     * - Downgrade Maintain switch for enable/disable
     *
     * @param bool|integer $isConditionMet
     * @param bool|integer $enableDowngrade
     * @param bool|integer $isMet4DownMaintain
     * @param bool|integer $enableDownMaintain
     * @return bool If true, that means do downgrade action; and if its false that means keep current level.
     */
    public function getDowngradeAction($isConditionMet, $enableDowngrade, $isMet4DownMaintain, $enableDownMaintain){
        $doDowngradeAction = false; // Not do downgrade action.
        $caseStr = '';
        $caseStr .= !empty($isConditionMet)? 'Y': 'N';
        $caseStr .= '.';

        $caseStr .= !empty($isMet4DownMaintain)? 'Y': 'N';
        $caseStr .= '.';

        $caseStr .= !empty($enableDowngrade)? 'Y': 'N';
        $caseStr .= '.';

        $caseStr .= !empty($enableDownMaintain)? 'Y': 'N';
        $caseStr .= '.';
        $this->utils->debug_log('OGP-27288.34.caseStr', $caseStr);
        switch ($caseStr){
            // 0,0,0,0
            case 'N.N.N.N.':
            break;

            // 0,0,0,1
            case 'N.N.N.Y.':
                $doDowngradeAction = true;
            break;

            // 0,0,1,0
            case 'N.N.Y.N.':
            break;

            // 0,0,1,1
            case 'N.N.Y.Y.':
                $doDowngradeAction = true;
            break;

            // 0,1,0,0
            case 'N.Y.N.N.':
            break;

            // 0,1,0,1
            case 'N.Y.N.Y.':
            break;

            // 0,1,1,0
            case 'N.Y.Y.N.':
            break;

            // 0,1,1,1
            case 'N.Y.Y.Y.':
            break;

            // 1,0,0,0
            case 'Y.N.N.N.':
            break;
            // 1,0,0,1
            case 'Y.N.N.Y.':
                $doDowngradeAction = true;
            break;
            // 1,0,1,0
            case 'Y.N.Y.N.':
                $doDowngradeAction = true;
            break;
            // 1,0,1,1
            case 'Y.N.Y.Y.':
                $doDowngradeAction = true;
            break;
            // 1,1,0,0
            case 'Y.Y.N.N.':
            break;
            // 1,1,0,1
            case 'Y.Y.N.Y.':
            break;
            // 1,1,1,0
            case 'Y.Y.Y.N.':
                $doDowngradeAction = true;
            break;
            // 1,1,1,1
            case 'Y.Y.Y.Y.':
            break;
        }
        return $doDowngradeAction;
    }// EOF getDowngradeAction

    /**
     * Insert Or Update the data into the table, player_accumulated_amounts_log.
     * The data will be the followings
     * - The accumulated total bet between date times.
     * - The accumulated deposit between date times.
     *
     * @param integer $player_id The field, "player.playerId".
     * @param float|array $gameLogData4bet The Total Bet amount for floating type. The separate bet in array type, but Not yet supported.
     * @param array $bet_daterange The date time range for the accumulated Total Bet. The key,"from" means the begin date time, and the key,"to" means the end date time.
     * @param float $deposit The deposit amount.
     * @param array $deposit_daterange The date time range for the accumulated deposit. The key,"from" means the begin date time, and the key,"to" means the end date time.
     * @param string $time_exec_begin The specified time for the fields,"created_at" and "updated_at".
     * @return array The PK. id of the inserted / effected data.
     */
    public function syncInPlayerAccumulatedAmountsLog(  $player_id // #1
                                                        , $gameLogData4bet // #2
                                                        , $bet_daterange // #3
                                                        , $deposit // #4
                                                        , $deposit_daterange // #5
                                                        , $time_exec_begin = 'now' // #6
    ){
        $this->ci->load->model([ 'player_accumulated_amounts_log' ]);

        $return = [];
        $return[Player_accumulated_amounts_log::ACCUMULATED_TYPE_BET] = [];
        $return[Player_accumulated_amounts_log::ACCUMULATED_TYPE_DEPOSIT] = [];

        // for bet
        if( is_numeric($gameLogData4bet) ){ // for CB
            $amount = $gameLogData4bet;
            $accumulated_type = Player_accumulated_amounts_log::ACCUMULATED_TYPE_BET;
            $query_token = Player_accumulated_amounts_log::QUERY_TOKEN_BET_AMOUNT;
            $begin_datetime = $bet_daterange['from'];
            $end_datetime = $bet_daterange['to'];
            $return[Player_accumulated_amounts_log::ACCUMULATED_TYPE_BET] = $this->ci->player_accumulated_amounts_log->log_accumulated_amount($player_id, $amount, $accumulated_type, $query_token, $begin_datetime, $end_datetime, null, $time_exec_begin);
        }else{
            // @todo SB
        }


        // for deposit
        $amount = $deposit;
        $accumulated_type = Player_accumulated_amounts_log::ACCUMULATED_TYPE_DEPOSIT;
        $query_token = Player_accumulated_amounts_log::QUERY_TOKEN_DEPOSIT_AMOUNT;
        $begin_datetime = $deposit_daterange['from'];
        $end_datetime = $deposit_daterange['to'];
        $return[Player_accumulated_amounts_log::ACCUMULATED_TYPE_DEPOSIT] = $this->ci->player_accumulated_amounts_log->log_accumulated_amount($player_id, $amount, $accumulated_type, $query_token, $begin_datetime, $end_datetime, null, $time_exec_begin);
        return $return;
    } // EOF syncInPlayerAccumulatedAmountsLog


    /**
     * Get the latest VIP Grade changed time
     *
     * @param integer $playerId
     * @param string $queryBeginDatetime The begin date time of search,format: "Y:m:d H:i:s". Usually be the register date time of the player.
     * @param string $queryEndDatetime The end date time of search,format: "Y:m:d H:i:s". Usually be now.
     * @param array $excluded_vip_grade_report_id_list The pk field, "id" will be excluded in where condition.
     *
     * @return string The data time of the latest VIP Grade changed.
     */
    public function queryLastGradeTimeByPlayerId($playerId, $queryBeginDatetime = '', $queryEndDatetime = '', $excluded_vip_grade_report_id_list = []){

$this->utils->debug_log('103.queryLastGradeTimeByPlayerId.param', $playerId, $queryBeginDatetime, $queryEndDatetime, $excluded_vip_grade_report_id_list);
        $this->ci->load->model([ 'player_model', 'vip_grade_report' ]);
        $time_exec_beginDT = new DateTime($queryEndDatetime);
        $playerDetails = $this->ci->player_model->getPlayerDetailsById($playerId);

        $lastGradeDatetime = $playerDetails->createdOn;// default, from registaction for into vip1 first

        // queryBeginDatetime, queryEndDatetime
        if( empty($queryBeginDatetime) ){
            $queryBeginDatetime = $playerDetails->createdOn;
        } // vip_grade_report


        $theLastGradeRecordRow = $this->ci->vip_grade_report->queryLastGradeRecordRowBy( $playerId // #1
                                                                                        , $queryBeginDatetime // #2
                                                                                        , $this->utils->formatDateTimeForMysql($time_exec_beginDT) // #3
                                                                                        , 'upgrade_or_downgrade' // #4
                                                                                        , 'pgrm_end_time' // #5, as default
                                                                                        , false // #6, as default
                                                                                        , $excluded_vip_grade_report_id_list // #7
                                                                                    );
$this->utils->debug_log('103.queryLastGradeRecordRowBy.param', $playerId // #1
                            , $queryBeginDatetime // #2
                        );
$this->utils->debug_log('103.queryLastGradeRecordRowBy.theLastGradeRecordRow', $theLastGradeRecordRow);
        if( ! empty($theLastGradeRecordRow) ){
            $lastGradeDatetime = $theLastGradeRecordRow['pgrm_end_time'];
        }
        return $lastGradeDatetime;
    } // EOF queryLastGradeTimeByPlayerId


    /**
     * Get the details of enforced Condition result
     *
     * @param bool $isMet The result of the enforced condition met or not. Its in the key,"isMet" of the return array.
     * @param string $reason The enforced condition reason for easier to know. Its in the key,"reason" of the return array.
     * @param array $reasonDetails The details in the key,"details" of the return array.
     * @return array  The return array.
     */
    public function getEnforcedConditionResultDetail($isMet, $reason = '', $reasonDetails = [] ){
        $enforceDetail = [];
        $enforceDetail['isMet'] = $isMet;
        $enforceDetail['reason'] = $reason;
        $enforceDetail['details'] = $reasonDetails;
        return $enforceDetail;
    } // EOF getEnforcedConditionResultDetail



    /**
     * search the keyword in the value of the array
     *
     * @param string $keyword The keyword string, ex: "bet_amount".
     * @param array $parsedFormula The array with string values.
     * @return void
     */
    public function searchKeywordInParsedFormula($keyword, $parsedFormula = []){
        $returnIndexKey = null;
        foreach($parsedFormula as $indexKey => $currentValue){
            if(strpos( $currentValue, $keyword) !== false){
                $returnIndexKey = $indexKey;
            }
        }
        return $returnIndexKey;
    } // EOF searchKeywordInParsedFormula

    /**
     * Append the boolean string into the amount condition then wrapper it.
     *
     * @param string $amountCondition
     * @param string $amount_name
     * @param string $appendToBool
     * @param string $preFlag4appendToBool
     * @return string
     */
    public function wrapAppendedBoolInAmountCondition( $amountCondition = 'and deposit_amount >= 999999999'
                                                    , $amount_name='deposit_amount'
                                                    ,  $appendToBool = 'true'
                                                    , $preFlag4appendToBool = '||'
    ){
        $_pattern = '/[and|or ]?('. $amount_name. '\s?[>=<]{1,2}\s?\S+)/i'; // ref. to https://regex101.com/r/aZMUUx/1
        if( strtolower($appendToBool) == 'true'){
            $_replacement = '( ${1} '. $preFlag4appendToBool. ' '. $appendToBool. ' )'; // always be true
        }else if( strtolower($appendToBool) == 'false'){
            $_replacement = '( ${1} '. $preFlag4appendToBool. ' '. $appendToBool. ' )'; // always be false
        }else{
            $_replacement = '( ${1} '. $preFlag4appendToBool. ' '. $appendToBool. ' )';
        }
        return preg_replace($_pattern, $_replacement, $amountCondition);
    } // wrapAndAppendBoolInAmountCondition


    /**
     * Get the Meted data in player_accumulated_amounts_log with the params,
     *
     *
     * @param integer $vipSettingId The field, "vipsettingcashbackrule.vipsettingcashbackruleId".
     * @param integer $playerId The field, "player.playerId" .
     * @param string $accumulated_type The accumulated iten, ex: Player_accumulated_amounts_log::ACCUMULATED_TYPE_BET.
     * @param string $beginDatetine The datetime for condition of mysql.
     * @param string $endDatetime The datetime for condition of mysql.
     * @return array $meted_log_row The row of player_accumulated_amounts_log. if its not found and return the empty array.
     */
    public function getMetedRowInAccumulatedAmountsLogWithParams($vipSettingId, $playerId, $accumulated_type, $beginDatetine, $endDatetime){
        $this->ci->load->model([ 'player_accumulated_amounts_log' ]);
        $meted_log_row = [];

        $query_token = sprintf(Player_accumulated_amounts_log::QUERY_TOKEN_IN_LEVEL, $vipSettingId);

        $meted_log_rows = $this->ci->player_accumulated_amounts_log->getDetailListByPlayerIdAndQueryToken($playerId, $query_token, $accumulated_type, $beginDatetine, $endDatetime, Player_accumulated_amounts_log::IS_MET_YES);
            // $this->utils->debug_log('OGP-24373.226.meted_log_rows:', $meted_log_rows);
        $is_meted = null;
        if( ! empty($meted_log_rows) ){ // convert integer is_met to bool
            $is_meted = ($meted_log_rows[0]['is_met'] == Player_accumulated_amounts_log::IS_MET_YES)? true: false;
        }
        if( $is_meted ){
            $meted_log_row = $meted_log_rows[0];
        }
        return $meted_log_row;
    } // EOF getMetedRowInAccumulatedAmountsLogWithParams


    /**
     * get the datatime by request time
     *
     * @param string $request_time
     * @param integer $delay_sec
     * @return string The date time for mysql. The format, "Y-m-d H:i:s"
     */
    public function get_pgrm_time_by_request_time($request_time = 'now', $delay_sec = 0){
        $_pgrm_timeDT = new DateTime($request_time);
        if( ! empty($delay_sec) ){
            $_pgrm_timeDT->modify('+'.$delay_sec.' sec');
        }
        $_pgrm_time = $this->utils->formatDateTimeForMysql($_pgrm_timeDT);
        return $_pgrm_time;
    }// EOF get_pgrm_time_by_request_time


    /**
     * Get the begin date time or the progress bar in player site
     *
     * The begin date time will get by the following rules,
     * 1. the previous period but latest changes moment  first.
     * 2. If result is not met, it will to search for records that have been completed.
     * Then return the begin date time of the completed record.
     *
     * @param integer $playerId The player.player_id
     * @return string The begin datetime.
     */
    public function _getBeginDatetimeInDepositWithAccumulationModeLastChangedGeadeResetIfMet($playerId){
		$this->ci->load->model(array('group_level','player_accumulated_amounts_log'));

		$returnBeginDatetime = null;

		$playerDetails = $this->ci->player_model->getPlayerDetailsById($playerId);

		//get player current level details
		$vipSettingId = $this->ci->group_level->getPlayerLevelId($playerId);
		$getPlayerCurrentLevelDetails = $this->ci->group_level->getVipGroupLevelDetails($vipSettingId);
		// get vip_upgrade_setting for upgrade
		$setting = $this->ci->group_level->getSettingData($getPlayerCurrentLevelDetails['vip_upgrade_id']); // vip_upgrade_setting
		$formula = json_decode($setting['formula'], true);

		$now = new DateTime();
        // $now->modify('+6 days'); // for OGP-24714 TEST
		$time_exec_begin = $now->format('Y-m-d H:i:s');
		//
		$schedule = json_decode($getPlayerCurrentLevelDetails['period_up_down_2'], true); // vipsettingcashbackrule.period_up_down_2 in upgrade

        // Get the latest changed date time.
		$queryBeginDatetime = '';
		$queryEndDatetime = $time_exec_begin;
		$lastGradeDatetime = $this->queryLastGradeTimeByPlayerId($playerId, $queryBeginDatetime, $queryEndDatetime);

        /// Get the latest period includes now, and latest changed first
        $first = $lastGradeDatetime;
        $last = $time_exec_begin;
        $isIncludeLatestRange = true;
        $periodType = array_keys($schedule)[0];
        $dateRanges = $this->utils->dateRangeByPeriod($first, $last, $periodType, $isIncludeLatestRange);
        $dateRangesCounter = count($dateRanges);
        $lastIndex = $dateRangesCounter-1;
        if( ! empty( $dateRanges[$lastIndex] ) ){
            $fromDatetime = $dateRanges[$lastIndex]["from"];// get the
            if( ! empty($dateRanges[$lastIndex]["first"]) ){
                $fromDatetime = $dateRanges[$lastIndex]["first"]; // override for last changed
            }
            $toDatetime = $dateRanges[$lastIndex]["to"];// get the
            if( ! empty($dateRanges[$lastIndex]["last"]) ){
                $toDatetime = $dateRanges[$lastIndex]["last"];// override for now
            }
        }
        $this->utils->debug_log('OGP-24714.273.dateRanges', $dateRanges, 'lastIndex', $lastIndex, 'fromDatetime', $fromDatetime, 'toDatetime', $toDatetime);

		$_deposit = null;
		$_remarkInMacro = null;
		$_isConditionMet = $this->ci->group_level->_macroGetTotalDepositWithdrawalBonusCashbackByPlayersWithFormulaAndReturnIsMet($playerId, $fromDatetime, $toDatetime, $formula, $_remarkInMacro, $_deposit);
		// $deposit = $_deposit; // as 1st check amount.
		$returnBeginDatetime = $fromDatetime;
        // $this->utils->debug_log('OGP-24714.272.returnBeginDatetime', $returnBeginDatetime, '_isConditionMet:', $_isConditionMet, '_remarkInMacro:', $_remarkInMacro, '_deposit:', $_deposit);
		if( ! $_isConditionMet ){
			$beginDatetine = $lastGradeDatetime;
			$endDatetime = $time_exec_begin; // disable for patch the issue. Not found the meted condition log by simulate current time.
			// $now = new DateTime();
			$accumulated_type = Player_accumulated_amounts_log::ACCUMULATED_TYPE_DEPOSIT;
			$endDatetime = $this->ci->utils->formatDateTimeForMysql(new DateTime($time_exec_begin));
			$query_token = sprintf(Player_accumulated_amounts_log::QUERY_TOKEN_IN_LEVEL, $vipSettingId);
			$is_met = Player_accumulated_amounts_log::IS_MET_YES;
			$meted_log_rows = $this->ci->player_accumulated_amounts_log->getDetailListByPlayerIdAndQueryToken($playerId, $query_token, $accumulated_type, $beginDatetine, $endDatetime, $is_met);
            if( ! empty($meted_log_rows) ){
				/// 若有曾經滿足。。。
				$deposit = $meted_log_rows[0]['amount'];
				$returnBeginDatetime = $meted_log_rows[0]['begin_datetime'];
                // $this->utils->debug_log('OGP-24714.286.returnBeginDatetime', $returnBeginDatetime);
			}
		}
        $this->utils->debug_log('OGP-24714.310.returnBeginDatetime', $returnBeginDatetime);
		return $returnBeginDatetime;
	} // EOF _getBeginDatetimeInDepositWithAccumulationModeLastChangedGeadeResetIfMet()

    /**
     * Get the begin date time or the progress bar in player site
     *
     * The begin date time will get by the following rules,
     * 1. the previous period but latest changes moment  first.
     * 2. If result is not met, it will to search for records that have been completed.
     * Then return the begin date time of the completed record.
     *
     * @param integer $playerId The player.player_id
     * @return string The begin datetime.
     */
    public function _getBeginDatetimeInBetWithAccumulationModeLastChangedGeadeResetIfMet($playerId){
        $this->ci->load->model(array('group_level','player_accumulated_amounts_log'));

		$playerDetails = $this->ci->player_model->getPlayerDetailsById($playerId);

        //get player current level details
        $vipSettingId = $this->ci->group_level->getPlayerLevelId($playerId);
        $getPlayerCurrentLevelDetails = $this->ci->group_level->getVipGroupLevelDetails($vipSettingId);
        // get vip_upgrade_setting for upgrade
        $setting = $this->ci->group_level->getSettingData($getPlayerCurrentLevelDetails['vip_upgrade_id']); // vip_upgrade_setting
        $formula = json_decode($setting['formula'], true);

		$now = new DateTime();
        // $now->modify('+6 days'); // for OGP-24714 TEST
		$time_exec_begin = $now->format('Y-m-d H:i:s');

		$schedule = json_decode($getPlayerCurrentLevelDetails['period_up_down_2'], true); // vipsettingcashbackrule.period_up_down_2 in upgrade

        // Get the latest changed date time.
        $queryBeginDatetime = '';
        $queryEndDatetime = $time_exec_begin;
        $lastGradeDatetime = $this->queryLastGradeTimeByPlayerId($playerId, $queryBeginDatetime, $queryEndDatetime);

        /// Get the latest period includes now, and latest changed first
        $first = $lastGradeDatetime;
        $last = $time_exec_begin;
        $isIncludeLatestRange = true;
        $periodType = array_keys($schedule)[0];
        $dateRanges = $this->utils->dateRangeByPeriod($first, $last, $periodType, $isIncludeLatestRange);
        $dateRangesCounter = count($dateRanges);
        $lastIndex = $dateRangesCounter-1;
        if( ! empty( $dateRanges[$lastIndex] ) ){
            $fromDatetime = $dateRanges[$lastIndex]["from"];// get the
            if( ! empty($dateRanges[$lastIndex]["first"]) ){
                $fromDatetime = $dateRanges[$lastIndex]["first"]; // override for last changed
            }
            $toDatetime = $dateRanges[$lastIndex]["to"];// get the
            if( ! empty($dateRanges[$lastIndex]["last"]) ){
                $toDatetime = $dateRanges[$lastIndex]["last"];// override for now
            }
        }
        $this->utils->debug_log('OGP-24714.372.dateRanges', $dateRanges, 'lastIndex', $lastIndex, 'fromDatetime', $fromDatetime, 'toDatetime', $toDatetime);

		$_gameLogData = null;
		$_remarkInMacro = null;
		$settingInfoHarshInUpGraded = [];
		$settingInfoHarshInUpGraded['total_deposit'] = true;
		$settingInfoHarshInUpGraded['total_bet'] = true;
		$_isConditionMet = $this->ci->group_level->_macroGetPlayerTotalBetWinLossByScheduleWithFormulaAndReturnIsMet($playerId // #1
                                                                                                                    , $fromDatetime // #2
                                                                                                                    , $toDatetime // #3
                                                                                                                    , $formula // #4
                                                                                                                    , 'up' // #5
                                                                                                                    , $_remarkInMacro // #6
                                                                                                                    , $_gameLogData // #7
                                                                                                                    , $settingInfoHarshInUpGraded // #8
                                                                                                                );
		// $total_bet = $_gameLogData['total_bet']; // as 1st check amount.
        $returnBeginDatetime = $fromDatetime;
		if( ! $_isConditionMet ){
			$beginDatetine = $lastGradeDatetime;
			$accumulated_type = Player_accumulated_amounts_log::ACCUMULATED_TYPE_BET;
			$endDatetime = $this->utils->formatDateTimeForMysql(new DateTime($time_exec_begin));

			// $meted_log_rows = [];
			$meted_log_row = $this->getMetedRowInAccumulatedAmountsLogWithParams($vipSettingId, $playerId, $accumulated_type, $beginDatetine, $endDatetime);
			if( ! empty($meted_log_row) ){
                /// 若有曾經滿足。。。
                $returnBeginDatetime = $meted_log_row['begin_datetime'];
			}
		}
        $this->utils->debug_log('OGP-24714.394.returnBeginDatetime', $returnBeginDatetime);
        return $returnBeginDatetime;
    }// EOF _getBeginDatetimeInBetWithAccumulationModeLastChangedGeadeResetIfMet
    /**
     * The upgraded info, the current level, new vip level id and setting
     *
     * @param integer $newVipLevelId The level id of the met the condition in the level.
     * @param array $playerLevel The level row, that is met the condition.
     * @param array $setting The setting of the level.
     * @return array The upgraded info array, its for report.
     */
    public function build_upgraded_info($newVipLevelId, $playerLevel, $setting){
        $_upgraded_info = [];
        $_upgraded_info['newvipId'] = $newVipLevelId; // VIP Level Id, upgraded

        if( ! empty($playerLevel['vipsettingcashbackruleId']) ){
            $_upgraded_info['vipsettingcashbackruleId'] = $playerLevel['vipsettingcashbackruleId']; // VIP Level Id, before check upgrade condition
            $_upgraded_info['vipGroupName'] = $playerLevel['groupName'];  // VIP Group Name,
            $_upgraded_info['vipLevelName'] =$playerLevel['vipLevelName'];  // VIP Level Name,
            $_upgraded_info['vipsettingId'] = $playerLevel['vipSettingId']; // VIP Group Id for debug.
        }else{
            $this->utils->error_log('Empty $playerLevel had referenced.'); // issue: unrelated vipsettingcashbackruleId used by vipsetting::getVipGroupLevelDetails().
        }


        $_upgraded_info['setting_name'] = '';
        $_upgraded_info['formula'] = '{}';
        $_upgraded_info['bet_amount_in_formula'] = [];
        $_upgraded_info['deposit_amount_in_formula'] = [];
        if( ! empty($setting) ){ // vip_upgrade_setting
            $_upgraded_info['setting_name'] = $setting['setting_name']; // The setting name of the VIP Level
            $_upgraded_info['formula'] = $setting['formula']; // The formula in the setting of the VIP Level
            $_upgraded_formula = json_decode($setting['formula'], true);
            if( ! empty($_upgraded_formula['bet_amount']) ){ // Patch the E_NOTICE: Undefined index: bet_amount
                $_upgraded_info['bet_amount_in_formula'] = $_upgraded_formula['bet_amount'];  // The bet_amount of formula, logical symbols and limits.
            }
            if( ! empty($_upgraded_formula['deposit_amount']) ){
                $_upgraded_info['deposit_amount_in_formula'] = $_upgraded_formula['deposit_amount'];  // The deposit_amount of formula, logical symbols and limits.
            }
        }else{
            $this->utils->error_log('Empty $setting had referenced.'); // issue: unrelated vip_upgrade_id used by group_level::getSettingData().
        }

        return $_upgraded_info;
    } // EOF build_upgraded_info()

    /**
     * From the array, extract the element of the specified key name
     *
     * @param array $calcResult
     * @param string $path The specified key name. It also be the multi-levels please contains the flag string, $delimiter .
     * @param string $delimiter The delimiter string.
     * @return array The result array format as,
     * - $results['bool'] bool If it is success, return true. default is NULL.
     * - $results['extracted'] mixed The extracted element.
     * - $results['referredPath'] string The the specified key name in extracted.
     */
    public function extractCurrConditionDetails($calcResult, $path = 'details.total_bet.enforcedDetails.details.curr_condition_details', $delimiter = '.'){

        $results = [];
        $extracted = null;
        $resultBool = null;
        $referredPathStr = '';
        // $path = 'details.total_bet.enforcedDetails.details.curr_condition_details';
        $pathArray = explode($delimiter, $path);
        if( ! empty($pathArray) ){
            foreach($pathArray as $pathStr){
                if( is_null($extracted) ){
                    if( isset($calcResult[$pathStr]) ){
                        $extracted = $calcResult[$pathStr];
                    }else{
                        $resultBool = false;
                    }
                }else{
                    if( isset($extracted[$pathStr]) ){
                        $extracted = $extracted[$pathStr];
                    }else{
                        $resultBool = false;
                    }
                }
                if($resultBool === false ){
                    break; // exit
                }else{
                    if( empty($referredPathStr) ){
                        $referredPathStr = $pathStr;
                    }else{
                        $referredPathStr = $referredPathStr. $delimiter. $pathStr;
                    }
                }
            } // EOF foreach($pathArray as $pathStr){...
            if($referredPathStr == $path ){
                $resultBool = true;
            }else{
                $resultBool = false;
            }
        }else{
            $resultBool = false;
        }
        $results['bool'] = $resultBool;
        $results['extracted'] = $extracted;
        $results['referredPath'] = $referredPathStr;
        return $results;
    }// EOF extractCurrConditionDetails

    public function getPlayerTotalDepositsWithForeachMultipleDBWithoutSuper(){
        $this->ci->load->model(['multiple_db_model', 'transactions']);
        // collect the args of method().
        $all_args = $this->func_get_all_args('Transactions::getPlayerTotalDeposits', func_get_args());

        $multi_currencies_rate_list = $this->get_multi_currencies_rate_list();

        $sourceDB = $this->utils->getActiveTargetDB();
        $readonly = true;
        $_this = $this;
        $_rlt_list = $this->ci->multiple_db_model->foreachMultipleDBWithoutSuper( function($db, &$rlt) use ( $_this, $all_args, $multi_currencies_rate_list){ // callback

            /// aka. $_this->ci->transactions->getPlayerTotalDeposits()
            // the arguments assign by caller of the funciton, group_level_lib::getPlayerTotalsByPlayersWithForeachMultipleDBWithoutSuper()
            // replace to current $db of multiple_db_model::foreachMultipleDBWithoutSuper().
            if ($all_args[3] === null || true) { // forced replacement
                $all_args[3] = $db; // #4
            }
            $rlt = call_user_func_array([$_this->ci->transactions, 'getPlayerTotalDeposits'], $all_args);
            return true; // success

        }, $readonly); // EOF $_rlt_list = $this->ci->multiple_db_model->foreachMultipleDBWithoutSuper(...

        // peeling suffix of database name, like as XXX_readonly to XXX.
        foreach($_rlt_list as $_db => $_rlt){
            $_remove_suffix_db = str_replace('_readonly', '', $_db);
            if($_remove_suffix_db != $_db){ // has appended suffix
                $_rlt_list[ $_remove_suffix_db ] = $_rlt;

                // clear for duplicate data
                $_rlt_list[ $_db ] = [];
                unset($_rlt_list[ $_db ]);
            }
        }

        /// multi-currencies total action, ex:
        // - BRL to USD
        // - PHP to USD
        // - USD
        $_multi_currencies_rate_details = [];
        foreach($_rlt_list as $_currency_key => $_rlt){
            // $_rlt['success']; // boolean
            // $_rlt['result']; // amount

            $_rate = null;

            $_rate_details = [];
            $_rate_details['base'] = $_currency_key;
            $_rate_details['target'] = $sourceDB;

            $orig_totals = 0;
            if( ! empty( $_rlt['result'] ) ){
                $orig_totals = $_rlt['result'];
            }

            $_converted = 0;
            if($_rlt['success']){
                $_rate = $multi_currencies_rate_list[$_currency_key][$sourceDB];
                if($_rate === null){
                    /// Not found rate by get_multi_currencies_rate_list().
                    // $_rate=null, thats  to Zero
                    $this->utils->error_log('OGP-31861.645.rate Not found, base:', $_currency_key, 'target:', $sourceDB);
                }

                if( ! empty( $_rlt_list[$_currency_key]['result'] ) ){
                    $_converted += $_rlt_list[$_currency_key]['result']* $_rate;
                }
            } // EOF if($_rlt_list[$_currency_key]['success']){...

            $_rate_details['orig_totals'] = $orig_totals;
            $_rate_details['rate'] = $_rate;
            $_rate_details['converted'] = $_converted;

            array_push($_multi_currencies_rate_details, $_rate_details);
        } // EOF foreach($_rlt_list as $_currency_key => $_rlt){...
        //
        /// totals all multi-currencies
        $converted_totals = 0;
        foreach($_multi_currencies_rate_details as $indexNumber => $_details){
            $converted_totals += $_details['converted'];
        }
        $_rlt_list['_multi_currencies_rate_details'] = $_multi_currencies_rate_details; // multi-currencies rate details
        $_rlt_list['_converted_totals'] = $converted_totals; /// it will be return
        // WFMDW = WithForeachMultipleDBWithoutSuper
        $this->detailsOfMethod_in_WFMDWS['getPlayerTotalDepositsWithForeachMultipleDBWithoutSuper'] = $_rlt_list; // for trace issue
        $this->utils->debug_log('OGP-31861.668._rlt_list', $_rlt_list); // details

        return $converted_totals;
    } // EOF getPlayerTotalDepositsWithForeachMultipleDBWithoutSuper

    public function getPlayerTotalsByPlayersWithForeachMultipleDBWithoutSuper(){
        $this->ci->load->model(['multiple_db_model', 'transactions']);
        // collect the args of method().
        $all_args = $this->func_get_all_args('Transactions::getPlayerTotalsByPlayers', func_get_args());

        $multi_currencies_rate_list = $this->get_multi_currencies_rate_list();

        $sourceDB = $this->utils->getActiveTargetDB();
        $readonly = true;
        $_this = $this;
        $_rlt_list = $this->ci->multiple_db_model->foreachMultipleDBWithoutSuper( function($db, &$rlt) use ( $_this, $all_args, $multi_currencies_rate_list){ // callback

                /// aka. $_this->ci->transactions->getPlayerTotalsByPlayers()
                // the arguments assign by caller of the funciton, group_level_lib::getPlayerTotalsByPlayersWithForeachMultipleDBWithoutSuper()
                // replace to current $db of multiple_db_model::foreachMultipleDBWithoutSuper().
                if ($all_args[3] === null || true) { // forced replacement
                    $all_args[3] = $db; // #4
                }
                $rlt = call_user_func_array([$_this->ci->transactions, 'getPlayerTotalsByPlayers'], $all_args);
                return true; // success

        }, $readonly); // EOF $_rlt_list = $this->ci->multiple_db_model->foreachMultipleDBWithoutSuper(...

        // peeling suffix of database name, like as XXX_readonly to XXX.
        foreach($_rlt_list as $_db => $_rlt){
            $_remove_suffix_db = str_replace('_readonly', '', $_db);
            if($_remove_suffix_db != $_db){ // has appended suffix
                $_rlt_list[ $_remove_suffix_db ] = $_rlt;

                // clear for duplicate data
                $_rlt_list[ $_db ] = [];
                unset($_rlt_list[ $_db ]);
            }
        }

        /// multi-currencies total action, ex:
        // - BRL to USD
        // - PHP to USD
        // - USD
        $_multi_currencies_rate_details = [];
        $_totals[] = [];
        foreach($_rlt_list as $_currency_key => $_rlt){
            // reset $_totals, from $_rlt['result'];
            // $_rlt['result']; // array, $transType => $amount
            if($_rlt['success']){
                foreach($_rlt['result'] as $transType => $amount){
                    $_totals[$transType] = 0;
                }
            }



            $_rate = null;

            $_rate_details = [];
            $_rate_details['base'] = $_currency_key;
            $_rate_details['target'] = $sourceDB;

            $orig_totals = [];
            if( ! empty( $_rlt['result'] ) ){
                $orig_totals = $_rlt['result'];
            }

            if($_rlt['success']){
                $_rate = $multi_currencies_rate_list[$_currency_key][$sourceDB];
                if($_rate === null){
                    /// Not found rate by get_multi_currencies_rate_list().
                    // $_rate=null, thats  to Zero
                    $this->utils->error_log('OGP-31861.658.rate Not found, base:', $_currency_key, 'target:', $sourceDB);
                }

                if( ! empty( $_rlt_list[$_currency_key]['result'] ) ){
                    foreach($_rlt['result'] as $transType => $amount){
                        $_totals[$transType] += $amount * $_rate;
                    }
                }

            } // EOF if($_rlt_list[$_currency_key]['success']){...

            $_rate_details['rate'] = $_rate;
            $_rate_details['converted'] = [];
            foreach($_rlt['result'] as $transType => $amount){
                // $_totals[$transType] += $amount * $_rate;
                $_rate_details['converted'][$transType] = $_totals[$transType];
                $_rate_details[$transType] = $orig_totals[ $transType];
            }
            // $_rate_details['converted_total_bet'] = $_total_bet;
            // $_rate_details['total_bet'] = $orig_total_bet;
            array_push($_multi_currencies_rate_details, $_rate_details);
        } // EOF foreach($_rlt_list as $_currency_key => $_rlt){...
        //
        /// totals all multi-currencies
        $converted_totals = [];
        foreach($_multi_currencies_rate_details as $indexNumber => $_details){
            foreach($_details['converted'] as $_transType => $_converted_amount){
                if( ! isset($converted_totals[$_transType]) ){
                    $converted_totals[$_transType] = 0;
                }
            }

            foreach($_details['converted'] as $_transType => $_converted_amount){
                $converted_totals[$_transType] += $_converted_amount;
            }
        }

        $_rlt_list['_multi_currencies_rate_details'] = $_multi_currencies_rate_details; // multi-currencies rate details
        $_rlt_list['_converted_totals'] = $converted_totals; /// it will be return
        // WFMDWS = WithForeachMultipleDBWithoutSuper
        $this->detailsOfMethod_in_WFMDWS['getPlayerTotalsByPlayersWithForeachMultipleDBWithoutSuper'] = $_rlt_list; // for trace issue
        $this->utils->debug_log('OGP-31861.703._rlt_list', $_rlt_list); // details

        return $converted_totals;
    } // EOF getPlayerTotalsByPlayersWithForeachMultipleDBWithoutSuper

    /**
     * Execute getPlayerTotalBetWinLoss wrapped with foreachMultipleDBWithoutSuper()
     *
     * The params are depend on total_player_game_day::getPlayerTotalBetWinLoss()
     *
     * @return array The structure should be the same as the method, total_player_game_day::getPlayerTotalBetWinLoss().
     */
    public function getPlayerTotalBetWinLossWithForeachMultipleDBWithoutSuper(){
        $this->ci->load->model(['multiple_db_model', 'total_player_game_day']);

        // collect the args of method().
        $all_args = $this->func_get_all_args('Total_player_game_day::getPlayerTotalBetWinLoss', func_get_args());

        $multi_currencies_rate_list = $this->get_multi_currencies_rate_list();

        $sourceDB = $this->utils->getActiveTargetDB();
        $readonly = true;
        $_this = $this;
        $_rlt_list = $this->ci->multiple_db_model->foreachMultipleDBWithoutSuper( function($db, &$rlt)
            use ( $_this, $all_args, $multi_currencies_rate_list){ // callback

                /// aka. $_this->ci->total_player_game_day->getPlayerTotalBetWinLoss()
                // the arguments assign by caller of the funciton, group_level_lib::getPlayerTotalBetWinLossWithForeachMultipleDBWithoutSuper()
                // replace to current $db of multiple_db_model::foreachMultipleDBWithoutSuper().
                if ($all_args[7] === null || true) { // forced replacement
                    $all_args[7] = $db; // #8
                }
                $rlt = call_user_func_array([$_this->ci->total_player_game_day, 'getPlayerTotalBetWinLoss'], $all_args);
                return true; // success

        }, $readonly); // EOF $_rlt_list = $this->ci->multiple_db_model->foreachMultipleDBWithoutSuper(...

        // peeling suffix of database name, like as XXX_readonly to XXX.
        foreach($_rlt_list as $_db => $_rlt){
            $_remove_suffix_db = str_replace('_readonly', '', $_db);
            if($_remove_suffix_db != $_db){ // has appended suffix
                $_rlt_list[ $_remove_suffix_db ] = $_rlt;

                // clear for duplicate data
                $_rlt_list[ $_db ] = [];
                unset($_rlt_list[ $_db ]);
            }
        }

        /// multi-currencies total action, ex:
        // - BRL to USD
        // - PHP to USD
        // - USD
        $_multi_currencies_rate_details = [];
        foreach($_rlt_list as $_currency_key => $_rlt){
            $_total_bet = 0;
            $_total_win = 0;
            $_total_loss = 0;
            $_rate = null;

            $_rate_details = [];
            $_rate_details['base'] = $_currency_key;
            $_rate_details['target'] = $sourceDB;

            $orig_total_bet = 0;
            if( ! empty( $_rlt_list[$_currency_key]['result']['total_bet'] ) ){
                $orig_total_bet = $_rlt_list[$_currency_key]['result']['total_bet'];
            }
            $orig_total_win = 0;
            if( ! empty( $_rlt_list[$_currency_key]['result']['total_win'] ) ){
                $orig_total_win = $_rlt_list[$_currency_key]['result']['total_win'];
            }
            $orig_total_loss = 0;
            if( ! empty( $_rlt_list[$_currency_key]['result']['total_loss'] ) ){
                $orig_total_loss = $_rlt_list[$_currency_key]['result']['total_loss'];
            }

            if($_rlt_list[$_currency_key]['success']){
                $_rate = $multi_currencies_rate_list[$_currency_key][$sourceDB];
                if($_rate === null){

                    /// Not found rate by get_multi_currencies_rate_list().
                    // $_rate=null, thats  to Zero
                    $this->utils->error_log('OGP-31861.672.rate Not found, base:', $_currency_key, 'target:', $sourceDB);
                }

                if( ! empty( $_rlt_list[$_currency_key]['result']['total_bet'] ) ){
                    $_total_bet += $_rlt_list[$_currency_key]['result']['total_bet'] * $_rate;
                }

                if( ! empty( $_rlt_list[$_currency_key]['result']['total_win'] ) ){
                    $_total_win += $_rlt_list[$_currency_key]['result']['total_win'] * $_rate;
                }

                if( ! empty( $_rlt_list[$_currency_key]['result']['total_loss'] ) ){
                    $_total_loss += $_rlt_list[$_currency_key]['result']['total_loss'] * $_rate;
                }
            } // EOF if($_rlt_list[$_currency_key]['success']){...

            $_rate_details['rate'] = $_rate;
            $_rate_details['converted_total_bet'] = $_total_bet;
            $_rate_details['converted_total_win'] = $_total_win;
            $_rate_details['converted_total_loss'] = $_total_loss;
            $_rate_details['total_bet'] = $orig_total_bet;
            $_rate_details['total_win'] = $orig_total_win;
            $_rate_details['total_loss'] = $orig_total_loss;
            array_push($_multi_currencies_rate_details, $_rate_details);
        } // EOF foreach($_rlt_list as $_currency_key => $_rlt){...
        //
        /// totals all multi-currencies
        $converted_totals = [];
        $converted_totals['total_bet'] = 0;
        $converted_totals['total_win'] = 0;
        $converted_totals['total_loss'] = 0;
        foreach($_multi_currencies_rate_details as $indexNumber => $_details){
            $converted_totals['total_bet'] += $_details['converted_total_bet'];
            $converted_totals['total_win'] += $_details['converted_total_win'];
            $converted_totals['total_loss'] += $_details['converted_total_loss'];
        }
        // // for player_id
        // if($_rlt_list[$sourceDB]['success']){
        //     $converted_totals['player_id'] = $_rlt_list[$sourceDB]['result']['player_id'];
        // }
        //
        $_rlt_list['_multi_currencies_rate_details'] = $_multi_currencies_rate_details; // multi-currencies rate details
        $_rlt_list['_converted_totals'] = $converted_totals; /// it will be return
        // WFMDW = WithForeachMultipleDBWithoutSuper
        $this->detailsOfMethod_in_WFMDWS['getPlayerTotalBetWinLossWithForeachMultipleDBWithoutSuper'] = $_rlt_list; // for trace issue
        $this->utils->debug_log('OGP-31861.750._rlt_list', $_rlt_list); // details

        return $converted_totals;
    }// EOF getPlayerTotalBetWinLossWithForeachMultipleDBWithoutSuper

    // Base convert to Target
    // When 1 USD = 7.07 CNY, rate = 7.07
    public function _getCurrentCurrencyRateWithBase($baseCurrency){
        // $this->_getCurrentCurrencyRateWithBaseFromDailyCurrency($baseCurrency);
        return $this->_getCurrentCurrencyRateWithBaseFromCurrencyConversionRate($baseCurrency);
    } // EOF _getCurrentCurrencyRateWithBase

    public function _getCurrentCurrencyRateWithBaseFromDailyCurrency($baseCurrency){
        $this->ci->load->model(['daily_currency']);
        $rate = null;

        // USD, BRL, PHP,...
        $targetCurrency = $this->utils->getActiveTargetDB(); // current Currency DB
        $date = $this->utils->getTodayForMysql();
        $result = $this->ci->daily_currency->getCurrentCurrencyRate($date,$baseCurrency,$targetCurrency);
        if( ! empty($result) ){
            $rate = $result->rate;
        }

        return $rate;
    } // EOF _getCurrentCurrencyRateWithBase

    /// Base convert to Target
    // When 1 USD = 7.07 CNY, rate = 7.07
    /**
     * Get Current Currency Rate With Base From currency_conversion_rate data-table
     *
     * When 1 USD = 7.07 CNY, rate = 7.07
     * @param string $baseCurrency ex: "USD", "BRL", ...
     * @return float|integer $rate
     */
    public function _getCurrentCurrencyRateWithBaseFromCurrencyConversionRate($baseCurrency){
        $this->ci->load->model(['currency_conversion_rate']);
        $targetCurrency = $this->utils->getActiveTargetDB(); // current Currency DB
        $rate = $this->ci->currency_conversion_rate->getRateByTargetCurrency($targetCurrency);
        if( empty($rate) ){
            $rate = 0; // filter No found result, return null.
        }
        return $rate;
    } // EOF _getCurrentCurrencyRateWithBaseFromCurrencyConversionRate

    public function get_multi_currencies_rate_list(){
        $sourceDB = $this->utils->getActiveTargetDB();
        $dbNameMap = $this->ci->group_level->getDatabaseNameMapFromMDB();
        $multi_currencies_rate_list = [];
        foreach ($dbNameMap as $_currency_key => $dbName) {
            if($sourceDB != $_currency_key){
                $base = $_currency_key;
                // use the data-table,  currency_conversion_rate
                $rate = $this->_getCurrentCurrencyRateWithBase($base);
            }else{
                $rate = 1;
            }

            // base : $_currency_key
            // target : $sourceDB
            $multi_currencies_rate_list[$_currency_key][$sourceDB] = $rate;
        } // EOF foreach ($dbNameMap as $_currency_key => $dbName)...multi_currencies_rate_list
        return $multi_currencies_rate_list;
    } // EOF get_multi_currencies_rate_list

    /**
     * Get the default/assigned arguments of the Specified function
     *
     * Reference to https://stackoverflow.com/a/36938317
     *
     * @param string|array $func The specified function name in string, while method of class in array type.
     * @param array $func_get_args It usually be the return of func_get_args().
     * @return array All arguments by the specified function, $func.
     */
    public function func_get_all_args($func, $func_get_args = array()){

        if((is_string($func) && function_exists($func)) || $func instanceof Closure){
            $ref = new ReflectionFunction($func);
        } else if(is_string($func) && !call_user_func_array('method_exists', explode('::', $func))){
            return $func_get_args;
        } else {
            $ref = new ReflectionMethod($func);
        }
        foreach ($ref->getParameters() as $key => $param) {

            if(!isset($func_get_args[ $key ]) && $param->isDefaultValueAvailable()){
                $func_get_args[ $key ] = $param->getDefaultValue();
            }
        }
        return $func_get_args;
    } // EOF func_get_all_args

    public function getDefaultLevelIdBySourceDB($sourceDB = 'usd'){
        $default_level_id = $this->utils->getConfig('default_level_id'); // @.default_level_id
        $multiple_currency_list =  $this->utils->getConfig('multiple_currency_list');
        if( !empty($multiple_currency_list[$sourceDB]['player_default_level_id']) ){
            // @.multiple_currency_list.vnd.player_default_level_id
            $default_level_id = $multiple_currency_list[$sourceDB]['player_default_level_id'];
        }
        return $default_level_id;
    }
    public function getVipSettingIdFromLevelId($level_id, $db = null){
        $this->ci->load->model(['multiple_db_model']);
        if( empty($db) ){
            $db = $this->ci->multiple_db_model->db;
        }
        $vipSettingId = null;
        $db->from('vipsettingcashbackrule');
        if(!empty($level_id)){
            $db->where('vipsettingcashbackruleId', $level_id);
        }
        $row = $this->ci->multiple_db_model->runOneRowArray($db); // result of levels
        if( ! empty($row['vipSettingId']) ){
            $vipSettingId = $row['vipSettingId'];
        }
        return $vipSettingId;
    } // EOF getVipSettingIdFromLevelId

    /**
     * Execute groupTotalBetsWinsLossGroupByPlayers wrapped with foreachMultipleDBWithoutSuper()
     *
     * The params are depend on total_player_game_day::groupTotalBetsWinsLossGroupByPlayers()
     *
     * @return array The structure should be the same as the method, total_player_game_day::groupTotalBetsWinsLossGroupByPlayers().
     */
    public function groupTotalBetsWinsLossGroupByPlayersWithForeachMultipleDBWithoutSuper(){
        $this->ci->load->model(['multiple_db_model', 'total_player_game_day']);

        // collect the args of method().
        $all_args = $this->func_get_all_args('Total_player_game_day::groupTotalBetsWinsLossGroupByPlayers', func_get_args());

        $multi_currencies_rate_list = $this->get_multi_currencies_rate_list();

        $sourceDB = $this->utils->getActiveTargetDB();
        $readonly = true;
        $_this = $this;
        $_rlt_list = $this->ci->multiple_db_model->foreachMultipleDBWithoutSuper( function($db, &$rlt) use ( $_this, $all_args, $multi_currencies_rate_list){ // callback

            /// aka. $_this->ci->total_player_game_day->groupTotalBetsWinsLossGroupByPlayers()
            // the arguments assign by caller of the funciton, group_level_lib::groupTotalBetsWinsLossGroupByPlayersWithForeachMultipleDBWithoutSuper()
            // replace to current $db of multiple_db_model::foreachMultipleDBWithoutSuper().
            if ($all_args[2] === null || true) { // forced replacement
                $all_args[2] = $db; // #3
            }
            $rlt = call_user_func_array([$_this->ci->total_player_game_day, 'groupTotalBetsWinsLossGroupByPlayers'], $all_args);
            return true; // success

        }, $readonly); // EOF $_rlt_list = $this->ci->multiple_db_model->foreachMultipleDBWithoutSuper(...

        // peeling suffix of database name, like as XXX_readonly to XXX.
        foreach($_rlt_list as $_db => $_rlt){
            $_remove_suffix_db = str_replace('_readonly', '', $_db);
            if($_remove_suffix_db != $_db){ // has appended suffix
                $_rlt_list[ $_remove_suffix_db ] = $_rlt;

                // clear for duplicate data
                $_rlt_list[ $_db ] = [];
                unset($_rlt_list[ $_db ]);
            }
        }

        /// multi-currencies total action, ex:
        // - BRL to USD
        // - PHP to USD
        // - USD
        $_multi_currencies_rate_details = [];
        $_totals[] = [];
        foreach($_rlt_list as $_currency_key => $_rlt){
            // $_rlt[result][n][total_bet]
            // $_rlt[result][n][total_loss]
            // $_rlt[result][n][total_win]
            // $_rlt[result][n][player_id]

            $_rate = null;

            $_rate_details = [];
            $_rate_details['base'] = $_currency_key;
            $_rate_details['target'] = $sourceDB;

            $orig_rows = [];
            if( ! empty( $_rlt['result'] ) ){
                $orig_rows = $_rlt['result'];
            }
            if($_rlt['success']){
                $_rate = $multi_currencies_rate_list[$_currency_key][$sourceDB];
                if($_rate === null){
                    $this->utils->error_log('OGP-28577.906.rate Not found, base:', $_currency_key, 'target:', $sourceDB);
                }
                $_rate = floatval($_rate);
                if( ! empty( $_rlt_list[$_currency_key]['result'] ) ){ // rows

                    foreach($_rlt['result'] as $indexNumber => $_row){
                        foreach($_row as $field_name => $field_val){
                            switch($field_name){
                                case 'player_id':
                                default:
                                break;
                                case 'total_bet':
                                case 'total_loss':
                                case 'total_win':
                                    // _converted_total_bet
                                    // _converted_total_loss
                                    // _converted_total_win
                                    $_rlt_list[$_currency_key]['result'][$indexNumber]['_converted_'. $field_name] = $field_val * $_rate;
                                    break;
                            } // EOF switch($field_name){...
                        } // EOF foreach($_row as $field_name => $field_val){...

                        // for accumulation details
                        $_rlt_list[$_currency_key]['result'][$indexNumber]['_rate'] = $_rate;
                        $_rlt_list[$_currency_key]['result'][$indexNumber]['_base_currency'] = $_currency_key;
                        $_rlt_list[$_currency_key]['result'][$indexNumber]['_target_currency'] = $sourceDB;
                        $this->utils->debug_log('OGP-28577.928._currency_key', $_currency_key, '_row:', $_rlt_list[$_currency_key]['result'][$indexNumber]); // row
                    } // EOF foreach($_rlt['result'] as $indexNumber => $_row){...
                }
            } // EOF if($_rlt_list[$_currency_key]['success']){...
        }// EOF foreach($_rlt_list as $_currency_key => $_rlt){...

        $_converted_rlt_details = []; // details of totals in original and converted by player
        foreach($_rlt_list as $_currency_key => $_rlt){
            foreach($_rlt['result'] as $indexNumber => $_row){
                $_converted_rlt_details[$_row['player_id']][$_currency_key] = $_row;
            }
        }
        // _converted_rlt_details : @.player_id._currency_key = _row

        $_converted_rlt_list = []; // totals in original and converted by player
        foreach($_rlt_list as $_currency_key => $_rlt){
            foreach($_rlt['result'] as $indexNumber => $_row){
                $find = array_search($_row['player_id'], array_column($_converted_rlt_list, 'player_id'));
                if($find !== false){ // found, accumulative
                    $_converted_rlt_list[$find]['_converted_total_bet'] += $_row['_converted_total_bet'];
                    $_converted_rlt_list[$find]['_converted_total_loss'] += $_row['_converted_total_loss'];
                    $_converted_rlt_list[$find]['_converted_total_win'] += $_row['_converted_total_win'];
                    $_converted_rlt_list[$find]['total_bet'] += $_row['total_bet'];
                    $_converted_rlt_list[$find]['total_loss'] += $_row['total_loss'];
                    $_converted_rlt_list[$find]['total_win'] += $_row['total_win'];
                }else{
                    // Not found, new one
                    array_push( $_converted_rlt_list, $_row);
                }
            }
        }

        // clear extra info.
        foreach($_converted_rlt_list as $indexNumber => $_rlt){
            $_converted_rlt_list[$indexNumber]['total_bet'] =  $_rlt['_converted_total_bet'];
            $_converted_rlt_list[$indexNumber]['total_loss'] =  $_rlt['_converted_total_loss'];
            $_converted_rlt_list[$indexNumber]['total_win'] =  $_rlt['_converted_total_win'];

            // clear legacy
            unset($_converted_rlt_list[$indexNumber]['_converted_total_bet']);
            unset($_converted_rlt_list[$indexNumber]['_converted_total_loss']);
            unset($_converted_rlt_list[$indexNumber]['_converted_total_win']);
            unset($_converted_rlt_list[$indexNumber]['_rate']);
            unset($_converted_rlt_list[$indexNumber]['_base_currency']);
            unset($_converted_rlt_list[$indexNumber]['_target_currency']);

            // assign details
            $_player_id = $_converted_rlt_list[$indexNumber]['player_id'];
            $_converted_rlt_list[$indexNumber]['_details'] = $_converted_rlt_details[$_player_id]; // accumulation details
        }
        return $_converted_rlt_list;
    } // EOF groupTotalBetsWinsLossGroupByPlayersWithForeachMultipleDBWithoutSuper

    /**
     *
     * The funtion, "adjustPlayerLevelWithLogs FromCurrentToOtherMDB WithLock" had combied from the following,
     * - the WithLock part of syncPlayerVIPLevelCurrentToMDBWithLock()
     * - the FromCurrentToOtherMDB part of syncPlayerVipLevelByUsernameFromCurrentToOtherMDB()
     * - the adjustPlayerLevelWithLogs part of adjustPlayerLevelWithLogsWithForeachMultipleDBWithoutSourceDB()
     *
     *
     * @param integer $playerId
     * @param integer $newPlayerLevel
     * @param integer $processed_by
     * @param string $action_management_title
     * @param array $logsExtraInfo
     * @return void
     */
    public function adjustPlayerLevelWithLogsFromCurrentToOtherMDBWithLock( $playerId // #1
                                                                            , $newPlayerLevel // #2
                                                                            , $processed_by // #3
                                                                            , $action_management_title // #4
                                                                            , $logsExtraInfo  // #5
                                                                            , &$rlt=null // #6
    ){
        if(!$this->utils->isEnabledMDB()){
            return true;
        }

        $username = $this->ci->player_model->getUsernameById($playerId);
        $_this = $this;
        return $this->utils->globalLockPlayerLevel($username, function ()
                use ($_this, &$rlt, $playerId, $newPlayerLevel, $processed_by, $action_management_title, $logsExtraInfo) {
            $rlt = $_this->adjustPlayerLevelWithLogsWithForeachMultipleDBWithoutSourceDB( $playerId // #1
                                                                                        , $newPlayerLevel // #2
                                                                                        , $processed_by // #3
                                                                                        , $action_management_title // #4
                                                                                        , $logsExtraInfo  // #5
            ); // EOF adjustPlayerLevelWithLogsWithForeachMultipleDBWithoutSourceDB(...
            $success=false;
            if(!empty($rlt)){
                foreach ($rlt as $key => $dbRlt) {
                    $success=$dbRlt['success'];
                    if(!$success){
                        break;
                    }
                }
            }
            return $success;
        });// EOF utils->globalLockPlayerLevel(...
    } // EOF adjustPlayerLevelWithLogsFromCurrentToOtherMDBWithLock

    public function adjustPlayerLevelWithLogsWithForeachMultipleDBWithoutSourceDB( $playerId
                                                                                    , $newPlayerLevel
                                                                                    , $processed_by = Users::SUPER_ADMIN_ID
                                                                                    , $action_management_title ='Test Command Module'
                                                                                    , $logsExtraInfo = []
    ){
        $this->ci->load->model(['multiple_db_model']);
        $sourceDB = $this->utils->getActiveTargetDB();
        // $newPlayerLevel .= '999'; // Test Empty VIP level
        // detect $logsExtraInfo, and conversion for changes and remark
        switch(true){
            case !empty($logsExtraInfo['source_method']):
                $_source_method = $logsExtraInfo['source_method'];
                if( !empty($logsExtraInfo['source_currency']) ){
                    $sourceDB = $logsExtraInfo['source_currency'];
                }
                $_adjusted_source = [];
                $_adjusted_source['method'] = $_source_method;

                $logsExtraInfo['remark'] = [];
                $logsExtraInfo['remark']['adjusted_source'] = $_adjusted_source;

                // convert to extra of changes in update history
                $_changes_formator = ' ( Updated form %s DB, method = %s ) '; // 2 params
                $_changesStr = sprintf($_changes_formator, $sourceDB, $_source_method);
                $logsExtraInfo['changes'] = $_changesStr;
                $logsExtraInfo['description'] = $_changesStr;
                break;

            case !empty($logsExtraInfo['vip_grade_report_id']):
                $_adjusted_source = [];
                $_adjusted_source['currency_key'] = $sourceDB;
                $_adjusted_source['vip_grade_report_id'] = $logsExtraInfo['vip_grade_report_id'];

                // convert to extra of remark in vip grade report
                $logsExtraInfo['remark'] = [];
                $logsExtraInfo['remark']['adjusted_source'] = $_adjusted_source;

                // convert to extra of changes in update history
                $_changes_formator = ' ( Updated form %s DB, vip_grade_report_id = %s ) '; // 2 params
                $_changesStr = sprintf($_changes_formator, $sourceDB, $logsExtraInfo['vip_grade_report_id']);
                $logsExtraInfo['changes'] = $_changesStr;
                $logsExtraInfo['description'] = $_changesStr;
                break;
        } // EOF switch(true){...

        $readonly = false;
        $_this = $this;
        // multiple_db_model->foreachMultipleDBWithoutSuper
        $_rlt_list = $this->ci->multiple_db_model->foreachMultipleDBWithoutSourceDB( $sourceDB, function($db, &$rlt)
        use ( $_this, $playerId, $newPlayerLevel, $processed_by, $action_management_title, $logsExtraInfo ){ // callback

            $theVipGroupLevelDetails = $_this->ci->group_level->getVipGroupLevelDetails($newPlayerLevel, $db);
            if( empty($theVipGroupLevelDetails) ){
                // get $theVipGroupLevelDetails from multiple_currency_list
                $currencyInfo=$_this->ci->multiple_db_model->getCurrencyByDB($db);
                $emptyPlayerLevelId = $newPlayerLevel;
                $newPlayerLevel = $currencyInfo['player_default_level_id'];
                $theVipGroupLevelDetails = $_this->ci->group_level->getVipGroupLevelDetails($newPlayerLevel, $db);
                $_this->utils->debug_log('OGP-28577.1154.change to newPlayerLevelId', $newPlayerLevel, 'empty PlayerLevelId', $emptyPlayerLevelId , 'in db:', $db->getOgTargetDB() );
            }

            $rlt = $_this->ci->group_level_lib->adjustPlayerLevelWithLogs( $playerId // #1
                , $newPlayerLevel // #2
                , $processed_by // #3
                , $action_management_title // #4
                , $logsExtraInfo // #5
                , $db // #6
            );
            return $rlt['success']; // success

        }, $readonly); // EOF $_rlt_list = $this->ci->multiple_db_model->foreachMultipleDBWithoutSuper(...

        return $_rlt_list;
    }

    public function adjustPlayerLevelWithLogs( $playerId // #1
                                                , $newPlayerLevel // #2
                                                , $processed_by = Users::SUPER_ADMIN_ID // #3
                                                , $action_management_title ='Player Management'  // #4, self::ACTION_MANAGEMENT_TITLE
                                                , $logsExtraInfo = [] // #5
                                                , $db = null // #6
    ){
        $this->ci->load->library(['player_manager']);
        $this->ci->load->model(array('users'));


        $results = [];
        $results['success'] = null;
        $results['code'] = null;
        $results['debugMsg'] = null;
        $results['details'] = [];

        $adminUserId = $processed_by;
		$adminUsername = $this->ci->users->getUsernameById($adminUserId, $db);

        //get db from super
        $db->from('player')->where('playerId', $playerId);
        $player=$this->ci->users->runOneRowArray($db);

        if(!empty($player)){
            $results['details']['playerId'] = $playerId;

            $oldlevel = $this->ci->player_manager->getPlayerLevel($playerId, $db);

            $oldlevel_playerGroupId = null;
            if( ! empty($oldlevel) ){
                $oldlevel_playerGroupId = $oldlevel['playerGroupId'];
            }
            $is_already_in = ($oldlevel_playerGroupId == $newPlayerLevel)? true: false;
            $results['details']['oldlevel_playerGroupId'] =$oldlevel_playerGroupId;
            $results['details']['newPlayerLevel'] =$newPlayerLevel;

            $vipupgradesettingId = null;
            $vipupgradesettinginfo = null;
            $theVipGroupLevelDetails = $this->ci->group_level->getVipGroupLevelDetails($newPlayerLevel, $db);
            if( ! empty($theVipGroupLevelDetails) ){
                $vipupgradesettingId = $theVipGroupLevelDetails['vipSettingId'];
                $vipupgradesettinginfo = $this->ci->group_level->getSettingData($theVipGroupLevelDetails['vipSettingId'], $db);
            }else{

            }


            if( ! $is_already_in ){
                $this->ci->group_level->startTrans($db); // Trans start
                $this->ci->group_level->adjustPlayerLevel($playerId, $newPlayerLevel, $db);
                $level = $this->ci->player_manager->getPlayerLevel($playerId, $db);

                $_description = "User " . $adminUsername . " has adjusted vip level of player '" . $playerId . "'";
                if( ! empty( $logsExtraInfo['description']) ){
                    $_description .= $logsExtraInfo['description'];
                }
                $this->utils->recordAction(
                    $action_management_title, // #1
                    lang('player.46'), // #2
                    $_description, // #3
                    $db // #4
                );

                $_changes = lang('player.46'). ' - '
                    . lang('adjustmenthistory.title.beforeadjustment')
                    . ' (' . lang($oldlevel['groupName']). ' - '
                    . lang($oldlevel['vipLevelName']). ') '
                    . lang('adjustmenthistory.title.afteradjustment')
                    . ' (' . lang($level['groupName'])
                    . ' - ' . lang($level['vipLevelName']) . ') ';
                if( ! empty( $logsExtraInfo['changes']) ){
                    $_changes .= $logsExtraInfo['changes'];
                }

                $this->utils->_savePlayerUpdateLog(
                    $playerId, // #1
                    $_changes, // #2
                    $adminUsername, // #3
                    null, // #4
                    $db // #5
                ); // Add log in playerupdatehistory

                $this->ci->group_level->setGradeRecord([
                    'player_id' => $playerId,
                    'request_type'  => Group_level::REQUEST_TYPE_SPECIFIC_GRADE,
                    'request_grade' => Group_level::RECORD_SPECIFICGRADE,
                    'updated_by'    => $adminUserId,
                    'newvipId'      => $newPlayerLevel,
                    'vipupgradesettingId'       => $vipupgradesettingId,
                    'vipupgradesettinginfo'     => json_encode($vipupgradesettinginfo),
                    'vipsettingcashbackruleinfo' => json_encode($theVipGroupLevelDetails),
                    'vipsettingId'  => empty($oldlevel['vipSettingId'])? 0: $oldlevel['vipSettingId'],
                    'vipsettingcashbackruleId' =>  empty($oldlevel['playerGroupId'])? 0: $oldlevel['playerGroupId'],
                    'level_from' =>  empty($oldlevel['vipLevel'])? 0: $oldlevel['vipLevel'],
                    'level_to'   => $level['vipLevel'],
                    'request_time'  => date('Y-m-d H:i:s'),
                    'pgrm_start_time' => date('Y-m-d H:i:s'),
                    'pgrm_end_time'   => date('Y-m-d H:i:s'),
                    'status'          => Group_level::GRADE_SUCCESS
                ]);

                if( ! empty( $logsExtraInfo['remark']) ){
                    $this->ci->group_level->gradeRecodeWithRemarkArray( $logsExtraInfo['remark'], $db);
                }else{
                    $this->ci->group_level->gradeRecode(false, $db);
                }


                $this->ci->group_level->endTrans($db); // Trans end

                if ($this->ci->group_level->isErrorInTrans($db)) {
                    $results['success'] = false;
                    $results['code'] = Utils::RESULT_CASE_THE_ERROR_IN_TRANS;
                    $results['debugMsg'] = lang('text.error');
                }else{
                    $results['success'] = true;
                    $results['code'] = Utils::RESULT_CASE_DONE_IN_TRANS;
                    $results['debugMsg'] = 'success';
                }
            }else{
                $results['success'] = true;
                $results['code'] = Utils::RESULT_CASE_THE_PLAYER_ALREADY_IN_THE_LEVEL;
                $results['debugMsg'] = 'The player already in the level';
            }
        }else{
            $results['success'] = false;
            $results['code'] = Utils::RESULT_CASE_THE_PLAYER_NOT_EXISTS;
            $results['debugMsg'] = 'The player is not exists';
        } // EOF if(!empty($player)){...

        return  $results;
    } // EOF adjustPlayerLevelWithLogs

} // EOF group_level_lib