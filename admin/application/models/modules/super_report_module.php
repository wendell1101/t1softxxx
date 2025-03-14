<?php
/**
 * General behaviors include
 * * get super player reports
 *
 * @category super_player_report
 * @version 1.8.10
 * @copyright 2013-2022 tot
 */
trait super_report_module {
	
	/**
	 * detail: get super player reports
	 *
	 * @param array $request
	 * @param Boolean $is_export
	 *
	 * @return array
	 */
	public function super_player_report($request, $is_export = false) {


		$readOnlyDB = $this->getReadOnlyDB();

		$this->load->library('data_tables', array("DB" => $readOnlyDB));
		$this->load->helper(['player_helper']);


		# START DEFINE COLUMNS #################################################################################################################################################
		$input = $this->data_tables->extra_search($request);

		$i = 0;
		$columns = array();
			$columns[] =  array(
				'select' => 'id',
				'alias' => 'data_id',
				'name' => lang('lang.action'),
			);
			$columns[] = array(
				'dt' => $i++,
				'alias' => 'currency',
				'select' => 'currency_key',
				'name' => lang('Currency'),
				'formatter' => function ($d) use ($is_export) {
					return $d;
				}
			);
			$columns[] = array(
				'dt' => $i++,
				'alias' => 'report_date',
				'select' => 'created_at' ,
				'name' => lang("report.sum02"),
				'formatter' => function ($d) use ($is_export) {
					if ($is_export) {
						return !empty($d) ? $d: lang('lang.norecyet');
					} else {
						return !empty($d) ? $d : '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
					}
				},
			);
			$columns[] = array(
				'dt' => $i++,
				'alias' => 'player_username',
				'select' => 'player_username',
				'name' => lang("report.pr01"),
				'formatter' => function ($d) use ($is_export) {
					if ($is_export) {
						return !empty($d) ? $d: lang('lang.norecyet');
					} else {
						return !empty($d) ? $d : '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
					}
				},
			);
			$columns[] = array(
				'dt' => $i++,
				'alias' => 'player_level',
				'select' =>'level_name',
				'name' => lang("report.pr03"),
				'formatter' => function ($d) use ($is_export) { 
					return lang($d);
				},
			);
			$columns[] = array(
				'dt' => $i++,
				'alias' => 'registered_by',
				'select' => 'registered_by',
				'name' => lang("report.pr05"),
				'formatter' => function ($d) use ($is_export) {
					if ($is_export) {
						return !empty($d) ? $d: lang('lang.norecyet');
					} else {
						return !empty($d) ? $d : '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
					}
				},
			);
			$columns[] = array(
				'dt' => $i++,
				'alias' => 'total_deposit_bonus',
				'select' =>'deposit_bonus',
				'name' => lang("report.pr15"),
				'formatter' => function ($d) use ($is_export) {
						//return !empty($d) ? $d : '0'; 
					    return $this->currencyFormatter($d, $is_export);					
				},

			 );
			$columns[] = array(
				'dt' => $i++,
				'alias' => 'total_cashback_bonus',
				'select' =>'total_cashback',
				'name' => lang("report.pr16"),
				'formatter' => function ($d) use ($is_export) {
					return $this->utils->formatCurrencyNoSym($d); 
				},
			);
			$columns[] = array(
				'dt' => $i++,
				'alias' => 'total_referral_bonus',
				'select' => 'referral_bonus',
				'name' => lang("report.pr17"),
				'formatter' => function ($d) use ($is_export) {
					return $this->utils->formatCurrencyNoSym($d);
				},
			);
			$columns[] = array(
				'dt' => $i++,
				'alias' => 'total_manual_bonus',
				'select' =>'manual_bonus',
				'name' => lang("report.pr17"),
				'formatter' => function ($d) use ($is_export) {
					return $this->utils->formatCurrencyNoSym($d);
				},
			);
			$columns[] = array(
                'dt' => $i++,
                'alias' => 'total_first_deposit',
                'select' => 'first_deposit_amount',
                'name' => lang("report.pr19"),
                'formatter' => function ($d) use ($is_export) {
					return $this->utils->formatCurrencyNoSym($d);
				},
            );
			$columns[] = array(
				'dt' => $i++,
				'alias' => 'total_second_deposit',
				'select' => 'second_deposit_amount',
				'name' => lang("report.pr20"),
				'formatter' => function ($d) use ($is_export) {
					return $this->utils->formatCurrencyNoSym($d);
				},
			);
			$columns[] = array(
				'dt' => $i++,
				'alias' => 'total_deposit',
				'select' => 'total_deposit',
				'name' => lang("report.pr21"),
				'formatter' => function ($d) use ($is_export) {
					return $this->utils->formatCurrencyNoSym($d);
				},
			);
			$columns[] = array(
				'dt' => $i++,
				'alias' => 'total_withdraw',
				'select' => 'total_withdrawal',
				'name' => lang('report.pr22'),
				'formatter' => function ($d) use ($is_export) {
					return $this->utils->formatCurrencyNoSym($d);
				},
			);
			
			# END DEFINE COLUMNS #################################################################################################################################################

			$joins = array();
			# START PROCESS SEARCH FORM #################################################################################################################################################
			$where = array();
			$values = array();
			if (isset($input['username'])) {
				$username = $input['username'];
				if(!empty($username)){
					$where[] = "player_username = ?";
					$values[] = $username;
				}			
			}

			$date_from = null;
			$date_to = null;
			if (isset($input['date_from'], $input['date_to'])) {
				$date_from = $input['date_from'];
				$date_to = $input['date_to'];
			}

			if (!empty($date_from) && !empty($date_to)) {
				$where[] = "created_at BETWEEN ? AND ?";
				$values[] = $date_from;
				$values[] = $date_to;
			}

			if (isset($input['currency'])) {
				$currency = $input['currency'];
				if(!empty($currency)){
					$where[] = "currency_key = ?";
					$values[] = $currency;
				}
			}
			# END PROCESS SEARCH FORM #################################################################################################################################################
			$table = 'player_report_hourly';

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
			$countOnlyField='player_report_hourly.id';
	
			
			$this->benchmark->mark('get_data_start');
			$result = $this->data_tables->get_data($request, $columns, $table, $where, $values, $joins,
						$group_by, $having, $distinct, $external_order, $not_datatable, $countOnlyField);
			$this->benchmark->mark('get_data_end');

			$this->benchmark->mark('get_totals_start');
			$sub_summary = $this->data_tables->summary($request, $table, $joins, 'SUM(deposit_bonus), SUM(total_cashback) total_cashback, SUM(referral_bonus) referral_bonus, SUM(manual_bonus) manual_bonus, SUM(first_deposit_amount) first_deposit_amount, SUM(second_deposit_amount) second_deposit_amount, SUM(total_deposit) total_deposit, SUM(total_withdrawal) total_withdrawal', 'currency_key', $columns, $where, $values);
			$this->benchmark->mark('get_totals_end');

			$this->utils->debug_log('player_super_reports get_data', $this->benchmark->elapsed_time('get_data_start', 'get_data_end'));
			$this->utils->debug_log('player_super_reports get_totals', $this->benchmark->elapsed_time('get_totals_start', 'get_totals_end'));
 		
			$to_format_fields_values = [
				'deposit_bonus',
				'total_cashback',
				'referral_bonus',
				'manual_bonus',
				'first_deposit_amount',
				'second_deposit_amount',
				'total_deposit',
				'total_withdrawal',
			];
			if(!empty($sub_summary)){
				$sub_summary = $sub_summary[0];
				foreach ($sub_summary as $key => &$value) {
					if (in_array($key, $to_format_fields_values)){
						$sub_summary[$key]=number_format(round($value,2),2);
					}else{
						$sub_summary[$key]=number_format($value);
					}
				}
			}else{
				$sub_summary = [
				'deposit_bonus'=> '0.00',
				'total_cashback'=> '0.00',
				'referral_bonus'=> '0.00',
				'manual_bonus' => '0.00',
				'first_deposit_amount'=> '0.00',
				'second_deposit_amount'=> '0.00',
				'total_deposit'=> '0.00',
				'total_withdrawal'=> '0.00',
				];
			}

			$result['sub_summary'] = $sub_summary;

			return $result;	
	}

