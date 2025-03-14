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
class Tournament_management extends BaseController {

	const ACTION_MANAGEMENT_TITLE = 'Tournament Management';

	function __construct() {
		parent::__construct();

		$this->load->helper(array('date_helper', 'url'));
		$this->load->library(array('form_validation', 'template', 'pagination', 'permissions', 'report_functions', 'payment_manager', 'tournament_lib'));
		// $this->load->model(array('group_level'));
		$this->permissions->checkSettings();
		$this->permissions->setPermissions();
	}

	/**
	 * Loads template for view based on regions in
	 * config > template.php
	 *
	 */
	private function loadTemplate($title, $description, $keywords, $activenav) {
		$this->template->add_js('resources/js/jquery.numeric.min.js');
		$this->template->add_css('resources/css/general/style.css');
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
		redirect('home');
	}
/**
	 *
	 * pay all tournament event bonus
	 *
	 * @return redirect back cashback report
	 */
	public function pay_tournament_event_bonus($event_id) {
		if (!$this->permissions->checkPermissions('manually_pay_cashback')) {
			return $this->error_access();
		}

		$this->load->model(['group_level']);
		$this->load->library(['lib_queue', 'language_function', 'authentication']);
		$callerType=Queue_result::CALLER_TYPE_ADMIN;
		$caller=$this->authentication->getUserId();
		$state=null;
		$lang=$this->language_function->getCurrentLanguage();

		//run queue
		$token=$this->lib_queue->addRemotePayTournment($event_id, $callerType, $caller, $state, $lang);

	    if (!empty($token)) {
	        $this->alertMessage(self::MESSAGE_TYPE_SUCCESS, lang('Create pay tournament event job successfully'));
			return redirect('/system_management/common_queue/'.$token);
	    } else {
	        $this->alertMessage(self::MESSAGE_TYPE_ERROR, lang('Create pay tournament event job failed'));
			return redirect();
	    }
	}

}

/* End of file player_management.php */
/* Location: ./application/controllers/player_management.php */
