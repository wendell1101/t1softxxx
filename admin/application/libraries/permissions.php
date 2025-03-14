<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

/**
 * Permissions
 *
 * Permissions library
 *
 * @package		Permissions
 * @author		Johann Merle
 * @version		1.0.0
 */

class Permissions {
	private $error = array();
	private $permissions = null;
	private $user_id = null;
	private $role_id = null;
	private $role_name = null;

	function __construct() {
		$this->ci = &get_instance();
		$this->ci->load->library(array('authentication', 'rolesfunctions', 'utils'));
		// $this->ci->load->model(array(''));
		$this->utils=$this->ci->utils;
		$this->user_id = $this->ci->authentication->getUserId();
		$this->setPermissions();
	}

	public function getPermissions() {
		return $this->permissions;
	}

	public function setPermissions() {
		$role = $this->getRoles();

		if ($role != null) {
			$role_id = $role['roleId'];

			$this->role_id = $role['roleId'];
			$this->role_name = $role['roleName'];
			$this->permissions = $this->getFunctions($role_id);
		}
	}

	public function checkPermissions($functionCode) {
		// $this->ci->utils->debug_log($this->permissions, $functionCode);

		if (is_array($functionCode)) {
			$result = true;
			$permissions = $this->permissions;
			// print_r($permissions);
			foreach ($functionCode as $item) {
				if (!in_array($item, $permissions)) {
					// $this->ci->utils->debug_log('no permission:'.$item);
					return false;
				}
			}
			return true;
		} else {
			$permissions = $this->permissions;
			if (is_array($permissions)) {
				return in_array($functionCode, $permissions);
			} else {
				return false;
			}
		}
	}

	public function checkAnyPermissions($functionCode) {
		// $this->ci->utils->debug_log($this->permissions, $functionCode);
		$permissions = $this->permissions;
		if (empty($permissions)) {
			return false;
		}
		if (is_array($functionCode)) {
			$result = false;
			// print_r($permissions);
			foreach ($functionCode as $item) {
				if (in_array($item, $permissions)) {
					$result=true;
					break;
				}
			}
			return $result;
		} else {
			return in_array($functionCode, $permissions);
		}
	}

	public function canAssignSuperAdmin() {
		return $this->getRoleId() == 1;
	}

	public function getRoleId() {
		return $this->role_id;
	}

	public function getRoleName() {
		return $this->role_name;
	}

	private function getRoles() {
		return $this->ci->rolesfunctions->getRoleByUserId($this->user_id);
	}

	private function getFunctions($role_id) {
		return $this->ci->rolesfunctions->getRolesFunctionsById($role_id);
	}

	public function checkSettings() {
		$this->ci->load->model(array('operatorglobalsettings', 'ip'));

		if ($this->ci->operatorglobalsettings->getSettingValue('ip_rules') == 'true') {
			//if ip is not allowed
			$ipAllowed=$this->ci->ip->checkIfIpAllowedForAdmin();
			$this->ci->utils->debug_log('checkIfIpAllowedForAdmin', ['ipAllowed'=>$ipAllowed,
				'getIpListFromXForwardedFor'=>$this->ci->input->getIpListFromXForwardedFor(),
				'getRemoteAddr'=>$this->ci->input->getRemoteAddr(),
				'getXRealipRemoteAddr'=>$this->ci->input->getXRealipRemoteAddr(),
			]);
			if (!$ipAllowed) {
				if(!$this->ci->input->is_ajax_request()){
                    $this->ci->authentication->logout();
                    $this->ci->utils->redirectToLogin();
				}else{
 					$this->ci->output->set_status_header(401);
				}
			}
		}

		$this->ci->load->library(array('user_functions'));

		if ($this->ci->user_functions->checkIfUserIsLocked($this->user_id)) {
			if(!$this->ci->input->is_ajax_request()){
                $this->ci->authentication->logout();
                $this->ci->utils->redirectToLogin();
			}else{
				 $this->ci->output->set_status_header(401);
			}
		}

		if (!$this->ci->authentication->isLoggedIn()) {
			//session timeout
			$show_message = array(
				'result' => 'warning',
				'message' => lang('session.timeout'),
			);
			$this->ci->session->set_userdata($show_message);

			if(!$this->ci->input->is_ajax_request()){
                $this->ci->utils->redirectToLogin();
			}else{
				$this->ci->output->set_status_header(401);
			}
		}

		if($this->ci->utils->isEnabledFeature('enable_otp_on_adminusers')){
			//check if force admin to enable 2FA
			list($force2FaEnabled, $adminEnabled2Fa, $forceRedirect) = $this->ci->utils->checkIfAdminForceEnabled2FA();
			$this->ci->utils->debug_log('checkIfAdminForceEnabled2FA', [
				'force2FaEnabled' => $force2FaEnabled,
				'adminEnabled2Fa' => $adminEnabled2Fa,
				'forceRedirect' => $forceRedirect
			]);
			if(!$this->ci->input->is_ajax_request()){
				if($force2FaEnabled && !$adminEnabled2Fa && $forceRedirect){
					$this->ci->utils->redirectOtpSettings();
				}
			}
		}
	}

}

/* End of file permissions.php */
/* Location: ./application/libraries/permissions.php */