	/**
	 * detail: get super player reports
	 *
	 * @param array $request
	 * @param Boolean $is_export
	 *
	 * @return array
	 */
	public function super_summary_report($request, $is_export = false) {
		$readOnlyDB = $this->getReadOnlyDB();
		$this->load->library('super_report_lib');
		$this->load->library('data_tables', array("DB" => $readOnlyDB));
		$this->load->helper(['player_helper']);

		# START DEFINE COLUMNS #################################################################################################################################################
		$input = $this->data_tables->extra_search($request);
		$is_month_only = isset($input['month_only'])&& $input['month_only'] == 'on' ? true : false;
		if (isset($input['currency'])) {
			$currency = $input['currency'];
		}else{
			$currency = '';
		}
		$decimals_config = $this->super_report_lib->getDecimalsConfigByCurrency($currency);
		$is_super = (isset($input['currency']) && strtolower($input['currency']) == 'super');
		$master_currency = strtolower($this->super_report_lib->getMasterCurrencyCode());
		$daily_select_array = [
			'data_id' => 'srd.id',
			'currency' => 'srd.currency_key',
			'report_date' => 'srd.summary_date',
			'new_players' => $is_super ? 'max(srd.count_new_player)':'srd.count_new_player',
			'total_players' => $is_super ? 'max(srd.count_all_players)':'srd.count_all_players',
			'first_deposit_count' => $is_super ? 'sum(srd.count_first_deposit)':'srd.count_first_deposit',
			'total_deposit_players' => $is_super ? 'sum(srd.count_deposit_member)':'srd.count_deposit_member',
			'second_deposit_count' => $is_super ? 'sum(srd.count_second_deposit)':'srd.count_second_deposit',
			'total_deposit' => $is_super ? 'sum(srd.total_deposit * rate)':'srd.total_deposit',
			'total_withdraw' => $is_super ? 'sum(srd.total_withdrawal * rate)':'srd.total_withdrawal',
			'total_bonus' => $is_super ? 'sum(srd.total_bonus * rate)':'srd.total_bonus' ,
			'total_cashback' => $is_super ? 'sum(srd.total_cashback * rate)':'srd.total_cashback',
			'percentage_of_bonus_cashback_bet' => $is_super ? 'IF((sum(srd.total_bet)=0), 0,((sum(srd.total_bonus * rate) + sum(srd.total_cashback * rate)) / sum(srd.total_bet * rate)))':'IF((srd.total_bet=0), 0, ((srd.total_bonus + srd.total_cashback) / srd.total_bet))',
			'total_player_fee' => $is_super ? 'sum(srd.total_player_fee * rate)':'srd.total_player_fee',
			'total_withdrawal_fee_from_player' => $is_super ? 'sum(srd.total_withdrawal_fee_from_player * rate)':'srd.total_withdrawal_fee_from_player',
			'total_fee' => $is_super ? 'sum(srd.total_fee * rate)':'srd.total_fee',
			'total_bank_cash_amount' =>  $is_super ? 'sum(srd.total_bank_cash_amount * rate)':'srd.total_bank_cash_amount',
			'total_bet' => $is_super ? 'sum(srd.total_bet * rate)':'srd.total_bet',
			'total_win' => $is_super ? 'sum(srd.total_win * rate)':'srd.total_win',
			'total_loss' => $is_super ? 'sum(srd.total_loss * rate)':'srd.total_loss',
			'gross_payout' => $is_super ? 'sum((srd.total_win * rate) - (srd.total_loss * rate))':'srd.total_win - srd.total_loss',
			'count_deposit_member' => $is_super ? 'sum(srd.count_deposit_member)':'srd.count_deposit_member',
			'count_active_member' => $is_super ? 'sum(srd.count_active_member)':'srd.count_active_member',
			'retention' => $is_super ? 'IF((sum(last_srd.count_active_member)=0), 0,(( sum(srd.count_active_member) - sum(srd.count_first_deposit * rate)) / sum(last_srd.count_active_member)))':'IF((last_srd.count_active_member=0), 0, ((srd.count_active_member - srd.count_first_deposit ) / last_srd.count_active_member))',
			'ret_dp' => $is_super ? 'IF((sum(last_srd.count_deposit_member)=0), 0,(( sum(srd.count_deposit_member) - sum(srd.count_first_deposit * rate)) / sum(last_srd.count_deposit_member)))':'IF((last_srd.count_deposit_member=0), 0, ((srd.count_deposit_member - srd.count_first_deposit ) / last_srd.count_deposit_member))',
		];
		$monthly_select_array = [
			'data_id' => 'srm.id',
			'currency' => 'srm.currency_key',
			'report_date' => 'DATE_FORMAT(CONCAT(srm.summary_trans_year_month,"01"), "%Y-%m")',
			'new_players' => $is_super ? 'max(srm.count_new_player)':'srm.count_new_player',
			'total_players' => 'max(srm.count_all_players)',
			'first_deposit_count' => $is_super ? 'sum(srm.count_first_deposit)' :'srm.count_first_deposit',
			'total_deposit_players' => $is_super ? 'sum(srm.count_deposit_member)':'srm.count_deposit_member',
			'second_deposit_count' => $is_super ? 'sum(srm.count_second_deposit)':'srm.count_second_deposit',
			'total_deposit' => $is_super ? 'sum(srm.total_deposit * rate)':'srm.total_deposit',
			'total_withdraw' => $is_super ? 'sum(srm.total_withdrawal * rate)':'srm.total_withdrawal',
			'total_bonus' => $is_super ? 'sum(srm.total_bonus * rate)':'srm.total_bonus',
			'total_cashback' => $is_super ? 'sum(srm.total_cashback * rate)':'srm.total_cashback',
			'percentage_of_bonus_cashback_bet' => $is_super ? 'IF((sum(srm.total_bet)=0), 0,((sum(srm.total_bonus * rate) + sum(srm.total_cashback * rate)) / sum(srm.total_bet * rate)))':'IF((srm.total_bet=0), 0, ((srm.total_bonus + srm.total_cashback) / srm.total_bet))',
			'total_player_fee' => $is_super ? 'sum(srm.total_player_fee * rate)':'srm.total_player_fee',
			'total_withdrawal_fee_from_player' => $is_super ? 'sum(srm.total_withdrawal_fee_from_player * rate)':'srm.total_withdrawal_fee_from_player',
			'total_fee' => $is_super ? 'sum(srm.total_fee * rate)':'srm.total_fee',
			'total_bank_cash_amount' => $is_super ? 'sum(srm.total_bank_cash_amount * rate)':'srm.total_bank_cash_amount',
			'total_bet' => $is_super ? 'sum(srm.total_bet * rate)':'srm.total_bet',
			'total_win' => $is_super ? 'sum(srm.total_win * rate)':'srm.total_win',
			'total_loss' => $is_super ? 'sum(srm.total_loss * rate)':'srm.total_loss',
			'gross_payout' => $is_super ? 'sum((srm.total_win * rate) - (srm.total_loss * rate))':'srm.total_win - srm.total_loss',
			'count_deposit_member' => $is_super ? 'sum(srm.count_deposit_member)':'srm.count_deposit_member',
			'count_active_member' => $is_super ? 'sum(srm.count_active_member)':'srm.count_active_member',
			'retention' => $is_super ? 'IF((sum(last_srm.count_active_member)=0), 0,(( sum(srm.count_active_member) - sum(srm.count_first_deposit * rate)) / sum(last_srm.count_active_member)))':'IF((last_srm.count_active_member=0), 0, ((srm.count_active_member - srm.count_first_deposit ) / last_srm.count_active_member))',
			'ret_dp' => $is_super ? 'IF((sum(last_srm.count_deposit_member)=0), 0,(( sum(srm.count_deposit_member) - sum(srm.count_first_deposit * rate)) / sum(last_srm.count_deposit_member)))':'IF((last_srm.count_deposit_member=0), 0, ((srm.count_deposit_member - srm.count_first_deposit ) / last_srm.count_deposit_member))',
		];

		$i = 0;
		$columns = array();
			$columns[] =  array(
				'select' => $is_month_only ? $monthly_select_array['data_id'] : $daily_select_array['data_id'],
				'alias' => 'data_id',
				'name' => lang('lang.action'),
			);
			$columns[] = array(
				'dt' => $i++,
				'alias' => 'currency',
				'select' => $is_month_only ? $monthly_select_array['currency'] : $daily_select_array['currency'],
				'name' => lang('Currency'),
				'formatter' => function ($d) use ($is_export, $is_super, $master_currency) {
					if($is_super){
						return strtoupper($master_currency);
					}else{
						return $d;
					}
				},
			);
			$columns[] = array(
				'dt' => $i++,
				'alias' => 'report_date',
				'select' => $is_month_only ? $monthly_select_array['report_date'] : $daily_select_array['report_date'],
				'name' => lang("report.sum02"),
				'formatter' => function ($d) use ($is_export) {
					if ($is_export) {
						return !empty($d) ? $d: lang('lang.norecyet');
					} else {
						return !empty($d) ? $d : '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
					}
				},
			);
			$columns[] = array(
				'dt' => $i++,
				'alias' => 'new_players',
				'select' => $is_month_only ? $monthly_select_array['new_players'] : $daily_select_array['new_players'],
				'name' => lang("report.sum05"),
				'formatter' => function ($d) use ($is_export) {
					return number_format($d);
				},
			);
			$columns[] = array(
				'dt' => $i++,
				'alias' => 'total_players',
				'select' => $is_month_only ? $monthly_select_array['total_players'] : $daily_select_array['total_players'],
				'name' => lang("report.sum06"),
				'formatter' => function ($d) use ($is_export) {
					return number_format($d);
				},
			);
			$columns[] = array(
				'dt' => $i++,
				'alias' => 'first_deposit_count',
				'select' => $is_month_only ? $monthly_select_array['first_deposit_count'] : $daily_select_array['first_deposit_count'],
				'name' => lang("report.sum07"),
				'formatter' => function ($d) use ($is_export) {
					return number_format($d);
				},
			);
			$columns[] = array(
				'dt' => $i++,
				'alias' => 'second_deposit_count',
				'select' => $is_month_only ? $monthly_select_array['second_deposit_count'] : $daily_select_array['second_deposit_count'],
				'name' => lang("report.sum08"),
				'formatter' => function ($d) use ($is_export) {
					return number_format($d);
				},
			);
			$columns[] = array(
				'dt' => $i++,
				'alias' => 'total_deposit_players',
				'select' => $is_month_only ? $monthly_select_array['total_deposit_players'] : $daily_select_array['total_deposit_players'],
				'name' => lang("report.sum22"),
				'formatter' => function ($d) use ($is_export) {
					return number_format($d);
				},
			);
			$columns[] = array(
				'dt' => $i++,
				'alias' => 'total_deposit',
				'select' => $is_month_only ? $monthly_select_array['total_deposit'] : $daily_select_array['total_deposit'],
				'name' => lang("report.sum09"),
				'formatter' => function ($d) use ($is_export) {
					return $this->utils->formatCurrencyNoSym($d);
				},
			);
			$columns[] = array(
				'dt' => $i++,
				'alias' => 'total_withdraw',
				'select' => $is_month_only ? $monthly_select_array['total_withdraw'] : $daily_select_array['total_withdraw'],
				'name' => lang("report.sum10"),
				'formatter' => function ($d) use ($is_export) {
					return $this->utils->formatCurrencyNoSym($d);
				},
			);
			$columns[] = array(
                'dt' => $i++,
                'alias' => 'total_bonus',
                'select' => $is_month_only ? $monthly_select_array['total_bonus'] : $daily_select_array['total_bonus'],
                'name' => lang("report.sum14"),
                'formatter' => function ($d) use ($is_export) {
					return $this->utils->formatCurrencyNoSym($d);
				},
            );
			$columns[] = array(
				'dt' => $i++,
				'alias' => 'total_cashback',
				'select' => $is_month_only ? $monthly_select_array['total_cashback'] : $daily_select_array['total_cashback'],
				'name' => lang("report.sum15"),
				'formatter' => function ($d) use ($is_export) {
					return $this->utils->formatCurrencyNoSym($d);
				},
			);
			$columns[] = array(
				'dt' => $i++,
				'alias' => 'percentage_of_bonus_cashback_bet',
				'select' => $is_month_only ? $monthly_select_array['percentage_of_bonus_cashback_bet'] : $daily_select_array['percentage_of_bonus_cashback_bet'],
				'name' => lang("report.percentage_of_bonus_cashback_bet"),
				'formatter' => function ($d) use ($is_export, $decimals_config) {
					return number_format(round($d * 100, $decimals_config), $decimals_config). '%';
				},
			);
			$columns[] = array(
				'dt' => $i++,
				'alias' => 'total_player_fee',
				'select' => $is_month_only ? $monthly_select_array['total_player_fee'] : $daily_select_array['total_player_fee'],
				'name' => lang("report.sum16"),
				'formatter' => function ($d) use ($is_export) {
					return $this->utils->formatCurrencyNoSym($d);
				},
			);
			$columns[] = array(
				'dt' => $i++,
				'alias' => 'total_withdrawal_fee_from_player',
				'select' => $is_month_only ? $monthly_select_array['total_withdrawal_fee_from_player'] : $daily_select_array['total_withdrawal_fee_from_player'],
				'name' => lang("report.sum16"),
				'formatter' => function ($d) use ($is_export) {
					return $this->utils->formatCurrencyNoSym($d);
				},
			);
			$columns[] = array(
				'dt' => $i++,
				'alias' => 'total_fee',
				'select' => $is_month_only ? $monthly_select_array['total_fee'] : $daily_select_array['total_fee'],
				'name' => lang("report.sum16"),
				'formatter' => function ($d) use ($is_export) {
					return $this->utils->formatCurrencyNoSym($d);
				},
			);
			$columns[] = array(
				'dt' => $i++,
				'alias' => 'total_bank_cash_amount',
				'select' => $is_month_only ? $monthly_select_array['total_bank_cash_amount'] : $daily_select_array['total_bank_cash_amount'],
				'name' => lang('report.sum18'),
				'formatter' => function ($d) use ($is_export) {
					return $this->utils->formatCurrencyNoSym($d);
				},
			);
			$columns[] = array(
				'dt' => $i++,
				'alias' => 'total_bet',
				'select' => $is_month_only ? $monthly_select_array['total_bet'] : $daily_select_array['total_bet'],
				'name' => lang('Total Bet'),
				'formatter' => function ($d) use ($is_export) {
					return $this->utils->formatCurrencyNoSym($d);
				},
			);
			$columns[] = array(
				'dt' => $i++,
				'alias' => 'total_win',
				'select' => $is_month_only ? $monthly_select_array['total_win'] : $daily_select_array['total_win'],
				'name' => lang('Total Win'),
				'formatter' => function ($d) use ($is_export) {
					return $this->utils->formatCurrencyNoSym($d);
				},
			);
			$columns[] = array(
				'dt' => $i++,
				'alias' => 'total_loss',
				'select' => $is_month_only ? $monthly_select_array['total_loss'] : $daily_select_array['total_loss'],
				'name' => lang('Total Loss'),
				'formatter' => function ($d) use ($is_export) {
					return $this->utils->formatCurrencyNoSym($d);
				},
			);
			$columns[] = array(
				'dt' => $i++,
				'alias' => 'gross_payout',
				'select' => $is_month_only ? $monthly_select_array['gross_payout'] : $daily_select_array['gross_payout'],
				'name' => lang('Gross Payout'),
				'formatter' => function ($d) use ($is_export) {
					return $this->utils->formatCurrencyNoSym($d);
				},
			);
			$columns[] = array(
				'dt' => $i++,
				'alias' => 'count_deposit_member',
				'select' => $is_month_only ? $monthly_select_array['count_deposit_member'] : $daily_select_array['count_deposit_member'],
				'name' => lang('Gross Payout'),
				'formatter' => function ($d) use ($is_export) {
					return number_format($d);
				},
			);
			$columns[] = array(
				'dt' => $i++,
				'alias' => 'count_active_member',
				'select' => $is_month_only ? $monthly_select_array['count_active_member'] : $daily_select_array['count_active_member'],
				'name' => lang('Gross Payout'),
				'formatter' => function ($d) use ($is_export) {
					return number_format($d);
				},
			);
			$columns[] = array(
				'dt' => $i++,
				'alias' => 'retention',
				'select' => $is_month_only ? $monthly_select_array['retention'] : $daily_select_array['retention'],
				'name' => lang('Retention'),
				'formatter' => function ($d) use ($is_export, $decimals_config) {
					return number_format(round($d * 100, $decimals_config), $decimals_config). '%';
				},
			);
			$columns[] = array(
				'dt' => $i++,
				'alias' => 'ret_dp',
				'select' => $is_month_only ? $monthly_select_array['ret_dp'] : $daily_select_array['ret_dp'],
				'name' => lang('ret_dp'),
				'formatter' => function ($d) use ($is_export, $decimals_config) {
					return number_format(round($d * 100, $decimals_config), $decimals_config). '%';
				},
			);

			# END DEFINE COLUMNS #################################################################################################################################################
			if($is_month_only){
				$table = 'summary2_report_monthly srm';
				$joins = array(
					'summary2_report_monthly AS last_srm' => 'DATE_FORMAT( DATE_SUB( CONCAT(srm.summary_trans_year_month,"01"), INTERVAL 1 MONTH), "%Y%m" )  = last_srm.summary_trans_year_month AND srm.currency_key = last_srm.currency_key',
				);
				if($is_super){
					$joins['currency_conversion_rate'] = "resource_currency = srm.currency_key and target_currency = '{$master_currency}'";
				}
			}else{
				$table = 'summary2_report_daily srd';
				$joins = array(
					'summary2_report_daily last_srd' => 'DATE_FORMAT( DATE_SUB( srd.summary_date, INTERVAL 1 DAY), "%Y-%m-%d" ) = last_srd.summary_date AND srd.currency_key = last_srd.currency_key',
				);
				if($is_super){
					$joins['currency_conversion_rate'] = "resource_currency = srd.currency_key and target_currency = '{$master_currency}'";
				}
			}

			# START PROCESS SEARCH FORM #################################################################################################################################################
			$where = array();
			$values = array();

			$date_from = null;
			$date_to = null;
			if (isset($input['date_from'], $input['date_to'])) {
				$date_from = $input['date_from'];
				$date_to = $input['date_to'];
			}

			if (!empty($currency) && !$is_super) {
				if($is_month_only){
					$where[] = "srm.currency_key = ?";
					$values[] = $currency;
				}else{
					$where[] = "srd.currency_key = ?";
					$values[] = $currency;
				}
			}

			if (!empty($date_from) && !empty($date_to)) {
				if($is_month_only){
					$date_from = str_replace('-', '', substr($date_from, 0, 7));
    				$date_to = str_replace('-', '', substr($date_to, 0, 7));
					$where[] = "srm.summary_trans_year_month BETWEEN ? AND ?";
					$values[] = $date_from;
					$values[] = $date_to;
				}else{
					$where[] = "srd.summary_date BETWEEN ? AND ?";
					$values[] = $date_from;
					$values[] = $date_to;
				}
			}
			# END PROCESS SEARCH FORM #################################################################################################################################################
			if($is_export){
				if (isset($input['currency']) && isset($input['exportSelectedColumns'.strtolower($input['currency'])])){
					$exportSelectedColumns = $input['exportSelectedColumns'.strtolower($input['currency'])];
					$columns = $this->getSelectedColumns(explode(",", $exportSelectedColumns), $columns);
				}
            	$this->data_tables->options['is_export']=true;
	            if(empty($csv_filename)){
	                $csv_filename=$this->utils->create_csv_filename(__FUNCTION__);
	            }
	            $this->data_tables->options['csv_filename']=$csv_filename;
			}

			if($is_month_only){
				$group_by = ['srm.summary_trans_year_month'];
			}else{
				$group_by = ['srd.summary_date'];
			}

			$having=[];
			$distinct=false;
			$external_order=[];
			$not_datatable='';

			if($is_month_only){
				$countOnlyField=($is_super) ? null: 'srm.id';
			}else{
				$countOnlyField=($is_super) ? null: 'srd.id';
			}
			
			$this->benchmark->mark('get_data_start');
			$result = $this->data_tables->get_data($request, $columns, $table, $where, $values, $joins, $group_by, $having, $distinct, $external_order, $not_datatable, $countOnlyField);
			$this->benchmark->mark('get_data_end');
		
            $this->benchmark->mark('get_totals_start');
			$summary_daily_select_array = [
				'count_new_player' => $is_super ? "" : 'SUM(srd.count_new_player) count_new_player',
				// 'count_all_players' => 'MAX(srd.count_all_players) count_all_players',
				'count_first_deposit' => 'SUM(srd.count_first_deposit) count_first_deposit',
				'count_second_deposit' => 'SUM(srd.count_second_deposit) count_second_deposit',
				'count_deposit_member' => 'SUM(srd.count_deposit_member) count_deposit_member',
				'total_deposit' => $is_super ? 'SUM(srd.total_deposit * rate) total_deposit':'SUM(srd.total_deposit) total_deposit',
				'total_withdrawal' => $is_super ? 'SUM(srd.total_withdrawal * rate) total_withdrawal':'SUM(srd.total_withdrawal) total_withdrawal',
				'total_bonus' => $is_super ? 'SUM(srd.total_bonus * rate) total_bonus':'SUM(srd.total_bonus) total_bonus',
				'total_cashback' => $is_super ? 'SUM(srd.total_cashback * rate) total_cashback':'SUM(srd.total_cashback) total_cashback',
				'total_player_fee' => $is_super ? 'SUM(srd.total_player_fee * rate) total_player_fee':'SUM(srd.total_player_fee) total_player_fee',
				'total_withdraw_fee_from_player' => $is_super ? 'SUM(srd.total_withdrawal_fee_from_player * rate) total_withdrawal_fee_from_player':'SUM(srd.total_withdrawal_fee_from_player) total_withdrawal_fee_from_player',
				'total_fee' => $is_super ? 'SUM(srd.total_fee * rate) total_fee':'SUM(srd.total_fee) total_fee',
				'total_bank_cash_amount' => $is_super ? 'SUM(srd.total_bank_cash_amount * rate) total_bank_cash_amount':'SUM(srd.total_bank_cash_amount) total_bank_cash_amount',
				'total_bet' => $is_super ? 'SUM(srd.total_bet * rate) total_bet':'SUM(srd.total_bet) total_bet',
				'total_win' => $is_super ? 'SUM(srd.total_win * rate) total_win':'SUM(srd.total_win) total_win',
				'total_loss' => $is_super ? 'SUM(srd.total_loss * rate) total_loss':'SUM(srd.total_loss) total_loss',
				'total_payout' => $is_super ? 'SUM(srd.total_win * rate) - SUM(srd.total_loss * rate) total_payout':'SUM(srd.total_win - srd.total_loss) total_payout',
				'count_active_member' => 'SUM(srd.count_active_member) count_active_member',
			];

			$summary_month_select_array = [
				'count_new_player' => $is_super ? "" : 'SUM(srm.count_new_player) count_new_player',
				// 'count_all_players' => 'MAX(srm.count_all_players) count_all_players',
				'count_first_deposit' => 'SUM(srm.count_first_deposit) count_first_deposit',
				'count_second_deposit' => 'SUM(srm.count_second_deposit) count_second_deposit',
				'count_deposit_member' => 'SUM(srm.count_deposit_member) count_deposit_member',
				'total_deposit' => $is_super ? 'SUM(srm.total_deposit * rate) total_deposit':'SUM(srm.total_deposit) total_deposit',
				'total_withdrawal' => $is_super ? 'SUM(srm.total_withdrawal * rate) total_withdrawal':'SUM(srm.total_withdrawal) total_withdrawal',
				'total_bonus' => $is_super ? 'SUM(srm.total_bonus * rate) total_bonus':'SUM(srm.total_bonus) total_bonus',
				'total_cashback' => $is_super ? 'SUM(srm.total_cashback * rate) total_cashback':'SUM(srm.total_cashback) total_cashback',
				'total_player_fee' => $is_super ? 'SUM(srm.total_player_fee * rate) total_player_fee':'SUM(srm.total_player_fee) total_player_fee',
				'total_withdraw_fee_from_player' => $is_super ? 'SUM(srm.total_withdrawal_fee_from_player * rate) total_withdrawal_fee_from_player':'SUM(srm.total_withdrawal_fee_from_player) total_withdrawal_fee_from_player',
				'total_fee' => $is_super ? 'SUM(srm.total_fee * rate) total_fee':'SUM(srm.total_fee) total_fee',
				'total_bank_cash_amount' => $is_super ? 'SUM(srm.total_bank_cash_amount * rate) total_bank_cash_amount':'SUM(srm.total_bank_cash_amount) total_bank_cash_amount',
				'total_bet' => $is_super ? 'SUM(srm.total_bet * rate) total_bet':'SUM(srm.total_bet) total_bet',
				'total_win' => $is_super ? 'SUM(srm.total_win * rate) total_win':'SUM(srm.total_win) total_win',
				'total_loss' => $is_super ? 'SUM(srm.total_loss * rate) total_loss':'SUM(srm.total_loss) total_loss',
				'total_payout' => $is_super ? 'SUM(srm.total_win * rate) - SUM(srm.total_loss * rate) total_payout':'SUM(srm.total_win - srm.total_loss) total_payout',
				'count_active_member' => 'SUM(srm.count_active_member) count_active_member',
			];

			if($is_month_only){
				$select = implode(",", $summary_month_select_array);
			}else{
				$select = implode(",", $summary_daily_select_array);
			}

			if(!$is_export){
				$result['cols_names_aliases'] = $this->get_dt_column_names_and_aliases($columns);
			}	

			$sub_summary = $this->data_tables->summary($request, $table, $joins, $select, '', $columns, $where, $values);

			if(!empty($sub_summary)){
				$sub_summary[0]['percentage_of_bonus_cashback_bet'] = $sub_summary[0]['total_bet'] > 0 ? (($sub_summary[0]['total_bonus']+$sub_summary[0]['total_cashback'])/$sub_summary[0]['total_bet']) : 0;
				$sub_summary[0]['total_retention'] = $this->get_total_retention($is_month_only, $is_super, $date_from, $date_to, $currency);
				$sub_summary[0]['total_ret_dp'] = $this->get_total_ret_dp($is_month_only, $is_super, $date_from, $date_to, $currency);
			}

		// 	if ($this->utils->getConfig('enabled_count_distinct_total_active_members')) {
		// 		$this->load->model(['total_player_game_hour']);
		// 		$distinct_active_members = $this->total_player_game_hour->getDistinctTotalActiveMembers($dateFrom, $dateTo);
		// 		$this->utils->printLastSQL();
		// 		$this->utils->debug_log(__METHOD__, 'distinct_active_members ',$distinct_active_members);
		// 		$output['distinct_active_members'] = $distinct_active_members;
		// 	}

		// 	if ($this->utils->getConfig('enabled_count_distinct_deposit_members')) {
		// 		$date['dateFrom'] = $dateFrom;
		// 		$date['dateTo'] = $dateTo;
		// 		$distinct_deposit_members = $this->report_model->get_count_deposit_member('DATE' ,$date);
		// 		$this->utils->printLastSQL();
		// 		$this->utils->debug_log(__METHOD__, 'distinct_deposit_members ',$distinct_deposit_members);
		// 		$output['distinct_deposit_members'] = $distinct_deposit_members;
		// 	}
            $this->benchmark->mark('get_totals_end');

			$this->utils->debug_log('summary2_report_daily get_data', $this->benchmark->elapsed_time('get_data_start', 'get_data_end'));
			$this->utils->debug_log('summary2_report_daily get_totals', $this->benchmark->elapsed_time('get_totals_start', 'get_totals_end'));

		    $to_format_fields_values = [
				'total_player_fee' => true,
		    	'total_bonus' => true,
		    	'total_cashback' => true,
		    	'total_deposit' => true,
		    	'total_fee' => true,
		    	'total_withdrawal' => true,
		    	'total_bank_cash_amount' => true,
		    	'total_bet' => true,
		    	'total_win' => true,
		    	'total_loss' => true,
		    	'gross_payout' => true,
				'total_withdraw_fee_from_player' => true,
				'total_retention' => true,
				'total_ret_dp' => true,
				'percentage_of_bonus_cashback_bet' => true,
		    ];

			$to_percentage_fields_values = [
				'percentage_of_bonus_cashback_bet' => true,
				'total_retention' => true,
				'total_ret_dp' => true,
			];

			if(!empty($sub_summary)){
		   		$sub_summary = $sub_summary[0];
				$this->load->library('super_report_lib');
		   		foreach ($sub_summary as $key => &$value) {
					if(isset($to_percentage_fields_values[$key])){
						$sub_summary[$key] = $sub_summary[$key] * 100;
					}
					if (isset($to_format_fields_values[$key])){
						$decimals_config = $this->super_report_lib->getDecimalsConfigByCurrency($currency);
						$sub_summary[$key]=number_format(round($value, $decimals_config), $decimals_config);
					}else{
						$sub_summary[$key]=number_format($value);
					}
				}	
		   }else{
				$sub_summary = [
					'count_new_player'=>$is_super ? '':'0',
					// 'count_all_players' => '0',
					'count_first_deposit' => '0',
					'count_second_deposit' => '0',
					'count_deposit_member' => '0',
					'total_bonus' =>'0.00',
					'total_cashback'=>'0.00',
					'total_deposit'=>'0.00',
					'total_player_fee' => '0.00',
					'total_fee'=>'0.00',
					'total_withdrawal'=>'0.00',
					'total_bank_cash_amount'=>'0.00',
					'total_bet'=>'0.00',
					'total_win'=>'0.00',
					'total_loss'=>'0.00',
					'gross_payout'=>'0.00',
					'count_active_member' => '0',
					'total_withdraw_fee_from_player' => '0.00',
					'percentage_of_bonus_cashback_bet' => '0.00%',
					'total_retention' => '0.00%',
					'total_ret_dp' => '0.00%',
				];
		   }		   		   
			
		   $result['sub_summary'] = $sub_summary;
		   return $result;
	}

