<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

require_once dirname(__FILE__) . '/BaseController.php';
/**
 * IP Management
 *
 * IP Management Controller
 *
 * General behaviors include
 * * update currency
 * * Add/update/delete domains
 * * Update setting for the duplicate account setting of fields
 * * Update the field permissions for the player form
 * * Update the field permissions for the affiliate form
 * * Lists all IP Addresses
 * * Add/Update/Delete a certain IP Address
 * * Able to Block and Allow a certain IP Address
 * * Able to Enable IP Whitelisting
 *
 * @category ip_management
 * @version 1.8.10
 * @copyright 2013-2022 tot
 */
class Ip_management extends BaseController {
	const IPRULES_SETTING = 'ip_rules';
    private $_username;

	function __construct() {
		parent::__construct();

		$this->load->helper('url');
		$this->load->library(array('permissions', 'template', 'form_validation', 'report_functions', 'salt', 'ip_manager'));
		$this->load->model(array('ip'));

		$this->permissions->checkSettings();
		$this->permissions->setPermissions();
        $this->_username = $this->authentication->getUsername();
	}

	/**
	 * overview : loads template
	 *
	 * detail : Loads template for view based on regions in config > template.php
	 *
	 * @param  string 	$title
	 * @param  string 	$description
	 * @param  string 	$keywords
	 * @param  string 	$activenav
	 * @return rendered template
	 */
	private function loadTemplate($title, $description, $keywords, $activenav) {
		$this->template->add_js('resources/js/system_management/ip_management.js');

		// $this->template->add_js('resources/js/datatables.min.js');
		// $this->template->add_js('resources/js/jquery.dataTables.min.js');
		// $this->template->add_js('resources/js/dataTables.responsive.min.js');

		$this->template->add_css('resources/css/general/style.css');
		//$this->template->add_css('resources/css/jquery.dataTables.css');
		//$this->template->add_css('resources/css/dataTables.responsive.css');
		// $this->template->add_css('resources/css/datatables.min.css');

		$this->loadThirdPartyToTemplate('datatables');

		$this->template->write('title', $title);
		$this->template->write('description', $description);
		$this->template->write('keywords', $keywords);
		$this->template->write('activenav', $activenav);
		$this->template->write('username', $this->authentication->getUsername());
		$this->template->write('userId', $this->authentication->getUserId());

		$this->template->write_view('sidebar', 'system_management/sidebar');
	}

	/**
	 * overview : error access
	 *
	 * detail : show error message if user can't access the page
	 */
	private function error_access() {
		$this->loadTemplate('IP Management', '', '', 'system');
		$systemUrl = $this->utils->activeSystemSidebar();
		$data['redirect'] = $systemUrl;

		$message = lang('con.i01');
		$this->alertMessage(self::MESSAGE_TYPE_ERROR, $message);

		$this->template->write_view('main_content', 'error_page', $data);
		$this->template->render();
	}

	/**
	 * overview
	 *
	 * detail: Index Page of User Management
	 * @return	void
	 */
	public function index() {
		redirect(BASEURL . 'ip_management/viewList');
	}


	/**
	 * overview : view IP's
	 *
	 * detail : view all IP either blocked/not-blocked IP
	 * @return render template
	 */
	public function viewList() {
		if (!$this->permissions->checkPermissions('ip')) {
			$this->error_access();
		} else {
			$this->loadTemplate(lang('system.word20'), '', '', 'system');

			if (($this->session->userdata('sidebar_status') == NULL)) {
				$this->session->set_userdata(array('sidebar_status' => 'active'));
			}
			// For crumbs
			if (($this->session->userdata('well_crumbs') == NULL)) {
				$this->session->set_userdata(array('well_crumbs' => 'active', 'system_crumb' => 'active'));
			}

			$data['ip'] = $this->ip_manager->getAllIp();

			$data['ipList'] = $this->checkIpList();
			$data['isMyIpExists'] = $this->ip_manager->checkIfIpExists($this->input->ip_address());

			$this->template->write_view('main_content', 'system_management/view_list', $data);
			$this->template->render();
		}
	}


