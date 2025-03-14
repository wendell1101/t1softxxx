<?php

require_once dirname(__FILE__) . '/BaseController.php';
require_once dirname(__FILE__) . '/modules/cms/playercenter_notif_module.php';
require_once dirname(__FILE__) . '/modules/cms/playercenter_cmspopup_module.php';

/**
 * General behaviors include
 * * Loads Template
 * * Get New pages
 * * Add News
 * * Verify Add News
 * * Edit News
 * * Display News
 * * Hide News
 * * Delete News
 * * Filter News
 * * Displays affiliates data who paid
 *
 * @category CMS Management
 * @version 1.8.10
 * @copyright 2013-2022 tot
 */

class CMS_Management extends BaseController {
	use playercenter_notif_module;
	use playercenter_cmspopup_module;

	function __construct() {
		parent::__construct();
		$this->load->helper(array('date_helper', 'url'));
		$this->load->model('cms_model');
		$this->load->library(array('permissions', 'form_validation', 'template', 'pagination', 'excel', 'marketing_functions', 'report_functions', 'multiple_image_uploader'));
		$this->permissions->checkSettings();
		$this->permissions->setPermissions(); //will set the permission for the logged in user
	}

	/**
	 * Loads template for view based on regions in
	 * config > template.php
	 *
	 */
	private function loadTemplate($title, $description, $keywords, $activenav) {
		$this->template->add_css('resources/css/cms_management/style.css');
		$this->template->add_js('resources/js/cms_management/cms_management.js');
		# JS
		// $this->template->add_js('resources/js/moment.min.js');
		// $this->template->add_js('resources/js/daterangepicker.js');
		$this->template->add_js('resources/js/chosen.jquery.min.js');
		$this->template->add_js('resources/js/summernote.min.js');
		// $this->template->add_js('resources/js/bootstrap-datetimepicker.js');
		$this->template->add_js('resources/js/marketing_management/marketing_management.js');
        $this->template->add_js($this->utils->thirdpartyUrl('bootstrap-select/1.12.4/bootstrap-select.min.js'));
        $this->template->add_js($this->utils->thirdpartyUrl('bootstrap-switch/3.3.4/js/bootstrap-switch.min.js'));
		//$this->template->add_js('resources/js/jquery.dataTables.min.js');
		//$this->template->add_js('resources/js/dataTables.responsive.min.js');

		# CSS
		$this->template->add_css('resources/css/general/style.css');
		// $this->template->add_css('resources/css/daterangepicker-bs3.css');
		$this->template->add_css('resources/css/font-awesome.min.css');
		$this->template->add_css('resources/css/chosen.min.css');
		$this->template->add_css('resources/css/summernote.css');
		//$this->template->add_css('resources/css/jquery.dataTables.css');
		//$this->template->add_css('resources/css/dataTables.responsive.css');
		$this->template->add_css('resources/css/datatables.min.css');
		$this->template->add_js('resources/js/datatables.min.js');
        $this->template->add_css($this->utils->thirdpartyUrl('bootstrap-select/1.12.4/bootstrap-select.min.css'));
        $this->template->add_css($this->utils->thirdpartyUrl('bootstrap-switch/3.3.4/css/bootstrap3/bootstrap-switch.min.css'));

		$this->template->write('title', $title);
		$this->template->write('description', $description);
		$this->template->write('keywords', $keywords);
		$this->template->write('activenav', $activenav);
		$this->template->write('username', $this->authentication->getUsername());
		$this->template->write('userId', $this->authentication->getUserId());
		$this->template->write_view('sidebar', 'cms_management/sidebar');
	}

	/**
	 * Shows Error message if user can't access the page
	 *
	 * @return  rendered Template
	 */
	public function error_access() {
		$this->loadTemplate('CMS Management', '', '', 'cms');
		$cmsUrl = $this->utils->activeCMSSidebar();
		$data['redirect'] = $cmsUrl;

		$message = lang('con.cms01');
		$this->alertMessage(self::MESSAGE_TYPE_ERROR, $message);

		$this->template->write_view('main_content', 'error_page', $data);
		$this->template->render();
	}

	/**
	 * Index Page of Report Management
	 *
	 *
	 * @return  void
	 */
	public function index() {
		redirect(BASEURL . 'cms_management/viewPromoManager');
	}

	/**
	 * deactivate promo item in promolist
	 *
	 * @return  void
	 */
	public function deactivatePromo($promoId) {
		$promo = $this->marketing_functions->retrievePromo($promoId);
		$this->session->set_userdata('promoSort', 'activated');
		if ($this->cms_model->deactivatePromo($promoId)) {
			$this->saveAction('cms_management', 'Deactivate Promo', "User " . $this->authentication->getUsername() . " has deactivated promo '" . $promo['name'] . "'");
			redirect(BASEURL . 'cms_management/viewPromoManager');
		} else {
			$message = lang('con.cms02');
			$this->alertMessage(2, $message);
			redirect(BASEURL . 'cms_management/viewPromoManager');
		}
	}

	/**
	 * activate promo item in promolist
	 *
	 * @return  void
	 */
	public function activatePromo($promoId) {
		$promo = $this->marketing_functions->retrievePromo($promoId);
		$data['promoId'] = $promoId;
		$data['status'] = 'active';
		$this->session->set_userdata('promoSort', 'nonactivated');

		if ($this->cms_model->activatePromo($data)) {
			$this->saveAction('cms_management', 'Activate Promo', "User " . $this->authentication->getUsername() . " has activated promo '" . $promo['name'] . "'");
			redirect(BASEURL . 'cms_management/viewPromoManager');
		} else {
			$message = lang('con.cms02');
			$this->alertMessage(2, $message);
			redirect(BASEURL . 'cms_management/viewPromoManager');
		}
	}

	/**
	 * activate game item
	 *
	 * @return  void
	 */
	public function activateGame($cmsGameId) {
		$data['cmsGameId'] = $cmsGameId;
		$data['status'] = 'activated';

		if ($this->cms_model->activateGame($data)) {
			$this->saveAction('cms_management', 'Activate Game', "User " . $this->authentication->getUsername() . " has activated a game");
			redirect(BASEURL . 'cms_management/viewGameManager');
		} else {
			$message = lang('con.cms02');
			$this->alertMessage(2, $message);
			redirect(BASEURL . 'cms_management/viewGameManager');
		}
	}

	/**
	 * deactivate game item
	 *
	 * @return  void
	 */
	public function deactivateGame($cmsGameId) {
		$data['cmsGameId'] = $cmsGameId;
		$data['status'] = 'deactivated';

		if ($this->cms_model->deactivateGame($data)) {
			$this->saveAction('cms_management', 'Deactivate Game', "User " . $this->authentication->getUsername() . " has deactivated a game");
			redirect(BASEURL . 'cms_management/sortGame');
		} else {
			$message = lang('con.cms02');
			$this->alertMessage(2, $message);
			redirect(BASEURL . 'cms_management/sortGame');
		}
	}

	/**
	 * sort Game page
	 *
	 * @return  void
	 */
	public function sortGame() {
		$sort['gameProvider'] = $this->input->post('gameProvider');
		$sort['gameType'] = $this->input->post('gameType');
		$sort['progressiveType'] = $this->input->post('progressiveType');
		$sort['brandedGame'] = $this->input->post('brandedGame');
		$sort['activeGame'] = $this->input->post('activeGame');
		//var_dump($sort); exit();
		$this->session->set_userdata('gameProvider', $sort['gameProvider']);
		$this->session->set_userdata('gameType', $sort['gameType']);
		$this->session->set_userdata('progressiveType', $sort['progressiveType']);
		$this->session->set_userdata('brandedGame', $sort['brandedGame']);
		$this->session->set_userdata('activeGame', $sort['activeGame']);

		$this->loadTemplate('CMS Management', '', '', 'cms');

		$data['games'] = $this->cms_model->sortCMSGame($sort, null, null);
		$data['level'] = $this->cms_model->getRankingSettings();

		$this->template->write_view('main_content', 'cms_management/game/view_game_mgr', $data);
		$this->template->render();
	}

	/**
	 * ajax sort Game page
	 *
	 * @return  void
	 */
	public function getSortGamePages($segment) {
		$sort['gameProvider'] = $this->session->userdata('gameProvider');
		$sort['gameType'] = $this->session->userdata('gameType');
		$sort['progressiveType'] = $this->session->userdata('progressiveType');
		$sort['brandedGame'] = $this->session->userdata('brandedGame');
		$sort['activeGame'] = $this->session->userdata('activeGame');

		$this->loadTemplate('CMS Management', '', '', 'cms');

		$data['count_all'] = count($this->cms_model->sortCMSGame($sort, null, null));
		$config['base_url'] = "javascript:get_sort_game_pages(";
		$config['total_rows'] = $data['count_all'];
		$config['per_page'] = '10';
		$config['num_links'] = '1';

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
		$data['games'] = $this->cms_model->sortCMSGame($sort, null, $segment);

		$this->load->view('cms_management/game/ajax_view_game_mgr', $data);
	}

	/**
	 * view Game Manager page
	 *
	 * @return  void
	 */
	public function viewGameManager() {
		$this->loadTemplate('CMS Management', '', '', 'cms');

		$data['count_all'] = count($this->cms_model->getAllCMSGame(null, null));
		$config['base_url'] = "javascript:get_game_pages(";
		$config['total_rows'] = $data['count_all'];
		$config['per_page'] = '10';
		$config['num_links'] = '1';

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
		$data['games'] = $this->cms_model->getAllCMSGame(null, null);

		$data['level'] = $this->cms_model->getRankingSettings();

		$this->template->write_view('main_content', 'cms_management/game/view_game_mgr', $data);
		$this->template->render();
	}

	/**
	 * ajax Game Manager page
	 *
	 * @return  void
	 */
	public function getGamePages($segment) {
		$this->loadTemplate('CMS Management', '', '', 'cms');

		$data['count_all'] = count($this->cms_model->getAllCMSGame(null, null));
		$config['base_url'] = "javascript:get_game_pages(";
		$config['total_rows'] = $data['count_all'];
		$config['per_page'] = '10';
		$config['num_links'] = '1';

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
		$data['games'] = $this->cms_model->getAllCMSGame(null, $segment);
		//$data['level'] = $this->cms_model->getRankingSettings();

		$this->load->view('cms_management/game/ajax_view_game_mgr', $data);
	}

	/**
	 * set game category
	 *
	 * @return  void
	 */
	public function gameCategory($game_id) {
		if ($this->input->post('category') != null) {
			$category = implode(',', $this->input->post('category'));
			$category = explode(',', $category);

			$this->cms_model->deleteGameCategory($game_id);

			foreach ($category as $key => $value) {
				$data = array(
					'rankingLevelSettingId' => $value,
					'cmsGameId' => $game_id,
				);

				$this->cms_model->addGameCategory($data);
			}

			$this->saveAction('cms_management', 'Change Category Game', "User " . $this->authentication->getUsername() . " has successfully change the category of game/s");

			$this->alertMessage('1', 'Successfully add to Category');
			redirect(BASEURL . 'cms_management/viewGameManager', 'refresh');
		} else {
			redirect(BASEURL . 'cms_management/viewGameManager', 'refresh');
		}
	}

	/**
	 * sort Promo page
	 *
	 * @return  void
	 */
	public function sortPromo() {
		$promoSort = $this->input->post('promoSort');
		if ($promoSort == 'activated') {
			$this->session->set_userdata('promoSort', 'activated');
		} else {
			$this->session->set_userdata('promoSort', 'nonactivated');
		}

		redirect(BASEURL . 'cms_management/viewPromoManager');
	}

	/**
	 * view Promo Manager page
	 *
	 * @return  void
	 */
	public function viewPromoManager() {
		$this->loadTemplate('CMS Management', '', '', 'cms');

		if (!$this->session->userdata('promoSort')) {
			$this->session->set_userdata('promoSort', 'activated');
		}

		if ($this->session->userdata('promoSort') == 'activated') {
			$data['count_all'] = count($this->cms_model->getAllActivatedPromo(null, null));
			$config['base_url'] = "javascript:get_sort_promo_pages(";
			$config['total_rows'] = $data['count_all'];
			$config['per_page'] = '5';
			$config['num_links'] = '1';

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
			$data['promos'] = $this->cms_model->getAllActivatedPromo(null, null);
		} else {
			$data['count_all'] = count($this->cms_model->getAllPromo(null, null));
			$config['base_url'] = "javascript:get_promo_pages(";
			$config['total_rows'] = $data['count_all'];
			$config['per_page'] = '5';
			$config['num_links'] = '1';

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
			$data['promos'] = $this->cms_model->getAllPromo(null, null);
		}

		$this->template->write_view('main_content', 'cms_management/promo/view_promo_mgr', $data);
		$this->template->render();
	}

	public function get_promosetting_pages($segment) {
		$sort = "promoName";

		$data['count_all'] = count($this->cms_model->getPromoSettingList($sort, null, null));
		$config['base_url'] = "javascript:get_promosetting_pages(";
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
		$data['data'] = $this->cms_model->getPromoSettingList($sort, null, $segment);

		$this->load->view('cms_management/promotion/ajax_view_promo_list', $data);
	}

	/**
	 * search promo
	 *
	 *
	 * @return  redirect page
	 */
	public function searchPromoCms($search = '') {
		$data['count_all'] = count($this->cms_model->searchPromoCms($search, null, null));
		$config['base_url'] = "javascript:get_promosetting_pages(";
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
		$data['data'] = $this->cms_model->searchPromoCms($search, null, null);

		//export report permission checking
		if (!$this->permissions->checkPermissions('export_report')) {
			$data['export_report_permission'] = FALSE;
		} else {
			$data['export_report_permission'] = TRUE;
		}
		$this->load->view('cms_management/promotion/ajax_view_promo_list', $data);
	}

