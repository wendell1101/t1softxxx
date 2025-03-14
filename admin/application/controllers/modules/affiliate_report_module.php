<?php
trait affiliate_report_module {
    /**
     * overview : view affiliate login report
     *
     * @return void
     */
    public function viewAffiliateLoginReport() {
        if (!$this->permissions->checkPermissions('view_affiliate_login_report')) {
			return $this->error_access();
        }
        $this->load->model('report_model');
        $this->loadTemplate('Affiliate Login Report', '', '', 'affiliate');

        $conditions = array(
            'by_date_from' => $this->input->get('by_date_from'),
            'by_date_to' => $this->input->get('by_date_to'),
            'by_username' => $this->input->get('by_username'),
            'login_ip' => $this->input->get('login_ip')
        );

        $data['conditions'] = $conditions;
        $data['report_data'] = array();// $this->report_model->getAffiliateLoginReport($conditions);

        $this->template->write_view('main_content', 'report_management/view_affiliate_login_report', $data);
        $this->template->render();
    }
}