	/**
	 * overview : check ip list
	 *
	 * detail : get if ipList will be use in config.xml
	 * @return  array
	 */
	public function checkIpList() {
		// $xml = new DOMDocument();
		// $xml->load(BASEPATH . "config.xml");

		// $nodes = $xml->getElementsByTagName("ipList");
		// $node = $nodes->item(0);

		$this->load->model('operatorglobalsettings');
		$data = $this->operatorglobalsettings->getOperatorGlobalSetting(self::IPRULES_SETTING);
		return $data[0]['value'];
	}

	/**
	 * overview : add ip
	 *
	 * detail : post add ip
	 * @return void
	 */
	public function addIp() {
		if (!$this->permissions->checkPermissions('ip')) {
			$this->error_access();
		} else {
			$type_of_action = $this->input->post('type_of_action');
			$today = date("Y-m-d H:i:s"); //will get the date based on the httpd.conf's timezone.
            $materMostTitle = "IP Settings - Add Ip Address To Whitelist";

			if ($type_of_action != 'Submit') {
				$ip_name = $this->input->ip_address();

				if(!$this->checkValidIp($ip_name)){
					$message = lang('system.invalidIP');
					$this->alertMessage(self::MESSAGE_TYPE_ERROR, $message);
					redirect('ip_management/viewList'); 
				}

				$result = $this->checkIfIpExists($ip_name);
                $materMostMessage = "User " . $this->_username . " try to add the ip address [ " . $ip_name . " ] but failed";
				if ($result == FALSE) {
					$message = $ip_name . " " . lang('con.i02');
					$this->alertMessage(self::MESSAGE_TYPE_ERROR, $message); //will set and send message to the user
				} else {
                    $adminUserId=$this->authentication->getUserId();
					//db trans
					$success=$this->dbtransOnly(function()
						use($ip_name, $today, $adminUserId){
						$reason=lang('system.addedIp') . ' - ' . $ip_name;
						$succ=$this->ip->recordIpChangeHistory($adminUserId, $reason);
						if($succ){
							$data = array(
								'ipName' => $ip_name,
								'createTime' => $today,
								'createPerson' => $adminUserId,
							);

							$this->ip->addIp($data);
							$this->saveAction(lang('system.addedIp'), $reason);
						}
						return $succ;
					});
					if($success){
                        $materMostMessage = "User " . $this->_username . " had successfully added the ip address [ " . $ip_name . " ]";
						$message = lang('con.i03') . ": " . $ip_name;
						$this->alertMessage(self::MESSAGE_TYPE_SUCCESS, $message); //will set and send message to the user
					}else{
						$message = lang('Save failed') . ": " . $ip_name;
						$this->alertMessage(self::MESSAGE_TYPE_ERROR, $message); //will set and send message to the user
					}
                    $this->sendMatterMostMessage($materMostTitle, $materMostMessage);
				}

                redirect('ip_management/viewList'); //redirect to addRole
			} else {
				$this->form_validation->set_rules('ip_name', 'IP Address', 'trim|required|xss_clean|callback_checkIfIpExists');
                if ($this->form_validation->run() == FALSE) {
					$this->viewList(); //redirect to addRole
				} else {
					$ip_name = $this->input->post('ip_name');
					$remarks = $this->input->post('remarks');
					$adminUserId=$this->authentication->getUserId();
                    $materMostMessage = "User " . $this->_username . " try to add the ip address [ " . $ip_name . " ] but failed";

					if(!$this->checkValidIp($ip_name)){
						$message = lang('system.invalidIP');
						$this->alertMessage(self::MESSAGE_TYPE_ERROR, $message);
						redirect('ip_management/viewList'); 
					}

					//db trans
					$success=$this->dbtransOnly(function()
						use($ip_name, $today, $remarks, $adminUserId){
						$reason=lang('system.addedIp') . ' - ' . $ip_name;
						$succ=$this->ip->recordIpChangeHistory($adminUserId, $reason);
						if($succ){
							$data = array(
								'ipName' => $ip_name,
								'remarks' => $remarks,
								'createTime' => $today,
								'createPerson' => $adminUserId,
							);
							$this->ip->addIp($data);
							$this->saveAction(lang('system.addedIp'), $reason);
						}
						return $succ;
					});
					if($success){
                        $materMostMessage = "User " . $this->_username . " had successfully added the ip address [ " . $ip_name . " ]";
						$message = lang('con.i03') . ": " . $ip_name;
						$this->alertMessage(self::MESSAGE_TYPE_SUCCESS, $message); //will set and send message to the user
					}else{
						$message = lang('Save failed') . ": " . $ip_name;
						$this->alertMessage(self::MESSAGE_TYPE_ERROR, $message); //will set and send message to the user
					}
                    $this->sendMatterMostMessage($materMostTitle, $materMostMessage);
                    redirect('ip_management/viewList'); //redirect to addRole
				}
			}
		}
	}