	/**
	 * sort promo cms
	 *
	 * @param   sort
	 * @return  void
	 */
	public function sortPromoCms($sort) {
		$data['count_all'] = count($this->cms_model->getPromoSettingList($sort, null, null));
		$config['base_url'] = "javascript:get_promosetting_pages(";
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
		$data['data'] = $this->cms_model->getPromoSettingList($sort, null, null);

		$this->load->view('cms_management/promotion/ajax_view_promo_list', $data);
	}

	/**
	 * promo category
	 *
	 * @return  void
	 */
	public function promoCategory($promoId) {
		if ($this->input->post('category') != null) {
			$category = implode(',', $this->input->post('category'));
			$category = explode(',', $category);

			$this->cms_model->deletePromoCategory($promoId);

			foreach ($category as $key => $value) {
				$data = array(
					'category' => $value,
					'promoId' => $promoId,
				);

				$this->cms_model->addPromoCategory($data);
			}

			$this->saveAction('cms_management', 'Change Category Promo', "User " . $this->authentication->getUsername() . " has successfully change the category of promo/s");

			$this->alertMessage('1', lang('con.cms08'));
			redirect(BASEURL . 'cms_management/viewPromoManager', 'refresh');
		} else {
			redirect(BASEURL . 'cms_management/viewPromoManager', 'refresh');
		}
	}

	/**
	 * view news or announcements page
	 *
	 * @return  void
	 */
	public function viewNews($offset = 0) {
		if (!$this->permissions->checkPermissions('view_news')) {
			$this->error_access();
		} else {
			$this->loadTemplate(lang('cms.02'), '', '', 'cms');

			$config['base_url'] = "/cms_management/viewNews/";

			$condition = [];
			if ($categoryId = $this->input->get('categoryId')) {
				$condition['categoryId'] = $categoryId;
			}

			$config['total_rows'] = count($this->cms_model->getAllNews(null, null, null, $condition, false));
			$config['per_page'] = 10;
			$config['num_links'] = '5';

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

			$data['condition'] = $condition;
			$data['news'] = $this->cms_model->getAllNews($config['per_page'], $offset, 'date desc', $condition, false);
			$data['newsCategoryList'] = $this->cms_model->getAllNewsCategory(null, null, null);

			$this->template->write_view('main_content', 'cms_management/news/view_news', $data);
			$this->template->render();
		}
	}

	/**
	 * view news or announcements page
	 *
	 * @return  void
	 */
	public function getNewsPages($segment, $sort) {
		$data['count_all'] = count($this->cms_model->getAllNews(null, null, null));
		$config['base_url'] = "javascript:get_news_pages(5)";
		$config['total_rows'] = $data['count_all'];
		$config['per_page'] = '5';
		$config['num_links'] = '1';

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
		$data['news'] = $this->cms_model->getAllNews(null, $segment, null);

		$this->load->view('cms_management/news/ajax_view_news', $data);
	}

	/**
	 * add news or announcements page
	 *
	 * @return  void
	 */
	public function addNews() {
		if (!$this->permissions->checkPermissions('view_news')) {
			$this->error_access();
		} else {
			$this->loadTemplate('CMS Management', '', '', 'cms');

			$data['newsCategoryList'] = $this->cms_model->getAllNewsCategory(null, null, null);

			$this->template->write_view('main_content', 'cms_management/news/add_news', $data);
			$this->template->render();
		}
	}

	/**
	 * verify add news or announcements page
	 *
	 * @return  void
	 */
	public function verifyAddNews() {
		if (!$this->permissions->checkPermissions('view_news')) {
			$this->error_access();
		} else {
			$this->form_validation->set_rules('content', 'Content', 'trim|xss_clean|required');
			$this->form_validation->set_rules('detail', 'Detail', 'trim');
			$this->form_validation->set_rules('categoryId', 'Category', 'trim|xss_clean|required');

			if ($this->form_validation->run() == false) {
				$this->addNews();
			} else {
				$title = $this->input->post('title');
				$content = $this->input->post('content');
				$detail = $this->input->post('detail');
				$categoryId = $this->input->post('categoryId');
	            $isDateRange = $this->input->post('is_daterange');
	            $start_date = $this->input->post('start_date');
	            $end_date = $this->input->post('end_date');

				$data = array(
					'title' => htmlspecialchars($title),
					'content' => nl2br( htmlspecialchars($content) ),
					'detail' => $detail,
					'categoryId' => $categoryId,
					'userId' => $this->authentication->getUserId(),
					'date' => date("Y-m-d H:i:s"),
					'is_daterange' => ($isDateRange && $start_date && $end_date) ? 1 : 0,
					'start_date' => $start_date,
					'end_date' => $end_date
				);

				$this->cms_model->addNews($data);

				$this->saveAction('cms_management', 'Add News', "User " . $this->authentication->getUsername() . " has successfully added news '" . $title . "'");

				$this->processTrackingCallback($data['userId'] ,$content);
			
				$this->alertMessage('1', lang('con.cms09'));
				redirect(BASEURL . 'cms_management/viewNews', 'refresh');
			}
		}
	}

	function processTrackingCallback($adminUserId, $message){
		$this->load->library(['player_trackingevent_library']);
		$params['message'] = $message;

		$this->utils->debug_log('============processTrackingCallback============ ', $adminUserId, $params);

		if(!empty($adminUserId)){
			$adminUser = $this->users->getUserInfoById($adminUserId);
		}

		if($this->utils->getConfig('third_party_tracking_platform_list')){
			$tracking_list = $this->utils->getConfig('third_party_tracking_platform_list');
			foreach($tracking_list as $key => $val){
				if(isset($val['always_tracking'])){
					$recid = $key;
					$this->player_trackingevent_library->processAddAnnouncementMessage($recid, $params, $adminUserId, $adminUser);
				}
			}
		}
	}

	/**
	 * edit news or announcements page
	 *
	 * @return  void
	 */
	public function editNews($news_id) {
		$this->loadTemplate('CMS Management', '', '', 'cms');

		$data['news'] = $this->cms_model->getNews($news_id);
		$data['newsCategoryList'] = $this->cms_model->getAllNewsCategory(null, null, null);

		$this->template->write_view('main_content', 'cms_management/news/edit_news', $data);
		$this->template->render();
	}

	/**
	 * verify edit news or announcements page
	 *
	 * @return  void
	 */
	public function verifyEditNews($news_id) {
		//$this->form_validation->set_rules('title', 'Title', 'trim|xss_clean|required|max(200)');
		$this->form_validation->set_rules('content', 'Content', 'trim|xss_clean|required');
		$this->form_validation->set_rules('detail', 'Detail', 'trim');
		//$this->form_validation->set_rules('language', 'Language', 'trim|xss_clean|required');
		$this->form_validation->set_rules('categoryId', 'Category', 'trim|xss_clean|required');

		if ($this->form_validation->run() == false) {
			$this->editNews($news_id);
		} else {
			$news = $this->cms_model->getNews($news_id);
			$title = $this->input->post('title');
			$content = $this->input->post('content');
			$detail = $this->input->post('detail');
			//$language = $this->input->post('language');
			$categoryId = $this->input->post('categoryId');
            $isDateRange = $this->input->post('is_daterange');
            $start_date = $this->input->post('start_date');
            $end_date = $this->input->post('end_date');
			//$filter = implode(',', $this->input->post('web'));

			//$category = $this->checkNewsCategory($filter);

			$data = array(
				'title' => htmlspecialchars($title),
				'content' => nl2br(htmlspecialchars($content)),
				'detail' => $detail,
				//'language' => $language,
				'categoryId' => $categoryId,
				'userId' => $this->authentication->getUserId(),
                'is_daterange' => ($isDateRange && $start_date && $end_date) ? 1 : 0,
                'start_date' => $start_date,
                'end_date' => $end_date
				//'category' => $category,
			);

			$this->cms_model->editNews($data, $news_id);

			$this->saveAction('cms_management', 'Edit News', "User " . $this->authentication->getUsername() . " has successfully edited news '" . $news['title'] . "' to '" . $title . "'");

			$this->alertMessage('1', lang('con.cms10'));
			redirect(BASEURL . 'cms_management/viewNews', 'refresh');
		}
	}

	/**
	 * show news or announcements page
	 *
	 * @return  void
	 */
	public function showNews($news_id, $title) {
		$news = $this->cms_model->getNews($news_id);
		$data = array(
			'status' => '0',
		);

		$this->cms_model->editNews($data, $news_id);

		$this->saveAction('cms_management', 'Show News', "User " . $this->authentication->getUsername() . " has successfully showed news '" . $news['title'] . "'");

		$this->alertMessage('1', lang('con.cms11') . ': ' . base64_decode($title));
		redirect(BASEURL . 'cms_management/viewNews', 'refresh');
	}

	/**
	 * hide news or announcements page
	 *
	 * @return  void
	 */
	public function hideNews($news_id, $title) {
		$news = $this->cms_model->getNews($news_id);
		$data = array(
			'status' => '1',
		);

		$this->cms_model->editNews($data, $news_id);
		$this->saveAction('cms_management', 'Hide News', "User " . $this->authentication->getUsername() . " has successfully hide news '" . $news['title'] . "'");
		$this->alertMessage('1', lang('con.cms12') . ': ' . base64_decode($title));
		redirect(BASEURL . 'cms_management/viewNews', 'refresh');
	}

	/**
	 * delete news or announcements page
	 *
	 * @return  void
	 */
	public function deleteNews($news_id, $title) {
		$news = $this->cms_model->getNews($news_id);
		$this->cms_model->deleteNews($news_id);
		$this->saveAction('cms_management', 'Show News', "User " . $this->authentication->getUsername() . " has successfully hide news '" . $news['title'] . "'");
		$this->alertMessage('1', lang('con.cms13') . ': ' . base64_decode($title));
		redirect(BASEURL . 'cms_management/viewNews', 'refresh');
	}

	/**
	 * filter news or announcements page
	 *
	 * @return  void
	 */
	public function filterNews($news_id, $title) {
		if (empty($this->input->post('web'))) {
			$this->alertMessage('2', lang('con.cms14') . ': ' . base64_decode($title));
			redirect(BASEURL . 'cms_management/viewNews', 'refresh');
		} else {
			$news = $this->cms_model->getNews($news_id);
			$filter = implode(',', $this->input->post('web'));

			$category = $this->checkNewsCategory($filter);

			$data = array(
				'category' => $category,
			);

			$this->cms_model->editNews($data, $news_id);
			$this->saveAction('cms_management', 'Change Category News', "User " . $this->authentication->getUsername() . " has successfully change the category of news '" . $news['title'] . "'");
			$this->alertMessage('1', lang('con.cms15') . ': ' . base64_decode($title));
			redirect(BASEURL . 'cms_management/viewNews', 'refresh');
		}
	}

	/**
	 * check category of news or announcements page
	 *
	 * @param  string
	 * @return string
	 */
	public function checkNewsCategory($filter) {
		$player = strpos($filter, 'player');
		$affiliate = strpos($filter, 'affiliate');
		$admin = strpos($filter, 'admin');

		/*echo 'player: ' . $player . '<br/>';
			echo 'affiliate: ' . $affiliate . '<br/>';
		*/

		if ($player !== false && $affiliate !== false && $admin !== false) {
			return '6';
		} else if ($player !== false && $affiliate === false && $admin === false) {
			return '0';
		} else if ($player === false && $affiliate !== false && $admin === false) {
			return '1';
		} else if ($player === false && $affiliate === false && $admin !== false) {
			return '2';
		} else if ($player !== false && $affiliate !== false && $admin === false) {
			return '3';
		} else if ($player !== false && $affiliate === false && $admin !== false) {
			return '4';
		} else if ($player === false && $affiliate !== false && $admin !== false) {
			return '5';
		} else {
			return '7';
		}
	}

	public function insert() {
		for ($i = 1; $i <= 239; $i++) {
			for ($x = 1; $x <= 6; $x++) {
				$data = array(
					'cmsGameId' => $i,
					'rankingLevelSettingId' => $x,
				);

				$this->cms_model->insertcms($data);
			}
		}
	}

	/**
	 * view news or announcements category page
	 *
	 * @return  void
	 */
	public function viewNewsCategory($offset = 0) {
		if (!$this->permissions->checkPermissions('view_news_category')) {
			$this->error_access();
		} else {
			$this->loadTemplate(lang('cms.newscategory'), '', '', 'cms');

			$config['base_url'] = "/cms_management/viewNewsCategory/";
			$config['total_rows'] = count($this->cms_model->getAllNewsCategory(null, null, null));
			$config['per_page'] = 10;
			$config['num_links'] = '5';

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

			//$data['total_pages'] = ceil($data['count_all'] / $config['per_page']);
			$data['news_category'] = $this->cms_model->getAllNewsCategory($config['per_page'], $offset, null);

			$this->template->write_view('main_content', 'cms_management/news/view_news_category', $data);
			$this->template->render();
		}
	}

	/**
	 * add news or announcements category page
	 *
	 * @return  void
	 */
	public function addNewsCategory() {
		$this->loadTemplate('CMS Management', '', '', 'cms');

		$this->template->write_view('main_content', 'cms_management/news/add_news_category');
		$this->template->render();
	}

	/**
	 * edit news or announcements category page
	 *
	 * @return  void
	 */
	public function editNewsCategory($id) {
		$this->loadTemplate('CMS Management', '', '', 'cms');

		$data['newscategory'] = $this->cms_model->getNewsCategory($id);

		$this->template->write_view('main_content', 'cms_management/news/edit_news_category', $data);
		$this->template->render();
	}

