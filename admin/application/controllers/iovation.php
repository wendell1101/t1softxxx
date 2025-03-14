<?php

require_once dirname(__FILE__) . '/BaseController.php';

/**
 * Iovation
 *
 *
 * @category System Management
 * @version 1.8.10
 * @copyright 2013-2022 tot
 */

class Iovation extends BaseController {

	function __construct() {
		parent::__construct();		
		$this->load->helper('url');
		$this->load->library(array('permissions', 'form_validation', 'template', 'pagination', 'report_functions', 'player_manager', 'duplicate_account', 'utils', 'iovation/iovation_lib'));
		$this->load->model(['external_system', 'iovation_logs', 'iovation_evidence']);		
		$this->permissions->checkSettings();
		$this->permissions->setPermissions(); //will set the permission for the logged in user
	}

	public function resend() {
		$this->load->model('iovation_logs');

		$this->form_validation->set_rules('id', 'Id', 'required|trim');

		if ($this->form_validation->run() == false) {
			$arr = array('status' => 'error', 'msg' => validation_errors());
			$this->returnJsonResult($arr);
		} else {
			$logId = $this->input->post('id');
			
			$data = $this->iovation_logs->getLog($logId, $this->iovation_lib->use_logs_monthly_table);
			
			if(!$data){
				$arr = array('status' => 'error', 'msg' => lang('iovation_evidence.internal_error'));
				$this->returnJsonResult($arr);
			}else{
				$params = [					
					'ip'=>$data->stated_ip,
					'blackbox'=>$data->blackbox,
					'log_id'=>$logId,
					'account_code'=>$data->account_code,
					'type'=>$data->type,
				];

				if($data->user_type='affiliate'){
					$params['affiliate_id'] = $data->player_id;
					$callType = Iovation_lib::API_resendAffiliateRegistration;
					if($data->type=='affiliateLogin'){						
						$callType = Iovation_lib::API_resendAffiliateLogin;
					}
					$iovationResponse = $this->iovation_lib->resendAffiliateToIovation($params, $callType);
				}else{
					$params['player_id'] = $data->player_id;	
                    $callType = Iovation_lib::API_resendRegistration;

                    if ($data->type == 'playerLogin') {						
						$callType = Iovation_lib::API_resendPlayerLogin;
					}		

					$iovationResponse = $this->iovation_lib->resendToIovation($params, $callType);			
				}
				
				if($iovationResponse['success']){
					$arr = array('status' => 'success','msg' => lang('iovation_evidence.successfully_resend'));
					$this->returnJsonResult($arr);
				}else{
					$arr = array('status' => 'error','msg' => lang('iovation_evidence.error_resending'));
					$this->returnJsonResult($arr);
				}
				
			}
		}

	}

	public function add_edit_evidence($evidenceId=null) {
		
		$data['appliedto_type_list'] = array(
			'' => lang('iovation_evidence.please_select'),
			'account' => 'account',
			'device' => 'device',
		);
		$data['user_type_list'] = array(
			'' => lang('N/A'),
			'player' => lang('Player'),
			'affiliate' => lang('Affiliate')
		);

		$data['evidence_appliedto_type'] = null;
		$data['appliedto_identifier'] = null;	
		$data['evidence_type_text'] = '';	
		$data['evidence_type_list'] = array(
			'' => lang('iovation_evidence.please_select'));

		foreach(Iovation_lib::EVIDENCE_TYPES as $key => $row){
			$data['evidence_type_list'][$key]=lang($row);
		}		

		$data['form_add_edit_url'] = "/iovation/postAddEvidence";
		if(!empty($evidenceId)){
			$data['form_add_edit_url'] = "/iovation/postUpdateEvidence/".$evidenceId ;
		}

		$evidence = $this->iovation_evidence->getEvidenceById($evidenceId);

		$data['evidence_id'] = $evidenceId;	
		$data['evidence_appliedto'] = '';	
		$data['evidence_appliedto_type_value'] = '';	
		$data['user_type'] = '';	
			
		$data['evidence'] = $evidence;
		$data['username'] = null;

		if($evidence){		
			$parsed_appliedTo = json_decode($evidence->applied_to, true);
			$parsed_details = json_decode($evidence->response, true);
			$data['evidence_appliedto'] = $parsed_appliedTo['type'];							
			if($data['evidence_appliedto']=='account'){
				$data['evidence_appliedto_type_value'] = $parsed_appliedTo['accountCode'];	
			}elseif($data['evidence_appliedto']=='device'){
				$data['evidence_appliedto_type_value'] = (string)$parsed_appliedTo['deviceAlias'];	
			}
			$data['evidence_type_text'] = lang(@Iovation_lib::EVIDENCE_TYPES[$evidence->evidence_type]);
			$data['user_type'] = $evidence->user_type;
		}

		$this->load->view('system_management/view_add_edit_evidence', $data);
	}

