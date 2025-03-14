<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}
require_once dirname(__FILE__) . '/BaseReport.php';
require_once dirname(__FILE__) . '/modules/export_agency_module.php';
require_once dirname(__FILE__) . '/modules/export_player_login_via_same_ip_module.php';
require_once dirname(__FILE__) . '/modules/export_player_basic_amount_list_module.php';
/**
 *
 * General behaviors include
 * * getting player/payment/promotion/cashback reports
 * * get transaction details
 * * get duplicate account reports
 * * get game report for a certain player
 * * get affiliate statics/earnings/lists
 * * get game description lists
 *
 * @category export data
 * @version 1.8.10
 * @copyright 2013-2022 tot
 */

class Export_data extends BaseReport {

	use export_agency_module;
	use export_player_login_via_same_ip_module;
	use export_player_basic_amount_list_module;

	public function __construct() {
		parent::__construct();

		$this->utils->initiateLang();
		set_time_limit($this->utils->getConfig('default_sync_game_logs_max_time_second'));
		$this->load->library(array('permissions','template', 'lib_queue'));
		$this->load->model(['affiliatemodel', 'report_model', 'queue_result']);
		$this->permissions->setPermissions();

        //function white list
		$export_data_white_functions=$this->utils->getConfig('export_data_white_functions');

		$func_name=$this->uri->segment(2);

		if($this->utils->isFromHost('agency')) {
			if( !$this->isLoggedAgency()){
				show_error('No permissions', 403);
				exit;
			}else{
				//logged , but permissions
				$agency_functions=$export_data_white_functions['agency'];

				if(!in_array($func_name, $agency_functions)){
					show_error('No permissions', 403);
					exit;
				}
			}
		}else if($this->utils->isAdminSubProject()) {
			$this->load->library('authentication');
			if( !$this->authentication->isLoggedIn()){
				show_error('No permissions', 403);
				exit;
			}else{
				//logged , but permissions
				$aff_functions=$export_data_white_functions['admin'];
				if(!in_array($func_name, $aff_functions)){
					show_error('No permissions', 403);
					exit;
				}
			}
		}else if($this->utils->isAffSubProject()) {
        	if(empty($this->session->userdata('affiliateId'))){
				show_error('No permissions', 403);
        		exit;
			}else{
				//logged , but permissions
				$aff_functions=$export_data_white_functions['aff'];
				if(!in_array($func_name, $aff_functions)){
					show_error('No permissions', 403);
					exit;
				}
        	}
        }else{
			show_error('No permissions', 403);
    		exit;
        }
	}

	/**
	 * detail: getting player reports
	 *
	 * @return json
	 */
	public function player_reports() {
		$is_export = true;
		$funcName='player_reports';
		$callerType=Queue_result::CALLER_TYPE_SYSTEM;
		$caller=0;
		$state='';
		$viewPlayerInfoPerm = $this->permissions->checkPermissions('player_contact_information_email');
		$extra_params=[self::HTTP_REQEUST_PARAM, $viewPlayerInfoPerm, $is_export];
		$rlt=$this->exportData($funcName, $extra_params, $callerType, $caller, $state);

		if($rlt['success']){
			redirect($rlt['link']);
		}else{
			return show_error(lang('Export failed'));
		}
	}

	/**
	 * detail: getting player reports2
	 *
	 * @return json
	 */
	public function player_reports_2() {
		$is_export = true;
		$funcName='player_reports_2';
		$callerType=Queue_result::CALLER_TYPE_SYSTEM;
		$caller=0;
		$state='';
		$viewPlayerInfoPerm = $this->permissions->checkPermissions('player_contact_information_email');
		$extra_params=[self::HTTP_REQEUST_PARAM, $viewPlayerInfoPerm, $is_export];
		$rlt=$this->exportData($funcName, $extra_params, $callerType, $caller, $state);

		if($rlt['success']){
			redirect($rlt['link']);
		}else{
			return show_error(lang('Export failed'));
		}
	}

	public function quest_report(){
		$is_export = true;
		$funcName='quest_report';
		$callerType=Queue_result::CALLER_TYPE_SYSTEM;
		$caller=0;
		$state='';
		$viewQuestReport = $this->permissions->checkPermissions('quest_report');
		$extra_params=[self::HTTP_REQEUST_PARAM, $viewQuestReport, $is_export];
		$rlt=$this->exportData($funcName, $extra_params, $callerType, $caller, $state);

		if($rlt['success']){
			redirect($rlt['link']);
		}else{
			return show_error(lang('Export failed'));
		}
	}

	/**
	 * detail: getting playerAdditionalRouletteReports
	 *
	 * @return json
	 */
	public function playerAdditionalRouletteReports() {
		$is_export = true;
		$funcName='player_additionl_roulette_report';
		$callerType=Queue_result::CALLER_TYPE_SYSTEM;
		$caller=0;
		$state='';
		$viewPlayerInfoPerm = $this->permissions->checkPermissions('player_contact_information_email');
		$extra_params=[self::HTTP_REQEUST_PARAM, $viewPlayerInfoPerm, $is_export];
		$rlt=$this->exportData($funcName, $extra_params, $callerType, $caller, $state);

		if($rlt['success']){
			redirect($rlt['link']);
		}else{
			return show_error(lang('Export failed'));
		}
	}

    /**
     * detail: getting playerAdditionalReports
     *
     * @return json
     */
    public function playerAdditionalReports() {
        $is_export = true;
        $funcName='player_additionl_report';
        $callerType=Queue_result::CALLER_TYPE_SYSTEM;
        $caller=0;
        $state='';
        $viewPlayerInfoPerm = $this->permissions->checkPermissions('player_contact_information_email');
        $extra_params=[self::HTTP_REQEUST_PARAM, $viewPlayerInfoPerm, $is_export];
        $rlt=$this->exportData($funcName, $extra_params, $callerType, $caller, $state);

        if($rlt['success']){
            redirect($rlt['link']);
        }else{
            return show_error(lang('Export failed'));
        }
    }

	/**
	 * detail: getting shopping point report
	 *
	 * @return json
	 */
	public function shopping_point_report() {
		$is_export = true;
		$funcName='shopping_point_report';
		$callerType=Queue_result::CALLER_TYPE_SYSTEM;
		$caller=0;
		$state='';
		$viewPlayerInfoPerm = $this->permissions->checkPermissions('shopping_center_manager');
		$extra_params=[self::HTTP_REQEUST_PARAM, $viewPlayerInfoPerm, $is_export];
		$rlt=$this->exportData($funcName, $extra_params, $callerType, $caller, $state);

		if($rlt['success']){
			redirect($rlt['link']);
		}else{
			return show_error(lang('Export failed'));
		}
	}

	/**
	 * detail: get player list reports
	 *
	 * @return json or void
	 */
	public function player_list_reports() {

        $is_export = true;
		$permissions=$this->getContactPermissions();
		$permissions['player_cpf_number'] = $this->permissions->checkPermissions('player_cpf_number');

		$funcName='player_list_reports';
		$callerType=Queue_result::CALLER_TYPE_SYSTEM;
		$caller=0;
		$state='';

		$extra_params=[self::HTTP_REQEUST_PARAM, $permissions, $is_export];

		$rlt=$this->exportData($funcName, $extra_params, $callerType, $caller, $state);

		if($rlt['success']){
			redirect($rlt['link']);
		}else{
			//return error
			return show_error(lang('Export failed'));
		}
	}

    public function player_analysis_report() {
        $is_export = true;
        $permissions=$this->getContactPermissions();

        $funcName='player_analysis_report';
        $callerType=Queue_result::CALLER_TYPE_SYSTEM;
        $caller=0;
        $state='';

        $extra_params=[self::HTTP_REQEUST_PARAM, $permissions, $is_export];

        $rlt=$this->exportData($funcName, $extra_params, $callerType, $caller, $state);

        if($rlt['success']){
            redirect($rlt['link']);
        }else{
            //return error
            return show_error(lang('Export failed'));
        }
    }

	public function transaction_details($playerId=null) {
		$playerId=($playerId=='null') ? null : $playerId ;
        $is_export = true;

		$funcName='transaction_details';
		$callerType=Queue_result::CALLER_TYPE_SYSTEM;
		$caller=0;
		$state='';
		$extra_params=[$playerId, self::HTTP_REQEUST_PARAM, $is_export];

		$rlt=$this->exportData($funcName, $extra_params, $callerType, $caller, $state);

		if($rlt['success']){
			redirect($rlt['link']);
		}else{
			//return error
			return show_error(lang('Export failed'));
		}
	}

	public function balance_transaction_details($playerId=null) {
		$playerId=($playerId=='null') ? null : $playerId ;
        $is_export = true;

		$funcName='balance_transaction_details';
		$callerType=Queue_result::CALLER_TYPE_SYSTEM;
		$caller=0;
		$state='';
		$extra_params=[$playerId, self::HTTP_REQEUST_PARAM, $is_export];

		$rlt=$this->exportData($funcName, $extra_params, $callerType, $caller, $state);

		if($rlt['success']){
			redirect($rlt['link']);
		}else{
			//return error
			return show_error(lang('Export failed'));
		}
	}

	/**
	 * detail: get promotion reports
	 *
	 * @return json
	 */
	public function promotion_report() {
		$is_export = true;
		$funcName='promotionReport';
		$callerType=Queue_result::CALLER_TYPE_SYSTEM;
		$caller=0;
		$state='';
		$extra_params=[self::HTTP_REQEUST_PARAM, $is_export];

		$rlt=$this->exportData($funcName, $extra_params, $callerType, $caller, $state);

		if($rlt['success']){
			redirect($rlt['link']);
		}else{
			//return error
			return show_error(lang('Export failed'));
		}
	}

	/**
	 * detail: get duplicate account reports
	 *
	 * @return json
	 */
	public function duplicate_account_report() {
		$this->load->model(array('report_model'));

		$request = $this->input->post();
		$result = $this->report_model->duplicateAccountTotal($request, 'for_export');

		$d = new DateTime();
		$filename = "duplicate_account_report-{$d->format('Ymd-Hi')}" . sprintf('-%03x', mt_rand(0, 0xfff));
		$link = $this->utils->create_excel($result, $filename, TRUE);
		//return file link
		$rlt = array('success' => true, 'link' => $link);
		$this->utils->recordAction(lang('export_data'), $this->router->fetch_method(), $filename);

		$this->returnJsonResult($rlt);
	}

	/**
	 * detail: get game report for a certain player
	 *
	 * @param int $player_id
	 * @return json
	 */
	public function game_report($playerId = 'null', $isCsv = 'false') {
		$playerId=($playerId=='null') ? null : $playerId ;
		$is_export = true;

		$funcName='gameReports';
		$callerType=Queue_result::CALLER_TYPE_SYSTEM;
		$caller=0;
		$state='';
		$permissions=$this->getContactPermissions();
		$extra_params=[self::HTTP_REQEUST_PARAM,$playerId, $is_export, $permissions];

		$rlt=$this->exportData($funcName, $extra_params, $callerType, $caller, $state);

		if($rlt['success']){
			redirect($rlt['link']);
		}else{
			//return error
			return show_error(lang('Export failed'));
		}
	}

	/**
	 * detail: get game report timezone for a certain player
	 *
	 * @param int $player_id
	 * @return json
	 */
	public function game_report_timezone($playerId = 'null', $isCsv = 'false') {
		$playerId=($playerId=='null') ? null : $playerId ;
		$is_export = true;

		$funcName='gameReportsTimezone';
		$callerType=Queue_result::CALLER_TYPE_SYSTEM;
		$caller=0;
		$state='';

		$extra_params=[self::HTTP_REQEUST_PARAM,$playerId, $is_export];

		$rlt=$this->exportData($funcName, $extra_params, $callerType, $caller, $state);

		if($rlt['success']){
			redirect($rlt['link']);
		}else{
			//return error
			return show_error(lang('Export failed'));
		}
	}

	public function export_game_report_from_aff() {
		$is_export = true;

		$funcName='gameReports';
		$callerType=Queue_result::CALLER_TYPE_SYSTEM;
		$caller=0;
		$state='';
		$playerId =null;

		$extra_params=[self::HTTP_REQEUST_PARAM,$playerId, $is_export];

		$rlt=$this->exportData($funcName, $extra_params, $callerType, $caller, $state);

		if($rlt['success']){
			redirect($rlt['link']);
		}else{
			//return error
			return show_error(lang('Export failed'));
		}
	}

	/**
	 * detail: get Dedicated And Additional Domains Report
	 *
	 * @return json or void
	 */
	public function dedicated_additional_domains_report() {

		$json=$this->input->post('json_search');
		$forExportJson =$this->utils->decodeJson($json);
		$d = new DateTime();
		$link =  $this->utils->create_csv($forExportJson, 'dedicated_domain_' . $d->format('Y_m_d_H_i_s') . '_' . rand(1, 9999));

     	$rlt = array('success' => true, 'link' => $link);
		$this->returnJsonResult($rlt);
	}

	/**
	 * detail: export json data from games_report page
	 *
	 * @return json
	 */
	public function game_report_results() {

		$json=$this->input->post('json_search');
		$forExportJson =$this->utils->decodeJson($json);
		$d = new DateTime();
		$link =  $this->utils->create_csv($forExportJson, 'game_report_' . $d->format('Y_m_d_H_i_s') . '_' . rand(1, 9999));

     	$rlt = array('success' => true, 'link' => $link);
		$this->returnJsonResult($rlt);
	}

	/**
	 * detail: export json data from  active player report page
	 *
	 * @return json
	 */
	public function active_player_report_results() {

		$json=$this->input->post('json_search');
		$forExportJson =$this->utils->decodeJson($json);
		$d = new DateTime();
		$link =  $this->utils->create_csv($forExportJson, 'active_player_report_' . $d->format('Y_m_d_H_i_s') . '_' . rand(1, 9999));

     	$rlt = array('success' => true, 'link' => $link);
		$this->returnJsonResult($rlt);
	}

