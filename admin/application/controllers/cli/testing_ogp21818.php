<?php

require_once dirname(__FILE__) . '/base_testing_ogp.php';


/**
 * The Situation Testing,
 * Execute the downgrade check,  triggered from the cron-job .
 *
 * !!! To set the proptype, isEnableTesting to true, before execute the testing in Local hosting.
 *
 * The URIs,
 *
 * - Display all kinds of combine keyword  in the list,
 * http://admin.og.local/cli/testing_ogp21818/index/displayCaseKindList
 *
 *
 * - Display all combine test cases with the Expected keyword,
 * http://admin.og.local/cli/testing_ogp21818/index/test_getCombinedCaseList/1/1
 *
 * - List the players for the testing at last 180 days and limit 5.
 * http://admin.og.local/cli/testing_ogp21818/index/searchTestPlayerList/180/5
 *
 * - Execute the testing with plauyer_id=5357, limit 3 test cases.
 * http://admin.og.local/cli/testing_ogp21818/index/test_DowngradeFromCronjob/5357/3
 *
 *
 * Execute the testing via cli,
 * <code>
 * vagrant@default_og_livestablemdb-PHP7:~/Code/og/admin$ php public/index.php cli/testing_ogp21818/index/test_DowngradeFromCronjob 5357 3 | w3m -T text/html > ../logs/ogp21818.5357.test_DowngradeFromCronjob.log &
 * </code>
 *
 * To moniter the testing process via cli,
 * <code>
 * vagrant@default_og_livestablemdb-PHP7:~/Code/og/admin$ tail -f ../logs/testing_ogp21818-index.log|grep 'caseTotalAmount'
 * </code>
 * And check the keyword,"caseTotalAmount" and "keyNumber".
 *
 */
class Testing_ogp21818 extends BaseTestingOGP {

	var $isEnableTesting = false; /// !!! Set to true, before execute the testing in Local hosting.

	public function __construct() {
		parent::__construct();
	}

	public function init(){

		$this->appendFixBegin = '//// Begin append by testing_ogp21818 ////';
		$this->appendFixEnd = '//// End append by testing_ogp21818 ////';

		// 3 params $indexKey, $exported_testParams, $exported_rlt
		$this->noteTpl=<<<EOF
<pre>
The Case, %s BEGIN,
The params:
%s
The rlt :
%s
</pre>
EOF;

	} // EOF init

	/**
	 * Display the all kind of case for test
	 * [TESTED] URI,
	 * http://admin.og.local/cli/testing_ogp21818/index/displayCaseKindList
	 *
	 * @return void Display in the browser
	 */
	public function displayCaseKindList(){
		$ary = $this->utils4testogp->_assignCaseKindList4ogp21818();
		$this->utils4testogp->displayCaseKindList($ary);
	} // EOF displayCaseKindList


	// $data = array(
	// 	array('1' => '1', '2' => '2'),
	// 	array('1' => '111', '2' => '222'),
	// 	array('1' => '111111', '2' => '222222'),
	// 	);

	// echo html_table($data);
	/**
	 * Search Test PlayerList
	 * URI,
	 * http://admin.og.local/cli/testing_ogp19825/index/searchTestPlayerList/30/5
	 * //admin.staging.onestop.t1t.in/cli/testing_ogp19825/index/searchTestPlayerList/60/5
	 *
	 * Cli,
	 * php admin/public/index.php cli/testing_ogp19825/searchTestPlayerList 60 5
	 *
	 *
	 * @param string $offsetRange
	 * @param integer $limit
	 * @return void
	 */
	public function searchTestPlayerList($offsetDayRange = '7', $limit = 10){
		$this->load->model(['group_level']);
		// $rows = $this->utils4testogp->_searchTestPlayerList($offsetDayRange, $limit);
		$rows = $this->utils4testogp->_searchTestPlayerListFilteredLowestLevel($offsetDayRange, $limit);

		$counter = count($rows);
		$note = 'Recommand the test player list,';
		$note .= '<pre>';
		$note .= var_export($rows, true);
		$note .= '</pre>';
		$this->test( ! empty($counter) // result
			,  true // expect
			, __METHOD__ // title
			, $note // note
		);
		// $this->returnText('returnText(): '.var_export($note, true));

	}


	/**
	 * [TESTED] URI,
	 * http://admin.og.local/cli/testing_ogp21818/index/searchTestPlayerByPlayerId/5357
	 *
	 * @param integer $thePlayerId
	 * @return void
	 */
	public function searchTestPlayerByPlayerId($thePlayerId){

		// _searchTestPlayerByPlayerId

		$rows = $this->utils4testogp->_searchTestPlayerByPlayerId($thePlayerId);

		$counter = count($rows);
		$note = 'The test player,';
		$note .= '<pre>';
		$note .= var_export($rows, true);
		$note .= '</pre>';
		$this->test( ! empty($counter) // result
			,  true // expect
			, __METHOD__ // title
			, $note // note
		);
	} // EOF searchTestPlayerByPlayerId

	/**
	 * Test the Downgrade Check simulated trigger from cronjob
	 * URI,
	 * http://admin.og.local/cli/testing_ogp21818/index/test_DowngradeFromCronjob/5357/3
	 * Cli,
	 * php public/index.php cli/testing_ogp21818/index/test_DowngradeFromCronjob 5357 3 | w3m -T text/html > ../logs/ogp21818.5357.test_DowngradeFromCronjob.log
	 *
	 * @param array|integer $theTestPlayerInfo If it is array, the array should be return from "utils4testogp::_searchTestPlayerByPlayerId()" or 'utils4testogp::_searchTestPlayerList()'.
	 * If it is numeric,(number string or integer), the integer should be the field, 'player.player_id'.
	 * If it is empty, that's means get the test player from utils4testogp::_searchTestPlayerList().
	 *
	 * @param integer $limitCaseAmount The Combined Case amount limit.default will be all cases.
	 * @return void
	 */
	public function test_DowngradeFromCronjob( $theTestPlayerInfo = [] // # 1
		, $limitCaseAmount = 999999 // # 2
	){
		if( ! $this->isEnableTesting ){
			return false;
		}
		$this->load->model(['group_level']);
		$now = new Datetime();

		if( empty($theTestPlayerInfo) ){
			$offsetDayRange = '180';
			$limit = 5;
			$params = [$offsetDayRange, $limit];
			$rows = call_user_func_array([$this->utils4testogp, '_searchTestPlayerListFilteredLowestLevel'], $params); // $rows = $this->_searchTestPlayerList($offsetDayRange, $limit);
			// var_dump($rows);
			$theTestPlayerInfo = $rows[0]; // for $rows[3] player_id=5357
		}


		$thePlayerId = 0;
		if( ! empty($theTestPlayerInfo)){
			if( is_array($theTestPlayerInfo) ){
				$thePlayerId = $theTestPlayerInfo['player_id'];
			}else if( is_numeric($theTestPlayerInfo) ){
				// this case the param,$theTestPlayerInfo should be thePlayerId.
				$thePlayerId = $theTestPlayerInfo;
				$rows = $this->utils4testogp->_searchTestPlayerByPlayerId($thePlayerId);
				$theTestPlayerInfo = $rows[0]; // reload theTestPlayerInfo for vipsettingcashbackruleId
			}
			$original_vipsettingcashbackruleId = $theTestPlayerInfo['vipsettingcashbackruleId'];
		}

		$_combinedCaseList = [];
		//
		// issue case, CACB.EmptyPeriodMode.PeriodNotMet.OnLevelMaintainEnable.OverMaintainTime.IsMetLevelMaintainCondition.NoAccumulation.ogp21818
		// $_combinedCaseList[] = 'CACB.DailyPeriodMode.PeriodIsMet.OffLevelMaintainEnable.InMaintainTime.IsMetLevelMaintainCondition.AccumulationYesRegistrationDate.EmptyUpgradeSetting'; // Patched in _getTestConditionFnFromCombinedCase4ogp21818().
		//                         // CACB.DailyPeriodMode.PeriodIsMet.OnLevelMaintainEnable.InMaintainTime.IsMetLevelMaintainCondition.AccumulationYesRegistrationDate.EmptyUpgradeSetting // Patched in test_getCombinedCaseList
		// $_combinedCaseList[] = 'CACB.DailyPeriodMode.PeriodIsMet.OffLevelMaintainEnable.InMaintainTime.IsMetLevelMaintainCondition.AccumulationYesRegistrationDate.DepositUpgradeSetting'; // Patched in _getTestConditionFnFromCombinedCase4ogp21818().
		// $_combinedCaseList[] = 'CACB.DailyPeriodMode.PeriodIsMet.OnLevelMaintainEnable.OverMaintainTime.IsMetLevelMaintainCondition.AccumulationYesRegistrationDate.EmptyUpgradeSetting.IsDowngradeConditionMet'; // patch in added the case,"if( $isEmptyUpgradeSetting){..." in _tryDowngradeSuccessTriggerFromCronjobV2()
			                    // CACB.DailyPeriodMode.PeriodIsMet.OnLevelMaintainEnable.OverMaintainTime.IsMetLevelMaintainCondition.AccumulationYesRegistrationDate.EmptyUpgradeSetting.IsDowngradeConditionMet
								//    CACB.DailyPeriodMode.PeriodIsMet.OnLevelMaintainEnable.OverMaintainTime.IsMetLevelMaintainCondition.AccumulationYesRegistrationDate.EmptyUpgradeSetting.IsDowngradeConditionMet
								// CACB.DailyPeriodMode.PeriodIsMet.OnLevelMaintainEnable.OverMaintainTime.IsMetLevelMaintainCondition.AccumulationYesRegistrationDate.EmptyUpgradeSetting.IsDowngradeConditionMet
		// $_combinedCaseList[] = 'CACB.DailyPeriodMode.PeriodNotMet.OnLevelMaintainEnable.InMaintainTime.IsMetLevelMaintainCondition.AccumulationYesRegistrationDate.EmptyUpgradeSetting.IsDowngradeConditionMet';
			// DailyPeriodMode.PeriodNotMet.?... patched in add ignore list
		// $_combinedCaseList[] = 'CACB.DailyPeriodMode.PeriodIsMet.OnLevelMaintainEnable.InMaintainTime.NotMetLevelMaintainCondition.AccumulationYesRegistrationDate.EmptyUpgradeSetting.IsDowngradeConditionNotMet';
		//
		$doOutputHtml = false;
		$theCombinedCaseList = $this->test_getCombinedCaseList($doOutputHtml);
		$_combinedCaseList = array_slice($theCombinedCaseList, 0, $limitCaseAmount);
		// $_combinedCaseList = array_slice($theCombinedCaseList, 0, 10);
		//

		$caseTotalAmount = count($_combinedCaseList);
		foreach($_combinedCaseList as $keyNumber => $currCase){
			/// downgrade for pre-setup env.
			$theCombinedCase = $currCase;
			$this->utils->debug_log('143.theCombinedCase:', $theCombinedCase, 'caseTotalAmount:', $caseTotalAmount, 'keyNumber:', $keyNumber);

			list($isSA, $isSB) = $this->utils4testogp->_getIsSAIsSBFromCombinedCase($theCombinedCase);

			// EmptyUpgradeSetting, BetUpgradeSetting,...
			// list($settingName, $getUpgradeLevelSettingFn) = $this->utils4testogp->_getUpgradeLevelSettingFnAndSettingNameFromCombinedCaseAndTestPlayerInfo($theCombinedCase, $theTestPlayerInfo);
			list($settingName, $getUpgradeLevelSettingFn) = $this->utils4testogp->_getUpgradeLevelSettingFnAndSettingNameFromCombinedCase($theCombinedCase, $theTestPlayerInfo);



			list($periodMode, $periodValue, $TEB_DateTime, $enableLevelMaintainFn) = $this->_getPeriodModeAndPeriodValueAndTEB_DateTimeAndEnableLevelMaintainFnFromCombinedCase($thePlayerId, $theCombinedCase, $settingName);

			$testConditionFn = $this->utils4testogp->_getTestConditionFnFromCombinedCase4ogp21818($theCombinedCase); // '_testConditionFn4beforeDiffAfter'; // _testConditionFn4beforeDiffAfter, _testConditionFn4beforeSameAsAfter

			$rows = $this->utils4testogp->_searchTestPlayerByPlayerId($thePlayerId);
			$theTestPlayerInfo = $rows[0]; // reload theTestPlayerInfo for vipsettingcashbackruleId

			$params = [ $theTestPlayerInfo // # 1
				, $periodMode // # 2
				, $periodValue // # 3
				, $TEB_DateTime // # 4
				, $testConditionFn // # 5 is_string for function name
				, $enableLevelMaintainFn // # 6 Level Maintain / Downgrade Guaranteed
				, $getUpgradeLevelSettingFn // # 7
				, $isSA // isSA = false mean CA isAccumulationSeparatedInConfig
				, $isSB // isSB = false mean CB isBettingSeparatedInConfig
				, $theCombinedCase
			];
			$funcName = '_tryDowngradeSuccessTriggerFromCronjobV2';
			$rlt = call_user_func_array([$this, $funcName], $params);
			/// ====
			// $hasDowngradedLastTime = null;
			// $reloadPlayerLevel = $this->player->getPlayerCurrentLevel($thePlayerId);
			// $rows = $this->utils4testogp->_searchTestPlayerByPlayerId($thePlayerId);
			// $theTestPlayerInfo = $rows[0]; // reload theTestPlayerInfo for vipsettingcashbackruleId
			//
			// $afterId = $reloadPlayerLevel[0]['vipsettingcashbackruleId'];
			// if($original_vipsettingcashbackruleId != $afterId){
			// 	$hasDowngradedLastTime = true;
			// }else{
			// 	$hasDowngradedLastTime = false;
			// }
			// $this->utils->debug_log('2024.hasDowngradedLastTime', $hasDowngradedLastTime);
			// if($hasDowngradedLastTime){
			// 	/// Test Downgraded by Level Maintain after last downgraded.
			// 	// OffLevelMaintainEnable => OnLevelMaintainEnable
			// 	// InMaintainTime => OverMaintainTime
			// 	// NotMetLevelMaintainCondition => IsMetLevelMaintainCondition
			//
			// 	$currCase = str_replace('OffLevelMaintainEnable', 'OnLevelMaintainEnable', $currCase);
			// 	$currCase = str_replace('InMaintainTime', 'OverMaintainTime', $currCase);
			// 	$currCase = str_replace('NotMetLevelMaintainCondition', 'IsMetLevelMaintainCondition', $currCase);
			// 	$theCombinedCase = $currCase;
			//
			// 	list($isSA, $isSB) = $this->utils4testogp->_getIsSAIsSBFromCombinedCase($theCombinedCase);
			//
			// 	// // ref. to _getDowngradeLevelSettingFn
			// 	// $getUpgradeLevelSettingFn = '_getDowngradeLevelSettingFn'; // so far, CACB
			// 	list($settingName, $getUpgradeLevelSettingFn) = $this->utils4testogp->_getUpgradeLevelSettingFnAndSettingNameFromCombinedCase($theCombinedCase);
			//
			// 	list($periodMode, $periodValue, $TEB_DateTime, $enableLevelMaintainFn) = $this->_getPeriodModeAndPeriodValueAndTEB_DateTimeAndEnableLevelMaintainFnFromCombinedCase($thePlayerId, $theCombinedCase, $settingName);
			//
			// 	$testConditionFn = $this->utils4testogp->_getTestConditionFnFromCombinedCase($theCombinedCase); // '_testConditionFn4beforeDiffAfter'; // _testConditionFn4beforeDiffAfter, _testConditionFn4beforeSameAsAfter
			//
			// 	$params = [ $theTestPlayerInfo // # 1
			// 		, $periodMode // # 2
			// 		, $periodValue // # 3
			// 		, $TEB_DateTime // # 4
			// 		, $testConditionFn // # 5 is_string for function name
			// 		, $enableLevelMaintainFn // # 6 Level Maintain / Downgrade Guaranteed
			// 		, $getUpgradeLevelSettingFn // # 7
			// 		, $isSA // isSA = false mean CA isAccumulationSeparatedInConfig
			// 		, $isSB // isSB = false mean CB isBettingSeparatedInConfig
			// 		, $theCombinedCase
			// 	];
			// 	$funcName = '_tryDowngradeSuccessTriggerFromCronjobV2';
			// 	$rlt = call_user_func_array([$this, $funcName], $params); // $rlt = $this->_tryUpgradeSuccessTriggerFromCronjob($theTestPlayerInfo,...
			//
			// } // EOF if($hasDowngradedLastTime){...
			/// ====
			// clear from yesterday.
			$playerId = $theTestPlayerInfo['player_id'];
			$now->modify('- 1 day');
			$nowYmdHis = $now->format('Y-m-d H:i:s');
			$this->try_revertThisCaseData($playerId, $original_vipsettingcashbackruleId, $nowYmdHis, $this->appendFixBegin, $this->appendFixEnd);


		} // EOF foreach($_combinedCaseList as $keyNumber => $currCase){...

	}// EOF test_DowngradeFromCronjob

