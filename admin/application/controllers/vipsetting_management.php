<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

require_once dirname(__FILE__) . '/BaseController.php';

/**
 * General behaviors include
 * * List of VIP group manager
 * * Exporting VIP group manage list through excel
 * * Adding group manager
 * * Modifying VIP group manager details
 * * Increasing/Decreasing group level count
 *
 * @category Player Management
 * @version 1.8.10
 * @copyright 2013-2022 tot
 */
class Vipsetting_Management extends BaseController {

	const ACTION_MANAGEMENT_TITLE = 'VIP Setting Management';

	const UPGRADE_SCHEDULE = ['daily' => 1, 'weekly' => 2, 'monthly' => 3, 'yearly' => 4];

	// deprecated in new vip upgrade/downgrade feature
	const UPGRADE_SETTING = ['upgrade_only' => 1, 'upgrade_and_downgrade' => 2];

	const UPGRADE_ACTIVE = 1;

	const DOWNGRADE_SCHEDULE = [ 'daily' => 1, 'weekly' => 2, 'monthly' => 3, 'yearly' => 4 ];

	const DOWNGRADE_SETTING = [ 'downgrade_only' => 1, 'upgrade_and_downgrade' => 2];

	const DOWNGRADE_ACTIVE = 1;

	const UPGRADE = 1;
	const DOWNGRADE = 2;

	const ERROR_COUNT = 0;

	function __construct() {
		parent::__construct();

		$this->load->helper(array('date_helper', 'url'));
		$this->load->library(array('form_validation', 'template', 'pagination', 'permissions', 'report_functions', 'payment_manager', 'depositpromo_manager'));
		$this->load->model(array('group_level'));
		$this->permissions->checkSettings();
		$this->permissions->setPermissions();
	}

	/**
	 * Loads template for view based on regions in
	 * config > template.php
	 *
	 */
	private function loadTemplate($title, $description, $keywords, $activenav) {
		$this->template->add_js('resources/js/player_management/vipsetting_management.js');
        $this->template->add_js('resources/js/player_management/vipsetting_sync.js');
		$this->template->add_css('resources/css/vipsetting_management/style.css');
		$this->template->add_js('resources/js/jquery.numeric.min.js');
		//$this->template->add_js('resources/js/datatables.min.js');
		//$this->template->add_js('resources/js/jquery.dataTables.min.js');
		//$this->template->add_js('resources/js/dataTables.responsive.min.js');

		$this->template->add_css('resources/css/general/style.css');
		//$this->template->add_css('resources/css/jquery.dataTables.css');
		//$this->template->add_css('resources/css/dataTables.responsive.css');
		$this->template->add_css('resources/css/datatables.min.css');
		$this->template->add_js('resources/js/datatables.min.js');
		$this->template->write('title', $title);
		$this->template->write('description', $description);
		$this->template->write('keywords', $keywords);
		$this->template->write('activenav', $activenav);
		$this->template->write('username', $this->authentication->getUsername());
		$this->template->write('userId', $this->authentication->getUserId());
	}

	/**
	 * Shows Error message if user can't access the page
	 *
	 * @return	rendered Template
	 */
	private function error_access() {
		$this->loadTemplate(lang('Player Management'), '', '', 'player');
		$playerUrl = $this->utils->activePlayerSidebar();
		$data['redirect'] = $playerUrl;

		$message = lang('con.vsm01');
		$this->alertMessage(self::MESSAGE_TYPE_ERROR, $message);

		$this->template->write_view('main_content', 'error_page', $data);
		$this->template->render();
	}

	/**
	 * Index Page for vip setting management
	 *
	 */
	public function index() {
		redirect('vipsetting_management/vipGroupSettingList');
	}

	/**
	 * View page for VIP Player
	 *
	 * @return	redered template
	 */
	public function vipGroupSettingList() {
		if (!$this->permissions->checkPermissions('vip_group_setting')) {
			$this->error_access();
		} else {
			$this->load->model(array('group_level', 'operatorglobalsettings', 'multiple_db_model'));

			$sort = "vipSettingId";
			$vipSettingList = $this->group_level->getVIPSettingList($sort, null, null);
			$data['data'] = $this->utils->stripHtmlTagsOfArray($vipSettingList);
			$data['operator_setting'] = json_decode($this->operatorglobalsettings->getSettingValue("vip_welcome_text"));
			$this->loadTemplate(lang('player.sd02'), '', '', 'player');
			$this->template->write_view('sidebar', 'player_management/sidebar');
			$this->template->add_css('resources/css/tutorial/tutorial.css');
			$this->template->write_view('main_content', 'player_management/vipsetting/view_vip_setting_list', $data);
			$this->template->render();
		}
	}

	/**
	 * export report to excel
	 *
	 *
	 * @return	excel format
	 */
	public function exportToExcel() {

		$this->load->library('excel');
		$this->load->model(array('group_level'));

		$this->saveAction(self::ACTION_MANAGEMENT_TITLE, 'Exported VIP Group List', "User " . $this->authentication->getUsername() . " exported VIP Group List");
		$result = $this->group_level->getVIPSettingListToExport();

		//$this->excel->to_excel($result, 'vipgrouplist-excel');
		$d = new DateTime();
		$this->utils->create_excel($result, 'vipgrouplist_' . $d->format('Y_m_d_H_i_s') . '_' . rand(1, 999));
	}

	public function get_vipgroupsetting_pages($segment) {
		$this->load->model(array('group_level'));
		$sort = "groupName";

		$data['count_all'] = count($this->group_level->getVIPSettingList($sort, null, null));
		$config['base_url'] = "javascript:get_vipgroupsetting_pages(";
		$config['total_rows'] = $data['count_all'];
		$config['per_page'] = 10;
		$config['num_links'] = 2;

		$config['first_tag_open'] = '<li>';
		$config['last_tag_open'] = '<li>';
		$config['next_tag_open'] = '<li>';
		$config['prev_tag_open'] = '<li>';
		$config['num_tag_open'] = '<li>';

		$config['first_tag_close'] = '</li>';
		$config['last_tag_close'] = '</li>';
		$config['next_tag_close'] = '</li>';
		$config['prev_tag_close'] = '</li>';
		$config['num_tag_close'] = '</li>';

		$config['cur_tag_open'] = "<li><span><b>";
		$config['cur_tag_close'] = "</b></span></li>";

		$this->pagination->initialize($config);

		$data['total_pages'] = ceil($data['count_all'] / $config['per_page']);
		$data['data'] = $this->group_level->getVIPSettingList($sort, $config['per_page'], $segment);

		$this->load->view('player_management/vipsetting/ajax_view_vip_setting_list', $data);
	}

	/**
	 * add vip group
	 *
	 * @return	rendered template
	 */
	public function addVipGroup() {
        $sync_vip_group_to_others = $this->input->post('sync_vip_group_to_others');
		//modify by jerbey 5-19-2017
		$vip_default_cover = $this->input->post('vip_default_cover');
		$image = !empty($_FILES['vip_cover']) ? $_FILES['vip_cover'] : null;
		$image_file_uploaded = null;
		$path = VIPCOVERPATH;
		$config = array(
			'allowed_types' => "jpg|jpeg|png|gif",
			'max_size' => $this->utils->getMaxUploadSizeByte(),
			'overwrite' => true,
			'remove_spaces' => true,
			'upload_path' => $path,
		);

		if (!$vip_default_cover) {
			$this->utils->debug_log('---image data--- post', json_encode($image));
			if (!empty($image) && $image['size'] != 0) {
				$this->load->library('multiple_image_uploader');
				$response = $this->multiple_image_uploader->do_multiple_uploads($image, $path, $config);
				if ($response['status'] == "fail") {
					$this->alertMessage(self::MESSAGE_TYPE_ERROR, $response['message']);
				} else {
					$image_file_uploaded = $response['filename'][0];
				}
			}
		}
		//endmodify

		$this->form_validation->set_rules('groupName', 'Group Name', 'trim|required|xss_clean');
		$this->form_validation->set_rules('groupLevelCount', 'Group Level Count', 'trim|required|xss_clean|is_numeric');
		$this->form_validation->set_rules('groupDescription', 'Group Description', 'trim|required|xss_clean');

		if ($this->form_validation->run() == false) {
			$message = lang('con.vsm03');
			$this->alertMessage(self::MESSAGE_TYPE_ERROR, $message);
		} else {

			$groupName = $this->utils->stripHTMLtags($this->input->post('groupName'));
			$groupLevelCount = $this->input->post('groupLevelCount');
			$groupDescription = $this->utils->stripHTMLtags($this->input->post('groupDescription'));
			$vipsettingId = $this->input->post('vipsettingId');
			$today = $this->utils->getNowForMysql();
			$vipGroupId = $this->input->post('vipGroupId');
			$can_be_self_join_in = $this->input->post('can_be_self_join_in');
			$image_name = $this->input->post('image_name');

			$this->load->model(array('group_level'));

            if ($vipGroupId != '') { // Edit VIP Group

				$data = array(
					'groupName' => ucfirst($groupName),
					'groupDescription' => $groupDescription,
					'updatedBy' => $this->authentication->getUserId(),
					'updatedOn' => $today,
					'can_be_self_join_in' => $can_be_self_join_in ? 1 : 0,
					'image' => ($vip_default_cover) ? "default_vip_cover.jpeg" : ((!empty($_FILES['vip_cover']['name'])) ? $image_file_uploaded : $image_name),
				);

				$flag = $this->group_level->editVIPGroup($data, $vipGroupId);
				if ($flag && isset($data['groupName'])) {
					$vipSettingId = $vipGroupId;
					$loggedAdminUserId = method_exists($this->authentication, 'getUserId') ? $this->authentication->getUserId() : Users::SUPER_ADMIN_ID;
					$is_blocked = false;
					$token = $this->triggerGenerateCommandEvent('batch_sync_group_name_in_player', [$vipSettingId, '_replace_to_queue_token_'], $is_blocked);
				}

				if($flag){
					$message = lang('con.vsm04') . " <b>" . lang($groupName) . "</b> " . lang('con.vsm05');
				}else{
					$message = lang('con.vsm04') . " <b>" . lang($groupName) . "</b> " . lang('con.editfailed');
				}

				$this->saveAction( self::ACTION_MANAGEMENT_TITLE
					, 'Edit VIP Group Name'
					, "User " . $this->authentication->getUsername() . " edit new vip group id: " . $vipGroupId
				);

			} else {
                // Add VIP Group, that includes levels
				$isGroupNameExist = $this->group_level->getVipGroupName($this->input->post('groupName'));
				if ($isGroupNameExist) {
					$message = lang('con.vsm06');
					$this->alertMessage(self::MESSAGE_TYPE_ERROR, $message);
				} elseif ($this->input->post('groupLevelCount') <= 0) {
					$message = lang('con.vsm07');
					$this->alertMessage(self::MESSAGE_TYPE_ERROR, $message);
				} else {
					$data = array(
						'groupName' => $groupName,
						'groupLevelCount' => $groupLevelCount,
						'groupDescription' => $groupDescription,
						'createdBy' => $this->authentication->getUserId(),
						'createdOn' => $today,
						'status' => 'active',
						'can_be_self_join_in' => $can_be_self_join_in ? 1 : 0,
						'image' => ($vip_default_cover) ? "default_vip_cover.jpeg" : ((!empty($_FILES['vip_cover']['name'])) ? $image_file_uploaded : $image_name),
					);
					$vipsettingId = $this->group_level->addVIPGroup($data);
					$this->saveAction(self::ACTION_MANAGEMENT_TITLE, 'Add VIP Group Name',
						"User " . $this->authentication->getUsername() . " edit new vip group id: " . $vipGroupId);
					$message = lang('con.vsm04') . " <b>" . lang($groupName) . "</b> " . lang('con.vsm08');
				}
			}

			$this->alertMessage(self::MESSAGE_TYPE_SUCCESS, $message);
		}

		redirect('vipsetting_management/vipGroupSettingList');
	}