	/**
	 * verify add news or announcements category page
	 *
	 * @return  void
	 */
	public function verifyAddNewsCategory() {
		$this->form_validation->set_rules('name', 'name', 'trim|xss_clean|required');
		$this->form_validation->set_rules('language', 'Language', 'trim|xss_clean|required');

		if ($this->form_validation->run() == false) {
			$this->addNewsCategory();
		} else {
			$name = $this->input->post('name');
			$language = $this->input->post('language');

			$data = array(
				'name' => htmlspecialchars($name),
				'language' => $language,
				'userId' => $this->authentication->getUserId(),
				'date' => date("Y-m-d H:i:s"),
			);

			$this->cms_model->addNewsCategory($data);

			$this->saveAction('cms_management', 'Add News Category', "User " . $this->authentication->getUsername() . " has successfully added news category'" . $name . "'");

			$this->alertMessage(self::MESSAGE_TYPE_SUCCESS, lang('con.cms09') . ': ' . $name);
			redirect(BASEURL . 'cms_management/viewNewsCategory', 'refresh');
		}
	}

	/**
	 * verify edit news or announcements category page
	 *
	 * @return  void
	 */
	public function verifyEditNewsCategory($category_id) {
		$this->form_validation->set_rules('name', 'name', 'trim|xss_clean|required');
		$this->form_validation->set_rules('language', 'Language', 'trim|xss_clean|required');

		if ($this->form_validation->run() == false) {
			$this->editNewsCategory($category_id);
		} else {
			$name = $this->input->post('name');
			$language = $this->input->post('language');

			$data = array(
				'name' => htmlspecialchars($name),
				'language' => $language,
				'userId' => $this->authentication->getUserId(),
				'date' => date("Y-m-d H:i:s"),
			);

			$this->cms_model->editNewsCategory($data, $category_id);

			$this->saveAction('cms_management', 'Add News Category', "User " . $this->authentication->getUsername() . " has successfully added news category'" . $name . "'");

			$this->alertMessage(self::MESSAGE_TYPE_SUCCESS, lang('con.cms09') . ': ' . $name);
			redirect(BASEURL . 'cms_management/viewNewsCategory', 'refresh');
		}
	}

	public function deleteNewsCategory($category_id, $name) {
		$newscategory = $this->cms_model->getNewsCategory($category_id);
		$this->cms_model->deleteNewsCategory($category_id);
		$this->saveAction('cms_management', 'Show News Category', "User " . $this->authentication->getUsername() . " has successfully hide news category '" . $newscategory['name'] . "'");
		$this->alertMessage('1', lang('con.cms13') . ': ' . base64_decode($name));
		redirect(BASEURL . 'cms_management/viewNewsCategory', 'refresh');
	}

	/**
	 * Delete selected cms promo
	 *
	 * @param   int
	 * @return  redirect
	 */
	public function deleteSelectedPromoCms() {
		$promocms = $this->input->post('promocms');
		$today = date("Y-m-d H:i:s");

		if ($promocms != '') {
			foreach ($promocms as $promocmsId) {
				$this->cms_model->deletePromoCms($promocmsId);

				$data = array(
					'username' => $this->authentication->getUsername(),
					'management' => 'CMS Promo Setting Management',
					'userRole' => $this->rolesfunctions->getRoleByUserId($this->authentication->getUserId())['roleName'],
					'action' => 'Delete cms promo item id:' . $promocmsId,
					'description' => "User " . $this->authentication->getUsername() . " deleted cms promo id: " . $promocmsId,
					'logDate' => date("Y-m-d H:i:s"),
					'status' => '0',
				);

				$this->report_functions->recordAction($data);
			}

			$message = lang('con.cms16');
			$this->alertMessage(1, $message); //will set and send message to the user
			redirect(BASEURL . 'marketing_management/promoSettingList');
		} else {
			$message = lang('con.cms17');
			$this->alertMessage(2, $message);
			redirect(BASEURL . 'marketing_management/promoSettingList');
		}
	}

	/**
	 * Delete cms promo
	 *
	 * @param   int
	 * @return  redirect
	 */
	public function deletePromoCmsItem($promocmsId) {
		$this->cms_model->deletePromoCms($promocmsId);

		$data = array(
			'username' => $this->authentication->getUsername(),
			'management' => 'CMS Promo Setting Management',
			'userRole' => $this->rolesfunctions->getRoleByUserId($this->authentication->getUserId())['roleName'],
			'action' => 'Delete cms promo item id:' . $promocmsId,
			'description' => "User " . $this->authentication->getUsername() . " deleted cms promo id: " . $promocmsId,
			'logDate' => date("Y-m-d H:i:s"),
			'status' => '0',
		);

		$this->report_functions->recordAction($data);

		$message = lang('con.cms18');
		$this->alertMessage(1, $message);
		redirect(BASEURL . 'marketing_management/promoSettingList');
	}

	/**
	 * activate promo cms
	 *
	 * @param   promocmsId
	 * @param   status
	 * @return  redirect
	 */
	public function activatePromoCms($promocmsId, $status) {
		$data = array(
			'updatedBy' => $this->authentication->getUserId(),
			'updatedOn' => date("Y-m-d H:i:s"),
			'status' => $status,
			'promoCmsSettingId' => $promocmsId,
		);

		$this->cms_model->activatePromoCms($data);

		$data = array(
			'username' => $this->authentication->getUsername(),
			'management' => 'CMS Promo Management',
			'userRole' => $this->rolesfunctions->getRoleByUserId($this->authentication->getUserId())['roleName'],
			'action' => 'Update status of cms promo item: ' . $promocmsId . ' to status: ' . $status,
			'description' => "User " . $this->authentication->getUsername() . " edit cms promo id: " . $promocmsId . ' to status: ' . $status,
			'logDate' => date("Y-m-d H:i:s"),
			'status' => '0',
		);

		$this->report_functions->recordAction($data);

		redirect(BASEURL . 'marketing_management/promoSettingList');
	}

	/**
	 * preview promo cms
	 *
	 * @param   promocmsId
	 * @param   status
	 * @return  redirect
	 */
	public function viewPromoDetails($promocmsId) {
		$data['promocms'] = $this->cms_model->getPromoCmsDetails($promocmsId);
		// $this->utils->debug_log($data['promocms']);
		$this->load->view('cms_management/promotion/view_promo_details', $data);
		//var_dump($data);exit();
		//$this->output->set_output('<div class="modal-header"><button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button><h4 class="modal-title">CMS Promo Details Page</h4></div><div class="modal-body">'.$data['promoDetails'].'</div><div class="modal-footer"><button type="button" class="btn btn-default" data-dismiss="modal">Close</button></div>');
	}

	/**
	 * get promo details
	 *
	 * @param   int
	 * @return  redirect
	 */
	public function getPromoCmsDetails($promocmsId) {
		echo json_encode($this->cms_model->getPromoCmsDetails($promocmsId));
	}

	/**
	 * export report
	 *
	 * @param   str
	 * @return  redirect
	 */
	public function exportToExcel($type) {
		$data = array(
			'username' => $this->authentication->getUsername(),
			'management' => 'CMS Setting Management',
			'userRole' => $this->rolesfunctions->getRoleByUserId($this->authentication->getUserId())['roleName'],
			'action' => 'Exported CSM Promo List',
			'description' => "User " . $this->authentication->getUsername() . " exported CMS Promo List",
			'logDate' => date("Y-m-d H:i:s"),
			'status' => '0',
		);

		$this->report_functions->recordAction($data);

		switch ($type) {
		case 'cmspromolist':
			$result = $this->cms_model->getPromoCmsSettingListToExport();
			break;

		default:
			# code...
			break;
		}

		//var_dump($result);exit();
		//$this->excel->to_excel($result, 'cmspromolist-excel');
		$d = new DateTime();
		$this->utils->create_excel($result, 'cmspromolist_' . $d->format('Y_m_d_H_i_s') . '_' . rand(1, 999));
	}

    public function viewEmailTemplateManager()
    {
		if (!$this->permissions->checkPermissions('emailcms')) {
			$this->error_access();
		} else {
	        $this->load->model('email_template_model');
	        $this->load->library(['language_function', 'email_manager']);
	        $this->loadTemplate(lang('email.template.manager'), '', '', 'cms');
	        $data['platform_type'] = $this->email_template_model->getCurtPlatformType();
	        $data['template_type'] = $this->email_template_model->getTemplateType();
	        $data['email_template_list'] = $this->email_template_model->getAllTemplateList();
	        $data['system_lang'] = $this->language_function->getAllSystemLangLocalWord();

	        $this->template->write_view('main_content', 'cms_management/emailTemplate/view_email_template_manager', $data);
	        $this->template->render();
	    }
    }

    public function viewEmailTemplateManagerDetail($templateId, $langId = null)
    {
		if (!$this->permissions->checkPermissions('emailcms')) {
			$this->error_access();
		} else {
	        $this->load->model('email_template_model');
	        $this->load->library(['language_function', 'email_manager']);

	        $template = $this->email_template_model->getTemplateRowById($templateId);
	        $platformList = $this->email_template_model->getPlatformType();
	        $data['template_id'] = $templateId;
	        $data['lang_id'] = $langId;
	        $data['template_name'] = $template['template_name'];
	        $data['platform_name'] = $platformList[$template['platform_type']];
	        $data['email_template_detail'] = $this->email_template_model->getCurtPlatformTemplateDetail($template['template_name']);
	        $data['template_element'] = $this->email_manager->template($data['platform_name'], $data['template_name'])->getElement();

	        $this->loadTemplate(lang('email.template.manager') . ' - ' .lang('email_template_name_'.$data['template_name']), '', '', 'cms');
	        $this->template->write_view('main_content', 'cms_management/emailTemplate/view_email_template_manager_detail', $data);
	        $this->template->render();
	    }
    }

    public function ajax_get_email_setting()
    {
        $data['operator_send_lang'] = $this->operatorglobalsettings->getSettingJson('admin_email_template_set_send_lang');
        $data['operator_send_mode'] = $this->operatorglobalsettings->getSettingValue('admin_email_template_set_send_content_mode');

        $this->returnJsonResult($data);
    }

    public function ajax_set_email_setting()
    {
        $this->operatorglobalsettings->putSetting('admin_email_template_set_send_lang', json_encode($this->input->post('operator_send_lang')));
        $this->operatorglobalsettings->putSetting('admin_email_template_set_send_content_mode', $this->input->post('operator_send_mode'));

        return $this->returnCommon(BaseController::MESSAGE_TYPE_SUCCESS, lang('save.success'));
    }

    public function ajax_get_email_template_detail($templateId)
    {
        $this->load->model('email_template_model');
        $this->load->library(['email_manager']);
        $template = $this->email_template_model->getTemplateRowById($templateId);
        $emailTemplateDetail = $this->email_template_model->getCurtPlatformTemplateDetail($template['template_name']);
        $platformTypeList = $this->email_template_model->getPlatformType();


        $operator_send_mode = $this->operatorglobalsettings->getSettingValue('admin_email_template_set_send_content_mode');

        $data = [];
        foreach ($emailTemplateDetail as $key => $row) {

			$mail_subject      = $row['mail_subject'];
			$mail_content      = $row['mail_content'];
			$mail_content_text = $row['mail_content_text'];

        	if(empty($mail_subject) && empty($mail_content) && empty($mail_content)){

        		$t = $this->email_manager->template($platformTypeList[$row['platform_type']], $row['template_name'], [
		            'template_lang' => $row['template_lang']
		        ]);

		        $t->setGlobalLang($row['template_lang']);
		        $script = $t->getEmailScript();

        		$mail_subject = $script['mail_subject'];
        		$mail_content = $script['mail_content'];
        		$mail_content_text = $script['mail_plain_content'];
			}

        	if ($operator_send_mode == '2') { #Text
        		$mail_content = $mail_content_text;
        	}

        	#show first 18 letters for subject
			if(mb_strlen($mail_subject) > 18){
				$mail_subject = mb_substr($mail_subject, 0, 18, "utf-8") . '...';
			}


			#show first 40 letters (exclude EOL) for content
			$mail_content = strip_tags($mail_content, '<br>'); #clean html tags except <br>
			$mail_content = str_replace("<br />\n", "<br>", $mail_content);
			$mail_content = str_replace("<br>\n", "<br>", $mail_content);
			$mail_content = str_replace("\n", "<br>", $mail_content);


			$temp_content = mb_substr($mail_content, 0, 40, "utf-8"); #get first 40 letters including EOL
    		$EOL_count = substr_count($temp_content, '<br>') * 4; #count <br> in first 40 letters
    		$totol_EOL_count = $EOL_count;

    		$start = 40;
	    	while ($EOL_count > 0) {
	    		$EOL_count = substr_count(mb_substr($mail_content, $start, $EOL_count, "utf-8"), '<br>') * 4;
	    		$totol_EOL_count = $totol_EOL_count + $EOL_count;
	    		$start = $start + $EOL_count;
	    	}


			$content_limit = 40 + $totol_EOL_count;
			if(mb_strlen($mail_content) > $content_limit){
				$mail_content = mb_substr($mail_content, 0, $content_limit, "utf-8") . '...';
			}

            $data[] = [
                'no' => $key,
                'lang' => $row['template_lang_text'],
                'mail_subject' => $mail_subject,
                'mail_content' => $mail_content,
                'edit_btn' => [
                    'data_id' => $row['id'] ,
                    'data_lang' => $row['template_lang'],
                    'data_lang_word' => $row['template_lang_text'],
                    'data_tmpl_type'  => $row['template_type'],
                    'data_tmpl_name'  => $row['template_name'],
                    'data_platf_type' => $row['platform_type']
                ]
            ];
        }

        return $this->returnJsonResult(['data' => $data]);
    }

    public function ajax_get_email_template_modal($templateId, $lang = 1)
    {
        $this->load->model('email_template_model');
        $template = $this->email_template_model->getTemplateRowById($templateId);
        $emailTemplateDetail = $this->email_template_model->getCurtPlatformTemplateDetail($template['template_name']);
        $row = $emailTemplateDetail[$lang];

        $data = [
            'id' => $row['id'] ,
            'lang' => $row['template_lang'],
            'lang_word' => $row['template_lang_text'],
            'tmpl_type'  => $row['template_type'],
            'tmpl_name'  => $row['template_name'],
            'platf_type' => $row['platform_type']
        ];

        return $this->returnJsonResult($data);
    }

