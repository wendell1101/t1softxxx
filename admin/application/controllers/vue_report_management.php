<?php if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

require_once dirname(__FILE__) . '/BaseController.php';

/**
 * Class Vue_Report_Management
 */
class Vue_Report_Management extends BaseController {

    function __construct() {
        parent::__construct();
        $this->load->helper('url');
        $this->load->library(array('permissions', 'template', 'form_validation', 'report_functions', 'salt', 'ip_manager','data_tables'));

        $this->currency_list_super_report = $this->utils->getConfig('currency_list_super_report');

        $this->load->model('super_report');

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
        $this->loadTemplate('Vue Report', '', '', 'system');

        $message = lang('con.i01');
        $this->alertMessage(2, $message);

        $this->template->render();
    }

    public function index() {

        if (!$this->permissions->checkPermissions('super_report')) {
            return $this->error_access();
        }

        $this->loadNewTemplate('Vue Report', '', '', 'vue_report');
        $this->template->write_view('main_content', 'vue_report_management/vue_report');
        $this->template->render();
    }

    private function loadNewTemplate($title, $description, $keywords, $activenav) {

        $this->template->set_template('vue_report');
        $this->template->write('title', $title);
        $this->template->write('description', $description);
        $this->template->write('keywords', $keywords);
        $this->template->write('activenav', $activenav);
        $this->template->write('username', $this->authentication->getUsername());
        $this->template->write('userId', $this->authentication->getUserId());
    }

    public function quickFireReport() {
        redirect('vue_report_management'.'#/vue_report/quickfire', 'refresh');
    }

    // public function quickFireReport()
    // {
    //     // if (!$this->permissions->checkPermissions('super_game_report')) {
    //     //     $this->error_access();
    //     // }
    //     // $data['currency_list_super_report'] = $this->currency_list_super_report;
    //     // //export report permission checking
    //     // // $data['export_report_permission'] = $this->permissions->checkPermissions('export_super_game_report');
    //     // $data['username'] = "test username";

    //     // $this->loadNewTemplate('Vue  Report', 'vue_report', 'vue_report', 'vue_report');
    //     // $this->template->write_view('main_content', 'super_report_management/mg_quickfire_report');
    //     // $this->template->render();
    // }
}