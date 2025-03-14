<?php
/**
 * General behaviors include
 * * get player reports
 * * get agency player reports
 * * get agency agent reports
 * * get deposit withdrawal of a certain agents
 *
 * @category report_module_player
 * @version 1.8.10
 * @copyright 2013-2022 tot
 */
trait report_module_player {

	/**
	 * detail: get player reports
	 *
	 * @param array $request
	 * @param Boolean $viewPlayerInfoPerm
	 * @param Boolean $is_export
	 *
	 * @return array
	 */
	public function player_reports($request, $viewPlayerInfoPerm, $is_export = false) {
		$readOnlyDB = $this->getReadOnlyDB();

		$this->load->library('data_tables', array("DB" => $readOnlyDB));
		$this->load->model(array('risk_score_model','player_kyc','kyc_status_model','agency_model'));
		$this->load->helper(['player_helper']);
		$game_apis_map = $this->utils->getAllSystemMap();


		$risk_score_model = $this->risk_score_model;
		$player_kyc = $this->player_kyc;
		$kyc_status_model = $this->kyc_status_model;


		$this->data_tables->is_export = $is_export;
		$risk_score_model = $this->risk_score_model;

		$this->load->model(array('transactions'));
		$i = 0;
		$input = $this->data_tables->extra_search($request);


    	$table = 'player';
		$joins = array();
		$where = array();
		$values = array();
		$group_by = array();
		$having = array();

		$joins['playerdetails'] = 'playerdetails.playerId = player.playerId';
		$joins['player_runtime'] = 'player_runtime.playerId = player.playerId';
		$joins['transactions'] = 'transactions.to_id = player.playerId';
		$joins['affiliates'] = 'affiliates.affiliateId = player.affiliateId';

		$date_from = null;
		$date_to = null;
		$playerlvl = null;
		$playerlvls = null;
		if (isset($input['date_from'], $input['date_to'])) {
			$date_from = $input['date_from'];
			$date_to = $input['date_to'];
		}

        $daterange = ' ';
		$daterange_hour = ' ';
		$secondndeposit_range = ' ';

		if (!empty($date_from) && !empty($date_to)) {
			#for transactions
			$daterange .= " AND transactions.created_at >=  '{$date_from}' AND transactions.created_at <= '{$date_to}'";
            #for total_player_game_hour
			$date_from_hour = (new DateTime($date_from))->format('YmdH');
			$date_to_hour = (new DateTime($date_to))->format('YmdH');
			$daterange_hour = " AND date_hour >=  {$date_from_hour} AND date_hour <=  {$date_to_hour}";
		}

		# FILTER ######################################################################################################################################################################################
		$where[] = 'transactions.to_type = ? AND transactions.status = ?';
		$values[] = Transactions::PLAYER;
		$values[] = Transactions::APPROVED;

		$show_username = true;
		$show_realname = true;
		$show_tag = true;
		$show_risk_level = true;
		$show_kyc_level = true;
		$show_player_level = true;
		$show_affiliate = true;
		$show_agent= true;
		$show_email =true;
		$show_phone = true;
		$show_registered_by = true;
		$show_registered_ip = true;
		$show_last_login_ip = true;
		$show_last_login_date = true;
		$show_last_logout_date = true;
		$show_register_date = true;
		$show_gender = true;
		$show_deposit_bonus=true;
		$show_total_bonus = true;
		$show_cashback_bonus = true;
		$show_referral_bonus = true;
		$show_manual_bonus = true;
		$show_subtract_bonus = true;
		$show_total_bonus = true;
		$show_first_deposit = true;
		$show_2nd_deposit = true;
		$show_total_deposit = true;
		$show_total_deposit_times = true;
		$show_total_withdrawal = true;
		$show_total_dw = true;
		$show_total_bets = true;
		$show_total_payout = true;
		$show_payout_rate = true;
		$show_total_revenue = true;

		if (isset($input['group_by'])) {
			switch ($input['group_by']) {
				case 'player_id':
					$group_by[] = 'player.playerId';
					break;

				case 'playerlevel':
					$group_by[] = 'player.levelId';
					$show_username = false;
					$show_realname = false;
					$show_tag = false;
					$show_risk_level = false;
					$show_kyc_level =false;
					$show_player_level = true;
					$show_affiliate = false;
					$show_agent= false;
					$show_email = false;
					$show_phone = false;
					$show_registered_by = false;
					$show_registered_ip = false;
					$show_last_login_ip = false;
					$show_last_login_date = false;
					$show_last_logout_date = false;
					$show_register_date = false;
					$show_gender =false;


					break;
				case 'affiliate_id':
					$group_by[] = 'player.affiliateId';
					$show_username = false;
					$show_realname = false;
					$show_tag = false;
					$show_risk_level = false;
					$show_kyc_level =false;
					$show_player_level = false;
					$show_affiliate = true;
					$show_agent= false;
					$show_email = false;
					$show_phone = false;
					$show_registered_by = false;
					$show_registered_ip = false;
					$show_last_login_ip = false;
					$show_last_login_date = false;
					$show_last_logout_date = false;
					$show_register_date = false;
					$show_gender = false;
					break;

			    case 'agent_id':
					$group_by[] = 'player.agent_id';
					$show_username = false;
					$show_realname = false;
					$show_tag = false;
					$show_risk_level = false;
					$show_kyc_level =false;
					$show_player_level = false;
					$show_affiliate = false;
					$show_agent= true;
					$show_email = false;
					$show_phone = false;
					$show_registered_by = false;
					$show_registered_ip = false;
					$show_last_login_ip = false;
					$show_last_login_date = false;
					$show_last_logout_date = false;
					$show_register_date = false;
					$show_gender = false;

					break;

				default:
					//
				break;
			}
		}

		if (isset($input['date_from'], $input['date_to'])) {
			$where[] = "transactions.created_at BETWEEN ? AND ?";
			$values[] = $input['date_from'];
			$values[] = $input['date_to'];
		}

		if (isset($input['username'])) {
			$where[] = "player.username LIKE ?";
			$values[] = '%' . $input['username'] . '%';
		}

		if (isset($input['referrer'])) {
			$refereePlayerId = $this->player_model->getPlayerIdByUsername($input['referrer']) ? : -1;
			$where[] = "player.refereePlayerId = ?";
			$values[] = $refereePlayerId;
		}

		if (isset($input['playerlevel'])) {
			$where[] = "player.levelId = ?";
			$values[] = $input['playerlevel'];
		}

		if (isset($input['depamt1'])) {
			$having['total_deposit <='] = $input['depamt1'];
		}

		if (isset($input['depamt2'])) {
			$having['total_deposit >='] = $input['depamt2'];
		}

		if (isset($input['widamt1'])) {
			$having['total_withdrawal <='] = $input['widamt1'];
		}

		if (isset($input['widamt2'])) {
			$having['total_withdrawal >='] = $input['widamt2'];
		}

		if (isset($input['only_under_agency']) && $input['only_under_agency'] != '') {
			$where[] = "player.agent_id IS NOT NULL";
			if (!isset($input['agent_name'])) {
				if (isset($input['current_agent_name']) && $input['current_agent_name'] != '') {
					$input['agent_name'] = $input['current_agent_name'];
				}
			}
		}

		if (isset($input['agent_name'])) {
			$this->load->model(array('agency_model'));
			$agent_detail = $this->agency_model->get_agent_by_name($input['agent_name']);
			$this->utils->debug_log('agent_detail', $agent_detail);

			if (isset($input['include_all_downlines']) && $input['include_all_downlines'] == true) {
				$joins['agency_agents'] = 'player.agent_id = agency_agents.agent_id';
				$parent_ids = array($agent_detail['agent_id']);
				$sub_ids = array();
				$all_ids = $parent_ids;
				while (!empty($sub_ids = $this->agency_model->get_sub_agent_ids_by_parent_id($parent_ids))) {
					$this->utils->debug_log('sub_ids', $sub_ids);
					$all_ids = array_merge($all_ids, $sub_ids);
					$parent_ids = $sub_ids;
					$sub_ids = array();
				}
				foreach ($all_ids as $i => $id) {
					if ($i == 0) {
						$w = "(player.agent_id = ?";
					} else {
						$w .= " OR player.agent_id = ?";
					}
					$values[] = $id;
				}
				$w .= ")";
				$where[] = $w;
			} else {
				$where[] = "player.agent_id = ?";
				$values[] = $agent_detail['agent_id'];
			}
		}

		if (isset($input['affiliate_name'])) {

			$this->load->model('affiliatemodel');
			$affiliateId = $this->affiliatemodel->getAffiliateIdByUsername($input['affiliate_name']);
			@$this->utils->debug_log('affiliateId', $$affiliateId);
			$affiliateIds = null;

			if (isset($input['aff_include_all_downlines']) && $input['aff_include_all_downlines'] && !empty($affiliateId)) {
				$affiliateIds = $this->affiliatemodel->includeAllDownlineAffiliateIds($affiliateId);
			}

			if (!empty($affiliateIds)) {
				$where[] = 'player.affiliateId IN(' . implode(',', $affiliateIds) . ')';
			} else {
				$where[] = "affiliates.username like ?";
				$values[] = '%' . $input['affiliate_name'] . '%';
			}
		}

		$affiliatesTagMap = [];

		if (isset($input['affiliate_tags'])) {
			$this->load->model(array('affiliatemodel','affiliate'));

			$affiliatesTagMap = $this->affiliate->getAffTagsMap();

			$this->utils->debug_log('affiliatesTagMap1', $affiliatesTagMap);

			$joins['affiliatetag'] = 'affiliatetag.affiliateId=affiliates.affiliateId';
			$joins['affiliatetaglist'] = 'affiliatetaglist.tagId=affiliatetag.tagId';
			$where[] = "affiliatetag.tagId = ?";
			$values[] = $input['affiliate_tags'];
		}

		if (isset($input['player_tag'])) {
			$joins['playertag'] = 'playertag.playerId = player.playerId';
			$where[] = "playertag.tagId = ?";
			$values[] = $input['player_tag'];
		}

		if (isset($input['affiliate_agent'])) {
			switch ($input['affiliate_agent']) {
				case '1': # Not under any Affiliate or Agent
					$where[] = '(player.agent_id IS NULL OR player.agent_id = 0) AND (player.affiliateId IS NULL OR player.affiliateId = 0)';
					break;

				case '2': # Under Affiliate Only
					$where[] = '(player.agent_id IS NULL OR player.agent_id = 0) AND player.affiliateId > 0';
					break;

				case '3': # Under Agent Only
					$where[] = 'player.agent_id > 0 AND (player.affiliateId IS NULL OR player.affiliateId = 0)';
					break;

				case '4': # Under Affiliate or Agent
					$where[] = '(player.agent_id > 0 OR player.affiliateId > 0)';
					break;
			}
		}

		$where[] = "player.deleted_at IS NULL";

    	$subtotals = ['subtotals_deposit_bonus'=> 0 , 'subtotals_cashback_bonus' => 0, 'subtotals_referral_bonus' => 0, 'subtotals_manual_bonus'=>0, 'subtotals_subtract_bonus' => 0, 'subtotals_total_bonus'=> 0, 'subtotals_first_deposit' => 0, 'subtotals_second_deposit' => 0,'subtotals_total_deposit' => 0, 'subtotals_total_deposit_times' => 0,'subtotals_total_withdrawal'=> 0,'subtotals_total_bets' => 0, 'subtotals_total_payout'=> 0, 'subtotals_total_dw'=> 0, 'subtotals_payout_rate' => 0];
    	$total = ['total_first_deposit' =>0, 'total_second_deposit'=> 0, 'total_bets_add' => 0, 'total_payout_add' => 0, 'total_payout_rate' => 0, 'total_player_id' => '', 'total_player_levelId' => ''];

		# DEFINE TABLE COLUMNS ######################################################################################
		$columns = array(
			array(
				'alias' => 'playerId',
				'name' => 'playerId',
				'select' => 'player.playerId',
			),
			array(
				'alias' => 'affiliateId',
	     		'select' => 'affiliates.affiliateId',
				'formatter' => 'defaultFormatter',
			),
			array(
				'alias' => 'levelId',
	     		'select' => 'player.levelId',
				'formatter' => 'defaultFormatter',
			),
			array(
				'dt' => $i++,
				'alias' => 'username',
				'name' => lang('report.pr01'),
				'select' => $show_username ? '( CASE WHEN affiliates.username IS NULL THEN player.username ELSE CONCAT(player.username, \' (\', affiliates.username,  \')\' ) END )' : '"N/A"',
				'formatter' => function ($d, $row) use ($is_export, $date_from, $date_to, $show_username) {

					if($show_username){
						if ($is_export) {
							return $d;
						} else {
							$date_qry = '';
							if (!empty($date_from) && !empty($date_to)) {
								$date = new DateTime($date_from);
								$date_qry = '&date_from=' . $date->format('Y-m-d') . '&hour_from=' . $date->format('H');

								$date = new DateTime($date_to);
								$date_qry .= '&date_to=' . $date->format('Y-m-d') . '&hour_to=' . $date->format('H');
							}
							return "<a href='/report_management/viewGamesReport?username={$d}{$date_qry}'>{$d}</a>";
						}
					}else{
						if($is_export){
							return lang('lang.norecyet');
						}
						return  '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
					}
				},
			),
			array(
				'dt' => $i++,
				'alias' => 'real_name',
				'name' => lang('report.pr02'),
				'select' => 'CONCAT(ifnull(playerdetails.firstName,""), \' \', ifnull(playerdetails.lastName,"") )',
				'formatter' => 'defaultFormatter',
				'formatter' => function ($d, $row) use ($is_export, $show_realname) {
					if($show_realname){
						return $d;
					}else{
						if($is_export){
							return lang('lang.norecyet');
						}
						return  '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
					}
    			},
			),
			array(
				'dt' => $i++,
				'alias' => 'tagName',
				'select' => 'player.playerId',
				'name' => lang("player.41"),
				'formatter' => function ($d, $row) use ($is_export, $show_tag) {
					if($show_tag){
						return player_tagged_list($row['playerId'], $is_export);
					}else{
						if($is_export){
							return lang('lang.norecyet');
						}
						return  '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
					}
    			},
     		),
			array(
				'dt' => $i++,
				'alias' => 'risk_level',
				'name' => lang("Risk Level/Score"),
				'select' => 'playerdetails.playerId',
				'formatter' => function ($d) use ($is_export,$risk_score_model,$show_risk_level) {
					if($show_risk_level){
						if ($is_export) {
							return $risk_score_model->getPlayerCurrentRiskLevel($d) && $risk_score_model->generate_total_risk_score($d) ? $risk_score_model->getPlayerCurrentRiskLevel($d) .' / '. $risk_score_model->generate_total_risk_score($d) : lang('lang.norecyet');
						}else{
							return $risk_score_model->getPlayerCurrentRiskLevel($d) && $risk_score_model->generate_total_risk_score($d) ? $risk_score_model->getPlayerCurrentRiskLevel($d) .' / '. $risk_score_model->generate_total_risk_score($d) : '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
						}
					}else{
						if($is_export){
							return lang('lang.norecyet');
						}
						return  '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
					}
				},
			),
			array(
				'dt' => $i++,
				'alias' => 'kyc_level',
				'name' => lang("KYC Level/Rate Code"),
				'select' => 'playerdetails.playerId',
				'formatter' => function ($d) use ($is_export,$player_kyc,$kyc_status_model,$show_kyc_level) {
					if($show_kyc_level){
						if ($is_export) {
							return $player_kyc->getPlayerCurrentKycLevel($d) && $kyc_status_model->getPlayerCurrentStatus($d) ? $player_kyc->getPlayerCurrentKycLevel($d) .' / '. $kyc_status_model->getPlayerCurrentStatus($d): lang('lang.norecyet');
						}else{
							return $player_kyc->getPlayerCurrentKycLevel($d) && $kyc_status_model->getPlayerCurrentStatus($d) ? $player_kyc->getPlayerCurrentKycLevel($d) .' / '. $kyc_status_model->getPlayerCurrentStatus($d): '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
						}
					}else{
						if($is_export){
							return lang('lang.norecyet');
						}
						return  '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
					}
				},
			),
			array(
				'dt' => $i++,
				'name' => lang('report.pr03'),
				'alias' => 'member_level',
				'select' => 'player.levelName',
				'formatter' => function ($d, $row) use ($show_player_level, $is_export) {
					if(($show_player_level)){
			            $getUpdatedGroupAndLevel = $this->player_model->getPlayerCurrentLevel($row['playerId']);
			            if($getUpdatedGroupAndLevel){
			                $groupName = lang($getUpdatedGroupAndLevel[0]['groupName']);
			                $levelName = lang($getUpdatedGroupAndLevel[0]['vipLevelName']);
			            	return $groupName . ' - ' .$levelName;
			            }
			            else{
			            	return null;
			            }
					}else{
						if($is_export){
							return lang('lang.norecyet');
						}
						return  '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
					}
				},
			),
			array(
				'dt' => $i++,
				'alias' => 'affiliates_name',
				'name' => lang('Affiliate'),
				'select' => 'affiliates.username',
				'formatter' => function($d,$row) use ($is_export, $show_affiliate, $input, $affiliatesTagMap){
					if($show_affiliate){
						$url = site_url('/affiliate_management/userInformation/' . $row['affiliateId']);
						$name = '';
						if($is_export){
							$name = !empty($d) ? $d :  lang('lang.norecyet') ;
						}else{
							if(!empty($d)){
								if(isset($input['affiliate_tags'])){
									if(isset($affiliatesTagMap[$input['affiliate_tags']])){
										$name = '<span class="badge badge-info" style="float:right;">'.$affiliatesTagMap[$input['affiliate_tags']].'</span>';
									}else{
										$name ='';
									}
								}
								$name .= '<a href="' . $url . '">' . $d . '</a> ';

							}else{
								$name = '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
							}
						}
						return $name;
					}else{
						if($is_export){
							return lang('lang.norecyet');
						}
						return  '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
					}
				},
			),
			array(
				'dt' => $i++,
				'alias' => 'agent',
				'select' => 'player.agent_id',
				'name' => lang("Agent"),
				'formatter' => function ($d) use ($is_export,$show_agent) {
					if($show_agent){
						$name = '';
						if ($d != null) {
							$agent_details = $this->agency_model->get_agent_by_id($d);
							$name = $agent_details['agent_name'];
							$url = site_url('/agency_management/agent_information/' . $d);
							if($is_export){
								$name =  $name;
							}else{
								$name = '<a href="' . $url . '">' . $name . '</a>';
							}
						} else {
							if ($is_export) {
								return trim(trim($d), ',') ?: lang('lang.norecyet');
							} else {
								return trim(trim($d), ',') ?: '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
							}
						}
						return $name;
					}else{
						if($is_export){
							return lang('lang.norecyet');
						}
						return  '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
					}
				},
			),
			array(
				'dt' => $i++,
				'alias' => 'email',
				'name' => lang('report.pr04'),
				'select' => 'player.email',
				'formatter' => function ($d) use ($viewPlayerInfoPerm, $is_export, $show_email) {
					if($show_email){
						$str = $d;
					//no permission then show *, hide real content
						if (!$viewPlayerInfoPerm) {
							$str = $this->data_tables->defaultFormatter(preg_replace('#.#', '*', $d));
							if (!$is_export) {
								$str = '<span title="' . lang('con.aff01') . '">' . $str . '</span>';
							}
						}
						return $str;
					}else{
						if($is_export){
							return lang('lang.norecyet');
						}
						return  '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
					}
				},
			),
			array(
				'dt' => $i++,
				'alias' => 'contactNumber',
				'select' => 'playerdetails.contactNumber',
				'name' => lang('aff.ai15'),
				'formatter' => function ($d) use ($viewPlayerInfoPerm, $is_export, $show_phone) {
					if($show_phone){
						$str = $d;
						if (!$str && !$is_export) {
							return '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
						}
					//no permission then show *, hide real content
						if (!$viewPlayerInfoPerm) {
							$str = $this->data_tables->defaultFormatter(preg_replace('#.#', '*', $d));
							if (!$is_export) {
								$str = '<span title="' . lang('con.aff01') . '">' . $str . '</span>';
							}
						}
						return $str;
					}else{
						if($is_export){
							return lang('lang.norecyet');
						}
						return  '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
					}
				},
			),
			array(
				'dt' => $i++,
				'alias' => 'registered_by',
				'select' => 'player.registered_by',
				'name' => lang('report.pr05'),
				'formatter' => function ($d) use ($show_registered_by, $is_export){
					if($show_registered_by){
						$reg_by = lang("player.69");
						switch (strtolower($d)) {
							case 'mobile':		$reg_by = lang("sys.item4");		break;
							case 'website':		$reg_by = lang("player.68");		break;
							default:			$reg_by = lang("player.69");		break;
						}
						return $reg_by;
					}else{
						if($is_export){
							return lang('lang.norecyet');
						}
						return  '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
					}
				},
    		),
			array(
				'dt' => $i++,
				'name' => lang('report.pr06'),
				'alias' => 'registrationIP',
				'select' => 'playerdetails.registrationIP',
				'formatter' => 'defaultFormatter',
				'formatter' =>  function ($d) use ($show_registered_ip, $is_export){
					if($show_registered_ip){
						return $d;
					}else{
						if($is_export){
							return lang('lang.norecyet');
						}
						return  '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
					}
				}
			),
			array(
				'dt' => $i++,
				'alias' => 'lastLoginIp',
				'name' => lang('report.pr07'),
				'select' => 'player_runtime.lastLoginIp',
				'formatter' =>  function ($d) use ($show_last_login_ip, $is_export){
					if($show_last_login_ip){
						return $d;
					}else{
						if($is_export){
							return lang('lang.norecyet');
						}
						return  '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
					}
				}
    		),
			array(
				'dt' => $i++,
				'alias' => 'lastLoginTime',
				'name' => lang('player.42'),
				'select' => 'player_runtime.lastLoginTime',
				'formatter' =>  function ($d) use ($show_last_login_date, $is_export){
					if($show_last_login_date){
						return $d;
					}else{
						if($is_export){
							return lang('lang.norecyet');
						}
						return  '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
					}
				}
			),
			array(
				'dt' => $i++,
				'alias' => 'lastLogoutTime',
				'name' => lang('report.pr09'),
				'player_runtime.lastLogoutTime',
				'formatter' =>  function ($d) use ($show_last_logout_date, $is_export){
					if($show_last_logout_date){
						return $d;
					}else{
						if($is_export){
							return lang('lang.norecyet');
						}
						return  '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
					}
				}
			),
			array(
				'dt' => $i++,
				'alias' => 'createdOn',
				'name' => lang('report.pr10'),
				'select' => 'player.createdOn',
				'formatter' =>  function ($d) use ($show_register_date, $is_export){
					if($show_register_date){
						return $d;
					}else{
						if($is_export){
							return lang('lang.norecyet');
						}
						return  '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
					}
				}
			),
			array(
				'dt' => $i++,
				'alias' => 'gender',
				'name' => lang('report.pr11'),
				'select' => 'playerdetails.gender',
				'formatter' =>  function ($d) use ($show_gender, $is_export){
					if($show_gender){
						return $d;
					}else{
						if($is_export){
							return lang('lang.norecyet');
						}
						return  '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
					}
				}
			),
			array(
				'dt' => $i++,
				'alias' => 'cashback',
				'name' => lang('report.sum15'),
				'select' => 'SUM(CASE WHEN transactions.transaction_type = ' . Transactions::AUTO_ADD_CASHBACK_TO_BALANCE . ' THEN transactions.amount ELSE 0 END)',
				'formatter' =>  function ($d) use ($show_cashback_bonus, $is_export, &$subtotals){
					if($show_cashback_bonus){
						$subtotals['subtotals_cashback_bonus'] += $d;
						return $this->data_tables->currencyFormatter($d);
					}else{
						if($is_export){
							return lang('lang.norecyet');
						}
						return  '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
					}
				}
			),
			array(
				'dt' => $i++,
				'alias' => 'deposit_bonus',
				'name' => lang('report.pr15'),
				'select' => 'SUM(CASE WHEN transactions.transaction_type = ' . Transactions::MEMBER_GROUP_DEPOSIT_BONUS . ' THEN transactions.amount ELSE 0 END)',
				'formatter' =>  function ($d) use ($show_deposit_bonus, $is_export, &$subtotals){
					if($show_deposit_bonus){
						$subtotals['subtotals_deposit_bonus'] += $d;
						return $this->data_tables->currencyFormatter($d);
					}else{
						if($is_export){
							return lang('lang.norecyet');
						}
						return  '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
					}
				}
			),
			array(
				'dt' => $i++,
				'alias' => 'referral_bonus',
				'name' => lang('report.pr17'),
				'select' => 'SUM(CASE WHEN transactions.transaction_type = ' . Transactions::PLAYER_REFER_BONUS . ' THEN transactions.amount ELSE 0 END)',
				'formatter' =>  function ($d) use ($show_referral_bonus, &$subtotals, $is_export){
					if($show_referral_bonus){
						$subtotals['subtotals_referral_bonus'] += $d;
						return $this->data_tables->currencyFormatter($d);
					}else{
						if($is_export){
							return lang('lang.norecyet');
						}
						return  '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
					}
				}
    		),
			array(
				'dt' => $i++,
				'alias' => 'manual_bonus',
				'name' => lang('transaction.manual_bonus'),
				'select' => 'SUM(CASE WHEN transactions.transaction_type = ' . Transactions::ADD_BONUS . ' THEN transactions.amount ELSE 0 END)',
				'formatter' =>  function ($d) use ($show_manual_bonus, $is_export, &$subtotals){
					if($show_manual_bonus){
						$subtotals['subtotals_manual_bonus'] += $d;
						return $this->data_tables->currencyFormatter($d);
					}else{
						if($is_export){
							return lang('lang.norecyet');
						}
						return  '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
					}
				}
     		),
     		array(
				'dt' => $i++,
				'alias' => 'subtract_bonus',
				'name' => lang('transaction.transaction.type.10'),
				'select' => 'SUM(CASE WHEN transactions.transaction_type = ' . Transactions::SUBTRACT_BONUS . ' THEN transactions.amount ELSE 0 END)',
				'formatter' =>  function ($d) use ($show_subtract_bonus, $is_export, &$subtotals){
					if($show_subtract_bonus){
						$subtotals['subtotals_subtract_bonus'] -= $d;
						return $this->data_tables->currencyFormatter($d);
					}else{
						if($is_export){
							return lang('lang.norecyet');
						}
						return  '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
					}
				}
     		),
			array(
				'dt' => $i++,
				'alias' => 'total_bonus',
				'name' => lang('report.pr18'),
				'select' => 'SUM(CASE WHEN transactions.transaction_type IN (' . implode(',', array(Transactions::MEMBER_GROUP_DEPOSIT_BONUS, Transactions::PLAYER_REFER_BONUS, Transactions::ADD_BONUS)) . ') THEN transactions.amount ELSE 0 END)',
				'formatter' =>  function ($d,$row) use ($show_total_bonus, $is_export, &$subtotals){
					if($show_total_bonus){
						$subtotals['subtotals_total_bonus'] += $d;
						return $this->data_tables->currencyFormatter($d - $row['subtract_bonus']);
					}else{
						if($is_export){
							return lang('lang.norecyet');
						}
						return  '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
					}
				}
			),
			array(
				'dt' => $i++,
				'alias' => 'first_deposit',
				'select' => 'transactions.id',
				'name' => lang('report.pr19'),
				'formatter' => function ($d, $row) use ($readOnlyDB,$daterange, $show_first_deposit, $is_export, &$subtotals) {
                    if($show_first_deposit){
                    	$query = $readOnlyDB->query("SELECT transactions.amount FROM transactions JOIN player ON player.playerId = transactions.to_id WHERE transactions.to_type = ?  AND transactions.transaction_type = ? AND transactions.status = ?  AND transactions.to_id  = ".$row['playerId']."  AND player.deleted_at IS NULL ORDER BY created_at ASC LIMIT ?,1", array(
						Transactions::PLAYER,
						//$d,
						Transactions::DEPOSIT,
						Transactions::APPROVED,
						0, # FIRST DEPOSIT
					));
					$row = $query->row_array();
					$subtotals['subtotals_first_deposit'] += isset($row['amount']) ? $row['amount'] : 0;
					return $this->data_tables->currencyFormatter(isset($row['amount']) ? $row['amount'] : 0);
                    }else{
                    	if($is_export){
							return lang('lang.norecyet');
						}
						return  '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
                    }
				},
			),
			array(
				'dt' => $i++,
				'alias' => 'second_deposit',
				'select' => 'player.playerId',
				'name' => lang('report.pr20'),
				'formatter' => function ($d, $row) use ($readOnlyDB,$daterange, $show_2nd_deposit, $is_export, &$subtotals) {
					if($show_2nd_deposit){
						$query = $readOnlyDB->query("SELECT transactions.amount FROM transactions JOIN player ON player.playerId = transactions.to_id WHERE transactions.to_type = ?  AND transactions.transaction_type = ? AND transactions.status = ?  AND transactions.to_id  = ".$row['playerId']."  AND player.deleted_at IS NULL ORDER BY created_at ASC LIMIT ?,1", array(
							Transactions::PLAYER,
						//$d,
							Transactions::DEPOSIT,
							Transactions::APPROVED,
						1, # FIRST DEPOSIT
					));

						$row = $query->row_array();
						$subtotals['subtotals_second_deposit'] += isset($row['amount']) ? $row['amount'] : 0;
						return $this->data_tables->currencyFormatter(isset($row['amount']) ? $row['amount'] : 0);

					}else{
						if($is_export){
							return lang('lang.norecyet');
						}
						return  '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
					}
				}
    		),
			array(
				'dt' => $i++,
				'alias' => 'total_deposit',
				'name' => lang('report.pr21'),
				'select' => 'SUM(CASE WHEN transactions.transaction_type IN (' . Transactions::DEPOSIT . ') THEN transactions.amount ELSE 0 END)',
				'formatter' =>  function ($d) use ($show_total_deposit, $is_export, &$subtotals){
					if($show_total_deposit){
						$subtotals['subtotals_total_deposit'] += $d;
						return $this->data_tables->currencyFormatter($d);
					}else{
						if($is_export){
							return lang('lang.norecyet');
						}
						return  '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
					}
				}
			),
            array(
                'dt' => $i++,
                'alias' => 'total_deposit_times',
                'name' => lang('yuanbao.deposit.times'),
                'select' => 'COUNT(CASE WHEN transactions.transaction_type IN (' . Transactions::DEPOSIT . ') THEN 1 END)',
                'formatter' =>  function ($d) use ($show_total_deposit_times, $is_export, &$subtotals){
					if($show_total_deposit_times){
						$subtotals['subtotals_total_deposit_times'] += $d;
						return $d;
					}else{
						if($is_export){
							return lang('lang.norecyet');
						}
						return  '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
					}
				}
            ),
			array(
				'dt' => $i++,
				'alias' => 'total_withdrawal',
				'name' => lang('report.pr22'),
				'select' => 'SUM(CASE WHEN transactions.transaction_type IN (' . implode(',', array(Transactions::WITHDRAWAL)) . ') THEN transactions.amount ELSE 0 END)',
				'formatter' =>  function ($d) use ($show_total_withdrawal, $is_export, &$subtotals){
					if($show_total_withdrawal ){
						$subtotals['subtotals_total_withdrawal'] += $d;
						return $this->data_tables->currencyFormatter($d);
					}else{
						if($is_export){
							return lang('lang.norecyet');
						}
						return  '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
					}
				}
			),
			array(
				'dt' => $i++,
				'alias' => 'total_dw',
				'name' => lang('Deposit - Withdraw'),
			    'select' =>'SUM(CASE WHEN transactions.transaction_type IN (' . implode(',', array(Transactions::DEPOSIT)) . ') THEN transactions.amount ELSE 0 END) - SUM(CASE WHEN transactions.transaction_type IN (' . implode(',', array(Transactions::WITHDRAWAL)) . ') THEN transactions.amount ELSE 0 END)',
				'formatter' =>  function ($d) use ($show_total_dw, $is_export, &$subtotals){
					if($show_total_dw){
						$subtotals['subtotals_total_dw'] += $d;
						return $this->data_tables->currencyFormatter($d);
					}else{
						if($is_export){
							return lang('lang.norecyet');
						}
						return  '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
					}
				}
			),
			array(
				'dt' => $i++,
				'alias' => 'total_bets',
				'name' => lang('cms.totalbets'),
				'select' => 'player.playerId',
				'formatter' => function ($d, $row) use ($readOnlyDB,$daterange_hour, $show_total_bets, $is_export, &$subtotals, $input) {
					if($show_total_bets){
							$queryString = "SELECT SUM(betting_amount) AS total_bets FROM total_player_game_hour WHERE player_id = ".$d."  ".$daterange_hour."";
							# overrive query if group by player level
							if (isset($input['group_by']) && strtolower($input['group_by']) == "playerlevel") {
								$queryString = "SELECT SUM(betting_amount) AS total_bets FROM total_player_game_hour WHERE player_id IN (SELECT playerId FROM player where levelId = ".$row['levelId'].")  ".$daterange_hour;
							}

							$query = $readOnlyDB->query($queryString);
							$row = $query->row_array();
							$subtotals['subtotals_total_bets'] += isset($row['total_bets']) ? $row['total_bets'] : 0 ;
							return $this->data_tables->currencyFormatter(isset($row['total_bets']) ? $row['total_bets'] : 0);

					}else{
						if($is_export){
							return lang('lang.norecyet');
						}
						return  '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
					}

				},
			),
			array(
				'dt' => $i++,
				'alias' => 'total_payout',
				'name' => lang('Payout'),
				'select' => 'player.playerId',
				'formatter' => function ($d, $row) use ($readOnlyDB,$daterange_hour,$show_total_payout, $is_export, &$subtotals, $input) {
					if($show_total_payout){
						$queryString = "SELECT SUM(loss_amount - win_amount) AS total_payout FROM total_player_game_hour WHERE player_id = ".$d."  ".$daterange_hour."";
						# overrive query if group by player level
						if (isset($input['group_by']) && strtolower($input['group_by']) == "playerlevel") {
							$queryString = "SELECT SUM(loss_amount - win_amount) AS total_payout FROM total_player_game_hour WHERE player_id in (SELECT playerId FROM player where levelId = ".$row['levelId'].") ". $daterange_hour;
						}

						// return $queryString;
						$query = $readOnlyDB->query($queryString);
						$row = $query->row_array();
						$subtotals['subtotals_total_payout'] += isset($row['total_payout']) ? $row['total_payout'] : 0 ;
						return $this->data_tables->currencyFormatter(isset($row['total_payout']) ? $row['total_payout'] : 0);
					}else{
						if($is_export){
							return lang('lang.norecyet');
						}
						return  '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
					}
				},
			),
			array(
				'dt' => $i++,
				'alias' => 'payout_rate',
				'name' => lang('sys.payoutrate'),
				'select' => 'player.playerId',
				'formatter' => function ($d, $r) use ($readOnlyDB,$daterange_hour,$show_payout_rate, $is_export, $input) {
					if($show_payout_rate){
						$queryString = "SELECT SUM(loss_amount - win_amount)/SUM(betting_amount) AS payout_rate FROM total_player_game_hour WHERE player_id = ".$d."  ".$daterange_hour."     GROUP BY player_id ";
						# overrive query if group by player level
						if (isset($input['group_by']) && strtolower($input['group_by']) == "playerlevel") {
							$queryString = "SELECT SUM(loss_amount - win_amount)/SUM(betting_amount) AS payout_rate FROM total_player_game_hour WHERE player_id in (SELECT playerId FROM player where levelId = ".$r['levelId'].") ".$daterange_hour;
						}

						$query = $readOnlyDB->query($queryString);
						$row = $query->row_array();
						return $this->data_tables->percentageFormatter(isset($row['payout_rate']) ? $row['payout_rate'] : '0');
					}else{
						if($is_export){
							return lang('lang.norecyet');
						}
						return  '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
					}
				},
			),
			array(
				'dt' => $i++,
				'alias' => 'game_bet_details',
				'name' => lang('Bet Detail'),
				'select' => 'player.playerId',
				'formatter' => function ($d, $row) use ($readOnlyDB,$daterange_hour,$game_apis_map, $is_export, $input) {
					$queryString = "
						SELECT game_platform_id AS game,
							   SUM(betting_amount) AS bet,
							   SUM(loss_amount - win_amount) AS payout,
							   SUM(loss_amount - win_amount)/SUM(betting_amount) AS payout_rate
						FROM total_player_game_hour
						WHERE player_id = ".$d." ".$daterange_hour."
						GROUP BY game_platform_id";
					# overrive query if group by player level
					if (isset($input['group_by']) && strtolower($input['group_by']) == "playerlevel") {
						$queryString = "
							SELECT game_platform_id AS game,
							       SUM(betting_amount) AS bet,
							       SUM(loss_amount - win_amount) AS payout,
							       SUM(loss_amount - win_amount)/SUM(betting_amount) AS payout_rate
							FROM total_player_game_hour
							WHERE player_id IN (
								SELECT playerId
								FROM player
								WHERE levelId = ".$row['levelId'].") ".$daterange_hour."
								GROUP BY game_platform_id";
					}

					$query = $readOnlyDB->query($queryString);
					$bet_details=$query->result_array();

					$total_bets_ = 0;
					$total_payout_ = 0;
					$total_rate_ = 0;

					if(!empty($bet_details)){
						if(!$is_export){
							$bet_details_str = '<table  border="1" style="width:500px">';
							$bet_details_str .=  '<tr style="padding:2px;">';
							$bet_details_str .=  '<th class="text-center" style="width:129px;"> '.lang('Game Platform').' </th><th class="text-center" style="width:105px;" > '.lang('Bets').' </th><th class="text-center" style="width:105px;"  > '.lang('report.Payout').' </th><th class="text-center" style="width:105px;"> '.lang('sys.payoutrate').'</th>';
							$bet_details_str .=  '</tr>';

							foreach ($bet_details as $v) {
								$total_bets_ += $v['bet'];
								$total_payout_ += $v['payout'];
								$bet_details_str .=  '<tr>';

								if(array_key_exists($v['game'], $game_apis_map)){
									$bet_details_str .= '<td style="text-align:right;padding:5px;width:50px;font-weight:bold;"><span class="text-success">'.$game_apis_map[$v['game']].'</span> </td>';
								}else{
									$bet_details_str .= '<td style="text-align:right;padding:5px;width:50px;"><span class="text-danger">'.$v['game'].'</span> </td>';
								}
								$bet_details_str .= '<td style="text-align:right;padding:5px;"> <span>'.$this->data_tables->currencyFormatter($v['bet']).'</span></td>';
								$bet_details_str .= '<td style="text-align:right;padding:5px;"> <span>'.$this->data_tables->currencyFormatter($v['payout']).'</span></td>';
								$bet_details_str .= '<td style="text-align:right;padding:5px;"> <span>'.$this->data_tables->currencyFormatter($v['payout_rate']*100).'%</span></td>';
								$bet_details_str .=  '</tr>';
							}

							$bet_details_str .=  '<tr>';
							$bet_details_str .=  '<td style="text-align:right;padding:5px;font-weight:bold;"><span class="text-primary">Total</span> </td> ';
							$bet_details_str .=  '<td style="text-align:right;padding:5px;font-weight:bold;"><span class="text-primary">'.$this->data_tables->currencyFormatter($total_bets_).'</span> </td> ';
							$bet_details_str .=  '<td style="text-align:right;padding:5px;font-weight:bold;"><span class="text-primary">'.$this->data_tables->currencyFormatter($total_payout_).'</span> </td> ';
							$bet_details_str .=  '<td style="text-align:right;padding:5px;font-weight:bold;"><span class="text-primary">'.@$this->data_tables->currencyFormatter(($total_payout_/$total_bets_)*100).'%</span> </td> ';
							$bet_details_str .=  '</tr>';

							return $bet_details_str;
						} else {
                            $details = [];
							foreach ($bet_details as $v) {
								$total_bets_ += $v['bet'];
								$total_payout_ += $v['payout'];
								$total_rate_ += $v['payout_rate'];

								$game =  (array_key_exists($v['game'], $game_apis_map)) ? $game_apis_map[$v['game']] : $v['game'];

								$game_row = array( 'Game Platform' => $game,
									'Bets' => $this->data_tables->currencyFormatter($v['bet']),
							    	'Payout'=> $this->data_tables->currencyFormatter($v['payout']),
		                        	'Payout_rate' => $this->data_tables->currencyFormatter($v['payout_rate']*100).'%'
		                        );

		                       $total_payout_rate_ = @$this->data_tables->currencyFormatter(($total_payout_/$total_bets_)*100);
		                        array_push($details, $game_row);
                            }
                            return json_encode(array('total_bets' => $total_bets_,'total_payout' => $total_payout_, 'total_payout_rate'=> $total_payout_rate_, 'details' => $details));
                        }
					}else{
						return "-";
					}
				},
			),
		);

		# OUTPUT #####################################################################################################################
		#SYSTEM FEATURES
		$columnsToDisplayOrNot = array(
			'risk_level' => array('field'=>'risk_level','showtThis' => 'yes',  'feature' => 'show_risk_score'),
			'kyc_level' => array('field'=>'kyc_level','showtThis' => 'yes', 'feature' => 'show_kyc_status')
		);

	    #CONTROL THIS WHEN USING SBE LOTTERY
		if(!$this->utils->isEnabledFeature('close_aff_and_agent')){
			$columnsToDisplayOrNot['affiliates_name'] = array('field'=>'affiliates_name','showtThis' => 'yes', 'feature' => 'show_search_affiliate');
			// $columnsToDisplayOrNot['agent'] = array('field'=>'agent','showtThis' => 'yes',  'feature' => 'agency');
		}else{
			$columnsToDisplayOrNot['affiliates_name'] = array('field'=>'affiliates_name','showtThis' => 'no', 'feature' => 'show_search_affiliate');
			// $columnsToDisplayOrNot['agent'] = array('field'=>'agent','showtThis' => 'no',  'feature' => 'agency');
		}

		$columns= $this->checkIfEnable($columnsToDisplayOrNot, $columns);

		#EXPORT TRIGGER
		if (isset($input['exportSelectedColumns'])) {
			$columns = $this->getSelectedColumns(explode(",", $input['exportSelectedColumns']), $columns);
		}
		if($is_export){
            $this->data_tables->options['is_export']=true;
			// $this->data_tables->options['only_sql']=true;
            if(empty($csv_filename)){
                $csv_filename=$this->utils->create_csv_filename(__FUNCTION__);
            }
            $this->data_tables->options['csv_filename']=$csv_filename;
		}
		$result = $this->data_tables->get_data($request, $columns, $table, $where, $values, $joins, $group_by, $having);

		if($is_export){
		    //drop result if export
			return $csv_filename;
		}
		#EXPORT - FOR VIEWING FIELDS TO EXPORT ON VIEW
		$result['cols_names_aliases'] = $this->get_dt_column_names_and_aliases($columns);

  //       if($is_export){
  //           $this->data_tables->options['is_export']=true;
  //           if(empty($csv_filename)){
  //               $csv_filename=$this->utils->create_csv_filename(__FUNCTION__);
  //           }
  //           $this->data_tables->options['csv_filename']=$csv_filename;
		// }

		$subtotals['subtotals_payout_rate']  = @$this->data_tables->percentageFormatter($subtotals['subtotals_total_payout']/$subtotals['subtotals_total_bets']).'%';
		$result['subtotals'] = $subtotals;



		$summary = $this->data_tables->summary($request, $table, $joins, 'SUM(CASE WHEN transactions.transaction_type = ' . Transactions::AUTO_ADD_CASHBACK_TO_BALANCE . ' THEN transactions.amount ELSE 0 END) total_cashback', null, $columns, $where, $values);
		$result['summary'][0]['total_cashback'] = $this->utils->formatCurrencyNoSym($summary[0]['total_cashback']);

		$summary = $this->data_tables->summary($request, $table, $joins, 'SUM(CASE WHEN transactions.transaction_type = ' . Transactions::MEMBER_GROUP_DEPOSIT_BONUS . ' THEN transactions.amount ELSE 0 END) total_deposit_bonus', null, $columns, $where, $values);
		$result['summary'][0]['total_deposit_bonus'] = $this->utils->formatCurrencyNoSym($summary[0]['total_deposit_bonus']);

		$summary = $this->data_tables->summary($request, $table, $joins, 'SUM(CASE WHEN transactions.transaction_type = ' . Transactions::PLAYER_REFER_BONUS . ' THEN transactions.amount ELSE 0 END) total_bonus', null, $columns, $where, $values);
		$result['summary'][0]['total_bonus'] = $this->utils->formatCurrencyNoSym($summary[0]['total_bonus']);

		$summary = $this->data_tables->summary($request, $table, $joins, 'SUM(CASE WHEN transactions.transaction_type = ' . Transactions::ADD_BONUS . ' THEN transactions.amount ELSE 0 END) total_add_bonus', null, $columns, $where, $values);
		$result['summary'][0]['total_add_bonus'] = $this->utils->formatCurrencyNoSym($summary[0]['total_add_bonus']);

		$summary = $this->data_tables->summary($request, $table, $joins, 'SUM(CASE WHEN transactions.transaction_type = ' . Transactions::SUBTRACT_BONUS . ' THEN transactions.amount ELSE 0 END) total_sub_bonus', null, $columns, $where, $values);
		$result['summary'][0]['total_sub_bonus'] = $this->utils->formatCurrencyNoSym($summary[0]['total_sub_bonus']);

		$summary = $this->data_tables->summary($request, $table, $joins, 'SUM(CASE WHEN transactions.transaction_type IN (' . implode(',', array(Transactions::MEMBER_GROUP_DEPOSIT_BONUS, Transactions::PLAYER_REFER_BONUS, Transactions::ADD_BONUS)) . ') THEN transactions.amount ELSE 0 END) total_total_bonus', null, $columns, $where, $values);
		$result['summary'][0]['total_total_bonus'] = $this->utils->formatCurrencyNoSym($summary[0]['total_total_bonus']);

		$summary = $this->data_tables->summary($request, $table, $joins, 'SUM(CASE WHEN transactions.transaction_type IN (' . Transactions::DEPOSIT . ') THEN transactions.amount ELSE 0 END) total_deposit', null, $columns, $where, $values);
		$result['summary'][0]['total_deposit'] = $this->utils->formatCurrencyNoSym($summary[0]['total_deposit']);

		$summary = $this->data_tables->summary($request, $table, $joins, 'COUNT(CASE WHEN transactions.transaction_type IN (' . Transactions::DEPOSIT . ') THEN 1 END) total_deposit_times', null, $columns, $where, $values);
		$result['summary'][0]['total_deposit_times'] = $summary[0]['total_deposit_times'];

		$summary = $this->data_tables->summary($request, $table, $joins, 'SUM(CASE WHEN transactions.transaction_type IN (' . implode(',', array(Transactions::WITHDRAWAL)) . ') THEN transactions.amount ELSE 0 END) total_withdrawal', null, $columns, $where, $values);
		$result['summary'][0]['total_withdrawal'] = $this->utils->formatCurrencyNoSym($summary[0]['total_withdrawal']);

		$summary = $this->data_tables->summary($request, $table, $joins, 'SUM(CASE WHEN transactions.transaction_type IN (' . implode(',', array(Transactions::DEPOSIT)) . ') THEN transactions.amount ELSE 0 END) - SUM(CASE WHEN transactions.transaction_type IN (' . implode(',', array(Transactions::WITHDRAWAL)) . ') THEN transactions.amount ELSE 0 END) total_dw', null, $columns, $where, $values);
		$result['summary'][0]['total_dw'] = $this->utils->formatCurrencyNoSym($summary[0]['total_dw']);

		/**
		 * Manual Query for Total First Deposit, Total Second Deposit, Total Bets, Payout and Payout Rate
		 * @param string $date_from $date_to
		 * ©ivan.php.ph
		 * Owned by : Triple One Tech
		 * Date: 08-06-2018
		*/
		if (isset($input['date_from'], $input['date_to'])) {
			$date_from = $input['date_from'];
			$date_to = $input['date_to'];
		}

		if (isset($input['playerlevel'])) {
			$playerlvl = $input['playerlevel'];
			if (!empty($playerlvl)) {
				$playerlvls = " AND player.levelId = ".$playerlvl."";
			}
		}

		if (isset($input['group_by'])) {
			switch ($input['group_by']) {
				case 'player_id':
				$group_bys = 'player.playerId';
				break;
				case 'playerlevel':
				$group_bys = 'player.levelId';
				break;
				case 'affiliate_id':
				$group_bys = 'player.affiliateId';
				break;
				case 'agent_id':
				$group_bys = 'player.agent_id';
				break;
				default:
				//
				break;
			}
		}

		$query = $readOnlyDB->query("SELECT player.playerId playerId, affiliates.affiliateId affiliateId, player.levelId levelId FROM (`player`) LEFT JOIN `playerdetails` ON playerdetails.playerId = player.playerId LEFT JOIN `player_runtime` ON player_runtime.playerId = player.playerId LEFT JOIN `transactions` ON transactions.to_id = player.playerId LEFT JOIN `affiliates` ON affiliates.affiliateId = player.affiliateId WHERE `transactions`.`to_type` = 2 AND transactions.status = 1 AND transactions.created_at BETWEEN ? AND ?".$playerlvls." AND player.deleted_at IS NULL GROUP BY ".$group_bys."", array($date_from, $date_to));
		$rows = $query->result_array();
		foreach( $rows as $row )
		{
			$total['total_player_id'] = $row['playerId'];
			$total['total_player_levelId'] = $row['levelId'];

			# total first deposit
			$query = $readOnlyDB->query("SELECT transactions.amount FROM transactions JOIN player ON player.playerId = transactions.to_id WHERE transactions.to_type = ?  AND transactions.transaction_type = ? AND transactions.status = ?  AND transactions.to_id  = ".$total['total_player_id']."  AND player.deleted_at IS NULL ORDER BY created_at ASC LIMIT ?,1", array( Transactions::PLAYER, Transactions::DEPOSIT, Transactions::APPROVED, 0, ));
			$row = $query->row_array();
			$total['total_first_deposit'] += isset($row['amount']) ? $row['amount'] : 0;

			#total second deposit
			$query = $readOnlyDB->query("SELECT transactions.amount as amounts FROM transactions JOIN player ON player.playerId = transactions.to_id WHERE transactions.to_type = ?  AND transactions.transaction_type = ? AND transactions.status = ?  AND transactions.to_id  = ".$total['total_player_id']."  AND player.deleted_at IS NULL ORDER BY created_at ASC LIMIT ?,1", array( Transactions::PLAYER, Transactions::DEPOSIT, Transactions::APPROVED, 1, ));
			$row = $query->row_array();
			$total['total_second_deposit'] += isset($row['amounts']) ? $row['amounts'] : 0;

			#total bet add
			$queryTotalBet= "SELECT SUM(betting_amount) AS total_bets FROM total_player_game_hour WHERE player_id = ".$total['total_player_id']."  ".$daterange_hour." ";
			# overrive query if group by player level
			if (isset($input['group_by']) && strtolower($input['group_by']) == "playerlevel") {
				$queryTotalBet = "SELECT SUM(betting_amount) AS total_bets FROM total_player_game_hour WHERE player_id IN (SELECT playerId FROM player where levelId = ".$total['total_player_levelId'].")  ".$daterange_hour;
			}
			$query = $readOnlyDB->query($queryTotalBet);
			$row = $query->row_array();
			$total['total_bets_add'] += isset($row['total_bets']) ? $row['total_bets'] : 0 ;

			#total payout add
			$queryTotalPayout = "SELECT SUM(loss_amount - win_amount) AS total_payout FROM total_player_game_hour WHERE player_id = ".$total['total_player_id']."  ".$daterange_hour."";
			# overrive query if group by player level
			if (isset($input['group_by']) && strtolower($input['group_by']) == "playerlevel") {
				$queryTotalPayout = "SELECT SUM(loss_amount - win_amount) AS total_payout FROM total_player_game_hour WHERE player_id in (SELECT playerId FROM player where levelId = ".$total['total_player_levelId'].") ". $daterange_hour;
			}
			$query = $readOnlyDB->query($queryTotalPayout);
			$row = $query->row_array();
			$total['total_payout_add'] += isset($row['total_payout']) ? $row['total_payout'] : 0 ;
		}
		#total payout rate
		$total['total_payout_rate'] = @$this->data_tables->percentageFormatter($total['total_payout_add']/$total['total_bets_add']).'%';

		$result['total'] = $total;


		return $result;
	}

	/**
	 * Get the datetime after offset by timezone.
	 *
	 * @param string $input_timezone
	 * @param string $input_datetime
	 * @return string
	 */
	private function _getDatetimeWithTimezone($input_timezone, $input_datetime){
		/// for apply the timezone,
		// override the inputs, deposit_date_from and deposit_date_to.
		if( ! empty($input_timezone) ){
			$default_timezone = $this->utils->getTimezoneOffset(new DateTime());
			$hours = $default_timezone - intval($input_timezone); // $input['timezone']);
			$date_from_str = $input_datetime; // $input['deposit_date_from'];
			$by_date_from = new DateTime($date_from_str);
			if($hours>0){
				$hours='+'.$hours;
			}
			$by_date_from->modify("".$hours." hours");
			$input_datetime = $this->utils->formatDateTimeForMysql($by_date_from);
		}
		return $input_datetime;
	} // EOF _getDatetimeWithTimezone

    public function player_reports_2($request, $viewPlayerInfoPerm, $is_export){
        $readOnlyDB = $this->getReadOnlyDB();

        $this->load->library('data_tables', array("DB" => $readOnlyDB));
        $this->load->model(array('risk_score_model','player_kyc','kyc_status_model','agency_model', 'transactions', 'affiliatemodel', 'affiliate', 'player_promo', 'promorules'));
        $this->load->helper(['player_helper']);
        $game_apis_map= $this->utils->getAllSystemMap();

        $sum_add_bonus_as_manual_bonus = $this->utils->getConfig('sum_add_bonus_as_manual_bonus');
        $sum_deposit_promo_bonus_as_total_deposit_bonus = $this->utils->getConfig('sum_deposit_promo_bonus_as_total_deposit_bonus');
        $player_kyc = $this->player_kyc;
        $kyc_status_model = $this->kyc_status_model;
        $risk_score_model = $this->risk_score_model;

        $this->data_tables->is_export = $is_export;

        $table = 'player_report_hourly';
        $joins = array();
        $where = array();
        $values = array();
        $group_by = array();
        $having = array();
        $joins['vipsettingcashbackrule'] = 'vipsettingcashbackrule.vipsettingcashbackruleId = player_report_hourly.level_id';
        $joins['vipsetting'] = 'vipsetting.vipSettingId = vipsettingcashbackrule.vipSettingId';
        $joins['player'] = "player.playerId = player_report_hourly.player_id";
        $joins['player_last_transactions'] = "player_last_transactions.player_id = player.playerId";
        $i = 0;
        $input = $this->data_tables->extra_search($request);

		if( empty($input['timezone']) ){
			$input['timezone'] = 0; // for undefined issue
		}

        $date_from = null;
        $date_to = null;
        $playerlvl = null;
        $playerlvls = null;
        if (isset($input['date_from'], $input['date_to'])) {
            // $date_from = $input['date_from'];
            // $date_to = $input['date_to'];

			$date_from = $this->_getDatetimeWithTimezone($input['timezone'], $input['date_from']);
			$date_to = $this->_getDatetimeWithTimezone($input['timezone'], $input['date_to']);
        }

        # FILTER ######################################################################################################################################################################################

		$show_username            = true;
		$show_realname            = true;
		$show_tag                 = true;
		$show_risk_level          = true;
		$show_kyc_level           = true;
		$show_player_level        = true;
		$show_affiliate           = true;
		$show_agent               = true;
		$show_register_date       = true;
		$show_deposit_bonus       = true;
		$show_cashback_bonus      = true;
		$show_referral_bonus      = true;
		$show_manual_bonus        = true;
		$show_subtract_bonus      = true;
		$show_total_bonus         = true;
		$show_subtract_balance    = true;
		$show_first_deposit       = true;
		$show_first_deposit_date  = true;
		$show_total_deposit       = true;
		$show_total_deposit_times = true;
		$show_total_withdrawal    = true;
		$show_total_dw            = true;
		$show_total_bets          = true;
		$show_total_win           = true;
		$show_total_loss          = true;
		$show_total_payout        = true;
		$show_payout_rate         = true;
		$show_total_revenue       = true;
        $show_last_login_date     = true;
        $show_last_deposit_date   = true;
        $show_net_loss            = true;
        $show_account_status      = true;

        if (isset($input['group_by'])) {
            switch ($input['group_by']) {
                case 'player_id':
                    $group_by[] = 'player_report_hourly.player_id';
                    break;

                case 'playerlevel':
                    $group_by[] = 'level_id';
                    $show_username = false;
                    $show_realname = false;
                    $show_tag = false;
                    $show_risk_level = false;
                    $show_kyc_level =false;
                    $show_player_level = true;
                    $show_affiliate = false;
                    $show_agent= false;
                    $show_register_date = false;
                    $show_account_status = false;
                    $show_last_deposit_date   = false;
                    break;
                case 'affiliate_id':
                    $group_by[] = 'affiliate_id';
                    $show_username = false;
                    $show_realname = false;
                    $show_tag = false;
                    $show_risk_level = false;
                    $show_kyc_level =false;
                    $show_player_level = false;
                    $show_affiliate = true;
                    $show_agent= false;
                    $show_register_date = false;
                    $show_account_status = false;
                    $show_last_deposit_date   = false;
                    break;

                case 'agent_id':
                    $group_by[] = 'player_report_hourly.agent_id';
                    $show_username = false;
                    $show_realname = false;
                    $show_tag = false;
                    $show_risk_level = false;
                    $show_kyc_level =false;
                    $show_player_level = false;
                    $show_affiliate = false;
                    $show_agent= true;
                    $show_register_date = false;
                    $show_account_status = false;
                    $show_last_deposit_date   = false;
                    break;

                default;
            }
        }

        if($show_last_login_date){
            $joins['player_runtime'] = "player_runtime.playerId = player.playerId"; //for last login details tal
        }


        // if($this->utils->getConfig('display_last_deposit_col') == true){
        //     $joins['(select max(player_submit_datetime ) player_submit_datetime, player_id, `status`  from sale_orders where sale_orders.status=5 GROUP BY player_id) sale_orders'] = "sale_orders.player_id = player.playerId";
        // }

        $dateTimeFrom = null;
        $dateTimeTo = null;
        if (isset($input['date_from'], $input['date_to'])) {
            $dateTimeFrom = $input['date_from'];
            $dateTimeTo   = $input['date_to'];

			/// for apply the timezone,
			// override the inputs, deposit_date_from and deposit_date_to.
			$dateTimeFrom = $this->_getDatetimeWithTimezone($input['timezone'], $dateTimeFrom);
			$dateTimeTo = $this->_getDatetimeWithTimezone($input['timezone'], $dateTimeTo);

            $where[]  = "player_report_hourly.date_hour >= ? AND player_report_hourly.date_hour <= ?";
            $values[] = $this->utils->formatDateHourForMysql(new DateTime($dateTimeFrom));
            $values[] = $this->utils->formatDateHourForMysql(new DateTime($dateTimeTo));
        }

		if (!empty($input['search_reg_date']) && $input['search_reg_date'] == 'on') {
			if (isset($input['registration_date_from'], $input['registration_date_to'])) {
				$where[] = "player.createdOn >= ?";
				$where[] = "player.createdOn <= ?";
				$values[] = $input['registration_date_from'];
				$values[] = $input['registration_date_to'];
			}
		}

        $approved_player_promo_range = '';
        $date_range_hour = '';
        if (!empty($dateTimeFrom) && !empty($dateTimeTo)) {
            $date_from_hour = (new DateTime($date_from))->format('YmdH');
            $date_to_hour = (new DateTime($date_to))->format('YmdH');
            $date_range_hour = " AND date_hour >=  {$date_from_hour} AND date_hour <=  {$date_to_hour}";

            if($sum_add_bonus_as_manual_bonus || $sum_add_bonus_as_manual_bonus){
                $player_prommo_from = (new DateTime($date_from))->format('Y-m-d H:i:s');
                $player_promo_to = (new DateTime($date_to))->format('Y-m-d H:i:s');
                $approved_player_promo_range = " AND dateProcessed >= '{$player_prommo_from}' AND dateProcessed <= '{$player_promo_to}'";
            }
        }

        if($sum_add_bonus_as_manual_bonus){
            $joins['(SELECT playerId, SUM(bonusAmount) as add_bonus FROM playerpromo WHERE order_generated_by = "'.Player_promo::ORDER_GENERATED_BY_SBE_ADD_BONUS.'"'.$approved_player_promo_range.' GROUP BY playerId ) AS playerpromo_filter'] = 'playerpromo_filter.playerId = player_report_hourly.player_id';
        }

        if($sum_deposit_promo_bonus_as_total_deposit_bonus){
            $joins['(SELECT playerId, SUM(playerpromo.bonusAmount) as deposit_promo_bonus FROM playerpromo LEFT JOIN promorules ON promorules.promorulesId = playerpromo.promorulesId WHERE promorules.promoType = "'.Promorules::PROMO_TYPE_DEPOSIT.'"'.$approved_player_promo_range.' GROUP BY playerId ) AS deposit_promo_filter'] = 'deposit_promo_filter.playerId = player_report_hourly.player_id';
        }

        if (isset($input['username'])) {
            $where[] = "player_username LIKE ?";
            $values[] = '%' . $input['username'] . '%';
        }

        if (isset($input['referrer'])) {

			$refereePlayerId = $this->player_model->getPlayerIdByUsername($input['referrer']) ? : -1;
			// $joins['player'] = "player.playerId = player_report_hourly.player_id";
            $where[] = "player.refereePlayerId = ?";
            $values[] = $refereePlayerId;
        }


        if (isset($input['playerlevel'])) {
            $where[] = "level_id = ?";
            $values[] = $input['playerlevel'];
        }

        if (isset($input['depamt1'])) {
            $having['total_deposit <='] = $input['depamt1'];
        }

        if (isset($input['depamt2'])) {
            $having['total_deposit >='] = $input['depamt2'];
        }

        if (isset($input['turnovermt_greater_than'])) {
            $having['total_bets >='] = $input['turnovermt_greater_than'];
        }

        if (isset($input['turnovermt_less_than'])) {
            $having['total_bets <='] = $input['turnovermt_less_than'];
        }

        if (isset($input['widamt1'])) {
            $having['total_withdrawal <='] = $input['widamt1'];
        }

        if (isset($input['widamt2'])) {
            $having['total_withdrawal >='] = $input['widamt2'];
        }
        if (isset($input['only_under_agency']) && $input['only_under_agency'] != '') {
            $where[] = "player_report_hourly.agent_id IS NOT NULL";
            if (!isset($input['agent_name'])) {
                if (isset($input['current_agent_name']) && $input['current_agent_name'] != '') {
                    $input['agent_name'] = $input['current_agent_name'];
                }
            }
        }
        if (isset($input['agent_name'])) {
            $agent_detail = $this->agency_model->get_agent_by_name($input['agent_name']);

            if (isset($input['include_all_downlines']) && $input['include_all_downlines'] == true) {
                $joins['agency_agents'] = 'player_report_hourly.agent_id = agency_agents.agent_id';
                $parent_ids = array($agent_detail['agent_id']);
                $all_ids = $parent_ids;
                // $sub_ids = $this->agency_model->get_sub_agent_ids_by_parent_id($parent_ids);
                while (!empty($sub_ids = $this->agency_model->get_sub_agent_ids_by_parent_id($parent_ids))) {
                    $this->utils->debug_log('sub_ids', $sub_ids);
					$all_ids = array_merge($all_ids, $sub_ids);
					$parent_ids = $sub_ids;
                    $sub_ids = array();
                }
                $w = '';
                foreach ($all_ids as $i => $id) {
                    if ($i == 0) {
                        $w = "(player_report_hourly.agent_id = ?";
                    } else {
                        $w .= " OR player_report_hourly.agent_id = ?";
                    }
                    $values[] = $id;
                }
                $w .= ")";
                $where[] = $w;
            } else {
                $where[] = "player_report_hourly.agent_id = ?";
                $values[] = $agent_detail['agent_id'];
            }
        }

        if (isset($input['affiliate_name'])) {

            $affiliateId = $this->affiliatemodel->getAffiliateIdByUsername($input['affiliate_name']);
            @$this->utils->debug_log('affiliateId', $$affiliateId);
            $affiliateIds = null;

            if (isset($input['aff_include_all_downlines']) && $input['aff_include_all_downlines']
                && !empty($affiliateId)) {
                $affiliateIds = $this->affiliatemodel->includeAllDownlineAffiliateIds($affiliateId);
            }

            if (!empty($affiliateIds)) {
                $where[] = 'affiliate_id IN(' . implode(',', $affiliateIds) . ')';
            } else {
                $where[] = "affiliate_username = ?";
                $values[] = $input['affiliate_name'];
            }
        }

        $affiliatesTagMap = [];

        if (isset($input['affiliate_tags'])) {

            $affiliatesTagMap = $this->affiliate->getAffTagsMap();

            $this->utils->debug_log('affiliatesTagMap1', $affiliatesTagMap);

            $joins['affiliatetag'] = 'affiliatetag.affiliateId=affiliate_id';
            $joins['affiliatetaglist'] = 'affiliatetaglist.tagId=affiliatetag.tagId';
            $where[] = "affiliatetag.tagId = ?";
            $values[] = $input['affiliate_tags'];
        }

        if (isset($input['player_tag'])) {
            $joins['playertag'] = 'playertag.playerId = player_id';
            $where[] = "playertag.tagId = ?";
            $values[] = $input['player_tag'];
		}

		if (isset($input['tag_list'])) {
            $tag = $input['tag_list'];
            $tagList = [];
            if(!is_array($tag)){
                array_push($tagList, $tag);
            }else{
                $tagList = $tag;
            }

            $notag = array_search('notag',$tagList);
            if($notag === false){
                $joins['(SELECT DISTINCT playerId FROM playertag WHERE playertag.tagId IN ('.implode(',', $tagList).')) AS exclude_list_filter'] = 'exclude_list_filter.playerId = player_report_hourly.player_id';
                $where[] = 'exclude_list_filter.playerId IS NULL';
            }else{
                $joins['(SELECT DISTINCT playerId FROM playertag) AS exclude_filter'] = 'exclude_filter.playerId = player_report_hourly.player_id';
                $where[] = 'exclude_filter.playerId IS NOT NULL';
                unset($tagList[$notag]);

                if(!empty($tagList)){
                    $joins['(SELECT DISTINCT playerId FROM playertag WHERE playertag.tagId NOT IN ('.implode(',', $tagList).')) AS exclude_list_filter'] = 'exclude_list_filter.playerId = player_report_hourly.player_id';
                    $where[] = 'exclude_list_filter.playerId IS NOT NULL';
                }
            }
		}

		if (isset($input['tag_list_included'])) {
			$tagIncluded = $input['tag_list_included'];
            $tagListIncluded = [];
            if(!is_array($tagIncluded)){
                array_push($tagListIncluded, $tagIncluded);
            }else{
                $tagListIncluded = $tagIncluded;
            }

            $notag = array_search('notag',$tagListIncluded);
            if($notag === false){
                $joins['(SELECT DISTINCT playerId FROM playertag WHERE playertag.tagId IN ('.implode(',', $tagListIncluded).')) AS include_list_filter'] = 'include_list_filter.playerId = player_report_hourly.player_id';
                $where[] = 'include_list_filter.playerId IS NOT NULL';
            }else{
                $only_has_notag = (count($tagListIncluded) == 1);
                if($only_has_notag){
                    $joins['(SELECT DISTINCT playerId FROM playertag) AS include_filter'] = 'include_filter.playerId = player_report_hourly.player_id';
                    $where[] = 'include_filter.playerId IS NULL';
                }else{
                    unset($tagListIncluded[$notag]);
                    $joins['(SELECT DISTINCT playerId FROM playertag WHERE playertag.tagId IN ('.implode(',', $tagListIncluded).')) AS include_list_filter'] = 'include_list_filter.playerId = player_report_hourly.player_id';
                    $where[] = 'include_list_filter.playerId IS NOT NULL';
                }
            }
		}

        if (isset($input['affiliate_agent'])) {
            switch ($input['affiliate_agent']) {

                case '1': # Not under any Affiliate or Agent
                    $where[] = '(player_report_hourly.agent_id IS NULL OR player_report_hourly.agent_id = 0) AND (affiliate_id IS NULL OR affiliate_id = 0)';
                    break;

                case '2': # Under Affiliate Only
                    $where[] = '(player_report_hourly.agent_id IS NULL OR player_report_hourly.agent_id = 0) AND affiliate_id > 0';
                    break;

                case '3': # Under Agent Only
                    $where[] = 'player_report_hourly.agent_id > 0 AND (affiliate_id IS NULL OR affiliate_id = 0)';
                    break;

                case '4': # Under Affiliate or Agent
                    $where[] = '(player_report_hourly.agent_id > 0 OR affiliate_id > 0)';
                    break;
            }

		}

        if (isset($input['username'], $input['search_by'])) {
            if ($input['search_by'] == 1) {
                $where[] = "player_username LIKE ?";
                $values[] = '%' . $input['username'] . '%';
            } else if ($input['search_by'] == 2) {
                $where[] = "player_username = ?";
                $values[] = $input['username'];
            }
        }

        $subtotals = ['subtotals_deposit_bonus'=> 0 , 'subtotals_cashback_bonus' => 0, 'subtotals_referral_bonus' => 0,
            'subtotals_manual_bonus'=>0, 'subtotals_subtract_bonus' => 0, 'subtotals_total_bonus'=> 0, 'subtotals_subtract_balance'=> 0, 'subtotals_first_deposit' => 0,
            'subtotals_second_deposit' => 0,'subtotals_total_deposit' => 0, 'subtotals_total_deposit_times' => 0,'subtotals_total_withdrawal'=> 0,
            'subtotals_total_bets' => 0, 'subtotals_total_payout'=> 0, 'subtotals_total_win' => 0, 'subtotals_total_loss' => 0,
            'subtotals_total_dw'=> 0, 'subtotals_payout_rate' => 0,
            'subtotals_dnb' => 0, 'subtotals_bod' => 0, 'subtotals_wod' => 0, 'subtotals_tat' => 0, 'subtotals_win' => 0, 'subtotals_loss' => 0
        ];
        # DEFINE TABLE COLUMNS ########################################################################################################################################################################
        $columns = array(
            array(
                'select' => 'affiliate_id'
			),
			array(
                'select' => 'player.levelId',
                'alias' => 'level_id'
            ),
            array(
                'select' => 'player_report_hourly.agent_id',
                'alias' => 'prh_agent_id'
            ),
            array(
                'select' => 'player.levelName',
                'alias' => 'level_name'
            ),
            array(
                'select' => 'vipLevelName'
			),
			array(
                'select' => 'player_report_hourly.player_id',
                'alias' => 'player_id'
			),
            array(
                'dt' => $i++,
                'alias' => 'username',
                'name' => lang('report.pr01'),
                'select' => 'player_username',
                'formatter' => function ($d, $row) use ($is_export, $date_from, $date_to, $show_username) {

                    if($show_username){
                        if ($is_export) {
                            return $d;
                        } else {
                            $date_qry = '';
                            if (!empty($date_from) && !empty($date_to)) {
                                $date = new DateTime($date_from);
                                $date_qry = '&date_from=' . $date->format('Y-m-d') . '&hour_from=' . $date->format('H');

                                $date = new DateTime($date_to);
                                $date_qry .= '&date_to=' . $date->format('Y-m-d') . '&hour_to=' . $date->format('H');
                            }
                            return '<a href="/player_management/userInformation/' . $row['player_id'] . '">' . $d . '</a>';
                        }
                    }else{
                        if($is_export){
                            return lang('lang.norecyet');
                        }
                        return  '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
                    }
                },
            ),
            array(
                'dt' => $i++,
                'alias' => 'tagName',
                'select' => 'player_report_hourly.player_id',
                'name' => lang("player.41"),
                'formatter' => function ($d) use ($is_export, $show_tag) {
                    if($show_tag){
                        return player_tagged_list($d, $is_export);
                    }else{
                        if($is_export){
                            return lang('lang.norecyet');
                        }
                        return  '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
                    }
                },
            ),
            array(
                'dt' => $i++,
                'alias' => 'risk_level',
                'name' => lang("Risk Level/Score"),
                'select' => 'player_report_hourly.player_id',
                'formatter' => function ($d) use ($is_export,$risk_score_model,$show_risk_level) {
                    if($show_risk_level){
                        if ($is_export) {
                            return $risk_score_model->getPlayerCurrentRiskLevel($d) && $risk_score_model->generate_total_risk_score($d) ? $risk_score_model->getPlayerCurrentRiskLevel($d) .' / '. $risk_score_model->generate_total_risk_score($d) : lang('lang.norecyet');
                        }else{
                            return $risk_score_model->getPlayerCurrentRiskLevel($d) && $risk_score_model->generate_total_risk_score($d) ? $risk_score_model->getPlayerCurrentRiskLevel($d) .' / '. $risk_score_model->generate_total_risk_score($d) : '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
                        }
                    }else{
                        if($is_export){
                            return lang('lang.norecyet');
                        }
                        return  '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
                    }
                },
            ),
            array(
                'dt' => $i++,
                'alias' => 'kyc_level',
                'name' => lang("KYC Level/Rate Code"),
                'select' => 'player_report_hourly.player_id',
                'formatter' => function ($d) use ($is_export,$player_kyc,$kyc_status_model,$show_kyc_level) {
                    if($show_kyc_level){
                        if ($is_export) {
                            return $player_kyc->getPlayerCurrentKycLevel($d) && $kyc_status_model->getPlayerCurrentStatus($d) ? $player_kyc->getPlayerCurrentKycLevel($d) .' / '. $kyc_status_model->getPlayerCurrentStatus($d): lang('lang.norecyet');
                        }else{
                            return $player_kyc->getPlayerCurrentKycLevel($d) && $kyc_status_model->getPlayerCurrentStatus($d) ? $player_kyc->getPlayerCurrentKycLevel($d) .' / '. $kyc_status_model->getPlayerCurrentStatus($d): '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
                        }
                    }else{
                        if($is_export){
                            return lang('lang.norecyet');
                        }
                        return  '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
                    }
                },
            ),
            array(
                'dt' => $i++,
                'name' => lang('report.pr03'),
                'alias' => 'member_level',
                'select' => 'player.groupName',
                'formatter' => function ($d, $row) use ($show_player_level, $is_export) {
                    if(($show_player_level)){
                        return lang($d)." - ".lang($row['level_name']);
                    }else{
                        if($is_export){
                            return lang('lang.norecyet');
                        }
                        return  '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
                    }
                },
            ),
            array(
                'dt' => $i++,
                'alias' => 'blocked',
                'select' => 'player.blocked' ,
                'name' => lang("lang.accountstatus"),
                'formatter' => function ($d, $row) use ($is_export, $show_account_status){
                    if($show_account_status){
                        $formatter = 1;
                        $lang = array();
                        $lang['lang.active'] = lang('status.normal');
                        $lang['Blocked'] = lang('player_list.options.blocked');
                        $lang['Suspended'] = lang('player_list.options.suspended');
                        $lang['Self Exclusion'] = lang('player_list.options.self_exclusion');
                        $lang['Failed Login Attempt'] = lang('player_list.options.failed_login_attempt');

                        $isBlockedUntilExpired_rlt = $this->player_model->isBlockedUntilExpired($row['player_id']);
                        if( $isBlockedUntilExpired_rlt['isBlocked']
                            && $isBlockedUntilExpired_rlt['isExpired']
                        ){  // reload
                            $d = $isBlockedUntilExpired_rlt['row']['blocked'];
                        }

                        return  $this->utils->getPlayerStatus($row['player_id'],$formatter,$d,$is_export, $lang);
                    }else{
                        if($is_export){
                            return lang('lang.norecyet');
                        }
                        return  '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
                    }
                },
            ),
            array(
                'dt' => $i++,
                'alias' => 'affiliates_name',
                'name' => lang('Affiliate'),
                'select' => 'affiliate_username',
                'formatter' => function($d,$row) use ($is_export, $show_affiliate, $input, $affiliatesTagMap){
                    if($show_affiliate){
                        $url = site_url('/affiliate_management/userInformation/' . $row['affiliate_id']);
                        $name = '';
                        if($is_export){
                            $name = !empty($d) ? $d :  lang('lang.norecyet') ;
                        }else{
                            if(!empty($d)){
                                if(isset($input['affiliate_tags'])){
                                    if(isset($affiliatesTagMap[$input['affiliate_tags']])){
                                        $name = '<span class="badge badge-info" style="float:right;">'.$affiliatesTagMap[$input['affiliate_tags']].'</span>';
                                    }else{
                                        $name ='';
                                    }
                                }
                                $name .= '<a href="' . $url . '">' . $d . '</a> ';

                            }else{
                                $name = '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
                            }
                        }
                        return $name;
                    }else{
                        if($is_export){
                            return lang('lang.norecyet');
                        }
                        return  '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
                    }
                },
            ),
            array(
                'dt' => $i++,
                'alias' => 'agent',
                'select' => 'agent_username',
                'name' => lang("Agent"),
                'formatter' => function ($d, $row) use ($is_export,$show_agent) {
                    if($show_agent){
                        if ($d != null) {
                            $url = site_url('/agency_management/agent_information/' . $row['prh_agent_id']);
                            if($is_export){
                                return $d;
                            }else{
                                return '<a href="' . $url . '">' . $d . '</a>';
                            }
                        } else {
                            if ($is_export) {
                                return trim(trim($d), ',') ?: lang('lang.norecyet');
                            } else {
                                return trim(trim($d), ',') ?: '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
                            }
                        }
                    }else{
                        if($is_export){
                            return lang('lang.norecyet');
                        }
                        return  '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
                    }
                },
            ),
            array(
                'dt' => $i++,
                'alias' => 'createdOn',
                'name' => lang('report.pr10'),
                'select' => 'registered_date',
                'formatter' =>  function ($d, $row) use ($show_register_date, $is_export){
                    if($show_register_date){
                        return $d;
                    }else{
                        if($is_export){
                            return lang('lang.norecyet');
                        }
                        return  '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
                    }
                }
            ),
            array(
				'dt' => $i++,
				'alias' => 'lastLoginTime',
				'name' => lang('player.42'),
				'select' => 'player_runtime.lastLoginTime',
                'formatter' => function ($d, $row) use ($show_last_login_date, $is_export, &$subtotals) {
                    if($show_last_login_date){
                        return $d;
                    }else{
                        if($is_export){
                            return lang('lang.norecyet');
                        }
                        return  '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
                    }
                },
			),
            array(
                'dt' => $i++,
                'alias' => 'first_deposit_datetime',
                'select' => 'MAX(first_deposit_datetime)',
                'name' => lang('aff.ap06'),
                'formatter' => function ($d, $row) use ($show_first_deposit_date, $is_export, &$subtotals) {
                    if($show_first_deposit_date){
                        return $d;
                    }else{
                        if($is_export){
                            return lang('lang.norecyet');
                        }
                        return  '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
                    }
                },
            ),
            array(
                'dt' => $this->utils->getConfig('display_last_deposit_col') ? $i++ : null,
                'alias' => 'lastDepositDateTime',
                'select' => 'player_last_transactions.last_deposit_date',
                'name' => lang('player.105'),
                'formatter' => function ($d, $row) use ($show_last_deposit_date, $is_export) {
                    if($show_last_deposit_date){
                        return $d;
                    }else{
                        if($is_export){
                            return lang('lang.norecyet');
                        }
                        return  '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
                    }
                },
            ),
			array(
                'dt' => $this->utils->getConfig('display_last_deposit_col') ? $i++ : null,
                'alias' => 'daysSinceLastDeposit',
                'select' => 'player_last_transactions.last_deposit_date',
                'name' => lang('player.DaysSinceLastDeposit'),
                'formatter' => function ($d, $row) use ($readOnlyDB, $is_export) {
					$today = new DateTime();
					$display_last_deposit_col = new DateTime(date('Y-m-d', strtotime($d)));

					if (!$d || strtotime($d) < 0) {
						$output = $is_export ? lang('lang.norecyet') : '<i>' . lang('lang.norecyet') . '</i>';
					} else {
						$dateDiff = $today->diff($display_last_deposit_col);
						$daysDiff = $dateDiff->days; // 获取时间差的天数部分
						$output = $daysDiff;
					}
					return $output;
				},
            ),
            array(
                'dt' => $i++,
                'alias' => 'cashback',
                'name' => lang('report.sum15'),
                'select' => 'SUM(player_report_hourly.total_cashback)',
                'formatter' =>  function ($d) use ($show_cashback_bonus, $is_export, &$subtotals){
                    if($show_cashback_bonus){
                        $subtotals['subtotals_cashback_bonus'] += $d;
                        return $this->data_tables->currencyFormatter($d);
                    }else{
                        if($is_export){
                            return lang('lang.norecyet');
                        }
                        return  '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
                    }
                }
            ),
            array(
                'dt' => $i++,
                'alias' => 'deposit_bonus',
                'name' => lang('report.pr15'),
                'select' => $sum_deposit_promo_bonus_as_total_deposit_bonus ? 'deposit_promo_filter.deposit_promo_bonus' : 'SUM(deposit_bonus)',
                'formatter' =>  function ($d) use ($show_deposit_bonus, $is_export, &$subtotals){
                    if($show_deposit_bonus){
                        $subtotals['subtotals_deposit_bonus'] += $d;
                        return $this->data_tables->currencyFormatter($d);
                    }else{
                        if($is_export){
                            return lang('lang.norecyet');
                        }
                        return  '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
                    }
                }
            ),
            array(
                'dt' => $i++,
                'alias' => 'referral_bonus',
                'name' => lang('report.pr17'),
                'select' => 'SUM(referral_bonus)',
                'formatter' =>  function ($d) use ($is_export, $show_referral_bonus, &$subtotals){
                    if($show_referral_bonus){
                        $subtotals['subtotals_referral_bonus'] += $d;
                        return $this->data_tables->currencyFormatter($d);
                    }else{
                        if($is_export){
                            return lang('lang.norecyet');
                        }
                        return  '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
                    }
                }
            ),
            array(
                'dt' => $i++,
                'alias' => 'manual_bonus',
                'name' => lang('transaction.manual_bonus'),
                'select' => $sum_add_bonus_as_manual_bonus ? 'playerpromo_filter.add_bonus' : 'SUM(manual_bonus)',
                'formatter' =>  function ($d) use ($show_manual_bonus, $is_export, &$subtotals){
                    if($show_manual_bonus){
                        $subtotals['subtotals_manual_bonus'] += $d;
                        return $this->data_tables->currencyFormatter($d);
                    }else{
                        if($is_export){
                            return lang('lang.norecyet');
                        }
                        return  '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
                    }
                }
            ),
            array(
                'dt' => $i++,
                'alias' => 'subtract_bonus',
                'name' => lang('transaction.transaction.type.10'),
                'select' => 'SUM(subtract_bonus)',
                'formatter' =>  function ($d) use ($show_subtract_bonus, $is_export, &$subtotals){
                    if($show_subtract_bonus){
                        $subtotals['subtotals_subtract_bonus'] -= $d;
                        return $this->data_tables->currencyFormatter($d);
                    }else{
                        if($is_export){
                            return lang('lang.norecyet');
                        }
                        return  '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
                    }
                }
            ),
            array(
                'dt' => $i++,
                'alias' => 'total_bonus',
                'name' => lang('report.pr18'),
                'select' => 'SUM(player_report_hourly.total_bonus)',
                'formatter' =>  function ($d,$row) use ($show_total_bonus, $is_export, &$subtotals){
                    if($show_total_bonus){
                        $subtotals['subtotals_total_bonus'] += $d;
                       // return $this->data_tables->currencyFormatter($d - $row['subtract_bonus']);
                        return $this->data_tables->currencyFormatter($d);
                    }else{
                        if($is_export){
                            return lang('lang.norecyet');
                        }
                        return  '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
                    }
                }
            ),
			array(
                'dt' => $i++,
                'alias' => 'subtract_balance',
                'name' => lang('report.pr35'),
                'select' => 'SUM(subtract_balance)',
                'formatter' =>  function ($d) use ($is_export, $show_subtract_balance, &$subtotals){
                    if($show_subtract_balance){
                        $subtotals['subtotals_subtract_balance'] -= $d;
                        return $this->data_tables->currencyFormatter($d);
                    }else{
                        if($is_export){
                            return lang('lang.norecyet');
                        }
                        return  '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
                    }
                }
            ),
            array(
                'dt' => $i++,
                'alias' => 'first_deposit',
                'select' => 'MAX(first_deposit_amount)',
                'name' => lang('player.75'),
                'formatter' => function ($d, $row) use ($show_first_deposit, $is_export, &$subtotals) {
                    if($show_first_deposit){
                        $subtotals['subtotals_first_deposit'] += $d;
                        return $this->data_tables->currencyFormatter($d);
                    }else{
                        if($is_export){
                            return lang('lang.norecyet');
                        }
                        return  '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
                    }
                },
            ),
            array(
                'dt' => $i++,
                'alias' => 'total_deposit',
                'name' => lang('report.pr21'),
                'select' => 'SUM(player_report_hourly.total_deposit)',
                'formatter' =>  function ($d) use ($show_total_deposit, $is_export, &$subtotals){
                    if($show_total_deposit){
                        $subtotals['subtotals_total_deposit'] += $d;
                        return $this->data_tables->currencyFormatter($d);
                    }else{
                        if($is_export){
                            return lang('lang.norecyet');
                        }
                        return  '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
                    }
                }
            ),
            array(
                'dt' => $i++,
                'alias' => 'total_deposit_times',
                'name' => lang('yuanbao.deposit.times'),
                'select' => 'SUM(deposit_times)',
                'formatter' =>  function ($d) use ($show_total_deposit_times, $is_export, &$subtotals){
                    if($show_total_deposit_times){
                        $subtotals['subtotals_total_deposit_times'] += $d;
                        return $d;
                    }else{
                        if($is_export){
                            return lang('lang.norecyet');
                        }
                        return  '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
                    }
                }
            ),
            array(
                'dt' => $i++,
                'alias' => 'total_deposit_and_bonus',
                'name' => lang('DNB'),
                'select' => 'SUM(player_report_hourly.total_deposit) + SUM(player_report_hourly.total_bonus)',
                'formatter' =>  function ($d) use ($show_total_deposit_times, $is_export, &$subtotals){
                    if($show_total_deposit_times){
                        $subtotals['subtotals_dnb'] += $d;
                        return $this->data_tables->currencyFormatter($d);
                    }else{
                        if($is_export){
                            return lang('lang.norecyet');
                        }
                        return  '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
                    }
                }
            ),
            array(
                'dt' => $i++,
                'alias' => 'total_bonus_over_deposit',
                'name' => lang('BOD%'),
                'select' => 'SUM(player_report_hourly.total_bonus)/SUM(player_report_hourly.total_deposit)',
                'formatter' =>  function ($d) use ($show_total_deposit_times, $is_export, &$subtotals){
                    if($show_total_deposit_times){
                        return $this->data_tables->percentageFormatter($d);
                    }else{
                        if($is_export){
                            return lang('lang.norecyet');
                        }
                        return  '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
                    }
                }
            ),
            array(
                'dt' => $i++,
                'alias' => 'total_withdrawal',
                'name' => lang('report.pr22'),
                'select' => 'SUM(total_withdrawal)',
                'formatter' =>  function ($d) use ($show_total_withdrawal, $is_export, &$subtotals){
                    if($show_total_withdrawal ){
                        $subtotals['subtotals_total_withdrawal'] += $d;
                        return $this->data_tables->currencyFormatter($d);
                    }else{
                        if($is_export){
                            return lang('lang.norecyet');
                        }
                        return  '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
                    }
                }
            ),
            array(
                'dt' => $i++,
                'alias' => 'total_dw',
                'name' => lang('Net Deposit'),
                'select' =>'SUM(total_gross)',
                'formatter' =>  function ($d) use ($show_total_dw, $is_export, &$subtotals){
                    if($show_total_dw){
                        $subtotals['subtotals_total_dw'] += $d;
                        return $this->data_tables->currencyFormatter($d);
                    }else{
                        if($is_export){
                            return lang('lang.norecyet');
                        }
                        return  '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
                    }
                }
            ),
            array(
                'dt' => $i++,
                'alias' => 'total_withdrawal_over_deposit',
                'name' => lang('WOD%'),
                'select' =>'SUM(total_withdrawal)/SUM(total_deposit)',
                'formatter' =>  function ($d) use ($show_total_dw, $is_export, &$subtotals){
                    if($show_total_dw){
                        return $this->data_tables->percentageFormatter($d);
                    }else{
                        if($is_export){
                            return lang('lang.norecyet');
                        }
                        return  '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
                    }
                }
            ),
            array(
                'dt' => $i++,
                'alias' => 'total_bets',
                'name' => lang('cms.totalbets'),
                'select' => 'SUM(total_bet)',
                'formatter' => function ($d) use ($show_total_bets, $is_export, &$subtotals, $input) {
                    if($show_total_bets){
                        $subtotals['subtotals_total_bets'] += $d;
                        return $this->data_tables->currencyFormatter($d);
                    }else{
                        if($is_export){
                            return lang('lang.norecyet');
                        }
                        return  '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
                    }
                },
            ),
            array(
                'dt' => $i++,
                'alias' => 'turn_around_time',
                'name' => lang('TAT'),
                'select' => 'SUM(total_bet)/ (SUM(total_deposit)+SUM(player_report_hourly.total_bonus))',
                'formatter' => function ($d) use ($show_total_bets, $is_export, &$subtotals, $input) {
                    if($show_total_bets){
                        $subtotals['subtotals_tat'] += $d;
                        return $this->data_tables->currencyFormatter($d);
                    }else{
                        if($is_export){
                            return lang('lang.norecyet');
                        }
                        return  '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
                    }
                },
            ),
            array(
                'dt' => $i++,
                'alias' => 'total_win',
                'name' => lang('Win'),
                'select' => 'SUM(total_win)',
                'formatter' => function ($d) use ($show_total_win, $is_export, &$subtotals, $input) {
                    if($show_total_win){
                        $subtotals['subtotals_total_win'] += $d;
                        return $this->data_tables->currencyFormatter($d);
                    }else{
                        if($is_export){
                            return lang('lang.norecyet');
                        }
                        return  '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
                    }
                },
            ),
            array(
                'dt' => $i++,
                'alias' => 'total_loss',
                'name' => lang('Loss'),
                'select' => 'SUM(total_loss)',
                'formatter' => function ($d) use ($show_total_loss, $is_export, &$subtotals, $input) {
                    if($show_total_loss){
                        $subtotals['subtotals_total_loss'] += $d;
                        return $this->data_tables->currencyFormatter($d);
                    }else{
                        if($is_export){
                            return lang('lang.norecyet');
                        }
                        return  '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
                    }
                },
            ),
            array(
                'dt' => $i++,
                'alias' => 'total_payout',
                'name' => lang('Payout'),
                'select' => 'SUM(total_bet) - ( SUM(total_loss) - SUM(total_win) )',
                'formatter' => function ($d) use ($show_total_payout, $is_export, &$subtotals, $input) {
                    if($show_total_payout){
                        $subtotals['subtotals_total_payout'] += $d;
                        return $this->data_tables->currencyFormatter($d);
                    }else{
                        if($is_export){
                            return lang('lang.norecyet');
                        }
                        return  '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
                    }
                },
            ),
            array(
                'dt' => $i++,
                'alias' => 'payout_rate',
                'name' => lang('sys.payoutrate'),
                'select' => '(SUM(total_bet) - (SUM(total_loss)-SUM(total_win))) / SUM(total_bet)',
                'formatter' => function ($d) use ($show_payout_rate, $is_export, $input) {
                    if($show_payout_rate){
                        return $this->data_tables->percentageFormatter($d);
                    }else{
                        if($is_export){
                            return lang('lang.norecyet');
                        }
                        return  '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
                    }
                },
			),
			array(
				'dt' => $i++,
				'alias' => 'total_revenue',
				'name' => lang('Game Revenue'),
				'select' => 'SUM(total_loss)-SUM(total_win)',
				'formatter' =>  function($d) use ($show_total_revenue, $is_export, $input) {
					if($show_total_revenue){
						return $this->data_tables->currencyFormatter($d);
					} else {
						if($is_export){
                            return lang('lang.norecyet');
                        }
                        return  '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
					}
				}

			),
            array(
				'dt' => $this->utils->getConfig('display_net_loss_col') ? $i++ : NULL,
				'alias' => 'net_loss',
				'name' => lang('report.pr33'),
				'select' => '(SUM(total_gross) - SUM(player_report_hourly.total_bonus) - SUM(player_report_hourly.total_cashback) /* - player.total_total_nofrozen */ )',
				'formatter' =>  function($d) use ($show_net_loss, $is_export) {
					if($show_net_loss){
						return $this->data_tables->currencyFormatter($d);
					} else {
						if($is_export){
                            return lang('lang.norecyet');
                        }
                        return  '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
					}
				}
			),
            array(
                'dt' => $i++,
                'alias' => 'game_bet_details',
                'name' => lang('Bet Detail'),
                'select' => 'player_report_hourly.player_id',
                'formatter' => function ($d, $row) use ($readOnlyDB, $date_range_hour, $game_apis_map, $is_export, $input) {
                    $queryString = "SELECT game_platform_id as game, SUM(betting_amount) as bet, SUM(loss_amount - win_amount) AS payout, SUM(loss_amount - win_amount)/SUM(betting_amount) as payout_rate FROM total_player_game_hour WHERE player_id = ".$d." ".$date_range_hour."  GROUP BY game_platform_id";
                    //  # overrive query if group by player level
                    if (isset($input['group_by']) && strtolower($input['group_by']) == "playerlevel") {
                        $queryString = "SELECT game_platform_id as game, SUM(betting_amount) as bet, SUM(loss_amount - win_amount) AS payout, SUM(loss_amount - win_amount)/SUM(betting_amount) as payout_rate FROM total_player_game_hour WHERE player_id IN (SELECT playerId FROM player where levelId = ".$row['level_id'].") ".$date_range_hour."  GROUP BY game_platform_id";
                    }

                    $query = $readOnlyDB->query($queryString);
                    $bet_details=$query->result_array();

                    $total_bets_ = 0;
                    $total_payout_ = 0;
                    $total_rate_ = 0;

                    if(!empty($bet_details)){

                        if(!$is_export){
                            $bet_details_str = '<table  border="1" style="width:500px;">';
                            $bet_details_str .=  '<tr style="padding:2px;">';
                            $bet_details_str .=  '<th class="text-center" style="width:129px;"> '.lang('Game Platform').' </th><th class="text-center" style="width:105px;" > '.lang('Bets').' </th><th class="text-center" style="width:105px;"  > '.lang('report.Payout').' </th><th class="text-center" style="width:105px;"> '.lang('sys.payoutrate').'</th>';
                            $bet_details_str .=  '</tr>';

                            foreach ($bet_details as $v) {

                                $total_bets_ += $v['bet'];
                                $total_payout_ += $v['payout'];
                                $bet_details_str .=  '<tr>';

                                if(array_key_exists($v['game'], $game_apis_map)){
                                    $bet_details_str .= '<td style="text-align:right;padding:5px;width:50px;font-weight:bold;"><span class="text-success">'.$game_apis_map[$v['game']].'</span> </td>';
                                }else{
                                    $bet_details_str .= '<td style="text-align:right;padding:5px;width:50px;"><span class="text-danger">'.$v['game'].'</span> </td>';
                                }
                                $bet_details_str .= '<td style="text-align:right;padding:5px;"> <span>'.$this->data_tables->currencyFormatter($v['bet']).'</span></td>';
                                $bet_details_str .= '<td style="text-align:right;padding:5px;"> <span>'.$this->data_tables->currencyFormatter($v['payout']).'</span></td>';
                                $bet_details_str .= '<td style="text-align:right;padding:5px;"> <span>'.$this->data_tables->currencyFormatter($v['payout_rate']*100).'%</span></td>';
                                $bet_details_str .=  '</tr>';

                            }

                            $bet_details_str .=  '<tr>';
                            $bet_details_str .=  '<td style="text-align:right;padding:5px;font-weight:bold;"><span class="text-primary">Total</span> </td> ';
                            $bet_details_str .=  '<td style="text-align:right;padding:5px;font-weight:bold;"><span class="text-primary">'.$this->data_tables->currencyFormatter($total_bets_).'</span> </td> ';
                            $bet_details_str .=  '<td style="text-align:right;padding:5px;font-weight:bold;"><span class="text-primary">'.$this->data_tables->currencyFormatter($total_payout_).'</span> </td> ';
                            $bet_details_str .=  '<td style="text-align:right;padding:5px;font-weight:bold;"><span class="text-primary">'.@$this->data_tables->currencyFormatter(($total_payout_/$total_bets_)*100).'%</span> </td> ';
                            $bet_details_str .=  '</tr>';

                            return $bet_details_str;

                        }else{
                            $details = [];
                            foreach ($bet_details as $v) {
                                $total_bets_ += $v['bet'];
                                $total_payout_ += $v['payout'];
                                $total_rate_ += $v['payout_rate'];

                                $game =  (array_key_exists($v['game'], $game_apis_map)) ? $game_apis_map[$v['game']] : $v['game'];

                                $game_row = array( 'Game Platform' => $game,
                                    'Bets' => $this->data_tables->currencyFormatter($v['bet']),
                                    'Payout'=> $this->data_tables->currencyFormatter($v['payout']),
                                    'Payout_rate' => $this->data_tables->currencyFormatter($v['payout_rate']*100).'%'
                                );

                                $total_payout_rate_ = @$this->data_tables->currencyFormatter(($total_payout_/$total_bets_)*100);
                                array_push($details, $game_row);
                            }
                            return json_encode(array('total_bets' => $total_bets_,'total_payout' => $total_payout_, 'total_payout_rate'=> $total_payout_rate_, 'details' => $details));
                        }
                    }else{
                        return "-";
                    }
                },
            ),
			array(
                'dt' => $i++,
                'alias' => 'total_total_nofrozen',
                'select' => 'player.total_total_nofrozen',
                'name' => lang("Total Balance"), // Balance
                'formatter' =>  function ($d,$row) use ($is_export){
                    // if($is_export){
                    //     return lang('lang.norecyet');
                    // }
                    return $this->data_tables->currencyFormatter($d);
                }
            ),

        );


        # OUTPUT ######################################################################################################################################################################################


        #SYSTEM FEATURES
        $columnsToDisplayOrNot = array(
            'risk_level' => array('field'=>'risk_level','showtThis' => 'yes',  'feature' => 'show_risk_score'),
            'kyc_level' => array('field'=>'kyc_level','showtThis' => 'yes', 'feature' => 'show_kyc_status')
        );

        #CONTROL THIS WHEN USING SBE LOTTERY
        if(!$this->utils->isEnabledFeature('close_aff_and_agent')){
            $columnsToDisplayOrNot['affiliates_name'] = array('field'=>'affiliates_name','showtThis' => 'yes', 'feature' => 'show_search_affiliate');
            // $columnsToDisplayOrNot['agent'] = array('field'=>'agent','showtThis' => 'yes',  'feature' => 'agency');
        }else{
            $columnsToDisplayOrNot['affiliates_name'] = array('field'=>'affiliates_name','showtThis' => 'no', 'feature' => 'show_search_affiliate');
            // $columnsToDisplayOrNot['agent'] = array('field'=>'agent','showtThis' => 'no',  'feature' => 'agency');
        }

        $columns= $this->checkIfEnable($columnsToDisplayOrNot, $columns);

        #EXPORT TRIGGER
        if (isset($input['exportSelectedColumns'])) {
            $columns = $this->getSelectedColumns(explode(",", $input['exportSelectedColumns']), $columns);
        }

        if($is_export){
            $this->data_tables->options['is_export']=true;
			// $this->data_tables->options['only_sql']=true;
            if(empty($csv_filename)){
                $csv_filename=$this->utils->create_csv_filename(__FUNCTION__);
            }
            $this->data_tables->options['csv_filename']=$csv_filename;
		}

        $result = $this->data_tables->get_data($request, $columns, $table, $where, $values, $joins, $group_by, $having);
		// $sql = $this->data_tables->last_query;
		// $result['last_query'] = $sql;
        if($is_export){
		    //drop result if export
        	return $csv_filename;
        }

        #EXPORT - FOR VIEWING FIELDS TO EXPORT ON VIEW
        $result['cols_names_aliases'] = $this->get_dt_column_names_and_aliases($columns);

        $subtotals['subtotals_payout_rate']  = empty($subtotals['subtotals_total_bets']) ? $this->data_tables->percentageFormatter(0): $this->data_tables->percentageFormatter($subtotals['subtotals_total_payout']/$subtotals['subtotals_total_bets']);
        $subtotals['subtotals_bod']  = empty($subtotals['subtotals_total_deposit']) ? $this->data_tables->percentageFormatter(0): $this->data_tables->percentageFormatter($subtotals['subtotals_total_bonus']/$subtotals['subtotals_total_deposit']);
        $subtotals['subtotals_wod']  = empty($subtotals['subtotals_total_deposit']) ? $this->data_tables->percentageFormatter(0): $this->data_tables->percentageFormatter($subtotals['subtotals_total_withdrawal']/$subtotals['subtotals_total_deposit']);
        $subtotals['subtotals_tat']  = ($subtotals['subtotals_total_bets'] && $subtotals['subtotals_dnb']) ? ($subtotals['subtotals_total_bets']/$subtotals['subtotals_dnb']) : 0;
        $subtotals['subtotals_game_revenue']  = $subtotals['subtotals_total_loss'] - $subtotals['subtotals_total_win'];

        $result['subtotals'] = $subtotals;

		$query = $this->db->select('MAX(first_deposit_amount) first_deposit_amount, MAX(second_deposit_amount) second_deposit_amount, SUM(subtract_bonus) subtract_bonus, SUM(subtract_balance) subtract_balance')->from('player_report_hourly');
        if (count($where) > 0) {
            if (count($joins) > 0) {
                $joins = array_unique($joins);
                foreach ($joins as $key => $value) {
                    $joinMode='left';
                    if (!empty($this->innerJoins)) {
                        if (in_array($key, $this->innerJoins)) {
                            $joinMode='inner';
                        }
                    }
                    $query->join($key, $value, $joinMode);
                }
            }
            if ($search_string_1 = $this->_flatten($where)) {
                $search_string_1 = $this->db->compile_binds($search_string_1, $values);
                $query->where($search_string_1);
            }
        }

        $query->group_by($group_by);

        $rows = $this->runMultipleRowArray();

        $this->utils->printLastSQL();
        $deposit = ['first' =>0, 'second'=> 0];
        $subtract_bonus = 0;
		$subtract_balance = 0;
        if (count($rows) > 0) {
            foreach ($rows as $row) {
                $deposit['first'] += $row['first_deposit_amount'];
                $deposit['second'] += $row['second_deposit_amount'];
                $subtract_bonus -= $row['subtract_bonus'];
				$subtract_balance -= $row['subtract_balance'];
            }
        }

        $summary = $this->data_tables->summary($request, $table, $joins,
			'SUM(player_report_hourly.total_cashback) cashback,
			 SUM(deposit_bonus) deposit_bonus,
			 SUM(referral_bonus) referral_bonus,
			 SUM(manual_bonus) total_manual,
			 SUM(player_report_hourly.total_bonus) total_bonus,
			 SUM(total_deposit) total_deposit,
			 SUM(deposit_times) deposit_times,
			 SUM(total_withdrawal) total_withdrawal,
			 SUM(total_gross) total_deposit_withdraw,
			 SUM(total_bet) total_bet,
			 SUM(payout) payout,
			 SUM(total_win) total_win,
			 SUM(total_loss) total_loss,', null, $columns, $where, $values);

		$total['total_cashback']       = $this->utils->formatCurrencyNoSym($summary[0]['cashback']);
        $total['total_deposit_bonus']  = $this->utils->formatCurrencyNoSym($summary[0]['deposit_bonus']);
        $total['total_referral_bonus'] = $this->utils->formatCurrencyNoSym($summary[0]['referral_bonus']);
        $total['total_add_bonus']      = $this->utils->formatCurrencyNoSym($summary[0]['total_manual']);
        $total['total_subtract_bonus'] = $this->utils->formatCurrencyNoSym($subtract_bonus);
        $total['total_total_bonus']    = $this->utils->formatCurrencyNoSym($summary[0]['total_bonus']);
		$total['total_subtract_balance'] = $this->utils->formatCurrencyNoSym($subtract_balance);
        $total['total_first_deposit']  = $this->utils->formatCurrencyNoSym($deposit['first']);
        $total['total_second_deposit'] = $this->utils->formatCurrencyNoSym($deposit['second']);
        $total['total_deposit']        = $this->utils->formatCurrencyNoSym($summary[0]['total_deposit']);
        $total['total_deposit_times']  = $summary[0]['deposit_times'] ?: 0;
        $total['total_withdrawal']     = $this->utils->formatCurrencyNoSym($summary[0]['total_withdrawal']);
        $total['total_dw']             = $this->utils->formatCurrencyNoSym($summary[0]['total_deposit_withdraw']);
		$total['total_bets']           = $this->utils->formatCurrencyNoSym($summary[0]['total_bet']);
		$total_payout = $summary[0]['total_bet']-($summary[0]['total_loss']-$summary[0]['total_win']);
        $total['total_payout']         = $this->utils->formatCurrencyNoSym($total_payout);
        $total['total_payout_rate']    = empty($summary[0]['total_bet']) ? $this->data_tables->percentageFormatter(0): $this->data_tables->percentageFormatter($total_payout/$summary[0]['total_bet']);
        $dnb = $summary[0]['total_deposit']+$summary[0]['total_bonus'];
        $total['total_dnb']            = $this->utils->formatCurrencyNoSym($dnb);
        $total['total_bod']            = empty($summary[0]['total_deposit']) ? $this->data_tables->percentageFormatter(0): $this->data_tables->percentageFormatter($summary[0]['total_bonus']/$summary[0]['total_deposit']);
        $total['total_wod']            = empty($summary[0]['total_deposit']) ? $this->data_tables->percentageFormatter(0): $this->data_tables->percentageFormatter($summary[0]['total_withdrawal']/$summary[0]['total_deposit']);
        $total['total_tat']            = empty($dnb) ? $this->utils->formatCurrencyNoSym(0): $this->utils->formatCurrencyNoSym($summary[0]['total_bet']/$dnb);
        $total['total_win']            = $this->utils->formatCurrencyNoSym($summary[0]['total_win']);
        $total['total_loss']           = $this->utils->formatCurrencyNoSym($summary[0]['total_loss']);
        $total['total_game_revenue']   = $this->utils->formatCurrencyNoSym($summary[0]['total_loss'] - $summary[0]['total_win']);
        $result['total'] = $total;
        return $result;
    }

	public function quest_report($request, $permissions, $is_export){
        $readOnlyDB = $this->getReadOnlyDB();

        $this->load->library('data_tables', array("DB" => $readOnlyDB));
        $this->load->model(array('quest_manager', 'player', 'withdraw_condition'));
        // $this->load->helper(['player_helper']);
        // $game_apis_map= $this->utils->getAllSystemMap();

        // $sum_add_bonus_as_manual_bonus = $this->utils->getConfig('sum_add_bonus_as_manual_bonus');
        // $sum_deposit_promo_bonus_as_total_deposit_bonus = $this->utils->getConfig('sum_deposit_promo_bonus_as_total_deposit_bonus');
        // $player_kyc = $this->player_kyc;
        // $kyc_status_model = $this->kyc_status_model;
        // $risk_score_model = $this->risk_score_model;

        $this->data_tables->is_export = $is_export;

        $table = 'player_quest_job_state';
        $joins = array();
        $where = array();
        $values = array();
        $group_by = array();
        $having = array();
        $joins['player'] = 'player.playerId = player_quest_job_state.playerId';
        $joins['quest_manager'] = "quest_manager.questManagerId = player_quest_job_state.questManagerId";
        $joins['quest_category'] = 'quest_category.questCategoryId = quest_manager.questCategoryId';
        $joins['withdraw_conditions'] = "withdraw_conditions.Id = player_quest_job_state.withdrawConditionId";
		$joins['quest_job'] = "quest_job.questJobId = player_quest_job_state.questJobId";
		$joins['quest_rule'] = "quest_rule.questRuleId = quest_job.questRuleId OR quest_rule.questRuleId = quest_manager.questRuleId";
        $i = 0;
        $input = $this->data_tables->extra_search($request);

		if( empty($input['timezone']) ){
			$input['timezone'] = 0; // for undefined issue
		}

        $date_from = null;
        $date_to = null;
        if (isset($input['date_from'], $input['date_to'])) {
            // $date_from = $input['date_from'];
            // $date_to = $input['date_to'];

			$date_from = $this->_getDatetimeWithTimezone($input['timezone'], $input['date_from']);
			$date_to = $this->_getDatetimeWithTimezone($input['timezone'], $input['date_to']);
        }

        # FILTER ######################################################################################################################################################################################

		$show_username            = true;
		$show_requested_date      = true;
		$show_category_title      = true;
		$show_manager_title       = true;
		$show_type     	  		  = true;
		$show_status              = true;
		$show_withdraw_conditions = true;
		$show_amount              = true;
		$show_player_request_ip   = true;
		$show_release_time 		  = true;
		
        $dateTimeFrom = null;
        $dateTimeTo = null;
        if (isset($input['date_from'], $input['date_to'])) {
            $dateTimeFrom = $input['date_from'];
            $dateTimeTo   = $input['date_to'];

			/// for apply the timezone,
			// override the inputs, deposit_date_from and deposit_date_to.
			// $dateTimeFrom = $this->_getDatetimeWithTimezone($input['timezone'], $dateTimeFrom);
			// $dateTimeTo = $this->_getDatetimeWithTimezone($input['timezone'], $dateTimeTo);
			if(isset($input['search_type']) && $input['search_type'] == 'releaseTime'){
				$where[]  = "player_quest_job_state.releaseTime >= ? AND player_quest_job_state.releaseTime <= ?";
			}else{
				$where[]  = "player_quest_job_state.createdAt >= ? AND player_quest_job_state.createdAt <= ?";
			}
            // $values[] = $this->utils->formatDateHourForMysql(new DateTime($dateTimeFrom));
            // $values[] = $this->utils->formatDateHourForMysql(new DateTime($dateTimeTo));
			$values[] = $dateTimeFrom;
			$values[] = $dateTimeTo;
        }

        if (isset($input['player_id'])) {
            $where[] = "player_quest_job_state.playerId = ?";
            $values[] = $input['player_id'];
		}

        if (isset($input['category_id'])) {
			$where[] = "quest_category.questCategoryId = ?";
			$values[] = $input['category_id'];
        }

        if (isset($input['manager_title'])) {
            $where[] = "(quest_manager.title = ? OR quest_job.title = ?)";
			$values[] = $input['manager_title'];
			$values[] = $input['manager_title'];
        }

        if (isset($input['status'])) {
			$where[] = "rewardStatus = ?";
			$values[] = $input['status'];
		}

        if (isset($input['username'], $input['search_by'])) {
            if ($input['search_by'] == 1) {
                $where[] = "player.username LIKE ?";
                $values[] = '%' . $input['username'] . '%';
            } else if ($input['search_by'] == 2) {
                $where[] = "player.username = ?";
                $values[] = $input['username'];
            }
        }

		if (isset($input['ip_address'], $input['search_by_ip'])) {
            if ($input['search_by_ip'] == 1) {
                $where[] = "player_quest_job_state.playerRequestIp LIKE ?";
                $values[] = '%' . $input['ip_address'] . '%';
            } else if ($input['search_by_ip'] == 2) {
                $where[] = "player_quest_job_state.playerRequestIp = ?";
                $values[] = $input['ip_address'];
            }
        }

		$where[] = "quest_manager.deleted = 0";

        # DEFINE TABLE COLUMNS ########################################################################################################################################################################
		$columns = array(
			array(
                'select' => 'player_quest_job_state.id',
                'alias' => 'id'
			),
			array(
                'select' => 'player_quest_job_state.playerId',
                'alias' => 'playerId'
			),
            array(
                'dt' => $i++,
                'alias' => 'create_time',
                'name' => lang('report.qr01'),
                'select' => 'player_quest_job_state.createdAt',
                'formatter' =>  function ($d, $row) use ($is_export, $show_requested_date){
					if($show_requested_date){
                        if ($is_export) {
                            return $d;
                        } else {
                            return $d;
                        }
                    }else{
                        if($is_export){
                            return lang('lang.norecyet');
                        }
                        return  '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
                    }
                }
            ),
            array(
                'dt' => $i++,
                'alias' => 'username',
                'name' => lang('report.pr01'),
                'select' => 'player.username',
                'formatter' => function ($d, $row) use ($is_export, $date_from, $date_to, $show_username) {

                    if($show_username){
                        if ($is_export) {
                            return $d;
                        } else {
                            $date_qry = '';
                            if (!empty($date_from) && !empty($date_to)) {
                                $date = new DateTime($date_from);
                                $date_qry = '&date_from=' . $date->format('Y-m-d') . '&hour_from=' . $date->format('H');

                                $date = new DateTime($date_to);
                                $date_qry .= '&date_to=' . $date->format('Y-m-d') . '&hour_to=' . $date->format('H');
                            }
                            return '<a href="/player_management/userInformation/' . $row['playerId'] . '">' . $d . '</a>';
                        }
                    }else{
                        if($is_export){
                            return lang('lang.norecyet');
                        }
                        return  '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
                    }
                },
            ),
			array(
                'dt' => $i++,
                'alias' => 'category_title',
                'name' => lang('report.qr02'),
                'select' => 'quest_category.title',
                'formatter' =>  function ($d, $row) use ($is_export, $show_category_title){
                    if($show_category_title){
                        return lang($d);
                    }else{
                        if($is_export){
                            return lang('lang.norecyet');
                        }
                        return  '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
                    }
                }
            ),
			array(
                'dt' => $i++,
                'alias' => '',
                'name' => lang('report.qr03'),
                'select' => 'quest_manager.title as single_title, quest_job.title as multiple_title',
                'formatter' =>  function ($d, $row) use ($is_export, $show_manager_title){
                    if($show_manager_title){
						if($row['multiple_title'] != null){
							return $row['multiple_title'];
						}else{
							return $row['single_title'];
						}
                    }else{
                        if($is_export){
                            return lang('lang.norecyet');
                        }
                        return  '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
                    }
                }
            ),
			array(
				'dt' => $i++,
				'alias' => 'type',
				'name' => lang('report.qr09'),
				'select' => 'quest_rule.rouletteConditionType',
				'formatter' =>  function ($d, $row) use ($is_export, $show_type){
					if($show_type){
						if($d != null){
							return lang('lang.roulettespin');
						}else{
							return lang('lang.questamount');
						}
					}else{
						if($is_export){
							return lang('lang.norecyet');
						}
						return  '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
					}
				}

			),
			array(
                'dt' => $i++,
                'alias' => 'status',
                'name' => lang('report.qr04'),
                'select' => 'rewardStatus',
                'formatter' =>  function ($d, $row) use ($is_export, $show_status){
                    if($show_status){
						if($d == 1){
							return lang('lang.not achieved');
						}else if($d == 2){
							return lang('lang.unrecived');
						}else if($d == 3){
							return lang('lang.recived');
						}else if($d == 4){
							return lang('lang.expired');
						}
                    }else{
                        if($is_export){
                            return lang('lang.norecyet');
                        }
                        return  '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
                    }
                }
            ),
			array(
                'dt' => $i++,
                'alias' => 'withdraw_conditions',
                'name' => lang('report.qr05'),
                'select' => 'withdraw_conditions.condition_amount',
                'formatter' =>  function ($d, $row) use ($is_export, $show_withdraw_conditions){
                    if($show_withdraw_conditions){
						if($row['withdraw_conditions'] != null){
							return $d;
						}else{
							return lang('lang.norecyet');
						}
                    }else{
                        if($is_export){
                            return lang('lang.norecyet');
                        }
                        return  '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
                    }
                }
            ),
			array(
                'dt' => $i++,
                'alias' => '',
                'name' => lang('report.qr06'),
                'select' => 'quest_rule.bonusConditionValue as amount, quest_rule.rouletteTimes as times',
                'formatter' =>  function ($d, $row) use ($is_export, $show_amount){
                    if($show_amount){
                        // return $d;
						if($row['times'] != null){
							return $row['times'];
						}else{
							return $row['amount'];
						}
                    }else{
                        if($is_export){
                            return lang('lang.norecyet');
                        }
                        return  '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
                    }
                }
            ),
			array(
                'dt' => $i++,
                'alias' => 'player_request_ip',
                'name' => lang('report.qr07'),
                'select' => 'playerRequestIp',
                'formatter' =>  function ($d, $row) use ($is_export, $show_player_request_ip){
                    if($show_player_request_ip){
						if($row['player_request_ip'] != null){
							return $d;
						}else{
							return lang('lang.norecyet');
						}
                    }else{
                        if($is_export){
                            return lang('lang.norecyet');
                        }
                        return  '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
                    }
                }
            ),
			array(
                'dt' => $i++,
                'alias' => 'release_time',
                'name' => lang('report.qr08'),
                'select' => 'releaseTime',
                'formatter' =>  function ($d, $row) use ($is_export, $show_release_time){
                    if($show_release_time){
						if($row['release_time'] != null){
							return $d;
						}else{
							return lang('lang.norecyet');
						}
                    }else{
                        if($is_export){
                            return lang('lang.norecyet');
                        }
                        return  '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
                    }
                }
            )
        );


        # OUTPUT ######################################################################################################################################################################################


        #EXPORT TRIGGER
        if (isset($input['exportSelectedColumns'])) {
            $columns = $this->getSelectedColumns(explode(",", $input['exportSelectedColumns']), $columns);
        }

        if($is_export){
            $this->data_tables->options['is_export']=true;
			// $this->data_tables->options['only_sql']=true;
            if(empty($csv_filename)){
                $csv_filename=$this->utils->create_csv_filename(__FUNCTION__);
            }
            $this->data_tables->options['csv_filename']=$csv_filename;
		}

        $result = $this->data_tables->get_data($request, $columns, $table, $where, $values, $joins, $group_by, $having);
		$this->utils->printLastSQL();

        if($is_export){
		    //drop result if export
        	return $csv_filename;
        }
		$result['cols_names_aliases'] = $this->get_dt_column_names_and_aliases($columns);
		$this->utils->debug_log('the result ---->', $result);
        return $result;
    }

    private function _flatten($a, $join = ' AND ') {
        if (!$a) {
            return '';
        } else if (is_array($a)) {
            return implode($join, $a);
        } else {
            return $a;
        }
    }

	private function checkIfEnable($columnsToDisplayOrNot, $columns){

		$fields =array_column($columnsToDisplayOrNot,'field' );
		$i = 0;
		foreach ($columns as $key => $column) {
			if(isset($column['alias'])){
				$alias = $column['alias'];
				if(in_array($alias, $fields ) && isset($columns[$key]['dt'])){

					if($this->utils->isEnabledFeature($columnsToDisplayOrNot[$alias]['feature'])){
						if($columnsToDisplayOrNot[$alias]['showtThis'] == 'no'){
							//$this->utils->debug_log('the  susi ---->', $columns[$key]);
							unset($columns[$key]);
						}
					}else{
						if($columnsToDisplayOrNot[$alias]['showtThis'] == 'yes'){
							//$this->utils->debug_log('the  susi ---->', $columns[$key]);
							unset($columns[$key]);
						}
					}
				}
				if(isset($columns[$key]['dt'])){
					$columns[$key]['dt'] = $i++;
				}
			}
		}
		return array_values($columns);
	}

	private function get_dt_column_names_and_aliases($columns){
		$cols_names_aliases = [];
		foreach ($columns as $key => $data) {
			if(isset( $data['dt'])){
				$arr = array();
				$arr['alias'] = $data['alias'];
				$arr['name'] = $data['name'];
				array_push($cols_names_aliases, $arr);
			}
		}
		return $cols_names_aliases;
	}

	private function getPlayerBetPayoutRate($playerId,$daterange_hour){
		$readOnlyDB = $this->getReadOnlyDB();
		$query = $readOnlyDB->query("SELECT game_platform_id as game, SUM(betting_amount) as bet, SUM(loss_amount - win_amount) AS payout, SUM(loss_amount - win_amount)/SUM(betting_amount) as payout_rate FROM total_player_game_hour WHERE player_id = ? ".$daterange_hour."  GROUP BY game_platform_id", array($playerId));
		return $query->result_array();
	}

	private function getSelectedColumns($selectedColumns, $columns){

		if(!empty($selectedColumns)){
			$i = 0;
			foreach ($columns as $key => $data) {
				if (!in_array($data['alias'], $selectedColumns) && isset($columns[$key]['dt'])) {
					unset($columns[$key]);
				}else{
					if(isset($columns[$key]['dt'])){
						$columns[$key]['dt'] = $i++;
					}
				}
			}
		}
		return array_values($columns);
	}

    /**
     * detail: get player list
     *
     * @param array $request
     * @param array $permissions For queue_results reference while executed.
     * @param Boolean $is_export true while executed from queue or false means query from SBE.
     * @param string $csv_filename The specified file name.
     *
     * @return array
     */
    public function player_list_reports($request, $permissions, $is_export = false,$csv_filename=null) {

        $session_timeout =  $this->utils->getConfig('session_timeout');
        $dateTimeNow =  $this->utils->getNowDateTime();
        $dateTimeNow = $dateTimeNow->modify($session_timeout);
        $dateTimeNow = $dateTimeNow->getTimestamp();
        $readOnlyDB = $this->getReadOnlyDB();

        $this->load->library('data_tables', array("DB" => $readOnlyDB));
        $this->load->helper(['player_helper']);

        $viewVerifiedStatus = true;
        $this->load->model(array('player_model', 'agency_model', 'wallet_model', 'game_logs','risk_score_model','player_kyc','player_attached_proof_file_model','kyc_status_model','transactions', 'ip_tag_list'));

		$permissions_player_cpf_number = false;
		if ( !empty($permissions['player_cpf_number']) ){
			$permissions_player_cpf_number = true;
		}

        $this->data_tables->is_export = $is_export;
        $wallet_model = $this->wallet_model;
        $risk_score_model = $this->risk_score_model;
        $player_attached_proof_file_model = $this->player_attached_proof_file_model;
        $player_kyc = $this->player_kyc;
        $kyc_status_model = $this->kyc_status_model;
        $utils = $this->utils;
        $model = $this;

		/**
		 * for imAccount1 ~ 3 formatter script for export.
		 * @param string|int $d
		 * @param array $permissions
		 * @param class $utils The utils class.
		 */
		$scriptListFormatterImAccount4export = function ($d, $permissions, $utils) {
			if (empty(trim($d))) {
				$d = lang('lang.norecyet');
			} elseif (!$permissions['view_player_detail_contactinfo_im']) {
				$d = $utils->keepTailString($d, 0);
			}
			return $d;
		};

							/**
		 * for imAccount1 ~ 3 formatter script for HTML.
		 * @param string|int $d
		 * @param array $permissions
		 * @param class $utils The utils class.
		 */
		$scriptListFormatterImAccount = function ($d, $permissions, $utils) {
			$no_record = '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
			$returnD = $no_record;
			$carry = '<span class="label label-primary">%s</span> ';
			if (! empty($d)) {
				if (! $permissions['view_player_detail_contactinfo_im']) {
					$d = $utils->keepTailString($d, 0);
				}
				$returnD = sprintf($carry, $d);
			}
			return $returnD;
		};

        # START DEFINE COLUMNS #################################################################################################################################################
        //permission and enabled system
        $input = $this->data_tables->extra_search($request);
        $this->utils->debug_log(__METHOD__,'log input',$input);

        $alias_array = isset($this->utils->getConfig('player_list_column_order')['custom_order']) ? $this->utils->getConfig('player_list_column_order')['custom_order']:$this->utils->getConfig('player_list_column_order')['default_order'];

		if( empty($input['timezone']) ){
			$input['timezone'] = 0; // for undefined issue
		}

        $i = 0;
        $columns = array();
        foreach ($alias_array as $alias) {
            switch ($alias){
                case 'batch_message_action':
                    if ( ! $is_export) { // for website
                        $columns[] = array(
                            'dt' => $i++, // #1
                            'alias' => 'batch_message_action',
                            'select' => 'player.playerId',

                            'formatter' => function ($d, $row) use ($is_export) {
                                if ($is_export) {
                                    return '';
                                } else {
                                    return '<input type="checkbox" class="batch-message-cb" title="' . lang('lang.select.send.message') . '" value="' . $row['playerId'] . '" username="' . $row['username'] . '"  id="cb-user-id-' . $row['username'] . '"/>';
                                }
                            },
                            'name' => '',
                        );
                    }
                break;
                case 'username':
                    $columns[] = array(
                        'dt' => $i++, // #2
                        'alias' => 'username',
                        'select' => 'player.username',
                        'formatter' => ($is_export) ? 'defaultFormatter' : function ($d, $row) {
                            return '<a href="/player_management/userInformation/' . $row['playerId'] . '">' . $d . '</a>';
                        },
                        'name' => lang('player.01'),
                    );

                break;

                case 'online':
                    // A2. Modify and arrange the sorting of the table: (the sequence as below)
                    $columns[] = array(
                        'dt' => $i++, // #3
                        'alias' => 'online',
                        // 'select' => 'player.playerId',
                        'select' => 'player.online',
                        'name' => lang("viewuser.03"),
                        'formatter' => function ($d, $row) use ($is_export,$dateTimeNow) {
                            if ($is_export) {
                                return '';
                            }else{
                                // ci_player_sessions.player_id = player.playerId and ci_player_sessions.player_id is not null;
                                // $isOnline=$this->player_model->existsOnlineSession($d);
                                // return $isOnline ? '<i class="text-muted">' . lang('icon.online') . '</i>' : '<i class="text-muted">' . lang('icon.offline') . '</i>';
                                return $d ? '<i class="text-muted">' . lang('icon.online') . '</i>' : '<i class="text-muted">' . lang('icon.offline') . '</i>';
                            }
                        },
                    );

                break;
                case 'lastLoginTime':
                    $columns[] = array(
                        'dt' => $i++, // #4
                        'alias' => 'lastLoginTime',
                        'select' => 'player_runtime.lastLoginTime',
                        'name' => lang("Last Login Date"),
                        'formatter' => function ($d) use ($is_export) {
                            if ($is_export) {
                                return (!$d || strtotime($d) < 0) ? lang('lang.norecyet') : date('Y-m-d H:i:s', strtotime($d));
                            } else {
                                return (!$d || strtotime($d) < 0) ? '<i class="text-muted">' . lang('lang.norecyet') . '</i>' : date('Y-m-d H:i:s', strtotime($d));
                            }
                        },
                    );

                break;
                case 'lastLoginIp':
                    if( ! $is_export && ! empty($this->utils->getConfig('do_mark_duplicated_ip_to_red_in_player_list'))){
                        $columns[] = array(
                            'alias' => 'isDuplicate',
                            'select' => '( SELECT IF( count(id) > 1, 1, 0) as isDuplicate
                                            FROM player_ip_last_request
                                            WHERE player_ip_last_request.ip = player_runtime.lastLoginIp
                                            AND player_ip_last_request.player_id != player.playerId
                                        )',
                        );
                    } // EOF if( ! $is_export ){...

                    $columns[] = array(
                        'dt' => $i++, // #5
                        'alias' => 'lastLoginIp',
                        'select' => 'player_runtime.lastLoginIp',
                        'name' => lang("player_list.fields.last_login_ip"), // Patch for OGP-14715
                        'formatter' => function ($d, $row) use ($is_export) {
							$ip = $d;
                            if ($is_export) {
                                $data = trim(trim($ip), ',') ?$ip." ( ".$this->utils->getCountry($ip)." )": lang('lang.norecyet');
                            } else {
                                $data = trim(trim($ip), ',') ?$ip." ( ".$this->utils->getCountry($ip)." )": '<i class="text-muted">' . lang('lang.norecyet') . '</i>';

								if(! empty($this->utils->getConfig('do_mark_duplicated_ip_to_red_in_player_list')) ){
									$isDuplicate = $row['isDuplicate'];
									if($isDuplicate){
										$data = '<span class="text-danger">'. $data. '</span>';
									}
								} // EOF if(! empty($this->utils->getConfig('do_mark_duplicated_ip_to_red_in_player_list')) ){...

                            }
							return $data;
                        },
                    );

                break;
                case 'blocked':
                    $columns[] = array(
                        'dt' => $i++, // #6
                        'alias' => 'blocked',
                        'select' => 'player.blocked' ,
                        'name' => lang("lang.accountstatus"),
                        'formatter' => function ($d, $row) use ($is_export){
                            $formatter = 1;
                            // OGP-15172
                            $lang = array();
                            $lang['lang.active'] = lang('status.normal');
                            $lang['Blocked'] = lang('player_list.options.blocked');
                            $lang['Suspended'] = lang('player_list.options.suspended');
                            $lang['Self Exclusion'] = lang('player_list.options.self_exclusion');
                            $lang['Failed Login Attempt'] = lang('player_list.options.failed_login_attempt');

							$isBlockedUntilExpired_rlt = $this->player_model->isBlockedUntilExpired($row['playerId']);
							if( $isBlockedUntilExpired_rlt['isBlocked']
								&& $isBlockedUntilExpired_rlt['isExpired']
							){  // reload
								$d = $isBlockedUntilExpired_rlt['row']['blocked'];
							}

                            return  $this->utils->getPlayerStatus($row['playerId'],$formatter,$d,$is_export, $lang);
                        },
                    );

                break;
                case 'createdOn':
                    $columns[] = array(
                        'dt' => $i++, // #7
                        'alias' => 'createdOn',
                        'select' => 'player.createdOn',
                        'name' => lang("player.38"),
                        'formatter' => function ($d) use ($is_export) {
                            if ($is_export) {
                                return (!$d || strtotime($d) < 0) ? lang('lang.norecyet') : date('Y-m-d H:i:s', strtotime($d));
                            } else {
                                return (!$d || strtotime($d) < 0) ? '<i class="text-muted">' . lang('lang.norecyet') . '</i>' : date('Y-m-d H:i:s', strtotime($d));
                            }
                        },
                    );

                break;
                case 'registered_by':
                    $columns[] = array(
                        'dt' => $i++, // #8
                        'alias' => 'registered_by',
                        'select' => 'player.registered_by',
                        'formatter' => function ($d) {
                            switch ($d) {
                                case Player_model::REGISTERED_BY_IMPORTER:
                                    $str = lang('Imported Account');
                                    break;
                                case Player_model::REGISTERED_BY_WEBSITE:
                                    $str = lang('player_list.options.website');
                                    break;
                                case Player_model::REGISTERED_BY_MASS_ACCOUNT:
                                    $str = lang('Batch Create');
                                    break;
                                case Player_model::REGISTERED_BY_MOBILE:
                                    $str = lang('player_list.options.mobile');
                                    break;
                                case Player_model::REGISTERED_BY_AGENCY_CREATED:
                                    $str = lang('Created by agency');
                                    break;
								case Player_model::REGISTERED_BY_PLAYER_CENTER_API:
                                    $str = lang('Player Center API');
                                    break;
                                default:
                                    $str = lang('Unknown');
                                    break;
                            }
                            return $str;
                        },
                        'name' => lang("Registered By"),
                    );

                break;
                case 'registrationWebsite':
                    $columns[] = array(
                        'dt' => $i++, // #9
                        'alias' => 'registrationWebsite',
                        'select' => 'playerdetails.registrationWebsite',
                        'name' => lang("Registered Website"),
                        'formatter' => function ($d) use ($is_export) {
                            if ($is_export) {
                                return trim(trim($d), ',') ?: lang('lang.norecyet');
                            } else {
                                return trim(trim($d), ',') ?: '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
                            }
                        },
                    );

                break;
                case 'ip_address':
                    $columns[] = array(
                        'dt' => $i++, // #10
                        'alias' => 'ip_address',
                        'select' => 'playerdetails.registrationIp',
                        'name' => lang('Signup IP'), // Signup IP
                        'formatter' => function ($d) use ($is_export) {
                            if ($is_export) {
                                return trim(trim($d), ',') ?$d." ( ".$this->utils->getCountry($d)." )": lang('lang.norecyet');
                            } else {
                                return trim(trim($d), ',') ?$d." ( ".$this->utils->getCountry($d)." )": '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
                            }
                        },
                    );

                break;
				case 'registration_ip_tags':
					$hide_iptaglist = $this->utils->getConfig('hide_iptaglist');
					if( empty($hide_iptaglist) ){
						$columns[] = array(
							'dt' => $i++, // #10.1
							'alias' => 'registration_ip_tags',
							'select' => 'playerdetails.registrationIp',
							'name' => lang('IP Tag'), // IP Tag
							'formatter' => function ($d) use ($is_export) {
								$return = lang('N/A');
								$return_list = [];
								$_ipTags = $this->ip_tag_list->getIpTagsByIp($d);
								if( ! empty($_ipTags) ){
									array_walk($_ipTags, function($currRow, $indexNumber) use ($is_export, &$return_list){
										if($is_export) {
											$return_list[] = $currRow['name'];
										}else{
											$currRowLite = [];
											$currRowLite['ip'] = $currRow['ip'];
											$currRowLite['color'] = $currRow['color'];
											$currRowLite['name'] = $currRow['name'];
											$return_list[] = $currRowLite;
											unset($currRowLite); // free memory in array type.
										}
									});
									unset($_ipTags); // free memory in array type.
								}

								if( ! empty($return_list) ){
									if($is_export) {
										// plainText in CSV
										$return = implode(',', $return_list);
									}else{
										// json for html in js.
										$_scriptHtml = <<<EOF
<script class="json_in_ip_tag_list" type="text/json">
%s
</script>
EOF;
										$return = sprintf($_scriptHtml, json_encode($return_list) );
									}
									unset($return_list); // free memory in array type.
								}
								return $return;
							}, // EOF 'formatter' => function ($d) use ($is_export) {...
						); // EOF $columns[] = array(...
					}
                break;
                case 'referral_code':
                    $columns[] = array(
                        'dt' => $i++, // #11
                        'alias' => 'referral_code',
                        'select' => 'player.invitationCode',
                        'name' => lang('player_list.fields.referral_code'),
                        'formatter' => function ($d) use ($is_export) {
                            if ($is_export) {
                                return trim(trim($d), ',') ?: lang('lang.norecyet');
                            } else {
                                return trim(trim($d), ',') ?: '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
                            }
                        },
                    );

                break;
                case 'refereePlayerId':
                    $columns[] = array(
                        'dt' => $i++, // #12
                        'alias' => 'refereePlayerId',
                        'select' => 'player.refereePlayerId',
                        'name' => lang("Referred By"),
                        'formatter' => function ($d, $row) use ($readOnlyDB, $is_export) {
                            $query = $readOnlyDB->query("SELECT username from player where playerId = ?", array($row['refereePlayerId'],));
                            $rs = $query->row_array();
                            if ($is_export) {
                                return !empty($rs['username']) ?  trim($rs['username']):lang('lang.norecord');
                            } else {
                                return !empty($rs['username']) ?  trim($rs['username']): '<i class="text-muted">' . lang('lang.norecord') . '</i>';
                            }
                        },
                    );

                break;
                case 'affiliate':
                    if(!$this->utils->isEnabledFeature('close_aff_and_agent')){
                        $columns[] = array(
                            'dt' => $i++, // #13
                            'alias' => 'affiliate',
                            'select' => 'affiliates.username',
                            'name' => lang('player.24'), // player.24
                            'formatter' => function ($d) use ($is_export) {
                                if ($is_export) {
                                    return trim(trim($d), ',') ?: lang('lang.norecyet');
                                } else {
                                    return trim(trim($d), ',') ?: '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
                                }
                            },

                        );
                    } // EOF if(!$this->utils->isEnabledFeature('close_aff_and_agent'))


                break;
                case 'agent':
                    if(!$this->utils->isEnabledFeature('close_aff_and_agent')){
                        $columns[] = array(
                            'dt' => $i++, // #14
                            'alias' => 'agent',
                            'select' => 'player.agent_id',
                            'name' => lang("Under Agency"),
                            'formatter' => function ($d) use ($is_export) {
                                if ($d != null) {
                                    $agent_details = $this->agency_model->get_agent_by_id($d);

                                    if(isset($agent_details['agent_name']) && !empty($agent_details['agent_name'])){
                                     $name = $agent_details['agent_name'];
                                     $url = site_url('/agency_management/agent_information/' . $d);

                                     if($is_export){
                                         return $name;
                                     }else{
                                         return '<a href="' . $url . '">' . $name . '</a>';
                                     }
                                 }else{
                                     if($is_export){
                                         return trim(trim($d), ',') ?: lang('lang.norecyet');
                                     }else{
                                         return trim(trim($d), ',') ?: '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
                                     }
                                 }

                                } else {
                                    if ($is_export) {
                                        return trim(trim($d), ',') ?: lang('lang.norecyet');
                                    } else {
                                        return trim(trim($d), ',') ?: '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
                                    }
                                }
                            },
                        );
                     } // EOF if(!$this->utils->isEnabledFeature('close_aff_and_agent'))

                break;
                case 'tagName':
                    $columns[] = array(
                        'dt' => $i++, // #15
                        'alias' => 'tagName',
                        'select' => 'player.playerId',
                        'name' => lang("Player Tag"), // Player Tag
                        'formatter' => function ($d, $row) use ($is_export) {
                            return player_tagged_list($row['playerId'], $is_export);
                        },
                    );

                break;
                case 'group_level':
                    $columns[] = array(
                        'dt' => $i++, // #16
                        'alias' => 'group_level',
                        'select' => 'player.levelName',
                        'formatter' => function ($d, $row) {
                            $getUpdatedGroupAndLevel = $this->player_model->getPlayerCurrentLevel($row['playerId']);
                            if($getUpdatedGroupAndLevel){
                                $groupName = lang($getUpdatedGroupAndLevel[0]['groupName']);
                                $levelName = lang($getUpdatedGroupAndLevel[0]['vipLevelName']);
                                return $groupName . ' - ' .$levelName;
                            }
                            else{
                                return null;
                            }
                        },
                        'name' => lang("player_list.fields.vip_level"), // player_list.fields.vip_level
                    );

                break;
                case 'first_name':
                    $columns[] = array(
                        'dt' => $i++, // #17
                        'alias' => 'first_name',
                        'select' => 'ifnull(playerdetails.firstName,"")',
                        'formatter' => function ($d) use ($is_export) {
                            if ($is_export) {
                                return trim(trim($d), ',') ?: lang('lang.norecyet');
                            } else {
                                return trim(trim($d), ',') ?: '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
                            }
                        },
                        'name' => lang("First Name"),
                    );

                break;
                case 'last_name':
                    $columns[] = array(
                        'dt' => $i++, // #18
                        'alias' => 'last_name',
                        'select' => 'ifnull(playerdetails.lastName,"")',
                        'formatter' => function ($d) use ($is_export) {
                            if ($is_export) {
                                return trim(trim($d), ',') ?: lang('lang.norecyet');
                            } else {
                                return trim(trim($d), ',') ?: '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
                            }
                        },
                        'name' => lang("Last Name"),
                    );

                break;
                case 'email':
                    $columns[] = array(
                        'dt' => $i++, // #19
                        'alias' => 'email',
                        'select' => 'player.email',
                        'name' => lang("player_list.fields.email_address"),
                        'formatter' => function ($d, $row) use ($permissions, $viewVerifiedStatus, $is_export) {
                            $str = $d;

                            if ( ! (isset($permissions['view_player_detail_contactinfo_em']) && $permissions['view_player_detail_contactinfo_em'] ) ) {
                                $str = $this->utils->keepHeadString($str,3); /// Patch for OGP-15077
                            }

                            if ($is_export) { // for export
                                if ($viewVerifiedStatus) {
                                    if( empty( trim($str) ) ){
                                        $str = lang('lang.norecyet');
                                    }else if( $row['verified_email'] == self::DB_TRUE ){
                                        $str = $str . ' ' . lang('player_list.options.verified');
                                    }else{
                                        $str = $str . ' ' . lang('player_list.options.not_verifiedked');
                                    }
                                }
                            }else{ // for HTML
                                if( empty( trim($str) ) ){
                                    $str = '<i class="fa fa-envelope"></i> '. lang('lang.norecyet');
                                }else if ($viewVerifiedStatus) {
                                    if ($row['verified_email'] == self::DB_TRUE) {
                                        $str = '<i class="fa fa-envelope"></i> <span class="text-success">' . $str . '</span>'; // OGP-15089
                                    } else {
                                        $str = '<i class="fa fa-envelope"></i> <span class="text-danger">' . $str . '</span>'; // OGP-15089
                                    }
                                } else {
                                    $str = '<i class="fa fa-envelope"></i> <span class="text-default">' . $str . '</span>'; // OGP-15089
                                }
                            }


                            return $str;
                        },
                    );

                break;
				case 'dialingCode':
                    $columns[] = array(
                        'dt' => $i++,
                        'alias' => 'dialingCode',
                        'select' => 'playerdetails.dialing_code',
                        'name' => lang("Dialing Code"),
						'formatter' => function ($d) use ($is_export) {
                            if ($is_export) { // for export
								if( empty(trim($d)) ){
                                    $str = lang('lang.norecyet');
								}else{
									$str = $d;
								}
                            } else { // for HTML
								if( empty(trim($d)) ){
                                    $str = '<i class="fa fa-phone"></i> '. lang('lang.norecyet');
                                } else {
                                    $str = '<i class="fa fa-phone"></i> <span class="text-default">' . '+' . $d . '</span>';
                                }
                            }
							return $str;
                        },
                    );
                break;
                case 'contactNumber':
                    $columns[] = array(
                        'dt' => $i++, // #20
                        'alias' => 'contactNumber',
                        'select' => 'playerdetails.contactNumber',
                        'name' => lang("player.63"),
                        'formatter' => function ($d, $row) use ($permissions, $viewVerifiedStatus, $is_export) {
                        	if($this->utils->getConfig('default_add_zero_in_contact_number')){
                        		$str = '0'.$d;
                        	}else{
                        		$str = $d;
                        	}
                            if (!$permissions['view_player_detail_contactinfo_cn'] ) {
                                $hide_middle = $this->utils->getConfig('contact_info_hide_middle');
                                if($hide_middle) {
                                    $str = $this->utils->maskMiddleStringLite($str, $hide_middle);
                                }
                                else {
                                    $str = $this->utils->keepTailString($str,3); /// Patch for OGP-15078
                                }
                            }
                            if ($is_export) { // for is_export and display VerifiedStatus.
                                if( empty( trim($str) ) ){
                                    $str = lang('lang.norecyet');
                                }else if ($viewVerifiedStatus) {
                                    if($row['verified_phone'] == self::DB_TRUE ){
                                        $str = $str . ' ' . lang('player_list.options.verified');
                                    }else{
                                        $str = $str . '  ' . lang('player_list.options.not_verifiedked');
                                    }
                                }
                            }else{ // for HTML
                                if( empty( trim($str) ) ){
                                    $str = '<i class="fa fa-phone"></i> '. lang('lang.norecyet');
                                }else {
                                    if ($viewVerifiedStatus) {
                                        if ($row['verified_phone'] == self::DB_TRUE) {
                                            $str = '<i class="fa fa-phone"></i> <span class="text-success">' . $str . '</span>'; // OGP-15088
                                        } else {
                                            $str = '<i class="fa fa-phone"></i> <span class="text-danger">' . $str . '</span>'; // OGP-15088
                                        }
                                    } else {
                                        $str = '<i class="fa fa-phone"></i> <span class="text-default">' . $str . '</span>'; // OGP-15088
                                    }

                                    // for add call_player_tele uri in HTML
                                    if ($permissions['telesales_call']) {
                                        $str = '<span>'.$str.'</span>';
                                    }
                                }
                            }


                            return $str;
                        },
                    );

                break;
                case 'imAccount1':
                    /// patch for OGP-15079 Modify permission of "Search Player IM" and " View Player List Contact Information (IM)": SBE_System >View Roles
                    // if ( $permissions['view_player_detail_contactinfo_im'] ){ // Show: All IM columns Marked: All IM account username replaced by *.
                    # separate im 1 and and im 2 into two columns during export
                if ($is_export) { // for export
                    $columns[] = array(
                        'dt' => $i++, // #21
                        'alias' => 'imAccount1',
                        'name' => lang('player_list.fields.imaccount1'),
                        'select' => 'CONCAT_WS(" ", playerdetails.imAccountType, playerdetails.imAccount)',
                        'formatter' => function ($d) use ($permissions, $utils, $scriptListFormatterImAccount4export) {
                            return $scriptListFormatterImAccount4export($d, $permissions, $utils);
                        },
                    );
                }else {

                    $columns[] = array(
                        'dt' => $i++, // #21
                        'alias' => 'imAccount1',
                        'name' => lang('player_list.fields.imaccount1'),
                        'select' => 'playerdetails.imAccount',
                        'formatter' => function ($d) use ($permissions, $utils, $scriptListFormatterImAccount) {
                            return $scriptListFormatterImAccount($d,$permissions, $utils);
                        },
                    );

                }


                break;
                case 'imAccount2':
                    if ($is_export) { // for export
                        $columns[] = array(
                            'dt' => $i++, // #22
                            'alias' => 'imAccount2',
                            'name' => lang('player_list.fields.imaccount2'),
                            'select' => 'CONCAT_WS(" ", playerdetails.imAccountType2, playerdetails.imAccount2)',
                            'formatter' => function ($d) use ($permissions, $utils, $scriptListFormatterImAccount4export) {
                                return $scriptListFormatterImAccount4export($d, $permissions, $utils);
                            },
                        );
                    }else {
                        $columns[] = array(
                            'dt' => $i++, // #22
                            'alias' => 'imAccount2',
                            'name' => lang('player_list.fields.imaccount2'),
                            'select' => 'playerdetails.imAccount2',
                            'formatter' => function ($d) use ($permissions, $utils, $scriptListFormatterImAccount) {
                                return $scriptListFormatterImAccount($d,$permissions, $utils);
                            },
                        );

                    }
                break;
                case 'imAccount3':
                    if ($is_export) { // for export
                        $columns[] = array(
                            'dt' => $i++, // #23
                            'alias' => 'imAccount3',
                            'name' => lang('player_list.fields.imaccount3'),
                            'select' => 'CONCAT_WS(" ", playerdetails.imAccountType3, playerdetails.imAccount3)',
                            'formatter' => function ($d) use ($permissions, $utils, $scriptListFormatterImAccount4export) {
                                return $scriptListFormatterImAccount4export($d,$permissions, $utils);
                            },
                        );

                    }else{
                        $columns[] = array(
                            'dt' => $i++, // #23
                            'alias' => 'imAccount3',
                            'name' => lang('player_list.fields.imaccount3'),
                            'select' => 'playerdetails.imAccount3',
                            'formatter' => function ($d) use ($permissions, $utils, $scriptListFormatterImAccount) {
                                return $scriptListFormatterImAccount($d,$permissions, $utils);
                            },
                        );
            // EOF if ($is_export)
            // } // EOF if ( $permissions['view_player_detail_contactinfo_im'] ) // Show: All IM columns Marked: All IM account username replaced by *.

                    }
                break;
                case 'imAccount4':
                    if ($is_export) { // for export
                        $columns[] = array(
                            'dt' => $i++, // #22
                            'alias' => 'imAccount4',
                            'name' => lang('player_list.fields.imaccount4'),
                            'select' => 'CONCAT_WS(" ", playerdetails.imAccountType4, playerdetails.imAccount4)',
                            'formatter' => function ($d) use ($permissions, $utils, $scriptListFormatterImAccount4export) {
                                return $scriptListFormatterImAccount4export($d, $permissions, $utils);
                            },
                        );
                    }else {
                        $columns[] = array(
                            'dt' => $i++, // #22
                            'alias' => 'imAccount4',
                            'name' => lang('player_list.fields.imaccount4'),
                            'select' => 'playerdetails.imAccount4',
                            'formatter' => function ($d) use ($permissions, $utils, $scriptListFormatterImAccount) {
                                return $scriptListFormatterImAccount($d,$permissions, $utils);
                            },
                        );

                    }
                break;
				case 'imAccount5':
                    if ($is_export) { // for export
                        $columns[] = array(
                            'dt' => $i++,
                            'alias' => 'imAccount5',
                            'name' => lang('player_list.fields.imaccount5'),
                            'select' => 'CONCAT_WS(" ", playerdetails.imAccountType5, playerdetails.imAccount5)',
                            'formatter' => function ($d) use ($permissions, $utils, $scriptListFormatterImAccount4export) {
                                return $scriptListFormatterImAccount4export($d, $permissions, $utils);
                            },
                        );
                    }else {
                        $columns[] = array(
                            'dt' => $i++,
                            'alias' => 'imAccount5',
                            'name' => lang('player_list.fields.imaccount5'),
                            'select' => 'playerdetails.imAccount5',
                            'formatter' => function ($d) use ($permissions, $utils, $scriptListFormatterImAccount) {
                                return $scriptListFormatterImAccount($d,$permissions, $utils);
                            },
                        );

                    }
                break;
                case 'lastDepositDateTime':
                    if($this->utils->getConfig('display_last_deposit_col') == true){
                        $columns[] = array(
                            'dt' => $i++, // #28.1
                            'alias' => 'lastDepositDateTime',
                            'select' => 'player_last_transactions.last_deposit_date',
                            'name' => lang('player.105'),
                            'formatter' => function ($d, $row) use ($readOnlyDB, $is_export) {
                                if($is_export){
                                    return (!$d || strtotime($d) < 0) ? lang('lang.norecyet') : date('Y-m-d H:i:s', strtotime($d));
                                }else{
                                    return (!$d || strtotime($d) < 0) ? '<i>' . lang('lang.norecyet') . '</i>' : date('Y-m-d H:i:s', strtotime($d));
                                }
                            },
                        );
                    }
				break;
				case 'daysSinceLastDeposit':
                    if($this->utils->getConfig('display_last_deposit_col') == true){
                        $columns[] = array(
                            'dt' => $i++, // #28.1
                            'alias' => 'daysSinceLastDeposit',
                            'select' => 'player_last_transactions.last_deposit_date',
                            'name' => lang('player.DaysSinceLastDeposit'),
                            'formatter' => function ($d, $row) use ($readOnlyDB, $is_export) {
								$today = new DateTime();
								$display_last_deposit_col = new DateTime(date('Y-m-d', strtotime($d)));

								if (!$d || strtotime($d) < 0) {
									$output = $is_export ? lang('lang.norecyet') : '<i>' . lang('lang.norecyet') . '</i>';
								} else {
									$dateDiff = $today->diff($display_last_deposit_col);
									$daysDiff = $dateDiff->days; // 获取时间差的天数部分
									$output = $daysDiff;
								}
								return $output;
							},
                        );
                    }
				break;
                case 'city':
                    $columns[] = array(
                        'dt' => $i++, // #24
                        'alias' => 'city',
                        'select' => 'playerdetails.city',
                        'name' => lang('player_list.fields.city'),  // player_list.fields.country
                        'formatter' => function ($d) use ($is_export) {
                            if ($is_export) {
                                return trim(trim($d), ',') ?: lang('lang.norecyet');
                            } else {
                                return trim(trim($d), ',') ?: '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
                            }
                        },
                    );

                break;
                case 'country':
                    $columns[] = array(
                        'dt' => $i++, // #25
                        'alias' => 'country',
                        'select' => 'playerdetails.residentCountry',
                        'name' => lang("player_list.fields.country"),
                        'formatter' => function ($d) use ($is_export) {
                            if ($is_export) {
                                return trim(trim($d), ',') ?: lang('lang.norecyet');
                            } else {
                                return trim(trim($d), ',') ?: '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
                            }
                        },
                    );

                break;
                case 'birthdate':
                    $columns[] = array(
                        'dt' => $i++, // #26
                        'alias' => 'birthdate',
                        'select' => 'playerdetails.birthdate',
                        'name' => lang("Date of Birth"),
                        'formatter' => function ($d, $row) use ($is_export) {
                            if($is_export){
                                return trim(trim($d), ',') ?: lang('lang.norecyet');
                            }else{
                                return trim(trim($d), ',') ?: '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
                            }
                        },
                    );

                break;
                case 'id_card_number':
                    $columns[] = array(
                        'dt' => $i++, // #27
                        'alias' => 'id_card_number',
                        'select' => 'playerdetails.id_card_number',
                        'name' => lang("player_list.fields.id_card_number"),
                        'formatter' => function ($d) use ($is_export) {
                            if ($is_export) {
                                return trim(trim($d), ',') ?: lang('lang.norecyet');
                            } else {
                                return trim(trim($d), ',') ?: '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
                            }
                        },
                    );

                break;
                case 'priority':
                    if($this->utils->getConfig('enabled_priority_player_features')){
                        $columns[] = array(
                            'dt' => $i++, // #27.1
                            'alias' => 'is_priority',
                            'select' => 'player_in_priority.is_priority',
                            'name' => lang("player_list.fields.priority"), // Priority player.108
                            'formatter' => function ($d) use ($is_export) {
                                $_data = $d;
                                switch($d){
                                    case '1':
                                        $_data = lang('lang.yes');
                                        break;
                                    default:
                                        $_data = lang('lang.no');
                                        break;
                                }
                                return $_data;
                            },

                        );
                    }
                break;
				case 'cpf_number':
                    $columns[] = array(
                        'dt' => $i++, // #27.2
                        'alias' => 'cpf_number',
                        'select' => 'playerdetails.pix_number',
                        'name' => lang("player_list.fields.cpf_number"),
                        'formatter' => function ($d) use ($is_export, $utils, $permissions_player_cpf_number) {
							if( empty($permissions_player_cpf_number) && ! empty($d) ){
								$d = $utils->keepTailString($d, 3);
							}
                            if ($is_export) {
                                return trim(trim($d), ',') ?: lang('lang.norecyet');
                            } else {
                                return trim(trim($d), ',') ?: '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
                            }
                        },
                    );

                break;
                case 'total_deposit_times':
                    $columns[] = array(
                        'dt' => $i++, // #28.1
                        'alias' => 'total_deposit_times',
                        'select' => 'player.approved_deposit_count', // Same as "The Counter form transactions where playerId, type = ransactions::PLAYER, transaction_type=Transactions::DEPOSIT and status=Transactions::APPROVED."
                        'name' => lang('Has Deposit'), // Has Deposit
                        'formatter' => function ($d, $row) use ($readOnlyDB, $is_export) {
                            $str = '';
                            if($d > 0){
                                $str = lang('Have');
                            }else{
                                $str = lang('Have Not');
                            }
                            return $str;
                        },
                    );
				break;
                case 'total_total_nofrozen':
                    $columns[] = array(
                        'dt' => $i++, // #29 // Reopen for OGP-15574
                        'alias' => 'total_total_nofrozen',
                        'select' => 'player.total_total_nofrozen',
                        'name' => lang("Total Balance"), // Balance
                        'formatter' => 'currencyFormatter',
                        // 'formatter' => function ($d) use ($is_export, $wallet_model, $model){
                        //  // $str='0';
                        //  //decode json
                        //  // if(!empty($d)){
                        //      $totalBalance=$wallet_model->getTotalBalanceOnBigWallet($d);
                        //      $str=$model->utils->formatCurrencyNoSym($totalBalance);
                        //  // }
                        //  return $str;
                        // }
                    );

				break;
                case 'total_deposit_amount':
                    $columns[] = array(
                        'dt' => $i++, // #30 // Reopen for OGP-15574
                        'alias' => 'total_deposit_amount',
                        'select' => 'player.totalDepositAmount', // directly referenced from OGP-9124.
                        'name' => lang('Total Deposit Amt'), // Total Deposit
                        'formatter' => 'currencyFormatter',
                    );

				break;

                case 'total_withdrawal_amount':
                    $columns[] = array(
                        'dt' => $i++, // #31 // Reopen for OGP-15574
                        'alias' => 'total_withdrawal_amount',
                        'select' => 'player.approvedWithdrawAmount',
                        'name' => lang('Total Withdrawal Amt'), // Total Withdrawal
                        'formatter' => function ($d, $row) use ($readOnlyDB, $is_export) {
                            // Disable by OGP-9124
                            /*$query = $readOnlyDB->query("SELECT sum(transactions.amount) as total_withdrawal FROM transactions WHERE transactions.to_id = ? AND transactions.to_type = ? AND transactions.transaction_type = ? AND transactions.status = ?  ", array(
                                $row['playerId'],
                                Transactions::PLAYER,
                                Transactions::WITHDRAWAL,
                                Transactions::APPROVED,
                            ));
                            $r = $query->row_array();

                            return $this->data_tables->currencyFormatter(isset($r['total_withdrawal']) ? $r['total_withdrawal'] : 0);*/
                            return $this->data_tables->currencyFormatter(isset($d) ? $d : 0);

                        },
                    );
                break;
				case 'net_cash':
                    $columns[] = array(
                        'dt' => $i++, // #31.5 // Reopen for OGP-31042
                        'alias' => 'net_cash',
						'select' => '( CASE WHEN player.totalDepositAmount IS NULL THEN 0 ELSE player.totalDepositAmount END ) - ( CASE WHEN player.approvedWithdrawAmount IS NULL THEN 0 ELSE player.approvedWithdrawAmount END )',
                        'name' => lang('Net Cash In/Out'), // Net Cash
                        'formatter' => function ($d, $row) use ($readOnlyDB, $is_export) {
							$netCash=!empty($d)?$d:0;
							$this->data_tables->currencyFormatter($netCash);
							if($is_export){
                                return $netCash;
                            }else{
								if($netCash<0){
									$netCash='<font class="text-danger">' . number_format($netCash,2) . '</font>';
								}else{
									$netCash='<font>' . number_format($netCash,2) . '</font>';
								}
							}
                            return $netCash;
                        },
                    );
                break;
                case 'total_betting_amount':
                    $columns[] = array(
                        'dt' => $i++, // #32 // Reopen for OGP-15574
                        'alias' => 'total_betting_amount',
                        'select' => 'player.totalBettingAmount',
                        'name' => lang('Total Bet Amt'), // Total Bets
                        'formatter' => function ($d, $row) use ($readOnlyDB, $is_export) {
                            // Disable by OGP-9124
                            /*$query = $readOnlyDB->query("SELECT  SUM(total_player_game_day.betting_amount) as total_bet FROM total_player_game_day WHERE player_id = ?", array(
                                $row['playerId'],
                            ));
                            $r = $query->row_array();

                            return $this->data_tables->currencyFormatter(isset($r['total_bet']) ? $r['total_bet'] : 0);*/

                            return $this->data_tables->currencyFormatter(isset($d) ? $d : 0);

                        },
                    );

				break;

				case 'approved_deposit_count':
					$columns[] = array(
						'dt' => $i++, // #33
						'alias' => 'total_deposit_count',
						'select' => 'player.approved_deposit_count', // Same as "The Counter form transactions where playerId, type = ransactions::PLAYER, transaction_type=Transactions::DEPOSIT and status=Transactions::APPROVED."
						'name' => lang('Total Deposit Count'), // Has Deposit
						'formatter' => function ($d, $row) use ($readOnlyDB, $is_export) {
							return $d?:0;
						},
					);
				break;
				case 'approved_withdraw_count':
                    $columns[] = array(
                        'dt' => $i++, // #34
                        'alias' => 'approved_withdraw_count',
						'select' => 'player.approvedWithdrawCount',
                        'name' => lang('Total Withdrawal Count'), // Has Deposit
                        'formatter' => function ($d, $row) use ($readOnlyDB, $is_export) {
								/*$query = $readOnlyDB->query("SELECT sum(transactions.amount) as total_withdrawal FROM transactions WHERE transactions.to_id = ? AND transactions.to_type = ? AND transactions.transaction_type = ? AND transactions.status = ?  ", array(
										$row['playerId'],
										Transactions::PLAYER,
										Transactions::WITHDRAWAL,
										Transactions::APPROVED,
									));
									$r = $query->row_array();
								*/
                            return $d?:0;
                        },
                    );
				break;
				case 'affiliate_source_code':
                    $columns[] = array(
						'dt' => $i++,
                        'alias' => 'affCode',
                        'select' => 'player.playerId',
                        'name' => lang("Affiliate Source Code"), // Affiliate Source Code
                        'formatter' => function ($d, $row) use ($is_export){

							$source_code = $this->player_model->getPlayerRegAffCode($row['playerId']);
							if ($is_export) {
                                return $source_code ?: lang('lang.norecyet');
                            } else {
                                return $source_code ?: '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
                            }
                        },
                    );
				break;
				case 'gender':
					$columns[] = array(
						'dt' => $i++,
                        'alias' => 'gender',
                        'select' => 'playerdetails.gender',
                        'name' => lang("Gender"), // gender
						'formatter' => function ($d, $row) use ($readOnlyDB, $is_export) {
							return $d?:'-';
						},
					);
				break;
				case 'player_sales_agent':
					if($this->utils->getConfig('enabled_sales_agent')){
						$columns[] = array(
							'dt' => $i++,
							'alias' => 'sales_agent_id',
							'select' => 'player_sales_agent.sales_agent_id',
							'name' => lang('Has Sales Agent'),
							'formatter' => function ($d, $row) use ($readOnlyDB, $is_export) {
								$str = '';
								if($d > 0){
									$str = lang('Have');
								}else{
									$str = lang('Have Not');
								}
								return $str;
							},
						);
					}
				break;
            }
        }

        $columns[] = array(
            // 'dt' => $i++, // A1. Remove items from the table:ID
            'alias' => 'playerId',
            'select' => 'player.playerId',
            'name' => lang('ID'), // ID
        );
        $columns[] = array(
            'alias' => 'verified_email',
            'select' => 'player.verified_email',
        );
        $columns[] = array(
            'alias' => 'verified_phone',
            'select' => 'player.verified_phone',
        );
        $columns[] = array(
            // 'dt' => $i++, // A1. Remove items from the table: Total First Deposit
            'alias' => 'first_deposit',
            'select' => 'player.first_deposit',
            'name' => lang('report.pr19'), // Total First Deposit
            'formatter' => function ($d, $row) use ($readOnlyDB, $is_export) {
                /*$query = $readOnlyDB->query("SELECT transactions.amount FROM transactions JOIN player ON player.playerId = transactions.to_id WHERE transactions.to_id = ? AND  transactions.to_type = ?  AND transactions.transaction_type = ? AND transactions.status = ? ORDER BY created_at ASC LIMIT ?,1", array(
                    $row['playerId'],
                    Transactions::PLAYER,
                    //$d,
                    Transactions::DEPOSIT,
                    Transactions::APPROVED,
                    0, # FIRST DEPOSIT
                ));
                $row = $query->row_array();
                return $this->data_tables->currencyFormatter(isset($row['amount']) ? $row['amount'] : 0);*/

                return $this->data_tables->currencyFormatter(isset($d) ? $d : 0);
            },
        );
        $columns[] = array(
            // 'dt' => $i++, // A1. Remove items from the table: Total Second Deposit
            'alias' => 'second_deposit',
            'select' => 'player.second_deposit',
            'name' => lang('report.pr20'), // Total Second Deposit
            'formatter' => function ($d, $row) use ($readOnlyDB, $is_export) {
                /*$query = $readOnlyDB->query("SELECT transactions.amount FROM transactions JOIN player ON player.playerId = transactions.to_id WHERE transactions.to_id = ? AND transactions.to_type = ? AND transactions.transaction_type = ? AND transactions.status = ? ORDER BY created_at ASC LIMIT ?,1", array(
                    $row['playerId'],
                    Transactions::PLAYER,
                    //$d,
                    Transactions::DEPOSIT,
                    Transactions::APPROVED,
                    1, # SECOND DEPOSIT
                ));
                $row = $query->row_array();
                return $this->data_tables->currencyFormatter(isset($row['amount']) ? $row['amount'] : 0);*/

                return $this->data_tables->currencyFormatter(isset($d) ? $d : 0);

            },
        );

        if($this->utils->isEnabledFeature('show_allowed_withdrawal_status') && $this->utils->isEnabledFeature('show_risk_score') && $this->utils->isEnabledFeature('show_kyc_status')){
            $columns[] = array(
                // 'dt' => $i++, // A1. Remove items from the table: Allowed Withdrawal Status
                'alias' => 'allowedWithdrawalStatus',
                'select' => 'playerdetails.playerId',
                'name' => lang("Allowed Withdrawal Status"), // Allowed Withdrawal Status
                'formatter' => function ($d) use ($is_export,$risk_score_model) {
                    return ($risk_score_model->generate_allowed_withdrawal_status($d)) ? lang('Yes') : lang('No');
                },

            );
        }
        if($this->utils->isEnabledFeature('verification_reference_for_player')){
            $columns[] = array(
                // 'dt' => $i++, // A1. Remove items from the table: Verified
                'alias' => 'verified',
                'select' => 'playerdetails.manual_verification',
                'name' => lang("Verified"), // Verified
                'formatter' => ($is_export) ?
                function ($d) use ($is_export){
                    return ($d == 1) ? 'Yes' : 'No';
                } :
                function($d, $row){
                    ($d == 1) ?
                    $output = '<i class="fa fa-check text-success" title="Yes"></i>'
                    : $output = '<i class="fa fa-close text-danger" title="No"></i>';
                    return $output;
                }
            );
        }
        // $columns[] = array(
        //     // 'dt' => $i++, // A1. Remove items from the table: Device
        //     'alias' => 'device',
        //     'select' => 'player_device_last_request.device',
        //     'name' => lang('con.plm71'), // Device
        //     'formatter' => function ($d) use ($is_export) {
        //         if ($is_export) {
        //             return trim(trim($d), ',') ?: lang('lang.norecyet');
        //         } else {
        //             return trim(trim($d), ',') ?: '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
        //         }
        //     },
        // );
        if(!$this->utils->isEnabledFeature('show_zip_code_in_list')){
            $columns[] = array(
                // 'dt' => $i++, // A1. Remove items from the table: Zip Code
                'alias' => 'zipcode',
                'select' => 'playerdetails.zipcode',
                'name' => lang("player.60"), // Zip Code
                'formatter' => function ($d) use ($is_export) {
                    if ($is_export) {
                        return trim(trim($d), ',') ?: lang('lang.norecyet');
                    } else {
                        return trim(trim($d), ',') ?: '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
                    }
                },


            );
        }

        //update array content if EnabledFeature add_close_status
        if (!$is_export) {
            $columns[] = array(
                // 'dt' => $i++, // A1. Remove items from the table:Attached File
                'alias' => 'proof_filename',
                'select' => 'player.playerId',
                'name' => lang('attached_file'), // Attached File
                'formatter' => function ($d, $row) use ($readOnlyDB, $is_export, $player_attached_proof_file_model) {
                    if($is_export){
                        return '';
                    }
                    $output = '<a onclick="modal(\'/player_management/player_attach_document/' . $row['playerId'] . '\',\'' . lang('Attached document') . '\')" href="javascript:void(0);" data-toggle="tooltip" title="View attached file"><i class="fa fa-search"></i></a>';
                    $imageInfo = $player_attached_proof_file_model->getAttachementRecordInfo($d);
                    if(!empty($imageInfo)){
                        $output .= ' Y';
                    }else{
                        $output .= ' N';
                    }
                    return $output;
                }
            );
        }

        $columns[] = array(
            'dt' => $i++, // #33 for OGP-15575
            'alias' => 'kyc_level',
            'select' => 'player.playerId', // 'playerdetails.playerId',
            'formatter' => function ($d) use ($is_export,$player_kyc,$kyc_status_model) {
                if ($is_export) {
                    return $player_kyc->getPlayerCurrentKycLevel($d) && $kyc_status_model->getPlayerCurrentStatus($d) ? $player_kyc->getPlayerCurrentKycLevel($d) .' / '. $kyc_status_model->getPlayerCurrentStatus($d): lang('lang.norecyet');
                }else{
                    return $player_kyc->getPlayerCurrentKycLevel($d) && $kyc_status_model->getPlayerCurrentStatus($d) ? $player_kyc->getPlayerCurrentKycLevel($d) .' / '. $kyc_status_model->getPlayerCurrentStatus($d): '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
                }
            },
            'name' => lang("KYC Level/Rate Code"), // KYC Level/Rate Code
        );
        if ($this->utils->isEnabledFeature('show_risk_score')):
            $columns[] = array(
                'dt' => $i++, // #34 for OGP-15575
                'alias' => 'risk_level',
                'select' => 'player.playerId', // 'playerdetails.playerId',
                'formatter' => function ($d) use ($is_export,$risk_score_model) {
                    if ($is_export) {
                        return $risk_score_model->getPlayerCurrentRiskLevel($d) && $risk_score_model->generate_total_risk_score($d) ? $risk_score_model->getPlayerCurrentRiskLevel($d) .' / '. $risk_score_model->generate_total_risk_score($d) : lang('lang.norecyet');
                    }else{
                        return $risk_score_model->getPlayerCurrentRiskLevel($d) && $risk_score_model->generate_total_risk_score($d) ? $risk_score_model->getPlayerCurrentRiskLevel($d) .' / '. $risk_score_model->generate_total_risk_score($d) : '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
                    }
                },
                'name' => lang("Risk Level/Score"), // Risk Level/Score
            );
        endif;


        $columns[] = array(
            // 'dt' => $i++, // A1. Remove items from the table: Total First Deposit
            'alias' => 'first_deposit',
            'select' => 'player.first_deposit',
            'name' => lang('report.pr19'), // Total First Deposit
            'formatter' => function ($d, $row) use ($readOnlyDB, $is_export) {
                /*$query = $readOnlyDB->query("SELECT transactions.amount FROM transactions JOIN player ON player.playerId = transactions.to_id WHERE transactions.to_id = ? AND  transactions.to_type = ?  AND transactions.transaction_type = ? AND transactions.status = ? ORDER BY created_at ASC LIMIT ?,1", array(
                    $row['playerId'],
                    Transactions::PLAYER,
                    //$d,
                    Transactions::DEPOSIT,
                    Transactions::APPROVED,
                    0, # FIRST DEPOSIT
                ));
                $row = $query->row_array();
                return $this->data_tables->currencyFormatter(isset($row['amount']) ? $row['amount'] : 0);*/

                return $this->data_tables->currencyFormatter(isset($d) ? $d : 0);
            },
        );
        $columns[] = array(
            // 'dt' => $i++, // A1. Remove items from the table: Total Second Deposit
            'alias' => 'second_deposit',
            'select' => 'player.second_deposit',
            'name' => lang('report.pr20'), // Total Second Deposit
            'formatter' => function ($d, $row) use ($readOnlyDB, $is_export) {
                /*$query = $readOnlyDB->query("SELECT transactions.amount FROM transactions JOIN player ON player.playerId = transactions.to_id WHERE transactions.to_id = ? AND transactions.to_type = ? AND transactions.transaction_type = ? AND transactions.status = ? ORDER BY created_at ASC LIMIT ?,1", array(
                    $row['playerId'],
                    Transactions::PLAYER,
                    //$d,
                    Transactions::DEPOSIT,
                    Transactions::APPROVED,
                    1, # SECOND DEPOSIT
                ));
                $row = $query->row_array();
                return $this->data_tables->currencyFormatter(isset($row['amount']) ? $row['amount'] : 0);*/

                return $this->data_tables->currencyFormatter(isset($d) ? $d : 0);

            },
        );

        if($this->utils->isEnabledFeature('show_allowed_withdrawal_status') && $this->utils->isEnabledFeature('show_risk_score') && $this->utils->isEnabledFeature('show_kyc_status')){
            $columns[] = array(
                // 'dt' => $i++, // A1. Remove items from the table: Allowed Withdrawal Status
                'alias' => 'allowedWithdrawalStatus',
                'select' => 'playerdetails.playerId',
                'name' => lang("Allowed Withdrawal Status"), // Allowed Withdrawal Status
                'formatter' => function ($d) use ($is_export,$risk_score_model) {
                    return ($risk_score_model->generate_allowed_withdrawal_status($d)) ? lang('Yes') : lang('No');
                },

            );
        }
        if($this->utils->isEnabledFeature('verification_reference_for_player')){
            $columns[] = array(
                // 'dt' => $i++, // A1. Remove items from the table: Verified
                'alias' => 'verified',
                'select' => 'playerdetails.manual_verification',
                'name' => lang("Verified"), // Verified
                'formatter' => ($is_export) ?
                function ($d) use ($is_export){
                    return ($d == 1) ? 'Yes' : 'No';
                } :
                function($d, $row){
                    ($d == 1) ?
                    $output = '<i class="fa fa-check text-success" title="Yes"></i>'
                    : $output = '<i class="fa fa-close text-danger" title="No"></i>';
                    return $output;
                }
            );
        }
        if(!$this->utils->isEnabledFeature('show_zip_code_in_list')){
            $columns[] = array(
                // 'dt' => $i++, // A1. Remove items from the table: Zip Code
                'alias' => 'zipcode',
                'select' => 'playerdetails.zipcode',
                'name' => lang("player.60"), // Zip Code
                'formatter' => function ($d) use ($is_export) {
                    if ($is_export) {
                        return trim(trim($d), ',') ?: lang('lang.norecyet');
                    } else {
                        return trim(trim($d), ',') ?: '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
                    }
                },


            );
        }

        //update array content if EnabledFeature add_close_status
        if (!$is_export) {
            $columns[] = array(
                // 'dt' => $i++, // A1. Remove items from the table:Attached File
                'alias' => 'proof_filename',
                'select' => 'player.playerId',
                'name' => lang('attached_file'), // Attached File
                'formatter' => function ($d, $row) use ($readOnlyDB, $is_export, $player_attached_proof_file_model) {
                    if($is_export){
                        return '';
                    }
                    $output = '<a onclick="modal(\'/player_management/player_attach_document/' . $row['playerId'] . '\',\'' . lang('Attached document') . '\')" href="javascript:void(0);" data-toggle="tooltip" title="View attached file"><i class="fa fa-search"></i></a>';
                    $imageInfo = $player_attached_proof_file_model->getAttachementRecordInfo($d);
                    if(!empty($imageInfo)){
                        $output .= ' Y';
                    }else{
                        $output .= ' N';
                    }
                    return $output;
                }
            );
        }


        # END DEFINE COLUMNS #################################################################################################################################################
        $this->benchmark->mark('data_gathering_start');
        $table = 'player';
        $innerJoins = array();
        $joins = array(
            'playerdetails' => 'playerdetails.playerId = player.playerId',
            'player_runtime' => 'player_runtime.playerId = player.playerId',
            'affiliates' => 'affiliates.affiliateId = player.affiliateId',
            // 'player_device_last_request' => 'player_device_last_request.player_id = player.playerId',
            // 'affiliate_traffic_stats' => 'affiliate_traffic_stats.player_id = player.playerId',
            // 'player_last_transactions' => 'player_last_transactions.player_id = player.playerId'
        );
		if ($this->utils->getConfig('enabled_sales_agent')) {
			$joins['player_sales_agent'] = 'player_sales_agent.player_id = player.playerId';
		}
		if ($this->utils->getConfig('enabled_priority_player_features')) {
			$joins['player_in_priority'] = 'player_in_priority.player_id = player.playerId';
		}

        if ($this->utils->getConfig('display_last_deposit_col')) {
			$joins['player_last_transactions'] = 'player_last_transactions.player_id = player.playerId';
        }
        $group_by = [];

        # START PROCESS SEARCH FORM #################################################################################################################################################
        $where = array();
        $values = array();
        $group_by = array();

        if($this->utils->isEnabledFeature('show_allowed_withdrawal_status') && $this->utils->isEnabledFeature('show_risk_score') && $this->utils->isEnabledFeature('show_kyc_status')){
            if(isset($input['allowed_withdrawal_status'])){
                $where[] = "player.allowed_withdrawal_status = ?";
                $values[] = $input['allowed_withdrawal_status'];
            }
        }

        if (isset($input['username'], $input['search_by'])) {
            if ($input['search_by'] == 1) {
                $where[] = "player.username LIKE ?";
                $values[] = '%' . $input['username'] . '%';
            } else if ($input['search_by'] == 2) {
                $where[] = "player.username = ?";
                $values[] = $input['username'];
            }
        }

        if (isset($input['affiliate_source_code'])) {
			$joins['affiliate_traffic_stats'] = 'affiliate_traffic_stats.player_id = player.playerId';
            $where[] = "affiliate_traffic_stats.tracking_source_code = ?";
            $values[] = $input['affiliate_source_code'];
        }

		if (isset($input['cpf_number']) && $input['cpf_number'] != '' ) {
            $where[] = "playerdetails.pix_number = ?";
            $values[] = $input['cpf_number'];
        }

        if (isset($input['email'])
            && ( $permissions['view_player_detail_contactinfo_em']
            || true // Patch for OGP-15540
        ) ) {
            $where[] = "player.email LIKE ?";
            $values[] = '%' . $input['email'] . '%';
        }

        if (isset($input['registration_date_from'], $input['registration_date_to'])) {

			$input['registration_date_from'] = $this->_getDatetimeWithTimezone($input['timezone'], $input['registration_date_from']);
			$input['registration_date_to'] = $this->_getDatetimeWithTimezone($input['timezone'], $input['registration_date_to']);

            $where[] = "player.createdOn BETWEEN ? AND ?";
            $values[] = $input['registration_date_from'];
            $values[] = $input['registration_date_to'];
            $totalRegisteredPlayers['createdOn >='] = $input['registration_date_from'];
            $totalRegisteredPlayers['createdOn <='] = $input['registration_date_to'];
        } else {
			$beginOfCurrentDay = $this->_getDatetimeWithTimezone($input['timezone'], date('Y-m-d 00:00:00'));
			$endOfCurrentDay = $this->_getDatetimeWithTimezone($input['timezone'], date('Y-m-d 23:59:59'));

            $totalRegisteredPlayers['createdOn >='] = $beginOfCurrentDay;
            $totalRegisteredPlayers['createdOn <='] = $endOfCurrentDay;
        }

        if (isset($input['last_login_date_from'], $input['last_login_date_to'])) {

			$input['last_login_date_from'] = $this->_getDatetimeWithTimezone($input['timezone'], $input['last_login_date_from']);
			$input['last_login_date_to'] = $this->_getDatetimeWithTimezone($input['timezone'], $input['last_login_date_to']);

            $where[] = "player_runtime.lastLoginTime >= ?";
            $values[] = $input['last_login_date_from'];
            $where[] = "player_runtime.lastLoginTime <= ?";
            $values[] = $input['last_login_date_to'];
        }

        // OGP-11882, Date of Birth
        if (isset($input['dob_from'], $input['dob_to'])) {
            if( ! isset($input['with_year']) ){
                $with_year = true;
            }else{
                if( $input['with_year'] ){
                    $with_year = true;
                }else{
                    $with_year = false;
                }
            }

            if($with_year){
				$dob_from = $input['dob_from'];
				$dob_to = $input['dob_to'];
				/// Precision unit is day, not hour.
				// $dob_from = $this->_getDatetimeWithTimezone($input['timezone'], $dob_from);
				// $dob_from = $this->utils->formatDateForMysql(new DateTime($dob_from) );
				//
				// $dob_to = $this->_getDatetimeWithTimezone($input['timezone'], $dob_to);
				// $dob_to = $this->utils->formatDateForMysql(new DateTime($dob_to) );

                // VARCHAR convert to DATE
                $where[] = "STR_TO_DATE(playerdetails.birthdate,'%Y-%m-%d') BETWEEN STR_TO_DATE(?,'%Y-%m-%d')  AND STR_TO_DATE(?,'%Y-%m-%d')";
                $values[] = $dob_from;
                $values[] = $dob_to;
            }else{ // without year

                // Get month and date of every year in the specified range.
                $currYear = date("Y");
                $dob_from = $currYear. '-'. $input['dob_from'];
                $dob_to = $currYear. '-'. $input['dob_to'];

				/// Precision unit is day, not hour.
				// $dob_from = $this->_getDatetimeWithTimezone($input['timezone'], $dob_from);
				// $dob_from = $this->utils->formatDateForMysql(new DateTime($dob_from) );
				//
				// $dob_to = $this->_getDatetimeWithTimezone($input['timezone'], $dob_to);
				// $dob_to = $this->utils->formatDateForMysql(new DateTime($dob_to) );


                $period = new DatePeriod(
                    new DateTime($dob_from),
                    new DateInterval('P1D'),
                    new DateTime($dob_to)
               );
               $mdRange = array();
               foreach ($period as $key => $value) {
                    $md = $value->format('m-d');
                    $mdRange[$md] = $md;
                }
                // append endDate of period
                $endDate = $period->getEndDate();
                $md = $endDate->format('m-d');
                $mdRange[$md] = $md;
                // Detect 02-29 and append into $mdRange
                $lookup0228 = array_key_exists('02-28',$mdRange);
                $lookup0301 = array_key_exists('03-01',$mdRange);
                if($lookup0228 && $lookup0301){
                    $md = '02-29';
                    $mdRange[$md] = $md;
                }
                // combine dates of every year
                $subWhere = array();
                foreach ($mdRange as $key => $value) {
                    $subWhere[] = "playerdetails.birthdate LIKE '%-$value'";
                }
                $where[] = '('. implode(' OR ', $subWhere). ')';

                /// Patch/Disable for OGP-15372 New player list DOB searching condition issue
                // $values[] = $input['dob_from'];
                // $values[] = $input['dob_to'];

            } // EOF if($with_year)
        } // EOF if (isset($input['dob_from'], $input['dob_to']))

        if (isset($input['registered_by'])) {
            if( $input['registered_by'] == Player_model::REGISTERED_BY_AGENCY_CREATED ){ // Patch for OGP-15085 Modify the logic of condition_Registered By: SBE_Player > All Player
                $where[] = "player.agent_id IS NOT NULL /* registered_by=created_on_agency */ ";
                $where[] = "(player.registered_by = ? OR player.registered_by = ? /* registered_by=created_on_agency */ )";
                $values[] = Player_model::REGISTERED_BY_MASS_ACCOUNT;
                $values[] = Player_model::REGISTERED_BY_AGENCY_CREATED;
            }else{
                $where[] = "player.registered_by = ?";
                $values[] = $input['registered_by'];
            }
        }

        if (isset($input['ip_address'])) {
            $where[] = "playerdetails.registrationIp = ?";
            $values[] = $input['ip_address'];
        }

        if (isset($input['lastLoginIp'])) {
            $where[] = "player_runtime.lastLoginIp = ?";
            $values[] = $input['lastLoginIp'];
        }

        // if (isset($input['device'])) {
        //     $where[] = "player_device_last_request.device = ?";
        //     $values[] = $input['device'];
        // }

        if (isset($input['blocked'])) {
        	$this->load->model(array('responsible_gaming'));
        	$responsible_gaming_condition = $this->responsible_gaming->getPlayerIdByTypeAndStatus();
            switch($input['blocked']){
                case 0: //Normal
                    // $where[] = "player.blockedUntil = 0"; // ignore
                    $where[] = "player.blocked = ?";
                    if ($this->utils->isEnabledFeature('responsible_gaming')) {
                        if( ! empty($responsible_gaming_condition) ){
                            $where[] = "player.playerId NOT IN ($responsible_gaming_condition)";
                        }
                    }
                    $values[] = $input['blocked'];
                    break;
                case Player_model::BLOCK_STATUS:
                case Player_model::SUSPENDED_STATUS:
                case Player_model::STATUS_BLOCKED_FAILED_LOGIN_ATTEMPT:
                    $where[] = "player.blocked = ?";
                    $values[] = $input['blocked'];
                    if ($this->utils->isEnabledFeature('responsible_gaming')) {
                        if( ! empty($responsible_gaming_condition) ){
                            $where[] = "player.playerId NOT IN ($responsible_gaming_condition)";
                        }
                    }
                    break;
                case Player_model::SELFEXCLUSION_STATUS:
                    if( ! empty($responsible_gaming_condition) ){
                        $where[] = "player.playerId NOT IN ($responsible_gaming_condition)";
                    }
                    break;
            }
            $this->utils->debug_log("blocked in post");
        }else{
            $this->utils->debug_log("blocked not in post");
        }

        if (isset($input['player_level'])) {
			$player_level = is_array($input['player_level']) ? implode(',', $input['player_level']) : $input['player_level'];
            $where[] = "player.levelId in (" . $player_level .")";
        }

        if (isset($input['friend_referral_code'])) {
            $where[] = "player.invitationCode = ?";
            $values[] = $input['friend_referral_code'];
        }

        if (isset($input['deposit'])) {
            switch ($input['deposit']) {
            case 0:
                $where[] = "player.totalDepositAmount <= 0";
                break;

            case 1:
                $where[] = "player.totalDepositAmount > 0";
                break;
            }
        }

        if (isset($input['first_name'])) {
            $where[] = "playerdetails.firstName LIKE ?";
            $values[] = '%' . $input['first_name'] . '%';
        }

        if (isset($input['last_name'])) {
            $where[] = "playerdetails.lastName LIKE ?";
            $values[] = '%' . $input['last_name'] . '%';
        }

        if (isset($input['registration_website'], $input['reg_web_search_by'])) {
            if ($input['reg_web_search_by'] == 1) {
                $where[] = "playerdetails.registrationWebsite LIKE ?";
                $values[] = '%' . $input['registration_website'] . '%';
            } else if ($input['reg_web_search_by'] == 2) {
                $where[] = "playerdetails.registrationWebsite = ?";
                $values[] = $input['registration_website'];
            }
        }

        if (isset($input['priority'])) {
            if(empty($input['priority'])){
                $where[] = "(player_in_priority.is_priority = 0 OR player_in_priority.is_priority IS NULL)";
            }else{
                $where[] = "player_in_priority.is_priority = ?";
                $values[] = $input['priority'];
            }
        }
        if (isset($input['city'])) {
            $where[] = "playerdetails.city = ?";
            $values[] = $input['city'];
        }

        if (isset($input['residentCountry'])) {
         $where[] = "playerdetails.residentCountry = ?";
         $values[] = $input['residentCountry'];
        }

        if (isset($input['im_account'])
            && ( $permissions['view_player_detail_contactinfo_im']
            || true // Patch for OGP-15540
        ) ) {
            $where[] = "(playerdetails.imAccount = ? OR playerdetails.imAccount2 = ? OR playerdetails.imAccount3 = ? OR playerdetails.imAccount4 = ?)";
            $values[] = $input['im_account'];
            $values[] = $input['im_account'];
            $values[] = $input['im_account'];
            $values[] = $input['im_account'];
        }

        /// total_balance_more_than / total_balance_less_than convert to wallet_order
        if ( isset($input['total_balance_more_than']) ) {
            $input['wallet_order'] = '-1';
            $input['wallet_amount_from'] = $input['total_balance_more_than'];
        }
        if ( isset($input['total_balance_less_than']) ){
            $input['wallet_order'] = '-1';
            $input['wallet_amount_to'] = $input['total_balance_less_than'];
        }

        if (isset($input['wallet_order']) && (isset($input['wallet_amount_from']) || isset($input['wallet_amount_to']))) {
            if ($input['wallet_order'] == '-1') {
                if (isset($input['wallet_amount_from'])) {
                    $where[] = "player.total_total_nofrozen >= ?";
                    $values[] = $input['wallet_amount_from'];
                }

                if (isset($input['wallet_amount_to'])) {
                    $where[] = "player.total_total_nofrozen <= ?";
                    $values[] = $input['wallet_amount_to'];
                }
            } else {
                $joins['playeraccount'] = 'playeraccount.playerId = player.playerId';

                if ($input['wallet_order'] == 0) {
                    $where[] = "playeraccount.type = ?";
                    $values[] = 'wallet';
                } else {
                    $where[] = "playeraccount.type = ? AND playeraccount.typeId = ?";
                    $values[] = 'subwallet';
                    $values[] = $input['wallet_order'];
                }

                if (isset($input['wallet_amount_from'])) {
                    $where[] = "playeraccount.totalBalanceAmount >= ?";
                    $values[] = $input['wallet_amount_from'];
                }

                if (isset($input['wallet_amount_to'])) {
                    $where[] = "playeraccount.totalBalanceAmount <= ?";
                    $values[] = $input['wallet_amount_to'];
                }
            }
        }

        if (isset($input['blocked_gaming_networks'])) {
            $joins['game_provider_auth'] = 'game_provider_auth.player_id = player.playerId';
            $where[] = "game_provider_auth.game_provider_id = ? AND is_blocked = ?";
            $values[] = $input['blocked_gaming_networks'];
            $values[] = 1;
        }

        if (isset($input['promo']) || isset($input['promoCode'])) {
            $joins['playerpromo'] = 'playerpromo.playerId = player.playerId';

            if (isset($input['promo'])) {
                $where[] = "playerpromo.promorulesId = ?";
                $values[] = $input['promo'];
            }

            if (isset($input['promoCode'])) {
                $joins['promorules'] = 'promorules.promorulesId = playerpromo.promorulesId';
                $where[] = "promorules.promoCode = ?";
                $values[] = $input['promoCode'];
            }
        }


		if (isset($input['latest_deposit_date_from'], $input['latest_deposit_date_to'])) {

			$joins['player_last_transactions'] = 'player_last_transactions.player_id = player.playerId';

			$input['latest_deposit_date_from'] = $this->_getDatetimeWithTimezone($input['timezone'], $input['latest_deposit_date_from']);
			$input['latest_deposit_date_to'] = $this->_getDatetimeWithTimezone($input['timezone'], $input['latest_deposit_date_to']);

            $where[] = "player_last_transactions.last_deposit_date BETWEEN ? AND ? ";
            $values[] = $input['latest_deposit_date_from'];
            $values[] = $input['latest_deposit_date_to'];
		}


        if (isset($input['affiliate'])) {

            $this->load->model('affiliatemodel');
            $affiliateId = $this->affiliatemodel->getAffiliateIdByUsername($input['affiliate']);
            if(!empty($affiliateId)){
                if (!empty($input['aff_include_all_downlines'])) {
                    $affiliateId_array = $this->affiliatemodel->getDirectDownlinesAffiliateIdsByParentId($affiliateId);
                    array_push($affiliateId_array, $affiliateId); # add self
                    $affiliateIds = implode(',', $affiliateId_array);

                    $where[] = 'player.affiliateId IN('.$affiliateIds.')';
                } else {
                    $where[] = "player.affiliateId = ?";
                    $values[] = $affiliateId;
                }
            } else {
				$where[] = "player.affiliateId = ?";
				$values[] = $input['affiliate'];
			}
        }

        if (!empty($input['referred_by'])) {
            $referred_player = $this->player_model->getPlayerByUsername($input['referred_by']);
            $where[] = "player.refereePlayerId = ? /* referred_by */";
            if($referred_player) {
                $values[] = $referred_player->playerId;
            } else {
                $values[] = NULL;
            }
        }

        if (isset($input['deposit_count'])) {
            $where[] = "player.approved_deposit_count = ? /* deposit_count */";
            $values[] = $input['deposit_count'];
        }

        if ( isset($input['total_deposit_count_more_than']) ) {
        	$where[] = "player.approved_deposit_count >= ?";
            $values[] = $input['total_deposit_count_more_than'];
        }
        if ( isset($input['total_deposit_count_less_than']) ){
            $where[] = "player.approved_deposit_count <= ?";
            $values[] = $input['total_deposit_count_less_than'];
        }

        if ( isset($input['total_deposit_more_than']) ) {
			$where[] = "player.totalDepositAmount >= ?";
            $values[] = $input['total_deposit_more_than'];
        }
        if ( isset($input['total_deposit_less_than']) ){
            $where[] = "player.totalDepositAmount <= ?";
            $values[] = $input['total_deposit_less_than'];
        }

        if (isset($input['agent_name'])) {
            $agent = $this->agency_model->get_agent_by_name($input['agent_name']);
            if (!empty($agent['agent_id']) && isset($input['own_downline_or_agency_line'])) {
                $agent_id = $agent['agent_id'];
                $own_downline_or_agency_line = $input['own_downline_or_agency_line'];

                if ($own_downline_or_agency_line == 'own_downline') {
                    $where[] = 'player.agent_id = ? /* own_downline_or_agency_line=direct_downline */';
                    $values[] = $agent_id;
                } elseif ($own_downline_or_agency_line == 'agency_line') {
                    // 代理下面的整條玩家
                    $idArr=$this->agency_model->get_all_downline_arr($agent_id);
                    $where[] = 'player.agent_id IN(' . implode(',', $idArr) . ') /* own_downline_or_agency_line=agency_line */';
                    // if($this->utils->getConfig('display_last_deposit_col') == false){
                    //     $group_by[] = 'player.playerId';
                    // }
                }
            } else {
				$where[] = 'player.agent_id = ?';
				$values[] = $input['agent_name'];
			}
        }

        if (isset($input['account_type'])) {
            $joins['playeraccount playertype'] = 'playertype.playerId = player.playerId';
            $where[] = "playertype.typeOfPlayer = ?";
            $values[] = $input['account_type'];
        }

        # deprecated
        if (isset($input['tagged'])) {

            $joins['playertag'] = 'playertag.playerId = player.playerId';

            if($input['tagged'] == 'no_tag'){
                $where[] = "playertag.tagId IS NULL";
            } else {
                $where[] = "playertag.tagId = ?";
                $values[] = $input['tagged'];
            }
        }

        if (isset($input['tag_list'])) {

            $tag_list = $input['tag_list'];

            if(is_array($tag_list)) {
                $notag = array_search('notag',$tag_list);
                if($notag !== false) {
                    $where[] = 'player.playerId IN (SELECT DISTINCT playerId FROM playertag)';
                    unset($tag_list[$notag]);
                }

            } elseif ($tag_list == 'notag') {
                $where[] = 'player.playerId IN (SELECT DISTINCT playerId FROM playertag)';
                $tag_list = null;
            }

            if (!empty($tag_list)) {
                $tagList = is_array($tag_list) ? implode(',', $tag_list) : $tag_list;
                $where[] = 'player.playerId NOT IN (SELECT DISTINCT playerId FROM playertag WHERE playertag.tagId IN ('.$tagList.'))';
            }
        }

		if (isset($input['include_tag_list'])) {
            $tag_list = $input['include_tag_list'];
			$is_include_notag = null;
            if(is_array($tag_list)) {
                $notag = array_search('notag',$tag_list);
                if($notag !== false) {
                    unset($tag_list[$notag]);
					$is_include_notag = true;
                }else{
					$is_include_notag = false;
				}
            } elseif ($tag_list == 'notag') {
                $tag_list = null;
				$is_include_notag = true;
            }

			$where_fragments = [];
			if($is_include_notag){
				$where_fragments[] = 'player.playerId NOT IN (SELECT DISTINCT playerId FROM playertag)';
			}

            if ( ! empty($tag_list) ) {
                $tagList = is_array($tag_list) ? implode(',', $tag_list) : $tag_list;
				$where_fragments[] =  'player.playerId IN (SELECT DISTINCT playerId FROM playertag WHERE playertag.tagId IN ('.$tagList.'))';
            }
			if( ! empty($where_fragments) ){
				$where[] = '('. implode(' OR ', $where_fragments ). ') ';
			}
        } // EOF if (isset($input['include_tag_list'])) {...


		if (isset($input['ip_tag_list'])) {

            $ip_tag_list = $input['ip_tag_list'];

            if(is_array($ip_tag_list)) {
                $notag = array_search('notag',$ip_tag_list);
                if($notag !== false) {
                    // $where[] = 'player.playerId IN (SELECT DISTINCT playerId FROM playertag)';
					$where[] = 'playerdetails.registrationIP IN (SELECT DISTINCT ip FROM ip_tag_list)';

                    unset($ip_tag_list[$notag]);
                }

            } elseif ($ip_tag_list == 'notag') {
                // $where[] = 'player.playerId IN (SELECT DISTINCT playerId FROM playertag)';
				$where[] = 'playerdetails.registrationIP IN (SELECT DISTINCT ip FROM ip_tag_list)';
                $ip_tag_list = null;
            }

            if (!empty($ip_tag_list)) {
                $ip_tagList = is_array($ip_tag_list) ? implode(',', $ip_tag_list) : $ip_tag_list;
                // $where[] = 'player.playerId NOT IN (SELECT DISTINCT playerId FROM playertag WHERE playertag.tagId IN ('.$ip_tagList.'))';
				$where[] = '( playerdetails.registrationIP IS NULL OR playerdetails.registrationIP NOT IN (SELECT DISTINCT ip FROM ip_tag_list WHERE ip_tag_list.id IN ('.$ip_tagList.')) )';

            }
        }

        if (isset($input['contactNumber'])
            && ( $permissions['view_player_detail_contactinfo_cn']
            || true // Patch for OGP-15540
        ) ) {
            $where[] = "playerdetails.contactNumber LIKE ?";
            $values[] = '%' . $input['contactNumber'] . '%';
        }

        if (isset($input['phone_status'])) {
            $where[] = "player.verified_phone = ?";
            $values[] = $input['phone_status'];
        }

        if (isset($input['email_status'])) {
            $where[] = "player.verified_email = ?";
            $values[] = $input['email_status'];
        }

        if (isset($input['game_username'])) {
            $joins['game_provider_auth'] = 'game_provider_auth.player_id = player.playerId';
            $where[] = "game_provider_auth.login_name = ?";
            $values[] = $input['game_username'];
        }

        if (isset($input['player_bank_account_number'])) {
            $where[] = "player.playerId in (SELECT playerId FROM `playerbankdetails` WHERE `bankAccountNumber` = ?)";
            $values[] = $input['player_bank_account_number'];
        }

		if(isset($input['withdrawal_status'])){
			$where[] = "player.enabled_withdrawal = ?";
			$values[] = $input['withdrawal_status']=='1'?"1":"0";
		}

        if (isset($input['id_card_number'])) {
            $where[] = "playerdetails.id_card_number LIKE ?";
            $values[] = '%' . $input['id_card_number'] . '%';
        }

        if(!$this->utils->isEnabledFeature('show_game_history_of_deleted_player')){
            $where[] = "player.deleted_at IS NULL";
        }

        if (isset($input['deposit_approve_date_from'], $input['deposit_approve_date_to'])) {


			$input['deposit_approve_date_from'] = $this->_getDatetimeWithTimezone($input['timezone'], $input['deposit_approve_date_from']);
			$input['deposit_approve_date_to'] = $this->_getDatetimeWithTimezone($input['timezone'], $input['deposit_approve_date_to']);

            // $joins['sale_orders'] = 'sale_orders.player_id = player.playerId';
            // $where[] = "sale_orders.process_time >= ?";
            // $values[] = $input['deposit_approve_date_from'];
            // $where[] = "sale_orders.process_time <= ?";
            // $values[] = $input['deposit_approve_date_to'];
            $subSaleOrders = sprintf('(SELECT player_id FROM sale_orders WHERE sale_orders.status=5 and process_time >= \'%s\' AND process_time <= \'%s\' GROUP BY player_id) as sale_orders', $input['deposit_approve_date_from'], $input['deposit_approve_date_to']);
            $joins[$subSaleOrders] = 'sale_orders.player_id = player.playerId';
            $innerJoins = [$subSaleOrders];
        }
        // else{
        //     if($this->utils->getConfig('display_last_deposit_col') == true){
        //         $joins['sale_orders'] = 'sale_orders.player_id = player.playerId AND sale_orders.status=5';
        //     }
        // }

		if (isset($input['daysSinceLastDeposit'])) {
			$daysSinceLastDeposit = $input['daysSinceLastDeposit'];
			$daysSinceLastDeposit = (int) $daysSinceLastDeposit;

			if ($daysSinceLastDeposit > 0) {

				$dateFormat  = $this->utils->formatDateForMysql(new \DateTime("-$daysSinceLastDeposit days"));
				$this->utils->debug_log('dateFormat', $dateFormat, 'daysSinceLastDeposit', $daysSinceLastDeposit);
				$this->utils->debug_log('daysSinceLastDepositRange====', $input['daysSinceLastDepositRange']);

				switch($input['daysSinceLastDepositRange']){
					case "0": // >
						$where[] = "player_last_transactions.last_deposit_date < ? ";
						$values[] = $dateFormat . ' 00:00:00';
						break;
					case "1": // <
						$where[] = "player_last_transactions.last_deposit_date BETWEEN ? AND ? ";
						$values[] = $dateFormat . ' 23:59:59';
						$values[] = $this->utils->formatDateForMysql(new \DateTime()). ' 00:00:00';
						break;
					case "2": // =
						$where[] = "player_last_transactions.last_deposit_date BETWEEN ? AND ? ";
						$values[] = $dateFormat . ' 00:00:00';
						$values[] = $dateFormat . ' 23:59:59';
						break;
					default:
						break;
				}
			}
		}

        if ($this->utils->getConfig('enable_3rd_party_affiliate')) {
			if (isset($input['affiliate_network_source'])) {
	            // $where[] = "playerdetails.levelId = ?";
	            $where[] = "playerdetails.cpaId->'$.rec' = ?";
	            $values[] = $input['affiliate_network_source'];
	        }
        }

		if ($this->utils->getConfig('enabled_sales_agent')) {
			if (isset($input['player_sales_agent'])) {
				switch ($input['player_sales_agent']) {
				case 0:
					$where[] = "player_sales_agent.sales_agent_id IS NULL";
					break;
				case 1:
					$where[] = "player_sales_agent.sales_agent_id > 0";
					break;
				}
			}
		}

		if (isset($input['cashback'])) {
			$where[] = "player.disabled_cashback = ?";
			$values[] = $input['cashback'];
		}

		if (isset($input['promotion'])) {
			$where[] = "player.disabled_promotion = ?";
			$values[] = $input['promotion'];
		}
        // if($this->utils->getConfig('display_last_deposit_col') == true){
        //     $group_by[] = 'player.playerId';
        // }

        # END PROCESS SEARCH FORM #################################################################################################################################################
        if($is_export){
            $this->data_tables->options['is_export']=true;
            if(empty($csv_filename)){
                $csv_filename=$this->utils->create_csv_filename(__FUNCTION__);
            }
            $this->data_tables->options['csv_filename']=$csv_filename;
        }


        $having=[];
        $distinct=true;
		// if no other join, set to false
		if(!array_key_exists('game_provider_auth', $joins)
			|| !array_key_exists('sale_orders', $joins)
			|| !array_key_exists('playeraccount playertype', $joins)
			|| !array_key_exists('playertag', $joins)
			|| !array_key_exists('playerpromo', $joins)
			|| !array_key_exists('playeraccount', $joins)
			|| !array_key_exists('affiliate_traffic_stats', $joins)
            || !empty($subSaleOrders)
			){
			$distinct=false;
		}
        $external_order=[];
        $not_datatable='';
        $countOnlyField='player.playerId';
        $this->benchmark->mark('data_gathering_end');

        $this->benchmark->mark('playerList_result_start');

        $columns = $this->checkIfEnabled($this->utils->isEnabledFeature('show_risk_score'), array('risk_level'), $columns);
        $columns = $this->checkIfEnabled($this->utils->isEnabledFeature('show_kyc_status'), array('kyc_level'), $columns);
        $columns = $this->checkIfEnabled(!$this->utils->isEnabledFeature('close_aff_and_agent'), array('affiliate','agent'), $columns);

        $this->utils->debug_log('playerlist column ======>', $columns);

        // $this->config->set_item('debug_data_table_sql', true);
        $result = $this->data_tables->get_data( $request // #1
                                                , $columns // #2
                                                , $table // #3
                                                , $where // #4
                                                , $values // #5
                                                , $joins // #6
                                                , $group_by // #7
                                                , $having // #8
                                                , $distinct // #9
                                                , $external_order // #10
                                                , $not_datatable // #11
                                                , $countOnlyField // #12
                                                , $innerJoins // #13
                                            );
        $sqls = $this->data_tables->last_query;

        $this->benchmark->mark('playerList_result_end');
        $this->utils->debug_log('result done');

        if($is_export){
            //drop result if export
            return $csv_filename;
        }

        //dont run summary calculations on export
        $this->benchmark->mark('playerList_summary_start');

        $select_all_result_size_limit = (int) $this->utils->getConfig('player_list_select_all_result_size_limit');

        $entries_per_page = (int) $request['length'];

        $this->utils->debug_log(__METHOD__, 'search_all', [ 'limit' => $select_all_result_size_limit, 'recordsTotal' => $result['recordsTotal'], 'entries_per_page', $entries_per_page ]);

        $result['select_all_result_size_limit'] = $select_all_result_size_limit;
        if ($select_all_result_size_limit <= 0 || $result['recordsTotal'] < $select_all_result_size_limit) {
	        $all_usernames = $this->data_tables->summary($request, $table, $joins, 'player.playerId as id,player.username', null, $columns, $where, $values);
	    }
	    else {
	    	$all_usernames = [];
	    }
        $result['all_usernames'] = $all_usernames;

        $summary = $this->data_tables->summary($request, $table, $joins, 'SUM(player.total_total_nofrozen) total_balance, SUM(player.totalBettingAmount) total_bets_amount, SUM(player.approvedWithdrawAmount) total_withdraw, SUM(player.totalDepositAmount) total_deposit', null, $columns, $where, $values);
        $result['summary'] = $summary;
        $this->benchmark->mark('playerList_summary_end');

        $result['benchmarks']['data_gathering'] = $this->utils->getMarkProfiler('data_gathering');
        $result['benchmarks']['playerList_result'] = $this->utils->getMarkProfiler('playerList_result');
        $result['benchmarks']['playerList_summary'] = $this->utils->getMarkProfiler('playerList_summary');
        $result['sqls'] = $sqls;
        $result['dbg_request'] = $where;
        $result['dbg_input'] =$input;// ['registered_by'];
        $result['dbg_request'] = $request;


        $this->utils->debug_log(array(
            'playerList data gathering================================' => $this->benchmark->elapsed_time('data_gathering_start', 'data_gathering_end'),
            'playerList result=============================' => $this->benchmark->elapsed_time('playerList_result_start', 'playerList_result_end'),
            'playerList Summary============================' => $this->benchmark->elapsed_time('playerList_summary_start', 'playerList_summary_end'),
        ));
		$this->utils->debug_log('playerList total==============================', $result['summary']);
        return $result;
    }// EOF player_list_reports

	/**
	 * detail: get agency player reports
	 *
	 * @param array $request
	 * @param Boolean $viewPlayerInfoPerm
	 * @param Boolean $is_export
	 *
	 * @return array
	 */
	public function get_agency_player_reports($request, $viewPlayerInfoPerm, $is_export = false) {
		$readOnlyDB = $this->getReadOnlyDB();

		$this->load->library('data_tables', array("DB" => $readOnlyDB));
		$this->data_tables->is_export = $is_export;

		$this->load->model(array('transactions', 'game_logs', 'total_player_game_minute'));
		$i = 0;
		$input = $this->data_tables->extra_search($request);
		$table = 'player';
		$joins = array();
		$where = array();
		$values = array();
		$group_by = array();
		$having = array();

		$joins['playerdetails'] = 'playerdetails.playerId = player.playerId';
		$joins['player_runtime'] = 'player_runtime.playerId = player.playerId';

		# DEFINE TABLE COLUMNS ########################################################################################################################################################################
		$columns = array(
			array(
				'alias' => 'playerId',
				'select' => 'player.playerId',
			),
			array(
				'alias' => 'group_by',
				'select' => isset($input['group_by']) ? '\'' . $input['group_by'] . '\'' : 'NULL',
			),
			array(
				'dt' => $i++,
				'alias' => 'username',
            	'select' => isset($input['group_by']) ? $input['group_by'] == 'player.playerId' ? 'player.username' : "''" : 'NULL',
            	'formatter' => 'defaultFormatter',
				'name' => lang('report.pr01'),
			),
			array(
				'alias' => 'levelName',
				'select' => 'player.levelName',
			),
			array(
				'dt' => $i++,
				'alias' => 'member_level',
				'select' => 'player.groupName',
				'formatter' => function ($d, $row) use ($is_export) {
					return lang($d)." - ".lang($row['levelName']);
				},
				'name' => lang('report.pr03'),
			),
			array(
				'dt' => $i++,
				'alias' => 'total_deposit',
				'select' => 'player.totalDepositAmount',
				'formatter' => function($d, $row) {
					return $this->data_tables->currencyFormatter($d);
				},
				'name' => lang('report.pr21'),
			),
			array(
				'dt' => $i++,
				'alias' => 'total_withdrawal',
				'select' => 'player.approvedWithdrawAmount',
				'formatter' => function($d, $row){
					return $this->data_tables->currencyFormatter($d);
				},
				'name' => lang('report.pr22'),
			),
			array(
				'dt' => $i++,
				'alias' => 'total_bets',
				'select' => $this->utils->getConfig('use_total_minute') ? 'SUM(total_player_game_minute.betting_amount)' : 'SUM(game_logs.bet_amount)',
				'formatter' => function($d, $row)  {
					return $this->utils->formatCurrencyNoSym($d);
				},
				'name' => lang('Total Bets'),
			),
			array(
				'dt' => $i++,
				'alias' => 'payout',
				'select' => $this->utils->getConfig('use_total_minute') ? 'SUM(total_player_game_minute.betting_amount) - (SUM(total_player_game_minute.loss_amount) - SUM(total_player_game_minute.win_amount))' : 'SUM(game_logs.bet_amount) - (SUM(game_logs.loss_amount) - SUM(game_logs.win_amount))',
				'formatter' => function($d, $row)  {
					return $this->utils->formatCurrencyNoSym($d);
				},
				'name' => lang('Payout'),
			),
			array(
				'dt' => $i++,
				'alias' => 'total_wins',
				'select' => $this->utils->getConfig('use_total_minute') ? 'SUM(total_player_game_minute.win_amount)' : 'SUM(game_logs.win_amount)',
				'formatter' => function($d, $row) {
					return $this->utils->formatCurrencyNoSym($d);
				},
				'name' => lang('Total Wins'),
			),
			array(
				'dt' => $i++,
				'alias' => 'total_loss',
				'select' => $this->utils->getConfig('use_total_minute') ? 'SUM(total_player_game_minute.loss_amount)' : 'SUM(game_logs.loss_amount)',
				'formatter' => function($d, $row) {
					return $this->utils->formatCurrencyNoSym($d);
				},
				'name' => lang('Total Loss'),
			),
			array(
				'dt' => $i++,
				'alias' => 'net_gaming',
				'select' => $this->utils->getConfig('use_total_minute') ? 'SUM(total_player_game_minute.loss_amount) - SUM(total_player_game_minute.win_amount)' : 'SUM(game_logs.loss_amount) - SUM(game_logs.win_amount)',
				'formatter' => function($d, $row) {
					return $d < 0 ? '<span class="text-danger">' . $this->utils->formatCurrencyNoSym($d) . '</span>' : $this->utils->formatCurrencyNoSym($d);
				},
				'name' => lang('Net Gaming'),
			),
			array(
				'dt' => $i++,
				'alias' => 'createdOn',
				'select' => 'player.createdOn',
				'formatter' => 'dateTimeFormatter',
				'name' => lang('report.pr10'),
			),
		);

		# FILTER ######################################################################################################################################################################################

		$dateTimeFrom = null;
		$dateTimeTo = null;

		if (isset($input['search_on_date']) && $input['search_on_date']) {
			if (isset($input['date_from'], $input['date_to'])) {
				$dateTimeFrom = $input['date_from'];
				$dateTimeTo = $input['date_to'];

				$where[] = "player.createdOn BETWEEN ? AND ?";
				$values[] = $dateTimeFrom;
				$values[] = $dateTimeTo;
			}
		}

		if($this->utils->getConfig('use_total_minute')){
			$total_player_game_minute_join = 'total_player_game_minute.player_id = player.playerId ';

			$joins['total_player_game_minute'] = $total_player_game_minute_join;
		}
		else{

			$game_logs_join = 'game_logs.flag = '.Game_logs::FLAG_GAME.' AND game_logs.player_id = player.playerId ';

			$joins['game_logs'] = $game_logs_join;
		}

		if (isset($input['group_by'])) {
			$group_by[] = $input['group_by'];
		}

		if (isset($input['username'])) {
			$where[] = "player.username LIKE ?";
			$values[] = '%' . $input['username'] . '%';
		}

		if (isset($input['playerlevel'])) {
			$where[] = "player.levelId = ?";
			$values[] = $input['playerlevel'];
		}

		if (isset($input['depamt1'])) {
			$having['total_deposit <='] = $input['depamt1'];
		}

		if (isset($input['depamt2'])) {
			$having['total_deposit >='] = $input['depamt2'];
		}

		if (isset($input['widamt1'])) {
			$having['total_withdrawal <='] = $input['widamt1'];
		}

		if (isset($input['widamt2'])) {
			$having['total_withdrawal >='] = $input['widamt2'];
		}
		if (isset($input['only_under_agency']) && $input['only_under_agency'] != '') {
			$where[] = "player.agent_id IS NOT NULL";
			if (!isset($input['agent_name'])) {
				if (isset($input['current_agent_name']) && $input['current_agent_name'] != '') {
					$input['agent_name'] = $input['current_agent_name'];
				}
			}
		}
		if (isset($input['agent_name'])) {
			$this->load->model(array('agency_model'));
			$agent_detail = $this->agency_model->get_agent_by_name($input['agent_name']);
			$this->utils->debug_log('agent_detail', $agent_detail);

			if (isset($input['include_all_downlines']) && $input['include_all_downlines'] == true) {
				$joins['agency_agents'] = 'player.agent_id = agency_agents.agent_id';
				$parent_ids = array($agent_detail['agent_id']);
				$sub_ids = array();
				$all_ids = $parent_ids;
				while (!empty($sub_ids = $this->agency_model->get_sub_agent_ids_by_parent_id($parent_ids))) {
					$this->utils->debug_log('sub_ids', $sub_ids);
					$all_ids = array_merge($all_ids, $sub_ids);
					$parent_ids = $sub_ids;
					$sub_ids = array();
				}
				foreach ($all_ids as $i => $id) {
					if ($i == 0) {
						$w = "(player.agent_id = ?";
					} else {
						$w .= " OR player.agent_id = ?";
					}
					$values[] = $id;
				}
				$w .= ")";
				$where[] = $w;
			} else {
				$where[] = "player.agent_id = ?";
				$values[] = $agent_detail['agent_id'];
			}
		}
		$this->utils->debug_log('GET_AGENCY_PLAYER_REPORTSS where values', $where, $values);
		# OUTPUT ######################################################################################################################################################################################
		$result = $this->data_tables->get_data($request, $columns, $table, $where, $values, $joins, $group_by, $having);
		if($this->utils->getConfig('use_total_minute')) {

			$summary = $this->data_tables->summary(
				$request,
				$table,
				$joins,
				'SUM(total_player_game_minute.betting_amount) total_bets, '.
				'SUM(total_player_game_minute.betting_amount) - (SUM(total_player_game_minute.loss_amount) - SUM(total_player_game_minute.win_amount)) payout,'.
				'SUM(total_player_game_minute.win_amount) total_wins,'.
				'SUM(total_player_game_minute.loss_amount) total_loss,'.
				'SUM(total_player_game_minute.loss_amount) - SUM(total_player_game_minute.win_amount) net_gaming',
				null,
				$columns,
				$where,
				$values
			);
			unset($joins['total_player_game_minute']);
		} else {

			$summary = $this->data_tables->summary(
				$request,
				$table,
				$joins,
				'SUM(game_logs.bet_amount) total_bets,'.
				'SUM(game_logs.bet_amount) - (SUM(game_logs.loss_amount) - SUM(game_logs.win_amount)) payout,'.
				'SUM(game_logs.win_amount) total_wins, SUM(game_logs.loss_amount) total_loss,'.
				'SUM(game_logs.loss_amount) - SUM(game_logs.win_amount) net_gaming',
				null,
				$columns,
				$where,
				$values
			);
			unset($joins['game_logs']);
		}
		$summary_player = $this->data_tables->summary(
			$request,
			$table,
			$joins,
			'SUM(player.totalDepositAmount) total_deposit,'.
			'SUM(player.approvedWithdrawAmount) total_withdrawal',
			null,
			$columns,
			$where,
			$values
		);

		$result['totals'] = array_merge($summary_player[0],$summary[0]);

		return $result;
	}

	public function get_agency_player_reports_hourly($request, $viewPlayerInfoPerm, $is_export = false) {

        $readOnlyDB = $this->getReadOnlyDB();

        $this->load->library('data_tables', array('DB' => $readOnlyDB));

        $this->data_tables->is_export = $is_export;

        $i          = 0;
        $input      = $this->data_tables->extra_search($request);
        $joins      = array();
        $where      = array();
        $values     = array();
        $group_by   = array();
        $having     = array();
		$start      = $request['start'] + 1;

        $only_show_non_zero_player = isset($input['only_show_non_zero_player']) ? $input['only_show_non_zero_player'] == 'true' : false;

        # DEFINE TABLE COLUMNS ########################################################################################################################################################################
        $columns = array();

        $columns[] = array(
            'alias' => 'group_by',
            'select' => $input['group_by'],
        );

        $columns[] = array(
            'dt' => $i++,
            'alias' => 'username',
            'select' => $input['group_by'] == 'player.playerId' ? 'player.username' : "''",
            'formatter' => 'defaultFormatter',
            'name' => lang('report.pr01'),
        );

        $columns[] = array(
			'alias' => 'levelName',
			'select' => 'player.levelName',
        );

        $columns[] = array(
            'dt' => $i++,
            'alias' => 'member_level',
			'select' => 'player.groupName',
            'formatter' => function ($d, $row) {
            	return lang($d)." - ".lang($row['levelName']);
            },
            'name' => lang('report.pr03'),
        );

        $columns[] = array(
            'dt' => $i++,
            'alias' => 'total_deposit',
            'select' => 'SUM(player_report_hourly.total_deposit)',
			'formatter' => function($d, $row){
				return $this->data_tables->currencyFormatter($d);
			},
            'name' => lang('report.pr21'),
        );

        $columns[] = array(
            'dt' => $i++,
            'alias' => 'total_withdrawal',
            'select' => 'SUM(player_report_hourly.total_withdrawal)',
			'formatter' => function($d, $row){
				return $this->data_tables->currencyFormatter($d);
			},
            'name' => lang('report.pr22'),
        );

        $columns[] = array(
            'dt' => $i++,
            'alias' => 'total_bets',
            'select' => 'SUM(player_report_hourly.total_bet)',
			'formatter' => function($d, $row) {
				return $this->data_tables->currencyFormatter($d);
			},
            'name' => lang('Total Bets'),
        );

        $columns[] = array(
            'dt' => $i++,
            'alias' => 'total_payout',
            'select' => 'SUM(player_report_hourly.total_bet + player_report_hourly.total_result)',
			'formatter' => function($d, $row) {
				return $this->data_tables->currencyFormatter($d);
			},
            'name' => lang('Payout'),
        );

        $columns[] = array(
            'dt' => $i++,
            'alias' => 'total_wins',
            'select' => 'SUM(player_report_hourly.total_win)',
			'formatter' => function($d, $row){
				return $this->data_tables->currencyFormatter($d);
			},
            'name' => lang('Total Wins'),
        );

        $columns[] = array(
            'dt' => $i++,
            'alias' => 'total_loss',
            'select' => 'SUM(player_report_hourly.total_loss)',
            'formatter' => function($d, $row){
				return $this->data_tables->currencyFormatter($d);
			},
            'name' => lang('Total Loss'),
        );

        $columns[] = array(
            'dt' => $i++,
            'alias' => 'net_gaming',
            'select' => 'SUM(-player_report_hourly.total_result)',
            'formatter' => function($d, $row){
				return $this->data_tables->currencyFormatter($d);
			},
            'name' => lang('Net Gaming'),
        );

        $columns[] = array(
            'dt' => $i++,
            'alias' => 'createdOn',
            'select' => 'player.createdOn',
            'formatter' => 'dateTimeFormatter',
            'name' => lang('report.pr10'),
        );

        # FILTER ######################################################################################################################################################################################
        $dateTimeFrom = null; $dateTimeTo = null;

        if ($only_show_non_zero_player) {

            $table = 'player_report_hourly';

            $joins['player'] = 'player.playerId = player_report_hourly.player_id';
            $joins['agency_agents'] = 'agency_agents.agent_id = player.agent_id';

            if (isset($input['search_on_date']) && $input['search_on_date']) {
                if (isset($input['date_from'], $input['date_to'])) {
                    $dateTimeFrom = $input['date_from'];
                    $dateTimeTo   = $input['date_to'];

                    $where[]  = "player_report_hourly.date_hour >= ? AND player_report_hourly.date_hour <= ?";
                    $values[] = $this->utils->formatDateHourForMysql(new DateTime($dateTimeFrom));
                    $values[] = $this->utils->formatDateHourForMysql(new DateTime($dateTimeTo));
                }
            }

        } else {

            $table = 'player';

            $joins['agency_agents'] = 'agency_agents.agent_id = player.agent_id';

            $transactions_sql = 'player_report_hourly.player_id = player.playerId ';

            if (isset($input['search_on_date']) && $input['search_on_date']) {
                if (isset($input['date_from'], $input['date_to'])) {
                    $dateTimeFrom = $input['date_from'];
                    $dateTimeTo   = $input['date_to'];

                    $transactions_sql .= " AND player_report_hourly.date_hour >= '" . $this->utils->formatDateHourForMysql(new DateTime($dateTimeFrom)) . "'";
                    $transactions_sql .= " AND player_report_hourly.date_hour <= '" . $this->utils->formatDateHourForMysql(new DateTime($dateTimeTo)) . "'";
                }
            }

            $joins['player_report_hourly'] = $transactions_sql;

        }

        if (isset($input['username'])) {
            $where[] = "player.username LIKE ?";
            $values[] = '%' . $input['username'] . '%';
        }

        if (isset($input['playerlevel'])) {
            $where[] = "player.levelId = ?";
            $values[] = $input['playerlevel'];
        }

        if (isset($input['group_by'])) {
            $group_by[] = $input['group_by'];
        }

        if (isset($input['depamt1'])) {
            $having['total_deposit <='] = $input['depamt1'];
        }

        if (isset($input['depamt2'])) {
            $having['total_deposit >='] = $input['depamt2'];
        }

        if (isset($input['widamt1'])) {
            $having['total_withdrawal <='] = $input['widamt1'];
        }

        if (isset($input['widamt2'])) {
            $having['total_withdrawal >='] = $input['widamt2'];
        }

        if (isset($input['only_under_agency']) && $input['only_under_agency'] != '') {
            if ( ! isset($input['agent_name'])) {
                if (isset($input['current_agent_name']) && $input['current_agent_name'] != '') {
                    $input['agent_name'] = $input['current_agent_name'];
                }
            }
        }

        if (isset($input['agent_name'])) {

            $this->load->model(array('agency_model'));

            $agent_detail = $this->agency_model->get_agent_by_name($input['agent_name']);

            if (isset($input['include_all_downlines']) && $input['include_all_downlines'] == true) {

                $parent_ids = array($agent_detail['agent_id']);

                $sub_ids = array();
                $all_ids = $parent_ids;

                while ( ! empty($sub_ids = $this->agency_model->get_sub_agent_ids_by_parent_id($parent_ids))) {
                    $all_ids = array_merge($all_ids, $sub_ids);
                    $parent_ids = $sub_ids;
                    $sub_ids = array();
                }

                foreach ($all_ids as $i => $id) {
                    if ($i == 0) {
                        $w = "(player.agent_id = ?";
                    } else {
                        $w .= " OR player.agent_id = ?";
                    }
                    $values[] = $id;
                }

                $w .= ")";

                $where[] = $w;

            } else {
                $where[] = "player.agent_id = ?";
                $values[] = $agent_detail['agent_id'];
            }

        }
        # OUTPUT ######################################################################################################################################################################################
		$result = $this->data_tables->get_data($request, $columns, $table, $where, $values, $joins, $group_by, $having);

		$summary = $this->data_tables->summary(
			$request,
			$table,
			$joins,
			'SUM(player_report_hourly.total_deposit) total_deposit,'.
			'SUM(player_report_hourly.total_withdrawal) total_withdrawal,'.
			'SUM(player_report_hourly.total_bet) total_bets, '.
			'SUM(player_report_hourly.total_bet + player_report_hourly.total_result) payout,'.
			'SUM(player_report_hourly.total_win) total_wins,'.
			'SUM(player_report_hourly.total_loss) total_loss,'.
			'SUM(-player_report_hourly.total_result) net_gaming',
			null,
			$columns,
			$where,
			$values
		);

		$result['totals'] = $summary[0];
        return $result;
    }

	/**
	 * detail: get agency agent reports
	 *
	 * @param array $request
	 * @param Boolean $viewagentInfoPerm
	 * @param Boolean $is_export
	 *
	 * @return array
	 */
	public function get_agency_agent_reports($request, $viewagentInfoPerm, $is_export = false) {

		$readOnlyDB = $this->getReadOnlyDB();

		$this->load->library('data_tables', array("DB" => $readOnlyDB));
		$this->data_tables->is_export = $is_export;

		$this->load->model(array('transactions', 'game_logs', 'agency_model','agency_agent_report'));
		$i = 0;
		$input = $this->data_tables->extra_search($request);
		$table = 'agency_agents';
		$joins = array();
		$where = array();
		$values = array();
		$group_by = array('agency_agents.agent_id');
		$having = array();

		$joins['player'] = 'player.agent_id = agency_agents.agent_id';

		# DEFINE TABLE COLUMNS ######################################################################################################################################
		$columns = array(
			array(
				'dt' => (!$is_export) ? $i++ : NULL,
				'alias' => 'agent_name',
				'select' => 'agency_agents.agent_name',
				'formatter' => function ($d, $row)  {
					return '<ul class="list-inline">' .
					'<li><a href="#" onclick="credit_transactions(\'' . $d . '\')" title="' . lang('Credit Transaction') . '"><i class="fa fa-money"></i></a></li>' .
					'<li><a href="#" onclick="agency_player_report(\'' . $d . '\')" title="' . lang('Player List') . '"><i class="fa fa-users"></i></a></li>' .
						'</ul>';
				},
				'name' => lang('Actions'),
			),
			array(
				'dt' => $i++,
				'alias' => 'agent_name',
				'select' => 'agency_agents.agent_name',
				'formatter' => function ($d, $row) {
					return '<a href="#" onclick="agency_agent_report(' . $row['agent_id'] . ')">' . $d . '</a>';
				},
				'name' => lang('Agent Username'),
			),
			array(
				'dt' => $i++,
				'alias' => 'available_credit',
				'select' => 'agency_agents.available_credit',
				'formatter' => function ($d) {return $d;},
				'name' => lang('Available Credit'),
			),
			array(
				'dt' => $i++,
				'alias' => 'agent_id',
				'select' => 'agency_agents.agent_id',
				'formatter' => function ($d) {return $d;},
				'name' => lang('Total Deposit'),
			),
			array(
				'dt' => $i++,
				'alias' => 'agent_id',
				'select' => 'agency_agents.agent_id',
				'formatter' => function ($d) {return $d;},
				'name' => lang('Total Withdrawal'),
			),
			array(
				'dt' => $i++,
				'alias' => 'agent_id',
				'select' => 'agency_agents.agent_id',
				'formatter' => function ($d) {return $d;},
				'name' => lang('Total Bet'),
			),
			array(
				'dt' => $i++,
				'alias' => 'agent_id',
				'select' => 'agency_agents.agent_id',
				'formatter' => function ($d) {return $d;},
				'name' => lang('Total Win'),
			),
			array(
				'dt' => $i++,
				'alias' => 'agent_id',
				'select' => 'agency_agents.agent_id',
				'formatter' => function ($d) {return $d;},
				'name' => lang('Total Loss'),
			),
			array(
				'dt' => $i++,
				'alias' => 'agent_id',
				'select' => 'agency_agents.agent_id',
				'formatter' => function ($d) {return $d;},
				'name' => lang('Net Gaming'),
			),
		);

		$columns_in_filter_empty_dt = array_filter( $columns, function( $aColumn ){
			return !is_null($aColumn['dt']);
		});

		$columns = array_values($columns_in_filter_empty_dt); // resort the key

		# FILTER ####################################################################################################################################################

		$dateTimeFrom = isset($input['date_from']) ? $input['date_from'] : NULL;
		$dateTimeTo = isset($input['date_to']) ? $input['date_to'] : NULL;

		if (isset($input['agent_id'])) {
			$where[] = 'agency_agents.parent_id = ?';
			$values[] = $input['agent_id'];
			$agent_username = $this->agency_model->get_agent_by_id($input['agent_id'])['agent_name'];
		}

		# OUTPUT ####################################################################################################################################################

		$result = $this->data_tables->get_data($request, $columns, $table, $where, $values, $joins, $group_by, $having);

		$agent_summary[0] = '';
		$agent_summary[1] = lang('Agent Summary');
		$agent_summary[2] = 0;
		$agent_summary[3] = 0;
		$agent_summary[4] = 0;
		$agent_summary[5] = 0;
		$agent_summary[6] = 0;
		$agent_summary[7] = 0;
		$agent_summary[8] = 0;

		$player_summary[0] = '';
		if ($agent_username) {
			$player_summary[0] .= '<ul class="list-inline">';
			$player_summary[0] .= '<li><a href="javascript:void()" onclick="credit_transactions(\'' . $agent_username . '\')"<i class="fa fa-money"></i></a></li>';
			$player_summary[0] .= '<li><a href="javascript:void()" onclick="agency_player_report(\'' . $agent_username . '\')"<i class="fa fa-users"></i></a></li>';
			$player_summary[0] .= '</ul>';
		}
		$player_summary[1] = lang('Player Summary');
		$player_summary[2] = 0;
		$player_summary[3] = 0;
		$player_summary[4] = 0;
		$player_summary[5] = 0;
		$player_summary[6] = 0;
		$player_summary[7] = 0;
		$player_summary[8] = 0;

		$total_summary[0] = '';
		$total_summary[1] = lang('Total Summary');
		$total_summary[2] = 0;
		$total_summary[3] = 0;
		$total_summary[4] = 0;
		$total_summary[5] = 0;
		$total_summary[6] = 0;
		$total_summary[7] = 0;
		$total_summary[8] = 0;

		# OGP-13822
		$use_agency_agent_reports = $this->utils->getConfig('use_agency_agent_reports');

		if (isset($result['data']) && !empty($result['data'])) {

			foreach ($result['data'] as &$data_row) {
				$row = $data_row;
				if($is_export){
					array_unshift($row, 0); // add the first for calcation by column while export.
				}


				$agentId = $row[3];

				if (!empty($agentId)) {

					$agentIdArr = $this->agency_model->get_all_downline_arr($agentId);
					$this->utils->debug_log('Agency Agents Report Agent IDS: ', $agentIdArr);

					if($use_agency_agent_reports){
						list($totalBet, $totalWin, $totalLoss, $totalDeposits, $totalWithdrawals) = $this->agency_agent_report->getAgentsSummaryReport($agentIdArr, $dateTimeFrom, $dateTimeTo);
					}else{
						list($totalBet, $totalWin, $totalLoss) = $this->game_logs->sumBetsWinsLossByDatetimeAgents($agentIdArr, $dateTimeFrom, $dateTimeTo);

						list($totalDeposits, $totalWithdrawals) = $this->sumDepositsWithdrawalsByDatetimeAgents($agentIdArr, $dateTimeFrom, $dateTimeTo);
					}

					$row[3] = $totalDeposits;
					$row[4] = $totalWithdrawals;
					$row[5] = $totalBet;
					$row[6] = $totalWin;
					$row[7] = $totalLoss;
					$row[8] = $totalLoss - $totalWin;

					$agent_summary[2] += $row[2];
					$agent_summary[3] += $row[3];
					$agent_summary[4] += $row[4];
					$agent_summary[5] += $row[5];
					$agent_summary[6] += $row[6];
					$agent_summary[7] += $row[7];
					$agent_summary[8] += $row[8];

				}
				if($is_export){
					$row = array_slice($row, 1); // remove the first for export.
				}
				$data_row = $row;
			}

		}

		if($use_agency_agent_reports){
			list($totalBet, $totalWin, $totalLoss, $totalDeposits, $totalWithdrawals) = $this->agency_agent_report->getAgentsSummaryReport(array($input['agent_id']), $dateTimeFrom, $dateTimeTo);
		}else{
			list($totalBet, $totalWin, $totalLoss) = $this->game_logs->sumBetsWinsLossByDatetimeAgents(array($input['agent_id']), $dateTimeFrom, $dateTimeTo);

			list($totalDeposits, $totalWithdrawals) = $this->sumDepositsWithdrawalsByDatetimeAgents(array($input['agent_id']), $dateTimeFrom, $dateTimeTo);
		}

		$player_summary[3] = $totalDeposits;
		$player_summary[4] = $totalWithdrawals;
		$player_summary[5] = $totalBet;
		$player_summary[6] = $totalWin;
		$player_summary[7] = $totalLoss;
		$player_summary[8] = $totalLoss - $totalWin;

		$total_summary[2] = $agent_summary[2] + $player_summary[2];
		$total_summary[3] = $agent_summary[3] + $player_summary[3];
		$total_summary[4] = $agent_summary[4] + $player_summary[4];
		$total_summary[5] = $agent_summary[5] + $player_summary[5];
		$total_summary[6] = $agent_summary[6] + $player_summary[6];
		$total_summary[7] = $agent_summary[7] + $player_summary[7];
		$total_summary[8] = $agent_summary[8] + $player_summary[8];

		foreach ($result['data'] as &$row) {
			$row[2] = $this->formatCurrency($row[2]);
			$row[3] = $this->formatCurrency($row[3]);
			$row[4] = $this->formatCurrency($row[4]);
			$row[5] = $this->formatCurrency($row[5]);
			$row[6] = $this->formatCurrency($row[6]);
			$row[7] = $this->formatCurrency($row[7]);
			if(isset($row[8])){
				$row[8] = $this->formatCurrency($row[8]);
			}
		}

		$agent_summary[2] = $this->formatCurrency($agent_summary[2]);
		$agent_summary[3] = $this->formatCurrency($agent_summary[3]);
		$agent_summary[4] = $this->formatCurrency($agent_summary[4]);
		$agent_summary[5] = $this->formatCurrency($agent_summary[5]);
		$agent_summary[6] = $this->formatCurrency($agent_summary[6]);
		$agent_summary[7] = $this->formatCurrency($agent_summary[7]);
		$agent_summary[8] = $this->formatCurrency($agent_summary[8]);

		$result['agent_summary'] = $agent_summary;

		$player_summary[2] = $this->formatCurrency($player_summary[2]);
		$player_summary[3] = $this->formatCurrency($player_summary[3]);
		$player_summary[4] = $this->formatCurrency($player_summary[4]);
		$player_summary[5] = $this->formatCurrency($player_summary[5]);
		$player_summary[6] = $this->formatCurrency($player_summary[6]);
		$player_summary[7] = $this->formatCurrency($player_summary[7]);
		$player_summary[8] = $this->formatCurrency($player_summary[8]);

		$result['player_summary'] = $player_summary;

		$total_summary[2] = $this->formatCurrency($total_summary[2]);
		$total_summary[3] = $this->formatCurrency($total_summary[3]);
		$total_summary[4] = $this->formatCurrency($total_summary[4]);
		$total_summary[5] = $this->formatCurrency($total_summary[5]);
		$total_summary[6] = $this->formatCurrency($total_summary[6]);
		$total_summary[7] = $this->formatCurrency($total_summary[7]);
		$total_summary[8] = $this->formatCurrency($total_summary[8]);

		$result['total_summary'] = $total_summary;

		return $result;
	}

	/**
	 * detail: format currency
	 *
	 * @param string $value
	 * @return string
	 */
	private function formatCurrency($value) {
		return $value > 0 ? $this->utils->formatCurrencyNoSym($value) : ($value < 0 ? '<span class="text-danger">' . $this->utils->formatCurrencyNoSym($value) . '</span>' : '<span class="text-muted">0.00</span>');
	}

	/**
	 * detail: get deposit withdrawal of a certain agents
	 *
	 * @param array $agentIdArr
	 * @param string $dateTimeFrom
	 * @param string $dateTimeTo
	 *
	 * @return array
	 */
	private function sumDepositsWithdrawalsByDatetimeAgents($agentIdArr, $dateTimeFrom = null, $dateTimeTo = null) {

		$db=$this->getReadOnlyDB();

		$db->select_sum(
			sprintf('(CASE WHEN transactions.transaction_type = %s THEN transactions.amount ELSE 0 END)', Transactions::DEPOSIT),
			'total_deposits'
		);

		$db->select_sum(
			sprintf('(CASE WHEN transactions.transaction_type = %s THEN transactions.amount ELSE 0 END)', Transactions::WITHDRAWAL),
			'total_withdrawals'
		);

		// $this->db->from('agency_agents');
		// $this->db->join('player', 'player.agent_id = agency_agents.agent_id', 'left');
		// $this->db->join('transactions', "transactions.to_id = player.playerId", 'left');
		$db->from('agency_agents');
		$db->join('player', 'player.agent_id = agency_agents.agent_id', 'left');
		$db->join('transactions', "transactions.to_id = player.playerId", 'left');
		$db->where_in('agency_agents.agent_id', $agentIdArr);
		$db->where('transactions.to_type', Transactions::PLAYER);
		$db->where('transactions.status', Transactions::APPROVED);

		if (isset($dateTimeFrom, $dateTimeTo)) {
			$db->where('transactions.created_at >=', $dateTimeFrom);
			$db->where('transactions.created_at <=', $dateTimeTo);
		}

		$db->limit(1);
		$query = $db->get();
		return array_values($query->row_array());
	}

	/**
	 * The List for HABA Api Results By playerpromo_id.
	 *
	 * @param array $request
	 * @param boolean $is_export So far, It's No Need to used.
	 * @return void
	 */
	public function reviewHabaApiResultsList($request) {
		$this->load->model(array('promorules', 'player_promo', 'insvr_log'));
		$this->load->library(array('data_tables'));
		$is_export = false; // So far, It's No Need to used.
		//	$request = $this->input->post();
		$input = $this->data_tables->extra_search($request);

		$columns = [];
		$dtIndex = -1; // for begin from Zero while begin to used.
		$i = -1; // for begin from Zero while begin to used.

		$i++;
		$columns[$i]['select'] = 'insvr_log.player_id';
		$columns[$i]['alias'] = 'insvr_log_player_id';

		$i++;
		$columns[$i]['select'] = 'insvr_log.playerpromo_id';
		$columns[$i]['alias'] = 'playerpromo_id';

		$i++;
		$columns[$i]['select'] = 'insvr_game_description_log.game_description_id';
		$columns[$i]['alias'] = 'game_description_id';

		$i++;
		$dtIndex++;
		$columns[$i]['dt'] = $dtIndex;
		$columns[$i]['select'] = 'insvr_log.id';
		$columns[$i]['alias'] = 'insvr_log_id';
		$columns[$i]['name'] = lang('ID');

		$i++;
		$dtIndex++;
		$columns[$i]['dt'] = $dtIndex;
		$columns[$i]['select'] = 'player.username';
		$columns[$i]['alias'] = 'username';
		$columns[$i]['name'] = lang('Player Username');
		// $columns[$i]['formatter'] = function ($d, $row) use ($is_export) {
		// 	return $d;
		// };


		$i++;
		$dtIndex++;
		$columns[$i]['dt'] = $dtIndex;
		$columns[$i]['select'] = 'game_description.game_name';
		$columns[$i]['alias'] = 'game_name';
		$columns[$i]['name'] = lang('Game Name');
		$columns[$i]['formatter'] = function ($d, $row) use ($is_export) {
			return $this->data_tables->languageFormatter($d);
		};

		$i++;
		$dtIndex++;
		$columns[$i]['dt'] = $dtIndex;
		$columns[$i]['select'] = 'insvr_log.response';
		$columns[$i]['alias'] = 'insvr_log_result';
		$columns[$i]['name'] = lang('Result');
		$columns[$i]['formatter'] = function ($d, $row) use ($is_export) {
			$returnData = lang('N/A');
			$jsonData = json_decode($d, true);

			if( is_string($jsonData) ){
				$returnData = lang('FALSE');
			}else if($jsonData['Created'] != true){
				// 3.a. The JMESPath, "@.Created" Not eq. true. The Message will be the JMESPath, "@.Message".
				$returnData = lang('FALSE');
			}else if( isset($jsonData['Players'][0]['FailMessage']) ){
				// 3.b. The JMESPath, "@.Players[0].FailMessage" is exists and not empty. The
				if( ! empty($jsonData['Players'][0]['FailMessage']) ){
					$returnData = lang('FALSE');
				}
			}else{
				$returnData = lang('TRUE');
			}

			return $returnData;
		};

		$i++;
		$dtIndex++;
		$columns[$i]['dt'] = $dtIndex;
		$columns[$i]['select'] = 'insvr_log.response';
		$columns[$i]['alias'] = 'insvr_log_message';
		$columns[$i]['name'] = lang('Message');
		$columns[$i]['formatter'] = function ($d, $row) use ($is_export) {

			$returnData = lang('N/A');

			$jsonData = json_decode($d, true);
			if( is_string($jsonData) ){
				$returnData = $jsonData;
			}else if( $jsonData['Created'] != true){
				$returnData = $jsonData['Message'];
			}else if( ! empty($jsonData['Players'][0]['FailMessage']) ){
				$returnData = $jsonData['Players'][0]['FailMessage'];
			}
			return $returnData;
		};

		$i++;
		$dtIndex++;
		$columns[$i]['dt'] = $dtIndex;
		$columns[$i]['select'] = 'insvr_log.request';
		$columns[$i]['alias'] = 'insvr_log_request';
		$columns[$i]['name'] = lang('Request');
		$columns[$i]['formatter'] = function ($d, $row) use ($is_export) {
			return $d;
		};

		$i++;
		$dtIndex++;
		$columns[$i]['dt'] = $dtIndex;
		$columns[$i]['select'] = 'insvr_log.response';
		$columns[$i]['alias'] = 'insvr_log_response';
		$columns[$i]['name'] = lang('Response');
		$columns[$i]['formatter'] = function ($d, $row) use ($is_export) {
			return $d;
		};

		$i++;
		$dtIndex++;
		$columns[$i]['dt'] = $dtIndex;
		$columns[$i]['select'] = 'insvr_log.created_at';
		$columns[$i]['alias'] = 'insvr_log_created_at';
		$columns[$i]['name'] = lang('Created At');
		// $columns[$i]['formatter'] = function ($d, $row) use ($is_export) {
		// };

		$i++;
		$dtIndex++;
		$columns[$i]['dt'] = $dtIndex;
		$columns[$i]['select'] = 'insvr_log.updated_at';
		$columns[$i]['alias'] = 'insvr_log_updated_at';
		$columns[$i]['name'] = lang('Updated At');
		// $columns[$i]['formatter'] = function ($d, $row) use ($is_export) {
		// 	return $d
		// };

		$where = array();
		$values = array();

		$table = 'insvr_log';
		$joins = array(
			'player' => 'player.playerId = insvr_log.player_id',
			'insvr_game_description_log' => 'insvr_game_description_log.insvr_log_id = insvr_log.id',
			'game_description' => 'game_description.id = insvr_game_description_log.game_description_id',
		);

		if( ! empty($input['playerpromo_id']) ){
			$where[] = "insvr_log.playerpromo_id = ?";
            $values[] = $input['playerpromo_id'];
		}

		$result = $this->data_tables->get_data($request, $columns, $table, $where, $values, $joins);
		// $result['dbg'] = $this->data_tables->last_query;

		return $result;
	} // EOF reviewHabaApiResultsList

	/**
	 * detail: get friend referal promo applications
	 * @param array $request
	 * @param Boolean $is_export
	 * @return array
	 */
	public function referralPromoApplicationList($request, $is_export = false) {

		$this->load->model(array('promorules', 'player_promo', 'player_friend_referral', 'player_model'));
		$this->load->library(array('data_tables'));
		$this->load->library(array('permissions'));
		$this->permissions->setPermissions();

        $this->load->library(['language_function']);
		$this->language_function->setCurrentLanguage(Language_function::INT_LANG_ENGLISH);
		$_currLang = $this->language_function->getCurrentLanguage();

		$promotion_rules = $this->utils->getConfig('promotion_rules');
		$enable_split_player_username_and_affiliate = $this->utils->getConfig('enable_split_player_username_and_affiliate');

		//default is true
		// $release_on_admin_approve = $promotion_rules['release_on_admin_approve'];
		$allow_decline_on_approved_and_without_release = $promotion_rules['allow_decline_on_approved_and_without_release'];
		$disable_pre_application_on_release_bonus_first = $promotion_rules['disable_pre_application_on_release_bonus_first'];

		$this->utils->debug_log('allow_decline_on_approved_and_without_release', $allow_decline_on_approved_and_without_release, 'disable_pre_application_on_release_bonus_first', $disable_pre_application_on_release_bonus_first);

		$i = 0;
		//	$request = $this->input->post();
		$input = $this->data_tables->extra_search($request);

        $controller = $this;

		$where = array();
		$values = array();

		// $allow_to_delete_declined_promotion = $this->permissions->checkPermissions('allow_to_delete_declined_promotion');
//		$declined_forever_promotion = $this->utils->isEnabledFeature('declined_forever_promotion');
//		$manaully_update_promotion_bonus = $this->permissions->checkPermissions('manaully_update_promotion_bonus');
//		$manually_decline_promo = $this->permissions->checkPermissions('promocancel_list');

		$columns = array(
			array(
				'select' => 'player.playerId',
				'alias' => 'playerId',
			),
			array(
				'select' => 'promocmssetting.promoCmsSettingId',
				'alias' => 'promoCmsSettingId',
			),
			array(
				'select' => 'promorules.promorulesId',
				'alias' => 'promorulesId',
			),
			array(
				'select' => 'promorules.disabled_pre_application',
				'alias' => 'disabled_pre_application',
			),
            array(
                'select' => '(CASE WHEN playerpromo.requestAdminId IS NOT NULL THEN adminUsersRequestId.username ELSE null END)',
                'alias' => 'requestAdmin',
            ),
            array(
                'select' => '(CASE WHEN playerpromo.requestPlayerId IS NOT NULL THEN player.username ELSE null END)',
                'alias' => 'requestPlayer',
            ),
			array(
                'select' => 'playerpromo.vip_level_info',
                'alias' => 'vip_level_info',
            ),
            array(
                'select' => 'playerpromo.group_name_on_created',
                'alias' => 'group_name_on_created',
            ),
            array(
                'select' => 'playerpromo.vip_level_on_created',
                'alias' => 'vip_level_on_created',
            ),
            array(
                'select' => 'playerpromo.referralId',
                'alias' => 'referralId',
            ),
            array(
                'select' => 'playerfriendreferral.playerId',
                'alias' => 'referrerPlayerId',
            ),
            array(
                'select' => 'playerfriendreferral.invitedPlayerId',
                'alias' => 'referredPlayerId',
            ),
			array(
				'dt' => (!$is_export) ? $i++ : NULL,
				'select' => 'player.playerId',
				'alias' => 'sales_promo_id',
				'formatter' => function ($d, $row) use ($is_export) {
					if (!$is_export) {
						if ($this->utils->isEnabledFeature('batch_decline_promo') || $this->utils->isEnabledFeature('batch_finish_promo') || $this->utils->isEnabledFeature('batch_release_promo')) {
							$action = '<div class="clearfix">';
							$action .= '	<div class="col-md-3" style="padding:5px 1px 0 2px"><input type="checkbox" name="sales_promo_id" class="chk-promo-id" value="'.$row['playerpromoId'].'"></div>';
							// OGP - 11385 Remove Details button and Action column
							// $action .= '	<div class="col-md-9" style="padding:0 2px 0 2px"><span class="btn btn-xs btn-info review-btn" data-toggle="modal" onclick="getDupLicateAccountList(' . $row["playerId"] . ')" data-target="#detailModal">' . lang("lang.details") . '</span></div>';
							$action .= "</div>";
							return $action;
						} else {
							// OGP - 11385 Remove Details button and Action column
							// return '<span class="btn btn-xs btn-info review-btn" data-toggle="modal" onclick="getDupLicateAccountList(' . $row["playerId"] . ')" data-target="#detailModal">' . lang("lang.details") . '</span>';
						}
					} else {
						return '';
					}
				},
			),
            array(
                'select' => 'playerpromo.playerpromoId',
                'alias' => 'playerpromoId',
                'name' => lang('column.id'),
                'formatter' => 'defaultFormatter'
            ),
			array(
				'dt' => $i++,
				'select' => 'player.username',
				'alias' => 'username',
				'name' => lang('player.01'),
				'formatter' => function ($d, $row) use ($is_export,$enable_split_player_username_and_affiliate) {
					if (!$is_export) {
						if (!$enable_split_player_username_and_affiliate) {
							if(!empty($row['affiliate'])){
								$d=$d.' ('.$row['affiliate'].')';
							}
						}
						return '<a href="/player_management/userInformation/' . $row['playerId'] . '" data-toggle="tooltip" data-original-title="' . lang("cms.checkPlayerDetails") . '" data-placement="right">' . $d . '</a>';
					} else {
						return $d;
					}
				},
			),
			array(
				'dt' => $enable_split_player_username_and_affiliate ? $i++ : null ,
                'select' => 'affiliates.username',
                'alias' => 'affiliate',
				'name' => lang('Affiliate'),
				'formatter' => function ($d, $row) use ($is_export) {
					if (!$is_export) {
                        return $d ? $d : '<i class="text-muted">' . lang('N/A') . '</i>';
					} else {
                        return ($d ? $d : lang('N/A'));
					}
				},
			),
			array(
				'dt' => $i++,
				'alias' => 'memberLevel',
				'select' => 'CONCAT(group_name_on_created, LPAD(vip_level_on_created, 4, 0) )',
				'name' => lang('player.07'),
				'formatter' => function ($d, $row) {
					$sprintf_format = '%s - %s'; // params: groupName, vipLevelName
					$groupName = lang('N/A'); // defaults
					$vipLevelName = lang('N/A'); // defaults
					$vip_level_info = json_decode($row['vip_level_info'], true);
					if( ! empty($vip_level_info['vipsetting']['groupName']) ){
						$groupName = lang($vip_level_info['vipsetting']['groupName']);
					}
					if( ! empty($vip_level_info['vipsettingcashbackrule']['vipLevelName']) ){
						$vipLevelName =  lang($vip_level_info['vipsettingcashbackrule']['vipLevelName']);
					}
					return sprintf($sprintf_format, $groupName, $vipLevelName);
				},
			),
			array(
				'dt' => $i++,
				'select' => 'promocmssetting.promoName',
				'alias' => 'promoTitle',
				'name' => lang('cms.promotitle'),
				'formatter' => function ($d, $row) use ($is_export) {

					if($d == Promorules::SYSTEM_MANUAL_PROMO_CMS_NAME){
						$promoName = lang('promo.'. $d);
					}else{
						$promoName = $d;
					}

					if (!$is_export) {
						if($d == Promorules::SYSTEM_MANUAL_PROMO_CMS_NAME){
							$html = $promoName;
						}else{
							$html = anchor_popup('/cms_management/viewPromoDetails/' . $row['promoCmsSettingId'], $promoName, array(
								'width' => '1030',
								'height' => '600',
								'scrollbars' => 'yes',
								'status' => 'yes',
								'resizable' => 'no',
								'screenx' => '0',
								'screeny' => '0'));
							$html = '<span class="check_cms_promo" data-toggle="tooltip" data-playerpromoid="'.$row['playerpromoId'].'" data-promocmssettingid="'.$row['promoCmsSettingId'].'" title="' . lang('cms.checkCmsPromo') . '" data-placement="right">'. $html. '</span>';
						}

						return $html;

					} else {
						return $promoName;
					}
				},
			),
            array(
                'dt' => $i++,
                'select' => 'playerpromo.playerId',
                'alias' => 'referralType',
                'name' => lang('Referral Type'),
                'formatter' => function ($d, $row) use ($controller, $is_export){
                    $referralId = $row['referralId'];
                    if(empty($referralId)){
                        if (!$is_export) {
                            return '<i class="text-muted">' . lang('N/A') . '</i>';
                        }else{
                            return lang('N/A');
                        }
                    }

                    $referral = $controller->player_friend_referral->getReferralByReferralId($referralId);
                    $referralType = null;
                    switch($d){
                        case $referral['playerId']:
                            $referralType = 'Referrer';
                            break;
                        case $referral['invitedPlayerId']:
                            $referralType = 'Referred';
                            break;
                    }

                    if (!$is_export) {
                        return !empty($referralType) ? lang($referralType) : '<i class="text-muted">' . lang('N/A') . '</i>';
                    } else {
                        return !empty($referralType) ? lang($referralType) : lang('N/A');
                    }
                },
            ),
			array(
                'dt' => $i++,
                'select' => 'playerpromo.playerId',
                'alias' => 'referrer',
                'name' => lang('Referrer'),
                'formatter' => function ($d, $row) use ($controller, $is_export) {
                    $referrer = null;
                    $referrerPlayerId = null;
                    $referrerUsername = null;
                    if ($d == $row['referredPlayerId']) {
                        $referrerPlayerId = $row['referrerPlayerId'];
                        $referrerUsername = $controller->player_model->getUsernameById($referrerPlayerId);
                    }

                    if(!empty($referrerPlayerId) && !empty($referrerUsername)){
                        $referrer = '<a href="/player_management/userInformation/' . $referrerPlayerId . '" data-toggle="tooltip" data-original-title="' . lang("cms.checkPlayerDetails") . '" data-placement="right">' . $referrerUsername . '</a>';
                    }

                    if (!$is_export) {
                        return !empty($referrer) ? $referrer : '<i class="text-muted">' . lang('N/A') . '</i>';
                    } else {
                        return !empty($referrer) ? $referrer : lang('N/A');
                    }
                },
            ),
			array(
                'dt' => $i++,
                'select' => 'playerpromo.playerId',
                'alias' => 'referred',
                'name' => lang('Referred'),
                'formatter' => function ($d, $row) use ($controller, $is_export) {
                    $referred = null;
                    $referredPlayerId = null;
                    $referredUsername = null;
                    if ($d == $row['referrerPlayerId']) {
                        $referredPlayerId = $row['referredPlayerId'];
                        $referredUsername = $controller->player_model->getUsernameById($referredPlayerId);
                    }

                    if(!empty($referredPlayerId) && !empty($referredUsername)){
                        $referred = '<a href="/player_management/userInformation/' . $referredPlayerId . '" data-toggle="tooltip" data-original-title="' . lang("cms.checkPlayerDetails") . '" data-placement="right">' . $referredUsername . '</a>';
                    }

                    if (!$is_export) {
                        return !empty($referred) ? $referred : '<i class="text-muted">' . lang('N/A') . '</i>';
                    } else {
                        return !empty($referred) ? $referred : lang('N/A');
                    }
                },
            ),
			array(
				'dt' => $i++,
				'select' => 'playerpromo.depositAmount',
				'alias' => 'depositAmount',
				'name' => lang('Deposit'),
				// 'formatter' => function ($d, $row) {
				// 	return $d ?: lang('N/A');
				// },
				'formatter' => function ($d) use ($is_export) {
					if (!$is_export) {
						return '<strong>' . $d . '</strong>';
					} else {
						return $d;
					}
				},
			),
			array(
				'dt' => $i++,
				'select' => 'playerpromo.bonusAmount',
				'alias' => 'bonusAmount',
				'name' => lang('cms.bonusAmount'),
				// 'formatter' => function ($d, $row) {
				// 	// if ($row['transactionStatus'] == Player_promo::TRANS_STATUS_MANUAL_REQUEST_PENDING) {
				// 	// 	return lang('Will generate result once approved');
				// 	// } else {
				// 	return $d = $d ?: lang('N/A');
				// 	// }
				// },
				'formatter' => function ($d) use ($is_export) {
					if (!$is_export) {
						return '<strong>' . $d . '</strong>';
					} else {
						return $d;
					}
				},
			),
			array(
				'dt' => $i++,
				'select' => 'playerpromo.withdrawConditionAmount',
				'alias' => 'withdrawConditionAmount',
				'name' => lang('Withdraw Condition'),
				// 'formatter' => function ($d, $row) {
				// 	// if ($row['transactionStatus'] == Player_promo::TRANS_STATUS_MANUAL_REQUEST_PENDING) {
				// 	// 	return lang('Will generate result once approved');
				// 	// } else {
				// 	return $d = $d ?: lang('N/A');
				// 	// }
				// },

				'formatter' => function ($d) use ($is_export) {
					if (!$is_export) {
						return '<strong>' . $d . '</strong>';
					} else {
						return $d;
					}
				},
			),
			array(
				'dt' => $i++,
				'select' => 'playerpromo.dateApply',
				'alias' => 'dateApply',
				'name' => lang('cms.dateApplyRequest'),
				'formatter' => 'dateTimeFormatter',
			),
            array(
                'dt' => $i++,
                'alias' => 'requestBy',
                'select' => 'CONCAT('. // To concat the related id for sort by dataTable()
                    // convert to string type
                    '(CASE WHEN playerpromo.requestAdminId IS NOT NULL THEN adminUsersRequestId.username ELSE "" END)'.
                    ','.
                    '(CASE WHEN playerpromo.requestPlayerId IS NOT NULL THEN player.username ELSE "" END)'.
                ')',
                'name' => lang('cms.requestBy'),
                'formatter' => function ($d, $row) {
                    if(isset($row['requestAdmin'])){
                        $d = $row['requestAdmin'];
                    }else if(isset($row['requestPlayer'])){
                        $d = $row['requestPlayer'];
                    }else{
                        $d = NULL;
                    }

                    return $d;
                },
            ),
            array(
                'dt' => $i++,
                'select' => 'playerpromo.order_generated_by',
                'alias' => 'order_generated_by',
                'name' => lang('promo.request_list.order_generated_by'),
                'formatter' => function ($d, $row) use ($controller) {
                    return $controller->player_promo->orderGeneratedByToName($d);
                },
            ),
            array(
                'dt' => $i++,
                'select' => 'playerpromo.player_request_ip',
                'alias' => 'player_request_ip',
                'name' => lang('promo.request_list.player_request_ip'),
                'formatter' => function ($d) {
                    return $d ? : lang('N/A');
                },
            ),
			array(
				'dt' => $i++,
				'select' => 'playerpromo.dateProcessed',
				'alias' => 'dateProcessed',
				'name' => lang('cms.dateProcessed'),
				'formatter' => function ($d, $row) {
					return $d ?: lang('N/A');
				},
			),
			array(
				'dt' => $i++,
				'alias' => 'processed_by_name',
				'select' => 'adminusers.username',
				'name' => lang('pay.procssby'),
				'formatter' => function ($d) use ($is_export) {
					if (!$is_export) {
						return $d ?: '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
					} else {
						return $d ?: lang('lang.norecyet');
					}
				},
			),
//			array(
//				'dt' => $i++,
//				'select' => 'playerpromo.login_ip',
//				'alias' => 'loginIp',
//				'name' => lang('sys.vu44'),
//				'formatter' => function ($d, $row) {
//					return $d ? : lang('N/A');
//				},
//			),
			array(
				'dt' => $i++,
				'select' => 'playerpromo.transactionStatus',
				'alias' => 'status',
				'name' => lang('Status'),
				'formatter' => function ($d, $row) use ($controller) {

					return $controller->player_promo->statusToName($d);

					// if ($row['transactionStatus'] == Player_promo::TRANS_STATUS_MANUAL_REQUEST_PENDING) {
					// return lang('REQUEST');
					// } else
					// if ($row['transactionStatus'] == Player_promo::TRANS_STATUS_MANUAL_REQUEST_APPROVED_WITHOUT_RELEASE_BONUS) {
					// 	return lang('(Approved) Pending Bonus Release');
					// } else {
					// 	return $d == 0 ? lang('cms.verificationStatus.none') : lang('cms.verificationStatus.ok');
					// }
				},
			),
			array(
				'dt' => $i++,
				'select' => 'promorules.bonusReleaseToPlayer',
				'alias' => 'bonusReleaseToPlayer',
				'name' => lang('cms.bonusRelease'),
				'formatter' => function ($d, $row) {
					return $d == Promorules::BONUS_RELEASE_TO_PLAYER_AUTO ? lang('cms.auto') : lang('cms.manual');
				},
			),
			array(
				'dt' => ($this->utils->getConfig('enabled_promorules_remaining_available')) ? $i++ : NULL,
				'alias' => 'total_approved_limit',
				'select' => 'promorules.total_approved_limit',
				'name' => lang('promorules.total_approved_limit'),
				'formatter' => function ($d, $row) use ($is_export) {
					$requestCount=$this->player_promo->getTotalPromoApproved($row['promorulesId']);
					if ($d > 0) {
						$d = $d - $requestCount;
						if ($d <= 0) {
							return '<strong>0</strong>';
						}
						return '<strong>'. $d .'</strong>';
					}else{
						if (!$is_export) {
							return $d ?: '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
						} else {
							return $d ?: lang('lang.norecyet');
						}
					}
				},
			),
			array(
				'select' => 'promorules.allow_zero_bonus',
				'alias' => 'allow_zero_bonus',
			),
            array(
				'select' => 'promorules.disabled_pre_application',
				'alias' => 'disabled_pre_application',
			),
			array(
                'dt' => (!$is_export) ? $i++ : NULL,
				'select' => 'playerpromo.transactionStatus',
				'alias' => 'transactionStatus',
				'name' => lang('lang.action'),
				'formatter' => function ($d, $row) use ($allow_decline_on_approved_and_without_release, $disable_pre_application_on_release_bonus_first, $is_export){

					if (!$is_export) {
						$ret = '';

						if ($d == Player_promo::TRANS_STATUS_REQUEST) {
							/**
							 * OGP-11240:
							 * a) Remove old perm guards $manaully_update_promotion_bonus, $manually_decline_promo
							 * b) Add new perm guards
							 * 	manaully_update_promotion_bonus -> button 'edit and release'
							 *  promocancel_list -> button release
							 *  promocancel_list -> button decline
							 */
                            // if($manaully_update_promotion_bonus){
                        	if ($this->permissions->checkPermissions('manaully_update_promotion_bonus')) {
                                $ret = '<a href="#" class="btn btn-xs btn-success" data-toggle="modal" data-target="#promoEditAndRelease" onclick="releasePromoWithZero(' . $row['playerpromoId'] . ', '. $row['allow_zero_bonus']. ')" >' . lang('Edit And Release') . '</a>';
                            }
                            //only bonusAmount>0 to allow release now
                            if(@$row['bonusAmount'] > 0 && $this->permissions->checkPermissions('promocancel_list')) {
                                $ret .= '<a href="#" class="btn btn-xs btn-success" data-toggle="modal" data-target="#promoRelease" onclick="releasePromo(' . $row['playerpromoId'] . ')" >' . lang('Release') . '</a>';
                            }
                            // }
							// if($disable_pre_application_on_release_bonus_first){
							// 	$ret = '<a href="#" class="btn btn-xs btn-success" onclick="approveManualRequestPromo(' . $row['promoCmsSettingId'] . ',' . $row['playerId'] . ')" >' . lang('lang.approve') . '</a>';
							// }
							// }

							// if($manually_decline_promo){
							if ($this->permissions->checkPermissions('promocancel_list')){
								$ret .= '<a href="#" class="btn btn-xs btn-danger" data-toggle="modal" data-target="#promoCancel" onclick="viewPromoDeclineForm(' . $row['playerpromoId'] . ')">' . lang('lang.decline') . '</a>';
							}
							return $ret;
							// } else if ($d == Player_promo::TRANS_STATUS_MANUAL_REQUEST_PENDING) {
							// 	$ret = '<a href="javascript:void()" class="btn btn-xs btn-success" onclick="approveManualRequestPromo(' . $row['promoCmsSettingId'] . ',' . $row['playerId'] . ')" >' . lang('lang.approve') . '</a>';
							// 	$ret .= '<a href="javascript:void()" class="btn btn-xs btn-danger" data-toggle="modal" data-target="#promoCancel" onclick="viewPromoDeclineForm(' . $row['playerpromoId'] . ')">' . lang('lang.decline') . '</a>';
							// 	return $ret;
						//} elseif ($d == Player_promo::TRANS_STATUS_DECLINED && $allow_to_delete_declined_promotion && $declined_forever_promotion) {
						//	$ret = '<a href="#" class="btn btn-xs btn-success" data-toggle="modal" data-target="#promoDeclineForever" onclick="viewPromoDeclineForeverForm(' . $row['playerpromoId'] . ')">' . lang('Declined Forever') . '</a>';
						//	return $ret;
						//} elseif ($d == Player_promo::TRANS_STATUS_MANUAL_REQUEST_APPROVED_WITHOUT_RELEASE_BONUS) {
							//active
						//	$ret = '<a href="#" class="btn btn-xs btn-warning" data-toggle="modal" data-target="#promoExpireModal" onclick="viewPromoExpireForm(' . $row['playerpromoId'] . ')">' . lang('Set Expire') . '</a>';
						//	if($manually_decline_promo){
						//		$ret .= '<a href="#" class="btn btn-xs btn-danger" data-toggle="modal" data-target="#promoCancel" onclick="viewPromoDeclineForm(' . $row['playerpromoId'] . ')">' . lang('lang.decline') . '</a>';
						//	}
						//	return $ret;
						//} elseif ($d == Player_promo::TRANS_STATUS_APPROVED_WITHOUT_RELEASE_BONUS) {
							// if($allow_decline_on_approved_and_without_release){
						//	$ret = '<a href="#" class="btn btn-xs btn-warning" data-toggle="modal" data-target="#promoExpireModal" onclick="viewPromoExpireForm(' . $row['playerpromoId'] . ')">' . lang('Set Expire') . '</a>';
						//	if($manually_decline_promo){
						//		$ret .= '<a href="#" class="btn btn-xs btn-danger" data-toggle="modal" data-target="#promoCancel" onclick="viewPromoDeclineForm(' . $row['playerpromoId'] . ')">' . lang('lang.decline') . '</a>';
						//	}
							// }
						//	if($manaully_update_promotion_bonus){
						//		$ret .= '<a href="#" class="btn btn-xs btn-success" onclick="releasePromo(' . $row['playerpromoId'] . ')" >' . lang('Release') . '</a>';
						//	}
						//	return $ret;
							// } elseif ($d == Player_promo::TRANS_STATUS_FINISHED_WITHDRAW_CONDITION) {
							// 	$ret = '<a href="#" class="btn btn-xs btn-warning" onclick="setFinished(' . $row['playerpromoId'] . ')">' . lang('Set Finished') . '</a>';
							// 	return $ret;
						} elseif ($d == Player_promo::TRANS_STATUS_APPROVED) {
							$ret = '<a href="#" class="btn btn-xs btn-warning" onclick="setFinished(' . $row['playerpromoId'] . ')">' . lang('Set to Finished') . '</a>';
							return $ret;
						} else {
							return '<i>' . lang('cms.noAvailableAction') . '</i>';
						}

					} else {
						return '';
						// $ret = '';

						// if ($d == Player_promo::TRANS_STATUS_REQUEST) {

						// 	return lang('pay.req');

						// } elseif ($d == Player_promo::TRANS_STATUS_DECLINED && $allow_to_delete_declined_promotion) {

						// 	return lang('report.p13');

						//} elseif ($d == Player_promo::TRANS_STATUS_MANUAL_REQUEST_APPROVED_WITHOUT_RELEASE_BONUS) {

						//	return lang('Manual request approved without release bonus.');

						//} elseif ($d == Player_promo::TRANS_STATUS_APPROVED_WITHOUT_RELEASE_BONUS) {

						//	return lang('Approved without release bonus.');

						// } elseif ($d == Player_promo::TRANS_STATUS_APPROVED) {

						// 	return lang('transaction.status.1');

						// } else {
						// 	return lang('lang.norecyet');
						// }
					}

				},
			),
			array(
				'dt' => $i++,
				'select' => 'playerpromo.note',
				'alias' => 'note',
				'name' => lang('Note'),
				'formatter' => function($d){
					$test = explode(' | ', $d);
					if(count($test) > 1){
						$result = array_unique($test);
						$d = '';
						foreach($result as $res){
							$d .= $res.' | ';
						}
					}
					return $d;
				}
			),
		);

		$table = 'playerpromo';
		$joins = array(
			'promorules' => 'promorules.promorulesId = playerpromo.promorulesId',
			'player' => 'player.playerId = playerpromo.playerId',
			// 'playerlevel' => 'playerlevel.playerId = player.playerId',
			'vipsettingcashbackrule' => 'vipsettingcashbackrule.vipsettingcashbackruleId = player.levelId',
			'vipsetting' => 'vipsetting.vipSettingId = vipsettingcashbackrule.vipSettingId',
			'promocmssetting' => 'promocmssetting.promoCmsSettingId = playerpromo.promoCmsSettingId',
			'adminusers' => 'adminusers.userId = playerpromo.processedBy',
			'adminusers adminUsersRequestId' => 'adminUsersRequestId.userId = playerpromo.requestAdminId',
			'affiliates' => 'affiliates.affiliateId = player.affiliateId',
			'playerfriendreferral' => 'playerfriendreferral.referralId = playerpromo.referralId'
		);

        if($input['status'] == Player_promo::TRANS_STATUS_REQUEST){
            //status request's request time and prodessed on are the same
            $input['transactionDateType'] = Player_promo::TRANSACTION_DATE_TYPE_REQUEST_TIME;
        }
        if($input['transactionDateType'] == Player_promo::TRANSACTION_DATE_TYPE_REQUEST_TIME){
            if(isset($input['request_date_from'], $input['request_date_to']) && !empty($input['search_by_date']) ){
                $where[] = "playerpromo.dateApply BETWEEN ? AND ?";
                $values[] = $input['request_date_from'];
                $values[] = $input['request_date_to'];
            }

        }
        if($input['transactionDateType'] == Player_promo::TRANSACTION_DATE_TYPE_PROCESSED_TIME){
            if(isset($input['request_date_from'], $input['request_date_to']) && !empty($input['search_by_date'])){
                $where[] = "playerpromo.dateProcessed BETWEEN ? AND ?";
                $values[] = $input['request_date_from'];
                $values[] = $input['request_date_to'];
            }
        }
        if($input['status'] != $input['player_promo_status']){ //dropdown status
            if($input['player_promo_status'] != 'all'){ //normal status
                $where[] = "playerpromo.transactionStatus = ?";
                $values[] = $input['player_promo_status'];
            }
        }else{ //portlet status
            $where[] = "playerpromo.transactionStatus = ?";
            $values[] = $input['status'];
        }
		if (isset($input['username'])) {
			$where[] = "player.username = ?";
			$values[] = $input['username'];
		}
		if (isset($input['vipsettingcashbackruleId'])) {
			$where[] = "vipsettingcashbackrule.vipsettingcashbackruleId = ?";
			$values[] = $input['vipsettingcashbackruleId'];
		}
		if (isset($input['promoCmsSettingId'])) {
			$where[] = "playerpromo.promoCmsSettingId = ?";
			$values[] = $input['promoCmsSettingId'];
		}
 		//if (!empty($input['processed_by'])) {
		//	$where[] = "processedBy = ?";
		//	$values[] = $input['processed_by'];
		//}

        $where[] = "player.deleted_at IS NULL";

        if(!empty($this->utils->getConfig('custom_friend_referral_promo_cms_id'))){
            $promo_cms_id = $this->utils->getConfig('custom_friend_referral_promo_cms_id');
            $where[] = "playerpromo.promoCmsSettingId in (" . implode(',', $promo_cms_id) . ")";
        }

        if($is_export){
            $this->data_tables->options['is_export']=true;
			// $this->data_tables->options['only_sql']=true;
            if(empty($csv_filename)){
                $csv_filename=$this->utils->create_csv_filename(__FUNCTION__);
            }
            $this->data_tables->options['csv_filename']=$csv_filename;
		}

		$result = $this->data_tables->get_data($request, $columns, $table, $where, $values, $joins);
		if($is_export){
		    //drop result if export
			return $csv_filename;
		}

		return $result;
	}

	/**
	 * detail: get hugebet friend referal promo applications
	 * @param array $request
	 * @param Boolean $is_export
	 * @return array
	 */
	public function hugebetReferralPromoApplicationList($request, $is_export = false) {

		$this->load->model(array('promorules', 'player_promo', 'player_friend_referral', 'player_model'));
		$this->load->library(array('data_tables'));
		$this->load->library(array('permissions'));
		$this->permissions->setPermissions();

        $this->load->library(['language_function']);
		$this->language_function->setCurrentLanguage(Language_function::INT_LANG_ENGLISH);
		$_currLang = $this->language_function->getCurrentLanguage();

		$promotion_rules = $this->utils->getConfig('promotion_rules');
		$enable_split_player_username_and_affiliate = $this->utils->getConfig('enable_split_player_username_and_affiliate');

		//default is true
		// $release_on_admin_approve = $promotion_rules['release_on_admin_approve'];
		$allow_decline_on_approved_and_without_release = $promotion_rules['allow_decline_on_approved_and_without_release'];
		$disable_pre_application_on_release_bonus_first = $promotion_rules['disable_pre_application_on_release_bonus_first'];

		$this->utils->debug_log('allow_decline_on_approved_and_without_release', $allow_decline_on_approved_and_without_release, 'disable_pre_application_on_release_bonus_first', $disable_pre_application_on_release_bonus_first);

		$i = 0;
		$input = $this->data_tables->extra_search($request);

        $controller = $this;

		$where = array();
		$values = array();

		$columns = array(
			array(
				'select' => 'player.playerId',
				'alias' => 'playerId',
			),
			array(
				'select' => 'promocmssetting.promoCmsSettingId',
				'alias' => 'promoCmsSettingId',
			),
			array(
				'select' => 'promorules.promorulesId',
				'alias' => 'promorulesId',
			),
			array(
				'select' => 'promorules.disabled_pre_application',
				'alias' => 'disabled_pre_application',
			),
            array(
                'select' => '(CASE WHEN playerpromo.requestAdminId IS NOT NULL THEN adminUsersRequestId.username ELSE null END)',
                'alias' => 'requestAdmin',
            ),
            array(
                'select' => '(CASE WHEN playerpromo.requestPlayerId IS NOT NULL THEN player.username ELSE null END)',
                'alias' => 'requestPlayer',
            ),
			array(
                'select' => 'playerpromo.vip_level_info',
                'alias' => 'vip_level_info',
            ),
            array(
                'select' => 'playerpromo.group_name_on_created',
                'alias' => 'group_name_on_created',
            ),
            array(
                'select' => 'playerpromo.vip_level_on_created',
                'alias' => 'vip_level_on_created',
            ),
            array(
                'select' => 'playerpromo.referralId',
                'alias' => 'referralId',
            ),
			array(
				'dt' => (!$is_export) ? $i++ : NULL,
				'select' => 'player.playerId',
				'alias' => 'sales_promo_id',
				'formatter' => function ($d, $row) use ($is_export) {
					if (!$is_export) {
						if ($this->utils->isEnabledFeature('batch_decline_promo') || $this->utils->isEnabledFeature('batch_finish_promo') || $this->utils->isEnabledFeature('batch_release_promo')) {
							$action = '<div class="clearfix">';
							$action .= '	<div class="col-md-3" style="padding:5px 1px 0 2px"><input type="checkbox" name="sales_promo_id" class="chk-promo-id" value="'.$row['playerpromoId'].'"></div>';
							// OGP - 11385 Remove Details button and Action column
							// $action .= '	<div class="col-md-9" style="padding:0 2px 0 2px"><span class="btn btn-xs btn-info review-btn" data-toggle="modal" onclick="getDupLicateAccountList(' . $row["playerId"] . ')" data-target="#detailModal">' . lang("lang.details") . '</span></div>';
							$action .= "</div>";
							return $action;
						} else {
							// OGP - 11385 Remove Details button and Action column
							// return '<span class="btn btn-xs btn-info review-btn" data-toggle="modal" onclick="getDupLicateAccountList(' . $row["playerId"] . ')" data-target="#detailModal">' . lang("lang.details") . '</span>';
						}
					} else {
						return '';
					}
				},
			),
            array(
                'select' => 'playerpromo.playerpromoId',
                'alias' => 'playerpromoId',
                'name' => lang('column.id'),
                'formatter' => 'defaultFormatter'
            ),
			array(
				'dt' => $i++,
				'select' => 'player.username',
				'alias' => 'username',
				'name' => lang('player.01'),
				'formatter' => function ($d, $row) use ($is_export,$enable_split_player_username_and_affiliate) {
					if (!$is_export) {
						if (!$enable_split_player_username_and_affiliate) {
							if(!empty($row['affiliate'])){
								$d=$d.' ('.$row['affiliate'].')';
							}
						}
						return '<a href="/player_management/userInformation/' . $row['playerId'] . '" data-toggle="tooltip" data-original-title="' . lang("cms.checkPlayerDetails") . '" data-placement="right">' . $d . '</a>';
					} else {
						return $d;
					}
				},
			),
			array(
				'dt' => $enable_split_player_username_and_affiliate ? $i++ : null ,
                'select' => 'affiliates.username',
                'alias' => 'affiliate',
				'name' => lang('Affiliate'),
				'formatter' => function ($d, $row) use ($is_export) {
					if (!$is_export) {
                        return $d ? $d : '<i class="text-muted">' . lang('N/A') . '</i>';
					} else {
                        return ($d ? $d : lang('N/A'));
					}
				},
			),
			array(
				'dt' => $i++,
				'alias' => 'memberLevel',
                'select' => 'CONCAT(group_name_on_created, LPAD(vip_level_on_created, 4, 0) )',
				'name' => lang('player.07'),
				'formatter' => function ($d, $row) {
                    $sprintf_format = '%s - %s'; // params: groupName, vipLevelName
					$groupName = lang('N/A'); // defaults
					$vipLevelName = lang('N/A'); // defaults
					$vip_level_info = json_decode($row['vip_level_info'], true);
					if( ! empty($vip_level_info['vipsetting']['groupName']) ){
						$groupName = lang($vip_level_info['vipsetting']['groupName']);
					}
					if( ! empty($vip_level_info['vipsettingcashbackrule']['vipLevelName']) ){
						$vipLevelName =  lang($vip_level_info['vipsettingcashbackrule']['vipLevelName']);
					}
					return sprintf($sprintf_format, $groupName, $vipLevelName);
				},
			),
			array(
				'dt' => $i++,
				'select' => 'promocmssetting.promoName',
				'alias' => 'promoTitle',
				'name' => lang('cms.promotitle'),
				'formatter' => function ($d, $row) use ($is_export) {

					if($d == Promorules::SYSTEM_MANUAL_PROMO_CMS_NAME){
						$promoName = lang('promo.'. $d);
					}else{
						$promoName = $d;
					}

					if (!$is_export) {
						if($d == Promorules::SYSTEM_MANUAL_PROMO_CMS_NAME){
							$html = $promoName;
						}else{
							$html = anchor_popup('/cms_management/viewPromoDetails/' . $row['promoCmsSettingId'], $promoName, array(
								'width' => '1030',
								'height' => '600',
								'scrollbars' => 'yes',
								'status' => 'yes',
								'resizable' => 'no',
								'screenx' => '0',
								'screeny' => '0'));
							$html = '<span class="check_cms_promo" data-toggle="tooltip" data-playerpromoid="'.$row['playerpromoId'].'" data-promocmssettingid="'.$row['promoCmsSettingId'].'" title="' . lang('cms.checkCmsPromo') . '" data-placement="right">'. $html. '</span>';
						}

						return $html;

					} else {
						return $promoName;
					}
				},
			),
            array(
                'dt' => $i++,
                'alias' => 'referralType',
                'name' => lang('Referral Type'),
                'formatter' => function ($d, $row) use ($controller, $is_export){
					$referralType = 'Referrer';
                    if (!$is_export) {
                        return !empty($referralType) ? lang($referralType) : '<i class="text-muted">' . lang('N/A') . '</i>';
                    } else {
                        return !empty($referralType) ? lang($referralType) : lang('N/A');
                    }
                },
            ),
			array(
                'dt' => $i++,
                'select' => 'playerfriendreferral.playerId',
                'alias' => 'referrer',
                'name' => lang('Referrer'),
                'formatter' => function ($d, $row) use ($controller, $is_export) {
					$referrerPlayerId = $d;
					$referrerUsername = $controller->player_model->getUsernameById($referrerPlayerId);
					$referrer = '<a href="/player_management/userInformation/' . $referrerPlayerId . '" data-toggle="tooltip" data-original-title="' . lang("cms.checkPlayerDetails") . '" data-placement="right">' . $referrerUsername . '</a>';
                    if (!$is_export) {
                        return !empty($referrer) ? $referrer : '<i class="text-muted">' . lang('N/A') . '</i>';
                    } else {
                        return !empty($referrer) ? $referrer : lang('N/A');
                    }
                },
            ),
			array(
                'dt' => $i++,
                'select' => 'playerfriendreferral.invitedPlayerId',
                'alias' => 'referred',
                'name' => lang('Referred'),
                'formatter' => function ($d, $row) use ($controller, $is_export) {
					$referredPlayerId = $d;
					$referredUsername = $controller->player_model->getUsernameById($referredPlayerId);
					$referred = '<a href="/player_management/userInformation/' . $referredPlayerId . '" data-toggle="tooltip" data-original-title="' . lang("cms.checkPlayerDetails") . '" data-placement="right">' . $referredUsername . '</a>';
                    if (!$is_export) {
                        return !empty($referred) ? $referred : '<i class="text-muted">' . lang('N/A') . '</i>';
                    } else {
                        return !empty($referred) ? $referred : lang('N/A');
                    }
                },
            ),
			array(
				'dt' => $i++,
				'select' => 'player_friend_referral_level.interval_level',
				'alias' => 'level',
                'name' => lang('Interval Level'),
				'formatter' => function ($d) use ($is_export) {
					if (!$is_export) {
						return '<strong>' . $d . '</strong>';
					} else {
						return $d;
					}
				},
			),
			array(
				'dt' => $i++,
				'select' => 'player_friend_referral_level.last_invited_total_bet',
				'alias' => 'last_invited_total_bet',
				'name' => lang('Bet Amount'),
				'formatter' => function ($d) use ($is_export) {
					if (!$is_export) {
						return '<strong>' . $d . '</strong>';
					} else {
						return $d;
					}
				},
			),
			array(
				'dt' => $i++,
				'select' => 'playerpromo.bonusAmount',
				'alias' => 'bonusAmount',
				'name' => lang('cms.bonusAmount'),
				'formatter' => function ($d) use ($is_export) {
					if (!$is_export) {
						return '<strong>' . $d . '</strong>';
					} else {
						return $d;
					}
				},
			),
			array(
				'dt' => $i++,
				'select' => 'playerpromo.withdrawConditionAmount',
				'alias' => 'withdrawConditionAmount',
				'name' => lang('Withdraw Condition'),
				'formatter' => function ($d) use ($is_export) {
					if (!$is_export) {
						return '<strong>' . $d . '</strong>';
					} else {
						return $d;
					}
				},
			),
			array(
				'dt' => $i++,
				'select' => 'playerpromo.dateApply',
				'alias' => 'dateApply',
				'name' => lang('cms.dateApplyRequest'),
				'formatter' => 'dateTimeFormatter',
			),
            array(
                'dt' => $i++,
                'alias' => 'requestBy',
                'name' => lang('cms.requestBy'),
                'select' => 'CONCAT('. // To concat the related id for sort by dataTable()
                    // convert to string type
                    '(CASE WHEN playerpromo.requestAdminId IS NOT NULL THEN adminUsersRequestId.username ELSE "" END)'.
                    ','.
                    '(CASE WHEN playerpromo.requestPlayerId IS NOT NULL THEN player.username ELSE "" END)'.
                ')',
                'formatter' => function ($d, $row) {
                    if(isset($row['requestAdmin'])){
                        $d = $row['requestAdmin'];
                    }else if(isset($row['requestPlayer'])){
                        $d = $row['requestPlayer'];
                    }else{
                        $d = NULL;
                    }

                    return $d;
                },
            ),
            array(
                'dt' => $i++,
                'select' => 'playerpromo.order_generated_by',
                'alias' => 'order_generated_by',
                'name' => lang('promo.request_list.order_generated_by'),
                'formatter' => function ($d, $row) use ($controller) {
                    return $controller->player_promo->orderGeneratedByToName($d);
                },
            ),
            array(
                'dt' => $i++,
                'select' => 'playerpromo.player_request_ip',
                'alias' => 'player_request_ip',
                'name' => lang('promo.request_list.player_request_ip'),
                'formatter' => function ($d) {
                    return $d ? : lang('N/A');
                },
            ),
			array(
				'dt' => $i++,
				'select' => 'playerpromo.dateProcessed',
				'alias' => 'dateProcessed',
				'name' => lang('cms.dateProcessed'),
				'formatter' => function ($d, $row) {
					return $d ?: lang('N/A');
				},
			),
			array(
				'dt' => $i++,
				'alias' => 'processed_by_name',
				'select' => 'adminusers.username',
				'name' => lang('pay.procssby'),
				'formatter' => function ($d) use ($is_export) {
					if (!$is_export) {
						return $d ?: '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
					} else {
						return $d ?: lang('lang.norecyet');
					}
				},
			),
			array(
				'dt' => $i++,
				'select' => 'playerpromo.transactionStatus',
				'alias' => 'status',
				'name' => lang('Status'),
				'formatter' => function ($d, $row) use ($controller) {

					return $controller->player_promo->statusToName($d);
				},
			),
			array(
				'dt' => $i++,
				'select' => 'promorules.bonusReleaseToPlayer',
				'alias' => 'bonusReleaseToPlayer',
				'name' => lang('cms.bonusRelease'),
				'formatter' => function ($d, $row) {
					return $d == Promorules::BONUS_RELEASE_TO_PLAYER_AUTO ? lang('cms.auto') : lang('cms.manual');
				},
			),
			array(
				'dt' => ($this->utils->getConfig('enabled_promorules_remaining_available')) ? $i++ : NULL,
				'alias' => 'total_approved_limit',
				'select' => 'promorules.total_approved_limit',
				'name' => lang('promorules.total_approved_limit'),
				'formatter' => function ($d, $row) use ($is_export) {
					$requestCount=$this->player_promo->getTotalPromoApproved($row['promorulesId']);
					if ($d > 0) {
						$d = $d - $requestCount;
						if ($d <= 0) {
							return '<strong>0</strong>';
						}
						return '<strong>'. $d .'</strong>';
					}else{
						if (!$is_export) {
							return $d ?: '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
						} else {
							return $d ?: lang('lang.norecyet');
						}
					}
				},
			),
            array(
                'select' => 'promorules.allow_zero_bonus',
                'alias' => 'allow_zero_bonus',
            ),
			array(
				'select' => 'promorules.disabled_pre_application',
				'alias' => 'disabled_pre_application',
			),
			array(
                'dt' => (!$is_export) ? $i++ : NULL,
				'select' => 'playerpromo.transactionStatus',
				'alias' => 'transactionStatus',
				'name' => lang('lang.action'),
				'formatter' => function ($d, $row) use ($allow_decline_on_approved_and_without_release, $disable_pre_application_on_release_bonus_first, $is_export){

					if (!$is_export) {
						$ret = '';

						if ($d == Player_promo::TRANS_STATUS_REQUEST) {
							/**
							 * OGP-11240:
							 * a) Remove old perm guards $manaully_update_promotion_bonus, $manually_decline_promo
							 * b) Add new perm guards
							 * 	manaully_update_promotion_bonus -> button 'edit and release'
							 *  promocancel_list -> button release
							 *  promocancel_list -> button decline
							 */
                            // if($manaully_update_promotion_bonus){
                        	if ($this->permissions->checkPermissions('manaully_update_promotion_bonus')) {
                                $ret = '<a href="#" class="btn btn-xs btn-success" data-toggle="modal" data-target="#promoEditAndRelease" onclick="releasePromoWithZero(' . $row['playerpromoId'] . ', '. $row['allow_zero_bonus']. ')" >' . lang('Edit And Release') . '</a>';
                            }
                            //only bonusAmount>0 to allow release now
                            if(@$row['bonusAmount'] > 0 && $this->permissions->checkPermissions('promocancel_list')) {
                                $ret .= '<a href="#" class="btn btn-xs btn-success" data-toggle="modal" data-target="#promoRelease" onclick="releasePromo(' . $row['playerpromoId'] . ')" >' . lang('Release') . '</a>';
                            }

							if ($this->permissions->checkPermissions('promocancel_list')){
								$ret .= '<a href="#" class="btn btn-xs btn-danger" data-toggle="modal" data-target="#promoCancel" onclick="viewPromoDeclineForm(' . $row['playerpromoId'] . ')">' . lang('lang.decline') . '</a>';
							}
							return $ret;

						} elseif ($d == Player_promo::TRANS_STATUS_APPROVED) {
							$ret = '<a href="#" class="btn btn-xs btn-warning" onclick="setFinished(' . $row['playerpromoId'] . ')">' . lang('Set to Finished') . '</a>';
							return $ret;
						} else {
							return '<i>' . lang('cms.noAvailableAction') . '</i>';
						}

					} else {
						return '';
					}

				},
			),
			array(
				'dt' => $i++,
				'select' => 'playerpromo.note',
				'alias' => 'note',
				'name' => lang('Note'),
				'formatter' => function($d){
					$test = explode(' | ', $d);
					if(count($test) > 1){
						$result = array_unique($test);
						$d = '';
						foreach($result as $res){
							$d .= $res.' | ';
						}
					}
					return $d;
				}
			),
		);

		$table = 'playerpromo';
		$joins = array(
			'promorules' => 'promorules.promorulesId = playerpromo.promorulesId',
			'player' => 'player.playerId = playerpromo.playerId',
			'vipsettingcashbackrule' => 'vipsettingcashbackrule.vipsettingcashbackruleId = player.levelId',
			'vipsetting' => 'vipsetting.vipSettingId = vipsettingcashbackrule.vipSettingId',
			'promocmssetting' => 'promocmssetting.promoCmsSettingId = playerpromo.promoCmsSettingId',
			'adminusers' => 'adminusers.userId = playerpromo.processedBy',
			'adminusers adminUsersRequestId' => 'adminUsersRequestId.userId = playerpromo.requestAdminId',
			'affiliates' => 'affiliates.affiliateId = player.affiliateId',
			'playerfriendreferral' => 'playerfriendreferral.referralId = playerpromo.referralId',
			'player_friend_referral_level' => 'player_friend_referral_level.player_promo_id = playerpromo.playerpromoId'
		);

        if($input['status'] == Player_promo::TRANS_STATUS_REQUEST){
            //status request's request time and prodessed on are the same
            $input['transactionDateType'] = Player_promo::TRANSACTION_DATE_TYPE_REQUEST_TIME;
        }
        if($input['transactionDateType'] == Player_promo::TRANSACTION_DATE_TYPE_REQUEST_TIME){
            if(isset($input['request_date_from'], $input['request_date_to']) && !empty($input['search_by_date']) ){
                $where[] = "playerpromo.dateApply BETWEEN ? AND ?";
                $values[] = $input['request_date_from'];
                $values[] = $input['request_date_to'];
            }

        }
        if($input['transactionDateType'] == Player_promo::TRANSACTION_DATE_TYPE_PROCESSED_TIME){
            if(isset($input['request_date_from'], $input['request_date_to']) && !empty($input['search_by_date'])){
                $where[] = "playerpromo.dateProcessed BETWEEN ? AND ?";
                $values[] = $input['request_date_from'];
                $values[] = $input['request_date_to'];
            }
        }
        if($input['status'] != $input['player_promo_status']){ //dropdown status
            if($input['player_promo_status'] != 'all'){ //normal status
                $where[] = "playerpromo.transactionStatus = ?";
                $values[] = $input['player_promo_status'];
            }
        }else{ //portlet status
            $where[] = "playerpromo.transactionStatus = ?";
            $values[] = $input['status'];
        }
		if (isset($input['username'])) {
			$where[] = "player.username = ?";
			$values[] = $input['username'];
		}
		if (isset($input['vipsettingcashbackruleId'])) {
			$where[] = "vipsettingcashbackrule.vipsettingcashbackruleId = ?";
			$values[] = $input['vipsettingcashbackruleId'];
		}
		if (isset($input['promoCmsSettingId'])) {
			$where[] = "playerpromo.promoCmsSettingId = ?";
			$values[] = $input['promoCmsSettingId'];
		}

        $where[] = "player.deleted_at IS NULL";

		$promo_cms_id = $this->utils->getConfig('t1t_common_brazil_friend_referral_promo_cms_id');
        if(!empty($promo_cms_id)){
            $where[] = "playerpromo.promoCmsSettingId in (" . implode(',', $promo_cms_id) . ")";
        }

        if($is_export){
            $this->data_tables->options['is_export']=true;
			// $this->data_tables->options['only_sql']=true;
            if(empty($csv_filename)){
                $csv_filename=$this->utils->create_csv_filename(__FUNCTION__);
            }
            $this->data_tables->options['csv_filename']=$csv_filename;
		}

		$result = $this->data_tables->get_data($request, $columns, $table, $where, $values, $joins);
		if($is_export){
		    //drop result if export
			return $csv_filename;
		}

		return $result;
	}

    /**
     * detail: get promo applications
     * @param array $request
     * @param Boolean $is_export
     * @return array
     */
    public function promoApplicationList($request, $is_export = false) {

        $this->load->model(array('promorules', 'player_promo'));
        $this->load->library(array('data_tables'));
        $this->load->library(array('permissions'));
        $this->permissions->setPermissions();

        $this->load->library(['language_function']);
		// $this->language_function->setCurrentLanguage(Language_function::INT_LANG_ENGLISH);
		$_currLang = $this->language_function->getCurrentLanguage();

        $promotion_rules = $this->utils->getConfig('promotion_rules');
        $enable_split_player_username_and_affiliate = $this->utils->getConfig('enable_split_player_username_and_affiliate');

        //default is true
        // $release_on_admin_approve = $promotion_rules['release_on_admin_approve'];
        $allow_decline_on_approved_and_without_release = $promotion_rules['allow_decline_on_approved_and_without_release'];
        $disable_pre_application_on_release_bonus_first = $promotion_rules['disable_pre_application_on_release_bonus_first'];

        $this->utils->debug_log('allow_decline_on_approved_and_without_release', $allow_decline_on_approved_and_without_release, 'disable_pre_application_on_release_bonus_first', $disable_pre_application_on_release_bonus_first);

        $i = 0;
        //	$request = $this->input->post();
        $input = $this->data_tables->extra_search($request);

        $controller = $this;

        $where = array();
        $values = array();

        // $allow_to_delete_declined_promotion = $this->permissions->checkPermissions('allow_to_delete_declined_promotion');
//		$declined_forever_promotion = $this->utils->isEnabledFeature('declined_forever_promotion');
//		$manaully_update_promotion_bonus = $this->permissions->checkPermissions('manaully_update_promotion_bonus');
//		$manually_decline_promo = $this->permissions->checkPermissions('promocancel_list');

        $columns = array(
            array(
                'select' => 'player.playerId',
                'alias' => 'playerId',
            ),
            array(
                'select' => 'promocmssetting.promoCmsSettingId',
                'alias' => 'promoCmsSettingId',
            ),
            array(
                'select' => 'promorules.promorulesId',
                'alias' => 'promorulesId',
            ),
            array(
                'select' => 'promorules.disabled_pre_application',
                'alias' => 'disabled_pre_application',
            ),
            array(
                'select' => '(CASE WHEN playerpromo.requestAdminId IS NOT NULL THEN adminUsersRequestId.username ELSE null END)',
                'alias' => 'requestAdmin',
            ),
            array(
                'select' => '(CASE WHEN playerpromo.requestPlayerId IS NOT NULL THEN player.username ELSE null END)',
                'alias' => 'requestPlayer',
            ),
            array(
                'select' => 'playerpromo.vip_level_info',
                'alias' => 'vip_level_info',
            ),
            array(
                'select' => 'playerpromo.group_name_on_created',
                'alias' => 'group_name_on_created',
            ),
            array(
                'select' => 'playerpromo.vip_level_on_created',
                'alias' => 'vip_level_on_created',
            ),
            array(
                'dt' => (!$is_export) ? $i++ : NULL,
                'select' => 'player.playerId',
                'alias' => 'sales_promo_id',
                'formatter' => function ($d, $row) use ($is_export) {
                    if (!$is_export) {
                        if ($this->utils->isEnabledFeature('batch_decline_promo') || $this->utils->isEnabledFeature('batch_finish_promo') || $this->utils->isEnabledFeature('batch_release_promo')) {
                            $action = '<div class="clearfix">';
                            $action .= '	<div class="col-md-3" style="padding:5px 1px 0 2px"><input type="checkbox" name="sales_promo_id" class="chk-promo-id" value="'.$row['playerpromoId'].'"></div>';
                            // OGP - 11385 Remove Details button and Action column
                            // $action .= '	<div class="col-md-9" style="padding:0 2px 0 2px"><span class="btn btn-xs btn-info review-btn" data-toggle="modal" onclick="getDupLicateAccountList(' . $row["playerId"] . ')" data-target="#detailModal">' . lang("lang.details") . '</span></div>';
                            $action .= "</div>";
                            return $action;
                        } else {
                            // OGP - 11385 Remove Details button and Action column
                            // return '<span class="btn btn-xs btn-info review-btn" data-toggle="modal" onclick="getDupLicateAccountList(' . $row["playerId"] . ')" data-target="#detailModal">' . lang("lang.details") . '</span>';
                        }
                    } else {
                        return '';
                    }
                },
            ),
            array(
                'select' => 'playerpromo.playerpromoId',
                'alias' => 'playerpromoId',
                'name' => lang('column.id'),
                'formatter' => 'defaultFormatter'
            ),
            array(
                'dt' => $i++,
                'select' => 'player.username',
                'alias' => 'username',
                'name' => lang('player.01'),
                'formatter' => function ($d, $row) use ($is_export,$enable_split_player_username_and_affiliate) {
                    if (!$is_export) {
                        if (!$enable_split_player_username_and_affiliate) {
                            if(!empty($row['affiliate'])){
                                $d=$d.' ('.$row['affiliate'].')';
                            }
                        }
                        return '<a href="/player_management/userInformation/' . $row['playerId'] . '" data-toggle="tooltip" data-original-title="' . lang("cms.checkPlayerDetails") . '" data-placement="right">' . $d . '</a>';
                    } else {
                        return $d;
                    }
                },
            ),
            array(
                'dt' => $enable_split_player_username_and_affiliate ? $i++ : null ,
                'select' => 'affiliates.username',
                'alias' => 'affiliate',
                'name' => lang('Affiliate'),
                'formatter' => function ($d, $row) use ($is_export) {
                    if (!$is_export) {
                        return $d ? $d : '<i class="text-muted">' . lang('N/A') . '</i>';
                    } else {
                        return ($d ? $d : lang('N/A'));
                    }
                },
            ),
            array(
                'dt' => $i++,
                'alias' => 'memberLevel',
                'select' => 'CONCAT(group_name_on_created, LPAD(vip_level_on_created, 4, 0) )',
                'name' => lang('player.07'),
                'formatter' => function ($d, $row) {
                    $sprintf_format = '%s - %s'; // params: groupName, vipLevelName
					$groupName = lang('N/A'); // defaults
					$vipLevelName = lang('N/A'); // defaults
					$vip_level_info = json_decode($row['vip_level_info'], true);
					if( ! empty($vip_level_info['vipsetting']['groupName']) ){
						$groupName = lang($vip_level_info['vipsetting']['groupName']);
					}
					if( ! empty($vip_level_info['vipsettingcashbackrule']['vipLevelName']) ){
						$vipLevelName =  lang($vip_level_info['vipsettingcashbackrule']['vipLevelName']);
					}
					return sprintf($sprintf_format, $groupName, $vipLevelName);
                },
            ),
            array(
                'dt' => $i++,
                'select' => 'promocmssetting.promoName',
                'alias' => 'promoTitle',
                'name' => lang('cms.promotitle'),
                'formatter' => function ($d, $row) use ($is_export) {

                    if($d == Promorules::SYSTEM_MANUAL_PROMO_CMS_NAME){
                        $promoName = lang('promo.'. $d);
                    }else{
                        $promoName = $d;
                    }

                    if (!$is_export) {
                        if($d == Promorules::SYSTEM_MANUAL_PROMO_CMS_NAME){
                            $html = $promoName;
                        }else{
                            $html = anchor_popup('/cms_management/viewPromoDetails/' . $row['promoCmsSettingId'], $promoName, array(
                                'width' => '1030',
                                'height' => '600',
                                'scrollbars' => 'yes',
                                'status' => 'yes',
                                'resizable' => 'no',
                                'screenx' => '0',
                                'screeny' => '0'));
                            $html = '<span class="check_cms_promo" data-toggle="tooltip" data-playerpromoid="'.$row['playerpromoId'].'" data-promocmssettingid="'.$row['promoCmsSettingId'].'" title="' . lang('cms.checkCmsPromo') . '" data-placement="right">'. $html. '</span>';
                        }

                        return $html;

                    } else {
                        return $promoName;
                    }
                },
            ),
            // array(
            // 	'dt' => $i++,
            // 	'select' => 'promorules.promoName',
            // 	'alias' => 'promoName',
            // 	'formatter' => function ($d, $row) {
            // 		return '<a href="#" data-toggle="modal" data-target="#promoDetails" onclick="return viewPromoRuleDetails(' . $row['promorulesId'] . ')">' .
            // 		'<span data-toggle="tooltip" data-original-title="' . lang("cms.showPromoRuleDetails") . '" data-placement="right">' . $row['promoName'] . '</span></a>';
            // 	},
            // ),
            array(
                'dt' => $i++,
                'select' => 'playerpromo.depositAmount',
                'alias' => 'depositAmount',
                'name' => lang('Deposit'),
                // 'formatter' => function ($d, $row) {
                // 	return $d ?: lang('N/A');
                // },
                'formatter' => function ($d) use ($is_export) {
                    if (!$is_export) {
                        return '<strong>' . $d . '</strong>';
                    } else {
                        return $d;
                    }
                },
            ),
            array(
                'dt' => $i++,
                'select' => 'playerpromo.bonusAmount',
                'alias' => 'bonusAmount',
                'name' => lang('cms.bonusAmount'),
                // 'formatter' => function ($d, $row) {
                // 	// if ($row['transactionStatus'] == Player_promo::TRANS_STATUS_MANUAL_REQUEST_PENDING) {
                // 	// 	return lang('Will generate result once approved');
                // 	// } else {
                // 	return $d = $d ?: lang('N/A');
                // 	// }
                // },
                'formatter' => function ($d) use ($is_export) {
                    if (!$is_export) {
                        return '<strong>' . $d . '</strong>';
                    } else {
                        return $d;
                    }
                },
            ),
            array(
                'dt' => $i++,
                'select' => 'playerpromo.withdrawConditionAmount',
                'alias' => 'withdrawConditionAmount',
                'name' => lang('Withdraw Condition'),
                // 'formatter' => function ($d, $row) {
                // 	// if ($row['transactionStatus'] == Player_promo::TRANS_STATUS_MANUAL_REQUEST_PENDING) {
                // 	// 	return lang('Will generate result once approved');
                // 	// } else {
                // 	return $d = $d ?: lang('N/A');
                // 	// }
                // },

                'formatter' => function ($d) use ($is_export) {
                    if (!$is_export) {
                        return '<strong>' . $d . '</strong>';
                    } else {
                        return $d;
                    }
                },
            ),
            array(
                'dt' => $i++,
                'select' => 'playerpromo.dateApply',
                'alias' => 'dateApply',
                'name' => lang('cms.dateApplyRequest'),
                'formatter' => 'dateTimeFormatter',
            ),
            array(
                'dt' => $i++,
                'alias' => 'requestBy',
                'select' => 'CONCAT('. // To concat the related id for sort by dataTable()
                    // convert to string type
                    '(CASE WHEN playerpromo.requestAdminId IS NOT NULL THEN adminUsersRequestId.username ELSE "" END)'.
                    ','.
                    '(CASE WHEN playerpromo.requestPlayerId IS NOT NULL THEN player.username ELSE "" END)'.
                ')',
                'name' => lang('cms.requestBy'),
                'formatter' => function ($d, $row) {
                    if(isset($row['requestAdmin'])){
                        $d = $row['requestAdmin'];
                    }else if(isset($row['requestPlayer'])){
                        $d = $row['requestPlayer'];
                    }else{
                        $d = NULL;
                    }

                    return $d;
                },
            ),
            array(
                'dt' => $i++,
                'select' => 'playerpromo.order_generated_by',
                'alias' => 'order_generated_by',
                'name' => lang('promo.request_list.order_generated_by'),
                'formatter' => function ($d, $row) use ($controller) {
                    return $controller->player_promo->orderGeneratedByToName($d);
                },
            ),
            array(
                'dt' => $i++,
                'select' => 'playerpromo.player_request_ip',
                'alias' => 'player_request_ip',
                'name' => lang('promo.request_list.player_request_ip'),
                'formatter' => function ($d) {
                    return $d ? : lang('N/A');
                },
            ),
            array(
                'dt' => $i++,
                'select' => 'playerpromo.dateProcessed',
                'alias' => 'dateProcessed',
                'name' => lang('cms.dateProcessed'),
                'formatter' => function ($d, $row) {
                    return $d ?: lang('N/A');
                },
            ),
            array(
                'dt' => $i++,
                'alias' => 'processed_by_name',
                'select' => 'adminusers.username',
                'name' => lang('pay.procssby'),
                'formatter' => function ($d) use ($is_export) {
                    if (!$is_export) {
                        return $d ?: '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
                    } else {
                        return $d ?: lang('lang.norecyet');
                    }
                },
            ),
//			array(
//				'dt' => $i++,
//				'select' => 'playerpromo.login_ip',
//				'alias' => 'loginIp',
//				'name' => lang('sys.vu44'),
//				'formatter' => function ($d, $row) {
//					return $d ? : lang('N/A');
//				},
//			),
            array(
                'dt' => $i++,
                'select' => 'playerpromo.transactionStatus',
                'alias' => 'status',
                'name' => lang('Status'),
                'formatter' => function ($d, $row) use ($controller) {

                    return $controller->player_promo->statusToName($d);

                    // if ($row['transactionStatus'] == Player_promo::TRANS_STATUS_MANUAL_REQUEST_PENDING) {
                    // return lang('REQUEST');
                    // } else
                    // if ($row['transactionStatus'] == Player_promo::TRANS_STATUS_MANUAL_REQUEST_APPROVED_WITHOUT_RELEASE_BONUS) {
                    // 	return lang('(Approved) Pending Bonus Release');
                    // } else {
                    // 	return $d == 0 ? lang('cms.verificationStatus.none') : lang('cms.verificationStatus.ok');
                    // }
                },
            ),
            array(
                'dt' => $i++,
                'select' => 'promorules.bonusReleaseToPlayer',
                'alias' => 'bonusReleaseToPlayer',
                'name' => lang('cms.bonusRelease'),
                'formatter' => function ($d, $row) {
                    return $d == Promorules::BONUS_RELEASE_TO_PLAYER_AUTO ? lang('cms.auto') : lang('cms.manual');
                },
            ),
            array(
                'dt' => ($this->utils->getConfig('enabled_promorules_remaining_available')) ? $i++ : NULL,
                'alias' => 'total_approved_limit',
                'select' => 'promorules.total_approved_limit',
                'name' => lang('promorules.total_approved_limit'),
                'formatter' => function ($d, $row) use ($is_export) {
                    $requestCount=$this->player_promo->getTotalPromoApproved($row['promorulesId']);
                    if ($d > 0) {
                        $d = $d - $requestCount;
                        if ($d <= 0) {
                            return '<strong>0</strong>';
                        }
                        return '<strong>'. $d .'</strong>';
                    }else{
                        if (!$is_export) {
                            return $d ?: '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
                        } else {
                            return $d ?: lang('lang.norecyet');
                        }
                    }
                },
            ),
            array(
                'select' => 'promorules.allow_zero_bonus',
                'alias' => 'allow_zero_bonus',
            ),
            array(
                'select' => 'promorules.disabled_pre_application',
                'alias' => 'disabled_pre_application',
            ),
            array(
                'dt' => (!$is_export) ? $i++ : NULL,
                'select' => 'playerpromo.transactionStatus',
                'alias' => 'transactionStatus',
                'name' => lang('lang.action'),
                'formatter' => function ($d, $row) use ($allow_decline_on_approved_and_without_release, $disable_pre_application_on_release_bonus_first, $is_export){

                    if (!$is_export) {
                        $ret = '';

                        if ($d == Player_promo::TRANS_STATUS_REQUEST) {
                            /**
                             * OGP-11240:
                             * a) Remove old perm guards $manaully_update_promotion_bonus, $manually_decline_promo
                             * b) Add new perm guards
                             * 	manaully_update_promotion_bonus -> button 'edit and release'
                             *  promocancel_list -> button release
                             *  promocancel_list -> button decline
                             */
                            // if($manaully_update_promotion_bonus){
                            if ($this->permissions->checkPermissions('manaully_update_promotion_bonus')) {
                                $ret = '<a href="#" class="btn btn-xs btn-success" data-toggle="modal" data-target="#promoEditAndRelease" onclick="releasePromoWithZero(' . $row['playerpromoId'] . ', '. $row['allow_zero_bonus']. ')" >' . lang('Edit And Release') . '</a>';
                            }
                            //only bonusAmount>0 to allow release now
                            if(@$row['bonusAmount'] > 0 && $this->permissions->checkPermissions('promocancel_list')) {
                                $ret .= '<a href="#" class="btn btn-xs btn-success" data-toggle="modal" data-target="#promoRelease" onclick="releasePromo(' . $row['playerpromoId'] . ')" >' . lang('Release') . '</a>';
                            }
                            // }
                            // if($disable_pre_application_on_release_bonus_first){
                            // 	$ret = '<a href="#" class="btn btn-xs btn-success" onclick="approveManualRequestPromo(' . $row['promoCmsSettingId'] . ',' . $row['playerId'] . ')" >' . lang('lang.approve') . '</a>';
                            // }
                            // }

                            // if($manually_decline_promo){
                            if ($this->permissions->checkPermissions('promocancel_list')){
                                $ret .= '<a href="#" class="btn btn-xs btn-danger" data-toggle="modal" data-target="#promoCancel" onclick="viewPromoDeclineForm(' . $row['playerpromoId'] . ')">' . lang('lang.decline') . '</a>';
                            }
                            return $ret;
                            // } else if ($d == Player_promo::TRANS_STATUS_MANUAL_REQUEST_PENDING) {
                            // 	$ret = '<a href="javascript:void()" class="btn btn-xs btn-success" onclick="approveManualRequestPromo(' . $row['promoCmsSettingId'] . ',' . $row['playerId'] . ')" >' . lang('lang.approve') . '</a>';
                            // 	$ret .= '<a href="javascript:void()" class="btn btn-xs btn-danger" data-toggle="modal" data-target="#promoCancel" onclick="viewPromoDeclineForm(' . $row['playerpromoId'] . ')">' . lang('lang.decline') . '</a>';
                            // 	return $ret;
                            //} elseif ($d == Player_promo::TRANS_STATUS_DECLINED && $allow_to_delete_declined_promotion && $declined_forever_promotion) {
                            //	$ret = '<a href="#" class="btn btn-xs btn-success" data-toggle="modal" data-target="#promoDeclineForever" onclick="viewPromoDeclineForeverForm(' . $row['playerpromoId'] . ')">' . lang('Declined Forever') . '</a>';
                            //	return $ret;
                            //} elseif ($d == Player_promo::TRANS_STATUS_MANUAL_REQUEST_APPROVED_WITHOUT_RELEASE_BONUS) {
                            //active
                            //	$ret = '<a href="#" class="btn btn-xs btn-warning" data-toggle="modal" data-target="#promoExpireModal" onclick="viewPromoExpireForm(' . $row['playerpromoId'] . ')">' . lang('Set Expire') . '</a>';
                            //	if($manually_decline_promo){
                            //		$ret .= '<a href="#" class="btn btn-xs btn-danger" data-toggle="modal" data-target="#promoCancel" onclick="viewPromoDeclineForm(' . $row['playerpromoId'] . ')">' . lang('lang.decline') . '</a>';
                            //	}
                            //	return $ret;
                            //} elseif ($d == Player_promo::TRANS_STATUS_APPROVED_WITHOUT_RELEASE_BONUS) {
                            // if($allow_decline_on_approved_and_without_release){
                            //	$ret = '<a href="#" class="btn btn-xs btn-warning" data-toggle="modal" data-target="#promoExpireModal" onclick="viewPromoExpireForm(' . $row['playerpromoId'] . ')">' . lang('Set Expire') . '</a>';
                            //	if($manually_decline_promo){
                            //		$ret .= '<a href="#" class="btn btn-xs btn-danger" data-toggle="modal" data-target="#promoCancel" onclick="viewPromoDeclineForm(' . $row['playerpromoId'] . ')">' . lang('lang.decline') . '</a>';
                            //	}
                            // }
                            //	if($manaully_update_promotion_bonus){
                            //		$ret .= '<a href="#" class="btn btn-xs btn-success" onclick="releasePromo(' . $row['playerpromoId'] . ')" >' . lang('Release') . '</a>';
                            //	}
                            //	return $ret;
                            // } elseif ($d == Player_promo::TRANS_STATUS_FINISHED_WITHDRAW_CONDITION) {
                            // 	$ret = '<a href="#" class="btn btn-xs btn-warning" onclick="setFinished(' . $row['playerpromoId'] . ')">' . lang('Set Finished') . '</a>';
                            // 	return $ret;
                        } elseif ($d == Player_promo::TRANS_STATUS_APPROVED) {
                            $ret = '<a href="#" class="btn btn-xs btn-warning" onclick="setFinished(' . $row['playerpromoId'] . ')">' . lang('Set to Finished') . '</a>';
                            return $ret;
                        } else {
                            return '<i>' . lang('cms.noAvailableAction') . '</i>';
                        }

                    } else {
                        return '';
                        // $ret = '';

                        // if ($d == Player_promo::TRANS_STATUS_REQUEST) {

                        // 	return lang('pay.req');

                        // } elseif ($d == Player_promo::TRANS_STATUS_DECLINED && $allow_to_delete_declined_promotion) {

                        // 	return lang('report.p13');

                        //} elseif ($d == Player_promo::TRANS_STATUS_MANUAL_REQUEST_APPROVED_WITHOUT_RELEASE_BONUS) {

                        //	return lang('Manual request approved without release bonus.');

                        //} elseif ($d == Player_promo::TRANS_STATUS_APPROVED_WITHOUT_RELEASE_BONUS) {

                        //	return lang('Approved without release bonus.');

                        // } elseif ($d == Player_promo::TRANS_STATUS_APPROVED) {

                        // 	return lang('transaction.status.1');

                        // } else {
                        // 	return lang('lang.norecyet');
                        // }
                    }

                },
            ),
            array(
                'dt' => $i++,
                'select' => 'playerpromo.note',
                'alias' => 'note',
                'name' => lang('Note'),
                'formatter' => function($d){
                    $test = explode(' | ', $d);
                    if(count($test) > 1){
                        $result = array_unique($test);
                        $d = '';
                        foreach($result as $res){
                            $d .= $res.' | ';
                        }
                    }
                    return $d;
                }
            ),
        );

        $table = 'playerpromo';
        $joins = array(
            'promorules' => 'promorules.promorulesId = playerpromo.promorulesId',
            'player' => 'player.playerId = playerpromo.playerId',
            // 'playerlevel' => 'playerlevel.playerId = player.playerId',
            'vipsettingcashbackrule' => 'vipsettingcashbackrule.vipsettingcashbackruleId = player.levelId',
            'vipsetting' => 'vipsetting.vipSettingId = vipsettingcashbackrule.vipSettingId',
            'promocmssetting' => 'promocmssetting.promoCmsSettingId = playerpromo.promoCmsSettingId',
            'adminusers' => 'adminusers.userId = playerpromo.processedBy',
            'adminusers adminUsersRequestId' => 'adminUsersRequestId.userId = playerpromo.requestAdminId',
            'affiliates' => 'affiliates.affiliateId = player.affiliateId',
        );

        if($input['status'] == Player_promo::TRANS_STATUS_REQUEST){
            //status request's request time and prodessed on are the same
            $input['transactionDateType'] = Player_promo::TRANSACTION_DATE_TYPE_REQUEST_TIME;
        }
        if($input['transactionDateType'] == Player_promo::TRANSACTION_DATE_TYPE_REQUEST_TIME){
            if(isset($input['request_date_from'], $input['request_date_to']) && !empty($input['search_by_date']) ){
                $where[] = "playerpromo.dateApply BETWEEN ? AND ?";
                $values[] = $input['request_date_from'];
                $values[] = $input['request_date_to'];
            }

        }
        if($input['transactionDateType'] == Player_promo::TRANSACTION_DATE_TYPE_PROCESSED_TIME){
            if(isset($input['request_date_from'], $input['request_date_to']) && !empty($input['search_by_date'])){
                $where[] = "playerpromo.dateProcessed BETWEEN ? AND ?";
                $values[] = $input['request_date_from'];
                $values[] = $input['request_date_to'];
            }
        }
        if($input['status'] != $input['player_promo_status']){ //dropdown status
            if($input['player_promo_status'] != 'all'){ //normal status
                $where[] = "playerpromo.transactionStatus = ?";
                $values[] = $input['player_promo_status'];
            }
        }else{ //portlet status
            $where[] = "playerpromo.transactionStatus = ?";
            $values[] = $input['status'];
        }
        if (isset($input['username'])) {
            $where[] = "player.username = ?";
            $values[] = $input['username'];
        }
        if (isset($input['vipsettingcashbackruleId'])) {
            $where[] = "vipsettingcashbackrule.vipsettingcashbackruleId = ?";
            $values[] = $input['vipsettingcashbackruleId'];
        }
        if (isset($input['promoCmsSettingId'])) {
            $where[] = "playerpromo.promoCmsSettingId = ?";
            $values[] = $input['promoCmsSettingId'];
        }
        //if (!empty($input['processed_by'])) {
        //	$where[] = "processedBy = ?";
        //	$values[] = $input['processed_by'];
        //}

        if (isset($input['only_show_active_promotion'])) {
            $where[] = "promocmssetting.status = ?";
            $values[] = 'active';
        }

        $where[] = "player.deleted_at IS NULL";

        if($is_export){
            $this->data_tables->options['is_export']=true;
            // $this->data_tables->options['only_sql']=true;
            if(empty($csv_filename)){
                $csv_filename=$this->utils->create_csv_filename(__FUNCTION__);
            }
            $this->data_tables->options['csv_filename']=$csv_filename;
        }

        $result = $this->data_tables->get_data($request, $columns, $table, $where, $values, $joins);
        if($is_export){
            //drop result if export
            return $csv_filename;
        }
        if( ! empty($this->data_tables->last_query) ){
			$result['sqls'] = $this->data_tables->last_query;
		}

        return $result;
    }

	/**
	 * detail: get shopping item claim list
	 * @param array $request
	 * @param Boolean $is_export
	 * @return array
	 */

	public function shoppingItemClaimList($request, $is_export = false) {

		$this->load->model(array('shopper_list', 'shopping_center', 'point_transactions'));
		$this->load->library(array('permissions'));

		$readOnlyDB = $this->getReadOnlyDB();
		$this->load->library('data_tables', array("DB" => $readOnlyDB));
		$this->permissions->setPermissions();

		$i = 0;
		$input = $this->data_tables->extra_search($request);
		$controller = $this;

		$where = array();
		$values = array();

		$playerTotalPoints = 0;

		$allow_to_declined_shopper_request = $this->permissions->checkPermissions('allow_to_declined_shopper_request');
		$allow_to_approve_shopper_request = $this->permissions->checkPermissions('allow_to_approve_shopper_request');
		$columns = array(
			array(
				'select' => 'player.playerId',
				'alias' => 'playerId',
			),
			array(
				'select' => 'shopper_list.before_points',
				'alias' => 'before_points',
			),
			array(
				'select' => 'shopper_list.after_points',
				'alias' => 'after_points',
			),
			array(
				'select' => 'shopper_list.trans_id',
				'alias' => 'trans_id',
			),
			array(
				'dt' => $i++,
				'select' => 'shopper_list.status',
				'alias' => 'status',
				'name' => lang('Status'),
				'formatter' => function ($d, $row) use ($allow_to_declined_shopper_request, $allow_to_approve_shopper_request, $is_export) {

					if (!$is_export) {
						$ret = '';

						if ($d == Shopper_list::REQUEST) {
							if ($allow_to_approve_shopper_request) {
								$ret = '<a href="#" class="btn btn-xs btn-success" onclick="approveOrDeclinedShopItemClaimRequest(' . $row['playerId'] . ',' . $row['shopping_item_unique_id'] . ',' . Shopper_list::APPROVED . ')" >' . lang('Approve') . '</a>';
							}

							if ($allow_to_declined_shopper_request) {
								// $ret .= ' <a href="#" class="btn btn-xs btn-danger" onclick="approveOrDeclinedShopItemClaimRequest(' . $row['playerId'] . ',' . $row['shopping_item_unique_id'] . ',' . Shopper_list::DECLINED . ')" >' . lang('Decline') . '</a>';

								$ret .= ' <button type="button" class="btn btn-xs btn-danger" data-toggle="modal" data-target="#declineShoppingRequestModal" onclick="showShoppinItemDeclineReasonModal(' . $row['playerId'] . ',' . $row['shopping_item_unique_id'] . ',' . Shopper_list::DECLINED . ')">' . lang('Decline') . '</button> ';
							}
							return $ret;

						} else {
							return '<i>' . lang('cms.noAvailableAction') . '</i>';
						}

					}else{
						switch ($d) {
							case Shopper_list::REQUEST:
								$d = lang('Requested');
								break;

							case Shopper_list::APPROVED:
								$d = lang('Approved');
								break;

							case Shopper_list::DECLINED:
								$d = lang('Declined');
								break;

							default:
								$d = lang('Pending');
								break;
						}
						return $d;
					}

				},
			),

			array(
				'dt' => $i++,//1
				'select' => '( CASE WHEN affiliates.username IS NULL THEN player.username ELSE CONCAT(player.username, \' (\', affiliates.username,  \')\' ) END )',
				'alias' => 'username',
				'name' => lang('player.01'),
				'formatter' => function ($d, $row) use ($is_export) {

					if (!$is_export) {
						return '<a href="/player_management/userInformation/' . $row['playerId'] . '" data-toggle="tooltip" data-original-title="' . lang("cms.checkPlayerDetails") . '" data-placement="right">' . $d . '</a>';
					} else {
						return $d;
					}

				},
			)
		);


		if($this->utils->getConfig('enable_col_application_time_shopping_request_list')){
			$columns[] = array(
				'dt' => $i++,
				'alias' => 'application_datetime',
				'select' => 'shopper_list.application_datetime',
				'name' => lang('Application Time'),
				'formatter' => 'dateTimeFormatter',
			);
		}

		$columns[]=array(
			'dt' => $i++,
			'select' => 'CONCAT(vipsetting.groupName, \'|\', vipsettingcashbackrule.vipLevelName )',
			'alias' => 'memberLevel',
			'name' => lang('player.07'),
			'formatter' => function ($d, $row) {
				$d = (explode("|",$d));
				if(count($d) > 1){
					$d = lang($d[0]).' - '.lang($d[1]);
				}
				return $d;
			},
		);
		$columns[]=array(
			'dt' => $i++,//3
			'select' => 'shopping_center.title',
			'alias' => 'itemTitle',
			'name' => lang('Item Title'),
			'formatter' => function ($d, $row) use ($is_export) {
				return ucwords($d);
			},
		);

		$columns[]=array(
			'dt' => $i++,//4
			'alias' => 'playerTotalPoints',
			'name' => lang('Player Available Points'),
			'formatter' => function ($d, $row) use ($controller, $is_export) {
				// $playerPoints = $controller->point_transactions->getPlayerTotalPoints($row['playerId']);
				// $getPlayerTotalDeductedPoints = $controller->point_transactions->getPlayerTotalDeductedPoints($row['playerId']);
				// $playerTotalPoints = !empty($playerPoints) ? array_sum(array_column($playerPoints, 'points')) : 0;
				// $playerDeductedPoints = !empty($getPlayerTotalDeductedPoints) ? $getPlayerTotalDeductedPoints['points'] : 0;
				// $total = (int) $playerTotalPoints - (int) $playerDeductedPoints;
				$amount = '-';
				switch ($row['status']) {
					case Shopper_list::REQUEST:
						$total = (float)$controller->point_transactions->getPlayerAvailablePoints($row['playerId']);
						$amount = $total;
						break;

					case Shopper_list::APPROVED:
						if (!empty($row['before_points'])) {
							$amount = $row['before_points'];
						}
						break;

					case Shopper_list::DECLINED:
						$amount = "-";
						break;

					default:
					$amount = "-";
						break;
				}

				if($is_export){
					return $amount;
				}else{
					return "$amount";
				}

			},
		);

		$columns[]=array(
			'dt' => $i++,//4
			'select' => 'shopping_center.requirements',
			'alias' => 'shoppingItemRequiredPoints',
			'name' => lang('Required Points'),
			'formatter' => function ($d) use ($is_export) {
				return json_decode($d, true)['required_points'];
			},
		);

		$columns[]=array(
			'dt' => $i++,//6
			// 'select' => 'shopping_center.requirements',
			'alias' => 'totalPointsAfterConverted',
			'name' => lang('Total Points After Converted'),
			'formatter' => function ($d, $row) use ($controller) {
				// $playerPoints = $controller->point_transactions->getPlayerTotalPoints($row['playerId']);
				// $getPlayerTotalDeductedPoints = $controller->point_transactions->getPlayerTotalDeductedPoints($row['playerId']);
				// $playerTotalPoints = !empty($playerPoints) ? array_sum(array_column($playerPoints, 'points')) : 0;
				// $playerDeductedPoints = !empty($getPlayerTotalDeductedPoints) ? $getPlayerTotalDeductedPoints['points'] : 0;
				// $total = (int) $playerTotalPoints - (int) $playerDeductedPoints;
				switch ($row['status']) {
					case Shopper_list::REQUEST:
						$total = (float)$controller->point_transactions->getPlayerAvailablePoints($row['playerId']);
						return $total - json_decode($row['shoppingItemRequiredPoints'], true)['required_points'];
						break;

					case Shopper_list::APPROVED:
						if(!empty($row['after_points'])){
							return $row['after_points'];
						} else {
							return "-";
						}

						break;

					case Shopper_list::DECLINED:
						return "-";
						break;

					default:
						return "-";
						break;
				}

			},
		);

		$columns[]=array(
			'dt' => $i++,//7
			'select' => 'shopping_center.how_many_available',
			'alias' => 'how_many_available',
			'name' => lang('How Many Available'),
			'formatter' => function ($d, $row) use ($readOnlyDB) {
				$query = $readOnlyDB->query("SELECT count(shopper_list.shopping_item_id) as usedItem FROM shopper_list WHERE shopper_list.status = ? AND shopper_list.shopping_item_id = ?", array(
					Shopper_list::APPROVED,
					$row['shopping_item_id'],
				));
				$row = $query->row_array();
				$availableItemCnt = (float) $d - (float) $row['usedItem'];
				return $availableItemCnt ?: 0;
			},
		);



		$columns[]=array(
			'dt' => $i++,//8
			'select' => 'shopper_list.notes',
			'alias' => 'note',
			'name' => lang('Note'),
			'formatter' => function ($d, $row) {
				return $d ?: lang('N/A');
			},
		);

		$columns[]=array(
			'dt' => $i++,//9
			'alias' => 'transactionHistory',
			'name' => lang('Transaction History'),
			'formatter' => function ($d, $row) use ($controller, $is_export) {
				$ret = ' <button type="button" class="btn btn-xs btn-info" data-toggle="modal" data-target="#shoppingTransactionHistoryModal" onclick="showShoppingTransactionHistoryModal(' . $row['playerId'] . ')">' . lang('History') . '</button> ';
				if($is_export){
					$ret = '';
				}
				return $ret;
			},
		);

		$columns[]=array(
			'alias' => 'shopping_item_id',
			'select' => 'shopper_list.shopping_item_id',
		);

		$columns[]=array(
			'alias' => 'shopping_item_unique_id',
			'select' => 'shopper_list.id',
		);



		$table = 'shopper_list';
		$joins = array(
			'player' => 'player.playerId = shopper_list.player_id',
			'vipsettingcashbackrule' => 'vipsettingcashbackrule.vipsettingcashbackruleId = player.levelId',
			'vipsetting' => 'vipsetting.vipSettingId = vipsettingcashbackrule.vipSettingId',
			'shopping_center' => 'shopping_center.id = shopper_list.shopping_item_id',
			'adminusers' => 'adminusers.userId = shopper_list.processed_by',
			'affiliates' => 'affiliates.affiliateId = player.affiliateId',
		);

		// $where[] = 'playerpromo.promoStatus = ?';
		// $values[] = Shopper_list::PROMO_STATUS_ACTIVE;

		if (isset($input['status'])) {
			if ($input['status'] == Shopper_list::REQUEST) {
				$where[] = "shopper_list.status in (" . Shopper_list::REQUEST . ")";
			} else {
				$where[] = "shopper_list.status = ?";
				$values[] = $input['status'];
			}
		}

		if ($input['status'] == Shopper_list::REQUEST) {
			if (isset($input['request_date_from'], $input['request_date_to'])) {
				$where[] = "shopper_list.application_datetime BETWEEN ? AND ?";
				$values[] = $input['request_date_from'];
				$values[] = $input['request_date_to'];
			}
		} else {
			if (isset($input['request_date_from'], $input['request_date_to'])) {
				$where[] = "shopper_list.processed_datetime BETWEEN ? AND ?";
				$values[] = $input['request_date_from'];
				$values[] = $input['request_date_to'];
			}
		}

		if (isset($input['username'])) {
			$where[] = "player.username = ?";
			$values[] = $input['username'];
		}
		if (isset($input['vipsettingcashbackruleId'])) {
			$where[] = "vipsettingcashbackrule.vipsettingcashbackruleId = ?";
			$values[] = $input['vipsettingcashbackruleId'];
		}
		if (isset($input['shoppingItemId'])) {
			$where[] = "shopping_center.id = ?";
			$values[] = $input['shoppingItemId'];
		}
		if (!empty($input['processed_by'])) {
			$where[] = "processed_by = ?";
			$values[] = $input['processed_by'];
		}
		// var_dump($request, $columns, $table, $where, $values, $joins);
		$result = $this->data_tables->get_data($request, $columns, $table, $where, $values, $joins);
		// var_dump($result);exit();
		return $result;
	}

	/**
	 * detail: get friend referrals
	 * @param array $request
	 * @param Boolean $is_export
	 * @return array
	 */
	public function friendReferral($request, $is_export = false) {

		$this->load->library('data_tables');
		$this->load->model(array('player_model'));
		$_player_model = &$this->player_model;

		# START DEFINE COLUMNS #################################################################################################################################################
		$i = 0;
		$columns = array(
			array(
				'alias' => 'inviterPlayerId',
				'select' => 'playerfriendreferral.playerId',
			),
			array(
				'alias' => 'invitedPlayerId',
				'select' => 'playerfriendreferral.invitedPlayerId',
			),
			array(
				'dt' => $i++,
				'alias' => 'invited',
				'select' => 'referred.username',
				'name' => lang('player.fr03'),
				'formatter' => function ($d, $row) use ($is_export) {
					if (!$is_export) {
						return '<a href="/player_management/userInformation/' . $row['invitedPlayerId'] . '" target="_blank">' . $d . '</a>';
					} else {
						return $d;
					}

				},
			),
			array(
				'dt' => $i++,
				'alias' => 'inviterCode',
				'select' => 'referred.invitationCode',
				'name' => lang('player.fr05'),
				'formatter' => 'defaultFormatter',
			),
			array(
				'dt' => $i++,
				'alias' => 'firstDepositTime',
				'select' => 'referred.playerId',
				'name' => lang('player.fr06'),
				'formatter' => function ($d) use ($is_export) {

					$first_deposit_time = $this->player_model->getPlayerFirstDepositDate($d);

					if(!empty($first_deposit_time)){
						return $first_deposit_time;
					}
					else{
						if (!$is_export) {
							return '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
						}else{
							return lang('lang.norecyet');
						}
					}
				},
			),
			array(
				'dt' => $i++,
				'alias' => 'firstDeposit',
				'select' => 'referred.first_deposit',
				'name' => lang('player.fr07'),
				'formatter' => 'defaultFormatter',
			),

			array( // Player Total Deposit
				'dt' => $i++,
				'alias' => 'firstDeposit',
				'select' => 'referred.first_deposit',
				'name' => lang('Player Total Deposit'),
				'formatter' => function ($d, $row) use ($is_export, &$_player_model) {

					// player_report_hourly.total_deposit
					// Player_model::getBetsAndDepositByDate()
					$playerId = $row['invitedPlayerId'];
					$player = $this->player_model->getPlayerById($playerId);
					$fromDate = $player->createdOn;
					$toDate = $this->getNowForMysql();
					list($bets, $deposit) = $_player_model->getBetsAndDepositByDate($playerId, $fromDate, $toDate);
					return $this->data_tables->currencyFormatter($deposit);
				}
			),
			array( // Player Total Bet
				'dt' => $i++,
				'alias' => 'firstDeposit',
				'select' => 'referred.first_deposit',
				'name' => lang('Player Total Bet'),
				'formatter' => function ($d, $row) use ($is_export, &$_player_model) {

					// player_report_hourly.total_bet
					// Player_model::getBetsAndDepositByDate($playerId, $fromDate, $toDate)
					$playerId = $row['invitedPlayerId'];
					$player = $this->player_model->getPlayerById($playerId);
					$fromDate = $player->createdOn;
					$toDate = $this->getNowForMysql();
					list($bets, $deposit) = $_player_model->getBetsAndDepositByDate($playerId, $fromDate, $toDate);
					return $this->data_tables->currencyFormatter($bets);
				}
			),

			array(
				'dt' => $i++,
				'alias' => 'inviter',
				'select' => 'referrer.username',
				'name' => lang('player.fr04'),
				'formatter' => function ($d, $row) use ($is_export) {

					if (!$is_export) {
						return '<a href="/player_management/userInformation/' . $row['inviterPlayerId'] . '" target="_blank">' . $d . '</a>';
					} else {
						return $d;
					}

				},
			),

			array( // Referrer Total Deposit
				'dt' => $i++,
				'alias' => 'inviter',
				'select' => 'referrer.username',
				'name' => lang('Referrer Total Deposit'),
				'formatter' => function ($d, $row) use ($is_export, &$_player_model) {

					$playerId = $row['inviterPlayerId'];
					$player = $this->player_model->getPlayerById($playerId);
					$fromDate = $player->createdOn; // same as `referred.createdOn`
					$toDate = $this->getNowForMysql();
					list($bets, $deposit) = $_player_model->getBetsAndDepositByDate($playerId, $fromDate, $toDate);
					return $this->data_tables->currencyFormatter($deposit);
				}
			),
			array( // Referrer Total Bet
				'dt' => $i++,
				'alias' => 'inviter',
				'select' => 'referrer.username',
				'name' => lang('Referrer Total Bet'),
				'formatter' => function ($d, $row) use ($is_export, &$_player_model) {

					$playerId = $row['inviterPlayerId'];
					$player = $this->player_model->getPlayerById($playerId);
					$fromDate = $player->createdOn; // same as `referred.createdOn`
					$toDate = $this->getNowForMysql();
					list($bets, $deposit) = $_player_model->getBetsAndDepositByDate($playerId, $fromDate, $toDate);
					return $this->data_tables->currencyFormatter($bets);
				}
			),

			array(
				'dt' => $i++,
				'alias' => 'createdOn',
				'select' => 'referred.createdOn',
				'name' => lang('player.fr02'),
				'formatter' => 'defaultFormatter',
			),
			array(
				'dt' => $i++,
				'alias' => 'bonus',
				'select' => 'trans.amount',
				'name' => lang('lang.bonus'),
				'formatter' => function ($d, $row) {
						return $d ? $d : lang('lang.norecyet');
				},
			),
			// array(
			// 	'dt' => $i++,
			// 	'alias' => 'transactionId',
			// 	'select' => 'playerfriendreferral.transactionId',
			// 	'name' => lang('pay.transact'),
			// 	'formatter' => function ($d, $row) use ($is_export) {
			// 		if (!$is_export) {
			// 			return $d ? '<a href="/payment_management/viewtransactionList?transaction_id=' . $d . '" target="_blank">' . $d . '</a>' : '<i>' . lang('lang.norecyet') . '</i>';
			// 		} else {
			// 			return $d ? $d : lang('lang.norecyet');
			// 		}

			// 	},

			// ),
			array(
				'dt' => $i++,
				'alias' => 'status',
				'select' => 'playerfriendreferral.status',
				'name' => lang('player.ut09'),
				'formatter' => function ($d, $row) use ($is_export) {
					switch ($d) {
					    case 1:
					        return lang('player.tl08');
					        break;
					    case 3:
					        return lang('lang.paid');
					        break;
					    case 4:
					        return lang('lang.decline');
					        break;
					}
				},
			),
			array(
				'dt' => (!$is_export) ? $i++ : NULL,
				'alias' => 'referralId',
				'select' => 'playerfriendreferral.referralId',
				'name' => lang('lang.action'),
				'formatter' => function ($d, $row) use ($is_export) {
					if (!$is_export) {
						return  $row['status'] == 1 ? '<a href="javascript:void(0)" class="btn btn-primary btn-xs decline_referral" data-id="'.$d.'" data-invited="'.$row['invitedPlayerId'].'" data-inviter="'.$row['inviterPlayerId'].'" data-singleton="true" data-toggle="confirmation" data-popout="true" data-btn-ok-label="'.lang('cms.yes').'" data-btn-ok-class="btn-success btn-xs" data-btn-cancel-label="'.lang('cms.no').'" data-btn-cancel-class="btn-danger btn-xs" data-title="'.lang('sys.sure').'" ><i class="fa fa-ban">'.lang('lang.decline').'</i></a>' : '<i>' . lang('lang.norecyet') . '</i>';
					} else {
						return '';
					}
				},
			),
		);
		# END DEFINE COLUMNS #################################################################################################################################################

		$table = 'playerfriendreferral';
		$joins = array(
			'player referrer' => 'referrer.playerId = playerfriendreferral.playerId',
			'player referred' => 'referred.playerId = playerfriendreferral.invitedPlayerId',
			'transactions trans' => 'trans.id = playerfriendreferral.transactionId'
		);

		# START PROCESS SEARCH FORM #################################################################################################################################################
		$where = array();
		$values = array();

		$input = $this->data_tables->extra_search($request);

		if (isset($input['enabled_date'])) {
			if (isset($input['date_from'], $input['date_to'])) {
				$where[] = "referred.createdOn BETWEEN ? AND ?";
				$values[] = $input['date_from'];
				$values[] = $input['date_to'];
			}
		}

		if (isset($input['inviter'])) {
			$where[] = "referrer.username = ?";
			$values[] = $input['inviter'];
		}

		if (isset($input['invited'])) {
			$where[] = "referred.username = ?";
			$values[] = $input['invited'];
		}

		# END PROCESS SEARCH FORM #################################################################################################################################################
		$this->config->set_item('debug_data_table_sql', true);

		if($is_export){
            $this->data_tables->options['is_export']=true;
            if(empty($csv_filename)){
                $csv_filename=$this->utils->create_csv_filename(__FUNCTION__);
            }
            $this->data_tables->options['csv_filename']=$csv_filename;
		}

		$result = $this->data_tables->get_data($request, $columns, $table, $where, $values, $joins);

		if($is_export){
		    //drop result if export
			return $csv_filename;
		}
		// $result['sqls'] = $this->data_tables->last_query;
		return $result;
	} // EOF friendReferral


	/**
	 * detail: get friend referrals
	 * @param array $request
	 * @param Boolean $is_export
	 * @return array
	 */
	public function ipTagList($request, $is_export = false) {

		$this->load->library('data_tables');
		$this->load->model(array('player_model'));

		# START DEFINE COLUMNS #################################################################################################################################################
		$i = 0;
		$columns = array(
			array(
				'alias' => 'adminusername',
				'select' => 'adminusers.username',
			),

			array(
				'dt' => (!$is_export) ? $i++ : NULL,
				'alias' => 'checkbox',
				'select' => 'ip_tag_list.id',
				'name' => lang('Select All'),
				'formatter' => function ($d, $row) use ($is_export) {
					return $d; // html by $.DataTable().columnDefs of js.
				},
			),

			array(
				'dt' => $i++,
				'alias' => 'ip_tag_list',
				'select' => 'ip_tag_list.name',
				'name' => lang('player.it02'),
				'formatter' => 'defaultFormatter',
			),
			array(
				'dt' => $i++,
				'alias' => 'ip',
				'select' => 'ip_tag_list.ip',
				'name' => lang('player.it03'),
				'formatter' => 'defaultFormatter',
			),
			array(
				'dt' => $i++,
				'alias' => 'description',
				'select' => 'ip_tag_list.description',
				'name' => lang('player.it04'),
				'formatter' => 'defaultFormatter', // @todo
			),
			array(
				'dt' => $i++,
				'alias' => 'color',
				'select' => 'ip_tag_list.color',
				'name' => lang('player.it06'),
				'formatter' => function ($d, $row) use ($is_export) {
					$return = $d; // render in columnDefs
					return $return;
				},
			),
			array(
				'dt' => $i++,
				'alias' => 'created_by',
				'select' => 'ip_tag_list.created_by',
				'name' => lang('sys.createdby'),
				'formatter' => function ($d, $row) use ($is_export) {
					$return = lang('N/A');
					if( ! empty($row['adminusername']) ){
						$return = $row['adminusername'];
					}
					return $return;
				}
			),
			array(
				'dt' => (!$is_export) ? $i++ : NULL,
				'alias' => 'action',
				'select' => 'ip_tag_list.id',
				'name' => lang('player.it05'),
				'formatter' => function ($d, $row) use ($is_export) {
					return $d; // render in columnDefs
				},
			),
		);
		# END DEFINE COLUMNS #################################################################################################################################################

		$table = 'ip_tag_list';
		$joins = array();
		$joins['adminusers'] = 'ip_tag_list.created_by = adminusers.userId';

		# START PROCESS SEARCH FORM #################################################################################################################################################
		$where = array();
		$values = array();

		$input = $this->data_tables->extra_search($request);

		if ( ! empty($input['name']) ) {
			$where[] = "name LIKE ?";
			$values[] = '%'. $input['name']. '%';
		}

		if ( ! empty($input['color']) ) {
			$where[] = "color LIKE ?";
			$values[] = '%'. $input['color']. '%';
		}

		if ( ! empty($input['description']) ) {
			$where[] = "description LIKE ?";
			$values[] = '%'. $input['description']. '%';
		}
		if ( ! empty($input['ip']) ) {
			$where[] = "ip LIKE ?";
			$values[] = '%'. $input['ip']. '%';
		}

		# END PROCESS SEARCH FORM #################################################################################################################################################
		// $this->config->set_item('debug_data_table_sql', true);

		if($is_export){
            $this->data_tables->options['is_export']=true;
            if(empty($csv_filename)){
                $csv_filename=$this->utils->create_csv_filename(__FUNCTION__);
            }
            $this->data_tables->options['csv_filename']=$csv_filename;
		}

		$result = $this->data_tables->get_data($request, $columns, $table, $where, $values, $joins);

		if($is_export){
		    //drop result if export
			return $csv_filename;
		}

		if( ! empty($this->data_tables->last_query) ){
			$result['sqls'] = $this->data_tables->last_query;
		}

		return $result;
	} // EOF ipTagList

	private function _getHtml4InitialAmountCol($initialAmount, $is_export = false){

		$_initialAmount = json_decode($initialAmount, true);
		$totalDepositRowHTML = $this->_getHtml4InitialAmountCol_TotalDepositRow($_initialAmount, $is_export);
		$isEmptyAfterParsed4CB = true; // common betting
		$totalBetRowHTML = $this->_getHtml4InitialAmountCol_TotalBetRow($_initialAmount, $isEmptyAfterParsed4CB, $is_export);
		$isEmptyAfterParsed4SB = true;// separated betting
		$totalBetAsEachGameRowHTML = $this->_getHtml4InitialAmountCol_TotalBetAsEachGameRow($_initialAmount, $isEmptyAfterParsed4SB, $is_export);
		$vip_setting_form_ver = $this->utils->getConfig('vip_setting_form_ver');
		$betingRowHTML = '';
		if( $isEmptyAfterParsed4CB && $isEmptyAfterParsed4SB){
			if($vip_setting_form_ver == 2){
				$betingRowHTML = $totalBetAsEachGameRowHTML;
			}else{
				$betingRowHTML = $totalBetRowHTML;
			}
		}
		if(!$isEmptyAfterParsed4CB){
			$betingRowHTML .= $totalBetRowHTML;
		}
		if(!$isEmptyAfterParsed4SB){
			$betingRowHTML .= $totalBetAsEachGameRowHTML;
		}

		$totalWinRowHTML = $this->_getHtml4InitialAmountCol_TotalWinRow($_initialAmount, $is_export);
		$totalLossRowHTML = $this->_getHtml4InitialAmountCol_TotalLossRow($_initialAmount, $is_export);

		// <textarea class="hide" readonly="readonly" rows="2" style="width: 100%;">%s</textarea> <!-- # 6.2 Initial Amount -->
		$html = <<<EOF
$totalDepositRowHTML
$betingRowHTML
$totalWinRowHTML
$totalLossRowHTML
EOF;
		return $html;
	} // EOF _getHtml4InitialAmountCol

	/**
	 * Apply Initial Amount and Get the .total-deposit-row HTML
	 *
	 * @param object $initialAmount The initialAmount element of remark.
	 * @param callable $getAmountScript( object $initialAmount)
	 * The script need get the amount to be applied in the HTML.
	 * If return NULL, then return the $html4Empty HTML for hide.
	 * others, will return the $html HTML for display.
	 * @return string The HTML string
	 */
	private function _getHtml4InitialAmountCol_TotalAmountRow($initialAmount = null, callable $getAmountScript = null, $html = null, $html4Empty = null, $is_export = false){
		$returnHTML = '';

		// @todo $initialAmount to $depositAmount
		if( is_null($html)){
			$lang_total_deposit = lang('Total Deposit');
			$html = <<<EOF
<div class="row total-deposit-row">
	<div class="col-md-7">$lang_total_deposit</div>
	<div class="col-md-5">%s</div>
</div>
EOF;
			if($is_export){
				$html = <<<EOF
$lang_total_deposit %s
EOF;
			}

		}

		if( is_null($html4Empty)){ // for default
			$html4Empty = <<<EOF
<div class="row hide total-deposit-row"></div>
EOF;
			if($is_export){
				$html4Empty = '';
			}
		}



		$theAmount = $getAmountScript( $initialAmount );
		if( is_null($theAmount) ){
			$returnHTML = $html4Empty;
		}else{
			$returnHTML = sprintf($html, $theAmount);
		}
		// if( isset($initialAmount['deposit']) ){
		// 	$depositAmount = $initialAmount['deposit'];
		// 	$returnHTML = sprintf($html, $depositAmount);
		// }else{
		// 	$returnHTML = $html4Empty;
		// }
		return $returnHTML;
	}// EOF _getHtml4InitialAmountCol_TotalAmountRow
	private function _getHtml4InitialAmountCol_TotalDepositRow($initialAmount, $is_export = false){

		$lang_total_deposit = lang('Total Deposit');
		$html = <<<EOF
<div class="row total-deposit-row">
	<div class="col-md-7">$lang_total_deposit</div>
	<div class="col-md-5">%s</div>
</div>
EOF;

		$html4Empty = <<<EOF
<div class="row hide total-deposit-row"></div>
EOF;

		if($is_export){
			$html = <<<EOF
$lang_total_deposit %s
EOF;
			$html4Empty = '';
		}

		return $this->_getHtml4InitialAmountCol_TotalAmountRow($initialAmount, function($_initialAmount){
			$returnAmount = null;
			if( isset($_initialAmount['deposit']) ){
				return $_initialAmount['deposit'];
			}
			return $returnAmount;
		}, $html, $html4Empty, $is_export);
	} // EOF _getHtml4InitialAmountCol_TotalDepositRow
	private function _getHtml4InitialAmountCol_TotalLossRow($initialAmount, $is_export = false){

		$lang_total_loss = lang('Total Loss');

		$html = <<<EOF
<div class="row total-loss-row">
	<div class="col-md-7">$lang_total_loss</div>
	<div class="col-md-5">%s</div>
</div>
EOF;

		$html4Empty = <<<EOF
<div class="row hide total-loss-row"></div>
EOF;

		if($is_export){
			$html = <<<EOF
$lang_total_loss %s
EOF;
			$html4Empty = '';
		}

		return $this->_getHtml4InitialAmountCol_TotalAmountRow($initialAmount, function($_initialAmount){
			$returnAmount = null;
			if( isset($_initialAmount['total_loss']) ){
				return $_initialAmount['total_loss'];
			}
			return $returnAmount;
		}, $html, $html4Empty, $is_export);

	} // EOF _getHtml4InitialAmountCol_TotalLossRow

	private function _getHtml4InitialAmountCol_TotalWinRow($initialAmount, $is_export = false){

		$lang_total_win = lang('Total Win');

		$html = <<<EOF
<div class="row total-win-row">
	<div class="col-md-7">$lang_total_win</div>
	<div class="col-md-5">%s</div>
</div>
EOF;

		$html4Empty = <<<EOF
<div class="row hide total-win-row"></div>
EOF;

		if($is_export){
			$html = <<<EOF
$lang_total_win %s
EOF;
			$html4Empty = '';
		}

		return $this->_getHtml4InitialAmountCol_TotalAmountRow($initialAmount, function($_initialAmount){
			$returnAmount = null;
			if( isset($_initialAmount['total_win']) ){
				return $_initialAmount['total_win'];
			}
			return $returnAmount;
		}, $html, $html4Empty, $is_export);

	} // EOF _getHtml4InitialAmountCol_TotalWinRow


	private function _getHtml4InitialAmountCol_TotalBetRow($initialAmount, &$isEmptyAfterParsed, $is_export = false){

		$lang_total_bet = lang('Total Bet');

		$html = <<<EOF
<div class="row total-bet-row">
	<div class="col-md-7">$lang_total_bet</div>
	<div class="col-md-5">%s</div>
</div>
EOF;

		$html4Empty = <<<EOF
<div class="row hide total-bet-row"></div>
EOF;


		if($is_export){
			$html = <<<EOF
$lang_total_bet %s
EOF;
			$html4Empty = '';
		}

		return $this->_getHtml4InitialAmountCol_TotalAmountRow($initialAmount, function($_initialAmount) use ( &$isEmptyAfterParsed ){
			$returnAmount = null;
			$isEmptyAfterParsed = true;
			if( isset($_initialAmount['total_bet'])
				&&  empty($_initialAmount['separated_bet'])
			){
				if( ! empty($_initialAmount['total_bet']) ){
					$isEmptyAfterParsed = false;
				}
				return $_initialAmount['total_bet'];
			}
			return $returnAmount;
		}, $html, $html4Empty, $is_export);

	}// EOF _getHtml4InitialAmountCol_TotalBetRow

	public function parseSeparated_betOfInitialAmount($initialAmount){
		$this->load->model(array('game_type_model', 'external_system'));

		$return_separated_bet_list = [];
		$separated_bet = $initialAmount['separated_bet'];
		if( ! empty($separated_bet) ){
			foreach($separated_bet as $game_id_key => $amount){
				$the_separated_bet = [];
				if( strpos($game_id_key, 'game_type_id_') !== false ){
					$typeKeyword = 'game_type';

				} else if( strpos($game_id_key, 'game_platform_id_') !== false ){
					$typeKeyword = 'game_platform';
				}
				$the_separated_bet['type'] = $typeKeyword;
				$the_separated_bet['game_id'] = str_replace( $typeKeyword. '_id_', '', $game_id_key);
				if($typeKeyword == 'game_platform'){
					$gamePlatformId = $the_separated_bet['game_id'];
					$gamePlatformName = $this->external_system->getNameById($gamePlatformId);
					// $gamePlatformName = $this->external_system->getSystemName($gamePlatformId);
					$the_separated_bet['name'] = $gamePlatformName;
				}else if($typeKeyword == 'game_type'){
					$gameTypeArray = (array)$this->game_type_model->getGameTypeById($the_separated_bet['game_id']);
					$gamePlatformId = $gameTypeArray['game_platform_id'];
					$gamePlatformName = $this->external_system->getNameById($gamePlatformId);
					// $gamePlatformName = $this->external_system->getSystemName($gameTypeArray['game_platform_id']);
					$the_separated_bet['name'] = $gamePlatformName. ' => '. lang($gameTypeArray['game_type_lang']);
				}
				$the_separated_bet['amount'] = $amount;
				$return_separated_bet_list[] = $the_separated_bet;
			}
		}
		return $return_separated_bet_list;
	} // EOF parseSeparated_betOfInitialAmount

	private function _getHtml4InitialAmountCol_TotalBetAsEachGameRow($initialAmount, &$isEmptyAfterParsed, $is_export = false){
		$separated_bet = null;
		$separated_bet_list = [];
$this->utils->debug_log('6427.initialAmount', $initialAmount);
		// $_initialAmount = json_decode($initialAmount, true);
		if( ! empty($initialAmount['separated_bet']) ){
			$separated_bet = $initialAmount['separated_bet'];
			$separated_bet_list = $this->parseSeparated_betOfInitialAmount($initialAmount);
		}
$this->utils->debug_log('6432.separated_bet_list', $separated_bet_list);

		$lang_total_bet_as_each_game = lang('Total Bet as each Game');

		$html = <<<EOF
<div class="row total-bet-as-each-game-row">
	<div class="col-md-12">$lang_total_bet_as_each_game</div>
	%s
</div>
EOF;
$lang_na = lang('N/A');
$html4Empty = <<<EOF
<div class="row total-bet-as-each-game-row">
	<div class="col-md-7">$lang_total_bet_as_each_game</div>
	<div class="col-md-5">$lang_na</div>
</div>
EOF;

		$html4eachGameRow = <<<EOF
<div class="row each-game-row">
	<div class="col-md-7">%s</div>
	<div class="col-md-5">%s</div>
</div>
EOF;

		$eachGameRowHTML = '';
		if( ! empty($separated_bet_list) ){
			$isEmptyAfterParsed = false;
			foreach($separated_bet_list as $aBetting){

				// $aBetting['type']
				// $aBetting['game_id']
				// $aBetting['amount']
				// $aBetting['game_type_id_42'] = Amount
				// parse the many bettings by game Platform Or Type
				$eachGameRowHTML .= sprintf($html4eachGameRow, $aBetting['name'], $aBetting['amount']);
			}
			$returnHTML = sprintf($html, $eachGameRowHTML);
		}else{
			$isEmptyAfterParsed = true;
			$returnHTML = $html4Empty;
		}
		return $returnHTML;
	} // EOF _getHtml4InitialAmountCol_TotalBetAsEachGameRow


	/**
	 * detail: get transfer request
	 * @param array $request
	 * @param Boolean $is_export
	 * @return array
	 */
	public function transferRequest($playerId = null, $request, $permissions, $is_export = false) {

		$readOnlyDB = $this->getReadOnlyDB();

		$this->load->library('data_tables', array("DB" => $readOnlyDB));

		$this->load->model('wallet_model');
		$this->utils->loadAnyGameApiObject();
		$abstractApi=$this->utils->loadAnyGameApiObject();
		$walletMap = $this->utils->getGameSystemMap();

		$i = 0;
		$input = $this->data_tables->extra_search($request);

		$where = array();
		$values = array();

		$hideAction = false;
		if((isset($permissions['hide_action']) && $permissions['hide_action']==true) || $is_export){
			$hideAction = true;
		}

		$columns = array(
			array(
				'select' => 'transfer_request.id',
				'alias' => 'id',
			),
			array(
				'select' => 'transfer_request.flag',
				'alias' => 'flag',
			),
			array(
				'dt' => ($hideAction) ? NULL : $i++,
				'select' => 'transfer_request.id',
				'alias' => 'action',
				'name' => lang('Action'),
				'formatter' => function ($d, $row) use($is_export, $permissions) {
					if(!$is_export){
						$str='';
						$str.=" <input type='button' class='btn btn-success btn-xs m-b-5' onclick='queryStatus(" . $d . ")' value='".lang('Query Status')."'>";
						if($permissions['make_up_transfer_record']){
							$str.=" <input type='button' class='btn btn-danger btn-xs m-b-5' onclick='autoFixTransfer(" . $d . ")' value='".lang('Auto Fix')."'>";
						}
						return $str;
					}
				},
			),
			array(
				'dt' => $i++,
				'select' => 'transfer_request.secure_id',
				'name' => lang('ID'),
				'alias' => 'secure_id',
			),
			array(
				'select' => 'transfer_request.player_id',
				'alias' => 'player_id',
			),
			array(
				'dt' => $i++,
				'select' => 'player.username',
				'name' => lang('Player Username'),
				'alias' => 'player_username',
				'formatter' => function ($d, $row) use ($hideAction) {
					if($hideAction){
						return $d;
					}else{
						return "<a href='".site_url('/player_management/userInformation/'.$row['player_id'])."' target='_blank'>".$d."</a>";
					}
				},
			),
			array(
				'select' => 'transfer_request.from_wallet_type_id',
				'alias' => 'from_wallet_type',
			),
			array(
				'dt' => $i++,
				'select' => 'transfer_request.to_wallet_type_id',
				'name' => lang('Transfer'),
				'alias' => 'to_wallet_type',
				'formatter' => function ($d, $row) use ($walletMap) {

					$to = $d == 0 ? lang('Main Wallet') : @$walletMap[$d];
					$from = $row['from_wallet_type'] == 0 ? lang('Main Wallet') : @$walletMap[$row['from_wallet_type']];

					return $from.' => '.$to;
				},
			),
			array(
				'dt' => $i++,
				'select' => 'adminusers.username',
				'alias' => 'admin_username',
				// 'name' => lang('Admin Username'),
				'name' => lang('pay_mgmt.admin_username'),
				'formatter' => function ($d, $row) {
					return $d = $d ?: lang('N/A');
				},
			),
			array(
				'dt' => $i++,
				'select' => 'transfer_request.amount',
				'alias' => 'amount',
				'name' => lang('Amount'),
				'formatter' => 'currencyFormatter',
			),
			array(
				'dt' => $i++,
				'select' => 'transfer_request.status',
				'alias' => 'status',
				'name' => lang('Status'),
				'formatter' => function ($d, $row) use ($is_export) {

					if (!$is_export) {

						switch ($d) {
						case Wallet_model::STATUS_TRANSFER_REQUEST:
							return '<strong class="text-warning">' . lang('Request') . '</strong>';
						case Wallet_model::STATUS_TRANSFER_SUCCESS:
							return '<strong class="text-success">' . lang('Successful') . '</strong>';
						case Wallet_model::STATUS_TRANSFER_FAILED:
							return '<strong class="text-danger">' . lang('Failed') . '</strong>';
						default:
							return '<i class="text-muted">N/A</i>';
						}

					} else {

						switch ($d) {
						case Wallet_model::STATUS_TRANSFER_REQUEST:
							return lang('Request');
						case Wallet_model::STATUS_TRANSFER_SUCCESS:
							return lang('Successful');
						case Wallet_model::STATUS_TRANSFER_FAILED:
							return lang('Failed');
						default:
							return 'N/A';
						}

					}

				},
			),
			array(
				'dt' => $i++,
				'select' => 'transfer_request.created_at',
				'alias' => 'created_at',
				'name' => lang('Created At'),
			),
			array(
				'dt' => $i++,
				'select' => 'transfer_request.updated_at',
				'alias' => 'updated_at',
				'name' => lang('Updated At'),
			),
			array(
				'dt' => $i++,
				'select' => 'transfer_request.external_transaction_id',
				'alias' => 'external_transaction_id',
				'name' => lang('External ID'),
			),
			array(
				'dt' => $i++,
				'select' => 'transfer_request.external_system_id',
				'alias' => 'external_system_id',
				'name' => lang('API ID'),
			),
			array(
				'select' => 'transfer_request.response_result_id',
				'alias' => 'response_result_id',
			),
			array(
				'select' => 'response_results.status_text',
				'alias' => 'status_text',
			),
			array(
				'dt' => $i++,
				'select' => 'transfer_request.reason_id',
				'name' => lang('Reason'),
				'alias' => 'reason_id',
				'formatter' => function ($d, $row) use($abstractApi) {
					return $abstractApi->translateReasonId($d, $row['status_text']);
				},
			),
			array(
				'dt' => $i++,
				'select' => 'transfer_request.transfer_status',
				'name' => lang('Query Status'),
				'alias' => 'transfer_status',
				'formatter' => function ($d, $row) use($abstractApi, $is_export) {
					$str=$abstractApi->translateTransferStatus($d);
					if($is_export){
						return $str;
					}else{
						if($d==Abstract_game_api::COMMON_TRANSACTION_STATUS_DECLINED){
							return '<strong class="text-danger">'.$str.'</strong>';
						}else if($d==Abstract_game_api::COMMON_TRANSACTION_STATUS_UNKNOWN){
							return '<strong class="text-warning">'.$str.'</strong>';
						}else{
							return $str;
						}
					}
				},
			),
			array(
				'dt' => $i++,
				'select' => 'transfer_request.guess_success',
				'name' => lang('Exec Time'),
				'alias' => 'execution_time',
				'formatter' => function ($d, $row) {
					return number_format($d).' ms';
				},
			),
			array(
				'dt' => $i++,
				'select' => 'transfer_request.fix_flag',
				'name' => lang('Fix Flag'),
				'alias' => 'fix_flag',
				'formatter' => function ($d, $row) use($is_export){
					$lostStr='';
					//check lost or not
					if($row['from_wallet_type']==Wallet_model::MAIN_WALLET_ID){
						//deposit
						if($row['status']==Wallet_model::STATUS_TRANSFER_SUCCESS &&
							$row['transfer_status']==Abstract_game_api::COMMON_TRANSACTION_STATUS_DECLINED){
							$lostStr='Lost Balance on Deposit';
						}
					}else{
						//withdrawal
						if($row['status']==Wallet_model::STATUS_TRANSFER_FAILED &&
							$row['transfer_status']==Abstract_game_api::COMMON_TRANSACTION_STATUS_APPROVED){
							$lostStr='Lost Balance on Withdrawal';
						}
					}
					$str=lang('N/A');
					if(!empty($lostStr)){
						if($d==Wallet_model::DB_TRUE){
							$str=lang($lostStr.', then be fixed.');
							if(!$is_export){
								$str='<strong class="text-success">'.$str.'</strong>';
							}
						}else{
							$str=lang($lostStr.', not fix yet.');
							if(!$is_export){
								$str='<strong class="text-danger">'.$str.'</strong>';
							}
						}
					}
					return $str;
				},
			),
			array(
				'dt' => $i++,
				'select' => 'response_results.filepath',
				'alias' => 'filepath',
				'name' => lang('File'),
				'formatter' => function ($d, $row) use($is_export) {
					if($is_export){
						return '';
					}

					return empty($d) ? '' : '<a href="'.site_url('/system_management/download_response_result/'.$d).'" target="_blank">'.lang('Download').'</a>';
				},
			),
			array(
				'dt' => $i++,
				'select' => 'transfer_request.response_result_id',
				'alias' => 'response_result_id',
				'name' => lang('Resp ID'),
				'formatter' => function ($d, $row) use($is_export) {
					if($is_export){
						return $d;
					}

					return empty($d) ? '' : '<a href="'.site_url('/system_management/view_resp_result?result_id='.$d).'" target="_blank">'.$d.'</a>';
				},
			),
		);

		$table = 'transfer_request';
		$joins = array(
			'adminusers' => 'adminusers.userId = transfer_request.user_id',
			'player' => 'player.playerId = transfer_request.player_id',
			'response_results' => 'response_results.id = transfer_request.response_result_id',
		);

		if (isset($input['status']) && !empty($input['status'])) {
			$where[] = "transfer_request.status = ?";
			$values[] = $input['status'];
		}

		if (!empty($playerId) && !is_array($playerId)) {
			$where[] = "transfer_request.player_id = ?";
			$values[] = $playerId;
		}

		if (isset($input['player_username']) && !empty($input['player_username'])) {
			$where[] = "player.username LIKE ?";
			$values[] = '%' . $input['player_username'] . '%';
		}

		if (isset($input['admin_username']) && !empty($input['admin_username'])) {
			$where[] = "adminusers.username LIKE ?";
			$values[] = '%' . $input['admin_username'] . '%';
		}

		if (isset($input['secure_id']) && !empty($input['secure_id'])) {
			$where[] = "transfer_request.secure_id = ?";
			$values[] = $input['secure_id'];
		}

		if ( ! empty($input['search_reg_date'])) {
            if (isset($input['date_from'], $input['date_to'])) {
                if (isset($input['timezone']) && $input['timezone']!='8') {
                    $hours= -( intval($input['timezone'])-8 );

                    $where[] = "transfer_request.created_at BETWEEN ? AND ?";

                    $date_from_str = $input['date_from'];
                    $date_to_str = $input['date_to'];

                    $by_date_from = new DateTime($date_from_str);
                    $by_date_to = new DateTime($date_to_str);

                    $this->utils->debug_log('date_from_str', $date_from_str, 'date_to_str', $date_to_str, 'hours', $hours);

                    if($hours!=0){
                        if($hours>0){
                            $hours='+'.$hours;
                        }
                        $by_date_from->modify("".$hours." hours");
                        $by_date_to->modify("".$hours." hours");
                    }


                    $values[] = $this->utils->formatDateTimeForMysql($by_date_from);
                    $values[] = $this->utils->formatDateTimeForMysql($by_date_to);
                } else {
                    $where[] = "transfer_request.created_at >= ? AND transfer_request.created_at <= ?";
                    $values[] = $input['date_from'];
                    $values[] = $input['date_to'];
                }
            }
        }

		if (isset($input['amount_from'])) {
			$where[] = "transfer_request.amount >= ?";
			$values[] = $input['amount_from'];
		}

		if (isset($input['amount_to'])) {
			$where[] = "transfer_request.amount <= ?";
			$values[] = $input['amount_to'];
		}

		if (isset($input['by_game_platform_id'])) {
			$where[] = "transfer_request.external_system_id = ?";
			$values[] = $input['by_game_platform_id'];
		}

		if (isset($input['result_id'])) {
			$where[] = "transfer_request.response_result_id = ?";
			$values[] = $input['result_id'];
		}

		if (isset($input['agent_id'])) {
			$joins['agency_agents'] = 'agency_agents.agent_id = player.agent_id';
			$where[] = "player.agent_id = ?";
			$values[] = $input['agent_id'];
		}

		if (isset($input['suspicious_trans'])) {
			if($input['suspicious_trans']==Wallet_model::SUSPICIOUS_ALL){
				//status is success and query status is declined
				//or status is failed but query status is approved
				$where[] = "((transfer_request.status=".Wallet_model::STATUS_TRANSFER_SUCCESS." and transfer_request.transfer_status = '".
					Abstract_game_api::COMMON_TRANSACTION_STATUS_DECLINED."' ) or (transfer_request.status=".Wallet_model::STATUS_TRANSFER_FAILED.
					" and transfer_request.transfer_status ='".Abstract_game_api::COMMON_TRANSACTION_STATUS_APPROVED."'))";

			}elseif($input['suspicious_trans']==Wallet_model::SUSPICIOUS_TRANSFER_IN_ONLY){
				$where[] = "transfer_request.status = ?";
				$where[] = "transfer_request.transfer_status = ?";
				$values[]=Wallet_model::STATUS_TRANSFER_SUCCESS;
				$values[]=Abstract_game_api::COMMON_TRANSACTION_STATUS_DECLINED;

			}elseif($input['suspicious_trans']==Wallet_model::SUSPICIOUS_TRANSFER_OUT_ONLY){
				$where[] = "transfer_request.status = ?";
				$where[] = "transfer_request.transfer_status = ?";
				$values[]=Wallet_model::STATUS_TRANSFER_FAILED;
				$values[]=Abstract_game_api::COMMON_TRANSACTION_STATUS_APPROVED;

			}
		}
		if (isset($input['transfer_type'])) {
			if($input['transfer_type']==Wallet_model::TRANSFER_TYPE_IN){
				$where[] = "transfer_request.from_wallet_type_id = ".Wallet_model::MAIN_WALLET_ID;
			}elseif($input['transfer_type']==Wallet_model::TRANSFER_TYPE_OUT){
				$where[] = "transfer_request.from_wallet_type_id != ".Wallet_model::MAIN_WALLET_ID;
			}
		}

		if(isset($input['query_status'])) {
			switch ($input['query_status']) {
				case Abstract_game_api::COMMON_TRANSACTION_STATUS_APPROVED:
					$where[] = "transfer_request.transfer_status = ?";
					$values[] = "approved";
					break;
				case Abstract_game_api::COMMON_TRANSACTION_STATUS_DECLINED:
					$where[] = "transfer_request.transfer_status = ?";
					$values[] = "declined";
					break;
				case Abstract_game_api::COMMON_TRANSACTION_STATUS_PROCESSING:
					$where[] = "transfer_request.transfer_status = ?";
					$values[] = "processing";
					break;
				case Abstract_game_api::COMMON_TRANSACTION_STATUS_UNKNOWN:
					$where[] = "transfer_request.transfer_status = ?";
					$values[] = "unknown";
					break;
				default:
					# code...
					break;
			}
		}

		//query by timeout flag
		if(isset($input['only_timeout'])){
			if($input['only_timeout']=='yes'){
				$where[] = 'response_results.sync_id in ('.implode(',',Abstract_game_api::DEFAULT_GUESS_SUCCESS_CODE).')';
			}
		}

		// to see test player transfer request  on their user information page
        if(empty($playerId)){
        	$where[] = "player.deleted_at IS NULL";
        }

        if($is_export){
        	$this->data_tables->options['is_export']=true;
        	if(empty($csv_filename)){
        		$csv_filename=$this->utils->create_csv_filename(__FUNCTION__);
        	}
        	$this->data_tables->options['csv_filename']=$csv_filename;
        }

        $group_by=[];
        $having=[];
        $distinct=true;
        $external_order=[];
        $not_datatable='';
        $countOnlyField='transfer_request.id';
		$result = $this->data_tables->get_data($request, $columns, $table, $where, $values, $joins, $group_by, $having,
			$distinct, $external_order, $not_datatable, $countOnlyField);

		if($is_export){
		    //drop result if export
			return $csv_filename;
		}


		return $result;
	}

	/**
	 * add by spencer.kuo
	 */
	public function friend_referrial_monthly_earnings($request, $is_export) {
		$readOnlyDB = $this->getReadOnlyDB();

		$this->load->library('data_tables', array('DB' => $readOnlyDB));
		$this->load->model('player_earning');

		$this->data_tables->is_export = $is_export;

		$input = $this->data_tables->extra_search($request);

		//$min_amount = $this->affiliate_earnings->getMinimumMonthlyPayAmountSetting();
		$min_amount = 0;
		$i = 0;
		$columns = array(
			array(
				'select' => 'friend_referrial_monthly_earnings.player_id',
				'alias' => 'player_id',
			),
			array(
				'dt' => $i++,
				'select' => 'friend_referrial_monthly_earnings.id',
				'alias' => 'id',
				'formatter' => function ($d, $row) use ($min_amount) {
					return $row['paid_flag'] == 0 && $row['total_commission'] > 0 ? '<input type="checkbox" class="batch-selected-cb user-success" id="selected_earnings_id" onClick="selectionValidate();" value="' . $d . '">' : '';
				},
			),
			array(
				'dt' => $i++,
				'select' => 'friend_referrial_monthly_earnings.id',
				'alias' => 'id',
				'formatter' => function ($d, $row) use ($min_amount) {
					return $row['paid_flag'] == 0 && $row['total_commission'] > 0 ? '<a class="btn btn-xs btn-primary pay" href="javascript:void(0)" onclick="payOne(this)" data-earningid="' . $d . '"><i class="fa fa-paper-plane-o"></i> Transfer to wallet</a>' : '';
				},
			),
			array(
				'dt' => $i++,
				'select' => 'friend_referrial_monthly_earnings.year_month',
				'alias' => 'yearmonth',
			),
			array(
				'dt' => isset($input['player_id']) ? NULL : $i++,
				'select' => 'player.username',
				'alias' => 'player_username',
				'formatter' => function ($d, $row) {
					$url = site_url('/player_management/userInformation/' . $row['player_id']);
					return $d ? "<a href=\"{$url}\" target=\"_blank\">{$d}</a>" : ('<i class="text-muted">' . lang('N/A') . '</i>');
				},
			),
			array(
				'dt' => $i++,
				'select' => 'friend_referrial_monthly_earnings.active_players',
				'alias' => 'active_players',
			),
			array(
				'dt' => $i++,
				'select' => 'friend_referrial_monthly_earnings.total_players',
				'alias' => 'total_players',
			),
			array(
				'dt' => $i++,
				'select' => 'friend_referrial_monthly_earnings.total_bets',
				'alias' => 'total_bets',
				'formatter' => 'currencyFormatter',
			),
			array(
				'dt' => $i++,
				'select' => 'friend_referrial_monthly_earnings.total_commission',
				'alias' => 'total_commission',
				'formatter' => 'currencyFormatter',
			),
			array(
				'dt' => $i++,
				'select' => 'friend_referrial_monthly_earnings.paid_flag',
				'alias' => 'paid_flag',
				'formatter' => function ($d, $row) {
					return $d == 0 ? lang('Unpaid') : lang('Paid');
				},
			),
			array(
				'dt' => $i++,
				'alias' => 'manual_adjustment',
				'formatter' => function ($d, $row) {
					$output = '';
					if ($row['paid_flag'] == 0) {
						$output .= '<a onclick="modal(\'/player_management/friend_referrial_commision_manual_adjustment/' . $row['id'] . '/' . $row['total_commission'] . '\',\'' . lang('Manual Adjustment') . '\')" href="javascript:void(0);" data-toggle="tooltip" title="' . lang('Manual Adjustment') . '"><span class="glyphicon glyphicon-edit"></a> ';
					}
					return $output;
				},
			),
			array(
				'dt' => $i++,
				'select' => 'friend_referrial_monthly_earnings.player_id',
				'alias' => 'player_id',
			),
			array(
				'dt' => $i++,
				'select' => 'friend_referrial_monthly_earnings.total_bets',
				'alias' => 'total_bets',
			),
		);
		# END DEFINE COLUMNS #################################################################################################################################################

		$table = 'friend_referrial_monthly_earnings';
		$joins = array(
			'player' => 'player.playerId = friend_referrial_monthly_earnings.player_id',
		);

		# START PROCESS SEARCH FORM #################################################################################################################################################
		$where = array();
		$values = array();

		if (isset($input['player_id'])) {
			$where[] = 'friend_referrial_monthly_earnings.player_id = ?';
			$values[] = $input['player_id'];
		}

		if (isset($input['year_month'])) {
			$where[] = 'friend_referrial_monthly_earnings.year_month = ?';
			$values[] = $input['year_month'];
		}

		if (isset($input['player_username'])) {
			$where[] = 'player.username = ?';
			$values[] = $input['player_username'];
		}

		if (isset($input['paid_flag'])) {
			$where[] = 'friend_referrial_monthly_earnings.paid_flag = ?';
			$values[] = $input['paid_flag'];
		}

		$result = $this->data_tables->get_data($request, $columns, $table, $where, $values, $joins);
		$total_bets_limit = 100000;
		//print_r($result);
		for ($c = 0; $c < count($result['data']); $c++) {
			$total_bets = $result['data'][$c][11]; //total_bets
			$yearmonth = $result['data'][$c][2]; //yearmonth
			$re = '';
			for ($i = 1; $i <= 3; $i++) {
				$re .= $yearmonth . ',';
				$previousReport = $this->player_earning->getPreviousEarning($result['data'][$c][10], $yearmonth);
				if (!$previousReport) {
					break;
				}

				$activeplayers += (int) $previousReport->active_players;
				$yearmonth = $previousReport->year_month;
			}
			if ($total_bets < $total_bets_limit) {
				$result['data'][$c][0] = '';
				$result['data'][$c][1] = '';
			}
		}
		return $result;
	}

	public function friend_referral_daily_report($request, $is_export) {
		$readOnlyDB = $this->getReadOnlyDB();
		$this->load->library('data_tables', array('DB' => $readOnlyDB));
		$this->load->model('player_earning');

		$this->data_tables->is_export = $is_export;

		$input = $this->data_tables->extra_search($request);
		$i = 0;
		$columns = array(
			array(
				'select' => 'player_friend_referrial_logs.id',
				'alias' => 'id',
			),
			array(
				'dt' => $i++,
				'select' => 'player_friend_referrial_logs.year_month_date',
				'alias' => 'yearmonthdate',
				'formatter' => function ($d, $row) {
					$year = substr($d, 0, 4);
					$month = substr($d, 4, 2);
					$date = substr($d, 6, 2);
					return $year . '-' . $month . '-' . $date;
				},
			),
			array(
				'dt' => $i++,
				'select' => 'player_friend_referrial_logs.referred_count',
				'alias' => 'referred_count',
			),
			array(
				'dt' => $i++,
				'select' => 'player_friend_referrial_logs.total_bets',
				'alias' => 'total_bets',
				'formatter' => 'currencyFormatter',
			),
		);
		$table = 'player_friend_referrial_logs';
		// $joins = array(
		// 	'player' => 'player.playerId = player_friend_referrial_logs.player_id',
		// );
		$joins = array();

		if (isset($input['player_id'])) {
			$where[] = 'player_friend_referrial_logs.player_id = ?';
			$values[] = $input['player_id'];
		}
		if (isset($input['from'])) {
			$where[] = 'player_friend_referrial_logs.year_month_date >= ?';
			$values[] = $input['from'];
		}
		if (isset($input['to'])) {
			$where[] = 'player_friend_referrial_logs.year_month_date <= ?';
			$values[] = $input['to'];
		}
		$result = $this->data_tables->get_data($request, $columns, $table, $where, $values, $joins);
		return $result;
	}

	/**
	 * Get the multi-lang of the Accumulation Mode.
	 * The language string for the field,"Remark" of Grade Report in SBE.
	 *
	 * @param integer $accumulationMode The value shoule be one of the following,
	 * - Group_level::ACCUMULATION_MODE_DISABLE
	 * - Group_level::ACCUMULATION_MODE_FROM_REGISTRATION
	 * - Group_level::ACCUMULATION_MODE_LAST_CHANGED_GEADE
	 * - Group_level::ACCUMULATION_MODE_LAST_CHANGED_GEADE_RESET_IF_MET
	 * @return string $lang The text string.
	 */
	public function getLangFromAccumulationMode($accumulationMode){
		$lang = '';
		switch($accumulationMode){
			case Group_level::ACCUMULATION_MODE_DISABLE :
				$lang = lang('None');
			break;

			case Group_level::ACCUMULATION_MODE_FROM_REGISTRATION :
				$lang = lang('Registration Date');
			break;

			case Group_level::ACCUMULATION_MODE_LAST_CHANGED_GEADE :
				$lang = lang('Last Change Period');
			break;

			case Group_level::ACCUMULATION_MODE_LAST_CHANGED_GEADE_RESET_IF_MET :
				$lang = lang('Last Change Period with Reset If Met');
			break;
		}
		return $lang;
	} // EOF getLangFromAccumulationMode

	public function parseRemark4settingInfoHarshInUpGraded($settingInfoHarshInUpGraded, $upgraded_list, $is_export, $report_row){
		$readOnlyDB = $this->getReadOnlyDB();
		$this->load->library('group_level_lib', array('DB' => $readOnlyDB));
		$this->load->model(['vipsetting', 'group_level']);

		$vip_grade_report_id = $report_row['id'];
		$langUpgradedAdditiveConditionals = lang('Upgraded additive conditionals');
		/// 1 param, contents
		$formatHtml = <<<EOF
		<div class="rlt1-upgraded_additive_conditionals" >
			<div class="rlt1-upgraded_additive_conditionals-title row" >
				<div class="col-md-12">
					<b>
						$langUpgradedAdditiveConditionals
					</b>
				</div>
			</div>
			%s  <!-- # 1 upgraded_additive_conditionals-contents -->
		</div>
EOF;
		$formatHtml = preg_replace('/\%([-!$%^&*()_+|~=`{}\[\]:";\'<>?,.\/]{1})/', '%%$1', $formatHtml); // Escape character

		if($is_export){
			$formatHtml = <<<EOF
$langUpgradedAdditiveConditionals
%s  <!-- # 1 upgraded_additive_conditionals-contents -->
EOF;
		}

		/// 2 param,
		// 1. level-info, apply for the content of sprintf(formatHtml4levelInfo).
		// 2. level-conditions-info
		$formatHtml4contents = <<<EOF
		<div class="rlt1-upgraded_additive_conditionals-contents row" >
			%s <!-- # 1 level-info -->
			%s <!-- # 2 level-conditions-info -->
		</div>
EOF;
		$formatHtml4contents = preg_replace('/\%([-!$%^&*()_+|~=`{}\[\]:";\'<>?,.\/]{1})/', '%%$1', $formatHtml4contents); // Escape character

		if($is_export){
			$formatHtml4contents = <<<EOF
%s <!-- # 1 level-info -->
%s <!-- # 2 level-conditions-info -->
EOF;
		}

		$langLevelName = lang('Level Name');
		/// 1 param, Level Name with Group Name
		$formatHtml4levelInfo =<<<EOF
		<div class="rlt1-upgraded_additive_conditionals-level-name row" >
			<div class="col-md-4">
				<div class="pull-right">
						$langLevelName
				</div>
			</div>
			<div class="col-md-8">
					%s  <!-- # 1 Level Name -->
			</div>
		</div> <!-- EOF .rlt1-upgraded_additive_conditionals-level-info -->
EOF;
		$formatHtml4levelInfo = preg_replace('/\%([-!$%^&*()_+|~=`{}\[\]:";\'<>?,.\/]{1})/', '%%$1', $formatHtml4levelInfo); // Escape character

		if($is_export){
			$formatHtml4levelInfo = <<<EOF
$langLevelName : %s  <!-- # 1 Level Name -->
EOF;
		}

		/// 3 params,
		// 1. Amount Type, ex: deposit, bet, "bet details"
		// 2. Condition Amount
		// 3. Is Additives
		$formatHtml4ConditionInfo =<<<EOF
		<div class="rlt1-upgraded_additive_conditionals-contitions row" >
			<div class="col-md-4">
				<div class="pull-right">
					<b>
						%s <!-- # 1 Amount Type -->
					</b>
				</div>
			</div>
			<div class="col-md-4">
					%s  <!-- # 2 Condition Amount -->
			</div>
			<div class="col-md-4">
				<b>
					%s  <!-- # 3 is additives -->
				</b>
			</div>
		</div> <!-- EOF .rlt1-upgraded_additive_conditionals-contitions -->
EOF;
		$formatHtml4ConditionInfo = preg_replace('/\%([-!$%^&*()_+|~=`{}\[\]:";\'<>?,.\/]{1})/', '%%$1', $formatHtml4ConditionInfo); // Escape character

		if($is_export){
			$formatHtml4ConditionInfo = <<<EOF
%s <!-- # 1 Amount Type --> %s <!-- # 2 Condition Amount --> %s <!-- # 3 is additives -->
EOF;
		}

		$returnData = '';
		if( ! empty($upgraded_list) ){
			$langAdditived = lang('Additived');
			$langNoAdditived = lang('No Additived');

			// pre-append the current upgraded level info
			$_vipsettingcashbackruleinfo = $this->utils->json_decode_handleErr($report_row['vipsettingcashbackruleinfo'], true);
			$_newVipLevelId = $_vipsettingcashbackruleinfo['vipsettingcashbackruleId'];
			$_playerLevel = $this->vipsetting->getVipGroupLevelDetails($_vipsettingcashbackruleinfo['vipsettingcashbackruleId']);
			$_setting = [];
			if( ! empty($_vipsettingcashbackruleinfo['vip_upgrade_id']) ){
				$_setting = $this->group_level->getSettingData($_vipsettingcashbackruleinfo['vip_upgrade_id']);
			}
			$vip_grade_report_id = $report_row['id'];
			$upgraded_list[$vip_grade_report_id] = $this->group_level_lib->build_upgraded_info($_newVipLevelId, $_playerLevel, $_setting);

			$html4contents = '';
			foreach($upgraded_list as $upgrad_report_id => $upgraded_detail){
				$html4levelInfo = '';
				$html4levelConditionsInfo = '';
				// the html4levelInfo only applied at first element
				$groupLevelName = '';
				$groupLevelName .= empty($upgraded_detail['vipGroupName'])? lang('N/A'): lang($upgraded_detail['vipGroupName']);
				$groupLevelName .= ' - ';
				$groupLevelName .= empty($upgraded_detail['vipLevelName'])? lang('N/A'): lang($upgraded_detail['vipLevelName']);
				$html4levelInfo .= sprintf($formatHtml4levelInfo, $groupLevelName);

				if( isset($upgraded_detail['bet_amount_in_formula']) ){
					$_langAmountType = lang('Bet');
					if( ! empty($upgraded_detail['bet_amount_in_formula'][1]) ){
						$_bet_amount = $upgraded_detail['bet_amount_in_formula'][1];
						$_is_additives_in_amount_type = $settingInfoHarshInUpGraded['total_bet'];
						$_is_additived = ( !!$_is_additives_in_amount_type )? $langAdditived: $langNoAdditived ;
						$html4ConditionInfo = sprintf(	$formatHtml4ConditionInfo
													, $_langAmountType // #1
													, $_bet_amount // #2
													, $_is_additived // #3
												);
						$html4levelConditionsInfo .= $html4ConditionInfo;
						if($is_export){
							$html4levelConditionsInfo .= Report_model::EXPORT_CSV_EOL;
						}
					}
				} // EOF if( isset($upgraded_detail['bet_amount_in_formula']) ){...

				if( isset($upgraded_detail['deposit_amount_in_formula']) ){
					$_langAmountType = lang('Deposit');
					if( ! empty($upgraded_detail['deposit_amount_in_formula'][1]) ){
						$_deposit_amount = $upgraded_detail['deposit_amount_in_formula'][1];
						$_is_additives_in_amount_type = $settingInfoHarshInUpGraded['total_deposit'];
						$_is_additived = ( !!$_is_additives_in_amount_type )? $langAdditived: $langNoAdditived ;
						$html4ConditionInfo = sprintf(	$formatHtml4ConditionInfo
													, $_langAmountType // #1
													, $_deposit_amount // #2
													, $_is_additived // #3
												);
						$html4levelConditionsInfo .= $html4ConditionInfo;
						if($is_export){
							$html4levelConditionsInfo .= Report_model::EXPORT_CSV_EOL;
						}
					}
				} // EOF if( isset($upgraded_detail['deposit_amount_in_formula']) ){...

				/// 2 param,
				// 1. level-info
				// 2. level-conditions-info
				$html4contents .= sprintf($formatHtml4contents, $html4levelInfo, $html4levelConditionsInfo);
			} // EOF foreach($upgraded_list as $upgrad_report_id => $upgraded_detail){...


			$returnData .= sprintf( $formatHtml
				, $html4contents // # 1 - contents
			);
		} // EOF if( ! empty($upgraded_list) ){...

		if($is_export){
			$returnData = strip_tags($returnData);
		}

		return $returnData ;
	}// EOF parseRemark4settingInfoHarshInUpGraded()

	/**
	 * detail: get player grade request
	 * @param array $request
	 * @param Boolean $is_export
	 * @return array
	 */
	public function playerGradeReports($request, $is_export = false)
	{
		$this->load->model(['group_level','game_type_model', 'player', 'users']);
		$this->load->library('data_tables');
		$readOnlyDB = $this->getReadOnlyDB();
		$this->load->library('group_level_lib', array('DB' => $readOnlyDB));

        if( $this->utils->getConfig('enabled_log_OGP31460_performance_trace') ){
			global $BM;
			$BM->mark('performance_trace_time_9515');
			$this->utils->debug_log('OGP31460.performance.trace.9515');
            $performance_trace_time_list = [];
		}

		/**
		 * To Parse the rule and rlt for Remark col.
		 * @param string $rule For Formula of Remark,
		 * @param string $rlt For Result of Remark,
		 * @param integer $accumulation for reference to ACCUMULATION_MODE_XXX
		 * @param boolean $is_export For CSV file export.
		 * @return string The combied text.
		 */
		$parseRemark4rule_rlt = function($rule, $rlt, $accumulation, $setting_name = '', $is_export = false){
			$formatHtml=<<<EOF
<div style="font-weight: bold;"> Accumulation: %s </div>
<div> Computation: %s </div>
<div> Setting: %s </div>
<div> Formula: %s </div>
<div> Result: %s </div>
EOF;
			switch($accumulation){
				default:
				case Group_level::ACCUMULATION_MODE_DISABLE :
					$_accumulation = lang('No');
					$_computation = lang('None');
				break;

				case Group_level::ACCUMULATION_MODE_FROM_REGISTRATION :
					$_accumulation = lang('Yes');
					$_computation = lang('Registration Date');
				break;

				case Group_level::ACCUMULATION_MODE_LAST_CHANGED_GEADE :
					$_accumulation = lang('Yes');
					$_computation = lang('Last Change Period');
				break;

				case Group_level::ACCUMULATION_MODE_LAST_CHANGED_GEADE_RESET_IF_MET :
					$_accumulation = lang('Yes');
					$_computation = lang('Last Change Period with Reset If Met');
				break;

			}
			if( empty($setting_name)){
				$setting_name = lang('N/A');
			}
			$returnData = sprintf($formatHtml, $_accumulation, $_computation, $setting_name, $rule, $rlt);

			if($is_export){
				$returnData = strip_tags($returnData);
			}

			return $returnData ;
		}; // EOF parseRemark4rule_rlt()

		// OGP-19716: show upgrade from/to level instead of name of upgrade settings
		/**
		 * Show the remark for more easy reading
		 * @param string $rule The formula string.
		 * @param string $rlt The result formula after applied the formula string.
		 * @param string $accumulation The commom accumulation value.
		 * @param string $level_from The source level name.
		 * @param string $level_to The target level name.
		 * @param array $initialAmount
		 * @param array $isMetOffsetRules
		 * @param string $setting_name The level setting name
		 * @param boolean $is_export The export to CVS request or not.
		 */
		$parseRemark4rule_rlt1 = function(	$rule // # 1
											, $rlt // # 2
											, $accumulation // # 3
											, $level_from // # 4
											, $level_to // # 5
											, $setting_name // # 6
											, $initialAmount // # 6.1
											, $isMetOffsetRules // # 6.2
											, $is_export = false // # 7
		){
			$langAccumulation = lang('Accumulation');
			$langComputation = lang('Computation');
			$langLevelSettings = lang('Level Settings');
			$langTo = lang('to');
			$langInitialAmount = lang('Initial Amount');
			$langFormula = lang('Formula');
			$langResult = lang('Result');
			$appendInitialAmountClass = '';
			$langToForceUseOffsetRules =  lang('To force use offset rules.');

			$formatHtml=<<<EOF
<div class="rlt1-remark" >
	<div class="rlt1-accumulation row" >
		<div class="col-md-5">
			<div class="pull-right">
				<b>
					$langAccumulation:
				</b>
			</div>
		</div>
		<div class="col-md-7">
			<b>
				%s  <!-- # 1 commom accumulation -->
			</b>
		</div>
	</div>
	<div class="rlt1-computation row" >
		<div class="col-md-5">
			<div class="pull-right">
				$langComputation :
			</div>
			<div class="pull-right font-size-12px col-is-force-offset-rules %s"> <!-- # 2.0 hide or EMPTY @todo OGP-19825  -->
				<i class="glyphicon glyphicon-info-sign text-danger" data-toggle="tooltip" data-placement="auto" data-html="true" data-title="$langToForceUseOffsetRules"></i>
			</div>
		</div>
		<div class="col-md-7">
			%s <!-- # 2 computation setting -->
		</div>
	</div> <!-- EOF .rlt1-computation -->
	<div class="rlt1-level-settings row">
		<div class="col-md-5">
			<div class="pull-right">
				$langLevelSettings :
			</div>
		</div>
		<div class="col-md-7">
			%s $langTo %s
			<!-- # 3 source level -->
			<!-- # 4 target level -->
			<span  class="rlt1-level-setting-name hide">
				(%s) <!-- # 5 setting name -->
			</span>
		</div>
	</div>
	<div class="rlt1-formula row">
		<div class="col-md-5">
			<div class="pull-right">
				$langFormula :
			</div>
		</div>
		<div class="col-md-7">
			%s <!-- # 6 Formula string -->
		</div>
	</div>
	<div class="rlt1-initial-amount row %s "><!-- # 6.1 appendInitialAmountClass, show/hide Initial Amount -->
		<div class="col-md-5">
			<div class="pull-right">
				$langInitialAmount :
			</div>
		</div>
		<div class="col-md-7 initial-amount-col">
			%s <!-- # 6.2 Initial Amount -->
		</div>
	</div>
	<div class="rlt1-result row">
		<div class="col-md-5">
			<div class="pull-right">
				$langResult :
			</div>
		</div>
		<div class="col-md-7">
			%s <!-- # 7 the result formula after applied the Formula string -->
		</div>
	</div>
</div>
EOF;
			$formatHtml = preg_replace('/\%([-!$%^&*()_+|~=`{}\[\]:";\'<>?,.\/]{1})/', '%%$1', $formatHtml); // Escape character

			if($is_export){
				$formatHtml = <<<EOF
$langAccumulation : %s <!-- # 1 commom accumulation -->%s<!-- # 2.0 hide or EMPTY @todo OGP-19825  -->
$langComputation : %s <!-- # 2 computation setting -->
$langLevelSettings : %s $langTo %s (%s) <!-- # 5 setting name -->
$langFormula : %s <!-- # 6 Formula string -->%s<!-- # 6.1 appendInitialAmountClass, show/hide Initial Amount -->
$langInitialAmount : %s <!-- # 6.2 Initial Amount -->
$langResult : %s <!-- # 7 the result formula after applied the Formula string -->
EOF;
			}


			switch($accumulation){
				default:
				case Group_level::ACCUMULATION_MODE_DISABLE :
					$_accumulation = lang('No');
					$_computation = lang('None');
				break;

				case Group_level::ACCUMULATION_MODE_FROM_REGISTRATION :
					$_accumulation = lang('Yes');
					$_computation = lang('Registration Date');
				break;

				case Group_level::ACCUMULATION_MODE_LAST_CHANGED_GEADE :
					$_accumulation = lang('Yes');
					$_computation = lang('Last Change Period');
				break;

				case Group_level::ACCUMULATION_MODE_LAST_CHANGED_GEADE_RESET_IF_MET :
					$_accumulation = lang('Yes');
					$_computation = lang('Last Change Period with Reset If Met');
				break;
			}
			if( empty($setting_name)){
				$setting_name = lang('N/A');
			}

			$_initialAmount = '';
			if( empty($initialAmount) ){
				$appendInitialAmountClass .= 'hide';
			}else{
				// parse the initialAmount and remove the textarea and replaced in.
				$_initialAmount = $this->utils->encodeJson($initialAmount);
				$_initialAmount = $this->_getHtml4InitialAmountCol($_initialAmount, $is_export);
			}

			$hideCSS = 'hide';
			if( ! empty($isMetOffsetRules['bool']) ){
				$hideCSS = '';
			}

			if($is_export){
				$hideCSS = ''; // # 2.0 hide or EMPTY @todo OGP-19825
				$appendInitialAmountClass = ''; // # 6.1
			}

			$returnData = sprintf( $formatHtml
									, $_accumulation // # 1 - commom accumulation
									, $hideCSS // # 2.0 // # 2.0 hide or EMPTY @todo OGP-19825
									, $_computation // # 2 - computation setting
									, $level_from // # 3 - source level
									, $level_to // # 4 - target level
									, $setting_name // # 5 - setting name
									, $rule // # 6 - Formula string
									, $appendInitialAmountClass // # 6.1
									, $_initialAmount // # 6.2
									, $rlt // # 7 - the result formula after applied the Formula string
									// ,'aaaaabb'
								);

			if($is_export){
				$returnData = strip_tags($returnData);
			}

			return $returnData ;
		}; // EOF parseRemark4rule_rlt1()

		/**
		 * To Parse the downMaintain for Remark col.
		 * @param array $downMaintain The settings and results about Level Maintain.
		 * @param boolean $is_export For CSV file export.
		 * @return string The combied text.
		 */
		$parseRemark4downMaintain = function($downMaintain, $is_export){
			$langGuaranteedLevelMaintainTime = lang('Guaranteed Level Maintain Time');
			$langPeriod = lang('Period');
			$langDatetimeRange = lang('Datetime Range');
			$langLevelMaintainCondition = lang('Level Maintain Condition');
			$langDepositAmount = lang('Deposit Amount');
			$langBetAmount = lang('Bet Amount');
			$langIsSufficient = lang('isSufficient');
            $langInsufficientReason = lang('Insufficient Reason');
			$langIsConditionMet = lang('isConditionMet');

			$fromDatetime = $downMaintain['calc']['dateTimeRange']['fromDatetime'];
			$toDatetime = $downMaintain['calc']['dateTimeRange']['toDatetime'];
			$downMaintainTimeLength = $downMaintain['rule']['downMaintainTimeLength'];
			$downMaintainUnit = $downMaintain['rule']['downMaintainUnit'];
			$downMaintainConditionDepositAmount = $downMaintain['rule']['downMaintainConditionDepositAmount'];
			$downMaintainConditionBetAmount = $downMaintain['rule']['downMaintainConditionBetAmount'];
			$betAmount = $downMaintain['result']['betAmount'];
			$deposit_amount = $downMaintain['result']['deposit_amount'];
			if( ! is_null($betAmount) ){
				$betAmount = sprintf('%0.2f ', $betAmount);
			}else{ // if No calc
				$betAmount = lang('N/A');
			}
			if( ! is_null($deposit_amount) ){
				$deposit_amount = sprintf('%0.2f ', $deposit_amount);
			}else{ // if No calc
				$deposit_amount = lang('N/A');
			}

			if( empty($downMaintain['result']['isSufficient']) ){
				$isSufficient = lang('FALSE');
			}else{
				$isSufficient = lang('TRUE');
			}
            $insufficientReason = lang('N/A');
            if( isset($downMaintain['result']['insufficientReason']) ){
				$insufficientReason = $downMaintain['result']['insufficientReason'];
			}

			if( empty($downMaintain['result']['isConditionMet']) ){
				$isConditionMet = lang('FALSE');
			}else{
				$isConditionMet = lang('TRUE');
			}



			$formatHtml=<<<EOF
<div class="down-maintain" style="font-weight: bold;">$langGuaranteedLevelMaintainTime</div>
<div>$langPeriod: %d %s</div> <!-- #1 #2 n Day/Week/Month -->
<div>$langDatetimeRange: </div> <!-- last changed Period -->
<div> %s ~ %s </div> <!-- #3 #4 2020/06/12 18:20:32 ~ 2020/07/07 12:22:12 -->
<div>$langLevelMaintainCondition</div>
<div>$langDepositAmount = %s %s %0.2f </div> <!-- #5 #5.1 #6 123 >= 111 -->
<div>$langBetAmount = %s %s %0.2f </div> <!-- #7 #7.1 #8 113 >= 112 -->
<div>$langIsSufficient: %s</div> <!-- #9 -->
<div>$langInsufficientReason: %s</div> <!-- #9.1 -->
<div>$langIsConditionMet: %s</div> <!-- #10 -->
EOF;

			if($is_export){
				$formatHtml=<<<EOF
$langGuaranteedLevelMaintainTime
$langPeriod: %d %s <!-- #1 #2 n Day/Week/Month -->
$langDatetimeRange: %s ~ %s <!-- last changed Period --> <!-- #3 #4 2020/06/12 18:20:32 ~ 2020/07/07 12:22:12 -->

$langLevelMaintainCondition
$langDepositAmount = %s %s %0.2f <!-- #5 #5.1 #6 123 >= 111 -->
$langBetAmount = %s %s %0.2f <!-- #7 #7.1 #8 113 >= 112 -->
$langIsSufficient: %s <!-- #9 -->
$langInsufficientReason: %s <!-- #9.1 -->
$langIsConditionMet: %s <!-- #10 -->
EOF;
			}

			if($is_export){
				$conditionFlag = '≥';
			}else{
				$conditionFlag = '&#8805;';
			}
			$returnData = sprintf(	$formatHtml
									, $downMaintainTimeLength // # 1
									, $downMaintainUnit // # 2
									, $fromDatetime // # 3
									, $toDatetime // # 4
									, $deposit_amount // # 5
									, $conditionFlag // # 5.1
									, $downMaintainConditionDepositAmount // setting  // # 6
									, $betAmount  // # 7
									, $conditionFlag // # 7.1
									, $downMaintainConditionBetAmount // setting // # 8
									, $isSufficient // # 9
                                    , $insufficientReason // # 9.1
									, $isConditionMet // # 10
								);

			if($is_export){
				$returnData = strip_tags($returnData);
			}

			return $returnData ;
		};// EOF $parseRemark4downMaintain()


		/**
		 * To parse the separate accumulation part of json, for Remark display.
		 * @param string|object $_separate_accumulation_settings If type is string then json_decode() to object.
		 * @param object $separate_accumulation_calcResult The results after Group_level::calcSeparateAccumulationByExtraSettingList().
		 * @param array $initialAmount
		 * @param array $isMetOffsetRules
		 * @param boolean $is_export If ture means to strip_tags() for export to the CSV file.
		 * @return string $returnData The HTML or Text for display the separate accumulation part information.
		 */
		$parseRemark4separate_accumulation = function($_separate_accumulation_settings // # 1
													, $separate_accumulation_calcResult // # 2
													, $initialAmount // # 3
													, $isMetOffsetRules // # 4
													, $is_export // # 5
		){
			if( gettype($_separate_accumulation_settings) == 'string'){
				$separate_accumulation_settings = json_decode($_separate_accumulation_settings, true);
			}else{
				$separate_accumulation_settings = $_separate_accumulation_settings;
			}

			$langSeparateAccumulation = lang('Separate Accumulation');
			$langTitle = lang('Title');
			$langAccumulation = lang('Accumulation');
			$langResult = lang('Result');
			$langInitial = lang('Initial Amount');
			$langDateRange = lang('Date Range');
			$langToForceUseOffsetRules =  lang('To force use offset rules.');

			$format4amountListHtml=<<<EOF
<div class="separate-accumulation-amount %s"> <!-- #1, extra class of separate-accumulation-amount -->
	<div class="row">
		<div class="amount-name-title col-md-5">$langTitle</div>
		<div class="amount-name col-md-7"> %s <!-- #2, bet amount --> </div>
	</div>
	<div class="row">
		<div class="accumulation-mode-title col-md-5">
			<div class="pull-right">
				$langAccumulation
			</div>
			<div class="pull-right font-size-12px col-is-force-offset-rules hide %s"> <!-- #3.0 hide or EMPTY @todo OGP-19825  -->
				<i class="glyphicon glyphicon-info-sign text-danger" data-toggle="tooltip" data-placement="auto" data-html="true" data-title="$langToForceUseOffsetRules"></i>
			</div>
		</div>
		<div class="accumulation-mode col-md-7"> %s <!-- #3, No Accumulation --> </div>
	</div>
	<div class="row">
		<div class="amount-count-title col-md-5">$langResult</div>
		<div class="amount-count col-md-7"> %s <!-- #4, 123.45 --> </div>
	</div>
	<div class="row">
		<div class="amount-initial-title col-md-5">$langInitial</div>
		<div class="amount-initial col-md-7"> %s <!-- #4.1, 123.45 --> </div>
	</div>
	<div class="row">
		<div class="amount-during-title col-md-5">
			<div class="">
				$langDateRange
			</div>
		</div>
		<div class="amount-during col-md-7">
			<div class="amount-during-start">
			%s <!-- #5, 2020/06/12 18:20:32 -->
			</div> ~ <div class="amount-during-end">
			%s <!-- #6, 2020/06/12 18:20:32 -->
			</div>
		</div>
	</div>
</div> <!-- EOF .separate-accumulation-amount -->
EOF;

			if($is_export){
				$format4amountListHtml=<<<EOF
%s <!-- #1, extra class of separate-accumulation-amount -->
$langTitle : %s <!-- #2, bet amount -->%s<!-- #3.0 hide or EMPTY @todo OGP-19825  -->
$langAccumulation : %s <!-- #3, No Accumulation -->
$langResult : %s <!-- #4, 123.45 -->
$langInitial : %s <!-- #4.1, 123.45 -->
$langDateRange : %s <!-- #5, 2020/06/12 18:20:32 --> ~ %s <!-- #6, 2020/06/12 18:20:32 -->
EOF;
			}

			$formated4amountList = [];
			if( empty($separate_accumulation_calcResult['details']) ){
				$separate_accumulation_calcResult['details']  = [];
			}

			$hideCSS = 'hide';
			if( ! empty($isMetOffsetRules['bool']) ){
				$hideCSS = '';
			}
			if($is_export){
				$hideCSS = '';
			}

// $this->utils->debug_log('OGP-19332.$separate_accumulation_calcResult',$separate_accumulation_calcResult);
			foreach($separate_accumulation_calcResult['details'] as $indexKey => $detail){
				// print_r($indexKey);
				// $suffix = '';
				$_initialAmount = 0;
				$doFormated = false;
				if( $indexKey == 'total_bet'
					&& ! empty($separate_accumulation_settings['bet_amount'])
				){
					$langAmountName = lang('Bet Amount');
					$separate_accumulation_setting_key = 'bet_amount';
					$doFormated = true;
					if( ! empty($initialAmount['total_bet']) ){
						$_initialAmount = $initialAmount['total_bet'];
						// $suffix = ' + '. $initialAmount['bet_amount'];
					}
				}
				if ($indexKey == 'separated_bet'){
					$langAmountName = lang('Bet Amount');
					$separate_accumulation_setting_key = 'bet_amount';
					$doFormated = true;
				}
				if( $indexKey == 'total_win'
					&& ! empty($separate_accumulation_settings['win_amount'])
				){
					$langAmountName = lang('Win Amount');
					$separate_accumulation_setting_key = 'win_amount';
					$doFormated = true;
					if( ! empty($initialAmount['total_win']) ){
						$_initialAmount = $initialAmount['total_win'];
					}
				}
				if( $indexKey == 'total_loss'
					&& ! empty($separate_accumulation_settings['loss_amount'])
				){
					$langAmountName = lang('Loss Amount');
					$separate_accumulation_setting_key = 'loss_amount';
					$doFormated = true;
					if( ! empty($initialAmount['total_loss']) ){
						$_initialAmount = $initialAmount['total_loss'];
					}
				}
				if( $indexKey == 'deposit'
					&& ! empty($separate_accumulation_settings['deposit_amount'])
				){
					$langAmountName = lang('Deposit Amount');
					$separate_accumulation_setting_key = 'deposit_amount';
					$doFormated = true;
					if( ! empty($initialAmount['deposit']) ){
						$_initialAmount = $initialAmount['deposit'];
					}
				}

				if($doFormated && isset($detail['count']) ){
					$langAccumulationMode = $this->getLangFromAccumulationMode($separate_accumulation_settings[$separate_accumulation_setting_key]['accumulation']);
					$count = $detail['count'];
					// if( ! empty($suffix) ){
					// 	$count = $count. ' '. $suffix;
					// }
					if($is_export){
						$indexKey = '';
					}
					$from = $detail['from'];
					$to = $detail['to'];



					$_formated4amountList = sprintf( $format4amountListHtml
													, $indexKey // # 1
													, $langAmountName // # 2
													, $hideCSS // # 3.0 hide or EMPTY
													, $langAccumulationMode // # 3
													, $count // # 4
													, $_initialAmount // # 4.1 - to search the keyword,"initialAmount" for trace the source.
													, $from // # 5
													, $to // # 6
									); // EOF $formated4amountList[] = sprintf(..
					if($is_export){
						// remove first line by empty indexKey in export
						if( strpos($_formated4amountList, Report_model::EXPORT_CSV_EOL) !== false){
							$_formated4amountList = array_slice( explode(Report_model::EXPORT_CSV_EOL, $_formated4amountList), 1);
							$_formated4amountList = implode(Report_model::EXPORT_CSV_EOL, $_formated4amountList);
						}
						$_formated4amountList = trim($_formated4amountList). Report_model::EXPORT_CSV_EOL;
					}
					$formated4amountList[] = $_formated4amountList;
				}else if( $doFormated
					&& $indexKey == 'separated_bet'
					&& !empty($separate_accumulation_settings) // Patch for A PHP Error was encountered | Severity: Notice | Message:  Undefined index: bet_amount | Filename: modules/report_module_player.php:7266
				){
					// OGP-19332 reporting


					$langAccumulationMode = $this->getLangFromAccumulationMode($separate_accumulation_settings[$separate_accumulation_setting_key]['accumulation']);

					$from = $detail['from'];
					$to = $detail['to'];

					$separated_bet = $detail;
					// Clear for clac the result_amount of the game_platform's and the game_type's.
					unset($separated_bet['from']) ;
					unset($separated_bet['to']) ;

					foreach($separated_bet as $indexStr => $currBetResult){
// $this->utils->debug_log('OGP-19825.7219.currBetResult',$currBetResult);
// {"message":"OGP-19825.7219.currBetResult","context":[{"type":"game_type","value":"801","math_sign":">=","game_type_id":"325","precon_logic_flag":"and","result_amount":0,"count":0,"game_type_id_325":0}],"level":100,"level_name":"DEBUG","channel":"default-og","datetime":"2020-11-26 15:27:25 519236","extra":{"tags":{"request_id":"fcdb3389774a3b2028a58c18e3da9b95","env":"live.og_local","version":"6.88.01.001","hostname":"default-og"},"file":"/home/vagrant/Code/og/admin/application/models/modules/report_module_player.php","line":7540,"class":"Report_model","function":"{closure}","url":"/api/playerGradeReports","ip":"172.22.0.1","http_method":"POST","referrer":"http://admin.og.local/report_management/viewGradeReport","host":"admin.og.local","real_ip":null,"browser_ip":null,"process_id":91492,"memory_peak_usage":"13.75 MB","memory_usage":"11.75 MB"}}
						$_initialAmount = 0; // default
						$_indexKey = $indexKey. $currBetResult['type'];
						switch($currBetResult['type']){
							case 'game_platform':
								$_indexKey .= $currBetResult['game_platform_id'];
								$currSeparatedBetKey = 'game_platform_id_'. $currBetResult['game_platform_id'];

								$langAmountName = $this->external_system->getNameById($currBetResult['game_platform_id']);
								if( !  empty($initialAmount['separated_bet'][$currSeparatedBetKey]) ){
									$_initialAmount = $initialAmount['separated_bet'][$currSeparatedBetKey];
								}

							break;
							case 'game_type':
								$_indexKey .= $currBetResult['game_type_id'];
								$currSeparatedBetKey = 'game_type_id_'. $currBetResult['game_type_id'];
								$langList = $this->game_type_model->searchGameTypeByList([ $currBetResult['game_type_id'] ]);
								$langAmountName = $langList[0];
								if( !  empty($initialAmount['separated_bet'][$currSeparatedBetKey]) ){
									$_initialAmount = $initialAmount['separated_bet'][$currSeparatedBetKey];
								}
							break;
						}
// $this->utils->debug_log('OGP-19825.currSeparatedBetKey',$currSeparatedBetKey, '$_initialAmount:', $_initialAmount);
						$count = $currBetResult['result_amount'];

						if($is_export){
							$_indexKey = '';
						}
						$_formated4amountList = sprintf(	$format4amountListHtml
										, $_indexKey // # 1
										, $langAmountName // # 2
										, $hideCSS // # 3.0 hide or EMPTY
										, $langAccumulationMode // # 3
										, $count // # 4
										, $_initialAmount // # 4.1 - to search the keyword,"initialAmount" for trace the source.
										, $from // # 5
										, $to // # 6
						); // EOF $formated4amountList[] = sprintf(...
						if($is_export){
							// remove first line by empty indexKey in export
							if( strpos($_formated4amountList, Report_model::EXPORT_CSV_EOL) !== false){
								$_formated4amountList = array_slice( explode(Report_model::EXPORT_CSV_EOL, $_formated4amountList), 1);
								$_formated4amountList = implode(Report_model::EXPORT_CSV_EOL, $_formated4amountList);
							}
							$_formated4amountList = trim($_formated4amountList). Report_model::EXPORT_CSV_EOL;
						}
						$formated4amountList[] = $_formated4amountList;
					} // EOF foreach($separated_bet as $indexStr => $currBetResult){...

				} // EOF if($doFormated && $indexKey == 'separated_bet' && !empty($separate_accumulation_settings) ){...


			} // EOF foreach($separate_accumulation_calcResult['details'] as $indexKey => $detail){...


			// for curr_condition_details
			foreach($separate_accumulation_calcResult['details'] as $indexKey => $detail){
				$_initialAmount = 0;
				$doFormated = false;
				// for @.details.total_bet.enforcedDetails.details.curr_condition_details
				$separate_accumulation_setting_key = 'bet_amount';
				if( $indexKey == 'total_bet'
					&& ! empty($separate_accumulation_settings[$separate_accumulation_setting_key])
				){
					$_path = 'enforcedDetails.details.curr_condition_details';
					$_rlt = $this->group_level_lib->extractCurrConditionDetails($detail, $_path);
					if( $_rlt['bool'] === true){
						$curr_detail = $_rlt['extracted'];
						$langAmountName = lang('Bet Amount');
						$langAccumulationMode = $this->getLangFromAccumulationMode($separate_accumulation_settings[$separate_accumulation_setting_key]['accumulation']);
						$doFormated = true;
						if( ! empty($initialAmount['total_bet']) ){
							$_initialAmount = $initialAmount['total_bet'];
							// $suffix = ' + '. $initialAmount['bet_amount'];
						}
						$count = $curr_detail['gameLogData']['total_bet'];
						$from = $curr_detail['fromDatetime'];
						$to = $curr_detail['toDatetime'];
					}
				}

				// for @.details.deposit.enforcedDetails.details.curr_condition_details
				$separate_accumulation_setting_key = 'deposit_amount';
				if( $indexKey == 'deposit'
					&& ! empty($separate_accumulation_settings[$separate_accumulation_setting_key])
				){
					$_path = 'enforcedDetails.details.curr_condition_details';
					$_rlt = $this->group_level_lib->extractCurrConditionDetails($detail, $_path);
					if( $_rlt['bool'] === true){
						$curr_detail = $_rlt['extracted'];
						$count = $curr_detail['deposit'];
						$from = $curr_detail['fromDatetime'];
						$to = $curr_detail['toDatetime'];
						$langAmountName = lang('Deposit Amount');
						$langAccumulationMode = $this->getLangFromAccumulationMode($separate_accumulation_settings[$separate_accumulation_setting_key]['accumulation']);
						$doFormated = true;
						if( ! empty($initialAmount['deposit']) ){
							$_initialAmount = $initialAmount['deposit'];
						}
					}
				}

				if($doFormated){
					if($is_export){
						$indexKey = '';
					}
					$_formated4amountList = sprintf(	$format4amountListHtml
														, $indexKey // # 1
														, $langAmountName // # 2
														, $hideCSS // # 3.0 hide or EMPTY
														, $langAccumulationMode // # 3
														, $count // # 4
														, $_initialAmount // # 4.1 - to search the keyword,"initialAmount" for trace the source.
														, $from // # 5
														, $to // # 6
										); // EOF $formated4amountList[] = sprintf(...
					if($is_export){
						// remove first line by empty indexKey in export
						if( strpos($_formated4amountList, Report_model::EXPORT_CSV_EOL) !== false){
							$_formated4amountList = array_slice( explode(Report_model::EXPORT_CSV_EOL, $_formated4amountList), 1);
							$_formated4amountList = implode(Report_model::EXPORT_CSV_EOL, $_formated4amountList);
						}
						$_formated4amountList = trim($_formated4amountList). Report_model::EXPORT_CSV_EOL;
					}
					$formated4amountList[] = $_formated4amountList;
				}
			} // EOF foreach($separate_accumulation_calcResult['details'] as $indexKey => $detail){...

			$formatHtml=<<<EOF
<div class="separate-accumulation-remark">
	<div style="font-weight: bold;">
		<div class=" font-size-12px col-is-force-offset-rules %s"> <!-- #1 hide or EMPTY @todo OGP-19825  -->
			<i class="glyphicon glyphicon-info-sign text-danger" data-toggle="tooltip" data-placement="auto" data-html="true" data-title="$langToForceUseOffsetRules"></i>
			$langSeparateAccumulation
		</div>
	</div>
	<div class="separate-accumulation-amount-list">
		%s
	</div> <!-- EOF .separate-accumulation-amount-list -->
</div> <!-- EOF .separate-accumulation-remark -->
EOF;

			if($is_export){
				$formatHtml=<<<EOF
%s <!-- #1 hide or EMPTY @todo OGP-19825  -->

$langSeparateAccumulation
%s <!-- EOF .separate-accumulation-amount-list -->
EOF;

				array_walk($formated4amountList, function($item, $key){
					$formated4amountList[$key] = trim($item). Report_model::EXPORT_CSV_EOL;
				});
			}

			$returnData = sprintf(	$formatHtml
				, $hideCSS // # 1
				, implode('', $formated4amountList) // # 2
			);

			if($is_export){
				$returnData = strip_tags($returnData);
				$returnData = trim($returnData);
			}

			return $returnData ;

		};// EOF $parseRemark4separate_accumulation()

		$i = 0;
		$input = $this->data_tables->extra_search($request);
		$allGroupLevels = $this->group_level->getGroupLevelList();
        $this->load->helper(['player_helper']);
		$_this = $this;
		# DEFINE TABLE COLUMNS ########################################################################################################################################################################
		$columns = array(
			array(
				'dt' => $i++,
				'alias' => 'id',
				'select' => 'vip_grade_report.id',
				'name' => lang('No.'),
			),
			array(
				'alias' => 'newvipId',
				'select' => 'vip_grade_report.newvipId',
			),
			array(
				'alias' => 'vipsettingcashbackruleId',
				'select' => 'vip_grade_report.vipsettingcashbackruleId',
			),
			array(
				'alias' => 'vipupgradesettinginfo',
				'select' => 'vip_grade_report.vipupgradesettinginfo',
			),
			array(
				'alias' => 'vipsettingcashbackruleinfo',
				'select' => 'vip_grade_report.vipsettingcashbackruleinfo',
			),
			array(
				'alias' => 'period_start_time',
				'select' => 'vip_grade_report.period_start_time',
			),
			array(
				'alias' => 'period_end_time',
				'select' => 'vip_grade_report.period_end_time',
			),
			[
				'alias'		=> 'vipsettingId' ,
				'select'	=> 'vip_grade_report.vipsettingId'
			],
			array(
				'alias' => 'playerId',
				'select' => 'vip_grade_report.player_id',
			),
			array(
				'dt' => $i++,
				'alias' => 'username',
				'select' => 'vip_grade_report.player_id',
				'name' => lang('Player Username'),
				'formatter' => function ($d, $row) use ($is_export, &$joins, &$performance_trace_time_list )  {
                    $player_id = $d;
                    // @todo detect player in $joins
                    if( $this->utils->getConfig('enabled_log_OGP31460_performance_trace') ){
                        global $BM;
                    }
                    if( $this->utils->getConfig('enabled_log_OGP31460_performance_trace') ){
                        $markStr = 'performance_trace_time_10288_player_id'. $player_id;
                        $performance_trace_time_list['10288'][$player_id] = $markStr;
                        $BM->mark($markStr);
                    }
                    $_row = $this->player->getPlayerUsername($player_id);
                    $d = $_row['username'];
                    if( $this->utils->getConfig('enabled_log_OGP31460_performance_trace') ){
                        $markStr = 'performance_trace_time_10299_player_id'. $player_id;
                        $performance_trace_time_list['10299'][$player_id] = $markStr;
                        $BM->mark($markStr);
                    }

					if (!$is_export) {
						return sprintf('<a href="/player_management/userInformation/%s">%s</a>', $row['playerId'], $d);
					}else{
						return $d;
					}
				},
			),
            array(
                'dt' => $i++,
                'alias' => 'player_tag',
                'select' => 'vip_grade_report.player_id',
                'formatter' => function($d) use ($is_export) {
                    return player_tagged_list($d, $is_export);
                },
                'name' => lang('Player Tag'),
            ),
            array(
                'dt' => $i++,
                'alias' => 'affiliate_username',
                'select' => 'vip_grade_report.player_id',
                'formatter' => function ($d) use ( &$joins, &$performance_trace_time_list ){
                    // @todo detect affiliates in $joins
                    // player.affiliateId = affiliates.affiliateId
                    $player_id = $d;
                    if( $this->utils->getConfig('enabled_log_OGP31460_performance_trace') ){
                        global $BM;
                    }
                    if( $this->utils->getConfig('enabled_log_OGP31460_performance_trace') ){
                        $markStr = 'performance_trace_time_10333_player_id'. $player_id;
                        $performance_trace_time_list['10333'][$player_id] = $markStr;
                        $BM->mark($markStr);
                    }
                    $_row = $this->player->getPlayerAffiliateUsername($player_id);
                    if( !empty($_row) ){
                        $d = $_row;
                    }else{
                        $d = '';
                    }
                    if( $this->utils->getConfig('enabled_log_OGP31460_performance_trace') ){
                        $markStr = 'performance_trace_time_10345_player_id'. $player_id;
                        $performance_trace_time_list['10345'][$player_id] = $markStr;
                        $BM->mark($markStr);
                    }
                    if (empty($d)) { // $d = affiliates.username
                        return 'N/A';
                    } else {
                        return lang($d);
                    }
                },
                'name' => lang('Affiliate'),
            ),
			array(
				'dt' => $i++,
				'alias' => 'request_type',
				'select' => 'vip_grade_report.request_type',
				'formatter' => function ($d) {
					switch ($d) {
						case Group_level::REQUEST_TYPE_AUTO_GRADE:
							return lang('report.gr.auto_grade');
						case Group_level::REQUEST_TYPE_MANUAL_GRADE:
							return lang('report.gr.manual_grade');
						case Group_level::REQUEST_TYPE_SPECIFIC_GRADE:
							return lang('report.gr.specific_grade');
						default:
							return 'N/A';
					}
				},
				'name' => lang('Request Type'),
			),
			array(
				'dt' => $i++,
				'alias' => 'request_time',
				'select' => 'vip_grade_report.request_time',
				'name' => lang('Request Time'),
			),
			array(
				'dt' => $i++,
				'alias' => 'request_grade',
				'select' => 'vip_grade_report.request_grade',
				'formatter' => function ($d) {
					switch ($d) {
						case Group_level::RECORD_UPGRADE:
							return lang('report.gr.upgrade');
						case Group_level::RECORD_DOWNGRADE:
							return lang('report.gr.downgrade');
						case Group_level::RECORD_SPECIFICGRADE:
							return lang('report.gr.specificgrade');
						default:
							return 'N/A';
					}
				},
				'name' => lang('Behavior'),
			),
			array(
				'dt' => $i++,
				'alias' => 'group_name',
				'select' => 'vip_grade_report.vipsettingId',
				'formatter' => function ($d, $row) use ( &$joins, &$performance_trace_time_list ){
                    // @todo detect vipsetting in $joins
                    $vipsettingId = $d;
                    if( $this->utils->getConfig('enabled_log_OGP31460_performance_trace') ){
                        global $BM;
                    }
                    if( $this->utils->getConfig('enabled_log_OGP31460_performance_trace') ){
                        $markStr = 'performance_trace_time_10409_vipsettingId'. $vipsettingId;
                        $performance_trace_time_list['10409'][$vipsettingId] = $markStr;
                        $BM->mark($markStr);
                    }
                    $group=$this->group_level->getGroupById($vipsettingId);
                    if( !empty($group['groupName']) ){
                        $d = $group['groupName'];
                    }else{
                        $d = '';
                    }
                    if( $this->utils->getConfig('enabled_log_OGP31460_performance_trace') ){
                        $markStr = 'performance_trace_time_10420_vipsettingId'. $vipsettingId;
                        $performance_trace_time_list['10420'][$vipsettingId] = $markStr;
                        $BM->mark($markStr);
                    }
					if (empty($d)) {
						return 'N/A';
					} else {
						return lang($d);
					}
				},
				'name' => lang('Group Name'),
			),
			array(
				'dt' => $i++,
				'alias' => 'level_from',
				'select' => 'vip_grade_report.level_from',
				'formatter' => function ($d, $row) use ($allGroupLevels) {
					// OGP-19716: use (vipsettingId, level_from) to determine old level, instead of vipsettingcashbackruleId
					// $oldLevelId = $row['vipsettingcashbackruleId'];
					$old_level = $this->group_level->getLevelByGroupAndLevelNum($row['vipsettingId'], $row['level_from']);
                    $oldLevelId = empty($old_level)? 0: $old_level['vipsettingcashbackruleId'];
					if (!empty($oldLevelId) && isset($allGroupLevels[$oldLevelId])) {
						return lang($allGroupLevels[$oldLevelId]['vipLevelName']);
					} else {
						return 'N/A';
					}
				},
				'name' => lang('Origin Level'),
			),
			array(
				'dt' => $i++,
				'alias' => 'level_to',
				'select' => 'vip_grade_report.level_to',
				'formatter' => function ($d, $row) use ($allGroupLevels) {
					$return = null;
					// OGP-19716: use (vipsettingId, level_to) to determine new level, instead of newvipId
					// $newLevelId = $row['newvipId'];
					$new_level = $this->group_level->getLevelByGroupAndLevelNum($row['vipsettingId'], $row['level_to']);
                    $newLevelId = empty($new_level)? 0: $new_level['vipsettingcashbackruleId'];
					if(!empty($newLevelId) && !empty($allGroupLevels)){
						if (isset($allGroupLevels[$newLevelId])) {
							$return = lang($allGroupLevels[$newLevelId]['vipLevelName']);
						}
					}

					$vipupgradesettinginfo = $this->utils->json_decode_handleErr($row['vipupgradesettinginfo'], true);
					$setting_name = lang('N/A'); // default
					if( ! empty($vipupgradesettinginfo['setting_name']) ){
						$setting_name = $vipupgradesettinginfo['setting_name'];
					}

					$vipsettingcashbackruleinfo = $this->utils->json_decode_handleErr($row['vipsettingcashbackruleinfo'], true);
					if( ! empty($vipsettingcashbackruleinfo) ){
						if($row['vipsettingId'] != $vipsettingcashbackruleinfo['vipSettingId'] ){
							$_groupName = $setting_name;
							$_vipLevelName = lang('N/A');
							// for Cross VIP Group, the field contains Group Name and Level Name
							$theVipGroupLevelDetails = $this->group_level->getVipGroupLevelDetails($vipsettingcashbackruleinfo['vipsettingcashbackruleId']);
							if( ! empty($theVipGroupLevelDetails) ){
								$_groupName = $vipsettingcashbackruleinfo['groupName'];
							}// EOF if( ! empty($theVipGroupLevelDetails) ){...
							if( ! empty($theVipGroupLevelDetails) ){
								$_vipLevelName = $vipsettingcashbackruleinfo['vipLevelName'];
							}
							$return = sprintf( '%s - %s', lang($_groupName), lang($_vipLevelName) );
						}
					}

					if( empty($return) ){
						$return = lang('N/A');
					}

					return $return;
				},
				'name' => lang('New Level'),
			),
			array(
				'dt' => $i++,
				'alias' => 'period_time',
				'select' => 'vip_grade_report.period_start_time',
				'formatter' => function ($d, $row) {
					$remark = json_decode($row['remark'], true) ;

					$isSeparatedAccumulation = false;
					if( ! empty($remark['separate_accumulation_settings']) ){
						$isSeparatedAccumulation = true;
					}

					if( ! empty($remark['downMaintain']['result']['isSufficient'])
						&& ! empty($remark['downMaintain']['result']['isConditionMet'])
					){
						$period_start_time = $remark['downMaintain']['calc']['dateTimeRange']['fromDatetime'];
						$period_end_time = $remark['downMaintain']['calc']['dateTimeRange']['toDatetime'];
						return $period_start_time. ' ~ '. $period_end_time;
					}else if($isSeparatedAccumulation){
						return lang('refer Remark');
					}else if ($row['period_start_time'] && $row['period_end_time']) {
						return $row['period_start_time'] . ' ~ ' . $row['period_end_time'];
					} else {
						return lang('N/A');
					}
				},
				'name' => lang('Grace Period'),
			),
			array(
				'dt' => $i++,
				'alias' => 'pgrm_start_time',
				'select' => 'vip_grade_report.pgrm_start_time',
				'name' => lang('Process Start Time'),
			),
			array(
				'dt' => $i++,
				'alias' => 'pgrm_end_time',
				'select' => 'vip_grade_report.pgrm_end_time',
				'name' => lang('Process End Time'),
			),
			array(
				'dt' => $i++,
				'alias' => 'pgrm_time_elapsed',
				'formatter' => function ($d, $row) {
					return (strtotime($row['pgrm_end_time']) - strtotime($row['pgrm_start_time']));
				},
				'name' => lang('Time Elapsed'),
			),
			array(
				'dt' => $i++,
				'alias' => 'updated_by',
				'select' => 'vip_grade_report.updated_by',
				'formatter' => function ($d) use ( &$joins, &$performance_trace_time_list ){
                    /// @todo detect adminusers in $joins
                    // adminusers.userId = vip_grade_report.updated_by
                    // adminusers.username
                    $userId = $d;
                    if( $this->utils->getConfig('enabled_log_OGP31460_performance_trace') ){
                        global $BM;
                    }
                    if( $this->utils->getConfig('enabled_log_OGP31460_performance_trace') ){
                        $markStr = 'performance_trace_time_10559_userId'. $userId;
                        $performance_trace_time_list['10559'][$userId] = $markStr;
                        $BM->mark($markStr);
                    }
                    $user = $this->users->selectUsersById($d);
                    if( !empty($user['username'])){
                        $d = $user['username'];
                    }else{
                        $d = lang('N/A');
                    }
                    if( $this->utils->getConfig('enabled_log_OGP31460_performance_trace') ){
                        $markStr = 'performance_trace_time_10570_userId'. $userId;
                        $performance_trace_time_list['10570'][$userId] = $markStr;
                        $BM->mark($markStr);
                    }
					return $d;
				},
				'name' => lang('Updated By'),
			),
			array(
				'dt' => $i++,
				'alias' => 'remark',
				'select' => 'vip_grade_report.remark',
				'formatter' => function ($d, $row) use ( $is_export, $parseRemark4rule_rlt, $parseRemark4downMaintain, $parseRemark4separate_accumulation, $parseRemark4rule_rlt1, $allGroupLevels, $_this){

					$vip_grade_report_id = $row['id'];
					$request_grade = $row['request_grade'];
					$vipupgradesettinginfo = json_decode($row['vipupgradesettinginfo'], true);
					$vipsettingcashbackruleinfo = json_decode($row['vipsettingcashbackruleinfo'], true);
					$returnData = [];
					if ( empty($d) ) {
						$returnData[] = lang('N/A');
					}

					$desc = json_decode($d, true);
					// for accumulation of settings
					if ( ! empty($desc['rule']) ){
						$rule = $desc['rule'];

                        // for debug Formula Not display Separate Bet-Game list while down.
                        // $this->utils->debug_log('OGP-19332.7104.rule:'.$rule);

						$rlt = $desc['rlt'];
						if( ! empty($vipupgradesettinginfo['accumulation']) ){
							$accumulation = $vipupgradesettinginfo['accumulation'];
						}else{
							$accumulation = Group_level::ACCUMULATION_MODE_DISABLE;
						}

						$this->utils->debug_log(__METHOD__, 7078, 'upgrade_info', $vipupgradesettinginfo);

						$setting_name = lang('N/A');
						if( ! empty($vipupgradesettinginfo['setting_name']) ){
							$setting_name = $vipupgradesettinginfo['setting_name'];
						}

						// $returnData[count($returnData)] = $parseRemark4rule_rlt($rule, $rlt, $accumulation, $setting_name, $is_export);
						$old_level = $this->group_level->getLevelByGroupAndLevelNum($row['vipsettingId'], $row['level_from']);
                        $oldLevelId = empty($old_level)? 0:  $old_level['vipsettingcashbackruleId'];
						$old_level_name = !empty($oldLevelId) && isset($allGroupLevels[$oldLevelId]) ? lang($allGroupLevels[$oldLevelId]['vipLevelName']) : lang('N/A');

						$new_level = $this->group_level->getLevelByGroupAndLevelNum($row['vipsettingId'], $row['level_to']);
                        $newLevelId = empty($new_level)? 0: $new_level['vipsettingcashbackruleId'];
						$new_level_name = !empty($newLevelId) && isset($allGroupLevels[$newLevelId]) ? lang($allGroupLevels[$newLevelId]['vipLevelName']) : lang('N/A');

						$initialAmount = [];
						if( ! empty($desc['initialAmount']) ){
							$initialAmount = $desc['initialAmount'];
						}
						$isMetOffsetRules = [];
						if( ! empty($desc['isMetOffsetRules']) ){
							$isMetOffsetRules = $desc['isMetOffsetRules'];
						}

                        // for debug Formula Not display Separate Bet-Game list while down.
                        $this->utils->debug_log('OGP-19825.7554.isMetOffsetRules:',$isMetOffsetRules);

						$returnData[count($returnData)] = $parseRemark4rule_rlt1( $rule // # 1
																				, $rlt // # 2
																				, $accumulation // # 3
																				, $old_level_name // # 4
																				, $new_level_name // # 5
																				, $setting_name // # 6
																				, $initialAmount // # 6.1
																				, $isMetOffsetRules // # 6.2
																				, $is_export // # 7
																			);



						$separate_accumulation_settings = '[]';
						if( ! empty($vipupgradesettinginfo['separate_accumulation_settings']) ){
							// separate_accumulation_calcResult
							$separate_accumulation_settings = json_decode($vipupgradesettinginfo['separate_accumulation_settings'], true);
						}

						$separate_accumulation_calcResult = [];
						if( ! empty($desc['separate_accumulation_calcResult']) ){
							$separate_accumulation_calcResult = $desc['separate_accumulation_calcResult'];
						}
						$initialAmount = [];
						if( ! empty($desc['initialAmount']) ){
							$initialAmount = $desc['initialAmount'];
						}
						$returnData[count($returnData)] = $parseRemark4separate_accumulation(	$separate_accumulation_settings // # 1
																							, $separate_accumulation_calcResult // # 2
																							, $initialAmount // # 3
																							, $isMetOffsetRules // # 4
																							, $is_export // # 5
																						);
					} // EOF if ( ! empty($desc['rule']) ){...

					if ( ! empty($desc['settingInfoHarshInUpGraded']) ){
						$settingInfoHarshInUpGraded = $desc['settingInfoHarshInUpGraded'];
						$upgraded_list = [];
						if( ! empty($desc['upgraded_list']) ){
							$upgraded_list = $desc['upgraded_list'];
						}

						$returnData[count($returnData)] = $_this->parseRemark4settingInfoHarshInUpGraded($settingInfoHarshInUpGraded, $upgraded_list, $is_export, $row);
					}

					// for Level Maintain of Edit VIP Group Level Setting
					if( ! empty($desc['downMaintain']) ){
						$downMaintain = $desc['downMaintain'];
						$returnData[count($returnData)] = $parseRemark4downMaintain($downMaintain, $is_export);
					}

					// merge to text/html
					if($is_export){
						$returnData = implode(Report_model::EXPORT_CSV_EOL, $returnData);
					}else{
						$returnData ='<div>'. implode('</div><div>', $returnData).'</div>';
					}


					if($is_export){
						$returnData = strip_tags($returnData);
					}

					return $returnData;
				},
				'name' => lang('Remark'),
			),
			array(
				'dt' => $i++,
				'alias' => 'status',
				'select' => 'vip_grade_report.status',
				'formatter' => function ($d) {
					return ($d) ? lang('success') : lang('Failed');
				},
				'name' => lang('Status')
			),
		);

		# FILTER ######################################################################################################################################################################################
		$table = 'vip_grade_report';
		$where = [];
		$values = [];
        $joins = [];
		$_joins = [ // The related tables will be used, once search uses the related fields,
			'player'     => 'player.playerId = vip_grade_report.player_id',
			// 'adminusers' => 'adminusers.userId = vip_grade_report.updated_by',
			// 'vipsetting' => 'vipsetting.vipSettingId = vip_grade_report.vipsettingId',
			'affiliates' => 'player.affiliateId = affiliates.affiliateId'
		];

		if (isset($input['date_from'], $input['date_to'])) {
			$where[] = "vip_grade_report.request_time BETWEEN ? AND ?";
			$values[] = $input['date_from'];
			$values[] = $input['date_to'];
		}

		if (isset($input['username'])) {
            $joins['player'] = $_joins['player'];

            if(isset($input['search_by']) && $input['search_by'] == 1) { // similar
                $where[]  = "player.username LIKE ?";
                $values[] = '%' . $input['username'] . '%';
            }
            else if(isset($input['search_by']) && $input['search_by'] == 2) { // exact
                $where[]  = "player.username = ?";
                $values[] = $input['username'];
            }
		}

		if (isset($input['request_type'])) {
			$where[]  = 'vip_grade_report.request_type = ?';
			$values[] = $input['request_type'];
		}

		if (isset($input['request_grade'])) {
			$where[]  = 'vip_grade_report.request_grade = ?';
			$values[] = $input['request_grade'];
		}

		if (isset($input['level_from'])) {
			$where[]  = 'vip_grade_report.vipsettingcashbackruleId = ?';
			$values[] = $input['level_from'];
		}

		if (isset($input['level_to'])) {
			$where[]  = 'vip_grade_report.newvipId = ?';
			$values[] = $input['level_to'];
		}

		if (isset($input['status'])) {
			$where[]  = 'vip_grade_report.status = ?';
			$values[] = $input['status'];
		}

		if (isset($input['tag_list'])) {
            $joins['player'] = $_joins['player'];
            $tag_list = $input['tag_list'];
			$is_include_notag = null;
            if(is_array($tag_list)) {
                $notag = array_search('notag',$tag_list);
                if($notag !== false) {
                    unset($tag_list[$notag]);
					$is_include_notag = true;
                }else{
					$is_include_notag = false;
				}

            } elseif ($tag_list == 'notag') {
                $tag_list = null;
				$is_include_notag = true;
            }

			$where_fragments = [];
			if($is_include_notag){
				$where_fragments[] = 'player.playerId NOT IN (SELECT DISTINCT playerId FROM playertag)';
			}

            if ( ! empty($tag_list) ) {
                $tagList = is_array($tag_list) ? implode(',', $tag_list) : $tag_list;
				$where_fragments[] =  'player.playerId IN (SELECT DISTINCT playerId FROM playertag WHERE playertag.tagId IN ('.$tagList.'))';
            }
			if( ! empty($where_fragments) ){
				$where[] = ' ('. implode(' OR ', $where_fragments ). ') ';
			}
        }

		if (isset($input['affiliate_username'])) {
            $joins['affiliates'] = $_joins['affiliates'];
			$where[] = "affiliates.username = ?";
			$values[] = $input['affiliate_username'];
		}

		if($is_export){
            $this->data_tables->options['is_export']=true;
			// $this->data_tables->options['only_sql']=true;
            if(empty($csv_filename)){
                $csv_filename=$this->utils->create_csv_filename(__FUNCTION__);
            }
            $this->data_tables->options['csv_filename']=$csv_filename;
		}

        if( $this->utils->getConfig('enabled_log_OGP31460_performance_trace') ){
			$BM->mark('performance_trace_time_10765');
			$this->utils->debug_log('OGP31460.performance.trace.10765');
		}

        $group_by = array();
        $having = array();
        $distinct = false;
        $external_order = [];
        $not_datatable = '';
        $countOnlyField = 'vip_grade_report.id';
		$result = $this->data_tables->get_data( $request // #1
                                                , $columns // #2
                                                , $table // #3
                                                , $where // #4
                                                , $values // #5
                                                , $joins // #6
                                                , $group_by // #7
                                                , $having // #8
                                                , $distinct // #9
                                                , $external_order // #10
                                                , $not_datatable // #11
                                                , $countOnlyField // #12
                                            );
        if( $this->utils->getConfig('enabled_log_OGP31460_performance_trace') ){
            $BM->mark('performance_trace_time_10788');
            $this->utils->debug_log('OGP31460.performance.trace.10788');
        }

        if( $this->utils->getConfig('enabled_log_OGP31460_performance_trace') ){
            $elapsed_time = [];

            $elapsed_time['10765_10788'] = $BM->elapsed_time('performance_trace_time_10765', 'performance_trace_time_10788'); // 10765_10788

            if( !empty($performance_trace_time_list['10333']) ){
                // player->getPlayerAffiliateUsername()
                $_elapsed_time_list = $this->utils->_script_elapsed_time_list($performance_trace_time_list['10333'], '10333', $performance_trace_time_list['10345'], '10345' ); // 10333_10345
                $elapsed_time = array_merge($elapsed_time , $_elapsed_time_list);
            }
            if( !empty($performance_trace_time_list['10409']) ){
                // group_level->getVipGroupLevelDetails()
                $_elapsed_time_list = $this->utils->_script_elapsed_time_list($performance_trace_time_list['10409'], '10409', $performance_trace_time_list['10420'], '10420' ); // 10409_10420
                $elapsed_time = array_merge($elapsed_time , $_elapsed_time_list);
            }
            if( !empty($performance_trace_time_list['10559']) ){
                // users->selectUsersById()
                $_elapsed_time_list = $this->utils->_script_elapsed_time_list($performance_trace_time_list['10559'], '10559', $performance_trace_time_list['10570'], '10570' ); // 10559_10570
                $elapsed_time = array_merge($elapsed_time , $_elapsed_time_list);
            }
            $this->utils->debug_log('playerGradeReports elapsed_time:', $elapsed_time);
            $elapsed_time = [];
            unset($elapsed_time);
        }else{
            $this->utils->debug_log('playerGradeReports elapsed_time disabled by enabled_log_OGP31465_performance_trace=0.');
        }

		if($is_export){
		    //drop result if export
			return $csv_filename;
		}

		if( ! empty( $this->data_tables->last_query )){
			$result['list_last_query'] = $this->data_tables->last_query;
		}

		return $result;
	} // EOF playerGradeReports

	/**
	 * detail: get player grade request
	 * @param array $request
	 * @param Boolean $is_export
	 * @return array
	 */
	public function playerRankReports($request, $is_export = false)
	{
		$this->load->library('data_tables');
		$this->load->model(array('player_score_model'));
		$input = $this->data_tables->extra_search($request);

		$newbet_bonus_turnover = 0;
		$newbet_bonus_trie = [];
		$rankKey = '';

		if (isset($input['rankKey'])) {
			$rankKey = $input['rankKey'];
			if (strpos($rankKey,'newbet') == 0){
				$newbet_setting = $this->player_score_model->checkCustomRank('newbet');
				if (!empty($newbet_setting)) {
					$turn_over = empty($newbet_setting['bonus_turnover']) ? 0 : $newbet_setting['bonus_turnover'];
					$newbet_bonus_trie =  empty($newbet_setting['rank_bonus_rate']) ? [] : $newbet_setting['rank_bonus_rate'];
					$rankKey_arr = explode( '_', $rankKey);
					if(!!($syncDate = (isset($rankKey_arr[1]) ? $rankKey_arr[1] : false))){
						$total_score = $this->player_score_model->getPlayerTotalScore(false, $syncDate, 'newbet', null, player_score_model::ID_FOR_TOTAL_SCORE);
						$newbet_bonus = (!empty($total_score[0]) && isset($total_score[0]['game_score'])) ? (float)$total_score[0]['game_score'] : 0;
						$newbet_bonus_turnover = $newbet_bonus * ($turn_over / 100);
					}
				}
			}
		}

		$i = 0;

		$columns = array(
			array(
				'alias' => 'player_id',
				'select' => 'rank.player_id',
				'name'	=> lang('player_id'),
			),
			array(
				'dt' => $i++,
				'alias' => 'rank',
				'select' => 'CAST(rank.rank as UNSIGNED)',
				'name'	=> lang('player_rank_report.rank'),
				'formatter' => function ($d) use ($is_export) {
					return (int) $d;
				},
			),
			array(
				'dt' => $i++,
				'alias' => 'username',
				'select' => 'p.username',
				'formatter' => ($is_export) ? 'defaultFormatter' : function ($d, $row) {
					$val = $d?:$row['player_id'];
					return '<a href="/player_management/userInformation/' . $row['player_id'] . '" target="_blank">' . $val . '</a>';
				},
				'name'	=> lang('Username')
			),
			array(
				'dt' => $i++,
				'alias' => 'score',
				'select' => 'rank.current_score',
				'formatter' =>function ($d) {
					return round($d, 2, PHP_ROUND_HALF_DOWN);
				},
				'name'	=> lang('Total Score')
			),
			array(
				'dt' => $i++,
				'alias' => 'updated_at',
				'select' => 'rank.updated_at',
				'formatter' => function ($d) use ($is_export) {
					if ($is_export) {
						return (!$d || strtotime($d) < 0) ? lang('lang.norecyet') : date('Y-m-d H:i:s', strtotime($d));
					} else {
						return (!$d || strtotime($d) < 0) ? '<i class="text-muted">' . lang('lang.norecyet') . '</i>' : date('Y-m-d H:i:s', strtotime($d));
					}
				},
				'name'	=> lang('Last Updated On')
			),
			array(
				'dt' => $i++,
				'alias' => 'rank_name',
				'select' => 'rank.rank_key',
				'name'	=> lang('player_rank_report.rank_name')
			),
			array(
				'dt' => $i++,
				'alias' => 'player_promo_id',
				'select' => 'rank.playerpromoId',
				'formatter' => function ($d, $row) use ($is_export, $newbet_bonus_turnover, $newbet_bonus_trie, $rankKey) {

					$note = lang('lang.norecyet');
					if (strpos($rankKey,'newbet') === 0){

						$current_rank = $row['rank'];
						$in_rank = $current_rank<=10 ? true : false;
						$total_bonus = round($newbet_bonus_turnover,2);
						$current_rate = $in_rank ? $newbet_bonus_trie[$current_rank] : 0;
						$current_bonus = $in_rank ? round($newbet_bonus_turnover * ($newbet_bonus_trie[$current_rank]/100),2) : 0;
						$current_status = $in_rank ? ($d ? lang('lang.paid') : lang('lang.unpaid')) : false;
						if ($is_export) {
							// $note = "Total Bonus: $total_bonus | Current Bonus: $current_bonus ($current_rate%)";
							$note = sprintf(lang("player_rank_report.newbet_detail_export"), $total_bonus, $current_bonus, $current_rate);
						} else {
							// $note = "Total Bonus: <b style='color:red'>$total_bonus</b> | Current Bonus: <b style='color:blue'>$current_bonus</b> (<b style='color:green'>$current_rate%</b>)";
							$note = sprintf(lang("player_rank_report.newbet_detail"), $total_bonus, $current_bonus, $current_rate);
						}
						if($current_status) $note =  $note." | ".lang("Status").": ". $current_status;
					}

					return $note;
				},
				'name'	=> lang('lang.detail'),
			),
			// array(
			// 	'dt' => ($is_export ? null : $i++),
			// 	'alias' => 'player_id',
			// 	'select' => 'rank.player_id',
			// 	'formatter' => function ($d, $row) use ($is_export) {
			// 		if (!$is_export) {
			// 			$adjustment_btn = '<a href="/report_management/viewAdjustmentScoreReport?by_username='.$row['username'].'" class="btn btn-xs btn-primary" target="_blank">'.lang('Manual Adjustment').'</a>';
			// 			$view_details_btn = '<button class="btn btn-xs btn-primary viewDetailsBtn" onClick="viewScoreDetails('.$row['player_id'].',\''.$row['username'].'\','. $row['score'].');return false;">'.lang('View Details').'</button>';
			// 			return $view_details_btn . $adjustment_btn;
			// 		}
			// 	},
			// 	'name'	=> lang('Action')
			// )
		);

		$table = 'score_rank rank';
        $where = [];
        $values = [];
        $joins = [
            'player p'     => 'rank.player_id = p.playerId',
            'total_score score'     => 'rank.player_id = score.player_id',
        ];

        $group_by = [];
        // $group_by = ['rank.player_id'];
		if (isset($input['username'])) {
            $where[]  = "p.username LIKE ?";
            $values[] = '%' . $input['username'] . '%';
        }

		if (isset($input['rank'])) {
            $where[]  = "rank.rank = ?";
            $values[] = (int)$input['rank'];
        }

		if (isset($input['rankGreaterThan'])) {
            $where[]  = "rank.rank >= ?";
            $values[] = (int)$input['rankGreaterThan'];
        }

		if (isset($input['rankLessThan'])) {
            $where[]  = "rank.rank <= ?";
            $values[] = (int)$input['rankLessThan'];
        }
		if (isset($input['rankKey'])) {
            $where[]  = "rank.rank_key = ?";
            $values[] = $input['rankKey'];
        }
		$request['order'] = [["column"=> 0, "dir"=> 'asc']];


		if ($is_export) {
            $this->data_tables->options['is_export']=true;
            if (empty($csv_filename)) {
                $csv_filename=$this->utils->create_csv_filename(__FUNCTION__);
            }
            $this->data_tables->options['csv_filename']=$csv_filename;
        }

		$result = $this->data_tables->get_data($request, $columns, $table, $where, $values, $joins, $group_by);

        if ($is_export) {
            //drop result if export
            return $csv_filename;
        }

        return $result;

	}

	public function getRedemptionCodeList($request, $is_export = false)
	{
		$this->load->model(array('redemption_code_model'));
		return $this->redemption_code_model->getRedemptionCodeList($request, $is_export);
	}

	public function getStaticRedemptionCodeList($request, $is_export = false)
	{
		$this->load->model(array('static_redemption_code_model'));
		return $this->static_redemption_code_model->getRedemptionCodeList($request, $is_export);
	}


	/**
	 * detail: get communicatio preference request
	 * @param array $request
	 * @param Boolean $is_export
	 * @return array
	 */
	public function communicationPreferenceReports($request, $is_export = false)
	{
		$this->load->model(array('group_level','communication_preference_model'));
		$this->load->library('data_tables');

		$i = 0;
		$input = $this->data_tables->extra_search($request);
		$allPlayerLevels = $this->group_level->getAllPlayerLevels();
		$config_comm_pref = $this->utils->getConfig('communication_preferences');
		# DEFINE TABLE COLUMNS ########################################################################################################################################################################
		$columns = array(
			/*array(
				//'dt' => $i++,
				'alias' => 'id',
				'select' => 'cp.id',
			),*/
			array(
				'alias' => 'player_id',
				'select' => 'cp.player_id',
			),
			array(
				'dt' => $i++,
				'alias' => 'requested_at',
				'select' => 'cp.requested_at',
				'name'	=> lang('Date of Request'),
				'formatter' => function ($d) use ($is_export) {
					if ($is_export) {
						return (!$d || strtotime($d) < 0) ? lang('lang.norecyet') : date('Y-m-d H:i:s', strtotime($d));
					} else {
						return (!$d || strtotime($d) < 0) ? '<i class="text-muted">' . lang('lang.norecyet') . '</i>' : date('Y-m-d H:i:s', strtotime($d));
					}
				},
			),
			array(
				'dt' => $i++,
				'alias' => 'username',
				'select' => 'p.username',
				'formatter' => ($is_export) ? 'defaultFormatter' : function ($d, $row) {
					return '<a href="/player_management/userInformation/' . $row['player_id'] . '">' . $d . '</a>';
				},
				'name'	=> lang('Username')

			),
			array(
				'dt' => $i++,
				'alias' => 'real_name',
				'select' => 'CONCAT(ifnull(pd.firstName,""), \' \', ifnull(pd.lastName,"") )',
				'formatter' => function ($d) use ($is_export) {
					if ($is_export) {
						return trim(trim($d), ',') ?: lang('lang.norecyet');
					} else {
						return trim(trim($d), ',') ?: '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
					}
				},
				'name' => lang("Real Name"),
			),
			array(
				'dt' => $i++,
				'alias' => 'aff_username',
				'select' => 'aff.username',
				'formatter' => function ($d) use ($is_export) {
					if ($is_export) {
						return trim(trim($d), ',') ?: lang('lang.norecyet');
					} else {
						return trim(trim($d), ',') ?: '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
					}
				},
				"name" => lang('Affiliate Username')
			),
			array(
				'dt' => $i++,
				'alias' => 'levelId',
				'select' => 'p.levelId',
				'formatter' => function ($d, $row) use ($allPlayerLevels, $is_export) {

					foreach ($allPlayerLevels as $key => $allPlayerLevel) {
						if($allPlayerLevel['vipsettingcashbackruleId'] == $d && $row['levelId'] == $d)
							return lang($allPlayerLevel['groupName']) . ' - ' . lang($allPlayerLevel['vipLevelName']);
					}

					if (isset($is_export)) {
						return lang('lang.norecyet');
					} else {
						return '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
					}
				},
				'name' => lang("Player Level"),
			),

		);

		foreach ($config_comm_pref as $comm_pref_key => $comm_pref_value) {
			$columns[] = array(
				'dt' => $i++,
				'alias' => 'preferences',
				'select' => 'cp.preferences',
				'formatter' => function ($d) use ($is_export, $comm_pref_key) {

					$d = json_decode($d);

					if(isset($d->$comm_pref_key) && $d->$comm_pref_key == "true")
						return lang('Yes');

					return lang('No');

				},
				'name' => lang($comm_pref_value),
			);
		}

		# FILTER ######################################################################################################################################################################################
		$table = 'player_communication_preference_history cp';
		$where = [];
		$values = [];
		$joins = [
			'player p'     => 'p.playerId = cp.player_id',
			'playerdetails pd' => 'pd.playerId = cp.player_id',
			'affiliates aff' => 'aff.affiliateId = p.affiliateId'
		];

		$group_by = ['cp.player_id','cp.requested_at','cp.preferences'];

		if (isset($input['date_from'], $input['date_to'])) {
			//$where[] = "cp.requested_at IN (SELECT max(requested_at) FROM player_communication_preference_history)";
			$where[] = "cp.requested_at IN (SELECT max(requested_at) FROM player_communication_preference_history WHERE player_id = cp.player_id AND requested_at BETWEEN ? AND ?) ";
			$values[] = $input['date_from'];
			$values[] = $input['date_to'];
		}
		else
		{
			$where[] = "cp.requested_at IN (SELECT max(requested_at) FROM player_communication_preference_history WHERE player_id = cp.player_id) ";
		}

		if (isset($input['player_username'])) {
			$where[]  = "p.username LIKE ?";
			$values[] = '%' . $input['player_username'] . '%';
		}

		if (isset($input['group_level'])) {
			$where[]  = 'p.levelId = ?';
			$values[] = $input['group_level'];
		}

		if (isset($input['aff_username'])) {
			$where[]  = "aff.username LIKE ?";
			$values[] = '%' . $input['aff_username'] . '%';
		}

		// -- Apply strict filtering if enabled
		$preferences = array();
		if(isset($input['strict_filtering']))
		{
			foreach ($config_comm_pref as $key => $value) {
				$preferences[$key] = "false";
			}
		}

		// -- Generate filter based on provided options
		if (isset($input['comm_pref_options'])) {

			if(!is_array($input['comm_pref_options'])){
				$preferences[$input['comm_pref_options']] = "true";
			}
			else{
				foreach ($input['comm_pref_options'] as $key => $value) {
					$preferences[$value] = "true";
				}
			}
		}

		// -- If strict, query with an "WHERE AND", else, use "WHERE OR"
		if(isset($input['strict_filtering'])){
			foreach ($preferences as $key => $value) {
				$where[]  = "cp.preferences LIKE ? ";
				$values[] = '%"'.$key.'":"'.$value.'"%';
			}
		}
		elseif(!empty($preferences)){
			$numItems = count($preferences);
			$i = 0;
			$where_or = "(";
			foreach ($preferences as $key => $value) {
				$where_or .= "cp.preferences LIKE ? ";
				if(++$i !== $numItems)
			    	$where_or .= "AND ";

				$values[] = '%"'.$key.'":"'.$value.'"%';
			}
			$where_or .= ")";

			$where[] = $where_or;
		}

		if($is_export){
            $this->data_tables->options['is_export']=true;
            if(empty($csv_filename)){
                $csv_filename=$this->utils->create_csv_filename(__FUNCTION__);
            }
            $this->data_tables->options['csv_filename']=$csv_filename;
		}

		$result = $this->data_tables->get_data($request, $columns, $table, $where, $values, $joins, $group_by);

		if($is_export){
		    //drop result if export
			return $csv_filename;
		}

		return $result;;
	}


	/**
	 * Return Income Access Signup Report
	 *
	 * @param void
	 * @return json
	 */
	public function incomeAccessSignupReports($request, $is_export = false) {
		$i = 0;
		$columns = array();
		$this->load->library('data_tables');

		$input = $this->data_tables->extra_search($request);

		$signup_csv_headers = $this->utils->getConfig('ia_daily_signup_csv_headers');

		foreach ($signup_csv_headers as $key => $header) {
			$data = array(
				'dt' => $i++,
				'alias' => $header,
				'select' => $header,
				'name' => lang($header)
			);

			array_push($columns, $data);
		}

		$input = $this->data_tables->extra_search($request);

		$from = isset($input['date_from']) ? $input['date_from'] : null;
		$to = isset($input['date_to']) ? $input['date_to'] : null;
		$username = isset($input['username']) ? $input['username'] : null;

		$data = $this->player_model->getDailySignupWithBtag($from, $to, $username);

		if ( $data > 0){
			$result = $this->data_tables->_prepareDataForLists($columns, $data);
		} else {
			$result = $this->data_tables->empty_data($request);
		}

		$result['header_data'] = $signup_csv_headers;

		return $result;

	}

	/**
	 * Return Income Access Sales Report
	 *
	 * @param void
	 * @return json
	 */
	public function incomeAccessSalesReports($request, $is_export = false) {
		$i = 0;
		$columns = array();
		$this->load->library('data_tables');

		$input = $this->data_tables->extra_search($request);

		$sales_csv_headers = $this->utils->getConfig('ia_daily_sales_csv_headers');

		foreach ($sales_csv_headers as $key => $header) {
			$data = array(
				'dt' => $i++,
				'alias' => $header,
				'select' => $header,
				'name' => lang($header)
			);

			array_push($columns, $data);
		}

		$input = $this->data_tables->extra_search($request);

		$from = isset($input['date_from']) ? $input['date_from'] : null;
		$to = isset($input['date_to']) ? $input['date_to'] : null;
		$username = isset($input['username']) ? $input['username'] : null;

		$data = $this->player_model->getDailySalesWithBtag($from, $to, $username);

		if ( $data > 0){
			$result = $this->data_tables->_prepareDataForLists($columns, $data);
		} else {
			$result = $this->data_tables->empty_data($request);
		}

		$result['header_data'] = $sales_csv_headers;

		return $result;

	}


	/**
	 * detail: get playertaggedlist
	 * @param array $request
	 * @param Boolean $is_export
	 * @return array
	 */
	public function playertaggedlist($request, $permissions, $is_export = false) {
		$readOnlyDB = $this->getReadOnlyDB();
		$this->load->library('data_tables', array("DB" => $readOnlyDB));
		$this->load->model(array('risk_score_model', 'player_kyc', 'kyc_status_model'));
		$this->load->helper(['player_helper']);

		$abstractApi=$this->utils->loadAnyGameApiObject();
		$walletMap = $this->utils->getGameSystemMap();
		$risk_score_model = $this->risk_score_model;
		$player_kyc = $this->player_kyc;
		$kyc_status_model = $this->kyc_status_model;

		$i = 0;
		$input = $this->data_tables->extra_search($request);

		$setCheckboxChecked = false;
		if (isset($input['triggered_by']) && !empty($input['triggered_by']) && $input['triggered_by']=='batch_remove_tags') {
			$setCheckboxChecked = true;
		}

		$where = array();
		$values = array();

		$columns = array(
			array(
				'select' => 'player.playerId',
				'alias' => 'playerId',
			),
			array(
				'alias' => 'verified_email',
				'select' => 'player.verified_email',
			),
			array(
				'alias' => 'verified_phone',
				'select' => 'player.verified_phone',
			),
		);

		if (!$is_export) {
			$columns[] = array(
				'dt' => $i++,
				'alias' => 'checkbox',
				'select' => 'playertag.playerTagId',
				'formatter' => function ($d, $row) use ($is_export, $setCheckboxChecked) {
					$str = '';
					if($setCheckboxChecked){
						$str = 'checked';
					}
					if ($is_export) {
						return '';
					} else {
                        return "
                            <input type='checkbox' class='checkWhite' id='playerTagId_{$d}' name='playerTagId[]' value='{$d}' onclick='uncheckAll(this.id)' {$str}>
                            <i class='fa fa-search cursor-pointer' aria-hidden='true' title='View History' onclick='viewPlayerTaggedhistory({$d}, {$row['playerId']})'></i>

                            <div class='modal fade in playerTagId_{$d}' id='viewTaggedHistoryModal' tabindex='-1' role='dialog' aria-labelledby='label_playerTagId_{$d}'>
                                <div class='modal-dialog modal-lg' role='document'>
                                    <div class='modal-content'>
                                        <div class='modal-header'>
                                            <button type='button' class='close' data-dismiss='modal' aria-label='Close'>
                                                <span aria-hidden='true'>&times;</span>
                                            </button>
                                            <h4 class='modal-title'><i class='icon-price-tags'></i> <span id='label_playerTagId_{$d}'></span> ({$row['username']})</h4>
                                        </div>
                                        <div class='modal-body'></div>
                                    </div>
                                </div>
                            </div>
                        ";
					}
				},
				'name' => '',
			);
		}

		$columns[] = array(
			'dt' => $i++, # 1
			'alias' => 'username',
			'select' => 'player.username',
			'formatter' => ($is_export) ? 'defaultFormatter' : function ($d, $row) {
				return '<a href="/player_management/userInformation/' . $row['playerId'] . '">' . $d . '</a>';
			},
			'name' => lang('player.01'),
		);

		$columns[] = array(
			'dt' => $i++, # 2
			'alias' => 'real_name',
			'select' => 'CONCAT(ifnull(playerdetails.firstName,""), \' \', ifnull(playerdetails.lastName,"") )',
			'formatter' => function ($d) use ($is_export) {
				if ($is_export) {
					return trim(trim($d), ',') ?: lang('lang.norecyet');
				} else {
					return trim(trim($d), ',') ?: '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
				}
			},
			'name' => lang("sys.vu19"),
		);

		$columns[] = array(
			'dt' => $i++, # 3
			'select' => 'CONCAT(player.groupName, \'|\', player.levelName )',
			'formatter' => function ($d, $row) {
				$d = (explode("|",$d));
				if(count($d) > 1){
					$d = lang($d[0]).' - '.lang($d[1]);
				}
				return $d;
			},
			'alias' => 'memberLevel',
			'name' => lang('VIP Level'),
		);

		// if ( $permissions['view_player_contactinfo_cn'] /// Combo permissions, view_player_contactinfo_cn and player_contact_info.
		// ){
		// 	$columns[] = array(
		// 		'dt' => $i++, # 4
		// 		'alias' => 'contactNumber',
		// 		'select' => 'playerdetails.contactNumber',
		// 		'name' => lang("player.63"),
		// 		'formatter' => function ($d, $row) use ($permissions, $is_export) {
		// 			$str = $d;
		// 			if ($permissions['view_player_detail_contactinfo_cn'] ) {
		// 				if ($is_export) {
		// 					$str = $row['verified_phone'] == self::DB_TRUE ? $str . '   ' . lang('Verified') : $str . '  ' . lang('Not verified');
		// 				} else {
		// 					if ($row['verified_phone'] == self::DB_TRUE) {
		// 						$str = '<span class="text-success"><i class="fa fa-phone"></i> ' . $str . '</a></span>';
		// 					} else {
		// 						$str = '<span class="text-danger"><i class="fa fa-phone"></i> ' . $str . '</a></span>';
		// 					}
		// 				}
		//
		// 			} else {
		// 				$str = $this->utils->maskMiddleString($str,0,strlen($str)-4, 4);
		// 				if (!$is_export) {
		// 					if ($row['verified_phone'] == self::DB_TRUE) {
		// 						$str = '<span class="text-success"><i class="fa fa-phone"></i><span title="' . lang('con.aff01') . '">' . $str . '</span>';
		// 					} else {
		// 						$str = '<span class="text-danger"><i class="fa fa-phone"></i><span title="' . lang('con.aff01') . '">' . $str . '</span>';
		// 					}
		// 				}
		//
		// 			}
		// 			if (!$is_export && $permissions['tele_marketing_call']) {
		// 				$str = '<a href="' . site_url('/api/call_player_tele/' . $row['playerId']) . '" target="_blank">' . $str . '</a>';
		// 			}
		// 			return $str;
		// 		},
		// 	);
		// }

		// if ( $permissions['view_player_contactinfo_em'] /// Combo permissions, view_player_contactinfo_em and player_contact_info.
		// 	&& (!$this->utils->isEnabledFeature('hide_taggedlist_email_column'))
		// ) {
		// 	$columns[] = array(
		// 		'dt' => $i++, # 5
		// 		'alias' => 'email',
		// 		'select' => 'player.email',
		// 		'name' => lang("player.06"),
		// 		'formatter' => function ($d, $row) use ($permissions, $is_export) {
		// 			$str = $d;
		// 			if ($permissions['view_player_detail_contactinfo_em']) {
		// 				if ($is_export) {
		// 					$str = $row['verified_email'] == self::DB_TRUE ? $str . '  ' . lang('Verified') : $str . '  ' . lang('Not verified');
		// 				} else {
		// 					if ($row['verified_email'] == self::DB_TRUE) {
		// 						$str = '<span class="text-success"><i class="fa fa-envelope"></i> ' . $str . '</a></span>';
		// 					} else {
		// 						$str = '<span class="text-danger"><i class="fa fa-envelope"></i> ' . $str . '</a></span>';
		// 					}
		// 				}
		// 			}else{
		// 				$str = $this->utils->maskMiddleStringLite($str, 4);
		// 				if (!$is_export) {
		// 					$str = '<span title="' . lang('con.aff01') . '">' . $str . '</span>';
		// 				}
		// 			}
		// 			return $str;
		// 		},
		// 	);
		// } // EOF if ($this->permissions->checkPermissions('view_player_contactinfo_em') && !$this->utils->isEnabledFeature('hide_taggedlist_email_column'))

		// $columns[] = array(
		// 	'dt' => $i++, # 6
		// 	'alias' => 'country',
		// 	'select' => 'playerdetails.residentCountry',
		// 	'name' => lang("player.20"),
		// 	'formatter' => function ($d) use ($is_export) {
		// 		if ($is_export) {
		// 			return trim(trim($d), ',') ?: lang('lang.norecyet');
		// 		} else {
		// 			return trim(trim($d), ',') ?: '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
		// 		}
		// 	},
		// );

		$columns[] = array(
			'dt' => $i++, # 7
			'alias' => 'tagName',
			'select' => 'playertag.tagid',
			'name' => lang("player.41"),
			'formatter' => function ($d, $row) use ($is_export) {
				if (!$is_export) {
					return tag_formatted($d);
				}
				return strip_tags(tag_formatted($d));
			},
		);

		$columns[] = array(
			'dt' => $i++, # 8
			'alias' => 'tagged_at',
			'select' => 'playertag.createdOn',
			'name' => lang("tagged_players.tagged_at"),
			// 'formatter' => function ($d, $row) use ($is_export) {
				// return 'aaa';
				// return player_tagged_list($row['playerId'], $is_export);
			// },
		);

		// if($this->utils->isEnabledFeature('show_risk_score')){
		// 	$columns[] = array(
		// 		'dt' => $i++, # 9
		// 		'alias' => 'risk_level',
		// 		'select' => 'playerdetails.playerId',
		// 		'formatter' => function ($d) use ($is_export,$risk_score_model) {
		// 			if ($is_export) {
		// 				return $risk_score_model->getPlayerCurrentRiskLevel($d) && $risk_score_model->generate_total_risk_score($d) ? $risk_score_model->getPlayerCurrentRiskLevel($d) .' / '. $risk_score_model->generate_total_risk_score($d) : lang('lang.norecyet');
		// 			}else{
		// 				return $risk_score_model->getPlayerCurrentRiskLevel($d) && $risk_score_model->generate_total_risk_score($d) ? $risk_score_model->getPlayerCurrentRiskLevel($d) .' / '. $risk_score_model->generate_total_risk_score($d) : '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
		// 			}
		// 		},
		// 		'name' => lang("tagged_players.risk_level_score"),
		// 	);
		// }

		// if($this->utils->isEnabledFeature('show_kyc_status')){
		// 	$columns[] = array(
		// 		'dt' => $i++,
		// 		'alias' => 'kyc_level',
		// 		'select' => 'playerdetails.playerId',
		// 		'formatter' => function ($d) use ($is_export,$player_kyc,$kyc_status_model) {
		// 			if ($is_export) {
		// 				return $player_kyc->getPlayerCurrentKycLevel($d) && $kyc_status_model->getPlayerCurrentStatus($d) ? $player_kyc->getPlayerCurrentKycLevel($d) .' / '. $kyc_status_model->getPlayerCurrentStatus($d): lang('lang.norecyet');
		// 			}else{
		// 				return $player_kyc->getPlayerCurrentKycLevel($d) && $kyc_status_model->getPlayerCurrentStatus($d) ? $player_kyc->getPlayerCurrentKycLevel($d) .' / '. $kyc_status_model->getPlayerCurrentStatus($d): '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
		// 			}
		// 		},
		// 		'name' => lang("KYC Level/Rate Code"),
		// 	);
		// }

		$columns[] = array(
			'dt' => $i++, # 11
			'alias' => 'lastLoginTime',
			'select' => 'player.lastLoginTime',
			'name' => lang("player.102"),
		);

		$columns[] = array(
			'dt' => $i++, # 12
			'alias' => 'createdOn',
			'select' => 'player.createdOn',
			'name' => lang("player.43"),
			'formatter' => function ($d) use ($is_export) {
				if ($is_export) {
					return (!$d || strtotime($d) < 0) ? lang('lang.norecyet') : date('Y-m-d H:i:s', strtotime($d));
				} else {
					return (!$d || strtotime($d) < 0) ? '<i class="text-muted">' . lang('lang.norecyet') . '</i>' : date('Y-m-d H:i:s', strtotime($d));
				}
			},

		);

		$columns[] = array(
            'dt' => $i++, # 13
            'alias' => 'blocked',
            'select' => 'player.blocked' ,
            'name' => lang("lang.status"),
            'formatter' => function ($d, $row) use ($is_export){
                $formatter = 1;
                return  $this->utils->getPlayerStatus($row['playerId'],$formatter,$d,$is_export);
            },
        );

        $columns[] = array(
            'dt' => $i++, # 14
            'alias' => 'blocked_status_last_update',
            'select' => 'player.blocked_status_last_update' ,
            'name' => lang("tagged_players.account_status_last_update"),
            'formatter' => function ($d, $row) use ($is_export){
                if ($is_export) {
					return (!$d || strtotime($d) < 0) ? lang('lang.norecyet') : date('Y-m-d H:i:s', strtotime($d));
				} else {
					return (!$d || strtotime($d) < 0) ? '<i class="text-muted">' . lang('lang.norecyet') . '</i>' : date('Y-m-d H:i:s', strtotime($d));
				}
            },
        );

        $columns[] = array(
			'alias' => 'isDeleted',
			'select' => 'playertag.isDeleted',
			'name' => lang("Is Deleted"),
		);

  //       $columns[] = array(
		// 	'dt' => $i++, # 15
		// 	'alias' => 'deletedAt',
		// 	'select' => 'playertag.deletedAt',
		// 	'name' => lang("Delete At"),
		// 	'formatter' => function ($d , $row) use ($is_export) {
		// 		if($row['isDeleted']){
		// 			return $d;
		// 		}
		// 		return "N/A";
		// 	},
		// );

		$table = 'playertag';
		$joins = array(
			'player' => 'player.playerId = playertag.playerId',
			'playerdetails' => 'player.playerid = playerdetails.playerid',
			'playerlevel' => 'player.playerid = playerlevel.playerid',
			//'playertag' => 'player.playerid = playertag.playerid',
			'tag' => 'playertag.tagid = tag.tagid',
		);

		/*$table = 'player';
		$joins = array(
			'playeraccount' => 'player.playerid = playeraccount.playerid',
			'playerdetails' => 'player.playerid = playerdetails.playerid',
			'playerlevel' => 'player.playerid = playerlevel.playerid',
			'playertag' => 'player.playerid = playertag.playerid',
			'vipsettingcashbackrule' => 'playerlevel.playergroupid = vipsettingcashbackrule.vipsettingcashbackruleid',
			'vipsetting' => 'vipsettingcashbackrule.vipsettingid = vipsetting.vipsettingid',
			'tag' => 'playertag.tagid = tag.tagid',
			//'playertag as ptag' => 'player.playerid = ptag.playerid',
		);*/

		$where[] = "playertag.tagId != '0'";
		$group_by=['playertag.playerTagId'];

		$this->utils->debug_log(__METHOD__, [ 'selected_tags' => isset($input['selected_tags']) ? $input['selected_tags'] : '(not set)' ]);

		if (isset($input['selected_tags']) && !empty($input['selected_tags'])) {
			$selected_tags =  is_array($input['selected_tags']) ? $input['selected_tags'] : [ $input['selected_tags'] ];
			$bind_marks = implode(',', array_fill(0, count($selected_tags), '?'));
			$where[] = "playertag.tagId IN ( $bind_marks )";
			foreach ($selected_tags as $tag) {
				$values[] = $tag;
			}
		}

		if (isset($input['player_tag_ids']) && !empty($input['player_tag_ids'])) {
			$selected_tags_ids =  is_array($input['player_tag_ids']) ? $input['player_tag_ids'] : [ $input['player_tag_ids'] ];
			$bind_marks = implode(',', array_fill(0, count($selected_tags_ids), '?'));
			$where[] = "playertag.playerTagId IN ( $bind_marks )";
			foreach ($selected_tags_ids as $playerTagid) {
				$values[] = $playerTagid;
			}
		}

		if (isset($input['playerTagId']) && !empty($input['playerTagId'])) {
			$selected_tags_ids =  is_array($input['playerTagId']) ? $input['playerTagId'] : [ $input['playerTagId'] ];
			$bind_marks = implode(',', array_fill(0, count($selected_tags_ids), '?'));
			$where[] = "playertag.playerTagId IN ( $bind_marks )";
			foreach ($selected_tags_ids as $playerTagid) {
				$values[] = $playerTagid;
			}
		}

		if (isset($input['player_tag_to_remove']) && !empty($input['player_tag_to_remove'])) {
			$player_tags_to_remove =  is_array($input['player_tag_to_remove']) ? $input['player_tag_to_remove'] : [ $input['player_tag_to_remove'] ];
			$bind_marks = implode(',', array_fill(0, count($player_tags_to_remove), '?'));
			$where[] = "tag.tagid IN ( $bind_marks )";
			foreach ($player_tags_to_remove as $tagId) {
				$values[] = $tagId;
			}
		}

		/*if (isset($input['should_have_tags']) && !empty($input['should_have_tags'])) {
			$should_have_tags =  is_array($input['should_have_tags']) ? $input['should_have_tags'] : [ $input['should_have_tags'] ];
			$bind_marks = implode(',', array_fill(0, count($should_have_tags), '?'));
			$where[] = "ptag.tagId IN ( $bind_marks )";
			foreach ($should_have_tags as $tag) {
				$values[] = $tag;
			}
		}*/

		if (isset($input['username']) && !empty($input['username'])) {
			$where[] = "player.username like ?";
			$values[] = "%{$input['username']}%";
		}

		if (isset($input['vip_level']) && !empty($input['vip_level'])) {
			$where[] = "player.levelId = ?";
			$values[] = $input['vip_level'];
		}

		if ( ! empty($input['search_reg_date'])) {
            if (isset($input['date_from'], $input['date_to'])) {
                $where[] = "playertag.createdon BETWEEN ? AND ?";
                $values[] = $input['date_from'];
                $values[] = $input['date_to'];
            }
        }

        if (!empty($input['search_last_update_date'])) {
        	if (isset($input['last_update_from'], $input['last_update_to'])) {
        		$where[] = "player.blocked_status_last_update BETWEEN ? AND ?";
        		$values[] = $input['last_update_from'];
        		$values[] = $input['last_update_to'];
        	}
        }

        $having=[];
        $distinct=true;
        $external_order=[];
        $not_datatable='';
		$countOnlyField='playertag.playerTagId';

		if($is_export){
            $this->data_tables->options['is_export']=true;
            if(empty($csv_filename)){
                $csv_filename=$this->utils->create_csv_filename(__FUNCTION__);
            }
            $this->data_tables->options['csv_filename']=$csv_filename;
		}

		// $this->config->set_item('debug_data_table_sql', true);
		$result = $this->data_tables->get_data($request, $columns, $table, $where, $values, $joins, $group_by, $having,	$distinct, $external_order, $not_datatable, $countOnlyField);
		// $result['sql'] = $this->data_tables->last_query;

		if($is_export){
		    //drop result if export
			return $csv_filename;
		}
		return $result;
	}

	public function player_realtime_balance($request, $is_export = false, $csv_filename = null) {
        $readOnlyDB = $this->getReadOnlyDB();
        $this->load->library('data_tables', array('DB' => $readOnlyDB));
		$this->load->library('rolesfunctions');
        $this->load->model(array('player_model','functions_report_field'));
        $this->load->helper(['player_helper']);
		$this->data_tables->is_export = $is_export;
		$input = $this->data_tables->extra_search($request);
		$fieldPermissionFlag =  $this->config->item('enable_roles_report',false);

		$customerPermission = [];
		if ($fieldPermissionFlag) {
			$roleInfo = $this->rolesfunctions->getRoleByUserId( $is_export? $request['extra_search']['caller'] : $this->authentication->getUserId());
			if (!empty($roleInfo)) {
				$fieldsPermission= $this->functions_report_field->getFunctionPermission($roleInfo['roleId'],'player_balance_report');
				if (!$fieldsPermission['exist']) {
					$customerPermission = array_keys($this->config->item('player_balance_report','roles_report')?:[]);
				} else {
					$customerPermission = $fieldsPermission['permission'];
				}
			}
		}

        $i          = 0;
        $input      = $this->data_tables->extra_search($request);
        $joins      = array();
        $where      = array();
        $values     = array();
        $group_by   = array();
        $having     = array();

        # DEFINE TABLE COLUMNS ########################################################################################################################################################################
        $columns = array();

        $columns[] = array(
            'alias' => 'playerId',
            'select' => 'player.playerId',
		);

		$columns[] = array(
			'dt' => $i++,
			'alias' => 'username',
			'select' => 'player.username',
			'name' => lang('Player Username'),
			'formatter' => function($d, $row) use ($is_export) {
				if (!$is_export) {
					return '<a href=/player_management/userInformation/'.$row['playerId'].'>'.$d.'</a>';
				} else {
					return $d;
				}
			}
		);
		

		if (!$fieldPermissionFlag || in_array('registration_date',$customerPermission)) {
			$columns[] = array(
				'dt' => $i++,
				'alias' => 'registration_date',
				'select' => 'player.createdOn',
				'formatter' => 'dateTimeFormatter',
				'name' => lang('player.38'),
			);
		}

		if (!$fieldPermissionFlag || in_array('deposit_date',$customerPermission)) {
			$columns[] = array(
				'dt' => $i++,
				'alias' => 'last_deposit_date',
				'select' => 'player_last_transactions.last_deposit_date',
				'formatter' => 'dateTimeFormatter',
				'name' => lang('player_list.search_latest_deposit_date'),
			);
		}

		if (!$fieldPermissionFlag || in_array('lastLoginTime',$customerPermission)) {
			$columns[] = array(
				'dt' => $i++,
				'alias' => 'lastLoginTime',
				'select' => 'player_runtime.lastLoginTime',
				'formatter' => 'dateTimeFormatter',
				'name' => lang('Last Sign In Date'),
			);
		}

		if (!$fieldPermissionFlag || in_array('tag',$customerPermission)) {
			$columns[] = array(
				'dt' => $i++,
				'alias' => 'tag',
				'select' => 'player.playerId',
				'name' => lang('Player Tag'),
				'formatter' => function($d, $row) use ($is_export) {
					return player_tagged_list($d, $is_export);
				}
			);
		}

		if (!$fieldPermissionFlag || in_array('total_balance',$customerPermission)) {
			$columns[] = array(
				'dt' => $i++,
				'alias' => 'total_balance',
				'select' => "player.total_total",
				'name' => lang('report.balance.player.balance'),
				'formatter' => function($d, $row) use ($is_export) {
					return number_format($d, 2, '.', ',');
				}
			);
		}

        # FILTER ######################################################################################################################################################################################

        $table = 'player';
        $joins = array(
			'player_last_transactions' => 'player_last_transactions.player_id = player.playerId',
			'player_runtime' => 'player_runtime.playerId = player.playerId'
		);

        if (!empty($input["username"])) {
        	$where[] = 'username = "'.$input["username"].'"';
        }

        if (!empty($input["total_balance"])) {
        	$where[] = "total_total <= ".$input["total_balance"];
        }

		if (!empty($input["total_balance_grater_then"])) {
        	$where[] = "total_total >= ".$input["total_balance_grater_then"];
        }

		if (isset($input['tag_list'])) {
			$tag_list = $input['tag_list'];

			if(is_array($tag_list)) {
				$notag = array_search('notag', $tag_list);
				if($notag !== false) {
					$where[] = 'player.playerId IN (SELECT DISTINCT playerId FROM playertag)';
					unset($tag_list[$notag]);
				}
			} elseif ($tag_list == 'notag') {
				$where[] = 'player.playerId IN (SELECT DISTINCT playerId FROM playertag)';
				$tag_list = null;
			}

			if (!empty($tag_list)) {
				$tagList = is_array($tag_list) ? implode(',', $tag_list) : $tag_list;
				$where[] = 'player.playerId NOT IN (SELECT DISTINCT playerId FROM playertag WHERE playertag.tagId IN ('.$tagList.'))';
			}
		}

		if (!empty($input['search_reg_date'])) {
            if (isset($input['registration_date_from'], $input['registration_date_to'])) {
                $where[] = "createdOn BETWEEN ? AND ?";
                $values[] = $input['registration_date_from'];
                $values[] = $input['registration_date_to'];
            }
        }

        if($is_export){
            $this->data_tables->options['is_export'] = true;
            if(empty($csv_filename)){
                $csv_filename = $this->utils->create_csv_filename(__FUNCTION__);
            }
            $this->data_tables->options['csv_filename'] = $csv_filename;
		}


        # OUTPUT ######################################################################################################################################################################################
        $result = $this->data_tables->get_data($request, $columns, $table, $where, $values, $joins, $group_by, $having, false);

        if($is_export){
			return $csv_filename;
		}


        $summary = $this->data_tables->summary($request, $table, $joins, 'SUM(total_total) sum_balance', null, $columns, $where, $values);
		$result['sum_balance'] = $this->utils->formatCurrencyNoSym($summary[0]['sum_balance']);
        return $result;
	}

	/**
	 * script for sub query (Sub-)Wallet
	 *
	 * @param array $row The row after query.
	 * @param integer $gamePlatformId The Sub-Wallet ID same as PlatformId, Zero means Main Wallet.
	 * @return string The formated currency.
	 */
 	private function _getDailyPlayerWalletBalanceByDate($row, $gamePlatformId, &$dailyPlayerWalletBalance=[]){

		$balance = 0;

		if( ! empty($row['playerId']) && !isset( $dailyPlayerWalletBalance[$row['playerId']] ) ){

			// dpwbbd=DailyPlayerWalletBalanceByDate
			$dpwbbd = $this->player_model->getDailyPlayerWalletBalanceByDate($row['game_date'], $row['playerId']);
			if( empty($dpwbbd) ){
				$dailyPlayerWalletBalance[$row['playerId']] = [];
			}else{
				$dailyPlayerWalletBalance[$row['playerId']] = $dpwbbd[$row['playerId']];
			}

		}

		if( ! empty($row['playerId']) && ! empty($dailyPlayerWalletBalance[$row['playerId']][$gamePlatformId]) ){
			$balance = $dailyPlayerWalletBalance[$row['playerId']][$gamePlatformId];
		}

		// if( ! empty($gamePlatformId) ){
		// 	$dailyBalanceRow = $this->daily_balance->getDailyBalanceForSubWallet($row['playerId'], $gamePlatformId, $row['game_date']);
		// }else{
		// 	$dailyBalanceRow = $this->daily_balance->getDailyBalanceForMainWallet($row['playerId'], $gamePlatformId, $row['game_date']);
		// }
		// if( ! empty($dailyBalanceRow)){
		// 	$balance = $dailyBalanceRow->balance;
		// }

		return $this->utils->formatCurrencyNoSym($balance);
	}

	public function player_daily_balance($request, $is_export = false, $csv_filename = null) {
        $readOnlyDB = $this->getReadOnlyDB();
        $this->load->library('data_tables', array('DB' => $readOnlyDB));
        $this->load->model(array('player_model','daily_balance'));
        $this->data_tables->is_export = $is_export;
        $input = $this->data_tables->extra_search($request);

		$_database = '';
		$_extra_db_name = '';
		$is_daily_balance_in_extra_db = $this->utils->_getDailyBalanceInExtraDbWithMethod(__METHOD__, $this->utils->getActiveTargetDB(), $_extra_db_name );
		if($is_daily_balance_in_extra_db){
			$_database = "`{$_extra_db_name}`";
			$_database .= '.'; // ex: "og_OGP-26371_extra."
		}


        $i          = 0;
        $input      = $this->data_tables->extra_search($request);
        $joins      = array();
        $where      = array();
        $values     = array();
        $group_by   = array();
        $having     = array();

        $dateFilter = $this->utils->getYesterdayForMysql();
        if (!empty($input["date_filter"])) {
        	$dateFilter = $input["date_filter"];
        }
		$dailyPlayerWalletBalance = [];
        # DEFINE TABLE COLUMNS ########################################################################################################################################################################
        $columns = array();

        $columns[] = array(
            'alias' => 'playerId',
            'select' => 'player.playerId',
        );

        $columns[] = array(
            'dt' => $i++,
            'alias' => 'game_date',
			'select' => 'daily_balance.game_date',
			// 'select' => '"'.$dateFilter.'"',
    		'formatter' => 'dateFormatter',
            'name' => lang('Date'),
        );

        $columns[] = array(
            'dt' => $i++,
            'alias' => 'username',
            'select' => 'player.username',
            'formatter' => 'defaultFormatter',
            'name' => lang('Player Username'),
        );

        $columns[] = array(
            'dt' => $i++,
            'alias' => 'total_balance',
            'select' => 'balance',
            'formatter' => 'currencyFormatter',
            'name' => lang('Total Balance')
        );

        $columns[] = array(
            'dt' => $i++,
            'alias' => 'main_wallet',
            'select' => 'player.playerId',
            'name' => lang('Main Wallet Balance'),
            'formatter' => function($d, $row) use (&$dailyPlayerWalletBalance){
				return $this->_getDailyPlayerWalletBalanceByDate($row, '0', $dailyPlayerWalletBalance);
            }
        );

        // $gamePlatforms = $this->utils->getActiveGameSystemList();
        $gamePlatforms = $this->utils->getAllGameSystemList();

        if (!empty($gamePlatforms)) {
        	foreach ($gamePlatforms as $key => $gamePlatform) {
        		$gamePlatformId = $gamePlatform['id'];
        		$columns[] = array(
		            'dt' => $i++,
		            'alias' => 'sub_wallet_' . $gamePlatformId,
		            'select' => 'player.playerId',
		            'name' => $gamePlatform['system_code'],
		            'formatter' => function($d, $row) use ($gamePlatformId, &$dailyPlayerWalletBalance) {
						return $this->_getDailyPlayerWalletBalanceByDate($row, $gamePlatformId, $dailyPlayerWalletBalance);
		            }
		        );
        	}
        }

        # FILTER ######################################################################################################################################################################################

		$table = 'player';

		$TYPE_MAIN_AND_SUB = Daily_balance::TYPE_MAIN_AND_SUB;
		$joins[$_database. 'daily_balance USE INDEX(idx_game_date, idx_type)'] = <<<EOF
player.playerId = daily_balance.player_id AND daily_balance.type = $TYPE_MAIN_AND_SUB AND daily_balance.game_date = "$dateFilter"
EOF;
        $where[] = 'player.deleted_at IS NULL';

        if (!empty($input["username"])) {
        	$where[] = 'player.username = "'.$input["username"].'"';
        }

        if (!empty($input["total_balance"])) {
        	$where[] = 'daily_balance.balance >= '.$input["total_balance"];
        }

        if (!empty($input['tag_list'])) {
        	$tagList = is_array($input['tag_list']) ? implode(',', $input["tag_list"]) : $input["tag_list"];
        	$where[] = 'player.playerId NOT IN (SELECT DISTINCT playerId FROM playertag WHERE playertag.tagId IN ('.$tagList.'))';
        }

        if($is_export){
            $this->data_tables->options['is_export'] = true;
            if(empty($csv_filename)){
                $csv_filename=$this->utils->create_csv_filename(__FUNCTION__);
            }
            $this->data_tables->options['csv_filename'] = $csv_filename;
		}

		$distinct = false;
		$external_order = [];
		$not_datatable ='';
		$countOnlyField='';
		$innerJoins = [];
		$innerJoins[] = $_database. 'daily_balance USE INDEX(idx_game_date, idx_type)'; // same as key of $joins.
        # OUTPUT ######################################################################################################################################################################################
        $result = $this->data_tables->get_data($request, $columns, $table, $where, $values, $joins, $group_by, $having, $distinct, $external_order, $not_datatable, $countOnlyField, $innerJoins);

        if($is_export){
			return $csv_filename;
		}

		if( ! empty($this->data_tables->last_query) ){
			$result['sql'] = $this->data_tables->last_query;
		}
        return $result;
    } // EOF public function player_daily_balance

    public function playerAttachmentFileList($request, $is_export = false){
		$readOnlyDB = $this->getReadOnlyDB();
		$this->load->library('data_tables', array("DB" => $readOnlyDB));
		$this->load->model(['risk_score_model','kyc_status_model']);
		$this->load->helper(['player_helper']);

		$input = $this->data_tables->extra_search($request);
		$kyc_status_model = $this->kyc_status_model;
		$risk_score_model = $this->risk_score_model;
		$player_kyc       = $this->player_kyc;

		$i=0;
		$where = array();
		$values = array();
		$joins = array();
		$joins['playerdetails'] = 'player.playerId = playerdetails.playerId';
		$joins['player_attached_proof_file'] = 'player.playerId = player_attached_proof_file.player_id';
		$joins['playertag'] = 'playertag.playerId = player.playerId';
		$joins['attached_file_status afs'] = 'afs.player_id = player.playerId';

		$group_by = array('player.playerId');

		$columns[] = array(
			'select' => 'playerdetails.proof_filename',
			'alias' => 'proof_filename',
		);

		$columns[] = array(
			'dt' => $i++,
			'alias' => 'playerId',
			'select' => 'player.playerId',
			'formatter' => 'defaultFormatter',
			'name' => lang('ID'),
		);

		if(!$is_export){

			$columns[] = array(
				'dt' => $i++,
				'alias' => 'action',
				'select' => '1',
				'formatter' => function ($d, $row){

	                $output = '<a onclick="modal(\'/player_management/player_attach_document/' . $row['playerId'] . '/attached_file_list\',\'' . lang('Attached document') . '\')" href="javascript:void(0);" data-toggle="tooltip" title="View attached file"><i class="fa fa-search"></i></a>';

					return $output;
				},
				'name' => lang('lang.action'),
			);
		}

		$columns[] = array(
			'dt' => $i++,
			'alias' => 'username',
			'select' => 'player.username',
			'formatter' => function ($d, $row) use ($is_export) {

				if($is_export) return $d;

				return '<a href="/player_management/userInformation/' . $row['playerId'] . '">' . $d . '</a>';
			},
			'name' => lang('player.01'),
		);


		if(!isset($input['show_logs'])){

			$columns[] = array(
				'dt' => $i++,
				'alias' => 'last_update_date',
				'select' => 'MAX(afs.updated_at)',
				'formatter' => function ($d, $row) {
					return !empty($d) ? $d : lang('lang.norecyet');
				},
				'name' => lang('last_update_date'),
			);

			$columns[] = array(
				'select' => '(CONCAT_WS("|",MAX(afs.id), "SUB_QUERY"))', // MAX(afs.status) Not real MAX() by id. It's max by string.
				'alias' => 'latest_status',
			);
		}
		else{
			$columns[] = array(
				'dt' => $i++,
				'alias' => 'last_update_date',
				'select' => 'afs.updated_at',
				'formatter' => function ($d, $row) {
					return !empty($d) ? $d : lang('lang.norecyet');
				},
				'name' => lang('last_update_date'),
			);

			$columns[] = array(
				'select' => 'CONCAT_WS("|",afs.id, afs.status)',
				'alias' => 'latest_status',
			);

			$group_by = array('afs.id');
		}

        $columns[] = array(
            'dt' => $i++,
            'alias' => 'id_card_number',
            'select' => 'playerdetails.id_card_number',
            'name' => lang('ID Card Number'),
        );

		$proof_attachment_types = $this->utils->getconfig('proof_attachment_type');

		if(!empty($proof_attachment_types) && is_array($proof_attachment_types)){
			foreach ($proof_attachment_types as $attachment_type_key => $attachment_type) {
				$columns[] = array(
					'dt' => $i++,
					'alias' => $attachment_type_key,
					'select' => 'afs.id',
					'formatter' => function ($d, $row) use($attachment_type, $is_export, $readOnlyDB){

						$status_array = explode('|', $row['latest_status']);

						// -- get all available verification status from config
						$verification_list = $this->utils->getConfig('verification');

						$remarks = [
							self::Remark_No_Attach      => 'text-secondary',
							self::Remark_Wrong_attach   => 'text-danger',
							self::Remark_Verified 		=> 'text-success',
							self::Remark_Not_Verified   => 'text-warning',
						];

						$default = "<span class='".$remarks[self::Remark_No_Attach]."'>" . (isset($verification_list[self::Remark_No_Attach]['description']) ? $verification_list[self::Remark_No_Attach]['description'] : 'No Attachment'). "</span>";

						if(empty($status_array) || !isset($status_array[1])) return $default;

						if($status_array[1] == 'SUB_QUERY'){
							$attached_file_status_id = $status_array[0];

							$query = $readOnlyDB->query("SELECT `status` FROM attached_file_status WHERE id = ?", array(
								$attached_file_status_id,
							));
							$row = $query->row_array();
							$status_array[1] = $row['status']; // attached_file_status

						}

						$status = json_decode($status_array[1]);

						foreach ($verification_list as $key => $value) {
							// patch for A PHP Error was encountered | Severity: Notice | Message: Array to string conversion
							$attachment_tag = $attachment_type['tag'];
							if( ! empty($status->$attachment_tag) ){
								if($status->$attachment_tag == $key) {

									if($is_export)
										$default = $value['description'];
									else
										$default = "<span class='".$remarks[$key]."'>" . $value['description']. "</span>";

									break;
								}
							}
						}

						return $default;
					},
					'name' => lang($attachment_type['description']),
				);
			}
		}


		$columns[] = array(
			'dt' => $i++,
			'alias' => 'risk_level',
			'select' => 'playerdetails.risk_score_level',
			'formatter' => function ($d, $row) use($risk_score_model){

				if(empty($d)) return lang('N/A');

				$risk_score_info = json_decode($d, true);

				if(!isset($risk_score_info['risk_score'], $risk_score_info['risk_level'])) return lang('N/A');

				return $risk_score_info['risk_score'] . ' / ' . $risk_score_info['risk_level'];
			},
			'name' => lang('Risk Level'),
		);

		$columns[] = array(
			'dt' => $i++,
			'alias' => 'kyc_level',
			'select' => 'playerdetails.kyc_status_id',
			'formatter' => function ($d, $row) use($kyc_status_model){

				if(empty($d)) return lang('N/A');

				$kyc_info = $kyc_status_model->getKycStatusInfo($d);

				if(empty($kyc_info) || !is_array($kyc_info)) return lang('N/A');

				return $kyc_info['kyc_lvl'].' / '.$kyc_info['rate_code'];
			},
			'name' => lang('KYC Level'),
		);

		$columns[] = array(
			'dt' => $i++,
			'alias' => 'tag',
			'select' => 'playertag.tagId',
			'formatter' => function ($d, $row) use($is_export){
				return player_tagged_list($row['playerId'], $is_export);
			},
			'name' => lang('Tag'),
		);

		$table = 'player';

		###### QUERY FILTER #####
		if(!empty($input['username'])){
			$where[] = 'player.username = ?';
			$values[] = $input['username'];
		}

		if (!empty($input['tag'])) {
			$where[] = "playertag.tagId = ?";
        	$values[] = $input['tag'];
		}

		if (!empty($input['kyc_level_id'])) {
			$where[] = "playerdetails.kyc_status_id = ?";
        	$values[] = $input['kyc_level_id'];
		}

        if (!empty($input['id_card_number'])) {
            $where[] = "playerdetails.id_card_number LIKE ?";
            $values[] = '%' . $input['id_card_number'] . '%';
        }

		if (isset($input['risk_score_level'])) {
			$where[] = "playerdetails.risk_score_level LIKE ?";
			$values[] = '%"' . $input['risk_score_level'] . '"%';
		}


        $where[] = "playerdetails.proof_filename IS NOT NULL";


		if(isset($input['can_attachment'])){
            $where_verification = "'".self::Verification_Income."',
                                    '".self::Verification_Adress."',
                                    '".self::Verification_Photo_ID."',
                                    '".self::Verification_Deposit_Withrawal."'";

            $where_verification_foreach = '';

            if(is_array($input['can_attachment']) && !empty($input['can_attachment'])){

                foreach ($input['can_attachment'] as $input_attachment_key => $input_attachment) {
                    if(isset($input[$input_attachment])){
                        $where[] = "afs.status LIKE ?";
                        $values[] = '%"' . $input_attachment . '":"'.$input[$input_attachment].'"%';
                        $where_verification_foreach .= "'".array_shift($input['can_attachment'])."',";
                    }
                }

            }else{
                if(isset($input[$input['can_attachment']])){
                    $where[] = "afs.status LIKE ?";
                    $values[] = '%"' . $input['can_attachment'] . '":"'.$input[$input['can_attachment']].'"%';
                    $where_verification_foreach .= "'".$input['can_attachment']."',";
                }
            }

            if(!empty($where_verification_foreach)){
                $where_verification_foreach = rtrim($where_verification_foreach, ',');
                $where_verification = $where_verification_foreach;
            }

            $where[] = "player_attached_proof_file.tag IN(" . $where_verification .")";

        }else{
            $where[] = "player_attached_proof_file.tag = ?";
            $values[] = '';
        }

		if (!empty($input['can_search_last_update_date']) && isset($input['last_update_date_from']) && isset($input['last_update_date_to'])) {
			$where[] = 'afs.updated_at >= ? AND afs.updated_at <= ?';
			$values[] = $input['last_update_date_from'];
			$values[] = $input['last_update_date_to'];
		}

		##### END OF QUERY FILTER #########

		if($is_export){
            $this->data_tables->options['is_export']=true;
            if(empty($csv_filename)){
                $csv_filename=$this->utils->create_csv_filename(__FUNCTION__);
            }
            $this->data_tables->options['csv_filename']=$csv_filename;
		}

		$result = $this->data_tables->get_data($request, $columns, $table, $where, $values, $joins, $group_by);

		$result['list_last_query'] = $this->data_tables->last_query;

		if($is_export){
		    //drop result if export
			return $csv_filename;
		}

		return $result;
	}

	function show_active_players($request, $is_export = false, $csv_filename = null)
    {
        $readOnlyDB = $this->getReadOnlyDB();
        $this->load->library('data_tables', array('DB' => $readOnlyDB));
        $this->load->model(array('player_model'));
		$this->load->helper(['player_helper']);
        $this->data_tables->is_export = $is_export;
        $input = $this->data_tables->extra_search($request);

        $i          = 0;
        $input      = $this->data_tables->extra_search($request);
        $joins      = [];
        $where      = [];
        $values     = [];
        $group_by   = [];
        $having     = [];
        $columns        = [];

        # DEFINE TABLE COLUMNS ########################################################################################################################################################################
         $columns[] = array(
            'alias' => 'player_id',
            'select' => 'total_player_game_day.player_id',
        );

        $columns[] = array(
            'dt' => $i++,
            'alias' => 'game_date',
            'select' => 'date',
            'name' => lang('Date'),
        );

		$columns[] = array(
            'dt' => $i++,
            'alias' => 'username',
            'select' => 'player.username',
            'formatter' => 'defaultFormatter',
            'name' => lang('Player Username'),
            'formatter' => function ($d, $row) use ($is_export){
				if ($is_export) {
					return ($d ? $d : lang('N/A'));
				} else {
					return '<a href="' . site_url('player_management/userInformation/' . $row['player_id']) . '">' . $d . '</a>';
				}

            }
        );
		//OGP-25040
        $columns[] = array(
            'dt' => $i++,
			'alias' => 'player_tag',
			'select' => 'player.playerId',
			'formatter' => function ($d, $row) use ($is_export) {
				if ($is_export) {
					$tagname = player_tagged_list($d, $is_export);
					return ($tagname ? $tagname : lang('N/A'));
				} else {
					$tagname = player_tagged_list($d);
					return $tagname ? $tagname : '<i class="text-muted">' . lang('N/A') . '</i>';
				}
				return $d;
			},
			'name' => lang('Player Tag'),
        );

		//OGP-32137
		$columns[] = array(
            'dt' => $i++,
            'alias' => 'sign_up_date',
            'select' => 'player.CreatedOn',
            'name' => lang('player.38'),
            'formatter' => function ($d, $row) {
                if (!empty($d)) {
                    return $d;
                } else {
                    return lang('lang.norecyet');
                }
            }
        );

        $columns[] = array(
            'dt' => $i++,
            'alias' => 'affiliate_username',
            'select' => 'affiliates.username',
            'name' => lang('Affiliate Username'),
            'formatter' => function ($d, $row) {
                if ($row['affiliate_username'] != '') {
                        return sprintf('%s', $row['affiliate_username'], $d);
                } else {
                        return lang('lang.norecyet');
                }
            }
        );

        $columns[] = array(
            'dt' => $i++,
            'alias' => 'agent_username',
            'select' => 'agency_agents.agent_name',
            'name' => lang('Agent Username'),
            'formatter' => function ($d, $row) {
                if (!empty($d)) {
                	return $d;
                } else {
                	return lang('lang.norecyet');
                }
            }
        );

        $columns[] = array(
            'dt' => $i++,
            'alias' => 'referrer',
            'select' => 'referrer.username',
            'name' => lang('Referrer'),
            'formatter' => function ($d, $row) {
                if (!empty($d)) {
                    return $d;
                } else {
                    return lang('lang.norecyet');
                }
            }
        );

        # FILTER ######################################################################################################################################################################################
        $table = 'total_player_game_day';
        $joins = [
            'player' => 'player.playerId = total_player_game_day.player_id',
            'affiliates' => 'affiliates.affiliateId = player.affiliateId',
            'agency_agents' => 'player.agent_id = agency_agents.agent_id',
            'player referrer' => 'player.refereePlayerId = referrer.playerId'
        ];

        $where[] = 'player.deleted_at IS NULL';
        $where[] = 'player.username IS NOT NULL';

        if (isset($input['date_start'], $input['date_end'])) {
            $where[] = 'total_player_game_day.date >= "'.$input['date_start'].'" AND total_player_game_day.date  <= "'.$input['date_end'].'"';
        }

        if(!empty($input['username'])){
			$where[] = 'player.username = ?';
			$values[] = $input['username'];
		}

		if (isset($input['tag_list'])) {

            $tag_list = $input['tag_list'];
			$is_include_notag = null;
            if(is_array($tag_list)) {
                $notag = array_search('notag',$tag_list);
                if($notag !== false) {
                    unset($tag_list[$notag]);
					$is_include_notag = true;
                }else{
					$is_include_notag = false;
				}

            } elseif ($tag_list == 'notag') {
                $tag_list = null;
				$is_include_notag = true;
            }

			$where_fragments = [];
			if($is_include_notag){
				$where_fragments[] = 'player.playerId NOT IN (SELECT DISTINCT playerId FROM playertag)';
			}

            if ( ! empty($tag_list) ) {
                $tagList = is_array($tag_list) ? implode(',', $tag_list) : $tag_list;
				$where_fragments[] =  'player.playerId IN (SELECT DISTINCT playerId FROM playertag WHERE playertag.tagId IN ('.$tagList.'))';
            }
			if( ! empty($where_fragments) ){
				$where[] = ' ('. implode(' OR ', $where_fragments ). ') ';
			}
        }

        $group_by[] = 'total_player_game_day.player_id';

        if($is_export){
            $this->data_tables->options['is_export']=true;
            if(empty($csv_filename)){
                $csv_filename=$this->utils->create_csv_filename(__FUNCTION__);
            }
            $this->data_tables->options['csv_filename'] = $csv_filename;
        }


        # OUTPUT ######################################################################################################################################################################################
        $result = $this->data_tables->get_data($request, $columns, $table, $where, $values, $joins, $group_by, $having);

        if($is_export){
            return $csv_filename;
        }

        $summary = $this->data_tables->summary($request, $table, $joins, 'affiliates.username affiliate_total, agency_agents.agent_name agency_total, referrer.username referrer_total', $group_by, $columns, $where, $values);

        $newSummary = [];

        if (!empty($summary)) {
            $newSummary['affiliate_total'] 		= ['total_count' => 0, 'description' => lang('affiliate_total'), ];
            $newSummary['agency_total'] 		= ['total_count' => 0, 'description' => lang('agency_total'), ];
            $newSummary['referrer_total'] 		= ['total_count' => 0, 'description' => lang('referrer_total'), ];
            $newSummary['direct_players_total'] = ['total_count' => 0, 'description' => lang('direct_players_total'), ];

            foreach ($summary as $summaryKey => $items) {
            	$lastKey = key(array_slice( $items, -1, 1, TRUE ) );
            	$isDirectPlayer = true;

	            foreach ($items as $itemsKey => $value) {
	            	# add to direct player if no aff, agent or reffer
	            	if (($itemsKey == $lastKey) && $isDirectPlayer && empty($value)) {
	            		$newSummary['direct_players_total']['total_count'] += 1;
	            	}

	            	if (!in_array($itemsKey, ['affiliate_total', 'agency_total', 'referrer_total']) || empty($value)) {
	            		continue;
	            	}

	            	# if pass in the above condition mean is the player is direct
	            	$isDirectPlayer = false;
                    if (!isset($newSummary[$itemsKey][$value])) {
                        $newSummary[$itemsKey][$value] = 1;
                    } else {
                        $newSummary[$itemsKey][$value] += 1;
                    }
	            }
            }

            foreach ($newSummary as $key => $value) {
                # get total
                $newSummary[$key]['total_count'] = array_sum($newSummary[$key]);
            }
        }

        $result['summary'] = $newSummary;

        return $result;
	}

	public function shopping_point_report($request, $viewPlayerInfoPerm, $is_export){
        $readOnlyDB = $this->getReadOnlyDB();

        $this->load->library('data_tables', array("DB" => $readOnlyDB));
        $this->load->model(array('Point_transactions', 'player_model'));
        $this->load->helper(['player_helper']);
        $point_transactions= $this->Point_transactions;

        // $sum_add_bonus_as_manual_bonus = $this->utils->getConfig('sum_add_bonus_as_manual_bonus');
        // $sum_deposit_promo_bonus_as_total_deposit_bonus = $this->utils->getConfig('sum_deposit_promo_bonus_as_total_deposit_bonus');
        // $player_kyc = $this->player_kyc;
        // $kyc_status_model = $this->kyc_status_model;
        // $risk_score_model = $this->risk_score_model;

        $this->data_tables->is_export = $is_export;

        $table = 'point_transactions';
        $joins = array();
        $where = array();
        $values = array();
        $group_by = array();
		$having = array();
        $joins['player'] = "player.playerId = point_transactions.to_id";
        $joins['vipsettingcashbackrule'] = 'vipsettingcashbackrule.vipsettingcashbackruleId = player.levelId';
        $joins['vipsetting'] = 'vipsetting.vipSettingId = vipsettingcashbackrule.vipSettingId';

        $i = 0;
        $input = $this->data_tables->extra_search($request);

        $date_from = null;
        $date_to = null;
        $playerlvl = null;
        $playerlvls = null;
        if (isset($input['date_from'], $input['date_to'])) {
            $date_from = $input['date_from'];
            $date_to = $input['date_to'];
        }

        # FILTER ######################################################################################################################################################################################

		$show_username            = true;
		$show_player_level        = true;
		$sum_total_point = 0;

		// DEPOSIT_POINT = 1;
		// BET_POINT = 2;
		// WIN_POINT = 3;
		// LOSS_POINT = 4;
		// DEDUCT_POINT = 5;
		// MANUAL_ADD_POINTS = 6;
		// MANUAL_DEDUCT_POINTS = 7;
		// DEDUCT_BET_POINT = 8;

		$action_map = array(
			'Auto'=>[
				Point_transactions::DEPOSIT_POINT,
				Point_transactions::BET_POINT,
				Point_transactions::WIN_POINT,
				Point_transactions::LOSS_POINT,
				Point_transactions::DEDUCT_POINT,
				Point_transactions::DEDUCT_BET_POINT,
			],
			'Manual'=>[
				Point_transactions::MANUAL_ADD_POINTS,
				Point_transactions::MANUAL_DEDUCT_POINTS,
			],
			'Deduct'=>[
			Point_transactions::DEDUCT_POINT,
			Point_transactions::DEDUCT_BET_POINT,
			Point_transactions::MANUAL_DEDUCT_POINTS,
			]
		);
		$transaction_type_map = array(
			Point_transactions::DEPOSIT_POINT=>lang('Deposit'),
			Point_transactions::BET_POINT=>lang('Bet'),
			Point_transactions::WIN_POINT=>lang('Win'),
			Point_transactions::LOSS_POINT=>lang('Loss'),
			Point_transactions::DEDUCT_POINT=>lang('Deduct'),
			Point_transactions::DEDUCT_BET_POINT=>lang('Bet'),
			Point_transactions::MANUAL_ADD_POINTS=>lang('lang.norecyet'),
			Point_transactions::MANUAL_DEDUCT_POINTS=>lang('lang.norecyet'),
		);

        $dateTimeFrom = null;
        $dateTimeTo = null;
        if (isset($input['date_from'], $input['date_to'])) {
            $dateTimeFrom = $input['date_from'];
            $dateTimeTo   = $input['date_to'];

			$where[]  = "point_transactions.created_at >= ? AND point_transactions.created_at <= ?";

            $values[] = $dateTimeFrom;
            $values[] = $dateTimeTo;
        }

        if (isset($input['username'])) {
            $where[] = "player.username LIKE ?";
            $values[] = '%' . $input['username'] . '%';
        }


        if (isset($input['playerlevel'])) {
            $where[] = "player.levelId = ?";
            $values[] = $input['playerlevel'];
        }

        if (isset($input['depamt1'])) {
            $having['source_amount <='] = $input['depamt1'];
        }

        if (isset($input['depamt2'])) {
            $having['source_amount >='] = $input['depamt2'];
        }

        if (isset($input['affiliate_agent'])) {
            switch ($input['affiliate_agent']) {

                case '1': # Not under any Affiliate or Agent
                    $where[] = '(player.agent_id IS NULL OR player.agent_id = 0) AND (player.affiliateId IS NULL OR player.affiliateId = 0)';
                    break;

                case '2': # Under Affiliate Only
                    $where[] = '(player.agent_id IS NULL OR player.agent_id = 0) AND player.affiliateId > 0';
                    break;

                case '3': # Under Agent Only
                    $where[] = 'player.agent_id > 0 AND (player.affiliateId IS NULL OR player.affiliateId = 0)';
                    break;

                case '4': # Under Affiliate or Agent
                    $where[] = '(player.agent_id > 0 OR player.affiliateId > 0)';
                    break;
            }
		}

        if (isset($input['username'], $input['search_by'])) {
            if ($input['search_by'] == 1) {
                $where[] = "player.username LIKE ?";
                $values[] = '%' . $input['username'] . '%';
            } else if ($input['search_by'] == 2) {
                $where[] = "player.username = ?";
                $values[] = $input['username'];
            }
		}
		if(isset($input['point_type'])){
			if($input['point_type'] == 2){
				$where[] = '(point_transactions.transaction_type IN (' . implode(',', $action_map['Manual']) . '))';
				// $values[] = json_encode($action_map['Manual']);
			} else if ($input['point_type'] == 3) {
				// $where[] = '(point_transactions.transaction_type in ?)';
				$where[] = '(point_transactions.transaction_type IN (' . implode(',', $action_map['Auto']) . '))';
				// $values[] = json_encode($action_map['Auto']);
			}
		}

		if (isset($input['tag_list'])) {

            $tag_list = $input['tag_list'];
			$is_include_notag = null;
            if(is_array($tag_list)) {
                $notag = array_search('notag',$tag_list);
                if($notag !== false) {
                    unset($tag_list[$notag]);
					$is_include_notag = true;
                }else{
					$is_include_notag = false;
				}

            } elseif ($tag_list == 'notag') {
                $tag_list = null;
				$is_include_notag = true;
            }

			$where_fragments = [];
			if($is_include_notag){
				$where_fragments[] = 'player.playerId NOT IN (SELECT DISTINCT playerId FROM playertag)';
			}

            if ( ! empty($tag_list) ) {
                $tagList = is_array($tag_list) ? implode(',', $tag_list) : $tag_list;
				$where_fragments[] =  'player.playerId IN (SELECT DISTINCT playerId FROM playertag WHERE playertag.tagId IN ('.$tagList.'))';
            }
			if( ! empty($where_fragments) ){
				$where[] = ' ('. implode(' OR ', $where_fragments ). ') ';
			}
        }

        # DEFINE TABLE COLUMNS ########################################################################################################################################################################
        $columns = array(
			array(
                'select' => 'player.levelId',
                'alias' => 'level_id'
            ),
            array(
                'select' => 'player.levelName',
                'alias' => 'level_name'
            ),
			array(
				'select' => 'player.playerId',
                'alias' => 'player_id'
			),
			array(
				'select' => 'point_transactions.from_id',
                'alias' => 'from_id'
			),
			array(
				'dt' => $i++,
				'select' => 'point_transactions.created_at',
				'alias' => 'trans_time',
                'name' => lang('Transaction Date And Time')
			),
            array(
                'dt' => $i++,
                'alias' => 'username',
                'name' => lang('report.pr01'),
                'select' => 'player.username',
                'formatter' => function ($d, $row) use ($is_export, $date_from, $date_to, $show_username) {

                    if($show_username){
                        if ($is_export) {
                            return $d;
                        } else {
                            return '<a href="/player_management/userInformation/' . $row['player_id'] . '">' . $d . '</a>';
                        }
                    }else{
                        if($is_export){
                            return lang('lang.norecyet');
                        }
                        return  '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
                    }
                },
            ),
			//OGP-25040
			array(
                'dt' => $i++,
                'alias' => 'player_tag',
                'select' => 'player.playerId',
                'formatter' => function ($d, $row) use ($is_export) {
                    if ($is_export) {
						$tagname = player_tagged_list($d, $is_export);
                        return ($tagname ? $tagname : lang('N/A'));
                    } else {
						$tagname = player_tagged_list($d);
                        return $tagname ? $tagname : '<i class="text-muted">' . lang('N/A') . '</i>';
                    }
					return $d;
                },
                'name' => lang('Player Tag'),
            ),
            array(
                'dt' => $i++,
                'name' => lang('report.pr03'),
                'alias' => 'member_level',
                'select' => 'player.groupName',
                'formatter' => function ($d, $row) use ($show_player_level, $is_export) {
                    if(($show_player_level)){
                        return lang($d)." - ".lang($row['level_name']);
                    }else{
                        if($is_export){
                            return lang('lang.norecyet');
                        }
                        return  '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
                    }
                },
			),
			array(
                'dt' => $i++,
                'name' => lang('Transaction Type'),
                'alias' => 'transaction_type',
                'select' => 'point_transactions.transaction_type',
                'formatter' => function ($d, $row) use ($transaction_type_map,$is_export) {
					return $transaction_type_map[$d];
				},
			),
			array(
                'dt' => $i++,
                'name' => lang('Amount'),
                'alias' => 'amount',
                'select' => 'point_transactions.source_amount',
                'formatter' => function ($d, $row) use ($is_export) {
                    if(!empty($d)){
                        return $d;
                    }else{
                        if($is_export){
                            return lang('lang.norecyet');
                        }
                        return  '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
                    }
                },
			),
			array(
                'dt' => $i++,
                'name' => lang('Conversion Rate'),
                'alias' => 'conversion_rate',
                'select' => 'point_transactions.current_rate',
                'formatter' => function ($d, $row) use ($is_export) {
                    if(!empty($d)){
                        return $d .' %';
                    }else{
                        if($is_export){
                            return lang('lang.norecyet');
                        }
                        return  '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
                    }
                },
			),
			array(
                'dt' => $i++,
                'name' => lang('Points'),
                'alias' => 'point',
                'select' => 'point_transactions.point',
                'formatter' => function ($d, $row) use (&$sum_total_point, $action_map, $is_export) {
                    if(!empty($d)){
						if(in_array($row['transaction_type'], $action_map['Deduct'])){
							$d = -1 * $d;
						}
						$sum_total_point += $d;
                        return $d;
                    }else{
						$sum_total_point += 0;

                        if($is_export){
                            return lang('lang.norecyet');
                        }
                        return  '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
                    }
                },
			),
			array(
                'dt' => $i++,
                'name' => lang('Point Type'),
                'alias' => 'point_type',
                'select' => 'point_transactions.transaction_type',
                'formatter' => function ($d, $row) use ($action_map, $is_export) {
					if(in_array($d, $action_map['Manual'])){
						return lang('Manual');
					} else if(in_array($d, $action_map['Auto'])) {
						return lang('Automatic');
					}
                },
			),
			array(
                'dt' => $i++,
                'name' => lang('Action Log'),
                'alias' => 'action_log',
                'select' => 'point_transactions.transaction_type',
                'formatter' => function ($d, $row) use ($action_map, $is_export) {
                    if(in_array($d, $action_map['Manual'])){
						$log = '<i>Points</i>: '.$row['point'].',<br>';

						$adminusername = $this->users->getUsernameById($row['from_id']);
						if( $d == Point_transactions::MANUAL_ADD_POINTS){

							$log .= '<b>Manual Add Points</b> By <b>'.$adminusername.'</b>';
						} else if($d == Point_transactions::MANUAL_DEDUCT_POINTS) {
							$log .= '<b>Manual Deduct Points</b> By <b>'.$adminusername.'</b>';
						}
						if( $is_export) {
							$log = strip_tags($log);
						}
                        return $log;
                    }else{
                        if($is_export){
                            return lang('lang.norecyet');
                        }
                        return  '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
                    }
                },
			),
			array(
                'dt' => $i++,
                'name' => lang('Remarks'),
                'alias' => 'remarks',
                'select' => 'point_transactions.note',
                'formatter' => function ($d, $row) use ($is_export) {
					if(!empty($d)) {
						return $d;
					} else {
						if ($is_export) {
                            return lang('lang.norecyet');
                        }
                        return  '<i class="text-muted">' . lang('lang.norecyet') . '</i>';

					}
                },
            )
        );

        # OUTPUT ######################################################################################################################################################################################

        if($is_export){
            $this->data_tables->options['is_export']=true;
			// $this->data_tables->options['only_sql']=true;
            if(empty($csv_filename)){
                $csv_filename=$this->utils->create_csv_filename(__FUNCTION__);
            }
            $this->data_tables->options['csv_filename']=$csv_filename;
		}

        $result = $this->data_tables->get_data($request, $columns, $table, $where, $values, $joins, $group_by, $having);

        if($is_export){
		    //drop result if export
        	return $csv_filename;
        }
		$result['cols_names_aliases'] = $this->get_dt_column_names_and_aliases($columns);
        $result['total_point'] = $sum_total_point;

        return $result;
    }

    /**
	 * detail: export ip history of a certain player
	 *
	 * @param int $player_id http_request playerId
	 * @return json
	 */
	public function export_ip_history($request, $is_export = false) {
		$readOnlyDB = $this->getReadOnlyDB();
		$this->load->library('data_tables', array("DB" => $readOnlyDB));
		$input = $this->data_tables->extra_search($request);
		$this->data_tables->is_export = $is_export;
		# START DEFINE COLUMNS #################################################################################################################################################
		$i = 0;
		$columns = array(
			array(
				'dt' => $i++,
				'alias' => 'ip',
				'select' => 'http_request.ip',
				'name' => lang('IP'),
				'formatter' => function ($d, $row) {
					return $d ? $d . ' (' . implode(', ', $this->utils->getIpCityAndCountry($d)) . ')' : $this->data_tables->defaultFormatter($d, $row);
				},
			),
			array(
				'dt' => $i++,
				'alias' => 'type',
				'select' => 'http_request.type',
				'name' => lang('Request Type'),
				'formatter' => function ($d) {
					return lang('http.type.' . $d);
				},
			),
			array(
				'dt' => $i++,
				'alias' => 'referrer',
				'select' => 'http_request.referrer',
				'name' => lang('Referrer'),
				'formatter' => 'defaultFormatter',
			),
			array(
				'dt' => $i++,
				'alias' => 'device',
				'select' => 'http_request.device',
				'name' => lang('Device'),
				'formatter' => 'defaultFormatter',
			),
			array(
				'dt' => $i++,
				'alias' => 'createdat',
				'select' => 'http_request.createdat',
				'name' => lang('Created at'),
				'formatter' => 'defaultFormatter',
			),
		);
		# END DEFINE COLUMNS #################################################################################################################################################

		$table = 'http_request';
		$joins = array();

		# START PROCESS SEARCH FORM #################################################################################################################################################
		$where = array();
		$values = array();

		if (isset($input['dateRangeValueStart'], $input['dateRangeValueEnd'])) {
			$where[] = "http_request.createdat BETWEEN ? AND ?";
			$values[] = $input['dateRangeValueStart'];
			$values[] = $input['dateRangeValueEnd'];
		}
		if (isset($input['player_id'])) {
			$where[] = "http_request.playerId = ?";
			$values[] = $input['player_id'];
		}

		$input = $this->data_tables->extra_search($request);
		# END PROCESS SEARCH FORM #################################################################################################################################################

		 # OUTPUT ######################################################################################################################################################################################

        if($is_export){
            $this->data_tables->options['is_export']=true;
            if(empty($csv_filename)){
                $csv_filename=$this->utils->create_csv_filename(__FUNCTION__);
            }
            $this->data_tables->options['csv_filename']=$csv_filename;
		}

        // $result = $this->data_tables->get_data($request, $columns, $table, $where, $values, $joins);

        $this->config->set_item('debug_data_table_sql', true);
        $group_by = array();
        $having = array();
        $mark = 'data_sql';
		$this->utils->markProfilerStart($mark);
        $result = $this->data_tables->get_data($request, $columns, $table, $where, $values, $joins, $group_by, $having);
        $sqls = $this->data_tables->last_query;

        if($is_export){
		    //drop result if export
        	return $csv_filename;
        }
        $this->utils->markProfilerEndAndPrint($mark);
        return $result;
	}

	public function no_deposit_player($request, $is_export = false){
		$this->load->model(['sale_order', 'player_model','vipsetting']);

		$this->load->library(['language_function']);
		$this->language_function->setCurrentLanguage(Language_function::INT_LANG_ENGLISH);
		$_currLang = $this->language_function->getCurrentLanguage();
		$readOnlyDB = $this->getReadOnlyDB();
		$this->load->library('data_tables', array("DB" => $readOnlyDB));
		$input = $this->data_tables->extra_search($request);
		// $this->data_tables->is_export = $is_export;
		// '2022-06-01 00:00:00' AND '2022-06-29 23:59:59'
		// $input['deposit_date_from'] = '2022-06-01 00:00:00';
		// $input['deposit_date_to'] = '2022-06-29 23:59:59';

		$datetime_range_begin = $input['deposit_date_from'];
		$datetime_range_end = $input['deposit_date_to'];


		$_this = $this;
		$i = 0;
		$columns = [];
		$columns[] = [
			'alias' => 'player_id',
			'select' => 'player.playerId',
		];
		$columns[] = [
			'dt' => $i++,
			'alias' => 'datetime_range',
			'select' => "\"$datetime_range_begin TO $datetime_range_end\"",
			'name' => lang('Date&Time (Weekly)', $_currLang),
		];
		$columns[] = [
			'dt' => $i++,
			'alias' => 'playerId',
			'select' => 'player.playerId',
			'name' => lang('Player ID', $_currLang),
		];
		$columns[] = [
			'dt' => $i++,
			'alias' => 'username',
			'select' => 'player.username',
			'name' => lang('Player Username', $_currLang),
		];
		$columns[] = [
			'dt' => $i++,
			'alias' => 'group_level',
			'select' => 'player.levelName',
			'name' => lang('VIP Level', $_currLang),
			'formatter' => function ($d, $row) use ( $_this ){
				$player_id = $row['player_id'];
				$sprintf_format = '%s - %s'; // params: groupName, vipLevelName
				$groupName = lang('N/A'); // defaults
				$vipLevelName = lang('N/A'); // defaults
				$vip_level_info = $_this->vipsetting->getVipGroupLevelInfoByPlayerId($player_id);

				if( ! empty($vip_level_info['vipsetting']['groupName']) ){
					$groupName = lang($vip_level_info['vipsetting']['groupName']);
				}
				if( ! empty($vip_level_info['vipsettingcashbackrule']['vipLevelName']) ){
					$vipLevelName =  lang($vip_level_info['vipsettingcashbackrule']['vipLevelName']);
				}
				unset($vip_level_info); // free memory
				return sprintf($sprintf_format, $groupName, $vipLevelName);
			},
		];

		$this->utils->debug_log(__METHOD__, 'data_tables.columns:', $columns);

		$where = array();
		$values = array();
		$useIndexStr=1;

		/// subquery in WHERE condition,
		// SELECT distinct player_id
		// FROM ( `sale_orders` )
		// WHERE sale_orders.status IN (4,5) -- Sale_order::STATUS_BROWSER_CALLBACK, Sale_order::STATUS_SETTLED
		// AND created_at BETWEEN '2022-06-01 00:00:00' AND '2022-06-29 23:59:59'
		// ;
		//
		$sale_orders_table = 'sale_orders';
		if($this->utils->getConfig('force_index_on_saleorders') && !empty($useIndexStr)){
			$useIndexStr='force index(idx_created_at)';
			$sale_orders_table.=' '.$useIndexStr;
		}

		$date_from_str = $input['deposit_date_from'];
		$date_to_str = $input['deposit_date_to'];
		$by_date_from = new DateTime($date_from_str);
		$by_date_to = new DateTime($date_to_str);
		$_by_date_from = $this->utils->formatDateTimeForMysql($by_date_from);
		$_by_date_to = $this->utils->formatDateTimeForMysql($by_date_to);


		$_status4BROWSER_CALLBACK = Sale_order::STATUS_BROWSER_CALLBACK;
		$_status4SETTLED = Sale_order::STATUS_SETTLED;
		$_condition_in_where =<<<EOF
		playerId NOT IN (
			SELECT distinct sale_orders.player_id
			FROM ( $sale_orders_table )
			WHERE sale_orders.status IN ($_status4BROWSER_CALLBACK, $_status4SETTLED)
			AND created_at BETWEEN "$_by_date_from" AND "$_by_date_to"
		)
EOF;
		$_condition_in_where = implode('', explode("\n", $_condition_in_where) ); // clear EOF
		$_condition_in_where = trim($_condition_in_where);
		$where[] = $_condition_in_where;

		// // test by Developer in local
		// $values = [];
		// $where = [];
		// $_condition_in_where = 'player.playerId IN ( 13989, 15043, 15735, 15659 ) ';
		// $where[] = $_condition_in_where;

		$where[] = "player.deleted_at IS NULL";

		if($is_export){
            $this->data_tables->options['is_export']=true;
            if(empty($csv_filename)){
                $csv_filename=$this->utils->create_csv_filename(__FUNCTION__);
            }
            $this->data_tables->options['csv_filename']=$csv_filename;
		}

		$table = 'player';
		$joins = [];

		/// defaults in data_tables::get_data()
		$group_by = array();
        $having = array();
		$distinct = false;
		$external_order = [];
		$notDatatable ='';
		$countOnlyField='';
		$innerJoins = [];
		$result = $this->data_tables->get_data($request // #1
									, $columns // #2
									, $table // #3
									, $where // #4
									, $values // #5
									, $joins // #6
									, $group_by // #7
									, $having // #8
									, $distinct // #9
									, $external_order // #10
									, $notDatatable // #11
									, $countOnlyField // #12
									, $innerJoins ); // #13
		$sql = $this->data_tables->last_query;

		if($is_export){
			if( ! empty($sql) ){
				$this->utils->debug_log(__METHOD__, 'data_tables.last_query:', $sql);
			}
		}else{
			$result['last_query'] = $sql;
		}

		if($is_export){
		    //drop result if export
			return $csv_filename;
		}

		return $result;
	} // EOF public function no_deposit_player($request, $is_export = false){....

	public function player_login_report($request, $is_export = false, $player_id = null) {

		$readOnlyDB = $this->getReadOnlyDB();

		$this->load->library('data_tables', array("DB" => $readOnlyDB));
		$this->load->model(array('player_login_report', 'player_model','http_request'));

		$this->data_tables->is_export = $is_export;

		$input = $this->data_tables->extra_search($request);
		$where = [];
		$values = [];
		$having = [];
		$group_by = [];
		$i = 0;
		$na = $is_export ? lang('lang.norecyet') : '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
        $_this = $this;

		$columns = array(
			array(
				'alias' => 'player_id',
				'select' => 'player_login_report.player_id',
			),
			// 0 - regdate
			array(
				'dt' => $i++,
				'alias' => 'create_at',
				'select' => 'player_login_report.create_at',
				'name' => lang("player_login_report.datetime"),
			),
			array(
				'dt' => $i++,
				'alias' => 'username',
				'select' => 'player_login_report.player_id',
				'name' => lang('player_login_report.username'),
				'formatter' => function ($d, $row) use ($is_export,$na) {
					$username = $na;

					if (!empty($d)) {
						$username = $this->player_model->getUsernameById($d);
					}

					if(!$is_export){
						return sprintf('<a href="/player_management/userInformation/%s">%s</a>', $d, $username);
					}else{
						return $username;
					}
				}
			),
			array(
				'dt' => $i++,
				'alias' => 'login_result',
				'select' => 'player_login_report.login_result',
				'name' => lang("player_login_report.login_result"),
				'formatter' => function ($d, $row) use ($is_export) {
					if($d==1){
						return lang("player_login_report_login_success");
					}else{
						return lang("player_login_report_login_failed");
					}
				}
			),
			array(
				'dt' => $i++,
				'alias' => 'player_status',
				'select' => 'player_login_report.player_status',
				'name' => lang("player_login_report.player_status"),
				'formatter' => function ($d, $row) use ($is_export) {
					switch($d){
                        case 0:
                            $statusTag = '<span class="text-success">' .lang('status.normal').'</span>';
                            break;
                        case 1:
                            $statusTag = '<span class="text-danger">' .lang('Blocked').'</span>';
                            break;
                        case 5:
                            $statusTag = '<span class="text-danger">' .lang('Suspended').'</span>';
                            break;
                        case 7:
                            $statusTag = '<span class="text-muted">' .lang('Self Exclusion').'</span>';
                            break;
                        case 8:
                            $statusTag = '<span class="text-danger">' .lang('Failed Login Attempt').'</span>';
                            break;
                    }

                    if ($is_export) {
						return strip_tags($statusTag);
                    }
                    return $statusTag;
				}
			),
			array(
				'dt' => $i++,
				'alias' => 'referrer',
				'select' => 'player_login_report.referrer',
				'name' => lang('player_login_report.referrer'),
			),
			array(
				'dt' => $i++,
				'alias' => 'ip',
				'select' => 'player_login_report.ip',
				'name' => lang('player_login_report.login_ip'),
			),
			array(
				'dt' => $i++,
				'alias' => 'device',
				'select' => 'player_login_report.device',
				'name' => lang('Device'),
			),
			array(
				'dt' => $i++,
				'alias' => 'login_from',
				'select' => 'player_login_report.login_from',
				'name' => lang("player_login_report.login_from"),
				'formatter' => function ($d, $row) use ($is_export) {
					if($d==1){
						return lang("player_login_report_login_from_admin");
					}else{
						return lang("player_login_report_login_from_player");
					}
				}
			),
			array(
				'dt' => $i++,
				'alias' => 'content',
				'select' => 'player_login_report.content',
				'name' => lang('player_login_report.content'),
				'formatter' => function ($d, $row) use ($is_export,$na) {
					if(!empty($d)){
						return $d;
					}else{
						return $na;
					}
				}
			),
			array(
				'dt' => $i++,
				'alias' => 'browser_type',
				'select' => 'player_login_report.browser_type',
				'name' => lang('Client End'),
				'formatter' => function ($d, $row) use ( $_this ) {
					if (!empty($d)) {
                        $browser_type = $_this->player_login_report->browserType_to_clientEnd($d);
						return $browser_type;
					}else{
						return lang('N/A');
					}
				},
			),
		);

		$this->utils->debug_log(__METHOD__, 'columns', $columns);

		$table = 'player_login_report';
		$joins = array(
			'player' => 'player.playerId = player_login_report.player_id',
		);


        if (isset($input['group_by'])) {
			$group_by_field = $input['group_by'];
			switch ($input['group_by']) {
                case player_login_report::GROUP_BY_NONE :
                    break;
                case player_login_report::GROUP_BY_CLIENTEND_PLAYER :
                    $group_by[] = 'player_login_report.browser_type';
                    $group_by[] = 'player_login_report.player_id';
                    break;
                case player_login_report::GROUP_BY_CLIENTEND_LOGINIP :
                    $group_by[] = 'player_login_report.browser_type';
                    $group_by[] = 'player_login_report.ip';
                    break;
                case player_login_report::GROUP_BY_USERNAME_CLIENTEND_LOGINIP :
                    $group_by[] = 'player_login_report.player_id';
                    $group_by[] = 'player_login_report.browser_type';
                    $group_by[] = 'player_login_report.ip';
                    break;
            }
		} // EOF  if (isset($input['group_by'])) {

		if (isset($input['by_date_from'], $input['by_date_to'])) {
			$where[] = "DATE(player_login_report.create_at) >=?";
			$where[] = "DATE(player_login_report.create_at) <=?";
			$values[] = $input['by_date_from'];
			$values[] = $input['by_date_to'];
		}

		if (isset($input['dateRangeValueStart'], $input['dateRangeValueEnd'])) {
			$where[] = "DATE(player_login_report.create_at) >=?";
			$where[] = "DATE(player_login_report.create_at) <=?";
			$values[] = $input['dateRangeValueStart'];
			$values[] = $input['dateRangeValueEnd'];
			$useIndexStr='force index(idx_created_at)';
		}

		if (isset($input['by_username'], $input['search_by'])) {
            if ($input['search_by'] == 1) {
                $where[] = "player.username LIKE ?";
                $values[] = '%' . $input['by_username'] . '%';
            } else if ($input['search_by'] == 2) {
                $where[] = "player.username = ?";
                $values[] = $input['by_username'];
            }
        }

		if (isset($input['by_login_result'])) {
			$where[] = "player_login_report.login_result = ?";
			$values[] = $input['by_login_result'];
		} else {
			$where[] = "player_login_report.login_result in (?,?,?)";
			$values[] = '';
			$values[] = Player_login_report::LOGIN_SUCCESS;
			$values[] = Player_login_report::LOGIN_FAILED;
		}

		if (isset($input['by_player_status'])) {
			$where[] = "player_login_report.player_status = ?";
			$values[] = $input['by_player_status'];
		} else {
			$where[] = "player_login_report.player_status in (?,?,?,?,?,?)";
			$values[] = '';
			$values[] = 0;
			$values[] = player_model::BLOCK_STATUS;
			$values[] = player_model::SUSPENDED_STATUS;
			$values[] = player_model::SELFEXCLUSION_STATUS;
			$values[] = player_model::STATUS_BLOCKED_FAILED_LOGIN_ATTEMPT;
		}

		if (isset($input['by_login_from'])) {
			$where[] = "player_login_report.login_from = ?";
			$values[] = $input['by_login_from'];
		} else {
			$where[] = "player_login_report.login_from in (?,?,?)";
			$values[] = '';
			$values[] = Player_login_report::LOGIN_FROM_ADMIN;
			$values[] = Player_login_report::LOGIN_FROM_PLAYER;
		}

		if (isset($input['by_client_end'])) {
			$where[] = "player_login_report.browser_type = ?";
			$values[] = $input['by_client_end'];
		} else {
			$where[] = "player_login_report.browser_type in (?,?,?,?,?)";
			$values[] = '';
			$values[] = Http_request::HTTP_BROWSER_TYPE_PC;
			$values[] = Http_request::HTTP_BROWSER_TYPE_MOBILE;
			$values[] = Http_request::HTTP_BROWSER_TYPE_IOS;
			$values[] = Http_request::HTTP_BROWSER_TYPE_ANDROID;
		}

		if (isset($input['login_ip'])) {
			$where[] = "player_login_report.ip = ?";
			$values[] = $input['login_ip'];
		}

		if (!empty($player_id)) {
			$where[] = "player_login_report.player_id = ?";
			$values[] = $player_id;
		}

		$this->utils->debug_log(__METHOD__, 'player_id', $player_id);

		if($is_export){
            $this->data_tables->options['is_export']=true;
			// $this->data_tables->options['only_sql']=true;
            if(empty($csv_filename)){
                $csv_filename=$this->utils->create_csv_filename(__FUNCTION__);
            }
            $this->data_tables->options['csv_filename']=$csv_filename;
		}

		$this->utils->debug_log(__METHOD__, '------------------request', $request);
		$result = $this->data_tables->get_data($request, $columns, $table, $where, $values, $joins, $group_by, $having);
		$this->utils->debug_log(__METHOD__, '------------------result', $result);
		if($is_export){
			return $csv_filename;
		}

		$result['list_last_query'] = $this->data_tables->last_query;

		return $result;
	}

	/**
	 * detail: get playertaggedlistHistory
	 * @param array $request
	 * @param Boolean $is_export
	 * @return array
	 */
	public function playertaggedlistHistory($request, $permissions, $is_export = false) {
		$readOnlyDB = $this->getReadOnlyDB();
		$this->load->library('data_tables', array("DB" => $readOnlyDB));
		$this->load->model(array('player'));
		$this->load->helper(['player_helper']);

		$i = 0;
		$input = $this->data_tables->extra_search($request);
		$where = array();
		$values = array();

		$columns = array(
			array(
				'select' => 'player.playerId',
				'alias' => 'playerId',
			),
			// array(
			// 	'alias' => 'verified_email',
			// 	'select' => 'player.verified_email',
			// ),
			// array(
			// 	'alias' => 'verified_phone',
			// 	'select' => 'player.verified_phone',
			// ),
			array(
				'alias' => 'tag_id',
				'select' => 'playertag.tag_id',
			),
			array(
				'alias' => 'tag_color',
				'select' => 'playertag.tag_color',
			),
		);

		$columns[] = array(
			'dt' => $i++, # 1
			'alias' => 'username',
			'select' => 'player.username',
			'formatter' => ($is_export) ? 'defaultFormatter' : function ($d, $row) {
				return '<a href="/player_management/userInformation/' . $row['playerId'] . '">' . $d . '</a>';
			},
			'name' => lang('player.01'),
		);

		$columns[] = array(
			'dt' => $i++, # 2
			'alias' => 'real_name',
			'select' => 'CONCAT(ifnull(playerdetails.firstName,""), \' \', ifnull(playerdetails.lastName,"") )',
			'formatter' => function ($d) use ($is_export) {
				if ($is_export) {
					return trim(trim($d), ',') ?: lang('lang.norecyet');
				} else {
					return trim(trim($d), ',') ?: '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
				}
			},
			'name' => lang("sys.vu19"),
		);

		$columns[] = array(
			'dt' => $i++, # 3
			'select' => 'CONCAT(player.groupName, \'|\', player.levelName )',
			'formatter' => function ($d, $row) {
				$d = (explode("|",$d));
				if(count($d) > 1){
					$d = lang($d[0]).' - '.lang($d[1]);
				}
				return $d;
			},
			'alias' => 'memberLevel',
			'name' => lang('VIP Level'),
		);


		$columns[] = array(
			'dt' => $i++, # 4
			'alias' => 'tagName',
			'select' => 'playertag.tag_name',
			'name' => lang("player.41"),
			'formatter' => function ($d, $row) use ($is_export) {
				if($is_export){
					return $d;
				}
				return player_tagged_formate($row['tag_id'], $d, $row['tag_color']);
			},
		);

		$columns[] = array(
			'dt' => $i++, # 5
			'alias' => 'action',
			'select' => 'playertag.action',
			'name' => lang("Update Status"),
			'formatter' => function ($d, $row) use ($is_export) {
				if($d == 'add_by_csv' || $d == 'add'){
					$d = lang('Add');
				}

				if($d == 'remove_by_csv' || $d == 'remove'){
					$d = lang('Remove');
				}
				return $d;
			},
		);

		$columns[] = array(
			'dt' => $i++, # 6
			'alias' => 'updated_at',
			'select' => 'playertag.updated_at',
			'name' => lang("updated_at"),
		);

		$columns[] = array(
            'dt' => $i++, # 7
            'alias' => 'status',
            'select' => 'playertag.tag_id' ,
            'name' => lang("Tag Status"),
            'formatter' => function ($d, $row) use ($is_export){
            	$tagDetails = $this->player->getTagDetails($d);
            	if(!empty($tagDetails)){
            		return lang("Active");
            	} else {
            		return lang("Deleted");
            	}
            },
        );

		$table = 'player_tag_history as playertag';
		$joins = array(
			'player' => 'player.playerId = playertag.player_id',
			'playerdetails' => 'player.playerid = playerdetails.playerid',
			'playerlevel' => 'player.playerid = playerlevel.playerid',
			'tag' => 'playertag.tag_id = tag.tagid',
		);

		$group_by=[];

		$this->utils->debug_log(__METHOD__, [ 'selected_tags' => isset($input['selected_tags']) ? $input['selected_tags'] : '(not set)' ]);

		if (isset($input['selected_tags']) && !empty($input['selected_tags'])) {
			$selected_tags =  is_array($input['selected_tags']) ? $input['selected_tags'] : [ $input['selected_tags'] ];
			$bind_marks = implode(',', array_fill(0, count($selected_tags), '?'));
			$where[] = "playertag.tag_id IN ( $bind_marks )";
			foreach ($selected_tags as $tag) {
				$values[] = $tag;
			}
		}

		if (isset($input['update_status']) && !empty($input['update_status'])) {
			if($input['update_status'] == 'add'){
				$where[] = "(playertag.action = 'add' or playertag.action = 'add_by_csv')";
			}
			if($input['update_status'] == 'remove'){
				$where[] = "(playertag.action = 'remove' or playertag.action = 'remove_by_csv')";
			}
		}

		if (isset($input['tag_status']) && !empty($input['tag_status'])) {
			if($input['tag_status'] == 'active'){
				$where[] = "tag.tagId is not null";
			}
			if($input['tag_status'] == 'deleted'){
				$where[] = "tag.tagId is null";
			}
		}

		if (isset($input['username']) && !empty($input['username'])) {
			$where[] = "player.username like ?";
			$values[] = "%{$input['username']}%";
		}

		if (isset($input['vip_level']) && !empty($input['vip_level'])) {
			$where[] = "player.levelId = ?";
			$values[] = $input['vip_level'];
		}

		if ( ! empty($input['search_reg_date'])) {
            if (isset($input['date_from'], $input['date_to'])) {
                $where[] = "playertag.created_at BETWEEN ? AND ?";
                $values[] = $input['date_from'];
                $values[] = $input['date_to'];
            }
        }

        if (!empty($input['search_last_update_date'])) {
        	if (isset($input['last_update_from'], $input['last_update_to'])) {
        		$where[] = "player.blocked_status_last_update BETWEEN ? AND ?";
        		$values[] = $input['last_update_from'];
        		$values[] = $input['last_update_to'];
        	}
        }

        $having=[];
        $distinct=false;
        $external_order=[];
        $not_datatable='';
		$countOnlyField='playertag.id';

		if($is_export){
            $this->data_tables->options['is_export']=true;
            if(empty($csv_filename)){
                $csv_filename=$this->utils->create_csv_filename(__FUNCTION__);
            }
            $this->data_tables->options['csv_filename']=$csv_filename;
		}

		// $this->config->set_item('debug_data_table_sql', true);
		$result = $this->data_tables->get_data($request, $columns, $table, $where, $values, $joins, $group_by, $having,	$distinct, $external_order, $not_datatable, $countOnlyField);
		// $result['sql'] = $this->data_tables->last_query;
		// echo $this->data_tables->last_query;exit();

		if($is_export){
		    //drop result if export
			return $csv_filename;
		}
		return $result;
	}

    /**
     * detail: get playertaggedHistory for modal
     * @param array $request
     * @param Boolean $is_export
     * @return array
     */
    public function playertaggedHistory($request, $permissions, $is_export = false)
    {
        $readOnlyDB = $this->getReadOnlyDB();
        $this->load->library('data_tables', array("DB" => $readOnlyDB));
        $this->load->model(array('player'));
        $this->load->helper(['player_helper']);

        $i = 0;
        $input = $this->data_tables->extra_search($request);
        $where = array();
        $values = array();

        $columns = array(
            array(
                'select' => 'player.playerId',
                'alias' => 'playerId',
            ),
            array(
                'alias' => 'verified_email',
                'select' => 'player.verified_email',
            ),
            array(
                'alias' => 'verified_phone',
                'select' => 'player.verified_phone',
            ),
            array(
                'alias' => 'tag_id',
                'select' => 'playertag.tag_id',
            ),
            array(
                'alias' => 'tag_color',
                'select' => 'playertag.tag_color',
            ),
        );

        $columns[] = array(
            'dt' => $i++, # 1
            'alias' => 'username',
            'select' => 'player.username',
            'formatter' => ($is_export) ? 'defaultFormatter' : function ($d, $row) {
                return '<a href="/player_management/userInformation/' . $row['playerId'] . '">' . $d . '</a>';
            },
            'name' => lang('player.01'),
        );

        $columns[] = array(
            'dt' => $i++, # 2
            'select' => 'CONCAT(player.groupName, \'|\', player.levelName )',
            'formatter' => function ($d, $row) {
                $d = (explode("|",$d));
                if (count($d) > 1) {
                    $d = lang($d[0]).' - '.lang($d[1]);
                }
                return $d;
            },
            'alias' => 'memberLevel',
            'name' => lang('VIP Level'),
        );

        $columns[] = array(
            'dt' => $i++, # 3
            'alias' => 'tagName',
            'select' => 'playertag.tag_name',
            'name' => lang("player.41"),
            'formatter' => function ($d, $row) use ($is_export) {
                if ($is_export) {
                    return $d;
                }

                return player_tagged_formate($row['tag_id'], $d, $row['tag_color']);
            },
        );

        $columns[] = array(
            'dt' => $i++, # 4
            'alias' => 'action',
            'select' => 'playertag.action',
            'name' => lang("Update Status"),
            'formatter' => function ($d, $row) use ($is_export) {
                if ($d == 'add_by_csv' || $d == 'add') {
                    $d = lang('Add');
                }

                if ($d == 'remove_by_csv' || $d == 'remove') {
                    $d = lang('Remove');
                }
                return $d;
            },
        );

        $columns[] = array(
            'dt' => $i++, # 5
            'alias' => 'updated_at',
            'select' => 'playertag.updated_at',
            'name' => lang("updated_at"),
        );

        $columns[] = array(
            'dt' => $i++, # 6
            'alias' => 'status',
            'select' => 'playertag.tag_id' ,
            'name' => lang("Tag Status"),
            'formatter' => function ($d, $row) use ($is_export){
                $tagDetails = $this->player->getTagDetails($d);
                if (!empty($tagDetails)) {
                    return lang("Active");
                } else {
                    return lang("Deleted");
                }
            },
        );

        $columns[] = array(
            'dt' => $i++, # 7
            'alias' => 'deleted_at',
            'select' => 'playertag.deleted_at',
            'name' => lang("deleted_at"),
        );

        $table = 'player_tag_history as playertag';
        $joins = array(
            'player' => 'player.playerId = playertag.player_id',
            'playerdetails' => 'player.playerid = playerdetails.playerid',
            'playerlevel' => 'player.playerid = playerlevel.playerid',
            'tag' => 'playertag.tag_id = tag.tagid',
        );

        $group_by = [];

        $this->utils->debug_log(__METHOD__, [ 'selected_tags' => isset($input['selected_tags']) ? $input['selected_tags'] : '(not set)' ]);

        if (!empty($input['username'])) {
            $where[] = "player.username like ?";
            $values[] = "%{$input['username']}%";
        }

        if (!empty($input['selected_tags'])) {
            $selected_tags =  is_array($input['selected_tags']) ? $input['selected_tags'] : [ $input['selected_tags'] ];
            $bind_marks = implode(',', array_fill(0, count($selected_tags), '?'));
            $where[] = "playertag.tag_id IN ( $bind_marks )";
            foreach ($selected_tags as $tag) {
                $values[] = $tag;
            }
        }

        if (!empty($input['update_status'])) {
            if ($input['update_status'] == 'add') {
                $where[] = "(playertag.action = 'add' or playertag.action = 'add_by_csv')";
            }

            if ($input['update_status'] == 'remove') {
                $where[] = "(playertag.action = 'remove' or playertag.action = 'remove_by_csv')";
            }
        }

        if (!empty($input['tag_status'])) {
            if ($input['tag_status'] == 'active') {
                $where[] = "tag.tagId is not null";
            }

            if ($input['tag_status'] == 'deleted') {
                $where[] = "tag.tagId is null";
            }
        }

        if (!empty($input['vip_level'])) {
            $where[] = "player.levelId = ?";
            $values[] = $input['vip_level'];
        }

        /* if (!empty($input['search_reg_date'])) {
            if (isset($input['date_from'], $input['date_to'])) {
                $where[] = "playertag.created_at BETWEEN ? AND ?";
                $values[] = $input['date_from'];
                $values[] = $input['date_to'];
            }
        }

        if (!empty($input['search_last_update_date'])) {
                if (isset($input['last_update_from'], $input['last_update_to'])) {
                $where[] = "player.blocked_status_last_update BETWEEN ? AND ?";
                $values[] = $input['last_update_from'];
                $values[] = $input['last_update_to'];
                }
        } */

        $having=[];
        $distinct=false;
        $external_order=[];
        $not_datatable='';
        $countOnlyField='playertag.id';

        if ($is_export) {
            $this->data_tables->options['is_export'] = true;

            if (empty($csv_filename)) {
                $csv_filename=$this->utils->create_csv_filename(__FUNCTION__);
            }

            $this->data_tables->options['csv_filename'] = $csv_filename;
        }

        // $this->config->set_item('debug_data_table_sql', true);
        $result = $this->data_tables->get_data($request, $columns, $table, $where, $values, $joins, $group_by, $having,	$distinct, $external_order, $not_datatable, $countOnlyField);
        // $result['sql'] = $this->data_tables->last_query;
        // echo $this->data_tables->last_query;exit();

        if ($is_export) {
            //drop result if export
            return $csv_filename;
        }

        return $result;
    }

	public function player_duplicate_contactnumber_report($request, $is_export = false) {

		$readOnlyDB = $this->getReadOnlyDB();
		$this->load->library('data_tables', array("DB" => $readOnlyDB));
		$this->load->model(array('duplicate_contactnumber_model', 'player_model'));
		$this->data_tables->is_export = $is_export;

		$input = $this->data_tables->extra_search($request);
		$where = [];
		$values = [];
		$having = [];
		$group_by = [];
		$i = 0;
		$na = $is_export ? lang('lang.norecyet') : '<i class="text-muted">' . lang('lang.norecyet') . '</i>';

		$columns = array(
			array(
				'alias' => 'player_id',
				'select' => 'player_duplicate_contactnumber.player_id',
			),

			// 0 - regdate
			array(
				'dt' => $i++,
				'alias' => 'username',
				'select' => 'player.username',
				'name' => lang('player.01'),
				'formatter' => function ($d, $row) use ($is_export,$na) {
					if ($is_export) {
						return ($d ? $d : $na);
					} else {
						return '<i class="fa fa-user" ></i> ' . ($d ? '<a href="/player_management/userInformation/' . $row['player_id'] . '" target="_blank">' . $d . '</a>' : '<i class="text-muted">' . $na . '</i>');
					}
				}
			),

			//1 new user reg date
			array(
				'dt' => $i++,
				'alias' => 'created_on',
				'select' => 'player.createdOn',
				'name' => lang("player.38"),
			),

			//2 new user reg IP
			array(
				'dt' => $i++,
				'alias' => 'registration_ip',
				'select' => 'playerdetails.registrationIP',
				'name' => lang('Signup IP'),
			),

			//3 new user login ip
			array(
				'dt' => $i++,
				'alias' => 'lastlogin_ip',
				'select' => 'player.lastLoginIp',
				'name' => lang('player_list.fields.last_login_ip'),
			),


			//4 contactnumber
			array(
				'dt' => $i++,
				'alias' => 'contact_number',
				'select' => 'player_duplicate_contactnumber.contact_number',
				'name' => lang("player.63"),
				'formatter' => function ($d, $row) use ($is_export,$na) {
					$contact_number = $d;
					if ($this->utils->isEnabledFeature('enabled_show_player_obfuscated_phone')) {
						$contact_number = $this->utils->keepOnlyString($contact_number, -3);
					}
					return ($contact_number ? $contact_number : $na);
				}
			),

			//5 old Duplicate Phone Number user
			array(
				'dt' => $i++,
				'alias' => 'duplicate_contactnumber_username',
				'select' => 'player_duplicate_contactnumber.duplicate_user',
				'name' => lang('duplicate_contactnumber_model.1'),
				'formatter' => function ($d, $row) use ($is_export,$na) {
					$usernames = $na;

					if(!empty($d)){
						$explode_user = explode(",",$d);
						$usernames = '';
						foreach ($explode_user as $name) {
							$player_id = $this->player_model->getPlayerIdByUsername($name);
							$this->utils->debug_log('duplicate_user', 'player_id', $player_id);
							if(empty($player_id)){
								continue;
							}
							$player = $this->player_model->getPlayerDetailsById($player_id);
							$created_on = $player->createdOn;
							$user_str = '<i class="fa fa-user" ></i> ' . '<a href="/player_management/userInformation/' . $player_id . '" target="_blank">' . $name . '</a>' .' / ' . $created_on;
							$usernames .= "$user_str<br>";
							$this->utils->debug_log('duplicate_user', 'created_on', $created_on);
						}
						$usernames = rtrim($usernames, '<br>');
					}
					return $is_export ? strip_tags($usernames) : $usernames;
				}
			),
		);

		$this->utils->debug_log(__METHOD__, 'columns', $columns);

		$table = 'player_duplicate_contactnumber';
		$joins = array(
			'player' => 'player.playerId = player_duplicate_contactnumber.player_id',
			'playerdetails' => 'playerdetails.playerId = player_duplicate_contactnumber.player_id',
		);

		if (isset($input['by_date_from'], $input['by_date_to'])) {
			$where[] = "player.createdOn >=?";
			$where[] = "player.createdOn <=?";
			$values[] = $input['by_date_from'];
			$values[] = $input['by_date_to'];
		}


        if (isset($input['by_username'])) {
			$where[] = "player.username = ?";
			$values[] = $input['by_username'];
		}

		if (isset($input['login_ip'])) {
			$where[] = "playerdetails.registrationIP = ?";
			$values[] = $input['login_ip'];
		}

		if($is_export){
            $this->data_tables->options['is_export']=true;
			// $this->data_tables->options['only_sql']=true;
            if(empty($csv_filename)){
                $csv_filename=$this->utils->create_csv_filename(__FUNCTION__);
            }
            $this->data_tables->options['csv_filename']=$csv_filename;
		}
		$result = $this->data_tables->get_data($request, $columns, $table, $where, $values, $joins, $group_by, $having);

		if($is_export){
			return $csv_filename;
		}

		$result['list_last_query'] = $this->data_tables->last_query;

		$this->utils->debug_log(__METHOD__, 'result', $result);
		return $result;
	}
}
////END OF FILE/////////