	/**
	 * detail: export json data from  adminusers
	 *
	 * @return json
	 */
	public function adminusers_results() {

		$json=$this->input->post('json_search');
		$forExportJson =$this->utils->decodeJson($json);
		$d = new DateTime();
		$link =  $this->utils->create_csv($forExportJson, 'adminusers_' . $d->format('Y_m_d_H_i_s') . '_' . rand(1, 9999));

		$rlt = array('success' => true, 'link' => $link);
		$this->returnJsonResult($rlt);
	}

	/**
	 * detail: get cashback reports
	 *
	 * @return json
	 */
	public function cashback_report() {
		$is_export = true;
		$funcName='cashbackReport';
		$callerType=Queue_result::CALLER_TYPE_SYSTEM;
		$caller=0;
		$state='';
		$extra_params=[self::HTTP_REQEUST_PARAM, $is_export];

		$rlt=$this->exportData($funcName, $extra_params, $callerType, $caller, $state);

		if($rlt['success']){
			redirect($rlt['link']);
		}else{
			//return error
			return show_error(lang('Export failed'));
		}
	}

    /**
     * detail: get recalculate recalculate_cashback_report
     *
     * @return json
     */
    public function recalculate_cashback_report() {
        $is_export = true;
        $funcName='getRecalculateCashbackReport';
        $callerType=Queue_result::CALLER_TYPE_SYSTEM;
        $caller=0;
        $state='';
        $extra_params=[self::HTTP_REQEUST_PARAM, $is_export];

        $rlt=$this->exportData($funcName, $extra_params, $callerType, $caller, $state);

        if($rlt['success']){
            redirect($rlt['link']);
        }else{
            //return error
            return show_error(lang('Export failed'));
        }
    }

    /**
     * detail: get withdraw_condition_deduction_report
     *
     * @return json
     */
    public function withdraw_condition_deduction_report() {
        $is_export = true;
        $funcName='getWcDeductionProcessReport';
        $callerType=Queue_result::CALLER_TYPE_SYSTEM;
        $caller=0;
        $state='';
        $extra_params=[self::HTTP_REQEUST_PARAM, $is_export];

        $rlt=$this->exportData($funcName, $extra_params, $callerType, $caller, $state);

        if($rlt['success']){
            redirect($rlt['link']);
        }else{
            //return error
            return show_error(lang('Export failed'));
        }
    }

    /**
     * detail: get recalculate withdraw_condition_deduction_report
     *
     * @return json
     */
    public function recalculate_withdraw_condition_deduction_report() {
        $is_export = true;
        $funcName='getRecalculteWcDeductionProcessReport';
        $callerType=Queue_result::CALLER_TYPE_SYSTEM;
        $caller=0;
        $state='';
        $extra_params=[self::HTTP_REQEUST_PARAM, $is_export];

        $rlt=$this->exportData($funcName, $extra_params, $callerType, $caller, $state);

        if($rlt['success']){
            redirect($rlt['link']);
        }else{
            //return error
            return show_error(lang('Export failed'));
        }
    }

	/**
	 * detail: get transactions_daily_summary_report
	 *
	 * @return json
	 */
	public function transactions_daily_summary_report() {
		$is_export = true;
		$funcName='transactionsDailySummaryReport';
		$callerType=Queue_result::CALLER_TYPE_SYSTEM;
		$caller=0;
		$state='';
		$extra_params=[self::HTTP_REQEUST_PARAM, $is_export];

		$rlt=$this->exportData($funcName, $extra_params, $callerType, $caller, $state);

		if($rlt['success']){
			redirect($rlt['link']);
		}else{
			//return error
			return show_error(lang('Export failed'));
		}
	}

	/**
	 * detail: get affiliate statics
	 *
	 * @return json
	 */
	public function affiliate_statistics() {
        $is_export = true;

        $funcName='affiliateStatistics';
        $callerType=Queue_result::CALLER_TYPE_SYSTEM;
        $caller=0;
        $state='';

        $extra_params=[self::HTTP_REQEUST_PARAM, $is_export];

        $rlt=$this->exportData($funcName, $extra_params, $callerType, $caller, $state);

        if($rlt['success']){
            redirect($rlt['link']);
        }else{
            return show_error(lang('Export failed'));
        }
	}
	/**
	 * detail: get affiliate statics 2
	 *
	 * @return json
	 */
	public function affiliate_statistics2() {
        $is_export = true;

        $funcName='affiliateStatistics2';
        $callerType=Queue_result::CALLER_TYPE_SYSTEM;
        $caller=0;
        $state='';

        $extra_params=[self::HTTP_REQEUST_PARAM, $is_export];

        $rlt=$this->exportData($funcName, $extra_params, $callerType, $caller, $state);

        if($rlt['success']){
            redirect($rlt['link']);
        }else{
            return show_error(lang('Export failed'));
        }
	}

	/**
	* detail: get affiliate traffic statics
	*
	* @return json
	*/
	public function export_affiliate_traffic_statistics() {
		//$request = $this->input->post();
		$this->load->library('data_tables');
		$request=$this->input->post('json_search');
		$request=$this->utils->decodeJson($request);
		$extrainfo = $this->data_tables->extra_search($request);

		$search = array(
			"by_date_from" => isset($extrainfo['by_date_from']) ? $extrainfo['by_date_from'] : null,
			"by_date_to" => isset($extrainfo['by_date_to']) ? $extrainfo['by_date_to'] : null,
			"by_affiliate_username" => isset($extrainfo['by_affiliate_username']) ? $extrainfo['by_affiliate_username'] : null,
			"by_banner_name" => isset($extrainfo['by_banner_name']) ? $extrainfo['by_banner_name'] : null,
			"by_tracking_code" => isset($extrainfo['by_tracking_code']) ? $extrainfo['by_tracking_code'] : null,
			"by_tracking_source_code" => isset($extrainfo['by_tracking_source_code']) ? $extrainfo['by_tracking_source_code'] : null,
			"by_type" => isset($extrainfo['by_type']) ? $extrainfo['by_type'] : null,
			"registrationWebsite" => isset($extrainfo['registrationWebsite']) ? $extrainfo['registrationWebsite'] : null,
			"remarks" => isset($extrainfo['remarks']) ? $extrainfo['remarks'] : null,
		);

		$is_export = true;
		$d = new DateTime();
		$result = $this->report_model->affiliate_traffic_statistics($request, $is_export);
		$link =  $this->utils->create_csv($result, 'affiliate_traffic_statistics_' . $d->format('Y_m_d_H_i_s') . '_' . rand(1, 9999));
		$rlt = array('success' => true, 'link' => $link);


		if($this->utils->isEnabledFeature('export_excel_on_queue')){
			$link=site_url($link);
			redirect($link);
		} else {
			$rlt = array('success' => true, 'link' => $link);
			$this->returnJsonResult($rlt);
		}
	}

	/**
	 * detail: get affiliate earnings
	 *
	 * @return json
	 */
	public function affiliate_earnings() {
		$is_export = true;
		if($this->utils->isEnabledFeature('switch_to_affiliate_daily_earnings')){
            $funcName='aff_daily_earnings';
        }else{
            $funcName='aff_monthly_earnings';
        }

        $callerType=Queue_result::CALLER_TYPE_SYSTEM;
        $caller=0;
        $state='';
        $extra_params=[self::HTTP_REQEUST_PARAM, $is_export];
        $rlt=$this->exportData($funcName, $extra_params, $callerType, $caller, $state);

        if($rlt['success']){
            redirect($rlt['link']);
        }else{
            return show_error(lang('Export failed'));
        }
	}

	/**
	 * detail: get affiliate partners
	 *
	 * @return json
	 */
	public function affiliatePartners() {
		$is_export = true;
		$funcName='affiliate_partners';
        $callerType=Queue_result::CALLER_TYPE_SYSTEM;
        $caller=0;
        $state='';
        $extra_params=[self::HTTP_REQEUST_PARAM, $is_export];
        $rlt=$this->exportData($funcName, $extra_params, $callerType, $caller, $state);

        if($rlt['success']){
            redirect($rlt['link']);
        }else{
            return show_error(lang('Export failed'));
        }
	}

	/**
	 * detail: get affiliate lists
	 *
	 * @return json
	 */
	public function aff_list() {
		$is_export = true;
		$funcName='aff_list';
		$callerType=Queue_result::CALLER_TYPE_SYSTEM;
		$caller=0;
		$state='';
		$allowed_affiliate_contact_info = $this->permissions->checkPermissions('affiliate_contact_info');
		$allowed_affiliate_tag = $this->permissions->checkPermissions('affiliate_tag');
		$permissions = array('affiliate_contact_info' => $allowed_affiliate_contact_info,
			'affiliate_tag' => $allowed_affiliate_tag);

		$extra_params=[self::HTTP_REQEUST_PARAM, $is_export, $permissions];

		$rlt=$this->exportData($funcName, $extra_params, $callerType, $caller, $state);

		if($rlt['success']){
			redirect($rlt['link']);
		}else{
			//return error
			return show_error(lang('Export failed'));
		}
	}

	/**
	 * detail: get payment reports
	 *
	 * @return json
	 */
	public function payment_report() {
        $is_export = true;
		$funcName='payment_report';
		$callerType=Queue_result::CALLER_TYPE_SYSTEM;
		$caller=0;
		$state='';
		$extra_params=[self::HTTP_REQEUST_PARAM, $is_export];

		$rlt=$this->exportData($funcName, $extra_params, $callerType, $caller, $state);

		if($rlt['success']){
			redirect($rlt['link']);
		}else{
			//return error
			return show_error(lang('Export failed'));
		}
	}

    /**
     * detail: get payment status history reports
     *
     * @return json
     */
    public function payment_status_history_report() {

        $this->load->model(array('report_model'));

        $request = $this->input->post();
        $is_export = true;
        $result = $this->report_model->payment_status_history_report($request, $is_export);

        $d = new DateTime();
        $link = $this->utils->create_excel($result, 'payment_status_history_report_' . $d->format('Y_m_d_H_i_s') . '_' . rand(1, 9999), TRUE);
        $rlt = array('success' => true, 'link' => $link);

        $this->returnJsonResult($rlt);
    }

	/**
	 * detail: get game description lists
	 *
	 * @return json
	 */
	public function gameDescriptionList() {
		$request = $this->input->post();
		$this->load->model(array('report_model'));

		$is_export = true;
	    $result = $this->report_model->gameDescriptionList($request, $is_export);
		$d = new DateTime();
		$link = $this->utils->create_excel($result, 'game_description_list_' . $d->format('Y_m_d_H_i_s') . '_' . rand(1, 9999), TRUE);
		//return file link
		$rlt = array('success' => true, 'link' => $link);
		$this->returnJsonResult($rlt);
	}

	/**
	 * detail: get traffic statictics affiliate
	 *
	 * @return json or void
	 */
	public function traffic_statistics_aff() {
		$affId = $this->getSessionAffId();
		$request = $this->input->post();
		$is_export = true;
		$d = new DateTime();
		$result = $this->report_model->traffic_statistics_aff($affId, $request, $is_export);
		$link =  $this->utils->create_csv($result, 'traffic_statistics_aff_' . $d->format('Y_m_d_H_i_s') . '_' . rand(1, 9999));
		$rlt = array('success' => true, 'link' => $link);
		$this->returnJsonResult($rlt);
	}

	/**
	 * detail: load title, description, keyword
	 *
	 * @param string $title
	 * @param string $description
	 * @param string $keywords
	 *
	 * @return load template
	 */
	protected function loadTemplate($title, $description, $keywords) {
		$this->template->write('title', $title);
		$this->template->write('description', $description);
		$this->template->write('keywords', $keywords);
	}

	/**
	 * detail: a simple queue page
	 *
	 * @param string $token
	 * @return loada template
	 */
	public function queue($token){
		$data['result_token']=$token;
		$this->loadTemplate(lang('Export Excel Progress'), '', 'export excel');

		$affId = $this->getSessionAffId();
		if(!empty($affId)){
			$this->template->write_view('nav_right', 'affiliate/navigation');
		}
		$this->load->model(['queue_result']);
		$result=$this->queue_result->getResult($token);

		$use_export_csv_with_progress_template =$this->utils->getConfig('use_export_csv_with_progress');
		$is_remote_export = substr($result['func_name'], 0, 6) == 'remote';


		$data['is_remote_export'] = $is_remote_export;
		$use_export_csv_with_progress = false;



		$funcName = $result['func_name'];

		if($this->utils->isAgencySubProject()) {
			if(in_array($funcName, $use_export_csv_with_progress_template['agency'])){
				$use_export_csv_with_progress =  true;
			}

		}elseif($this->utils->isAdminSubProject()) {
			if(in_array($funcName, $use_export_csv_with_progress_template['admin'])){
				$use_export_csv_with_progress =  true;
			}
			//aff
		}else{
			if(in_array($funcName, $use_export_csv_with_progress_template['aff'])){
				$use_export_csv_with_progress =  true;
			}
		}

		$data['is_remote_export'] = $is_remote_export;
		if($is_remote_export || $use_export_csv_with_progress && $this->utils->getConfig('dt_use_fetch_all_on_csv_export')){
			$this->template->write_view('main_content', 'includes/export_csv_progress', $data);
		}else{
			$this->template->write_view('main_content', 'includes/export_excel_progress', $data);
		}

		$this->template->render();
	}