	/**
	 * Add vip group level
	 * @param 	int
	 * @return	loaded view page
	 */
	public function increaseVipGroupLevel($vipSettingId, $sync_vip_group_to_others = false) {
		$this->group_level->increaseVIPGroupLevel($vipSettingId);
        /// Disabled for preview popup via ".btn_incgrplvlcnt" .
        // if($sync_vip_group_to_others){
        //     // TODO
        // }
		redirect('/vipsetting_management/viewVipGroupRules/' . $vipSettingId);
	}

	/**
	 * Delete vip group level
	 * @param 	int
	 * @return	loaded view page
	 */
	public function decreaseVipGroupLevel($vipSettingId, $sync_vip_group_to_others = false) {
		$result = $this->group_level->decreaseVipGroupLevel($vipSettingId);
        /// Disabled for preview popup via ".btn_decgrplvlcnt" .
        // if($sync_vip_group_to_others){
        //     // TODO
        // }
		redirect('/vipsetting_management/viewVipGroupRules/' . $vipSettingId);
	}

    /**
     * Get player id list in the Highest(, latest) Level By the Group id(, vipSettingId ).
     *
     *
     * @param 	int $vipSettingId The VIP Group id, (aka. vipSetting.vipSettingId).
	 * @return	loaded The json response. the format as,
     * - @.success bool It always be true
     * - @.result array The player id list(, aka "player.playerId" field. )
     */
    public function getPlayerIdsInHighestLevelByVipSettingId($vipSettingId) {
        $result = $this->group_level->playerIdsInHighestLevelByVipSettingId($vipSettingId);
        $success = true;
        $data = [ "success"=> $success
                // , "code"=> $code
                // , "message"=> $message
                , "result" => $result ];
        return $this->returnJsonResult($data);
    } // EOF getPlayerIdsInHighestLevelByVipSettingId
    /**
	 * Decrease vip group highest level
	 * @param 	int $vipSettingId
	 * @return	loaded json response
	 */
    public function decreaseVipGroupLevelWithDetectPlayerExists($vipSettingId) {

        $result = [];
        $message = '';
        $code = 0; // default
        $success = true;

        $playerIdsInTheLevel = [];
        $forceStopWhenPlayerExists = true;
        $rlt = $this->group_level->decreaseVipGroupLevel($vipSettingId, $forceStopWhenPlayerExists, $playerIdsInTheLevel);
        $playerIdsCounter = count($playerIdsInTheLevel);
        $result['count'] = $playerIdsCounter;

        if($rlt){
            $code = Group_level::CODE_DECREASEVIPGROUPLEVEL_IN_DECREASE_COMPLETED; // decrease level completed
            $next_uri = '/vipsetting_management/viewVipGroupRules/' . $vipSettingId;
            $result['next_uri'] = $next_uri;
            $message = lang('vip.setting.decrease_vip_group_level_completed');
        }else{
            /// its occurs when player exists
            // under forceStopWhenPlayerExists = true
            $code = Group_level::CODE_DECREASEVIPGROUPLEVEL_IN_DECREASE_NO_GOOD; // decrease level NG
            $success = false;
            $message = lang('Decrease Vip Group Level Not Yet completed.');
            if( !empty($playerIdsCounter) ){
                $code = Group_level::CODE_DECREASEVIPGROUPLEVEL_IN_LEVEL_EXIST_PLAYER; // Level has exists players.
                if($playerIdsCounter == 1){
                    $message = lang('There is a player in the VIP level.');
                }else if($playerIdsCounter > 1){
                    $message = lang('There are %s players in the VIP level.');
                }
                $message = sprintf($message, $playerIdsCounter );
            }
        }

        $data = [ "success"=> $success
                , "code"=> $code
                , "message"=> $message
                , "result" => $result ];
        $this->returnJsonResult($data);
	} // EOF decreaseVipGroupLevelWithDetectPlayerExists

	/**
	 * Edit vip group level
	 * @param 	int
	 * @return	loaded view page
	 */
	public function editVipGroupLevel($vipgrouplevelId) {
        if (!$this->permissions->checkPermissions('vip_group_setting')) {
            $this->error_access();
            return;
		}

		$data['enable_separate_accumulation_in_setting'] = $this->config->item('enable_separate_accumulation_in_setting');
		$data['vip_setting_form_ver'] = $this->config->item('vip_setting_form_ver');
		$data['showGameTree'] = $this->config->item('show_particular_game_in_tree');

		$this->load->model(array('group_level', 'game_description_model', 'promorules'));
		// $this->load->model('game');

		$data['vipgrouplevelId'] = $vipgrouplevelId;

		$data['data'] = $this->utils->stripHtmlTagsOfArray($this->group_level->getVipGroupLevelDetails($vipgrouplevelId));
		$this->utils->debug_log('328.editVipGroupLevel.data:', $data['data']);


		if (!empty($data['data']['vip_upgrade_id'])) {
			$data['data']['upgrade_setting'] = $this->group_level->getUpgradeSettingById($data['data']['vip_upgrade_id']);
		}
		if (!empty($data['data']['period_up_down_2'])) {
			$data['data']['period_up_down_2'] = json_decode($data['data']['period_up_down_2'], true);
		}
		if (!empty($data['data']['vip_downgrade_id'])) {
			$data['data']['downgrade_setting'] = $this->group_level->getUpgradeSettingById($data['data']['vip_downgrade_id']);
		}
		if(!empty($data['data']['period_down'])) {
			$data['data']['period_down'] = json_decode($data['data']['period_down'], true);
		}

		$highestGroupLevelsId = $this->group_level->getHighestGroupLevelsId();
		$groupLevels = $this->group_level->getGroupLevels($highestGroupLevelsId);
		$data['highestGroupLevelsId'] = $highestGroupLevelsId;

		$data['groupLevels'] = $groupLevels;

		if ($this->utils->isEnabledFeature('only_manually_add_active_promotion')) {
			$data['promoCms'] = $this->promorules->getAvailablePromoCMSList();
		} else {
			$data['promoCms'] = $this->promorules->getAllPromoCMSList();
		}

		$this->loadTemplate(lang('player.sd02'), '', '', 'player');
		$this->template->write_view('sidebar', 'player_management/sidebar');
		$this->template->add_css('resources/css/collapse-style.css');
		$this->template->add_css('resources/css/jquery-checktree.css');

		if($data['vip_setting_form_ver'] == 2){
			$this->template->add_js('resources/js/marketing_management/bet_amount_settings.js');
			$this->template->add_js('resources/js/marketing_management/level_upgrade.js');
		}


		$this->addBoxDialogToTemplate();

		$this->addJsTreeToTemplate();




		$this->template->write_view('main_content', 'player_management/vipsetting/view_editvipgrouplevel_rules', $data);
		$this->template->render();
	}

	/**
	 * Delete VIP group level
	 *
	 * @param 	vipgrouplevelId
	 * @return	redirect
	 */
	public function deleteVIPGroupLevel($vipgrouplevelId) {
		// if(!$this->permissions->checkPermissions('delete_vipgrouplevel')){
		// 	$this->error_access();
		// } else {

		if (!$this->permissions->checkPermissions('vip_group_setting')) {
			$this->error_access();
		}

		$this->group_level->deletevipgrouplevel($vipgrouplevelId);

		$this->saveAction(self::ACTION_MANAGEMENT_TITLE, 'Delete VIP Group', "User " . $this->authentication->getUsername() . " deleted vip group level");

		$message = lang('con.vsm09');
		$this->alertMessage(self::MESSAGE_TYPE_SUCCESS, $message);
		redirect('vipsetting_management/viewVipGroupRules/' . $vipgrouplevelId, 'refresh');
		//}
	}

	/**
	 * view vip group rules
	 *
	 * @param 	vipgrouplevelId
	 * @return	rendered template
	 */
	public function viewVipGroupRules($vipgrouplevelId) {
        if ( $this->utils->isEnabledMDB() ){
            $this->load->model(['multiple_db_model']);
        }

		if (!$this->permissions->checkPermissions('vip_group_setting')) {
			$this->error_access();
		} else {
            $filter_deleted = true;
			$data['data'] = $this->utils->stripHtmlTagsOfArray($this->group_level->getVIPGroupRules($vipgrouplevelId, $filter_deleted));
			$data['vipSettingId'] = $vipgrouplevelId;

			// $settingData = $this->group_level->getVIPGroupRules($vipgrouplevelId);

			$this->loadTemplate(lang('player.sd02').' - '.lang($data['data'][0]['groupName']), '', '', 'player');
			$this->template->write_view('sidebar', 'player_management/sidebar');
			$this->template->write_view('main_content', 'player_management/vipsetting/view_vip_setting_rules', $data);
			$this->template->render();
		}
	}