	public function retract_evidence($evidenceId=null) {

		$evidence = $this->iovation_evidence->getEvidenceById($evidenceId);
		if(!$evidence){
			return;
		}

		$data['evidence_id'] = $evidenceId;	
		$data['form_add_edit_url'] = "/iovation/retractEvidence/".$evidenceId;		

		$this->load->view('system_management/view_retract_evidence', $data);
	}

	public function getPlayerIovationLogs(){
		$arr = array('status' => 'success','msg' => lang('Success'));
		$this->returnJsonResult($arr);
	}

	public function postAddEvidence(){

		$this->load->model(['player_model', 'affiliate']);

		//validate required data
		$request = $this->input->post();

		$this->form_validation->set_rules('evidence_appliedto_type', 'Evidence Applied To', 'required|trim');
		$this->form_validation->set_rules('evidence_appliedto_type_value', 'Username, DeviceID', 'trim');		
		$this->form_validation->set_rules('evidence_type', 'Evidence Type', 'required|trim');
		$this->form_validation->set_rules('user_type', 'User Type', 'required|trim');
		$this->form_validation->set_rules('comment', 'Comment', 'required|trim');
		
		if ($this->form_validation->run() == false) {
			$arr = array('status' => 'error', 'msg' => validation_errors());
			$this->returnJsonResult($arr);
			return;
		}

		if(!in_array($request['evidence_appliedto_type'], ['account','device'])){
			$arr = array('status' => 'error', 'msg' => 'Invalid applied to type (device|account)');
			$this->returnJsonResult($arr);
			return;
		}

		if(empty($request['evidence_appliedto_type_value'])){
			$arr = array('status' => 'error', 'msg' => 'Username or Device ID is required');
			$this->returnJsonResult($arr);
			return;
		}

		$evidence_appliedto_type_value = $request['evidence_appliedto_type_value'];


		//find existing iovation logs by username
		$accountCode = $player = null;
		
		//send request to iovation		
		$applied_to = [];
		$device_alias = null;
		$account_code = null;
		$playerId = null;
		$affiliateId = null;

		$applied_to['type'] = $request['evidence_appliedto_type'];
		$params = [
			'evidence_type'=>$request['evidence_type'],
			'user_type'=>$request['user_type'],
			'comment'=>$request['comment'],				
			'applied_to_type'=>$request['evidence_appliedto_type'],	
			'iovation_log'=>null,									
		];		

		if($request['evidence_appliedto_type']=='account'){			
			$account_code = $applied_to['accountCode'] = $this->iovation_lib->getAccountCode($evidence_appliedto_type_value, $request['user_type']);		

		}elseif($request['evidence_appliedto_type']=='device'){	
			$device_alias = $applied_to['deviceAlias'] = $evidence_appliedto_type_value;
		}
		
		if($request['evidence_appliedto_type']=='account'){		
			if($request['user_type']=='affiliate'){
				$affiliate = $this->affiliate->getAffiliateByName($evidence_appliedto_type_value);
				$affiliateId = isset($affiliate['affiliateId'])?$affiliate['affiliateId']:null;
			}else{
				$player = $this->player->getPlayerByUsername($evidence_appliedto_type_value);				
				$playerId = isset($player['playerId'])?$player['playerId']:null;
			}
			if(empty($playerId) && empty($affiliateId)){
				$arr = array('status' => 'error','msg' => lang('iovation_evidence.error_sending_evidence_account_error'));
				$this->returnJsonResult($arr);
				return;
			}
		}

		$params['player_id'] = $playerId;
		$params['affiliate_id'] = $affiliateId;
		$params['applied_to'] = $applied_to;
		$params['device_alias'] = $device_alias;
		$params['account_code'] = $account_code;

		$iovationResponse = $this->iovation_lib->sendEvidenceToIovation($params);
		if(!$iovationResponse['success']){
			$arr = array('status' => 'error','msg' => lang('iovation_evidence.error_sending_evidence'));
			$this->returnJsonResult($arr);
			return;
		}

		$arr = array('status' => 'success','msg' => lang('iovation_evidence.successfully_added'));
		$this->returnJsonResult($arr);
	}