	public function stop_queue($token_to_stop){

		$this->load->library(['lib_queue']);
		$this->load->model(['queue_result']);
		$row=$this->queue_result->getResult($token_to_stop);
		if(substr($row['func_name'], 0, 6)=='remote'){
			$funcName='stop_queue';
			$callerType=Queue_result::CALLER_TYPE_SYSTEM;
			$caller=0;
			$state='';
			$params = ['token_to_stop'=> $token_to_stop];

			$token=$this->lib_queue->stopRemoteQueueJob($funcName, $params, $callerType, $caller, $state);
			$rlt=['success'=>true, 'done'=>false];
			$queue_result=$this->queue_result->getResult($token_to_stop);
			$rlt['done']=isset($queue_result['status']) ? $queue_result['status']==Queue_result::STATUS_STOPPED : false;
			$rlt['process_status']= $queue_result['status'];
			$rlt['token'] =$token ;
			$this->returnJsonResult($rlt);
		}
	}

	/**
	 * detail: checking queue
	 *
	 * @param string $token
	 * @return json
	 */
	public function check_queue($token){
		$rlt=['success'=>true, 'done'=>false];
		$this->load->model(['queue_result']);
		$queue_result=$this->queue_result->getResult($token);
		$rlt['queue_result']=$this->utils->decodeJson($queue_result['result']);
		$rlt['done']=isset($queue_result['status']) ? $queue_result['status']==Queue_result::STATUS_DONE : false;
		$rlt['process_status']= $queue_result['status'];
		$this->returnJsonResult($rlt);
	}

	/**
	 * detail: get deposits lists and export to excel
	 *
	 * @return json
	 */
	public function depositList($playerId='null', $isCsv = 'false'){

		$playerId=($playerId=='null') ? null : $playerId ;
        $is_export = true;
        $playerDetailPermissions=$this->getContactPermissions();
		$callerType=Queue_result::CALLER_TYPE_SYSTEM;
		$caller=0;
		$state='';
		$funcName='depositList';
		$customizationExternalId = $this->utils->getConfig('deposit_list_customization_external_id');

		$extra_params=[$playerId, self::HTTP_REQEUST_PARAM, $is_export, false, '', '', '', null, false ,$customizationExternalId, $playerDetailPermissions];

		$rlt=$this->exportData($funcName, $extra_params, $callerType, $caller, $state);


		if($rlt['success']){
			redirect($rlt['link']);
		}else{
			//return error
			return show_error(lang('Export failed'));
		}
	}
	/**
	 * detail: get withdraw Checking Report and export to excel
	 *
	 * @return json
	 */
	public function withdrawCheckingReport($isCsv = 'false') {
        $is_export = true;

        $status_permission = $this->getWithdrawalStatusPermissonFromExport();
		$callerType=Queue_result::CALLER_TYPE_SYSTEM;
		$caller=0;
		$state='';
		$funcName='withdrawCheckingReport';

		$extra_params=[self::HTTP_REQEUST_PARAM, $is_export, $status_permission];

		$rlt=$this->exportData($funcName, $extra_params, $callerType, $caller, $state);

		if($rlt['success']){
			redirect($rlt['link']);
		}else{
			//return error
			return show_error(lang('Export failed'));
		}
	}

	/**
	 * detail: get deposit Checking Report and export to excel
	 *
	 * @return json
	 */
	public function depositCheckingReport($isCsv = 'false') {
        $is_export = true;

		$callerType=Queue_result::CALLER_TYPE_SYSTEM;
		$caller=0;
		$state='';
		$funcName='depositCheckingReport';

		$extra_params=[self::HTTP_REQEUST_PARAM, $is_export];

		$rlt=$this->exportData($funcName, $extra_params, $callerType, $caller, $state);

		if($rlt['success']){
			redirect($rlt['link']);
		}else{
			//return error
			return show_error(lang('Export failed'));
		}
	}


	/**
	 * detail: get withdrawal lists and export to excel
	 *
	 * @return json
	 */
	public function withdrawList( $playerId=null, $enabledAction='false', $isCsv = 'false') {
		$enabledAction = $enabledAction=='true';
		$playerId=($playerId=='null') ? null : $playerId ;
        $is_export = true;
        $playerDetailPermissions=$this->getContactPermissions();
        $status_permission = $this->getWithdrawalStatusPermissonFromExport();

		$callerType=Queue_result::CALLER_TYPE_SYSTEM;
		$caller=0;
		$state='';
		$funcName='withdrawList';

		$extra_params=[$playerId, $enabledAction, self::HTTP_REQEUST_PARAM, $is_export, false, null, $status_permission, $playerDetailPermissions];
		
		$rlt=$this->exportData($funcName, $extra_params, $callerType, $caller, $state);

		if($rlt['success']){
			redirect($rlt['link']);
		}else{
			//return error
			return show_error(lang('Export failed'));
		}
	}

    /**
     * detail: get referral Promo Application lists and export to excel
     *
     * @return json
     */
    public function referralPromoApplicationList(){
        $is_export = true;

        $callerType=Queue_result::CALLER_TYPE_SYSTEM;
        $caller=0;
        $state='';
        $funcName='referralPromoApplicationList';

        $extra_params=[self::HTTP_REQEUST_PARAM, $is_export];

        $rlt=$this->exportData($funcName, $extra_params, $callerType, $caller, $state);


        if($rlt['success']){
            redirect($rlt['link']);
        }else{
            //return error
            return show_error(lang('Export failed'));
        }
    }

    /**
     * detail: get hugebet referral Promo Application lists and export to excel
     *
     * @return json
     */
    public function hugebetReferralPromoApplicationList(){
        $is_export = true;

        $callerType=Queue_result::CALLER_TYPE_SYSTEM;
        $caller=0;
        $state='';
        $funcName='hugebetReferralPromoApplicationList';

        $extra_params=[self::HTTP_REQEUST_PARAM, $is_export];

        $rlt=$this->exportData($funcName, $extra_params, $callerType, $caller, $state);


        if($rlt['success']){
            redirect($rlt['link']);
        }else{
            //return error
            return show_error(lang('Export failed'));
        }
    }

	/**
	 * detail: get Promo Application lists and export to excel
	 *
	 * @return json
	 */
	public function promoApplicationList(){
        $is_export = true;

		$callerType=Queue_result::CALLER_TYPE_SYSTEM;
		$caller=0;
		$state='';
		$funcName='promoApplicationList';

		$extra_params=[self::HTTP_REQEUST_PARAM, $is_export];

		$rlt=$this->exportData($funcName, $extra_params, $callerType, $caller, $state);


		if($rlt['success']){
			redirect($rlt['link']);
		}else{
			//return error
			return show_error(lang('Export failed'));
		}
	}

	/**
	 * detail: get affiliate earnings
	 *
	 * @return json
	 */
	public function smsVerificationCodeReport() {
		$this->load->model(array('sms_verification'));

		$request = $this->input->post();
		$is_export = true;
		$result = $this->sms_verification->listVerificationCodes($request,$is_export);

		$d = new DateTime();
		$filename = 'sms_verification_code_' . $d->format('Y_m_d_H_i_s') . '_' . rand(1, 9999);
		$link = $this->utils->create_excel($result, $filename, TRUE);
		//return file link
		$rlt = array('success' => true, 'link' => $link);
		$this->utils->recordAction(lang('export_data'), $this->router->fetch_method(), $filename);

		$this->returnJsonResult($rlt);
	}

	/**
	 * detail: get Affiliate tags lists and export to excel
	 *
	 * @return json
	 */
	public function affiliateTag(){

		$request = $this->input->post();
		$viewPlayerInfoPerm = $this->permissions->checkPermissions('player_contact_information_email');
		$viewPlayerInfoCn = $this->permissions->checkPermissions('player_contact_information_contact_number');
		$is_export = true;

		$this->load->library('affiliate_manager');

		$sort = "tagId";
		$tags = $this->affiliate_manager->getAllTags($sort, null, null);

		$result = array();

		$result['header_data'] = array(
			lang('aff.t02'),
			lang('aff.t04'),
			lang('player.tm09'),
			lang('aff.t06')
		);

		$result['data'] = array();

		foreach ($tags as $key => $value) {
			$result['data'][] = array(
				'tagName' => $value['tagName'],
				'tagDescription' => $value['tagDescription'],
				'tagColor' => $value['tagColor'],
				'username' => $value['username'],
			);
		}

		$d = new DateTime();
		$link = $this->utils->create_excel($result, 'affiliate_tag_' . $d->format('Y_m_d_H_i_s') . '_' . rand(1, 9999), TRUE);
		//return file link
		$rlt = array('success' => true, 'link' => $link);
		$this->returnJsonResult($rlt);
	}

	/**
	 * detail: get Game logs lists and export to excel
	 *
	 * @return json
	 */
	public function gamesHistory($playerId=null){
		$is_export = true;
		$funcName='gamesHistory';
		$callerType=Queue_result::CALLER_TYPE_SYSTEM;
		$caller=0;
		$state='';

		$extra_params=[self::HTTP_REQEUST_PARAM, $playerId, $is_export];

		$rlt=$this->exportData($funcName, $extra_params, $callerType, $caller, $state);

		if($rlt['success']){
			redirect($rlt['link']);
		}else{
			//return error
			return show_error(lang('Export failed'));
		}
	}

	/**
	 * detail: get Affiliate payment lists and export to excel
	 *
	 * @return json
	 */
	public function affiliatePayment(){
		$request = $this->input->post();
		$viewPlayerInfoPerm = $this->permissions->checkPermissions('player_contact_information_email');
		$viewPlayerInfoCn = $this->permissions->checkPermissions('player_contact_information_contact_number');
		$is_export = true;

		$this->load->model(array('affiliatemodel'));
		$this->load->library('data_tables');

		$extrainfo = $this->data_tables->extra_search($request);

		$search = array(
			"username" => isset($extrainfo['username']) ? $extrainfo['username'] : null,
			"status" => isset($extrainfo['status']) ? $extrainfo['status'] : null,
		);

		if (isset($extrainfo['start_date'], $extrainfo['end_date'])) {
			$search['request_range'] = "'" . $extrainfo['start_date'] . date(' H:i:s',mktime(00,00,00)) . "' AND '" . $extrainfo['end_date'] . date(' H:i:s',mktime(23,59,59)) . "'";
		} else {
			$search['request_range'] = "'" . date("Y-m-d 00:00:00", strtotime('-1 month')) . "' AND '" . date("Y-m-d 23:59:59") . "'";
		}

		$data = $this->affiliatemodel->getSearchPayment(null, null, $search);

		$result = array();
		$result['header_data'] = array(
			lang('Date'),
			lang('Affiliate Username'),
			lang('Bank'),
			lang('Amount'),
			lang('Processed Date'),
			lang('Processed By'),
			lang('lang.status'),
			lang('aff.apay11')
		);

		$result['data'] = array();
		foreach ($data as $key => $value) {
			switch ( $value['status'] ) {
				case 1:
					$status = lang('Request');
					break;
				case 2:
					$status = lang('Approved');
					break;
				case 3:
					$status = lang('Declined');
					break;
				default:
					$status = '';
					break;
			}

			$result['data'][] = array(
				'createdOn' => $value['createdOn'],
				'username' => $value['username'],
				'bankName' => lang($value['bankName']),
				'amount' => $value['amount'],
				'processedOn' => $value['processedOn'],
				'adminuser' => $value['adminuser'],
				'status' => $status,
				'reason' => $value['reason'],
			);
		}

		$d = new DateTime();
		$link = $this->utils->create_excel($result, 'affiliate_payment_' . $d->format('Y_m_d_H_i_s') . '_' . rand(1, 9999), TRUE);
		//return file link
		$rlt = array('success' => true, 'link' => $link);
		$this->returnJsonResult($rlt);
	}

	/**
	 * detail: get Tagged Player lists and export to excel
	 *
	 * @return json
	 */
	public function taggedPlayers(){

		$request = $this->input->post();
		$viewPlayerInfoPerm = $this->permissions->checkPermissions('player_contact_information_email');
		$viewPlayerInfoCn = $this->permissions->checkPermissions('player_contact_information_contact_number');
		$is_export = true;

		$this->load->library(array('player_manager'));

		if ($this->session->userdata('black_sort_by')) {
			$black_sort_by = $this->session->userdata('black_sort_by');
		} else {
			$black_sort_by = 'createdOn';
		}

		if ($this->session->userdata('black_in')) {
			$black_in = $this->session->userdata('black_in');
		} else {
			$black_in = 'desc';
		}

		$data = $this->player_manager->getBlacklist($black_sort_by, $black_in, null, null);

		$result = array();
		$result['header_data'] = array(
				lang('player.01'),
				lang('player.40'),
				lang('player.39'),
				lang('player.06'),
				lang('player.20'),
				lang('player.41'),
				lang('player.42'),
				lang('player.43'),
				lang('lang.status')
			);

		$result['data'] = array();

		foreach ($data as $key => $value) {
			$name = $value['lastName'] . " " . $value['firstName'];
			$result['data'][] = array(
				'username' => $value['username'],
				'name' => ($value['lastName'] == '') && ($value['firstName'] == '') ? lang('lang.norecyet') : $name,
				'level' => ($value['groupName'] == '') ? lang('lang.norecyet') : lang($value['groupName']) . ' ' . lang($value['vipLevel']),
				'email_address' => ( $value['email'] == '' ) ? lang('lang.norecyet') : $value['email'],
				'country' => ( $value['country'] == '' ) ? lang('lang.norecyet') : $value['country'],
				'tag' => ( $value['tagName'] == '' ) ? lang('lang.norecyet') : $value['tagName'],
				'last_login_date' => ( $value['lastLoginTime'] == '' ) ? lang('lang.norecyet') : $value['lastLoginTime'],
				'registered_date' => ( $value['createdOn'] == '' ) ? lang('lang.norecyet') : $value['createdOn'],
				'status' => ( $value['status'] == 0 ) ? lang('player.14') : lang('player.15')
			);
		}

		$d = new DateTime();
		$link = $this->utils->create_csv($result, 'tagged_players_' . $d->format('Y_m_d_H_i_s') . '_' . rand(1, 9999), TRUE);
		//return file link
		$rlt = array('success' => true, 'link' => $link);
		$this->returnJsonResult($rlt);
	}