    public function check_vip_group_before_sync($vipSettingId, $dryRun = 1, $others_in = 'all', $verNo= 2){
        if ( ! $this->utils->isEnabledMDB()
            || ! $this->permissions->checkPermissions('vip_group_setting')
        ) {
            if ( ! $this->utils->isEnabledMDB() ){
                $success= false;
                $message = lang('Ignored, disabled MDB');
            }else{
                $success= false;
                $message = lang('No Permissions');
            }

            if ($this->input->is_ajax_request()) {
                $result=["success"=> $success, "message"=> $message];
                $this->returnJsonResult($result);
            }else{
                // return $this->error_redirection();
                $this->error_access();
            }

            return; // non-json response
        }else if ( $this->utils->isEnabledMDB() ){
            $this->load->library(['group_level_lib']);
            $this->load->model(array('multiple_db_model'));
            $sourceDB=$this->multiple_db_model->getActiveTargetDB();

            if($dryRun == multiple_db_model::DRY_RUN_MODE_IN_ADD_GROUP){
                $data = [];
                // $vipSettingId = $this->input->post('vipSettingId');
                // $dryRunMode = $this->input->post('dryRunMode');
                $dataJsonStr = $this->input->post('data');
                $data = $this->utils->json_decode_handleErr($dataJsonStr, true);
                $data['createdBy'] = $this->authentication->getUserId();
                $data['createdOn'] = $this->utils->getNowForMysql();
                $_vipSettingId = 'groupLevelCount_'. $data['groupLevelCount']; // for in multiple_db_model::DRY_RUN_MODE_IN_ADD_GROUP
                $formater = 'groupName_%sgroupLevelCount_%d'; // 2 params: groupName, groupLevelCount
                $_vipSettingId = sprintf($formater, $data['groupName'], $data['groupLevelCount']); // for in multiple_db_model::DRY_RUN_MODE_IN_ADD_GROUP

                // for sample, get the group id form default of currently config
                $dbName = $this->multiple_db_model->db->getOgTargetDB();
                $dbKey = str_replace('_readonly', '', $dbName);
                $default_level_id = $this->group_level_lib->getDefaultLevelIdBySourceDB($dbKey);
                $vipSettingId = $this->group_level_lib->getVipSettingIdFromLevelId($default_level_id);

                $vipSettingId = $this->utils->getMaxPrimaryIdByTable('vipsetting', true);
            }else{
                $_vipSettingId = $vipSettingId;
            }

            $filter_deleted = false;
            $select_fields_list = [];
            $select_fields_list['vipsetting'] = [];
            $select_fields_list['vipsetting'][] = 'vipSettingId'; // P.K.
            $select_fields_list['vipsetting'][] = 'groupName';
            $select_fields_list['vipsetting'][] = 'groupLevelCount';
            $select_fields_list['vipsetting'][] = 'deleted';
            $select_fields_list['vipsetting'][] = 'status';
            $select_fields_list['vipsettingcashbackrule']  = [];
            $select_fields_list['vipsettingcashbackrule'][] = 'vipsettingcashbackruleId'; // P.K.
            $select_fields_list['vipsettingcashbackrule'][] = 'vipSettingId'; // F.K.
            $select_fields_list['vipsettingcashbackrule'][] = 'vipLevel';
            $select_fields_list['vipsettingcashbackrule'][] = 'vipLevelName';
            $select_fields_list['vipsettingcashbackrule'][] = 'deleted';
            $select_fields_list['vipsettingcashbackrule'][] = 'vip_upgrade_id';
            $select_fields_list['vipsettingcashbackrule'][] = 'vip_downgrade_id';
            $return_source = true;
            $rlt = $this->multiple_db_model->listVIPGroupAndLevelsWithForeachMultipleDBWithoutSourceDB($sourceDB, $vipSettingId, $filter_deleted, $select_fields_list, $return_source);

            $insertOnly=false;
            if($verNo == 2){
                $rlt4syncVIPGroupFromOneToOtherMDBWithFixPKid = $this->multiple_db_model->syncVIPGroupFromOneToOtherMDBWithFixPKidVer2($sourceDB, $_vipSettingId, $insertOnly, $dryRun, $others_in);
            }else{ // default
                $rlt4syncVIPGroupFromOneToOtherMDBWithFixPKid = $this->multiple_db_model->syncVIPGroupFromOneToOtherMDBWithFixPKid($sourceDB, $_vipSettingId, $insertOnly, $dryRun, $others_in);
            }

            $rlt['__dryRun'] = $rlt4syncVIPGroupFromOneToOtherMDBWithFixPKid;
            $success= true;
            $message = lang('For more details, please check result.');;
        }else{
            $success= true;
            $message = lang('Ignored, disabled MDB');
        }

        $result=["success"=> $success, "message"=> $message];

        if( ! empty($rlt) ){
            $result['result'] = $rlt;
        }
        $this->returnJsonResult($result);

    } // EOF check_vip_group_before_sync

    /**
     * POST sync vip group
     * @param void The params will via POST
     * - vipSettingId integer The field, vipsetting.vipSettingId
     * - dryRunMode integer
     * - data string The json encoded string
     * @return void
     */
    public function sync_vip_group(){

        $vipSettingId = $this->input->post('vipSettingId');
        $dryRunMode = $this->input->post('dryRunMode');
        $data = $this->input->post('data'); // It should be json encode string.
        $extra_info = $this->utils->isValidJson($data)? $data: '{}';

		if ( ! $this->utils->isEnabledMDB()
            || ! $this->permissions->checkPermissions('vip_group_setting')
        ) {
			// return $this->error_redirection();
            $this->error_access();
            return;
		}else if ( $this->utils->isEnabledMDB() ){
            $this->load->library(['lib_queue']);
            $this->load->model(['multiple_db_model']);

            switch($dryRunMode){
                case Multiple_db_model::DRY_RUN_MODE_IN_DISABLED: // sync current to others
                    $dryrun_in_vipsettingid = Multiple_db_model::DRY_RUN_MODE_IN_DISABLED;
                    break;
                case Multiple_db_model::DRY_RUN_MODE_IN_NORMAL: // dryrun
                    $dryrun_in_vipsettingid = Multiple_db_model::DRY_RUN_MODE_IN_NORMAL;
                    break;
                case Multiple_db_model::DRY_RUN_MODE_IN_INCREASED_LEVELS: // dryrun with increase
                    $dryrun_in_vipsettingid = Multiple_db_model::DRY_RUN_MODE_IN_INCREASED_LEVELS;
                    break;
                case Multiple_db_model::DRY_RUN_MODE_IN_DECREASED_LEVELS:// dryrun with decreased
                    $dryrun_in_vipsettingid = Multiple_db_model::DRY_RUN_MODE_IN_DECREASED_LEVELS;
                    break;
                case Multiple_db_model::DRY_RUN_MODE_IN_ADD_GROUP: // dryrun with add group
                    $dryrun_in_vipsettingid = Multiple_db_model::DRY_RUN_MODE_IN_ADD_GROUP;
                    break;

                case Multiple_db_model::DRY_RUN_MODE_IN_DISABLED_NORMAL: // sync current to others
                    $dryrun_in_vipsettingid = Multiple_db_model::DRY_RUN_MODE_IN_DISABLED;
                    break;
                case Multiple_db_model::DRY_RUN_MODE_IN_DISABLED_INCREASED_LEVELS: // increase in current, and sync to others
                    // do increase VIP Group Level in current DB
                    $this->group_level->increaseVIPGroupLevel($vipSettingId);
                    // sync to others via queue
                    $dryrun_in_vipsettingid = Multiple_db_model::DRY_RUN_MODE_IN_DISABLED;
                    break;
                case Multiple_db_model::DRY_RUN_MODE_IN_DISABLED_DECREASED_LEVELS:// decreased in current, and sync to others
                    // do decreased VIP Group Level in current DB
                    $this->group_level->decreaseVipGroupLevel($vipSettingId);
                    // sync to others via queue
                    $dryrun_in_vipsettingid = Multiple_db_model::DRY_RUN_MODE_IN_DISABLED;
                    break;
                case Multiple_db_model::DRY_RUN_MODE_IN_DISABLED_ADD_GROUP:
                    /// TODO, do add VIP Group Level in current DB
                    $_data = $this->utils->json_decode_handleErr($extra_info, true);
                    $_data['createdBy'] = $this->authentication->getUserId();
                    $_data['createdOn'] = $this->utils->getNowForMysql();
                    $vipSettingId = $this->group_level->addVIPGroup($_data);
                    $this->saveAction(self::ACTION_MANAGEMENT_TITLE, 'Add VIP Group Name',
						"User " . $this->authentication->getUsername() . " new vip group id: " . $vipSettingId);

                    $dryrun_in_vipsettingid = Multiple_db_model::DRY_RUN_MODE_IN_DISABLED;
                    $extra_info = json_encode($_data); // replace
                    break;
            }

            $callerType = Queue_result::CALLER_TYPE_ADMIN;
            $caller=$this->authentication->getUserId();
            $state=null;
            $lang=$this->language_function->getCurrentLanguage();
            $_msg = null; // for collect result message
            //
            $targetIdArr = []; // will stored in queue_results.full_params
            $targetIdArr['source_currency'] = $this->utils->getActiveTargetDB();
            $targetIdArr['vipsettingid'] = $vipSettingId;
            $targetIdArr['vipsettingid_lock_unique_name'] = $vipSettingId;
            $targetIdArr['dryrun_in_vipsettingid'] = $dryrun_in_vipsettingid;
            $targetIdArr['extra_info'] = $extra_info; // json encode string

            $this->utils->debug_log('OGP-28577.596.targetIdArr', $targetIdArr);
            //
            $token=$this->lib_queue->triggerAsyncRemoteSyncMDBEvent($targetIdArr, $callerType, $caller, $state, $lang, $_msg);
            // $token= '123abc123abc123abc'; // enable for dev.
            $rlt = [];
            $rlt['token'] = $token;
            $rlt['success_finial'] = true;

            if(! empty($token) ){
                $success = true;
                $common_queue_href= '<a href="'. site_url( '/system_management/common_queue/'. $token ). '" target="_blank">'. $token. '</a>';
                $message = lang('The syncing had added in queue, the job token:'. $common_queue_href);
                $this->alertMessage(self::MESSAGE_TYPE_SUCCESS, $message );
            }
            ///
            // $insert_only=false;
            // $rlt = null; // for collect details
            // $success = $this->syncVIPGroupCurrentToMDBWithLock($vipSettingId, $insert_only, $rlt);
            // $message = '';
            // if($success){
            //     $message = lang('Sync Done');
            // }else{
            //     $message = lang('Sync issue. For more details, please check result.');
            // }
        }else{
            $success= true;
            $message = lang('Ignored, disabled MDB');
        }

        $result=["success"=> $success, "message"=> $message];
        if( ! empty($rlt) ){
            $result['result'] = $rlt;
        }
        $this->returnJsonResult($result);
	}

