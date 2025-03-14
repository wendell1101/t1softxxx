<?php if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

require_once dirname(__FILE__) . '/BaseController.php';

/**
 * Class Country_management
 */
class Super_Report_Management extends BaseController {

    function __construct() {
        parent::__construct();
        $this->load->helper('url');
        $this->load->library(array('permissions', 'template', 'form_validation', 'report_functions', 'salt', 'ip_manager','data_tables','super_report_lib'));

        $this->currency_list_super_report = $this->utils->getConfig('currency_list_super_report');

        $this->load->model(['super_report', 'group_level', 'promorules', 'group_level', 'player']);

        $this->permissions->checkSettings();
        $this->permissions->setPermissions();

    }

    private function loadTemplate($title, $description, $keywords, $activenav) {
        $this->loadThirdPartyToTemplate('datatables');
        $this->template->add_js('resources/js/report_management/report_management.js');
        $this->template->add_js('resources/js/select2.full.js');
        $this->template->add_css('resources/css/select2.min.css');
        $this->template->add_css('resources/css/general/style.css');

        $this->template->write('title', $title);
        $this->template->write('description', $description);
        $this->template->write('keywords', $keywords);
        $this->template->write('activenav', $activenav);
        $this->template->write('username', $this->authentication->getUsername());
        $this->template->write('userId', $this->authentication->getUserId());
        $this->template->write_view('sidebar', 'super_report_management/sidebar');
    }

    private function error_access() {
        $this->loadTemplate('Super Report', '', '', 'system');

        $message = lang('con.i01');
        $this->alertMessage(2, $message);

        $this->template->render();
    }

    public function index() {

        if (!$this->permissions->checkPermissions('super_report')) {
            return $this->error_access();
        }

        if(!$this->utils->isEnabledFeature('use_new_super_report')){
            return redirect('super_report_management/summary_report');
        }

        // $data['currency_list_super_report'] = $this->currency_list_super_report;
        //export report permission checking
        // $data['export_report_permission'] = $this->permissions->checkPermissions('export_super_summary_report');

        $this->loadNewTemplate('Super Report', '', '', 'super_report');
        $data=['donotLoadExtraCSS'=>true, 'donotLoadExtraJS'=>true];
        $this->template->write_view('main_content', 'super_report_management/home', $data);
        $this->template->render();

        // redirect('super_report_management/summary2_report');
    }

    public function summary_report() {

        if (!$this->permissions->checkPermissions('super_summary_report')) {
            $this->error_access();
        }else{
            $data['currency_list_super_report'] = $this->currency_list_super_report;
            $data['month_only'] = false;

            //export report permission checking
            $data['export_report_permission'] = $this->permissions->checkPermissions('export_super_summary_report');
            $data['currency_list'] = $this->super_report_lib->getAvailableCurrencies();
            $data['master_currency'] = $this->super_report_lib->getMasterCurrencyCode();            
            $data['conditions'] = $this->safeLoadParams(array( 
                'date_from' => $this->utils->getTodayForMysql().' '.Utils::FIRST_TIME,
                'date_to' => $this->utils->getTodayForMysql().' '.Utils::LAST_TIME,
                'month_only' => '',
                'chosen_currencies'=>implode(",", $data['currency_list'])
            ));
            $this->loadTemplate('Super Summary Report', '', '', 'super_report');
            $this->template->write_view('main_content', 'super_report_management/summary_report', $data);
            $this->template->render();
        }
    }

    public function player_report() {

        if (!$this->permissions->checkPermissions('super_player_report')) {
            $this->error_access();
        }
        $data['currency_list_super_report'] = $this->currency_list_super_report;
        //export report permission checking
        $data['export_report_permission'] = $this->permissions->checkPermissions('export_super_player_report');

        $this->loadTemplate('Super Player Report', '', '', 'super_report');
        $this->template->write_view('main_content', 'super_report_management/player_report', $data);
        $this->template->render();
    }

    public function games_report() {

        if (!$this->permissions->checkPermissions('super_game_report')) {
            $this->error_access();
        }
        $data['export_report_permission'] = $this->permissions->checkPermissions('export_super_game_report');

        $currency_keys =  array_keys($this->utils->getConfig('multiple_databases'));

        $currency_list = [];
        foreach ($currency_keys as $value) {
            if($value != 'super'){
                array_push($currency_list, $value);
            } else {
                array_push($currency_list, 'all');
            }
        }

        $data['currency_list'] = $currency_list;   
        $data['conditions'] = $this->safeLoadParams(array( 
            'date_from' => $this->utils->getTodayForMysql().' '.Utils::FIRST_TIME,
            'date_to' => $this->utils->getTodayForMysql().' '.Utils::LAST_TIME,
            'username' => '',
            'chosen_currencies'=>implode(",", $currency_list),
            'total_bet_from' => '',
            'total_bet_to' => '',
            'total_loss_from' => '',
            'total_loss_to' => '',
            'total_gain_from' => '',
            'total_gain_to' => '',
            'group_by' => '',

        ));

        $this->loadTemplate('Super Games Report', '', '', 'super_report');
        $this->template->write_view('main_content', 'super_report_management/game_report',$data);
        $this->template->render();
    }