	/**
	 * detail: get Player Tag Management lists and export to excel
	 *
	 * @return json
	 */
	public function playerTagManagement(){

		$request = $this->input->post();
		$viewPlayerInfoPerm = $this->permissions->checkPermissions('player_contact_information_email');
		$viewPlayerInfoCn = $this->permissions->checkPermissions('player_contact_information_contact_number');
		$is_export = true;

		$this->load->library(array('player_manager'));
		$sort = "tagId";

		$data = $this->player_manager->getTags($sort, null, null);

		$result = array();

		$result['header_data'] = array(
			lang('player.tm02'),
			lang('player.tm04'),
			lang('cms.createdby')
		);

        if($this->utils->getConfig('enable_wdremark_in_tag_management')){
            array_push($result['header_data'], lang('player.tm10'));
        }

		$result['data'] = array();

		foreach ($data as $key => $value) {
			$result['data'][$key] = array(
				'tagName' => $value['tagName'],
				'tagDescription' => ( $value['tagDescription'] ) ? $value['tagDescription'] : lang('player.tm06'),
				'username' => $value['username']
			);

            if($this->utils->getConfig('enable_wdremark_in_tag_management')){
                $wdRemark = !empty($value['wdRemark']) ? $value['wdRemark'] : lang('N/A');
               array_push($result['data'][$key], $wdRemark);
            }
		}

		$d = new DateTime();
		$link = $this->utils->create_csv($result, 'player_tag_management_' . $d->format('Y_m_d_H_i_s') . '_' . rand(1, 9999));

		$rlt = array('success' => true, 'link' => $link);
		$this->returnJsonResult($rlt);
	}

	/**
	 * detail: get Batch Create lists and export to excel
	 *
	 * @return json
	 */
	public function batchCreate(){

		$request = $this->input->post();
		$viewPlayerInfoPerm = $this->permissions->checkPermissions('player_contact_information_email');
		$viewPlayerInfoCn = $this->permissions->checkPermissions('player_contact_information_contact_number');
		$is_export = true;

		$this->load->library(array('player_manager'));
		$data = $this->player_manager->getBatchAccount(null, null);

		$result = array();

		$result['header_data'] = array(
			lang('Player Username'),
			lang('player.mp03'),
			lang('player.mp04')
		);

		$result['data'] = array();

		foreach ($data as $key => $value) {
			$result['data'][] = array(
				'name' => $value['name'],
				'count' => $value['count'],
				'description' => ($value['description'] == null) ? lang('player.mp05') : $value['description']
			);
		}

		$d = new DateTime();
		$link = $this->utils->create_csv($result, 'batch_create_' . $d->format('Y_m_d_H_i_s') . '_' . rand(1, 9999), TRUE);
		//return file link
		$rlt = array('success' => true, 'link' => $link);
		$this->returnJsonResult($rlt);
	}

	/**
	 * detail: get Friend referrals lists and export to excel
	 *
	 * @return json
	 */
	public function friendReferral(){

		$funcName='friendReferral';
		$callerType=Queue_result::CALLER_TYPE_SYSTEM;
		$caller=0;
		$state='';
		$is_export = true;

		$extra_params=[self::HTTP_REQEUST_PARAM,$is_export];

		$rlt=$this->exportData($funcName, $extra_params, $callerType, $caller, $state);

		if($rlt['success']){
			redirect($rlt['link']);
		}else{
			//return error
			return show_error(lang('Export failed'));
		}
	}

	/**
	 * detail: get Friend referrals lists and export to excel
	 *
	 * @return json
	 */
	public function ipTagList(){

		$funcName='ipTagList';
		$callerType=Queue_result::CALLER_TYPE_SYSTEM;
		$caller=0;
		$state='';
		$is_export = true;

		$extra_params=[self::HTTP_REQEUST_PARAM,$is_export];

		$rlt=$this->exportData($funcName, $extra_params, $callerType, $caller, $state);

		if($rlt['success']){
			redirect($rlt['link']);
		}else{
			//return error
			return show_error(lang('Export failed'));
		}
	}

	/**
	 * detail: get Bank payment lists and export to excel
	 *
	 * @return json
	 */
	public function bankPaymentList(){

		$request = $this->input->post();
		$viewPlayerInfoPerm = $this->permissions->checkPermissions('player_contact_information_email');
		$viewPlayerInfoCn = $this->permissions->checkPermissions('player_contact_information_contact_number');
		$is_export = true;

		$this->load->model(array('banktype', 'financial_account_setting'));

		$data = $this->banktype->getAllBanktype();

		$result = array();
		$result['header_data'] = array(
				lang('column.id'),
				lang('pay.bt.bankname'),
				lang('Bank Code'),
				lang('pay.bt.payment_api_id'),
				lang('Bank/3rd Payment Type'),
				lang('report.p07'),
				lang('report.p06'),
				lang('pay.bt.createdon'),
				lang('pay.bt.updatedon'),
				lang('pay.bt.createdby'),
				lang('pay.bt.updatedby'),
				lang('pay.bt.status')
			);

		$result['data'] = array();

		$payment_type_flags = $this->utils->insertEmptyToHeader($this->financial_account_setting->getPaymentTypeAllFlagsKV(), '', lang('select.empty.line'));

		foreach ($data as $key => $value) {

			switch ($value['status']) {
				case Banktype::STATUS_ACTIVE:
					$status = lang('lang.active');
					break;

				default:
					$status = lang('Blocked');
					break;
			}

			$result['data'][] = array(
				'bankTypeId' => $value['bankTypeId'],
				'bankName' => lang($value['bankName']),
				'bankCode' => $value['bank_code'],
				'external_system_id' => ($value['external_system_id']) ? $value['external_system_id'] : lang('lang.norecyet'),
				'bank/3rd_payment_type' => $payment_type_flags[$value['payment_type_flag']],
				'enabled_withdrawal' => ($value['enabled_withdrawal']) ? lang('status.normal') : lang('status.disabled'),
				'enabled_deposit' => ($value['enabled_deposit']) ? lang('status.normal') : lang('status.disabled'),
				'createdOn' => $value['createdOn'],
				'updatedOn' => $value['updatedOn'],
				'createdByUsername' => $value['createdByUsername'],
				'updatedByUsername' => $value['updatedByUsername'],
				'status' => $status
			);
		}

		$d = new DateTime();
		$link = $this->utils->create_excel($result, 'bank_payment_list_' . $d->format('Y_m_d_H_i_s') . '_' . rand(1, 9999), TRUE);
		//return file link
		if($this->utils->isEnabledFeature('export_excel_on_queue')){
			$link=site_url($link);
			redirect($link);
		} else {
			$rlt = array('success' => true, 'link' => $link);
			$this->returnJsonResult($rlt);
		}
	}

	/**
	 * detail: get Transfer Request lists and export to excel
	 *
	 * @return json
	 */
	public function transferRequest($playerId=null){
        $permissions = array(
			'make_up_transfer_record' => $this->permissions->checkPermissions('make_up_transfer_record'),
		);
		$playerId=($playerId=='null') ? null : $playerId ;
        $is_export = true;

		$callerType=Queue_result::CALLER_TYPE_SYSTEM;
		$caller=0;
		$state='';
		$funcName='transferRequest';

		$extra_params=[$playerId, self::HTTP_REQEUST_PARAM, $permissions, $is_export];

		$rlt=$this->exportData($funcName, $extra_params, $callerType, $caller, $state);


		if($rlt['success']){
			redirect($rlt['link']);
		}else{
			//return error
			return show_error(lang('Export failed'));
		}
	}

	public function agency_logs(){
		$request = $this->input->post();
		$is_export = true;

		if($this->utils->isEnabledFeature('export_excel_on_queue')){
			$funcName='agency_get_logs';
			$callerType=Queue_result::CALLER_TYPE_SYSTEM;
			$caller=0;
			$state='';

			$extra_params=[self::HTTP_REQEUST_PARAM, $is_export];
			$rlt=$this->exportData($funcName, $extra_params, $callerType, $caller, $state);

			if($rlt['success']){
				redirect($rlt['link']);
			} else {
				return show_error(lang('Export failed'));
			}
		} else {
			$this->load->model(array('agency_model'));

	        $result = $this->agency_model->get_logs($request, $is_export);

	        $d = new DateTime();
			$link = $this->utils->create_excel($result, 'agency_logs_' . $d->format('Y_m_d_H_i_s') . '_' . rand(1, 9999), TRUE);

			$rlt = array('success' => true, 'link' => $link);
			$this->returnJsonResult($rlt);
		}
	}

	public function credit_transactions(){
		$request = $this->input->post();
		$is_export = true;

		if($this->utils->isEnabledFeature('export_excel_on_queue')){

			$permissions=  $this->permissions->checkPermissions('export_credit_transaction');

			$funcName='agency_get_transactions';
			$callerType=Queue_result::CALLER_TYPE_SYSTEM;
			$caller=0;
			$state='';

			$extra_params=[self::HTTP_REQEUST_PARAM, $permissions, $is_export];
			$rlt=$this->exportData($funcName, $extra_params, $callerType, $caller, $state);

			if($rlt['success']){
				redirect($rlt['link']);
			} else {
				//return error
				return show_error(lang('Export failed'));
			}
		} else {

			$this->load->model(array('agency_model'));
	        $request = $this->input->post();
	        $result = $this->agency_model->get_transactions($request, $is_export);

	        $d = new DateTime();
			$link = $this->utils->create_excel($result, 'credit_transactions_' . $d->format('Y_m_d_H_i_s') . '_' . rand(1, 9999), TRUE);
			//return file link
			$rlt = array('success' => true, 'link' => $link);
			$this->returnJsonResult($rlt);
		}
	}

    public function agent_list(){

		$request = $this->input->post();
		$is_export = true;

		if($this->utils->isEnabledFeature('export_excel_on_queue')){
			$permissions=  $this->permissions->checkPermissions('export_agent_list');

			$funcName='get_agent_list';
			$callerType=Queue_result::CALLER_TYPE_SYSTEM;
			$caller=0;
			$state='';

			$extra_params=[self::HTTP_REQEUST_PARAM, $permissions, $is_export];
			$rlt=$this->exportData($funcName, $extra_params, $callerType, $caller, $state);

			if($rlt['success']){
				redirect($rlt['link']);
			} else {
				//return error
				return show_error(lang('Export failed'));
			}
		}else{
			$this->load->model(array('agency_model'));
	        $result = $this->agency_model->get_agent_list($request, $is_export);

	        $d = new DateTime();
			$link = $this->utils->create_excel($result, 'agent_list_' . $d->format('Y_m_d_H_i_s') . '_' . rand(1, 9999), TRUE);
			//return file link
			$rlt = array('success' => true, 'link' => $link);
			$this->returnJsonResult($rlt);
		}
	}

    public function structure_list(){

		$request = $this->input->post();
		$is_export = true;

		if($this->utils->isEnabledFeature('export_excel_on_queue')){

			$permissions = $this->permissions->checkPermissions('export_agent_template_list');
			$funcName='get_structure_list';
			$callerType=Queue_result::CALLER_TYPE_SYSTEM;
			$caller=0;
			$state='';

			$extra_params=[self::HTTP_REQEUST_PARAM, $permissions, $is_export];
			$rlt=$this->exportData($funcName, $extra_params, $callerType, $caller, $state);
			if($rlt['success']){
				redirect($rlt['link']);
			} else {
				return show_error(lang('Export failed'));
			}
		}else{
			$this->load->model(array('agency_model'));
	        $result = $this->agency_model->get_structure_list($request, $is_export);

	        $d = new DateTime();
			$link = $this->utils->create_excel($result, 'structure_list_' . $d->format('Y_m_d_H_i_s') . '_' . rand(1, 9999), TRUE);
			$rlt = array('success' => true, 'link' => $link);
			$this->returnJsonResult($rlt);
		}
	}