	/**
	 * overview : check if ip exist
	 *
	 * detail : check if ip address exists
	 * @param  string 	$ip
	 * @return bool
	 */
	public function checkIfIpExists($ip) {
		$result = $this->ip_manager->checkIfIpExists($ip);

		if ($result) {
			$this->form_validation->set_message('checkIfIpExists', 'The %s already exists');
			return FALSE;
		} else {
			return TRUE;
		}
	}

	/**
	 * overview : lock ip
	 *
	 * detail : post lock ip
	 * @param  int 	$ip_id
	 * @param  int 	$lock
	 * @return void
	 */
	public function lockIp($ip_id, $lock) {
		if (!$this->permissions->checkPermissions('ip')) {
			$this->error_access();
		} else {
			$status = $lock == 1 ? lang('system.blockIp') : lang('system.allowIp');
			$adminUserId=$this->authentication->getUserId();
			//db trans
			$success=$this->dbtransOnly(function()
				use($lock, $status, $ip_id, $adminUserId){
				$reason=$status . " - " . $oldIp['ipName'];
				$succ=$this->ip->recordIpChangeHistory($adminUserId, $reason);
				if($succ){
					$oldIp = $this->ip->getIPDetails($ip_id);
					$data = array(
						"status" => $lock,
					);
					$this->ip->lockIp($ip_id, $data);
					$this->saveAction($status, $reason);
				}
				return $succ;
			});
			if($success){
				$this->alertMessage(self::MESSAGE_TYPE_SUCCESS, $status); //will set and send message to the user
			}else{
				$this->alertMessage(self::MESSAGE_TYPE_ERROR, lang('Save failed'));
			}

			redirect('/ip_management/viewList');
		}
	}

	/**
	 * overview : edit ip
	 *
	 * @param  int 	$ip_id
	 * @return render template
	 */
	public function editIp($ip_id) {
		if (!$this->permissions->checkPermissions('ip')) {
			$this->error_access();
		} else {
			$this->loadTemplate('IP Management', '', '', 'system');

			$data['ip'] = $this->ip_manager->getAllIp();
			$data['ip_by_id'] = $this->ip_manager->getIpById($ip_id);
			$data['ipList'] = $this->checkIpList();
			$data['isMyIpExists'] = $this->ip_manager->checkIfIpExists($this->input->ip_address());
			$this->template->write_view('main_content', 'system_management/view_list', $data);
			$this->template->render();
		}
	}

	/**
	 * overview : verify edit ip
	 *
	 * detail : verifies the edited ip
	 * @param  int 	$ip_id
	 * @return void
	 */
	public function verifyEditIp($ip_id) {
		$type_of_action = $this->input->post('type_of_action');
		if ($type_of_action == 'Submit') {
			$this->form_validation->set_rules('ip_name', 'IP Name', 'trim|required|xss_clean');
			if ($this->form_validation->run()) {
                $ip_name = $this->input->post('ip_name');
				$remarks = $this->input->post('remarks');

				if(!$this->checkValidIp($ip_name)){
					$message = lang('system.invalidIP');
					$this->alertMessage(self::MESSAGE_TYPE_ERROR, $message);
					redirect('ip_management/viewList'); 
				}

                $materMostTitle = "IP Settings - Edit Ip Address To Whitelist";
                $materMostMessage = "User " . $this->_username . " try to edit ip address [ ". $ip_name ." ] but failed";
				$adminUserId=$this->authentication->getUserId();
				//db trans
				$success=$this->dbtransOnly(function()
					use($ip_id, $ip_name, $remarks, $adminUserId){
					$oldIp = $this->ip->getIPDetails($ip_id);
					$reason=lang('system.editIp') . " - [" . lang('adjustmenthistory.title.beforeadjustment') . '] ' . $oldIp['ipName'] . ' [' . lang('adjustmenthistory.title.afteradjustment') . '] ' . $ip_name;
					$succ=$this->ip->recordIpChangeHistory($adminUserId, $reason);
					if($succ){
						$data = array(
							'ipName' => $ip_name,
							'remarks' => $remarks,
						);
						$this->ip->editIp($ip_id, $data);
						$this->saveAction(lang('system.editIp'), $reason);
					}
					return $succ;
				});
				if($success){
                    $materMostMessage = "User " . $this->_username . " had successfully edited ip address [ ". $ip_name . " ]";
					$message = lang('con.i09') . ": " . $ip_name;
					$this->alertMessage(self::MESSAGE_TYPE_SUCCESS, $message);
				}else{
					$this->alertMessage(self::MESSAGE_TYPE_ERROR, lang('Save failed'));
				}
                $this->sendMatterMostMessage($materMostTitle, $materMostMessage);
			}
			redirect('/ip_management/viewList');
		}

		redirect('/ip_management/viewList');
	}