	public function postUpdateEvidence($id){
		//validate required data
		$request = $this->input->post();

		$this->form_validation->set_rules('evidence_id', 'Evidence ID', 'required|trim');
		$this->form_validation->set_rules('evidence_type', 'Evidence Type', 'required|trim');
		$this->form_validation->set_rules('evidence_appliedto_type', 'Evidence Applied To', 'required|trim');
		$this->form_validation->set_rules('comment', 'Comment', 'required|trim');
		
		if ($this->form_validation->run() == false) {
			$arr = array('status' => 'error', 'msg' => validation_errors());
			$this->returnJsonResult($arr);
			return;
		}

		if(!in_array($request['evidence_appliedto_type'], ['account','device'])){
			$arr = array('status' => 'error', 'msg' => 'Invalid applied to type (device|account)');
			$this->returnJsonResult($arr);
			return;
		}

		//find existing evidence
		$evidence = $this->iovation_evidence->getEvidenceById($request['evidence_id']);
		if(!$evidence){
			$arr = array('status' => 'error','msg' => lang('iovation_evidence.cannot_find_evidence'));
			$this->returnJsonResult($arr);
			return;
		}

		$evidence_appliedto_type_value = $request['evidence_appliedto_type_value'];
		
		//find existing iovation logs by username
		$accountCode = $player = null;
		
		//send request to iovation
		
		$applied_to = [];
		$device_alias = null;
		$account_code = null;
		$applied_to['type'] = $request['evidence_appliedto_type'];
		if($request['evidence_appliedto_type']=='account'){			
			$account_code = $applied_to['accountCode'] = $evidence_appliedto_type_value;
			$player = $this->player->getPlayerByUsername($evidence_appliedto_type_value);
		}elseif($request['evidence_appliedto_type']=='device'){			
			$device_alias = $applied_to['deviceAlias'] = $evidence_appliedto_type_value;
		}
		$playerId = null;
		if(isset($player['playerId'])){
			$playerId = $player['playerId'];
		}

		$params = [
			'evidence_type'=>$request['evidence_type'],
			'comment'=>$request['comment'],
			'user_type'=>$request['user_type'],
			'applied_to'=>$applied_to,
			'player_id'=>$playerId,
			'account_code'=>$account_code,	
			'device_alias'=>$device_alias,				
			'iovation_log'=>null,	
			'applied_to_type'=>$request['evidence_appliedto_type'],	
			'evidence_id'=>$evidence->evidence_id,									
		];

		$iovationResponse = $this->iovation_lib->updateEvidenceToIovation($params);
		if(!$iovationResponse['success']){
			$arr = array('status' => 'error','msg' => lang('iovation_evidence.error_sending_evidence'));
			$this->returnJsonResult($arr);
			return;
		}

		$arr = array('status' => 'success','msg' => lang('iovation_evidence.successfully_edited'));
		$this->returnJsonResult($arr);
	}

	public function retractEvidence($id) {
		$this->load->model(['iovation_logs','player_model', 'affiliate']);		

		$this->form_validation->set_rules('evidence_id', 'Evidence Id', 'required|trim');
		$this->form_validation->set_rules('comment', 'Comment', 'required|trim');

		if ($this->form_validation->run() == false) {
			$arr = array('status' => 'error', 'msg' => validation_errors());
			$this->returnJsonResult($arr);
		} else {

			$request = $this->input->post();

			$evidence = $this->iovation_evidence->getEvidenceById($request['evidence_id']);
			
			if(!$evidence){
				$arr = array('status' => 'error', 'msg' => lang('iovation_evidence.cannot_find_evidence'));
				$this->returnJsonResult($arr);
			}else{
				$playerId = null;
				$affiliateId = null;
				if($evidence->user_type='affiliate'){
					$affiliate = $this->affiliate->getAffiliateByName($evidence->affiliate_id);
					$affiliateId = isset($affiliate['affiliateId'])?$affiliate['affiliateId']:null;
				}else{
					$player = $this->player->getPlayerById($evidence->player_id);
					$playerId = isset($player['playerId'])?$player['playerId']:null;
				}

				//try retract
				$params = [
					'evidence_id'=>$evidence->evidence_id,
					'comment'=>$request['comment'],			
					'player_id'=>$playerId,		
					'affiliate_id'=>$affiliateId,		
				];
				$iovationResponse = $this->iovation_lib->retractEvidence($params);
				if($iovationResponse['success']){
					$arr = array('status' => 'success','msg' => lang($iovationResponse['msg']));
					$this->returnJsonResult($arr);
				}else{
					$arr = array('status' => 'error','msg' => lang($iovationResponse['msg']));
					$this->returnJsonResult($arr);
				}
			}
		}

	}

