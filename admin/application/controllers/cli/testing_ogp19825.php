<?php

require_once dirname(__FILE__) . '/base_testing_ogp.php';

/**
 * http://admin.staging.onestop.t1t.in/cli/testing_ogp19825/index/searchTestPlayerList/180/10
 *
 * http://admin.staging.onestop.t1t.in/cli/testing_ogp19825/index/scriptUpgrade/1540/cacb
 * http://admin.staging.onestop.t1t.in/cli/testing_ogp19825/index/scriptDowngrade/1540/cacb
 *
 *
 */
class Testing_ogp19825 extends BaseTestingOGP {

	var $playerId = 162503; // test11054
	var $currCase = [];
	var $isEnableTesting = FALSE;

	public function __construct() {
		parent::__construct();
	}

	public function init(){
		// $this->ci = &get_instance();
		$this->load->library(['utils']);

		$this->playerId = 162503; // test11054

		$this->currCase = [];

		/// auto by config.
		$enable_separate_accumulation_in_setting = $this->utils->getConfig('enable_separate_accumulation_in_setting');
		$vip_setting_form_ver = $this->utils->getConfig('vip_setting_form_ver');

		$mode = '';
		if( empty($enable_separate_accumulation_in_setting) ){
			$mode .= 'ca';
		}else{
			$mode .= 'sa';
		}
		if( empty($vip_setting_form_ver) || $vip_setting_form_ver == '1'){
			$mode .= 'cb';
		}else{
			$mode .= 'sb';
		}
		$this->currCase['mode'] = $mode; // Common Accumulation + Common Bet Amount

	}

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
// onestop-staging player_id=1540, test08171