    public function ajax_get_email_template()
    {
        $this->load->model('email_template_model');
        $this->load->library(['email_manager']);

        $templateName = $this->input->get('template_name');
        $templateLang = $this->input->get('template_lang');
        $platformType = $this->input->get('platform_type');

        $platformTypeList = $this->email_template_model->getPlatformType();

        $script = $this->email_manager->template($platformTypeList[$platformType], $templateName, [
            'template_lang' => $templateLang
        ])->getEmailScript();

        return $this->returnJsonResult($script);
    }

    public function ajax_get_email_template_by_name()
    {
        $this->load->model('email_template_model');
        $this->load->library(['email_manager']);

        $templateName = $this->input->get('template_name');
        $templateLang = $this->input->get('template_lang');
        $platformType = $this->input->get('platform_type');

        $platformTypeList = $this->email_template_model->getPlatformType();

        $script = $this->email_manager->template($platformTypeList[$platformType], $templateName, [
            'template_lang' => $templateLang
        ])->getEmailScript();

        $script['test_description'] =  str_replace('[target]', lang('email.'.$platformTypeList[$platformType]), lang('email.preview.sending.test.description')) ;
        return $this->returnJsonResult([$script]);
    }

    public function ajax_edit_email_template()
    {
        $this->load->model('email_template_model');
        $this->load->library('authentication');

        $id = $this->input->post('id');
        $data['template_lang'] = $this->input->post('template_lang');
        $data['template_name'] = $this->input->post('template_name');
        $data['template_type'] = $this->input->post('template_type');
        $data['platform_type'] = $this->input->post('platform_type');
        $data['mail_subject']  = $this->input->post('mail_subject');
        $data['mail_content']  = $this->input->post('mail_content');
        $data['mail_content_text'] = $this->input->post('mail_content_text');

        if (!$id) {
            $data['createdBy'] = $this->authentication->getUserId();
            $data['created_at'] = date('Y-m-d H:i:s');
            $bool = $this->email_template_model->insertData($data);
        } else {
            $data['updatedBy'] = $this->authentication->getUserId();
            $data['updated_at'] = date('Y-m-d H:i:s');
            $bool = $this->email_template_model->updateData($id, $data);
        }

        return $this->returnJsonResult(($bool) ? 1 : 0);
    }

    public function ajax_enable_email_template()
    {
        $this->load->model('email_template_model');

        $data['is_enable'] = $this->input->post('is_enable');
        $cond['platform_type'] = $this->input->post('platform_type');
        $cond['template_name'] = $this->input->post('template_name');

        $this->email_template_model->updateData(null, $data, $cond);
    }

    public function ajax_send_preview_email()
    {
        $this->load->model(['player', 'affiliate', 'agency_model', 'email_template_model', 'queue_result']);
        $this->load->library(['email_manager']);

        $username = $this->input->post('username');
        $curtMode = $this->input->post('curt_mode');
        $templateName = $this->input->post('template_name');
        $templateLang = $this->input->post('template_lang');
        $platformType = $this->input->post('platform_type');

        $platformList = $this->email_template_model->getPlatformType();
        $platform = $platformList[$platformType];

        if ($platform == 'player') {
            $user = $this->player->getPlayerByUsername($username);
            $params['player_id'] = @$user['playerId'];
        }

        if ($platform == 'affiliate') {
            $user = $this->affiliate->getAffiliateByName($username);
            $params['affiliate_id'] = @$user['affiliateId'];
        }

        if ($platform == 'agency') {
            $user = $this->agency_model->get_agent_by_name($username);
            $params['agency_id'] = @$user['agent_id'];
        }

        $email = isset($user['email']) ? $user['email'] : '';

        if (!$email) {
            return $this->returnJsonResult(0);
        }

        $params['template_lang'] = $templateLang;
        $params['template_mode'] = $curtMode;

        $template = $this->email_manager->template($platform, $templateName, $params);
        $template->sendingEmail($email, Queue_result::CALLER_TYPE_ADMIN, $this->authentication->getUserId(), false);

        return $this->returnJsonResult(1);
    }

    /**
     * View Sendmessage Tempalate Page
     *
     * bind premission with Email Tempalate
     * @return  void
     */
    public function viewMsgtpl() {
        if (!$this->permissions->checkPermissions('emailcms')) {
            $this->error_access();
        } else {
            $this->loadTemplate(lang('cms.msg.template'), '', '', 'cms');

            $data['email'] = $this->cms_model->getMsgTemplate();
            //$data['email'] = $this->cms_model->getEmailTemplate();
            $this->template->write_view('main_content', 'cms_management/message/view_msgtpl', $data);
            $this->template->render();
        }
    }

    /**
     * get email template details
     *
     * @param   int
     * @return  redirect
     */
    public function getMsgCmsDetails($id) {
        echo json_encode($this->cms_model->getMsgCmsDetails($id));
    }

    /**
     * edit email template details
     *
     *
     * @param   int
     * @return  redirect
     */
    public function editMsgTemplate() {
        $this->form_validation->set_rules('msgTemplateName', 'Message Template Title', 'trim|required');
        $this->form_validation->set_rules('msgTemplateContent', 'Message Template Content', 'trim|required');

        if ($this->form_validation->run() == false) {
            $message = lang('con.cf02');
            $this->alertMessage(2, $message);
        } else {
            $editMsgTemplateId = $this->input->post('editMsgTemplateId');
            $msgTemplateName = $this->input->post('msgTemplateName');
            $msgTemplateContent = $this->input->post('msgTemplateContent');

            $data = array(
                'id' => $editMsgTemplateId,
                'note' => $msgTemplateName,
                'template' => $msgTemplateContent,
            );

            $this->cms_model->editMsgTempalte($data);
            $message = $msgTemplateName . ' ' . lang('con.cf04');

            $this->recordAction('Edit CMS Msg Manager', 'Edit CMS Msg Template Title: ' . $msgTemplateName,
                "User " . $this->authentication->getUsername() . " edit CMS msg template id: " . $editMsgTemplateId);



            $this->alertMessage(self::MESSAGE_TYPE_SUCCESS, $message);
        }
        redirect('cms_management/viewMsgtpl');
    }

	/**
	 * generate sites
	 *
	 * @return  void
	 */
	public function generateSites() {
		if (!$this->permissions->checkPermissions('generate_sites')) {
			$this->error_access();
		} else {
			$this->loadTemplate('CMS Management', '', '', 'cms');
			$this->template->write_view('main_content', 'cms_management/generatesites/generate_sites');
			$this->template->render();
		}
	}

	/**
	 * generate sites now
	 *
	 * @return  void
	 */
	public function generateSitesNow() {
		if (!$this->permissions->checkPermissions('generate_sites')) {
			$this->error_access();
		} else {
			$this->loadTemplate('CMS Management', '', '', 'cms');
			$this->template->write_view('main_content', 'cms_management/generatesites/generate_sites_now');
			$this->template->render();
		}
	}

	public function smtp_setting($config = null) {
		if (!$this->permissions->checkPermissions('smtp_setting')) {
			$this->error_access();
		} else {

			$data['mail_smtp_server'] = $config ? $config['mail_smtp_server'] : $this->operatorglobalsettings->getSettingValue('mail_smtp_server');
			$data['mail_smtp_port'] = $config ? $config['mail_smtp_port'] : $this->operatorglobalsettings->getSettingValue('mail_smtp_port');
			$data['mail_smtp_auth'] = $config ? $config['mail_smtp_auth'] : $this->operatorglobalsettings->getSettingValue('mail_smtp_auth');
			$data['mail_smtp_secure'] = $config ? $config['mail_smtp_secure'] : $this->operatorglobalsettings->getSettingValue('mail_smtp_secure');
			$data['mail_smtp_username'] = $config ? $config['mail_smtp_username'] : $this->operatorglobalsettings->getSettingValue('mail_smtp_username');
			$data['mail_smtp_password'] = $config ? $config['mail_smtp_password'] : $this->operatorglobalsettings->getSettingValue('mail_smtp_password');
			$data['mail_from'] = $config ? $config['mail_from'] : $this->operatorglobalsettings->getSettingValue('mail_from');
			$data['mail_from_email'] = $config ? $config['mail_from_email'] : $this->operatorglobalsettings->getSettingValue('mail_from_email');
			$data['disable_smtp_ssl_verify'] = $config ? $config['disable_smtp_ssl_verify'] : $this->operatorglobalsettings->getSettingValue('disable_smtp_ssl_verify');
			$data['email'] = $config ? $config['email'] : '';

			$this->loadTemplate(lang('smtp.setting.title'), '', '', 'cms');

			if ($this->utils->isSmtpApiEnabled())
				$this->template->write_view('main_content', 'cms_management/smtp_setting/smtp_setting_with_api', $data);
			else
				$this->template->write_view('main_content', 'cms_management/smtp_setting/smtp_setting', $data);

			$this->template->render();
		}
	}

	/**
	 * overview: view SMTP log from SMTP Settings Test button
	 *
	 * @return rendered templete
	 */
	public function test_email($config = null){
		if (!$this->permissions->checkPermissions('smtp_setting')) {
			$this->error_access();
		} else {

			$data['mail_smtp_server'] = $config ? $config['mail_smtp_server'] : $this->operatorglobalsettings->getSettingValue('mail_smtp_server');
			$data['mail_smtp_port'] = $config ? $config['mail_smtp_port'] : $this->operatorglobalsettings->getSettingValue('mail_smtp_port');
			$data['mail_smtp_auth'] = $config ? $config['mail_smtp_auth'] : $this->operatorglobalsettings->getSettingValue('mail_smtp_auth');
			$data['mail_smtp_secure'] = $config ? $config['mail_smtp_secure'] : $this->operatorglobalsettings->getSettingValue('mail_smtp_secure');
			$data['mail_smtp_username'] = $config ? $config['mail_smtp_username'] : $this->operatorglobalsettings->getSettingValue('mail_smtp_username');

			$tempPw = $this->operatorglobalsettings->getSettingValue('mail_smtp_password');

			//OGP-15795 can be removed after smtp password is working as encrypted
			$tempDecrypted = $this->utils->decryptPassword($tempPw);
			if ($tempDecrypted == false) {
				$tempDecrypted = $tempPw;
			}

			$data['mail_smtp_password'] = !empty($config['mail_smtp_password']) ? $config['mail_smtp_password'] : $tempDecrypted;

			$data['mail_from'] = $config ? $config['mail_from'] : $this->operatorglobalsettings->getSettingValue('mail_from');
			$data['mail_from_email'] = $config ? $config['mail_from_email'] : $this->operatorglobalsettings->getSettingValue('mail_from_email');
			$data['disable_smtp_ssl_verify'] = $config ? $config['disable_smtp_ssl_verify'] : $this->operatorglobalsettings->getSettingValue('disable_smtp_ssl_verify');
			$data['email'] = $config ? $config['email'] : '';

			$this->loadTemplate('CMS Management', '', '', 'cms');
			$this->template->write_view('main_content', 'cms_management/smtp_setting/test_email', $data);
			$this->template->render();
		}
	}

	/**
	 * Test email sending thru SMTP API
	 *
	 * @return rendered template
	 */
	public function test_email_smtp($config){
		if(!$this->permissions->checkPermissions('smtp_setting') || !$this->utils->isSmtpApiEnabled() || !$config['test_api_email_recipient']) return $this->error_access();

		$data = array();
		$data['from_name']  = $config['smtp_api_mail_from_name']  ?: $this->operatorglobalsettings->getSettingValue('smtp_api_mail_from_name') ?: $this->operatorglobalsettings->getSettingValue('mail_from');
		$data['from_email'] = $config['smtp_api_mail_from_email'] ?: $this->operatorglobalsettings->getSettingValue('smtp_api_mail_from_email') ?: $this->operatorglobalsettings->getSettingValue('mail_from_email');
		$data['email'] 		= $config['test_api_email_recipient'];

		$this->loadTemplate('CMS Management', '', '', 'cms');
		$this->template->write_view('main_content', 'cms_management/smtp_setting/test_smtp_email', $data);
		$this->template->render();
	}

	/**
	 * Change email sending method
	 * Either using SMTP Settings || SMTP API
	 *
	 * @return booloean
	 */
	public function change_enabled_email_sending_method(){
		if(!$this->utils->isSmtpApiEnabled() || !$this->input->post()) {
			echo false;
			return;
		}

		if($this->input->post('use_smtp_api') == 'true')
			echo $this->utils->putOperatorSetting('use_smtp_api','true');
		else
			echo $this->utils->putOperatorSetting('use_smtp_api','false');
	}

	public function post_smtp_setting() {
		if ($this->input->post('action') == 'save') {
			$this->load->model(['operatorglobalsettings']);

			$key_array=['mail_smtp_server', 'mail_smtp_port', 'mail_smtp_auth', 'mail_smtp_secure', 'mail_smtp_username',
				'mail_from', 'mail_from_email', 'disable_smtp_ssl_verify',
			];

			$mail_smtp_password = $this->input->post('mail_smtp_password');
			if (!empty($mail_smtp_password) && $this->checkPassword($mail_smtp_password)) {
				array_push($key_array, "mail_smtp_password");
			}

			foreach ($key_array as $key) {
                if ($key == "mail_smtp_password") {
                    $value = $this->utils->encryptPassword($this->input->post($key));
                }else {
					$value=$this->input->post($key);
				}

				//-- OGP-7885
				if($this->operatorglobalsettings->existsSetting($key)){
					$this->operatorglobalsettings->putSetting($key, $value);
				} else {
					$this->operatorglobalsettings->insertSetting($key, $value);
				}
			}

			$this->alertMessage(1, lang('smtp.setting.update.success'));
			redirect('cms_management/smtp_setting');
		} else {
			$this->load->library('email_setting');
			$mail_config=$this->input->post();
			$mail_config['is_debug']=true;
			$this->test_email($this->input->post());
		}
	}

	public function checkPassword($password) {
		if ($this->operatorglobalsettings->getSettingValue('mail_smtp_password') == $password) {
			$this->utils->debug_log('==============mail_smtp_password checkPassword same', $password);
			return false;
		}
		return true;
	}