	public function paymentAPI(){
		$request = $this->input->post();
		$viewPlayerInfoPerm = $this->permissions->checkPermissions('player_contact_information_email');
		$viewPlayerInfoCn = $this->permissions->checkPermissions('player_contact_information_contact_number');
		$is_export = true;

		$this->load->model('external_system');

		$gameApis = $this->external_system->getAllSystemPaymentApi();
		$data = json_decode(json_encode($gameApis), true);

		$result = array();
		$result['header_data'] = array(
			lang('sys.pay.systemid'),
			lang('sys.pay.systemname'),
			lang('sys.pay.status'),
			lang('sys.pay.note'),
			lang('sys.pay.lastsyncdt'),
			lang('sys.pay.lastsyncid'),
			lang('sys.pay.lastsyncdet'),
			lang('sys.pay.systemtype'),
			lang('sys.pay.liveurl'),
			lang('sys.pay.sandboxurl'),
			lang('sys.pay.livekey'),
			lang('sys.pay.livesecret'),
			lang('sys.pay.sandboxkey'),
			lang('sys.pay.sandboxsecret'),
			lang('sys.pay.livemode'),
			lang('sys.pay.secondurl'),
			lang('sys.pay.sandboxacct'),
			lang('sys.pay.liveacct'),
			lang('sys.pay.systemcode'),
			lang('sys.pay.classname'),
			lang('sys.pay.localpath'),
			lang('sys.pay.manager'),
			lang('sys.pay.extrainfo'),
			lang('sys.pay.sandboxextrainfo'),
			lang('pay.createdon')
		);

		$result['data'] = array();

		foreach ($data as $key => $value) {
			$result['data'][] = array(
				'id' => $value['id'],
				'system_name' => ($value['system_name'] != "") ? htmlspecialchars($value['system_name']) : "-",
				'status' => ( $value['status'] == 1 ) ? '✓' : '☓',
				'note' => ($value['note'] != "") ? $value['note'] : "-",
				'last_sync_datetime' => ($value['last_sync_datetime'] == "" || $value['last_sync_datetime'] == "0000-00-00 00:00:00") ? "-" : htmlspecialchars($value['last_sync_datetime']),
				'last_sync_id' => ($value['last_sync_id'] != "") ? htmlspecialchars($value['last_sync_id']) : "-",
				'last_sync_details' => ($value['last_sync_details'] != "") ? htmlspecialchars($value['last_sync_details']) : "-",
				'system_type' => ($value['system_type'] == 2) ? lang('sys.payment.api') : "-",
				'live_url' => ($value['live_url'] != "") ? htmlspecialchars($value['live_url']) : "-",
				'sandbox_url' => ($value['sandbox_url'] != "") ? htmlspecialchars($value['sandbox_url']) : "-",
				'live_key' => ($value['live_key'] != "") ? htmlspecialchars($value['live_key']) : "-",
				'live_secret' => ($value['live_secret'] != "") ? htmlspecialchars($value['live_secret']) : "-",
				'sandbox_key' => ($value['sandbox_key'] != "") ? htmlspecialchars($value['sandbox_key']) : "-",
				'sandbox_secret' => ($value['sandbox_secret'] != "") ? htmlspecialchars($value['sandbox_secret']) : "-",
				'live_mode' => ( $value['live_mode'] == 1 ) ? '✓' : '☓',
				'second_url' => ($value['second_url'] != "") ? htmlspecialchars($value['second_url']) : "-",
				'sandbox_account' => ($value['sandbox_account'] != "") ? htmlspecialchars($value['sandbox_account']) : "-",
				'live_account' => ($value['live_account'] != "") ? htmlspecialchars($value['live_account']) : "-",
				'system_code' => ($value['system_code'] != "") ? htmlspecialchars($value['system_code']) : "-",
				'class_name' => ($value['class_name'] != "") ? htmlspecialchars($value['class_name']) : "-",
				'local_path' => ($value['local_path'] != "") ? htmlspecialchars($value['local_path']) : "-",
				'manager' => ($value['manager'] != "") ? htmlspecialchars($value['manager']) : "-",
				'extra_info' => ($value['extra_info']) ? htmlspecialchars($value['extra_info']) : "-",
				'sandbox_extra_info' => ($value['sandbox_extra_info']) ? htmlspecialchars($value['sandbox_extra_info']) : "-",
				'created_on' => ($value['created_on'] == null) ? lang('N/A') : $value['created_on']
			);
		}

	  	$d = new DateTime();
		$link = $this->utils->create_excel($result, 'payment_api_' . $d->format('Y_m_d_H_i_s') . '_' . rand(1, 9999), TRUE);
		//return file link
		$rlt = array('success' => true, 'link' => $link);
		$this->returnJsonResult($rlt);
	}

	public function getAllGameType(){

		$request = $this->input->post();
		$viewPlayerInfoPerm = $this->permissions->checkPermissions('player_contact_information_email');
		$viewPlayerInfoCn = $this->permissions->checkPermissions('player_contact_information_contact_number');
		$is_export = true;

		$this->load->model(array('game_type_model'));
		$result = $this->game_type_model->getAllGameType($request, $is_export);

        $d = new DateTime();
		$link = $this->utils->create_excel($result, 'game_type_list_' . $d->format('Y_m_d_H_i_s') . '_' . rand(1, 9999), TRUE);
		//return file link
		$rlt = array('success' => true, 'link' => $link);
		$this->returnJsonResult($rlt);
	}

	public function export_png_freegame() {

		$request = $this->input->post();
		$is_export = true;

		$this->load->model(array('report_model'));
	    $result = $this->report_model->pngFreeGameOfferReport($request, null, $is_export);
	    $result['header_data'] = ['Request ID', 'External Request ID', 'Player Username', 'Games', 'Lines', 'Coins', 'Denomination', 'Rounds', 'Turnover', 'Expiration Time', 'Created At'];

        $d = new DateTime();
		$link = $this->utils->create_excel($result, 'png_freegame_offer_' . $d->format('Y_m_d_H_i_s') . '_' . rand(1, 9999), TRUE);
		//return file link
		$rlt = array('success' => true, 'link' => $link);
		$this->returnJsonResult($rlt);
	}

	/**
	 * The getAllGameTypeHistory export data for dataTable.
	 *
	 * @return void
	 */
	public function getAllGameTypeHistory(){
		$request = $this->input->post();

		$is_export = true;

		$this->load->model(array('game_type_model'));
		$result = $this->game_type_model->getAllGameTypeHistory($request, $is_export);

		$d = new DateTime();
		$link = $this->utils->create_excel($result, 'game_type_history_' . $d->format('Y_m_d_H_i_s') . '_' . rand(1, 9999), TRUE);
		//return file link
		$rlt = array('success' => true, 'link' => $link);
		$this->returnJsonResult($rlt);
	} // EOF getAllGameTypeHistory

	public function userLogs(){
		$is_export = true;

		$funcName='getUserLogs';
		$callerType=Queue_result::CALLER_TYPE_SYSTEM;
		$caller=0;
		$state='';

		$extra_params=[self::HTTP_REQEUST_PARAM,$is_export];

		$rlt=$this->exportData($funcName, $extra_params, $callerType, $caller, $state);

		if($rlt['success']){
			redirect($rlt['link']);
		}else{
			return show_error(lang('Export failed'));
		}
	}

	public function viewRoles(){

		$request = $this->input->post();
		// $viewPlayerInfoPerm = $this->permissions->checkPermissions('player_contact_information_email');
		// $viewPlayerInfoCn = $this->permissions->checkPermissions('player_contact_information_contact_number');
		$is_export = true;

		$user_id = $this->authentication->getUserId();
		$roleId = $this->users->getRoleIdByUserId($user_id);
		$admin_manage_user_roles = $this->permissions->checkPermissions('admin_manage_user_roles');

		$roles = $this->roles->getAllRoles($roleId, null, null, $user_id,$admin_manage_user_roles);

		$result = array();
		$result['header_data'] = array(
			lang('system.word5f'),
			lang('system.word58'),
			lang('system.word.active_roles'),
			lang('system.word59'),
			lang('system.word60'),
			lang('system.word61'),
			lang('isAdmin.short')
		);

		$result['data'] = array();

		foreach ($roles as $key => $value) {
			$user_count = $this->rolesfunctions->countUsersUsingRoles($value['roleId']);

			switch ($value['status']) {
				case 0:
					$status = lang('system.word62');
				break;
				case 1:
					$status = lang('system.word63');
				break;
				default:
					$status = lang('system.word64');
			}

			$activeRoles = $this->rolesfunctions->countActiveRolesByThisRoleId($value['roleId']);

			$result['data'][] = array(
				'role' => $value['roleName'],
				'amount' => $user_count,
				'active_roles' => $activeRoles,
				'register_time' => $value['createTime'],
				'created_by' => $value['createPerson'],
				'status' => $status,
				'admin_role' => ($value['isAdmin']) ? lang('lang.yes') : lang('lang.no'),
			);
		}

		$d = new DateTime();
		$link = $this->utils->create_excel($result, 'view_roles_' . $d->format('Y_m_d_H_i_s') . '_' . rand(1, 9999), TRUE);
		//return file link
		$rlt = array('success' => true, 'link' => $link);
		$this->returnJsonResult($rlt);
	}

	public function countryRules(){
		$request = $this->input->post();
		$viewPlayerInfoPerm = $this->permissions->checkPermissions('player_contact_information_email');
		$viewPlayerInfoCn = $this->permissions->checkPermissions('player_contact_information_contact_number');
		$is_export = true;

		$this->load->model(array('country_rules'));

		$data = $this->country_rules->getCountryRules(true);

		$result = array();
		$result['header_data'] = array(
			lang('Country Name'),
			lang('Country Code'),
			lang('sys.ip09'),
			lang('sys.ip10'),
			lang('sys.ip11'),
			lang('cashier.134')
		);

		$result['data'] = array();

		foreach ($data as $key => $value) {
			$result['data'][] = array(
				'country_name' => $value->country_name,
				'country_code' => $value->country_code,
				'created_at' => $value->created_at,
				'created_by' => $value->username,
				'flag' => ( $value->flag == 1 ) ? 'Allowed' : 'Blocked',
				'notes' => $value->notes,
			);
		}

        $d = new DateTime();
		$link = $this->utils->create_excel($result, 'country_rules_' . $d->format('Y_m_d_H_i_s') . '_' . rand(1, 9999), TRUE);
		//return file link
		$rlt = array('success' => true, 'link' => $link);
		$this->returnJsonResult($rlt);
	}

	public function exception_order_list($isCsv='false'){

		$isCsv= $isCsv=='true';
		$funcName='exception_order_list';

		if($this->utils->isEnabledFeature('export_excel_on_queue')){

			$this->load->library(['lib_queue']);
			$this->load->model(['queue_result']);

			$request=$this->input->post('json_search');
			$request=$this->utils->decodeJson($request);

			$callerType=Queue_result::CALLER_TYPE_SYSTEM;
			$caller=0;
			$state='';
			$params=[$request, $is_export];

			if($isCsv){
				$token=$this->lib_queue->addExportCsvJob($funcName, $params, $callerType, $caller, $state);
			}else{
				$token=$this->lib_queue->addExportExcelJob($funcName, $params, $callerType, $caller, $state);
			}

			$link=site_url('/export_data/queue/'.$token);

			redirect($link);
		}else{
			$this->load->model(array('report_model'));

			$result = $this->report_model->exception_order_list($request, $is_export);
			$d = new DateTime();

			if($isCsv){
				$link = $this->utils->create_csv($result, $funcName.'_' . $d->format('Y_m_d_H_i_s') . '_' . rand(1, 9999));
			}else{
				$link = $this->utils->create_excel($result, $funcName.'_' . $d->format('Y_m_d_H_i_s') . '_' . rand(1, 9999), TRUE, null, $isCsv);
			}
			//return file link
			$rlt = array('success' => true, 'link' => $link);
			$this->returnJsonResult($rlt);
		}
	}

	/**
	 * export report to excel
	 *
	 *
	 * @return	excel format
	 */
	public function export_vip_setting_list() {

		$this->load->library('excel');
		$this->load->model(array('group_level'));

		$sort = "groupName";
		$data =$this->group_level->getVIPSettingList($sort, null, null);

		$result['header_data'] = array(
				lang('player.grpname'),
				lang('player.grplvlcnt'),
				lang('Player can choose to join group'),
				lang('pay.description'),
				lang('cms.createdon'),
				lang('cms.createdby'),
				lang('cms.updatedon'),
				lang('cms.updatedby'),
				lang('lang.status'),
		);

		foreach($data as $key => $val) {
			$result['data'][] = array(
				'groupName' => lang($val['groupName']),
				'groupLevelCount' => $val['groupLevelCount'],
				'can_be_self_join_in' => $val['can_be_self_join_in'] ? lang('lang.yes') : lang('lang.no'),
				'groupDescription' => $val['groupDescription'],
				'createdOn' => $val['createdOn'],
				'createdBy' => $val['createdBy'],
				'updatedOn' => $val['updatedOn'],
				'updatedBy' => $val['updatedBy'],
				'status' => $val['status'],
			);
		}

		$d = new DateTime();
		$link = $this->utils->create_csv($result, 'vip_group_manager' . $d->format('Y_m_d_H_i_s') . '_' . rand(1, 9999));

		$rlt = array('success' => true, 'link' => $link);
		$this->returnJsonResult($rlt);
	}

	public function export_collection_account() {
		$this->load->model('payment_account');

		$sort = "payment_type";
		$data = $this->payment_account->getAllPaymentAccountDetails($sort, null, null);

		$result['header_data'] = array(
			lang('pay.payment_order'),
			lang('pay.payment_name'),
			lang('pay.payment_account_flag'),
			lang('pay.payment_account_name'),
			lang('pay.payment_account_number'),
			( $this->utils->getConfig('use_branch_as_ifsc_in_withdrawal_accounts') ? lang('financial_account.ifsc') : lang('pay.payment_branch_name')),
			lang('pay.exchange'),
			lang('Min deposit per transaction'),
			lang('Max deposit per transaction'),
			lang('pay.daily_max_depsit_amount'),
			lang('pay.total_deposit_limit'),
			lang('pay.daily_max_transaction_count'),
			lang('lang.status'),
			lang('cms.notes'),
			lang('cms.createdon'),
			lang('cms.createdby'),
			lang('cms.updatedon'),
			lang('cms.updatedby')
		);

		if (!empty($data)) {
			$data = json_decode(json_encode($data), true);
			foreach( $data as $key => $val ) {
				$result['data'][] = array(
					'payment_order' => $val['payment_order'],
					'payment_type' => lang($val['payment_type']),
					'flag_name' => $val['flag_name'],
					'payment_account_name' => $val['payment_account_name'],
					'payment_account_number' => $val['payment_account_number'],
					'payment_branch_name' => $val['payment_branch_name'],
					'exchange' => $val['exchange'],
					'min_deposit_trans' => $val['min_deposit_trans'],
					'max_deposit_trans' => $val['max_deposit_trans'],
					'daily_deposit_amount' => $val['daily_deposit_amount'] .' / '.$val['max_deposit_daily'],
					'total_deposit_amount' => $val['total_deposit_amount'] .' / '.$val['total_deposit'],
					'daily_deposit_limit_count' =>(!empty($val['daily_deposit_limit_count']) && $this->utils->getConfig('display_daily_deposit_count_in_collection_account')) ? $val['daily_deposit_count'].'/'.$val['daily_deposit_limit_count'] : $val['daily_deposit_limit_count'],
					'status' => $val['status'] == Payment_account::STATUS_NORMAL ? lang('Active') : lang('Inactive'),
					'notes' => $val['notes'],
					'created_at' => $val['created_at'],
					'created_by' => $val['created_by'],
					'updated_at' => $val['updated_at'],
					'updated_by' => $val['updated_by']
				);
			}
		}

		$d = new DateTime();
		$link = $this->utils->create_csv($result, 'paymentaccountlist_' . $d->format('Y_m_d_H_i_s') . '_' . rand(1, 9999));

		$rlt = array('success' => true, 'link' => $link);
		$this->returnJsonResult($rlt);
	}