	public function get_total_retention($is_month_only, $is_super = false,$dateFrom = null, $dateTo = null, $currency = null) {
		$readOnlyDB = $this->getReadOnlyDB();
		if($is_month_only){
			if($is_super){
				$this->load->library('super_report_lib');
				$masterCurrency = strtolower($this->super_report_lib->getMasterCurrencyCode());
				$readOnlyDB->select("IF((sum(last_srm.count_active_member)=0), 0,(( sum(srm.count_active_member) - sum(srm.count_first_deposit)) / sum(last_srm.count_active_member))) AS retention", false);
				$readOnlyDB->from('summary2_report_monthly srm');
				$readOnlyDB->join('currency_conversion_rate' , "resource_currency = srm.currency_key and target_currency = '{$masterCurrency}'", 'left');
				$readOnlyDB->join('summary2_report_monthly last_srm', 'DATE_FORMAT( DATE_SUB( CONCAT(srm.summary_trans_year_month,"01"), INTERVAL 1 MONTH), "%Y%m" )  = last_srm.summary_trans_year_month AND srm.currency_key = last_srm.currency_key', 'left');
				$readOnlyDB->group_by('srm.summary_trans_year_month');
			}else{
				$readOnlyDB->select("IF((last_srm.count_active_member=0), 0, ((srm.count_active_member - srm.count_first_deposit )/ last_srm.count_active_member)) AS retention", false);
				$readOnlyDB->from('summary2_report_monthly srm');
				$readOnlyDB->join('summary2_report_monthly last_srm', 'DATE_FORMAT( DATE_SUB( CONCAT(srm.summary_trans_year_month,"01"), INTERVAL 1 MONTH), "%Y%m" )  = last_srm.summary_trans_year_month AND srm.currency_key = last_srm.currency_key', 'left');
				$readOnlyDB->where("srm.currency_key", $currency);
			}

			if(!empty($dateFrom) && !empty($dateTo)) {
				$dateFrom = str_replace('-', '', substr($dateFrom, 0, 7));
				$dateTo = str_replace('-', '', substr($dateTo, 0, 7));
				$readOnlyDB->where("srm.summary_trans_year_month >=", $dateFrom);
				$readOnlyDB->where("srm.summary_trans_year_month <=", $dateTo);
			}

		}else{
			if($is_super){
				$this->load->library('super_report_lib');
				$master_currency = strtolower($this->super_report_lib->getMasterCurrencyCode());
				$readOnlyDB->select("IF((sum(last_srd.count_active_member)=0), 0,(( sum(srd.count_active_member) - sum(srd.count_first_deposit)) / sum(last_srd.count_active_member))) AS retention", false);
				$readOnlyDB->from('summary2_report_daily srd');
				$readOnlyDB->join('currency_conversion_rate' , "resource_currency = srd.currency_key and target_currency = '{$master_currency}'", 'left');
				$readOnlyDB->join('summary2_report_daily last_srd' , 'DATE_FORMAT( DATE_SUB( srd.summary_date, INTERVAL 1 DAY), "%Y-%m-%d" ) = last_srd.summary_date AND srd.currency_key = last_srd.currency_key', 'left');
				$readOnlyDB->group_by('srd.summary_date');
			}else{
				$readOnlyDB->select("IF((last_srd.count_active_member=0), 0, ((srd.count_active_member - srd.count_first_deposit )/ last_srd.count_active_member)) AS retention", false);
				$readOnlyDB->from('summary2_report_daily srd');
				$readOnlyDB->join('summary2_report_daily last_srd' , 'DATE_FORMAT( DATE_SUB( srd.summary_date, INTERVAL 1 DAY), "%Y-%m-%d" ) = last_srd.summary_date AND srd.currency_key = last_srd.currency_key', 'left');
				$readOnlyDB->where("srd.currency_key", $currency);
			}

			if (!empty($dateFrom) && !empty($dateTo)) {
				$readOnlyDB->where("srd.summary_date >=", $dateFrom);
				$readOnlyDB->where("srd.summary_date <=", $dateTo);
			}
		}

		$query = $readOnlyDB->get();
		$result = $query->result_array();
		$total_retention = 0;
		$count = 0;
		if(is_array($result) && count($result) >= 1){
			foreach ($result as $value) {
				if(isset($value['retention']) && $value['retention'] != 0){
					$count ++;
					$total_retention += $value['retention'];
				}
			}
			if($total_retention != 0 && $count != 0){
				return $total_retention / $count;
			}else{
				return 0;
			}
		}
	}