	public function post_smtp_api_configuration() {
		if ($this->input->post('action') == 'save') {

			$this->load->model(['operatorglobalsettings']);

			$key_array=['smtp_api_mail_from_name', 'smtp_api_mail_from_email'];
			foreach ($key_array as $key) {

				$value=$this->input->post($key);

				if($this->operatorglobalsettings->existsSetting($key))
					$this->operatorglobalsettings->putSetting($key, $value);
				else
					$this->operatorglobalsettings->insertSetting($key, $value);

			}

			$this->alertMessage(1, lang('smtp.setting.update.success'));
			redirect('cms_management/smtp_setting');
		} else {
			$this->test_email_smtp($this->input->post());
		}
	}

	public function staticSites() {
		//permission
		if (!$this->permissions->checkPermissions('staticSites')) {
			//super only function
			return $this->error_access();
		}
		//only super site
		if (!$this->utils->isSuperSiteOrNoMDB()) {
			//super only function
			return redirect('/');
		}

		$this->load->model('Static_site');

		$this->loadTemplate(lang('Static sites'), '', '', 'cms');
        $static_sites = $this->Static_site->getAllStaticSites(null, null, 'id'); //take data
        $data['static_sites'] = (empty($static_sites)) ? [] : $static_sites;

		$this->template->add_js('resources/js/bootstrap-filestyle.min.js');
		$this->template->write_view('main_content', 'cms_management/static_sites/static_sites',$data);
		$this->template->render();
	}

	public function saveStaticSites() {
		if (!$this->permissions->checkPermissions('staticSites')) {
			//super site only
			return $this->error_access();
		}

		//only super site
		if (!$this->utils->isSuperSiteOrNoMDB()) {
			//super only function
			return redirect('/');
		}

		$this->load->model('Static_site');
		$this->load->library(['upload', 'player_main_js_library']);

		$path = $this->utils->getUploadPath();
		$this->utils->addSuffixOnMDB($path);
		$imgName1 = isset($_FILES['logoIconFilepath']['name']) ? $_FILES['logoIconFilepath']['name'] : null;
		$imgName2 = isset($_FILES['logoIconHorizontalFilepath']['name']) ? $_FILES['logoIconHorizontalFilepath']['name'] : null;
		$imgName3 = isset($_FILES['favIconFilepath']['name']) ? $_FILES['favIconFilepath']['name'] : null;
		$wrongExt1 = false;
		$wrongExt2 = false;
		$wrongExt3 = false;
		if (!empty($imgName1)||!empty($imgName2)) {
			$ext_allowed = array("jpg", "jpeg", "gif", "png");
			if($imgName1!=null){
				$ext1 = explode('.', $imgName1);
				$ext1 = $ext1[count($ext1) - 1];
				$wrongExt1 = in_array($ext1, $ext_allowed)?0:1;
			}
			if($imgName2!=null){
				$ext2 = explode('.', $imgName2);
				$ext2 = $ext2[count($ext2) - 1];
				$wrongExt2 = in_array($ext2, $ext_allowed)?0:1;
			}
			if($imgName3!=null){
				$ext3 = explode('.', $imgName3);
				$ext3 = $ext3[count($ext3) - 1];
				$wrongExt3 = in_array($ext3, $ext_allowed)?0:1;
			}
		}
		//echo $ext1[1].$wrongExt1." ".$wrongExt2;print_r(explode('.', $imgName1));exit;
		if ($wrongExt1||$wrongExt2||$wrongExt3) {
			$message = lang('con.aff46');
			$this->alertMessage(2, $message);
		}

		$isSiteNameExist = $this->Static_site->getSiteByName($this->input->post('site_name'));
		if ($isSiteNameExist && !$this->input->post('id')) {
			$message = lang('cms.siteExist');
			$this->alertMessage(2, $message);
		} else {
			$siteName = $this->input->post('site_name');
			if ($imgName1 != null) {
				$ext1 = explode('.', $imgName1);
				$file_name = 'logoIconFilepath.'.$ext1[1];
				$result = $this->upload($file_name, $path, 'logoIconFilepath');
				$_POST['logo_icon_filepath'] = $file_name;
			}
			if ($imgName2 != null) {
				$ext2 = explode('.', $imgName2);
				$file_name = 'logoIconHorizontalFilepath.'.$ext2[1];
				$result = $this->upload($file_name, $path, 'logoIconHorizontalFilepath');
				$_POST['logo_icon_horizontal_filepath'] = $file_name;
			}
			if ($imgName3 != null) {
				$ext3 = explode('.', $imgName3);
				$file_name = 'favIconFilepath.'.$ext3[1];
				$result = $this->upload($file_name, $path, 'favIconFilepath');
				$_POST['fav_icon_filepath'] = $file_name;
			}

			if($this->input->post('id')){ // edit
				$this->Static_site->editStaticSite($_POST, $this->input->post('id'));
				$message = lang('role.113') . " <b>" . $siteName . "</b> " . lang('con.aff30');
			}else{ // add new
				$this->Static_site->addStaticSites($_POST);
				$message = lang('role.113') . " <b>" . $siteName . "</b> " . lang('con.aff31');
				$this->alertMessage(1, $message);
			}

			$this->player_main_js_library->generate_static_scripts($siteName);
		}
		redirect('cms_management/staticSites');
	}

	public function deleteStaticSite($id){
		if (!$this->permissions->checkPermissions('generate_sites')) {
			$this->error_access();
		} else {
			$this->load->model('Static_site');
			if($id==1){
				$message = lang("cms.cantdeleteStaticSite");
				$this->alertMessage(2, $message); //will set and send message to the user
			}else{
				$this->Static_site->deleteStaticSite($id);
				$message = lang('cms.deleteStaticSite');
				$this->alertMessage(1, $message); //will set and send message to the user
			}
			redirect('cms_management/staticSites', 'refresh'); //redirect to viewRoles
		}
	}

	public function deactivateStaticSite($id) {
		$this->load->model('Static_site');
		$data = array(
			'status' => '0',
		);
		$this->Static_site->editStaticSite($data, $id);
		$message = lang('cms.deactivateStaticSite');
		$this->alertMessage(1, $message);
		redirect('cms_management/staticSites', 'refresh');
	}

	public function removeLogoStaticSite($id) {
		$this->load->model('Static_site');
		$data = array('logo_icon_filepath'=>'og-login-logo.png');
		$this->Static_site->deleteStaticSiteLogoIconPath($data,$id);
		 return true;
	}

	public function activateStaticSite($id) {
		$this->load->model('Static_site');
		$data = array(
			'status' => '1',
		);
		$this->Static_site->editStaticSite($data, $id);
		$message = lang('cms.activateStaticSite');
		$this->alertMessage(1, $message);
		redirect('cms_management/staticSites', 'refresh');
	}

	public function static_site_script_generate($site_name){
        if (!$this->permissions->checkPermissions('generate_sites')) {
            $this->error_access();
        } else {
            $this->load->library(['player_main_js_library']);

            $this->player_main_js_library->generate_static_scripts($site_name);

            // this is right url
            redirect($this->utils->playerResUrl("built_in/{$site_name}_all.min.js"));
        }
    }

	public function upload($file_name,$path,$field_name, $configParam = null) {

		$config['upload_path'] = $path;
		$config['allowed_types'] = 'jpg|jpeg|gif|png|ico|webp';
		$config['max_size'] = '';
		$config['remove_spaces'] = true;
		$config['overwrite'] = true;
		$config['file_name'] = $file_name;
		$config['max_width'] = '';
		$config['max_height'] = '';
        if($configParam) {
            $config = array_merge($config, $configParam);
        }
		$this->load->library('upload', $config);
		$this->upload->initialize($config);
        $result = $this->upload->do_upload($field_name);
		return $result ? $result : $this->upload->display_errors('','');
	}

	public function deleteSelectedStaticSites() {
		$this->load->model('Static_site');
		$sites = $this->input->post('sites');

		if ($sites != '') {
			foreach ($sites as $site_id) {
				if($site_id != 1){
					$this->Static_site->deleteStaticSite($site_id);
				}
			}

			$message = lang('cms.deleteStaticSite');
			$this->alertMessage(1, $message); //will set and send message to the user
		} else {
			$message = lang('cms.deleteSelectFirst');
			$this->alertMessage(2, $message);

		}
		redirect('cms_management/staticSites', 'refresh');
	}

	public function player_center_settings() {
		if (!$this->permissions->checkPermissions('player_center_settings')) {
			$this->error_access();
		} else {
			$this->load->model(array('operatorglobalsettings','static_site'));
            $prefer_player_center_logo = (int)$this->operatorglobalsettings->getSettingIntValue('prefer_player_center_logo');
            $player_center_mobile_header_title_style = $this->operatorglobalsettings->getSettingIntValue('player_center_mobile_header_title_style', 1);

            $data['logo_icon'] = $this->static_site->getDefaultLogoUrl();
	        $data['prefer_player_center_logo'] = $prefer_player_center_logo;
	        $data['player_center_mobile_header_title_style'] = $player_center_mobile_header_title_style;
	        $data['playerCenterLanguage'] = $this->operatorglobalsettings->getSettingJson('player_center_language');
	        $data['isForceToDefaultLanguage'] = $this->utils->isForcePlayerCenterToDefaultLanguage();
	        $data['isRetainCurrentLanguage'] = $this->utils->isRetainCurrentLanguage();
	        $data['withdrawalVerification'] = $this->utils->getConfig('withdraw_verification');

	        $data['cms_version']=$this->operatorglobalsettings->getSettingJson('cms_version');

            $data['template_list'] = call_user_func_array(function($template_setting_file_path){
                $template = [];

                include_once $template_setting_file_path;

                $template_list = [];
                foreach($template as $template_name => $template_setting){
                    if($template_name === 'active_template') continue;

                    $template_list[] = $template_name;
                }

                return $template_list;
            }, [realpath(APPPATH . '/../../player/public/' . APPPATH . '/config/template.php')]);

			$userId = $this->authentication->getUserId();
			$username = $this->authentication->getUsername();
			$render = true;

			$this->loadTemplate(lang('Player Center Settings'), '', '', 'cms');
			$this->template->add_js('resources/js/bootstrap-filestyle.min.js');
			$this->template->write_view('main_content', 'cms_management/player_center_settings',$data);
			$this->template->render();

		}
	}

	public function save_operator_settings(){
		//only admin
		//only admin
		if (!$this->permissions->checkPermissions('player_center_settings')) {
			$this->returnErrorStatus();
			// $this->showErrorAccess(lang('System Message'),'system_management/sidebar','system_message');
		}

		$success=$this->utils->putOperatorSetting('sms_registration_template', $this->input->post('sms_registration_template'));
		$message=null;

		if($success){
			$this->alertMessage(self::MESSAGE_TYPE_SUCCESS, lang('Save settings successfully'));
		} else {
			$this->alertMessage(self::MESSAGE_TYPE_ERROR, !empty($message) ? $message : lang('error.default.db.message'));
		}

		redirect('cms_management/player_center_settings');
	}

	public function save_cms_version(){
		//only admin
		if (!$this->permissions->checkPermissions('player_center_settings')) {
			$this->returnErrorStatus();
			redirect('cms_management/player_center_settings');
			return;
		}
		$cms_version = $this->input->post('cms_version');

		if ($cms_version) {
			if($this->utils->isEnabledMDB()){
				$this->load->model(['operatorglobalsettings']);
				//update mdb first
	        	$sourceDB=$this->utils->getActiveTargetDB();
				$rlt=$this->operatorglobalsettings->foreachMultipleDBWithoutSourceDB($sourceDB,
					function($db, &$result) use($cms_version){
					$result=$cms_version;
					return $this->operatorglobalsettings->syncSettingJson("cms_version", $cms_version, 'value', $db);
				});
				$this->utils->debug_log('update cms version on mdb', $rlt);
			}
			$this->operatorglobalsettings->syncSettingJson("cms_version", $cms_version , 'value');
			$this->alertMessage(self::MESSAGE_TYPE_SUCCESS, lang('CMS version successfully update'));
		} else {
			$this->alertMessage(self::MESSAGE_TYPE_ERROR, lang('player.mp14'));
		}

		redirect('cms_management/player_center_settings');
	}

	public function setPlayerCenterDefaultLanguage() {
		if (!$this->permissions->checkPermissions('player_center_settings')) {
			$this->returnErrorStatus();
			redirect('cms_management/player_center_settings');
			return;
		}

		$selectedLanguage = $this->input->post('rdbLanguage');
		$isForceToDefault = $this->input->post('chkForceToDefaultLanguage');
		$isRetainCurrentLanguage = $this->input->post('chkRetainCurrentLanguage');

		if (!isset($selectedLanguage) || empty($selectedLanguage)) {
			$selectedLanguage = '0';
		}

		$this->operatorglobalsettings->syncSettingJson("player_center_language", $selectedLanguage , 'value');
		$this->operatorglobalsettings->syncSettingJson("force_to_default_language", $isForceToDefault , 'value');
		$this->operatorglobalsettings->syncSettingJson("retain_player_current_language", $isRetainCurrentLanguage , 'value');

		if ($this->endTransWithSucc()) {
			$this->alertMessage(self::MESSAGE_TYPE_SUCCESS, lang('Save system settings successfully'));
		} else {
			$this->alertMessage(self::MESSAGE_TYPE_ERROR, lang('error.default.db.message'));
		}

		redirect('cms_management/player_center_settings');
	}