	/**
	 * sort vip group
	 *
	 * @param 	sort
	 * @return	void
	 */
	public function sortVipgroup($sort) {
		$data['count_all'] = count($this->group_level->getVIPSettingList($sort, null, null));
		$config['base_url'] = "javascript:get_vipgroupsetting_pages(";
		$config['total_rows'] = $data['count_all'];
		$config['per_page'] = 10;
		$config['num_links'] = 2;

		$config['first_tag_open'] = '<li>';
		$config['last_tag_open'] = '<li>';
		$config['next_tag_open'] = '<li>';
		$config['prev_tag_open'] = '<li>';
		$config['num_tag_open'] = '<li>';

		$config['first_tag_close'] = '</li>';
		$config['last_tag_close'] = '</li>';
		$config['next_tag_close'] = '</li>';
		$config['prev_tag_close'] = '</li>';
		$config['num_tag_close'] = '</li>';

		$config['cur_tag_open'] = "<li><span><b>";
		$config['cur_tag_close'] = "</b></span></li>";

		$this->pagination->initialize($config);

		$data['total_pages'] = ceil($data['count_all'] / $config['per_page']);
		$data['data'] = $this->group_level->getVIPSettingList($sort, $config['per_page'], null);

		$this->load->view('player_management/vipsetting/ajax_view_vip_setting_list', $data);
	}

	/**
	 * activate vip group
	 *
	 * @param 	vipsettingId
	 * @param 	status
	 * @return	redirect
	 */
	public function activateVIPGroup($vipsettingId, $status) {
		$data['vipsettingId'] = $vipsettingId;
		$data['status'] = $status;

		$this->group_level->activateVIPGroup($data);

		// $data = array(
		// 	'username' => $this->authentication->getUsername(),
		// 	'management' => 'VIP Setting Management',
		// 	'userRole' => $this->rolesfunctions->getRoleByUserId($this->authentication->getUserId())['roleName'],
		// 	'action' => 'Update status of vip group id:' . $vipsettingId . 'to status:' . $status,
		// 	'description' => "User " . $this->authentication->getUsername() . " edit vip group status to " . $status,
		// 	'logDate' => date("Y-m-d H:i:s"),
		// 	'status' => 0,
		// );

		// $this->report_functions->recordAction($data);

		$this->saveAction(self::ACTION_MANAGEMENT_TITLE, 'Update status of vip group', "User " . $this->authentication->getUsername() . " edit vip group status to " . $status);

		redirect('vipsetting_management/vipGroupSettingList');
	}

	/**
	 * Delete get vip group details
	 *
	 * @param 	int
	 * @return	redirect
	 */
	public function getVIPGroupDetails($vipsetting_id) {
		echo json_encode($this->group_level->getVIPGroupDetails($vipsetting_id));
	}

	/**
	 * Delete vip group
	 *
	 * @param 	int
	 * @return	redirect
	 */
	public function deleteVIPGroup($vipsettingId) {
		$this->group_level->DeleteVIPGroup($vipsettingId);

		// $data = array(
		// 	'username' => $this->authentication->getUsername(),
		// 	'management' => 'VIP Setting Management',
		// 	'userRole' => $this->rolesfunctions->getRoleByUserId($this->authentication->getUserId())['roleName'],
		// 	'action' => 'Delete vip group id:' . $vipsettingId,
		// 	'description' => "User " . $this->authentication->getUsername() . " delete vip group id: " . $vipsettingId,
		// 	'logDate' => date("Y-m-d H:i:s"),
		// 	'status' => 0,
		// );

		// $this->report_functions->recordAction($data);

		$this->saveAction(self::ACTION_MANAGEMENT_TITLE, 'Delete vip group', "User " . $this->authentication->getUsername() . " delete vip group id: " . $vipsettingId);

		$message = lang('con.vsm10');
		$this->alertMessage(self::MESSAGE_TYPE_SUCCESS, $message);
		redirect('vipsetting_management/vipGroupSettingList');
	}

	/**
	 * Delete vip group
	 *
	 * @param 	int
	 * @return	redirect
	 */
	public function fakeDeleteVIPGroup($vipsettingId) {
		$this->group_level->fakeDeleteVIPGroup($vipsettingId);

		// $data = array(
		// 	'username' => $this->authentication->getUsername(),
		// 	'management' => 'VIP Setting Management',
		// 	'userRole' => $this->rolesfunctions->getRoleByUserId($this->authentication->getUserId())['roleName'],
		// 	'action' => 'Delete vip group id:' . $vipsettingId,
		// 	'description' => "User " . $this->authentication->getUsername() . " delete vip group id: " . $vipsettingId,
		// 	'logDate' => date("Y-m-d H:i:s"),
		// 	'status' => 0,
		// );

		// $this->report_functions->recordAction($data);

		$this->saveAction(self::ACTION_MANAGEMENT_TITLE, 'Delete vip group', "User " . $this->authentication->getUsername() . " delete vip group id: " . $vipsettingId);

		$message = lang('con.vsm10');
		$this->alertMessage(self::MESSAGE_TYPE_SUCCESS, $message);
		redirect('vipsetting_management/vipGroupSettingList');
	}

	/**
	 * Delete vip setting
	 *
	 * @param 	int
	 * @return	redirect
	 */
	public function deleteSelectedVip() {
		$vipgroup = $this->input->post('vipgroup');
		$today = date("Y-m-d H:i:s");

		if (!empty($vipgroup)) {
			$this->load->model(['group_level']);
			foreach ($vipgroup as $vipgroupId) {
				$this->group_level->deleteVIPGroup($vipgroupId);
			}

			$message = lang('con.vsm11');
			$this->alertMessage(self::MESSAGE_TYPE_SUCCESS, $message); //will set and send message to the user
			redirect('vipsetting_management/vipGroupSettingList');
		} else {
			$message = lang('con.vsm12');
			$this->alertMessage(self::MESSAGE_TYPE_ERROR, $message);
			redirect('vipsetting_management/vipGroupSettingList');
		}
	}