	public function checkValidIp($ip) {
		if ($this->form_validation->valid_ip($ip) == FALSE) {
			return FALSE;
		} else {
			return TRUE;
		}
	}

	/**
	 * overview : check ip
	 *
	 * detail : check ip process
	 * @return void
	 */
	public function checkIp() {
		if (!empty($this->input->post('ip'))) {
			$type_of_action = $this->input->post('type_of_action');
			$ip = implode(",", $this->input->post('ip'));

			switch ($type_of_action) {
			case 'Delete':
				$this->deleteCheckIp($ip);
				break;

			case 'Freeze':
				$this->lockCheckIp($ip, '1');
				break;

			case 'UnFreeze':
				$this->lockCheckIp($ip, '0');
				break;

			default:
				break;
			}
		} else {
			$message = lang('con.i10');
			$this->alertMessage(2, $message); //will set and send message to the user
			redirect(BASEURL . 'ip_management/viewList', 'refresh');
		}
	}

	/**
	 * check ip process
	 *
	 */
	/**
	 * overview : manage ip
	 *
	 * detail : check ip process
	 * @param  int 	$id
	 * @param  int 	$status
	 * @return void
	 */
	public function manageIp($id, $status) {
		if (!$this->permissions->checkPermissions('ip')) {
			$this->error_access();
		} else {
			$message=null;
			$adminUserId=$this->authentication->getUserId();
			//db trans
			$success=$this->dbtransOnly(function()
				use($id, $status, $adminUserId, &$message){
				$reason = null;
				$ipDetails = $this->ip->getIPDetails($id);
				if ($status == Ip::STATUS_DELETE) {
                    $materMostTitle = "IP Settings - Delete The Ip Address From Whitelist";
                    $materMostMessage = "User " . $this->_username . " try to delete the ip address[ " . $ipDetails['ipName'] . " ] but failed";
					$reason = lang('system.deleteIp') . " - " . $ipDetails['ipName'];
				} else if ($status == Ip::STATUS_ALLOW) {
                    $materMostTitle = "IP Settings - Allow The Ip Address From Whitelist";
                    $materMostMessage = "User " . $this->_username . " try to allow the ip address[ " . $ipDetails['ipName'] . " ] but failed";
					$reason = lang('system.allowIp') . " - " . $ipDetails['ipName'];
				} elseif ($status == Ip::STATUS_BLOCK) {
                    $materMostTitle = "IP Settings - Block The Ip Address From Whitelist";
                    $materMostMessage = "User " . $this->_username . " try to block the ip address[ " . $ipDetails['ipName'] . " ] but failed";
					$reason = lang('system.blockIp') . " - " . $ipDetails['ipName'];
				}
				$succ=$this->ip->recordIpChangeHistory($adminUserId, $reason);
				if($succ){
					if ($status == Ip::STATUS_DELETE) {
						$message = lang('system.message.ipmanagement.delete.success');
                        $materMostMessage = "User " . $this->_username . " had successfully deleted the ip address [ ". $ipDetails['ipName'] . " ]";
						$this->ip->deleteIp($id);
						$this->saveAction(lang('system.deleteIp'), $reason);
					} else {
						$this->ip->editIp($id, array('status' => $status));
						if ($status == Ip::STATUS_ALLOW) {
							$this->saveAction(lang('system.allowIp'), $reason);
							$message = lang('system.message.ipmanagement.allow.success');
                            $materMostMessage = "User " . $this->_username . " had successfully allowed the ip address [ ". $ipDetails['ipName'] . " ]";
						} elseif ($status == Ip::STATUS_BLOCK) {
							$this->saveAction(lang('system.blockIp'), $reason);
							$message = lang('system.message.ipmanagement.block.success');
                            $materMostMessage = "User " . $this->_username . " had successfully blocked the ip address [ ". $ipDetails['ipName'] . " ]";
						}
					}
				}
                if(!empty($reason)){
                    $this->sendMatterMostMessage($materMostTitle, $materMostMessage);
                }
				return $succ;
			});
			if($success){
				$this->alertMessage(self::MESSAGE_TYPE_SUCCESS, $message);
			}else{
				$this->alertMessage(self::MESSAGE_TYPE_ERROR, lang('Save failed'));
			}

			redirect('/ip_management/viewList');
		}
	}