	public function get_total_ret_dp($is_month_only, $is_super = false,$dateFrom = null, $dateTo = null, $currency = null) {
		$readOnlyDB = $this->getReadOnlyDB();
		if($is_month_only){
			if($is_super){
				$this->load->library('super_report_lib');
				$masterCurrency = strtolower($this->super_report_lib->getMasterCurrencyCode());
				$readOnlyDB->select("IF((sum(last_srm.count_deposit_member)=0), 0,(( sum(srm.count_deposit_member) - sum(srm.count_first_deposit)) / sum(last_srm.count_deposit_member))) AS ret_dp", false);
				$readOnlyDB->from('summary2_report_monthly srm');
				$readOnlyDB->join('currency_conversion_rate' , "resource_currency = srm.currency_key and target_currency = '{$masterCurrency}'", 'left');
				$readOnlyDB->join('summary2_report_monthly last_srm', 'DATE_FORMAT( DATE_SUB( CONCAT(srm.summary_trans_year_month,"01"), INTERVAL 1 MONTH), "%Y%m" )  = last_srm.summary_trans_year_month AND srm.currency_key = last_srm.currency_key', 'left');
				$readOnlyDB->group_by('srm.summary_trans_year_month');
			}else{
				$readOnlyDB->select("IF((last_srm.count_deposit_member=0), 0, ((srm.count_deposit_member - srm.count_first_deposit ) / last_srm.count_deposit_member)) AS ret_dp", false);
				$readOnlyDB->from('summary2_report_monthly srm');
				$readOnlyDB->join('summary2_report_monthly last_srm', 'DATE_FORMAT( DATE_SUB( CONCAT(srm.summary_trans_year_month,"01"), INTERVAL 1 MONTH), "%Y%m" )  = last_srm.summary_trans_year_month AND srm.currency_key = last_srm.currency_key', 'left');
				$readOnlyDB->where("srm.currency_key", $currency);
			}

			if (!empty($dateFrom) && !empty($dateTo)) {
				$dateFrom = str_replace('-', '', substr($dateFrom, 0, 7));
				$dateTo = str_replace('-', '', substr($dateTo, 0, 7));
				$readOnlyDB->where("srm.summary_trans_year_month >=", $dateFrom);
				$readOnlyDB->where("srm.summary_trans_year_month <=", $dateTo);
			}

		}else{
			if($is_super){
				$this->load->library('super_report_lib');
				$master_currency = strtolower($this->super_report_lib->getMasterCurrencyCode());
				$readOnlyDB->select("IF((sum(last_srd.count_deposit_member)=0), 0,(( sum(srd.count_deposit_member) - sum(srd.count_first_deposit * rate)) / sum(last_srd.count_deposit_member))) AS ret_dp", false);
				$readOnlyDB->from('summary2_report_daily srd');
				$readOnlyDB->join('currency_conversion_rate' , "resource_currency = srd.currency_key and target_currency = '{$master_currency}'", 'left');
				$readOnlyDB->join('summary2_report_daily last_srd' , 'DATE_FORMAT( DATE_SUB( srd.summary_date, INTERVAL 1 DAY), "%Y-%m-%d" ) = last_srd.summary_date AND srd.currency_key = last_srd.currency_key', 'left');
				$readOnlyDB->group_by('srd.summary_date');
			}else{
				$readOnlyDB->select("IF((last_srd.count_deposit_member=0), 0, ((srd.count_deposit_member - srd.count_first_deposit ) / last_srd.count_deposit_member)) AS ret_dp", false);
				$readOnlyDB->from('summary2_report_daily srd');
				$readOnlyDB->join('summary2_report_daily last_srd' , 'DATE_FORMAT( DATE_SUB( srd.summary_date, INTERVAL 1 DAY), "%Y-%m-%d" ) = last_srd.summary_date AND srd.currency_key = last_srd.currency_key', 'left');
				$readOnlyDB->where("srd.currency_key", $currency);
			}

			if (!empty($dateFrom) && !empty($dateTo)) {
				$readOnlyDB->where("srd.summary_date >=", $dateFrom);
				$readOnlyDB->where("srd.summary_date <=", $dateTo);
			}
		}
		$query = $readOnlyDB->get();
		$result = $query->result_array();
		$total_ret_dp = 0;
		$count = 0;
		if(is_array($result) && count($result) >= 1){
			foreach ($result as $value) {
				if(isset($value['ret_dp']) && $value['ret_dp'] != 0){
					$count ++;
					$total_ret_dp += $value['ret_dp'];
				}
			}
			if($total_ret_dp != 0 && $count != 0){
				return $total_ret_dp / $count;
			}else{
				return 0;
			}
		}
	}