	public function export_task_list() {
		$is_export = true;
		$permissions=$this->getContactPermissions();

		$funcName='task_list';
		$callerType=Queue_result::CALLER_TYPE_SYSTEM;
		$caller=0;
		$state='';

		$extra_params=[self::HTTP_REQEUST_PARAM, '', $permissions, $is_export];

		$rlt=$this->exportData($funcName, $extra_params, $callerType, $caller, $state);

		if($rlt['success']){
			redirect($rlt['link']);
		} else {
			return show_error(lang('Export failed'));
		}
	}

	public function export_response_result_list() {
		$is_export = true;
		$permissions = $this->permissions->checkPermissions('export_response_result_list');

		$funcName='response_result_list';
		$callerType=Queue_result::CALLER_TYPE_SYSTEM;
		$caller=0;
		$state='';

		$extra_params=[self::HTTP_REQEUST_PARAM, false, $is_export];

		$rlt=$this->exportData($funcName, $extra_params, $callerType, $caller, $state);

		$this->utils->debug_log('=========extra_params ---->', $extra_params);

		if($rlt['success']){
			redirect($rlt['link']);
		} else {
			return show_error(lang('Export failed'));
		}
	}

	/**
	 * detail: get super player list reports
	 *
	 * @return json or void
	 */
	public function export_super_player_report() {
		$is_export = true;
		$request = $this->input->post();
		$funcName='super_player_report';
		$callerType=Queue_result::CALLER_TYPE_SYSTEM;
		$caller=0;
		$state='';
		$export_permission = $this->permissions->checkPermissions('export_super_player_report');
		$extra_params=[$request, $export_permission, $is_export];

		$rlt=$this->exportData($funcName, $extra_params, $callerType, $caller, $state);
		if($rlt['success']){
			redirect($rlt['link']);
		}else{
			return show_error(lang('Export failed'));
		}
	}

	/**
	 * detail: get super summary list reports
	 *
	 * @return json or void
	 */
	public function export_super_summary_report() {
		$is_export = true;
		$request = $this->input->post();
		$funcName='super_summary_report';
		$callerType=Queue_result::CALLER_TYPE_SYSTEM;
		$caller=0;
		$state='';
		$export_permission = $this->permissions->checkPermissions('export_super_summary_report');
		$extra_params=[$request, $export_permission, $is_export];

		$rlt=$this->exportData($funcName, $extra_params, $callerType, $caller, $state);

		if($rlt['success']){
			redirect($rlt['link']);
		}else{
			return show_error(lang('Export failed'));
		}
	}

	/**
	 * detail: get super game reports
	 *
	 * @return json or void
	 */
	public function export_super_game_report() {
		$is_export = true;
		$request = $this->input->post();
		$funcName='super_game_report';
		$callerType=Queue_result::CALLER_TYPE_SYSTEM;
		$caller=0;
		$state='';
		$export_permission = $this->permissions->checkPermissions('export_super_summary_report');
		$extra_params=[$request, $export_permission, $is_export];

		$rlt=$this->exportData($funcName, $extra_params, $callerType, $caller, $state);
		if($rlt['success']){
			redirect($rlt['link']);
		}else{
			return show_error(lang('Export failed'));
		}
	}

	/**
	 * detail: get super payment reports
	 *
	 * @return json or void
	 */
	public function export_super_payment_report() {
		$is_export = true;
		$request = $this->input->post();
		$funcName='super_payment_report';
		$callerType=Queue_result::CALLER_TYPE_SYSTEM;
		$caller=0;
		$state='';
		$export_permission = $this->permissions->checkPermissions('export_super_payment_report');
		$extra_params=[$request, $export_permission, $is_export];

		$rlt=$this->exportData($funcName, $extra_params, $callerType, $caller, $state);
		if($rlt['success']){
			redirect($rlt['link']);
		}else{
			return show_error(lang('Export failed'));
		}
	}

	/**
	 * detail: get super promotion reports
	 *
	 * @return json or void
	 */
	public function export_super_promotion_report() {
		$is_export = true;
		$request = $this->input->post();
		$funcName='super_payment_report';
		$callerType=Queue_result::CALLER_TYPE_SYSTEM;
		$caller=0;
		$state='';
		$export_permission = $this->permissions->checkPermissions('export_super_promotion_report');
		$extra_params=[$request, $export_permission, $is_export];

		$rlt=$this->exportData($funcName, $extra_params, $callerType, $caller, $state);
		if($rlt['success']){
			redirect($rlt['link']);
		}else{
			return show_error(lang('Export failed'));
		}
	}

	/**
	 * detail: get super cashback reports
	 *
	 * @return json or void
	 */
	public function export_super_cashback_report() {
		$is_export = true;
		$request = $this->input->post();
		$funcName='super_cashback_report';
		$callerType=Queue_result::CALLER_TYPE_SYSTEM;
		$caller=0;
		$state='';
		$export_permission = $this->permissions->checkPermissions('export_super_cashback_report');
		$extra_params=[$request, $export_permission, $is_export];

		$rlt=$this->exportData($funcName, $extra_params, $callerType, $caller, $state);
		if($rlt['success']){
			redirect($rlt['link']);
		}else{
			return show_error(lang('Export failed'));
		}
	}

	/**
	 * detail: get Game logs lists and export to excel
	 *
	 * @return json
	 */
	public function gamesHistoryV2($playerId=null){

		$is_export = true;

		$funcName='gamesHistoryV2';
		$callerType=Queue_result::CALLER_TYPE_SYSTEM;
		$caller=0;
		$state='';

		$extra_params=[self::HTTP_REQEUST_PARAM, $playerId, $is_export];

		$rlt=$this->exportData($funcName, $extra_params, $callerType, $caller, $state);

		if($rlt['success']){
			redirect($rlt['link']);
		}else{
			//return error
			return show_error(lang('Export failed'));
		}
	}

	/**
	 * detail: get player grade reports
	 *
	 * @return json
	 */
	public function grade_report() {
		$is_export = true;
		$permissions=$this->permissions->checkPermissions('grade_report');

		$funcName='playerGradeReports';
		$callerType=Queue_result::CALLER_TYPE_SYSTEM;
		$caller=0;
		$state='';

		$extra_params=[self::HTTP_REQEUST_PARAM, $permissions, $is_export];

		$rlt=$this->exportData($funcName, $extra_params, $callerType, $caller, $state);

		if($rlt['success']){
			redirect($rlt['link']);
		}else{
			return show_error(lang('Export failed'));
		}
	}

	/**
	 * detail: get player rank_report
	 *
	 * @return json
	 */
	public function rank_report() {
		$is_export = true;
		// $permissions=$this->permissions->checkPermissions('export_rank_report');
        $permissions= true;

		$funcName='playerRankReports';
		$callerType=Queue_result::CALLER_TYPE_SYSTEM;
		$caller=0;
		$state='';

		$extra_params=[self::HTTP_REQEUST_PARAM, $permissions, $is_export];

		$rlt=$this->exportData($funcName, $extra_params, $callerType, $caller, $state);

		if($rlt['success']){
			redirect($rlt['link']);
		}else{
			return show_error(lang('Export failed'));
		}
	}

	/**
	 * detail: export getRedemptionCodeList
	 *
	 * @return json
	 */
	public function redemptionCodeReport() {
		$is_export = true;
		// $permissions=$this->permissions->checkPermissions('export_redemption_code_report');
        $permissions= true;

		$funcName='getRedemptionCodeList';
		$callerType=Queue_result::CALLER_TYPE_SYSTEM;
		$caller=0;
		$state='';

		$extra_params=[self::HTTP_REQEUST_PARAM, $permissions, $is_export];

		$rlt=$this->exportData($funcName, $extra_params, $callerType, $caller, $state);

		if($rlt['success']){
			redirect($rlt['link']);
		}else{
			return show_error(lang('Export failed'));
		}
	}

	/**
	 * detail: export getRedemptionCodeList
	 *
	 * @return json
	 */
	public function staticRedemptionCodeReport() {
		$is_export = true;
		// $permissions=$this->permissions->checkPermissions('export_redemption_code_report');
        $permissions= true;

		$funcName='getStaticRedemptionCodeList';
		$callerType=Queue_result::CALLER_TYPE_SYSTEM;
		$caller=0;
		$state='';

		$extra_params=[self::HTTP_REQEUST_PARAM, $permissions, $is_export];

		$rlt=$this->exportData($funcName, $extra_params, $callerType, $caller, $state);

		if($rlt['success']){
			redirect($rlt['link']);
		}else{
			return show_error(lang('Export failed'));
		}
	}

	/**
	 * detail: get communication preference reports
	 *
	 * @return json
	 */
	public function exportCommunicationPreferenceReport() {
		$is_export = true;
		$permissions = $this->permissions->checkPermissions('export_communication_preference_report');

		$funcName='communicationPreferenceReports';
		$callerType=Queue_result::CALLER_TYPE_SYSTEM;
		$caller=0;
		$state='';

		$extra_params=[self::HTTP_REQEUST_PARAM, $is_export];

		$rlt=$this->exportData($funcName, $extra_params, $callerType, $caller, $state);

		if($rlt['success']){
			redirect($rlt['link']);
		}else{
			return show_error(lang('Export failed'));
		}
	}

	/**
	 * detail: get income access signup reports
	 *
	 * @return json
	 */
	public function exportIncomeAccessSignupReports() {
		$is_export = true;
		$permissions = $this->permissions->checkPermissions('export_income_access_signup_report');

		$funcName='incomeAccessSignupReports';
		$callerType=Queue_result::CALLER_TYPE_SYSTEM;
		$caller=0;
		$state='';

		$extra_params=[self::HTTP_REQEUST_PARAM, $permissions, $is_export];

		$rlt=$this->exportData($funcName, $extra_params, $callerType, $caller, $state);

		if($rlt['success']){
			redirect($rlt['link']);
		}else{
			return show_error(lang('Export failed'));
		}
	}

	/**
	 * detail: get income access sales reports
	 *
	 * @return json
	 */
	public function exportIncomeAccessSalesReports() {
		$is_export = true;
		$permissions = $this->permissions->checkPermissions('export_income_access_sales_report');

		$funcName='incomeAccessSalesReports';
		$callerType=Queue_result::CALLER_TYPE_SYSTEM;
		$caller=0;
		$state='';

		$extra_params=[self::HTTP_REQEUST_PARAM, $permissions, $is_export];

		$rlt=$this->exportData($funcName, $extra_params, $callerType, $caller, $state);

		if($rlt['success']){
			redirect($rlt['link']);
		}else{
			return show_error(lang('Export failed'));
		}
	}

    /**
     * detail: export the responsible gaming report
     *
     * @return json or void
     */
    public function exportResponsibleGamingReport() {
        $is_export = true;
        $request = $this->input->post();
        if($this->utils->isEnabledFeature('export_excel_on_queue')) {
            $export_permission = $this->permissions->checkPermissions('export_responsible_gaming_report');
            $funcName = 'responsibleGamingReport';
            $callerType = Queue_result::CALLER_TYPE_SYSTEM;
            $caller = 0;
            $state = '';

            $extra_params = [self::HTTP_REQEUST_PARAM, $export_permission, $is_export];
            $rlt = $this->exportData($funcName, $extra_params, $callerType, $caller, $state);

            if ($rlt['success']) {
                redirect($rlt['link']);
            } else {
                return show_error(lang('Export failed'));
            }
        }
    }

	/**
	 * detail: get Transfer Request lists and export to excel
	 *
	 * @return json
	 */
	public function playertaggedlist($playerId=null){
		$funcName='playertaggedlist';
		$callerType=Queue_result::CALLER_TYPE_SYSTEM;
		$caller=0;
		$state='';
		$is_export = true;
		$permissions = $this->getContactPermissions();

		$extra_params=[self::HTTP_REQEUST_PARAM,$permissions,$is_export];

		$rlt=$this->exportData($funcName, $extra_params, $callerType, $caller, $state);

		if($rlt['success']){
			redirect($rlt['link']);
		}else{
			//return error
			return show_error(lang('Export failed'));
		}
	}

	/**
	 * OGP-25544 add shopping export
	 *
	 * @return json
	 */
	public function shoppingItemList($playerId=null){
		$funcName='shoppingItemClaimList';
		$callerType=Queue_result::CALLER_TYPE_SYSTEM;
		$caller=0;
		$state='';
		$is_export = true;
		$permissions = $this->getContactPermissions();

		$extra_params=[self::HTTP_REQEUST_PARAM,$permissions,$is_export];

		$rlt=$this->exportData($funcName, $extra_params, $callerType, $caller, $state);

		if($rlt['success']){
			redirect($rlt['link']);
		}else{
			//return error
			return show_error(lang('Export failed'));
		}
	}