	/**
	 * overview: delete check ip
	 *
	 * @param  string 	$ip
	 * @return void
	 */
	public function deleteCheckIp($ip) {
		if (!$this->permissions->checkPermissions('ip')) {
			$this->error_access();
		} else {
			if(!empty($ip)){
                $materMostTitle = "IP Settings - Delete The Ip Address From Whitelist";
                $ip_list = explode(',', $ip);
                $ip_names = [];
                $adminUserId=$this->authentication->getUserId();
                //db trans
                $success = $this->dbtransOnly(function()
                    use($ip_list, $adminUserId, $ip, &$ip_names){
                    $reason=lang('system.deleteIp') . " - " . $ip;
                    $succ=$this->ip->recordIpChangeHistory($adminUserId, $reason);
                    if($succ){
                        foreach ($ip_list as $value) {
                            $ipDetails = $this->ip->getIPDetails($value);
                            $this->saveAction(lang('system.deleteIp'), lang('system.deleteIp') . " - " . $ipDetails['ipName']);
                            $this->ip->deleteIp($value);
                            array_push($ip_names, $ipDetails['ipName']);
                        }
                    }
                    return $succ;
                });
                $ip_names = implode(" , ", $ip_names);
                if($success){
                    $materMostMessage = "User " . $this->_username . " had successfully deleted these ip address [ ". $ip_names ." ]";
                    $message = lang('con.i04');
                    $this->alertMessage(self::MESSAGE_TYPE_SUCCESS, $message);
                }else{
                    $materMostMessage = "User " . $this->_username . " try to delete these ip address [ ". $ip_names ." ] but failed";
                    $this->alertMessage(self::MESSAGE_TYPE_ERROR, lang('Save failed'));
                }
			}else{
                $materMostMessage = "User " . $this->_username . " try to delete but failed because no ip address selected";
                $this->alertMessage(self::MESSAGE_TYPE_ERROR, lang('Save failed'));
            }
            $this->sendMatterMostMessage($materMostTitle, $materMostMessage);
			redirect('/ip_management/viewList');
		}
	}

	/**
	 * overview : check locked ip
	 *
	 * @param  string 	$ip
	 * @param  int 		$lock
	 * @return void
	 */
	public function lockCheckIp($ip, $lock) {
		if (!$this->permissions->checkPermissions('ip')) {
			$this->error_access();
		} else {
			$ip_list = explode(',', $ip);
			$status = $lock == 1 ? lang('system.blockIp') : lang('system.allowIp');
            $ip_names = [];
            $materMostTitle = $lock == 1 ?
                "IP Settings - Block The Ip Address From Whitelist" :
                "IP Settings - Unblock The Ip Address From Whitelist";
			$message=null;
			$adminUserId=$this->authentication->getUserId();
			//db trans
			$success=$this->dbtransOnly(function()
				use($ip_list, $lock, $adminUserId, $status, $ip, &$message, &$ip_names){
				$reason=$status . " - " .$ip;
				$succ=$this->ip->recordIpChangeHistory($adminUserId, $reason);
				if($succ){
					$data = array(
						"status" => $lock,
					);
					foreach ($ip_list as $value) {
						$this->ip->lockIp($value, $data);
						$ipDetails = $this->ip->getIPDetails($value);
						$this->saveAction($status, $status . " - " . $ipDetails['ipName']);
                        array_push($ip_names, $ipDetails['ipName']);
					}
					if ($lock == 1) {
						$message = lang('system.blockIp');
					} else {
						$message = lang('system.allowIp');
					}
				}
				return $succ;
			});
            $ip_names = implode(" , ", $ip_names);
			if($success){
                $this->alertMessage(self::MESSAGE_TYPE_SUCCESS, $message);
                $materMostMessage = $lock == 1 ? 
                    "User " . $this->_username . " had successfully blocked these ip address [ " . $ip_names . " ] " :
                    "User " . $this->_username . " had successfully unblocked these ip address [ " . $ip_names . " ] ";
			}else{
                $this->alertMessage(self::MESSAGE_TYPE_ERROR, lang('Save failed'));
                $materMostMessage = $lock == 1 ? 
                    "User " . $this->_username . " try to block these ip address [ " . $ip_names . " ] but failed" :
                    "User " . $this->_username . " try to unblock these ip address [ " . $ip_names . " ]  but failed";
			}

            $this->sendMatterMostMessage($materMostTitle, $materMostMessage);
            
			redirect('/ip_management/viewList'); //redirect to addRole
		}
	}