	/**
	 * detail: get super game reports
	 *
	 * @param array $request
	 * @param Boolean $is_export
	 *
	 * @return array
	 */
	public function super_game_report($request, $is_export = false) {
		$this->load->library('super_report_lib');
		$master_currency = strtolower($this->super_report_lib->getMasterCurrencyCode());
		$readOnlyDB = $this->getReadOnlyDB();

		$this->load->library('data_tables', array("DB" => $readOnlyDB));
		$this->load->helper(['player_helper']);

		# START DEFINE COLUMNS #################################################################################################################################################
		$input = $this->data_tables->extra_search($request);
		$is_super = false;
		if (isset($input['currency'])) {
			if(!empty($input['currency']) && strtolower($input['currency']) == "all"){
				$is_super = true;
			}
		}
		$group_by=[];
		if (isset($input['group_by']) && !empty($input['group_by'])) {
			switch ($input['group_by']) {
				case 'game_platform_id':
					$group_by[] = 'game_report_hourly.game_platform_id';
					$group_by_game_platform_id = true;
					$group_by_game_type_id = false;
					$group_by_player_id = false;
					$group_by_game = false;
					break;
				case 'game_type_id':
					$group_by[] = 'game_report_hourly.game_type_code';
					$group_by_game_platform_id = false;
					$group_by_game_type_id = true;
					$group_by_player_id = false;
					$group_by_game = false;
					break;
				case 'game':
					$group_by[] = 'game_report_hourly.external_game_id';
					$group_by_game_platform_id = false;
					$group_by_game_type_id = false;
					$group_by_player_id = false;
					$group_by_game = true;
					break;
				case 'player_id':
					$group_by[] = 'game_report_hourly.player_id';
					$group_by_game_platform_id = false;
					$group_by_game_type_id = false;
					$group_by_player_id = true;
					$group_by_game = false;
			break;
			}
		}

		$i = 0;
		$columns = array();
		$columns[] =  array(
			'select' => 'game_report_hourly.id',
			'alias' => 'data_id',
			'name' => lang('lang.action'),
		);
		$columns[] = array(
			'dt' => $i++,
			'alias' => 'currency_key',
			//'select' => 'currency',
			'select' => 'game_report_hourly.currency_key',
			'name' => lang('Currency'),
			'formatter' => function ($d) use ($is_export, $master_currency, $is_super) {
				if($is_super){
					if($master_currency){
						return strtoupper($master_currency);
					}
					return "USDT";
				}
				return $d;
			},
		);
		$columns[] = array(
			'dt' => $i++,
			'alias' => 'player_username',
			'select' => $group_by_player_id ? 'game_report_hourly.player_username' : null,
			'name' => lang("Player Username"),
			'formatter' => function ($d) use ($is_export) {
				if ($is_export) {
					return !empty($d) ? $d: lang('lang.norecyet');
				} else {
					return !empty($d) ? $d : '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
				}
			},
		);
		$columns[] = array(
			'dt' => $i++,
			'alias' => 'report_date',
			'select' => 'game_report_hourly.created_at' ,
			'name' => lang("report.sum02"),
			'formatter' => function ($d) use ($is_export) {
				if ($is_export) {
					return !empty($d) ? $d: lang('lang.norecyet');
				} else {
					return !empty($d) ? $d : '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
				}
			},
		);
		// $columns[] = array(
		// 	'dt' => $i++,
		// 	'alias' => 'game_platform_id',
		// 	'select' => 'game_platform_id',
		// 	'name' => lang("Game Platform Id"),
		// 	'formatter' => function ($d) use ($is_export) {
		// 		if ($is_export) {
		// 			return !empty($d) ? $d: lang('lang.norecyet');
		// 		} else {
		// 			return !empty($d) ? $d : '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
		// 		}
		// 	},
		// );
		$columns[] = array(
			'dt' => $i++,
			'alias' => 'game_platform_code',
			'select' => $group_by_game_platform_id ? 'game_report_hourly.game_platform_code' : null,
			'name' => lang("Game Provider"),
			'formatter' => function ($d) use ($is_export) {
				if ($is_export) {
					return !empty($d) ? $d: lang('lang.norecyet');
				} else {
					return !empty($d) ? $d : '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
				}
			},
		);
		$columns[] = array(
			'dt' => $i++,
			'alias' => 'game_type_code',
			'select' => $group_by_game_type_id ? 'game_report_hourly.game_type_code' : null,
			'name' => lang("Game Type Code"),
			'formatter' => function ($d) use ($is_export) {
				if ($is_export) {
					return !empty($d) ? $d: lang('lang.norecyet');
				} else {
					return !empty($d) ? $d : '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
				}
			},
		);
		$columns[] = array(
			'dt' => $i++,
			'alias' => 'game',
			'select' => $group_by_game ? 'game_description.english_name' : null,
			'name' => lang("Game"),
			'formatter' => function ($d) use ($is_export) {
				if ($is_export) {
					return !empty($d) ? $d: lang('lang.norecyet');
				} else {
					return !empty($d) ? $d : '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
				}
			},
		);
		$columns[] = array(
			'dt' => $i++,
			'alias' => 'total_player',
			'select' => !$group_by_player_id ? 'count(DISTINCT(game_report_hourly.player_username))' : null,
			'name' => lang("Total Player"),
			'formatter' => function ($d) use ($is_export) {
				if ($is_export) {
					return !empty($d) ? $d: lang('lang.norecyet');
				} else {
					return !empty($d) ? $d : '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
				}
			},
		);
		if($is_super){
			$columns[] = array(
				'dt' => $i++,
				'alias' => 'total_bet_amount',
				// 'select' => 'total_bet_amount',
				'select' => !empty($input['group_by']) ? 'sum(game_report_hourly.betting_amount) * (if(currency_conversion_rate.rate is null, 1, currency_conversion_rate.rate))' : 'game_report_hourly.betting_amount * (if(currency_conversion_rate.rate is null, 1, currency_conversion_rate.rate))',
				'name' => lang('Total Bet'),
				'formatter' => function ($d) use ($is_export) {
					return $this->utils->formatCurrencyNoSym($d);
				},
			);
			$columns[] = array(
				'dt' => $i++,
				'alias' => 'total_payout',
				// 'select' => 'total_bet_amount',
				'select' => !empty($input['group_by']) ? '(sum( game_report_hourly.betting_amount ) - (sum( game_report_hourly.loss_amount ) - sum( game_report_hourly.win_amount ))) * (if(currency_conversion_rate.rate is null, 1, currency_conversion_rate.rate))' : '(game_report_hourly.betting_amount - (game_report_hourly.loss_amount - game_report_hourly.win_amount )) * (if(currency_conversion_rate.rate is null, 1, currency_conversion_rate.rate))',
				'name' => lang('Agency Payout'),
				'formatter' => function ($d) use ($is_export) {
					return $this->utils->formatCurrencyNoSym($d);
				},
			);
			$columns[] = array(
				'dt' => $i++,
				'alias' => 'total_win_amount',
				'select' => !empty($input['group_by']) ? 'sum(game_report_hourly.win_amount) * (if(currency_conversion_rate.rate is null, 1, currency_conversion_rate.rate))' : 'game_report_hourly.win_amount * (if(currency_conversion_rate.rate is null, 1, currency_conversion_rate.rate))',
				'name' => lang('Total Win'),
				'formatter' => function ($d) use ($is_export) {
					return $this->utils->formatCurrencyNoSym($d);
				},
			);
			$columns[] = array(
				'dt' => $i++,
				'alias' => 'total_loss_amount',
				// 'select' => 'total_loss_amount',
				'select' => !empty($input['group_by']) ? 'sum(game_report_hourly.loss_amount) * (if(currency_conversion_rate.rate is null, 1, currency_conversion_rate.rate))' : 'game_report_hourly.loss_amount * (if(currency_conversion_rate.rate is null, 1, currency_conversion_rate.rate))',
				'name' => lang('Total Loss'),
				'formatter' => function ($d) use ($is_export) {
					return $this->utils->formatCurrencyNoSym($d);
				},
			);
			$columns[] = array(
				'dt' => $i++,
				'alias' => 'total_result_amount',
				'select' => !empty($input['group_by']) ? '(sum(game_report_hourly.result_amount) * -1) * (if(currency_conversion_rate.rate is null, 1, currency_conversion_rate.rate))' : 'game_report_hourly.result_amount * (if(currency_conversion_rate.rate is null, 1, currency_conversion_rate.rate))',
				'name' => lang('Game Revenue'),
				'formatter' => function ($d) use ($is_export) {
					return $this->utils->formatCurrencyNoSym($d);
				},
			);
			$columns[] = array(
				'dt' => $i++,
				'alias' => 'game_revenue_percent',
				'select' => !empty($input['group_by']) ? '((sum( game_report_hourly.loss_amount ) - sum( game_report_hourly.win_amount )) / sum( game_report_hourly.betting_amount)) * (if(currency_conversion_rate.rate is null, 1, currency_conversion_rate.rate))' : ' ((game_report_hourly.loss_amount - game_report_hourly.win_amount) / game_report_hourly.betting_amount) * (if(currency_conversion_rate.rate is null, 1, currency_conversion_rate.rate))',
				'name' => lang('Game Revenue %'),
				'formatter' => 'percentageFormatter',
			);
		} else {
			$columns[] = array(
				'dt' => $i++,
				'alias' => 'total_bet_amount',
				// 'select' => 'total_bet_amount',
				'select' => !empty($input['group_by']) ? 'sum(game_report_hourly.betting_amount)' : 'game_report_hourly.betting_amount',
				'name' => lang('Total Bet'),
				'formatter' => function ($d) use ($is_export) {
					return $this->utils->formatCurrencyNoSym($d);
				},
			);
			$columns[] = array(
				'dt' => $i++,
				'alias' => 'total_payout',
				// 'select' => 'total_bet_amount',
				'select' => !empty($input['group_by']) ? '(sum( game_report_hourly.betting_amount ) - (sum( game_report_hourly.loss_amount ) - sum( game_report_hourly.win_amount )))' : '(game_report_hourly.betting_amount - (game_report_hourly.loss_amount - game_report_hourly.win_amount ))',
				'name' => lang('Agency Payout'),
				'formatter' => function ($d) use ($is_export) {
					return $this->utils->formatCurrencyNoSym($d);
				},
			);
			$columns[] = array(
				'dt' => $i++,
				'alias' => 'total_win_amount',
				'select' => !empty($input['group_by']) ? 'sum(game_report_hourly.win_amount)' : 'game_report_hourly.win_amount',
				'name' => lang('Total Win'),
				'formatter' => function ($d) use ($is_export) {
					return $this->utils->formatCurrencyNoSym($d);
				},
			);
			$columns[] = array(
				'dt' => $i++,
				'alias' => 'total_loss_amount',
				// 'select' => 'total_loss_amount',
				'select' => !empty($input['group_by']) ? 'sum(game_report_hourly.loss_amount)' : 'game_report_hourly.loss_amount',
				'name' => lang('Total Loss'),
				'formatter' => function ($d) use ($is_export) {
					return $this->utils->formatCurrencyNoSym($d);
				},
			);
			$columns[] = array(
				'dt' => $i++,
				'alias' => 'total_result_amount',
				'select' => !empty($input['group_by']) ? '(sum(game_report_hourly.result_amount) * -1)' : 'game_report_hourly.result_amount',
				'name' => lang('Game Revenue'),
				'formatter' => function ($d) use ($is_export) {
					return $this->utils->formatCurrencyNoSym($d);
				},
			);
			$columns[] = array(
				'dt' => $i++,
				'alias' => 'game_revenue_percent',
				'select' => !empty($input['group_by']) ? '((sum( game_report_hourly.loss_amount ) - sum( game_report_hourly.win_amount )) / sum( game_report_hourly.betting_amount))' : ' ((game_report_hourly.loss_amount - game_report_hourly.win_amount) / game_report_hourly.betting_amount)',
				'name' => lang('Game Revenue %'),
				'formatter' => 'percentageFormatter',
			);
		}
		# END DEFINE COLUMNS #################################################################################################################################################

	
		$table = 'game_report_hourly';

		$joins = array();
		$joins['game_description'] = 'game_description.external_game_id = game_report_hourly.external_game_id and game_description.game_platform_id = game_report_hourly.game_platform_id';
		if (isset($input['currency'])) {
			if(!empty($input['currency']) && strtolower($input['currency']) == "all"){
				$joins['currency_conversion_rate'] = "currency_conversion_rate.resource_currency = game_report_hourly.currency_key and currency_conversion_rate.target_currency = '{$master_currency}'";
			}
		}
		# START PROCESS SEARCH FORM #################################################################################################################################################
		$where = array();
		$values = array();
		if (isset($input['username'])) {
			$username = $input['username'];
			if(!empty($username)){
				$where[] = "player_username = ?";
				$values[] = $username;
			}			
		}

		$date_from = null;
		$date_to = null;
		if (isset($input['date_from'], $input['date_to'])) {
			$date_from = $input['date_from'];
			$date_to = $input['date_to'];
		}

		if (!empty($date_from) && !empty($date_to)) {
			$where[] = "game_report_hourly.date_hour BETWEEN ? AND ?";
			$values[] = date("YmdH", strtotime($date_from));
			$values[] = date("YmdH", strtotime($date_to));
		}

		if (isset($input['currency'])) {
			$currency = $input['currency'];
			if(!empty($currency) && strtolower($currency) != "all"){
				$where[] = "game_report_hourly.currency_key = ?";
				$values[] = $currency;
			}
		}
		# END PROCESS SEARCH FORM #################################################################################################################################################
		if($is_export){
        	$this->data_tables->options['is_export']=true;
            if(empty($csv_filename)){
                $csv_filename=$this->utils->create_csv_filename(__FUNCTION__);
            }
            $this->data_tables->options['csv_filename']=$csv_filename;
		}

		// $group_by=[];
		$having=[];
		$distinct=true;
		$external_order=[];
		$not_datatable='';
        // $countOnlyField='game_report_hourly.id';
        $countOnlyField='';
        

		if (isset($input['total_bet_from'])) {
			$having['total_bet_amount >='] = $input['total_bet_from'];
		}

		if (isset($input['total_bet_to'])) {
			$having['total_bet_amount <='] = $input['total_bet_to'];
		}

		if (isset($input['total_gain_from'])) {
			$having['total_win_amount >='] = $input['total_gain_from'];
		}

		if (isset($input['total_gain_to'])) {
			$having['total_win_amount <='] = $input['total_gain_to'];
		}

		if (isset($input['total_loss_from'])) {
			$having['total_loss_amount >='] = $input['total_loss_from'];
		}

		if (isset($input['total_loss_to'])) {
			$having['total_loss_amount <='] = $input['total_loss_to'];
		}

		$this->benchmark->mark('get_data_start');
		$result = $this->data_tables->get_data($request, $columns, $table, $where, $values, $joins,
			$group_by, $having, $distinct, $external_order, $not_datatable, $countOnlyField);
		$this->benchmark->mark('get_data_end');

		$this->benchmark->mark('get_totals_start');
		$group_by[] = 'game_report_hourly.currency_key';
		if($is_super){
			$rate_sql = "(if(currency_conversion_rate.rate is null, 1, currency_conversion_rate.rate))";
			$sub_summary = $this->data_tables->summary($request, $table, $joins, "SUM(betting_amount) * {$rate_sql} total_bet_amount,SUM(win_amount) * {$rate_sql} total_win_amount, SUM(loss_amount) * {$rate_sql} total_loss_amount, (SUM(result_amount) * -1) * {$rate_sql} result_amount, (SUM(betting_amount) - (SUM(loss_amount) - SUM(win_amount))) * {$rate_sql} total_payout, (((sum( game_report_hourly.loss_amount ) - sum( game_report_hourly.win_amount )) / sum( game_report_hourly.betting_amount)) * 100) * {$rate_sql} as total_game_revenue, count(DISTINCT(game_report_hourly.player_username)) total_player, GROUP_CONCAT(DISTINCT ( game_report_hourly.player_username )) as concat_total_player", $group_by, $columns, $where, $values);
		} else {
			$sub_summary = $this->data_tables->summary($request, $table, $joins, 'SUM(betting_amount) total_bet_amount,SUM(win_amount) total_win_amount, SUM(loss_amount) total_loss_amount, (SUM(result_amount) * -1) result_amount, (SUM(betting_amount) - (SUM(loss_amount) - SUM(win_amount))) total_payout, ((sum( game_report_hourly.loss_amount ) - sum( game_report_hourly.win_amount )) / sum( game_report_hourly.betting_amount)) * 100 as total_game_revenue, count(DISTINCT(game_report_hourly.player_username)) total_player, GROUP_CONCAT(DISTINCT ( game_report_hourly.player_username )) as concat_total_player', $group_by, $columns, $where, $values);
		}
		
		// echo "<pre>";
		// print_r($sub_summary);exit();
		$this->benchmark->mark('get_totals_end');
		$this->utils->debug_log('game_report_hourly get_data', $this->benchmark->elapsed_time('get_data_start', 'get_data_end'));
		$this->utils->debug_log('game_report_hourly get_totals', $this->benchmark->elapsed_time('get_totals_start', 'get_totals_end'));

		$to_format_fields_values = [
			'total_bet_amount',
			'total_win_amount',
			'total_loss_amount',
			'result_amount',			
		];
		if(!empty($sub_summary)){
			$player_list = [];
			array_walk($sub_summary, function($sub_summary_row) use(&$player_list) {
				$sub_summary_row_player_list = explode(",", $sub_summary_row['concat_total_player']);
                $player_list = array_merge($player_list, $sub_summary_row_player_list);
            });
            $distinct_player_count = count(array_unique($player_list));
			// $sub_summary = $sub_summary[0];
			// foreach ($sub_summary as $key => &$value) {
			// 	if (in_array($key, $to_format_fields_values)){
			// 		$sub_summary[$key]+=number_format(round($value,2),2);
			// 	}else{
			// 		$sub_summary[$key]+=number_format($value);
			// 	}
			// }
			$sub_summary = [
				'total_bet_amount' => $this->utils->formatCurrencyNoSym(array_sum(array_column($sub_summary,'total_bet_amount'))),
				'total_win_amount' => $this->utils->formatCurrencyNoSym(array_sum(array_column($sub_summary,'total_win_amount'))),
				'total_loss_amount' => $this->utils->formatCurrencyNoSym(array_sum(array_column($sub_summary,'total_loss_amount'))),
				'result_amount' => $this->utils->formatCurrencyNoSym(array_sum(array_column($sub_summary,'result_amount'))),
				'total_payout' => $this->utils->formatCurrencyNoSym(array_sum(array_column($sub_summary,'total_payout'))),
				'total_game_revenue' => $this->utils->formatCurrencyNoSym(array_sum(array_column($sub_summary,'total_game_revenue'))),
				'total_player' => $group_by_player_id ? null : $distinct_player_count,
			];
		}else{
			$sub_summary = [
				'total_bet_amount' => '0.00',
				'total_win_amount' => '0.00',
				'total_loss_amount' => '0.00',
				'result_amount' => '0.00',
				'total_payout' => '0.00',
				'total_game_revenue' => '0',
				'total_player' => '0',
			];
		}

		$result['sub_summary'] = $sub_summary;
		return $result;
	}