	public function upload_player_logo() {

	    $prefer_player_center_logo = $this->input->post('prefer_player_center_logo');
        $this->operatorglobalsettings->putSetting("prefer_player_center_logo", $prefer_player_center_logo , 'value');

	    switch($prefer_player_center_logo){
            case PLAYER_CENTER_LOGO_PREFER_DEFAULT:
                $this->operatorglobalsettings->syncSettingJson("player_center_logo", null, 'value');
                $this->alertMessage(self::MESSAGE_TYPE_SUCCESS, lang('Logo set to player default'));
                redirect('cms_management/player_center_settings');
                return;
                break;
            case PLAYER_CENTER_LOGO_PREFER_WWW:
            case PLAYER_CENTER_LOGO_PREFER_UPLOAD:
            default:
                $file_header_logo = isset($_FILES['fileToUpload']) ? $_FILES['fileToUpload'] : null;
                $path_logo_image =$this->utils->getLogoTemplatePath();
                $path_logo_image=rtrim($path_logo_image, '/');
                $file_name = 'playercenter_header_logo_'.strtotime('today').str_random(5);
                $this->load->model(array('operatorglobalsettings'));

                if(empty($file_header_logo['size'][0])){
                    $this->alertMessage(self::MESSAGE_TYPE_SUCCESS, lang('Player logo set successfully'));
                    redirect('cms_management/player_center_settings');

                    return;
                }else{
                    $config_logo_image = [
                        'allowed_types' => 'png|jpg|jpeg|gif|PNG',//array("jpg","jpeg","png","gif", "PNG"),
                        'max_size' => $this->utils->getMaxUploadSizeByte(),
                        'max_width' => $this->utils->getUploadMaxWidth(),
                        'max_height' => $this->utils->getUploadMaxHeight(),
                        'overwrite' => TRUE,
                        'remove_spaces' => TRUE,
                        'upload_path' => $path_logo_image,
                    ];

                    $file_to_upload[] = [
                        "file_details" => $file_header_logo,
                        "upload_path" => $path_logo_image,
                        "config_header" => $config_logo_image,
                        "file_name" => $file_name,
                    ];

                    foreach($file_to_upload as $key => $value){
                        $response = $this->multiple_image_uploader->do_multiple_uploads($value['file_details'], $value['upload_path'], $value['config_header'], $value['file_name']);
                        if($response['status'] == "fail"){
                            $this->alertMessage(self::MESSAGE_TYPE_ERROR, $response['message']);
                            redirect('cms_management/player_center_settings');

                            return;
                        }
                    }

                    $this->operatorglobalsettings->syncSettingJson("player_center_logo", $file_name, 'value');
                    //clear cache if upload
	                $cacheKey = $this->utils->getCacheKeyForPlayerCenterLogoURL();
	                $this->utils->deleteCache($cacheKey);

                    $this->alertMessage(self::MESSAGE_TYPE_SUCCESS, lang('Player logo set successfully'));
                    redirect('cms_management/player_center_settings');
                }
                break;
        }
    }

    public function upload_player_favicon() {

		if ($this->input->post('setDefaultPlayerFavicon')) {
			$this->operatorglobalsettings->syncSettingJson("player_center_favicon", null, 'value');
			$this->alertMessage(self::MESSAGE_TYPE_SUCCESS, "Favicon set to player default.");
			redirect('cms_management/player_center_settings');
			return;
		}

		$file_header_favicon = isset($_FILES['fileToUpload']) ? $_FILES['fileToUpload'] : null;
		$path_favicon_image =$this->utils->getFaviconTemplatePath();
		$path_favicon_image=rtrim($path_favicon_image, '/');
		$file_name = 'favicon_'.strtotime('today').str_random(5);
		$this->load->model(array('operatorglobalsettings'));

		if(empty($file_header_favicon['size'][0])) {
				$this->alertMessage(self::MESSAGE_TYPE_ERROR, lang('player.mp14'));
				redirect('cms_management/player_center_settings');
				return;
		} else {
			 	$config_logo_image = array(
		            'allowed_types' => 'ico',//array("jpg","jpeg","png","gif", "PNG"),
		            'max_size'      => $this->utils->getMaxUploadSizeByte(),
		            'overwrite'     => true,
		            'remove_spaces' => true,
		            'upload_path'   => $path_favicon_image,
		        );

		        $file_to_upload[] = array(
					"file_details" => $file_header_favicon,
					"upload_path" => $path_favicon_image,
					"config_header" => $config_logo_image,
					"file_name" => $file_name,
				);

		        foreach ($file_to_upload as $key => $value) {
		        	$response = $this->multiple_image_uploader->do_multiple_uploads($value['file_details'], $value['upload_path'], $value['config_header'], $value['file_name']);
			        if($response['status'] == "fail" ) {
			        	$this->alertMessage(self::MESSAGE_TYPE_ERROR, $response['message']);
			        	redirect('cms_management/player_center_settings');
						return;
			        }
		        }

		        $this->operatorglobalsettings->syncSettingJson("player_center_favicon", $file_name, 'value');
		        $this->alertMessage(self::MESSAGE_TYPE_SUCCESS, "Player favicon set successfully.");
		        redirect('cms_management/player_center_settings');
		}
	}

	public function save_player_center_title(){
		//only admin
		if (!$this->permissions->checkPermissions('player_center_settings')) {
			$this->returnErrorStatus();
			redirect('cms_management/player_center_settings');
			return;
		}
        $player_center_title = $this->input->post('player_center_title');
        $player_center_mobile_header_title_style = $this->input->post('player_center_mobile_header_title_style');

        $result = TRUE;
        $result = ($this->operatorglobalsettings->syncSettingJson("player_center_title", $player_center_title , 'value')) ? $result : FALSE;
        $result = ($this->operatorglobalsettings->putSetting("player_center_mobile_header_title_style", $player_center_mobile_header_title_style , 'value')) ? $result : FALSE;
        if ($result) {
			$this->alertMessage(self::MESSAGE_TYPE_SUCCESS, "Player Center title successfully update.");
		} else {
			$this->alertMessage(self::MESSAGE_TYPE_ERROR, lang('player.mp14'));
		}

		redirect('cms_management/player_center_settings');

	}

	public function setNewPlayeCenterTemplate(){
		//update player_center_template, validate dir
		$result=['success'=>true];
		$success = false;

		$dir=dirname(__FILE__).'/../../../player/application/views/'.$this->utils->getConfig('new_player_center_default_template');
		if(is_dir($dir)){
			$success = $this->utils->putOperatorSetting('player_center_template', $this->utils->getConfig('new_player_center_default_template'));
		} else {
			$message=lang('Wrong template of player center');
		}

		if(!$success){
			$this->alertMessage(self::MESSAGE_TYPE_ERROR, !empty($message) ? $message : lang('error.default.db.message'));
			redirect('cms_management/player_center_settings');
			$result=['success'=>false];
		}

		$this->returnJsonResult($result);
	}

	public function setupNewPlayerCenter($setup_type = "manual") {
		if (!$this->permissions->checkPermissions('player_center_settings')) {
			return $this->showErrorAccess($data['title'], $data['sidebar'], $data['player_center_settings']);
		}

		# Load models
		$this->load->model(array('system_feature'));

		$userId = $this->authentication->getUserId();
		$username = $this->authentication->getUsername();
		$render = true;

		# Prepare view data
		$data['setupType'] = $setup_type;
		$data['system_feature'] = $this->system_feature->get();
		$data['player_current_language'] = $this->utils->getPlayerCenterLanguage();
		$this->loadTemplate('CMS Management', '', '', 'cms');
		$this->template->add_js('resources/js/bootstrap-filestyle.min.js');
		$this->template->write_view('main_content', 'cms_management/setup_new_player_center',$data);
		$this->template->render();
	}

	function saveSystemFeatures(){

		$this->load->model(array('system_feature'));
		$feature = $this->input->post('enabled');

		$result=['success'=>true];

		if( ! empty( $feature ) ){

			$data = array();

			foreach ($feature as $idx => $feature) {
				$this->system_feature->updateFeatures($feature['id'], array(
						'enabled' => $feature['enabled']
					));
			}

		}

		$success = $this->endTransWithSucc();
		if ($success) {
			$this->alertMessage(self::MESSAGE_TYPE_SUCCESS, lang('Save system features successfully'));
		} else {
			$this->alertMessage(self::MESSAGE_TYPE_ERROR, lang('error.default.db.message'));
		}

		$this->returnJsonResult($result);
	}

	public function setNewPlayeCenterLanguage() {
		$this->load->model(array('operatorglobalsettings'));
		$selectedLanguage = $this->input->post('language');

		$result=['success'=>true];
		$success = false;

		if (!isset($selectedLanguage) || empty($selectedLanguage)) {
			$selectedLanguage = '0';
		}
		$langSetupValue = $this->operatorglobalsettings->getSettingJson('player_center_language');
        if(empty($langSetupValue)) {
            $langSetupValue = [];
        }

		$langSetupValue['language'] = $selectedLanguage;
		$langSetupValue['hide_lang'] = false;

		$success = $this->operatorglobalsettings->putSettingJson('player_center_language', $langSetupValue);

		if(!$success){
			$this->alertMessage(self::MESSAGE_TYPE_ERROR, !empty($message) ? $message : lang('error.default.db.message'));
			$result=['success'=>false];
		}

		$this->returnJsonResult($result);
	}

	public function save_withdrawal_verification_type(){
		//only admin
		if (!$this->permissions->checkPermissions('player_center_settings')) {
			$this->returnErrorStatus();
			redirect('cms_management/player_center_settings');
			return;
		}
		$withdraw_verification_type = $this->input->post('rdbWithdrawType');

		if ($withdraw_verification_type) {
			$this->utils->putOperatorSetting("withdraw_verification", $withdraw_verification_type);
			$this->alertMessage(self::MESSAGE_TYPE_SUCCESS, lang("Withdrawal verification successfully update."));
		} else {
			$this->alertMessage(self::MESSAGE_TYPE_ERROR, lang('player.mp14'));
		}

		redirect('cms_management/player_center_settings');
	}

	public function autoSetupCollectionAccount() {
		$this->load->model(array('payment_account', 'banktype', 'vipsetting', 'operatorglobalsettings'));

		# Check if bank is set
		$bankList = $this->banktype->getBankTypes();
		if (empty($bankList)) {
			return;
		}

		# Check if bank deposit is set
		$depositBank = $this->payment_account->getActveDepositPaymentAccount();
		if (!empty($depositBank)) {
			return;
		}

		$bank = $bankList[0];
		$data = $this->utils->getConfig('initial_collection_account_setup');
		$data['payment_type_id'] 	= $bank->bankTypeId;
		$data['created_by_userid'] 	= $this->authentication->getUserId();
		$data['created_at'] 		= $this->utils->getNowForMysql();
		$data['payment_order'] 		= $this->payment_account->getNextOrder();

		# set collection account
		$playerLevels = array_column($this->vipsetting->getAllvipsetting(), 'vipSettingId');
		$result = $this->payment_account->addPaymentAccount($data, $playerLevels);

		if (!empty($result)) {
			# Set default collection
			$paymentAccount = $this->payment_account->getAllPaymentAccountDetails()[0]->id;
			if (!empty($paymentAccount)) {
				$this->operatorglobalsettings->setSpecialPaymentList(array($paymentAccount));
			}
		}
	}

	public function save_custom_script(){
		//only admin
		if (!$this->permissions->checkPermissions('player_center_settings')) {
			$this->returnErrorStatus();
			redirect('cms_management/player_center_settings');
			return;
		}
		$customScript = $this->input->post('taCustomScript');

        $this->operatorglobalsettings->syncSettingJson("player_center_custom_script", $customScript , 'template');
        $this->alertMessage(self::MESSAGE_TYPE_SUCCESS, lang('Custom script successfully update'));

		redirect('cms_management/player_center_settings');
	}

	public function sms_activity_views($offset = 0) {

		if (!$this->permissions->checkPermissions('sms_manager')) {
			$this->error_access();
		} else {
			$this->loadTemplate(lang('cms.11'), '', '', 'sms');

			$config['base_url'] = "/cms_management/sms_activity_views/";

			$condition = [];
			$getStatus = $this->input->get('status');

			if ($getStatus) {
				$condition['sms_activity_msg.status'] = $getStatus - 1;
			}

			$config['total_rows'] = count($this->cms_model->getSMSActivityMsg($condition, null, null, null));
			$config['per_page'] = 10;
			$config['num_links'] = '5';

			$config['cur_tag_open'] = "<li><span><b>";
			$config['cur_tag_close'] = "</b></span></li>";
			$tag = ['first_tag', 'last_tag', 'next_tag', 'prev_tag', 'num_tag'];

			foreach ($tag as $val) {
				$config[$val . '_open'] = '<li>';
				$config[$val . '_close'] = '</li>';
			}

			//id, content, create_time, update_time, send_time, update_user_id, send_user_id, status
			$this->pagination->initialize($config);

			$data['condition'] = $condition;
			$data['data'] = $this->cms_model->getSMSActivityMsg($condition, $config['per_page'], $offset, 'id desc');

			$this->template->write_view('main_content', 'cms_management/sms/sms_activity_views', $data);
			$this->template->render();
		}
	}

	public function sms_activity_add() {

		$this->loadTemplate('CMS Management', '', '', 'cms');

		if ($_POST) {
			$this->form_validation->set_rules('content', 'Content', 'trim|xss_clean|required');

			if ($this->form_validation->run() == false) {
				redirect('cms_management/sms_activity_add');
			} else {

				$data = [
					'content' => $this->input->post('content'),
					'create_time' => date("Y-m-d H:i:s"),
					'update_time' => date("Y-m-d H:i:s"),
					'update_user_id'  =>  $this->authentication->getUserId(),
					'status'  => 0
				];

				$this->cms_model->addSMSActivityMsg($data);

				$this->saveAction('cms_management', 'Add Activity Msg', "User " . $this->authentication->getUsername() . " has successfully add activity msg");
				$this->alertMessage('1', lang('con.cms22'));

				redirect('cms_management/sms_activity_views');
			}
		}

		$this->template->write_view('main_content', 'cms_management/sms/sms_activity_add');
		$this->template->render();
	}

