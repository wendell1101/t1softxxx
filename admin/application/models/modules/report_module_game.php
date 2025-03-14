<?php

/**
 * General behaviors include
 * * get game report for a certain player
 * * get agency game report
 * * get game descriptions
 *
 * @category report_module_game
 * @version 1.8.10
 * @copyright 2013-2022 tot
 */
 trait report_module_game {

    # add game platform id here for default format of bet details
    public $game_apis_for_default_format_bet_details = [
        // Main Game Apis
        CQ9_API, JUMB_GAMING_API, LUCKY365_GAME_API, LIONKING_GAME_API, EVOPLAY_SEAMLESS_GAME_API, IDNLIVE_SEAMLESS_GAME_API, CQ9_SEAMLESS_GAME_API,
        SOFTSWISS_SEAMLESS_GAME_API, SOFTSWISS_BGAMING_SEAMLESS_GAME_API, BGAMING_SEAMLESS_GAME_API, WAZDAN_SEAMLESS_GAME_API, SOFTSWISS_EVOLUTION_SEAMLESS_GAME_API,
        YL_NTTECH_SEAMLESS_GAME_API, SOFTSWISS_SPRIBE_SEAMLESS_GAME_API, SOFTSWISS_EVOPLAY_SEAMLESS_GAME_API, SOFTSWISS_BETSOFT_SEAMLESS_GAME_API, SOFTSWISS_WAZDAN_SEAMLESS_GAME_API,
        AG_SEAMLESS_GAME_API, TADA_SEAMLESS_GAME_API, SPADEGAMING_SEAMLESS_GAME_API, BOOMING_SEAMLESS_GAME_API, CMD_SEAMLESS_GAME_API, CMD2_SEAMLESS_GAME_API, SV388_AWC_SEAMLESS_GAME_API, YGG_DCS_SEAMLESS_GAME_API,
        HACKSAW_DCS_SEAMLESS_GAME_API, BTI_SEAMLESS_GAME_API, VIVOGAMING_SEAMLESS_API, KING_MAKER_SEAMLESS_GAME_API, WM_SEAMLESS_GAME_API, BETGAMES_SEAMLESS_GAME_API, TWAIN_SEAMLESS_GAME_API, HP_LOTTERY_GAME_API, FLOW_GAMING_SEAMLESS_API,
        FLOW_GAMING_QUICKSPIN_SEAMLESS_API, AVATAR_UX_DCS_SEAMLESS_GAME_API, HACKSAW_SEAMLESS_GAME_API, BNG_SEAMLESS_GAME_API, RTG_SEAMLESS_GAME_API, ONE_TOUCH_SEAMLESS_GAME_API, AB_SEAMLESS_GAME_API, MPOKER_SEAMLESS_GAME_API,
        EVOLUTION_SEAMLESS_GAMING_API, EVOLUTION_NETENT_SEAMLESS_GAMING_API, EVOLUTION_NLC_SEAMLESS_GAMING_API, EVOLUTION_REDTIGER_SEAMLESS_GAMING_API, EVOLUTION_BTG_SEAMLESS_GAMING_API, RTG2_SEAMLESS_GAME_API, PGSOFT_API,
        PT_SEAMLESS_GAME_API, IDN_PT_SEAMLESS_GAME_API, IDN_SLOTS_PT_SEAMLESS_GAME_API, IDN_LIVE_PT_SEAMLESS_GAME_API, HABANERO_SEAMLESS_GAMING_API, IDN_HABANERO_SEAMLESS_GAMING_API, FA_WS168_SEAMLESS_GAME_API,

        // T1 Game Apis
        T1_BOOMING_SEAMLESS_GAME_API, T1_CMD_SEAMLESS_GAME_API, T1_CMD2_SEAMLESS_GAME_API, T1_SV388_AWC_SEAMLESS_GAME_API, T1_YGG_DCS_SEAMLESS_GAME_API, T1_HACKSAW_DCS_SEAMLESS_GAME_API, T1_VIVOGAMING_SEAMLESS_API,
        T1_KING_MAKER_SEAMLESS_GAME_API, T1_WM_SEAMLESS_GAME_API, T1_BETGAMES_SEAMLESS_GAME_API, T1_TWAIN_SEAMLESS_GAME_API, T1_FLOW_GAMING_SEAMLESS_API,
        T1_FLOW_GAMING_QUICKSPIN_SEAMLESS_API, T1_AVATAR_UX_DCS_SEAMLESS_GAME_API, T1_HACKSAW_SEAMLESS_GAME_API, T1_BNG_SEAMLESS_GAME_API, T1_RTG_SEAMLESS_GAME_API, T1_ONE_TOUCH_SEAMLESS_GAME_API, T1_AB_SEAMLESS_GAME_API,T1_MPOKER_SEAMLESS_GAME_API,
        T1_EVOLUTION_SEAMLESS_GAME_API, T1_EVOLUTION_NETENT_SEAMLESS_GAMING_API, T1_EVOLUTION_NLC_SEAMLESS_GAMING_API, T1_EVOLUTION_REDTIGER_SEAMLESS_GAMING_API, T1_EVOLUTION_BTG_SEAMLESS_GAMING_API, T1_RTG2_SEAMLESS_GAME_API,
        T1_PT_SEAMLESS_GAME_API, T1_IDN_PT_SEAMLESS_GAME_API, T1_IDN_SLOTS_PT_SEAMLESS_GAME_API, T1_IDN_LIVE_PT_SEAMLESS_GAME_API, T1_HABANERO_SEAMLESS_GAME_API, T1_IDN_HABANERO_SEAMLESS_GAMING_API, T1_FA_WS168_SEAMLESS_GAME_API,
    ];

    public $pt_seamless_game_apis = [
        //? Main Game Apis
        PT_SEAMLESS_GAME_API,
        IDN_PT_SEAMLESS_GAME_API,
        IDN_SLOTS_PT_SEAMLESS_GAME_API,
        IDN_LIVE_PT_SEAMLESS_GAME_API,

        //? t1 Game Apis
        T1_PT_SEAMLESS_GAME_API,
        T1_IDN_PT_SEAMLESS_GAME_API,
        T1_IDN_SLOTS_PT_SEAMLESS_GAME_API,
        T1_IDN_LIVE_PT_SEAMLESS_GAME_API,
    ];

    public $habanero_seamless_game_apis = [
        //? Main Game Apis
        HABANERO_SEAMLESS_GAMING_API,
        IDN_HABANERO_SEAMLESS_GAMING_API,

        //? t1 Game Apis
        T1_HABANERO_SEAMLESS_GAME_API,
        T1_IDN_HABANERO_SEAMLESS_GAMING_API,
    ];

    public $evolution_seamless_game_apis = [
        //? Main Game Apis
        EVOLUTION_SEAMLESS_GAMING_API,
        EVOLUTION_NETENT_SEAMLESS_GAMING_API,
        EVOLUTION_NLC_SEAMLESS_GAMING_API,
        EVOLUTION_REDTIGER_SEAMLESS_GAMING_API,
        EVOLUTION_BTG_SEAMLESS_GAMING_API,
        IDN_EVOLUTION_SEAMLESS_GAMING_API,
        IDN_EVOLUTION_NETENT_SEAMLESS_GAMING_API,
        IDN_EVOLUTION_NLC_SEAMLESS_GAMING_API,
        IDN_EVOLUTION_REDTIGER_SEAMLESS_GAMING_API,
        IDN_EVOLUTION_BTG_SEAMLESS_GAMING_API,

        //? t1 Game Apis
        T1_EVOLUTION_SEAMLESS_GAME_API,
        T1_EVOLUTION_NETENT_SEAMLESS_GAMING_API,
        T1_EVOLUTION_NLC_SEAMLESS_GAMING_API,
        T1_EVOLUTION_REDTIGER_SEAMLESS_GAMING_API,
        T1_EVOLUTION_BTG_SEAMLESS_GAMING_API,
        T1_IDN_EVOLUTION_SEAMLESS_GAMING_API,
        T1_IDN_EVOLUTION_NETENT_SEAMLESS_GAMING_API,
        T1_IDN_EVOLUTION_NLC_SEAMLESS_GAMING_API,
        T1_IDN_EVOLUTION_REDTIGER_SEAMLESS_GAMING_API,
        T1_IDN_EVOLUTION_BTG_SEAMLESS_GAMING_API,
    ];

    public $fa_seamless_game_apis = [
        //? Main Game Apis
        FA_WS168_SEAMLESS_GAME_API,

        //? t1 Game Apis
        T1_FA_WS168_SEAMLESS_GAME_API,
    ];

	/**
	 * detail: get game report for a certain player
	 *
	 * @param array $request
	 * @param int $player_id total_player_game_hour player_id
	 *
	 * @return array
	 */
	public function gameReports($request, $player_id = null, $is_export = false, $permission = null) {
		$this->load->library(array('data_tables'));
		$this->load->model(array('player_model','game_logs','game_type_model', 'affiliatemodel'));
		$this->load->helper(['player_helper', 'aff_helper']);
		// $request = $this->input->post();
		$input = $this->data_tables->extra_search($request);

		$table = 'total_player_game_hour';
		$unsettle=isset($input['search_unsettle_game']) && $input['search_unsettle_game'];
		if($unsettle){
			//FIXME add hour for unsettle
			$table='game_logs_unsettle as total_player_game_hour';
		}

		$joins = array();
		$where = array();
		$values = array();
		$group_by = array();
		$having = array();

		$joins['game_type'] = 'game_type.id = total_player_game_hour.game_type_id';
		$joins['game_description'] = 'game_description.id = total_player_game_hour.game_description_id';
		$joins['external_system'] = 'external_system.id = total_player_game_hour.game_platform_id';
		$joins['player'] = 'player.playerId = total_player_game_hour.player_id';

		$joins['affiliates'] = 'affiliates.affiliateId = player.affiliateId';
		$joins['vipsettingcashbackrule'] = 'vipsettingcashbackrule.vipsettingcashbackruleId = player.levelId';
		$joins['vipsetting'] = 'vipsetting.vipSettingId = vipsettingcashbackrule.vipSettingId';

		$group_by_field = null;
		$show_game_platform = false;
		$show_game_type = false;
		$show_game = false;
		$show_player_tag = false;
		$show_player = false;
		$show_affiliate_tag = true;

		if (isset($input['namespace'])) {
			switch($input['namespace']){
				case "affiliateBo":
					$show_affiliate_tag = false;
					break;
			}
		}
		if (isset($input['group_by'])) {
			$group_by_field = $input['group_by'];
			switch ($input['group_by']) {
			case 'game_platform_id':
				$group_by[] = 'total_player_game_hour.game_platform_id';
				$show_game_platform = true;
				$show_player_tag = false;
				break;
			case 'game_type_id':
				$group_by[] = 'total_player_game_hour.game_type_id';
				$show_game_platform = true;
				$show_game_type = true;
				$show_player_tag = false;
				break;
			case 'game_description_id':
				$group_by[] = 'total_player_game_hour.game_description_id';
				$show_game_platform = true;
				$show_game_type = true;
				$show_game = true;
				$show_player_tag = false;
				break;
			case 'player_id':
				$group_by[] = 'total_player_game_hour.player_id';
				$show_player = true;
				$show_player_tag = true;
				break;
			case 'aff_id':
				$group_by[] = 'player.affiliateId';
				$show_player = true;
				$show_player_tag = true;
				break;
			case 'agent_id':
				$group_by[] = 'player.agent_id';
				$show_player = true;
				$show_player_tag = true;
				break;
			case 'game_type_and_player':
				$group_by[] = 'total_player_game_hour.player_id, total_player_game_hour.game_type_id';
				$show_game_platform = true;
				$show_game_type = true;
				$show_player = true;
				$show_player_tag = true;
				break;
			case 'game_platform_and_player':
				$group_by[] = 'total_player_game_hour.player_id, total_player_game_hour.game_platform_id';
				$show_game_platform = true;
				$show_player = true;
				$show_player_tag = true;
				break;
			case 'game_description_and_player':
				$group_by[] = 'total_player_game_hour.player_id, total_player_game_hour.game_description_id';
				$show_game_platform = true;
				$show_game_type = true;
				$show_game = true;
				$show_player = true;
				$show_player_tag = true;
				break;
			case 'aff_and_game_platform':
				$group_by[] = 'player.affiliateId, total_player_game_hour.game_platform_id';
				$show_game_platform = true;
				// $show_player = true;
				// $show_player_tag = true;
				break;
			}
		}

		if (isset($input['username'])) {
			$player_id = $this->player_model->getPlayerIdByUsername($input['username']);
		}

		if (isset($input['referrer'])) {
			$refereePlayerId = $this->player_model->getPlayerIdByUsername($input['referrer']) ? : -1;
			$where[] = "player.refereePlayerId = ?";
			$values[] = $refereePlayerId;
		}

		if (isset($input['external_system'])) {
			if($input['external_system'] != 0) {
				$where[] = "external_system.id = ?";
				$values[] = $input['external_system'];
				$show_game_platform = true;
			}
		}

		if (isset($input['game_type_multiple'])) {
			if($input['game_type_multiple'] != 0) {
				$game_type_arr = explode('+', $input['game_type_multiple']);

                if (isset($input['game_type'])){
                	array_push($game_type_arr, $input['game_type']);
                }

				$game_types =  $game_type_arr;
				$where[] = "game_type.id in (" . implode(',', $game_types) . ") ";
				// $group_by[] = 'total_player_game_hour.game_type_id';
				// $show_game_type = true;
			}
		}

		if (isset($input['game_type']) && !isset($input['game_type_multiple'])) {
			if($input['game_type'] != 0) {
				$where[] = "game_type.id = ?";
				$values[] = $input['game_type'];
				//$show_game_type = true;
			}
		}

		// $where[] = "game_type.status = ?";
		// $values[] = game_type_model::ACTIVE_GAME_TYPE;

		if (isset($player_id)) {
			$where[] = "total_player_game_hour.player_id = ?";
			$values[] = $player_id;
			$show_player = true;
		} else if (isset($input['affiliate_username']) && !$this->utils->isFromHost('aff')) {
			// $where[] = "affiliates.username = ?";
			// $values[] = $input['affiliate_username'];
			// $show_player = true;

			$this->load->model(array('affiliatemodel'));
            $affiliate_detail = (array)$this->affiliatemodel->getAffiliateByUsername($input['affiliate_username']);
			if (isset($input['include_all_downlines_aff']) && $input['include_all_downlines_aff'] == true && !empty($affiliate_detail)) {

    			$parent_ids = array($affiliate_detail['affiliateId']);
    			$sub_ids = array();
    			$all_ids = $parent_ids;
    			while (!empty($sub_ids = $this->affiliatemodel->get_sub_affiliate_ids_by_parent_id($parent_ids))) {
    				$all_ids = array_merge($all_ids, $sub_ids);
    				$parent_ids = $sub_ids;
    				$sub_ids = array();
    			}
    			foreach ($all_ids as $i => $id) {
    				if ($i == 0) {
    					$w = "(player.affiliateId = ?";
    				} else {
    					$w .= " OR player.affiliateId = ?";
    				}
    				$values[] = $id;
    			}
    			$w .= ")";
    			$where[] = $w;
    		} else {
    			$where[] = "affiliates.username = ?";
				$values[] = $input['affiliate_username'];
    		}
		}

		if (isset($input['only_under_agency']) && $input['only_under_agency'] != '') {
            // $show_game_platform = true;
            // $show_game_type = true;
            // $show_game = true;
            // $show_player = true;
			$where[] = "player.agent_id IS NOT NULL";
			if (!isset($input['agent_name'])) {
				if (isset($input['current_agent_name']) && $input['current_agent_name'] != '') {
					$input['agent_name'] = $input['current_agent_name'];
				}
			}
		}

		if (isset($input['agent_name'])) {
            // $show_game_platform = true;
            // $show_game_type = true;
            // $show_game = true;
            // $show_player = true;
            $this->load->model(array('agency_model'));
			$agent_detail = $this->agency_model->get_agent_by_name($input['agent_name']);

			if (isset($input['include_all_downlines']) && $input['include_all_downlines'] == 'on' && !empty($agent_detail)) {
				$joins['agency_agents'] = 'player.agent_id = agency_agents.agent_id';
				$parent_ids = array($agent_detail['agent_id']);
				$sub_ids = array();
				$all_ids = $parent_ids;
				while (!empty($sub_ids = $this->agency_model->get_sub_agent_ids_by_parent_id($parent_ids))) {
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

		if($this->utils->isFromHost('aff')) {

			$affiliateId = $this->session->userdata('affiliateId');

            // if (isset($input['affiliate_username'])) {
            //getAffiliateById
           //  	$this->load->model(array('affiliatemodel'));
           //  	$searchedAffiliateId = $this->affiliatemodel->getAffiliateByUsername($input['affiliate_username'])->affiliateId;
           //  	if ($searchedAffiliateId  != $affiliateId  && !$this->affilitatemodel->is_upline($searchedAffiliateId, $affiliatId)) {

           //  		// $show_game_platform = true;
           //  		// $show_game_type = true;
           //  		// $show_game = true;
           //  		// $show_player = true;
           //  		$this->load->model(array('affiliatemodel'));
           //  		$affiliate_detail = (array)$this->affiliatemodel->getAffiliateByUsername($input['affiliate_username']);

			        // //not implemented yet but just in case we need
           //  		if (isset($input['include_all_downlines']) && $input['include_all_downlines'] == true && !empty($affiliate_detail)) {

           //  			$parent_ids = array($affiliate_detail['affiliateId']);
           //  			$sub_ids = array();
           //  			$all_ids = $parent_ids;
           //  			while (!empty($sub_ids = $this->affiliatemodel->get_sub_affiliate_ids_by_parent_id($parent_ids))) {
           //  				$all_ids = array_merge($all_ids, $sub_ids);
           //  				$parent_ids = $sub_ids;
           //  				$sub_ids = array();
           //  			}
           //  			foreach ($all_ids as $i => $id) {
           //  				if ($i == 0) {
           //  					$w = "(player.affiliateId = ?";
           //  				} else {
           //  					$w .= " OR player.affiliateId = ?";
           //  				}
           //  				$values[] = $id;
           //  			}
           //  			$w .= ")";
           //  			$where[] = $w;
           //  		} else {
           //  			$where[] = "player.affiliateId = ?";
           //  			$values[] = $affiliate_detail['affiliateId'];
           //  		}
           //  	};
        	$this->load->model(array('affiliatemodel'));
        	if (isset($input['affiliate_username'])) {
            	$affiliate_detail = (array)$this->affiliatemodel->getAffiliateByUsername($input['affiliate_username']);
            } else {
            	$affiliate_detail = (array)$this->affiliatemodel->getAffiliateById($affiliateId);
            }
			if (isset($input['include_all_downlines_aff']) && $input['include_all_downlines_aff'] == true && !empty($affiliate_detail)) {

    			$parent_ids = array($affiliate_detail['affiliateId']);
    			$sub_ids = array();
    			$all_ids = $parent_ids;
    			while (!empty($sub_ids = $this->affiliatemodel->get_sub_affiliate_ids_by_parent_id($parent_ids))) {
    				$all_ids = array_merge($all_ids, $sub_ids);
    				$parent_ids = $sub_ids;
    				$sub_ids = array();
    			}
    			foreach ($all_ids as $i => $id) {
    				if ($i == 0) {
    					$w = "(player.affiliateId = ?";
    				} else {
    					$w .= " OR player.affiliateId = ?";
    				}
    				$values[] = $id;
    			}
    			$w .= ")";
    			$where[] = $w;
            }else{
            	$where[] = "player.affiliateId = ?";
            	$values[] = $affiliateId;
            }

		}

        if ($this->utils->isIncludedInGamesReportSearch('storeCode') && !empty($input['agency_code'])) {
            $joins['playerdetails_extra'] = 'player.playerId = playerdetails_extra.playerId';
            $where[] = "playerdetails_extra.storeCode = ?";
            $values[] = $input['agency_code'];
        }

		# DEFINE TABLE COLUMNS ########################################################################################################################################################################
		$i = 0;
		$columns = array(
			array(
				'alias' => 'player_playerId',
				'select' => 'player.playerId'
			),

			array(
				'alias' => 'player_levelName',
				'select' => 'vipsettingcashbackrule.vipLevelName'
			),
			array(
				'alias' => 'player_groupName',
				'select' => 'vipsetting.groupName'
			),
			array(
				'alias' => 'affiliates_affiliateId',
				'select' => 'affiliates.affiliateId'
			),
			array(
				'alias' => 'external_system_id',
				'select' => 'external_system.id'
			),
			array(
				'alias' => 'game_description_id',
				'select' => $show_game ? 'game_description.id' : '"N/A"',
			),
			array(
				'alias' => 'game_type_id',
				'select' => $show_game_type ? 'game_type.id' : '"N/A"',
			),
			array(
				'dt' => $i++,
				'alias' => 'system_code',
				'select' => $show_game_platform ? 'external_system.system_code' : '"N/A"',
				'formatter' => function($d, $row) use ($is_export)  {

                     if ($is_export) {
                     	return $d;
                     }else{
                     	return $d. '<p class=" hide gamePlatformId">'. $row['external_system_id']. '</p>';
                     }

				},
				'name' => lang('Game Platform'),
			),
			array(
				'dt' => $i++,
				'alias' => 'game_type_lang',
				'select' => $show_game_type ? 'game_type.game_type_lang' : '"N/A"',
				'formatter' => 'languageFormatter',
				'name' => lang('Game Type'),
			),
			array(
				'dt' => $i++,
				'alias' => 'game_name',
				'select' => $show_game ? 'game_description.game_name' : '"N/A"',
				'formatter' => 'languageFormatter',
				'name' => lang('Game'),
			),
			array(
				'dt' => $i++,
				'alias' => 'player_id',
				'select' => $show_player ? 'player.playerId' : '"N/A"',
				'formatter' => 'languageFormatter',
				'name' => lang('Player Id'),
			),
			array(
				'dt' => $i++,
				'alias' => 'player_username',
				'select' => $show_player ? 'player.username' : '"N/A"',
				'formatter' => function($d, $row) use ($is_export) {

					if ($this->utils->isAffSubProject() && $this->utils->isEnabledFeature('masked_player_username_on_affiliate')) {
						$d = $this->utils->keepOnlyString($d, 4);
					}

					if($d != 'N/A') {
						return $is_export ? $d : '<a href="/player_management/userInformation/' . $row['player_playerId'] . '" target="_blank">' . $d . '</a>';
					}
					return $d;
				},
				'name' => lang('Player Username'),
			),

			//OGP-25040
			array(
                'dt' => $i++,
                'alias' => 'player_tag',
                'select' =>  $show_player_tag ? 'player.playerId' : '"N/A"',
				'formatter' => function ($d, $row) use ($is_export) {
                    if ($is_export) {
						if($d=="N/A"){
							return "N/A";
						}else{
							$tagname = player_tagged_list($d, $is_export);
							return ($tagname ? $tagname : lang('N/A'));
						}
                    } else {
						if($d=="N/A"){
							return '<i class="text-muted">' . lang('N/A') . '</i>';
						}else{
							$tagname = player_tagged_list($d);
							return $tagname ? $tagname : '<i class="text-muted">' . lang('N/A') . '</i>';
						}
                    }
					return $d;
                },

                'name' => lang('Player Tag'),
            ),

			array(
				'dt' => $i++,
				'alias' => 'player_level',
				'select' => $show_player ? 'player.levelId' : '"N/A"',
				'formatter' => function($d, $row){
					if($d != 'N/A') {
						return lang($row['player_groupName']).' - '.lang($row['player_levelName']);
					}
		  			return $d;

				},
				'name' => lang('Player Level'),

			),
			array(
				'dt' => $i++,
				'alias' => 'affiliate',
				'select' => 'affiliates.username',
				'formatter' => function($d, $row) use ($is_export, $permission) {
					if ($this->utils->isEnabledFeature('masked_affiliate_username_on_affiliate') || !$permission['show_affiliate_username_on_affiliate']) {
						$d = $this->utils->keepOnlyString($d, 4);
					}

					if($d) {
						if($this->utils->isFromHost('aff')) {
							return trim(trim($d), ',') ?: lang('lang.norecyet');
						}
						return $is_export ? $d : '<a href="/affiliate_management/userInformation/' . $row['affiliates_affiliateId'] . '" target="_blank">' . $d . '</a>';
					} else {

					}
				},
				'name' => lang('aff.as03'),
			),
			array(
				'dt' => $show_affiliate_tag ? $i++ : NULL,
				'alias' => 'affiliate_tag',
				'select' => 'affiliates.affiliateId',
				'formatter' => function ($d, $row)  use ($is_export)  {
					return aff_tagged_list($d, $is_export);
				},
				'name' => lang('Affiliate Tag'),
			),
			array(
				'dt' => $i++,
				'alias' => 'total_player',
				'select' => 'COUNT(DISTINCT total_player_game_hour.player_id)',
				'formatter' => function ($d, $row)  use ($is_export)  {
					return $d;
				},
				'name' => lang('aff.as24'),
			),
			array(
				'dt' => $i++,
				'alias' => 'total_bet',
				'select' => $unsettle ? 'SUM(total_player_game_hour.bet_amount)' : 'SUM(total_player_game_hour.betting_amount)',
				'formatter' =>  function($d, $row) use ($is_export) {
					return $this->currencyFormatter($d, $is_export);
				},
				'name' => lang('report.g09'),
			),
			array(
				'dt' => $i++,
				'alias' => 'total_payout', # OGP-11721 Add "Payout" Column in Game Report (Tota Bets - (Total Loss - Total Wins)) - Fixed in OGP-12135
				'select' => $unsettle ? 'SUM(total_player_game_hour.bet_amount - (total_player_game_hour.loss_amount - total_player_game_hour.win_amount))' : 'SUM(total_player_game_hour.betting_amount - (total_player_game_hour.loss_amount - total_player_game_hour.win_amount))',
				'formatter' =>  function($d, $row) use ($is_export) {
					return $this->currencyFormatter($d, $is_export);
				},
				'name' => lang('Agency Payout'),
			),

			array(
				'dt' => $i++,
				'alias' => 'total_gain',
				'select' => 'SUM(total_player_game_hour.win_amount)',
				'formatter' =>  function($d, $row) use ($is_export) {
					return $this->currencyFormatter($d, $is_export);
				},
				'name' => lang('report.g10'),
			),
			array(
				'dt' => $i++,
				'alias' => 'total_loss',
				'select' => 'SUM(total_player_game_hour.loss_amount)',
				'formatter' =>  function($d, $row) use ($is_export) {
					return $this->currencyFormatter($d, $is_export);
				},
				'name' => lang('report.g11'),
			),
			array(
				'dt' => $i++,
				'alias' => 'total_revenue', # OGP-11721 Change the label of current "Payout" to "Game Revenue"
				'select' => 'SUM(total_player_game_hour.loss_amount - total_player_game_hour.win_amount)',
				'formatter' =>  function($d, $row) use ($is_export) {
					return $this->currencyFormatter($d, $is_export);
				},
				'name' => lang('Game Revenue'),
			),
			array(
				'dt' => $i++,
				'alias' => 'total_revenue_percent', #  OGP-11721 Change the lable pf current "Payout Rate" to "Game Revenue %"
				'select' => $unsettle ? 'SUM(total_player_game_hour.loss_amount-total_player_game_hour.win_amount)/SUM(total_player_game_hour.bet_amount)' : 'SUM(total_player_game_hour.loss_amount-total_player_game_hour.win_amount)/SUM(total_player_game_hour.betting_amount)',
				'formatter' => 'percentageFormatter',
				'name' => lang('Game Revenue %'),
			),
		);

		# FILTER ######################################################################################################################################################################################

		if (isset($input['datetime_from'], $input['datetime_to'])) {
			if($unsettle){
				#WE WILL ALWAYS USE TIMEZONE NOW OGP-6434
				$default_timezone = $this->utils->getTimezoneOffset(new DateTime());
				$timezone = !empty($this->utils->getConfig('force_default_timezone_option')) ? $input['timezone'] : intval($input['timezone']);
				$hours = $default_timezone - $timezone;
				$date_from_str = $input['datetime_from'];
				$date_to_str = $input['datetime_to'];
				$by_date_from = new DateTime($date_from_str);
				$by_date_to = new DateTime($date_to_str);
				$by_date_from = new DateTime($date_from_str);
				$by_date_to = new DateTime($date_to_str);
				if($hours>0){
					$hours='+'.$hours;
				}
				$by_date_from->modify("".$hours." hours");
				$by_date_to->modify("".$hours." hours");

				$this->utils->debug_log('default_timezone', $default_timezone, 'date_from_str', $date_from_str, 'date_to_str', $date_to_str, 'hours', $hours);
				// $where[] ='total_player_game_hour.status = ?';
				// $values[] = Game_logs::STATUS_ACCEPTED;
				$where[] = "total_player_game_hour.end_at >= ? AND total_player_game_hour.end_at <= ?";
				$values[] = $this->utils->formatDateTimeForMysql($by_date_from);
				$values[] = $this->utils->formatDateTimeForMysql($by_date_to);
			}
			else{
				$default_timezone = $this->utils->getTimezoneOffset(new DateTime());
				$timezone = !empty($this->utils->getConfig('force_default_timezone_option')) ? $input['timezone'] : intval($input['timezone']);
				$hours = $default_timezone - $timezone;
				$date_from_str = $input['datetime_from'] . ' ' . $hours . ' hours';
				$date_to_str = $input['datetime_to'] . ' ' . $hours . ' hours';
				$this->utils->debug_log('default_timezone', $default_timezone, 'date_from_str', $date_from_str, 'date_to_str', $date_to_str, 'hours', $hours);
				$where[] ='total_player_game_hour.date_hour >= ?';
				$where[] ='total_player_game_hour.date_hour <= ?';
				$values[] = date('YmdH', strtotime($date_from_str));
				$values[] = date('YmdH', strtotime($date_to_str));

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

		// tag_list_included => affiliate_tag_list
		if (isset($input['affiliate_tag_list'])) {
            $affiliate_tag_list = $input['affiliate_tag_list'];
			$is_include_notag = null;
            if(is_array($affiliate_tag_list)) {
                $notag = array_search('notag',$affiliate_tag_list);
                if($notag !== false) {
                    unset($affiliate_tag_list[$notag]);
					$is_include_notag = true;
                }else{
					$is_include_notag = false;
				}
            } elseif ($affiliate_tag_list == 'notag') {
                $affiliate_tag_list = null;
				$is_include_notag = true;
            }

			$where_fragments = [];
			if($is_include_notag){
				// $where_fragments[] = 'player.playerId NOT IN (SELECT DISTINCT playerId FROM playertag)';
				$where_fragments[] = 'affiliates.affiliateId NOT IN (SELECT DISTINCT affiliateId FROM affiliatetag)';
			}

            if ( ! empty($affiliate_tag_list) ) {
                $affiliateTagList = is_array($affiliate_tag_list) ? implode(',', $affiliate_tag_list) : $affiliate_tag_list;
				// $where_fragments[] =  'player.playerId IN (SELECT DISTINCT playerId FROM playertag WHERE playertag.tagId IN ('.$tagList.'))';
				$where_fragments[] =  'affiliates.affiliateId IN (SELECT DISTINCT affiliateId FROM affiliatetag WHERE affiliatetag.tagId IN ('.$affiliateTagList.'))';
            }
			if( ! empty($where_fragments) ){
				$where[] = ' ('. implode(' OR ', $where_fragments ). ') ';
			}
        } // EOF if (isset($input['affiliate_tag_list'])) {...


		############################################

		if (isset($input['total_bet_from'])) {
			$having['total_bet >='] = $input['total_bet_from'];
		}

		if (isset($input['total_bet_to'])) {
			$having['total_bet <='] = $input['total_bet_to'];
		}

		if (isset($input['total_gain_from'])) {
			$having['total_gain >='] = $input['total_gain_from'];
		}

		if (isset($input['total_gain_to'])) {
			$having['total_gain <='] = $input['total_gain_to'];
		}

		if (isset($input['total_loss_from'])) {
			$having['total_loss >='] = $input['total_loss_from'];
		}

		if (isset($input['total_loss_to'])) {
			$having['total_loss <='] = $input['total_loss_to'];
		}

		if (isset($input['total_player'])) {
			$having['total_player <='] = $input['total_player'];
		}


        // to see test player game reports  on their user information page
         if(empty($player_id)){
            $where[] = "player.deleted_at IS NULL";
         }

         if($is_export){
            $this->data_tables->options['is_export']=true;
            if(empty($csv_filename)){
                $csv_filename=$this->utils->create_csv_filename(__FUNCTION__);
            }
            $this->data_tables->options['csv_filename']=$csv_filename;
		}


		# OUTPUT ######################################################################################################################################################################################
		$result = $this->data_tables->get_data($request, $columns, $table, $where, $values, $joins, $group_by, $having, false);
		if( ! empty($this->data_tables->last_query) ){
			$last_query = $this->data_tables->last_query;
			$result['list_last_query'] = $last_query;
		}

		if($is_export){
		    //drop result if export
			return $csv_filename;
		}

		if( ! empty($this->data_tables->last_query) ){
			$result['list_last_query'] = $this->data_tables->last_query;
		}

		$result['summary'] = array();
		$total_revenue_percent = $unsettle ? 'SUM(total_player_game_hour.loss_amount-total_player_game_hour.win_amount)/SUM(total_player_game_hour.bet_amount) * 100 total_revenue_percent' : 'SUM(total_player_game_hour.loss_amount-total_player_game_hour.win_amount)/SUM(total_player_game_hour.betting_amount) * 100 total_revenue_percent';
		$total_bet = $unsettle ? 'SUM(total_player_game_hour.bet_amount) total_bet ' : 'SUM(total_player_game_hour.betting_amount) total_bet ';
		$total_ave_count = $unsettle ? 'COUNT(total_player_game_hour.bet_amount) total_ave_count ' : 'COUNT(total_player_game_hour.betting_amount) total_ave_count ';
    	$total_ave_bet = $unsettle ? 'SUM(total_player_game_hour.bet_amount) /COUNT(total_player_game_hour.bet_amount)   ' : 'SUM(total_player_game_hour.betting_amount) / COUNT(total_player_game_hour.betting_amount)   as total_ave_bet ';
		$total_payout = $unsettle ? 'SUM(total_player_game_hour.bet_amount - (total_player_game_hour.loss_amount - total_player_game_hour.win_amount)) total_payout ' : 'SUM(total_player_game_hour.betting_amount - (total_player_game_hour.loss_amount - total_player_game_hour.win_amount)) total_payout ';

		// Summary calculation
		$summary = $this->data_tables->summary($request, $table, $joins, 'COUNT(DISTINCT total_player_game_hour.player_id) total_player, '.$total_bet.','.$total_ave_bet.','.$total_ave_count.'  ,SUM(total_player_game_hour.win_amount) as total_win, SUM(total_player_game_hour.win_amount) as total_gain, SUM(total_player_game_hour.loss_amount) total_loss,'.$total_payout.',SUM(total_player_game_hour.loss_amount-total_player_game_hour.win_amount) total_revenue,'.$total_revenue_percent.'', null, $columns, $where, $values);
		$result['summary_last_query'] = $this->data_tables->last_query;
		
		if(isset($summary[0])){
			array_walk($summary[0], function (&$value) {
				$value = round(floatval($value), 2);
			});
		} else {
			$summary[0] = array(
				'total_player' => 0,
				'total_bet' => 0,
				'total_ave_bet' => 0,
				'total_ave_count' => 0,
				'total_win' => 0,
				'total_gain' => 0,
				'total_loss' => 0,
				'total_payout' => 0,
				'total_revenue' => 0,
				'total_revenue_percent' => 0,
			);
		}
		$result['summary'] = $summary;


		// OGP-12293: Skip player_total_bets_per_game calculation if not used in view
		// OGP-17821: feature display_player_bets_per_game retired
		// OGP-18149: revert game report feature
		if ($this->utils->isEnabledFeature('display_player_bets_per_game')) {
			// ----- player_total_bets_per_game calculation
			$group_by2=[];
			$group_by2[] = 'total_player_game_hour.player_id, total_player_game_hour.game_platform_id';
			$player_total_bets_per_game = $this->data_tables->summary($request, $table, $joins, 'DISTINCT total_player_game_hour.player_id player_id , player.username, player.playerId as player_tag, total_player_game_hour.game_platform_id, '.$total_bet.', SUM(total_player_game_hour.win_amount) as total_win, SUM(total_player_game_hour.win_amount) as total_gain, SUM(total_player_game_hour.loss_amount) total_loss,'.$total_payout.', SUM(total_player_game_hour.loss_amount-total_player_game_hour.win_amount) total_revenue,'.$total_revenue_percent.'', $group_by2, $columns, $where, $values);
			$result['bets_per_game_last_query'] = $this->data_tables->last_query;

			$player_total_bets_per_game_map = [];
			$game_platform_header_map =[];
	        $sum_total_bets_map =[];
	        $sum_total_wins_map =[];
	        $sum_total_loss_map =[];
	        $sum_total_payout_map =[];
	        $sum_total_revenue_map =[];
	        $bet_details_map = [];

			foreach ($player_total_bets_per_game as $v) {

	            //make bet details array
				$bet_details_map[$v['player_id']][$v['game_platform_id']] = array(
					'game_platform_id' => $v['game_platform_id'],
					'total_bet' => $this->currencyFormatter($v['total_bet']),
					'total_win' => $this->currencyFormatter($v['total_win']),
					'total_loss' => $this->currencyFormatter($v['total_loss']),
					'total_payout' => $this->currencyFormatter($v['total_payout']),
					'total_revenue' => $this->currencyFormatter($v['total_revenue']),
					'total_revenue_percent' => $this->currencyFormatter($v['total_revenue_percent']),
				);

	            //array push every games bet per player
				$sum_total_bets_map[$v['player_id']][] = $v['total_bet'] ;
				$sum_total_wins_map[$v['player_id']][] = $v['total_win'] ;
				$sum_total_loss_map[$v['player_id']][] = $v['total_loss'] ;
				$sum_total_payout_map[$v['player_id']][] = $v['total_payout'] ;
				$sum_total_revenue_map[$v['player_id']][] = $v['total_revenue'] ;
				$sum_total_revenue_percent_map[$v['player_id']][] = $v['total_revenue_percent'] ;
				//add the bet items to get the total bets for all player game
	            $player_total_games_bet = $this->currencyFormatter(array_sum($sum_total_bets_map[$v['player_id']]));
	            $player_total_games_win = $this->currencyFormatter(array_sum($sum_total_wins_map[$v['player_id']]));
	            $player_total_games_loss = $this->currencyFormatter(array_sum($sum_total_loss_map[$v['player_id']]));
	            $player_total_games_payout = $this->currencyFormatter(array_sum($sum_total_payout_map[$v['player_id']]));
	            $player_total_games_revenue = $this->currencyFormatter(array_sum($sum_total_revenue_map[$v['player_id']]));
	            $player_total_games_revenue_percent = $this->currencyFormatter(array_sum($sum_total_revenue_percent_map[$v['player_id']]));
	             //construct now the player row
	            $player_row = [];
	            $player_row['player_id'] = $v['player_id'];
	            $player_row['username'] = $v['username'];
				$player_row['player_tag'] = player_tagged_list($v['player_id']);
	            $player_row['bet_details'] =  $bet_details_map[$v['player_id']];
	            $player_row['sum_total_bets'] = $player_total_games_bet;
	            $player_row['sum_total_wins'] = $player_total_games_win;
	            $player_row['sum_total_loss'] = $player_total_games_loss;
	            $player_row['sum_total_payout'] = $player_total_games_payout;
	            $player_row['sum_total_revenue'] = $player_total_games_revenue;
	            $player_row['sum_total_revenue_percent'] = $player_total_games_revenue_percent;
	            // add on main players map for json output
				$player_total_bets_per_game_map[$v['player_id']] =  $player_row;
				// make selective table headers  by making map -> associative override
				$game_platform_header_map[$v['game_platform_id']] = $v['game_platform_id'];

			}
	        $result['player_total_bets_per_game'] = $player_total_bets_per_game_map;
	        $result['game_platform_header_map'] = $game_platform_header_map;
	        // ----- End calculation of player_total_bets_per_game
	    }

		return $result;
	}

	public function gameReports_api($input) {
		$this->load->model([ 'player_model', 'game_logs' ]);

		$flag_unsettled = !empty($input['search_unsettled_games']);

		// 1: FROM
		$this->db->from( $flag_unsettled ? 'game_logs_unsettle AS H' : 'total_player_game_hour AS H');

		// 2: JOIN
		$this->db->join('game_type AS T'	, 'T.id = H.game_type_id'			, 'left')
			->join('game_description AS D'	, 'D.id = H.game_description_id'	, 'left')
			->join('external_system AS S'	, 'S.id = H.game_platform_id'		, 'left')
			->join('player AS P'			, 'P.playerId = H.player_id'		, 'left')
			->join('affiliates AS A'		, 'A.affiliateId = P.affiliateId'	, 'left')
		;

		// 2.5: PRESETS, DEFAULTS

		$group_by	= [];
		$wheres		= [];
		$where_in	= [];

		$show_game_platform = false;
        $show_game_type = false;
        $show_game = false;
        $show_player = false;

		// 3: GROUP_BY
		if (!empty($input['group_by'])) {

			// $group_by[] = $input['group_by'];

			switch ($input['group_by']) {
				case 'game_platform_id':
					$group_by[] = 'H.game_platform_id';
					$show_game_platform = true;
					break;
				case 'game_type_id':
					$group_by[] = 'H.game_type_id';
					$show_game_platform = true;
					$show_game_type = true;
					break;
				case 'game_description_id':
					$group_by[] = 'H.game_description_id';
					$show_game_platform = true;
					$show_game_type = true;
					$show_game = true;
					break;
				case 'player_id':
					$group_by[] = 'H.player_id';
					$show_player = true;
					break;
				case 'aff_id':
					$group_by[] = 'P.affiliateId';
					$show_player = true;
					break;
				case 'agent_id':
					$group_by[] = 'P.agent_id';
					$show_player = true;
					break;
				case 'game_type_and_player':
					$group_by[] = 'H.player_id,  H.game_type_id';
					$show_game_platform = true;
					$show_game_type = true;
					$show_player = true;
					break;
				case 'game_platform_and_player':
					$group_by[] = 'H.player_id,  H.game_platform_id';
					$show_game_platform = true;
					$show_player = true;
					break;
				case 'game_description_and_player':
					$group_by[] = 'H.player_id,  H.game_description_id';
					$show_game_platform = true;
					$show_game_type = true;
					$show_game = true;
					$show_player = true;
					break;
				default :
					$group_by[] = 'H.game_platform_id';
					$show_game_platform = true;
					break;
			}
		}

		foreach ($group_by as $gby) {
			$this->db->group_by($gby);
		}

		// 4: WHERE, WHERE_IN
		if (!empty($input['username'])) {
			$player_id = $this->player_model->getPlayerIdByUsername($input['username']);
		}

		// if (!empty($input['external_system'])) {
		// 	// $where[] = "S.id = ?";
		// 	// $values[] = $input['external_system'];
		// 	$this->db->where('S.id', $input[''])
		// 	$show_game_platform = true;
		// }


		// // game_type_multiple -- UNIMPLEMENTED
		// if (!empty($input['game_type_multiple'])) {
		// 	if($input['game_type_multiple'] != 0) {
		// 		$game_type_arr = explode('+', $input['game_type_multiple']);

  //               if (!empty($input['game_type'])){
  //               	array_push($game_type_arr, $input['game_type']);
  //               }

		// 		$game_types = (count($input['game_type_multiple']) > 1) ? $game_type_arr : $game_type_arr ;
		// 		$where[] = "game_type.id in (" . implode(',', $game_types) . ") ";
		// 		$group_by[] = 'total_player_game_hour.game_type_id';
		// 		$show_game_type = true;
		// 	}
		// }

		// // game_type_multiple -- UNIMPLEMENTED
		// if (!empty($input['game_type']) && !isset($input['game_type_multiple'])) {
		// 	if($input['game_type'] != 0) {
		// 		$where[] = "game_type.id = ?";
		// 		$values[] = $input['game_type'];
		// 		$show_game_type = true;

		// 	}
		// }

		if (!empty($player_id)) {
			// $where[] = "total_player_game_hour.player_id = ?";
			// $values[] = $player_id;
			$wheres['H.player.id'] = $player_id;
			$show_player = true;
		} else if (!empty($input['affiliate_username'])) {

			// $where[] = "affiliates.username = ?";
			// $values[] = $input['affiliate_username'];
			$wheres['A.username'] = $input['affiliate_username'];
			$show_player = true;
		}
		// if (!empty($input['only_under_agency']) && $input['only_under_agency'] != '') {
  //           $show_game_platform = true;
  //           $show_game_type = true;
  //           $show_game = true;
  //           $show_player = true;
		// 	$where[] = "player.agent_id IS NOT NULL";
		// 	if (!isset($input['agent_name'])) {
		// 		if (!empty($input['current_agent_name']) && $input['current_agent_name'] != '') {
		// 			$input['agent_name'] = $input['current_agent_name'];
		// 		}
		// 	}
		// }
		if (!empty($input['agent_username'])) {
            $show_game_platform = true;
            $show_game_type = true;
            $show_game = true;
            $show_player = true;
            $this->load->model(array('agency_model'));
			$agent_detail = $this->agency_model->get_agent_by_name($input['agent_username']);

			if (!empty($input['include_all_downlines']) && !empty($agent_detail)) {
				// $joins['agency_agents'] = 'player.agent_id = agency_agents.agent_id';
				$this->db->join('agency_agencys AS AA', 'P.agent_id = AA.agent_id');
				$parent_ids = array($agent_detail['agent_id']);
				$sub_ids = array();
				$all_ids = $parent_ids;
				while (!empty($sub_ids = $this->agency_model->get_sub_agent_ids_by_parent_id($parent_ids))) {
					//$this->utils->debug_log('sub_ids', $sub_ids);
					$all_ids = array_merge($all_ids, $sub_ids);
					$parent_ids = $sub_ids;
					$sub_ids = array();
				}

				// foreach ($all_ids as $i => $id) {
				// 	if ($i == 0) {
				// 		$w = "(player.agent_id = ?";
				// 	} else {
				// 		$w .= " OR player.agent_id = ?";
				// 	}
				// 	$values[] = $id;
				// }
				// $w .= ")";
				// $where[] = $w;
				$where_in['P.agent_id'] = implode(',', $all_ids);
			} else {
				// $where[] = "player.agent_id = ?";
				// $values[] = $agent_detail['agent_id'];
				$wheres['P.agent_id'] = $agent_detail['agent_id'];
			}
		}

		foreach ($wheres as $col => $val) {
			$this->db->where($col, $val);
		}
		foreach ($where_in as $col => $vals) {
			$this->db->where_in($col, $vals);
		}

		// 5: SELECTS
		$this->db->select('P.playerId, P.levelName, P.groupName, A.affiliateId, S.id');
		if ($show_game) {
			$this->db->select('D.id AS game_desc_id');
			$this->db->select('D.game_name AS game_name');
		}
		if ($show_game_platform)
			{ $this->db->select('S.system_code AS system_code'); }
		if ($show_game_type) {
			$this->db->select('T.id AS game_type_id');
			$this->db->select('T.game_type_lang AS game_type_lang');
		}
		if ($show_player) {
			$this->db->select('P.username AS username');
			$this->db->select('P.levelId AS player_level');
		}
		$this->db->select('A.username AS affiliate');
		$this->db->select('COUNT(DISTINCT H.player_id) AS total_player');
		if ($flag_unsettled) {
			$this->db->select('SUM(H.bet_amount) AS total_bet');
			$this->db->select('SUM(H.loss_amount - H.win_amount) / SUM(H.bet_amount) AS rate');
		}
		else {
			$this->db->select('SUM(H.betting_amount) AS total_bet');
			$this->db->select('SUM(H.loss_amount - H.win_amount) / SUM(H.betting_amount) AS rate');
		}
		$this->db->select('SUM(H.win_amount) AS total_gain');
		$this->db->select('SUM(H.loss_amount) AS total_loss');
		$this->db->select('SUM(H.loss_amount - H.win_amount) AS payout');


		// 6: TIMEZONE, DATETIME FROM/TO WHERES
		if (isset($input['datetime_from'], $input['datetime_to'])) {
			// Append default time to datetime_from/to (which are stripped of time part in API cotroller)
			$datetime_from = date('c', strtotime("{$input['datetime_from']} 00:00:00"));
			$datetime_to = date('c', strtotime("{$input['datetime_to']} 23:59:59"));

			// $this->utils->debug_log(__FUNCTION__, ['datetime_fr'])

			// Calculate tz diff
			$default_tz = $this->utils->getTimezoneOffset(new DateTime());
			$hr_diff = $default_tz - intval($input['timezone']);
			// Correct datetime from/to by tz diff
			// $datetime_from	= strtotime("{$input['datetime_from']} {$hr_diff} hours");
			// $datetime_to	= strtotime("{$input['datetime_to']} {$hr_diff} hours");
			$datetime_from	= strtotime("{$datetime_from} {$hr_diff} hours");
			$datetime_to	= strtotime("{$datetime_to} {$hr_diff} hours");

			$this->utils->debug_log(__FUNCTION__, 'tz-datetime',
				[ 'input_tz' => $input['timezone'] ,
				  'default_tz' => $default_tz ,
				  'hr_diff' => $hr_diff ,
				  'datetime_from_st' => "{$input['datetime_from']} {$hr_diff} hours" ,
				  'datetime_from' => date('c', $datetime_from) ,
				  'datetime_to_st' => "{$input['datetime_to']} {$hr_diff} hours" ,
				  'datetime_to' => date('c', $datetime_to)
				]);

			// Wheres
			if ($flag_unsettled) {
				// Search unsettled: table game_logs_unsettle
				$this->db->where('H.status', Game_logs::STATUS_ACCEPTED);
				$this->db->where('H.end_at >=', $datetime_from);
				$this->db->where('H.end_at <=', $datetime_to);
			}
			else {
				// Search settled:   table total_player_game_hour
				$this->db->where('H.date_hour >=', date('YmdH', $datetime_from));
				$this->db->where('H.date_hour <=', date('YmdH', $datetime_to));
			}
		}

		// 7: HAVINGS
		$having = [];

		if (!empty($input['total_bet_from']))
			{ $having['total_bet >=']	= $input['total_bet_from']; }

		if (!empty($input['total_bet_to']))
			{ $having['total_bet <=']	= $input['total_bet_to']; }

		if (!empty($input['total_gain_from']))
			{ $having['total_gain >=']	= $input['total_gain_from']; }

		if (!empty($input['total_gain_to']))
			{ $having['total_gain <=']	= $input['total_gain_to']; }

		if (!empty($input['total_loss_from']))
			{ $having['total_loss >=']	= $input['total_loss_from']; }

		if (!empty($input['total_loss_to']))
			{ $having['total_loss <=']	= $input['total_loss_to']; }

		if (!empty($input['total_player']))
			{ $having['total_player <=']	= $input['total_player']; }

		foreach ($having as $col => $val) {
			$this->db->having($col, $val);
		}

		// 8: WHERE FOR TEST PLAYERS
		// to see test player game reports  on their user information page
		if (empty($player_id))
			// { $where[] = "player.deleted_at IS NULL";}
			{ $this->db->where("P.deleted_at IS NULL", null, false); }

		$gr_rows = $this->runMultipleRowArray();

		// 9: OUTPUT (NOT CONVERTED YET)


		// 9.9: OUR OWN FORMAT
		$sum = ['total_bet'	=> 0, 'total_gain'	=> 0, 'total_loss' => 0, 'payout' => 0 ,
			// 'rate' => 0
		 ];
		if (!empty($gr_rows)) {
			foreach ($gr_rows as & $row) {
				array_walk($row, function (& $val, $key) use (& $sum) {
					$prec = [
						'total_bet'		=> 2,
						'rate'			=> 5,
						'total_gain'	=> 2,
						'total_loss'	=> 2,
						'payout'		=> 2
					];
					if (isset($prec[$key])) {
						$val = round(floatval($val), $prec[$key]);
					}
					if (isset($sum[$key])) {
						$sum[$key] += $val;
					}
				});
			}

			$sum['rate'] = round($sum['payout'] / $sum['total_bet'], 5);
		}

		// 10: FORMAT (NOT CONVERTED YET)


		$report_res = [
			'rows' => $gr_rows ,
			'sums' => $sum
		];

		// FINAL: return values
		return [ 'res' => $report_res, 'sql' => $this->db->last_query() ];

	} // End of gameReports_api()

    /**
     * detail: get agency game report
     *
     * @param array $request
     * @param int $player_id total_player_game_hour player_id
     * @param Boolean $is_export
     *
     * @return array
     */
	public function get_agency_game_reports($request, $player_id = null, $is_export = false) {
		$this->load->library(array('data_tables'));
		$this->load->model(array('player_model'));
		// $request = $this->input->post();
		$input = $this->data_tables->extra_search($request);
		$table = 'total_player_game_hour';
		$joins = array();
		$where = array();
		$values = array();
		$group_by = array();
		$having = array();

		$joins['game_type'] = 'game_type.id = total_player_game_hour.game_type_id';
		$joins['game_description'] = 'game_description.id = total_player_game_hour.game_description_id';
		$joins['external_system'] = 'external_system.id = total_player_game_hour.game_platform_id';
		$joins['player'] = 'player.playerId = total_player_game_hour.player_id';

		$group_by_field = null;
		$show_game_platform = false;
		$show_game_type = false;
		$show_game = false;
		$show_player = false;
		if (isset($input['group_by'])) {
			$group_by_field = $input['group_by'];
			switch ($input['group_by']) {
			case 'game_platform_id':
				$group_by[] = 'total_player_game_hour.game_platform_id';
				$show_game_platform = true;
				break;
			case 'game_type_id':
				$group_by[] = 'total_player_game_hour.game_type_id';
				$show_game_platform = true;
				$show_game_type = true;
				break;
			case 'game_description_id':
				$group_by[] = 'total_player_game_hour.game_description_id';
				$show_game_platform = true;
				$show_game_type = true;
				$show_game = true;
				break;
			case 'player_id':
				$group_by[] = 'total_player_game_hour.player_id';
				$show_player = true;
				break;
			}
		}

		if (isset($input['username'])) {
			$player_id = $this->player_model->getPlayerIdByUsername($input['username']);
		}

		if (isset($player_id)) {
			$where[] = "total_player_game_hour.player_id = ?";
			$values[] = $player_id;
			$show_player = true;
        }
		if (isset($input['only_under_agency']) && $input['only_under_agency'] != '') {
            // $show_game_platform = true;
            // $show_game_type = true;
            // $show_game = true;
            // $show_player = true;
			$where[] = "player.agent_id IS NOT NULL";
			if (!isset($input['agent_name'])) {
				if (isset($input['current_agent_name']) && $input['current_agent_name'] != '') {
					$input['agent_name'] = $input['current_agent_name'];
				}
			}
		}
		if (isset($input['agent_name'])) {
            // $show_game_platform = true;
            // $show_game_type = true;
            // $show_game = true;
            // $show_player = true;
            $this->load->model(array('agency_model'));
			$agent_detail = $this->agency_model->get_agent_by_name($input['agent_name']);

			if (isset($input['include_all_downlines']) && $input['include_all_downlines'] == true) {
				$joins['agency_agents'] = 'player.agent_id = agency_agents.agent_id';
				$parent_ids = array($agent_detail['agent_id']);
				$sub_ids = array();
				$all_ids = $parent_ids;
				while (!empty($sub_ids = $this->agency_model->get_sub_agent_ids_by_parent_id($parent_ids))) {
					//$this->utils->debug_log('sub_ids', $sub_ids);
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

		# DEFINE TABLE COLUMNS ########################################################################################################################################################################
		$i = 0;
		$columns = array(
			array(
				'alias' => 'game_description_id',
				'select' => $show_game ? 'game_description.id' : '"N/A"',
			),
			array(
				'alias' => 'game_type_id',
				'select' => $show_game_type ? 'game_type.id' : '"N/A"',
			),
			array(
				'dt' => $i++,
				'alias' => 'system_code',
				'select' => $show_game_platform ? 'external_system.system_code' : '"N/A"',
				'name' => lang('System Code'),
			),
			array(
				'dt' => $i++,
				'alias' => 'game_type_lang',
				'select' => $show_game_type ? 'game_type.game_type_lang' : '"N/A"',
				'formatter' => 'languageFormatter',
				'name' => lang('Game Type Language'),
			),
			array(
				'dt' => $i++,
				'alias' => 'game_name',
				'select' => $show_game ? 'game_description.game_name' : '"N/A"',
				'formatter' => 'languageFormatter',
				'name' => lang('Game Name'),
			),
			array(
				'dt' => $i++,
				'alias' => 'username',
				'select' => $show_player ? 'player.username' : '"N/A"',
				'name' => lang('Username'),
			),
			array(
				'dt' => $i++,
				'alias' => 'total_player',
				'select' => 'COUNT(DISTINCT total_player_game_hour.player_id)',
				// 'formatter' => $input['group_by'] == 'total_player_game_hour.game_description_id' ? function ($d, $row) {
				// return $d;
				// return '&game_description_id=' . $row['game_description_id'] . '">' . $d . '</a>';
				// } : function ($d, $row) {
				// return $d;
				// return '&game_type_id=' . $row['game_type_id'] . '">' . $d . '</a>';
				// },
				'name' => lang('Total Player'),
			),
			array(
				'dt' => $i++,
				'alias' => 'total_bet',
				'select' => 'SUM(total_player_game_hour.betting_amount)',
				'formatter' => 'currencyFormatter',
				'name' => lang('Total Bet'),
			),
			array(
				'dt' => $i++,
				'alias' => 'payout',
				'select' => 'SUM(total_player_game_hour.betting_amount-(total_player_game_hour.loss_amount-total_player_game_hour.win_amount))',
				'formatter' => 'currencyFormatter',
				'name' => lang('Payout'),
			),
			array(
				'dt' => $i++,
				'alias' => 'total_gain',
				'select' => 'SUM(total_player_game_hour.win_amount)',
				'formatter' => 'currencyFormatter',
				'name' => lang('Total Gain'),
			),
			array(
				'dt' => $i++,
				'alias' => 'total_loss',
				'select' => 'SUM(total_player_game_hour.loss_amount)',
				'formatter' => 'currencyFormatter',
				'name' => lang('Total Loss'),
			),
			array(
				'dt' => $i++,
				'alias' => 'game_revenue',
				'select' => 'SUM(total_player_game_hour.loss_amount-total_player_game_hour.win_amount)',
				'formatter' => 'currencyFormatter',
				'name' => lang('Game Revenue'),
			),
			array(
				'dt' => $i++,
				'alias' => 'game_revenue_percent',
				'select' => 'SUM(total_player_game_hour.loss_amount-total_player_game_hour.win_amount)/SUM(total_player_game_hour.betting_amount)',
				'formatter' => 'percentageFormatter',
				'name' => lang('Game Revenue %'),
			),
		);

		# FILTER ######################################################################################################################################################################################

		if (isset($input['search_on_date']) && $input['search_on_date']) {
            if (isset($input['date_from'], $input['date_to'], $input['timezone'])) {
				$default_timezone = $this->utils->getTimezoneOffset(new DateTime());
				$hours = $default_timezone - intval($input['timezone']);
				$date_from_str = $input['date_from'] . ' ' . $hours . ' hours';
				$date_to_str = $input['date_to'] . ' ' . $hours . ' hours';

				$where[] = "total_player_game_hour.date_hour >= ?";
                $where[] = "total_player_game_hour.date_hour <= ?";

                $values[] = (new DateTime($date_from_str))->format('YmdH');
                $values[] = (new DateTime($date_to_str))->format('YmdH');
            }else if (isset($input['date_from'], $input['date_to'])) {
				$where[] = "total_player_game_hour.date_hour >= ?";
				$where[] = "total_player_game_hour.date_hour <= ?";
				// $where[] = "total_player_game_hour.date BETWEEN ? AND ?";
				$values[] = (new DateTime($input['date_from']))->format('YmdH');
                $values[] = (new DateTime($input['date_to']))->format('YmdH');
			}
		}

        /*
		if (isset($input['date_from'], $input['date_to'])) {
			if (!isset($input['hour_from']) || empty($input['hour_from'])) {
				$input['hour_from'] = '00';
			}
			if (!isset($input['hour_to']) || empty($input['hour_to'])) {
				$input['hour_to'] = '23';
			}
			$where[] = "total_player_game_hour.date_hour >= ?";
			$where[] = "total_player_game_hour.date_hour <= ?";

			// $where[] = "total_player_game_hour.date BETWEEN ? AND ?";
			$values[] = (new DateTime($input['date_from']))->format('Ymd') . $input['hour_from'];
			$values[] = (new DateTime($input['date_to']))->format('Ymd') . $input['hour_to'];
		}
         */

		if (isset($input['total_bet_from'])) {
			$having['total_bet >='] = $input['total_bet_from'];
		}

		if (isset($input['total_bet_to'])) {
			$having['total_bet <='] = $input['total_bet_to'];
		}

		if (isset($input['total_gain_from'])) {
			$having['total_gain >='] = $input['total_gain_from'];
		}

		if (isset($input['total_gain_to'])) {
			$having['total_gain <='] = $input['total_gain_to'];
		}

		if (isset($input['total_loss_from'])) {
			$having['total_loss >='] = $input['total_loss_from'];
		}

		if (isset($input['total_loss_to'])) {
			$having['total_loss <='] = $input['total_loss_to'];
		}

		# OUTPUT ######################################################################################################################################################################################
        // $this->utils->debug_log('GAME_REPORT where values', $where, $values, $group_by, $having, $joins);
		$result = $this->data_tables->get_data($request, $columns, $table, $where, $values, $joins, $group_by, $having, false);

		// $result['summary'] = array();

		// $summary = $this->data_tables->summary($request, $table, $joins,
		// 	'SUM(total_player_game_hour.betting_amount) total_bet', null, $columns, $where, $values);

		// $result['summary'][0]['total_amount'] = $this->utils->formatCurrencyNoSym($summary[0]['total_amount']);

		return $result;
	} // get_agency_game_reports }}}2

	/**
	 * detail: get game descriptions
	 *
	 * @param array $request
	 * @param Boolean $is_export
	 *
	 * @return array
	 */
	public function gameDescriptionList($request, $is_export = false) {
		$visible_columns = isset($request['visible_columns']) ? $request['visible_columns'] : [];
		$db_false = 0;
		$db_true = 1;

		$this->load->library(array('data_tables'));
		$this->load->model(array('agency_model'));

		$i = 0;
	//	$request = $this->input->post();

		$where = array();
		$values = array();
		$columns = array();

		$columns[] = array(
			'select' => 'game_description.deleted_at',
			'alias' => 'deleted_at',
			'name' => lang('Deleted At')
		);
		$columns[] = array(
			'select' => 'external_system.system_code',
			'alias' => 'system_code',
			'name' => lang('Game Platform Id')
		);

        $game_list_column_order = isset($this->utils->getConfig('game_list_column_order')['custom_order']) ? $this->utils->getConfig('game_list_column_order')['custom_order'] : $this->utils->getConfig('game_list_column_order')['default_order'];

        foreach($game_list_column_order as $alias_order){
            switch($alias_order){
                case 'action':
                    $columns[] = array(
                        'dt' => $i++,
                        'select' => 'game_description.id',
                        'alias' => 'id',
                        'name' => lang('sys.pay.systemid'),
                        'default' => true,
                        'formatter' => function ($d, $row) use ($is_export) {
                            if ($is_export) {
                                return $d;
                            }else{
                                $output = '<a href="" title="' . lang('sys.gd23') . '" class="viewGameDescriptionHistory" data-toggle="modal" data-target="#gameDescriptionHistory" data-row-id="'. $d . '" ><span class="glyphicon glyphicon-list-alt"></span></a>';
                                // if ($row['no_cash_back'] == 1) {
                                // 	$output .= '<a href="' . site_url('game_description/deactivateNoCashback/' . $d) . '"  ><span style="color:#004d00" title="' . lang('lang.deactivate.no.cashback') . '" class="glyphicon glyphicon-ok-sign deactivate-no-cashback"></span></a>';
                                // } else {
                                // 	$output .= '<a href="' . site_url('game_description/activateNoCashback/' . $d) . '"  ><span style="color:#990000;" title="' . lang('lang.activate.no.cashback') . '" class="glyphicon glyphicon-remove-circle activate-no-cashback"></span></a>';
                                // }
                                // if ($row['void_bet'] == 1) {
                                // 	$output .= '<a href="' . site_url('game_description/deactivateVoidBet/' . $d) . '"  ><span style="color:#009900;" title="' . lang('lang.deactivate.void.bet') . '" class="glyphicon glyphicon-ok-sign deactivate-void-bet"></span></a>';
                                // } else {
                                // 	$output .= '<a href="' . site_url('game_description/activateVoidBet/' . $d) . '"  ><span  style="color:#ff3333;" title="' . lang('lang.activate.void.bet') . '"  class="glyphicon glyphicon-remove-circle activate-void-bet"></span></a>';
                                // }
                                if (empty($row['deleted_at'])) {
                                    $output .= '<a href="javascript:void(0)" title="' . lang('Delete this game') . '" class="delete-gd" id="delete_gd-' . $d . '" ><span style="color:#ff3333" class="glyphicon glyphicon-trash"></span></a>';
                                }

                                if (empty($row['deleted_at']) && !empty($row['system_code'])) {
                                    $output .= '<a href="javascript:void(0)" title="' . lang('sys.gd23') . '" class="edit-gd" id="edit_gd-' . $d . '" ><span class="glyphicon glyphicon-edit"></span></a>';
                                }

                                if ($row['status']) {
                                    $output .= '<a href="javascript:void(0)" title="' . lang('Deactivate this game') . '" class="deactivate-gd" id="deactivate_gd-' . $d . '" ><span style="color:#ff3333" class="glyphicon glyphicon-remove"></span></a>';
                                }else{
                                    $output .= '<a href="javascript:void(0)" title="' . lang('Activate this game') . '" class="activate-gd" id="activate_gd-' . $d . '" ><span style="color:#ff3333" class="glyphicon glyphicon-ok-sign"></span></a>';
                                }

                                if ($row['flag_show_in_site']) {
                                    $output .= '<a href="javascript:void(0)" title="' . lang('Hide in Site') . '" class="hideinsite-gd" id="hideinsite_gd-' . $d . '" ><span style="color:#ff3333" class="glyphicon glyphicon-eye-close"></span></a>';
                                }else{
                                    $output .= '<a href="javascript:void(0)" title="' . lang('Show in Site') . '" class="showinsite-gd" id="showinsite_gd-' . $d . '" ><span style="color:#ff3333" class="glyphicon glyphicon-eye-open"></span></a>';
                                }

                                return $output;
                            }
                        },
                    );
                    break;
                case 'english_name':
                    $columns[] = array(
                        'dt' => $i++,
                        'select' => 'game_description.english_name',
                        'alias' => 'english_name',
                        'name' => lang('lang.english.name'),
                        'formatter' => function ($d, $row) {
                            return $d ?: '-';
                        },
                    );
                    break;
                case 'game_type':
                    $columns[] = array(
                        'dt' => $i++,
                        'select' => 'game_type.game_type',
                        'alias' => 'game_type',
                        'formatter' => 'languageFormatter',
                        'name' => lang('sys.gd6')
                    );
                    break;
                case 'system_code':
                    $columns[] = array(
                        'dt' => $i++,
                        'select' => 'external_system.system_code',
                        'alias' => 'system_name',
                        'name' => lang('sys.gd7')
                    );
                    break;
                case 'game_name':
                    $columns[] = array(
                        'dt' => $i++,
                        'select' => 'game_description.game_name',
                        'alias' => 'game_name',
                        'formatter' => 'languageFormatter',
                        'name' => lang('sys.gd8')
                    );
                    break;
                case 'game_code':
                    $columns[] = array(
                        'dt' => $i++,
                        'select' => 'game_description.game_code',
                        'alias' => 'game_code',
                        'name' => lang('sys.gd9')
                    );
                    break;
				case 'external_game_id':
					$columns[] = array(
						'dt' => $i++,
						'select' => 'game_description.external_game_id',
						'alias' => 'external_game_id',
						'name' =>  lang('External Game ID')
					);
					break;
                case 'rtp':
                    $columns[] = array(
                        'dt' => $i++,
                        'select' => 'game_description.rtp',
                        'alias' => 'rtp',
                        'name' => lang('sys.gd42'),
                        'formatter' => function ($d, $row) {
                            return $d ?: '-';
                        },
                    );
                    break;
                case 'attributes':
                    $columns[] = array(
                        'dt' => $i++,
                        'select' => 'game_description.attributes',
                        'alias' => 'attributes',
                        'name' => lang('Game Attributes'),
                        'formatter' => function ($d, $row) {
                            return $d ?: '-';
                        },
                    );
                    break;
                case 'progressive':
                    $columns[] = array(
                        'dt' => $i++,
                        'select' => 'game_description.progressive',
                        'alias' => 'progressive',
                        'name' => lang('sys.gd10'),
                        'formatter' => function ($d, $row) use ($is_export){
                            if ($is_export) {
                                return (($d == 1) ? '☑' : '☐') ;
                            }else{
                                return '<i class="' . (($d == 1) ? 'glyphicon glyphicon-check' : 'glyphicon glyphicon-unchecked') . '"></i>';
                            }
                        },
                    );
                    break;
                case 'mobile_enabled':
                    $columns[] = array(
                        'dt' => $i++,
                        'select' => 'game_description.mobile_enabled',
                        'alias' => 'mobile_enabled',
                        'name' => lang('Mobile'),
                        'formatter' => function ($d, $row) use ($is_export)  {
                            if ($is_export) {
                                return (($d == 1) ? '☑' : '☐') ;
                            }else{
                                return '<i class="' . (($d == 1) ? 'glyphicon glyphicon-check' : 'glyphicon glyphicon-unchecked') . '"></i>';
                            }
                        },
                    );
                    break;
                case 'flash_enabled':
                    $columns[] = array(
                        'dt' => $i++,
                        'select' => 'game_description.flash_enabled',
                        'alias' => 'flash_enabled',
                        'name' => lang('Flash'),
                        'formatter' => function ($d, $row) use ($is_export)  {
                            if ($is_export) {
                                return (($d == 1) ? '☑' : '☐') ;
                            }else{
                                return '<i class="' . (($d == 1) ? 'glyphicon glyphicon-check' : 'glyphicon glyphicon-unchecked') . '"></i>';
                            }
                        },
                    );
                    break;
                case 'html_five_enabled':
                    $columns[] = array(
                        'dt' => $i++,
                        'select' => 'game_description.html_five_enabled',
                        'alias' => 'html_five_enabled',
                        'name' => lang('HTML5'),
                        'formatter' => function ($d, $row) use ($is_export)  {
                            if ($is_export) {
                                return (($d == 1) ? '☑' : '☐') ;
                            }else{
                                return '<i class="' . (($d == 1) ? 'glyphicon glyphicon-check' : 'glyphicon glyphicon-unchecked') . '"></i>';
                            }
                        },
                    );
                    break;
                case 'enabled_on_ios':
                    $columns[] = array(
                        'dt' => $i++,
                        'select' => 'game_description.enabled_on_ios',
                        'alias' => 'enabled_on_ios',
                        'name' => lang('IOS'),
                        'formatter' => function ($d, $row) use ($is_export)  {
                            if ($is_export) {
                                return (($d == 1) ? '☑' : '☐') ;
                            }else{
                                return '<i class="' . (($d == 1) ? 'glyphicon glyphicon-check' : 'glyphicon glyphicon-unchecked') . '"></i>';
                            }
                        },
                    );
                    break;
                case 'enabled_on_android':
                    $columns[] = array(
                        'dt' => $i++,
                        'select' => 'game_description.enabled_on_android',
                        'alias' => 'enabled_on_android',
                        'name' => lang('Android'),
                        'formatter' => function ($d, $row) use ($is_export)  {
                            if ($is_export) {
                                return (($d == 1) ? '☑' : '☐') ;
                            }else{
                                return '<i class="' . (($d == 1) ? 'glyphicon glyphicon-check' : 'glyphicon glyphicon-unchecked') . '"></i>';
                            }
                        },
                    );
                    break;
                case 'dlc_enabled':
                    $columns[] = array(
                        'dt' => $i++,
                        'select' => 'game_description.dlc_enabled',
                        'alias' => 'dlc_enabled',
                        'name' => lang('DLC'),
                        'formatter' => function ($d, $row) use ($is_export)  {
                            if ($is_export) {
                                return (($d == 1) ? '☑' : '☐') ;
                            }else{
                                return '<i class="' . (($d == 1) ? 'glyphicon glyphicon-check' : 'glyphicon glyphicon-unchecked') . '"></i>';
                            }
                        },
                    );
                    break;
                case 'desktop_enabled':
                    $columns[] = array(
                        'dt' => $i++,
                        'select' => 'game_description.desktop_enabled',
                        'alias' => 'desktop_enabled',
                        'name' => lang('Desktop'),
                        'formatter' => function ($d, $row) use ($is_export)  {
                            if ($is_export) {
                                return (($d == 1) ? '☑' : '☐') ;
                            }else{
                                return '<i class="' . (($d == 1) ? 'glyphicon glyphicon-check' : 'glyphicon glyphicon-unchecked') . '"></i>';
                            }
                        },
                    );
                    break;
                case 'offline_enabled':
                    $columns[] = array(
                        'dt' => $i++,
                        'select' => 'game_description.offline_enabled',
                        'alias' => 'offline_enabled',
                        'name' => lang('Available Offline'),
                        'formatter' => function ($d, $row) use ($is_export)  {
                            if ($is_export) {
                                return (($d == 1) ? '☑' : '☐') ;
                            }else{
                                return '<i class="' . (($d == 1) ? 'glyphicon glyphicon-check' : 'glyphicon glyphicon-unchecked') . '"></i>';
                            }
                        },
                    );
                    break;
                case 'flag_hot_game':
                    $columns[] = array(
                        'dt' => $i++,
                        'select' => 'game_description.flag_hot_game',
                        'alias' => 'flag_hot_game',
                        'name' => lang('Hot Game'),
                        'formatter' => function ($d, $row) use ($is_export)  {
                            if ($is_export) {
                                return (($d == 1) ? '☑' : '☐') ;
                            }else{
                                return '<i class="' . (($d == 1) ? 'glyphicon glyphicon-check' : 'glyphicon glyphicon-unchecked') . '"></i>';
                            }
                        },
                    );
                    break;
                case 'flag_new_game':
                    $columns[] = array(
                        'dt' => $i++,
                        'select' => 'game_description.flag_new_game',
                        'alias' => 'flag_new_game',
                        'name' => lang('New Game'),
                        'formatter' => function ($d, $row) use ($is_export)  {
                            if ($is_export) {
                                return (($d == 1) ? '☑' : '☐') ;
                            }else{
                                return '<i class="' . (($d == 1) ? 'glyphicon glyphicon-check' : 'glyphicon glyphicon-unchecked') . '"></i>';
                            }
                        },
                    );
                    break;
                case 'tag_code':
                    $columns[] = array(
                        'dt' => $i++,
                        'select' => 'group_concat(game_tags.tag_code)',
                        'alias' => 'tag_code',
                        'name' => lang('Tag Code'),
                        'formatter' => function ($d, $row) {
                            return $d ?: '-';
                        },
                    );
                    break;
                case 'note':
                    $columns[] = array(
                        'dt' => $i++,
                        'select' => 'game_description.note',
                        'alias' => 'note',
                        'name' => lang('sys.gd11'),
                        'formatter' => function ($d, $row) {
                            return $d ?: '-';
                        },
                    );
                    break;
                case 'no_cash_back':
                    $columns[] = array(
                        'dt' => $i++,
                        'select' => 'game_description.no_cash_back',
                        'alias' => 'no_cash_back',
                        'name' => lang('sys.gd18'),
                        'formatter' => function ($d, $row) use ($is_export)  {
                            if ($is_export) {
                                return (($d == 1) ? '☑' : '☐') ;
                            }else{
                                return '<i class="' . (($d == 1) ? 'glyphicon glyphicon-check' : 'glyphicon glyphicon-unchecked') . '"></i>';
                            }
                        },
                    );
                    break;
                case 'void_bet':
                    $columns[] = array(
                        'dt' => $i++,
                        'select' => 'game_description.void_bet',
                        'alias' => 'void_bet',
                        'name' => lang('sys.gd19'),
                        'formatter' => function ($d, $row) use ($is_export) {
                            if ($is_export) {
                                return (($d == 1) ? '☑' : '☐') ;
                            }else{
                                return '<i class="' . (($d == 1) ? 'glyphicon glyphicon-check' : 'glyphicon glyphicon-unchecked') . '"></i>';
                            }
                        },
                    );
                    break;
                case 'status':
                    $columns[] = array(
                        'dt' => $i++,
                        'select' => 'game_description.status',
                        'alias' => 'status',
                        'name' => lang('sys.gd16'),
                        'formatter' => function ($d, $row) use ($is_export) {
                            if ($is_export) {
                                return (($d == 1) ? '☑' : '☐') ;
                            }else{
                                return '<i id="status-' . $row['id'] .'" class="' . (($d == 1) ? 'glyphicon glyphicon-check' : 'glyphicon glyphicon-unchecked') . '"></i>';
                            }
                        },
                    );
                    break;
                case 'flag_show_in_site':
                    $columns[] = array(
                        'dt' => $i++,
                        'select' => 'game_description.flag_show_in_site',
                        'alias' => 'flag_show_in_site',
                        'name' => lang('sys.gd17'),
                        'formatter' => function ($d, $row) use ($is_export) {
                            if ($is_export) {
                                return (($d == 1) ? '☑' : '☐') ;
                            }else{
                                return '<i id="flag_show_in_site-' . $row['id'] .'" class="' . (($d == 1) ? 'glyphicon glyphicon-check' : 'glyphicon glyphicon-unchecked') . '"></i>';
                            }
                        },
                    );
                    break;
                case 'game_order':
                    $columns[] = array(
                        'dt' => $i++,
                        'select' => 'game_description.game_order',
                        'alias' => 'game_order',
                        'name' => lang('sys.gd20'),
                        'formatter' => function ($d, $row) {
                            return $d ?: '-';
                        },
                    );
                    break;
                case 'tag_game_order':
                    $columns[] = array(
                        'dt' => $i++,
                        'select' => 'game_tag_list.game_order',
                        'alias' => 'tag_game_order',
                        'name' => lang('Tag Game Order'),
                        'formatter' => function ($d, $row) {
                            return $d ?: '-';
                        },
                    );
                    break;
				case 'release_date':
					$columns[] = array(
						'dt' => $i++,
						'select' => 'game_description.release_date',
						'alias' => 'release_date',
						'name' => lang('sys.gd41'),
						'formatter' => function ($d, $row) {
							return $d ? date('Y-m-d H:i:s', strtotime($d)): '-';
						},
					);
					break;
                case 'created_on':
                    $columns[] = array(
                        'dt' => $i++,
                        'select' => 'game_description.created_on',
                        'alias' => 'created_on',
                        'name' => lang('Created At'),
                        'formatter' => function ($d, $row) {
                            return $d ?: '-';
                        },
                    );
                    break;
                case 'updated_at':
                    $columns[] = array(
                        'dt' => $i++,
                        'select' => 'game_description.updated_at',
                        'alias' => 'updated_at',
                        'name' => lang('Last Update'),
                        'formatter' => function ($d, $row) {
                            return $d ?: '-';
                        },
                    );
                    break;
                case 'deleted_at':
                    $columns[] = array(
                        'dt' => $i++,
                        'select' => 'game_description.deleted_at',
                        'alias' => 'deleted_at',
                        'name' => lang('Delete Time'),
                        'formatter' => function ($d, $row) {
                            return $d ?: '-';
                        },
                    );
                    break;
                case 'locked_flag':
                    $columns[] = array(
                        'dt' => $i++,
                        'select' => 'game_description.locked_flag',
                        'alias' => 'locked_flag',
                        'name' => lang('Locked Flag'),
                        'formatter' => function ($d, $row) use ($is_export) {
                            if ($is_export) {
                                return (($d == 1) ? '☑' : '☐') ;
                            }else{
                                return '<i class="' . (($d == 1) ? 'glyphicon glyphicon-check' : 'glyphicon glyphicon-unchecked') . '"></i>';
                            }
                        },
                    );
                    break;
                  case 'demo_link':
                    $columns[] = array(
                        'dt' => $i++,
                        'select' => 'if(game_description.demo_link = "supported", 1, 0)',
                        'alias' => 'demo_link',
                        'name' => lang('Demo Supported'),
                        'formatter' => function ($d, $row) use ($is_export) {
                            if ($is_export) {
                                return (($d == 1) ? '☑' : '☐') ;
                            }else{
                                return '<i class="' . (($d == 1) ? 'glyphicon glyphicon-check' : 'glyphicon glyphicon-unchecked') . '"></i>';
                            }
                        },
                    );
                    break;
            }
        }

      if(!empty($visible_columns) &&  $is_export){
      	$columns = array_filter($columns, function ($data) use ($visible_columns) {
      		if(isset($data['name']) && in_array($data['name'], $visible_columns) || isset($data['default'])){
      			return $data;
      		}
			});
      }

		$table = 'game_description';
		$joins = array(
			'game_type' => 'game_type.id = game_description.game_type_id',
			'external_system' => 'external_system.id = game_description.game_platform_id',
			'game_tag_list' => 'game_tag_list.game_description_id = game_description.id',
			'game_tags' => 'game_tags.id = game_tag_list.tag_id',
			'agency_agent_game_platforms' => 'agency_agent_game_platforms.game_platform_id = external_system.id',
		);

		# START PROCESS SEARCH FORM #################################################################################################################################################
		$where = array();
		$values = array();
		$input = $this->data_tables->extra_search($request);
		if (isset($input['agentName'])) {
			$agent = $this->agency_model->get_agent_by_name($input['agentName']);
			if (!empty($agent['agent_id'])) {
				$agent_id = $agent['agent_id'];
				$where[] = 'agency_agent_game_platforms.agent_id = ?';
				$values[] = $agent_id;
			} else {
				$where[] = 'agency_agent_game_platforms.agent_id = ?';
				$values[] = $input['agentName'];
			}
		}

		if (isset($input['gamePlatform'])) {
			if($input['gamePlatform'] != "N/A"){
				$where[] = "game_description.game_platform_id = ? ";
				$values[] = $input['gamePlatform'];
			}else{
				if (!$this->utils->getConfig('show_non_active_game_api_game_list')) {
					$apiArr=$this->utils->getAllCurrentGameSystemList();
					$where[] = "game_description.game_platform_id in ( ".implode(',', $apiArr)." )";
				}
			}

		}else{
			//only for active api
			$apiArr=$this->utils->getAllCurrentGameSystemList();
			$where[] = "game_description.game_platform_id in ( ".implode(',', $apiArr)." )";

		}

		if(isset($input['gameCode'])){
			$gameCodes = null;
			if (is_array($input['gameCode'])) {
				foreach ($input['gameCode'] as $gameCode) {
					$gameCodes .= "'" . $gameCode ."',";
				}
				$gameCodes = rtrim($gameCodes,",");
			}else{
				if(!strpos($input['gameCode'],',')){
					$gameCodes = "'" . $input['gameCode'] . "'";
				}else{
				$gameCodesTemp = explode(',', $input['gameCode']);
				foreach ($gameCodesTemp as $gameCode) {
					$gameCodes .= "'" . $gameCode ."',";
				}
				$gameCodes = rtrim($gameCodes,",");
				}
			}

			$where[] = 'game_description.game_code in ('.$gameCodes.')';
		}

		if(isset($input['gameTag'])){
			$gameTag = $input['gameTag'];
			
            if (!empty($gameTag) && is_array($gameTag)) {
                $gameTags = '"' . implode('","', $gameTag) . '"';

                $where[] = "game_tags.tag_code IN ({$gameTags})";
            } else {
                $where[] = 'game_tags.tag_code = "'.$gameTag.'"';
            }
		}

		if(isset($input['gameId'])){
			$gameIds = null;
			if(!strpos($input['gameId'],',')){
					$gameIds = "'" . $input['gameId'] . "'";
				}else{
				$gameIdsTemp = explode(',', $input['gameId']);
				foreach ($gameIdsTemp as $gameId) {
					$gameIds .= "'" . $gameId ."',";
				}
				$gameIds = rtrim($gameIds,",");
				}
			$where[] = 'game_description.external_game_id in ('.$gameIds.')';
		}
		if($input['gameFlagShow'] != "All"){
			$where[] = "game_description.flag_show_in_site =".$input['gameFlagShow'];
		}
		if($input['gameStatus'] != "All" ){
			$where[] = "game_description.status =".$input["gameStatus"];
		}
		if(isset($input['gameName'])){
			$where[] = 'game_description.game_name LIKE "%'.$input['gameName'].'%"';
		}
		if(isset($input['gameTypeIdHide'])){
			$where[] = 'game_description.game_type_id LIKE "%'.$input['gameTypeIdHide'].'%"';
		}
        if(isset($input['gameType'])){
            $where[] = 'game_description.game_type_id = ' . $input['gameType'];
        }

		if(isset($input['filters'])){
			if (is_array($input['filters'])) {
				foreach ($input['filters'] as $key => $filter) {
					if($filter == "game_order"){
						$where[] = "game_description.game_order != {$db_false} and game_description.game_order is not null";
					}
					else if($filter == "tag_game_order"){
						$where[] = "game_tag_list.game_order != {$db_false} and game_description.game_order is not null";
					}
					else{
						$filter_arr = explode('-',$filter);
						if (count($filter_arr) > 1) {
							$where[] = 'game_description.'.reset($filter_arr).' = '. $db_false;
						}else{
							$where[] = 'game_description.'.$filter.' = '. $db_true;
						}
					}
				}
			}else{
				$filter_arr = explode('-',$input['filters']);
				if (count($filter_arr)>1) {
					$where[] = 'game_description.'.reset($filter_arr).' = '. $db_false;
				}else{
					if($input['filters'] == 'game_order'){
						$where[] = "game_description.game_order != {$db_false} and game_description.game_order is not null";
					} 
					else if($input['filters'] == "tag_game_order"){
						$where[] = "game_tag_list.game_order != {$db_false} and game_description.game_order is not null";
					}
					else {
						$where[] = 'game_description.'.$input['filters'].' = '. $db_true;
					}
				}
			}
		}

        $groupBy = 'game_description.id';
		$result = $this->data_tables->get_data($request, $columns, $table, $where, $values, $joins, $groupBy);
		return $result;
	}

	/**
	 * detail: get game provider auth list
	 *
	 * @param array $request
	 * @param Boolean $is_export
	 *
	 * @return array
	 */
	public function gameProviderAuthList($request, $is_export = false) {
		$db_false = 0;
		$db_true = 1;

		$this->load->library(array('data_tables'));

		$i = 0;
	//	$request = $this->input->post();

		$where = array();
		$values = array();
		$columns = array();

		$columns[] = array(
			'dt' => $i++,
			'select' => 'game_provider_auth.login_name',
			'alias' => 'login_name',
			'name' => lang('sys.gp1')
		);

		$columns[] = array(
			'dt' => $i++,
			'select' => 'external_system.system_code',
			'alias' => 'system_code',
			'name' => lang('sys.gd7')
		);

		$columns[] = array(
			'dt' => $i++,
			'select' => 'player.username',
			'alias' => 'username',
			'name' => lang('Player Username'),
		);

		$columns[] = array(
			'dt' => $i++,
			'select' => 'game_provider_auth.id',
			'alias' => 'id',
			'name' => lang('sys.gp4'),
			'formatter' => function ($d, $row) use ($is_export) {
				$output = '<a href="javascript:void(0)" title="' . lang('Delete Duplicate Prefix') . '" class="delete_gpa" id="delete_gpa-' . $d . '" ><span style="color:#ff3333" class="glyphicon glyphicon-trash"></span></a>';
				return $output;

			},
		);


		$table = 'game_provider_auth';
		$joins = array(
			'player' => 'player.playerId = game_provider_auth.player_id',
			'external_system' => 'external_system.id = game_provider_auth.game_provider_id',
		);

		# START PROCESS SEARCH FORM #################################################################################################################################################
		$where = array();
		$values = array();
		$input = $this->data_tables->extra_search($request);


		if (isset($input['gamePlatform'])) {
			if($input['gamePlatform'] != "N/A"){
				$where[] = "game_provider_auth.game_provider_id = ? ";
				$values[] = $input['gamePlatform'];
			}else{
				if (!$this->utils->getConfig('show_non_active_game_api_game_list')) {
					$apiArr=$this->utils->getAllCurrentGameSystemList();
					$where[] = "game_provider_auth.game_provider_id in ( ".implode(',', $apiArr)." )";
				}
			}

		}else{
			//only for active api
			$apiArr=$this->utils->getAllCurrentGameSystemList();
			$where[] = "game_provider_auth.game_provider_id in ( ".implode(',', $apiArr)." )";

		}

		if (isset($input['gameUsername'])) {
			if($input['gameUsername'] != '') {
				$where[] = "game_provider_auth.login_name LIKE ? ";
				$values[] = '%' . $input['gameUsername'] . '%';
			}
		}

		$where[] = "game_provider_auth.register = ? ";
		$values[] = '1';

		$result = $this->data_tables->get_data($request, $columns, $table, $where, $values, $joins);
		return $result;
	}


	/**
	 * detail: get game descriptions
	 *
	 * @param array $request
	 * @param Boolean $is_export
	 *
	 * @return array
	 */
	public function gameDescriptionListHistory($request, $is_export = false) {
		$db_false = 0;
		$db_true = 1;

		$this->load->library(array('data_tables'));

		$i = 0;
	//	$request = $this->input->post();

		$where = array();
		$values = array();
		$columns = array();

		$columns[] = array(
			'select' => 'game_description_history.game_description_id',
			'alias' => 'game_description_id',
		);
		$columns[] = array(
			'dt' => $i++,
			'select' => 'game_description_history.id',
			'alias' => 'id',
			'name' => lang('sys.pay.systemid'),
			'formatter' => function ($d, $row) use ($is_export) {
				if ($is_export) {
					return $d;
				}else{
					$output = '<a href="" title="' . lang('sys.gd23') . '" class="viewGameDescriptionHistory" data-toggle="modal" data-target="#gameDescriptionHistory" data-row-id="'. $row['game_description_id'] . '" ><span class="glyphicon glyphicon-list-alt"></span></a>';
					return $output;
				}
			},
		);
		$columns[] = array(
			'dt' => $i++,
			'select' => 'game_description_history.english_name',
			'alias' => 'english_name',
			'name' => lang('lang.english.name'),
			'formatter' => function ($d, $row) {
				return $d ?: '-';
			},
		);
		$columns[] = array(
			'dt' => $i++,
			'select' => 'game_description_history.action',
			'alias' => 'action',
			'name' => lang('Action'),
			'formatter' => function ($d, $row) {
                $actionArray = [
                    'Add' => '<span class="label label-success">Add</span>',
                    'Update' => '<span class="label label-primary">Update</span>',
                    'Batch Update' => '<span class="label label-primary">Batch Update</span>',
                    'Delete' => '<span class="label label-danger">Delete</span>',
                ];
				return $actionArray[$d] ? $actionArray[$d] : '<span class="label label-secondary">Unknown</span>';
			},
		);
		$columns[] = array(
			'dt' => $i++,
			'select' => 'game_type.game_type',
			'alias' => 'game_type',
			'formatter' => 'languageFormatter',
			'name' => lang('sys.gd6')
		);
		$columns[] = array(
			'dt' => $i++,
			'select' => 'external_system.system_code',
			'alias' => 'system_name',
			'name' => lang('sys.gd7')
		);
		$columns[] = array(
			'dt' => $i++,
			'select' => 'game_description_history.game_name',
			'alias' => 'game_name',
			'formatter' => 'languageFormatter',
			'name' => lang('sys.gd8')
		);
		$columns[] = array(
			'dt' => $i++,
			'select' => 'game_description_history.game_code',
			'alias' => 'game_code',
			'name' => lang('sys.gd9')
		);
		$columns[] = array(
			'dt' => $i++,
			'select' => 'game_description_history.rtp',
			'alias' => 'rtp',
			'name' => lang('sys.gd42'),
            'formatter' => function ($d, $row) {
                return $d ?: '-';
            },
		);
		$columns[] = array(
			'dt' => $i++,
			'select' => 'game_description_history.external_game_id',
			'alias' => 'game_code',
			'name' => lang('lang.external.game.id')
		);
		$columns[] = array(
			'dt' => $i++,
			'select' => 'game_description_history.attributes',
			'alias' => 'attributes',
			'name' => lang('Attributes'),
			'formatter' => function ($d, $row) {
				return $d ?: '-';
			},
		);
        $columns[] = array(
            'dt' => $i++,
            'select' => 'game_description_history.progressive',
            'alias' => 'progressive',
            'name' => lang('sys.gd10'),
            'formatter' => function ($d, $row) use ($is_export){
                if ($is_export) {
                    return (($d == 1) ? '☑' : '☐') ;
                }else{
                    return '<i class="' . (($d == 1) ? 'glyphicon glyphicon-check' : 'glyphicon glyphicon-unchecked') . '"></i>';
                }
            },
        );
        $columns[] = array(
            'dt' => $i++,
            'select' => 'game_description_history.mobile_enabled',
            'alias' => 'mobile_enabled',
            'name' => lang('Mobile'),
            'formatter' => function ($d, $row) use ($is_export)  {
                if ($is_export) {
                    return (($d == 1) ? '☑' : '☐') ;
                }else{
                    return '<i class="' . (($d == 1) ? 'glyphicon glyphicon-check' : 'glyphicon glyphicon-unchecked') . '"></i>';
                }
            },
        );
        $columns[] = array(
            'dt' => $i++,
            'select' => 'game_description_history.flash_enabled',
            'alias' => 'flash_enabled',
            'name' => lang('Flash'),
            'formatter' => function ($d, $row) use ($is_export)  {
                if ($is_export) {
                    return (($d == 1) ? '☑' : '☐') ;
                }else{
                    return '<i class="' . (($d == 1) ? 'glyphicon glyphicon-check' : 'glyphicon glyphicon-unchecked') . '"></i>';
                }
            },
        );
        $columns[] = array(
                'dt' => $i++,
                'select' => 'game_description_history.html_five_enabled',
                'alias' => 'html_five_enabled',
                'name' => lang('HTML5'),
                'formatter' => function ($d, $row) use ($is_export)  {
                    if ($is_export) {
                        return (($d == 1) ? '☑' : '☐') ;
                    }else{
                        return '<i class="' . (($d == 1) ? 'glyphicon glyphicon-check' : 'glyphicon glyphicon-unchecked') . '"></i>';
                    }
                },
            );
        $columns[] = array(
                'dt' => $i++,
                'select' => 'game_description_history.enabled_on_ios',
                'alias' => 'enabled_on_ios',
                'name' => lang('IOS'),
                'formatter' => function ($d, $row) use ($is_export)  {
                    if ($is_export) {
                        return (($d == 1) ? '☑' : '☐') ;
                    }else{
                        return '<i class="' . (($d == 1) ? 'glyphicon glyphicon-check' : 'glyphicon glyphicon-unchecked') . '"></i>';
                    }
                },
            );
        $columns[] = array(
                'dt' => $i++,
                'select' => 'game_description_history.enabled_on_android',
                'alias' => 'enabled_on_android',
                'name' => lang('Android'),
                'formatter' => function ($d, $row) use ($is_export)  {
                    if ($is_export) {
                        return (($d == 1) ? '☑' : '☐') ;
                    }else{
                        return '<i class="' . (($d == 1) ? 'glyphicon glyphicon-check' : 'glyphicon glyphicon-unchecked') . '"></i>';
                    }
                },
            );
        $columns[] = array(
                'dt' => $i++,
                'select' => 'game_description_history.dlc_enabled',
                'alias' => 'dlc_enabled',
                'name' => lang('Android'),
                'formatter' => function ($d, $row) use ($is_export)  {
                    if ($is_export) {
                        return (($d == 1) ? '☑' : '☐') ;
                    }else{
                        return '<i class="' . (($d == 1) ? 'glyphicon glyphicon-check' : 'glyphicon glyphicon-unchecked') . '"></i>';
                    }
                },
            );
        $columns[] = array(
                'dt' => $i++,
                'select' => 'game_description_history.offline_enabled',
                'alias' => 'offline_enabled',
                'name' => lang('Android'),
                'formatter' => function ($d, $row) use ($is_export)  {
                    if ($is_export) {
                        return (($d == 1) ? '☑' : '☐') ;
                    }else{
                        return '<i class="' . (($d == 1) ? 'glyphicon glyphicon-check' : 'glyphicon glyphicon-unchecked') . '"></i>';
                    }
                },
            );
        $columns[] = array(
                'dt' => $i++,
                'select' => 'game_description_history.flag_new_game',
                'alias' => 'flag_new_game',
                'name' => lang('Android'),
                'formatter' => function ($d, $row) use ($is_export)  {
                    if ($is_export) {
                        return (($d == 1) ? '☑' : '☐') ;
                    }else{
                        return '<i class="' . (($d == 1) ? 'glyphicon glyphicon-check' : 'glyphicon glyphicon-unchecked') . '"></i>';
                    }
                },
            );
		$columns[] = array(
			'dt' => $i++,
			'select' => 'game_description_history.note',
			'alias' => 'note',
			'name' => lang('sys.gd11'),
			'formatter' => function ($d, $row) {
				return $d ?: '-';
			},
		);
		$columns[] = array(
				'dt' => $i++,
				'select' => 'game_description_history.no_cash_back',
				'alias' => 'no_cash_back',
				'name' => lang('sys.gd18'),
				'formatter' => function ($d, $row) use ($is_export)  {
					if ($is_export) {
						return (($d == 1) ? '☑' : '☐') ;
					}else{
						return '<i class="' . (($d == 1) ? 'glyphicon glyphicon-check' : 'glyphicon glyphicon-unchecked') . '"></i>';
					}
				},
			);
		$columns[] = array(
			'dt' => $i++,
			'select' => 'game_description_history.void_bet',
			'alias' => 'void_bet',
			'name' => lang('sys.gd19'),
			'formatter' => function ($d, $row) use ($is_export) {
				if ($is_export) {
					return (($d == 1) ? '☑' : '☐') ;
				}else{
					return '<i class="' . (($d == 1) ? 'glyphicon glyphicon-check' : 'glyphicon glyphicon-unchecked') . '"></i>';
				}
			},
		);
		$columns[] = array(
			'dt' => $i++,
			'select' => 'game_description_history.status',
			'alias' => 'status',
			'name' => lang('sys.gd16'),
			'formatter' => function ($d, $row) use ($is_export) {
				if ($is_export) {
					return (($d == 1) ? '☑' : '☐') ;
				}else{
					return '<i class="' . (($d == 1) ? 'glyphicon glyphicon-check' : 'glyphicon glyphicon-unchecked') . '"></i>';
				}
			},
		);
		$columns[] = array(
			'dt' => $i++,
			'select' => 'game_description_history.flag_show_in_site',
			'alias' => 'flag_show_in_site',
			'name' => lang('sys.gd17'),
			'formatter' => function ($d, $row) use ($is_export) {
				if ($is_export) {
					return (($d == 1) ? '☑' : '☐') ;
				}else{
					return '<i class="' . (($d == 1) ? 'glyphicon glyphicon-check' : 'glyphicon glyphicon-unchecked') . '"></i>';
				}
			},
		);
		$columns[] = array(
			'dt' => $i++,
			'select' => 'game_description_history.game_order',
			'alias' => 'game_order',
			'name' => lang('sys.gd20'),
			'formatter' => function ($d, $row) {
				return $d ?: '-';
			},
		);
		$columns[] = array(
			'dt' => $i++,
			'select' => 'game_description_history.created_on',
			'alias' => 'created_on',
			'name' => lang('Created At'),
			'formatter' => function ($d, $row) {
				return $d ?: '-';
			},
		);
		$columns[] = array(
			'dt' => $i++,
			'select' => 'game_description_history.updated_at',
			'alias' => 'updated_at',
			'name' => lang('Last Update'),
			'formatter' => function ($d, $row) {
				return $d ?: '-';
			},
		);
		$columns[] = array(
			'dt' => $i++,
			'select' => 'game_description_history.deleted_at',
			'alias' => 'deleted_at',
			'name' => lang('Deleted At'),
			'formatter' => function ($d, $row) {
				return $d ?: '-';
			},
		);
        $columns[] = array(
			'dt' => $i++,
			'select' => 'adminusers.username',
			'alias' => 'user_id',
			'name' => lang('sys.updatedby'),
			'formatter' => function ($d, $row) {
				return $d ?: '-';
			},
		);
		$columns[] = array(
			'dt' => $i++,
			'select' => 'game_description_history.user_ip_address',
			'alias' => 'user_ip_address',
			'name' => lang('sys.ip_address'),
			'formatter' => function ($d, $row) {
				return $d ?: '-';
			},
		);
		$columns[] = array(
			'dt' => $i++,
			'select' => 'if(game_description_history.demo_link = "supported", 1, 0)',
			'alias' => 'demo_link',
			'name' => lang('Demo Supported'),
			'formatter' => function ($d, $row) use ($is_export) {
				if ($is_export) {
					return (($d == 1) ? '☑' : '☐') ;
				}else{
					return '<i class="' . (($d == 1) ? 'glyphicon glyphicon-check' : 'glyphicon glyphicon-unchecked') . '"></i>';
				}
			},
		);

		$table = 'game_description_history';
		$joins = array(
			'game_type' => 'game_type.id = game_description_history.game_type_id',
			'external_system' => 'external_system.id = game_description_history.game_platform_id',
			'adminusers' => 'adminusers.userId = game_description_history.user_id',
		);

		# START PROCESS SEARCH FORM #################################################################################################################################################
		$where = array();
		$values = array();
		$input = $this->data_tables->extra_search($request);

		if (isset($input['gamePlatform'])) {
			if($input['gamePlatform'] != "N/A"){
				$where[] = "game_description_history.game_platform_id = ? ";
				$values[] = $input['gamePlatform'];
			}else{
				if (!$this->utils->getConfig('show_non_active_game_api_game_list')) {
					$apiArr=$this->utils->getAllCurrentGameSystemList();
					$where[] = "game_description_history.game_platform_id in ( ".implode(',', $apiArr)." )";
				}
			}

		}else{
			//only for active api
			$apiArr=$this->utils->getAllCurrentGameSystemList();
			$where[] = "game_description_history.game_platform_id in ( ".implode(',', $apiArr)." )";

		}

		if(isset($input['gameCode'])){
			$gameCodes = null;
			if (is_array($input['gameCode'])) {
				foreach ($input['gameCode'] as $gameCode) {
					$gameCodes .= "'" . $gameCode ."',";
				}
				$gameCodes = rtrim($gameCodes,",");
			}else{
				$gameCodes = "'" . $input['gameCode'] . "'";
			}

			$where[] = 'game_description_history.game_code in ('.$gameCodes.')';
		}

		if(isset($input['gameName'])){
			$where[] = 'game_description_history.game_name LIKE "%'.$input['gameName'].'%"';
		}
		if(isset($input['gameTypeIdHide'])){
			$where[] = 'game_description_history.game_type_id LIKE "%'.$input['gameTypeIdHide'].'%"';
		}

		if(isset($input['filters'])){
			if (is_array($input['filters'])) {
				foreach ($input['filters'] as $key => $filter) {
					$filter_arr = explode('-',$filter);
					if (count($filter_arr) > 1) {
						$where[] = 'game_description_history.'.reset($filter_arr).' = '. $db_false;
					}else{
						$where[] = 'game_description_history.'.$filter.' = '. $db_true;
					}
				}
			}else{
				$filter_arr = explode('-',$input['filters']);
				if (count($filter_arr)>1) {
					$where[] = 'game_description_history.'.reset($filter_arr).' = '. $db_false;
				}else{
					$where[] = 'game_description_history.'.$input['filters'].' = '. $db_true;
				}
			}
		}

		if(isset($input['action'])){
			$where[] = 'game_description_history.action ="'.$input['action'].'"';
		}

		$result = $this->data_tables->get_data($request, $columns, $table, $where, $values, $joins);
		return $result;
	}

	public function gamesHistory($request, $player_id = null, $is_export = false, $not_datatable = '', $csv_filename=null, $from_aff = false ){

		$this->load->model(array('game_logs', 'player', 'game_provider_auth'));
		$readOnlyDB = $this->getReadOnlyDB();

		$this->load->library('data_tables', array("DB" => $readOnlyDB));

		$input = $this->data_tables->extra_search($request);
		$game_logs_date_column = !empty($input['by_date_type']) ? $this->game_logs->getGameLogsDateColumn($input['by_date_type']) : 'game_logs.end_at';

		# START DEFINE COLUMNS #################################################################################################################################################
		$show_bet_detail_on_game_logs = $this->utils->isEnabledFeature('show_bet_detail_on_game_logs');
        $mobile_winlost_column = $this->utils->isEnabledFeature('mobile_winlost_column');
		$i = 0;

		if ($is_export) {
			$columns = [];
		}
		else {
			$columns = [
				/*[
	                'dt' => $i++,
					'alias' => 'id',
					'select' => 'game_logs.id',
				],*/
			];
		}

		$columns1 = array(
			array(
				//'dt' => $i++,
				'alias' => 'id',
				'select' => 'game_logs.id',
			),

			array(
				'alias' => 'is_parlay',
				'select' => 'game_logs.is_parlay',
			),
			array(
				'alias' => 'game_type',
				'select' => 'game_type.game_type',
			),
			array(
				'alias' => 'game_code',
				'select' => 'game_description.game_code',
			),
			array(
				'alias' => 'playerId',
				'select' => 'player.playerId',
			),
			array(
				'alias' => 'player_levelName',
				'select' => 'vipsettingcashbackrule.vipLevelName'
			),
			array(
				'alias' => 'player_groupName',
				'select' => 'vipsetting.groupName'
			),
			array(
				'alias' => 'game_platform_id',
				'select' => 'game_logs.game_platform_id',
			),
            array(
                'dt' => $i++,
                'alias' => 'bet_at',
                'select' => 'game_logs.bet_at',
                'name' => lang('player.ug06'),
                'formatter' => function ($d, $row) use ($is_export) {
					$d = ($row['flag'] != Game_logs::FLAG_GAME) ? lang('N/A') : $d;
					if( ! $is_export ){
						return sprintf('<span data-id="%s">%s</span>', $row['id'], $d);
					}else{
						return $d;
					}
                    //return $d;
                },
            ),
			array(
				'dt' => $i++,
				'alias' => 'end_at',
				'select' => $game_logs_date_column,
				'name' => lang('Transaction Date / Payout Date'),
				'formatter' => 'dateTimeFormatter',
			),
            array(
                'dt' => $i++,
                'alias' => 'updated_time',
                'select' => 'game_logs.updated_at',
                'name' => lang('Updated Date'),
                'formatter' => 'dateTimeFormatter',
            ),
			array(
				'dt' => $i++,
				'alias' => 'player_username',
                'select' => $this->utils->isEnabledFeature('display_aff_beside_playername_gamelogs') ? '( CASE WHEN affiliates.username IS NULL THEN player.username ELSE CONCAT(player.username, \' (\', affiliates.username,  \')\' ) END )' : 'player.username',
				'name' => lang('Player Username'),
				'formatter' => function ($d, $row) use ($is_export) {

					if( ! $is_export ){

						return sprintf('<a target="_blank" href="/player_management/userInformation/%s">%s</a>', $row['playerId'], $d);

					}else{

						return $d;

					}

				},
			),
            array(
                'dt' => $this->utils->isEnabledFeature('aff_show_real_name_on_reports') ? $i++ : NULL,
                'alias' => 'realname',
                'select' => "CONCAT_WS(' ', playerdetails.firstName,playerdetails.lastName)",
                'formatter' => function ($d, $row) {
                    return trim($d);
                },
                'name' => lang('Real Name'),
            ),
			array(
				'dt' => !$this->utils->isEnabledFeature('close_aff_and_agent') ? $i++ : NULL,
				'alias' => 'affiliate_username',
				'select' => 'affiliates.username',
				'name' => lang('Affiliate Username'),
				'formatter' => function ($d, $row) {
					if ($row['affiliate_username'] != '') {
						return sprintf('%s', $row['affiliate_username'], $d);
					} else {
						return 'NA';
					}
				},
			),
			array(
				'dt' => $this->utils->isEnabledFeature('show_agent_name_on_game_logs') ? $i++ : NULL,
				'alias' => 'agent_username',
				'select' => 'agency_agents.agent_name',
				'name' => lang('Agent Username'),
				'formatter' => function ($d, $row) {
					if (!empty($d)) {
						return $d;
					} else {
						return lang('lang.norecyet');
					}
				},
			),
			array(
				'dt' => $i++,
				'alias' => 'player_level',
				'select' => 'player.levelId',
				'formatter' => function($d, $row){
					if($d != 'N/A') {
						return lang($row['player_groupName']).' - '.lang($row['player_levelName']);
					}
		  			return $d;

				},
				'name' => lang('Player Level'),

			),
			array(
				'dt' => $i++,
				'alias' => 'game',
				'select' => 'external_system.system_code',
				'name' => lang('cms.gameprovider'),
				'formatter' => 'defaultFormatter',
			),
			array(
				'dt' => $i++,
				'alias' => 'game_type_lang',
				'select' => 'game_type.game_type_lang',
				'name' => lang('cms.gametype'),
				'formatter' => function ($d, $row) {
					if ($row['flag'] == Game_logs::FLAG_TRANSACTION) {
						return lang('N/A');
					}

					return $this->data_tables->languageFormatter($d);
				},
			),
			array(
				'dt' => $i++,
				'alias' => 'game_name',
				'select' => 'game_description.game_name',
				'name' => lang('cms.gamename'),
				'formatter' => function ($d, $row) {
					if ($row['flag'] == Game_logs::FLAG_TRANSACTION) {
						return lang('N/A');
					}

					return $this->data_tables->languageFormatter($d);
				},
			),
			array(
				'dt' => $i++,
				'alias' => 'real_bet_amount',
				'select' => 'game_logs.trans_amount',
				'name' => lang('Real Bet'),
				'formatter' => function ($d, $row) {
					//only for game type
					if ($row['flag'] == Game_logs::FLAG_TRANSACTION) {
						return lang('N/A');
					} else {
						if ($d == 0) {
							return lang('N/A');
						// 	$d = $row['bet_amount'];
						}

						return $this->utils->formatCurrencyWithSpecificApisDecimal($d, $row['game_platform_id']);
					}
				},
				// 'formatter' => 'currencyFormatter',
			),
			array(
				'dt' => $i++,
				'alias' => 'bet_amount',
				'select' => 'game_logs.bet_amount',
				'name' => lang('Valid Bet'),
				'formatter' => function ($d, $row) {
					if ($row['flag'] == Game_logs::FLAG_TRANSACTION) {
						return lang('N/A');
					} else {
						if($row['game_platform_id'] == BBIN_API){
							$decimal_count = strcspn(strrev($d), '.');
							$precision = 3;
							if($decimal_count >= $precision){
								return $this->utils->formatCurrencyNoSymwithDecimal($d, $precision);
							}
						}
						return $this->utils->formatCurrencyWithSpecificApisDecimal($d, $row['game_platform_id']);
					}
				},
				// 'formatter' => 'currencyFormatter',
			),
			array(
				'dt' => $i++,
				'alias' => 'result_amount',
				'select' => 'game_logs.result_amount',
				'name' => lang('mark.resultAmount'),
				'formatter' => function ($d, $row) use ($is_export) {
					if ($row['flag'] == Game_logs::FLAG_TRANSACTION) {
						return lang('N/A');
					} else {
						if(!$is_export){
							if($d <= 0){
							//lose->green
								# make text dark green if free spin for it to be visible
								if (empty($row['real_bet_amount']) && empty($row['bet_amount'])) {
									return sprintf('<span style="font-weight:bold;color:#008000">%s</span>', $this->utils->formatCurrencyWithSpecificApisDecimal($d, $row['game_platform_id']));
								}

								return sprintf('<span style="font-weight:bold;" class ="text-success">%s</span>', $this->utils->formatCurrencyWithSpecificApisDecimal($d, $row['game_platform_id']));
							}else{
								// if free spin, make text color dark red for it to be readable
								if (empty($row['real_bet_amount']) && empty($row['bet_amount'])) {
									return sprintf('<span style="font-weight:bold;color:#8B0000">%s</span>', $this->utils->formatCurrencyWithSpecificApisDecimal($d, $row['game_platform_id']));
								} else {
									// win->red
									return sprintf('<span style="font-weight:bold;" class ="text-danger">%s</span>', $this->utils->formatCurrencyWithSpecificApisDecimal($d, $row['game_platform_id']));
								}
							}
						}else{
							return $this->utils->formatCurrencyWithSpecificApisDecimal($d, $row['game_platform_id']);
						}
					}
				},
				// 'formatter' => 'currencyFormatter',
			),
			array(
				'dt' => $i++,
				'alias' => 'bet_plus_result_amount',
				'select' => 'game_logs.bet_amount + game_logs.result_amount',
				'name' => lang('lang.bet.plus.result'),
				// 'formatter' => 'currencyFormatter',
				'formatter' => function ($d, $row) use ($is_export) {
					if ($row['flag'] == Game_logs::FLAG_TRANSACTION) {
						return lang('N/A');
					} else {
						if(!$is_export){
							if ($d <= 0) {
								# make text dark green if free spin for it to be visible
								if (empty($row['real_bet_amount']) && empty($row['bet_amount'])) {
									return sprintf('<span style="font-weight:bold;color:#008000">%s</span>', $this->utils->formatCurrencyWithSpecificApisDecimal($d, $row['game_platform_id']));
								}
							}
						}
						if($row['game_platform_id'] == BBIN_API){
							$decimal_count = strcspn(strrev($d), '.');
							$precision = 3;
							if($decimal_count >= $precision){
								return $this->utils->formatCurrencyNoSymwithDecimal($d, $precision);
							}
						}
						return $this->utils->formatCurrencyWithSpecificApisDecimal($d, $row['game_platform_id']);
					}
				},
			),
			array(
				'dt' => $i++,
				'alias' => 'win_amount',
				'select' => 'game_logs.win_amount',
				'name' => lang('Win Amount'),
				// 'formatter' => 'currencyFormatter',
				'formatter' => function ($d, $row) {
					if ($row['flag'] == Game_logs::FLAG_TRANSACTION) {
						return lang('N/A');
					} else {
						return $this->utils->formatCurrencyWithSpecificApisDecimal($d, $row['game_platform_id']);
					}
				},
			),
			array(
				'dt' => $i++,
				'alias' => 'loss_amount',
				'select' => 'game_logs.loss_amount',
				'name' => lang('Loss Amount'),
				// 'formatter' => 'currencyFormatter',
				'formatter' => function ($d, $row) {
					if ($row['flag'] == Game_logs::FLAG_TRANSACTION) {
						return lang('N/A');
					} else {
						return $this->utils->formatCurrencyWithSpecificApisDecimal($d, $row['game_platform_id']);
					}
				},
			),
			array(
				'dt' => $i++,
				'alias' => 'after_balance',
				'select' => 'game_logs.after_balance',
				'name' => lang('mark.afterBalance'),
				'formatter' => function($d, $row) use ($is_export){
					if ( $is_export ) {
						return $this->utils->formatCurrencyWithSpecificApisDecimal($d, $row['game_platform_id']);
					} else {
						return $d == 0 ? '<span class="text-muted">' . $this->utils->formatCurrencyWithSpecificApisDecimal($d, $row['game_platform_id']) . '</span>' : '<strong>' . $this->utils->formatCurrencyWithSpecificApisDecimal($d, $row['game_platform_id']) . '</strong>';
					}
				},
			),
			array(
				'dt' => $i++,
				'alias' => 'trans_amount',
				'select' => 'game_logs.trans_amount',
				'name' => lang('pay.transamount'),
				// 'formatter' => 'currencyFormatter',
				'formatter' => function ($d, $row) {
					//only for game type
					if ($row['flag'] != Game_logs::FLAG_TRANSACTION) {
						return lang('N/A');
					} else {
						return $this->utils->formatCurrencyNoSym($d);
					}
				},
			),
            array(
                'dt' => $i++,
                'alias' => 'roundno',
                'select' => 'game_logs.table',
                'name' => lang('Round No'),
                'formatter' => function ($d, $row) use ($show_bet_detail_on_game_logs, $is_export) {

                    if( ! $is_export ){

                    	if($this->utils->isEnabledFeature('switch_ag_round_and_notes')){
                    		$platform_id = (int) $row['game_platform_id'];
	                    	if($platform_id == AGIN_API && $row['flag'] == 1 && $row['game_type']=='AGIN'){
	                    		return $row['note'];
	                    	}
                    	}
                        $str = '<p>' . $d . '</p>';
                        if ($show_bet_detail_on_game_logs) {
                            $show_bet_detail_on_game_logs = $str . '<ul class="list-inline">' .
                            '<li><a href="javascript:void(0)" onclick="betDetail(\'' . $d . '\')">' . lang('Bet Detail') . '</a></li>' .
                            '<li><a href="javascript:void(0)" onclick="betResult(\'' . $d . '\')">' . lang('Bet Result') . '</a></li>' .
                                '</ul>';
                        }
                        return $str;

                    }else{
                    	if(is_numeric($d)){
                    		if(strlen($d) >= 16){
                    			return '"'.$d.'"';
                    		}
                    	}
                    	return $d;
                    }

                },
            ),
			array(
				'dt' => $i++,
				'alias' => 'external_uniqueid',
				'select' => 'game_logs.external_uniqueid',
				'name' => lang('External Unique ID'),
				'formatter' => function ($d, $row) use ($is_export) {
					if( ! $is_export ){
						return '<div data-is_parlay="'. $row['is_parlay']. '">'.$d. '</div>';
					}else{
						return $d;
					}
				},
			),
            array(
                'alias' => 'game_type',
                'select' => 'game_logs.game_type',
                'name' => lang('game_type'),
            ),
            array(
                'dt' => $i++,
                'alias' => 'note',
                'select' => 'game_logs.note',
                'name' => lang('Note'),
                'formatter' => function ($d, $row) {
                    if(!empty($d)){
	            		if($row['flag'] == Game_logs::FLAG_GAME && $row['game_platform_id'] == JUMB_GAMING_API) {
								$bet_details = json_decode($d, true);
								if (!isset($bet_details['bet_details'])) {
									return empty($d) ? "N/A" : $d;
								}
								$bet_details = $bet_details['bet_details'];
								$bet_details = explode(",", $bet_details);
								$betDetailLink = '';
								foreach($bet_details as $key => $value) {
								    $betDetailLink .=  $value . "<br>";
								}
		            			return  "<div style='width:200px;'>" . $betDetailLink . "</div>";
	            		}
                    	if($this->utils->isEnabledFeature('switch_ag_round_and_notes')){
                    		$platform_id = (int) $row['game_platform_id'];
	                    	if($platform_id == AGIN_API && $row['flag'] == 1 && $row['game_type']=='AGIN'){
	                    		return $row['roundno'];
	                    	}
                    	}
                        return $d;
                    }else{
                        return "N/A";
                    }
                },
            ),
            array(
                'dt' => $i++,
                'alias' => 'betDetails',
                'select' => 'game_logs.bet_details',
                'name' => lang('Bet Detail'),
                'formatter' => function ($d, $row) use ($is_export) {
                	$platform_id = (int) $row['game_platform_id'];
                	$external_uniqueid = $row['external_uniqueid'];
                	$player_username = $row['player_username'];
	            	$bet_type =  $row['bet_type'];
	            	$bet_at_date = $row['bet_at'];
                	$end_at_date = $row['end_at'];
                	$roundno = $row['roundno'];
					$status = $row['status'];
                    $bet_details = !empty($d) ? json_decode($d, true) : null;

					$api = $this->utils->loadExternalSystemLibObject($platform_id);
					$use_bet_detail_ui =  $api->use_bet_detail_ui;

                    $flow_gaming_apis = [
                        FLOW_GAMING_SEAMLESS_API,
                        FLOW_GAMING_QUICKSPIN_SEAMLESS_API,

                        // T1
                        T1_FLOW_GAMING_SEAMLESS_API,
                        T1_FLOW_GAMING_QUICKSPIN_SEAMLESS_API,
                    ];
					
					if($row['flag'] == Game_logs::FLAG_GAME && $use_bet_detail_ui){
                        // for flow gaming apis
                        if (in_array($platform_id, $flow_gaming_apis)) {
                            if (isset($bet_details['bet_id'])) {
                                $external_uniqueid = $bet_details['bet_id'];
                            }
                        }

						$result = $api->queryBetDetailLink($player_username, $external_uniqueid);

						$url = '';

						if((isset($result['success']) && $result['success'] = true) && (isset($result['url']) )){
							$url = $result['url'];
						}
						if(!empty($url)){
							return '<a href="'.$url.'" target="_blank" class="btn btn-info">'.lang('View Detail').'</a>';
						}
						return '';
					}

                	if($row['flag'] == Game_logs::FLAG_GAME && $platform_id == BETBY_SEAMLESS_GAME_API) {
                		if($is_export){
                			return $d;
                		}
							if($this->utils->isValidJson($d)){
             				$encoded_string = urlencode( base64_encode($d));
             				$json_print_url = '/echoinfo/pretty_print_json/' . $encoded_string;
								return '<a href="'.$json_print_url.'" target="_blank" class="btn btn-link btn-xs">'.lang('View Json').'</a>';
             			}
					}

                    if ($row['flag'] == Game_logs::FLAG_GAME && in_array($platform_id, $flow_gaming_apis)) {
                        $transaction_id = null;

                        if (isset($bet_details['bet_id'])) {
                            $transaction_id = $bet_details['bet_id'];
                        }

                        $betDetailLink = $api->queryBetDetailLink($player_username, $transaction_id);

                        if (isset($betDetailLink['url']) && !empty($betDetailLink['url'])) {
                            return '<a href="'.$betDetailLink['url'].'" target="_blank" class="btn btn-info">'.lang('Bet Detail').'</a>';
                        }

                        return '';
                    }

                	if ($row['flag'] == Game_logs::FLAG_GAME && in_array($platform_id, [CALETA_SEAMLESS_API, T1_CALETA_SEAMLESS_API])) {
	            			$api = $this->utils->loadExternalSystemLibObject($platform_id);
	            			/* $transaction = substr($external_uniqueid,4); */

                            if ($platform_id == T1_CALETA_SEAMLESS_API) {
                                $exploded_external_uniqueid = explode('-', $external_uniqueid);
                                unset($exploded_external_uniqueid[0]);
                                $external_uniqueid = $this->utils->mergeArrayValues($exploded_external_uniqueid);
                            }

	            			$betDetailLink = $api->queryBetDetailLink($player_username, $external_uniqueid, $roundno);
							if(isset($betDetailLink['url'])&& !empty($betDetailLink['url'])){
								return '<a href="'.$betDetailLink['url'].'" target="_blank" class="btn btn-info">'.lang('Bet Detail').'</a>';
							}
							return '';
	            		}
	            	if ($row['flag'] == Game_logs::FLAG_GAME && ($platform_id == WE_SEAMLESS_GAME_API || $platform_id == T1_WE_SEAMLESS_GAME_API)) {
            			$api = $this->utils->loadExternalSystemLibObject($platform_id);

                        if ($platform_id == T1_WE_SEAMLESS_GAME_API) {
                            $exploded_external_uniqueid = explode('-', $external_uniqueid);
                            $external_uniqueid = !empty($exploded_external_uniqueid[1]) ? $exploded_external_uniqueid[1] : $external_uniqueid;
                        }

            			// $betDetailLink = $api->queryBetDetailLink(null, $roundno, $this->language_function->getCurrentLanguage());
            			// return '<a href="'.$betDetailLink['url'].'" target="_blank" class="btn btn-info">'.lang('Bet Detail').'</a>';
            			return '<a href="'.site_url('async/get_bet_detail_link_of_game_api/' . $row['game_platform_id'] . '/' . $player_username .'/'.$external_uniqueid .'/'. $this->language_function->getCurrentLanguage()).'" target="_blank" class="btn btn-info">'.lang('Bet Detail').'</a>';
            		}

					
					if ($row['flag'] == Game_logs::FLAG_GAME && (in_array($platform_id, [
						PGSOFT_SEAMLESS_API, T1_PGSOFT_SEAMLESS_API,
						PGSOFT2_SEAMLESS_API, T1_PGSOFT2_SEAMLESS_API,
						PGSOFT3_SEAMLESS_API, T1_PGSOFT3_SEAMLESS_API,
						IDN_PGSOFT_SEAMLESS_API, T1_IDN_PGSOFT_SEAMLESS_API,
					]))) {
						$api 						= $this->utils->loadExternalSystemLibObject($platform_id);

						if($platform_id == T1_PGSOFT_SEAMLESS_API){
							$external_uniqueid = str_replace(PGSOFT_SEAMLESS_API.'-','',$external_uniqueid);
						}elseif($platform_id == T1_PGSOFT2_SEAMLESS_API){
							$external_uniqueid = str_replace(PGSOFT2_SEAMLESS_API.'-','',$external_uniqueid);
						}elseif($platform_id == T1_PGSOFT3_SEAMLESS_API){
							$external_uniqueid = str_replace(PGSOFT3_SEAMLESS_API.'-','',$external_uniqueid);
						}elseif($platform_id == T1_IDN_PGSOFT_SEAMLESS_API){
							$external_uniqueid = str_replace(IDN_PGSOFT_SEAMLESS_API.'-','',$external_uniqueid);
						}

						$this->CI->utils->debug_log('get_bet_detail_link_of_game_api-pgsoft2', $external_uniqueid);
						$bet_url = '/async/get_bet_detail_link_of_game_api/' . $platform_id . '/' . $player_username . '/' . $external_uniqueid;
						return '<a href="'.$bet_url.'" target="_blank" class="btn btn-info" style="visibility:"visible";">'.lang('Bet Detail').'</a>';
					}

	            	$ntsexyArr = [
	            				  NTTECH_V2_API,
	            				  NTTECH_V2_IDR_B1_API,
	            				  NTTECH_V2_CNY_B1_API,
	            				  NTTECH_V2_THB_B1_API,
	            				  NTTECH_V2_USD_B1_API,
	            				  NTTECH_V2_VND_B1_API,
                                  NTTECH_V2_MYR_B1_API,
								  NTTECH_V2_INR_B1_API,
                                  NTTECH_V3_API,
	            				 ];
	            	if ($row['flag'] == Game_logs::FLAG_GAME && $platform_id == BG_GAME_API) {
	            		$language = $this->language_function->getCurrentLanguage();
	            		$extra = $row['game_code']."_".$language;
            			return  '<a href="'.site_url('async/get_bet_detail_link_of_game_api/' . $row['game_platform_id'] . '/' . $player_username .'/'.$row['roundno'] .'/'. $extra).'" target="_blank" class="btn btn-info">'.lang('Bet Detail').'</a>';
            		}

                	## FOR NTTECH_V2 APIS only since im not sure about NTTECH V1 bet details
            		if ($row['flag'] == Game_logs::FLAG_GAME && in_array($platform_id,$ntsexyArr)) {
            			$d = stripslashes($d);
            			$bet_details = $d;
            			// If there's a double quote on bet_details string
						if (substr($d, 0, 1) === '"' && substr($d, -1) === '"') {
							$bet_details = substr($d, 1, -1);
						}

						if($is_export){
							return $bet_details;
						}
						$bet_details = json_decode($bet_details, true);

                        $betDetailLink = @$this->parseArrayBetDetails($bet_details);

            			/*$betDetailLink = "";
            			foreach ($bet_details as $key => $value) {
                            $other_str = "";

                            if(!is_array($key)){
                                if($key == 'result') {
                                    $value = implode("<br>", $value);
                                }
                                if (preg_match('/\blabel\b/',$value)){
                                    $betDetailLink .=  "<label>" . $value . "</label>" . "<br>";
                                } else {
                                    if($key == "bet"){
                                        $highlighted_str = substr($value, 0, strrpos($value, " "));
                                        $other_str = str_replace($highlighted_str,"",$value);
                                        $value = $highlighted_str;
    
                                    }
                                    $betDetailLink .=  "<label class='".$key."'>" . $key . " : " . $value . "</label>" . $other_str . "<br>";
                                }
                            }
            			}*/
            			return  "<div style='width:200px;'>" . $betDetailLink . "</div>";
            		}

            		$ion_seamless_apis = [
            			IONGAMING_SEAMLESS_IDR1_GAME_API,
						IONGAMING_SEAMLESS_IDR2_GAME_API,
						IONGAMING_SEAMLESS_IDR3_GAME_API,
						IONGAMING_SEAMLESS_IDR4_GAME_API,
						IONGAMING_SEAMLESS_IDR5_GAME_API,
						IONGAMING_SEAMLESS_IDR6_GAME_API,
						IONGAMING_SEAMLESS_IDR7_GAME_API,
						IONGAMING_SEAMLESS_CNY1_GAME_API,
						IONGAMING_SEAMLESS_CNY2_GAME_API,
						IONGAMING_SEAMLESS_CNY3_GAME_API,
						IONGAMING_SEAMLESS_CNY4_GAME_API,
						IONGAMING_SEAMLESS_CNY5_GAME_API,
						IONGAMING_SEAMLESS_CNY6_GAME_API,
						IONGAMING_SEAMLESS_CNY7_GAME_API,
						IONGAMING_SEAMLESS_THB1_GAME_API,
						IONGAMING_SEAMLESS_THB2_GAME_API,
						IONGAMING_SEAMLESS_THB3_GAME_API,
						IONGAMING_SEAMLESS_THB4_GAME_API,
						IONGAMING_SEAMLESS_THB5_GAME_API,
						IONGAMING_SEAMLESS_THB6_GAME_API,
						IONGAMING_SEAMLESS_THB7_GAME_API,
						IONGAMING_SEAMLESS_MYR1_GAME_API,
						IONGAMING_SEAMLESS_MYR2_GAME_API,
						IONGAMING_SEAMLESS_MYR3_GAME_API,
						IONGAMING_SEAMLESS_MYR4_GAME_API,
						IONGAMING_SEAMLESS_MYR5_GAME_API,
						IONGAMING_SEAMLESS_MYR6_GAME_API,
						IONGAMING_SEAMLESS_MYR7_GAME_API
            		];
            		## FOR ION GAMING SEAMLESS BET DETAILS
            		if ($row['flag'] == Game_logs::FLAG_GAME && in_array($platform_id,$ion_seamless_apis)) {
							$bet_details = json_decode($d, true);
                			$betDetailLink = "";
                			foreach ($bet_details as $key => $value) {
                				$betDetailLink .=  "<label class='".$key."'>" . $key ."</label>" . ": " . $value . "<br>";
                			}

                			return  "<div style='width:200px;'>" . $betDetailLink . "</div>";
            		}

                	if(!$is_export){
                        if ($row['flag'] == Game_logs::FLAG_GAME && in_array($platform_id, $this->pt_seamless_game_apis)) {
                            // remove parent game platform id prefix in external unique id if T1 Game
                            switch($platform_id) {
                                case T1_PT_SEAMLESS_GAME_API:
                                    $external_uniqueid = str_replace(PT_SEAMLESS_GAME_API . '-', '', $external_uniqueid);
                                    break;
                                case T1_IDN_PT_SEAMLESS_GAME_API:
                                    $external_uniqueid = str_replace(IDN_PT_SEAMLESS_GAME_API . '-', '', $external_uniqueid);
                                    break;
                                case T1_IDN_SLOTS_PT_SEAMLESS_GAME_API:
                                    $external_uniqueid = str_replace(IDN_SLOTS_PT_SEAMLESS_GAME_API . '-', '', $external_uniqueid);
                                    break;
                                case T1_IDN_LIVE_PT_SEAMLESS_GAME_API:
                                    $external_uniqueid = str_replace(IDN_LIVE_PT_SEAMLESS_GAME_API . '-', '', $external_uniqueid);
                                    break;
                            }

                            $betDetailLink = $api->queryBetDetailLink($player_username, $external_uniqueid);

                            if (!empty($betDetailLink['url'])) {
                                return '<a href="' . $betDetailLink['url'] . '" target="_blank" class="btn btn-info">' . lang('Bet Detail') . '</a>';
                            }

                            return $this->getDefaultFormatForBetDetails($d);
                        }

                        if ($row['flag'] == Game_logs::FLAG_GAME && in_array($platform_id, $this->habanero_seamless_game_apis)) {
                            // remove parent game platform id prefix in external unique id if T1 Game
                            switch($platform_id) {
                                case T1_HABANERO_SEAMLESS_GAME_API:
                                    $external_uniqueid = str_replace(HABANERO_SEAMLESS_GAMING_API . '-', '', $external_uniqueid);
                                    break;
                                case T1_IDN_HABANERO_SEAMLESS_GAMING_API:
                                    $external_uniqueid = str_replace(IDN_HABANERO_SEAMLESS_GAMING_API . '-', '', $external_uniqueid);
                                    break;
                            }

                            $betDetailLink = $api->queryBetDetailLink($player_username, $external_uniqueid);

                            if (!empty($betDetailLink['url'])) {
                                return '<a href="' . $betDetailLink['url'] . '" target="_blank" class="btn btn-info">' . lang('Bet Detail') . '</a>';
                            }

                            return $this->getDefaultFormatForBetDetails($d);
                        }

                        if ($row['flag'] == Game_logs::FLAG_GAME && in_array($platform_id, $this->evolution_seamless_game_apis)) {
                            // remove parent game platform id prefix in external unique id if T1 Game
                            /* switch($platform_id) {
                                case T1_EVOLUTION_SEAMLESS_GAME_API:
                                    $external_uniqueid = str_replace(EVOLUTION_SEAMLESS_GAMING_API . '-', '', $external_uniqueid);
                                    break;
                                case T1_EVOLUTION_NETENT_SEAMLESS_GAMING_API:
                                    $external_uniqueid = str_replace(EVOLUTION_NETENT_SEAMLESS_GAMING_API . '-', '', $external_uniqueid);
                                    break;
                                case T1_EVOLUTION_NLC_SEAMLESS_GAMING_API:
                                    $external_uniqueid = str_replace(EVOLUTION_NLC_SEAMLESS_GAMING_API . '-', '', $external_uniqueid);
                                    break;
                                case T1_EVOLUTION_REDTIGER_SEAMLESS_GAMING_API:
                                    $external_uniqueid = str_replace(EVOLUTION_REDTIGER_SEAMLESS_GAMING_API . '-', '', $external_uniqueid);
                                    break;
                                case T1_EVOLUTION_BTG_SEAMLESS_GAMING_API:
                                    $external_uniqueid = str_replace(EVOLUTION_BTG_SEAMLESS_GAMING_API . '-', '', $external_uniqueid);
                                    break;
                                case T1_IDN_EVOLUTION_SEAMLESS_GAMING_API:
                                    $external_uniqueid = str_replace(IDN_EVOLUTION_SEAMLESS_GAMING_API . '-', '', $external_uniqueid);
                                    break;
                                case T1_IDN_EVOLUTION_NETENT_SEAMLESS_GAMING_API:
                                    $external_uniqueid = str_replace(IDN_EVOLUTION_NETENT_SEAMLESS_GAMING_API . '-', '', $external_uniqueid);
                                    break;
                                case T1_IDN_EVOLUTION_NLC_SEAMLESS_GAMING_API:
                                    $external_uniqueid = str_replace(IDN_EVOLUTION_NLC_SEAMLESS_GAMING_API . '-', '', $external_uniqueid);
                                    break;
                                case T1_IDN_EVOLUTION_REDTIGER_SEAMLESS_GAMING_API:
                                    $external_uniqueid = str_replace(IDN_EVOLUTION_REDTIGER_SEAMLESS_GAMING_API . '-', '', $external_uniqueid);
                                    break;
                                case T1_IDN_EVOLUTION_BTG_SEAMLESS_GAMING_API:
                                    $external_uniqueid = str_replace(IDN_EVOLUTION_BTG_SEAMLESS_GAMING_API . '-', '', $external_uniqueid);
                                    break;
                            } */

                            $betDetailLink = $api->queryBetDetailLink($player_username, $external_uniqueid, ['round_id' => $roundno]);

                            if (isset($betDetailLink['url']) && !empty($betDetailLink['url'])) {
                                return '<a href="' . $betDetailLink['url'] . '" target="_blank" class="btn btn-info">' . lang('Bet Detail') . '</a>';
                            }

                            return $this->getDefaultFormatForBetDetails($d);
                        }

                        if ($row['flag'] == Game_logs::FLAG_GAME && in_array($platform_id, $this->fa_seamless_game_apis)) {
                            $betDetail = $api->queryBetDetailLink($player_username, $external_uniqueid, ['round_id' => $roundno]);

                            if (!empty($betDetail['url'])) {
                                return '<a href="'. $betDetail['url'] .'" target="_blank" class="btn btn-link btn-xs">'.lang('View Json').'</a>';
                            }

                            return $this->getDefaultFormatForBetDetails($d);
                        }

                        // DEFAULT BET DETAIL FORMAT
                        if($row['flag'] == Game_logs::FLAG_GAME && in_array($platform_id, $this->game_apis_for_default_format_bet_details)) {
                            return $this->getDefaultFormatForBetDetails($d);
                        }

                		if($row['flag'] == Game_logs::FLAG_GAME && $platform_id == CHAMPION_SPORTS_GAME_API){
                			if(!empty($row['betDetails'])){
                				$betDetails = json_decode($d, TRUE);
                				$str = "";
                				if(!empty($betDetails)){
                					foreach ($betDetails as $key => $detail) {
                						$str.= lang("Bet Line") . " : " . $detail['betline'] ."<br>";
                						$str.= lang("Beting Type") . " : " . $detail['bettype'] ."<br>";
                						$str.= lang("Outcome") . " : " . $detail['oddsname'] ."<br>";
                						$str.= lang("Odds") . " : " . $detail['odds'] ."<br>";
                						if(!empty($detail['p1']) && !empty($detail['p2'])){
                							$str.= lang("Participants") . " : " . $detail['p1'] . " vs ". $detail['p2'] . "<br>";
                						}
                						$str.= "---------------------------------------------------<br>";
                					}
                				}
                				return  "<div style='width:200px;'>" . $str . "</div>";
                			}
                		}
                		if ($platform_id == VR_API) {
                			$str = lang('Bet ID'). " : " . $row['roundno'] ."<br>";
                			if(!empty($row['betDetails'])){
                				$array = json_decode($d, TRUE);
                				if(isset($array['bet_details'][$row['roundno']])){
									$bet_placed = $array['bet_details'][$row['roundno']]['bet_placed'];
									$bet_round = $array['bet_details'][$row['roundno']]['issue_number'];
									$bet_typed = $array['bet_details'][$row['roundno']]['bet_type_name'];
									$str .= lang('Bet Placed'). " : " . $bet_placed ."<br>";
									$str .= lang('Bet Round'). " : " . $bet_round ."<br>";
									$str .= lang('Bet Type'). " : " . $bet_typed ."<br>";
                				}
                			}
							return $str;
                		}
	            		if($row['flag'] == Game_logs::FLAG_GAME && $platform_id == LIVEGAMING_API){
	            			$betDetails = "";
	            			$full_betdetails = !empty($row['betDetails'])?json_decode($row['betDetails'],true):null;
	            			$number = 1;
	            			foreach ($full_betdetails as $key => $betdetails) {
	            				if($betdetails['bet_money'] == 0){ # skip 0 bets
	            					continue;
	            				}
	            				$betDetails .= "<b>".$number.".</b> ";
	            				foreach ($betdetails as $detail_key => $value) {
	            					$betDetails .=  "<label><b>" . $detail_key ."</b> : " . $value . "</label> <br>";
	            				}
	            				$number++;
	            			}
	            			return  "<div style='width:200px;'>" . $betDetails . "</div>";
	            		}

	            		if ($row['flag'] == Game_logs::FLAG_GAME && $platform_id == LUCKY_GAME_CHESS_POKER_API) {
	            			$temp = json_decode($d, TRUE);
	            			if($temp && isset($temp['url'])){
	            				return '<a href="'.$temp['url'].'" target="_blank" class="btn btn-info">'.lang('Bet Detail').'</a>';
	            			}
                		}

	            		if ($platform_id == SUNCITY_API || $platform_id == TGP_AG_API) {
	            			$temp = json_decode($row['betDetails'], TRUE);
	            			if($temp && isset($temp['url'])){
	            				return '<a href="'.$temp['url'].'" target="_blank" class="btn btn-info">'.lang('Bet Detail').'</a>';
	            			}

                		}

                        if($row['flag'] == Game_logs::FLAG_GAME && $platform_id == KING_MAKER_GAMING_THB_B1_API) {
                        	$d = stripslashes($d);
	            			// If there's a double quote on bet_details string
							if (substr($d, 0, 1) === '"' && substr($d, -1) === '"') {
								$d = substr($d, 1, -1);
							}

                            $betDetailLink = json_decode($d,true);

                            if($betDetailLink && isset($betDetailLink['link'])) {
                               return '<a href="'.$betDetailLink['link'].'" target="_blank" class="btn btn-info">'.lang('Bet Detail').'</a>';
                            }
                        }

                		if ($platform_id == OG_API) {
                			$temp = json_decode($row['betDetails'], TRUE);
                			if($temp && isset($temp['bet_details'])){
                				return $temp['bet_details'];
                			}
                		}

                		if ($platform_id == TCG_API) {
							$temp = explode(",",json_decode($row['betDetails'], true));
                			if($temp && isset($temp)){
                				return array_values(array_filter($temp, function($v){
					                return $v !== false && !is_null($v) && ($v != '' || $v == '0');
					            }));
                			}
                		}

                		if ($row['flag'] == Game_logs::FLAG_GAME && $platform_id == MG_QUICKFIRE_API) {

                			$api = $this->utils->loadExternalSystemLibObject($platform_id);

                			$temp = json_decode($row['betDetails'], TRUE);
                			if ($temp && isset($temp['game_id'])) {
                				$betDetailLink = $api->queryBetDetailLink($row['player_username'], $temp['game_id']);
								if(isset($betDetailLink['url'])&&!empty($betDetailLink['url'])){
									return '<a href="'.$betDetailLink['url'].'" target="_blank" class="btn btn-info">'.lang('Bet Detail').'</a>';
								}
								return '';
                			}

                		}

                		if($row['flag'] == Game_logs::FLAG_GAME && $platform_id == DONGSEN_ESPORTS_API) {
                			$bet_details = json_decode($d, true);
                			$betDetailLink = "";
                			foreach ($bet_details as $key => $value) {
                				$other_str = "";
		                		if (preg_match('/\blabel\b/',$value)){
		                			$betDetailLink .=  "<label>" . $value . "</label>" . "<br>";
		                				} else {
		                					if($key == "bet"){
			                					$highlighted_str = substr($value, 0, strrpos($value, " "));
			                					$other_str = str_replace($highlighted_str,"",$value);
			                					$value = $highlighted_str;
			                				}
			                		$betDetailLink .=  "<label class='".$key."'>" . $value . "</label>" . $other_str . "<br>";
		                			}
                			}

                			return  "<div style='width:200px;'>" . $betDetailLink . "</div>";
                		}

                		if($row['flag'] == Game_logs::FLAG_GAME && ($platform_id == TFGAMING_ESPORTS_API || $platform_id == T1TFGAMING_ESPORTS_API)) {
                			$bet_details = json_decode($d, true);
                			$betDetailLink = "";
                			foreach ($bet_details as $key => $value) {
                				if(is_array($value)) {
                					$details = implode("<br />", $value);

                					$betDetailLink .= "<label>" . $details . "</label>" . "<br>" . "<br>";
                				} else {
                					$betDetailLink .= $value . "<br>";
                				}

                			}

                			return  "<div style='width:200px;'>" . $betDetailLink . "</div>";
                		}

                		if($row['flag'] == Game_logs::FLAG_GAME && $platform_id == SEXY_BACCARAT_SEAMLESS_API) {
                			$bet_details = json_decode($d, true);
                			$betDetailLink = "";
                			if (isset($bet_details['Result']) && is_array($bet_details['Result'])) {
	                			$result = implode(",", $bet_details['Result']);
	                			$bet_details['Result'] = $result;
	                		}
	                		else {
	                			$bet_details['Result'] = '';
	                		}

                			foreach ($bet_details as $key => $bet_detail) {
                				$betDetailLink .=  "<label>" . $key ."</label>" . ": " . $bet_detail . "<br>";
                			}

                			return  "<div style='width:200px;'>" . $betDetailLink . "</div>";
                		}

                		if ($row['flag'] == Game_logs::FLAG_GAME && (
                			$platform_id == MGPLUS_API 		|| $platform_id == T1MGPLUS_API	   || $platform_id == MGPLUS_IDR1_API || $platform_id == MGPLUS_IDR2_API ||
                			$platform_id == MGPLUS_IDR3_API || $platform_id == MGPLUS_IDR4_API || $platform_id == MGPLUS_IDR5_API || $platform_id == MGPLUS_IDR6_API ||
                			$platform_id == MGPLUS_IDR7_API || $platform_id == MGPLUS_THB2_API || $platform_id == MGPLUS_THB1_API || $platform_id == MGPLUS_VND2_API ||
                			$platform_id == MGPLUS_VND1_API || $platform_id == MGPLUS_CNY2_API || $platform_id == MGPLUS_CNY1_API || $platform_id == MGPLUS_MYR2_API ||
                			$platform_id == MGPLUS_MYR1_API || $platform_id == MGPLUS2_API)) {

                			$api = $this->utils->loadExternalSystemLibObject($platform_id);
								
            				$betDetailLink = $api->queryBetDetailLink($row['player_username'], $row['roundno']);
							if(isset($betDetailLink['url'])&&!empty($betDetailLink['url'])){
								return '<a href="'.$betDetailLink['url'].'" target="_blank" class="btn btn-info">'.lang('Bet Detail').'</a>';
							}

							return '';

                		}
                		if ($platform_id == BAISON_GAME_API ) {
                			$baison_bet_details = (array)json_decode($d, true);
                			$url = isset($baison_bet_details['url']) ? $baison_bet_details['url'] : null;
                			if(!empty($url)){
                				return '<a href="'.$url.'" target="_blank" class="btn btn-info">'.lang('Bet Detail').'</a>';
                			}
                			return '';
						}
						if ($platform_id == AE_SLOTS_GAMING_API ) {
							$ae_slots_bet_details = (array)json_decode($d, true);
							$roundId = isset($ae_slots_bet_details['roundId']) ? $ae_slots_bet_details['roundId'] : null;
							$gameUsername = isset($ae_slots_bet_details['gameUsername']) ? $ae_slots_bet_details['gameUsername'] : null;
							$is_hidden = isset($ae_slots_bet_details['isBet']) ? $ae_slots_bet_details['isBet'] : false;
							$hidden = $is_hidden ? '' : 'hidden';

							$bet_url = '/async/get_bet_detail_link_of_ae_slots/'.$gameUsername."/".$roundId;
							if(!empty($bet_url)){
								return '<a href="'.$bet_url.'" target="_blank" class="btn btn-info" style="visibility:'.$hidden.';">'.lang('Bet Detail').'</a>';
							}
							return '';
						}
						if (
							$platform_id == QUEEN_MAKER_GAME_API ||
							$platform_id == QUEEN_MAKER_REDTIGER_GAME_API ||
							$platform_id == ONEGAME_GAME_API
						) {

							$queen_maker_bet_details = (array)json_decode($d, true);
							$roundId = isset($queen_maker_bet_details['roundId']) ? $queen_maker_bet_details['roundId'] : null;
							$gameUsername = isset($queen_maker_bet_details['gameUsername']) ? $queen_maker_bet_details['gameUsername'] : null;
							$is_hidden = isset($queen_maker_bet_details['isBet']) ? $queen_maker_bet_details['isBet'] : false;
							$hidden = $is_hidden ? '' : 'hidden';

							$bet_url = '/async/get_bet_detail_link_of_queen_maker/'.$gameUsername."/".$roundId."/".$platform_id;
							if(!empty($bet_url)){
								return '<a href="'.$bet_url.'" target="_blank" class="btn btn-info" style="visibility:'.$hidden.';">'.lang('Bet Detail').'</a>';
							}
							return '';
						}

						if ($row['flag'] == Game_logs::FLAG_GAME && $platform_id == GPK_API) {
							$bet_url = '/async/get_bet_detail_link_of_game_api/' . $platform_id . '/' . $player_username . '/' . $external_uniqueid;
							return '<a href="'.$bet_url.'" target="_blank" class="btn btn-info" style="visibility:"visible";">'.lang('Bet Detail').'</a>';
						}

						if ($row['flag'] == Game_logs::FLAG_GAME && $platform_id == JUMB_GAMING_API && $this->utils->getConfig('use_buttons_for_jumbo_betdetails')) {
							$bet_url = '/async/get_bet_detail_link_of_game_api/' . $platform_id . '/' . $player_username . '/' . $external_uniqueid;
							return '<a href="'.$bet_url.'" target="_blank" class="btn btn-info" style="visibility:"visible";">'.lang('Bet Detail').'</a>';
						}

						if($row['flag'] == Game_logs::FLAG_GAME && ($platform_id == PRETTY_GAMING_API || $platform_id == PRETTY_GAMING_API_IDR1_GAME_API || $platform_id == PRETTY_GAMING_API_CNY1_GAME_API || $platform_id == PRETTY_GAMING_API_THB1_GAME_API || $platform_id == PRETTY_GAMING_API_MYR1_GAME_API || $platform_id == PRETTY_GAMING_API_VND1_GAME_API || $platform_id == PRETTY_GAMING_API_USD1_GAME_API)) {

							$bet_url = '/async/get_bet_detail_link_of_game_api/' . $platform_id . '/' . $player_username . '/' . $external_uniqueid . '/' . $roundno;
							return '<a href="'.$bet_url.'" target="_blank" class="btn btn-info" style="visibility:"visible";">'.lang('Bet Detail').'</a>';
                		}


						if ($row['flag'] == Game_logs::FLAG_GAME && $platform_id == KINGPOKER_GAME_API) {
							$bet_url = '/async/get_bet_detail_link_of_game_api/' . $platform_id . '/' . $player_username . '/' . $external_uniqueid . '/' . $roundno;
							return '<a href="'.$bet_url.'" target="_blank" class="btn btn-info" style="visibility:"visible";">'.lang('Bet Detail').'</a>';
						}

                		if($row['flag'] == Game_logs::FLAG_GAME && ($platform_id == PRETTY_GAMING_SEAMLESS_API || $platform_id == PRETTY_GAMING_SEAMLESS_API_IDR1_GAME_API || $platform_id == PRETTY_GAMING_SEAMLESS_API_CNY1_GAME_API || $platform_id == PRETTY_GAMING_SEAMLESS_API_THB1_GAME_API || $platform_id == PRETTY_GAMING_SEAMLESS_API_MYR1_GAME_API || $platform_id == PRETTY_GAMING_SEAMLESS_API_VND1_GAME_API || $platform_id == PRETTY_GAMING_SEAMLESS_API_USD1_GAME_API)) {

							$bet_url = '/async/get_bet_detail_link_of_game_api/' . $platform_id . '/' . $player_username . '/' . $external_uniqueid . '/' . $roundno;
							return '<a href="'.$bet_url.'" target="_blank" class="btn btn-info" style="visibility:"visible";">'.lang('Bet Detail').'</a>';
                		}


						if ($row['flag'] == Game_logs::FLAG_GAME && $platform_id == JOKER_API) {
							$bet_url = '/async/get_bet_detail_link_of_game_api/' . $platform_id . '/' . $player_username . '/' . $external_uniqueid . '/' . $bet_type;
							return '<a href="'.$bet_url.'" target="_blank" class="btn btn-info" style="visibility:"visible";">'.lang('Bet Detail').'</a>';
						}

						if ($row['flag'] == Game_logs::FLAG_GAME && $platform_id == DT_API) {
							$external_uniqueid = urlencode($external_uniqueid);
							$bet_url = '/async/get_bet_detail_link_of_game_api/' . $platform_id . '/' . $player_username . '/' . $external_uniqueid;
							return '<a href="'.$bet_url.'" target="_blank" class="btn btn-info" style="visibility:"visible";">'.lang('Bet Detail').'</a>';
						}

						if ($row['flag'] == Game_logs::FLAG_GAME && $platform_id == LE_GAMING_API && $this->utils->getConfig('allow_le_gaming_bet_details')) {
							$bet_url = '/async/get_bet_detail_link_of_game_api/' . $platform_id . '/' . $player_username . '/' . $external_uniqueid;
							return '<a href="'.$bet_url.'" target="_blank" class="btn btn-info" style="visibility:"visible";">'.lang('Bet Detail').'</a>';
						}

						if ($row['flag'] == Game_logs::FLAG_GAME && ($platform_id == KYCARD_API || $platform_id == T1KYCARD_API)) { // && $this->utils->getConfig('allow_game_bet_details')
							$bet_url = '/async/get_bet_detail_link_of_game_api/' . $platform_id . '/' . $player_username . '/' . $external_uniqueid;
							return '<a href="'.$bet_url.'" target="_blank" class="btn btn-info" style="visibility:"visible";">'.lang('Bet Detail').'</a>';
						}

						if ($row['flag'] == Game_logs::FLAG_GAME && $platform_id == GMT_GAME_API) {
							$bet_url = '/async/get_bet_detail_link_of_game_api/' . $platform_id . '/' . $player_username . '/' . $roundno;
							return '<a href="'.$bet_url.'" target="_blank" class="btn btn-info" style="visibility:"visible";">'.lang('Bet Detail').'</a>';
						}

						if ($row['flag'] == Game_logs::FLAG_GAME && $platform_id == RG_API ) {
                            $rg_bet_details = (array) json_decode($d, true);
                            $html = "";

                            if (isset($rg_bet_details[0])) {

								if($this->utils->isValidJson($rg_bet_details[0]) && !is_array($rg_bet_details[0])){
									$bet_details = (array) json_decode($rg_bet_details[0], true);
								}else{
									$bet_details = (array) $rg_bet_details[0];
								}


	                            if (isset($bet_details[0])) {
	                            	foreach ($bet_details as $key => $value) {
	                            		$html .= "<label>${value['title']} @ ${value['odds']}</label><br>";
	                            		$html .= "<label>" . $value['match_name'] . "</label><br>";
	                            		$html .= "<br>";
	                            	}
	                            } else {
	                            	$html = "<label>" . $bet_details['title'] . "@" . $bet_details['odds'] . "</label><br>";
	                            	$html .= "<label>" . $bet_details['match_name'] . "</label>";
	                            }
                            } else {
                            	$html = "<label>${rg_bet_details['title']} @ ${rg_bet_details['odds']}</label><br>";
	                            $html .= "<label>" . $rg_bet_details['match_name'] . "</label><br>";
                            }

                			return  "<div style='width:200px;'>" . $html . "</div>";
                        }

                        if($row['flag'] == Game_logs::FLAG_GAME && $platform_id == KG_POKER_API) {
                        	$bet_details = json_decode($d, true);

                			$betDetailLink = "";
                			foreach ($bet_details as $key => $value) {
                				$betDetailLink .= ucfirst($key) . ": " . $value . "<br>";
                			}

                			return  "<div style='width:200px;'>" . $betDetailLink . "</div>";
                		}

                		if($row['flag'] == Game_logs::FLAG_GAME && $platform_id == OGPLUS_API) {
                        	$bet_details = json_decode($d, true);

                			$betDetailLink = "";
                			foreach ($bet_details as $key => $value) {
                				$betDetailLink .= ucfirst($key) . ": " . $value . "<br>";
                			}

                			return  "<div style='width:200px;'>" . $betDetailLink . "</div>";
                		}

                        if ($row['flag'] == Game_logs::FLAG_GAME && $platform_id == LB_API ) {
                            $lb_bet_details = (array) json_decode($d, true);
                            $betDetailLink = "";
                            if (array_key_exists("Created At",$lb_bet_details)){
                				unset($lb_bet_details['Created At']);
                			}

                			if (isset($lb_bet_details["bet_details"][0]["bet_content"])) {
                				$bet_type = ($lb_bet_details["bet_details"][0]["bet_type"]) ? $lb_bet_details["bet_details"][0]["bet_type"] : "";

                				if($bet_type == Game_logs::BET_TYPE_SINGLE_BET) {
                					$bet_details = $lb_bet_details["bet_details"][0]["bet_content"];
                					$betDetailLink .=  "<label>" . $bet_details . "</label>" . "<br>";
                				} else {
                					$bet_content = $lb_bet_details["bet_details"][0]["bet_content"];
	                				if (is_array($bet_content)) {
		                				foreach($bet_content as $ki => $vi) {

			                				$betDetailLink .=  "<label>" . $vi . "</label>" . "<br>";
			                			}
		                			}
                				}
                			}

                			return  "<div style='width:200px;'>" . $betDetailLink . "</div>";
                        }

                        if ($row['flag'] == Game_logs::FLAG_GAME && $platform_id == RGS_API ) {
                            $bet_details = (array) json_decode($d, true);
                            $html = "";

                            if (!empty($bet_details)) {
                            	$html =  "<label class='bet'>Bet Details: </label>" . "<br>";
                            	foreach ($bet_details as $key => $value) {
                            		if (!empty($value)) {
                            			$html .= "<label>" .  $key ." : ". $value . "</label><br>";
                            		}
                            	}
                            }
                			return  "<div style='width:200px;'>" . $html . "</div>";
                        }

						if ($row['flag'] == Game_logs::FLAG_GAME && $platform_id == BBIN_API ) {
                            $bet_details = json_decode($d, true);

                            $html = "";

                            if (!empty($bet_details)) {
								if(!is_array($bet_details)){
									$bet_details = (array) json_decode($bet_details, true);
								}

								$html =  "";


								if(isset($bet_details[0]) && is_array($bet_details[0])){



									foreach($bet_details as $k => $bet_detail){

										if($k != 0) {
											$html .= "--------------------<br>";
										}

										foreach ($bet_detail as $key => $value) {
											if (!empty($value)) {
												$html .= "<label>" .  $key ." : ". $value . "</label><br>";
											}
										}
									}

								} else {



									foreach ($bet_details as $key => $value) {
										if (!empty($value)) {
											$html .= "<label>" .  $key ." : ". $value . "</label><br>";
										}
									}

								}


                            }
                			return  "<div style='width:200px;'>" . $html . "</div>";
                        }

						if ($row['flag'] == Game_logs::FLAG_GAME && $platform_id == SA_GAMING_API ) {
                            $bet_details = json_decode($d, true);

                            $html = "";

							if (isset($bet_details["bet_details"])) {

								$html =  "";

								$bet_details = $bet_details["bet_details"];
								$count = 0;
								foreach($bet_details as $k => $bet_detail){

									if($count != 0) {
										$html .= "--------------------<br>";
									}

									$html .= "<label>key : ". $k . "</label><br>";

									foreach ($bet_detail as $key => $value) {
										if (!empty($value)) {
											$html .= "<label>" .  $key ." : ". $value . "</label><br>";
										}
									}

									$count++;
								}

							}
                			return  "<div style='width:200px;'>" . $html . "</div>";
                        }

                        if($row['flag'] == Game_logs::FLAG_GAME && $platform_id == IMESB_API) {
                              $bet_details = json_decode($d, true);
                              $betDetailLink = '';
                              if (!empty($bet_details)) {
                              		$betDetails = json_decode($bet_details,true);
                              		$betdetailsTitle = 'Sports Bet';
                                    $label = '';
                              		$details = '';
                                    foreach ($betDetails['bet_details'] as $key => $v) {
                                        $details .= $key . ": ". $v . "<br>";
                                    }
                                    $betDetailLink .= "<b>" .lang($betdetailsTitle) ."</b>" . ":" . "<br>" . "<label>" . $details . "</label>" . "<br>" . "<br>";
                                }

                                return  "<div style='width:200px;'>" . $betDetailLink . "</div>";

                        }

                        if($row['flag'] == Game_logs::FLAG_GAME && $platform_id == SBTECH_BTI_API) {
                              $bet_details = json_decode($d, true);
                              $betDetailLink = '';
                              if (!empty($bet_details) && is_array($bet_details)) {
                                    foreach ($bet_details['sports_bet'] as $key => $bet_detail) {
                                                $key = 'Sports Bet';
                                                $label = '';
                                                $betDetails = '';
                                                foreach ($bet_detail as $k => $v) {
                                                      $betDetails .= $k . ": ". $v . "<br>";
                                                }

                                                $betDetailLink .= "<b>" .lang($key) ."</b>" . ":" . "<br>" . "<label>" . $betDetails . "</label>" . "<br>" . "<br>";
                                          }
                                    }

                                    return  "<div style='width:200px;'>" . $betDetailLink . "</div>";

                        }

                        if($row['flag'] == Game_logs::FLAG_GAME && $platform_id == BETF_API) {
                            $bet_details = json_decode($d, true);
                            $this->CI->utils->debug_log('BETF encode', gettype($bet_details));
                            $betDetailLink = '';
                            if (!empty($bet_details) && is_array($bet_details)) {
                              foreach ($bet_details as $key => $bet_detail) {
                                          $label = '';
                                          $betDetails = '';
                                          foreach ($bet_detail as $k => $v) {
                                                  $betDetails .= $k . ": ". $v . "<br>";
                                          }

                                          $betDetailLink .= "<label>" . $betDetails . "</label>" . "<br>" . "<br>";
                                      }
                              }

                              return  "<div style='width:200px;'>" . $betDetailLink . "</div>";

                      }

                		if ($platform_id == ONEWORKS_API || $platform_id == T1ONEWORKS_API || $platform_id == ONEBOOK_THB_B1_API || $platform_id == IBC_ONEBOOK_API || $platform_id == IBC_ONEBOOK_SEAMLESS_API) {
                			//check array
                			$data = (array)json_decode($d, true);

                			$betDetailLink = "";
                			if (!empty($data) && is_array($data)) {
                				if (array_key_exists("Created At",$data)){
	                				unset($data['Created At']);
	                			}
	                			if (array_key_exists("Last Sync Time",$data)){
	                				unset($data['Last Sync Time']);
	                			}
								if($bet_type == Game_logs::BET_TYPE_SINGLE_BET || $bet_type == Game_logs::BET_TYPE_OLD_SINGLE_BET) {

	                				if(!empty($data) && (is_array($data) || is_object($data))) {
	                					if(count($data) == 1) { //single new_format
	                						$datax = json_decode($data[0], true);

	                						if(!empty($datax) && (is_array($datax) || is_object($datax))) {

                                                if(!empty($datax["bet_details"])) {
												    $betDetailLink .= @$this->parseOneworksBetDetails($datax["bet_details"], 'Bet Details');
                                                }

												//for bet details
							                	/*$betDetailLink .=  "<label class='bet'>Bet Details: </label>" . "<br>";
												foreach($datax["bet_details"] as $ki => $vi) {
													$other_str = "";

							                		if (preg_match('/\blabel\b/',$vi)){
							                			$betDetailLink .=  "<label>" . $vi . "</label>" . "<br>";
							                		} else {
							               				if($ki == "bet"){
								               				$highlighted_str = substr($vi, 0, strrpos($vi, " "));
								               				$other_str = str_replace($highlighted_str,"",$vi);
								               				$vi = "<br>".$highlighted_str;
								               			}elseif($ki == "is_parlay"){
								               				continue;
								               			}
							               			}
							               			$betDetailLink .=  "<label class='".$ki."'>" . $vi . "</label>" . $other_str . "<br>";
												}*/

                                                if(!empty($datax["match_details"])) {
                                                    //for match details
                                                    $betDetailLink .= "<br><br>";
                                                    $betDetailLink .= @$this->parseOneworksBetDetails($datax["match_details"], 'Match Details');
                                                }

							                	/*$betDetailLink .=  "<br><label class='bet'>Match Details: </label>" . "<br>";
												foreach($datax["match_details"] as $ki => $vi) {
													$other_str = "";

							                		if (preg_match('/\blabel\b/',$vi)){
							                			$betDetailLink .=  "<label>" . $vi . "</label>" . "<br>";
							                		} else {
							               				if($ki == "bet"){
								               				$highlighted_str = substr($vi, 0, strrpos($vi, " "));
								               				$other_str = str_replace($highlighted_str,"",$vi);
								               				$vi = "<br>".$highlighted_str;
								               			}elseif($ki == "is_parlay"){
								               				continue;
								               			}
							               			}
							               			$betDetailLink .=  "<label class='".$ki."'>" . $ki . ' : ' . $vi . "</label>" . $other_str . "<br>";
												}*/
	                						}
	                					}else { //single old format
											foreach ($data as $key => $value) {
		                						$other_str = "";
		                						if (preg_match('/\blabel\b/',$value)){
		                							$betDetailLink .=  "<label>" . $value . "</label>" . "<br>";
		                						} else {
		                							if($key == "bet"){
			                							$highlighted_str = substr($value, 0, strrpos($value, " "));
			                							$other_str = str_replace($highlighted_str,"",$value);
			                							$value = $highlighted_str;
			                						}

			                						$betDetailLink .=  "<label class='".$key."'>" . $value . "</label>" . $other_str . "<br>";
			                					}
			                				}
			                			}
			                		}
	                			}else { //if parlay
	                				if(!empty($data) && (is_array($data) || is_object($data))) {
	                					if(count($data) == 1) { //new format - multibet
	                						$datax = json_decode($data[0], true);

		                					foreach ($data as $key => $parlayi) {
		                						//check old format of bet_details
			                					$datai = (array)json_decode($parlayi, true);

			                					//for new bet_details format
			                					if (array_key_exists("bet_details", $datai)) {
													if(!empty($datai) && (is_array($datai) || is_object($datai))) {

														//for bet details

														$betDetailLink .= @$this->parseOneworksBetDetails($datai['bet_details'], 'Bet Details');

														/*foreach($datai['bet_details'] as $k => $v) {
															foreach($datai['bet_details'][$k] as $ki => $vi){
																$other_str = "";

							                					if (preg_match('/\blabel\b/',$vi)){
							                						$betDetailLink .=  "<label>" . $vi . "</label>" . "<br>";
							                					} else {
							                						if($ki == "bet"){
								                						$highlighted_str = substr($vi, 0, strrpos($vi, " "));
								                						$other_str = str_replace($highlighted_str,"",$vi);
								                						$vi = "<br>".$highlighted_str;
								                					}elseif($ki == "is_parlay"){
								                						continue;
								                					}
							                					}
							                					$betDetailLink .=  "<label class='".$ki."'>" . $vi . "</label>" . $other_str . "<br>";
															}
														}*/

														//for match details
														$betDetailLink .= "<br><br>";
														$betDetailLink .= @$this->parseOneworksBetDetails($datai["match_details"], 'Match Details');

													}

				                					$betDetailLink .= "<br>";
				                				}
				                			}
										}else {	// old format - multibet

	                						foreach ($data as $ki => $vi) {
	                							foreach($data[$ki] as $kil => $vil){
													$other_str = "";

				                					if (preg_match('/\blabel\b/',$vil)){
				                						$betDetailLink .=  "<label>" . $vil . "</label>" . "<br>";
				                					} else {
				                						if($kil == "bet"){
					                						$highlighted_str = substr($vil, 0, strrpos($vil, " "));
					                						$other_str = str_replace($highlighted_str,"",$vil);
					                						$vil = "<br>".$highlighted_str;
					                					}elseif($kil == "is_parlay"){
					                						continue;
					                					}
				                					}
				                					$betDetailLink .=  "<label class='".$kil."'>" . $vil . "</label>" . $other_str . "<br>";
				                				}

	                						}
	                					}
	                				}
 								}

	                			if(trim($betDetailLink) == '')
	                				$betDetailLink = lang('N/A');

	                			return  "<div style='width:200px;'>" . $betDetailLink . "</div>";
                			}
						}

						if($row['flag'] == Game_logs::FLAG_GAME &&
						($platform_id == VIVOGAMING_SEAMLESS_API ||
						$platform_id == VIVOGAMING_SEAMLESS_IDR1_API ||
						$platform_id == VIVOGAMING_SEAMLESS_CNY1_API ||
						$platform_id == VIVOGAMING_SEAMLESS_THB1_API ||
						$platform_id == VIVOGAMING_SEAMLESS_USD1_API ||
						$platform_id == VIVOGAMING_SEAMLESS_VND1_API ||
						$platform_id == VIVOGAMING_SEAMLESS_MYR1_API)
						){
							$betDetails = json_decode($d,true);
							$betDetailsString = '<label>History:</label>';
							foreach($betDetails as $key => $value){
								if(is_array($value)){
									foreach($value as $key2 => $value2){
										$betDetailsString .= "<label>$key2: $value2</label>";
									}
								}else{
									$betDetailsString .= "<label>$key: $value</label>";
								}
							}

							return $betDetailsString;
						}

						if($row['flag'] == Game_logs::FLAG_GAME && $platform_id == BETGAMES_SEAMLESS_THB1_GAME_API) {
							$bet_details = json_decode($d, true);
							$betDetailLink = "";
							if(is_array($bet_details)) {
	            				foreach ($bet_details as $bet_detail) {
	            					$betDetails="";
	            					foreach ($bet_detail as $key => $value) {
	            						$betDetails .= "<b>". $key . "</b>" . ": " . $value . "<br>";
	            					}
	            					$betDetailLink .= "<label>" . $betDetails . "</label>" . "<br>";
	            				}

	                			return  "<div style='width:200px;'>" . $betDetailLink . "</div>";

							} else {
								return "<div style='width:200px;'>" . $d . "</div>";
							}
						}

						if ($row['flag'] == Game_logs::FLAG_GAME && $platform_id == PLAYSTAR_API) {

                			$api = $this->utils->loadExternalSystemLibObject($platform_id);

            				$betDetailLink = $api->queryBetDetailLink($row['playerId'], $row['roundno']);
							if(isset($betDetailLink['url'])&&!empty($betDetailLink['url'])){
								return '<a href="'.$betDetailLink['url'].'" target="_blank" class="btn btn-info">'.lang('Bet Detail').'</a>';
							}
							return '';

                		}

						if($row['flag'] == Game_logs::FLAG_GAME && ($platform_id == SPORTSBOOK_FLASH_TECH_GAME_API || $platform_id == T1SPORTSBOOK_FLASH_TECH_GAME_API)) {
                            $ft_bet_details = (array)json_decode($d, true);
                            if(isset($ft_bet_details['home_team_name'])){
                                return $this->formatBetDetails(SPORTSBOOK_FLASH_TECH_GAME_API, $d);
                            }
                        }

						if($row['flag'] == Game_logs::FLAG_GAME && $platform_id == EVOLUTION_SEAMLESS_THB1_API) {
							$betDetailsArr = (array)json_decode($d, true);
							return $this->utils->formatResursiveJsonToHtmlListBetDetails($betDetailsArr, 'Result');
						}

						if($row['flag'] == Game_logs::FLAG_GAME && $platform_id == AB_V2_GAME_API) {
							$betDetailsArr = (array)json_decode($d, true);
							return $this->utils->formatResursiveJsonToHtmlListBetDetails($betDetailsArr, 'Result');
						}

						if($row['flag'] == Game_logs::FLAG_GAME && $platform_id == LOTO_SEAMLESS_API) {
							$betDetailsArr = (array)json_decode($d, true);
							return $this->utils->formatResursiveJsonToHtmlListBetDetails($betDetailsArr, "Details");
						}

						if($row['flag'] == Game_logs::FLAG_GAME && ($platform_id == QT_HACKSAW_SEAMLESS_API || $platform_id == T1_QT_HACKSAW_SEAMLESS_API)) {
							$betDetailsArr = (array)json_decode($d, true);
							$bet_details = '';
							$processItem = function($array) use (&$bet_details, &$processItem) {
								foreach ($array as $key => $value) {
									if (is_array($value)) {
										if (!is_numeric($key)) {
											$bet_details .= '<h6><strong>' . lang($key) . '</strong></h6>';
										}
										$processItem($value);
										$bet_details .= '<br>';
									} else {
										$bet_details .= '<span style="white-space: nowrap;">' . lang($key) . ': ' . $value . '</span><br>';
									}
								}
							};

							if (!empty($d) || $d != '') {
								$data = json_decode($d, true);
								if (is_array($data)) {
									$processItem($data);
								} else {
									$bet_details = $d;
								}
							} else {
								$bet_details = 'N/A';
							}
							
							return $bet_details != '' ? $bet_details : $this->utils->formatResursiveJsonToHtmlListBetDetails($betDetailsArr, "Details");
						}

                		if (!empty($d)) {
                			$data = json_decode($d, true);
                			$betDetailLink = "";

                			if (!empty($data) && is_array($data)) {
                				foreach ($data as $key => $value) {
                					if (!empty($value)) {
                						if (!empty($betDetailLink))
                							$betDetailLink .= ", ";
                						if(is_array($value)){
                							$value=formatDebugMessage($value);
                						}else{
                							$value=lang($value);
                						}
                						if($key == 'sports_bet'){
                							$key = 'Sports Bet';
                							$res =  json_decode($value, true);
                							$label = '';
                							foreach($res as $k => $v){
                								if(isset($v['yourBet'])) {
                									$live = $v['isLive'] == true ? 'Live!' : 'Not Live';
                									$htScore = $v['htScore'];
                									if(is_array($htScore) ){
                										$scoreDet = '';
                										foreach($htScore as $n => $score){
                											$scoreDet .= $htScore[$n]['score'].' ';
                										}
                										$htScore = "(".$scoreDet.")";
                									}
                									$label .= '<p>'.$v['yourBet'].', '.$v['odd'].', '.$live.', '.$htScore;
                									$label .= (isset($v['eventName']) && isset($v['league']) ) ? ', '.$v['eventName'].', '.$v['league'] : '</p>';
                								}
                							}
                							$value = $label;
                						}
										
                						$betDetailLink .= lang($key) . " : " .$value;

                						if ($key == 'bet_details') {
                							unset($betDetailLink);
                							$details = json_decode($value, true);
                							$label = '';

                                    	# bet ids
                							if (is_array($details) && !empty($details)) {
                								foreach ($details as $details_id => $detail) {
                									$bet_list = '';
                                    			# bet list
                									if (is_array($detail))  {

                										foreach ($detail as $detail_key => $bets) {
                                    					# list of not included in the display
                											if (in_array($detail_key, array('odds', 'won_side', "win_amount"))) {
                												continue;
                											}
                											$bet_list .=  lang('bet_detail.'.$detail_key) . ":" . lang($bets) . ", ";
                										}
                									}

                									$label .= "<div style='border-bottom:solid 0.1em #C0C0C0; margin-top:0.5em;'>" . lang('Bet ID') .": " . $details_id . "<br> (" . substr($bet_list, 0, -2) . ")</div>";
                								}
                							}
                							$betDetailLink = $label;
                						}
                					}
                				}
								if (!empty($platform_id)) {
									if($platform_id == MG_API) {
										$api = $this->utils->loadExternalSystemLibObject($platform_id);
										$gameProviderInfo = $this->game_provider_auth->getByPlayerIdGamePlatformId($row['playerId'], $platform_id);
										$getPlayerGameHistoryURL = $api->queryBetDetailLink($gameProviderInfo['login_name'], null, array('password'=> $gameProviderInfo['password']));
										if ($getPlayerGameHistoryURL && $getPlayerGameHistoryURL['success']) {
											if(isset($getPlayerGameHistoryURL['url'])&&!empty($getPlayerGameHistoryURL['url'])){
												$betDetailLink .= '<a href="'.$getPlayerGameHistoryURL['url'].'" target="_blank" class="btn btn-info">'.lang('Bet Detail').'</a>';
											}
											
										}
									} elseif ($platform_id == QT_API) {
										if (!empty($betDetailLink))
											$betDetailLink .= "<br>";
										$betDetailLink .= '<a href="'.site_url('marketing_management/queryBetDetail/' . $row['game_platform_id'] . '/' . $row['playerId']).'" target="_blank" class="btn btn-info">'.lang('Bet Detail').'</a>';
									} elseif ($platform_id == TPG_API) {
										$language = $this->language_function->getCurrentLanguage();
										if (!empty($betDetailLink))
											$betDetailLink = "";
										$betDetailLink .=  '<a href="'.site_url('async/get_bet_detail_link_of_game_api/' . $row['game_platform_id'] . '/' . $player_username .'/'.$row['roundno'].'/'. $language).'" target="_blank" class="btn btn-info">'.lang('Bet Detail').'</a>';
									}
								}
                			}else{
                				$bet_details = stripslashes(substr($d, 1, -1));
                				$betDetailLink = lang($bet_details);
                			}
                			#check if json
                			if($this->utils->isValidJson($betDetailLink)){
                				$encoded_string = urlencode( base64_encode($betDetailLink));
                				$json_print_url = '/echoinfo/pretty_print_json/' . $encoded_string;
								return '<a href="'.$json_print_url.'" target="_blank" class="btn btn-link btn-xs">'.lang('View Json').'</a>';
                			}
                			return $betDetailLink;
                		}else{
                			$bet_details = stripslashes(substr($d, 1, -1));
                			return $d ? $bet_details : "N/A";
                		}// EOF if (!empty($d)) {...

                	}else{ // else IN if(!$is_export){...
                		if ($this->utils->isEnabledFeature('enable_show_bet_details_gamelogs_report')) {
                		 	$platform_id = (int) $row['game_platform_id'];
                            $bet_type =  $row['bet_type'];

                            if ($row['flag'] == Game_logs::FLAG_GAME && $platform_id == MG_QUICKFIRE_API) {

                                $api = $this->utils->loadExternalSystemLibObject($platform_id);

                                $temp = json_decode($row['betDetails'], TRUE);
                                if ($temp && isset($temp['game_id'])) {
                                    $betDetailLink = $api->queryBetDetailLink($row['player_username'], $temp['game_id']);
									if(isset($betDetailLink['url'])&&!empty($betDetailLink['url'])){
										return strip_tags('<a href="'.$betDetailLink['url'].'" target="_blank" class="btn btn-info">'.lang('Bet Detail').'</a>');
									}
									return '';
                                }

                            }

                            if ($row['flag'] == Game_logs::FLAG_GAME && $platform_id == LUCKY_GAME_CHESS_POKER_API) {
                                $temp = json_decode($d, TRUE);
                                if($temp && isset($temp['url'])){
                                    return $temp['url'];
                                }
                            }

                            if ($platform_id == ONEWORKS_API || $platform_id == T1ONEWORKS_API) {
                                $data = (array)json_decode($d, true);
                                $betDetailLink = "";
                                if (!empty($data) && is_array($data)) {
                                    if (array_key_exists("Created At",$data)){
                                        unset($data['Created At']);
                                    }
                                    if($bet_type == Game_logs::BET_TYPE_OLD_SINGLE_BET) {
                                        if(!empty($data) && (is_array($data) || is_object($data)))
                                        {
                                            foreach ($data as $key => $value) {
                                                $betDetailLink .=  "<label>" . $value . "</label>" . "<br>";
                                            }
                                        }
                                    }else if($bet_type == Game_logs::BET_TYPE_SINGLE_BET) {
                                        if(!empty($data) && (is_array($data) || is_object($data)))
                                        {
                                            foreach ($data as $key => $value) {
                                                $other_str = "";
                                                if (preg_match('/\blabel\b/',$value)){
                                                $betDetailLink .=  "<label>" . $value . "</label>" . "<br>";
                                                } else {
                                                    if($key == "bet"){
                                                        $highlighted_str = substr($value, 0, strrpos($value, " "));
                                                        $other_str = str_replace($highlighted_str,"",$value);
                                                        $value = $highlighted_str;
                                                    }
                                                $betDetailLink .=  "<label class='".$key."'>" . $value . "</label>" . $other_str . "<br>";
                                                }
                                            }
                                        }
                                    }
                                    else { //if parlay
                                        if(!empty($data) && (is_array($data) || is_object($data)))
                                        {
                                            foreach ($data as $key => $parlayi) {
                                                $datai = json_decode(json_encode($parlayi), true);
                                                if(!empty($datai)){
                                                    foreach ($datai as $key => $valuei) {
                                                        $betDetailLink .=  "<label>" . $valuei . "</label>" . "<br>";
                                                    }
                                                }
                                                $betDetailLink .= "<br>";
                                            }
                                        }
                                    }
                                    if(trim($betDetailLink) == '')
                                        $betDetailLink = lang('N/A');
                                    return  strip_tags("<div style='width:200px;'>" . $betDetailLink . "</div>");
                                }
                            }

                            if ($platform_id == IBC_ONEBOOK_SEAMLESS_API || $platform_id == IBC_ONEBOOK_API) {
                                //? copied from other function that shows in sbe datatables
                                //? modified to the requirements of the bet details with IBC ONEBOOK
                                //check array
                                $data = (array)json_decode($d, true);

                                $betDetailLink = '';
                                if (!empty($data) && is_array($data)) {
                                    if (array_key_exists("Created At",$data)){
                                        unset($data['Created At']);
                                    }
                                    if (array_key_exists("Last Sync Time",$data)){
                                        unset($data['Last Sync Time']);
                                    }
                                    if($bet_type == Game_logs::BET_TYPE_SINGLE_BET || $bet_type == Game_logs::BET_TYPE_OLD_SINGLE_BET) {

                                        if(!empty($data) && (is_array($data) || is_object($data))) {
                                            if(count($data) == 1) { //single new_format
                                                $datax = json_decode($data[0], true);

                                                if(!empty($datax) && (is_array($datax) || is_object($datax))) {

                                                    $betDetailLink .= @$this->parseOneworksBetDetails($datax["bet_details"], 'Bet Details', true);

                                                    //for match details
                                                    $betDetailLink .= PHP_EOL . PHP_EOL;
                                                    $betDetailLink .= @$this->parseOneworksBetDetails($datax["match_details"], 'Match Details', true);

                                                }
                                            }else { //single old format
                                                foreach ($data as $key => $value) {
                                                    $other_str = "";
                                                    if (preg_match('/\blabel\b/',$value)){
                                                        $betDetailLink .= $value . PHP_EOL;
                                                    } else {
                                                        if($key == "bet"){
                                                            $highlighted_str = substr($value, 0, strrpos($value, " "));
                                                            $other_str = str_replace($highlighted_str,"",$value);
                                                            $value = $highlighted_str;
                                                        }

                                                        $betDetailLink .= $value . $other_str . PHP_EOL;
                                                    }
                                                }
                                            }
                                        }
                                    }else { //if parlay
                                        if(!empty($data) && (is_array($data) || is_object($data))) {
                                            if(count($data) == 1) { //new format - multibet
                                                $datax = json_decode($data[0], true);

                                                foreach ($data as $key => $parlayi) {
                                                    //check old format of bet_details
                                                    $datai = (array)json_decode($parlayi, true);

                                                    //for new bet_details format
                                                    if (array_key_exists("bet_details", $datai)) {
                                                        if(!empty($datai) && (is_array($datai) || is_object($datai))) {

                                                            //for bet details

                                                            $betDetailLink .= @$this->parseOneworksBetDetails($datai['bet_details'], 'Bet Details', true);

                                                            //for match details
                                                            $betDetailLink .= PHP_EOL . PHP_EOL;
                                                            $betDetailLink .= @$this->parseOneworksBetDetails($datai["match_details"], 'Match Details', true);

                                                        }

                                                        $betDetailLink .= PHP_EOL;
                                                    }
                                                }
                                            }else {	// old format - multibet

                                                foreach ($data as $ki => $vi) {
                                                    foreach($data[$ki] as $kil => $vil){
                                                        $other_str = "";

                                                        if (preg_match('/\blabel\b/',$vil)){
                                                            $betDetailLink .=  $vil . PHP_EOL;
                                                        } else {
                                                            if($kil == "bet"){
                                                                $highlighted_str = substr($vil, 0, strrpos($vil, " "));
                                                                $other_str = str_replace($highlighted_str,"",$vil);
                                                                $vil = PHP_EOL. $highlighted_str;
                                                            }elseif($kil == "is_parlay"){
                                                                continue;
                                                            }
                                                        }
                                                        $betDetailLink .=  $vil . $other_str . PHP_EOL;
                                                    }

                                                }
                                            }
                                        }
                                    }

                                    if(trim($betDetailLink) == '')
                                        $betDetailLink = lang('N/A');

                                    return $betDetailLink;
                                }
                            }

                            if (!empty($d)) {
                                $data = json_decode($d, true);
                                $betDetailLink = "";
                                if (!empty($data) && is_array($data)) {
                                    foreach ($data as $key => $value) {
                                        if (!empty($value)) {
                                            if (!empty($betDetailLink))
                                                $betDetailLink .= ", ";
                                            if(is_array($value)){
                                                $value=formatDebugMessage($value);
                                            }else{
                                                $value=lang($value);
                                            }
                                            if($key == 'sports_bet'){
                                                $key = 'Sports Bet';
                                                $res =  json_decode($value, true);
                                                $label = '';
                                                foreach($res as $k => $v){
                                                    if(isset($v['yourBet'])) {
                                                        $live = $v['isLive'] == true ? 'Live!' : 'Not Live';
                                                        $htScore = $v['htScore'];
                                                        if(is_array($htScore) ){
                                                            $scoreDet = '';
                                                            foreach($htScore as $n => $score){
                                                                $scoreDet .= $htScore[$n]['score'].' ';
                                                            }
                                                            $htScore = "(".$scoreDet.")";
                                                        }
                                                        $label .= '<p>'.$v['yourBet'].', '.$v['odd'].', '.$live.', '.$htScore;
                                                        $label .= (isset($v['eventName']) && isset($v['league']) ) ? ', '.$v['eventName'].', '.$v['league'] : '</p>';
                                                    }
                                                }
                                                $value = $label;
                                            }

                                            $betDetailLink .= lang($key) . " : " .$value;

                                            if ($key == 'bet_details') {
                                                unset($betDetailLink);
                                                $details = json_decode($value, true);
                                                $label = '';

                                            # bet ids
                                                if (is_array($details) && !empty($details)) {
                                                    foreach ($details as $details_id => $detail) {
                                                        $bet_list = '';
                                                    # bet list
                                                        if (is_array($detail))  {

                                                            foreach ($detail as $detail_key => $bets) {
                                                            # list of not included in the display
                                                                if (in_array($detail_key, array('odds', 'won_side', "win_amount"))) {
                                                                    continue;
                                                                }
                                                                $bet_list .=  lang($detail_key) . ":" . lang($bets) . ", ";
                                                            }
                                                        }

                                                        $label .= "<div style='border-bottom:solid 0.1em #C0C0C0; margin-top:0.5em;'>" . lang('Bet ID') .": " . $details_id . "<br> (" . substr($bet_list, 0, -2) . ")</div>";
                                                    }
                                                }
                                                $betDetailLink = $label;
                                            }
                                        }
                                    }
                                    if (!empty($platform_id)) {
                                        if($platform_id == MG_API) {
                                            $api = $this->utils->loadExternalSystemLibObject($platform_id);
                                            $gameProviderInfo = $this->game_provider_auth->getByPlayerIdGamePlatformId($row['playerId'], $platform_id);
                                            $getPlayerGameHistoryURL = $api->queryBetDetailLink($gameProviderInfo['login_name'], null, array('password'=> $gameProviderInfo['password']));
                                            if ($getPlayerGameHistoryURL && $getPlayerGameHistoryURL['success'] && isset($getPlayerGameHistoryURL['url']) && !empty($getPlayerGameHistoryURL['url'])) {
                                                $betDetailLink .= '<a href="'.$getPlayerGameHistoryURL['url'].'" target="_blank" class="btn btn-info">'.lang('Bet Detail').'</a>';
                                            }
                                        } elseif ($platform_id == QT_API) {
                                            if (!empty($betDetailLink))
                                                $betDetailLink .= "<br>";
                                            $betDetailLink .= '<a href="'.site_url('marketing_management/queryBetDetail/' . $row['game_platform_id'] . '/' . $row['playerId']).'" target="_blank" class="btn btn-info">'.lang('Bet Detail').'</a>';
                                        } elseif ($platform_id == TPG_API) {
                                            $language = $this->language_function->getCurrentLanguage();
                                            if (!empty($betDetailLink))
                                                $betDetailLink = "";
                                            $betDetailLink .=  '<a href="'.site_url('async/get_bet_detail_link_of_game_api/' . $row['game_platform_id'] . '/' . $player_username .'/'.$row['roundno'].'/'. $language).'" target="_blank" class="btn btn-info">'.lang('Bet Detail').'</a>';
                                        }
                                    }
                                }else{
                                    $betDetailLink = lang($d) ;
                                }
                                return strip_tags($betDetailLink);
                            }else{
                                $bet_details = stripslashes(substr($d, 1, -1));
                                return $d ? $bet_details : "N/A";
                            }
                        } else {
                            $bet_details = stripslashes(substr($d, 1, -1));
                            return $d ? $bet_details : "N/A";
                        }
                	} // EOF if(!$is_export){...
                },//
            ),
            array(
                'dt' => $this->utils->isEnabledFeature('show_bet_time_column') ? $i++ : NULL,
                'alias' => 'bet_time',
                'select' => 'game_logs.start_at',
                'name' => lang('player.ug06'),
                'formatter' => 'dateTimeFormatter',
            ),
			array(
				'dt' => $i++,
				'alias' => 'flag',
				'select' => 'game_logs.flag',
				'name' => lang('player.ut10'),
                'formatter' => 'defaultFormatter'
			),
			array(
				'dt' => $i++,
				'alias' => 'gameproviderId',
				'select' => 'external_system.id',
				'name' => lang('gameproviderId'),
			),
            array(
                'dt' => $i++,
                'alias' => 'bet_type',
                'select' => 'game_logs.bet_type',
                'name' => lang('Bet Type'),
                'formatter' => function ($d, $row) use($is_export, $i) {
                	$platform_id = (int) $row['game_platform_id'];
					if($platform_id == IBC_ONEBOOK_SEAMLESS_API){
						if (!empty($row['roundno'])) {
							$round = $row['roundno'];
							$link = "'/echoinfo/match_details/$platform_id/$round','Match Details','width=100,height=300'";
							return '<a href="javascript:void(window.open('.$link.'))">'.$d.'</a>';
						}else{
							return $d;
						}
                	}
                	if($platform_id == ONEWORKS_API || $platform_id == T1ONEWORKS_API || $platform_id == SUNCITY_API || $platform_id == TFGAMING_ESPORTS_API || $platform_id == TGP_AG_API || $platform_id == SEXY_BACCARAT_SEAMLESS_API || $platform_id == TCG_API || $platform_id == AB_V2_GAME_API) {
                		return $d;
                	}

                    $return_link_game_apis = [
                        VR_API,
                    ];

                    if( ! $is_export && (strpos(strtolower($d), 'single') != 'single' && !empty($d) && in_array($platform_id, $return_link_game_apis))){
                        $unique_id = $row['unique_id'];
                        $bets = json_decode($row['betDetails'], true);

                        $count = 2;
                        $count = !empty($bets['sports_bet']) ? count($bets['sports_bet']):0;
                        $count = !empty($bets['bet_details']) ? count($bets['bet_details']) + $count: $count;
                        $h = ($count > 1) ? ($count * 33) + 155 : 188;
                        $link = "'/echoinfo/bet_details/$unique_id','Match Details','width=840,height=$h'";
                        return '<a href="javascript:void(window.open('.$link.'))">'.$d.'</a>';
                    }else{
                        return $d;
                    }
                },
            ),
            array(
                'dt' => $this->utils->isEnabledFeature('show_sports_game_columns_in_game_logs') ? $i++ : NULL,
                'alias' => 'match_type',
                'select' => 'game_logs.match_type',
                'name' => lang('Match Type'),
                'formatter' => function ($d, $row) use($is_export) {
                	$platform_id = (int) $row['game_platform_id'];
                	if($is_export){ //start OGP-8467
                		if($platform_id == IDN_API) {
                		$data = json_decode($d,true);
                			return $array = (is_array($data) && $data["hand"] != "-") ? strip_tags($data["hand"].'<br><a href="javascript:void(0);" class="showCardList" data-card="'.$data["card"].'" ><i class="fa fa-search"></i></a>') : 'N/A';
	                	} else if($platform_id == ONEWORKS_API || $platform_id == SBTECH_BTI_API) {
	                		return strip_tags($d);
	                	} else {
	                    	return ($d == '0' || empty($d)) ? 'N/A' : 'Live';
	                	}
                	} else { //end OGP-8467
                		if ($platform_id == SUNCITY_API) {
							$api = $this->utils->loadExternalSystemLibObject($platform_id);
							return $api->convertMatchTypeCodeToReadable($d);
                		}

                		if($platform_id == IDN_API) {
                		$data = json_decode($d,true);
                			return $array = (is_array($data) && $data["hand"] != "-") ? $data["hand"].'<br><a href="javascript:void(0);" class="showCardList" data-card="'.$data["card"].'" ><i class="fa fa-search"></i></a>' : 'N/A';
	                	} else if($platform_id == ONEWORKS_API || $platform_id == SUNCITY_API || $platform_id == SBTECH_BTI_API) {
	                		return $d;
	                	} else {
	                    	return ($d == '0' || empty($d)) ? 'N/A' : 'Live';
	                	}
                	}
                }
            ),
			array(
				'dt' => $this->utils->isEnabledFeature('show_sports_game_columns_in_game_logs') ? $i++ : NULL,
				'alias' => 'match_details',
				'select' => 'game_logs.match_details',
				'name' => lang('Match Details'),
                'formatter' => function ($d, $row) use($is_export, $i) {
                	$platform_id = (int) $row['game_platform_id'];
                	if($is_export){ //start OGP-8467
                		if($d === NULL)//display null only as N/A
	                	{
	                		return lang('N/A');
	                	}
	                	if ($platform_id == ONEWORKS_API) {
	                		$betDetailLink = "";
	            			$data = json_decode($d, true);
	            			if (!empty($data) && is_array($data)) {
	            				foreach ($data as $key => $value) {
		            				$betDetailLink .=  "<label>" . $key . " : ". $value . "</label>" . "<br>";
		            			}
	            			}
	            			return strip_tags("<div style='width:200px;'>" . $betDetailLink . "</div>");
	            		}
	                	//return and accept 0 (string)
	                    return strip_tags($d);
                	} else {
                		if($d === NULL)//display null only as N/A
	                	{
	                		return lang('N/A');
	                	}
	                	if ($platform_id == ONEWORKS_API) {
	                		$betDetailLink = "";
	            			$data = json_decode($d, true);
	            			if (!empty($data) && is_array($data)) {
	            				foreach ($data as $key => $value) {
		            				$betDetailLink .=  "<label>" . $key . " : ". $value . "</label>" . "<br>";
		            			}
	            			}
	            			return "<div style='width:200px;'>" . $betDetailLink . "</div>";
	            		}
	                	//return and accept 0 (string)
	                    return $d;
                	}


                },
			),
            array(
                'dt' => $this->utils->isEnabledFeature('show_sports_game_columns_in_game_logs') ? $i++ : NULL,
                'alias' => 'handicap',
                'select' => 'game_logs.handicap',
                'name' => lang('Handicap'),
                'formatter' => function ($d, $row) use($is_export, $i) {
                	$platform_id = (int) $row['game_platform_id'];
                	if ($row['flag'] == GAME_LOGS::FLAG_GAME && in_array($platform_id, $this->utils->getConfig('game_history_override_handicap_if_zero_platform_ids'))) {
                			return ($d == '0' || empty($d)) ? '0.00' : $d;
                	} else {
                		# OGP-16589
                		if($platform_id == RG_API){
                			return $d?lang('lang.yes'):lang('lang.no');
                		}else{
                			return ($d == '0' || empty($d)) ? lang('lang.norecyet') : $d;
                		}
                	}
                }

            ),
            array(
                'alias' => 'odds_type',
                'select' => 'game_logs.odds_type',
            ),
			array(
				'dt' => $this->utils->isEnabledFeature('show_sports_game_columns_in_game_logs') ? $i++ : NULL,
				'alias' => 'odds',
				'select' => 'game_logs.odds',
				'name' => lang('Odds'),
                'formatter' => function ($d, $row) use($is_export, $i) {
                	$platform_id = (int) $row['game_platform_id'];
                	$odds_type = $row['odds_type'];
                	if ($platform_id == ONEWORKS_API || $platform_id == T1ONEWORKS_API || $platform_id == TFGAMING_ESPORTS_API || $platform_id ==SBTECH_BTI_API) {
                		if(!empty($odds_type)){
                			$api = $this->utils->loadExternalSystemLibObject($platform_id);
	                		$type = $api->getOddsType($odds_type);
	                		return $d . " " . $type;
                		}
                		return $d;
                	} else {
                		return $d;
                	}

                }
			),
            array(
                'alias' => 'unique_id',
                'select' => 'game_logs.external_uniqueid',
            ),
            array(
				'dt' => $this->utils->isEnabledFeature('enabled_show_rake') ? $i++ : NULL,
				'alias' => 'rent',
				'select' => 'game_logs.rent',
				'name' => lang('Rake'),
				'formatter' => function ($d, $row) use($is_export, $i) {
                	$platform_id = (int) $row['game_platform_id'];
                	if (empty($d)) {
                		return 'N/A';
                	} else {
                		return $d;
                	}
                }
			),
            array(
                'dt' => $i++,
                'alias' => 'winloss_amount',
                'name' => lang('winloss_amount'),
                'formatter' => function ($d, $row) use($is_export,$mobile_winlost_column) {
                    //$mobile_winlost_column
                    $tWin = $row['win_amount'];
                    $tLost = $row['loss_amount'];
                    $value = $tWin-$tLost;
                    $csstag ='<span style="font-weight:bold;" class ="text-success">%s</span>';

                    if(!$is_export){
                    	if($mobile_winlost_column){
                    		if($value<0){
                    			$csstag = '<span style="font-weight:bold;" class ="text-danger">%s</span>';
                    		}
                    	}else{
                    		if($value>0){
                    			$csstag = '<span style="font-weight:bold;" class ="text-danger">%s</span>';
                    		}
                    	}
                    	return sprintf($csstag,$this->utils->formatCurrencyNoSym($value));
                    }else{
                    	return $this->utils->formatCurrencyNoSym($value);
                    }
                },
            ),
		); // EOF $columns1 = array(...

		$columns = array_merge($columns, $columns1);

		# END DEFINE COLUMNS #################################################################################################################################################

		$input = $this->data_tables->extra_search($request);

		// OGP-20746 workaround: accept game_date_from/_to as by_date_from/_to
		// if (!isset($input['by_date_from']) && !isset($input['by_date_to']) && isset($input['game_date_from']) && isset($input['game_date_to'])) {
		// 	$input['by_date_from'] = $input['game_date_from'];
		// 	$input['by_date_to'] = $input['game_date_to'];
		// }

		if($this->utils->getConfig('game_logs_report_date_range_restriction') || $this->utils->getConfig('player_game_history_date_range_restriction')) {

			// $this->utils->debug_log(__METHOD__, 'marker A', [ 'player_id' => $player_id ]);

			// -- Check if from and to exists, and Validate permission
			$this->load->library('permissions');

			if(!isset($input['by_date_from']) || !isset($input['by_date_to']) ||  trim($input['by_date_from']) == '' || trim($input['by_date_to']) == '' || (!$from_aff && !$is_export && !$this->permissions->checkAnyPermissions(['gamelogs', 'report_gamelogs'])))
			{
				// $this->utils->debug_log(__METHOD__, 'marker B', [ 'player_id' => $player_id ]);

				$result = $this->data_tables->empty_data($request);
				$result['header_data'] = $this->data_tables->get_columns($columns);
				$result['summary'] = array(array("real_total_bet"=>0,"total_bet"=>0,"total_result"=>0,"total_bet_result"=>0,"total_win"=>0,"total_loss"=>0,"total_ave_bet"=>0,"total_count_bet"=>0));
				$result['sub_summary'] = array();

				return $result;
			}


			// -- Check date range if within one day

			$date_diff = date_diff(date_create($input['by_date_to']), date_create($input['by_date_from']));

			$game_logs_report_date_range_restriction = isset($input['by_username']) && $this->utils->getConfig('game_logs_report_with_username_date_range_restriction') ? $this->utils->getConfig('game_logs_report_with_username_date_range_restriction') : $this->utils->getConfig('game_logs_report_date_range_restriction');

			$restriction = isset($input['is_player_game_history']) ? $this->utils->getConfig('player_game_history_date_range_restriction') - 1 : $game_logs_report_date_range_restriction - 1;

			if($date_diff->format('%a') >  $restriction){

				$result = $this->data_tables->empty_data($request);
				$result['header_data'] = $this->data_tables->get_columns($columns);
				$result['summary'] = array(array("real_total_bet"=>0,"total_bet"=>0,"total_result"=>0,"total_bet_result"=>0,"total_win"=>0,"total_loss"=>0,"total_ave_bet"=>0,"total_count_bet"=>0));
				$result['sub_summary'] = array();

				return $result;
			}
		}

		$use_index = true;
        $table = 'game_logs';
		if (array_key_exists("by_bet_type",$input)){
			$table = ($input['by_bet_type'] == Game_logs::IS_GAMELOGS) ? 'game_logs' : 'game_logs_unsettle as game_logs';
			if($input['by_bet_type'] == Game_logs::IS_GAMELOGS){
				$columns[] = array(
					'dt' => $i++,
					'alias' => 'status',
					'name' => lang('lang.status'),
					'select' => '"'.lang('Settled').'"',
				);
			} else {
				$columns[] = array(
					'dt' => $i++,
					'alias' => 'status',
					'select' => 'game_logs.status',
					'name' => lang('lang.status'),
					'formatter' => function ($d, $row) use($is_export, $i) {
						// if(!$is_export){
							switch ((int)$d) {
							    case Game_logs::STATUS_ACCEPTED:
							        return lang('Running');
							        break;
							    case Game_logs::STATUS_REJECTED:
							        return lang('Rejected');
							        break;
							    case Game_logs::STATUS_VOID:
							        return lang('Void');
							        break;
							    case Game_logs::STATUS_REFUND:
							        return lang('Refund');
							        break;
							    case Game_logs::STATUS_CANCELLED:
							        return lang('Cancel');
							        break;
							    default:
							        return lang('Waiting');
							}
						// } else {
						// 	return $d;
						// }
					},
				);
			}
		}
		if ($this->utils->isEnabledFeature('hide_free_spin_on_game_history')) {
			$columns[] = array(
				'dt' => $i++,
				'alias' => 'game_platform_id',
				'select' => 'game_logs.game_platform_id',
				'formatter' => function ($d, $row) use($is_export, $i){
					//10 = JOKER WILD
					//11 = American Blackjack
					//12 = European Blackjack
					if ($d == 797) {
						if($row['game_code'] == 10 || $row['game_code'] == 11 || $row['game_code'] == 12) {
							return $d = $d.$row['game_code'].$row['unique_id'];
						} else {
							return 'N/A';
						}
					} else {
						return 'N/A';
					}
				}
			);
		}

    	$joins = array(
    		'player' => 'player.playerId = game_logs.player_id',
    		'affiliates' => 'affiliates.affiliateId = player.affiliateId',
    		'game_description' => 'game_description.id = game_logs.game_description_id',
    		'game_type' => 'game_type.id = game_description.game_type_id',
    		'external_system' => 'game_logs.game_platform_id = external_system.id',
    		'playerdetails' => 'playerdetails.playerId = player.playerId',
    		'agency_agents' => 'player.agent_id = agency_agents.agent_id',
    		'vipsettingcashbackrule' => 'vipsettingcashbackrule.vipsettingcashbackruleId = player.levelId',
			'vipsetting' => 'vipsetting.vipSettingId = vipsettingcashbackrule.vipSettingId'
    	);

		$innerJoins=['player', 'game_description', 'external_system'];

		# START PROCESS SEARCH FORM #################################################################################################################################################
		$where = array();
		$values = array();

		$left_joins_to_use_on_summary = array();

		$input = $this->data_tables->extra_search($request);

		if( ! empty( $not_datatable ) ) $input = $request;

		$removeDeletedSql="";
        if(!$this->utils->isEnabledFeature('show_game_history_of_deleted_player')){
        	$removeDeletedSql=" and player.deleted_at IS NULL";
        }

		if (!isset($player_id)) {
			// $where[] = "player.playerId IS NOT NULL";
			if (isset($input['by_username'])) {
				$by_username=trim($input['by_username']);
				if(!empty($by_username)){
					if (@$input['by_username_match_mode'] == '1') {
						$by_username=$this->db->escape_like_str($by_username);
						$joins['player']='player.playerId = game_logs.player_id and player.username like "%'.$by_username.'%"'.$removeDeletedSql;
						// $where[] = "player.username LIKE ?";
						// $values[] = '%' . $input['by_username'] . '%';
					} else {
						$use_index = false;

						$by_username=$this->db->escape_str($by_username);
						$joins['player']='player.playerId = game_logs.player_id and player.username="'.$by_username.'"'.$removeDeletedSql;
						// $where[] = "player.username = ? ";
						// $values[] = $input['by_username'];
					}
				}
			}
		}else{
			if(!is_array($player_id) && $player_id>0){
				$use_index = false;
				$joins['player']='player.playerId = game_logs.player_id and game_logs.player_id='.intval($player_id).$removeDeletedSql;
			}else{
				$this->utils->error_log('wrong player id', $player_id);
			}
		}

		// $where[] = "player.playerId IS NOT NULL";

		// if (isset($input['group_level']) && isset($input['only_allowed_game'])) {
		// 	$joins['vipsetting_cashback_game'] = 'vipsetting_cashback_game.game_description_id = game_logs.game_description_id';
		// }

		if (isset($input['by_game_platform_id']) && ! empty( $input['by_game_platform_id'] )) {
			$data_arr = explode(",", $input['by_game_platform_id']);
			$where[] = "game_logs.game_platform_id IN (" . implode(',', array_fill(0, count($data_arr), '?')) . ")";
			//$values[] = $data_arr;
			$values = array_merge($values, $data_arr);
		}

		if (isset($input['by_game_flag'])) {
			$where[] = "game_logs.flag = ?";
			$values[] = $input['by_game_flag'];
		}

		if (isset($input['by_date_from'], $input['by_date_to']) || isset($input['dateRangeValueStart'], $input['dateRangeValueEnd'])) {

			if (isset($input['timezone'])) {

				// $hours= -( intval($input['timezone'])-8 );
				// $hours= 24 + intval($input['timezone'])-8;
				$default_timezone = $this->utils->getTimezoneOffset(new DateTime());

				$timezone = !empty($this->utils->getConfig('force_default_timezone_option')) ? $input['timezone'] : intval($input['timezone']);
				$hours = $default_timezone - $timezone;

				$where[] = $game_logs_date_column . ' >= ?';
				$where[] = $game_logs_date_column . ' <= ?';

				$date_from_str = $input['by_date_from'];// . ' ' . $hours . ' hours';
				$date_to_str = $input['by_date_to'];// . ' ' . $hours . ' hours';

				$by_date_from = new DateTime($date_from_str);
                $by_date_to = new DateTime($date_to_str);

                $this->utils->debug_log('default_timezone', $default_timezone, 'date_from_str', $date_from_str, 'date_to_str', $date_to_str, 'hours', $hours);

				if($hours!=0){
					if($hours>0){
						$hours='+'.$hours;
					}
	                $by_date_from->modify("".$hours." hours");
	                $by_date_to->modify("".$hours." hours");
				}

				$values[] = $this->utils->formatDateTimeForMysql($by_date_from);
				$values[] = $this->utils->formatDateTimeForMysql($by_date_to);

			}else{

				$where[] = $game_logs_date_column . " >= ? AND ".$game_logs_date_column." <= ?";
				$values[] = (isset($input['by_date_from'])) ? $input['by_date_from'] : $input['dateRangeValueStart'];
				$values[] = (isset($input['by_date_to'])) ? $input['by_date_to'] : $input['dateRangeValueEnd'];
		   }

		}

		// if (isset($player_id)) {
		// 	if(!is_array($player_id)){
		// 		$where[] = "game_logs.player_id = ?";
		// 		$values[] = $player_id;
		// 	}else{
		// 		$this->utils->error_log('wrong player id', $player_id);
		// 	}
		// }

		if (isset($input['by_no_affiliate']) && $input['by_no_affiliate'] == true) {
			$where[] = "player.affiliateId IS NULL";
		}

        if (isset($input['by_free_spin']) && $input['by_free_spin']) {
            $game_no_free_spin = $this->config->item('game_with_no_free_spin');
            if(!empty($game_no_free_spin)){
                $where[] = "game_logs.game_platform_id NOT IN (" . implode(',', $game_no_free_spin) . ")";
             }
            $where[] = "game_logs.flag = 1 AND (game_logs.trans_amount = 0 OR game_logs.trans_amount = '') AND (bet_amount = 0 OR bet_amount = '') AND result_amount != 0";
        }

		// if (isset($input['by_username'])) {
		// 	if (@$input['by_username_match_mode'] == '1') {
		// 		$where[] = "player.username LIKE ?";
		// 		$values[] = '%' . $input['by_username'] . '%';
		// 	} else {
		// 		$where[] = "player.username = ? ";
		// 		$values[] = $input['by_username'];
		// 	}
		// }
		if (isset($input['by_affiliate'])) {
			$where[] = "affiliates.username LIKE ?";
			$values[] = '%' . $input['by_affiliate'] . '%';
			array_push($left_joins_to_use_on_summary, 'affiliates');
		}

		if (isset($input['by_game_code'])) {
			$where[] = "game_description.game_code = ?";
			$values[] = $input['by_game_code'];
		}

		if (isset($input['game_description_id'])) {
			$where[] = "game_description.id = ?";
			$values[] = $input['game_description_id'];
		}

		if (isset($input['by_group_level'])) {
			$where[] = "player.levelId  = ?";
			$values[] = $input['by_group_level'];
		}
		/*
		/// if (isset($input['group_level']) && isset($input['only_allowed_game'])) {
		// 	$where[] = "vipsetting_cashback_game.vipsetting_cashbackrule_id  = ? ";
		// 	$values[] = $input['group_level'];
		// }

		// if (isset($input['group_level'])){ // && !isset($input['only_allowed_game'])) {
		// 	$where[] = "player.levelId  = ?";
		// 	$values[] = $input['group_level'];
		// }

		// $all_game_types = isset($input['all_game_types']) ? ($input['all_game_types'] == 'true' || $input['all_game_types'] == 'on') : false;
		// if (isset($input['game_type_id']) && !$all_game_types) {

		// 	if (is_array($input['game_type_id'])) {
		// 		if (isset($input['game_type_id_null'])) {
		// 			$where[] = "(game_type.id IN (" . implode(',', array_fill(0, count($input['game_type_id']), '?')) . ") OR game_type.id IS NULL)";
		// 		} else {
		// 			$where[] = "game_type.id IN (" . implode(',', array_fill(0, count($input['game_type_id']), '?')) . ")";
		// 		}
		// 		$values = array_merge($values, $input['game_type_id']);
		// 	} else {
		// 		if (isset($input['game_type_id_null'])) {
		// 			$where[] = "(game_type.id = ? OR game_type.id IS NULL)";
		// 		} else {
		// 			$where[] = "game_type.id = ?";
		// 		}
		// 		$values[] = $input['game_type_id'];
		// 	}
		// } else if (isset($input['game_type_id_null'])) {
		// 	$where[] = "game_type.id IS NULL";
		// }
        */
		if (!empty($input['game_type_id'])){
			$gtArr=$input['game_type_id'];
			$gameTypeIds=explode(',', $gtArr);
			if(!empty($gameTypeIds)){
				$where[] = "game_logs.game_type_id IN (" . implode(',', array_fill(0, count($gameTypeIds), '?')) . ")";
				$values = array_merge($values, $gameTypeIds);
			}
		}

		if (isset($input['round_no'])) {
			$where[] = "game_logs.table = ?";
			$values[] = $input['round_no'];
		}

		if (isset($input['by_amount_from'])) {
			$where[] = "game_logs.result_amount >= ?";
			$values[] = $input['by_amount_from'];
		}

		if (isset($input['by_amount_to'])) {
			$where[] = "game_logs.result_amount <= ?";
			$values[] = $input['by_amount_to'];
		}

		if (isset($input['by_bet_amount_from'])) {
			$where[] = "game_logs.bet_amount >= ?";
			$values[] = $input['by_bet_amount_from'];
		}

		if (isset($input['by_bet_amount_to'])) {
			$where[] = "game_logs.bet_amount <= ?";
			$values[] = $input['by_bet_amount_to'];
		}

		if (isset($input['agency_username'])) {
			$where[] = "agency_agents.agent_name = ?";
			$values[] = $input['agency_username'];
			array_push($left_joins_to_use_on_summary, 'agency_agents');
		}

		// to see test player game logs  on their user information page
        // if(empty($player_id)){
        // if(!$this->utils->isEnabledFeature('show_game_history_of_deleted_player')){
        // 	$where[] = "player.deleted_at IS NULL";
        // }


		# END PROCESS SEARCH FORM #################################################################################################################################################
        // $csv_filename=null;
		if($is_export){
            $this->data_tables->options['is_export']=true;
//			$this->data_tables->options['only_sql']=true;
            if(empty($csv_filename)){
                $csv_filename=$this->utils->create_csv_filename(__FUNCTION__);
            }
            $this->data_tables->options['csv_filename']=$csv_filename;
            $not_datatable = 1;
		}

		$group_by=[];
		$having=[];
		$distinct=false;
		$external_order=[];
		// $not_datatable='';
		$countOnlyField='game_logs.id';
		$userIndex=[];
        // $columns = $this->checkIfEnable(true,'show_sports_game_columns_in_game_logs', array('match_type','match_details','handicap','odds'), $columns);
        // $columns = $this->checkIfEnable(true,'close_aff_and_agent', array('affiliate_username'), $columns);
        //$columns = $this->checkIfEnable($this->utils->isEnabledFeature('show_bet_detail_on_game_logs'), array('betDetails'), $columns);
		if($use_index) {
			// $table = $table . ' use index(idx_end_at)';

			#Force to use index date base on selected date type
			if($input['by_date_type'] == GAME_LOGS::DATE_TYPES['settled']){
				$table = $table . ' use index(idx_end_at)';
			}

			if($input['by_date_type'] == GAME_LOGS::DATE_TYPES['bet']){
				$table = $table . ' use index(idx_bet_at)';
			}

			if($input['by_date_type'] == GAME_LOGS::DATE_TYPES['updated']){
				$table = $table . ' use index(idx_updated_at)';
			}
		}
		$result = $this->data_tables->get_data($request, $columns, $table, $where, $values, $joins,
			$group_by, $having, $distinct, $external_order, $not_datatable, $countOnlyField, $innerJoins, $userIndex);
		if($is_export){
		    //drop result if export
			return $csv_filename;
		}

		// -- remove unecessary joins from total / summary queries
		foreach ($joins as $join_key => &$join_value) {
			if(!in_array($join_key, $left_joins_to_use_on_summary) && !in_array($join_key, $innerJoins))
				unset($joins[$join_key]);
		}

		//for summary
		if(!$this->utils->isEnabledFeature('show_game_history_of_deleted_player')){
			$where[] = "player.deleted_at IS NULL";
		}

		$summary = $this->data_tables->summary($request, $table, $joins, 'SUM(if(game_logs.flag="1", trans_amount, 0 )) real_total_bet, SUM(bet_amount) total_bet, SUM(result_amount) total_result, SUM(bet_amount + result_amount) total_bet_result, SUM(win_amount) total_win, SUM(loss_amount) total_loss, SUM(IF(game_logs.flag = "1", bet_amount, 0))/ SUM(IF(game_logs.flag = 1, 1, 0)) total_ave_bet, SUM(IF(game_logs.flag = 1 && game_logs.real_betting_amount > 0, 1, 0)) total_count_bet', null, $columns, $where, $values);
		$result['summary_last_query'] = $this->data_tables->last_query;

		$sub_summary = $this->data_tables->summary($request, $table, $joins, 'external_system.system_code, SUM(bet_amount) total_bet, SUM(result_amount) total_result, SUM(bet_amount + result_amount) total_bet_result, SUM(win_amount) total_win, SUM(loss_amount) total_loss', 'external_system.system_code', $columns, $where, $values);
		$result['sub_summary_last_query'] = $this->data_tables->last_query;

		array_walk($summary[0], function (&$value) {
			$value = round(floatval($value), 2);
		});

		foreach ($sub_summary as &$sub_summary_row) {
			array_walk($sub_summary_row, function (&$value, $key) {
				if ($key != 'system_code') {
					$value = round(floatval($value), 2);
				}

			});
		}

		$result['summary'] = $summary;
		$result['sub_summary'] = $sub_summary;

		if( ! empty( $not_datatable ) ) return $result;

		return $result;

	}


	public function player_gamesHistory( $request, $player_id = null, $is_export = false, $not_datatable = '', $csv_filename=null ){
	// this function for player center game report

		$this->load->model(array('game_logs', 'player', 'game_provider_auth', 'game_type_model'));
		$readOnlyDB = $this->getReadOnlyDB();

		$this->load->library('data_tables', array("DB" => $readOnlyDB));
		# START DEFINE COLUMNS #################################################################################################################################################
		$show_bet_detail_on_game_logs = $this->utils->isEnabledFeature('show_bet_detail_on_game_logs');
        $mobile_winlost_column = $this->utils->isEnabledFeature('mobile_winlost_column');
		$i = 0;


		$columns = array(
			array(
				'alias' => 'id',
				'select' => 'game_logs.id',
			),
			array(
				'alias' => 'game_type',
				'select' => 'game_type.game_type',
			),
			array(
				'alias' => 'game_code',
				'select' => 'game_description.game_code',
			),
			array(
				'alias' => 'playerId',
				'select' => 'player.playerId',
			),
			array(
				'alias' => 'player_levelName',
				'select' => 'player.levelName'
			),
			array(
				'alias' => 'player_groupName',
				'select' => 'player.groupName'
			),
			array(
				'alias' => 'game_platform_id',
				'select' => 'game_logs.game_platform_id',
			),
			array(
				'dt' => $i++,
				'alias' => 'end_at',
				'select' => 'game_logs.end_at',
				'name' => lang('player.ug01'),
				'formatter' => 'dateTimeFormatter',
			),
			array(
				'dt' => $i++,
				'alias' => 'player_username',
				'select' => 'player.username',
				'name' => lang('Player Username'),
				'formatter' => function ($d, $row) use ($is_export) {

					if( ! $is_export ){

						return sprintf('<a target="_blank" href="/player_management/userInformation/%s">%s</a>', $row['playerId'], $d);

					}else{

						return $d;

					}

				},
			),
            array(
                'dt' => $i++,
                'alias' => 'realname',
                'select' => "CONCAT_WS(' ', playerdetails.firstName,playerdetails.lastName)",
                'formatter' => function ($d, $row) {
                    return trim($d);
                },
                'name' => lang('Real Name'),
            ),
			array(
				'dt' => $i++,
				'alias' => 'affiliate_username',
				'select' => 'affiliates.username',
				'name' => lang('Affiliate Username'),
				'formatter' => function ($d, $row) {
					if ($row['affiliate_username'] != '') {
						return sprintf('%s', $row['affiliate_username'], $d);
					} else {
						return 'NA';
					}
				},
			),
			array(
				'dt' => $i++,
				'alias' => 'player_level',
				'select' => 'player.levelId',
				'formatter' => function($d, $row){
					if($d != 'N/A') {
						return lang($row['player_groupName']).' - '.lang($row['player_levelName']);
					}
		  			return $d;

				},
				'name' => lang('Player Level'),

			),
			array(
				'dt' => $i++,
				'alias' => 'game',
				'select' => 'external_system.system_code',
				'name' => lang('cms.gameprovider'),
				'formatter' => 'defaultFormatter',
			),
			array(
				'dt' => $i++,
				'alias' => 'game_type_lang',
				'select' => 'game_type.game_type_lang',
				'name' => lang('cms.gametype'),
				'formatter' => function ($d, $row) {
					if ($row['flag'] == Game_logs::FLAG_TRANSACTION) {
						return lang('N/A');
					}

					return $this->data_tables->languageFormatter($d);
				},
			),
			array(
				'dt' => $i++,
				'alias' => 'game_name',
				'select' => 'game_description.game_name',
				'name' => lang('cms.gamename'),
				'formatter' => function ($d, $row) {
					if ($row['flag'] == Game_logs::FLAG_TRANSACTION) {
						return lang('N/A');
					}

					return $this->data_tables->languageFormatter($d);
				},
			),
			array(
				'dt' => $i++,
				'alias' => 'real_bet_amount',
				'select' => 'game_logs.trans_amount',
				'name' => lang('Real Bet'),
				'formatter' => function ($d, $row) {
					//only for game type
					if ($row['flag'] == Game_logs::FLAG_TRANSACTION) {
						return lang('N/A');
					} else {
						if ($d == 0) {
							return lang('N/A');
						// 	$d = $row['bet_amount'];
						}
						return $this->utils->formatCurrencyWithSpecificApisDecimal($d, $row['game_platform_id']);
					}
				},
				// 'formatter' => 'currencyFormatter',
			),
			array(
				'dt' => $i++,
				'alias' => 'bet_amount',
				'select' => 'game_logs.bet_amount',
				'name' => lang('Available Bet'),
				'formatter' => function ($d, $row) {
					if ($row['flag'] == Game_logs::FLAG_TRANSACTION) {
						return lang('N/A');
					} else {
						return $this->utils->formatCurrencyWithSpecificApisDecimal($d, $row['game_platform_id']);
					}
				},
				// 'formatter' => 'currencyFormatter',
			),
			array(
				'dt' => $i++,
				'alias' => 'result_amount',
				'select' => 'game_logs.result_amount',
				'name' => lang('mark.resultAmount'),
				'formatter' => function ($d, $row) use ($is_export) {
					if ($row['flag'] == Game_logs::FLAG_TRANSACTION) {
						return lang('N/A');
					} else {
						if(!$is_export){
							if($d <= 0){
							//lose->green
								return sprintf('<span style="font-weight:bold;" class ="text-success">%s</span>', $this->utils->formatCurrencyWithSpecificApisDecimal($d, $row['game_platform_id']));
							}else{
								// win->red
								return sprintf('<span style="font-weight:bold;" class ="text-danger">%s</span>', $this->utils->formatCurrencyWithSpecificApisDecimal($d, $row['game_platform_id']));
							}
						}else{
							return $this->utils->formatCurrencyWithSpecificApisDecimal($d, $row['game_platform_id']);
						}
					}
				},
				// 'formatter' => 'currencyFormatter',
			),
			array(
				'dt' => $i++,
				'alias' => 'bet_plus_result_amount',
				'select' => 'game_logs.bet_amount + game_logs.result_amount',
				'name' => lang('lang.bet.plus.result'),
				// 'formatter' => 'currencyFormatter',
				'formatter' => function ($d, $row) {
					if ($row['flag'] == Game_logs::FLAG_TRANSACTION) {
						return lang('N/A');
					} else {
						return $this->utils->formatCurrencyWithSpecificApisDecimal($d, $row['game_platform_id']);
					}
				},
			),
			array(
				'dt' => $i++,
				'alias' => 'win_amount',
				'select' => 'game_logs.win_amount',
				'name' => lang('Win Amount'),
				// 'formatter' => 'currencyFormatter',
				'formatter' => function ($d, $row) {
					if ($row['flag'] == Game_logs::FLAG_TRANSACTION) {
						return lang('N/A');
					} else {
						return $this->utils->formatCurrencyWithSpecificApisDecimal($d, $row['game_platform_id']);
					}
				},
			),
			array(
				'dt' => $i++,
				'alias' => 'loss_amount',
				'select' => 'game_logs.loss_amount',
				'name' => lang('Loss Amount'),
				// 'formatter' => 'currencyFormatter',
				'formatter' => function ($d, $row) {
					if ($row['flag'] == Game_logs::FLAG_TRANSACTION) {
						return lang('N/A');
					} else {
						return $this->utils->formatCurrencyWithSpecificApisDecimal($d, $row['game_platform_id']);
					}
				},
			),
			array(
				'dt' => $i++,
				'alias' => 'after_balance',
				'select' => 'game_logs.after_balance',
				'name' => lang('mark.afterBalance'),
				'formatter' => function($d, $row) use ($is_export){
					if ( $is_export ) {
						return $this->utils->formatCurrencyWithSpecificApisDecimal($d, $row['game_platform_id']);
					} else {
						return $d == 0 ? '<span class="text-muted">' . $this->utils->formatCurrencyWithSpecificApisDecimal($d, $row['game_platform_id']) . '</span>' : '<strong>' . $this->utils->formatCurrencyWithSpecificApisDecimal($d, $row['game_platform_id']) . '</strong>';
					}
				},
			),
			array(
				'dt' => $i++,
				'alias' => 'trans_amount',
				'select' => 'game_logs.trans_amount',
				'name' => lang('pay.transamount'),
				// 'formatter' => 'currencyFormatter',
				'formatter' => function ($d, $row) {
					//only for game type
					if ($row['flag'] != Game_logs::FLAG_TRANSACTION) {
						return lang('N/A');
					} else {
						return $this->utils->formatCurrencyNoSym($d);
					}
				},
			),
            array(
                'dt' => $i++,
                'alias' => 'roundno',
                'select' => 'game_logs.table',
                'name' => lang('Round No'),
                'formatter' => function ($d, $row) use ($show_bet_detail_on_game_logs, $is_export) {

                    if( ! $is_export ){

                        $str = '<p>' . $d . '</p>';
                        if ($show_bet_detail_on_game_logs) {
                            $show_bet_detail_on_game_logs = $str . '<ul class="list-inline">' .
                            '<li><a href="javascript:void(0)" onclick="betDetail(\'' . $d . '\')">' . lang('Bet Detail') . '</a></li>' .
                            '<li><a href="javascript:void(0)" onclick="betResult(\'' . $d . '\')">' . lang('Bet Result') . '</a></li>' .
                                '</ul>';
                        }
                        return $str;

                    }else{

                        return $d;

                    }

                },
            ),
            array(
                'dt' => $i++,
                'alias' => 'note',
                'select' => 'game_logs.note',
                'name' => lang('Note'),
                'formatter' => function ($d, $row) {
                    if(!empty($d)){
	            		if($row['flag'] == Game_logs::FLAG_GAME && $row['game_platform_id'] == JUMB_GAMING_API) {
								$bet_details = json_decode($d, true);
								if (!isset($bet_details['bet_details'])) {
									return empty($d) ? "N/A" : $d;
								}
								$bet_details = $bet_details['bet_details'];
								$bet_details = explode(",", $bet_details);
								$betDetailLink = '';
								foreach($bet_details as $key => $value) {
								    $betDetailLink .=  $value . "<br>";
								}
		            			return  "<div style='width:200px;'>" . $betDetailLink . "</div>";
	            		}
                        return $d;
                    }else{
                        return "N/A";
                    }
                },
            ),
            array(
                'dt' => $i++,
                'alias' => 'betDetails',
                'select' => 'game_logs.bet_details',
                'name' => lang('Bet Detail'),
                'formatter' => function ($d, $row) {
					$platform_id		= (int) $row['game_platform_id'];
					$external_uniqueid	= $row['unique_id'];
					$player_username	= $row['player_username'];
					$bet_type =  $row['bet_type'];
					$roundno = $row['roundno'];
					
					$api = $this->utils->loadExternalSystemLibObject($platform_id);
					$use_bet_detail_ui =  $api->use_bet_detail_ui;
					$show_player_center_bet_details_ui =  $api->show_player_center_bet_details_ui;


					if($row['flag'] == Game_logs::FLAG_GAME && $use_bet_detail_ui && $show_player_center_bet_details_ui){
						$result = $api->queryBetDetailLink($player_username, $external_uniqueid);

						$url = '';

						if((isset($result['success']) && $result['success'] = true) && (isset($result['url']) )){
							$url = $result['url'];
						}
						if(!empty($url)){
							return '<a href="'.$url.'" target="_blank" class="btn btn-info">'.lang('View Detail').'</a>';
						}
						return '';
					}


                	if ($this->utils->getConfig('show_player_center_bet_details')) {
                        if ($row['flag'] == Game_logs::FLAG_GAME && in_array($platform_id, $this->pt_seamless_game_apis)) {
                            // remove parent game platform id prefix in external unique id if T1 Game
                            switch($platform_id) {
                                case T1_PT_SEAMLESS_GAME_API:
                                    $external_uniqueid = str_replace(PT_SEAMLESS_GAME_API . '-', '', $external_uniqueid);
                                    break;
                                case T1_IDN_PT_SEAMLESS_GAME_API:
                                    $external_uniqueid = str_replace(IDN_PT_SEAMLESS_GAME_API . '-', '', $external_uniqueid);
                                    break;
                                case T1_IDN_SLOTS_PT_SEAMLESS_GAME_API:
                                    $external_uniqueid = str_replace(IDN_SLOTS_PT_SEAMLESS_GAME_API . '-', '', $external_uniqueid);
                                    break;
                                case T1_IDN_LIVE_PT_SEAMLESS_GAME_API:
                                    $external_uniqueid = str_replace(IDN_LIVE_PT_SEAMLESS_GAME_API . '-', '', $external_uniqueid);
                                    break;
                            }

                            $betDetailLink = $api->queryBetDetailLink($player_username, $external_uniqueid);

                            if (isset($betDetailLink['url']) && !empty($betDetailLink['url'])) {
                                return '<a href="' . $betDetailLink['url'] . '" target="_blank" class="btn btn-info">' . lang('Bet Detail') . '</a>';
                            }

                            return $this->getDefaultFormatForBetDetails($d);
                        }

                        if ($row['flag'] == Game_logs::FLAG_GAME && in_array($platform_id, $this->evolution_seamless_game_apis)) {
                            // remove parent game platform id prefix in external unique id if T1 Game
                            /* switch($platform_id) {
                                case T1_EVOLUTION_SEAMLESS_GAME_API:
                                    $external_uniqueid = str_replace(EVOLUTION_SEAMLESS_GAMING_API . '-', '', $external_uniqueid);
                                    break;
                                case T1_EVOLUTION_NETENT_SEAMLESS_GAMING_API:
                                    $external_uniqueid = str_replace(EVOLUTION_NETENT_SEAMLESS_GAMING_API . '-', '', $external_uniqueid);
                                    break;
                                case T1_EVOLUTION_NLC_SEAMLESS_GAMING_API:
                                    $external_uniqueid = str_replace(EVOLUTION_NLC_SEAMLESS_GAMING_API . '-', '', $external_uniqueid);
                                    break;
                                case T1_EVOLUTION_REDTIGER_SEAMLESS_GAMING_API:
                                    $external_uniqueid = str_replace(EVOLUTION_REDTIGER_SEAMLESS_GAMING_API . '-', '', $external_uniqueid);
                                    break;
                                case T1_EVOLUTION_BTG_SEAMLESS_GAMING_API:
                                    $external_uniqueid = str_replace(EVOLUTION_BTG_SEAMLESS_GAMING_API . '-', '', $external_uniqueid);
                                    break;
                                case T1_IDN_EVOLUTION_SEAMLESS_GAMING_API:
                                    $external_uniqueid = str_replace(IDN_EVOLUTION_SEAMLESS_GAMING_API . '-', '', $external_uniqueid);
                                    break;
                                case T1_IDN_EVOLUTION_NETENT_SEAMLESS_GAMING_API:
                                    $external_uniqueid = str_replace(IDN_EVOLUTION_NETENT_SEAMLESS_GAMING_API . '-', '', $external_uniqueid);
                                    break;
                                case T1_IDN_EVOLUTION_NLC_SEAMLESS_GAMING_API:
                                    $external_uniqueid = str_replace(IDN_EVOLUTION_NLC_SEAMLESS_GAMING_API . '-', '', $external_uniqueid);
                                    break;
                                case T1_IDN_EVOLUTION_REDTIGER_SEAMLESS_GAMING_API:
                                    $external_uniqueid = str_replace(IDN_EVOLUTION_REDTIGER_SEAMLESS_GAMING_API . '-', '', $external_uniqueid);
                                    break;
                                case T1_IDN_EVOLUTION_BTG_SEAMLESS_GAMING_API:
                                    $external_uniqueid = str_replace(IDN_EVOLUTION_BTG_SEAMLESS_GAMING_API . '-', '', $external_uniqueid);
                                    break;
                            } */

                            $betDetailLink = $api->queryBetDetailLink($player_username, $external_uniqueid, ['round_id' => $roundno]);

                            if (isset($betDetailLink['url']) && !empty($betDetailLink['url'])) {
                                return '<a href="' . $betDetailLink['url'] . '" target="_blank" class="btn btn-info">' . lang('Bet Detail') . '</a>';
                            }

                            return $this->getDefaultFormatForBetDetails($d);
                        }

                        if ($row['flag'] == Game_logs::FLAG_GAME && in_array($platform_id, $this->fa_seamless_game_apis)) {
                            $betDetail = $api->queryBetDetailLink($player_username, $external_uniqueid, ['round_id' => $roundno]);

                            if (!empty($betDetail['url'])) {
                                return '<a href="'. $betDetail['url'] .'" target="_blank" class="btn btn-link btn-xs">'.lang('View Json').'</a>';
                            }

                            return $this->getDefaultFormatForBetDetails($d);
                        }

                        // DEFAULT BET DETAIL FORMAT
                        if($row['flag'] == Game_logs::FLAG_GAME && in_array($platform_id, $this->game_apis_for_default_format_bet_details)) {
                            return $this->getDefaultFormatForBetDetails($d);
                        }

	                	if ($row['flag'] == Game_logs::FLAG_GAME && $platform_id == SBTECH_BTI_API) {
	                		$betDetails = json_decode($row['betDetails'], TRUE);
	                		if(isset($betDetails['sports_bet']) && !empty($betDetails['sports_bet'])){
	                			$string = "";
	                			foreach ($betDetails['sports_bet'] as $key => $value) {
	                				$string  .= $this->parseArrayBetDetails($value);
	                			}
	                			return $string;
	                		}
	                	}

	                	if ($row['flag'] == Game_logs::FLAG_GAME && $platform_id == CALETA_SEAMLESS_API) {
	            			$api = $this->utils->loadExternalSystemLibObject($platform_id);
	            			$transaction = substr($external_uniqueid,4);
	            			$betDetailLink = $api->queryBetDetailLink($player_username, $transaction, $roundno);
							if(isset($betDetailLink['url'])&&!empty($betDetailLink['url'])){
								return '<a href="'.$betDetailLink['url'].'" target="_blank" class="btn btn-info">'.lang('Bet Detail').'</a>';
							}
	            			return '';
	            		}

	                    if ($row['flag'] == Game_logs::FLAG_GAME && $platform_id == MG_QUICKFIRE_API) {

							$api = $this->utils->loadExternalSystemLibObject($platform_id);

							$temp = json_decode($row['betDetails'], TRUE);
							if ($temp && isset($temp['game_id'])) {
	                    		$betDetailLink = $api->queryBetDetailLink($row['player_username'], $temp['game_id']);
								if(isset($betDetailLink['url'])&&!empty($betDetailLink['url'])){
									return '<a href="'.$betDetailLink['url'].'" target="_blank" class="btn btn-info">'.lang('Bet Detail').'</a>';
								}
	                    		return '';
							}

						}
						if ($row['flag'] == Game_logs::FLAG_GAME && $platform_id == BG_GAME_API) {
							$language = $this->language_function->getCurrentLanguage();
	            			$extra = $row['game_code']."_".$language;
	            			return  '<a href="'.site_url('async/get_bet_detail_link_of_game_api/' . $row['game_platform_id'] . '/' . $player_username .'/'.$row['roundno'] .'/'. $extra).'" target="_blank" class="btn btn-info">'.lang('Bet Detail').'</a>';
	            		}
						$ion_seamless_apis = [
	            			IONGAMING_SEAMLESS_IDR1_GAME_API,
							IONGAMING_SEAMLESS_IDR2_GAME_API,
							IONGAMING_SEAMLESS_IDR3_GAME_API,
							IONGAMING_SEAMLESS_IDR4_GAME_API,
							IONGAMING_SEAMLESS_IDR5_GAME_API,
							IONGAMING_SEAMLESS_IDR6_GAME_API,
							IONGAMING_SEAMLESS_IDR7_GAME_API,
							IONGAMING_SEAMLESS_CNY1_GAME_API,
							IONGAMING_SEAMLESS_CNY2_GAME_API,
							IONGAMING_SEAMLESS_CNY3_GAME_API,
							IONGAMING_SEAMLESS_CNY4_GAME_API,
							IONGAMING_SEAMLESS_CNY5_GAME_API,
							IONGAMING_SEAMLESS_CNY6_GAME_API,
							IONGAMING_SEAMLESS_CNY7_GAME_API,
							IONGAMING_SEAMLESS_THB1_GAME_API,
							IONGAMING_SEAMLESS_THB2_GAME_API,
							IONGAMING_SEAMLESS_THB3_GAME_API,
							IONGAMING_SEAMLESS_THB4_GAME_API,
							IONGAMING_SEAMLESS_THB5_GAME_API,
							IONGAMING_SEAMLESS_THB6_GAME_API,
							IONGAMING_SEAMLESS_THB7_GAME_API,
							IONGAMING_SEAMLESS_MYR1_GAME_API,
							IONGAMING_SEAMLESS_MYR2_GAME_API,
							IONGAMING_SEAMLESS_MYR3_GAME_API,
							IONGAMING_SEAMLESS_MYR4_GAME_API,
							IONGAMING_SEAMLESS_MYR5_GAME_API,
							IONGAMING_SEAMLESS_MYR6_GAME_API,
							IONGAMING_SEAMLESS_MYR7_GAME_API
	            		];
	            		## FOR ION GAMING SEAMLESS BET DETAILS
	            		if ($row['flag'] == Game_logs::FLAG_GAME && in_array($platform_id,$ion_seamless_apis)) {
								$bet_details = json_decode($d, true);
	                			$betDetailLink = "";
	                			foreach ($bet_details as $key => $value) {
	                				$betDetailLink .=  "<label class='".$key."'>" . $key ."</label>" . ": " . $value . "<br>";
	                			}

	                			return  "<div style='width:200px;'>" . $betDetailLink . "</div>";
	            		}

						if ($row['flag'] == Game_logs::FLAG_GAME && $platform_id == KINGPOKER_GAME_API) {
							$bet_url = '/async/get_bet_detail_link_of_game_api/' . $platform_id . '/' . $player_username . '/' . $external_uniqueid . '/' . $roundno;
							return '<a href="'.$bet_url.'" target="_blank" class="btn btn-info" style="visibility:"visible";">'.lang('Bet Detail').'</a>';
						}

	                    if($row['flag'] == Game_logs::FLAG_GAME && $platform_id == KING_MAKER_GAMING_THB_B1_API || $row['flag'] == Game_logs::FLAG_GAME && $platform_id == KING_MAKER_GAMING_API){
	                        	$row['betDetails'] = stripslashes($row['betDetails']);
		            			// If there's a double quote on bet_details string
								if (substr($row['betDetails'], 0, 1) === '"' && substr($row['betDetails'], -1) === '"') {
									$row['betDetails'] = substr($row['betDetails'], 1, -1);
								}

	                            $betDetails = json_decode($row['betDetails'],true);
	                            $link = isset($betDetails['link']) ? $betDetails['link'] : '';

	                            return '<a href="'.$link.'" target="_blank" class="btn btn-info">'.lang('Bet Detail').'</a>';
	                    }

	                    // OGP-17960: MGPLUS_API, JOKER_API, DT_API
	                    if ($row['flag'] == Game_logs::FLAG_GAME && $platform_id == MGPLUS_API) {
	                    	$api = $this->utils->loadExternalSystemLibObject($platform_id);

	        				$betDetailLink = $api->queryBetDetailLink($row['player_username'], $row['roundno']);
	        				$lang_bet_detail = lang('Bet Detail');
							if(isset($betDetailLink['url'])&&!empty($betDetailLink['url'])){
								return "<a href='{$betDetailLink['url']}' target='_blank' class='btn btn-info'>{$lang_bet_detail}</a>";
							}
							return '';
	                    }

						if ($row['flag'] == Game_logs::FLAG_GAME && $platform_id == MGPLUS2_API) {
	                    	$api = $this->utils->loadExternalSystemLibObject($platform_id);

	        				$betDetailLink = $api->queryBetDetailLink($row['player_username'], $row['roundno']);
	        				$lang_bet_detail = lang('Bet Detail');
							if(isset($betDetailLink['url'])&&!empty($betDetailLink['url'])){
								return "<a href='{$betDetailLink['url']}' target='_blank' class='btn btn-info'>{$lang_bet_detail}</a>";
							}
							return '';
	                    }

	                    if ($row['flag'] == Game_logs::FLAG_GAME && $platform_id == JOKER_API) {

							$bet_url = "/async/get_bet_detail_link_of_game_api/{$platform_id}/{$player_username}/{$external_uniqueid}/{$bet_type}";
							$lang_bet_detail = lang('Bet Detail');
							return "<a href='{$bet_url}' target='_blank' class='btn btn-info' style='visibility:visible;'>{$lang_bet_detail}</a>";
						}

						if ($row['flag'] == Game_logs::FLAG_GAME && $platform_id == DT_API) {
							$external_uniqueid = urlencode($external_uniqueid);
							$bet_url = "/async/get_bet_detail_link_of_game_api/{$platform_id}/{$player_username}/{$external_uniqueid}";
							$lang_bet_detail = lang('Bet Detail');
							return "<a href='{$bet_url}' target='_blank' class='btn btn-info' style='visibility:visible;'>{$lang_bet_detail}</a>";
						}

						if ($row['flag'] == Game_logs::FLAG_GAME && $platform_id == PLAYSTAR_API) {
	            			$api = $this->utils->loadExternalSystemLibObject($platform_id);
	        				$betDetailLink = $api->queryBetDetailLink($row['playerId'], $row['roundno']);
							if(isset($betDetailLink['url'])&&!empty($betDetailLink['url'])){
								return '<a href="'.$betDetailLink['url'].'" target="_blank" class="btn btn-info">'.lang('Bet Detail').'</a>';
							}
							return '';
	            		}

						if ($row['flag'] == Game_logs::FLAG_GAME && $platform_id == PLAYSTAR_API) {
							$api = $this->utils->loadExternalSystemLibObject($platform_id);
							$betDetailLink = $api->queryBetDetailLink($row['playerId'], $row['roundno']);
							if(isset($betDetailLink['url'])&&!empty($betDetailLink['url'])){
								return '<a href="'.$betDetailLink['url'].'" target="_blank" class="btn btn-info">'.lang('Bet Detail').'</a>';
							}
							return '';
						}

						if($row['flag'] == Game_logs::FLAG_GAME && ($platform_id == SPORTSBOOK_FLASH_TECH_GAME_API || $platform_id == T1SPORTSBOOK_FLASH_TECH_GAME_API)) {
                            $ft_bet_details = (array)json_decode($d, true);
                            if(isset($ft_bet_details['home_team_name'])){
                                return $this->formatBetDetails(SPORTSBOOK_FLASH_TECH_GAME_API, $d);
                            }
                        }

						if($row['flag'] == Game_logs::FLAG_GAME && $platform_id == EVOLUTION_SEAMLESS_THB1_API) {
							$betDetailsArr = (array)json_decode($d, true);
							return $this->utils->formatResursiveJsonToHtmlListBetDetails($betDetailsArr, 'Result');
						}

						if (
							$platform_id == QUEEN_MAKER_GAME_API ||
							$platform_id == QUEEN_MAKER_REDTIGER_GAME_API ||
							$platform_id == ONEGAME_GAME_API
						) {

							$queen_maker_bet_details = (array)json_decode($d, true);
							$roundId = isset($queen_maker_bet_details['roundId']) ? $queen_maker_bet_details['roundId'] : null;
							$gameUsername = isset($queen_maker_bet_details['gameUsername']) ? $queen_maker_bet_details['gameUsername'] : null;
							$is_hidden = isset($queen_maker_bet_details['isBet']) ? $queen_maker_bet_details['isBet'] : false;
							$hidden = $is_hidden ? '' : 'hidden';

							$bet_url = '/async/get_bet_detail_link_of_queen_maker/'.$gameUsername."/".$roundId."/".$platform_id;
							if(!empty($bet_url)){
								return '<a href="'.$bet_url.'" target="_blank" class="btn btn-info" style="visibility:'.$hidden.';">'.lang('Bet Detail').'</a>';
							}
							return '';
						}

						if ($row['flag'] == Game_logs::FLAG_GAME && ($platform_id == KYCARD_API || $platform_id == T1KYCARD_API)) {
							$bet_url = '/async/get_bet_detail_link_of_game_api/' . $platform_id . '/' . $player_username . '/' . $external_uniqueid;
							return '<a href="'.$bet_url.'" target="_blank" class="btn btn-info" style="visibility:"visible";">'.lang('Bet Detail').'</a>';
						}

						if ($row['flag'] == Game_logs::FLAG_GAME && $platform_id == GMT_GAME_API) {
							$bet_url = '/async/get_bet_detail_link_of_game_api/' . $platform_id . '/' . $player_username . '/' . $roundno;
							return '<a href="'.$bet_url.'" target="_blank" class="btn btn-info" style="visibility:"visible";">'.lang('Bet Detail').'</a>';
						}

	            		if ($platform_id == ONEWORKS_API || $platform_id == T1ONEWORKS_API || $platform_id == ONEBOOK_THB_B1_API || $platform_id == IBC_ONEBOOK_API) {
	                			//check array
	                			$data = (array)json_decode($d, true);

	                			$betDetailLink = "";
	                			if (!empty($data) && is_array($data)) {
	                				if (array_key_exists("Created At",$data)){
		                				unset($data['Created At']);
		                			}
		                			if (array_key_exists("Last Sync Time",$data)){
		                				unset($data['Last Sync Time']);
		                			}
									if($bet_type == Game_logs::BET_TYPE_SINGLE_BET || $bet_type == Game_logs::BET_TYPE_OLD_SINGLE_BET) {

		                				if(!empty($data) && (is_array($data) || is_object($data))) {
		                					if(count($data) == 1) { //single new_format
		                						$datax = json_decode($data[0], true);

		                						if(!empty($datax) && (is_array($datax) || is_object($datax))) {
													if(!empty($datax["bet_details"])) {
													    $betDetailLink .= @$this->parseOneworksBetDetails($datax["bet_details"], 'Bet Details');
                                                    }

                                                    if(!empty($datax["match_details"])) {
                                                        //for match details
                                                        $betDetailLink .= "<br><br>";
                                                        $betDetailLink .= @$this->parseOneworksBetDetails($datax["match_details"], 'Match Details');
                                                    }
		                						}
		                					}else { //single old format
												foreach ($data as $key => $value) {
			                						$other_str = "";
			                						if (preg_match('/\blabel\b/',$value)){
			                							$betDetailLink .=  "<label>" . $value . "</label>" . "<br>";
			                						} else {
			                							if($key == "bet"){
				                							$highlighted_str = substr($value, 0, strrpos($value, " "));
				                							$other_str = str_replace($highlighted_str,"",$value);
				                							$value = $highlighted_str;
				                						}

				                						$betDetailLink .=  "<label class='".$key."'>" . $value . "</label>" . $other_str . "<br>";
				                					}
				                				}
				                			}
				                		}
		                			}else { //if parlay
		                				if(!empty($data) && (is_array($data) || is_object($data))) {
		                					if(count($data) == 1) { //new format - multibet
		                						$datax = json_decode($data[0], true);

			                					foreach ($data as $key => $parlayi) {
			                						//check old format of bet_details
				                					$datai = (array)json_decode($parlayi, true);

				                					//for new bet_details format
				                					if (array_key_exists("bet_details", $datai)) {
														if(!empty($datai) && (is_array($datai) || is_object($datai))) {

															//for bet details

															$betDetailLink .= @$this->parseOneworksBetDetails($datai['bet_details'], 'Bet Details');

															//for match details
															$betDetailLink .= "<br><br>";
															$betDetailLink .= @$this->parseOneworksBetDetails($datai["match_details"], 'Match Details');

														}

					                					$betDetailLink .= "<br>";
					                				}
					                			}
											}else {	// old format - multibet

		                						foreach ($data as $ki => $vi) {
		                							foreach($data[$ki] as $kil => $vil){
														$other_str = "";

					                					if (preg_match('/\blabel\b/',$vil)){
					                						$betDetailLink .=  "<label>" . $vil . "</label>" . "<br>";
					                					} else {
					                						if($kil == "bet"){
						                						$highlighted_str = substr($vil, 0, strrpos($vil, " "));
						                						$other_str = str_replace($highlighted_str,"",$vil);
						                						$vil = "<br>".$highlighted_str;
						                					}elseif($kil == "is_parlay"){
						                						continue;
						                					}
					                					}
					                					$betDetailLink .=  "<label class='".$kil."'>" . $vil . "</label>" . $other_str . "<br>";
					                				}

		                						}
		                					}
		                				}
	 								}

		                			if(trim($betDetailLink) == '')
		                				$betDetailLink = lang('N/A');

		                			return  "<div style='width:200px;'>" . $betDetailLink . "</div>";
	                			}
							}

	                    if (!empty($d)) {
	                        $data = json_decode($d, true);
	                       	$betDetailLink = "";

	                       	if (!empty($data) && is_array($data)) {
	                       		foreach ($data as $key => $value) {
	                       			if (!empty($value)) {
	                       				if (!empty($betDetailLink))
	                       					$betDetailLink .= ", ";
	                       				if(is_array($value)){
	                       					$value=formatDebugMessage($value);
	                       				}else{
	                       					$value=lang($value);
	                       				}
	                                    if($key == 'sports_bet'){
	                                        $key = 'Sports Bet';
	                                        $res =  json_decode($value, true);
	                                        $label = '';
	                                        foreach($res as $k => $v){
	                                            if(isset($v['yourBet'])) {
	                                                $live = $v['isLive'] == true ? 'Live!' : 'Not Live';
	                                                $htScore = $v['htScore'];
	                                                if(is_array($htScore) ){
	                                                    $scoreDet = '';
	                                                    foreach($htScore as $n => $score){
	                                                        $scoreDet .= $htScore[$n]['score'].' ';
	                                                    }
	                                                    $htScore = "(".$scoreDet.")";
	                                                }
	                                                $label .= '<p>'.$v['yourBet'].', '.$v['odd'].', '.$live.', '.$htScore;
	                                                $label .= (isset($v['eventName']) && isset($v['league']) ) ? ', '.$v['eventName'].', '.$v['league'] : '</p>';
	                                            }
	                                        }
	                                        $value = $label;
	                                    }

	                                    $betDetailLink .= lang($key) . " : " .$value;

	                                    if ($key == 'bet_details') {
	                                    	unset($betDetailLink);
	                                    	$details = json_decode($value, true);
	                                    	$label = '';

	                                    	# bet ids
	                                    	if (is_array($details) && !empty($details)) {
	                                    		foreach ($details as $details_id => $detail) {
	                                    			$bet_list = '';
	                                    			# bet list
	                                    			if (is_array($detail))  {

	                                    				foreach ($detail as $detail_key => $bets) {
	                                    					# list of not included in the display
	                                    					if (in_array($detail_key, array('odds', 'won_side', "win_amount"))) {
	                                    						continue;
	                                    					}
	                                    					$bet_list .=  lang($detail_key) . ":" . lang($bets) . ", ";
	                                    				}
	                                    			}

	                                    			$label .= "<div style='border-bottom:solid 0.1em #C0C0C0; margin-top:0.5em;'>" . lang('Bet ID') .": " . $details_id . "<br> (" . substr($bet_list, 0, -2) . ")</div>";
	                                    		}
	                                    	}
	                                    	$betDetailLink = $label;
	                                    }
	                       			}
	                       		}
								if (!empty($platform_id)) {
	//									if ($platform_id == MG_API || $platform_id == QT_API)
	//									{
	//										if (!empty($betDetailLink))
	//											$betDetailLink .= "<br>";
	//										$betDetailLink .= '<a href="'.site_url('marketing_management/queryBetDetail/' . $row['game_platform_id'] . '/' . $row['playerId']).'" target="_blank" class="btn btn-info">'.lang('Bet Detail').'</a>';
	//									}

									if($platform_id == MG_API) {
										$api = $this->utils->loadExternalSystemLibObject($platform_id);
										$gameProviderInfo = $this->game_provider_auth->getByPlayerIdGamePlatformId($row['playerId'], $platform_id);
										$getPlayerGameHistoryURL = $api->queryBetDetailLink($gameProviderInfo['login_name'], null, array('password'=> $gameProviderInfo['password']));
										if ($getPlayerGameHistoryURL && $getPlayerGameHistoryURL['success'] && isset($getPlayerGameHistoryURL['url']) && !empty($getPlayerGameHistoryURL['url'])) {
											$betDetailLink .= '<a href="'.$getPlayerGameHistoryURL['url'].'" target="_blank" class="btn btn-info">'.lang('Bet Detail').'</a>';
										}
									} elseif ($platform_id == QT_API) {
										if (!empty($betDetailLink))
											$betDetailLink .= "<br>";
										$betDetailLink .= '<a href="'.site_url('marketing_management/queryBetDetail/' . $row['game_platform_id'] . '/' . $row['playerId']).'" target="_blank" class="btn btn-info">'.lang('Bet Detail').'</a>';
									} elseif ($platform_id == TPG_API) {
										$language = $this->language_function->getCurrentLanguage();
										if (!empty($betDetailLink))
											$betDetailLink = "";
											$betDetailLink .=  '<a href="'.site_url('async/get_bet_detail_link_of_game_api/' . $row['game_platform_id'] . '/' . $player_username .'/'.$row['roundno'].'/'. $language).'" target="_blank" class="btn btn-info">'.lang('Bet Detail').'</a>';
									}
								}
	                        }else{
	                            $betDetailLink = lang($d) ;
	                        }
		                    return $betDetailLink;
	                    }else{
	                        return "N/A";
	                    }
                	}else{
                        return "N/A";
                    }
                },
            ),
			array(
				'dt' => $i++,
				'alias' => 'flag',
				'select' => 'game_logs.flag',
				'name' => lang('player.ut10'),
                'formatter' => 'defaultFormatter'
			),
            array(
                'dt' => $i++,
                'alias' => 'bet_type',
                'select' => 'game_logs.bet_type',
                'name' => lang('Bet Type'),
                'formatter' => function ($d, $row) use($is_export, $i) {
                    if( ! $is_export && (strpos(strtolower($d), 'single') != 'single' && !empty($d))){
                        $unique_id = $row['unique_id'];
                        $bets = json_decode($row['betDetails'], true);

                        $count = 2;
                        $count = !empty($bets['sports_bet']) ? count($bets['sports_bet']):0;
                        $count = !empty($bets['bet_details']) ? count($bets['bet_details']) + $count: $count;
                        $h = ($count > 1) ? ($count * 33) + 155 : 188;
                        $link = "'/echoinfo/bet_details/$unique_id','Match Details','width=840,height=$h'";
                        return '<a href="javascript:void(window.open('.$link.'))">'.$d.'</a>';
                    }else{
                        return $d;
                    }
                },
            ),
            array(
                'dt' => $i++,
                'alias' => 'match_type',
                'select' => 'game_logs.match_type',
                'name' => lang('Match Type'),
                'formatter' => function ($d) use($is_export) {
                    return ($d == '0' || empty($d)) ? 'N/A' : 'Live';
                }
            ),
			array(
				'dt' => $i++,
				'alias' => 'match_details',
				'select' => 'game_logs.match_details',
				'name' => lang('Match Details'),
                'formatter' => 'defaultFormatter',
			),
            array(
                'dt' => $i++,
                'alias' => 'handicap',
                'select' => 'game_logs.handicap',
                'name' => lang('Handicap'),
                'formatter' => 'defaultFormatter',
            ),
			array(
				'dt' => $i++,
				'alias' => 'odds',
				'select' => 'game_logs.odds',
				'name' => lang('Odds'),
                'formatter' => 'defaultFormatter',
			),
            array(
                'alias' => 'unique_id',
                'select' => 'game_logs.external_uniqueid',
            ),
            array(
                'dt' => $i++,
                'alias' => 'winloss_amount',
                'name' => lang('winloss_amount'),
                'formatter' => function ($d, $row) use($is_export,$mobile_winlost_column) {
                    //$mobile_winlost_column
                    $tWin = $row['win_amount'];
                    $tLost = $row['loss_amount'];
                    $value = $tWin-$tLost;
                    $csstag ='<span style="font-weight:bold;" class ="text-success">%s</span>';
                    if($mobile_winlost_column){
                        if($value<0){
                            $csstag = '<span style="font-weight:bold;" class ="text-danger">%s</span>';
                        }
                    }else{
                        if($value>0){
                            $csstag = '<span style="font-weight:bold;" class ="text-danger">%s</span>';
                        }
                    }
                    return sprintf($csstag,$this->utils->formatCurrencyNoSym($value));

                },
            ),
			array(
				'dt' => $i++,
				'alias' => 'start_at',
				'select' => 'game_logs.start_at',
				'name' => lang('Bet Date'),
				'formatter' => 'dateTimeFormatter',
			),
			array(
				'dt' => $i++,
				'alias' => 'updated_at',
				'select' => 'game_logs.updated_at',
				'name' => lang('Updated Date'),
				'formatter' => 'dateTimeFormatter',
			),
			array(
				'dt' => $i++,
                'alias' => 'external_uniqueid',
                'select' => 'game_logs.external_uniqueid',
				'name' => lang('External Uniqued ID'),
            ),
            array(
                'dt' => $is_export ? ($i++) : ($i-1),
                'alias' => 'status',
                'select' => 'game_logs.status',
                'name' => lang('lang.status'),
                'formatter' => function ($d, $row) use($is_export, $i) {
                        switch ((int)$d) {
                            case Game_logs::STATUS_SETTLED:
                                return lang('Settled');
                                break;
                            case Game_logs::STATUS_PENDING:
                                return lang('Pending');
                                break;
                            case Game_logs::STATUS_ACCEPTED:
                                return lang('Running');
                                break;
                            case Game_logs::STATUS_REJECTED:
                                return lang('Rejected');
                                break;
                            case Game_logs::STATUS_CANCELLED:
                                return lang('Cancel');
                                break;
                            case Game_logs::STATUS_VOID:
                                return lang('Void');
                                break;
                            case Game_logs::STATUS_REFUND:
                                return lang('Refund');
                                break;
                            case Game_logs::STATUS_SETTLED_NO_PAYOUT:
                                return lang('Settled no payout');
                                break;
                            default:
                                return lang('Waiting');
                        }
                },
            ),
		);

		# END DEFINE COLUMNS #################################################################################################################################################
		$use_index = true;
		$table = 'game_logs';
		$joins = array(
			'player' => 'player.playerId = game_logs.player_id',
			'affiliates' => 'affiliates.affiliateId = player.affiliateId',
			'game_description' => 'game_description.id = game_logs.game_description_id',
			'game_type' => 'game_type.id = game_description.game_type_id',
			'external_system' => 'game_logs.game_platform_id = external_system.id',
            'playerdetails' => 'playerdetails.playerId = player.playerId'
		);

		$left_joins_to_use_on_summary = array();

		# START PROCESS SEARCH FORM #################################################################################################################################################
		$where = array();
		$values = array();

		$input = $this->data_tables->extra_search($request);

		if ($this->utils->getConfig('player_game_history_date_range_restriction'))
		{


			if(!isset($input['dateRangeValueStart']) || !isset($input['dateRangeValueEnd']) ||  trim($input['dateRangeValueStart']) == '' || trim($input['dateRangeValueEnd']) == '')
			{

				$result = $this->data_tables->empty_data($request);
				$result['header_data'] = $this->data_tables->get_columns($columns);
				$result['summary'] = array(array("real_total_bet"=>0,"total_bet"=>0,"total_result"=>0,"total_bet_result"=>0,"total_win"=>0,"total_loss"=>0,"total_ave_bet"=>0,"total_count_bet"=>0));
				$result['sub_summary'] = array();

				return $result;
			}

		}

		if( ! empty( $not_datatable ) ) $input = $request;

		$where[] = "player.playerId IS NOT NULL";

		if( ! empty($this->utils->getConfig('filter_unknow_game_type_in_player_site') ) ){
			$_select = 'id';
			$_where = "game_type_code = 'unknown' ";
			$_getGameTypeByQuery = $_getGameTypeByQuery = $this->game_type_model->getGameTypeByQuery($_select, $_where);
			$_unknow_game_type_id_list = array_column($_getGameTypeByQuery, 'id');
			$imploded_unknow_game_type_id_list = implode(', ',$_unknow_game_type_id_list);
			$where[] = " game_description.game_type_id NOT IN ($imploded_unknow_game_type_id_list)";
		}
		if( ! empty($this->utils->getConfig('filter_unknow_game_name_in_player_site') ) ){
			$where[] = " game_description.game_code <> 'unknown' ";
			$where[] = " game_description.game_name NOT LIKE '%:\"Unknown\"%' ";
		}

		if (isset($input['by_game_platform_id']) && ! empty( $input['by_game_platform_id'] )) {
			$data_arr = explode(",", $input['by_game_platform_id']);
			$where[] = "game_logs.game_platform_id IN (" . implode(',', array_fill(0, count($data_arr), '?')) . ")";
			//$values[] = $data_arr;
			$values = array_merge($values, $data_arr);
		}

		if (isset($input['by_game_flag'])) {
			$where[] = "game_logs.flag = ?";
			$values[] = $input['by_game_flag'];
		}

		if (isset($input['by_date_from'], $input['by_date_to']) || isset($input['dateRangeValueStart'], $input['dateRangeValueEnd'])) {

			if (isset($input['timezone'])) {

				$hours= -( intval($input['timezone'])-8 );
				// $hours= 24 + intval($input['timezone'])-8;

				$where[] ='game_logs.end_at >= ?';
				$where[] ='game_logs.end_at <= ?';

				$date_from_str = $input['by_date_from'];// . ' ' . $hours . ' hours';
				$date_to_str = $input['by_date_to'];// . ' ' . $hours . ' hours';

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

			}else{

				$where[] = "game_logs.end_at >= ? AND game_logs.end_at <= ?";
				$values[] = (isset($input['by_date_from'])) ? $input['by_date_from'] : $input['dateRangeValueStart'];
				$values[] = (isset($input['by_date_to'])) ? $input['by_date_to'] : $input['dateRangeValueEnd'];
		   }

		}

		$removeDeletedSql="";
        if(!$this->utils->isEnabledFeature('show_game_history_of_deleted_player')){
        	$removeDeletedSql=" and player.deleted_at IS NULL";
        // 	$where[] = "player.deleted_at IS NULL";
        }

		if (isset($player_id)) {
			if(!is_array($player_id) && $player_id>0){
				$use_index = false;
				$joins['player']='player.playerId = game_logs.player_id and game_logs.player_id='.intval($player_id).$removeDeletedSql;
			}else{
				$this->utils->error_log('wrong player id', $player_id);
			}
			// if(!is_array($player_id)){
			// 	$where[] = "player.playerId = ?";
			// 	$values[] = $player_id;
			// }else{
			// 	$this->utils->error_log('wrong player id', $player_id);
			// }
		}

		if (isset($input['by_no_affiliate']) && $input['by_no_affiliate'] == true) {
			$where[] = "player.affiliateId IS NULL";
		}

		if (isset($input['by_username'])) {
			if (@$input['by_username_match_mode'] == '1') {
				$where[] = "player.username LIKE ?";
				$values[] = '%' . $input['by_username'] . '%';
			} else {
				$use_index = false;
				$where[] = "player.username = ? ";
				$values[] = $input['by_username'];
			}
		}
		if (isset($input['by_affiliate'])) {
			$where[] = "affiliates.username LIKE ?";
			$values[] = '%' . $input['by_affiliate'] . '%';
			array_push($left_joins_to_use_on_summary, 'affiliates');
		}

		if (isset($input['by_game_code'])) {
			$where[] = "game_description.game_code = ?";
			$values[] = $input['by_game_code'];
		}

		if (isset($input['game_description_id'])) {
			$where[] = "game_description.id = ?";
			$values[] = $input['game_description_id'];
		}

		if (isset($input['by_group_level'])) {
			$where[] = "player.levelId  = ?";
			$values[] = $input['by_group_level'];
		}

		if (!empty($input['game_type_id'])){
			$gtArr=$input['game_type_id'];
			$gameTypeIds=explode(',', $gtArr);
			if(!empty($gameTypeIds)){
				$where[] = "game_type.id IN (" . implode(',', array_fill(0, count($gameTypeIds), '?')) . ")";
				$values = array_merge($values, $gameTypeIds);
			}
		}

		if (isset($input['round_no'])) {
			$where[] = "game_logs.table = ?";
			$values[] = $input['round_no'];
		}

		if (isset($input['by_amount_from'])) {
			$where[] = "game_logs.result_amount >= ?";
			$values[] = $input['by_amount_from'];
		}

		if (isset($input['by_amount_to'])) {
			$where[] = "game_logs.result_amount <= ?";
			$values[] = $input['by_amount_to'];
		}

		if (isset($input['by_bet_amount_from'])) {
			$where[] = "game_logs.bet_amount >= ?";
			$values[] = $input['by_bet_amount_from'];
		}

		if (isset($input['by_bet_amount_to'])) {
			$where[] = "game_logs.bet_amount <= ?";
			$values[] = $input['by_bet_amount_to'];
		}

		# If specified to query unsettle logs, return only true unsettled
		if (array_key_exists("by_bet_type", $input) && $input['by_bet_type'] == Game_logs::IS_GAMELOGS_UNSETTLE){
			$table = 'game_logs_unsettle as game_logs'; # Query from unsettle table instead
			$where[] = "game_logs.status IN (".Game_logs::STATUS_PENDING.",".Game_logs::STATUS_ACCEPTED.",".Game_logs::STATUS_REFUND.")";


			if(isset($input['by_player_center_unsettled']) && $input['by_player_center_unsettled'] == true) { // need this so that it will not affect the sbe unsettled game logs

				if($this->utils->getConfig('account_history_unsettled_game_history_game_codes')) {
					$game_platform_game_code = $this->utils->getConfig('account_history_unsettled_game_history_game_codes');
					$game_count = 0;
					$game_code_where = "";
					foreach($game_platform_game_code as $game_platform => $game_codes){


						foreach($game_codes as $game_code) {
							if ($game_count == 0) {
								$game_code_where = "((game_logs.game_platform_id = '$game_platform' AND game_logs.game_code = '$game_code')";
							} else {
								$game_code_where .= " OR (game_logs.game_platform_id = '$game_platform' AND game_logs.game_code = '$game_code')";;
							}

							// $values[] = $game_platform;
							// $values[] = $game_code;

							$game_count++;
						}
					}

					if($game_code_where != "") {
						$game_code_where .= ")";
						$where[] = $game_code_where;
					}



					if (isset($input['game_code'])) {
						$where[] = "game_logs.game_code = ?";
						$values[] = $input['game_code'];
					}
				}

			}
		}

		// to see test player game logs  on their user information page
        if(empty($player_id)){
        	$where[] = "player.deleted_at IS NULL";
        }


		# END PROCESS SEARCH FORM #################################################################################################################################################
        // $csv_filename=null;
		if($is_export){
            $this->data_tables->options['is_export']=true;
//			$this->data_tables->options['only_sql']=true;
            if(empty($csv_filename)){
                $csv_filename=$this->utils->create_csv_filename(__FUNCTION__);
            }
            $this->data_tables->options['csv_filename']=$csv_filename;
		}

		$group_by=[];
		$having=[];
		$distinct=false;
		$external_order=[];
		// $not_datatable='';
		$countOnlyField='game_logs.id';
		$innerJoins=['player', 'game_description', 'external_system'];

		if ($use_index) {
            $table = $table . ' use index(idx_end_at)';
        }

		$result = $this->data_tables->get_data($request, $columns, $table, $where, $values, $joins,
			$group_by, $having, $distinct, $external_order, $not_datatable, $countOnlyField, $innerJoins);

		if($is_export){
		    //drop result if export
			return $csv_filename;
		}

		if( ! empty($this->data_tables->last_query) ){
			$result['list_last_query'] = $this->data_tables->last_query;
		}

		// -- remove unecessary joins from total / summary queries
		foreach ($joins as $join_key => &$join_value) {
			if(!in_array($join_key, $left_joins_to_use_on_summary) && !in_array($join_key, $innerJoins))
				unset($joins[$join_key]);
		}

		$summary = $this->data_tables->summary($request, $table, $joins, 'SUM(if(game_logs.flag="1", trans_amount, 0 )) real_total_bet, SUM(bet_amount) total_bet, SUM(result_amount) total_result, SUM(bet_amount + result_amount) total_bet_result, SUM(win_amount) total_win, SUM(loss_amount) total_loss, SUM(IF(game_logs.flag = "1", bet_amount, 0))/ SUM(IF(game_logs.flag = 1, 1, 0)) total_ave_bet, SUM(IF(game_logs.flag = 1 && game_logs.real_betting_amount > 0, 1, 0)) total_count_bet', null, $columns, $where, $values);
		if( ! empty($this->data_tables->last_query) ){
			$result['summary_last_query'] = $this->data_tables->last_query;
		}

		$sub_summary = $this->data_tables->summary($request, $table, $joins, 'external_system.system_code, SUM(bet_amount) total_bet, SUM(result_amount) total_result, SUM(bet_amount + result_amount) total_bet_result, SUM(win_amount) total_win, SUM(loss_amount) total_loss', 'external_system.system_code', $columns, $where, $values);

		if( ! empty($this->data_tables->last_query) ){
			$result['sub_summary_last_query'] = $this->data_tables->last_query;
		}

		array_walk($summary[0], function (&$value) {
			$value = round(floatval($value), 2);
		});

		foreach ($sub_summary as &$sub_summary_row) {
			array_walk($sub_summary_row, function (&$value, $key) {
				if ($key != 'system_code') {
					$value = round(floatval($value), 2);
				}

			});
		}

		$result['summary'] = $summary;
		$result['sub_summary'] = $sub_summary;

		if( ! empty( $not_datatable ) ) return $result;

		return $result;

	}

	public function gamesHistoryV2( $request, $player_id = null, $is_export = false, $not_datatable = '', $csv_filename=null ){

		$this->load->library('kingrich_library');
		$this->submit_game_records = FALSE;

		$this->load->model(array('game_logs','kingrich_api_logs'));
		$readOnlyDB = $this->getReadOnlyDB();

		$this->load->library('data_tables', array("DB" => $readOnlyDB));
		$input = $this->data_tables->extra_search($request);

		if (isset($input['trigger_data_api']) && $input['trigger_data_api'] == "true") {
			$this->submit_game_records = TRUE;
		}

		$player_tag_test_account = $this->utils->getConfig('player_tag_test_account');

		if ( ! is_array($player_tag_test_account)) {
			$player_tag_test_account = [$player_tag_test_account];
		}

		foreach ($player_tag_test_account as &$tag) {
			$tag = "'{$tag}'";
		}

		$sql = "SELECT DISTINCT p.playerId FROM playertag AS pt JOIN tag AS t ON t.tagId = pt.tagId JOIN player AS p ON p.playerId = pt.playerId WHERE p.deleted_at IS NULL AND t.tagName IN (" . implode(',', $player_tag_test_account) . ")";
		$query = $this->db->query($sql);
		$rows = $query->result_array();
		$test_players_id = array_column($rows, 'playerId');

		//var_dump($this->submit_game_records);die();
		# START DEFINE COLUMNS #################################################################################################################################################
		$show_bet_detail_on_game_logs = $this->utils->isEnabledFeature('show_bet_detail_on_game_logs');
        $mobile_winlost_column = $this->utils->isEnabledFeature('mobile_winlost_column');
		$i = 0;

		//use the currency settings.
		$currency_settings = $this->utils->getCurrentCurrency()['currency_code'];
		$brand_name = $this->utils->getConfig('brand_name');
		$brand_url = $this->utils->getConfig('brand_url');

		if($this->CI->utils->getConfig('enable_kingrich_gametypes_new')) {
			$kingrich_gametypes = $this->utils->getConfig('kingrich_gametypes');
		} else {
			$kingrich_gametypes = $this->utils->getConfig('kingrich_gametypes_old');
		}

		$kingrich_currency_branding = $this->config->item('kingrich_currency_branding');

		$columns = array(
			array(
				'alias' => 'id',
				'select' => 'game_logs.id',
			),
			array(
				'alias' => 'game_type',
				'select' => 'game_type.game_type',
			),
			array(
				'alias' => 'game_code',
				'select' => 'game_description.game_code',
			),
			array(
				'alias' => 'playerId',
				'select' => 'player.playerId',
			),
			array(
				'alias' => 'player_levelName',
				'select' => 'player.levelName'
			),
			array(
				'alias' => 'player_groupName',
				'select' => 'player.groupName'
			),
			array(
				'alias' => 'game_platform_id',
				'select' => 'game_logs.game_platform_id',
			),
			array(
				'alias' => 'player_level',
				'formatter' => function($d, $row){
					if($d != 'N/A') {
						return lang($row['player_groupName']).' - '.lang($row['player_levelName']);
					}
		  			return $d;

				},
				'name' => lang('Player Level'),

			),
			array(
				'alias' => 'game',
				'select' => 'external_system.system_code',
			),
			array(
				'alias' => 'bet_type',
				'select' => 'game_logs.bet_type',
			),
			array(
				'alias' => 'bet_amount',
				'select' => 'game_logs.bet_amount',
				'name' => lang('Available Bet'),
				'formatter' => function ($d, $row) {
					if ($row['flag'] == Game_logs::FLAG_TRANSACTION) {
						return lang('N/A');
					} else {
						return $this->utils->formatCurrencyNoSym($d);
					}
				},
				// 'formatter' => 'currencyFormatter',
			),
			array(
				'alias' => 'result_amount',
				'select' => 'game_logs.result_amount',
				'name' => lang('mark.resultAmount'),
				'formatter' => function ($d, $row) use ($is_export) {
					if ($row['flag'] == Game_logs::FLAG_TRANSACTION) {
						return lang('N/A');
					} else {
						if(!$is_export){
							if($d <= 0){
							//lose->green
								return sprintf('<span style="font-weight:bold;" class ="text-success">%s</span>',$this->utils->formatCurrencyNoSym($d));
							}else{
								// if free spin, make text color dark red for it to be readable
								if (empty($row['real_bet_amount']) && empty($row['bet_amount'])) {
									return sprintf('<span style="font-weight:bold;color:#8B0000">%s</span>',$this->utils->formatCurrencyNoSym($d));
								} else {
									// win->red
									return sprintf('<span style="font-weight:bold;" class ="text-danger">%s</span>',$this->utils->formatCurrencyNoSym($d));
								}
							}
						}else{
							return $this->utils->formatCurrencyNoSym($d);
						}
					}
				},
				// 'formatter' => 'currencyFormatter',
			),
			array(
				'alias' => 'bet_plus_result_amount',
				'select' => 'game_logs.bet_amount + game_logs.result_amount',
				'name' => lang('lang.bet.plus.result'),
				// 'formatter' => 'currencyFormatter',
				'formatter' => function ($d, $row) {
					if ($row['flag'] == Game_logs::FLAG_TRANSACTION) {
						return lang('N/A');
					} else {
						return $this->utils->formatCurrencyNoSym($d);
					}
				},
			),

			array(
				'alias' => 'after_balance',
				'select' => 'game_logs.after_balance',
				'name' => lang('mark.afterBalance'),
				'formatter' => function($d, $row) use ($is_export){
					if ( $is_export ) {
						return $this->utils->formatCurrencyNoSym($d);
					} else {
						return $d == 0 ? '<span class="text-muted">' . $this->utils->formatCurrencyNoSym($d) . '</span>' : '<strong>' . $this->utils->formatCurrencyNoSym($d) . '</strong>';
					}
				},
			),
			array(
				'alias' => 'trans_amount',
				'select' => 'game_logs.trans_amount',
				'name' => lang('pay.transamount'),
				// 'formatter' => 'currencyFormatter',
				'formatter' => function ($d, $row) {
					//only for game type
					if ($row['flag'] != Game_logs::FLAG_TRANSACTION) {
						return lang('N/A');
					} else {
						return $this->utils->formatCurrencyNoSym($d);
					}
				},
			),
            array(
                'alias' => 'game_type',
                'select' => 'game_logs.game_type',
                'name' => lang('game_type'),
            ),
            array(
                'alias' => 'note',
                'select' => 'game_logs.note',
                'name' => lang('Note'),
                'formatter' => function ($d, $row) {
                    if(!empty($d)){
	            		if($row['flag'] == Game_logs::FLAG_GAME && $row['game_platform_id'] == JUMB_GAMING_API) {
								$bet_details = json_decode($d, true);
								if (!isset($bet_details['bet_details'])) {
									return empty($d) ? "N/A" : $d;
								}
								$bet_details = $bet_details['bet_details'];
								$bet_details = explode(",", $bet_details);
								$betDetailLink = '';
								foreach($bet_details as $key => $value) {
								    $betDetailLink .=  $value . "<br>";
								}
		            			return  "<div style='width:200px;'>" . $betDetailLink . "</div>";
	            		}
                    	if($this->utils->isEnabledFeature('switch_ag_round_and_notes')){
                    		$platform_id = (int) $row['game_platform_id'];
	                    	if($platform_id == AGIN_API && $row['flag'] == 1 && $row['game_type']=='AGIN'){
	                    		return $row['roundno'];
	                    	}
                    	}
                        return $d;
                    }else{
                        return "N/A";
                    }
                },
            ),
            array(
                'alias' => 'betDetails',
                'select' => 'game_logs.bet_details',
                'name' => lang('Bet Detail'),
                'formatter' => function ($d, $row) use ($is_export) {


                	if(!$is_export){
                		$platform_id = (int) $row['game_platform_id'];

                		if ($row['flag'] == Game_logs::FLAG_GAME && $platform_id == MG_QUICKFIRE_API) {

                			$api = $this->utils->loadExternalSystemLibObject($platform_id);

                			$temp = json_decode($row['betDetails'], TRUE);
                			if ($temp && isset($temp['game_id'])) {
                				$betDetailLink = $api->queryBetDetailLink($row['player_username'], $temp['game_id']);
								if(isset($betDetailLink['url'])&&!empty($betDetailLink['url'])){
									return '<a href="'.$betDetailLink['url'].'" target="_blank" class="btn btn-info">'.lang('Bet Detail').'</a>';
								}
                				return '';
                			}

                		}

                		if (!empty($d)) {
                			$data = json_decode($d, true);
                			$betDetailLink = "";

                			if (!empty($data) && is_array($data)) {
                				foreach ($data as $key => $value) {
                					if (!empty($value)) {
                						if (!empty($betDetailLink))
                							$betDetailLink .= ", ";
                						if(is_array($value)){
                							$value=formatDebugMessage($value);
                						}else{
                							$value=lang($value);
                						}
                						if($key == 'sports_bet'){
                							$key = 'Sports Bet';
                							$res =  json_decode($value, true);
                							$label = '';
                							foreach($res as $k => $v){
                								if(isset($v['yourBet'])) {
                									$live = $v['isLive'] == true ? 'Live!' : 'Not Live';
                									$htScore = $v['htScore'];
                									if(is_array($htScore) ){
                										$scoreDet = '';
                										foreach($htScore as $n => $score){
                											$scoreDet .= $htScore[$n]['score'].' ';
                										}
                										$htScore = "(".$scoreDet.")";
                									}
                									$label .= '<p>'.$v['yourBet'].', '.$v['odd'].', '.$live.', '.$htScore;
                									$label .= (isset($v['eventName']) && isset($v['league']) ) ? ', '.$v['eventName'].', '.$v['league'] : '</p>';
                								}
                							}
                							$value = $label;
                						}

                						$betDetailLink .= lang($key) . " : " .$value;

                						if ($key == 'bet_details') {
                							unset($betDetailLink);
                							$details = json_decode($value, true);
                							$label = '';

                                    	# bet ids
                							if (is_array($details) && !empty($details)) {
                								foreach ($details as $details_id => $detail) {
                									$bet_list = '';
                                    			# bet list
                									if (is_array($detail))  {

                										foreach ($detail as $detail_key => $bets) {
                                    					# list of not included in the display
                											if (in_array($detail_key, array('odds', 'won_side', "win_amount"))) {
                												continue;
                											}
                											$bet_list .=  lang($detail_key) . ":" . lang($bets) . ", ";
                										}
                									}

                									$label .= "<div style='border-bottom:solid 0.1em #C0C0C0; margin-top:0.5em;'>" . lang('Bet ID') .": " . $details_id . "<br> (" . substr($bet_list, 0, -2) . ")</div>";
                								}
                							}
                							$betDetailLink = $label;
                						}
                					}
                				}
                				if (!empty($platform_id)) {
                					if ($platform_id == MG_API || $platform_id == QT_API)
                					{
                						if (!empty($betDetailLink))
                							$betDetailLink .= "<br>";
                						$betDetailLink .= '<a href="'.site_url('marketing_management/queryBetDetail/' . $row['game_platform_id'] . '/' . $row['playerId']).'" target="_blank" class="btn btn-info">'.lang('Bet Detail').'</a>';
                					}
                				}
                			}else{
                				$betDetailLink = lang($d) ;
                			}
                			return $betDetailLink;
                		}else{
                			return "N/A";
                		}
                	}else{
                		//if is_export hide it
                		return "N/A";
                	}


                },//
            ),
			array(
				'alias' => 'flag',
				'select' => 'game_logs.flag',
				'name' => lang('player.ut10'),
                'formatter' => 'defaultFormatter'
			),
            array(
                'alias' => 'bet_type',
                'select' => 'game_logs.bet_type',
                'name' => lang('Bet Type'),
                'formatter' => function ($d, $row) use($is_export, $i) {
                    if( ! $is_export && (strpos(strtolower($d), 'single') != 'single' && !empty($d))){
                        $unique_id = $row['unique_id'];
                        $bets = json_decode($row['betDetails'], true);

                        $count = 2;
                        $count = !empty($bets['sports_bet']) ? count($bets['sports_bet']):0;
                        $count = !empty($bets['bet_details']) ? count($bets['bet_details']) + $count: $count;
                        $h = ($count > 1) ? ($count * 33) + 155 : 188;
                        $link = "'/echoinfo/bet_details/$unique_id','Match Details','width=840,height=$h'";
                        return '<a href="javascript:void(window.open('.$link.'))">'.$d.'</a>';
                    }else{
                        return $d;
                    }
                },
            ),
            array(
                'alias' => 'match_type',
                'select' => 'game_logs.match_type',
                'name' => lang('Match Type'),
                'formatter' => function ($d) use($is_export) {
                    return ($d == '0' || empty($d)) ? 'N/A' : 'Live';
                }
            ),
			array(
				'alias' => 'match_details',
				'select' => 'game_logs.match_details',
				'name' => lang('Match Details'),
                'formatter' => 'defaultFormatter',
			),
            array(
                'alias' => 'handicap',
                'select' => 'game_logs.handicap',
                'name' => lang('Handicap'),
                'formatter' => 'defaultFormatter',
            ),
			array(
				'alias' => 'odds',
				'select' => 'game_logs.odds',
				'name' => lang('Odds'),
                'formatter' => 'defaultFormatter',
			),
            array(
                'alias' => 'unique_id',
                'select' => 'game_logs.external_uniqueid',
            ),
			//Player Username
			array(
				'dt' => $col_player_name = $i++,
				'alias' => 'player_username',
				'select' => $this->utils->isEnabledFeature('display_aff_beside_playername_gamelogs') ? '( CASE WHEN affiliates.username IS NULL THEN player.username ELSE CONCAT(player.username, \' (\', affiliates.username,  \')\' ) END )' : 'player.username',
				'name' => lang('Player Username'),
				'formatter' => function ($d, $row) use ($is_export) {
					if ($is_export || $this->submit_game_records) {
						return $d;
					} else {
						return sprintf('<a target="_blank" href="/player_management/userInformation/%s">%s</a>', $row['playerId'], $d);
					}
				},
			),
			//Player No or ID
			array(
				'dt' => $col_player_no = $i++,
				'alias' => 'playerId',
				'select' => 'player.playerId',
				'name' => lang('Player No.'),
			),
			//Player Type
			array(
				'dt' => $col_player_type = $i++,
				'alias' => 'player_type',
				'name' => lang('Player Type'),
				'formatter' => function ($d, $row) use ($test_players_id) {
					if ( ! empty($test_players_id) && in_array($row['playerId'], $test_players_id)) {
						return Kingrich_library::PLAYER_TYPE_TEST;
					} else {
						return Kingrich_library::PLAYER_TYPE_REAL;
					}
				},
			),
			//Date and Time of Transactions
			array(
				'dt' => $col_transaction_date = $i++,
				'alias' => 'end_at',
				'select' => 'game_logs.end_at',
				'name' => lang('Date and Time of Transactions'),
				'formatter' => $this->submit_game_records ? function($d) {
					return date('Y-m-d\TH:i:s.000', strtotime($d));
				} : 'dateTimeFormatter',
			),
			//Transaction ID / Round No.
			array(
                'dt' => $col_transaction_id = $i++,
                'alias' => 'roundno',
                'select' => 'game_logs.external_uniqueid',
                'name' => lang('Transaction ID'),
                'formatter' => function ($d, $row) use ($is_export, $show_bet_detail_on_game_logs) {
                    if ($is_export || $this->submit_game_records) {
                        return $d;
                    } else {
                    	if ($this->utils->isEnabledFeature('switch_ag_round_and_notes')) {
                    		$platform_id = (int) $row['game_platform_id'];
	                    	if ($platform_id == AGIN_API && $row['flag'] == 1 && $row['game_type']=='AGIN') {
	                    		return $row['note'];
	                    	}
                    	}
                        $str = '<p>' . $d . '</p>';
                        if ($show_bet_detail_on_game_logs) {
                            $show_bet_detail_on_game_logs = $str . '<ul class="list-inline">' .
                            '<li><a href="javascript:void(0)" onclick="betDetail(\'' . $d . '\')">' . lang('Bet Detail') . '</a></li>' .
                            '<li><a href="javascript:void(0)" onclick="betResult(\'' . $d . '\')">' . lang('Bet Result') . '</a></li>' .
							'</ul>';
                        }
                        return $str;
                    }
                },
            ),
            //Settlement Date
            array(
				'dt' => $col_settlement_date = $i++,
				'alias' => 'settlement_date',
				'select' => 'game_logs.end_at',
				'name' => lang('Settlement Date'),
				'formatter' => $this->submit_game_records ? function($d) {
					return date('Y-m-d\TH:i:s.000', strtotime($d));
				} : 'dateTimeFormatter',
			),
			//Brand
			array(
				'dt' => $col_brand = $i++,
				'alias' => 'brand',
				'name' => lang('Brand'),
				'formatter' => function ($d, $row) use ($brand_name, $kingrich_currency_branding) {
					if( !empty($kingrich_currency_branding) ) {
						if ( isset($kingrich_currency_branding[$row['currency']]) ) {
							if( isset($kingrich_currency_branding[$row['currency']]['brand_name']) ){
								return $kingrich_currency_branding[$row['currency']]['brand_name'];
							}
						}
					}
					return $this->data_tables->defaultFormatter($brand_name);
				},
			),
			//URL
			array(
				'dt' => $col_brand_url = $i++,
				'alias' => 'url',
				'name' => lang('sys.api01'),
				'formatter' => function ($d, $row) use ($brand_url, $kingrich_currency_branding) {
					if( !empty($kingrich_currency_branding) ) {
						if ( isset($kingrich_currency_branding[$row['currency']]) ) {
							if( isset($kingrich_currency_branding[$row['currency']]['brand_url']) ){
								return $kingrich_currency_branding[$row['currency']]['brand_url'];
							}
						}
					}
					return $this->data_tables->defaultFormatter($brand_url);
				},
			),
			array(
				'dt' => $col_game_provider = $i++,
				'alias' => 'game',
				'select' => 'external_system.system_code',
				'name' => lang('cms.gameprovider'),
				'formatter' => 'defaultFormatter',
			),
			//Game Code
			array(
				'dt' => $col_game_code = $i++,
				'alias' => 'game_code_v2',
				'select' => "game_description.game_code",
				'name' => lang('sys.gd9'),
                'formatter' => 'defaultFormatter',
			),
			//Game Name
			array(
				'dt' => $col_game_name = $i++,
				'alias' => 'game_name',
				'select' => 'game_description.game_name',
				'name' => lang('sys.gd8'),
				'formatter' => function ($d, $row) {
					return $row['flag'] == Game_logs::FLAG_GAME ? $this->data_tables->languageFormatter($d) : lang('N/A');
				},
			),
			//Game Type
			array(
				'dt' => $col_game_type = $i++,
				/*'alias' => $this->submit_game_records ? 'game_tag_id' : 'game_type_lang',
				'select' => $this->submit_game_records ? 'game_type.game_tag_id' : 'game_type.game_type_lang',*/
				'alias' => 'game_tag_id',
				'select' => 'game_type.game_tag_id',
				'name' => lang('sys.gd6'),
				'formatter' => function ($d, $row) {
					/*if ($this->submit_game_records) return $this->kingrich_library->get_game_type($d);
					return $row['flag'] == Game_logs::FLAG_GAME ? $this->data_tables->languageFormatter($d) : lang('N/A');*/
					return ( $this->kingrich_library->get_game_type($d,$row['game_platform_id']) ) ? : lang('N/A');
				},
			),
			//Currency
			array(
				'dt' => $col_currency_code = $i++,
				'alias' => 'currency',
				'select' => 'player.currency',
				'name' => lang('Currency'),
				'formatter' => function ($d, $row) use ($is_export, $currency_settings) {
					if(!empty($d)){
						return $this->data_tables->defaultFormatter($d);
					}
					return $this->data_tables->defaultFormatter($currency_settings);
				},
			),
			//Bet Type
			array(
				'dt' => $col_bet_type = $i++,
				'alias' => 'bet_type2',
				'name' => lang('Bet type'),
				'formatter' => function ($d, $row) use ($is_export) {
					if($this->kingrich_library->get_game_type($row['game_tag_id'],$row['game_platform_id']) == Kingrich_library::GAME_TYPE_SPORTSBOOK){
						if ($row['real_bet_amount'] > 0 ) {
							return lang("Cash");
						} else if ($row['real_bet_amount'] <= 0) {
							return lang("Credit");
						} else {
							return lang("Credit");
						}
					} else {
						return lang('N/A');
					}
				},
			),
			//Bet Amount / Real Bet
			array(
				'dt' => $col_bet_amount = $i++,
				'alias' => 'real_bet_amount',
				'select' => 'game_logs.trans_amount',
				'name' => lang('Real Bet'),
				'formatter' => function ($d, $row) {
					if ($this->submit_game_records) return floatval($d);
					return $row['flag'] == Game_logs::FLAG_GAME && $d != 0 ? $this->data_tables->currencyFormatter($d) : $this->data_tables->currencyFormatter(0);
				},
			),
			//Debit - (Player Loss) / Loss Amount
			// = bet_amount
			array(
				'dt' => $col_debit_amount = $i++,
				'alias' => 'loss_amount',
				//'select' => 'game_logs.loss_amount',
				'select' => 'game_logs.trans_amount',
				'name' => lang('Loss Amount'),
				'formatter' => function ($d, $row) {
					if ($this->submit_game_records) return floatval($d);
					return $row['flag'] == Game_logs::FLAG_GAME ? $this->data_tables->currencyFormatter($d) : lang('N/A');
				},
			),
			//Credit + (Player Win) / Win Amount
			// = bet_amount + win_amount
			array(
				'alias' => 'win_amount',
				'select' => 'game_logs.win_amount',
				'name' => lang('Win Amount'),
				'formatter' => function ($d, $row) {
					return $this->data_tables->currencyFormatter($d);
				},
			),
			array(
				'dt' => $col_credit_amount = $i++,
				'alias' => 'credit_amount',
				//'select' => 'game_logs.win_amount + game_logs.trans_amount',
				'select' => 'IF(game_logs.win_amount > 0, game_logs.win_amount + game_logs.trans_amount, 0)',
				'name' => lang('Win Amount'),
				'formatter' => function ($d, $row) {
					if ($this->submit_game_records) return floatval($d);
					return $row['flag'] == Game_logs::FLAG_GAME ? $this->data_tables->currencyFormatter($d) : lang('N/A');
				},
			),
			//Net Amount / Win/Loss
			// = bet_amount - credit_amount
			array(
                'dt' => $col_net_amount = $i++,
                'alias' => 'winloss_amount',
                //'select' => 'game_logs.loss_amount - (game_logs.win_amount + game_logs.trans_amount)',
                'select' => 'game_logs.trans_amount - ( IF(game_logs.win_amount > 0, game_logs.win_amount + game_logs.trans_amount, 0))',
                'name' => lang('net_amount'),
                'formatter' => function ($d, $row) use ($is_export, $mobile_winlost_column) {

                    $value = $d;

					if ($this->submit_game_records) return floatval($value);

                    if ( ! $is_export) {

                    	$csstag ='<span style="font-weight:bold;" class ="text-success">%s</span>';

                    	if ($mobile_winlost_column) {
                    		if ($value < 0) {
                    			$csstag = '<span style="font-weight:bold;" class ="text-danger">%s</span>';
                    		}
                    	} else {
                    		if ($value > 0) {
                    			$csstag = '<span style="font-weight:bold;" class ="text-danger">%s</span>';
                    		}
                    	}

                    	return sprintf($csstag, $this->utils->formatCurrencyNoSym($value));
                    } else {
                    	return $this->data_tables->currencyFormatter($value);
                    }

                },
            ),
			//OLD
			/*array(
                'dt' => $col_net_amount = $i++,
                'alias' => 'winloss_amount',
                'name' => lang('winloss_amount'),
                'formatter' => function ($d, $row) use ($is_export, $mobile_winlost_column) {

                    $tWin = $row['win_amount'];
                    $tLost = $row['loss_amount'];
                    $value = $tWin - $tLost;

					if ($this->submit_game_records) return floatval($value);

                    if ( ! $is_export) {

                    	$csstag ='<span style="font-weight:bold;" class ="text-success">%s</span>';

                    	if ($mobile_winlost_column) {
                    		if ($value < 0) {
                    			$csstag = '<span style="font-weight:bold;" class ="text-danger">%s</span>';
                    		}
                    	} else {
                    		if ($value > 0) {
                    			$csstag = '<span style="font-weight:bold;" class ="text-danger">%s</span>';
                    		}
                    	}

                    	return sprintf($csstag, $this->utils->formatCurrencyNoSym($value));
                    } else {
                    	return $this->data_tables->currencyFormatter($value);
                    }

                },
            ),*/
		);

		# END DEFINE COLUMNS #################################################################################################################################################
		//$input = $this->data_tables->extra_search($request);
		$table = 'game_logs use index(idx_end_at)';
		/*if (array_key_exists("by_bet_type",$input)){
			$table = ($input['by_bet_type']) ? 'game_logs' : 'game_logs_unsettle as game_logs';
		}*/

		$joins = array(
			'player' => 'player.playerId = game_logs.player_id',
			'affiliates' => 'affiliates.affiliateId = player.affiliateId',
			'game_description' => 'game_description.id = game_logs.game_description_id',
			'game_type' => 'game_type.id = game_description.game_type_id',
			'external_system' => 'game_logs.game_platform_id = external_system.id',
            'playerdetails' => 'playerdetails.playerId = player.playerId',
            'agency_agents' => 'player.agent_id = agency_agents.agent_id'
		);

		$left_joins_to_use_on_summary = array('external_system');

		# START PROCESS SEARCH FORM #################################################################################################################################################
		$where = array();
		$values = array();



		if( ! empty( $not_datatable ) ) $input = $request;

		$where[] = "player.playerId IS NOT NULL";
		//$where[] = "game_logs.external_uniqueid IS NOT NULL";
		if (isset($input['by_player_type']) && ! empty( $input['by_player_type'] )) {
			if($input['by_player_type'] == Kingrich_library::PLAYER_TYPE_TEST){
				if ( ! empty($test_players_id)) {
					$where[] = "player.playerId IN (" . implode(',', $test_players_id) . ")";
				}
			} else if($input['by_player_type'] == Kingrich_library::PLAYER_TYPE_REAL) {
				if ( ! empty($test_players_id)) {
					$where[] = "player.playerId NOT IN (" . implode(',', $test_players_id) . ")";
				}
			}

			array_push($left_joins_to_use_on_summary, 'player');

		}


		//remove gametype not listed in kingrich
		$game_tag_included = [];
		$game_tag_provider = [];
		if(!empty($kingrich_gametypes)){
			if(!$this->CI->utils->getConfig('enable_kingrich_gametypes_new')){
				foreach ($kingrich_gametypes as $key => $value) {
					if(isset($value['tag_id'])){
						$game_tag_included = array_merge($game_tag_included, $value['tag_id']);
					}
				}

				if (isset($input['by_game_type_globalcom'])) {
					foreach ($kingrich_gametypes as $key => $value) {
						if($input['by_game_type_globalcom'] == $key && $input['by_game_type_globalcom'] != Kingrich_library::ALL_GAME_TYPE){
							$game_tag_included = [];
							if(isset($value['tag_id'])){
								$game_tag_included = array_merge($game_tag_included, $value['tag_id']);
							}
						}
					}
				}
			} else {
				if (isset($input['by_game_type_globalcom']) && $input['by_game_type_globalcom'] != Kingrich_library::ALL_GAME_TYPE ) {
					foreach ($kingrich_gametypes as $key => $value) {
						if( $input['by_game_type_globalcom'] == $key ){
							if(isset($value['tag_id'])) {
								foreach ($value['tag_id'] as $tag_key => $tag_value) {
									if( !empty($tag_value) ) {
										$game_tag_included = array_merge($game_tag_included, $tag_value);
										if(!in_array($tag_key, $game_tag_provider, true)){
									        array_push($game_tag_provider, $tag_key);
									    }
									}
								}
							}
						}
					}
				} else {
					foreach ($kingrich_gametypes as $key => $value) {
						if(isset($value['tag_id'])){
							foreach ($value['tag_id'] as $tag_key => $tag_value) {
								if( !empty($tag_value) ){
									$game_tag_included = array_merge($game_tag_included, $tag_value);
									if(!in_array($tag_key, $game_tag_provider, true)){
								        array_push($game_tag_provider, $tag_key);
								    }
								}

							}
						}
					}
				}
			}
		}

		if(!empty($game_tag_included)) {
			$where[] = "game_type.game_tag_id IN (" . implode(',', array_fill(0, count($game_tag_included), '?')) . ")";
			$values = array_merge($values, $game_tag_included);

			array_push($left_joins_to_use_on_summary, 'game_type');
			array_push($left_joins_to_use_on_summary, 'game_description');
		}

		if(!empty($game_tag_provider)) {
			$where[] = "game_logs.game_platform_id IN (" . implode(',', array_fill(0, count($game_tag_provider), '?')) . ")";
			$values = array_merge($values, $game_tag_provider);
		}

		if (isset($input['by_game_platform_id']) && ! empty( $input['by_game_platform_id'] )) {
			$data_arr = explode(",", $input['by_game_platform_id']);
			$where[] = "game_logs.game_platform_id IN (" . implode(',', array_fill(0, count($data_arr), '?')) . ")";
			//$values[] = $data_arr;
			$values = array_merge($values, $data_arr);
		}


		if (isset($input['by_kingrich_currency_branding']) && ! empty( $input['by_kingrich_currency_branding'] )) {
			$where[] = "player.currency = ?";
			$values[] = $input['by_kingrich_currency_branding'];
			array_push($left_joins_to_use_on_summary, 'player');
		}

		/*if (isset($input['by_game_flag'])) {
			$where[] = "game_logs.flag = ?";
			$values[] = $input['by_game_flag'];
		}*/

		if (isset($input['by_date_from'], $input['by_date_to']) || isset($input['dateRangeValueStart'], $input['dateRangeValueEnd'])) {

			if (isset($input['timezone'])) {

				$hours= -( intval($input['timezone'])-8 );
				// $hours= 24 + intval($input['timezone'])-8;

				$where[] ='game_logs.end_at >= ?';
				$where[] ='game_logs.end_at <= ?';

				$date_from_str = $input['by_date_from'];// . ' ' . $hours . ' hours';
				$date_to_str = $input['by_date_to'];// . ' ' . $hours . ' hours';

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

			}else{

				$where[] = "game_logs.end_at >= ? AND game_logs.end_at <= ?";
				$values[] = (isset($input['by_date_from'])) ? $input['by_date_from'] : $input['dateRangeValueStart'];
				$values[] = (isset($input['by_date_to'])) ? $input['by_date_to'] : $input['dateRangeValueEnd'];
		   }

		}

		if (isset($player_id)) {
			if(!is_array($player_id)){
				$where[] = "player.playerId = ?";
				$values[] = $player_id;
			}else{
				$this->utils->error_log('wrong player id', $player_id);
			}
			array_push($left_joins_to_use_on_summary, 'player');
		}

		if (isset($input['by_no_affiliate']) && $input['by_no_affiliate'] == true) {
			$where[] = "player.affiliateId IS NULL";
			array_push($left_joins_to_use_on_summary, 'player');
		}

        if (isset($input['by_free_spin']) && $input['by_free_spin']) {
            $game_no_free_spin = $this->config->item('game_with_no_free_spin');
            if(!empty($game_no_free_spin)){
                $where[] = "game_logs.game_platform_id NOT IN (" . implode(',', $game_no_free_spin) . ")";
            }
            $where[] = "game_logs.flag = 1 AND (game_logs.trans_amount = 0 OR game_logs.trans_amount = '') AND (bet_amount = 0 OR bet_amount = '') AND result_amount != 0";
        }

		if (isset($input['by_username'])) {
			if (@$input['by_username_match_mode'] == '1') {
				$where[] = "player.username LIKE ?";
				$values[] = '%' . $input['by_username'] . '%';
			} else {
				$where[] = "player.username = ? ";
				$values[] = $input['by_username'];
			}

			array_push($left_joins_to_use_on_summary, 'player');
		}
		/*if (isset($input['by_affiliate'])) {
			$where[] = "affiliates.username LIKE ?";
			$values[] = '%' . $input['by_affiliate'] . '%';
		}*/

		if (isset($input['by_game_code'])) {
			$where[] = "game_description.game_code = ?";
			$values[] = $input['by_game_code'];
			array_push($left_joins_to_use_on_summary, 'game_description');
		}

		if (isset($input['game_description_id'])) {
			$where[] = "game_description.id = ?";
			$values[] = $input['game_description_id'];
			array_push($left_joins_to_use_on_summary, 'game_description');
		}

		if (isset($input['round_no'])) {
			$where[] = "game_logs.external_uniqueid = ?";
			$values[] = $input['round_no'];
		}

		if (isset($input['by_amount_from'])) {
			$where[] = "game_logs.result_amount >= ?";
			$values[] = $input['by_amount_from'];
		}

		if (isset($input['by_amount_to'])) {
			$where[] = "game_logs.result_amount <= ?";
			$values[] = $input['by_amount_to'];
		}

		if (isset($input['by_bet_amount_from'])) {
			$where[] = "game_logs.bet_amount >= ?";
			$values[] = $input['by_bet_amount_from'];
		}

		if (isset($input['by_bet_amount_to'])) {
			$where[] = "game_logs.bet_amount <= ?";
			$values[] = $input['by_bet_amount_to'];
		}

		if (isset($input['by_debit_amount_from'])) {
			$where[] = "game_logs.trans_amount >= ?";
			$values[] = $input['by_debit_amount_from'];
		}

		if (isset($input['by_debit_amount_to'])) {
			$where[] = "game_logs.trans_amount <= ?";
			$values[] = $input['by_debit_amount_to'];
		}

		if (isset($input['by_credit_amount_from'])) {
			$where[] = "(game_logs.win_amount + game_logs.trans_amount) >= ?";
			$values[] = $input['by_credit_amount_from'];
		}

		if (isset($input['by_credit_amount_to'])) {
			$where[] = "(game_logs.win_amount + game_logs.trans_amount) <= ?";
			$values[] = $input['by_credit_amount_to'];
		}

		if (isset($input['agency_username'])) {
			$where[] = "agency_agents.agent_name = ?";
			$values[] = $input['agency_username'];
			array_push($left_joins_to_use_on_summary, 'agency_agents');
		}

		if (isset($input['by_bet_type'])) {
			if(!empty($input['by_bet_type'])){
				if($input['by_bet_type'] == "cash"){
					$where[] = "game_logs.trans_amount > ?";
					$values[] = $input['by_bet_type'];
				} else if($input['by_bet_type'] == "credit"){
					$where[] = "game_logs.trans_amount <= ?";
					$values[] = $input['by_bet_type'];
				}
			}
		}


		if (isset($input['submitted_status'])) {
			if($input['submitted_status'] == 'submitted' && !$this->submit_game_records){
				$where[] = "game_logs.room IS NOT NULL";
			} else if($input['submitted_status'] == 'not_submitted'){
				$where[] = "game_logs.room IS NULL";
			}
		}

		if (isset($input['for_data_api'])){
			if ($input['for_data_api'] == "true") {
				$where[] = "game_logs.room IS NULL";
			}
		}

		if (isset($input['batch_transaction_id_filter'])) {
			$where[] = "game_logs.room = ?";
			$values[] = $input['batch_transaction_id_filter'];
		}

		// to see test player game logs  on their user information page
        // if(empty($player_id)){
        if(!$this->utils->isEnabledFeature('show_game_history_of_deleted_player')){
        	$where[] = "player.deleted_at IS NULL";
			array_push($left_joins_to_use_on_summary, 'player');
        }


		# END PROCESS SEARCH FORM #################################################################################################################################################
        // $csv_filename=null;
		if($is_export){
            $this->data_tables->options['is_export']=true;
//			$this->data_tables->options['only_sql']=true;
            if(empty($csv_filename)){
                $csv_filename=$this->utils->create_csv_filename(__FUNCTION__);
            }
            $this->data_tables->options['csv_filename']=$csv_filename;
            $not_datatable = 1;
		}

		$group_by=[];
		$having=[];
		$distinct=false;
		$external_order=[];
		// $not_datatable='';
		$countOnlyField='game_logs.id';

        // $columns = $this->checkIfEnable(true,'show_sports_game_columns_in_game_logs', array('match_type','match_details','handicap','odds'), $columns);
        // $columns = $this->checkIfEnable(true,'close_aff_and_agent', array('affiliate_username'), $columns);
        // $columns = $this->checkIfEnable($this->utils->isEnabledFeature('show_bet_detail_on_game_logs'), array('betDetails'), $columns);

		$where[] = "game_logs.flag = ?";
		$values[] = Game_logs::FLAG_GAME;

		if ($this->submit_game_records) {

			$result = array();
			$request['start'] = 0;
			$request['length'] = Kingrich_library::LIMIT;

			do {

				$data = $this->data_tables->get_data($request, $columns, $table, $where, $values, $joins, $group_by, $having, $distinct, $external_order, $not_datatable, $countOnlyField);

				$rows = $data['data'];
				$rows_count = count($rows);

				if ( ! empty($rows)) {

					$game_transactions = array_map( function($row) use ($col_player_name ,$col_player_no ,$col_player_type ,$col_transaction_date ,$col_transaction_id ,$col_settlement_date ,$col_brand ,$col_brand_url ,$col_game_code ,$col_game_name ,$col_game_type ,$col_currency_code ,$col_bet_type ,$col_bet_amount ,$col_debit_amount ,$col_credit_amount ,$col_net_amount) {

						return array(
							'player_name' 		=> $row[$col_player_name],
							'player_no' 		=> $row[$col_player_no],
							'player_type' 		=> $row[$col_player_type],
							'transaction_date' 	=> $row[$col_transaction_date],
							'transaction_id' 	=> $row[$col_transaction_id],
							'settlement_date' 	=> $row[$col_settlement_date],
							'brand' 			=> $row[$col_brand],
							'brand_url' 		=> $row[$col_brand_url],
							'game_code' 		=> $row[$col_game_code],
							'game_name' 		=> lang($row[$col_game_name],1),
							'game_type' 		=> $row[$col_game_type],
							'currency_code' 	=> $row[$col_currency_code],
							'bet_type' 			=> $row[$col_bet_type],
							'bet_amount' 		=> $row[$col_bet_amount],
							'debit_amount' 		=> $row[$col_debit_amount],
							'credit_amount' 	=> $row[$col_credit_amount],
							'net_amount' 		=> $row[$col_net_amount],
						);

					}, $rows);

					// $game_transactions = array_filter($game_transactions, function($game_transaction) {
					// 	return $game_transaction['player_type'] == Kingrich_library::PLAYER_TYPE_REAL;
					// });

					$submit_result = $this->kingrich_library->submit_game_records($game_transactions);

					$this->utils->debug_log('kingrich submit_result', $submit_result);

					if(!empty($submit_result)){
						if(isset($submit_result['batch_transaction_id'])){
							$submit_result_data = array(
								'batch_transaction_id' => $submit_result['batch_transaction_id'],
								'api_created_date' => $submit_result['created_date'],
								'created_at' => $this->utils->getNowForMysql(),
								'status' => $submit_result['status'],
							);

							$game_logs_update = array();
							foreach ($game_transactions as $key => $value) {
								array_push($game_logs_update,
									array(
										'external_uniqueid' => $value['transaction_id'],
										'room'  => $submit_result['batch_transaction_id'],
									)
								);
							}

							$this->db->update_batch("game_logs", $game_logs_update, 'external_uniqueid');

							$this->kingrich_api_logs->insertRecord($submit_result_data);
						}
					}


					$result[] = [
						'start' 			=> $request['start'],
						'length' 			=> $request['length'],
						'rows'				=> $game_transactions,
						'result'			=> $submit_result,
						'recordsFiltered' 	=> $rows_count,
						'recordsTotal' 		=> $data['recordsTotal'],
					];
				}

				$request['start'] += $rows_count;

			} while ($request['start'] < $data['recordsTotal']);

			return $result;
		}

		$result = $this->data_tables->get_data($request, $columns, $table, $where, $values, $joins, $group_by, $having,	 $distinct, $external_order, $not_datatable, $countOnlyField);

		if($is_export){
		    //drop result if export
			return $csv_filename;
		}

		// -- remove unecessary joins from total / summary queries
		foreach ($joins as $join_key => &$join_value) {
			if(!in_array($join_key, $left_joins_to_use_on_summary))
				unset($joins[$join_key]);
		}

		$summary = $this->data_tables->summary($request, $table, $joins, 'SUM(if(game_logs.flag="1", trans_amount, 0 )) real_total_bet, SUM(bet_amount) total_bet, SUM(result_amount) total_result, SUM(bet_amount + result_amount) total_bet_result, SUM(IF(game_logs.win_amount > 0, game_logs.win_amount + game_logs.trans_amount, 0)) total_credit_win, SUM(game_logs.trans_amount) total_debit_loss, SUM(game_logs.trans_amount - ( IF(game_logs.win_amount > 0, game_logs.win_amount + game_logs.trans_amount, 0))) net_credit_debit, SUM(win_amount) total_win, SUM(loss_amount) total_loss, SUM(win_amount + loss_amount) net_win_loss, SUM(IF(game_logs.flag = "1", bet_amount, 0))/ SUM(IF(game_logs.flag = 1, 1, 0)) total_ave_bet, SUM(IF(game_logs.flag = 1 && game_logs.real_betting_amount > 0, 1, 0)) total_count_bet', null, $columns, $where, $values);

		$sub_summary = $this->data_tables->summary($request, $table, $joins, 'external_system.system_code, SUM(bet_amount) total_bet, SUM(result_amount) total_result, SUM(bet_amount + result_amount) total_bet_result, SUM(win_amount) total_win, SUM(loss_amount) total_loss', 'external_system.system_code', $columns, $where, $values);


		array_walk($summary[0], function (&$value) {
			$value = round(floatval($value), 2);
		});

		foreach ($sub_summary as &$sub_summary_row) {
			array_walk($sub_summary_row, function (&$value, $key) {
				if ($key != 'system_code') {
					$value = round(floatval($value), 2);
				}

			});
		}

		$result['summary'] = $summary;
		$result['sub_summary'] = $sub_summary;

		if( ! empty( $not_datatable ) ) return $result;

		return $result;

	}

	/**
	 * detail: get Kingrich api response logs
	 *
	 * @param string transaction_batch_id
	 * @param datetime create_date
	 * @return json
	 */
	public function kingrichApiResponseLogs($request, $player_id = null, $is_export = false, $not_datatable = '', $csv_filename=null) {

        $this->load->library('data_tables');
		$i = 0;
		$columns = array(
			array(
				'dt' => $i++,
				'alias' => 'batch_transaction_id',
				'select' => 'kingrich_api_logs.batch_transaction_id',
				'formatter' => function($d, $row) use ($is_export){
						return $d;
				},
			),
			array(
				'dt' => $i++,
				'alias' => 'created_at',
				'select' => 'kingrich_api_logs.created_at',
				'formatter' => function($d, $row) use ($is_export){
						return $d;
				},
			),
			array(
				'dt' => $i++,
				'alias' => 'status',
				'select' => 'kingrich_api_logs.status',
				'formatter' => function($d, $row) use ($is_export){
						return $d;
				},
			),
		);

		$input = $this->data_tables->extra_search($request);

		$table = 'kingrich_api_logs';

		$joins = array();

		# START PROCESS SEARCH FORM #################################################################################################################################################
		$where = array();
		$values = array();

		if (isset($input['dateRangeValueStart'])) {
			$where[] = "created_at >= ?";
			$values[] = $input['dateRangeValueStart'];
		}

		if (isset($input['dateRangeValueEnd'])) {
			$where[] = "created_at <= ?";
			$values[] = $input['dateRangeValueEnd'];
		}

		# END PROCESS SEARCH FORM #################################################################################################################################################

        $group_by=[];
        $having=[];
        $distinct=true;
        $external_order=[];
        $not_datatable='';
		$countOnlyField='kingrich_api_logs.id';

		$result = $this->data_tables->get_data($request, $columns, $table, $where, $values, $joins, $group_by, $having,	 $distinct, $external_order, $not_datatable, $countOnlyField);

		return $result;
	}

	public function daily_active_players_by_game_type($qdate = '') {
		$qdate = empty($qdate) ? $this->utils->getTodayForMysql() : date('Y-m-d', strtotime($qdate));

		$this->db->from('total_player_game_day AS H')
			->join('game_type AS T', 'H.game_type_id = T.id', 'left')
			->join('external_system AS S', 'H.game_platform_id = S.id', 'left')
			->join('game_tags AS G', 'T.game_tag_id = G.id', 'left')
			->select([
				'S.system_code AS system_code' ,
				'T.id AS game_type_id' ,
				'COUNT(DISTINCT H.player_id) AS player_count' ,
				'G.tag_code'
			])
			->where(['H.date' => $qdate])
			->group_by(['tag_code'])
			->order_by('tag_code')
		;

		$res = $this->runMultipleRowArray();

		// $this->utils->debug_log(__METHOD__, 'res', $res);

		if (empty($res)) {
			return $res;
		}

		return $res;
	}

	public function dashboard_daily_active_players($qdate = '') {
		$act_players = $this->daily_active_players_by_game_type($qdate);
		if (empty($act_players)) {
			return [
				// 'morris' => [ 'label' => lang('Not available'), 'value' => 0 ] ,
				'morris' => [[ 'label' => lang('Not available'), 'value' => 0 ]] ,
				'cat_cues' => 'others'
			];
		}

		if(!empty($this->utils->getConfig('customer_game_tag_disp')) && is_array($this->utils->getConfig('customer_game_tag_disp'))){
			$customer_game_tag_disp = $this->utils->getConfig('customer_game_tag_disp');
			foreach ($customer_game_tag_disp as $key => $value) {
				$game_tag_disp[$key] = lang($value['lang_key']);
			}
		}else{
			$game_tag_disp = [
				'live_dealer'	=> lang('Live Dealers') ,
				'sports'		=> lang('Sports') ,
				'slots'			=> lang('Slots') ,
				'poker'			=> lang('Poker') ,
				'fishing_game'	=> lang('Fishing') ,
				'lottery'		=> lang('Lottery') ,
			];
		}

		foreach ($act_players as $row) {
			if (isset($cats[$row['tag_code']])) {
				$cats[$row['tag_code']] += $row['player_count'];
			}
			else {
				$cats[$row['tag_code']] = [$row['player_count']];
			}
		}

		$cat_cues = [];
		$morris_data = [];
		$cat_others = 0;
		foreach ($cats as $cat => $player_count) {
			$this->utils->debug_log(__METHOD__, $cat, $player_count);
			if (isset($game_tag_disp[$cat])) {
				$morris_data[] = [
					'label' => $game_tag_disp[$cat] ,
			 		'value' => $player_count
				];
				$cat_cues[] = $cat;
			}
			else {
				$cat_others += intval(array_pop($player_count));
			}
		}
		if ($cat_others > 0) {
			$morris_data[] = [
				'label' => lang('Others') ,
			 	'value' => $cat_others
			];
			$cat_cues[] = 'others';
		}

		return [ 'morris' => $morris_data, 'cat_cues' => $cat_cues ];
	}

	/**
	 * detail: get oneworks game report for a certain player
	 *
	 * @param array $request
	 * @param int $player_id oneworks_game_report player_id
	 *
	 * @return array
	 */
	public function oneworksGameReports($request, $player_id = null, $is_export = false) {
		$this->load->library(array('data_tables'));
		$this->load->model(array('player_model','game_logs'));
		$input = $this->data_tables->extra_search($request);
		$table = 'oneworks_game_report';

		$joins = array();
		$where = array();
		$values = array();
		$group_by = array();
		$having = array();

		$joins['game_type'] = 'game_type.id = oneworks_game_report.game_type_id';
		$joins['game_description'] = 'game_description.id = oneworks_game_report.game_description_id';
		$joins['external_system'] = 'external_system.id = oneworks_game_report.game_platform_id';
		$joins['player'] = 'player.playerId = oneworks_game_report.player_id';
		$joins['affiliates'] = 'affiliates.affiliateId = player.affiliateId';

		$group_by_field = null;
		$show_game_platform = false;
		$show_game_type = false;
		$show_game = false;
		$show_player = false;
		$this->utils->debug_log('the input ------>', json_encode($input));

		if (isset($input['group_by'])) {
			$group_by_field = $input['group_by'];
			switch ($input['group_by']) {
			case 'game_platform_id':
				$group_by[] = 'oneworks_game_report.game_platform_id';
				$show_game_platform = true;
				break;
			case 'game_type_id':
				$group_by[] = 'oneworks_game_report.game_type_id';
				$show_game_platform = true;
				$show_game_type = true;
				break;
			case 'game_description_id':
				$group_by[] = 'oneworks_game_report.game_description_id';
				$show_game_platform = true;
				$show_game_type = true;
				$show_game = true;
				break;
			case 'player_id':
				$group_by[] = 'oneworks_game_report.player_id';
				$show_player = true;
				break;
			case 'aff_id':
				$group_by[] = 'oneworks_game_report.affiliate_id';
				$show_player = true;
				break;
			case 'agent_id':
				$group_by[] = 'oneworks_game_report.agent_id';
				$show_player = true;
				break;
			case 'game_type_and_player':
				$group_by[] = 'oneworks_game_report.player_id, oneworks_game_report.game_type_id';
				$show_game_platform = true;
				$show_game_type = true;
				$show_player = true;
				break;
			case 'game_platform_and_player':
				$group_by[] = 'oneworks_game_report.player_id, oneworks_game_report.game_platform_id';
				$show_game_platform = true;
				$show_player = true;
				break;
			case 'game_description_and_player':
				$group_by[] = 'oneworks_game_report.player_id, oneworks_game_report.game_description_id';
				$show_game_platform = true;
				$show_game_type = true;
				$show_game = true;
				$show_player = true;
				break;
			}
		}
		if (isset($input['username'])) {
			$where[] = "oneworks_game_report.player_username = ?";
			$values[] = $input['username'];
		}

		# DEFINE TABLE COLUMNS ########################################################################################################################################################################
		$i = 0;
		$columns = array(
			array(
				'alias' => 'player_playerId',
				'select' => 'player.playerId'
			),

			array(
				'alias' => 'player_levelName',
				'select' => 'player.levelName'
			),
			array(
				'alias' => 'player_groupName',
				'select' => 'player.groupName'
			),
			array(
				'alias' => 'affiliates_affiliateId',
				'select' => 'affiliates.affiliateId'
			),
			array(
				'alias' => 'external_system_id',
				'select' => 'external_system.id'
			),
			array(
				'alias' => 'game_description_id',
				'select' => $show_game ? 'game_description.id' : '"N/A"',
			),
			array(
				'alias' => 'game_type_id',
				'select' => $show_game_type ? 'game_type.id' : '"N/A"',
			),
			array(
				'dt' => $i++,
				'alias' => 'system_code',
				'select' => $show_game_platform ? 'external_system.system_code' : '"N/A"',
				'formatter' => function($d, $row) use ($is_export)  {

                     if ($is_export) {
                     	return $d;
                     }else{
                     	return $d. '<p class=" hide gamePlatformId">'. $row['external_system_id']. '</p>';
                     }

				},
				'name' => lang('System Code'),
			),
			array(
				'dt' => $i++,
				'alias' => 'game_type_lang',
				'select' => $show_game_type ? 'game_type.game_type_lang' : '"N/A"',
				'formatter' => 'languageFormatter',
				'name' => lang('Game Type Language'),
			),
			array(
				'dt' => $i++,
				'alias' => 'game_name',
				'select' => $show_game ? 'game_description.game_name' : '"N/A"',
				'formatter' => 'languageFormatter',
				'name' => lang('Game Name'),
			),
			array(
				'dt' => $i++,
				'alias' => 'player_username',
				 //'select' => $show_player ? '( CASE WHEN affiliates.username IS NULL THEN player.username ELSE CONCAT(player.username, \' (\', affiliates.username,  \')\' ) END )' : '"N/A"',
				'select' => $show_player ? 'player.username' : '"N/A"',
				'formatter' => function($d, $row) use ($is_export) {

					if ($this->utils->isAffSubProject() && $this->utils->isEnabledFeature('masked_player_username_on_affiliate')) {
						$d = $this->utils->keepOnlyString($d, 4);
					}

					if($d != 'N/A') {
						return $is_export ? $d : '<a href="/player_management/userInformation/' . $row['player_playerId'] . '" target="_blank">' . $d . '</a>';
					}
					return $d;
				},
				'name' => lang('Username'),
			),

			array(
				'dt' => $i++,
				'alias' => 'player_level',
				'select' => $show_player ? 'player.levelId' : '"N/A"',
				'formatter' => function($d, $row){
					if($d != 'N/A') {
						return lang($row['player_groupName']).' - '.lang($row['player_levelName']);
					}
		  			return $d;

				},
				'name' => lang('Player Level'),

			),
			array(
				'dt' => $i++,
				'alias' => 'affiliate',
				'select' => 'affiliates.username',
				'formatter' => function($d, $row) use ($is_export) {

					if ($this->utils->isEnabledFeature('masked_affiliate_username_on_affiliate')) {
						$d = $this->utils->keepOnlyString($d, 4);
					}

					if($d) {
						if($this->utils->isFromHost('aff')) {
							return trim(trim($d), ',') ?: lang('lang.norecyet');
						}
						return $is_export ? $d : '<a href="/affiliate_management/userInformation/' . $row['affiliates_affiliateId'] . '" target="_blank">' . $d . '</a>';
					} else {

					}
				},
				'name' => lang('aff.as03'),
			),
			array(
				'dt' => $i++,
				'alias' => 'total_player',
				'select' => 'COUNT(DISTINCT oneworks_game_report.player_id)',
				'formatter' => function ($d, $row)  use ($is_export)  {
					//return $is_export ? $d : '<a href="#" class="totalPlayers">' . $d . '</a>';
					return $d;
				},
				'name' => lang('Total Player'),
			),
			array(
				'dt' => $i++,
				'alias' => 'total_bet',
				'select' => 'SUM(oneworks_game_report.total_wins + oneworks_game_report.total_loss)',
				'formatter' =>  function($d, $row) use ($is_export) {
					return $this->currencyFormatter($d, $is_export);
				},
				'name' => lang('Total Bet'),
			),

			array(
				'dt' => $i++,
				'alias' => 'total_gain',
				'select' => 'SUM(oneworks_game_report.total_wins)',
				'formatter' =>  function($d, $row) use ($is_export) {
					return $this->currencyFormatter($d, $is_export);
				},
				'name' => lang('Total Gain'),
			),
			array(
				'dt' => $i++,
				'alias' => 'total_loss',
				'select' => 'SUM(oneworks_game_report.total_loss)',
				'formatter' =>  function($d, $row) use ($is_export) {
					return $this->currencyFormatter($d, $is_export);
				},
				'name' => lang('Total Loss'),
			),
			array(
				'dt' => $i++,
				'alias' => 'payout',
				'select' => 'SUM(oneworks_game_report.total_loss-oneworks_game_report.total_wins)',
				'formatter' =>  function($d, $row) use ($is_export) {
					return $this->currencyFormatter($d, $is_export);
				},
				'name' => lang('Payout'),
			),
			array(
				'dt' => $i++,
				'alias' => 'rate',
				'select' => 'SUM(oneworks_game_report.total_loss-oneworks_game_report.total_wins)/SUM(oneworks_game_report.total_bets)',
				'formatter' => 'percentageFormatter',
				'name' => lang('Rate'),
			),
		);

		# FILTER ######################################################################################################################################################################################

		if (isset($input['datetime_from'], $input['datetime_to'])) {
			$where[] ='oneworks_game_report.game_date >= ?';
			$where[] ='oneworks_game_report.game_date <= ?';
			$values[] = $input['datetime_from'];
			$values[] = $input['datetime_to'];
		}
		$where[] = "(oneworks_game_report.status IN ('won','draw','lose','half won','half lose') OR oneworks_game_report.status IS NULL)";//get only settled and not updated status which is null
		$result = $this->data_tables->get_data($request, $columns, $table, $where, $values, $joins, $group_by, $having, false);

		$result['summary'] = array();

		$total_payout_rate =  'SUM(oneworks_game_report.total_loss-oneworks_game_report.total_wins)/SUM(oneworks_game_report.total_bets) * 100 total_payout_rate' ;
		$total_bet =  'SUM(oneworks_game_report.total_wins + oneworks_game_report.total_loss) total_bet ';
		$total_ave_count =  'COUNT(oneworks_game_report.total_bets) total_ave_count ';
     	$total_ave_bet = 'SUM(oneworks_game_report.total_bets) /COUNT(oneworks_game_report.total_bets)  total_ave_bet ';


		$summary = $this->data_tables->summary($request, $table, $joins, 'COUNT(DISTINCT oneworks_game_report.player_id) total_player, '.$total_bet.','.$total_ave_bet.','.$total_ave_count.'  ,SUM(oneworks_game_report.total_wins) as total_win, SUM(oneworks_game_report.total_loss) total_loss,SUM(oneworks_game_report.total_loss-oneworks_game_report.total_wins) total_payout,'.$total_payout_rate.'', null, $columns, $where, $values);

		$group_by2=[];
		$group_by2[] = 'oneworks_game_report.player_id, oneworks_game_report.game_platform_id';
		$player_total_bets_per_game = $this->data_tables->summary($request, $table, $joins, 'DISTINCT oneworks_game_report.player_id player_id , player.username, oneworks_game_report.game_platform_id, '.$total_bet.', SUM(oneworks_game_report.total_wins) as total_win, SUM(oneworks_game_report.total_loss) total_loss , SUM(oneworks_game_report.total_loss-oneworks_game_report.total_wins) total_payout,', $group_by2, $columns, $where, $values);


		$player_total_bets_per_game_map = [];
		$game_platform_header_map =[];
        $sum_total_bets_map =[];
        $sum_total_wins_map =[];
        $sum_total_loss_map =[];
        $sum_total_payout_map =[];
        $bet_details_map = [];

        foreach ($player_total_bets_per_game as $v) {

            //make bet details array
			$bet_details_map[$v['player_id']][$v['game_platform_id']]= array('game_platform_id' => $v['game_platform_id'] , 'total_bet' => $this->currencyFormatter($v['total_bet']) , 'total_win' => $this->currencyFormatter($v['total_win']) , 'total_loss' => $this->currencyFormatter($v['total_loss']) ,'total_payout' => $this->currencyFormatter($v['total_payout'])  ) ;
            //array push every games bet per player
			$sum_total_bets_map[$v['player_id']][] = $v['total_bet'] ;
			$sum_total_wins_map[$v['player_id']][] = $v['total_win'] ;
			$sum_total_loss_map[$v['player_id']][] = $v['total_loss'] ;
			$sum_total_payout_map[$v['player_id']][] = $v['total_payout'] ;
			//add the bet items to get the total bets for all player game
            $player_total_games_bet = $this->currencyFormatter(array_sum($sum_total_bets_map[$v['player_id']]));
            $player_total_games_win = $this->currencyFormatter(array_sum($sum_total_wins_map[$v['player_id']]));
            $player_total_games_loss = $this->currencyFormatter(array_sum($sum_total_loss_map[$v['player_id']]));
            $player_total_games_payout = $this->currencyFormatter(array_sum($sum_total_payout_map[$v['player_id']]));
             //construct now the player row
            $player_row = [];
            $player_row['player_id'] = $v['player_id'];
            $player_row['username'] = $v['username'];
            $player_row['bet_details'] =  $bet_details_map[$v['player_id']];
            $player_row['sum_total_bets'] = $player_total_games_bet;
            $player_row['sum_total_wins'] = $player_total_games_win;
            $player_row['sum_total_loss'] = $player_total_games_loss;
            $player_row['sum_total_payout'] = $player_total_games_payout;
            // add on main players map for json output
			$player_total_bets_per_game_map[$v['player_id']] =  $player_row;
			// make selective table headers  by making map -> associative override
			$game_platform_header_map[$v['game_platform_id']] = $v['game_platform_id'];

		}
		array_walk($summary[0], function (&$value) {
			$value = round(floatval($value), 2);
		});

        $result['player_total_bets_per_game'] = $player_total_bets_per_game_map;
        $result['game_platform_header_map'] = $game_platform_header_map;
		$result['summary'] = $summary;
		//$result['summary'] = [];

		return $result;
	}

	/**
	 * detail: get Kingrich Summary Report
	 *
	 * @param string transaction_batch_id
	 * @param datetime create_date
	 * @return json
	 */
	public function kingrichSummaryReport($request, $is_export = false ) {

        $this->load->library('data_tables');
		$i = 0;

		$input = $this->data_tables->extra_search($request);
		$report_interval = null;
		if(isset($input['by_report_interval'])) {
			$report_interval = $input['by_report_interval'];
		}

		$columns = array(
			array(
				'alias' => 'kingrich_summary_report_id',
				'select' => 'kingrich_summary_reports.id',
				'formatter' => function($d, $row) use ($is_export){
						return $d;
				},
			),
			array(
				'alias' => 'kingrich_game_type',
				'select' => 'kingrich_summary_reports.kingrich_game_type',
				'formatter' => function($d, $row) use ($is_export){
						return $d;
				},
			),
			array(
				'alias' => 'includes_empty_currency',
				'select' => '(CASE WHEN MIN(kingrich_summary_reports.currency) = "" THEN 1 WHEN MIN(kingrich_summary_reports.currency) IS NULL THEN 1 ELSE 0 END)',
				'formatter' => function($d, $row) use ($is_export){
						return $d;
				},
			),
			array(
				'dt' => $i++,
				'alias' => 'settlement_date',
				'select' => 'kingrich_summary_reports.settlement_date',
				'formatter' => function($d, $row) use ($is_export, $report_interval, $input){
						if (!empty($report_interval)) {

							$settlement_date = new DateTime($d);

							$short_remark = '';

							if(isset($input['currency']) && isset($row['includes_empty_currency']) && $row['includes_empty_currency'] == '1'){
								$short_remark = ' <i class="short-remark" style="color: red;">('.lang('Improperly filtered by currency due to outdated data.').')</i>';
							}

							switch ($report_interval) {
								case 'daily':
									return $this->utils->formatDateForMysql(new DateTime($d)) . $short_remark;
									break;
								case 'weekly':
									//This is to identify the current week of the day in particular month
									list($y, $m, $d) = explode('-', date('Y-m-d', strtotime($d)));
								    $w = 1;
								    for ($i = 1; $i <= $d; ++$i) {
								        if ($i > 1 && date('w', strtotime("$y-$m-$i")) == 0) {
								            ++$w;
								        }
								    }
									return lang("week")." - ".$w." - ".$settlement_date->format('M Y')  . $short_remark;
									break;
								case 'monthly':
									return $settlement_date->format('M Y')  . $short_remark;
									break;
								default:
									return $this->utils->formatDateForMysql(new DateTime($d))  . $short_remark;
									break;
							}
						}
						return $this->utils->formatDateForMysql(new DateTime($d));
				},
				'name' => lang('Settlement Date'),
			),
			array(
				'dt' => $i++,
				'alias' => 'sum_bet_amount',
				'select' => 'SUM(kingrich_summary_reports.sum_bet_amount)',
				'formatter' => function($d, $row) use ($is_export){
						return $this->data_tables->currencyFormatter($d);;
				},
				'name' => lang('Bet Amount'),
			),
			array(
				'dt' => $i++,
				'alias' => 'sum_debit_amount',
				'select' => 'SUM(kingrich_summary_reports.sum_debit_amount)',
				'formatter' => function($d, $row) use ($is_export){
						return $this->data_tables->currencyFormatter($d);;
				},
				'name' => lang('Debit Amount'),
			),
			array(
				'dt' => $i++,
				'alias' => 'sum_credit_amount',
				'select' => 'SUM(kingrich_summary_reports.sum_credit_amount)',
				'formatter' => function($d, $row) use ($is_export){
						return $this->data_tables->currencyFormatter($d);;
				},
				'name' => lang('Credit Amount'),
			),
			array(
				'dt' => $i++,
				'alias' => 'sum_net_amount',
				'select' => 'SUM(kingrich_summary_reports.sum_net_amount)',
				'formatter' => function($d, $row) use ($is_export){
						return $this->data_tables->currencyFormatter($d);;
				},
				'name' => lang('Net Amount'),
			),
			array(
				'dt' => $i++,
				'alias' => 'sum_number_of_bets',
				'select' => 'SUM(kingrich_summary_reports.sum_number_of_bets)',
				'formatter' => function($d, $row) use ($is_export){
						return $d;
				},
				'name' => lang('Number of Bets'),
			),
		);



		$table = 'kingrich_summary_reports';

		$joins = array();

		# START PROCESS SEARCH FORM #################################################################################################################################################
		$where = array();
		$values = array();
		$group_by=[];
        $having=[];

		if (isset($input['dateRangeValueStart'])) {
			$where[] = "settlement_date >= ?";
			$values[] = $input['dateRangeValueStart'];
		}

		if (isset($input['dateRangeValueEnd'])) {
			$where[] = "settlement_date <= ?";
			$values[] = $input['dateRangeValueEnd'];
		}

		if (isset($input['by_game_type_globalcom'])) {
			if(!empty($input['by_game_type_globalcom'])){
				$where[] = "kingrich_game_type = ?";
				$values[] = $input['by_game_type_globalcom'];
			}
		}

		if (!empty($report_interval)) {
			switch ($report_interval) {
				case 'daily':
					$group_by[] = 'date(settlement_date)';
					break;
				case 'weekly':
					$group_by[] = 'WEEK(settlement_date)';
					break;
				case 'monthly':
					$group_by[] = 'MONTH(settlement_date)';
					break;
				default:
					$group_by[] = 'date(settlement_date)';
					break;
			}
		}

		if (isset($input['currency'])) {
			if(!empty($input['currency'])){
				$where[] = "(currency = ? OR currency = ? OR currency IS NULL)";
				$values[] = $input['currency'];
				$values[] = '';
			}
		}


		# END PROCESS SEARCH FORM #################################################################################################################################################
		if($is_export){
            $this->data_tables->options['is_export']=true;
            // $this->data_tables->options['only_sql']=true;
            if(empty($csv_filename)){
                $csv_filename=$this->utils->create_csv_filename(__FUNCTION__);
            }
            $this->data_tables->options['csv_filename']=$csv_filename;
        }

        $distinct=true;
        $external_order=[];
        $not_datatable='';
		$countOnlyField='kingrich_summary_reports.id';

		$result = $this->data_tables->get_data($request, $columns, $table, $where, $values, $joins, $group_by, $having,	 $distinct, $external_order, $not_datatable, $countOnlyField);

		if($is_export){
		    //drop result if export
			return $csv_filename;
		}

		$summary = $this->data_tables->summary($request, $table, $joins, 'SUM(kingrich_summary_reports.sum_bet_amount) total_sum_bet_amount, SUM(kingrich_summary_reports.sum_debit_amount) total_sum_debit_amount, SUM(kingrich_summary_reports.sum_credit_amount) total_sum_credit_amount, SUM(kingrich_summary_reports.sum_net_amount) total_sum_net_amount, SUM(kingrich_summary_reports.sum_number_of_bets) total_sum_number_of_bets', null, $columns, $where, $values);

		array_walk($summary[0], function (&$value) {
			$value = round(floatval($value), 2);
		});


		$result['summary'] = $summary;

		return $result;
	}


	/**
	 * detail: get game maintenace schedule
	 *
	 * @return array
	 */
	public function getGameMaintenanceSchedule ($request,$is_export)
	{
		$this->load->library(array('data_tables'));
		$this->load->model('users');
		$where = array();
		$values = array();
		$columns = array();


		$input = $this->data_tables->extra_search($request);
		if(isset($input['game_platform_id']) AND !empty($input['game_platform_id'])){
			$where[] = "game_maintenance_schedule.game_platform_id = ? ";
			$values[] = $input['game_platform_id'];
		}

		if(isset($input['date_from_search']) AND !empty($input['date_from_search'])){
			$where[] = "game_maintenance_schedule.start_date >= ?";
			$where[] = "game_maintenance_schedule.start_date <= ?";
			$values[] = $input['date_from_search'].' '.Utils::FIRST_TIME;
			$values[] = $input['date_from_search'].' '.Utils::LAST_TIME;
		}

		$i = 0;

		$is_export = false;

		$columns[] = array(
			'dt' => $i++,
			'select' => 'external_system.system_code',
			'alias' => 'external_system',
			'name' => lang('Maintenance Game'),
		);
		$columns[] = array(
			'select' => 'game_maintenance_schedule.end_date',
			'alias' => 'end_date',
		);

		$columns[] = array(
			'dt' => $i++,
			'select' => 'game_maintenance_schedule.start_date',
			'alias' => 'start_date',
			'name' => lang('Start Date'),
			'formatter' => function ($d, $row){
                return $d . ' ~ ' . $row['end_date'];
            },
		);

		$columns[] = array(
			'dt' => $i++,
			'select' => 'game_maintenance_schedule.status',
			'alias' => 'status',
			'name' => lang('Mission Status'),
			'formatter' => function ($d, $row) use ($is_export) {
				if ($is_export) {
					return $d;
				}else{
					switch ($row['status']) {
					    case External_system::MAINTENANCE_STATUS_PENDING:
					       $status = "Pending";
					        break;
					    case External_system::MAINTENANCE_STATUS_IN_MAINTENANCE:
					    	$status = "<span style='color:red'>In Maintenance</span>";
					    	break;
					    case External_system::MAINTENANCE_STATUS_DONE:
					    	$status = "<span style='color:green'>Maintenance Done</span>";
					    	break;
					    default:
					    	$status = "Cancelled";
				 }
				 return $status;
			  }
		   }
		);

		$columns[] = array(
			'dt' => $i++,
			'select' => 'game_maintenance_schedule.created_by',
			'alias' => 'created_by',
			'name' => lang('Maintenance Scheduled By'),
			'formatter' => function ($d){
				$result = $this->users->selectUsersById($d);
				return $result['username'];
			}
		);

		$columns[] = array(
			'dt' => $i++,
			'select' => 'adminusers.username',
			'alias' => 'last_edit_user',
			'name' => lang('Last Updated By'),
		);

		$columns[] = array(
			'dt' => $i++,
			'select' => 'game_maintenance_schedule.updated_at',
			'alias' => 'updatedOn',
			'name' => lang('Updated Date'),
		);

		$columns[] = array(
			'dt' => $i++,
			'select' => 'game_maintenance_schedule.note',
			'alias' => 'note',
			'name' => lang('Note'),
		);

		$columns[] = array(
			'select' => 'game_maintenance_schedule.id',
			'alias' => 'id',
		);

		$columns[] = array(
			'dt' => $i++,
			'select' => 'game_maintenance_schedule.game_platform_id',
			'alias' => 'game_platform_id',
			'name' => lang('Action'),
			'formatter' => function ($d, $row) use ($is_export) {
				if ($is_export) {
					return $d;
				}else{
					$output = '';
					if($row['status'] == 1){
						if($this->permissions->checkPermissions('maintenance_schedule_control')){
							$output = '<button  class="btn btn-primary btn-sm edit" id= '.$row['id'].'>Edit</button><button  class="btn-danger btn btn-sm danger cancel" id= '.$row['id'].' >Cancel</button>';
						}
					}
					if($row['status'] == 2){
						$output = '<button  class="btn btn-danger btn-sm stop" id= '.$row['id'].' >Stop</button>';
					}
					return $output;
				}
			}
		);

		$table = 'game_maintenance_schedule';
		$joins = array(
			'external_system' => 'external_system.id = game_maintenance_schedule.game_platform_id',
			'adminusers' => 'adminusers.userId = game_maintenance_schedule.last_edit_user',
		);

		return $result = $this->data_tables->get_data($request, $columns, $table, $where, $values, $joins);
	}

	 public function sbobetGameReports($request, $player_id = null, $is_export = false) {
		 $this->load->library(array('data_tables'));
		 $this->load->model(array('player_model','game_logs'));
		 $input = $this->data_tables->extra_search($request);
		 $table = 'sbo_game_report';

		 $joins = array();
		 $where = array();
		 $values = array();
		 $group_by = array();
		 $having = array();

		 $joins['game_type'] = 'game_type.id = sbo_game_report.game_type_id';
		 $joins['game_description'] = 'game_description.id = sbo_game_report.game_description_id';
		 $joins['external_system'] = 'external_system.id = sbo_game_report.game_platform_id';
		 $joins['player'] = 'player.playerId = sbo_game_report.player_id';
		 $joins['affiliates'] = 'affiliates.affiliateId = player.affiliateId';

		 $group_by_field = null;
		 $show_game_platform = false;
		 $show_game_type = false;
		 $show_game = false;
		 $show_player = false;
		 $this->utils->debug_log('the input ------>', json_encode($input));

		 if (isset($input['group_by'])) {
			 $group_by_field = $input['group_by'];
			 switch ($input['group_by']) {
				 case 'game_platform_id':
					 $group_by[] = 'sbo_game_report.game_platform_id';
					 $show_game_platform = true;
					 break;
				 case 'game_type_id':
					 $group_by[] = 'sbo_game_report.game_type_id';
					 $show_game_platform = true;
					 $show_game_type = true;
					 break;
				 case 'game_description_id':
					 $group_by[] = 'sbo_game_report.game_description_id';
					 $show_game_platform = true;
					 $show_game_type = true;
					 $show_game = true;
					 break;
				 case 'player_id':
					 $group_by[] = 'sbo_game_report.player_id';
					 $show_player = true;
					 break;
				 case 'aff_id':
					 $group_by[] = 'sbo_game_report.affiliate_id';
					 $show_player = true;
					 break;
				 case 'agent_id':
					 $group_by[] = 'sbo_game_report.agent_id';
					 $show_player = true;
					 break;
				 case 'game_type_and_player':
					 $group_by[] = 'sbo_game_report.player_id, sbo_game_report.game_type_id';
					 $show_game_platform = true;
					 $show_game_type = true;
					 $show_player = true;
					 break;
				 case 'game_platform_and_player':
					 $group_by[] = 'sbo_game_report.player_id, sbo_game_report.game_platform_id';
					 $show_game_platform = true;
					 $show_player = true;
					 break;
				 case 'game_description_and_player':
					 $group_by[] = 'sbo_game_report.player_id, sbo_game_report.game_description_id';
					 $show_game_platform = true;
					 $show_game_type = true;
					 $show_game = true;
					 $show_player = true;
					 break;
			 }
		 }
		 if (isset($input['username'])) {
			 $where[] = "sbo_game_report.player_username = ?";
			 $values[] = $input['username'];
		 }

		 # DEFINE TABLE COLUMNS ########################################################################################################################################################################
		 $i = 0;
		 $columns = array(
			 array(
				 'alias' => 'player_playerId',
				 'select' => 'player.playerId'
			 ),
			 array(
				 'alias' => 'player_levelName',
				 'select' => 'player.levelName'
			 ),
			 array(
				 'alias' => 'player_groupName',
				 'select' => 'player.groupName'
			 ),
			 array(
				 'alias' => 'affiliates_affiliateId',
				 'select' => 'affiliates.affiliateId'
			 ),
			 array(
				 'alias' => 'external_system_id',
				 'select' => 'external_system.id'
			 ),
			 array(
				 'alias' => 'game_description_id',
				 'select' => $show_game ? 'game_description.id' : '"N/A"',
			 ),
			 array(
				 'alias' => 'game_type_id',
				 'select' => $show_game_type ? 'game_type.id' : '"N/A"',
			 ),
			 array(
				 'dt' => $i++,
				 'alias' => 'system_code',
				 'select' => $show_game_platform ? 'external_system.system_code' : '"N/A"',
				 'formatter' => function($d, $row) use ($is_export)  {

					 if ($is_export) {
						 return $d;
					 }else{
						 return $d. '<p class=" hide gamePlatformId">'. $row['external_system_id']. '</p>';
					 }

				 },
				 'name' => lang('System Code'),
			 ),
			 array(
				 'dt' => $i++,
				 'alias' => 'game_type_lang',
				 'select' => $show_game_type ? 'game_type.game_type_lang' : '"N/A"',
				 'formatter' => 'languageFormatter',
				 'name' => lang('Game Type Language'),
			 ),
			 array(
				 'dt' => $i++,
				 'alias' => 'game_name',
				 'select' => $show_game ? 'game_description.game_name' : '"N/A"',
				 'formatter' => 'languageFormatter',
				 'name' => lang('Game Name'),
			 ),
			 array(
				 'dt' => $i++,
				 'alias' => 'player_username',
				 'select' => $show_player ? 'player.username' : '"N/A"',
				 'formatter' => function($d, $row) use ($is_export) {

					 if ($this->utils->isAffSubProject() && $this->utils->isEnabledFeature('masked_player_username_on_affiliate')) {
						 $d = $this->utils->keepOnlyString($d, 4);
					 }

					 if($d != 'N/A') {
						 return $is_export ? $d : '<a href="/player_management/userInformation/' . $row['player_playerId'] . '" target="_blank">' . $d . '</a>';
					 }
					 return $d;
				 },
				 'name' => lang('Username'),
			 ),

			 array(
				 'dt' => $i++,
				 'alias' => 'player_level',
				 'select' => $show_player ? 'player.levelId' : '"N/A"',
				 'formatter' => function($d, $row){
					 if($d != 'N/A') {
						 return lang($row['player_groupName']).' - '.lang($row['player_levelName']);
					 }
					 return $d;

				 },
				 'name' => lang('Player Level'),
			 ),
			 array(
				 'dt' => $i++,
				 'alias' => 'affiliate',
				 'select' => 'affiliates.username',
				 'formatter' => function($d, $row) use ($is_export) {

					 if ($this->utils->isEnabledFeature('masked_affiliate_username_on_affiliate')) {
						 $d = $this->utils->keepOnlyString($d, 4);
					 }

					 if($d) {
						 if($this->utils->isFromHost('aff')) {
							 return trim(trim($d), ',') ?: lang('lang.norecyet');
						 }
						 return $is_export ? $d : '<a href="/affiliate_management/userInformation/' . $row['affiliates_affiliateId'] . '" target="_blank">' . $d . '</a>';
					 } else {

					 }
				 },
				 'name' => lang('aff.as03'),
			 ),
			 array(
				 'dt' => $i++,
				 'alias' => 'total_player',
				 'select' => 'COUNT(DISTINCT sbo_game_report.player_id)',
				 'formatter' => function ($d, $row)  use ($is_export)  {
					 return $d;
				 },
				 'name' => lang('Total Player'),
			 ),
			 array(
				 'dt' => $i++,
				 'alias' => 'total_bet',
				 'select' => 'SUM(sbo_game_report.total_bets)',
				 'formatter' =>  function($d, $row) use ($is_export) {
					 return $this->currencyFormatter($d, $is_export);
				 },
				 'name' => lang('Total Bet'),
			 ),
			 array(
				 'dt' => $i++,
				 'alias' => 'total_gain',
				 'select' => 'SUM(sbo_game_report.total_wins)',
				 'formatter' =>  function($d, $row) use ($is_export) {
					 return $this->currencyFormatter($d, $is_export);
				 },
				 'name' => lang('Total Gain'),
			 ),
			 array(
				 'dt' => $i++,
				 'alias' => 'total_loss',
				 'select' => 'SUM(sbo_game_report.total_loss)',
				 'formatter' =>  function($d, $row) use ($is_export) {
					 return $this->currencyFormatter($d, $is_export);
				 },
				 'name' => lang('Total Loss'),
			 ),
			 array(
				 'dt' => $i++,
				 'alias' => 'payout',
				 'select' => 'SUM(sbo_game_report.total_loss-sbo_game_report.total_wins)',
				 'formatter' =>  function($d, $row) use ($is_export) {
					 return $this->currencyFormatter($d, $is_export);
				 },
				 'name' => lang('Payout'),
			 ),
			 array(
				 'dt' => $i++,
				 'alias' => 'rate',
				 'select' => 'SUM(sbo_game_report.total_loss-sbo_game_report.total_wins)/SUM(sbo_game_report.total_bets)',
				 'formatter' => 'percentageFormatter',
				 'name' => lang('Rate'),
			 ),
		 );

		 if (isset($input['datetime_from'], $input['datetime_to'])) {
			 $where[] ='sbo_game_report.game_date >= ?';
			 $where[] ='sbo_game_report.game_date <= ?';
			 $values[] = $input['datetime_from'];
			 $values[] = $input['datetime_to'];
		 }

		 $result = $this->data_tables->get_data($request, $columns, $table, $where, $values, $joins, $group_by, $having, false);

		 $result['summary'] = array();

		 $total_payout_rate =  'SUM(sbo_game_report.total_loss-sbo_game_report.total_wins)/SUM(sbo_game_report.total_bets) * 100 total_payout_rate' ;
		 $total_bet =  'SUM(sbo_game_report.total_bets) total_bet ';
		 $total_ave_count =  'COUNT(sbo_game_report.total_bets) total_ave_count ';
		 $total_ave_bet = 'SUM(sbo_game_report.total_bets) /COUNT(sbo_game_report.total_bets)  total_ave_bet ';

		 $summary = $this->data_tables->summary($request, $table, $joins, 'COUNT(DISTINCT sbo_game_report.player_id) total_player, '.$total_bet.','.$total_ave_bet.','.$total_ave_count.'  ,SUM(sbo_game_report.total_wins) as total_win, SUM(sbo_game_report.total_loss) total_loss,SUM(sbo_game_report.total_loss-sbo_game_report.total_wins) total_payout,'.$total_payout_rate.'', null, $columns, $where, $values);

		 $group_by2=[];
		 $group_by2[] = 'sbo_game_report.player_id, sbo_game_report.game_platform_id';
		 $player_total_bets_per_game = $this->data_tables->summary($request, $table, $joins, 'DISTINCT sbo_game_report.player_id player_id , player.username, sbo_game_report.game_platform_id, '.$total_bet.', SUM(sbo_game_report.total_wins) as total_win, SUM(sbo_game_report.total_loss) total_loss , SUM(sbo_game_report.total_loss-sbo_game_report.total_wins) total_payout,', $group_by2, $columns, $where, $values);

		 $player_total_bets_per_game_map = [];
		 $game_platform_header_map =[];
		 $sum_total_bets_map =[];
		 $sum_total_wins_map =[];
		 $sum_total_loss_map =[];
		 $sum_total_payout_map =[];
		 $bet_details_map = [];

		 foreach ($player_total_bets_per_game as $v) {

			 //make bet details array
			 $bet_details_map[$v['player_id']][$v['game_platform_id']]= array('game_platform_id' => $v['game_platform_id'] , 'total_bet' => $this->currencyFormatter($v['total_bet']) , 'total_win' => $this->currencyFormatter($v['total_win']) , 'total_loss' => $this->currencyFormatter($v['total_loss']) ,'total_payout' => $this->currencyFormatter($v['total_payout'])  ) ;
			 //array push every games bet per player
			 $sum_total_bets_map[$v['player_id']][] = $v['total_bet'] ;
			 $sum_total_wins_map[$v['player_id']][] = $v['total_win'] ;
			 $sum_total_loss_map[$v['player_id']][] = $v['total_loss'] ;
			 $sum_total_payout_map[$v['player_id']][] = $v['total_payout'] ;
			 //add the bet items to get the total bets for all player game
			 $player_total_games_bet = $this->currencyFormatter(array_sum($sum_total_bets_map[$v['player_id']]));
			 $player_total_games_win = $this->currencyFormatter(array_sum($sum_total_wins_map[$v['player_id']]));
			 $player_total_games_loss = $this->currencyFormatter(array_sum($sum_total_loss_map[$v['player_id']]));
			 $player_total_games_payout = $this->currencyFormatter(array_sum($sum_total_payout_map[$v['player_id']]));
			 //construct now the player row
			 $player_row = [];
			 $player_row['player_id'] = $v['player_id'];
			 $player_row['username'] = $v['username'];
			 $player_row['bet_details'] =  $bet_details_map[$v['player_id']];
			 $player_row['sum_total_bets'] = $player_total_games_bet;
			 $player_row['sum_total_wins'] = $player_total_games_win;
			 $player_row['sum_total_loss'] = $player_total_games_loss;
			 $player_row['sum_total_payout'] = $player_total_games_payout;
			 // add on main players map for json output
			 $player_total_bets_per_game_map[$v['player_id']] =  $player_row;
			 // make selective table headers  by making map -> associative override
			 $game_platform_header_map[$v['game_platform_id']] = $v['game_platform_id'];

		 }
		 array_walk($summary[0], function (&$value) {
			 $value = round(floatval($value), 2);
		 });

		 $result['player_total_bets_per_game'] = $player_total_bets_per_game_map;
		 $result['game_platform_header_map'] = $game_platform_header_map;
		 $result['summary'] = $summary;

		 return $result;
	 }

	 /**
	 * detail: get Kingrich Data Send Scheduler
	 *
	 * @param string transaction_batch_id
	 * @param datetime create_date
	 * @return json
	 */
	public function kingrichSchedulerReport($request, $is_export = false ) {

        $this->load->library(['data_tables','permissions']);
		$i = 0;

		$input = $this->data_tables->extra_search($request);
		$kingrich_scheduler_status = $this->utils->getConfig('kingrich_scheduler_status');
		$edit_kingrich_data_scheduler = $this->permissions->checkPermissions('edit_kingrich_data_scheduler');

		$columns = array(
			array(
				'alias' => 'id',
				'select' => 'kingrich_send_data_scheduler.id',
				'formatter' => function($d, $row) use ($is_export){
						return $d;
				},
			),
			array(
				'dt' => $i++,
				'alias' => 'date_process',
				'select' => 'CONCAT_WS(" to ", kingrich_send_data_scheduler.date_from,kingrich_send_data_scheduler.date_to)',
				'formatter' => function($d, $row) use ($is_export){
						return $d;
				},
				'name' => lang('Date to Process'),
			),
			array(
				'dt' => $i++,
				'alias' => 'currency',
				'select' => 'kingrich_send_data_scheduler.currency',
				'formatter' => function($d, $row) use ($is_export){
						return ($d) ? : lang('All');
				},
				'name' => lang('Currency'),
			),
			array(
				'dt' => $i++,
				'alias' => 'status',
				'select' => 'kingrich_send_data_scheduler.status',
				'formatter' => function($d, $row) use ($is_export , $kingrich_scheduler_status){
						return $kingrich_scheduler_status[$d]['label'];
				},
				'name' => lang('Status'),
			),
			array(
				'dt' => $i++,
				'alias' => 'created_by',
				'select' => 'kingrich_send_data_scheduler.created_by',
				'formatter' => function($d, $row) use ($is_export){
						return $d;
				},
				'name' => lang('Created By'),
			),
			array(
				'dt' => $i++,
				'alias' => 'created_at',
				'select' => 'kingrich_send_data_scheduler.created_at',
				'formatter' => function($d, $row) use ($is_export){
						return $d;
				},
				'name' => lang('Date Created'),
			),
			array(
				'dt' => $i++,
				'alias' => 'action',
				'select' => 'kingrich_send_data_scheduler.updated_at',
				'formatter' => function($d, $row) use ($is_export,$edit_kingrich_data_scheduler){
						if($is_export){
							return lang('N/A');
						}

						$output = '';

						if($edit_kingrich_data_scheduler){
							if($row['status'] == self::PENDING || $row['status'] == self::PAUSED) {
								$output .= '<a href="javascript:void(0);" onclick="return update_status('.$row['id'].' , '.self::ONGOING.' , '."'".lang('Are you sure you want to start?')."'".');"><span title="' . lang('Start') . '" class="glyphicon glyphicon-play"></span></a>';
							}

							if($row['status'] == self::PENDING || $row['status'] == self::PAUSED) {
								$output .= '<a href="javascript:void(0);" onclick="return update_status('.$row['id'].' , '.self::STOPPED.' , '."'".lang('Once you stop this schedule, you cannot start it again. Are you sure you want to stop?')."'".');"><span style="color:#990000;" title="' . lang('Stop') . '" class="glyphicon glyphicon-stop"></span></a>';
							}

							if($row['status'] == self::ONGOING) {
								$output .= '<a href="javascript:void(0);" onclick="return update_status('.$row['id'].' , '.self::PAUSED.' , '."'".lang('Are you sure you want to pause?')."'".');"><span style="color:#ff3333;" title="' . lang('Pause') . '" class="glyphicon glyphicon-pause"></span></a>';
							}

							if($row['status'] == self::PENDING) {
								$output .= '<a href="javascript:void(0);" onclick="modal('."'/marketing_management/kingrich_load_add_item/". $row['id'] ."'".' , '."'".lang('Edit Schedule')."'".');"><span title="' . lang('lang.edit') . '" class="glyphicon glyphicon-edit"></span></a>';
							}
						}

						if($row['status'] != self::PENDING) {
							$output .= '<a href="javascript:void(0);" onclick="modal('."'/marketing_management/kingrich_load_scheduler_logs/". $row['id'] ."'".' , '."'".lang('API Response Logs')."'".');"><span title="' . lang('lang.info') . '" class="glyphicon glyphicon-info-sign"></span></a>';
						}
						if(empty($output)) {
							$output = lang('role.nopermission');
						}


						return $output;
				},
				'name' => lang('Action'),
			),
		);



		$table = 'kingrich_send_data_scheduler';

		$joins = array();

		# START PROCESS SEARCH FORM #################################################################################################################################################
		$where = array();
		$values = array();
		$group_by=[];
        $having=[];

		/*if (isset($input['by_date_from'])) {
			$where[] = "(date_from >= ? and date_from <= ?) or (date_to >= ? and date_to <= ?)";
			$values[] = $input['by_date_from'];
			$values[] = $input['by_date_to'];
			$values[] = $input['by_date_from'];
			$values[] = $input['by_date_to'];
		}*/

		if (isset($input['by_date_from']) && isset($input['by_date_to'])) {

			$date_from_str = $input['by_date_from'];
			$date_to_str = $input['by_date_to'];

			$by_date_from = new DateTime($date_from_str);
            $by_date_to = new DateTime($date_to_str);

            $where[] = "((date_from >= ? AND date_from <= ?) OR (date_to >= ? AND date_to <= ?))";
			$values[] = $this->utils->formatDateTimeForMysql($by_date_from);
			$values[] = $this->utils->formatDateTimeForMysql($by_date_to);
			$values[] = $this->utils->formatDateTimeForMysql($by_date_from);
			$values[] = $this->utils->formatDateTimeForMysql($by_date_to);

		}

		if (isset($input['by_status'])) {
			if(!empty($input['by_status'])){
				$where[] = "status = ?";
				$values[] = $input['by_status'];
			}
		}

		if (isset($input['by_currency'])) {
			if(!empty($input['by_currency'])){
				$where[] = "currency = ?";
				$values[] = $input['by_currency'];
			}
		}


		# END PROCESS SEARCH FORM #################################################################################################################################################
		if($is_export){
            $this->data_tables->options['is_export']=true;
            // $this->data_tables->options['only_sql']=true;
            if(empty($csv_filename)){
                $csv_filename=$this->utils->create_csv_filename(__FUNCTION__);
            }
            $this->data_tables->options['csv_filename']=$csv_filename;
        }

        $distinct=true;
        $external_order=[];
        $not_datatable='';
		$countOnlyField='kingrich_send_data_scheduler.id';

		$result = $this->data_tables->get_data($request, $columns, $table, $where, $values, $joins, $group_by, $having,	 $distinct, $external_order, $not_datatable, $countOnlyField);

		if($is_export){
		    //drop result if export
			return $csv_filename;
		}

		return $result;
	}

	/**
	 * detail: get Kingrich Data Send Scheduler Summary Logss
	 *
	 * @param string transaction_batch_id
	 * @param datetime create_date
	 * @return json
	 */
	public function kingrichSchedulerSummarLogs($request, $is_export = false ) {

        $this->load->library(['data_tables']);
		$i = 0;

		$input = $this->data_tables->extra_search($request);

		$columns = array(
			array(
				'alias' => 'id',
				'select' => 'kingrich_scheduler_logs.id',
				'formatter' => function($d, $row) use ($is_export){
						return $d;
				},
			),
			array(
				'dt' => $i++,
				'alias' => 'created_at',
				'select' => 'kingrich_scheduler_logs.created_at',
				'formatter' => function($d, $row) use ($is_export){
						return $d;
				},
				'name' => lang('Created Date'),
			),
			array(
				'dt' => $i++,
				'alias' => 'batch_transaction_id',
				'select' => 'kingrich_scheduler_logs.batch_transaction_id',
				'formatter' => function($d, $row) use ($is_export){
						return $d;
				},
				'name' => lang('Batch Transaction ID'),
			),
			array(
				'dt' => $i++,
				'alias' => 'status',
				'select' => 'kingrich_api_logs.status',
				'formatter' => function($d, $row) use ($is_export){
						return ($d) ? : lang('N/A');
				},
				'name' => lang('Status'),
			),
			array(
				'dt' => $i++,
				'alias' => 'total',
				'select' => 'kingrich_scheduler_logs.total',
				'formatter' => function($d, $row) use ($is_export){
						return $d;
				},
				'name' => lang('Total'),
			)
		);



		$table = 'kingrich_scheduler_logs';

		$joins = array();
		$joins['kingrich_api_logs'] = 'kingrich_api_logs.batch_transaction_id = kingrich_scheduler_logs.batch_transaction_id';

		# START PROCESS SEARCH FORM #################################################################################################################################################
		$where = array();
		$values = array();
		$group_by=[];
        $having=[];

		if (isset($input['by_scheduler_id'])) {
			if(!empty($input['by_scheduler_id'])){
				$where[] = "scheduler_id = ?";
				$values[] = $input['by_scheduler_id'];
			}
		}


		# END PROCESS SEARCH FORM #################################################################################################################################################
		if($is_export){
            $this->data_tables->options['is_export']=true;
            // $this->data_tables->options['only_sql']=true;
            if(empty($csv_filename)){
                $csv_filename=$this->utils->create_csv_filename(__FUNCTION__);
            }
            $this->data_tables->options['csv_filename']=$csv_filename;
        }

        $distinct=true;
        $external_order=[];
        $not_datatable='';
		$countOnlyField='kingrich_scheduler_logs.id';

		$result = $this->data_tables->get_data($request, $columns, $table, $where, $values, $joins, $group_by, $having,	 $distinct, $external_order, $not_datatable, $countOnlyField);

		if($is_export){
		    //drop result if export
			return $csv_filename;
		}

		$summary = $this->data_tables->summary($request, $table, $joins, 'SUM(kingrich_scheduler_logs.total) overall_total', null, $columns, $where, $values);


		$result['summary'] = $summary;

		return $result;
	}

	/**
	 * detail: get Kingrich Data Send Scheduler Summary Logss
	 *
	 * @param string transaction_batch_id
	 * @param datetime create_date
	 * @return json
	 */
	public function kycC6AcurisByPlayerReport($request, $is_export = false ) {

        $this->load->library(['data_tables']);
		$i = 0;

		$input = $this->data_tables->extra_search($request);
		$columns = array(
			array(
				'alias' => 'id',
				'select' => 'acuris_logs.id',
				'formatter' => function($d, $row) use ($is_export){
						return $d;
				},
			),
			array(
				'dt' => $i++,
				'alias' => 'created_at',
				'select' => 'acuris_logs.created_at',
				'formatter' => function($d, $row) use ($is_export){
						return $d;
				},
				'name' => lang('Date'),
			),
			array(
				'dt' => $i++,
				'alias' => 'score',
				'select' => 'acuris_logs.score',
				'formatter' => function($d, $row) use ($is_export){
						return ($d) ? $d : lang('N/A');
				},
				'name' => lang('Score'),
			),
			array(
				'dt' => $i++,
				'alias' => 'person_id',
				'select' => 'acuris_logs.person_id',
				'formatter' => function($d, $row) use ($is_export){
						return ($d) ? $d : lang('N/A');
				},
				'name' => lang('Person ID'),
			),
			array(
				'dt' => $i++,
				'alias' => 'pep',
				'select' => 'acuris_logs.is_pep',
				'formatter' => function($d, $row) use ($is_export){
						return (bool)$d;
				},
				'name' => lang('PEP'),
			),
			array(
				'dt' => $i++,
				'alias' => 'previous_sanctions',
				'select' => 'acuris_logs.is_sanctions_previous',
				'formatter' => function($d, $row) use ($is_export){
						return (bool)$d;
				},
				'name' => lang('Previous Sanctions'),
			),
			array(
				'dt' => $i++,
				'alias' => 'current_sanctions',
				'select' => 'acuris_logs.is_sanctions_current',
				'formatter' => function($d, $row) use ($is_export){
						return (bool)$d;
				},
				'name' => lang('Current Sanctions'),
			),
			array(
				'dt' => $i++,
				'alias' => 'law_enforcement',
				'select' => 'acuris_logs.is_law_enforcement',
				'formatter' => function($d, $row) use ($is_export){
						return (bool)$d;
				},
				'name' => lang('Law Enforcement'),
			),
			array(
				'dt' => $i++,
				'alias' => 'financial_regulator',
				'select' => 'acuris_logs.is_financial_regulator',
				'formatter' => function($d, $row) use ($is_export){
						return (bool)$d;
				},
				'name' => lang('Financial Regulator'),
			),
			array(
				'dt' => $i++,
				'alias' => 'insolvency',
				'select' => 'acuris_logs.is_insolvent',
				'formatter' => function($d, $row) use ($is_export){
						return (bool)$d;
				},
				'name' => lang('Insolvency'),
			),
			array(
				'dt' => $i++,
				'alias' => 'disqualified_director',
				'select' => 'acuris_logs.is_disqualified_director',
				'formatter' => function($d, $row) use ($is_export){
						return (bool)$d;
				},
				'name' => lang('Disqualified Director'),
			),
			array(
				'dt' => $i++,
				'alias' => 'adverse_media',
				'select' => 'acuris_logs.is_adverse_media',
				'formatter' => function($d, $row) use ($is_export){
						return (bool)$d;
				},
				'name' => lang('Adverse Media'),
			),
			array(
				'dt' => $i++,
				'alias' => 'details',
				'select' => 'acuris_logs.id',
				'formatter' => function($d, $row) use ($is_export){
					if($row['score']){
						return '<a href="javascript:void(0)"><i class="glyphicon glyphicon-question-sign" onclick="return view_details('.$row['id'].');">';
					} else {
						return lang('N/A');
					}
				},
				'name' => lang('Details'),
			),
			array(
				'dt' => $i++,
				'alias' => 'generated_by',
				'select' => 'acuris_logs.generated_by',
				'formatter' => function($d, $row) use ($is_export){
						$generatedBy = $this->users->getUsernameById($d);
						return (!$d) ? lang('System Generated') :lang('Manually Generated By:').' '.$generatedBy;
				},
				'name' => lang('Generated By'),
			)
		);



		$table = 'acuris_logs';

		$joins = array();

		# START PROCESS SEARCH FORM #################################################################################################################################################
		$where = array();
		$values = array();
		$group_by=[];
        $having=[];

		if (isset($input['by_player_id'])) {
			if(!empty($input['by_player_id'])){
				$where[] = "player_id = ?";
				$values[] = $input['by_player_id'];
			}
		}


		# END PROCESS SEARCH FORM #################################################################################################################################################
		if($is_export){
            $this->data_tables->options['is_export']=true;
            // $this->data_tables->options['only_sql']=true;
            if(empty($csv_filename)){
                $csv_filename=$this->utils->create_csv_filename(__FUNCTION__);
            }
            $this->data_tables->options['csv_filename']=$csv_filename;
        }

        $distinct=true;
        $external_order=[];
        $not_datatable='';
		$countOnlyField='acuris_logs.id';

		$result = $this->data_tables->get_data($request, $columns, $table, $where, $values, $joins, $group_by, $having,	 $distinct, $external_order, $not_datatable, $countOnlyField);

		if($is_export){
		    //drop result if export
			return $csv_filename;
		}

		return $result;
	}

	/**
	 * detail: get vr game report for a certain player
	 *
	 * @param array $request
	 * @param int $player_id oneworks_game_report player_id
	 *
	 * @return array
	 */
	public function vrGameReports($request, $player_id = null, $is_export = false) {
		$this->load->library(array('data_tables'));
		$this->load->model(array('player_model','game_logs'));
		$input = $this->data_tables->extra_search($request);
		$table = 'vr_game_report';

		$joins = array();
		$where = array();
		$values = array();
		$group_by = array();
		$having = array();

		$joins['game_type'] = 'game_type.id = vr_game_report.game_type_id';
		$joins['game_description'] = 'game_description.id = vr_game_report.game_description_id';
		$joins['external_system'] = 'external_system.id = vr_game_report.game_platform_id';
		$joins['player'] = 'player.playerId = vr_game_report.player_id';
		$joins['affiliates'] = 'affiliates.affiliateId = player.affiliateId';

		$group_by_field = null;
		$show_game_platform = false;
		$show_game_type = false;
		$show_game = false;
		$show_player = false;
		$this->utils->debug_log('the input ------>', json_encode($input));

		if (isset($input['group_by'])) {
			$group_by_field = $input['group_by'];
			switch ($input['group_by']) {
			case 'game_platform_id':
				$group_by[] = 'vr_game_report.game_platform_id';
				$show_game_platform = true;
				break;
			case 'game_type_id':
				$group_by[] = 'vr_game_report.game_type_id';
				$show_game_platform = true;
				$show_game_type = true;
				break;
			case 'game_description_id':
				$group_by[] = 'vr_game_report.game_description_id';
				$show_game_platform = true;
				$show_game_type = true;
				$show_game = true;
				break;
			case 'player_id':
				$group_by[] = 'vr_game_report.player_id';
				$show_player = true;
				break;
			case 'aff_id':
				$group_by[] = 'vr_game_report.affiliate_id';
				$show_player = true;
				break;
			case 'agent_id':
				$group_by[] = 'vr_game_report.agent_id';
				$show_player = true;
				break;
			case 'game_type_and_player':
				$group_by[] = 'vr_game_report.player_id, vr_game_report.game_type_id';
				$show_game_platform = true;
				$show_game_type = true;
				$show_player = true;
				break;
			case 'game_platform_and_player':
				$group_by[] = 'vr_game_report.player_id, vr_game_report.game_platform_id';
				$show_game_platform = true;
				$show_player = true;
				break;
			case 'game_description_and_player':
				$group_by[] = 'vr_game_report.player_id, vr_game_report.game_description_id';
				$show_game_platform = true;
				$show_game_type = true;
				$show_game = true;
				$show_player = true;
				break;
			}
		}
		if (isset($input['username'])) {
			$where[] = "vr_game_report.player_username = ?";
			$values[] = $input['username'];
		}

		# DEFINE TABLE COLUMNS ########################################################################################################################################################################
		$i = 0;
		$columns = array(
			array(
				'alias' => 'player_playerId',
				'select' => 'player.playerId'
			),

			array(
				'alias' => 'player_levelName',
				'select' => 'player.levelName'
			),
			array(
				'alias' => 'player_groupName',
				'select' => 'player.groupName'
			),
			array(
				'alias' => 'affiliates_affiliateId',
				'select' => 'affiliates.affiliateId'
			),
			array(
				'alias' => 'external_system_id',
				'select' => 'external_system.id'
			),
			array(
				'alias' => 'game_description_id',
				'select' => $show_game ? 'game_description.id' : '"N/A"',
			),
			array(
				'alias' => 'game_type_id',
				'select' => $show_game_type ? 'game_type.id' : '"N/A"',
			),
			array(
				'dt' => $i++,
				'alias' => 'system_code',
				'select' => $show_game_platform ? 'external_system.system_code' : '"N/A"',
				'formatter' => function($d, $row) use ($is_export)  {

                     if ($is_export) {
                     	return $d;
                     }else{
                     	return $d. '<p class=" hide gamePlatformId">'. $row['external_system_id']. '</p>';
                     }

				},
				'name' => lang('System Code'),
			),
			array(
				'dt' => $i++,
				'alias' => 'game_type_lang',
				'select' => $show_game_type ? 'game_type.game_type_lang' : '"N/A"',
				'formatter' => 'languageFormatter',
				'name' => lang('Game Type Language'),
			),
			array(
				'dt' => $i++,
				'alias' => 'game_name',
				'select' => $show_game ? 'game_description.game_name' : '"N/A"',
				'formatter' => 'languageFormatter',
				'name' => lang('Game Name'),
			),
			array(
				'dt' => $i++,
				'alias' => 'player_username',
				 //'select' => $show_player ? '( CASE WHEN affiliates.username IS NULL THEN player.username ELSE CONCAT(player.username, \' (\', affiliates.username,  \')\' ) END )' : '"N/A"',
				'select' => $show_player ? 'player.username' : '"N/A"',
				'formatter' => function($d, $row) use ($is_export) {

					if ($this->utils->isAffSubProject() && $this->utils->isEnabledFeature('masked_player_username_on_affiliate')) {
						$d = $this->utils->keepOnlyString($d, 4);
					}

					if($d != 'N/A') {
						return $is_export ? $d : '<a href="/player_management/userInformation/' . $row['player_playerId'] . '" target="_blank">' . $d . '</a>';
					}
					return $d;
				},
				'name' => lang('Username'),
			),

			array(
				'dt' => $i++,
				'alias' => 'player_level',
				'select' => $show_player ? 'player.levelId' : '"N/A"',
				'formatter' => function($d, $row){
					if($d != 'N/A') {
						return lang($row['player_groupName']).' - '.lang($row['player_levelName']);
					}
		  			return $d;

				},
				'name' => lang('Player Level'),

			),
			array(
				'dt' => $i++,
				'alias' => 'affiliate',
				'select' => 'affiliates.username',
				'formatter' => function($d, $row) use ($is_export) {

					if ($this->utils->isEnabledFeature('masked_affiliate_username_on_affiliate')) {
						$d = $this->utils->keepOnlyString($d, 4);
					}

					if($d) {
						if($this->utils->isFromHost('aff')) {
							return trim(trim($d), ',') ?: lang('lang.norecyet');
						}
						return $is_export ? $d : '<a href="/affiliate_management/userInformation/' . $row['affiliates_affiliateId'] . '" target="_blank">' . $d . '</a>';
					} else {

					}
				},
				'name' => lang('aff.as03'),
			),
			array(
				'dt' => $i++,
				'alias' => 'total_player',
				'select' => 'COUNT(DISTINCT vr_game_report.player_id)',
				'formatter' => function ($d, $row)  use ($is_export)  {
					//return $is_export ? $d : '<a href="#" class="totalPlayers">' . $d . '</a>';
					return $d;
				},
				'name' => lang('Total Player'),
			),
			array(
				'dt' => $i++,
				'alias' => 'total_bet',
				'select' => 'SUM(vr_game_report.total_bets)',
				'formatter' =>  function($d, $row) use ($is_export) {
					return $this->currencyFormatter($d, $is_export);
				},
				'name' => lang('Total Bet'),
			),

			array(
				'dt' => $i++,
				'alias' => 'total_gain',
				'select' => 'SUM(vr_game_report.total_wins)',
				'formatter' =>  function($d, $row) use ($is_export) {
					return $this->currencyFormatter($d, $is_export);
				},
				'name' => lang('Total Gain'),
			),
			array(
				'dt' => $i++,
				'alias' => 'total_loss',
				'select' => 'SUM(vr_game_report.total_loss)',
				'formatter' =>  function($d, $row) use ($is_export) {
					return $this->currencyFormatter($d, $is_export);
				},
				'name' => lang('Total Loss'),
			),
			array(
				'dt' => $i++,
				'alias' => 'total_payout',
				'select' => 'SUM(vr_game_report.total_bets - vr_game_report.total_wins)',
				'formatter' =>  function($d, $row) use ($is_export) {
					return $this->currencyFormatter($d, $is_export);
				},
				'name' => lang('Payout'),
			),
			array(
				'dt' => $i++,
				'alias' => 'rate',
				'select' => 'SUM(vr_game_report.total_bets-vr_game_report.total_wins)/SUM(vr_game_report.total_bets)',
				'formatter' => 'percentageFormatter',
				'name' => lang('Rate'),
			),
		);

		# FILTER ######################################################################################################################################################################################

		if (isset($input['datetime_from'], $input['datetime_to'])) {
			$where[] ='vr_game_report.game_date >= ?';
			$where[] ='vr_game_report.game_date <= ?';
			$values[] = $input['datetime_from'];
			$values[] = $input['datetime_to'];
		}
		// $where[] = "(vr_game_report.status IN ('won','draw','lose','half won','half lose') OR vr_game_report.status IS NULL)";//get only settled and not updated status which is null
		$result = $this->data_tables->get_data($request, $columns, $table, $where, $values, $joins, $group_by, $having, false);

		$result['summary'] = array();

		$total_payout_rate =  'SUM(vr_game_report.total_bets-vr_game_report.total_wins)/SUM(vr_game_report.total_bets) * 100 total_payout_rate' ;
		$total_bet =  'SUM(vr_game_report.total_bets) total_bet ';
		$total_ave_count =  'COUNT(vr_game_report.total_bets) total_ave_count ';
     	$total_ave_bet = 'SUM(vr_game_report.total_bets) /COUNT(vr_game_report.total_bets)  total_ave_bet ';


		$summary = $this->data_tables->summary($request, $table, $joins, 'COUNT(DISTINCT vr_game_report.player_id) total_player, '.$total_bet.','.$total_ave_bet.','.$total_ave_count.'  ,SUM(vr_game_report.total_wins) as total_win, SUM(vr_game_report.total_loss) total_loss,SUM(vr_game_report.total_bets-vr_game_report.total_wins) total_payout,'.$total_payout_rate.'', null, $columns, $where, $values);

		$group_by2=[];
		$group_by2[] = 'vr_game_report.player_id, vr_game_report.game_platform_id';
		$player_total_bets_per_game = $this->data_tables->summary($request, $table, $joins, 'DISTINCT vr_game_report.player_id player_id , player.username, vr_game_report.game_platform_id, '.$total_bet.', SUM(vr_game_report.total_wins) as total_win, SUM(vr_game_report.total_loss) total_loss , SUM(vr_game_report.total_bets-vr_game_report.total_wins) total_payout,', $group_by2, $columns, $where, $values);


		$player_total_bets_per_game_map = [];
		$game_platform_header_map =[];
        $sum_total_bets_map =[];
        $sum_total_wins_map =[];
        $sum_total_loss_map =[];
        $sum_total_payout_map =[];
        $bet_details_map = [];

        foreach ($player_total_bets_per_game as $v) {

            //make bet details array
			$bet_details_map[$v['player_id']][$v['game_platform_id']]= array('game_platform_id' => $v['game_platform_id'] , 'total_bet' => $this->currencyFormatter($v['total_bet']) , 'total_win' => $this->currencyFormatter($v['total_win']) , 'total_loss' => $this->currencyFormatter($v['total_loss']) ,'total_payout' => $this->currencyFormatter($v['total_payout'])  ) ;
            //array push every games bet per player
			$sum_total_bets_map[$v['player_id']][] = $v['total_bet'] ;
			$sum_total_wins_map[$v['player_id']][] = $v['total_win'] ;
			$sum_total_loss_map[$v['player_id']][] = $v['total_loss'] ;
			$sum_total_payout_map[$v['player_id']][] = $v['total_payout'] ;
			//add the bet items to get the total bets for all player game
            $player_total_games_bet = $this->currencyFormatter(array_sum($sum_total_bets_map[$v['player_id']]));
            $player_total_games_win = $this->currencyFormatter(array_sum($sum_total_wins_map[$v['player_id']]));
            $player_total_games_loss = $this->currencyFormatter(array_sum($sum_total_loss_map[$v['player_id']]));
            $player_total_games_payout = $this->currencyFormatter(array_sum($sum_total_payout_map[$v['player_id']]));
             //construct now the player row
            $player_row = [];
            $player_row['player_id'] = $v['player_id'];
            $player_row['username'] = $v['username'];
            $player_row['bet_details'] =  $bet_details_map[$v['player_id']];
            $player_row['sum_total_bets'] = $player_total_games_bet;
            $player_row['sum_total_wins'] = $player_total_games_win;
            $player_row['sum_total_loss'] = $player_total_games_loss;
            $player_row['sum_total_payout'] = $player_total_games_payout;
            // add on main players map for json output
			$player_total_bets_per_game_map[$v['player_id']] =  $player_row;
			// make selective table headers  by making map -> associative override
			$game_platform_header_map[$v['game_platform_id']] = $v['game_platform_id'];

		}
		array_walk($summary[0], function (&$value) {
			$value = round(floatval($value), 2);
		});

        $result['player_total_bets_per_game'] = $player_total_bets_per_game_map;
        $result['game_platform_header_map'] = $game_platform_header_map;
		$result['summary'] = $summary;
		//$result['summary'] = [];

		return $result;
	}

	/**
	 * detail: get vr game report for a certain player
	 *
	 * @param array $request
	 * @param int $player_id oneworks_game_report player_id
	 *
	 * @return array
	 */
	public function pngFreeGameOfferReport($request, $player_id = null, $is_export = false) {
		$this->load->library(array('data_tables'));
		$this->load->model(array('player_model','game_logs'));
		$input = $this->data_tables->extra_search($request);
		$table = 'png_free_game_offer';

		$joins = array();
		$where = array();
		$values = array();
		$group_by = array();
		$having = array();

		$group_by_field = null;
		$show_game_platform = false;
		$show_game_type = false;
		$show_game = false;
		$show_player = false;
		$this->utils->debug_log('the input ------>', json_encode($input));

		# DEFINE TABLE COLUMNS ########################################################################################################################################################################
		$i = 0;
		$columns = array(
			array(
				'dt' => $i++,
				'select' => 'RequestId',
				'alias' => 'RequestId',
				'formatter' => function ($d, $row) use ($is_export) {
					if ($is_export) {
						return $d;
					} else {
						$curr = date('Y-m-d H:i:s');

						if ($row['ExpireTime'] > $curr) {
							$output = '<a href="cancelFreeGameOffer/' . $d . '" title="' . lang('Cancel Free Game Offer') . '" class="delete-png" id="delete-png-' . $d . '"><span style="color:#ff3333" class="glyphicon glyphicon-trash"></span></a>';
							return $output;
						}
					}
				}
			),
			array(
				'dt' => $i++,
				'select' => 'RequestId',
				'alias' => 'RequestId'
			),
			array(
				'dt' => $i++,
				'select' => 'Username',
				'alias' => 'Username'
			),
			array(
				'dt' => $i++,
				'select' => 'GameNameList',
				'alias' => 'GameNameList',
				'format' => function ($d, $row) {
					return $d;
				}
			),
			array(
				'dt' => $i++,
				'select' => 'IF(Line IS NULL, "N/A", Line)',
				'alias' => 'Line'
			),
			array(
				'dt' => $i++,
				'select' => 'IF(Coins IS NULL, "N/A", Coins)',
				'alias' => 'Coins'
			),
			array(
				'dt' => $i++,
				'select' => 'IF(Denomination IS NULL, "N/A", Denomination)',
				'alias' => 'Denomination'
			),
			array(
				'dt' => $i++,
				'select' => 'IF(Rounds IS NULL, "N/A", Rounds)',
				'alias' => 'Rounds'
			),
			array(
				'dt' => $i++,
				'select' => 'IF(Turnover IS NULL, "N/A", Turnover)',
				'alias' => 'Turnover'
			),
			array(
				'dt' => $i++,
				'select' => 'ExpireTime',
				'alias' => 'ExpireTime',
				'formatter' => 'dateTimeFormatter',
			),
			array(
				'dt' => $i++,
				'select' => 'created_at',
				'alias' => 'created_at',
				'formatter' => 'dateTimeFormatter',
			),
		);

		# FILTER ######################################################################################################################################################################################
		#

		if (isset($input['datetime_from'], $input['datetime_to'])) {
			$where[] ='png_free_game_offer.created_at >= ?';
			$where[] ='png_free_game_offer.created_at <= ?';
			$where[] ='png_free_game_offer.status = ?';
			$values[] = $input['datetime_from'] . ' 00:00:00';
			$values[] = $input['datetime_to'] . ' 23:59:59';
			$values[] = 'approve';

			if (!empty($input['gamesSearch']) && $input['gamesSearch'] != 'false') {
				$where[] ='png_free_game_offer.GameIdList LIKE ?';
				$values[] = '%' . $input['gamesSearch'] . '%';
			}
		}
		$result = $this->data_tables->get_data($request, $columns, $table, $where, $values, $joins, $group_by, $having, false);

		return $result;
	}


	public function gameApiUpdateHistory($request, $is_export = false) {
		$db_false = 0;
		$db_true = 1;

		$this->load->library(array('data_tables'));
		$input = $this->data_tables->extra_search($request);

		$i = 0;
		$where = array();
		$values = array();
		$columns = array();
		$joins = array();
		$joins['adminusers'] = 'adminusers.userId = game_api_update_history.user_id';

		$columns[] = array(
			'dt' => $i++,
			'select' => 'game_api_update_history.id',
			'alias' => 'id',
			'name' => lang('history.id'),
			'formatter' => function ($d, $row){
				return $d;
			}
		);
		$columns[] = array(
			'dt' => $i++,
			'select' => 'game_api_update_history.action',
			'alias' => 'action',
			'name' => lang('lang.action'),
			'formatter' => function ($d, $row) use ($is_export) {

				switch ($d) {
					case External_system::GAME_API_HISTORY_ACTION_ADD:
					return  $is_export === true ? lang('Add Credential') : '<span class="label label-success">'.lang('Add Credential') .'</span>';
					break;
					case External_system::GAME_API_HISTORY_ACTION_UPDATE:
					return  $is_export === true ? lang('Update Credential') : '<span class="label label-primary">'.lang('Update Credential').'</span>';
					break;
					case External_system::GAME_API_HISTORY_ACTION_DELETE:
					return  $is_export === true ? lang('Delete Credential') : '<span class="label label-danger">'.lang('Delete Credential').'</span>';
					break;
					case External_system::GAME_API_HISTORY_ACTION_UNDER_MAINTENANCE:
					return  $is_export === true ? lang('Under Maintenance') : '<span class="label label-warning">'.lang('Under Maintenance').'</span>';
					break;
					case External_system::GAME_API_HISTORY_ACTION_FINISH_MAINTENANCE:
					return  $is_export === true ? lang('Finish Maintenance') : '<span class="label label-info">'.lang('Finish Maintenance').'</span>';
					break;
					case External_system::GAME_API_HISTORY_ACTION_BLOCKED:

					if($this->utils->getConfig('use_new_sbe_color')){
						return  $is_export === true ? lang('Blocked') : '<span class="btn btn-chestnutrose btn-xs">'.lang('Blocked').'</span>';
					}else{
						return  $is_export === true ? lang('Blocked') : '<span class="label label-danger">'.lang('Blocked').'</span>';
					}
					break;

					case External_system::GAME_API_HISTORY_ACTION_UNBLOCKED:


					if($this->utils->getConfig('use_new_sbe_color')){
						return  $is_export === true ? lang('Unblocked') : '<span class="btn btn-scooter btn-xs">'.lang('Unblocked').'</span>';
					}else{
						return  $is_export === true ? lang('Unblocked') : '<span class="label label-info">'.lang('Unblocked').'</span>';
					}
					break;

					case External_system::GAME_API_HISTORY_ACTION_PAUSED_SYNC:
					return  $is_export === true ? lang('Paused Sync') : '<span class="label label-default">'.lang('Paused Sync').'</span>';
					break;
					case External_system::GAME_API_HISTORY_ACTION_RESUMED_SYNC:
					return  $is_export === true ? lang('Resumed Sync') : '<span class="label label-info">'.lang('Resumed Sync').'</span>';
					break;
				}
			}

		);
		$columns[] = array(
			'dt' => $i++,
			'select' => 'adminusers.username',
			'alias' => 'user_id',
			'name' => lang('sys.updatedby'),
			'formatter' => function ($d, $row) {
				return $d ?: '-';
			},
		);
		$columns[] = array(
			'dt' => $i++,
			'select' => 'game_api_update_history.created_on',
			'alias' => 'created_on',
			'name' => lang('sys.createdon'),
			'formatter' => function ($d, $row) {
				return $d ?: '-';
			},
		);
		$columns[] = array(
			'dt' => $i++,
			'select' => 'game_api_update_history.updated_at',
			'alias' => 'updated_at',
			'name' => lang('sys.updatedon'),
			'formatter' => function ($d, $row) {
				return $d ?: '-';
			},
		);
		$columns[] = array(
			'dt' => $i++,
			'select' => 'game_api_update_history.game_platform_id',
			'alias' => 'game_platform_id',
			'name' => lang('Game Platform')
		);
		$columns[] = array(
			'dt' => $i++,
			'select' => 'game_api_update_history.live_mode',
			'alias' => 'live_mode',
			'name' => lang('sys.ga.livemode'),
			'formatter' => function ($d, $row) use ($is_export){
				if(!$is_export){
					return $d==1 ? '<i class="glyphicon glyphicon-check"></i> ' : '<i class="glyphicon glyphicon-unchecked"></i> ';
				}else{
					return $d==1 ? 1 : '0';
				}
			},
		);
		$columns[] = array(
			'dt' => $i++,
			'select' => 'game_api_update_history.status',
			'alias' => 'status',
			'name' => lang('sys.ga.status'),
			'formatter' => function ($d, $row) use ($is_export){
				if(!$is_export){
					return $d==1 ? '<i class="glyphicon glyphicon-check"></i> ' : '<i class="glyphicon glyphicon-unchecked"></i> ';
				}else{
					return $d==1 ? 1 : '0';
				}
			},
		);

		$columns[] = array(
			'dt' => $i++,
			'select' => 'game_api_update_history.maintenance_mode',
			'alias' => 'maintenance_mode',
			'name' => lang('Maintenance Mode'),
			'formatter' => function ($d, $row) use ($is_export){
				if(!$is_export){
					return $d==1 ? '<i class="glyphicon glyphicon-check"></i> ' : '<i class="glyphicon glyphicon-unchecked"></i> ';
				}else{
					return $d==1 ? 1 : '0';
				}
			},
		);
		$columns[] = array(
			'dt' => $i++,
			'select' => 'game_api_update_history.pause_sync',
			'alias' => 'pause_sync',
			'name' => lang('Pause Sync'),
			'formatter' => function ($d, $row) use ($is_export){
				if(!$is_export){
					return $d==1 ? '<i class="glyphicon glyphicon-check"></i> ' : '<i class="glyphicon glyphicon-unchecked"></i> ';
				}else{
					return $d==1 ? 1 : '0';
				}
			},
		);

		$columns[] = array(
			'dt' => $i++,
			'select' => 'game_api_update_history.system_code',
			'alias' => 'system_code',
			'name' => lang('sys.ga.systemcode'),
			'formatter' => function ($d, $row) {
				return $d ?: '-';
			},
		);
		$columns[] = array(
			'dt' => $i++,
			'select' => 'game_api_update_history.system_name',
			'alias' => 'system_name',
			'name' => lang('sys.ga.systemname')
		);
		$columns[] = array(
			'dt' => $i++,
			'select' => 'game_api_update_history.extra_info',
			'alias' => 'extra_info',
			'name' => lang('sys.ga.extrainfo'),
			'formatter' => function ($d, $row) use ($is_export){
				if(!$is_export){
					return "<div class='code-holder' style='padding:5px;width:510px;' ><pre class='pre-code-container'id='pre-code-".$row['id']."' style='word-break: normal;width:500px; font-size: 0.875em;'><code class=\"JSON hljs pre-code\">".$d ?: '-'. "</code></pre></div>";
				}else{
					return $d;
				}
			},
		);

		$columns[] = array(
			'dt' => $i++,
			'select' => 'game_api_update_history.sandbox_extra_info',
			'alias' => 'sandbox_extra_info',
			'name' => lang('sys.ga.sandboxextrainfo'),
			'formatter' => function ($d, $row) use ($is_export){
				if(!$is_export){
					return "<div class='code-holder' style='padding:5px;width:510px;' ><pre class='pre-code-container'id='pre-code-".$row['id']."' style='word-break: normal;width:500px; font-size: 0.875em;'><code class=\"JSON hljs pre-code\">".$d ?: '-'. "</code></pre></div>";
				}else{
					return $d;
				}
			},
		);
		$columns[] = array(
			'dt' => $i++,
			'select' => 'game_api_update_history.system_type',
			'alias' => 'system_type',
			'name' => lang('sys.ga.systemtype'),
			'formatter' => function ($d, $row) {
				return ($d== 1) ? lang('sys.game.api') : '-';
			},
		);
		$columns[] = array(
			'dt' => $i++,
			'select' => 'game_api_update_history.last_sync_datetime',
			'alias' => 'last_sync_datetime',
			'name' => lang('sys.ga.lastsyncdt')
		);
		$columns[] = array(
			'dt' => $i++,
			'select' => 'game_api_update_history.note',
			'alias' => 'note',
			'name' => lang('sys.ga.note'),
			'formatter' => function ($d, $row) {
				return $d ?: '-';
			},
		);
		$columns[] = array(
			'dt' => $i++,
			'select' => 'game_api_update_history.last_sync_id',
			'alias' => 'last_sync_id',
			'name' => lang('sys.ga.lastsyncid'),
			'formatter' => function ($d, $row) {
				return $d ?: '-';
			},
		);
		$columns[] = array(
			'dt' => $i++,
			'select' => 'game_api_update_history.last_sync_details',
			'alias' => 'last_sync_details',
			'name' => lang('sys.ga.lastsyncdet'),
			'formatter' => function ($d, $row) {
				return $d ?: '-';
			},
		);
		$columns[] = array(
			'dt' => $i++,
			'select' => 'game_api_update_history.live_url',
			'alias' => 'live_url',
			'name' => lang('sys.ga.liveurl'),
			'formatter' => function ($d, $row) {
				return $d ?: '-';
			},
		);
		$columns[] = array(
			'dt' => $i++,
			'select' => 'game_api_update_history.sandbox_url',
			'alias' => 'sandbox_url',
			'name' => lang('sys.ga.sandboxurl'),
			'formatter' => function ($d, $row) {
				return $d ?: '-';
			},
		);
		$columns[] = array(
			'dt' => $i++,
			'select' => 'game_api_update_history.live_key',
			'alias' => 'live_key',
			'name' => lang('sys.ga.livekey'),
			'formatter' => function ($d, $row) {
				return $d ?: '-';
			},
		);
		$columns[] = array(
			'dt' => $i++,
			'select' => 'game_api_update_history.sandbox_key',
			'alias' => 'sandbox_key',
			'name' => lang('sys.ga.sandboxkey'),
			'formatter' => function ($d, $row) {
				return $d ?: '-';
			},
		);
		$columns[] = array(
			'dt' => $i++,
			'select' => 'game_api_update_history.live_secret',
			'alias' => 'live_secret',
			'name' => lang('sys.ga.livesecret'),
			'formatter' => function ($d, $row) {
				return $d ?: '-';
			},
		);
		$columns[] = array(
			'dt' => $i++,
			'select' => 'game_api_update_history.sandbox_secret',
			'alias' => 'sandbox_secret',
			'name' => lang('sys.ga.sandboxsecret'),
			'formatter' => function ($d, $row) {
				return $d ?: '-';
			},
		);
		$columns[] = array(
			'dt' => $i++,
			'select' => 'game_api_update_history.second_url',
			'alias' => 'second_url',
			'name' => lang('sys.ga.secondurl'),
			'formatter' => function ($d, $row) {
				return $d ?: '-';
			},
		);
		$columns[] = array(
			'dt' => $i++,
			'select' => 'game_api_update_history.live_account',
			'alias' => 'live_account',
			'name' => lang('sys.ga.liveacct'),
			'formatter' => function ($d, $row) use ($is_export){
				if(!$is_export){
					return $d==1 ? '<i class="glyphicon glyphicon-check"></i> ' : '<i class="glyphicon glyphicon-unchecked"></i> ';
				}else{
					return $d==1 ? 1 : '0';
				}
			},
		);
		$columns[] = array(
			'dt' => $i++,
			'select' => 'game_api_update_history.sandbox_account',
			'alias' => 'sandbox_account',
			'name' => lang('sys.ga.sandboxacct'),
			'formatter' => function ($d, $row) use ($is_export){
				if(!$is_export){
					return $d==1 ? '<i class="glyphicon glyphicon-check"></i> ' : '<i class="glyphicon glyphicon-unchecked"></i> ';
				}else{
					return $d==1 ? 1 : '0';
				}
			},
		);
		$columns[] = array(
			'dt' => $i++,
			'select' => 'game_api_update_history.class_name',
			'alias' => 'class_name',
			'name' => lang('sys.ga.classname'),
			'formatter' => function ($d, $row) {
				return $d ?: '-';
			},
		);
		$columns[] = array(
			'dt' => $i++,
			'select' => 'game_api_update_history.local_path',
			'alias' => 'local_path',
			'name' => lang('sys.ga.localpath'),
			'formatter' => function ($d, $row) {
				return $d ?: '-';
			},
		);
		$columns[] = array(
			'dt' => $i++,
			'select' => 'game_api_update_history.manager',
			'alias' => 'manager',
			'name' => lang('sys.ga.manager'),
			'formatter' => function ($d, $row) {
				return $d ?: '-';
			},
		);
		$columns[] = array(
			'dt' => $i++,
			'select' => 'game_api_update_history.game_platform_rate',
			'alias' => 'game_platform_rate',
			'name' => lang('sys.gd7') . " " . lang('sys.rate'),
			'formatter' => function ($d, $row) {
				return $d ?: '-';
			},
		);
		$columns[] = array(
			'dt' => $i++,
			'select' => 'game_api_update_history.allow_deposit_withdraw',
			'alias' => 'allow_deposit_withdraw',
			'name' => lang('Allow Deposit Withdraw'),
			'formatter' => function ($d, $row) use ($is_export){
				if(!$is_export){
					return $d==1 ? '<i class="glyphicon glyphicon-check"></i> ' : '<i class="glyphicon glyphicon-unchecked"></i> ';
				}else{
					return $d==1 ? 1 : '0';
				}
			},
		);
		$columns[] = array(
			'dt' => $i++,
			'select' => 'game_api_update_history.amount_float',
			'alias' => 'live_account',
			'name' => lang('sys.ga.amount_float'),
			'formatter' => function ($d, $row) use ($is_export){

				return $d > 0 ? $d : '0';

			},
		);

		$table ='game_api_update_history';

		if($is_export){
			$this->data_tables->options['is_export']=true;
			if(empty($csv_filename)){
				$csv_filename=$this->utils->create_csv_filename(__FUNCTION__);
			}
			$this->data_tables->options['csv_filename']=$csv_filename;
		}


		if (isset($input['search_by'])) {
			if(isset($input['date_from']) && isset($input['date_to'])){
				if($input['search_by'] == 1){
					$where[] =  "game_api_update_history.created_on >= ? AND game_api_update_history.created_on <= ?";
					$values[] = $input['date_from'];
					$values[] = $input['date_to'];
				}else{
					$where[] =  "game_api_update_history.updated_at >= ? AND game_api_update_history.updated_at <= ?";
					$values[] = $input['date_from'];
					$values[] = $input['date_to'];
				}
			}
		}
		if (isset($input['action']) && !empty($input['action'])) {
			$where[] =  "game_api_update_history.action = ?";
			$values[] =  $input['action'];
		}
		if (isset($input['game_platform_id']) && !empty($input['game_platform_id'])) {
			$where[] =  "game_api_update_history.game_platform_id = ?";
			$values[] =  $input['game_platform_id'];
		}

		$result = $this->data_tables->get_data($request, $columns, $table, $where, $values, $joins);

		if($is_export){
		    //drop result if export
			return $csv_filename;
		}
		return $result;
	}

		/**
	 * detail: get game provider game report for a certain player
	 *
	 * @param array $request
	 * @param int $player_id
	 *
	 * @return array
	 */
	public function getGameproviderReport($request, $player_id = null, $is_export = false) {
		$this->load->library(array('data_tables'));
		$this->load->model(array('player_model','game_logs'));
		$input = $this->data_tables->extra_search($request);
		$table = 'game_provider_report';

		$joins = array();
		$where = array();
		$values = array();
		$group_by = array();
		$having = array();

		$joins['game_type'] = 'game_type.id = game_provider_report.game_type_id';
		$joins['game_description'] = 'game_description.id = game_provider_report.game_description_id';
		$joins['external_system'] = 'external_system.id = game_provider_report.game_platform_id';
		$joins['player'] = 'player.playerId = game_provider_report.player_id';
		$joins['affiliates'] = 'affiliates.affiliateId = player.affiliateId';

		$group_by_field = null;
		$show_game_platform = false;
		$show_game_type = false;
		$show_game = false;
		$show_player = false;
		$this->utils->debug_log('the input ------>', json_encode($input));

		if (isset($input['group_by'])) {
			$group_by_field = $input['group_by'];
			switch ($input['group_by']) {
			case 'game_platform_id':
				$group_by[] = 'game_provider_report.game_platform_id';
				$show_game_platform = true;
				break;
			case 'game_type_id':
				$group_by[] = 'game_provider_report.game_type_id';
				$show_game_platform = true;
				$show_game_type = true;
				break;
			case 'game_description_id':
				$group_by[] = 'game_provider_report.game_description_id';
				$show_game_platform = true;
				$show_game_type = true;
				$show_game = true;
				break;
			case 'player_id':
				$group_by[] = 'game_provider_report.player_id';
				$show_player = true;
				break;
			case 'aff_id':
				$group_by[] = 'game_provider_report.affiliate_id';
				$show_player = true;
				break;
			case 'agent_id':
				$group_by[] = 'game_provider_report.agent_id';
				$show_player = true;
				break;
			case 'game_type_and_player':
				$group_by[] = 'game_provider_report.player_id, game_provider_report.game_type_id';
				$show_game_platform = true;
				$show_game_type = true;
				$show_player = true;
				break;
			case 'game_platform_and_player':
				$group_by[] = 'game_provider_report.player_id, game_provider_report.game_platform_id';
				$show_game_platform = true;
				$show_player = true;
				break;
			case 'game_description_and_player':
				$group_by[] = 'game_provider_report.player_id, game_provider_report.game_description_id';
				$show_game_platform = true;
				$show_game_type = true;
				$show_game = true;
				$show_player = true;
				break;
			}
		}
		if (isset($input['username'])) {
			$where[] = "game_provider_report.player_username = ?";
			$values[] = $input['username'];
		}

		if(isset($input['game_platform_id'])){
			$where[] = "game_provider_report.game_platform_id = ?";
			$values[] = $input['game_platform_id'];
		}

		# DEFINE TABLE COLUMNS ########################################################################################################################################################################
		$i = 0;
		$columns = array(
			array(
				'alias' => 'player_playerId',
				'select' => 'player.playerId'
			),

			array(
				'alias' => 'player_levelName',
				'select' => 'player.levelName'
			),
			array(
				'alias' => 'player_groupName',
				'select' => 'player.groupName'
			),
			array(
				'alias' => 'affiliates_affiliateId',
				'select' => 'affiliates.affiliateId'
			),
			array(
				'alias' => 'external_system_id',
				'select' => 'external_system.id'
			),
			array(
				'alias' => 'game_description_id',
				'select' => $show_game ? 'game_description.id' : '"N/A"',
			),
			array(
				'alias' => 'game_type_id',
				'select' => $show_game_type ? 'game_type.id' : '"N/A"',
			),
			array(
				'dt' => $i++,
				'alias' => 'system_code',
				'select' => $show_game_platform ? 'external_system.system_code' : '"N/A"',
				'formatter' => function($d, $row) use ($is_export)  {

                     if ($is_export) {
                     	return $d;
                     }else{
                     	return $d. '<p class=" hide gamePlatformId">'. $row['external_system_id']. '</p>';
                     }

				},
				'name' => lang('System Code'),
			),
			array(
				'dt' => $i++,
				'alias' => 'game_type_lang',
				'select' => $show_game_type ? 'game_type.game_type_lang' : '"N/A"',
				'formatter' => 'languageFormatter',
				'name' => lang('Game Type Language'),
			),
			array(
				'dt' => $i++,
				'alias' => 'game_name',
				'select' => $show_game ? 'game_description.game_name' : '"N/A"',
				'formatter' => 'languageFormatter',
				'name' => lang('Game Name'),
			),
			array(
				'dt' => $i++,
				'alias' => 'player_username',
				 //'select' => $show_player ? '( CASE WHEN affiliates.username IS NULL THEN player.username ELSE CONCAT(player.username, \' (\', affiliates.username,  \')\' ) END )' : '"N/A"',
				'select' => $show_player ? 'player.username' : '"N/A"',
				'formatter' => function($d, $row) use ($is_export) {

					if ($this->utils->isAffSubProject() && $this->utils->isEnabledFeature('masked_player_username_on_affiliate')) {
						$d = $this->utils->keepOnlyString($d, 4);
					}

					if($d != 'N/A') {
						return $is_export ? $d : '<a href="/player_management/userInformation/' . $row['player_playerId'] . '" target="_blank">' . $d . '</a>';
					}
					return $d;
				},
				'name' => lang('Username'),
			),

			array(
				'dt' => $i++,
				'alias' => 'player_level',
				'select' => $show_player ? 'player.levelId' : '"N/A"',
				'formatter' => function($d, $row){
					if($d != 'N/A') {
						return lang($row['player_groupName']).' - '.lang($row['player_levelName']);
					}
		  			return $d;

				},
				'name' => lang('Player Level'),

			),
			array(
				'dt' => $i++,
				'alias' => 'affiliate',
				'select' => 'affiliates.username',
				'formatter' => function($d, $row) use ($is_export) {

					if ($this->utils->isEnabledFeature('masked_affiliate_username_on_affiliate')) {
						$d = $this->utils->keepOnlyString($d, 4);
					}

					if($d) {
						if($this->utils->isFromHost('aff')) {
							return trim(trim($d), ',') ?: lang('lang.norecyet');
						}
						return $is_export ? $d : '<a href="/affiliate_management/userInformation/' . $row['affiliates_affiliateId'] . '" target="_blank">' . $d . '</a>';
					} else {

					}
				},
				'name' => lang('aff.as03'),
			),
			array(
				'dt' => $i++,
				'alias' => 'total_player',
				'select' => 'COUNT(DISTINCT game_provider_report.player_id)',
				'formatter' => function ($d, $row)  use ($is_export)  {
					//return $is_export ? $d : '<a href="#" class="totalPlayers">' . $d . '</a>';
					return $d;
				},
				'name' => lang('Total Player'),
			),
			array(
				'dt' => $i++,
				'alias' => 'total_bet',
				'select' => 'SUM(game_provider_report.betting_amount)',
				'formatter' =>  function($d, $row) use ($is_export) {
					return $this->currencyFormatter($d, $is_export);
				},
				'name' => lang('Total Bet'),
			),

			array(
				'dt' => $i++,
				'alias' => 'total_gain',
				'select' => 'SUM(game_provider_report.win_amount)',
				'formatter' =>  function($d, $row) use ($is_export) {
					return $this->currencyFormatter($d, $is_export);
				},
				'name' => lang('Total Gain'),
			),
			array(
				'dt' => $i++,
				'alias' => 'loss_amount',
				'select' => 'SUM(game_provider_report.loss_amount)',
				'formatter' =>  function($d, $row) use ($is_export) {
					return $this->currencyFormatter($d, $is_export);
				},
				'name' => lang('Total Loss'),
			),
			array(
				'dt' => $i++,
				'alias' => 'payout',
				'select' => 'SUM(game_provider_report.loss_amount-game_provider_report.win_amount)',
				'formatter' =>  function($d, $row) use ($is_export) {
					return $this->currencyFormatter($d, $is_export);
				},
				'name' => lang('Payout'),
			),
			array(
				'dt' => $i++,
				'alias' => 'rate',
				'select' => 'SUM(game_provider_report.loss_amount-game_provider_report.win_amount)/SUM(game_provider_report.betting_amount)',
				'formatter' => 'percentageFormatter',
				'name' => lang('Rate'),
			),
		);

		# FILTER ######################################################################################################################################################################################

		if (isset($input['datetime_from'], $input['datetime_to'])) {
			$where[] ='game_provider_report.date >= ?';
			$where[] ='game_provider_report.date <= ?';
			$values[] = $input['datetime_from'];
			$values[] = $input['datetime_to'];
		}
		$where[] = "game_provider_report.status = ?";//get only settled and not updated status which is null
		$values[] = GAME_LOGS::STATUS_SETTLED;
		$result = $this->data_tables->get_data($request, $columns, $table, $where, $values, $joins, $group_by, $having, false);

		$result['summary'] = array();

		$total_payout_rate =  'SUM(game_provider_report.loss_amount-game_provider_report.win_amount)/SUM(game_provider_report.betting_amount) * 100 total_payout_rate' ;
		$total_bet =  'SUM(game_provider_report.betting_amount) total_bet ';
		$total_ave_count =  'COUNT(game_provider_report.betting_amount) total_ave_count ';
     	$total_ave_bet = 'SUM(game_provider_report.betting_amount) /COUNT(game_provider_report.betting_amount)  total_ave_bet ';


		$summary = $this->data_tables->summary($request, $table, $joins, 'COUNT(DISTINCT game_provider_report.player_id) total_player, '.$total_bet.','.$total_ave_bet.','.$total_ave_count.'  ,SUM(game_provider_report.win_amount) as total_win, SUM(game_provider_report.loss_amount) total_loss,SUM(game_provider_report.loss_amount-game_provider_report.win_amount) total_payout,'.$total_payout_rate.'', null, $columns, $where, $values);

		$group_by2=[];
		$group_by2[] = 'game_provider_report.player_id, game_provider_report.game_platform_id';
		$player_total_bets_per_game = $this->data_tables->summary($request, $table, $joins, 'DISTINCT game_provider_report.player_id player_id , player.username, game_provider_report.game_platform_id, '.$total_bet.', SUM(game_provider_report.win_amount) as total_win, SUM(game_provider_report.loss_amount) total_loss , SUM(game_provider_report.loss_amount-game_provider_report.win_amount) total_payout,', $group_by2, $columns, $where, $values);


		$player_total_bets_per_game_map = [];
		$game_platform_header_map =[];
        $sum_total_bets_map =[];
        $sum_total_wins_map =[];
        $sum_total_loss_map =[];
        $sum_total_payout_map =[];
        $bet_details_map = [];

        foreach ($player_total_bets_per_game as $v) {

            //make bet details array
			$bet_details_map[$v['player_id']][$v['game_platform_id']]= array('game_platform_id' => $v['game_platform_id'] , 'total_bet' => $this->currencyFormatter($v['total_bet']) , 'total_win' => $this->currencyFormatter($v['total_win']) , 'total_loss' => $this->currencyFormatter($v['total_loss']) ,'total_payout' => $this->currencyFormatter($v['total_payout'])  ) ;
            //array push every games bet per player
			$sum_total_bets_map[$v['player_id']][] = $v['total_bet'] ;
			$sum_total_wins_map[$v['player_id']][] = $v['total_win'] ;
			$sum_total_loss_map[$v['player_id']][] = $v['total_loss'] ;
			$sum_total_payout_map[$v['player_id']][] = $v['total_payout'] ;
			//add the bet items to get the total bets for all player game
            $player_total_games_bet = $this->currencyFormatter(array_sum($sum_total_bets_map[$v['player_id']]));
            $player_total_games_win = $this->currencyFormatter(array_sum($sum_total_wins_map[$v['player_id']]));
            $player_total_games_loss = $this->currencyFormatter(array_sum($sum_total_loss_map[$v['player_id']]));
            $player_total_games_payout = $this->currencyFormatter(array_sum($sum_total_payout_map[$v['player_id']]));
             //construct now the player row
            $player_row = [];
            $player_row['player_id'] = $v['player_id'];
            $player_row['username'] = $v['username'];
            $player_row['bet_details'] =  $bet_details_map[$v['player_id']];
            $player_row['sum_total_bets'] = $player_total_games_bet;
            $player_row['sum_total_wins'] = $player_total_games_win;
            $player_row['sum_total_loss'] = $player_total_games_loss;
            $player_row['sum_total_payout'] = $player_total_games_payout;
            // add on main players map for json output
			$player_total_bets_per_game_map[$v['player_id']] =  $player_row;
			// make selective table headers  by making map -> associative override
			$game_platform_header_map[$v['game_platform_id']] = $v['game_platform_id'];

		}
		array_walk($summary[0], function (&$value) {
			$value = round(floatval($value), 2);
		});

        $result['player_total_bets_per_game'] = $player_total_bets_per_game_map;
        $result['game_platform_header_map'] = $game_platform_header_map;
		$result['summary'] = $summary;
		//$result['summary'] = [];

		return $result;
	}



	public function gameApi2($request, $is_export = false) {

		$db_false = 0;
		$db_true = 1;

		$this->load->library(array('data_tables'));
		$input = $this->data_tables->extra_search($request);

		$i = 0;
		$where = array();
		$values = array();
		$columns = array();
		$joins = array();

		$columns[] = array(
			'select' => 'external_system.status',
			'alias' => 'external_system_status',
		);

		$columns[] = array(
			'dt' => $i++,
			'select' => 'external_system.id',
			'alias' => 'external_system_id',
			'name' => lang('lang.action'),
			'formatter' => function ($d, $row) use ($is_export) {

				$edit = '<a href="javascript:void(0)" class="game_api_actions" game_api_action="edit" game_api_id="'.$d.'" data-toggle="tooltip" title="'.lang("sys.em6").'" class="edit-row" id="edit_row-1029"><span class="glyphicon glyphicon-edit"></span></a>&nbsp;';

				$block_icon = '';
				$block_icon_title = '';
				$maintenance='';
				$maintenance_action='';
				$pause_sync_action='';
				$block_action = '';
				$game_api_action='';

				if($row['status'] == 1){
					$block_icon = 'glyphicon-remove-circle';
					$block_icon_title = lang('tool.pm08');
					$block_action = 'disable';

				}else{
					$block_icon = 'glyphicon-ok-sign';
					$block_icon_title = lang('tool.pm09');
					$block_action = 'able';
				}

				$maintenance_icon = '';
				$maintenance_icon_title = '';
				if($row['maintenance_mode'] == 1){
					$maintenance_icon = 'glyphicon-ok-circle';
					$maintenance_icon_title = lang('Finish Maintenance');
					$maintenance_action = 'finish_maintenance';

				}else{
					$maintenance_icon = 'glyphicon-cog';
					$maintenance_icon_title = lang('Start Maintenance');
					$maintenance_action = 'start_maintenance';
				}

				$pause_sync_icon = '';
				$pause_sync_icon_title = '';
				if($row['pause_sync'] == 1){
					$pause_sync_icon = 'glyphicon-play';
					$pause_sync_icon_title = lang('Revert To Syncing');
					$pause_sync_action="revert_sync";

				}else{
					$pause_sync_icon = 'glyphicon-pause';
					$pause_sync_icon_title = lang('Pause Syncing');
					$pause_sync_action="pause_sync";
				}


				$block = '<a href="javascript:void(0)"  game_api_id="'.$d.'"  data-toggle="tooltip" title="'.$block_icon_title.'" class="game_api_actions" game_api_action="'.$block_action.'"   ><span  game_api_id="'.$d.'" class="glyphicon '.$block_icon.'  primary"></span></a>&nbsp;';

				if($this->permissions->checkPermissions('game_api_maintenance')){
					$maintenance='<a href="javascript:void(0)" data-toggle="tooltip" title="'.$maintenance_icon_title.'" class="game_api_actions"  game_api_id="'.$d.'"  game_api_action="'.$maintenance_action.'"   data-original-title="Start Maintenance"><span class="glyphicon '.$maintenance_icon.'"></span></a>&nbsp;';
				}

				$pause_sync ='<a href="javascript:void(0)" data-toggle="tooltip" title="'.$pause_sync_icon_title.'" class="game_api_actions" game_api_id="'.$d.'" game_api_action="'.$pause_sync_action.'" data-original-title="'.$pause_sync_icon .'"><span class="glyphicon '.$pause_sync_icon .'"></span></a>';

				return $edit.$block.$maintenance.$pause_sync;
			}
		);
		$columns[] = array(
			'dt' => $i++,
			'select' => 'external_system.id',
			'alias' => 'game_platform_id',
			'name' => lang('Game Platform'),
			'formatter' => function ($d, $row) use ($is_export,$db_true) {
				if(!$is_export){
					$css_class=($row['external_system_status'] == $db_true) ? 'text-success' : 'text-danger';
					return '<span class="'.$css_class.'"><b>'.$d.'</b></span>';
				}
				return $d;
			}
		);
		$columns[] = array(
			'dt' => $i++,
			'select' => 'external_system.live_mode',
			'alias' => 'live_mode',
			'name' => lang('sys.ga.livemode'),
			'formatter' => function ($d, $row) use ($is_export){
				if(!$is_export){
					return $d==1 ? '<span class="glyphicon glyphicon-check text-success"></span>' : '<span class="glyphicon glyphicon-unchecked text-default"></span>';
				}else{
					return $d==1 ? 1 : '0';
				}
			},
		);
		$columns[] = array(
			'dt' => $i++,
			'select' => 'external_system.seamless',
			'alias' => 'seamless',
			'name' => lang('sys.ga.seamless'),
			'formatter' => function ($d, $row) use ($is_export){
				if(!$is_export){
					return $d==1 ? '<span class="glyphicon glyphicon-check text-success"></span>' : '<span class="glyphicon glyphicon-unchecked text-default"></span>';
				}else{
					return $d==1 ? 1 : '0';
				}
			},
		);
		$columns[] = array(
			'dt' => $i++,
			'select' => 'external_system.status',
			'alias' => 'status',
			'name' => lang('sys.ga.status'),
			'formatter' => function ($d, $row) use ($is_export){
				if(!$is_export){
					// return $d==1 ? '<span class="label label-success">Active</span>' : '<span class="label label-danger">Blocked</span> ';
					return $d==1 ? '1' :  '0';
				}else{
					return $d==1 ? 1 : '0';
				}
			},
		);
		$columns[] = array(
			'dt' => $i++,
			'select' => 'external_system.system_code',
			'alias' => 'system_code',
			'name' => lang('sys.ga.systemcode'),
			'formatter' => function ($d, $row) {
				return $d ?: '-';
			},
		);
		$columns[] = array(
			'dt' => $i++,
			'select' => 'external_system.system_name',
			'alias' => 'system_name',
			'name' => lang('sys.ga.systemname')
		);
		$columns[] = array(
			'dt' => $i++,
			'select' => 'external_system.system_type',
			'alias' => 'system_type',
			'name' => lang('sys.ga.systemtype'),
			'formatter' => function ($d, $row) {
				return ($d== 1) ? lang('sys.game.api') : '-';
			},
		);
		$columns[] = array(
			'dt' => $i++,
			'select' => 'external_system.maintenance_mode',
			'alias' => 'maintenance_mode',
			'name' => lang('Maintenance Mode'),
			'formatter' => function ($d, $row) use ($is_export){
				if(!$is_export){
					return $d==1 ? '1' :  '0';
				}else{
					return $d==1 ? 1 : '0';
				}
			},
		);
		$columns[] = array(
			'dt' => $i++,
			'select' => 'external_system.pause_sync',
			'alias' => 'pause_sync',
			'name' => lang('Pause Sync'),
			'formatter' => function ($d, $row) use ($is_export){
				if(!$is_export){
					return $d==1 ? '1' :  '0';
				}else{
					return $d==1 ? 1 : '0';
				}
			},
		);
		$columns[] = array(
			'dt' => $i++,
			'select' => 'external_system.last_sync_datetime',
			'alias' => 'last_sync_datetime',
			'name' => lang('sys.ga.lastsyncdt'),
			'formatter' => function ($d, $row) use ($is_export){
				if(!$is_export){
					$d = !empty($d) ? $d : '-';
					$edit ="<span class='label label-danger pull-left game_api_hint game_api_actions' style='cursor:pointer' data-toggle='tooltip' title='".lang("sys.em6")."'  game_api_action='edit_by_field' game_api_field='last_sync_datetime' game_api_id='".$row['external_system_id']."'> <i class='fa fa-pencil'></i> </span>";
					$hint="<span class='label label-info pull-left game_api_hint'>".$row['game_platform_id']."</span><span class='label label-success pull-left game_api_hint'>".$row['system_code']."</span>".$edit."<div class='clearfix'></div>";
					return $hint."<code>".$d."</code></pre>";
				}else{
					return $d;
				}
			},
		);
		$columns[] = array(
			'dt' => $i++,
			'select' => 'external_system.last_sync_id',
			'alias' => 'last_sync_id',
			'name' => lang('sys.ga.lastsyncid'),
			'formatter' => function ($d, $row) use ($is_export){
				if(!$is_export){
					$d = !empty($d) ? $d : '-';
					$edit ="<span class='label label-danger pull-left game_api_hint game_api_actions' style='cursor:pointer' data-toggle='tooltip' title='".lang("sys.em6")."'  game_api_action='edit_by_field' game_api_field='last_sync_id' game_api_id='".$row['external_system_id']."'> <i class='fa fa-pencil'></i> </span>";
					$hint="<span class='label label-info pull-left game_api_hint'>".$row['game_platform_id']."</span><span class='label label-success pull-left game_api_hint'>".$row['system_code']."</span>".$edit."<div class='clearfix'></div>";
					return $hint."<code>".$d."</code></pre>";
				}else{
					return $d;
				}
			},
		);
		$columns[] = array(
			'dt' => $i++,
			'select' => 'external_system.last_sync_details',
			'alias' => 'last_sync_details',
			'name' => lang('sys.ga.lastsyncdet'),
			'formatter' => function ($d, $row) use ($is_export){
				if(!$is_export){
					$d = !empty($d) ? $d : '-';
					$edit ="<span class='label label-danger pull-left game_api_hint game_api_actions' style='cursor:pointer' data-toggle='tooltip' title='".lang("sys.em6")."'  game_api_action='edit_by_field' game_api_field='last_sync_details' game_api_id='".$row['external_system_id']."'> <i class='fa fa-pencil'></i> </span>";
					$hint="<span class='label label-info pull-left game_api_hint'>".$row['game_platform_id']."</span><span class='label label-success pull-left game_api_hint'>".$row['system_code']."</span>".$edit."<div class='clearfix'></div>";
					return $hint."<code>".$d."</code></pre>";
				}else{
					return $d;
				}
			},
		);
		$columns[] = array(
			'dt' => $i++,
			'select' => 'external_system.live_url',
			'alias' => 'live_url',
			'name' => lang('sys.ga.liveurl'),
			'formatter' => function ($d, $row) use ($is_export){
				if(!$is_export){
					$d = !empty($d) ? $d : '-';
					$edit ="<span class='label label-danger pull-left game_api_hint game_api_actions' style='cursor:pointer' data-toggle='tooltip' title='".lang("sys.em6")."'  game_api_action='edit_by_field' game_api_field='live_url' game_api_id='".$row['external_system_id']."'> <i class='fa fa-pencil'></i> </span>";
					$hint="<span class='label label-info pull-left game_api_hint'>".$row['game_platform_id']."</span><span class='label label-success pull-left game_api_hint'>".$row['system_code']."</span>".$edit."<div class='clearfix'></div>";
					return $hint."<code>".$d."</code></pre>";
				}else{
					return $d;
				}
			},
		);
		$columns[] = array(
			'dt' => $i++,
			'select' => 'external_system.second_url',
			'alias' => 'second_url',
			'name' => lang('sys.ga.secondurl'),
			'formatter' => function ($d, $row) use ($is_export){
				if(!$is_export){
					$d = !empty($d) ? $d : '-';
					$edit ="<span class='label label-danger pull-left game_api_hint game_api_actions' style='cursor:pointer' data-toggle='tooltip' title='".lang("sys.em6")."'  game_api_action='edit_by_field' game_api_field='second_url' game_api_id='".$row['external_system_id']."'> <i class='fa fa-pencil'></i> </span>";
					$hint="<span class='label label-info pull-left game_api_hint'>".$row['game_platform_id']."</span><span class='label label-success pull-left game_api_hint'>".$row['system_code']."</span>".$edit."<div class='clearfix'></div>";
					return $hint."<code>".$d."</code></pre>";
				}else{
					return $d;
				}
			},
		);
		$columns[] = array(
			'dt' => $i++,
			'select' => 'external_system.sandbox_url',
			'alias' => 'sandbox_url',
			'name' => lang('sys.ga.sandboxurl'),
			'formatter' => function ($d, $row) use ($is_export){
				if(!$is_export){
					$d = !empty($d) ? $d : '-';
					$edit ="<span class='label label-danger pull-left game_api_hint game_api_actions' style='cursor:pointer' data-toggle='tooltip' title='".lang("sys.em6")."'  game_api_action='edit_by_field' game_api_field='sandbox_url' game_api_id='".$row['external_system_id']."'> <i class='fa fa-pencil'></i> </span>";
					$hint="<span class='label label-info pull-left game_api_hint'>".$row['game_platform_id']."</span><span class='label label-success pull-left game_api_hint'>".$row['system_code']."</span>".$edit."<div class='clearfix'></div>";
					return $hint."<code>".$d."</code></pre>";
				}else{
					return $d;
				}
			},
		);
		$columns[] = array(
			'dt' => $i++,
			'select' => 'external_system.extra_info',
			'alias' => 'extra_info',
			'name' => lang('sys.ga.extrainfo'),
			'formatter' => function ($d, $row) use ($is_export){
				if(!$is_export){
					$edit ="<span class='label label-danger pull-left game_api_hint game_api_actions' style='cursor:pointer' data-toggle='tooltip' title='".lang("sys.em6")."'   game_api_action='edit_by_field' game_api_field='extra_info' game_api_id='".$row['external_system_id']."'> <i class='fa fa-pencil'></i> </span>";
					$hint="<span  class='label label-info pull-left game_api_hint text-center'>".$row['game_platform_id']."</span><span class='label label-success pull-left game_api_hint'>".$row['system_code']."</span>".$edit."<div class='clearfix'></div>";
					return $hint."<div class='code-holder bg-info' style='padding:11px;width:510px;' ><pre class='pre-code-container'id='pre-code-extra_info-".$row['external_system_id']."' style='word-break: normal;width:490px; font-size: 0.875em;'><code class=\"JSON hljs pre-code\">".$d ?: '-'. "</code></pre></div>";
				}else{
					return $d;
				}
			},
		);
		$columns[] = array(
			'dt' => $i++,
			'select' => 'external_system.sandbox_extra_info',
			'alias' => 'sandbox_extra_info',
			'name' => lang('sys.ga.sandboxextrainfo'),
			'formatter' => function ($d, $row) use ($is_export){
				if(!$is_export){
					$edit ="<span class='label label-danger pull-left game_api_hint game_api_actions' style='cursor:pointer' data-toggle='tooltip' title='".lang("sys.em6")."'  game_api_action='edit_by_field' game_api_field='sandbox_extra_info' game_api_id='".$row['external_system_id']."'> <i class='fa fa-pencil'></i> </span>";
					$hint="<span class='label label-info pull-left game_api_hint'>".$row['game_platform_id']."</span><span class='label label-success pull-left game_api_hint'>".$row['system_code']."</span>".$edit."<div class='clearfix'></div>";
					return $hint."<div class='code-holder bg-info' style='padding:11px;width:510px;'><pre class='pre-code-container'id='pre-code-sandbox_extra_info-".$row['external_system_id']."' style='word-break: normal;width:490px; font-size: 0.875em;'><code class=\"JSON hljs pre-code\">".$d ?: '-'. "</code></pre></div>";
				}else{
					return $d;
				}
			},
		);
		$columns[] = array(
			'dt' => $i++,
			'select' => 'external_system.live_key',
			'alias' => 'live_key',
			'name' => lang('sys.ga.livekey'),
			'formatter' => function ($d, $row) use ($is_export){
				if(!$is_export){
					$d = !empty($d) ? $d : '-';
					$edit ="<span class='label label-danger pull-left game_api_hint game_api_actions' style='cursor:pointer' data-toggle='tooltip' title='".lang("sys.em6")."'  game_api_action='edit_by_field' game_api_field='live_key' game_api_id='".$row['external_system_id']."'> <i class='fa fa-pencil'></i> </span>";
					$hint="<span class='label label-info pull-left game_api_hint'>".$row['game_platform_id']."</span><span class='label label-success pull-left game_api_hint'>".$row['system_code']."</span>".$edit."<div class='clearfix'></div>";
					return $hint."<code>".$d."</code></pre>";
				}else{
					return $d;
				}
			},
		);
		$columns[] = array(
			'dt' => $i++,
			'select' => 'external_system.sandbox_key',
			'alias' => 'sandbox_key',
			'name' => lang('sys.ga.sandboxkey'),
			'formatter' => function ($d, $row) use ($is_export){
				if(!$is_export){
					$d = !empty($d) ? $d : '-';
					$edit ="<span class='label label-danger pull-left game_api_hint game_api_actions' style='cursor:pointer' data-toggle='tooltip' title='".lang("sys.em6")."'  game_api_action='edit_by_field' game_api_field='sandbox_key' game_api_id='".$row['external_system_id']."'> <i class='fa fa-pencil'></i> </span>";
					$hint="<span class='label label-info pull-left game_api_hint'>".$row['game_platform_id']."</span><span class='label label-success pull-left game_api_hint'>".$row['system_code']."</span>".$edit."<div class='clearfix'></div>";
					return $hint."<code>".$d."</code></pre>";

				}else{
					return $d;
				}
			},
		);
		$columns[] = array(
			'dt' => $i++,
			'select' => 'external_system.live_secret',
			'alias' => 'live_secret',
			'name' => lang('sys.ga.livesecret'),
			'formatter' => function ($d, $row) use ($is_export){
				if(!$is_export){
					$d = !empty($d) ? $d : '-';
					$edit ="<span class='label label-danger pull-left game_api_hint game_api_actions' style='cursor:pointer' data-toggle='tooltip' title='".lang("sys.em6")."' game_api_action='edit_by_field' game_api_field='live_secret' game_api_id='".$row['external_system_id']."'> <i class='fa fa-pencil'></i> </span>";
					$hint="<span class='label label-info pull-left game_api_hint'>".$row['game_platform_id']."</span><span class='label label-success pull-left game_api_hint'>".$row['system_code']."</span>".$edit."<div class='clearfix'></div>";
					return $hint."<code>".$d."</code></pre>";
				}else{
					return $d;
				}
			},
		);
		$columns[] = array(
			'dt' => $i++,
			'select' => 'external_system.sandbox_secret',
			'alias' => 'sandbox_secret',
			'name' => lang('sys.ga.sandboxsecret'),
			'formatter' => function ($d, $row) use ($is_export){
				if(!$is_export){
					$d = !empty($d) ? $d : '-';
					$edit ="<span class='label label-danger pull-left game_api_hint game_api_actions' style='cursor:pointer' data-toggle='tooltip' title='".lang("sys.em6")."'  game_api_action='edit_by_field' game_api_field='sandbox_secret' game_api_id='".$row['external_system_id']."'> <i class='fa fa-pencil'></i> </span>";
					$hint="<span class='label label-info pull-left game_api_hint'>".$row['game_platform_id']."</span><span class='label label-success pull-left game_api_hint'>".$row['system_code']."</span>".$edit."<div class='clearfix'></div>";
					return $hint."<code>".$d. "</code></pre>";
				}else{
					return $d;
				}
			},
		);
		$columns[] = array(
			'dt' => $i++,
			'select' => 'external_system.live_account',
			'alias' => 'live_account',
			'name' => lang('sys.ga.liveacct'),
			'formatter' => function ($d, $row) use ($is_export){
				if(!$is_export){
					$d = !empty($d) ? $d : '-';
					$edit ="<span class='label label-danger pull-left game_api_hint game_api_actions' style='cursor:pointer' data-toggle='tooltip' title='".lang("sys.em6")."'  game_api_action='edit_by_field' game_api_field='live_account' game_api_id='".$row['external_system_id']."'> <i class='fa fa-pencil'></i> </span>";
					$hint="<span class='label label-info pull-left game_api_hint'>".$row['game_platform_id']."</span><span class='label label-success pull-left game_api_hint'>".$row['system_code']."</span>".$edit."<div class='clearfix'></div>";
					return $hint."<code>".$d."</code></pre>";
				}else{
					return $d;
				}
			},
		);
		$columns[] = array(
			'dt' => $i++,
			'select' => 'external_system.sandbox_account',
			'alias' => 'sandbox_account',
			'name' => lang('sys.ga.sandboxacct'),
			'formatter' => function ($d, $row) use ($is_export){
				if(!$is_export){
					$d = !empty($d) ? $d : '-';
					$edit ="<span class='label label-danger pull-left game_api_hint game_api_actions' style='cursor:pointer' data-toggle='tooltip' title='".lang("sys.em6")."'  game_api_action='edit_by_field' game_api_field='sandbox_account' game_api_id='".$row['external_system_id']."'> <i class='fa fa-pencil'></i> </span>";
					$hint="<span class='label label-info pull-left game_api_hint'>".$row['game_platform_id']."</span><span class='label label-success pull-left game_api_hint'>".$row['system_code']."</span>".$edit."<div class='clearfix'></div>";
					return $hint."<code>".$d."</code></pre>";
				}else{
					return $d;
				}
			},
		);
		$columns[] = array(
			'dt' => $i++,
			'select' => 'external_system.class_name',
			'alias' => 'class_name',
			'name' => lang('sys.ga.classname'),
			'formatter' => function ($d, $row) use ($is_export){
				if(!$is_export){
					$d = !empty($d) ? $d : '-';
					$edit ="<span class='label label-danger pull-left game_api_hint game_api_actions' style='cursor:pointer' data-toggle='tooltip' title='".lang("sys.em6")."'  game_api_action='edit_by_field' game_api_field='class_name' game_api_id='".$row['external_system_id']."'> <i class='fa fa-pencil'></i> </span>";
					$hint="<span class='label label-info pull-left game_api_hint'>".$row['game_platform_id']."</span><span class='label label-success pull-left game_api_hint'>".$row['system_code']."</span>".$edit."<div class='clearfix'></div>";
					return $hint."<code>".$d."</code></pre>";
				}else{
					return $d;
				}
			},
		);
		$columns[] = array(
			'dt' => $i++,
			'select' => 'external_system.local_path',
			'alias' => 'local_path',
			'name' => lang('sys.ga.localpath'),
			'formatter' => function ($d, $row) use ($is_export){
				if(!$is_export){
					$d = !empty($d) ? $d : '-';
					$edit ="<span class='label label-danger pull-left game_api_hint game_api_actions' style='cursor:pointer' data-toggle='tooltip' title='".lang("sys.em6")."'  game_api_action='edit_by_field' game_api_field='local_path' game_api_id='".$row['external_system_id']."'> <i class='fa fa-pencil'></i> </span>";
					$hint="<span class='label label-info pull-left game_api_hint'>".$row['game_platform_id']."</span><span class='label label-success pull-left game_api_hint'>".$row['system_code']."</span>".$edit."<div class='clearfix'></div>";
					return $hint."<code>".$d."</code></pre>";
				}else{
					return $d;
				}
			},
		);
		$columns[] = array(
			'dt' => $i++,
			'select' => 'external_system.manager',
			'alias' => 'manager',
			'name' => lang('sys.ga.manager'),
			'formatter' => function ($d, $row) use ($is_export){
				if(!$is_export){
					$d = !empty($d) ? $d : '-';
					$edit ="<span class='label label-danger pull-left game_api_hint game_api_actions' style='cursor:pointer' data-toggle='tooltip' title='".lang("sys.em6")."'  game_api_action='edit_by_field' game_api_field='manager' game_api_id='".$row['external_system_id']."'> <i class='fa fa-pencil'></i> </span>";
					$hint="<span class='label label-info pull-left game_api_hint'>".$row['game_platform_id']."</span><span class='label label-success pull-left game_api_hint'>".$row['system_code']."</span>".$edit."<div class='clearfix'></div>";
					return $hint."<code>".$d."</code></pre>";
				}else{
					return $d;
				}
			},
		);
		$columns[] = array(
			'dt' => $i++,
			'select' => 'external_system.game_platform_rate',
			'alias' => 'game_platform_rate',
			'name' => lang('sys.gd7') . " " . lang('sys.rate'),
			'formatter' => function ($d, $row) use ($is_export){
				if(!$is_export){
					$d = !empty($d) ? $d : '-';
					$edit ="<span class='label label-danger pull-left game_api_hint game_api_actions' style='cursor:pointer' data-toggle='tooltip' title='".lang("sys.em6")."'  game_api_action='edit_by_field' game_api_field='game_platform_rate' game_api_id='".$row['external_system_id']."'> <i class='fa fa-pencil'></i> </span>";
					$hint="<span class='label label-info pull-left game_api_hint'>".$row['game_platform_id']."</span><span class='label label-success pull-left game_api_hint'>".$row['system_code']."</span>".$edit."<div class='clearfix'></div>";
					return $hint."<code>".$d."</code></pre>";
				}else{
					return $d;
				}
			},
		);
		// $columns[] = array(
		// 	'dt' => $i++,
		// 	'select' => 'external_system.amount_float',
		// 	'alias' => 'amount_float',
		// 	'name' => lang('sys.ga.amount_float'),
		// 	'formatter' => function ($d, $row) use ($is_export){


		// 		$amount_float_vals=[
		// 			'0'=> '0(Integer)',
		// 			'1'=> '.1',
		// 			'2'=> '.01',
		// 		];


		// 		if(!$is_export){
		// 		//$d = !empty($d) ? $d : '-';
		// 			$edit ="<span class='label label-danger pull-left game_api_hint game_api_actions' style='cursor:pointer' data-toggle='tooltip' title='".lang("sys.em6")."'   game_api_action='edit_by_field' game_api_field='amount_float' game_api_id='".$row['external_system_id']."'> <i class='fa fa-pencil'></i> </span>";
		// 			$hint="<span class='label label-info pull-left game_api_hint'>".$row['game_platform_id']."</span><span class='label label-success pull-left game_api_hint'>".$row['system_code']."</span>".$edit."<div class='clearfix'></div>";
		// 			return $hint.$amount_float_vals[$d];
		// 		}else{
		// 			return $amount_float_vals[$d];
		// 		}

		// 	},
		// );
		$columns[] = array(
			'dt' => $i++,
			'select' => 'external_system.note',
			'alias' => 'note',
			'name' => lang('sys.ga.note'),
			'formatter' => function ($d, $row) use ($is_export){

				if(!$is_export){
					$edit ="<span class='label label-danger pull-left game_api_hint game_api_actions' style='cursor:pointer' data-toggle='tooltip' title='".lang("sys.em6")."'  game_api_action='edit_by_field' game_api_field='note' game_api_id='".$row['external_system_id']."'> <i class='fa fa-pencil'></i> </span>";
					$hint="<span class='label label-info pull-left game_api_hint'>".$row['game_platform_id']."</span><span class='label label-success pull-left game_api_hint'>".$row['system_code']."</span>".$edit."<div class='clearfix' style='width:250px;'></div>";

					return $hint."<div class='code-holder bg-info' style='padding:11px;width:510px;'><pre class='pre-code-container'id='pre-code-".$row['external_system_id']."' style='word-break: normal;width:490px; font-size: 0.875em;'><code class=\"JSON hljs pre-code\">".$d ?: '-'. "</code></pre></div>";
				}else{
					return $d;
				}
			},
		);
		$columns[] = array(
			'dt' => $i++,
			'select' => 'external_system.created_on',
			'alias' => 'created_on',
			'name' => lang('sys.createdon'),
			'formatter' => function ($d, $row) {
				return $d ?: '-';
			},
		);
		$table ='external_system';

		if($is_export){
			$this->data_tables->options['is_export']=true;
			if(empty($csv_filename)){
				$csv_filename=$this->utils->create_csv_filename(__FUNCTION__);
			}
			$this->data_tables->options['csv_filename']=$csv_filename;
		}

		if (isset($input['search_by'])) {
			if(isset($input['date_from']) && isset($input['date_to'])){
				if($input['search_by'] == 1){
					$where[] =  "external_system.created_on >= ? AND game_api_update_history.created_on <= ?";
					$values[] = $input['date_from'];
					$values[] = $input['date_to'];
				}else{
					$where[] =  "external_system.updated_at >= ? AND game_api_update_history.updated_at <= ?";
					$values[] = $input['date_from'];
					$values[] = $input['date_to'];
				}
			}
		}

		if (isset($input['action']) && !empty($input['action'])) {
			$where[] =  "external_system.action = ?";
			$values[] =  $input['action'];
		}

		if (isset($input['game_platform_id']) && !empty($input['game_platform_id'])) {
			$where[] =  "external_system.game_platform_id = ?";
			$values[] =  $input['game_platform_id'];
		}


		if(isset($input['game_api_statuses'])){
			switch ($input['game_api_statuses']) {
				case '1':
				$where[]  = "external_system.status = ?";
				$values[] = $db_true ;
				break;
				case '2':
				$where[]  = "external_system.status = ?";
				$values[] = $db_false ;
				break;
				case '3':
				$where[]  = "external_system.maintenance_mode = ?";
				$values[] = $db_true ;
				break;
				case '4':
				$where[]  = "external_system.pause_sync = ?";
				$values[] = $db_true ;
				break;
				default:
				//
				break;
			}
		}
		if (isset($input['game_api_ids']) && !empty($input['game_api_ids'])) {
			if(!is_array($input['game_api_ids'])){
				$where[]  = "external_system.id = ?";
				$values[] = $input['game_api_ids'];
			}else{
				$where[] = 'external_system.id IN(' . implode(',', $input['game_api_ids']) . ')';
			}
		}
		$where[] =  "external_system.system_type = ?";
		$values[] =  External_system::SYSTEM_GAME_API;

		$result = $this->data_tables->get_data($request, $columns, $table, $where, $values, $joins);

		if($is_export){
		    //drop result if export
			return $csv_filename;
		}
		return $result;
	}

	public function parseArrayBetDetails($data){
		$string= "";
		try{
			if(is_array($data)){
				foreach($data as $key => $value){
					if(!is_array($value)){
						$string .= "<br><label>" . $key ." : ". $value . "</label>";
					}else{
						$string .= "<br><label>" . $key . "</label>";
						$string .= $this->parseArrayBetDetails($value);
					}
					
				}
			}else{
				$string .= "<br><label>" . $data . "</label>";
			}
		}catch(Exception $e){
			$string .= "<br><label>???</label>";
		}

		return $string;
	}

	public function parseOneworksBetDetails($data, $title, $is_export = false){
        if (!$is_export) {
            $string = "<label><b>$title</b></label>";

            try{
                //$string .= json_encode($data);
                if(is_array($data)){
                    foreach($data as $key => $value){
                        if(!is_array($value)){

                            switch ($key) {
                                case 'refNo':
                                    $string .= "<br><label class='".$key."'>" . $value . "</label>";
                                    break;
                                case 'betTime':
                                    $string .= "<br><label class='".$key."'>Bet time: " . $value . "</label>";
                                    break;
                                case 'bet':
                                    $string .= "<br><label class='".$key."'>Bet: " . $value . "</label>";
                                    break;
                                case 'betType':
                                    $string .= "<br><label class='".$key."'>Bet Type: " . $value . "</label>";
                                    break;
                                case 'vs':
                                    $string .= "<br><label class='".$key."'>Vs: " . $value . "</label>";
                                    break;
                                case 'League':
                                    $string .= "<br><label class='".$key."'>League: " . $value . "</label>";
                                    break;
                                default:
                                    $string .= "<br><label class='".$key."'>$key: " . $value . "</label>";
                                    break;
                            }
                        }else{
                            $string .= @$this->parseOneworksBetDetails($value, '<br>');
                            //$string .= json_encode($data);
                            /*foreach($value as $key2 => $value2){
                                $string .= $this->parseOneworksBetDetails($value2, '<br>');
                            }*/
                        }
                    }
                }else{
                    $string .= "<br><label>" . $data . "</label>";
                }
            }catch(Exception $e){
                $string .= "<br><label>???</label>";
            }
        } else {
            $string = $title;
            if(is_array($data)){
                foreach($data as $key => $value){
                    if(!is_array($value)){

                        switch ($key) {
                            case 'refNo':
                                $string .= PHP_EOL . $value;
                                break;
                            case 'betTime':
                                $string .= PHP_EOL . "Bet time: " . $value;
                                break;
                            case 'bet':
                                $string .= PHP_EOL . "Bet: " . $value;
                                break;
                            case 'betType':
                                $string .= PHP_EOL . "Bet Type: " . $value;
                                break;
                            case 'vs':
                                $string .= PHP_EOL . "Vs: " . $value;
                                break;
                            case 'League':
                                $string .= PHP_EOL . "League: " . $value;
                                break;
                            default:
                                $string .= PHP_EOL . $key . ": " . $value;
                                break;
                        }
                    }else{
                        $string .= @$this->parseOneworksBetDetails($value, PHP_EOL, true);
                    }
                }
            }else{
                $string .= PHP_EOL . $data;
            }
        }

		return $string;
	}

	/**
	 * detail: get game report timezone for a certain player
	 *
	 * @param array $request
	 * @param int $player_id total_player_game_day_timezone player_id
	 *
	 * @return array
	 */
	public function gameReportsTimezone($request, $player_id = null, $is_export = false) {
		$this->load->library(array('data_tables'));
		$this->load->model(array('player_model','game_logs','game_type_model'));
		$input = $this->data_tables->extra_search($request);
		$table = 'total_player_game_day_timezone';
		$unsettle=false;

		$joins = array();
		$where = array();
		$values = array();
		$group_by = array();
		$having = array();

		$joins['game_type'] = 'game_type.id = total_player_game_day_timezone.game_type_id';
		$joins['game_description'] = 'game_description.id = total_player_game_day_timezone.game_description_id';
		$joins['external_system'] = 'external_system.id = total_player_game_day_timezone.game_platform_id';
		$joins['player'] = 'player.playerId = total_player_game_day_timezone.player_id';
		$joins['affiliates'] = 'affiliates.affiliateId = player.affiliateId';
		$joins['vipsettingcashbackrule'] = 'vipsettingcashbackrule.vipsettingcashbackruleId = player.levelId';
		$joins['vipsetting'] = 'vipsetting.vipSettingId = vipsettingcashbackrule.vipSettingId';

		$group_by_field = null;
		$show_game_platform = false;
		$show_game_type = false;
		$show_game = false;
		$show_player = false;
		if (isset($input['group_by'])) {
			$group_by_field = $input['group_by'];
			switch ($input['group_by']) {
			case 'game_platform_id':
				$group_by[] = 'total_player_game_day_timezone.game_platform_id';
				$show_game_platform = true;
				break;
			case 'game_type_id':
				$group_by[] = 'total_player_game_day_timezone.game_type_id';
				$show_game_platform = true;
				$show_game_type = true;
				break;
			case 'game_description_id':
				$group_by[] = 'total_player_game_day_timezone.game_description_id';
				$show_game_platform = true;
				$show_game_type = true;
				$show_game = true;
				break;
			case 'player_id':
				$group_by[] = 'total_player_game_day_timezone.player_id';
				$show_player = true;
				break;
			case 'aff_id':
				$group_by[] = 'player.affiliateId';
				$show_player = true;
				break;
			case 'agent_id':
				$group_by[] = 'player.agent_id';
				$show_player = true;
				break;
			case 'game_type_and_player':
				$group_by[] = 'total_player_game_day_timezone.player_id, total_player_game_day_timezone.game_type_id';
				$show_game_platform = true;
				$show_game_type = true;
				$show_player = true;
				break;
			case 'game_platform_and_player':
				$group_by[] = 'total_player_game_day_timezone.player_id, total_player_game_day_timezone.game_platform_id';
				$show_game_platform = true;
				$show_player = true;
				break;
			case 'game_description_and_player':
				$group_by[] = 'total_player_game_day_timezone.player_id, total_player_game_day_timezone.game_description_id';
				$show_game_platform = true;
				$show_game_type = true;
				$show_game = true;
				$show_player = true;
				break;
			}
		}

		if (isset($input['username'])) {
			$player_id = $this->player_model->getPlayerIdByUsername($input['username']);
		}

		if (isset($input['referrer'])) {
			$refereePlayerId = $this->player_model->getPlayerIdByUsername($input['referrer']) ? : -1;
			$where[] = "player.refereePlayerId = ?";
			$values[] = $refereePlayerId;
		}

		if (isset($input['external_system'])) {
			if($input['external_system'] != 0) {
				$where[] = "external_system.id = ?";
				$values[] = $input['external_system'];
				$show_game_platform = true;
			}
		}

		if (isset($input['game_type_multiple'])) {
			if($input['game_type_multiple'] != 0) {
				$game_type_arr = explode('+', $input['game_type_multiple']);

                if (isset($input['game_type'])){
                	array_push($game_type_arr, $input['game_type']);
                }

				$game_types =  $game_type_arr;
				$where[] = "game_type.id in (" . implode(',', $game_types) . ") ";
			}
		}

		if (isset($input['game_type']) && !isset($input['game_type_multiple'])) {
			if($input['game_type'] != 0) {
				$where[] = "game_type.id = ?";
				$values[] = $input['game_type'];
			}
		}

		if (isset($player_id)) {
			$where[] = "total_player_game_day_timezone.player_id = ?";
			$values[] = $player_id;
			$show_player = true;
		} else if (isset($input['affiliate_username']) && !$this->utils->isFromHost('aff')) {

			$this->load->model(array('affiliatemodel'));
            $affiliate_detail = (array)$this->affiliatemodel->getAffiliateByUsername($input['affiliate_username']);
			if (isset($input['include_all_downlines_aff']) && $input['include_all_downlines_aff'] == true && !empty($affiliate_detail)) {

    			$parent_ids = array($affiliate_detail['affiliateId']);
    			$sub_ids = array();
    			$all_ids = $parent_ids;
    			while (!empty($sub_ids = $this->affiliatemodel->get_sub_affiliate_ids_by_parent_id($parent_ids))) {
    				$all_ids = array_merge($all_ids, $sub_ids);
    				$parent_ids = $sub_ids;
    				$sub_ids = array();
    			}
    			foreach ($all_ids as $i => $id) {
    				if ($i == 0) {
    					$w = "(player.affiliateId = ?";
    				} else {
    					$w .= " OR player.affiliateId = ?";
    				}
    				$values[] = $id;
    			}
    			$w .= ")";
    			$where[] = $w;
    		} else {
    			$where[] = "affiliates.username = ?";
				$values[] = $input['affiliate_username'];
    		}
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

			if (isset($input['include_all_downlines']) && $input['include_all_downlines'] == 'on' && !empty($agent_detail)) {
				$joins['agency_agents'] = 'player.agent_id = agency_agents.agent_id';
				$parent_ids = array($agent_detail['agent_id']);
				$sub_ids = array();
				$all_ids = $parent_ids;
				while (!empty($sub_ids = $this->agency_model->get_sub_agent_ids_by_parent_id($parent_ids))) {
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

		if($this->utils->isFromHost('aff')) {

			$affiliateId = $this->session->userdata('affiliateId');

            if (isset($input['affiliate_username'])) {

            	$this->load->model(array('affiliatemodel'));
            	$searchedAffiliateId = $this->affiliatemodel->getAffiliateByUsername($input['affiliate_username'])->affiliateId;
            	if ($searchedAffiliateId  != $affiliateId  && !$this->affilitatemodel->is_upline($searchedAffiliateId, $affiliatId)) {

            		$this->load->model(array('affiliatemodel'));
            		$affiliate_detail = (array)$this->affiliatemodel->getAffiliateByUsername($input['affiliate_username']);

			        //not implemented yet but just in case we need
            		if (isset($input['include_all_downlines']) && $input['include_all_downlines'] == true && !empty($affiliate_detail)) {

            			$parent_ids = array($affiliate_detail['affiliateId']);
            			$sub_ids = array();
            			$all_ids = $parent_ids;
            			while (!empty($sub_ids = $this->affiliatemodel->get_sub_affiliate_ids_by_parent_id($parent_ids))) {
            				$all_ids = array_merge($all_ids, $sub_ids);
            				$parent_ids = $sub_ids;
            				$sub_ids = array();
            			}
            			foreach ($all_ids as $i => $id) {
            				if ($i == 0) {
            					$w = "(player.affiliateId = ?";
            				} else {
            					$w .= " OR player.affiliateId = ?";
            				}
            				$values[] = $id;
            			}
            			$w .= ")";
            			$where[] = $w;
            		} else {
            			$where[] = "player.affiliateId = ?";
            			$values[] = $affiliate_detail['affiliateId'];
            		}
            	};

            }else{
            	$where[] = "player.affiliateId = ?";
            	$values[] = $affiliateId;
            }

		}


		# DEFINE TABLE COLUMNS ########################################################################################################################################################################
		$i = 0;
		$columns = array(
			array(
				'alias' => 'player_playerId',
				'select' => 'player.playerId'
			),

			array(
				'alias' => 'player_levelName',
				'select' => 'vipsettingcashbackrule.vipLevelName'
			),
			array(
				'alias' => 'player_groupName',
				'select' => 'vipsetting.groupName'
			),
			array(
				'alias' => 'affiliates_affiliateId',
				'select' => 'affiliates.affiliateId'
			),
			array(
				'alias' => 'external_system_id',
				'select' => 'external_system.id'
			),
			array(
				'alias' => 'game_description_id',
				'select' => $show_game ? 'game_description.id' : '"N/A"',
			),
			array(
				'alias' => 'game_type_id',
				'select' => $show_game_type ? 'game_type.id' : '"N/A"',
			),
			array(
				'dt' => $i++,
				'alias' => 'system_code',
				'select' => $show_game_platform ? 'external_system.system_code' : '"N/A"',
				'formatter' => function($d, $row) use ($is_export)  {

                     if ($is_export) {
                     	return $d;
                     }else{
                     	return $d. '<p class=" hide gamePlatformId">'. $row['external_system_id']. '</p>';
                     }

				},
				'name' => lang('Game Platform'),
			),
			array(
				'dt' => $i++,
				'alias' => 'game_type_lang',
				'select' => $show_game_type ? 'game_type.game_type_lang' : '"N/A"',
				'formatter' => 'languageFormatter',
				'name' => lang('Game Type'),
			),
			array(
				'dt' => $i++,
				'alias' => 'game_name',
				'select' => $show_game ? 'game_description.game_name' : '"N/A"',
				'formatter' => 'languageFormatter',
				'name' => lang('Game'),
			),
			array(
				'dt' => $i++,
				'alias' => 'player_username',
				'select' => $show_player ? 'player.username' : '"N/A"',
				'formatter' => function($d, $row) use ($is_export) {

					if ($this->utils->isAffSubProject() && $this->utils->isEnabledFeature('masked_player_username_on_affiliate')) {
						$d = $this->utils->keepOnlyString($d, 4);
					}

					if($d != 'N/A') {
						return $is_export ? $d : '<a href="/player_management/userInformation/' . $row['player_playerId'] . '" target="_blank">' . $d . '</a>';
					}
					return $d;
				},
				'name' => lang('Player Username'),
			),

			array(
				'dt' => $i++,
				'alias' => 'player_level',
				'select' => $show_player ? 'player.levelId' : '"N/A"',
				'formatter' => function($d, $row){
					if($d != 'N/A') {
						return lang($row['player_groupName']).' - '.lang($row['player_levelName']);
					}
		  			return $d;

				},
				'name' => lang('Player Level'),

			),
			array(
				'dt' => $i++,
				'alias' => 'affiliate',
				'select' => 'affiliates.username',
				'formatter' => function($d, $row) use ($is_export) {
					if ($this->utils->isEnabledFeature('masked_affiliate_username_on_affiliate')) {
						$d = $this->utils->keepOnlyString($d, 4);
					}

					if($d) {
						if($this->utils->isFromHost('aff')) {
							return trim(trim($d), ',') ?: lang('lang.norecyet');
						}
						return $is_export ? $d : '<a href="/affiliate_management/userInformation/' . $row['affiliates_affiliateId'] . '" target="_blank">' . $d . '</a>';
					} else {

					}
				},
				'name' => lang('aff.as03'),
			),
			array(
				'dt' => $i++,
				'alias' => 'total_player',
				'select' => 'COUNT(DISTINCT total_player_game_day_timezone.player_id)',
				'formatter' => function ($d, $row)  use ($is_export)  {
					return $d;
				},
				'name' => lang('aff.as24'),
			),
			array(
				'dt' => $i++,
				'alias' => 'total_bet',
				'select' => $unsettle ? 'SUM(total_player_game_day_timezone.bet_amount)' : 'SUM(total_player_game_day_timezone.betting_amount)',
				'formatter' =>  function($d, $row) use ($is_export) {
					return $this->currencyFormatter($d, $is_export);
				},
				'name' => lang('report.g09'),
			),
			array(
				'dt' => $i++,
				'alias' => 'total_payout',
				'select' => 'SUM(total_player_game_day_timezone.betting_amount - (total_player_game_day_timezone.loss_amount - total_player_game_day_timezone.win_amount))',
				'formatter' =>  function($d, $row) use ($is_export) {
					return $this->currencyFormatter($d, $is_export);
				},
				'name' => lang('Agency Payout'),
			),

			array(
				'dt' => $i++,
				'alias' => 'total_gain',
				'select' => 'SUM(total_player_game_day_timezone.win_amount)',
				'formatter' =>  function($d, $row) use ($is_export) {
					return $this->currencyFormatter($d, $is_export);
				},
				'name' => lang('report.g10'),
			),
			array(
				'dt' => $i++,
				'alias' => 'total_loss',
				'select' => 'SUM(total_player_game_day_timezone.loss_amount)',
				'formatter' =>  function($d, $row) use ($is_export) {
					return $this->currencyFormatter($d, $is_export);
				},
				'name' => lang('report.g11'),
			),
			array(
				'dt' => $i++,
				'alias' => 'total_revenue',
				'select' => 'SUM(total_player_game_day_timezone.loss_amount - total_player_game_day_timezone.win_amount)',
				'formatter' =>  function($d, $row) use ($is_export) {
					return $this->currencyFormatter($d, $is_export);
				},
				'name' => lang('Game Revenue'),
			),
			array(
				'dt' => $i++,
				'alias' => 'total_revenue_percent',
				'select' => $unsettle ? 'SUM(total_player_game_day_timezone.loss_amount-total_player_game_day_timezone.win_amount)/SUM(total_player_game_day_timezone.bet_amount)' : 'SUM(total_player_game_day_timezone.loss_amount-total_player_game_day_timezone.win_amount)/SUM(total_player_game_day_timezone.betting_amount)',
				'formatter' => 'percentageFormatter',
				'name' => lang('Game Revenue %'),
			),
		);

		# FILTER ######################################################################################################################################################################################

		if (isset($input['datetime_from'], $input['datetime_to'])) {
			$date_from_str = $input['datetime_from'];
			$date_to_str = $input['datetime_to'];
			$where[] ='total_player_game_day_timezone.date >= ?';
			$where[] ='total_player_game_day_timezone.date <= ?';
			$values[] = date('Ymd', strtotime($date_from_str));
			$values[] = date('Ymd', strtotime($date_to_str));
		}

		if (isset($input['timezone'])) {
			$where[] ='total_player_game_day_timezone.timezone = ?';
			$values[] = $input['timezone'];
		}
		############################################

		if (isset($input['total_bet_from'])) {
			$having['total_bet >='] = $input['total_bet_from'];
		}

		if (isset($input['total_bet_to'])) {
			$having['total_bet <='] = $input['total_bet_to'];
		}

		if (isset($input['total_gain_from'])) {
			$having['total_gain >='] = $input['total_gain_from'];
		}

		if (isset($input['total_gain_to'])) {
			$having['total_gain <='] = $input['total_gain_to'];
		}

		if (isset($input['total_loss_from'])) {
			$having['total_loss >='] = $input['total_loss_from'];
		}

		if (isset($input['total_loss_to'])) {
			$having['total_loss <='] = $input['total_loss_to'];
		}

		if (isset($input['total_player'])) {
			$having['total_player <='] = $input['total_player'];
		}


        // to see test player game reports  on their user information page
         if(empty($player_id)){
            $where[] = "player.deleted_at IS NULL";
         }

         if($is_export){
            $this->data_tables->options['is_export']=true;
            if(empty($csv_filename)){
                $csv_filename=$this->utils->create_csv_filename(__FUNCTION__);
            }
            $this->data_tables->options['csv_filename']=$csv_filename;
		}


		# OUTPUT ######################################################################################################################################################################################
		$result = $this->data_tables->get_data($request, $columns, $table, $where, $values, $joins, $group_by, $having, false);

		if($is_export){
		    //drop result if export
			return $csv_filename;
		}

		$result['summary'] = array();
		$total_revenue_percent = $unsettle ? 'SUM(total_player_game_day_timezone.loss_amount-total_player_game_day_timezone.win_amount)/SUM(total_player_game_day_timezone.bet_amount) * 100 total_revenue_percent' : 'SUM(total_player_game_day_timezone.loss_amount-total_player_game_day_timezone.win_amount)/SUM(total_player_game_day_timezone.betting_amount) * 100 total_revenue_percent';
		$total_bet = $unsettle ? 'SUM(total_player_game_day_timezone.bet_amount) total_bet ' : 'SUM(total_player_game_day_timezone.betting_amount) total_bet ';
		$total_ave_count = $unsettle ? 'COUNT(total_player_game_day_timezone.bet_amount) total_bet ' : 'COUNT(total_player_game_day_timezone.betting_amount) total_ave_count ';
    	$total_ave_bet = $unsettle ? 'SUM(total_player_game_day_timezone.bet_amount) /COUNT(total_player_game_day_timezone.bet_amount)   ' : 'SUM(total_player_game_day_timezone.betting_amount) / COUNT(total_player_game_day_timezone.betting_amount)   as total_ave_bet ';
		$total_payout = $unsettle ? 'SUM(total_player_game_day_timezone.bet_amount - (total_player_game_day_timezone.loss_amount - total_player_game_day_timezone.win_amount)) total_payout ' : 'SUM(total_player_game_day_timezone.betting_amount - (total_player_game_day_timezone.loss_amount - total_player_game_day_timezone.win_amount)) total_payout ';

		// Summary calculation
		$summary = $this->data_tables->summary($request, $table, $joins, 'COUNT(DISTINCT total_player_game_day_timezone.player_id) total_player, '.$total_bet.','.$total_ave_bet.','.$total_ave_count.'  ,SUM(total_player_game_day_timezone.win_amount) as total_win, SUM(total_player_game_day_timezone.loss_amount) total_loss,'.$total_payout.',SUM(total_player_game_day_timezone.loss_amount-total_player_game_day_timezone.win_amount) total_revenue,'.$total_revenue_percent.'', null, $columns, $where, $values);

		array_walk($summary[0], function (&$value) {
			$value = round(floatval($value), 2);
		});
		$result['summary'] = $summary;

		if ($this->utils->isEnabledFeature('display_player_bets_per_game')) {
			// ----- player_total_bets_per_game calculation
			$group_by2=[];
			$group_by2[] = 'total_player_game_day_timezone.player_id, total_player_game_day_timezone.game_platform_id';
			$player_total_bets_per_game = $this->data_tables->summary($request, $table, $joins, 'DISTINCT total_player_game_day_timezone.player_id player_id , player.username, total_player_game_day_timezone.game_platform_id, '.$total_bet.', SUM(total_player_game_day_timezone.win_amount) as total_win, SUM(total_player_game_day_timezone.loss_amount) total_loss,'.$total_payout.', SUM(total_player_game_day_timezone.loss_amount-total_player_game_day_timezone.win_amount) total_revenue,'.$total_revenue_percent.'', $group_by2, $columns, $where, $values);


			$player_total_bets_per_game_map = [];
			$game_platform_header_map =[];
	        $sum_total_bets_map =[];
	        $sum_total_wins_map =[];
	        $sum_total_loss_map =[];
	        $sum_total_payout_map =[];
	        $sum_total_revenue_map =[];
	        $bet_details_map = [];

			foreach ($player_total_bets_per_game as $v) {

	            //make bet details array
				$bet_details_map[$v['player_id']][$v['game_platform_id']] = array(
					'game_platform_id' => $v['game_platform_id'],
					'total_bet' => $this->currencyFormatter($v['total_bet']),
					'total_win' => $this->currencyFormatter($v['total_win']),
					'total_loss' => $this->currencyFormatter($v['total_loss']),
					'total_payout' => $this->currencyFormatter($v['total_payout']),
					'total_revenue' => $this->currencyFormatter($v['total_revenue']),
					'total_revenue_percent' => $this->currencyFormatter($v['total_revenue_percent']),
				);

	            //array push every games bet per player
				$sum_total_bets_map[$v['player_id']][] = $v['total_bet'] ;
				$sum_total_wins_map[$v['player_id']][] = $v['total_win'] ;
				$sum_total_loss_map[$v['player_id']][] = $v['total_loss'] ;
				$sum_total_payout_map[$v['player_id']][] = $v['total_payout'] ;
				$sum_total_revenue_map[$v['player_id']][] = $v['total_revenue'] ;
				$sum_total_revenue_percent_map[$v['player_id']][] = $v['total_revenue_percent'] ;
				//add the bet items to get the total bets for all player game
	            $player_total_games_bet = $this->currencyFormatter(array_sum($sum_total_bets_map[$v['player_id']]));
	            $player_total_games_win = $this->currencyFormatter(array_sum($sum_total_wins_map[$v['player_id']]));
	            $player_total_games_loss = $this->currencyFormatter(array_sum($sum_total_loss_map[$v['player_id']]));
	            $player_total_games_payout = $this->currencyFormatter(array_sum($sum_total_payout_map[$v['player_id']]));
	            $player_total_games_revenue = $this->currencyFormatter(array_sum($sum_total_revenue_map[$v['player_id']]));
	            $player_total_games_revenue_percent = $this->currencyFormatter(array_sum($sum_total_revenue_percent_map[$v['player_id']]));
	             //construct now the player row
	            $player_row = [];
	            $player_row['player_id'] = $v['player_id'];
	            $player_row['username'] = $v['username'];
	            $player_row['bet_details'] =  $bet_details_map[$v['player_id']];
	            $player_row['sum_total_bets'] = $player_total_games_bet;
	            $player_row['sum_total_wins'] = $player_total_games_win;
	            $player_row['sum_total_loss'] = $player_total_games_loss;
	            $player_row['sum_total_payout'] = $player_total_games_payout;
	            $player_row['sum_total_revenue'] = $player_total_games_revenue;
	            $player_row['sum_total_revenue_percent'] = $player_total_games_revenue_percent;
	            // add on main players map for json output
				$player_total_bets_per_game_map[$v['player_id']] =  $player_row;
				// make selective table headers  by making map -> associative override
				$game_platform_header_map[$v['game_platform_id']] = $v['game_platform_id'];

			}
	        $result['player_total_bets_per_game'] = $player_total_bets_per_game_map;
	        $result['game_platform_header_map'] = $game_platform_header_map;
	        // ----- End calculation of player_total_bets_per_game
	    }

		return $result;
	}

	/**
	 * Format bet details using template
	 */
	public function formatBetDetails($apiId, $d){
		if(empty($d)){
			return '';
		}
		$output = '';
		$data['data'] = $d;
		$view = $this->CI->load->view('games_report_template/game_history-'.$apiId, $data, TRUE);

		return $view;
	}

    /**
	 * array bet details default format
	 */
    public function getDefaultFormatForBetDetails($d) {
        $bet_details = '';

        if(!empty($d)) {
            $data = json_decode($d, true);
            if(is_array($data)) {
                foreach($data as $field_name => $detail) {
                    if (is_array($detail)) {
                        $bet_details = json_encode($data, JSON_PRETTY_PRINT);
                    } else {
                        $bet_details .= '<span style="white-space: nowrap;">' . lang($field_name) . ': ' . $detail . '</span><br>';
                    }
                }
            }else{
                $bet_details = $d;
            }
        }else{
            $bet_details = 'N/A';
        }

        return $bet_details;
    }

   public function tournamentWinnerReports($request, $is_export = false){

		$i = 0;

		$columns = array();

		$columns[] = array(
			'alias' => 'id',
			'select' => 'game_tournaments_winners.id',
			'name' => lang("Id"),
			'formatter' => 'languageFormatter',
		);

		$columns[] = array(
			'dt' => $i++,
			'alias' => 'game',
			'name' => lang("Game"),
			'select' => 'external_system.system_name',
			'formatter' => 'languageFormatter',
		);
		$columns[] = array(
			'dt' => $i++,
			'alias' => 'tournament_name',
			'select' => 'game_tournaments_winners.tournament_name',
			'name' => lang("Tournament Name"),
			'formatter' => 'languageFormatter',
		);
		$columns[] = array(
			'dt' => $i++,
			'alias' => 'tournament_id',
			'select' => 'game_tournaments_winners.tournament_id',
			'name' => lang("Tournament Id"),
			'formatter' => 'languageFormatter',
		);
		$columns[] = array(
			'dt' => $i++,
			'alias' => 'player_username',
			'select' => 'game_tournaments_winners.player_username',
			'name' => lang("Player Username"),
			'formatter' => 'languageFormatter',
		);
		$columns[] = array(
			'dt' => $i++,
			'alias' => 'position',
			'select' => 'game_tournaments_winners.position',
			'name' => lang("Position"),
			'formatter' => 'languageFormatter',
		);
		$columns[] = array(
			'dt' => $i++,
			'alias' => 'score',
			'select' => 'game_tournaments_winners.score',
			'name' => lang("Score"),
			'formatter' => 'languageFormatter',
		);
		$columns[] = array(
			'dt' => $i++,
			'alias' => 'prize_amount',
			'select' => 'game_tournaments_winners.prize_amount',
			'name' => lang("Price Amount"),
			'formatter' => 'languageFormatter',
		);
		$columns[] = array(
			'dt' => $i++,
			'alias' => 'currency',
			'select' => 'game_tournaments_winners.currency',
			'name' => lang("Currency"),
			'formatter' => 'languageFormatter',
		);
		$columns[] = array(
			'dt' => $i++,
			'alias' => 'end_at',
			'name' => lang("End at"),
			'select' => 'game_tournaments_winners.end_at',
		);
		$columns[] = array(
			'dt' => $i++,
			'alias' => 'start_at',
			'name' => lang("Start at"),
			'select' => 'game_tournaments_winners.start_at',
		);


		$table = 'game_tournaments_winners';
		$joins = array(
			'external_system' => 'external_system.id = game_tournaments_winners.game_platform_id',
		);

		# START PROCESS SEARCH FORM #################################################################################################################################################
		$where = array();
		$values = array();

		$this->load->library('data_tables');
		$input = $this->data_tables->extra_search($request);
		if(isset($input['datetime_from'], $input['datetime_to'])) {
			$where[] ='game_tournaments_winners.start_at >= ?';
			$where[] ='game_tournaments_winners.end_at <= ?';
			$values[] = $input['datetime_from'];
			$values[] = $input['datetime_to'];
		}
		if(isset($input['username'])) {
			$where[] ='game_tournaments_winners.player_username = ?';
			$values[] = $input['username'];
		}

		$result = $this->data_tables->get_data($request, $columns, $table, $where, $values, $joins);
		return $result;
    }

    /**
	 * detail: get game report for billing
	 *
	 * @param array $request
	 * @param bool $is_export
	 *
	 * @return array
	 */
	public function gameBillingReports($request, $is_export = false, $permission = null) {
		// echo 1231231231;exit();
		$this->load->library(array('data_tables'));
		$this->load->model(array('player_model','game_logs','game_type_model', 'affiliatemodel'));
		$this->load->helper(['player_helper', 'aff_helper']);

		$input = $this->data_tables->extra_search($request);
		// echo "<pre>";
		// print_r($input);exit();
		$table = 'game_billing_report';
		$joins = array();
		$where = array();
		$values = array();
		$group_by = array();
		$having = array();

		$joins['game_type'] = 'game_type.id = game_billing_report.game_type_id';
		$joins['game_description'] = 'game_description.id = game_billing_report.game_description_id';
		$joins['external_system'] = 'external_system.id = game_billing_report.game_platform_id';

		$group_by_field = null;
		$show_game_platform = false;
		$show_game_type = false;
		$show_game = false;
		$show_player_tag = false;
		$show_player = false;
		$show_affiliate_tag = true;

		$group_by[] = 'game_billing_report.game_type_id';
		if(isset($input['game_type_multiple'])) {
			if($input['game_type_multiple'] != 0) {
				$game_type_arr = explode('+', $input['game_type_multiple']);
             if (isset($input['game_type'])){
             	array_push($game_type_arr, $input['game_type']);
             }
				$game_types =  $game_type_arr;
				$where[] = "game_type.id in (" . implode(',', $game_types) . ") ";
			}
		}

		# DEFINE TABLE COLUMNS ########################################################################################################################################################################
		$i = 0;
		$columns = array(
			array(
				'alias' => 'external_system_id',
				'select' => 'external_system.id'
			),
			array(
				'dt' => $i++,
				'alias' => 'system_code',
				'select' => 'external_system.system_code',
				'formatter' => function($d, $row) use ($is_export)  {

                     if ($is_export) {
                     	return $d;
                     }else{
                     	return $d. '<p class=" hide gamePlatformId">'. $row['external_system_id']. '</p>';
                     }

				},
				'name' => lang('Game Platform'),
			),
			array(
				'dt' => $i++,
				'alias' => 'game_type_lang',
				'select' => 'game_type.game_type_lang',
				'formatter' => 'languageFormatter',
				'name' => lang('Game Type'),
			),
			array(
				'dt' => $i++,
				'alias' => 'timezone',
				'select' => 'game_billing_report.timezone',
				'formatter' => 'languageFormatter',
				'name' => lang('Timezone'),
			),
			array(
				'dt' => $i++,
				'alias' => 'start_day',
				'select' => 'game_billing_report.start_of_the_month',
				'formatter' => 'languageFormatter',
				'name' => lang('Start Day'),
			),
			array(
				'dt' => $i++,
				'alias' => 'game_fee',
				'select' => 'game_billing_report.game_fee',
				'formatter' => 'languageFormatter',
				'name' => lang('Game Fee'),
			),
			array(
				'dt' => $i++,
				'alias' => 'total_player',
				'select' => 'COUNT(DISTINCT game_billing_report.player_id)',
				'formatter' => function ($d, $row)  use ($is_export)  {
					return $d;
				},
				'name' => lang('aff.as24'),
			),
			array(
				'dt' => $i++,
				'alias' => 'total_bet',
				'select' => 'SUM(game_billing_report.betting_amount)',
				'formatter' =>  function($d, $row) use ($is_export) {
					return $this->currencyFormatter($d, $is_export);
				},
				'name' => lang('report.g09'),
			),
			array(
				'dt' => $i++,
				'alias' => 'total_payout',
				'select' => 'SUM(game_billing_report.betting_amount - (game_billing_report.loss_amount - game_billing_report.win_amount))',
				'formatter' =>  function($d, $row) use ($is_export) {
					return $this->currencyFormatter($d, $is_export);
				},
				'name' => lang('Agency Payout'),
			),

			array(
				'dt' => $i++,
				'alias' => 'total_gain',
				'select' => 'SUM(game_billing_report.win_amount)',
				'formatter' =>  function($d, $row) use ($is_export) {
					return $this->currencyFormatter($d, $is_export);
				},
				'name' => lang('report.g10'),
			),
			array(
				'dt' => $i++,
				'alias' => 'total_loss',
				'select' => 'SUM(game_billing_report.loss_amount)',
				'formatter' =>  function($d, $row) use ($is_export) {
					return $this->currencyFormatter($d, $is_export);
				},
				'name' => lang('report.g11'),
			),
			array(
				'dt' => $i++,
				'alias' => 'total_revenue',
				'select' => 'SUM(game_billing_report.loss_amount - game_billing_report.win_amount)',
				'formatter' =>  function($d, $row) use ($is_export) {
					return $this->currencyFormatter($d, $is_export);
				},
				'name' => lang('Game Revenue'),
			),
			array(
				'dt' => $i++,
				'alias' => 'total_revenue_percent',
				'select' => 'SUM(game_billing_report.loss_amount-game_billing_report.win_amount)/SUM(game_billing_report.betting_amount)',
				'formatter' => 'percentageFormatter',
				'name' => lang('Game Revenue %'),
			),
		);

		if(isset($input['month'])){
			$where[] ='game_billing_report.`month` = ?';
			$values[] = date('Ym', strtotime($input['month']));
		}

		if($is_export){
		   $this->data_tables->options['is_export']=true;
		   if(empty($csv_filename)){
		       $csv_filename=$this->utils->create_csv_filename(__FUNCTION__);
		   }
		   $this->data_tables->options['csv_filename']=$csv_filename;
		}


		# OUTPUT ######################################################################################################################################################################################
		$result = $this->data_tables->get_data($request, $columns, $table, $where, $values, $joins, $group_by, $having, false);
		if(!empty($this->data_tables->last_query) ){
			$last_query = $this->data_tables->last_query;
			$result['list_last_query'] = $last_query;
		}
		// echo "<pre>";
		// print_r($where);exit();

		if($is_export){
		    //drop result if export
			return $csv_filename;
		}

		if( ! empty($this->data_tables->last_query) ){
			$result['list_last_query'] = $this->data_tables->last_query;
		}

		$result['summary'] = array();
		$total_revenue_percent = 'SUM(game_billing_report.loss_amount-game_billing_report.win_amount)/SUM(game_billing_report.betting_amount) * 100 total_revenue_percent';
		$total_bet = 'SUM(game_billing_report.betting_amount) total_bet ';
		$total_ave_count = 'COUNT(game_billing_report.betting_amount) total_ave_count ';
    	$total_ave_bet = 'SUM(game_billing_report.betting_amount) / COUNT(game_billing_report.betting_amount)   as total_ave_bet ';
		$total_payout = 'SUM(game_billing_report.betting_amount - (game_billing_report.loss_amount - game_billing_report.win_amount)) total_payout ';

		// Summary calculation
		$summary = $this->data_tables->summary($request, $table, $joins, 'COUNT(DISTINCT game_billing_report.player_id) total_player, '.$total_bet.','.$total_ave_bet.','.$total_ave_count.'  ,SUM(game_billing_report.win_amount) as total_win, SUM(game_billing_report.win_amount) as total_gain, SUM(game_billing_report.loss_amount) total_loss,'.$total_payout.',SUM(game_billing_report.loss_amount-game_billing_report.win_amount) total_revenue,'.$total_revenue_percent.'', null, $columns, $where, $values);
		$result['summary_last_query'] = $this->data_tables->last_query;
		
		if(isset($summary[0])){
			array_walk($summary[0], function (&$value) {
				$value = round(floatval($value), 2);
			});
		} else {
			$summary[0] = array(
				'total_player' => 0,
				'total_bet' => 0,
				'total_ave_bet' => 0,
				'total_ave_count' => 0,
				'total_win' => 0,
				'total_gain' => 0,
				'total_loss' => 0,
				'total_payout' => 0,
				'total_revenue' => 0,
				'total_revenue_percent' => 0,
			);
		}
		$result['summary'] = $summary;
		return $result;
	}

	/**
	 * detail: query game history for export
	 *
	 * @param array $request
	 *
	 * @return array
	 */
	public function queryGameHistoryForExport($dateFrom = null, $dateTo = null) {
		$this->load->library(array('data_tables'));
		
		if(empty($dateFrom)){
			$dateFrom = new DateTime();
      	$dateFrom = $dateFrom->format('Y-m-d H:00:00');
		}

		if(empty($dateTo)){
			$dateTo = new DateTime();
      	$dateTo = $dateTo->format('Y-m-d H:59:59');
		}
		$request = array("dateFrom" => $dateFrom, "dateTo" => $dateTo);
		$table = 'game_logs';
		$joins = array();
		$where = array();
		$values = array();
		$group_by = array();
		$having = array();

		$joins['game_description'] = 'game_description.id = game_logs.game_description_id';
		$joins['external_system'] = 'external_system.id = game_logs.game_platform_id';
		$joins['player'] = 'player.playerId = game_logs.player_id';


		# DEFINE TABLE COLUMNS ########################################################################################################################################################################
		$i = 0;
		$columns = array(
			array(
				'alias' => 'external_system_id',
				'select' => 'external_system.id'
			),
			array(
				'dt' => $i++,
				'alias' => 'GAMEDATE',
				'select' => 'game_logs.start_at',
				'formatter' => 'languageFormatter',
				'name' => lang('GAMEDATE'),
			),
			array(
				'dt' => $i++,
				'alias' => 'GAMENAME',
				'select' => 'game_description.english_name',
				'formatter' => 'languageFormatter',
				'name' => lang('GAMENAME'),
			),
			array(
				'dt' => $i++,
				'alias' => 'GAMEPROVIDER',
				'select' => 'external_system.system_name',
				'formatter' => 'languageFormatter',
				'name' => lang('GAMEPROVIDER'),
			),
			array(
				'dt' => $i++,
				'alias' => 'PLAYERACCOUNT',
				'select' => 'player.username',
				'formatter' => 'languageFormatter',
				'name' => lang('PLAYERACCOUNT'),
			),
			array(
				'dt' => $i++,
				'alias' => 'SESSIONID',
				'select' => 'game_logs.external_uniqueid',
				'formatter' => 'languageFormatter',
				'name' => lang('SESSIONID'),
			),
			array(
				'dt' => $i++,
				'alias' => 'TOTALSTAKES',
				'select' => 'game_logs.bet_amount',
				// 'formatter' => 'languageFormatter',
				'formatter' =>  function($d, $row) {
					$betAmount = !empty($d) ? $d : 0;
					return $betAmount;
				},
				'name' => lang('TOTALSTAKES'),
			),
			array(
				'dt' => $i++,
				'alias' => 'TOTALWINS',
				'select' => 'game_logs.win_amount',
				// 'formatter' => 'languageFormatter',
				'formatter' =>  function($d, $row) {
					$winAmount = !empty($d) ? $d : 0;
					return $winAmount;
				},
				'name' => lang('TOTALWINS'),
			),
			array(
				'dt' => $i++,
				'alias' => 'TRANSACTIONID',
				'select' => 'game_logs.table',
				'formatter' => 'languageFormatter',
				'name' => lang('TRANSACTIONID'),
			),
			array(
				'dt' => $i++,
				'alias' => 'UPDATEDATETIME',
				'select' => 'game_logs.updated_at',
				'formatter' => 'languageFormatter',
				'name' => lang('UPDATEDATETIME'),
			),
			array(
				'dt' => $i++,
				'alias' => 'SETTLEMENTTIME',
				'select' => 'game_logs.end_at',
				'formatter' => 'languageFormatter',
				'name' => lang('SETTLEMENTTIME'),
			),

		);

		$where[] ='game_logs.end_at >= ? AND game_logs.end_at <= ? AND game_logs.flag = ?';
		$values[] = $dateFrom;
		$values[] = $dateTo;
		$values[] = true;

		
	   

      $now = $this->utils->getTimestampNow();
      $from = new DateTime($dateFrom);
      $to = new DateTime($dateTo);
      $from = $from->format('YmdHis');
      $to = $to->format('YmdHis');
	   $csv_filename = "GAMELOGS_{$from}_TO_{$to}_{$now}";
	   $this->data_tables->options['csv_filename']=$csv_filename;
	   $this->data_tables->options['is_export']=true;

		# OUTPUT ######################################################################################################################################################################################
		$result = $this->data_tables->get_data($request, $columns, $table, $where, $values, $joins, $group_by, $having, false);
		if(!empty($this->data_tables->last_query) ){
			$last_query = $this->data_tables->last_query;
			$result['list_last_query'] = $last_query;
		}

		$totalCount = 0;
		$summary = $this->data_tables->summary($request, $table, [], 'COUNT(DISTINCT game_logs.id) totalCount', null, $columns, $where, $values);

		if(isset($summary[0]['totalCount'])){
			$totalCount = $summary[0]['totalCount'];
		}

		if($totalCount > 0){
			return $csv_filename;
		}

		return null;
	}

	/**
	 * detail: get game report for game logs export hourly
	 *
	 * @param array $request
	 * @param bool $is_export
	 *
	 * @return array
	 */
	public function gamelogsExportHourly($request) {

		$this->load->library(array('data_tables'));
		$this->load->model(array('player_model','game_logs','game_type_model', 'affiliatemodel'));
		$this->load->helper(['player_helper', 'aff_helper']);

		$input = $this->data_tables->extra_search($request);
		$table = 'game_logs_export_hour';
		$joins = array();
		$where = array();
		$values = array();
		$group_by = array();
		$having = array();


		# DEFINE TABLE COLUMNS ########################################################################################################################################################################
		$i = 0;
		$columns = array(
			array(
				'alias' => 'game_logs_export_hour_id',
				'select' => 'game_logs_export_hour.id'
			),
			array(
				'dt' => $i++,
				'alias' => 'date_hour',
				'select' => 'game_logs_export_hour.date_hour',
				'name' => lang('Date hour'),
			),
			array(
				'dt' => $i++,
				'alias' => 'file_name',
				'select' => 'game_logs_export_hour.file_name',
				// 'formatter' => 'languageFormatter',
				'formatter' =>  function($d, $row) {
					return '<a href="/reports/' . $d . '.csv" target="_blank">' . $d . '</a>';
				},
				'name' => lang('Filename'),
			),
			array(
				'dt' => $i++,
				'alias' => 'created_at',
				'select' => 'game_logs_export_hour.created_at',
				'formatter' => 'languageFormatter',
				'name' => lang('Created at'),
			),
		);

		if(isset($input['date'])){
			$where[] ='game_logs_export_hour.`date` = ?';
			$date = date('Ymd', strtotime($input['date']));
			$values[] =$date;
		}

		# OUTPUT ######################################################################################################################################################################################
		$result = $this->data_tables->get_data($request, $columns, $table, $where, $values, $joins, $group_by, $having, false);

		return $result;
	}

	/**
	 * detail: get player agent info
	 *
	 * @param array $request
	 *
	 * @return array
	 */
	public function playerAffiliateAgent($request) {
		$this->load->library('permissions');
		if( !$this->permissions->checkPermissions('assign_player_under_affiliate') || !$this->permissions->checkPermissions('assign_player_under_agent') ){
			return array(
				"data" => [],
			);
		}


		$this->load->library(array('data_tables'));
		$this->load->model(array('player_model','game_logs','game_type_model', 'affiliatemodel'));
		$this->load->helper(['player_helper', 'aff_helper']);

		$input = $this->data_tables->extra_search($request);
		$username = isset($input['player_name']) ? $input['player_name'] : '';
		$table = 'player';
		$joins = array();
		$joins['affiliates'] = 'affiliates.affiliateId = player.affiliateId';
		$joins['agency_agents'] = 'agency_agents.agent_id = player.agent_id';
		$where = array();
		$values = array();
		$group_by = array();
		$having = array();


		# DEFINE TABLE COLUMNS ########################################################################################################################################################################
		$i = 0;
		$columns = array(
			array(
				'alias' => 'playerId',
				'select' => 'player.playerId'
			),
			array(
				'dt' => $i++,
				'alias' => 'username',
				'select' => 'player.username',
				'name' => lang('Username'),
			),
			array(
				'dt' => $i++,
				'alias' => 'affiliates',
				'select' => 'affiliates.username',
				'name' => lang('Affiliates'),
				'formatter' => function ($d, $row) {
					$output = lang("N/A");
					if(!empty($d)){
						$output = $d . " " .'<a href="javascript:void(0)" class="pull-right" onclick="return showAffList('.$row['playerId'].');" >'.lang('change').'</a>';
					}
					return $output;
				},
			),
			array(
				'dt' => $i++,
				'alias' => 'agency_agents',
				'select' => 'agency_agents.agent_name',
				'formatter' => function ($d, $row) {
					$output = lang("N/A");
					if(!empty($d)){
						$output = $d . " " .'<a href="javascript:void(0)" class="pull-right" onclick="return showAgentList('.$row['playerId'].');" >'.lang('change').'</a>';
					}
					return $output;
				},
				'name' => lang('Agents'),
			),
		);

		
		$where[] ='player.`username` = ?';
		$values[] =$username;
		

		# OUTPUT ######################################################################################################################################################################################
		$result = $this->data_tables->get_data($request, $columns, $table, $where, $values, $joins, $group_by, $having, false);

		return $result;
	}

	/**
	 * detail: get playerGameAndTransactionSummaryReport
	 *
	 * @param array $request
	 * @param bool $is_export
	 *
	 * @return array
	 */
	public function playerGameAndTransactionSummaryReport($request, $is_export = false, $permission = null) {

		$this->load->library(array('data_tables'));
		$this->load->model(array('player_model','game_logs','game_type_model', 'affiliatemodel'));
		$this->load->helper(['player_helper', 'aff_helper']);

		$input = $this->data_tables->extra_search($request);
		$table = 'total_game_transaction_monthly';
		$joins = array();
		$where = array();
		$values = array();
		$group_by = array();
		$having = array();

		$group_by_field = null;
		$show_game_platform = false;
		$show_game_type = false;
		$show_game = false;
		$show_player_tag = false;
		$show_player = false;
		$show_affiliate_tag = true;

		$group_by[] = 'total_game_transaction_monthly.player_id';


		# DEFINE TABLE COLUMNS ########################################################################################################################################################################
		$i = 0;
		$columns = array(
			array(
				'alias' => 'id',
				'select' => 'total_game_transaction_monthly.id'
			),
			array(
				'alias' => 'player_id',
				'select' => 'total_game_transaction_monthly.player_id'
			),
			array(
				'dt' => $i++,
				'alias' => 'player_username',
				'select' => 'total_game_transaction_monthly.player_username',
				'name' => lang('Player Username'),
				'formatter' => function($d, $row) use ($is_export) {
					return $is_export ? $d : '<a href="/player_management/userInformation/' . $row['player_id'] . '" target="_blank">' . $d . '</a>';
				},
			),
			array(
				'dt' => $i++,
				'alias' => 'total_amount_deposit',
				'select' => 'SUM(total_game_transaction_monthly.total_amount_deposit)',
				'formatter' =>  function($d, $row) use ($is_export) {
					return $this->currencyFormatter($d, $is_export);
				},
				'name' => lang('Total Deposit'),
			),
			array(
				'dt' => $i++,
				'alias' => 'total_amount_withdraw',
				'select' => 'SUM(total_game_transaction_monthly.total_amount_withdraw)',
				'formatter' =>  function($d, $row) use ($is_export) {
					return $this->currencyFormatter($d, $is_export);
				},
				'name' => lang('Total Withdraw'),
			),
			array(
				'dt' => $i++,
				'alias' => 'total_bonus',
				'select' => 'SUM(total_game_transaction_monthly.total_bonus)',
				'formatter' =>  function($d, $row) use ($is_export) {
					return $this->currencyFormatter($d, $is_export);
				},
				'name' => lang('Total Bonus'),
			),
			array(
				'dt' => $i++,
				'alias' => 'total_bet_amount',
				'select' => 'SUM(total_game_transaction_monthly.total_bet_amount)',
				'formatter' =>  function($d, $row) use ($is_export) {
					return $this->currencyFormatter($d, $is_export);
				},
				'name' => lang('Total Bet'),
			),
			array(
				'dt' => $i++,
				'alias' => 'total_net_loss',
				'select' => 'SUM(total_game_transaction_monthly.total_net_loss)',
				'formatter' =>  function($d, $row) use ($is_export) {
					return $this->currencyFormatter($d, $is_export);
				},
				'name' => lang('Total Net Loss'),
			),
		);

		if(isset($input['vpgts_player_username'])){
			$player_username = $input['vpgts_player_username'];
			$player_id = $this->player_model->getPlayerIdByUsername($player_username);
			$where[] ='total_game_transaction_monthly.`player_id` = ?';
			$values[] = $player_id;
		}

		if($is_export){
		   $this->data_tables->options['is_export']=true;
		   if(empty($csv_filename)){
		       $csv_filename=$this->utils->create_csv_filename(__FUNCTION__);
		   }
		   $this->data_tables->options['csv_filename']=$csv_filename;
		}


		# OUTPUT ######################################################################################################################################################################################
		$result = $this->data_tables->get_data($request, $columns, $table, $where, $values, $joins, $group_by, $having, false);
		if(!empty($this->data_tables->last_query) ){
			$last_query = $this->data_tables->last_query;
			$result['list_last_query'] = $last_query;
		}

		if($is_export){
		    //drop result if export
			return $csv_filename;
		}

		if( ! empty($this->data_tables->last_query) ){
			$result['list_last_query'] = $this->data_tables->last_query;
		}

		return $result;
	}
}
// zR to open all folded lines
// vim:ft=php:fdm=marker
// end of report_module_game.php
////END OF FILE/////////
