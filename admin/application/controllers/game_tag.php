<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

require_once dirname(__FILE__) . '/BaseController.php';

/**
 * Game Type
 *
 * Game Type Controller
 *
 * General behaviors include
 * * Load Template
 * * Display messages for users
 * * Display Game Tag List
 * * Add Tag by Game Type
 * * 
 *
 * @category Game Model
 * @version 1.8.10
 * @copyright 2013-2022 tot
 */

class Game_Tag extends BaseController {

	function __construct() {
		parent::__construct();
		$this->load->helper('url');
		$this->load->library(array('permissions', 'form_validation', 'template', 'pagination', 'data_tables'));
        $this->load->model(array('external_system', 'game_description_model', 'game_tags', 'game_type_model', 'report_model', 'game_tag_list'));
	}

    public function viewGameTag() {

		$this->permissions->checkSettings();
		$this->permissions->setPermissions(); //will set the permission for the logged in user

		if (!$this->permissions->checkPermissions('game_description')) {
			$this->error_redirection();
		} else {

			if (($this->session->userdata('sidebar_status') == NULL)) {
				$this->session->set_userdata(array('sidebar_status' => 'active'));
			}
			
			if (($this->session->userdata('sidebar_status') == NULL)) {
				$this->session->set_userdata(array('sidebar_status' => 'active'));
			}

			// sets the history for breadcrumbs
			if (($this->session->userdata('well_crumbs') == NULL)) {
				$this->session->set_userdata(array('well_crumbs' => 'active'));
			}
			$data['languages'] = $this->CI->language_function->getAllSystemLanguages();
			$this->history->setHistory('header_system.system_word96', 'game_tag/viewGameTag');

			$data['game_platforms'] = $this->external_system->getAllActiveSytemGameApi(null, false, "system_code");
			$data['game_tags'] = $this->processTagsWithTranslation();

			// $this->template->add_css('resources/css/game_type/game_type.css');
			$this->loadTemplate(lang('Game Tags'), '', '', 'system');

			$this->template->write_view('main_content', 'system_management/game_tag_v2', $data);
			$this->template->render();
		}
	}

	private function processTagsWithTranslation(){
		$list_of_game_tags = $this->game_tags->getAllGameTags();
		foreach ($list_of_game_tags as $game_tag) {
			$game_tag['translation'] = [];
			$json_tag_name = $this->convertJsonStringToJson($game_tag['tag_name']);
			$game_tag['translation'] = $game_tag['tag_name'];
			if(gettype($json_tag_name) == 'array'){
				$game_tag['translation'] = $this->setValueToTranslation($json_tag_name);
			}
			$data_game_tags[] = $game_tag;
		}

		return $data_game_tags;
	}

    public function searchGamesByGameTypeId($gameTypeId) {
		#get game description and tag for each game by game type
		$list_of_game_obj = $this->game_description_model->getGamesByGameTypeId($gameTypeId);
		foreach ($list_of_game_obj as $object) {
			$list_of_game[] = $object->id;
		}
		$list_of_game_description = $this->game_description_model->getGameDescriptionByIdList($list_of_game);
		$list_of_game_tag = $this->game_tag_list->getGameTagListByGameDescriptionIds($list_of_game);

		#merge game descriptions and tags
		foreach ($list_of_game_description as $game) {
			$data = $game;
			$data['tags'] = [];
			$data['tag_game_order'] = [];

			foreach ($list_of_game_tag as $tag) {
				if ($game['id'] == $tag['game_description_id']) {
					$data['tags'][] = $tag['tag_code'];
					$data['tag_game_order'] = $tag['game_order'];
				}
			}

			#get name for language setting, else english
			$json_game_name = $this->convertJsonStringToJson($game['game_name']);
			$data['game_name'] = $this->setValueToTranslation($json_game_name);
			$data_game_description_and_tag[] = $data;
		}

        $this->returnJsonResult($data_game_description_and_tag);
	}

	public function batchTagGames($gameTagToApply){
		$game_description_ids = $this->input->post('data');
		$game_description_ids = json_decode($game_description_ids, true);

		$game_tag = $this->game_tag_list->checkBeforeInsertGameTagList($gameTagToApply, $game_description_ids);

		if ($game_tag) {
			$response = [
				'status' => 'success'
			];
		} else {
			$response = [
				'status' => 'error'
			];
		}

		$this->returnJsonResult($response);
	}

	private function convertJsonStringToJson($raw_json){
		if(strpos($raw_json, '_json:') === false){
			return $raw_json;
		}

		$json_string = substr($raw_json, strpos($raw_json, '_json:') + 6 );
		$converted_json = json_decode($json_string, true);
		
		return $converted_json;
	}

	private function setValueToTranslation($json_object = []){
		$translation = $json_object[Language_function::INT_LANG_ENGLISH];
		$languange_index = $this->language_function->getCurrentLanguage();
		
		if(!empty($json_object[$languange_index])){
			$translation = $json_object[$languange_index];
		}
		
		return $translation;
	}

    private function error_redirection(){
		$this->loadTemplate('Game List Management', '', '', 'system');
		$systemUrl = $this->utils->activeSystemSidebar();
		$data['redirect'] = $systemUrl;

		$message = lang('con.usm01');
		$this->alertMessage(self::MESSAGE_TYPE_ERROR, $message);

		$this->template->write_view('main_content', 'error_page', $data);
		$this->template->render();
	}

	private function loadTemplate($title, $description, $keywords, $activenav) {
		$this->template->write('title', $title);
		$this->template->write('description', $description);
		$this->template->write('keywords', $keywords);
		$this->template->write('activenav', $activenav);
		$this->template->write('username', $this->authentication->getUsername());
		$this->template->write('userId', $this->authentication->getUserId());
		$this->template->write_view('sidebar', 'system_management/sidebar');
	}

	public function remote_sync_game_tag_from_one_to_other_mdb(){
		$this->permissions->checkSettings();
		$this->permissions->setPermissions(); //will set the permission for the logged in user

		if (!$this->permissions->checkPermissions('game_description')) {
			$this->error_redirection();
		} else {
			$this->load->library(['lib_queue', 'language_function']);
			$success = true;
			$game_ids = $this->input->post('game_ids');
			$params['game_ids'] = $game_ids;
			$params['source_db'] = $this->utils->getActiveTargetDB();
	        $systemId = Queue_result::SYSTEM_UNKNOWN;
	        $funcName = 'sync_game_tag_from_one_to_other_mdb';
	        $callerType = Queue_result::CALLER_TYPE_ADMIN;
	        $caller = $this->authentication->getUserId();
	        $state = null;
	        $lang = $this->language_function->getCurrentLanguage();

			$token = $this->lib_queue->commonAddRemoteJob($systemId, $funcName, $params, $callerType, $caller, $state, $lang);

			$this->utils->debug_log('Game tag - remote_sync_game_tag_from_one_to_other_mdb', 'token', $token, 'params', $params);
        	redirect('/system_management/common_queue/' . $token);
		}
	}
}