	/**
	 * detail: get super payment reports
	 *
	 * @param array $request
	 * @param Boolean $is_export
	 *
	 * @return array
	 */
	public function super_payment_report($request, $is_export = false) {
		$readOnlyDB = $this->getReadOnlyDB();
		$this->load->library('data_tables', array("DB" => $readOnlyDB));
		$this->load->helper(['player_helper']);

		# START DEFINE COLUMNS #################################################################################################################################################
		$input = $this->data_tables->extra_search($request);
		$where=[];
		$group_by=[];
		$show_cols=[];
		$is_super = (isset($input['currency']) && strtolower($input['currency']) == 'super');
		$this->load->library('super_report_lib');
		$master_currency = strtolower($this->super_report_lib->getMasterCurrencyCode());

		if (isset($input['group_by']) ) {
			switch ($input['group_by']) {
				case 'by_player' :
					if($is_super){
						$group_by = ['player_id', 'transaction_type', 'payment_date'];
						$show_cols =  ['currency', 'report_date', 'player_username', 'transaction_type', 'super_amount'];
					}else{
						$group_by = ['player_id', 'transaction_type', 'payment_date', 'currency_key'];
						$show_cols =  ['currency', 'report_date', 'player_username', 'transaction_type', 'amount'];
					}
					break;
				case 'by_level' :
					if($is_super){
						$group_by = ['level_id', 'player_group_level_name', 'transaction_type', 'payment_date'];
						$show_cols =  ['currency', 'report_date', 'player_level', 'transaction_type', 'super_amount'];
					}else{
						$group_by = ['level_id', 'player_group_level_name', 'transaction_type', 'payment_date', 'currency_key'];
						$show_cols =  ['currency', 'report_date', 'player_level', 'transaction_type', 'amount'];
					}
					$where[] = "player_group_level_name IS NOT NULL AND player_group_name IS NOT NULL";
					break;
				case 'by_payment_type' : default :
					if($is_super){
						$group_by = ['payment_account_id', 'transaction_type', 'payment_date'];
						$show_cols =  ['currency', 'report_date', 'transaction_type', 'paymentAccount', 'super_amount'];
					}else{
						$group_by = ['payment_account_id', 'transaction_type', 'payment_date','currency_key'];
						$show_cols =  ['currency', 'report_date', 'transaction_type', 'paymentAccount', 'amount'];
					}
					$where[] = "payment_account_id IS NOT NULL";
					break;
			}
		}

		$i = 0;
		$columns = array();
			$columns[] =  array(
				'select' => 'payment_report_daily.id',
				'alias' => 'data_id',
				'name' => lang('lang.action'),
			);
			$columns[] = array(
				'dt' => in_array('currency',$show_cols) ? $i++ : NULL,
				'alias' => 'currency',
				'select' =>'currency_key',
				'name' => lang('Currency'),
				'formatter' => function ($d) use ($is_export, $is_super, $master_currency) {
					if($is_super){
						return strtoupper($master_currency);
					}else{
						return $d;
					}
				},
			);
			$columns[] = array(
				'dt' => in_array('report_date',$show_cols) ? $i++ : NULL,
				'alias' => 'report_date',
				'select' => 'payment_date' ,
				'name' => lang("Date"),
				'formatter' => function ($d) use ($is_export) {
					if ($is_export) {
						return !empty($d) ? $d: lang('lang.norecyet');
					} else {
						return !empty($d) ? $d : '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
					}
				},
			);
			$columns[] = array(
				'dt' => in_array('player_username',$show_cols) ? $i++ : NULL,
				'alias' => 'player_username',
				'select' => 'player_username',
				'name' => lang("Player Username"),
				'formatter' => function ($d) use ($is_export, $group_by) {
					if (in_array('player_id', $group_by)) {
						if ($is_export) {
							return !empty($d) ? $d: lang('lang.norecyet');
						} else {
							return !empty($d) ? $d : '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
						}
					}else{
						return '';
					}
				},
			);
			$columns[] = array(
				'alias' => 'player_group_name',
				'select' => 'player_group_name',
			);
			$columns[] = array(
				'dt' => in_array('player_level',$show_cols) ? $i++ : NULL,
				'alias' => 'player_level',
				'select' =>'player_group_level_name',
				'name' => lang("Group Level"),
				'formatter' => function ($d, $row) use ($is_export, $group_by) { 
					if (in_array('level_id', $group_by)) {
						if(!empty($row['player_group_name']) && !empty($row['player_level'])){
							return lang($row['player_group_name']).' - '.lang($row['player_level']);
						}else{
							if ($is_export) {
								return  lang('lang.norecyet');
							}else{
								return  '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
							}
						}
					}else{
						return '';
					}
				}
			);
			$columns[] = array(
				'dt' => in_array('transaction_type',$show_cols) ? $i++ : NULL,
				'alias' => 'transaction_type',
				'select' => 'transaction_type',
				'name' => lang("Transaction Type"),
				'formatter' => function ($d) use ($is_export) {
					switch ($d) {
						case 1:
						case 2:
						case 3:
						case 4:
						case 5:
						case 6:
						case 7:
						case 8:
						case 9:
						case 10:
						case 11:
						case 12:
						case 13:
						case 14:
							$d = lang('transaction.transaction.type.'.$d);
							break;
						default:
							break;
					}
					if ($is_export) {
						return !empty($d) ? $d: lang('lang.norecyet');
					} else {
						return !empty($d) ? $d : '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
					}
				},
			);		
			$columns[] = array(
				'dt' => in_array('paymentAccount',$show_cols) ? $i++ : NULL,
				'alias' => 'payment_account_name',
				'select' => 'payment_account_name',
				'name' => lang("pay.deposit_payment_account_name"),
				'formatter' => function ($d) use ($is_export, $group_by) {
					if (in_array('payment_account_id', $group_by)) {
						if ($is_export) {
							return !empty($d) ? $d: lang('lang.norecyet');
						} else {
							return !empty($d) ? $d : '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
						}
					}else{
						return '';
					}
				},
			);
			if($is_super){
				$columns[] = array(
					'dt' => in_array('super_amount',$show_cols) ? $i++ : NULL,
					'alias' => 'amount',
					'select' => "sum( amount * rate)",
					'name' => lang("Amount"),
					'formatter' => function ($d) use ($is_export) {
						if ($is_export) {
							return !empty($d) ? $this->utils->formatCurrencyNoSym($d): lang('lang.norecyet');
						} else {
							return !empty($d) ? $this->utils->formatCurrencyNoSym($d) : '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
						}
					},
				);
			}else{
				$columns[] = array(
					'dt' => in_array('amount',$show_cols) ? $i++ : NULL,
					'alias' => 'amount',
					'select' => 'sum(amount)',
					'name' => lang("Amount"),
					'formatter' => function ($d) use ($is_export) {
						return $this->utils->formatCurrencyNoSym($d);
					},
				);
			}
			# END DEFINE COLUMNS #################################################################################################################################################
			$table = 'payment_report_daily';
			if($is_super){
				$joins = array(
					'currency_conversion_rate' => "resource_currency = currency_key and target_currency = '{$master_currency}'",
				);
			}else{
				$joins = array();
			}
			# START PROCESS SEARCH FORM #################################################################################################################################################
			$values = array();

			if (isset($input['username'])) {
				$username = $input['username'];
				if(!empty($username)){
					$where[] = "player_username = ?";
					$values[] = $username;
				}			
			}
			
			$date_from = null;
			$date_to = null;
			if (isset($input['date_from'], $input['date_to'])) {
				$date_from = $input['date_from'];
				$date_to = $input['date_to'];
			}

			if (!empty($date_from) && !empty($date_to)) {
				$where[] = "payment_date BETWEEN ? AND ?";
				$values[] = $date_from;
				$values[] = $date_to;
			}

			if (isset($input['currency']) && !$is_super) {
				$currency = $input['currency'];
				if(!empty($currency)){
					$where[] = "currency_key = ?";
					$values[] = $currency;
				}
			}
			# END PROCESS SEARCH FORM #################################################################################################################################################
			if($is_export){
            	$this->data_tables->options['is_export']=true;
	            if(empty($csv_filename)){
	                $csv_filename=$this->utils->create_csv_filename(__FUNCTION__);
	            }
	            $this->data_tables->options['csv_filename']=$csv_filename;
			}

			$having=[];

			if (isset($input['amount_greater_than'])) {
				$amount_greater_than = $input['amount_greater_than'];
				if(!empty($amount_greater_than)){
					$having["amount >= "] = $amount_greater_than;
				}
			}

			if (isset($input['amount_less_than'])) {
				$amount_less_than = $input['amount_less_than'];
				if(!empty($amount_less_than)){
					$having["amount <= "] = $amount_less_than;
				}
			}

			$distinct=false;
			$external_order=[];
			$not_datatable='';
			$this->benchmark->mark('get_data_start');
			if($is_super){
				$result = $this->data_tables->get_data($request, $columns, $table, $where, $values, $joins,
				$group_by, $having, $distinct, $external_order, $not_datatable);
			}else{
				$countOnlyField ='payment_report_daily.id';
				$result = $this->data_tables->get_data($request, $columns, $table, $where, $values, $joins,
				$group_by, $having, $distinct, $external_order, $not_datatable, $countOnlyField);
			}
			
			$this->benchmark->mark('get_data_end');
			$this->benchmark->mark('get_totals_start');
			$sub_amount = 0.00;
			$total_amount = 0.00;
			if(is_array($result) && !empty($result['data']) && !empty($result['header_data'])){
				$amount['index'] = array_search(lang("Amount") , $result['header_data']);
				foreach($result['data'] as  $value){
					$floatValue = floatval(str_replace(',', '', $value[$amount['index']]));
					$sub_amount += $floatValue;
				}
			}
			$summary_amount['sub_amount'] = $this->utils->formatCurrencyNoSym($sub_amount);
			if (isset($input['amount_greater_than'])) {
				$amount_greater_than = $input['amount_greater_than'];
				if(!empty($amount_greater_than)){
					$where[] = "amount >= ?";
					$values[] = $amount_greater_than;
				}
			}

			if (isset($input['amount_less_than'])) {
				$amount_less_than = $input['amount_less_than'];
				if(!empty($amount_less_than)){
					$where[] = "amount <= ?";
					$values[] = $amount_less_than;
				}
			}
			if($is_super){
				$total_amount = $this->data_tables->summary($request, $table, $joins, 'SUM(amount * rate) amount', '', $columns, $where, $values);
			}else{
				$total_amount = $this->data_tables->summary($request, $table, $joins, 'SUM(amount) amount', 'currency_key', $columns, $where, $values);
			}
			$this->benchmark->mark('get_totals_end');
			$this->utils->debug_log('payment_report_daily get_data', $this->benchmark->elapsed_time('get_data_start', 'get_data_end'));
			$this->utils->debug_log('payment_report_daily get_totals', $this->benchmark->elapsed_time('get_totals_start', 'get_totals_end'));

			if(!empty($total_amount)){
				$to_format_fields_values = [
					'amount',			
				];
				$total_summary = $total_amount[0];
				foreach ($total_summary as $key => &$value) {
					if (in_array($key, $to_format_fields_values)){
						$total_summary[$key]=number_format(round($value,2),2);
						
					}else{
						$total_summary[$key]=number_format($value);
					}
				}
				$summary_amount['total_amount'] = $total_summary['amount'];
			}else{
				$summary_amount['total_amount'] = $this->utils->formatCurrencyNoSym(0.00);
			}
			$result['summary_amount'] = $summary_amount;
			return $result;	
	}