    public function postBatchAddEvidence(){
        $this->load->model(array('player_model', 'affiliate'));
        $this->load->library(array('history'));
        $this->permissions->checkSettings();
        $this->permissions->setPermissions();

        $aResult = [];
        $aEvidence_list = [];
        $message = '';

        $available_ext = array("csv");
        $available_mime_type = array("text/plain");
        $file_name = new SplFileInfo($_FILES['tags']['name']);
        $file_ext  = $file_name->getExtension();
        if(in_array($file_ext, $available_ext) && in_array(mime_content_type($_FILES['tags']['tmp_name']) , $available_mime_type)){
            $aEvidence_list = array_map('str_getcsv', file($_FILES['tags']['tmp_name']));
        } else {
            $message = lang('Please put a csv file!');
            $this->alertMessage(self::MESSAGE_TYPE_ERROR, $message);
            redirect('report_management/viewIovationEvidence');
        }

        if(isset($message) === true) {
            $aResult['message'] = $message;
        }

        array_walk($aEvidence_list, function(&$a) use ($aEvidence_list) {
            if(count($aEvidence_list[0])==count($a)){
                $a = array_combine($aEvidence_list[0], $a);
            }          
        });
        array_shift($aEvidence_list);

        $aResult['processed_evidence'] = [];    

        $countAdded = 0;

        if(empty($aEvidence_list)){
            $message = lang('CSV file is empty!');
            $this->alertMessage(self::MESSAGE_TYPE_ERROR, $message);
            redirect('report_management/viewIovationEvidence');
        }

        foreach($aEvidence_list as $key => &$evidence){
            if(isset($evidence['evidence_type_code'])&&!empty($evidence['evidence_type_code'])){
                $evidence['evidence_type'] = $evidence['evidence_type_code'];
            }

            if(!isset($evidence['evidence_type'])){
                continue;
            }
            $evidence = $this->utils->trim_multi_array($evidence);
            $aEvidence_list[$key]['evidence_type_desc'] = lang($this->iovation_lib->getEvidenceDesc($evidence['evidence_type']));

            $aEvidence_list[$key]['message'] = '';
            $aEvidence_list[$key]['is_success'] = false;
                
            # check parameters
            if(!isset($evidence['applied_to'])|| empty($evidence['applied_to'])){
                $aEvidence_list[$key]['message'] = lang('Missing Applied to.');
                continue;
            }
            if(!in_array($evidence['applied_to'], ['account','device'])){
                $aEvidence_list[$key]['message'] = lang('Applied to should be either account or device.');
                continue;
            }
            if($evidence['applied_to']=='device' && empty($evidence['device_id'])){
                $$aEvidence_list[$key]['message'] = lang('Missing device ID.');
                continue;
            }
            if($evidence['applied_to']=='account' && empty($evidence['username'])){
                $aEvidence_list[$key]['message'] = lang('Missing username.');
                continue;
            }
            if(!isset($evidence['user_type'])|| empty($evidence['user_type'])){
                $aEvidence_list[$key]['message'] = lang('Missing user type.');
                continue;
            }
            if(!in_array($evidence['user_type'], ['player','affiliate'])){
                $aEvidence_list[$key]['message'] = lang('User should be either player or affiliate.');
                continue;
            }
            if(!isset($evidence['comments'])|| empty($evidence['comments'])){
                $aEvidence_list[$key]['message'] = lang('Missing comments.');
                continue;
            }
            if(!isset($evidence['evidence_type'])|| empty($evidence['evidence_type'])){
                $aEvidence_list[$key]['message'] = lang('Missing evidence type.');
                continue;
            }
            if(empty($aEvidence_list[$key]['evidence_type_desc'])){
                $aEvidence_list[$key]['message'] = lang('Invalid evidence type. Type: '. $evidence['evidence_type'] . ' Value: '. $aEvidence_list[$key]['evidence_type_desc']);
                continue;
            }

            $playerId = null;
            $affiliateId = null;
            $account_code = null;
            $this->utils->error_log('evidence', $evidence);

            if($evidence['user_type']=='affiliate'){
				$affiliate = $this->affiliate->getAffiliateByName($evidence['username']);
				$affiliateId = isset($affiliate['affiliateId'])?$affiliate['affiliateId']:null;
                if(empty($affiliateId)){
                    $aEvidence_list[$key]['message'] = lang('iovation_evidence.error_sending_evidence_account_error');
                    continue;
                }
			}elseif($evidence['user_type']=='player'){
				$player = $this->player->getPlayerByUsername($evidence['username']);				
				$playerId = isset($player['playerId'])?$player['playerId']:null;
                if(empty($playerId) && $evidence['applied_to']=='account'){
                    $aEvidence_list[$key]['message'] = lang('iovation_evidence.error_sending_evidence_account_error');
                    continue;
                }
			}else{
                $aEvidence_list[$key]['message'] = lang('Invalid user type. Choose only between player and affiliate');
				continue;
            }

            $applied_to = [];
            $account_code = $this->iovation_lib->getAccountCode($evidence['username'], $evidence['user_type']);	
            
            if($evidence['applied_to']=='account'){			
                $account_code = $applied_to['accountCode'] = $account_code;		
    
            }elseif($evidence['applied_to']=='device'){	
                $device_alias = $applied_to['deviceAlias'] = trim($evidence['device_id'], " '");
            }

            $params = [];
            $applied_to['type'] = $evidence['applied_to'];

            $params['player_id'] = $playerId;
            $params['affiliate_id'] = $affiliateId;
            $params['applied_to'] = $applied_to;
            $params['applied_to_type'] = trim($evidence['applied_to']);
            $params['user_type'] = trim($evidence['user_type']);
            $params['comment'] = $evidence['comments'];
            $params['evidence_type'] = trim($evidence['evidence_type']);
            $params['account_code'] = $account_code;

            $iovationResponse = $this->iovation_lib->sendEvidenceToIovation($params);
            $this->utils->debug_log(__METHOD__." iovationResponse: ",$iovationResponse,
            'params', $params);
            if(!$iovationResponse['success']){
				$aEvidence_list[$key]['message'] = lang('iovation_evidence.error_sending_evidence');     
            }else{
                $aEvidence_list[$key]['message'] = lang('Successfully added evidence.');                           
                $aEvidence_list[$key]['is_success'] = true;
            }

            $temp['account_code'] = $account_code;
            $temp['params'] = $params;
        }

        $aResult['processed_evidence'] = $aEvidence_list;


        $aResult['header'] = ['Applied To', 'Device ID', 'Username', 'User Type', 'Evidence Type', 'Comments', 'Status'];
        $aResult['countAdded'] = $countAdded;
        $this->loadTemplate('Report Management', '', '', 'system');
		$this->template->add_js('resources/js/bootstrap-switch.min.js');
		$this->template->add_css('resources/css/bootstrap-switch.min.css');
        $this->template->write_view('main_content', 'report_management/batch_add_evidence_result', $aResult);
        $this->template->render();

    }


	private function loadTemplate($title, $description, $keywords, $activenav) {
		$this->template->add_css('resources/css/report_management/style.css');
		$this->template->add_js('resources/js/report_management/report_management.js');

		$this->template->add_js('resources/js/datatables.min.js');
		$this->template->add_js('resources/js/select2.full.js');
		$this->template->add_css('resources/css/select2.min.css');
		$this->template->add_css('resources/css/general/style.css');
		$this->template->add_css('resources/css/datatables.min.css');

		$this->template->write('title', $title);
		$this->template->write('description', $description);
		$this->template->write('keywords', $keywords);
		$this->template->write('activenav', $activenav);
		$this->template->write('username', $this->authentication->getUsername());
		$this->template->write('userId', $this->authentication->getUserId());
		$this->template->write_view('sidebar', 'report_management/sidebar');
	}

}

/* End of file game_api.php */
/* Location: ./application/controllers/iovation.php */