	/**
	 * edit group level item
	 * the end user and add a player
	 *
	 * @return	redirect page
	 */
	public function editVipGroupLevelItemSetting() {
		if (!$this->permissions->checkPermissions('vip_group_setting')) {
			$this->error_access();
		} else {
			$this->form_validation->set_rules('vipLevelName', 'Level Name', 'trim|required|xss_clean');
			$this->form_validation->set_rules('minDeposit', 'Min Deposit', 'trim|required|xss_clean|is_numeric');
			$this->form_validation->set_rules('maxDeposit', 'Max Deposit', 'trim|required|xss_clean|is_numeric');
			$this->form_validation->set_rules('dailyMaxWithdrawal', 'Max Daily Withdrawal', 'trim|required|xss_clean|is_numeric');
			$this->form_validation->set_rules('max_withdraw_per_transaction', 'Max Withdrawal Per Transaction', 'trim|required|xss_clean|is_numeric');
			$this->form_validation->set_rules('min_withdrawal_per_transaction', 'Min Withdrawal Per Transaction', 'trim|xss_clean|is_numeric');

			// upgradeAmount, downgradeAmount No Found in view, html. It's should always be Zero in database.
			$this->form_validation->set_rules('upgradeAmount', 'Amount for Upgrade', 'trim|xss_clean|is_numeric');
			$this->form_validation->set_rules('downgradeAmount', 'Amount for Downgrade', 'trim|xss_clean|is_numeric');

			$this->form_validation->set_rules('bonusModeCashback', 'Cashback Bonus Mode', 'trim|xss_clean');
			$this->form_validation->set_rules('bonusModeDeposit', 'Deposit Bonus Mode', 'trim|xss_clean');
			$this->form_validation->set_rules('firstTimeDepositBonusOption', 'First Time Deposit Option', 'trim|xss_clean');
			$this->form_validation->set_rules('firstTimeDepositBonus', 'First Time Deposit Bonus', 'trim|xss_clean|is_numeric');
			$this->form_validation->set_rules('firstTimeDepositBonusUpTo', 'First Time Deposit Bonus Up To', 'trim|xss_clean|is_numeric');
			$this->form_validation->set_rules('firstTimeDepositWithdrawCondition', 'First Time Withdrawal Condition', 'trim|xss_clean|is_numeric');
			$this->form_validation->set_rules('succeedingDepositBonusOption', 'Succeeding Deposit Bonus Option', 'trim|xss_clean');
			$this->form_validation->set_rules('succeedingDepositBonus', 'Succeeding Deposit Bonus', 'trim|xss_clean|is_numeric');
			$this->form_validation->set_rules('succeedingDepositBonusUpTo', 'Succeeding Deposit Bonus Up To', 'trim|xss_clean|is_numeric');
			$this->form_validation->set_rules('succeedingDepositWithdrawCondition', 'Succeeding Deposit Withdraw Condition', 'trim|xss_clean|is_numeric');
			$this->form_validation->set_rules('cashbackBonusPercentage', 'Bonus Percentage', 'trim|xss_clean|is_numeric');
			$this->form_validation->set_rules('maxCashbackBonus', 'Max Bonus', 'trim|xss_clean|is_numeric');
			$this->form_validation->set_rules('maxDailyCashbackBonus', 'Daily Max Bonus', 'trim|xss_clean|is_numeric');
			$this->form_validation->set_rules('withdraw_times_limit', 'Max Withdraw Times', 'trim|required|xss_clean|is_numeric');
			$this->form_validation->set_rules('max_withdrawal_non_deposit_player', 'Max withdrawal amount for non-deposit player', 'trim|xss_clean|is_numeric');
			$this->form_validation->set_rules('betAmountConvertionRate', 'Bet Amount Convertion Rate', 'trim|xss_clean');
			$this->form_validation->set_rules('depositAmountConvertionRate', 'Deposit Amount Convertion Rate', 'trim|xss_clean');
			$this->form_validation->set_rules('birthdayBonusAmount', 'Birthday Bonus Amount', 'trim|xss_clean|is_numeric');
			$this->form_validation->set_rules('max_monthly_withdrawal', 'max_monthly_withdrawal', 'trim|required|xss_clean|is_numeric');

			//OGP-21105 points limit
			$this->form_validation->set_rules('vipSettingPointLimit', 'Points Limit', 'trim|xss_clean|is_numeric');
			$this->form_validation->set_rules('vipSettingPointLimitType', 'Points Limit Type', 'trim|xss_clean');

			$vipsettingcashbackruleId = $this->input->post('vipsettingcashbackruleId');
			$this->form_validation->set_rules('auto_tick_new_games_in_game_type', 'Auto Tick New Game', 'trim|xss_clean');

			if ($this->form_validation->run() == false) {
				$message = validation_errors();
				$this->alertMessage(self::MESSAGE_TYPE_ERROR, $message);

				redirect('vipsetting_management/editVipGroupLevel/' . $vipsettingcashbackruleId);
			} else {
				$this->utils->debug_log('----editVipGroupLevelItemSetting--- post', $this->input->post());
				$this->load->model(['promorules']);

				$additional_message=null;
				$adminUserId=$this->authentication->getUserId();
				$success=$this->group_level->lockAndTrans(Utils::GLOBAL_LOCK_ACTION_VIP_CASHBACK, 0, function() use($adminUserId, $vipsettingcashbackruleId, &$additional_message){
					$birthday_bonus_amount_expiration_period = null;
					$success=true;
					// level upgrade setting
					$upgradeSetting = $this->input->post('upgradeSetting');
					$upgradeLevelId = null;
					if ($upgradeSetting) {
						if ($upgradeSetting == 1) {
							$upgradeLevelId = $this->input->post('upgradeOnly');
						} else if ($upgradeSetting == 2) {
							$upgradeLevelId = $this->input->post('upgradeDowngrade');
						}
					}
					$downgradeSetting = $this->input->post('downgradeSetting');
					$downgradeLevelId = null;
					if ($downgradeSetting) {
						if ($downgradeSetting == 3) {
							$downgradeLevelId = $this->input->post('downgradeOnly');
						}
					}

					$schedData = array();
					$periodSched = $this->input->post('upgradeSched');
					if ($periodSched == 1) {
						$schedData = array('daily' => $this->input->post('daily'));
					} elseif ($periodSched == 2) {
						$schedData = array('weekly' => $this->input->post('weekly'));
					} elseif ($periodSched == 3) {
						$schedData = array('monthly' => $this->input->post('monthly'));
					} elseif ($periodSched == 4) {
						$schedData = array('yearly' => $this->input->post('yearly'));
					}

					$hourlyCheckUpgrade = $this->input->post('hourlyCheckUpgrade');
					if($hourlyCheckUpgrade == '1') {
						$schedData['hourly'] = true;
					}

					$schedDownData = array();
					$periodDownSched = $this->input->post('downgradeSched');
					// schedDownData[0] will be confirm by Group_level::getPlayerBetAmtForNextLvl().
					if ($periodDownSched == 1) {
						$schedDownData = array('daily' => $this->input->post('down_daily'));
					} elseif ($periodDownSched == 2) {
						$schedDownData = array('weekly' => $this->input->post('down_weekly'));
					} elseif ($periodDownSched == 3) {
						$schedDownData = array('monthly' => $this->input->post('down_monthly'));
					} elseif ($periodDownSched == 4) {
						$schedDownData = array('yearly' => $this->input->post('down_yearly'));
					}

					$enableDownMaintain = $this->input->post('enableDownMaintain');
					$downMaintainUnit = $this->input->post('downMaintainTimeUnit');
					$downMaintainTimeLength = $this->input->post('downMaintainTimeLength');
					$downMaintainConditionDepositAmount = $this->input->post('downMaintainConditionDepositAmount');
					$downMaintainConditionBetAmount = $this->input->post('downMaintainConditionBetAmount');
					$schedDownData['enableDownMaintain'] = !empty($enableDownMaintain); // convert to bool
					$schedDownData['downMaintainUnit'] = $downMaintainUnit;
					$schedDownData['downMaintainTimeLength'] = $downMaintainTimeLength;
					$schedDownData['downMaintainConditionDepositAmount'] = $downMaintainConditionDepositAmount;
					$schedDownData['downMaintainConditionBetAmount'] = $downMaintainConditionBetAmount;

					$vipLevelName = $this->input->post('vipLevelName');
					$minDeposit = $this->input->post('minDeposit');
					$maxDeposit = $this->input->post('maxDeposit');
					$dailyMaxWithdrawal = $this->input->post('dailyMaxWithdrawal');
					$withdraw_times_limit = $this->input->post('withdraw_times_limit');
					$max_withdrawal_per_transaction = $this->input->post('max_withdraw_per_transaction');
	                $min_withdrawal_per_transaction = $this->input->post('min_withdrawal_per_transaction');
					$max_withdrawal_non_deposit_player = $this->input->post('max_withdrawal_non_deposit_player');
					$can_cashback = $this->input->post('can_cashback') ? $this->input->post('can_cashback') : 'false';
					$promo_cms_id = $this->input->post('promo_cms_id');
					$promo_rule_id = $this->promorules->getPromorulesIdByPromoCmsId($promo_cms_id);
					$downgrade_promo_cms_id = $this->input->post('downgrade_promo_cms_id');
					$downgrade_promo_rule_id = $this->promorules->getPromorulesIdByPromoCmsId($downgrade_promo_cms_id);
					$upgradeAmount = $this->input->post('upgradeAmount');
					$downgradeAmount = $this->input->post('downgradeAmount');
					$minimumMonthlyDeposit = $this->input->post('minimumMonthlyDeposit');
					$periodUpDown = $this->input->post('periodUpdown');
					$cashback_period = $this->input->post('cashback_period');
					$bonus_mode_cashback = $this->input->post('bonusModeCashback') ?: null;
					$bonus_mode_deposit = $this->input->post('bonusModeDeposit') ?: null;
					$birthday_mode_deposit = $this->input->post('bonusModeBirthday') ?: null;
					$cashback_target = $this->input->post('cashbackTarget');
					$bet_convert_rate = $this->input->post('betAmountConvertionRate') ?: null;
					$deposit_convert_rate = $this->input->post('depositAmountConvertionRate') ?: null;
					$winning_convert_rate = $this->input->post('winningAmountConvertionRate') ?: null;
					$losing_convert_rate = $this->input->post('losingAmountConvertionRate') ?: null;
					$max_monthly_withdrawal = $this->input->post('max_monthly_withdrawal');

					$vipSettingPointLimit = $this->input->post('vipSettingPointLimit');
					$vipSettingPointLimitType = $this->input->post('vipSettingPointLimitType');

					if ($bonus_mode_cashback == self::BONUS_MODE_ENABLE) {
						$bonus_mode_cashback = self::BONUS_MODE_ENABLE;
						$cashback_percentage = $this->input->post('cashbackBonusPercentage') ?: null;
						$cashback_maxbonus = $this->input->post('maxCashbackBonus') ?: null;
						$cashback_daily_maxbonus = $this->input->post('maxDailyCashbackBonus') ?: null;
					} else {
						$bonus_mode_cashback = self::BONUS_MODE_DISABLE;
						$cashback_percentage = null;
						$cashback_maxbonus = null;
						$cashback_daily_maxbonus = null;
					}

					if ($bonus_mode_deposit == self::BONUS_MODE_ENABLE) {
						$firsttime_dep_type = $this->input->post('firstTimeDepositBonusOption') ?: null;
						$firsttime_dep_bonus = $this->input->post('firstTimeDepositBonus') ?: null;
						$firsttime_dep_percentage_upto = $this->input->post('firstTimeDepositBonusUpTo') ?: null;
						$firsttime_dep_withdraw_condition = $this->input->post('firstTimeDepositWithdrawCondition');
						$succeeding_dep_type = $this->input->post('succeedingDepositBonusOption') ?: null;
						$succeeding_dep_bonus = $this->input->post('succeedingDepositBonus') ?: null;
						$succeeding_dep_percentage_upto = $this->input->post('succeedingDepositBonusUpTo') ?: null;
						$succeeding_dep_withdraw_condition = $this->input->post('succeedingDepositWithdrawCondition');
						$bonus_mode_deposit = self::BONUS_MODE_ENABLE;
					} else {
						$firsttime_dep_type = null;
						$firsttime_dep_bonus = null;
						$firsttime_dep_percentage_upto = null;
						$firsttime_dep_withdraw_condition = null;
						$succeeding_dep_type = null;
						$succeeding_dep_bonus = null;
						$succeeding_dep_percentage_upto = null;
						$succeeding_dep_withdraw_condition = null;
						$bonus_mode_deposit = self::BONUS_MODE_DISABLE;
					}

					if ($birthday_mode_deposit == self::BONUS_MODE_ENABLE) {
						$birthday_bonus_amount = $this->input->post('birthdayBonusAmount') ?: null;
						$birthday_bonus_withdraw_condition = $this->input->post('birthdayBonusWithdrawCondition') ?: null;
					} else {
						$birthday_bonus_amount = null;
						$birthday_bonus_amount_expiration_period = null;
						$birthday_mode_deposit = self::BONUS_MODE_DISABLE;
						$birthday_bonus_withdraw_condition = null;
					}

					$one_withdraw_only = $this->input->post('one_withdraw_only') == 'true';

					$this->load->model(array('group_level', 'game_description_model'));

					$showGameTree = $this->config->item('show_particular_game_in_tree');
					$this->utils->debug_log('showGameTree', $showGameTree);

					$withdrawal_fee_levels_setting = $this->config->item('withdrawal_fee_levels');
					if ($withdrawal_fee_levels_setting) {
						if ($max_monthly_withdrawal >= $withdrawal_fee_levels_setting[0]) {
							$this->utils->error_log('wrong submit', $withdrawal_fee_levels_setting, $max_monthly_withdrawal);
							$additional_message=sprintf(lang('Monthly service fee waived withdrawal limit cannot be greater than %s of config'),$withdrawal_fee_levels_setting[0]);
							return false;
						}
					}

					if(!$this->utils->isEnabledFeature('enable_isolated_vip_game_tree_view')) {
						$enabled_edit_game_tree=$this->input->post('enabled_edit_game_tree')=='true';

						if($enabled_edit_game_tree){
							list($gamePlatformList, $gameTypeList, $gameDescList) = $this->processSubmitGameTreeWithNumber();
							$this->utils->debug_log('vipsettingcashbackruleId', $vipsettingcashbackruleId);
							if($gamePlatformList===null){
								//wrong submit
								$this->utils->error_log('wrong submit', $gamePlatformList, $gameTypeList, $gameDescList);
								$additional_message=lang('Saved cashback game tree failed, because submit wrong format');
								return false;
							}

							if(count($gamePlatformList)<=0 || count($gameTypeList)<=0){
								$this->utils->error_log('empty game tree on vip', $gamePlatformList, $gameTypeList);
								$additional_message=lang('Saved cashback game tree failed');
								return false;
							}else{
								$selected_game_tree = $this->input->post('selected_game_tree');
								$diffList=[];
								$rlt = $this->group_level->batchAddCashbackPercentage($vipsettingcashbackruleId,
									$gamePlatformList, $gameTypeList, $gameDescList, $adminUserId, $selected_game_tree, $diffList);
								$this->utils->debug_log('batchAddCashbackPercentage', $rlt, $diffList);
								if(!$rlt){
									$additional_message=lang('Saved cashback game tree failed');
									return $rlt;
								}
								$additional_message=lang('Saved cashback settings');
								//show diff list
								if(!empty($diffList)){
									if(!empty($diffList['deleted_game_platform'])){
										//search system code
										$this->load->model(['external_system']);
										$langList=$this->external_system->searchSystemCodeByList($diffList['deleted_game_platform']);
										sort($langList);
										$additional_message.=' | '.lang('Deleted').' '.implode(',', $langList);
									}
									if(!empty($diffList['deleted_game_type'])){
										//search game type lang
										$this->load->model(['game_type_model']);
										$langList=$this->game_type_model->searchGameTypeByList($diffList['deleted_game_type']);
										sort($langList);
										$additional_message.=' | '.lang('Deleted').' '.implode(',', $langList);
									}

									$this->utils->debug_log('additional_message for batchAddCashbackPercentage', $additional_message);
								}
							}
						}else{
							$this->utils->debug_log('ignore save game tree');
							$additional_message=lang('Cashback settings was not changed');
						}
					}

					$vipSettingId = $this->input->post('vipSettingId');
					$vipsettingcashbackbonuspergameId = $this->input->post('vipsettingcashbackbonuspergameId');
					$today = date("Y-m-d H:i:s");
					$guaranteed_downgrade_period_number = $this->input->post('guaranteed_downgrade_period_number');
					$guaranteed_downgrade_period_total_deposit = $this->input->post('guaranteed_downgrade_period_total_deposit');
					$auto_tick_new_games_in_cash_back_tree = $this->input->post('auto_tick_new_games_in_game_type') ? 1 : 0;

					//updates bonus rule
					$bonusRule = array(
						'vipLevelName' => $vipLevelName,
						'vipsettingcashbackruleId' => $vipsettingcashbackruleId,
						'minDeposit' => $minDeposit,
						'maxDeposit' => $maxDeposit,
						'dailyMaxWithdrawal' => $dailyMaxWithdrawal,
						'max_withdraw_per_transaction' => $max_withdrawal_per_transaction,
						'upgradeAmount' => $upgradeAmount,
						'minimumMonthlyDeposit' => $minimumMonthlyDeposit,
						'period_up_down' => $periodUpDown,
						'downgradeAmount' => $downgradeAmount,
						'bonus_mode_cashback' => $bonus_mode_cashback,
						'bonus_mode_deposit' => $bonus_mode_deposit,
						'firsttime_dep_type' => $firsttime_dep_type,
						'firsttime_dep_bonus' => $firsttime_dep_bonus,
						'firsttime_dep_percentage_upto' => $firsttime_dep_percentage_upto,
						'firsttime_dep_withdraw_condition' => $firsttime_dep_withdraw_condition,
						'succeeding_dep_type' => $succeeding_dep_type,
						'succeeding_dep_bonus' => $succeeding_dep_bonus,
						'succeeding_dep_percentage_upto' => $succeeding_dep_percentage_upto,
						'succeeding_dep_withdraw_condition' => $succeeding_dep_withdraw_condition,
						'cashback_percentage' => $cashback_percentage,
						'cashback_maxbonus' => $cashback_maxbonus,
						'cashback_daily_maxbonus' => $cashback_daily_maxbonus,
						'withdraw_times_limit' => $withdraw_times_limit,
						'bet_convert_rate' => $bet_convert_rate,
						'winning_convert_rate' => $winning_convert_rate,
						'losing_convert_rate' => $losing_convert_rate,
						'deposit_convert_rate' => $deposit_convert_rate,
						'one_withdraw_only' => $one_withdraw_only,
						'promo_cms_id' => $promo_cms_id,
						'promo_rule_id' => $promo_rule_id,
						'vip_upgrade_id' => $upgradeLevelId,
						'period_up_down_2' => json_encode($schedData),
						'vip_downgrade_id' => $downgradeLevelId,
						'period_down' => json_encode($schedDownData),
						'max_withdrawal_non_deposit_player' => $max_withdrawal_non_deposit_player,
						'downgrade_promo_cms_id' => $downgrade_promo_cms_id,
						'downgrade_promo_rule_id' => $downgrade_promo_rule_id,
						'guaranteed_downgrade_period_number' => $guaranteed_downgrade_period_number,
						'guaranteed_downgrade_period_total_deposit' => $guaranteed_downgrade_period_total_deposit,
						'bonus_mode_birthday' => $birthday_mode_deposit,
						'birthday_bonus_amount' => $birthday_bonus_amount,
						'birthday_bonus_expiration_datetime' => $birthday_bonus_amount_expiration_period,
						'birthday_bonus_withdraw_condition' => $birthday_bonus_withdraw_condition,
						'cashback_period' => $cashback_period,
						'can_cashback' => $can_cashback,
	                    'max_monthly_withdrawal' => $max_monthly_withdrawal,
						'min_withdrawal_per_transaction' => $min_withdrawal_per_transaction,
						'auto_tick_new_game_in_cashback_tree' => $auto_tick_new_games_in_cash_back_tree ? 1 : 0,
						'points_limit' => $vipSettingPointLimit,
						'points_limit_type' => $vipSettingPointLimitType,
						'cashback_target' => $cashback_target,
					);

                    $enable_vip_downgrade_switch = !empty($this->config->item('enable_vip_downgrade_switch'));
                    if($enable_vip_downgrade_switch){
                        $enableLevelDown = $this->input->post('enableLevelDown');
                        $bonusRule['enable_vip_downgrade'] = empty($enableLevelDown)? '0': '1';
                    }

					$this->utils->debug_log('----editVipGroupLevelItemSetting---', $bonusRule);

					$this->group_level->editVipGroupBonusRule($bonusRule);

					$this->utils->debug_log('editVipGroupBonusRule');

					$this->group_level->updateAllVipGroupBonusPerGame($vipsettingcashbackruleId, $cashback_percentage, $cashback_maxbonus);

					$this->utils->debug_log('updateAllVipGroupBonusPerGame');
					//updates vip group setting
					$vipGroupSettingDetails = array(
						'updatedOn' => $today,
						'updatedBy' => $this->authentication->getUserId(),
					);

					$this->group_level->editVIPGroup($vipGroupSettingDetails, $vipSettingId);
					$this->utils->debug_log('editVIPGroup');
					$this->utils->debug_log('commit trans after editVIPGroup');
					return $success;
				}); // EOF group_level->lockAndTrans(Utils::GLOBAL_LOCK_ACTION_VIP_CASHBACK ...

				if(!$success){
					$this->alertMessage(self::MESSAGE_TYPE_ERROR, lang('save.failed').' '.$additional_message);
				} else {
					$message = lang('con.vsm15');
					$this->alertMessage(self::MESSAGE_TYPE_WARNING, $message.' '.$additional_message);
				}
				redirect('vipsetting_management/editVipGroupLevel/' . $vipsettingcashbackruleId);
			}
		}
	}