	/**
	 * detail: get super payment reports
	 *
	 * @param array $request
	 * @param Boolean $is_export
	 *
	 * @return array
	 */
	public function super_promotion_report($request, $is_export = false) {

		$readOnlyDB = $this->getReadOnlyDB();

		$this->load->library('data_tables', array("DB" => $readOnlyDB));
		$this->load->helper(['player_helper']);
	

		# START DEFINE COLUMNS #################################################################################################################################################
		$input = $this->data_tables->extra_search($request);

		$i = 0;
		$columns = array();
			$columns[] =  array(
				'select' => 'id',
				'alias' => 'data_id',
				'name' => lang('lang.action'),
			);
			$columns[] = array(
				'dt' => $i++,
				'alias' => 'currency',
				'select' => 'currency_key' ,
				'name' => lang('Currency'),
				'formatter' => function ($d) use ($is_export) {
					return $d;
				},
			);
			$columns[] = array(
				'dt' => $i++,
				'alias' => 'report_date',
				'select' => 'created_at',
				'name' => lang("aff.ap07"),
				'formatter' => function ($d) use ($is_export) {
					if ($is_export) {
						return !empty($d) ? $d: lang('lang.norecyet');
					} else {
						return !empty($d) ? $d : '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
					}
				},
			);
			$columns[] = array(
				'dt' => $i++,
				'alias' => 'player_username',
				'select' => 'player_username',
				'name' => lang("Player Username"),
				'formatter' => function ($d) use ($is_export) {
					if ($is_export) {
						return !empty($d) ? $d: lang('lang.norecyet');
					} else {
						return !empty($d) ? $d : '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
					}
				},
			);
			$columns[] = array(
				'dt' => $i++,
				'alias' => 'player_level',
				'select' => 'player_group_level_name',
				'name' => lang("Group Level"),
				'formatter' => function ($d) use ($is_export) { 
					return lang($d);
				}
			);
			$columns[] = array(
				'dt' => $i++,
				'alias' => 'promorule_name',
				'select' => 'promo_name',
				'name' => lang("Promo Rule"),
				'formatter' => function ($d) use ($is_export) {
					if ($is_export) {
						return !empty($d) ? $d: lang('lang.norecyet');
					} else {
						return !empty($d) ? $d : '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
					}
				},
			);
			$columns[] = array(
				'dt' => $i++,
				'alias' => 'promorule_status',
				'select' => 'promo_status',
				'name' => lang("Promotion Status"),
				'formatter' => function ($d) use ($is_export) {
					switch ($d) {
						case 0:
							$d = lang('Pending');
						case 1:
							$d = lang('Approved');
						case 3:
							$d = lang('Declined');
						case 9:
							$d = lang('Finished');
							break;
						default:
							break;
					}
					if ($is_export) {
						return !empty($d) ? $d: lang('lang.norecyet');
					} else {
						return !empty($d) ? $d : '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
					}
				},

			);
			$columns[] = array(
				'dt' => $i++,
				'alias' => 'amount',
				'select' => 'amount',
				'name' => lang("Amount"),
				'formatter' => function ($d) use ($is_export) {
					return $this->utils->formatCurrencyNoSym($d);
				},
			);
			# END DEFINE COLUMNS #################################################################################################################################################

			$table='promotion_report_details';

			$joins = array();
			# START PROCESS SEARCH FORM #################################################################################################################################################
			$where = array();
			$values = array();

			if (isset($input['username'])) {
				$username = $input['username'];
				if(!empty($username)){
					$where[] = "player_username = ?";
					$values[] = $username;
				}			
			}
			
			$date_from = null;
			$date_to = null;
			if (isset($input['date_from'], $input['date_to'])) {
				$date_from = $input['date_from'];
				$date_to = $input['date_to'];
			}

			if (!empty($date_from) && !empty($date_to)) {
				
				$where[] = "created_at BETWEEN ? AND ?";
				$values[] = $date_from;
				$values[] = $date_to;
			}

			if (isset($input['currency'])) {
				$currency = $input['currency'];
				if(!empty($currency)){
					$where[] = "currency_key = ?";
					$values[] = $currency;
				}
			}
			# END PROCESS SEARCH FORM #################################################################################################################################################
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
            $countOnlyField='promotion_report_details.id';
	
            $this->benchmark->mark('get_data_start');
            $result = $this->data_tables->get_data($request, $columns, $table, $where, $values, $joins,
            	$group_by, $having, $distinct, $external_order, $not_datatable, $countOnlyField);
            $this->benchmark->mark('get_data_end');

			$this->utils->printLastSQL();

            $this->benchmark->mark('get_totals_start');
            $sub_summary = $this->data_tables->summary($request, $table, $joins, 'SUM(amount) amount', 'currency_key', $columns, $where, $values);
            $this->benchmark->mark('get_totals_end');

            $this->utils->debug_log('promotion_report_details get_data', $this->benchmark->elapsed_time('get_data_start', 'get_data_end'));
            $this->utils->debug_log('promotion_report_details get_totals', $this->benchmark->elapsed_time('get_totals_start', 'get_totals_end'));

            $to_format_fields_values = [
            	'amount',			
            ];

            if(!empty($sub_summary)){
            	$sub_summary = $sub_summary[0];
            	foreach ($sub_summary as $key => &$value) {
            		if (in_array($key, $to_format_fields_values)){
            			$sub_summary[$key]=number_format(round($value,2),2);
            		}else{
            			$sub_summary[$key]=number_format($value);
            		}
            	}
            }else{
            	$sub_summary = [
            		'amount'=>'0',
            	];
            }


            $result['sub_summary'] = $sub_summary;
            return $result;

	}

