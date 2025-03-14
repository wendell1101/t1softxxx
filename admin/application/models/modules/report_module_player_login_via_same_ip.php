<?php
trait report_module_player_login_via_same_ip {

    public function exportPlayerLoginViaSameIpLogsList($request, $permissions, $is_export = false) {
        $this->load->model(['player_login_via_same_ip_logs']);

        // $request = $this->input->post();
        // $is_export = false;
        // $permissions=$this->getContactPermissions();

        $result = $this->player_login_via_same_ip_logs->dataTablesList($request, $permissions, $is_export);
        return $result;
        // return $this->returnJsonResult($result);
    }
}