	public function setVipGameCashbackPercentage($ajax_request=true) {
		// if (!empty($gamesAptList)) {
		$vipsettingcashbackruleId = $this->input->post('vipsettingcashbackruleId');
		$this->utils->debug_log('vipsettingcashbackruleId', $vipsettingcashbackruleId);
		$additional_message=null;
		// $rlt = $this->group_level->batchAddCashbackAllowedGames($vipsettingcashbackruleId, $cashback_percentage, $cashback_maxbonus, $gamesAptList);
		$rlt=$this->group_level->lockAndTrans(Utils::GLOBAL_LOCK_ACTION_VIP_CASHBACK, 0,
				function() use($vipsettingcashbackruleId, &$additional_message){

			$adminUserId=$this->authentication->getUserId();
			$this->utils->debug_log('==========================enter setVipGameCashbackPercentage');
			list($gamePlatformList, $gameTypeList, $gameDescList) = $this->processSubmitGameTreeWithNumber();
			$this->utils->debug_log('================setVipGameCashbackPercentage gamePlatformList', $gamePlatformList);
			$this->utils->debug_log('================setVipGameCashbackPercentage gameTypeList', $gameTypeList);
			$this->utils->debug_log('================setVipGameCashbackPercentage gameDescList', $gameDescList);

			if($gamePlatformList===null){
				//wrong submit
				$this->utils->error_log('wrong submit', $gamePlatformList, $gameTypeList, $gameDescList);
				$additional_message=lang('Saved cashback game tree failed, because submit wrong format');
				return false;
			}

			if(count($gamePlatformList)<=0 || count($gameTypeList)<=0){

				$this->utils->error_log('empty game tree on vip', $gamePlatformList, $gameTypeList);
				$additional_message=lang('Saved cashback game tree failed');
				return false;
			}
			$selected_game_tree = $this->input->post('selected_game_tree');
			$diffList=[];
			$rlt = $this->group_level->batchAddCashbackPercentage($vipsettingcashbackruleId,
				$gamePlatformList, $gameTypeList, $gameDescList, $adminUserId, $selected_game_tree, $diffList);
			$this->utils->debug_log('batchAddCashbackPercentage', $rlt, $diffList);
			if(!$rlt){
				$additional_message=lang('Saved cashback game tree failed');
				return $rlt;
			}
			$additional_message=lang('Saved cashback settings');
			//show diff list
			if(!empty($diffList)){
				if(!empty($diffList['deleted_game_platform'])){
					//search system code
					$this->load->model(['external_system']);
					$langList=$this->external_system->searchSystemCodeByList($diffList['deleted_game_platform']);
					sort($langList);
					$additional_message.=' | '.lang('Deleted').' '.implode(',', $langList);
				}
				if(!empty($diffList['deleted_game_type'])){
					//search game type lang
					$this->load->model(['game_type_model']);
					$langList=$this->game_type_model->searchGameTypeByList($diffList['deleted_game_type']);
					sort($langList);
					$additional_message.=' | '.lang('Deleted').' '.implode(',', $langList);
				}

				$this->utils->debug_log('additional_message for batchAddCashbackPercentage', $additional_message);
			}
			return $rlt;
		});

		$this->utils->debug_log('batchAddCashbackPercentage', $rlt);
		if(!$ajax_request) {
			return $rlt;
		}

		if($rlt) {
			// echo json_encode(array("success"=> true, "message"=> "Already successfully updated selected games and their cashback percentage."));
			$result=["success"=> true, "message"=> lang('con.vsm15').' '.$additional_message];
			$this->returnJsonResult($result);
			// $message = "Already successfully updated selected games and their cashback percentage.";
			// $this->alertMessage(self::MESSAGE_TYPE_SUCCESS, $message);
		}else {
			// echo json_encode(array("success"=> false, "message"=> lang('save.failed')));
			$result=["success"=> false, "message"=> lang('save.failed').' '.$additional_message];
			$this->returnJsonResult($result);
			// $messsage = "Failed to update selected games and their cashback percentage.";
			// $this->alertMessage(self::MESSAGE_TYPE_ERROR, $message);
		}
	}