	public function sms_activity_edit($id = null) {

		$this->loadTemplate('CMS Management', '', '', 'cms');

		if ($_POST) {
			$this->form_validation->set_rules('content', 'Content', 'trim|xss_clean|required');
			if ($this->form_validation->run() == false) {
				redirect("cms_management/sms_activity_edit/$id");
			} else {

				$condition = [
					'content' => $this->input->post('content'),
					'update_time' => date("Y-m-d H:i:s"),
					'update_user_id'  =>  $this->authentication->getUserId(),
				];

				$this->cms_model->editSMSActivityMsg($id, $condition);

				$this->saveAction('cms_management', 'Edit Activity Msg', "User " . $this->authentication->getUsername() . " has successfully edit activity msg");
				$this->alertMessage('1', lang('con.cms23'));

				redirect('cms_management/sms_activity_views');
			}
		}

		$data = $this->cms_model->getSMSActivityMsg(['id' => $id]);
		if (empty($data[0])) {
			redirect('cms_management/sms_activity_views');
		}

		$this->template->write_view('main_content', 'cms_management/sms/sms_activity_edit', $data[0]);
		$this->template->render();
	}

	public function sms_activity_send($id) {

		$this->loadTemplate('CMS Management', '', '', 'cms');

		if ($_POST) {

			$this->load->model(['player_model', 'queue_result']);
			$this->load->library(["lib_queue", "authentication", 'sms/sms_sender']);

			# get all player contact number
			$mobileNumberList = $this->player_model->getAllVerifiedPhonePlayer();

			# get sms content
			$id = $this->input->post('id');
			$data = $this->cms_model->getSMSActivityMsg(['sms_activity_msg.id' => $id]);
			$smsContent = $data[0]['content'];
			$callerType = Queue_result::CALLER_TYPE_ADMIN;
			$caller = $this->authentication->getUserId();
			$state = null;
			$isEnabledSendSmsUseQueueServer = $this->utils->isEnabledFeature('enabled_send_sms_use_queue_server');

			if ($isEnabledSendSmsUseQueueServer) {
				$this->lib_queue->addRemoteSMSJob($mobileNumberList, $smsContent, $callerType, $caller, $state, null ,$isGroup = true);
			} else {
				$this->alertMessage('2', lang('cms.mustBeEnabledSMSQueueServer'));
				redirect('cms_management/sms_activity_views');
			}

			# Update activity msg
			$condition = [
				'status' => 1,
				'send_user_id' => $caller,
				'send_time' => date("Y-m-d H:i:s")
			];
			$this->cms_model->editSMSActivityMsg($id, ['status' => 1]);

			$this->saveAction('cms_management', 'Send Activity Msg', "User " . $this->authentication->getUsername() . " has successfully send activity msg");
			$this->alertMessage('1', lang('con.cms25'));

			redirect('cms_management/sms_activity_views');
		}

		$data = $this->cms_model->getSMSActivityMsg(['id' => $id]);
		if (empty($data[0]) || $data[0]['status'] == 1) {
			redirect('cms_management/sms_activity_views');
		}

		$this->template->write_view('main_content', 'cms_management/sms/sms_activity_send', $data[0]);
		$this->template->render();
	}

	public function sms_activity_delete($id = null) {
		$data = $this->cms_model->getSMSActivityMsg(['id' => $id]);
		if (empty($data[0])) {
			redirect('cms_management/sms_activity_edit');
		}

		$this->cms_model->deleteSMSActivityMsg($id);

		$this->saveAction('cms_management', 'Delete Activity Msg', "User " . $this->authentication->getUsername() . " has successfully delete activity msg");
		$this->alertMessage('1', lang('con.cms24'));

		redirect(BASEURL . 'cms_management/sms_activity_edit', 'refresh');
	}

	public function sms_manager_views($offset = 0) {

		if (!$this->permissions->checkPermissions('sms_manager')) {
			$this->error_access();
		} else {
			$this->loadTemplate(lang('cms.10'), '', '', 'cms');

			$config['base_url'] = "/cms_management/sms_manager_views/";

			$condition = [];
			$category = $this->input->get('category');

			if ($category) {
				$condition['sms_manager_msg.category'] = $category;
			}

			$config['total_rows'] = count($this->cms_model->getSMSManagerMsg($condition, null, null, null));
			$config['per_page'] = 4;
			$config['num_links'] = '10';

			$config['cur_tag_open'] = "<li><span><b>";
			$config['cur_tag_close'] = "</b></span></li>";
			$tag = ['first_tag', 'last_tag', 'next_tag', 'prev_tag', 'num_tag'];

			foreach ($tag as $val) {
				$config[$val . '_open'] = '<li>';
				$config[$val . '_close'] = '</li>';
			}

			$this->pagination->initialize($config);

			$data['condition'] = $condition;
			$data['data'] = $this->cms_model->getSMSManagerMsg($condition, $config['per_page'], $offset, "id desc");
			$data['currentData'] = $this->cms_model->getSMSManagerMsg(["sms_manager_msg.status" => 1], null, null, null);
			$data['categoryTpye'] = $this->config->item('smsManagerTypeList');

			$this->template->write_view('main_content', 'cms_management/sms/sms_manager_views', $data);
			$this->template->render();
		}
	}

	public function sms_manager_add() {

		$this->loadTemplate('CMS Management', '', '', 'cms');

		if ($_POST) {
			$this->form_validation->set_rules('content', 'Content', 'trim|xss_clean|required');
			$this->form_validation->set_rules('category', 'Category', 'trim|xss_clean|required');
			$this->form_validation->set_rules('status', 'Status', 'trim|xss_clean|required');

			if ($this->form_validation->run() == false) {
				redirect('cms_management/sms_manager_add');
			} else {

				if ($this->input->post('status') == 1) {
					$this->cms_model->editSMSManagerMsg([
						'category' => $this->input->post('category')
					], [
						'status' => 0,
					]);
				}

				$data = [
					'content' => $this->input->post('content'),
					'userId'  =>  $this->authentication->getUserId(),
					'create_time' => date("Y-m-d H:i:s"),
					'update_time' => date("Y-m-d H:i:s"),
					'status'  => $this->input->post('status'),
					'category' => $this->input->post('category')
				];

				$this->cms_model->addSMSManagerMsg($data);

				$this->saveAction('cms_management', 'Add Manager Msg', "User " . $this->authentication->getUsername() . " has successfully add manager msg");
				$this->alertMessage('1', lang('con.cms19'));

				redirect('cms_management/sms_manager_views');
			}
		}
		$data['categoryTpye'] = $this->config->item('smsManagerTypeList');

		$this->template->write_view('main_content', 'cms_management/sms/sms_manager_add', $data);
		$this->template->render();
	}

	public function sms_manager_edit($id = null) {

		$this->loadTemplate('CMS Management', '', '', 'cms');

		if ($_POST) {
			$this->form_validation->set_rules('content', 'Content', 'trim|xss_clean|required');
			$this->form_validation->set_rules('category', 'Category', 'trim|xss_clean|required');
			$this->form_validation->set_rules('status', 'Status', 'trim|xss_clean|required');
			if ($this->form_validation->run() == false) {
				redirect("cms_management/sms_manager_edit/$id");
			} else {

				if ($this->input->post('status') == 1) {
					$this->cms_model->editSMSManagerMsg([
						'category' => $this->input->post('category')
					], [
						'status' => 0,
					]);
				}

				$condition = [
					'content' => $this->input->post('content'),
					'userId'  =>  $this->authentication->getUserId(),
					'update_time' => date("Y-m-d H:i:s"),
					'category' => $this->input->post('category'),
					'status'  => $this->input->post('status')
				];

				$this->cms_model->editSMSManagerMsg(['id' => $id], $condition);

				$this->saveAction('cms_management', 'Edit Manager Msg', "User " . $this->authentication->getUsername() . " has successfully edit manager msg");
				$this->alertMessage('1', lang('con.cms20'));

				redirect('cms_management/sms_manager_views');
			}
		}

		$data = $this->cms_model->getSMSManagerMsg(['id' => $id], null, null, null);
		if (empty($data[0])) {
			redirect('cms_management/sms_manager_views');
		}

		$data = $data[0];
		$data['categoryTpye'] = $this->config->item('smsManagerTypeList');
		$this->template->write_view('main_content', 'cms_management/sms/sms_manager_edit', $data);
		$this->template->render();
	}

	public function sms_manager_delete($id = null) {
		$data = $this->cms_model->getSMSManagerMsg(['id' => $id],null,null,null);
		if (empty($data[0])) {
			redirect('cms_management/sms_manager_views');
		}

		$this->cms_model->deleteSMSManagerMsg($id);

		$this->saveAction('cms_management', 'Delete Manager Msg', "User " . $this->authentication->getUsername() . " has successfully delete manager msg");
		$this->alertMessage('1', lang('con.cms21'));

		redirect(BASEURL . 'cms_management/sms_manager_views', 'refresh');
	}

	/**
	 * view metadata page
	 *
	 * @return  void
	 */
	public function viewMetaData($offset = 0) {
		if (!$this->permissions->checkPermissions('metadata_manager')) {
			$this->error_access();
		} else {
			$this->loadTemplate(lang('cms.12'), '', '', 'cms');

			$config['base_url'] = "/cms_management/view_metadata/";

			$condition = [];
			if ($uri_string = $this->input->get('uri_string')) {
				$condition['uri_string'] = $uri_string;
			}

			$config['total_rows'] = count($this->cms_model->getAllMetaData(null, null, null, $condition));
			$config['per_page'] = 10;
			$config['num_links'] = '5';

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

			$data['condition'] = $condition;
			$data['uri_string'] = $this->input->get('uri_string');
			$data['list'] = $this->cms_model->getAllMetaData($config['per_page'], $offset, null, $condition);

			$this->template->write_view('main_content', 'cms_management/metadata/view_metadata', $data);
			$this->template->render();
		}
	}

	public function addMetaData() {
		$this->loadTemplate('CMS Management', '', '', 'cms');

		if ($_POST) {
			$this->input->post('uri_string', true);
			$this->form_validation->set_rules('uri_string', 'Path', 'trim|xss_clean|required');

			if ($this->form_validation->run() == false) {
				$message = lang('con.cms03');
				$this->alertMessage('2', $message);
				redirect(BASEURL . 'cms_management/viewMetaData', 'refresh');
			} else {
				$uri_string = $this->input->post('uri_string');
				$title      = $this->input->post('title');
				$keyword      = $this->input->post('keyword');
				$description  = $this->input->post('description');

				$data = array(
					'title' => $title,
					'uri_string' => $uri_string,
					'keyword'    => $keyword,
					'description' => $description,
					'updated_by'  => $this->authentication->getUserId(),
					'updated_at'  => date("Y-m-d H:i:s")
				);

				$this->cms_model->addMetaData($data);

				$this->alertMessage('1', lang('con.cms19') . ': ' . $title);
				redirect(BASEURL . 'cms_management/viewMetaData', 'refresh');
			}
		}
		$this->template->write_view('main_content', 'cms_management/metadata/add_metadata');
		$this->template->render();
	}

	public function editMetaData($id) {
		$this->loadTemplate('CMS Management', '', '', 'cms');

		$data['id'] = $id;
		$data['row'] = $this->cms_model->getMetaDataById($id);

		if ($_POST) {
			$this->input->post('uri_string', true);
			$this->form_validation->set_rules('uri_string', 'Path', 'trim|xss_clean|required');

			if ($this->form_validation->run() == false) {
				$message = lang('con.cms03');
				$this->alertMessage('2', $message);
				redirect(BASEURL . 'cms_management/viewMetaData', 'refresh');
			} else {
				$uri_string = $this->input->post('uri_string');
				$title      = $this->input->post('title');
				$keyword      = $this->input->post('keyword');
				$description  = $this->input->post('description');

				$data = array(
					'title' => $title,
					'uri_string' => $uri_string,
					'keyword'    => $keyword,
					'description' => $description,
					'updated_by'  => $this->authentication->getUserId(),
					'updated_at'  => date("Y-m-d H:i:s")
				);

				$this->cms_model->editMetaData(['id' => $id], $data);

				$this->alertMessage('1', lang('con.cms20') . ': ' . $title);
				redirect(BASEURL . 'cms_management/viewMetaData', 'refresh');
			}
		}
		$this->template->write_view('main_content', 'cms_management/metadata/edit_metadata', $data);
		$this->template->render();
	}

	public function deleteMetaData($id, $title) {
		$this->cms_model->deleteMetaData($id);
		$this->alertMessage('1', lang('con.cms21') . ': ' . base64_decode($title));
		redirect(BASEURL . 'cms_management/viewMetaData', 'refresh');
	}

    public function viewNavigationGameType() {
        if (!$this->permissions->checkPermissions('navigation_manager')) {
            $this->error_access();
        } else {
            $this->loadTemplate(lang('cms.13'), '', '', 'cms');


            $this->load->model([ 'game_type_model','game_description_model', 'cms_navigation_settings']);

            $this->CI->load->library(['game_list_lib']);
            $lang = $this->utils->getCurrentLanguageCode();
            // $status = BaseModel::DB_TRUE;
            $status = null;
            $game_types = $this->cms_navigation_settings->getGameTypes($status);

            foreach($game_types as $key => $game_type) {
                $game_types[$key]['game_type_lang'] = $this->utils->extractLangJson($game_type['game_type_lang']);
            }

            $data = [
                'game_types' => $game_types,
                // 'navigation_game_platforms' => $navigation_game_platforms,
                // 'navigation_game_types' => $navigation_game_types,
            ];

            $this->template->write_view('main_content', 'cms_management/navigation/view_navigation', $data);
            $this->template->render();
        }
    }

    public function viewModifyGameType($game_type_id) {

        $this->load->model(['cms_navigation_settings']);
        $game_type = $this->cms_navigation_settings->findById($game_type_id);
        $game_type['game_type_lang'] = $this->utils->extractLangJson($game_type['game_type_lang']);
        $data = [
            'game_type' => $game_type
        ];
        $this->load->view('cms_management/navigation/modify_game_type', $data);
    }

