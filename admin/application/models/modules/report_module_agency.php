<?php

trait report_module_agency{

	public function agency_game_history($request, $player_id, $is_export){
		$this->load->model(array('game_logs','agency_model'));
		$this->load->library(['data_tables']);
		# START DEFINE COLUMNS #####################################################################

		$show_bet_detail_on_game_logs=$this->utils->isEnabledFeature('show_bet_detail_on_game_logs');

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
				'alias' => 'external_uniqueid',
				'select' => 'game_logs.external_uniqueid',
			),
			array(
				'dt' => $i++,
				'alias' => 'end_at',
				'select' => 'game_logs.end_at',
				'formatter' => 'dateTimeFormatter',
			),
			array(
				'dt' => $i++,
				'alias' => 'player_username',
				'select' => 'player.username',
				'formatter' => function ($d, $row) use ($is_export) {
					if($is_export){
						return $d;
					}else{
						return sprintf('<a href="/agency/player_information/%s">%s</a>', $row['playerId'], $d);
					}
				},
			),
			array(
				'dt' => $i++,
				'alias' => 'agent_username',
				'select' => 'agency_agents.agent_name',
				'formatter' => 'defaultFormatter',
			),
			array(
				'dt' => $i++,
				'alias' => 'game',
				'select' => 'external_system.system_code',
				'formatter' => 'defaultFormatter',
			),
			array(
				'dt' => $i++,
				'alias' => 'game_type_lang',
				'select' => 'game_type.game_type_lang',
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
				'formatter' => function ($d, $row) {
					//only for game type
					if($row['flag']==Game_logs::FLAG_TRANSACTION){
						return lang('N/A');
					}else{
						if($d==0){
							$d=$row['bet_amount'];
						}
						return $this->utils->formatCurrencyNoSym($d);
					}
				},
				// 'formatter' => 'currencyFormatter',
			),
			array(
				'dt' => $i++,
				'alias' => 'bet_amount',
				'select' => 'game_logs.bet_amount',
				'formatter' => function ($d, $row) {
					if($row['flag']==Game_logs::FLAG_TRANSACTION){
						return lang('N/A');
					}else{
						return $this->utils->formatCurrencyNoSym($d);
					}
				},
				// 'formatter' => 'currencyFormatter',
			),
			array(
				'dt' => $i++,
				'alias' => 'result_amount',
				'select' => 'game_logs.result_amount',
				'formatter' => function ($d, $row) {
					if($row['flag']==Game_logs::FLAG_TRANSACTION){
						return lang('N/A');
					}else{
						return $this->utils->formatCurrencyNoSym($d);
					}
				},
				// 'formatter' => 'currencyFormatter',
			),
			array(
				'dt' => $i++,
				'alias' => 'bet_plus_result_amount',
				'select' => 'game_logs.bet_amount + game_logs.result_amount',
				'formatter' => function ($d, $row) {
					if($row['flag']==Game_logs::FLAG_TRANSACTION){
						return lang('N/A');
					}else{
						return $this->utils->formatCurrencyNoSym($d);
					}
				},
				// 'formatter' => 'currencyFormatter',
			),
			array(
				'dt' => $i++,
				'alias' => 'win_amount',
				'select' => 'game_logs.win_amount',
				'formatter' => function ($d, $row) {
					if($row['flag']==Game_logs::FLAG_TRANSACTION){
						return lang('N/A');
					}else{
						return $this->utils->formatCurrencyNoSym($d);
					}
				},
				// 'formatter' => 'currencyFormatter',
			),
			array(
				'dt' => $i++,
				'alias' => 'loss_amount',
				'select' => 'game_logs.loss_amount',
				'formatter' => function ($d, $row) {
					if($row['flag']==Game_logs::FLAG_TRANSACTION){
						return lang('N/A');
					}else{
						return $this->utils->formatCurrencyNoSym($d);
					}
				},
				// 'formatter' => 'currencyFormatter',
			),
			array(
				'dt' => $i++,
				'alias' => 'after_balance',
				'select' => 'game_logs.after_balance',
				'formatter' => 'currencyFormatter',
			),
			/*array(
				'dt' => $i++,
				'alias' => 'trans_amount',
				'select' => 'game_logs.trans_amount',
				'formatter' => function ($d, $row) {
					if($row['flag']!=Game_logs::FLAG_TRANSACTION){
						return lang('N/A');
					}else{
						return $this->utils->formatCurrencyNoSym($d);
					}
				},
			),
			array(
				'dt' => $i++,
				'alias' => 'roundno',
				'select' => 'game_logs.table',
				'formatter' => function($d, $row) use($show_bet_detail_on_game_logs){
					$str='<p>'.$d.'</p>';
					if($show_bet_detail_on_game_logs){
						$str=$str.'<ul class="list-inline">'.
						'<li><a href="javascript:void(0)" onclick="betDetail(\''.$row['external_uniqueid'].'\')">'.lang('Bet Detail').'</a></li>'.
						'<li><a href="javascript:void(0)" onclick="betResult(\''.$row['external_uniqueid'].'\')">'.lang('Bet Result').'</a></li>'.
						'</ul>';
					}
					return $str;

				},
			),*/
			array(
				'dt' => $i++,
				'alias' => 'note',
				'select' => 'game_logs.note',
				'name' => lang('Note'),
				'formatter' => function ($d, $row) {
					if(!empty($d)){
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
				'formatter' => function ($d, $row) use($show_bet_detail_on_game_logs) {
					if (!empty($d)) {
						$data = json_decode($d, true);
						$betDetailLink = "";
						$platform_id = (int)$row['game_platform_id'];
						if (!empty($data) && $show_bet_detail_on_game_logs && is_array($data)) {
							foreach ($data as $key => $value) {
								if (!empty($value)) {
									if (!empty($betDetailLink))
										$betDetailLink .= ", ";
									if(is_array($value)){
										$value=formatDebugMessage($value);
									}else{
										$value=lang($value);
									}
									if ($platform_id == (SBOBET_API || ULTRAPLAY_API || PINNACLE_API || GAMEPLAY_SBTECH_API) ){
										if($key == 'sports_bet'){
											$key = 'Sports Bet';
											$res =  json_decode($value, true);
											$this->utils->debug_log('the data =======>', $res);
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
									}
									$betDetailLink .= lang($key) . " : " .$value;
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
							$betDetailLink = $d;
						}
						return $betDetailLink;
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
				'formatter' => 'defaultFormatter',
			),
			array(
				'dt' => $i++,
				'alias' => 'bet_type',
				'select' => 'game_logs.bet_type',
				'name' => 'Bet Type',
				'formatter' => function ($d, $row) {
					$unique_id = $row['external_uniqueid'];
					// if( (strpos(strtolower($d), 'single') != 'single' && !empty($d))){
					//     $bets = json_decode($row['betDetails'], true);
					//     $count = count($bets['sports_bet']);
					//     $h = ($count > 1) ? ($count * 33) + 155 : 188;
					//     $link = "'/echoinfo/bet_details/$unique_id','Bet Type','width=800,height=$h'";
					//     return sprintf('<a href="javascript:void(window.open('.$link.'))">%s</a>',$d);
				   // }else{
						return $d;
				  //  }
				},
			),
			array(
				'dt' => $i++,
				'alias' => 'match_type',
				'select' => 'game_logs.match_type',
				'name' => 'Match Type',
				'formatter' => function ($d){
					return ($d == '0' || empty($d)) ? 'N/A' : 'Live';
				}
			),
			array(
				'dt' => $i++,
				'alias' => 'match_details',
				'select' => 'game_logs.match_details',
				'name' => 'Match Details',
				'formatter' => 'defaultFormatter',
			),
			array(
				'dt' => $i++,
				'alias' => 'handicap',
				'select' => 'game_logs.handicap',
				'name' => 'Handicap',
				'formatter' => 'defaultFormatter',
			),
			array(
				'dt' => $i++,
				'alias' => 'odds',
				'select' => 'game_logs.odds',
				'name' => 'Odds',
				'formatter' => 'defaultFormatter',
			),
			array(
				'alias' => 'game_platform_id',
				'select' => 'game_logs.game_platform_id',
			)


			// array(
			//  'dt' => $i++,
			//  'alias' => 'external_uniqueid',
			//  'select' => 'game_logs.external_uniqueid',
			//  'formatter' => function($d, $row) {
			//      return '<ul class="list-inline">'.
			//      '<li><a href="javascript:void(0)" onclick="betDetail(\''.$d.'\')">'.lang('Bet Detail').'</a></li>'.
			//      '<li><a href="javascript:void(0)" onclick="betResult(\''.$d.'\')">'.lang('Bet Result').'</a></li>'.
			//      '</ul>';
			//  },
			// ),
		);
		# END DEFINE COLUMNS #######################################################################

		// $table = 'game_logs use index(idx_end_at)';
		$table = 'game_logs';

		$joins = array(
			'player' => 'player.playerId = game_logs.player_id',
			'agency_agents' => 'agency_agents.agent_id = player.agent_id',
			'game_description' => 'game_description.id = game_logs.game_description_id',
			'game_type' => 'game_type.id = game_description.game_type_id',
			'external_system' => 'game_logs.game_platform_id = external_system.id',
		);

		$left_joins_to_use_on_summary = array();


		# START PROCESS SEARCH FORM ################################################################
		$where = array();
		$values = array();
		$request = $this->input->post();
		$input = $this->data_tables->extra_search($request);
		// $this->utils->debug_log('AGENCY_GAME_HISTORY request111', $request);

		// -- Validate date range if config was set
		if($this->utils->getConfig('agency_game_logs_report_date_range_restriction'))
		{
			// -- If date range is empty || does not exist from the request, immediately return empty result.
			if(!isset($input['by_date_from']) || !isset($input['by_date_to']) ||  trim($input['by_date_from']) == '' || trim($input['by_date_to']) == '')
			{

				$result = $this->data_tables->empty_data($request);
				$result['header_data'] = $this->data_tables->get_columns($columns);
				$result['sub_summary'] = array(array("total_amount"=>"0.00","total_bet"=>"0.00","total_bet_result"=>"0.00","total_loss"=>"0.00","total_real_bet"=>"0.00","total_result"=>"0.00","total_win"=>"0.00"));
				$result['summary'] = array(array("total_amount"=>"0.00","total_bet"=>"0.00","total_bet_result"=>"0.00","total_loss"=>"0.00","total_real_bet"=>"0.00","total_result"=>"0.00","total_win"=>"0.00"));

				return $result;
			}


			// -- Check date range if within the provided restriction

			$date_diff = date_diff(date_create($input['by_date_to']), date_create($input['by_date_from']));

			$restriction = $this->utils->getConfig('agency_game_logs_report_date_range_restriction') - 1;

			if($date_diff->format('%a') >  $restriction){

				$result = $this->data_tables->empty_data($request);
				$result['header_data'] = $this->data_tables->get_columns($columns);
				$result['sub_summary'] = array(array("total_amount"=>"0.00","total_bet"=>"0.00","total_bet_result"=>"0.00","total_loss"=>"0.00","total_real_bet"=>"0.00","total_result"=>"0.00","total_win"=>"0.00"));
				$result['summary'] = array(array("total_amount"=>"0.00","total_bet"=>"0.00","total_bet_result"=>"0.00","total_loss"=>"0.00","total_real_bet"=>"0.00","total_result"=>"0.00","total_win"=>"0.00"));

				return $result;
			}
		}


		$where[] = "player.playerId IS NOT NULL";

		// if (isset($input['group_level']) && isset($input['only_allowed_game'])) {
		//  $joins['vipsetting_cashback_game'] = 'vipsetting_cashback_game.game_description_id = game_logs.game_description_id';
		// }

		if (isset($input['agent_id']) && $input['agent_id'] != '') {
			$downlines = $this->agency_model->get_all_downline_arr($input['agent_id']);
			$where[] = "player.agent_id IN (" . implode(',', $downlines) . ")";
			array_push($left_joins_to_use_on_summary, 'player');
		}
		if (isset($input['by_game_platform_id'])) {
			$where[] = "game_logs.game_platform_id = ?";
			$values[] = $input['by_game_platform_id'];
		}

		if (isset($input['by_game_flag'])) {
			$where[] = "game_logs.flag = ?";
			$values[] = $input['by_game_flag'];
		}

		if (isset($input['by_date_from'], $input['by_date_to'], $input['timezone']) ) {
			$default_timezone = $this->utils->getTimezoneOffset(new DateTime());
			$hours = $default_timezone - intval($input['timezone']);
			$date_from_str = $input['by_date_from'] . ' ' . $hours . ' hours';
			$date_to_str = $input['by_date_to'] . ' ' . $hours . ' hours';
			$where[] = "game_logs.end_at BETWEEN ? AND ?";
			$values[] = $date_from_str;
			$values[] = $date_to_str;
		}

		if (isset($player_id)) {
			$where[] = "player.playerId = ?";
			$values[] = $player_id;
			array_push($left_joins_to_use_on_summary, 'player');
		}

		if (isset($input['by_username'])) {
			$where[] = "player.username = ?";
			$values[] = $input['by_username'];
			array_push($left_joins_to_use_on_summary, 'player');
		}

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

		if (isset($input['by_group_level'])) {
			$where[] = "player.levelId  = ?";
			$values[] = $input['by_group_level'];
			array_push($left_joins_to_use_on_summary, 'player');
		}

		$all_game_types= isset($input['all_game_types']) ? ($input['all_game_types']=='true' || $input['all_game_types']=='on') : false ;
		if (isset($input['game_type_id']) && !$all_game_types) {

			if (is_array($input['game_type_id'])) {
				if (isset($input['game_type_id_null'])) {
					$where[] = "(game_type.id IN (" . implode(',', array_fill(0, count($input['game_type_id']), '?')) . ") OR game_type.id IS NULL)";
				} else {
					$where[] = "game_type.id IN (" . implode(',', array_fill(0, count($input['game_type_id']), '?')) . ")";
				}
				$values = array_merge($values, $input['game_type_id']);
			} else {
				if (isset($input['game_type_id_null'])) {
					$where[] = "(game_type.id = ? OR game_type.id IS NULL)";
				} else {
					$where[] = "game_type.id = ?";
				}
				$values[] = $input['game_type_id'];
			}

			array_push($left_joins_to_use_on_summary, 'game_type');

		} else if (isset($input['game_type_id_null'])) {
			$where[] = "game_type.id IS NULL";
			array_push($left_joins_to_use_on_summary, 'game_type');

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

		if (isset($input['by_round_number'])) {
			$where[] = "game_logs.table = ?";
			$values[] = $input['by_round_number'];
		}

		# END PROCESS SEARCH FORM ##################################################################
		$group_by = [];
		$having = [];
		$distinct = false;

		$result = $this->data_tables->get_data($request, $columns, $table, $where, $values, $joins, $group_by, $having, $distinct);
		// $this->utils->debug_log('AGENCY_GAME_HISTORY request', $request, $where, $values, $joins);
		// $this->utils->debug_log('AGENCY_GAME_HISTORY result', $result);

		// -- remove unecessary joins from total / summary queries
		foreach ($joins as $join_key => &$join_value) {
			if(!in_array($join_key, $left_joins_to_use_on_summary))
				unset($joins[$join_key]);
		}

		$summary = $this->data_tables->summary($request, $table, $joins,
			'SUM( case when flag='.Game_logs::FLAG_GAME.' && trans_amount>0 then trans_amount when flag='.Game_logs::FLAG_TRANSACTION.' then 0 else bet_amount end ) total_real_bet, SUM(bet_amount) total_bet, SUM(result_amount) total_result, SUM(bet_amount + result_amount) total_bet_result, SUM(win_amount) total_win, SUM(loss_amount) total_loss, SUM(trans_amount) total_amount',
			null, $columns, $where, $values);
		// $sub_summary = $this->data_tables->summary($request, $table, $joins,
		//     'external_system.system_code, SUM(bet_amount) total_bet, SUM(result_amount) total_result, SUM(win_amount) total_win, SUM(loss_amount) total_loss',
		//     'external_system.system_code', $columns, $where, $values);



		$result['summary'][0]['total_real_bet']         = $this->utils->formatCurrencyNoSym($summary[0]['total_real_bet']);
		$result['summary'][0]['total_bet']              = $this->utils->formatCurrencyNoSym($summary[0]['total_bet']);
		$result['summary'][0]['total_result']           = $this->utils->formatCurrencyNoSym($summary[0]['total_result']);
		$result['summary'][0]['total_bet_result']       = $this->utils->formatCurrencyNoSym($summary[0]['total_bet_result']);
		$result['summary'][0]['total_win']              = $this->utils->formatCurrencyNoSym($summary[0]['total_win']);
		$result['summary'][0]['total_loss']             = $this->utils->formatCurrencyNoSym($summary[0]['total_loss']);
		$result['summary'][0]['total_amount']           = $this->utils->formatCurrencyNoSym($summary[0]['total_amount']);

		$result['sub_summary'][0]['total_real_bet']     = $this->utils->formatCurrencyNoSym(array_sum(array_map(function($row) { return str_replace(',', '', $row[6]);}, $result['data'])));
		$result['sub_summary'][0]['total_bet']          = $this->utils->formatCurrencyNoSym(array_sum(array_map(function($row) { return str_replace(',', '', $row[7]);}, $result['data'])));
		$result['sub_summary'][0]['total_result']       = $this->utils->formatCurrencyNoSym(array_sum(array_map(function($row) { return str_replace(',', '', $row[8]);}, $result['data'])));
		$result['sub_summary'][0]['total_bet_result']   = $this->utils->formatCurrencyNoSym(array_sum(array_map(function($row) { return str_replace(',', '', $row[9]);}, $result['data'])));
		$result['sub_summary'][0]['total_win']          = $this->utils->formatCurrencyNoSym(array_sum(array_map(function($row) { return str_replace(',', '', $row[10]);}, $result['data'])));
		$result['sub_summary'][0]['total_loss']         = $this->utils->formatCurrencyNoSym(array_sum(array_map(function($row) { return str_replace(',', '', $row[11]);}, $result['data'])));
		$result['sub_summary'][0]['total_amount']       = $this->utils->formatCurrencyNoSym(array_sum(array_map(function($row) { return str_replace(',', '', $row[13]);}, $result['data'])));

		return $result;
	}

}