	/**
	 * detail: get Kingrich API logs lists and export to excel
	 *
	 * @return json
	 */
	public function kingrichApiResponseLogs($playerId=null){

		$is_export = true;


		$request=$this->input->post('json_search');
        $request=$this->utils->decodeJson($request);

        $funcName='kingrichApiResponseLogs';
        $callerType=Queue_result::CALLER_TYPE_SYSTEM;
        $caller=0;
        $state='';

        $extra_params=[$request, $playerId, $is_export];

        $token=$this->lib_queue->addExportCsvJob($funcName, $extra_params, $callerType, $caller, $state);

        $link=site_url('/export_data/queue/'.$token);

        redirect($link);
	}

    public function agency_player_reports(){
        $is_export = true;
        $viewPlayerInfoPerm = true;

        if ($this->utils->isEnabledFeature('enable_agency_player_report_generator')) {
            $funcName='get_agency_player_reports_hourly';
        } else {
            $funcName='get_agency_player_reports';
        }
        $callerType=Queue_result::CALLER_TYPE_SYSTEM;
        $caller=0;
        $state='';

        $extra_params=[self::HTTP_REQEUST_PARAM, $viewPlayerInfoPerm, $is_export];

        $rlt=$this->exportData($funcName, $extra_params, $callerType, $caller, $state);

        if($rlt['success']){
            redirect($rlt['link']);
        }else{
            return show_error(lang('Export failed'));
        }
    }

    /**
	 * detail: get Kingrich Summary Report lists and export to excel
	 *
	 * @return json
	 */
	public function kingrich_summary_report($playerId=null) {

        $is_export = true;

		$funcName='kingrichSummaryReport';
		$callerType=Queue_result::CALLER_TYPE_SYSTEM;
		$caller=0;
		$state='';

		$extra_params=[self::HTTP_REQEUST_PARAM, $is_export];

		$rlt=$this->exportData($funcName, $extra_params, $callerType, $caller, $state);

		if($rlt['success']){
			redirect($rlt['link']);
		}else{
			return show_error(lang('Export failed'));
		}
	}

	/**
	 * detail: get game report for a certain player
	 *
	 * @param int $player_id
	 * @return json
	 */
	public function player_daily_balance() {
		$is_export = true;

		$funcName 	 = 'player_daily_balance';
		$callerType  = Queue_result::CALLER_TYPE_SYSTEM;
		$caller 	 = 0;
		$state 		 = '';
		$extra_params = [self::HTTP_REQEUST_PARAM, $is_export];
		$rlt=$this->exportData($funcName, $extra_params, $callerType, $caller, $state);

		if($rlt['success']){
			redirect($rlt['link']);
		}else{
			//return error
			return show_error(lang('Export failed'));
		}
	}

	/**
	 * detail: get game report for a certain player
	 *
	 * @param int $player_id
	 * @return json
	 */
	public function player_realtime_balance() {
		$is_export = true;

		$funcName 	 = 'player_realtime_balance';
		$callerType  = Queue_result::CALLER_TYPE_SYSTEM;
		$caller 	 = $this->authentication->getUserId();
		$state 		 = '';
		$extra_params = [self::HTTP_REQEUST_PARAM, $is_export];
		$rlt=$this->exportData($funcName, $extra_params, $callerType, $caller, $state);

		if($rlt['success']){
			redirect($rlt['link']);
		}else{
			//return error
			return show_error(lang('Export failed'));
		}
	}

	/**
	 * detail: get player attachment file list
	 *
	 * @return json
	 */
	public function exportPlayerAttachmentFileList() {
		$is_export = true;
		//$permissions = $this->permissions->checkPermissions('attached_file_list');

		$funcName='playerAttachmentFileList';
		$callerType=Queue_result::CALLER_TYPE_SYSTEM;
		$caller=0;
		$state='';

		$extra_params=[self::HTTP_REQEUST_PARAM, $is_export];

		$rlt=$this->exportData($funcName, $extra_params, $callerType, $caller, $state);

		if($rlt['success']){
			redirect($rlt['link']);
		}else{
			return show_error(lang('Export failed'));
		}
	}

	/**
	 * detail: get Kingrich Data Send Scheduler
	 *
	 * @param string transaction_batch_id
	 * @param datetime create_date
	 * @return json
	 */
	public function kingrich_scheduler_report() {
		$is_export = true;

		$funcName='kingrichSchedulerReport';
		$callerType=Queue_result::CALLER_TYPE_SYSTEM;
		$caller=0;
		$state='';

		$extra_params=[self::HTTP_REQEUST_PARAM, $is_export];

		$rlt=$this->exportData($funcName, $extra_params, $callerType, $caller, $state);

		if($rlt['success']){
			redirect($rlt['link']);
		}else{
			return show_error(lang('Export failed'));
		}
	}

	/**
	 * detail: get Kingrich Data Send Scheduler Summary Logs
	 *
	 * @param string transaction_batch_id
	 * @param datetime create_date
	 * @return json
	 */
	public function kingrich_scheduler_summary_logs() {
		$is_export = true;

		$funcName='kingrichSchedulerSummarLogs';
		$callerType=Queue_result::CALLER_TYPE_SYSTEM;
		$caller=0;
		$state='';

		$extra_params=[self::HTTP_REQEUST_PARAM, $is_export];

		$rlt=$this->exportData($funcName, $extra_params, $callerType, $caller, $state);

		if($rlt['success']){
			redirect($rlt['link']);
		}else{
			return show_error(lang('Export failed'));
		}
	}

	/**
	 * detail: get KYC C6 Acuris by player report
	 *
	 * @param datetime player_id
	 * @return json
	 */
	public function kyc_c6_acuris_by_player_report() {
		$is_export = true;

		$funcName='kycC6AcurisByPlayerReport';
		$callerType=Queue_result::CALLER_TYPE_SYSTEM;
		$caller=0;
		$state='';

		$extra_params=[self::HTTP_REQEUST_PARAM, $is_export];

		$rlt=$this->exportData($funcName, $extra_params, $callerType, $caller, $state);

		if($rlt['success']){
			redirect($rlt['link']);
		}else{
			return show_error(lang('Export failed'));
		}
	}

	/**
	 * detail: get player list reports
	 *
	 * @return json or void
	 */
	public function conversion_rate_report($summaryBy) {

        $is_export = true;
		// $request = $this->input->post();
		$permissions=$this->getContactPermissions();

		$funcName='conversion_rate_report';
		$callerType=Queue_result::CALLER_TYPE_SYSTEM;
		$caller=0;
		$state='';

		$extra_params=[$summaryBy, self::HTTP_REQEUST_PARAM, $permissions, $is_export];
		$rlt=$this->exportData($funcName, $extra_params, $callerType, $caller, $state);

		if($rlt['success']){
			redirect($rlt['link']);
		}else{
			//return error
			return show_error(lang('Export failed'));
		}
	}

	/**
	 * detail: export game api update history
	 *
	 * @param datetime player_id
	 * @return json
	 */
	public function gameApiUpdateHistory() {
		$is_export = true;

		$funcName='gameApiUpdateHistory';
		$callerType=Queue_result::CALLER_TYPE_SYSTEM;
		$caller=0;
		$state='';

		$extra_params=[self::HTTP_REQEUST_PARAM, $is_export];

		$rlt=$this->exportData($funcName, $extra_params, $callerType, $caller, $state);

		if($rlt['success']){
			redirect($rlt['link']);
		}else{
			return show_error(lang('Export failed'));
		}
	}

	/**
	 * detail: export game description update history
	 *
	 * @param datetime player_id
	 * @return json
	 */
	public function gameDescriptionHistory() {
		$request = $this->input->post();
		$this->load->model(array('report_model'));

		$is_export = true;
	    $result = $this->report_model->gameDescriptionListHistory($request, $is_export);
		$d = new DateTime();
		$link = $this->utils->create_excel($result, 'game_description_history_list_' . $d->format('Y_m_d_H_i_s') . '_' . rand(1, 9999), TRUE);
		//return file link
		$rlt = array('success' => true, 'link' => $link);
		$this->returnJsonResult($rlt);

		// $is_export = true;

		// $funcName='gameDescriptionList';
		// $callerType=Queue_result::CALLER_TYPE_SYSTEM;
		// $caller=0;
		// $state='';

		// $extra_params=[true];

		// $rlt=$this->exportData($funcName, $extra_params, $callerType, $caller, $state);

		// if($rlt['success']){
		// 	redirect($rlt['link']);
		// }else{
		// 	return show_error(lang('Export failed'));
		// }
	}

	// public function hedgeInAG4playerList(){
	// 	$is_export = true;
	// 	$funcName='hedgeInAG4playerList';
	// 	$callerType=Queue_result::CALLER_TYPE_SYSTEM;
	// 	$caller=0;
	// 	$state='';
	// 	$extra_params=[self::HTTP_REQEUST_PARAM, $is_export];
	//
	// 	$rlt=$this->exportData($funcName, $extra_params, $callerType, $caller, $state);
	// 	if($rlt['success']){
	// 		redirect($rlt['link']);
	// 	}else{
	// 		//return error
	// 		return show_error(lang('Export failed'));
	// 	}
	// }

	/**
	 * detail: get iovation reports
	 *
	 * @return json
	 */
	public function iovationReport() {
        $is_export = true;
		$funcName='iovation_report';
		$callerType=Queue_result::CALLER_TYPE_SYSTEM;
		$caller=0;
		$state='';
		$extra_params=[self::HTTP_REQEUST_PARAM, $is_export];

		$rlt=$this->exportData($funcName, $extra_params, $callerType, $caller, $state);

		if($rlt['success']){
			redirect($rlt['link']);
		}else{
			//return error
			return show_error(lang('Export failed'));
		}
	}

	/**
	 * detail: get iovation evidence
	 *
	 * @return json
	 */
	public function iovationEvidence() {
        $is_export = true;
		$funcName='iovation_evidence';
		$callerType=Queue_result::CALLER_TYPE_SYSTEM;
		$caller=0;
		$state='';
		$extra_params=[self::HTTP_REQEUST_PARAM, $is_export];

		$rlt=$this->exportData($funcName, $extra_params, $callerType, $caller, $state);

		if($rlt['success']){
			redirect($rlt['link']);
		}else{
			//return error
			return show_error(lang('Export failed'));
		}
	}

	/**
	 * detail: get achieveThresholdReport
	 *
	 * @return json
	 */
	public function achieveThresholdReport() {
        $is_export = true;
		$funcName='achieve_threshold_report';
		$callerType=Queue_result::CALLER_TYPE_SYSTEM;
		$caller=0;
		$state='';
		$extra_params=[self::HTTP_REQEUST_PARAM, $is_export];

		$rlt=$this->exportData($funcName, $extra_params, $callerType, $caller, $state);

		if($rlt['success']){
			redirect($rlt['link']);
		}else{
			//return error
			return show_error(lang('Export failed'));
		}
	}

	/**
	 * detail: get abnormalPaymentReport
	 *
	 * @return json
	 */
	public function abnormalPaymentReport() {
        $is_export = true;
		$funcName='abnormal_payment_report';
		$callerType=Queue_result::CALLER_TYPE_SYSTEM;
		$caller=0;
		$state='';
		$extra_params=[self::HTTP_REQEUST_PARAM, $is_export];

		$rlt=$this->exportData($funcName, $extra_params, $callerType, $caller, $state);

		if($rlt['success']){
			redirect($rlt['link']);
		}else{
			//return error
			return show_error(lang('Export failed'));
		}
	}

	/**
	 * detail: get abnormalPaymentReport
	 *
	 * @return json
	 */
	public function excessWithdrawalRequestsList() {
        $is_export = true;
		$funcName='excessWithdrawalRequestsList';
		$callerType=Queue_result::CALLER_TYPE_SYSTEM;
		$caller=0;
		$state='';
		$extra_params=[self::HTTP_REQEUST_PARAM, $is_export];

		$rlt=$this->exportData($funcName, $extra_params, $callerType, $caller, $state);

		if($rlt['success']){
			redirect($rlt['link']);
		}else{
			//return error
			return show_error(lang('Export failed'));
		}
	}

	/**
	 * detail: export ip history
	 *
	 * @return json or void
	 */
	public function export_ip_history() {
		#export_ip_history
		$is_export = true;
		$funcName='export_ip_history';
		$callerType=Queue_result::CALLER_TYPE_SYSTEM;
		$caller=0;
		$state='';
		$extra_params=[self::HTTP_REQEUST_PARAM, $is_export];

		$rlt=$this->exportData($funcName, $extra_params, $callerType, $caller, $state);

		if($rlt['success']){
			redirect($rlt['link']);
		}else{
			//return error
			return show_error(lang('Export failed'));
		}
	}

	/**
	 * detail: get playerLoginReport
	 *
	 * @return json
	 */
	public function playerLoginReport() {
        $is_export = true;
		$funcName='player_login_report';
		$callerType=Queue_result::CALLER_TYPE_SYSTEM;
		$caller=0;
		$state='';
		$extra_params=[self::HTTP_REQEUST_PARAM, $is_export];

		$rlt=$this->exportData($funcName, $extra_params, $callerType, $caller, $state);

		if($rlt['success']){
			redirect($rlt['link']);
		}else{
			//return error
			return show_error(lang('Export failed'));
		}
	}

	/**
	 * detail: get playerDuplicateContactNumberReport
	 *
	 * @return json
	 */
	public function playerDuplicateContactNumberReport() {
        $is_export = true;
		$funcName='player_duplicate_contactnumber_report';
		$callerType=Queue_result::CALLER_TYPE_SYSTEM;
		$caller=0;
		$state='';
		$extra_params=[self::HTTP_REQEUST_PARAM, $is_export];

		$rlt=$this->exportData($funcName, $extra_params, $callerType, $caller, $state);

		if($rlt['success']){
			redirect($rlt['link']);
		}else{
			//return error
			return show_error(lang('Export failed'));
		}
	}