	/**
	 * detail: get super payment reports
	 *
	 * @param array $request
	 * @param Boolean $is_export
	 *
	 * @return array
	 */
	public function super_cashback_report($request, $is_export = false) {


		$readOnlyDB = $this->getReadOnlyDB();

		$this->load->library('data_tables', array("DB" => $readOnlyDB));
		$this->load->helper(['player_helper']);
        $this->load->model(['tag']);

		# START DEFINE COLUMNS #################################################################################################################################################
		$input = $this->data_tables->extra_search($request);
        $is_super = (isset($input['currency']) && strtolower($input['currency']) == 'super');
        $this->load->library('super_report_lib');
		$master_currency = strtolower($this->super_report_lib->getMasterCurrencyCode());

		// $i = 0;
        $currency_column = 0;
        $report_date_column = 1;
        $player_username_column = 2;
        $agent_username_column = 3;
        $player_tag_column = 4;
        $player_level_column = 5;
        $amount_column = 6;
        $bet_amount_column = 7;
        $real_bet_amount_column = 8;
        $paid_flag_column = 9;
        $game_platform_id_column = 10;
        $game_platform_code_column = 11;
        $game_type_name_column = 12;
        $updated_at_column = 13;
        $paid_date_column = 14;
        $registration_time_column = 15;
        $paid_amount_column = 16;
        $withdraw_condition_amount_column = 17;

		$columns = array();
			$columns[] =  array(
				'select' => 'cashback_report_daily.id',
				'alias' => 'data_id',
				'name' => lang('lang.action'),
			);
			$columns[] = array(
				'dt' => $currency_column,
				'alias' => 'currency',
				'select' => 'cashback_report_daily.currency_key',
				'name' => lang('Currency'),
				'formatter' => function ($d) use ($is_export, $is_super, $master_currency) {
                    if ($is_super && !empty($master_currency)) {
                        return strtoupper($master_currency);
                    } else {
                        return strtoupper($d);
                    }
				}
			);
			$columns[] = array(
				'dt' => $report_date_column,
				'alias' => 'report_date',
				'select' => 'cashback_report_daily.cashback_date',
				'name' => lang("aff.ap07"),
				'formatter' => function ($d) use ($is_export) {
					if ($is_export) {
						return !empty($d) ? $d: lang('lang.norecyet');
					} else {
						return !empty($d) ? $d : '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
					}
				},
			);
			$columns[] = array(
				'dt' => $player_username_column,
				'alias' => 'player_username',
				'select' => 'cashback_report_daily.player_username',
				'name' => lang("Player Username"),
				'formatter' => function ($d) use ($is_export) {
					if ($is_export) {
						return !empty($d) ? $d: lang('lang.norecyet');
					} else {
						return !empty($d) ? $d : '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
					}
				},
			);
            $columns[] = array(
                'dt' => $agent_username_column,
                'alias' => 'agent_username',
                'select' => 'agency_agents.agent_name',
                'name' => lang("Agent Username"),
                'formatter' => function ($d) use ($is_export) {
                    if ($is_export) {
                        return !empty($d) ? $d: lang('lang.norecyet');
                    } else {
                        return !empty($d) ? $d : '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
                    }
                },
            );
            $columns[] = array(
                'dt' => $player_tag_column,
                'alias' => 'player_tag',
                'select' => 'cashback_report_daily.playerTags',
                'name' => lang('Player Tag'),
                'formatter' => function ($d) use ($is_export) {
                    $playerTags = !empty($d) && !is_array($d) ? json_decode($d, true) : [];
                    $this->utils->debug_log(__METHOD__, 'playerTags', $playerTags, 'data', $d);

                    if ($is_export) {
                        /* $tagname = player_tagged_list($d, $is_export);
                        return ($tagname ? $tagname : lang('lang.norecyet')); */
                        return !empty($playerTags) ? implode(',', $playerTags) : lang('lang.norecyet');
                    } else {
                        /* $tagname = player_tagged_list($d);
                        return $tagname ? $tagname : '<i class="text-muted">' . lang('lang.norecyet') . '</i>'; */
                        $html_tag_list = [];

                        if (!empty($playerTags)) {
                            foreach ($playerTags as $tagName) {
                                $tagColor = $this->tag->getTagColorByTagName(null, $tagName);
                                $html_tag = '<span class="tag label label-info" style="background-color: ' . $tagColor . ';">' . $tagName . '</span>';
                                array_push($html_tag_list, $html_tag);
                            }
    
                            return implode(' ', $html_tag_list);
                        } else {
                            return lang('lang.norecyet');
                        }
                    }
                },
            );
			$columns[] = array(
				'dt' => $player_level_column,
				'alias' => 'player_level',
				'select' => 'cashback_report_daily.player_group_level_name',
				'name' => lang("Group Level"),
				'formatter' => function ($d) use ($is_export) {  
					return lang($d);	
				},
			);
			if ($is_super) {
                $columns[] = array(
                    'dt' => $amount_column,
                    'alias' => 'amount',
                    'select' => 'sum(cashback_report_daily.cashback_amount * currency_conversion_rate.rate)',
                    'name' => lang("Amount"),
                    'formatter' => function ($d) use ($is_export) {
                        return $this->utils->formatCurrencyNoSym($d);
                    },
                );
            } else {
                $columns[] = array(
                    'dt' => $amount_column,
                    'alias' => 'amount',
                    'select' => 'cashback_report_daily.cashback_amount',
                    'name' => lang("Amount"),
                    'formatter' => function ($d) use ($is_export) {
                        return $this->utils->formatCurrencyNoSym($d);
                    },
                );
            }
            if ($is_super) {
				$columns[] = array(
					'dt' => $bet_amount_column,
					'alias' => 'bet_amount',
					'select' => "sum(cashback_report_daily.betting_amount * currency_conversion_rate.rate)",
					'name' => lang("Bet Amount"),
					'formatter' => function ($d) use ($is_export) {
                        return $this->utils->formatCurrencyNoSym($d);
					},
				);
			} else {
				$columns[] = array(
                    'dt' => $bet_amount_column,
                    'alias' => 'bet_amount',
                    //'select' => 'bet_amount',
                    'select' => 'cashback_report_daily.betting_amount',
                    'name' => lang("Bet Amount"),
                    'formatter' => function ($d) use ($is_export) {
                        return $this->utils->formatCurrencyNoSym($d);
                    },
                );
			}
            if ($is_super) {
                $columns[] = array(
                    'dt' => $real_bet_amount_column,
                    'alias' => 'real_bet_amount',
                    //'select' => 'bet_amount',
                    'select' => 'sum(cashback_report_daily.original_betting_amount * currency_conversion_rate.rate)',
                    'name' => lang("Real Bet Amount"),
                    'formatter' => function ($d) use ($is_export) {
                        return $this->utils->formatCurrencyNoSym($d);
                    },
                );
            } else {
                $columns[] = array(
                    'dt' => $real_bet_amount_column,
                    'alias' => 'real_bet_amount',
                    //'select' => 'bet_amount',
                    'select' => 'cashback_report_daily.original_betting_amount',
                    'name' => lang("Real Bet Amount"),
                    'formatter' => function ($d) use ($is_export) {
                        return $this->utils->formatCurrencyNoSym($d);
                    },
                );
            }
			$columns[] = array(
				'dt' => $paid_flag_column,
				'alias' => 'paid_flag',
				'select' => 'cashback_report_daily.paid_flag',
				'name' => lang("Paid"),
				'formatter' => function ($d) use ($is_export) {
					if($d == 0) {
			            $d = lang('Not pay');
			        } else if($d == 1) {
			            $d = lang('Paid');
			        }
					if ($is_export) {
						return !empty($d) ? $d: lang('lang.norecyet');
					} else {
						return !empty($d) ? $d : '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
					}
				},
			);
			$columns[] = array(
				'dt' => $game_platform_id_column,
				'alias' => 'game_platform_id',
				'select' => 'cashback_report_daily.game_platform_id',
				'name' => lang("Game Platform"),
				'formatter' => function ($d) use ($is_export) {
					if ($is_export) {
						return !empty($d) ? $d: lang('lang.norecyet');
					} else {
						return !empty($d) ? $d : '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
					}
				},
			);

			$columns[] = array(
					'dt' => $game_platform_code_column,
					'alias' => 'game_platform_code',
					'select' => 'cashback_report_daily.game_platform_code',
					'name' => lang("sys.gd9"),
					'formatter' => function ($d) use ($is_export) {
						if ($is_export) {
							return !empty($d) ? lang($d): lang('lang.norecyet');
						} else {
							return !empty($d) ? lang($d) : '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
						}
					},
				);
			$columns[] = array(
				'dt' => $game_type_name_column,
				'alias' => 'game_type_name',
				//'select' => 'game_type_name',
				'select' => 'cashback_report_daily.game_type_code',
				'name' => lang("Game Type"),
				'formatter' => function ($d) use ($is_export) {
					if ($is_export) {
						return !empty($d) ? lang($d) : lang('lang.norecyet');
					} else {
						return !empty($d) ? lang($d) : '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
					}
				},
			);
			$columns[] = array(
				'dt' => $updated_at_column,
				'alias' => 'updated_at',
				'select' => 'cashback_report_daily.updated_at',
				'name' => lang("Updated at"),
				'formatter' => function ($d) use ($is_export) {
					if ($is_export) {
						return !empty($d) ? $d: lang('lang.norecyet');
					} else {
						return !empty($d) ? $d : '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
					}
				},
			);
			$columns[] = array(
				'dt' => $paid_date_column,
				'alias' => 'paid_date',
				'select' => 'cashback_report_daily.paid_date',
				'name' => lang("Paid date"),
				'formatter' => function ($d) use ($is_export) {
					if ($is_export) {
						return !empty($d) ? $d: lang('lang.norecyet');
					} else {
						return !empty($d) ? $d : '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
					}
				},
			);
            $columns[] = array(
				'dt' => $registration_time_column,
				'alias' => 'registration_time',
				'select' => 'player.createdOn',
				'name' => lang("Registration Time"),
				'formatter' => function ($d) use ($is_export) {
					if ($is_export) {
						return !empty($d) ? $d: lang('lang.norecyet');
					} else {
						return !empty($d) ? $d : '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
					}
				},
			);
            if ($is_super) {
                $columns[] = array(
                    'dt' => $paid_amount_column,
                    'alias' => 'paid_amount',
                    'select' => 'sum(cashback_report_daily.paid_amount * currency_conversion_rate.rate)',
                    'name' => lang("Paid amount"),
                    'formatter' => function ($d) use ($is_export) {
                        return $this->utils->formatCurrencyNoSym($d);
                    },
                );
            } else {
                $columns[] = array(
                    'dt' => $paid_amount_column,
                    'alias' => 'paid_amount',
                    'select' => 'cashback_report_daily.paid_amount',
                    'name' => lang("Paid amount"),
                    'formatter' => function ($d) use ($is_export) {
                        return $this->utils->formatCurrencyNoSym($d);
                    },
                );
            }
            if ($is_super) {
                $columns[] = array(
                    'dt' => $withdraw_condition_amount_column,
                    'alias' => 'withdraw_condition_amount',
                    'select' => 'sum(cashback_report_daily.withdraw_condition_amount * currency_conversion_rate.rate)',
                    'name' => lang("Withdraw Condition amount"),
                    'formatter' => function ($d) use ($is_export) {
                        return $this->utils->formatCurrencyNoSym($d);
                    },
                );
            } else {
                $columns[] = array(
                    'dt' => $withdraw_condition_amount_column,
                    'alias' => 'withdraw_condition_amount',
                    'select' => 'cashback_report_daily.withdraw_condition_amount',
                    'name' => lang("Withdraw Condition amount"),
                    'formatter' => function ($d) use ($is_export) {
                        return $this->utils->formatCurrencyNoSym($d);
                    },
                );
            }
			# END DEFINE COLUMNS #################################################################################################################################################
            $table='cashback_report_daily';

            if ($is_super) {
                $joins = [
                    'player' => 'cashback_report_daily.player_id = player.playerId',
                    'agency_agents' => 'player.agent_id = agency_agents.agent_id',
                    'currency_conversion_rate' => "resource_currency = currency_key and target_currency = '{$master_currency}'",
                ];
            } else {
                $joins = [
                    'player' => 'cashback_report_daily.player_id = player.playerId',
                    'agency_agents' => 'player.agent_id = agency_agents.agent_id',
                ];
            }

			# START PROCESS SEARCH FORM #################################################################################################################################################
			$where = array();
			$values = array();

			/* if (isset($input['username'])) {
				$username = $input['username'];
				if(!empty($username)){
					$where[] = "player_username = ?";
					$values[] = $username;
				}			
			}
			
			$date_from = null;
			$date_to = null;
			if (isset($input['date_from'], $input['date_to'])) {
				$date_from = $input['date_from'];
				$date_to = $input['date_to'];
			}

			if (!empty($date_from) && !empty($date_to)) {
			
				$where[] = "created_at BETWEEN ? AND ?";
				$values[] = $date_from;
				$values[] = $date_to;
			} */

			if (isset($input['currency']) && !$is_super) {
				$currency = $input['currency'];
				if(!empty($currency)){
					$where[] = "cashback_report_daily.currency_key = ?";
					$values[] = $currency;
				}
			}

            if (!empty($input['search_reg_date']) && $input['search_reg_date'] == 'on') {
                if (isset($input['registration_date_from'], $input['registration_date_to'])) {
                    $where[] = "player.createdOn >= ?";
                    $where[] = "player.createdOn <= ?";
                    $values[] = $input['registration_date_from'];
                    $values[] = $input['registration_date_to'];
                }
            }

            if (isset($input['by_amount_greater_than'])) {
                $where[] = "cashback_report_daily.cashback_amount >= ?";
                $values[] = $input['by_amount_greater_than'];
            }

            if (isset($input['by_amount_less_than'])) {
                $where[] = "cashback_report_daily.cashback_amount <= ?";
                $values[] = $input['by_amount_less_than'];
            }

            if (isset($input['by_player_level'])) {
                $where[] = "cashback_report_daily.level_id = ?";
                $values[] = $input['by_player_level'];
            }

            if (isset($input['by_paid_flag'])) {
                $where[] = "cashback_report_daily.paid_flag = ?";
                $values[] = $input['by_paid_flag'];
            }


            if ($this->safeGetParam($input, 'enable_date') == 'true') {
                if (isset($input['by_date_from'])) {
                    $where[] = "cashback_report_daily.cashback_date >= ?";
                    $values[] = $input['by_date_from'];
                }

                if (isset($input['by_date_to'])) {
                    $where[] = "cashback_report_daily.cashback_date <= ?";
                    $values[] = $input['by_date_to'];
                }
            }
    
            if (isset($input['by_username'])) {
                $where[] = "cashback_report_daily.player_username LIKE ?";
                $values[] = '%' . $input['by_username'] . '%';
            }
    
            if (isset($input['agent_username'])) {
                $where[] = "agency_agents.agent_name LIKE ?";
                $values[] = '%' . $input['agent_username'] . '%';
            }

            if (!empty($input['tag_list'])) {
                $tag_list = $input['tag_list'];
                $if_no_tag_selected = "(cashback_report_daily.playerTags IS NULL OR cashback_report_daily.playerTags = '')";

                if (is_array($tag_list)) {
                    $like_operators = [];

                    foreach ($tag_list as $tag) {
                        if ($tag == 'notag') {
                            $like_operator = $if_no_tag_selected;
                        } else {
                            $like_operator = "json_extract(cashback_report_daily.playerTags, '$') LIKE '%{$tag}%'";
                        }

                        array_push($like_operators, $like_operator);
                    }

                    $where[] = "(" . implode(" OR ", $like_operators) . ")";
                } else {
                    if ($tag_list == 'notag') {
                        $where[] = $if_no_tag_selected;
                    } else {
                        $where[] = "json_extract(cashback_report_daily.playerTags, '$') LIKE '%{$tag_list}%'";
                    }
                }

                /* if (is_array($tag_list)) {
                    $notag = array_search('notag', $tag_list);

                    if ($notag !== false) {
                        unset($tag_list[$notag]);
                        $is_include_notag = true;
                    } else {
                        $is_include_notag = false;
                    }

                } elseif ($tag_list == 'notag') {
                    $tag_list = null;
                    $is_include_notag = true;
                }

                $where_fragments = [];

                if ($is_include_notag) {
                    $where_fragments[] = 'cashback_report_daily.player_id NOT IN (SELECT DISTINCT playerId FROM playertag)';
                }

                if (!empty($tag_list) ) {
                    $tagList = is_array($tag_list) ? implode('","', $tag_list) : $tag_list;
                    $where_fragments[] =  'cashback_report_daily.player_id IN (SELECT DISTINCT playerId FROM playertag WHERE playertag.tagName IN ("'.$tagList.'"))';
                }

                if (!empty($where_fragments) ) {
                    $where[] = ' ('. implode(' OR ', $where_fragments ). ') ';
                } */
            }

            $where[] = "player.deleted_at IS NULL";
			# END PROCESS SEARCH FORM #################################################################################################################################################
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
			$countOnlyField='cashback_report_daily.id';
            $summary_group_by = 'currency_key';

            if ($is_super) {
                $group_by = [
                    'cashback_report_daily.id'
                ];

                $countOnlyField='';
                $summary_group_by = '';
            }

			$this->benchmark->mark('get_data_start');
			$result = $this->data_tables->get_data($request, $columns, $table, $where, $values, $joins,
				$group_by, $having, $distinct, $external_order, $not_datatable, $countOnlyField);
			$this->benchmark->mark('get_data_end');

            // rebuild data for player tag
            /* if (!empty($result['data']) && is_array($result['data'])) {
                $this->load->model(['multiple_db_model']);
                $html_tag_list = $tag_list = $tags = [];
                $tags_exist_count = 0;

                foreach ($result['data'] as $key => $data) {
                    $currency = $data[$currency_column];
                    $player_username = $data[$player_username_column];
                    $player_tags = $this->multiple_db_model->getPlayerTagByCurrencyToSuper($currency, $player_username);

                    if (!empty($player_tags)) {
                        foreach ($player_tags as $player_tag) {
                            $tag = '<span class="tag label label-info" style="background-color: ' . $player_tag['tagColor'] . '; margin-left: 2px; margin-right: 2px;">' . $player_tag['tagName'] . '</span>';

                            array_push($html_tag_list, $tag);
                            array_push($tags, $player_tag['tagName']);
                        }

                        $result['data'][$key][$player_tag_column] = implode('', $html_tag_list);
                    }

                    if (isset($input['tag_list'])) {
                        $tag_list = $input['tag_list'];

                        if (!empty($tag_list)) {
                            if (!empty($player_tags)) {
                                if (is_array($tag_list)) {
                                    foreach ($tags as $tag) {
                                        if (in_array($tag, $tag_list)) {
                                            $tags_exist_count++;
                                        }
                                    }
                                } else {
                                    if (in_array($tag_list, $tags)) {
                                        $tags_exist_count++;
                                    }
                                }

                                if ($tags_exist_count < 1) {
                                    unset($result['data'][$key]);
                                }
                            } else {
                                if ($tag_list != 'notag') {
                                    unset($result['data'][$key]);
                                }
                            }
                        }
                    }

                    $html_tag_list = $tag_list = $tags = [];
                    $tags_exist_count = 0;
                }
            }

            $result['data'] = array_values($result['data']); */

			$this->benchmark->mark('get_totals_start');
            if ($is_super) {
                $sub_summary = $this->data_tables->summary($request, $table, $joins, 'SUM(cashback_report_daily.cashback_amount * currency_conversion_rate.rate) cashback_amount, SUM(cashback_report_daily.betting_amount * currency_conversion_rate.rate) betting_amount, SUM(cashback_report_daily.original_betting_amount * currency_conversion_rate.rate) original_betting_amount, SUM(cashback_report_daily.paid_amount * currency_conversion_rate.rate) paid_amount, SUM(cashback_report_daily.withdraw_condition_amount * currency_conversion_rate.rate) withdraw_condition_amount ', $summary_group_by, $columns, $where, $values);
            } else {
                $sub_summary = $this->data_tables->summary($request, $table, $joins, 'SUM(cashback_report_daily.cashback_amount) cashback_amount, SUM(cashback_report_daily.betting_amount) betting_amount, SUM(cashback_report_daily.original_betting_amount) original_betting_amount, SUM(cashback_report_daily.paid_amount) paid_amount, SUM(cashback_report_daily.withdraw_condition_amount) withdraw_condition_amount ', $summary_group_by, $columns, $where, $values);
            }
			$this->benchmark->mark('get_totals_end');

			$this->utils->debug_log('cashback_report_daily get_data', $this->benchmark->elapsed_time('get_data_start', 'get_data_end'));
			$this->utils->debug_log('cashback_report_daily get_totals', $this->benchmark->elapsed_time('get_totals_start', 'get_totals_end'));


			$to_format_fields_values = [
				'cashback_amount',
				'betting_amount',
				'original_betting_amount',
				'paid_amount',
				'withdraw_condition_amount'

			];
			if(!empty($sub_summary)){
				$sub_summary = $sub_summary[0];
				foreach ($sub_summary as $key => &$value) {
					if (in_array($key, $to_format_fields_values)){
						$sub_summary[$key]=number_format(round($value,2),2);
					}else{
						$sub_summary[$key]=number_format($value);
					}
				}
			}else{
				$sub_summary = [
					'cashback_amount'=>'0.00',
					'betting_amount'=>'0.00',
                    'original_betting_amount' => '0.00',
					'paid_amount'=>'0.00',
					'withdraw_condition_amount'=>'0.00',
				];
			}

			
			$result['sub_summary'] = $sub_summary;
			return $result;
		
	}
}