		return $vipUpgradeSettings;
	}


	public function DELtestMain(){

		$this->detect4vipUpgradeSettings();

		$this->script4PlayerUpgrade($thePlayerId);

	}

	public function DELdev(){
		$this->scriptDowngrade();
	}

	/**
	 * Search Test PlayerList
	 * URI,
	 * http://admin.og.local/cli/testing_ogp19825/index/searchTestPlayerList/30/5
	 * //admin.staging.onestop.t1t.in/cli/testing_ogp19825/index/searchTestPlayerList/60/5
	 *
	 * Cli,
	 * php admin/public/index.php cli/testing_ogp19825/searchTestPlayerList 180 5
	 *
	 * @param string $offsetRange
	 * @param integer $limit
	 * @return void
	 */
	public function searchTestPlayerList($offsetDayRange = '7', $limit = 10){

		$this->load->model(['group_level']);
		// for query with index.
		$sql = 'select id, player_id, player_username, game_platform_id, game_type_id, game_description_id, start_at, end_at from game_logs where end_at > ? group by player_id limit '.$limit;

		$params = [];
		//"-7 days"
		$params[] = $this->utils->formatDateTimeForMysql(new \DateTime("-$offsetDayRange days") );
		$rows = $this->group_level->runRawArraySelectSQL($sql, $params);

		if( ! empty($rows) ){

			foreach($rows as $indexNumber => $row){
				$playerLevel = $this->player->getPlayerCurrentLevel($row['player_id']);
				$rows[$indexNumber]['groupName'] = lang($playerLevel[0]['groupName']);
				$rows[$indexNumber]['levelName'] = lang($playerLevel[0]['vipLevelName']);
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
				$game_descriptionList = $this->group_level->runRawArraySelectSQL($sql, $params);
				if( ! empty($game_descriptionList) ){
					$rows[$indexNumber]['game_name'] = lang($game_descriptionList[0]['game_name']);
					$rows[$indexNumber]['game_type'] = lang($game_descriptionList[0]['game_type_lang']);
					$rows[$indexNumber]['game_platform'] = lang($game_descriptionList[0]['system_code']);
				}
			}

		}


		$counter = count($rows);
		$result = $counter; //! empty($counter);
		$note = 'Recommand the test player list,';
		$note .= '<pre>';
		$note .= var_export($rows, true);
		$note .= '</pre>';
		$this->test( $result // result
			,  true // expect
			, __METHOD__ // title
			, $note // note
		);
		$this->returnText('returnText(): '.var_export($note, true));

	}


	/**
	 * !!! DONT Executed in the prod hosting, Will Update VIP LEVEL Settings !!!
	 * URI,
	 * //admin.og.local/cli/testing_ogp19825/index/scriptDowngrade/12345/cacb
	 * //admin.staging.onestop.t1t.in/cli/testing_ogp19825/index/searchTestPlayerList/180/10
	 *
	 * Cli,
	 * php admin/public/index.php cli/testing_ogp19825/scriptDowngrade 1540 'sacb'
	 *
	 * @param [type] $playerId
	 * @param [type] $mode
	 * @return void
	 */
	public function scriptDowngrade($playerId = null, $mode = null){
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

		/// auto by config.
		// // enable_separate_accumulation_in_setting=false , vip_setting_form_ver=1
		// $this->currCase['mode'] = 'cacb';
		// // enable_separate_accumulation_in_setting=true , vip_setting_form_ver=1
		// $this->currCase['mode'] = 'sacb';
		// // enable_separate_accumulation_in_setting=false , vip_setting_form_ver=2
		// $this->currCase['mode'] = 'casb';
		// // enable_separate_accumulation_in_setting=true , vip_setting_form_ver=2
		// $this->currCase['mode'] = 'sasb';
		//
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
	public function scriptUpgrade($playerId = null, $mode = null){
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

		/// auto by config.
		// // enable_separate_accumulation_in_setting=false , vip_setting_form_ver=1
		// $this->currCase['mode'] = 'cacb';
		// // enable_separate_accumulation_in_setting=true , vip_setting_form_ver=1
		// $this->currCase['mode'] = 'sacb';
		// // enable_separate_accumulation_in_setting=false , vip_setting_form_ver=2
		// $this->currCase['mode'] = 'casb';
		// // enable_separate_accumulation_in_setting=true , vip_setting_form_ver=2
		// $this->currCase['mode'] = 'sasb';
		//
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
		$this->returnText('328.returnText().result: '.var_export($setupUpGrade, true));
		$this->returnText('329.returnText().result: '.var_export($result, true));

		$setupUpGrade = '2to3'; // 設定 VIP 2 - OGP19825-2to3-cacb
		$result = $this->stepSetupVIP1to2InPlayerCurrentLevel($setupUpGrade); // period_up_down_2 = {"weekly":"2","hourly":true}
		$this->returnText('333.returnText().result: '.var_export($setupUpGrade, true));
		$this->returnText('334.returnText().result: '.var_export($result, true));

		// 系統 將玩家( VIP 2 ) 升級 （ 到 VIP 3 ）
		$result = $this->triggerPlayer_Management_manuallyUpgradeLevel();
		$this->returnText(__LINE__.'.returnText().result: '.var_export($result, true));

	}

	/**
	 * clone from Player_Management::manuallyUpgradeLevel()
	 *
	 * @return void
	 */
	public function triggerPlayer_Management_manuallyUpgradeLevel(){
		$this->load->model(array('group_level', 'player_promo'));

		$player_id = $this->playerId;

		$this->group_level->setGradeRecord([
			'request_type'  => Group_level::REQUEST_TYPE_MANUAL_GRADE,
			'request_grade' => Group_level::RECORD_UPGRADE,
			'request_time'  => date('Y-m-d H:i:s')
		]);

		$order_generated_by = ['order_generated_by' => Player_promo::ORDER_GENERATED_BY_MANUALLY_UPGRADE_LEVEL];
		if( $this->isEnableTesting){
			$time_exec_begin = date('c', time());
			$result = $this->group_level->batchUpDownLevelUpgrade($player_id, false, false, $time_exec_begin, $order_generated_by);
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
			$time_exec_begin = date('c', time());
			$result = $this->group_level->batchUpDownLevelDowngrade($player_id, false, $order_generated_by, $time_exec_begin);
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
	 * 設定升級條件 VIP1to2 到玩家上一個等級
	 * （玩家應該至少會是 LV 2）
	 * stepSetupVIP1to2
	 *
	 * @return void
	 */
	public function stepSetupVIP1to2InPlayerCurrentLevel($setupUpGrade = '1to2'){
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
		$the_vip_upgrade_setting_list = $this->_getVip_upgrade_settingListBysettingName($curr1to2ModeSetting_name);
$this->returnText(__LINE__.'.returnText().The curr1to2ModeSetting_name = '. var_export($curr1to2ModeSetting_name, true) );
$this->returnText(__LINE__.'.returnText().The vipsettingcashbackruleList = '. var_export($the_vip_upgrade_setting_list, true) );
		$vipsettingcashbackrule_1to2Mode = $the_vip_upgrade_setting_list[0];

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
		$this->returnText(__METHOD__.'.returnText().The previous vipsettingcashbackruleId='. $previousVipsettingcashbackruleId.'.');
		$previousVipsettingcashbackrule = []; // default

		$allPlayerLevels = $this->group_level->getAllPlayerLevels(); // gets all player levels
		$levelMap = array();
		foreach ($allPlayerLevels as $lvl) {
			$levelMap[$lvl['vipsettingcashbackruleId']] = $lvl;
		}
		if( !empty($previousVipsettingcashbackruleId) ){
			$previousVipsettingcashbackrule = $levelMap[$previousVipsettingcashbackruleId];
		}



		if( !empty($previousVipsettingcashbackruleId) ){
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
			$this->returnText(__METHOD__.'.returnText().The result = '. var_export($result, true) );
		}


		if( !empty($previousVipsettingcashbackruleId) ){
			if($gradeMode == 'upgrade'){
				$period_up_down_2 = '{"weekly":"2","hourly":true}';
				$targetField = 'period_up_down_2';
			}
			if($gradeMode == 'downgrade'){
				$period_up_down_2 = '{"weekly":"3","enableDownMaintain":false,"downMaintainUnit":"1","downMaintainTimeLength":"1","downMaintainConditionDepositAmount":"0","downMaintainConditionBetAmount":"0"}';
				$targetField = 'period_down';
			}
			$result = $this->_updatePeriod_up_down_2InVipsettingcashbackrule($period_up_down_2, $vipsettingcashbackruleId, $targetField);
			$this->returnText(__METHOD__.'.returnText().The result = '. var_export($result, true) );

			if( ! empty( $result ) ){
				$isUpdated = true;
				$resultMessages[] = '('.__LINE__.') The vipsettingcashbackrule had update vip_upgrade_id = '.$upgrade_id.' ['. $curr1to2ModeSetting_name.'].';
			}
		}

		// $this->returnText(__METHOD__.'.returnText().The previous = '. var_export($previousVipsettingcashbackrule, true) );
		// $this->returnText(__METHOD__.'.returnText().The vipsettingcashbackrule_1to2Mode = '. var_export($vipsettingcashbackrule_1to2Mode, true) );
		// $this->returnText(__METHOD__.'.returnText().The result = '. var_export($result, true) );
		// $this->returnText(__METHOD__.'.returnText().The result = '. var_export($result, true) );
		// if( ! empty( $result ) ){
		// 	$isUpdated = true;
		// 	$resultMessages[] = '('.__LINE__.') The vipsettingcashbackrule had update vip_upgrade_id = '.$upgrade_id.' ['. $curr1to2ModeSetting_name.'].';
		// }

		/// 更新後，重新讀取
		$allPlayerLevels = $this->group_level->getAllPlayerLevels(); // gets all player levels
		$levelMap = array();
		foreach ($allPlayerLevels as $lvl) {
			$levelMap[$lvl['vipsettingcashbackruleId']] = $lvl;
		}

		if( !empty($previousVipsettingcashbackruleId) ){
			$previousVipsettingcashbackrule = $levelMap[$previousVipsettingcashbackruleId];
			/// 驗證是否已更新。
			if( $previousVipsettingcashbackrule['vip_upgrade_id'] == $upgrade_id){
				$isDone = true;
				$resultMessages[] = '('.__LINE__.') The player already in '. $curr1to2ModeSetting_name.'.';
			}
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
	} // EOF getVipsettingcashbackruleListByUpgradeSettingName


	/**
	 * 設定玩家等級到 含有 OGP19825-2to3-cacb 升級條件的 VIP LEVEL。
	 * 需要下面屬性配合：
	 * $this->playerId 測試玩家編號
	 * $this->currCase['mode'] 測試模式
	 *
	 * @return void
	 */
	public function DELstepSetPlayerToVip2(){
		$thePlayerId = $this->playerId;

		$return = [];
		$isDone = false;
		$isVipsettingcashbackruleExist = false;
		$resultMessages = [];
		$thePresetVipUpgradeSettings = $this->_preSetup4vipUpgradeSettings();
		$curr1to2ModeKey = '2to3_'. $this->currCase['mode'];
		$curr1to2ModeSetting_name = $thePresetVipUpgradeSettings[$curr1to2ModeKey]['setting_name'];

		$vipsettingcashbackruleList = $this->_getVipsettingcashbackruleListByUpgradeSettingName($curr1to2ModeSetting_name);
		// $this->returnText(__METHOD__.'.returnText().vipsettingcashbackruleList: '.var_export($vipsettingcashbackruleList, true));
		if( count( $vipsettingcashbackruleList ) > 0 ){
			$isVipsettingcashbackruleExist = true;
			$vipsettingcashbackruleId = $this->_getVipsettingcashbackruleIdByPlayerId($thePlayerId);

			foreach($vipsettingcashbackruleList as $vipsettingcashbackrule){
				if($vipsettingcashbackrule['vipsettingcashbackruleId'] == $vipsettingcashbackruleId){
					$isDone = true;
					$resultMessages[] = '('.__LINE__.') The player already in 1to2_'. $curr1to2ModeSetting_name.'.';
					// $this->returnText(__METHOD__.'.returnText().The player already in 1to2_'. $curr1to2ModeSetting_name.'.');
				}
			}
			// $thePlayerCurrentLevel = $this->player->getPlayerCurrentLevel($thePlayerId);

		}else{

			$vipsettingcashbackruleList = [];
			// 需要新增一筆 OGP19825-1to2-cacb 升級條件的 VIP 等級
			// $this->returnText(__METHOD__.'.returnText().Pls to add OGP19825-1to2-'. $this->currCase['mode']. '.');
			$resultMessages[] = '('.__LINE__.') Pls to add OGP19825-1to2-'. $this->currCase['mode']. '.';
		}
		if( ! $isDone && $isVipsettingcashbackruleExist){

			$playerId = $thePlayerId;
			$newPlayerLevel = $vipsettingcashbackruleList[0]['vipsettingcashbackruleId']; // vipsettingcashbackruleId
			// clone from Player_Management::adjustVipLevelThruAjax()
			$result = $this->group_level->adjustPlayerLevel($playerId, $newPlayerLevel);

			if($result) {
				$isDone = true;
			}
			// $this->returnText(__METHOD__.'.returnText().result: '.var_export($result, true));
			$resultMessages[] = '('.__LINE__.') Had adjust the Player Level.';

		}
		$return['isDone'] = $isDone;
		$return['resultMessages'] = $resultMessages;
		return $return;
	} // EOF stepSetupVIP1to2

	/**
	 * 更新 vipsettingcashbackrule 的 vip_upgrade_id 欄位。
	 *
	 * @param integer $upgrade_id 通常指的是 vipsettingcashbackrule.vip_upgrade_id。
	 * @param integer $vipsettingcashbackruleId 通常指的是 vipsettingcashbackrule 主鍵， vipsettingcashbackrule.vipsettingcashbackruleId 。
	 * @return integer 變動資料的筆數， CI_DB_driver::affected_rows()。
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

	private function _getVip_upgrade_settingListBysettingName($settingName){
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

	public function DELisPlayerLevelEqVIP1to2(){

	}

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

} // EOF Testing_ogp19825