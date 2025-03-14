<?php

trait agency_help_page_module {

	public function agency_help_page() {
		if ( ! $this->permissions->checkPermissions('view_agent')) {
			return $this->error_access();
		}
		
		$this->load_template(lang('Agency help page'), '', '', 'agency');
		// $this->template->add_js($this->utils->thirdpartyUrl('bootstrap/3.3.7/bootstrap.min.js'));
		// $this->template->add_css($this->utils->thirdpartyUrl('bootstrap/3.3.7/bootstrap.min.css'));
		$this->template->add_css($this->utils->thirdpartyUrl('font-awesome/v5/css/all.min.css'));
		$this->template->write_view('main_content', 'agency_management/help_page/english/view_page');
		$this->template->render();
	}
}