    public function postModifyGameType() {

        $this->form_validation->set_rules('english_name', 'English Name', 'trim|xss_clean|required');
        $this->form_validation->set_rules('chinese_name', 'Chinese Name', 'trim|xss_clean|required');
        $this->form_validation->set_rules('indonesian_name', 'Indonesian Name', 'trim|xss_clean|required');
        $this->form_validation->set_rules('vietnamese_name', 'Vietnamese Name', 'trim|xss_clean|required');
        $this->form_validation->set_rules('korean_name', 'Korean Name', 'trim|xss_clean|required');
        $this->form_validation->set_rules('thailand_name', 'Thailand Name', 'trim|xss_clean|required');
        $this->form_validation->set_rules('order', 'Order', 'trim|xss_clean|required|numeric');
        $this->form_validation->set_rules('status', 'Status', 'trim|xss_clean|required|numeric');

        if ($this->form_validation->run() == false) {
            $message = lang('cms.navigation.form.invalid');
            $this->alertMessage('2', $message);
            redirect(BASEURL . 'cms_management/viewNavigationGameType', 'refresh');
        }

        $this->load->model(['cms_navigation_settings']);
        $lang = [
            '1' => $this->input->post('english_name'),
            '2' => $this->input->post('chinese_name'),
            '3' => $this->input->post('indonesian_name'),
            '4' => $this->input->post('vietnamese_name'),
            '5' => $this->input->post('korean_name'),
            '6' => $this->input->post('thailand_name'),
        ];

        $data = [
            'game_type_lang' => '_json:' . json_encode($lang),
            'order' => $this->input->post('order'),
            'status' => $this->input->post('status'),
        ];

        $hasIcon = !empty($_FILES['icon']['name']) ? true : false;
        if($hasIcon) {
            $this->load->helper('string');
            $path = $this->utils->getUploadPath();
            $this->utils->addSuffixOnMDB($path);
            $path .= '/cms_game_types';

			#if file extension is webp, retain the extension
			$file_extension = pathinfo($_FILES['icon']['name'], PATHINFO_EXTENSION);
			$file_name = random_string('alnum', 20);
			if($file_extension == 'webp'){
				$file_name = $file_name .'.webp';
			}else{
				$file_name = $file_name .'.png';
			}

            $result = $this->upload($file_name, $path, 'icon', ['max_size' => '2048']);
            if($result !== true) {
                // $message = lang('cms.navigation.form.invalid');
                $message = $result;
                $this->alertMessage('2', $message);
                redirect(BASEURL . 'cms_management/viewNavigationGameType', 'refresh');
            }
            $data['icon'] = $file_name;
        }

        $game_type_id = $this->input->post('id');
        if(!empty($game_type_id)) {
            if($hasIcon) {
                $this->cms_navigation_settings->deleteOldIconById($game_type_id);
            }
            $success = $this->cms_navigation_settings->updateById($game_type_id, $data);
        }
        else {
            $success = $this->cms_navigation_settings->insert($data);
        }

        if($success) {
            $this->alertMessage('1', lang('cms.navigation.success'));
            redirect(BASEURL . 'cms_management/viewNavigationGameType', 'refresh');
        }
    }

    public function viewModifyGamePlatform($game_platform_id) {

        $this->load->model(['cms_navigation_game_platform']);
        $game_platform = $this->cms_navigation_game_platform->findById($game_platform_id);
        $game_platform['game_platform_lang'] = $this->utils->extractLangJson($game_platform['game_platform_lang']);
        $data = [
            'game_platform' => $game_platform
        ];
        $this->load->view('cms_management/navigation/modify_game_platform', $data);
    }

    public function viewNavigationGamePlatform($game_type_id) {
        if (!$this->permissions->checkPermissions('navigation_manager')) {
            $this->error_access();
        } else {
            $this->loadTemplate(lang('cms.13'), '', '', 'cms');

            $this->load->model(['cms_navigation_game_platform', 'cms_navigation_settings']);
            $game_platforms = $this->cms_navigation_game_platform->findGamePlatformsByNavigationSettingId($game_type_id);

            $this->load->library(['language_function']);

            $currentLang = $this->language_function->getCurrentLanguage();
            $currentLang = array_key_exists($currentLang, Language_function::ISO2_LANG) ? Language_function::ISO2_LANG[$currentLang] : 'en';

            $game_type = $this->cms_navigation_settings->findById($game_type_id);
            $game_type_lang = $this->utils->extractLangJson($game_type['game_type_lang'])[$currentLang];

            foreach($game_platforms as $key => $game_platform) {
                $game_platforms[$key]['game_platform_lang'] = $this->utils->extractLangJson($game_platform['game_platform_lang']);
            }
            $data = [
                'game_platforms' => $game_platforms,
                'game_type_id' => $game_type_id,
                'game_type_lang' => $game_type_lang
            ];

            $this->template->write_view('main_content', 'cms_management/navigation/view_game_platform', $data);
            $this->template->render();
        }
    }

    public function refreshNavigationGameType() {
        $this->load->model(['cms_navigation_settings', 'game_tags']);

        $data = [];
        $game_tags = $this->game_tags->getAllGameTags();
        foreach($game_tags as $game_tag) {
            $data[] = [
                'game_type_lang' => $game_tag['tag_name'],
                'game_type_code' => $game_tag['tag_code'],
                'order' => 0,
                'status' => 0,
                'icon' => null,
            ];
        }
        $success = $this->cms_navigation_settings->insertMissingGameType($data);
        if($success) {
            $this->alertMessage('1', lang('cms.navigation.success'));
            redirect(BASEURL . 'cms_management/viewNavigationGameType', 'refresh');
        }
        else {
            $this->alertMessage('2', lang('cms.navigation.failed'));
            redirect(BASEURL . 'cms_management/viewNavigationGameType', 'refresh');
        }
    }

    public function refreshNavigationGamePlatform($game_type_id) {
        $this->load->model(['cms_navigation_settings', 'game_type_model', 'cms_navigation_game_platform']);
        $navigation = $this->cms_navigation_settings->findById($game_type_id);
        if($navigation) {
            $game_platforms = $this->game_type_model->queryGameTypeAndTagCategory($navigation['game_type_code']);

            $data = [];
            foreach($game_platforms as $game_platform) {
                $lang = [
                    '1' => $game_platform['system_code'],
                    '2' => $game_platform['system_code'],
                    '3' => $game_platform['system_code'],
                    '4' => $game_platform['system_code'],
                    '5' => $game_platform['system_code'],
                    '6' => $game_platform['system_code'],
                ];
                $data[] = [
                    'game_platform_lang' => '_json:' . json_encode($lang),
                    'navigation_setting_id' => $game_type_id,
                    'game_platform_id' => $game_platform['game_platform_id'],
                    'order' => 0,
                    'status' => 0,
                ];
            }
            $success = $this->cms_navigation_game_platform->insertMissingGamePlatform($data);
            if($success) {
                $this->alertMessage('1', lang('cms.navigation.success'));
                redirect(BASEURL . 'cms_management/viewNavigationGamePlatform/' . $game_type_id, 'refresh');
            }
            else {
                $this->alertMessage('2', lang('cms.navigation.failed'));
                redirect(BASEURL . 'cms_management/viewNavigationGamePlatform/' . $game_type_id, 'refresh');
            }
        }
        else {
            $this->alertMessage('2', lang('cms.navigation.failed'));
            redirect(BASEURL . 'cms_management/viewNavigationGameType', 'refresh');
        }
    }

    public function postModifyGamePlatform() {

        $navigation_setting_id = $this->input->post('navigation_setting_id');
        $this->form_validation->set_rules('english_name', 'English Name', 'trim|xss_clean|required');
        $this->form_validation->set_rules('chinese_name', 'Chinese Name', 'trim|xss_clean|required');
        $this->form_validation->set_rules('indonesian_name', 'Indonesian Name', 'trim|xss_clean|required');
        $this->form_validation->set_rules('vietnamese_name', 'Vietnamese Name', 'trim|xss_clean|required');
        $this->form_validation->set_rules('korean_name', 'Korean Name', 'trim|xss_clean|required');
        $this->form_validation->set_rules('thailand_name', 'Thailand Name', 'trim|xss_clean|required');
        $this->form_validation->set_rules('order', 'Order', 'trim|xss_clean|required|numeric');
        $this->form_validation->set_rules('status', 'Status', 'trim|xss_clean|required|numeric');

        if ($this->form_validation->run() == false) {
            $message = lang('cms.navigation.form.invalid');
            $this->alertMessage('2', $message);
            redirect(BASEURL . 'cms_management/viewNavigationGamePlatform/' . $navigation_setting_id, 'refresh');
        }

        $this->load->model(['cms_navigation_game_platform']);
        $lang = [
            '1' => $this->input->post('english_name'),
            '2' => $this->input->post('chinese_name'),
            '3' => $this->input->post('indonesian_name'),
            '4' => $this->input->post('vietnamese_name'),
            '5' => $this->input->post('korean_name'),
            '6' => $this->input->post('thailand_name'),
        ];

        $data = [
            'game_platform_lang' => '_json:' . json_encode($lang),
            'order' => $this->input->post('order'),
            'status' => $this->input->post('status'),
        ];

        $hasIcon = !empty($_FILES['icon']['name']) ? true : false;
        if($hasIcon) {
            $this->load->helper('string');
            $path = $this->utils->getUploadPath();
            $this->utils->addSuffixOnMDB($path);
            $path .= '/cms_game_platforms';

			#if file extension is webp, retain the extension
			$file_extension = pathinfo($_FILES['icon']['name'], PATHINFO_EXTENSION);
			$file_name = random_string('alnum', 20);
			if ($file_extension == 'webp') {
				$file_name = $file_name . '.webp';
			} else {
				$file_name = $file_name . '.png';
			}

           $result = $this->upload($file_name, $path, 'icon', ['max_size' => '2048']);
            if($result !== true) {
                // $message = lang('cms.navigation.form.invalid');
                $message = $result;
                $this->alertMessage('2', $message);
                redirect(BASEURL . 'cms_management/viewNavigationGamePlatform/' . $navigation_setting_id, 'refresh');
            }
            $data['icon'] = $file_name;
        }

        $game_platform_id = $this->input->post('id');
        if(!empty($game_platform_id)) {
            if($hasIcon) {
                $this->cms_navigation_game_platform->deleteOldIconById($game_platform_id);
            }
            $success = $this->cms_navigation_game_platform->updateById($game_platform_id, $data);
        }
        if($success) {
            $this->alertMessage('1', lang('cms.navigation.success'));
            redirect(BASEURL . 'cms_management/viewNavigationGamePlatform/' . $navigation_setting_id, 'refresh');
        }
    }

    /**
	 * Casino navigation
	 *
	 * @return  void
	 */
	public function viewCasinoNavigation() {
		if(!$this->permissions->checkPermissions('website_management')){
		    return $this->error_access();
        }
        $this->load->model([ 'game_type_model','game_description_model', 'cms_navigation_settings', 'game_tags']);

        $data['languages'] = $this->CI->language_function->getAllSystemLanguages();
		$this->history->setHistory('header_system.system_word96', 'game_type/viewGameType');
		$data['gameTypes'] = json_decode(json_encode($this->game_type_model->getGameTypesForDisplay()), true);
		$data['game_platforms'] = $this->external_system->getAllActiveSytemGameApi(null, false, "system_code");
		$game_tags = $this->game_tags->queryGameTagsForNavigation("", "asc");
		if(!empty($game_tags)){
			foreach ($game_tags as $key => $game_tag) {
				$translation_array = $this->utils->text_from_json($game_tag['title']);
				$game_tags[$key]['title'] = $translation_array[1];
			}
		}
		$data['game_tags'] = $game_tags;

		$data['landing_page'] = $this->utils->getOperatorSetting('casino_navigation_landing_page');
		$data['favorite_enabled'] = $this->utils->getOperatorSetting('casino_navigation_favorite_games_enabled');
		$data['recent_enabled'] = $this->utils->getOperatorSetting('casino_navigation_recent_games_enabled');
		$this->template->add_css('resources/css/game_type/game_type.css');
		$this->loadTemplate(lang('Casino navigation'), '', '', 'cms');
		$this->template->write_view('main_content', 'cms_management/website_management/view_casino_navigation', $data);
		$this->template->render();
	}

	public function update_casino_navigation($navigation, $value= ""){
		if (!$this->permissions->checkPermissions('website_management')) {
			$this->returnJsonResult(array("success" => false));
		}
		$success=$this->utils->putOperatorSetting($navigation, $value);
		$this->returnJsonResult(array("success" => $success));
	}

	public function update_game_tag_show_insite(){
		if (!$this->permissions->checkPermissions('website_management')) {
			$this->returnJsonResult(array("success" => false));
		}
		$this->load->model(array('game_tags'));
		$id = $this->input->post('id');
		$flag = $this->input->post('flag');
		$totalUpdate = $this->game_tags->updateGameTags('id', $id, ['flag_show_in_site' => $flag == "true" ? 1 : 0, 'game_tag_order' => 0]);
		$success = false;
		if($totalUpdate > 0){
			$success = true;
		}
		$this->returnJsonResult(array("success" => $success));
	}

	public function update_game_tag_sorting(){
		if (!$this->permissions->checkPermissions('website_management')) {
			$this->returnJsonResult(array("success" => false));
		}
		$tagsOrder = $this->input->post('tagsOrder');
		$success = false;
		if(!empty($tagsOrder)){
			$this->load->model(array('game_tags'));
			$this->game_tags->resetGameTagOrder();
			foreach ($tagsOrder as $key => $tagOrder) {
				$tagCode = $tagOrder[0];
				$order = $tagOrder[1];
				$this->game_tags->updateGameTags('tag_code', $tagCode, ['game_tag_order' => intval($order)]);
			}
			$success = true;
		}
		$this->returnJsonResult(array("success" => $success));
	}
}

/* End of file cms_management.php */
/* Location: ./application/controllers/cms_management.php */
