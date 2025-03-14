<?php

/**
 * General behaviors include
 * * get the affiliate statistics
 * * get the game bet records
 * * get the statistics for a certain affiliate
 * * get affiliate earnings
 * * get the all affiliates
 * * get affiliate traffic statistics
 *
 * @category report_module_aff
 * @version 1.8.10
 * @copyright 2013-2022 tot
 */

trait report_module_aff {

	/**
	 * detail: get the affiliate statistics
	 *
	 * @param array $request
	 * @param Boolean $is_export
	 *
	 * @return array
	 */
	public function affiliateStatistics($request, $is_export) {
		$el = microtime(1);
		if($this->export_token){
		   $this->load->model(['queue_result']);
		}

		$readOnlyDB = $this->getReadOnlyDB();
		$requestasdsadsad = $request;

		$this->load->library('data_tables', array("DB" => $readOnlyDB));
		$this->load->model(array('player_model', 'transactions', 'affiliatemodel', 'total_player_game_hour', 'game_logs', 'player'));
		$this->load->helper(['aff_helper']);

		$this->data_tables->is_export = $is_export;
		$affiliatemodel = $this->affiliatemodel;

		$affiliate_username_column = 0;
		$tagname_column            = 1;
		$affiliate_level_column    = 2;
		$total_sub_column          = 3;
		$total_registered_player   = 4;
		$total_deposited_player    = 5;
		$total_bet_column          = 6;
		$total_win_column          = 7;
		$total_loss_column         = 8;
		$total_win_loss_column     = 9;
		$total_income_column       = 10;
		$total_cashback_column     = 11;
		$total_bonus_column        = 12;
		$total_deposit_column      = 13;
		$total_withdraw_column     = 14;
		$earnings_platform_fee     = 15;
		$earnings_bonus_fee        = 16;
		$earnings_cashback_fee     = 17;
		$earnings_transaction_fee  = 18;
		$earnings_admin_fee        = 19;
		$earnings_total_fee        = 20;

		$array = $request['extra_search'];
		$enable_date= null;
		$data_request = array();
			foreach($array as $value){
			$data_request[$value['name']] = $value;
		}
		if(array_key_exists('enable_date', $data_request)){
			$enable_date = 'yes';
		}
		$columns = array(
			array(
				'alias' => 'affiliateId',
				'select' => 'affiliates.affiliateId',
			),
			array(
				'dt' => $affiliate_username_column,
				'alias' => 'username',
				'select' => 'affiliates.username',
				'formatter' => function ($d, $row) use ($is_export) {
					if ($is_export) {
						return ($d ? $d : lang('N/A'));
					} else {
						$url = site_url('/affiliate_management/userInformation/' . $row['affiliateId']);
						// add data-affiliateid for this::extracTaffiliateidFromAnchorInHTML()
						return '<i class="fa fa-user" ></i> ' . ($d ? '<a href="' . $url . '" data-affiliateid="'. $row['affiliateId']. '" target="_blank">' . $d . '</a>' : '<i class="text-muted">' . lang('N/A') . '</i>');
					}
				},
				'name' => lang('Affiliate Username'),
			),
            array(
                'dt' => $tagname_column,
                'alias' => 'tagName',
                'select' => 'affiliates.affiliateId',
                'name' => lang("player.41"),
                'formatter' => function ($d, $row) use ($is_export) {
                    return aff_tagged_list($row['affiliateId'], $is_export);
                },
            ),
            array(
				'dt' => $affiliate_level_column,
				'alias' => 'affiliate_level',
				'select' => 'affiliates.levelNumber',
				'name' => lang('Affiliate Level'),
			),
			array(
				'dt' => $total_sub_column,
				'alias' => 'total_sub',
				'select' => 'affiliates.countSub',
				'name' => lang('Total Sub-affiliates'),
			),
			array(
				'dt' => $total_registered_player,
				'alias' => 'total_registered_player',
                'select' => 'affiliates.countPlayer',
				'formatter' => function ($d, $row) use ($request,$enable_date,$data_request) {
						if($enable_date == null){
							$players = $this->affiliatemodel->getAllPlayersUnderAffiliateId($row['affiliateId']);
						}
						else{
							$start_date = $data_request['by_date_from']['value'] . date(' H:i:s',mktime(00,00,00));
							$end_date = $data_request['by_date_to']['value'] . date(' H:i:s',mktime(23,59,59));
							$players = $this->affiliatemodel->getAllPlayersUnderAffiliateId($row['affiliateId'], $start_date, $end_date);
						}
						return count($players);
				},
				'name' => lang('Total Registered Players'),
			),
			array(
				'dt' => $total_deposited_player,
				'alias' => 'total_deposited_player',
                'select' => 'affiliates.totalPlayerDeposit',
				'formatter' => function ($d, $row) use ($request,$enable_date,$data_request) {
						if($enable_date == null){
							$players = $this->affiliatemodel->getAffiliateDepositedPLayer($row['affiliateId']);
							// $n_players = $this->affiliatemodel->getAffiliateDepositedPLayer($row['affiliateId'], null, null, 'return_count');
						}
						else{
							$start_date = $data_request['by_date_from']['value'] . date(' H:i:s',mktime(00,00,00));
							$end_date = $data_request['by_date_to']['value'] . date(' H:i:s',mktime(23,59,59));
							$players = $this->affiliatemodel->getAffiliateDepositedPLayer($row['affiliateId'], $start_date, $end_date);
							// $n_players = $this->affiliatemodel->getAffiliateDepositedPLayer($row['affiliateId'], $start_date, $end_date, 'return_count');
						}
						return count($players);
						// return $n_players;
				},
				'name' => lang('Total Deposited Players'),
			),
			array(
				'dt' => $total_bet_column,
				'alias' => 'total_bet',
				'select' => 'affiliates.affiliateId',
				'name' => lang('Total Bet'),
			),
			array(
				'dt' => $total_win_column,
				'alias' => 'total_win',
				'select' => 'affiliates.affiliateId',
				'name' => lang('Total Win'),
			),
			array(
				'dt' => $total_loss_column,
				'alias' => 'total_loss',
				'select' => 'affiliates.affiliateId',
				'name' => lang('Total Loss'),
			),
			array(
				'dt' => $total_win_loss_column,
				'alias' => 'total_win_loss',
				'select' => 'affiliates.affiliateId',
				'name' => lang('Company Win/Loss'),
			),
			array(
				'dt' => $total_income_column,
				'alias' => 'total_income',
				'select' => 'affiliates.affiliateId',
				'name' => lang('Company Income'),
			),
			array(
				'dt' => $total_cashback_column,
				'alias' => 'total_cashback',
				'select' => 'affiliates.affiliateId',
				'name' => lang('Total Cashback'),
			),
			array(
				'dt' => $total_bonus_column,
				'alias' => 'total_bonus',
				'select' => 'affiliates.affiliateId',
				'name' => lang('Total Bonus'),
			),
			array(
				'dt' => $total_deposit_column,
				'alias' => 'total_deposit',
				'select' => 'affiliates.affiliateId',
				'name' => lang('Total Deposit'),
			),
			array(
				'dt' => $total_withdraw_column,
				'alias' => 'total_withdraw',
				'select' => 'affiliates.affiliateId',
				'name' => lang('Total Withdraw'),
			),
			array(
				'dt' => $earnings_platform_fee,
				'alias' => 'earnings_platform_fee',
				'select' => 'sum(platform_fee)',
				'name' => lang('Platform Fee'),
				'formatter' => function($d, $row) {
					return number_format(floatval($d), 2);
				}
			),
			array(
				'dt' => $earnings_bonus_fee,
				'alias' => 'earnings_bonus_fee',
				'select' => 'sum(bonus_fee)',
				'name' => lang('Bonus Fee'),
				'formatter' => function($d, $row) {
					return number_format(floatval($d), 2);
				}
			),
			array(
				'dt' => $earnings_cashback_fee,
				'alias' => 'earnings_cashback_fee',
				'select' => 'sum(cashback_fee)',
				'name' => lang('Cashback Fee'),
				'formatter' => function($d, $row) {
					return number_format(floatval($d), 2);
				}
			),
			array(
				'dt' => $earnings_transaction_fee,
				'alias' => 'earnings_transaction_fee',
				'select' => 'sum(transaction_fee)',
				'name' => lang('Transaction Fee'),
				'formatter' => function($d, $row) {
					return number_format(floatval($d), 2);
				}
			),
			array(
				'dt' => $earnings_admin_fee,
				'alias' => 'earnings_admin_fee',
				'select' => 'sum(admin_fee)',
				'name' => lang('Admin Fee'),
				'formatter' => function($d, $row) {
					return number_format(floatval($d), 2);
				}
			),
			array(
				'dt' => $earnings_total_fee,
				'alias' => 'earnings_total_fee',
				'select' => 'sum(total_fee)',
				'name' => lang('Total Fee'),
				'formatter' => function($d, $row) {
					return number_format(floatval($d), 2);
				}
			),
		);
		# END DEFINE COLUMNS #################################################################################################################################################

		$table = 'affiliates';
		$group_by = 'affiliateId';

		# START PROCESS SEARCH FORM #################################################################################################################################################
		$where = array();
		$values = array();
		$input = $this->data_tables->extra_search($request);

		$use_total_hour = $this->utils->getConfig('use_total_hour');
		$start_date = null;
		$end_date = null;
		$yearmonth_from = null;
		$yearmonth_to = null;

		$grandtotal_clauses = [];

		if ($this->safeGetParam($input, 'enable_date') == 'true') {

			if (isset($input['by_date_from'])) {
				$start_date = $input['by_date_from'] . date(' H:i:s',mktime(00,00,00));
				$yearmonth_from = date('Ym', strtotime($input['by_date_from']));
			}

			if (isset($input['by_date_to'])) {
				$end_date = $input['by_date_to'] . date(' H:i:s',mktime(23,59,59));
				$yearmonth_to = date('Ym', strtotime($input['by_date_to']));
			}
		}

		// OGP-22856: add columns from aff earnings
		$aff_monthly_earnings_join_cond = 'affiliates.affiliateId = E.affiliate_id';
		if (!empty($yearmonth_from)) {
			$aff_monthly_earnings_join_cond .= " AND E.year_month >= '{$yearmonth_from}' ";
		}
		if (!empty($yearmonth_to)) {
			$aff_monthly_earnings_join_cond .= " AND E.year_month <= '{$yearmonth_to}' ";
		}
		// join aff_monthly_earnings
		$joins = [ 'aff_monthly_earnings AS E' => $aff_monthly_earnings_join_cond ];

		if (empty($start_date)) {
			$start_date = '2000-01-01 00:00:00';
		}

		if (empty($end_date)) {
			$end_date = $this->utils->getNowForMysql();
		}

		$affiliate_id = null;

		if (isset($input['by_affiliate_username'])) {
			$affiliate_id = $this->affiliatemodel->getAffiliateIdByUsername($input['by_affiliate_username']);
			if($input['by_affiliate_username']!=""){
				$where[] = "affiliates.affiliateId = ?";
				$values[] = $affiliate_id;
				// $grandtotal_clauses[] = "affiliateId = {$affiliate_id}";
			}
		}

		if ($affiliate_id) {
			$where[] = "affiliates.affiliateId = ?";
			$values[] = $affiliate_id;
		}

		if (isset($input['tag_id'])) {
            if (is_array($input['tag_id'])) {
                $where[] = 'affiliates.affiliateId IN ( SELECT affiliateId FROM affiliatetag WHERE tagId IN ('.implode(',', $input['tag_id']).') )';
                // $tag_ids = implode(',', $input['tag_id']);
                // $grandtotal_clauses[] = "affiliateId IN ( SELECT affiliateId FROM affiliatetag WHERE tagId IN ({$tag_ids}) )";
            } else {
                $where[] = 'affiliates.affiliateId IN ( SELECT affiliateId FROM affiliatetag WHERE tagId = ? )';
                $values[] = $input['tag_id'];
                // $grandtotal_clauses[] = "affiliateId IN ( SELECT affiliateId FROM affiliatetag WHERE tagId = {$input['tag_id']} )";
            }
		}

		//status
		if (isset($input['by_status'])) {
			$by_status = $input['by_status'];
			if ($by_status == '-1') {
				$where[] = 'affiliates.countPlayer >?';
				$values[] = 0;
				// $grandtotal_clauses[] = 'countPlayer > 0';
			} else {
				$where[] = 'affiliates.status = ?';
				$values[] = $by_status;
				// $grandtotal_clauses[] = "status = {$by_status}";
			}
		} else {
			$where[] = 'affiliates.status != ? ';
			$values[] = Affiliatemodel::STATUS_DELETED;
			// $grandtotal_clauses[] = "status != " . Affiliatemodel::STATUS_DELETED;
		}

		//==where condition=================================


		# END PROCESS SEARCH FORM #################################################################################################################################################
		$result = $this->data_tables->get_data($request, $columns, $table, $where, $values, $joins, $group_by);
		if($this->export_token){
			$rlt=['success'=>false, 'is_export'=>true, 'processMsg'=> lang('Calculating Totals').'...',  'written' => 0, 'total_count' => 0, 'progress' => 0];
			$this->queue_result->updateResultRunning($this->export_token, $rlt, array('processId'=>$this->export_pid));
		}
		// $summary = $this->data_tables->summary($request, $table, $joins, 'SUM(affiliates.countSub) total_sub, SUM(E.platform_fee) platform_fee, SUM(E.bonus_fee) bonus_fee, SUM(E.cashback_fee) cashback_fee, SUM(E.transaction_fee) transaction_fee, SUM(E.admin_fee) admin_fee, SUM(E.total_fee) total_fee', null, $columns, $where, $values);
		$summary = $this->data_tables->summary($request, $table, $joins, 'SUM(affiliates.countSub) total_sub', null, $columns, $where, $values);

		$result['summary']['total_sub_affiliates'] = $summary[0]['total_sub'];

		$sum_earnings = $this->affiliatemodel->sumMonthlyEarningsByYearMonth($yearmonth_from, $yearmonth_to);

		$result['summary']['platform_fee'] 		= $sum_earnings['platform_fee'];
		$result['summary']['bonus_fee'] 		= $sum_earnings['bonus_fee'];
		$result['summary']['cashback_fee'] 		= $sum_earnings['cashback_fee'];
		$result['summary']['transaction_fee'] 	= $sum_earnings['transaction_fee'];
		$result['summary']['admin_fee'] 		= $sum_earnings['admin_fee'];
		$result['summary']['total_fee'] 		= $sum_earnings['total_fee'];

		$show_game_platform = isset($input['show_game_platform']) && $input['show_game_platform'];

		if (isset($result['data']) && !empty($result['data'])) {
			foreach ($result['data'] as &$row) {
				$affId = $row[$total_bet_column];
				# GET LIST OF PLAYERS UNDER AFFILIATE
				$players = $this->affiliatemodel->getAllPlayersUnderAffiliateId($affId, $start_date, $end_date);

				$add_manual=false;
				//from transactions
				list($totalDeposit, $totalWithdrawal, $totalBonus) =
				$this->transactions->getTotalDepositWithdrawalBonusCashbackByPlayers($players, $start_date, $end_date, $add_manual);

				$totalCashback = $this->player_model->getPlayersTotalCashback($players, $start_date, $end_date, $input['by_total_cashback_date_type']);

				list($totalBets, $totalWins, $totalLoss) = $this->game_logs->getTotalBetsWinsLossByPlayers($players, $start_date, $end_date);

				if ($show_game_platform) {

					$gameInfo = $this->game_logs->getTotalBetsWinsLossGroupByGamePlatformByPlayers($players, $start_date, $end_date);

					list($row[$total_bet_column], $row[$total_win_column], $row[$total_loss_column]) = $this->printGameBetInfo($gameInfo, $is_export);
				} else {
					$row[$total_bet_column] = $this->utils->formatCurrencyNoSym($totalBets);
					$row[$total_win_column] = $this->utils->formatCurrencyNoSym($totalWins);
					$row[$total_loss_column] = $this->utils->formatCurrencyNoSym($totalLoss);
				}

				$row[$total_cashback_column] = $this->utils->formatCurrencyNoSym($totalCashback);
				$row[$total_bonus_column] = $this->utils->formatCurrencyNoSym($totalBonus);
				$row[$total_deposit_column] = $this->utils->formatCurrencyNoSym($totalDeposit);
				$row[$total_withdraw_column] = $this->utils->formatCurrencyNoSym($totalWithdrawal);

				# OGP-2628
				$win_loss = $totalLoss - $totalWins;
				$income = $win_loss - $totalBonus - $totalCashback;
				if($is_export){
					$row[$total_win_loss_column] = $this->utils->formatCurrencyNoSym($win_loss);
					$row[$total_income_column] = $this->utils->formatCurrencyNoSym($income);
				} else {
					$row[$total_win_loss_column] = '<span class="' . ($win_loss == 0 ? '' : ($win_loss > 0 ? 'text-success' : 'text-danger')) . '">' . $this->utils->formatCurrencyNoSym($win_loss) . '</span>';

					$row[$total_income_column] = '<span class="' . ($income == 0 ? '' : ($income > 0 ? 'text-success' : 'text-danger')) . '">' . $this->utils->formatCurrencyNoSym($income) . '</span>';
				}

			}
		}


		$players = $this->affiliatemodel->getAllPlayersUnderAffiliateId(NULL, $start_date, $end_date);

		$result['summary']['total_registered_player'] = count($players);

		$add_manual = false;
		list($totalDeposit, $totalWithdrawal, $totalBonus, $totalCashback) = $this->transactions->getTotalDepositWithdrawalBonusCashbackByPlayers($players, $start_date, $end_date, $add_manual);

		$result['summary']['total_cashback'] = number_format($totalCashback, 2);
		$result['summary']['total_bonus'] = number_format($totalBonus, 2);
		$result['summary']['total_deposit'] = number_format($totalDeposit, 2);
		$result['summary']['total_withdraw'] = number_format($totalWithdrawal, 2);

		list($totalBets, $totalWins, $totalLoss) = $this->game_logs->getTotalBetsWinsLossByPlayers($players, $start_date, $end_date);

		if ($show_game_platform) {

			$gameInfo = $this->game_logs->getTotalBetsWinsLossGroupByGamePlatformByPlayers($players, $start_date, $end_date);

			list($result['summary']['total_bet'], $result['summary']['total_win'], $result['summary']['total_loss']) = $this->printGameBetInfo($gameInfo, $is_export);
		} else {
			$result['summary']['total_bet'] = $this->utils->formatCurrencyNoSym($totalBets);
			$result['summary']['total_win'] = $this->utils->formatCurrencyNoSym($totalWins);
			$result['summary']['total_loss'] = $this->utils->formatCurrencyNoSym($totalLoss);
		}

		$win_loss = $totalLoss - $totalWins;
		$result['summary']['total_win_loss'] = '<span class="' . ($win_loss == 0 ? '' : ($win_loss > 0 ? 'text-success' : 'text-danger')) . '">' . $this->utils->formatCurrencyNoSym($win_loss) . '</span>';

		$income = $win_loss - $totalBonus - $totalCashback;
		$result['summary']['total_income'] = '<span class="' . ($income == 0 ? '' : ($income > 0 ? 'text-success' : 'text-danger')) . '">' . $this->utils->formatCurrencyNoSym($income) . '</span>';

		// $players = $this->affiliatemodel->getAffiliateDepositedPLayer(NULL, $start_date, $end_date);
		// $result['summary']['total_deposited_player'] = count($players);

		$result['summary']['total_deposited_player'] = $this->affiliatemodel->countAllAffDepositedPlayer($start_date, $end_date);



		$el = microtime(1) - $el;
		$this->utils->debug_log(__METHOD__, 'time consumption', sprintf('%.2f', $el));

		return $result;
	}

    public function formatter_aff_username($affiliateId, $aff_username, $_is_hide, $is_export, $prefix_string = '<i class="fa fa-user" ></i>'){
        $url = site_url('/affiliate_management/userInformation/' . $affiliateId);
        $_is_username_empty = false;
        if( empty($aff_username) ){
            $_is_username_empty = true;
        }

        $return_string = '';
        $caseStr = (int)$is_export. '_'. (int)$_is_username_empty. '_'. (int)$_is_hide;
        switch($caseStr){
            /// display in list
            case '0_0_0':
                $return_string .= $prefix_string;
                $return_string .=  '<a href="' . $url . '" data-affiliateid="'. $affiliateId. '" target="_blank">' . $aff_username . '</a>';
                break;
            case '0_0_1':
                /// aff. is hide
                // data-affiliateid for self::extractAffiliateidFromAnchorInHTML()
                $return_string .= $prefix_string;
                $return_string .= '<span data-affiliateid="'. $affiliateId. '">';
                $return_string .= $aff_username;
                $return_string .= '</span>';
                $return_string .= ' ';
                $return_string .= '('. lang('Hidden'). ')';
                break;
            case '0_1_0':
            case '0_1_1':
                /// username is empty
                $return_string .= $prefix_string;
                $return_string .= '<i class="text-muted">'. lang('N/A'). '</i>';
                break;

            /// export csv
            case '1_0_0':
                $return_string .= $aff_username;
                break;
            case '1_0_1':
                /// aff. is hide
                $return_string .= $aff_username;
                $return_string .= ' ';
                $return_string .= '('. lang('Hidden'). ')';
                break;

            case '1_1_0':
            case '1_1_1':
                /// username is empty
                $return_string .= lang('N/A');
                break;

            default:
                break;
        }
        return $return_string;
    }