	public function try_revertThisCaseData($playerId, $original_vipsettingcashbackruleId, $nowYmdHis){
		$note = $this->utils4testogp->try_revertThisCaseData($playerId, $original_vipsettingcashbackruleId, $nowYmdHis, $this->appendFixBegin, $this->appendFixEnd);
		return $this->test( true // ! empty($vipsettingcashbackruleId) // result
				,  true // expect
				, __METHOD__ // title
				, $note // note
		);
	}// EOF try_revertThisCaseData

	/**
	 * The Common Downgrade Check Testing Script
	 *
	 * @param array $theTestPlayerInfo
	 * @param string $periodMode
	 * @param integer|string $periodValue
	 * @param string $TEB_DateTime
	 * @param script|string|integer $testConditionFn To test the result Is Expected or Not.
	 * If it's Zero, will applied in _testConditionFn4beforeDiffAfterV2() that's means the level will be downgraded.
	 * If it's 1, will applied in _testConditionFn4beforeSameAsAfterV2() that's means the level will be Keep current, No downgraded.
	 * If it's is a callable function, "script" , it will be executed with the params, "$origId, $afterId".
	 * If it's is a String, that's means will executed the function name of this,and with the params, "$origId, $afterId".
	 * @param script|string|integer $enableLevelMaintainFn To switch the features,"Level Maintain" and "Downgrade Guaranteed".
	 * If it's is a callable function, "script", that's used by the "Level Maintain" settings to pre-setup.
	 * If it's is a String, that's means will executed the function name of this,and with the param, this.
	 * If it's 1, that's will execute the function,"_getEnableLevelMaintainInPlayerCurrentLevelFn" and with the default params.
	 * If it's Zero, that's will switch to the feature,"Downgrade Guaranteed".
	 * @param script|string $getUpgradeLevelSettingFn To setup the upgrade/downgrade setting and hook the test player current level.
	 * If it's Zero, that's will execute the function,"_getDowngradeLevelSettingFn"
	 * @param [type] $isAccumulationSeparatedInConfig
	 * @param [type] $isBettingSeparatedInConfig
	 * @return void
	 */
	public function _tryDowngradeSuccessTriggerFromCronjobV2( $theTestPlayerInfo = [] // # 1
		, $periodMode = 'monthly' // # 2
		, $periodValue = 1 // # 3
		, $TEB_DateTime = 'now' // # 4
		, $testConditionFn = 0 // # 5 is_string for function name
		, $enableLevelMaintainFn = 1 // # 6 Level Maintain / Downgrade Guaranteed
		, $getUpgradeLevelSettingFn = 0 // # 7
		, $isAccumulationSeparatedInConfig = 0  // # 8 // CA
		, $isBettingSeparatedInConfig = 0  // # 9 // CB
		, $theCombinedCase = '' // # 10 // for trace test case
	){
		$this->init();

		$isEmptyUpgradeSetting = $this->utils4testogp->_pos($theCombinedCase, 'EmptyUpgradeSetting');

		if( is_string($TEB_DateTime) ){
			//  string convert to DateTime
			$TEB_DateTime = new DateTime($TEB_DateTime);
		}
		$time_exec_begin = $this->utils->formatDateTimeForMysql($TEB_DateTime);

		if(empty($theTestPlayerInfo) ){ // empty for search the test player.
			$offsetDayRange = '180';
			$limit = 5;
			$params = [$offsetDayRange, $limit];
			$rows = call_user_func_array([$this->utils4testogp, '_searchTestPlayerList'], $params); // $rows = $this->_searchTestPlayerList($offsetDayRange, $limit);
			// $rows[0] = array (
			// 	'id' => '72885716',
			// 	'player_id' => '5357',
			// 	'player_username' => 'yiyusheng ',
			// 	'game_platform_id' => '5674',
			// 	'game_type_id' => '408',
			// 	'game_description_id' => '509223',
			// 	'start_at' => '2020-10-13 16:24:38',
			// 	'groupName' => 'OGP-18415',
			// 	'levelName' => 'Level Name 7',
			//  'vipsettingcashbackruleId' => '236',
			// 	'game_name' => 'Unknown',
			// 	'game_type' => 'Unknown',
			// 	'game_platform' => 'EVO马彩',
			// ),
			if( ! empty($rows) ){
				$theTestPlayerInfo = $rows[0];
			}
			$counter = count($rows);
			$note = sprintf($this->noteTpl, 'To get the Test player List ', var_export($rows, true), var_export($theTestPlayerInfo, true));
			$this->test( !empty($counter) // result
					,  true // expect
					, __METHOD__ // title
					, $note // note
				);
		}else{
			// or directily assign the test player.
			$note = sprintf($this->noteTpl, 'Assign the test player', '', var_export($theTestPlayerInfo, true));
			$this->test( !empty($theTestPlayerInfo) // result
					,  true // expect
					, __METHOD__ // title
					, $note // note
				);
		}

		$origId = null; // vipsettingcashbackruleId
		if( ! empty( $theTestPlayerInfo ) ){
			$origId = $theTestPlayerInfo['vipsettingcashbackruleId']; // for revert

			/// devMacro 設定共同累計共同投注
			$isAccumulationSeparated = false; // for SA
			if( ! empty( $isAccumulationSeparatedInConfig ) ){
				$isAccumulationSeparated = $isAccumulationSeparatedInConfig;
			}
			$isBettingSeparated = false; // for SB
			if( ! empty( $isBettingSeparatedInConfig) ){
				$isBettingSeparated = $isBettingSeparatedInConfig;
			}
			$rlt = $this->utils4testogp->_preSetupAccumulationAndBettingTo($isAccumulationSeparated, $isBettingSeparated, $this->appendFixBegin, $this->appendFixEnd);

			// devMacro. Enable level_maintain feature Or use Downgrade Guaranteed feature.
			$featureName = 'vip_level_maintain_settings';
			$value = 1;
			if( empty($enableLevelMaintainFn) ){
				$value = 0;
			}
			$rlt = $this->utils4testogp->_preSetupSystemFeatureTo($featureName, $value);
		}

		if( ! empty( $theTestPlayerInfo ) ){
			if( empty($getUpgradeLevelSettingFn) ){
				$getUpgradeLevelSettingFn = '_getDowngradeLevelSettingFn';
			}

			$settingName = 'devDowngradeMet.CACB';
			$forGrade = 'downgrade';
			if( gettype($getUpgradeLevelSettingFn) == 'string'){
				// $params = [$settingName, $forGrade];
				// list($rlt, $settingName) = call_user_func_array([$this, $getUpgradeLevelSettingFn], $params); // $rlt = $this->_getDowngradeLevelSettingFn(...
				$params = [$this];
				list($rlt, $settingName) = call_user_func_array([$this, $getUpgradeLevelSettingFn], $params); // $rlt = $this->_getDowngradeLevelSettingFn(...
			}else if( gettype($getUpgradeLevelSettingFn) == 'object' ) { // Custom function
				list($rlt, $settingName) = $getUpgradeLevelSettingFn($this);// ($settingName, $forGrade);
				// list($rlt, $settingName) = $getUpgradeLevelSettingFn($settingName, $forGrade);
			}

		}

		if( ! empty( $theTestPlayerInfo ) ){
			/// step: load setting for update in player current level,  theSeparateAccumulationSettings
			$theVipUpgradeSetting = null;
			$theVipUpgradeSettingList = $this->utils4testogp->_getVip_upgrade_settingListBySettingName($settingName);
			if( ! empty($theVipUpgradeSettingList) ){
				$theVipUpgradeSetting = $theVipUpgradeSettingList[0];
				// $theSeparateAccumulationSettings = $this->utils->json_decode_handleErr($theVipUpgradeSetting['separate_accumulation_settings'], true);
				// $theFormula = $this->utils->json_decode_handleErr($theVipUpgradeSetting['formula'], true);
			}
			$note = sprintf($this->noteTpl, '[Step] load setting for update in player current level,  theSeparateAccumulationSettings', var_export($settingName, true), var_export($theVipUpgradeSetting, true) );
			$this->test( ! empty($theVipUpgradeSetting) // result
				,  true // expect
				, __METHOD__. ' '. 'Load setting for downdate' // title
				, $note // note
			);
		}

		$thePlayerCurrentLevel = null;
		$currentvipsettingcashbackruleId = null;
		if( ! empty($theTestPlayerInfo) ){
			// load the player current level.
			// ref. to _updateUpgradeIdInVipsettingcashbackrule()
			$thePlayerId = $theTestPlayerInfo['player_id'];
			$thePlayerCurrentLevel = $this->player->getPlayerCurrentLevel($thePlayerId);
			$thePlayerCurrentLevel = $thePlayerCurrentLevel[0];
			$currentvipsettingcashbackruleId = $thePlayerCurrentLevel['vipsettingcashbackruleId'];

			// $vipLevel = $thePlayerCurrentLevel['vipLevel'];
			// $nextVipsettingcashbackruleId = $this->group_level->getVipLevelIdByLevelNumber($thePlayerCurrentLevel['vipSettingId'], $vipLevel+ 1);
		}

		if( ! empty( $thePlayerCurrentLevel ) ){

			if( $isEmptyUpgradeSetting){
				$vipsettingcashbackruleId = $currentvipsettingcashbackruleId;
				$upgrade_id = 0;
				$targetField='vip_downgrade_id'; // for downgrade
				$params = [$upgrade_id, $vipsettingcashbackruleId, $targetField, $this->isEnableTesting];
				$rlt = call_user_func_array([$this->utils4testogp, '_updateUpgradeIdInVipsettingcashbackrule'], $params); // $rlt = $this->_updateUpgradeIdInVipsettingcashbackrule($upgrade_id, $vipsettingcashbackruleId, $targetField);
				$note = sprintf($this->noteTpl, '[Step] clear the downgrade setting on the player current level', var_export($params, true), var_export($rlt, true) );
				$this->test( true // ! empty($vipsettingcashbackruleId) // result
					,  true // expect
					, __METHOD__. ' '. 'Update the period on the player current level' // title
					, $note // note
				);
			}else if( ! empty($theVipUpgradeSetting) ){
				// update the setting into the player current level.
				// ref. to _updateUpgradeIdInVipsettingcashbackrule()
				$vipsettingcashbackruleId = $currentvipsettingcashbackruleId;
				$upgrade_id = $theVipUpgradeSetting['upgrade_id'];
				$targetField='vip_downgrade_id'; // for downgrade
				$params = [$upgrade_id, $vipsettingcashbackruleId, $targetField, $this->isEnableTesting];
				$rlt = call_user_func_array([$this->utils4testogp, '_updateUpgradeIdInVipsettingcashbackrule'], $params); // $rlt = $this->_updateUpgradeIdInVipsettingcashbackrule($upgrade_id, $vipsettingcashbackruleId, $targetField);

				$note = sprintf($this->noteTpl, '[Step] hook the downgrade setting into the player current level', var_export($params, true), var_export($rlt, true) );
				$this->test( true // ! empty($vipsettingcashbackruleId) // result
					,  true // expect
					, __METHOD__. ' '. 'Hook setting into the player current level' // title
					, $note // note
				);
			}

			if( ! empty($theVipUpgradeSetting) ){
				// Update the period on the player current level
				$gradeMode = 'downgrade';
				$_periodMode = $periodMode;
				$_periodValue = $periodValue;
				$isHourly = false; // always be false in  downgrade check
				$extraData = []; // For Level Maintain of downgrade.
				$theJson = $this->utils4testogp->_getPeriodJson($_periodMode, $_periodValue, $isHourly, $extraData);
				$params = [$thePlayerId, $gradeMode, $theJson, $this->isEnableTesting];
				$rlt = call_user_func_array([$this->utils4testogp, '_preSetupPeriodInPlayerCurrentLevel'], $params);// $rlt = $this->_preSetupPeriodInPlayerCurrentLevel($thePlayerId, $gradeMode, $theJson);
				$note = sprintf($this->noteTpl, '[Step] Update the period on the player current level', var_export($params, true), var_export($rlt, true) );
				$this->test( true // ! empty($vipsettingcashbackruleId) // result
					,  true // expect
					, __METHOD__. ' '. 'Update the period on the player current level' // title
					, $note // note
				);
			} // EOF if( ! empty($theVipUpgradeSetting) ){...

			$isNull4enableLevelMaintain = null;
			if( is_null($enableLevelMaintainFn) ){
				$isNull4enableLevelMaintain = true;
			}else{
				$isNull4enableLevelMaintain = false;
			}

			if($isNull4enableLevelMaintain){
				/// @todo load the fields,period_up_down_2 and period_down of the table vipsettingcashbackrule.
				// for keep the settings, if neet ignore seupt the Level Maintain settings.
			}

			if( ! empty($theVipUpgradeSetting) ){
				if( ! empty($enableLevelMaintainFn) ){

					$params = [$thePlayerId]; // default
					if( gettype($enableLevelMaintainFn) != 'object'
						&& $enableLevelMaintainFn == 1
					){ // use buildin
						$_playerId = $thePlayerId;
						$enableBool = true;
						$theUnit = 1;
						$theTimeLength = 2;
						$theConditionDepositAmount = 5;
						$theConditionBetAmount = 6;
						$params = [$_playerId, $enableBool, $theUnit, $theTimeLength, $theConditionDepositAmount, $theConditionBetAmount]; // overide
						$enableLevelMaintainFn = '_getEnableLevelMaintainInPlayerCurrentLevelFn';
					}

					if( gettype($enableLevelMaintainFn) == 'string' ) { // Specified call local function
						$rlt = call_user_func_array([$this, $enableLevelMaintainFn], $params); // $rlt = $this->_syncUpgradeLevelSettingByName($settingName, $data);
					}else if( gettype($enableLevelMaintainFn) == 'object' ) { // Custom function
						$rlt = $enableLevelMaintainFn($thePlayerId);
					}

				}
			} // EOF if( ! empty($theVipUpgradeSetting) ){...

			if( ! empty($theVipUpgradeSetting) ){
				if( $isNull4enableLevelMaintain ){
					// ignore setup the settings
					// @todo ignore the Level Maintain setting. The function need load the Level Maintain settings and upadte into period_up_down_2/period_down for keep the settings.
				}else if( empty($enableLevelMaintainFn) ){ // enable Downgrade Guaranteed
					// Setup Downgrade Guaranteed settings.
					$period_number = 1; // vipsettingcashbackrule.guaranteed_downgrade_period_number
					$period_total_deposit = 2; // vipsettingcashbackrule.guaranteed_downgrade_period_total_deposit
				}else{
					// Setup Downgrade Guaranteed settings.
					$period_number = 1; // vipsettingcashbackrule.guaranteed_downgrade_period_number
					$period_total_deposit = 0; // vipsettingcashbackrule.guaranteed_downgrade_period_total_deposit
				}
				if( ! $isNull4enableLevelMaintain ){
					$params = [$thePlayerId, $period_number, $period_total_deposit, $this->isEnableTesting];
					$rlt = call_user_func_array([$this->utils4testogp, '_preSetupGuaranteedDowngradeInPlayerCurrentLevel'], $params);// $rlt = $this->_preSetupGuaranteedDowngradeInPlayerCurrentLevel(...

					$note = sprintf($this->noteTpl, '[Step] Update the Downgrade Guaranteed settings on the player current level', var_export($params, true), var_export($rlt, true) );
					$this->test( true // ! empty($vipsettingcashbackruleId) // result
						,  true // expect
						, __METHOD__. ' '. 'Update the Downgrade Guaranteed settings on the player current level' // title
						, $note // note
					);
				}

			}


		} // EOF if( ! empty( $thePlayerCurrentLevel ) ){...


		if( ! empty( $theTestPlayerInfo ) ){
			/// step: trigger upgrade check by cron/sbe
			// ref. to player_level_downgrade_by_playerId()

			$is_blocked = false;
			$params = [];
			$params[] = $theTestPlayerInfo['player_id']. '_0'; // $playerId = null
			$params[] = 0; // $manual_batch = true
			$params[] = $time_exec_begin; // $time_exec_begin = null
			// $playerId = null, $manual_batch = true, $time_exec_begin = null
			$func = 'player_level_downgrade_by_playerId';
			$cmd = $this->utils->generateCommandLine($func, $params, $is_blocked);
			$return_var = $this->utils->runCmd($cmd);

			$note = sprintf($this->noteTpl, 'To trigger downgrade check by cron via command,"'.var_export($cmd,true).'". ', var_export($params,true), var_export($return_var,true) );
			$this->test( true // result
				,  true // expect
				, __METHOD__ // title
				, $note // note
			);

		}

		if( ! empty( $theTestPlayerInfo ) ){

			$idleTotalSec = 4; // use isOverWaitingTimeWithWaitingByPS() for detect ps BUT Not work.
			$this->utils->debug_log('xxx.idleTotalSec',$this->oghome);
			$this->utils->idleSec($idleTotalSec);
			$this->utils->debug_log('yyy.idleTotalSec',$this->oghome);
			$isExecingCB = null;
			$funcList = [];
			$funcList[] = $func;
			$maxWaitingTimes = 30;
			$waitingSec = 1;
			$isOverWaitingTime = $this->utils->isOverWaitingTimeWithWaitingByPS($funcList, $isExecingCB, $maxWaitingTimes, $waitingSec. $this->oghome);
			if ( ! $isOverWaitingTime ) {
			}
			$this->utils->debug_log('zzz.idleTotalSec',$this->oghome);

			$reloadPlayerLevel = $this->player->getPlayerCurrentLevel($theTestPlayerInfo['player_id']);
			$afterId = $reloadPlayerLevel[0]['vipsettingcashbackruleId'];

			// Check pe-setup in log file
			$funcStr = 'job_player_level_downgrade_by_playerId';
			$theLogFileList = $this->utils4testogp->getLogFileListViaCmd($cmd, $funcStr);
			$this->utils->debug_log('2462.theLogFileList',$theLogFileList);
// var_dump($theLogFileList);
			if( ! empty($theLogFileList) ){
				$theLogPathFilename = $theLogFileList[0];
				$logFileContents = $this->utils4testogp->util_readFile($theLogPathFilename);
				// $theCombinedCase
				$this->try_MaintainTimeOfLogFilenameWithFileContents($theLogPathFilename, $logFileContents, $theCombinedCase);
				/// @todo check the log for each combine with $cmd, https://regex101.com/r/jSqOcJ/1
				// InMaintainTime/ OnLevelMaintainEnable
				// isSufficient4RequiredDatetimeRange diffInSeconds
				// {"message":"isSufficient4RequiredDatetimeRange diffInSeconds","context":[74559,"2021-04-02 15:15:36~2021-04-01 18:32:57","diffInSeconds4Required",1209600,"2021-04-02 15:15:36~2021-03-19 15:15:36"],"level":100,"level_name":"DEBUG","channel":"default-og","datetime":"2021-04-07T15:15:40+08:00","trace":"../../models/group_level.php:7607@isSufficient4RequiredDatetimeRange > ../../models/group_level.php:7723@calcDatetimeRangeAndPreviousFromDatetime > ","extra":{"tags":{"request_id":"4b4833a13687d007b262257d2e9433f4","env":"live.og_local","version":"6.112.01.001","hostname":"default-og"},"process_id":17806,"memory_peak_usage":"34.25 MB","memory_usage":"32.25 MB"}}

				// OnLevelMaintainEnable / OffLevelMaintainEnable
				// enableDownMaintain
				// {"message":"downgrade 3710 isConditionMet","context":[true,"playerId:","5357","enableDownMaintain:",true,"isMet4DownMaintain:",false,"schedule:",{"enableDownMaintain":true,"downMaintainUnit":1,"downMaintainTimeLength":14,"downMaintainConditionDepositAmount":5,"downMaintainConditionBetAmount":6}],"level":100,"level_name":"DEBUG","channel":"default-og","datetime":"2021-04-07T15:15:40+08:00","trace":"../../models/group_level.php:2433@playerLevelAdjustDowngrade > ../../controllers/modules/lock_app_module.php:105@{closure} > ","extra":{"tags":{"request_id":"4b4833a13687d007b262257d2e9433f4","env":"live.og_local","version":"6.112.01.001","hostname":"default-og"},"process_id":17806,"memory_peak_usage":"34.25 MB","memory_usage":"32.25 MB"}}

				$this->try_PeriodIsMetOrNotOfLogFilenameWithFileContents($theLogPathFilename, $logFileContents, $theCombinedCase);
				// DailyPeriodMode / Weekly1PeriodMode / Monthly5PeriodMode / PeriodIsMet / PeriodNotMet
				// DailyPeriodMode - periodType, baseTime
				// {"message":"OGP-20868.getScheduleDateRange.currentDate:","context":["2021-04-06 14:38:15 000000","subNumber:",1,"time_exec_begin:","2021-04-06 14:38:15","adjustGradeTo:","down","schedule:",{"daily":"00:00:00 - 23:59:59","enableDownMaintain":true,"downMaintainUnit":1,"downMaintainTimeLength":13,"downMaintainConditionDepositAmount":999999999,"downMaintainConditionBetAmount":999999999}],"level":100,"level_name":"DEBUG","channel":"default-og","datetime":"2021-04-07T15:16:59+08:00","trace":"../../models/group_level.php:4268@getScheduleDateRange > ../../models/group_level.php:2433@playerLevelAdjustDowngrade > ","extra":{"tags":{"request_id":"3ffec018fe18657b5cd4b5c7e6458adb","env":"live.og_local","version":"6.112.01.001","hostname":"default-og"},"process_id":18016,"memory_peak_usage":"34.25 MB","memory_usage":"32.25 MB"}}
				//
				// Weekly1PeriodMode - periodType, baseTime
				// {"message":"OGP-20868.getScheduleDateRange.currentDate:","context":["2021-04-05 00:00:00 000000","subNumber:",1,"time_exec_begin:","2021-04-05 00:00:00","adjustGradeTo:","down","schedule:",{"weekly":1,"enableDownMaintain":true,"downMaintainUnit":1,"downMaintainTimeLength":13,"downMaintainConditionDepositAmount":999999999,"downMaintainConditionBetAmount":999999999}],"level":100,"level_name":"DEBUG","channel":"default-og","datetime":"2021-04-07T15:19:41+08:00","trace":"../../models/group_level.php:4268@getScheduleDateRange > ../../models/group_level.php:2433@playerLevelAdjustDowngrade > ","extra":{"tags":{"request_id":"9cbb1c7bcf93e8153a9e51cfc5d9eaaa","env":"live.og_local","version":"6.112.01.001","hostname":"default-og"},"process_id":18451,"memory_peak_usage":"34.25 MB","memory_usage":"32.25 MB"}}
				//
				// Monthly5PeriodMode - periodType, baseTime
				// {"message":"OGP-20868.getScheduleDateRange.currentDate:","context":["2021-03-05 00:00:00 000000","subNumber:",1,"time_exec_begin:","2021-03-05 00:00:00","adjustGradeTo:","down","schedule:",{"monthly":5,"enableDownMaintain":true,"downMaintainUnit":1,"downMaintainTimeLength":13,"downMaintainConditionDepositAmount":999999999,"downMaintainConditionBetAmount":999999999}],"level":100,"level_name":"DEBUG","channel":"default-og","datetime":"2021-04-07T15:23:16+08:00","trace":"../../models/group_level.php:4268@getScheduleDateRange > ../../models/group_level.php:2433@playerLevelAdjustDowngrade > ","extra":{"tags":{"request_id":"85b69e203846082d7028dbb91db63a5d","env":"live.og_local","version":"6.112.01.001","hostname":"default-og"},"process_id":19021,"memory_peak_usage":"34.25 MB","memory_usage":"32.25 MB"}}
				//
				// {"message":"OGP-20868.3095.upgradeSched","context":[{"periodType":"daily","baseTime":{"date":"2021-04-02 15:15:36.000000","timezone_type":3,"timezone":"Asia/Hong_Kong"},"dateFrom":null,"dateTo":null,"isBatch":true}],"level":100,"level_name":"DEBUG","channel":"default-og","datetime":"2021-04-07T15:15:39+08:00","trace":"../../models/group_level.php:2433@playerLevelAdjustDowngrade > ../../controllers/modules/lock_app_module.php:105@{closure} > ","extra":{"tags":{"request_id":"4b4833a13687d007b262257d2e9433f4","env":"live.og_local","version":"6.112.01.001","hostname":"default-og"},"process_id":17806,"memory_peak_usage":"34.25 MB","memory_usage":"32.25 MB"}}
				//
			} // EOF if( ! empty($theLogFileList) ){...


			if( empty($testConditionFn) ){ // 0
				// $testConditionFn = function($origId, $afterId) {
				// 	return $origId != $afterId;
				// };
				$testConditionFn = '_testConditionFn4beforeDiffAfterV2';
			}else if( $testConditionFn == '1' ){ // for URI
				// $testConditionFn = function($origId, $afterId) {
				// 	return $origId == $afterId;
				// };
				$testConditionFn = '_testConditionFn4beforeSameAsAfterV2';
			}else if( gettype($testConditionFn) == 'string'  // Specified call local function
				|| gettype($testConditionFn) == 'object' // Custom function
			){ }

			if( gettype($testConditionFn) == 'string' ){  // Specified call local function
				$params = [$origId, $afterId];
				list($rlt, $noteTpl) = call_user_func_array([$this, $testConditionFn], $params);// $rlt = $this->_testConditionFn4beforeDiffAfter(...
				$this->utils->debug_log('will call user func', $testConditionFn, 'params:', $params, 'rlt:', $rlt);
			}else if( gettype($testConditionFn) == 'object' ){ // Custom function
				list($rlt, $noteTpl) = $testConditionFn($origId, $afterId);
				$this->utils->debug_log('will call Closure func. params:', $params, 'rlt:', $rlt);
			}

			$theGenerateCallTrace = '';
			if($rlt !== true){
				$theGenerateCallTrace .= ' ';
				$theGenerateCallTrace .= PHP_EOL;
				$theGenerateCallTrace .= $this->utils->generateCallTrace();
			}

			$generatedCallTrace = $this->utils->generateCallTrace();
			$this->utils->debug_log('2430.generateCallTrace:',  $generatedCallTrace);
			$_theCombinedCase = $theCombinedCase;
			// $isogp21818 = $this->_pos($generatedCallTrace, 'ogp21818');
			// if( $isogp21818 ){
			// 	$_theCombinedCase .= '.ogp21818';
			// }

			$note = sprintf($noteTpl, '[Check] Test case,"'.$_theCombinedCase.'" <br/> Compare Player Level: Before(params) / After(rlt).', var_export($origId,true), var_export($afterId,true). $theGenerateCallTrace );
			$this->test( $rlt // result
				,  true // expect
				, __METHOD__ // title
				, $note // note
			);

		}

	} // EOF _tryDowngradeSuccessTriggerFromCronjobV2