	/**
	 * search group level
	 *
	 *
	 * @return	redirect page
	 */
	public function searchVipGroupList($search = '') {
		$data['count_all'] = count($this->group_level->searchVipGroupList($search, null, null));
		$config['base_url'] = "javascript:get_vipgroupsetting_pages(";
		$config['total_rows'] = $data['count_all'];
		$config['per_page'] = 10;
		$config['num_links'] = 2;

		$config['first_tag_open'] = '<li>';
		$config['last_tag_open'] = '<li>';
		$config['next_tag_open'] = '<li>';
		$config['prev_tag_open'] = '<li>';
		$config['num_tag_open'] = '<li>';

		$config['first_tag_close'] = '</li>';
		$config['last_tag_close'] = '</li>';
		$config['next_tag_close'] = '</li>';
		$config['prev_tag_close'] = '</li>';
		$config['num_tag_close'] = '</li>';

		$config['cur_tag_open'] = "<li><span><b>";
		$config['cur_tag_close'] = "</b></span></li>";

		$this->pagination->initialize($config);

		$data['total_pages'] = ceil($data['count_all'] / $config['per_page']);
		$data['data'] = $this->group_level->searchVipGroupList($search, $config['per_page'], null);

		//export report permission checking
		if (!$this->permissions->checkPermissions('export_report')) {
			$data['export_report_permission'] = FALSE;
		} else {
			$data['export_report_permission'] = TRUE;
		}

		$this->load->view('player_management/vipsetting/ajax_view_vip_setting_list', $data);
	}

    public function sync_recalculate_cashback_report(){
        $data['conditions'] = $this->safeLoadParams(array(
            'by_date_from' => $this->utils->getTodayForMysql(),
            'by_date_to' => $this->utils->getTodayForMysql(),
        ));

        $condition = $data['conditions'];
        $fromDate = $condition['by_date_from'];
        $toDate = $condition['by_date_to'];

        $tempRecalculateCashbackReportTable = "recalculate_cashback_temp_".date("Ymd");
        $callerType=Queue_result::CALLER_TYPE_ADMIN;
        $caller=$this->authentication->getUserId();
        $state=null;

        $this->load->library(['lib_queue', 'authentication']);
        $token = $this->lib_queue->addReomoteGenerateRecalculateCashbackReportJob($fromDate, $toDate, $tempRecalculateCashbackReportTable, $callerType, $caller, $state);

        if (!empty($token)) {
            $this->alertMessage(self::MESSAGE_TYPE_SUCCESS, lang('Sync recalculate cashback report job successfully'));
            return redirect('/system_management/common_queue/'.$token);
        } else {
            $this->alertMessage(self::MESSAGE_TYPE_ERROR, lang('Sync recalculate cashback report job failed'));
            redirect('/report_management/recalculate_cashback_report');
        }
	}

    public function sync_recalculate_wc_deduction_process_report(){
        $data['conditions'] = $this->safeLoadParams(array(
            'by_date_from' => $this->utils->getTodayForMysql(),
            'by_date_to' => $this->utils->getTodayForMysql(),
        ));

        $condition = $data['conditions'];
        $fromDate = $condition['by_date_from'];
        $toDate = $condition['by_date_to'];

        // WCDP = Withdraw Condition Deduction Process
        $tempWCDPReportTable = "withdraw_condition_deducted_process_temp_".date("Ymd");
        $callerType=Queue_result::CALLER_TYPE_ADMIN;
        $caller=$this->authentication->getUserId();
        $state=null;

        $this->load->library(['lib_queue', 'authentication']);
        $token = $this->lib_queue->addReomoteGenerateRecalculateWCDPReportJob($fromDate, $toDate, $tempWCDPReportTable, $callerType, $caller, $state);

        if (!empty($token)) {
            $this->alertMessage(self::MESSAGE_TYPE_SUCCESS, lang('Sync wc deduction process report job successfully'));
            return redirect('/system_management/common_queue/'.$token);
        } else {
            $this->alertMessage(self::MESSAGE_TYPE_ERROR, lang('Sync wc deduction process report job failed'));
            redirect('/report_management/recalculate_wc_deduction_process_report');
        }
    }

	/**
	 *
	 * pay all cashback
	 *
	 * @return redirect back cashback report
	 */
	public function pay_all_cashback() {
		if (!$this->permissions->checkPermissions('manually_pay_cashback')) {
			return $this->error_access();
		}

		$data['conditions'] = $this->safeLoadParams(array(
			'by_date_from' => $this->utils->getTodayForMysql(),
			'by_date_to' => $this->utils->getTodayForMysql(),
			'by_username' => '',
			'by_player_level' => '',
			'by_amount_greater_than' => '',
			'by_amount_less_than' => '',
			'by_paid_flag' => '',
			'enable_date' => 'false',
		));
		$this->load->model(['group_level']);
		$this->load->library(['lib_queue', 'language_function', 'authentication']);
		$callerType=Queue_result::CALLER_TYPE_ADMIN;
		$caller=$this->authentication->getUserId();
		$state=null;
		$lang=$this->language_function->getCurrentLanguage();

		//add pay time
		$cashBackSettings=$this->group_level->getCashbackSettings();
		$dateStr=$this->utils->getTodayForMysql().' ' . $cashBackSettings->payTimeHour . ':00';

		//run queue
		$errMsg = null;
		$token=$this->lib_queue->addRemotePayCashbackDaily($dateStr, $callerType, $caller, $state, $lang, $errMsg);

	    if (!empty($token)) {
	        $this->alertMessage(self::MESSAGE_TYPE_SUCCESS, lang('Create pay cashback job successfully'));
			return redirect('/system_management/common_queue/'.$token);
	    } elseif (!empty($errMsg)) {
	        $this->alertMessage(self::MESSAGE_TYPE_ERROR, lang($errMsg));
			redirect('/report_management/cashback_report');
	    } else {
	        $this->alertMessage(self::MESSAGE_TYPE_ERROR, lang('Create pay cashback job failed'));
			redirect('/report_management/cashback_report');
	    }

		// $this->load->model(['report_model', 'group_level']);
		// $this->load->library('data_tables');
		// //get report condition
		// $input = $data['conditions'];

		// if ($input['by_paid_flag'] == Group_level::DB_TRUE) {
		// 	//can't pay paid records
		// 	$this->alertMessage(self::MESSAGE_TYPE_ERROR, lang('Cannot pay this condition, please change flag to Not pay'));
		// } else {
		// 	//search by conditions
		// 	//convert to selected_id_value
		// 	$selected_id_array = $this->group_level->searchUnpaidCashbackByCondition($input);

		// 	if (!empty($selected_id_array)) {

		// 		$success = false;
		// 		//lock action
		// 		$lockedKey = null;
		// 		$lock_it = $this->utils->lockResourceBy('', Utils::LOCK_ACTION_MANUALLY_PAY_CASHBACK, $lockedKey);
		// 		if ($lock_it) {
		// 			try {
		// 				$success = $this->group_level->manuallyPayCashbackByIdArr($selected_id_array);
		// 			} finally {
		// 				$rlt = $this->utils->releaseResourceBy('', Utils::LOCK_ACTION_MANUALLY_PAY_CASHBACK, $lockedKey);
		// 			}
		// 		} else {
		// 			$this->utils->debug_log('pay cashback failed, lock failed');
		// 		}

		// 		if ($success) {
		// 			$this->alertMessage(self::MESSAGE_TYPE_SUCCESS, lang('Pay cashback successfully'));
		// 		} else {
		// 			$this->alertMessage(self::MESSAGE_TYPE_ERROR, lang('Pay cashback failed'));
		// 		}
		// 	} else {
		// 		$this->alertMessage(self::MESSAGE_TYPE_ERROR, lang('Do not find any records'));
		// 	}
		// }

		// //go back report
		// // $this->alertMessage(self::MESSAGE_TYPE_SUCCESS, lang('Pay cashback successfully'));
		// redirect('/report_management/cashback_report?' . http_build_query($data['conditions']));
	}

	/**
	 *
	 * pay selected cashback
	 *
	 * @deprecated
	 *
	 * @return redirect back cashback report
	 */
	public function pay_selected() {
		// if (!$this->permissions->checkPermissions('manually_pay_cashback')) {
		// 	return $this->error_access();
		// }

		// $redirect_url = $this->input->post('redirect_url');

		// $selected_id_value = $this->input->post("selected_id_value");
		// $this->load->model(['group_level']);

		// $selected_id_array = !empty($selected_id_value) ? explode(',', $selected_id_value) : null;

		// if (!empty($selected_id_array)) {

		// 	$success = false;
		// 	//only lock action, because manuallyPayCashbackByIdArr still have another lock and trans
		// 	$lockedKey = null;
		// 	$lock_it = $this->utils->lockResourceBy('', Utils::LOCK_ACTION_MANUALLY_PAY_CASHBACK, $lockedKey);
		// 	if ($lock_it) {
		// 		try {
		// 			$success = $this->group_level->manuallyPayCashbackByIdArr($selected_id_array);
		// 		} finally {
		// 			$rlt = $this->utils->releaseResourceBy('', Utils::LOCK_ACTION_MANUALLY_PAY_CASHBACK, $lockedKey);
		// 		}
		// 	} else {
		// 		$this->utils->debug_log('pay cashback failed, lock failed');
		// 	}

		// 	if ($success) {
		// 		$this->alertMessage(self::MESSAGE_TYPE_SUCCESS, lang('Pay cashback successfully'));
		// 	} else {
		// 		$this->alertMessage(self::MESSAGE_TYPE_ERROR, lang('Pay cashback failed'));

		// 	}

		// } else {
		// 	$this->alertMessage(self::MESSAGE_TYPE_ERROR, lang('Do not find any records'));
		// }

		// redirect($redirect_url);
	}

