<?php

require_once dirname(__FILE__) . '/base_testing_ogp.php';

/**
 * The Situation Testing,
 *
 * Execute the downgrade check,  triggered from the cron-job .
 *
 * !!! To set the proptype, isEnableTesting to true, before execute the testing in Local hosting.
 *
 * The URIs,
 *
 * - Display all kinds of combine keyword  in the list,
 * http://admin.og.local/cli/testing_ogp24373/index/displayCaseKindList
 *
 *
 * - Display all combine test cases with the Expected keyword,
 * http://admin.og.local/cli/testing_ogp24373/index/test_getCombinedCaseList/1/1
 *
 * - List the players for the testing at last 180 days and limit 5.
 * http://admin.og.local/cli/testing_ogp24373/index/searchTestPlayerList/180/5
 *
 * - Execute the testing with plauyer_id=5357, limit 3 test cases.
 * http://admin.og.local/cli/testing_ogp24373/index/test_DowngradeFromCronjob/5357/3
 *
 *
 * Execute the testing via cli,
 * <code>
 * vagrant@default_og_livestablemdb-PHP7:~/Code/og/admin$ php public/index.php cli/testing_ogp24373/index/test_DowngradeFromCronjob 5522 30 | w3m -T text/html > ../logs/ogp24373.5522.test_DowngradeFromCronjob.log &
 * </code>
 *
 * To moniter the testing process via cli,
 * <code>
 * vagrant@default_og_livestablemdb-PHP7:~/Code/og/admin$ tail -f ../logs/testing_ogp24373-index.log|grep 'keyNumber'
 * </code>
 * And check the keyword,"caseTotalAmount" and "keyNumber".
 *
 */
/**
 * Execute the downgrade check,  triggered from the SBE.
 *
 * !!! To set the proptype, isEnableTesting to true, before execute the testing in Local hosting.
 *
 * The URIs,
 *
 * - Display all kinds of combine keyword  in the list,
 * http://admin.og.local/cli/testing_ogp24373/index/displayCaseKindList4FromSBE
 *
 * - Display all combine test cases with the Expected keyword,
 * http://admin.og.local/cli/testing_ogp24373/index/test_getCombinedCaseList4FromSBE/1/1
 *
 * - List the players for the testing at last 180 days and limit 5.
 * http://admin.og.local/cli/testing_ogp24373/index/searchTestPlayerList4FromSBE/180/5
 *
 *
 * - Execute the testing with plauyer_id=5357, limit 3 test cases.
 * http://admin.og.local/cli/testing_ogp24373/index/test_DowngradeFromSBE/5357/3
 *
 * Execute the testing via cli,
 * <code>
 * vagrant@default_og_livestablemdb-PHP7:~/Code/og/admin$ php public/index.php cli/testing_ogp24373/index/test_DowngradeFromSBE 5522 30 | w3m -T text/html > ../logs/ogp24373.5522.test_DowngradeFromSBE.log &
 * </code>
 *
 * To moniter the testing process via cli,
 * <code>
 * vagrant@default_og_livestablemdb-PHP7:~/Code/og/admin$ tail -f ../logs/testing_ogp24373-index.log|grep 'keyNumber'
 * </code>
 * And check the keyword,"caseTotalAmount" and "keyNumber".
 *
 */
class Testing_ogp24373 extends BaseTestingOGP {

	var $playerId = 162503; // test11054
	var $currCase = [];
	var $isEnableTesting = false; /// !!! Set to true, before execute the testing in Local hosting.
	var $level_upgrade_upgrade = 1;
	var $level_upgrade_downgrade = 3;

	public function __construct() {
		parent::__construct();
	}

	public function init(){

		$this->playerId = 162503; // test11054

		$this->currCase = [];
		$this->currCase['mode'] = 'cacb'; // Common Accumulation + Common Bet Amount

		// 連續升級 feature
		// disable_player_multiple_upgrade
		// SELECT * FROM `system_features` WHERE `name` = 'disable_player_multiple_upgrade'

		// config file: secret_keys/config_secret_local.php
		// Separate / Common Accumulation - enable_separate_accumulation_in_setting = true / false
		// Separate / Common Bet Amount - vip_setting_form_ver = 2/1

		$this->appendFixBegin = '//// Begin append by testing_ogp24373 ////';
		$this->appendFixEnd = '//// End append by testing_ogp24373 ////';

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
	 * http://admin.og.local/cli/testing_ogp24373/index/displayCaseKindList
	 *
	 * @return void Display in the browser
	 */
	// public function displayCaseKindList(){
	// 	$this->utils4testogp->displayCaseKindList();

	// } // EOF displayCaseKindList

	/**
	 * Display the all kind of case for test
	 * [TESTED]ok URI,
	 * http://admin.og.local/cli/testing_ogp24373/index/displayCaseKindList
	 *
	 * @return void Display in the browser
	 */
	public function displayCaseKindList(){
		$ary = $this->_assignCaseKindList4ogp24373();
		$this->utils4testogp->displayCaseKindList($ary);
	} // EOF displayCaseKindList

	public function _assignCaseKindList4ogp24373(){
		$theCaseKindList = $this->utils4testogp->_assignCaseKindList();
		// // override theAccumulationCaseList
		// $theAccumulationCaseList = [];
		// $theAccumulationCaseList['NoAccumulation'] = 'Accumulation Setup to No';
		// $theAccumulationCaseList['AccumulationYesRegistrationDate'] = 'Accumulation Setup to Yes,Registration Date';
		// $theAccumulationCaseList['AccumulationYesLastChangePeriod'] = 'Accumulation Setup to Yes,Last Change Period';
		// AccumulationLastChangePeriodResetIfMet
		// $theCaseKindList['theAccumulationCaseList'] = $theAccumulationCaseList;


		// override thePeriodModeCaseList
		$thePeriodModeCaseList = []; // for $periodMode,$periodValue
		// $thePeriodModeCaseList['EmptyPeriodMode'] = 'The Period setting is Empty'; // PeriodMode is empty
		// $thePeriodModeCaseList['DailyPeriodMode'] = 'The Period setting is daily, "00:00:00 - 23:59:59".';// 'daily,00:00:00 - 23:59:59';
		$thePeriodModeCaseList['Weekly1PeriodMode'] = 'The Period setting is weekly, Monday.';// 'weekly,1'; // Monday Tuesday Wednesday Thursday Friday Saturday Sunday
		// $thePeriodModeCaseList['Monthly5PeriodMode'] = 'The Period setting is Monthly, 5th.';// 'monthly,1'; // 1 ~ 31
		$theCaseKindList['thePeriodModeCaseList'] = $thePeriodModeCaseList;


		// $theConditionMetList = [];
		// // $theConditionMetList['IsDowngradeConditionMet'] = 'The Downgrade Condition Is Met, and the player will do downgrade in the level.';
		// $theConditionMetList['IsDowngradeConditionNotMet'] = 'The Downgrade Condition Is Not Met, and the player will keep in the current level.';
		// $theCaseKindList['theConditionMetList'] = $theConditionMetList;


		$theCaseKindList['theNextLevelUpPeriodModeCaseList'] = $this->utils4testogp->genNextLevelList4CaseKindList($thePeriodModeCaseList);
		// $theNextLevelUpPeriodModeCaseList = [];
		// $theNextLevelUpPeriodModeCaseList['NextLevelUpEmptyPeriodMode'] = $theCaseKindList['theNextLevelUpPeriodModeCaseList']['NextLevelUpEmptyPeriodMode'];
		// $theNextLevelUpPeriodModeCaseList['NextLevelUpDailyPeriodMode'] = $theCaseKindList['theNextLevelUpPeriodModeCaseList']['NextLevelUpDailyPeriodMode'];
		// $theNextLevelUpPeriodModeCaseList['NextLevelUpWeekly1PeriodMode'] = $theCaseKindList['theNextLevelUpPeriodModeCaseList']['NextLevelUpWeekly1PeriodMode'];
		// $theNextLevelUpPeriodModeCaseList['NextLevelUpMonthly5PeriodMode'] = $theCaseKindList['theNextLevelUpPeriodModeCaseList']['NextLevelUpMonthly5PeriodMode'];
		// $theCaseKindList['theNextLevelUpPeriodModeCaseList'] = $theNextLevelUpPeriodModeCaseList;
		// // theNextLevelUpPeriodModeCaseList for OGP-21051

		/// Still need to test in OGP-25082
		// // theNextLevelUpUpgradeSettingCaseList for OGP-21818
		// // ignore theNextLevelUpUpgradeSettingCaseList
		// $theCaseKindList['theNextLevelUpUpgradeSettingCaseList'] = [];
		// unset($theCaseKindList['theNextLevelUpUpgradeSettingCaseList']);

		// ignore theLevelMaintainEnableCaseList for upgrade
		$theCaseKindList['theLevelMaintainEnableCaseList'] = [];
		unset($theCaseKindList['theLevelMaintainEnableCaseList']);

		// ignore theLevelMaintainTimeCaseList for upgrade
		$theCaseKindList['theLevelMaintainTimeCaseList'] = [];
		unset($theCaseKindList['theLevelMaintainTimeCaseList']);

		// ignore theLevelMaintainConditionCaseList for upgrade
		$theCaseKindList['theLevelMaintainConditionCaseList'] = [];
		unset($theCaseKindList['theLevelMaintainConditionCaseList']);

		// override
		$theExpectedList = [];
		// $theExpectedList['ExpectedNoDowngrade'] = 'Expected No Downgrade';
		// $theExpectedList['ExpectedDowngrade'] = 'Expected Downgrade';
		$theExpectedList['ExpectedNoUpgrade'] = 'Expected No Upgrade';
		$theExpectedList['ExpectedUpgrade'] = 'Expected Upgrade';
		$theExpectedList['ExpectedMultiUpgrade'] = 'Expected Multi Upgrade';
		$theCaseKindList['theExpectedList'] = $theExpectedList;

		return $theCaseKindList;
	}


	/**
	 * Display the all kind of case for testing, execute from SBE.
	 * [TESTED] URI,
	 * http://admin.og.local/cli/testing_ogp24373/index/displayCaseKindList4FromSBE
	 *
	 * @return void Display in the browser
	 */
	public function displayCaseKindList4FromSBE(){
		$theCaseKindList = $this->utils4testogp->_assignCaseKindList4FromSBE();
		$this->utils4testogp->displayCaseKindList($theCaseKindList);
	} // EOF displayCaseKindList4FromSBE


	/**
	 * URI, http://admin.og.local/cli/testing_ogp24373/index/tryMacroSetupCACBInVipUpgradeSetting
	 * [TESTED]
	 *
	 * @return void
	 */
	public function tryMacroSetupCACBInVipUpgradeSetting(){
		$this->init();

		/// devMacro 設定共同累計共同投注
		$isAccumulationSeparated = false; // for CA
		$isBettingSeparated = false; // for CB
		$rlt = $this->_preSetupAccumulationAndBettingTo($isAccumulationSeparated, $isBettingSeparated);

		$settingName = 'devTest12345.CACB';
		$data = [];
		$data['setting_name'] = $settingName;
		$data['description'] = 'devTest12345.CACB.description';
		$data['status'] = 1; // always be 1 for active.
		$data['level_upgrade'] = 1; // 1, 3 // $this->level_upgrade_upgrade;

		/// CB
		$betAmountMathSign = '<=';
		$betAmountValue = 4;
		$operatorBeforeDeposit = 'and';
		$depositAmountMathSign = '<=';
		$depositAmountValue = 5;
		$operatorBeforeLoss = 'and';
		$lossAmountMathSign = '<=';
		$lossAmountValue = 6;
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
		$data['bet_amount_settings'] = NULL; // always be NULL in CB

		/// CA
		$data['accumulation'] = 4; // 0 / 1 / 4 / 5 : No / Yes, Registration Date / Yes, Last Change Period / Yes, Last Change Period with Reset If Met
		$data['separate_accumulation_settings'] = NULL; // always be NULL in CA

		$params = [$settingName, $data];
		$rlt = call_user_func_array([$this, '_syncUpgradeLevelSettingByName'], $params); // $rlt = $this->_syncUpgradeLevelSettingByName($settingName, $data);

		// reload vip_upgrade_setting by name form DB for check
		$theVipUpgradeSetting = null;
		$theFormula = null;
		$theVipUpgradeSettingList = $this->_getVip_upgrade_settingListBySettingName($settingName);
		if( ! empty($theVipUpgradeSettingList) ){
			$theVipUpgradeSetting = $theVipUpgradeSettingList[0];
			$theSeparateAccumulationSettings = $this->utils->json_decode_handleErr($theVipUpgradeSetting['separate_accumulation_settings'], true);
			$theFormula = $this->utils->json_decode_handleErr($theVipUpgradeSetting['formula'], true);
		}

		$exported_testParams = var_export($params, true);
		$exported_rlt = var_export($rlt, true);
		$note = sprintf($this->noteTpl, 'macroSetupCACB', $exported_testParams, $exported_rlt);
		$this->test( $theFormula['deposit_amount'][1] // $rlt // result
			, 5 // $testInfo['expect'] // expect
			, __METHOD__ // title
			, $note // note
		);

	} // tryMacroSetupCACBInVipUpgradeSetting

	/**
	 * URI, http://admin.og.local/cli/testing_ogp24373/index/tryMacroSetupSACBInVipUpgradeSetting
	 * [TESTED]
	 *
	 * @return void
	 */
	public function tryMacroSetupSACBInVipUpgradeSetting(){
		$this->init();

		/// devMacro 設定共同累計共同投注
		$isAccumulationSeparated = true; // for SA
		$isBettingSeparated = false; // for CB
		$rlt = $this->_preSetupAccumulationAndBettingTo($isAccumulationSeparated, $isBettingSeparated);

		$settingName = 'devTest12345.SACB';
		$data = [];
		$data['setting_name'] = $settingName;
		$data['description'] = 'devTest12345.SACB.description';
		$data['status'] = 1; // always be 1 for active.
		$data['level_upgrade'] = 1;  // 1, 3 : upgrade, downgrade

		/// CB
		$betAmountMathSign = '<=';
		$betAmountValue = 7;
		$operatorBeforeDeposit = 'and';
		$depositAmountMathSign = '<=';
		$depositAmountValue = 8;
		$operatorBeforeLoss = 'and';
		$lossAmountMathSign = '<=';
		$lossAmountValue = 9;
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

		/// SA
		$data['accumulation'] = 0; // 0 / 1 / 4 : No / Yes, Registration Date / Yes, Last Change Period
		$data['separate_accumulation_settings'] = '{"bet_amount": {"accumulation": "1"}, "win_amount": {"accumulation": "4"}, "loss_amount": {"accumulation": "0"}, "deposit_amount": {"accumulation": "1"}}'; // always be NULL

		$params = [$settingName, $data];
		$rlt = call_user_func_array([$this, '_syncUpgradeLevelSettingByName'], $params); // $rlt = $this->_syncUpgradeLevelSettingByName($settingName, $data);

		// reload vip_upgrade_setting by name form DB for check
		$theVipUpgradeSetting = null;
		$theVipUpgradeSettingList = $this->_getVip_upgrade_settingListBySettingName($settingName);
		if( ! empty($theVipUpgradeSettingList) ){
			$theVipUpgradeSetting = $theVipUpgradeSettingList[0];
		}
		$theSeparateAccumulationSettings = $this->utils->json_decode_handleErr($theVipUpgradeSetting['separate_accumulation_settings'], true);

		$exported_testParams = var_export($params, true);
		$exported_rlt = var_export($rlt, true);
		$note = sprintf($this->noteTpl, 'macroSetupSACB', $exported_testParams, $exported_rlt);
		// @todo 目前隨機確認一個欄位是否一致。最好的方式：需要確認每個欄位的資訊。
		$this->test( $theSeparateAccumulationSettings['loss_amount']['accumulation'] // $rlt // result
			, 0 // $testInfo['expect'] // expect
			, __METHOD__ // title
			, $note // note
		);
	} // EOF tryMacroSetupSACBInVipUpgradeSetting

	/**
	 * URI, http://admin.og.local/cli/testing_ogp24373/index/tryMacroSetupCASBInVipUpgradeSetting
	 * [TESTED]
	 *
	 * @return void
	 */
	public function tryMacroSetupCASBInVipUpgradeSetting(){
		$this->init();

		/// devMacro 設定共同累計共同投注
		$isAccumulationSeparated = false; // for CA
		$isBettingSeparated = true; // for SB
		$rlt = $this->_preSetupAccumulationAndBettingTo($isAccumulationSeparated, $isBettingSeparated);

		$settingName = 'devTest12345.CASB';
		$data = [];
		$data['setting_name'] = $settingName;
		$data['description'] = 'devTest12345.CASB.description';
		$data['status'] = 1; // always be 1 for active.
		$data['level_upgrade'] = 1;  // 1, 3 : upgrade, downgrade

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

		// game_platform_and_type
		$params = [];
		$params['defaultValue'] = 20;
		$params['defaultMathSign'] = '>=';
		$gameKeyInfoList= [];
		$gameKeyInfoList['type'] = 'game_platform';
		$gameKeyInfoList['value'] = 28;
		$gameKeyInfoList['math_sign'] = '>=';
		$gameKeyInfoList['game_platform_id'] = '5674'; // MUST BE STRING
		$params['GKAMSAVL'][] = $gameKeyInfoList;
		$gameKeyInfoList= [];
		$gameKeyInfoList['type'] = 'game_type';
		$gameKeyInfoList['value'] = 29;
		$gameKeyInfoList['math_sign'] = '>=';
		$gameKeyInfoList['game_type_id'] = '561'; // MUST BE STRING
		$gameKeyInfoList['precon_logic_flag'] = 'or';
		$params['GKAMSAVL'][] = $gameKeyInfoList;

		$defaultValue = $params['defaultValue'];
		$defaultMathSign = $params['defaultMathSign'];
		$gameKeysAndMathSignAndValueList = $params['GKAMSAVL'];
		$theBetAmountSettings = $this->_prepareBetAmountSettingsOfVipUpgradeSetting($defaultValue, $defaultMathSign, $gameKeysAndMathSignAndValueList);
		// $rlt = call_user_func_array([$this, '_prepareBetAmountSettingsOfVipUpgradeSetting'], $testInfo['params']);
		$data['bet_amount_settings'] = $theBetAmountSettings; // always be NULL

		/// CA
		$data['accumulation'] = 1; // 0 / 1 / 4 : No / Yes, Registration Date / Yes, Last Change Period
		$data['separate_accumulation_settings'] = NULL; // always be NULL

		$params = [$settingName, $data];
		$rlt = call_user_func_array([$this, '_syncUpgradeLevelSettingByName'], $params); // $rlt = $this->_syncUpgradeLevelSettingByName($settingName, $data);

		// reload vip_upgrade_setting by name form DB for check
		$theVipUpgradeSetting = null;
		$theFormula = null;
		$theVipUpgradeSettingList = $this->_getVip_upgrade_settingListBySettingName($settingName);
		if( ! empty($theVipUpgradeSettingList) ){
			$theVipUpgradeSetting = $theVipUpgradeSettingList[0];
			$theSeparateAccumulationSettings = $this->utils->json_decode_handleErr($theVipUpgradeSetting['separate_accumulation_settings'], true);
			$theFormula = $this->utils->json_decode_handleErr($theVipUpgradeSetting['formula'], true);
		}

		$exported_testParams = var_export($params, true);
		$exported_rlt = var_export($rlt, true);
		$note = sprintf($this->noteTpl, 'macroSetupCASB', $exported_testParams, $exported_rlt);
		// @todo 目前隨機確認一個欄位是否一致。最好的方式：需要確認每個欄位的資訊。
		$this->test( $theFormula['deposit_amount'][1] // $rlt // result
			, 8 // $testInfo['expect'] // expect
			, __METHOD__ // title
			, $note // note
		);

	} // tryMacroSetupCASBInVipUpgradeSetting


	/**
	 * URI, http://admin.og.local/cli/testing_ogp24373/index/tryMacroSetupSASBInVipUpgradeSetting
	 * [TESTED]
	 *
	 * @return void
	 */
	public function tryMacroSetupSASBInVipUpgradeSetting(){
		$this->init();

		/// devMacro 設定共同累計共同投注
		$isAccumulationSeparated = true; // for SA
		$isBettingSeparated = true; // for SB
		$rlt = $this->_preSetupAccumulationAndBettingTo($isAccumulationSeparated, $isBettingSeparated);

		$settingName = 'devTest12345.SASB';
		$data = [];
		$data['setting_name'] = $settingName;
		$data['description'] = 'devTest12345.SASB.description';
		$data['status'] = 1; // always be 1 for active.
		$data['level_upgrade'] = 3; // 1, 3 : upgrade, downgrade

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

		// game_platform_and_type
		$params = [];
		$params['defaultValue'] = 20;
		$params['defaultMathSign'] = '>=';
		$gameKeyInfoList= [];
		$gameKeyInfoList['type'] = 'game_platform';
		$gameKeyInfoList['value'] = 28;
		$gameKeyInfoList['math_sign'] = '>=';
		$gameKeyInfoList['game_platform_id'] = '5674'; // MUST BE STRING
		$params['GKAMSAVL'][] = $gameKeyInfoList;
		$gameKeyInfoList= [];
		$gameKeyInfoList['type'] = 'game_type';
		$gameKeyInfoList['value'] = 29;
		$gameKeyInfoList['math_sign'] = '>=';
		$gameKeyInfoList['game_type_id'] = '561'; // MUST BE STRING
		$gameKeyInfoList['precon_logic_flag'] = 'or';
		$params['GKAMSAVL'][] = $gameKeyInfoList;

		$defaultValue = $params['defaultValue'];
		$defaultMathSign = $params['defaultMathSign'];
		$gameKeysAndMathSignAndValueList = $params['GKAMSAVL'];
		$theBetAmountSettings = $this->_prepareBetAmountSettingsOfVipUpgradeSetting($defaultValue, $defaultMathSign, $gameKeysAndMathSignAndValueList);
		// $rlt = call_user_func_array([$this, '_prepareBetAmountSettingsOfVipUpgradeSetting'], $testInfo['params']);
		$data['bet_amount_settings'] = $theBetAmountSettings; // always be NULL

		/// SA
		$data['accumulation'] = 0; // 0 / 1 / 4 : No / Yes, Registration Date / Yes, Last Change Period
		$data['separate_accumulation_settings'] = '{"bet_amount": {"accumulation": "1"}, "win_amount": {"accumulation": "4"}, "loss_amount": {"accumulation": "0"}, "deposit_amount": {"accumulation": "1"}}'; // always be NULL


		$params = [$settingName, $data];
		$rlt = call_user_func_array([$this, '_syncUpgradeLevelSettingByName'], $params); // $rlt = $this->_syncUpgradeLevelSettingByName($settingName, $data);

		// reload vip_upgrade_setting by name form DB for check
		$theVipUpgradeSetting = null;
		$theFormula = null;
		$theVipUpgradeSettingList = $this->_getVip_upgrade_settingListBySettingName($settingName);
		if( ! empty($theVipUpgradeSettingList) ){
			$theVipUpgradeSetting = $theVipUpgradeSettingList[0];
			$theSeparateAccumulationSettings = $this->utils->json_decode_handleErr($theVipUpgradeSetting['separate_accumulation_settings'], true);
			$theFormula = $this->utils->json_decode_handleErr($theVipUpgradeSetting['formula'], true);
		}

		$exported_testParams = var_export($params, true);
		$exported_rlt = var_export($rlt, true);
		$note = sprintf($this->noteTpl, 'macroSetupSASB', $exported_testParams, $exported_rlt);
		// @todo 目前隨機確認一個欄位是否一致。最好的方式：需要確認每個欄位的資訊。
		$this->test( $theFormula['deposit_amount'][1] // $rlt // result
			, 8 // $testInfo['expect'] // expect
			, __METHOD__ // title
			, $note // note
		);

	} // tryMacroSetupSASBInVipUpgradeSetting


	/**
	 *
	 * [TESTED]
	 *
	 * @return void
	 */
	public function test_syncUpgradeLevelSettingByName(){
		if( ! $this->isEnableTesting ){
			return false;
		}
		$this->tryMacroSetupCACBInVipUpgradeSetting();
		$this->tryMacroSetupSACBInVipUpgradeSetting();
		$this->tryMacroSetupCASBInVipUpgradeSetting();
		$this->tryMacroSetupSASBInVipUpgradeSetting();

	}

	/**
	 * [TESTED] URI,
	 * http://admin.og.local/cli/testing_ogp24373/index/test_UpgradeMonthly/tryUpgradeSuccessInCACBTriggerFromCronjob
	 * http://admin.og.local/cli/testing_ogp24373/index/test_UpgradeMonthly/tryUpgradeSuccessInSACBTriggerFromCronjob
	 * http://admin.og.local/cli/testing_ogp24373/index/test_UpgradeMonthly/tryUpgradeSuccessInCASBTriggerFromCronjob
	 * http://admin.og.local/cli/testing_ogp24373/index/test_UpgradeMonthly/tryUpgradeSuccessInSASBTriggerFromCronjob
	 *
	 *
	 * @todo Test the setting, Accumulation used in No/ Yes, Registration Date/ Yes, Last Change Period.
	 *
	 * @param string $tryMethod Use the settings to upgrade check.
	 * @return void
	 */
	public function test_UpgradeMonthly($tryMethod = 'tryUpgradeSuccessInCACBTriggerFromCronjob'){
		if( ! $this->isEnableTesting ){
			return false;
		}
		$offsetDayRange = '180';
		$limit = 5;
		$params = [$offsetDayRange, $limit];
		$rows = call_user_func_array([$this->utils4testogp, '_searchTestPlayerList'], $params); // $rows = $this->utils4testogp->_searchTestPlayerList($offsetDayRange, $limit);
		$theTestPlayerInfo = $rows[0]; // for $rows[3] player_id=5357

		if( ! empty($theTestPlayerInfo)){
			// $playerId = $theTestPlayerInfo['player_id'];
			$original_vipsettingcashbackruleId = $theTestPlayerInfo['vipsettingcashbackruleId'];
		}


		// #1 [非連續升級]每月、現在時間、驗證升級後，同等級。因為 非period 指定的日期。
		// tryUpgradeSuccessInCACBTriggerFromCronjob/0/monthly/1/now/1
		$periodMode = 'monthly';
		$periodDatetime = new Datetime('now');
		$periodValue = $periodDatetime->modify('+ 1 day')->format('d');// 1;
		$TEB_DateTime = 'now';
		$testConditionFn = 1; // 同等級
		$isMultipleUpgrade = 0;
		$params = [$theTestPlayerInfo, $periodMode, $periodValue, $TEB_DateTime, $testConditionFn, $isMultipleUpgrade];
		$rlt = call_user_func_array([$this, $tryMethod], $params); // $this->tryUpgradeSuccessInCACBTriggerFromCronjob($theTestPlayerInfo,... )

		// revert player level
		$playerId = $theTestPlayerInfo['player_id'];
		$newPlayerLevel = $original_vipsettingcashbackruleId;
		$result = $this->group_level->adjustPlayerLevel($playerId, $newPlayerLevel);

		// #2 [非連續升級]每月、現在時間、驗證升級後，會升等級。因為 現在時間 為 period 指定的日期。
		// URI, testing_ogp24373/index/tryUpgradeSuccessInCACBTriggerFromCronjob/0/monthly/29/now/0
		$periodMode = 'monthly';
		$periodDatetime = new Datetime('now');
		$periodValue = $periodDatetime->format('d');// = 29;
		$TEB_DateTime = 'now';
		$testConditionFn = 0; // 會升等級
		$isMultipleUpgrade = 0;
		$params = [$theTestPlayerInfo, $periodMode, $periodValue, $TEB_DateTime, $testConditionFn, $isMultipleUpgrade];
		$rlt = call_user_func_array([$this, $tryMethod], $params); // $this->tryUpgradeSuccessInCACBTriggerFromCronjob($theTestPlayerInfo,... )

		// revert player level
		$playerId = $theTestPlayerInfo['player_id'];
		$newPlayerLevel = $original_vipsettingcashbackruleId;
		$result = $this->group_level->adjustPlayerLevel($playerId, $newPlayerLevel);

		// #3 [連續升級]每月、現在時間、驗證升級後，會升等級。因為 現在時間 為 非period 指定的日期。
		// URI, tryUpgradeSuccessInCACBTriggerFromCronjob/0/monthly/29/now/0/1/0
		$periodMode = 'monthly';
		$periodDatetime = new Datetime('now');
		$periodValue = $periodDatetime->modify('+ 1 day')->format('d');// = 29;
		$TEB_DateTime = 'now';
		$testConditionFn = function($origId, $afterId) {
			return $origId == $afterId;
		}; // 會升等級
		$isMultipleUpgrade = 1;
		$isHourlyInSetting = 0;
		$params = [$theTestPlayerInfo, $periodMode, $periodValue, $TEB_DateTime, $testConditionFn, $isMultipleUpgrade, $isHourlyInSetting];
		$rlt = call_user_func_array([$this, $tryMethod], $params); // $this->tryUpgradeSuccessInCACBTriggerFromCronjob($theTestPlayerInfo,... )


		// revert player level
		$playerId = $theTestPlayerInfo['player_id'];
		$newPlayerLevel = $original_vipsettingcashbackruleId;
		$result = $this->group_level->adjustPlayerLevel($playerId, $newPlayerLevel);

		// #4 [連續升級]每月、現在時間、驗證升級後，會升等級。因為 現在時間 為 period 指定的日期。
		// URI, tryUpgradeSuccessInCACBTriggerFromCronjob/0/monthly/29/now/0/1/0
		$periodMode = 'monthly';
		$periodDatetime = new Datetime('now');
		$periodValue = $periodDatetime->format('d');// = 29;
		$TEB_DateTime = 'now';
		$testConditionFn = function($origId, $afterId) {
			return $origId != $afterId;
		}; // 會升等級偵測
		$isMultipleUpgrade = 1;
		$isHourlyInSetting = 0;
		$params = [$theTestPlayerInfo, $periodMode, $periodValue, $TEB_DateTime, $testConditionFn, $isMultipleUpgrade, $isHourlyInSetting];
		$rlt = call_user_func_array([$this, $tryMethod], $params); // $this->tryUpgradeSuccessInCACBTriggerFromCronjob($theTestPlayerInfo,... )

		// revert player level
		$playerId = $theTestPlayerInfo['player_id'];
		$newPlayerLevel = $original_vipsettingcashbackruleId;
		$result = $this->group_level->adjustPlayerLevel($playerId, $newPlayerLevel);

		// #5 [連續升級]每月、現在時間、驗證升級後，不會升等級。因為 period 為 Hourly 但 cronjob 不是 Hourly 去呼叫。
		// @todo 但該測試升級成功
		// URI, tryUpgradeSuccessInCACBTriggerFromCronjob/0/monthly/29/now/1/1/1
		$periodMode = 'monthly';
		$periodDatetime = new Datetime('now');
		$periodValue = $periodDatetime->format('d');// = 29;
		$TEB_DateTime = 'now';
		$testConditionFn = function($origId, $afterId) {
			return $origId != $afterId;
		}; // 會升等級偵測
		$isMultipleUpgrade = 1;
		$isHourlyInSetting = 1;
		$params = [$theTestPlayerInfo, $periodMode, $periodValue, $TEB_DateTime, $testConditionFn, $isMultipleUpgrade, $isHourlyInSetting];
		$rlt = call_user_func_array([$this, $tryMethod], $params); // $this->tryUpgradeSuccessInCACBTriggerFromCronjob($theTestPlayerInfo,... )

		// revert player level
		$playerId = $theTestPlayerInfo['player_id'];
		$newPlayerLevel = $original_vipsettingcashbackruleId;
		$result = $this->group_level->adjustPlayerLevel($playerId, $newPlayerLevel);

		// #6 [連續升級]每月、現在時間、驗證升級後，不會升等級。因為 period 為 非Hourly 、 period 為 Hourly 但 cronjob 不是 Hourly 去呼叫。
		// @todo 但該測試升級成功
		// URI, tryUpgradeSuccessInCACBTriggerFromCronjob/0/monthly/29/now/1/1/1
		$periodMode = 'monthly';
		$periodDatetime = new Datetime('now');
		$periodValue = $periodDatetime->modify('+ 1 day')->format('d');// = 29;
		$TEB_DateTime = 'now';
		$testConditionFn = function($origId, $afterId) {
			return $origId != $afterId;
		}; // 會升等級偵測
		$isMultipleUpgrade = 1;
		$isHourlyInSetting = 1;
		$params = [$theTestPlayerInfo, $periodMode, $periodValue, $TEB_DateTime, $testConditionFn, $isMultipleUpgrade, $isHourlyInSetting];
		$rlt = call_user_func_array([$this, $tryMethod], $params); // $this->tryUpgradeSuccessInCACBTriggerFromCronjob($theTestPlayerInfo,... )

		// revert player level
		$playerId = $theTestPlayerInfo['player_id'];
		$newPlayerLevel = $original_vipsettingcashbackruleId;
		$result = $this->group_level->adjustPlayerLevel($playerId, $newPlayerLevel);

		$isAccumulationSeparated = null;
		$isBettingSeparated = null;
		$this->_preSetupAccumulationAndBettingTo($isAccumulationSeparated, $isBettingSeparated);
	} // EOF test_UpgradeMonthly

	/**
	 * @todo Cloned from test_UpgradeMonthly and Change $periodXXXX to weekly related.
	 *
	 * @return void
	 */
	public function test_UpgradeWeekly(){
		if( ! $this->isEnableTesting ){
			return false;
		}
		$offsetDayRange = '180';
		$limit = 5;
		$params = [$offsetDayRange, $limit];
		$rows = call_user_func_array([$this->utils4testogp, '_searchTestPlayerList'], $params); // $rows = $this->utils4testogp->_searchTestPlayerList($offsetDayRange, $limit);
		$theTestPlayerInfo = $rows[0]; // for $rows[3] player_id=5357

		if( ! empty($theTestPlayerInfo)){
			// $playerId = $theTestPlayerInfo['player_id'];
			$original_vipsettingcashbackruleId = $theTestPlayerInfo['vipsettingcashbackruleId'];
		}


		// [非連續升級]每月、現在時間、驗證升級後，同等級。因為 非period 指定的日期。
		// tryUpgradeSuccessInCACBTriggerFromCronjob/0/monthly/1/now/1
		$periodMode = 'weekly';
		$periodDatetime = new Datetime('now');
		$periodValue = $periodDatetime->modify('+ 1 day')->format('N');// 1;
		$TEB_DateTime = 'now';
		$testConditionFn = 1; // 同等級
		$isMultipleUpgrade = 0;
		$params = [$theTestPlayerInfo, $periodMode, $periodValue, $TEB_DateTime, $testConditionFn, $isMultipleUpgrade];
		$rlt = call_user_func_array([$this, 'tryUpgradeSuccessInCACBTriggerFromCronjob'], $params); // $this->tryUpgradeSuccessInCACBTriggerFromCronjob($theTestPlayerInfo,... )

		// revert player level
		$playerId = $theTestPlayerInfo['player_id'];
		$newPlayerLevel = $original_vipsettingcashbackruleId;
		$result = $this->group_level->adjustPlayerLevel($playerId, $newPlayerLevel);

		// [非連續升級]每月、現在時間、驗證升級後，會升等級。因為 現在時間 為 period 指定的日期。
		// URI, testing_ogp24373/index/tryUpgradeSuccessInCACBTriggerFromCronjob/0/monthly/29/now/0
		$periodMode = 'weekly';
		$periodDatetime = new Datetime('now');
		$periodValue = $periodDatetime->format('N');// = 29;
		$TEB_DateTime = 'now';
		$testConditionFn = 0; // 會升等級
		$isMultipleUpgrade = 0;
		$params = [$theTestPlayerInfo, $periodMode, $periodValue, $TEB_DateTime, $testConditionFn, $isMultipleUpgrade];
		$rlt = call_user_func_array([$this, 'tryUpgradeSuccessInCACBTriggerFromCronjob'], $params); // $this->tryUpgradeSuccessInCACBTriggerFromCronjob($theTestPlayerInfo,... )

		// revert player level
		$playerId = $theTestPlayerInfo['player_id'];
		$newPlayerLevel = $original_vipsettingcashbackruleId;
		$result = $this->group_level->adjustPlayerLevel($playerId, $newPlayerLevel);

		// [連續升級]每月、現在時間、驗證升級後，會升等級。因為 現在時間 為 period 指定的日期。
		// URI, tryUpgradeSuccessInCACBTriggerFromCronjob/0/monthly/29/now/0/1/0
		$periodMode = 'weekly';
		$periodDatetime = new Datetime('now');
		$periodValue = $periodDatetime->format('N');// = 29;
		$TEB_DateTime = 'now';
		$testConditionFn = function($origId, $afterId) {
			return $origId != $afterId;
		}; // 會升等級
		$isMultipleUpgrade = 1;
		$isHourlyInSetting = 0;
		$params = [$theTestPlayerInfo, $periodMode, $periodValue, $TEB_DateTime, $testConditionFn, $isMultipleUpgrade, $isHourlyInSetting];
		$rlt = call_user_func_array([$this, 'tryUpgradeSuccessInCACBTriggerFromCronjob'], $params); // $this->tryUpgradeSuccessInCACBTriggerFromCronjob($theTestPlayerInfo,... )

		// revert player level
		$playerId = $theTestPlayerInfo['player_id'];
		$newPlayerLevel = $original_vipsettingcashbackruleId;
		$result = $this->group_level->adjustPlayerLevel($playerId, $newPlayerLevel);

		// [連續升級]每月、現在時間、驗證升級後，不會升等級。因為 period 為 Hourly 但 cronjob 不是 Hourly 去呼叫。
		// URI, tryUpgradeSuccessInCACBTriggerFromCronjob/0/monthly/29/now/1/1/1
		$periodMode = 'weekly';
		$periodDatetime = new Datetime('now');
		$periodValue = $periodDatetime->format('N');// = 29;
		$TEB_DateTime = 'now';
		$testConditionFn = function($origId, $afterId) {
			return $origId != $afterId;
			// @todo 但該測試升級成功
		}; // 會升等級
		$isMultipleUpgrade = 1;
		$isHourlyInSetting = 1;
		$params = [$theTestPlayerInfo, $periodMode, $periodValue, $TEB_DateTime, $testConditionFn, $isMultipleUpgrade, $isHourlyInSetting];
		$rlt = call_user_func_array([$this, 'tryUpgradeSuccessInCACBTriggerFromCronjob'], $params); // $this->tryUpgradeSuccessInCACBTriggerFromCronjob($theTestPlayerInfo,... )

		// revert player level
		$playerId = $theTestPlayerInfo['player_id'];
		$newPlayerLevel = $original_vipsettingcashbackruleId;
		$result = $this->group_level->adjustPlayerLevel($playerId, $newPlayerLevel);

	} // EOF test_UpgradeWeekly

	public function test_Upgrade(){
		if( ! $this->isEnableTesting ){
			return false;
		}
		// $this->test_UpgradeDaily();
		$this->test_UpgradeWeekly();
		$this->test_UpgradeMonthly();
	}


	/**
	 * Get the Ingore Combined Test Cases.
	 * The array ref. to http://admin.og.local/cli/testing_ogp24373/index/test_getCombinedCaseList4FromSBE/1/0
	 * @return array
	 */
	public function _getIngoreCombinedCases4FromSBE(){
		$ignoreTestCaseKeyList = [];
		// ignore EmptyPeriodMode.IsDowngradeConditionNotMet
		// IsDowngradeConditionNotMet replace to IsConditionNotMet
		// CACB
		$ignoreTestCaseKeyList[] = 'CACB.EmptyPeriodMode.IsConditionNotMet.OnLevelMaintainEnable.InMaintainTime.IsMetLevelMaintainCondition.NoAccumulation';
		$ignoreTestCaseKeyList[] = 'CACB.EmptyPeriodMode.IsConditionNotMet.OnLevelMaintainEnable.InMaintainTime.IsMetLevelMaintainCondition.AccumulationYesRegistrationDate';
		$ignoreTestCaseKeyList[] = 'CACB.EmptyPeriodMode.IsConditionNotMet.OnLevelMaintainEnable.InMaintainTime.IsMetLevelMaintainCondition.AccumulationYesLastChangePeriod';
		$ignoreTestCaseKeyList[] = 'CACB.EmptyPeriodMode.IsConditionNotMet.OnLevelMaintainEnable.InMaintainTime.NotMetLevelMaintainCondition.NoAccumulation';
		$ignoreTestCaseKeyList[] = 'CACB.EmptyPeriodMode.IsConditionNotMet.OnLevelMaintainEnable.InMaintainTime.NotMetLevelMaintainCondition.AccumulationYesRegistrationDate';
		$ignoreTestCaseKeyList[] = 'CACB.EmptyPeriodMode.IsConditionNotMet.OnLevelMaintainEnable.InMaintainTime.NotMetLevelMaintainCondition.AccumulationYesLastChangePeriod';
		$ignoreTestCaseKeyList[] = 'CACB.EmptyPeriodMode.IsConditionNotMet.OnLevelMaintainEnable.OverMaintainTime.IsMetLevelMaintainCondition.NoAccumulation';
		$ignoreTestCaseKeyList[] = 'CACB.EmptyPeriodMode.IsConditionNotMet.OnLevelMaintainEnable.OverMaintainTime.IsMetLevelMaintainCondition.AccumulationYesRegistrationDate';
		$ignoreTestCaseKeyList[] = 'CACB.EmptyPeriodMode.IsConditionNotMet.OnLevelMaintainEnable.OverMaintainTime.IsMetLevelMaintainCondition.AccumulationYesLastChangePeriod';
		$ignoreTestCaseKeyList[] = 'CACB.EmptyPeriodMode.IsConditionNotMet.OnLevelMaintainEnable.OverMaintainTime.NotMetLevelMaintainCondition.NoAccumulation';
		$ignoreTestCaseKeyList[] = 'CACB.EmptyPeriodMode.IsConditionNotMet.OnLevelMaintainEnable.OverMaintainTime.NotMetLevelMaintainCondition.AccumulationYesRegistrationDate';
		$ignoreTestCaseKeyList[] = 'CACB.EmptyPeriodMode.IsConditionNotMet.OnLevelMaintainEnable.OverMaintainTime.NotMetLevelMaintainCondition.AccumulationYesLastChangePeriod';
		$ignoreTestCaseKeyList[] = 'CACB.EmptyPeriodMode.IsConditionNotMet.OffLevelMaintainEnable.InMaintainTime.IsMetLevelMaintainCondition.NoAccumulation';
		$ignoreTestCaseKeyList[] = 'CACB.EmptyPeriodMode.IsConditionNotMet.OffLevelMaintainEnable.InMaintainTime.IsMetLevelMaintainCondition.AccumulationYesRegistrationDate';
		$ignoreTestCaseKeyList[] = 'CACB.EmptyPeriodMode.IsConditionNotMet.OffLevelMaintainEnable.InMaintainTime.IsMetLevelMaintainCondition.AccumulationYesLastChangePeriod';
		$ignoreTestCaseKeyList[] = 'CACB.EmptyPeriodMode.IsConditionNotMet.OffLevelMaintainEnable.InMaintainTime.NotMetLevelMaintainCondition.NoAccumulation';
		$ignoreTestCaseKeyList[] = 'CACB.EmptyPeriodMode.IsConditionNotMet.OffLevelMaintainEnable.InMaintainTime.NotMetLevelMaintainCondition.AccumulationYesRegistrationDate';
		$ignoreTestCaseKeyList[] = 'CACB.EmptyPeriodMode.IsConditionNotMet.OffLevelMaintainEnable.InMaintainTime.NotMetLevelMaintainCondition.AccumulationYesLastChangePeriod';
		$ignoreTestCaseKeyList[] = 'CACB.EmptyPeriodMode.IsConditionNotMet.OffLevelMaintainEnable.OverMaintainTime.IsMetLevelMaintainCondition.NoAccumulation';
		$ignoreTestCaseKeyList[] = 'CACB.EmptyPeriodMode.IsConditionNotMet.OffLevelMaintainEnable.OverMaintainTime.IsMetLevelMaintainCondition.AccumulationYesRegistrationDate';
		$ignoreTestCaseKeyList[] = 'CACB.EmptyPeriodMode.IsConditionNotMet.OffLevelMaintainEnable.OverMaintainTime.IsMetLevelMaintainCondition.AccumulationYesLastChangePeriod';
		$ignoreTestCaseKeyList[] = 'CACB.EmptyPeriodMode.IsConditionNotMet.OffLevelMaintainEnable.OverMaintainTime.NotMetLevelMaintainCondition.NoAccumulation';
		$ignoreTestCaseKeyList[] = 'CACB.EmptyPeriodMode.IsConditionNotMet.OffLevelMaintainEnable.OverMaintainTime.NotMetLevelMaintainCondition.AccumulationYesRegistrationDate';
		$ignoreTestCaseKeyList[] = 'CACB.EmptyPeriodMode.IsConditionNotMet.OffLevelMaintainEnable.OverMaintainTime.NotMetLevelMaintainCondition.AccumulationYesLastChangePeriod';
		// SACB
		$ignoreTestCaseKeyList[] = 'SACB.EmptyPeriodMode.IsConditionNotMet.OnLevelMaintainEnable.InMaintainTime.IsMetLevelMaintainCondition.NoAccumulation';
		$ignoreTestCaseKeyList[] = 'SACB.EmptyPeriodMode.IsConditionNotMet.OnLevelMaintainEnable.InMaintainTime.IsMetLevelMaintainCondition.AccumulationYesRegistrationDate';
		$ignoreTestCaseKeyList[] = 'SACB.EmptyPeriodMode.IsConditionNotMet.OnLevelMaintainEnable.InMaintainTime.IsMetLevelMaintainCondition.AccumulationYesLastChangePeriod';
		$ignoreTestCaseKeyList[] = 'SACB.EmptyPeriodMode.IsConditionNotMet.OnLevelMaintainEnable.InMaintainTime.NotMetLevelMaintainCondition.NoAccumulation';
		$ignoreTestCaseKeyList[] = 'SACB.EmptyPeriodMode.IsConditionNotMet.OnLevelMaintainEnable.InMaintainTime.NotMetLevelMaintainCondition.AccumulationYesRegistrationDate';
		$ignoreTestCaseKeyList[] = 'SACB.EmptyPeriodMode.IsConditionNotMet.OnLevelMaintainEnable.InMaintainTime.NotMetLevelMaintainCondition.AccumulationYesLastChangePeriod';
		$ignoreTestCaseKeyList[] = 'SACB.EmptyPeriodMode.IsConditionNotMet.OnLevelMaintainEnable.OverMaintainTime.IsMetLevelMaintainCondition.NoAccumulation';
		$ignoreTestCaseKeyList[] = 'SACB.EmptyPeriodMode.IsConditionNotMet.OnLevelMaintainEnable.OverMaintainTime.IsMetLevelMaintainCondition.AccumulationYesRegistrationDate';
		$ignoreTestCaseKeyList[] = 'SACB.EmptyPeriodMode.IsConditionNotMet.OnLevelMaintainEnable.OverMaintainTime.IsMetLevelMaintainCondition.AccumulationYesLastChangePeriod';
		$ignoreTestCaseKeyList[] = 'SACB.EmptyPeriodMode.IsConditionNotMet.OnLevelMaintainEnable.OverMaintainTime.NotMetLevelMaintainCondition.NoAccumulation';
		$ignoreTestCaseKeyList[] = 'SACB.EmptyPeriodMode.IsConditionNotMet.OnLevelMaintainEnable.OverMaintainTime.NotMetLevelMaintainCondition.AccumulationYesRegistrationDate';
		$ignoreTestCaseKeyList[] = 'SACB.EmptyPeriodMode.IsConditionNotMet.OnLevelMaintainEnable.OverMaintainTime.NotMetLevelMaintainCondition.AccumulationYesLastChangePeriod';
		$ignoreTestCaseKeyList[] = 'SACB.EmptyPeriodMode.IsConditionNotMet.OffLevelMaintainEnable.InMaintainTime.IsMetLevelMaintainCondition.NoAccumulation';
		$ignoreTestCaseKeyList[] = 'SACB.EmptyPeriodMode.IsConditionNotMet.OffLevelMaintainEnable.InMaintainTime.IsMetLevelMaintainCondition.AccumulationYesRegistrationDate';
		$ignoreTestCaseKeyList[] = 'SACB.EmptyPeriodMode.IsConditionNotMet.OffLevelMaintainEnable.InMaintainTime.IsMetLevelMaintainCondition.AccumulationYesLastChangePeriod';
		$ignoreTestCaseKeyList[] = 'SACB.EmptyPeriodMode.IsConditionNotMet.OffLevelMaintainEnable.InMaintainTime.NotMetLevelMaintainCondition.NoAccumulation';
		$ignoreTestCaseKeyList[] = 'SACB.EmptyPeriodMode.IsConditionNotMet.OffLevelMaintainEnable.InMaintainTime.NotMetLevelMaintainCondition.AccumulationYesRegistrationDate';
		$ignoreTestCaseKeyList[] = 'SACB.EmptyPeriodMode.IsConditionNotMet.OffLevelMaintainEnable.InMaintainTime.NotMetLevelMaintainCondition.AccumulationYesLastChangePeriod';
		$ignoreTestCaseKeyList[] = 'SACB.EmptyPeriodMode.IsConditionNotMet.OffLevelMaintainEnable.OverMaintainTime.IsMetLevelMaintainCondition.NoAccumulation';
		$ignoreTestCaseKeyList[] = 'SACB.EmptyPeriodMode.IsConditionNotMet.OffLevelMaintainEnable.OverMaintainTime.IsMetLevelMaintainCondition.AccumulationYesRegistrationDate';
		$ignoreTestCaseKeyList[] = 'SACB.EmptyPeriodMode.IsConditionNotMet.OffLevelMaintainEnable.OverMaintainTime.IsMetLevelMaintainCondition.AccumulationYesLastChangePeriod';
		$ignoreTestCaseKeyList[] = 'SACB.EmptyPeriodMode.IsConditionNotMet.OffLevelMaintainEnable.OverMaintainTime.NotMetLevelMaintainCondition.NoAccumulation';
		$ignoreTestCaseKeyList[] = 'SACB.EmptyPeriodMode.IsConditionNotMet.OffLevelMaintainEnable.OverMaintainTime.NotMetLevelMaintainCondition.AccumulationYesRegistrationDate';
		$ignoreTestCaseKeyList[] = 'SACB.EmptyPeriodMode.IsConditionNotMet.OffLevelMaintainEnable.OverMaintainTime.NotMetLevelMaintainCondition.AccumulationYesLastChangePeriod';
		// CASB
		$ignoreTestCaseKeyList[] = 'CASB.EmptyPeriodMode.IsConditionNotMet.OnLevelMaintainEnable.InMaintainTime.IsMetLevelMaintainCondition.NoAccumulation';
		$ignoreTestCaseKeyList[] = 'CASB.EmptyPeriodMode.IsConditionNotMet.OnLevelMaintainEnable.InMaintainTime.IsMetLevelMaintainCondition.AccumulationYesRegistrationDate';
		$ignoreTestCaseKeyList[] = 'CASB.EmptyPeriodMode.IsConditionNotMet.OnLevelMaintainEnable.InMaintainTime.IsMetLevelMaintainCondition.AccumulationYesLastChangePeriod';
		$ignoreTestCaseKeyList[] = 'CASB.EmptyPeriodMode.IsConditionNotMet.OnLevelMaintainEnable.InMaintainTime.NotMetLevelMaintainCondition.NoAccumulation';
		$ignoreTestCaseKeyList[] = 'CASB.EmptyPeriodMode.IsConditionNotMet.OnLevelMaintainEnable.InMaintainTime.NotMetLevelMaintainCondition.AccumulationYesRegistrationDate';
		$ignoreTestCaseKeyList[] = 'CASB.EmptyPeriodMode.IsConditionNotMet.OnLevelMaintainEnable.InMaintainTime.NotMetLevelMaintainCondition.AccumulationYesLastChangePeriod';
		$ignoreTestCaseKeyList[] = 'CASB.EmptyPeriodMode.IsConditionNotMet.OnLevelMaintainEnable.OverMaintainTime.IsMetLevelMaintainCondition.NoAccumulation';
		$ignoreTestCaseKeyList[] = 'CASB.EmptyPeriodMode.IsConditionNotMet.OnLevelMaintainEnable.OverMaintainTime.IsMetLevelMaintainCondition.AccumulationYesRegistrationDate';
		$ignoreTestCaseKeyList[] = 'CASB.EmptyPeriodMode.IsConditionNotMet.OnLevelMaintainEnable.OverMaintainTime.IsMetLevelMaintainCondition.AccumulationYesLastChangePeriod';
		$ignoreTestCaseKeyList[] = 'CASB.EmptyPeriodMode.IsConditionNotMet.OnLevelMaintainEnable.OverMaintainTime.NotMetLevelMaintainCondition.NoAccumulation';
		$ignoreTestCaseKeyList[] = 'CASB.EmptyPeriodMode.IsConditionNotMet.OnLevelMaintainEnable.OverMaintainTime.NotMetLevelMaintainCondition.AccumulationYesRegistrationDate';
		$ignoreTestCaseKeyList[] = 'CASB.EmptyPeriodMode.IsConditionNotMet.OnLevelMaintainEnable.OverMaintainTime.NotMetLevelMaintainCondition.AccumulationYesLastChangePeriod';
		$ignoreTestCaseKeyList[] = 'CASB.EmptyPeriodMode.IsConditionNotMet.OffLevelMaintainEnable.InMaintainTime.IsMetLevelMaintainCondition.NoAccumulation';
		$ignoreTestCaseKeyList[] = 'CASB.EmptyPeriodMode.IsConditionNotMet.OffLevelMaintainEnable.InMaintainTime.IsMetLevelMaintainCondition.AccumulationYesRegistrationDate';
		$ignoreTestCaseKeyList[] = 'CASB.EmptyPeriodMode.IsConditionNotMet.OffLevelMaintainEnable.InMaintainTime.IsMetLevelMaintainCondition.AccumulationYesLastChangePeriod';
		$ignoreTestCaseKeyList[] = 'CASB.EmptyPeriodMode.IsConditionNotMet.OffLevelMaintainEnable.InMaintainTime.NotMetLevelMaintainCondition.NoAccumulation';
		$ignoreTestCaseKeyList[] = 'CASB.EmptyPeriodMode.IsConditionNotMet.OffLevelMaintainEnable.InMaintainTime.NotMetLevelMaintainCondition.AccumulationYesRegistrationDate';
		$ignoreTestCaseKeyList[] = 'CASB.EmptyPeriodMode.IsConditionNotMet.OffLevelMaintainEnable.InMaintainTime.NotMetLevelMaintainCondition.AccumulationYesLastChangePeriod';
		$ignoreTestCaseKeyList[] = 'CASB.EmptyPeriodMode.IsConditionNotMet.OffLevelMaintainEnable.OverMaintainTime.IsMetLevelMaintainCondition.NoAccumulation';
		$ignoreTestCaseKeyList[] = 'CASB.EmptyPeriodMode.IsConditionNotMet.OffLevelMaintainEnable.OverMaintainTime.IsMetLevelMaintainCondition.AccumulationYesRegistrationDate';
		$ignoreTestCaseKeyList[] = 'CASB.EmptyPeriodMode.IsConditionNotMet.OffLevelMaintainEnable.OverMaintainTime.IsMetLevelMaintainCondition.AccumulationYesLastChangePeriod';
		$ignoreTestCaseKeyList[] = 'CASB.EmptyPeriodMode.IsConditionNotMet.OffLevelMaintainEnable.OverMaintainTime.NotMetLevelMaintainCondition.NoAccumulation';
		$ignoreTestCaseKeyList[] = 'CASB.EmptyPeriodMode.IsConditionNotMet.OffLevelMaintainEnable.OverMaintainTime.NotMetLevelMaintainCondition.AccumulationYesRegistrationDate';
		$ignoreTestCaseKeyList[] = 'CASB.EmptyPeriodMode.IsConditionNotMet.OffLevelMaintainEnable.OverMaintainTime.NotMetLevelMaintainCondition.AccumulationYesLastChangePeriod';
		// SASB
		$ignoreTestCaseKeyList[] = 'SASB.EmptyPeriodMode.IsConditionNotMet.OnLevelMaintainEnable.InMaintainTime.IsMetLevelMaintainCondition.NoAccumulation';
		$ignoreTestCaseKeyList[] = 'SASB.EmptyPeriodMode.IsConditionNotMet.OnLevelMaintainEnable.InMaintainTime.IsMetLevelMaintainCondition.AccumulationYesRegistrationDate';
		$ignoreTestCaseKeyList[] = 'SASB.EmptyPeriodMode.IsConditionNotMet.OnLevelMaintainEnable.InMaintainTime.IsMetLevelMaintainCondition.AccumulationYesLastChangePeriod';
		$ignoreTestCaseKeyList[] = 'SASB.EmptyPeriodMode.IsConditionNotMet.OnLevelMaintainEnable.InMaintainTime.NotMetLevelMaintainCondition.NoAccumulation';
		$ignoreTestCaseKeyList[] = 'SASB.EmptyPeriodMode.IsConditionNotMet.OnLevelMaintainEnable.InMaintainTime.NotMetLevelMaintainCondition.AccumulationYesRegistrationDate';
		$ignoreTestCaseKeyList[] = 'SASB.EmptyPeriodMode.IsConditionNotMet.OnLevelMaintainEnable.InMaintainTime.NotMetLevelMaintainCondition.AccumulationYesLastChangePeriod';
		$ignoreTestCaseKeyList[] = 'SASB.EmptyPeriodMode.IsConditionNotMet.OnLevelMaintainEnable.OverMaintainTime.IsMetLevelMaintainCondition.NoAccumulation';
		$ignoreTestCaseKeyList[] = 'SASB.EmptyPeriodMode.IsConditionNotMet.OnLevelMaintainEnable.OverMaintainTime.IsMetLevelMaintainCondition.AccumulationYesRegistrationDate';
		$ignoreTestCaseKeyList[] = 'SASB.EmptyPeriodMode.IsConditionNotMet.OnLevelMaintainEnable.OverMaintainTime.IsMetLevelMaintainCondition.AccumulationYesLastChangePeriod';
		$ignoreTestCaseKeyList[] = 'SASB.EmptyPeriodMode.IsConditionNotMet.OnLevelMaintainEnable.OverMaintainTime.NotMetLevelMaintainCondition.NoAccumulation';
		$ignoreTestCaseKeyList[] = 'SASB.EmptyPeriodMode.IsConditionNotMet.OnLevelMaintainEnable.OverMaintainTime.NotMetLevelMaintainCondition.AccumulationYesRegistrationDate';
		$ignoreTestCaseKeyList[] = 'SASB.EmptyPeriodMode.IsConditionNotMet.OnLevelMaintainEnable.OverMaintainTime.NotMetLevelMaintainCondition.AccumulationYesLastChangePeriod';
		$ignoreTestCaseKeyList[] = 'SASB.EmptyPeriodMode.IsConditionNotMet.OffLevelMaintainEnable.InMaintainTime.IsMetLevelMaintainCondition.NoAccumulation';
		$ignoreTestCaseKeyList[] = 'SASB.EmptyPeriodMode.IsConditionNotMet.OffLevelMaintainEnable.InMaintainTime.IsMetLevelMaintainCondition.AccumulationYesRegistrationDate';
		$ignoreTestCaseKeyList[] = 'SASB.EmptyPeriodMode.IsConditionNotMet.OffLevelMaintainEnable.InMaintainTime.IsMetLevelMaintainCondition.AccumulationYesLastChangePeriod';
		$ignoreTestCaseKeyList[] = 'SASB.EmptyPeriodMode.IsConditionNotMet.OffLevelMaintainEnable.InMaintainTime.NotMetLevelMaintainCondition.NoAccumulation';
		$ignoreTestCaseKeyList[] = 'SASB.EmptyPeriodMode.IsConditionNotMet.OffLevelMaintainEnable.InMaintainTime.NotMetLevelMaintainCondition.AccumulationYesRegistrationDate';
		$ignoreTestCaseKeyList[] = 'SASB.EmptyPeriodMode.IsConditionNotMet.OffLevelMaintainEnable.InMaintainTime.NotMetLevelMaintainCondition.AccumulationYesLastChangePeriod';
		$ignoreTestCaseKeyList[] = 'SASB.EmptyPeriodMode.IsConditionNotMet.OffLevelMaintainEnable.OverMaintainTime.IsMetLevelMaintainCondition.NoAccumulation';
		$ignoreTestCaseKeyList[] = 'SASB.EmptyPeriodMode.IsConditionNotMet.OffLevelMaintainEnable.OverMaintainTime.IsMetLevelMaintainCondition.AccumulationYesRegistrationDate';
		$ignoreTestCaseKeyList[] = 'SASB.EmptyPeriodMode.IsConditionNotMet.OffLevelMaintainEnable.OverMaintainTime.IsMetLevelMaintainCondition.AccumulationYesLastChangePeriod';
		$ignoreTestCaseKeyList[] = 'SASB.EmptyPeriodMode.IsConditionNotMet.OffLevelMaintainEnable.OverMaintainTime.NotMetLevelMaintainCondition.NoAccumulation';
		$ignoreTestCaseKeyList[] = 'SASB.EmptyPeriodMode.IsConditionNotMet.OffLevelMaintainEnable.OverMaintainTime.NotMetLevelMaintainCondition.AccumulationYesRegistrationDate';
		$ignoreTestCaseKeyList[] = 'SASB.EmptyPeriodMode.IsConditionNotMet.OffLevelMaintainEnable.OverMaintainTime.NotMetLevelMaintainCondition.AccumulationYesLastChangePeriod';

		/// ignore EmptyPeriodMode, IsDowngradeConditionMet, NoAccumulation, because the case is impossible.
		// for CACB
		// IsDowngradeConditionMet replace to IsConditionMet
		$ignoreTestCaseKeyList[] = 'CACB.EmptyPeriodMode.IsConditionMet.OnLevelMaintainEnable.InMaintainTime.IsMetLevelMaintainCondition.NoAccumulation';
		$ignoreTestCaseKeyList[] = 'CACB.EmptyPeriodMode.IsConditionMet.OnLevelMaintainEnable.InMaintainTime.NotMetLevelMaintainCondition.NoAccumulation';
		$ignoreTestCaseKeyList[] = 'CACB.EmptyPeriodMode.IsConditionMet.OnLevelMaintainEnable.OverMaintainTime.IsMetLevelMaintainCondition.NoAccumulation';
		$ignoreTestCaseKeyList[] = 'CACB.EmptyPeriodMode.IsConditionMet.OnLevelMaintainEnable.OverMaintainTime.NotMetLevelMaintainCondition.NoAccumulation';
		$ignoreTestCaseKeyList[] = 'CACB.EmptyPeriodMode.IsConditionMet.OffLevelMaintainEnable.InMaintainTime.IsMetLevelMaintainCondition.NoAccumulation';
		$ignoreTestCaseKeyList[] = 'CACB.EmptyPeriodMode.IsConditionMet.OffLevelMaintainEnable.InMaintainTime.NotMetLevelMaintainCondition.NoAccumulation';
		$ignoreTestCaseKeyList[] = 'CACB.EmptyPeriodMode.IsConditionMet.OffLevelMaintainEnable.OverMaintainTime.IsMetLevelMaintainCondition.NoAccumulation';
		$ignoreTestCaseKeyList[] = 'CACB.EmptyPeriodMode.IsConditionMet.OffLevelMaintainEnable.OverMaintainTime.NotMetLevelMaintainCondition.NoAccumulation';
		// for SACB
		$ignoreTestCaseKeyList[] = 'SACB.EmptyPeriodMode.IsConditionMet.OnLevelMaintainEnable.InMaintainTime.IsMetLevelMaintainCondition.NoAccumulation';
		$ignoreTestCaseKeyList[] = 'SACB.EmptyPeriodMode.IsConditionMet.OnLevelMaintainEnable.InMaintainTime.NotMetLevelMaintainCondition.NoAccumulation';
		$ignoreTestCaseKeyList[] = 'SACB.EmptyPeriodMode.IsConditionMet.OnLevelMaintainEnable.OverMaintainTime.IsMetLevelMaintainCondition.NoAccumulation';
		$ignoreTestCaseKeyList[] = 'SACB.EmptyPeriodMode.IsConditionMet.OnLevelMaintainEnable.OverMaintainTime.NotMetLevelMaintainCondition.NoAccumulation';
		$ignoreTestCaseKeyList[] = 'SACB.EmptyPeriodMode.IsConditionMet.OffLevelMaintainEnable.InMaintainTime.IsMetLevelMaintainCondition.NoAccumulation';
		$ignoreTestCaseKeyList[] = 'SACB.EmptyPeriodMode.IsConditionMet.OffLevelMaintainEnable.InMaintainTime.NotMetLevelMaintainCondition.NoAccumulation';
		$ignoreTestCaseKeyList[] = 'SACB.EmptyPeriodMode.IsConditionMet.OffLevelMaintainEnable.OverMaintainTime.IsMetLevelMaintainCondition.NoAccumulation';
		$ignoreTestCaseKeyList[] = 'SACB.EmptyPeriodMode.IsConditionMet.OffLevelMaintainEnable.OverMaintainTime.NotMetLevelMaintainCondition.NoAccumulation';
		// for CASB
		$ignoreTestCaseKeyList[] = 'CASB.EmptyPeriodMode.IsConditionMet.OnLevelMaintainEnable.InMaintainTime.IsMetLevelMaintainCondition.NoAccumulation';
		$ignoreTestCaseKeyList[] = 'CASB.EmptyPeriodMode.IsConditionMet.OnLevelMaintainEnable.InMaintainTime.NotMetLevelMaintainCondition.NoAccumulation';
		$ignoreTestCaseKeyList[] = 'CASB.EmptyPeriodMode.IsConditionMet.OnLevelMaintainEnable.OverMaintainTime.IsMetLevelMaintainCondition.NoAccumulation';
		$ignoreTestCaseKeyList[] = 'CASB.EmptyPeriodMode.IsConditionMet.OnLevelMaintainEnable.OverMaintainTime.NotMetLevelMaintainCondition.NoAccumulation';
		$ignoreTestCaseKeyList[] = 'CASB.EmptyPeriodMode.IsConditionMet.OffLevelMaintainEnable.InMaintainTime.IsMetLevelMaintainCondition.NoAccumulation';
		$ignoreTestCaseKeyList[] = 'CASB.EmptyPeriodMode.IsConditionMet.OffLevelMaintainEnable.InMaintainTime.NotMetLevelMaintainCondition.NoAccumulation';
		$ignoreTestCaseKeyList[] = 'CASB.EmptyPeriodMode.IsConditionMet.OffLevelMaintainEnable.OverMaintainTime.IsMetLevelMaintainCondition.NoAccumulation';
		$ignoreTestCaseKeyList[] = 'CASB.EmptyPeriodMode.IsConditionMet.OffLevelMaintainEnable.OverMaintainTime.NotMetLevelMaintainCondition.NoAccumulation';
		// for SASB
		$ignoreTestCaseKeyList[] = 'SASB.EmptyPeriodMode.IsConditionMet.OnLevelMaintainEnable.InMaintainTime.IsMetLevelMaintainCondition.NoAccumulation';
		$ignoreTestCaseKeyList[] = 'SASB.EmptyPeriodMode.IsConditionMet.OnLevelMaintainEnable.InMaintainTime.NotMetLevelMaintainCondition.NoAccumulation';
		$ignoreTestCaseKeyList[] = 'SASB.EmptyPeriodMode.IsConditionMet.OnLevelMaintainEnable.OverMaintainTime.IsMetLevelMaintainCondition.NoAccumulation';
		$ignoreTestCaseKeyList[] = 'SASB.EmptyPeriodMode.IsConditionMet.OnLevelMaintainEnable.OverMaintainTime.NotMetLevelMaintainCondition.NoAccumulation';
		$ignoreTestCaseKeyList[] = 'SASB.EmptyPeriodMode.IsConditionMet.OffLevelMaintainEnable.InMaintainTime.IsMetLevelMaintainCondition.NoAccumulation';
		$ignoreTestCaseKeyList[] = 'SASB.EmptyPeriodMode.IsConditionMet.OffLevelMaintainEnable.InMaintainTime.NotMetLevelMaintainCondition.NoAccumulation';
		$ignoreTestCaseKeyList[] = 'SASB.EmptyPeriodMode.IsConditionMet.OffLevelMaintainEnable.OverMaintainTime.IsMetLevelMaintainCondition.NoAccumulation';
		$ignoreTestCaseKeyList[] = 'SASB.EmptyPeriodMode.IsConditionMet.OffLevelMaintainEnable.OverMaintainTime.NotMetLevelMaintainCondition.NoAccumulation';

		/// ignore EmptyPeriodMode and OffLevelMaintainEnable
		// CACB
		$ignoreTestCaseKeyList[] = 'CACB.EmptyPeriodMode.IsConditionMet.OffLevelMaintainEnable.InMaintainTime.IsMetLevelMaintainCondition.AccumulationYesRegistrationDate';
		$ignoreTestCaseKeyList[] = 'CACB.EmptyPeriodMode.IsConditionMet.OffLevelMaintainEnable.InMaintainTime.IsMetLevelMaintainCondition.AccumulationYesLastChangePeriod';
		$ignoreTestCaseKeyList[] = 'CACB.EmptyPeriodMode.IsConditionMet.OffLevelMaintainEnable.InMaintainTime.NotMetLevelMaintainCondition.AccumulationYesRegistrationDate';
		$ignoreTestCaseKeyList[] = 'CACB.EmptyPeriodMode.IsConditionMet.OffLevelMaintainEnable.InMaintainTime.NotMetLevelMaintainCondition.AccumulationYesLastChangePeriod';
		$ignoreTestCaseKeyList[] = 'CACB.EmptyPeriodMode.IsConditionMet.OffLevelMaintainEnable.OverMaintainTime.IsMetLevelMaintainCondition.AccumulationYesRegistrationDate';
		$ignoreTestCaseKeyList[] = 'CACB.EmptyPeriodMode.IsConditionMet.OffLevelMaintainEnable.OverMaintainTime.IsMetLevelMaintainCondition.AccumulationYesLastChangePeriod';
		$ignoreTestCaseKeyList[] = 'CACB.EmptyPeriodMode.IsConditionMet.OffLevelMaintainEnable.OverMaintainTime.NotMetLevelMaintainCondition.AccumulationYesRegistrationDate';
		$ignoreTestCaseKeyList[] = 'CACB.EmptyPeriodMode.IsConditionMet.OffLevelMaintainEnable.OverMaintainTime.NotMetLevelMaintainCondition.AccumulationYesLastChangePeriod';
		// SACB
		$ignoreTestCaseKeyList[] = 'SACB.EmptyPeriodMode.IsConditionMet.OffLevelMaintainEnable.InMaintainTime.IsMetLevelMaintainCondition.AccumulationYesRegistrationDate';
		$ignoreTestCaseKeyList[] = 'SACB.EmptyPeriodMode.IsConditionMet.OffLevelMaintainEnable.InMaintainTime.IsMetLevelMaintainCondition.AccumulationYesLastChangePeriod';
		$ignoreTestCaseKeyList[] = 'SACB.EmptyPeriodMode.IsConditionMet.OffLevelMaintainEnable.InMaintainTime.NotMetLevelMaintainCondition.AccumulationYesRegistrationDate';
		$ignoreTestCaseKeyList[] = 'SACB.EmptyPeriodMode.IsConditionMet.OffLevelMaintainEnable.InMaintainTime.NotMetLevelMaintainCondition.AccumulationYesLastChangePeriod';
		$ignoreTestCaseKeyList[] = 'SACB.EmptyPeriodMode.IsConditionMet.OffLevelMaintainEnable.OverMaintainTime.IsMetLevelMaintainCondition.AccumulationYesRegistrationDate';
		$ignoreTestCaseKeyList[] = 'SACB.EmptyPeriodMode.IsConditionMet.OffLevelMaintainEnable.OverMaintainTime.IsMetLevelMaintainCondition.AccumulationYesLastChangePeriod';
		$ignoreTestCaseKeyList[] = 'SACB.EmptyPeriodMode.IsConditionMet.OffLevelMaintainEnable.OverMaintainTime.NotMetLevelMaintainCondition.AccumulationYesRegistrationDate';
		$ignoreTestCaseKeyList[] = 'SACB.EmptyPeriodMode.IsConditionMet.OffLevelMaintainEnable.OverMaintainTime.NotMetLevelMaintainCondition.AccumulationYesLastChangePeriod';
		// CASB
		$ignoreTestCaseKeyList[] = 'CASB.EmptyPeriodMode.IsConditionMet.OffLevelMaintainEnable.InMaintainTime.IsMetLevelMaintainCondition.AccumulationYesRegistrationDate';
		$ignoreTestCaseKeyList[] = 'CASB.EmptyPeriodMode.IsConditionMet.OffLevelMaintainEnable.InMaintainTime.IsMetLevelMaintainCondition.AccumulationYesLastChangePeriod';
		$ignoreTestCaseKeyList[] = 'CASB.EmptyPeriodMode.IsConditionMet.OffLevelMaintainEnable.InMaintainTime.NotMetLevelMaintainCondition.AccumulationYesRegistrationDate';
		$ignoreTestCaseKeyList[] = 'CASB.EmptyPeriodMode.IsConditionMet.OffLevelMaintainEnable.InMaintainTime.NotMetLevelMaintainCondition.AccumulationYesLastChangePeriod';
		$ignoreTestCaseKeyList[] = 'CASB.EmptyPeriodMode.IsConditionMet.OffLevelMaintainEnable.OverMaintainTime.IsMetLevelMaintainCondition.AccumulationYesRegistrationDate';
		$ignoreTestCaseKeyList[] = 'CASB.EmptyPeriodMode.IsConditionMet.OffLevelMaintainEnable.OverMaintainTime.IsMetLevelMaintainCondition.AccumulationYesLastChangePeriod';
		$ignoreTestCaseKeyList[] = 'CASB.EmptyPeriodMode.IsConditionMet.OffLevelMaintainEnable.OverMaintainTime.NotMetLevelMaintainCondition.AccumulationYesRegistrationDate';
		$ignoreTestCaseKeyList[] = 'CASB.EmptyPeriodMode.IsConditionMet.OffLevelMaintainEnable.OverMaintainTime.NotMetLevelMaintainCondition.AccumulationYesLastChangePeriod';
		// SASB
		$ignoreTestCaseKeyList[] = 'SASB.EmptyPeriodMode.IsConditionMet.OffLevelMaintainEnable.InMaintainTime.IsMetLevelMaintainCondition.AccumulationYesRegistrationDate';
		$ignoreTestCaseKeyList[] = 'SASB.EmptyPeriodMode.IsConditionMet.OffLevelMaintainEnable.InMaintainTime.IsMetLevelMaintainCondition.AccumulationYesLastChangePeriod';
		$ignoreTestCaseKeyList[] = 'SASB.EmptyPeriodMode.IsConditionMet.OffLevelMaintainEnable.InMaintainTime.NotMetLevelMaintainCondition.AccumulationYesRegistrationDate';
		$ignoreTestCaseKeyList[] = 'SASB.EmptyPeriodMode.IsConditionMet.OffLevelMaintainEnable.InMaintainTime.NotMetLevelMaintainCondition.AccumulationYesLastChangePeriod';
		$ignoreTestCaseKeyList[] = 'SASB.EmptyPeriodMode.IsConditionMet.OffLevelMaintainEnable.OverMaintainTime.IsMetLevelMaintainCondition.AccumulationYesRegistrationDate';
		$ignoreTestCaseKeyList[] = 'SASB.EmptyPeriodMode.IsConditionMet.OffLevelMaintainEnable.OverMaintainTime.IsMetLevelMaintainCondition.AccumulationYesLastChangePeriod';
		$ignoreTestCaseKeyList[] = 'SASB.EmptyPeriodMode.IsConditionMet.OffLevelMaintainEnable.OverMaintainTime.NotMetLevelMaintainCondition.AccumulationYesRegistrationDate';
		$ignoreTestCaseKeyList[] = 'SASB.EmptyPeriodMode.IsConditionMet.OffLevelMaintainEnable.OverMaintainTime.NotMetLevelMaintainCondition.AccumulationYesLastChangePeriod';
		return $ignoreTestCaseKeyList;
	} // EOF _getIngoreCombinedCases4FromSBE
	/**
	 * Get the Ingore Combined Test Cases.
	 *
	 * @return array
	 */
	public function _getIngoreCombinedCases(){
		return $this->utils4testogp->_getIngoreCombinedCases();
	} // EOF _getIngoreCombinedCases



	public function test_getCombinedCaseList4upgradeSBE($doOutputHtml = true, $isAppendTestConditionFn = false, $isIgnoreAccumulationCases = false){ // test_DwongradeMonthly(){ //
		$isTriggerFromHourlyCronjob = true;
		$manual_batch = true;
		return $this->test_getCombinedCaseList4upgrade($doOutputHtml, $isAppendTestConditionFn, $isTriggerFromHourlyCronjob, $isIgnoreAccumulationCases);
	} // EOF test_getCombinedCaseList4upgradeHourlyCronjob
	public function test_getCombinedCaseList4upgradeHourlyCronjob($doOutputHtml = true, $isAppendTestConditionFn = false, $isIgnoreAccumulationCases = false){ // test_DwongradeMonthly(){ //
		$isTriggerFromHourlyCronjob = true;
		return $this->test_getCombinedCaseList4upgrade($doOutputHtml, $isAppendTestConditionFn, $isTriggerFromHourlyCronjob, $isIgnoreAccumulationCases);
	} // EOF test_getCombinedCaseList4upgradeHourlyCronjob
	//
	// Todo : Utils4testogp::_getTestConditionFnFromCombinedCase4ogp24373upgrade()
	// http://admin.og.local/cli/testing_ogp24373/index/test_getCombinedCaseList4upgrade/1/1/0/1
	/**
	 * Get the Combined Case List for upgrade test
	 *
	 * @param boolean $doOutputHtml
	 * @param boolean $isAppendTestConditionFn
	 * @param boolean $isTriggerFromHourlyCronjob
	 * @param boolean $isIgnoreAccumulationCases
	 *
	 * @return void
	 */
	public function test_getCombinedCaseList4upgrade( $doOutputHtml = true
						, $isAppendTestConditionFn = false
						, $isTriggerFromHourlyCronjob = false
						, $isIgnoreAccumulationCases = false
						, $manual_batch = false // #5 If false means batch, if true means manual.
	){ // test_DwongradeMonthly(){ //

		// theCommonSeparateModeCaseList: CACB, SACB, CASB, SASB
		// thePeriodModeCaseList: EmptyPeriodMode, DailyPeriodMode, Weekly1PeriodMode, Monthly5PeriodMode
		// theNextLevelUpPeriodModeCaseList: NextLevelUpEmptyPeriodMode, NextLevelUpDailyPeriodMode, NextLevelUpWeekly1PeriodMode, NextLevelUpMonthly5PeriodMode
		// thePeriodIsMetCaseList: PeriodIsMet, PeriodNotMet
		// theNextLevelUpPeriodIsMetCaseList: NextLevelUpPeriodIsMet, NextLevelUpPeriodNotMet
		// theAccumulationCaseList: NoAccumulation, AccumulationYesRegistrationDate, AccumulationYesLastChangePeriod, AccumulationLastChangePeriodResetIfMet
		// theNextLevelUpAccumulationCaseList: NextLevelUpNoAccumulation, NextLevelUpAccumulationYesRegistrationDate, NextLevelUpAccumulationYesLastChangePeriod, NextLevelUpAccumulationLastChangePeriodResetIfMet
		// theHourlyCheckUpgradeEnableCaseList: OnHourlyCheckUpgradeEnable, OffHourlyCheckUpgradeEnable
		// theMultipleUpgradeEnableModeCaseList: OnMultipleUpgradeEnable, OffMultipleUpgradeEnable

		// _assignCaseKindList4ogp24373
		// $theCaseKindList = $this->utils4testogp->_assignCaseKindList();
		$theCaseKindList = $this->_assignCaseKindList4ogp24373();
		$theCommonSeparateModeCaseList = $theCaseKindList['theCommonSeparateModeCaseList'];
		$thePeriodModeCaseList = $theCaseKindList['thePeriodModeCaseList'];
		$thePeriodIsMetCaseList = $theCaseKindList['thePeriodIsMetCaseList'];
		$theUpgradeSettingCaseList = $theCaseKindList['theUpgradeSettingCaseList'];
		$theConditionMetList = $theCaseKindList['theConditionMetList'];
		$theMetedInPreconditionsCaseList = $theCaseKindList['theMetedInPreconditionsCaseList'];

		$theMultipleUpgradeEnableModeCaseList = $theCaseKindList['theMultipleUpgradeEnableModeCaseList'];

		$theNextLevelUpUpgradeSettingCaseList = $theCaseKindList['theNextLevelUpUpgradeSettingCaseList']; // 與 theNextLevelUpPeriodModeCaseList 重複定義。
		$theNextLevelUpPeriodModeCaseList = $theCaseKindList['theNextLevelUpPeriodModeCaseList'];
		$theNextLevelUpPeriodIsMetCaseList = $theCaseKindList['theNextLevelUpPeriodIsMetCaseList'];
		$theNextLevelUpAccumulationCaseList = $theCaseKindList['theNextLevelUpAccumulationCaseList'];
		$theNextLevelUpMetInFormulaCaseList = $theCaseKindList['theNextLevelUpMetInFormulaCaseList'];

		$theHourlyCheckUpgradeEnableCaseList = $theCaseKindList['theHourlyCheckUpgradeEnableCaseList'];



		// theNextLevelUpUpgradeSettingCaseList
		// theNextLevelUpPeriodModeCaseList
		// theNextLevelUpPeriodIsMetCaseList
		// theNextLevelUpAccumulationCaseList

		// $theLevelMaintainEnableCaseList = $theCaseKindList['theLevelMaintainEnableCaseList'];
		// $theLevelMaintainTimeCaseList = $theCaseKindList['theLevelMaintainTimeCaseList'];
		// $theLevelMaintainConditionCaseList = $theCaseKindList['theLevelMaintainConditionCaseList'];
		$theAccumulationCaseList = $theCaseKindList['theAccumulationCaseList'];
		$theMetInFormulaCaseList = $theCaseKindList['theMetInFormulaCaseList'];

		//ignore some case of the env.
		$thePeriodModeCaseList['EmptyPeriodMode'] = null;
		unset($thePeriodModeCaseList['EmptyPeriodMode']);
		$theNextLevelUpPeriodModeCaseList['NextLevelUpEmptyPeriodMode'] = null;
		unset($theNextLevelUpPeriodModeCaseList['NextLevelUpEmptyPeriodMode']);

		// $isIgnoreAccumulationCases = true; // The test cases amount is 400 , if ignore Some Cases.
		// 從上面的組合 轉成各種測試情境
		$testCaseComboList = [ array_keys($theCommonSeparateModeCaseList)
			, array_keys($thePeriodModeCaseList)
			, array_keys($theNextLevelUpPeriodModeCaseList)
			, array_keys($theMetedInPreconditionsCaseList) // 針對曾經滿足過的記錄處理 OGP-24903
			, array_keys($thePeriodIsMetCaseList)
			, array_keys($theNextLevelUpPeriodIsMetCaseList)
			, array_keys($theHourlyCheckUpgradeEnableCaseList)
			// , array_keys($theLevelMaintainEnableCaseList)
			// , array_keys($theLevelMaintainTimeCaseList)
			// , array_keys($theLevelMaintainConditionCaseList)
			, array_keys($theAccumulationCaseList) // options

			, array_keys($theUpgradeSettingCaseList)
			, array_keys($theNextLevelUpUpgradeSettingCaseList)
			, array_keys($theConditionMetList) // only for theUpgradeSettingCaseList
			, array_keys($theMetInFormulaCaseList) // options, its should be combined with theAccumulationCaseList
			// , array_keys($theNextLevelUpAccumulationCaseList) // options 應該不需要，目前只看當下的升級狀況。
			, array_keys($theNextLevelUpMetInFormulaCaseList) // options, its should be combined with theNextLevelUpAccumulationCaseList
			, array_keys($theMultipleUpgradeEnableModeCaseList)
		]; // The test cases amount is 3000 more.
		if($isIgnoreAccumulationCases == 1 && false){
			/// for ORIG,
			// for test OGP-24903
			$theCommonSeparateModeCaseList = [];
			// $theCommonSeparateModeCaseList['CACB']= 'SBE has set Common Accumulation + Common Bet Amount'; // 'tryDowngradeSuccessInCACBTriggerFromCronjob';
			$theCommonSeparateModeCaseList['SACB']= 'SBE has set Separate Accumulation + Common Bet Amount'; // 'tryDowngradeSuccessInSACBTriggerFromCronjob';
			// $theCommonSeparateModeCaseList['CASB']= 'SBE has set Common Accumulation + Separate Bet Amount'; // 'tryDowngradeSuccessInCASBTriggerFromCronjob';
			// $theCommonSeparateModeCaseList['SASB']= 'SBE has set Separate Accumulation + Separate Bet Amount'; // 'tryDowngradeSuccessInSASBTriggerFromCronjob';


			// override $thePeriodModeCaseList
			$thePeriodModeCaseList = []; // for $periodMode,$periodValue
			// $thePeriodModeCaseList['EmptyPeriodMode'] = 'The Period setting is Empty'; // PeriodMode is empty
			// $thePeriodModeCaseList['DailyPeriodMode'] = 'The Period setting is daily, "00:00:00 - 23:59:59".';// 'daily,00:00:00 - 23:59:59';
			$thePeriodModeCaseList['Weekly1PeriodMode'] = 'The Period setting is weekly, Monday.';// 'weekly,1'; // Monday Tuesday Wednesday Thursday Friday Saturday Sunday
			// $thePeriodModeCaseList['Monthly5PeriodMode'] = 'The Period setting is Monthly, 5th.';// 'monthly,1'; // 1 ~ 31
			// $theNextLevelUpPeriodModeCaseList = $this->utils4testogp->genNextLevelList4CaseKindList($thePeriodModeCaseList);

			// override $theAccumulationCaseList
			$theAccumulationCaseList = [];
			// $theAccumulationCaseList['NoAccumulation'] = 'Accumulation Setup to No';
			// $theAccumulationCaseList['AccumulationYesRegistrationDate'] = 'Accumulation Setup to Yes,Registration Date';
			// $theAccumulationCaseList['AccumulationYesLastChangePeriod'] = 'Accumulation Setup to Yes,Last Change Period';
			$theAccumulationCaseList['AccumulationLastChangePeriodResetIfMet'] = 'Accumulation Setup to Last Change Period,Reset If Met'; // OGP-24373
			// $theAccumulationCaseList['AccumulationYesLastChangePeriodResetAlways'] = 'Accumulation Setup to Yes,Last Change Period,Reset always'; // OGP-24373




			$theMultipleUpgradeEnableModeCaseList = [];
			$theMultipleUpgradeEnableModeCaseList['OnMultipleUpgradeEnable'] = 'Has Enable Multiple Upgrade Feature';
			$theMultipleUpgradeEnableModeCaseList['OffMultipleUpgradeEnable'] = 'Has Disable Multiple Upgrade Feature';



			$testCaseComboList = [ array_keys($theCommonSeparateModeCaseList)
				, array_keys($thePeriodModeCaseList)
				// , array_keys($theNextLevelUpPeriodModeCaseList) // options
				, array_keys($theMetedInPreconditionsCaseList)
				, array_keys($thePeriodIsMetCaseList)
				// , array_keys($theNextLevelUpPeriodIsMetCaseList) // options
				, array_keys($theHourlyCheckUpgradeEnableCaseList)
				// , array_keys($theLevelMaintainEnableCaseList)
				// , array_keys($theLevelMaintainTimeCaseList)
				// , array_keys($theLevelMaintainConditionCaseList)
				, array_keys($theAccumulationCaseList)
				// theUpgradeSettingCaseList , theMetInFormulaCaseList
				, array_keys($theUpgradeSettingCaseList)
				, array_keys($theNextLevelUpUpgradeSettingCaseList)
				, array_keys($theConditionMetList)
				, array_keys($theMetInFormulaCaseList) // options, its should be combined with theAccumulationCaseList
				// , array_keys($theNextLevelUpAccumulationCaseList) // options
				// , array_keys($theNextLevelUpMetInFormulaCaseList) // options, its should be combined with theNextLevelUpAccumulationCaseList
				, array_keys($theMultipleUpgradeEnableModeCaseList)
			];
		}else if($isIgnoreAccumulationCases == 1){
			/// for test OGP-24903
			$theCommonSeparateModeCaseList = [];
			// pass_at_220211_CACB
			// $theCommonSeparateModeCaseList['CACB']= 'SBE has set Common Accumulation + Common Bet Amount'; // 'tryDowngradeSuccessInCACBTriggerFromCronjob';
			$theCommonSeparateModeCaseList['SACB']= 'SBE has set Separate Accumulation + Common Bet Amount'; // 'tryDowngradeSuccessInSACBTriggerFromCronjob';
			// $theCommonSeparateModeCaseList['CASB']= 'SBE has set Common Accumulation + Separate Bet Amount'; // 'tryDowngradeSuccessInCASBTriggerFromCronjob';
			// $theCommonSeparateModeCaseList['SASB']= 'SBE has set Separate Accumulation + Separate Bet Amount'; // 'tryDowngradeSuccessInSASBTriggerFromCronjob';


			// override $thePeriodModeCaseList
			$thePeriodModeCaseList = []; // for $periodMode,$periodValue
			// $thePeriodModeCaseList['EmptyPeriodMode'] = 'The Period setting is Empty'; // PeriodMode is empty
			// $thePeriodModeCaseList['DailyPeriodMode'] = 'The Period setting is daily, "00:00:00 - 23:59:59".';// 'daily,00:00:00 - 23:59:59';
			// pass_at_220210
			$thePeriodModeCaseList['Weekly1PeriodMode'] = 'The Period setting is weekly, Monday.';// 'weekly,1'; // Monday Tuesday Wednesday Thursday Friday Saturday Sunday
			// $thePeriodModeCaseList['Monthly5PeriodMode'] = 'The Period setting is Monthly, 5th.';// 'monthly,1'; // 1 ~ 31
			$theNextLevelUpPeriodModeCaseList = $this->utils4testogp->genNextLevelList4CaseKindList($thePeriodModeCaseList);

			// thePeriodIsMetCaseList
			$thePeriodIsMetCaseList = [];
			$thePeriodIsMetCaseList['PeriodIsMet'] = 'The check time has Met with the Period setting';
			// $thePeriodIsMetCaseList['PeriodNotMet'] = 'The check time has Not Met with the Period setting';
			$theNextLevelUpPeriodIsMetCaseList = $this->utils4testogp->genNextLevelList4CaseKindList($thePeriodIsMetCaseList);

			// override $theMetInFormulaCaseList
			/// All items: bet, deposit, loss and win
			$theMetInFormulaCaseList = [];// There are no items that satisfy the formula
			// $theMetInFormulaCaseList['AllNoMetInFormula'] = 'No items are met In Formula, all items had set.';// Formula, "bet < 0 and deposit < 0 and loss < 0 and win < 0"
			// $theMetInFormulaCaseList['OnlyMetBetOfAllInFormula'] = 'The Formula only met bet, all items had set.'; // Formula, "bet >= 0 and deposit < 0 and loss < 0 and win < 0"
			// $theMetInFormulaCaseList['OnlyMetDepositOfAllInFormula'] = 'The Formula only met deposit, all items had set.'; // Formula, "bet < 0 and deposit >= 0 and loss < 0 and win < 0"
			$theMetInFormulaCaseList['OnlyMetBetDepositOfAllInFormula'] = 'The Formula only met bet and deposit, all items had set.'; // Formula, "bet >= 0 and deposit >= 0 and loss < 0 and win < 0"
			// $theMetInFormulaCaseList['OnlyMetBetDepositWinOfAllInFormula'] = 'The Formula only met bet, deposit and win, all items had set.'; // Formula, "bet >= 0 and deposit >= 0 and loss < 0 and win >= 0"
			// $theMetInFormulaCaseList['AllMetInFormula'] = 'All items are met In Formula, all items had set.'; // Formula, "bet >= 0 and deposit >= 0 and loss >= 0 and win >= 0"
			// $theMetInFormulaCaseList['OnlyMetBetOfBetInFormula'] = 'The Formula only met bet, and only bet had set.'; // Formula, "bet >= 0"
			$theMetInFormulaCaseList['OnlyMetBetOfBetDepositInFormula'] = 'The Formula only met bet, and bet and deposit had set.'; // Formula, "bet >= 0 and deposit < 0 "
			///  replace the cases(x8), EmptyUpgradeSetting, DepositUpgradeSetting, BetUpgradeSetting and DepositBetUpgradeSetting to the followings,
			// $theMetInFormulaCaseList['EmptyInFormula'] = 'The empty Formula.'; // aka. EmptyUpgradeSetting. Formula, ""
			// $theMetInFormulaCaseList['OnlyMetDepositOfDepositInFormula'] = 'The Formula met deposit and only had set the deposit.'; // aka. DepositUpgradeSetting+ IsConditionMet. Formula, "deposit >= 0"
			// $theMetInFormulaCaseList['NotMetDepositOfDepositInFormula'] = 'The Formula Not met deposit and only had set the deposit.'; // aka. DepositUpgradeSetting+ IsConditionNotMet. Formula, "deposit <= 0"
			// disabel for duplicate, $theMetInFormulaCaseList['OnlyMetBetOfBetInFormula'] = 'The Formula met bet and only had set the bet.'; // aka. BetUpgradeSetting+ IsConditionMet. Formula, "bet >= 0"
			// $theMetInFormulaCaseList['NotMetBetOfBetInFormula'] = 'The Formula Not met bet and only had set the bet.'; // aka. BetUpgradeSetting+ IsConditionNotMet. Formula, "bet <= 0"
			// $theMetInFormulaCaseList['OnlyMetBetOfBetDepositInFormula'] = ''; // aka. DepositBetUpgradeSetting+ IsConditionMet. Formula, "bet >= 0 and deposit <= 0"
			$theMetInFormulaCaseList['OnlyMetDepositOfBetDepositInFormula'] = 'The Formula Only met deposit and that had set the bet and deposit.'; // aka. DepositBetUpgradeSetting+ IsConditionNotMet. Formula, "bet <= 0 and deposit >= 0"
			// $theMetInFormulaCaseList['NoMetAllOfBetDepositInFormula'] = 'The Formula Not met any condition and that had set the bet and deposit.'; // aka. DepositBetUpgradeSetting+ IsConditionNotMet. Formula, "bet <= 0 and deposit <= 0"
			$theMetInFormulaCaseList['AllMetOfBetDepositInFormula'] = 'The Formula All met and that had set the bet and deposit.'; // aka. DepositBetUpgradeSetting+ IsConditionMet. Formula, "bet >= 0 and deposit >= 0"
			$theNextLevelUpMetInFormulaCaseList = $this->utils4testogp->genNextLevelList4CaseKindList($theMetInFormulaCaseList);

			// override $theAccumulationCaseList
			$theAccumulationCaseList = [];
			// $theAccumulationCaseList['NoAccumulation'] = 'Accumulation Setup to No';
			// $theAccumulationCaseList['AccumulationYesRegistrationDate'] = 'Accumulation Setup to Yes,Registration Date';
			// $theAccumulationCaseList['AccumulationYesLastChangePeriod'] = 'Accumulation Setup to Yes,Last Change Period';
			$theAccumulationCaseList['AccumulationLastChangePeriodResetIfMet'] = 'Accumulation Setup to Last Change Period,Reset If Met'; // OGP-24373
			// $theAccumulationCaseList['AccumulationYesLastChangePeriodResetAlways'] = 'Accumulation Setup to Yes,Last Change Period,Reset always'; // OGP-24373

			$theMultipleUpgradeEnableModeCaseList = [];
			$theMultipleUpgradeEnableModeCaseList['OnMultipleUpgradeEnable'] = 'Has Enable Multiple Upgrade Feature';
			$theMultipleUpgradeEnableModeCaseList['OffMultipleUpgradeEnable'] = 'Has Disable Multiple Upgrade Feature';



			$testCaseComboList = [ array_keys($theCommonSeparateModeCaseList)
				, array_keys($thePeriodModeCaseList)
				, array_keys($theNextLevelUpPeriodModeCaseList) // options
				, array_keys($theMetedInPreconditionsCaseList)
				, array_keys($thePeriodIsMetCaseList)
				, array_keys($theNextLevelUpPeriodIsMetCaseList) // options
				, array_keys($theHourlyCheckUpgradeEnableCaseList)
				// , array_keys($theLevelMaintainEnableCaseList)
				// , array_keys($theLevelMaintainTimeCaseList)
				// , array_keys($theLevelMaintainConditionCaseList)
				, array_keys($theAccumulationCaseList)
				// theUpgradeSettingCaseList deprecated, and renew in theMetInFormulaCaseList.
				// , array_keys($theUpgradeSettingCaseList)
				// , array_keys($theNextLevelUpUpgradeSettingCaseList)
				// , array_keys($theConditionMetList)
				, array_keys($theMetInFormulaCaseList) // options, its should be combined with theAccumulationCaseList
				, array_keys($theNextLevelUpMetInFormulaCaseList) // options, its should be combined with theNextLevelUpAccumulationCaseList
				// , array_keys($theNextLevelUpAccumulationCaseList) // options
				, array_keys($theMultipleUpgradeEnableModeCaseList)
			];
		}else if($isIgnoreAccumulationCases == 2){
			// for test OGP-24903. others
			$theCommonSeparateModeCaseList = [];
			$theCommonSeparateModeCaseList['CACB']= 'SBE has set Common Accumulation + Common Bet Amount'; // 'tryDowngradeSuccessInCACBTriggerFromCronjob';
			// $theCommonSeparateModeCaseList['SACB']= 'SBE has set Separate Accumulation + Common Bet Amount'; // 'tryDowngradeSuccessInSACBTriggerFromCronjob';
			$theCommonSeparateModeCaseList['CASB']= 'SBE has set Common Accumulation + Separate Bet Amount'; // 'tryDowngradeSuccessInCASBTriggerFromCronjob';
			$theCommonSeparateModeCaseList['SASB']= 'SBE has set Separate Accumulation + Separate Bet Amount'; // 'tryDowngradeSuccessInSASBTriggerFromCronjob';

			$thePeriodModeCaseList = [];
			// $thePeriodModeCaseList['EmptyPeriodMode'] = 'The Period setting is Empty'; // PeriodMode is empty
			// $thePeriodModeCaseList['DailyPeriodMode'] = 'The Period setting is daily, "00:00:00 - 23:59:59".';// 'daily,00:00:00 - 23:59:59';
			$thePeriodModeCaseList['Weekly1PeriodMode'] = 'The Period setting is weekly, Monday.';// 'weekly,1'; // Monday Tuesday Wednesday Thursday Friday Saturday Sunday
			// $thePeriodModeCaseList['Monthly5PeriodMode'] = 'The Period setting is Monthly, 5th.';// 'monthly,1'; // 1 ~ 31
			$theNextLevelUpPeriodModeCaseList = $this->utils4testogp->genNextLevelList4CaseKindList($thePeriodModeCaseList);

			// override $theAccumulationCaseList
			$theAccumulationCaseList = [];
			$theAccumulationCaseList['NoAccumulation'] = 'Accumulation Setup to No';
			$theAccumulationCaseList['AccumulationYesRegistrationDate'] = 'Accumulation Setup to Yes,Registration Date';
			$theAccumulationCaseList['AccumulationYesLastChangePeriod'] = 'Accumulation Setup to Yes,Last Change Period';
			// $theAccumulationCaseList['AccumulationLastChangePeriodResetIfMet'] = 'Accumulation Setup to Last Change Period,Reset If Met'; // OGP-24373



			$theMetedInPreconditionsCaseList = [];
			$theMetedInPreconditionsCaseList['NeverMetedInPreconditions'] = 'Anyone had not Meted, for "Accumulation Yes,Last Change Period,Reset If Met".'; // OGP-24373
			$theMetedInPreconditionsCaseList['HadBetMetedInPreconditions'] = 'The Bet had Meted, for "Accumulation Yes,Last Change Period,Reset If Met".'; // OGP-24373
			$theMetedInPreconditionsCaseList['HadDepositMetedInPreconditions'] = 'The Deposit had Meted, for "Accumulation Yes,Last Change Period,Reset If Met".'; // OGP-24373
			$theMetedInPreconditionsCaseList['IngoreInPreconditions'] = 'The Deposit had Meted, for "Accumulation Yes,Last Change Period,Reset If Met".'; // OGP-24373


			// for test normal upgrade
			$testCaseComboList = [ array_keys($theCommonSeparateModeCaseList)
				, array_keys($thePeriodModeCaseList)
				, array_keys($theNextLevelUpPeriodModeCaseList) // options
				, array_keys($theMetedInPreconditionsCaseList)
				, array_keys($thePeriodIsMetCaseList)
				, array_keys($theNextLevelUpPeriodIsMetCaseList) // options
				, array_keys($theHourlyCheckUpgradeEnableCaseList)
				// , array_keys($theLevelMaintainEnableCaseList)
				// , array_keys($theLevelMaintainTimeCaseList)
				// , array_keys($theLevelMaintainConditionCaseList)
				, array_keys($theAccumulationCaseList)
				// , array_keys($theUpgradeSettingCaseList)
				// , array_keys($theConditionMetList)
				, array_keys($theMetInFormulaCaseList) // options, its should be combined with theAccumulationCaseList
				// , array_keys($theNextLevelUpAccumulationCaseList) // options
				// , array_keys($theNextLevelUpMetInFormulaCaseList) // options, its should be combined with theNextLevelUpAccumulationCaseList
				, array_keys($theMultipleUpgradeEnableModeCaseList)
			];
		}// EOF if($isIgnoreAccumulationCases){...
		$testCaseCombinedList = $this->utils4testogp->combination_arr( $testCaseComboList );
		$testCaseList = [];
		foreach($testCaseCombinedList as $indexNumber => $testCase){
			$testCaseList[] = implode('.',$testCase);
		}
		// ignore the specific test cases.
		$ignoreTestCaseKeyList = $this->utils4testogp->_getIngoreCombinedCases4upgrade(); // @todo

		$filtedTestCaseList = array_filter($testCaseList, function($v, $k) use ( $ignoreTestCaseKeyList){
			// echo 'k:'.$k;echo PHP_EOL;
			return ! in_array($v, $ignoreTestCaseKeyList);
		}, ARRAY_FILTER_USE_BOTH);
		$filtedTestCaseList = array_values($filtedTestCaseList);
		//
		// ignore the specific test cases by keyword.
		$filtedTestCaseList = array_filter($filtedTestCaseList, function($v, $k) use ( $ignoreTestCaseKeyList){
			// echo 'k:'.$k;echo PHP_EOL;
			$isIngore = $this->utils4testogp->_isIngoreCombinedCases4upgrade($v); // will call utils4testogp::_isIngoreCombinedCases4upgrade()
			return ! $isIngore;
		}, ARRAY_FILTER_USE_BOTH);
		// ignore the specific test cases by keyword in ogp24373.
		$filtedTestCaseList = array_filter($filtedTestCaseList, function($v, $k) use ( $ignoreTestCaseKeyList){
			// echo 'k:'.$k;echo PHP_EOL;
			$isIngore = $this->_isIngoreCombinedCases4upgrade($v); // will call testing_ogp24373::_isIngoreCombinedCases4upgrade()
			return ! $isIngore;
		}, ARRAY_FILTER_USE_BOTH);

		/// After array_filter(), how can I reset the keys to go in numerical order starting at 0
 		// https://stackoverflow.com/a/3401863
		$filtedTestCaseList = array_values($filtedTestCaseList);

		$checkFrom = 'Cronjob';
		if($isTriggerFromHourlyCronjob){
			$checkFrom = 'CronjobHourly';
		}else if( ! empty($manual_batch) ){
			$checkFrom = 'SBE';
		}
		// echo 'checkFrom:'. $checkFrom;
		$filtedTestCaseListATCF = [];
		foreach($filtedTestCaseList as $indexNumber => $testCase ){

			$theTestConditionFn = $this->_getTestConditionFnFromCombinedCase4ogp24373upgrade($testCase, $checkFrom);

			$isBeforeDiffAfter = $this->utils4testogp->_pos($theTestConditionFn, 'beforeDiffAfter' );
			$isBeforeDiffOneMoreAsAfter = $this->utils4testogp->_pos($theTestConditionFn, 'beforeDiffOneMoreAsAfter' );
			$isBeforeSameAsAfter = $this->utils4testogp->_pos($theTestConditionFn, 'beforeSameAsAfter' );
			if($isBeforeSameAsAfter){
				$testCase .= '.ExpectedNoUpgrade';
			} else if ($isBeforeDiffAfter){
				$testCase .= '.ExpectedUpgrade';
			} else if ($isBeforeDiffOneMoreAsAfter){
				$testCase .= '.ExpectedMultiUpgrade';
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

	} // EOF test_getCombinedCaseList4upgrade
	public function _isIngoreCombinedCases4upgrade($theCombinedCase){
		$isIngore = null;
		/// Contains dot,
		// because the string,"NextLevelUpDailyPeriodMode" has the keyword,"DailyPeriodMode".
		// It is misjudgment the the keyword,"DailyPeriodMode".

		// $isEmptyPeriodMode = $this->_pos($theCombinedCase, '.EmptyPeriodMode');
		// $isDailyPeriodMode = $this->_pos($theCombinedCase, '.DailyPeriodMode');
		// $isWeekly1PeriodMode = $this->_pos($theCombinedCase, '.Weekly1PeriodMode');
		// $isMonthly5PeriodMode = $this->_pos($theCombinedCase, '.Monthly5PeriodMode');

		// $isPeriodIsMet = $this->_pos($theCombinedCase, '.PeriodIsMet');
		// $isPeriodNotMet = $this->_pos($theCombinedCase, '.PeriodNotMet');

		// $isNextLevelUpEmptyPeriodMode = $this->_pos($theCombinedCase, '.NextLevelUpEmptyPeriodMode');
		// $isNextLevelUpDailyPeriodMode = $this->_pos($theCombinedCase, '.NextLevelUpDailyPeriodMode');
		// $isNextLevelUpWeekly1PeriodMode = $this->_pos($theCombinedCase, '.NextLevelUpWeekly1PeriodMode');
		// $isNextLevelUpMonthly5PeriodMode = $this->_pos($theCombinedCase, '.NextLevelUpMonthly5PeriodMode');

		// $isNextLevelUpPeriodIsMet = $this->_pos($theCombinedCase, '.NextLevelUpPeriodIsMet');
		// $isNextLevelUpPeriodNotMet = $this->_pos($theCombinedCase, '.NextLevelUpPeriodNotMet.');


		$isNeverMetedInPreconditions = $this->utils4testogp->_pos($theCombinedCase, 'NeverMetedInPreconditions');
		$isHadBetMetedInPreconditions = $this->utils4testogp->_pos($theCombinedCase, 'HadBetMetedInPreconditions');
		$isHadDepositMetedInPreconditions = $this->utils4testogp->_pos($theCombinedCase, 'HadDepositMetedInPreconditions');


		// theAccumulationCaseList: NoAccumulation, AccumulationYesRegistrationDate, AccumulationYesLastChangePeriod, AccumulationLastChangePeriodResetIfMet
		$isNoAccumulation = $this->utils4testogp->_pos($theCombinedCase, '.NoAccumulation');
		$isAccumulationYesRegistrationDate = $this->utils4testogp->_pos($theCombinedCase, '.AccumulationYesRegistrationDate');
		$isAccumulationYesLastChangePeriod = $this->utils4testogp->_pos($theCombinedCase, '.AccumulationYesLastChangePeriod');
		$isAccumulationLastChangePeriodResetIfMet = $this->utils4testogp->_pos($theCombinedCase, '.AccumulationLastChangePeriodResetIfMet');

$detectCase = null;
$detectCase = 'CACB.DailyPeriodMode.NextLevelUpDailyPeriodMode.NeverMetedInPreconditions.PeriodIsMet.NextLevelUpPeriodIsMet.OnHourlyCheckUpgradeEnable.NoAccumulation.AllNoMetInFormula.OffMultipleUpgradeEnable';
if( $theCombinedCase == $detectCase){
	$toDebuglog = true;
}else{
	$toDebuglog = false;
}
if($toDebuglog)print_r(['1337.isNoAccumulation', $isNoAccumulation
					, 'isAccumulationYesRegistrationDate:', $isAccumulationYesRegistrationDate
					, 'isAccumulationYesLastChangePeriod:', $isAccumulationYesLastChangePeriod
					, 'isAccumulationLastChangePeriodResetIfMet:', $isAccumulationLastChangePeriodResetIfMet
					, 'isNeverMetedInPreconditions:', $isNeverMetedInPreconditions
					, 'isHadBetMetedInPreconditions:', $isHadBetMetedInPreconditions
					, 'isHadDepositMetedInPreconditions:', $isHadDepositMetedInPreconditions
					, 'theCombinedCase:', $theCombinedCase
						]);

		$isIngore = false; // default

		// @todo, 這兩者不用互搭： HadDepositMetedInPreconditions, AccumulationYesLastChangePeriod 。 xxxInPreconditions 只限制搭配於 AccumulationLastChangePeriodResetIfMet
		if( ( $isNoAccumulation
				|| $isAccumulationYesRegistrationDate
				|| $isAccumulationYesLastChangePeriod
				|| $isAccumulationLastChangePeriodResetIfMet
			) && ( $isNeverMetedInPreconditions
				|| $isHadBetMetedInPreconditions
				|| $isHadDepositMetedInPreconditions
			)
		){// If Accumulation and xxxInPreconditions had specified
			if( $isAccumulationLastChangePeriodResetIfMet && ( $isNeverMetedInPreconditions
				|| $isHadBetMetedInPreconditions
				|| $isHadDepositMetedInPreconditions
			) ){
				// xxxInPreconditions only for AccumulationLastChangePeriodResetIfMet
			}else{
				// unnecessary cases.
				$isIngore = true;
			}

		}
		// if($isDailyPeriodMode && $isPeriodNotMet){
		// 	// impossible cases.
		// 	$isIngore = true;
		// }
		// if($isNextLevelUpDailyPeriodMode && $isNextLevelUpPeriodNotMet){
		// 	// impossible cases.
		// 	$isIngore = true;
		// }
		// $detectCase = null;
		// // $detectCase = 'CACB.Weekly1PeriodMode.NextLevelUpWeekly1PeriodMode.PeriodNotMet.NextLevelUpPeriodIsMet.OnHourlyCheckUpgradeEnable.OnMultipleUpgradeEnable';
		// if( $theCombinedCase == $detectCase){
		// 	$toDebuglog = true;
		// }else{
		// 	$toDebuglog = false;
		// }
		// if($toDebuglog)print_r(['1388.isPeriodIsMet', $isPeriodIsMet
		// 						, 'isNextLevelUpPeriodIsMet:', $isNextLevelUpPeriodIsMet
		// 						, 'isDailyPeriodMode:', $isDailyPeriodMode
		// 						, 'isNextLevelUpDailyPeriodMode:', $isNextLevelUpDailyPeriodMode
		// 						, 'isWeekly1PeriodMode:', $isWeekly1PeriodMode
		// 						, 'isNextLevelUpWeekly1PeriodMode:', $isNextLevelUpWeekly1PeriodMode

		// 						, 'isMonthly5PeriodMode:', $isMonthly5PeriodMode
		// 						, 'isNextLevelUpMonthly5PeriodMode:', $isNextLevelUpMonthly5PeriodMode
		// 						]);
		// // the impassible case, isPeriodIsMet and ! isNextLevelUpPeriodIsMet then PeriodMode != NextLevelUpPeriodMode
		// // if( $isPeriodIsMet && ! $isNextLevelUpPeriodIsMet ){
		// if( $isPeriodIsMet != $isNextLevelUpPeriodIsMet ){
		// 	if( ($isDailyPeriodMode == true && $isNextLevelUpDailyPeriodMode == true)
		// 		|| ($isWeekly1PeriodMode == true && $isNextLevelUpWeekly1PeriodMode == true)
		// 		|| ($isMonthly5PeriodMode == true && $isNextLevelUpMonthly5PeriodMode == true)
		// 	){
		// 		$isIngore = true;
		// 	}
		// }
		return $isIngore;
	} // EOF _isIngoreCombinedCases4upgrade

	///
	// 進入點：php public/index.php cli/testing_ogp24373/index/test_UpgradeFromCronjob 54169 3 0 1 | w3m -T text/html > ../logs/ogp24373.54169.test_UpgradeFromCronjob.log &
	// 需要
	public function DEL_test_getCombinedCaseList4upgradeReq( $doOutputHtml = true
						, $isAppendTestConditionFn = false
						, $isTriggerFromHourlyCronjob = false
						, $isIgnoreAccumulationCases = false
						, $manual_batch = false // #5 If false means batch, if true means manual.
	){ // test_DwongradeMonthly(){ //

		// theCommonSeparateModeCaseList: CACB, SACB, CASB, SASB
		// thePeriodModeCaseList: EmptyPeriodMode, DailyPeriodMode, Weekly1PeriodMode, Monthly5PeriodMode
		// theNextLevelUpPeriodModeCaseList: NextLevelUpEmptyPeriodMode, NextLevelUpDailyPeriodMode, NextLevelUpWeekly1PeriodMode, NextLevelUpMonthly5PeriodMode
		// thePeriodIsMetCaseList: PeriodIsMet, PeriodNotMet
		// theNextLevelUpPeriodIsMetCaseList: NextLevelUpPeriodIsMet, NextLevelUpPeriodNotMet
		// theAccumulationCaseList: NoAccumulation, AccumulationYesRegistrationDate, AccumulationYesLastChangePeriod, AccumulationLastChangePeriodResetIfMet
		// theNextLevelUpAccumulationCaseList: NextLevelUpNoAccumulation, NextLevelUpAccumulationYesRegistrationDate, NextLevelUpAccumulationYesLastChangePeriod, NextLevelUpAccumulationLastChangePeriodResetIfMet
		// theHourlyCheckUpgradeEnableCaseList: OnHourlyCheckUpgradeEnable, OffHourlyCheckUpgradeEnable
		// theMultipleUpgradeEnableModeCaseList: OnMultipleUpgradeEnable, OffMultipleUpgradeEnable

		// _assignCaseKindList4ogp24373
		$theCaseKindList = $this->utils4testogp->_assignCaseKindList();
		$theCommonSeparateModeCaseList = $theCaseKindList['theCommonSeparateModeCaseList'];
		$thePeriodModeCaseList = $theCaseKindList['thePeriodModeCaseList'];
		$thePeriodIsMetCaseList = $theCaseKindList['thePeriodIsMetCaseList'];

		$theMultipleUpgradeEnableModeCaseList = $theCaseKindList['theMultipleUpgradeEnableModeCaseList'];

		// $theNextLevelUpUpgradeSettingCaseList = $theCaseKindList['theNextLevelUpUpgradeSettingCaseList']; // 與 theNextLevelUpPeriodModeCaseList 重複定義。
		$theNextLevelUpPeriodModeCaseList = $theCaseKindList['theNextLevelUpPeriodModeCaseList'];
		$theNextLevelUpPeriodIsMetCaseList = $theCaseKindList['theNextLevelUpPeriodIsMetCaseList'];
		$theNextLevelUpAccumulationCaseList = $theCaseKindList['theNextLevelUpAccumulationCaseList'];
		$theHourlyCheckUpgradeEnableCaseList = $theCaseKindList['theHourlyCheckUpgradeEnableCaseList'];

		// theNextLevelUpUpgradeSettingCaseList
		// theNextLevelUpPeriodModeCaseList
		// theNextLevelUpPeriodIsMetCaseList
		// theNextLevelUpAccumulationCaseList

		// $theLevelMaintainEnableCaseList = $theCaseKindList['theLevelMaintainEnableCaseList'];
		// $theLevelMaintainTimeCaseList = $theCaseKindList['theLevelMaintainTimeCaseList'];
		// $theLevelMaintainConditionCaseList = $theCaseKindList['theLevelMaintainConditionCaseList'];
		$theAccumulationCaseList = $theCaseKindList['theAccumulationCaseList'];

		//ignore some case of the env.
		$thePeriodModeCaseList['EmptyPeriodMode'] = null;
		unset($thePeriodModeCaseList['EmptyPeriodMode']);
		$theNextLevelUpPeriodModeCaseList['NextLevelUpEmptyPeriodMode'] = null;
		unset($theNextLevelUpPeriodModeCaseList['NextLevelUpEmptyPeriodMode']);

		$isIgnoreAccumulationCases = true; // The test cases amount is 400 , if ignore Some Cases.
		// 從上面的組合 轉成各種測試情境
		$testCaseComboList = [ array_keys($theCommonSeparateModeCaseList)
			, array_keys($thePeriodModeCaseList)
			, array_keys($theNextLevelUpPeriodModeCaseList)
			, array_keys($thePeriodIsMetCaseList)
			, array_keys($theNextLevelUpPeriodIsMetCaseList)
			, array_keys($theHourlyCheckUpgradeEnableCaseList)
			// , array_keys($theLevelMaintainEnableCaseList)
			// , array_keys($theLevelMaintainTimeCaseList)
			// , array_keys($theLevelMaintainConditionCaseList)
			, array_keys($theAccumulationCaseList) // options
			, array_keys($theNextLevelUpAccumulationCaseList) // options
			, array_keys($theMultipleUpgradeEnableModeCaseList)
		]; // The test cases amount is 3000 more.
		if($isIgnoreAccumulationCases){

			// override $thePeriodModeCaseList
			$thePeriodModeCaseList = []; // for $periodMode,$periodValue
			// $thePeriodModeCaseList['EmptyPeriodMode'] = 'The Period setting is Empty'; // PeriodMode is empty
			$thePeriodModeCaseList['DailyPeriodMode'] = 'The Period setting is daily, "00:00:00 - 23:59:59".';// 'daily,00:00:00 - 23:59:59';
			$thePeriodModeCaseList['Weekly1PeriodMode'] = 'The Period setting is weekly, Monday.';// 'weekly,1'; // Monday Tuesday Wednesday Thursday Friday Saturday Sunday
			$thePeriodModeCaseList['Monthly5PeriodMode'] = 'The Period setting is Monthly, 5th.';// 'monthly,1'; // 1 ~ 31

			// override $theAccumulationCaseList
			$theAccumulationCaseList = [];
			// $theAccumulationCaseList['NoAccumulation'] = 'Accumulation Setup to No';
			// $theAccumulationCaseList['AccumulationYesRegistrationDate'] = 'Accumulation Setup to Yes,Registration Date';
			$theAccumulationCaseList['AccumulationYesLastChangePeriod'] = 'Accumulation Setup to Yes,Last Change Period';
			$theAccumulationCaseList['AccumulationLastChangePeriodResetIfMet'] = 'Accumulation Setup to Last Change Period,Reset If Met'; // OGP-24373
			// $theAccumulationCaseList['AccumulationYesLastChangePeriodResetAlways'] = 'Accumulation Setup to Yes,Last Change Period,Reset always'; // OGP-24373


			$testCaseComboList = [ array_keys($theCommonSeparateModeCaseList)
				, array_keys($thePeriodModeCaseList)
				, array_keys($theNextLevelUpPeriodModeCaseList) // options
				, array_keys($thePeriodIsMetCaseList)
				, array_keys($theNextLevelUpPeriodIsMetCaseList) // options
				// , array_keys($theHourlyCheckUpgradeEnableCaseList)
				// , array_keys($theLevelMaintainEnableCaseList)
				// , array_keys($theLevelMaintainTimeCaseList)
				// , array_keys($theLevelMaintainConditionCaseList)
				, array_keys($theAccumulationCaseList)
				// , array_keys($theNextLevelUpAccumulationCaseList) // options
				, array_keys($theMultipleUpgradeEnableModeCaseList)
			];
		}// EOF if($isIgnoreAccumulationCases){...
		$testCaseCombinedList = $this->utils4testogp->combination_arr( $testCaseComboList );
		$testCaseList = [];
		foreach($testCaseCombinedList as $indexNumber => $testCase){
			$testCaseList[] = implode('.',$testCase);
		}
		// ignore the specific test cases.
		$ignoreTestCaseKeyList = $this->utils4testogp->_getIngoreCombinedCases4upgrade(); // @todo
		$filtedTestCaseList = array_filter($testCaseList, function($v, $k) use ( $ignoreTestCaseKeyList){
			// echo 'k:'.$k;echo PHP_EOL;
			return ! in_array($v, $ignoreTestCaseKeyList);
		}, ARRAY_FILTER_USE_BOTH);
		$filtedTestCaseList = array_values($filtedTestCaseList);
		//
		// ignore the specific test cases by keyword.
		$filtedTestCaseList = array_filter($filtedTestCaseList, function($v, $k) use ( $ignoreTestCaseKeyList){
			// echo 'k:'.$k;echo PHP_EOL;
			$isIngore = $this->utils4testogp->_isIngoreCombinedCases4upgrade($v);
			return ! $isIngore;
		}, ARRAY_FILTER_USE_BOTH);

		/// After array_filter(), how can I reset the keys to go in numerical order starting at 0
 		// https://stackoverflow.com/a/3401863
		$filtedTestCaseList = array_values($filtedTestCaseList);

		$checkFrom = 'Cronjob';
		if($isTriggerFromHourlyCronjob){
			$checkFrom = 'CronjobHourly';
		}else if( ! empty($manual_batch) ){
			$checkFrom = 'SBE';
		}
		// echo 'checkFrom:'. $checkFrom;
		foreach($filtedTestCaseList as $indexNumber => $testCase ){

			$theTestConditionFn = $this->_getTestConditionFnFromCombinedCase4ogp24373upgrade($testCase, $checkFrom);

			$isBeforeDiffAfter = $this->utils4testogp->_pos($theTestConditionFn, 'beforeDiffAfter' );
			$isBeforeDiffOneMoreAsAfter = $this->utils4testogp->_pos($theTestConditionFn, 'beforeDiffOneMoreAsAfter' );
			$isBeforeSameAsAfter = $this->utils4testogp->_pos($theTestConditionFn, 'beforeSameAsAfter' );
			if($isBeforeSameAsAfter){
				$testCase .= '.ExpectedNoUpgrade';
			} else if ($isBeforeDiffAfter){
				$testCase .= '.ExpectedUpgrade';
			} else if ($isBeforeDiffOneMoreAsAfter){
				$testCase .= '.ExpectedMultiUpgrade';
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

	} // EOF test_getCombinedCaseList4upgrade

	// cloned form _getTestConditionFnFromCombinedCase4ogp21051upgrade
	/**
	 * For test_getCombinedCaseList4upgrade(),
	 * Get the test Function, for to know if the feedback result is expected.
	 *
	 * @see test_getCombinedCaseList4upgrade()
	 *
	 * @param string $theCombinedCase The Case String. ex: "CACB.DailyPeriodMode.NextLevelUpWeekly1PeriodMode.PeriodIsMet.NextLevelUpPeriodIsMet.AccumulationYesLastChangePeriod.OnlyMetBetOfAllInFormula.OffMultipleUpgradeEnable"
	 * @param string $checkFrom The trigger source of the upgrade condition checking. ex: Cronjob, CronjobHourly, SBE.
	 * @return void
	 */
	public function _getTestConditionFnFromCombinedCase4ogp24373upgrade($theCombinedCase = 'CACB.EmptyPeriodMode.PeriodNotMet.OnLevelMaintainEnab', $checkFrom = 'Cronjob' ){
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
		$testConditionFn = '_testConditionFn4beforeDiffAfterV2'; // should be downgraded/upgraded (changed)
		// _testConditionFn4beforeSameAsAfterV2
		// _testConditionFn4beforeDiffOneMoreAsAfterV2

		$isPeriodNotMet = $this->utils4testogp->_pos($theCombinedCase, '.PeriodNotMet');
		$isPeriodIsMet = $this->utils4testogp->_pos($theCombinedCase, '.PeriodIsMet');

		$isEmptyPeriodMode = $this->utils4testogp->_pos($theCombinedCase, '.EmptyPeriodMode');
		$isDailyPeriodMode = $this->utils4testogp->_pos($theCombinedCase, '.DailyPeriodMode');
		$isWeekly1PeriodMode = $this->utils4testogp->_pos($theCombinedCase, '.Weekly1PeriodMode');
		$isMonthly5PeriodMode = $this->utils4testogp->_pos($theCombinedCase, '.Monthly5PeriodMode');

		$isNextLevelUpPeriodIsMet = $this->utils4testogp->_pos($theCombinedCase, 'NextLevelUpPeriodIsMet');
		$isNextLevelUpPeriodNotMet = $this->utils4testogp->_pos($theCombinedCase, 'NextLevelUpPeriodNotMet');

		$isNextLevelUpEmptyPeriodMode = $this->utils4testogp->_pos($theCombinedCase, 'NextLevelUpEmptyPeriodMode');
		$isNextLevelUpDailyPeriodMode = $this->utils4testogp->_pos($theCombinedCase, 'NextLevelUpDailyPeriodMode');
		$isNextLevelUpWeekly1PeriodMode = $this->utils4testogp->_pos($theCombinedCase, 'NextLevelUpWeekly1PeriodMode');
		$isNextLevelUpMonthly5PeriodMode = $this->utils4testogp->_pos($theCombinedCase, 'NextLevelUpMonthly5PeriodMode');

		$isNoAccumulation = $this->utils4testogp->_pos($theCombinedCase, 'NoAccumulation');
		$isAccumulationYesRegistrationDate = $this->utils4testogp->_pos($theCombinedCase, 'AccumulationYesRegistrationDate');
		$isAccumulationYesLastChangePeriod = $this->utils4testogp->_pos($theCombinedCase, 'AccumulationYesLastChangePeriod');
		$isAccumulationLastChangePeriodResetIfMet = $this->utils4testogp->_pos($theCombinedCase, 'AccumulationLastChangePeriodResetIfMet');

		$isEmptyUpgradeSetting = $this->utils4testogp->_pos($theCombinedCase, '.EmptyUpgradeSetting');
		$isDepositUpgradeSetting = $this->utils4testogp->_pos($theCombinedCase, '.DepositUpgradeSetting');
		$isBetUpgradeSetting = $this->utils4testogp->_pos($theCombinedCase, '.BetUpgradeSetting');
		$isDepositBetUpgradeSetting = $this->utils4testogp->_pos($theCombinedCase, '.DepositBetUpgradeSetting');
		$isIsConditionMet = $this->utils4testogp->_pos($theCombinedCase, 'IsConditionMet');
		$isIsConditionNotMet = $this->utils4testogp->_pos($theCombinedCase, 'IsConditionNotMet');


		$isNeverMetedInPreconditions = $this->utils4testogp->_pos($theCombinedCase, 'NeverMetedInPreconditions');
		$isHadBetMetedInPreconditions = $this->utils4testogp->_pos($theCombinedCase, 'HadBetMetedInPreconditions');
		$isHadDepositMetedInPreconditions = $this->utils4testogp->_pos($theCombinedCase, 'HadDepositMetedInPreconditions');

		// theMetInFormulaCaseList
		$isAllNoMetInFormula = $this->utils4testogp->_pos($theCombinedCase, '.AllNoMetInFormula');
		$isOnlyMetBetOfAllInFormula = $this->utils4testogp->_pos($theCombinedCase, '.OnlyMetBetOfAllInFormula');
		$isOnlyMetDepositOfAllInFormula = $this->utils4testogp->_pos($theCombinedCase, '.OnlyMetDepositOfAllInFormula');
		$isOnlyMetBetDepositOfAllInFormula = $this->utils4testogp->_pos($theCombinedCase, '.OnlyMetBetDepositOfAllInFormula');
		$isOnlyMetBetDepositWinOfAllInFormula = $this->utils4testogp->_pos($theCombinedCase, '.OnlyMetBetDepositWinOfAllInFormula');
		$isAllMetInFormula = $this->utils4testogp->_pos($theCombinedCase, '.AllMetInFormula');
		$isOnlyMetBetOfBetInFormula = $this->utils4testogp->_pos($theCombinedCase, '.OnlyMetBetOfBetInFormula');
		$isOnlyMetBetOfBetDepositInFormula = $this->utils4testogp->_pos($theCombinedCase, '.OnlyMetBetOfBetDepositInFormula');
		///  replace the cases(x8), EmptyUpgradeSetting, DepositUpgradeSetting, BetUpgradeSetting and DepositBetUpgradeSetting to the followings,
		$isEmptyInFormula = $this->utils4testogp->_pos($theCombinedCase, '.EmptyInFormula');
		$isOnlyMetDepositOfDepositInFormula = $this->utils4testogp->_pos($theCombinedCase, '.OnlyMetDepositOfDepositInFormula');
		$isNotMetDepositOfDepositInFormula = $this->utils4testogp->_pos($theCombinedCase, '.NotMetDepositOfDepositInFormula');
		// $isOnlyMetBetOfBetInFormula = $this->utils4testogp->_pos($theCombinedCase, '.OnlyMetBetOfBetInFormula');
		$isNotMetBetOfBetInFormula = $this->utils4testogp->_pos($theCombinedCase, '.NotMetBetOfBetInFormula');
		$isOnlyMetDepositOfBetDepositInFormula = $this->utils4testogp->_pos($theCombinedCase, '.OnlyMetDepositOfBetDepositInFormula');
		$isNoMetAllOfBetDepositInFormula = $this->utils4testogp->_pos($theCombinedCase, '.NoMetAllOfBetDepositInFormula');
		$isAllMetOfBetDepositInFormula = $this->utils4testogp->_pos($theCombinedCase, '.AllMetOfBetDepositInFormula');

		$_isMetInFormulaCase = null;
		switch(true){
			case $isAllNoMetInFormula:
			case $isOnlyMetBetOfAllInFormula:
			case $isOnlyMetDepositOfAllInFormula:
			case $isOnlyMetBetDepositOfAllInFormula:
			case $isOnlyMetBetDepositWinOfAllInFormula:
			case $isOnlyMetBetOfBetDepositInFormula:
			case $isEmptyInFormula:
			case $isNotMetDepositOfDepositInFormula:
			case $isNotMetBetOfBetInFormula:
			case $isOnlyMetDepositOfBetDepositInFormula:
			case $isNoMetAllOfBetDepositInFormula:
				$_isMetInFormulaCase = false;
				break;
			case $isAllMetInFormula:
			case $isOnlyMetBetOfBetInFormula:
			case $isOnlyMetDepositOfDepositInFormula:
			case $isAllMetOfBetDepositInFormula:
				$_isMetInFormulaCase = true;
				break;
		}




		// theNextLevelUpMetInFormulaCaseList
		$isNextLevelUpAllNoMetInFormula = $this->utils4testogp->_pos($theCombinedCase, 'NextLevelUpAllNoMetInFormula');
		$isNextLevelUpOnlyMetBetOfAllInFormula = $this->utils4testogp->_pos($theCombinedCase, 'NextLevelUpOnlyMetBetOfAllInFormula');
		$isNextLevelUpOnlyMetDepositOfAllInFormula = $this->utils4testogp->_pos($theCombinedCase, 'NextLevelUpOnlyMetDepositOfAllInFormula');
		$isNextLevelUpOnlyMetBetDepositOfAllInFormula = $this->utils4testogp->_pos($theCombinedCase, 'NextLevelUpOnlyMetBetDepositOfAllInFormula');
		$isNextLevelUpAllMetInFormula = $this->utils4testogp->_pos($theCombinedCase, 'NextLevelUpAllMetInFormula');
		$isNextLevelUpOnlyMetBetOfBetDepositInFormula = $this->utils4testogp->_pos($theCombinedCase, 'NextLevelUpOnlyMetBetOfBetDepositInFormula');
		$isNextLevelUpOnlyMetBetDepositWinOfAllInFormula = $this->utils4testogp->_pos($theCombinedCase, 'NextLevelUpOnlyMetBetDepositWinOfAllInFormula');
		///  replace the cases(x8), EmptyUpgradeSetting, DepositUpgradeSetting, BetUpgradeSetting and DepositBetUpgradeSetting to the followings,
		$isNextLevelUpEmptyInFormula = $this->utils4testogp->_pos($theCombinedCase, 'NextLevelUpEmptyInFormula');
		$isNextLevelUpOnlyMetDepositOfDepositInFormula = $this->utils4testogp->_pos($theCombinedCase, 'NextLevelUpOnlyMetDepositOfDepositInFormula');
		$isNextLevelUpNotMetDepositOfDepositInFormula = $this->utils4testogp->_pos($theCombinedCase, 'NextLevelUpNotMetDepositOfDepositInFormula');
		$isNextLevelUpOnlyMetBetOfBetInFormula = $this->utils4testogp->_pos($theCombinedCase, 'NextLevelUpOnlyMetBetOfBetInFormula');
		$isNextLevelUpNotMetBetOfBetInFormula = $this->utils4testogp->_pos($theCombinedCase, 'NextLevelUpNotMetBetOfBetInFormula');
		$isNextLevelUpOnlyMetDepositOfBetDepositInFormula = $this->utils4testogp->_pos($theCombinedCase, 'NextLevelUpOnlyMetDepositOfBetDepositInFormula');
		$isNextLevelUpNoMetAllOfBetDepositInFormula = $this->utils4testogp->_pos($theCombinedCase, 'NextLevelUpNoMetAllOfBetDepositInFormula');
		$isNextLevelUpAllMetOfBetDepositInFormula = $this->utils4testogp->_pos($theCombinedCase, 'NextLevelUpAllMetOfBetDepositInFormula');

		$_isMetInFormulaCase4nextLevelUp = null;
		switch(true){
			case $isNextLevelUpAllNoMetInFormula:
			case $isNextLevelUpOnlyMetBetOfAllInFormula:
			case $isNextLevelUpOnlyMetDepositOfAllInFormula:
			case $isNextLevelUpOnlyMetBetDepositOfAllInFormula:
			case $isNextLevelUpOnlyMetBetDepositWinOfAllInFormula:
			case $isNextLevelUpOnlyMetBetOfBetDepositInFormula:
			case $isNextLevelUpEmptyInFormula:
			case $isNextLevelUpNotMetDepositOfDepositInFormula:
			case $isNextLevelUpNotMetBetOfBetInFormula:
			case $isNextLevelUpOnlyMetDepositOfBetDepositInFormula:
			case $isNextLevelUpNoMetAllOfBetDepositInFormula:
				$_isMetInFormulaCase4nextLevelUp = false;
				break;
			case $isNextLevelUpAllMetInFormula:
			case $isNextLevelUpOnlyMetBetOfBetInFormula:
			case $isNextLevelUpOnlyMetDepositOfDepositInFormula:
			case $isNextLevelUpAllMetOfBetDepositInFormula:
				$_isMetInFormulaCase4nextLevelUp = true;
				break;
		}



		$isOnHourlyCheckUpgradeEnable = $this->utils4testogp->_pos($theCombinedCase, 'OnHourlyCheckUpgradeEnable');
		$isOffHourlyCheckUpgradeEnable = $this->utils4testogp->_pos($theCombinedCase, 'OffHourlyCheckUpgradeEnable');

		$isOnMultipleUpgradeEnable = $this->utils4testogp->_pos($theCombinedCase, 'OnMultipleUpgradeEnable');
		$isOffMultipleUpgradeEnable = $this->utils4testogp->_pos($theCombinedCase, 'OffMultipleUpgradeEnable');

		/// catch the issue CASE for debug
		$toDebuglog = null;
		$detectCase = null;
		// $detectCase = 'CACB.Weekly1PeriodMode.NextLevelUpDailyPeriodMode.PeriodIsMet.NextLevelUpPeriodIsMet.OffHourlyCheckUpgradeEnable.AccumulationYesLastChangePeriod.NextLevelUpAccumulationYesLastChangePeriod.OnMultipleUpgradeEnable';
		// $detectCase = 'CACB.DailyPeriodMode.NextLevelUpDailyPeriodMode.PeriodIsMet.NextLevelUpPeriodIsMet.OffHourlyCheckUpgradeEnable.NoAccumulation.NextLevelUpAccumulationYesRegistrationDate.OnMultipleUpgradeEnable';
		// $detectCase = 'CACB.Weekly1PeriodMode.NextLevelUpDailyPeriodMode.PeriodNotMet.NextLevelUpPeriodIsMet.OnHourlyCheckUpgradeEnable.NoAccumulation.NextLevelUpNoAccumulation.OnMultipleUpgradeEnable';
		//
		// // $detectCase = 'CACB.DailyPeriodMode.NextLevelUpDailyPeriodMode.PeriodIsMet.NextLevelUpPeriodIsMet.OnHourlyCheckUpgradeEnable.AccumulationYesLastChangePeriod.NextLevelUpAccumulationYesLastChangePeriod.OnMultipleUpgradeEnable';
		//
		// $detectCase = 'CACB.Weekly1PeriodMode.NextLevelUpDailyPeriodMode.PeriodIsMet.NextLevelUpPeriodIsMet.OffHourlyCheckUpgradeEnable.OnMultipleUpgradeEnable';
		// $detectCase = 'CACB.DailyPeriodMode.NextLevelUpDailyPeriodMode.PeriodIsMet.NextLevelUpPeriodIsMet.OffHourlyCheckUpgradeEnable.OnMultipleUpgradeEnable';
		// $detectCase = 'CACB.DailyPeriodMode.NextLevelUpWeekly1PeriodMode.PeriodIsMet.NextLevelUpPeriodIsMet.OnHourlyCheckUpgradeEnable.OnMultipleUpgradeEnable';
		// $detectCase = 'CACB.DailyPeriodMode.NextLevelUpDailyPeriodMode.NeverMetedInPreconditions.PeriodIsMet.NextLevelUpPeriodIsMet.OffHourlyCheckUpgradeEnable.NoAccumulation.AllNoMetInFormula.OnMultipleUpgradeEnable';
		// $detectCase = 'SACB.Weekly1PeriodMode.NeverMetedInPreconditions.PeriodIsMet.OffHourlyCheckUpgradeEnable.AccumulationLastChangePeriodResetIfMet.EmptyUpgradeSetting.IsConditionNotMet.OnlyMetBetOfBetDepositInFormula.OnMultipleUpgradeEnable';
		// for debug, [79] => SACB.Weekly1PeriodMode.NeverMetedInPreconditions.PeriodIsMet.OffHourlyCheckUpgradeEnable.AccumulationLastChangePeriodResetIfMet.EmptyUpgradeSetting.IsConditionNotMet.OnlyMetBetOfBetDepositInFormula.OnMultipleUpgradeEnable.ExpectedNoUpgrade
		// $detectCase = 'SACB.Weekly1PeriodMode.NextLevelUpWeekly1PeriodMode.HadDepositMetedInPreconditions.PeriodIsMet.NextLevelUpPeriodIsMet.OffHourlyCheckUpgradeEnable.AccumulationLastChangePeriodResetIfMet.OnlyMetDepositOfBetDepositInFormula.NextLevelUpOnlyMetDepositOfBetDepositInFormula.OnMultipleUpgradeEnable';
		// for debug, [90] => SACB.Weekly1PeriodMode.NextLevelUpWeekly1PeriodMode.HadDepositMetedInPreconditions.PeriodIsMet.NextLevelUpPeriodIsMet.OffHourlyCheckUpgradeEnable.AccumulationLastChangePeriodResetIfMet.OnlyMetDepositOfBetDepositInFormula.NextLevelUpOnlyMetDepositOfBetDepo
		// $detectCase = 'SACB.Weekly1PeriodMode.NextLevelUpWeekly1PeriodMode.NeverMetedInPreconditions.PeriodIsMet.NextLevelUpPeriodIsMet.OffHourlyCheckUpgradeEnable.AccumulationLastChangePeriodResetIfMet.OnlyMetBetOfBetDepositInFormula.NextLevelUpOnlyMetBetDepositOfAllInFormula.OnMultipleUpgradeEnable';
		// for debug, [20] => SACB.Weekly1PeriodMode.NextLevelUpWeekly1PeriodMode.NeverMetedInPreconditions.PeriodIsMet.NextLevelUpPeriodIsMet.OffHourlyCheckUpgradeEnable.AccumulationLastChangePeriodResetIfMet.OnlyMetBetOfBetDepositInFormula.NextLevelUpOnlyMetBetDepositOfAllInFormula.OnMultipleUpgradeEnable.ExpectedNoUpgrade
		// $detectCase = 'SACB.Weekly1PeriodMode.NextLevelUpWeekly1PeriodMode.HadBetMetedInPreconditions.PeriodIsMet.NextLevelUpPeriodIsMet.OffHourlyCheckUpgradeEnable.AccumulationLastChangePeriodResetIfMet.AllMetOfBetDepositInFormula.NextLevelUpOnlyMetBetOfBetDepositInFormula.OnMultipleUpgradeEnable';
		// for debug, [61] => SACB.Weekly1PeriodMode.NextLevelUpWeekly1PeriodMode.HadBetMetedInPreconditions.PeriodIsMet.NextLevelUpPeriodIsMet.OffHourlyCheckUpgradeEnable.AccumulationLastChangePeriodResetIfMet.AllMetOfBetDepositInFormula.NextLevelUpOnlyMetBetOfBetDepositInFormula.OnMultipleUpgradeEnable.ExpectedUpgrade
		// $detectCase = 'CACB.Weekly1PeriodMode.NextLevelUpWeekly1PeriodMode.IngoreInPreconditions.PeriodNotMet.NextLevelUpPeriodNotMet.OffHourlyCheckUpgradeEnable.NoAccumulation.AllMetInFormula.OnMultipleUpgradeEnable';
		// // for debug, [352] => CACB.Weekly1PeriodMode.NextLevelUpWeekly1PeriodMode.IngoreInPreconditions.PeriodNotMet.NextLevelUpPeriodNotMet.OffHourlyCheckUpgradeEnable.NoAccumulation.AllMetInFormula.OnMultipleUpgradeEnable.ExpectedUpgrade

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
		if($isOffHourlyCheckUpgradeEnable){// 沒有啟用 Hourly Checkbox 的等級條件
			if($checkFrom != 'CronjobHourly'){ // cronjob Hourly triggered
				$isToCheck = true;
				if($toDebuglog)print_r(['1886.checkFrom.isToCheck = true']);
			}else if($checkFrom == 'SBE'){ // SBE 手動觸發檢查
				$isToCheck = true;
				if($toDebuglog)print_r(['1889.checkFrom.isToCheck = true']);
			}else{
				$isToCheck = false;
				if($toDebuglog)print_r(['1892.checkFrom.isToCheck = false']);
				if($toDebuglog){
					$this->utils->debug_log('1869.isToCheck:',$isToCheck);
				}
			}
		}
		if($isOnHourlyCheckUpgradeEnable){// 有啟用 Hourly Checkbox 的等級條件
			if($checkFrom == 'CronjobHourly'){ // cronjob Hourly triggered
				$isToCheck = true;
				if($toDebuglog)print_r(['1898.checkFrom.isToCheck = true']);
			}else if($checkFrom == 'SBE'){ // SBE 手動觸發檢查
				$isToCheck = true;
				if($toDebuglog)print_r(['1900.checkFrom.isToCheck = true']);
			}else{
				$isToCheck = false;
				if($toDebuglog)print_r(['1901.checkFrom.isToCheck = false']);
				if($toDebuglog){
					$this->utils->debug_log('1879.isToCheck:',$isToCheck);
				}
			}
		}
		// else{
		// 	$isToCheck = false;
			if($toDebuglog){
				$this->utils->debug_log('1885.isToCheck:',$isToCheck);
			}
		// }


		/// 檢查檢查前後，是否等級一樣？（包含連續升級）
		// handle $testConditionFn, ex: _testConditionFn4beforeSameAsAfterV2, _testConditionFn4beforeDiffOneMoreAsAfterV2
		if($isToCheck){ // 有要確認升級條件
			// _testConditionFn4beforeSameAsAfterV2

			if($checkFrom == 'CronjobHourly' && $isOnHourlyCheckUpgradeEnable){
				// ignore the case, because hourlyInSetting=1 and fromHourlyCronjob == hourlyInSetting
				if($toDebuglog)print_r(['1915.update testConditionFn']);
			}


			if($isEmptyUpgradeSetting){
				$testConditionFn = '_testConditionFn4beforeSameAsAfterV2';
			}
			if($isDepositUpgradeSetting || $isBetUpgradeSetting || $isDepositBetUpgradeSetting){
				if( $isIsConditionNotMet ){
					$testConditionFn = '_testConditionFn4beforeSameAsAfterV2';
				}
			}

			if( ! $_isMetInFormulaCase){
				$testConditionFn = '_testConditionFn4beforeSameAsAfterV2';
			}


		if($toDebuglog)print_r(['1936.update testConditionFn.will.switch.true.testConditionFn:', $testConditionFn]);
			switch(true){
				/// disable for only handle _testConditionFn4beforeDiffAfterV2 result.
				// the _testConditionFn4beforeDiffOneMoreAsAfterV2 result will be handled at the later condition,"isOnMultipleUpgradeEnable".
				//
				// case ( $_isMetInFormulaCase
				// 		&& $_isMetInFormulaCase4nextLevelUp
				// 		&& $isOnMultipleUpgradeEnable
				// 	):
				// 	$testConditionFn = '_testConditionFn4beforeDiffOneMoreAsAfterV2';
				// 	break;
				// case ($_isMetInFormulaCase && !$_isMetInFormulaCase4nextLevelUp):
				// 	$testConditionFn = '_testConditionFn4beforeDiffAfterV2';
				// 	break;
				case $_isMetInFormulaCase:
					if($toDebuglog)print_r(['1951.update testConditionFn.in.switch.true']);
				case ($isHadDepositMetedInPreconditions && $isOnlyMetBetOfBetDepositInFormula):
					if($toDebuglog)print_r(['1953.update testConditionFn.in.switch.true']);
				case ($isHadBetMetedInPreconditions && $isOnlyMetDepositOfBetDepositInFormula):
					if($toDebuglog)print_r(['1955.update testConditionFn.in.switch.true']);
					$testConditionFn = '_testConditionFn4beforeDiffAfterV2';
				break;


				case $isAllNoMetInFormula:
				case $isOnlyMetBetOfAllInFormula:
				case $isOnlyMetDepositOfAllInFormula:
				case $isOnlyMetBetDepositOfAllInFormula:
				case $isOnlyMetBetDepositWinOfAllInFormula:
				case $isOnlyMetBetOfBetDepositInFormula:
					$testConditionFn = '_testConditionFn4beforeSameAsAfterV2';
				if($toDebuglog)print_r(['1794.update testConditionFn']);
				break;



				case $isAllMetInFormula:
				case $isOnlyMetBetOfBetInFormula:
					$testConditionFn = '_testConditionFn4beforeDiffAfterV2';
					if($toDebuglog)print_r(['1801.update testConditionFn'
						, 'isAllMetInFormula', $isAllMetInFormula
						, 'isOnlyMetBetOfBetInFormula', $isOnlyMetBetOfBetInFormula
						, 'isOnlyMetBetOfBetDepositInFormula', $isOnlyMetBetOfBetDepositInFormula
					]);
				break;
			}
			switch(true){
				case $isEmptyUpgradeSetting:
				case $isDepositUpgradeSetting:
				case $isBetUpgradeSetting:
				case $isDepositBetUpgradeSetting:
					if($isIsConditionMet){
						$testConditionFn = '_testConditionFn4beforeDiffAfterV2';
						if($toDebuglog)print_r(['1811.update testConditionFn'
							, 'isEmptyUpgradeSetting', $isEmptyUpgradeSetting
							, 'isDepositUpgradeSetting', $isDepositUpgradeSetting
							, 'isBetUpgradeSetting', $isBetUpgradeSetting
							, 'isDepositBetUpgradeSetting', $isDepositBetUpgradeSetting
						]);
					}
					if($isIsConditionNotMet){
						$testConditionFn = '_testConditionFn4beforeSameAsAfterV2';
						if($toDebuglog)print_r(['1815.update testConditionFn'
							, 'isEmptyUpgradeSetting', $isEmptyUpgradeSetting
							, 'isDepositUpgradeSetting', $isDepositUpgradeSetting
							, 'isBetUpgradeSetting', $isBetUpgradeSetting
							, 'isDepositBetUpgradeSetting', $isDepositBetUpgradeSetting
						]);
					}
				break;
			}


			if( $isOnMultipleUpgradeEnable ){
				// maybe return _testConditionFn4beforeDiffOneMoreAsAfterV2
				if( $isPeriodIsMet && $isNextLevelUpPeriodIsMet
					&& $testConditionFn != '_testConditionFn4beforeSameAsAfterV2'
				){
					if($toDebuglog)print_r(['2011.update testConditionFn', $testConditionFn]); // _testConditionFn4beforeDiffAfterV2
					if($_isMetInFormulaCase4nextLevelUp){
						$testConditionFn = '_testConditionFn4beforeDiffOneMoreAsAfterV2';
						if($toDebuglog)print_r(['1926.update testConditionFn', $testConditionFn]);
						if($toDebuglog){
							$this->utils->debug_log('1901.testConditionFn:',$testConditionFn);
						}

					}
					if($isAccumulationLastChangePeriodResetIfMet){
						// 定義，啟用連續升級、現在週期跟下個升級條件週期，有符合，且第一個等級有升級了。
						// 該模式，哪些狀況，可以連續升級？
						// 找下個升級條件沒過的(目前是下列 找 $_isMetInFormulaCase4nextLevelUp = false; 的範圍)，
						// 這些環境下，曾經滿足某項，當下升級滿足另一個項目的
						switch(true){ // OnlyMetBetOfBetDepositInF
							case $isNextLevelUpAllNoMetInFormula:
							case $isNextLevelUpNotMetDepositOfDepositInFormula:
							case $isNextLevelUpNotMetBetOfBetInFormula:
							case $isNextLevelUpNoMetAllOfBetDepositInFormula:
								if( ($isHadDepositMetedInPreconditions && $isOnlyMetBetOfBetDepositInFormula)
									|| ($isHadBetMetedInPreconditions && $isOnlyMetDepositOfBetDepositInFormula)
								){
									$testConditionFn = '_testConditionFn4beforeDiffOneMoreAsAfterV2';
								}
								break;

							case $isNextLevelUpOnlyMetBetOfBetDepositInFormula:
								if( $isHadDepositMetedInPreconditions
									|| $isOnlyMetDepositOfBetDepositInFormula
									|| $isAllMetOfBetDepositInFormula
								){
									$testConditionFn = '_testConditionFn4beforeDiffOneMoreAsAfterV2';
								}

								break;

							case $isNextLevelUpOnlyMetDepositOfBetDepositInFormula:
								if( $isHadBetMetedInPreconditions
									|| $isOnlyMetBetOfBetDepositInFormula
									|| $isAllMetOfBetDepositInFormula
								){
									$testConditionFn = '_testConditionFn4beforeDiffOneMoreAsAfterV2';
								}
								break;

							case $isNextLevelUpEmptyInFormula:
							case $isNextLevelUpOnlyMetBetDepositOfAllInFormula: // because win and loss Not met.
							case $isNextLevelUpOnlyMetBetDepositWinOfAllInFormula: // because loss Not met.
							case $isNextLevelUpOnlyMetDepositOfAllInFormula: // @todo Need to check win and loss amount.
							case $isNextLevelUpOnlyMetBetOfAllInFormula: // @todo Need to check win and loss amount.
								// keep _testConditionFn4beforeSameAsAfterV2
								break;

						} // EOF switch(true){...
					} // EOF if($isAccumulationLastChangePeriodResetIfMet){...
				}
			}

			if( $isPeriodNotMet ){
				$testConditionFn = '_testConditionFn4beforeSameAsAfterV2';
				if($toDebuglog)print_r(['1917.update testConditionFn']);
				if($toDebuglog){

					$this->utils->debug_log('1893.testConditionFn:',$testConditionFn);
				}
			}

		}else{
			$testConditionFn = '_testConditionFn4beforeSameAsAfterV2';
			if($toDebuglog)print_r(['1909.update testConditionFn']);
			if($toDebuglog){
				$this->utils->debug_log('1909.testConditionFn:',$testConditionFn, 'theCombinedCase:', $theCombinedCase);
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
			$this->utils->debug_log('1905.testConditionFn:',$testConditionFn, 'checkFrom:', $checkFrom, 'isToCheck:', $isToCheck, 'isOnHourlyCheckUpgradeEnable', $isOnHourlyCheckUpgradeEnable, 'theCombinedCase:', $theCombinedCase);

		}
		if($toDebuglog){
			print_r(['1905.testConditionFn:',$testConditionFn, 'checkFrom:', $checkFrom, 'isToCheck:', $isToCheck, 'isOnHourlyCheckUpgradeEnable', $isOnHourlyCheckUpgradeEnable, 'theCombinedCase:', $theCombinedCase]);
			die('cccccc');
		}

		return $testConditionFn;
	} // EOF _getTestConditionFnFromCombinedCase4ogp24373upgrade

	/**
	 * Get the Combined test Case and Expected List
	 *
	 * [TESTED]
	 * http://admin.og.local/cli/testing_ogp24373/index/test_getCombinedCaseList4FromSBE
	 *
	 * http://admin.og.local/cli/testing_ogp24373/index/test_getCombinedCaseList4FromSBE/1/1
	 *
	 * @param boolean $doOutputHtml If true, that means view the list via the browser.
	 * @param boolean $isAppendTestConditionFn If true, that means will append the ExpectedDowngrade/ExpectedNoDowngrade in the list.
	 * @return array The Combined Case List
	 */
	public function test_getCombinedCaseList4FromSBE($doOutputHtml = true, $isAppendTestConditionFn = false){
		// tryDowngradeSuccessInCACBTriggerFromCronjob
		$theCaseKindList = $this->utils4testogp->_assignCaseKindList4FromSBE();
		$theCommonSeparateModeCaseList = $theCaseKindList['theCommonSeparateModeCaseList'];
		$thePeriodModeCaseList = $theCaseKindList['thePeriodModeCaseList'];
		$thePeriodIsMetCaseList = $theCaseKindList['thePeriodIsMetCaseList'];
		$theLevelMaintainEnableCaseList = $theCaseKindList['theLevelMaintainEnableCaseList'];
		$theLevelMaintainTimeCaseList = $theCaseKindList['theLevelMaintainTimeCaseList'];
		$theLevelMaintainConditionCaseList = $theCaseKindList['theLevelMaintainConditionCaseList'];
		$theAccumulationCaseList = $theCaseKindList['theAccumulationCaseList'];

		$theConditionMetList = $theCaseKindList['theConditionMetList'];

		// 從上面的組合 轉成各種測試情境
		$testCaseComboList = [ array_keys($theCommonSeparateModeCaseList)
			, array_keys($thePeriodModeCaseList)
			// , array_keys($thePeriodIsMetCaseList)// will ignore when triggered from SBE
			, array_keys($theConditionMetList)
			, array_keys($theLevelMaintainEnableCaseList)
			, array_keys($theLevelMaintainTimeCaseList)
			, array_keys($theLevelMaintainConditionCaseList)
			, array_keys($theAccumulationCaseList)
		];
		// var_dump($testCaseComboList);
		$testCaseCombinedList = $this->utils4testogp->combination_arr( $testCaseComboList );
		$testCaseList = [];
		foreach($testCaseCombinedList as $indexNumber => $testCase){
			$testCaseList[] = implode('.',$testCase);
		}

		// ignore the specific test cases. // 需要忽略的狀況
		$ignoreTestCaseKeyList = $this->_getIngoreCombinedCases4FromSBE();
		$filtedTestCaseList = array_filter($testCaseList, function($v, $k) use ( $ignoreTestCaseKeyList){
			// echo 'k:'.$k;echo PHP_EOL;
			return ! in_array($v, $ignoreTestCaseKeyList);
		}, ARRAY_FILTER_USE_BOTH);

		$filtedTestCaseList = array_values($filtedTestCaseList);

		// 預期結果，需要重新檢視
		foreach($filtedTestCaseList as $indexNumber => $testCase ){

			$theTestConditionFn = $this->_getTestConditionFnFromCombinedCase4FromSBE($testCase);

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

	} // EOF test_getCombinedCaseList4FromSBE
	/**
	 * Get the Combined test Case and Expected List
	 *
	 * [TESTED]
	 * http://admin.og.local/cli/testing_ogp24373/index/test_getCombinedCaseList
	 *
	 * http://admin.og.local/cli/testing_ogp24373/index/test_getCombinedCaseList/1/1
	 *
	 * @param boolean $doOutputHtml If true, that means view the list via the browser.
	 * @param boolean $isAppendTestConditionFn If true, that means will append the ExpectedDowngrade/ExpectedNoDowngrade in the list.
	 * @return array The Combined Case List
	 */
	public function test_getCombinedCaseList($doOutputHtml = true, $isAppendTestConditionFn = false){ // test_DwongradeMonthly(){ //
		// tryDowngradeSuccessInCACBTriggerFromCronjob
		$theCaseKindList = $this->utils4testogp->_assignCaseKindList();
		$theCommonSeparateModeCaseList = $theCaseKindList['theCommonSeparateModeCaseList'];
		$thePeriodModeCaseList = $theCaseKindList['thePeriodModeCaseList'];
		$thePeriodIsMetCaseList = $theCaseKindList['thePeriodIsMetCaseList'];
		$theLevelMaintainEnableCaseList = $theCaseKindList['theLevelMaintainEnableCaseList'];
		$theLevelMaintainTimeCaseList = $theCaseKindList['theLevelMaintainTimeCaseList'];
		$theLevelMaintainConditionCaseList = $theCaseKindList['theLevelMaintainConditionCaseList'];
		$theAccumulationCaseList = $theCaseKindList['theAccumulationCaseList'];

		// 從上面的組合 轉成各種測試情境
		$testCaseComboList = [ array_keys($theCommonSeparateModeCaseList)
				, array_keys($thePeriodModeCaseList)
				, array_keys($thePeriodIsMetCaseList)
				, array_keys($theLevelMaintainEnableCaseList)
				, array_keys($theLevelMaintainTimeCaseList)
				, array_keys($theLevelMaintainConditionCaseList)
				, array_keys($theAccumulationCaseList)
			];
		$testCaseCombinedList = $this->utils4testogp->combination_arr( $testCaseComboList );
		$testCaseList = [];
		foreach($testCaseCombinedList as $indexNumber => $testCase){
			$testCaseList[] = implode('.',$testCase);
		}
		// ignore the specific test cases.
		$ignoreTestCaseKeyList = $this->utils4testogp->_getIngoreCombinedCases();
		$filtedTestCaseList = array_filter($testCaseList, function($v, $k) use ( $ignoreTestCaseKeyList){
			// echo 'k:'.$k;echo PHP_EOL;
			return ! in_array($v, $ignoreTestCaseKeyList);
		}, ARRAY_FILTER_USE_BOTH);
		/// After array_filter(), how can I reset the keys to go in numerical order starting at 0
 		// https://stackoverflow.com/a/3401863
		$filtedTestCaseList = array_values($filtedTestCaseList);

		// /// Test in CACB, disable for online
		// $filtedTestCaseList = array_filter($filtedTestCaseList, function($v, $k) use ( $ignoreTestCaseKeyList){
		// 	$findme = 'CACB.';
		// 	return $this->utils4testogp->_pos($v, $findme ) !== false;
		// 	// return ! in_array($v, $ignoreTestCaseKeyList);
		// }, ARRAY_FILTER_USE_BOTH);
		// $filtedTestCaseList = array_values($filtedTestCaseList);

		foreach($filtedTestCaseList as $indexNumber => $testCase ){

			$theTestConditionFn = $this->utils4testogp->_getTestConditionFnFromCombinedCase($testCase);

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




	// reference to test_MaintainTimeOfLogFilenameWithFileContents
	/**
	 * Check the log file by search keyword for confirm the pre-set is work.
	 *
	 * @param string $theLogFilename The path and the log filename.
	 * @param string $theFileContents the part of the log file content.
	 * @return void
	 */
	public function _testInLogFileFn4FromSBE($theLogFilename = '/home/vagrant/Code/og/admin/application/logs/tmp_shell/job_player_level_downgrade_by_playerId_6aa4a851566ec0f89f9ff47c6960aa96.log'
		, $theFileContents = '{"message":"isSufficient4RequiredDatetimeRange diffInSeconds","context":[74559,"2021-04-02 15:15:36~2021-04-01 18:32:57","diffInSeconds4Required",1209600,"2021-04-02 15:15:36~2021-03-19 15:15:36"],"level":100,"level_name":"DEBUG","channel":"default-og","datetime":"2021-04-07T15:15:40+08:00","trace":"../../models/group_level.php:7607@isSufficient4RequiredDatetimeRange > ../../models/group_level.php:7723@calcDatetimeRangeAndPreviousFromDatetime > ","extra":{"tags":{"request_id":"4b4833a13687d007b262257d2e9433f4","env":"live.og_local","version":"6.112.01.001","hostname":"default-og"},"process_id":17806,"memory_peak_usage":"34.25 MB","memory_usage":"32.25 MB"}}'
		, $theCombinedCase = 'CASB.EmptyPeriodMode.PeriodIsMet.OnLevelMaintainEnable.InMaintainTime.IsMetLevelMaintainCondition.NoAccumulation'
	){
		$this->test_MaintainTimeOfLogFilenameWithFileContents($theLogFilename, $theFileContents, $theCombinedCase);
		$this->test_DowngradeConditionMetOfLogFilenameWithFileContents($theLogFilename, $theFileContents, $theCombinedCase);
	}

	/**
	 * Get the TestConditionFn by theCombinedCase
	 *
	 * @param string $theCombinedCase
	 * @return string $testConditionFn
	 */
	public function _getTestConditionFnFromCombinedCase4FromSBE($theCombinedCase = 'CACB.EmptyPeriodMode.PeriodNotMet.OnLevelMaintainEnable.InMaintainTime.IsMetLevelMaintainCondition.NoAccumulation'){
		$isPeriodNotMet = $this->utils4testogp->_pos($theCombinedCase, 'PeriodNotMet');

		$isEmptyPeriodMode = $this->utils4testogp->_pos($theCombinedCase, 'EmptyPeriodMode');

		$isOnLevelMaintainEnable = $this->utils4testogp->_pos($theCombinedCase, 'OnLevelMaintainEnable');
		$isOffLevelMaintainEnable = $this->utils4testogp->_pos($theCombinedCase, 'OffLevelMaintainEnable');

		$isInMaintainTime = $this->utils4testogp->_pos($theCombinedCase, 'InMaintainTime');
		$isOverMaintainTime = $this->utils4testogp->_pos($theCombinedCase, 'OverMaintainTime');

		$isNotMetLevelMaintainCondition = $this->utils4testogp->_pos($theCombinedCase, 'NotMetLevelMaintainCondition');
		$isIsMetLevelMaintainCondition = $this->utils4testogp->_pos($theCombinedCase, 'IsMetLevelMaintainCondition');

		$isIsDowngradeConditionMet = $this->utils4testogp->_pos($theCombinedCase, 'IsDowngradeConditionMet');
		$isIsDowngradeConditionNotMet = $this->utils4testogp->_pos($theCombinedCase, 'IsDowngradeConditionNotMet');

		$testConditionFn = '_testConditionFn4beforeDiffAfterV2'; // should be downgraded

		if( $isEmptyPeriodMode && $isOffLevelMaintainEnable){
			// should without downgraded, keep the current level.
			$testConditionFn = '_testConditionFn4beforeSameAsAfterV2';
		}

		if($isIsDowngradeConditionNotMet && $isOffLevelMaintainEnable){
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

		$this->utils->debug_log('1068.testConditionFn:', $testConditionFn, 'theCombinedCase:', $theCombinedCase);

		return $testConditionFn;
	} // EOF _getTestConditionFnFromCombinedCase4FromSBE


	public function doHadMetedInPreconditions( $theCombinedCase = 'SACB.Weekly1PeriodMode.NextLevelUpWeekly1PeriodMode.HadDepositMetedInPreconditions.PeriodIsMet.NextLevelUpPeriodIsMet.IsConditionNotMet.AccumulationLastChangePeriodResetIfMet.OnlyMetBetDepositOfAllInFormula.OnMultipleUpgradeEnable.ExpectedNoUpgrade'
		, $theTestPlayerInfo = []
		, $isTriggerFromHourlyCronjob
		, $manual_batch
	){


		$periodMode = 'DailyPeriodMode'; // # 2, its should be daily, monthly,...
		$nextLevelUpPeriodMode = 'NextLevelUpDailyPeriodMode'; // # 2.1, ignore for  Not Met in next levelup
		$periodValue = '00:00:00 - 23:59:59';// # 3
		$nextLevelUpPeriodValue = '00:00:00 - 23:59:59';// # 3.1
		$TEB_DateTime = new DateTime(); // # 4


		// OnlyMetBetOfAllInFormula
		// OnlyMetDepositOfAllInFormula
		/// assign for test
		// $_combinedCase = 'SACB.DailyPeriodMode.NextLevelUpDailyPeriodMode.PeriodIsMet.IsConditionMet.AccumulationLastChangePeriodResetIfMet.OnlyMetBetDepositOfAllInFormula.ExpectedNoUpgrade';

		// xxInPreconditions
		$isIngoreInPreconditions = $this->utils4testogp->_pos($theCombinedCase, '.IngoreInPreconditions');
		$isNeverMetedInPreconditions = $this->utils4testogp->_pos($theCombinedCase, '.NeverMetedInPreconditions');
		$isHadBetMetedInPreconditions = $this->utils4testogp->_pos($theCombinedCase, '.HadBetMetedInPreconditions');
		$isHadDepositMetedInPreconditions = $this->utils4testogp->_pos($theCombinedCase, '.HadDepositMetedInPreconditions');

		$toDoUpgradeCheck = false;
		if( ! $isIngoreInPreconditions ){
			$toDoUpgradeCheck = true;
		}

			var_dump(['debugIn2340.indoHadMetedInPreconditions'
				, 'theCombinedCase:', $theCombinedCase
			]);

		$_combinedCase = $theCombinedCase;

		// clear the original formula settings
		$_upgradeSettingAndFormulaRelatedList = $this->utils4testogp->getUpgradeSettingAndFormulaRelatedList();
		$_nextLevelUpUpgradeSettingAndFormulaRelatedList = $this->utils4testogp->getUpgradeSettingAndFormulaRelatedList('NextLevelUp');
		$findConditionList = array_merge($_upgradeSettingAndFormulaRelatedList, $_nextLevelUpUpgradeSettingAndFormulaRelatedList);
		$targetCondition = '';
		$replaceList = array_fill(0, count($findConditionList), $targetCondition);
		$_combinedCase = str_replace($findConditionList, $replaceList, $_combinedCase);
			var_dump(['debugIn2353.indoHadMetedInPreconditions'
				, '_combinedCase:', $_combinedCase
				, 'theCombinedCase:', $theCombinedCase
				, 'findConditionList:', $findConditionList
			]);

		// $findConditionList = $this->utils4testogp->getUpgradeSettingAndFormulaRelatedList();
		// $targetCondition = '';
		// switch(true){
		// 	case $isIngoreInPreconditions:
		// 	break;
		// 	case $isNeverMetedInPreconditions:
		// 		$findConditionList[] = '.DepositBetUpgradeSetting'; // setp 1 / 2, in isNeverMetedInPreconditions
		// 	break;
		// 	case $isHadBetMetedInPreconditions:
		// 		$targetCondition = '.BetUpgradeSetting';
		// 	break;
		// 	case $isHadDepositMetedInPreconditions:
		// 		$targetCondition = '.DepositUpgradeSetting';
		// 	break;
		// }
		// $replaceList = array_fill(0, count($findConditionList), $targetCondition);
		// $_combinedCase = str_replace($findConditionList,$replaceList,$_combinedCase);
		//
		// 	var_dump(['debugIn2375.indoHadMetedInPreconditions'
		// 		, '_combinedCase:', $_combinedCase
		// 	]);
		// switch(true){
		// 	case $isNeverMetedInPreconditions: // setp 2 / 2, in isNeverMetedInPreconditions
		// 		// override for IsConditionMet replace to IsConditionNotMet
		// 		$findConditionList = [];
		// 		$findConditionList[] = '.IsConditionMet';
		// 		$targetCondition = '.IsConditionNotMet';
		// 		$replaceList = array_fill(0, count($findConditionList), $targetCondition);
		// 		$_combinedCase = str_replace($findConditionList, $replaceList, $_combinedCase);

		// 			var_dump(['debugIn2389.indoHadMetedInPreconditions'
		// 				, '_combinedCase:', $_combinedCase
		// 			]);
		// 	break;
		// }


		// the solution, to use OnlyMetBetDepositOfAllInFormula, that will pre-append to AccumulationLastChangePeriodResetIfMet.
		switch(true){
			case $isNeverMetedInPreconditions:
				// override for IsConditionMet replace to IsConditionNotMet
				$findConditionList = [];
				$findConditionList[] = '.AccumulationLastChangePeriodResetIfMet';
				$targetCondition = '.AllNoMetInFormula.AccumulationLastChangePeriodResetIfMet';
				$replaceList = array_fill(0, count($findConditionList), $targetCondition);
				$_combinedCase = str_replace($findConditionList, $replaceList, $_combinedCase);

				var_dump(['debugIn2404.indoHadMetedInPreconditions'
					, '_combinedCase:', $_combinedCase
				]);
			break;
			case $isHadBetMetedInPreconditions: // use OnlyMetBetOfAllInFormula to meted
				$findConditionList = [];
				$findConditionList[] = '.AccumulationLastChangePeriodResetIfMet';
				$targetCondition = '.OnlyMetBetOfAllInFormula.AccumulationLastChangePeriodResetIfMet';
				$replaceList = array_fill(0, count($findConditionList), $targetCondition);
				$_combinedCase = str_replace($findConditionList, $replaceList, $_combinedCase);
				break;
			case $isHadDepositMetedInPreconditions: // use OnlyMetDepositOfAllInFormula to meted
				$findConditionList = [];
				$findConditionList[] = '.AccumulationLastChangePeriodResetIfMet';
				$targetCondition = '.OnlyMetDepositOfAllInFormula.AccumulationLastChangePeriodResetIfMet';
				$replaceList = array_fill(0, count($findConditionList), $targetCondition);
				$_combinedCase = str_replace($findConditionList, $replaceList, $_combinedCase);
				break;
		}

		// ref. to $periodMode = 'DailyPeriodMode'; // # 2
		// override for replace to DailyPeriodMode
		$findConditionList = [];
		$findConditionList[] = '.EmptyPeriodMode';
		$findConditionList[] = '.Weekly1PeriodMode';
		$findConditionList[] = '.Monthly5PeriodMode';
		$targetCondition = '.DailyPeriodMode';
		$replaceList = array_fill(0, count($findConditionList), $targetCondition);
		$_combinedCase = str_replace($findConditionList, $replaceList, $_combinedCase);

			var_dump(['debugIn2419.indoHadMetedInPreconditions'
				, '_combinedCase:', $_combinedCase
			]);
		// override for replace PeriodNotMet to PeriodIsMet
		$findConditionList = [];
		$findConditionList[] = '.PeriodNotMet';
		$targetCondition = '.PeriodIsMet';
		$replaceList = array_fill(0, count($findConditionList), $targetCondition);
		$_combinedCase = str_replace($findConditionList, $replaceList, $_combinedCase);

			var_dump(['debugIn2430.indoHadMetedInPreconditions'
				, '_combinedCase:', $_combinedCase
			]);

		// override for disable Multiple Upgrade
		$findConditionList = [];
		$findConditionList[] = '.OnMultipleUpgradeEnable';
		$targetCondition = '.OffMultipleUpgradeEnable';
		$replaceList = array_fill(0, count($findConditionList), $targetCondition);
		$_combinedCase = str_replace($findConditionList, $replaceList, $_combinedCase);

			var_dump(['debugIn2441.indoHadMetedInPreconditions'
				, '_combinedCase:', $_combinedCase
			]);

		// override TEB_DateTime
		$TEB_DateTime = $this->_getMetTimeExecBeginOfPeriodAndNextLevelUpFromCombinedCase($_combinedCase);

		$isEmptyPeriodMode = $this->utils4testogp->_pos($_combinedCase, 'EmptyPeriodMode');
		$isDailyPeriodMode = $this->utils4testogp->_pos($_combinedCase, 'DailyPeriodMode');
		$isWeekly1PeriodMode = $this->utils4testogp->_pos($_combinedCase, 'Weekly1PeriodMode');
		$isMonthly5PeriodMode = $this->utils4testogp->_pos($_combinedCase, 'Monthly5PeriodMode');

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

			var_dump(['debugIn2446.indoHadMetedInPreconditions'
				, '_combinedCase:', $_combinedCase
			]);
		$theSettingNamePrefix = 'HadMetedInPreconditions';
		$forGrade = 'upgrade';
		list($settingName, $getUpgradeLevelSettingFn) = $this->_getUpgradeLevelSettingFnAndSettingNameFromCombinedCase( $_combinedCase
			, $theSettingNamePrefix
			, $forGrade
		);


		$isOnMultipleUpgradeEnable = $this->utils4testogp->_pos($theCombinedCase, 'OnMultipleUpgradeEnable');
		$isOffMultipleUpgradeEnable = $this->utils4testogp->_pos($theCombinedCase, 'OffMultipleUpgradeEnable');
		// $isMultipleUpgrade = null;
		// if($isOnMultipleUpgradeEnable){
		// 	$isMultipleUpgrade = true;
		// }
		// if($isOffMultipleUpgradeEnable){
		// 	$isMultipleUpgrade = false;
		// }
		$isMultipleUpgrade = false; // only for doHadMetedInPreconditions, its always be false.

		$isCACB = $this->utils4testogp->_pos($theCombinedCase, 'CACB');
		$isSACB = $this->utils4testogp->_pos($theCombinedCase, 'SACB');
		$isCASB = $this->utils4testogp->_pos($theCombinedCase, 'CASB');
		$isSASB = $this->utils4testogp->_pos($theCombinedCase, 'SASB');

		$isSA = null;
		if($isSACB || $isSASB){
			$isSA = true;
		}else{
			$isSA = false;
		}
		$isCA = null;
		if($isCACB || $isCASB){
			$isCA = true;
		}else{
			$isCA = false;
		}

		$isSB = null;
		if($isSASB || $isCASB){
			$isSB = true;
		}
		if($isSACB || $isCACB){
			$isSB = false;
		}

		$isNeverMetedInPreconditions = $this->utils4testogp->_pos($theCombinedCase, 'NeverMetedInPreconditions');
		$isHadBetMetedInPreconditions = $this->utils4testogp->_pos($theCombinedCase, 'HadBetMetedInPreconditions');
		$isHadDepositMetedInPreconditions = $this->utils4testogp->_pos($theCombinedCase, 'HadDepositMetedInPreconditions');

		$testConditionFn = 1; // 同等級

		$isHourlyInSetting = false;
		if($isTriggerFromHourlyCronjob){
			$isHourlyInSetting = true;
		}

		$params = [ $theTestPlayerInfo // # 1
				, $periodMode // # 2
				, $nextLevelUpPeriodMode // # 2.1
				, $periodValue // # 3
				, $nextLevelUpPeriodValue // # 3.1
				, $TEB_DateTime // # 4
				, $testConditionFn // # 5 is_string for function name
				, $isMultipleUpgrade // $enableLevelMaintainFn // # 6 Level Maintain / Downgrade Guaranteed
				, $isHourlyInSetting // # 7
				, $getUpgradeLevelSettingFn // # 8
				, $isSA // #9 // isSA = false mean CA isAccumulationSeparatedInConfig
				, $isSB // #10 // isSB = false mean CB isBettingSeparatedInConfig
				, $isTriggerFromHourlyCronjob // #11
				, false // #12 as default
				, null // #13 as default
				, 2 // #14 for debug
			];
// var_dump('debugIn'.__LINE__);
// var_dump($params);
// var_dump(['debugIn1943.params:', $params]);
				var_dump(['1940.will _tryUpgradeSuccessTriggerFromCronjobV2.params', $params, 'toDoUpgradeCheck:', $toDoUpgradeCheck, 'datetime:', new DateTime() ]);
			$funcName = '_tryUpgradeSuccessTriggerFromCronjobV2'; // '_tryDowngradeSuccessTriggerFromCronjobV2';
			if($toDoUpgradeCheck){
				$rlt = call_user_func_array([$this, $funcName], $params); // $rlt = $this->_tryUpgradeSuccessTriggerFromCronjob($theTestPlayerInfo,...
			}
// var_dump(['debugIn1943.rlt:', $rlt]);
	} // EOF doHadMetedInPreconditions

	// http://admin.og.local/cli/testing_ogp24373/index/test_DowngradeFromSBE/5357/3
	/**
	 * Test the Downgrade Check simulated trigger from SBE
	 *
	 * URI,
	 * http://admin.og.local/cli/testing_ogp24373/index/test_DowngradeFromSBE/5357/3
	 * Cli,
	 * php public/index.php cli/testing_ogp24373/index/test_DowngradeFromSBE 5357 3 | w3m -T text/html > ../logs/ogp24373.5357.test_DowngradeFromSBE.log
	 *
	 * @param array|integer $theTestPlayerInfo If it is array, the array should be return from "utils4testogp::_searchTestPlayerByPlayerId()" or 'utils4testogp::_searchTestPlayerList()'.
	 * If it is numeric,(number string or integer), the integer should be the field, 'player.player_id'.
	 * If it is empty, that's means get the test player from utils4testogp::_searchTestPlayerList().
	 *
	 * @param integer $limitCaseAmount The Combined Case amount limit.default will be all cases.
	 * @return void
	 */
	public function DELtest_DowngradeFromSBE($theTestPlayerInfo = [] // # 1
		, $limitCaseAmount = 999999 // # 2
	){
	// 	$this->load->model(['group_level']);
	// 	$now = new Datetime();
	//
	// 	if( empty($theTestPlayerInfo) ){
	// 		$offsetDayRange = '180';
	// 		$limit = 5;
	// 		$params = [$offsetDayRange, $limit];
	// 		$rows = call_user_func_array([$this->utils4testogp, '_searchTestPlayerList4FromSBE'], $params); // $rows = $this->utils4testogp->_searchTestPlayerList($offsetDayRange, $limit);
	// 		$theTestPlayerInfo = $rows[0]; // for $rows[3] player_id=5357
	// 	}
	//
	// 	$thePlayerId = 0;
	// 	if( ! empty($theTestPlayerInfo)){
	// 		if( is_array($theTestPlayerInfo) ){
	// 			$thePlayerId = $theTestPlayerInfo['player_id'];
	// 		}else if( is_numeric($theTestPlayerInfo) ){
	// 			// this case the param,$theTestPlayerInfo should be thePlayerId.
	// 			$thePlayerId = $theTestPlayerInfo;
	// 			$rows = $this->utils4testogp->_searchTestPlayerByPlayerId($thePlayerId);
	// 			$theTestPlayerInfo = $rows[0]; // reload theTestPlayerInfo for vipsettingcashbackruleId
	// 		}
	// 		$original_vipsettingcashbackruleId = $theTestPlayerInfo['vipsettingcashbackruleId'];
	// 	}
	// 	// issue case has resolved.
	// 	// $theCombinedCase = 'CACB.EmptyPeriodMode.IsDowngradeConditionMet.OffLevelMaintainEnable.InMaintainTime.IsMetLevelMaintainCondition.NoAccumulation';
	// 	// $theCombinedCase = 'CACB.DailyPeriodMode.IsDowngradeConditionMet.OnLevelMaintainEnable.InMaintainTime.IsMetLevelMaintainCondition.NoAccumulation'; // patch in testInLogFileFn
	// 	// $theCombinedCase = 'CACB.EmptyPeriodMode.IsDowngradeConditionMet.OnLevelMaintainEnable.OverMaintainTime.IsMetLevelMaintainCondition.NoAccumulation';
	// 	// $_combinedCaseList[] = $theCombinedCase;
	// 	// $theCombinedCase = 'CACB.DailyPeriodMode.IsDowngradeConditionNotMet.OnLevelMaintainEnable.OverMaintainTime.IsMetLevelMaintainCondition.NoAccumulation';// patched in _getTestConditionFnFromCombinedCase4FromSBE().
	// 	// $_combinedCaseList[] = $theCombinedCase;
	// 	// $theCombinedCase = 'SACB.EmptyPeriodMode.IsDowngradeConditionMet.OnLevelMaintainEnable.InMaintainTime.IsMetLevelMaintainCondition.NoAccumulation';// patched in update strintf() to sprintf()
	// 	// $_combinedCaseList[] = $theCombinedCase;
	// 	$theCombinedCase = 'CACB.EmptyPeriodMode.PeriodNotMet.OnLevelMaintainEnable.OverMaintainTime.IsMetLevelMaintainCondition.NoAccumulation';
	// 	                    // CACB.EmptyPeriodMode.IsDowngradeConditionMet.OnLevelMaintainEnable.InMaintainTime.IsMetLevelMaintainCondition.AccumulationYesRegistrationDate // Patch in add ignore cases, EmptyPeriodMode, IsDowngradeConditionMet and NoAccumulation
	// 						// CACB.EmptyPeriodMode.IsDowngradeConditionMet.OffLevelMaintainEnable.InMaintainTime.IsMetLevelMaintainCondition.AccumulationYesRegistrationDate // Patch in add ignore cases, EmptyPeriodMode, OffLevelMaintainEnable.
	// 						// CACB.EmptyPeriodMode.PeriodNotMet.OnLevelMaintainEnable.OverMaintainTime.IsMetLevelMaintainCondition.NoAccumulation
	// 	$_combinedCaseList[] = $theCombinedCase;
	// 	//
	// 	$doOutputHtml = false;
	// 	$theCombinedCaseList = $this->test_getCombinedCaseList4FromSBE($doOutputHtml);
	// 	$_combinedCaseList = array_slice($theCombinedCaseList, 0, $limitCaseAmount); // @todo ignore for Dev. TEST
	// 	// $_combinedCaseList = array_slice($theCombinedCaseList, 166, $limitCaseAmount); // test code doe develop, ignore for online
	// 	// $_combinedCaseList = array_slice($theCombinedCaseList, 0, 3); // test code doe develop, ignore for online
	//
	// 	$caseTotalAmount = count($_combinedCaseList);
	// 	foreach($_combinedCaseList as $keyNumber => $currCase){
	// 		$theCombinedCase = $currCase;
	// 		$this->utils->debug_log('1223.theCombinedCase:', $theCombinedCase, 'caseTotalAmount:', $caseTotalAmount, 'keyNumber:', $keyNumber);
	// 		list($isSA, $isSB) = $this->_getIsSAIsSBFromCombinedCase($theCombinedCase);
	//
	// 		// // ref. to _getDowngradeLevelSettingFn
	// 		// $getUpgradeLevelSettingFn = '_getDowngradeLevelSettingFn'; // so far, CACB
	// 		// list($settingName, $getUpgradeLevelSettingFn) = $this->_getUpgradeLevelSettingFnAndSettingNameFromCombinedCase($theCombinedCase);
	// 		list($settingName, $getUpgradeLevelSettingFn) = $this->_getUpgradeLevelSettingFnAndSettingNameFromCombinedCaseAndTestPlayerInfo($theCombinedCase, $theTestPlayerInfo);
	//
	//
	// 		list($periodMode, $periodValue, $TEB_DateTime, $enableLevelMaintainFn) = $this->_getPeriodModeAndPeriodValueAndTEB_DateTimeAndEnableLevelMaintainFnFromCombinedCase4FromSBE($thePlayerId, $theCombinedCase);
	//
	// 		$testConditionFn = $this->_getTestConditionFnFromCombinedCase4FromSBE($theCombinedCase); // '_testConditionFn4beforeDiffAfterV2'; // _testConditionFn4beforeDiffAfterV2, _testConditionFn4beforeSameAsAfterV2
	// 		// $testConditionFn = $this->utils4testogp->_getTestConditionFnFromCombinedCase($theCombinedCase); // '_testConditionFn4beforeDiffAfterV2'; // _testConditionFn4beforeDiffAfterV2, _testConditionFn4beforeSameAsAfterV2
	// 		$manual_batch = 1;
	// 		$testInLogFileFn = '_testInLogFileFn4FromSBE'; // @todo
	// 		$params = [ $theTestPlayerInfo // # 1
	// 			, $periodMode // # 2
	// 			, $periodValue // # 3
	// 			, $TEB_DateTime // # 4
	// 			, $testConditionFn // # 5 is_string for function name
	// 			, $enableLevelMaintainFn // # 6 Level Maintain / Downgrade Guaranteed
	// 			, $getUpgradeLevelSettingFn // # 7
	// 			, $isSA // isSA = false mean CA isAccumulationSeparatedInConfig
	// 			, $isSB // isSB = false mean CB isBettingSeparatedInConfig
	// 			, $theCombinedCase // # 10 // for trace test case
	// 			, $manual_batch // # 11 $manual_batch If it's 0, that's means false; If it's 1, that's means true,
	// 			, $testInLogFileFn // #12 After downgrade check, To test pre-set in the log file
	// 		];
	//
	// 		$funcName = '_tryDowngradeSuccessTriggerFromCronjobV2';
	// 		$rlt = call_user_func_array([$this, $funcName], $params); // $rlt = $this->_tryUpgradeSuccessTriggerFromCronjob($theTestPlayerInfo,...
	//
	// 		// clear from yesterday.
	// 		$playerId = $theTestPlayerInfo['player_id'];
	// 		$now->modify('- 1 day');
	// 		$nowYmdHis = $now->format('Y-m-d H:i:s');
	// 		$this->try_revertThisCaseData($playerId, $original_vipsettingcashbackruleId, $nowYmdHis);
	//
	// 	}// EOF foreach($theCombinedCaseList as $currCase){...

	}// EOF DELtest_DowngradeFromSBE

	/**
	 * Test the Downgrade Check simulated trigger from cronjob
	 *
	 * URI,
	 * http://admin.og.local/cli/testing_ogp24373/index/test_DowngradeFromCronjob/5357/3
	 * Cli,
	 * php public/index.php cli/testing_ogp24373/index/test_DowngradeFromCronjob 5357 3 | w3m -T text/html > ../logs/ogp24373.5357.test_DowngradeFromCronjob.log
	 *
	 * @param array|integer $theTestPlayerInfo If it is array, the array should be return from "utils4testogp::_searchTestPlayerByPlayerId()" or 'utils4testogp::_searchTestPlayerList()'.
	 * If it is numeric,(number string or integer), the integer should be the field, 'player.player_id'.
	 * If it is empty, that's means get the test player from utils4testogp::_searchTestPlayerList().
	 *
	 * @param integer $limitCaseAmount The Combined Case amount limit.default will be all cases.
	 * @return void
	 */
	public function DELtest_DowngradeFromCronjob($theTestPlayerInfo = [] // # 1
		, $limitCaseAmount = 999999 // # 2
	){
	// 	if( ! $this->isEnableTesting ){
	// 		return false;
	// 	}
	// 	$this->load->model(['group_level']);
	// 	$now = new Datetime();
	//
	// 	if( empty($theTestPlayerInfo) ){
	// 		$offsetDayRange = '180';
	// 		$limit = 5;
	// 		$params = [$offsetDayRange, $limit];
	// 		$rows = call_user_func_array([$this->utils4testogp, '_searchTestPlayerListFilteredLowestLevel'], $params); // $rows = $this->utils4testogp->_searchTestPlayerList($offsetDayRange, $limit);
	// 		$theTestPlayerInfo = $rows[0]; // for $rows[3] player_id=5357
	// 	}
	//
	// 	$thePlayerId = 0;
	// 	if( ! empty($theTestPlayerInfo)){
	// 		if( is_array($theTestPlayerInfo) ){
	// 			$thePlayerId = $theTestPlayerInfo['player_id'];
	// 		}else if( is_numeric($theTestPlayerInfo) ){
	// 			// this case the param,$theTestPlayerInfo should be thePlayerId.
	// 			$thePlayerId = $theTestPlayerInfo;
	// 			$rows = $this->utils4testogp->_searchTestPlayerByPlayerId($thePlayerId);
	// 			$theTestPlayerInfo = $rows[0]; // reload theTestPlayerInfo for vipsettingcashbackruleId
	// 		}
	// 		$original_vipsettingcashbackruleId = $theTestPlayerInfo['vipsettingcashbackruleId'];
	// 	}
	//
	// 	$theCombinedCase = 'CACB.EmptyPeriodMode.PeriodNotMet.OnLevelMaintainEnable.InMaintainTime.IsMetLevelMaintainCondition.NoAccumulation';
	// 	// CACB / SACB / CASB / SASB
	// 	// NoAccumulation, AccumulationYesLastChangePeriod, AccumulationYesRegistrationDate[TRIED], AccumulationLastChangePeriodResetIfMet
	// 	// OnLevelMaintainEnable / OffLevelMaintainEnable [TRIED]
	// 	// EmptyPeriodMode / DailyPeriodMode / Weekly1PeriodMode / Monthly5PeriodMode [TRIED]
	// 	// PeriodNotMet / PeriodIsMet [TRIED]
	// 	// InMaintainTime / OverMaintainTime [TRIED]
	// 	// IsMetLevelMaintainCondition / NotMetLevelMaintainCondition [TRIED]
	// 	$theCombinedCase = 'CACB.Monthly5PeriodMode.PeriodIsMet.OnLevelMaintainEnable.InMaintainTime.IsMetLevelMaintainCondition.AccumulationYesLastChangePeriod';
	// 	$theCombinedCase = 'CACB.Monthly5PeriodMode.PeriodIsMet.OnLevelMaintainEnable.OverMaintainTime.IsMetLevelMaintainCondition.AccumulationYesLastChangePeriod';
	// 	$theCombinedCase = 'CACB.Monthly5PeriodMode.PeriodNotMet.OnLevelMaintainEnable.InMaintainTime.IsMetLevelMaintainCondition.AccumulationYesLastChangePeriod';
	// 	$theCombinedCase = 'CACB.Monthly5PeriodMode.PeriodNotMet.OnLevelMaintainEnable.OverMaintainTime.IsMetLevelMaintainCondition.AccumulationYesLastChangePeriod';
	// 	$theCombinedCase = 'SACB.Monthly5PeriodMode.PeriodIsMet.OnLevelMaintainEnable.InMaintainTime.IsMetLevelMaintainCondition.AccumulationYesLastChangePeriod';
	// 	// done, debug for test_MaintainTimeOfLogFilenameWithFileContents applied.
	// 	$theCombinedCase = 'CACB.EmptyPeriodMode.PeriodNotMet.OnLevelMaintainEnable.OverMaintainTime.IsMetLevelMaintainCondition.NoAccumulation';
	//
	// 	// issue case has resolved.
	// 	// $theCombinedCase = 'CACB.DailyPeriodMode.PeriodNotMet.OnLevelMaintainEnable.OverMaintainTime.IsMetLevelMaintainCondition.NoAccumulation';
	// 	// CACB.Weekly1PeriodMode.PeriodIsMet.OnLevelMaintainEnable.InMaintainTime.IsMetLevelMaintainCondition.NoAccumulation
	// 	// $theCombinedCase = 'CACB.Weekly1PeriodMode.PeriodIsMet.OnLevelMaintainEnable.InMaintainTime.IsMetLevelMaintainCondition.NoAccumulation'; // patched
	// 	// $theCombinedCase = 'CACB.Monthly5PeriodMode.PeriodIsMet.OnLevelMaintainEnable.InMaintainTime.IsMetLevelMaintainCondition.NoAccumulation'; // patched
	// 	// $theCombinedCase = 'SACB.DailyPeriodMode.PeriodNotMet.OnLevelMaintainEnable.OverMaintainTime.IsMetLevelMaintainCondition.NoAccumulation'; // DailyPeriodMode.PeriodNotMet 不可能發生
	// 	//
	// 	// // Patch for the cause by test data is exists.
	// 	// $theCombinedCase = 'CACB.Weekly1PeriodMode.PeriodIsMet.OnLevelMaintainEnable.InMaintainTime.IsMetLevelMaintainCondition.NoAccumulation';
	// 	// $_combinedCaseList[] = $theCombinedCase;
	// 	// $theCombinedCase = 'CACB.EmptyPeriodMode.PeriodNotMet.OnLevelMaintainEnable.OverMaintainTime.IsMetLevelMaintainCondition.NoAccumulation';
	// 	// $_combinedCaseList[] = $theCombinedCase;
	// 	//
	// 	// Patch for ignore in OffLevelMaintainEnable
	// 	// $theCombinedCase = 'CACB.EmptyPeriodMode.PeriodNotMet.OffLevelMaintainEnable.InMaintainTime.IsMetLevelMaintainCondition.NoAccumulation';
	// 	// $_combinedCaseList[] = $theCombinedCase;
	// 	//
	// 	$theCombinedCase = 'CACB.Weekly1PeriodMode.PeriodNotMet.OnLevelMaintainEnable.OverMaintainTime.IsMetLevelMaintainCondition.NoAccumulation';
	// 	$_combinedCaseList[] = $theCombinedCase;
	// 	//
	// 	//
	// 	$doOutputHtml = false;
	// 	$theCombinedCaseList = $this->test_getCombinedCaseList($doOutputHtml);
	// 	$_combinedCaseList = array_slice($theCombinedCaseList, 0, $limitCaseAmount);
	// 	// $_combinedCaseList = array_slice($theCombinedCaseList, 0, 3); // test code doe develop, ignore for online
	// 	//
	// 	// $_combinedCaseList = $theCombinedCaseList;
	// 	$caseTotalAmount = count($_combinedCaseList);
	// 	foreach($_combinedCaseList as $keyNumber => $currCase){
	// 		$theCombinedCase = $currCase;
	// 		$this->utils->debug_log('1223.theCombinedCase:', $theCombinedCase, 'caseTotalAmount:', $caseTotalAmount, 'keyNumber:', $keyNumber);
	// 		list($isSA, $isSB) = $this->_getIsSAIsSBFromCombinedCase($theCombinedCase);
	//
	// 		// // ref. to _getDowngradeLevelSettingFn
	// 		// $getUpgradeLevelSettingFn = '_getDowngradeLevelSettingFn'; // so far, CACB
	// 		list($settingName, $getUpgradeLevelSettingFn) = $this->_getUpgradeLevelSettingFnAndSettingNameFromCombinedCase($theCombinedCase);
	//
	// 		list($periodMode, $periodValue, $TEB_DateTime, $enableLevelMaintainFn) = $this->_getPeriodModeAndPeriodValueAndTEB_DateTimeAndEnableLevelMaintainFnFromCombinedCase($thePlayerId, $theCombinedCase);
	//
	// 		$testConditionFn = $this->utils4testogp->_getTestConditionFnFromCombinedCase($theCombinedCase); // '_testConditionFn4beforeDiffAfterV2'; // _testConditionFn4beforeDiffAfterV2, _testConditionFn4beforeSameAsAfterV2
	//
	// 		$params = [ $theTestPlayerInfo // # 1
	// 			, $periodMode // # 2
	// 			, $periodValue // # 3
	// 			, $TEB_DateTime // # 4
	// 			, $testConditionFn // # 5 is_string for function name
	// 			, $enableLevelMaintainFn // # 6 Level Maintain / Downgrade Guaranteed
	// 			, $getUpgradeLevelSettingFn // # 7
	// 			, $isSA // isSA = false mean CA isAccumulationSeparatedInConfig
	// 			, $isSB // isSB = false mean CB isBettingSeparatedInConfig
	// 			, $theCombinedCase
	// 		];
	// 		$funcName = '_tryDowngradeSuccessTriggerFromCronjobV2';
	// 		$rlt = call_user_func_array([$this, $funcName], $params); // $rlt = $this->_tryUpgradeSuccessTriggerFromCronjob($theTestPlayerInfo,...
	//
	// 		// clear from yesterday.
	// 		$playerId = $theTestPlayerInfo['player_id'];
	// 		$now->modify('- 1 day');
	// 		$nowYmdHis = $now->format('Y-m-d H:i:s');
	// 		$this->try_revertThisCaseData($playerId, $original_vipsettingcashbackruleId, $nowYmdHis);
	//
	// 	}// EOF foreach($theCombinedCaseList as $currCase){...
	//
	} // EOF test_DowngradeFromCronjob

	// http://admin.og.local/cli/testing_ogp24373/index/getCombinWithPeriodModeCaseListAndPeriodIsMetCaseListAndLevelMaintainTimeCaseList4FromSBE
	/**
	 * Get Combin With CommonSeparateModeCaseList, PeriodModeCaseList, ConditionMetList, LevelMaintainTimeCaseList, LevelMaintainEnableCaseList, LevelMaintainConditionCaseList and AccumulationCaseList
	 * [TESTED]
	 * http://admin.og.local/cli/testing_ogp24373/index/getCombinWithPeriodModeCaseListAndPeriodIsMetCaseListAndLevelMaintainTimeCaseList4FromSBE
	 *
	 * @return array
	 */
	public function getCombinWithPeriodModeCaseListAndPeriodIsMetCaseListAndLevelMaintainTimeCaseList4FromSBE(){
		$theCaseKindList = $this->utils4testogp->_assignCaseKindList4FromSBE();
		$theCommonSeparateModeCaseList = $theCaseKindList['theCommonSeparateModeCaseList'];
		$thePeriodModeCaseList = $theCaseKindList['thePeriodModeCaseList'];
		$theConditionMetList = $theCaseKindList['theConditionMetList'];

		$theLevelMaintainEnableCaseList = $theCaseKindList['theLevelMaintainEnableCaseList'];
		$theLevelMaintainTimeCaseList = $theCaseKindList['theLevelMaintainTimeCaseList'];
		$theLevelMaintainConditionCaseList = $theCaseKindList['theLevelMaintainConditionCaseList'];
		$theAccumulationCaseList = $theCaseKindList['theAccumulationCaseList'];

		$array = [ array_keys($thePeriodModeCaseList)
			, array_keys($theConditionMetList)
			, array_keys($theLevelMaintainTimeCaseList)
			, array_keys($theLevelMaintainEnableCaseList)
			, array_keys($theLevelMaintainConditionCaseList)
		]; // 5 params
		echo '<pre>';
		$phpCode = $this->_genPhpCode4EachCaseList($array);
		print_r($phpCode);

	} // EOF getCombinWithPeriodModeCaseListAndPeriodIsMetCaseListAndLevelMaintainTimeCaseList4FromSBE

	/**
	 * Get Combin With the following list,
	 * CommonSeparateModeCaseList,
	 * PeriodModeCaseList,
	 * PeriodIsMetCaseList,
	 * LevelMaintainTimeCaseList,
	 * LevelMaintainEnableCaseList,
	 * LevelMaintainConditionCaseList and AccumulationCaseList.
	 * [TESTED]
	 * http://admin.og.local/cli/testing_ogp24373/index/getCombinWithPeriodModeCaseListAndPeriodIsMetCaseListAndLevelMaintainTimeCaseList
	 *
	 * @return array
	 */
	public function getCombinWithPeriodModeCaseListAndPeriodIsMetCaseListAndLevelMaintainTimeCaseList(){

		$theCaseKindList = $this->utils4testogp->_assignCaseKindList();
		$theCommonSeparateModeCaseList = $theCaseKindList['theCommonSeparateModeCaseList'];
		$thePeriodModeCaseList = $theCaseKindList['thePeriodModeCaseList'];
		$thePeriodIsMetCaseList = $theCaseKindList['thePeriodIsMetCaseList'];
		$theLevelMaintainEnableCaseList = $theCaseKindList['theLevelMaintainEnableCaseList'];
		$theLevelMaintainTimeCaseList = $theCaseKindList['theLevelMaintainTimeCaseList'];
		$theLevelMaintainConditionCaseList = $theCaseKindList['theLevelMaintainConditionCaseList'];
		$theAccumulationCaseList = $theCaseKindList['theAccumulationCaseList'];

		$array = [ array_keys($thePeriodModeCaseList)
			, array_keys($thePeriodIsMetCaseList)
			, array_keys($theLevelMaintainTimeCaseList)
			, array_keys($theLevelMaintainEnableCaseList)
			, array_keys($theLevelMaintainConditionCaseList)

			, array_keys($theAccumulationCaseList)
		]; // 5 params

		echo '<pre>';
		$phpCode = $this->utils4testogp->_genPhpCode4EachCaseList($array);
		print_r($phpCode);
	} // EOF getCombinWithPeriodModeCaseListAndPeriodIsMetCaseListAndLevelMaintainTimeCaseList


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
				// $thePeriodInfo = $this->_parsePeriodInfoInPeriod_down($thePeriodDownStr);
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

			// $theJson = $this->_getPeriodJson($_periodMode, $_periodValue, $isHourly, $extraData);
			$theJson = $this->utils4testogp->_getPeriodJson($_periodMode,$_periodValue, $isHourly, $extraData);

			$params = [$thePlayerId, $gradeMode, $theJson];
			$rlt = call_user_func_array([$this, '_preSetupPeriodInPlayerCurrentLevel'], $params);// $rlt = $this->_preSetupPeriodInPlayerCurrentLevel($thePlayerId, $gradeMode, $theJson);

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

	public function _getPeriodModeAndPeriodValueAndTEB_DateTimeAndEnableLevelMaintainFnFromCombinedCase4FromSBE( $thePlayerId
		, $theCombinedCase = 'CACB.EmptyPeriodMode.PeriodNotMet.OnLevelMaintainEnable.InMaintainTime.IsMetLevelMaintainCondition.NoAccumulation'
	){
		$this->load->model(['group_level', 'player']);
		// should be return [$periodMode, $periodValue, $TEB_DateTime, $enableLevelMaintainFn].

		// EmptyPeriodMode / DailyPeriodMode / Weekly1PeriodMode / Monthly5PeriodMode
		$isEmptyPeriodMode = $this->utils4testogp->_pos($theCombinedCase, 'EmptyPeriodMode');
		$isDailyPeriodMode = $this->utils4testogp->_pos($theCombinedCase, 'DailyPeriodMode');
		$isWeekly1PeriodMode = $this->utils4testogp->_pos($theCombinedCase, 'Weekly1PeriodMode');
		$isMonthly5PeriodMode = $this->utils4testogp->_pos($theCombinedCase, 'Monthly5PeriodMode');
		//
		/// IsDowngradeConditionMet / IsDowngradeConditionNotMet
		$isIsDowngradeConditionMet = $this->utils4testogp->_pos($theCombinedCase, 'IsDowngradeConditionMet');
		$isIsDowngradeConditionNotMet = $this->utils4testogp->_pos($theCombinedCase, 'IsDowngradeConditionNotMet');
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
		$isAccumulationLastChangePeriodResetIfMet = $this->utils4testogp->_pos($theCombinedCase, 'AccumulationLastChangePeriodResetIfMet');


		/// gen by the URI, http://admin.og.local/cli/testing_ogp24373/index/getCombinWithPeriodModeCaseListAndPeriodIsMetCaseListAndLevelMaintainTimeCaseList4FromSBE
		// ====
		if( $isEmptyPeriodMode && $isIsDowngradeConditionMet && $isInMaintainTime && $isOnLevelMaintainEnable && $isIsMetLevelMaintainCondition ){ // # 0
			$caseNo = 0;
			}else if( $isEmptyPeriodMode && $isIsDowngradeConditionMet && $isInMaintainTime && $isOnLevelMaintainEnable && $isNotMetLevelMaintainCondition ){ // # 1
				$caseNo = 1;
			}else if( $isEmptyPeriodMode && $isIsDowngradeConditionMet && $isInMaintainTime && $isOffLevelMaintainEnable && $isIsMetLevelMaintainCondition ){ // # 2
				$caseNo = 2;
			}else if( $isEmptyPeriodMode && $isIsDowngradeConditionMet && $isInMaintainTime && $isOffLevelMaintainEnable && $isNotMetLevelMaintainCondition ){ // # 3
				$caseNo = 3;
			}else if( $isEmptyPeriodMode && $isIsDowngradeConditionMet && $isOverMaintainTime && $isOnLevelMaintainEnable && $isIsMetLevelMaintainCondition ){ // # 4
				$caseNo = 4;
			}else if( $isEmptyPeriodMode && $isIsDowngradeConditionMet && $isOverMaintainTime && $isOnLevelMaintainEnable && $isNotMetLevelMaintainCondition ){ // # 5
				$caseNo = 5;
			}else if( $isEmptyPeriodMode && $isIsDowngradeConditionMet && $isOverMaintainTime && $isOffLevelMaintainEnable && $isIsMetLevelMaintainCondition ){ // # 6
				$caseNo = 6;
			}else if( $isEmptyPeriodMode && $isIsDowngradeConditionMet && $isOverMaintainTime && $isOffLevelMaintainEnable && $isNotMetLevelMaintainCondition ){ // # 7
				$caseNo = 7;
			}else if( $isEmptyPeriodMode && $isIsDowngradeConditionNotMet && $isInMaintainTime && $isOnLevelMaintainEnable && $isIsMetLevelMaintainCondition ){ // # 8
				$caseNo = 8;
			}else if( $isEmptyPeriodMode && $isIsDowngradeConditionNotMet && $isInMaintainTime && $isOnLevelMaintainEnable && $isNotMetLevelMaintainCondition ){ // # 9
				$caseNo = 9;
			}else if( $isEmptyPeriodMode && $isIsDowngradeConditionNotMet && $isInMaintainTime && $isOffLevelMaintainEnable && $isIsMetLevelMaintainCondition ){ // # 10
				$caseNo = 10;
			}else if( $isEmptyPeriodMode && $isIsDowngradeConditionNotMet && $isInMaintainTime && $isOffLevelMaintainEnable && $isNotMetLevelMaintainCondition ){ // # 11
				$caseNo = 11;
			}else if( $isEmptyPeriodMode && $isIsDowngradeConditionNotMet && $isOverMaintainTime && $isOnLevelMaintainEnable && $isIsMetLevelMaintainCondition ){ // # 12
				$caseNo = 12;
			}else if( $isEmptyPeriodMode && $isIsDowngradeConditionNotMet && $isOverMaintainTime && $isOnLevelMaintainEnable && $isNotMetLevelMaintainCondition ){ // # 13
				$caseNo = 13;
			}else if( $isEmptyPeriodMode && $isIsDowngradeConditionNotMet && $isOverMaintainTime && $isOffLevelMaintainEnable && $isIsMetLevelMaintainCondition ){ // # 14
				$caseNo = 14;
			}else if( $isEmptyPeriodMode && $isIsDowngradeConditionNotMet && $isOverMaintainTime && $isOffLevelMaintainEnable && $isNotMetLevelMaintainCondition ){ // # 15
				$caseNo = 15;
			}else if( $isDailyPeriodMode && $isIsDowngradeConditionMet && $isInMaintainTime && $isOnLevelMaintainEnable && $isIsMetLevelMaintainCondition ){ // # 16
				$caseNo = 16;
			}else if( $isDailyPeriodMode && $isIsDowngradeConditionMet && $isInMaintainTime && $isOnLevelMaintainEnable && $isNotMetLevelMaintainCondition ){ // # 17
				$caseNo = 17;
			}else if( $isDailyPeriodMode && $isIsDowngradeConditionMet && $isInMaintainTime && $isOffLevelMaintainEnable && $isIsMetLevelMaintainCondition ){ // # 18
				$caseNo = 18;
			}else if( $isDailyPeriodMode && $isIsDowngradeConditionMet && $isInMaintainTime && $isOffLevelMaintainEnable && $isNotMetLevelMaintainCondition ){ // # 19
				$caseNo = 19;
			}else if( $isDailyPeriodMode && $isIsDowngradeConditionMet && $isOverMaintainTime && $isOnLevelMaintainEnable && $isIsMetLevelMaintainCondition ){ // # 20
				$caseNo = 20;
			}else if( $isDailyPeriodMode && $isIsDowngradeConditionMet && $isOverMaintainTime && $isOnLevelMaintainEnable && $isNotMetLevelMaintainCondition ){ // # 21
				$caseNo = 21;
			}else if( $isDailyPeriodMode && $isIsDowngradeConditionMet && $isOverMaintainTime && $isOffLevelMaintainEnable && $isIsMetLevelMaintainCondition ){ // # 22
				$caseNo = 22;
			}else if( $isDailyPeriodMode && $isIsDowngradeConditionMet && $isOverMaintainTime && $isOffLevelMaintainEnable && $isNotMetLevelMaintainCondition ){ // # 23
				$caseNo = 23;
			}else if( $isDailyPeriodMode && $isIsDowngradeConditionNotMet && $isInMaintainTime && $isOnLevelMaintainEnable && $isIsMetLevelMaintainCondition ){ // # 24
				$caseNo = 24;
			}else if( $isDailyPeriodMode && $isIsDowngradeConditionNotMet && $isInMaintainTime && $isOnLevelMaintainEnable && $isNotMetLevelMaintainCondition ){ // # 25
				$caseNo = 25;
			}else if( $isDailyPeriodMode && $isIsDowngradeConditionNotMet && $isInMaintainTime && $isOffLevelMaintainEnable && $isIsMetLevelMaintainCondition ){ // # 26
				$caseNo = 26;
			}else if( $isDailyPeriodMode && $isIsDowngradeConditionNotMet && $isInMaintainTime && $isOffLevelMaintainEnable && $isNotMetLevelMaintainCondition ){ // # 27
				$caseNo = 27;
			}else if( $isDailyPeriodMode && $isIsDowngradeConditionNotMet && $isOverMaintainTime && $isOnLevelMaintainEnable && $isIsMetLevelMaintainCondition ){ // # 28
				$caseNo = 28;
			}else if( $isDailyPeriodMode && $isIsDowngradeConditionNotMet && $isOverMaintainTime && $isOnLevelMaintainEnable && $isNotMetLevelMaintainCondition ){ // # 29
				$caseNo = 29;
			}else if( $isDailyPeriodMode && $isIsDowngradeConditionNotMet && $isOverMaintainTime && $isOffLevelMaintainEnable && $isIsMetLevelMaintainCondition ){ // # 30
				$caseNo = 30;
			}else if( $isDailyPeriodMode && $isIsDowngradeConditionNotMet && $isOverMaintainTime && $isOffLevelMaintainEnable && $isNotMetLevelMaintainCondition ){ // # 31
				$caseNo = 31;
			}else if( $isWeekly1PeriodMode && $isIsDowngradeConditionMet && $isInMaintainTime && $isOnLevelMaintainEnable && $isIsMetLevelMaintainCondition ){ // # 32
				$caseNo = 32;
			}else if( $isWeekly1PeriodMode && $isIsDowngradeConditionMet && $isInMaintainTime && $isOnLevelMaintainEnable && $isNotMetLevelMaintainCondition ){ // # 33
				$caseNo = 33;
			}else if( $isWeekly1PeriodMode && $isIsDowngradeConditionMet && $isInMaintainTime && $isOffLevelMaintainEnable && $isIsMetLevelMaintainCondition ){ // # 34
				$caseNo = 34;
			}else if( $isWeekly1PeriodMode && $isIsDowngradeConditionMet && $isInMaintainTime && $isOffLevelMaintainEnable && $isNotMetLevelMaintainCondition ){ // # 35
				$caseNo = 35;
			}else if( $isWeekly1PeriodMode && $isIsDowngradeConditionMet && $isOverMaintainTime && $isOnLevelMaintainEnable && $isIsMetLevelMaintainCondition ){ // # 36
				$caseNo = 36;
			}else if( $isWeekly1PeriodMode && $isIsDowngradeConditionMet && $isOverMaintainTime && $isOnLevelMaintainEnable && $isNotMetLevelMaintainCondition ){ // # 37
				$caseNo = 37;
			}else if( $isWeekly1PeriodMode && $isIsDowngradeConditionMet && $isOverMaintainTime && $isOffLevelMaintainEnable && $isIsMetLevelMaintainCondition ){ // # 38
				$caseNo = 38;
			}else if( $isWeekly1PeriodMode && $isIsDowngradeConditionMet && $isOverMaintainTime && $isOffLevelMaintainEnable && $isNotMetLevelMaintainCondition ){ // # 39
				$caseNo = 39;
			}else if( $isWeekly1PeriodMode && $isIsDowngradeConditionNotMet && $isInMaintainTime && $isOnLevelMaintainEnable && $isIsMetLevelMaintainCondition ){ // # 40
				$caseNo = 40;
			}else if( $isWeekly1PeriodMode && $isIsDowngradeConditionNotMet && $isInMaintainTime && $isOnLevelMaintainEnable && $isNotMetLevelMaintainCondition ){ // # 41
				$caseNo = 41;
			}else if( $isWeekly1PeriodMode && $isIsDowngradeConditionNotMet && $isInMaintainTime && $isOffLevelMaintainEnable && $isIsMetLevelMaintainCondition ){ // # 42
				$caseNo = 42;
			}else if( $isWeekly1PeriodMode && $isIsDowngradeConditionNotMet && $isInMaintainTime && $isOffLevelMaintainEnable && $isNotMetLevelMaintainCondition ){ // # 43
				$caseNo = 43;
			}else if( $isWeekly1PeriodMode && $isIsDowngradeConditionNotMet && $isOverMaintainTime && $isOnLevelMaintainEnable && $isIsMetLevelMaintainCondition ){ // # 44
				$caseNo = 44;
			}else if( $isWeekly1PeriodMode && $isIsDowngradeConditionNotMet && $isOverMaintainTime && $isOnLevelMaintainEnable && $isNotMetLevelMaintainCondition ){ // # 45
				$caseNo = 45;
			}else if( $isWeekly1PeriodMode && $isIsDowngradeConditionNotMet && $isOverMaintainTime && $isOffLevelMaintainEnable && $isIsMetLevelMaintainCondition ){ // # 46
				$caseNo = 46;
			}else if( $isWeekly1PeriodMode && $isIsDowngradeConditionNotMet && $isOverMaintainTime && $isOffLevelMaintainEnable && $isNotMetLevelMaintainCondition ){ // # 47
				$caseNo = 47;
			}else if( $isMonthly5PeriodMode && $isIsDowngradeConditionMet && $isInMaintainTime && $isOnLevelMaintainEnable && $isIsMetLevelMaintainCondition ){ // # 48
				$caseNo = 48;
			}else if( $isMonthly5PeriodMode && $isIsDowngradeConditionMet && $isInMaintainTime && $isOnLevelMaintainEnable && $isNotMetLevelMaintainCondition ){ // # 49
				$caseNo = 49;
			}else if( $isMonthly5PeriodMode && $isIsDowngradeConditionMet && $isInMaintainTime && $isOffLevelMaintainEnable && $isIsMetLevelMaintainCondition ){ // # 50
				$caseNo = 50;
			}else if( $isMonthly5PeriodMode && $isIsDowngradeConditionMet && $isInMaintainTime && $isOffLevelMaintainEnable && $isNotMetLevelMaintainCondition ){ // # 51
				$caseNo = 51;
			}else if( $isMonthly5PeriodMode && $isIsDowngradeConditionMet && $isOverMaintainTime && $isOnLevelMaintainEnable && $isIsMetLevelMaintainCondition ){ // # 52
				$caseNo = 52;
			}else if( $isMonthly5PeriodMode && $isIsDowngradeConditionMet && $isOverMaintainTime && $isOnLevelMaintainEnable && $isNotMetLevelMaintainCondition ){ // # 53
				$caseNo = 53;
			}else if( $isMonthly5PeriodMode && $isIsDowngradeConditionMet && $isOverMaintainTime && $isOffLevelMaintainEnable && $isIsMetLevelMaintainCondition ){ // # 54
				$caseNo = 54;
			}else if( $isMonthly5PeriodMode && $isIsDowngradeConditionMet && $isOverMaintainTime && $isOffLevelMaintainEnable && $isNotMetLevelMaintainCondition ){ // # 55
				$caseNo = 55;
			}else if( $isMonthly5PeriodMode && $isIsDowngradeConditionNotMet && $isInMaintainTime && $isOnLevelMaintainEnable && $isIsMetLevelMaintainCondition ){ // # 56
				$caseNo = 56;
			}else if( $isMonthly5PeriodMode && $isIsDowngradeConditionNotMet && $isInMaintainTime && $isOnLevelMaintainEnable && $isNotMetLevelMaintainCondition ){ // # 57
				$caseNo = 57;
			}else if( $isMonthly5PeriodMode && $isIsDowngradeConditionNotMet && $isInMaintainTime && $isOffLevelMaintainEnable && $isIsMetLevelMaintainCondition ){ // # 58
				$caseNo = 58;
			}else if( $isMonthly5PeriodMode && $isIsDowngradeConditionNotMet && $isInMaintainTime && $isOffLevelMaintainEnable && $isNotMetLevelMaintainCondition ){ // # 59
				$caseNo = 59;
			}else if( $isMonthly5PeriodMode && $isIsDowngradeConditionNotMet && $isOverMaintainTime && $isOnLevelMaintainEnable && $isIsMetLevelMaintainCondition ){ // # 60
				$caseNo = 60;
			}else if( $isMonthly5PeriodMode && $isIsDowngradeConditionNotMet && $isOverMaintainTime && $isOnLevelMaintainEnable && $isNotMetLevelMaintainCondition ){ // # 61
				$caseNo = 61;
			}else if( $isMonthly5PeriodMode && $isIsDowngradeConditionNotMet && $isOverMaintainTime && $isOffLevelMaintainEnable && $isIsMetLevelMaintainCondition ){ // # 62
				$caseNo = 62;
			}else if( $isMonthly5PeriodMode && $isIsDowngradeConditionNotMet && $isOverMaintainTime && $isOffLevelMaintainEnable && $isNotMetLevelMaintainCondition ){ // # 63
				$caseNo = 63;
		}
		// ====
		$isUseDefault = null; // ignore should use the default

		$this->utils->debug_log('1661.isUseDefault',$isUseDefault);

		/// for Period setting and level maintain
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

		/// default without the period setting
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
		$this->utils->debug_log('1624.isUseDefault',$isUseDefault);

		$TEB_DateTime->modify('first day of this month')->modify('+ 1 day'); // 2th of this month

		/// for $TEB_DateTime, after compare the followings,
		// - theLevelMaintainTimeCaseList: InMaintainTime and OverMaintainTime
		// - thePeriodModeCaseList: EmptyPeriodMode, DailyPeriodMode, Weekly1PeriodMode and Monthly5PeriodMode
		if( $isInMaintainTime && $isEmptyPeriodMode ){ // just check level maintain under the case, isPeriodNotMet
			// TEB_DateTime Base on intoThisLevelDatetimeStr, and offset TEB_DateTime to In Maintain Time
			$intoThisLevelDatetime = new DateTime($intoThisLevelDatetimeStr);
			$TEB_DateTime = clone $intoThisLevelDatetime;
			$TEB_DateTime->modify('+ '. intval($downMaintainTimeLength). ' '. $downMaintainTimeUnit); // offset to downMaintainTime
			$TEB_DateTime->modify('- 1 day '); // offset within 1 day
			// $TEB_DateTime->modify('+ '. ( intval($downMaintainTimeLength)- 1). ' '. $downMaintainTimeUnit); // offset within 3 day

			$TEB_YmdHis = $TEB_DateTime->format('Y-m-d H:i:s');
			$theNearBy = null;
			$this->utils->debug_log('2029.TEB_DateTime',$TEB_DateTime, 'TEB_YmdHis:', $TEB_YmdHis, 'thePeriodSetting:', $thePeriodSetting, 'theNearBy:', $theNearBy, 'theCombinedCase:', $theCombinedCase);
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
			$this->utils->debug_log('2041.TEB_DateTime',$TEB_DateTime, 'TEB_YmdHis:', $TEB_YmdHis, 'thePeriodSetting:', $thePeriodSetting, 'theNearBy:', $theNearBy, 'theCombinedCase:', $theCombinedCase);
		}else if( $isOverMaintainTime && $isEmptyPeriodMode ){
			// TEB_DateTime Base on intoThisLevelDatetimeStr, and offset TEB_DateTime to In Maintain Time
			$intoThisLevelDatetime = new DateTime($intoThisLevelDatetimeStr);
			$TEB_DateTime = clone $intoThisLevelDatetime;
			$TEB_DateTime->modify('+ '. intval($downMaintainTimeLength). ' '. $downMaintainTimeUnit); // offset to downMaintainTime
			$TEB_DateTime->modify('+ 1 day '); // offset over 1 day

			$theNearBy = 'later'; // for isOverMaintainTime
			$TEB_YmdHis = $TEB_DateTime->format('Y-m-d H:i:s');
			$this->utils->debug_log('2051.TEB_DateTime',$TEB_DateTime, 'TEB_YmdHis:', $TEB_YmdHis, 'thePeriodSetting:', $thePeriodSetting, 'theNearBy:', $theNearBy, 'theCombinedCase:', $theCombinedCase);
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
			$this->utils->debug_log('2063.TEB_DateTime',$TEB_DateTime, 'TEB_YmdHis:', $TEB_YmdHis, 'thePeriodSetting:', $thePeriodSetting, 'theNearBy:', $theNearBy, 'theCombinedCase:', $theCombinedCase);
		}

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

	} // EOF _getPeriodModeAndPeriodValueAndTEB_DateTimeAndEnableLevelMaintainFnFromCombinedCase4FromSBE

	/**
	 * To get the params,$periodMode, $periodValue, $TEB_DateTime, $enableLevelMaintainFn for testCasea.
	 * [TESTED]
	 * @param integer $thePlayerId
	 * @param string $theCombinedCase
	 * @return array The script,"list($periodMode, $periodValue, $TEB_DateTime, $enableLevelMaintainFn )" for get the params.
	 */
	public function _getPeriodModeAndPeriodValueAndTEB_DateTimeAndEnableLevelMaintainFnFromCombinedCase( $thePlayerId
			, $theCombinedCase = 'CACB.EmptyPeriodMode.PeriodNotMet.OnLevelMaintainEnable.InMaintainTime.IsMetLevelMaintainCondition.NoAccumulation'
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
		$isAccumulationLastChangePeriodResetIfMet = $this->utils4testogp->_pos($theCombinedCase, 'AccumulationLastChangePeriodResetIfMet');


		/// gen by the URI, http://admin.og.local/cli/testing_ogp24373/index/getCombinWithPeriodModeCaseListAndPeriodIsMetCaseListAndLevelMaintainTimeCaseList
		// ====

		if( $isEmptyPeriodMode && $isPeriodIsMet && $isInMaintainTime && $isOnLevelMaintainEnable && $isIsMetLevelMaintainCondition ){ // # 0
			$caseNo = 0;
			}else if( $isEmptyPeriodMode && $isPeriodIsMet && $isInMaintainTime && $isOnLevelMaintainEnable && $isNotMetLevelMaintainCondition ){ // # 1
				$caseNo = 1;
			}else if( $isEmptyPeriodMode && $isPeriodIsMet && $isInMaintainTime && $isOffLevelMaintainEnable && $isIsMetLevelMaintainCondition ){ // # 2
				$caseNo = 2;
			}else if( $isEmptyPeriodMode && $isPeriodIsMet && $isInMaintainTime && $isOffLevelMaintainEnable && $isNotMetLevelMaintainCondition ){ // # 3
				$caseNo = 3;
			}else if( $isEmptyPeriodMode && $isPeriodIsMet && $isOverMaintainTime && $isOnLevelMaintainEnable && $isIsMetLevelMaintainCondition ){ // # 4
				$caseNo = 4;
			}else if( $isEmptyPeriodMode && $isPeriodIsMet && $isOverMaintainTime && $isOnLevelMaintainEnable && $isNotMetLevelMaintainCondition ){ // # 5
				$caseNo = 5;
			}else if( $isEmptyPeriodMode && $isPeriodIsMet && $isOverMaintainTime && $isOffLevelMaintainEnable && $isIsMetLevelMaintainCondition ){ // # 6
				$caseNo = 6;
			}else if( $isEmptyPeriodMode && $isPeriodIsMet && $isOverMaintainTime && $isOffLevelMaintainEnable && $isNotMetLevelMaintainCondition ){ // # 7
				$caseNo = 7;
			}else if( $isEmptyPeriodMode && $isPeriodNotMet && $isInMaintainTime && $isOnLevelMaintainEnable && $isIsMetLevelMaintainCondition ){ // # 8
				$caseNo = 8;
			}else if( $isEmptyPeriodMode && $isPeriodNotMet && $isInMaintainTime && $isOnLevelMaintainEnable && $isNotMetLevelMaintainCondition ){ // # 9
				$caseNo = 9;
			}else if( $isEmptyPeriodMode && $isPeriodNotMet && $isInMaintainTime && $isOffLevelMaintainEnable && $isIsMetLevelMaintainCondition ){ // # 10
				$caseNo = 10;
			}else if( $isEmptyPeriodMode && $isPeriodNotMet && $isInMaintainTime && $isOffLevelMaintainEnable && $isNotMetLevelMaintainCondition ){ // # 11
				$caseNo = 11;
			}else if( $isEmptyPeriodMode && $isPeriodNotMet && $isOverMaintainTime && $isOnLevelMaintainEnable && $isIsMetLevelMaintainCondition ){ // # 12
				$caseNo = 12;
			}else if( $isEmptyPeriodMode && $isPeriodNotMet && $isOverMaintainTime && $isOnLevelMaintainEnable && $isNotMetLevelMaintainCondition ){ // # 13
				$caseNo = 13;
			}else if( $isEmptyPeriodMode && $isPeriodNotMet && $isOverMaintainTime && $isOffLevelMaintainEnable && $isIsMetLevelMaintainCondition ){ // # 14
				$caseNo = 14;
			}else if( $isEmptyPeriodMode && $isPeriodNotMet && $isOverMaintainTime && $isOffLevelMaintainEnable && $isNotMetLevelMaintainCondition ){ // # 15
				$caseNo = 15;
			}else if( $isDailyPeriodMode && $isPeriodIsMet && $isInMaintainTime && $isOnLevelMaintainEnable && $isIsMetLevelMaintainCondition ){ // # 16
				$caseNo = 16;
			}else if( $isDailyPeriodMode && $isPeriodIsMet && $isInMaintainTime && $isOnLevelMaintainEnable && $isNotMetLevelMaintainCondition ){ // # 17
				$caseNo = 17;
			}else if( $isDailyPeriodMode && $isPeriodIsMet && $isInMaintainTime && $isOffLevelMaintainEnable && $isIsMetLevelMaintainCondition ){ // # 18
				$caseNo = 18;
			}else if( $isDailyPeriodMode && $isPeriodIsMet && $isInMaintainTime && $isOffLevelMaintainEnable && $isNotMetLevelMaintainCondition ){ // # 19
				$caseNo = 19;
			}else if( $isDailyPeriodMode && $isPeriodIsMet && $isOverMaintainTime && $isOnLevelMaintainEnable && $isIsMetLevelMaintainCondition ){ // # 20
				$caseNo = 20;
			}else if( $isDailyPeriodMode && $isPeriodIsMet && $isOverMaintainTime && $isOnLevelMaintainEnable && $isNotMetLevelMaintainCondition ){ // # 21
				$caseNo = 21;
			}else if( $isDailyPeriodMode && $isPeriodIsMet && $isOverMaintainTime && $isOffLevelMaintainEnable && $isIsMetLevelMaintainCondition ){ // # 22
				$caseNo = 22;
			}else if( $isDailyPeriodMode && $isPeriodIsMet && $isOverMaintainTime && $isOffLevelMaintainEnable && $isNotMetLevelMaintainCondition ){ // # 23
				$caseNo = 23;
			}else if( $isDailyPeriodMode && $isPeriodNotMet && $isInMaintainTime && $isOnLevelMaintainEnable && $isIsMetLevelMaintainCondition ){ // # 24
				$caseNo = 24;
			}else if( $isDailyPeriodMode && $isPeriodNotMet && $isInMaintainTime && $isOnLevelMaintainEnable && $isNotMetLevelMaintainCondition ){ // # 25
				$caseNo = 25;
			}else if( $isDailyPeriodMode && $isPeriodNotMet && $isInMaintainTime && $isOffLevelMaintainEnable && $isIsMetLevelMaintainCondition ){ // # 26
				$caseNo = 26;
			}else if( $isDailyPeriodMode && $isPeriodNotMet && $isInMaintainTime && $isOffLevelMaintainEnable && $isNotMetLevelMaintainCondition ){ // # 27
				$caseNo = 27;
			}else if( $isDailyPeriodMode && $isPeriodNotMet && $isOverMaintainTime && $isOnLevelMaintainEnable && $isIsMetLevelMaintainCondition ){ // # 28
				$caseNo = 28;
			}else if( $isDailyPeriodMode && $isPeriodNotMet && $isOverMaintainTime && $isOnLevelMaintainEnable && $isNotMetLevelMaintainCondition ){ // # 29
				$caseNo = 29;
			}else if( $isDailyPeriodMode && $isPeriodNotMet && $isOverMaintainTime && $isOffLevelMaintainEnable && $isIsMetLevelMaintainCondition ){ // # 30
				$caseNo = 30;
			}else if( $isDailyPeriodMode && $isPeriodNotMet && $isOverMaintainTime && $isOffLevelMaintainEnable && $isNotMetLevelMaintainCondition ){ // # 31
				$caseNo = 31;
			}else if( $isWeekly1PeriodMode && $isPeriodIsMet && $isInMaintainTime && $isOnLevelMaintainEnable && $isIsMetLevelMaintainCondition ){ // # 32
				$caseNo = 32;
			}else if( $isWeekly1PeriodMode && $isPeriodIsMet && $isInMaintainTime && $isOnLevelMaintainEnable && $isNotMetLevelMaintainCondition ){ // # 33
				$caseNo = 33;
			}else if( $isWeekly1PeriodMode && $isPeriodIsMet && $isInMaintainTime && $isOffLevelMaintainEnable && $isIsMetLevelMaintainCondition ){ // # 34
				$caseNo = 34;
			}else if( $isWeekly1PeriodMode && $isPeriodIsMet && $isInMaintainTime && $isOffLevelMaintainEnable && $isNotMetLevelMaintainCondition ){ // # 35
				$caseNo = 35;
			}else if( $isWeekly1PeriodMode && $isPeriodIsMet && $isOverMaintainTime && $isOnLevelMaintainEnable && $isIsMetLevelMaintainCondition ){ // # 36
				$caseNo = 36;
			}else if( $isWeekly1PeriodMode && $isPeriodIsMet && $isOverMaintainTime && $isOnLevelMaintainEnable && $isNotMetLevelMaintainCondition ){ // # 37
				$caseNo = 37;
			}else if( $isWeekly1PeriodMode && $isPeriodIsMet && $isOverMaintainTime && $isOffLevelMaintainEnable && $isIsMetLevelMaintainCondition ){ // # 38
				$caseNo = 38;
			}else if( $isWeekly1PeriodMode && $isPeriodIsMet && $isOverMaintainTime && $isOffLevelMaintainEnable && $isNotMetLevelMaintainCondition ){ // # 39
				$caseNo = 39;
			}else if( $isWeekly1PeriodMode && $isPeriodNotMet && $isInMaintainTime && $isOnLevelMaintainEnable && $isIsMetLevelMaintainCondition ){ // # 40
				$caseNo = 40;
			}else if( $isWeekly1PeriodMode && $isPeriodNotMet && $isInMaintainTime && $isOnLevelMaintainEnable && $isNotMetLevelMaintainCondition ){ // # 41
				$caseNo = 41;
			}else if( $isWeekly1PeriodMode && $isPeriodNotMet && $isInMaintainTime && $isOffLevelMaintainEnable && $isIsMetLevelMaintainCondition ){ // # 42
				$caseNo = 42;
			}else if( $isWeekly1PeriodMode && $isPeriodNotMet && $isInMaintainTime && $isOffLevelMaintainEnable && $isNotMetLevelMaintainCondition ){ // # 43
				$caseNo = 43;
			}else if( $isWeekly1PeriodMode && $isPeriodNotMet && $isOverMaintainTime && $isOnLevelMaintainEnable && $isIsMetLevelMaintainCondition ){ // # 44
				$caseNo = 44;
			}else if( $isWeekly1PeriodMode && $isPeriodNotMet && $isOverMaintainTime && $isOnLevelMaintainEnable && $isNotMetLevelMaintainCondition ){ // # 45
				$caseNo = 45;
			}else if( $isWeekly1PeriodMode && $isPeriodNotMet && $isOverMaintainTime && $isOffLevelMaintainEnable && $isIsMetLevelMaintainCondition ){ // # 46
				$caseNo = 46;
			}else if( $isWeekly1PeriodMode && $isPeriodNotMet && $isOverMaintainTime && $isOffLevelMaintainEnable && $isNotMetLevelMaintainCondition ){ // # 47
				$caseNo = 47;
			}else if( $isMonthly5PeriodMode && $isPeriodIsMet && $isInMaintainTime && $isOnLevelMaintainEnable && $isIsMetLevelMaintainCondition ){ // # 48
				$caseNo = 48;
			}else if( $isMonthly5PeriodMode && $isPeriodIsMet && $isInMaintainTime && $isOnLevelMaintainEnable && $isNotMetLevelMaintainCondition ){ // # 49
				$caseNo = 49;
			}else if( $isMonthly5PeriodMode && $isPeriodIsMet && $isInMaintainTime && $isOffLevelMaintainEnable && $isIsMetLevelMaintainCondition ){ // # 50
				$caseNo = 50;
			}else if( $isMonthly5PeriodMode && $isPeriodIsMet && $isInMaintainTime && $isOffLevelMaintainEnable && $isNotMetLevelMaintainCondition ){ // # 51
				$caseNo = 51;
			}else if( $isMonthly5PeriodMode && $isPeriodIsMet && $isOverMaintainTime && $isOnLevelMaintainEnable && $isIsMetLevelMaintainCondition ){ // # 52
				$caseNo = 52;
			}else if( $isMonthly5PeriodMode && $isPeriodIsMet && $isOverMaintainTime && $isOnLevelMaintainEnable && $isNotMetLevelMaintainCondition ){ // # 53
				$caseNo = 53;
			}else if( $isMonthly5PeriodMode && $isPeriodIsMet && $isOverMaintainTime && $isOffLevelMaintainEnable && $isIsMetLevelMaintainCondition ){ // # 54
				$caseNo = 54;
			}else if( $isMonthly5PeriodMode && $isPeriodIsMet && $isOverMaintainTime && $isOffLevelMaintainEnable && $isNotMetLevelMaintainCondition ){ // # 55
				$caseNo = 55;
			}else if( $isMonthly5PeriodMode && $isPeriodNotMet && $isInMaintainTime && $isOnLevelMaintainEnable && $isIsMetLevelMaintainCondition ){ // # 56
				$caseNo = 56;
			}else if( $isMonthly5PeriodMode && $isPeriodNotMet && $isInMaintainTime && $isOnLevelMaintainEnable && $isNotMetLevelMaintainCondition ){ // # 57
				$caseNo = 57;
			}else if( $isMonthly5PeriodMode && $isPeriodNotMet && $isInMaintainTime && $isOffLevelMaintainEnable && $isIsMetLevelMaintainCondition ){ // # 58
				$caseNo = 58;
			}else if( $isMonthly5PeriodMode && $isPeriodNotMet && $isInMaintainTime && $isOffLevelMaintainEnable && $isNotMetLevelMaintainCondition ){ // # 59
				$caseNo = 59;
			}else if( $isMonthly5PeriodMode && $isPeriodNotMet && $isOverMaintainTime && $isOnLevelMaintainEnable && $isIsMetLevelMaintainCondition ){ // # 60
				$caseNo = 60;
			}else if( $isMonthly5PeriodMode && $isPeriodNotMet && $isOverMaintainTime && $isOnLevelMaintainEnable && $isNotMetLevelMaintainCondition ){ // # 61
				$caseNo = 61;
			}else if( $isMonthly5PeriodMode && $isPeriodNotMet && $isOverMaintainTime && $isOffLevelMaintainEnable && $isIsMetLevelMaintainCondition ){ // # 62
				$caseNo = 62;
			}else if( $isMonthly5PeriodMode && $isPeriodNotMet && $isOverMaintainTime && $isOffLevelMaintainEnable && $isNotMetLevelMaintainCondition ){ // # 63
				$caseNo = 63;
		}
		// ====
		$isUseDefault = null; // ignore should use the default
		if( $isEmptyPeriodMode && $isPeriodIsMet ){ // $caseNo: 0 ~ 7
			// the case, isEmptyPeriodMode and isPeriodIsMet is impassable.
			$isUseDefault = true;
		}else if( $isEmptyPeriodMode && $isPeriodNotMet ){ // $caseNo: 8 ~ 15
			// the case, isEmptyPeriodMode and isPeriodNotMet. The Empty PeriodMode Never Met Period.
			$isUseDefault = true;
		}elseif( $isDailyPeriodMode && $isPeriodNotMet ){
			// the case, The Daily PeriodMode Always Met Period.
			$isUseDefault = true;
		}else{
			// other cases
			$isUseDefault = false;
		}
// echo '<pre>$isUseDefault.1499:';
// print_r($isUseDefault); //$isUseDefault = false;
		$this->utils->debug_log('2288.isUseDefault',$isUseDefault);

		/// for Period setting and level maintain
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
		$this->utils->debug_log('2318.intoThisLevelDatetimeStr',$intoThisLevelDatetimeStr, 'theLastGradeRecordRow:', $theLastGradeRecordRow );

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
			$this->utils->debug_log('2338.thePeriodSetting',$thePeriodSetting);
		}
		$this->utils->debug_log('2340.isUseDefault',$isUseDefault);
		if($isUseDefault == true){
			// clear the period settins
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
				$this->utils->debug_log('2360.TEB_DateTime',$TEB_DateTime, 'TEB_YmdHis:', $TEB_YmdHis, 'thePeriodSetting:', $thePeriodSetting, 'theNearBy:', $theNearBy, 'theCombinedCase:', $theCombinedCase);
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
				$this->utils->debug_log('2372.TEB_DateTime',$TEB_DateTime, 'TEB_YmdHis:', $TEB_YmdHis, 'thePeriodSetting:', $thePeriodSetting, 'theNearBy:', $theNearBy, 'theCombinedCase:', $theCombinedCase);
			}else if( $isOverMaintainTime && $isEmptyPeriodMode ){
				// TEB_DateTime Base on intoThisLevelDatetimeStr, and offset TEB_DateTime to In Maintain Time
				$intoThisLevelDatetime = new DateTime($intoThisLevelDatetimeStr);
				$TEB_DateTime = clone $intoThisLevelDatetime;
				$TEB_DateTime->modify('+ '. intval($downMaintainTimeLength). ' '. $downMaintainTimeUnit); // offset to downMaintainTime
				$TEB_DateTime->modify('+ 1 day '); // offset over 1 day

				$theNearBy = 'later'; // for isOverMaintainTime
				$TEB_YmdHis = $TEB_DateTime->format('Y-m-d H:i:s');
				$this->utils->debug_log('2382.TEB_DateTime',$TEB_DateTime, 'TEB_YmdHis:', $TEB_YmdHis, 'thePeriodSetting:', $thePeriodSetting, 'theNearBy:', $theNearBy, 'theCombinedCase:', $theCombinedCase);
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
				$this->utils->debug_log('2394.TEB_DateTime',$TEB_DateTime, 'TEB_YmdHis:', $TEB_YmdHis, 'thePeriodSetting:', $thePeriodSetting, 'theNearBy:', $theNearBy, 'theCombinedCase:', $theCombinedCase);
			}

		}else{

			$this->utils->debug_log('2399.isInMaintainTime',$isInMaintainTime
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
				$this->utils->debug_log('2416.TEB_DateTime',$TEB_DateTime, 'TEB_YmdHis:', $TEB_YmdHis, 'thePeriodSetting:', $thePeriodSetting, 'theNearBy:', $theNearBy, 'theCombinedCase:', $theCombinedCase);
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
				$this->utils->debug_log('2428.TEB_DateTime',$TEB_DateTime, 'TEB_YmdHis:', $TEB_YmdHis, 'thePeriodSetting:', $thePeriodSetting, 'theNearBy:', $theNearBy, 'theCombinedCase:', $theCombinedCase);
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
				$this->utils->debug_log('2440.TEB_DateTime',$TEB_DateTime, 'TEB_YmdHis:', $TEB_YmdHis, 'thePeriodSetting:', $thePeriodSetting, 'theNearBy:', $theNearBy, 'theCombinedCase:', $theCombinedCase);
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
				$this->utils->debug_log('2452.TEB_DateTime',$TEB_DateTime, 'TEB_YmdHis:', $TEB_YmdHis, 'thePeriodSetting:', $thePeriodSetting, 'theNearBy:', $theNearBy, 'theCombinedCase:', $theCombinedCase);
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


	public function _getIsSAIsSBFromCombinedCase($theCombinedCase = 'CACB.EmptyPeriodMode.PeriodNotMet.OnLevelMaintainEnable.InMaintainTime.IsMetLevelMaintainCondition.NoAccumulation'){
		return $this->utils4testogp->_getIsSAIsSBFromCombinedCase($theCombinedCase);
	} // EOF getIsSAIsSBFrom

	/**
	 * [TESTED] URI,
	 * 降級應該要失敗，因為週期不對：
	 * http://admin.og.local/cli/testing_ogp24373/index/tryDowngradeSuccessInCACBTriggerFromCronjob/0/monthly/1/now/1
	 * 降級應該要失敗，因為週期符合，但是保級保護著：
	 * http://admin.og.local/cli/testing_ogp24373/index/tryDowngradeSuccessInCACBTriggerFromCronjob/0/monthly/30/now/1/1
	 * 降級應該要成功，因為週期符合，保級關閉：
	 * http://admin.og.local/cli/testing_ogp24373/index/tryDowngradeSuccessInCACBTriggerFromCronjob/0/monthly/30/now/0/0
	 *
	 * 會議後的待辦：
	 * 沒有設定降級> 保級是否有運作? > 預計要有保級降級動作
	 * 升級，時時檢查，那 period 的 weekly and monthly. 的起始、終止時間為何？ hourly upgrade check , when begin and end for period? weekly and monthly.
	 * 透過 SBE 手動降級檢查，新保級是否有效果？
	 *
	 * 降級保級
	 *
	 * @param array $theTestPlayerInfo
	 * @param string $periodMode
	 * @param integer $periodValue
	 * @param string $TEB_DateTime
	 * @param integer $testConditionFn
	 * @param integer $enableLevelMaintainFn
	 * @param integer $getUpgradeLevelSettingFn
	 * @return void
	 */
	public function DELtryDowngradeSuccessInCACBTriggerFromCronjob( $theTestPlayerInfo = []
		, $periodMode = 'monthly'
		, $periodValue = 1
		, $TEB_DateTime = 'now'
		, $testConditionFn = 0
		, $enableLevelMaintainFn = 1
		, $getUpgradeLevelSettingFn = 0
	){


		// _tryDowngradeSuccessTriggerFromCronjobV2
		$params = [ $theTestPlayerInfo // # 1
			, $periodMode // # 2
			, $periodValue // # 3
			, $TEB_DateTime // # 4
			, $testConditionFn // # 5 is_string for function name
			, $enableLevelMaintainFn // # 6 Level Maintain / Downgrade Guaranteed
			, $getUpgradeLevelSettingFn // # 7
			, 0 // isSA = false mean CA isAccumulationSeparatedInConfig
			, 0 // isSB = false mean CB isBettingSeparatedInConfig
		];

		$rlt = call_user_func_array([$this, '_tryDowngradeSuccessTriggerFromCronjobV2'], $params); // $rlt = $this->_tryUpgradeSuccessTriggerFromCronjob($theTestPlayerInfo,...

	} // EOF tryDowngradeSuccessInCACBTriggerFromCronjob


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
	public function DEL_tryDowngradeSuccessTriggerFromCronjobV2( $theTestPlayerInfo = [] // # 1
		, $periodMode = 'monthly' // # 2
		, $periodValue = 1 // # 3
		, $TEB_DateTime = 'now' // # 4
		, $testConditionFn = 0 // # 5 is_string for function name
		, $enableLevelMaintainFn = 1 // # 6 Level Maintain / Downgrade Guaranteed
		, $getUpgradeLevelSettingFn = 0 // # 7
		, $isAccumulationSeparatedInConfig = 0  // # 8 // CA
		, $isBettingSeparatedInConfig = 0  // # 9 // CB
		, $theCombinedCase = '' // # 10 // for trace test case
		, $manual_batch = 0 // # 11 $manual_batch If it's 0, that's means false; If it's 1, that's means true,
		, $testInLogFileFn = null // #12 To test pre-set in the log file
	){
		$this->init();

		if( is_string($TEB_DateTime) ){
			//  string convert to DateTime
			$TEB_DateTime = new DateTime($TEB_DateTime);
		}
		$time_exec_begin = $this->utils->formatDateTimeForMysql($TEB_DateTime);

		if(empty($theTestPlayerInfo) ){ // empty for search the test player.
			$offsetDayRange = '180';
			$limit = 5;
			$params = [$offsetDayRange, $limit];
			$rows = call_user_func_array([$this->utils4testogp, '_searchTestPlayerList'], $params); // $rows = $this->utils4testogp->_searchTestPlayerList($offsetDayRange, $limit);
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
			$rlt = $this->_preSetupAccumulationAndBettingTo($isAccumulationSeparated, $isBettingSeparated);

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
			$theVipUpgradeSettingList = $this->_getVip_upgrade_settingListBySettingName($settingName);
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
			if( ! empty($theVipUpgradeSetting) ){
				// update the setting into the player current level.
				// ref. to _updateUpgradeIdInVipsettingcashbackrule()
				$vipsettingcashbackruleId = $currentvipsettingcashbackruleId;
				$upgrade_id = $theVipUpgradeSetting['upgrade_id'];
				$targetField='vip_downgrade_id'; // for downgrade
				$params = [$upgrade_id, $vipsettingcashbackruleId, $targetField];
				$rlt = call_user_func_array([$this, '_updateUpgradeIdInVipsettingcashbackrule'], $params); // $rlt = $this->_updateUpgradeIdInVipsettingcashbackrule($upgrade_id, $vipsettingcashbackruleId, $targetField);

				$note = sprintf($this->noteTpl, '[Step] hook setting into the player current level', var_export($params, true), var_export($rlt, true) );
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

				// $theJson = $this->_getPeriodJson($_periodMode, $_periodValue, $isHourly, $extraData);
				$theJson = $this->utils4testogp->_getPeriodJson($_periodMode,$_periodValue, $isHourly, $extraData);

				$params = [$thePlayerId, $gradeMode, $theJson];
				$rlt = call_user_func_array([$this, '_preSetupPeriodInPlayerCurrentLevel'], $params);// $rlt = $this->_preSetupPeriodInPlayerCurrentLevel($thePlayerId, $gradeMode, $theJson);
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
					$params = [$thePlayerId, $period_number, $period_total_deposit];
					$rlt = call_user_func_array([$this, '_preSetupGuaranteedDowngradeInPlayerCurrentLevel'], $params);// $rlt = $this->_preSetupGuaranteedDowngradeInPlayerCurrentLevel(...

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
			$params[] = $theTestPlayerInfo['player_id']. '_1'; // $playerId = null
			$params[] = $manual_batch; // $manual_batch = true
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
			$this->utils->debug_log('2841.xxx.idleTotalSec',$this->oghome);
			$this->utils->idleSec($idleTotalSec);
			$this->utils->debug_log('2843.yyy.idleTotalSec',$this->oghome);
			$isExecingCB = null;
			$funcList = [];
			$funcList[] = $func;
			$maxWaitingTimes = 30;
			$waitingSec = 1;
			$isOverWaitingTime = $this->utils->isOverWaitingTimeWithWaitingByPS($funcList, $isExecingCB, $maxWaitingTimes, $waitingSec. $this->oghome);
			if ( ! $isOverWaitingTime ) {
			}
			$this->utils->debug_log('2852.zzz.idleTotalSec',$this->oghome);

			$reloadPlayerLevel = $this->player->getPlayerCurrentLevel($theTestPlayerInfo['player_id']);
			$afterId = $reloadPlayerLevel[0]['vipsettingcashbackruleId'];

			// Check pe-setup in log file
			$funcStr = 'job_player_level_downgrade_by_playerId';
			$theLogFileList = $this->utils4testogp->getLogFileListViaCmd($cmd, $funcStr);
			$this->utils->debug_log('2860.theLogFileList',$theLogFileList);
// var_dump($theLogFileList);
			if( ! empty($theLogFileList) ){
				$theLogPathFilename = $theLogFileList[0];
				$logFileContents = $this->utils4testogp->util_readFile($theLogPathFilename);
				if( empty($testInLogFileFn) ){
					// $theCombinedCase
					$this->test_MaintainTimeOfLogFilenameWithFileContents($theLogPathFilename, $logFileContents, $theCombinedCase);
					/// @todo check the log for each combine with $cmd, https://regex101.com/r/jSqOcJ/1
					// InMaintainTime/ OnLevelMaintainEnable
					// isSufficient4RequiredDatetimeRange diffInSeconds
					// {"message":"isSufficient4RequiredDatetimeRange diffInSeconds","context":[74559,"2021-04-02 15:15:36~2021-04-01 18:32:57","diffInSeconds4Required",1209600,"2021-04-02 15:15:36~2021-03-19 15:15:36"],"level":100,"level_name":"DEBUG","channel":"default-og","datetime":"2021-04-07T15:15:40+08:00","trace":"../../models/group_level.php:7607@isSufficient4RequiredDatetimeRange > ../../models/group_level.php:7723@calcDatetimeRangeAndPreviousFromDatetime > ","extra":{"tags":{"request_id":"4b4833a13687d007b262257d2e9433f4","env":"live.og_local","version":"6.112.01.001","hostname":"default-og"},"process_id":17806,"memory_peak_usage":"34.25 MB","memory_usage":"32.25 MB"}}

					// OnLevelMaintainEnable / OffLevelMaintainEnable
					// enableDownMaintain
					// {"message":"downgrade 3710 isConditionMet","context":[true,"playerId:","5357","enableDownMaintain:",true,"isMet4DownMaintain:",false,"schedule:",{"enableDownMaintain":true,"downMaintainUnit":1,"downMaintainTimeLength":14,"downMaintainConditionDepositAmount":5,"downMaintainConditionBetAmount":6}],"level":100,"level_name":"DEBUG","channel":"default-og","datetime":"2021-04-07T15:15:40+08:00","trace":"../../models/group_level.php:2433@playerLevelAdjustDowngrade > ../../controllers/modules/lock_app_module.php:105@{closure} > ","extra":{"tags":{"request_id":"4b4833a13687d007b262257d2e9433f4","env":"live.og_local","version":"6.112.01.001","hostname":"default-og"},"process_id":17806,"memory_peak_usage":"34.25 MB","memory_usage":"32.25 MB"}}

					$this->test_PeriodIsMetOrNotOfLogFilenameWithFileContents($theLogPathFilename, $logFileContents, $theCombinedCase);
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
				}else if( gettype($testInLogFileFn) == 'string' ){
					$params = [$theLogPathFilename, $logFileContents, $theCombinedCase];
					call_user_func_array([$this, $testInLogFileFn], $params);// $rlt = $this->_testConditionFn4beforeDiffAfterV2(...
					$this->utils->debug_log('2893.will call user func', $testInLogFileFn, 'params:', $params, 'rlt:', $rlt);
				}else if( gettype($testInLogFileFn) == 'object' ){
					$testInLogFileFn($theLogPathFilename, $logFileContents, $theCombinedCase);
					$this->utils->debug_log('2896.will call Closure func. params:', $params, 'rlt:', $rlt);
				}

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
				list($rlt, $noteTpl) = call_user_func_array([$this, $testConditionFn], $params);// $rlt = $this->_testConditionFn4beforeDiffAfterV2(...
				$this->utils->debug_log('2919.will call user func', $testConditionFn, 'params:', $params, 'rlt:', $rlt);
			}else if( gettype($testConditionFn) == 'object' ){ // Custom function
				list($rlt, $noteTpl) = $testConditionFn($origId, $afterId);
				$this->utils->debug_log('2922.will call Closure func. params:', $params, 'rlt:', $rlt);
			}

			$theGenerateCallTrace = '';
			if($rlt !== true){
				$theGenerateCallTrace .= ' ';
				$theGenerateCallTrace .= PHP_EOL;
				$theGenerateCallTrace .= $this->utils->generateCallTrace();
			}

			$generatedCallTrace = $this->utils->generateCallTrace();
			$this->utils->debug_log('2933.generateCallTrace:',  $generatedCallTrace);
			$_theCombinedCase = $theCombinedCase;
			$isOGP21799 = $this->utils4testogp->_pos($generatedCallTrace, 'OGP21799');
			if( $isOGP21799 ){
				$_theCombinedCase .= '.OGP21799';
			}

			$note = sprintf($noteTpl, '[Check] Test case,"'.$_theCombinedCase.'" <br/> Compare Player Level: Before(params) / After(rlt).', var_export($origId,true), var_export($afterId,true). $theGenerateCallTrace );
			$this->test( $rlt // result
				,  true // expect
				, __METHOD__ // title
				, $note // note
			);

		}

	} // EOF DEL_tryDowngradeSuccessTriggerFromCronjobV2


	/**
	 * Test the Level Maintain Time related items,"InMaintainTime", "OnLevelMaintainEnable" in Log Filename.
	 *
	 * @param string $theLogFilename The log file path and name.
	 * @param string $theCombinedCase The Combined Case String.
	 * @return void
	 */
	public function test_MaintainTimeOfLogFilename(
		$theLogFilename = '/home/vagrant/Code/og/admin/application/logs/tmp_shell/job_player_level_downgrade_by_playerId_6aa4a851566ec0f89f9ff47c6960aa96.log'
		, $theCombinedCase = 'CASB.EmptyPeriodMode.PeriodIsMet.OnLevelMaintainEnable.InMaintainTime.IsMetLevelMaintainCondition.NoAccumulation'
	){
		$fileContents = $this->utils4testogp->util_readFile($theLogFilename);
		return $this->test_MaintainTimeOfLogFilenameWithFileContents($fileContents, $theCombinedCase);
	}// EOF test_MaintainTimeOfLogFilename

		/**
	 * Test the Level Maintain Time related items,"InMaintainTime", "OnLevelMaintainEnable" in Log Filename.
	 *
	 * @param string $theLogFilename The log file path and name.
	 * @param string $theFileContents The log file contents. If empty than will try to load the contents form $theLogFilename.
	 * @param string $theCombinedCase The Combined Case String.
	 * @return void
	 */
	public function test_DowngradeConditionMetOfLogFilenameWithFileContents( $theLogFilename = '/home/vagrant/Code/og/admin/application/logs/tmp_shell/job_player_level_downgrade_by_playerId_6aa4a851566ec0f89f9ff47c6960aa96.log'
		, $theFileContents = '{"message":"isSufficient4RequiredDatetimeRange diffInSeconds","context":[74559,"2021-04-02 15:15:36~2021-04-01 18:32:57","diffInSeconds4Required",1209600,"2021-04-02 15:15:36~2021-03-19 15:15:36"],"level":100,"level_name":"DEBUG","channel":"default-og","datetime":"2021-04-07T15:15:40+08:00","trace":"../../models/group_level.php:7607@isSufficient4RequiredDatetimeRange > ../../models/group_level.php:7723@calcDatetimeRangeAndPreviousFromDatetime > ","extra":{"tags":{"request_id":"4b4833a13687d007b262257d2e9433f4","env":"live.og_local","version":"6.112.01.001","hostname":"default-og"},"process_id":17806,"memory_peak_usage":"34.25 MB","memory_usage":"32.25 MB"}}'
		, $theCombinedCase = 'CASB.EmptyPeriodMode.PeriodIsMet.OnLevelMaintainEnable.InMaintainTime.IsMetLevelMaintainCondition.NoAccumulation'
	){

		$isIsDowngradeConditionMet = $this->utils4testogp->_pos($theCombinedCase, 'IsDowngradeConditionMet');
		$isIsDowngradeConditionNotMet = $this->utils4testogp->_pos($theCombinedCase, 'IsDowngradeConditionNotMet');

		if( empty($theFileContents) ){
			$theFileContents = $this->utils4testogp->util_readFile($theLogFilename);
		}

		// Ref. to https://regex101.com/r/3YAFU0/1/
		$re = '/downgrade 3710 isConditionMet","context":\[(?P<isConditionMet>[^,]+),"playerId:","?(?P<playerId>\d+)"?,"?enableDownMaintain[^"]?"?,(?P<enableDownMaintain>[^,]+),"?isMet4DownMaintain[^"]?"?,(?P<isMet4DownMaintain>[^,]+),/m';

		// // Ref. to https://regex101.com/r/QdGalp/1
		// $re = '/3413\.downgrade isConditionMet.*"context":\[(?P<isConditionMet>[^,]+),\".*isMet4DownMaintain:",(?P<isMet4DownMaintain>[^,]+),"playerId:"."?(?P<playerId>[\d]+)"?,/m';
		preg_match_all($re, $theFileContents, $matches, PREG_SET_ORDER, 0);

		// defaults
		$result = null;
		$params = [];
		$params['theCombinedCase'] = $theCombinedCase;
		// Print the entire match result
		// var_dump($matches);
		$isConditionMetList = [];
		$isMet4DownMaintainList = [];
		$playerIdList = [];
		$enableDownMaintainList = [];
		if( !empty($matches) ){
			foreach($matches as $matche){
				$isConditionMetList[] = $matche['isConditionMet'];
				$isMet4DownMaintainList[] = $matche['isMet4DownMaintain'];
				$playerIdList[] = $matche['playerId'];
				if(!empty($matche['enableDownMaintain'])){
					$enableDownMaintainList = $matche['enableDownMaintain'];
				}
			}
			$params['isConditionMetList'] = $isConditionMetList;
			$params['isMet4DownMaintainList'] = $isMet4DownMaintainList;
			$params['playerIdList'] = $playerIdList;
			if( ! empty($enableDownMaintainList) ){
				$params['enableDownMaintainList'] = $enableDownMaintainList;
			}
		}
		/// todo
		if($isIsDowngradeConditionMet){
			$result = $isConditionMetList[0] == 'true';
		}else if($isIsDowngradeConditionNotMet){
			$result = $isConditionMetList[0] == 'false';
		}

		$note = sprintf( $this->noteTpl, '[Check] The pre-setup for the downgrade Condition is Met or Not in the Log File, "'. $theLogFilename. '".' // # 1
										, var_export($params, true) // # 2
										, var_export($result, true) // # 3
									);
		return $this->test( $result // ! empty($vipsettingcashbackruleId) // result
				,  true // expect
				, __METHOD__ // title
				, $note // note
		);
	} // EOF test_DowngradeConditionMetOfLogFilenameWithFileContents
	/**
	 * Test the Level Maintain Time related items,"InMaintainTime", "OnLevelMaintainEnable" in Log Filename.
	 *
	 * @param string $theLogFilename The log file path and name.
	 * @param string $theFileContents The log file contents. If empty than will try to load the contents form $theLogFilename.
	 * @param string $theCombinedCase The Combined Case String.
	 * @return void
	 */
	public function test_MaintainTimeOfLogFilenameWithFileContents( $theLogFilename = '/home/vagrant/Code/og/admin/application/logs/tmp_shell/job_player_level_downgrade_by_playerId_6aa4a851566ec0f89f9ff47c6960aa96.log'
		, $theFileContents = '{"message":"isSufficient4RequiredDatetimeRange diffInSeconds","context":[74559,"2021-04-02 15:15:36~2021-04-01 18:32:57","diffInSeconds4Required",1209600,"2021-04-02 15:15:36~2021-03-19 15:15:36"],"level":100,"level_name":"DEBUG","channel":"default-og","datetime":"2021-04-07T15:15:40+08:00","trace":"../../models/group_level.php:7607@isSufficient4RequiredDatetimeRange > ../../models/group_level.php:7723@calcDatetimeRangeAndPreviousFromDatetime > ","extra":{"tags":{"request_id":"4b4833a13687d007b262257d2e9433f4","env":"live.og_local","version":"6.112.01.001","hostname":"default-og"},"process_id":17806,"memory_peak_usage":"34.25 MB","memory_usage":"32.25 MB"}}'
		, $theCombinedCase = 'CASB.EmptyPeriodMode.PeriodIsMet.OnLevelMaintainEnable.InMaintainTime.IsMetLevelMaintainCondition.NoAccumulation'
	){

		$isOffLevelMaintainEnable = $this->utils4testogp->_pos($theCombinedCase, 'OffLevelMaintainEnable');
		$isOnLevelMaintainEnable = $this->utils4testogp->_pos($theCombinedCase, 'OnLevelMaintainEnable');

		$isInMaintainTime = $this->utils4testogp->_pos($theCombinedCase, 'InMaintainTime');
		$isOverMaintainTime = $this->utils4testogp->_pos($theCombinedCase, 'OverMaintainTime');

		$isNotMetLevelMaintainCondition = $this->utils4testogp->_pos($theCombinedCase, 'NotMetLevelMaintainCondition');


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
		}else if($isOnLevelMaintainEnable && $isInMaintainTime){
			$params['ignoreBy'] = 'OnLevelMaintainEnable.InMaintainTime';
			$result = true; // always be true for ignore.
		}else if($isOnLevelMaintainEnable && $isOverMaintainTime && $isNotMetLevelMaintainCondition){
			$params['ignoreBy'] = 'OnLevelMaintainEnable.OverMaintainTime.NotMetLevelMaintainCondition';
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
	} // EOF test_MaintainTimeOfLogFilenameWithFileContents

	/**
	 * Test the Upgrade/Downgrade related setting for "PeriodIsMet" Or "PeriodNotMet" in Log Filename.
	 *
	 * @param string $theLogFilename The log file path and name.
	 * @param string $theCombinedCase The Combined Case String.
	 * @return void
	 */
	public function test_PeriodIsMetOrNotOfLogFilenameWithFileContents( $theLogFilename = '/home/vagrant/Code/og/admin/application/logs/tmp_shell/job_player_level_downgrade_by_playerId_6aa4a851566ec0f89f9ff47c6960aa96.log'
		, $theFileContents = '{"message":"OGP-20868.getScheduleDateRange.currentDate:","context":["2021-04-06 14:38:15 000000","subNumber:",1,"time_exec_begin:","2021-04-06 14:38:15","adjustGradeTo:","down","schedule:",{"daily":"00:00:00 - 23:59:59","enableDownMaintain":true,"downMaintainUnit":1,"downMaintainTimeLength":13,"downMaintainConditionDepositAmount":999999999,"downMaintainConditionBetAmount":999999999}],"level":100,"level_name":"DEBUG","channel":"default-og","datetime":"2021-04-07T15:16:59+08:00","trace":"../../models/group_level.php:4268@getScheduleDateRange > ../../models/group_level.php:2433@playerLevelAdjustDowngrade > ","extra":{"tags":{"request_id":"3ffec018fe18657b5cd4b5c7e6458adb","env":"live.og_local","version":"6.112.01.001","hostname":"default-og"},"process_id":18016,"memory_peak_usage":"34.25 MB","memory_usage":"32.25 MB"}}'
		, $theCombinedCase = 'CASB.EmptyPeriodMode.PeriodIsMet.OnLevelMaintainEnable.InMaintainTime.IsMetLevelMaintainCondition.NoAccumulation'
	){
		$isEmptyPeriodMode = $this->utils4testogp->_pos($theCombinedCase, 'EmptyPeriodMode');
		$isDailyPeriodMode = $this->utils4testogp->_pos($theCombinedCase, 'DailyPeriodMode');
		$isWeekly1PeriodMode = $this->utils4testogp->_pos($theCombinedCase, 'Weekly1PeriodMode');
		$isMonthly5PeriodMode = $this->utils4testogp->_pos($theCombinedCase, 'Monthly5PeriodMode');

		$isPeriodIsMet = $this->utils4testogp->_pos($theCombinedCase, 'PeriodIsMet');
		$isPeriodNotMet = $this->utils4testogp->_pos($theCombinedCase, 'PeriodNotMet');

		if( empty($theFileContents) ){
			$theFileContents = $this->utils4testogp->util_readFile($theLogFilename);
		}

		// defaults
		$result = null;
		$params = [];
		$params['theCombinedCase'] = $theCombinedCase;

		if( ! $isEmptyPeriodMode ){
			// Ref. to https://regex101.com/r/l8xZdP/2
			$re = '/getScheduleDateRange.currentDate:","context":\["(?P<currentDate>\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}).*schedule:",.*(?P<periodType>daily|weekly|monthly)":(?P<periodValue>\d+|["0-9:\- ]+),/m';
			// $fileContents = '{"message":"isSufficient4RequiredDatetimeRange diffInSeconds","context":[74559,"2021-04-02 15:15:36~2021-04-01 18:32:57","diffInSeconds4Required",1209600,"2021-04-02 15:15:36~2021-03-19 15:15:36"],"level":100,"level_name":"DEBUG","channel":"default-og","datetime":"2021-04-07T15:15:40+08:00","trace":"../../models/group_level.php:7607@isSufficient4RequiredDatetimeRange > ../../models/group_level.php:7723@calcDatetimeRangeAndPreviousFromDatetime > ","extra":{"tags":{"request_id":"4b4833a13687d007b262257d2e9433f4","env":"live.og_local","version":"6.112.01.001","hostname":"default-og"},"process_id":17806,"memory_peak_usage":"34.25 MB","memory_usage":"32.25 MB"}}';

			preg_match_all($re, $theFileContents, $matches, PREG_SET_ORDER, 0);

			$currentDateList = [];
			$periodTypeList = [];
			$periodValueList = [];
			if( !empty($matches) ){
				foreach($matches as $matche){
					$currentDateList[] = $matche['currentDate'];
					$periodTypeList[] = $matche['periodType'];
					$periodValueList[] = $matche['periodValue'];
				}
			}
			if( ! empty($currentDateList)
				&& ! empty($periodTypeList)
				&& ! empty($periodValueList)
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
	} // EOF test_PeriodIsMetOrNotOfLogFilenameWithFileContents

	// revert player level
	// clear settings in config
	// clear test data in vip_grade_report.
	public function try_revertThisCaseData($playerId, $original_vipsettingcashbackruleId, $nowYmdHis){
		// revert player level
		// $playerId = $theTestPlayerInfo['player_id'];
		$newPlayerLevel = $original_vipsettingcashbackruleId;
		$result = $this->group_level->adjustPlayerLevel($playerId, $newPlayerLevel);

		// clear settings in config
		$isAccumulationSeparated = null;
		$isBettingSeparated = null;
		$this->_preSetupAccumulationAndBettingTo($isAccumulationSeparated, $isBettingSeparated);

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
		$note .= 'Delete ' . $this->group_level->runRawUpdateInsertSQL($sql) . " rows from `vip_grade_report`". PHP_EOL;
		// echo $output;
		// $note = sprintf( $this->noteTpl, '[Step] Check the pre-setup for the related Period settings(for PeriodIsMet and PeriodNotMet) in the Log File, "'. $theLogFilename. '".' // # 1
		// 							, var_export($params, true) // # 2
		// 							, var_export($result, true) // # 3
		// 						);


		// PAAL = player_accumulated_amounts_log
		$sql4PAAL = <<<EOF
		DELETE FROM `player_accumulated_amounts_log`
		WHERE player_accumulated_amounts_log.player_id = $playerId
		AND`created_at` >= '$nowYmdHis' -- created_at is real time.
EOF;
$note .= 'Delete ' . $this->group_level->runRawUpdateInsertSQL($sql4PAAL) . " rows from `player_accumulated_amounts_log`". PHP_EOL;

		$this->utils->debug_log('3259.note:',$note);

		$this->test( true // ! empty($vipsettingcashbackruleId) // result
				,  true // expect
				, __METHOD__ // title
				, $note // note
		);
	}// EOF try_revertThisCaseData


	/**
	 * for test() Detects the $origId and $afterId should be the same.
	 *
	 * @param integer $origId The original Id ( or Value).
	 * @param integer $afterId The Id ( or Value) after test action.
	 * @return bool
	 */
	private function _testConditionFn4beforeSameAsAfter($origId, $afterId){
		return $origId == $afterId;
	} // EOF _testConditionFn4beforeSameAsAfter

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
	} // EOF _testConditionFnV2
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
	 * for test() Detects the $origId and $afterId should be the difference.
	 *
	 * @param integer $origId The original Id ( or Value).
	 * @param integer $afterId The Id ( or Value) after test action.
	 * @return bool
	 */
	private function DEL_testConditionFn4beforeDiffAfter($origId, $afterId){
		return $origId != $afterId;
	} // EOF _testConditionFn4beforeDiffAfter

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
				// $thePeriodInfo = $this->_parsePeriodInfoInPeriod_down($thePeriodDownStr);
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

			// $theJson = $this->_getPeriodJson($_periodMode, $_periodValue, $isHourly, $extraData);
			$theJson = $this->utils4testogp->_getPeriodJson($_periodMode,$_periodValue, $isHourly, $extraData);

			$params = [$thePlayerId, $gradeMode, $theJson];
			$rlt = call_user_func_array([$this, '_preSetupPeriodInPlayerCurrentLevel'], $params);// $rlt = $this->_preSetupPeriodInPlayerCurrentLevel($thePlayerId, $gradeMode, $theJson);
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
	 * To Get $settingName, $getUpgradeLevelSettingFn for test case setups
	 *
	 * @param string $theCombinedCase
	 * @param array $theTestPlayerInfo The return array of utils4testogp::_searchTestPlayerByPlayerId().
	 * @return array [$settingName, $getUpgradeLevelSettingFn]
	 */
	// public function DEL_getUpgradeLevelSettingFnAndSettingNameFromCombinedCaseAndTestPlayerInfo($theCombinedCase, $theTestPlayerInfo){
	// 	// @todo IsDowngradeConditionMet IsDowngradeConditionNotMet
	// 	// return $this->_getUpgradeLevelSettingFnAndSettingNameFromCombinedCase($theCombinedCase, $theTestPlayerInfo);
	// 	// $theTestPlayerInfo[game_description_id]
	// 	// will return [$settingName, $getUpgradeLevelSettingFn]

	// 	$isCACB = $this->utils4testogp->_pos($theCombinedCase, 'CACB');
	// 	$isSACB = $this->utils4testogp->_pos($theCombinedCase, 'SACB');
	// 	$isCASB = $this->utils4testogp->_pos($theCombinedCase, 'CASB');
	// 	$isSASB = $this->utils4testogp->_pos($theCombinedCase, 'SASB');

	// 	$isIsDowngradeConditionMet = $this->utils4testogp->_pos($theCombinedCase, 'IsDowngradeConditionMet');
	// 	$isIsDowngradeConditionNotMet = $this->utils4testogp->_pos($theCombinedCase, 'IsDowngradeConditionNotMet');

	// 	$isNoAccumulation = $this->utils4testogp->_pos($theCombinedCase, 'NoAccumulation');
	// 	$isAccumulationYesRegistrationDate = $this->utils4testogp->_pos($theCombinedCase, 'AccumulationYesRegistrationDate');
	// 	$isAccumulationYesLastChangePeriod = $this->utils4testogp->_pos($theCombinedCase, 'AccumulationYesLastChangePeriod');
	// 	$isAccumulationLastChangePeriodResetIfMet = $this->utils4testogp->_pos($theCombinedCase, 'AccumulationLastChangePeriodResetIfMet');
	// 	// $isAccumulationYesLastChangePeriodResetAlways = $this->utils4testogp->_pos($theCombinedCase, 'AccumulationYesLastChangePeriodResetAlways');

	// 	/// for $getUpgradeLevelSettingFn
	// 	$accumulation = 0; // default
	// 	if($isNoAccumulation){
	// 		$accumulation = 0; // Group_level::ACCUMULATION_MODE_DISABLE
	// 	}else if($isAccumulationYesRegistrationDate){
	// 		$accumulation = 1; // Group_level::ACCUMULATION_MODE_FROM_REGISTRATION
	// 	}else if($isAccumulationYesLastChangePeriod){
	// 		$accumulation = 4; // Group_level::ACCUMULATION_MODE_LAST_CHANGED_GEADE
	// 	}else if($isAccumulationLastChangePeriodResetIfMet){
	// 		$accumulation = 5; // Group_level::ACCUMULATION_MODE_LAST_CHANGED_GEADE_RESET_IF_MET
	// 	// }else if($isAccumulationYesLastChangePeriodResetAlways){
	// 	// 	$accumulation = 6; // Group_level::ACCUMULATION_MODE_LAST_CHANGED_GEADE_RESET_ALWAYS
	// 	}
	// 	/// handle DowngradeLevelSetting
	// 	$_this = $this;

	// 	if( $isCACB ){
	// 		$settingName = 'devDowngradeMet.CACB';
	// 		$forGrade = 'downgrade';
	// 		// $params = [$settingName, $forGrade];
	// 		// ref. to _getDowngradeLevelSettingFn > _syncUpgradeLevelSettingByName
	// 		// related _tryUpgradeSuccessTriggerFromCronjobV2
	// 		$data = [];
	// 		$data['setting_name'] = $settingName;
	// 		$data['description'] = $settingName. '.testing';
	// 		// $data['level_upgrade'] = 3; // for downgrade
	// 		/// CB
	// 		if( $isIsDowngradeConditionMet ){
	// 			// betAmount >= 0 ... Is Met
	// 			$betAmountMathSign = '>=';
	// 			$betAmountValue = 0;
	// 			$operatorBeforeDeposit = 'and';
	// 			// depositAmount >= 0
	// 			$depositAmountMathSign = '>=';
	// 			$depositAmountValue = 0;
	// 			$operatorBeforeLoss = 'and';
	// 			// lossAmount >= 0
	// 			$lossAmountMathSign = '>=';
	// 			$lossAmountValue = 0;
	// 			$operatorBeforeWin = null;
	// 			// ignore winAmount
	// 			$winAmountMathSign = null;
	// 			$winAmountValue = null;
	// 		}else if($isIsDowngradeConditionNotMet){
	// 			// betAmount < 0 ... Not MET
	// 			$betAmountMathSign = '<';
	// 			$betAmountValue = 0;
	// 			$operatorBeforeDeposit = 'and';
	// 			// depositAmount < 0
	// 			$depositAmountMathSign = '<';
	// 			$depositAmountValue = 0;
	// 			$operatorBeforeLoss = 'and';
	// 			// lossAmount < 0
	// 			$lossAmountMathSign = '<';
	// 			$lossAmountValue = 0;
	// 			$operatorBeforeWin = null;
	// 			// ignore winAmount
	// 			$winAmountMathSign = null;
	// 			$winAmountValue = null;
	// 		}

	// 		$formula = $this->_prepareFormulaOfVipUpgradeSetting($betAmountMathSign // #1
	// 			, $betAmountValue // #2
	// 			, $operatorBeforeDeposit // #3
	// 			, $depositAmountMathSign // #4
	// 			, $depositAmountValue // #5
	// 			, $operatorBeforeLoss // #6
	// 			, $lossAmountMathSign // #7
	// 			, $lossAmountValue // #8
	// 			, $operatorBeforeWin // #9
	// 			, $winAmountMathSign // #10
	// 			, $winAmountValue ); // #11
	// 		$data['formula'] = $formula;
	// 		$data['bet_amount_settings'] = NULL; // always be NULL
	// 		/// CA
	// 		$data['accumulation'] = $accumulation; // 0 / 1 / 4 : No / Yes, Registration Date / Yes, Last Change Period
	// 		$data['separate_accumulation_settings'] = NULL; // always be NULL
	// 		//
	// 		$params = [$settingName, $forGrade, $data];
	// 		$getUpgradeLevelSettingFn = function($_this) use ($params){
	// 			// '_getDowngradeLevelSettingFn'; // so far, CACB
	// 			// $params = [$settingName, $data];
	// 			// $theGenerateCallTrace = $this->utils->generateCallTrace();
	// 			// echo '<pre>';print_r($theGenerateCallTrace);exit();
	// 			return call_user_func_array([$_this->utils4testogp, '_getDowngradeLevelSettingFnV2'], $params); // $rlt = $this->_syncUpgradeLevelSettingByName($settingName, $data);
	// 		};

	// 	}else if( $isSACB ){
	// 		$settingName = 'devDowngradeMet.SACB';
	// 		$forGrade = 'downgrade';
	// 		// $params = [$settingName, $forGrade];
	// 		// ref. to _getDowngradeLevelSettingFn > _syncUpgradeLevelSettingByName
	// 		// related _tryUpgradeSuccessTriggerFromCronjobV2
	// 		$data = [];
	// 		$data['setting_name'] = $settingName;
	// 		$data['description'] = $settingName. '.testing';
	// 		// $data['level_upgrade'] = 3; // for downgrade
	// 		/// CB
	// 		if( $isIsDowngradeConditionMet ){
	// 			// betAmount >= 0 ... Is Met
	// 			$betAmountMathSign = '>=';
	// 			$betAmountValue = 0;
	// 			$operatorBeforeDeposit = 'and';
	// 			// depositAmount >= 0
	// 			$depositAmountMathSign = '>=';
	// 			$depositAmountValue = 0;
	// 			$operatorBeforeLoss = 'and';
	// 			// lossAmount >= 0
	// 			$lossAmountMathSign = '>=';
	// 			$lossAmountValue = 0;
	// 			$operatorBeforeWin = null;
	// 			// ignore winAmount
	// 			$winAmountMathSign = null;
	// 			$winAmountValue = null;
	// 		}else if($isIsDowngradeConditionNotMet){
	// 			// betAmount < 0 ... Not MET
	// 			$betAmountMathSign = '<';
	// 			$betAmountValue = 0;
	// 			$operatorBeforeDeposit = 'and';
	// 			// depositAmount < 0
	// 			$depositAmountMathSign = '<';
	// 			$depositAmountValue = 0;
	// 			$operatorBeforeLoss = 'and';
	// 			// lossAmount < 0
	// 			$lossAmountMathSign = '<';
	// 			$lossAmountValue = 0;
	// 			$operatorBeforeWin = null;
	// 			// ignore winAmount
	// 			$winAmountMathSign = null;
	// 			$winAmountValue = null;
	// 		}

	// 		$formula = $this->_prepareFormulaOfVipUpgradeSetting($betAmountMathSign
	// 			, $betAmountValue
	// 			, $operatorBeforeDeposit
	// 			, $depositAmountMathSign
	// 			, $depositAmountValue
	// 			, $operatorBeforeLoss
	// 			, $lossAmountMathSign
	// 			, $lossAmountValue
	// 			, $operatorBeforeWin
	// 			, $winAmountMathSign
	// 			, $winAmountValue );
	// 		$data['formula'] = $formula;
	// 		$data['bet_amount_settings'] = NULL; // always be NULL

	// 		/// SA
	// 		$data['accumulation'] = 0; // 0 / 1 / 4 : No / Yes, Registration Date / Yes, Last Change Period
	// 		$separate_accumulation_settings_format = '{"bet_amount": {"accumulation": "%d"}, "win_amount": {"accumulation": "%d"}, "loss_amount": {"accumulation": "%d"}, "deposit_amount": {"accumulation": "%d"}}';  // 4 params
	// 		$data['separate_accumulation_settings'] = sprintf($separate_accumulation_settings_format, $accumulation, $accumulation, $accumulation, $accumulation); // '{"bet_amount": {"accumulation": "1"}, "win_amount": {"accumulation": "4"}, "loss_amount": {"accumulation": "0"}, "deposit_amount": {"accumulation": "1"}}';

	// 		$params = [$settingName, $forGrade, $data];
	// 		$getUpgradeLevelSettingFn = function($_this) use ($params){
	// 			return call_user_func_array([$_this->utils4testogp, '_getDowngradeLevelSettingFnV2'], $params); // $rlt = $this->_syncUpgradeLevelSettingByName($settingName, $data);
	// 		};
	// 	}else if( $isCASB ){
	// 		$settingName = 'devDowngradeMet.CASB';
	// 		$forGrade = 'downgrade';
	// 		// $params = [$settingName, $forGrade];
	// 		// ref. to _getDowngradeLevelSettingFn > _syncUpgradeLevelSettingByName
	// 		// related _tryUpgradeSuccessTriggerFromCronjobV2
	// 		$data = [];
	// 		$data['setting_name'] = $settingName;
	// 		$data['description'] = $settingName. '.testing';
	// 		// $data['level_upgrade'] = 3; // for downgrade

	// 		/// SB
	// 		$betAmountMathSign = '>=';
	// 		$betAmountValue = 0;
	// 		$operatorBeforeDeposit = 'and';
	// 		$depositAmountMathSign = '>=';
	// 		$depositAmountValue = 0;
	// 		$operatorBeforeLoss = 'and';
	// 		$lossAmountMathSign = '>=';
	// 		$lossAmountValue = 0;
	// 		$operatorBeforeWin = null;
	// 		$winAmountMathSign = null;
	// 		$winAmountValue = null;
	// 		$formula = $this->_prepareFormulaOfVipUpgradeSetting($betAmountMathSign
	// 			, $betAmountValue
	// 			, $operatorBeforeDeposit
	// 			, $depositAmountMathSign
	// 			, $depositAmountValue
	// 			, $operatorBeforeLoss
	// 			, $lossAmountMathSign
	// 			, $lossAmountValue
	// 			, $operatorBeforeWin
	// 			, $winAmountMathSign
	// 			, $winAmountValue );
	// 		$data['formula'] = $formula;

	// 		if( $isIsDowngradeConditionMet ){
	// 			// betAmount >= 0 ... Is Met
	// 			// game_platform_and_type
	// 			$params = [];
	// 			$params['defaultValue'] = 1;
	// 			$params['defaultMathSign'] = '>=';
	// 			$gameKeyInfoList= [];
	// 			$gameKeyInfoList['type'] = 'game_platform';
	// 			$gameKeyInfoList['value'] = 0;
	// 			$gameKeyInfoList['math_sign'] = '>=';
	// 			$gameKeyInfoList['game_platform_id'] = ''. $theTestPlayerInfo['game_platform_id'];// MUST BE STRING
	// 			$params['GKAMSAVL'][] = $gameKeyInfoList;
	// 			$gameKeyInfoList= [];
	// 			$gameKeyInfoList['type'] = 'game_type';
	// 			$gameKeyInfoList['value'] = 0;
	// 			$gameKeyInfoList['math_sign'] = '>=';
	// 			$gameKeyInfoList['game_type_id'] = ''. $theTestPlayerInfo['game_type_id']; // MUST BE STRING
	// 			$gameKeyInfoList['precon_logic_flag'] = 'and';
	// 			$params['GKAMSAVL'][] = $gameKeyInfoList;
	// 		}else if($isIsDowngradeConditionNotMet){
	// 			// betAmount < 0 ... Not MET
	// 			// game_platform_and_type
	// 			$params = [];
	// 			$params['defaultValue'] = 1;
	// 			$params['defaultMathSign'] = '<';
	// 			$gameKeyInfoList= [];
	// 			$gameKeyInfoList['type'] = 'game_platform';
	// 			$gameKeyInfoList['value'] = 0;
	// 			$gameKeyInfoList['math_sign'] = '<';
	// 			$gameKeyInfoList['game_platform_id'] = ''. $theTestPlayerInfo['game_platform_id'];// MUST BE STRING
	// 			$params['GKAMSAVL'][] = $gameKeyInfoList;
	// 			$gameKeyInfoList= [];
	// 			$gameKeyInfoList['type'] = 'game_type';
	// 			$gameKeyInfoList['value'] = 0;
	// 			$gameKeyInfoList['math_sign'] = '<';
	// 			$gameKeyInfoList['game_type_id'] = ''. $theTestPlayerInfo['game_type_id']; // MUST BE STRING
	// 			$gameKeyInfoList['precon_logic_flag'] = 'and';
	// 			$params['GKAMSAVL'][] = $gameKeyInfoList;
	// 		}

	// 		$defaultValue = $params['defaultValue'];
	// 		$defaultMathSign = $params['defaultMathSign'];
	// 		$gameKeysAndMathSignAndValueList = $params['GKAMSAVL'];
	// 		$theBetAmountSettings = $this->_prepareBetAmountSettingsOfVipUpgradeSetting($defaultValue, $defaultMathSign, $gameKeysAndMathSignAndValueList);
	// 		// $rlt = call_user_func_array([$this, '_prepareBetAmountSettingsOfVipUpgradeSetting'], $testInfo['params']);
	// 		$data['bet_amount_settings'] = $theBetAmountSettings; // always be NULL

	// 		/// CA
	// 		$data['accumulation'] = $accumulation; // 0 / 1 / 4 : No / Yes, Registration Date / Yes, Last Change Period
	// 		$data['separate_accumulation_settings'] = NULL; // always be NULL
	// 		//
	// 		$params = [$settingName, $forGrade, $data];
	// 		$getUpgradeLevelSettingFn = function($_this) use ($params){
	// 			// '_getDowngradeLevelSettingFn'; // so far, CACB
	// 			// $params = [$settingName, $data];
	// 			// $theGenerateCallTrace = $this->utils->generateCallTrace();
	// 			// echo '<pre>';print_r($theGenerateCallTrace);exit();
	// 			return call_user_func_array([$_this->utils4testogp, '_getDowngradeLevelSettingFnV2'], $params); // $rlt = $this->_syncUpgradeLevelSettingByName($settingName, $data);
	// 		};
	// 	}else if( $isSASB ){
	// 		$settingName = 'devDowngradeMet.SASB';
	// 		$forGrade = 'downgrade';
	// 		// $params = [$settingName, $forGrade];
	// 		// ref. to _getDowngradeLevelSettingFn > _syncUpgradeLevelSettingByName
	// 		// related _tryUpgradeSuccessTriggerFromCronjobV2
	// 		$data = [];
	// 		$data['setting_name'] = $settingName;
	// 		$data['description'] = $settingName. '.testing';
	// 		// $data['level_upgrade'] = 3; // for downgrade

	// 		/// SB
	// 		$betAmountMathSign = '>=';
	// 		$betAmountValue = 0;
	// 		$operatorBeforeDeposit = 'and';
	// 		$depositAmountMathSign = '>=';
	// 		$depositAmountValue = 0;
	// 		$operatorBeforeLoss = 'and';
	// 		$lossAmountMathSign = '>=';
	// 		$lossAmountValue = 0;
	// 		$operatorBeforeWin = null;
	// 		$winAmountMathSign = null;
	// 		$winAmountValue = null;
	// 		$formula = $this->_prepareFormulaOfVipUpgradeSetting($betAmountMathSign
	// 			, $betAmountValue
	// 			, $operatorBeforeDeposit
	// 			, $depositAmountMathSign
	// 			, $depositAmountValue
	// 			, $operatorBeforeLoss
	// 			, $lossAmountMathSign
	// 			, $lossAmountValue
	// 			, $operatorBeforeWin
	// 			, $winAmountMathSign
	// 			, $winAmountValue );
	// 		$data['formula'] = $formula;

	// 		if( $isIsDowngradeConditionMet ){
	// 			// betAmount >= 0 ... Is Met
	// 			// game_platform_and_type
	// 			$params = [];
	// 			$params['defaultValue'] = 1;
	// 			$params['defaultMathSign'] = '>=';
	// 			$gameKeyInfoList= [];
	// 			$gameKeyInfoList['type'] = 'game_platform';
	// 			$gameKeyInfoList['value'] = 0;
	// 			$gameKeyInfoList['math_sign'] = '>=';
	// 			$gameKeyInfoList['game_platform_id'] = ''. $theTestPlayerInfo['game_platform_id'];// MUST BE STRING
	// 			$params['GKAMSAVL'][] = $gameKeyInfoList;
	// 			$gameKeyInfoList= [];
	// 			$gameKeyInfoList['type'] = 'game_type';
	// 			$gameKeyInfoList['value'] = 0;
	// 			$gameKeyInfoList['math_sign'] = '>=';
	// 			$gameKeyInfoList['game_type_id'] = ''. $theTestPlayerInfo['game_type_id']; // MUST BE STRING
	// 			$gameKeyInfoList['precon_logic_flag'] = 'and';
	// 			$params['GKAMSAVL'][] = $gameKeyInfoList;
	// 		}else if($isIsDowngradeConditionNotMet){
	// 			// betAmount < 0 ... Not MET
	// 			// game_platform_and_type
	// 			$params = [];
	// 			$params['defaultValue'] = 1;
	// 			$params['defaultMathSign'] = '<';
	// 			$gameKeyInfoList= [];
	// 			$gameKeyInfoList['type'] = 'game_platform';
	// 			$gameKeyInfoList['value'] = 0;
	// 			$gameKeyInfoList['math_sign'] = '<';
	// 			$gameKeyInfoList['game_platform_id'] = ''. $theTestPlayerInfo['game_platform_id'];// MUST BE STRING
	// 			$params['GKAMSAVL'][] = $gameKeyInfoList;
	// 			$gameKeyInfoList= [];
	// 			$gameKeyInfoList['type'] = 'game_type';
	// 			$gameKeyInfoList['value'] = 0;
	// 			$gameKeyInfoList['math_sign'] = '<';
	// 			$gameKeyInfoList['game_type_id'] = ''. $theTestPlayerInfo['game_type_id']; // MUST BE STRING
	// 			$gameKeyInfoList['precon_logic_flag'] = 'and';
	// 			$params['GKAMSAVL'][] = $gameKeyInfoList;
	// 		}

	// 		$defaultValue = $params['defaultValue'];
	// 		$defaultMathSign = $params['defaultMathSign'];
	// 		$gameKeysAndMathSignAndValueList = $params['GKAMSAVL'];
	// 		$theBetAmountSettings = $this->_prepareBetAmountSettingsOfVipUpgradeSetting($defaultValue, $defaultMathSign, $gameKeysAndMathSignAndValueList);
	// 		// $rlt = call_user_func_array([$this, '_prepareBetAmountSettingsOfVipUpgradeSetting'], $testInfo['params']);
	// 		$data['bet_amount_settings'] = $theBetAmountSettings; // always be NULL

	// 		/// SA
	// 		$data['accumulation'] = 0; // 0 / 1 / 4 : No / Yes, Registration Date / Yes, Last Change Period
	// 		$separate_accumulation_settings_format = '{"bet_amount": {"accumulation": "%d"}, "win_amount": {"accumulation": "%d"}, "loss_amount": {"accumulation": "%d"}, "deposit_amount": {"accumulation": "%d"}}';  // 4 params
	// 		$data['separate_accumulation_settings'] = sprintf($separate_accumulation_settings_format, $accumulation, $accumulation, $accumulation, $accumulation); // '{"bet_amount": {"accumulation": "1"}, "win_amount": {"accumulation": "4"}, "loss_amount": {"accumulation": "0"}, "deposit_amount": {"accumulation": "1"}}';

	// 		$params = [$settingName, $forGrade, $data];
	// 		$getUpgradeLevelSettingFn = function($_this) use ($params){
	// 			// '_getDowngradeLevelSettingFn'; // so far, CACB
	// 			// $params = [$settingName, $data];
	// 			// $theGenerateCallTrace = $this->utils->generateCallTrace();
	// 			// echo '<pre>';print_r($theGenerateCallTrace);exit();
	// 			return call_user_func_array([$_this->utils4testogp, '_getDowngradeLevelSettingFnV2'], $params); // $rlt = $this->_syncUpgradeLevelSettingByName($settingName, $data);
	// 		};
	// 	}

	// 	return [$settingName, $getUpgradeLevelSettingFn];
	// } // EOF DEL_getUpgradeLevelSettingFnAndSettingNameFromCombinedCaseAndTestPlayerInfo
	/**
	 * To Get $settingName, $getUpgradeLevelSettingFn for test case setups
	 *
	 * @param rtring $theCombinedCase
	 * @return array [$settingName, $getUpgradeLevelSettingFn]
	 */
	// public function DEL_getUpgradeLevelSettingFnAndSettingNameFromCombinedCase($theCombinedCase){

	// 	// will return [$settingName, $getUpgradeLevelSettingFn]

	// 	$isCACB = $this->utils4testogp->_pos($theCombinedCase, 'CACB');
	// 	$isSACB = $this->utils4testogp->_pos($theCombinedCase, 'SACB');
	// 	$isCASB = $this->utils4testogp->_pos($theCombinedCase, 'CASB');
	// 	$isSASB = $this->utils4testogp->_pos($theCombinedCase, 'SASB');

	// 	$isNoAccumulation = $this->utils4testogp->_pos($theCombinedCase, 'NoAccumulation');
	// 	$isAccumulationYesRegistrationDate = $this->utils4testogp->_pos($theCombinedCase, 'AccumulationYesRegistrationDate');
	// 	$isAccumulationYesLastChangePeriod = $this->utils4testogp->_pos($theCombinedCase, 'AccumulationYesLastChangePeriod');
	// 	$isAccumulationLastChangePeriodResetIfMet = $this->utils4testogp->_pos($theCombinedCase, 'AccumulationLastChangePeriodResetIfMet');
	// 	// $isAccumulationYesLastChangePeriodResetAlways = $this->utils4testogp->_pos($theCombinedCase, 'AccumulationYesLastChangePeriodResetAlways');

	// 	/// for $getUpgradeLevelSettingFn
	// 	if($isNoAccumulation){
	// 		$accumulation = 0; // Group_level::ACCUMULATION_MODE_DISABLE
	// 	}else if($isAccumulationYesRegistrationDate){
	// 		$accumulation = 1; // Group_level::ACCUMULATION_MODE_FROM_REGISTRATION
	// 	}else if($isAccumulationYesLastChangePeriod){
	// 		$accumulation = 4; // Group_level::ACCUMULATION_MODE_LAST_CHANGED_GEADE
	// 	}else if($isAccumulationLastChangePeriodResetIfMet){
	// 		$accumulation = 5; // Group_level::ACCUMULATION_MODE_LAST_CHANGED_GEADE_RESET_IF_MET
	// 	// }else if($isAccumulationYesLastChangePeriodResetAlways){
	// 	// 	$accumulation = 6; // Group_level::ACCUMULATION_MODE_LAST_CHANGED_GEADE_RESET_ALWAYS
	// 	}
	// 	/// handle DowngradeLevelSetting
	// 	$_this = $this;
	// 	if( $isCACB ){
	// 		$settingName = 'devDowngradeMet.CACB';
	// 		$forGrade = 'downgrade';
	// 		// $params = [$settingName, $forGrade];
	// 		// ref. to _getDowngradeLevelSettingFn > _syncUpgradeLevelSettingByName
	// 		// related _tryUpgradeSuccessTriggerFromCronjobV2
	// 		$data = [];
	// 		$data['setting_name'] = $settingName;
	// 		$data['description'] = $settingName. '.testing';
	// 		// $data['level_upgrade'] = 3; // for downgrade
	// 		/// CB
	// 		$betAmountMathSign = '>=';
	// 		$betAmountValue = 0;
	// 		$operatorBeforeDeposit = 'and';
	// 		$depositAmountMathSign = '>=';
	// 		$depositAmountValue = 0;
	// 		$operatorBeforeLoss = 'and';
	// 		$lossAmountMathSign = '>=';
	// 		$lossAmountValue = 0;
	// 		$operatorBeforeWin = null;
	// 		$winAmountMathSign = null;
	// 		$winAmountValue = null;
	// 		$formula = $this->_prepareFormulaOfVipUpgradeSetting($betAmountMathSign
	// 			, $betAmountValue
	// 			, $operatorBeforeDeposit
	// 			, $depositAmountMathSign
	// 			, $depositAmountValue
	// 			, $operatorBeforeLoss
	// 			, $lossAmountMathSign
	// 			, $lossAmountValue
	// 			, $operatorBeforeWin
	// 			, $winAmountMathSign
	// 			, $winAmountValue );
	// 		$data['formula'] = $formula;
	// 		$data['bet_amount_settings'] = NULL; // always be NULL
	// 		/// CA
	// 		$data['accumulation'] = $accumulation; // 0 / 1 / 4 : No / Yes, Registration Date / Yes, Last Change Period
	// 		$data['separate_accumulation_settings'] = NULL; // always be NULL
	// 		//
	// 		$params = [$settingName, $forGrade, $data];
	// 		$getUpgradeLevelSettingFn = function($_this) use ($params){
	// 			// '_getDowngradeLevelSettingFn'; // so far, CACB
	// 			// $params = [$settingName, $data];
	// 			// $theGenerateCallTrace = $this->utils->generateCallTrace();
	// 			// echo '<pre>';print_r($theGenerateCallTrace);exit();
	// 			return call_user_func_array([$_this->utils4testogp, '_getDowngradeLevelSettingFnV2'], $params); // $rlt = $this->_syncUpgradeLevelSettingByName($settingName, $data);
	// 		};
	// 	}else if( $isSACB ){
	// 		$settingName = 'devDowngradeMet.SACB';
	// 		$forGrade = 'downgrade';
	// 		// $params = [$settingName, $forGrade];
	// 		// ref. to _getDowngradeLevelSettingFn > _syncUpgradeLevelSettingByName
	// 		// related _tryUpgradeSuccessTriggerFromCronjobV2
	// 		$data = [];
	// 		$data['setting_name'] = $settingName;
	// 		$data['description'] = $settingName. '.testing';
	// 		// $data['level_upgrade'] = 3; // for downgrade
	// 		/// CB
	// 		$betAmountMathSign = '>=';
	// 		$betAmountValue = 0;
	// 		$operatorBeforeDeposit = 'and';
	// 		$depositAmountMathSign = '>=';
	// 		$depositAmountValue = 0;
	// 		$operatorBeforeLoss = 'and';
	// 		$lossAmountMathSign = '>=';
	// 		$lossAmountValue = 0;
	// 		$operatorBeforeWin = null;
	// 		$winAmountMathSign = null;
	// 		$winAmountValue = null;
	// 		$formula = $this->_prepareFormulaOfVipUpgradeSetting($betAmountMathSign
	// 			, $betAmountValue
	// 			, $operatorBeforeDeposit
	// 			, $depositAmountMathSign
	// 			, $depositAmountValue
	// 			, $operatorBeforeLoss
	// 			, $lossAmountMathSign
	// 			, $lossAmountValue
	// 			, $operatorBeforeWin
	// 			, $winAmountMathSign
	// 			, $winAmountValue );
	// 		$data['formula'] = $formula;
	// 		$data['bet_amount_settings'] = NULL; // always be NULL

	// 		/// SA
	// 		$data['accumulation'] = 0; // 0 / 1 / 4 : No / Yes, Registration Date / Yes, Last Change Period
	// 		$separate_accumulation_settings_format = '{"bet_amount": {"accumulation": "%d"}, "win_amount": {"accumulation": "%d"}, "loss_amount": {"accumulation": "%d"}, "deposit_amount": {"accumulation": "%d"}}';  // 4 params
	// 		$data['separate_accumulation_settings'] = sprintf($separate_accumulation_settings_format, $accumulation, $accumulation, $accumulation, $accumulation); // '{"bet_amount": {"accumulation": "1"}, "win_amount": {"accumulation": "4"}, "loss_amount": {"accumulation": "0"}, "deposit_amount": {"accumulation": "1"}}';

	// 		$params = [$settingName, $forGrade, $data];
	// 		$getUpgradeLevelSettingFn = function($_this) use ($params){
	// 			return call_user_func_array([$_this->utils4testogp, '_getDowngradeLevelSettingFnV2'], $params); // $rlt = $this->_syncUpgradeLevelSettingByName($settingName, $data);
	// 		};
	// 	}else if( $isCASB ){
	// 		$settingName = 'devDowngradeMet.CASB';
	// 		$forGrade = 'downgrade';
	// 		// $params = [$settingName, $forGrade];
	// 		// ref. to _getDowngradeLevelSettingFn > _syncUpgradeLevelSettingByName
	// 		// related _tryUpgradeSuccessTriggerFromCronjobV2
	// 		$data = [];
	// 		$data['setting_name'] = $settingName;
	// 		$data['description'] = $settingName. '.testing';
	// 		// $data['level_upgrade'] = 3; // for downgrade

	// 		/// SB
	// 		$betAmountMathSign = '<=';
	// 		$betAmountValue = 0;
	// 		$operatorBeforeDeposit = 'and';
	// 		$depositAmountMathSign = '<=';
	// 		$depositAmountValue = 8;
	// 		$operatorBeforeLoss = 'and';
	// 		$lossAmountMathSign = '<=';
	// 		$lossAmountValue = 9;
	// 		$operatorBeforeWin = null;
	// 		$winAmountMathSign = null;
	// 		$winAmountValue = null;
	// 		$formula = $this->_prepareFormulaOfVipUpgradeSetting($betAmountMathSign
	// 			, $betAmountValue
	// 			, $operatorBeforeDeposit
	// 			, $depositAmountMathSign
	// 			, $depositAmountValue
	// 			, $operatorBeforeLoss
	// 			, $lossAmountMathSign
	// 			, $lossAmountValue
	// 			, $operatorBeforeWin
	// 			, $winAmountMathSign
	// 			, $winAmountValue );
	// 		$data['formula'] = $formula;

	// 		// game_platform_and_type
	// 		$params = [];
	// 		$params['defaultValue'] = 20;
	// 		$params['defaultMathSign'] = '>=';
	// 		$gameKeyInfoList= [];
	// 		$gameKeyInfoList['type'] = 'game_platform';
	// 		$gameKeyInfoList['value'] = 28;
	// 		$gameKeyInfoList['math_sign'] = '<=';
	// 		$gameKeyInfoList['game_platform_id'] = '5674';// MUST BE STRING
	// 		$params['GKAMSAVL'][] = $gameKeyInfoList;
	// 		$gameKeyInfoList= [];
	// 		$gameKeyInfoList['type'] = 'game_type';
	// 		$gameKeyInfoList['value'] = 29;
	// 		$gameKeyInfoList['math_sign'] = '<=';
	// 		$gameKeyInfoList['game_type_id'] = '561'; // MUST BE STRING
	// 		$gameKeyInfoList['precon_logic_flag'] = 'or';
	// 		$params['GKAMSAVL'][] = $gameKeyInfoList;

	// 		$defaultValue = $params['defaultValue'];
	// 		$defaultMathSign = $params['defaultMathSign'];
	// 		$gameKeysAndMathSignAndValueList = $params['GKAMSAVL'];
	// 		$theBetAmountSettings = $this->_prepareBetAmountSettingsOfVipUpgradeSetting($defaultValue, $defaultMathSign, $gameKeysAndMathSignAndValueList);
	// 		// $rlt = call_user_func_array([$this, '_prepareBetAmountSettingsOfVipUpgradeSetting'], $testInfo['params']);
	// 		$data['bet_amount_settings'] = $theBetAmountSettings; // always be NULL

	// 		/// CA
	// 		$data['accumulation'] = $accumulation; // 0 / 1 / 4 : No / Yes, Registration Date / Yes, Last Change Period
	// 		$data['separate_accumulation_settings'] = NULL; // always be NULL
	// 		//
	// 		$params = [$settingName, $forGrade, $data];
	// 		$getUpgradeLevelSettingFn = function($_this) use ($params){
	// 			// '_getDowngradeLevelSettingFn'; // so far, CACB
	// 			// $params = [$settingName, $data];
	// 			// $theGenerateCallTrace = $this->utils->generateCallTrace();
	// 			// echo '<pre>';print_r($theGenerateCallTrace);exit();
	// 			return call_user_func_array([$_this->utils4testogp, '_getDowngradeLevelSettingFnV2'], $params); // $rlt = $this->_syncUpgradeLevelSettingByName($settingName, $data);
	// 		};
	// 	}else if( $isSASB ){
	// 		$settingName = 'devDowngradeMet.SASB';
	// 		$forGrade = 'downgrade';
	// 		// $params = [$settingName, $forGrade];
	// 		// ref. to _getDowngradeLevelSettingFn > _syncUpgradeLevelSettingByName
	// 		// related _tryUpgradeSuccessTriggerFromCronjobV2
	// 		$data = [];
	// 		$data['setting_name'] = $settingName;
	// 		$data['description'] = $settingName. '.testing';
	// 		// $data['level_upgrade'] = 3; // for downgrade

	// 		/// SB
	// 		$betAmountMathSign = '<=';
	// 		$betAmountValue = 0;
	// 		$operatorBeforeDeposit = 'and';
	// 		$depositAmountMathSign = '<=';
	// 		$depositAmountValue = 8;
	// 		$operatorBeforeLoss = 'and';
	// 		$lossAmountMathSign = '<=';
	// 		$lossAmountValue = 9;
	// 		$operatorBeforeWin = null;
	// 		$winAmountMathSign = null;
	// 		$winAmountValue = null;
	// 		$formula = $this->_prepareFormulaOfVipUpgradeSetting($betAmountMathSign
	// 			, $betAmountValue
	// 			, $operatorBeforeDeposit
	// 			, $depositAmountMathSign
	// 			, $depositAmountValue
	// 			, $operatorBeforeLoss
	// 			, $lossAmountMathSign
	// 			, $lossAmountValue
	// 			, $operatorBeforeWin
	// 			, $winAmountMathSign
	// 			, $winAmountValue );
	// 		$data['formula'] = $formula;

	// 		// game_platform_and_type
	// 		$params = [];
	// 		$params['defaultValue'] = 20;
	// 		$params['defaultMathSign'] = '>=';
	// 		$gameKeyInfoList= [];
	// 		$gameKeyInfoList['type'] = 'game_platform';
	// 		$gameKeyInfoList['value'] = 28;
	// 		$gameKeyInfoList['math_sign'] = '<=';
	// 		$gameKeyInfoList['game_platform_id'] = '5674';// MUST BE STRING
	// 		$params['GKAMSAVL'][] = $gameKeyInfoList;
	// 		$gameKeyInfoList= [];
	// 		$gameKeyInfoList['type'] = 'game_type';
	// 		$gameKeyInfoList['value'] = 29;
	// 		$gameKeyInfoList['math_sign'] = '<=';
	// 		$gameKeyInfoList['game_type_id'] = '561'; // MUST BE STRING
	// 		$gameKeyInfoList['precon_logic_flag'] = 'or';
	// 		$params['GKAMSAVL'][] = $gameKeyInfoList;

	// 		$defaultValue = $params['defaultValue'];
	// 		$defaultMathSign = $params['defaultMathSign'];
	// 		$gameKeysAndMathSignAndValueList = $params['GKAMSAVL'];
	// 		$theBetAmountSettings = $this->_prepareBetAmountSettingsOfVipUpgradeSetting($defaultValue, $defaultMathSign, $gameKeysAndMathSignAndValueList);
	// 		// $rlt = call_user_func_array([$this, '_prepareBetAmountSettingsOfVipUpgradeSetting'], $testInfo['params']);
	// 		$data['bet_amount_settings'] = $theBetAmountSettings; // always be NULL

	// 		/// SA
	// 		$data['accumulation'] = 0; // 0 / 1 / 4 : No / Yes, Registration Date / Yes, Last Change Period
	// 		$separate_accumulation_settings_format = '{"bet_amount": {"accumulation": "%d"}, "win_amount": {"accumulation": "%d"}, "loss_amount": {"accumulation": "%d"}, "deposit_amount": {"accumulation": "%d"}}';  // 4 params
	// 		$data['separate_accumulation_settings'] = sprintf($separate_accumulation_settings_format, $accumulation, $accumulation, $accumulation, $accumulation); // '{"bet_amount": {"accumulation": "1"}, "win_amount": {"accumulation": "4"}, "loss_amount": {"accumulation": "0"}, "deposit_amount": {"accumulation": "1"}}';

	// 		$params = [$settingName, $forGrade, $data];
	// 		$getUpgradeLevelSettingFn = function($_this) use ($params){
	// 			// '_getDowngradeLevelSettingFn'; // so far, CACB
	// 			// $params = [$settingName, $data];
	// 			// $theGenerateCallTrace = $this->utils->generateCallTrace();
	// 			// echo '<pre>';print_r($theGenerateCallTrace);exit();
	// 			return call_user_func_array([$_this->utils4testogp, '_getDowngradeLevelSettingFnV2'], $params); // $rlt = $this->_syncUpgradeLevelSettingByName($settingName, $data);
	// 		};
	// 	}

	// 	return [$settingName, $getUpgradeLevelSettingFn];
	// }// EOF DEL_getUpgradeLevelSettingFnAndSettingNameFromCombinedCase
	/// Cloned from admin/application/controllers/cli/testing_ogp21051.php
	//
	/**
	 * Get SettingName after generated the Upgrade Level Setting function/script.
	 *
	 * @param string $theCombinedCase
	 * @param string $theSettingNamePrefix
	 * @param string $forGrade For upgrade OR downgrade
	 * @return void
	 */
	public function _getUpgradeLevelSettingFnAndSettingNameFromCombinedCase($theCombinedCase, $theSettingNamePrefix = 'devDowngradeMet', $forGrade = 'downgrade'){
		$testing_ogp = $this; // for test()
		return $this->utils4testogp->_getUpgradeLevelSettingFnAndSettingNameFromCombinedCaseV2( $theCombinedCase
																								, $theSettingNamePrefix
																								, $forGrade
																								, [] // $theTestPlayerInfo
																								, $testing_ogp
																							);
		/// moved to utils4testogp::_getUpgradeLevelSettingFnAndSettingNameFromCombinedCaseV2()

// 		// will return [$settingName, $getUpgradeLevelSettingFn]
//
// 		$isCACB = $this->utils4testogp->_pos($theCombinedCase, 'CACB');
// 		$isSACB = $this->utils4testogp->_pos($theCombinedCase, 'SACB');
// 		$isCASB = $this->utils4testogp->_pos($theCombinedCase, 'CASB');
// 		$isSASB = $this->utils4testogp->_pos($theCombinedCase, 'SASB');
//
// 		$isNoAccumulation = $this->utils4testogp->_pos($theCombinedCase, 'NoAccumulation');
// 		$isAccumulationYesRegistrationDate = $this->utils4testogp->_pos($theCombinedCase, 'AccumulationYesRegistrationDate');
// 		$isAccumulationYesLastChangePeriod = $this->utils4testogp->_pos($theCombinedCase, 'AccumulationYesLastChangePeriod');
// 		$isAccumulationLastChangePeriodResetIfMet = $this->utils4testogp->_pos($theCombinedCase, 'AccumulationLastChangePeriodResetIfMet');
//
// 		/// for $getUpgradeLevelSettingFn
// 		$accumulation = 1; // default, isAccumulationYesRegistrationDate
// 		if($isNoAccumulation){
// 			$accumulation = 0; // Group_level::ACCUMULATION_MODE_DISABLE
// 		}else if($isAccumulationYesRegistrationDate){
// 			$accumulation = 1; // Group_level::ACCUMULATION_MODE_FROM_REGISTRATION
// 		}else if($isAccumulationYesLastChangePeriod){
// 			$accumulation = 4; // Group_level::ACCUMULATION_MODE_LAST_CHANGED_GEADE
// 		}else if($isAccumulationLastChangePeriodResetIfMet){
// 			$accumulation = 5; // Group_level::ACCUMULATION_MODE_LAST_CHANGED_GEADE_RESET_IF_MET
// 		}
//
//
// 		$isAllNoMetInFormula = $this->utils4testogp->_pos($theCombinedCase, 'AllNoMetInFormula');
// 		$isOnlyMetBetOfAllInFormula = $this->utils4testogp->_pos($theCombinedCase, 'OnlyMetBetOfAllInFormula');
// 		$isOnlyMetDepositOfAllInFormula = $this->utils4testogp->_pos($theCombinedCase, 'OnlyMetDepositOfAllInFormula');
// 		$isOnlyMetBetDepositOfAllInFormula = $this->utils4testogp->_pos($theCombinedCase, 'OnlyMetBetDepositOfAllInFormula');
// 		$isOnlyMetBetDepositWinOfAllInFormula = $this->utils4testogp->_pos($theCombinedCase, 'OnlyMetBetDepositWinOfAllInFormula');
// 		$isAllMetInFormula = $this->utils4testogp->_pos($theCombinedCase, 'AllMetInFormula');
// 		$isOnlyMetBetOfBetInFormula = $this->utils4testogp->_pos($theCombinedCase, 'OnlyMetBetOfBetInFormula');
// 		$isOnlyMetBetOfBetDepositInFormula = $this->utils4testogp->_pos($theCombinedCase, 'OnlyMetBetOfBetDepositInFormula');
//
// 		$isEmptyUpgradeSetting = $this->utils4testogp->_pos($theCombinedCase, 'EmptyUpgradeSetting');
// 		$isDepositUpgradeSetting = $this->utils4testogp->_pos($theCombinedCase, 'DepositUpgradeSetting');
// 		$isBetUpgradeSetting = $this->utils4testogp->_pos($theCombinedCase, 'BetUpgradeSetting');
// 		$isDepositBetUpgradeSetting = $this->utils4testogp->_pos($theCombinedCase, 'DepositBetUpgradeSetting');
//
// 		/// disable for handle by the function, getUpgradeLevelSettingFnWithParams().
// 		// /// handle DowngradeLevelSetting/UpgradeLevelSetting
// 		// $_this = $this;
// 		// if( $isCACB ){
// 		// 	$settingName = $theSettingNamePrefix.'.CACB'; // #1
// 		// 	$betAmountMathSign = '>='; // #2
// 		// 	$betAmountValue = 0; // #3
// 		// 	$operatorBeforeDeposit = 'and'; // #4
// 		// 	$depositAmountMathSign = '>='; // #5
// 		// 	$depositAmountValue = 0; // #6
// 		// 	$operatorBeforeLoss = 'and'; // #7
// 		// 	$lossAmountMathSign = '>='; // #8
// 		// 	$lossAmountValue = 0; // #9
// 		// 	$operatorBeforeWin = null; // #10
// 		// 	$winAmountMathSign = null; // #11
// 		// 	$winAmountValue = null; // #12
// 		// 	$accumulation = $accumulation; // #13, from params
// 		// 	$separate_accumulation_settings = null; // #14
// 		// 	$forGrade = $forGrade; // #15, from params
// 		// 	$getUpgradeLevelSettingFn = $this->getUpgradeLevelSettingFnWithParams(	$settingName // #1
// 		// 												, $betAmountMathSign  // #2
// 		// 												, $betAmountValue // #3
// 		// 												, $operatorBeforeDeposit // #4
// 		// 												, $depositAmountMathSign // #5
// 		// 												, $depositAmountValue // #6
// 		// 												, $operatorBeforeLoss // #7
// 		// 												, $lossAmountMathSign // #8
// 		// 												, $lossAmountValue // #9
// 		// 												, $operatorBeforeWin // #10
// 		// 												, $winAmountMathSign // #11
// 		// 												, $winAmountValue // #12
// 		// 												, $accumulation // #13
// 		// 												, $separate_accumulation_settings // #14
// 		// 												, $forGrade // #15
// 		// 											);
// 		// 	// $settingName = $theSettingNamePrefix.'.CACB';
// 		// 	// // $forGrade = 'downgrade';
// 		// 	// // $params = [$settingName, $forGrade];
// 		// 	// // ref. to _getDowngradeLevelSettingFn > _syncUpgradeLevelSettingByName
// 		// 	// // related _tryUpgradeSuccessTriggerFromCronjobV2
// 		// 	// $data = [];
// 		// 	// $data['setting_name'] = $settingName;
// 		// 	// $data['description'] = $settingName. '.testing';
// 		// 	// // $data['level_upgrade'] = 3; // for downgrade
// 		// 	// /// CB
// 		// 	// $betAmountMathSign = '>=';
// 		// 	// $betAmountValue = 0;
// 		// 	// $operatorBeforeDeposit = 'and';
// 		// 	// $depositAmountMathSign = '>=';
// 		// 	// $depositAmountValue = 0;
// 		// 	// $operatorBeforeLoss = 'and';
// 		// 	// $lossAmountMathSign = '>=';
// 		// 	// $lossAmountValue = 0;
// 		// 	// $operatorBeforeWin = null;
// 		// 	// $winAmountMathSign = null;
// 		// 	// $winAmountValue = null;
// 		// 	// $formula = $this->utils4testogp->_prepareFormulaOfVipUpgradeSetting($betAmountMathSign
// 		// 	// 	, $betAmountValue
// 		// 	// 	, $operatorBeforeDeposit
// 		// 	// 	, $depositAmountMathSign
// 		// 	// 	, $depositAmountValue
// 		// 	// 	, $operatorBeforeLoss
// 		// 	// 	, $lossAmountMathSign
// 		// 	// 	, $lossAmountValue
// 		// 	// 	, $operatorBeforeWin
// 		// 	// 	, $winAmountMathSign
// 		// 	// 	, $winAmountValue );
// 		// 	// $data['formula'] = $formula;
// 		// 	// $data['bet_amount_settings'] = NULL; // always be NULL
// 		// 	// /// CA
// 		// 	// $data['accumulation'] = $accumulation; // 0 / 1 / 4 : No / Yes, Registration Date / Yes, Last Change Period
// 		// 	// $data['separate_accumulation_settings'] = NULL; // always be NULL
// 		// 	// //
// 		// 	// $params = [$settingName, $forGrade, $data];
// 		// 	// $getUpgradeLevelSettingFn = function($_this) use ($params){
// 		// 	// 	// '_getDowngradeLevelSettingFn'; // so far, CACB
// 		// 	// 	// $params = [$settingName, $data];
// 		// 	// 	// $theGenerateCallTrace = $this->utils->generateCallTrace();
// 		// 	// 	// echo '<pre>';print_r($theGenerateCallTrace);exit();
// 		// 	// 	return call_user_func_array([$_this->utils4testogp, '_getDowngradeLevelSettingFnV2'], $params); // $rlt = $this->_syncUpgradeLevelSettingByName($settingName, $data);
// 		// 	// };
// 		// }else if( $isSACB ){
// 		// 	$settingName = $theSettingNamePrefix.'.SACB';
// 		// 	// $forGrade = 'downgrade';
// 		// 	// $params = [$settingName, $forGrade];
// 		// 	// ref. to _getDowngradeLevelSettingFn > _syncUpgradeLevelSettingByName
// 		// 	// related _tryUpgradeSuccessTriggerFromCronjobV2
// 		// 	$data = [];
// 		// 	$data['setting_name'] = $settingName;
// 		// 	$data['description'] = $settingName. '.testing';
// 		// 	// $data['level_upgrade'] = 3; // for downgrade
// 		// 	/// CB
// 		// 	$betAmountMathSign = '>=';
// 		// 	$betAmountValue = 0;
// 		// 	$operatorBeforeDeposit = 'and';
// 		// 	$depositAmountMathSign = '>=';
// 		// 	$depositAmountValue = 0;
// 		// 	$operatorBeforeLoss = 'and';
// 		// 	$lossAmountMathSign = '>=';
// 		// 	$lossAmountValue = 0;
// 		// 	$operatorBeforeWin = null;
// 		// 	$winAmountMathSign = null;
// 		// 	$winAmountValue = null;
// 		// 	$formula = $this->_prepareFormulaOfVipUpgradeSetting($betAmountMathSign
// 		// 		, $betAmountValue
// 		// 		, $operatorBeforeDeposit
// 		// 		, $depositAmountMathSign
// 		// 		, $depositAmountValue
// 		// 		, $operatorBeforeLoss
// 		// 		, $lossAmountMathSign
// 		// 		, $lossAmountValue
// 		// 		, $operatorBeforeWin
// 		// 		, $winAmountMathSign
// 		// 		, $winAmountValue );
// 		// 	$data['formula'] = $formula;
// 		// 	$data['bet_amount_settings'] = NULL; // always be NULL
// 		//
// 		// 	/// SA
// 		// 	$data['accumulation'] = 0; // 0 / 1 / 4 : No / Yes, Registration Date / Yes, Last Change Period
// 		// 	$data['separate_accumulation_settings'] = '{"bet_amount": {"accumulation": "1"}, "win_amount": {"accumulation": "4"}, "loss_amount": {"accumulation": "0"}, "deposit_amount": {"accumulation": "1"}}'; // always be NULL
// 		//
// 		// 	$params = [$settingName, $forGrade, $data];
// 		// 	$getUpgradeLevelSettingFn = function($_this) use ($params){
// 		// 		return call_user_func_array([$_this->utils4testogp, '_getDowngradeLevelSettingFnV2'], $params); // $rlt = $this->_syncUpgradeLevelSettingByName($settingName, $data);
// 		// 	};
// 		// }else if( $isCASB ){
// 		// 	$settingName = $theSettingNamePrefix.'.CASB';
// 		// 	// $forGrade = 'downgrade';
// 		// 	// $params = [$settingName, $forGrade];
// 		// 	// ref. to _getDowngradeLevelSettingFn > _syncUpgradeLevelSettingByName
// 		// 	// related _tryUpgradeSuccessTriggerFromCronjobV2
// 		// 	$data = [];
// 		// 	$data['setting_name'] = $settingName;
// 		// 	$data['description'] = $settingName. '.testing';
// 		// 	// $data['level_upgrade'] = 3; // for downgrade
// 		//
// 		// 	/// SB
// 		// 	$betAmountMathSign = '<=';
// 		// 	$betAmountValue = 0;
// 		// 	$operatorBeforeDeposit = 'or';
// 		// 	$depositAmountMathSign = '<=';
// 		// 	$depositAmountValue = 8;
// 		// 	$operatorBeforeLoss = 'or';
// 		// 	$lossAmountMathSign = '<=';
// 		// 	$lossAmountValue = 9;
// 		// 	$operatorBeforeWin = null;
// 		// 	$winAmountMathSign = null;
// 		// 	$winAmountValue = null;
// 		// 	$formula = $this->_prepareFormulaOfVipUpgradeSetting($betAmountMathSign
// 		// 		, $betAmountValue
// 		// 		, $operatorBeforeDeposit
// 		// 		, $depositAmountMathSign
// 		// 		, $depositAmountValue
// 		// 		, $operatorBeforeLoss
// 		// 		, $lossAmountMathSign
// 		// 		, $lossAmountValue
// 		// 		, $operatorBeforeWin
// 		// 		, $winAmountMathSign
// 		// 		, $winAmountValue );
// 		// 	$data['formula'] = $formula;
// 		//
// 		// 	// game_platform_and_type
// 		// 	$params = [];
// 		// 	$params['defaultValue'] = 20;
// 		// 	$params['defaultMathSign'] = '>=';
// 		// 	$gameKeyInfoList= [];
// 		// 	$gameKeyInfoList['type'] = 'game_platform';
// 		// 	$gameKeyInfoList['value'] = 28;
// 		// 	$gameKeyInfoList['math_sign'] = '<=';
// 		// 	$gameKeyInfoList['game_platform_id'] = '5674';// MUST BE STRING
// 		// 	$params['GKAMSAVL'][] = $gameKeyInfoList;
// 		// 	$gameKeyInfoList= [];
// 		// 	$gameKeyInfoList['type'] = 'game_type';
// 		// 	$gameKeyInfoList['value'] = 29;
// 		// 	$gameKeyInfoList['math_sign'] = '<=';
// 		// 	$gameKeyInfoList['game_type_id'] = '561'; // MUST BE STRING
// 		// 	$gameKeyInfoList['precon_logic_flag'] = 'or';
// 		// 	$params['GKAMSAVL'][] = $gameKeyInfoList;
// 		//
// 		// 	$defaultValue = $params['defaultValue'];
// 		// 	$defaultMathSign = $params['defaultMathSign'];
// 		// 	$gameKeysAndMathSignAndValueList = $params['GKAMSAVL'];
// 		// 	$theBetAmountSettings = $this->_prepareBetAmountSettingsOfVipUpgradeSetting($defaultValue, $defaultMathSign, $gameKeysAndMathSignAndValueList);
// 		// 	// $rlt = call_user_func_array([$this, '_prepareBetAmountSettingsOfVipUpgradeSetting'], $testInfo['params']);
// 		// 	$data['bet_amount_settings'] = $theBetAmountSettings; // always be NULL
// 		//
// 		// 	/// CA
// 		// 	$data['accumulation'] = $accumulation; // 0 / 1 / 4 : No / Yes, Registration Date / Yes, Last Change Period
// 		// 	$data['separate_accumulation_settings'] = NULL; // always be NULL
// 		// 	//
// 		// 	$params = [$settingName, $forGrade, $data];
// 		// 	$getUpgradeLevelSettingFn = function($_this) use ($params){
// 		// 		// '_getDowngradeLevelSettingFn'; // so far, CACB
// 		// 		// $params = [$settingName, $data];
// 		// 		// $theGenerateCallTrace = $this->utils->generateCallTrace();
// 		// 		// echo '<pre>';print_r($theGenerateCallTrace);exit();
// 		// 		return call_user_func_array([$_this->utils4testogp, '_getDowngradeLevelSettingFnV2'], $params); // $rlt = $this->_syncUpgradeLevelSettingByName($settingName, $data);
// 		// 	};
// 		// }else if( $isSASB ){
// 		// 	$settingName = $theSettingNamePrefix.'.SASB';
// 		// 	// $forGrade = 'downgrade';
// 		// 	// $params = [$settingName, $forGrade];
// 		// 	// ref. to _getDowngradeLevelSettingFn > _syncUpgradeLevelSettingByName
// 		// 	// related _tryUpgradeSuccessTriggerFromCronjobV2
// 		// 	$data = [];
// 		// 	$data['setting_name'] = $settingName;
// 		// 	$data['description'] = $settingName. '.testing';
// 		// 	// $data['level_upgrade'] = 3; // for downgrade
// 		//
// 		// 	/// SB
// 		// 	$betAmountMathSign = '<=';
// 		// 	$betAmountValue = 0;
// 		// 	$operatorBeforeDeposit = 'or';
// 		// 	$depositAmountMathSign = '<=';
// 		// 	$depositAmountValue = 8;
// 		// 	$operatorBeforeLoss = 'or';
// 		// 	$lossAmountMathSign = '<=';
// 		// 	$lossAmountValue = 9;
// 		// 	$operatorBeforeWin = null;
// 		// 	$winAmountMathSign = null;
// 		// 	$winAmountValue = null;
// 		// 	$formula = $this->_prepareFormulaOfVipUpgradeSetting($betAmountMathSign
// 		// 		, $betAmountValue
// 		// 		, $operatorBeforeDeposit
// 		// 		, $depositAmountMathSign
// 		// 		, $depositAmountValue
// 		// 		, $operatorBeforeLoss
// 		// 		, $lossAmountMathSign
// 		// 		, $lossAmountValue
// 		// 		, $operatorBeforeWin
// 		// 		, $winAmountMathSign
// 		// 		, $winAmountValue );
// 		// 	$data['formula'] = $formula;
// 		//
// 		// 	// game_platform_and_type
// 		// 	$params = [];
// 		// 	$params['defaultValue'] = 20;
// 		// 	$params['defaultMathSign'] = '>=';
// 		// 	$gameKeyInfoList= [];
// 		// 	$gameKeyInfoList['type'] = 'game_platform';
// 		// 	$gameKeyInfoList['value'] = 28;
// 		// 	$gameKeyInfoList['math_sign'] = '<=';
// 		// 	$gameKeyInfoList['game_platform_id'] = '5674';// MUST BE STRING
// 		// 	$params['GKAMSAVL'][] = $gameKeyInfoList;
// 		// 	$gameKeyInfoList= [];
// 		// 	$gameKeyInfoList['type'] = 'game_type';
// 		// 	$gameKeyInfoList['value'] = 29;
// 		// 	$gameKeyInfoList['math_sign'] = '<=';
// 		// 	$gameKeyInfoList['game_type_id'] = '561'; // MUST BE STRING
// 		// 	$gameKeyInfoList['precon_logic_flag'] = 'or';
// 		// 	$params['GKAMSAVL'][] = $gameKeyInfoList;
// 		//
// 		// 	$defaultValue = $params['defaultValue'];
// 		// 	$defaultMathSign = $params['defaultMathSign'];
// 		// 	$gameKeysAndMathSignAndValueList = $params['GKAMSAVL'];
// 		// 	$theBetAmountSettings = $this->_prepareBetAmountSettingsOfVipUpgradeSetting($defaultValue, $defaultMathSign, $gameKeysAndMathSignAndValueList);
// 		// 	// $rlt = call_user_func_array([$this, '_prepareBetAmountSettingsOfVipUpgradeSetting'], $testInfo['params']);
// 		// 	$data['bet_amount_settings'] = $theBetAmountSettings; // always be NULL
// 		//
// 		// 	/// SA
// 		// 	$data['accumulation'] = 0; // 0 / 1 / 4 : No / Yes, Registration Date / Yes, Last Change Period
// 		// 	$data['separate_accumulation_settings'] = '{"bet_amount": {"accumulation": "1"}, "win_amount": {"accumulation": "4"}, "loss_amount": {"accumulation": "0"}, "deposit_amount": {"accumulation": "1"}}'; // always be NULL
// 		//
// 		// 	$params = [$settingName, $forGrade, $data];
// 		// 	$getUpgradeLevelSettingFn = function($_this) use ($params){
// 		// 		// '_getDowngradeLevelSettingFn'; // so far, CACB
// 		// 		// $params = [$settingName, $data];
// 		// 		// $theGenerateCallTrace = $this->utils->generateCallTrace();
// 		// 		// echo '<pre>';print_r($theGenerateCallTrace);exit();
// 		// 		return call_user_func_array([$_this->utils4testogp, '_getDowngradeLevelSettingFnV2'], $params); // $rlt = $this->_syncUpgradeLevelSettingByName($settingName, $data);
// 		// 	};
// 		// }

// 		/// handle #1
// 		switch(true){
// 			case $isCACB :
// 				$settingName = $theSettingNamePrefix.'.CACB'; // #1
// 				break;
// 			case $isSACB :
// 				$settingName = $theSettingNamePrefix.'.SACB'; // #1
// 				break;
// 			case $isCASB :
// 				$settingName = $theSettingNamePrefix.'.CASB'; // #1
// 				break;
// 			case $isSASB :
// 				$settingName = $theSettingNamePrefix.'.SASB'; // #1
// 				break;
// 		}
//
//
// 		$isIsConditionMet= $this->utils4testogp->_pos($theCombinedCase, 'IsConditionMet');
// 		$isIsConditionNotMet = $this->utils4testogp->_pos($theCombinedCase, 'IsConditionNotMet');
// var_dump(['debugIn5081.isCASB:', $isCASB, 'theCombinedCase:', $theCombinedCase]);
// 		/// handle #2 ~ #12
// 		switch(true){
// 			case $isEmptyUpgradeSetting:
// 				// Formula, ""
// 				//  But maybe the variable "$upgrade_id = 0;" will be used in admin/application/controllers/cli/testing_ogp21818.php
// 				$betAmountMathSign = null; // #2
// 				$betAmountValue = null; // #3
// 				$operatorBeforeDeposit = null; // #4
// 				$depositAmountMathSign = null; // #5
// 				$depositAmountValue = null; // #6
// 				$operatorBeforeLoss = null; // #7
// 				$lossAmountMathSign = null; // #8
// 				$lossAmountValue = null; // #9
// 				$operatorBeforeWin = null; // #10
// 				$winAmountMathSign = null; // #11
// 				$winAmountValue = null; // #12
// 				break;
// 			case $isDepositUpgradeSetting:
// 				// Formula, "deposit >= 0"
// 				$betAmountMathSign = null; // #2
// 				$betAmountValue = null; // #3
// 				$operatorBeforeDeposit = null; // #4
// 				$depositAmountMathSign = '>='; // #5
// 				$depositAmountValue = 0; // #6
// 				$operatorBeforeLoss = null; // #7
// 				$lossAmountMathSign = null; // #8
// 				$lossAmountValue = null; // #9
// 				$operatorBeforeWin = null; // #10
// 				$winAmountMathSign = null; // #11
// 				$winAmountValue = null; // #12
// 				// if($isIsDowngradeConditionNotMet){
// 				// 	$depositAmountMathSign = '<'; // #5
// 				// }
// 				break;
// 			case $isBetUpgradeSetting:
// 				// Formula, "bet >= 0"
// 				$betAmountMathSign = '>='; // #2
// 				$betAmountValue = 0; // #3
// 				$operatorBeforeDeposit = null; // #4
// 				$depositAmountMathSign = null; // #5
// 				$depositAmountValue = null; // #6
// 				$operatorBeforeLoss = null; // #7
// 				$lossAmountMathSign = null; // #8
// 				$lossAmountValue = null; // #9
// 				$operatorBeforeWin = null; // #10
// 				$winAmountMathSign = null; // #11
// 				$winAmountValue = null; // #12
// 				break;
// 			case $isDepositBetUpgradeSetting:
// 				// Formula, "bet >= 0 and deposit >= 0"
// 				$betAmountMathSign = '>='; // #2
// 				$betAmountValue = 0; // #3
// 				$operatorBeforeDeposit = 'and'; // #4
// 				$depositAmountMathSign = '>='; // #5
// 				$depositAmountValue = 0; // #6
// 				$operatorBeforeLoss = null; // #7
// 				$lossAmountMathSign = null; // #8
// 				$lossAmountValue = null; // #9
// 				$operatorBeforeWin = null; // #10
// 				$winAmountMathSign = null; // #11
// 				$winAmountValue = null; // #12
// 				break;
// 			case $isAllNoMetInFormula:
// 				// Formula, "bet < 0 and deposit < 0 and loss < 0 and win < 0"
// 				$betAmountMathSign = '<'; // #2
// 				$betAmountValue = 0; // #3
// 				$operatorBeforeDeposit = 'and'; // #4
// 				$depositAmountMathSign = '<'; // #5
// 				$depositAmountValue = 0; // #6
// 				$operatorBeforeLoss = 'and'; // #7
// 				$lossAmountMathSign = '<'; // #8
// 				$lossAmountValue = 0; // #9
// 				$operatorBeforeWin = 'and'; // #10
// 				$winAmountMathSign = '<'; // #11
// 				$winAmountValue = 0; // #12
// 				break;
// 			case $isOnlyMetBetOfAllInFormula:
// 				// Formula, "bet >= 0 and deposit < 0 and loss < 0 and win < 0"
// 				$betAmountMathSign = '>='; // #2
// 				$betAmountValue = 0; // #3
// 				$operatorBeforeDeposit = 'and'; // #4
// 				$depositAmountMathSign = '<'; // #5
// 				$depositAmountValue = 0; // #6
// 				$operatorBeforeLoss = 'and'; // #7
// 				$lossAmountMathSign = '<'; // #8
// 				$lossAmountValue = 0; // #9
// 				$operatorBeforeWin = 'and'; // #10
// 				$winAmountMathSign = '<'; // #11
// 				$winAmountValue = 0; // #12
// 				break;
// 			case $isOnlyMetDepositOfAllInFormula:
// 				// Formula, "bet < 0 and deposit >= 0 and loss < 0 and win < 0"
// 				$betAmountMathSign = '<'; // #2
// 				$betAmountValue = 0; // #3
// 				$operatorBeforeDeposit = 'and'; // #4
// 				$depositAmountMathSign = '>='; // #5
// 				$depositAmountValue = 0; // #6
// 				$operatorBeforeLoss = 'and'; // #7
// 				$lossAmountMathSign = '<'; // #8
// 				$lossAmountValue = 0; // #9
// 				$operatorBeforeWin = 'and'; // #10
// 				$winAmountMathSign = '<'; // #11
// 				$winAmountValue = 0; // #12
// 				break;
// 			case $isOnlyMetBetDepositOfAllInFormula:
// 				// Formula, "bet >= 0 and deposit >= 0 and loss < 0 and win < 0"
// 				$betAmountMathSign = '>='; // #2
// 				$betAmountValue = 0; // #3
// 				$operatorBeforeDeposit = 'and'; // #4
// 				$depositAmountMathSign = '>='; // #5
// 				$depositAmountValue = 0; // #6
// 				$operatorBeforeLoss = 'and'; // #7
// 				$lossAmountMathSign = '<'; // #8
// 				$lossAmountValue = 0; // #9
// 				$operatorBeforeWin = 'and'; // #10
// 				$winAmountMathSign = '<'; // #11
// 				$winAmountValue = 0; // #12
// 				break;
// 			case $isOnlyMetBetDepositWinOfAllInFormula:
// 				// Formula, "bet >= 0 and deposit >= 0 and loss < 0 and win >= 0"
// 				$betAmountMathSign = '>='; // #2
// 				$betAmountValue = 0; // #3
// 				$operatorBeforeDeposit = 'and'; // #4
// 				$depositAmountMathSign = '>='; // #5
// 				$depositAmountValue = 0; // #6
// 				$operatorBeforeLoss = 'and'; // #7
// 				$lossAmountMathSign = '<'; // #8
// 				$lossAmountValue = 0; // #9
// 				$operatorBeforeWin = 'and'; // #10
// 				$winAmountMathSign = '>='; // #11
// 				$winAmountValue = 0; // #12
// 				break;
// 			case $isAllMetInFormula:
// 				// Formula, "bet >= 0 and deposit >= 0 and loss >= 0 and win >= 0"
// 				$betAmountMathSign = '>='; // #2
// 				$betAmountValue = 0; // #3
// 				$operatorBeforeDeposit = 'and'; // #4
// 				$depositAmountMathSign = '>='; // #5
// 				$depositAmountValue = 0; // #6
// 				$operatorBeforeLoss = 'and'; // #7
// 				$lossAmountMathSign = '>='; // #8
// 				$lossAmountValue = 0; // #9
// 				$operatorBeforeWin = 'and'; // #10
// 				$winAmountMathSign = '>='; // #11
// 				$winAmountValue = 0; // #12
// 				break;
// 				case $isOnlyMetBetOfBetInFormula:
// 				// Formula, "bet >= 0"
// 				$betAmountMathSign = '>='; // #2
// 				$betAmountValue = 0; // #3
// 				$operatorBeforeDeposit = null; // #4
// 				$depositAmountMathSign = null; // #5
// 				$depositAmountValue = null; // #6
// 				$operatorBeforeLoss = null; // #7
// 				$lossAmountMathSign = null; // #8
// 				$lossAmountValue = null; // #9
// 				$operatorBeforeWin = null; // #10
// 				$winAmountMathSign = null; // #11
// 				$winAmountValue = null; // #12
// 				break;
// 				case $isOnlyMetBetOfBetDepositInFormula:
// 				// Formula, "bet >= 0 and deposit < 0 "
// 				$betAmountMathSign = '>='; // #2
// 				$betAmountValue = 0; // #3
// 				$operatorBeforeDeposit = 'and'; // #4
// 				$depositAmountMathSign = '<'; // #5
// 				$depositAmountValue = 0; // #6
// 				$operatorBeforeLoss = null; // #7
// 				$lossAmountMathSign = null; // #8
// 				$lossAmountValue = null; // #9
// 				$operatorBeforeWin = null; // #10
// 				$winAmountMathSign = null; // #11
// 				$winAmountValue = null; // #12
// 				break;
//
//
// 		} // EOF switch(true){
//
// 		//  isIsConditionMet and isIsConditionNotMet Only for isDepositUpgradeSetting, isBetUpgradeSetting and isDepositBetUpgradeSetting
// 		if( $isDepositUpgradeSetting
// 			|| $isBetUpgradeSetting
// 			|| $isDepositBetUpgradeSetting
// 		){
// 			if( $isIsConditionMet ){
// 				if($betAmountMathSign !== null){
// 					$betAmountMathSign = '>=';
// 				}
// 				if($depositAmountMathSign !== null){
// 					$depositAmountMathSign = '>=';
// 				}
// 				if($lossAmountMathSign !== null){
// 					$lossAmountMathSign = '>=';
// 				}
// 				if($winAmountMathSign !== null){
// 					$winAmountMathSign = '>=';
// 				}
// 			}else if($isIsConditionNotMet){
// 				if($betAmountMathSign !== null){
// 					$betAmountMathSign = '<';
// 				}
// 				if($depositAmountMathSign !== null){
// 					$depositAmountMathSign = '<';
// 				}
// 				if($lossAmountMathSign !== null){
// 					$lossAmountMathSign = '<';
// 				}
// 				if($winAmountMathSign !== null){
// 					$winAmountMathSign = '<';
// 				}
// 			}
// 		}
//
//
// 		// $settingName = $theSettingNamePrefix.'.CACB'; // #1
// 		// $betAmountMathSign = '>='; // #2
// 		// $betAmountValue = 0; // #3
// 		// $operatorBeforeDeposit = 'and'; // #4
// 		// $depositAmountMathSign = '>='; // #5
// 		// $depositAmountValue = 0; // #6
// 		// $operatorBeforeLoss = 'and'; // #7
// 		// $lossAmountMathSign = '>='; // #8
// 		// $lossAmountValue = 0; // #9
// 		// $operatorBeforeWin = null; // #10
// 		// $winAmountMathSign = null; // #11
// 		// $winAmountValue = null; // #12
// 		$_accumulation = $accumulation; // #13, from params
// 		/// handle #13, #14
// 		// for CA
// 		if($isCACB || $isCASB ){
// 			$accumulation = $_accumulation; // #13, from params
// 			$separate_accumulation_settings = null; // #14
// 		}
// 		// for SA
// 		if($isSACB || $isSASB){
// 			// separate_accumulation_settings_format with 4 params:bet, win, loss and deposit.
// 			$separate_accumulation_settings_format = '{"bet_amount": {"accumulation": "%s"}, "win_amount": {"accumulation": "%s"}, "loss_amount": {"accumulation": "%s"}, "deposit_amount": {"accumulation": "%s"}}';
// 			$separate_accumulation_settings = sprintf($separate_accumulation_settings_format, $_accumulation, $_accumulation, $_accumulation, $_accumulation); // #14
// 			$accumulation = 0; // override common accumulation
// 		}
//
// 		$theBetAmountSettings = null; // default
// 		// for CB
// 		if($isCACB || $isSACB ){
// 			if($betAmountMathSign !== null){ // had setup bet setting.
// 				$theBetAmountSettings = null;
// 			}
// 		}
// 		// for SB
// 		if($isCASB || $isSASB ){
// 			if($betAmountMathSign !== null){ // had setup bet setting.
// 			// game_platform_and_type
// 			$params = [];
// 			$params['defaultValue'] = 5334;
// 			$params['defaultMathSign'] = $betAmountMathSign;
// 			$gameKeyInfoList= [];
// 			$gameKeyInfoList['type'] = 'game_platform';
// 			$gameKeyInfoList['value'] = 0;
// 			$gameKeyInfoList['math_sign'] = $betAmountMathSign;
// 			/// game_platform_id is a random number
// 			$gameKeyInfoList['game_platform_id'] = "5674";// MUST BE STRING
// 			$params['GKAMSAVL'][] = $gameKeyInfoList;
// 			$gameKeyInfoList= [];
// 			$gameKeyInfoList['type'] = 'game_type';
// 			$gameKeyInfoList['value'] = 0;
// 			$gameKeyInfoList['math_sign'] = $betAmountMathSign;
// 			/// game_type_id is a random number
// 			$gameKeyInfoList['game_type_id'] = "561";// MUST BE STRING
// 			$gameKeyInfoList['precon_logic_flag'] = 'or';
// 			$params['GKAMSAVL'][] = $gameKeyInfoList;
// 			$defaultValue = $params['defaultValue'];
// 			$defaultMathSign = $params['defaultMathSign'];
// 			$gameKeysAndMathSignAndValueList = $params['GKAMSAVL'];
// 			$theBetAmountSettings = $this->_prepareBetAmountSettingsOfVipUpgradeSetting($defaultValue, $defaultMathSign, $gameKeysAndMathSignAndValueList);
//
// 			$betAmountValue = 0;
// 			}
//
//
// 		}// EOF if($isCASB || $isSASB ){...
//
// 			var_dump(['debugIn5549.will getUpgradeLevelSettingFnWithParams']);
// 		/// @todo for SB, ref. to _prepareBetAmountSettingsOfVipUpgradeSetting() in "admin/application/controllers/cli/testing_ogp21673.php".
// // var_dump(['debugIn5160.betAmountValue:', $betAmountValue, $operatorBeforeDeposit, $depositAmountMathSign]);
// 		$forGrade = $forGrade; // #15, from params
// 		$getUpgradeLevelSettingFn = $this->getUpgradeLevelSettingFnWithParams(	$settingName // #1
// 													, $betAmountMathSign  // #2
// 													, $betAmountValue // #3
// 													, $operatorBeforeDeposit // #4
// 													, $depositAmountMathSign // #5
// 													, $depositAmountValue // #6
// 													, $operatorBeforeLoss // #7
// 													, $lossAmountMathSign // #8
// 													, $lossAmountValue // #9
// 													, $operatorBeforeWin // #10
// 													, $winAmountMathSign // #11
// 													, $winAmountValue // #12
// 													, $accumulation // #13
// 													, $separate_accumulation_settings // #14
// 													, $forGrade // #15
// 													, $theBetAmountSettings // for separate betting setting
// 												);
//
//
//
// 		return [$settingName, $getUpgradeLevelSettingFn];
	}// EOF _getUpgradeLevelSettingFnAndSettingNameFromCombinedCase

	/**
	 * Uncaught Exception Error: Call to protected method BaseTesting::test()
	 *
	 * @return void
	 */
	public function execTest(){
		$params = func_get_args();
		//$this->test()
		return call_user_func_array([$this, 'test'], $params);
	}


	/**
	 * Undocumented function
	 *
	 * @param string $settingName The sync setting name.
	 * @param string $forGrade The keyword,"upgrade" and "downgrade".
	 * @param array $theMergedData
	 * @return array [$rlt, $settingName]
	 */
	private function DEL_getDowngradeLevelSettingFnV2(	$settingName = 'devDowngradeMet.CACB'
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
		$data['accumulation'] = 1; // 0 / 1 / 4 : No / Yes, Registration Date / Yes, Last Change Period
		$data['separate_accumulation_settings'] = NULL; // always be NULL
		$data = array_merge( $data, $theMergedData );
		$params = [$settingName, $data];
		$rlt = call_user_func_array([$this, '_syncUpgradeLevelSettingByName'], $params); // $rlt = $this->_syncUpgradeLevelSettingByName($settingName, $data);
		$this->utils->debug_log('4130.params:', $params, 'rlt:', $rlt);
		$note = sprintf($this->noteTpl, '[Step]preset VipUpgradeSetting in CACB for upgrade success', var_export($params, true), var_export($rlt, true) );
		$this->test( true // result
			,  true // expect
			, __METHOD__. ' '. 'Preset vip_upgrade_setting table' // title
			, $note // note
		);

		return [$rlt, $settingName];

	} // EOF DEL_getDowngradeLevelSettingFnV2
	/**
	 * Get use sync(new / update) the Upgrade Level Setting By Name
	 * clone from $getUpgradeLevelSettingFn
	 *
	 * @param string $settingName The setting name
	 * @param string $forGrade The setting for upgrade or downgrade.
	 * @return void
	 */
	private function DEL_getDowngradeLevelSettingFn($settingName = 'devDowngradeMet.CACB', $forGrade = 'upgrade'){
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
	} // EOF DEL_getDowngradeLevelSettingFn


	/**
	 * [TESTED]
	 * http://admin.og.local/cli/testing_ogp24373/index/test_prepareBetAmountSettingsOfVipUpgradeSetting
	 *
	 * @return void
	 */
	public function test_prepareBetAmountSettingsOfVipUpgradeSetting(){

		$this->init();

		$testCases = [];

		// test game_type only
		$testCases['game_type_only'] = [];
		$testCases['game_type_only']['params'] = [];
		$testCases['game_type_only']['params']['defaultValue'] = 20;
		$testCases['game_type_only']['params']['defaultMathSign'] = '>=';
		$gameKeyInfoList= [];
		$gameKeyInfoList['type'] = 'game_type';
		$gameKeyInfoList['value'] = 24;
		$gameKeyInfoList['math_sign'] = '>=';
		$gameKeyInfoList['game_type_id'] = '561'; // MUST BE STRING
		// GKAMSAVL = gameKeysAndMathSignAndValueList
		$testCases['game_type_only']['params']['GKAMSAVL'][] = $gameKeyInfoList;
		$testCases['game_type_only']['expect'] = '{"defaultItem":{"value":20,"math_sign":">="},"itemList":[{"type":"game_type","value":24,"math_sign":">=","game_type_id":561}]}';

		// test game_platform only
		$testCases['game_platform_only'] = [];
		$testCases['game_platform_only']['params'] = [];
		$testCases['game_platform_only']['params']['defaultValue'] = 20;
		$testCases['game_platform_only']['params']['defaultMathSign'] = '>=';
		$gameKeyInfoList= [];
		$gameKeyInfoList['type'] = 'game_platform';
		$gameKeyInfoList['value'] = 25;
		$gameKeyInfoList['math_sign'] = '>=';
		$gameKeyInfoList['game_platform_id'] = '5674'; // MUST BE STRING
		// GKAMSAVL = gameKeysAndMathSignAndValueList
		$testCases['game_platform_only']['params']['GKAMSAVL'][] = $gameKeyInfoList;
		$testCases['game_platform_only']['expect'] = '{"defaultItem":{"value":20,"math_sign":">="},"itemList":[{"type":"game_platform","value":25,"math_sign":">=","game_platform_id":5674}]}';

		// test game_type and game_platform
		$testCases['game_type_and_platform'] = [];
		$testCases['game_type_and_platform']['params'] = [];
		$testCases['game_type_and_platform']['params']['defaultValue'] = 20;
		$testCases['game_type_and_platform']['params']['defaultMathSign'] = '>=';
		$gameKeyInfoList= [];
		$gameKeyInfoList['type'] = 'game_type';
		$gameKeyInfoList['value'] = 26;
		$gameKeyInfoList['math_sign'] = '>=';
		$gameKeyInfoList['game_type_id'] = '561'; // MUST BE STRING
		$testCases['game_type_and_platform']['params']['GKAMSAVL'][] = $gameKeyInfoList;
		$gameKeyInfoList= [];
		$gameKeyInfoList['type'] = 'game_platform';
		$gameKeyInfoList['value'] = 27;
		$gameKeyInfoList['math_sign'] = '>=';
		$gameKeyInfoList['game_platform_id'] = '5674'; // MUST BE STRING
		$gameKeyInfoList['precon_logic_flag'] = 'and';
		$testCases['game_type_and_platform']['params']['GKAMSAVL'][] = $gameKeyInfoList;
		$testCases['game_type_and_platform']['expect'] = '{"defaultItem":{"value":20,"math_sign":">="},"itemList":[{"type":"game_type","value":26,"math_sign":">=","game_type_id":561},{"type":"game_platform","value":27,"math_sign":">=","game_platform_id":5674,"precon_logic_flag":"and"}]}';

		// test game_platform and game_type
		$testCases['game_platform_and_type'] = [];
		$testCases['game_platform_and_type']['params'] = [];
		$testCases['game_platform_and_type']['params']['defaultValue'] = 20;
		$testCases['game_platform_and_type']['params']['defaultMathSign'] = '>=';
		$gameKeyInfoList= [];
		$gameKeyInfoList['type'] = 'game_platform';
		$gameKeyInfoList['value'] = 28;
		$gameKeyInfoList['math_sign'] = '>=';
		$gameKeyInfoList['game_platform_id'] = '5674'; // MUST BE STRING
		$testCases['game_platform_and_type']['params']['GKAMSAVL'][] = $gameKeyInfoList;
		$gameKeyInfoList= [];
		$gameKeyInfoList['type'] = 'game_type';
		$gameKeyInfoList['value'] = 29;
		$gameKeyInfoList['math_sign'] = '>=';
		$gameKeyInfoList['game_type_id'] = '561'; // MUST BE STRING
		$gameKeyInfoList['precon_logic_flag'] = 'and';
		$testCases['game_platform_and_type']['params']['GKAMSAVL'][] = $gameKeyInfoList;
		$testCases['game_platform_and_type']['expect'] = '{"defaultItem":{"value":20,"math_sign":">="},"itemList":[{"type":"game_platform","value":28,"math_sign":">=","game_platform_id":5674},{"type":"game_type","value":29,"math_sign":">=","game_type_id":561,"precon_logic_flag":"and"}]}';

		// test empty game
		$testCases['empty_game'] = [];
		$testCases['empty_game']['params'] = [];
		$testCases['empty_game']['params']['defaultValue'] = 20;
		$testCases['empty_game']['params']['defaultMathSign'] = '>=';
		$gameKeyInfoList= [];
		$testCases['empty_game']['params']['GKAMSAVL'] = $gameKeyInfoList;
		$testCases['empty_game']['expect'] = 'null';


		foreach($testCases as $indexKey => $testInfo){

			$rlt = call_user_func_array([$this, '_prepareBetAmountSettingsOfVipUpgradeSetting'], $testInfo['params']);

			$exported_testParams = var_export($testInfo['params'], true);
			$exported_rlt = var_export($rlt, true);
			$note = sprintf($this->noteTpl, $indexKey, $exported_testParams, $exported_rlt);
			$this->test( $rlt // result
				, $testInfo['expect'] // expect
				, __METHOD__ // title
				, $note // note
			);
		} // EOF foreach($testCases as $indexKey => $testInfo){...

	} // EOF test_prepareBetAmountSettingsOfVipUpgradeSetting

	/**
	 * [TESTED]
	 * URI, http://admin.og.local/cli/testing_ogp24373/index/test_prepareFormulaOfVipUpgradeSetting
	 *
	 * @return void
	 */
	public function test_prepareFormulaOfVipUpgradeSetting(){

		$this->init();

		$testCases = [];
		$testCases['0,0,0,0'] = [];
		$testCases['0,0,0,0']['params']['betAmountMathSign'] = null;
		$testCases['0,0,0,0']['params']['betAmountValue'] = null;
		$testCases['0,0,0,0']['params']['operatorBeforeDeposit'] = null;
		$testCases['0,0,0,0']['params']['depositAmountMathSign'] = null;
		$testCases['0,0,0,0']['params']['depositAmountValue'] = null;
		$testCases['0,0,0,0']['params']['operatorBeforeLoss'] = null;
		$testCases['0,0,0,0']['params']['lossAmountMathSign'] = null;
		$testCases['0,0,0,0']['params']['lossAmountValue'] = null;
		$testCases['0,0,0,0']['params']['operatorBeforeWin'] = null;
		$testCases['0,0,0,0']['params']['winAmountMathSign'] = null;
		$testCases['0,0,0,0']['params']['winAmountValue'] = null;
		$testCases['0,0,0,0']['expect'] = 'null';

		$testCases['0,0,0,1'] = [];
		$testCases['0,0,0,1']['params']['betAmountMathSign'] = null;
		$testCases['0,0,0,1']['params']['betAmountValue'] = null;
		$testCases['0,0,0,1']['params']['operatorBeforeDeposit'] = null;
		$testCases['0,0,0,1']['params']['depositAmountMathSign'] = null;
		$testCases['0,0,0,1']['params']['depositAmountValue'] = null;
		$testCases['0,0,0,1']['params']['operatorBeforeLoss'] = null;
		$testCases['0,0,0,1']['params']['lossAmountMathSign'] = null;
		$testCases['0,0,0,1']['params']['lossAmountValue'] = null;
		$testCases['0,0,0,1']['params']['operatorBeforeWin'] = null;
		$testCases['0,0,0,1']['params']['winAmountMathSign'] = '>=';
		$testCases['0,0,0,1']['params']['winAmountValue'] = 1;
		$testCases['0,0,0,1']['expect'] = '{"win_amount":[">=",1]}';

		$testCases['0,0,1,0'] = [];
		$testCases['0,0,1,0']['params']['betAmountMathSign'] = null;
		$testCases['0,0,1,0']['params']['betAmountValue'] = null;
		$testCases['0,0,1,0']['params']['operatorBeforeDeposit'] = null;
		$testCases['0,0,1,0']['params']['depositAmountMathSign'] = null;
		$testCases['0,0,1,0']['params']['depositAmountValue'] = null;
		$testCases['0,0,1,0']['params']['operatorBeforeLoss'] = null;
		$testCases['0,0,1,0']['params']['lossAmountMathSign'] = '>=';
		$testCases['0,0,1,0']['params']['lossAmountValue'] = '1';
		$testCases['0,0,1,0']['params']['operatorBeforeWin'] = null;
		$testCases['0,0,1,0']['params']['winAmountMathSign'] = null;
		$testCases['0,0,1,0']['params']['winAmountValue'] = null;
		$testCases['0,0,1,0']['expect'] = '{"loss_amount":[">=","1"]}';

		$testCases['0,0,1,1'] = [];
		$testCases['0,0,1,1']['params']['betAmountMathSign'] = null;
		$testCases['0,0,1,1']['params']['betAmountValue'] = null;
		$testCases['0,0,1,1']['params']['operatorBeforeDeposit'] = null;
		$testCases['0,0,1,1']['params']['depositAmountMathSign'] = null;
		$testCases['0,0,1,1']['params']['depositAmountValue'] = null;
		$testCases['0,0,1,1']['params']['operatorBeforeLoss'] = null;
		$testCases['0,0,1,1']['params']['lossAmountMathSign'] = '>=';
		$testCases['0,0,1,1']['params']['lossAmountValue'] = '1';
		$testCases['0,0,1,1']['params']['operatorBeforeWin'] = 'and';
		$testCases['0,0,1,1']['params']['winAmountMathSign'] = '<=';
		$testCases['0,0,1,1']['params']['winAmountValue'] = '2';
		$testCases['0,0,1,1']['expect'] = '{"loss_amount":[">=","1"],"operator_2":"and","win_amount":["<=","2"]}';

		$testCases['0,1,0,0'] = [];
		$testCases['0,1,0,0']['params']['betAmountMathSign'] = null;
		$testCases['0,1,0,0']['params']['betAmountValue'] = null;
		$testCases['0,1,0,0']['params']['operatorBeforeDeposit'] = null;
		$testCases['0,1,0,0']['params']['depositAmountMathSign'] = '>=';
		$testCases['0,1,0,0']['params']['depositAmountValue'] = 1;
		$testCases['0,1,0,0']['params']['operatorBeforeLoss'] = null;
		$testCases['0,1,0,0']['params']['lossAmountMathSign'] = null;
		$testCases['0,1,0,0']['params']['lossAmountValue'] = null;
		$testCases['0,1,0,0']['params']['operatorBeforeWin'] = null;
		$testCases['0,1,0,0']['params']['winAmountMathSign'] = null;
		$testCases['0,1,0,0']['params']['winAmountValue'] = null;
		$testCases['0,1,0,0']['expect'] = '{"deposit_amount":[">=",1]}';

		$testCases['0,1,0,1'] = [];
		$testCases['0,1,0,1']['params']['betAmountMathSign'] = null;
		$testCases['0,1,0,1']['params']['betAmountValue'] = null;
		$testCases['0,1,0,1']['params']['operatorBeforeDeposit'] = null;
		$testCases['0,1,0,1']['params']['depositAmountMathSign'] = '>=';
		$testCases['0,1,0,1']['params']['depositAmountValue'] = 1;
		$testCases['0,1,0,1']['params']['operatorBeforeLoss'] = null;
		$testCases['0,1,0,1']['params']['lossAmountMathSign'] = null;
		$testCases['0,1,0,1']['params']['lossAmountValue'] = null;
		$testCases['0,1,0,1']['params']['operatorBeforeWin'] = 'and';
		$testCases['0,1,0,1']['params']['winAmountMathSign'] = '>=';
		$testCases['0,1,0,1']['params']['winAmountValue'] = 2;
		$testCases['0,1,0,1']['expect'] = '{"deposit_amount":[">=",1],"operator_2":"and","win_amount":[">=",2]}';

		$testCases['0,1,1,0'] = [];
		$testCases['0,1,1,0']['params']['betAmountMathSign'] = null;
		$testCases['0,1,1,0']['params']['betAmountValue'] = null;
		$testCases['0,1,1,0']['params']['operatorBeforeDeposit'] = null;
		$testCases['0,1,1,0']['params']['depositAmountMathSign'] = '>=';
		$testCases['0,1,1,0']['params']['depositAmountValue'] = 1;
		$testCases['0,1,1,0']['params']['operatorBeforeLoss'] = 'and';
		$testCases['0,1,1,0']['params']['lossAmountMathSign'] = '>=';
		$testCases['0,1,1,0']['params']['lossAmountValue'] = 2;
		$testCases['0,1,1,0']['params']['operatorBeforeWin'] = null;
		$testCases['0,1,1,0']['params']['winAmountMathSign'] = null;
		$testCases['0,1,1,0']['params']['winAmountValue'] = null;
		$testCases['0,1,1,0']['expect'] = '{"deposit_amount":[">=",1],"operator_2":"and","loss_amount":[">=",2]}';

		$testCases['0,1,1,1'] = [];
		$testCases['0,1,1,1']['params']['betAmountMathSign'] = null;
		$testCases['0,1,1,1']['params']['betAmountValue'] = null;
		$testCases['0,1,1,1']['params']['operatorBeforeDeposit'] = null;
		$testCases['0,1,1,1']['params']['depositAmountMathSign'] = '>=';
		$testCases['0,1,1,1']['params']['depositAmountValue'] = 1;
		$testCases['0,1,1,1']['params']['operatorBeforeLoss'] = 'and';
		$testCases['0,1,1,1']['params']['lossAmountMathSign'] = '>=';
		$testCases['0,1,1,1']['params']['lossAmountValue'] = 2;
		$testCases['0,1,1,1']['params']['operatorBeforeWin'] = 'or';
		$testCases['0,1,1,1']['params']['winAmountMathSign'] = '<=';
		$testCases['0,1,1,1']['params']['winAmountValue'] = 3;
		$testCases['0,1,1,1']['expect'] = '{"deposit_amount":[">=",1],"operator_2":"and","loss_amount":[">=",2],"operator_3":"or","win_amount":["<=",3]}';

		// ==== ==== ==== ==== ==== ==== ==== ==== ==== ==== ====
		$testCases['1,0,0,0'] = [];
		$testCases['1,0,0,0']['params']['betAmountMathSign'] = '>=';
		$testCases['1,0,0,0']['params']['betAmountValue'] = 1;
		$testCases['1,0,0,0']['params']['operatorBeforeDeposit'] = null;
		$testCases['1,0,0,0']['params']['depositAmountMathSign'] = null;
		$testCases['1,0,0,0']['params']['depositAmountValue'] = null;
		$testCases['1,0,0,0']['params']['operatorBeforeLoss'] = null;
		$testCases['1,0,0,0']['params']['lossAmountMathSign'] = null;
		$testCases['1,0,0,0']['params']['lossAmountValue'] = null;
		$testCases['1,0,0,0']['params']['operatorBeforeWin'] = null;
		$testCases['1,0,0,0']['params']['winAmountMathSign'] = null;
		$testCases['1,0,0,0']['params']['winAmountValue'] = null;
		$testCases['1,0,0,0']['expect'] = '{"bet_amount":[">=",1]}';

		$testCases['1,0,0,1'] = [];
		$testCases['1,0,0,1']['params']['betAmountMathSign'] = '<=';
		$testCases['1,0,0,1']['params']['betAmountValue'] = 2;
		$testCases['1,0,0,1']['params']['operatorBeforeDeposit'] = null;
		$testCases['1,0,0,1']['params']['depositAmountMathSign'] = null;
		$testCases['1,0,0,1']['params']['depositAmountValue'] = null;
		$testCases['1,0,0,1']['params']['operatorBeforeLoss'] = null;
		$testCases['1,0,0,1']['params']['lossAmountMathSign'] = null;
		$testCases['1,0,0,1']['params']['lossAmountValue'] = null;
		$testCases['1,0,0,1']['params']['operatorBeforeWin'] = 'and';
		$testCases['1,0,0,1']['params']['winAmountMathSign'] = '>=';
		$testCases['1,0,0,1']['params']['winAmountValue'] = 1;
		$testCases['1,0,0,1']['expect'] = '{"bet_amount":["<=",2],"operator_2":"and","win_amount":[">=",1]}';

		$testCases['1,0,1,0'] = [];
		$testCases['1,0,1,0']['params']['betAmountMathSign'] = '<=';
		$testCases['1,0,1,0']['params']['betAmountValue'] = 2;
		$testCases['1,0,1,0']['params']['operatorBeforeDeposit'] = null;
		$testCases['1,0,1,0']['params']['depositAmountMathSign'] = null;
		$testCases['1,0,1,0']['params']['depositAmountValue'] = null;
		$testCases['1,0,1,0']['params']['operatorBeforeLoss'] = 'and';
		$testCases['1,0,1,0']['params']['lossAmountMathSign'] = '>=';
		$testCases['1,0,1,0']['params']['lossAmountValue'] = '1';
		$testCases['1,0,1,0']['params']['operatorBeforeWin'] = null;
		$testCases['1,0,1,0']['params']['winAmountMathSign'] = null;
		$testCases['1,0,1,0']['params']['winAmountValue'] = null;
		$testCases['1,0,1,0']['expect'] = '{"bet_amount":["<=",2],"operator_2":"and","loss_amount":[">=","1"]}';

		$testCases['1,0,1,1'] = [];
		$testCases['1,0,1,1']['params']['betAmountMathSign'] = '<=';
		$testCases['1,0,1,1']['params']['betAmountValue'] = 3;
		$testCases['1,0,1,1']['params']['operatorBeforeDeposit'] = null;
		$testCases['1,0,1,1']['params']['depositAmountMathSign'] = null;
		$testCases['1,0,1,1']['params']['depositAmountValue'] = null;
		$testCases['1,0,1,1']['params']['operatorBeforeLoss'] = 'or';
		$testCases['1,0,1,1']['params']['lossAmountMathSign'] = '>=';
		$testCases['1,0,1,1']['params']['lossAmountValue'] = '1';
		$testCases['1,0,1,1']['params']['operatorBeforeWin'] = 'and';
		$testCases['1,0,1,1']['params']['winAmountMathSign'] = '<=';
		$testCases['1,0,1,1']['params']['winAmountValue'] = '2';
		$testCases['1,0,1,1']['expect'] = '{"bet_amount":["<=",3],"operator_2":"or","loss_amount":[">=","1"],"operator_3":"and","win_amount":["<=","2"]}';

		$testCases['1,1,0,0'] = [];
		$testCases['1,1,0,0']['params']['betAmountMathSign'] = '<=';
		$testCases['1,1,0,0']['params']['betAmountValue'] = 2;
		$testCases['1,1,0,0']['params']['operatorBeforeDeposit'] = 'and';
		$testCases['1,1,0,0']['params']['depositAmountMathSign'] = '>=';
		$testCases['1,1,0,0']['params']['depositAmountValue'] = 1;
		$testCases['1,1,0,0']['params']['operatorBeforeLoss'] = null;
		$testCases['1,1,0,0']['params']['lossAmountMathSign'] = null;
		$testCases['1,1,0,0']['params']['lossAmountValue'] = null;
		$testCases['1,1,0,0']['params']['operatorBeforeWin'] = null;
		$testCases['1,1,0,0']['params']['winAmountMathSign'] = null;
		$testCases['1,1,0,0']['params']['winAmountValue'] = null;
		$testCases['1,1,0,0']['expect'] = '{"bet_amount":["<=",2],"operator_2":"and","deposit_amount":[">=",1]}';

		$testCases['1,1,0,1'] = [];
		$testCases['1,1,0,1']['params']['betAmountMathSign'] = '<=';
		$testCases['1,1,0,1']['params']['betAmountValue'] = 3;
		$testCases['1,1,0,1']['params']['operatorBeforeDeposit'] = 'or';
		$testCases['1,1,0,1']['params']['depositAmountMathSign'] = '>=';
		$testCases['1,1,0,1']['params']['depositAmountValue'] = 1;
		$testCases['1,1,0,1']['params']['operatorBeforeLoss'] = null;
		$testCases['1,1,0,1']['params']['lossAmountMathSign'] = null;
		$testCases['1,1,0,1']['params']['lossAmountValue'] = null;
		$testCases['1,1,0,1']['params']['operatorBeforeWin'] = 'and';
		$testCases['1,1,0,1']['params']['winAmountMathSign'] = '>=';
		$testCases['1,1,0,1']['params']['winAmountValue'] = 2;
		$testCases['1,1,0,1']['expect'] = '{"bet_amount":["<=",3],"operator_2":"or","deposit_amount":[">=",1],"operator_3":"and","win_amount":[">=",2]}';

		$testCases['1,1,1,0'] = [];
		$testCases['1,1,1,0']['params']['betAmountMathSign'] = '<=';
		$testCases['1,1,1,0']['params']['betAmountValue'] = 3;
		$testCases['1,1,1,0']['params']['operatorBeforeDeposit'] = 'or';
		$testCases['1,1,1,0']['params']['depositAmountMathSign'] = '>=';
		$testCases['1,1,1,0']['params']['depositAmountValue'] = 1;
		$testCases['1,1,1,0']['params']['operatorBeforeLoss'] = 'and';
		$testCases['1,1,1,0']['params']['lossAmountMathSign'] = '>=';
		$testCases['1,1,1,0']['params']['lossAmountValue'] = 2;
		$testCases['1,1,1,0']['params']['operatorBeforeWin'] = null;
		$testCases['1,1,1,0']['params']['winAmountMathSign'] = null;
		$testCases['1,1,1,0']['params']['winAmountValue'] = null;
		$testCases['1,1,1,0']['expect'] = '{"bet_amount":["<=",3],"operator_2":"or","deposit_amount":[">=",1],"operator_3":"and","loss_amount":[">=",2]}';

		$testCases['1,1,1,1'] = [];
		$testCases['1,1,1,1']['params']['betAmountMathSign'] = '<=';
		$testCases['1,1,1,1']['params']['betAmountValue'] = 4;
		$testCases['1,1,1,1']['params']['operatorBeforeDeposit'] = 'or';
		$testCases['1,1,1,1']['params']['depositAmountMathSign'] = '>=';
		$testCases['1,1,1,1']['params']['depositAmountValue'] = 1;
		$testCases['1,1,1,1']['params']['operatorBeforeLoss'] = 'and';
		$testCases['1,1,1,1']['params']['lossAmountMathSign'] = '>=';
		$testCases['1,1,1,1']['params']['lossAmountValue'] = 2;
		$testCases['1,1,1,1']['params']['operatorBeforeWin'] = 'or';
		$testCases['1,1,1,1']['params']['winAmountMathSign'] = '<=';
		$testCases['1,1,1,1']['params']['winAmountValue'] = 3;
		$testCases['1,1,1,1']['expect'] = '{"bet_amount":["<=",4],"operator_2":"or","deposit_amount":[">=",1],"operator_3":"and","loss_amount":[">=",2],"operator_4":"or","win_amount":["<=",3]}';

		foreach($testCases as $indexKey => $testInfo){

			$rlt = call_user_func_array([$this, '_prepareFormulaOfVipUpgradeSetting'], $testInfo['params']);

			$exported_testParams = var_export($testInfo['params'], true);
			$exported_rlt = var_export($rlt, true);
			$note = sprintf($this->noteTpl, $indexKey, $exported_testParams, $exported_rlt);
			$this->test( $rlt // result
				,  $testInfo['expect'] // expect
				, __METHOD__ // title
				, $note // note
			);
		} // EOF foreach($testCases as $indexKey => $testInfo){...

	} // EOF test_prepareFormulaOfVipUpgradeSetting


	/**
	 * [TESTED] URI,
	 * 升級應該要失敗，因為週期不對：http://admin.og.local/cli/testing_ogp24373/index/tryUpgradeSuccessInSASBTriggerFromCronjob/0/monthly/1/now/1/0/0/0
	 *
	 */
	public function tryUpgradeSuccessInSASBTriggerFromCronjob( $theTestPlayerInfo = [] // #1
																, $periodMode = 'monthly' // #2
																, $periodValue = 1 // #3
																, $TEB_DateTime = 'now' // #4
																, $testConditionFn = 0 // #5
																, $isMultipleUpgrade = 0 // #6
																, $isHourlyInSetting = 0 // #7
																, $separate_accumulation_settings = '{"bet_amount": {"accumulation": "1"}, "win_amount": {"accumulation": "4"}, "loss_amount": {"accumulation": "0"}, "deposit_amount": {"accumulation": "1"}}' // #8
	){
		$_this = $this;
		$settingName = 'devUpgradeMet.SASB';
		// for SACB setting
		$getUpgradeLevelSettingFn = function($_this) use ($settingName){
			/// step: preset VipUpgradeSetting in SACB for upgrade success
			// ref. to tryMacroSetupCACBInVipUpgradeSetting()
			// $settingName = $settingName4UpgradeMet;
			$data = [];
			$data['setting_name'] = $settingName;
			$data['description'] = $settingName. '.testing';
			$data['status'] = 1; // always be 1 for active.
			$data['level_upgrade'] = 1; // 1, 3 : upgrade, downgrade

			/// SB
			$betAmountMathSign = '<=';
			$betAmountValue = 4866;
			$operatorBeforeDeposit = 'and';
			$depositAmountMathSign = '<=';
			$depositAmountValue = 4869;
			$operatorBeforeLoss = 'or';
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

			// game_platform_and_type
			$params = [];
			$params['defaultValue'] = 0;
			$params['defaultMathSign'] = '>=';
			$gameKeyInfoList= [];
			$gameKeyInfoList['type'] = 'game_platform';
			$gameKeyInfoList['value'] = 0;
			$gameKeyInfoList['math_sign'] = '<=';
			$gameKeyInfoList['game_platform_id'] = '5674';// MUST BE STRING
			$params['GKAMSAVL'][] = $gameKeyInfoList;
			$gameKeyInfoList= [];
			$gameKeyInfoList['type'] = 'game_type';
			$gameKeyInfoList['value'] = 0;
			$gameKeyInfoList['math_sign'] = '>=';
			$gameKeyInfoList['game_type_id'] = '561'; // MUST BE STRING
			$gameKeyInfoList['precon_logic_flag'] = 'or';
			$params['GKAMSAVL'][] = $gameKeyInfoList;

			$defaultValue = $params['defaultValue'];
			$defaultMathSign = $params['defaultMathSign'];
			$gameKeysAndMathSignAndValueList = $params['GKAMSAVL'];
			$theBetAmountSettings = $this->_prepareBetAmountSettingsOfVipUpgradeSetting($defaultValue, $defaultMathSign, $gameKeysAndMathSignAndValueList);
			// $rlt = call_user_func_array([$this, '_prepareBetAmountSettingsOfVipUpgradeSetting'], $testInfo['params']);
			$data['bet_amount_settings'] = $theBetAmountSettings; // always be NULL


			/// SA
			$data['accumulation'] = 0; // 0 / 1 / 4 : No / Yes, Registration Date / Yes, Last Change Period
			$data['separate_accumulation_settings'] = $separate_accumulation_settings; // '{"bet_amount": {"accumulation": "1"}, "win_amount": {"accumulation": "4"}, "loss_amount": {"accumulation": "0"}, "deposit_amount": {"accumulation": "1"}}'; // always be NULL

			$params = [$settingName, $data];
			$rlt = call_user_func_array([$_this, '_syncUpgradeLevelSettingByName'], $params); // $rlt = $this->_syncUpgradeLevelSettingByName($settingName, $data);

			return [$rlt, $settingName];
		}; // function($settingName, $_this){...

		$params = [$theTestPlayerInfo, $periodMode, $periodValue, $TEB_DateTime, $testConditionFn, $isMultipleUpgrade, $isHourlyInSetting];
		// $rlt = call_user_func_array([$this, '_tryUpgradeSuccessTriggerFromCronjob'], $params); // $rlt = $this->_tryUpgradeSuccessTriggerFromCronjob($theTestPlayerInfo,...
		$params[] = $getUpgradeLevelSettingFn;
		$params[] = 1; // isSA = true mean SA
		$params[] = 1; // isSB = true mean SB
		$rlt = call_user_func_array([$this, '_tryUpgradeSuccessTriggerFromCronjobV2'], $params);
	} // EOF tryUpgradeSuccessInSASBTriggerFromCronjob

	/**
	 * [TESTED] URI,
	 * 升級應該要失敗，因為週期不對：http://admin.og.local/cli/testing_ogp24373/index/tryUpgradeSuccessInCASBTriggerFromCronjob/0/monthly/1/now/1/0/0/0
	 *
	 */
	public function tryUpgradeSuccessInCASBTriggerFromCronjob(	$theTestPlayerInfo = []
																, $periodMode = 'monthly'
																, $periodValue = 1
																, $TEB_DateTime = 'now'
																, $testConditionFn = 0
																, $isMultipleUpgrade = 0
																, $isHourlyInSetting = 0
																, $accumulation = 1
	){
		$_this = $this;
		$settingName = 'devUpgradeMet.CASB';
		// for SACB setting
		$getUpgradeLevelSettingFn = function($_this) use ($settingName){
			/// step: preset VipUpgradeSetting in SACB for upgrade success
			// ref. to tryMacroSetupCACBInVipUpgradeSetting()
			// $settingName = $settingName4UpgradeMet;
			$data = [];
			$data['setting_name'] = $settingName;
			$data['description'] = $settingName. '.testing';
			$data['status'] = 1; // always be 1 for active.
			$data['level_upgrade'] = 1; // 1, 3 : upgrade, downgrade

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

			// game_platform_and_type
			$params = [];
			$params['defaultValue'] = 0;
			$params['defaultMathSign'] = '>=';
			$gameKeyInfoList= [];
			$gameKeyInfoList['type'] = 'game_platform';
			$gameKeyInfoList['value'] = 0;
			$gameKeyInfoList['math_sign'] = '<=';
			$gameKeyInfoList['game_platform_id'] = "5674";// MUST BE STRING
			$params['GKAMSAVL'][] = $gameKeyInfoList;
			$gameKeyInfoList= [];
			$gameKeyInfoList['type'] = 'game_type';
			$gameKeyInfoList['value'] = 0;
			$gameKeyInfoList['math_sign'] = '<=';
			$gameKeyInfoList['game_type_id'] = "561";// MUST BE STRING
			$gameKeyInfoList['precon_logic_flag'] = 'or';
			$params['GKAMSAVL'][] = $gameKeyInfoList;

			$defaultValue = $params['defaultValue'];
			$defaultMathSign = $params['defaultMathSign'];
			$gameKeysAndMathSignAndValueList = $params['GKAMSAVL'];
			$theBetAmountSettings = $this->_prepareBetAmountSettingsOfVipUpgradeSetting($defaultValue, $defaultMathSign, $gameKeysAndMathSignAndValueList);
			// $rlt = call_user_func_array([$this, '_prepareBetAmountSettingsOfVipUpgradeSetting'], $testInfo['params']);
			$data['bet_amount_settings'] = $theBetAmountSettings; // always be NULL


			/// CA
			$data['accumulation'] = $accumulation; //assign by param. // 0 / 1 / 4 : No / Yes, Registration Date / Yes, Last Change Period
			$data['separate_accumulation_settings'] = NULL; // always be NULL

			$params = [$settingName, $data];
			$rlt = call_user_func_array([$_this, '_syncUpgradeLevelSettingByName'], $params); // $rlt = $this->_syncUpgradeLevelSettingByName($settingName, $data);

			return [$rlt, $settingName];
		}; // function($settingName, $_this){...


		$params = [$theTestPlayerInfo, $periodMode, $periodValue, $TEB_DateTime, $testConditionFn, $isMultipleUpgrade, $isHourlyInSetting];
		// $rlt = call_user_func_array([$this, '_tryUpgradeSuccessTriggerFromCronjob'], $params); // $rlt = $this->_tryUpgradeSuccessTriggerFromCronjob($theTestPlayerInfo,...
		$params[] = $getUpgradeLevelSettingFn;
		$params[] = 0; // isSA = false mean CA
		$params[] = 1; // isSB = true mean SB
		$rlt = call_user_func_array([$this, '_tryUpgradeSuccessTriggerFromCronjobV2'], $params); // $rlt = $this->_tryUpgradeSuccessTriggerFromCronjob($theTestPlayerInfo,...
	} // EOF tryUpgradeSuccessInCASBTriggerFromCronjob

	/**
	 * tryUpgradeSuccessInSACBTriggerFromCronjob
	 *
	 * [TESTED] URI,
	 * 升級應該要失敗，因為週期不對：http://admin.og.local/cli/testing_ogp24373/index/tryUpgradeSuccessInSACBTriggerFromCronjob/0/monthly/1/now/1/0/0/0
	 *
	 * @param array $theTestPlayerInfo
	 * @param string $periodMode
	 * @param integer $periodValue
	 * @param string $TEB_DateTime
	 * @param integer $testConditionFn
	 * @param integer $isMultipleUpgrade
	 * @param integer $isHourlyInSetting
	 * @param string $separate_accumulation_settings
	 * @return void
	 */
	public function tryUpgradeSuccessInSACBTriggerFromCronjob( $theTestPlayerInfo = [] // #1
																, $periodMode = 'monthly' // #2
																, $periodValue = 1 // #3
																, $TEB_DateTime = 'now' // #4
																, $testConditionFn = 0 // #5
																, $isMultipleUpgrade = 0 // #6
																, $isHourlyInSetting = 0 // #7
																, $separate_accumulation_settings = '{"bet_amount": {"accumulation": "1"}, "win_amount": {"accumulation": "4"}, "loss_amount": {"accumulation": "0"}, "deposit_amount": {"accumulation": "1"}}' // #8
	){
		$_this = $this;
		$settingName = 'devUpgradeMet.SACB';
		// for SACB setting
		$getUpgradeLevelSettingFn = function($_this) use ($settingName){
			/// step: preset VipUpgradeSetting in SACB for upgrade success
			// ref. to tryMacroSetupCACBInVipUpgradeSetting()
			// $settingName = $settingName4UpgradeMet;
			$data = [];
			$data['setting_name'] = $settingName;
			$data['description'] = $settingName. '.testing';
			$data['status'] = 1; // always be 1 for active.
			$data['level_upgrade'] = 1; // 1, 3 : upgrade, downgrade

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
			$formula = $_this->_prepareFormulaOfVipUpgradeSetting($betAmountMathSign
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
			$data['separate_accumulation_settings'] = $separate_accumulation_settings ; // assisn by param, '{"bet_amount": {"accumulation": "1"}, "win_amount": {"accumulation": "4"}, "loss_amount": {"accumulation": "0"}, "deposit_amount": {"accumulation": "1"}}'; // always be NULL

			$params = [$settingName, $data];
			$rlt = call_user_func_array([$_this, '_syncUpgradeLevelSettingByName'], $params); // $rlt = $this->_syncUpgradeLevelSettingByName($settingName, $data);

			return [$rlt, $settingName];
		}; // function($settingName, $_this){...


		$params = [$theTestPlayerInfo, $periodMode, $periodValue, $TEB_DateTime, $testConditionFn, $isMultipleUpgrade, $isHourlyInSetting];
		// $rlt = call_user_func_array([$this, '_tryUpgradeSuccessTriggerFromCronjob'], $params); // $rlt = $this->_tryUpgradeSuccessTriggerFromCronjob($theTestPlayerInfo,...
		$params[] = $getUpgradeLevelSettingFn;
		$params[] = 1; // isSA = true mean SA
		$params[] = 0; // isSB = false mean CB
		$rlt = call_user_func_array([$this, '_tryUpgradeSuccessTriggerFromCronjobV2'], $params); // $rlt = $this->_tryUpgradeSuccessTriggerFromCronjob($theTestPlayerInfo,...
	} // EOF tryUpgradeSuccessInSACBTriggerFromCronjob


	/**
	 * Upgrade Success Check(by settings) In CACB Trigger From Cronjob.
	 * If Upgrade failed, please check the period settings.
	 * [TESTED] URI,
	 * 升級應該要失敗，因為週期不對：http://admin.og.local/cli/testing_ogp24373/index/tryUpgradeSuccessInCACBTriggerFromCronjob/0/monthly/1/now/1
	 * 非連續，單一升級應該要成功：
	 * http://admin.og.local/cli/testing_ogp24373/index/tryUpgradeSuccessInCACBTriggerFromCronjob/0/monthly/29/now/0
	 * 連續升級應該要成功：（連升兩級）
	 * http://admin.og.local/cli/testing_ogp24373/index/tryUpgradeSuccessInCACBTriggerFromCronjob/0/monthly/29/now/0/1/0
	 * 連續升級應該要失敗：（連升兩級，因為 Hourly 限制 非 Hourly 的 cronjob）@todo 但該測試升級成功
	 * http://admin.og.local/cli/testing_ogp24373/index/tryUpgradeSuccessInCACBTriggerFromCronjob/0/monthly/29/now/1/1/1
	 *
	 * @param array $theTestPlayerInfo If empty array will get the first one element of _searchTestPlayerList()'s return.
	 * @param string $periodMode example list: daily, weekly and monthly.
	 * @param integer $periodValue 1,2,3,... 31 while $periodMode= "monthly".
	 * @param string|DateTime $TEB_DateTime It will be the param of new DateTime() while the param is string type.And Recommand apply from date_create_from_format("Y-m-d H:i:s", "2021-03-29 12:11:33");
	 * @param null|integer|function $testConditionFn The test Condition function, return true for pass while the vipsettingcashbackruleId is difference at Before and After.
	 * If applied in "1" will return true while the vipsettingcashbackruleId is same at Before and After.
	 * Or other param, function type for check vipsettingcashbackruleId at Before and After.
	 * @return void
	 */
	public function tryUpgradeSuccessInCACBTriggerFromCronjob(	$theTestPlayerInfo = []
																, $periodMode = 'monthly'
																, $periodValue = 1
																, $TEB_DateTime = 'now'
																, $testConditionFn = 0
																, $isMultipleUpgrade = 0
																, $isHourlyInSetting = 0
	){

		$params = [	$theTestPlayerInfo // #1
					, $periodMode // #2
					, $periodValue // #3
					, $TEB_DateTime // #4
					, $testConditionFn // #5
					, $isMultipleUpgrade // #6
					, $isHourlyInSetting // #7
				];
		// $rlt = call_user_func_array([$this, '_tryUpgradeSuccessTriggerFromCronjob'], $params); // $rlt = $this->_tryUpgradeSuccessTriggerFromCronjob($theTestPlayerInfo,...
		$params[] = 0; // for getUpgradeLevelSettingFn // #8
		$params[] = 0; // isSA = false mean CA // #9
		$params[] = 0; // isSB = false mean CB // #10
		$rlt = call_user_func_array([$this, '_tryUpgradeSuccessTriggerFromCronjobV2'], $params); // $rlt = $this->_tryUpgradeSuccessTriggerFromCronjob($theTestPlayerInfo,...
	}



	public function _tryUpgradeSuccessTriggerFromCronjob($theTestPlayerInfo = [], $periodMode = 'monthly', $periodValue = 1, $TEB_DateTime = 'now', $testConditionFn = 0, $isMultipleUpgrade = 0, $isHourlyInSetting = 0){
		$this->init();

		if( is_string($TEB_DateTime) ){
			//  string convert to DateTime
			$TEB_DateTime = new DateTime($TEB_DateTime);
		}
		// $TEB_DateTime = new DateTime(); // TEB = time_exec_begin // date_create_from_format("Y-m-d H:i:s", "2021-03-29 12:11:33");
		$time_exec_begin = $this->utils->formatDateTimeForMysql($TEB_DateTime);
		// $periodMode = 'monthly';
		// $periodValue = $TEB_DateTime->format('d');

		if(empty($theTestPlayerInfo) ){
			$offsetDayRange = '180';
			$limit = 5;
			$params = [$offsetDayRange, $limit];
			$rows = call_user_func_array([$this->utils4testogp, '_searchTestPlayerList'], $params); // $rows = $this->utils4testogp->_searchTestPlayerList($offsetDayRange, $limit);
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
			$note = sprintf($this->noteTpl, 'Assign the test player', '', var_export($theTestPlayerInfo, true));
			$this->test( !empty($theTestPlayerInfo) // result
					,  true // expect
					, __METHOD__ // title
					, $note // note
				);
		}

		$origId = null;
		if( ! empty( $theTestPlayerInfo ) ){
			$origId = $theTestPlayerInfo['vipsettingcashbackruleId'];

			/// devMacro 設定共同累計共同投注
			$isAccumulationSeparated = false; // for SA
			$isBettingSeparated = false; // for SB
			$rlt = $this->_preSetupAccumulationAndBettingTo($isAccumulationSeparated, $isBettingSeparated);

			// devMacro 啟用連續升級 feature
			$featureName = 'disable_player_multiple_upgrade';
			$value = 1;
			if($isMultipleUpgrade){
				$value = 0;
			}
			$rlt = $this->utils4testogp->_preSetupSystemFeatureTo($featureName, $value);
		}

		$settingName4UpgradeMet = 'devUpgradeMet.CACB';
		if( ! empty( $theTestPlayerInfo ) ){
			/// step: preset VipUpgradeSetting in CACB for upgrade success
			// ref. to tryMacroSetupCACBInVipUpgradeSetting()
			$settingName = $settingName4UpgradeMet;
			$data = [];
			$data['setting_name'] = $settingName;
			$data['description'] = $settingName. '.testing';
			$data['status'] = 1; // always be 1 for active.
			$data['level_upgrade'] = 1; // 1, 3 : upgrade, downgrade

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
		}

		if( ! empty( $theTestPlayerInfo ) ){
			/// step: load setting for update in player current level,  theSeparateAccumulationSettings
			$theVipUpgradeSetting = null;
			$theVipUpgradeSettingList = $this->_getVip_upgrade_settingListBySettingName($settingName);
			if( ! empty($theVipUpgradeSettingList) ){
				$theVipUpgradeSetting = $theVipUpgradeSettingList[0];
				// $theSeparateAccumulationSettings = $this->utils->json_decode_handleErr($theVipUpgradeSetting['separate_accumulation_settings'], true);
				// $theFormula = $this->utils->json_decode_handleErr($theVipUpgradeSetting['formula'], true);
			}
			$note = sprintf($this->noteTpl, '[Step] load setting for update in player current level,  theSeparateAccumulationSettings', var_export($settingName, true), var_export($theVipUpgradeSetting, true) );
			$this->test( ! empty($theVipUpgradeSetting) // result
				,  true // expect
				, __METHOD__. ' '. 'Load setting for update' // title
				, $note // note
			);
		}

		$thePlayerCurrentLevel = null;
		$currentvipsettingcashbackruleId = null;
		$nextVipsettingcashbackruleId = null;
		if( ! empty($theTestPlayerInfo) ){
			// load the player current level.
			// ref. to _updateUpgradeIdInVipsettingcashbackrule()
			$thePlayerId = $theTestPlayerInfo['player_id'];
			$thePlayerCurrentLevel = $this->player->getPlayerCurrentLevel($thePlayerId);
			$thePlayerCurrentLevel = $thePlayerCurrentLevel[0];
			$currentvipsettingcashbackruleId = $thePlayerCurrentLevel['vipsettingcashbackruleId'];

			$vipLevel = $thePlayerCurrentLevel['vipLevel'];
			$nextVipsettingcashbackruleId = $this->group_level->getVipLevelIdByLevelNumber($thePlayerCurrentLevel['vipSettingId'], $vipLevel+ 1);
		}

		if( ! empty( $thePlayerCurrentLevel ) ){
			if( ! empty($theVipUpgradeSetting) ){
				// update the setting into the player current level.
				// ref. to _updateUpgradeIdInVipsettingcashbackrule()
				$vipsettingcashbackruleId = $currentvipsettingcashbackruleId;
				$upgrade_id = $theVipUpgradeSetting['upgrade_id'];
				$targetField='vip_upgrade_id';
				$params = [$upgrade_id, $vipsettingcashbackruleId, $targetField];
				$rlt = call_user_func_array([$this, '_updateUpgradeIdInVipsettingcashbackrule'], $params); // $rlt = $this->_updateUpgradeIdInVipsettingcashbackrule($upgrade_id, $vipsettingcashbackruleId, $targetField);

				$note = sprintf($this->noteTpl, '[Step] hook setting into the player current level', var_export($params, true), var_export($rlt, true) );
				$this->test( true // ! empty($vipsettingcashbackruleId) // result
					,  true // expect
					, __METHOD__. ' '. 'Hook setting into the player current level' // title
					, $note // note
				);
			}
			if( ! empty($theVipUpgradeSetting) ){
				// Update the period on the player current level
				$gradeMode = 'upgrade';
				$_periodMode = $periodMode;
				$_periodValue = $periodValue;
				$isHourly = false;
				if($isHourlyInSetting){
					$isHourly = true;
				}
				$extraData = [];

				// $theJson = $this->_getPeriodJson($_periodMode, $_periodValue, $isHourly, $extraData);
				$theJson = $this->utils4testogp->_getPeriodJson($_periodMode,$_periodValue, $isHourly, $extraData);

				$params = [$thePlayerId, $gradeMode, $theJson];
				$rlt = call_user_func_array([$this, '_preSetupPeriodInPlayerCurrentLevel'], $params);// $rlt = $this->_preSetupPeriodInPlayerCurrentLevel($thePlayerId, $gradeMode, $theJson);
				$note = sprintf($this->noteTpl, '[Step] Update the period on the player current level', var_export($params, true), var_export($rlt, true) );
				$this->test( true // ! empty($vipsettingcashbackruleId) // result
					,  true // expect
					, __METHOD__. ' '. 'Update the period on the player current level' // title
					, $note // note
				);
			} // EOF if( ! empty($theVipUpgradeSetting) ){...
		}

		if( $isMultipleUpgrade && ! empty($nextVipsettingcashbackruleId) ){
			if( ! empty($theVipUpgradeSetting) ){
				// update the setting into the player current level.
				// ref. to _updateUpgradeIdInVipsettingcashbackrule()
				$vipsettingcashbackruleId = $nextVipsettingcashbackruleId;
				$upgrade_id = $theVipUpgradeSetting['upgrade_id'];
				$targetField='vip_upgrade_id';
				$params = [$upgrade_id, $vipsettingcashbackruleId, $targetField];
				$rlt = call_user_func_array([$this, '_updateUpgradeIdInVipsettingcashbackrule'], $params); // $rlt = $this->_updateUpgradeIdInVipsettingcashbackrule($upgrade_id, $vipsettingcashbackruleId, $targetField);
			}

			if( ! empty($theVipUpgradeSetting) ){
				// Update the period on the player current level
				$gradeMode = 'upgrade';
				$_periodMode = $periodMode;
				$_periodValue = $periodValue;
				$isHourly = false;
				if($isHourlyInSetting){
					$isHourly = true;
				}
				$extraData = [];

				// $theJson = $this->_getPeriodJson($_periodMode, $_periodValue, $isHourly, $extraData);
				$theJson = $this->utils4testogp->_getPeriodJson($_periodMode,$_periodValue, $isHourly, $extraData);

				$params = [$nextVipsettingcashbackruleId, $gradeMode, $theJson];
				$rlt = call_user_func_array([$this, '_preSetupPeriodInVipSettingCashbackRuleId'], $params);// $rlt = $this->_preSetupPeriodInVipSettingCashbackRuleId($nextVipsettingcashbackruleId, $gradeMode, $theJson);
				$note = sprintf($this->noteTpl, '[Step] Update the period on the player Next level', var_export($params, true), var_export($rlt, true) );
				$this->test( true // ! empty($vipsettingcashbackruleId) // result
					,  true // expect
					, __METHOD__. ' '. 'Update the period on the player Next level' // title
					, $note // note
				);
			} // EOF if( ! empty($theVipUpgradeSetting) ){...
		}

		if( ! empty( $theTestPlayerInfo ) ){
			/// step: trigger upgrade check by cron/sbe

			// // ref. to triggerPlayer_Management_manuallyUpgradeLevel()
			// $result = $this->group_level->batchUpDownLevelUpgrade($player_id, false, false, null, $order_generated_by);
			// // batchUpDownLevelUpgrade($playerIds, $manual_batch = false, $check_hourly = false, $time_exec_begin = null, $order_generated_by = null) {

			$is_blocked = false;
			$params = [];
			$params[] = $theTestPlayerInfo['player_id']. '_1'; // $playerId = null
			$params[] = 0; // $manual_batch = true
			$params[] = $time_exec_begin; // $time_exec_begin = null
			$params[] = 0; // $check_hourly
			// $playerId = null, $manual_batch = true, $time_exec_begin = null, $check_hourly
			$func = 'player_level_upgrade_by_playerIdV2';
			$cmd = $this->utils->generateCommandLine($func, $params, $is_blocked);
			$return_var = $this->utils->runCmd($cmd);

			$note = sprintf($this->noteTpl, 'To trigger upgrade check by cron via command.', var_export($params,true), var_export($return_var,true) );
			$this->test( true // result
				,  true // expect
				, __METHOD__ // title
				, $note // note
			);



		}

		if( ! empty( $theTestPlayerInfo ) ){



			$idleTotalSec = 4; // use isOverWaitingTimeWithWaitingByPS() for detect ps BUT Not work.
			$this->utils->debug_log('5118.bbb.idleTotalSec',$this->oghome);
			$this->utils->idleSec($idleTotalSec);
			$this->utils->debug_log('5120.ccc.idleTotalSec',$this->oghome);
			$isExecingCB = null;
			$funcList = [];
			$funcList[] = $func;
			$maxWaitingTimes = 30;
			$waitingSec = 1;
			$isOverWaitingTime = $this->utils->isOverWaitingTimeWithWaitingByPS($funcList, $isExecingCB, $maxWaitingTimes, $waitingSec. $this->oghome);
			if ( ! $isOverWaitingTime ) {
			}
			$this->utils->debug_log('5129.aaa.idleTotalSec',$this->oghome);

			$reloadPlayerLevel = $this->player->getPlayerCurrentLevel($theTestPlayerInfo['player_id']);
			$afterId = $reloadPlayerLevel[0]['vipsettingcashbackruleId'];

			if( empty($testConditionFn) ){ // 0
				$testConditionFn = function($origId, $afterId) {
					return $origId != $afterId;
				};
			}else if( $testConditionFn == '1' ){ // for URI
				$testConditionFn = function($origId, $afterId) {
					return $origId == $afterId;
				};
			}else{
				// or $testConditionFn function
			}

			$rlt = $testConditionFn($origId, $afterId);
			$note = sprintf($this->noteTpl, 'Compare Player Level: Before(params) / After(rlt).', var_export($origId,true), var_export($afterId,true) );
			$this->test( $rlt // result
				,  true // expect
				, __METHOD__ // title
				, $note // note
			);

		}


		//[] tryMacroSetupCACBInVipUpgradeSetting
		//[] step: preset VipUpgradeSetting in CACB for upgrade success
		//[] step: load setting for update in player current level,  theSeparateAccumulationSettings
		// step: trigger upgrade check by cron/sbe

	} // EOF tryUpgradeSuccessInCACBTriggerFromCronjob


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
			$formula = $_this->_prepareFormulaOfVipUpgradeSetting($betAmountMathSign
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
			$data['bet_amount_settings'] = $bet_amount_settings; // always be NULL for CB

			/// CA
			$data['accumulation'] = $accumulation ; // 0 / 1 / 4 : No / Yes, Registration Date / Yes, Last Change Period
			$data['separate_accumulation_settings'] = $separate_accumulation_settings; // always be NULL

			$params = [$settingName, $data];
			$rlt = call_user_func_array([$_this, '_syncUpgradeLevelSettingByName'], $params); // $rlt = $this->_syncUpgradeLevelSettingByName($settingName, $data);

			$note = sprintf($_this->noteTpl, '[Step]preset VipUpgradeSetting in CACB for upgrade success', var_export($params, true), var_export($rlt, true) );
			$this->test( true // result
				,  true // expect
				, __METHOD__. ' '. 'Preset vip_upgrade_setting table' // title
				, $note // note
			);

			return [$rlt, $settingName];
		}; // function($settingName, $_this){...
		return $getUpgradeLevelSettingFn;
	} // EOF getUpgradeLevelSettingFnWithParams

	// Ref. by 21051
	public function _tryUpgradeSuccessTriggerFromCronjobV2($theTestPlayerInfo = [] // # 1
		, $periodMode = 'monthly' // # 2
		, $nextLevelUpPeriodMode = 'monthly' // # 2.1
		, $periodValue = 1 // # 3
		, $nextLevelUpPeriodValue = 1 // # 3.1
		, $TEB_DateTime = 'now' // # 4
		, $testConditionFn = 0 // # 5 @todo is_string for function name
		, $isMultipleUpgrade = 0 // # 6
		, $isHourlyInSetting = 0 // # 7
		, $getUpgradeLevelSettingFn = 0  // # 8 // function for _syncUpgradeLevelSettingByName
		, $isAccumulationSeparatedInConfig = 0  // # 9 // CA
		, $isBettingSeparatedInConfig = 0  // # 10 // CB
		, $isTriggerFromHourlyCronjob = false // #11 is Trigger From Hourly Cronjob
		, $manual_batch = false // #12 If false means batch, if true means manual.
		, $getNextLevelUpgradeLevelSettingFn = null // #13 // function for _syncUpgradeLevelSettingByName and like as #8 but its for nexe level
		, $tmpAnotherTestPlayerId = 1 // #14
	){
		$this->init();

		$gradeMode = 'upgrade';

		if( is_string($TEB_DateTime) ){
			//  string convert to DateTime
			$TEB_DateTime = new DateTime($TEB_DateTime);
		}
		// $TEB_DateTime = new DateTime(); // TEB = time_exec_begin // date_create_from_format("Y-m-d H:i:s", "2021-03-29 12:11:33");
		$time_exec_begin = $this->utils->formatDateTimeForMysql($TEB_DateTime);
		// $periodMode = 'monthly';
		// $periodValue = $TEB_DateTime->format('d');

		if(empty($theTestPlayerInfo) ){ // empty for search the test player.
			$offsetDayRange = '180';
			$limit = 5;
			$params = [$offsetDayRange, $limit];
			$rows = call_user_func_array([$this->utils4testogp, '_searchTestPlayerList'], $params); // $rows = $this->utils4testogp->_searchTestPlayerList($offsetDayRange, $limit);
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
			$note = sprintf($this->noteTpl, 'Assign the test player', '', var_export($theTestPlayerInfo, true));
			$this->test( !empty($theTestPlayerInfo) // result
					,  true // expect
					, __METHOD__ // title
					, $note // note
				);
		}

		$origId = null;
		if( ! empty( $theTestPlayerInfo ) ){
			$origId = $theTestPlayerInfo['vipsettingcashbackruleId'];  // for revert

			/// devMacro 設定共同累計共同投注
			$isAccumulationSeparated = false; // for SA
			if( ! empty( $isAccumulationSeparatedInConfig ) ){
				$isAccumulationSeparated = $isAccumulationSeparatedInConfig;
			}
			$isBettingSeparated = false; // for SB
			if( ! empty( $isBettingSeparatedInConfig) ){
				$isBettingSeparated = $isBettingSeparatedInConfig;
			}
			$rlt = $this->_preSetupAccumulationAndBettingTo($isAccumulationSeparated, $isBettingSeparated);

			// devMacro 啟用連續升級 feature
			$featureName = 'disable_player_multiple_upgrade';
			$value = 1;
			if($isMultipleUpgrade){
				$value = 0;
			}
			$rlt = $this->utils4testogp->_preSetupSystemFeatureTo($featureName, $value);
		}


		if( ! empty( $theTestPlayerInfo ) ){
			/// like as _getDowngradeLevelSettingFn()
			// if( empty($getUpgradeLevelSettingFn) ){
			// 	$getUpgradeLevelSettingFn = '_getDowngradeLevelSettingFn';
			// }

			if( empty($getUpgradeLevelSettingFn) ){
					var_dump(['debugIn6922.will getUpgradeLevelSettingFnWithParams']);
				$settingName = 'devUpgradeMet.CACB'; // #1
				$betAmountMathSign = '>='; // #2
				$betAmountValue = 0; // #3
				$operatorBeforeDeposit = 'and'; // #4
				$depositAmountMathSign = '>='; // #5
				$depositAmountValue = 0; // #6
				$operatorBeforeLoss = 'and'; // #7
				$lossAmountMathSign = '>='; // #8
				$lossAmountValue = 0; // #9
				$operatorBeforeWin = null; // #10
				$winAmountMathSign = null; // #11
				$winAmountValue = null; // #12
				$accumulation = 1; // #13
				$separate_accumulation_settings = NULL;
				$getUpgradeLevelSettingFn = $this->getUpgradeLevelSettingFnWithParams( $settingName // #1
																						, $betAmountMathSign // #2
																						, $betAmountValue // #3
																						, $operatorBeforeDeposit // #4
																						, $depositAmountMathSign // #5
																						, $depositAmountValue // #6
																						, $operatorBeforeLoss // #7
																						, $lossAmountMathSign // #8
																						, $lossAmountValue // #9
																						, $operatorBeforeWin // #10
																						, $winAmountMathSign // #11
																						, $winAmountValue// #12
																						, $accumulation // #13
																						, $separate_accumulation_settings // #14
																					);
				// $settingName = 'devUpgradeMet.CACB';
				// $getUpgradeLevelSettingFn = function( $_this ) use ($settingName){
				// 	/// step: preset VipUpgradeSetting in CACB for upgrade success
				// 	// ref. to tryMacroSetupCACBInVipUpgradeSetting()
				// 	// $settingName = $settingName4UpgradeMet;
				// 	$data = [];
				// 	$data['setting_name'] = $settingName;
				// 	$data['description'] = $settingName. '.testing';
				// 	$data['status'] = 1; // always be 1 for active.
				// 	$data['level_upgrade'] = 1; // 1, 3 : upgrade, downgrade
				//
				// 	/// CB
				// 	$betAmountMathSign = '>=';
				// 	$betAmountValue = 0;
				// 	$operatorBeforeDeposit = 'and';
				// 	$depositAmountMathSign = '>=';
				// 	$depositAmountValue = 0;
				// 	$operatorBeforeLoss = 'and';
				// 	$lossAmountMathSign = '>=';
				// 	$lossAmountValue = 0;
				// 	$operatorBeforeWin = null;
				// 	$winAmountMathSign = null;
				// 	$winAmountValue = null;
				// 	$formula = $_this->_prepareFormulaOfVipUpgradeSetting($betAmountMathSign
				// 		, $betAmountValue
				// 		, $operatorBeforeDeposit
				// 		, $depositAmountMathSign
				// 		, $depositAmountValue
				// 		, $operatorBeforeLoss
				// 		, $lossAmountMathSign
				// 		, $lossAmountValue
				// 		, $operatorBeforeWin
				// 		, $winAmountMathSign
				// 		, $winAmountValue );
				// 	$data['formula'] = $formula;
				// 	$data['bet_amount_settings'] = NULL; // always be NULL
				//
				// 	/// CA
				// 	$data['accumulation'] = 1; // 0 / 1 / 4 : No / Yes, Registration Date / Yes, Last Change Period
				// 	$data['separate_accumulation_settings'] = NULL; // always be NULL
				//
				// 	$params = [$settingName, $data];
				// 	$rlt = call_user_func_array([$_this, '_syncUpgradeLevelSettingByName'], $params); // $rlt = $this->_syncUpgradeLevelSettingByName($settingName, $data);
				//
				// 	$note = sprintf($_this->noteTpl, '[Step]preset VipUpgradeSetting in CACB for upgrade success', var_export($params, true), var_export($rlt, true) );
				// 	$this->test( true // result
				// 		,  true // expect
				// 		, __METHOD__. ' '. 'Preset vip_upgrade_setting table' // title
				// 		, $note // note
				// 	);
				//
				// 	return [$rlt, $settingName];
				// }; // function($settingName, $_this){...
			}
			list($rlt, $settingName) = $getUpgradeLevelSettingFn($this); // handle $rlt, $settingName


			if($getNextLevelUpgradeLevelSettingFn !== null){ // $getNextLevelUpgradeLevelSettingFn had assigned
				list($rlt4nextLevel, $settingName4nextLevel) = $getNextLevelUpgradeLevelSettingFn($this); // handle $rlt, $settingName
			}
		}

		if( ! empty( $theTestPlayerInfo ) ){
			/// step: load setting for update in player current level,  theSeparateAccumulationSettings
			$theVipUpgradeSetting = null;
			$theVipUpgradeSettingList = $this->_getVip_upgrade_settingListBySettingName($settingName);
			if( ! empty($theVipUpgradeSettingList) ){
				$theVipUpgradeSetting = $theVipUpgradeSettingList[0];
				// $theSeparateAccumulationSettings = $this->utils->json_decode_handleErr($theVipUpgradeSetting['separate_accumulation_settings'], true);
				// $theFormula = $this->utils->json_decode_handleErr($theVipUpgradeSetting['formula'], true);
			}
			$note = sprintf($this->noteTpl, '[Step] load setting for update in player current level,  theSeparateAccumulationSettings', var_export($settingName, true), var_export($theVipUpgradeSetting, true) );
			$this->test( ! empty($theVipUpgradeSetting) // result
				,  true // expect
				, __METHOD__. ' '. 'Load setting for update' // title
				, $note // note
			);

			if($getNextLevelUpgradeLevelSettingFn !== null){ // $getNextLevelUpgradeLevelSettingFn had assigned
				// optional by $getNextLevelUpgradeLevelSettingFn
				/// step: load setting for update in player NEXT level,  theSeparateAccumulationSettings
				$theNextLevelVipUpgradeSetting = null;
				$theNextLevelVipUpgradeSettingList = $this->_getVip_upgrade_settingListBySettingName($settingName4nextLevel);
				if( ! empty($theNextLevelVipUpgradeSettingList) ){
					$theNextLevelVipUpgradeSetting = $theNextLevelVipUpgradeSettingList[0];
				}
				$note = sprintf($this->noteTpl, '[Step] load setting for update in player NEXT level,  theSeparateAccumulationSettings', var_export($settingName4nextLevel, true), var_export($theNextLevelVipUpgradeSetting, true) );
				$this->test( ! empty($theNextLevelVipUpgradeSetting) // result
					,  true // expect
					, __METHOD__. ' '. 'Load setting for update' // title
					, $note // note
				);
			}

		}

		$thePlayerCurrentLevel = null;
		$currentVipsettingcashbackruleId = null;
		$nextVipsettingcashbackruleId = null;
		if( ! empty($theTestPlayerInfo) ){
			// load the player current level.
			// ref. to _updateUpgradeIdInVipsettingcashbackrule()
			$thePlayerId = $theTestPlayerInfo['player_id'];
			$thePlayerCurrentLevel = $this->player->getPlayerCurrentLevel($thePlayerId);
			$thePlayerCurrentLevel = $thePlayerCurrentLevel[0];
			$currentVipsettingcashbackruleId = $thePlayerCurrentLevel['vipsettingcashbackruleId'];

			$vipLevel = $thePlayerCurrentLevel['vipLevel'];
			$nextVipsettingcashbackruleId = $this->group_level->getVipLevelIdByLevelNumber($thePlayerCurrentLevel['vipSettingId'], $vipLevel+ 1);
		}

		if( ! empty( $thePlayerCurrentLevel ) ){
			if( ! empty($theVipUpgradeSetting) ){
				// update the setting into the player current level.
				// ref. to _updateUpgradeIdInVipsettingcashbackrule()
				$vipsettingcashbackruleId = $currentVipsettingcashbackruleId;
				$upgrade_id = $theVipUpgradeSetting['upgrade_id'];
				$targetField='vip_upgrade_id';
				$params = [$upgrade_id, $vipsettingcashbackruleId, $targetField];
				$rlt = call_user_func_array([$this, '_updateUpgradeIdInVipsettingcashbackrule'], $params); // $rlt = $this->_updateUpgradeIdInVipsettingcashbackrule($upgrade_id, $vipsettingcashbackruleId, $targetField);

				$note = sprintf($this->noteTpl, '[Step] hook setting into the player current level', var_export($params, true), var_export($rlt, true) );
				$this->test( true // ! empty($vipsettingcashbackruleId) // result
					,  true // expect
					, __METHOD__. ' '. 'Hook setting into the player current level' // title
					, $note // note
				);
			}

			if( ! empty($theVipUpgradeSetting) ){
				// Update the period on the player current level
				// $gradeMode = 'upgrade'; // moved to up.
				$_periodMode = $periodMode;
				$_periodValue = $periodValue;
				$isHourly = false;
				if($isHourlyInSetting){
					$isHourly = true;
				}
				$extraData = [];
				$theJson = $this->_getPeriodJson($_periodMode, $_periodValue, $isHourly, $extraData);
				$params = [$thePlayerId, $gradeMode, $theJson];
				$rlt = call_user_func_array([$this, '_preSetupPeriodInPlayerCurrentLevel'], $params);// $rlt = $this->_preSetupPeriodInPlayerCurrentLevel($thePlayerId, $gradeMode, $theJson);
				var_dump(['debugIn7187._preSetupPeriodInPlayerCurrentLevel.params', $params, '_periodMode:', $_periodMode, '_periodValue:', $_periodValue]);
				$note = sprintf($this->noteTpl, '[Step] Update the period on the player current level', var_export($params, true), var_export($rlt, true) );
				$this->test( true // ! empty($vipsettingcashbackruleId) // result
					,  true // expect
					, __METHOD__. ' '. 'Update the period on the player current level' // title
					, $note // note
				);
			} // EOF if( ! empty($theVipUpgradeSetting) ){...
		}

		/// 下一個等級的設定
		if( $isMultipleUpgrade && ! empty($nextVipsettingcashbackruleId) ){
			if( ! empty($theNextLevelVipUpgradeSetting) ){
				// update the setting F.K. into the player current level.
				// ref. to _updateUpgradeIdInVipsettingcashbackrule()
				$vipsettingcashbackruleId = $nextVipsettingcashbackruleId;
				$upgrade_id = $theNextLevelVipUpgradeSetting['upgrade_id']; // for formula, bet_amount_settings, accumulation and separate_accumulation_settings
				$targetField='vip_upgrade_id';
				$params = [$upgrade_id, $vipsettingcashbackruleId, $targetField];
				$rlt = call_user_func_array([$this, '_updateUpgradeIdInVipsettingcashbackrule'], $params); // $rlt = $this->_updateUpgradeIdInVipsettingcashbackrule($upgrade_id, $vipsettingcashbackruleId, $targetField);
			}

			if( ! empty($theVipUpgradeSetting) ){
				// Update the period on the player next upgrade level
				// for nextLevelUpPeriodMode, nextLevelUpPeriodValue
				// $gradeMode = 'upgrade'; // moved to up
				$_periodMode = $nextLevelUpPeriodMode;
				$_periodValue = $nextLevelUpPeriodValue;
				$isHourly = false;
				if($isHourlyInSetting){
					$isHourly = true;
				}
				$extraData = [];
				$theJson = $this->_getPeriodJson($_periodMode, $_periodValue, $isHourly, $extraData);
				$params = [$nextVipsettingcashbackruleId, $gradeMode, $theJson];
				$rlt = call_user_func_array([$this, '_preSetupPeriodInVipSettingCashbackRuleId'], $params);// $rlt = $this->_preSetupPeriodInVipSettingCashbackRuleId($nextVipsettingcashbackruleId, $gradeMode, $theJson);
				$note = sprintf($this->noteTpl, '[Step] Update the period on the player Next level', var_export($params, true), var_export($rlt, true) );
				$this->test( true // ! empty($vipsettingcashbackruleId) // result
					,  true // expect
					, __METHOD__. ' '. 'Update the period on the player Next level' // title
					, $note // note
				);
			} // EOF if( ! empty($theVipUpgradeSetting) ){...
		}

		if( ! empty( $theTestPlayerInfo ) ){
			/// step: trigger upgrade check by cron/sbe

			// // ref. to triggerPlayer_Management_manuallyUpgradeLevel()
			// $result = $this->group_level->batchUpDownLevelUpgrade($player_id, false, false, null, $order_generated_by);
			// // batchUpDownLevelUpgrade($playerIds, $manual_batch = false, $check_hourly = false, $time_exec_begin = null, $order_generated_by = null) {

			$is_blocked = false;
			$params = [];
			$params[] = $theTestPlayerInfo['player_id']. '_'. $tmpAnotherTestPlayerId; // $playerId = null,  splite string="_", and playerId=1 is tmp.
			$params[] = $manual_batch; // $manual_batch = true
			$params[] = $time_exec_begin; // $time_exec_begin = null
			$params[] = $isTriggerFromHourlyCronjob; // $check_hourly
			// $playerId = null, $manual_batch = true, $time_exec_begin = null, $check_hourly
			// $func = 'player_level_upgrade_by_playerIdV2'; // player_level_upgrade_by_playerIdV2 same as player_level_upgrade_by_playerId
			$func = 'player_level_upgrade_by_playerId';
			$cmd = $this->utils->generateCommandLine($func, $params, $is_blocked);
			$return_var = $this->utils->runCmd($cmd);

			$note = sprintf($this->noteTpl, 'To trigger upgrade check by cron via command.', var_export($params,true), var_export($return_var,true) );
			$this->test( true // result
				,  true // expect
				, __METHOD__ // title
				, $note // note
			);

		}

		if( ! empty( $theTestPlayerInfo ) ){

			$idleTotalSec = 4; // use isOverWaitingTimeWithWaitingByPS() for detect ps BUT Not work.
			$this->utils->debug_log('bbb.idleTotalSec',$this->oghome);
			$this->utils->idleSec($idleTotalSec);
			$this->utils->debug_log('ccc.idleTotalSec',$this->oghome);
			$isExecingCB = null;
			$funcList = [];
			$funcList[] = $func;
			$maxWaitingTimes = 30;
			$waitingSec = 1;
			$isOverWaitingTime = $this->utils->isOverWaitingTimeWithWaitingByPS($funcList, $isExecingCB, $maxWaitingTimes, $waitingSec. $this->oghome);
			if ( ! $isOverWaitingTime ) {
			}
			$this->utils->debug_log('aaa.idleTotalSec',$this->oghome);

			$idleTotalSec = 1;
			$this->utils->idleSec($idleTotalSec);

			$reloadPlayerLevel = $this->player->getPlayerCurrentLevel($theTestPlayerInfo['player_id']);
			$afterId = $reloadPlayerLevel[0]['vipsettingcashbackruleId'];

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
			){

			}else{
				// or $testConditionFn function
			}

			if( gettype($testConditionFn) == 'string' ){  // Specified call local function
				$params = [$origId, $afterId];
// var_dump(['debugIn6780.params:', $params]);
				// list($rlt, $noteTpl) = call_user_func_array([$this, $testConditionFn], $params);// $rlt = $this->_testConditionFn4beforeDiffAfterV2(...
				list($rlt, $noteTpl) = call_user_func_array([$this->utils4testogp, $testConditionFn], $params);// $rlt = $this->_testConditionFn4beforeDiffAfterV2(...

				$this->utils->debug_log('will call user func', $testConditionFn, 'params:', $params, 'rlt:', $rlt);
			}else if( gettype($testConditionFn) == 'object' ){ // Custom function
// var_dump(['debugIn6786.params:', $origId, $afterId]);
				list($rlt, $noteTpl) = $testConditionFn($origId, $afterId);
// var_dump(['debugIn6786.rlt:', $rlt]);
				$this->utils->debug_log('will call Closure func. params:', $params, 'rlt:', $rlt);
			}

			/// cloned form "testing_ogp21051.php".
			// $aaabbbccc = $this->utils->generateCallTrace();
			// $this->utils->debug_log('4303.aaabbbccc',$aaabbbccc);

			// $rlt = $testConditionFn($origId, $afterId);

			$theGenerateCallTrace = '';
			if($rlt !== true){
				$theGenerateCallTrace .= ' ';
				$theGenerateCallTrace .= $this->utils->generateCallTrace();
			}
			$note = sprintf($this->noteTpl, 'Compare Player Level: Before(params) / After(rlt).', var_export($origId,true), var_export($afterId,true). $theGenerateCallTrace );
			$this->test( $rlt // result
				,  true // expect
				, __METHOD__ // title
				, $note // note
			);

		}


		//[] tryMacroSetupCACBInVipUpgradeSetting
		//[] step: preset VipUpgradeSetting in CACB for upgrade success
		//[] step: load setting for update in player current level,  theSeparateAccumulationSettings
		// step: trigger upgrade check by cron/sbe

	} // EOF _tryUpgradeSuccessTriggerFromCronjobV2

	// Ref. by 21673
	public function ogp21673_tryUpgradeSuccessTriggerFromCronjobV2($theTestPlayerInfo = [] // # 1
		, $periodMode = 'monthly' // # 2
		, $periodValue = 1 // # 3
		, $TEB_DateTime = 'now' // # 4
		, $testConditionFn = 0 // # 5 @todo is_string for function name
		, $isMultipleUpgrade = 0 // # 6
		, $isHourlyInSetting = 0 // # 7
		, $getUpgradeLevelSettingFn = 0  // # 8 // function for _syncUpgradeLevelSettingByName
		, $isAccumulationSeparatedInConfig = 0  // # 9 // CA
		, $isBettingSeparatedInConfig = 0  // # 10 // CB
	){
		$this->init();


        $this->utils->debug_log('5178.in_tryUpgradeSuccessTriggerFromCronjobV2.TEB_DateTime:', $TEB_DateTime, 'generateCallTrace:', $this->utils->generateCallTrace());
		if( is_string($TEB_DateTime) ){
			//  string convert to DateTime
			$TEB_DateTime = new DateTime($TEB_DateTime);
		}

		// $TEB_DateTime = new DateTime(); // TEB = time_exec_begin // date_create_from_format("Y-m-d H:i:s", "2021-03-29 12:11:33");
		$time_exec_begin = $this->utils->formatDateTimeForMysql($TEB_DateTime);
		// $periodMode = 'monthly';
		// $periodValue = $TEB_DateTime->format('d');
        $this->utils->debug_log('5183.in_tryUpgradeSuccessTriggerFromCronjobV2.time_exec_begin:', $time_exec_begin);
		if(empty($theTestPlayerInfo) ){
			$offsetDayRange = '180';
			$limit = 5;
			$params = [$offsetDayRange, $limit];
			$rows = call_user_func_array([$this->utils4testogp, '_searchTestPlayerList'], $params); // $rows = $this->utils4testogp->_searchTestPlayerList($offsetDayRange, $limit);
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
				$theTestPlayerInfo = $rows[0]; // for $rows[3] player_id=5357
			}
			$counter = count($rows);
			$note = sprintf($this->noteTpl, 'To get the Test player List ', var_export($rows, true), var_export($theTestPlayerInfo, true));
			$this->test( !empty($counter) // result
					,  true // expect
					, __METHOD__ // title
					, $note // note
				);
		}else{
			$note = sprintf($this->noteTpl, 'Assign the test player', '', var_export($theTestPlayerInfo, true));
			$this->test( !empty($theTestPlayerInfo) // result
					,  true // expect
					, __METHOD__ // title
					, $note // note
				);
		}

		$origId = null;
		if( ! empty( $theTestPlayerInfo ) ){
			$origId = $theTestPlayerInfo['vipsettingcashbackruleId'];  // for revert

			/// devMacro 設定共同累計共同投注
			$isAccumulationSeparated = false; // for SA
			if( ! empty( $isAccumulationSeparatedInConfig ) ){
				$isAccumulationSeparated = $isAccumulationSeparatedInConfig;
			}
			$isBettingSeparated = false; // for SB
			if( ! empty( $isBettingSeparatedInConfig) ){
				$isBettingSeparated = $isBettingSeparatedInConfig;
			}
			$rlt = $this->_preSetupAccumulationAndBettingTo($isAccumulationSeparated, $isBettingSeparated);

			// devMacro 啟用連續升級 feature
			$featureName = 'disable_player_multiple_upgrade';
			$value = 1;
			if($isMultipleUpgrade){
				$value = 0;
			}
			$rlt = $this->utils4testogp->_preSetupSystemFeatureTo($featureName, $value);
		}


		if( ! empty( $theTestPlayerInfo ) ){
			if( empty($getUpgradeLevelSettingFn) ){
                // for default setting
				$settingName = 'devUpgradeMet.CACB';
				$getUpgradeLevelSettingFn = function( $_this ) use ($settingName){
					/// step: preset VipUpgradeSetting in CACB for upgrade success
					// ref. to tryMacroSetupCACBInVipUpgradeSetting()
					// $settingName = $settingName4UpgradeMet;
					$data = [];
					$data['setting_name'] = $settingName;
					$data['description'] = $settingName. '.testing';
					$data['status'] = 1; // always be 1 for active.
					$data['level_upgrade'] = 1; // 1, 3 : upgrade, downgrade

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
					$formula = $_this->_prepareFormulaOfVipUpgradeSetting($betAmountMathSign
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
					$rlt = call_user_func_array([$_this, '_syncUpgradeLevelSettingByName'], $params); // $rlt = $this->_syncUpgradeLevelSettingByName($settingName, $data);

					$note = sprintf($_this->noteTpl, '[Step]preset VipUpgradeSetting in CACB for upgrade success', var_export($params, true), var_export($rlt, true) );
					$this->test( true // result
						,  true // expect
						, __METHOD__. ' '. 'Preset vip_upgrade_setting table' // title
						, $note // note
					);

					return [$rlt, $settingName];
				}; // function($settingName, $_this){...
			}
			list($rlt, $settingName) = $getUpgradeLevelSettingFn($this);

		}

		if( ! empty( $theTestPlayerInfo ) ){
			/// step: load setting for update in player current level,  theSeparateAccumulationSettings
			$theVipUpgradeSetting = null;
			$theVipUpgradeSettingList = $this->_getVip_upgrade_settingListBySettingName($settingName);
			if( ! empty($theVipUpgradeSettingList) ){
				$theVipUpgradeSetting = $theVipUpgradeSettingList[0];
				// $theSeparateAccumulationSettings = $this->utils->json_decode_handleErr($theVipUpgradeSetting['separate_accumulation_settings'], true);
				// $theFormula = $this->utils->json_decode_handleErr($theVipUpgradeSetting['formula'], true);
			}
			$note = sprintf($this->noteTpl, '[Step] load setting for update in player current level,  theSeparateAccumulationSettings', var_export($settingName, true), var_export($theVipUpgradeSetting, true) );

//				var_dump(['debugIn7500._getVip_upgrade_settingListBySettingName.settingName', $settingName, '_periodMode:theVipUpgradeSetting:', $theVipUpgradeSetting]);

			$this->test( ! empty($theVipUpgradeSetting) // result
				,  true // expect
				, __METHOD__. ' '. 'Load setting for update' // title
				, $note // note
			);
		}

		$thePlayerCurrentLevel = null;
		$currentvipsettingcashbackruleId = null;
		$nextVipsettingcashbackruleId = null;
		if( ! empty($theTestPlayerInfo) ){
			// load the player current level.
			// ref. to _updateUpgradeIdInVipsettingcashbackrule()
			$thePlayerId = $theTestPlayerInfo['player_id'];
			$thePlayerCurrentLevel = $this->player->getPlayerCurrentLevel($thePlayerId);
			$thePlayerCurrentLevel = $thePlayerCurrentLevel[0];
			$currentvipsettingcashbackruleId = $thePlayerCurrentLevel['vipsettingcashbackruleId'];

			$vipLevel = $thePlayerCurrentLevel['vipLevel'];
			$nextVipsettingcashbackruleId = $this->group_level->getVipLevelIdByLevelNumber($thePlayerCurrentLevel['vipSettingId'], $vipLevel+ 1);
		}

		if( ! empty( $thePlayerCurrentLevel ) ){
			if( ! empty($theVipUpgradeSetting) ){
				// update the setting into the player current level.
				// ref. to _updateUpgradeIdInVipsettingcashbackrule()
				$vipsettingcashbackruleId = $currentvipsettingcashbackruleId;
				$upgrade_id = $theVipUpgradeSetting['upgrade_id'];
				$targetField='vip_upgrade_id';
				$params = [$upgrade_id, $vipsettingcashbackruleId, $targetField];
				$rlt = call_user_func_array([$this, '_updateUpgradeIdInVipsettingcashbackrule'], $params); // $rlt = $this->_updateUpgradeIdInVipsettingcashbackrule($upgrade_id, $vipsettingcashbackruleId, $targetField);

				$note = sprintf($this->noteTpl, '[Step] hook setting into the player current level', var_export($params, true), var_export($rlt, true) );
				$this->test( true // ! empty($vipsettingcashbackruleId) // result
					,  true // expect
					, __METHOD__. ' '. 'Hook setting into the player current level' // title
					, $note // note
				);
			}

			if( ! empty($theVipUpgradeSetting) ){
				// Update the period on the player current level
				$gradeMode = 'upgrade';
				$_periodMode = $periodMode;
				$_periodValue = $periodValue;
				$isHourly = false;
				if($isHourlyInSetting){
					$isHourly = true;
				}
				$extraData = [];

				// $theJson = $this->_getPeriodJson($_periodMode, $_periodValue, $isHourly, $extraData);
				$theJson = $this->utils4testogp->_getPeriodJson($_periodMode,$_periodValue, $isHourly, $extraData);

				$params = [$thePlayerId, $gradeMode, $theJson];
				$rlt = call_user_func_array([$this, '_preSetupPeriodInPlayerCurrentLevel'], $params);// $rlt = $this->_preSetupPeriodInPlayerCurrentLevel($thePlayerId, $gradeMode, $theJson);
//					var_dump(['debugIn7555._preSetupPeriodInPlayerCurrentLevel.params', $params, '_periodMode:', $_periodMode]);
				$note = sprintf($this->noteTpl, '[Step] Update the period on the player current level', var_export($params, true), var_export($rlt, true) );
				$this->test( true // ! empty($vipsettingcashbackruleId) // result
					,  true // expect
					, __METHOD__. ' '. 'Update the period on the player current level' // title
					, $note // note
				);
			} // EOF if( ! empty($theVipUpgradeSetting) ){...
		}

		if( $isMultipleUpgrade && ! empty($nextVipsettingcashbackruleId) ){
			if( ! empty($theVipUpgradeSetting) ){
				// update the setting into the player current level.
				// ref. to _updateUpgradeIdInVipsettingcashbackrule()
				$vipsettingcashbackruleId = $nextVipsettingcashbackruleId;
				$upgrade_id = $theVipUpgradeSetting['upgrade_id'];
				$targetField='vip_upgrade_id';
				$params = [$upgrade_id, $vipsettingcashbackruleId, $targetField];
				$rlt = call_user_func_array([$this, '_updateUpgradeIdInVipsettingcashbackrule'], $params); // $rlt = $this->_updateUpgradeIdInVipsettingcashbackrule($upgrade_id, $vipsettingcashbackruleId, $targetField);
			}

			if( ! empty($theVipUpgradeSetting) ){
				// Update the period on the player current level
				$gradeMode = 'upgrade';
				$_periodMode = $periodMode;
				$_periodValue = $periodValue;
				$isHourly = false;
				if($isHourlyInSetting){
					$isHourly = true;
				}
				$extraData = [];

				// $theJson = $this->_getPeriodJson($_periodMode, $_periodValue, $isHourly, $extraData);
				$theJson = $this->utils4testogp->_getPeriodJson($_periodMode,$_periodValue, $isHourly, $extraData);

				$params = [$nextVipsettingcashbackruleId, $gradeMode, $theJson];
				$rlt = call_user_func_array([$this, '_preSetupPeriodInVipSettingCashbackRuleId'], $params);// $rlt = $this->_preSetupPeriodInVipSettingCashbackRuleId($nextVipsettingcashbackruleId, $gradeMode, $theJson);
//					var_dump(['debugIn7592._preSetupPeriodInVipSettingCashbackRuleId.params', $params, '_periodMode:', $_periodMode]);
				$note = sprintf($this->noteTpl, '[Step] Update the period on the player Next level', var_export($params, true), var_export($rlt, true) );
				$this->test( true // ! empty($vipsettingcashbackruleId) // result
					,  true // expect
					, __METHOD__. ' '. 'Update the period on the player Next level' // title
					, $note // note
				);
			} // EOF if( ! empty($theVipUpgradeSetting) ){...
		}

		if( ! empty( $theTestPlayerInfo ) ){
			/// step: trigger upgrade check by cron/sbe

			// // ref. to triggerPlayer_Management_manuallyUpgradeLevel()
			// $result = $this->group_level->batchUpDownLevelUpgrade($player_id, false, false, null, $order_generated_by);
			// // batchUpDownLevelUpgrade($playerIds, $manual_batch = false, $check_hourly = false, $time_exec_begin = null, $order_generated_by = null) {

			$is_blocked = false;
			$params = [];
			$params[] = $theTestPlayerInfo['player_id']. '_0'; // $playerId = null
			$params[] = 0; // $manual_batch = true
			$params[] = $time_exec_begin; // $time_exec_begin = null
			$params[] = 0; // $check_hourly
			// $playerId = null, $manual_batch = true, $time_exec_begin = null, $check_hourly
			$func = 'player_level_upgrade_by_playerId';
			$cmd = $this->utils->generateCommandLine($func, $params, $is_blocked);
			$return_var = $this->utils->runCmd($cmd);

			$note = sprintf($this->noteTpl, 'To trigger upgrade check by cron via command.', var_export($params,true), var_export($return_var,true) );
			$this->test( true // result
				,  true // expect
				, __METHOD__ // title
				, $note // note
			);

		}

		if( ! empty( $theTestPlayerInfo ) ){

			$idleTotalSec = 4; // use isOverWaitingTimeWithWaitingByPS() for detect ps BUT Not work.
			$this->utils->debug_log('5453.bbb.idleTotalSec',$this->oghome);
			$this->utils->idleSec($idleTotalSec);
			$this->utils->debug_log('5455.ccc.idleTotalSec',$this->oghome);
			$isExecingCB = null;
			$funcList = [];
			$funcList[] = $func;
			$maxWaitingTimes = 30;
			$waitingSec = 1;
			$isOverWaitingTime = $this->utils->isOverWaitingTimeWithWaitingByPS($funcList, $isExecingCB, $maxWaitingTimes, $waitingSec. $this->oghome);
			if ( ! $isOverWaitingTime ) {
			}
			$this->utils->debug_log('5464.aaa.idleTotalSec',$this->oghome);

			$reloadPlayerLevel = $this->player->getPlayerCurrentLevel($theTestPlayerInfo['player_id']);
			$afterId = $reloadPlayerLevel[0]['vipsettingcashbackruleId'];

			if( empty($testConditionFn) ){ // 0
				$testConditionFn = function($origId, $afterId) {
					return $origId != $afterId;
				};
			}else if( $testConditionFn == '1' ){ // for URI
				$testConditionFn = function($origId, $afterId) {
					return $origId == $afterId;
				};
			}else{
				// or $testConditionFn function
			}

			$rlt = $testConditionFn($origId, $afterId);
			$theGenerateCallTrace = '';
			if($rlt !== true){
				$theGenerateCallTrace .= ' ';
				$theGenerateCallTrace .= $this->utils->generateCallTrace();
			}
			$note = sprintf($this->noteTpl, 'Compare Player Level: Before(params) / After(rlt).', var_export($origId,true), var_export($afterId,true). $theGenerateCallTrace );
			$this->test( $rlt // result
				,  true // expect
				, __METHOD__ // title
				, $note // note
			);

		}


		//[] tryMacroSetupCACBInVipUpgradeSetting
		//[] step: preset VipUpgradeSetting in CACB for upgrade success
		//[] step: load setting for update in player current level,  theSeparateAccumulationSettings
		// step: trigger upgrade check by cron/sbe

	} // EOF _tryUpgradeSuccessTriggerFromCronjobV2



	/**
	 * URI,
	 * http://admin.og.local/cli/testing_ogp24373/index/dev
	 *
	 *
	 * @return void
	 */
	public function dev(){
		$this->init();


		if( false ){ // for test script need.
			$playerId = 54169; // domedin
			$game_description_id = 66746;
			$bet_datetime = '2022-02-11 12:34:51';
			$amount = 25;
			$rlt = $this->utils4testogp->_insertBet($playerId, $amount, $game_description_id, $bet_datetime );
			var_dump($rlt );
			$this->utils->idleSec(2);

			$playerId = 54169; // domedin
			$game_description_id = 66746;
			$bet_datetime = '2022-02-14 12:34:52';
			$amount = 25;
			$rlt = $this->utils4testogp->_insertBet($playerId, $amount, $game_description_id, $bet_datetime );
			var_dump($rlt );
		}

		/// Tested and pass
		// $playerId = 51719;
		// $game_description_id = 66746;
		// $bet_datetime = '2022-02-01 12:34:58';
		// $amount = 14;
		// $rlt = $this->utils4testogp->_insertBet($playerId, $amount, $game_description_id, $bet_datetime = 'now' );
		// var_dump($rlt );

		/// Tested and pass
		// $playerId = 51719;
		// $id_list_total_player_game_tables = [];
		// $id_list_total_player_game_tables['total_player_game_day'] = $rlt['total_player_game_day']; // 262872030;
		// $id_list_total_player_game_tables['total_player_game_hour'] = $rlt['total_player_game_hour']; // 37241657;
		// $id_list_total_player_game_tables['total_player_game_month'] = $rlt['total_player_game_month']; // 2;
		// $rlt = $this->utils4testogp->_saltBetByPlayerId($playerId, $id_list_total_player_game_tables);
		// var_dump($rlt );



		/// Tested and pass
		// $playerId = 51719;
		// $amount = 13;
		// $created_at = '2022-02-01 12:34:57';
		// $saleOrderId = $this->utils4testogp->_insertDeposit($playerId, $amount, $created_at);
		//
		// $rlt = $this->utils4testogp->_updeteStatusInTransactions($saleOrderId, 2);

		return false;
		// 連續升級
		// $rlt = $this->utils4testogp->_preSetupSystemFeatureTo('disable_player_multiple_upgrade', '1');
		// CACB
		// $isAccumulationSeparated = false;
		// $isBettingSeparated = false;
		// $rlt = $this->_preSetupAccumulationAndBettingTo($isAccumulationSeparated, $isBettingSeparated);
		/// 抓出最近 7 天， 10 位玩家來測試
		// $offsetDayRange = '7';
		// $limit = 10;
		// $testPlayerList = $this->utils4testogp->_searchTestPlayerList($offsetDayRange, $limit);
		// // $testPlayerList[0]['player_id']

		/// [TESTED] update upgrade setting into the level of player current.
		// $thePlayerId = 5357; // yiyusheng
		// $gradeMode = 'upgrade';
		// $bySettingName = 'OGP19825-2to3-cacb';// 'isMet-1to2-cacb';
		// $this->_preSetupSettingInPlayerCurrentLevel($thePlayerId, $gradeMode, $bySettingName );

		/// [TESTED] update downgrade setting into the level of player current.
		// $thePlayerId = 5357; // yiyusheng
		// $gradeMode = 'downgrade';
		// $bySettingName = 'OGP19825-3to2-cacb';// 'isMet-1to2-cacb';
		// $this->_preSetupSettingInPlayerCurrentLevel($thePlayerId, $gradeMode, $bySettingName );


		/// [TESTED] update downgrade Period and Maintain into the level of player current.
		// $thePlayerId = 5357; // yiyusheng
		// $gradeMode = 'downgrade';
		// $periodMode = 'weekly';
		// $periodValue = 2;
		// $isHourly = null;
		// $extraData = [];
		// $extraData['enableDownMaintain'] = false; // on:true, off:false
		// $extraData['downMaintainUnit'] = 2; // 1:Day, 2:Week, 3:Month
		// $extraData['downMaintainTimeLength'] = 10;
		// $theJson = $this->_getPeriodJson($periodMode, $periodValue, $isHourly, $extraData);
		// $rlt = $this->_preSetupPeriodInPlayerCurrentLevel($thePlayerId, $gradeMode, $theJson);

		/// [TESTED] update upgrade Period and hourly into the level of player current.
		// $thePlayerId = 5357; // yiyusheng
		// $gradeMode = 'upgrade';
		// $periodMode = 'monthly';
		// $periodValue = 29;
		// $isHourly = false;
		// $extraData = [];
		// $theJson = $this->_getPeriodJson($periodMode, $periodValue, $isHourly, $extraData);
		// $rlt = $this->_preSetupPeriodInPlayerCurrentLevel($thePlayerId, $gradeMode, $theJson);


		/// devMacro 設定共同累計共同投注
		$isAccumulationSeparated = false; // for CA
		$isBettingSeparated = false; // for CB
		$rlt = $this->_preSetupAccumulationAndBettingTo($isAccumulationSeparated, $isBettingSeparated);

		/// devMacro [TESTED] 清除附加設定
		// $isAccumulationSeparated = null;
		// $isBettingSeparated = null;
		// $this->_preSetupAccumulationAndBettingTo($isAccumulationSeparated, $isBettingSeparated);

		//
		$thePlayerId = 5357; // yiyusheng
		$settingName = 'devTest123';
		$data = [];
		$data['setting_name'] = 'devTest12345';
		$data['description'] = 'devTest12345.description';
		$data['status'] = 1; // always be 1 for active.
		$data['level_upgrade'] = $this->level_upgrade_upgrade;
		/// CA
		$data['accumulation'] = ''; // 0 / 1 / 4 : No / Yes, Registration Date / Yes, Last Change Period
		$data['separate_accumulation_settings'] = NULL; // always be NULL
		// NULL
		/// SA
		// $data['accumulation'] = 0;  // always be Zero
		// $data['separate_accumulation_settings'] = '@todo';
		// // {"bet_amount": {"accumulation": "1"}, "deposit_amount": {"accumulation": "1"}}
		// // {"bet_amount": {"accumulation": "1"}, "win_amount": {"accumulation": "4"}, "loss_amount": {"accumulation": "0"}, "deposit_amount": {"accumulation": "1"}}

		/// CB
		$betAmountMathSign = '<=';
		$betAmountValue = 1;
		$operatorBeforeDeposit = 'and';
		$depositAmountMathSign = '<=';
		$depositAmountValue = 2;
		$operatorBeforeLoss = 'and';
		$lossAmountMathSign = '<=';
		$lossAmountValue = 3;
		$operatorBeforeWin = null;
		$winAmountMathSign = null;
		$winAmountValue = null;
		$formula = $this->_prepareFormulaOfVipUpgradeSetting($betAmountMathSign
			, $betAmountValue
			, $operatorBeforeDeposit
			, $depositAmountMathSignl
			, $depositAmountValue
			, $operatorBeforeLoss
			, $lossAmountMathSign
			, $lossAmountValue
			, $operatorBeforeWin
			, $winAmountMathSign
			, $winAmountValue );
		$data['formula'] = $formula;
		// {"bet_amount":[">=","0"],"operator_2":"and","deposit_amount":[">=","212"]}
		// {"deposit_amount":[">=","45"],"operator_2":"and","bet_amount":["<=","23"]}
		$data['bet_amount_settings'] = NULL; // always be NULL

		/// SB
		// // 有 Betting 各別給值
		// // 有 Betting 只給總額度
		// // 沒有設定 Betting 的時候
		//
		/// 有 Betting 只給總額度
		// $betAmountMathSign = '<=';
		// $betAmountValue = 1;
		// $operatorBeforeDeposit = 'and';
		// $depositAmountMathSign = '<=';
		// $depositAmountValue = 2;
		// $operatorBeforeLoss = 'and';
		// $lossAmountMathSign = '<=';
		// $lossAmountValue = 3;
		// $operatorBeforeWin = null;
		// $winAmountMathSign = null;
		// $winAmountValue = null;
		//
		// // 沒有設定 Betting 的時候
		// $betAmountMathSign = null;
		// $betAmountValue = null;
		// $operatorBeforeDeposit = null;
		// $depositAmountMathSign = '<=';
		// $depositAmountValue = 2;
		// $operatorBeforeLoss = 'and';
		// $lossAmountMathSign = '<=';
		// $lossAmountValue = 3;
		// $operatorBeforeWin = null;
		// $winAmountMathSign = null;
		// $winAmountValue = null;
		//
		// $formula = $this->_prepareFormulaOfVipUpgradeSetting($betAmountMathSign
		// 	, $betAmountValue
		// 	, $operatorBeforeDeposit
		// 	, $depositAmountMathSignl
		// 	, $depositAmountValue
		// 	, $operatorBeforeLoss
		// 	, $lossAmountMathSign
		// 	, $lossAmountValue
		// 	, $operatorBeforeWin
		// 	, $winAmountMathSign
		// 	, $winAmountValue );
		// $data['formula'] = $formula;
		// // {"bet_amount":[">=","0"],"operator_2":"and","deposit_amount":[">=","26"]}
		// // {"bet_amount":[">=","123"],"operator_2":"or","deposit_amount":[">=","234"],"operator_3":"and","loss_amount":[">=","345"]}
		// // {"deposit_amount":[">=","1"],"operator_2":"and","loss_amount":["<=","2"],"operator_3":"or","win_amount":[">=","3"]}

		// 有 Betting 只給總額度
		$bet_amount_settings = null;
		// $bet_amount_settings
		$data['bet_amount_settings'] = $bet_amount_settings;
		// {"itemList": [{"type": "game_type", "value": "24", "math_sign": ">=", "game_type_id": "561"}, {"type": "game_platform", "value": "25", "math_sign": ">=", "game_platform_id": "5674", "precon_logic_flag": "and"}], "defaultItem": {"value": "123", "math_sign": ">="}}
		// NULL
		// NULL
		$rlt = $this->_syncUpgradeLevelSettingByName($settingName, $data);
		echo 'aa';
// 		// 設定升級等級的 setting
// // player should be LV3.
// $setupUpGrade = '2to1';// 設定 VIP 2 - OGP19825-2to1-cacb
// $result = $this->stepSetupVIP1to2InPlayerCurrentLevel($setupUpGrade);
// $this->returnText(__LINE__.'.returnText().result: '.var_export($result, true));

// 	$this->load->model(['group_level', 'player']);
// 	/// 取得玩家當下等級，的上一個等級資料。$
// 	$result = $this->player->getPlayerCurrentLevel($thePlayerId);
// 	$previousVipsettingcashbackruleId = $this->group_level->getVipLevelIdByLevelNumber($thePlayerCurrentLevel['vipSettingId'], $vipLevel);
		// $this->_getVip_upgrade_settingListBySettingName
// _getVip_upgrade_settingListBySettingName
// _updateUpgradeIdInVipsettingcashbackrule


		/// [TESTED] 設定共同累計共同投注
		// $isAccumulationSeparated = false;
		// $isBettingSeparated = false;
		// $rlt = $this->_preSetupAccumulationAndBettingTo($isAccumulationSeparated, $isBettingSeparated);
		/// [TESTED] 清除附加設定
		// $isAccumulationSeparated = null;
		// $isBettingSeparated = null;
		// $this->_preSetupAccumulationAndBettingTo($isAccumulationSeparated, $isBettingSeparated);

		/// [TESTED] 可以控制 Feature 開關
		// $this->utils4testogp->_preSetupSystemFeatureTo('disable_player_multiple_upgrade', '1');

		/// for enable_separate_accumulation_in_setting, vip_setting_form_ver
		// // 附加設定變數到設定檔案。 [TESTED]
		// $settingName = 'aaa';
		// $settingValue = 'true';
		// $this->_appendBooleanSettingInConfig_secret_local($settingName, $settingValue);
		// $settingName = 'bbb';
		// $settingValue = '45623';
		// return $this->_appendBooleanSettingInConfig_secret_local($settingName, $settingValue);

		/// 清空附加的內容（保持特定字元） [TESTED]
		// // dirname()
		// $realPATH = '../../'. 'secret_keys/config_secret_local.php';
		// // CSL = config_secret_local
		// $phpPathFile4CSL = realpath($realPATH) ;
		// $appendFixBegin = $this->appendFixBegin;
		// $appendFixEnd = $this->appendFixEnd;
		// return $this->utils4testogp->util_clearAppendedContentsInFile($phpPathFile4CSL, $appendFixBegin, $appendFixEnd);
	}

	/**
	 * Append The Setting into the config_secret_local.php file.
	 * The Setting should be non-array and non-object type.
	 * [TESTED]
	 * @param string $settingName The key name of the $config array in the config_secret_local.php file.
	 * @param string $settingValue The value of the element of $config array in the config_secret_local.php file.
	 * @return boolean always be true.
	 */
	private function _appendBooleanSettingInConfig_secret_local($settingName, $settingValue){
		$appendFixBegin = $this->appendFixBegin;
		$appendFixEnd = $this->appendFixEnd;
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
		$fileContents4CSL = $this->utils4testogp->util_readFile($phpPathFile4CSL);

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
			$this->utils4testogp->util_writeToFile($phpPathFile4CSL, $fileContents4CSL);
		}else{
			// $str_to_insert = $extraContents;
			// $fileContents4CSL = substr_replace($fileContents4CSL, $str_to_insert, $pos, 0);
			// $this->utils4testogp->util_writeToFile($phpPathFile4CSL, $fileContents4CSL);
			$this->utils4testogp->util_appendContentsInFileWithPreg_replace($phpPathFile4CSL, $extraContents, $appendFixBegin, $appendFixEnd);
		}
		return true;
	} // EOF _appendBooleanSettingInConfig_secret_local






	/**
	 * Specified vip_upgrade_setting rows
	 *
	 * @return array
	 */
	private function _preSetup4vipUpgradeSettings(){
		$vipUpgradeSettings = [];

		// - CaseA: Common Accumulation + Common Bet Amount
		$vipUpgradeSettings['1to2_cacb'] = [];
		// $vipUpgradeSettings['1to2_cacb']['upgrade_id'] = 123;
		$vipUpgradeSettings['1to2_cacb']['setting_name'] = 'OGP19825-1to2-cacb';

		$vipUpgradeSettings['2to1_cacb'] = []; // downgrade
		$vipUpgradeSettings['2to1_cacb']['setting_name'] = 'OGP19825-2to1-cacb';//STG:v

		$vipUpgradeSettings['2to3_cacb'] = [];
		$vipUpgradeSettings['2to3_cacb']['setting_name'] = 'OGP19825-2to3-cacb';

		$vipUpgradeSettings['3to2_cacb'] = []; // downgrade
		$vipUpgradeSettings['3to2_cacb']['setting_name'] = 'OGP19825-3to2-cacb';//STG:v

		// - CaseB: Common Accumulation + Separate Bet Amount
		$vipUpgradeSettings['1to2_casb'] = [];
		$vipUpgradeSettings['1to2_casb']['setting_name'] = 'OGP19825-1to2-casb';

		$vipUpgradeSettings['2to1_casb'] = []; // downgrade
		$vipUpgradeSettings['2to1_casb']['setting_name'] = 'OGP19825-2to1-casb';//STG:v

		$vipUpgradeSettings['2to3_casb'] = [];
		$vipUpgradeSettings['2to3_casb']['setting_name'] = 'OGP19825-2to3-casb';

		$vipUpgradeSettings['3to2_casb'] = []; // downgrade
		$vipUpgradeSettings['3to2_casb']['setting_name'] = 'OGP19825-3to2-casb';//STG:v

		// - CaseC: Separate Accumulation + Common Bet Amount
		$vipUpgradeSettings['1to2_sacb'] = [];
		$vipUpgradeSettings['1to2_sacb']['setting_name'] = 'OGP19825-1to2-sacb';

		$vipUpgradeSettings['2to1_sacb'] = []; // downgrade
		$vipUpgradeSettings['2to1_sacb']['setting_name'] = 'OGP19825-2to1-sacb';//STG:

		$vipUpgradeSettings['2to3_sacb'] = [];
		$vipUpgradeSettings['2to3_sacb']['setting_name'] = 'OGP19825-2to3-sacb';

		$vipUpgradeSettings['3to2_sacb'] = []; // downgrade
		$vipUpgradeSettings['3to2_sacb']['setting_name'] = 'OGP19825-3to2-sacb';//STG:

		// - CaseD: Separate Accumulation + Separate Bet Amount
		$vipUpgradeSettings['1to2_sasb'] = [];
		$vipUpgradeSettings['1to2_sasb']['setting_name'] = 'OGP19825-1to2-sasb';

		$vipUpgradeSettings['2to1_sasb'] = []; // downgrade
		$vipUpgradeSettings['2to1_sasb']['setting_name'] = 'OGP19825-2to1-sasb';//STG:v

		$vipUpgradeSettings['2to3_sasb'] = [];
		$vipUpgradeSettings['2to3_sasb']['setting_name'] = 'OGP19825-2to3-sasb';

		$vipUpgradeSettings['3to2_sasb'] = []; // downgrade
		$vipUpgradeSettings['3to2_sasb']['setting_name'] = 'OGP19825-3to2-sasb';//STG:v

		return $vipUpgradeSettings;
	} // EOF _preSetup4vipUpgradeSettings


	public function _preSetup4vipUpgradeSettingsV2($vipUpgradeSettings){
		if( empty($vipUpgradeSettings) ){
			$vipUpgradeSettings = [];
		}

		// - CaseA: Common Accumulation + Common Bet Amount
		if( empty($vipUpgradeSettings['1to2_cacb']) ){
			$vipUpgradeSettings['1to2_cacb'] = [];
		}
		// $vipUpgradeSettings['1to2_cacb']['upgrade_id'] = 123;
		$vipUpgradeSettings['1to2_cacb']['isMet']['setting_name'] = 'isMet-1to2-cacb';
		$vipUpgradeSettings['1to2_cacb']['notMet']['setting_name'] = 'notMet-1to2-cacb';

		if( empty($vipUpgradeSettings['2to1_cacb']) ){
			$vipUpgradeSettings['2to1_cacb'] = [];// downgrade
		}
		$vipUpgradeSettings['2to1_cacb']['isMet']['setting_name'] = 'isMet-2to1-cacb';
		$vipUpgradeSettings['2to1_cacb']['notMet']['setting_name'] = 'notMet-2to1-cacb';

		// - CaseB: Common Accumulation + Separate Bet Amount
		if( empty($vipUpgradeSettings['1to2_casb']) ){
			$vipUpgradeSettings['1to2_casb'] = [];// downgrade
		}
		$vipUpgradeSettings['1to2_casb']['isMet']['setting_name'] = 'isMet-1to2-casb';
		$vipUpgradeSettings['1to2_casb']['notMet']['setting_name'] = 'notMet-1to2-casb';

		if( empty($vipUpgradeSettings['2to1_casb']) ){
			$vipUpgradeSettings['2to1_casb'] = [];// downgrade
		}
		$vipUpgradeSettings['2to1_casb']['isMet']['setting_name'] = 'isMet-2to1-casb';
		$vipUpgradeSettings['2to1_casb']['notMet']['setting_name'] = 'notMet-2to1-casb';

		// todo CaseC: Separate Accumulation + Common Bet Amount
		// todo CaseD: Separate Accumulation + Separate Bet Amount

		return $vipUpgradeSettings;
	} // EOF _preSetup4vipUpgradeSettingsV2


	/**
	 * Search Test PlayerList
	 *
	 * URI,
	 * http://admin.og.local/cli/testing_ogp24373/index/searchTestPlayerList4FromSBE/180/5
	 * //admin.staging.onestop.t1t.in/cli/testing_ogp24373/index/searchTestPlayerList4FromSBE/180/5
	 *
	 * Cli,
	 * php admin/public/index.php cli/testing_ogp24373/searchTestPlayerList4FromSBE 60 5
	 *
	 *
	 * @param string $offsetRange The betting log data that'd started a few days ago.
	 * @param integer $limit Catch the data,the test players amount.
	 * @return void
	 */
	public function searchTestPlayerList4FromSBE($offsetDayRange = '7', $limit = 10){

		$this->load->model(['group_level']);
		// $rows = $this->utils4testogp->_searchTestPlayerList($offsetDayRange, $limit);
		$rows = $this->utils4testogp->_searchTestPlayerList4FromSBE($offsetDayRange, $limit);

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
	}

	/**
	 * Search Test PlayerList
	 * URI,
	 * http://admin.og.local/cli/testing_ogp24373/index/searchTestPlayerList/30/5
	 * //admin.staging.onestop.t1t.in/cli/testing_ogp24373/index/searchTestPlayerList/60/5
	 *
	 * Cli,
	 * php admin/public/index.php cli/testing_ogp24373/searchTestPlayerList 60 5
	 *
	 *
	 * @param string $offsetRange The betting log data that'd started a few days ago.
	 * @param integer $limit Catch the data,the test players amount.
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
	 * http://admin.og.local/cli/testing_ogp24373/index/searchTestPlayerByPlayerId/5357
	 *
	 * @param integer $thePlayerId
	 * @return void
	 */
	public function searchTestPlayerByPlayerId($thePlayerId){
		$rows = $this->utils4testogp->_searchTestPlayerByPlayerId($thePlayerId);
		//$rows = $this->_searchTestPlayerByPlayerId($thePlayerId);

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
	 * To preset/clear for Accumulation And Betting settings
	 * [TESTED]
	 *
	 * @param boolean|null $isAccumulationSeparated If true will be Common Accumulation else Separated.
	 * @param boolean|null $isBettingSeparated If true will be Common Betting else Separated.
	 * @return bool Always return true.
	 */
	private function _preSetupAccumulationAndBettingTo($isAccumulationSeparated = false, $isBettingSeparated = false){

		if($isAccumulationSeparated === null && $isBettingSeparated === null){
			// clear the preset for Accumulation And Betting settings
			$realPATH = '../../'. 'secret_keys/config_secret_local.php';
			$phpPathFile4CSL = realpath($realPATH);
			$appendFixBegin = $this->appendFixBegin;
			$appendFixEnd = $this->appendFixEnd;
			$this->utils4testogp->util_clearAppendedContentsInFile($phpPathFile4CSL, $appendFixBegin, $appendFixEnd);
		}else if( ! $isAccumulationSeparated && ! $isBettingSeparated ) { // CACB
			$settingName = 'enable_separate_accumulation_in_setting';
			$settingValue = 'false'; // CA
			$this->_appendBooleanSettingInConfig_secret_local($settingName, $settingValue);
			$settingName = 'vip_setting_form_ver';
			$settingValue = '1'; // CB
			$this->_appendBooleanSettingInConfig_secret_local($settingName, $settingValue);
		}else if( ! $isAccumulationSeparated && $isBettingSeparated ) { // CASB
			$settingName = 'enable_separate_accumulation_in_setting';
			$settingValue = 'false'; // CA
			$this->_appendBooleanSettingInConfig_secret_local($settingName, $settingValue);
			$settingName = 'vip_setting_form_ver';
			$settingValue = '2'; // SB
			$this->_appendBooleanSettingInConfig_secret_local($settingName, $settingValue);
		}else if( $isAccumulationSeparated && ! $isBettingSeparated ) { // SACB
			$settingName = 'enable_separate_accumulation_in_setting';
			$settingValue = 'true'; // SA
			$this->_appendBooleanSettingInConfig_secret_local($settingName, $settingValue);
			$settingName = 'vip_setting_form_ver';
			$settingValue = '1'; // CB
			$this->_appendBooleanSettingInConfig_secret_local($settingName, $settingValue);
		}else if( $isAccumulationSeparated && $isBettingSeparated ) { // SASB
			$settingName = 'enable_separate_accumulation_in_setting';
			$settingValue = 'true'; // SA
			$this->_appendBooleanSettingInConfig_secret_local($settingName, $settingValue);
			$settingName = 'vip_setting_form_ver';
			$settingValue = '2'; // SB
			$this->_appendBooleanSettingInConfig_secret_local($settingName, $settingValue);
		}
		return true;
	} // _preSetupAccumulationAndBettingTo

	/**
	 * !!! DONT Executed in the prod hosting, Will Update VIP LEVEL Settings !!!
	 * URI,
	 * //admin.staging.onestop.t1t.in/cli/testing_ogp24373/index/searchTestPlayerList/180/10
	 *
	 * Cli,
	 * php admin/public/index.php cli/testing_ogp19825/scriptDowngrade 1540 'sacb'
	 *
	 * @param [type] $playerId
	 * @param [type] $mode
	 * @return void
	 */
	public function DELscriptDowngrade($playerId = null, $mode = null){
		// $playerId = 159440;
		// $this->_getVipsettingcashbackruleIdByPlayerId($playerId);

		// $vipUpgradeSettings = $this->_preSetup4vipUpgradeSettings();
		// $settingName = $vipUpgradeSettings['1to2_cacb']['setting_name'];
		// $this->_getVipsettingcashbackruleListByUpgradeSettingName($settingName);

		// $this->playerId = 31773; // lwb1986
		$this->playerId = 159805; // test002 in local
		// $this->playerId = 162503; // test11054
		// $this->playerId = 162514; // testow1
		// $this->playerId = 162515; // testow2

		// enable_separate_accumulation_in_setting=false , vip_setting_form_ver=1
		$this->currCase['mode'] = 'cacb';
		// enable_separate_accumulation_in_setting=true , vip_setting_form_ver=1
		$this->currCase['mode'] = 'sacb';
		// enable_separate_accumulation_in_setting=false , vip_setting_form_ver=2
		$this->currCase['mode'] = 'casb';
		// enable_separate_accumulation_in_setting=true , vip_setting_form_ver=2
		$this->currCase['mode'] = 'sasb';
		// stepSetupVIP1to2

		if( ! empty($playerId) ){
			$this->playerId = $playerId;
		}
		if( ! empty($mode) ){
			$this->currCase['mode'] = $mode;
		}

		/// OK
		// $this->currCase = [];
		// $this->currCase['mode'] = 'cacb';
		// $result = $this->stepSetPlayerToVip2();
		// $this->returnText(__METHOD__.'.returnText().result: '.var_export($result, true));

		/// OK
		// $result = $this->getVipsettingcashbackruleListByUpgradeSettingName('OGP19825-2to3-cacb', 162514);
		// $this->returnText(__METHOD__.'.returnText().result: '.var_export($result, true));

		// player should be LV3.
		$setupUpGrade = '2to1';// 設定 VIP 2 - OGP19825-2to1-cacb
		$result = $this->stepSetupVIP1to2InPlayerCurrentLevel($setupUpGrade);
		$this->returnText(__LINE__.'.returnText().result: '.var_export($result, true));

		$setupUpGrade = '3to2'; // 設定 VIP 3 - OGP19825-3to2-cacb
		$result = $this->stepSetupVIP1to2InPlayerCurrentLevel($setupUpGrade); // period_up_down_2 = {"weekly":"2","hourly":true}
		$this->returnText(__LINE__.'.returnText().result: '.var_export($result, true));

		// 系統 將玩家( VIP 3 ) 升級 （ 到 VIP 2 ）
		$result = $this->triggerPlayer_Management_manuallyDowngradeLevel();
		$this->returnText(__LINE__.'.returnText().result: '.var_export($result, true));

	}


	/**
	 * !!! DONT Executed in the prod hosting, Will Update VIP LEVEL Settings !!!
	 * URI,
	 * //admin.og.local/cli/testing_ogp19825/index/scriptUpgrade/12345/cacb
	 * //admin.staging.onestop.t1t.in/cli/testing_ogp19825/index/scriptUpgrade/1540/sacb
	 *
	 * Cli,
	 * php admin/public/index.php cli/testing_ogp19825/scriptUpgrade 12345 'cacb'
	 * php admin/public/index.php cli/testing_ogp19825/scriptUpgrade 1540 'sacb'
	 *
	 * @param [type] $playerId
	 * @param [type] $mode
	 * @return void
	 */
	public function DELscriptUpgrade($playerId = null, $mode = null){
		// $playerId = 159440;
		// $this->_getVipsettingcashbackruleIdByPlayerId($playerId);

		// $vipUpgradeSettings = $this->_preSetup4vipUpgradeSettings();
		// $settingName = $vipUpgradeSettings['1to2_cacb']['setting_name'];
		// $this->_getVipsettingcashbackruleListByUpgradeSettingName($settingName);

		$this->playerId = 31773; // lwb1986 in local
		// $this->playerId = 159805; // test002
		// $this->playerId = 162503; // test11054
		// $this->playerId = 162514; // testow1
		// $this->playerId = 162515; // testow2

		// enable_separate_accumulation_in_setting=false , vip_setting_form_ver=1
		$this->currCase['mode'] = 'cacb';
		// enable_separate_accumulation_in_setting=true , vip_setting_form_ver=1
		$this->currCase['mode'] = 'sacb';
		// enable_separate_accumulation_in_setting=false , vip_setting_form_ver=2
		$this->currCase['mode'] = 'casb';
		// enable_separate_accumulation_in_setting=true , vip_setting_form_ver=2
		$this->currCase['mode'] = 'sasb';
		// stepSetupVIP1to2

		if( ! empty($playerId) ){
			$this->playerId = $playerId;
		}
		if( ! empty($mode) ){
			$this->currCase['mode'] = $mode;
		}

		/// OK
		// $this->currCase = [];
		// $this->currCase['mode'] = 'cacb';
		// $result = $this->stepSetPlayerToVip2();
		// $this->returnText(__METHOD__.'.returnText().result: '.var_export($result, true));

		/// OK
		// $result = $this->getVipsettingcashbackruleListByUpgradeSettingName('OGP19825-2to3-cacb', 162514);
		// $this->returnText(__METHOD__.'.returnText().result: '.var_export($result, true));

		$setupUpGrade = '1to2';// 設定 VIP 1 - OGP19825-1to2-cacb
		$result = $this->stepSetupVIP1to2InPlayerCurrentLevel();
		$this->returnText(__LINE__.'.returnText().result: '.var_export($setupUpGrade, true));

		$setupUpGrade = '2to3'; // 設定 VIP 2 - OGP19825-2to3-cacb
		$result = $this->stepSetupVIP1to2InPlayerCurrentLevel($setupUpGrade); // period_up_down_2 = {"weekly":"2","hourly":true}
		$this->returnText(__LINE__.'.returnText().result: '.var_export($result, true));

		// 系統 將玩家( VIP 2 ) 升級 （ 到 VIP 3 ）
		$result = $this->triggerPlayer_Management_manuallyUpgradeLevel();
		$this->returnText(__LINE__.'.returnText().result: '.var_export($result, true));

	}





	/**
	 * clone from Player_Management::manuallyUpgradeLevel()
	 *
	 *
	 * Player_Management::manuallyUpgradeLevel($player_id)
	 *
	 *
	 * Command::batch_player_level_upgrade
	 * $result = $this->group_level->batchUpDownLevelUpgrade($playerIds, $manual_batch, null, $time_exec_begin, $order_generated_by);
	 *
	 * Command::batch_player_level_upgrade_check_hourly
	 * $result = $this->group_level->batchUpDownLevelUpgrade($playerIds, $manual_batch, true, $time_exec_begin, $order_generated_by);
	 *
	 * @return void
	 */
	public function DELtriggerPlayer_Management_manuallyUpgradeLevel(){
		$this->load->model(array('group_level', 'player_promo'));

		$player_id = $this->playerId;

		$this->group_level->setGradeRecord([
			'request_type'  => Group_level::REQUEST_TYPE_MANUAL_GRADE,
			'request_grade' => Group_level::RECORD_UPGRADE,
			'request_time'  => date('Y-m-d H:i:s')
		]);

		$order_generated_by = ['order_generated_by' => Player_promo::ORDER_GENERATED_BY_MANUALLY_UPGRADE_LEVEL];
		if( $this->isEnableTesting){
			$result = $this->group_level->batchUpDownLevelUpgrade($player_id, false, false, null, $order_generated_by);
		}else{
			$result = null;
		}


		$isDone = true;
		$resultMessages = [];
		$return['isDone'] = $isDone;
		$return['result'] = $result;
		$return['resultMessages'] = $resultMessages;
		return $return;
	}

	/**
	 * Player_Management::manuallyDowngradeLevel($player_id)
	 * $result = $this->group_level->batchUpDownLevelDowngrade($player_id, false, $order_generated_by);
	 *
	 * @return void
	 */
	public function triggerPlayer_Management_manuallyDowngradeLevel() {
		$this->load->model(array('group_level', 'player_promo'));

		$player_id = $this->playerId;

		$this->group_level->setGradeRecord([
			'request_type'  => Group_level::REQUEST_TYPE_MANUAL_GRADE,
			'request_grade' => Group_level::RECORD_DOWNGRADE,
			'request_time'  => date('Y-m-d H:i:s')
		]);

		$order_generated_by = ['order_generated_by' => Player_promo::ORDER_GENERATED_BY_MANUALLY_DOWNGRADE_LEVEL];
		if( $this->isEnableTesting){
			$result = $this->group_level->batchUpDownLevelDowngrade($player_id, false, $order_generated_by);
		}else{
			$result = false;
		}

		$isDone = true;
		$resultMessages = [];
		$return['isDone'] = $isDone;
		$return['result'] = $result;
		$return['resultMessages'] = $resultMessages;
		return $return;

		// if (!empty($result['success'])) {
		// 	$this->alertMessage(self::MESSAGE_TYPE_SUCCESS, $result['success']);
		// } else {
		// 	$this->alertMessage(self::MESSAGE_TYPE_ERROR, $result['error']);
		// }

		// redirect('player_management/userInformation/' . $player_id);
	} // EOF triggerPlayer_Management_manuallyDowngradeLevel



	/**
	 * Setup the Setting into the Level of the player Current.
	 *
	 * @param integer $thePlayerId The field,"player.playerId".
	 * @param string $gradeMode Jusgt support 'upgrade' and 'downgrade'.
	 * @param string $bySettingName The field, vip_upgrade_setting.setting_name.
	 * @return boolean|integer The return of db::update().
	 */
	private function _preSetupSettingInPlayerCurrentLevel($thePlayerId, $gradeMode = 'upgrade', $bySettingName = null){
		$this->load->model(['group_level', 'player']);
		$result = $this->player->getPlayerCurrentLevel($thePlayerId);
		$thePlayerCurrentLevel = $result[0];
		// $thePlayerCurrentLevel['vipsettingcashbackruleId'];
		$vipLevel = $thePlayerCurrentLevel['vipLevel'];
		// $vip_upgrade_id = $theVipsettingcashbackruleRow['vip_upgrade_id'];
		// $theVipsettingcashbackruleId = $this->group_level->getVipLevelIdByLevelNumber($thePlayerCurrentLevel['vipSettingId'], $vipLevel);
// print_r($theVipsettingcashbackruleId);
		$settingList = $this->_getVip_upgrade_settingListBySettingName($bySettingName);
		$the_vip_upgrade_setting = $settingList[0];

		$vipsettingcashbackruleId = $thePlayerCurrentLevel['vipsettingcashbackruleId'];

		$upgrade_id = $the_vip_upgrade_setting['upgrade_id'];
		if($gradeMode == 'upgrade'){
			$targetField = 'vip_upgrade_id';
		}
		if($gradeMode == 'downgrade'){
			$targetField = 'vip_downgrade_id';
		}
		$rlt = $this->_updateUpgradeIdInVipsettingcashbackrule($upgrade_id, $vipsettingcashbackruleId, $targetField);
// print_r('_updateUpgradeIdInVipsettingcashbackrule.rlt');
// print_r($rlt);
		return $rlt;
	}

// 	private function _preSetupSettingInVipSettingCashbackRuleId($vipsettingcashbackruleId, $gradeMode = 'upgrade', $bySettingName = null){
// 		$this->load->model(['group_level', 'player']);
// // 		$result = $this->player->getPlayerCurrentLevel($thePlayerId);
// // 		$thePlayerCurrentLevel = $result[0];
// // 		// $thePlayerCurrentLevel['vipsettingcashbackruleId'];
// // 		$vipLevel = $thePlayerCurrentLevel['vipLevel'];
// // 		// $vip_upgrade_id = $theVipsettingcashbackruleRow['vip_upgrade_id'];
// // 		// $theVipsettingcashbackruleId = $this->group_level->getVipLevelIdByLevelNumber($thePlayerCurrentLevel['vipSettingId'], $vipLevel);
// // // print_r($theVipsettingcashbackruleId);
// 		$settingList = $this->_getVip_upgrade_settingListBySettingName($bySettingName);
// 		$the_vip_upgrade_setting = $settingList[0];
// //
// // 		$vipsettingcashbackruleId = $thePlayerCurrentLevel['vipsettingcashbackruleId'];
//
// 		$upgrade_id = $the_vip_upgrade_setting['upgrade_id'];
// 		if($gradeMode == 'upgrade'){
// 			$targetField = 'vip_upgrade_id';
// 		}
// 		if($gradeMode == 'downgrade'){
// 			$targetField = 'vip_downgrade_id';
// 		}
// 		$rlt = $this->_updateUpgradeIdInVipsettingcashbackrule($upgrade_id, $vipsettingcashbackruleId, $targetField);
// // print_r('_updateUpgradeIdInVipsettingcashbackrule.rlt');
// // print_r($rlt);
// 		return $rlt;
// 	}


	/**
	 * Prepare Formula [TESTED]
	 *
	 *  TEST betAmount, depositAmount, lossAmount and winAmount empty value test
	 *  // 0,0,0,0 => No Formula
	 *  // 0,0,0,1 => Only winAmount
	 *  // 0,0,1,0 => Only lossAmount
	 *  // 0,0,1,1 => lossAmount and winAmount
	 *  // 0,1,0,0 => Only depositAmount
	 *  // 0,1,0,1 => depositAmount and winAmount
	 *  // 0,1,1,0 => depositAmount and lossAmount
	 *  // 0,1,1,1 => depositAmount, lossAmount and lossAmount
	 *  // 1,0,0,0 => Only betAmount
	 *  // 1,0,0,1 => betAmount and winAmount
	 *  // 1,0,1,0 => betAmount and lossAmount
	 *  // 1,0,1,1 => betAmount, lossAmount and winAmount
	 *  // 1,1,0,0 => betAmount and depositAmount
	 *  // 1,1,0,1 => betAmount, depositAmount and winAmount
	 *  // 1,1,1,0 => betAmount, depositAmount and lossAmount
	 *  // 1,1,1,1 => All
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
		return $this->utils4testogp->_prepareFormulaOfVipUpgradeSetting( $betAmountMathSign // #1
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
	} // EOF _prepareFormulaOfVipUpgradeSetting


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
	private function _syncUpgradeLevelSettingByName($settingName, $data){
		return $this->utils4testogp->_syncUpgradeLevelSettingByName($settingName, $data);
	}// _syncUpgradeLevelSetting

	/**
	 * Setup the Period in the level of the Player Current.
	 * [TRIED]
	 * @param integer $thePlayerId The field, player.playerId.
	 * @param string $gradeMode For upgrade or downgrade.
	 * @param string $theJson The json string for Period settgings.
	 * @return void
	 */
	private function _preSetupPeriodInPlayerCurrentLevel($thePlayerId, $gradeMode = 'upgrade', $theJson = '{}'){
		$this->load->model(['group_level', 'player']);
		$result = $this->player->getPlayerCurrentLevel($thePlayerId);
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
		$rlt = $this->_updateUpgradeIdInVipsettingcashbackrule($period, $vipsettingcashbackruleId, $targetField);
	} // EOF _preSetupPeriodInPlayerCurrentLevel


	private function _preSetupGuaranteedDowngradeInPlayerCurrentLevel($thePlayerId, $period_number = 0, $period_total_deposit = 0){
		$this->load->model(['group_level', 'player']);
		$result = $this->player->getPlayerCurrentLevel($thePlayerId);
		$thePlayerCurrentLevel = $result[0];
		$vipsettingcashbackruleId = $thePlayerCurrentLevel['vipsettingcashbackruleId'];
		$targetField = 'guaranteed_downgrade_period_number';
		$targetValue = $period_number;
		$rlt4period_number = $this->_updateUpgradeIdInVipsettingcashbackrule($targetValue, $vipsettingcashbackruleId, $targetField);

		$targetField = 'guaranteed_downgrade_period_total_deposit';
		$targetValue = $period_total_deposit;
		$rlt4period_total_deposit = $this->_updateUpgradeIdInVipsettingcashbackrule($targetValue, $vipsettingcashbackruleId, $targetField);

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
	private function DEL_parsePeriodInfoInPeriod_down($thePeriodDownStr = '[]'){
		return $this->utils4testogp->_parsePeriodInfoInPeriod_down($thePeriodDownStr);
		// $thePeriodInfo = [];
		// $thePeriodDown = $this->utils->json_decode_handleErr($thePeriodDownStr, true);
		// if( !empty($thePeriodDown) ){
		// 	if(array_key_exists('daily', $thePeriodDown) !== false){
		// 		$thePeriodInfo['PeriodMode'] = 'daily';
		// 		$thePeriodInfo['PeriodValue'] = $thePeriodDown['daily'];
		// 	}else if(array_key_exists('weekly', $thePeriodDown) !== false){
		// 		$thePeriodInfo['PeriodMode'] = 'weekly';
		// 		$thePeriodInfo['PeriodValue'] = $thePeriodDown['weekly'];
		// 	}else if(array_key_exists('monthly', $thePeriodDown) !== false){
		// 		$thePeriodInfo['PeriodMode'] = 'monthly';
		// 		$thePeriodInfo['PeriodValue'] = $thePeriodDown['monthly'];
		// 	}
		// }
		// return $thePeriodInfo;
	} // _parsePeriodInfoInPeriod_down

	/**
	 * Setup the Period in the level of the Player Current.
	 * [TRIED]
	 * @param integer $thePlayerId The field, player.playerId.
	 * @param string $gradeMode For upgrade or downgrade.
	 * @param string $theJson The json string for Period settgings.
	 * @return void
	 */
	private function _preSetupPeriodInVipSettingCashbackRuleId($vipsettingcashbackruleId, $gradeMode = 'upgrade', $theJson = '{}'){
		$this->load->model(['group_level', 'player']);
		// $result = $this->player->getPlayerCurrentLevel($thePlayerId);
		// $thePlayerCurrentLevel = $result[0];
		// $vipLevel = $thePlayerCurrentLevel['vipLevel'];
		// $vipsettingcashbackruleId = $thePlayerCurrentLevel['vipsettingcashbackruleId'];
		if($gradeMode == 'upgrade'){
			$targetField = 'period_up_down_2';
		}
		if($gradeMode == 'downgrade'){
			$targetField = 'period_down';
		}
		$period = $theJson;
		$rlt = $this->_updateUpgradeIdInVipsettingcashbackrule($period, $vipsettingcashbackruleId, $targetField);
	} // EOF _preSetupPeriodInPlayerCurrentLevel
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
	private function _getPeriodJson($periodMode = 'weekly', $periodValue = '', $isHourly = null, $extraData = []){
		return $this->utils4testogp->_getPeriodJson($periodMode, $periodValue, $isHourly, $extraData);
		// $jsonArray = [];
		// switch( strtolower($periodMode) ){
		// 	case 'daily':
		// 		if( ! empty($periodValue) ){
		// 			$periodValue = '00:00:00 - 23:59:59';
		// 			$jsonArray['daily'] = $periodValue;
		// 		}
		// 		break;
		// 	case 'weekly':
		// 		if( ! empty($periodValue)
		// 			&&  1 <= $periodValue && $periodValue <= 7 // periodValue : 1~7
		// 		){

		// 			$jsonArray['weekly'] = $periodValue;
		// 		}
		// 		break;
		// 	case 'monthly':
		// 		if( ! empty($periodValue)
		// 			&&  1 <= $periodValue && $periodValue <= 31 // periodValue : 1~31
		// 		){
		// 			$jsonArray['monthly'] = $periodValue;
		// 		}
		// 		break;
		// 	default:
		// 		break;
		// }
		// if( ! empty($isHourly) ){
		// 	$jsonArray['hourly'] = true;
		// }
		// $jsonArray = array_merge($jsonArray, $extraData);

		// $jsonStr = json_encode($jsonArray);

		// return $jsonStr;
	}// EOF _getPeriodJson




	/**
	 * 設定升級條件 VIP1to2 到玩家上一個等級
	 * （玩家應該至少會是 LV 2）
	 * stepSetupVIP1to2
	 *
	 * @return void
	 */
	public function DELstepSetupVIP1to2InPlayerCurrentLevel($setupUpGrade = '1to2'){
		$return['isDone'] = null;
		$return['resultMessages'] = [];
		$return['list'] = [];

		if( ! $this->isEnableTesting ){
			return $return;
		}

		$this->load->model(['group_level']);
		$thePlayerId = $this->playerId;

		$return = [];
		$isDone = false;
		$resultMessages = [];

		$thePresetVipUpgradeSettings = $this->_preSetup4vipUpgradeSettings();
		$curr1to2ModeKey = $setupUpGrade.'_'. $this->currCase['mode']; // 1to2_cacb / 1to2_casb/ 1to2_sacb/ 1to2_sasb

		/// 取得 Vipsettingcashbackrule 資料是 setting_name = OGP19825-1to2-cacb / casb/ sacb/ sasb
		$curr1to2ModeSetting_name = $thePresetVipUpgradeSettings[$curr1to2ModeKey]['setting_name'];
		 /// 不需要升級條件，設定了要測試的升級條件？後面會補更改升級條件的動作。
		$the_vip_upgrade_setting_list = $this->_getVip_upgrade_settingListBySettingName($curr1to2ModeSetting_name);
$this->returnText(__LINE__.'.returnText().The vipsettingcashbackruleList = '. var_export($the_vip_upgrade_setting_list, true) );
		$vipsettingcashbackrule_1to2Mode = $the_vip_upgrade_setting_list[0];
$this->returnText(__LINE__.'.returnText().The curr1to2ModeSetting_name = '. var_export($curr1to2ModeSetting_name, true) );
		/// 有升級條件設定了要測試的升級條件？
		// $result = $this->getVipsettingcashbackruleListByUpgradeSettingName($curr1to2ModeSetting_name);
		// $this->returnText('('.__LINE__.').returnText().getVipsettingcashbackruleListByUpgradeSettingName.result,'. var_export($result, true).'.');
		// if( !empty( $result['list'] ) ){
		// 	$vipsettingcashbackrule_1to2Mode = $result['list'][0];
		// }else{
		// 	$this->returnText('('.__LINE__.').returnText().Pls add the setting,'. $curr1to2ModeSetting_name.'.');
		// 	$vipsettingcashbackrule_1to2Mode = [];
		// }



		/// 取得玩家當下等級，的上一個等級資料。
		$result = $this->player->getPlayerCurrentLevel($thePlayerId);
		$thePlayerCurrentLevel = $result[0];
		// $this->returnText(__METHOD__.'.returnText().The previous thePlayerCurrentLevel='. var_export($thePlayerCurrentLevel, true) );
		if(	$setupUpGrade == '1to2' // upgrade
			|| $setupUpGrade == '2to1' // downgrade
		){
			$vipLevel = $thePlayerCurrentLevel['vipLevel'] - 1;
		}else {
			$vipLevel = $thePlayerCurrentLevel['vipLevel'];
		}
		if(	$setupUpGrade == '1to2'|| $setupUpGrade == '2to3'){
			$gradeMode = 'upgrade';
		}
		if(	$setupUpGrade == '2to1'|| $setupUpGrade == '3to2'){
			$gradeMode = 'downgrade';
		}


		$previousVipsettingcashbackruleId = $this->group_level->getVipLevelIdByLevelNumber($thePlayerCurrentLevel['vipSettingId'], $vipLevel);
		// $this->returnText(__METHOD__.'.returnText().The previous vipsettingcashbackruleId='. $previousVipsettingcashbackruleId.'.');


		$allPlayerLevels = $this->group_level->getAllPlayerLevels(); // gets all player levels
		$levelMap = array();
		foreach ($allPlayerLevels as $lvl) {
			$levelMap[$lvl['vipsettingcashbackruleId']] = $lvl;
		}
		$previousVipsettingcashbackrule = $levelMap[$previousVipsettingcashbackruleId];

		/// 更新  上一個等級資料 的升級條件（upgrade_id）為 OGP19825-1to2-cacb / casb/ sacb/ sasb
		$vipsettingcashbackruleId = $previousVipsettingcashbackruleId;
		$upgrade_id = $vipsettingcashbackrule_1to2Mode['upgrade_id'];
		if($gradeMode == 'upgrade'){
			$targetField = 'vip_upgrade_id';
		}
		if($gradeMode == 'downgrade'){
			$targetField = 'vip_downgrade_id';
		}
		$result = $this->_updateUpgradeIdInVipsettingcashbackrule($upgrade_id, $vipsettingcashbackruleId, $targetField);




		if($gradeMode == 'upgrade'){
			$period_up_down_2 = '{"weekly":"2","hourly":true}';
			$targetField = 'period_up_down_2';
		}
		if($gradeMode == 'downgrade'){
			$period_up_down_2 = '{"weekly":"3","enableDownMaintain":false,"downMaintainUnit":"1","downMaintainTimeLength":"1","downMaintainConditionDepositAmount":"0","downMaintainConditionBetAmount":"0"}';
			$targetField = 'period_down';
		}
		$result = $this->_updatePeriod_up_down_2InVipsettingcashbackrule($period_up_down_2, $vipsettingcashbackruleId, $targetField);

		// $this->returnText(__METHOD__.'.returnText().The previous = '. var_export($previousVipsettingcashbackrule, true) );
		// $this->returnText(__METHOD__.'.returnText().The vipsettingcashbackrule_1to2Mode = '. var_export($vipsettingcashbackrule_1to2Mode, true) );
		$this->returnText(__METHOD__.'.returnText().The result = '. var_export($result, true) );
		// $this->returnText(__METHOD__.'.returnText().The result = '. var_export($result, true) );
		if( ! empty( $result ) ){
			$isUpdated = true;
			$resultMessages[] = '('.__LINE__.') The vipsettingcashbackrule had update vip_upgrade_id = '.$upgrade_id.' ['. $curr1to2ModeSetting_name.'].';
		}

		/// 更新後，重新讀取
		$allPlayerLevels = $this->group_level->getAllPlayerLevels(); // gets all player levels
		$levelMap = array();
		foreach ($allPlayerLevels as $lvl) {
			$levelMap[$lvl['vipsettingcashbackruleId']] = $lvl;
		}
		$previousVipsettingcashbackrule = $levelMap[$previousVipsettingcashbackruleId];
		/// 驗證是否已更新。
		if( $previousVipsettingcashbackrule['vip_upgrade_id'] == $upgrade_id){
			$isDone = true;
			$resultMessages[] = '('.__LINE__.') The player already in '. $curr1to2ModeSetting_name.'.';
		}

		$return['isDone'] = $isDone;
		$return['resultMessages'] = $resultMessages;
		return $return;
	}

	public function getVipsettingcashbackruleListByUpgradeSettingName($theSettingName, $matchedPlayerId = null){
		$return = [];
		$isDone = false;
		$resultMessages = [];

		$curr1to2ModeSetting_name = $theSettingName;
		$vipsettingcashbackruleList = $this->_getVipsettingcashbackruleListByUpgradeSettingName($curr1to2ModeSetting_name);
		// $this->returnText(__METHOD__.'.returnText().vipsettingcashbackruleList: '.var_export($vipsettingcashbackruleList, true));
		if( count( $vipsettingcashbackruleList ) > 0 ){
			// $isVipsettingcashbackruleExist = true;
			if( ! empty($matchedPlayerId) ){
				$vipsettingcashbackruleId = $this->_getVipsettingcashbackruleIdByPlayerId($matchedPlayerId);
				foreach($vipsettingcashbackruleList as $vipsettingcashbackrule){
					if( $vipsettingcashbackrule['vipsettingcashbackruleId'] == $vipsettingcashbackruleId ){
						$isDone = true;
						$resultMessages[] = '('.__LINE__.') The player already in '. $curr1to2ModeSetting_name.'.';
						// $this->returnText(__METHOD__.'.returnText().The player already in 1to2_'. $curr1to2ModeSetting_name.'.');
					}else{
						// $vipsettingcashbackrule['vipSettingId']
						/// @todo $vipsettingcashbackrule['groupName'] need referrence to vipsetting.
						// $vipLevelName = lang($vipsettingcashbackrule['groupName']) . ' - ' . lang($vipsettingcashbackrule['vipLevelName']);
						$vipLevelName = /* lang($vipsettingcashbackrule['groupName']) . ' - ' .*/ lang($vipsettingcashbackrule['vipLevelName']);
						$resultMessages[] = '('.__LINE__.') The '. $vipLevelName. ' already setted in '. $curr1to2ModeSetting_name.'.';
					}
				}
			}else{
				$isDone = true;
				$resultMessages[] = '('.__LINE__.') The '. $curr1to2ModeSetting_name.' is exist.';
			}
			// $thePlayerCurrentLevel = $this->player->getPlayerCurrentLevel($thePlayerId);
		}else{
			$vipsettingcashbackruleList = [];
			// 需要新增一筆 OGP19825-1to2-cacb 升級條件的 VIP 等級
			// $this->returnText(__METHOD__.'.returnText().Pls to add OGP19825-1to2-'. $this->currCase['mode']. '.');
			$resultMessages[] = '('.__LINE__.') Pls to add '. $theSettingName. '.';
		}
		$return['isDone'] = $isDone;
		$return['resultMessages'] = $resultMessages;
		$return['list'] = $vipsettingcashbackruleList;
		return $return;
	} // EOF getVipsettingcashbackruleListByUpgradeSettingNames

	/**
	 * update vipsettingcashbackrule.vip_upgrade_id field or other field,ex:"vip_downgrade_id".
	 *
	 * @param integer $upgrade_id The field, "vipsettingcashbackrule.vip_upgrade_id".
	 * @param integer $vipsettingcashbackruleId The PK field, "vipsettingcashbackrule.vipsettingcashbackruleId".
	 * @return integer The return of CI_DB_driver::affected_rows().
	 */
	private function _updateUpgradeIdInVipsettingcashbackrule($upgrade_id, $vipsettingcashbackruleId, $targetField='vip_downgrade_id'){
		if( ! $this->isEnableTesting ){
			return null;
		}
		$this->load->model(['group_level']);
		$sql = "update vipsettingcashbackrule set $targetField=? where vipsettingcashbackruleId=?";
		return $this->group_level->runRawUpdateInsertSQL($sql, array($upgrade_id, $vipsettingcashbackruleId));
	}
	private function _updatePeriod_up_down_2InVipsettingcashbackrule($period_up_down_2, $vipsettingcashbackruleId, $targetField = 'period_up_down_2'){
		if( ! $this->isEnableTesting ){
			return null;
		}
		$this->load->model(['group_level']);
		$sql = "update vipsettingcashbackrule set $targetField=? where vipsettingcashbackruleId=?";
		return $this->group_level->runRawUpdateInsertSQL($sql, array($period_up_down_2, $vipsettingcashbackruleId));
	}

	private function _getVipsettingcashbackruleIdByPlayerId($playerId){
		$this->load->model(['player']);

		$playerLevel = $this->player->getPlayerCurrentLevel($playerId);
		// $playerLevel[0]['vipSettingId']
		// $playerLevel[0]['vipsettingcashbackruleId']
		// $this->returnText(__METHOD__.'.returnText(): '.var_export($playerLevel, true));

		return $playerLevel[0]['vipsettingcashbackruleId'];
	}

	/**
	 * Get the setting  by setting_name
	 * For hook to vipsettingcashbackrule.vip_upgrade_id while upgrade
	 * and vipsettingcashbackrule.vip_downgrade_id while downgrade
	 *
	 * @param string $settingName The field,"vip_upgrade_setting.setting_name".
	 * @return array The rows array.
	 */
	private function _getVip_upgrade_settingListBySettingName($settingName){
		$this->load->model(['group_level']);
		$this->db->from('vip_upgrade_setting');
		$this->db->where('vip_upgrade_setting.setting_name', $settingName);
		$the_vip_upgrade_setting_list = $this->group_level->runMultipleRowArray();

		// $this->returnText(__METHOD__.'.returnText().theVipUpgradeList: '.var_export($theVipUpgradeList, true));
		return $the_vip_upgrade_setting_list;
	}

	/**
	 * 取得 vipsettingcashbackrule 有設定到 升級條件的設定（ vip_upgrade_setting ）名稱為 $settingName
	 *
	 * @param string $settingName 通常是指 vip_upgrade_setting.setting_name
	 * @return array $vip_upgrade_setting 符合條件的 _getVipsettingcashbackruleListByUpgradeSettingName
	 */
	private function _getVipsettingcashbackruleListByUpgradeSettingName($settingName){
		$this->load->model(['group_level']);
		$this->db->from('vipsettingcashbackrule');
		$this->db->join('vip_upgrade_setting', 'vip_upgrade_setting.upgrade_id=vipsettingcashbackrule.vip_upgrade_id');
		$this->db->where('vip_upgrade_setting.setting_name', $settingName);
		$theVipUpgradeList = $this->group_level->runMultipleRowArray();

		// $this->returnText(__METHOD__.'.returnText().theVipUpgradeList: '.var_export($theVipUpgradeList, true));
		return $theVipUpgradeList;

		// $vip_upgrade_setting = [];
		// if( !empty($theVipUpgradeList) ){
		// 	foreach($theVipUpgradeList as $theVipUpgrade){
		// 		$settingData =  $this->group_level->getSettingData($theVipUpgrade['vip_upgrade_id']);
		// 		array_push($vip_upgrade_setting, $settingData);
		// 	}
		// }
		// $vipsettingcashbackrule = [];
		// if( !empty($theVipUpgradeList) ){
		// }

		// $this->returnText(__METHOD__.'.returnText().vip_upgrade_setting: '.var_export($vip_upgrade_setting, true));
		// return $vip_upgrade_setting;
	}

	// private function getVipsettingcashbackruleRows(){
	// 	$this->load->model(['group_level']);
	// 	$this->group_level->getVIPGroupUpgradeDetails($upgradeId);
	// }

	/// 直接指定玩家等級
	// $newVipLevelId = $this->getVipLevelIdByLevelNumber($playerLevel['vipSettingId'], $playerLevel['vipLevel'] + 1);
	// group_level::adjustPlayerLevel($playerId, $newLevelId)
	// $setting = group_level::getSettingData($playerLevel['vip_downgrade_id']);
	/// 找出 VIP 設定的所有等級資料
	// group_level::getVIPGroupLevels
	// $this->ci->player->getPlayerCurrentLevel($playerId);
	/// 取得 VIP 某等級的詳細
	// group_level::getVipGroupLevelSetting()
	/// 取得 所有 VIP 某等級的詳細
	// $allPlayerLevels = $this->getAllPlayerLevels(); // gets all player levels
	// $levelMap = array();
	// foreach ($allPlayerLevels as $lvl) {
	// 	$levelMap[$lvl['vipsettingcashbackruleId']] = $lvl;
	// }

	/// 測試腳本：
	//
	// 設定 VIP 1 - OGP19825-1to2-cacb , toSet `vipsettingcashbackrule` hook `vip_upgrade_setting`
	// 設定 VIP 2 - OGP19825-2to3-cacb
	// 設定 玩家 在/直接指定 VIP 2
	//
	// 系統 將玩家( VIP 2 ) 升級 （ 到 VIP 3 ）
	//
	// 設定 玩家 直接指定 VIP 2
	//
	// 刪除直接指定：設定玩家 直接指定 VIP 2
	/**
	 * Undocumented function
	 *
	 * @return void
	 */
	public function DELscript4PlayerUpgrade($playerId){

		// test player VIP 2 upgrade
		$this->setCurrCase('cacb');
		$this->stepSetupVIP1to2();
		stepSetupVIP2to3();
		stepClearPlayerVipHistoryAfterTested();
		stepSetPlayerToVip2();
		stepTriggerUpgrade();
		stepReviewResults();

	}

	public function _setCurrCase(){
		$this->currCase['mode'] = 'cacb';
	}

	/**
	 * 偵測 vipUpgradeSettin 所設定的升級條件，是否含有關鍵的設定名稱？
	 *
	 * @param string $mode
	 * @return void
	 */
	public function detect4vipUpgradeSetting($mode = '1to2_sacb'){
		$this->load->model(['group_level']);
		$returnBool = false;
		$counter = 0;
		$thePresetVipUpgradeSettings = $this->_preSetup4vipUpgradeSettings();


		$allPlayerLevels = $this->group_level->getAllPlayerLevels();
		$levelMap = array();
		foreach ($allPlayerLevels as $lvl) {
			$vip_upgrade_id = $lvl['vip_upgrade_id'];
			$upgrade_setting_data = $this->group_level->getSettingData($vip_upgrade_id);
			$lvl['upgrade_setting_data'] = $upgrade_setting_data;
			$levelMap[$lvl['vipsettingcashbackruleId']] = $lvl;

			if( ! empty( $upgrade_setting_data ) ){
				if( $upgrade_setting_data['setting_name'] == $thePresetVipUpgradeSettings[$mode]['setting_name'] ){
					$counter++;
				}
			}
		}

		if($counter == 1){
			$returnBool = true;
		}
		return $returnBool;
	} // EOF detect4vipUpgradeSetting

	/**
	 * 取得 VIP 等級的陣列。
	 * 陣列，key-value 為 vipsettingcashbackruleId-row(vipsettingcashbackrule join vipsetting)
	 *
	 * @param [type] $theVipsettingcashbackruleId
	 * @return void
	 */
	public function _getVipsettingcashbackrule($theVipsettingcashbackruleId){
		$this->load->model(['group_level']);
		$return = [];
		$allPlayerLevels = $this->group_level->getAllPlayerLevels(); // gets all player levels
		$levelMap = array();
		foreach ($allPlayerLevels as $lvl) {
			$levelMap[$lvl['vipsettingcashbackruleId']] = $lvl;
		}

		if( ! empty($levelMap[$theVipsettingcashbackruleId]) ){
			$return = $levelMap[$theVipsettingcashbackruleId];
		}

		return $return;
	}// EOF _getVipsettingcashbackrule

	public function DELstepSetupVIP1to2(){
		$this->load->model(['player', 'group_level']);

		$thePresetVipUpgradeSettings = $this->_preSetup4vipUpgradeSettings();

		$thePlayerId = $this->playerId;
		$thePlayerCurrentLevel = $this->player->getPlayerCurrentLevel($thePlayerId);
		$vipsettingcashbackruleId = $thePlayerCurrentLevel['vipsettingcashbackruleId'];
		$theVipsettingcashbackruleRow = $this->_getVipsettingcashbackrule($vipsettingcashbackruleId);
		$vip_upgrade_id = $theVipsettingcashbackruleRow['vip_upgrade_id'];
		$theVipUgradeSettingRow = $this->group_level->getSettingData($vip_upgrade_id);

		$isNeedSetupVIP1to2 = false;

		$curr1to2ModeKey = '1to2_'. $this->currCase['mode'];
		$curr1to2ModeSetting_name = $thePresetVipUpgradeSettings[$curr1to2ModeKey]['setting_name'];
		if( $theVipUgradeSettingRow['setting_name'] != $curr1to2ModeSetting_name ){
			$isNeedSetupVIP1to2 = true;
		}
		if($isNeedSetupVIP1to2){
			// @todo 取得 VIP設定 vipsettingcashbackrule 有 1to2_cacb 的
		}
	}


	//
	// php public/index.php cli/testing_ogp21051/index/test_UpgradeFromSBE 242752 999999 1 | w3m -T text/html > ../logs/ogp21051.242752.test_UpgradeFromSBE.log &
	/**
	 * Undocumented function
	 *
	 * @param array $theTestPlayerInfo
	 * @param [type] $limitCaseAmount
	 * @param boolean $isIgnoreAccumulationCases
	 * @return void
	 */
	public function test_UpgradeFromSBE($theTestPlayerInfo = [] // # 1
		, $limitCaseAmount = 999999 // # 2
		, $isIgnoreAccumulationCases = false // # 3
	){
		$isTriggerFromHourlyCronjob = false;
		$manual_batch = true;
		return $this->test_UpgradeFromCronjob($theTestPlayerInfo
						, $limitCaseAmount
						, $isTriggerFromHourlyCronjob
						, $isIgnoreAccumulationCases
						, $manual_batch );

	}

	/**
	 * Test the Upgrade Check simulated trigger from the cronjob, "Check upgrade level hourly".
	 *
	 * @param array $theTestPlayerInfo
	 * @param [type] $limitCaseAmount
	 * @param boolean $isIgnoreAccumulationCases
	 * @return void
	 */
	public function test_UpgradeFromHourlyCronjob($theTestPlayerInfo = [] // # 1
		, $limitCaseAmount = 999999 // # 2
		, $isIgnoreAccumulationCases = false
	){
		$isTriggerFromHourlyCronjob = true;
		$isIgnoreAccumulationCases = false;
		$manual_batch = false;
		return $this->test_UpgradeFromCronjob($theTestPlayerInfo
						, $limitCaseAmount
						, $isTriggerFromHourlyCronjob
						, $isIgnoreAccumulationCases
						, $manual_batch );

	}// EOF test_UpgradeFromHourlyCronjob
	//
	/**
	 * Test the Upgrade Check simulated trigger from the cronjob, "Upgrade level daily".
	 * URI,
	 * http://admin.og.local/cli/testing_ogp21051/index/test_UpgradeFromCronjob/5357/3
	 * Cli,
	 * php public/index.php cli/testing_ogp21051/index/test_UpgradeFromCronjob 5357 3 | w3m -T text/html > ../logs/ogp21051.5357.test_UpgradeFromCronjob.log
	 *
	 * Ignore the related Accumulation setting and simulate the Cronjob,"Upgrade level daily" testing.
	 * The Cli,
	 * <code>
	 * php public/index.php cli/testing_ogp21051/index/test_UpgradeFromCronjob 5357 999999 0 1 | w3m -T text/html > ../logs/ogp21051.5357.test_UpgradeFromCronjob.log
	 * <code>
	 *
	 * To simulate the Cronjob,"Check upgrade level hourly" testing with Include the related Accumulation setting,
	 * The Cli,
	 * <code>
	 * php public/index.php cli/testing_ogp21051/index/test_UpgradeFromCronjob 5357 999999 1 0 | w3m -T text/html > ../logs/ogp21051.5357.test_UpgradeFromHourlyCronjob.log
	 * <code>
	 *
	 * @param array|integer $theTestPlayerInfo If it is array, the array should be return from "utils4testogp::_searchTestPlayerByPlayerId()" or 'utils4testogp::_searchTestPlayerList()'.
	 * If it is numeric,(number string or integer), the integer should be the field, 'player.player_id'.
	 * If it is empty, that's means get the test player from utils4testogp::_searchTestPlayerList().
	 *
	 * @param integer $limitCaseAmount The Combined Case amount limit.default will be all cases.
	 * @param integer|bool $isTriggerFromHourlyCronjob The HourlyCronjob Mode, To simulated trigger from the Cronjob,"Check upgrade level hourly".
	 * If 1 mean simulate the Cronjob,"Check upgrade level hourly".
	 * If 0 (zero) mean simulate the Cronjob,"Upgrade level daily".
	 * @param integer|bool $isIgnoreAccumulationCases Test and ignore the related Accumulation setting. The test case amount less than the include amount.
	 * If 1 mean to ignore, if 0 (zero) mean include the related Accumulation setting case.
	 * @param integer $manual_batch The manual Mode, or the batch mode. If false means batch, if true means manual.
	 * @return void
	 */
	public function test_UpgradeFromCronjob($theTestPlayerInfo = [] // # 1
		, $limitCaseAmount = 999999 // # 2
		, $isTriggerFromHourlyCronjob = false // # 3
		, $isIgnoreAccumulationCases = false // # 4
		, $manual_batch = false // #5 If false means batch, if true means manual.
	){
// var_dump('debugIn'.__LINE__);
		if( ! $this->isEnableTesting ){
			return false;
		}
		$this->load->model(['group_level']);
		$now = new Datetime();
// var_dump('debugIn'.__LINE__);
		// $isTriggerFromHourlyCronjob = false;
		if( empty($theTestPlayerInfo) ){
			$offsetDayRange = '180';
			$limit = 5;
			$params = [$offsetDayRange, $limit];
			$rows = call_user_func_array([$this->utils4testogp, '_searchTestPlayerListFilteredLowestLevel'], $params); // $rows = $this->utils4testogp->_searchTestPlayerList($offsetDayRange, $limit);
			$theTestPlayerInfo = $rows[0]; // for $rows[3] player_id=5357
		}
// var_dump('debugIn'.__LINE__);
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
// var_dump('debugIn'.__LINE__);
		// OnHourlyCheckUpgradeEnable => isHourlyInSetting
		// $theCombinedCase = 'CACB.DailyPeriodMode.NextLevelUpDailyPeriodMode.PeriodIsMet.NextLevelUpPeriodIsMet.OnHourlyCheckUpgradeEnable.NoAccumulation.NextLevelUpNoAccumulation.OffMultipleUpgradeEnable';
		// $theCombinedCase = 'CACB.DailyPeriodMode.NextLevelUpDailyPeriodMode.PeriodIsMet.NextLevelUpPeriodIsMet.OnHourlyCheckUpgradeEnable.NoAccumulation.NextLevelUpNoAccumulation.OnMultipleUpgradeEnable';
		//
		// $theCombinedCase = 'CACB.DailyPeriodMode.NextLevelUpDailyPeriodMode.PeriodIsMet.NextLevelUpPeriodIsMet.OffHourlyCheckUpgradeEnable.NoAccumulation.NextLevelUpNoAccumulation.OnMultipleUpgradeEnable'; // debug in adjust test player VIP level.
		// $theCombinedCase = 'CACB.DailyPeriodMode.NextLevelUpDailyPeriodMode.PeriodIsMet.NextLevelUpPeriodIsMet.OnHourlyCheckUpgradeEnable.NoAccumulation.NextLevelUpNoAccumulation.OnMultipleUpgradeEnable'; // debug in isMetScheduleDate()
// 		$theCombinedCase = 'CACB.Weekly1PeriodMode.NextLevelUpDailyPeriodMode.PeriodIsMet.NextLevelUpPeriodIsMet.OffHourlyCheckUpgradeEnable.AccumulationYesLastChangePeriod.NextLevelUpAccumulationYesLastChangePeriod.OnMultipleUpgradeEnable'; // debug with $detectCase  in _getTestConditionFnFromCombinedCase4ogp21051upgrade()
// // $detectCase = 'CACB.Weekly1PeriodMode.NextLevelUpDailyPeriodMode.PeriodIsMet.NextLevelUpPeriodIsMet.OffHourlyCheckUpgradeEnable.AccumulationYesLastChangePeriod.NextLevelUpAccumulationYesLastChangePeriod.OnMultipleUpgradeEnable';
// // $detectCase = 'CACB.DailyPeriodMode.NextLevelUpDailyPeriodMode.PeriodIsMet.NextLevelUpPeriodIsMet.OffHourlyCheckUpgradeEnable.NoAccumulation.NextLevelUpAccumulationYesRegistrationDate.OnMultipleUpgradeEnable';
		// $theCombinedCase = 'CACB.DailyPeriodMode.NextLevelUpWeekly1PeriodMode.PeriodIsMet.NextLevelUpPeriodIsMet.OffHourlyCheckUpgradeEnable.NoAccumulation.NextLevelUpNoAccumulation.OnMultipleUpgradeEnable'; // debug on To filter the PeriodMode Not Equire to NextLevelUpPeriodMode, change to _testConditionFn4beforeDiffAfterV2.
		// $theCombinedCase = 'CACB.Weekly1PeriodMode.NextLevelUpWeekly1PeriodMode.PeriodIsMet.NextLevelUpPeriodNotMet.OffHourlyCheckUpgradeEnable.NoAccumulation.NextLevelUpNoAccumulation.OnMultipleUpgradeEnable'; // debug in _getPeriodModeAndPeriodValueFromCombinedCase()
		// $theCombinedCase = 'CASB.DailyPeriodMode.NextLevelUpDailyPeriodMode.PeriodIsMet.NextLevelUpPeriodIsMet.OffHourlyCheckUpgradeEnable.AccumulationYesRegistrationDate.NextLevelUpAccumulationYesRegistrationDate.OnMultipleUpgradeEnable'; // _testConditionFn4beforeDiffOneMoreAsAfterV2 // debug in _getUpgradeLevelSettingFnAndSettingNameFromCombinedCase()
		// SASB.DailyPeriodMode.NextLevelUpDailyPeriodMode.PeriodIsMet.NextLevelUpPeriodIsMet.OffHourlyCheckUpgradeEnable.NoAccumulation.NextLevelUpNoAccumulation.OnMultipleUpgradeEnable // debug in _getUpgradeLevelSettingFnAndSettingNameFromCombinedCase()
		// // 05-19
		// $theCombinedCase = 'CACB.DailyPeriodMode.NextLevelUpDailyPeriodMode.PeriodIsMet.NextLevelUpPeriodIsMet.OnHourlyCheckUpgradeEnable.NoAccumulation.NextLevelUpNoAccumulation.OnMultipleUpgradeEnable'; // debug in isMetScheduleDate(),  line no.3823
		// $theCombinedCase = 'CACB.Weekly1PeriodMode.NextLevelUpDailyPeriodMode.PeriodNotMet.NextLevelUpPeriodIsMet.OnHourlyCheckUpgradeEnable.NoAccumulation.NextLevelUpNoAccumulation.OnMultipleUpgradeEnable'; // test failed at 216,continue from 210
		// $theCombinedCase = 'CACB.Weekly1PeriodMode.NextLevelUpWeekly1PeriodMode.PeriodIsMet.NextLevelUpPeriodIsMet.OnHourlyCheckUpgradeEnable.NoAccumulation.NextLevelUpNoAccumulation.OnMultipleUpgradeEnable'; // debug in cmd,
		// $theCombinedCase = 'CACB.Weekly1PeriodMode.NextLevelUpDailyPeriodMode.PeriodNotMet.NextLevelUpPeriodIsMet.OnHourlyCheckUpgradeEnable.NoAccumulation.NextLevelUpNoAccumulation.OnMultipleUpgradeEnable'; // debug in test data,
		// $theCombinedCase = 'CACB.Weekly1PeriodMode.NextLevelUpWeekly1PeriodMode.PeriodIsMet.NextLevelUpPeriodIsMet.OnHourlyCheckUpgradeEnable.AccumulationYesRegistrationDate.NextLevelUpAccumulationYesRegistrationDate.OnMultipleUpgradeEnable'; // debug in test data,// test failed at 948
		// 05-20
		// $theCombinedCase = 'CACB.Weekly1PeriodMode.NextLevelUpDailyPeriodMode.PeriodNotMet.NextLevelUpPeriodIsMet.OffHourlyCheckUpgradeEnable.OnMultipleUpgradeEnable'; // debug in isMetScheduleDate(). need retest all case.
		// $theCombinedCase = 'CACB.Weekly1PeriodMode.NextLevelUpDailyPeriodMode.PeriodNotMet.NextLevelUpPeriodIsMet.OnHourlyCheckUpgradeEnable.OnMultipleUpgradeEnable'; // debug for test_UpgradeFromHourlyCronjob // patch in batchUpDownLevel()
		// $theCombinedCase = 'CACB.Weekly1PeriodMode.NextLevelUpDailyPeriodMode.PeriodIsMet.NextLevelUpPeriodIsMet.OffHourlyCheckUpgradeEnable.OnMultipleUpgradeEnable'; // patch in _getTestConditionFnFromCombinedCase4ogp21051upgrade()
		// $theCombinedCase = 'CACB.DailyPeriodMode.NextLevelUpWeekly1PeriodMode.PeriodIsMet.NextLevelUpPeriodIsMet.OnHourlyCheckUpgradeEnable.OnMultipleUpgradeEnable'; // patch in test_UpgradeFromCronjob()
		//
		// $theCombinedCase = 'CACB.Weekly1PeriodMode.NextLevelUpDailyPeriodMode.PeriodIsMet.NextLevelUpPeriodIsMet.OnHourlyCheckUpgradeEnable.OnMultipleUpgradeEnable","caseTotalAmount'; // patch in _getMetTimeExecBeginOfPeriodAndNextLevelUpFromCombinedCase()
		//
		// $_combinedCaseList[] = $theCombinedCase;
		//
		///
		$doOutputHtml = false;
		$isAppendTestConditionFn = false;
		// $isTriggerFromHourlyCronjob = false; // source from params
		// $isIgnoreAccumulationCases = false; // source from params
		// $isIgnoreAccumulationCases = 2; // TEST
		$theCombinedCaseList = $this->test_getCombinedCaseList4upgrade($doOutputHtml
										, $isAppendTestConditionFn
										, $isTriggerFromHourlyCronjob
										, $isIgnoreAccumulationCases ); // @todo
		$_combinedCaseList = array_slice($theCombinedCaseList, 0, $limitCaseAmount);
		//
		/// 05-18
		// $_combinedCaseList = array_slice($theCombinedCaseList, 1280, $limitCaseAmount); // continue from 1280, after debug in 1286.
		// $_combinedCaseList = array_slice($theCombinedCaseList, 2715, $limitCaseAmount); // continue from 2715, after debug in 2718.
		// $_combinedCaseList = array_slice($theCombinedCaseList, 0, 900); // test code doe develop, ignore for online
		/// 05-19
		// $_combinedCaseList = array_slice($theCombinedCaseList, 210, $limitCaseAmount); // test failed at 216,continue from 210
		// $_combinedCaseList = array_slice($theCombinedCaseList, 250, $limitCaseAmount); // test failed at 210+50,continue from 250
		// $_combinedCaseList = array_slice($theCombinedCaseList, 945, $limitCaseAmount); // test failed at 948,continue from 945
		// $_combinedCaseList = array_slice($theCombinedCaseList, 20, $limitCaseAmount);
		//
		// $_combinedCaseList = array_slice($theCombinedCaseList, 130, $limitCaseAmount); // continue from 120, after debug in 128.
		// $_combinedCaseList = array_slice($theCombinedCaseList, 45, $limitCaseAmount); // continue from 45, after debug in 48.
		// $_combinedCaseList = array_slice($theCombinedCaseList, 2015, $limitCaseAmount); // continue from 2015, after debug in 2017.
		// $_combinedCaseList = array_slice($theCombinedCaseList, 4030, $limitCaseAmount); // continue from 4030, after debug in 4032.
		// $_combinedCaseList = array_slice($theCombinedCaseList, 1129, $limitCaseAmount); /// continue
		//
		// $_combinedCaseList = array_slice($theCombinedCaseList, 2686, $limitCaseAmount); /// for check 5946 ~ 5951
		// // $_combinedCaseList = array_slice($theCombinedCaseList, 6046, $limitCaseAmount); /// for check 5946 ~ 5951
		// $_combinedCaseList = array_slice($theCombinedCaseList, 5950, $limitCaseAmount); /// for check [5950] => SASB.Monthly5PeriodMode.NextLevelUpMonthly5PeriodMode.HadDepositMetedInPreconditions.PeriodIsMet.NextLevelUpPeriodIsMet.OffHourlyCheckUpgradeEnable.AccumulationYesLastChangePeriod.OnlyMetBetOfBetDepositInFormula.OnMultipleUpgradeEnable.ExpectedNoUpgrade
		/// {"message":"upgrade isConditionMet","context":["54169",true,"$remark:",{"rule":"game_platform_id_5674 >= 0 or game_type_id_561 >= 0 and deposit_amount < 0","rlt":"0 >= 0 or 0 >= 0 and 0 < 0","rule4human":"5674 >= 0 or game_type_id_561 >= 0 and Deposit < 0","game_id4human_list":{"game_platform_id_5674":"5674","game_type_id_561":"game_type_id_561"},"initialAmount":{"total_bet":0,"deposit":0,"total_win":0,"total_loss":0,"separated_bet":[]},"isMetOffsetRules":{"bool":false,"cuz":"3772 : empty($isUseOffsetUpGradeRulesAfterDownGrade )"},"separate_accumulation_settings":"{\"bet_amount\": {\"accumulation\": \"4\"}, \"win_amount\": {\"accumulation\": \"4\"}, \"loss_amount\": {\"accumulation\": \"4\"}, \"deposit_amount\": {\"accumulation\": \"4\"}}","separate_accumulation_calcResult":{"details":{"separated_bet":{"0":{"type":"game_platform","value":0,"math_sign":">=","game_platform_id":"5674","result_amount":0,"count":0,"game_platform_id_5674":0},"1":{"type":"game_type","value":0,"math_sign":">=","game_type_id":"561","precon_logic_flag":"or","result_amount":0,"count":0,"game_type_id_561":0},"from":"2022-02-04 16:04:08","to":"2022-02-04 16:24:08","enforcedDetails":null},"deposit":{"count":0,"from":"2022-02-04 16:04:08","to":"2022-02-04 16:24:08"}},"total_bet":null,"separated_bet":[{"type":"game_platform","value":0,"math_sign":">=","game_platform_id":"5674","result_amount":0,"count":0,"game_platform_id_5674":0},{"type":"game_type","value":0,"math_sign":">=","game_type_id":"561","precon_logic_flag":"or","result_amount":0,"count":0,"game_type_id_561":0}],"deposit":0},"groupLevelName":"S8 - Silver 1","setting_name":"devUpgradeIsMet.SASB"}],"level":100,"level_name":"DEBUG","channel":"default-og","datetime":"2022-02-04T16:24:08+08:00","trace":"../../models/group_level.php:2686@playerLevelAdjust > ../../controllers/modules/lock_app_module.php:116@{closure} > ","extra":{"tags":{"request_id":"459e80e72898006c1130920e494618a6","env":"live.og_local","version":"6.146.01.001","hostname":"default-og"},"process_id":99154,"memory_peak_usage":"36.25 MB","memory_usage":"34.25 MB"}}
		// issue 1. "rule4human":"5674 >= 0 or game_type_id_561 >= 0 and Deposit < 0"
		// => 因為找不到 game_platform_id=5674 的流水，所以放置了 5674 進來，但是有可能跟 amount 混淆，所以，預計要另外給 "not_found_game_platform_id_" 前綴詞。
		// issue 2. "rule":"game_platform_id_5674 >= 0 or game_type_id_561 >= 0 and deposit_amount < 0","rlt":"0 >= 0 or 0 >= 0 and 0 < 0"
		// => 因為 game_platform_id_5674 >= 0 or game_type_id_561 >= 0 ，這段條件，是一起的。若使用了 OR 則會分開判斷邏輯，導致，其中一個為真，整段皆為真。預計括號 () 起來：「(game_platform_id_5674 >= 0 or game_type_id_561 >= 0) and deposit_amount < 0」。
		// Done 這兩者不用互搭： HadDepositMetedInPreconditions, AccumulationYesLastChangePeriod 。 xxxInPreconditions 只限制搭配於 AccumulationLastChangePeriodResetIfMet
		// @todo,  NextLevelUpWeekly1PeriodMode? 在 CACB, CASB and SASB  的表現。
		// /// 暫停在 [288] => CASB.Weekly1PeriodMode.NextLevelUpWeekly1PeriodMode.IngoreInPreconditions.PeriodNotMet.NextLevelUpPeriodNotMet.OnHourlyCheckUpgradeEnable.NoAccumulation.AllNoMetInFormula.OnMultipleUpgradeEnable.ExpectedNoUpgrade
		// 抽查 [265]~[270]
		// $_combinedCaseList = array_slice($theCombinedCaseList, 270, $limitCaseAmount);
	// CACB
	// Weekly1PeriodMode,
	// [57] => CACB.Weekly1PeriodMode.NextLevelUpWeekly1PeriodMode.IngoreInPreconditions.PeriodIsMet.NextLevelUpPeriodIsMet.OffHourlyCheckUpgradeEnable.NoAccumulation.OnlyMetBetDepositWinOfAllInFormula.OffMultipleUpgradeEnable.ExpectedNoUpgrade
    // [58] => CACB.Weekly1PeriodMode.NextLevelUpWeekly1PeriodMode.IngoreInPreconditions.PeriodIsMet.NextLevelUpPeriodIsMet.OffHourlyCheckUpgradeEnable.NoAccumulation.AllMetInFormula.OnMultipleUpgradeEnable.ExpectedMultiUpgrade
    // [59] => CACB.Weekly1PeriodMode.NextLevelUpWeekly1PeriodMode.IngoreInPreconditions.PeriodIsMet.NextLevelUpPeriodIsMet.OffHourlyCheckUpgradeEnable.NoAccumulation.AllMetInFormula.OffMultipleUpgradeEnable.ExpectedUpgrade
    // [60] => CACB.Weekly1PeriodMode.NextLevelUpWeekly1PeriodMode.IngoreInPreconditions.PeriodIsMet.NextLevelUpPeriodIsMet.OffHourlyCheckUpgradeEnable.NoAccumulation.OnlyMetBetOfBetInFormula.OnMultipleUpgradeEnable.ExpectedMultiUpgrade
    // [61] => CACB.Weekly1PeriodMode.NextLevelUpWeekly1PeriodMode.IngoreInPreconditions.PeriodIsMet.NextLevelUpPeriodIsMet.OffHourlyCheckUpgradeEnable.NoAccumulation.OnlyMetBetOfBetInFormula.OffMultipleUpgradeEnable.ExpectedUpgrade
    // [62] => CACB.Weekly1PeriodMode.NextLevelUpWeekly1PeriodMode.IngoreInPreconditions.PeriodIsMet.NextLevelUpPeriodIsMet.OffHourlyCheckUpgradeEnable.NoAccumulation.OnlyMetBetOfBetDepositInFormula.OnMultipleUpgradeEnable.ExpectedNoUpgrade
	// DailyPeriodMode
	// v[57] => CACB.DailyPeriodMode.NextLevelUpDailyPeriodMode.NeverMetedInPreconditions.PeriodIsMet.NextLevelUpPeriodIsMet.OffHourlyCheckUpgradeEnable.NoAccumulation.OnlyMetBetDepositWinOfAllInFormula.OffMultipleUpgradeEnable.ExpectedNoUpgrade
    // v[58] => CACB.DailyPeriodMode.NextLevelUpDailyPeriodMode.NeverMetedInPreconditions.PeriodIsMet.NextLevelUpPeriodIsMet.OffHourlyCheckUpgradeEnable.NoAccumulation.AllMetInFormula.OnMultipleUpgradeEnable.ExpectedMultiUpgrade
    // v[59] => CACB.DailyPeriodMode.NextLevelUpDailyPeriodMode.NeverMetedInPreconditions.PeriodIsMet.NextLevelUpPeriodIsMet.OffHourlyCheckUpgradeEnable.NoAccumulation.AllMetInFormula.OffMultipleUpgradeEnable.ExpectedUpgrade
    // v[60] => CACB.DailyPeriodMode.NextLevelUpDailyPeriodMode.NeverMetedInPreconditions.PeriodIsMet.NextLevelUpPeriodIsMet.OffHourlyCheckUpgradeEnable.NoAccumulation.OnlyMetBetOfBetInFormula.OnMultipleUpgradeEnable.ExpectedMultiUpgrade
    // v[61] => CACB.DailyPeriodMode.NextLevelUpDailyPeriodMode.NeverMetedInPreconditions.PeriodIsMet.NextLevelUpPeriodIsMet.OffHourlyCheckUpgradeEnable.NoAccumulation.OnlyMetBetOfBetInFormula.OffMultipleUpgradeEnable.ExpectedUpgrade
    // v[62] => CACB.DailyPeriodMode.NextLevelUpDailyPeriodMode.NeverMetedInPreconditions.PeriodIsMet.NextLevelUpPeriodIsMet.OffHourlyCheckUpgradeEnable.NoAccumulation.OnlyMetBetOfBetDepositInFormula.OnMultipleUpgradeEnable.ExpectedNoUpgrade
	// CASB
	// Weekly1PeriodMode,
	// v[265] => CASB.Weekly1PeriodMode.NextLevelUpWeekly1PeriodMode.IngoreInPreconditions.PeriodIsMet.NextLevelUpPeriodIsMet.OffHourlyCheckUpgradeEnable.AccumulationYesRegistrationDate.OnlyMetBetDepositWinOfAllInFormula.OffMultipleUpgradeEnable.ExpectedNoUpgrade
    // v[266] => CASB.Weekly1PeriodMode.NextLevelUpWeekly1PeriodMode.IngoreInPreconditions.PeriodIsMet.NextLevelUpPeriodIsMet.OffHourlyCheckUpgradeEnable.AccumulationYesRegistrationDate.AllMetInFormula.OnMultipleUpgradeEnable.ExpectedMultiUpgrade
    // v[267] => CASB.Weekly1PeriodMode.NextLevelUpWeekly1PeriodMode.IngoreInPreconditions.PeriodIsMet.NextLevelUpPeriodIsMet.OffHourlyCheckUpgradeEnable.AccumulationYesRegistrationDate.AllMetInFormula.OffMultipleUpgradeEnable.ExpectedUpgrade
    // v[268] => CASB.Weekly1PeriodMode.NextLevelUpWeekly1PeriodMode.IngoreInPreconditions.PeriodIsMet.NextLevelUpPeriodIsMet.OffHourlyCheckUpgradeEnable.AccumulationYesRegistrationDate.OnlyMetBetOfBetInFormula.OnMultipleUpgradeEnable.ExpectedMultiUpgrade
    // v[269] => CASB.Weekly1PeriodMode.NextLevelUpWeekly1PeriodMode.IngoreInPreconditions.PeriodIsMet.NextLevelUpPeriodIsMet.OffHourlyCheckUpgradeEnable.AccumulationYesRegistrationDate.OnlyMetBetOfBetInFormula.OffMultipleUpgradeEnable.ExpectedUpgrade
    // v[270] => CASB.Weekly1PeriodMode.NextLevelUpWeekly1PeriodMode.IngoreInPreconditions.PeriodIsMet.NextLevelUpPeriodIsMet.OffHourlyCheckUpgradeEnable.AccumulationYesRegistrationDate.OnlyMetBetOfBetDepositInFormula.OnMultipleUpgradeEnable.ExpectedNoUpgrade
	// DailyPeriodMode,
	// v[2681] => CASB.DailyPeriodMode.NextLevelUpMonthly5PeriodMode.HadBetMetedInPreconditions.PeriodIsMet.NextLevelUpPeriodNotMet.OffHourlyCheckUpgradeEnable.AccumulationYesLastChangePeriod.OnlyMetBetDepositWinOfAllInFormula.OffMultipleUpgradeEnable.ExpectedNoUpgrade
    // v[2682] => CASB.DailyPeriodMode.NextLevelUpMonthly5PeriodMode.HadBetMetedInPreconditions.PeriodIsMet.NextLevelUpPeriodNotMet.OffHourlyCheckUpgradeEnable.AccumulationYesLastChangePeriod.AllMetInFormula.OnMultipleUpgradeEnable.ExpectedUpgrade
    // v[2683] => CASB.DailyPeriodMode.NextLevelUpMonthly5PeriodMode.HadBetMetedInPreconditions.PeriodIsMet.NextLevelUpPeriodNotMet.OffHourlyCheckUpgradeEnable.AccumulationYesLastChangePeriod.AllMetInFormula.OffMultipleUpgradeEnable.ExpectedUpgrade
    // v[2684] => CASB.DailyPeriodMode.NextLevelUpMonthly5PeriodMode.HadBetMetedInPreconditions.PeriodIsMet.NextLevelUpPeriodNotMet.OffHourlyCheckUpgradeEnable.AccumulationYesLastChangePeriod.OnlyMetBetOfBetInFormula.OnMultipleUpgradeEnable.ExpectedUpgrade
    // v[2685] => CASB.DailyPeriodMode.NextLevelUpMonthly5PeriodMode.HadBetMetedInPreconditions.PeriodIsMet.NextLevelUpPeriodNotMet.OffHourlyCheckUpgradeEnable.AccumulationYesLastChangePeriod.OnlyMetBetOfBetInFormula.OffMultipleUpgradeEnable.ExpectedUpgrade
    // v[2686] => CASB.DailyPeriodMode.NextLevelUpMonthly5PeriodMode.HadBetMetedInPreconditions.PeriodIsMet.NextLevelUpPeriodNotMet.OffHourlyCheckUpgradeEnable.AccumulationYesLastChangePeriod.OnlyMetBetOfBetDepositInFormula.OnMultipleUpgradeEnable.ExpectedNoUpgrade
	// SACB
	//
	// SASB
	// Weekly1PeriodMode,
	// [473] => SASB.Weekly1PeriodMode.NextLevelUpWeekly1PeriodMode.IngoreInPreconditions.PeriodIsMet.NextLevelUpPeriodIsMet.OffHourlyCheckUpgradeEnable.AccumulationYesLastChangePeriod.OnlyMetBetDepositWinOfAllInFormula.OffMultipleUpgradeEnable.ExpectedNoUpgrade
    // [474] => SASB.Weekly1PeriodMode.NextLevelUpWeekly1PeriodMode.IngoreInPreconditions.PeriodIsMet.NextLevelUpPeriodIsMet.OffHourlyCheckUpgradeEnable.AccumulationYesLastChangePeriod.AllMetInFormula.OnMultipleUpgradeEnable.ExpectedMultiUpgrade
    // [475] => SASB.Weekly1PeriodMode.NextLevelUpWeekly1PeriodMode.IngoreInPreconditions.PeriodIsMet.NextLevelUpPeriodIsMet.OffHourlyCheckUpgradeEnable.AccumulationYesLastChangePeriod.AllMetInFormula.OffMultipleUpgradeEnable.ExpectedUpgrade
    // [476] => SASB.Weekly1PeriodMode.NextLevelUpWeekly1PeriodMode.IngoreInPreconditions.PeriodIsMet.NextLevelUpPeriodIsMet.OffHourlyCheckUpgradeEnable.AccumulationYesLastChangePeriod.OnlyMetBetOfBetInFormula.OnMultipleUpgradeEnable.ExpectedMultiUpgrade
    // [477] => SASB.Weekly1PeriodMode.NextLevelUpWeekly1PeriodMode.IngoreInPreconditions.PeriodIsMet.NextLevelUpPeriodIsMet.OffHourlyCheckUpgradeEnable.AccumulationYesLastChangePeriod.OnlyMetBetOfBetInFormula.OffMultipleUpgradeEnable.ExpectedUpgrade
    // [478] => SASB.Weekly1PeriodMode.NextLevelUpWeekly1PeriodMode.IngoreInPreconditions.PeriodIsMet.NextLevelUpPeriodIsMet.OffHourlyCheckUpgradeEnable.AccumulationYesLastChangePeriod.OnlyMetBetOfBetDepositInFormula.OnMultipleUpgradeEnable.ExpectedNoUpgrade
	// Monthly5PeriodMode,
	// v[5946] => SASB.Monthly5PeriodMode.NextLevelUpMonthly5PeriodMode.HadDepositMetedInPreconditions.PeriodIsMet.NextLevelUpPeriodIsMet.OffHourlyCheckUpgradeEnable.AccumulationYesLastChangePeriod.AllMetInFormula.OnMultipleUpgradeEnable.ExpectedMultiUpgrade
    // v[5947] => SASB.Monthly5PeriodMode.NextLevelUpMonthly5PeriodMode.HadDepositMetedInPreconditions.PeriodIsMet.NextLevelUpPeriodIsMet.OffHourlyCheckUpgradeEnable.AccumulationYesLastChangePeriod.AllMetInFormula.OffMultipleUpgradeEnable.ExpectedUpgrade
    // v[5948] => SASB.Monthly5PeriodMode.NextLevelUpMonthly5PeriodMode.HadDepositMetedInPreconditions.PeriodIsMet.NextLevelUpPeriodIsMet.OffHourlyCheckUpgradeEnable.AccumulationYesLastChangePeriod.OnlyMetBetOfBetInFormula.OnMultipleUpgradeEnable.ExpectedMultiUpgrade
    // v[5949] => SASB.Monthly5PeriodMode.NextLevelUpMonthly5PeriodMode.HadDepositMetedInPreconditions.PeriodIsMet.NextLevelUpPeriodIsMet.OffHourlyCheckUpgradeEnable.AccumulationYesLastChangePeriod.OnlyMetBetOfBetInFormula.OffMultipleUpgradeEnable.ExpectedUpgrade
    // v[5950] => SASB.Monthly5PeriodMode.NextLevelUpMonthly5PeriodMode.HadDepositMetedInPreconditions.PeriodIsMet.NextLevelUpPeriodIsMet.OffHourlyCheckUpgradeEnable.AccumulationYesLastChangePeriod.OnlyMetBetOfBetDepositInFormula.OnMultipleUpgradeEnable.ExpectedNoUpgrade
    // [5951] => SASB.Monthly5PeriodMode.NextLevelUpMonthly5PeriodMode.HadDepositMetedInPreconditions.PeriodIsMet.NextLevelUpPeriodIsMet.OffHourlyCheckUpgradeEnable.AccumulationYesLastChangePeriod.OnlyMetBetOfBetDepositInFormula.OffMultipleUpgradeEnable.ExpectedNoUpgrade
		// $_combinedCaseList = array_slice($theCombinedCaseList, 79, $limitCaseAmount);
		/// debug for Not Expected, [79] => SACB.Weekly1PeriodMode.NeverMetedInPreconditions.PeriodIsMet.OffHourlyCheckUpgradeEnable.AccumulationLastChangePeriodResetIfMet.EmptyUpgradeSetting.IsConditionNotMet.OnlyMetBetOfBetDepositInFormula.OnMultipleUpgradeEnable.ExpectedNoUpgrade

		// $_combinedCaseList = array_slice($theCombinedCaseList, 357, $limitCaseAmount);
		/// debug the BetUpgradeSetting.NextLevelUpDepositUpgradeSetting (BetUpgradeSetting of current Level and DepositUpgradeSetting of Next Level)
		// [144] => SACB.Weekly1PeriodMode.NeverMetedInPreconditions.PeriodIsMet.OnHourlyCheckUpgradeEnable.AccumulationLastChangePeriodResetIfMet.BetUpgradeSetting.NextLevelUpDepositUpgradeSetting.IsConditionMet.AllNoMetInFormula.OnMultipleUpgradeEnable.ExpectedNoUpgrade
		// 144 看不到 下個等級的。所以改用 357
		// debug DepositUpgradeSetting.NextLevelUpBetUpgradeSetting
		// [357] => SACB.Weekly1PeriodMode.NeverMetedInPreconditions.PeriodIsMet.OffHourlyCheckUpgradeEnable.AccumulationLastChangePeriodResetIfMet.DepositUpgradeSetting.NextLevelUpBetUpgradeSetting.IsConditionMet.AllMetInFormula.OnMultipleUpgradeEnable.ExpectedUpgrade

		// $_combinedCaseList = array_slice($theCombinedCaseList, 54, $limitCaseAmount);
		// [56] => SACB.Weekly1PeriodMode.NextLevelUpWeekly1PeriodMode.HadBetMetedInPreconditions.PeriodIsMet.NextLevelUpPeriodIsMet.OffHourlyCheckUpgradeEnable.AccumulationLastChangePeriodResetIfMet.OnlyMetDepositOfBetDepositInFormula.NextLevelUpOnlyMetBetDepositOfAllInFormula.OnMultipleUpgradeEnable.ExpectedUpgrade
		// $_combinedCaseList = array_slice($theCombinedCaseList, 123, $limitCaseAmount);
		// [125] => SACB.Weekly1PeriodMode.NextLevelUpWeekly1PeriodMode.IngoreInPreconditions.PeriodIsMet.NextLevelUpPeriodIsMet.OffHourlyCheckUpgradeEnable.AccumulationLastChangePeriodResetIfMet.AllMetOfBetDepositInFormula.NextLevelUpOnlyMetBetOfBetDepositInFormula.OnMultipleUpgradeEnable.ExpectedUpgrade

		/// AccumulationLastChangePeriodResetIfMet 目前只支援 CB 所以 SB會有非預期結果，沒需求去補丁。
		// $_combinedCaseList = array_slice($theCombinedCaseList, 57, $limitCaseAmount); // skip 56
		// // CASB + AccumulationLastChangePeriodResetIfMet Not Expected
		// // [56] => CASB.Weekly1PeriodMode.NextLevelUpWeekly1PeriodMode.HadBetMetedInPreconditions.PeriodIsMet.NextLevelUpPeriodIsMet.OffHourlyCheckUpgradeEnable.AccumulationLastChangePeriodResetIfMet.OnlyMetDepositOfBetDepositInFormula.NextLevelUpOnlyMetBetDepositOfAllInFormula.OnMultipleUpgradeEnable.ExpectedUpgrade

		// $_combinedCaseList = array_slice($theCombinedCaseList, 29, $limitCaseAmount); // 29 當例子：曾經滿足這個時機點 是「當下」。
		// [61] => SACB.Weekly1PeriodMode.NextLevelUpWeekly1PeriodMode.HadBetMetedInPreconditions.PeriodIsMet.NextLevelUpPeriodIsMet.OffHourlyCheckUpgradeEnable.AccumulationLastChangePeriodResetIfMet.AllMetOfBetDepositInFormula.NextLevelUpOnlyMetBetOfBetDepositInFormula.OnMultipleUpgradeEnable.ExpectedUpgrade
    	// :: pre-BetMeted + AllMetOfBetDeposit(Upgrade) + NextLevelUpOnlyMetBetOfBetDeposit ??? 應該要連升級，為什麼預計只升一級？
		/// [DONE]debug for [13] 應該 AllMetOfBetDeposit 有要升級才對，預期卻為 ExpectedNoUpgrade
		// 因為該VIP SETTING 設定為 OnHourlyCheckUpgradeEnable，而腳本使用非 Hourly 的任務執行。 如下，第三個參數 0 ，表示不使用 Hourly 任務觸發。
		//  $ php public/index.php cli/testing_ogp24373/index/test_UpgradeFromCronjob 54169 999999 0 1 | w3m -T text/html > ../logs/ogp24373.54169.test_UpgradeFromCronjob.log
		// [13] => SACB.Weekly1PeriodMode.NextLevelUpWeekly1PeriodMode.NeverMetedInPreconditions.PeriodIsMet.NextLevelUpPeriodIsMet.OnHourlyCheckUpgradeEnable.AccumulationLastChangePeriodResetIfMet.AllMetOfBetDepositInFormula.NextLevelUpOnlyMetBetOfBetDepositInFormula.OnMultipleUpgradeEnable.ExpectedNoUpgrade
		//
		// $_combinedCaseList = array_slice($theCombinedCaseList, 58, $limitCaseAmount); // 29 疑似會衝上頂級，手動設定下下升級的條件，為不滿足，但有該項（bet/deposit）金額條件。
		// // [58] => SACB.Weekly1PeriodMode.NextLevelUpWeekly1PeriodMode.NeverMetedInPreconditions.PeriodIsMet.NextLevelUpPeriodIsMet.OffHourlyCheckUpgradeEnable.AccumulationLastChangePeriodResetIfMet.AllMetOfBetDepositInFormula.NextLevelUpOnlyMetBetOfBetDepositInFormula.OnMultipleUpgradeEnable.ExpectedMultiUpgrade
		// // 先設定下下升級的條件，為不滿足，但有該項（bet/deposit）金額條件。
		// // 1. 直接把測試腳本開到 [29]去測試
		// // 2. 原先預計結果為升兩級；如果有升三級，就是會有衝到頂級的錯誤。
		$_combinedCaseList = array_slice($theCombinedCaseList, 350, $limitCaseAmount);
		// [352] => CACB.Weekly1PeriodMode.NextLevelUpWeekly1PeriodMode.IngoreInPreconditions.PeriodNotMet.NextLevelUpPeriodNotMet.OffHourlyCheckUpgradeEnable.NoAccumulation.AllMetInFormula.OnMultipleUpgradeEnable.ExpectedNoUpgrade


// var_dump('debugIn'.__LINE__);
		$caseTotalAmount = count($_combinedCaseList);
		foreach($_combinedCaseList as $keyNumber => $currCase){
			$theCombinedCase = $currCase;

			// @todo Dev the following cases, (OGP-25082 Enable Multiple Level Upgrade in VIP)
			// 切開、各自呼叫函式、參數 use parseCombinedCase4Router()

			$this->doHadMetedInPreconditions( $currCase
					, $theTestPlayerInfo
					, $isTriggerFromHourlyCronjob
					, $manual_batch
			);

// var_dump('debugIn'.__LINE__);
			$this->utils->debug_log('7358.theCombinedCase:', $theCombinedCase, 'caseTotalAmount:', $caseTotalAmount, 'keyNumber:', $keyNumber); // for trace the progress rate.
			list($isSA, $isSB) = $this->_getIsSAIsSBFromCombinedCase($theCombinedCase);
// var_dump('debugIn'.__LINE__);
			// // ref. to _getDowngradeLevelSettingFn_getUpgradeLevelSettingFnAndSettingNameFromCombinedCase
			// $getUpgradeLevelSettingFn = '_getDowngradeLevelSettingFn'; // so far, CACB
			list($settingName, $getUpgradeLevelSettingFn) = $this->_getUpgradeLevelSettingFnAndSettingNameFromCombinedCase($theCombinedCase, 'devTestUpgrade', 'upgrade');
			// list($settingName, $getUpgradeLevelSettingFn) = $this->utils4testogp->_getUpgradeLevelSettingFnAndSettingNameFromCombinedCase($theCombinedCase, 'devUpgradeIsMet', 'upgrade'); // disable for the params amount is diff as this.


			$theCombinedCase4nextLevel = $theCombinedCase;
			// clear the current UpgradeSetting, xxxUpgradeSetting.
			$findConditionList = $this->utils4testogp->getUpgradeSettingAndFormulaRelatedList();
			$targetCondition = '';
			$replaceList = array_fill(0, count($findConditionList), $targetCondition);
			$theCombinedCase4nextLevel = str_replace($findConditionList, $replaceList, $theCombinedCase4nextLevel);
			// override for NextLevelUpxxx to xxx, ex: NextLevelUpEmptyUpgradeSetting to EmptyUpgradeSetting
			$findConditionList = $this->utils4testogp->getUpgradeSettingAndFormulaRelatedList('NextLevelUp');
			$replaceList = $this->utils4testogp->getUpgradeSettingAndFormulaRelatedList();
			// $findConditionList = [];
			// $replaceList = [];
			// // for confirm the formula is work
			// $findConditionList[] = '.NextLevelUpEmptyUpgradeSetting';
			// $replaceList[] = '.EmptyUpgradeSetting';
			// $findConditionList[] = '.NextLevelUpBetUpgradeSetting';
			// $replaceList[] = '.BetUpgradeSetting';
			// $findConditionList[] = '.NextLevelUpDepositUpgradeSetting';
			// $replaceList[] = '.DepositUpgradeSetting';
			// $findConditionList[] = '.NextLevelUpDepositBetUpgradeSetting';
			// $replaceList[] = '.DepositBetUpgradeSetting';
			// // for testing script x8
			// $findConditionList[] = '.NextLevelUpAllNoMetInFormula'; // #1
			// $replaceList[] = '.AllNoMetInFormula'; // #1
			// $findConditionList[] = '.NextLevelUpOnlyMetBetOfAllInFormula'; // #2
			// $replaceList[] = '.OnlyMetBetOfAllInFormula'; // #2
			// $findConditionList[] = '.NextLevelUpOnlyMetDepositOfAllInFormula'; // #3
			// $replaceList[] = '.OnlyMetDepositOfAllInFormula'; // #3
			// $findConditionList[] = '.NextLevelUpOnlyMetBetDepositOfAllInFormula'; // #4
			// $replaceList[] = '.OnlyMetBetDepositOfAllInFormula'; // #4
			// $findConditionList[] = '.NextLevelUpOnlyMetBetDepositWinOfAllInFormula'; // #5
			// $replaceList[] = '.OnlyMetBetDepositWinOfAllInFormula'; // #5
			// $findConditionList[] = '.NextLevelUpAllMetInFormula'; // #6
			// $replaceList[] = '.AllMetInFormula'; // #6
			// $findConditionList[] = '.NextLevelUpOnlyMetBetOfBetInFormula'; // #7
			// $replaceList[] = '.OnlyMetBetOfBetInFormula'; // #7
			// $findConditionList[] = '.NextLevelUpOnlyMetBetOfBetDepositInFormula'; // #8
			// $replaceList[] = '.OnlyMetBetOfBetDepositInFormula'; // #8
			$theCombinedCase4nextLevel = str_replace($findConditionList, $replaceList, $theCombinedCase4nextLevel);
			list($settingName4nextLevel, $getNextLevelUpgradeLevelSettingFn) = $this->_getUpgradeLevelSettingFnAndSettingNameFromCombinedCase($theCombinedCase4nextLevel, 'devTestNextLevelUpgrade', 'upgrade');
				var_dump(['debugIn9437', 'settingName:', $settingName
					, 'getUpgradeLevelSettingFn:', $getUpgradeLevelSettingFn
					, 'settingName4nextLevel:', $settingName4nextLevel
					, 'getNextLevelUpgradeLevelSettingFn:', $getNextLevelUpgradeLevelSettingFn
				]);

// var_dump('debugIn'.__LINE__);
			$isPeriodIsMet = $this->utils4testogp->_pos($theCombinedCase, '.PeriodIsMet');
			$isPeriodNotMet = $this->utils4testogp->_pos($theCombinedCase, '.PeriodNotMet');
			$isNextLevelUpPeriodIsMet = $this->utils4testogp->_pos($theCombinedCase, 'NextLevelUpPeriodIsMet');
			$isNextLevelUpPeriodNotMet = $this->utils4testogp->_pos($theCombinedCase, 'NextLevelUpPeriodNotMet');

			/// get $TEB_DateTime only
			$tmpPeriodMode = null;
			$tmpPeriodValue = null;
			if($isPeriodIsMet && $isNextLevelUpPeriodIsMet){
				// 每星期一 每月五號
				$TEB_DateTime = $this->_getMetTimeExecBeginOfPeriodAndNextLevelUpFromCombinedCase($theCombinedCase);
			}else if($isPeriodIsMet && ! $isNextLevelUpPeriodIsMet){
				list($tmpPeriodMode, $tmpPeriodValue, $TEB_DateTime) = $this->_getPeriodModeAndPeriodValueFromCombinedCase($thePlayerId, $theCombinedCase);
			}else if(! $isPeriodIsMet && $isNextLevelUpPeriodIsMet){
				$nextLevelUpTEB_DateTime = new DateTime('now');
				$nextLevelUpTEB_DateTimeStr = $nextLevelUpTEB_DateTime->format('Y-m-d H:i:s'); // nextLevelUpTEB_DateTimeStr for nextPeriod mode
				list($tmpPeriodMode, $tmpPeriodValue, $TEB_DateTime) = $this->_getPeriodModeAndPeriodValueFromCombinedCase($thePlayerId, $theCombinedCase, $nextLevelUpTEB_DateTimeStr);
			}else if(! $isPeriodIsMet && ! $isNextLevelUpPeriodIsMet){
				// 每星期一 每月五號 +1天
				$TEB_DateTime = $this->_getMetTimeExecBeginOfPeriodAndNextLevelUpFromCombinedCase($theCombinedCase);
				$TEB_DateTime->modify('+1 day');
			}
// var_dump('debugIn'.__LINE__);
			$this->utils->debug_log('7387.TEB_DateTime', $TEB_DateTime->format('Y-m-d H:i:s'));
			/// get $periodMode, $periodValue only
			$tmpTEB = null;
			// @todo downgrade test need replace _getPeriodModeAndPeriodValueFromCombinedCase() to _getPeriodModeAndPeriodValueFromCombinedCase()
			// list($periodMode, $periodValue, $TEB_DateTime, $enableLevelMaintainFn) = $this->_getPeriodModeAndPeriodValueAndTEB_DateTimeAndEnableLevelMaintainFnFromCombinedCase($thePlayerId, $theCombinedCase);
// var_dump('debugIn'.__LINE__);
			list($periodMode, $periodValue, $tmpTEB) = $this->_getPeriodModeAndPeriodValueFromCombinedCase($thePlayerId, $theCombinedCase);
			// if($isPeriodNotMet){
			// 	$TEB_DateTime->modify('+ 1 day');
			// }
			/// get $nextLevelUpPeriodMode, $nextLevelUpPeriodValue only
			$nextLevelUpTEB_DateTime = clone $TEB_DateTime;
			$nextLevelUpTEB_DateTime->modify('+ 1 day');
			$nextLevelUpTEB_DateTimeStr = $nextLevelUpTEB_DateTime->format('Y-m-d H:i:s'); // nextLevelUpTEB_DateTimeStr for nextPeriod mode
			list($nextLevelUpPeriodMode, $nextLevelUpPeriodValue, $tmpTEB) = $this->_getPeriodModeAndPeriodValueFromCombinedCase($thePlayerId, $theCombinedCase, $nextLevelUpTEB_DateTimeStr);
			// $TEB_DateTime = clone $tmpTEB;
			// if($isNextLevelUpPeriodNotMet){
			// 	$TEB_DateTime->modify('+ 1 day');
			// }
// var_dump('debugIn'.__LINE__);

			// $testConditionFn = $this->utils4testogp->_getTestConditionFnFromCombinedCase($theCombinedCase); // '_testConditionFn4beforeDiffAfterV2'; // _testConditionFn4beforeDiffAfterV2, _testConditionFn4beforeSameAsAfterV2
			$checkFrom = 'Cronjob';
			if($isTriggerFromHourlyCronjob){
				$checkFrom = 'CronjobHourly';
			}else if( ! empty($manual_batch) ){
				$checkFrom = 'SBE';
			}
// var_dump('debugIn'.__LINE__);
			$testConditionFn = $this->_getTestConditionFnFromCombinedCase4ogp24373upgrade($theCombinedCase, $checkFrom);
// var_dump('debugIn'.__LINE__);
			$isMultipleUpgrade = $this->utils4testogp->_pos($theCombinedCase, 'OnMultipleUpgradeEnable');

			$isHourlyInSetting = $this->utils4testogp->_pos($theCombinedCase, 'OnHourlyCheckUpgradeEnable'); // isHourlyInSetting
// var_dump('debugIn'.__LINE__);
			$params = [ $theTestPlayerInfo // # 1
				, $periodMode // # 2
				, $nextLevelUpPeriodMode // # 2.1
				, $periodValue // # 3
				, $nextLevelUpPeriodValue // # 3.1
				, $TEB_DateTime // # 4
				, $testConditionFn // # 5 is_string for function name
				, $isMultipleUpgrade // $enableLevelMaintainFn // # 6 Level Maintain / Downgrade Guaranteed
				, $isHourlyInSetting // # 7
				, $getUpgradeLevelSettingFn // # 8
				, $isSA // #9 // isSA = false mean CA isAccumulationSeparatedInConfig
				, $isSB // #10 // isSB = false mean CB isBettingSeparatedInConfig
				, $isTriggerFromHourlyCronjob // #11
				, $manual_batch // #12
				, $getNextLevelUpgradeLevelSettingFn // #13
			];
// var_dump('debugIn'.__LINE__);
// var_dump($params);

			var_dump(['7435.will _tryUpgradeSuccessTriggerFromCronjobV2.params', $params, 'datetime:', new DateTime() ]);
			$funcName = '_tryUpgradeSuccessTriggerFromCronjobV2'; // '_tryDowngradeSuccessTriggerFromCronjobV2';
			$rlt = call_user_func_array([$this, $funcName], $params); // $rlt = $this->_tryUpgradeSuccessTriggerFromCronjob($theTestPlayerInfo,...
// var_dump('debugIn'.__LINE__);
            $this->utils->debug_log('7440._tryUpgradeSuccessTriggerFromCronjobV2.parltrams', $rlt);



			/// The "N" content of the "testing_ogp24373.contiue" file, it will be stop next case at anytime.
			// Others will continue with next case until the end.
			$contiueFile = dirname(__FILE__). '/testing_ogp24373.contiue';
			var_dump(['debugIn'.__LINE__, 'contiueFile:', $contiueFile]);
			// // home/vagrant/Code/og/admin/application/controllers/cli/testing_ogp24373.contiue
			if ( file_exists($contiueFile) ) {
				$contiueFileContent = file_get_contents($contiueFile);
				var_dump(['debugIn'.__LINE__, 'contiueFileContent:', $contiueFileContent]);
				if( trim($contiueFileContent) == 'N'){
					break;
				}
			}

			// clear from yesterday.
			$playerId = $theTestPlayerInfo['player_id'];
			$now->modify('- 1 day');
			$nowYmdHis = $now->format('Y-m-d H:i:s');
			$this->try_revertThisCaseData($playerId, $original_vipsettingcashbackruleId, $nowYmdHis);
			// var_dump('debugIn'.__LINE__);

		}// EOF foreach($theCombinedCaseList as $currCase){...
	} // EOF test_UpgradeFromCronjob




    /**
	 * Get the TimeExecBegin DateTime, that is met the Period And NextLevelUpPeriod.
	 *
	 * Cloned from admin/application/controllers/cli/testing_ogp21051.php
     *
	 * @param string $theCombinedCase
	 * @return void
	 */
	public function _getMetTimeExecBeginOfPeriodAndNextLevelUpFromCombinedCase( $theCombinedCase = 'CACB.DailyPeriodMode.NextLevelUpDailyPeriodMode.PeriodIsMet.NextLevelUpPeriodIsMet.OnHourlyCheckUpgradeEnable.NoAccumulation.NextLevelUpNoAccumulation.OnMultipleUpgradeEnable' // # 1
        , $timeExecBegin = 'now' // #2
    ){

        // EmptyPeriodMode / DailyPeriodMode / Weekly1PeriodMode / Monthly5PeriodMode
        $isEmptyPeriodMode = $this->utils4testogp->_pos($theCombinedCase, '.EmptyPeriodMode');
        $isDailyPeriodMode = $this->utils4testogp->_pos($theCombinedCase, '.DailyPeriodMode');
        $isWeekly1PeriodMode = $this->utils4testogp->_pos($theCombinedCase, '.Weekly1PeriodMode');
        $isMonthly5PeriodMode = $this->utils4testogp->_pos($theCombinedCase, '.Monthly5PeriodMode');
        //
        // PeriodIsMet / PeriodNotMet
        $isPeriodIsMet = $this->utils4testogp->_pos($theCombinedCase, '.PeriodIsMet');
        $isPeriodNotMet = $this->utils4testogp->_pos($theCombinedCase, '.PeriodNotMet');
        //
        $isNoAccumulation = $this->utils4testogp->_pos($theCombinedCase, '.NoAccumulation');
        $isAccumulationYesRegistrationDate = $this->utils4testogp->_pos($theCombinedCase, '.AccumulationYesRegistrationDate');
        $isAccumulationYesLastChangePeriod = $this->utils4testogp->_pos($theCombinedCase, '.AccumulationYesLastChangePeriod');
		$isAccumulationLastChangePeriodResetIfMet = $this->utils4testogp->_pos($theCombinedCase, '.AccumulationLastChangePeriodResetIfMet');


        // NextLevelUpEmptyPeriodMode / NextLevelUpDailyPeriodMode / NextLevelUpWeekly1PeriodMode / NextLevelUpMonthly5PeriodMode
        $isNextLevelUpEmptyPeriodMode = $this->utils4testogp->_pos($theCombinedCase, 'NextLevelUpEmptyPeriodMode');
        $isNextLevelUpDailyPeriodMode = $this->utils4testogp->_pos($theCombinedCase, 'NextLevelUpDailyPeriodMode');
        $isNextLevelUpWeekly1PeriodMode = $this->utils4testogp->_pos($theCombinedCase, 'NextLevelUpWeekly1PeriodMode');
        $isNextLevelUpMonthly5PeriodMode = $this->utils4testogp->_pos($theCombinedCase, 'NextLevelUpMonthly5PeriodMode');
        //
        // NextLevelUpPeriodIsMet / NextLevelUpPeriodNotMet
        $isNextLevelUpPeriodIsMet = $this->utils4testogp->_pos($theCombinedCase, 'NextLevelUpPeriodIsMet');
        $isNextLevelUpPeriodNotMet = $this->utils4testogp->_pos($theCombinedCase, 'NextLevelUpPeriodNotMet');
        //
        $isNextLevelUpNoAccumulation = $this->utils4testogp->_pos($theCombinedCase, 'NextLevelUpNoAccumulation');
        $isNextLevelUpAccumulationYesRegistrationDate = $this->utils4testogp->_pos($theCombinedCase, 'NextLevelUpAccumulationYesRegistrationDate');
        $isNextLevelUpAccumulationYesLastChangePeriod = $this->utils4testogp->_pos($theCombinedCase, 'NextLevelUpAccumulationYesLastChangePeriod');
		$isNextLevelUpAccumulationLastChangePeriodResetIfMet = $this->utils4testogp->_pos($theCombinedCase, 'NextLevelUpAccumulationLastChangePeriodResetIfMet');


        $timeExecBeginMode = null;
        if( $isDailyPeriodMode && $isNextLevelUpDailyPeriodMode ){
            // any day
            $timeExecBeginMode = 'Any';
        }else if( $isDailyPeriodMode && $isNextLevelUpWeekly1PeriodMode ){
            // just handle Weekly1Period
            $timeExecBeginMode = 'Weekly1Period';
        }else if( $isDailyPeriodMode && $isNextLevelUpMonthly5PeriodMode ){
            // just handle Monthly5Period
            $timeExecBeginMode = 'Monthly5Period';
        }else if( $isWeekly1PeriodMode && $isNextLevelUpDailyPeriodMode ){
            // just handle Weekly1Period
            $timeExecBeginMode = 'Weekly1Period';
        }else if( $isWeekly1PeriodMode && $isNextLevelUpWeekly1PeriodMode ){
            // handle Weekly1Period
            $timeExecBeginMode = 'Weekly1Period';
        }else if( $isWeekly1PeriodMode && $isNextLevelUpMonthly5PeriodMode ){
            // handle Weekly1Period and Monthly5Period
            $timeExecBeginMode = 'Weekly1Period.Monthly5Period';
        }else if( $isMonthly5PeriodMode && $isNextLevelUpDailyPeriodMode ){
            // just handle Monthly5Period
            $timeExecBeginMode = 'Monthly5Period';
        }else if( $isMonthly5PeriodMode && $isNextLevelUpWeekly1PeriodMode ){
            // handle Weekly1Period and Monthly5Period
            $timeExecBeginMode = 'Weekly1Period.Monthly5Period';
        }else if( $isMonthly5PeriodMode && $isNextLevelUpMonthly5PeriodMode ){
            // handle Monthly5Period
            $timeExecBeginMode = 'Monthly5Period';
        }

        $TEB_DateTime = new DateTime($timeExecBegin);
        switch($timeExecBeginMode){
            default:
            case 'Any':
                break;
            case 'Weekly1Period':
                if( $TEB_DateTime->format('N') !== 1){
                    $TEB_DateTime->modify('next monday');
                }
                break;
            case 'Monthly5Period':
                if( $TEB_DateTime->format('d') != 5){
                    $TEB_DateTime->modify('first day of next month');
                    $offsetDayAmount = 5- 1;
                    $TEB_DateTime->modify('+'. $offsetDayAmount. ' day');
                }
                break;
            case 'Weekly1Period.Monthly5Period':
                $findDth = 5; // Monthly5Period
                $findWeekday = 1; // Weekly1Period
                $timeExecBeginStr = 'now';
                $isFoundIt = null;
                $TEB_DateTime = $this->utils->findWeekdayAndDth($findDth, $findWeekday, $timeExecBeginStr, $isFoundIt);
                if( empty($isFoundIt) ){
                    // handle Not found
                    $this->utils->debug_log('1323.Not found the day by findWeekdayAndDth(), Weekly1Period.Monthly5Period', $TEB_DateTime);
                }
                break;
        }

        return $TEB_DateTime;

    } // EOF _getMetTimeExecBeginOfPeriodAndNextLevelUpFromCombinedCase


    /**
     * Get the $periodMode, $periodValue for met next period condition or not by Case string,$theCombinedCase.
     * Cloned from admin/application/controllers/cli/testing_ogp21051.php
     *
     * @param [type] $thePlayerId
     * @param string $theCombinedCase
     * @param string $nextLevelUpTEB_DateTime
     * @return void
     */
    public function _getPeriodModeAndPeriodValueFromCombinedCase( $thePlayerId
		, $theCombinedCase = 'CACB.DailyPeriodMode.NextLevelUpDailyPeriodMode.PeriodIsMet.NextLevelUpPeriodIsMet.OnHourlyCheckUpgradeEnable.NoAccumulation.NextLevelUpNoAccumulation.OnMultipleUpgradeEnable'
		, $nextLevelUpTEB_DateTime = '0000-00-00 00:00:00'
	){

		$this->load->model(['group_level', 'player']);


		// EmptyPeriodMode / DailyPeriodMode / Weekly1PeriodMode / Monthly5PeriodMode
		$isEmptyPeriodMode = $this->utils4testogp->_pos($theCombinedCase, '.EmptyPeriodMode');
		$isDailyPeriodMode = $this->utils4testogp->_pos($theCombinedCase, '.DailyPeriodMode');
		$isWeekly1PeriodMode = $this->utils4testogp->_pos($theCombinedCase, '.Weekly1PeriodMode');
		$isMonthly5PeriodMode = $this->utils4testogp->_pos($theCombinedCase, '.Monthly5PeriodMode');
		//
		// PeriodIsMet / PeriodNotMet
		$isPeriodIsMet = $this->utils4testogp->_pos($theCombinedCase, '.PeriodIsMet');
		$isPeriodNotMet = $this->utils4testogp->_pos($theCombinedCase, '.PeriodNotMet');
		//
		$isNoAccumulation = $this->utils4testogp->_pos($theCombinedCase, '.NoAccumulation');
		$isAccumulationYesRegistrationDate = $this->utils4testogp->_pos($theCombinedCase, '.AccumulationYesRegistrationDate');
		$isAccumulationYesLastChangePeriod = $this->utils4testogp->_pos($theCombinedCase, '.AccumulationYesLastChangePeriod');
		$isAccumulationLastChangePeriodResetIfMet = $this->utils4testogp->_pos($theCombinedCase, '.AccumulationLastChangePeriodResetIfMet');


		// NextLevelUpEmptyPeriodMode / NextLevelUpDailyPeriodMode / NextLevelUpWeekly1PeriodMode / NextLevelUpMonthly5PeriodMode
		$isNextLevelUpEmptyPeriodMode = $this->utils4testogp->_pos($theCombinedCase, 'NextLevelUpEmptyPeriodMode');
		$isNextLevelUpDailyPeriodMode = $this->utils4testogp->_pos($theCombinedCase, 'NextLevelUpDailyPeriodMode');
		$isNextLevelUpWeekly1PeriodMode = $this->utils4testogp->_pos($theCombinedCase, 'NextLevelUpWeekly1PeriodMode');
		$isNextLevelUpMonthly5PeriodMode = $this->utils4testogp->_pos($theCombinedCase, 'NextLevelUpMonthly5PeriodMode');
		//
		// NextLevelUpPeriodIsMet / NextLevelUpPeriodNotMet
		$isNextLevelUpPeriodIsMet = $this->utils4testogp->_pos($theCombinedCase, 'NextLevelUpPeriodIsMet');
		$isNextLevelUpPeriodNotMet = $this->utils4testogp->_pos($theCombinedCase, 'NextLevelUpPeriodNotMet');
		//
		$isNextLevelUpNoAccumulation = $this->utils4testogp->_pos($theCombinedCase, 'NextLevelUpNoAccumulation');
		$isNextLevelUpAccumulationYesRegistrationDate = $this->utils4testogp->_pos($theCombinedCase, 'NextLevelUpAccumulationYesRegistrationDate');
		$isNextLevelUpAccumulationYesLastChangePeriod = $this->utils4testogp->_pos($theCombinedCase, 'NextLevelUpAccumulationYesLastChangePeriod');
		$isNextLevelUpAccumulationLastChangePeriodResetIfMet = $this->utils4testogp->_pos($theCombinedCase, 'NextLevelUpAccumulationLastChangePeriodResetIfMet');


		$isNextLevelUpMode = null;
		if( $nextLevelUpTEB_DateTime !== '0000-00-00 00:00:00'){
			$TEB_DateTime = new DateTime($nextLevelUpTEB_DateTime);
			$isNextLevelUpMode = true;
		}else{
			$TEB_DateTime = new DateTime();
			$isNextLevelUpMode = false;
		}
		if($isNextLevelUpMode == true){
			$isUsedNextLevelUp = null;
			if( $isNextLevelUpEmptyPeriodMode
				|| $isNextLevelUpDailyPeriodMode
				|| $isNextLevelUpWeekly1PeriodMode
				|| $isNextLevelUpMonthly5PeriodMode

				|| $isNextLevelUpPeriodIsMet
				|| $isNextLevelUpPeriodNotMet

				|| $isNextLevelUpNoAccumulation
				|| $isNextLevelUpAccumulationYesRegistrationDate
				|| $isNextLevelUpAccumulationYesLastChangePeriod
			 ) {
				$isUsedNextLevelUp = true;
			 }else{
				$isUsedNextLevelUp = false;
			 }
			 if( ! $isUsedNextLevelUp ) {
				$isNextLevelUpMode = false;
				$this->utils->debug_log('1243.isNextLevelUpMode TRUE convert to FALSE',$isNextLevelUpMode, 'bcz theCombinedCase No used NextLevelUpXXX.');
			 }
		}


		// /// for $TEB_DateTime
		// // the part Must be after the script,"for $enableLevelMaintainFn".
		// // Cause by the rule,"Since player get into this level".
		// $TEB_DateTime = new DateTime();
		// $TEB_DateTime->modify('+ 1 hour'); // for spliated time.
		// $playerDetails = $this->player_model->getPlayerDetailsById($thePlayerId);
		// $queryFieldname = 'request_time';
		// $theLastGradeRecordRow = $this->group_level->queryLastGradeRecordRowBy($thePlayerId, $playerDetails->createdOn, $this->utils->formatDateTimeForMysql($TEB_DateTime), 'upgrade_or_downgrade', $queryFieldname);
		// if( empty($theLastGradeRecordRow) ){
		// 	$intoThisLevelDatetimeStr = $playerDetails->createdOn; // from registaction for into vip1 first
		// }else{
		// 	$intoThisLevelDatetimeStr = $theLastGradeRecordRow['request_time'];
		// }
		// $this->utils->debug_log('1722.intoThisLevelDatetimeStr',$intoThisLevelDatetimeStr, 'theLastGradeRecordRow:', $theLastGradeRecordRow );

		/// 產生  period 相關的 json
		$periodMode = null;
		$periodValue = null;
		if( $isNextLevelUpMode ){
			if($isNextLevelUpDailyPeriodMode){
				$periodMode = 'daily'; // 'monthly';
				$periodValue = '00:00:00 - 23:59:59'; // 2;
			}else if($isNextLevelUpWeekly1PeriodMode){
				$periodMode = 'weekly'; // 'monthly';
				$periodValue = 1; // 2;
			}else if($isNextLevelUpMonthly5PeriodMode){
				$periodMode = 'monthly'; // 'monthly';
				$periodValue = 5; // 2;
			}
		}else{
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
		}

		//
		$thePeriodSetting = null;
		if( ! empty($periodMode) && ! empty($periodValue) ){
			$thePeriod = $periodMode;
			$theDay = $periodValue;
			$thePeriodSetting = $this->utils4testogp->getPeriodSetting($thePeriod, $theDay);
			$this->utils->debug_log('1697.thePeriodSetting',$thePeriodSetting);
		}





		if( $isNextLevelUpWeekly1PeriodMode
			|| $isNextLevelUpMonthly5PeriodMode
			|| $isWeekly1PeriodMode
			|| $isMonthly5PeriodMode
		){
			$theNearBy='later';
			$TEB_YmdHis = $TEB_DateTime->format('Y-m-d H:i:s');
			$TEB_DateTime = $this->utils4testogp->getTheDateNearByPeriodSetting($TEB_DateTime, $thePeriodSetting, $theNearBy);
			if( ( $isNextLevelUpPeriodNotMet && $isNextLevelUpMode )
				|| ($isPeriodNotMet && ! $isNextLevelUpMode)
			){
				$TEB_DateTime->modify('+ 1 day'); // offset 1 day for PeriodNotMet
			}
			$this->utils->debug_log('1317.TEB_DateTime', $TEB_DateTime
									, 'TEB_YmdHis:', $TEB_YmdHis
									, 'thePeriodSetting:', $thePeriodSetting
									, 'theNearBy:', $theNearBy
									, 'theCombinedCase:', $theCombinedCase
									, 'isPeriodNotMet:', $isPeriodNotMet
									, 'isNextLevelUpPeriodNotMet', $isNextLevelUpPeriodNotMet );
		}
		// return [$periodMode, $periodValue, $TEB_DateTime, $enableLevelMaintainFn];
		return [$periodMode, $periodValue, $TEB_DateTime];
	} // EOF _getPeriodModeAndPeriodValueFromCombinedCase

} // EOF Testing_ogp24373