	/**
	 * for test() Detects the $origId and $afterId should be the difference.
	 *
	 * @param integer $origId The original Id ( or Value).
	 * @param integer $afterId The Id ( or Value) after test action.
	 * @param boolean $continueTest If false, test() will be stopped after unexpected results. else keep going next test().
	 * @param string $note
	 * @return array list($result, $note)
	 */
	private function _testConditionFnV2($compareFn, $continueTest = false, $noteTpl = ''){
		if( empty($noteTpl) ){
			$noteTpl = $this->noteTpl;
		}
		$result = $compareFn();
		if( $continueTest ){
			$result = true;
		}
		return [$result, $noteTpl];
	} // EOF _testConditionFn4beforeDiffAfter
	private function _testConditionFn4beforeSameAsAfterV2($origId, $afterId, $continueTest = false, $noteTpl = ''){

		if( ! ($origId == $afterId) ){
			$hasExpectedString = 'has unexpected';
		}else{
			$hasExpectedString = 'has expected';
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

		return $this->_testConditionFnV2(function() use ($origId, $afterId) {
			return ($origId == $afterId);
		}, $continueTest, $noteTpl);
	}// EOF _testConditionFn4beforeSameAsAfterV2
	private function _testConditionFn4beforeDiffAfterV2($origId, $afterId, $continueTest = false, $noteTpl = ''){

		if( ! ($origId != $afterId) ){
			$hasExpectedString = 'has unexpected';
		}else{
			$hasExpectedString = 'has expected';
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

		return $this->_testConditionFnV2(function() use ($origId, $afterId) {
			return ($origId != $afterId);
		}, $continueTest, $noteTpl);
	}// EOF _testConditionFn4beforeDiffAfterV2

	/**
	 * Test the Level Maintain Time related items,"InMaintainTime", "OnLevelMaintainEnable" in Log Filename.
	 *
	 * @param string $theLogFilename The log file path and name.
	 * @param string $theFileContents The log file contents. If empty than will try to load the contents form $theLogFilename.
	 * @param string $theCombinedCase The Combined Case String.
	 * @return void
	 */
	public function try_MaintainTimeOfLogFilenameWithFileContents( $theLogFilename = '/home/vagrant/Code/og/admin/application/logs/tmp_shell/job_player_level_downgrade_by_playerId_6aa4a851566ec0f89f9ff47c6960aa96.log'
		, $theFileContents = '{"message":"isSufficient4RequiredDatetimeRange diffInSeconds","context":[74559,"2021-04-02 15:15:36~2021-04-01 18:32:57","diffInSeconds4Required",1209600,"2021-04-02 15:15:36~2021-03-19 15:15:36"],"level":100,"level_name":"DEBUG","channel":"default-og","datetime":"2021-04-07T15:15:40+08:00","trace":"../../models/group_level.php:7607@isSufficient4RequiredDatetimeRange > ../../models/group_level.php:7723@calcDatetimeRangeAndPreviousFromDatetime > ","extra":{"tags":{"request_id":"4b4833a13687d007b262257d2e9433f4","env":"live.og_local","version":"6.112.01.001","hostname":"default-og"},"process_id":17806,"memory_peak_usage":"34.25 MB","memory_usage":"32.25 MB"}}'
		, $theCombinedCase = 'CASB.EmptyPeriodMode.PeriodIsMet.OnLevelMaintainEnable.InMaintainTime.IsMetLevelMaintainCondition.NoAccumulation'
	){

		$isOffLevelMaintainEnable = $this->utils4testogp->_pos($theCombinedCase, 'OffLevelMaintainEnable');

		$isInMaintainTime = $this->utils4testogp->_pos($theCombinedCase, 'InMaintainTime');
		$isOverMaintainTime = $this->utils4testogp->_pos($theCombinedCase, 'OverMaintainTime');

		if( empty($theFileContents) ){
			$theFileContents = $this->utils4testogp->util_readFile($theLogFilename);
		}

		// Ref. to https://regex101.com/r/HwP5EU/1
		$re = '/isSufficient4RequiredDatetimeRange diffInSeconds.*"context":\[(?P<diffInSeconds>\d+).*diffInSeconds4Required",(?P<diffInSeconds4Required>\d+).*\]/m';
		preg_match_all($re, $theFileContents, $matches, PREG_SET_ORDER, 0);

		// defaults
		$result = null;
		$params = [];
		$params['theCombinedCase'] = $theCombinedCase;
		// Print the entire match result
		// var_dump($matches);
		$diffInSecondsList = [];
		$diffInSeconds4RequiredList = [];
		if( !empty($matches) ){
			foreach($matches as $matche){
                $diffInSecondsList[] = $matche['diffInSeconds'];
				$diffInSeconds4RequiredList[] = $matche['diffInSeconds4Required'];
            }
		}

		$isSufficient = null;
		if( ! empty($diffInSecondsList) && ! empty($diffInSeconds4RequiredList) ){
			$params['all'] = $matches[0][0];
			$params['theCombinedCase'] = $theCombinedCase;
			$params['diffInSecondsList'] = $diffInSecondsList;
			$params['diffInSeconds4RequiredList'] = $diffInSeconds4RequiredList;
			$isSufficient = intval($diffInSecondsList[0]) > intval($diffInSeconds4RequiredList[0]);
			$params['isSufficient'] = $isSufficient;
			$result = false;
			if( $isInMaintainTime ){
				$result = ($isSufficient !== true);
			}
			if( $isOverMaintainTime ){
				$result = ($isSufficient === true);
			}
		}
		if($isOffLevelMaintainEnable){
			$params['ignoreBy'] = 'OffLevelMaintainEnable';
			$result = true; // always be true for ignore.
		}

		$note = sprintf( $this->noteTpl, '[Check] The pre-setup for the related Level Maintain Time in the Log File, "'. $theLogFilename. '".' // # 1
										, var_export($params, true) // # 2
										, var_export($isSufficient, true) // # 3
									);
		return $this->test( $result // ! empty($vipsettingcashbackruleId) // result
				,  true // expect
				, __METHOD__ // title
				, $note // note
		);
	} // EOF try_MaintainTimeOfLogFilenameWithFileContents

	/**
	 * Test the Upgrade/Downgrade related setting for "PeriodIsMet" Or "PeriodNotMet" in Log Filename.
	 *
	 * @param string $theLogFilename The log file path and name.
	 * @param string $theCombinedCase The Combined Case String.
	 * @return void
	 */
	public function try_PeriodIsMetOrNotOfLogFilenameWithFileContents( $theLogFilename = '/home/vagrant/Code/og/admin/application/logs/tmp_shell/job_player_level_downgrade_by_playerId_6aa4a851566ec0f89f9ff47c6960aa96.log'
		, $theFileContents = '{"message":"OGP-20868.getScheduleDateRange.currentDate:","context":["2021-04-06 14:38:15 000000","subNumber:",1,"time_exec_begin:","2021-04-06 14:38:15","adjustGradeTo:","down","schedule:",{"daily":"00:00:00 - 23:59:59","enableDownMaintain":true,"downMaintainUnit":1,"downMaintainTimeLength":13,"downMaintainConditionDepositAmount":999999999,"downMaintainConditionBetAmount":999999999}],"level":100,"level_name":"DEBUG","channel":"default-og","datetime":"2021-04-07T15:16:59+08:00","trace":"../../models/group_level.php:4268@getScheduleDateRange > ../../models/group_level.php:2433@playerLevelAdjustDowngrade > ","extra":{"tags":{"request_id":"3ffec018fe18657b5cd4b5c7e6458adb","env":"live.og_local","version":"6.112.01.001","hostname":"default-og"},"process_id":18016,"memory_peak_usage":"34.25 MB","memory_usage":"32.25 MB"}}'
		, $theCombinedCase = 'CASB.EmptyPeriodMode.PeriodIsMet.OnLevelMaintainEnable.InMaintainTime.IsMetLevelMaintainCondition.NoAccumulation'
	){
		$isEmptyPeriodMode = $this->utils4testogp->_pos($theCombinedCase, 'EmptyPeriodMode');
		$isDailyPeriodMode = $this->utils4testogp->_pos($theCombinedCase, 'DailyPeriodMode');
		$isWeekly1PeriodMode = $this->utils4testogp->_pos($theCombinedCase, 'Weekly1PeriodMode');
		$isMonthly5PeriodMode = $this->utils4testogp->_pos($theCombinedCase, 'Monthly5PeriodMode');

		$isPeriodIsMet = $this->utils4testogp->_pos($theCombinedCase, 'PeriodIsMet');
		$isPeriodNotMet = $this->utils4testogp->_pos($theCombinedCase, 'PeriodNotMet');

		$isEmptyUpgradeSetting = $this->utils4testogp->_pos($theCombinedCase, 'EmptyUpgradeSetting');


		if( empty($theFileContents) ){
			$theFileContents = $this->utils4testogp->util_readFile($theLogFilename);
		}

		// defaults
		$result = null;
		$params = [];
		$params['theCombinedCase'] = $theCombinedCase;
		$params['isEmptyPeriodMode'] = $isEmptyPeriodMode;
		if( ! $isEmptyPeriodMode ){

			// https://regex101.com/r/9RUc6i/1
			$re = '/getScheduleDateRange.currentDate:","context":\["(?P<currentDate>\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}).*schedule:",\{(?P<periodPart>"(?P<periodType>daily|weekly|monthly)":(?P<periodValue>["0-9:\- ]+),)?"enableDownMaintain":(?P<enableDownMaintain>[^,]+),/m';

				// // Ref. to https://regex101.com/r/l8xZdP/2
				// $re = '/getScheduleDateRange.currentDate:","context":\["(?P<currentDate>\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}).*schedule:",.*(?P<periodType>daily|weekly|monthly)":(?P<periodValue>\d+|["0-9:\- ]+),/m';
				// $fileContents = '{"message":"isSufficient4RequiredDatetimeRange diffInSeconds","context":[74559,"2021-04-02 15:15:36~2021-04-01 18:32:57","diffInSeconds4Required",1209600,"2021-04-02 15:15:36~2021-03-19 15:15:36"],"level":100,"level_name":"DEBUG","channel":"default-og","datetime":"2021-04-07T15:15:40+08:00","trace":"../../models/group_level.php:7607@isSufficient4RequiredDatetimeRange > ../../models/group_level.php:7723@calcDatetimeRangeAndPreviousFromDatetime > ","extra":{"tags":{"request_id":"4b4833a13687d007b262257d2e9433f4","env":"live.og_local","version":"6.112.01.001","hostname":"default-og"},"process_id":17806,"memory_peak_usage":"34.25 MB","memory_usage":"32.25 MB"}}';

			preg_match_all($re, $theFileContents, $matches, PREG_SET_ORDER, 0);
			$params['matches'] = $matches;
			$currentDateList = [];
			$periodPartList = [];
			$periodTypeList = [];
			$periodValueList = [];
			if( !empty($matches) ){
				$i = 0;
				foreach($matches as $matche){
					$currentDateList[$i] = $matche['currentDate'];
					if( ! empty($matche['periodPart']) ){
						$periodPartList[$i] = $matche['periodPart'];
						$periodTypeList[$i] = $matche['periodType'];
						$periodValueList[$i] = $matche['periodValue'];
					}
					$i++;
				}
			}
			if( ! empty($currentDateList)
				&& ! empty($periodPartList)
			){
				$currentDate = $currentDateList[0];
				$periodType = $periodTypeList[0];
				$periodValue = $periodValueList[0];

				$params['currentDate'] = $currentDate;
				$params['periodType'] = $periodType;
				$params['periodValue'] = $periodValue;

				$currentDate_Datetime = new Datetime($currentDate);

				if( $isDailyPeriodMode ){
					if($isPeriodIsMet){
						$result = true;
					}else if($isPeriodNotMet){
						$result = false;
					}

				}else if( $isWeekly1PeriodMode ){
					$currentWeekNum = $currentDate_Datetime->format('N');
					$params['currentWeekNum'] = $currentWeekNum;

					if($isPeriodIsMet){
						$result = ($currentWeekNum == $periodValue);
					}else if($isPeriodNotMet){
						$result = ($currentWeekNum != $periodValue);
					}
				}else if( $isMonthly5PeriodMode ){
					$currentDayNum = $currentDate_Datetime->format('d');
					$params['currentDayNum'] = $currentDayNum;

					if($isPeriodIsMet){
						$result = ($currentDayNum == $periodValue);
					}else if($isPeriodNotMet){
						$result = ($currentDayNum != $periodValue);
					}
				}
			}
		}else{
			$result = true; // ignore, for EmptyPeriodMode
		} // EOF if( ! $isEmptyPeriodMode ){..


		$note = sprintf( $this->noteTpl, '[Check] The pre-setup for the related Period settings(for PeriodIsMet and PeriodNotMet) in the Log File, "'. $theLogFilename. '".' // # 1
										, var_export($params, true) // # 2
										, var_export($result, true) // # 3
									);
		return $this->test( $result // ! empty($vipsettingcashbackruleId) // result
				,  true // expect
				, __METHOD__ // title
				, $note // note
		);
	} // EOF try_PeriodIsMetOrNotOfLogFilenameWithFileContents

	/**
	 * Adjust the Level Maintain settings In the Player Current Level
	 *
	 *
	 * @param integer $thePlayerId The test player_id.
	 * @param boolean $enableBool Is enable Level Maintain?
	 * @param integer $theUnit The Level Maintain Time Unit. (1:Day, 2:Week, 3:Month)
	 * @param integer $theTimeLength The Level Maintain Time Length.
	 * @param integer $theConditionDepositAmount The Condition Deposit Amount over Level Maintain Time.
	 * @param integer $theConditionBetAmount The Condition Bet Amount over Level Maintain Time.
	 * @return void
	 */
	private function _getEnableLevelMaintainInPlayerCurrentLevelFn($thePlayerId = 0, $enableBool = true, $theUnit = 1, $theTimeLength = 2, $theConditionDepositAmount = 5, $theConditionBetAmount = 6){
		$this->load->model(['group_level', 'player']);
		$result = $this->player->getPlayerCurrentLevel($thePlayerId);
		$thePlayerCurrentLevel = $result[0];

		if( ! empty($thePlayerCurrentLevel) ){
			$periodMode = null;
			$periodValue = null;
			$vipsettingcashbackruleId = $thePlayerCurrentLevel['vipsettingcashbackruleId'];
			$theCashbackRule = $this->group_level->getCashbackRule($vipsettingcashbackruleId);
			$thePeriodDownStr = $theCashbackRule->period_down;
			if( ! empty($thePeriodDownStr)) {
				$thePeriodInfo = $this->utils4testogp->_parsePeriodInfoInPeriod_down($thePeriodDownStr);
				if( ! empty($thePeriodInfo) ){
					$periodMode = $thePeriodInfo['PeriodMode'];
					$periodValue = $thePeriodInfo['PeriodValue'];
				}
			}

			/// Update the period on the player current level
			// append the Previous period script.
			$gradeMode = 'downgrade';
			$_periodMode = $periodMode;
			$_periodValue = $periodValue;

			$isHourly = false; // always be false in  downgrade check
			$extraData = []; // For Level Maintain of downgrade.
			$extraData['enableDownMaintain'] = $enableBool;
			$extraData['downMaintainUnit'] = $theUnit;
			$extraData['downMaintainTimeLength'] = $theTimeLength;
			$extraData['downMaintainConditionDepositAmount'] = $theConditionDepositAmount;
			$extraData['downMaintainConditionBetAmount'] = $theConditionBetAmount;

			$theJson = $this->utils4testogp->_getPeriodJson($_periodMode, $_periodValue, $isHourly, $extraData);
			$params = [$thePlayerId, $gradeMode, $theJson, $this->isEnableTesting];
			$rlt = call_user_func_array([$this->utils4testogp, '_preSetupPeriodInPlayerCurrentLevel'], $params);// $rlt = $this->_preSetupPeriodInPlayerCurrentLevel($thePlayerId, $gradeMode, $theJson);
			$note = sprintf($this->noteTpl, '[Step] Update the period and Level Maintain on the player current level', var_export($params, true), var_export($rlt, true) );
			$this->test( true // ! empty($vipsettingcashbackruleId) // result
				,  true // expect
				, __METHOD__. ' '. 'Update the period and Level Maintain on the player current level' // title
				, $note // note
			);
		}else{
			$note = sprintf($this->noteTpl, '[Step] Update the period and Level Maintain on the player current level', var_export('The param issue, thePlayerId is empty.', true), var_export('', true) );
			$this->test( false // ! empty($vipsettingcashbackruleId) // result
				,  true // expect
				, __METHOD__. ' '. 'Update the period and Level Maintain on the player current level' // title
				, $note // note
			);
		}

	} // EOF _getEnableLevelMaintainInPlayerCurrentLevelFn

	/**
	 * Get use sync(new / update) the Upgrade Level Setting By Name
	 * clone from $getUpgradeLevelSettingFn
	 *
	 * @param string $settingName The setting name
	 * @param string $forGrade The setting for upgrade or downgrade.
	 * @return void
	 */
	private function _getDowngradeLevelSettingFn($settingName = 'devDowngradeMet.CACB', $forGrade = 'upgrade'){
		/// step: preset VipUpgradeSetting in CACB for upgrade success
		// ref. to tryMacroSetupCACBInVipUpgradeSetting()
		// $settingName = $settingName4UpgradeMet;
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
		$data['accumulation'] = 1; // 0 / 1 / 4 : No / Yes, Registration Date / Yes, Last Change Period
		$data['separate_accumulation_settings'] = NULL; // always be NULL

		$params = [$settingName, $data];
		$rlt = call_user_func_array([$this, '_syncUpgradeLevelSettingByName'], $params); // $rlt = $this->_syncUpgradeLevelSettingByName($settingName, $data);

		$note = sprintf($this->noteTpl, '[Step]preset VipUpgradeSetting in CACB for upgrade success', var_export($params, true), var_export($rlt, true) );
		$this->test( true // result
			,  true // expect
			, __METHOD__. ' '. 'Preset vip_upgrade_setting table' // title
			, $note // note
		);

		return [$rlt, $settingName];
	} // EOF _getDowngradeLevelSettingFn

	/**
	 * Get Combin With PeriodModeCaseList, PeriodIsMetCaseList and LevelMaintainTimeCaseList.
	 * [TESTED]
	 * http://admin.og.local/cli/testing_ogp21818/index/getCombinWithPeriodModeCaseListAndPeriodIsMetCaseListAndLevelMaintainTimeCaseList
	 *
	 * @return array
	 */
	public function getCombinWithPeriodModeCaseListAndPeriodIsMetCaseListAndLevelMaintainTimeCaseList(){

		$theCaseKindList = $this->utils4testogp->_assignCaseKindList();
		$theCommonSeparateModeCaseList = $theCaseKindList['theCommonSeparateModeCaseList'];
		// $thePeriodModeCaseList = $theCaseKindList['thePeriodModeCaseList'];
		$thePeriodIsMetCaseList = $theCaseKindList['thePeriodIsMetCaseList'];
		$theLevelMaintainEnableCaseList = $theCaseKindList['theLevelMaintainEnableCaseList'];
		$theLevelMaintainTimeCaseList = $theCaseKindList['theLevelMaintainTimeCaseList'];
		$theLevelMaintainConditionCaseList = $theCaseKindList['theLevelMaintainConditionCaseList'];
		// $theAccumulationCaseList = $theCaseKindList['theAccumulationCaseList'];
		$theUpgradeSettingCaseList = $theCaseKindList['theUpgradeSettingCaseList'];


		$array = [ array_keys($thePeriodIsMetCaseList)
			// , array_keys($thePeriodModeCaseList)
			, array_keys($theLevelMaintainTimeCaseList)
			, array_keys($theLevelMaintainEnableCaseList)
			, array_keys($theLevelMaintainConditionCaseList)
			// , array_keys($theAccumulationCaseList)
			, array_keys($theUpgradeSettingCaseList)
		]; // 4 params

		echo '<pre>';
		$phpCode = $this->utils4testogp->_genPhpCode4EachCaseList($array);
		print_r($phpCode);


		// $testCaseList = $this->utils4testogp->combination_arr($array);
		//
		// $phpCode = '';
		// $prefix = 'if( ';
		// $suffix = ' }';
		// $phpCodeFormatList = [];
		// // $format = '$is%s && $is%s && $is%s && $is%s && $is%s && $is%s){ // # %d'. PHP_EOL. "\t". '$caseNo = %d;'. PHP_EOL. '}else if( '; // 6+ 2 params
		// $format = '$is%s && $is%s && $is%s && $is%s && $is%s ){ // # %d'. PHP_EOL. "\t". '$caseNo = %d;'. PHP_EOL. '}else if( '; // 5+ 2 params
		// foreach($testCaseList as $indexNumber => $testCase){
		// 	// format: $isEmptyPeriodMode && $isPeriodIsMet && $isInMaintainTime){ // # 0
		// 	// }else if(
		// 	// prefix: "if( "
		// 	// suffix: " }"
		// 	// $sprintf = sprintf($format, $testCase[0], $testCase[1], $testCase[2], $testCase[3], $testCase[4], $testCase[5], $indexNumber, $indexNumber); // 6+ 2 params
		// 	$sprintf = sprintf($format, $testCase[0], $testCase[1], $testCase[2], $testCase[3], $testCase[4], $indexNumber, $indexNumber); // 5+ 2 params
		// 	$phpCodeFormatList[] = $sprintf;
		// }
		// $phpCode = $prefix. implode('', $phpCodeFormatList). $suffix;
		// $phpCode = str_replace('}else if(  }','}',$phpCode);
		// echo '<pre>';
		// // print_r($testCaseList);
		// print_r($phpCode);
		// // return $testCaseList;
	} // EOF getCombinWithPeriodModeCaseListAndPeriodIsMetCaseListAndLevelMaintainTimeCaseList


	/**
	 * To get the params,$periodMode, $periodValue, $TEB_DateTime, $enableLevelMaintainFn for testCasea.
	 * [TESTED]
	 * @param integer $thePlayerId
	 * @param string $theCombinedCase
	 * @return array The script,"list($periodMode, $periodValue, $TEB_DateTime, $enableLevelMaintainFn )" for get the params.
	 */
	public function _getPeriodModeAndPeriodValueAndTEB_DateTimeAndEnableLevelMaintainFnFromCombinedCase( $thePlayerId
			, $theCombinedCase = 'CACB.EmptyPeriodMode.PeriodNotMet.OnLevelMaintainEnable.InMaintainTime.IsMetLevelMaintainCondition.NoAccumulation'
			, $settingName = 'devDowngradeMet.CACB'
	){
		$this->load->model(['group_level', 'player']);
		// should be return [$periodMode, $periodValue, $TEB_DateTime, $enableLevelMaintainFn].



		// EmptyPeriodMode / DailyPeriodMode / Weekly1PeriodMode / Monthly5PeriodMode
		$isEmptyPeriodMode = $this->utils4testogp->_pos($theCombinedCase, 'EmptyPeriodMode');
		$isDailyPeriodMode = $this->utils4testogp->_pos($theCombinedCase, 'DailyPeriodMode');
		$isWeekly1PeriodMode = $this->utils4testogp->_pos($theCombinedCase, 'Weekly1PeriodMode');
		$isMonthly5PeriodMode = $this->utils4testogp->_pos($theCombinedCase, 'Monthly5PeriodMode');
		//
		// PeriodIsMet / PeriodNotMet
		$isPeriodIsMet = $this->utils4testogp->_pos($theCombinedCase, 'PeriodIsMet');
		$isPeriodNotMet = $this->utils4testogp->_pos($theCombinedCase, 'PeriodNotMet');
		//
		// InMaintainTime / OverMaintainTime
		$isInMaintainTime = $this->utils4testogp->_pos($theCombinedCase, 'InMaintainTime');
		$isOverMaintainTime = $this->utils4testogp->_pos($theCombinedCase, 'OverMaintainTime');

		$isOnLevelMaintainEnable = $this->utils4testogp->_pos($theCombinedCase, 'OnLevelMaintainEnable');
		$isOffLevelMaintainEnable = $this->utils4testogp->_pos($theCombinedCase, 'OffLevelMaintainEnable');

		$isIsMetLevelMaintainCondition = $this->utils4testogp->_pos($theCombinedCase, 'IsMetLevelMaintainCondition');
		$isNotMetLevelMaintainCondition = $this->utils4testogp->_pos($theCombinedCase, 'NotMetLevelMaintainCondition');


		$isNoAccumulation = $this->utils4testogp->_pos($theCombinedCase, 'NoAccumulation');
		$isAccumulationYesRegistrationDate = $this->utils4testogp->_pos($theCombinedCase, 'AccumulationYesRegistrationDate');
		$isAccumulationYesLastChangePeriod = $this->utils4testogp->_pos($theCombinedCase, 'AccumulationYesLastChangePeriod');
		// NoAccumulation
		// AccumulationYesRegistrationDate
		// AccumulationYesLastChangePeriod

		$isEmptyUpgradeSetting = $this->utils4testogp->_pos($theCombinedCase, 'EmptyUpgradeSetting');
		$isDepositUpgradeSetting = $this->utils4testogp->_pos($theCombinedCase, 'DepositUpgradeSetting');
		$isBetUpgradeSetting = $this->utils4testogp->_pos($theCombinedCase, 'BetUpgradeSetting');
		$isWinUpgradeSetting = $this->utils4testogp->_pos($theCombinedCase, 'WinUpgradeSetting');
		$isLossUpgradeSetting = $this->utils4testogp->_pos($theCombinedCase, 'LossUpgradeSetting');


		/// If need to get someone case, gen by the URI, http://admin.og.local/cli/testing_ogp21818/index/getCombinWithPeriodModeCaseListAndPeriodIsMetCaseListAndLevelMaintainTimeCaseList

		$isUseDefault = null; // ignore should use the default
		// if( $isEmptyPeriodMode && $isPeriodIsMet ){ // $caseNo: 0 ~ 7
		// 	// the case, isEmptyPeriodMode and isPeriodIsMet is impassable.
		// 	$isUseDefault = true;
		// }else if( $isEmptyPeriodMode && $isPeriodNotMet ){ // $caseNo: 8 ~ 15
		// 	// the case, isEmptyPeriodMode and isPeriodNotMet. The Empty PeriodMode Never Met Period.
		// 	$isUseDefault = true;
		// }else
		if( $isDailyPeriodMode && $isPeriodNotMet ){
			// the case, The Daily PeriodMode Always Met Period.
			$isUseDefault = true;
		}else{
			// other cases
			$isUseDefault = false; // just used this one in ogp21818 from log file,"../logs/testing_ogp21818-index.log".
		}
// echo '<pre>$isUseDefault.1499:';
// print_r($isUseDefault); //$isUseDefault = false;
		$this->utils->debug_log('1661.isUseDefault',$isUseDefault);

		// handle $theUnit and $theTimeLength, default: 13 days
		$theUnit = 1; // day
		$theTimeLength = 13;
		if($isDailyPeriodMode){
			// use defaults
		}else if($isWeekly1PeriodMode){
			$theUnit = 2; // week
			$theTimeLength = 3;
		}else if($isMonthly5PeriodMode){
			$theUnit = 3; // month
			$theTimeLength = 3;
		}
		list($downMaintainTimeLength, $downMaintainTimeUnit) = $this->utils4testogp->downMaintainConvert2AmountAndUnit($theUnit, $theTimeLength);

		/// for $TEB_DateTime
		// the part Must be after the script,"for $enableLevelMaintainFn".
		// Cause by the rule,"Since player get into this level".
		$TEB_DateTime = new DateTime();
		$TEB_DateTime->modify('+ 1 hour'); // for spliated time.
		$playerDetails = $this->player_model->getPlayerDetailsById($thePlayerId);
		$queryFieldname = 'request_time';
		$theLastGradeRecordRow = $this->group_level->queryLastGradeRecordRowBy($thePlayerId, $playerDetails->createdOn, $this->utils->formatDateTimeForMysql($TEB_DateTime), 'upgrade_or_downgrade', $queryFieldname);
		if( empty($theLastGradeRecordRow) ){
			$intoThisLevelDatetimeStr = $playerDetails->createdOn; // from registaction for into vip1 first
		}else{
			$intoThisLevelDatetimeStr = $theLastGradeRecordRow['request_time'];
		}
		$this->utils->debug_log('1722.intoThisLevelDatetimeStr',$intoThisLevelDatetimeStr, 'theLastGradeRecordRow:', $theLastGradeRecordRow );

		$periodMode = null;
		$periodValue = null;
		if($isDailyPeriodMode){
			$periodMode = 'daily'; // 'monthly';
			$periodValue = '00:00:00 - 23:59:59'; // 2;
		}else if($isWeekly1PeriodMode){
			$periodMode = 'weekly'; // 'monthly';
			$periodValue = 1; // 2;
		}else if($isMonthly5PeriodMode){
			$periodMode = 'monthly'; // 'monthly';
			$periodValue = 5; // 2;
		}

		$thePeriodSetting = null;
		if( ! empty($periodMode) && ! empty($periodValue) ){
			$thePeriod = $periodMode;
			$theDay = $periodValue;
			$thePeriodSetting = $this->utils4testogp->getPeriodSetting($thePeriod, $theDay);
			$this->utils->debug_log('1697.thePeriodSetting',$thePeriodSetting);
		}


		$TEB_DateTime->modify('first day of this month')->modify('+ 1 day'); // 2th of this month

		if($isUseDefault == true){
			/// for $periodMode
			$periodMode = null; // 'monthly';
			/// for $periodValue
			$periodValue = null; // 2;

			$TEB_DateTime->modify('first day of this month')->modify('+ 1 day'); // 2th of this month

			if( $isInMaintainTime && $isEmptyPeriodMode ){ // just check level maintain under the case, isPeriodNotMet
				// TEB_DateTime Base on intoThisLevelDatetimeStr, and offset TEB_DateTime to In Maintain Time
				$intoThisLevelDatetime = new DateTime($intoThisLevelDatetimeStr);
				$TEB_DateTime = clone $intoThisLevelDatetime;
				$TEB_DateTime->modify('+ '. intval($downMaintainTimeLength). ' '. $downMaintainTimeUnit); // offset to downMaintainTime
				$TEB_DateTime->modify('- 1 day '); // offset within 1 day
				// $TEB_DateTime->modify('+ '. ( intval($downMaintainTimeLength)- 1). ' '. $downMaintainTimeUnit); // offset within 3 day

				$TEB_YmdHis = $TEB_DateTime->format('Y-m-d H:i:s');
				$theNearBy = null;
				$this->utils->debug_log('1717.TEB_DateTime',$TEB_DateTime, 'TEB_YmdHis:', $TEB_YmdHis, 'thePeriodSetting:', $thePeriodSetting, 'theNearBy:', $theNearBy, 'theCombinedCase:', $theCombinedCase);
			}else if($isInMaintainTime && ! $isEmptyPeriodMode ){  // (! $isEmptyPeriodMode) = isDailyPeriodMode || isWeekly1PeriodMode || isMonthly5PeriodMode
				// TEB_DateTime Base on intoThisLevelDatetimeStr, and offset TEB_DateTime to In Maintain Time
				$intoThisLevelDatetime = new DateTime($intoThisLevelDatetimeStr);
				$TEB_DateTime = clone $intoThisLevelDatetime;
				$TEB_DateTime->modify('+ '. intval($downMaintainTimeLength). ' '. $downMaintainTimeUnit); // offset to downMaintainTime
				$TEB_DateTime->modify('- 1 day '); // offset within 1 day
				// $TEB_DateTime->modify('+ '. ( intval($downMaintainTimeLength)- 1). ' '. $downMaintainTimeUnit); // offset within 3 day

				$theNearBy='earlier'; // for isInMaintainTime
				$TEB_YmdHis = $TEB_DateTime->format('Y-m-d H:i:s');
				$TEB_DateTime = $this->utils4testogp->getTheDateNearByPeriodSetting($TEB_DateTime, $thePeriodSetting, $theNearBy);
				$this->utils->debug_log('1727.TEB_DateTime',$TEB_DateTime, 'TEB_YmdHis:', $TEB_YmdHis, 'thePeriodSetting:', $thePeriodSetting, 'theNearBy:', $theNearBy, 'theCombinedCase:', $theCombinedCase);
			}else if( $isOverMaintainTime && $isEmptyPeriodMode ){
				// TEB_DateTime Base on intoThisLevelDatetimeStr, and offset TEB_DateTime to In Maintain Time
				$intoThisLevelDatetime = new DateTime($intoThisLevelDatetimeStr);
				$TEB_DateTime = clone $intoThisLevelDatetime;
				$TEB_DateTime->modify('+ '. intval($downMaintainTimeLength). ' '. $downMaintainTimeUnit); // offset to downMaintainTime
				$TEB_DateTime->modify('+ 1 day '); // offset over 1 day

				$theNearBy = 'later'; // for isOverMaintainTime
				$TEB_YmdHis = $TEB_DateTime->format('Y-m-d H:i:s');
				$this->utils->debug_log('1735.TEB_DateTime',$TEB_DateTime, 'TEB_YmdHis:', $TEB_YmdHis, 'thePeriodSetting:', $thePeriodSetting, 'theNearBy:', $theNearBy, 'theCombinedCase:', $theCombinedCase);
			}else if( $isOverMaintainTime && !$isEmptyPeriodMode ){
				// TEB_DateTime Base on intoThisLevelDatetimeStr, and offset TEB_DateTime to In Maintain Time
				$intoThisLevelDatetime = new DateTime($intoThisLevelDatetimeStr);
				$TEB_DateTime = clone $intoThisLevelDatetime;
				// $TEB_DateTime->modify('+ '. ( intval($downMaintainTimeLength)+ 1). ' '. $downMaintainTimeUnit); // offset within 3 day
				$TEB_DateTime->modify('+ '. intval($downMaintainTimeLength). ' '. $downMaintainTimeUnit); // offset to downMaintainTime
				$TEB_DateTime->modify('+ 1 day '); // offset over 1 day

				$theNearBy='later'; // for isOverMaintainTime
				$TEB_YmdHis = $TEB_DateTime->format('Y-m-d H:i:s');
				$TEB_DateTime = $this->utils4testogp->getTheDateNearByPeriodSetting($TEB_DateTime, $thePeriodSetting, $theNearBy);
				$this->utils->debug_log('1745.TEB_DateTime',$TEB_DateTime, 'TEB_YmdHis:', $TEB_YmdHis, 'thePeriodSetting:', $thePeriodSetting, 'theNearBy:', $theNearBy, 'theCombinedCase:', $theCombinedCase);
			}

		}else{

			$this->utils->debug_log('1650.isInMaintainTime',$isInMaintainTime
				,'$isOverMaintainTime :',$isOverMaintainTime
				,'$isPeriodIsMet :',$isPeriodIsMet
				,'$isPeriodNotMet :',$isPeriodNotMet
			);

			if( $isInMaintainTime && $isPeriodIsMet){
				/// [TESTED]
				// offset TEB_DateTime to In Maintain Time
				$intoThisLevelDatetime = new DateTime($intoThisLevelDatetimeStr);
				$TEB_DateTime = clone $intoThisLevelDatetime;
				$TEB_DateTime->modify('+ '. intval($downMaintainTimeLength). ' '. $downMaintainTimeUnit); // offset to downMaintainTime
				$TEB_DateTime->modify('- 1 day '); // offset within 1 day

				$theNearBy='earlier'; // for isInMaintainTime
				$TEB_YmdHis = $TEB_DateTime->format('Y-m-d H:i:s');
				$TEB_DateTime = $this->utils4testogp->getTheDateNearByPeriodSetting($TEB_DateTime, $thePeriodSetting, $theNearBy);
				$this->utils->debug_log('1662.TEB_DateTime',$TEB_DateTime, 'TEB_YmdHis:', $TEB_YmdHis, 'thePeriodSetting:', $thePeriodSetting, 'theNearBy:', $theNearBy, 'theCombinedCase:', $theCombinedCase);
			}else if( $isOverMaintainTime && $isPeriodIsMet){
				/// [TESTED]
				// offset TEB_DateTime to In Maintain Time
				$intoThisLevelDatetime = new DateTime($intoThisLevelDatetimeStr);
				$TEB_DateTime = clone $intoThisLevelDatetime;
				$TEB_DateTime->modify('+ '. intval($downMaintainTimeLength). ' '. $downMaintainTimeUnit); // offset to downMaintainTime
				$TEB_DateTime->modify('+ 1 day '); // offset over 1 day

				$theNearBy='later'; // for isOverMaintainTime
				$TEB_YmdHis = $TEB_DateTime->format('Y-m-d H:i:s');
				$TEB_DateTime = $this->utils4testogp->getTheDateNearByPeriodSetting($TEB_DateTime, $thePeriodSetting, $theNearBy);
				$this->utils->debug_log('1681.TEB_DateTime',$TEB_DateTime, 'TEB_YmdHis:', $TEB_YmdHis, 'thePeriodSetting:', $thePeriodSetting, 'theNearBy:', $theNearBy, 'theCombinedCase:', $theCombinedCase);
			}else if( $isInMaintainTime && $isPeriodNotMet){
				/// [TESTED]
				// offset TEB_DateTime to In Maintain Time
				$intoThisLevelDatetime = new DateTime($intoThisLevelDatetimeStr);
				$TEB_DateTime = clone $intoThisLevelDatetime;
				$TEB_DateTime->modify('+ '. intval($downMaintainTimeLength). ' '. $downMaintainTimeUnit); // offset to downMaintainTime

				$theNearBy='earlier'; // for isInMaintainTime
				$TEB_YmdHis = $TEB_DateTime->format('Y-m-d H:i:s');
				$TEB_DateTime = $this->utils4testogp->getTheDateNearByPeriodSetting($TEB_DateTime, $thePeriodSetting, $theNearBy);
				$TEB_DateTime->modify('- 1 day'); // offset 1 day for PeriodNotMet
				$this->utils->debug_log('1699.TEB_DateTime',$TEB_DateTime, 'TEB_YmdHis:', $TEB_YmdHis, 'thePeriodSetting:', $thePeriodSetting, 'theNearBy:', $theNearBy, 'theCombinedCase:', $theCombinedCase);
			}else if( $isOverMaintainTime && $isPeriodNotMet){
				// offset TEB_DateTime to In Maintain Time
				$intoThisLevelDatetime = new DateTime($intoThisLevelDatetimeStr);
				$TEB_DateTime = clone $intoThisLevelDatetime;
				$TEB_DateTime->modify('+ '. intval($downMaintainTimeLength). ' '. $downMaintainTimeUnit); // offset to downMaintainTime
				$TEB_DateTime->modify('+ 1 day '); // offset over 1 day

				$theNearBy='later'; // for isOverMaintainTime
				$TEB_YmdHis = $TEB_DateTime->format('Y-m-d H:i:s');
				$TEB_DateTime = $this->utils4testogp->getTheDateNearByPeriodSetting($TEB_DateTime, $thePeriodSetting, $theNearBy);
				$TEB_DateTime->modify('+ 1 day'); // offset 1 day for PeriodNotMet
				$this->utils->debug_log('1718.TEB_DateTime',$TEB_DateTime, 'TEB_YmdHis:', $TEB_YmdHis, 'thePeriodSetting:', $thePeriodSetting, 'theNearBy:', $theNearBy, 'theCombinedCase:', $theCombinedCase);
			}

		} // EOF if($isUseDefault == true){...


		/// for $enableLevelMaintainFn - part 1/2
		// handle $enableBool
		$enableBool = null;
		if($isOnLevelMaintainEnable){
			$enableBool = true;
		}else if($isOffLevelMaintainEnable){
			$enableBool = false;
		}


		// handle $theUnit and $theTimeLength, default: 13 days
		// $theUnit = 1; // day
		// $theTimeLength = 13;
		// handle $theConditionDepositAmount and $theConditionBetAmount
		if($isIsMetLevelMaintainCondition){ // [TESTED]
			$theConditionDepositAmount = 999999999;
			$theConditionBetAmount = 999999999;
		}else if($isNotMetLevelMaintainCondition){
			$theConditionDepositAmount = 0;
			$theConditionBetAmount = 0;
		}
		$params = [$thePlayerId, $enableBool, $theUnit, $theTimeLength, $theConditionDepositAmount, $theConditionBetAmount];

		/// for $enableLevelMaintainFn - part 2/2
		$_this = $this;
		$enableLevelMaintainFn = function( $thePlayerId ) use ($_this, $params){
			$rlt = call_user_func_array([$_this, '_enableLevelMaintainFn4GPMAPVATAELMFCC'], $params);// $rlt = $this->_enableLevelMaintainFn4GPMAPVATAELMFCC($thePlayerId,...
			return $rlt;
		};



		return [$periodMode, $periodValue, $TEB_DateTime, $enableLevelMaintainFn];
	} // EOF _getPeriodModeAndPeriodValueAndTEB_DateTimeAndEnableLevelMaintainFnFromCombinedCase


	/**
	 * Get the all combine of the case
	 * [TESTED] URI,
	 * http://admin.og.local/cli/testing_ogp21818/index/test_getCombinedCaseList/1/1
	 *
	 * @param boolean|integer $doOutputHtml If true, that will display in the browse and create the csv file for download.
	 * If false, that will return the list for referenced by another method.
	 * @param boolean $isAppendTestConditionFn If true, that will append the Expected keyword for review.
	 *  If false, the list without the Expected keyword.
	 * @return void|array
	 */
	public function test_getCombinedCaseList($doOutputHtml = true, $isAppendTestConditionFn = false){

		$theCaseKindList = $this->utils4testogp->_assignCaseKindList4ogp21818(); // theUpgradeSettingCaseList

		$theCommonSeparateModeCaseList = $theCaseKindList['theCommonSeparateModeCaseList'];
		$thePeriodModeCaseList = $theCaseKindList['thePeriodModeCaseList'];
		$thePeriodIsMetCaseList = $theCaseKindList['thePeriodIsMetCaseList'];
		$theLevelMaintainEnableCaseList = $theCaseKindList['theLevelMaintainEnableCaseList'];
		$theLevelMaintainTimeCaseList = $theCaseKindList['theLevelMaintainTimeCaseList'];
		$theLevelMaintainConditionCaseList = $theCaseKindList['theLevelMaintainConditionCaseList'];
		$theAccumulationCaseList = $theCaseKindList['theAccumulationCaseList'];
		$theUpgradeSettingCaseList = $theCaseKindList['theUpgradeSettingCaseList'];

		$theConditionMetList = $theCaseKindList['theConditionMetList'];

		// theUpgradeSettingCaseList
// WinUpgradeSetting

		// 從上面的組合 轉成各種測試情境
		$testCaseComboList = [ array_keys($theCommonSeparateModeCaseList)
				, array_keys($thePeriodModeCaseList)
				, array_keys($thePeriodIsMetCaseList)
				, array_keys($theLevelMaintainEnableCaseList)
				, array_keys($theLevelMaintainTimeCaseList)
				, array_keys($theLevelMaintainConditionCaseList)
				, array_keys($theAccumulationCaseList)
				, array_keys($theUpgradeSettingCaseList)
				, array_keys($theConditionMetList)
			]; // 9 params
		$testCaseCombinedList = $this->utils4testogp->combination_arr( $testCaseComboList );
		$testCaseList = [];
		foreach($testCaseCombinedList as $indexNumber => $testCase){
			$testCaseList[] = implode('.',$testCase);
		}
		// ignore the specific test cases.
		$ignoreTestCaseKeyList = $this->utils4testogp->_getIngoreCombinedCases4ogp21818();
		$filtedTestCaseList = array_filter($testCaseList, function($v, $k) use ( $ignoreTestCaseKeyList){
			// echo 'k:'.$k;echo PHP_EOL;
			return ! in_array($v, $ignoreTestCaseKeyList);
		}, ARRAY_FILTER_USE_BOTH);
		/// After array_filter(), how can I reset the keys to go in numerical order starting at 0
 		// https://stackoverflow.com/a/3401863
		$filtedTestCaseList = array_values($filtedTestCaseList);

		foreach($filtedTestCaseList as $indexNumber => $testCase ){

			$theTestConditionFn = $this->utils4testogp->_getTestConditionFnFromCombinedCase4ogp21818($testCase);

			$isBeforeDiffAfter = $this->utils4testogp->_pos($theTestConditionFn, 'beforeDiffAfter' );
			$isBeforeSameAsAfter = $this->utils4testogp->_pos($theTestConditionFn, 'beforeSameAsAfter' );
			if($isBeforeSameAsAfter){
				$testCase .= '.ExpectedNoDowngrade';
			} else if ($isBeforeDiffAfter){
				$testCase .= '.ExpectedDowngrade';
			}
			// ATCF = AppendedTestConditionFn
			$filtedTestCaseListATCF[] = $testCase;
		}
		if($isAppendTestConditionFn){
			$filtedTestCaseList = $filtedTestCaseListATCF;
		}

		if($doOutputHtml){
			// Convert $filtedTestCaseList to $csvArray required.
			$filtedTestCaseList4csv = [];
			foreach($filtedTestCaseList as $indexNumber => $testCase ){
				$filtedTestCaseList4csv[] = [$testCase];
			}

			$csvArray = [];
			$csvArray['header_data'] = ['caseHeader'];
			$csvArray['data'] = $filtedTestCaseList4csv;
			$csvArray['folder_name'] = ['caseFolder'];
			$filename=$this->utils->create_csv_filename('filtedTestCaseList');
			$link = $this->utils->create_csv($csvArray, $filename);
			echo '<pre>';
			echo '<a href="'.$link.'">CSV</a>';
			echo PHP_EOL;
			print_r($filtedTestCaseList);
		}else{
			return $filtedTestCaseList;
		}

	} // EOF test_getCombinedCaseList


	// GPMAPVATAELMFCC = getPeriodModeAndPeriodValueAndTEB_DateTimeAndEnableLevelMaintainFnFromCombinedCase
	// _enableLevelMaintainFn4GPMAPVATAELMFCC = enableLevelMaintainFn4_getPeriodModeAndPeriodValueAndTEB_DateTimeAndEnableLevelMaintainFnFromCombinedCase
	public function _enableLevelMaintainFn4GPMAPVATAELMFCC($thePlayerId, $enableBool, $theUnit, $theTimeLength, $theConditionDepositAmount, $theConditionBetAmount){
		$this->load->model(['group_level', 'player']);
		$result = $this->player->getPlayerCurrentLevel($thePlayerId);
		$thePlayerCurrentLevel = $result[0];

		if( ! empty($thePlayerCurrentLevel) ){
			$periodMode = null;
			$periodValue = null;
			$vipsettingcashbackruleId = $thePlayerCurrentLevel['vipsettingcashbackruleId'];
			// load the original values of player's current level
			$theCashbackRule = $this->group_level->getCashbackRule($vipsettingcashbackruleId);
			$thePeriodDownStr = $theCashbackRule->period_down;
			if( ! empty($thePeriodDownStr)) {
				$thePeriodInfo = $this->utils4testogp->_parsePeriodInfoInPeriod_down($thePeriodDownStr);
				if( ! empty($thePeriodInfo) ){
					$periodMode = $thePeriodInfo['PeriodMode'];
					$periodValue = $thePeriodInfo['PeriodValue'];
				}
			}

			/// Update the period on the player current level
			// append the Previous period script.
			$gradeMode = 'downgrade';
			$_periodMode = $periodMode;
			$_periodValue = $periodValue;
// $theGenerateCallTrace = $this->utils->generateCallTrace();
// echo '<pre>1265:';
// print_r(func_get_args());
// print_r($theGenerateCallTrace);
// exit();
			$isHourly = false; // always be false in  downgrade check
			$extraData = []; // For Level Maintain of downgrade.
			$extraData['enableDownMaintain'] = $enableBool;
			$extraData['downMaintainUnit'] = $theUnit;
			$extraData['downMaintainTimeLength'] = $theTimeLength;
			$extraData['downMaintainConditionDepositAmount'] = $theConditionDepositAmount;
			$extraData['downMaintainConditionBetAmount'] = $theConditionBetAmount;

			$theJson = $this->utils4testogp->_getPeriodJson($_periodMode, $_periodValue, $isHourly, $extraData);
			$params = [$thePlayerId, $gradeMode, $theJson, $this->isEnableTesting];
			$rlt = call_user_func_array([$this->utils4testogp, '_preSetupPeriodInPlayerCurrentLevel'], $params);// $rlt = $this->_preSetupPeriodInPlayerCurrentLevel($thePlayerId, $gradeMode, $theJson);

			$note = sprintf($this->noteTpl, '[Step] Update the period and Level Maintain on the player current level', var_export($params, true), var_export($rlt, true) );
			$result = true;
			$expect = true;

			// $note = sprintf($this->noteTpl, '[Step] Update the period and Level Maintain on the player current level', var_export($params, true), var_export($rlt, true) );
			// $this->test( true // ! empty($vipsettingcashbackruleId) // result
			// 	,  true // expect
			// 	, __METHOD__. ' '. 'Update the period and Level Maintain on the player current level' // title
			// 	, $note // note
			// );
		}else{
			$note = sprintf($this->noteTpl, '[Step] Update the period and Level Maintain on the player current level', var_export('The param issue, thePlayerId is empty.', true), var_export('', true) );
			$result = false;
			$expect = true;
			// $note = sprintf($this->noteTpl, '[Step] Update the period and Level Maintain on the player current level', var_export('The param issue, thePlayerId is empty.', true), var_export('', true) );
			// $this->test( false // ! empty($vipsettingcashbackruleId) // result
			// 	,  true // expect
			// 	, __METHOD__. ' '. 'Update the period and Level Maintain on the player current level' // title
			// 	, $note // note
			// );
		}
		// $note = sprintf($this->noteTpl, $note );
		$this->test( $result // ! empty($vipsettingcashbackruleId) // result
			,  $expect // expect
			, __METHOD__. ' '. 'Update the period and Level Maintain on the player current level' // title
			, $note // note
		);
	} // EOF _enableLevelMaintainFn4GPMAPVATAELMFCC

}