	/**
	 *
	 * manually calc cashback
	 *
	 * @return redirect back cashback report
	 */
	public function calc_cashback() {
		if (!$this->permissions->checkPermissions('manually_calculate_cashback')) {
			return $this->error_access();
		}

		//only 4 conditions
		$conditions = $this->safeLoadParams(array(
			'by_date_from' => $this->utils->getTodayForMysql(),
			'by_date_to' => $this->utils->getTodayForMysql(),
			'by_username' => '',
			// 'by_player_level' => '',
			// 'by_amount_greater_than' => '',
			// 'by_amount_less_than' => '',
			// 'by_paid_flag' => '',
			// 'enable_date' => 'false',
		));
		$conditions['enable_date'] = 'true';

		$this->load->model(['report_model', 'group_level', 'player_model']);
		$this->load->library(['data_tables', 'player_cashback_library']);

		$start = $conditions['by_date_from'];
		$end = $conditions['by_date_to'];
		$playerId = null;
		if (empty($conditions['by_username'])) {
			$playerId = $this->player_model->getPlayerIdByUsername($conditions['by_username']);
		}
		$controller = $this;
		$lock_info = ['lock_type' => Utils::LOCK_ACTION_MANUALLY_PAY_CASHBACK, 'lock_id' => ''];
		//try call
		$has_success_calc = false;
		$success = $this->utils->loopDateStartEnd($start, $end, '+1 day', $lock_info,
			function ($from, $to) use ($controller, $playerId, $has_success_calc) {
				$date = $controller->utils->formatDateForMysql($from);
				$result_cnt = $controller->player_cashback_library->manuallyCalculateTotalCashback($date, $playerId);
				if($result_cnt > 0) {
					$has_success_calc = true;
				}
				return $has_success_calc;
			});

		//lock action
		// $lockedKey=null;
		// $lock_it = $this->utils->lockResourceBy('', Utils::LOCK_ACTION_MANUALLY_PAY_CASHBACK, $lockedKey);
		// if ($lock_it) {
		// 	try {
		// 		$playerId=$this->player_model->getPlayerIdByUsername($conditions['by_username']);
		// 		$success=$this->group_level->totalCashbackManually($conditions['by_date_from'],
		// 			$conditions['by_date_to'], $playerId);
		// 	} finally {
		// 		$rlt = $this->utils->releaseResourceBy('', Utils::LOCK_ACTION_MANUALLY_PAY_CASHBACK, $lockedKey);
		// 	}
		// }else{
		// 	$this->utils->debug_log('pay cashback failed, lock failed');
		// }

		if ($success) {
			$this->alertMessage(self::MESSAGE_TYPE_SUCCESS, lang('Calculate cashback successfully'));
		} else {
			$this->alertMessage(self::MESSAGE_TYPE_ERROR, lang('Calculate cashback failed'));
		}

		redirect('/report_management/cashback_report?' . http_build_query($conditions));
	}

	/**
     * Level Upgrade Setting
	 */
	public function saveUpgradeSetting() {

		// conjunction variable referring to "or" "and"
		$vip_setting_form_ver = $this->config->item('vip_setting_form_ver');
		$enable_separate_accumulation_in_setting = $this->config->item('enable_separate_accumulation_in_setting');

		$conjunction = $this->input->post('conjunction');
		$formula = $this->input->post('formula');
		$accumulation = $this->input->post('accumulation');
		$accumulationFrom = $this->input->post('accumulationFrom');
		$upgrade_id = $this->input->post('upgrade_id');
		$bet_settings = $this->input->post('bet_settings'); // ver.2


		$accumulation_win_amount = $this->input->post('accumulation_win_amount');
		$accumulation_loss_amount = $this->input->post('accumulation_loss_amount');
		$accumulation_deposit_amount = $this->input->post('accumulation_deposit_amount');
		$accumulation_bet_amount = $this->input->post('accumulation_bet_amount');

		if( !empty($accumulationFrom) ){
			$_accumulation = $accumulationFrom;
		}else{
			$_accumulation = $accumulation;
		}

		$resultJson = array();

		if($vip_setting_form_ver == 2){
			// separate bet amount by game tree.
			$_formula = $this->input->post('formula');
			$formulaStr = json_encode($_formula);
			$resultJson = json_decode($formulaStr, true);
		}else{
			// ver.1
			$count = 0;
			foreach ($formula as $key => $val) {
				$operator = $this->getOperator($val[0]);

				if (sizeof($formula) > 1) {
					$count++;

					$conjunctionKeyOperator = 'operator_' . $count;

					if ($count >= 2) {
						$conjunctionKey = $count - 2;
						$resultJson[$conjunctionKeyOperator] = $conjunction[$conjunctionKey];
					}
					$resultJson[$key] = [$operator, $val[1]];
				} else {
					$resultJson[$key] = [$operator, $val[1]]; // if selected 1 option only
				}
			}
		} // EOF if($vip_setting_form_ver == 2)

		$data = [];
		if( ! empty($enable_separate_accumulation_in_setting) ){ // enable_separate_accumulation_in_setting=true only
			$separate_accumulation_settings = [];
			if( is_numeric($accumulation_bet_amount) ){ // optional
				$separate_accumulation_settings['bet_amount']['accumulation'] = $accumulation_bet_amount;
			}
			if( is_numeric($accumulation_deposit_amount) ){// optional
				$separate_accumulation_settings['deposit_amount']['accumulation'] = $accumulation_deposit_amount;
			}
			if( is_numeric($accumulation_loss_amount) ){// optional
				$separate_accumulation_settings['loss_amount']['accumulation'] = $accumulation_loss_amount;
			}
			if( is_numeric($accumulation_win_amount) ){// optional
				$separate_accumulation_settings['win_amount']['accumulation'] = $accumulation_win_amount;
			}

			$data['separate_accumulation_settings'] = json_encode($separate_accumulation_settings);
		}
		if( ! empty($bet_settings) ){
			$data['bet_amount_settings'] = json_encode($bet_settings);
		}else{
			$data['bet_amount_settings'] = null;
		}

		$data['setting_name'] = $this->input->post('settingName');
		$data['description'] = $this->input->post('description');
		$data['status'] = self::UPGRADE_ACTIVE;
		$data['level_upgrade'] = $this->input->post('levelUpgrade');
		$data['formula'] = json_encode($resultJson);
		$data['accumulation'] = $_accumulation;
		$data['created_at'] = $this->utils->getNowForMysql();
		if ($upgrade_id) {
			$data['upgrade_id'] = $upgrade_id;
		}
		$result = $this->group_level->addUpgradeLevelSetting($data);
	}

	public function upgradeLevelSetting() {
		echo json_encode(array('aaData' => $this->group_level->upgradeLevelSetting()));
	}

	public function deleteUpgradeLevelSetting() {
		$this->group_level->deleteUpgradeLevelSetting($this->input->post('id'));
	}

	public function enableDisableSetting() {
		$this->group_level->enableDisableSetting($this->input->post('id'), $this->input->post('status'));
	}

	public function upDownTemplateList() {
		$data = $this->group_level->upDownTemplateList();
		// echo json_encode($data);
		$this->returnJsonResult($data);
	}

	/**
	 * Convert OperatorNumber to the math symbol.
	 *
	 * @param integer $num
	 * @return string The  math symbol for update into the database.
	 */
	public function getOperator($num) {
		$operator = '';
		if ($num == 1) {
			$operator = '>=';
		}
		if ($num == 2) {
			$operator = '<=';
		}
		if ($num == 3) {
			$operator = '>';
		}
		if ($num == 4) {
			$operator = '<';
		}
		return $operator;
	}

	public function uploadVipLevelbadge() {
		$set_default_badge = $this->input->post('set_default_badge');
		$vipLevelId = $this->input->post('vipLevelId');
		$badge = "vip-badge.png";
		$this->load->model('vipsetting');
		$image = isset($_FILES['vipbadge']) ? $_FILES['vipbadge'] : null;
		$path = $this->utils->getVipBadgePath();
		$config = array(
			'allowed_types' => "jpg|jpeg|png|gif",
			'max_size' => $this->utils->getMaxUploadSizeByte(),
			'overwrite' => true,
			'remove_spaces' => true,
			'upload_path' => $path,
		);
		if (!$set_default_badge) {
			if (!empty($image)) {
				$this->load->library('multiple_image_uploader');
				$response = $this->multiple_image_uploader->do_multiple_uploads($image, $path, $config);
				if ($response['status'] == "success") {
					$result = $this->vipsetting->updateVipLevelBadge($vipLevelId, $response['filename'][0]);
					if ($result == 1 || $result == 0) {
						$this->alertMessage(self::MESSAGE_TYPE_SUCCESS, lang('Successfully uploaded.'));
					} else {
						$this->alertMessage(self::MESSAGE_TYPE_ERROR, lang('An error occurred and the upload failed.'));
					}
				} else {
					$this->alertMessage(self::MESSAGE_TYPE_ERROR, $response['message']);
				}
			}
		} else {
			$result = $this->vipsetting->updateVipLevelBadge($vipLevelId, $badge);
			if ($result == 1 || $result == 0) {
				$this->alertMessage(self::MESSAGE_TYPE_SUCCESS, lang('Successfully uploaded.'));
			} else {
				$this->alertMessage(self::MESSAGE_TYPE_ERROR, lang('An error occurred and the upload failed.'));
			}
		}
		redirect('/vipsetting_management/editVipGroupLevel/' . $vipLevelId);
	}

	public function updateVipWelcomeText() {
		$main = $this->input->post('main');
		$sub = $this->input->post('sub');
		$b1 = $this->input->post('b1');
		$b2 = $this->input->post('b2');
		$b3 = $this->input->post('b3');
		$b4 = $this->input->post('b4');
		$this->load->model(array('group_level', 'operatorglobalsettings'));
		$operator_setting = $this->operatorglobalsettings->getOperatorGlobalSetting("vip_welcome_text");
		$data['operator_setting'] = json_decode($operator_setting[0]['value'], true);
		$val = json_encode(array(
			'main' => $main,
			'sub' => $sub,
			'b1' => $b1,
			'b2' => $b2,
			'b3' => $b3,
			'b4' => $b4,
		));
		$result = $this->operatorglobalsettings->putSetting("vip_welcome_text", $val, $field = 'value');
		echo $result;
		/*// print_r($data['operator_setting']);
			        foreach ($data['operator_setting'] as $key => $os) {

			        	$array[$key] = $os;
		*/
		// echo ($array);
		//var_dump($array);
		//var_dump($data['operator_setting']);
	}
}

/* End of file player_management.php */
/* Location: ./application/controllers/player_management.php */