    public function payment_report() {

        if (!$this->permissions->checkPermissions('super_payment_report')) {
            $this->error_access();
        }
        $data['currency_list_super_report'] = $this->currency_list_super_report;
        //export report permission checking
        $data['export_report_permission'] = $this->permissions->checkPermissions('export_super_payment_report');
        $data['currency_list'] = $this->super_report_lib->getAvailableCurrencies();
        $data['master_currency'] = $this->super_report_lib->getMasterCurrencyCode();
        $data['conditions'] = $this->safeLoadParams(array( 
            'date_from' => $this->utils->getTodayForMysql().' '.Utils::FIRST_TIME,
            'date_to' => $this->utils->getTodayForMysql().' '.Utils::LAST_TIME,
            'username' => '',
            'chosen_currencies'=>implode(",", $data['currency_list']),
            'group_by' => '',
            'amount_greater_than' => '',
            'amount_less_than' => '',
        ));
        $data['group_by_list'] = array(
			'by_payment_type'	  => lang('pay_report.by_coll_account'),
			'by_player'	          => lang('pay_report.by_player'),
			'by_level'	          => lang('pay_report.by_player_level'),
		);

		switch ($data['conditions']['group_by']) {
			case 'by_player':
                $data['show_cols'] = ['currency', 'date', 'userName', 'transactionType', 'amount'];
				break;
			case 'by_level':
                $data['show_cols'] = ['currency', 'date', 'levelName', 'transactionType', 'amount'];
				break;
			case 'by_payment_type':
			default:
                $data['show_cols'] = ['currency', 'date', 'transactionType', 'paymentAccount', 'amount'];
				break;
		}

        $this->loadTemplate('Super Payment Report', '', '', 'super_report');
        $this->template->write_view('main_content', 'super_report_management/payment_report',$data);
        $this->template->render();
    }

    public function promotion_report() {

        if (!$this->permissions->checkPermissions('super_promotion_report')) {
            $this->error_access();
        }
        $data['currency_list_super_report'] = $this->currency_list_super_report;
        //export report permission checking
        $data['export_report_permission'] = $this->permissions->checkPermissions('export_super_promotion_report');

        $this->loadTemplate('Super Promotion Report', '', '', 'super_report');
        $this->template->write_view('main_content', 'super_report_management/promotion_report',$data);
        $this->template->render();
    }

    public function cashback_report() {

        if (!$this->permissions->checkPermissions('super_cashback_report')) {
            $this->error_access();
        }

        // $data['currency_list_super_report'] = $this->currency_list_super_report;
        $data['currency_list'] = $this->super_report_lib->getAvailableCurrencies();
        $data['master_currency'] = $this->super_report_lib->getMasterCurrencyCode();
        //export report permission checking
        $data['export_report_permission'] = $this->permissions->checkPermissions('export_super_cashback_report');

        $data['conditions'] = $this->safeLoadParams([
            'chosen_currencies' => implode(",", $data['currency_list']),
            'search_reg_date' => 'off',
            'registration_date_from' => $this->utils->getTodayForMysql().' '.Utils::FIRST_TIME,
            'registration_date_to' => $this->utils->getTodayForMysql().' '.Utils::LAST_TIME,
            'username' => '',
            'by_date_from' => $this->utils->getTodayForMysql(),
            'by_date_to' => $this->utils->getTodayForMysql(),
            'by_paid_flag' => '',
            'by_username' => '',
            'by_player_level' => '',
            'by_amount_less_than' => '',
            'by_amount_greater_than' => '',
            'agent_username' => '',
        ]);

        $data['conditions']['enable_date'] = $this->safeGetParam('enable_date', true, true);

        $playerLevels = $this->group_level->getAllPlayerLevelsForSelect();

        array_walk($playerLevels, function (&$row) {
            $data = (explode("|",$row['groupLevelName']));
            if(!empty($data)){
                $row['groupLevelName'] = lang($data[0]).' - '.lang($data[1]);
            }
        });

        $data['vipgrouplist'] = array('' => lang('Select All')) + array_column($playerLevels, 'groupLevelName', 'vipsettingcashbackruleId');
        $data['selected_tags'] = $this->input->get_post('tag_list');
        $data['tags'] = $this->player->getAllTagsOnly();

        /* $tag_list = [];
        $this->load->model(['multiple_db_model']);
        foreach ($data['currency_list'] as $currency) {
            $tags = $this->multiple_db_model->getTagsByCurrencyToSuper($currency);

            $tag_list = array_merge($tag_list, $tags);
        }

        $data['tags'] = $tag_list; */

        $this->loadTemplate('Super Cashback Report', '', '', 'super_report');
        $this->template->add_js('resources/js/bootstrap-switch.min.js');
        $this->template->add_css('resources/css/bootstrap-switch.min.css');
        $this->template->add_css('resources/third_party/bootstrap-multiselect-master/dist/css/bootstrap-multiselect.css');
        $this->template->add_js('resources/third_party/bootstrap-multiselect-master/dist/js/bootstrap-multiselect.js');
        $this->template->write_view('main_content', 'super_report_management/cashback_report',$data);
        $this->template->render();
    }

    private function loadNewTemplate($title, $description, $keywords, $activenav) {

        // $this->template->set_template('super_report');
        $this->template->write('title', $title);
        $this->template->write('description', $description);
        $this->template->write('keywords', $keywords);
        $this->template->write('activenav', $activenav);
        $this->template->write('username', $this->authentication->getUsername());
        $this->template->write('userId', $this->authentication->getUserId());
    }


}