	/**
	 * overview : set ip list
	 *
	 * detail : able/disable ip white listing
	 * @param  array 	$result
	 * @return void
	 */
	public function setIpList($result) {
		$this->load->model(array('operatorglobalsettings', 'ip'));
		$this->load->library(['authentication']);
		if ($result == 'true') {
			if (!$this->ip->checkIfIpExists($this->utils->getIP())) {
                $ipName = $this->utils->getIP();
				$adminUserId=$this->authentication->getUserId();
				//db trans
				$success=$this->dbtransOnly(function()
					use($adminUserId, $ipName){
					$reason='[' . lang('system.enableIpWhitelisting') . '] ' . lang('con.i03') . ": " . $this->utils->getIP();
					$succ=$this->ip->recordIpChangeHistory($adminUserId, $reason);
					if($succ){
						$data = array(
							'ipName' => $ipName,
							'createTime' => $this->utils->getNowForMysql(),
							'createPerson' => $adminUserId,
						);

						$this->ip->addIp($data);

						$this->saveAction(lang('system.enableIpWhitelisting'), lang('system.enableIpWhitelisting'));
					}
					return $succ;
				});
                $materMostTitle = "IP Settings - Add Ip Address To Whitelist";
				if($success){
                    $materMostMessage = "User " . $this->_username . " had successfully added ip address [ ". $ipName ." ]";
					$message = '[' . lang('system.enableIpWhitelisting') . '] ' . lang('con.i03') . ": " . $this->utils->getIP();
                    $this->alertMessage(self::MESSAGE_TYPE_SUCCESS, $message);
				}else{
                    $materMostMessage = "User " . $this->_username . " try to add the ip address [ ". $ipName ." ] but failed";
					$this->alertMessage(self::MESSAGE_TYPE_ERROR, lang('Save failed'));
				}
			} else {
                $materMostTitle = "IP Settings - Enable Ip Whitelisting";
                $materMostMessage = "User " . $this->_username . " set whitelisting to enable";
				$this->saveAction(lang('system.enableIpWhitelisting'), lang('system.enableIpWhitelisting'));
                $this->alertMessage(self::MESSAGE_TYPE_SUCCESS, lang('system.enableIpWhitelisting'));
			}            
		} else {
            $materMostTitle = "IP Settings - Disable Ip Whitelisting";
            $materMostMessage = "User " . $this->_username . " set whitelisting to disable";
			$this->saveAction(lang('system.disableIpWhitelisting'), lang('system.disableIpWhitelisting'));
            $this->alertMessage(self::MESSAGE_TYPE_SUCCESS, lang('system.disableIpWhitelisting'));
		}

		$data = array(
			'name' => self::IPRULES_SETTING,
			'value' => $result,
		);
		$this->operatorglobalsettings->setOperatorGlobalSetting($data);
        $this->sendMatterMostMessage($materMostTitle, $materMostMessage);
        return redirect('/ip_management/viewList');
	}

    private function sendMatterMostMessage($title, $message){ 
        $setting = $this->utils->getConfig('moniter_changing_ip_rule');
        if(!empty($setting['channel'])){
            $level = 'warning';
            $channel = $setting['channel'];
            $this->utils->sendMessageToMattermostChannel($channel, $level, $title, $message);
        }
    }

}

/* End of file ip_management.php */
/* Location: ./application/controllers/ip_management.php */