	/**
	 * detail: get the affiliate statistics
	 *
	 * @param array $request
	 * @param Boolean $is_export
	 *
	 * @return array
	 */
	public function affiliateStatistics2($request, $is_export) {
		$readOnlyDB = $this->getReadOnlyDB();

		$this->load->library('data_tables', array("DB" => $readOnlyDB));
		$this->load->model(array('player_model', 'transactions', 'affiliatemodel', 'total_player_game_hour', 'game_logs', 'player', 'affiliate_earnings'));
		$this->load->helper(['aff_helper']);
		$this->data_tables->is_export = $is_export;
		$affiliate_earnings = $this->affiliate_earnings;
        $_affiliatemodel = $this->affiliatemodel;
        $_this = $this;
        $input = $this->data_tables->extra_search($request);


		// $affiliate_username_column = 0;
		// $tagname_column            = 1;
		// $affiliate_level_column    = 2;
		// $total_sub_column          = 3;
		// $total_registered_player   = 4;
		// $total_deposited_player    = 5;
		// $total_bet_column          = 6;
		// $total_win_column          = 7;
		// $total_loss_column         = 8;
		// $total_win_loss_column     = 9;
		// $total_income_column       = 10;
		// $total_cashback_column     = 11;
		// $total_bonus_column        = 12;
		// $total_deposit_column      = 13;
		// $total_withdraw_column     = 14;

		$col_aff_username		= 0;
		$col_tagname			= 1;
        $col_parent_aff         = 2;
		$col_aff_level			= 3;
		$col_total_sub			= 4;
		$col_total_reg_players	= 5;
		$col_total_dep_players	= 6;
        $total_deposited_player_specified_period = 7;
		$col_total_bets			= 8;
		$col_total_win			= 9;
		$col_total_loss			= 10;
		$col_total_win_loss		= 11;
		$col_total_income		= 12;
		$col_total_cashback		= 13;
		$col_total_bonus		= 14;
		$col_total_deposit		= 15;
		$col_total_withdraw		= 16;
		// $col_aff_id				= 16;
		$earnings_platform_fee     = 17;
		$earnings_bonus_fee        = 18;
		$earnings_cashback_fee     = 19;
		$earnings_transaction_fee  = 20;
		$earnings_admin_fee        = 21;
		$earnings_total_fee        = 22;
		$col_cashback_revenue   = 23;
		$col_aff_id				= 24;



		$array = $request['extra_search'];
		$enable_date= null;
		$data_request = array();
        foreach($array as $value){
			$data_request[$value['name']] = $value;
		}
        $i = 0;
		$columns = array(
			// array(
			// 	'alias' => 'affiliateId',
			// 	'select' => 'affiliate_id',
			// ),
			array(
				// 'dt' => $i++, // 0
				'dt' => $col_aff_username ,
				'alias' => 'username',
				'select' => 'aff_username',
				'formatter' => function ($d, $row) use ($is_export, $_this) {
                    $aff_username = $d;
                    $affiliateId =  $row['affiliateId'];
                    $_is_hide = false;
                    if( $row['is_hide'] == Affiliatemodel::DB_TRUE){
                        $_is_hide = true;
                    }
                    return $_this->formatter_aff_username($affiliateId, $aff_username, $_is_hide, $is_export);
				},
				'name' => lang('Affiliate Username'),
			),
            array(
                // 'dt' => $i++, // 1
                'dt' => $col_tagname ,
                'alias' => 'tagName',
                'select' => 'affiliates.affiliateId',
                'name' => lang("player.41"),
                'formatter' => function ($d, $row) use ($is_export) {
                    return aff_tagged_list($row['affiliateId'], $is_export);
                },
            ),
            array(
                'alias' => 'is_hide',
                'select' => 'affiliates.is_hide'
            ),
            array(
                'alias' => 'parent_aff_id',
                'select' => 'parent.affiliateId'
            ),
            array(
                'alias' => 'is_parent_hide',
                'select' => 'parent.is_hide'
            ),
            array(
                'dt' => $col_parent_aff ,
                'alias' => 'parent_aff',
                'select' => 'parent.username',
                'name' => lang('Parent Affiliate'),
                'formatter' => function ($d, $row) use ($is_export, $_this) {
                    $aff_username = $d;
                    $affiliateId =  $row['parent_aff_id'];
                    $_is_hide = false;
                    if( $row['is_parent_hide'] == Affiliatemodel::DB_TRUE){
                        $_is_hide = true;
                    }
                    return $_this->formatter_aff_username($affiliateId, $aff_username, $_is_hide, $is_export);
                },
            ),
			array(
				// 'dt' => $i++, // 2
				'dt' => $col_aff_level ,
				'alias' => 'affiliate_level',
				'select' => 'affiliate_level',
				'name' => lang('Affiliate Level'),
			),
			array(
				// 'dt' => $i++, // 3
				'dt' => $col_total_sub ,
				'alias' => 'total_sub',
				'select' => 'SUM(total_sub_affiliates)',
				'name' => lang('Total Sub-affiliates'),
			),
			array(
				// 'dt' => $i++, // 4
				'dt' => $col_total_reg_players ,
				'alias' => 'total_registered_player',
                'select' => 'SUM(total_registered_players)',
				'name' => lang('Total Registered Players'),
			),
			array(
				// 'dt' => $i++, // 5
				'dt' => $col_total_dep_players ,
				'alias' => 'total_deposited_player',
                'select' => 'SUM(total_deposited_players)',
				'name' => lang('Total Deposited Players'),
			),
            array(
				// 'dt' => $i++, // 5
				'dt' => $total_deposited_player_specified_period ,
				'alias' => 'total_deposited_player_specified_period',
                'select' => 'affiliates.affiliateId',
				'name' => lang('Total deposited players in Date Range'),
                'formatter' => function($d, $row) use ($is_export, $input) {
                    $affiliateId = $d;
                    $start_end_infos = $this->get_start_end_infos_from_input($input);
                    $start_date = $start_end_infos['start_date'];
                    $end_date = $start_end_infos['end_date'];
                    if ($is_export) {

                        $by_status = 'NULL';
                        $parentAffUsername = 'NULL';
                        $affTags = 0;
                        $by_affiliate_username = $row['username'];

                        $start_date .= ' 00:00:00';
                        $end_date .= ' 23:59:59';

                        $isCached = false; // For collect the result is Cached or Not, default should be false to apply.
                        $cacheOnly=false; // the ttl, please reference to sync_latest_game_records_cache_ttl in config
                        $forceRefresh = false;
                        $ttl = 3*60;
                        $rows = $this->sale_order->getDistinctDepositedPLayerWithAffiliate( $start_date
                                                                                        , $end_date
                                                                                        , $by_status
                                                                                        , $parentAffUsername
                                                                                        , $affTags
                                                                                        , $by_affiliate_username
                                                                                        , $isCached // #7
                                                                                        , $forceRefresh // #8
                                                                                        , $cacheOnly // #9
                                                                                        , $ttl // #10
                                                                                    );
                        $total_deposited_players = array_sum(array_column($rows, 'player_count'));
                        return $total_deposited_players;
                    }
                    else {
                        /// tdpsp = Total Deposited Players Specified Period
                        // params: $affiliateId, $start_date and $end_date
                        $html_formater = '<span class="tdpsp" data-affiliate_id="%s" data-start_date="%s" data-end_date="%s" >%s</span>';
                        return sprintf($html_formater, $affiliateId, $start_date, $end_date , lang('Waiting'));
                    }
                }
			),
			array(
				// 'dt' => $i++, // 6
				'dt' => $col_total_bets ,
				'alias' => 'total_bet',
				'select' => 'SUM(total_bet)',
				'name' => lang('Total Bet'),
                'formatter' => function($d){
                    return $this->utils->formatCurrencyNoSym($d);
                }
			),
			array(
				// 'dt' => $i++, // 7
				'dt' => $col_total_win ,
				'alias' => 'total_win',
				'select' => 'SUM(total_win)',
				'name' => lang('Total Win'),
                'formatter' => function($d){
                    return $this->utils->formatCurrencyNoSym($d);
                }
			),
			array(
				// 'dt' => $i++, // 8
				'dt' => $col_total_loss ,
				'alias' => 'total_loss',
				'select' => 'SUM(total_loss)',
				'name' => lang('Total Loss'),
                'formatter' => function($d){
                    return $this->utils->formatCurrencyNoSym($d);
                }
			),
			array(
				// 'dt' => $i++, // 9
				'dt' => $col_total_win_loss ,
				'alias' => 'total_win_loss',
				'select' => 'SUM(company_win_loss)',
				'name' => lang('Company Win/Loss'),
                'formatter' => function($d) use ($is_export) {
                    if ($is_export) {
                        return $this->utils->formatCurrencyNoSym($d);
                    }
                    else {
                        return '<span class="' . ($d == 0 ? '' : ($d > 0 ? 'text-success' : 'text-danger')) . '">' . $this->utils->formatCurrencyNoSym($d) . '</span>';
                    }
                }
			),
			array(
				// 'dt' => $i++, // 10
				'dt' => $col_total_income ,
				'alias' => 'total_income',
				'select' => 'SUM(company_income)',
				'name' => lang('Company Income'),
                'formatter' => function($d) use ($is_export) {
                    if ($is_export) {
                        return $this->utils->formatCurrencyNoSym($d);
                    }
                    else {
                        return '<span class="' . ($d == 0 ? '' : ($d > 0 ? 'text-success' : 'text-danger')) . '">' . $this->utils->formatCurrencyNoSym($d) . '</span>';
                    }
                }
			),
			array(
				// 'dt' => $i++, // 11
				'dt' => $col_total_cashback ,
				'alias' => 'total_cashback',
				'select' => 'SUM(affiliate_static_report.total_cashback)',
				'name' => lang('Total Cashback'),
                'formatter' => function($d){
                    return $this->utils->formatCurrencyNoSym($d);
                }
			),
			array(
				// 'dt' => $i++, // 12
				'dt' => $col_total_bonus ,
				'alias' => 'total_bonus',
				'select' => 'SUM(total_bonus)',
				'name' => lang('Total Bonus'),
                'formatter' => function($d){
                    return $this->utils->formatCurrencyNoSym($d);
                }
			),
			array(
				// 'dt' => $i++, // 13
				'dt' => $col_total_deposit ,
				'alias' => 'total_deposit',
				'select' => 'SUM(total_deposit)',
				'name' => lang('Total Deposit'),
                'formatter' => function($d){
                    return $this->utils->formatCurrencyNoSym($d);
                }
			),
			array(
				// 'dt' => $i++, // 14
				'dt' => $col_total_withdraw ,
				'alias' => 'total_withdraw',
				'select' => 'SUM(total_withdraw)',
				'name' => lang('Total Withdraw'),
                'formatter' => function($d){
                    return $this->utils->formatCurrencyNoSym($d);
                }
			),
			array(
				'dt' => $earnings_platform_fee, // 15
				'alias' => 'earnings_platform_fee',
				'select' => 'affiliates.affiliateId',
				'name' => lang('Platform Fee'),
				'formatter' => function($d, $row) use ($affiliate_earnings) { // aff_monthly_earnings
					$affiliateId = $d;
					$yearmonth_range = [];
					$yearmonth_range['from'] = $row['yearmonth_from'];
					$yearmonth_range['to'] = $row['yearmonth_to'];
					$select = 'sum(platform_fee) AS amount';
					$row = $affiliate_earnings->getAffiliateMonthlyCommissionByAffidBetweenYearmonth($affiliateId, $yearmonth_range, $select);
					$platform_fee = $row['amount'];
					return number_format(floatval($platform_fee), 2);
				}
			),
			array(
				'dt' => $earnings_bonus_fee, // 16
				'alias' => 'earnings_bonus_fee',
				'select' => 'affiliates.affiliateId',
				'name' => lang('Bonus Fee'),
				'formatter' => function($d, $row) use ($affiliate_earnings) { // aff_monthly_earnings
					$affiliateId = $d;
					$yearmonth_range = [];
					$yearmonth_range['from'] = $row['yearmonth_from'];
					$yearmonth_range['to'] = $row['yearmonth_to'];
					$select = 'sum(bonus_fee) AS amount';
					$row = $affiliate_earnings->getAffiliateMonthlyCommissionByAffidBetweenYearmonth($affiliateId, $yearmonth_range, $select);
					$bonus_fee = $row['amount'];
					return number_format(floatval($bonus_fee), 2);
				}
			),
			array(
				'dt' => $earnings_cashback_fee, // 17
				'alias' => 'earnings_cashback_fee',
				'select' => 'affiliates.affiliateId',
				'name' => lang('Cashback Fee'),
				'formatter' => function($d, $row) use ($affiliate_earnings) { // aff_monthly_earnings
					$affiliateId = $d;
					$yearmonth_range = [];
					$yearmonth_range['from'] = $row['yearmonth_from'];
					$yearmonth_range['to'] = $row['yearmonth_to'];
					$select = 'sum(cashback_fee) AS amount';
					$row = $affiliate_earnings->getAffiliateMonthlyCommissionByAffidBetweenYearmonth($affiliateId, $yearmonth_range, $select);
					$cashback_fee = $row['amount'];
					return number_format(floatval($cashback_fee), 2);
				}
			),
			array(
				'dt' => $earnings_transaction_fee, // 18
				'alias' => 'earnings_transaction_fee',
				'select' => 'affiliates.affiliateId',
				'name' => lang('Transaction Fee'),
				'formatter' => function($d, $row) use ($affiliate_earnings) { // aff_monthly_earnings
					$affiliateId = $d;
					$yearmonth_range = [];
					$yearmonth_range['from'] = $row['yearmonth_from'];
					$yearmonth_range['to'] = $row['yearmonth_to'];
					$select = 'sum(transaction_fee) AS amount';
					$row = $affiliate_earnings->getAffiliateMonthlyCommissionByAffidBetweenYearmonth($affiliateId, $yearmonth_range, $select);
					$transaction_fee = $row['amount'];
					return number_format(floatval($transaction_fee), 2);
				}
			),
			array(
				'dt' => $earnings_admin_fee, // 19
				'alias' => 'earnings_admin_fee',
				'select' => 'affiliates.affiliateId',
				'name' => lang('Admin Fee'),
				'formatter' => function($d, $row) use ($affiliate_earnings) { // aff_monthly_earnings
					$affiliateId = $d;
					$yearmonth_range = [];
					$yearmonth_range['from'] = $row['yearmonth_from'];
					$yearmonth_range['to'] = $row['yearmonth_to'];
					$select = 'sum(admin_fee) AS amount';
					$row = $affiliate_earnings->getAffiliateMonthlyCommissionByAffidBetweenYearmonth($affiliateId, $yearmonth_range, $select);
					$admin_fee = $row['amount'];
					return number_format(floatval($admin_fee), 2);
				}
			),
			array(
				'dt' => $earnings_total_fee, // 20
				'alias' => 'earnings_total_fee',
				'select' => 'affiliates.affiliateId',
				'name' => lang('Total Fee'),
				'formatter' => function($d, $row) use ($affiliate_earnings) { // aff_monthly_earnings
					$affiliateId = $d;
					$yearmonth_range = [];
					$yearmonth_range['from'] = $row['yearmonth_from'];
					$yearmonth_range['to'] = $row['yearmonth_to'];
					$select = 'sum(total_fee) AS amount';
					$row = $affiliate_earnings->getAffiliateMonthlyCommissionByAffidBetweenYearmonth($affiliateId, $yearmonth_range, $select);
					$total_fee = $row['amount'];
					return number_format(floatval($total_fee), 2);
				}
			),
			array(
				'dt' => $col_cashback_revenue ,
				'alias' => 'cashback_revenue',
				'select' => 'SUM(affiliate_static_report.cashback_revenue)',
				'name' => lang('Cashback Revenue'),
                'formatter' => function($d){
                    return $this->utils->formatCurrencyNoSym($d);
                }
			),
			array(
				// 'dt' => $col_aff_id , // 21 # commented to remove it from export
				'alias' => 'affiliateId',
				'select' => 'affiliate_static_report.affiliate_id',
			),
			array(
				'alias' => 'report_date',
				'select' => 'report_date'
			),
			// yearmonth_from and yearmonth_to will be assigned later
		);
		# END DEFINE COLUMNS #################################################################################################################################################
		$table = 'affiliate_static_report';
		$joins = array(
			 'affiliates' => "affiliates.affiliateId = affiliate_static_report.affiliate_id",
			 'affiliates as parent' => "parent.affiliateId = affiliates.parentId",
		);
        $group_by=['affiliateId'];
		# START PROCESS SEARCH FORM #################################################################################################################################################
		$where = array();
		$values = array();

		$input = $this->data_tables->extra_search($request);

        $start_end_infos = $this->get_start_end_infos_from_input($input);
        $start_date = $start_end_infos['start_date'];
        $yearmonth_from = $start_end_infos['yearmonth_from'];
        $end_date = $start_end_infos['end_date'];
		$yearmonth_to = $start_end_infos['yearmonth_to'];

        if (isset($input['enable_date']) && $input['enable_date'] == 'true') {
            $where[] = 'report_date >= ?';
            $values[] = $start_date;

            $where[] = 'report_date <= ?';
            $values[] = $end_date;
        }

		// assign yearmonth_from and yearmonth_to for referenced in aff_monthly_earnings
		array_push($columns, [
			'alias' => 'yearmonth_from',
			'select' => sprintf('"%s"', $yearmonth_from)
		]);
		array_push($columns, [
			'alias' => 'yearmonth_to',
			'select' => sprintf('"%s"', $yearmonth_to)
		]);

		if (isset($input['by_affiliate_username'])) {
			if($input['by_affiliate_username']!=""){
				$where[] = "aff_username = ?";
				$values[] = $input['by_affiliate_username'];
			}
		}
        if (isset($input['parent_affiliate_username'])) {
            if(!empty($input['parent_affiliate_username'])){
                $where[] = "parent.username = ?";
                $values[] = $input['parent_affiliate_username'];
            }
        }
		if (isset($input['tag_id'])) {
            if (is_array($input['tag_id'])) {
                $where[] = 'affiliates.affiliateId IN ( SELECT affiliateId FROM affiliatetag WHERE tagId IN ('.implode(',', $input['tag_id']).') )';
            } else {
                $where[] = 'affiliates.affiliateId IN ( SELECT affiliateId FROM affiliatetag WHERE tagId = ? )';
                $values[] = $input['tag_id'];
            }
		}

		//status
		if (isset($input['by_status'])) {
			$by_status = $input['by_status'];
			if ($by_status == '-1') {
				$where[] = 'affiliates.countPlayer >?';
				$values[] = 0;
			} else {
				$where[] = 'affiliates.status = ?';
				$values[] = $by_status;
			}
		} else {
			$where[] = 'affiliates.status != ? ';
			$values[] = Affiliatemodel::STATUS_DELETED;
		}

		//==where condition=================================

		# END PROCESS SEARCH FORM #################################################################################################################################################

        $result = $this->data_tables->get_data($request, $columns, $table, $where, $values, $joins, $group_by);
		// $last_query = $this->data_tables->last_query;
        // $this->utils->debug_log('OGP-27747.939.last_query:', $last_query);


		$sum_earnings = $this->affiliatemodel->sumMonthlyEarningsByYearMonth($yearmonth_from, $yearmonth_to);
		$result['summary']['platform_fee'] 		= $sum_earnings['platform_fee'];
		$result['summary']['bonus_fee'] 		= $sum_earnings['bonus_fee'];
		$result['summary']['cashback_fee'] 		= $sum_earnings['cashback_fee'];
		$result['summary']['transaction_fee'] 	= $sum_earnings['transaction_fee'];
		$result['summary']['admin_fee'] 		= $sum_earnings['admin_fee'];
		$result['summary']['total_fee'] 		= $sum_earnings['total_fee'];

        $summary = $this->data_tables->summary($request, $table, $joins, 'SUM(total_sub_affiliates) total_sub_affiliates, SUM(total_registered_players) total_registered_players, SUM(total_deposited_players) total_deposited_players, SUM(total_bet) total_bet,
        SUM(total_win) total_win, SUM(total_loss) total_loss, SUM(company_win_loss) company_win_loss, SUM(company_income) company_income, SUM(affiliate_static_report.total_cashback) total_cashback, SUM(affiliate_static_report.cashback_revenue) cashback_revenue, SUM(total_bonus) total_bonus, SUM(total_deposit) total_deposit, SUM(total_withdraw) total_withdraw', null, $columns, $where, $values);

        $result['summary']['total_sub_affiliates']      = $this->utils->formatCurrencyNoSym($summary[0]['total_sub_affiliates']);
        $result['summary']['total_registered_players']  = $this->utils->formatCurrencyNoSym($summary[0]['total_registered_players']);
        $result['summary']['total_deposited_players']   = $this->utils->formatCurrencyNoSym($summary[0]['total_deposited_players']);
        $result['summary']['total_deposited_player_specified_period']   = $this->utils->formatCurrencyNoSym(0); // total_deposited_player_specified_period, subtotal in .datatable() of JS.
        $result['summary']['total_bet']                 = $this->utils->formatCurrencyNoSym($summary[0]['total_bet']);
        $result['summary']['total_win']                 = $this->utils->formatCurrencyNoSym($summary[0]['total_win']);
        $result['summary']['total_loss']                = $this->utils->formatCurrencyNoSym($summary[0]['total_loss']);
        $result['summary']['company_win_loss']          = $this->utils->formatCurrencyNoSym($summary[0]['company_win_loss']);
        $result['summary']['company_income']            = $this->utils->formatCurrencyNoSym($summary[0]['company_income']);
        $result['summary']['total_cashback']            = $this->utils->formatCurrencyNoSym($summary[0]['total_cashback']);
        $result['summary']['total_bonus']               = $this->utils->formatCurrencyNoSym($summary[0]['total_bonus']);
        $result['summary']['total_deposit']             = $this->utils->formatCurrencyNoSym($summary[0]['total_deposit']);
        $result['summary']['total_withdraw']            = $this->utils->formatCurrencyNoSym($summary[0]['total_withdraw']);
		$result['summary']['cashback_revenue']            = $this->utils->formatCurrencyNoSym($summary[0]['cashback_revenue']);

        // begin code from affiliateStatistics
		$show_game_platform = isset($input['show_game_platform']) && $input['show_game_platform'];
		$start_date = date('Y-m-d', strtotime($start_date)).' 00:00:00';
		$end_date =  date('Y-m-d', strtotime($end_date)).' 23:59:59';
		$this->utils->debug_log(__METHOD__,'remap date for total player game minute',$start_date, $end_date);

		if (isset($result['data']) && !empty($result['data'])) {
			foreach ($result['data'] as &$row) {

				if ($is_export) {
					// aff_username convert to affId
					$aff_username = $row[$col_aff_username];
					$_aff = $this->affiliatemodel->getAffiliateArrayByUsername($aff_username);
					$affId = $_aff['affiliateId'];
				}else{
					// html extract to affId
					$html_aff_username = $row[$col_aff_username];
					$affiliateid_list = $this->extractAffiliateidFromAnchorInHTML($html_aff_username);
					$affId = $affiliateid_list[0];
				}
				// $this->utils->debug_log(__METHOD__, [ 'data-row' => $row, 'aff_id' => $affId ]);
				# GET LIST OF PLAYERS UNDER AFFILIATE
				// $players = $this->affiliatemodel->getAllPlayersUnderAffiliateId($affId, $start_date, $end_date);

				if ($show_game_platform) {

					$players = $this->affiliatemodel->getAllPlayersUnderAffiliateId($affId);

					$gameInfo = $this->game_logs->getTotalBetsWinsLossGroupByGamePlatformByPlayers($players, $start_date, $end_date);

					list($row[$col_total_bets], $row[$col_total_win], $row[$col_total_loss]) = $this->printGameBetInfo($gameInfo, $is_export);
				}

			}
		}
		// code for summary
		$players = $this->affiliatemodel->getAllPlayersUnderAffiliateId(NULL, $start_date, $end_date);

		$result['summary']['total_registered_player'] = count($players);

		// $add_manual = false;
		// list($totalDeposit, $totalWithdrawal, $totalBonus, $totalCashback) = $this->transactions->getTotalDepositWithdrawalBonusCashbackByPlayers($players, $start_date, $end_date, $add_manual);

		// $result['summary']['total_cashback'] = number_format($totalCashback, 2);
		// $result['summary']['total_bonus'] = number_format($totalBonus, 2);
		// $result['summary']['total_deposit'] = number_format($totalDeposit, 2);
		// $result['summary']['total_withdraw'] = number_format($totalWithdrawal, 2);

		// list($totalBets, $totalWins, $totalLoss) = $this->game_logs->getTotalBetsWinsLossByPlayers($players, $start_date, $end_date);

		if ($show_game_platform) {

			$gameInfo = $this->game_logs->getTotalBetsWinsLossGroupByGamePlatformByPlayers($players, $start_date, $end_date);

			list($result['summary']['total_bet'], $result['summary']['total_win'], $result['summary']['total_loss']) = $this->printGameBetInfo($gameInfo, $is_export);
		}
		// else {
		// 	$result['summary']['total_bet'] = $this->utils->formatCurrencyNoSym($totalBets);
		// 	$result['summary']['total_win'] = $this->utils->formatCurrencyNoSym($totalWins);
		// 	$result['summary']['total_loss'] = $this->utils->formatCurrencyNoSym($totalLoss);
		// }

		// $win_loss = $totalLoss - $totalWins;
		// $result['summary']['total_win_loss'] = '<span class="' . ($win_loss == 0 ? '' : ($win_loss > 0 ? 'text-success' : 'text-danger')) . '">' . $this->utils->formatCurrencyNoSym($win_loss) . '</span>';

		// $income = $win_loss - $totalBonus - $totalCashback;
		// $result['summary']['total_income'] = '<span class="' . ($income == 0 ? '' : ($income > 0 ? 'text-success' : 'text-danger')) . '">' . $this->utils->formatCurrencyNoSym($income) . '</span>';

		// // $players = $this->affiliatemodel->getAffiliateDepositedPLayer(NULL, $start_date, $end_date);
		// // $result['summary']['total_deposited_player'] = count($players);

		// $result['summary']['total_deposited_player'] = $this->affiliatemodel->countAllAffDepositedPlayer($start_date, $end_date);

		// $el = microtime(1) - $el;
		// $this->utils->debug_log(__METHOD__, 'time consumption', sprintf('%.2f', $el));
        // end code from affiliateStatistics

		// $result['summary']['last_query'] = $last_query;
        return $result;
	}

    private function get_start_end_infos_from_input($input){
        $infos = [];

        $start_date = '2000-01-01';
		$end_date = $this->utils->getTodayForMysql();
		$yearmonth_from = date('Ym', strtotime($end_date));
		$yearmonth_to = date('Ym', strtotime($end_date));

		if (isset($input['enable_date']) && $input['enable_date'] == 'true') {
			if (isset($input['by_date_from'])) {
				$start_date = date('Y-m-d', strtotime($input['by_date_from']));
				$yearmonth_from = date('Ym', strtotime($input['by_date_from']));
			}

			if (isset($input['by_date_to'])) {
				// $end_date = $start_date = date('Y-m-d', strtotime($input['by_date_to']));
				$end_date = date('Y-m-d', strtotime($input['by_date_to']));
				$yearmonth_to = date('Ym', strtotime($input['by_date_to']));
			}
		}

        $infos['start_date'] = $start_date;
        $infos['yearmonth_from'] = $yearmonth_from;
        $infos['end_date'] = $end_date;
        $infos['yearmonth_to'] = $yearmonth_to;
        return $infos;
    }

	/**
	 * detail: get the game bet records
	 *
	 * @param array $gameInfo
	 * @param Boolean $is_export
	 *
	 * @return array
	 */
	private function printGameBetInfo($gameInfo, $is_export) {
		// $this->utils->debug_log('gameInfo', $gameInfo);
		$totalBets = '';
		$totalWins = '';
		$totalLoss = '';
		if (!empty($gameInfo)) {
			$gameSysMap = $this->utils->getGameSystemMap();
			//game platform id => (bet, win, loss)
			foreach ($gameSysMap as $id => $sysCode) {
				$info = isset($gameInfo[$id]) ? $gameInfo[$id] : array(0, 0, 0);
				if ($is_export) {
					$totalBets .= $sysCode . ': ' . $this->utils->formatCurrencyNoSym($info[0]) . ' |';
					$totalWins .= $sysCode . ': ' . $this->utils->formatCurrencyNoSym($info[1]) . ' |';
					$totalLoss .= $sysCode . ': ' . $this->utils->formatCurrencyNoSym($info[2]) . ' |';
				} else {
					$totalBets .= $sysCode . ': <span class="text-success">' . $this->utils->formatCurrencyNoSym($info[0]) . '</span> |';
					$totalWins .= $sysCode . ': <span class="text-danger">' . $this->utils->formatCurrencyNoSym($info[1]) . '</span> |';
					$totalLoss .= $sysCode . ': <span class="text-warning">' . $this->utils->formatCurrencyNoSym($info[2]) . '</span> |';
				}
			}

			// foreach ($gameInfo as $gamePlatformId => $info) {
			// 	$gameSysCode = $gameSysMap[$gamePlatformId];
			// 	$totalBets .= $gameSysCode . ': <span class="text-success">' . $this->utils->formatCurrencyNoSym($info[0]) . '</span> |';
			// 	$totalWins .= $gameSysCode . ': <span class="text-danger">' . $this->utils->formatCurrencyNoSym($info[1]) . '</span> |';
			// 	$totalLoss .= $gameSysCode . ': <span class="text-warning">' . $this->utils->formatCurrencyNoSym($info[2]) . '</span> |';
			// }
		} else {
			$totalBets = lang('N/A');
			$totalWins = lang('N/A');
			$totalLoss = lang('N/A');
		}
		return array($totalBets, $totalWins, $totalLoss);
	}

	/**
	 * detail: get the statistics for a certain affiliate
	 *
	 * @param int $affId player affiliateId
	 * @param array $request
	 * @param Boolean $is_expoert
	 *
	 * @return array
	 */
	public function affiliateStatisticsForAff($affId, $request, $is_export) {
		if (empty($affId)) {
			return null;
		}

		$readOnlyDB = $this->getReadOnlyDB();

		$this->load->library('data_tables', array("DB" => $readOnlyDB));
		$this->load->model(array('transactions', 'player_model', 'affiliatemodel', 'total_player_game_hour', 'game_logs', 'wallet_model'));

		$this->data_tables->is_export = $is_export;

		$affiliatemodel = $this->affiliatemodel;
		$isHidePlayerInfoOnAff=$this->utils->isHidePlayerInfoOnAff();

		$isSwitchToSecureId=$this->utils->isEnabledFeature('switch_to_player_secure_id_on_affiliate');

		$username_column = 0;
		// $realname_column = 1;
		// $affiliate_level_column = 2;
		// $total_sub_column = 3;
		// $total_players_column = 4;
		$total_bet_column = 1;
		$total_win_column = 2;
		$total_loss_column = 3;
		$total_cashback_column = 4;
		$total_bonus_column = 5;
		$total_deposit_column = 6;
		$total_withdraw_column = 7;
		$total_add_bal_column=8;
		$total_sub_bal_column=9;
		$total_balance_column = 10;
		$columns = array(
			array(
				'alias' => 'affiliateId',
				'select' => 'player.affiliateId',
			),
			array(
				'alias' => 'playerId',
				'select' => 'player.playerId',
			),
			array(
				'dt' => $username_column,
				'alias' => 'username',
				'select' => $isSwitchToSecureId ? 'player.secure_id' : 'player.username',
				'formatter' => function ($d, $row) use ($is_export, $isHidePlayerInfoOnAff, $isSwitchToSecureId) {

					if ($this->utils->isAffSubProject() && $this->utils->isEnabledFeature('masked_player_username_on_affiliate')) {
						$d = $this->utils->keepOnlyString($d, 4);
					}

					if ($is_export) {
						return ($d ? $d : lang('N/A'));
					} else {
						if($isHidePlayerInfoOnAff){
							return '<i class="fa fa-user" ></i> ' . ($d ? $d : '<i class="text-muted">' . lang('N/A') . '</i>');
						}else{
							$url = site_url('/affiliate/viewPlayerById/' . $row['playerId']);
							return '<i class="fa fa-user" ></i> ' . ($d ? '<a href="' . $url . '" target="_blank">' . $d . '</a>' : '<i class="text-muted">' . lang('N/A') . '</i>');
						}
					}
				},
				'name' => lang('Username'),
			),
			array(
				'dt' => $total_bet_column,
				'alias' => 'total_bet',
				'select' => 'player.playerId',
				'name' => lang('Total Bet'),
			),
			array(
				'dt' => $total_win_column,
				'alias' => 'total_win',
				'select' => 'player.playerId',
				'name' => lang('Total Win'),
			),
			array(
				'dt' => $total_loss_column,
				'alias' => 'total_loss',
				'select' => 'player.playerId',
				'name' => lang('Total Loss'),
			),
			array(
				'dt' => $total_cashback_column,
				'alias' => 'total_cashback',
				'select' => 'player.playerId',
				'name' => lang('Total Cashback'),
			),
			array(
				'dt' => $total_bonus_column,
				'alias' => 'total_bonus',
				'select' => 'player.playerId',
				'name' => lang('Total Bonus'),
			),
			array(
				'dt' => $total_deposit_column,
				'alias' => 'total_deposit',
				'select' => 'player.playerId',
				'name' => lang('Total Deposit'),
			),
			array(
				'dt' => $total_withdraw_column,
				'alias' => 'total_withdraw',
				'select' => 'player.playerId',
				'name' => lang('Total Withdraw'),
			),
			array(
				'dt' => $total_add_bal_column,
				'alias' => 'total_add_bal',
				'select' => 'player.playerId',
				'name' => lang('Total Add Balance'),
			),
			array(
				'dt' => $total_sub_bal_column,
				'alias' => 'total_sub_bal',
				'select' => 'player.playerId',
				'name' => lang('Total Subtract Balance'),
			),
			array(
				'dt' => $total_balance_column,
				'alias' => 'total_balance',
				'select' => 'player.playerId',
				'name' => lang('Balance'),
			),

		);
		# END DEFINE COLUMNS #################################################################################################################################################

		$table = 'player';
		$joins = array();

		# START PROCESS SEARCH FORM #################################################################################################################################################
		$where = array();
		$values = array();
		// $request = $this->input->post();
		$input = $this->data_tables->extra_search($request);
		$use_total_hour = $this->utils->getConfig('use_total_hour');
		$start_date = null;
		$end_date = null;
		if ($this->safeGetParam($input, 'enable_date') == 'true') {
			if (isset($input['by_date_from'])) {
				$start_date = $input['by_date_from'];
			}

			if (isset($input['by_date_to'])) {
				$end_date = $input['by_date_to'];
			}
		}

		if (empty($start_date)) {
			$start_date = '2000-01-01 00:00:00';
		}
		if (empty($end_date)) {
			$end_date = $this->utils->getNowForMysql();
		}

		if ($affId) {
			$where[] = "player.affiliateId = ?";
			$values[] = $affId;
		}

		$playerId = null;
		//convert memberUsername to player id
		if (isset($input['by_username'], $input['search_by'])) {
            if ($input['search_by'] == 1) {
                $where[] = "player.username LIKE ?";
                $values[] = '%' . $input['by_username'] . '%';

                $playerList = $this->player_model->getAvailablePlayers($input['by_username']);
                $this->utils->debug_log('input playerList', $playerList);
            } else if ($input['search_by'] == 2) {
                $where[] = "player.username = ?";
                $values[] = $input['by_username'];
            }
        }
		//==where condition=================================


		# END PROCESS SEARCH FORM #################################################################################################################################################
		$result = $this->data_tables->get_data($request, $columns, $table, $where, $values, $joins);
		$show_game_platform = isset($input['show_game_platform']) && $input['show_game_platform'];
		if (isset($result['data']) && !empty($result['data'])) {
			foreach ($result['data'] as &$row) {
				$player_id = $row[$total_bet_column];
				$players = array($player_id);
				//from transactions
				list($totalDeposit, $totalWithdrawal, $totalBonus, $totalCashback,$totalAddBal, $totalSubtractBal) =
					$this->transactions->getTotalBalDepositWithdrawalBonusCashbackByPlayers($players, $start_date, $end_date);

				if ($show_game_platform) {

					$gameInfo = $this->game_logs->getTotalBetsWinsLossGroupByGamePlatformByPlayers(
						$players, $start_date, $end_date);

					list($row[$total_bet_column], $row[$total_win_column], $row[$total_loss_column]) = $this->printGameBetInfo($gameInfo, $is_export);
				} else {

					list($totalBets, $totalWins, $totalLoss) = $this->game_logs->getTotalBetsWinsLossByPlayers(
						$players, $start_date, $end_date);

					$row[$total_bet_column] = $this->utils->formatCurrencyNoSym($totalBets);
					$row[$total_win_column] = $this->utils->formatCurrencyNoSym($totalWins);
					$row[$total_loss_column] = $this->utils->formatCurrencyNoSym($totalLoss);
				}

				$total_balance = $this->wallet_model->getTotalBalance($player_id);

				$row[$total_cashback_column] = $this->utils->formatCurrencyNoSym($totalCashback);
				$row[$total_bonus_column] = $this->utils->formatCurrencyNoSym($totalBonus);
				$row[$total_deposit_column] = $this->utils->formatCurrencyNoSym($totalDeposit);
				$row[$total_withdraw_column] = $this->utils->formatCurrencyNoSym($totalWithdrawal);
				$row[$total_add_bal_column]=$this->utils->formatCurrencyNoSym($totalAddBal);
				$row[$total_sub_bal_column]=$this->utils->formatCurrencyNoSym($totalSubtractBal);
				$row[$total_balance_column] = $this->utils->formatCurrencyNoSym($total_balance);

			}
		}

		if (isset($input['by_username'], $input['search_by'])) {
			$this->utils->debug_log('search_by', $input['search_by']);
            if ($input['search_by'] == 1) {
                $this->utils->debug_log('memberUsername', $input['by_username']);
				$playerList = $this->player_model->getAvailablePlayersArr($input['by_username']);

				$this->utils->debug_log('playerList', $playerList);
				$playerIdArr = $playerList ? array_column($playerList, 'playerId') : array();

            } else if ($input['search_by'] == 2) {
                $this->utils->debug_log('memberUsername', $input['by_username']);
				$playerIdArr = $this->player_model->getPlayerIdByUsername($input['by_username']);
            }
        }else {
			$playerIdArr = $affiliatemodel->getAllPlayersUnderAffiliateId($affId);
		}

		$this->utils->debug_log('playerIdArr', $playerIdArr);

		if ($show_game_platform) {
			$gameInfo = $this->game_logs->getTotalBetsWinsLossGroupByGamePlatformByPlayers(
				$playerIdArr, $start_date, $end_date);
			$totalBets = 0;
			$totalWins = 0;
			$totalLoss = 0;
			if(!empty($gameInfo)){
				foreach ($gameInfo as $key => $game) {
					$totalBets += $gameInfo[$key][0];
					$totalWins += $gameInfo[$key][1];
					$totalLoss += $gameInfo[$key][2];
				}
			}
		} else {
			list($totalBets, $totalWins, $totalLoss) = $this->game_logs->sumBetsWinsLossByDatetimePlayers($playerIdArr, $start_date, $end_date);
		}

		list($totalDeposit, $totalWithdrawal, $totalBonus, $totalCashback, $totalAddBal, $totalSubtractBal) = $this->transactions->getTotalBalDepositWithdrawalBonusCashbackByPlayers($playerIdArr, $start_date, $end_date);
		$result['summary'] = array();
		$result['summary']['totalBets'] = $this->utils->formatCurrencyNoSym($totalBets);
		$result['summary']['totalWins'] = $this->utils->formatCurrencyNoSym($totalWins);
		$result['summary']['totalLoss'] = $this->utils->formatCurrencyNoSym($totalLoss);
		$result['summary']['totalDeposit'] = $this->utils->formatCurrencyNoSym($totalDeposit);
		$result['summary']['totalWithdrawal'] = $this->utils->formatCurrencyNoSym($totalWithdrawal);
		$result['summary']['totalAddBal'] = $this->utils->formatCurrencyNoSym($totalAddBal);
		$result['summary']['totalSubtractBal'] = $this->utils->formatCurrencyNoSym($totalSubtractBal);
		$result['summary']['totalBonus'] = $this->utils->formatCurrencyNoSym($totalBonus);
		$result['summary']['totalCashback'] = $this->utils->formatCurrencyNoSym($totalCashback);

		return $result;
	}

	/**
	 * detail: get affiliate earnings
	 *
	 * @param array $request
	 * @param Boolean $is_export
	 *
	 * @return array
	 */
	public function affiliateEarnings($request, $is_export) {
		$readOnlyDB = $this->getReadOnlyDB();

		$this->load->library('data_tables', array("DB" => $readOnlyDB));
		$this->load->model(array('transactions', 'affiliatemodel', 'affiliate_earnings'));

		$this->data_tables->is_export = $is_export;

		$affiliatemodel = $this->affiliatemodel;
		$min_amount = $this->affiliate_earnings->getMinimumMonthlyPayAmountSetting();

		$action_column = 0;
		$affiliate_username_column = 1;
		$realname_column = 2;
		$yearmonth_column = 3;
		$aff_sub_column = 4;
		$active_players_column = 5;
		$count_players_column = 6;
		$gross_net_column = 7;
		$platform_fee = 8;
		$fee_column = 9;
		$net_column = 10;
		$percetage_column = 11;
		$amount_column = 12;
		$wallet_column = 13;
		$status_column = 14;
		$note_column = 15;

		$columns = array(
			array(
				'alias' => 'affiliate_id',
				'select' => 'monthly_earnings.affiliate_id',
			),
			array(
				'alias' => 'id',
				'select' => 'monthly_earnings.id',
			),
			array(
				'dt' => $action_column,
				'alias' => 'action',
				'select' => 'monthly_earnings.id',
				'formatter' => function ($d, $row) use ($is_export, $min_amount) {
					if ($is_export) {
						return '';
					} else {

						if ($row['paid_flag'] == 0 && ($row['amount'] >= $min_amount || $row['amount'] < 0)) {
							$id = $row['id'];
							$btnTxt = lang('Transfer to wallet');
							$btn = <<<EOD
<a class="btn btn-xs btn-primary pay" href="javascript:void(0)" onclick="payOne(this)" data-earningid="{$id}">
	<i class="fa fa-paper-plane-o"></i> {$btnTxt}
</a>
EOD;
							return $btn;
						} else {
							return '';
						}

					}
				},
			),
			array(
				'dt' => $affiliate_username_column,
				'alias' => 'username',
				'select' => 'affiliates.username',
				'formatter' => function ($d, $row) use ($is_export) {
					if ($is_export) {
						return ($d ? $d : lang('N/A'));
					} else {
						$url = site_url('/affiliate_management/userInformation/' . $row['affiliate_id']);
						return '' . ($d ? '<a href="' . $url . '" target="_blank">' . $d . '</a>' : '<i class="text-muted">' . lang('N/A') . '</i>');
					}
				},
				'name' => lang('Affiliate Username'),
			),
			array(
				'dt' => $realname_column,
				'alias' => 'realname',
				'select' => 'concat(affiliates.firstname," ",affiliates.lastname)',
				'name' => lang('Realname'),
			),
			array(
				'dt' => $yearmonth_column,
				'alias' => 'earning_year_month',
				'select' => 'monthly_earnings.`year_month`',
				'name' => lang('Year month'),
			),
			array(
				'dt' => $aff_sub_column,
				'alias' => 'total_sub',
				'select' => 'affiliates.countSub',
				'name' => lang('Total Sub-affiliates'),
			),
			array(
				'dt' => $active_players_column,
				'alias' => 'active_players',
				'select' => 'monthly_earnings.count_active_player',
				'name' => lang('Active Players'),
			),
			array(
				'dt' => $count_players_column,
				'alias' => 'total_players',
				'select' => 'affiliates.countPlayer',
				'name' => lang('Total Players'),
			),
			array(
				'dt' => $gross_net_column,
				'alias' => 'gross_net',
				'select' => 'monthly_earnings.gross_net',
				'name' => lang('Gross Net'),
			),
			array(
				'dt' => $platform_fee,
				'alias' => 'platform_fee',
				'select' => 'monthly_earnings.platform_fee',
				'name' => lang('Platform Fee'),
			),
			array(
				'alias' => 'transaction_fee',
				'select' => 'monthly_earnings.transaction_fee',
			),
			array(
				'alias' => 'cashback',
				'select' => 'monthly_earnings.cashback',
			),
			array(
				'alias' => 'admin_fee',
				'select' => 'monthly_earnings.admin_fee',
			),
			array(
				'dt' => $fee_column,
				'alias' => 'fee',
				'select' => 'monthly_earnings.bonus_fee',
				'name' => lang('Fee'),
				'formatter' => function ($d, $row) use ($is_export) {
					return $d + $row['transaction_fee'] + $row['cashback'] + $row['admin_fee'];
				},
			),
			array(
				'dt' => $net_column,
				'alias' => 'net',
				'select' => 'monthly_earnings.net',
				'name' => lang('Net'),
			),
			array(
				'dt' => $percetage_column,
				'alias' => 'rate_for_affiliate',
				'select' => 'monthly_earnings.rate_for_affiliate',
				'name' => lang('Percetage'),
			),
			array(
				'dt' => $amount_column,
				'alias' => 'amount',
				'select' => 'monthly_earnings.amount',
				'name' => lang('Amount'),
				'formatter' => 'currencyFormatter',
			),
			array(
				'dt' => $wallet_column,
				'alias' => 'wallet',
				'select' => 'affiliates.wallet_balance',
				'name' => lang('Wallet'),
				'formatter' => 'currencyFormatter',
			),
			array(
				'dt' => $status_column,
				'alias' => 'paid_flag',
				'select' => 'monthly_earnings.paid_flag',
				'name' => lang('Status'),
				'formatter' => function ($d, $row) use ($is_export) {
					return $d == 0 ? lang('Unpaid') : lang('Paid');
				},
			),
			array(
				'dt' => $note_column,
				'alias' => 'note',
				'select' => 'monthly_earnings.note',
				'name' => lang('Notes'),
			),

		);
		# END DEFINE COLUMNS #################################################################################################################################################

		$table = 'monthly_earnings';
		$joins = array(
			'affiliates' => "affiliates.affiliateId = monthly_earnings.affiliate_id",
		);

		# START PROCESS SEARCH FORM #################################################################################################################################################
		$where = array();
		$values = array();
		$input = $this->data_tables->extra_search($request);

		$use_total_hour = $this->utils->getConfig('use_total_hour');

		if (isset($input['by_year_month'])) {
			$where[] = 'monthly_earnings.`year_month` = ?';
			$values[] = $input['by_year_month'];
		}

		$affiliate_id = null;

		if (isset($input['by_username'])) {
			$affiliate_id = $this->affiliatemodel->getAffiliateIdByUsername($input['by_username']);
			if($input['by_username']!=""){
				$where[] = "affiliates.affiliateId = ?";
				$values[] = $affiliate_id;
			}
		}

		if ($affiliate_id) {
			$where[] = "monthly_earnings.affiliate_id = ?";
			$values[] = $affiliate_id;
		}

		if (isset($input['by_parent_id'])) {
			$where[] = "affiliates.parentId = ?";
			$values[] = $input['by_parent_id'];
		}

		//status
		if (isset($input['by_flag'])) {
			$by_flag = $input['by_flag'];
			$where[] = 'monthly_earnings.paid_flag=?';
			$values[] = $by_flag;
		}

		//==where condition=================================

		# END PROCESS SEARCH FORM #################################################################################################################################################
		$result = $this->data_tables->get_data($request, $columns, $table, $where, $values, $joins);

		return $result;
	}

	/**
	 * detail: get the all affiliates
	 *
	 * @param array $request
	 * @param Boolean $is_export
	 * @param array $permissions
	 *
	 * @return array
	 */
	public function aff_list($request, $is_export, $permissions) {
		$readOnlyDB = $this->getReadOnlyDB();

		$this->load->library('data_tables', array("DB" => $readOnlyDB));
		$this->load->model(array('transactions', 'affiliatemodel', 'affiliate_earnings'));
		$this->load->helper(['aff_helper']);

		$this->data_tables->is_export = $is_export;

		$affiliatemodel = $this->affiliatemodel;
		$min_amount = $this->affiliate_earnings->getMinimumMonthlyPayAmountSetting();

		$sysFeatureUpdateAffiliatePlayerTotal = $this->utils->getOperatorSetting('update_affiliate_player_total');
		$isEnableUpdateAffiliatePlayerTotal = $sysFeatureUpdateAffiliatePlayerTotal == 'ON';

		$i=0;

		$columns = array(
			array(
				'alias' => 'parentId',
				'select' => 'affiliates.parentId',
			),
			array(
				'alias' => 'affiliate_id',
				'select' => 'affiliates.affiliateId',
			),
			array(
				'dt' => (!$is_export) ? $i++ : NULL,
				'alias' => 'checkbox',
				'select' => 'affiliates.affiliateId',
				'formatter' => function ($d, $row) use ($is_export) {
					if ($is_export) {
						return $d;
					} else {
						$affId = $row['affiliate_id'];
						return "<input type='checkbox' class='checkWhite' id='aff_{$affId}' name='affiliate[]' value='{$affId}' onclick='uncheckAll(this.id)'>";
					}
				},
			),
			array(
				'dt' => (!$is_export) ? $i++ : NULL,
				'alias' => 'action',
				'select' => 'affiliates.affiliateId',
				'formatter' => function ($d, $row) use ($is_export, $permissions) {
					if ($is_export) {
						return $d;
					} else {
						$result = '<ul class="list-inline">';
						$affId = $row['affiliate_id'];
						$username = $row['username'];
						if ($permissions['activate_deactivate_affiliate'] && $row['status'] == 1) {
							$affUrl = site_url('affiliate_management/active/' . $affId.'/aff_list');
							$affTitle = lang('tool.am05');
							$result .= "<li><a href='{$affUrl}'' data-toggle='tooltip' title='{$affTitle}' onclick=\"return confirm('".lang('sys.ga.conf.able.msg')."')\"><span class='fa fa-user'></span></a></li>";
						} else if ($permissions['affiliate_tag'] && $row['status'] != 2) {
							$affTagTitle = lang('tool.am07');
							$result .= "<li><a href='#tags' data-toggle='tooltip' title='{$affTagTitle}' onclick='viewAffiliateWithCurrentPage({$affId}, \"affiliateTag\");'><span class='fa fa-tag'></span></a></li>";
						}

						if ($row['status'] == 2) {
							return lang('N/A');
						}

						$affAddTitle = lang('Add Remarks');
						$result .= "<li><a href='#notes' data-toggle='tooltip' title='{$affAddTitle}' onclick='affiliate_notes({$affId})'><span class='fa fa-sticky-note-o'></span></a></li>";

						$result .= '</ul>';
						return $result;
					}
				},
			),
			array(
				'dt' => $i++,
				'alias' => 'username',
				'select' => 'affiliates.username',
				'formatter' => function ($d, $row) use ($is_export) {
					if ($is_export) {
						return ($d ? $d : lang('N/A'));
					} else {
						$url = site_url('/affiliate_management/userInformation/' . $row['affiliate_id']);
						return '' . ($d ? '<a href="' . $url . '" target="_blank">' . $d . '</a>' : '<i class="text-muted">' . lang('N/A') . '</i>');
					}
				},
				'name' => lang('Affiliate Username'),
			),
			array(
				'dt' => $i++,
				'alias' => 'trackingCode',
				'select' => 'affiliates.trackingCode',
				'name' => lang('Tracking Code'),
				'formatter' => 'defaultFormatter',
			),
			array(
				'dt' => $i++,
				'alias' => 'realname',
				'select' => 'concat(affiliates.firstname," ",affiliates.lastname)',
				'name' => lang('Realname'),
				'formatter' => 'defaultFormatter',
			),
			array(
				'dt' => $i++,
				'alias' => 'email',
				'select' => 'affiliates.email',
				'name' => lang('Email'),
				'formatter' => function ($d, $row) use ($is_export, $permissions) {
					if($is_export){
						if ($permissions['affiliate_contact_info']) {
							return ($d ? $d : lang('N/A'));
						} else {
							return '******';
						}
					}else{
						if ($permissions['affiliate_contact_info']) {
							return $d ?: '<i class="text-muted">' . lang('N/A') . '</i>';
						} else {
							return '******';
						}
					}
				},
			),
			array(
				'dt' => $i++,
				'alias' => 'country',
				'select' => 'affiliates.country',
				'name' => lang('Country'),
				'formatter' => 'defaultFormatter',
			),
			array(
				'dt' => $i++,
				'alias' => 'parent',
				'select' => 'parentAff.username',
				'name' => lang('Parent'),
				'formatter' => function ($d, $row) use ($is_export) {
					if ($is_export) {
						return ($d ? $d : lang('N/A'));
					} else {
						$url = site_url('/affiliate_management/userInformation/' . $row['parentId']);
						return '' . ($d ? '<a href="' . $url . '" target="_blank">' . $d . '</a>' : '<i class="text-muted">' . lang('N/A') . '</i>');
					}
				},
			),
			array(
                'dt' => $i++,
                'alias' => 'tagName',
                'select' => 'affiliates.affiliateId',
                'name' => lang("player.41"),
                'formatter' => function ($d, $row) use ($is_export) {
                    return aff_tagged_list($row['affiliate_id'], $is_export);
                }
			),
			array(
				'dt' => $i++,
				'alias' => 'reg_date',
				'select' => 'affiliates.createdOn',
				'name' => lang('Registration Date'),
			),
			array(
				'dt' => $i++,
				'alias' => 'prefix_of_player',
				'select' => 'affiliates.prefix_of_player',
				'name' => lang('Prefix of player'),
				// 'formatter' => 'currencyFormatter',
			),
            array(
                'dt' => $this->utils->getConfig('display_aff_list_total_players_col') ? $i++ : null,
                'alias' => 'total_players',
                'select' => 'affiliates.countPlayer',
                'name' => lang('Total Players'),
            ),
			array(
				'dt' => $i++,
				'alias' => 'wallet_balance',
				'select' => 'affiliates.wallet_balance',
				'name' => lang('Balance Wallet'),
				'formatter' => 'currencyFormatter',
			),
			array(
				'dt' => $isEnableUpdateAffiliatePlayerTotal ? $i++ : null,
				'alias' => 'totalPlayerDeposit',
				'select' => 'affiliates.totalPlayerDeposit',
				'name' => lang('aff.al51'),
				'formatter' => 'currencyFormatter',
			),
			array(
				'dt' => $isEnableUpdateAffiliatePlayerTotal ? $i++ : null,
				'alias' => 'totalPlayerWithdraw',
				'select' => 'affiliates.totalPlayerWithdraw',
				'name' => lang('aff.al52'),
				'formatter' => 'currencyFormatter',
			),
			array(
				'dt' => $i++,
				'alias' => 'status',
				'select' => 'affiliates.status',
				'formatter' => function ($d, $row) use ($is_export) {
					$status = '';
					if ($d == 0) {
						$status = lang('Active');
					} else if ($d == 1) {
						$status = lang('Inactive');
					} else {
						$status = lang('Deleted');
					}
					return $status;
				},
				'name' => lang('Status'),
			),
			array(
				'dt' => $i++,
				'alias' => 'lastLogin',
				'select' => 'affiliates.lastLogin',
				'name' => lang('Last Login'),
				'formatter' => function($d, $row) {
					return strtotime($d) > 0 ? $d : '';
				}
			),
			array(
				'dt' => $i++,
				'alias' => 'lastLoginIp',
				'select' => 'affiliates.lastLoginIp',
				'name' => lang('Last Login IP'),
				'formatter' => function($d, $row) {
					return ($d ? $d : lang('N/A'));
				}
			),
			array(
				'dt' => $i++,
				'alias' => 'signupIP',
				'select' => 'affiliates.ip_address',
				'name' => lang('Signup IP'),
				'formatter' => function($d, $row) {
					return ($d ? $d : lang('N/A'));
				}
			),
		);
		# END DEFINE COLUMNS #################################################################################################################################################

		$table = 'affiliates';
		$joins = array(
			'affiliates as parentAff' => "affiliates.parentId = parentAff.affiliateId",
		);

		# START PROCESS SEARCH FORM #################################################################################################################################################
		$where = array();
		$values = array();
		$input = $this->data_tables->extra_search($request);

		$affiliate_id = null;
		if (isset($input['search_reg_date']) && $input['search_reg_date']) {

			if (isset($input['start_date'])) {
				$where[] = "affiliates.createdOn >= ?";
				$values[] = $input['start_date'];
			}

			if (isset($input['end_date'])) {
				$where[] = "affiliates.createdOn <= ?";
				$values[] = $input['end_date'];
			}
		}

		if (isset($input['domain'])) {
			$joins['affiliate_domain'] = 'affiliate_domain.affiliateId = affiliates.affiliateId';
			$joins['domain'] = 'domain.domainId = affiliate_domain.domainId OR show_to_affiliate = 1';
			$joins['aff_tracking_link'] = 'aff_tracking_link.aff_id = affiliates.affiliateId AND aff_tracking_link.deleted_at IS NULL AND aff_tracking_link.tracking_type = ' . Affiliatemodel::TRACKING_TYPE_DOMAIN;
			$where[] = "(affiliates.affdomain LIKE ? OR domain.domainName LIKE ? OR aff_tracking_link.tracking_domain LIKE ?)";
			$values[] = '%' . $input['domain'] . '%';
			$values[] = '%' . $input['domain'] . '%';
			$values[] = '%' . $input['domain'] . '%';
		}

		if (isset($input['by_username'])) {
			$affiliate_id = $this->affiliatemodel->getAffiliateIdByUsername($input['by_username']);
			if($input['by_username']!=""){
				$where[] = "affiliates.username LIKE ?";
				$values[] = '%' . $input['by_username'] . '%';
			}
		}

		if (isset($input['by_code'])) {
			$where[] = "affiliates.trackingCode = ?";
			$values[] = $input['by_code'];
		}

		if (isset($input['by_firstname'])) {
			$where[] = "affiliates.firstname like ?";
			$values[] = '%' . $input['by_firstname'] . '%';
		}

		if (isset($input['by_lastname'])) {
			$where[] = "affiliates.lastname like ?";
			$values[] = '%' . $input['by_lastname'] . '%';
		}

		if (isset($input['by_email'])) {
			$where[] = "affiliates.email = ?";
			$values[] = $input['by_email'];
		}

		if ($affiliate_id) {
			$where[] = "affiliates.affiliateId = ?";
			$values[] = $affiliate_id;
		}

		if (isset($input['by_parent_id'])) {
			$where[] = "affiliates.parentId = ?";
			$values[] = $input['by_parent_id'];
		}

        if (isset($input['tag_id'])) {
            if (is_array($input['tag_id'])) {
                $where[] = 'affiliates.affiliateId IN ( SELECT affiliateId FROM affiliatetag WHERE tagId IN ('.implode(',', $input['tag_id']).') )';
            } else {
                $where[] = 'affiliates.affiliateId IN ( SELECT affiliateId FROM affiliatetag WHERE tagId = ? )';
                $values[] = $input['tag_id'];
            }
        }

		//status
		if (isset($input['by_status'])) {
			$by_status = $input['by_status'];
			if ($by_status == -1) {
				$where[] = 'affiliates.countPlayer > ?';
				$values[] = 0;
				// $values[] = $by_status;
			} else {
				$where[] = 'affiliates.status = ?';
				$values[] = $by_status;
			}
		}
		//else {
			$where[] = 'affiliates.status != ?';
			$values[] = Affiliatemodel::STATUS_DELETED;
			$where[] = "affiliates.deleted_at IS NULL";
		//}

		if (isset($input['last_login_ip'])) {
			$where[] = "affiliates.lastLoginIp = ?";
			$values[] = $input['last_login_ip'];
		}

		if (isset($input['signup_ip'])) {
			$where[] = "affiliates.ip_address = ?";
			$values[] = $input['signup_ip'];
		}

        $where[] = "affiliates.is_hide = ". (int)Affiliatemodel::DB_FALSE;

		//==where condition=================================

		// $this->benchmark->mark('pre_processing_end');

		# END PROCESS SEARCH FORM #################################################################################################################################################
		// $this->benchmark->mark('data_sql_start');
		//echo "<pre>";print_r($where);exit;
		if($is_export){
            $this->data_tables->options['is_export']=true;
			// $this->data_tables->options['only_sql']=true;
            if(empty($csv_filename)){
                $csv_filename=$this->utils->create_csv_filename(__FUNCTION__);
            }
            $this->data_tables->options['csv_filename']=$csv_filename;
		}
		$group_by = array('affiliates.affiliateId');
		$result = $this->data_tables->get_data($request, $columns, $table, $where, $values, $joins, $group_by);
		// $this->benchmark->mark('data_sql_end');

		// $this->utils->debug_log($result);
		if($is_export){
		    //drop result if export
			return $csv_filename;
		}

        if( empty($result['data']) ){ // No data
            if( !empty($input['by_username'])){
            	if(!empty($affiliate_id)){
	                if( $this->affiliatemodel->is_hide($affiliate_id) ){
	                    $result['affiliate_username_is_hide'] = $input['by_username'];
	                }
            	}else{
            		$result['affiliate_username_is_hide'] = 'not_exist';
            	}
			}
        }

		return $result;
	}

	/**
	 * detail: get affiliate traffic statistics
	 *
	 * @param int $affId affiliate_traffic_stats affiliate_id
	 * @param array $request
	 * @param Boolean $is_export
	 *
	 * @return array
	 */
	public function traffic_statistics_aff($affId, $request, $is_export){

		//get from affiliate_traffic_stats
		if (empty($affId)) {
			return null;
		}

		//debug
		// sleep(5);

		$readOnlyDB = $this->getReadOnlyDB();

		$this->load->library('data_tables', array("DB" => $readOnlyDB));
		$this->load->model(array('transactions', 'player_model', 'affiliatemodel', 'wallet_model'));

		$this->data_tables->is_export = $is_export;

		$affiliatemodel = $this->affiliatemodel;
		$isHidePlayerInfoOnAff=$this->utils->isHidePlayerInfoOnAff();

		$isSwitchToSecureId=$this->utils->isEnabledFeature('switch_to_player_secure_id_on_affiliate');

		$tracking_code=$this->affiliatemodel->getTrackingCodeByAffiliateId($affId);

		$date_column = 0;
		$triggered_url_column = 1;
		$registration_website_column = 2;
		$banner_column = 3;
		$tracking_code_column = 4;
		$source_code_column = 5;
		$no_of_clicks_column = 6;
		$sign_up_column = 7;
		$ftd_number_column = 8;
		$ftd_amount_column = 9;
		$total_deposit_column = 10;
        $remarks = 11;

		$columns = array(
			array(
				'alias' => 'affiliate_id',
				'select' => 'affiliate_traffic_stats.affiliate_id',
			),
			array(
				'alias' => 'banner_id',
				'select' => 'affiliate_traffic_stats.banner_id',
			),
			array(
				'dt' => $date_column,
				'alias' => 'date_column',
				'select' => 'affiliate_traffic_stats.created_at',
				'name' => lang('Date'),
				'formatter' => 'defaultFormatter',
			),
			array(
				'dt' => $triggered_url_column,
				'alias' => 'triggered_url_column',
				'select' => 'affiliate_traffic_stats.referrer',
				'name' => lang('URL'),
				'formatter' => 'defaultFormatter',
			),
			array(
				'dt' => $registration_website_column,
				'alias' => 'registrationWebsite',
				'select' => 'playerdetails.registrationWebsite',
				'name' => lang('Registration Website'),
				'formatter' => 'defaultFormatter',
			),
			array(
				'dt' => $banner_column,
				'alias' => 'banner',
				'select' => 'affiliate_traffic_stats.banner_name',
				'formatter' => function ($d, $row) use ($is_export, $tracking_code) {

					if ($is_export) {
						return ($d ? $d : lang('N/A'));
					} else {
						if(!empty($row['banner_id'])){
							$url=$this->utils->getSystemUrl('player','/pub/banner/'.$row['banner_id'].'/'.$tracking_code);

                            if ($this->utils->isEnabledMDB()) {
                                $url .= '?__OG_TARGET_DB=' . $this->utils->getActiveCurrencyKeyOnMDB();
                            }
							return '<a href="'.$url.'" target="_blank">'.$d.'</a>';
						}else{
							return lang('N/A');
						}
					}
				},
				'name' => lang('Banner'),
			),
			array(
				'dt' => $tracking_code_column,
				'alias' => 'tracking_code',
				'select' => 'affiliate_traffic_stats.tracking_code',
				'name' => lang('Tracking Code'),
			),
			array(
				'dt' => $source_code_column,
				'alias' => 'tracking_source_code',
				'select' => 'affiliate_traffic_stats.tracking_source_code',
				'name' => lang('Source Code'),
				'formatter' => 'defaultFormatter',
			),
			array(
				'dt' => $no_of_clicks_column,
				'alias' => 'no_of_clicks',
				'select' => 'count(distinct affiliate_traffic_stats.id)',
				'name' => lang('No of Clicks'),
			),
			array(
				'dt' => $sign_up_column,
				'alias' => 'sign_up',
				'select' => 'count(distinct if(affiliate_traffic_stats.sign_up_player_id<=0, null, affiliate_traffic_stats.sign_up_player_id))',
				'name' => lang('Sign Up'),
			),
			array(
				'dt' => $ftd_number_column,
				'alias' => 'first_time_deposit',
				'select' => 'GROUP_CONCAT(DISTINCT affiliate_traffic_stats.sign_up_player_id SEPARATOR ",")',
				'name' => lang('First Time Deposit'),
			),
			array(
				'dt' => $ftd_amount_column,
				'alias' => 'first_time_deposit_amount',
				'select' => 'GROUP_CONCAT(DISTINCT affiliate_traffic_stats.sign_up_player_id SEPARATOR ",")',
				'name' => lang('First Time Deposit Amount'),
			),
			array(
				'dt' => $total_deposit_column,
				'alias' => 'total_deposit',
				'select' => 'sum(transactions.amount)',
				'name' => lang('Total Deposit'),
				'formatter' => 'currencyFormatter',
			),
            array(
                'dt' => $remarks,
                'alias' => 'remarks',
                'select' => 'aff_tracking_link.remarks',
                'name' => lang('Remarks'),
            ),
		);
		# END DEFINE COLUMNS #################################################################################################################################################

		$table = 'affiliate_traffic_stats';
		$joins = array(
			'transactions' => 'transactions.to_id=affiliate_traffic_stats.sign_up_player_id and transactions.to_type='.Transactions::PLAYER.' and transactions.transaction_type='.Transactions::DEPOSIT. ' and transactions.`status`='. Transactions::APPROVED,
			'playerdetails' => "affiliate_traffic_stats.sign_up_player_id = playerdetails.playerId",
            'aff_tracking_link' => "affiliate_traffic_stats.tracking_source_code = aff_tracking_link.tracking_source_code and affiliate_traffic_stats.affiliate_id = aff_tracking_link.aff_id",
		);
		$group_by=['date_column','registrationWebsite','affiliate_traffic_stats.banner_id', 'affiliate_traffic_stats.tracking_code',
			'affiliate_traffic_stats.tracking_source_code'];

		# START PROCESS SEARCH FORM #################################################################################################################################################
		$where = array();
		$values = array();
		// $request = $this->input->post();
		$input = $this->data_tables->extra_search($request);
		$start_date = null;
		$end_date = null;
		if ($this->safeGetParam($input, 'enable_date') == 'true') {

			if (isset($input['by_date_from'])) {
				$start_date = $input['by_date_from'];
			}

			if (isset($input['by_date_to'])) {
				$end_date = $input['by_date_to'];
			}
		}

		if (empty($start_date)) {
			$start_date = '2000-01-01 00:00:00';
		}
		if (empty($end_date)) {
			$end_date = $this->utils->getNowForMysql();
		}
		$where[]='affiliate_traffic_stats.created_at >= ?';
		$values[]=$start_date;
		$where[]='affiliate_traffic_stats.created_at <= ?';
		$values[]=$end_date;

		if ($affId) {
			$where[] = "affiliate_traffic_stats.affiliate_id = ?";
			$values[] = $affId;
		}

		if (isset($input['by_banner_name'])) {
			$where[] = "affiliate_traffic_stats.banner_name like ?";
			$values[] = '%' . $input['by_banner_name'] . '%';
		}

		if (isset($input['by_tracking_code'])) {
			$where[] = "affiliate_traffic_stats.tracking_code like ?";
			$values[] = '%'.$input['by_tracking_code'].'%';
		}

		if (isset($input['by_tracking_source_code'])) {
			$where[] = "affiliate_traffic_stats.tracking_source_code like ?";
			$values[] = '%'.$input['by_tracking_source_code'].'%';
		}

		if (isset($input['by_type'])) {
			$where[] = "affiliate_traffic_stats.type = ? ";
			$values[] = $input['by_type'];
		}

		if (isset($input['registrationWebsite'])) {
			$where[] = "playerdetails.registrationWebsite like ?";
			$values[] = '%'.$input['registrationWebsite'].'%';
		}

		if (isset($input['remarks'])) {
			$where[] = "aff_tracking_link.remarks like ?";
			$values[] = '%'.$input['remarks'].'%';
		}
		//==where condition=================================

		# END PROCESS SEARCH FORM #################################################################################################################################################
		$result = $this->data_tables->get_data($request, $columns, $table, $where, $values, $joins, $group_by);

		if (isset($result['data']) && !empty($result['data'])) {
			foreach ($result['data'] as &$row) {
				$playerStr = $row[$ftd_number_column];
				$playerIdArr= explode(',', $playerStr);
				$this->utils->debug_log('playerIdArr', $playerIdArr, $playerStr);

				list($ftdNumber, $ftdAmount)=$this->transactions->sumFirstTimeDepositInfo($playerIdArr);

				$row[$ftd_number_column]=$ftdNumber;
				$row[$ftd_amount_column]=$this->utils->formatCurrencyNoSym($ftdAmount);
			}
		}
		$where[]='affiliate_traffic_stats.created_at >= ?';
		$values[]=$start_date;
		$where[]='affiliate_traffic_stats.created_at <= ?';
		$values[]=$end_date;

		$playerIds = $this->db->distinct()->select('affiliate_traffic_stats.sign_up_player_id')->from('affiliate_traffic_stats')
		->where('affiliate_traffic_stats.created_at >=', $start_date)
		->where('affiliate_traffic_stats.created_at <=', $end_date)
		->where('affiliate_traffic_stats.affiliate_id', $affId)
		->get()->result_array();
		$playerIds = array_filter(array_column($playerIds, 'sign_up_player_id'));
		list($ftdNumber, $ftdAmount)=$this->transactions->sumFirstTimeDepositInfo($playerIds);

		$deposit_amount = $this->db->select_sum('transactions.amount')->from('affiliate_traffic_stats')->join('transactions','transactions.to_id=affiliate_traffic_stats.sign_up_player_id and transactions.to_type='.Transactions::PLAYER.' and transactions.transaction_type='.Transactions::DEPOSIT.' and transactions.status='.Transactions::APPROVED)->where('affiliate_traffic_stats.affiliate_id', $affId)
		->where('affiliate_traffic_stats.created_at >=', $start_date)
		->where('affiliate_traffic_stats.created_at <=', $end_date)
		->get()->row()->amount;

		$result['total']['first_time_deposit_amount'] = number_format($ftdAmount,2);
		$result['total']['deposit_amount'] = number_format($deposit_amount,2);

		return $result;
	}

	/**
	* detail: get affiliate traffic statistics from SBE
	*
	* @param int $affId affiliate_traffic_stats affiliate_id
	* @param array $request
	* @param Boolean $is_export
	*
	* @return array
	*/
	public function affiliate_traffic_statistics($request, $is_export){


		//debug
		// sleep(5);

		$readOnlyDB = $this->getReadOnlyDB();

		$this->load->library('data_tables', array("DB" => $readOnlyDB));
		$this->load->model(array('transactions', 'player_model', 'affiliatemodel', 'wallet_model'));

		$this->data_tables->is_export = $is_export;

		$affiliatemodel = $this->affiliatemodel;
		$isHidePlayerInfoOnAff=$this->utils->isHidePlayerInfoOnAff();

		//$isSwitchToSecureId=$this->utils->isEnabledFeature('switch_to_player_secure_id_on_affiliate');

		$tracking_code=null;//$tracking_code=$this->affiliatemodel->getTrackingCodeByAffiliateId($affId);

		$date_column = 0;
		$aff_username_column = 1;
		$registration_website_column = 2;
		$banner_column = 3;
		$tracking_code_column = 4;
		$source_code_column = 5;
		$no_of_clicks_column = 6;
		$sign_up_column = 7;
		$ftd_number_column = 8;
		$ftd_amount_column = 9;
		$total_deposit_column = 10;
		$remarks = 11;

		$columns = array(
			array(
				'alias' => 'affiliate_id',
				'select' => 'affiliate_traffic_stats.affiliate_id',
			),
			array(
				'alias' => 'banner_id',
				'select' => 'affiliate_traffic_stats.banner_id',
			),
			array(
				'dt' => $date_column,
				'alias' => 'date_column',
				'select' => 'affiliate_traffic_stats.created_at',
				'name' => lang('Date'),
				'formatter' => 'defaultFormatter',
			),
            array(
				'alias' => 'is_hide',
				'select' => 'affiliates.is_hide',
			),
			array(
				'dt' => $aff_username_column,
				'alias' => 'affiliate_username',
				'select' => 'affiliates.username',
				'name' => lang('Affiliates Username'),
                'formatter' => function ($d, $row) {
                    $returnStr = '';

                    $aff_username = $d;
                    $returnStr .= $aff_username;
                    if( $row['is_hide'] ){
                        $returnStr .= ' ';
                        $returnStr .= '('. lang('Hidden'). ')';
                    }
                    return $returnStr;
                }
			),
			array(
				'dt' => $registration_website_column,
				'alias' => 'registrationWebsite',
				'select' => 'playerdetails.registrationWebsite',
				'name' => lang('Registration Website'),
				'formatter' => 'defaultFormatter',
			),
			array(
				'dt' => $banner_column,
				'alias' => 'banner',
				'select' => 'affiliate_traffic_stats.banner_name',
				'formatter' => function ($d, $row) use ($is_export, $tracking_code) {

					if ($is_export) {
						return ($d ? $d : lang('N/A'));
					} else {
						if(!empty($row['banner_id'])){
							$url=$this->utils->getSystemUrl('player','/pub/banner/'.$row['banner_id'].'/'.$row['tracking_code']);

							return '<a href="'.$url.'" target="_blank">'.$d.'</a>';
						}else{
							return lang('N/A');
						}
					}
				},
				'name' => lang('Banner'),
			),
			array(
				'dt' => $tracking_code_column,
				'alias' => 'tracking_code',
				'select' => 'affiliate_traffic_stats.tracking_code',
				'name' => lang('Tracking Code'),
			),
			array(
				'dt' => $source_code_column,
				'alias' => 'tracking_source_code',
				'select' => 'affiliate_traffic_stats.tracking_source_code',
				'name' => lang('Source Code'),
				'formatter' => 'defaultFormatter',
			),
			array(
				'dt' => $no_of_clicks_column,
				'alias' => 'no_of_clicks',
				'select' => 'count(distinct affiliate_traffic_stats.id)',
				'name' => lang('No of Clicks'),
			),
			array(
				'dt' => $sign_up_column,
				'alias' => 'sign_up',
				'select' => 'count(distinct if(affiliate_traffic_stats.sign_up_player_id<=0, null, affiliate_traffic_stats.sign_up_player_id))',
				'name' => lang('Sign Up'),
			),
			array(
				'dt' => $ftd_number_column,
				'alias' => 'first_time_deposit',
				'select' => 'GROUP_CONCAT(DISTINCT affiliate_traffic_stats.sign_up_player_id SEPARATOR ",")',
				'name' => lang('First Time Deposit'),
			),
			array(
				'dt' => $ftd_amount_column,
				'alias' => 'first_time_deposit_amount',
				'select' => 'GROUP_CONCAT(DISTINCT affiliate_traffic_stats.sign_up_player_id SEPARATOR ",")',
				'name' => lang('First Time Deposit Amount'),
			),
			array(
				'dt' => $total_deposit_column,
				'alias' => 'total_deposit',
				'select' => 'sum(transactions.amount)',
				'name' => lang('Total Deposit'),
				'formatter' => 'currencyFormatter',
			),
			array(
				'dt' => $remarks,
				'alias' => 'remarks',
				'select' => 'aff_tracking_link.remarks',
				'name' => lang('Remarks'),
			),
		);
		# END DEFINE COLUMNS #################################################################################################################################################

		$table = 'affiliate_traffic_stats';
		$joins = array(
			'transactions' => 'transactions.to_id=affiliate_traffic_stats.sign_up_player_id and transactions.to_type='.Transactions::PLAYER.' and transactions.transaction_type='.Transactions::DEPOSIT. ' and transactions.`status`='. Transactions::APPROVED,
			'playerdetails' => "affiliate_traffic_stats.sign_up_player_id = playerdetails.playerId",
			'affiliates' => "affiliates.affiliateId = affiliate_traffic_stats.affiliate_id",
			'aff_tracking_link' => "affiliate_traffic_stats.tracking_source_code = aff_tracking_link.tracking_source_code and affiliate_traffic_stats.affiliate_id = aff_tracking_link.aff_id",
		);
		$group_by=['date_column','registrationWebsite','affiliate_traffic_stats.banner_id', 'affiliate_traffic_stats.tracking_code',
			'affiliate_traffic_stats.tracking_source_code'];

		# START PROCESS SEARCH FORM #################################################################################################################################################
		$where = array();
		$values = array();

		$input = $this->data_tables->extra_search($request);
		$this->utils->debug_log('bermar input', $input);

		$start_date = null;
		$end_date = null;
		if ($this->safeGetParam($input, 'enable_date') == 'true') {

			if (isset($input['by_date_from'])) {
				$start_date = $input['by_date_from'];
			}

			if (isset($input['by_date_to'])) {
				$end_date = $input['by_date_to'];
			}
		}

		if (empty($start_date)) {
			$start_date = '2000-01-01 00:00:00';
		}
		if (empty($end_date)) {
			$end_date = $this->utils->getNowForMysql();
		}

		$where[]='DATE(affiliate_traffic_stats.created_at) >= ?';
		$values[]=$start_date;
		$where[]='DATE(affiliate_traffic_stats.created_at) <= ?';
		$values[]=$end_date;

		if (isset($input['by_affiliate_username'])) {
			$where[] = "affiliates.username like ?";
			$values[] = '%' . $input['by_affiliate_username'] . '%';
		}

		if (isset($input['by_banner_name'])) {
			$where[] = "affiliate_traffic_stats.banner_name like ?";
			$values[] = '%' . $input['by_banner_name'] . '%';
		}

		if (isset($input['by_tracking_code'])) {
			$where[] = "affiliate_traffic_stats.tracking_code like ?";
			$values[] = '%'.$input['by_tracking_code'].'%';
		}

		if (isset($input['by_tracking_source_code'])) {
			$where[] = "affiliate_traffic_stats.tracking_source_code like ?";
			$values[] = '%'.$input['by_tracking_source_code'].'%';
		}

		if (isset($input['by_type'])) {
			$where[] = "affiliate_traffic_stats.type = ? ";
			$values[] = $input['by_type'];
		}

		if (isset($input['registrationWebsite'])) {
			$where[] = "playerdetails.registrationWebsite like ?";
			$values[] = '%'.$input['registrationWebsite'].'%';
		}

		if (isset($input['remarks'])) {
			$where[] = "aff_tracking_link.remarks like ?";
			$values[] = '%'.$input['remarks'].'%';
		}
		//==where condition=================================

		# END PROCESS SEARCH FORM #################################################################################################################################################
		$result = $this->data_tables->get_data($request, $columns, $table, $where, $values, $joins, $group_by);
		$totalFtAmount = 0;
		if (isset($result['data']) && !empty($result['data'])) {
			foreach ($result['data'] as &$row) {
				$playerStr = $row[$ftd_number_column];
				$playerIdArr= explode(',', $playerStr);
				$this->utils->debug_log('playerIdArr', $playerIdArr, $playerStr);

				list($ftdNumber, $ftdAmount)=$this->transactions->sumFirstTimeDepositInfo($playerIdArr);

				$row[$ftd_number_column]=$ftdNumber;
				$row[$ftd_amount_column]=$this->utils->formatCurrencyNoSym($ftdAmount);
				$totalFtAmount+=$ftdAmount;
			}
		}

		$deposit_amount = $this->db->select_sum('transactions.amount')
		->from('affiliate_traffic_stats')
		->join('transactions','transactions.to_id=affiliate_traffic_stats.sign_up_player_id and transactions.to_type='.Transactions::PLAYER.' and transactions.transaction_type='.Transactions::DEPOSIT.' and transactions.status='.Transactions::APPROVED)
		->join('playerdetails', 'affiliate_traffic_stats.sign_up_player_id = playerdetails.playerId')
		->join('affiliates', "affiliates.affiliateId=affiliate_traffic_stats.affiliate_id", 'left')
		->join('aff_tracking_link', "affiliate_traffic_stats.tracking_source_code = aff_tracking_link.tracking_source_code and affiliate_traffic_stats.affiliate_id = aff_tracking_link.aff_id", 'left');

		$this->db->where('DATE(affiliate_traffic_stats.created_at) >=', $start_date);
		$this->db->where('DATE(affiliate_traffic_stats.created_at) <=', $end_date);

		 if (isset($input['by_affiliate_username'])) {
			 $this->db->like('affiliates.username', $input['by_affiliate_username']);
		 }

		 if (isset($input['by_banner_name'])) {
			 $this->db->like('affiliate_traffic_stats.banner_name', $input['by_banner_name']);
		 }

		 if (isset($input['by_tracking_code'])) {
			 $this->db->like('affiliate_traffic_stats.tracking_code', $input['by_tracking_code']);
		 }

		 if (isset($input['by_tracking_source_code'])) {
			 $this->db->like('affiliate_traffic_stats.tracking_source_code', $input['by_tracking_source_code']);
		 }

		 if (isset($input['by_type'])) {
			 $this->db->where('affiliate_traffic_stats.type', $input['by_type']);
		 }

		 if (isset($input['registrationWebsite'])) {
			 $this->db->like('playerdetails.registrationWebsite', $input['registrationWebsite']);
		 }

		 if (isset($input['remarks'])) {
			 $this->db->like('aff_tracking_link.remarks', $input['remarks']);
		 }

		 $deposit_amount = $this->db->get()->row()->amount;

		$result['total']['first_time_deposit_amount'] = number_format($totalFtAmount,2);
		$result['total']['deposit_amount'] = number_format($deposit_amount,2);

		return $result;
	}

	/**
	 * @param int $affiliate_id
	 * @param array $request
	 * @param array $viewPlayerInfoPerm
	 * @param bool $is_export
	 * @return array
	 */
	public function get_affiliate_player_reports($affiliate_id, $request, $viewPlayerInfoPerm, $is_export = false) {
		# Logged out
		if(empty($affiliate_id)) {
			return '';
		}

		$readOnlyDB = $this->getReadOnlyDB();

		$this->load->library('data_tables', array("DB" => $readOnlyDB));
		$this->load->model(array('transactions','game_logs','affiliatemodel'));

		$this->data_tables->is_export = $is_export;

		$input 		= $this->data_tables->extra_search($request);
		$affiliate_downline_ids = $this->affiliatemodel->includeAllDownlineAffiliateIds($affiliate_id);

		//use total day if > 1 day
		//use total hour if <= 1 day
		$use_total_hour=true;
		if (isset($input['search_on_date']) && $input['search_on_date']) {
			if (isset($input['date_from'], $input['date_to'])) {
				$from = strtotime($input['date_from']);
				$to = strtotime($input['date_to']);
				$one_day=3600*24;
				$use_total_hour = ($to - $from) <= $one_day;
			}
		}
        $only_show_non_zero_player=isset($input['only_show_non_zero_player']) ? $input['only_show_non_zero_player']=='true' : false;

		if($use_total_hour){
			$game_table='total_player_game_hour';
		}else{
			$game_table='total_player_game_day';
		}

		$this->utils->debug_log('use_total_hour', $use_total_hour, $game_table, @$input['date_from'], @$input['date_to']);

		$i 			= 0;
		$table 		= 'player';
		$joins 		= array();
		$where 		= array();
		$values 	= array();
		$group_by 	= array();
		$having 	= array();
		$start 		= $request['start'] + 1;
		# DEFINE TABLE COLUMNS ########################################################################################################################################################################
		$columns = array();
		$columns[] = array(
			'alias' => 'playerId',
			'select' => 'player.playerId',
		);
		$columns[] = array(
			'alias' => 'affiliateId',
			'select' => 'player.affiliateId',
		);
		$columns[] = array(
			'alias' => 'groupName',
			'select' => 'player.groupName',
		);
		$columns[] = array(
			'alias' => 'levelName',
			'select' => 'player.levelName',
		);
		$columns[] = array(
			'alias' => 'group_by',
			'select' => isset($input['group_by']) ? "'" . $input['group_by'] . "'" : '\'<i class="text-muted">N/A</i>\'',
		);
		$columns[] = array(
			'dt' => $i++,
			'alias' => 'serial',
			'select' => 'player.playerId',
			'name' => lang('report.pr01'),
			'formatter' => function($d, $row) use (&$start) {
				return $start++;
			}
		);
		$columns[] = array(
			'dt' => ($username_col = $i++),
			'alias' => 'username',
			'select' => 'player.username',
			'formatter' => function ($d, $row) use ($is_export) {

				if ($this->utils->isAffSubProject() && $this->utils->isEnabledFeature('masked_player_username_on_affiliate')) {
					$d = $this->utils->keepOnlyString($d, 4);
				}

				if ($is_export) {
					return $d;
				} else {
					return "<a href='/report_management/viewPlayerGameReport/{$row['playerId']}'>{$d}</a>";
				}
			},
			'name' => lang('report.pr01'),
		);

		if ($this->utils->isEnabledFeature('aff_show_real_name_on_reports')) {
			$columns[] = array(
				'dt' => ($realname_col = $i++),
				'alias' => 'realname',
				'select' => "CONCAT_WS(' ', playerdetails.firstName,playerdetails.lastName)",
				'formatter' => function ($d, $row) {

					if ($this->utils->isEnabledFeature('masked_realname_on_affiliate')) {
						$d = $this->utils->keepOnlyString($d, 4);
					}

					return trim($d);
				},
				'name' => lang('Real Name'),
			);
		}

		$columns[] = array(
			'dt' => ($affiliate_username_col = $i++),
			'alias' => 'affiliate_username',
			'select' => 'affiliates.username',
			'formatter' => function ($d, $row) use ($is_export) {

				if ($this->utils->isEnabledFeature('masked_affiliate_username_on_affiliate')) {
					$d = $this->utils->keepOnlyString($d, 4);
				}

				// if ($is_export) {
					return $d;
				// } else {
					// return "<a href='/report_management/viewPlayerGameReport/{$row['affiliateId']}'>{$d}</a>";
				// }
			},
			'name' => lang('report.pr01'),
		);
		$columns[] = array(
			'dt' => ($member_level_col = $i++),
			'alias' => 'member_level',
			'select' => "player.playerId",
			'formatter' => function ($d, $row) {
				return implode(' - ', array(lang($row['groupName']),lang($row['levelName'])));
			},
			'name' => lang('report.pr03'),
		);

		if($this->utils->getConfig('display_affiliate_player_ip_history_in_player_report')){
			$columns[] = array(
				'dt' => ($ip_address = $i++),
				'alias' => 'ip_address',
				'select' => "player_ip_last_request.ip",
				'formatter' => function ($d, $row) {
					return isset($d) ? '<a href="/affiliate/ip_history/'.$row['playerId'].'">' . $d . '</a>' : '<i class="text-muted">' . lang('N/A') . '</i>';
				},
				'name' => lang('report.pr03'),
			);
		}

		# start transactions #######################################################################
		$columns[] = array(
			'alias' => 'transactions_count',
			'select' => '(count(distinct transactions.id) / count(transactions.id))',
			'formatter' => 'currencyFormatter',
		);

        $columns[] = array(
            'alias' => 'transactions_count_2',
            'select' => 'count(distinct transactions.id)',
            'formatter' => 'currencyFormatter',
        );
        $columns[] = array(
            'alias' => 'game_count',
            'select' => 'count(distinct '.$game_table.'.id)',
            'formatter' => 'currencyFormatter',
        );

		if ($this->utils->isEnabledFeature('show_cashback_and_bonus_on_aff_player_report')) {
			$columns[] = array(
				'dt' => ($total_cashback_col = $i++),
				'alias' => 'total_cashback',
				'select' => 'SUM(CASE transactions.transaction_type WHEN ' . Transactions::AUTO_ADD_CASHBACK_TO_BALANCE . ' THEN transactions.amount WHEN '.Transactions::CASHBACK.' THEN transactions.amount ELSE 0 END)',
				'formatter' => function($d, $row) {
                    if($row['game_count'] == 0){
                        $row['game_count'] = 1;
                    }
                    $d = round($d / $row['game_count'], 2);
					return $d == 0 ? '<span class="text-muted">' . $this->utils->formatCurrencyNoSym($d) . '</span>' : '<strong>' . $this->utils->formatCurrencyNoSym($d) . '</strong>';
				},
				'name' => lang('report.pr21'),
			);
			$columns[] = array(
				'dt' => ($total_bonus_col = $i++),
				'alias' => 'total_bonus',
				'select' => 'SUM(CASE transactions.transaction_type WHEN '.Transactions::ADD_BONUS.' THEN transactions.amount WHEN '.Transactions::MEMBER_GROUP_DEPOSIT_BONUS.' THEN transactions.amount WHEN '.Transactions::PLAYER_REFER_BONUS.' THEN transactions.amount WHEN '.Transactions::SUBTRACT_BONUS.' THEN -transactions.amount ELSE 0 END)',
				'formatter' => function($d, $row) {
                    if($row['game_count'] == 0){
                        $row['game_count'] = 1;
                    }
                    $d = round($d / $row['game_count'], 2);
					return $d == 0 ? '<span class="text-muted">' . $this->utils->formatCurrencyNoSym($d) . '</span>' : '<strong>' . $this->utils->formatCurrencyNoSym($d) . '</strong>';
				},
				'name' => lang('report.pr21'),
			);
		}
		$columns[] = array(
			'dt' => $this->utils->isEnabledFeature('hide_deposit_and_withdraw_on_aff_player_report') ? NULL : ($total_deposit_col = $i++),
			'alias' => 'total_deposit',
			'select' => 'SUM(IF(transactions.transaction_type=' . Transactions::DEPOSIT . ',transactions.amount,0))',
			'formatter' => function($d, $row) {
                if($row['game_count'] == 0){
                    $row['game_count'] = 1;
                }
                $d = round($d / $row['game_count'], 2);
				return $d == 0 ? '<span class="text-muted">' . $this->utils->formatCurrencyNoSym($d) . '</span>' : '<strong>' . $this->utils->formatCurrencyNoSym($d) . '</strong>';
			},
			'name' => lang('report.pr21'),
		);
		$columns[] = array(
			'dt' => $this->utils->isEnabledFeature('hide_deposit_and_withdraw_on_aff_player_report') ? NULL : ($total_withdrawal_col = $i++),
			'alias' => 'total_withdrawal',
			'select' => 'SUM(IF(transactions.transaction_type=' . Transactions::WITHDRAWAL . ',transactions.amount,0))',
			'formatter' => function($d, $row) {
                if($row['game_count'] == 0){
                    $row['game_count'] = 1;
                }
                $d = round($d / $row['game_count'], 2);;
				return $d == 0 ? '<span class="text-muted">' . $this->utils->formatCurrencyNoSym($d) . '</span>' : '<strong>' . $this->utils->formatCurrencyNoSym($d) . '</strong>';
			},
			'name' => lang('report.pr22'),
		);
		$columns[] = array(
			'dt' => ($total_dep_with_col = $i++),
			'alias' => 'total_dep_with',
			'select' => 'player.playerId',
			'formatter' => function($d, $row) {
                if($row['game_count'] == 0){
                    $row['game_count'] = 1;
                }
				$d = round($row['total_deposit'] / $row['game_count'], 2) - round($row['total_withdrawal'] / $row['game_count'], 2);
				return $d == 0 ? '<span class="text-muted">' . $this->utils->formatCurrencyNoSym($d) . '</span>' : '<strong>' . $this->utils->formatCurrencyNoSym($d) . '</strong>';
			},
			'name' => lang('report.pr22'),
		);

		# start $game_table #######################################################################
		$columns[] = array(
			'alias' => 'total_player_game_minute_count',
			'select' => '(count(distinct '.$game_table.'.id) / count('.$game_table.'.id))',
			'formatter' => 'currencyFormatter',
		);

		$columns[] = array(
			'dt' => ($total_bets_col = $i++),
			'alias' => 'total_bets',
			'select' => 'sum('.$game_table.'.betting_amount)',
			'formatter' => function($d, $row) {
                if($row['transactions_count_2'] == 0){
                    $row['transactions_count_2'] = 1;
                }
                $d = round($d / $row['transactions_count_2'], 2);
				return $d == 0 ? '<span class="text-muted">' . $this->utils->formatCurrencyNoSym($d) . '</span>' : '<strong>' . $this->utils->formatCurrencyNoSym($d) . '</strong>';
			},
			'name' => lang('Total Bets'),
		);
		$columns[] = array(
			'dt' => ($total_bets_col = $i++),
			'alias' => 'bet_plus_result',
			'select' => 'sum('.$game_table.'.betting_amount) + sum('.$game_table.'.loss_amount) - sum('.$game_table.'.win_amount)',
			'formatter' => 'currencyFormatter',
			'name' => lang('Bet Result'),
		);

		if ( ! $this->utils->isEnabledFeature('hide_total_win_loss_on_aff_player_report')) {

			$columns[] = array(
				'dt' => ($total_wins_col = $i++),
				'alias' => 'total_wins',
				'select' => 'sum('.$game_table.'.win_amount)',
				'formatter' => function($d, $row) {
                    if($row['transactions_count_2'] == 0){
                        $row['transactions_count_2'] = 1;
                    }
					$d = round($d / $row['transactions_count_2'], 2);
					return $d == 0 ? '<span class="text-muted">' . $this->utils->formatCurrencyNoSym($d) . '</span>' : '<strong>' . $this->utils->formatCurrencyNoSym($d) . '</strong>';
				},
				'name' => lang('Total Wins'),
			);

			$columns[] = array(
				'dt' => ($total_loss_col = $i++),
				'alias' => 'total_loss',
				'select' => 'sum('.$game_table.'.loss_amount)',
				'formatter' => function($d, $row) {
                    if($row['transactions_count_2'] == 0){
                        $row['transactions_count_2'] = 1;
                    }
                    $d = round($d / $row['transactions_count_2'], 2);
					return $d == 0 ? '<span class="text-muted">' . $this->utils->formatCurrencyNoSym($d) . '</span>' : '<strong>' . $this->utils->formatCurrencyNoSym($d) . '</strong>';
				},
				'name' => lang('Total Loss'),
			);

		}

		$columns[] = array(
			'dt' => ($net_gaming_col = $i++),
			'alias' => 'net_gaming',
			'select' => 'sum('.$game_table.'.loss_amount) - sum('.$game_table.'.win_amount)',
			'formatter' => function($d, $row) {
                if($row['transactions_count_2'] == 0){
                    $row['transactions_count_2'] = 1;
                }
                $d = round($d / $row['transactions_count_2'], 2);
				return $d == 0 ? '<span class="text-muted">' . $this->utils->formatCurrencyNoSym($d) . '</span>' : '<strong>' . $this->utils->formatCurrencyNoSym($d) . '</strong>';
			},
			'name' => lang('Net Gaming'),
		);

		# end total_player_game_minute #######################################################################

		# FILTER ######################################################################################################################################################################################
		$joins['affiliates'] = 'affiliates.affiliateId = player.affiliateId';
		$joins['playerdetails'] = 'playerdetails.playerId = player.playerId';
		$joins['player_ip_last_request'] = 'player_ip_last_request.player_id = player.playerId';


		$transactions_sql = 'transactions.status = '.Transactions::APPROVED.' AND transactions.to_type = '.Transactions::PLAYER.' and transactions.to_id = player.playerId ';

		$dateTimeFrom = null; $dateTimeTo = null;
		$game_join = ' '.$game_table.'.player_id = player.playerId ';

        $enable_tier = false;
        $commonSettings = $this->affiliatemodel->getDefaultAffSettings();
        if(isset($commonSettings['enable_commission_by_tier']) && $commonSettings['enable_commission_by_tier'] == true){
            $enable_tier = true;
        }
        if($this->utils->isEnabledFeature('enable_exclude_platforms_in_player_report') && $enable_tier){
            $game_join .= 'AND '.$game_table.".game_platform_id IN (" . implode(',', $commonSettings['tier_provider']) . ")";
        }
 		if (isset($input['search_on_date']) && $input['search_on_date']) {
 			if (isset($input['date_from'], $input['date_to'])) {
 				$dateTimeFrom = $input['date_from'];
 				$dateTimeTo = $input['date_to'];

 				$dateTimeFrom=(new DateTime($dateTimeFrom))->format('Y-m-d H:i:s');
 				$dateTimeTo=(new DateTime($dateTimeTo))->format('Y-m-d H:i:s');

 				$transactions_sql .= " AND transactions.created_at >='".$dateTimeFrom."' AND transactions.created_at<='".$dateTimeTo."' ";

 				if($use_total_hour){

					$from= (new DateTime($dateTimeFrom))->format('YmdH');
					$to= (new DateTime($dateTimeTo))->format('YmdH');
					$game_join.= " and ".$game_table.".date_hour >='".$from."' and ".$game_table.".date_hour<='".$to."' ";

				}else{

					$from= (new DateTime($dateTimeFrom))->format('Y-m-d');
					$to= (new DateTime($dateTimeTo))->format('Y-m-d');
					$game_join.= " and ".$game_table.".date >='".$from."' and ".$game_table.".date <='".$to."' ";

				}
			}
		}

		$joins['transactions'] = $transactions_sql;

		$joins[$game_table] = $game_join;

		$where[] = "player.affiliateId IS NOT NULL";

		if (isset($input['group_by'])) {
			$group_by[] = $input['group_by'];
		}else{
            $group_by[] = 'player.playerId';
        }

		if (isset($input['username'])) {
			$where[] = "player.username LIKE ?";
			$values[] = '%' . $input['username'] . '%';
		}

		if (isset($input['realname'])) {
			$where[] = "CONCAT_WS('', playerdetails.firstName,playerdetails.lastName) = ?";
			$values[] = str_replace(' ', '', $input['realname']);
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

        if($only_show_non_zero_player){
            $having['(total_deposit > 0 OR total_withdrawal > 0 OR total_bets > 0 OR total_wins > 0 OR total_loss > 0 )'] = null;
        }
        if($this->utils->isEnabledFeature('enable_exclude_platforms_in_player_report') && $enable_tier){
            $having['game_count >'] = "0";
        }
		$search_affiliate_id = $affiliate_id;
		if (isset($input['affiliate_username']) && ! empty($input['affiliate_username'])) {
			$search_affiliate_id = $this->affiliatemodel->getAffiliateIdByUsername($input['affiliate_username']);
			if ( ! in_array($search_affiliate_id, $affiliate_downline_ids)) {
				$search_affiliate_id = 0;
			}
		}

		if ($search_affiliate_id && isset($input['include_all_downlines']) && $input['include_all_downlines'] == true) {
			if ($search_affiliate_id != $affiliate_id) {
				$affiliate_downline_ids = $this->affiliatemodel->includeAllDownlineAffiliateIds($search_affiliate_id);
			}
			$where[]  = "player.affiliateId IN (" . implode(',', $affiliate_downline_ids) . ")";
		} else {
			$where[]  = "player.affiliateId = ?";
			$values[] = $search_affiliate_id;
		}

		$this->utils->debug_log('GET_AFFILIATE_PLAYER_REPORTS where values', $where, $values);

		# OUTPUT ######################################################################################################################################################################################
		$this->benchmark->mark('data_start');
		$distinct=true;
		$external_order=[];
		$not_datatable='';
		$countOnlyField='player.playerId';
		$result  = $this->data_tables->get_data($request, $columns, $table, $where, $values, $joins, $group_by, $having,
			$distinct, $external_order, $not_datatable, $countOnlyField);
		$this->benchmark->mark('data_end');

		$temp_joins = $joins;
		unset($temp_joins['transactions']);
		$this->benchmark->mark('games_start');
		$game_summary = $this->data_tables->summary($request, $table, $temp_joins,
			 'round(sum('.$game_table.'.betting_amount), 2) as bet_total '.
			 ', round(sum('.$game_table.'.win_amount), 2) as win_total '.
			 ', round(sum('.$game_table.'.betting_amount) + sum('.$game_table.'.loss_amount) - sum('.$game_table.'.win_amount), 2) as bet_plus_result_total '.
			 ', round(sum('.$game_table.'.loss_amount), 2) as loss_total'
			, null, $columns, $where, $values);
		$this->benchmark->mark('games_end');
        if($this->utils->isEnabledFeature('enable_exclude_platforms_in_player_report') && $enable_tier) {
            $where[] = "EXISTS (SELECT 1 FROM {$game_table} WHERE player.playerId = {$game_table}.player_id)";
        }

        $temp_joins = $joins;
        unset($temp_joins[$game_table]);
        $this->benchmark->mark('transactions_start');
        $transaction_summary = $this->data_tables->summary($request, $table, $temp_joins,
            'round(SUM(CASE transactions.transaction_type WHEN '.Transactions::AUTO_ADD_CASHBACK_TO_BALANCE.' THEN transactions.amount WHEN '.Transactions::CASHBACK.' THEN transactions.amount ELSE 0 END), 2) cashback_total'.
            ', round(SUM(CASE transactions.transaction_type WHEN '.Transactions::ADD_BONUS.' THEN transactions.amount WHEN '.Transactions::MEMBER_GROUP_DEPOSIT_BONUS.' THEN transactions.amount WHEN '.Transactions::PLAYER_REFER_BONUS.' THEN transactions.amount WHEN '.Transactions::SUBTRACT_BONUS.' THEN -transactions.amount ELSE 0 END), 2) bonus_total'.
            ', round(SUM(IF(transactions.transaction_type='.Transactions::DEPOSIT.',transactions.amount,0)), 2) deposit_total'.
            ', round(SUM(IF(transactions.transaction_type='.Transactions::WITHDRAWAL.',transactions.amount,0)), 2) withdrawal_total'
            , null, $columns, $where, $values);
        $this->benchmark->mark('transactions_end');

		$result['summary'][0]['cashback_total'] 			= round($transaction_summary[0]['cashback_total'], 2);
		$result['summary'][0]['bonus_total'] 				= round($transaction_summary[0]['bonus_total'], 2);
		$result['summary'][0]['deposit_total'] 				= round($transaction_summary[0]['deposit_total'], 2);
		$result['summary'][0]['withdrawal_total'] 			= round($transaction_summary[0]['withdrawal_total'], 2);
		$result['summary'][0]['deposit_withdrawal_total'] 	= round($result['summary'][0]['deposit_total'] - $result['summary'][0]['withdrawal_total'], 2);
		$result['summary'][0]['bet_total'] 					= round($game_summary[0]['bet_total'], 2);
		$result['summary'][0]['win_total'] 					= round($game_summary[0]['win_total'], 2);
		$result['summary'][0]['loss_total'] 				= round($game_summary[0]['loss_total'], 2);
		$result['summary'][0]['net_total'] 					= round($result['summary'][0]['loss_total'] - $result['summary'][0]['win_total'], 2);

		$result['data_time'] 		 = $this->benchmark->elapsed_time('data_start', 'data_end');
		$result['transactions_time'] = $this->benchmark->elapsed_time('transactions_start', 'transactions_end');
		$result['games_time'] 		 = $this->benchmark->elapsed_time('games_start', 'games_end');

		return $result;
	}

	public function get_subaffiliate_reports($affiliate_id, $request, $viewPlayerInfoPerm, $is_export = false) {
		# Logged out
		if (empty($affiliate_id)) {
			return '';
		}

		$readOnlyDB = $this->getReadOnlyDB();

		$this->load->library('data_tables', array("DB" => $readOnlyDB));
		$this->load->model(array('transactions','game_logs','affiliatemodel'));

		$this->data_tables->is_export = $is_export;

		$input = $this->data_tables->extra_search($request);
		$affiliate_downline_ids = $this->affiliatemodel->includeAllDownlineAffiliateIds($affiliate_id);
		$affiliate_downline_ids = array_diff( $affiliate_downline_ids, [$affiliate_id] ); # DO NOT INCLUDE CURRENT AFFILIATE

		//use total day if > 1 day
		//use total hour if <= 1 day
		$use_total_hour=true;
		if (isset($input['search_on_date']) && $input['search_on_date'] == 1) {
			if (isset($input['date_from'], $input['date_to'])) {
				$from = strtotime($input['date_from']);
				$to = strtotime($input['date_to']);
				$one_day = 3600 * 24;
				$use_total_hour = ($to - $from) <= $one_day;
			}
		}

		$game_table = 'total_player_game_hour';
		if ($use_total_hour) {
			$game_table = 'total_player_game_hour';
		} else {
			$game_table = 'total_player_game_day';

		}

		$this->utils->debug_log('use_total_hour', $use_total_hour, $game_table, @$input['date_from'], @$input['date_to']);

		$i 			= 0;
		$table 		= 'affiliates';
		$joins 		= array();
		$where 		= array();
		$values 	= array();
		$group_by 	= array();
		$having 	= array();
		$start 		= $request['start'] + 1;

		# DEFINE TABLE COLUMNS ########################################################################################################################################################################
		$columns = array();

		$columns[] = array(
			'dt' => $i++,
			'alias' => 'username',
			'select' => 'affiliates.username',
			'name' => lang('report.pr01'),
			'formatter' => function ($d, $row) {

				if ($this->utils->isEnabledFeature('masked_affiliate_username_on_affiliate')) {
					$d = $this->utils->keepOnlyString($d, 4);
				}

				return $d;

			},
		);

		$columns[] = array(
			'dt' => $i++,
			'alias' => 'total_players',
			'select' => 'COUNT(distinct player.playerId)',
			'name' => lang('Total Players'),
		);

		# start transactions #######################################################################

		/*$columns[] = array(
			'alias' => 'transactions_count',
			'select' => '(count(distinct transactions.id) / count(transactions.id))',
			'formatter' => 'currencyFormatter',
		);*/

		$columns[] = array(
			'dt' => $i++,
			'alias' => 'deposited_players',
			'select' => 'COUNT(DISTINCT IF(transactions.transaction_type=' . Transactions::DEPOSIT . ',player.playerId,NULL))',
			'name' => lang('Deposited Players'),
		);

		$columns[] = array(
			'dt' => $i++,
			'alias' => 'total_deposit',
			'select' => 'SUM(IF(transactions.transaction_type=' . Transactions::DEPOSIT . ',transactions.amount,0))',
			'formatter' => function($d, $row) {
				//return round($d * $row['transactions_count'], 2);
				return round($d, 2);
			},
			'name' => lang('report.pr21'),
		);

		$columns[] = array(
			'dt' => $i++,
			'alias' => 'total_withdrawal',
			'select' => 'SUM(IF(transactions.transaction_type=' . Transactions::WITHDRAWAL . ',transactions.amount,0))',
			'formatter' => function($d, $row) {
				//return round($d * $row['transactions_count'], 2);
				return round($d, 2);
			},
			'name' => lang('report.pr22'),
		);

		$columns[] = array(
			'dt' => $i++,
			'alias' => 'total_bonus',
			'select' => 'SUM(CASE transactions.transaction_type WHEN '.Transactions::ADD_BONUS.' THEN transactions.amount WHEN '.Transactions::MEMBER_GROUP_DEPOSIT_BONUS.' THEN transactions.amount WHEN '.Transactions::PLAYER_REFER_BONUS.' THEN transactions.amount WHEN '.Transactions::SUBTRACT_BONUS.' THEN -transactions.amount ELSE 0 END)',
			'formatter' => function($d, $row) {
				//return round($d * $row['transactions_count'], 2);
				return round($d, 2);
			},
			'name' => lang('report.pr21'),
		);

		$columns[] = array(
			'dt' => $i++,
			'alias' => 'total_cashback',
			'select' => 'SUM(CASE transactions.transaction_type WHEN ' . Transactions::AUTO_ADD_CASHBACK_TO_BALANCE . ' THEN transactions.amount WHEN '.Transactions::CASHBACK.' THEN transactions.amount ELSE 0 END)',
			'formatter' => function($d, $row) {
				//return round($d * $row['transactions_count'], 2);
				return round($d, 2);
			},
			'name' => lang('report.pr21'),
		);

		# start $game_table #######################################################################
		/*$columns[] = array(
			'alias' => 'total_player_game_minute_count',
			'select' => '(count(distinct '.$game_table.'.id) / count('.$game_table.'.id))',
			'formatter' => 'currencyFormatter',
		);*/

		$columns[] = array(
			'dt' => $i++,
			'alias' => 'total_loss',
			'select' => 'tgp.total_loss',
			'formatter' => function($d, $row) {
				//return round($d * $row['total_player_game_minute_count'], 2);
				return round($d, 2);
			},
			'name' => lang('Total Loss'),
		);

		$columns[] = array(
			'dt' => $i++,
			'alias' => 'total_wins',
			'select' => 'tgp.total_wins',
			'formatter' => function($d, $row) {
				//return round($d * $row['total_player_game_minute_count'], 2);
				return round($d, 2);
			},
			'name' => lang('Total Wins'),
		);

		# end total_player_game_minute #######################################################################

		# FILTER ######################################################################################################################################################################################
		$joins['player'] = 'player.affiliateId = affiliates.affiliateId';

		$transactions_sql = 'transactions.status = '.Transactions::APPROVED.' AND transactions.to_type = '.Transactions::PLAYER.' and transactions.to_id = player.playerId ';

		$dateTimeFrom = null;
		$dateTimeTo = null;
		$game_join = ' '.$game_table.'.player_id = player.playerId ';
 		if (isset($input['search_on_date']) && $input['search_on_date']) {
 			if (isset($input['date_from'], $input['date_to'])) {
 				$dateTimeFrom = $input['date_from'];
 				$dateTimeTo = $input['date_to'];

 				$dateTimeFrom = (new DateTime($dateTimeFrom))->format('Y-m-d H:i:s');
 				$dateTimeTo = (new DateTime($dateTimeTo))->format('Y-m-d H:i:s');

 				$transactions_sql .= " AND transactions.created_at >= '" . $dateTimeFrom . "' AND transactions.created_at <= '" . $dateTimeTo . "' ";

 				if ($use_total_hour) {

					$from = (new DateTime($dateTimeFrom))->format('YmdH');
					$to = (new DateTime($dateTimeTo))->format('YmdH');
					$game_join .= " and " . $game_table . ".date_hour >= '" . $from . "' and " . $game_table . ".date_hour <= '".$to."' ";

					$joins["(SELECT player_id, SUM(loss_amount) total_loss, SUM(win_amount) total_wins
					FROM $game_table
					WHERE date_hour >= '$from'
						AND date_hour <= '$to'
					GROUP BY player_id) tgp"] = "tgp.player_id = player.playerId";

				} else {

					$from = (new DateTime($dateTimeFrom))->format('Y-m-d');
					$to = (new DateTime($dateTimeTo))->format('Y-m-d');
					$game_join .= " and " . $game_table . ".date >= '" . $from . "' and " . $game_table . ".date <= '" . $to . "' ";

					$joins["(SELECT player_id, SUM(loss_amount) total_loss, SUM(win_amount) total_wins
					FROM $game_table
					WHERE date >= '$from'
						AND date <= '$to'
					GROUP BY player_id) tgp"] = "tgp.player_id = player.playerId";
				}
			}
		}else{
			$joins["(SELECT player_id, SUM(loss_amount) total_loss, SUM(win_amount) total_wins
					FROM $game_table
					GROUP BY player_id) tgp"] = "tgp.player_id = player.playerId";
		}



		$joins['transactions'] = $transactions_sql;

		//$joins[$game_table] = $game_join;

		$where[] = "affiliates.affiliateId IN (" . implode(',', $affiliate_downline_ids) . ")";

		$group_by[] = 'affiliates.affiliateId';

		$this->utils->debug_log('GET_AFFILIATE_PLAYER_REPORTS where values', $where, $values);

		# OUTPUT ######################################################################################################################################################################################
		$result  = $this->data_tables->get_data($request, $columns, $table, $where, $values, $joins, $group_by);

		return $result;
	}

	public function get_subaffiliate_earnings($affiliate_id, $request, $viewPlayerInfoPerm, $is_export = false) {
		if (empty($affiliate_id)) {
			return 0;
		}

		$readOnlyDB = $this->getReadOnlyDB();
		$this->load->library('data_tables', array("DB" => $readOnlyDB));
		$this->data_tables->is_export = $is_export;

		$input = $this->data_tables->extra_search($request);
		$affiliate_downline_ids = $this->affiliatemodel->includeAllDownlineAffiliateIds($affiliate_id);
		$affiliate_downline_ids = array_diff( $affiliate_downline_ids, [$affiliate_id] ); # DO NOT INCLUDE CURRENT AFFILIATE
        if (empty($affiliate_downline_ids)) {
            return 0;
        }

        $this->load->model('affiliatemodel');
        $commonSettings = $this->affiliatemodel->getDefaultAffSettings();
        $enable_tier = false;
        if(isset($commonSettings['enable_commission_by_tier']) && $commonSettings['enable_commission_by_tier'] == true){
            $enable_tier = true;
        }

		$i 			= 0;
		$table 		= 'aff_monthly_earnings';
		$joins 		= array();
		$where 		= array();
		$values 	= array();

		# DEFINE TABLE COLUMNS ########################################################################################################################################################################

		$columns[] = array(
			'dt' => $i++,
			'alias' => 'username',
			'select' => 'affiliates.username',
			'name' => lang('report.pr01'),
			'formatter' => function ($d) {
				if ($this->utils->isEnabledFeature('masked_affiliate_username_on_affiliate')) {
					$d = $this->utils->keepOnlyString($d, 4);
				}
				return $d;
			},
		);
		$columns[] = array(
			'dt' => $i++,
			'select' => 'total_players',
			'name' => lang('Total Players'),
		);
		$columns[] = array(
			'dt' => $i++,
			'select' => 'active_players',
			'name' => lang('Active Members'),
		);
		$columns[] = array(
            'dt' => $i++,
			'select' => 'total_net_revenue',
			'formatter' => 'currencyFormatter',
            'name' => lang('Net Revenue'),
		);
		$columns[] = array(
			'dt' => $i++,
			'select' => ($enable_tier) ? 'commission_amount_by_tier' : 'commission_amount',
			'name' => lang('Commission Amount'),
            'formatter' => 'currencyFormatter',
		);

        if (isset($input['search_on_date']) && $input['search_on_date'] == 1) {
            if (isset($input['year_month'])) {
                 	$where[] = "year_month = ?";
                 	$values[] = $input['year_month'];
            }
        }

		# FILTER ######################################################################################################################################################################################
		$joins['affiliates'] = 'aff_monthly_earnings.affiliate_id = affiliates.affiliateId';
		$where[] = "aff_monthly_earnings.affiliate_id IN (" . implode(',', $affiliate_downline_ids) . ")";
        $group_by = [];

		# OUTPUT ######################################################################################################################################################################################
		$result  = $this->data_tables->get_data($request, $columns, $table, $where, $values, $joins, $group_by);

		return $result;
	}

	/**
	 * @param int $affiliate_id
	 * @param array $request
	 * @param array $viewPlayerInfoPerm
	 * @param bool $is_export
	 * @return array
	 */
	public function get_affiliate_game_history($affiliate_id, $request, $viewPlayerInfoPerm, $is_export = false) {
		# Logged out
		if(empty($affiliate_id)) {
			return '';
		}

		$this->load->model(array('game_logs','affiliatemodel'));
		# START DEFINE COLUMNS #####################################################################
		$show_bet_detail_on_game_logs = $this->utils->isEnabledFeature('show_bet_detail_on_game_logs');

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

					if ($this->utils->isAffSubProject() && $this->utils->isEnabledFeature('masked_player_username_on_affiliate')) {
						$d = $this->utils->keepOnlyString($d, 4);
					}

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

					if ($this->utils->isEnabledFeature('masked_realname_on_affiliate')) {
						$d = $this->utils->keepOnlyString($d, 4);
					}

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

					if ($this->utils->isEnabledFeature('masked_affiliate_username_on_affiliate')) {
						$d = $this->utils->keepOnlyString($d, 4);
					}

					if ($d != '') {
						return sprintf('%s', $d, $d);
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
						}
						return $this->utils->formatCurrencyNoSym($d);
					}
				},
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
						return $this->utils->formatCurrencyNoSym($d);
					}
				},
			),
			array(
				'dt' => $i++,
				'alias' => 'result_amount',
				'select' => 'game_logs.result_amount',
				'name' => lang('mark.resultAmount'),
				'formatter' => function ($d, $row) use ($is_export) {
					if($this->utils->is_readonly()){
						return 'N/A';
					}
					if ($row['flag'] == Game_logs::FLAG_TRANSACTION) {
						return lang('N/A');
					} else {
						return $this->utils->formatCurrencyNoSym($d);
					}
				},
			),
			array(
				'dt' => $i++,
				'alias' => 'bet_plus_result_amount',
				'select' => 'game_logs.bet_amount + game_logs.result_amount',
				'name' => lang('lang.bet.plus.result'),
				'formatter' => function ($d, $row) {
					if($this->utils->is_readonly()){
						return 'N/A';
					}
					if ($row['flag'] == Game_logs::FLAG_TRANSACTION) {
						return lang('N/A');
					} else {
						return $this->utils->formatCurrencyNoSym($d);
					}
				},
			),
			array(
				'dt' => $i++,
				'alias' => 'win_amount',
				'select' => 'game_logs.win_amount',
				'name' => lang('Win Amount'),
				'formatter' => function ($d, $row) {
					if($this->utils->is_readonly()){
						return 'N/A';
					}
					if ($row['flag'] == Game_logs::FLAG_TRANSACTION) {
						return lang('N/A');
					} else {
						return $this->utils->formatCurrencyNoSym($d);
					}
				},
			),
			array(
				'dt' => $i++,
				'alias' => 'loss_amount',
				'select' => 'game_logs.loss_amount',
				'name' => lang('Loss Amount'),
				'formatter' => function ($d, $row) {
					if($this->utils->is_readonly()){
						return 'N/A';
					}
					if ($row['flag'] == Game_logs::FLAG_TRANSACTION) {
						return lang('N/A');
					} else {
						return $this->utils->formatCurrencyNoSym($d);
					}
				},
			),
			array(
				'dt' => $i++,
				'alias' => 'after_balance',
				'select' => 'game_logs.after_balance',
				'name' => lang('mark.afterBalance'),
				'formatter' => function($d, $row) use ($is_export){
					if($this->utils->is_readonly()){
						return 'N/A';
					}
					if ( $is_export ) {
						return $this->utils->formatCurrencyNoSym($d);
					} else {
						return $d == 0 ? '<span class="text-muted">' . $this->utils->formatCurrencyNoSym($d) . '</span>' : '<strong>' . $this->utils->formatCurrencyNoSym($d) . '</strong>';
					}
				},
			),
			array(
				'dt' => $i++,
				'alias' => 'trans_amount',
				'select' => 'game_logs.trans_amount',
				'name' => lang('pay.transamount'),
				'formatter' => function ($d, $row) {
					if($this->utils->is_readonly()){
						return 'N/A';
					}
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
					if($this->utils->is_readonly()){
						return 'N/A';
					}
					if (!empty($d)) {
						$data = json_decode($d, true);
						$betDetailLink = "";
						$platform_id = (int)$row['game_platform_id'];
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
														$bet_list .=  lang($detail_key) . ":" . $bets . ", ";
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
				'name' => lang('Bet Type'),
				'formatter' => function ($d, $row) use($is_export, $i) {
					if( ! $is_export && (strpos(strtolower($d), 'single') != 'single' && !empty($d))){
						$unique_id = $row['unique_id'];
						$bets = json_decode($row['betDetails'], true);

						$count = 0;
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
			)
		);
		# END DEFINE COLUMNS #######################################################################

		$table = 'game_logs use index(idx_end_at)';
		$joins = array(
			'player' => 'player.playerId = game_logs.player_id',
			'playerdetails' => 'playerdetails.playerId = player.playerId',
			'affiliates' => 'affiliates.affiliateId = player.affiliateId',
			'game_description' => 'game_description.id = game_logs.game_description_id',
			'game_type' => 'game_type.id = game_description.game_type_id',
			'external_system' => 'game_logs.game_platform_id = external_system.id',
		);

        $left_joins_to_use_on_summary = array('player');


		# START PROCESS SEARCH FORM ################################################################
		$where = array();
		$values = array();
		$request = $this->input->post();
		$input = $this->data_tables->extra_search($request);
        // $this->utils->debug_log('AGENCY_GAME_HISTORY request111', $request);

		// -- Validate date range if config was set
		if ($this->utils->getConfig('affiliate_game_logs_report_date_range_restriction')) {
			// -- If date range is empty || does not exist from the request, immediately return empty result.
			if (!isset($input['by_date_from']) || !isset($input['by_date_to']) ||  trim($input['by_date_from']) == '' || trim($input['by_date_to']) == '') {

				$result = $this->data_tables->empty_data($request);
				$result['header_data'] = $this->data_tables->get_columns($columns);
				$result['summary'] = array(array("real_total_bet"=>0,"total_ave_bet"=>0,"total_bet"=>0,"total_bet_result"=>0,"total_count_bet"=>0,"total_loss"=>0,"total_result"=>0,"total_win"=>0));

				return $result;
			}

			// -- Check date range if within the provided restriction

			$date_diff = date_diff(date_create($input['by_date_to']), date_create($input['by_date_from']));
			$restriction = $this->utils->getConfig('affiliate_game_logs_report_date_range_restriction') - 1;

			if($date_diff->format('%a') >  $restriction){
				$result = $this->data_tables->empty_data($request);
				$result['header_data'] = $this->data_tables->get_columns($columns);
				$result['summary'] = array(array("real_total_bet"=>0,"total_ave_bet"=>0,"total_bet"=>0,"total_bet_result"=>0,"total_count_bet"=>0,"total_loss"=>0,"total_result"=>0,"total_win"=>0));

				return $result;
			}
		}


		$where[] = "player.playerId IS NOT NULL";

		$search_affiliate_id = $affiliate_id;

		$downlines = $this->affiliatemodel->includeAllDownlineAffiliateIds($affiliate_id);

		// Convert aff username to aff_id
		if (isset($input['affiliate_username'])) {
			$search_affiliate_id = $this->affiliatemodel->getAffiliateIdByUsername($input['affiliate_username']);
		}

		// if aff_id empty, do not go down with aff search
		if (empty($search_affiliate_id) || !in_array($search_affiliate_id, $downlines)) {
			// search for an impossible aff_id (ensure returning nothing)
			// OGP-12294: Use -32767 instead of 0, because ole777cn non-affiliated players have affiliateId == 0 (should be NULL)
			$where[] = "player.affiliateId = ?";
			$values[] = -32767;
		}
		else {
			if (isset($input['include_all_downlines'])) {

				if ($search_affiliate_id != $affiliate_id) {
					$downlines = $this->affiliatemodel->includeAllDownlineAffiliateIds($search_affiliate_id);
				}

	        	$where[] = "player.affiliateId IN (" . implode(',', $downlines) . ")";

			} else {
		        $where[] = "player.affiliateId = ?";
				$values[] = $search_affiliate_id;
			}
		}

        if (isset($input['by_game_platform_id'])) {
			$where[] = "game_logs.game_platform_id = ?";
			$values[] = $input['by_game_platform_id'];
		}

		if (isset($input['by_game_flag'])) {
			$where[] = "game_logs.flag = ?";
			$values[] = $input['by_game_flag'];
		}

		if (isset($input['by_date_from'], $input['by_date_to'])) {
			$where[] = "game_logs.end_at BETWEEN ? AND ?";
			$values[] = $input['by_date_from'];
			$values[] = $input['by_date_to'];
		}

		if (isset($input['player_id'])) {
			$where[] = "player.playerId = ?";
			$values[] = $input['player_id'];
		}

		if (isset($input['by_username'])) {
			if ($this->utils->is_readonly()) {
				$where[] = "player.username = ?";
				$values[] = $input['by_username'];
			} else {
				$where[] = "player.username LIKE ?";
				$values[] = '%' . $input['by_username'] . '%';
			}
		}

		if (isset($input['by_realname'])) {
			$where[] = "CONCAT_WS('', playerdetails.firstName,playerdetails.lastName) = ?";
			$values[] = str_replace(' ', '', $input['by_realname']);
            array_push($left_joins_to_use_on_summary, 'playerdetails');
		}

		if (isset($input['game_description_id'])) {
			$where[] = "game_description.id = ?";
			$values[] = $input['game_description_id'];
            array_push($left_joins_to_use_on_summary, 'game_description');
		}

		if (isset($input['by_group_level'])) {
			$where[] = "player.levelId  = ?";
			$values[] = $input['by_group_level'];
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
		$distinct=false;
		$group_by=[];
		$having=[];
		$external_order=[];
		$not_datatable='';
		$countOnlyField='game_logs.id';
		$result = $this->data_tables->get_data($request, $columns, $table, $where, $values, $joins, $group_by, $having,
			$distinct, $external_order, $not_datatable, $countOnlyField);

		// -- remove unecessary joins from total / summary queries
        foreach ($joins as $join_key => &$join_value) {
            if(!in_array($join_key, $left_joins_to_use_on_summary))
                unset($joins[$join_key]);
        }

		$summary = $this->data_tables->summary($request, $table, $joins, 'SUM(if(game_logs.flag="1", trans_amount, 0 )) real_total_bet, SUM(bet_amount) total_bet, SUM(result_amount) total_result, SUM(bet_amount + result_amount) total_bet_result, SUM(win_amount) total_win, SUM(loss_amount) total_loss, SUM(IF(game_logs.flag = "1", bet_amount, 0))/ SUM(IF(game_logs.flag = 1, 1, 0)) total_ave_bet, SUM(IF(game_logs.flag = 1, 1, 0)) total_count_bet', null, $columns, $where, $values);

		array_walk($summary[0], function (&$value) {
			$value = round(floatval($value), 2);
		});

		$result['summary'] = $summary;
		return $result;
	}

	public function get_affiliate_credit_transactions($affiliate_id, $request, $viewPlayerInfoPerm, $is_export = false) {
		# Logged out
		if(empty($affiliate_id)) {
			return '';
		}

        $this->load->library(array('data_tables'));
        $this->load->model(array('transactions','affiliatemodel'));

		$isHidePlayerInfoOnAff = $this->utils->isHidePlayerInfoOnAff();

        $input 	= $this->data_tables->extra_search($request);
        $table 	= 'transactions';
        $joins 	= array();
        $where 	= array();
        $values = array();
        $distinct=false;

        # DEFINE TABLE COLUMNS #####################################################################
        $i = 0;
        $columns = array(
            array(
            	'select' =>'player.playerId',
            	'alias' => 'playerId',
            ),
            array(
            	'select' =>'transactions.from_type',
            	'alias' => 'from_type',
            ),
            array(
            	'select' =>'transactions.from_id',
            	'alias' => 'from_id',
            ),
            array(
            	'select' =>'transactions.from_username',
            	'alias' => 'from_username',
            ),
            array(
            	'select' =>'transactions.to_type',
            	'alias' => 'to_type',
            ),
            array(
            	'select' =>'transactions.to_id',
            	'alias' => 'to_id',
            ),
            array(
            	'select' =>'transactions.to_username',
            	'alias' => 'to_username',
            ),
            array(
                'dt' => $i++,
                'select' =>'transactions.created_at',
            	'alias' => 'created_at',
                'name' => lang('Date')
            ),
            array(
                'dt' => $i++,
                'select' =>'transactions.transaction_type',
            	'alias' => 'transaction_type',
                'name' => lang('Transaction Type'),
                'formatter' => function($d, $row) {
                	return lang('transaction.transaction.type.' . $d);
                }
            ),
            array(
                'dt' => $i++,
                'select' =>'transactions.sub_wallet_id',
            	'alias' => 'sub_wallet_id',
                'name' => lang('Wallet'),
                'formatter' => function($d, $row) {
                	return $d ? lang('Subwallet') : lang('Main Wallet');
                }
            ),
            array(
                'dt' => $i++,
                'select' => 'player.username',
            	'alias' => 'player',
                'name' => lang('Player'),
				'formatter' => function ($d, $row) use ($isHidePlayerInfoOnAff) {

					if ($this->utils->isAffSubProject() && $this->utils->isEnabledFeature('masked_player_username_on_affiliate')) {
						$d = $this->utils->keepOnlyString($d, 4);
					}

					if ($isHidePlayerInfoOnAff) {
						return $d ? $d : '<i class="text-muted">' . lang('N/A') . '</i>';
					} else {
						$url = site_url('/affiliate/viewPlayerById/' . $row['playerId']);
						return $d ? ('<a href="' . $url . '" target="_blank">' . $d . '</a>') : ('<i class="text-muted">' . lang('N/A') . '</i>');
					}
				},
            ),
            array(
                'dt' => $i++,
                'select' => 'transactions.amount',
            	'alias' => 'amount',
                'name' => lang('Amount'),
                'formatter' => function($d, $row) use ($is_export) {
                	if($this->utils->is_readonly()){
                		return 'N/A';
                	}

                    if ($is_export) {
                        return $this->utils->formatCurrencyNoSym($d);
                    } else {
                        return $d == 0 ? '<span class="text-muted">0.00</span>' : '<strong>' . $this->utils->formatCurrencyNoSym($d) . '</strong>';
                    }
                },
            ),
            array(
                'dt' => $i++,
                'select' => 'transactions.before_balance',
                'alias' => 'before_balance',
                'name' => lang('Before Balance'),
                'formatter' => function($d, $row) use ($is_export) {
                	if($this->utils->is_readonly()){
                		return 'N/A';
                	}

                    if ($is_export) {
                        return $this->utils->formatCurrencyNoSym($d);
                    } else {
                        return $d == 0 ? '<span class="text-muted">0.00</span>' : '<strong>' . $this->utils->formatCurrencyNoSym($d) . '</strong>';
                    }
                },
            ),
            array(
                'dt' => $i++,
                'select' => 'transactions.after_balance',
                'alias' => 'after_balance',
                'name' => lang('After Balance'),
                'formatter' => function($d, $row) use ($is_export) {
                	if($this->utils->is_readonly()){
                		return 'N/A';
                	}

                    if ($is_export) {
                        return $this->utils->formatCurrencyNoSym($d);
                    } else {
                        return $d == 0 ? '<span class="text-muted">0.00</span>' : '<strong>' . $this->utils->formatCurrencyNoSym($d) . '</strong>';
                    }
                },
            ),
        );

        $affiliate_ids = $this->affiliatemodel->includeAllDownlineAffiliateIds($affiliate_id);
        $joins['player'] = '1 = 1 AND ((player.playerId = transactions.from_id AND transactions.from_type = '.Transactions::PLAYER.') OR (player.playerId = transactions.to_id AND transactions.to_type = '.Transactions::PLAYER.'))';
        $where[] = "player.affiliateId IN (".implode(',', array_fill(0, count($affiliate_ids), '?')).")";
        $values = array_merge($values, $affiliate_ids);

        if (isset($input['affiliate_username']) && $input['affiliate_username'] != '') {
            $search_affiliate_id = $this->affiliatemodel->getAffiliateIdByUsername($input['affiliate_username']) ? : 0;
            $where[] = "player.affiliateId = ?";
            $values[] = $search_affiliate_id;
        }

        if (isset($input['search_on_date']) && $input['search_on_date']) {
            if (isset($input['date_from'], $input['date_to'])) {
                $where[] = "transactions.created_at BETWEEN ? AND ?";
                $values[] = $input['date_from'];
                $values[] = $input['date_to'];
            }
        }

        if (isset($input['transaction_type']) && ! empty($input['transaction_type'])) {
            if (is_array($input['transaction_type'])) {
	            $where[] = "transactions.transaction_type IN (".implode(',', array_fill(0, count($input['transaction_type']), '?')).")";
	            $values = array_merge($values,$input['transaction_type']);
            } else {
            	$where[] = "transactions.transaction_type = ?";
            	$values[] = $input['transaction_type'];
            }
        } else {
            $where[] = "transactions.transaction_type = 0";
        }

        if (isset($input['min_credit_amount'])) {
            $where[] = "transactions.amount >= ?";
            $values[] = $input['min_credit_amount'];
        }

        if (isset($input['max_credit_amount'])) {
            $where[] = "transactions.amount <= ?";
            $values[] = $input['max_credit_amount'];
        }

        if (isset($input['player_username']) && $input['player_username'] != '') {
            $where[] = "player.username = ?";
            $values[] = $input['player_username'];
        }

        // OGP-22222: Exclude declined transactions by default
        $where[] = "transactions.status != 2";

        # OUTPUT ###################################################################################
        $countOnlyField='transactions.id';
        $result = $this->data_tables->get_data($request, $columns, $table, $where, $values, $joins,[], [],
            $distinct, [], '', $countOnlyField);
        return $result;
    }

    public function affiliateTag($request, $is_export=false){

		$this->load->library('affiliate_manager');

		$sort = "tagId";
		$tags = $this->affiliate_manager->getAllTags($sort, null, null);

		$result = array();

		$result['header_data'] = array(
				lang('aff.t02'),
				lang('aff.t04'),
				lang('aff.t06')
			);

		$result['data'] = array();

		foreach ($tags as $key => $value) {
			$result['data'][] = array(
				'tagName' => $value['tagName'],
				'tagDescription' => $value['tagDescription'],
				'username' => $value['username'],
			);
		}

		return $result;
	}

	public function affiliatePayment($input){


		$this->load->model(array('affiliatemodel'));
		$search = array(
			"username" => $this->input->post('username'),
			"status" => $this->input->post('status'),
		);

		$data['input'] = $this->input->get();

		if ($this->input->post('start_date') && $this->input->post('end_date')) {
			$search['request_range'] = "'" . $this->input->post('start_date') . "' AND '" . $this->input->post('end_date') . "'";
		} else {
			$search['request_range'] = "'" . date("Y-m-d 00:00:00") . "' AND '" . date("Y-m-d 23:59:59") . "'";
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

			switch ( $row['status'] ) {
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
					'amount' => $value['amount'],
					'processedOn' => $value['processedOn'],
					'adminuser' => $value['adminuser'],
					'processedOn' => $value['processedOn'],
					'status' => $status,
					'reason' => $reason
				);
		}

		return $result;
	}

	public function aff_daily_earnings($request, $is_export) {

		$readOnlyDB = $this->getReadOnlyDB();

		$this->load->library('data_tables', array('DB' => $readOnlyDB));
        $this->load->helper(['aff_helper']);
		$this->load->model(['affiliate_earnings', 'affiliatemodel']);

		$this->data_tables->is_export = $is_export;

		$input = $this->data_tables->extra_search($request);

		$min_amount = $this->affiliate_earnings->getMinimumPayAmountSetting();

		$i = 0;
		$columns = array(
            array(
                'select' =>'affiliates.affiliateId',
            	'alias' => 'affiliate_id',
            ),
            array(
                'dt' => $i++,
                'select' =>'aff_daily_earnings.id',
            	'alias' => 'id',
            	'formatter' => function ($d, $row) use ($is_export, $min_amount){
            		if ($is_export) {
						return '';
					} else {
					return $row['paid_flag'] == 0 && ($min_amount <= $row['total_commission'] || $row['total_commission'] < 0) ? '<input type="checkbox" class="batch-selected-cb user-success" id="selected_earnings_id" onClick="selectionValidate();" value="' . $d . '">': '';
					}
				},
            ),
            array(
                'dt' => $i++,
                'select' =>'aff_daily_earnings.id',
            	'alias' => 'id',
            	'formatter' => function ($d, $row) use ($is_export, $min_amount){
            		if ($is_export) {
						return '';
					} else {
					return $row['paid_flag'] == 0 && ($min_amount <= $row['total_commission'] || $row['total_commission'] < 0) ? '<a class="btn btn-xs btn-primary pay" href="javascript:void(0)" onclick="payOne(this)" data-earningid="' . $d . '"><i class="fa fa-paper-plane-o"></i>'.lang("Transfer to wallet").'</a>' : '';
					}
				},
            ),
            array(
                'dt' => $i++,
                'select' =>'aff_daily_earnings.date',
            	'alias' => 'date',
            	'name' => lang('Date'),
            ),
            array(
                'dt' => isset($input['affiliate_id']) ? NULL : $i++,
                'select' =>'affiliates.username',
            	'alias' => 'username',
            	'formatter' => function ($d, $row) use ($is_export) {
            		if ($is_export) {
						return ($d ? $d : lang('N/A'));
					} else {
					$url = site_url('/affiliate_management/userInformation/' . $row['affiliate_id']);
					return $d ? "<a href=\"{$url}\" target=\"_blank\">{$d}</a>" : ('<i class="text-muted">' . lang('N/A') . '</i>');
					}
				},
				'name' => lang('Affiliate Username'),
            ),
            array(
                'dt' => $i++,
                'alias' => 'tagName',
                'select' => 'affiliates.affiliateId',
                'name' => lang("player.41"),
                'formatter' => function ($d, $row) use ($is_export) {
                    return aff_tagged_list($row['affiliate_id'], $is_export);
                },
            ),
            array(
                'dt' => $i++,
                'select' =>'aff_daily_earnings.active_players',
            	'alias' => 'active_players',
            	'name' => lang('Active Players'),
            ),
            array(
                'dt' => $i++,
                'select' =>'aff_daily_earnings.total_players',
            	'alias' => 'total_players',
            	'name' => lang('Total Players'),
            ),
            array(
                'dt' => $i++,
                'select' =>'aff_daily_earnings.gross_revenue',
            	'alias' => 'gross_revenue',
				'formatter' => 'currencyFormatter',
				'name' => lang('Gross Revenue'),
            ),
            array(
                'dt' => $i++,
                'select' =>'aff_daily_earnings.platform_fee',
            	'alias' => 'platform_fee',
				'formatter' => 'currencyFormatter',
				'name' => lang('Platform Fee'),
            ),
            array(
                'dt' => $i++,
                'select' =>'aff_daily_earnings.bonus_fee',
            	'alias' => 'bonus_fee',
				'formatter' => 'currencyFormatter',
				'name' => lang('Bonus Fee'),
            ),
            array(
                'dt' => $i++,
                'select' =>'aff_daily_earnings.cashback_fee',
            	'alias' => 'cashback_fee',
				'formatter' => 'currencyFormatter',
				'name' => lang('Cashback Fee'),
            ),
            array(
                'dt' => $i++,
                'select' =>'aff_daily_earnings.transaction_fee',
            	'alias' => 'transaction_fee',
				'formatter' => 'currencyFormatter',
				'name' => lang('Transaction Fee'),
            ),
            array(
                'dt' => $i++,
                'select' =>'aff_daily_earnings.admin_fee',
            	'alias' => 'admin_fee',
				'formatter' => 'currencyFormatter',
				'name' => lang('Admin Fee'),
            ),
            array(
                'dt' => $i++,
                'select' =>'aff_daily_earnings.total_fee',
            	'alias' => 'total_fee',
				'formatter' => 'currencyFormatter',
				'name' => lang('Total Fee'),
            ),
            array(
                'dt' => $i++,
                'select' =>'aff_daily_earnings.net_revenue',
            	'alias' => 'net_revenue',
				'formatter' => 'currencyFormatter',
				'name' => lang('Net Revenue'),
            ),
            array(
                'dt' => $i++,
                'select' =>'aff_daily_earnings.commission_percentage_breakdown',
            	'alias' => 'commission_percentage',
				'formatter' => function ($d, $row) use ($is_export) {
					$str = '';
					$commission_percentage_breakdown = json_decode($d, TRUE);

					if ($commission_percentage_breakdown) {

						$commission_percentages = array_unique(array_values($commission_percentage_breakdown));

						if (count($commission_percentages) == 1) return $commission_percentages[0] . '%';

						$str = '';
						foreach ($commission_percentage_breakdown as $platform_id => $commission_percentage) {

							if ($is_export)
								$str .= $this->external_system->getNameById($platform_id) . ": {$commission_percentage}%, ";
							else
								$str .= $this->external_system->getNameById($platform_id) . ": {$commission_percentage}%<br>";
						}
					}

					if(trim($str) == '')
						return $is_export ? lang('N/A') : '<i class="text-muted">'.lang('N/A').'</i>';

					return $str;

				},
				'name' => lang('Commission Rate'),
            ),
            array(
                'dt' => $i++,
                'select' =>'aff_daily_earnings.commission_amount',
            	'alias' => 'commission_amount',
				'formatter' => 'currencyFormatter',
				'name' => lang('Commission Amount'),
            ),
            array(
                'dt' => $i++,
                'select' =>'aff_daily_earnings.commission_from_sub_affiliates',
            	'alias' => 'commission_from_sub_affiliates',
				'formatter' => 'currencyFormatter',
				'name' => lang('Commission From Sub-affiliates'),
            ),
            array(
                'dt' => $i++,
                'select' =>'aff_daily_earnings.total_commission',
            	'alias' => 'total_commission',
				'formatter' => 'currencyFormatter',
				'name' => lang('Total Commission'),
            ),
            array(
                'dt' => $i++,
            	'alias' => 'manual_adjustment',
				'formatter' => function ($d, $row) use ($is_export) {
					$output = '';
					if ($is_export) {
						$output; '';
					} else {
					if($row['paid_flag'] == 0){
						$output .= '<a onclick="modal(\'/affiliate_management/affiliate_commision_manual_adjustment/'.$row['id'].'/'.$row['total_commission'].'\',\'' . lang('Manual Adjustment') . '\')" href="javascript:void(0);" data-toggle="tooltip" title="' . lang('Manual Adjustment') . '"><span class="glyphicon glyphicon-edit"></a> ';
					}
					return $output;
					}
				},

            ),
            array(
                'dt' => $i++,
                'select' =>'aff_daily_earnings.paid_flag',
            	'alias' => 'paid_flag',
				'formatter' => function ($d, $row) {
					return $d == 0 ? lang('Unpaid') : lang('Paid');
				},
				'name' => lang('Status'),
            ),
            array(
                'dt' => $i++,
                'select' =>'adminusers.username',
            	'alias' => 'updated_by',
				'formatter' => function ($d, $row) {
					return $d;
				},
                'name' => lang('Paid By'),
            ),
            array(
                'dt' => $i++,
                'select' =>'affiliates.affiliateId',
            	'alias' => 'affiliate_id',
            	'formatter' => function ($d, $row) use ($is_export) {
					if ($is_export) {
						return '';
					}
				},
            ),
		);

		# END DEFINE COLUMNS #################################################################################################################################################

		$table = 'aff_daily_earnings';
		$joins = array(
			'affiliates' => 'affiliates.affiliateId = aff_daily_earnings.affiliate_id',
			'adminusers' => 'aff_daily_earnings.updated_by = adminusers.userId',
		);

		# START PROCESS SEARCH FORM #################################################################################################################################################
		$where = array();
		$values = array();

		if (isset($input['affiliate_id'])) {
			$where[] = 'aff_daily_earnings.affiliate_id = ?';
			$values[] = $input['affiliate_id'];
		}

		if (isset($input['date'])) {
			$where[] = 'date = ?';
			$values[] = $input['date'];
		}

		if (isset($input['affiliate_username'])) {
			$where[] = 'affiliates.username = ?';
			$values[] = $input['affiliate_username'];
		}

        if (isset($input['tag_id'])) {
            if (is_array($input['tag_id'])) {
                $where[] = 'affiliates.affiliateId IN ( SELECT affiliateId FROM affiliatetag WHERE tagId IN ('.implode(',', $input['tag_id']).') )';
            } else {
                $where[] = 'affiliates.affiliateId IN ( SELECT affiliateId FROM affiliatetag WHERE tagId = ? )';
                $values[] = $input['tag_id'];
            }
        }

		if (isset($input['parent_affiliate'])) {
			$where[] = 'affiliates.parentId = ?';
			$values[] = $input['parent_affiliate'];
		}

		if (isset($input['paid_flag'])) {
			$where[] = 'aff_daily_earnings.paid_flag = ?';
			$values[] = $input['paid_flag'];
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

		if ($this->utils->isEnabledFeature('switch_to_ibetg_commission')) {
			$totalActivePlayer = $this->affiliate_earnings->getTotalActivePlayerSetting();
			//print_r($result);
			for ($c = 0; $c < count($result['data']); $c++) {
				$activeplayers = $result['data'][$c][4]; //active_players
				$yearmonth = $result['data'][$c][2]; //yearmonth
				$re = '';
				for($i = 1; $i <= 3; $i++) {
					$re .= $yearmonth . ',';
					$previousReport = $this->affiliate_earnings->getPreviousEarning($result['data'][$c][20], $yearmonth);
					if (!$previousReport)
						break;
					$activeplayers += (int)$previousReport->active_players;
					$yearmonth = $previousReport->year_month;
				}
				if ($activeplayers <= $totalActivePlayer) {
					$result['data'][$c][0] = '';
					$result['data'][$c][1] = '';
				}
			}
		}

		return $result;
	}

	public function aff_monthly_earnings($request, $is_export) {

		$readOnlyDB = $this->getReadOnlyDB();

		$this->load->library(['data_tables', 'permissions'], array('DB' => $readOnlyDB));
        $this->load->helper(['aff_helper']);
		$this->load->model(['affiliate_earnings','affiliatemodel']);

		$this->data_tables->is_export = $is_export;
		$commonSettings = $this->affiliatemodel->getDefaultAffSettings();

        $_affiliatemodel = $this->affiliatemodel;

		$allowed_adjust_player_benefit_fee = $this->permissions->checkPermissions('adjust_player_benefit_fee');
        $allowed_adjust_addon_affiliates_platform_fee = $this->permissions->checkPermissions('adjust_addon_affiliates_platform_fee');

		$input = $this->data_tables->extra_search($request);
		$affiliate_id = isset($input['affiliate_id']) ? $input['affiliate_id'] : NULL;

		$min_amount = $this->affiliate_earnings->getMinimumMonthlyPayAmountSetting();

        $enable_tier = false;
        if(isset($commonSettings['enable_commission_by_tier']) && $commonSettings['enable_commission_by_tier'] == true){
            $enable_tier = true;
        }

		$enforce_cashback = empty($this->utils->getConfig('enforce_cashback_target'))? 0: $this->utils->getConfig('enforce_cashback_target');

		$i = 0;
		$columns = array(
            array(
                'select' =>'affiliates.affiliateId',
            	'alias' => 'affiliate_id',
            ),
            array(
                'select' =>'affiliates.is_hide',
            	'alias' => 'is_hide',
            ),
            array(
                'select' =>'aff_monthly_earnings.commission_percentage',
            	'alias' => 'rate',
            ),
            array(
                'select' =>'aff_monthly_earnings.adjustment_notes',
            	'alias' => 'adjustment_notes',
            ),
            array(
                'dt' => isset($input['affiliate_id'])||$is_export ? NULL : $i++,
                'select' =>'aff_monthly_earnings.id',
            	'alias' => 'id',
                'name' => lang('ID'),
            	'formatter' => function ($d, $row) use ($min_amount, $is_export) {

            		$output = '';

            		if ($row['paid_flag'] == Affiliatemodel::DB_FALSE) {
        				$output = '<input type="checkbox" class="batch-selected-cb user-success" id="selected_earnings_id" onClick="selectionValidate();" value="' . $d . '">';
            		}
                    if($is_export){
                        return $d;
                    }

            		return $output;

				},
            ),
            array(
                'dt' => isset($input['affiliate_id'])||$is_export ? NULL : $i++,
                'select' =>'aff_monthly_earnings.id',
            	'alias' => 'id',
                'name' => lang('Action'),
            	'formatter' => function ($d, $row) use ($min_amount, $is_export) {

            		$output = '';

            		if ($row['paid_flag'] == Affiliatemodel::DB_FALSE) {

            			if ($row['total_commission'] == 0) {
            				$output = '<a class="btn btn-xs btn-success pay" href="javascript:void(0)" onclick="payOne(this)" data-earningid="' . $d . '"><i class="fa fa-check"></i> '.lang("Set to Reviewed").'</a>';
            			} else if ($min_amount <= $row['total_commission'] || $row['total_commission'] < 0) {
            				$output = '<a class="btn btn-xs btn-primary pay" href="javascript:void(0)" onclick="payOne(this)" data-earningid="' . $d . '"><i class="fa fa-paper-plane-o"></i> '.lang("Transfer to wallet").'</a>';
            			}

            		}

                    if(!$is_export){
                        return $output;
                    }

				},
            ),
            array(
                'dt' => $i++,
                'select' =>'aff_monthly_earnings.year_month',
            	'alias' => 'yearmonth',
                'name' => lang('Year month'),
            ),
            array(
                'dt' => isset($input['affiliate_id']) ? NULL : $i++,
                'select' =>'affiliates.username',
            	'alias' => 'username',
                'name' => lang('Affiliate Username'),
            	'formatter' => function ($d, $row) use($is_export, $_affiliatemodel) {

                    $is_hide = false;
                    if($row['is_hide'] == $_affiliatemodel::DB_TRUE){
                        $is_hide = true;
                    }

                    if($is_hide){
                        $d .= ' ('. lang('Hidden').')';
                    }
                    if($is_export){
                        return $d;
                    }

					$url = site_url('/affiliate_management/userInformation/' . $row['affiliate_id']);
                    $html_string = $d ? "<a href=\"{$url}\" target=\"_blank\">{$d}</a>" : ('<i class="text-muted">' . lang('N/A') . '</i>');

                    if($is_hide){
                        $html_string = '<i class="text-muted">' . $d . '</i>';
                    }

					return $html_string;
				},
            ),
            array(
                'dt' => isset($input['affiliate_id']) ? NULL : $i++,
                'alias' => 'tagName',
                'select' => 'affiliates.affiliateId',
                'name' => lang("player.41"),
                'formatter' => function ($d, $row) use ($is_export) {
                    return aff_tagged_list($row['affiliate_id'], $is_export);
                },
            ),
            array(
                'alias' => 'parent_aff_id',
                'select' => 'parent.affiliateId'
            ),
            array(
                'dt' => isset($input['affiliate_id']) ? NULL : $i++,
                'alias' => 'parent_aff',
                'select' => 'parent.username',
                'name' => lang('Parent Affiliate'),
                'formatter' => function ($d, $row) use ($is_export) {
                    if ($is_export) {
                        return ($d ? $d : lang('N/A'));
                    }
                    else {
                        $url = site_url('/affiliate_management/userInformation/' . $row['parent_aff_id']);
                        return $d ? '<a href="' . $url . '" target="_blank">' . $d . '</a>' : '<i class="text-muted">' . lang('N/A') . '</i>';
                    }
                },
            ),
            array(
                'dt' => $i++,
                'select' =>'aff_monthly_earnings.active_players',
            	'alias' => 'active_players',
                'name' => lang('Active Players'),
            ),
            array(
                'dt' => $i++,
                'select' =>'aff_monthly_earnings.total_players',
            	'alias' => 'total_players',
                'name' => lang('Total Players'),
            ),
            array(
                'dt' => $i++,
                'select' =>'aff_monthly_earnings.gross_revenue',
            	'alias' => 'gross_revenue',
				'formatter' => 'currencyFormatter',
                'name' => lang('Gross Revenue'),
            ),
			array(
                'dt' => $i++,
                'select' =>'aff_monthly_earnings.cashback_revenue',
            	'alias' => 'cashback_revenue',
				'formatter' => 'currencyFormatter',
                'name' => lang('Cashback Revenue'),
            ),
            array(
                'dt' => $i++,
                'select' =>'aff_monthly_earnings.platform_fee',
            	'alias' => 'platform_fee',
				'formatter' => 'currencyFormatter',
                'name' => lang('Platform Fee'),
            ),
            array(
                'dt' => $i++,
                'select' =>'aff_monthly_earnings.bonus_fee',
            	'alias' => 'bonus_fee',
				'formatter' => 'currencyFormatter',
                'name' => lang('Bonus Fee'),
            ),
            array(
                'dt' => $i++,
                'select' =>'aff_monthly_earnings.cashback_fee',
            	'alias' => 'cashback_fee',
				'formatter' => 'currencyFormatter',
                'name' => lang('Cashback Fee'),
            ),
            array(
                'dt' => $i++,
                'select' =>'aff_monthly_earnings.transaction_fee',
            	'alias' => 'transaction_fee',
				'formatter' => 'currencyFormatter',
                'name' => lang('Transaction Fee'),
            ),
            array(
                'dt' => $i++,
                'select' =>'aff_monthly_earnings.admin_fee',
            	'alias' => 'admin_fee',
				'formatter' => 'currencyFormatter',
                'name' => lang('Admin Fee'),
			),
			array(
				'dt' => $this->utils->isEnabledFeature('enable_player_benefit_fee') ? $i++ : NULL,
				'select' =>'affiliate_player_benefit_fee.player_benefit_fee',
				'alias' => 'player_benefit_fee',
				'name' => lang("Player's Benefit Fee"),
				'formatter' => function ($d, $row) use ($affiliate_id, $is_export, $allowed_adjust_player_benefit_fee) {
                    if ($is_export) {
                        return $d;
                    } else {

						if($allowed_adjust_player_benefit_fee && $row['paid_flag'] == 0) {

							$id = $row['id'];
							$yearmonth = $row['yearmonth'];
							$affiliate_id = $row['affiliate_id'];
							$id = $row['id'];


							$modal_url = "/affiliate_management/affiliate_player_benefit_fee_adjustment/$id/$yearmonth/$affiliate_id";
							$modal_tiltle = lang("Update Player\'s Benefit Fee");
							$format_d = $this->utils->formatCurrencyNoSym($d);
							$btn = "<a href=\"#\" onClick=\"return modal('$modal_url','$modal_tiltle');\">$format_d</a>";
							return $btn;
						} else {

							return $this->utils->formatCurrencyNoSym($d);
						}
					}

				},
			),
			array(
				'dt' => $this->utils->isEnabledFeature('enable_addon_affiliate_platform_fee') ? $i++ : NULL,
				'select' =>'addon_affiliate_platform_fee.platform_fee',
				'alias' => 'addon_platform_fee',
				'name' => lang("Addon Platform Fee"),
				'formatter' => function ($d, $row) use ($affiliate_id, $is_export, $allowed_adjust_addon_affiliates_platform_fee) {
                    if ($is_export) {
                        return $d;
                    } else {

						if($allowed_adjust_addon_affiliates_platform_fee && $row['paid_flag'] == 0) {

							$id = $row['id'];
							$yearmonth = $row['yearmonth'];
							$affiliate_id = $row['affiliate_id'];
							$id = $row['id'];


							$modal_url = "/affiliate_management/affiliate_addon_platform_fee_adjustment/$id/$yearmonth/$affiliate_id";
							$modal_tiltle = lang("Update Addon Platform Fee");
							$format_d = $this->utils->formatCurrencyNoSym($d);
							$btn = "<a href=\"#\" onClick=\"return modal('$modal_url','$modal_tiltle');\">$format_d</a>";
							return $btn;
						} else {

							return $this->utils->formatCurrencyNoSym($d);
						}
					}

				},
			),
            array(
                'dt' => $i++,
                'select' =>'aff_monthly_earnings.total_fee',
            	'alias' => 'total_fee',
				'formatter' => 'currencyFormatter',
                'name' => lang('Total Fee'),
            ),
            // OGP-18086
		    //         [
		    //         	'dt' => $i++,
		    //             'select' =>'aff_monthly_earnings.net_revenue',
		    //         	'alias' => 'monthly_net_revenue',
						// 'formatter' => 'currencyFormatter',
		    //             'name' => lang('Monthly Net Revenue'),
		    //         ],
            array(
                'dt' => $i++,
                'select' => ($enable_tier) ? 'aff_monthly_earnings.total_net_revenue' : 'aff_monthly_earnings.net_revenue',
            	'alias' => 'net_revenue',
                'name' => lang('Net Revenue'),
                'formatter' => function($d, $row) use($is_export, $enable_tier){
                    if($is_export){
                        return $d;
                    }
                    if( $enable_tier ){
                        return '<a href="#" onClick="return modal(\'/affiliate_management/commission_details/' . $row['id'] .'/'. $row['yearmonth'].'/'. $row['affiliate_id']. '\', \''.lang('Commission Details').'\')">'.$this->utils->formatCurrencyNoSym($d).'</a>';
                    }
                    return $this->data_tables->currencyFormatter($d);

                }
			),
            array(
                'dt' => $i++,
                'select' => ($enable_tier) ? 'aff_monthly_earnings.commission_amount_breakdown' : 'aff_monthly_earnings.commission_percentage_breakdown',
            	'alias' => 'commission_percentage',
				'formatter' => function ($d, $row) use($is_export, $enable_tier) {
                    $str = '';
                    if($enable_tier && !empty($d)){
                    	if($this->CI->utils->getConfig('enabled_affiliate_commission_by_last_tier_only')){
                    		if($row['rate'] > 0){
                    			return ($row['rate'] * 100) ."%";
                    		}
                    		return "";
                    	}
                    	$data = json_decode($d, true);
                        $breakdown = end($data);
                        if(!empty($breakdown)){
                            $str .= (lang('Level'). ' : '. isset($breakdown['level'])) ? $breakdown['level'] : (('N/A'. ', '. lang('Rate'). ' : '. isset($breakdown['rate'])) ? $breakdown['rate'] : 'N/A');
                        }
                    }else{

                        $commission_percentage_breakdown = json_decode($d, TRUE);

                        if ($commission_percentage_breakdown) {

                            $commission_percentages = array_unique(array_values($commission_percentage_breakdown));

                            if (count($commission_percentages) == 1) return $commission_percentages[0] . '%';

                            foreach ($commission_percentage_breakdown as $platform_id => $commission_percentage) {
                                $str .= $this->external_system->getNameById($platform_id) . ": {$commission_percentage}%";
                                $str .= ($is_export) ? "\n" : "<br />";
                            }
                        }
                    }

					if(trim($str) == '')
						return $is_export ? lang('N/A') : '<i class="text-muted">'.lang('N/A').'</i>';

					return $str;
				},
                'name' => lang('Commission Rate'),
            ),
            array(
                'dt' => $i++,
                'select' => ($enable_tier) ? 'aff_monthly_earnings.commission_amount_by_tier' : 'aff_monthly_earnings.commission_amount',
            	'alias' => 'commission_amount',
				'formatter' => function($d, $row) use($is_export, $enable_tier){
					if($this->CI->utils->getConfig('enabled_affiliate_commission_by_last_tier_only')){
                		return $d;
                	}
                    if($is_export){
                        return $d;
                    }
                    if( $enable_tier ){
                        return '<a href="#" onClick="return modal(\'/affiliate_management/commission_details_by_tier/' . $row['id'] . '\', \''.lang('Commission Details').'\')">'.$this->utils->formatCurrencyNoSym($d).'</a>';
                    }else{
                        return '<a href="#" onClick="return modal(\'/affiliate_management/commission_details/' . $row['id'] . '\', \''.lang('Commission Details').'\')">'.$this->utils->formatCurrencyNoSym($d).'</a>';
                    }
				},
                'name' => lang('Commission Amount'),
            ),
            array(
                'dt' => $i++,
                'select' =>'aff_monthly_earnings.commission_from_sub_affiliates',
            	'alias' => 'commission_from_sub_affiliates',
                'formatter' => function($d, $row) use($is_export){
                    if($is_export || !$this->utils->isEnabledFeature('enable_sub_affiliate_commission_breakdown')){
                        return $d;
                    }else{
                        return '<a href="#" onClick="return modal(\'/affiliate_management/sub_affiliate_details/' . $row['id'] . '\', \''.lang('Commission Details').'\')">'.$this->utils->formatCurrencyNoSym($d).'</a>';
                    }

                },
                'name' => lang('Commission From Sub-affiliates'),
            ),
            array(
                'dt' => $i++,
                'select' =>'aff_monthly_earnings.total_commission',
            	'alias' => 'total_commission',
				'formatter' => 'currencyFormatter',
                'name' => lang('Total Commission'),
            ),
			array(
                'dt' => $enforce_cashback == Group_Level::CASHBACK_TARGET_AFFILIATE ? $i++ : null,
                'select' =>'aff_monthly_earnings.total_cashback',
            	'alias' => 'total_cashback',
				'formatter' => 'currencyFormatter',
                'name' => lang('Total Cashback'),
            ),
            array(
                'dt' => $i++,
            	'alias' => 'manual_adjustment',
				'formatter' => function ($d, $row) use ($affiliate_id, $is_export) {
					$output='';

					if($row['paid_flag'] == 0 && ! $affiliate_id) {
						$output .= '<a onclick="modal(\'/affiliate_management/affiliate_commision_manual_adjustment/'.$row['id'].'/'.$row['total_commission'].'\',\'' . lang('Manual Adjustment') . '\')" href="javascript:void(0);" data-toggle="tooltip" title="' . lang('Manual Adjustment') . '"><span class="glyphicon glyphicon-edit"></a> ';
					}

					$row['adjustment_notes'] = trim($row['adjustment_notes']);

					if ( ! empty($row['adjustment_notes'])) {
						if ( ! empty($output)) {
							$output .= '<br>';
						}
						$output .= '<strong>' . lang('Notes') . ': </strong>' . $row['adjustment_notes'];
					}

					$output = trim($output);

                    if($is_export){
                        return empty($row['adjustment_notes']) ? 'N/A' : lang('Notes'). ': '. $row['adjustment_notes'];
                    }
					return empty($output) ? '<i class="text-muted">N/A</i>' : $output;
				},
                'name' => lang('Manual Adjustment'),
            ),
            array(
                'dt' => $i++,
                'select' =>'aff_monthly_earnings.paid_flag',
            	'alias' => 'paid_flag',
				'formatter' => function ($d, $row) {
					return $d == 0 ? lang('Unpaid') : lang('Paid');
				},
                'name' => lang('Status'),
            ),
            array(
                'dt' => $i++,
                'select' =>'adminusers.username',
            	'alias' => 'updated_by',
				'formatter' => function ($d, $row) {
					return $d;
				},
                'name' => lang('Paid By'),
            ),
            array(
                'select' =>'affiliates.affiliateId',
            	'alias' => 'affiliate_id',
            	'formatter' => function ($d, $row) use ($is_export) {
					if ($is_export) {
						return '';
					}
				},
            ),
		);
		# END DEFINE COLUMNS #################################################################################################################################################

		$table = 'aff_monthly_earnings';
		$joins = array(
			'affiliates' => 'affiliates.affiliateId = aff_monthly_earnings.affiliate_id',
			'adminusers' => 'aff_monthly_earnings.updated_by = adminusers.userId',
            'affiliates as parent' => "parent.affiliateId = affiliates.parentId",
		);
		$joins['affiliate_player_benefit_fee'] = 'CONCAT(affiliate_player_benefit_fee.affiliate_id,affiliate_player_benefit_fee.year_month) = CONCAT(aff_monthly_earnings.affiliate_id,aff_monthly_earnings.year_month)';
		$joins['addon_affiliate_platform_fee'] = 'CONCAT(addon_affiliate_platform_fee.affiliate_id,addon_affiliate_platform_fee.year_month) = CONCAT(aff_monthly_earnings.affiliate_id,aff_monthly_earnings.year_month)';

		# START PROCESS SEARCH FORM #################################################################################################################################################
		$where = array();
		$values = array();

		if (isset($input['affiliate_id'])) {
			$where[] = 'aff_monthly_earnings.affiliate_id = ?';
			$values[] = $input['affiliate_id'];
		}

		if (isset($input['year_month'])) {
			$where[] = 'aff_monthly_earnings.year_month = ?';
			$values[] = $input['year_month'];
		}

		if (isset($input['affiliate_username'])) {
			$where[] = 'affiliates.username = ?';
			$values[] = $input['affiliate_username'];
		}

        if (isset($input['tag_id'])) {
            if (is_array($input['tag_id'])) {
                $where[] = 'affiliates.affiliateId IN ( SELECT affiliateId FROM affiliatetag WHERE tagId IN ('.implode(',', $input['tag_id']).') )';
            } else {
                $where[] = 'affiliates.affiliateId IN ( SELECT affiliateId FROM affiliatetag WHERE tagId = ? )';
                $values[] = $input['tag_id'];
            }
        }

		if (isset($input['parent_affiliate'])) {
			$where[] = 'affiliates.parentId = ?';
			$values[] = $input['parent_affiliate'];
		}

		if (isset($input['paid_flag'])) {
			$where[] = 'aff_monthly_earnings.paid_flag = ?';
			$values[] = $input['paid_flag'];
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

		if ($this->utils->isEnabledFeature('switch_to_ibetg_commission')) {
			$totalActivePlayer = $this->affiliate_earnings->getTotalActivePlayerSetting();
			//print_r($result);
			for ($c = 0; $c < count($result['data']); $c++) {
				$activeplayers = $result['data'][$c][4]; //active_players
				$yearmonth = $result['data'][$c][2]; //yearmonth
				$re = '';
				for($i = 1; $i <= 3; $i++) {
					$re .= $yearmonth . ',';
					$previousReport = $this->affiliate_earnings->getPreviousEarning($result['data'][$c][20], $yearmonth);
					if (!$previousReport)
						break;
					$activeplayers += (int)$previousReport->active_players;
					$yearmonth = $previousReport->year_month;
				}
				if ($activeplayers <= $totalActivePlayer) {
					$result['data'][$c][0] = '';
					$result['data'][$c][1] = '';
				}
			}
		}

		return $result;
	}

	public function aff_earnings_3($request, $is_export) {

		$readOnlyDB = $this->getReadOnlyDB();

		$this->load->library('data_tables', array('DB' => $readOnlyDB));
		$this->load->helper('aff_helper');
		$this->load->model(['affiliate_earnings','external_system','affiliatemodel']);

		$this->data_tables->is_export = $is_export;

		$input = $this->data_tables->extra_search($request);

		$min_amount = $this->affiliate_earnings->getMinimumMonthlyPayAmountSetting();

		$i = 0;
		$columns = array(
            array(
                'select' =>'affiliates.affiliateId',
            	'alias' => 'affiliate_id',
            ),
            array(
                'dt' => $i++,
                'select' =>'affiliate_game_platform_earnings.id',
            	'alias' => 'id',
            	'formatter' => function ($d, $row){
					return $row['paid_flag'] == 0 && $row['commission_amount'] != 0 ? '<input type="checkbox" class="batch-selected-cb user-success" id="selected_earnings_id" onClick="selectionValidate();" value="' . $d . '">': '';
				},
            ),
            array(
                'dt' => $i++,
                'select' =>'affiliate_game_platform_earnings.id',
            	'alias' => 'id',
            	'formatter' => function ($d, $row){
					return $row['paid_flag'] == 0 && $row['commission_amount'] != 0 ? '<a class="btn btn-xs btn-primary pay" href="javascript:void(0)" onclick="payOne(this)" data-earningid="' . $d . '"><i class="fa fa-paper-plane-o"></i>'.lang("Transfer to wallet").'</a>' : '';
				},
            ),
            array(
                'dt' => isset($input['affiliate_id']) ? NULL : $i++,
                'select' =>'affiliates.username',
            	'alias' => 'username',
            	'formatter' => function ($d, $row) {
					$url = site_url('/affiliate_management/userInformation/' . $row['affiliate_id']);
					return $d ? "<a href=\"{$url}\" target=\"_blank\">{$d}</a>" : ('<i class="text-muted">' . lang('N/A') . '</i>');
				},
            ),
            array(
                'dt' => $i++,
                'alias' => 'tagName',
                'select' => 'affiliates.affiliateId',
                'name' => lang("player.41"),
                'formatter' => function ($d, $row) use ($is_export) {
                    return aff_tagged_list($row['affiliate_id'], $is_export);
                },
            ),
            array(
                'dt' => $i++,
                'select' =>'affiliate_game_platform_earnings.game_platform_id',
            	'alias' => 'game_platform_id',
				'formatter' => function($d, $row) {
					return $this->external_system->getNameById($d);
				},
            ),
            array(
                'dt' => $i++,
                'select' =>'affiliate_game_platform_earnings.period',
            	'alias' => 'period',
				'formatter' => function($d) {return ucfirst($d);},
            ),
            array(
                'dt' => $i++,
                'select' =>'affiliate_game_platform_earnings.start_date',
            	'alias' => 'start_date',
				'formatter' =>  function($d) {return date('Y-m-d', strtotime($d));},
            ),
            array(
                'dt' => $i++,
                'select' =>'ROUND(affiliate_game_platform_earnings.game_platform_revenue - affiliate_game_platform_earnings.game_platform_gross_revenue,2)',
            	'alias' => 'game_platform_fee',
				'formatter' => 'currencyFormatter',
            ),
            array(
                'dt' => $i++,
                'select' =>'ROUND(affiliate_game_platform_earnings.game_platform_gross_revenue,2)',
            	'alias' => 'gross_revenue',
				'formatter' => 'currencyFormatter',
            ),
            array(
                'dt' => $i++,
                'select' =>'ROUND(affiliate_game_platform_earnings.game_platform_admin_fee,2)',
            	'alias' => 'admin_fee',
				'formatter' => 'currencyFormatter',
            ),
            array(
                'dt' => $i++,
                'select' =>'ROUND(affiliate_game_platform_earnings.game_platform_bonus_fee,2)',
            	'alias' => 'bonus_fee',
				'formatter' => 'currencyFormatter',
            ),
            array(
                'dt' => $i++,
                'select' =>'ROUND(affiliate_game_platform_earnings.game_platform_cashback_fee,2)',
            	'alias' => 'cashback_fee',
				'formatter' => 'currencyFormatter',
            ),
            array(
                'dt' => $i++,
                'select' =>'ROUND(affiliate_game_platform_earnings.game_platform_transaction_fee,2)',
            	'alias' => 'transaction_fee',
				'formatter' => 'currencyFormatter',
            ),
            array(
                'dt' => $i++,
                'select' =>'ROUND(affiliate_game_platform_earnings.game_platform_admin_fee+affiliate_game_platform_earnings.game_platform_bonus_fee+affiliate_game_platform_earnings.game_platform_cashback_fee+affiliate_game_platform_earnings.game_platform_transaction_fee,2)',
            	'alias' => 'total_fee',
				'formatter' => 'currencyFormatter',
            ),
            array(
                'dt' => $i++,
                'select' =>'ROUND(affiliate_game_platform_earnings.game_platform_net_revenue,2)',
            	'alias' => 'net_revenue',
				'formatter' => 'currencyFormatter',
            ),
            array(
                'dt' => $i++,
                'select' => 'affiliate_game_platform_earnings.game_platform_commission_rate',
            	'alias' => 'commission_percentage',
				'formatter' => function($d) {
					return number_format($d,2) . '%';
				},
            ),
            array(
                'dt' => $i++,
                'select' =>'ROUND(affiliate_game_platform_earnings.game_platform_commission_amount,2)',
            	'alias' => 'commission_amount',
				'formatter' => 'currencyFormatter',
            ),
            array(
                'dt' => $i++,
            	'alias' => 'manual_adjustment',
				'formatter' => function ($d, $row) {
					$output='';
					if($row['paid_flag'] == 0){
						$output .= '<a onclick="modal(\'/affiliate_management/affiliate_commision_manual_adjustment/'.$row['id'].'/'.$row['commission_amount'].'\',\'' . lang('Manual Adjustment') . '\')" href="javascript:void(0);" data-toggle="tooltip" title="' . lang('Manual Adjustment') . '"><span class="glyphicon glyphicon-edit"></a> ';
					}
					return $output;
				},
            ),
            array(
                'dt' => $i++,
                'select' =>'affiliate_game_platform_earnings.paid_flag',
            	'alias' => 'paid_flag',
				'formatter' => function ($d, $row) {
					return $d == 0 ? lang('Unpaid') : lang('Paid');
				},
            ),
            array(
                'dt' => $i++,
                'select' =>'adminusers.username',
            	'alias' => 'updated_by',
				'formatter' => function ($d, $row) {
					return $d;
				},
                'name' => lang('Paid By'),
            ),
		);

		# END DEFINE COLUMNS #################################################################################################################################################

		$table = 'affiliate_game_platform_earnings';
		$joins = array(
			'affiliates' => 'affiliates.affiliateId = affiliate_game_platform_earnings.affiliate_id',
			'adminusers' => 'affiliate_game_platform_earnings.updated_by = adminusers.userId',
		);

		# START PROCESS SEARCH FORM #################################################################################################################################################
		$where = array();
		$values = array();

		if (isset($input['affiliate_id'])) {
			$where[] = 'affiliate_game_platform_earnings.affiliate_id = ?';
			$values[] = $input['affiliate_id'];
		}

		if (isset($input['start_date'], $input['end_date'])) {
			$where[] = 'start_date >= ? && end_date <= ?';
			$values[] = $input['start_date'];
			$values[] = $input['end_date'];
		}

		if (isset($input['game_platform_id'])) {
			if (is_array($input['game_platform_id'])) {
				$where[] = 'affiliate_game_platform_earnings.game_platform_id IN ('.implode(',', $input['game_platform_id']).')';
			} else {
				$where[] = 'affiliate_game_platform_earnings.game_platform_id = ?';
				$values[] = $input['game_platform_id'];
			}
		}

		if (isset($input['affiliate_username'])) {
			$where[] = 'affiliates.username = ?';
			$values[] = $input['affiliate_username'];
		}

		if (isset($input['parent_affiliate'])) {
			$where[] = 'affiliates.parentId = ?';
			$values[] = $input['parent_affiliate'];
		}

		if (isset($input['tag_id'])) {
			if (is_array($input['tag_id'])) {
				$where[] = 'affiliates.affiliateId IN ( SELECT affiliateId FROM affiliatetag WHERE tagId IN ('.implode(',', $input['tag_id']).') )';
			} else {
				$where[] = 'affiliates.affiliateId IN ( SELECT affiliateId FROM affiliatetag WHERE tagId = ? )';
				$values[] = $input['tag_id'];
			}
		}

		if (isset($input['paid_flag'])) {
			$where[] = 'affiliate_game_platform_earnings.paid_flag = ?';
			$values[] = $input['paid_flag'];
		}

		$result = $this->data_tables->get_data($request, $columns, $table, $where, $values, $joins);

		if ($this->utils->isEnabledFeature('switch_to_ibetg_commission')) {
			$totalActivePlayer = $this->affiliate_earnings->getTotalActivePlayerSetting();
			for ($c = 0; $c < count($result['data']); $c++) {
				$activeplayers = $result['data'][$c][4]; //active_players
				$yearmonth = $result['data'][$c][2]; //yearmonth
				$re = '';
				for($i = 1; $i <= 3; $i++) {
					$re .= $yearmonth . ',';
					$previousReport = $this->affiliate_earnings->getPreviousEarning($result['data'][$c][20], $yearmonth);
					if (!$previousReport)
						break;
					$activeplayers += (int)$previousReport->active_players;
					$yearmonth = $previousReport->year_month;
				}
				if ($activeplayers <= $totalActivePlayer) {
					$result['data'][$c][0] = '';
					$result['data'][$c][1] = '';
				}
			}
		}

		$summary = $this->data_tables->summary($request, $table, $joins, '
			SUM(ROUND(affiliate_game_platform_earnings.game_platform_revenue - affiliate_game_platform_earnings.game_platform_gross_revenue,2)) as game_platform_fee,
			SUM(ROUND(affiliate_game_platform_earnings.game_platform_gross_revenue,2)) as gross_revenue,
			SUM(ROUND(affiliate_game_platform_earnings.game_platform_admin_fee,2)) as admin_fee,
			SUM(ROUND(affiliate_game_platform_earnings.game_platform_bonus_fee,2)) as bonus_fee,
			SUM(ROUND(affiliate_game_platform_earnings.game_platform_cashback_fee,2)) as cashback_fee,
			SUM(ROUND(affiliate_game_platform_earnings.game_platform_transaction_fee,2)) as transaction_fee,
			SUM(ROUND(affiliate_game_platform_earnings.game_platform_admin_fee+affiliate_game_platform_earnings.game_platform_bonus_fee+affiliate_game_platform_earnings.game_platform_cashback_fee+affiliate_game_platform_earnings.game_platform_transaction_fee,2)) as total_fee,
			SUM(ROUND(affiliate_game_platform_earnings.game_platform_net_revenue,2)) as net_revenue,
			SUM(ROUND(affiliate_game_platform_earnings.game_platform_commission_amount,2)) as commission_amount
		', null, $columns, $where, $values);

		$result['summary'] = $summary;

		return $result;
	}

    public function aff_user_earnings_3($request, $is_export) {

        $readOnlyDB = $this->getReadOnlyDB();

        $this->load->library('data_tables', array('DB' => $readOnlyDB));
        $this->load->model(['affiliate_earnings','external_system']);

        $this->data_tables->is_export = $is_export;

        $input = $this->data_tables->extra_search($request);

        $min_amount = $this->affiliate_earnings->getMinimumMonthlyPayAmountSetting();

        $i = 0;
        $columns = array(
            array(
                'select' =>'affiliates.affiliateId',
                'alias' => 'affiliate_id',
            ),
            array(
                'dt' => $i++,
                'select' =>'affiliate_game_platform_earnings.id',
                'alias' => 'id',
                'formatter' => function ($d, $row){
                    return $row['paid_flag'] == 0 && $row['commission_amount'] != 0 ? '<input type="checkbox" class="batch-selected-cb user-success" id="selected_earnings_id" onClick="selectionValidate();" value="' . $d . '">': '';
                },
            ),
            array(
                'dt' => $i++,
                'select' =>'affiliate_game_platform_earnings.id',
                'alias' => 'id',
                'formatter' => function ($d, $row){
                    return $row['paid_flag'] == 0 && $row['commission_amount'] != 0 ? '<a class="btn btn-xs btn-primary pay" href="javascript:void(0)" onclick="payOne(this)" data-earningid="' . $d . '"><i class="fa fa-paper-plane-o"></i>'.lang("Transfer to wallet").'</a>' : '';
                },
            ),
            array(
                'dt' => isset($input['affiliate_id']) ? NULL : $i++,
                'select' =>'affiliates.username',
                'alias' => 'username',
                'formatter' => function ($d, $row) {
                    $url = site_url('/affiliate_management/userInformation/' . $row['affiliate_id']);
                    return $d ? "<a href=\"{$url}\" target=\"_blank\">{$d}</a>" : ('<i class="text-muted">' . lang('N/A') . '</i>');
                },
            ),
            array(
                'dt' => $i++,
                'select' =>'affiliate_game_platform_earnings.game_platform_id',
                'alias' => 'game_platform_id',
                'formatter' => function($d, $row) {
                    return $this->external_system->getNameById($d);
                },
            ),
            array(
                'dt' => $i++,
                'select' =>'affiliate_game_platform_earnings.period',
                'alias' => 'period',
                'formatter' => function($d) {return ucfirst($d);},
            ),
            array(
                'dt' => $i++,
                'select' =>'affiliate_game_platform_earnings.start_date',
                'alias' => 'start_date',
                'formatter' =>  function($d) {return date('Y-m-d', strtotime($d));},
            ),
            array(
                'dt' => $i++,
                'select' =>'ROUND(affiliate_game_platform_earnings.game_platform_revenue - affiliate_game_platform_earnings.game_platform_gross_revenue,2)',
                'alias' => 'game_platform_fee',
                'formatter' => 'currencyFormatter',
            ),
            array(
                'dt' => $i++,
                'select' =>'ROUND(affiliate_game_platform_earnings.game_platform_gross_revenue,2)',
                'alias' => 'gross_revenue',
                'formatter' => 'currencyFormatter',
            ),
            array(
                'dt' => $i++,
                'select' =>'ROUND(affiliate_game_platform_earnings.game_platform_admin_fee,2)',
                'alias' => 'admin_fee',
                'formatter' => 'currencyFormatter',
            ),
            array(
                'dt' => $i++,
                'select' =>'ROUND(affiliate_game_platform_earnings.game_platform_bonus_fee,2)',
                'alias' => 'bonus_fee',
                'formatter' => 'currencyFormatter',
            ),
            array(
                'dt' => $i++,
                'select' =>'ROUND(affiliate_game_platform_earnings.game_platform_cashback_fee,2)',
                'alias' => 'cashback_fee',
                'formatter' => 'currencyFormatter',
            ),
            array(
                'dt' => $i++,
                'select' =>'ROUND(affiliate_game_platform_earnings.game_platform_transaction_fee,2)',
                'alias' => 'transaction_fee',
                'formatter' => 'currencyFormatter',
            ),
            array(
                'dt' => $i++,
                'select' =>'ROUND(affiliate_game_platform_earnings.game_platform_admin_fee+affiliate_game_platform_earnings.game_platform_bonus_fee+affiliate_game_platform_earnings.game_platform_cashback_fee+affiliate_game_platform_earnings.game_platform_transaction_fee,2)',
                'alias' => 'total_fee',
                'formatter' => 'currencyFormatter',
            ),
            array(
                'dt' => $i++,
                'select' =>'ROUND(affiliate_game_platform_earnings.game_platform_net_revenue,2)',
                'alias' => 'net_revenue',
                'formatter' => 'currencyFormatter',
            ),
            array(
                'dt' => $i++,
                'select' => 'affiliate_game_platform_earnings.game_platform_commission_rate',
                'alias' => 'commission_percentage',
                'formatter' => function($d) {
                    return number_format($d,2) . '%';
                },
            ),
            array(
                'dt' => $i++,
                'select' =>'ROUND(affiliate_game_platform_earnings.game_platform_commission_amount,2)',
                'alias' => 'commission_amount',
                'formatter' => 'currencyFormatter',
            ),
            array(
                'dt' => $i++,
                'select' =>'affiliate_game_platform_earnings.paid_flag',
                'alias' => 'paid_flag',
                'formatter' => function ($d, $row) {
                    return $d == 0 ? lang('Unpaid') : lang('Paid');
                },
            ),
            array(
                'dt' => $i++,
                'alias' => 'manual_adjustment',
                'formatter' => function ($d, $row) {
                    $output='';
                    if($row['paid_flag'] == 0){
                        $output .= '<a onclick="modal(\'/affiliate_management/affiliate_commision_manual_adjustment/'.$row['id'].'/'.$row['commission_amount'].'\',\'' . lang('Manual Adjustment') . '\')" href="javascript:void(0);" data-toggle="tooltip" title="' . lang('Manual Adjustment') . '"><span class="glyphicon glyphicon-edit"></a> ';
                    }
                    return $output;
                },
            ),
        );

        # END DEFINE COLUMNS #################################################################################################################################################

        $table = 'affiliate_game_platform_earnings';
        $joins = array(
            'affiliates' => 'affiliates.affiliateId = affiliate_game_platform_earnings.affiliate_id',
        );

        # START PROCESS SEARCH FORM #################################################################################################################################################
        $where = array();
        $values = array();

        if (isset($input['affiliate_id'])) {
            $where[] = 'affiliate_game_platform_earnings.affiliate_id = ?';
            $values[] = $input['affiliate_id'];
        }

        if (isset($input['start_date'], $input['end_date'])) {
            $where[] = 'start_date >= ? && end_date <= ?';
            $values[] = $input['start_date'];
            $values[] = $input['end_date'];
        }

        if (isset($input['game_platform_id'])) {
            if (is_array($input['game_platform_id'])) {
                $where[] = 'affiliate_game_platform_earnings.game_platform_id IN ('.implode(',', $input['game_platform_id']).')';
            } else {
                $where[] = 'affiliate_game_platform_earnings.game_platform_id = ?';
                $values[] = $input['game_platform_id'];
            }
        }

        if (isset($input['affiliate_username'])) {
            $where[] = 'affiliates.username = ?';
            $values[] = $input['affiliate_username'];
        }

        if (isset($input['parent_affiliate'])) {
            $where[] = 'affiliates.parentId = ?';
            $values[] = $input['parent_affiliate'];
        }

        if (isset($input['tag_id'])) {
            if (is_array($input['tag_id'])) {
                $where[] = 'affiliates.affiliateId IN ( SELECT affiliateId FROM affiliatetag WHERE tagId IN ('.implode(',', $input['tag_id']).') )';
            } else {
                $where[] = 'affiliates.affiliateId IN ( SELECT affiliateId FROM affiliatetag WHERE tagId = ? )';
                $values[] = $input['tag_id'];
            }
        }

        if (isset($input['paid_flag'])) {
            $where[] = 'affiliate_game_platform_earnings.paid_flag = ?';
            $values[] = $input['paid_flag'];
        }

        $result = $this->data_tables->get_data($request, $columns, $table, $where, $values, $joins);

        if ($this->utils->isEnabledFeature('switch_to_ibetg_commission')) {
            $totalActivePlayer = $this->affiliate_earnings->getTotalActivePlayerSetting();
            //print_r($result);
            for ($c = 0; $c < count($result['data']); $c++) {
                $activeplayers = $result['data'][$c][4]; //active_players
                $yearmonth = $result['data'][$c][2]; //yearmonth
                $re = '';
                for($i = 1; $i <= 3; $i++) {
                    $re .= $yearmonth . ',';
                    $previousReport = $this->affiliate_earnings->getPreviousEarning($result['data'][$c][20], $yearmonth);
                    if (!$previousReport)
                        break;
                    $activeplayers += (int)$previousReport->active_players;
                    $yearmonth = $previousReport->year_month;
                }
                if ($activeplayers <= $totalActivePlayer) {
                    $result['data'][$c][0] = '';
                    $result['data'][$c][1] = '';
                }
            }
        }

        $summary = $this->data_tables->summary($request, $table, $joins, '
			SUM(ROUND(affiliate_game_platform_earnings.game_platform_revenue - affiliate_game_platform_earnings.game_platform_gross_revenue,2)) as game_platform_fee,
			SUM(ROUND(affiliate_game_platform_earnings.game_platform_gross_revenue,2)) as gross_revenue,
			SUM(ROUND(affiliate_game_platform_earnings.game_platform_admin_fee,2)) as admin_fee,
			SUM(ROUND(affiliate_game_platform_earnings.game_platform_bonus_fee,2)) as bonus_fee,
			SUM(ROUND(affiliate_game_platform_earnings.game_platform_cashback_fee,2)) as cashback_fee,
			SUM(ROUND(affiliate_game_platform_earnings.game_platform_transaction_fee,2)) as transaction_fee,
			SUM(ROUND(affiliate_game_platform_earnings.game_platform_admin_fee+affiliate_game_platform_earnings.game_platform_bonus_fee+affiliate_game_platform_earnings.game_platform_cashback_fee+affiliate_game_platform_earnings.game_platform_transaction_fee,2)) as total_fee,
			SUM(ROUND(affiliate_game_platform_earnings.game_platform_net_revenue,2)) as net_revenue,
			SUM(ROUND(affiliate_game_platform_earnings.game_platform_commission_amount,2)) as commission_amount
		', null, $columns, $where, $values);

        $result['summary'] = $summary;

        return $result;
    }

	public function get_affiliate_player_report_hourly($affiliate_id, $request, $viewPlayerInfoPerm, $is_export = false) {

		$readOnlyDB = $this->getReadOnlyDB();

		$this->load->library('data_tables', array("DB" => $readOnlyDB));
		$this->load->model(array('transactions','game_logs','affiliatemodel'));

		$this->data_tables->is_export = $is_export;

        $enable_tier = false;
        $commonSettings = $this->affiliatemodel->getDefaultAffSettings();
        if(isset($commonSettings['enable_commission_by_tier']) && $commonSettings['enable_commission_by_tier'] == true){
            $enable_tier = true;
        }

		$i 			= 0;
		$input 		= $this->data_tables->extra_search($request);
		$joins 		= array();
		$where 		= array();
		$values 	= array();
		$group_by 	= array();
		// $having 	= array();

		$only_show_non_zero_player=isset($input['only_show_non_zero_player']) ? $input['only_show_non_zero_player']=='true' : false;
		$start 		= $request['start'] + 1;
		# DEFINE TABLE COLUMNS ########################################################################################################################################################################
		$columns = array();
		$columns[] = array(
			'alias' => 'playerId',
			'select' => 'player.playerId',
		);
		$columns[] = array(
			'alias' => 'affiliateId',
			'select' => 'player.affiliateId',
		);
		$columns[] = array(
			'alias' => 'groupName',
			'select' => 'player.groupName',
		);
		$columns[] = array(
			'alias' => 'levelName',
			'select' => 'player.levelName',
		);
		$columns[] = array(
			'alias' => 'group_by',
			'select' => isset($input['group_by']) ? "'" . $input['group_by'] . "'" : '\'<i class="text-muted">N/A</i>\'',
		);
		$columns[] = array(
			'dt' => $i++,
			'alias' => 'serial',
			'select' => 'player.playerId',
			'name' => lang('#'),
			'formatter' => function($d, $row) use (&$start) {
				return $start++;
			}
		);
		$columns[] = array(
			'dt' => ($username_col = $i++),
			'alias' => 'username',
			'select' => 'player.username',
			'formatter' => function ($d, $row) use ($is_export) {

				if ($this->utils->isAffSubProject() && $this->utils->isEnabledFeature('masked_player_username_on_affiliate')) {
					$d = $this->utils->keepOnlyString($d, 4);
				}

				if ($is_export) {
					return $d;
				} else {
					return "<a href='/report_management/viewPlayerGameReport/{$row['playerId']}'>{$d}</a>";
				}
			},
			'name' => lang('Player'),
		);

		if ($this->utils->isEnabledFeature('aff_show_real_name_on_reports')) {
			$columns[] = array(
				'dt' => ($realname_col = $i++),
				'alias' => 'realname',
				'select' => "CONCAT_WS(' ', playerdetails.firstName,playerdetails.lastName)",
				'formatter' => function ($d, $row) {

					if ($this->utils->isEnabledFeature('masked_realname_on_affiliate')) {
						$d = $this->utils->keepOnlyString($d, 4);
					}

					return trim($d);
				},
				'name' => lang('Real Name'),
			);
		}

		$columns[] = array(
			'dt' => ($affiliate_username_col = $i++),
			'alias' => 'affiliate_username',
			'select' => 'affiliates.username',
			'formatter' => function ($d, $row) use ($is_export) {

				if ($this->utils->isEnabledFeature('masked_affiliate_username_on_affiliate')) {
					$d = $this->utils->keepOnlyString($d, 4);
				}

				// if ($is_export) {
					return $d;
				// } else {
					// return "<a href='/report_management/viewPlayerGameReport/{$row['affiliateId']}'>{$d}</a>";
				// }
			},
			'name' => lang('Affiliate'),
		);
        $columns[] = array(
            'dt' => ($first_deposit_datetime_col = $i++),
            'alias' => 'first_deposit_datetime',
            'select' => 'MAX(first_deposit_datetime)',
            'formatter' => function ($d, $row) {
                return $d;
            },
            'name' => lang('aff.ap06'),
        );
		$columns[] = array(
			'dt' => ($member_level_col = $i++),
			'alias' => 'member_level',
			'select' => "player.playerId",
			'formatter' => function ($d, $row) {
				return implode(' - ', array(lang($row['groupName']),lang($row['levelName'])));
			},
			'name' => lang('report.pr03'),
		);
		if($this->config->item('display_affiliate_player_ip_history_in_player_report')){
			$columns[] = array(
				'dt' => ($ip_address = $i++),
				'alias' => 'ip_address',
				'select' => "player_ip_last_request.ip",
				'formatter' => function ($d, $row) {
					return isset($d) ? '<a href="/affiliate/ip_history/'.$row['playerId'].'">' . $d . '</a>' : '<i class="text-muted">' . lang('N/A') . '</i>';
				},
				'name' => lang('report.pr03'),
			);
		}

		if ($this->utils->isEnabledFeature('show_cashback_and_bonus_on_aff_player_report')) {
			$columns[] = array(
				'dt' => ($total_cashback_col = $i++),
				'alias' => 'total_cashback',
				'select' => 'SUM(player_report_hourly.total_cashback)',
				'formatter' => 'currencyFormatter',
				'name' => lang('Total Cashback'),
			);
			$columns[] = array(
				'dt' => ($total_bonus_col = $i++),
				'alias' => 'total_bonus',
				'select' => 'SUM(player_report_hourly.total_bonus)',
				'formatter' => 'currencyFormatter',
				'name' => lang('Total Bonus'),
			);
		}
		if ( ! $this->utils->isEnabledFeature('hide_deposit_and_withdraw_on_aff_player_report')) {
			$columns[] = array(
				'dt' => ($total_deposit_col = $i++),
				'alias' => 'total_deposit',
				'select' => 'SUM(player_report_hourly.total_deposit)',
				'formatter' => 'currencyFormatter',
				'name' => lang('report.pr21'),
			);
			$columns[] = array(
				'dt' => ($total_withdrawal_col = $i++),
				'alias' => 'total_withdrawal',
				'select' => 'SUM(player_report_hourly.total_withdrawal)',
				'formatter' => 'currencyFormatter',
				'name' => lang('report.pr22'),
			);
		}
		$columns[] = array(
			'dt' => ($total_dep_with_col = $i++),
			'alias' => 'total_dep_with',
			'select' => 'SUM(player_report_hourly.total_gross)',
			'formatter' => 'currencyFormatter',
			'name' => lang('Deposit - Withdraw'),
		);
		$columns[] = array(
			'dt' => ($total_bets_col = $i++),
			'alias' => 'total_bets',
			'select' => 'SUM(player_report_hourly.total_bet)',
			'formatter' => 'currencyFormatter',
			'name' => lang('Total Bets'),
		);
		$columns[] = array(
			'dt' => ($total_bets_col = $i++),
			'alias' => 'bet_plus_result',
			'select' => 'SUM(player_report_hourly.total_bet) + SUM(player_report_hourly.total_result)',
			'formatter' => 'currencyFormatter',
			'name' => lang('Bet Result'),
		);

		if ( ! $this->utils->isEnabledFeature('hide_total_win_loss_on_aff_player_report')) {
			$columns[] = array(
				'dt' => ($total_wins_col = $i++),
				'alias' => 'total_wins',
				'select' => 'SUM(player_report_hourly.total_win)',
				'formatter' => 'currencyFormatter',
				'name' => lang('Total Wins'),
			);
			$columns[] = array(
				'dt' => ($total_loss_col = $i++),
				'alias' => 'total_loss',
				'select' => 'SUM(player_report_hourly.total_loss)',
				'formatter' => 'currencyFormatter',
				'name' => lang('Total Loss'),
			);
		}

		$columns[] = array(
			'dt' => ($net_gaming_col = $i++),
			'alias' => 'net_gaming',
			'select' => 'SUM(-player_report_hourly.total_result)',
			'formatter' => 'currencyFormatter',
			'name' => lang('Net Gaming'),
		);

		# FILTER ######################################################################################################################################################################################
		$where[] = "player.affiliateId IS NOT NULL";

		$dateTimeFrom = null; $dateTimeTo = null;

		if($only_show_non_zero_player){
			$table 		= 'player_report_hourly';
			$joins['player'] = 'player.playerId = player_report_hourly.player_id';
			$joins['playerdetails'] ='player.playerId=playerdetails.playerId';
			$joins['affiliates'] = 'affiliates.affiliateId = player.affiliateId';

	 		if (isset($input['search_on_date']) && $input['search_on_date']) {
	 			if (isset($input['date_from'], $input['date_to'])) {
	 				$dateTimeFrom = $input['date_from'];
	 				$dateTimeTo = $input['date_to'];

					$where[] = "player_report_hourly.date_hour >=? and player_report_hourly.date_hour <=?";
					$values[] = $this->utils->formatDateHourForMysql(new DateTime($dateTimeFrom));
					$values[] = $this->utils->formatDateHourForMysql(new DateTime($dateTimeTo));
				}
			}

		}else{
			$table 		= 'player';
			$joins['affiliates'] = 'affiliates.affiliateId = player.affiliateId';
			$joins['playerdetails'] ='player.playerId=playerdetails.playerId';
			$transactions_sql = 'player_report_hourly.player_id = player.playerId ';

	 		if (isset($input['search_on_date']) && $input['search_on_date']) {
	 			if (isset($input['date_from'], $input['date_to'])) {
	 				$dateTimeFrom = $input['date_from'];
	 				$dateTimeTo = $input['date_to'];
	 				$transactions_sql .= " AND player_report_hourly.date_hour >='".$this->utils->formatDateHourForMysql(new DateTime($dateTimeFrom)).
	 					"' AND player_report_hourly.date_hour <='".$this->utils->formatDateHourForMysql(new DateTime($dateTimeTo))."'";
				}
			}

			$joins['player_report_hourly'] = $transactions_sql;
		}

		$joins['player_ip_last_request'] = 'player_ip_last_request.player_id = player.playerId';

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
			$where[] = 'player_report_hourly.total_deposit <= ?';
			$values[] = $input['depamt1'];
		}

		if (isset($input['depamt2'])) {
			$where[] = 'player_report_hourly.total_deposit >= ?';
			$values[] = $input['depamt2'];
		}

		if (isset($input['widamt1'])) {
			$where[]='player_report_hourly.total_withdrawal <= ?';
			$values[] = $input['widamt1'];
		}

		if (isset($input['widamt2'])) {
			$where[]='player_report_hourly.total_withdrawal >= ?';
			$values[] = $input['widamt2'];
		}

        if($this->utils->isEnabledFeature('enable_exclude_platforms_in_player_report') && $enable_tier){
            $where[]  = "player_report_hourly.game_platform_id IN (" . implode(',', $commonSettings['tier_provider']) . ")";
        }

		$search_affiliate_id = $affiliate_id;
		if (isset($input['affiliate_username']) && ! empty($input['affiliate_username'])) {
			$affiliate_downline_ids = $this->affiliatemodel->includeAllDownlineAffiliateIds($search_affiliate_id);
			$search_affiliate_id = $this->affiliatemodel->getAffiliateIdByUsername($input['affiliate_username']);
			if ( ! in_array($search_affiliate_id, $affiliate_downline_ids)) {
				$search_affiliate_id = 0;
			}

		}

		if(empty($search_affiliate_id)){
			//return null , security
			return null;
		}

		if (isset($input['include_all_downlines']) && $input['include_all_downlines'] == true) {

			$affiliate_downline_ids = $this->affiliatemodel->includeAllDownlineAffiliateIds($search_affiliate_id);
			$affiliateList=$this->affiliatemodel->getUsernames($affiliate_downline_ids);

			$where[]  = "player.affiliateId IN (" . implode(',', $affiliate_downline_ids) . ")";
		} else {

			//add username to $affiliateList
			$affiliateList=$this->affiliatemodel->getUsernames([$search_affiliate_id]);

			$where[]  = "player.affiliateId = ?";
			$values[] = $search_affiliate_id;
		}

		$this->utils->debug_log('GET_AFFILIATE_PLAYER_REPORTS_HOURLY where values', $where, $values);

		# OUTPUT ######################################################################################################################################################################################
		$result  = $this->data_tables->get_data($request, $columns, $table, $where, $values, $joins, $group_by);
        $summary = $this->data_tables->summary($request, $table, $joins,
            'SUM( player_report_hourly.total_cashback ) cashback_total, SUM( player_report_hourly.total_bonus) bonus_total, '.
            'SUM( player_report_hourly.total_deposit ) deposit_total, SUM( player_report_hourly.total_withdrawal ) withdrawal_total, '.
            'SUM( player_report_hourly.total_gross )  deposit_withdrawal_total, '.
            'SUM( player_report_hourly.total_bet ) bet_total, SUM( player_report_hourly.total_win ) win_total, '.
            '(SUM( player_report_hourly.total_bet ) + SUM(player_report_hourly.total_result )) bet_plus_result_total, '.
            'SUM( player_report_hourly.total_loss ) loss_total, SUM( -player_report_hourly.total_result ) net_total',
            null, $columns, $where, $values);

        $result['summary']=$summary;

        if(isset($result['summary'][0])){

			foreach ($result['summary'][0] as &$value) {
				$value = $this->utils->formatCurrencyNoSym($value);
			}

        }

        $result['affiliate_list']=$affiliateList;

		return $result;
	}

	/**
	 * detail: get affiliate partners
	 * @param array $request
	 * @param Boolean $is_export
	 * @return array
	 */
	public function affiliate_partners($request, $is_export = false) {

		$this->load->library('data_tables');
		$this->load->model(array('affiliatemodel','player_model'));
        $_this = $this;

		# START DEFINE COLUMNS #################################################################################################################################################
		$i = 0;
		$columns = array(
			array(
				'alias' => 'player_id',
				'select' => 'player.playerId',
			),
			array(
				'alias' => 'aff_id',
				'select' => 'player.affiliateId',
			),
			array(
				'dt' => $i++,
				'alias' => 'player_username',
				'select' => 'player.username',
				'name' => lang('Username'),
				'formatter' => function ($d, $row) use ($is_export) {
					if (!$is_export) {
						return '<a href="/player_management/userInformation/' . $row['player_id'] . '" target="_blank">' . $d . '</a>';
					} else {
						return $d;
					}
				},
			),
			array(
				'dt' => $i++,
				'alias' => 'reg_time',
				'select' => 'player.createdOn',
				'name' => lang('Registration time'),
				'formatter' => 'defaultFormatter',
			),
			array(
				'dt' => $i++,
				'alias' => 'registered_by',
				'select' => 'player.registered_by',
				'name' => lang('Registration method'),
				// 'formatter' => 'defaultFormatter',
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
			),
			array(
				'dt' => $i++,
				'alias' => 'reg_ip',
				'select' => 'playerdetails.registrationIp',
				'name' => lang('Registration IP'),
				'formatter' => 'defaultFormatter',
			),
			array(
				'dt' => $i++,
				'alias' => 'referrer',
				'select' => 'player.refereePlayerId',
				'name' => lang('Referrer'),
				'formatter' => function ($d, $row) use ($is_export) {
					$referrerUsername = $this->player_model->getPlayerUsername($d)['username'];
					if (!$is_export) {
						return '<a href="/player_management/userInformation/' . $d . '" target="_blank">' . $referrerUsername . '</a>';
					} else {
						return $referrerUsername;
					}
				},
			),
			array(
				'dt' => $i++,
				'alias' => 'parent_id',
				'select' => 'affiliates.parentId',
				'name' => lang('Parent Affiliate'),
				'formatter' => function ($d, $row) use ($is_export, $_this) {
                    $_affiliateId = $d;
                    $parentusername = $this->affiliatemodel->getUsernameById($_affiliateId);
                    $_is_hide = $this->affiliatemodel->is_hide($_affiliateId);
                    $prefix_string = '';
                    return $_this->formatter_aff_username($_affiliateId, $parentusername, $_is_hide, $is_export, $prefix_string);
				},
			),
            array(
				'alias' => 'is_aff_hide',
				'select' => 'affiliates.is_hide',
			),
			array(
				'dt' => $i++,
				'alias' => 'aff_username',
				'select' => 'affiliates.username',
				'name' => lang('Affiliate'),
				'formatter' => function ($d, $row) use ($is_export, $_this) {
                    $_affiliateId = $row['aff_id'];
                    $_is_hide = $row['is_aff_hide'];
                    $_aff_username = $d;
                    $prefix_string = '';
                    return $_this->formatter_aff_username($_affiliateId, $_aff_username, $_is_hide, $is_export, $prefix_string);
				},
			),
			array(
				'dt' => $i++,
				'alias' => 'total_deposit',
				'select' => 'sum(player_report_hourly.total_deposit)',
				'name' => lang('Total deposit'),
				// 'formatter' => 'defaultFormatter',
				'formatter' => function ($d, $row) {
					return $this->utils->formatCurrencyNoSym($d);
				},
			),
			array(
				'dt' => $i++,
				'alias' => 'total_withdrawal',
				'select' => 'sum(player_report_hourly.total_withdrawal)',
				'name' => lang('Total withdrawal'),
				// 'formatter' => 'defaultFormatter',
				'formatter' => function ($d, $row) {
					return $this->utils->formatCurrencyNoSym($d);
				},
			),
			array(
				'dt' => $i++,
				'alias' => 'total_bet',
				'select' => 'sum(player_report_hourly.total_bet)',
				'name' => lang('Total bet'),
				// 'formatter' => 'defaultFormatter',
				'formatter' => function ($d, $row) {
					return $this->utils->formatCurrencyNoSym($d);
				},
			),
			array(
				'dt' => $i++,
				'alias' => 'total_win',
				'select' => 'sum(player_report_hourly.total_win)',
				'name' => lang('Total win'),
				// 'formatter' => 'defaultFormatter',
				'formatter' => function ($d, $row) {
					return $this->utils->formatCurrencyNoSym($d);
				},
			),
			array(
				'dt' => $i++,
				'alias' => 'total_loss',
				'select' => 'sum(player_report_hourly.total_loss)',
				'name' => lang('Total loss'),
				// 'formatter' => 'defaultFormatter',
				'formatter' => function ($d, $row) {
					return $this->utils->formatCurrencyNoSym($d);
				},
			)

		);
		# END DEFINE COLUMNS #################################################################################################################################################
		$input = $this->data_tables->extra_search($request);

		$startDateTime = new DateTime($this->utils->getNowForMysql());
    	$endDateTime = new DateTime($this->utils->getNowForMysql());
    	$queryDateTimeStart = $startDateTime->format("YmdH");
		$queryDateTimeEnd = $endDateTime->format("Ymd23");

		if (isset($input['date_from']) && isset($input['date_to'])) {
			$startDateTime = new DateTime($input['date_from']);
	    	$endDateTime = new DateTime($input['date_to']);
	    	$queryDateTimeStart = $startDateTime->format("YmdH");
			$queryDateTimeEnd = $endDateTime->format("YmdH");
		}

		$table = 'player';
		$joins = array(
			'affiliates' => 'affiliates.affiliateId = player.affiliateId',
			'playerdetails' => 'playerdetails.playerId = player.playerId',
			'player_report_hourly' => "player_report_hourly.player_id = player.playerId  and player_report_hourly.date_hour >= '{$queryDateTimeStart}' and player_report_hourly.date_hour <= '{$queryDateTimeEnd}' "
		);

		# START PROCESS SEARCH FORM #################################################################################################################################################
		$where = array();
		$values = array();
		if(isset($input['username'])){
			$affiliateId = $this->affiliatemodel->getAffiliateIdByUsername($input['username']);
			$get_parent_aff = true;
			$affiliateIds = $this->affiliatemodel->getAffiliateParentChildHierarchy($affiliateId, $get_parent_aff);
			$where[] = 'player.affiliateId IN(' . implode(',', $affiliateIds) . ')';
		}
		$where[] = "player.deleted_at IS NULL";

		$group_by[] = 'player.playerId';
		$having = [];
		$distinct = false;

		# END PROCESS SEARCH FORM #################################################################################################################################################
		$this->config->set_item('debug_data_table_sql', true);

		if($is_export){
            $this->data_tables->options['is_export']=true;
            if(empty($csv_filename)){
                $csv_filename=$this->utils->create_csv_filename(__FUNCTION__);
            }
            $this->data_tables->options['csv_filename']=$csv_filename;
		}

		$result = $this->data_tables->get_data($request, $columns, $table, $where, $values, $joins, $group_by, $having, $distinct);

		if($is_export){
		    //drop result if export
			return $csv_filename;
		}

        $last_query = $this->data_tables->last_query;
        $result['dbg'] = $last_query;
		return $result;
	} // EOF affiliate_partners

    public function get_affiliate_login_logs($request, $is_export = false) {

        $this->load->library('data_tables');
        $this->load->model('affiliatemodel');
		$input 		= $this->data_tables->extra_search($request);
		$i 			= 0;
		$joins 		= array();
		$where 		= array();
		$values 	= array();
		$group_by 	= array();
        $having 	= array();
        $distinct = false;


        $_this = $this;
        $updated_at_col = 0;
        $username_col = 1;
        $ip_col = 2;
        $referrer_col = 3;
        # DEFINE TABLE COLUMNS ########################################################################################################################################################################
        $columns = array(
            array(
                'select' => 'aff_logs.affiliate_id',
                'alias' => 'aff_id',
            ),
            array(
                'dt' => $updated_at_col,
                'select' => 'aff_logs.updated_at',
                'alias' => 'updated_at',
            ),
            array(
                'dt' => $username_col,
                'select' => 'aff_logs.username',
				'name' => lang('Affiliate'),
                'alias' => 'username',
				'formatter' => function ($d, $row) use ($is_export, $_this) {
                    $_affiliateId = $row['aff_id'];
                    $_is_hide = false;
                    $_aff_username = $d;
                    $prefix_string = '';
                    return $_this->formatter_aff_username($_affiliateId, $_aff_username, $_is_hide, $is_export, $prefix_string);
                }
            ),
            array(
                'dt' => $ip_col,
                'select' => 'aff_logs.ip',
                'alias' => 'ip',
            ),
            array(
                // 'dt' => $action_col,
                'select' => 'aff_logs.action',
                'alias' => 'action',
            ),
            array(
                'dt' => $referrer_col,
                'select' => 'aff_logs.referrer',
                'alias' => 'referrer',
            ),
        );

        $table = 'aff_logs';
        $joins['affiliates'] = 'affiliates.affiliateId = aff_logs.affiliate_id';

        if($input['by_date_from'] && $input['by_date_to']){
            $where[] = 'aff_logs.updated_at >= ? AND aff_logs.updated_at <= ?';
            $values[] = $input['by_date_from'];
            $values[] = $input['by_date_to'];
        }

        if(!empty($input['by_username'])){
            $where[] = 'aff_logs.username = ?';
            $values[] = $input['by_username'];
        }

        if(!empty($input['login_ip'])){
            $where[] = 'aff_logs.ip = ?';
            $values[] = $input['login_ip'];
        }

        $where[] = 'aff_logs.action = ?';
        $values[] = 'login';

        $external_order=[];
		$notDatatable = '';
		$countOnlyField = '';
		$innerJoins=['affiliates'];
		$useIndex=[];
        # OUTPUT ######################################################################################################################################################################################
        // $result = $this->data_tables->get_data($request, $columns, $table, $where, $values, $joins, $group_by, $having, $distinct);
        $result = $this->data_tables->get_data($request // #1
        , $columns // #2
        , $table // #3
        , $where // #4
        , $values // #5
        , $joins // #6
        , $group_by // #7
        , $having  // #8
        , $distinct // #9
        , $external_order // #10
        , $notDatatable // #11
        , $countOnlyField // #12
        , $innerJoins // #13
        , $useIndex // #14
    );
        $last_query = $this->data_tables->last_query;
        $result['dbg'] = $last_query;
		return $result;
    }

	/**
	 * To extract affiliateid from the anchor in HTML
	 *
	 * ref. to, https://regex101.com/r/HsycjC/1
	 *
	 * @param string $anchorOfHtml
	 * @return array The affiliateid list
	 */
	public function extractAffiliateidFromAnchorInHTML($anchorOfHtml = '<i class="fa fa-user" ></i> <a href="/affiliate_management/userInformation/666" data-affiliateid="666" target="_blank">123htrz</a>'){
		$affiliateid_list = [];
		$regex = '/data-affiliateid=["\'](?P<affiliateid>\d+)["\']/m';
		preg_match_all($regex, $anchorOfHtml, $matches, PREG_SET_ORDER, 0);
		if( ! empty($matches) ){
			foreach($matches as $indexNumber => $currMatche){
				if( ! empty($currMatche['affiliateid'] ) ){
					array_push($affiliateid_list, $currMatche['affiliateid']);
				}
			}
		}
		return $affiliateid_list;
	}// EOF extractAffiliateidFromAnchorInHTML

}

///END OF FILE/////////////
