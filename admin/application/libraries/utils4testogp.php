<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

class Utils4testogp {

	// private $_app_prefix;
	// private $_multiple_databases;

	// cloned from Group_level
	const DOWN_MAINTAIN_TIME_UNIT_DAY = 1;
	const DOWN_MAINTAIN_TIME_UNIT_WEEK = 2;
	const DOWN_MAINTAIN_TIME_UNIT_MONTH = 3;

	function __construct() {
		$this->noteTpl=<<<EOF
<pre>
The Case, %s BEGIN,
The params:
%s
The rlt :
%s
</pre>
EOF;
		$this->CI = &get_instance();
		//default load model
		// $this->CI->load->model(['player_model']);

        // $this->_app_prefix=try_get_prefix();

        // $this->debug_log('load app prefix: '.$this->_app_prefix);

		// $this->CI->utils = $this;
	}


	// [TESTED]
	public function util_clearAppendedContentsInFile($pathFile = '/tmp/someone.php', $appendFixBegin, $appendFixEnd){
		// get the file Contents
		$fileContents4CSL = $this->util_readFile($pathFile);

		// ref. to https://regex101.com/r/qaTZ9n/1
		$pattern = '[('. $appendFixBegin. ')[^\/]*('. $appendFixEnd. ')]im';
		$replacement = '${1}'. PHP_EOL. '${2}';
		$cleared = preg_replace($pattern, $replacement, $fileContents4CSL);
		$this->util_writeToFile($pathFile, $cleared);
	} // EOF util_clearAppendedContentsInFile

	// [TESTED]
	public function util_appendContentsInFileWithPreg_replace($pathFile = '/tmp/someone.php', $extraContents, $appendFixBegin, $appendFixEnd){
		// get the file Contents
		$fileContents4CSL = $this->util_readFile($pathFile);

		$pattern = '[('. $appendFixBegin. ')([^\/]*)('. $appendFixEnd. ')]im';
		$replacement = '${1}${2}'. $extraContents. '${3}';
		$replaced = preg_replace($pattern, $replacement, $fileContents4CSL);
		// echo $pattern;
		$this->util_writeToFile($pathFile, $replaced);
	} // EOF util_appendContentsInFileWithPreg_replace

	//  [TESTED]
	public function util_writeToFile($pathFile = '/tmp/someone.php', $contents){
		return file_put_contents($pathFile, $contents);
	}// writeFile

	//  [TESTED]
	public function util_readFile($pathFile = '/tmp/someone.php'){

		return file_get_contents($pathFile);
		// $fileHandle = fopen($pathFile, "r");
		// // echo 'aaa:'. filesize($pathFile);
		// $contents = fread($fileHandle, filesize($pathFile));
		// fclose($fileHandle);
		// return $contents;
	}// readFile


	//
	/**
	 * 陣列排列組合：array(array(1,2,3),array('a','b','c','d'),array('白色','黑色'))
	 *
	 * Ref. to https://www.itread01.com/content/1548952952.html
	 *
	 *
	 * @param array $arrs The 2-way array.
	 * @return void
	 */
	function combination_arr($arrs) {

		$num = 1;
		foreach ($arrs as $k=>$v) {
		$num *= count($v);
		}

		$arr_num = $num;
		foreach ($arrs as $key=>$v_list) {

		$v_num = count($v_list);
		//$v_list中的元素在排列組合中出現的最大重複次數
		$arr_num = $arr_num / $v_num;
		$position = 0;
		// 開始對$v_list進行迴圈
		foreach($v_list as $v)
		{
			$v_position = $position;
			$count = $num / $v_num / $arr_num;
			for($j = 1; $j <= $count; $j ++)
			{
			for($i = 0; $i < $arr_num; $i ++)
			{
				$result[$v_position + $i][$key] = $v;
			}
			$v_position += $arr_num * $v_num;
			}
			$position += $arr_num;
		}
		}
		return $result;
	} // EOF combination_arr

	/**
	 * Just find keyword in the string for check the elements of the CaseKindList.
	 *
	 * @param string $mystring The string of the CaseKindList's xxxCaseList.
	 * @param string $findme The key word.
	 * @return boolean If true, it means find out the key string, otherwise it means that is not found.
	 */
	public function _pos($mystring, $findme = 'CACB'){
		// $findme = 'CACB';
		// $mystring = $theCombinedCase;
		$pos = strpos($mystring, $findme);
		return ($pos !== false);
	}// EOF _pos

	/**
	 *
	 * getWeekDayNameFromDay in group_level::getDayCashbackSetting().
     * @param string $theDate The speclied date.
     * @param array $thePeriodSetting The return of getPeriodSetting().
     * @param string $theNearBy earlier / later
     */
	function getTheDateNearByPeriodSetting($theDate='now', $thePeriodSetting = [], $theNearBy='later'){

		if( gettype($theDate) == 'string'){
			 $_dateDatetime = new Datetime($theDate);
		}else if( gettype($theDate) == 'object'){ // new Datetime()
			$_dateDatetime = $theDate;
		}
		$returnDate = clone $_dateDatetime;
		switch($thePeriodSetting['period']){
			case 'daily': // should not used.
				$offsetDayOfTheDate = intval($_dateDatetime->format('N'));
			break;
			case 'weekly':
				$weekNoOfTheDate = intval($_dateDatetime->format('N'));
			break;
			case 'monthly':
				$monthDateNoOfTheDate = intval($_dateDatetime->format('d'));
				break;
		}


		$caseString = strtolower($thePeriodSetting['period']). '_'.strtolower($theNearBy);
		switch( $caseString ){
			case 'daily_later':
				$offset = intval($thePeriodSetting['day'])+1;
				$returnDate->modify('+ '. $offset.' days' );
			break;
			case 'daily_earlier':
				$offset = intval($thePeriodSetting['day'])-1;
				if($offset < 0){
					$offset = abs($offset);
					$returnDate->modify('- '. $offset.' days' );
				}else if($offset > 0){
					$returnDate->modify('+ '. $offset.' days' );
				}
				break;
			case 'weekly_later':
				 // $isDateContains
				 // if( $thePeriodSetting['day'] <= $weekNoOfTheDate){
				 //     // $returnDate eq. $_dateDatetime
				 //
				 // }else{
				 //     // $returnDate->modify('+ 1 week')->modify()
				 // }
				$_weekDayName = $this->getDayCashbackSetting( $thePeriodSetting['day'] );
				//  $_weekDayName = getWeekDayNameFromDay( $thePeriodSetting['day'] );
				 $returnDate->modify('next '. $_weekDayName);
				 break;
			case 'weekly_earlier':
				$_weekDayName = $this->getDayCashbackSetting( $thePeriodSetting['day'] );
				// $_weekDayName = getWeekDayNameFromDay( $thePeriodSetting['day'] );
				$returnDate->modify('previous '. $_weekDayName);
				break;
			 case 'monthly_later':
				 $returnDate->modify('next month');
				 $_returnDateStr = $returnDate->format('Y-m-'.sprintf('%02d',$thePeriodSetting['day']));
				 if( ! $this->validateDate($_returnDateStr, 'Y-m-d') ){ // only 2021-04-31 will be invalid date.
					 $_returnDateStr = $returnDate->modify('the last day of this month')->format('Y-m-d');
				 }
				 // overide the $returnDate
				 $returnDate = new Datetime($_returnDateStr);
				 break;
			 case 'monthly_earlier':
				 $returnDate->modify('previous month');
// print_r('173:');
// print_r($returnDate->format('Y-m-d') );
// print_r('175:');
// print_r($thePeriodSetting );
// print_r('177:');
// print_r('Y-m-'.sprintf('%02d',$thePeriodSetting['day']) );

				 $_returnDateStr = $returnDate->format('Y-m-'.sprintf('%02d',$thePeriodSetting['day']));
// print_r('181:');
// print_r($_returnDateStr );
				 if( ! $this->validateDate($_returnDateStr, 'Y-m-d') ){ // only 2021-04-31 will be invalid date.

					 $_returnDateStr = $returnDate->modify('the last day of this month')->format('Y-m-d');
				 }
				 // overide the $returnDate
				 $returnDate = new Datetime($_returnDateStr);
				break;
		}
		 return $returnDate;

	} // EOF getTheDateNearByPeriodSetting

	/**
	 * Get the Period Setting array
	 * Like as the fields,period_up_down_2 and period_down of the table vipsettingcashbackrule.
	 *
	 *
	 * @param string $thePeriod The Period Key string, "day",  "week", and "month".
	 * @param integer $theDay
	 * @return void
	 */
	function getPeriodSetting($thePeriod = 'week', $theDay = 1){
		$_periodSetting = [];
		$_day = null;
		$_period = null;
		switch( strtolower($thePeriod) ){
			case 'daily':
				$_day = intval($theDay); // the input will be "00:00:00 - 23:59:59".
				$_period = strtolower($thePeriod);
				break;
			 case 'weekly':
				$_day = 1;
				$_period = strtolower($thePeriod);
				if( 1 <= $theDay && $theDay <= 7){
					$_day = $theDay;
				}
				break;
			 case 'monthly':
				$_day = 1;
				$_period = strtolower($thePeriod);
				if( 1 <= $theDay && $theDay <= 31){
					$_day = $theDay;
				}
				break;
		}

		if( ! is_null($_day) && ! is_null($_period) ){
			$_periodSetting['period'] = $_period;
			$_periodSetting['day'] = $_day;
		}

		 return $_periodSetting;
	 } // EOF getPeriodSetting


	 /**
	  * cloned from group_level::getDayCashbackSetting()
	  *
	  * @param [type] $cashbackWeeklySetting
	  * @return void
	  */
	 function getDayCashbackSetting($cashbackWeeklySetting) {
		$day = '';
		if ($cashbackWeeklySetting == 1) {
			$day = 'Monday';
		} elseif ($cashbackWeeklySetting == 2) {
			$day = 'Tuesday';
		} elseif ($cashbackWeeklySetting == 3) {
			$day = 'Wednesday';
		} elseif ($cashbackWeeklySetting == 4) {
			$day = 'Thursday';
		} elseif ($cashbackWeeklySetting == 5) {
			$day = 'Friday';
		} elseif ($cashbackWeeklySetting == 6) {
			$day = 'Saturday';
		} elseif ($cashbackWeeklySetting == 7) {
			$day = 'Sunday';
		}
		return $day;
	}// EOF getDayCashbackSetting

	/**
	 * cloned from utils::validateDate()
	 *
	 * Validate Datetime string
	 * Reference to https://stackoverflow.com/a/47151635
	 *
	 * @param string $date The datetime string
	 * @param string $format the datetime format string.
	 * @return boolean Return true for valided date string else invalided.
	 */
	public function validateDate($date, $format = 'Y-m-d H:i:s')
	{
		$d = DateTime::createFromFormat($format, $date);
		return $d && $d->format($format) == $date;
	}



	public function downMaintainConvert2AmountAndUnit($downMaintainUnit, $downMaintainTimeLength){
		switch($downMaintainUnit){
			default:
			case self::DOWN_MAINTAIN_TIME_UNIT_DAY: // cloned from Group_level::DOWN_MAINTAIN_TIME_UNIT_DAY
				$min_required = $downMaintainTimeLength;
				$unit_required = 'day';
			break;
			case self::DOWN_MAINTAIN_TIME_UNIT_WEEK:// cloned from Group_level::DOWN_MAINTAIN_TIME_UNIT_WEEK
				$min_required = $downMaintainTimeLength * 7; // 7 day/week
				$unit_required = 'day';
			break;
			case self::DOWN_MAINTAIN_TIME_UNIT_MONTH:// cloned from Group_level::DOWN_MAINTAIN_TIME_UNIT_MONTH
				$min_required = $downMaintainTimeLength;
				$unit_required = 'month';
			break;
		}
		return [$min_required, $unit_required]; //  amount, unit
	}// EOF downMaintainConvert2AmountAndUnit

	/**
	 * Get LogFile via Cmd
	 * Reference to https://regex101.com/r/j6mIBq/1
	 *
	 * @param string $cmdStr the command string , example:
	 * nohup bash /home/vagrant/Code/og/admin/application/logs/tmp_shell/player_level_downgrade_by_playerId_6aa4a851566ec0f89f9ff47c6960aa96.sh 2>&1 > /home/vagrant/Code/og/admin/application/logs/tmp_shell/job_player_level_downgrade_by_playerId_6aa4a851566ec0f89f9ff47c6960aa96.log &
	 * @param string $funcStr The function name will be the part of log filename.
	 * @return array The log file list.
	 */
	public function getLogFileListViaCmd($cmdStr = '', $funcStr = 'job_player_level_downgrade_by_playerId'){
		$re = '/(?P<filename>[^ ]+'. $funcStr. '_.{32}\.log)/m';
		preg_match_all($re, $cmdStr, $matches, PREG_SET_ORDER, 0);

		// Print the entire match result
		// var_dump($matches);
		$filernameList = [];
		if( !empty($matches) ){
            foreach($matches as $matche){
                $filernameList[] = $matche['filename'];
            }
		}

		return $filernameList;
	} // EOF getLogFileListViaCmd

	// revert player level
	// clear settings in config
	// clear test data in vip_grade_report.
	public function try_revertThisCaseData( $playerId // #1
						, $original_vipsettingcashbackruleId // #2
						, $nowYmdHis // #3
						, $appendFixBegin = '//// Begin append by testing_ogp21799 ////' // #4
						, $appendFixEnd = '//// End append by testing_ogp21799 ////' // #5
	){
		$this->CI->load->model(['group_level']);
		// revert player level
		// $playerId = $theTestPlayerInfo['player_id'];
		$newPlayerLevel = $original_vipsettingcashbackruleId;
		$result = $this->CI->group_level->adjustPlayerLevel($playerId, $newPlayerLevel);

		// clear settings in config
		$isAccumulationSeparated = null;
		$isBettingSeparated = null;
		$this->_preSetupAccumulationAndBettingTo($isAccumulationSeparated, $isBettingSeparated, $appendFixBegin, $appendFixEnd);

		/// clear test data in vip_grade_report.
		// DELETE FROM `vip_grade_report` WHERE `request_time` >= '2021-04-06 00:00:00' LIMIT 50
		// SELECT * FROM `vip_grade_report` WHERE `request_time` >= '2021-04-06 00:00:00' LIMIT 50
		// $nowYmdHis = $now->format('Y-m-d H:i:s');
		$sql = <<<EOF
		DELETE FROM `vip_grade_report`
		WHERE vip_grade_report.player_id = $playerId
		-- AND`request_time` >= '$nowYmdHis'
		AND`pgrm_end_time` >= '$nowYmdHis' -- pgrm_end_time is real time.

EOF;
		$note = '';
		$note .= 'Revert player level,"vipsettingcashbackruleId" to '.$newPlayerLevel;
		$note .= '<br/>'. PHP_EOL;
		$note .= 'Clear settings in config file.';
		$note .= '<br/>'. PHP_EOL;
		$note .= 'Delete ' . $this->CI->group_level->runRawUpdateInsertSQL($sql) . " rows from `vip_grade_report`". PHP_EOL;
		// echo $output;
		// $note = sprintf( $this->noteTpl, '[Step] Check the pre-setup for the related Period settings(for PeriodIsMet and PeriodNotMet) in the Log File, "'. $theLogFilename. '".' // # 1
		// 							, var_export($params, true) // # 2
		// 							, var_export($result, true) // # 3
		// 						);
		$this->CI->utils->debug_log('2830.note:',$note);
		return $note;
		// $this->test( true // ! empty($vipsettingcashbackruleId) // result
		// 		,  true // expect
		// 		, __METHOD__ // title
		// 		, $note // note
		// );
	}// EOF try_revertThisCaseData

	/**
	 * To preset/clear for Accumulation And Betting settings
	 * [TESTED]
	 *
	 * @param boolean|null $isAccumulationSeparated If true will be Common Accumulation else Separated.
	 * @param boolean|null $isBettingSeparated If true will be Common Betting else Separated.
	 * @return bool Always return true.
	 */
	public function _preSetupAccumulationAndBettingTo($isAccumulationSeparated = false
					, $isBettingSeparated = false
					, $appendFixBegin = '//// Begin append by testing_ogp21799 ////'
					, $appendFixEnd = '//// End append by testing_ogp21799 ////'
	){

		if($isAccumulationSeparated === null && $isBettingSeparated === null){
			// clear the preset for Accumulation And Betting settings
			$realPATH = '../../'. 'secret_keys/config_secret_local.php';
			$phpPathFile4CSL = realpath($realPATH);
			// $appendFixBegin = $this->appendFixBegin;
			// $appendFixEnd = $this->appendFixEnd;
			$this->util_clearAppendedContentsInFile($phpPathFile4CSL, $appendFixBegin, $appendFixEnd);
		}else if( ! $isAccumulationSeparated && ! $isBettingSeparated ) { // CACB
			$settingName = 'enable_separate_accumulation_in_setting';
			$settingValue = 'false'; // CA
			$this->_appendBooleanSettingInConfig_secret_local($settingName, $settingValue, $appendFixBegin, $appendFixEnd);
			$settingName = 'vip_setting_form_ver';
			$settingValue = '1'; // CB
			$this->_appendBooleanSettingInConfig_secret_local($settingName, $settingValue, $appendFixBegin, $appendFixEnd);
		}else if( ! $isAccumulationSeparated && $isBettingSeparated ) { // CASB
			$settingName = 'enable_separate_accumulation_in_setting';
			$settingValue = 'false'; // CA
			$this->_appendBooleanSettingInConfig_secret_local($settingName, $settingValue, $appendFixBegin, $appendFixEnd);
			$settingName = 'vip_setting_form_ver';
			$settingValue = '2'; // SB
			$this->_appendBooleanSettingInConfig_secret_local($settingName, $settingValue, $appendFixBegin, $appendFixEnd);
		}else if( $isAccumulationSeparated && ! $isBettingSeparated ) { // SACB
			$settingName = 'enable_separate_accumulation_in_setting';
			$settingValue = 'true'; // SA
			$this->_appendBooleanSettingInConfig_secret_local($settingName, $settingValue, $appendFixBegin, $appendFixEnd);
			$settingName = 'vip_setting_form_ver';
			$settingValue = '1'; // CB
			$this->_appendBooleanSettingInConfig_secret_local($settingName, $settingValue, $appendFixBegin, $appendFixEnd);
		}else if( $isAccumulationSeparated && $isBettingSeparated ) { // SASB
			$settingName = 'enable_separate_accumulation_in_setting';
			$settingValue = 'true'; // SA
			$this->_appendBooleanSettingInConfig_secret_local($settingName, $settingValue, $appendFixBegin, $appendFixEnd);
			$settingName = 'vip_setting_form_ver';
			$settingValue = '2'; // SB
			$this->_appendBooleanSettingInConfig_secret_local($settingName, $settingValue, $appendFixBegin, $appendFixEnd);
		}
		return true;
	} // _preSetupAccumulationAndBettingTo

	/**
	 * Append The Setting into the config_secret_local.php file.
	 * The Setting should be non-array and non-object type.
	 * [TESTED]
	 * @param string $settingName The key name of the $config array in the config_secret_local.php file.
	 * @param string $settingValue The value of the element of $config array in the config_secret_local.php file.
	 * @return boolean always be true.
	 */
	private function _appendBooleanSettingInConfig_secret_local($settingName
						, $settingValue
						, $appendFixBegin = '//// Begin append by testing_ogp21799 ////'
						, $appendFixEnd = '//// End append by testing_ogp21799 ////'
	){
		// $appendFixBegin = $this->appendFixBegin;
		// $appendFixEnd = $this->appendFixEnd;
		// dirname()
		$realPATH = '../../'. 'secret_keys/config_secret_local.php';
		// CSL = config_secret_local
		$phpPathFile4CSL = realpath($realPATH) ;

		$appendSettingFormat = <<<EOF
\$config['%s'] = %s;
EOF;

		$appendSetting = sprintf($appendSettingFormat, $settingName, $settingValue);
		$extraContents = '';
		$extraContents .= $appendSetting;
		$extraContents .= PHP_EOL;

		// get the file Contents
		$fileContents4CSL = $this->util_readFile($phpPathFile4CSL);

		// look for the appendFixEnd string in the file Contents
		$mystring = $fileContents4CSL;
		$findme = $appendFixEnd;
		$pos = strpos($mystring, $findme);

		$isFrist = null;
		if($pos === false){
			$isFrist = true;
		}else{
			$isFrist = false;
		}
		// echo 'isFrist'. $isFrist;
		if($isFrist){
			// append the extra Contents into the file Contents
			$fileContents4CSL .= PHP_EOL;
			$fileContents4CSL .= PHP_EOL;
			$fileContents4CSL .= $appendFixBegin;
			$fileContents4CSL .= PHP_EOL;
			$fileContents4CSL .= $extraContents;
			$fileContents4CSL .= $appendFixEnd;
			$fileContents4CSL .= PHP_EOL;
			$this->util_writeToFile($phpPathFile4CSL, $fileContents4CSL);
		}else{
			// $str_to_insert = $extraContents;
			// $fileContents4CSL = substr_replace($fileContents4CSL, $str_to_insert, $pos, 0);
			// $this->util_writeToFile($phpPathFile4CSL, $fileContents4CSL);
			$this->util_appendContentsInFileWithPreg_replace($phpPathFile4CSL, $extraContents, $appendFixBegin, $appendFixEnd);
		}
		return true;
	} // EOF _appendBooleanSettingInConfig_secret_local

	/**
	 * Assign the all kind combine of the case for testing, execute from SBE.
	 * [TESTED]
	 *
	 * @return array The 2-way array for category classification, $theCaseKindList[categoryStr][kindKeyword] string The meaning of kindKeyword.
	 * - $theCaseKindList['theCommonSeparateModeCaseList']['CACB'] srting SBE has set Common Accumulation + Common Bet Amount.
	 * ...
	 * - $theCaseKindList['thePeriodModeCaseList']['EmptyPeriodMode'] srting The Period setting is Empty.
	 * ...
	 */
	public function _assignCaseKindList4FromSBE(){

		$theCaseKindList = $this->_assignCaseKindList();

		$theConditionMetList = [];
		$theConditionMetList['IsDowngradeConditionMet'] = 'The Downgrade Condition Is Met, and the player will do downgrade in the level.';
		$theConditionMetList['IsDowngradeConditionNotMet'] = 'The Downgrade Condition Is Not Met, and the player will keep in the current level.';
		$theCaseKindList['theConditionMetList'] = $theConditionMetList;
		return $theCaseKindList;
	}// EOF _assignCaseKindList4FromSBE

	public function _assignCaseKindList4ogp21818(){

		$theCaseKindList = $this->_assignCaseKindList();

		// override theAccumulationCaseList
		$theAccumulationCaseList = [];
		$theAccumulationCaseList['AccumulationYesRegistrationDate'] = 'Accumulation Setup to Yes,Registration Date';
		$theCaseKindList['theAccumulationCaseList'] = $theAccumulationCaseList;

		// override thePeriodModeCaseList
		$thePeriodModeCaseList = []; // for $periodMode,$periodValue
		$thePeriodModeCaseList['DailyPeriodMode'] = 'The Period setting is daily, "00:00:00 - 23:59:59".';// 'daily,00:00:00 - 23:59:59';
		$theCaseKindList['thePeriodModeCaseList'] = $thePeriodModeCaseList;

		$theConditionMetList = [];
		$theConditionMetList['IsDowngradeConditionMet'] = 'The Downgrade Condition Is Met, and the player will do downgrade in the level.';
		$theConditionMetList['IsDowngradeConditionNotMet'] = 'The Downgrade Condition Is Not Met, and the player will keep in the current level.';
		$theCaseKindList['theConditionMetList'] = $theConditionMetList;

		return $theCaseKindList;
	}// EOF _assignCaseKindList4ogp21818

	/**
	 * Assign the all kind combine of the case for test
	 * [TESTED]
	 *
	 * @return array The 2-way array for category classification, $theCaseKindList[categoryStr][kindKeyword] string The meaning of kindKeyword.
	 * - $theCaseKindList['theCommonSeparateModeCaseList']['CACB'] srting SBE has set Common Accumulation + Common Bet Amount.
	 * ...
	 * - $theCaseKindList['thePeriodModeCaseList']['EmptyPeriodMode'] srting The Period setting is Empty.
	 * ...
	 */
	public function _assignCaseKindList(){
		$theCaseKindList = [];

		$theCommonSeparateModeCaseList = $this->genCommonSeparateModeCaseList();
		///CommonSeparateMode = Separate Accumulation + Common Bet Amount
		//
		/// values, CACB / SACB / CASB / SASB
		// tryDowngradeSuccessInCACBTriggerFromCronjob
		// tryDowngradeSuccessInSACBTriggerFromCronjob
		// tryDowngradeSuccessInCASBTriggerFromCronjob
		// tryDowngradeSuccessInSASBTriggerFromCronjob
		$theCaseKindList['theCommonSeparateModeCaseList'] = $theCommonSeparateModeCaseList;
		// $this->theCommonSeparateModeCaseList = $theCommonSeparateModeCaseList;

		$thePeriodModeCaseList = $this->genPeriodModeCaseList();
		/// 各個週期下，符合 / 不符合
		// // 週期: N/A, Daily, Weekly, Monthly
		// // N/A : $periodMode = 'null'
		// // Daily : $periodMode = 'daily', $periodValue = 00:00:00 - 23:59:59
		// // Weekly : $periodMode = 'weekly', $periodValue = 1
		// // Monthly : $periodMode = 'monthly', $periodValue = 1
		// /// 現在時間，為指定的週期時間：
		// // daily: $periodValue = '00:00:00 - 23:59:59';
		// // weekly: $periodValue = $TEB_DateTime->format('N');
		// // monthly: $periodValue = $TEB_DateTime->format('d');
		$theCaseKindList['thePeriodModeCaseList'] = $thePeriodModeCaseList;
		// $this->thePeriodModeCaseList = $thePeriodModeCaseList;

		// add the prefix,"nextLevelUp" to $thePeriodModeCaseList
		$theCaseKindList['theNextLevelUpPeriodModeCaseList'] = $this->genNextLevelList4CaseKindList($thePeriodModeCaseList);

		$theUpgradeSettingCaseList = $this->genUpgradeSettingCaseList();
		$theCaseKindList['theUpgradeSettingCaseList'] = $theUpgradeSettingCaseList;

		// add the prefix,"nextLevelUp" to $theUpgradeSettingCaseList
		$theCaseKindList['theNextLevelUpUpgradeSettingCaseList'] = $this->genNextLevelList4CaseKindList($theUpgradeSettingCaseList);

		$theConditionMetList = $this->genConditionMetList();
		// Only for theUpgradeSettingCaseList
		$theCaseKindList['theConditionMetList'] = $theConditionMetList;


		$theMetInFormulaCaseList = $this->genMetInFormulaCaseList();
		/// All items: bet, deposit, loss and win In Formula
		$theCaseKindList['theMetInFormulaCaseList'] = $theMetInFormulaCaseList;
		// add the prefix,"NextLevelUp" to $theMetInFormulaCaseList
		$theCaseKindList['theNextLevelUpMetInFormulaCaseList'] = $this->genNextLevelList4CaseKindList($theMetInFormulaCaseList);

		$thePeriodIsMetCaseList = $this->genPeriodIsMetCaseList();
		$theCaseKindList['thePeriodIsMetCaseList'] = $thePeriodIsMetCaseList;

		// add the prefix,"nextLevelUp" to $thePeriodIsMetCaseList
		$theCaseKindList['theNextLevelUpPeriodIsMetCaseList'] = $this->genNextLevelList4CaseKindList($thePeriodIsMetCaseList);

		$theLevelMaintainEnableCaseList = $this->genLevelMaintainEnableCaseList();
		/// 新保級制度 ： OFF / ON, Maintain Time 保護 / Level Maintain Condition 沒完成、降級 / Level Maintain Condition 完成、沒降級
		// // OFF: $enableLevelMaintainFn = 0
		// // ON, Maintain Time 保護: ( MaintainTime:2 day)
		// // Level Maintain Condition 沒完成、降級: ( MaintainTime:2 day)
		// // Level Maintain Condition 完成、沒降級: ( MaintainTime:2 day)
		// //
		// // $_this = $this;
		// // $thePlayerId = $theTestPlayerInfo['player_id'];
		// // $enableLevelMaintainFn = function ($thePlayerId) use ($_this){
		// // 	return $_this->_getEnableLevelMaintainInPlayerCurrentLevelFn($thePlayerId, true, 2, 22, 55, 66);
		// // };
		$theCaseKindList['theLevelMaintainEnableCaseList'] = $theLevelMaintainEnableCaseList;


		$theHourlyCheckUpgradeEnableCaseList = $this->genHourlyCheckUpgradeEnableCaseList();
		/// Hourly Check Upgrade for upgrade only
		$theCaseKindList['theHourlyCheckUpgradeEnableCaseList'] = $theHourlyCheckUpgradeEnableCaseList;

		$theLevelMaintainTimeCaseList = $this->genLevelMaintainTimeCaseList();
		$theCaseKindList['theLevelMaintainTimeCaseList'] = $theLevelMaintainTimeCaseList;
		// $this->theLevelMaintainTimeCaseList = $theLevelMaintainTimeCaseList;

		$theLevelMaintainConditionCaseList = $this->genLevelMaintainConditionCaseList();
		$theCaseKindList['theLevelMaintainConditionCaseList'] = $theLevelMaintainConditionCaseList;
		// $this->theLevelMaintainConditionCaseList = $theLevelMaintainConditionCaseList;

		/// 舊保級制度  [ignore]

		$theAccumulationCaseList = $this->genAccumulationCaseList();
		/// 累計: No/ Yes, Registration Date / Yes, Last Change Period
		// // $getUpgradeLevelSettingFn = function ($settingName, $forGrade) { // ref. to _getDowngradeLevelSettingFn()
		// //
		// // }
		// // 包含 "NextLevelUp" 前綴詞。下週期的累計模式
		$theCaseKindList['theAccumulationCaseList'] = $theAccumulationCaseList;
		// add the prefix,"NextLevelUp" to $thePeriodIsMetCaseList
		$theCaseKindList['theNextLevelUpAccumulationCaseList'] = $this->genNextLevelList4CaseKindList($theAccumulationCaseList);

		$theMetedInPreconditionsCaseList = $this->genMetedInPreconditionsCaseList();
		$theCaseKindList['theMetedInPreconditionsCaseList'] = $theMetedInPreconditionsCaseList;

		$theMultipleUpgradeEnableModeCaseList = $this->genMultipleUpgradeEnableModeCaseList();
		$theCaseKindList['theMultipleUpgradeEnableModeCaseList'] = $theMultipleUpgradeEnableModeCaseList;

		$theExpectedList = $this->genExpectedList();
		$theCaseKindList['theExpectedList'] = $theExpectedList;

		return $theCaseKindList;
	} // EOF _assignCaseKindList

	/**
	 * The combines cases in Separate / Common Accumulation
	 *
	 * @return array
	 */
	public function genCommonSeparateModeCaseList(){
		///CommonSeparateMode = Separate Accumulation + Common Bet Amount
		//
		/// values, CACB / SACB / CASB / SASB
		// tryDowngradeSuccessInCACBTriggerFromCronjob
		// tryDowngradeSuccessInSACBTriggerFromCronjob
		// tryDowngradeSuccessInCASBTriggerFromCronjob
		// tryDowngradeSuccessInSASBTriggerFromCronjob
		$theCommonSeparateModeCaseList = [];
		$theCommonSeparateModeCaseList['CACB']= 'SBE has set Common Accumulation + Common Bet Amount'; // 'tryDowngradeSuccessInCACBTriggerFromCronjob';
		$theCommonSeparateModeCaseList['SACB']= 'SBE has set Separate Accumulation + Common Bet Amount'; // 'tryDowngradeSuccessInSACBTriggerFromCronjob';
		$theCommonSeparateModeCaseList['CASB']= 'SBE has set Common Accumulation + Separate Bet Amount'; // 'tryDowngradeSuccessInCASBTriggerFromCronjob';
		$theCommonSeparateModeCaseList['SASB']= 'SBE has set Separate Accumulation + Separate Bet Amount'; // 'tryDowngradeSuccessInSASBTriggerFromCronjob';
		return $theCommonSeparateModeCaseList;
	}// EOF genCommonSeparateModeCaseList

	/**
	 * The combines cases in Period
	 *
	 * @return array
	 */
	public function genPeriodModeCaseList(){
		/// 各個週期下，符合 / 不符合
		// 週期: N/A, Daily, Weekly, Monthly
		// N/A : $periodMode = 'null'
		// Daily : $periodMode = 'daily', $periodValue = 00:00:00 - 23:59:59
		// Weekly : $periodMode = 'weekly', $periodValue = 1
		// Monthly : $periodMode = 'monthly', $periodValue = 1
		/// 現在時間，為指定的週期時間：
		// daily: $periodValue = '00:00:00 - 23:59:59';
		// weekly: $periodValue = $TEB_DateTime->format('N');
		// monthly: $periodValue = $TEB_DateTime->format('d');
		$thePeriodModeCaseList = []; // for $periodMode,$periodValue
		$thePeriodModeCaseList['EmptyPeriodMode'] = 'The Period setting is Empty'; // PeriodMode is empty
		$thePeriodModeCaseList['DailyPeriodMode'] = 'The Period setting is daily, "00:00:00 - 23:59:59".';// 'daily,00:00:00 - 23:59:59';
		$thePeriodModeCaseList['Weekly1PeriodMode'] = 'The Period setting is weekly, Monday.';// 'weekly,1'; // Monday Tuesday Wednesday Thursday Friday Saturday Sunday
		$thePeriodModeCaseList['Monthly5PeriodMode'] = 'The Period setting is Monthly, 5th.';// 'monthly,1'; // 1 ~ 31
		return $thePeriodModeCaseList;
	} // EOF genPeriodModeCaseList
	/**
	 * The combines cases in Formula
	 * If used, and should be used with $thePeriodModeCaseList together.
	 *
	 * @return array
	 */
	public function genPeriodIsMetCaseList(){
		$thePeriodIsMetCaseList = [];
		$thePeriodIsMetCaseList['PeriodIsMet'] = 'The check time has Met with the Period setting';
		$thePeriodIsMetCaseList['PeriodNotMet'] = 'The check time has Not Met with the Period setting';
		return $thePeriodIsMetCaseList;
	} // EOF genPeriodIsMetCaseList
	/**
	 * The combines cases in Accumulation
	 *
	 * @return array
	 */
	public function genAccumulationCaseList(){
		/// 累計: No/ Yes, Registration Date / Yes, Last Change Period
		// $getUpgradeLevelSettingFn = function ($settingName, $forGrade) { // ref. to _getDowngradeLevelSettingFn()
		//
		// }
		// 包含 "NextLevelUp" 前綴詞。下週期的累計模式
		$theAccumulationCaseList = [];
		$theAccumulationCaseList['NoAccumulation'] = 'Accumulation Setup to No';
		$theAccumulationCaseList['AccumulationYesRegistrationDate'] = 'Accumulation Setup to Yes,Registration Date';
		$theAccumulationCaseList['AccumulationYesLastChangePeriod'] = 'Accumulation Setup to Yes,Last Change Period';
		$theAccumulationCaseList['AccumulationLastChangePeriodResetIfMet'] = 'Accumulation Setup to Yes,Last Change Period,Reset If Met'; // OGP-24373
		return $theAccumulationCaseList;
	} // EOF genAccumulationCaseList

	/**
	 * The combines cases in Formula
	 *
	 * @return array
	 */
	public function genMetInFormulaCaseList(){
		/// All items: bet, deposit, loss and win
		$theMetInFormulaCaseList = [];// There are no items that satisfy the formula
		$theMetInFormulaCaseList['AllNoMetInFormula'] = 'No items are met In Formula, all items had set.';// Formula, "bet < 0 and deposit < 0 and loss < 0 and win < 0"
		$theMetInFormulaCaseList['OnlyMetBetOfAllInFormula'] = 'The Formula only met bet, all items had set.'; // Formula, "bet >= 0 and deposit < 0 and loss < 0 and win < 0"
		$theMetInFormulaCaseList['OnlyMetDepositOfAllInFormula'] = 'The Formula only met deposit, all items had set.'; // Formula, "bet < 0 and deposit >= 0 and loss < 0 and win < 0"
		$theMetInFormulaCaseList['OnlyMetBetDepositOfAllInFormula'] = 'The Formula only met bet and deposit, all items had set.'; // Formula, "bet >= 0 and deposit >= 0 and loss < 0 and win < 0"
		$theMetInFormulaCaseList['OnlyMetBetDepositWinOfAllInFormula'] = 'The Formula only met bet, deposit and win, all items had set.'; // Formula, "bet >= 0 and deposit >= 0 and loss < 0 and win >= 0"
		$theMetInFormulaCaseList['AllMetInFormula'] = 'All items are met In Formula, all items had set.'; // Formula, "bet >= 0 and deposit >= 0 and loss >= 0 and win >= 0"
		$theMetInFormulaCaseList['OnlyMetBetOfBetInFormula'] = 'The Formula only met bet, and only bet had set.'; // Formula, "bet >= 0"
		$theMetInFormulaCaseList['OnlyMetBetOfBetDepositInFormula'] = 'The Formula only met bet, and bet and deposit had set.'; // Formula, "bet >= 0 and deposit < 0 "
		///  replace the cases(x8), EmptyUpgradeSetting, DepositUpgradeSetting, BetUpgradeSetting and DepositBetUpgradeSetting to the followings,
		$theMetInFormulaCaseList['EmptyInFormula'] = 'The empty Formula.'; // aka. EmptyUpgradeSetting. Formula, ""
		$theMetInFormulaCaseList['OnlyMetDepositOfDepositInFormula'] = 'The Formula met deposit and only had set the deposit.'; // aka. DepositUpgradeSetting+ IsConditionMet. Formula, "deposit >= 0"
		$theMetInFormulaCaseList['NotMetDepositOfDepositInFormula'] = 'The Formula Not met deposit and only had set the deposit.'; // aka. DepositUpgradeSetting+ IsConditionNotMet. Formula, "deposit <= 0"

		$theMetInFormulaCaseList['NotMetBetOfBetInFormula'] = 'The Formula Not met bet and only had set the bet.'; // aka. BetUpgradeSetting+ IsConditionNotMet. Formula, "bet <= 0"
		// $theMetInFormulaCaseList['OnlyMetBetOfBetDepositInFormula'] = ''; // aka. DepositBetUpgradeSetting+ IsConditionMet. Formula, "bet >= 0 and deposit <= 0"
		$theMetInFormulaCaseList['OnlyMetDepositOfBetDepositInFormula'] = 'The Formula Only met deposit and that had set the bet and deposit.'; // aka. DepositBetUpgradeSetting+ IsConditionNotMet. Formula, "bet <= 0 and deposit >= 0"
		$theMetInFormulaCaseList['NoMetAllOfBetDepositInFormula'] = 'The Formula Not met any condition and that had set the bet and deposit.'; // aka. DepositBetUpgradeSetting+ IsConditionNotMet. Formula, "bet <= 0 and deposit <= 0"
		$theMetInFormulaCaseList['AllMetOfBetDepositInFormula'] = 'The Formula All met and that had set the bet and deposit.'; // aka. DepositBetUpgradeSetting+ IsConditionMet. Formula, "bet >= 0 and deposit >= 0"

		/// Another one is allowed. @todo OGP-25082
		// GT : > (Greater Than)
		// GTE : >= (Greater Than or Equal to)
		// LT : < (Less Than)
		// LTE : <= (Less Than or Equal to)
		$theMetInFormulaCaseList['betGT10AndDepositGTE0OfFormula'] = 'The condition, "bet > 10 and deposit >= 0" of the Formula, that in the current level.'; // Formula, "bet > 0 and deposit >= 0 "
		$theMetInFormulaCaseList['betGTE10AndDepositLT0OfFormulaInNextLv'] = 'The condition, "bet >= 10 and deposit < 0" of the Formula, that in the next level.'; // Formula, "bet >= 0 and deposit < 0 "
		$theMetInFormulaCaseList['betGTE10OrDepositLT0OfFormulaInNext2Lv'] = 'The condition, "bet >= 10 or deposit < 0" of the Formula, that in the next 2nd level.'; // Formula, "bet >= 10 or deposit < 0"
		$theMetInFormulaCaseList['betGTE10OrDepositLT0OfFormulaInNext3Lv'] = 'The condition, "bet >= 10 or deposit < 0" of the Formula, that in the next 3rd level.'; // Formula, "bet >= 10 or deposit < 0"
		// @todo Met Or Not, It should confirm the date range of the amount accumulated.


		return $theMetInFormulaCaseList;
	} // EOF genMetInFormulaCaseList()

	/**
	 * The combines cases in Formula, but this is only for the old testing.
	 * More better, PLEASE use genMetInFormulaCaseList().
	 *
	 * @return array
	 */
	public function genUpgradeSettingCaseList(){
		$theUpgradeSettingCaseList = []; // for $periodMode,$periodValue

		/// Upgrade Setting is empty
		// Formula, ""
		$theUpgradeSettingCaseList['EmptyUpgradeSetting'] = 'The Upgrade Setting is Empty';
		/// 1 item only
		// Formula, "deposit >= 0"
		$theUpgradeSettingCaseList['DepositUpgradeSetting'] = 'The Upgrade Setting has Deposit Only'; // Upgrade Setting has Deposit Only
		// Formula, "bet >= 0"
		$theUpgradeSettingCaseList['BetUpgradeSetting'] = 'The Upgrade Setting has Bet Only'; // Upgrade Settin has Bet Only
		// $theUpgradeSettingCaseList['WinUpgradeSetting'] = 'The Upgrade Setting has Win Only'; // Upgrade Settin is empty
		// $theUpgradeSettingCaseList['LossUpgradeSetting'] = 'The Upgrade Setting has Loss Only'; // Upgrade Settin is empty
		/// 2 items combine, Deposit x Bet,...
		// Formula, "bet >= 0 and deposit >= 0"
		$theUpgradeSettingCaseList['DepositBetUpgradeSetting'] = 'The Upgrade Setting has Deposit and Bet'; // Upgrade Settin is empty
		// $theUpgradeSettingCaseList['DepositWinUpgradeSetting'] = 'The Upgrade Setting has Deposit and Win'; // Upgrade Settin is empty
		// $theUpgradeSettingCaseList['DepositLossUpgradeSetting'] = 'The Upgrade Setting has Deposit and Loss'; // Upgrade Settin is empty
		// // 2 items combine, Bet x Deposit,...
		// $theUpgradeSettingCaseList['BetDepositUpgradeSetting'] = 'The Upgrade Setting has Bet and Deposit'; // Upgrade Settin is empty
		// $theUpgradeSettingCaseList['BetWinUpgradeSetting'] = 'The Upgrade Setting has Bet and Win'; // Upgrade Settin is empty
		// $theUpgradeSettingCaseList['BetLossUpgradeSetting'] = 'The Upgrade Setting has Bet and Loss'; // Upgrade Settin is empty
		// // 2 items combine, Win x Deposit,...
		// $theUpgradeSettingCaseList['WinDepositUpgradeSetting'] = 'The Upgrade Setting has Win and Deposit'; // Upgrade Settin is empty
		// $theUpgradeSettingCaseList['WinBetUpgradeSetting'] = 'The Upgrade Setting has Win and Bet'; // Upgrade Settin is empty
		// $theUpgradeSettingCaseList['WinLossUpgradeSetting'] = 'The Upgrade Setting has Win and Loss'; // Upgrade Settin is empty
		// // 2 items combine, Loss x Deposit,...
		// $theUpgradeSettingCaseList['LossDepositUpgradeSetting'] = 'The Upgrade Setting has Loss and Deposit'; // Upgrade Settin is empty
		// $theUpgradeSettingCaseList['LossBetUpgradeSetting'] = 'The Upgrade Setting has Loss and Bet'; // Upgrade Settin is empty
		// $theUpgradeSettingCaseList['LossWinUpgradeSetting'] = 'The Upgrade Setting has Loss and Win'; // Upgrade Settin is empty
		// @todo 3 items combine, ...
		return $theUpgradeSettingCaseList;
	} // EOF genUpgradeSettingCaseList
	/**
	 * It only for genUpgradeSettingCaseList(),"$theUpgradeSettingCaseList)" and this is only for the old testing.
	 * Because its controlled all amount to be Met Or Not, and it cannot be specified individually.
	 * So PLEASE use genMetInFormulaCaseList().
	 */
	public function genConditionMetList(){
		$theConditionMetList = [];
		$theConditionMetList['IsConditionMet'] = 'The Upgrade/Downgrade Condition Is Met, and the player will do upgrade in the level.';
		$theConditionMetList['IsConditionNotMet'] = 'The Upgrade/Downgrade Condition Is Not Met, and the player will keep in the current level.';
		/// IsDowngradeConditionMet and IsDowngradeConditionNotMet for admin/application/controllers/cli/testing_ogp21673.php
		// $theConditionMetList['IsDowngradeConditionMet'] = 'The Downgrade Condition Is Met, and the player will do downgrade in the level.';
		// $theConditionMetList['IsDowngradeConditionNotMet'] = 'The Downgrade Condition Is Not Met, and the player will keep in the current level.';
		return $theConditionMetList;
	} // EOF genConditionMetList

	/**
	 * The combines cases in the expected results
	 *
	 * @return array
	 */
	public function genExpectedList(){
		$theExpectedList = [];
		$theExpectedList['ExpectedNoDowngrade'] = 'Expected No Downgrade';
		$theExpectedList['ExpectedDowngrade'] = 'Expected Downgrade';
		$theExpectedList['ExpectedNoUpgrade'] = 'Expected No Upgrade';
		$theExpectedList['ExpectedUpgrade'] = 'Expected Upgrade';
		$theExpectedList['ExpectedMultiUpgrade'] = 'Expected Multi Upgrade';
		return $theExpectedList;
	} // EOF genExpectedList

	/**
	 * The combines cases in Hourly Checkbox of the VIP setting
	 * Its only for upgrade.
	 *
	 * @return array
	 */
	public function genHourlyCheckUpgradeEnableCaseList(){
		// Hourly Check Upgrade for upgrade only
		$theHourlyCheckUpgradeEnableCaseList = [];
		$theHourlyCheckUpgradeEnableCaseList['OnHourlyCheckUpgradeEnable'] = 'Has turn On Hourly Check Upgrade';
		$theHourlyCheckUpgradeEnableCaseList['OffHourlyCheckUpgradeEnable'] = 'Has turn Off Hourly Check Upgrade';
		return $theHourlyCheckUpgradeEnableCaseList;
	} // EOF genHourlyCheckUpgradeEnableCaseList

	/**
	 * The combines cases handle the had or not met condition In Preconditions.
	 * Its only for upgrade.
	 *
	 * @return array
	 */
	public function genMetedInPreconditionsCaseList(){
		$theMetedInPreconditionsCaseList = [];
		$theMetedInPreconditionsCaseList['NeverMetedInPreconditions'] = 'Anyone had not Meted, for "Accumulation Yes,Last Change Period,Reset If Met".'; // OGP-24373
		$theMetedInPreconditionsCaseList['HadBetMetedInPreconditions'] = 'The Bet had Meted, for "Accumulation Yes,Last Change Period,Reset If Met".'; // OGP-24373
		$theMetedInPreconditionsCaseList['HadDepositMetedInPreconditions'] = 'The Deposit had Meted, for "Accumulation Yes,Last Change Period,Reset If Met".'; // OGP-24373
		$theMetedInPreconditionsCaseList['IngoreInPreconditions'] = 'The Deposit had Meted, for "Accumulation Yes,Last Change Period,Reset If Met".'; // OGP-24373

		// 流水已有 25 at 2/11, 2/14, Another one is allowed. @todo OGP-25082
		$theMetedInPreconditionsCaseList['HadBet25To66746InPreconditionsOn20220211121110'] ='The Bet 25 had been to game_description_id=66746, on 2022-02-11 12:11:10.';
		$theMetedInPreconditionsCaseList['HadBet25To66746InPreconditionsOn20220214121113'] ='The Bet 25 had been to game_description_id=66746, on 2022-02-14 12:11:13.';
		$theMetedInPreconditionsCaseList['HadDeposit25To66746InPreconditionsOn20220211121110'] ='The Deposit 25 had been to game_description_id=66746, on 2022-02-11 12:11:10.';
		$theMetedInPreconditionsCaseList['HadDeposit25To66746InPreconditionsOn20220214121113'] ='The Deposit 25 had been to game_description_id=66746, on 2022-02-14 12:11:13.';
		$theMetedInPreconditionsCaseList['ExecUpgradeOn20220214121314InPreconditions'] = 'Execut the Upgrade to confirm that it has been met.';
		return $theMetedInPreconditionsCaseList;
	} // EOF genMetedInPreconditionsCaseList

	/**
	 * The combines cases handle Multiple Upgrade.
	 * Its only for upgrade.
	 *
	 * @return array
	 */
	public function genMultipleUpgradeEnableModeCaseList(){
		$theMultipleUpgradeEnableModeCaseList = [];
		$theMultipleUpgradeEnableModeCaseList['OnMultipleUpgradeEnable'] = 'Has Enable Multiple Upgrade Feature';
		$theMultipleUpgradeEnableModeCaseList['OffMultipleUpgradeEnable'] = 'Has Disable Multiple Upgrade Feature';
		return $theMultipleUpgradeEnableModeCaseList;
	} // EOF genMultipleUpgradeEnableModeCaseList

	/**
	 * The combines cases in Level Maintain
	 * Its only for downgrade.
	 *
	 * @return array
	 */
	public function genLevelMaintainEnableCaseList(){
		/// 新保級制度 ： OFF / ON, Maintain Time 保護 / Level Maintain Condition 沒完成、降級 / Level Maintain Condition 完成、沒降級
		// OFF: $enableLevelMaintainFn = 0
		// ON, Maintain Time 保護: ( MaintainTime:2 day)
		// Level Maintain Condition 沒完成、降級: ( MaintainTime:2 day)
		// Level Maintain Condition 完成、沒降級: ( MaintainTime:2 day)
		//
		// $_this = $this;
		// $thePlayerId = $theTestPlayerInfo['player_id'];
		// $enableLevelMaintainFn = function ($thePlayerId) use ($_this){
		// 	return $_this->_getEnableLevelMaintainInPlayerCurrentLevelFn($thePlayerId, true, 2, 22, 55, 66);
		// };
		$theLevelMaintainEnableCaseList = [];
		$theLevelMaintainEnableCaseList['OnLevelMaintainEnable'] = 'Has turn On Level Maintain';
		$theLevelMaintainEnableCaseList['OffLevelMaintainEnable'] = 'Has turn Off Level Maintain';
		return $theLevelMaintainEnableCaseList;
	} // EOF genLevelMaintainEnableCaseList
	/**
	 * The combines cases control the check downgrade time, that is in Maintain Time of Level Maintain Or over.
	 * Its only for downgrade.
	 * If used, and should be used with $theLevelMaintainEnableCaseList together.
	 *
	 * @return array
	 */
	public function genLevelMaintainTimeCaseList(){
		$theLevelMaintainTimeCaseList = [];
		$theLevelMaintainTimeCaseList['InMaintainTime'] = 'The downgrade check time (on current)  Within Level Maintain Time';
		$theLevelMaintainTimeCaseList['OverMaintainTime'] = 'The downgrade check time (on current)  Over Level Maintain Time';
		return $theLevelMaintainTimeCaseList;
	} // EOF genLevelMaintainTimeCaseList
	/**
	 * The combines cases control the check downgrade time, that is in Maintain Time of Level Maintain Or over.
	 * Its only for downgrade.
	 * If used, and should be used with $theLevelMaintainEnableCaseList together.
	 *
	 * @return array
	 */
	public function genLevelMaintainConditionCaseList(){
		$theLevelMaintainConditionCaseList = [];
		$theLevelMaintainConditionCaseList['IsMetLevelMaintainCondition'] = 'Is Met Level Maintain Condition, will be downgrade by this.';
		$theLevelMaintainConditionCaseList['NotMetLevelMaintainCondition'] = 'Not Met Level Maintain Condition, will be keep in current level by this.';
		return $theLevelMaintainConditionCaseList;
	} // EOF genLevelMaintainConditionCaseList

	/**
	 * Generate the "NextLevel" prefix array for theCaseKindList.
	 *
	 * @param array $theSourceCaseList
	 * @return void
	 */
	public function genNextLevelList4CaseKindList($theSourceCaseList = []){
		$theNextLevelCaseList = [];
		if( ! empty($theSourceCaseList) ){
			foreach($theSourceCaseList as $modeCaseStr => $modeCaseString ){
				$theNextLevelCaseList['NextLevelUp'. $modeCaseStr] = $modeCaseString. '(at next level up)';
			}
		}

		return $theNextLevelCaseList;
	} // EOF genNextLevelList4CaseKindList

	/**
	 * The System Feature Setup
	 *
	 * @param string $featureName The System Feature Name in the field,"system_features.name"
	 * @param integer|string $value Just support 1 or 0(Zero). That's mean enable and disable.
	 * @return boolean The return of db::update().
	 */
	public function _preSetupSystemFeatureTo($featureName = 'disable_player_multiple_upgrade', $value){
		// SELECT * FROM `system_features` WHERE `name` = 'disable_player_multiple_upgrade' LIMIT 50 (0.002秒) 編輯
		$this->CI->load->model(['system_feature']); // system_feature::__syncAllFeatures
		$rlt = $this->CI->system_feature->updateTheFeatureWithName('disable_player_multiple_upgrade', $value);
		return $rlt;
	} // EOF _preSetupSystemFeaturesTo

	/**
	 * Get the Test Player Info
	 *
	 * @param integer $thePlayerId The field, player.player_id
	 * The player should has betted and has the data in the game_logs.
	 * @return array The player list, event ony one record.
	 * The array will be the following format,
	 * - $rows[0][id] integer The field, "game_logs.id".
	 * - $rows[0][player_id] integer The field, "player.player_id".
	 * - $rows[0][game_platform_id] integer The field, "game_logs.game_platform_id".
	 * - $rows[0][game_type_id] integer The field, "game_logs.game_type_id".
	 * - $rows[0][game_description_id] integer The field, "game_logs.game_description_id".
	 * - $rows[0][start_at] integer The field, "game_logs.start_at".
	 * - $rows[0][groupName] string The field, "vipsetting.groupName" after lang().
	 * - $rows[0][levelName] string The field, "vipsettingcashbackrule.vipLevelName" after lang().
	 * - $rows[0][vipsettingcashbackruleId] integer The field, "vipsettingcashbackrule.vipsettingcashbackruleId".
	 * - $rows[0][game_name] string The field, "game_description.game_name".
	 * - $rows[0][game_type] string The field, "game_type.game_type_lang" after lang().
	 * - $rows[0][game_type_lang] string The field, "game_type.game_type_lang".
	 * - $rows[0][system_code] string The field, "external_system.system_code".
	 * - $rows[0][game_platform] string The field, "external_system.system_code" after lang().
	 *
	 */
	public function _searchTestPlayerByPlayerId($thePlayerId){
		$this->CI->load->model(['group_level','player']);

		// $this->load->model(['group_level']);
		$sql =<<<EOF
SELECT id
, player_id
, player_username
, game_platform_id
, game_type_id
, game_description_id
, start_at
FROM game_logs
WHERE player_id = ?
ORDER BY start_at DESC
LIMIT 1;
EOF;
		$params = [];
		//"-7 days"
		$params[] = $thePlayerId;
		$rows = $this->CI->group_level->runRawArraySelectSQL($sql, $params);
		if( ! empty($rows) ){

			foreach($rows as $indexNumber => $row){
				$playerLevel = $this->CI->player->getPlayerCurrentLevel($row['player_id']);
				$rows[$indexNumber]['groupName'] = lang($playerLevel[0]['groupName']);
				$rows[$indexNumber]['levelName'] = lang($playerLevel[0]['vipLevelName']);
				$rows[$indexNumber]['vipsettingcashbackruleId'] = $playerLevel[0]['vipsettingcashbackruleId'];
			}

			foreach($rows as $indexNumber => $row){
				$sql = <<<EOF
select game_description.game_name
, game_type.game_type_lang
, external_system.system_code
from game_description
inner join game_type on game_description.game_type_id = game_type.id
inner join external_system on game_description.game_platform_id = external_system.id
where game_description.id = ?
EOF;
				$params = [];
				$params[] = $row['game_description_id'];
				$game_descriptionList = $this->CI->group_level->runRawArraySelectSQL($sql, $params);
				if( ! empty($game_descriptionList) ){
					$rows[$indexNumber]['game_name'] = lang($game_descriptionList[0]['game_name']);
					$rows[$indexNumber]['game_type'] = lang($game_descriptionList[0]['game_type_lang']);
					$rows[$indexNumber]['game_platform'] = lang($game_descriptionList[0]['system_code']);
				}
			}

		}

		return $rows;
	}// EOF _searchTestPlayerByPlayerId

	/**
	 * Search the players who had betting in game_logs at the lastest days
	 *
	 * @todo To use the method,self::_searchTestPlayerByPlayerId() for replace the part, to get the fields other than player_id.
	 * @param integer $offsetDayRange the lastest days number.
	 * @param integer $limit Get the amount, how many players to get.
	 * @return array The player list contains the game intro of had betting.
	 */
	public function _searchTestPlayerList4FromSBE($offsetDayRange = '7', $limit = 10){
		$this->CI->load->model(['group_level','player']);
		$this->CI->load->library(['utils']);
		$_limit = $limit * 100;// for enough samples to catch the data
		$sql =<<<EOF
SELECT player_game_logs.*
, vipsettingcashbackrule.vipLevel
, vip_grade_report.request_time
FROM (
	SELECT id
	, player_id
	, player_username
	, game_platform_id
	, game_type_id
	, game_description_id
	, start_at
	FROM game_logs
	WHERE start_at > ?
	-- GROUP BY player_id
	LIMIT $_limit
) as player_game_logs
RIGHT JOIN playerlevel ON playerlevel.playerId = player_game_logs.player_id
RIGHT JOIN vipsettingcashbackrule ON playerlevel.playerGroupId = vipsettingcashbackrule.vipsettingcashbackruleId
RIGHT JOIN vip_grade_report ON vip_grade_report.player_id = player_game_logs.player_id AND vip_grade_report.request_time < ?
WHERE vipsettingcashbackrule.vipLevel > 1 -- Not in the lowest level for downgrade test
AND DATEDIFF(start_at, request_time) < 365 -- The during time between start_at to request_time. The 7 days for local database, please adjust param by the current database.
GROUP BY player_id
ORDER BY start_at DESC
LIMIT $limit
EOF;
		$offsetDayRangeDatetime = new \DateTime("-$offsetDayRange days");
		$offsetDayRangeDateTimeForMysql = $this->CI->utils->formatDateTimeForMysql( $offsetDayRangeDatetime );
		$params = [];
		//"-7 days"
		$params[] = $offsetDayRangeDateTimeForMysql;
		$params[] = $offsetDayRangeDateTimeForMysql;
		$rows = $this->CI->group_level->runRawArraySelectSQL($sql, $params);
// print_r($this->CI->group_level->db->last_query());
		if( ! empty($rows) ){

			foreach($rows as $indexNumber => $row){
				$playerLevel = $this->CI->player->getPlayerCurrentLevel($row['player_id']);
				$rows[$indexNumber]['groupName'] = lang($playerLevel[0]['groupName']);
				$rows[$indexNumber]['levelName'] = lang($playerLevel[0]['vipLevelName']);
				$rows[$indexNumber]['vipsettingcashbackruleId'] = $playerLevel[0]['vipsettingcashbackruleId'];
			}

			foreach($rows as $indexNumber => $row){
				$sql = <<<EOF
select game_description.game_name
, game_type.game_type_lang
, external_system.system_code
from game_description
inner join game_type on game_description.game_type_id = game_type.id
inner join external_system on game_description.game_platform_id = external_system.id
where game_description.id = ?
EOF;
				$params = [];
				$params[] = $row['game_description_id'];
				$game_descriptionList = $this->CI->group_level->runRawArraySelectSQL($sql, $params);
				if( ! empty($game_descriptionList) ){
					$rows[$indexNumber]['game_name'] = lang($game_descriptionList[0]['game_name']);
					$rows[$indexNumber]['game_type'] = lang($game_descriptionList[0]['game_type_lang']);
					$rows[$indexNumber]['game_platform'] = lang($game_descriptionList[0]['system_code']);
				}
			}

		}

		return $rows;

	 } // EOF _searchTestPlayerList4FromSBE

	public function _searchTestPlayerListFilteredLowestLevel($offsetDayRange = '7', $limit = 10){
		$this->CI->load->model(['group_level','player']);
		$this->CI->load->library(['utils']);
		$_limit = $limit * 100;
		$sql =<<<EOF
SELECT player_game_logs.*
, vipsettingcashbackrule.vipLevel
FROM (
	SELECT id
	, player_id
	, player_username
	, game_platform_id
	, game_type_id
	, game_description_id
	, start_at
	FROM game_logs
	WHERE start_at > ?
	-- GROUP BY player_id
	LIMIT $_limit
) as player_game_logs
RIGHT JOIN playerlevel ON playerlevel.playerId = player_game_logs.player_id
RIGHT JOIN vipsettingcashbackrule ON playerlevel.playerGroupId = vipsettingcashbackrule.vipsettingcashbackruleId
WHERE vipsettingcashbackrule.vipLevel > 1
GROUP BY player_id
ORDER BY start_at DESC
LIMIT $limit
EOF;
		$params = [];
		//"-7 days"
		$params[] = $this->CI->utils->formatDateTimeForMysql(new \DateTime("-$offsetDayRange days") );
		$rows = $this->CI->group_level->runRawArraySelectSQL($sql, $params);

		if( ! empty($rows) ){

			foreach($rows as $indexNumber => $row){
				$playerLevel = $this->CI->player->getPlayerCurrentLevel($row['player_id']);
				$rows[$indexNumber]['groupName'] = lang($playerLevel[0]['groupName']);
				$rows[$indexNumber]['levelName'] = lang($playerLevel[0]['vipLevelName']);
				$rows[$indexNumber]['vipsettingcashbackruleId'] = $playerLevel[0]['vipsettingcashbackruleId'];
			}

			foreach($rows as $indexNumber => $row){
				$sql = <<<EOF
select game_description.game_name
, game_type.game_type_lang
, external_system.system_code
from game_description
inner join game_type on game_description.game_type_id = game_type.id
inner join external_system on game_description.game_platform_id = external_system.id
where game_description.id = ?
EOF;
				$params = [];
				$params[] = $row['game_description_id'];
				$game_descriptionList = $this->CI->group_level->runRawArraySelectSQL($sql, $params);
				if( ! empty($game_descriptionList) ){
					$rows[$indexNumber]['game_name'] = lang($game_descriptionList[0]['game_name']);
					$rows[$indexNumber]['game_type'] = lang($game_descriptionList[0]['game_type_lang']);
					$rows[$indexNumber]['game_platform'] = lang($game_descriptionList[0]['system_code']);
				}
			}

		}

		return $rows;

	 } // EOF _searchTestPlayerListFilteredLowestLevel


	/**
	 * Search the players who had betting in game_logs at the lastest days
	 *
	 * @todo To use the method,self::_searchTestPlayerByPlayerId() for replace the part, to get the fields other than player_id.
	 *
	 * @param integer $offsetDayRange the lastest days number.
	 * @param integer $limit Get the amount, how many players to get.
	 * @return array The player list contains the game intro of had betting.
	 */
	public function _searchTestPlayerList($offsetDayRange = '7', $limit = 10){
		$this->CI->load->model(['group_level','player']);
		$this->CI->load->library(['utils']);
		$sql =<<<EOF
SELECT *
FROM (
	SELECT id
	, player_id
	, player_username
	, game_platform_id
	, game_type_id
	, game_description_id
	, start_at
	FROM game_logs
	WHERE start_at > ?
	GROUP BY player_id
	LIMIT $limit
) as tmp
ORDER BY start_at DESC
EOF;
		$params = [];
		//"-7 days"
		$params[] = $this->CI->utils->formatDateTimeForMysql(new \DateTime("-$offsetDayRange days") );
		$rows = $this->CI->group_level->runRawArraySelectSQL($sql, $params);

		if( ! empty($rows) ){

			foreach($rows as $indexNumber => $row){
				$playerLevel = $this->CI->player->getPlayerCurrentLevel($row['player_id']);
				$rows[$indexNumber]['groupName'] = lang($playerLevel[0]['groupName']);
				$rows[$indexNumber]['levelName'] = lang($playerLevel[0]['vipLevelName']);
				$rows[$indexNumber]['vipsettingcashbackruleId'] = $playerLevel[0]['vipsettingcashbackruleId'];
			}

			foreach($rows as $indexNumber => $row){
				$sql = <<<EOF
select game_description.game_name
, game_type.game_type_lang
, external_system.system_code
from game_description
inner join game_type on game_description.game_type_id = game_type.id
inner join external_system on game_description.game_platform_id = external_system.id
where game_description.id = ?
EOF;
				$params = [];
				$params[] = $row['game_description_id'];
				$game_descriptionList = $this->CI->group_level->runRawArraySelectSQL($sql, $params);
				if( ! empty($game_descriptionList) ){
					$rows[$indexNumber]['game_name'] = lang($game_descriptionList[0]['game_name']);
					$rows[$indexNumber]['game_type'] = lang($game_descriptionList[0]['game_type_lang']);
					$rows[$indexNumber]['game_platform'] = lang($game_descriptionList[0]['system_code']);
				}
			}

		}

		return $rows;
	} // EOF _searchTestPlayerList()

	public function _getIngoreCombinedCases4ogp21818(){
		$ignoreTestCaseKeyList = [];
		/// DailyPeriodMode.PeriodNotMet
			// CACB
			$ignoreTestCaseKeyList[] = 'CACB.DailyPeriodMode.PeriodNotMet.OnLevelMaintainEnable.InMaintainTime.IsMetLevelMaintainCondition.AccumulationYesRegistrationDate.EmptyUpgradeSetting.IsDowngradeConditionMet';
			$ignoreTestCaseKeyList[] = 'CACB.DailyPeriodMode.PeriodNotMet.OnLevelMaintainEnable.InMaintainTime.IsMetLevelMaintainCondition.AccumulationYesRegistrationDate.EmptyUpgradeSetting.IsDowngradeConditionNotMet';
			$ignoreTestCaseKeyList[] = 'CACB.DailyPeriodMode.PeriodNotMet.OnLevelMaintainEnable.InMaintainTime.IsMetLevelMaintainCondition.AccumulationYesRegistrationDate.DepositUpgradeSetting.IsDowngradeConditionMet';
			$ignoreTestCaseKeyList[] = 'CACB.DailyPeriodMode.PeriodNotMet.OnLevelMaintainEnable.InMaintainTime.IsMetLevelMaintainCondition.AccumulationYesRegistrationDate.DepositUpgradeSetting.IsDowngradeConditionNotMet';
			$ignoreTestCaseKeyList[] = 'CACB.DailyPeriodMode.PeriodNotMet.OnLevelMaintainEnable.InMaintainTime.IsMetLevelMaintainCondition.AccumulationYesRegistrationDate.BetUpgradeSetting.IsDowngradeConditionMet';
			$ignoreTestCaseKeyList[] = 'CACB.DailyPeriodMode.PeriodNotMet.OnLevelMaintainEnable.InMaintainTime.IsMetLevelMaintainCondition.AccumulationYesRegistrationDate.BetUpgradeSetting.IsDowngradeConditionNotMet';
			$ignoreTestCaseKeyList[] = 'CACB.DailyPeriodMode.PeriodNotMet.OnLevelMaintainEnable.InMaintainTime.IsMetLevelMaintainCondition.AccumulationYesRegistrationDate.DepositBetUpgradeSetting.IsDowngradeConditionMet';
			$ignoreTestCaseKeyList[] = 'CACB.DailyPeriodMode.PeriodNotMet.OnLevelMaintainEnable.InMaintainTime.IsMetLevelMaintainCondition.AccumulationYesRegistrationDate.DepositBetUpgradeSetting.IsDowngradeConditionNotMet';
			$ignoreTestCaseKeyList[] = 'CACB.DailyPeriodMode.PeriodNotMet.OnLevelMaintainEnable.InMaintainTime.NotMetLevelMaintainCondition.AccumulationYesRegistrationDate.EmptyUpgradeSetting.IsDowngradeConditionMet';
			$ignoreTestCaseKeyList[] = 'CACB.DailyPeriodMode.PeriodNotMet.OnLevelMaintainEnable.InMaintainTime.NotMetLevelMaintainCondition.AccumulationYesRegistrationDate.EmptyUpgradeSetting.IsDowngradeConditionNotMet';
			$ignoreTestCaseKeyList[] = 'CACB.DailyPeriodMode.PeriodNotMet.OnLevelMaintainEnable.InMaintainTime.NotMetLevelMaintainCondition.AccumulationYesRegistrationDate.DepositUpgradeSetting.IsDowngradeConditionMet';
			$ignoreTestCaseKeyList[] = 'CACB.DailyPeriodMode.PeriodNotMet.OnLevelMaintainEnable.InMaintainTime.NotMetLevelMaintainCondition.AccumulationYesRegistrationDate.DepositUpgradeSetting.IsDowngradeConditionNotMet';
			$ignoreTestCaseKeyList[] = 'CACB.DailyPeriodMode.PeriodNotMet.OnLevelMaintainEnable.InMaintainTime.NotMetLevelMaintainCondition.AccumulationYesRegistrationDate.BetUpgradeSetting.IsDowngradeConditionMet';
			$ignoreTestCaseKeyList[] = 'CACB.DailyPeriodMode.PeriodNotMet.OnLevelMaintainEnable.InMaintainTime.NotMetLevelMaintainCondition.AccumulationYesRegistrationDate.BetUpgradeSetting.IsDowngradeConditionNotMet';
			$ignoreTestCaseKeyList[] = 'CACB.DailyPeriodMode.PeriodNotMet.OnLevelMaintainEnable.InMaintainTime.NotMetLevelMaintainCondition.AccumulationYesRegistrationDate.DepositBetUpgradeSetting.IsDowngradeConditionMet';
			$ignoreTestCaseKeyList[] = 'CACB.DailyPeriodMode.PeriodNotMet.OnLevelMaintainEnable.InMaintainTime.NotMetLevelMaintainCondition.AccumulationYesRegistrationDate.DepositBetUpgradeSetting.IsDowngradeConditionNotMet';
			$ignoreTestCaseKeyList[] = 'CACB.DailyPeriodMode.PeriodNotMet.OnLevelMaintainEnable.OverMaintainTime.IsMetLevelMaintainCondition.AccumulationYesRegistrationDate.EmptyUpgradeSetting.IsDowngradeConditionMet';
			$ignoreTestCaseKeyList[] = 'CACB.DailyPeriodMode.PeriodNotMet.OnLevelMaintainEnable.OverMaintainTime.IsMetLevelMaintainCondition.AccumulationYesRegistrationDate.EmptyUpgradeSetting.IsDowngradeConditionNotMet';
			$ignoreTestCaseKeyList[] = 'CACB.DailyPeriodMode.PeriodNotMet.OnLevelMaintainEnable.OverMaintainTime.IsMetLevelMaintainCondition.AccumulationYesRegistrationDate.DepositUpgradeSetting.IsDowngradeConditionMet';
			$ignoreTestCaseKeyList[] = 'CACB.DailyPeriodMode.PeriodNotMet.OnLevelMaintainEnable.OverMaintainTime.IsMetLevelMaintainCondition.AccumulationYesRegistrationDate.DepositUpgradeSetting.IsDowngradeConditionNotMet';
			$ignoreTestCaseKeyList[] = 'CACB.DailyPeriodMode.PeriodNotMet.OnLevelMaintainEnable.OverMaintainTime.IsMetLevelMaintainCondition.AccumulationYesRegistrationDate.BetUpgradeSetting.IsDowngradeConditionMet';
			$ignoreTestCaseKeyList[] = 'CACB.DailyPeriodMode.PeriodNotMet.OnLevelMaintainEnable.OverMaintainTime.IsMetLevelMaintainCondition.AccumulationYesRegistrationDate.BetUpgradeSetting.IsDowngradeConditionNotMet';
			$ignoreTestCaseKeyList[] = 'CACB.DailyPeriodMode.PeriodNotMet.OnLevelMaintainEnable.OverMaintainTime.IsMetLevelMaintainCondition.AccumulationYesRegistrationDate.DepositBetUpgradeSetting.IsDowngradeConditionMet';
			$ignoreTestCaseKeyList[] = 'CACB.DailyPeriodMode.PeriodNotMet.OnLevelMaintainEnable.OverMaintainTime.IsMetLevelMaintainCondition.AccumulationYesRegistrationDate.DepositBetUpgradeSetting.IsDowngradeConditionNotMet';
			$ignoreTestCaseKeyList[] = 'CACB.DailyPeriodMode.PeriodNotMet.OnLevelMaintainEnable.OverMaintainTime.NotMetLevelMaintainCondition.AccumulationYesRegistrationDate.EmptyUpgradeSetting.IsDowngradeConditionMet';
			$ignoreTestCaseKeyList[] = 'CACB.DailyPeriodMode.PeriodNotMet.OnLevelMaintainEnable.OverMaintainTime.NotMetLevelMaintainCondition.AccumulationYesRegistrationDate.EmptyUpgradeSetting.IsDowngradeConditionNotMet';
			$ignoreTestCaseKeyList[] = 'CACB.DailyPeriodMode.PeriodNotMet.OnLevelMaintainEnable.OverMaintainTime.NotMetLevelMaintainCondition.AccumulationYesRegistrationDate.DepositUpgradeSetting.IsDowngradeConditionMet';
			$ignoreTestCaseKeyList[] = 'CACB.DailyPeriodMode.PeriodNotMet.OnLevelMaintainEnable.OverMaintainTime.NotMetLevelMaintainCondition.AccumulationYesRegistrationDate.DepositUpgradeSetting.IsDowngradeConditionNotMet';
			$ignoreTestCaseKeyList[] = 'CACB.DailyPeriodMode.PeriodNotMet.OnLevelMaintainEnable.OverMaintainTime.NotMetLevelMaintainCondition.AccumulationYesRegistrationDate.BetUpgradeSetting.IsDowngradeConditionMet';
			$ignoreTestCaseKeyList[] = 'CACB.DailyPeriodMode.PeriodNotMet.OnLevelMaintainEnable.OverMaintainTime.NotMetLevelMaintainCondition.AccumulationYesRegistrationDate.BetUpgradeSetting.IsDowngradeConditionNotMet';
			$ignoreTestCaseKeyList[] = 'CACB.DailyPeriodMode.PeriodNotMet.OnLevelMaintainEnable.OverMaintainTime.NotMetLevelMaintainCondition.AccumulationYesRegistrationDate.DepositBetUpgradeSetting.IsDowngradeConditionMet';
			$ignoreTestCaseKeyList[] = 'CACB.DailyPeriodMode.PeriodNotMet.OnLevelMaintainEnable.OverMaintainTime.NotMetLevelMaintainCondition.AccumulationYesRegistrationDate.DepositBetUpgradeSetting.IsDowngradeConditionNotMet';
			$ignoreTestCaseKeyList[] = 'CACB.DailyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.InMaintainTime.IsMetLevelMaintainCondition.AccumulationYesRegistrationDate.EmptyUpgradeSetting.IsDowngradeConditionMet';
			$ignoreTestCaseKeyList[] = 'CACB.DailyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.InMaintainTime.IsMetLevelMaintainCondition.AccumulationYesRegistrationDate.EmptyUpgradeSetting.IsDowngradeConditionNotMet';
			$ignoreTestCaseKeyList[] = 'CACB.DailyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.InMaintainTime.IsMetLevelMaintainCondition.AccumulationYesRegistrationDate.DepositUpgradeSetting.IsDowngradeConditionMet';
			$ignoreTestCaseKeyList[] = 'CACB.DailyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.InMaintainTime.IsMetLevelMaintainCondition.AccumulationYesRegistrationDate.DepositUpgradeSetting.IsDowngradeConditionNotMet';
			$ignoreTestCaseKeyList[] = 'CACB.DailyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.InMaintainTime.IsMetLevelMaintainCondition.AccumulationYesRegistrationDate.BetUpgradeSetting.IsDowngradeConditionMet';
			$ignoreTestCaseKeyList[] = 'CACB.DailyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.InMaintainTime.IsMetLevelMaintainCondition.AccumulationYesRegistrationDate.BetUpgradeSetting.IsDowngradeConditionNotMet';
			$ignoreTestCaseKeyList[] = 'CACB.DailyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.InMaintainTime.IsMetLevelMaintainCondition.AccumulationYesRegistrationDate.DepositBetUpgradeSetting.IsDowngradeConditionMet';
			$ignoreTestCaseKeyList[] = 'CACB.DailyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.InMaintainTime.IsMetLevelMaintainCondition.AccumulationYesRegistrationDate.DepositBetUpgradeSetting.IsDowngradeConditionNotMet';
			$ignoreTestCaseKeyList[] = 'CACB.DailyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.InMaintainTime.NotMetLevelMaintainCondition.AccumulationYesRegistrationDate.EmptyUpgradeSetting.IsDowngradeConditionMet';
			$ignoreTestCaseKeyList[] = 'CACB.DailyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.InMaintainTime.NotMetLevelMaintainCondition.AccumulationYesRegistrationDate.EmptyUpgradeSetting.IsDowngradeConditionNotMet';
			$ignoreTestCaseKeyList[] = 'CACB.DailyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.InMaintainTime.NotMetLevelMaintainCondition.AccumulationYesRegistrationDate.DepositUpgradeSetting.IsDowngradeConditionMet';
			$ignoreTestCaseKeyList[] = 'CACB.DailyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.InMaintainTime.NotMetLevelMaintainCondition.AccumulationYesRegistrationDate.DepositUpgradeSetting.IsDowngradeConditionNotMet';
			$ignoreTestCaseKeyList[] = 'CACB.DailyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.InMaintainTime.NotMetLevelMaintainCondition.AccumulationYesRegistrationDate.BetUpgradeSetting.IsDowngradeConditionMet';
			$ignoreTestCaseKeyList[] = 'CACB.DailyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.InMaintainTime.NotMetLevelMaintainCondition.AccumulationYesRegistrationDate.BetUpgradeSetting.IsDowngradeConditionNotMet';
			$ignoreTestCaseKeyList[] = 'CACB.DailyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.InMaintainTime.NotMetLevelMaintainCondition.AccumulationYesRegistrationDate.DepositBetUpgradeSetting.IsDowngradeConditionMet';
			$ignoreTestCaseKeyList[] = 'CACB.DailyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.InMaintainTime.NotMetLevelMaintainCondition.AccumulationYesRegistrationDate.DepositBetUpgradeSetting.IsDowngradeConditionNotMet';
			$ignoreTestCaseKeyList[] = 'CACB.DailyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.OverMaintainTime.IsMetLevelMaintainCondition.AccumulationYesRegistrationDate.EmptyUpgradeSetting.IsDowngradeConditionMet';
			$ignoreTestCaseKeyList[] = 'CACB.DailyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.OverMaintainTime.IsMetLevelMaintainCondition.AccumulationYesRegistrationDate.EmptyUpgradeSetting.IsDowngradeConditionNotMet';
			$ignoreTestCaseKeyList[] = 'CACB.DailyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.OverMaintainTime.IsMetLevelMaintainCondition.AccumulationYesRegistrationDate.DepositUpgradeSetting.IsDowngradeConditionMet';
			$ignoreTestCaseKeyList[] = 'CACB.DailyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.OverMaintainTime.IsMetLevelMaintainCondition.AccumulationYesRegistrationDate.DepositUpgradeSetting.IsDowngradeConditionNotMet';
			$ignoreTestCaseKeyList[] = 'CACB.DailyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.OverMaintainTime.IsMetLevelMaintainCondition.AccumulationYesRegistrationDate.BetUpgradeSetting.IsDowngradeConditionMet';
			$ignoreTestCaseKeyList[] = 'CACB.DailyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.OverMaintainTime.IsMetLevelMaintainCondition.AccumulationYesRegistrationDate.BetUpgradeSetting.IsDowngradeConditionNotMet';
			$ignoreTestCaseKeyList[] = 'CACB.DailyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.OverMaintainTime.IsMetLevelMaintainCondition.AccumulationYesRegistrationDate.DepositBetUpgradeSetting.IsDowngradeConditionMet';
			$ignoreTestCaseKeyList[] = 'CACB.DailyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.OverMaintainTime.IsMetLevelMaintainCondition.AccumulationYesRegistrationDate.DepositBetUpgradeSetting.IsDowngradeConditionNotMet';
			$ignoreTestCaseKeyList[] = 'CACB.DailyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.OverMaintainTime.NotMetLevelMaintainCondition.AccumulationYesRegistrationDate.EmptyUpgradeSetting.IsDowngradeConditionMet';
			$ignoreTestCaseKeyList[] = 'CACB.DailyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.OverMaintainTime.NotMetLevelMaintainCondition.AccumulationYesRegistrationDate.EmptyUpgradeSetting.IsDowngradeConditionNotMet';
			$ignoreTestCaseKeyList[] = 'CACB.DailyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.OverMaintainTime.NotMetLevelMaintainCondition.AccumulationYesRegistrationDate.DepositUpgradeSetting.IsDowngradeConditionMet';
			$ignoreTestCaseKeyList[] = 'CACB.DailyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.OverMaintainTime.NotMetLevelMaintainCondition.AccumulationYesRegistrationDate.DepositUpgradeSetting.IsDowngradeConditionNotMet';
			$ignoreTestCaseKeyList[] = 'CACB.DailyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.OverMaintainTime.NotMetLevelMaintainCondition.AccumulationYesRegistrationDate.BetUpgradeSetting.IsDowngradeConditionMet';
			$ignoreTestCaseKeyList[] = 'CACB.DailyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.OverMaintainTime.NotMetLevelMaintainCondition.AccumulationYesRegistrationDate.BetUpgradeSetting.IsDowngradeConditionNotMet';
			$ignoreTestCaseKeyList[] = 'CACB.DailyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.OverMaintainTime.NotMetLevelMaintainCondition.AccumulationYesRegistrationDate.DepositBetUpgradeSetting.IsDowngradeConditionMet';
			$ignoreTestCaseKeyList[] = 'CACB.DailyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.OverMaintainTime.NotMetLevelMaintainCondition.AccumulationYesRegistrationDate.DepositBetUpgradeSetting.IsDowngradeConditionNotMet';
			// SACB
			$ignoreTestCaseKeyList[] = 'SACB.DailyPeriodMode.PeriodNotMet.OnLevelMaintainEnable.InMaintainTime.IsMetLevelMaintainCondition.AccumulationYesRegistrationDate.EmptyUpgradeSetting.IsDowngradeConditionMet';
			$ignoreTestCaseKeyList[] = 'SACB.DailyPeriodMode.PeriodNotMet.OnLevelMaintainEnable.InMaintainTime.IsMetLevelMaintainCondition.AccumulationYesRegistrationDate.EmptyUpgradeSetting.IsDowngradeConditionNotMet';
			$ignoreTestCaseKeyList[] = 'SACB.DailyPeriodMode.PeriodNotMet.OnLevelMaintainEnable.InMaintainTime.IsMetLevelMaintainCondition.AccumulationYesRegistrationDate.DepositUpgradeSetting.IsDowngradeConditionMet';
			$ignoreTestCaseKeyList[] = 'SACB.DailyPeriodMode.PeriodNotMet.OnLevelMaintainEnable.InMaintainTime.IsMetLevelMaintainCondition.AccumulationYesRegistrationDate.DepositUpgradeSetting.IsDowngradeConditionNotMet';
			$ignoreTestCaseKeyList[] = 'SACB.DailyPeriodMode.PeriodNotMet.OnLevelMaintainEnable.InMaintainTime.IsMetLevelMaintainCondition.AccumulationYesRegistrationDate.BetUpgradeSetting.IsDowngradeConditionMet';
			$ignoreTestCaseKeyList[] = 'SACB.DailyPeriodMode.PeriodNotMet.OnLevelMaintainEnable.InMaintainTime.IsMetLevelMaintainCondition.AccumulationYesRegistrationDate.BetUpgradeSetting.IsDowngradeConditionNotMet';
			$ignoreTestCaseKeyList[] = 'SACB.DailyPeriodMode.PeriodNotMet.OnLevelMaintainEnable.InMaintainTime.IsMetLevelMaintainCondition.AccumulationYesRegistrationDate.DepositBetUpgradeSetting.IsDowngradeConditionMet';
			$ignoreTestCaseKeyList[] = 'SACB.DailyPeriodMode.PeriodNotMet.OnLevelMaintainEnable.InMaintainTime.IsMetLevelMaintainCondition.AccumulationYesRegistrationDate.DepositBetUpgradeSetting.IsDowngradeConditionNotMet';
			$ignoreTestCaseKeyList[] = 'SACB.DailyPeriodMode.PeriodNotMet.OnLevelMaintainEnable.InMaintainTime.NotMetLevelMaintainCondition.AccumulationYesRegistrationDate.EmptyUpgradeSetting.IsDowngradeConditionMet';
			$ignoreTestCaseKeyList[] = 'SACB.DailyPeriodMode.PeriodNotMet.OnLevelMaintainEnable.InMaintainTime.NotMetLevelMaintainCondition.AccumulationYesRegistrationDate.EmptyUpgradeSetting.IsDowngradeConditionNotMet';
			$ignoreTestCaseKeyList[] = 'SACB.DailyPeriodMode.PeriodNotMet.OnLevelMaintainEnable.InMaintainTime.NotMetLevelMaintainCondition.AccumulationYesRegistrationDate.DepositUpgradeSetting.IsDowngradeConditionMet';
			$ignoreTestCaseKeyList[] = 'SACB.DailyPeriodMode.PeriodNotMet.OnLevelMaintainEnable.InMaintainTime.NotMetLevelMaintainCondition.AccumulationYesRegistrationDate.DepositUpgradeSetting.IsDowngradeConditionNotMet';
			$ignoreTestCaseKeyList[] = 'SACB.DailyPeriodMode.PeriodNotMet.OnLevelMaintainEnable.InMaintainTime.NotMetLevelMaintainCondition.AccumulationYesRegistrationDate.BetUpgradeSetting.IsDowngradeConditionMet';
			$ignoreTestCaseKeyList[] = 'SACB.DailyPeriodMode.PeriodNotMet.OnLevelMaintainEnable.InMaintainTime.NotMetLevelMaintainCondition.AccumulationYesRegistrationDate.BetUpgradeSetting.IsDowngradeConditionNotMet';
			$ignoreTestCaseKeyList[] = 'SACB.DailyPeriodMode.PeriodNotMet.OnLevelMaintainEnable.InMaintainTime.NotMetLevelMaintainCondition.AccumulationYesRegistrationDate.DepositBetUpgradeSetting.IsDowngradeConditionMet';
			$ignoreTestCaseKeyList[] = 'SACB.DailyPeriodMode.PeriodNotMet.OnLevelMaintainEnable.InMaintainTime.NotMetLevelMaintainCondition.AccumulationYesRegistrationDate.DepositBetUpgradeSetting.IsDowngradeConditionNotMet';
			$ignoreTestCaseKeyList[] = 'SACB.DailyPeriodMode.PeriodNotMet.OnLevelMaintainEnable.OverMaintainTime.IsMetLevelMaintainCondition.AccumulationYesRegistrationDate.EmptyUpgradeSetting.IsDowngradeConditionMet';
			$ignoreTestCaseKeyList[] = 'SACB.DailyPeriodMode.PeriodNotMet.OnLevelMaintainEnable.OverMaintainTime.IsMetLevelMaintainCondition.AccumulationYesRegistrationDate.EmptyUpgradeSetting.IsDowngradeConditionNotMet';
			$ignoreTestCaseKeyList[] = 'SACB.DailyPeriodMode.PeriodNotMet.OnLevelMaintainEnable.OverMaintainTime.IsMetLevelMaintainCondition.AccumulationYesRegistrationDate.DepositUpgradeSetting.IsDowngradeConditionMet';
			$ignoreTestCaseKeyList[] = 'SACB.DailyPeriodMode.PeriodNotMet.OnLevelMaintainEnable.OverMaintainTime.IsMetLevelMaintainCondition.AccumulationYesRegistrationDate.DepositUpgradeSetting.IsDowngradeConditionNotMet';
			$ignoreTestCaseKeyList[] = 'SACB.DailyPeriodMode.PeriodNotMet.OnLevelMaintainEnable.OverMaintainTime.IsMetLevelMaintainCondition.AccumulationYesRegistrationDate.BetUpgradeSetting.IsDowngradeConditionMet';
			$ignoreTestCaseKeyList[] = 'SACB.DailyPeriodMode.PeriodNotMet.OnLevelMaintainEnable.OverMaintainTime.IsMetLevelMaintainCondition.AccumulationYesRegistrationDate.BetUpgradeSetting.IsDowngradeConditionNotMet';
			$ignoreTestCaseKeyList[] = 'SACB.DailyPeriodMode.PeriodNotMet.OnLevelMaintainEnable.OverMaintainTime.IsMetLevelMaintainCondition.AccumulationYesRegistrationDate.DepositBetUpgradeSetting.IsDowngradeConditionMet';
			$ignoreTestCaseKeyList[] = 'SACB.DailyPeriodMode.PeriodNotMet.OnLevelMaintainEnable.OverMaintainTime.IsMetLevelMaintainCondition.AccumulationYesRegistrationDate.DepositBetUpgradeSetting.IsDowngradeConditionNotMet';
			$ignoreTestCaseKeyList[] = 'SACB.DailyPeriodMode.PeriodNotMet.OnLevelMaintainEnable.OverMaintainTime.NotMetLevelMaintainCondition.AccumulationYesRegistrationDate.EmptyUpgradeSetting.IsDowngradeConditionMet';
			$ignoreTestCaseKeyList[] = 'SACB.DailyPeriodMode.PeriodNotMet.OnLevelMaintainEnable.OverMaintainTime.NotMetLevelMaintainCondition.AccumulationYesRegistrationDate.EmptyUpgradeSetting.IsDowngradeConditionNotMet';
			$ignoreTestCaseKeyList[] = 'SACB.DailyPeriodMode.PeriodNotMet.OnLevelMaintainEnable.OverMaintainTime.NotMetLevelMaintainCondition.AccumulationYesRegistrationDate.DepositUpgradeSetting.IsDowngradeConditionMet';
			$ignoreTestCaseKeyList[] = 'SACB.DailyPeriodMode.PeriodNotMet.OnLevelMaintainEnable.OverMaintainTime.NotMetLevelMaintainCondition.AccumulationYesRegistrationDate.DepositUpgradeSetting.IsDowngradeConditionNotMet';
			$ignoreTestCaseKeyList[] = 'SACB.DailyPeriodMode.PeriodNotMet.OnLevelMaintainEnable.OverMaintainTime.NotMetLevelMaintainCondition.AccumulationYesRegistrationDate.BetUpgradeSetting.IsDowngradeConditionMet';
			$ignoreTestCaseKeyList[] = 'SACB.DailyPeriodMode.PeriodNotMet.OnLevelMaintainEnable.OverMaintainTime.NotMetLevelMaintainCondition.AccumulationYesRegistrationDate.BetUpgradeSetting.IsDowngradeConditionNotMet';
			$ignoreTestCaseKeyList[] = 'SACB.DailyPeriodMode.PeriodNotMet.OnLevelMaintainEnable.OverMaintainTime.NotMetLevelMaintainCondition.AccumulationYesRegistrationDate.DepositBetUpgradeSetting.IsDowngradeConditionMet';
			$ignoreTestCaseKeyList[] = 'SACB.DailyPeriodMode.PeriodNotMet.OnLevelMaintainEnable.OverMaintainTime.NotMetLevelMaintainCondition.AccumulationYesRegistrationDate.DepositBetUpgradeSetting.IsDowngradeConditionNotMet';
			$ignoreTestCaseKeyList[] = 'SACB.DailyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.InMaintainTime.IsMetLevelMaintainCondition.AccumulationYesRegistrationDate.EmptyUpgradeSetting.IsDowngradeConditionMet';
			$ignoreTestCaseKeyList[] = 'SACB.DailyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.InMaintainTime.IsMetLevelMaintainCondition.AccumulationYesRegistrationDate.EmptyUpgradeSetting.IsDowngradeConditionNotMet';
			$ignoreTestCaseKeyList[] = 'SACB.DailyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.InMaintainTime.IsMetLevelMaintainCondition.AccumulationYesRegistrationDate.DepositUpgradeSetting.IsDowngradeConditionMet';
			$ignoreTestCaseKeyList[] = 'SACB.DailyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.InMaintainTime.IsMetLevelMaintainCondition.AccumulationYesRegistrationDate.DepositUpgradeSetting.IsDowngradeConditionNotMet';
			$ignoreTestCaseKeyList[] = 'SACB.DailyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.InMaintainTime.IsMetLevelMaintainCondition.AccumulationYesRegistrationDate.BetUpgradeSetting.IsDowngradeConditionMet';
			$ignoreTestCaseKeyList[] = 'SACB.DailyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.InMaintainTime.IsMetLevelMaintainCondition.AccumulationYesRegistrationDate.BetUpgradeSetting.IsDowngradeConditionNotMet';
			$ignoreTestCaseKeyList[] = 'SACB.DailyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.InMaintainTime.IsMetLevelMaintainCondition.AccumulationYesRegistrationDate.DepositBetUpgradeSetting.IsDowngradeConditionMet';
			$ignoreTestCaseKeyList[] = 'SACB.DailyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.InMaintainTime.IsMetLevelMaintainCondition.AccumulationYesRegistrationDate.DepositBetUpgradeSetting.IsDowngradeConditionNotMet';
			$ignoreTestCaseKeyList[] = 'SACB.DailyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.InMaintainTime.NotMetLevelMaintainCondition.AccumulationYesRegistrationDate.EmptyUpgradeSetting.IsDowngradeConditionMet';
			$ignoreTestCaseKeyList[] = 'SACB.DailyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.InMaintainTime.NotMetLevelMaintainCondition.AccumulationYesRegistrationDate.EmptyUpgradeSetting.IsDowngradeConditionNotMet';
			$ignoreTestCaseKeyList[] = 'SACB.DailyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.InMaintainTime.NotMetLevelMaintainCondition.AccumulationYesRegistrationDate.DepositUpgradeSetting.IsDowngradeConditionMet';
			$ignoreTestCaseKeyList[] = 'SACB.DailyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.InMaintainTime.NotMetLevelMaintainCondition.AccumulationYesRegistrationDate.DepositUpgradeSetting.IsDowngradeConditionNotMet';
			$ignoreTestCaseKeyList[] = 'SACB.DailyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.InMaintainTime.NotMetLevelMaintainCondition.AccumulationYesRegistrationDate.BetUpgradeSetting.IsDowngradeConditionMet';
			$ignoreTestCaseKeyList[] = 'SACB.DailyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.InMaintainTime.NotMetLevelMaintainCondition.AccumulationYesRegistrationDate.BetUpgradeSetting.IsDowngradeConditionNotMet';
			$ignoreTestCaseKeyList[] = 'SACB.DailyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.InMaintainTime.NotMetLevelMaintainCondition.AccumulationYesRegistrationDate.DepositBetUpgradeSetting.IsDowngradeConditionMet';
			$ignoreTestCaseKeyList[] = 'SACB.DailyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.InMaintainTime.NotMetLevelMaintainCondition.AccumulationYesRegistrationDate.DepositBetUpgradeSetting.IsDowngradeConditionNotMet';
			$ignoreTestCaseKeyList[] = 'SACB.DailyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.OverMaintainTime.IsMetLevelMaintainCondition.AccumulationYesRegistrationDate.EmptyUpgradeSetting.IsDowngradeConditionMet';
			$ignoreTestCaseKeyList[] = 'SACB.DailyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.OverMaintainTime.IsMetLevelMaintainCondition.AccumulationYesRegistrationDate.EmptyUpgradeSetting.IsDowngradeConditionNotMet';
			$ignoreTestCaseKeyList[] = 'SACB.DailyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.OverMaintainTime.IsMetLevelMaintainCondition.AccumulationYesRegistrationDate.DepositUpgradeSetting.IsDowngradeConditionMet';
			$ignoreTestCaseKeyList[] = 'SACB.DailyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.OverMaintainTime.IsMetLevelMaintainCondition.AccumulationYesRegistrationDate.DepositUpgradeSetting.IsDowngradeConditionNotMet';
			$ignoreTestCaseKeyList[] = 'SACB.DailyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.OverMaintainTime.IsMetLevelMaintainCondition.AccumulationYesRegistrationDate.BetUpgradeSetting.IsDowngradeConditionMet';
			$ignoreTestCaseKeyList[] = 'SACB.DailyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.OverMaintainTime.IsMetLevelMaintainCondition.AccumulationYesRegistrationDate.BetUpgradeSetting.IsDowngradeConditionNotMet';
			$ignoreTestCaseKeyList[] = 'SACB.DailyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.OverMaintainTime.IsMetLevelMaintainCondition.AccumulationYesRegistrationDate.DepositBetUpgradeSetting.IsDowngradeConditionMet';
			$ignoreTestCaseKeyList[] = 'SACB.DailyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.OverMaintainTime.IsMetLevelMaintainCondition.AccumulationYesRegistrationDate.DepositBetUpgradeSetting.IsDowngradeConditionNotMet';
			$ignoreTestCaseKeyList[] = 'SACB.DailyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.OverMaintainTime.NotMetLevelMaintainCondition.AccumulationYesRegistrationDate.EmptyUpgradeSetting.IsDowngradeConditionMet';
			$ignoreTestCaseKeyList[] = 'SACB.DailyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.OverMaintainTime.NotMetLevelMaintainCondition.AccumulationYesRegistrationDate.EmptyUpgradeSetting.IsDowngradeConditionNotMet';
			$ignoreTestCaseKeyList[] = 'SACB.DailyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.OverMaintainTime.NotMetLevelMaintainCondition.AccumulationYesRegistrationDate.DepositUpgradeSetting.IsDowngradeConditionMet';
			$ignoreTestCaseKeyList[] = 'SACB.DailyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.OverMaintainTime.NotMetLevelMaintainCondition.AccumulationYesRegistrationDate.DepositUpgradeSetting.IsDowngradeConditionNotMet';
			$ignoreTestCaseKeyList[] = 'SACB.DailyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.OverMaintainTime.NotMetLevelMaintainCondition.AccumulationYesRegistrationDate.BetUpgradeSetting.IsDowngradeConditionMet';
			$ignoreTestCaseKeyList[] = 'SACB.DailyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.OverMaintainTime.NotMetLevelMaintainCondition.AccumulationYesRegistrationDate.BetUpgradeSetting.IsDowngradeConditionNotMet';
			$ignoreTestCaseKeyList[] = 'SACB.DailyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.OverMaintainTime.NotMetLevelMaintainCondition.AccumulationYesRegistrationDate.DepositBetUpgradeSetting.IsDowngradeConditionMet';
			$ignoreTestCaseKeyList[] = 'SACB.DailyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.OverMaintainTime.NotMetLevelMaintainCondition.AccumulationYesRegistrationDate.DepositBetUpgradeSetting.IsDowngradeConditionNotMet';
			// CASB
			$ignoreTestCaseKeyList[] = 'CASB.DailyPeriodMode.PeriodNotMet.OnLevelMaintainEnable.InMaintainTime.IsMetLevelMaintainCondition.AccumulationYesRegistrationDate.EmptyUpgradeSetting.IsDowngradeConditionMet';
			$ignoreTestCaseKeyList[] = 'CASB.DailyPeriodMode.PeriodNotMet.OnLevelMaintainEnable.InMaintainTime.IsMetLevelMaintainCondition.AccumulationYesRegistrationDate.EmptyUpgradeSetting.IsDowngradeConditionNotMet';
			$ignoreTestCaseKeyList[] = 'CASB.DailyPeriodMode.PeriodNotMet.OnLevelMaintainEnable.InMaintainTime.IsMetLevelMaintainCondition.AccumulationYesRegistrationDate.DepositUpgradeSetting.IsDowngradeConditionMet';
			$ignoreTestCaseKeyList[] = 'CASB.DailyPeriodMode.PeriodNotMet.OnLevelMaintainEnable.InMaintainTime.IsMetLevelMaintainCondition.AccumulationYesRegistrationDate.DepositUpgradeSetting.IsDowngradeConditionNotMet';
			$ignoreTestCaseKeyList[] = 'CASB.DailyPeriodMode.PeriodNotMet.OnLevelMaintainEnable.InMaintainTime.IsMetLevelMaintainCondition.AccumulationYesRegistrationDate.BetUpgradeSetting.IsDowngradeConditionMet';
			$ignoreTestCaseKeyList[] = 'CASB.DailyPeriodMode.PeriodNotMet.OnLevelMaintainEnable.InMaintainTime.IsMetLevelMaintainCondition.AccumulationYesRegistrationDate.BetUpgradeSetting.IsDowngradeConditionNotMet';
			$ignoreTestCaseKeyList[] = 'CASB.DailyPeriodMode.PeriodNotMet.OnLevelMaintainEnable.InMaintainTime.IsMetLevelMaintainCondition.AccumulationYesRegistrationDate.DepositBetUpgradeSetting.IsDowngradeConditionMet';
			$ignoreTestCaseKeyList[] = 'CASB.DailyPeriodMode.PeriodNotMet.OnLevelMaintainEnable.InMaintainTime.IsMetLevelMaintainCondition.AccumulationYesRegistrationDate.DepositBetUpgradeSetting.IsDowngradeConditionNotMet';
			$ignoreTestCaseKeyList[] = 'CASB.DailyPeriodMode.PeriodNotMet.OnLevelMaintainEnable.InMaintainTime.NotMetLevelMaintainCondition.AccumulationYesRegistrationDate.EmptyUpgradeSetting.IsDowngradeConditionMet';
			$ignoreTestCaseKeyList[] = 'CASB.DailyPeriodMode.PeriodNotMet.OnLevelMaintainEnable.InMaintainTime.NotMetLevelMaintainCondition.AccumulationYesRegistrationDate.EmptyUpgradeSetting.IsDowngradeConditionNotMet';
			$ignoreTestCaseKeyList[] = 'CASB.DailyPeriodMode.PeriodNotMet.OnLevelMaintainEnable.InMaintainTime.NotMetLevelMaintainCondition.AccumulationYesRegistrationDate.DepositUpgradeSetting.IsDowngradeConditionMet';
			$ignoreTestCaseKeyList[] = 'CASB.DailyPeriodMode.PeriodNotMet.OnLevelMaintainEnable.InMaintainTime.NotMetLevelMaintainCondition.AccumulationYesRegistrationDate.DepositUpgradeSetting.IsDowngradeConditionNotMet';
			$ignoreTestCaseKeyList[] = 'CASB.DailyPeriodMode.PeriodNotMet.OnLevelMaintainEnable.InMaintainTime.NotMetLevelMaintainCondition.AccumulationYesRegistrationDate.BetUpgradeSetting.IsDowngradeConditionMet';
			$ignoreTestCaseKeyList[] = 'CASB.DailyPeriodMode.PeriodNotMet.OnLevelMaintainEnable.InMaintainTime.NotMetLevelMaintainCondition.AccumulationYesRegistrationDate.BetUpgradeSetting.IsDowngradeConditionNotMet';
			$ignoreTestCaseKeyList[] = 'CASB.DailyPeriodMode.PeriodNotMet.OnLevelMaintainEnable.InMaintainTime.NotMetLevelMaintainCondition.AccumulationYesRegistrationDate.DepositBetUpgradeSetting.IsDowngradeConditionMet';
			$ignoreTestCaseKeyList[] = 'CASB.DailyPeriodMode.PeriodNotMet.OnLevelMaintainEnable.InMaintainTime.NotMetLevelMaintainCondition.AccumulationYesRegistrationDate.DepositBetUpgradeSetting.IsDowngradeConditionNotMet';
			$ignoreTestCaseKeyList[] = 'CASB.DailyPeriodMode.PeriodNotMet.OnLevelMaintainEnable.OverMaintainTime.IsMetLevelMaintainCondition.AccumulationYesRegistrationDate.EmptyUpgradeSetting.IsDowngradeConditionMet';
			$ignoreTestCaseKeyList[] = 'CASB.DailyPeriodMode.PeriodNotMet.OnLevelMaintainEnable.OverMaintainTime.IsMetLevelMaintainCondition.AccumulationYesRegistrationDate.EmptyUpgradeSetting.IsDowngradeConditionNotMet';
			$ignoreTestCaseKeyList[] = 'CASB.DailyPeriodMode.PeriodNotMet.OnLevelMaintainEnable.OverMaintainTime.IsMetLevelMaintainCondition.AccumulationYesRegistrationDate.DepositUpgradeSetting.IsDowngradeConditionMet';
			$ignoreTestCaseKeyList[] = 'CASB.DailyPeriodMode.PeriodNotMet.OnLevelMaintainEnable.OverMaintainTime.IsMetLevelMaintainCondition.AccumulationYesRegistrationDate.DepositUpgradeSetting.IsDowngradeConditionNotMet';
			$ignoreTestCaseKeyList[] = 'CASB.DailyPeriodMode.PeriodNotMet.OnLevelMaintainEnable.OverMaintainTime.IsMetLevelMaintainCondition.AccumulationYesRegistrationDate.BetUpgradeSetting.IsDowngradeConditionMet';
			$ignoreTestCaseKeyList[] = 'CASB.DailyPeriodMode.PeriodNotMet.OnLevelMaintainEnable.OverMaintainTime.IsMetLevelMaintainCondition.AccumulationYesRegistrationDate.BetUpgradeSetting.IsDowngradeConditionNotMet';
			$ignoreTestCaseKeyList[] = 'CASB.DailyPeriodMode.PeriodNotMet.OnLevelMaintainEnable.OverMaintainTime.IsMetLevelMaintainCondition.AccumulationYesRegistrationDate.DepositBetUpgradeSetting.IsDowngradeConditionMet';
			$ignoreTestCaseKeyList[] = 'CASB.DailyPeriodMode.PeriodNotMet.OnLevelMaintainEnable.OverMaintainTime.IsMetLevelMaintainCondition.AccumulationYesRegistrationDate.DepositBetUpgradeSetting.IsDowngradeConditionNotMet';
			$ignoreTestCaseKeyList[] = 'CASB.DailyPeriodMode.PeriodNotMet.OnLevelMaintainEnable.OverMaintainTime.NotMetLevelMaintainCondition.AccumulationYesRegistrationDate.EmptyUpgradeSetting.IsDowngradeConditionMet';
			$ignoreTestCaseKeyList[] = 'CASB.DailyPeriodMode.PeriodNotMet.OnLevelMaintainEnable.OverMaintainTime.NotMetLevelMaintainCondition.AccumulationYesRegistrationDate.EmptyUpgradeSetting.IsDowngradeConditionNotMet';
			$ignoreTestCaseKeyList[] = 'CASB.DailyPeriodMode.PeriodNotMet.OnLevelMaintainEnable.OverMaintainTime.NotMetLevelMaintainCondition.AccumulationYesRegistrationDate.DepositUpgradeSetting.IsDowngradeConditionMet';
			$ignoreTestCaseKeyList[] = 'CASB.DailyPeriodMode.PeriodNotMet.OnLevelMaintainEnable.OverMaintainTime.NotMetLevelMaintainCondition.AccumulationYesRegistrationDate.DepositUpgradeSetting.IsDowngradeConditionNotMet';
			$ignoreTestCaseKeyList[] = 'CASB.DailyPeriodMode.PeriodNotMet.OnLevelMaintainEnable.OverMaintainTime.NotMetLevelMaintainCondition.AccumulationYesRegistrationDate.BetUpgradeSetting.IsDowngradeConditionMet';
			$ignoreTestCaseKeyList[] = 'CASB.DailyPeriodMode.PeriodNotMet.OnLevelMaintainEnable.OverMaintainTime.NotMetLevelMaintainCondition.AccumulationYesRegistrationDate.BetUpgradeSetting.IsDowngradeConditionNotMet';
			$ignoreTestCaseKeyList[] = 'CASB.DailyPeriodMode.PeriodNotMet.OnLevelMaintainEnable.OverMaintainTime.NotMetLevelMaintainCondition.AccumulationYesRegistrationDate.DepositBetUpgradeSetting.IsDowngradeConditionMet';
			$ignoreTestCaseKeyList[] = 'CASB.DailyPeriodMode.PeriodNotMet.OnLevelMaintainEnable.OverMaintainTime.NotMetLevelMaintainCondition.AccumulationYesRegistrationDate.DepositBetUpgradeSetting.IsDowngradeConditionNotMet';
			$ignoreTestCaseKeyList[] = 'CASB.DailyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.InMaintainTime.IsMetLevelMaintainCondition.AccumulationYesRegistrationDate.EmptyUpgradeSetting.IsDowngradeConditionMet';
			$ignoreTestCaseKeyList[] = 'CASB.DailyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.InMaintainTime.IsMetLevelMaintainCondition.AccumulationYesRegistrationDate.EmptyUpgradeSetting.IsDowngradeConditionNotMet';
			$ignoreTestCaseKeyList[] = 'CASB.DailyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.InMaintainTime.IsMetLevelMaintainCondition.AccumulationYesRegistrationDate.DepositUpgradeSetting.IsDowngradeConditionMet';
			$ignoreTestCaseKeyList[] = 'CASB.DailyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.InMaintainTime.IsMetLevelMaintainCondition.AccumulationYesRegistrationDate.DepositUpgradeSetting.IsDowngradeConditionNotMet';
			$ignoreTestCaseKeyList[] = 'CASB.DailyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.InMaintainTime.IsMetLevelMaintainCondition.AccumulationYesRegistrationDate.BetUpgradeSetting.IsDowngradeConditionMet';
			$ignoreTestCaseKeyList[] = 'CASB.DailyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.InMaintainTime.IsMetLevelMaintainCondition.AccumulationYesRegistrationDate.BetUpgradeSetting.IsDowngradeConditionNotMet';
			$ignoreTestCaseKeyList[] = 'CASB.DailyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.InMaintainTime.IsMetLevelMaintainCondition.AccumulationYesRegistrationDate.DepositBetUpgradeSetting.IsDowngradeConditionMet';
			$ignoreTestCaseKeyList[] = 'CASB.DailyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.InMaintainTime.IsMetLevelMaintainCondition.AccumulationYesRegistrationDate.DepositBetUpgradeSetting.IsDowngradeConditionNotMet';
			$ignoreTestCaseKeyList[] = 'CASB.DailyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.InMaintainTime.NotMetLevelMaintainCondition.AccumulationYesRegistrationDate.EmptyUpgradeSetting.IsDowngradeConditionMet';
			$ignoreTestCaseKeyList[] = 'CASB.DailyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.InMaintainTime.NotMetLevelMaintainCondition.AccumulationYesRegistrationDate.EmptyUpgradeSetting.IsDowngradeConditionNotMet';
			$ignoreTestCaseKeyList[] = 'CASB.DailyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.InMaintainTime.NotMetLevelMaintainCondition.AccumulationYesRegistrationDate.DepositUpgradeSetting.IsDowngradeConditionMet';
			$ignoreTestCaseKeyList[] = 'CASB.DailyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.InMaintainTime.NotMetLevelMaintainCondition.AccumulationYesRegistrationDate.DepositUpgradeSetting.IsDowngradeConditionNotMet';
			$ignoreTestCaseKeyList[] = 'CASB.DailyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.InMaintainTime.NotMetLevelMaintainCondition.AccumulationYesRegistrationDate.BetUpgradeSetting.IsDowngradeConditionMet';
			$ignoreTestCaseKeyList[] = 'CASB.DailyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.InMaintainTime.NotMetLevelMaintainCondition.AccumulationYesRegistrationDate.BetUpgradeSetting.IsDowngradeConditionNotMet';
			$ignoreTestCaseKeyList[] = 'CASB.DailyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.InMaintainTime.NotMetLevelMaintainCondition.AccumulationYesRegistrationDate.DepositBetUpgradeSetting.IsDowngradeConditionMet';
			$ignoreTestCaseKeyList[] = 'CASB.DailyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.InMaintainTime.NotMetLevelMaintainCondition.AccumulationYesRegistrationDate.DepositBetUpgradeSetting.IsDowngradeConditionNotMet';
			$ignoreTestCaseKeyList[] = 'CASB.DailyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.OverMaintainTime.IsMetLevelMaintainCondition.AccumulationYesRegistrationDate.EmptyUpgradeSetting.IsDowngradeConditionMet';
			$ignoreTestCaseKeyList[] = 'CASB.DailyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.OverMaintainTime.IsMetLevelMaintainCondition.AccumulationYesRegistrationDate.EmptyUpgradeSetting.IsDowngradeConditionNotMet';
			$ignoreTestCaseKeyList[] = 'CASB.DailyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.OverMaintainTime.IsMetLevelMaintainCondition.AccumulationYesRegistrationDate.DepositUpgradeSetting.IsDowngradeConditionMet';
			$ignoreTestCaseKeyList[] = 'CASB.DailyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.OverMaintainTime.IsMetLevelMaintainCondition.AccumulationYesRegistrationDate.DepositUpgradeSetting.IsDowngradeConditionNotMet';
			$ignoreTestCaseKeyList[] = 'CASB.DailyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.OverMaintainTime.IsMetLevelMaintainCondition.AccumulationYesRegistrationDate.BetUpgradeSetting.IsDowngradeConditionMet';
			$ignoreTestCaseKeyList[] = 'CASB.DailyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.OverMaintainTime.IsMetLevelMaintainCondition.AccumulationYesRegistrationDate.BetUpgradeSetting.IsDowngradeConditionNotMet';
			$ignoreTestCaseKeyList[] = 'CASB.DailyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.OverMaintainTime.IsMetLevelMaintainCondition.AccumulationYesRegistrationDate.DepositBetUpgradeSetting.IsDowngradeConditionMet';
			$ignoreTestCaseKeyList[] = 'CASB.DailyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.OverMaintainTime.IsMetLevelMaintainCondition.AccumulationYesRegistrationDate.DepositBetUpgradeSetting.IsDowngradeConditionNotMet';
			$ignoreTestCaseKeyList[] = 'CASB.DailyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.OverMaintainTime.NotMetLevelMaintainCondition.AccumulationYesRegistrationDate.EmptyUpgradeSetting.IsDowngradeConditionMet';
			$ignoreTestCaseKeyList[] = 'CASB.DailyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.OverMaintainTime.NotMetLevelMaintainCondition.AccumulationYesRegistrationDate.EmptyUpgradeSetting.IsDowngradeConditionNotMet';
			$ignoreTestCaseKeyList[] = 'CASB.DailyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.OverMaintainTime.NotMetLevelMaintainCondition.AccumulationYesRegistrationDate.DepositUpgradeSetting.IsDowngradeConditionMet';
			$ignoreTestCaseKeyList[] = 'CASB.DailyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.OverMaintainTime.NotMetLevelMaintainCondition.AccumulationYesRegistrationDate.DepositUpgradeSetting.IsDowngradeConditionNotMet';
			$ignoreTestCaseKeyList[] = 'CASB.DailyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.OverMaintainTime.NotMetLevelMaintainCondition.AccumulationYesRegistrationDate.BetUpgradeSetting.IsDowngradeConditionMet';
			$ignoreTestCaseKeyList[] = 'CASB.DailyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.OverMaintainTime.NotMetLevelMaintainCondition.AccumulationYesRegistrationDate.BetUpgradeSetting.IsDowngradeConditionNotMet';
			$ignoreTestCaseKeyList[] = 'CASB.DailyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.OverMaintainTime.NotMetLevelMaintainCondition.AccumulationYesRegistrationDate.DepositBetUpgradeSetting.IsDowngradeConditionMet';
			$ignoreTestCaseKeyList[] = 'CASB.DailyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.OverMaintainTime.NotMetLevelMaintainCondition.AccumulationYesRegistrationDate.DepositBetUpgradeSetting.IsDowngradeConditionNotMet';
			// SASB
			$ignoreTestCaseKeyList[] = 'SASB.DailyPeriodMode.PeriodNotMet.OnLevelMaintainEnable.InMaintainTime.IsMetLevelMaintainCondition.AccumulationYesRegistrationDate.EmptyUpgradeSetting.IsDowngradeConditionMet';
			$ignoreTestCaseKeyList[] = 'SASB.DailyPeriodMode.PeriodNotMet.OnLevelMaintainEnable.InMaintainTime.IsMetLevelMaintainCondition.AccumulationYesRegistrationDate.EmptyUpgradeSetting.IsDowngradeConditionNotMet';
			$ignoreTestCaseKeyList[] = 'SASB.DailyPeriodMode.PeriodNotMet.OnLevelMaintainEnable.InMaintainTime.IsMetLevelMaintainCondition.AccumulationYesRegistrationDate.DepositUpgradeSetting.IsDowngradeConditionMet';
			$ignoreTestCaseKeyList[] = 'SASB.DailyPeriodMode.PeriodNotMet.OnLevelMaintainEnable.InMaintainTime.IsMetLevelMaintainCondition.AccumulationYesRegistrationDate.DepositUpgradeSetting.IsDowngradeConditionNotMet';
			$ignoreTestCaseKeyList[] = 'SASB.DailyPeriodMode.PeriodNotMet.OnLevelMaintainEnable.InMaintainTime.IsMetLevelMaintainCondition.AccumulationYesRegistrationDate.BetUpgradeSetting.IsDowngradeConditionMet';
			$ignoreTestCaseKeyList[] = 'SASB.DailyPeriodMode.PeriodNotMet.OnLevelMaintainEnable.InMaintainTime.IsMetLevelMaintainCondition.AccumulationYesRegistrationDate.BetUpgradeSetting.IsDowngradeConditionNotMet';
			$ignoreTestCaseKeyList[] = 'SASB.DailyPeriodMode.PeriodNotMet.OnLevelMaintainEnable.InMaintainTime.IsMetLevelMaintainCondition.AccumulationYesRegistrationDate.DepositBetUpgradeSetting.IsDowngradeConditionMet';
			$ignoreTestCaseKeyList[] = 'SASB.DailyPeriodMode.PeriodNotMet.OnLevelMaintainEnable.InMaintainTime.IsMetLevelMaintainCondition.AccumulationYesRegistrationDate.DepositBetUpgradeSetting.IsDowngradeConditionNotMet';
			$ignoreTestCaseKeyList[] = 'SASB.DailyPeriodMode.PeriodNotMet.OnLevelMaintainEnable.InMaintainTime.NotMetLevelMaintainCondition.AccumulationYesRegistrationDate.EmptyUpgradeSetting.IsDowngradeConditionMet';
			$ignoreTestCaseKeyList[] = 'SASB.DailyPeriodMode.PeriodNotMet.OnLevelMaintainEnable.InMaintainTime.NotMetLevelMaintainCondition.AccumulationYesRegistrationDate.EmptyUpgradeSetting.IsDowngradeConditionNotMet';
			$ignoreTestCaseKeyList[] = 'SASB.DailyPeriodMode.PeriodNotMet.OnLevelMaintainEnable.InMaintainTime.NotMetLevelMaintainCondition.AccumulationYesRegistrationDate.DepositUpgradeSetting.IsDowngradeConditionMet';
			$ignoreTestCaseKeyList[] = 'SASB.DailyPeriodMode.PeriodNotMet.OnLevelMaintainEnable.InMaintainTime.NotMetLevelMaintainCondition.AccumulationYesRegistrationDate.DepositUpgradeSetting.IsDowngradeConditionNotMet';
			$ignoreTestCaseKeyList[] = 'SASB.DailyPeriodMode.PeriodNotMet.OnLevelMaintainEnable.InMaintainTime.NotMetLevelMaintainCondition.AccumulationYesRegistrationDate.BetUpgradeSetting.IsDowngradeConditionMet';
			$ignoreTestCaseKeyList[] = 'SASB.DailyPeriodMode.PeriodNotMet.OnLevelMaintainEnable.InMaintainTime.NotMetLevelMaintainCondition.AccumulationYesRegistrationDate.BetUpgradeSetting.IsDowngradeConditionNotMet';
			$ignoreTestCaseKeyList[] = 'SASB.DailyPeriodMode.PeriodNotMet.OnLevelMaintainEnable.InMaintainTime.NotMetLevelMaintainCondition.AccumulationYesRegistrationDate.DepositBetUpgradeSetting.IsDowngradeConditionMet';
			$ignoreTestCaseKeyList[] = 'SASB.DailyPeriodMode.PeriodNotMet.OnLevelMaintainEnable.InMaintainTime.NotMetLevelMaintainCondition.AccumulationYesRegistrationDate.DepositBetUpgradeSetting.IsDowngradeConditionNotMet';
			$ignoreTestCaseKeyList[] = 'SASB.DailyPeriodMode.PeriodNotMet.OnLevelMaintainEnable.OverMaintainTime.IsMetLevelMaintainCondition.AccumulationYesRegistrationDate.EmptyUpgradeSetting.IsDowngradeConditionMet';
			$ignoreTestCaseKeyList[] = 'SASB.DailyPeriodMode.PeriodNotMet.OnLevelMaintainEnable.OverMaintainTime.IsMetLevelMaintainCondition.AccumulationYesRegistrationDate.EmptyUpgradeSetting.IsDowngradeConditionNotMet';
			$ignoreTestCaseKeyList[] = 'SASB.DailyPeriodMode.PeriodNotMet.OnLevelMaintainEnable.OverMaintainTime.IsMetLevelMaintainCondition.AccumulationYesRegistrationDate.DepositUpgradeSetting.IsDowngradeConditionMet';
			$ignoreTestCaseKeyList[] = 'SASB.DailyPeriodMode.PeriodNotMet.OnLevelMaintainEnable.OverMaintainTime.IsMetLevelMaintainCondition.AccumulationYesRegistrationDate.DepositUpgradeSetting.IsDowngradeConditionNotMet';
			$ignoreTestCaseKeyList[] = 'SASB.DailyPeriodMode.PeriodNotMet.OnLevelMaintainEnable.OverMaintainTime.IsMetLevelMaintainCondition.AccumulationYesRegistrationDate.BetUpgradeSetting.IsDowngradeConditionMet';
			$ignoreTestCaseKeyList[] = 'SASB.DailyPeriodMode.PeriodNotMet.OnLevelMaintainEnable.OverMaintainTime.IsMetLevelMaintainCondition.AccumulationYesRegistrationDate.BetUpgradeSetting.IsDowngradeConditionNotMet';
			$ignoreTestCaseKeyList[] = 'SASB.DailyPeriodMode.PeriodNotMet.OnLevelMaintainEnable.OverMaintainTime.IsMetLevelMaintainCondition.AccumulationYesRegistrationDate.DepositBetUpgradeSetting.IsDowngradeConditionMet';
			$ignoreTestCaseKeyList[] = 'SASB.DailyPeriodMode.PeriodNotMet.OnLevelMaintainEnable.OverMaintainTime.IsMetLevelMaintainCondition.AccumulationYesRegistrationDate.DepositBetUpgradeSetting.IsDowngradeConditionNotMet';
			$ignoreTestCaseKeyList[] = 'SASB.DailyPeriodMode.PeriodNotMet.OnLevelMaintainEnable.OverMaintainTime.NotMetLevelMaintainCondition.AccumulationYesRegistrationDate.EmptyUpgradeSetting.IsDowngradeConditionMet';
			$ignoreTestCaseKeyList[] = 'SASB.DailyPeriodMode.PeriodNotMet.OnLevelMaintainEnable.OverMaintainTime.NotMetLevelMaintainCondition.AccumulationYesRegistrationDate.EmptyUpgradeSetting.IsDowngradeConditionNotMet';
			$ignoreTestCaseKeyList[] = 'SASB.DailyPeriodMode.PeriodNotMet.OnLevelMaintainEnable.OverMaintainTime.NotMetLevelMaintainCondition.AccumulationYesRegistrationDate.DepositUpgradeSetting.IsDowngradeConditionMet';
			$ignoreTestCaseKeyList[] = 'SASB.DailyPeriodMode.PeriodNotMet.OnLevelMaintainEnable.OverMaintainTime.NotMetLevelMaintainCondition.AccumulationYesRegistrationDate.DepositUpgradeSetting.IsDowngradeConditionNotMet';
			$ignoreTestCaseKeyList[] = 'SASB.DailyPeriodMode.PeriodNotMet.OnLevelMaintainEnable.OverMaintainTime.NotMetLevelMaintainCondition.AccumulationYesRegistrationDate.BetUpgradeSetting.IsDowngradeConditionMet';
			$ignoreTestCaseKeyList[] = 'SASB.DailyPeriodMode.PeriodNotMet.OnLevelMaintainEnable.OverMaintainTime.NotMetLevelMaintainCondition.AccumulationYesRegistrationDate.BetUpgradeSetting.IsDowngradeConditionNotMet';
			$ignoreTestCaseKeyList[] = 'SASB.DailyPeriodMode.PeriodNotMet.OnLevelMaintainEnable.OverMaintainTime.NotMetLevelMaintainCondition.AccumulationYesRegistrationDate.DepositBetUpgradeSetting.IsDowngradeConditionMet';
			$ignoreTestCaseKeyList[] = 'SASB.DailyPeriodMode.PeriodNotMet.OnLevelMaintainEnable.OverMaintainTime.NotMetLevelMaintainCondition.AccumulationYesRegistrationDate.DepositBetUpgradeSetting.IsDowngradeConditionNotMet';
			$ignoreTestCaseKeyList[] = 'SASB.DailyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.InMaintainTime.IsMetLevelMaintainCondition.AccumulationYesRegistrationDate.EmptyUpgradeSetting.IsDowngradeConditionMet';
			$ignoreTestCaseKeyList[] = 'SASB.DailyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.InMaintainTime.IsMetLevelMaintainCondition.AccumulationYesRegistrationDate.EmptyUpgradeSetting.IsDowngradeConditionNotMet';
			$ignoreTestCaseKeyList[] = 'SASB.DailyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.InMaintainTime.IsMetLevelMaintainCondition.AccumulationYesRegistrationDate.DepositUpgradeSetting.IsDowngradeConditionMet';
			$ignoreTestCaseKeyList[] = 'SASB.DailyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.InMaintainTime.IsMetLevelMaintainCondition.AccumulationYesRegistrationDate.DepositUpgradeSetting.IsDowngradeConditionNotMet';
			$ignoreTestCaseKeyList[] = 'SASB.DailyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.InMaintainTime.IsMetLevelMaintainCondition.AccumulationYesRegistrationDate.BetUpgradeSetting.IsDowngradeConditionMet';
			$ignoreTestCaseKeyList[] = 'SASB.DailyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.InMaintainTime.IsMetLevelMaintainCondition.AccumulationYesRegistrationDate.BetUpgradeSetting.IsDowngradeConditionNotMet';
			$ignoreTestCaseKeyList[] = 'SASB.DailyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.InMaintainTime.IsMetLevelMaintainCondition.AccumulationYesRegistrationDate.DepositBetUpgradeSetting.IsDowngradeConditionMet';
			$ignoreTestCaseKeyList[] = 'SASB.DailyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.InMaintainTime.IsMetLevelMaintainCondition.AccumulationYesRegistrationDate.DepositBetUpgradeSetting.IsDowngradeConditionNotMet';
			$ignoreTestCaseKeyList[] = 'SASB.DailyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.InMaintainTime.NotMetLevelMaintainCondition.AccumulationYesRegistrationDate.EmptyUpgradeSetting.IsDowngradeConditionMet';
			$ignoreTestCaseKeyList[] = 'SASB.DailyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.InMaintainTime.NotMetLevelMaintainCondition.AccumulationYesRegistrationDate.EmptyUpgradeSetting.IsDowngradeConditionNotMet';
			$ignoreTestCaseKeyList[] = 'SASB.DailyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.InMaintainTime.NotMetLevelMaintainCondition.AccumulationYesRegistrationDate.DepositUpgradeSetting.IsDowngradeConditionMet';
			$ignoreTestCaseKeyList[] = 'SASB.DailyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.InMaintainTime.NotMetLevelMaintainCondition.AccumulationYesRegistrationDate.DepositUpgradeSetting.IsDowngradeConditionNotMet';
			$ignoreTestCaseKeyList[] = 'SASB.DailyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.InMaintainTime.NotMetLevelMaintainCondition.AccumulationYesRegistrationDate.BetUpgradeSetting.IsDowngradeConditionMet';
			$ignoreTestCaseKeyList[] = 'SASB.DailyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.InMaintainTime.NotMetLevelMaintainCondition.AccumulationYesRegistrationDate.BetUpgradeSetting.IsDowngradeConditionNotMet';
			$ignoreTestCaseKeyList[] = 'SASB.DailyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.InMaintainTime.NotMetLevelMaintainCondition.AccumulationYesRegistrationDate.DepositBetUpgradeSetting.IsDowngradeConditionMet';
			$ignoreTestCaseKeyList[] = 'SASB.DailyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.InMaintainTime.NotMetLevelMaintainCondition.AccumulationYesRegistrationDate.DepositBetUpgradeSetting.IsDowngradeConditionNotMet';
			$ignoreTestCaseKeyList[] = 'SASB.DailyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.OverMaintainTime.IsMetLevelMaintainCondition.AccumulationYesRegistrationDate.EmptyUpgradeSetting.IsDowngradeConditionMet';
			$ignoreTestCaseKeyList[] = 'SASB.DailyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.OverMaintainTime.IsMetLevelMaintainCondition.AccumulationYesRegistrationDate.EmptyUpgradeSetting.IsDowngradeConditionNotMet';
			$ignoreTestCaseKeyList[] = 'SASB.DailyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.OverMaintainTime.IsMetLevelMaintainCondition.AccumulationYesRegistrationDate.DepositUpgradeSetting.IsDowngradeConditionMet';
			$ignoreTestCaseKeyList[] = 'SASB.DailyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.OverMaintainTime.IsMetLevelMaintainCondition.AccumulationYesRegistrationDate.DepositUpgradeSetting.IsDowngradeConditionNotMet';
			$ignoreTestCaseKeyList[] = 'SASB.DailyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.OverMaintainTime.IsMetLevelMaintainCondition.AccumulationYesRegistrationDate.BetUpgradeSetting.IsDowngradeConditionMet';
			$ignoreTestCaseKeyList[] = 'SASB.DailyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.OverMaintainTime.IsMetLevelMaintainCondition.AccumulationYesRegistrationDate.BetUpgradeSetting.IsDowngradeConditionNotMet';
			$ignoreTestCaseKeyList[] = 'SASB.DailyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.OverMaintainTime.IsMetLevelMaintainCondition.AccumulationYesRegistrationDate.DepositBetUpgradeSetting.IsDowngradeConditionMet';
			$ignoreTestCaseKeyList[] = 'SASB.DailyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.OverMaintainTime.IsMetLevelMaintainCondition.AccumulationYesRegistrationDate.DepositBetUpgradeSetting.IsDowngradeConditionNotMet';
			$ignoreTestCaseKeyList[] = 'SASB.DailyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.OverMaintainTime.NotMetLevelMaintainCondition.AccumulationYesRegistrationDate.EmptyUpgradeSetting.IsDowngradeConditionMet';
			$ignoreTestCaseKeyList[] = 'SASB.DailyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.OverMaintainTime.NotMetLevelMaintainCondition.AccumulationYesRegistrationDate.EmptyUpgradeSetting.IsDowngradeConditionNotMet';
			$ignoreTestCaseKeyList[] = 'SASB.DailyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.OverMaintainTime.NotMetLevelMaintainCondition.AccumulationYesRegistrationDate.DepositUpgradeSetting.IsDowngradeConditionMet';
			$ignoreTestCaseKeyList[] = 'SASB.DailyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.OverMaintainTime.NotMetLevelMaintainCondition.AccumulationYesRegistrationDate.DepositUpgradeSetting.IsDowngradeConditionNotMet';
			$ignoreTestCaseKeyList[] = 'SASB.DailyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.OverMaintainTime.NotMetLevelMaintainCondition.AccumulationYesRegistrationDate.BetUpgradeSetting.IsDowngradeConditionMet';
			$ignoreTestCaseKeyList[] = 'SASB.DailyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.OverMaintainTime.NotMetLevelMaintainCondition.AccumulationYesRegistrationDate.BetUpgradeSetting.IsDowngradeConditionNotMet';
			$ignoreTestCaseKeyList[] = 'SASB.DailyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.OverMaintainTime.NotMetLevelMaintainCondition.AccumulationYesRegistrationDate.DepositBetUpgradeSetting.IsDowngradeConditionMet';
			$ignoreTestCaseKeyList[] = 'SASB.DailyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.OverMaintainTime.NotMetLevelMaintainCondition.AccumulationYesRegistrationDate.DepositBetUpgradeSetting.IsDowngradeConditionNotMet';


		return $ignoreTestCaseKeyList;
	}// EOF _getIngoreCombinedCases4ogp21818

	/**
	 * Get the CombinedCases,  which will be ignored when testing the cases.
	 *
	 * @return array The ignored CombinedCases.
	 */
	public function _getIngoreCombinedCases4upgrade(){
		$ignoreTestCaseKeyList = [];
		// $ignoreTestCaseKeyList[] = 'CACB.EmptyPeriodMode.PeriodIsMet.OnLevelMaintainEnable.InMaintainTime.IsMetLevelMaintainCondition.NoAccumulation';

		// // DailyPeriodMode && PeriodNotMet => DailyPeriodMode\..*\.PeriodNotMet
		// $ignoreTestCaseKeyList[] = 'CACB.DailyPeriodMode.NextLevelUpDailyPeriodMode.PeriodNotMet.NextLevelUpPeriodIsMet.OnHourlyCheckUpgradeEnable.NoAccumulation.NextLevelUpNoAccumulation.OnMultipleUpgradeEnable';

		// // NextLevelUpDailyPeriodMode && NextLevelUpPeriodNotMet => NextLevelUpDailyPeriodMode\..*\.NextLevelUpPeriodNotMet
		// $ignoreTestCaseKeyList[] = 'CACB.DailyPeriodMode.NextLevelUpDailyPeriodMode.PeriodIsMet.NextLevelUpPeriodNotMet.OnHourlyCheckUpgradeEnable.NoAccumulation.NextLevelUpNoAccumulation.OnMultipleUpgradeEnable';

		return $ignoreTestCaseKeyList;
	}

	/**
	 * Check the theCombinedCase Ingore during test the cases.
	 *
	 * @param string $theCombinedCase
	 * @return bool If true, the $theCombinedCase should be ignore.
	 */
	public function _isIngoreCombinedCases4upgrade($theCombinedCase){
		$isIngore = null;
		/// Contains dot,
		// because the string,"NextLevelUpDailyPeriodMode" has the keyword,"DailyPeriodMode".
		// It is misjudgment the the keyword,"DailyPeriodMode".

		$isEmptyPeriodMode = $this->_pos($theCombinedCase, '.EmptyPeriodMode');
		$isDailyPeriodMode = $this->_pos($theCombinedCase, '.DailyPeriodMode');
		$isWeekly1PeriodMode = $this->_pos($theCombinedCase, '.Weekly1PeriodMode');
		$isMonthly5PeriodMode = $this->_pos($theCombinedCase, '.Monthly5PeriodMode');

		$isPeriodIsMet = $this->_pos($theCombinedCase, '.PeriodIsMet');
		$isPeriodNotMet = $this->_pos($theCombinedCase, '.PeriodNotMet');

		$isNextLevelUpEmptyPeriodMode = $this->_pos($theCombinedCase, '.NextLevelUpEmptyPeriodMode');
		$isNextLevelUpDailyPeriodMode = $this->_pos($theCombinedCase, '.NextLevelUpDailyPeriodMode');
		$isNextLevelUpWeekly1PeriodMode = $this->_pos($theCombinedCase, '.NextLevelUpWeekly1PeriodMode');
		$isNextLevelUpMonthly5PeriodMode = $this->_pos($theCombinedCase, '.NextLevelUpMonthly5PeriodMode');

		$isNextLevelUpPeriodIsMet = $this->_pos($theCombinedCase, '.NextLevelUpPeriodIsMet');
		$isNextLevelUpPeriodNotMet = $this->_pos($theCombinedCase, '.NextLevelUpPeriodNotMet.');

		$isIngore = false;
		if($isDailyPeriodMode && $isPeriodNotMet){
			// impossible cases.
			$isIngore = true;
		}
		if($isNextLevelUpDailyPeriodMode && $isNextLevelUpPeriodNotMet){
			// impossible cases.
			$isIngore = true;
		}
		$detectCase = null;
		// $detectCase = 'CACB.Weekly1PeriodMode.NextLevelUpWeekly1PeriodMode.PeriodNotMet.NextLevelUpPeriodIsMet.OnHourlyCheckUpgradeEnable.OnMultipleUpgradeEnable';
		if( $theCombinedCase == $detectCase){
			$toDebuglog = true;
		}else{
			$toDebuglog = false;
		}
		if($toDebuglog)print_r(['1388.isPeriodIsMet', $isPeriodIsMet
								, 'isNextLevelUpPeriodIsMet:', $isNextLevelUpPeriodIsMet
								, 'isDailyPeriodMode:', $isDailyPeriodMode
								, 'isNextLevelUpDailyPeriodMode:', $isNextLevelUpDailyPeriodMode
								, 'isWeekly1PeriodMode:', $isWeekly1PeriodMode
								, 'isNextLevelUpWeekly1PeriodMode:', $isNextLevelUpWeekly1PeriodMode

								, 'isMonthly5PeriodMode:', $isMonthly5PeriodMode
								, 'isNextLevelUpMonthly5PeriodMode:', $isNextLevelUpMonthly5PeriodMode
								]);
		// the impassible case, isPeriodIsMet and ! isNextLevelUpPeriodIsMet then PeriodMode != NextLevelUpPeriodMode
		// if( $isPeriodIsMet && ! $isNextLevelUpPeriodIsMet ){
		if( $isPeriodIsMet != $isNextLevelUpPeriodIsMet ){
			if( ($isDailyPeriodMode == true && $isNextLevelUpDailyPeriodMode == true)
				|| ($isWeekly1PeriodMode == true && $isNextLevelUpWeekly1PeriodMode == true)
				|| ($isMonthly5PeriodMode == true && $isNextLevelUpMonthly5PeriodMode == true)
			){
				$isIngore = true;
			}
		}
		return $isIngore;
	} // EOF _isIngoreCombinedCases4upgrade

	/**
	 * Get the Ingore Combined Test Cases.
	 *
	 * @return array
	 */
	public function _getIngoreCombinedCases(){
		$ignoreTestCaseKeyList = [];
		// ignore EmptyPeriodMode.PeriodIsMet for CACB
			$ignoreTestCaseKeyList[] = 'CACB.EmptyPeriodMode.PeriodIsMet.OnLevelMaintainEnable.InMaintainTime.IsMetLevelMaintainCondition.NoAccumulation';
			$ignoreTestCaseKeyList[] = 'CACB.EmptyPeriodMode.PeriodIsMet.OnLevelMaintainEnable.InMaintainTime.IsMetLevelMaintainCondition.AccumulationYesRegistrationDate';
			$ignoreTestCaseKeyList[] = 'CACB.EmptyPeriodMode.PeriodIsMet.OnLevelMaintainEnable.InMaintainTime.IsMetLevelMaintainCondition.AccumulationYesLastChangePeriod';
			$ignoreTestCaseKeyList[] = 'CACB.EmptyPeriodMode.PeriodIsMet.OnLevelMaintainEnable.InMaintainTime.NotMetLevelMaintainCondition.NoAccumulation';
			$ignoreTestCaseKeyList[] = 'CACB.EmptyPeriodMode.PeriodIsMet.OnLevelMaintainEnable.InMaintainTime.NotMetLevelMaintainCondition.AccumulationYesRegistrationDate';
			$ignoreTestCaseKeyList[] = 'CACB.EmptyPeriodMode.PeriodIsMet.OnLevelMaintainEnable.InMaintainTime.NotMetLevelMaintainCondition.AccumulationYesLastChangePeriod';
			$ignoreTestCaseKeyList[] = 'CACB.EmptyPeriodMode.PeriodIsMet.OnLevelMaintainEnable.OverMaintainTime.IsMetLevelMaintainCondition.NoAccumulation';
			$ignoreTestCaseKeyList[] = 'CACB.EmptyPeriodMode.PeriodIsMet.OnLevelMaintainEnable.OverMaintainTime.IsMetLevelMaintainCondition.AccumulationYesRegistrationDate';
			$ignoreTestCaseKeyList[] = 'CACB.EmptyPeriodMode.PeriodIsMet.OnLevelMaintainEnable.OverMaintainTime.IsMetLevelMaintainCondition.AccumulationYesLastChangePeriod';
			$ignoreTestCaseKeyList[] = 'CACB.EmptyPeriodMode.PeriodIsMet.OnLevelMaintainEnable.OverMaintainTime.NotMetLevelMaintainCondition.NoAccumulation';
			$ignoreTestCaseKeyList[] = 'CACB.EmptyPeriodMode.PeriodIsMet.OnLevelMaintainEnable.OverMaintainTime.NotMetLevelMaintainCondition.AccumulationYesRegistrationDate';
			$ignoreTestCaseKeyList[] = 'CACB.EmptyPeriodMode.PeriodIsMet.OnLevelMaintainEnable.OverMaintainTime.NotMetLevelMaintainCondition.AccumulationYesLastChangePeriod';
			$ignoreTestCaseKeyList[] = 'CACB.EmptyPeriodMode.PeriodIsMet.OffLevelMaintainEnable.InMaintainTime.IsMetLevelMaintainCondition.NoAccumulation';
			$ignoreTestCaseKeyList[] = 'CACB.EmptyPeriodMode.PeriodIsMet.OffLevelMaintainEnable.InMaintainTime.IsMetLevelMaintainCondition.AccumulationYesRegistrationDate';
			$ignoreTestCaseKeyList[] = 'CACB.EmptyPeriodMode.PeriodIsMet.OffLevelMaintainEnable.InMaintainTime.IsMetLevelMaintainCondition.AccumulationYesLastChangePeriod';
			$ignoreTestCaseKeyList[] = 'CACB.EmptyPeriodMode.PeriodIsMet.OffLevelMaintainEnable.InMaintainTime.NotMetLevelMaintainCondition.NoAccumulation';
			$ignoreTestCaseKeyList[] = 'CACB.EmptyPeriodMode.PeriodIsMet.OffLevelMaintainEnable.InMaintainTime.NotMetLevelMaintainCondition.AccumulationYesRegistrationDate';
			$ignoreTestCaseKeyList[] = 'CACB.EmptyPeriodMode.PeriodIsMet.OffLevelMaintainEnable.InMaintainTime.NotMetLevelMaintainCondition.AccumulationYesLastChangePeriod';
			$ignoreTestCaseKeyList[] = 'CACB.EmptyPeriodMode.PeriodIsMet.OffLevelMaintainEnable.OverMaintainTime.IsMetLevelMaintainCondition.NoAccumulation';
			$ignoreTestCaseKeyList[] = 'CACB.EmptyPeriodMode.PeriodIsMet.OffLevelMaintainEnable.OverMaintainTime.IsMetLevelMaintainCondition.AccumulationYesRegistrationDate';
			$ignoreTestCaseKeyList[] = 'CACB.EmptyPeriodMode.PeriodIsMet.OffLevelMaintainEnable.OverMaintainTime.IsMetLevelMaintainCondition.AccumulationYesLastChangePeriod';
			$ignoreTestCaseKeyList[] = 'CACB.EmptyPeriodMode.PeriodIsMet.OffLevelMaintainEnable.OverMaintainTime.NotMetLevelMaintainCondition.NoAccumulation';
			$ignoreTestCaseKeyList[] = 'CACB.EmptyPeriodMode.PeriodIsMet.OffLevelMaintainEnable.OverMaintainTime.NotMetLevelMaintainCondition.AccumulationYesRegistrationDate';
			$ignoreTestCaseKeyList[] = 'CACB.EmptyPeriodMode.PeriodIsMet.OffLevelMaintainEnable.OverMaintainTime.NotMetLevelMaintainCondition.AccumulationYesLastChangePeriod';
		// ignore EmptyPeriodMode.PeriodIsMet for SACB
			$ignoreTestCaseKeyList[] = 'SACB.EmptyPeriodMode.PeriodIsMet.OnLevelMaintainEnable.InMaintainTime.IsMetLevelMaintainCondition.NoAccumulation';
			$ignoreTestCaseKeyList[] = 'SACB.EmptyPeriodMode.PeriodIsMet.OnLevelMaintainEnable.InMaintainTime.IsMetLevelMaintainCondition.AccumulationYesRegistrationDate';
			$ignoreTestCaseKeyList[] = 'SACB.EmptyPeriodMode.PeriodIsMet.OnLevelMaintainEnable.InMaintainTime.IsMetLevelMaintainCondition.AccumulationYesLastChangePeriod';
			$ignoreTestCaseKeyList[] = 'SACB.EmptyPeriodMode.PeriodIsMet.OnLevelMaintainEnable.InMaintainTime.NotMetLevelMaintainCondition.NoAccumulation';
			$ignoreTestCaseKeyList[] = 'SACB.EmptyPeriodMode.PeriodIsMet.OnLevelMaintainEnable.InMaintainTime.NotMetLevelMaintainCondition.AccumulationYesRegistrationDate';
			$ignoreTestCaseKeyList[] = 'SACB.EmptyPeriodMode.PeriodIsMet.OnLevelMaintainEnable.InMaintainTime.NotMetLevelMaintainCondition.AccumulationYesLastChangePeriod';
			$ignoreTestCaseKeyList[] = 'SACB.EmptyPeriodMode.PeriodIsMet.OnLevelMaintainEnable.OverMaintainTime.IsMetLevelMaintainCondition.NoAccumulation';
			$ignoreTestCaseKeyList[] = 'SACB.EmptyPeriodMode.PeriodIsMet.OnLevelMaintainEnable.OverMaintainTime.IsMetLevelMaintainCondition.AccumulationYesRegistrationDate';
			$ignoreTestCaseKeyList[] = 'SACB.EmptyPeriodMode.PeriodIsMet.OnLevelMaintainEnable.OverMaintainTime.IsMetLevelMaintainCondition.AccumulationYesLastChangePeriod';
			$ignoreTestCaseKeyList[] = 'SACB.EmptyPeriodMode.PeriodIsMet.OnLevelMaintainEnable.OverMaintainTime.NotMetLevelMaintainCondition.NoAccumulation';
			$ignoreTestCaseKeyList[] = 'SACB.EmptyPeriodMode.PeriodIsMet.OnLevelMaintainEnable.OverMaintainTime.NotMetLevelMaintainCondition.AccumulationYesRegistrationDate';
			$ignoreTestCaseKeyList[] = 'SACB.EmptyPeriodMode.PeriodIsMet.OnLevelMaintainEnable.OverMaintainTime.NotMetLevelMaintainCondition.AccumulationYesLastChangePeriod';
			$ignoreTestCaseKeyList[] = 'SACB.EmptyPeriodMode.PeriodIsMet.OffLevelMaintainEnable.InMaintainTime.IsMetLevelMaintainCondition.NoAccumulation';
			$ignoreTestCaseKeyList[] = 'SACB.EmptyPeriodMode.PeriodIsMet.OffLevelMaintainEnable.InMaintainTime.IsMetLevelMaintainCondition.AccumulationYesRegistrationDate';
			$ignoreTestCaseKeyList[] = 'SACB.EmptyPeriodMode.PeriodIsMet.OffLevelMaintainEnable.InMaintainTime.IsMetLevelMaintainCondition.AccumulationYesLastChangePeriod';
			$ignoreTestCaseKeyList[] = 'SACB.EmptyPeriodMode.PeriodIsMet.OffLevelMaintainEnable.InMaintainTime.NotMetLevelMaintainCondition.NoAccumulation';
			$ignoreTestCaseKeyList[] = 'SACB.EmptyPeriodMode.PeriodIsMet.OffLevelMaintainEnable.InMaintainTime.NotMetLevelMaintainCondition.AccumulationYesRegistrationDate';
			$ignoreTestCaseKeyList[] = 'SACB.EmptyPeriodMode.PeriodIsMet.OffLevelMaintainEnable.InMaintainTime.NotMetLevelMaintainCondition.AccumulationYesLastChangePeriod';
			$ignoreTestCaseKeyList[] = 'SACB.EmptyPeriodMode.PeriodIsMet.OffLevelMaintainEnable.OverMaintainTime.IsMetLevelMaintainCondition.NoAccumulation';
			$ignoreTestCaseKeyList[] = 'SACB.EmptyPeriodMode.PeriodIsMet.OffLevelMaintainEnable.OverMaintainTime.IsMetLevelMaintainCondition.AccumulationYesRegistrationDate';
			$ignoreTestCaseKeyList[] = 'SACB.EmptyPeriodMode.PeriodIsMet.OffLevelMaintainEnable.OverMaintainTime.IsMetLevelMaintainCondition.AccumulationYesLastChangePeriod';
			$ignoreTestCaseKeyList[] = 'SACB.EmptyPeriodMode.PeriodIsMet.OffLevelMaintainEnable.OverMaintainTime.NotMetLevelMaintainCondition.NoAccumulation';
			$ignoreTestCaseKeyList[] = 'SACB.EmptyPeriodMode.PeriodIsMet.OffLevelMaintainEnable.OverMaintainTime.NotMetLevelMaintainCondition.AccumulationYesRegistrationDate';
			$ignoreTestCaseKeyList[] = 'SACB.EmptyPeriodMode.PeriodIsMet.OffLevelMaintainEnable.OverMaintainTime.NotMetLevelMaintainCondition.AccumulationYesLastChangePeriod';
		// ignore EmptyPeriodMode.PeriodIsMet for CASB
			$ignoreTestCaseKeyList[] = 'CASB.EmptyPeriodMode.PeriodIsMet.OnLevelMaintainEnable.InMaintainTime.IsMetLevelMaintainCondition.NoAccumulation';
			$ignoreTestCaseKeyList[] = 'CASB.EmptyPeriodMode.PeriodIsMet.OnLevelMaintainEnable.InMaintainTime.IsMetLevelMaintainCondition.AccumulationYesRegistrationDate';
			$ignoreTestCaseKeyList[] = 'CASB.EmptyPeriodMode.PeriodIsMet.OnLevelMaintainEnable.InMaintainTime.IsMetLevelMaintainCondition.AccumulationYesLastChangePeriod';
			$ignoreTestCaseKeyList[] = 'CASB.EmptyPeriodMode.PeriodIsMet.OnLevelMaintainEnable.InMaintainTime.NotMetLevelMaintainCondition.NoAccumulation';
			$ignoreTestCaseKeyList[] = 'CASB.EmptyPeriodMode.PeriodIsMet.OnLevelMaintainEnable.InMaintainTime.NotMetLevelMaintainCondition.AccumulationYesRegistrationDate';
			$ignoreTestCaseKeyList[] = 'CASB.EmptyPeriodMode.PeriodIsMet.OnLevelMaintainEnable.InMaintainTime.NotMetLevelMaintainCondition.AccumulationYesLastChangePeriod';
			$ignoreTestCaseKeyList[] = 'CASB.EmptyPeriodMode.PeriodIsMet.OnLevelMaintainEnable.OverMaintainTime.IsMetLevelMaintainCondition.NoAccumulation';
			$ignoreTestCaseKeyList[] = 'CASB.EmptyPeriodMode.PeriodIsMet.OnLevelMaintainEnable.OverMaintainTime.IsMetLevelMaintainCondition.AccumulationYesRegistrationDate';
			$ignoreTestCaseKeyList[] = 'CASB.EmptyPeriodMode.PeriodIsMet.OnLevelMaintainEnable.OverMaintainTime.IsMetLevelMaintainCondition.AccumulationYesLastChangePeriod';
			$ignoreTestCaseKeyList[] = 'CASB.EmptyPeriodMode.PeriodIsMet.OnLevelMaintainEnable.OverMaintainTime.NotMetLevelMaintainCondition.NoAccumulation';
			$ignoreTestCaseKeyList[] = 'CASB.EmptyPeriodMode.PeriodIsMet.OnLevelMaintainEnable.OverMaintainTime.NotMetLevelMaintainCondition.AccumulationYesRegistrationDate';
			$ignoreTestCaseKeyList[] = 'CASB.EmptyPeriodMode.PeriodIsMet.OnLevelMaintainEnable.OverMaintainTime.NotMetLevelMaintainCondition.AccumulationYesLastChangePeriod';
			$ignoreTestCaseKeyList[] = 'CASB.EmptyPeriodMode.PeriodIsMet.OffLevelMaintainEnable.InMaintainTime.IsMetLevelMaintainCondition.NoAccumulation';
			$ignoreTestCaseKeyList[] = 'CASB.EmptyPeriodMode.PeriodIsMet.OffLevelMaintainEnable.InMaintainTime.IsMetLevelMaintainCondition.AccumulationYesRegistrationDate';
			$ignoreTestCaseKeyList[] = 'CASB.EmptyPeriodMode.PeriodIsMet.OffLevelMaintainEnable.InMaintainTime.IsMetLevelMaintainCondition.AccumulationYesLastChangePeriod';
			$ignoreTestCaseKeyList[] = 'CASB.EmptyPeriodMode.PeriodIsMet.OffLevelMaintainEnable.InMaintainTime.NotMetLevelMaintainCondition.NoAccumulation';
			$ignoreTestCaseKeyList[] = 'CASB.EmptyPeriodMode.PeriodIsMet.OffLevelMaintainEnable.InMaintainTime.NotMetLevelMaintainCondition.AccumulationYesRegistrationDate';
			$ignoreTestCaseKeyList[] = 'CASB.EmptyPeriodMode.PeriodIsMet.OffLevelMaintainEnable.InMaintainTime.NotMetLevelMaintainCondition.AccumulationYesLastChangePeriod';
			$ignoreTestCaseKeyList[] = 'CASB.EmptyPeriodMode.PeriodIsMet.OffLevelMaintainEnable.OverMaintainTime.IsMetLevelMaintainCondition.NoAccumulation';
			$ignoreTestCaseKeyList[] = 'CASB.EmptyPeriodMode.PeriodIsMet.OffLevelMaintainEnable.OverMaintainTime.IsMetLevelMaintainCondition.AccumulationYesRegistrationDate';
			$ignoreTestCaseKeyList[] = 'CASB.EmptyPeriodMode.PeriodIsMet.OffLevelMaintainEnable.OverMaintainTime.IsMetLevelMaintainCondition.AccumulationYesLastChangePeriod';
			$ignoreTestCaseKeyList[] = 'CASB.EmptyPeriodMode.PeriodIsMet.OffLevelMaintainEnable.OverMaintainTime.NotMetLevelMaintainCondition.NoAccumulation';
			$ignoreTestCaseKeyList[] = 'CASB.EmptyPeriodMode.PeriodIsMet.OffLevelMaintainEnable.OverMaintainTime.NotMetLevelMaintainCondition.AccumulationYesRegistrationDate';
			$ignoreTestCaseKeyList[] = 'CASB.EmptyPeriodMode.PeriodIsMet.OffLevelMaintainEnable.OverMaintainTime.NotMetLevelMaintainCondition.AccumulationYesLastChangePeriod';
		// ignore EmptyPeriodMode.PeriodIsMet for SASB
			$ignoreTestCaseKeyList[] = 'SASB.EmptyPeriodMode.PeriodIsMet.OnLevelMaintainEnable.InMaintainTime.IsMetLevelMaintainCondition.NoAccumulation';
			$ignoreTestCaseKeyList[] = 'SASB.EmptyPeriodMode.PeriodIsMet.OnLevelMaintainEnable.InMaintainTime.IsMetLevelMaintainCondition.AccumulationYesRegistrationDate';
			$ignoreTestCaseKeyList[] = 'SASB.EmptyPeriodMode.PeriodIsMet.OnLevelMaintainEnable.InMaintainTime.IsMetLevelMaintainCondition.AccumulationYesLastChangePeriod';
			$ignoreTestCaseKeyList[] = 'SASB.EmptyPeriodMode.PeriodIsMet.OnLevelMaintainEnable.InMaintainTime.NotMetLevelMaintainCondition.NoAccumulation';
			$ignoreTestCaseKeyList[] = 'SASB.EmptyPeriodMode.PeriodIsMet.OnLevelMaintainEnable.InMaintainTime.NotMetLevelMaintainCondition.AccumulationYesRegistrationDate';
			$ignoreTestCaseKeyList[] = 'SASB.EmptyPeriodMode.PeriodIsMet.OnLevelMaintainEnable.InMaintainTime.NotMetLevelMaintainCondition.AccumulationYesLastChangePeriod';
			$ignoreTestCaseKeyList[] = 'SASB.EmptyPeriodMode.PeriodIsMet.OnLevelMaintainEnable.OverMaintainTime.IsMetLevelMaintainCondition.NoAccumulation';
			$ignoreTestCaseKeyList[] = 'SASB.EmptyPeriodMode.PeriodIsMet.OnLevelMaintainEnable.OverMaintainTime.IsMetLevelMaintainCondition.AccumulationYesRegistrationDate';
			$ignoreTestCaseKeyList[] = 'SASB.EmptyPeriodMode.PeriodIsMet.OnLevelMaintainEnable.OverMaintainTime.IsMetLevelMaintainCondition.AccumulationYesLastChangePeriod';
			$ignoreTestCaseKeyList[] = 'SASB.EmptyPeriodMode.PeriodIsMet.OnLevelMaintainEnable.OverMaintainTime.NotMetLevelMaintainCondition.NoAccumulation';
			$ignoreTestCaseKeyList[] = 'SASB.EmptyPeriodMode.PeriodIsMet.OnLevelMaintainEnable.OverMaintainTime.NotMetLevelMaintainCondition.AccumulationYesRegistrationDate';
			$ignoreTestCaseKeyList[] = 'SASB.EmptyPeriodMode.PeriodIsMet.OnLevelMaintainEnable.OverMaintainTime.NotMetLevelMaintainCondition.AccumulationYesLastChangePeriod';
			$ignoreTestCaseKeyList[] = 'SASB.EmptyPeriodMode.PeriodIsMet.OffLevelMaintainEnable.InMaintainTime.IsMetLevelMaintainCondition.NoAccumulation';
			$ignoreTestCaseKeyList[] = 'SASB.EmptyPeriodMode.PeriodIsMet.OffLevelMaintainEnable.InMaintainTime.IsMetLevelMaintainCondition.AccumulationYesRegistrationDate';
			$ignoreTestCaseKeyList[] = 'SASB.EmptyPeriodMode.PeriodIsMet.OffLevelMaintainEnable.InMaintainTime.IsMetLevelMaintainCondition.AccumulationYesLastChangePeriod';
			$ignoreTestCaseKeyList[] = 'SASB.EmptyPeriodMode.PeriodIsMet.OffLevelMaintainEnable.InMaintainTime.NotMetLevelMaintainCondition.NoAccumulation';
			$ignoreTestCaseKeyList[] = 'SASB.EmptyPeriodMode.PeriodIsMet.OffLevelMaintainEnable.InMaintainTime.NotMetLevelMaintainCondition.AccumulationYesRegistrationDate';
			$ignoreTestCaseKeyList[] = 'SASB.EmptyPeriodMode.PeriodIsMet.OffLevelMaintainEnable.InMaintainTime.NotMetLevelMaintainCondition.AccumulationYesLastChangePeriod';
			$ignoreTestCaseKeyList[] = 'SASB.EmptyPeriodMode.PeriodIsMet.OffLevelMaintainEnable.OverMaintainTime.IsMetLevelMaintainCondition.NoAccumulation';
			$ignoreTestCaseKeyList[] = 'SASB.EmptyPeriodMode.PeriodIsMet.OffLevelMaintainEnable.OverMaintainTime.IsMetLevelMaintainCondition.AccumulationYesRegistrationDate';
			$ignoreTestCaseKeyList[] = 'SASB.EmptyPeriodMode.PeriodIsMet.OffLevelMaintainEnable.OverMaintainTime.IsMetLevelMaintainCondition.AccumulationYesLastChangePeriod';
			$ignoreTestCaseKeyList[] = 'SASB.EmptyPeriodMode.PeriodIsMet.OffLevelMaintainEnable.OverMaintainTime.NotMetLevelMaintainCondition.NoAccumulation';
			$ignoreTestCaseKeyList[] = 'SASB.EmptyPeriodMode.PeriodIsMet.OffLevelMaintainEnable.OverMaintainTime.NotMetLevelMaintainCondition.AccumulationYesRegistrationDate';
			$ignoreTestCaseKeyList[] = 'SASB.EmptyPeriodMode.PeriodIsMet.OffLevelMaintainEnable.OverMaintainTime.NotMetLevelMaintainCondition.AccumulationYesLastChangePeriod';

		/// ==== PeriodNotMet.OffLevelMaintainEnable ====
			// keep this one case for test
			// $ignoreTestCaseKeyList[] = 'CACB.EmptyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.InMaintainTime.IsMetLevelMaintainCondition.NoAccumulation';
			$ignoreTestCaseKeyList[] = 'CACB.EmptyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.InMaintainTime.IsMetLevelMaintainCondition.AccumulationYesRegistrationDate';
			$ignoreTestCaseKeyList[] = 'CACB.EmptyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.InMaintainTime.IsMetLevelMaintainCondition.AccumulationYesLastChangePeriod';
			$ignoreTestCaseKeyList[] = 'CACB.EmptyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.InMaintainTime.NotMetLevelMaintainCondition.NoAccumulation';
			$ignoreTestCaseKeyList[] = 'CACB.EmptyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.InMaintainTime.NotMetLevelMaintainCondition.AccumulationYesRegistrationDate';
			$ignoreTestCaseKeyList[] = 'CACB.EmptyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.InMaintainTime.NotMetLevelMaintainCondition.AccumulationYesLastChangePeriod';
			$ignoreTestCaseKeyList[] = 'CACB.EmptyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.OverMaintainTime.IsMetLevelMaintainCondition.NoAccumulation';
			$ignoreTestCaseKeyList[] = 'CACB.EmptyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.OverMaintainTime.IsMetLevelMaintainCondition.AccumulationYesRegistrationDate';
			$ignoreTestCaseKeyList[] = 'CACB.EmptyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.OverMaintainTime.IsMetLevelMaintainCondition.AccumulationYesLastChangePeriod';
			$ignoreTestCaseKeyList[] = 'CACB.EmptyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.OverMaintainTime.NotMetLevelMaintainCondition.NoAccumulation';
			$ignoreTestCaseKeyList[] = 'CACB.EmptyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.OverMaintainTime.NotMetLevelMaintainCondition.AccumulationYesRegistrationDate';
			$ignoreTestCaseKeyList[] = 'CACB.EmptyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.OverMaintainTime.NotMetLevelMaintainCondition.AccumulationYesLastChangePeriod';

			$ignoreTestCaseKeyList[] = 'CACB.DailyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.InMaintainTime.IsMetLevelMaintainCondition.NoAccumulation';
			$ignoreTestCaseKeyList[] = 'CACB.DailyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.InMaintainTime.IsMetLevelMaintainCondition.AccumulationYesRegistrationDate';
			$ignoreTestCaseKeyList[] = 'CACB.DailyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.InMaintainTime.IsMetLevelMaintainCondition.AccumulationYesLastChangePeriod';
			$ignoreTestCaseKeyList[] = 'CACB.DailyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.InMaintainTime.NotMetLevelMaintainCondition.NoAccumulation';
			$ignoreTestCaseKeyList[] = 'CACB.DailyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.InMaintainTime.NotMetLevelMaintainCondition.AccumulationYesRegistrationDate';
			$ignoreTestCaseKeyList[] = 'CACB.DailyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.InMaintainTime.NotMetLevelMaintainCondition.AccumulationYesLastChangePeriod';
			$ignoreTestCaseKeyList[] = 'CACB.DailyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.OverMaintainTime.IsMetLevelMaintainCondition.NoAccumulation';
			$ignoreTestCaseKeyList[] = 'CACB.DailyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.OverMaintainTime.IsMetLevelMaintainCondition.AccumulationYesRegistrationDate';
			$ignoreTestCaseKeyList[] = 'CACB.DailyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.OverMaintainTime.IsMetLevelMaintainCondition.AccumulationYesLastChangePeriod';
			$ignoreTestCaseKeyList[] = 'CACB.DailyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.OverMaintainTime.NotMetLevelMaintainCondition.NoAccumulation';
			$ignoreTestCaseKeyList[] = 'CACB.DailyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.OverMaintainTime.NotMetLevelMaintainCondition.AccumulationYesRegistrationDate';
			$ignoreTestCaseKeyList[] = 'CACB.DailyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.OverMaintainTime.NotMetLevelMaintainCondition.AccumulationYesLastChangePeriod';

			$ignoreTestCaseKeyList[] = 'CACB.Weekly1PeriodMode.PeriodNotMet.OffLevelMaintainEnable.InMaintainTime.IsMetLevelMaintainCondition.NoAccumulation';
			$ignoreTestCaseKeyList[] = 'CACB.Weekly1PeriodMode.PeriodNotMet.OffLevelMaintainEnable.InMaintainTime.IsMetLevelMaintainCondition.AccumulationYesRegistrationDate';
			$ignoreTestCaseKeyList[] = 'CACB.Weekly1PeriodMode.PeriodNotMet.OffLevelMaintainEnable.InMaintainTime.IsMetLevelMaintainCondition.AccumulationYesLastChangePeriod';
			$ignoreTestCaseKeyList[] = 'CACB.Weekly1PeriodMode.PeriodNotMet.OffLevelMaintainEnable.InMaintainTime.NotMetLevelMaintainCondition.NoAccumulation';
			$ignoreTestCaseKeyList[] = 'CACB.Weekly1PeriodMode.PeriodNotMet.OffLevelMaintainEnable.InMaintainTime.NotMetLevelMaintainCondition.AccumulationYesRegistrationDate';
			$ignoreTestCaseKeyList[] = 'CACB.Weekly1PeriodMode.PeriodNotMet.OffLevelMaintainEnable.InMaintainTime.NotMetLevelMaintainCondition.AccumulationYesLastChangePeriod';
			$ignoreTestCaseKeyList[] = 'CACB.Weekly1PeriodMode.PeriodNotMet.OffLevelMaintainEnable.OverMaintainTime.IsMetLevelMaintainCondition.NoAccumulation';
			$ignoreTestCaseKeyList[] = 'CACB.Weekly1PeriodMode.PeriodNotMet.OffLevelMaintainEnable.OverMaintainTime.IsMetLevelMaintainCondition.AccumulationYesRegistrationDate';
			$ignoreTestCaseKeyList[] = 'CACB.Weekly1PeriodMode.PeriodNotMet.OffLevelMaintainEnable.OverMaintainTime.IsMetLevelMaintainCondition.AccumulationYesLastChangePeriod';
			$ignoreTestCaseKeyList[] = 'CACB.Weekly1PeriodMode.PeriodNotMet.OffLevelMaintainEnable.OverMaintainTime.NotMetLevelMaintainCondition.NoAccumulation';
			$ignoreTestCaseKeyList[] = 'CACB.Weekly1PeriodMode.PeriodNotMet.OffLevelMaintainEnable.OverMaintainTime.NotMetLevelMaintainCondition.AccumulationYesRegistrationDate';
			$ignoreTestCaseKeyList[] = 'CACB.Weekly1PeriodMode.PeriodNotMet.OffLevelMaintainEnable.OverMaintainTime.NotMetLevelMaintainCondition.AccumulationYesLastChangePeriod';

			$ignoreTestCaseKeyList[] = 'CACB.Monthly5PeriodMode.PeriodNotMet.OffLevelMaintainEnable.InMaintainTime.IsMetLevelMaintainCondition.NoAccumulation';
			$ignoreTestCaseKeyList[] = 'CACB.Monthly5PeriodMode.PeriodNotMet.OffLevelMaintainEnable.InMaintainTime.IsMetLevelMaintainCondition.AccumulationYesRegistrationDate';
			$ignoreTestCaseKeyList[] = 'CACB.Monthly5PeriodMode.PeriodNotMet.OffLevelMaintainEnable.InMaintainTime.IsMetLevelMaintainCondition.AccumulationYesLastChangePeriod';
			$ignoreTestCaseKeyList[] = 'CACB.Monthly5PeriodMode.PeriodNotMet.OffLevelMaintainEnable.InMaintainTime.NotMetLevelMaintainCondition.NoAccumulation';
			$ignoreTestCaseKeyList[] = 'CACB.Monthly5PeriodMode.PeriodNotMet.OffLevelMaintainEnable.InMaintainTime.NotMetLevelMaintainCondition.AccumulationYesRegistrationDate';
			$ignoreTestCaseKeyList[] = 'CACB.Monthly5PeriodMode.PeriodNotMet.OffLevelMaintainEnable.InMaintainTime.NotMetLevelMaintainCondition.AccumulationYesLastChangePeriod';
			$ignoreTestCaseKeyList[] = 'CACB.Monthly5PeriodMode.PeriodNotMet.OffLevelMaintainEnable.OverMaintainTime.IsMetLevelMaintainCondition.NoAccumulation';
			$ignoreTestCaseKeyList[] = 'CACB.Monthly5PeriodMode.PeriodNotMet.OffLevelMaintainEnable.OverMaintainTime.IsMetLevelMaintainCondition.AccumulationYesRegistrationDate';
			$ignoreTestCaseKeyList[] = 'CACB.Monthly5PeriodMode.PeriodNotMet.OffLevelMaintainEnable.OverMaintainTime.IsMetLevelMaintainCondition.AccumulationYesLastChangePeriod';
			$ignoreTestCaseKeyList[] = 'CACB.Monthly5PeriodMode.PeriodNotMet.OffLevelMaintainEnable.OverMaintainTime.NotMetLevelMaintainCondition.NoAccumulation';
			$ignoreTestCaseKeyList[] = 'CACB.Monthly5PeriodMode.PeriodNotMet.OffLevelMaintainEnable.OverMaintainTime.NotMetLevelMaintainCondition.AccumulationYesRegistrationDate';
			$ignoreTestCaseKeyList[] = 'CACB.Monthly5PeriodMode.PeriodNotMet.OffLevelMaintainEnable.OverMaintainTime.NotMetLevelMaintainCondition.AccumulationYesLastChangePeriod';
			// keep this one case for test
			// $ignoreTestCaseKeyList[] = 'SACB.EmptyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.InMaintainTime.IsMetLevelMaintainCondition.NoAccumulation';
			$ignoreTestCaseKeyList[] = 'SACB.EmptyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.InMaintainTime.IsMetLevelMaintainCondition.AccumulationYesRegistrationDate';
			$ignoreTestCaseKeyList[] = 'SACB.EmptyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.InMaintainTime.IsMetLevelMaintainCondition.AccumulationYesLastChangePeriod';
			$ignoreTestCaseKeyList[] = 'SACB.EmptyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.InMaintainTime.NotMetLevelMaintainCondition.NoAccumulation';
			$ignoreTestCaseKeyList[] = 'SACB.EmptyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.InMaintainTime.NotMetLevelMaintainCondition.AccumulationYesRegistrationDate';
			$ignoreTestCaseKeyList[] = 'SACB.EmptyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.InMaintainTime.NotMetLevelMaintainCondition.AccumulationYesLastChangePeriod';
			$ignoreTestCaseKeyList[] = 'SACB.EmptyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.OverMaintainTime.IsMetLevelMaintainCondition.NoAccumulation';
			$ignoreTestCaseKeyList[] = 'SACB.EmptyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.OverMaintainTime.IsMetLevelMaintainCondition.AccumulationYesRegistrationDate';
			$ignoreTestCaseKeyList[] = 'SACB.EmptyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.OverMaintainTime.IsMetLevelMaintainCondition.AccumulationYesLastChangePeriod';
			$ignoreTestCaseKeyList[] = 'SACB.EmptyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.OverMaintainTime.NotMetLevelMaintainCondition.NoAccumulation';
			$ignoreTestCaseKeyList[] = 'SACB.EmptyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.OverMaintainTime.NotMetLevelMaintainCondition.AccumulationYesRegistrationDate';
			$ignoreTestCaseKeyList[] = 'SACB.EmptyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.OverMaintainTime.NotMetLevelMaintainCondition.AccumulationYesLastChangePeriod';

			$ignoreTestCaseKeyList[] = 'SACB.DailyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.InMaintainTime.IsMetLevelMaintainCondition.NoAccumulation';
			$ignoreTestCaseKeyList[] = 'SACB.DailyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.InMaintainTime.IsMetLevelMaintainCondition.AccumulationYesRegistrationDate';
			$ignoreTestCaseKeyList[] = 'SACB.DailyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.InMaintainTime.IsMetLevelMaintainCondition.AccumulationYesLastChangePeriod';
			$ignoreTestCaseKeyList[] = 'SACB.DailyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.InMaintainTime.NotMetLevelMaintainCondition.NoAccumulation';
			$ignoreTestCaseKeyList[] = 'SACB.DailyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.InMaintainTime.NotMetLevelMaintainCondition.AccumulationYesRegistrationDate';
			$ignoreTestCaseKeyList[] = 'SACB.DailyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.InMaintainTime.NotMetLevelMaintainCondition.AccumulationYesLastChangePeriod';
			$ignoreTestCaseKeyList[] = 'SACB.DailyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.OverMaintainTime.IsMetLevelMaintainCondition.NoAccumulation';
			$ignoreTestCaseKeyList[] = 'SACB.DailyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.OverMaintainTime.IsMetLevelMaintainCondition.AccumulationYesRegistrationDate';
			$ignoreTestCaseKeyList[] = 'SACB.DailyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.OverMaintainTime.IsMetLevelMaintainCondition.AccumulationYesLastChangePeriod';
			$ignoreTestCaseKeyList[] = 'SACB.DailyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.OverMaintainTime.NotMetLevelMaintainCondition.NoAccumulation';
			$ignoreTestCaseKeyList[] = 'SACB.DailyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.OverMaintainTime.NotMetLevelMaintainCondition.AccumulationYesRegistrationDate';
			$ignoreTestCaseKeyList[] = 'SACB.DailyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.OverMaintainTime.NotMetLevelMaintainCondition.AccumulationYesLastChangePeriod';

			$ignoreTestCaseKeyList[] = 'SACB.Weekly1PeriodMode.PeriodNotMet.OffLevelMaintainEnable.InMaintainTime.IsMetLevelMaintainCondition.NoAccumulation';
			$ignoreTestCaseKeyList[] = 'SACB.Weekly1PeriodMode.PeriodNotMet.OffLevelMaintainEnable.InMaintainTime.IsMetLevelMaintainCondition.AccumulationYesRegistrationDate';
			$ignoreTestCaseKeyList[] = 'SACB.Weekly1PeriodMode.PeriodNotMet.OffLevelMaintainEnable.InMaintainTime.IsMetLevelMaintainCondition.AccumulationYesLastChangePeriod';
			$ignoreTestCaseKeyList[] = 'SACB.Weekly1PeriodMode.PeriodNotMet.OffLevelMaintainEnable.InMaintainTime.NotMetLevelMaintainCondition.NoAccumulation';
			$ignoreTestCaseKeyList[] = 'SACB.Weekly1PeriodMode.PeriodNotMet.OffLevelMaintainEnable.InMaintainTime.NotMetLevelMaintainCondition.AccumulationYesRegistrationDate';
			$ignoreTestCaseKeyList[] = 'SACB.Weekly1PeriodMode.PeriodNotMet.OffLevelMaintainEnable.InMaintainTime.NotMetLevelMaintainCondition.AccumulationYesLastChangePeriod';
			$ignoreTestCaseKeyList[] = 'SACB.Weekly1PeriodMode.PeriodNotMet.OffLevelMaintainEnable.OverMaintainTime.IsMetLevelMaintainCondition.NoAccumulation';
			$ignoreTestCaseKeyList[] = 'SACB.Weekly1PeriodMode.PeriodNotMet.OffLevelMaintainEnable.OverMaintainTime.IsMetLevelMaintainCondition.AccumulationYesRegistrationDate';
			$ignoreTestCaseKeyList[] = 'SACB.Weekly1PeriodMode.PeriodNotMet.OffLevelMaintainEnable.OverMaintainTime.IsMetLevelMaintainCondition.AccumulationYesLastChangePeriod';
			$ignoreTestCaseKeyList[] = 'SACB.Weekly1PeriodMode.PeriodNotMet.OffLevelMaintainEnable.OverMaintainTime.NotMetLevelMaintainCondition.NoAccumulation';
			$ignoreTestCaseKeyList[] = 'SACB.Weekly1PeriodMode.PeriodNotMet.OffLevelMaintainEnable.OverMaintainTime.NotMetLevelMaintainCondition.AccumulationYesRegistrationDate';
			$ignoreTestCaseKeyList[] = 'SACB.Weekly1PeriodMode.PeriodNotMet.OffLevelMaintainEnable.OverMaintainTime.NotMetLevelMaintainCondition.AccumulationYesLastChangePeriod';

			$ignoreTestCaseKeyList[] = 'SACB.Monthly5PeriodMode.PeriodNotMet.OffLevelMaintainEnable.InMaintainTime.IsMetLevelMaintainCondition.NoAccumulation';
			$ignoreTestCaseKeyList[] = 'SACB.Monthly5PeriodMode.PeriodNotMet.OffLevelMaintainEnable.InMaintainTime.IsMetLevelMaintainCondition.AccumulationYesRegistrationDate';
			$ignoreTestCaseKeyList[] = 'SACB.Monthly5PeriodMode.PeriodNotMet.OffLevelMaintainEnable.InMaintainTime.IsMetLevelMaintainCondition.AccumulationYesLastChangePeriod';
			$ignoreTestCaseKeyList[] = 'SACB.Monthly5PeriodMode.PeriodNotMet.OffLevelMaintainEnable.InMaintainTime.NotMetLevelMaintainCondition.NoAccumulation';
			$ignoreTestCaseKeyList[] = 'SACB.Monthly5PeriodMode.PeriodNotMet.OffLevelMaintainEnable.InMaintainTime.NotMetLevelMaintainCondition.AccumulationYesRegistrationDate';
			$ignoreTestCaseKeyList[] = 'SACB.Monthly5PeriodMode.PeriodNotMet.OffLevelMaintainEnable.InMaintainTime.NotMetLevelMaintainCondition.AccumulationYesLastChangePeriod';
			$ignoreTestCaseKeyList[] = 'SACB.Monthly5PeriodMode.PeriodNotMet.OffLevelMaintainEnable.OverMaintainTime.IsMetLevelMaintainCondition.NoAccumulation';
			$ignoreTestCaseKeyList[] = 'SACB.Monthly5PeriodMode.PeriodNotMet.OffLevelMaintainEnable.OverMaintainTime.IsMetLevelMaintainCondition.AccumulationYesRegistrationDate';
			$ignoreTestCaseKeyList[] = 'SACB.Monthly5PeriodMode.PeriodNotMet.OffLevelMaintainEnable.OverMaintainTime.IsMetLevelMaintainCondition.AccumulationYesLastChangePeriod';
			$ignoreTestCaseKeyList[] = 'SACB.Monthly5PeriodMode.PeriodNotMet.OffLevelMaintainEnable.OverMaintainTime.NotMetLevelMaintainCondition.NoAccumulation';
			$ignoreTestCaseKeyList[] = 'SACB.Monthly5PeriodMode.PeriodNotMet.OffLevelMaintainEnable.OverMaintainTime.NotMetLevelMaintainCondition.AccumulationYesRegistrationDate';
			$ignoreTestCaseKeyList[] = 'SACB.Monthly5PeriodMode.PeriodNotMet.OffLevelMaintainEnable.OverMaintainTime.NotMetLevelMaintainCondition.AccumulationYesLastChangePeriod';
			// keep this one case for test
			// $ignoreTestCaseKeyList[] = 'CASB.EmptyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.InMaintainTime.IsMetLevelMaintainCondition.NoAccumulation';
			$ignoreTestCaseKeyList[] = 'CASB.EmptyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.InMaintainTime.IsMetLevelMaintainCondition.AccumulationYesRegistrationDate';
			$ignoreTestCaseKeyList[] = 'CASB.EmptyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.InMaintainTime.IsMetLevelMaintainCondition.AccumulationYesLastChangePeriod';
			$ignoreTestCaseKeyList[] = 'CASB.EmptyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.InMaintainTime.NotMetLevelMaintainCondition.NoAccumulation';
			$ignoreTestCaseKeyList[] = 'CASB.EmptyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.InMaintainTime.NotMetLevelMaintainCondition.AccumulationYesRegistrationDate';
			$ignoreTestCaseKeyList[] = 'CASB.EmptyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.InMaintainTime.NotMetLevelMaintainCondition.AccumulationYesLastChangePeriod';
			$ignoreTestCaseKeyList[] = 'CASB.EmptyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.OverMaintainTime.IsMetLevelMaintainCondition.NoAccumulation';
			$ignoreTestCaseKeyList[] = 'CASB.EmptyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.OverMaintainTime.IsMetLevelMaintainCondition.AccumulationYesRegistrationDate';
			$ignoreTestCaseKeyList[] = 'CASB.EmptyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.OverMaintainTime.IsMetLevelMaintainCondition.AccumulationYesLastChangePeriod';
			$ignoreTestCaseKeyList[] = 'CASB.EmptyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.OverMaintainTime.NotMetLevelMaintainCondition.NoAccumulation';
			$ignoreTestCaseKeyList[] = 'CASB.EmptyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.OverMaintainTime.NotMetLevelMaintainCondition.AccumulationYesRegistrationDate';
			$ignoreTestCaseKeyList[] = 'CASB.EmptyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.OverMaintainTime.NotMetLevelMaintainCondition.AccumulationYesLastChangePeriod';

			$ignoreTestCaseKeyList[] = 'CASB.DailyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.InMaintainTime.IsMetLevelMaintainCondition.NoAccumulation';
			$ignoreTestCaseKeyList[] = 'CASB.DailyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.InMaintainTime.IsMetLevelMaintainCondition.AccumulationYesRegistrationDate';
			$ignoreTestCaseKeyList[] = 'CASB.DailyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.InMaintainTime.IsMetLevelMaintainCondition.AccumulationYesLastChangePeriod';
			$ignoreTestCaseKeyList[] = 'CASB.DailyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.InMaintainTime.NotMetLevelMaintainCondition.NoAccumulation';
			$ignoreTestCaseKeyList[] = 'CASB.DailyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.InMaintainTime.NotMetLevelMaintainCondition.AccumulationYesRegistrationDate';
			$ignoreTestCaseKeyList[] = 'CASB.DailyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.InMaintainTime.NotMetLevelMaintainCondition.AccumulationYesLastChangePeriod';
			$ignoreTestCaseKeyList[] = 'CASB.DailyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.OverMaintainTime.IsMetLevelMaintainCondition.NoAccumulation';
			$ignoreTestCaseKeyList[] = 'CASB.DailyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.OverMaintainTime.IsMetLevelMaintainCondition.AccumulationYesRegistrationDate';
			$ignoreTestCaseKeyList[] = 'CASB.DailyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.OverMaintainTime.IsMetLevelMaintainCondition.AccumulationYesLastChangePeriod';
			$ignoreTestCaseKeyList[] = 'CASB.DailyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.OverMaintainTime.NotMetLevelMaintainCondition.NoAccumulation';
			$ignoreTestCaseKeyList[] = 'CASB.DailyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.OverMaintainTime.NotMetLevelMaintainCondition.AccumulationYesRegistrationDate';
			$ignoreTestCaseKeyList[] = 'CASB.DailyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.OverMaintainTime.NotMetLevelMaintainCondition.AccumulationYesLastChangePeriod';

			$ignoreTestCaseKeyList[] = 'CASB.Weekly1PeriodMode.PeriodNotMet.OffLevelMaintainEnable.InMaintainTime.IsMetLevelMaintainCondition.NoAccumulation';
			$ignoreTestCaseKeyList[] = 'CASB.Weekly1PeriodMode.PeriodNotMet.OffLevelMaintainEnable.InMaintainTime.IsMetLevelMaintainCondition.AccumulationYesRegistrationDate';
			$ignoreTestCaseKeyList[] = 'CASB.Weekly1PeriodMode.PeriodNotMet.OffLevelMaintainEnable.InMaintainTime.IsMetLevelMaintainCondition.AccumulationYesLastChangePeriod';
			$ignoreTestCaseKeyList[] = 'CASB.Weekly1PeriodMode.PeriodNotMet.OffLevelMaintainEnable.InMaintainTime.NotMetLevelMaintainCondition.NoAccumulation';
			$ignoreTestCaseKeyList[] = 'CASB.Weekly1PeriodMode.PeriodNotMet.OffLevelMaintainEnable.InMaintainTime.NotMetLevelMaintainCondition.AccumulationYesRegistrationDate';
			$ignoreTestCaseKeyList[] = 'CASB.Weekly1PeriodMode.PeriodNotMet.OffLevelMaintainEnable.InMaintainTime.NotMetLevelMaintainCondition.AccumulationYesLastChangePeriod';
			$ignoreTestCaseKeyList[] = 'CASB.Weekly1PeriodMode.PeriodNotMet.OffLevelMaintainEnable.OverMaintainTime.IsMetLevelMaintainCondition.NoAccumulation';
			$ignoreTestCaseKeyList[] = 'CASB.Weekly1PeriodMode.PeriodNotMet.OffLevelMaintainEnable.OverMaintainTime.IsMetLevelMaintainCondition.AccumulationYesRegistrationDate';
			$ignoreTestCaseKeyList[] = 'CASB.Weekly1PeriodMode.PeriodNotMet.OffLevelMaintainEnable.OverMaintainTime.IsMetLevelMaintainCondition.AccumulationYesLastChangePeriod';
			$ignoreTestCaseKeyList[] = 'CASB.Weekly1PeriodMode.PeriodNotMet.OffLevelMaintainEnable.OverMaintainTime.NotMetLevelMaintainCondition.NoAccumulation';
			$ignoreTestCaseKeyList[] = 'CASB.Weekly1PeriodMode.PeriodNotMet.OffLevelMaintainEnable.OverMaintainTime.NotMetLevelMaintainCondition.AccumulationYesRegistrationDate';
			$ignoreTestCaseKeyList[] = 'CASB.Weekly1PeriodMode.PeriodNotMet.OffLevelMaintainEnable.OverMaintainTime.NotMetLevelMaintainCondition.AccumulationYesLastChangePeriod';

			$ignoreTestCaseKeyList[] = 'CASB.Monthly5PeriodMode.PeriodNotMet.OffLevelMaintainEnable.InMaintainTime.IsMetLevelMaintainCondition.NoAccumulation';
			$ignoreTestCaseKeyList[] = 'CASB.Monthly5PeriodMode.PeriodNotMet.OffLevelMaintainEnable.InMaintainTime.IsMetLevelMaintainCondition.AccumulationYesRegistrationDate';
			$ignoreTestCaseKeyList[] = 'CASB.Monthly5PeriodMode.PeriodNotMet.OffLevelMaintainEnable.InMaintainTime.IsMetLevelMaintainCondition.AccumulationYesLastChangePeriod';
			$ignoreTestCaseKeyList[] = 'CASB.Monthly5PeriodMode.PeriodNotMet.OffLevelMaintainEnable.InMaintainTime.NotMetLevelMaintainCondition.NoAccumulation';
			$ignoreTestCaseKeyList[] = 'CASB.Monthly5PeriodMode.PeriodNotMet.OffLevelMaintainEnable.InMaintainTime.NotMetLevelMaintainCondition.AccumulationYesRegistrationDate';
			$ignoreTestCaseKeyList[] = 'CASB.Monthly5PeriodMode.PeriodNotMet.OffLevelMaintainEnable.InMaintainTime.NotMetLevelMaintainCondition.AccumulationYesLastChangePeriod';
			$ignoreTestCaseKeyList[] = 'CASB.Monthly5PeriodMode.PeriodNotMet.OffLevelMaintainEnable.OverMaintainTime.IsMetLevelMaintainCondition.NoAccumulation';
			$ignoreTestCaseKeyList[] = 'CASB.Monthly5PeriodMode.PeriodNotMet.OffLevelMaintainEnable.OverMaintainTime.IsMetLevelMaintainCondition.AccumulationYesRegistrationDate';
			$ignoreTestCaseKeyList[] = 'CASB.Monthly5PeriodMode.PeriodNotMet.OffLevelMaintainEnable.OverMaintainTime.IsMetLevelMaintainCondition.AccumulationYesLastChangePeriod';
			$ignoreTestCaseKeyList[] = 'CASB.Monthly5PeriodMode.PeriodNotMet.OffLevelMaintainEnable.OverMaintainTime.NotMetLevelMaintainCondition.NoAccumulation';
			$ignoreTestCaseKeyList[] = 'CASB.Monthly5PeriodMode.PeriodNotMet.OffLevelMaintainEnable.OverMaintainTime.NotMetLevelMaintainCondition.AccumulationYesRegistrationDate';
			$ignoreTestCaseKeyList[] = 'CASB.Monthly5PeriodMode.PeriodNotMet.OffLevelMaintainEnable.OverMaintainTime.NotMetLevelMaintainCondition.AccumulationYesLastChangePeriod';
			// keep this one case for test
			// $ignoreTestCaseKeyList[] = 'SASB.EmptyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.InMaintainTime.IsMetLevelMaintainCondition.NoAccumulation';
			$ignoreTestCaseKeyList[] = 'SASB.EmptyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.InMaintainTime.IsMetLevelMaintainCondition.AccumulationYesRegistrationDate';
			$ignoreTestCaseKeyList[] = 'SASB.EmptyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.InMaintainTime.IsMetLevelMaintainCondition.AccumulationYesLastChangePeriod';
			$ignoreTestCaseKeyList[] = 'SASB.EmptyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.InMaintainTime.NotMetLevelMaintainCondition.NoAccumulation';
			$ignoreTestCaseKeyList[] = 'SASB.EmptyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.InMaintainTime.NotMetLevelMaintainCondition.AccumulationYesRegistrationDate';
			$ignoreTestCaseKeyList[] = 'SASB.EmptyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.InMaintainTime.NotMetLevelMaintainCondition.AccumulationYesLastChangePeriod';
			$ignoreTestCaseKeyList[] = 'SASB.EmptyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.OverMaintainTime.IsMetLevelMaintainCondition.NoAccumulation';
			$ignoreTestCaseKeyList[] = 'SASB.EmptyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.OverMaintainTime.IsMetLevelMaintainCondition.AccumulationYesRegistrationDate';
			$ignoreTestCaseKeyList[] = 'SASB.EmptyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.OverMaintainTime.IsMetLevelMaintainCondition.AccumulationYesLastChangePeriod';
			$ignoreTestCaseKeyList[] = 'SASB.EmptyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.OverMaintainTime.NotMetLevelMaintainCondition.NoAccumulation';
			$ignoreTestCaseKeyList[] = 'SASB.EmptyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.OverMaintainTime.NotMetLevelMaintainCondition.AccumulationYesRegistrationDate';
			$ignoreTestCaseKeyList[] = 'SASB.EmptyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.OverMaintainTime.NotMetLevelMaintainCondition.AccumulationYesLastChangePeriod';

			$ignoreTestCaseKeyList[] = 'SASB.DailyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.InMaintainTime.IsMetLevelMaintainCondition.NoAccumulation';
			$ignoreTestCaseKeyList[] = 'SASB.DailyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.InMaintainTime.IsMetLevelMaintainCondition.AccumulationYesRegistrationDate';
			$ignoreTestCaseKeyList[] = 'SASB.DailyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.InMaintainTime.IsMetLevelMaintainCondition.AccumulationYesLastChangePeriod';
			$ignoreTestCaseKeyList[] = 'SASB.DailyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.InMaintainTime.NotMetLevelMaintainCondition.NoAccumulation';
			$ignoreTestCaseKeyList[] = 'SASB.DailyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.InMaintainTime.NotMetLevelMaintainCondition.AccumulationYesRegistrationDate';
			$ignoreTestCaseKeyList[] = 'SASB.DailyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.InMaintainTime.NotMetLevelMaintainCondition.AccumulationYesLastChangePeriod';
			$ignoreTestCaseKeyList[] = 'SASB.DailyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.OverMaintainTime.IsMetLevelMaintainCondition.NoAccumulation';
			$ignoreTestCaseKeyList[] = 'SASB.DailyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.OverMaintainTime.IsMetLevelMaintainCondition.AccumulationYesRegistrationDate';
			$ignoreTestCaseKeyList[] = 'SASB.DailyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.OverMaintainTime.IsMetLevelMaintainCondition.AccumulationYesLastChangePeriod';
			$ignoreTestCaseKeyList[] = 'SASB.DailyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.OverMaintainTime.NotMetLevelMaintainCondition.NoAccumulation';
			$ignoreTestCaseKeyList[] = 'SASB.DailyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.OverMaintainTime.NotMetLevelMaintainCondition.AccumulationYesRegistrationDate';
			$ignoreTestCaseKeyList[] = 'SASB.DailyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.OverMaintainTime.NotMetLevelMaintainCondition.AccumulationYesLastChangePeriod';

			$ignoreTestCaseKeyList[] = 'SASB.Weekly1PeriodMode.PeriodNotMet.OffLevelMaintainEnable.InMaintainTime.IsMetLevelMaintainCondition.NoAccumulation';
			$ignoreTestCaseKeyList[] = 'SASB.Weekly1PeriodMode.PeriodNotMet.OffLevelMaintainEnable.InMaintainTime.IsMetLevelMaintainCondition.AccumulationYesRegistrationDate';
			$ignoreTestCaseKeyList[] = 'SASB.Weekly1PeriodMode.PeriodNotMet.OffLevelMaintainEnable.InMaintainTime.IsMetLevelMaintainCondition.AccumulationYesLastChangePeriod';
			$ignoreTestCaseKeyList[] = 'SASB.Weekly1PeriodMode.PeriodNotMet.OffLevelMaintainEnable.InMaintainTime.NotMetLevelMaintainCondition.NoAccumulation';
			$ignoreTestCaseKeyList[] = 'SASB.Weekly1PeriodMode.PeriodNotMet.OffLevelMaintainEnable.InMaintainTime.NotMetLevelMaintainCondition.AccumulationYesRegistrationDate';
			$ignoreTestCaseKeyList[] = 'SASB.Weekly1PeriodMode.PeriodNotMet.OffLevelMaintainEnable.InMaintainTime.NotMetLevelMaintainCondition.AccumulationYesLastChangePeriod';
			$ignoreTestCaseKeyList[] = 'SASB.Weekly1PeriodMode.PeriodNotMet.OffLevelMaintainEnable.OverMaintainTime.IsMetLevelMaintainCondition.NoAccumulation';
			$ignoreTestCaseKeyList[] = 'SASB.Weekly1PeriodMode.PeriodNotMet.OffLevelMaintainEnable.OverMaintainTime.IsMetLevelMaintainCondition.AccumulationYesRegistrationDate';
			$ignoreTestCaseKeyList[] = 'SASB.Weekly1PeriodMode.PeriodNotMet.OffLevelMaintainEnable.OverMaintainTime.IsMetLevelMaintainCondition.AccumulationYesLastChangePeriod';
			$ignoreTestCaseKeyList[] = 'SASB.Weekly1PeriodMode.PeriodNotMet.OffLevelMaintainEnable.OverMaintainTime.NotMetLevelMaintainCondition.NoAccumulation';
			$ignoreTestCaseKeyList[] = 'SASB.Weekly1PeriodMode.PeriodNotMet.OffLevelMaintainEnable.OverMaintainTime.NotMetLevelMaintainCondition.AccumulationYesRegistrationDate';
			$ignoreTestCaseKeyList[] = 'SASB.Weekly1PeriodMode.PeriodNotMet.OffLevelMaintainEnable.OverMaintainTime.NotMetLevelMaintainCondition.AccumulationYesLastChangePeriod';

			$ignoreTestCaseKeyList[] = 'SASB.Monthly5PeriodMode.PeriodNotMet.OffLevelMaintainEnable.InMaintainTime.IsMetLevelMaintainCondition.NoAccumulation';
			$ignoreTestCaseKeyList[] = 'SASB.Monthly5PeriodMode.PeriodNotMet.OffLevelMaintainEnable.InMaintainTime.IsMetLevelMaintainCondition.AccumulationYesRegistrationDate';
			$ignoreTestCaseKeyList[] = 'SASB.Monthly5PeriodMode.PeriodNotMet.OffLevelMaintainEnable.InMaintainTime.IsMetLevelMaintainCondition.AccumulationYesLastChangePeriod';
			$ignoreTestCaseKeyList[] = 'SASB.Monthly5PeriodMode.PeriodNotMet.OffLevelMaintainEnable.InMaintainTime.NotMetLevelMaintainCondition.NoAccumulation';
			$ignoreTestCaseKeyList[] = 'SASB.Monthly5PeriodMode.PeriodNotMet.OffLevelMaintainEnable.InMaintainTime.NotMetLevelMaintainCondition.AccumulationYesRegistrationDate';
			$ignoreTestCaseKeyList[] = 'SASB.Monthly5PeriodMode.PeriodNotMet.OffLevelMaintainEnable.InMaintainTime.NotMetLevelMaintainCondition.AccumulationYesLastChangePeriod';
			$ignoreTestCaseKeyList[] = 'SASB.Monthly5PeriodMode.PeriodNotMet.OffLevelMaintainEnable.OverMaintainTime.IsMetLevelMaintainCondition.NoAccumulation';
			$ignoreTestCaseKeyList[] = 'SASB.Monthly5PeriodMode.PeriodNotMet.OffLevelMaintainEnable.OverMaintainTime.IsMetLevelMaintainCondition.AccumulationYesRegistrationDate';
			$ignoreTestCaseKeyList[] = 'SASB.Monthly5PeriodMode.PeriodNotMet.OffLevelMaintainEnable.OverMaintainTime.IsMetLevelMaintainCondition.AccumulationYesLastChangePeriod';
			$ignoreTestCaseKeyList[] = 'SASB.Monthly5PeriodMode.PeriodNotMet.OffLevelMaintainEnable.OverMaintainTime.NotMetLevelMaintainCondition.NoAccumulation';
			$ignoreTestCaseKeyList[] = 'SASB.Monthly5PeriodMode.PeriodNotMet.OffLevelMaintainEnable.OverMaintainTime.NotMetLevelMaintainCondition.AccumulationYesRegistrationDate';
			$ignoreTestCaseKeyList[] = 'SASB.Monthly5PeriodMode.PeriodNotMet.OffLevelMaintainEnable.OverMaintainTime.NotMetLevelMaintainCondition.AccumulationYesLastChangePeriod';

		// DailyPeriodMode.PeriodNotMet 每天都會降級，則都會符合 Period ，無法不符合。
			$ignoreTestCaseKeyList[] = 'CACB.DailyPeriodMode.PeriodNotMet.OnLevelMaintainEnable.InMaintainTime.IsMetLevelMaintainCondition.NoAccumulation';
			$ignoreTestCaseKeyList[] = 'CACB.DailyPeriodMode.PeriodNotMet.OnLevelMaintainEnable.InMaintainTime.IsMetLevelMaintainCondition.AccumulationYesRegistrationDate';
			$ignoreTestCaseKeyList[] = 'CACB.DailyPeriodMode.PeriodNotMet.OnLevelMaintainEnable.InMaintainTime.IsMetLevelMaintainCondition.AccumulationYesLastChangePeriod';
			$ignoreTestCaseKeyList[] = 'CACB.DailyPeriodMode.PeriodNotMet.OnLevelMaintainEnable.InMaintainTime.NotMetLevelMaintainCondition.NoAccumulation';
			$ignoreTestCaseKeyList[] = 'CACB.DailyPeriodMode.PeriodNotMet.OnLevelMaintainEnable.InMaintainTime.NotMetLevelMaintainCondition.AccumulationYesRegistrationDate';
			$ignoreTestCaseKeyList[] = 'CACB.DailyPeriodMode.PeriodNotMet.OnLevelMaintainEnable.InMaintainTime.NotMetLevelMaintainCondition.AccumulationYesLastChangePeriod';
			$ignoreTestCaseKeyList[] = 'CACB.DailyPeriodMode.PeriodNotMet.OnLevelMaintainEnable.OverMaintainTime.IsMetLevelMaintainCondition.NoAccumulation';
			$ignoreTestCaseKeyList[] = 'CACB.DailyPeriodMode.PeriodNotMet.OnLevelMaintainEnable.OverMaintainTime.IsMetLevelMaintainCondition.AccumulationYesRegistrationDate';
			$ignoreTestCaseKeyList[] = 'CACB.DailyPeriodMode.PeriodNotMet.OnLevelMaintainEnable.OverMaintainTime.IsMetLevelMaintainCondition.AccumulationYesLastChangePeriod';
			$ignoreTestCaseKeyList[] = 'CACB.DailyPeriodMode.PeriodNotMet.OnLevelMaintainEnable.OverMaintainTime.NotMetLevelMaintainCondition.NoAccumulation';
			$ignoreTestCaseKeyList[] = 'CACB.DailyPeriodMode.PeriodNotMet.OnLevelMaintainEnable.OverMaintainTime.NotMetLevelMaintainCondition.AccumulationYesRegistrationDate';
			$ignoreTestCaseKeyList[] = 'CACB.DailyPeriodMode.PeriodNotMet.OnLevelMaintainEnable.OverMaintainTime.NotMetLevelMaintainCondition.AccumulationYesLastChangePeriod';
			$ignoreTestCaseKeyList[] = 'SACB.DailyPeriodMode.PeriodNotMet.OnLevelMaintainEnable.InMaintainTime.IsMetLevelMaintainCondition.NoAccumulation';
			$ignoreTestCaseKeyList[] = 'SACB.DailyPeriodMode.PeriodNotMet.OnLevelMaintainEnable.InMaintainTime.IsMetLevelMaintainCondition.AccumulationYesRegistrationDate';
			$ignoreTestCaseKeyList[] = 'SACB.DailyPeriodMode.PeriodNotMet.OnLevelMaintainEnable.InMaintainTime.IsMetLevelMaintainCondition.AccumulationYesLastChangePeriod';
			$ignoreTestCaseKeyList[] = 'SACB.DailyPeriodMode.PeriodNotMet.OnLevelMaintainEnable.InMaintainTime.NotMetLevelMaintainCondition.NoAccumulation';
			$ignoreTestCaseKeyList[] = 'SACB.DailyPeriodMode.PeriodNotMet.OnLevelMaintainEnable.InMaintainTime.NotMetLevelMaintainCondition.AccumulationYesRegistrationDate';
			$ignoreTestCaseKeyList[] = 'SACB.DailyPeriodMode.PeriodNotMet.OnLevelMaintainEnable.InMaintainTime.NotMetLevelMaintainCondition.AccumulationYesLastChangePeriod';
			$ignoreTestCaseKeyList[] = 'SACB.DailyPeriodMode.PeriodNotMet.OnLevelMaintainEnable.OverMaintainTime.IsMetLevelMaintainCondition.NoAccumulation';
			$ignoreTestCaseKeyList[] = 'SACB.DailyPeriodMode.PeriodNotMet.OnLevelMaintainEnable.OverMaintainTime.IsMetLevelMaintainCondition.AccumulationYesRegistrationDate';
			$ignoreTestCaseKeyList[] = 'SACB.DailyPeriodMode.PeriodNotMet.OnLevelMaintainEnable.OverMaintainTime.IsMetLevelMaintainCondition.AccumulationYesLastChangePeriod';
			$ignoreTestCaseKeyList[] = 'SACB.DailyPeriodMode.PeriodNotMet.OnLevelMaintainEnable.OverMaintainTime.NotMetLevelMaintainCondition.NoAccumulation';
			$ignoreTestCaseKeyList[] = 'SACB.DailyPeriodMode.PeriodNotMet.OnLevelMaintainEnable.OverMaintainTime.NotMetLevelMaintainCondition.AccumulationYesRegistrationDate';
			$ignoreTestCaseKeyList[] = 'SACB.DailyPeriodMode.PeriodNotMet.OnLevelMaintainEnable.OverMaintainTime.NotMetLevelMaintainCondition.AccumulationYesLastChangePeriod';
			$ignoreTestCaseKeyList[] = 'CASB.DailyPeriodMode.PeriodNotMet.OnLevelMaintainEnable.InMaintainTime.IsMetLevelMaintainCondition.NoAccumulation';
			$ignoreTestCaseKeyList[] = 'CASB.DailyPeriodMode.PeriodNotMet.OnLevelMaintainEnable.InMaintainTime.IsMetLevelMaintainCondition.AccumulationYesRegistrationDate';
			$ignoreTestCaseKeyList[] = 'CASB.DailyPeriodMode.PeriodNotMet.OnLevelMaintainEnable.InMaintainTime.IsMetLevelMaintainCondition.AccumulationYesLastChangePeriod';
			$ignoreTestCaseKeyList[] = 'CASB.DailyPeriodMode.PeriodNotMet.OnLevelMaintainEnable.InMaintainTime.NotMetLevelMaintainCondition.NoAccumulation';
			$ignoreTestCaseKeyList[] = 'CASB.DailyPeriodMode.PeriodNotMet.OnLevelMaintainEnable.InMaintainTime.NotMetLevelMaintainCondition.AccumulationYesRegistrationDate';
			$ignoreTestCaseKeyList[] = 'CASB.DailyPeriodMode.PeriodNotMet.OnLevelMaintainEnable.InMaintainTime.NotMetLevelMaintainCondition.AccumulationYesLastChangePeriod';
			$ignoreTestCaseKeyList[] = 'CASB.DailyPeriodMode.PeriodNotMet.OnLevelMaintainEnable.OverMaintainTime.IsMetLevelMaintainCondition.NoAccumulation';
			$ignoreTestCaseKeyList[] = 'CASB.DailyPeriodMode.PeriodNotMet.OnLevelMaintainEnable.OverMaintainTime.IsMetLevelMaintainCondition.AccumulationYesRegistrationDate';
			$ignoreTestCaseKeyList[] = 'CASB.DailyPeriodMode.PeriodNotMet.OnLevelMaintainEnable.OverMaintainTime.IsMetLevelMaintainCondition.AccumulationYesLastChangePeriod';
			$ignoreTestCaseKeyList[] = 'CASB.DailyPeriodMode.PeriodNotMet.OnLevelMaintainEnable.OverMaintainTime.NotMetLevelMaintainCondition.NoAccumulation';
			$ignoreTestCaseKeyList[] = 'CASB.DailyPeriodMode.PeriodNotMet.OnLevelMaintainEnable.OverMaintainTime.NotMetLevelMaintainCondition.AccumulationYesRegistrationDate';
			$ignoreTestCaseKeyList[] = 'CASB.DailyPeriodMode.PeriodNotMet.OnLevelMaintainEnable.OverMaintainTime.NotMetLevelMaintainCondition.AccumulationYesLastChangePeriod';
			$ignoreTestCaseKeyList[] = 'SASB.DailyPeriodMode.PeriodNotMet.OnLevelMaintainEnable.InMaintainTime.IsMetLevelMaintainCondition.NoAccumulation';
			$ignoreTestCaseKeyList[] = 'SASB.DailyPeriodMode.PeriodNotMet.OnLevelMaintainEnable.InMaintainTime.IsMetLevelMaintainCondition.AccumulationYesRegistrationDate';
			$ignoreTestCaseKeyList[] = 'SASB.DailyPeriodMode.PeriodNotMet.OnLevelMaintainEnable.InMaintainTime.IsMetLevelMaintainCondition.AccumulationYesLastChangePeriod';
			$ignoreTestCaseKeyList[] = 'SASB.DailyPeriodMode.PeriodNotMet.OnLevelMaintainEnable.InMaintainTime.NotMetLevelMaintainCondition.NoAccumulation';
			$ignoreTestCaseKeyList[] = 'SASB.DailyPeriodMode.PeriodNotMet.OnLevelMaintainEnable.InMaintainTime.NotMetLevelMaintainCondition.AccumulationYesRegistrationDate';
			$ignoreTestCaseKeyList[] = 'SASB.DailyPeriodMode.PeriodNotMet.OnLevelMaintainEnable.InMaintainTime.NotMetLevelMaintainCondition.AccumulationYesLastChangePeriod';
			$ignoreTestCaseKeyList[] = 'SASB.DailyPeriodMode.PeriodNotMet.OnLevelMaintainEnable.OverMaintainTime.IsMetLevelMaintainCondition.NoAccumulation';
			$ignoreTestCaseKeyList[] = 'SASB.DailyPeriodMode.PeriodNotMet.OnLevelMaintainEnable.OverMaintainTime.IsMetLevelMaintainCondition.AccumulationYesRegistrationDate';
			$ignoreTestCaseKeyList[] = 'SASB.DailyPeriodMode.PeriodNotMet.OnLevelMaintainEnable.OverMaintainTime.IsMetLevelMaintainCondition.AccumulationYesLastChangePeriod';
			$ignoreTestCaseKeyList[] = 'SASB.DailyPeriodMode.PeriodNotMet.OnLevelMaintainEnable.OverMaintainTime.NotMetLevelMaintainCondition.NoAccumulation';
			$ignoreTestCaseKeyList[] = 'SASB.DailyPeriodMode.PeriodNotMet.OnLevelMaintainEnable.OverMaintainTime.NotMetLevelMaintainCondition.AccumulationYesRegistrationDate';
			$ignoreTestCaseKeyList[] = 'SASB.DailyPeriodMode.PeriodNotMet.OnLevelMaintainEnable.OverMaintainTime.NotMetLevelMaintainCondition.AccumulationYesLastChangePeriod';


		return $ignoreTestCaseKeyList;
	} // EOF _getIngoreCombinedCases

	/**
	 * Get the $isSA and $isSB From $theCombinedCase
	 *
	 * @param string $theCombinedCase
	 * @return array
	 */
	public function _getIsSAIsSBFromCombinedCase($theCombinedCase = 'CACB.EmptyPeriodMode.PeriodNotMet.OnLevelMaintainEnable.InMaintainTime.IsMetLevelMaintainCondition.NoAccumulation'){

		if( $this->_pos($theCombinedCase, 'CACB') ){
			$isSA = 0;
			$isSB = 0;
		}
		if( $this->_pos($theCombinedCase, 'CASB') ){
			$isSA = 0;
			$isSB = 1;
		}

		if( $this->_pos($theCombinedCase, 'SACB') ){
			$isSA = 1;
			$isSB = 0;
		}

		if( $this->_pos($theCombinedCase, 'SASB') ){
			$isSA = 1;
			$isSB = 1;
		}
		return [$isSA, $isSB];
	} // EOF getIsSAIsSBFrom

	public function _getTestConditionFnFromCombinedCase4ogp21818($theCombinedCase = 'CACB.DailyPeriodMode.PeriodIsMet.OnLevelMaintainEnable.InMaintainTime.IsMetLevelMaintainCondition.AccumulationYesRegistrationDate.BetUpgradeSetting'){
		$isOffLevelMaintainEnable = $this->_pos($theCombinedCase, 'OffLevelMaintainEnable');
		$isEmptyUpgradeSetting = $this->_pos($theCombinedCase, 'EmptyUpgradeSetting');

		$isOnLevelMaintainEnable = $this->_pos($theCombinedCase, 'OnLevelMaintainEnable');
		$isOffLevelMaintainEnable = $this->_pos($theCombinedCase, 'OffLevelMaintainEnable');

		$isInMaintainTime = $this->_pos($theCombinedCase, 'InMaintainTime');
		$isOverMaintainTime = $this->_pos($theCombinedCase, 'OverMaintainTime');

		$isNotMetLevelMaintainCondition = $this->_pos($theCombinedCase, 'NotMetLevelMaintainCondition');
		$isIsMetLevelMaintainCondition = $this->_pos($theCombinedCase, 'IsMetLevelMaintainCondition');


		$isIsDowngradeConditionNotMet = $this->_pos($theCombinedCase, 'IsDowngradeConditionNotMet');
		$isIsDowngradeConditionMet = $this->_pos($theCombinedCase, 'IsDowngradeConditionMet');


		$testConditionFn = '_testConditionFn4beforeDiffAfterV2'; // should be downgraded

		if($isOffLevelMaintainEnable && $isEmptyUpgradeSetting){
			$testConditionFn = '_testConditionFn4beforeSameAsAfterV2';
		} else if($isOffLevelMaintainEnable && $isIsDowngradeConditionNotMet){
			$testConditionFn = '_testConditionFn4beforeSameAsAfterV2';
		}

		if($isOnLevelMaintainEnable && $isInMaintainTime){
			// cause by with in Level Maintain Time
			// should without downgraded, keep the current level.
			$testConditionFn = '_testConditionFn4beforeSameAsAfterV2';
		}else if($isOnLevelMaintainEnable && $isOverMaintainTime && $isNotMetLevelMaintainCondition){
			// cause by with Not Met Level Maintain Condition during Over Maintain Time.
			// should without downgraded, keep the current level.
			$testConditionFn = '_testConditionFn4beforeSameAsAfterV2';
		}

		$this->CI->utils->debug_log('1409.testConditionFn:',$testConditionFn);
		return $testConditionFn;
	} // EOF _getTestConditionFnFromCombinedCase4ogp21818


	// @todo
	/**
	 * Undocumented function
	 *
	 * @param string $theCombinedCase
	 * @param string $checkFrom ex: Cronjob, CronjobHourly, SBE
	 * @param boolean $manual_batch The manual Mode, or the batch mode. If false means batch, if true means manual.
	 * @return void
	 */
	public function _getTestConditionFnFromCombinedCase4ogp21051upgrade($theCombinedCase = 'CACB.EmptyPeriodMode.PeriodNotMet.OnLevelMaintainEnab', $checkFrom = 'Cronjob', $manual_batch = false ){ // Cronjob, CronjobHourly, SBE
		// theCommonSeparateModeCaseList: CACB, SACB, CASB, SASB
		// thePeriodModeCaseList: EmptyPeriodMode, DailyPeriodMode, Weekly1PeriodMode, Monthly5PeriodMode
		// theNextLevelUpPeriodModeCaseList: NextLevelUpEmptyPeriodMode, NextLevelUpDailyPeriodMode, NextLevelUpWeekly1PeriodMode, NextLevelUpMonthly5PeriodMode
		// thePeriodIsMetCaseList: PeriodIsMet, PeriodNotMet
		// theNextLevelUpPeriodIsMetCaseList: NextLevelUpPeriodIsMet, NextLevelUpPeriodNotMet
		// theAccumulationCaseList: NoAccumulation, AccumulationYesRegistrationDate, AccumulationYesLastChangePeriod, AccumulationLastChangePeriodResetIfMet
		// theNextLevelUpAccumulationCaseList: NextLevelUpNoAccumulation, NextLevelUpAccumulationYesRegistrationDate, NextLevelUpAccumulationYesLastChangePeriod, NextLevelUpAccumulationLastChangePeriodResetIfMet
		// theHourlyCheckUpgradeEnableCaseList: OnHourlyCheckUpgradeEnable, OffHourlyCheckUpgradeEnable
		// theMultipleUpgradeEnableModeCaseList: OnMultipleUpgradeEnable, OffMultipleUpgradeEnable

		// passable outputs
		$testConditionFn = '_testConditionFn4beforeDiffAfterV2'; // should be downgraded
		// _testConditionFn4beforeSameAsAfterV2
		// _testConditionFn4beforeDiffOneMoreAsAfterV2

		$isPeriodNotMet = $this->_pos($theCombinedCase, '.PeriodNotMet');
		$isPeriodIsMet = $this->_pos($theCombinedCase, '.PeriodIsMet');

		$isEmptyPeriodMode = $this->_pos($theCombinedCase, '.EmptyPeriodMode');
		$isDailyPeriodMode = $this->_pos($theCombinedCase, '.DailyPeriodMode');
		$isWeekly1PeriodMode = $this->_pos($theCombinedCase, '.Weekly1PeriodMode');
		$isMonthly5PeriodMode = $this->_pos($theCombinedCase, '.Monthly5PeriodMode');

		$isNextLevelUpPeriodIsMet = $this->_pos($theCombinedCase, 'NextLevelUpPeriodIsMet');
		$isNextLevelUpPeriodNotMet = $this->_pos($theCombinedCase, 'NextLevelUpPeriodNotMet');

		$isNextLevelUpEmptyPeriodMode = $this->_pos($theCombinedCase, 'NextLevelUpEmptyPeriodMode');
		$isNextLevelUpDailyPeriodMode = $this->_pos($theCombinedCase, 'NextLevelUpDailyPeriodMode');
		$isNextLevelUpWeekly1PeriodMode = $this->_pos($theCombinedCase, 'NextLevelUpWeekly1PeriodMode');
		$isNextLevelUpMonthly5PeriodMode = $this->_pos($theCombinedCase, 'NextLevelUpMonthly5PeriodMode');

		$isNoAccumulation = $this->_pos($theCombinedCase, 'NoAccumulation');
		$isAccumulationYesRegistrationDate = $this->_pos($theCombinedCase, 'AccumulationYesRegistrationDate');
		$isAccumulationYesLastChangePeriod = $this->_pos($theCombinedCase, 'AccumulationYesLastChangePeriod');
		$isAccumulationLastChangePeriodResetIfMet = $this->_pos($theCombinedCase, 'AccumulationLastChangePeriodResetIfMet');

		$isOnHourlyCheckUpgradeEnable = $this->_pos($theCombinedCase, 'OnHourlyCheckUpgradeEnable');
		$isOffHourlyCheckUpgradeEnable = $this->_pos($theCombinedCase, 'OffHourlyCheckUpgradeEnable');

		$isOnMultipleUpgradeEnable = $this->_pos($theCombinedCase, 'OnMultipleUpgradeEnable');
		$isOffMultipleUpgradeEnable = $this->_pos($theCombinedCase, 'OffMultipleUpgradeEnable');
$toDebuglog = null;
$detectCase = null;
// $detectCase = 'CACB.Weekly1PeriodMode.NextLevelUpDailyPeriodMode.PeriodIsMet.NextLevelUpPeriodIsMet.OffHourlyCheckUpgradeEnable.AccumulationYesLastChangePeriod.NextLevelUpAccumulationYesLastChangePeriod.OnMultipleUpgradeEnable';
// $detectCase = 'CACB.DailyPeriodMode.NextLevelUpDailyPeriodMode.PeriodIsMet.NextLevelUpPeriodIsMet.OffHourlyCheckUpgradeEnable.NoAccumulation.NextLevelUpAccumulationYesRegistrationDate.OnMultipleUpgradeEnable';
// $detectCase = 'CACB.Weekly1PeriodMode.NextLevelUpDailyPeriodMode.PeriodNotMet.NextLevelUpPeriodIsMet.OnHourlyCheckUpgradeEnable.NoAccumulation.NextLevelUpNoAccumulation.OnMultipleUpgradeEnable';

// // $detectCase = 'CACB.DailyPeriodMode.NextLevelUpDailyPeriodMode.PeriodIsMet.NextLevelUpPeriodIsMet.OnHourlyCheckUpgradeEnable.AccumulationYesLastChangePeriod.NextLevelUpAccumulationYesLastChangePeriod.OnMultipleUpgradeEnable';

// $detectCase = 'CACB.Weekly1PeriodMode.NextLevelUpDailyPeriodMode.PeriodIsMet.NextLevelUpPeriodIsMet.OffHourlyCheckUpgradeEnable.OnMultipleUpgradeEnable';
// $detectCase = 'CACB.DailyPeriodMode.NextLevelUpDailyPeriodMode.PeriodIsMet.NextLevelUpPeriodIsMet.OffHourlyCheckUpgradeEnable.OnMultipleUpgradeEnable';
// $detectCase = 'CACB.DailyPeriodMode.NextLevelUpWeekly1PeriodMode.PeriodIsMet.NextLevelUpPeriodIsMet.OnHourlyCheckUpgradeEnable.OnMultipleUpgradeEnable';
if( $theCombinedCase == $detectCase){
$toDebuglog = true;
}else{
$toDebuglog = false;
}


		// $checkFrom ex: Cronjob, CronjobHourly, SBE
		// @todo 時時升級，由時時升級背景工作執行
		// // 沒有勾時時升級，由升級背景工作執行
		// if( !empty($manual_batch) ){
		// 	$checkFrom = 'SBE';
		// }
		$isToCheck = null;
		if($isOffHourlyCheckUpgradeEnable){
			if($checkFrom != 'CronjobHourly'){
				$isToCheck = true;
				if($toDebuglog)print_r(['1886.checkFrom.isToCheck = true']);
			}else if($checkFrom == 'SBE'){
				$isToCheck = true;
				if($toDebuglog)print_r(['1889.checkFrom.isToCheck = true']);
			}else{
				$isToCheck = false;
				if($toDebuglog)print_r(['1892.checkFrom.isToCheck = false']);
				if($toDebuglog){
					$this->CI->utils->debug_log('1869.isToCheck:',$isToCheck);
				}
			}
		}
		if($isOnHourlyCheckUpgradeEnable){
			if($checkFrom == 'CronjobHourly'){
				$isToCheck = true;
				if($toDebuglog)print_r(['1898.checkFrom.isToCheck = true']);
			}else if($checkFrom == 'SBE'){
				$isToCheck = true;
				if($toDebuglog)print_r(['1900.checkFrom.isToCheck = true']);
			}else{
				$isToCheck = false;
				if($toDebuglog)print_r(['1901.checkFrom.isToCheck = false']);
				if($toDebuglog){
					$this->CI->utils->debug_log('1879.isToCheck:',$isToCheck);
				}
			}
		}
		// else{
		// 	$isToCheck = false;
		// 	if($toDebuglog){
		// 		$this->CI->utils->debug_log('1885.isToCheck:',$isToCheck);
		// 	}
		// }
		// if( empty($manual_batch) ){
		// 	// $manual_batch==false, manual mode in SBE
		// 	$isToCheck = true;
		// 	if($toDebuglog)print_r(['1903.manual_batch.isToCheck = true']);
		// }



		if($isToCheck){
			// if()
			// _testConditionFn4beforeSameAsAfterV2

			if($checkFrom == 'CronjobHourly' && $isOnHourlyCheckUpgradeEnable){
				// ignore the case, because hourlyInSetting=1 and fromHourlyCronjob == hourlyInSetting
				if($toDebuglog)print_r(['1915.update testConditionFn']);
			}else if( $isPeriodNotMet ){
				$testConditionFn = '_testConditionFn4beforeSameAsAfterV2';
				if($toDebuglog)print_r(['1917.update testConditionFn']);
				if($toDebuglog){

					$this->CI->utils->debug_log('1893.testConditionFn:',$testConditionFn);
				}
			}

			if( $isOnMultipleUpgradeEnable ){
				// maybe return _testConditionFn4beforeDiffOneMoreAsAfterV2
				if( $isPeriodIsMet && $isNextLevelUpPeriodIsMet ){
					$testConditionFn = '_testConditionFn4beforeDiffOneMoreAsAfterV2';
					if($toDebuglog)print_r(['1926.update testConditionFn']);
					if($toDebuglog){
						$this->CI->utils->debug_log('1901.testConditionFn:',$testConditionFn);
					}
				}
			}

		}else{
			$testConditionFn = '_testConditionFn4beforeSameAsAfterV2';
			if($toDebuglog)print_r(['1909.update testConditionFn']);
			if($toDebuglog){
				$this->CI->utils->debug_log('1909.testConditionFn:',$testConditionFn, 'theCombinedCase:', $theCombinedCase);
			}
		}

		// $isWeekly1PeriodMode
		// $isMonthly5PeriodMode
		// $isNextLevelUpWeekly1PeriodMode
		// $isNextLevelUpMonthly5PeriodMode
		// To filter the PeriodMode Not Equire to NextLevelUpPeriodMode, change to _testConditionFn4beforeDiffAfterV2.
		if($testConditionFn == '_testConditionFn4beforeDiffOneMoreAsAfterV2'){
			if($isPeriodNotMet || $isNextLevelUpPeriodNotMet){
				$testConditionFn = '_testConditionFn4beforeDiffAfterV2';
			}

			// if( ($isWeekly1PeriodMode && ! $isNextLevelUpWeekly1PeriodMode)
			// 	|| ( ! $isWeekly1PeriodMode && $isNextLevelUpWeekly1PeriodMode )
			// ){
			// 	$testConditionFn = '_testConditionFn4beforeDiffAfterV2';
			// 	if($toDebuglog)print_r(['1951.update testConditionFn']);
			// }
			// if($isMonthly5PeriodMode != $isNextLevelUpMonthly5PeriodMode){
			// 	$testConditionFn = '_testConditionFn4beforeDiffAfterV2';
			// 	if($toDebuglog)print_r(['1955.update testConditionFn']);
			// }
		}

		if($toDebuglog){
			$this->CI->utils->debug_log('1905.testConditionFn:',$testConditionFn, 'checkFrom:', $checkFrom, 'isToCheck:', $isToCheck, 'isOnHourlyCheckUpgradeEnable', $isOnHourlyCheckUpgradeEnable, 'theCombinedCase:', $theCombinedCase);
		}
		if($toDebuglog)print_r(['1905.testConditionFn:',$testConditionFn, 'checkFrom:', $checkFrom, 'isToCheck:', $isToCheck, 'isOnHourlyCheckUpgradeEnable', $isOnHourlyCheckUpgradeEnable, 'theCombinedCase:', $theCombinedCase]);

		return $testConditionFn;
	} // EOF _getTestConditionFnFromCombinedCase4ogp21051upgrade


	/**
	 * Get the TestConditionFn by theCombinedCase
	 *
	 * @param string $theCombinedCase
	 * @return string $testConditionFn
	 */
	public function _getTestConditionFnFromCombinedCase($theCombinedCase = 'CACB.EmptyPeriodMode.PeriodNotMet.OnLevelMaintainEnable.InMaintainTime.IsMetLevelMaintainCondition.NoAccumulation'){
		$isPeriodNotMet = $this->_pos($theCombinedCase, 'PeriodNotMet');

		$isEmptyPeriodMode = $this->_pos($theCombinedCase, 'EmptyPeriodMode');

		$isOnLevelMaintainEnable = $this->_pos($theCombinedCase, 'OnLevelMaintainEnable');
		$isOffLevelMaintainEnable = $this->_pos($theCombinedCase, 'OffLevelMaintainEnable');

		$isInMaintainTime = $this->_pos($theCombinedCase, 'InMaintainTime');
		$isOverMaintainTime = $this->_pos($theCombinedCase, 'OverMaintainTime');

		$isNotMetLevelMaintainCondition = $this->_pos($theCombinedCase, 'NotMetLevelMaintainCondition');
		$isIsMetLevelMaintainCondition = $this->_pos($theCombinedCase, 'IsMetLevelMaintainCondition');

		$testConditionFn = '_testConditionFn4beforeDiffAfterV2'; // should be downgraded

		if( $isEmptyPeriodMode && $isOffLevelMaintainEnable){
			// should without downgraded, keep the current level.
			$testConditionFn = '_testConditionFn4beforeSameAsAfterV2';
		}
		if( $isPeriodNotMet && $isOffLevelMaintainEnable ){
			// should without downgraded, keep the current level.
			$testConditionFn = '_testConditionFn4beforeSameAsAfterV2';
		}
		if($isOnLevelMaintainEnable && $isInMaintainTime){
			// cause by with in Level Maintain Time
			// should without downgraded, keep the current level.
			$testConditionFn = '_testConditionFn4beforeSameAsAfterV2';
		}else if($isOnLevelMaintainEnable && $isOverMaintainTime && $isNotMetLevelMaintainCondition){
			// cause by with Not Met Level Maintain Condition during Over Maintain Time.
			// should without downgraded, keep the current level.
			$testConditionFn = '_testConditionFn4beforeSameAsAfterV2';
		}

		return $testConditionFn;

	} // EOF _getTestConditionFnFromCombinedCase

	/**
	 * for multi-upgrade test
	 *
	 * @param integer $origId The original Id ( or Value).
	 * @param integer $afterId The Id ( or Value) after test action.
	 * @param boolean $continueTest If false, test() will be stopped after unexpected results. else keep going next test().
	 * @param string $noteTpl
	 * @return array list($result, $note)
	 * @return void
	 */
	public function _testConditionFn4beforeDiffOneMoreAsAfterV2($origId, $afterId, $continueTest = false, $noteTpl = ''){
		// return true for pass, false for NG.
		$_compareFn = function( $origId, $afterId, &$resultMessage = null){
			$resultBool = null;
			$this->CI->load->model(['group_level']);
			$origVipsettingcashbackruleId = $origId;
			$origCashbackRule = $this->CI->group_level->getCashbackRule($origVipsettingcashbackruleId);
			$afterVipsettingcashbackruleId = $afterId;
			$afterCashbackRule = $this->CI->group_level->getCashbackRule($afterVipsettingcashbackruleId);
			if( ! empty($origCashbackRule) && ! empty($afterCashbackRule) ){
				if($origCashbackRule->vipSettingId == $afterCashbackRule->vipSettingId){
					if( abs($origCashbackRule->vipLevel - $afterCashbackRule->vipLevel) > 1 ){
						$resultBool = true;
						$resultMessage = 'Expected';
					}else{
						// Not Diff One More
						$resultBool = false;
						$resultMessage = 'VIP level is not more than 1 or more between origId and afterId .';
					}
				}else{
					$resultBool = false;
					$resultMessage = 'The VIP Group of origId and the VIP Group of afterId are Not Same as one.';
				}
			}else{
				if(empty($origCashbackRule)){
					$resultBool = false;
					$resultMessage = 'The origId data of vipsettingcashbackrule is Empty.';
				}
				if(empty($afterCashbackRule)){
					$resultBool = false;
					$resultMessage = 'The afterId data of vipsettingcashbackrule is Empty.';
				}
			}
			return $resultBool;
		}; // EOF $_compareFn = function( $origId, $afterId, &$resultMessage){

		$resultMessage = null; // for debug_log()
		$resultBool = $_compareFn($origId, $afterId, $resultMessage);
		if($resultBool){
			$hasExpectedString = 'has expected';
		}else{
			$hasExpectedString = 'has unexpected';
		}
		$this->CI->utils->debug_log('1869.resultBool:', $resultBool, 'resultMessage:', $resultMessage);
		$noteTpl=<<<EOF
<pre>
The Case $hasExpectedString, %s BEGIN,
The params:
%s
The rlt :
%s
</pre>
EOF;

		return $this->_testConditionFnV2(function() use ($origId, $afterId, $_compareFn) {
			return $resultBool = $_compareFn($origId, $afterId);
		}, $continueTest, $noteTpl);
	}// EOF _testConditionFn4beforeDiffOneMoreAsAfterV2
	public function _testConditionFn4beforeSameAsAfterV2($origId, $afterId, $continueTest = false, $noteTpl = ''){
		// return true for pass, false for NG.
		$_compareFn = function( $origId, $afterId ){
			return $origId == $afterId;
		};
		if( $_compareFn ){
			$hasExpectedString = 'has expected';
		}else{
			$hasExpectedString = 'has unexpected';
		}
		$noteTpl=<<<EOF
<pre>
The Case $hasExpectedString, %s BEGIN,
The params:
%s
The rlt :
%s
</pre>
EOF;

		return $this->_testConditionFnV2(function() use ($origId, $afterId, $_compareFn) {
			return $_compareFn($origId, $afterId);
		}, $continueTest, $noteTpl);
	}// EOF _testConditionFn4beforeSameAsAfterV2
	public function _testConditionFn4beforeDiffAfterV2($origId, $afterId, $continueTest = false, $noteTpl = ''){
		// return true for pass, false for NG.
		$_compareFn = function( $origId, $afterId ){
			return $origId != $afterId;
		};
		if( $_compareFn ){
			$hasExpectedString = 'has expected';
		}else{
			$hasExpectedString = 'has unexpected';
		}
		$noteTpl=<<<EOF
<pre>
The Case $hasExpectedString, %s BEGIN,
The params:
%s
The rlt :
%s
</pre>
EOF;

		return $this->_testConditionFnV2(function() use ($origId, $afterId, $_compareFn) {
			return $_compareFn($origId, $afterId);
		}, $continueTest, $noteTpl);
	}// EOF _testConditionFn4beforeDiffAfterV2

	/**
	 * for test() Detects the $origId and $afterId should be the difference.
	 *
	 * @param integer $origId The original Id ( or Value).
	 * @param integer $afterId The Id ( or Value) after test action.
	 * @param boolean $continueTest If false, test() will be stopped after unexpected results. else keep going next test().
	 * @param string $note
	 * @return array list($result, $note)
	 */
	public function _testConditionFnV2($compareFn, $continueTest = false, $noteTpl = ''){
		if( empty($noteTpl) ){
			$noteTpl = $this->noteTpl;
		}
		$result = $compareFn();
		if( $continueTest ){
			$result = true;
		}
		return [$result, $noteTpl];
	} // EOF _testConditionFnV2

	/**
	 * To Get $settingName, $getUpgradeLevelSettingFn for test case setups
	 *
	 * @param string $theCombinedCase
	 * @param array $theTestPlayerInfo The return array of utils4testogp::_searchTestPlayerByPlayerId().
	 * @return array [$settingName, $getUpgradeLevelSettingFn]
	 */
	public function _getUpgradeLevelSettingFnAndSettingNameFromCombinedCaseAndTestPlayerInfo($theCombinedCase, $theTestPlayerInfo){
		// @todo IsDowngradeConditionMet IsDowngradeConditionNotMet
		// return $this->_getUpgradeLevelSettingFnAndSettingNameFromCombinedCase($theCombinedCase, $theTestPlayerInfo);
		// $theTestPlayerInfo[game_description_id]
		// will return [$settingName, $getUpgradeLevelSettingFn]

		$isCACB = $this->_pos($theCombinedCase, 'CACB');
		$isSACB = $this->_pos($theCombinedCase, 'SACB');
		$isCASB = $this->_pos($theCombinedCase, 'CASB');
		$isSASB = $this->_pos($theCombinedCase, 'SASB');

		$isIsDowngradeConditionMet = $this->_pos($theCombinedCase, 'IsDowngradeConditionMet');
		$isIsDowngradeConditionNotMet = $this->_pos($theCombinedCase, 'IsDowngradeConditionNotMet');

		$isNoAccumulation = $this->_pos($theCombinedCase, 'NoAccumulation');
		$isAccumulationYesRegistrationDate = $this->_pos($theCombinedCase, 'AccumulationYesRegistrationDate');
		$isAccumulationYesLastChangePeriod = $this->_pos($theCombinedCase, 'AccumulationYesLastChangePeriod');
		$isAccumulationLastChangePeriodResetIfMet = $this->_pos($theCombinedCase, 'AccumulationLastChangePeriodResetIfMet');

		/// for $getUpgradeLevelSettingFn
		$accumulation = 0; // default
		if($isNoAccumulation){
			$accumulation = 0; // Group_level::ACCUMULATION_MODE_DISABLE
		}else if($isAccumulationYesRegistrationDate){
			$accumulation = 1; // Group_level::ACCUMULATION_MODE_FROM_REGISTRATION
		}else if($isAccumulationYesLastChangePeriod){
			$accumulation = 4; // Group_level::ACCUMULATION_MODE_LAST_CHANGED_GEADE
		}else if($isAccumulationLastChangePeriodResetIfMet){
			$accumulation = 5; // Group_level::ACCUMULATION_MODE_LAST_CHANGED_GEADE_RESET_IF_MET
		}
		/// handle DowngradeLevelSetting
		$_this = $this;

		if( $isCACB ){
			$settingName = 'devDowngradeMet.CACB';
			$forGrade = 'downgrade';
			// $params = [$settingName, $forGrade];
			// ref. to _getDowngradeLevelSettingFn > _syncUpgradeLevelSettingByName
			// related _tryUpgradeSuccessTriggerFromCronjobV2
			$data = [];
			$data['setting_name'] = $settingName;
			$data['description'] = $settingName. '.testing';
			// $data['level_upgrade'] = 3; // for downgrade
			/// CB
			if( $isIsDowngradeConditionMet ){
				// betAmount >= 0 ... Is Met
				$betAmountMathSign = '>=';
				$betAmountValue = 0;
				$operatorBeforeDeposit = 'and';
				// depositAmount >= 0
				$depositAmountMathSign = '>=';
				$depositAmountValue = 0;
				$operatorBeforeLoss = 'and';
				// lossAmount >= 0
				$lossAmountMathSign = '>=';
				$lossAmountValue = 0;
				$operatorBeforeWin = null;
				// ignore winAmount
				$winAmountMathSign = null;
				$winAmountValue = null;
			}else if($isIsDowngradeConditionNotMet){
				// betAmount < 0 ... Not MET
				$betAmountMathSign = '<';
				$betAmountValue = 0;
				$operatorBeforeDeposit = 'and';
				// depositAmount < 0
				$depositAmountMathSign = '<';
				$depositAmountValue = 0;
				$operatorBeforeLoss = 'and';
				// lossAmount < 0
				$lossAmountMathSign = '<';
				$lossAmountValue = 0;
				$operatorBeforeWin = null;
				// ignore winAmount
				$winAmountMathSign = null;
				$winAmountValue = null;
			}

			$formula = $this->_prepareFormulaOfVipUpgradeSetting($betAmountMathSign // #1
				, $betAmountValue // #2
				, $operatorBeforeDeposit // #3
				, $depositAmountMathSign // #4
				, $depositAmountValue // #5
				, $operatorBeforeLoss // #6
				, $lossAmountMathSign // #7
				, $lossAmountValue // #8
				, $operatorBeforeWin // #9
				, $winAmountMathSign // #10
				, $winAmountValue ); // #11
			$data['formula'] = $formula;
			$data['bet_amount_settings'] = NULL; // always be NULL
			/// CA
			$data['accumulation'] = $accumulation; // 0 / 1 / 4 : No / Yes, Registration Date / Yes, Last Change Period
			$data['separate_accumulation_settings'] = NULL; // always be NULL
			//
			$params = [$settingName, $forGrade, $data];
			$getUpgradeLevelSettingFn = function($_this) use ($params){
				// '_getDowngradeLevelSettingFn'; // so far, CACB
				// $params = [$settingName, $data];
				// $theGenerateCallTrace = $this->utils->generateCallTrace();
				// echo '<pre>';print_r($theGenerateCallTrace);exit();
				return call_user_func_array([$_this, '_getDowngradeLevelSettingFnV2'], $params); // $rlt = $this->_syncUpgradeLevelSettingByName($settingName, $data);
			};

		}else if( $isSACB ){
			$settingName = 'devDowngradeMet.SACB';
			$forGrade = 'downgrade';
			// $params = [$settingName, $forGrade];
			// ref. to _getDowngradeLevelSettingFn > _syncUpgradeLevelSettingByName
			// related _tryUpgradeSuccessTriggerFromCronjobV2
			$data = [];
			$data['setting_name'] = $settingName;
			$data['description'] = $settingName. '.testing';
			// $data['level_upgrade'] = 3; // for downgrade
			/// CB
			if( $isIsDowngradeConditionMet ){
				// betAmount >= 0 ... Is Met
				$betAmountMathSign = '>=';
				$betAmountValue = 0;
				$operatorBeforeDeposit = 'and';
				// depositAmount >= 0
				$depositAmountMathSign = '>=';
				$depositAmountValue = 0;
				$operatorBeforeLoss = 'and';
				// lossAmount >= 0
				$lossAmountMathSign = '>=';
				$lossAmountValue = 0;
				$operatorBeforeWin = null;
				// ignore winAmount
				$winAmountMathSign = null;
				$winAmountValue = null;
			}else if($isIsDowngradeConditionNotMet){
				// betAmount < 0 ... Not MET
				$betAmountMathSign = '<';
				$betAmountValue = 0;
				$operatorBeforeDeposit = 'and';
				// depositAmount < 0
				$depositAmountMathSign = '<';
				$depositAmountValue = 0;
				$operatorBeforeLoss = 'and';
				// lossAmount < 0
				$lossAmountMathSign = '<';
				$lossAmountValue = 0;
				$operatorBeforeWin = null;
				// ignore winAmount
				$winAmountMathSign = null;
				$winAmountValue = null;
			}

			$formula = $this->_prepareFormulaOfVipUpgradeSetting($betAmountMathSign
				, $betAmountValue
				, $operatorBeforeDeposit
				, $depositAmountMathSign
				, $depositAmountValue
				, $operatorBeforeLoss
				, $lossAmountMathSign
				, $lossAmountValue
				, $operatorBeforeWin
				, $winAmountMathSign
				, $winAmountValue );
			$data['formula'] = $formula;
			$data['bet_amount_settings'] = NULL; // always be NULL

			/// SA
			$data['accumulation'] = 0; // 0 / 1 / 4 : No / Yes, Registration Date / Yes, Last Change Period
			$separate_accumulation_settings_format = '{"bet_amount": {"accumulation": "%d"}, "win_amount": {"accumulation": "%d"}, "loss_amount": {"accumulation": "%d"}, "deposit_amount": {"accumulation": "%d"}}';  // 4 params
			$data['separate_accumulation_settings'] = sprintf($separate_accumulation_settings_format, $accumulation, $accumulation, $accumulation, $accumulation); // '{"bet_amount": {"accumulation": "1"}, "win_amount": {"accumulation": "4"}, "loss_amount": {"accumulation": "0"}, "deposit_amount": {"accumulation": "1"}}';

			$params = [$settingName, $forGrade, $data];
			$getUpgradeLevelSettingFn = function($_this) use ($params){
				return call_user_func_array([$_this, '_getDowngradeLevelSettingFnV2'], $params); // $rlt = $this->_syncUpgradeLevelSettingByName($settingName, $data);
			};
		}else if( $isCASB ){
			$settingName = 'devDowngradeMet.CASB';
			$forGrade = 'downgrade';
			// $params = [$settingName, $forGrade];
			// ref. to _getDowngradeLevelSettingFn > _syncUpgradeLevelSettingByName
			// related _tryUpgradeSuccessTriggerFromCronjobV2
			$data = [];
			$data['setting_name'] = $settingName;
			$data['description'] = $settingName. '.testing';
			// $data['level_upgrade'] = 3; // for downgrade

			/// SB
			$betAmountMathSign = '>=';
			$betAmountValue = 0;
			$operatorBeforeDeposit = 'and';
			$depositAmountMathSign = '>=';
			$depositAmountValue = 0;
			$operatorBeforeLoss = 'and';
			$lossAmountMathSign = '>=';
			$lossAmountValue = 0;
			$operatorBeforeWin = null;
			$winAmountMathSign = null;
			$winAmountValue = null;
			$formula = $this->_prepareFormulaOfVipUpgradeSetting($betAmountMathSign
				, $betAmountValue
				, $operatorBeforeDeposit
				, $depositAmountMathSign
				, $depositAmountValue
				, $operatorBeforeLoss
				, $lossAmountMathSign
				, $lossAmountValue
				, $operatorBeforeWin
				, $winAmountMathSign
				, $winAmountValue );
			$data['formula'] = $formula;

			if( $isIsDowngradeConditionMet ){
				// betAmount >= 0 ... Is Met
				// game_platform_and_type
				$params = [];
				$params['defaultValue'] = 1;
				$params['defaultMathSign'] = '>=';
				$gameKeyInfoList= [];
				$gameKeyInfoList['type'] = 'game_platform';
				$gameKeyInfoList['value'] = 0;
				$gameKeyInfoList['math_sign'] = '>=';
				$gameKeyInfoList['game_platform_id'] = ''. $theTestPlayerInfo['game_platform_id'];// MUST BE STRING
				$params['GKAMSAVL'][] = $gameKeyInfoList;
				$gameKeyInfoList= [];
				$gameKeyInfoList['type'] = 'game_type';
				$gameKeyInfoList['value'] = 0;
				$gameKeyInfoList['math_sign'] = '>=';
				$gameKeyInfoList['game_type_id'] = ''. $theTestPlayerInfo['game_type_id']; // MUST BE STRING
				$gameKeyInfoList['precon_logic_flag'] = 'and';
				$params['GKAMSAVL'][] = $gameKeyInfoList;
			}else if($isIsDowngradeConditionNotMet){
				// betAmount < 0 ... Not MET
				// game_platform_and_type
				$params = [];
				$params['defaultValue'] = 1;
				$params['defaultMathSign'] = '<';
				$gameKeyInfoList= [];
				$gameKeyInfoList['type'] = 'game_platform';
				$gameKeyInfoList['value'] = 0;
				$gameKeyInfoList['math_sign'] = '<';
				$gameKeyInfoList['game_platform_id'] = ''. $theTestPlayerInfo['game_platform_id'];// MUST BE STRING
				$params['GKAMSAVL'][] = $gameKeyInfoList;
				$gameKeyInfoList= [];
				$gameKeyInfoList['type'] = 'game_type';
				$gameKeyInfoList['value'] = 0;
				$gameKeyInfoList['math_sign'] = '<';
				$gameKeyInfoList['game_type_id'] = ''. $theTestPlayerInfo['game_type_id']; // MUST BE STRING
				$gameKeyInfoList['precon_logic_flag'] = 'and';
				$params['GKAMSAVL'][] = $gameKeyInfoList;
			}

			$defaultValue = $params['defaultValue'];
			$defaultMathSign = $params['defaultMathSign'];
			$gameKeysAndMathSignAndValueList = $params['GKAMSAVL'];
			$theBetAmountSettings = $this->_prepareBetAmountSettingsOfVipUpgradeSetting($defaultValue, $defaultMathSign, $gameKeysAndMathSignAndValueList);
			// $rlt = call_user_func_array([$this, '_prepareBetAmountSettingsOfVipUpgradeSetting'], $testInfo['params']);
			$data['bet_amount_settings'] = $theBetAmountSettings; // always be NULL

			/// CA
			$data['accumulation'] = $accumulation; // 0 / 1 / 4 : No / Yes, Registration Date / Yes, Last Change Period
			$data['separate_accumulation_settings'] = NULL; // always be NULL
			//
			$params = [$settingName, $forGrade, $data];
			$getUpgradeLevelSettingFn = function($_this) use ($params){
				// '_getDowngradeLevelSettingFn'; // so far, CACB
				// $params = [$settingName, $data];
				// $theGenerateCallTrace = $this->utils->generateCallTrace();
				// echo '<pre>';print_r($theGenerateCallTrace);exit();
				return call_user_func_array([$_this, '_getDowngradeLevelSettingFnV2'], $params); // $rlt = $this->_syncUpgradeLevelSettingByName($settingName, $data);
			};
		}else if( $isSASB ){
			$settingName = 'devDowngradeMet.SASB';
			$forGrade = 'downgrade';
			// $params = [$settingName, $forGrade];
			// ref. to _getDowngradeLevelSettingFn > _syncUpgradeLevelSettingByName
			// related _tryUpgradeSuccessTriggerFromCronjobV2
			$data = [];
			$data['setting_name'] = $settingName;
			$data['description'] = $settingName. '.testing';
			// $data['level_upgrade'] = 3; // for downgrade

			/// SB
			$betAmountMathSign = '>=';
			$betAmountValue = 0;
			$operatorBeforeDeposit = 'and';
			$depositAmountMathSign = '>=';
			$depositAmountValue = 0;
			$operatorBeforeLoss = 'and';
			$lossAmountMathSign = '>=';
			$lossAmountValue = 0;
			$operatorBeforeWin = null;
			$winAmountMathSign = null;
			$winAmountValue = null;
			$formula = $this->_prepareFormulaOfVipUpgradeSetting($betAmountMathSign
				, $betAmountValue
				, $operatorBeforeDeposit
				, $depositAmountMathSign
				, $depositAmountValue
				, $operatorBeforeLoss
				, $lossAmountMathSign
				, $lossAmountValue
				, $operatorBeforeWin
				, $winAmountMathSign
				, $winAmountValue );
			$data['formula'] = $formula;

			if( $isIsDowngradeConditionMet ){
				// betAmount >= 0 ... Is Met
				// game_platform_and_type
				$params = [];
				$params['defaultValue'] = 1;
				$params['defaultMathSign'] = '>=';
				$gameKeyInfoList= [];
				$gameKeyInfoList['type'] = 'game_platform';
				$gameKeyInfoList['value'] = 0;
				$gameKeyInfoList['math_sign'] = '>=';
				$gameKeyInfoList['game_platform_id'] = ''. $theTestPlayerInfo['game_platform_id'];// MUST BE STRING
				$params['GKAMSAVL'][] = $gameKeyInfoList;
				$gameKeyInfoList= [];
				$gameKeyInfoList['type'] = 'game_type';
				$gameKeyInfoList['value'] = 0;
				$gameKeyInfoList['math_sign'] = '>=';
				$gameKeyInfoList['game_type_id'] = ''. $theTestPlayerInfo['game_type_id']; // MUST BE STRING
				$gameKeyInfoList['precon_logic_flag'] = 'and';
				$params['GKAMSAVL'][] = $gameKeyInfoList;
			}else if($isIsDowngradeConditionNotMet){
				// betAmount < 0 ... Not MET
				// game_platform_and_type
				$params = [];
				$params['defaultValue'] = 1;
				$params['defaultMathSign'] = '<';
				$gameKeyInfoList= [];
				$gameKeyInfoList['type'] = 'game_platform';
				$gameKeyInfoList['value'] = 0;
				$gameKeyInfoList['math_sign'] = '<';
				$gameKeyInfoList['game_platform_id'] = ''. $theTestPlayerInfo['game_platform_id'];// MUST BE STRING
				$params['GKAMSAVL'][] = $gameKeyInfoList;
				$gameKeyInfoList= [];
				$gameKeyInfoList['type'] = 'game_type';
				$gameKeyInfoList['value'] = 0;
				$gameKeyInfoList['math_sign'] = '<';
				$gameKeyInfoList['game_type_id'] = ''. $theTestPlayerInfo['game_type_id']; // MUST BE STRING
				$gameKeyInfoList['precon_logic_flag'] = 'and';
				$params['GKAMSAVL'][] = $gameKeyInfoList;
			}

			$defaultValue = $params['defaultValue'];
			$defaultMathSign = $params['defaultMathSign'];
			$gameKeysAndMathSignAndValueList = $params['GKAMSAVL'];
			$theBetAmountSettings = $this->_prepareBetAmountSettingsOfVipUpgradeSetting($defaultValue, $defaultMathSign, $gameKeysAndMathSignAndValueList);
			// $rlt = call_user_func_array([$this, '_prepareBetAmountSettingsOfVipUpgradeSetting'], $testInfo['params']);
			$data['bet_amount_settings'] = $theBetAmountSettings; // always be NULL

			/// SA
			$data['accumulation'] = 0; // 0 / 1 / 4 : No / Yes, Registration Date / Yes, Last Change Period
			$separate_accumulation_settings_format = '{"bet_amount": {"accumulation": "%d"}, "win_amount": {"accumulation": "%d"}, "loss_amount": {"accumulation": "%d"}, "deposit_amount": {"accumulation": "%d"}}';  // 4 params
			$data['separate_accumulation_settings'] = sprintf($separate_accumulation_settings_format, $accumulation, $accumulation, $accumulation, $accumulation); // '{"bet_amount": {"accumulation": "1"}, "win_amount": {"accumulation": "4"}, "loss_amount": {"accumulation": "0"}, "deposit_amount": {"accumulation": "1"}}';

			$params = [$settingName, $forGrade, $data];
			$getUpgradeLevelSettingFn = function($_this) use ($params){
				// '_getDowngradeLevelSettingFn'; // so far, CACB
				// $params = [$settingName, $data];
				// $theGenerateCallTrace = $this->utils->generateCallTrace();
				// echo '<pre>';print_r($theGenerateCallTrace);exit();
				return call_user_func_array([$_this, '_getDowngradeLevelSettingFnV2'], $params); // $rlt = $this->_syncUpgradeLevelSettingByName($settingName, $data);
			};
		}

		return [$settingName, $getUpgradeLevelSettingFn];
	} // EOF _getUpgradeLevelSettingFnAndSettingNameFromCombinedCaseAndTestPlayerInfo

	// PFOVUS = _prepareFormulaOfVipUpgradeSetting
	public function _getParams4PFOVUS($theCombinedCase = [], $theTestPlayerInfo = []){

		$isEmptyUpgradeSetting = $this->_pos($theCombinedCase, 'EmptyUpgradeSetting');
		$isDepositUpgradeSetting = $this->_pos($theCombinedCase, 'DepositUpgradeSetting');
		$isBetUpgradeSetting = $this->_pos($theCombinedCase, 'BetUpgradeSetting');
		$isWinUpgradeSetting = $this->_pos($theCombinedCase, 'WinUpgradeSetting');
		$isLossUpgradeSetting = $this->_pos($theCombinedCase, 'LossUpgradeSetting');
		$isDepositBetUpgradeSetting = $this->_pos($theCombinedCase, 'DepositBetUpgradeSetting');

		$isIsDowngradeConditionMet = $this->_pos($theCombinedCase, 'IsDowngradeConditionMet');
		$isIsDowngradeConditionNotMet = $this->_pos($theCombinedCase, 'IsDowngradeConditionNotMet');

		$params = [];

		// for default
		$betAmountMathSign = '>=';
		$betAmountValue = 0;
		$operatorBeforeDeposit = 'and';
		$depositAmountMathSign = '>=';
		$depositAmountValue = 0;
		$operatorBeforeLoss = 'and';
		$lossAmountMathSign = '>=';
		$lossAmountValue = 0;
		$operatorBeforeWin = null;
		$winAmountMathSign = null;
		$winAmountValue = null;
		if( $isIsDowngradeConditionMet ){
			$betAmountMathSign = '>=';
			$depositAmountMathSign = '>=';
			$lossAmountMathSign = '>=';
		}else if($isIsDowngradeConditionNotMet){
			$betAmountMathSign = '<';
			$depositAmountMathSign = '<';
			$lossAmountMathSign = '<';
		}

		// override
		if($isEmptyUpgradeSetting && false){ //  ignore for "$upgrade_id = 0;" in admin/application/controllers/cli/testing_ogp21818.php
			$betAmountMathSign = null;
			$betAmountValue = null;
			$operatorBeforeDeposit = null;
			$depositAmountMathSign = null;
			$depositAmountValue = null;
			$operatorBeforeLoss = null;
			$lossAmountMathSign = null;
			$lossAmountValue = null;
			$operatorBeforeWin = null;
			$winAmountMathSign = null;
			$winAmountValue = null;
		} else if( $isDepositUpgradeSetting ){
			$betAmountMathSign = null;
			$betAmountValue = null;
			$operatorBeforeDeposit = null;
			$depositAmountMathSign = '>=';
			$depositAmountValue = 0;
			$operatorBeforeLoss = null;
			$lossAmountMathSign = null;
			$lossAmountValue = null;
			$operatorBeforeWin = null;
			$winAmountMathSign = null;
			$winAmountValue = null;
			if($isIsDowngradeConditionNotMet){
				$depositAmountMathSign = '<';
			}
		} else if( $isBetUpgradeSetting ){
			$betAmountMathSign = '>=';
			$betAmountValue = 0;
			$operatorBeforeDeposit = null;
			$depositAmountMathSign = null;
			$depositAmountValue = null;
			$operatorBeforeLoss = null;
			$lossAmountMathSign = null;
			$lossAmountValue = null;
			$operatorBeforeWin = null;
			$winAmountMathSign = null;
			$winAmountValue = null;
			if($isIsDowngradeConditionNotMet){
				$betAmountMathSign = '<';
			}
		} else if( $isWinUpgradeSetting ){
			$betAmountMathSign = null;
			$betAmountValue = null;
			$operatorBeforeDeposit = null;
			$depositAmountMathSign = null;
			$depositAmountValue = null;
			$operatorBeforeLoss = null;
			$lossAmountMathSign = null;
			$lossAmountValue = null;
			$operatorBeforeWin = null;
			$winAmountMathSign = '>=';
			$winAmountValue = 0;
			if($isIsDowngradeConditionNotMet){
				$winAmountMathSign = '<';
			}
		} else if( $isLossUpgradeSetting ){
			$betAmountMathSign = null;
			$betAmountValue = null;
			$operatorBeforeDeposit = null;
			$depositAmountMathSign = null;
			$depositAmountValue = null;
			$operatorBeforeLoss = null;
			$lossAmountMathSign = '>=';
			$lossAmountValue = 0;
			$operatorBeforeWin = null;
			$winAmountMathSign = null;
			$winAmountValue = null;
			if($isIsDowngradeConditionNotMet){
				$lossAmountMathSign = '<';
			}
		} else if( $isDepositBetUpgradeSetting ){
			$betAmountMathSign = '>=';
			$betAmountValue = 0;
			$operatorBeforeDeposit = 'and';
			$depositAmountMathSign = '>=';
			$depositAmountValue = 0;
			$operatorBeforeLoss = null;
			$lossAmountMathSign = null;
			$lossAmountValue = null;
			$operatorBeforeWin = null;
			$winAmountMathSign = null;
			$winAmountValue = null;
			if($isIsDowngradeConditionNotMet){
				$betAmountMathSign = '<';
				$depositAmountMathSign = '<';
			}
		}

		$params = [ $betAmountMathSign // #1
						, $betAmountValue // #2
						, $operatorBeforeDeposit // #3
						, $depositAmountMathSign // #4
						, $depositAmountValue // #5
						, $operatorBeforeLoss // #6
						, $lossAmountMathSign // #7
						, $lossAmountValue // #8
						, $operatorBeforeWin // #9
						, $winAmountMathSign // #10
						, $winAmountValue // #11
					];
		return $params;
	} // EOF _getParams4PFOVUS

	/**
	 * To Get $settingName, $getUpgradeLevelSettingFn for test case setups
	 * It had used by testing_ogp21799, testing_ogp21818, testing_ogp22219
	 * @param string $theCombinedCase
	 * @return array [$settingName, $getUpgradeLevelSettingFn]
	 */
	public function _getUpgradeLevelSettingFnAndSettingNameFromCombinedCase($theCombinedCase = [], $theTestPlayerInfo = [], $forGrade = 'downgrade'){

		// will return [$settingName, $getUpgradeLevelSettingFn]

		$isCACB = $this->_pos($theCombinedCase, 'CACB');
		$isSACB = $this->_pos($theCombinedCase, 'SACB');
		$isCASB = $this->_pos($theCombinedCase, 'CASB');
		$isSASB = $this->_pos($theCombinedCase, 'SASB');

		$isNoAccumulation = $this->_pos($theCombinedCase, 'NoAccumulation');
		$isAccumulationYesRegistrationDate = $this->_pos($theCombinedCase, 'AccumulationYesRegistrationDate');
		$isAccumulationYesLastChangePeriod = $this->_pos($theCombinedCase, 'AccumulationYesLastChangePeriod');
		$isAccumulationLastChangePeriodResetIfMet = $this->_pos($theCombinedCase, 'AccumulationLastChangePeriodResetIfMet');

		$isEmptyUpgradeSetting = $this->_pos($theCombinedCase, 'EmptyUpgradeSetting');
		$isDepositUpgradeSetting = $this->_pos($theCombinedCase, 'DepositUpgradeSetting');
		$isBetUpgradeSetting = $this->_pos($theCombinedCase, 'BetUpgradeSetting');
		$isWinUpgradeSetting = $this->_pos($theCombinedCase, 'WinUpgradeSetting');
		$isLossUpgradeSetting = $this->_pos($theCombinedCase, 'LossUpgradeSetting');
		$isDepositBetUpgradeSetting = $this->_pos($theCombinedCase, 'DepositBetUpgradeSetting');

		$isIsDowngradeConditionMet = $this->_pos($theCombinedCase, 'IsDowngradeConditionMet');
		$isIsDowngradeConditionNotMet = $this->_pos($theCombinedCase, 'IsDowngradeConditionNotMet');

		// $isEmptyUpgradeSetting = $this->_pos($theCombinedCase, 'EmptyUpgradeSetting');
		// $isDepositUpgradeSetting = $this->_pos($theCombinedCase, 'DepositUpgradeSetting');
		// $isBetUpgradeSetting = $this->_pos($theCombinedCase, 'BetUpgradeSetting');
		// $isWinUpgradeSetting = $this->_pos($theCombinedCase, 'WinUpgradeSetting');
		// $isLossUpgradeSetting = $this->_pos($theCombinedCase, 'LossUpgradeSetting');


		/// for $getUpgradeLevelSettingFn
		if($isNoAccumulation){
			$accumulation = 0; // Group_level::ACCUMULATION_MODE_DISABLE
		}else if($isAccumulationYesRegistrationDate){
			$accumulation = 1; // Group_level::ACCUMULATION_MODE_FROM_REGISTRATION
		}else if($isAccumulationYesLastChangePeriod){
			$accumulation = 4; // Group_level::ACCUMULATION_MODE_LAST_CHANGED_GEADE
		}else if($isAccumulationLastChangePeriodResetIfMet){
			$accumulation = 5; // Group_level::ACCUMULATION_MODE_LAST_CHANGED_GEADE_RESET_IF_MET
		}
		/// handle DowngradeLevelSetting
		$_this = $this;
		if( $isCACB ){
			$settingName = 'devDowngradeMet.CACB';
			// $forGrade = 'downgrade';
			// $params = [$settingName, $forGrade];
			// ref. to _getDowngradeLevelSettingFn > _syncUpgradeLevelSettingByName
			// related _tryUpgradeSuccessTriggerFromCronjobV2
			$data = [];
			$data['setting_name'] = $settingName;
			$data['description'] = $settingName. '.testing';
			// $data['level_upgrade'] = 3; // for downgrade
			/// CB, while No theUpgradeSettingCaseList
			$betAmountMathSign = '>=';
			$betAmountValue = 0;
			$operatorBeforeDeposit = 'and';
			$depositAmountMathSign = '>=';
			$depositAmountValue = 0;
			$operatorBeforeLoss = 'and';
			$lossAmountMathSign = '>=';
			$lossAmountValue = 0;
			$operatorBeforeWin = null;
			$winAmountMathSign = null;
			$winAmountValue = null;
			/// moved to _getParams4PFOVUS().
				// if($isEmptyUpgradeSetting){
				// 	$betAmountMathSign = null;
				// 	$betAmountValue = null;
				// 	$operatorBeforeDeposit = null;
				// 	$depositAmountMathSign = null;
				// 	$depositAmountValue = null;
				// 	$operatorBeforeLoss = null;
				// 	$lossAmountMathSign = null;
				// 	$lossAmountValue = null;
				// 	$operatorBeforeWin = null;
				// 	$winAmountMathSign = null;
				// 	$winAmountValue = null;
				// } else if( $isDepositUpgradeSetting ){
				// 	$betAmountMathSign = null;
				// 	$betAmountValue = null;
				// 	$operatorBeforeDeposit = null;
				// 	$depositAmountMathSign = '>=';
				// 	$depositAmountValue = 0;
				// 	$operatorBeforeLoss = null;
				// 	$lossAmountMathSign = null;
				// 	$lossAmountValue = null;
				// 	$operatorBeforeWin = null;
				// 	$winAmountMathSign = null;
				// 	$winAmountValue = null;
				// } else if( $isBetUpgradeSetting ){
				// 	$betAmountMathSign = '>=';
				// 	$betAmountValue = 0;
				// 	$operatorBeforeDeposit = null;
				// 	$depositAmountMathSign = null;
				// 	$depositAmountValue = null;
				// 	$operatorBeforeLoss = null;
				// 	$lossAmountMathSign = null;
				// 	$lossAmountValue = null;
				// 	$operatorBeforeWin = null;
				// 	$winAmountMathSign = null;
				// 	$winAmountValue = null;
				// } else if( $isWinUpgradeSetting ){
				// 	$betAmountMathSign = null;
				// 	$betAmountValue = null;
				// 	$operatorBeforeDeposit = null;
				// 	$depositAmountMathSign = null;
				// 	$depositAmountValue = null;
				// 	$operatorBeforeLoss = null;
				// 	$lossAmountMathSign = null;
				// 	$lossAmountValue = null;
				// 	$operatorBeforeWin = null;
				// 	$winAmountMathSign = '>=';
				// 	$winAmountValue = 0;
				// } else if( $isLossUpgradeSetting ){
				// 	$betAmountMathSign = null;
				// 	$betAmountValue = null;
				// 	$operatorBeforeDeposit = null;
				// 	$depositAmountMathSign = null;
				// 	$depositAmountValue = null;
				// 	$operatorBeforeLoss = null;
				// 	$lossAmountMathSign = '>=';
				// 	$lossAmountValue = 0;
				// 	$operatorBeforeWin = null;
				// 	$winAmountMathSign = null;
				// 	$winAmountValue = null;
				// } else if( $isDepositBetUpgradeSetting ){
				// 	$betAmountMathSign = '>=';
				// 	$betAmountValue = 0;
				// 	$operatorBeforeDeposit = 'and';
				// 	$depositAmountMathSign = '>=';
				// 	$depositAmountValue = 0;
				// 	$operatorBeforeLoss = null;
				// 	$lossAmountMathSign = null;
				// 	$lossAmountValue = null;
				// 	$operatorBeforeWin = null;
				// 	$winAmountMathSign = null;
				// 	$winAmountValue = null;
				// }
			$params = $this->_getParams4PFOVUS($theCombinedCase, $theTestPlayerInfo);
			// $params = [ $betAmountMathSign // #1
			// 			, $betAmountValue // #2
			// 			, $operatorBeforeDeposit // #3
			// 			, $depositAmountMathSign // #4
			// 			, $depositAmountValue // #5
			// 			, $operatorBeforeLoss // #6
			// 			, $lossAmountMathSign // #7
			// 			, $lossAmountValue // #8
			// 			, $operatorBeforeWin // #9
			// 			, $winAmountMathSign // #10
			// 			, $winAmountValue // #11
			// 		];
			$formula = call_user_func_array([$this, '_prepareFormulaOfVipUpgradeSetting'], $params);
			// $formula = $this->_prepareFormulaOfVipUpgradeSetting($betAmountMathSign
				// 	, $betAmountValue
				// 	, $operatorBeforeDeposit
				// 	, $depositAmountMathSign
				// 	, $depositAmountValue
				// 	, $operatorBeforeLoss
				// 	, $lossAmountMathSign
				// 	, $lossAmountValue
				// 	, $operatorBeforeWin
				// 	, $winAmountMathSign
				// 	, $winAmountValue );

			$data['formula'] = $formula;
			$data['bet_amount_settings'] = NULL; // always be NULL
			/// CA
			$data['accumulation'] = $accumulation; // 0 / 1 / 4 : No / Yes, Registration Date / Yes, Last Change Period
			$data['separate_accumulation_settings'] = NULL; // always be NULL
			//
			$params = [$settingName, $forGrade, $data];
			$getUpgradeLevelSettingFn = function() use ($params, $_this){
				// '_getDowngradeLevelSettingFn'; // so far, CACB
				// $params = [$settingName, $data];
				// $theGenerateCallTrace = $this->CI->utils->generateCallTrace();
				// echo '<pre>';print_r($theGenerateCallTrace);exit();
				return call_user_func_array([$_this, '_getDowngradeLevelSettingFnV2'], $params); // $rlt = $this->_syncUpgradeLevelSettingByName($settingName, $data);
			};
		}else if( $isSACB ){
			$settingName = 'devDowngradeMet.SACB';
			// $forGrade = 'downgrade';
			// $params = [$settingName, $forGrade];
			// ref. to _getDowngradeLevelSettingFn > _syncUpgradeLevelSettingByName
			// related _tryUpgradeSuccessTriggerFromCronjobV2
			$data = [];
			$data['setting_name'] = $settingName;
			$data['description'] = $settingName. '.testing';
			// $data['level_upgrade'] = 3; // for downgrade
			/// CB
			$betAmountMathSign = '>=';
			$betAmountValue = 0;
			$operatorBeforeDeposit = 'and';
			$depositAmountMathSign = '>=';
			$depositAmountValue = 0;
			$operatorBeforeLoss = 'and';
			$lossAmountMathSign = '>=';
			$lossAmountValue = 0;
			$operatorBeforeWin = null;
			$winAmountMathSign = null;
			$winAmountValue = null;
			/// moved to _getParams4PFOVUS
				// if($isEmptyUpgradeSetting){
				// 	$betAmountMathSign = null;
				// 	$betAmountValue = null;
				// 	$operatorBeforeDeposit = null;
				// 	$depositAmountMathSign = null;
				// 	$depositAmountValue = null;
				// 	$operatorBeforeLoss = null;
				// 	$lossAmountMathSign = null;
				// 	$lossAmountValue = null;
				// 	$operatorBeforeWin = null;
				// 	$winAmountMathSign = null;
				// 	$winAmountValue = null;
				// } else if( $isDepositUpgradeSetting ){
				// 	$betAmountMathSign = null;
				// 	$betAmountValue = null;
				// 	$operatorBeforeDeposit = null;
				// 	$depositAmountMathSign = '>=';
				// 	$depositAmountValue = 0;
				// 	$operatorBeforeLoss = null;
				// 	$lossAmountMathSign = null;
				// 	$lossAmountValue = null;
				// 	$operatorBeforeWin = null;
				// 	$winAmountMathSign = null;
				// 	$winAmountValue = null;
				// } else if( $isBetUpgradeSetting ){
				// 	$betAmountMathSign = '>=';
				// 	$betAmountValue = 0;
				// 	$operatorBeforeDeposit = null;
				// 	$depositAmountMathSign = null;
				// 	$depositAmountValue = null;
				// 	$operatorBeforeLoss = null;
				// 	$lossAmountMathSign = null;
				// 	$lossAmountValue = null;
				// 	$operatorBeforeWin = null;
				// 	$winAmountMathSign = null;
				// 	$winAmountValue = null;
				// } else if( $isWinUpgradeSetting ){
				// 	$betAmountMathSign = null;
				// 	$betAmountValue = null;
				// 	$operatorBeforeDeposit = null;
				// 	$depositAmountMathSign = null;
				// 	$depositAmountValue = null;
				// 	$operatorBeforeLoss = null;
				// 	$lossAmountMathSign = null;
				// 	$lossAmountValue = null;
				// 	$operatorBeforeWin = null;
				// 	$winAmountMathSign = '>=';
				// 	$winAmountValue = 0;
				// } else if( $isLossUpgradeSetting ){
				// 	$betAmountMathSign = null;
				// 	$betAmountValue = null;
				// 	$operatorBeforeDeposit = null;
				// 	$depositAmountMathSign = null;
				// 	$depositAmountValue = null;
				// 	$operatorBeforeLoss = null;
				// 	$lossAmountMathSign = '>=';
				// 	$lossAmountValue = 0;
				// 	$operatorBeforeWin = null;
				// 	$winAmountMathSign = null;
				// 	$winAmountValue = null;
				// } else if( $isDepositBetUpgradeSetting ){
				// 	$betAmountMathSign = '>=';
				// 	$betAmountValue = 0;
				// 	$operatorBeforeDeposit = 'and';
				// 	$depositAmountMathSign = '>=';
				// 	$depositAmountValue = 0;
				// 	$operatorBeforeLoss = null;
				// 	$lossAmountMathSign = null;
				// 	$lossAmountValue = null;
				// 	$operatorBeforeWin = null;
				// 	$winAmountMathSign = null;
				// 	$winAmountValue = null;
				// }
			$params = $this->_getParams4PFOVUS($theCombinedCase, $theTestPlayerInfo);
			$formula = call_user_func_array([$this, '_prepareFormulaOfVipUpgradeSetting'], $params);
			// $formula = $this->_prepareFormulaOfVipUpgradeSetting($betAmountMathSign
			// 	, $betAmountValue
			// 	, $operatorBeforeDeposit
			// 	, $depositAmountMathSign
			// 	, $depositAmountValue
			// 	, $operatorBeforeLoss
			// 	, $lossAmountMathSign
			// 	, $lossAmountValue
			// 	, $operatorBeforeWin
			// 	, $winAmountMathSign
			// 	, $winAmountValue );
			$data['formula'] = $formula;
			$data['bet_amount_settings'] = NULL; // always be NULL

			/// SA
			$data['accumulation'] = 0; // 0 / 1 / 4 : No / Yes, Registration Date / Yes, Last Change Period
			$data['separate_accumulation_settings'] = '{"bet_amount": {"accumulation": "1"}, "win_amount": {"accumulation": "4"}, "loss_amount": {"accumulation": "0"}, "deposit_amount": {"accumulation": "1"}}'; // always be NULL

			$params = [$settingName, $forGrade, $data];
			$getUpgradeLevelSettingFn = function() use ($params, $_this){
				return call_user_func_array([$_this, '_getDowngradeLevelSettingFnV2'], $params); // $rlt = $this->_syncUpgradeLevelSettingByName($settingName, $data);
			};
		}else if( $isCASB ){
			$settingName = 'devDowngradeMet.CASB';
			// $forGrade = 'downgrade';
			// $params = [$settingName, $forGrade];
			// ref. to _getDowngradeLevelSettingFn > _syncUpgradeLevelSettingByName
			// related _tryUpgradeSuccessTriggerFromCronjobV2
			$data = [];
			$data['setting_name'] = $settingName;
			$data['description'] = $settingName. '.testing';
			// $data['level_upgrade'] = 3; // for downgrade

			/// SB
			$betAmountMathSign = '<=';
			$betAmountValue = 0;
			$operatorBeforeDeposit = 'and';
			$depositAmountMathSign = '<=';
			$depositAmountValue = 8;
			$operatorBeforeLoss = 'and';
			$lossAmountMathSign = '<=';
			$lossAmountValue = 9;
			$operatorBeforeWin = null;
			$winAmountMathSign = null;
			$winAmountValue = null;
			/// moved to _getParams4PFOVUS
				// if($isEmptyUpgradeSetting){
				// 	$betAmountMathSign = null;
				// 	$betAmountValue = null;
				// 	$operatorBeforeDeposit = null;
				// 	$depositAmountMathSign = null;
				// 	$depositAmountValue = null;
				// 	$operatorBeforeLoss = null;
				// 	$lossAmountMathSign = null;
				// 	$lossAmountValue = null;
				// 	$operatorBeforeWin = null;
				// 	$winAmountMathSign = null;
				// 	$winAmountValue = null;
				// } else if( $isDepositUpgradeSetting ){
				// 	$betAmountMathSign = null;
				// 	$betAmountValue = null;
				// 	$operatorBeforeDeposit = null;
				// 	$depositAmountMathSign = '>=';
				// 	$depositAmountValue = 0;
				// 	$operatorBeforeLoss = null;
				// 	$lossAmountMathSign = null;
				// 	$lossAmountValue = null;
				// 	$operatorBeforeWin = null;
				// 	$winAmountMathSign = null;
				// 	$winAmountValue = null;
				// } else if( $isBetUpgradeSetting ){
				// 	$betAmountMathSign = '>=';
				// 	$betAmountValue = 0;
				// 	$operatorBeforeDeposit = null;
				// 	$depositAmountMathSign = null;
				// 	$depositAmountValue = null;
				// 	$operatorBeforeLoss = null;
				// 	$lossAmountMathSign = null;
				// 	$lossAmountValue = null;
				// 	$operatorBeforeWin = null;
				// 	$winAmountMathSign = null;
				// 	$winAmountValue = null;
				// } else if( $isWinUpgradeSetting ){
				// 	$betAmountMathSign = null;
				// 	$betAmountValue = null;
				// 	$operatorBeforeDeposit = null;
				// 	$depositAmountMathSign = null;
				// 	$depositAmountValue = null;
				// 	$operatorBeforeLoss = null;
				// 	$lossAmountMathSign = null;
				// 	$lossAmountValue = null;
				// 	$operatorBeforeWin = null;
				// 	$winAmountMathSign = '>=';
				// 	$winAmountValue = 0;
				// } else if( $isLossUpgradeSetting ){
				// 	$betAmountMathSign = null;
				// 	$betAmountValue = null;
				// 	$operatorBeforeDeposit = null;
				// 	$depositAmountMathSign = null;
				// 	$depositAmountValue = null;
				// 	$operatorBeforeLoss = null;
				// 	$lossAmountMathSign = '>=';
				// 	$lossAmountValue = 0;
				// 	$operatorBeforeWin = null;
				// 	$winAmountMathSign = null;
				// 	$winAmountValue = null;
				// } else if( $isDepositBetUpgradeSetting ){
				// 	$betAmountMathSign = '>=';
				// 	$betAmountValue = 0;
				// 	$operatorBeforeDeposit = 'and';
				// 	$depositAmountMathSign = '>=';
				// 	$depositAmountValue = 0;
				// 	$operatorBeforeLoss = null;
				// 	$lossAmountMathSign = null;
				// 	$lossAmountValue = null;
				// 	$operatorBeforeWin = null;
				// 	$winAmountMathSign = null;
				// 	$winAmountValue = null;
				// }
			$params = $this->_getParams4PFOVUS($theCombinedCase, $theTestPlayerInfo);
			$formula = call_user_func_array([$this, '_prepareFormulaOfVipUpgradeSetting'], $params);
			// $formula = $this->_prepareFormulaOfVipUpgradeSetting($betAmountMathSign
			// 	, $betAmountValue
			// 	, $operatorBeforeDeposit
			// 	, $depositAmountMathSign
			// 	, $depositAmountValue
			// 	, $operatorBeforeLoss
			// 	, $lossAmountMathSign
			// 	, $lossAmountValue
			// 	, $operatorBeforeWin
			// 	, $winAmountMathSign
			// 	, $winAmountValue );
			$data['formula'] = $formula;

			// game_platform_and_type
			$params = [];
			$params['defaultValue'] = 20;
			$params['defaultMathSign'] = '>=';
			$gameKeyInfoList= [];
			$gameKeyInfoList['type'] = 'game_platform';
			$gameKeyInfoList['value'] = 28;
			$gameKeyInfoList['math_sign'] = '<=';
			$gameKeyInfoList['game_platform_id'] = '5674';// MUST BE STRING
			$params['GKAMSAVL'][] = $gameKeyInfoList;
			$gameKeyInfoList= [];
			$gameKeyInfoList['type'] = 'game_type';
			$gameKeyInfoList['value'] = 29;
			$gameKeyInfoList['math_sign'] = '<=';
			$gameKeyInfoList['game_type_id'] = '561'; // MUST BE STRING
			$gameKeyInfoList['precon_logic_flag'] = 'or';
			$params['GKAMSAVL'][] = $gameKeyInfoList;
			if( $isIsDowngradeConditionMet || $isIsDowngradeConditionNotMet ){
				$params = $this->_gameKeysAndMathSignAndValueListFromCombinedCaseAndTestPlayerInfo($theCombinedCase, $theTestPlayerInfo);
			}
			//
			$defaultValue = $params['defaultValue'];
			$defaultMathSign = $params['defaultMathSign'];
			$gameKeysAndMathSignAndValueList = $params['GKAMSAVL'];
			//
			if($isEmptyUpgradeSetting && false ){ // clear the Separated bet,  ignore for "$upgrade_id = 0;" in admin/application/controllers/cli/testing_ogp21818.php
				$gameKeysAndMathSignAndValueList = [];
			} else if( $isDepositUpgradeSetting ){ // clear the Separated bet
				$gameKeysAndMathSignAndValueList = [];
			} else if( $isBetUpgradeSetting ){ // keep the Separated bet
				$gameKeysAndMathSignAndValueList = $params['GKAMSAVL'];
			} else if( $isWinUpgradeSetting ){ // clear the Separated bet
				$gameKeysAndMathSignAndValueList = [];
			} else if( $isLossUpgradeSetting ){ // clear the Separated bet
				$gameKeysAndMathSignAndValueList = [];
			}
			$theBetAmountSettings = $this->_prepareBetAmountSettingsOfVipUpgradeSetting($defaultValue, $defaultMathSign, $gameKeysAndMathSignAndValueList);
			// $rlt = call_user_func_array([$this, '_prepareBetAmountSettingsOfVipUpgradeSetting'], $testInfo['params']);
			$data['bet_amount_settings'] = $theBetAmountSettings; // always be NULL

			/// CA
			$data['accumulation'] = $accumulation; // 0 / 1 / 4 : No / Yes, Registration Date / Yes, Last Change Period
			$data['separate_accumulation_settings'] = NULL; // always be NULL
			//
			$params = [$settingName, $forGrade, $data];
			$getUpgradeLevelSettingFn = function() use ($params, $_this){
				// '_getDowngradeLevelSettingFn'; // so far, CACB
				// $params = [$settingName, $data];
				// $theGenerateCallTrace = $this->CI->utils->generateCallTrace();
				// echo '<pre>';print_r($theGenerateCallTrace);exit();
				return call_user_func_array([$_this, '_getDowngradeLevelSettingFnV2'], $params); // $rlt = $this->_syncUpgradeLevelSettingByName($settingName, $data);
			};
		}else if( $isSASB ){
			$settingName = 'devDowngradeMet.SASB';
			// $forGrade = 'downgrade';
			// $params = [$settingName, $forGrade];
			// ref. to _getDowngradeLevelSettingFn > _syncUpgradeLevelSettingByName
			// related _tryUpgradeSuccessTriggerFromCronjobV2
			$data = [];
			$data['setting_name'] = $settingName;
			$data['description'] = $settingName. '.testing';
			// $data['level_upgrade'] = 3; // for downgrade

			/// SB
			$betAmountMathSign = '<=';
			$betAmountValue = 0;
			$operatorBeforeDeposit = 'and';
			$depositAmountMathSign = '<=';
			$depositAmountValue = 8;
			$operatorBeforeLoss = 'and';
			$lossAmountMathSign = '<=';
			$lossAmountValue = 9;
			$operatorBeforeWin = null;
			$winAmountMathSign = null;
			$winAmountValue = null;
			/// moved to _getParams4PFOVUS
				// if($isEmptyUpgradeSetting){
				// 	$betAmountMathSign = null;
				// 	$betAmountValue = null;
				// 	$operatorBeforeDeposit = null;
				// 	$depositAmountMathSign = null;
				// 	$depositAmountValue = null;
				// 	$operatorBeforeLoss = null;
				// 	$lossAmountMathSign = null;
				// 	$lossAmountValue = null;
				// 	$operatorBeforeWin = null;
				// 	$winAmountMathSign = null;
				// 	$winAmountValue = null;
				// } else if( $isDepositUpgradeSetting ){
				// 	$betAmountMathSign = null;
				// 	$betAmountValue = null;
				// 	$operatorBeforeDeposit = null;
				// 	$depositAmountMathSign = '>=';
				// 	$depositAmountValue = 0;
				// 	$operatorBeforeLoss = null;
				// 	$lossAmountMathSign = null;
				// 	$lossAmountValue = null;
				// 	$operatorBeforeWin = null;
				// 	$winAmountMathSign = null;
				// 	$winAmountValue = null;
				// } else if( $isBetUpgradeSetting ){
				// 	$betAmountMathSign = '>=';
				// 	$betAmountValue = 0;
				// 	$operatorBeforeDeposit = null;
				// 	$depositAmountMathSign = null;
				// 	$depositAmountValue = null;
				// 	$operatorBeforeLoss = null;
				// 	$lossAmountMathSign = null;
				// 	$lossAmountValue = null;
				// 	$operatorBeforeWin = null;
				// 	$winAmountMathSign = null;
				// 	$winAmountValue = null;
				// } else if( $isWinUpgradeSetting ){
				// 	$betAmountMathSign = null;
				// 	$betAmountValue = null;
				// 	$operatorBeforeDeposit = null;
				// 	$depositAmountMathSign = null;
				// 	$depositAmountValue = null;
				// 	$operatorBeforeLoss = null;
				// 	$lossAmountMathSign = null;
				// 	$lossAmountValue = null;
				// 	$operatorBeforeWin = null;
				// 	$winAmountMathSign = '>=';
				// 	$winAmountValue = 0;
				// } else if( $isLossUpgradeSetting ){
				// 	$betAmountMathSign = null;
				// 	$betAmountValue = null;
				// 	$operatorBeforeDeposit = null;
				// 	$depositAmountMathSign = null;
				// 	$depositAmountValue = null;
				// 	$operatorBeforeLoss = null;
				// 	$lossAmountMathSign = '>=';
				// 	$lossAmountValue = 0;
				// 	$operatorBeforeWin = null;
				// 	$winAmountMathSign = null;
				// 	$winAmountValue = null;
				// } else if( $isDepositBetUpgradeSetting ){
				// 	$betAmountMathSign = '>=';
				// 	$betAmountValue = 0;
				// 	$operatorBeforeDeposit = 'and';
				// 	$depositAmountMathSign = '>=';
				// 	$depositAmountValue = 0;
				// 	$operatorBeforeLoss = null;
				// 	$lossAmountMathSign = null;
				// 	$lossAmountValue = null;
				// 	$operatorBeforeWin = null;
				// 	$winAmountMathSign = null;
				// 	$winAmountValue = null;
				// }
			$params = $this->_getParams4PFOVUS($theCombinedCase, $theTestPlayerInfo);
			$formula = call_user_func_array([$this, '_prepareFormulaOfVipUpgradeSetting'], $params);
			// $formula = $this->_prepareFormulaOfVipUpgradeSetting($betAmountMathSign
			// 	, $betAmountValue
			// 	, $operatorBeforeDeposit
			// 	, $depositAmountMathSign
			// 	, $depositAmountValue
			// 	, $operatorBeforeLoss
			// 	, $lossAmountMathSign
			// 	, $lossAmountValue
			// 	, $operatorBeforeWin
			// 	, $winAmountMathSign
			// 	, $winAmountValue );
			$data['formula'] = $formula;

			// game_platform_and_type
			$params = [];
			$params['defaultValue'] = 20;
			$params['defaultMathSign'] = '>=';
			$gameKeyInfoList= [];
			$gameKeyInfoList['type'] = 'game_platform';
			$gameKeyInfoList['value'] = 28;
			$gameKeyInfoList['math_sign'] = '<=';
			$gameKeyInfoList['game_platform_id'] = '5674';// MUST BE STRING
			$params['GKAMSAVL'][] = $gameKeyInfoList;
			$gameKeyInfoList= [];
			$gameKeyInfoList['type'] = 'game_type';
			$gameKeyInfoList['value'] = 29;
			$gameKeyInfoList['math_sign'] = '<=';
			$gameKeyInfoList['game_type_id'] = '561'; // MUST BE STRING
			$gameKeyInfoList['precon_logic_flag'] = 'or';
			$params['GKAMSAVL'][] = $gameKeyInfoList;
			if( $isIsDowngradeConditionMet || $isIsDowngradeConditionNotMet ){
				$params = $this->_gameKeysAndMathSignAndValueListFromCombinedCaseAndTestPlayerInfo($theCombinedCase, $theTestPlayerInfo);
			}else if($isIsDowngradeConditionNotMet){
				$params = $this->_gameKeysAndMathSignAndValueListFromCombinedCaseAndTestPlayerInfo($theCombinedCase, $theTestPlayerInfo);
			}
			$defaultValue = $params['defaultValue'];
			$defaultMathSign = $params['defaultMathSign'];
			$gameKeysAndMathSignAndValueList = $params['GKAMSAVL'];
			//
			if($isEmptyUpgradeSetting && false){ // clear the Separated bet, ignore for "$upgrade_id = 0;" in admin/application/controllers/cli/testing_ogp21818.php
				$gameKeysAndMathSignAndValueList = [];
			} else if( $isDepositUpgradeSetting ){ // clear the Separated bet
				$gameKeysAndMathSignAndValueList = [];
			} else if( $isBetUpgradeSetting ){ // keep the Separated bet
				$gameKeysAndMathSignAndValueList = $params['GKAMSAVL'];
			} else if( $isWinUpgradeSetting ){ // clear the Separated bet
				$gameKeysAndMathSignAndValueList = [];
			} else if( $isLossUpgradeSetting ){ // clear the Separated bet
				$gameKeysAndMathSignAndValueList = [];
			}
			$theBetAmountSettings = $this->_prepareBetAmountSettingsOfVipUpgradeSetting($defaultValue, $defaultMathSign, $gameKeysAndMathSignAndValueList);
			// $rlt = call_user_func_array([$this, '_prepareBetAmountSettingsOfVipUpgradeSetting'], $testInfo['params']);
			$data['bet_amount_settings'] = $theBetAmountSettings; // always be NULL

			/// SA
			$data['accumulation'] = 0; // 0 / 1 / 4 : No / Yes, Registration Date / Yes, Last Change Period
			$data['separate_accumulation_settings'] = '{"bet_amount": {"accumulation": "1"}, "win_amount": {"accumulation": "4"}, "loss_amount": {"accumulation": "0"}, "deposit_amount": {"accumulation": "1"}}'; // always be NULL

			$params = [$settingName, $forGrade, $data];
			$getUpgradeLevelSettingFn = function() use ($params, $_this){
				// '_getDowngradeLevelSettingFn'; // so far, CACB
				// $params = [$settingName, $data];
				// $theGenerateCallTrace = $this->CI->utils->generateCallTrace();
				// echo '<pre>';print_r($theGenerateCallTrace);exit();
				return call_user_func_array([$_this, '_getDowngradeLevelSettingFnV2'], $params); // $rlt = $this->_syncUpgradeLevelSettingByName($settingName, $data);
			};
		}

		return [$settingName, $getUpgradeLevelSettingFn];
	}// EOF _getUpgradeLevelSettingFnAndSettingNameFromCombinedCase

	/**
	 * Prepare bet_amount_settings json for data of _syncUpgradeLevelSettingByName().
	 * [TESTED]
	 * // {"itemList": [{"type": "game_type", "value": "24", "math_sign": ">=", "game_type_id": "561"}, {"type": "game_platform", "value": "25", "math_sign": ">=", "game_platform_id": "5674", "precon_logic_flag": "and"}], "defaultItem": {"value": "123", "math_sign": ">="}}
	 * @param null|integer $defaultValue
	 * @param string $defaultMathSign
	 * @param array $gameKeysAndMathSignAndValueList
	 * @return void
	 */
	public function _prepareBetAmountSettingsOfVipUpgradeSetting($defaultValue = NULL, $defaultMathSign = '<', $gameKeysAndMathSignAndValueList = []){
		$jsonArray = [];
		$isReturnNull = null;
		if($defaultValue === null){
			$isReturnNull = true;
		}else{
			$isReturnNull = false;
		}
		if( empty($gameKeysAndMathSignAndValueList) ){
			$isReturnNull = true;
		}

		if( ! $isReturnNull ){
			$jsonArray['defaultItem'] = [];
			$jsonArray['defaultItem']['value'] = $defaultValue;
			$jsonArray['defaultItem']['math_sign'] = $defaultMathSign;

			$jsonArray['itemList'] = [];
			if( ! empty($gameKeysAndMathSignAndValueList) ){
				foreach($gameKeysAndMathSignAndValueList as $indexNumber => $curr){
					$jsonArray['itemList'][$indexNumber]['type'] = $curr['type'];
					$jsonArray['itemList'][$indexNumber]['value'] = $curr['value'];
					$jsonArray['itemList'][$indexNumber]['math_sign'] = $curr['math_sign'];
					if($curr['type'] == 'game_type'){
						$jsonArray['itemList'][$indexNumber]['game_type_id'] = $curr['game_type_id'];
					}else if($curr['type'] == 'game_platform'){
						$jsonArray['itemList'][$indexNumber]['game_platform_id'] = $curr['game_platform_id'];
					}
					if($indexNumber > 0 ){
						$jsonArray['itemList'][$indexNumber]['precon_logic_flag'] = $curr['precon_logic_flag'];
					}
				}
			}
		}else{
			$jsonArray = null;
		}

		$jsonStr = json_encode($jsonArray);
		return $jsonStr;
	} // EOF _prepareBetAmountSettingsOfVipUpgradeSetting

	/**
	 * Undocumented function
	 *
	 * @param string $settingName The sync setting name.
	 * @param string $forGrade The keyword,"upgrade" and "downgrade".
	 * @param array $theMergedData
	 * @return array [$rlt, $settingName]
	 */
	public function _getDowngradeLevelSettingFnV2($settingName = 'devDowngradeMet.CACB'
		, $forGrade = 'upgrade'
		, $theMergedData = []
	){
		$data = [];
		$data['setting_name'] = $settingName;
		$data['description'] = $settingName. '.testing';
		$data['status'] = 1; // always be 1 for active.
		// level_upgrade: 1, 3 => upgrade, downgrade
		if($forGrade == 'upgrade'){
			$data['level_upgrade'] = 1; // upgrade
		}else if($forGrade == 'downgrade'){
			$data['level_upgrade'] = 3; // downgrade
		}

		/// CB
		$betAmountMathSign = '>=';
		$betAmountValue = 0;
		$operatorBeforeDeposit = 'and';
		$depositAmountMathSign = '>=';
		$depositAmountValue = 0;
		$operatorBeforeLoss = 'and';
		$lossAmountMathSign = '>=';
		$lossAmountValue = 0;
		$operatorBeforeWin = null;
		$winAmountMathSign = null;
		$winAmountValue = null;
		$formula = $this->_prepareFormulaOfVipUpgradeSetting($betAmountMathSign
			, $betAmountValue
			, $operatorBeforeDeposit
			, $depositAmountMathSign
			, $depositAmountValue
			, $operatorBeforeLoss
			, $lossAmountMathSign
			, $lossAmountValue
			, $operatorBeforeWin
			, $winAmountMathSign
			, $winAmountValue );
		$data['formula'] = $formula;
		$data['bet_amount_settings'] = NULL; // always be NULL

		/// CA
		$data['accumulation'] = 1; // 0 / 1 / 4 / 5: No / Yes, Registration Date / Yes, Last Change Period / Yes, Last Change Period with Reset If Met
		$data['separate_accumulation_settings'] = NULL; // always be NULL
		$data = array_merge( $data, $theMergedData );
		$params = [$settingName, $data];
		$rlt = call_user_func_array([$this, '_syncUpgradeLevelSettingByName'], $params); // $rlt = $this->_syncUpgradeLevelSettingByName($settingName, $data);

		// $note = sprintf($this->noteTpl, '[Step]preset VipUpgradeSetting in CACB for upgrade success', var_export($params, true), var_export($rlt, true) );
		// $this->test( true // result
		// 	,  true // expect
		// 	, __METHOD__. ' '. 'Preset vip_upgrade_setting table' // title
		// 	, $note // note
		// );

		return [$rlt, $settingName];

	} // EOF _getDowngradeLevelSettingFnV2

	/**
	 * Prepare Formula [TESTED]
	 *
	 *
	 * @param string $betAmountMathSign
	 * @param integer|float $betAmountValue
	 * @param string $operatorBeforeDeposit
	 * @param string $depositAmountMathSign
	 * @param integer|float $depositAmountValue
	 * @param string $operatorBeforeLoss
	 * @param string $lossAmountMathSign
	 * @param integer|float $lossAmountValue
	 * @param string $operatorBeforeWin
	 * @param string $winAmountMathSign
	 * @param integer|float $winAmountValue
	 * @return string
	 */
	public function _prepareFormulaOfVipUpgradeSetting( $betAmountMathSign = null // #1
														, $betAmountValue = null // #2
														, $operatorBeforeDeposit = null // #3
														, $depositAmountMathSign = null // #4
														, $depositAmountValue = null // #5
														, $operatorBeforeLoss = null // #6
														, $lossAmountMathSign = null // #7
														, $lossAmountValue = null // #8
														, $operatorBeforeWin = null // #9
														, $winAmountMathSign = null // #10
														, $winAmountValue = null // #11
	){
		// print_r(func_get_args()); echo '<br>';
		$jsonArray = [];
		// {"bet_amount":[">=","0"],"operator_2":"and","deposit_amount":[">=","223"],"operator_3":"and","loss_amount":[">=","0"],"operator_4":"and","win_amount":[">=","0"]}
var_dump(['debugIn3763.betAmountMathSign:', $betAmountMathSign, $betAmountValue, $operatorBeforeDeposit]);
		$isBetAmountNull = null;
		if($betAmountMathSign === null || $betAmountValue === null){
			$isBetAmountNull = true;
		}else{
			$isBetAmountNull = false;
		}

		$isDepositAmountNull = null;
		if($depositAmountMathSign === null || $depositAmountValue === null){
			$isDepositAmountNull = true;
		}else{
			$isDepositAmountNull = false;
		}

		$isLossAmountNull = null;
		if($lossAmountMathSign === null || $lossAmountValue === null){
			$isLossAmountNull = true;
		}else{
			$isLossAmountNull = false;
		}

		$isWinAmountNull = null;
		if($winAmountMathSign === null || $winAmountValue === null){
			$isWinAmountNull = true;
		}else{
			$isWinAmountNull = false;
		}

		$operatorList = []; // for operatorBeforeDeposit, operatorBeforeLoss and operatorBeforeWin

		// handle betAmountValue
		if( ! $isBetAmountNull ){
			$jsonArray['bet_amount'] = [];
			$jsonArray['bet_amount'][0] = $betAmountMathSign; // ">=";
			$jsonArray['bet_amount'][1] = $betAmountValue; // 0;
		}

		/// handle operatorBeforeDeposit
		if( $depositAmountValue !== null && $betAmountValue !== null ){
			$operatorValue = $operatorBeforeDeposit;
			$operatorList[] = $operatorValue;
			$operatorIndex = count($operatorList)+1;
			$operatorKeyString = 'operator_'.$operatorIndex;
			$jsonArray[$operatorKeyString] = $operatorValue;
		}

		// handle depositAmountValue
		if( ! $isDepositAmountNull ){
			$jsonArray['deposit_amount'] = [];
			$jsonArray['deposit_amount'][0] = $depositAmountMathSign; // ">=";
			$jsonArray['deposit_amount'][1] = $depositAmountValue; // 223;
		}

		/// handle operatorBeforeLoss
		if( ($lossAmountValue !== null && $betAmountValue !== null)
			|| ($lossAmountValue !== null && $depositAmountValue !== null )
		){
			$operatorValue = $operatorBeforeLoss;
			$operatorList[] = $operatorValue;
			$operatorIndex = count($operatorList)+1;
			$operatorKeyString = 'operator_'.$operatorIndex;
			$jsonArray[$operatorKeyString] = $operatorValue;
		}

		// handle lossAmountValue
		if( ! $isLossAmountNull ){
			$jsonArray['loss_amount'] = [];
			$jsonArray['loss_amount'][0] = $lossAmountMathSign; // ">=";
			$jsonArray['loss_amount'][1] = $lossAmountValue; // 0;
		}

		// handle operatorBeforeWin
		if( ($winAmountValue !== null && $betAmountValue !== null)
				|| ($winAmountValue !== null && $depositAmountValue !== null )
				|| ($winAmountValue !== null && $lossAmountValue !== null )
			){
				$operatorValue = $operatorBeforeWin;
				$operatorList[] = $operatorValue;
				$operatorIndex = count($operatorList)+1;
				$operatorKeyString = 'operator_'.$operatorIndex;
				$jsonArray[$operatorKeyString] = $operatorValue;
			}

		// handle winAmountValue
		if( ! $isWinAmountNull ){
			$jsonArray['win_amount'] = [];
			$jsonArray['win_amount'][0] = $winAmountMathSign; // ">=";
			$jsonArray['win_amount'][1] = $winAmountValue; // 0;
		}

		if( $isBetAmountNull
			& $isDepositAmountNull
			& $isLossAmountNull
			& $isWinAmountNull
		) {
			$jsonArray = NULL;
		}

		$jsonStr = json_encode($jsonArray);

		return $jsonStr;
	} // EOF _prepareFormulaOfVipUpgradeSetting

	/**
	 *
	 * Add or Update the setting by name
	 *
	 * CACB.add.update [TESTED]:
	 * test_macroSetupCACBInVipUpgradeSetting
	 * test_macroSetupSACBInVipUpgradeSetting
	 * test_macroSetupCASBInVipUpgradeSetting
	 * test_macroSetupSASBInVipUpgradeSetting
	 *
	// Request URL: http://admin.og.local/vipsetting_management/saveUpgradeSetting
	 ==== CACB ====
	// settingName: dev.1add
	// description: dev.1add Description
	// levelUpgrade: 1 <<< upgrade
	// accumulationFrom: 4
	// formula[deposit_amount][]: 1
	// formula[deposit_amount][]: 123
	// formula[bet_amount][]: 2
	// formula[bet_amount][]: 234
	// accumulation: 1
	// conjunction[]: or
	 ==== SASB - CB setting ====
	// settingName: dev.sasb.add
	// description: dev.sasb.add Description
	// levelUpgrade: 3 <<< downgrade
	// accumulationFrom: 0
	// formula[bet_amount][]: >=
	// formula[bet_amount][]: 123
	// formula[operator_2]: or
	// formula[deposit_amount][]: >=
	// formula[deposit_amount][]: 234
	// formula[operator_3]: and
	// formula[loss_amount][]: >=
	// formula[loss_amount][]: 345
	// conjunction[]: or
	// conjunction[]: and
	// bet_settings:
	// accumulation_settings[bet_amount][accumulation]: 1
	// accumulation_settings[deposit_amount][accumulation]: 4
	// accumulation_settings[loss_amount][accumulation]: 0
	// accumulation_settings[win_amount][accumulation]: 0
	// accumulation_bet_amount: 1
	// accumulation_deposit_amount: 4
	// accumulation_loss_amount: 0
	 ==== SASB - SB setting ====
	// settingName: dev.sasb.add2
	// description: dev.sasb.add2 Description
	// levelUpgrade: 3
	// accumulationFrom: 0
	// formula[bet_amount][]: >=
	// formula[bet_amount][]: 0
	// formula[operator_2]: or
	// formula[deposit_amount][]: <=
	// formula[deposit_amount][]: 0
	// formula[operator_4]: and
	// formula[win_amount][]: >=
	// formula[win_amount][]: 0
	// conjunction[]: or
	// conjunction[]: and
	// conjunction[]: or
	// conjunction[]: and
	// bet_settings[itemList][0][type]: game_type
	// bet_settings[itemList][0][game_type_id]: 22
	// bet_settings[itemList][0][value]: 923
	// bet_settings[itemList][0][math_sign]: >=
	// bet_settings[itemList][1][type]: game_type
	// bet_settings[itemList][1][game_type_id]: 257
	// bet_settings[itemList][1][value]: 912
	// bet_settings[itemList][1][math_sign]: <=
	// bet_settings[itemList][1][precon_logic_flag]: or
	// bet_settings[itemList][2][type]: game_platform
	// bet_settings[itemList][2][game_platform_id]: 38
	// bet_settings[itemList][2][value]: 934
	// bet_settings[itemList][2][math_sign]: >=
	// bet_settings[itemList][2][precon_logic_flag]: and
	// bet_settings[defaultItem][value]: 912
	// bet_settings[defaultItem][math_sign]: >=
	// accumulation_settings[bet_amount][accumulation]: 1
	// accumulation_settings[deposit_amount][accumulation]: 0
	// accumulation_settings[loss_amount][accumulation]: 0
	// accumulation_settings[win_amount][accumulation]: 1
	// accumulation_bet_amount: 1
	// accumulation_deposit_amount: 0
	// accumulation_win_amount: 1
	///CA
	// accumulation 0 / 1 / 4 : No / Yes,
	// separate_accumulation_settings NULL
	// formula
	// {"bet_amount":[">=","0"],"operator_2":"and","deposit_amount":[">=","212"]}
	// {"deposit_amount":[">=","45"],"operator_2":"and","bet_amount":["<=","23"]}
	// bet_amount_settings NULL
	///SA
	// accumulation 0
	// separate_accumulation_settings
	// {"bet_amount": {"accumulation": "1"}, "deposit_amount": {"accumulation": "1"}}
	// {"bet_amount": {"accumulation": "1"}, "win_amount": {"accumulation": "4"}, "loss_amount": {"accumulation": "0"}, "deposit_amount": {"accumulation": "1"}}
	*/
	public function _syncUpgradeLevelSettingByName($settingName, $data){
		$this->CI->load->model(['group_level']);
		$upgrade_id = null;
		$result = null;
		$settingList = $this->_getVip_upgrade_settingListBysettingName($settingName);

		if( ! empty($settingList) ){
			$upgrade_id = $settingList[0]['upgrade_id'];
		}
		$resultJson = [];
		if( ! empty($data) ){
			// $data['setting_name'] = $this->input->post('settingName');
			// $data['description'] = $this->input->post('description');
			// $data['status'] = self::UPGRADE_ACTIVE;
			// $data['level_upgrade'] = $this->input->post('levelUpgrade');
			// $data['formula'] = json_encode($resultJson);
			// $data['accumulation'] = $_accumulation;
			// $data['separate_accumulation_settings
			// $data['bet_amount_settings
			$data['created_at'] = $this->CI->utils->getNowForMysql();
			if ( ! empty($upgrade_id) ){
				$data['upgrade_id'] = $upgrade_id;
			}
			$result = $this->CI->group_level->addUpgradeLevelSetting($data);
		}
		return $result;
	}// _syncUpgradeLevelSetting

	/**
	 * Get the setting  by setting_name
	 * For hook to vipsettingcashbackrule.vip_upgrade_id while upgrade
	 * and vipsettingcashbackrule.vip_downgrade_id while downgrade
	 *
	 * @param string $settingName The field,"vip_upgrade_setting.setting_name".
	 * @return array The rows array.
	 */
	public function _getVip_upgrade_settingListBySettingName($settingName){
		$this->CI->load->model(['group_level']);
		$this->CI->group_level->db->from('vip_upgrade_setting');
		$this->CI->group_level->db->where('vip_upgrade_setting.setting_name', $settingName);
		$the_vip_upgrade_setting_list = $this->CI->group_level->runMultipleRowArray();

		// $this->returnText(__METHOD__.'.returnText().theVipUpgradeList: '.var_export($theVipUpgradeList, true));
		return $the_vip_upgrade_setting_list;
	} // EOF _getVip_upgrade_settingListBySettingName

	/**
	 * Setup the Period in the level of the Player Current.
	 * [TRIED]
	 * @param integer $thePlayerId The field, player.playerId.
	 * @param string $gradeMode For upgrade or downgrade.
	 * @param string $theJson The json string for Period settgings.
	 * @return void
	 */
	public function _preSetupPeriodInPlayerCurrentLevel($thePlayerId, $gradeMode = 'upgrade', $theJson = '{}', $isEnableTesting = false){
		$this->CI->load->model(['group_level', 'player']);
		$result = $this->CI->player->getPlayerCurrentLevel($thePlayerId);
		$thePlayerCurrentLevel = $result[0];
		// $vipLevel = $thePlayerCurrentLevel['vipLevel'];
		$vipsettingcashbackruleId = $thePlayerCurrentLevel['vipsettingcashbackruleId'];
		if($gradeMode == 'upgrade'){
			$targetField = 'period_up_down_2';
		}
		if($gradeMode == 'downgrade'){
			$targetField = 'period_down';
		}
		$period = $theJson;
		$rlt = $this->_updateUpgradeIdInVipsettingcashbackrule($period, $vipsettingcashbackruleId, $targetField, $isEnableTesting);
	} // EOF _preSetupPeriodInPlayerCurrentLevel

	/**
	 * update vipsettingcashbackrule.vip_upgrade_id field or other field,ex:"vip_downgrade_id".
	 *
	 * @param integer $upgrade_id The field, "vipsettingcashbackrule.vip_upgrade_id".
	 * @param integer $vipsettingcashbackruleId The PK field, "vipsettingcashbackrule.vipsettingcashbackruleId".
	 * @return integer The return of CI_DB_driver::affected_rows().
	 */
	public function _updateUpgradeIdInVipsettingcashbackrule($upgrade_id, $vipsettingcashbackruleId, $targetField='vip_downgrade_id', $isEnableTesting = false){
		if( ! $isEnableTesting ){
			return null;
		}
		$this->CI->load->model(['group_level']);
		$sql = "update vipsettingcashbackrule set $targetField=? where vipsettingcashbackruleId=?";
		return $this->CI->group_level->runRawUpdateInsertSQL($sql, array($upgrade_id, $vipsettingcashbackruleId));
	}

	public function _preSetupGuaranteedDowngradeInPlayerCurrentLevel($thePlayerId, $period_number = 0, $period_total_deposit = 0, $isEnableTesting = false){
		$this->CI->load->model(['group_level', 'player']);
		$result = $this->CI->player->getPlayerCurrentLevel($thePlayerId);
		$thePlayerCurrentLevel = $result[0];
		$vipsettingcashbackruleId = $thePlayerCurrentLevel['vipsettingcashbackruleId'];
		$targetField = 'guaranteed_downgrade_period_number';
		$targetValue = $period_number;
		$rlt4period_number = $this->_updateUpgradeIdInVipsettingcashbackrule($targetValue, $vipsettingcashbackruleId, $targetField, $isEnableTesting);

		$targetField = 'guaranteed_downgrade_period_total_deposit';
		$targetValue = $period_total_deposit;
		$rlt4period_total_deposit = $this->_updateUpgradeIdInVipsettingcashbackrule($targetValue, $vipsettingcashbackruleId, $targetField, $isEnableTesting);

		return [$rlt4period_number, $rlt4period_total_deposit];
	}

	/**
	 * For get PeriodMode and PeriodValue
	 * parse the vipsettingcashbackrule.period_down / vipsettingcashbackrule.period_up_down_2
	 *
	 *
	 * @param string $thePeriodDownStr The fields, vipsettingcashbackrule.period_down Or vipsettingcashbackrule.period_up_down_2.
	 * @return array The array format,
	 * - $thePeriodInfo['PeriodMode'] string Examples, daily, weekly and monthly.
	 * - $thePeriodInfo['PeriodValue'] integer|string The integer type for weekly and monthly, string type for daily.
	 */
	public function _parsePeriodInfoInPeriod_down($thePeriodDownStr = '[]'){
		$thePeriodInfo = [];
		$thePeriodDown = $this->CI->utils->json_decode_handleErr($thePeriodDownStr, true);
		if( !empty($thePeriodDown) ){
			if(array_key_exists('daily', $thePeriodDown) !== false){
				$thePeriodInfo['PeriodMode'] = 'daily';
				$thePeriodInfo['PeriodValue'] = $thePeriodDown['daily'];
			}else if(array_key_exists('weekly', $thePeriodDown) !== false){
				$thePeriodInfo['PeriodMode'] = 'weekly';
				$thePeriodInfo['PeriodValue'] = $thePeriodDown['weekly'];
			}else if(array_key_exists('monthly', $thePeriodDown) !== false){
				$thePeriodInfo['PeriodMode'] = 'monthly';
				$thePeriodInfo['PeriodValue'] = $thePeriodDown['monthly'];
			}
		}
		return $thePeriodInfo;
	} // _parsePeriodInfoInPeriod_down

	/**
	 * Get the Json string for Period contains Level Maintain and hourly, if need.
	 *
	 * @param string $periodMode So far, support daily, weekly and monthly.
	 * @param string|integer $periodValue Reference to $periodMode.
	 * @param bool $isHourly For upgrade, If checked the hourly checkbox then true else false.
	 * @param array $extraData For Level Maintain of downgrade.
	 * @return string The json string for the fields,"vipsettingcashbackrule.period_down" and "vipsettingcashbackrule.period_up_down_2".
	 */
	/// for downgrade
	// vipsettingcashbackrule.period_down
	// {"monthly":"1","enableDownMaintain":true,"downMaintainUnit":"3","downMaintainTimeLength":"2","downMaintainConditionDepositAmount":"0","downMaintainConditionBetAmount":"1"}
	// {"monthly":"6","enableDownMaintain":true,"downMaintainUnit":"3","downMaintainTimeLength":"2","downMaintainConditionDepositAmount":"0","downMaintainConditionBetAmount":"1"}
	// {"monthly":"6"}
	//
	/// for upgrade
	// vipsettingcashbackrule.period_up_down_2
	// {"weekly":"6"}
	// {"weekly":"6","hourly":true}
	// {"daily":"00:00:00 - 23:59:59"}
	// {"daily":"00:00:00 - 23:59:59","hourly":true}
	// {"hourly":true}
	// []
	//
	public function _getPeriodJson($periodMode = 'weekly',$periodValue = '', $isHourly = null, $extraData = []){
		$jsonArray = [];
		switch( strtolower($periodMode) ){
			case 'daily':
				if( ! empty($periodValue) ){
					$periodValue = '00:00:00 - 23:59:59';
					$jsonArray['daily'] = $periodValue;
				}
				break;
			case 'weekly':
				if( ! empty($periodValue)
					&&  1 <= $periodValue && $periodValue <= 7 // periodValue : 1~7
				){

					$jsonArray['weekly'] = $periodValue;
				}
				break;
			case 'monthly':
				if( ! empty($periodValue)
					&&  1 <= $periodValue && $periodValue <= 31 // periodValue : 1~31
				){
					$jsonArray['monthly'] = $periodValue;
				}
				break;
			default:
				break;
		}
		if( ! empty($isHourly) ){
			$jsonArray['hourly'] = true;
		}
		$jsonArray = array_merge($jsonArray, $extraData);

		$jsonStr = json_encode($jsonArray);

		return $jsonStr;
	}// EOF _getPeriodJson

	/**
	 * Display the all kind of case for test
	 * [TESTED] URI,
	 * http://admin.og.local/cli/testing_ogp21799/index/displayCaseKindList
	 *
	 * @return void Display in the browser
	 */
	public function displayCaseKindList($rows = []){
		if(empty($rows) ){
			$rows = $this->_assignCaseKindList();
		}
		// echo '<pre>';
		// echo var_export($rows, true);

		// Reference to https://www.daniweb.com/programming/web-development/threads/257754/plz-help-to-display-multidimensional-array-in-html-table
		$table = "<table>";
		foreach($rows as $value) {
			foreach($value as $keyword => $info) {
			$table .= "<tr>";
				$table .= "<th>$keyword</th>";
				$table .= "<td>$info</td>";
				$table .= "</tr>";
			}
		}
		$table .= "</table>";
		echo $table;
	} // EOF displayCaseKindList

	/**
	 * Undocumented function
	 *
	 * @param [type] $array
	 * @return void
	 */
	public function _genPhpCode4EachCaseList($array){
		$testCaseList = $this->combination_arr($array);

		$phpCode = '';
		$prefix = 'if( ';
		$suffix = ' }';
		$phpCodeFormatList = [];

		$arrayLength = count($array);

		switch($arrayLength){
			case 5: // 5+ 2 params
				$format = '$is%s && $is%s && $is%s && $is%s && $is%s ){ // # %d'. PHP_EOL. "\t". '$caseNo = %d;'. PHP_EOL. '}else if( '; // 5+ 2 params
				break;
			case 6: // 6+ 2 params
				$format = '$is%s && $is%s && $is%s && $is%s && $is%s && $is%s ){ // # %d'. PHP_EOL. "\t". '$caseNo = %d;'. PHP_EOL. '}else if( '; // 6+ 2 params
				break;
		}
		// $format = '$is%s && $is%s && $is%s && $is%s && $is%s && $is%s){ // # %d'. PHP_EOL. "\t". '$caseNo = %d;'. PHP_EOL. '}else if( '; // 6+ 2 params
		// $format = '$is%s && $is%s && $is%s && $is%s && $is%s ){ // # %d'. PHP_EOL. "\t". '$caseNo = %d;'. PHP_EOL. '}else if( '; // 5+ 2 params
		foreach($testCaseList as $indexNumber => $testCase){
			// format: $isEmptyPeriodMode && $isPeriodIsMet && $isInMaintainTime){ // # 0
			// }else if(
			// prefix: "if( "
			// suffix: " }"
			// $sprintf = sprintf($format, $testCase[0], $testCase[1], $testCase[2], $testCase[3], $testCase[4], $testCase[5], $indexNumber, $indexNumber); // 6+ 2 params
			// $sprintf = sprintf($format, $testCase[0], $testCase[1], $testCase[2], $testCase[3], $testCase[4], $indexNumber, $indexNumber); // 5+ 2 params
			switch($arrayLength){
				case 5: // 5+ 2 params
					$params = [$format, $testCase[0], $testCase[1], $testCase[2], $testCase[3], $testCase[4], $indexNumber, $indexNumber];
					break;
				case 6: // 6+ 2 params
					$params = [$format, $testCase[0], $testCase[1], $testCase[2], $testCase[3], $testCase[4], $testCase[5], $indexNumber, $indexNumber];
					break;
			}
			$sprintf = call_user_func_array('sprintf', $params);
			$phpCodeFormatList[] = $sprintf;
		}
		$phpCode = $prefix. implode('', $phpCodeFormatList). $suffix;
		$phpCode = str_replace('}else if(  }','}',$phpCode);
		return $phpCode;
	}// EOF _genPhpCode4EachCaseList

	public function _gameKeysAndMathSignAndValueListFromCombinedCaseAndTestPlayerInfo($theCombinedCase, $theTestPlayerInfo){
		$isCACB = $this->_pos($theCombinedCase, 'CACB');
		$isSACB = $this->_pos($theCombinedCase, 'SACB');
		$isCASB = $this->_pos($theCombinedCase, 'CASB');
		$isSASB = $this->_pos($theCombinedCase, 'SASB');

		$isIsDowngradeConditionMet = $this->_pos($theCombinedCase, 'IsDowngradeConditionMet');
		$isIsDowngradeConditionNotMet = $this->_pos($theCombinedCase, 'IsDowngradeConditionNotMet');

		$params = [];

		if($isCASB || $isSASB){
			if( $isIsDowngradeConditionMet ){
				$params = [];
				$params['defaultValue'] = 1;
				$params['defaultMathSign'] = '>=';
				$gameKeyInfoList= [];
				$gameKeyInfoList['type'] = 'game_platform';
				$gameKeyInfoList['value'] = 0;
				$gameKeyInfoList['math_sign'] = '>=';
				$gameKeyInfoList['game_platform_id'] = ''. $theTestPlayerInfo['game_platform_id'];// MUST BE STRING
				$params['GKAMSAVL'][] = $gameKeyInfoList;
				$gameKeyInfoList= [];
				$gameKeyInfoList['type'] = 'game_type';
				$gameKeyInfoList['value'] = 0;
				$gameKeyInfoList['math_sign'] = '>=';
				$gameKeyInfoList['game_type_id'] = ''. $theTestPlayerInfo['game_type_id']; // MUST BE STRING
				$gameKeyInfoList['precon_logic_flag'] = 'and';
				$params['GKAMSAVL'][] = $gameKeyInfoList;
			}else if( $isIsDowngradeConditionNotMet ){
				// betAmount < 0 ... Not MET
				// game_platform_and_type
				$params = [];
				$params['defaultValue'] = 1;
				$params['defaultMathSign'] = '<';
				$gameKeyInfoList= [];
				$gameKeyInfoList['type'] = 'game_platform';
				$gameKeyInfoList['value'] = 0;
				$gameKeyInfoList['math_sign'] = '<';
				$gameKeyInfoList['game_platform_id'] = ''. $theTestPlayerInfo['game_platform_id'];// MUST BE STRING
				$params['GKAMSAVL'][] = $gameKeyInfoList;
				$gameKeyInfoList= [];
				$gameKeyInfoList['type'] = 'game_type';
				$gameKeyInfoList['value'] = 0;
				$gameKeyInfoList['math_sign'] = '<';
				$gameKeyInfoList['game_type_id'] = ''. $theTestPlayerInfo['game_type_id']; // MUST BE STRING
				$gameKeyInfoList['precon_logic_flag'] = 'and';
				$params['GKAMSAVL'][] = $gameKeyInfoList;
			}
		}
		return $params;
	}


	/**
	 * Get SettingName after generated the Upgrade Level Setting function/script.
	 *
	 * @param string $theCombinedCase
	 * @param string $theSettingNamePrefix
	 * @param string $forGrade For upgrade OR downgrade
	 * @param array $theTestPlayerInfo A row of utils4testogp::_searchTestPlayerListFilteredLowestLevel(), for bet_amount_settings in SB.
	 * If its empty.
	 * In the formula of the setting, the games by $theTestPlayerInfo['game_platform_id'] and $theTestPlayerInfo['game_type_id'], that will be where the player had NOT bet on.
	 * If its Not empty.
	 * In the formula of the setting, the games by $theTestPlayerInfo['game_platform_id'] and $theTestPlayerInfo['game_type_id'], that will be where the player had bet on.
	 * @param object $testing_ogp The class testing_ogpxxxx, ex:"testing_ogp24373".
	 * @return array [$settingName, $getUpgradeLevelSettingFn]
	 * - $settingName The setting name, aka. `vip_upgrade_setting`.`setting_name` field.
	 * - $getUpgradeLevelSettingFn The callable setting script of the setting by name.
	 */
	public function _getUpgradeLevelSettingFnAndSettingNameFromCombinedCaseV2( $theCombinedCase
																					, $theSettingNamePrefix = 'devDowngradeMet'
																					, $forGrade = 'downgrade'
																					, $theTestPlayerInfo = []
																					, $testing_ogp
	){
		var_dump(['debugIn4197.in_getUpgradeLevelSettingFnAndSettingNameFromCombinedCaseV2'
			, 'theCombinedCase:', $theCombinedCase
		]);
		// will return [$settingName, $getUpgradeLevelSettingFn]

		$isCACB = $this->_pos($theCombinedCase, 'CACB');
		$isSACB = $this->_pos($theCombinedCase, 'SACB');
		$isCASB = $this->_pos($theCombinedCase, 'CASB');
		$isSASB = $this->_pos($theCombinedCase, 'SASB');

		$isNoAccumulation = $this->_pos($theCombinedCase, 'NoAccumulation');
		$isAccumulationYesRegistrationDate = $this->_pos($theCombinedCase, 'AccumulationYesRegistrationDate');
		$isAccumulationYesLastChangePeriod = $this->_pos($theCombinedCase, 'AccumulationYesLastChangePeriod');
		$isAccumulationLastChangePeriodResetIfMet = $this->_pos($theCombinedCase, 'AccumulationLastChangePeriodResetIfMet');


		$isEmptyUpgradeSetting = $this->_pos($theCombinedCase, '.EmptyUpgradeSetting');
		$isDepositUpgradeSetting = $this->_pos($theCombinedCase, '.DepositUpgradeSetting');
		$isBetUpgradeSetting = $this->_pos($theCombinedCase, '.BetUpgradeSetting');
		$isDepositBetUpgradeSetting = $this->_pos($theCombinedCase, '.DepositBetUpgradeSetting');
		$isWinUpgradeSetting = $this->_pos($theCombinedCase, '.WinUpgradeSetting');
		$isLossUpgradeSetting = $this->_pos($theCombinedCase, '.LossUpgradeSetting');

		$isNextLevelUpEmptyUpgradeSetting = $this->_pos($theCombinedCase, '.NextLevelUpEmptyUpgradeSetting');
		$isNextLevelUpDepositUpgradeSetting = $this->_pos($theCombinedCase, '.NextLevelUpDepositUpgradeSetting');
		$isNextLevelUpBetUpgradeSetting = $this->_pos($theCombinedCase, '.NextLevelUpBetUpgradeSetting');
		$isNextLevelUpDepositBetUpgradeSetting = $this->_pos($theCombinedCase, '.NextLevelUpDepositBetUpgradeSetting');
		$isNextLevelUpWinUpgradeSetting = $this->_pos($theCombinedCase, '.NextLevelUpWinUpgradeSetting');
		$isNextLevelUpLossUpgradeSetting = $this->_pos($theCombinedCase, '.NextLevelUpLossUpgradeSetting');

		/// Deprecated, for Utils4testogp::_gameKeysAndMathSignAndValueListFromCombinedCaseAndTestPlayerInfo()
		// for bet_amount_settings in SB
		// handle the cases,the player has the bets of the game or not, in the formula.
		// Please refer. to isIsConditionMet and isIsConditionNotMet for replaced.
		$isIsDowngradeConditionMet = $this->_pos($theCombinedCase, 'IsDowngradeConditionMet');
		$isIsDowngradeConditionNotMet = $this->_pos($theCombinedCase, 'IsDowngradeConditionNotMet');
		if($forGrade == 'downgrade'){
			if($isIsDowngradeConditionMet){
				$isIsConditionMet = true;
			}
			if($IsDowngradeConditionNotMet){
				$isIsConditionNotMet = true;
			}
		}



		$isAllNoMetInFormula = $this->_pos($theCombinedCase, '.AllNoMetInFormula');
		$isOnlyMetBetOfAllInFormula = $this->_pos($theCombinedCase, '.OnlyMetBetOfAllInFormula');
		$isOnlyMetDepositOfAllInFormula = $this->_pos($theCombinedCase, '.OnlyMetDepositOfAllInFormula');
		$isOnlyMetBetDepositOfAllInFormula = $this->_pos($theCombinedCase, '.OnlyMetBetDepositOfAllInFormula');
		$isOnlyMetBetDepositWinOfAllInFormula = $this->_pos($theCombinedCase, '.OnlyMetBetDepositWinOfAllInFormula');
		$isAllMetInFormula = $this->_pos($theCombinedCase, '.AllMetInFormula');
		$isOnlyMetBetOfBetInFormula = $this->_pos($theCombinedCase, '.OnlyMetBetOfBetInFormula');
		$isOnlyMetBetOfBetDepositInFormula = $this->_pos($theCombinedCase, '.OnlyMetBetOfBetDepositInFormula');

		///  replace the cases(x8), EmptyUpgradeSetting, DepositUpgradeSetting, BetUpgradeSetting and DepositBetUpgradeSetting to the followings,
		$isEmptyInFormula = $this->_pos($theCombinedCase, '.EmptyInFormula');
		$isOnlyMetDepositOfDepositInFormula = $this->_pos($theCombinedCase, '.OnlyMetDepositOfDepositInFormula');
		$isNotMetDepositOfDepositInFormula = $this->_pos($theCombinedCase, '.NotMetDepositOfDepositInFormula');

		$isNotMetBetOfBetInFormula = $this->_pos($theCombinedCase, '.NotMetBetOfBetInFormula');
		$isOnlyMetDepositOfBetDepositInFormula = $this->_pos($theCombinedCase, '.OnlyMetDepositOfBetDepositInFormula');
		$isNoMetAllOfBetDepositInFormula = $this->_pos($theCombinedCase, '.NoMetAllOfBetDepositInFormula');
		$isAllMetOfBetDepositInFormula = $this->_pos($theCombinedCase, '.AllMetOfBetDepositInFormula');




		/// for $getUpgradeLevelSettingFn
		$accumulation = 1; // default, isAccumulationYesRegistrationDate
		if($isNoAccumulation){
			$accumulation = 0; // Group_level::ACCUMULATION_MODE_DISABLE
		}else if($isAccumulationYesRegistrationDate){
			$accumulation = 1; // Group_level::ACCUMULATION_MODE_FROM_REGISTRATION
		}else if($isAccumulationYesLastChangePeriod){
			$accumulation = 4; // Group_level::ACCUMULATION_MODE_LAST_CHANGED_GEADE
		}else if($isAccumulationLastChangePeriodResetIfMet){
			$accumulation = 5; // Group_level::ACCUMULATION_MODE_LAST_CHANGED_GEADE_RESET_IF_MET
		}

		/// handle #1
		switch(true){
			case $isCACB :
				$settingName = $theSettingNamePrefix.'.CACB'; // #1
				break;
			case $isSACB :
				$settingName = $theSettingNamePrefix.'.SACB'; // #1
				break;
			case $isCASB :
				$settingName = $theSettingNamePrefix.'.CASB'; // #1
				break;
			case $isSASB :
				$settingName = $theSettingNamePrefix.'.SASB'; // #1
				break;
		}


		$isIsConditionMet= $this->_pos($theCombinedCase, 'IsConditionMet');
		$isIsConditionNotMet = $this->_pos($theCombinedCase, 'IsConditionNotMet');
			// var_dump(['debugIn5081.isCASB:', $isCASB, 'theCombinedCase:', $theCombinedCase]);
		/// handle #2 ~ #12
		switch(true){
			case $isNextLevelUpEmptyUpgradeSetting:
			case $isEmptyUpgradeSetting:
			case $isEmptyInFormula:
				// Formula, ""
				//  But maybe the variable "$upgrade_id = 0;" will be used in admin/application/controllers/cli/testing_ogp21818.php
				$betAmountMathSign = null; // #2
				$betAmountValue = null; // #3
				$operatorBeforeDeposit = null; // #4
				$depositAmountMathSign = null; // #5
				$depositAmountValue = null; // #6
				$operatorBeforeLoss = null; // #7
				$lossAmountMathSign = null; // #8
				$lossAmountValue = null; // #9
				$operatorBeforeWin = null; // #10
				$winAmountMathSign = null; // #11
				$winAmountValue = null; // #12
				break;
			case $isNextLevelUpDepositUpgradeSetting:
			case $isDepositUpgradeSetting:
			case $isOnlyMetDepositOfDepositInFormula:
				// Formula, "deposit >= 0"
				$betAmountMathSign = null; // #2
				$betAmountValue = null; // #3
				$operatorBeforeDeposit = null; // #4
				$depositAmountMathSign = '>='; // #5
				$depositAmountValue = 0; // #6
				$operatorBeforeLoss = null; // #7
				$lossAmountMathSign = null; // #8
				$lossAmountValue = null; // #9
				$operatorBeforeWin = null; // #10
				$winAmountMathSign = null; // #11
				$winAmountValue = null; // #12
				// if($isIsDowngradeConditionNotMet){
				// 	$depositAmountMathSign = '<'; // #5
				// }
				break;
			case $isNotMetDepositOfDepositInFormula:
				// Formula, "deposit < 0"
				$betAmountMathSign = null; // #2
				$betAmountValue = null; // #3
				$operatorBeforeDeposit = null; // #4
				$depositAmountMathSign = '<'; // #5
				$depositAmountValue = 0; // #6
				$operatorBeforeLoss = null; // #7
				$lossAmountMathSign = null; // #8
				$lossAmountValue = null; // #9
				$operatorBeforeWin = null; // #10
				$winAmountMathSign = null; // #11
				$winAmountValue = null; // #12
				break;
			case $isNextLevelUpBetUpgradeSetting:
			case $isBetUpgradeSetting:
			case $isOnlyMetBetOfBetInFormula:
				// Formula, "bet >= 0"
				$betAmountMathSign = '>='; // #2
				$betAmountValue = 0; // #3
				$operatorBeforeDeposit = null; // #4
				$depositAmountMathSign = null; // #5
				$depositAmountValue = null; // #6
				$operatorBeforeLoss = null; // #7
				$lossAmountMathSign = null; // #8
				$lossAmountValue = null; // #9
				$operatorBeforeWin = null; // #10
				$winAmountMathSign = null; // #11
				$winAmountValue = null; // #12
				break;
			case $isNotMetBetOfBetInFormula:
				// Formula, "bet < 0"
				$betAmountMathSign = '<'; // #2
				$betAmountValue = 0; // #3
				$operatorBeforeDeposit = null; // #4
				$depositAmountMathSign = null; // #5
				$depositAmountValue = null; // #6
				$operatorBeforeLoss = null; // #7
				$lossAmountMathSign = null; // #8
				$lossAmountValue = null; // #9
				$operatorBeforeWin = null; // #10
				$winAmountMathSign = null; // #11
				$winAmountValue = null; // #12
				break;
			case $isNextLevelUpDepositBetUpgradeSetting:
			case $isDepositBetUpgradeSetting:
			case $isAllMetOfBetDepositInFormula:
				// Formula, "bet >= 0 and deposit >= 0"
				$betAmountMathSign = '>='; // #2
				$betAmountValue = 0; // #3
				$operatorBeforeDeposit = 'and'; // #4
				$depositAmountMathSign = '>='; // #5
				$depositAmountValue = 0; // #6
				$operatorBeforeLoss = null; // #7
				$lossAmountMathSign = null; // #8
				$lossAmountValue = null; // #9
				$operatorBeforeWin = null; // #10
				$winAmountMathSign = null; // #11
				$winAmountValue = null; // #12
				break;
			case $isOnlyMetDepositOfBetDepositInFormula:
				// Formula, "bet < 0 and deposit >= 0"
				$betAmountMathSign = '<'; // #2
				$betAmountValue = 0; // #3
				$operatorBeforeDeposit = 'and'; // #4
				$depositAmountMathSign = '>='; // #5
				$depositAmountValue = 0; // #6
				$operatorBeforeLoss = null; // #7
				$lossAmountMathSign = null; // #8
				$lossAmountValue = null; // #9
				$operatorBeforeWin = null; // #10
				$winAmountMathSign = null; // #11
				$winAmountValue = null; // #12
				break;
			case $isNoMetAllOfBetDepositInFormula:
				// Formula, "bet < 0 and deposit < 0"
				$betAmountMathSign = '<'; // #2
				$betAmountValue = 0; // #3
				$operatorBeforeDeposit = 'and'; // #4
				$depositAmountMathSign = '<'; // #5
				$depositAmountValue = 0; // #6
				$operatorBeforeLoss = null; // #7
				$lossAmountMathSign = null; // #8
				$lossAmountValue = null; // #9
				$operatorBeforeWin = null; // #10
				$winAmountMathSign = null; // #11
				$winAmountValue = null; // #12
				break;
			// case $isNextLevelUpAllNoMetInFormula: // @todo
			case $isAllNoMetInFormula:
				// Formula, "bet < 0 and deposit < 0 and loss < 0 and win < 0"
				$betAmountMathSign = '<'; // #2
				$betAmountValue = 0; // #3
				$operatorBeforeDeposit = 'and'; // #4
				$depositAmountMathSign = '<'; // #5
				$depositAmountValue = 0; // #6
				$operatorBeforeLoss = 'and'; // #7
				$lossAmountMathSign = '<'; // #8
				$lossAmountValue = 0; // #9
				$operatorBeforeWin = 'and'; // #10
				$winAmountMathSign = '<'; // #11
				$winAmountValue = 0; // #12
				break;
			case $isOnlyMetBetOfAllInFormula:
				// Formula, "bet >= 0 and deposit < 0 and loss < 0 and win < 0"
				$betAmountMathSign = '>='; // #2
				$betAmountValue = 0; // #3
				$operatorBeforeDeposit = 'and'; // #4
				$depositAmountMathSign = '<'; // #5
				$depositAmountValue = 0; // #6
				$operatorBeforeLoss = 'and'; // #7
				$lossAmountMathSign = '<'; // #8
				$lossAmountValue = 0; // #9
				$operatorBeforeWin = 'and'; // #10
				$winAmountMathSign = '<'; // #11
				$winAmountValue = 0; // #12
				break;
			case $isOnlyMetDepositOfAllInFormula:
				// Formula, "bet < 0 and deposit >= 0 and loss < 0 and win < 0"
				$betAmountMathSign = '<'; // #2
				$betAmountValue = 0; // #3
				$operatorBeforeDeposit = 'and'; // #4
				$depositAmountMathSign = '>='; // #5
				$depositAmountValue = 0; // #6
				$operatorBeforeLoss = 'and'; // #7
				$lossAmountMathSign = '<'; // #8
				$lossAmountValue = 0; // #9
				$operatorBeforeWin = 'and'; // #10
				$winAmountMathSign = '<'; // #11
				$winAmountValue = 0; // #12
				break;
			case $isOnlyMetBetDepositOfAllInFormula:
				// Formula, "bet >= 0 and deposit >= 0 and loss < 0 and win < 0"
				$betAmountMathSign = '>='; // #2
				$betAmountValue = 0; // #3
				$operatorBeforeDeposit = 'and'; // #4
				$depositAmountMathSign = '>='; // #5
				$depositAmountValue = 0; // #6
				$operatorBeforeLoss = 'and'; // #7
				$lossAmountMathSign = '<'; // #8
				$lossAmountValue = 0; // #9
				$operatorBeforeWin = 'and'; // #10
				$winAmountMathSign = '<'; // #11
				$winAmountValue = 0; // #12
				break;
			case $isOnlyMetBetDepositWinOfAllInFormula:
				// Formula, "bet >= 0 and deposit >= 0 and loss < 0 and win >= 0"
				$betAmountMathSign = '>='; // #2
				$betAmountValue = 0; // #3
				$operatorBeforeDeposit = 'and'; // #4
				$depositAmountMathSign = '>='; // #5
				$depositAmountValue = 0; // #6
				$operatorBeforeLoss = 'and'; // #7
				$lossAmountMathSign = '<'; // #8
				$lossAmountValue = 0; // #9
				$operatorBeforeWin = 'and'; // #10
				$winAmountMathSign = '>='; // #11
				$winAmountValue = 0; // #12
				break;
			case $isAllMetInFormula:
				// Formula, "bet >= 0 and deposit >= 0 and loss >= 0 and win >= 0"
				$betAmountMathSign = '>='; // #2
				$betAmountValue = 0; // #3
				$operatorBeforeDeposit = 'and'; // #4
				$depositAmountMathSign = '>='; // #5
				$depositAmountValue = 0; // #6
				$operatorBeforeLoss = 'and'; // #7
				$lossAmountMathSign = '>='; // #8
				$lossAmountValue = 0; // #9
				$operatorBeforeWin = 'and'; // #10
				$winAmountMathSign = '>='; // #11
				$winAmountValue = 0; // #12
				break;
			case $isOnlyMetBetOfBetInFormula:
				// Formula, "bet >= 0"
				$betAmountMathSign = '>='; // #2
				$betAmountValue = 0; // #3
				$operatorBeforeDeposit = null; // #4
				$depositAmountMathSign = null; // #5
				$depositAmountValue = null; // #6
				$operatorBeforeLoss = null; // #7
				$lossAmountMathSign = null; // #8
				$lossAmountValue = null; // #9
				$operatorBeforeWin = null; // #10
				$winAmountMathSign = null; // #11
				$winAmountValue = null; // #12
				break;
			case $isOnlyMetBetOfBetDepositInFormula:
				// Formula, "bet >= 0 and deposit < 0 "
				$betAmountMathSign = '>='; // #2
				$betAmountValue = 0; // #3
				$operatorBeforeDeposit = 'and'; // #4
				$depositAmountMathSign = '<'; // #5
				$depositAmountValue = 0; // #6
				$operatorBeforeLoss = null; // #7
				$lossAmountMathSign = null; // #8
				$lossAmountValue = null; // #9
				$operatorBeforeWin = null; // #10
				$winAmountMathSign = null; // #11
				$winAmountValue = null; // #12
				break;


		} // EOF switch(true){

		//  isIsConditionMet and isIsConditionNotMet Only for isDepositUpgradeSetting, isBetUpgradeSetting and isDepositBetUpgradeSetting
		if( $isDepositUpgradeSetting
			|| $isBetUpgradeSetting
			|| $isDepositBetUpgradeSetting
			// NextLevelUpxxx
			|| $isNextLevelUpDepositUpgradeSetting
			|| $isNextLevelUpBetUpgradeSetting
			|| $isNextLevelUpDepositBetUpgradeSetting
		){
			if( $isIsConditionMet ){
				if($betAmountMathSign !== null){
					$betAmountMathSign = '>=';
				}
				if($depositAmountMathSign !== null){
					$depositAmountMathSign = '>=';
				}
				if($lossAmountMathSign !== null){
					$lossAmountMathSign = '>=';
				}
				if($winAmountMathSign !== null){
					$winAmountMathSign = '>=';
				}
			}else if($isIsConditionNotMet){
				if($betAmountMathSign !== null){
					$betAmountMathSign = '<';
				}
				if($depositAmountMathSign !== null){
					$depositAmountMathSign = '<';
				}
				if($lossAmountMathSign !== null){
					$lossAmountMathSign = '<';
				}
				if($winAmountMathSign !== null){
					$winAmountMathSign = '<';
				}
			}
		}


		// $settingName = $theSettingNamePrefix.'.CACB'; // #1
		// $betAmountMathSign = '>='; // #2
		// $betAmountValue = 0; // #3
		// $operatorBeforeDeposit = 'and'; // #4
		// $depositAmountMathSign = '>='; // #5
		// $depositAmountValue = 0; // #6
		// $operatorBeforeLoss = 'and'; // #7
		// $lossAmountMathSign = '>='; // #8
		// $lossAmountValue = 0; // #9
		// $operatorBeforeWin = null; // #10
		// $winAmountMathSign = null; // #11
		// $winAmountValue = null; // #12
		$_accumulation = $accumulation; // #13, from params
		/// handle #13, #14
		// for CA
		if($isCACB || $isCASB ){
			$accumulation = $_accumulation; // #13, from params
			$separate_accumulation_settings = null; // #14
		}
		// for SA
		if($isSACB || $isSASB){
			// separate_accumulation_settings_format with 4 params:bet, win, loss and deposit.
			$separate_accumulation_settings_format = '{"bet_amount": {"accumulation": "%s"}, "win_amount": {"accumulation": "%s"}, "loss_amount": {"accumulation": "%s"}, "deposit_amount": {"accumulation": "%s"}}';
			$separate_accumulation_settings = sprintf($separate_accumulation_settings_format, $_accumulation, $_accumulation, $_accumulation, $_accumulation); // #14
			$accumulation = 0; // override common accumulation
		}

		$theBetAmountSettings = null; // default
		// for CB
		if($isCACB || $isSACB ){
			if( ! empty($betAmountMathSign) ){ // had setup bet setting.
				$theBetAmountSettings = null;
			}
		}
		// for SB
		if($isCASB || $isSASB ){
			if($betAmountMathSign !== null){ // had setup bet setting.
				// game_platform_and_type
				$params = [];
				$params['defaultValue'] = 5334;
				$params['defaultMathSign'] = $betAmountMathSign;
				$gameKeyInfoList= [];
				$gameKeyInfoList['type'] = 'game_platform';
				$gameKeyInfoList['value'] = 0;
				$gameKeyInfoList['math_sign'] = $betAmountMathSign;
				/// game_platform_id
				$gameKeyInfoList['game_platform_id'] = "99999999999999"; // "5674";// MUST BE STRING, as default
				if(! empty($theTestPlayerInfo['game_platform_id']) ){
					$gameKeyInfoList['game_platform_id'] = ''. $theTestPlayerInfo['game_platform_id'];// MUST BE STRING
				}
				$params['GKAMSAVL'][] = $gameKeyInfoList;
				$gameKeyInfoList= [];
				$gameKeyInfoList['type'] = 'game_type';
				$gameKeyInfoList['value'] = 0;
				$gameKeyInfoList['math_sign'] = $betAmountMathSign;
				/// game_type_id
				$gameKeyInfoList['game_type_id'] = "99999999999999"; // "561";// MUST BE STRING, as default
				if(! empty($theTestPlayerInfo['game_type_id']) ){
					$gameKeyInfoList['game_type_id'] = ''. $theTestPlayerInfo['game_type_id'];// MUST BE STRING
				}
				$gameKeyInfoList['precon_logic_flag'] = 'or';
				$params['GKAMSAVL'][] = $gameKeyInfoList;
				$defaultValue = $params['defaultValue'];
				$defaultMathSign = $params['defaultMathSign'];
				$gameKeysAndMathSignAndValueList = $params['GKAMSAVL'];
				$theBetAmountSettings = $this->_prepareBetAmountSettingsOfVipUpgradeSetting($defaultValue, $defaultMathSign, $gameKeysAndMathSignAndValueList);

				$betAmountValue = 0;
			}


		}// EOF if($isCASB || $isSASB ){...

			var_dump(['debugIn4560.will getUpgradeLevelSettingFnWithParams'
				, 'settingName:', $settingName
				, 'betAmountValue:', isset($betAmountValue)? $betAmountValue: '_Undefined_'
				, 'depositAmountValue:', isset($depositAmountValue)? $depositAmountValue: '_Undefined_'
				, 'theCombinedCase:', isset($theCombinedCase)? $theCombinedCase: '_Undefined_'
			]);
		/// @todo for SB, ref. to _prepareBetAmountSettingsOfVipUpgradeSetting() in "admin/application/controllers/cli/testing_ogp21673.php".
// var_dump(['debugIn5160.betAmountValue:', $betAmountValue, $operatorBeforeDeposit, $depositAmountMathSign]);
		$forGrade = $forGrade; // #15, from params
		$getUpgradeLevelSettingFn = $this->getUpgradeLevelSettingFnWithParams(	$settingName // #1
													, $betAmountMathSign // #2, @todo A PHP Error was encountered | Severity: Notice | Message: Undefined variable:betAmountMathSign | Filename: libraries/Utils4testogp.php:4775
													, $betAmountValue // #3, @todo A PHP Error was encountered | Severity: Notice | Message: Undefined variable:betAmountValue | Filename: libraries/Utils4testogp.php:4776
													, $operatorBeforeDeposit // #4, @todo A PHP Error was encountered | Severity: Notice | Message: Undefined variable:operatorBeforeDeposit | Filename: libraries/Utils4testogp.php:4777
													, $depositAmountMathSign // #5, @todo A PHP Error was encountered | Severity: Notice | Message: Undefined variable:depositAmountMathSign | Filename: libraries/Utils4testogp.php:4778
													, $depositAmountValue // #6, @todo A PHP Error was encountered | Severity: Notice | Message: Undefined variable:depositAmountValue | Filename: libraries/Utils4testogp.php:4779
													, $operatorBeforeLoss // #7, @todo A PHP Error was encountered | Severity: Notice | Message: Undefined variable:operatorBeforeLoss | Filename: libraries/Utils4testogp.php:4780
													, $lossAmountMathSign // #8, @todo A PHP Error was encountered | Severity: Notice | Message: Undefined variable:lossAmountMathSign | Filename: libraries/Utils4testogp.php:4781
													, $lossAmountValue // #9, @todo A PHP Error was encountered | Severity: Notice | Message: Undefined variable:lossAmountValue | Filename: libraries/Utils4testogp.php:4782
													, $operatorBeforeWin // #10, @todo A PHP Error was encountered | Severity: Notice | Message: Undefined variable:operatorBeforeWin | Filename: libraries/Utils4testogp.php:4783
													, $winAmountMathSign // #11, @todo A PHP Error was encountered | Severity: Notice | Message: Undefined variable:winAmountMathSign | Filename: libraries/Utils4testogp.php:4784
													, $winAmountValue // #12, @todo A PHP Error was encountered | Severity: Notice | Message: Undefined variable:winAmountValue | Filename: libraries/Utils4testogp.php:4785
													, $accumulation // #13
													, $separate_accumulation_settings // #14
													, $forGrade // #15
													, $theBetAmountSettings // #16 for separate betting setting
													, $testing_ogp // #17
												);



		return [$settingName, $getUpgradeLevelSettingFn];
	}// EOF _getUpgradeLevelSettingFnAndSettingNameFromCombinedCase

	/**
	 * for the setting of upgrade, get/add/update the callable variable with params (by name)
	 *
	 * @param string $settingName The field, "vip_upgrade_setting.setting_name".
	 * @param string $betAmountMathSign The Math Sign of the bet in "vip_upgrade_setting.formula".
	 * @param float|integer $betAmountValue The amount of the bet in "vip_upgrade_setting.formula".
	 * @param string $operatorBeforeDeposit The operator between bet and deposit in "vip_upgrade_setting.formula". ex: "and", "or".
	 * @param string $depositAmountMathSign The Math Sign of the deposit in "vip_upgrade_setting.formula".
	 * @param float|integer $depositAmountValue The amount of the deposit in "vip_upgrade_setting.formula".
	 * @param string $operatorBeforeLoss The operator between deposit and loss in "vip_upgrade_setting.formula". ex: "and", "or".
	 * @param string $lossAmountMathSign The Math Sign of the loss in "vip_upgrade_setting.formula".
	 * @param float|integer $lossAmountValue The amount of the loss in "vip_upgrade_setting.formula".
	 * @param string $operatorBeforeWin The operator between loss and win in "vip_upgrade_setting.formula". ex: "and", "or".
	 * @param string $winAmountMathSign The Math Sign of the win in "vip_upgrade_setting.formula".
	 * @param float|integer $winAmountValue The amount of the win in "vip_upgrade_setting.formula".
	 * @param integer $accumulation The field, "vip_upgrade_setting.accumulation". Only for common accumulation mode.
	 * @param string $separate_accumulation_settings The json string, as the field, "vip_upgrade_setting.separate_accumulation_settings".
	 * @param integer $forGrade The setting for upgrade Or downgrade? If its 1, thats means for upgrade. and If its 3, thats means for downgrade.
	 * @return callable
	 */
	public function getUpgradeLevelSettingFnWithParams( $settingName = 'devUpgradeMet.CACB' // #1
														, $betAmountMathSign = '>=' // #2
														, $betAmountValue = 0 // #3
														, $operatorBeforeDeposit = 'and' // #4
														, $depositAmountMathSign = '>=' // #5
														, $depositAmountValue = 0 // #6
														, $operatorBeforeLoss = 'and' // #7
														, $lossAmountMathSign = '>=' // #8
														, $lossAmountValue = 0 // #9
														, $operatorBeforeWin = null // #10
														, $winAmountMathSign = null // #11
														, $winAmountValue = null // #12
														, $accumulation = 1 // #13
														, $separate_accumulation_settings = NULL // #14
														, $forGrade = 'upgrade' // #15
														, $bet_amount_settings = null // #16 for separate betting settings
														, $testing_ogp = null // #17 for noteTpl, test()
	){

		// 參數 $_this，是在使用的時候給的。這邊只要定義腳本，不需要給實際值。
		$getUpgradeLevelSettingFn = function( $_this ) use ( $settingName
			, $forGrade
			, $betAmountMathSign
			, $betAmountValue
			, $operatorBeforeDeposit
			, $depositAmountMathSign
			, $depositAmountValue
			, $operatorBeforeLoss
			, $lossAmountMathSign
			, $lossAmountValue
			, $operatorBeforeWin
			, $winAmountMathSign
			, $winAmountValue
			, $accumulation
			, $separate_accumulation_settings
			, $bet_amount_settings
			, $testing_ogp
		){
			/// step: preset VipUpgradeSetting in CACB for upgrade success
			// ref. to tryMacroSetupCACBInVipUpgradeSetting()
			// $settingName = $settingName4UpgradeMet;
			$data = [];
			$data['setting_name'] = $settingName;
			$data['description'] = $settingName. '.testing';
			$data['status'] = 1; // always be 1 for active.
			/// 1, 3 : upgrade, downgrade
			$data['level_upgrade'] = 1; // default, upgrade
			if($forGrade == 'upgrade'){
				$data['level_upgrade'] = 1; // 1, 3 : upgrade, downgrade
			}else if($forGrade == 'downgrade'){
				$data['level_upgrade'] = 3;
			}

			// var_dump(['debugIn6378.betAmountMathSign:', $betAmountMathSign, $betAmountValue, $operatorBeforeDeposit]);
			/// CB
			// $betAmountMathSign = '>=';
			// $betAmountValue = 0;
			// $operatorBeforeDeposit = 'and';
			// $depositAmountMathSign = '>=';
			// $depositAmountValue = 0;
			// $operatorBeforeLoss = 'and';
			// $lossAmountMathSign = '>=';
			// $lossAmountValue = 0;
			// $operatorBeforeWin = null;
			// $winAmountMathSign = null;
			// $winAmountValue = null;
			$formula = $this->_prepareFormulaOfVipUpgradeSetting($betAmountMathSign // #1
				, $betAmountValue // #2
				, $operatorBeforeDeposit // #3
				, $depositAmountMathSign // #4
				, $depositAmountValue // #5
				, $operatorBeforeLoss // #6
				, $lossAmountMathSign // #7
				, $lossAmountValue // #8
				, $operatorBeforeWin // #9
				, $winAmountMathSign // #10
				, $winAmountValue // #11
			);
			$data['formula'] = $formula;
			$data['bet_amount_settings'] = $bet_amount_settings; // always be NULL for CB

			/// CA
			$data['accumulation'] = $accumulation ; // 0 / 1 / 4 : No / Yes, Registration Date / Yes, Last Change Period
			$data['separate_accumulation_settings'] = $separate_accumulation_settings; // always be NULL

			$params = [$settingName, $data];
			// $rlt = call_user_func_array([$_this, '_syncUpgradeLevelSettingByName'], $params); // $rlt = $this->_syncUpgradeLevelSettingByName($settingName, $data);
			/// Patch A PHP Error was encountered | Severity: Warning | Message: call_user_func_array
			// () expects parameter 1 to be a valid callback, cannot access private method
			// Testing_ogp24373::_syncUpgradeLevelSettingByName() | Filename: libraries/
			// Utils4testogp.php:4689
			$rlt = call_user_func_array([$this, '_syncUpgradeLevelSettingByName'], $params); // $rlt = $this->_syncUpgradeLevelSettingByName($settingName, $data);
			$formater = '[Step]preset VipUpgradeSetting,"%s" for %s success'; // 2 params, $settingName and $forGrade
			$note = sprintf( $_this->noteTpl
								, sprintf($formater, $settingName, $forGrade) // #1 by sprintf()
								, var_export($params, true) // #2 // #1 by sprintf()
								, var_export($rlt, true) // #3 // #1 by sprintf()
							);

				$theGenerateCallTrace = $this->generateCallTrace();
				var_dump(['debugIn4703.theGenerateCallTrace:', $theGenerateCallTrace, 'settingName:', $settingName]);
			if($testing_ogp !== null){
				$testing_ogp->execTest( true // result
					,  true // expect
					, __METHOD__. ' '. 'Preset vip_upgrade_setting table' // title
					, $note // note
				);
			}

			return [$rlt, $settingName];
		}; // function($settingName, $_this){...
		return $getUpgradeLevelSettingFn;
	} // EOF getUpgradeLevelSettingFnWithParams

	function generateCallTrace(){
        $e = new Exception();
        $trace = explode("\n", $e->getTraceAsString());
        // reverse array to make steps line up chronologically
        $trace = array_reverse($trace);
        array_shift($trace); // remove {main}
        array_pop($trace); // remove call to this method
        $length = count($trace);
        $result = array();

        for ($i = 0; $i < $length; $i++)
        {
            $result[] = ($i + 1)  . ')' . substr($trace[$i], strpos($trace[$i], ' ')); // replace '#someNum' with '$i)', set the right ordering
        }

        return "\t" . implode("\n\t", $result);
    }

	/**
	 * Get the array of the theUpgradeSettingCaseList and theMetInFormulaCaseList collected.
	 *
	 * @param string $prefix If need NextLevelUp prefix for NextLevelUpXXXX keys.
	 * @return void
	 */
	public function getUpgradeSettingAndFormulaRelatedList($prefix = ''){ // NextLevelUp
		$findConditionList = [];

		// for confirm the formula is work
		$findConditionList[] = '.'. $prefix. 'EmptyUpgradeSetting';
		$findConditionList[] = '.'. $prefix. 'BetUpgradeSetting';
		$findConditionList[] = '.'. $prefix. 'DepositUpgradeSetting';
		$findConditionList[] = '.'. $prefix. 'DepositBetUpgradeSetting';
		// for testing script x8
		$findConditionList[] = '.'. $prefix. 'AllNoMetInFormula'; // #1
		$findConditionList[] = '.'. $prefix. 'OnlyMetBetOfAllInFormula'; // #2
		$findConditionList[] = '.'. $prefix. 'OnlyMetDepositOfAllInFormula'; // #3
		$findConditionList[] = '.'. $prefix. 'OnlyMetBetDepositOfAllInFormula'; // #4
		$findConditionList[] = '.'. $prefix. 'OnlyMetBetDepositWinOfAllInFormula'; // #5
		$findConditionList[] = '.'. $prefix. 'AllMetInFormula'; // #6
		$findConditionList[] = '.'. $prefix. 'OnlyMetBetOfBetInFormula'; // #7
		$findConditionList[] = '.'. $prefix. 'OnlyMetBetOfBetDepositInFormula'; // #8

		// the cases(x8-1), for EmptyUpgradeSetting, DepositUpgradeSetting, BetUpgradeSetting and DepositBetUpgradeSetting replaced to the followings,
		$findConditionList[] = '.'. $prefix. 'EmptyInFormula'; // #1
		$findConditionList[] = '.'. $prefix. 'OnlyMetDepositOfDepositInFormula'; // #2
		$findConditionList[] = '.'. $prefix. 'NotMetDepositOfDepositInFormula'; // #3
		$findConditionList[] = '.'. $prefix. 'NotMetBetOfBetInFormula'; // #4
		$findConditionList[] = '.'. $prefix. 'OnlyMetDepositOfBetDepositInFormula'; // #5
		$findConditionList[] = '.'. $prefix. 'NoMetAllOfBetDepositInFormula'; // #6
		$findConditionList[] = '.'. $prefix. 'AllMetOfBetDepositInFormula'; // #7

		return $findConditionList;
	}

	/**
	 * Insert a deposit
	 *
	 * @param integer $playerId
	 * @param integer|float $amount
	 * @param string $created_at
	 * @return integer $saleOrderId The field, "transactions.order_id".
	 */
	public function _insertDeposit($playerId, $amount, $created_at = 'now'){
		// ref. to Payment_management::newDeposit()
		$this->CI->load->library('user_agent');
		$this->CI->load->model(['payment_account', 'player_model', 'sale_order', 'sale_orders_notes', 'sale_orders_timelog']);

		// The source of "account: 69",
		// $data['account_list'] = $this->payment_account->getAllPaymentAccountDetails(array(
		// 	'banktype.bankName' => 'asc',
		// 	'payment_account.payment_account_name' => 'asc',
		// ));
		$paymentKind = Sale_order::PAYMENT_KIND_DEPOSIT;
		$currency = $this->CI->config->item('default_currency'); // ?
		$status = Sale_order::STATUS_SETTLED;// Settled
		$promo_cms_id = 0;
		$subwallet = ''; // Not supported.
		$player = $this->CI->player_model->getPlayerById($playerId);
		$username =  $player->username;
		$bankAccountOwnerName = 'bankAccountOwnerName'. $playerId;
		$account_list = $this->CI->payment_account->getAllPaymentAccountDetails(array(
				'banktype.bankName' => 'asc',
				'payment_account.payment_account_name' => 'asc',
			));
		$payment_account_id = $account_list[0]->id;
		// var_dump($payment_account_id); exit();
		// $payment_account_id = $this->input->post('account');
		$paymentAccount = $this->CI->payment_account->getPaymentAccount($payment_account_id);
		$systemId = $paymentAccount->external_system_id;
		$date = $this->CI->utils->getNowForMysql();
		$actionlogbybankAccountOwnerName = $bankAccountOwnerName." ".sprintf("Manual New Deposit %s to %s", $this->CI->utils->formatCurrencyNoSym($amount), $username);
		$userId = Transactions::ADMIN; // $adminUserId = Transactions::ADMIN;
		$internal_note = 'inserted for testing';
		$external_note = '';
		$show_reason = false;
		$saleOrderId = null; // return

		$lockedKey=null;
		// $locked = $this->lockPlayerBalanceResource($playerId, $lockedKey);
		$locked = $this->CI->utils->lockResourceBy($playerId, Utils::LOCK_ACTION_BALANCE, $lockedKey);
		try {
			if ($locked) {
				$this->CI->payment_account->startTrans();

				/// Payment_management::process_player_promo()
				// $player_promo_id = $this->process_player_promo($playerId, $promo_cms_id, $amount, $subwallet, $error);
				$player_promo_id = null;

				$saleOrderId = $this->CI->sale_order->createSaleOrder($systemId, $playerId, $amount, $paymentKind,
						Sale_order::STATUS_PROCESSING, null, $player_promo_id, $currency, $payment_account_id, $date, null,
						$subwallet);
				$this->CI->player_model->incTotalDepositCount($playerId);
				$success = !empty($saleOrderId);
				if($success){
					$this->CI->sale_orders_notes->add($actionlogbybankAccountOwnerName, Users::SUPER_ADMIN_ID, Sale_orders_notes::ACTION_LOG, $saleOrderId);
					$this->CI->sale_orders_timelog->add($saleOrderId, Sale_orders_timelog::ADMIN_USER, $userId, array('before_status' => Sale_order::STATUS_PROCESSING, 'after_status' => null));
					if (!empty($internal_note)) {
						$this->CI->sale_orders_notes->add($internal_note, $userId, Sale_orders_notes::INTERNAL_NOTE, $saleOrderId);
					}
					if (!empty($external_note)) {
						$this->CI->sale_orders_notes->add($external_note, $userId, Sale_orders_notes::EXTERNAL_NOTE, $saleOrderId);
					}
					if ($status == Sale_order::STATUS_SETTLED) {
						$newDepositActionlogApproved = sprintf("New Deposit is success settled, status => %s", $status);
						$suc = $this->CI->sale_order->approveSaleOrder($saleOrderId, $newDepositActionlogApproved, $show_reason);

						if ($this->CI->utils->isEnabledFeature('show_player_deposit_withdrawal_achieve_threshold')) {
							if ($suc) {
								$this->CI->load->model(['player_dw_achieve_threshold']);
								$this->CI->load->library(['payment_library']);
								$this->CI->payment_library->verify_dw_achieve_threshold_amount($playerId, Player_dw_achieve_threshold::ACHIEVE_THRESHOLD_DEPOSIT);
							}
				        }
						//transfer to subwallet
						$transfer_to = $subwallet;
						if ($this->CI->utils->existsSubWallet($transfer_to)) {
							$transfer_from = Wallet_model::MAIN_WALLET_ID;
							$rlt = $this->CI->utils->transferWallet($playerId, $username, $transfer_from, $transfer_to, $amount, $userId);

							$this->CI->utils->debug_log('transfer to subwallet failed', $playerId);
						}
					}

					// $created_at
					// UPDATE `transactions` SET `created_at` = '2022-02-01 01:11:11' WHERE `id` = '659854';
					$created_atDT = new DateTime($created_at);
					$created_at = $this->CI->utils->formatDateTimeForMysql($created_atDT);
					$sql = "UPDATE `transactions` SET `created_at` = '$created_at' WHERE `order_id` = '$saleOrderId';";
					$this->CI->group_level->runRawUpdateInsertSQL($sql);

				}
			} // EOF if ($locked) {...
			$success = $this->CI->payment_account->endTransWithSucc() && $success;
		} finally {
			// release it
			// $rlt = $this->releasePlayerBalanceResource($playerId, $lockedKey);
			$rlt = $this->CI->utils->releaseResourceBy($playerId, Utils::LOCK_ACTION_BALANCE, $lockedKey);
			$this->CI->utils->debug_log('newDeposit releasePlayerBalance', $playerId, $rlt);
		}
		return $saleOrderId;
	} // EOF _insertDepositByPlayerId

	/**
	 * For revert data, change the state of the transation
	 *
	 * @param integer $saleOrderId The field, "transactions.order_id".
	 * @param integer $status Transactions::APPROVED, Transactions::DECLINED, Transactions::PENDING
	 * @return void
	 */
	function _updeteStatusInTransactions($saleOrderId, $status){
		$this->CI->load->model(['group_level']);
		# STATUS
		// const APPROVED = 1;
		// const DECLINED = 2;
		// const PENDING = 3;

		$sql = "UPDATE `transactions` SET `status` = '$status' WHERE `order_id` = '$saleOrderId';";
		return $this->CI->group_level->runRawUpdateInsertSQL($sql);
	}

	public function _insertBet($playerId, $amount, $game_description_id, $bet_datetime = 'now' ){
		$this->CI->load->model(['group_level', 'game_description_model']);
		$rlt_list = [];

		$nowForMysqlInDateTimeNoSpace = $this->CI->utils->formatDateTimeNoSpaceForMysql(new DateTime());
		$nowForMysql = $this->CI->utils->getNowForMysql();

		$bet_datetimeDT = new DateTime($bet_datetime);
		$bet_datetimeInDate = $this->CI->utils->formatDateForMysql($bet_datetimeDT); // 2021-10-26
		$bet_datetimeInHour = $bet_datetimeDT->format('H');
		$bet_datetimeInDateHour = $this->CI->utils->formatDateHourForMysql($bet_datetimeDT); // 2021102623
		$bet_datetimeInYearMonth = $this->CI->utils->formatYearMonthForMysql($bet_datetimeDT); // 202110


		$game_platform_id = 0;
		$game_type_id = 0;
		if( ! empty($game_description_id) ){
			$gameDescription = $this->CI->game_description_model->getGameDescription($game_description_id);
			$game_platform_id = $gameDescription->game_platform_id;
			$game_type_id = $gameDescription->game_type_id;
		}

		$uniqueidCollect = [];
		$uniqueidCollect[] = $playerId;
		$uniqueidCollect[] = $game_platform_id;
		$uniqueidCollect[] = $game_type_id;
		$uniqueidCollect[] = $game_description_id;
		$uniqueidCollect[] = $bet_datetimeInDate;
		$uniqueidCollect[] = 'testAt';
		$uniqueidCollect[] = $nowForMysqlInDateTimeNoSpace;
		$uniqueid = implode('_', $uniqueidCollect);

		// total_player_game_day
		$sql = <<<EOF
INSERT INTO `total_player_game_day` (`player_id`, `betting_amount`, `date`, `updated_at`, `game_description_id`, `game_platform_id`, `game_type_id`, `uniqueid`, `result_amount`, `win_amount`, `loss_amount`, `real_betting_amount`, `bet_for_cashback`, `update_date_day`, `md5_sum`, `currency_key`)
VALUES ('$playerId'
, '$amount'
, '$bet_datetimeInDate'
, '$nowForMysql'
, '$game_description_id'
, '$game_platform_id'
, '$game_type_id'
, '$uniqueid'
, '-500', '0', '500', '550', '550', NULL, NULL, NULL);
EOF;

		$insert_id = 0;
		$rlt = $this->CI->group_level->runRawUpdateInsertSQL($sql);
		if($rlt){
			$insert_id = $this->CI->group_level->db->insert_id();
		}
		$rlt_list['total_player_game_day'] = $insert_id;

		/// total_player_game_hour
		$sql = <<<EOF
INSERT INTO `total_player_game_hour` (`player_id`, `betting_amount`, `hour`, `date`, `updated_at`, `game_description_id`, `game_platform_id`, `game_type_id`, `uniqueid`, `result_amount`, `date_hour`, `win_amount`, `loss_amount`, `real_betting_amount`, `bet_for_cashback`, `update_date_hour`, `md5_sum`, `currency_key`)
VALUES ('$playerId'
, '$amount'
, '$bet_datetimeInHour'
, '$bet_datetimeInDate'
, '$nowForMysql'
, '$game_description_id'
, '$game_platform_id'
, '$game_type_id'
, '$uniqueid'
, '-1000'
, '$bet_datetimeInDateHour'
, '0', '1000', '3000', '3000', NULL, NULL, NULL);
EOF;

		$insert_id = 0;
		$rlt = $this->CI->group_level->runRawUpdateInsertSQL($sql);
		if($rlt){
			$insert_id = $this->CI->group_level->db->insert_id();
		}
		$rlt_list['total_player_game_hour'] = $insert_id;

		/// total_player_game_month
		$sql = <<<EOF
INSERT INTO `total_player_game_month` (`player_id`, `betting_amount`, `month`, `updated_at`, `game_description_id`, `game_platform_id`, `game_type_id`, `uniqueid`
, `result_amount`, `win_amount`, `loss_amount`, `real_betting_amount`, `bet_for_cashback`, `update_date_month`, `md5_sum`, `currency_key`)
VALUES ('$playerId'
, '$amount'
, '$bet_datetimeInYearMonth'
, '$nowForMysql'
, '$game_description_id'
, '$game_platform_id'
, '$game_type_id'
, '$uniqueid'
, '-1000', '0', '1000', '3000', '3000', NULL, NULL, NULL);
EOF;

		$insert_id = 0;
		$rlt = $this->CI->group_level->runRawUpdateInsertSQL($sql);
		if($rlt){
			$insert_id = $this->CI->group_level->db->insert_id();
		}
		$rlt_list['total_player_game_month'] = $insert_id;

		return $rlt_list;
	} // EOF _insertBet

	/**
	 * Add salt in Bet data by PlayerId and specified P.K.
	 * For revert the Bet data of the test player
	 *
	 * @param integer $playerId
	 * @param array $id_list_total_player_game_tables The specified P.K. in the tables, total_player_game_hour, total_player_game_day and total_player_game_month
	 *
	 * @return array The result of the each total_player_game_XXX table.
	 */
	public function _saltBetByPlayerId($playerId, $id_list_total_player_game_tables = []){
		$this->CI->load->model(['group_level']);
		$rlt_list = [];
		if( ! empty($id_list_total_player_game_tables['total_player_game_hour']) ){
			$id = $id_list_total_player_game_tables['total_player_game_hour'];
			$sql = <<<EOF
UPDATE `total_player_game_hour` SET
`player_id` = CONCAT('99', `player_id`)
, `uniqueid` = CONCAT(`uniqueid`, '_salted')
WHERE `id` = '$id' AND `player_id` = '$playerId';
EOF;
			$rlt_list['total_player_game_hour'] = $this->CI->group_level->runRawUpdateInsertSQL($sql);
		}

		if( ! empty($id_list_total_player_game_tables['total_player_game_day']) ){
			$id = $id_list_total_player_game_tables['total_player_game_day'];
			$sql = <<<EOF
UPDATE `total_player_game_day` SET
`player_id` = CONCAT('99', `player_id`)
, `uniqueid` = CONCAT(`uniqueid`, '_salted')
WHERE `id` = '$id' AND `player_id` = '$playerId';
EOF;
			$rlt_list['total_player_game_day'] = $this->CI->group_level->runRawUpdateInsertSQL($sql);
		}

		if( ! empty($id_list_total_player_game_tables['total_player_game_month']) ){
			$id = $id_list_total_player_game_tables['total_player_game_month'];
			$sql = <<<EOF
UPDATE `total_player_game_month` SET
`player_id` = CONCAT('99', `player_id`)
, `uniqueid` = CONCAT(`uniqueid`, '_salted')
WHERE `id` = '$id' AND `player_id` = '$playerId';
EOF;
			$rlt_list['total_player_game_month'] = $this->CI->group_level->runRawUpdateInsertSQL($sql);
		}
		return $rlt_list;
	}

	/**
	 * @todo Dev the following cases, (OGP-25082 Enable Multiple Level Upgrade in VIP)
	 * 連續升級時，存款會因為曾經滿足/當下累計金額 而升級，但不會超過等級限制的升級。 存款測試。
     *  1. 設計 Lv1, Lv2, Lv3, Lv4 (setupDepositOfFormulaIn1stLv,setupDepositOfFormulaIn2edLv, setupDepositOfFormulaIn3thLv, setupDepositOfFormulaIn4thLv)
     *  deposit >= 10 devTestUpgrade.deposit10.SACB
     *  deposit >= 20 devTestUpgrade.deposit20.SACB
     *  deposit >= 30 devTestUpgrade.deposit30.SACB
     *  N/A
     *  2. 調整玩家等級 到 Lv1
     *  3. 清空該玩家的兩張表記錄： player_accumulated_amounts_log 跟 vip_grade_report
     *  4. 調整 Lv1 升級條件，使其不成功升級，但符合存款： （存款已有 25，但流水故意不滿足）
     *  {"bet_amount":["<",0],"operator_2":"and","deposit_amount":[">=",10]}
     *  5. 依序指令： （ 先檢查 Lv1 升級條件，使其不成功升級，讓表格 player_accumulated_amounts_log 有記錄滿足的項目。）
     *  php public/index.php cli/command/player_level_upgrade_by_playerId "54169_1" "false" "2022-02-14 00:00:00" "0" 2>&1 > /home/vagrant/Code/og/admin/application/logs/tmp_shell/command_player_level_upgrade_by_playerId_02140917.log &
     *  6. 調整恢復 Lv1 升級條件（改成 Bet >= 0），讓滿足流水條件：
     *  {"bet_amount":[">=",0],"operator_2":"and","deposit_amount":[">=",10]}
     *  7. 依序指令：（或指定 2/28 檢查升級）
     *  php public/index.php cli/command/player_level_upgrade_by_playerId "54169_1" "false" "2022-02-21 00:00:03" "0" 2>&1 > /home/vagrant/Code/og/admin/application/logs/tmp_shell/command_player_level_upgrade_by_playerId_02140920.log &
	 *
     *  02/14預備檢查但不升級、 02/28升級檢查 是曾經滿足 而升級。
     *  02/14預備檢查但不升級、 02/21升級檢查 是當下累計 而升級。
	 *
     *  ----
     *  betGTE10AndDepositGTE0OfFormula
     *  betGTE20AndDepositGTE0OfFormulaInNextLv
     *  betGTE30AndDepositGTE0OfFormulaInNext2Lv
	 *
     *  連續升級時，存款會因為曾經滿足/當下累計金額 而升級，但不會超過等級限制的升級。 流水測試。
     *  1. 設計 Lv1, Lv2, Lv3, Lv4 (setupBetOfFormulaIn1stLv,setupBetOfFormulaIn2edLv, setupBetOfFormulaIn3thLv, setupBetOfFormulaIn4thLv)
     *  bet >= 10 devTestUpgrade.betGT10OfBetDeposit.SACB {"bet_amount":[">=",10],"operator_2":"and","deposit_amount":["<",0]}
     *  bet >= 20 devTestUpgrade.betGT20OfBetDeposit.SACB
     *  bet >= 30 devTestUpgrade.betGT30OfBetDeposit.SACB
     *  N/A
     *  流水已有 25 at 2/11, 2/14
     *  2. 調整玩家等級 到 Lv1
     *  3. 清空該玩家的兩張表記錄： player_accumulated_amounts_log 跟 vip_grade_report
     *  4. 調整 Lv1 升級條件，使其不成功升級，但符合流水： （流水已有 25，但存款故意不滿足） betGTE10AndDepositLT0OfFormula
     *  {"bet_amount":[">=",10],"operator_2":"and","deposit_amount":["<",0]}
     *  5. 依序指令： （ 先檢查 Lv1 升級條件，使其不成功升級，讓表格 player_accumulated_amounts_log 有記錄滿足的項目。）
     *  php public/index.php cli/command/player_level_upgrade_by_playerId "54169_1" "false" "2022-02-14 00:00:00" "0" 2>&1 > /home/vagrant/Code/og/admin/application/logs/tmp_shell/command_player_level_upgrade_by_playerId_02151329.log &
     *  6. 調整恢復 Lv1 升級條件（改成 Deposit >= 0），讓滿足存款條件：
     *  {"bet_amount":[">=",10],"operator_2":"and","deposit_amount":[">=",0]}
     *  7. 依序指令：（或指定 2/28 檢查升級）
     *  php public/index.php cli/command/player_level_upgrade_by_playerId "54169_1" "false" "2022-02-21 00:00:03" "0" 2>&1 > /home/vagrant/Code/og/admin/application/logs/tmp_shell/command_player_level_upgrade_by_playerId_02140920.log &
	 *
     *  02/14預備檢查但不升級、 02/28升級檢查 是曾經滿足 而升級。
     *  02/14預備檢查但不升級、 02/21升級檢查 是當下累計 而升級。
	 *
     *  指定 2/28 檢查升級:
     *  php public/index.php cli/command/player_level_upgrade_by_playerId "54169_1" "false" "2022-02-28 00:00:03" "0" 2>&1 > /home/vagrant/Code/og/admin/application/logs/tmp_shell/command_player_level_upgrade_by_playerId_02140921.log &
	 *
     *  SACB.Weekly1PeriodMode.NextLevelUpWeekly1PeriodMode.PeriodIsMet.NextLevelUpPeriodIsMet.OffHourlyCheckUpgradeEnable.AccumulationLastChangePeriodResetIfMet.OnMultipleUpgradeEnable
	 *
     *  betGTE10AndDepositGTE0OfFormula.
     *  betGTE20AndDepositGTE0OfFormulaInNextLv.
     *  betGTE30AndDepositGTE0OfFormulaInNext2Lv.
     *  HadBet25To66746InPreconditionsOn20220211121110
     *  HadBet25To66746InPreconditionsOn20220214121113
     *  ExecUpgradeOn20220214121314InPreconditions
	 *
	 *
	 * @param [type] $theCombinedCase
	 * @return void
	 */
	public function parseCombinedCase4Router($theCombinedCase){


		$segment_list = explode('.', $theCombinedCase);
		$segment_rlt_list = [];
		foreach($segment_list as $segment ){
			// $segment
			$segment_rlt_list[$segment] = $this->doSegmentOfCombined($segment);
		}
	}

	/**
	 * @todo Dev the following cases, (OGP-25082 Enable Multiple Level Upgrade in VIP)
	 */
	public function doSegmentOfCombined($theSegment){

		// HadBet25To66746InPreconditionsOn20220211121110

		$theMetedInPreconditionsCaseList = $this->genMetedInPreconditionsCaseList();


		switch(true){
			case(in_array($theSegment, array_keys($theMetedInPreconditionsCaseList)) ):
				$rlt = call_user_func_array($this, 'handleMetedInPreconditionsCaseList', []);
				break;
		}
		return $rlt;
	}

	public function handleMetedInPreconditionsCaseList($theSegment){

	}
}

////END OF FILE