	/**
	 * detail: get playerRouletteReport
	 *
	 * @return json
	 */
	public function playerRouletteReport() {
        $is_export = true;
		$funcName='player_roulette_report';
		$callerType=Queue_result::CALLER_TYPE_SYSTEM;
		$caller=0;
		$state='';
		$extra_params=[self::HTTP_REQEUST_PARAM, $is_export];

		$rlt=$this->exportData($funcName, $extra_params, $callerType, $caller, $state);

		if($rlt['success']){
			redirect($rlt['link']);
		}else{
			//return error
			return show_error(lang('Export failed'));
		}
	}

	/**
	 * detail: get playerRouletteReport
	 *
	 * @return json
	 */
	public function showActivePlayers() {
        $is_export = true;
		$funcName='show_active_players';
		$callerType=Queue_result::CALLER_TYPE_SYSTEM;
		$caller=0;
		$state='';
		$extra_params=[self::HTTP_REQEUST_PARAM, $is_export];

		$rlt=$this->exportData($funcName, $extra_params, $callerType, $caller, $state);

		if($rlt['success']){
			redirect($rlt['link']);
		}else{
			//return error
			return show_error(lang('Export failed'));
		}
	}

	/**
	 * detail: get adjustmentScoreReport
	 *
	 * @return json
	 */
	public function adjustmentScoreReport() {
        $is_export = true;
		$funcName = 'adjustment_score_report';
		$callerType = Queue_result::CALLER_TYPE_SYSTEM;
		$caller= 0;
		$state='';
		$extra_params = [self::HTTP_REQEUST_PARAM, $is_export];

		$rlt = $this->exportData($funcName, $extra_params, $callerType, $caller, $state);

		if($rlt['success']){
			redirect($rlt['link']);
		}else{
			//return error
			return show_error(lang('Export failed'));
		}
	}

	/**
	 * export report to excel
	 *
	 *
	 * @return	excel format
	 */
	public function export_free_spin_campaign_list($game_platform_id) {

		$this->load->library('excel');
		$this->load->model(array('common_game_free_spin_campaign'));
		$data = $this->common_game_free_spin_campaign->getGameCampaignList($game_platform_id);

		$result['header_data'] = array(
				lang('Campaign Id'),
				lang('Campaign Name'),
				lang('Number of Games'),
				lang('lang.status'),
				lang('currency'),
				lang('Start Time'),
				lang('End Time'),
				lang('For new player'),
				lang('cms.createdon'),
				lang('cms.updatedon'),
		);

		foreach($data as $key => $val) {
			$result['data'][] = array(
				'campaign_id' => lang($val['campaign_id']),
				'name' => $val['name'],
				'num_of_games' => $val['num_of_games'],
				'status' => $val['status'],
				'currency' => $val['currency'],
				'start_time' => $val['start_time'],
				'end_time' => $val['end_time'],
				'is_for_new_player' => $val['is_for_new_player'] ? lang('lang.yes') : lang('lang.no'),
				'created_at' => $val['created_at'],
				'updated_at' => $val['updated_at'],
			);
		}

		$d = new DateTime();
		$link = $this->utils->create_csv($result, $game_platform_id . '_campaign_list' . $d->format('Y_m_d_H_i_s') . '_' . rand(1, 9999));

		$rlt = array('success' => true, 'link' => $link);
		$this->returnJsonResult($rlt);
	}

	public function export_system_feature_search_result() {

		$is_export = true;
		$this->load->model(array('system_feature'));
		$request=$this->input->post('json_search');
        $request=$this->utils->decodeJson($request);
		$search_conditions = [
		    'keyword' => '',
		    'show_default_features' => false,
		];
		foreach ($request as $condition) {
			$search_conditions[$condition['name']] = $condition['value'];
		}
		$d = new DateTime();
		$system_features = $this->system_feature->get($search_conditions['keyword']);


		$result = array();
        $result['header_data'] = array(
            lang('id'),
            lang('type'),
            lang('name'),
			Lang('enabled')
        );

        $result['data'] = array();

		$default_to_new_features = $this->utils->getConfig('default_to_new_features');
        if ($search_conditions['show_default_features'] && empty($search_conditions['keyword'] )) {
            foreach ($system_features as $idx => $feature) {
                if (!in_array($feature['name'], $default_to_new_features)) {
                    unset($system_features[$idx]);
                }
            }
        }

        foreach ($system_features as $feature) {
			//filter deprecated_features
			$deprecated_features = $this->utils->getConfig('deprecated_features');
			if(in_array($feature['name'], $deprecated_features)){continue;}
            $result['data'][] = array(
                'id' => $feature['id'],
                'type' => $feature['type'],
                'name' => $feature['name'],
                'enabled' => $feature['enabled'],
            );
        }
		$link =  $this->utils->create_csv($result, 'system_feature_search_result_' . $d->format('Y_m_d_H_i_s') . '_' . rand(1, 9999));
		$rlt = array('success' => true, 'link' => $link);
		$this->returnJsonResult($rlt);
	}

	public function export_player_center_api_domains(){
		$this->load->model(array('player_center_api_domains'));
		$export = true;
		$data = $this->player_center_api_domains->getPlayerCenterApiDomainList($export);
		$result = array();
		$result['header_data'] = array(
			lang('Id'),
			lang('Domain'),
			lang('Remarks'),
			lang('Status'),
			lang('system.word60'),
			lang('cms.updatedby'),
			lang('player_transfer_request.created_at'),
			lang('Updated at')
		);

		$result['data'] = array();

		foreach ($data as $key => $value) {
			$result['data'][] = array(
				'id' => $value->id,
				'domain' => $value->domain,
				'note' => $value->note,
				'status' => ( $value->status == 1 ) ? 'Allowed' : 'Blocked',
				'created_by' => $value->created_by,
				'updated_by' => $value->updated_by,
				'created_at' => $value->created_at,
				'updated_at' => $value->updated_at,
			);
		}

        $d = new DateTime();
		$link = $this->utils->create_excel($result, 'player_center_api_domains_' . $d->format('Y_m_d_H_i_s') . '_' . rand(1, 9999), TRUE);
		//return file link
		$rlt = array('success' => true, 'link' => $link);
		$this->returnJsonResult($rlt);
	}

	/**
	 * detail: export seamlessBalanceHistory
	 *
	 * @return json
	 */
	public function seamless_balance_history($playerId) {
        $is_export = true;
		$funcName = 'seamless_balance_history';
		$callerType = Queue_result::CALLER_TYPE_SYSTEM;
		$caller= 0;
		$state='';
		$extra_params = [$playerId, self::HTTP_REQEUST_PARAM, $is_export];

		$rlt = $this->exportData($funcName, $extra_params, $callerType, $caller, $state);

		if($rlt['success']){
			redirect($rlt['link']);
		}else{
			//return error
			return show_error(lang('Export failed'));
		}
	}

	/**
	 * detail: export playertaggedlistHistory
	 *
	 * @return json
	 */
	public function playertaggedlistHistory($playerId=null){
		$funcName='playertaggedlistHistory';
		$callerType=Queue_result::CALLER_TYPE_SYSTEM;
		$caller=0;
		$state='';
		$is_export = true;
		$permissions = $this->getContactPermissions();

		$extra_params=[self::HTTP_REQEUST_PARAM,$permissions,$is_export];

		$rlt=$this->exportData($funcName, $extra_params, $callerType, $caller, $state);

		if($rlt['success']){
			redirect($rlt['link']);
		}else{
			//return error
			return show_error(lang('Export failed'));
		}
	}

    /**
     * detail: export playertaggedHistory
     *
     * @return json
     */
    public function playertaggedHistory($playerId=null)
    {
        $funcName = 'playertaggedHistory';
        $callerType = Queue_result::CALLER_TYPE_SYSTEM;
        $caller = 0;
        $state = '';
        $is_export = true;
        $permissions = $this->getContactPermissions();

        $extra_params = [self::HTTP_REQEUST_PARAM, $permissions, $is_export];

        $rlt = $this->exportData($funcName, $extra_params, $callerType, $caller, $state);

        if ($rlt['success']) {
            redirect($rlt['link']);
        } else {
            //return error
            return show_error(lang('Export failed'));
        }
    }


    public function getAllGameTags(){

		$request = $this->input->post();
		$is_export = true;

		$this->load->model(array('game_tags'));
		$result = $this->game_tags->queryAllGameTags($request, $is_export);
		$languages = array_values($this->CI->language_function->getAllSystemLanguages());
		$headers = isset($result['header_data']) ? $result['header_data'] : [];
		$language_data = [];
		if(!empty($result)){
			if(!empty($languages)){
				foreach ($languages as $key => $language) {
					if(!empty($headers)){
						unset($headers[0]);
						$headers[] = $language['word'];
						$language_data[$language['word']] = "";
					}
				}
				$result['header_data'] = array_values($headers);
			}

			$data = isset($result['data']) ? $result['data'] : [];
			if(!empty($data)){
				foreach ($data as $key => $datai) {
					$datai = array_merge($datai, $language_data);
					$lang_data_translation = isset($datai[0]) ? $datai[0] : [];
					if(!empty($lang_data_translation)){
						foreach ($lang_data_translation as $ltkey => $translation) {
							#override
							$datai[$ltkey] = $translation;
						}
						unset($datai[0]);
					}
					$result['data'][$key] = array_values($datai);
				}
			}
		}
        $d = new DateTime();
		$link = $this->utils->create_excel($result, 'game_tags_' . $d->format('Y_m_d_H_i_s') . '_' . rand(1, 9999), TRUE);
		//return file link
		$rlt = array('success' => true, 'link' => $link);
		$this->returnJsonResult($rlt);
	}

    /**
     * detail: export tournamentWinnerReports
     *
     * @return json
     */
    public function tournamentWinnerReports($playerId=null)
    {
        $request = $this->input->post();
		$is_export = true;

		$this->load->model(array('report_model'));
		$result = $this->report_model->tournamentWinnerReports($request, $is_export);

        $d = new DateTime();
		$link = $this->utils->create_excel($result, 'tournament_winners_' . $d->format('Y_m_d_H_i_s') . '_' . rand(1, 9999), TRUE);
		//return file link
		$rlt = array('success' => true, 'link' => $link);
		$this->returnJsonResult($rlt);
    }

    /**
	 * detail: export remote wallet balance history
	 *
	 * @return json
	 */
	public function remote_wallet_balance_history($playerId = null) {
        $is_export = true;
		$funcName = 'remote_wallet_balance_history';
		$callerType = Queue_result::CALLER_TYPE_SYSTEM;
		$caller= 0;
		$state='';
		$extra_params = [$playerId, self::HTTP_REQEUST_PARAM, $is_export];

		$rlt = $this->exportData($funcName, $extra_params, $callerType, $caller, $state);

		if($rlt['success']){
			redirect($rlt['link']);
		}else{
			//return error
			return show_error(lang('Export failed'));
		}
	}
	
	public function export_message_list_report($playerId='null', $isCsv = 'false'){
		$is_export = true;
		$permissions=$this->getContactPermissions();

		$funcName='export_message_list_report';
		$callerType=Queue_result::CALLER_TYPE_SYSTEM;
		$caller=0;
		$state='';

		$extra_params=[self::HTTP_REQEUST_PARAM,$permissions, $is_export];

		$rlt=$this->exportData($funcName, $extra_params, $callerType, $caller, $state);
		if($rlt['success']){
			redirect($rlt['link']);
		}else{
			return show_error(lang('Export failed'));
		}
	}

	public function export_player_remarks_report($playerId='null', $isCsv = 'false'){
		$this->utils->debug_log();
		$is_export = true;
		$funcName='export_player_remarks_report';
		$callerType=Queue_result::CALLER_TYPE_SYSTEM;
		$caller=0;
		$state='';
		$extra_params = [self::HTTP_REQEUST_PARAM, $is_export];

		$rlt = $this->exportData($funcName, $extra_params, $callerType, $caller, $state);
		if($rlt['success']){
			redirect($rlt['link']);
		}else{
			return show_error(lang('Export failed'));
		}
	}

	/**
	 * detail: get game report for a certain player
	 *
	 * @param int $player_id
	 * @return json
	 */
	public function game_billing_report() {
		$is_export = true;

		$funcName='gameBillingReports';
		$callerType=Queue_result::CALLER_TYPE_SYSTEM;
		$caller=0;
		$state='';
		$permissions=$this->getContactPermissions();
		$extra_params=[self::HTTP_REQEUST_PARAM, $is_export, $permissions];

		$rlt=$this->exportData($funcName, $extra_params, $callerType, $caller, $state);

		if($rlt['success']){
			redirect($rlt['link']);
		}else{
			//return error
			return show_error(lang('Export failed'));
		}
	}

	/**
	 * detail: get player_game_and_transaction_summary_report
	 *
	 * @param int $player_id
	 * @return json
	 */
	public function player_game_and_transaction_summary_report() {
		$is_export = true;

		$funcName='playerGameAndTransactionSummaryReport';
		$callerType=Queue_result::CALLER_TYPE_SYSTEM;
		$caller=0;
		$state='';
		$permissions=$this->getContactPermissions();
		$extra_params=[self::HTTP_REQEUST_PARAM, $is_export, $permissions];

		$rlt=$this->exportData($funcName, $extra_params, $callerType, $caller, $state);

		if($rlt['success']){
			redirect($rlt['link']);
		}else{
			//return error
			return show_error(lang('Export failed'));
		}
	}
}

////END OF FILE
