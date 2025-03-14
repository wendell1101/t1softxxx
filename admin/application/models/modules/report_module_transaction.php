<?php
/**
 * General behaviors include
 * * get transaction details of a certain player
 * * get summary reports
 * * the sum/total of the win and loss payout
 * * get new added and total number of players
 * * get all new players
 * * get total number of players
 * * get the first and second deposit record
 * * get first/second deposit record
 * * get certain payments report
 * * get balance history of a certain player
 *
 * @category report_module_transaction
 * @version 1.8.10
 * @copyright 2013-2022 tot
 */

const APPROVED_MODAL = 1;  // 'approvedDetailsModal';
const DECLINED_MODAL = 2;  // 'declinedDetailsModal';
const REQUEST_MODAL = 3;   //'requestDetailsModal';

trait report_module_transaction {


	/**
	 * cloned form transaction_details()
	 *
	 * @param integer $player_id
	 * @param array $request
	 * @param boolean $is_export
	 * @param string $not_datatable
	 * @param string $where_status
	 * @param string $mobile
	 * @param string $csv_filename
	 * @return void
	 */
	public function balance_transaction_details($player_id, $request, $is_export = false, $not_datatable = '', $where_status = '',$mobile = '',$csv_filename=null) {

		$readOnlyDB = $this->getReadOnlyDB();

		$this->load->library('data_tables', array("DB" => $readOnlyDB));
		$this->load->model(['transactions'
			, 'player_model'
			// , 'affiliatemodel'
			, 'transaction_notes'
			// , 'common_category'
			, 'walletaccount_notes'
			// , 'promorules'
			, 'sale_orders_notes'
			, 'payment_account'
		] );
		$this->load->helper(['player_helper']);

		$this->data_tables->is_export = $is_export;

		// pre_processing_start

		# START DEFINE COLUMNS #################################################################################################################################################
		$i = 0;
		$columns = array(
			array(
				'alias' => 'id',
				'select' => 'transactions.id',
			),
			array(
				'alias' => 'from_type',
				'select' => 'transactions.from_type',
			),
			array(
				'alias' => 'from_id',
				'select' => 'transactions.from_id',
			),
			array(
				'alias' => 'to_type',
				'select' => 'transactions.to_type',
			),
			array(
				'alias' => 'to_id',
				'select' => 'transactions.to_id',
			),
			array(
				'alias' => 'order_id',
				'select' => 'sale_orders.secure_id',
			),
			array(
                'alias' => 'promorulesId',
                'select' => 'promorules.promorulesId',
            ),
			array(
				'alias' => 'sale_orders_id',
				'select' => 'sale_orders.id',
			),
			array( // #1 Requested Time,
				// Deposit => Request Time
				'alias' => 'sale_orders_created_at',
				'select' => 'sale_orders.created_at',
			),
			array( // #1 Requested Time,
				// Withdrawal => Request Time
				'alias' => 'walletaccount_dwDatetime',
				'select' => 'walletaccount.dwDatetime',
			),
			array( // #8 Collection Acc.Bank/Payment,
				// Deposit => Collection Acc. Bank/Payment Name
				'alias' => 'sale_orders_payment_type_name',
				'select' => 'sale_orders.payment_type_name',
			),
			array( // #8 Collection Acc.Bank/Payment,
				// Withdrawal => Bank/Payment Type
				'alias' => 'banktype_default_payment_flag',
				'select' => 'banktype.default_payment_flag',
			),

			array(
				'alias' => 'walletAccountId',
				'select' => 'walletaccount.walletAccountId',
			),
			array(
				'alias' => 'status',
				'select' => 'transactions.status',
			),
			array(// #1 Requested Time
				'dt' => $i++,
				'alias' => 'created_at',
				'select' => 'transactions.created_at',
				'name' => lang('pay.reqtime'),
				'formatter' => function ($d, $row) use ($is_export)  {

					$formatted = null; // default
					$transaction_type = $row['transaction_type'];
					switch($transaction_type){
						case Transactions::DEPOSIT: // 1
							$formatted = $row['sale_orders_created_at']; // 'select' => 'sale_orders.created_at',
							break;
						case Transactions::WITHDRAWAL: // 2
							$formatted = $row['walletaccount_dwDatetime']; // 'select' => 'walletaccount.dwDatetime',
							break;
						case Transactions::MANUAL_ADD_BALANCE: // 7
						case Transactions::MANUAL_SUBTRACT_BALANCE: // 8
						case Transactions::ADD_BONUS: // 9
							$formatted = $d; // transactions.created_at
							break;
					}
					return $formatted;
					/// apply to each case,
					// $row['transactions_created_at'] // Manual Add Balance, Manual Subtract Balance
					// $row['sale_orders_created_at'] // Deposit
					// $row['walletaccount_dwDatetime'] // Withdrawal
				}
			),

			array( // #2 Processed On
				'dt' => $i++,
				'alias' => 'process_time',
				'select' => 'sale_orders.process_time',
				'name' => lang('pay.procsson'),
				'formatter' => function ($d, $row) use ($is_export)  {


					$formatted = null; // default
					$transaction_type = $row['transaction_type'];
					switch($transaction_type){
						case Transactions::DEPOSIT: // 1
							if (!$is_export) {
								$formatted = $d ?: '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
							}else{
								$formatted = $d ?: lang('lang.norecyet');
							}
							break;
						case Transactions::WITHDRAWAL: // 2
						case Transactions::MANUAL_ADD_BALANCE: // 7
						case Transactions::MANUAL_SUBTRACT_BALANCE: // 8
						case Transactions::ADD_BONUS: // 9
							$formatted = $row['created_at']; // transactions.created_at
							break;
					}
					return $formatted;
				},
			),
			array( // #3 Transaction Type
				'dt' => $i++,
				'alias' => 'transaction_type',
				'select' => 'transactions.transaction_type',
				'formatter' => function ($d, $row) use ($is_export) {
					$val = '';
					if ($is_export) {
						$val = lang('transaction.transaction.type.' . $d) ?: lang('lang.norecyet');
					} else {
						$val = lang('transaction.transaction.type.' . $d) ?: '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
						//sub query
						// "GROUP_CONCAT(manual_subtract_balance_tag.adjust_tag_name ORDER BY transactions.id, manual_subtract_balance_tag.id SEPARATOR ';')"
						$tags=$this->transactions->getManualSubtractBalanceTagsBy($row['id']);
						if(!empty($tags)){
							foreach ($tags as $tag) {
								$val.=' <span class="label label-info">'. lang($tag['adjust_tag_name']) . '</span>';
							}
						}
					}
					return $val;
				},
				'name' => lang('player.ut02'),
			),
			array( // #4 From (Processed By), @todo TODO: Processed By in Deposit AND  Withdrawal
				'dt' => $i++,
				'alias' => 'from_username',
				'select' => '(CASE transactions.from_type WHEN 1 THEN fromUser.username WHEN 2 THEN fromPlayer.username WHEN 3 THEN fromAffiliate.username ELSE NULL END)',
				'formatter' => function ($d, $row) use ($is_export) {
					if ($is_export) {
						return ($d ? $d : lang('lang.norecyet'));
					} else {
						switch ($row['from_type']) {
						case Transactions::ADMIN:
							return '<i class="fa fa-user-secret" title="' . lang('transaction.from.to.type.' . $row['from_type']) . '"></i> ' . ($d ?: '<i class="text-muted">' . lang('lang.norecyet') . '</i>');
						case Transactions::PLAYER:
							return '<i class="fa fa-user" title="' . lang('transaction.from.to.type.' . $row['from_type']) . '"></i> ' . ($d ? '<a href="/player_management/userInformation/' . $row['from_id'] . '" target="_blank">' . $d . '</a>' : '<i class="text-muted">' . lang('lang.norecyet') . '</i>');
						case Transactions::AFFILIATE:
							return '<i class="fa fa-users" title="' . lang('transaction.from.to.type.' . $row['from_type']) . '"></i> ' . ($d ?: '<i class="text-muted">' . lang('lang.norecyet') . '</i>');
						default:
							return '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
						}
					}
				},
				'name' => lang('pay.from.procssby'),
			),
			array( // #5 Amount
				'dt' => $i++,
				'alias' => 'amount',
				'select' => 'round(transactions.amount,2)',//round
				'formatter' => function ($d) use ($is_export) {
					if ($is_export) {
						return $this->utils->formatCurrencyNoSym($d);
					} else {
						return $d == 0 ? '<span class="text-muted">' . $this->utils->formatCurrencyNoSym($d) . '</span>' : '<strong>' . $this->utils->formatCurrencyNoSym($d) . '</strong>';
					}
				},
				'name' => lang('pay.amt'),
			),
			array( // #6 Before Balance
				'dt' => $i++,
				'alias' => 'before_balance',
				'select' => 'transactions.before_balance',
				'formatter' => 'currencyFormatter',
				'name' => lang('player.ut06'),
			),
			array( // #7 After Balance
				'dt' => $i++,
				'alias' => 'after_balance',
				'select' => 'transactions.after_balance',
				'formatter' => 'currencyFormatter',
				'name' => lang('player.ut07'),
			),
			array( // #8 Bank/Payment Type
				// Others => N/A
				'dt' => $i++,
				'alias' => 'content',
				'select' => 'transactions.id',
				'name' => lang('pay.payment_account_flag'),
				'formatter' => function ($d, $row) use ($is_export) {
					$formatted = lang('N/A');

					$transaction_type = $row['transaction_type'];
					switch($transaction_type){
						case Transactions::DEPOSIT:
							/// cloned from depositList(), Keyword: "'name' => lang('pay.collection_name'),"
							// languageFormatter
							$formatted = $this->data_tables->languageFormatter($row['sale_orders_payment_type_name']);
							break;
						case Transactions::WITHDRAWAL:
							$formatted = $row['banktype_default_payment_flag'];
							break;
						case Transactions::MANUAL_ADD_BALANCE:
						case Transactions::MANUAL_SUBTRACT_BALANCE:
							$formatted = lang('N/A');
							break;
					}

					if($transaction_type == Transactions::WITHDRAWAL){
						/// cloned from withdrawList(), Keyword: "'name' => lang('pay.payment_account_flag'),"
						$payment_account_flag = $formatted;
						$payment_flag = lang('pay.manual_online_payment');
						switch ($payment_account_flag) {
							case Payment_account::FLAG_MANUAL_LOCAL_BANK:
								$payment_flag = lang('pay.local_bank_offline');
								break;
							case Payment_account::FLAG_MANUAL_ONLINE_PAYMENT:
								$payment_flag = lang('pay.manual_online_payment');
								break;
							case Payment_account::FLAG_AUTO_ONLINE_PAYMENT:
								$payment_flag = lang('pay.auto_online_payment');
								break;
						}
						$formatted = $payment_flag; // override
					}

					return $formatted;
				} // EOF 'formatter' => function ($d, $row) use ($is_export) {...
			),
			array( // #9 Request ID
				'dt' => $i++,
				'alias' => 'transaction_id',
				'select' => 'transactions.id',
				'formatter' => 'defaultFormatter',
				'name' => $this->utils->getConfig('enabled_rename_transation_id') ? lang('pay.transid') : lang('Request ID'),
			),
			array( // #10 External ID (in case player deposit over AMB)
				'dt' => $i++,
				'alias' => 'external_transaction_id',
				'select' => 'transactions.external_transaction_id',
				'formatter' => 'defaultFormatter',
				'name' => lang('External ID'),
			),

			array( // #11 Order ID
				'dt' => $i++,
				'alias' => 'secure_id',
				'select' => 'transactions.request_secure_id',
				'name' => lang('Order ID'),
				'formatter' => function ($d, $row) use ($is_export)  {
					$formatted = lang('N/A');

					$transaction_type = $row['transaction_type'];
					switch($transaction_type){
						case Transactions::DEPOSIT:
						case Transactions::WITHDRAWAL:
							$formatted = $d;
							break;
						default:
							$formatted = lang('N/A');
							break;
					}
					return $formatted;
				},
			),
			array( // #12 Promo Rule
				'dt' => $i++,
				'alias' => 'promo_name',
				'select' => 'promorules.promoName',
				'name' => lang('Promo Rule'),
				'formatter' => function ($d, $row) use ($is_export)  {
					// 'playerpromo' => "playerpromo.playerpromoId = transactions.player_promo_id",
					// 'promorules' => "promorules.promorulesId = playerpromo.promorulesId",
					if($d == Promorules::SYSTEM_MANUAL_PROMO_RULE_NAME){
						$d = lang('promo.'. Promorules::SYSTEM_MANUAL_PROMO_RULE_NAME);
					}

					if ($is_export) {
						return $d ?: lang('lang.norecyet');
					} else {
						return $d ?: '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
					}
				},
			),
			array( // #13 Action Log
				'dt' => $i++,
				'alias' => 'note',
				'select' => 'transactions.note',
				'name' => lang('player.ut13'), // Action Log
				'formatter' => function ($d, $row) use ($is_export) {
					if ($is_export) {
						return strip_tags($d);
					} else {
						return $d;
					}
				},
			),
			array(  // #14 Internal Note
				'dt' => $i++,
				'alias' => 'content',
				'select' => 'transactions.id',
				'name' => lang('Internal Note'),
				'formatter' => function ($d, $row) use ($is_export)  {
					$formatted = null; // default
					$transaction_type = $row['transaction_type'];
					switch($transaction_type){
						case Transactions::DEPOSIT:
							// cloned from depositList(). Keyword: "'name' => lang('Internal Note'),"
							$sale_order_id = $row['sale_orders_id'];
							$display_last_notes = false;
							$limit_the_number_of_words_displayed = true;
							$allNotes = $this->sale_orders_notes->getNotesByNoteType(Sale_orders_notes::INTERNAL_NOTE, $sale_order_id, $display_last_notes, false, $limit_the_number_of_words_displayed);
							$exportNotes = $this->sale_orders_notes->getNotesByNoteType(Sale_orders_notes::INTERNAL_NOTE, $sale_order_id, $display_last_notes, false,false);

							if (!$is_export) {
								$formatted =  $allNotes ?: '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
							}else{
								$formatted =  str_replace('</br>', "\n", $exportNotes) ?: lang('lang.norecyet');
							}
							break;
						case Transactions::WITHDRAWAL:
							// cloned from withdrawList(). Keyword: "'name' => lang('Internal Note'),"
							$walletAccountId = $row['walletAccountId'];
							$display_last_notes = false;
							$limit_the_number_of_words_displayed = true;
							$allNotes = $this->walletaccount_notes->getNotesByNoteType(Walletaccount_notes::INTERNAL_NOTE, $walletAccountId, $display_last_notes, false, $limit_the_number_of_words_displayed);

							$exportNotes = $this->walletaccount_notes->getNotesByNoteType(Walletaccount_notes::INTERNAL_NOTE, $walletAccountId, $display_last_notes, false, false);

							if (!$is_export) {
								$formatted =  $allNotes ?: '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
							}else{
								$formatted =  str_replace('</br>', "\n", $exportNotes) ?: lang('lang.norecyet');
							}
							break;
						case Transactions::MANUAL_ADD_BALANCE:
						case Transactions::MANUAL_SUBTRACT_BALANCE:
							$formatted = lang('N/A');
							break;
					}
					return $formatted;
				},
			),

		);	// EOF $columns = array(...

		# END DEFINE COLUMNS #################################################################################################################################################
		$table = 'transactions';
		$joins = array(
			'adminusers fromUser' => "transactions.from_type = 1 AND fromUser.userId = transactions.from_id",
			'player fromPlayer' => "transactions.from_type = 2 AND fromPlayer.playerId = transactions.from_id",
			'affiliates fromAffiliate' => "transactions.from_type = 3 AND fromAffiliate.affiliateId = transactions.from_id",
			'adminusers toUser' => "transactions.to_type = 1 AND toUser.userId = transactions.to_id",
			'player toPlayer' => "transactions.to_type = 2 AND toPlayer.playerId = transactions.to_id",
			'affiliates toAffiliate' => "transactions.to_type = 3 AND toAffiliate.affiliateId = transactions.to_id",
			'external_system' => "external_system.id = transactions.sub_wallet_id",
			// 'promotype' => "promotype.promotypeId = transactions.promo_category", /// , disable for Not in the requirements.
			'playerpromo' => "playerpromo.playerpromoId = transactions.player_promo_id",
			'promorules' => "promorules.promorulesId = playerpromo.promorulesId",
  			'sale_orders' => 'sale_orders.transaction_id = transactions.id',
			'walletaccount' => 'walletaccount.transaction_id = transactions.id',
            // 'promocmssetting' => 'promocmssetting.promoCmsSettingId = playerpromo.promoCmsSettingId' /// , disable for Not in the requirements.
			/// for banktype Begin
			'localbankwithdrawaldetails' => 'localbankwithdrawaldetails.walletAccountId = walletaccount.walletAccountId',
			'playerbankdetails' => 'playerbankdetails.playerBankDetailsId = localbankwithdrawaldetails.playerBankDetailsId',
			'banktype' => 'banktype.bankTypeId = playerbankdetails.bankTypeId',
			/// for banktype End

		);

		# START PROCESS SEARCH FORM #################################################################################################################################################
		$where = array();
		$values = array();
		$group_by=[];
		$having=[];
		$distinct = false;
		$external_order=[];

		$input = $this->data_tables->extra_search($request);

		/// cloned form transaction_details()
		if( ! empty( $where_status ) ){

			$where[] = $where_status;

		}else{

			$where[]="transactions.status = ?";
			$values[]=Transactions::APPROVED;

		}

		// $where[], $values[]
		/// Transaction Date
		// name="dateRangeValueStart"
		// name="dateRangeValueEnd"
		if (isset($input['dateRangeValueStart'], $input['dateRangeValueEnd']) && ( ! empty( $input['dateRangeValueStart'] ) && ! empty( $input['dateRangeValueEnd'] ) )) {
			$where[] = "transactions.created_at BETWEEN ? AND ?";
			$values[] = $input['dateRangeValueStart'];
			$values[] = $input['dateRangeValueEnd'];
		}
		//
		// //NOTE: For command line export ONLY !/ ask me: aris
		// if (isset($request['CdateRangeValueStart'], $request['CdateRangeValueEnd'])) {
		// 	$where[] = "transactions.created_at >= ? AND transactions.created_at <= ?";
		// 	$values[] = $request['CdateRangeValueStart'];
		// 	$values[] = $request['CdateRangeValueEnd'];
		// }

		/// Username:
		// name="search_by"
		// name="memberUsername"
		// name="no_affiliate"
		// name="no_agent"
		//convert memberUsername to player id
		if (isset($input['memberUsername']) && !empty($input['memberUsername'])) {
			if (isset($input['search_by']) && $input['search_by'] == 2) {
				//search player id first
				$memberUsername=$input['memberUsername'];
				$player_id = $this->player_model->getPlayerIdByUsername($memberUsername);
			} else {
				$where[] = "(fromPlayer.username LIKE ? OR toPlayer.username LIKE ?)";
				$values[] = '%' . $input['memberUsername'] . '%';
				$values[] = '%' . $input['memberUsername'] . '%';
			}
		}

		$player_id=intval($player_id);
		if ($player_id>0) {
			$where[] = "((transactions.to_type=2 and transactions.to_id = ?) OR (transactions.from_type=2 and transactions.from_id = ?) )";
			$values[] = $player_id;
			$values[] = $player_id;
		}

		/// Belongs To Affiliate
		// name="belongAff"
		// name="aff_include_all_downlines"
		if (isset($input['no_affiliate']) && $input['no_affiliate'] == true) {
			$where[] = "(fromPlayer.affiliateId IS NULL AND toPlayer.affiliateId IS NULL)";
		} else if (isset($input['belongAff']) && !empty($input['belongAff'])) {

			$this->load->model('affiliatemodel');
			$affiliateId = $this->affiliatemodel->getAffiliateIdByUsername($input['belongAff']);
			$affiliateIds=null;

			if (isset($input['aff_include_all_downlines']) && $input['aff_include_all_downlines']
					&& !empty($affiliateId)) {
				$affiliateIds = $this->affiliatemodel->includeAllDownlineAffiliateIds($affiliateId);
			}

			if(!empty($affiliateIds)){
				$where[] = '(fromPlayer.affiliateId IN('.implode(',', $affiliateIds).') OR toPlayer.affiliateId IN('.implode(',', $affiliateIds).'))';
			}else{
				$where[] = "(fromPlayer.affiliateId = ? OR toPlayer.affiliateId = ?)";
				$values[] = $affiliateId;
				$values[] = $affiliateId;
			}

		}
        if (isset($input['no_agent']) && $input['no_agent'] == true) {
            $where[] = "(fromPlayer.agent_id IS NULL AND toPlayer.agent_id IS NULL OR fromPlayer.agent_id = 0 AND toPlayer.agent_id = 0)";
        }


		/// Transaction ID
		// name="transaction_id"
		if (isset($input['transaction_id'])) {
			$where[] = "transactions.id = ?";
			$values[] = $input['transaction_id'];
		}

		// /// Promo Category
		// // name="promo_category", disable for Not in the requirements.
		// if (isset($input['promo_category'])) {
		// 	$where[] = "transactions.promo_category = ?";
		// 	$values[] = $input['promo_category'];
		// }

		/// Flag:
		// name="flag"
		if (isset($input['flag'])) {
			$where[] = "transactions.flag = ?";
			$values[] = $input['flag'];
		}

		/// From
		// name="from_type"
		// name="fromUsername"
		// name="fromUsernameId"
		if (isset($input['from_type'])) {
			$where[] = "transactions.from_type = ?";
			$values[] = $input['from_type'];
		}
		if (isset($input['fromUsernameId'])) {
			$where[] = "(CASE transactions.from_type WHEN ? THEN fromUser.userId WHEN ? THEN fromPlayer.playerId WHEN ? THEN fromAffiliate.affiliateId ELSE NULL END) = ?";
			$values[] = Transactions::ADMIN;
			$values[] = Transactions::PLAYER;
			$values[] = Transactions::AFFILIATE;
			$values[] = $input['fromUsernameId'];
		}

		/// To
		// name="to_type"
		// name="toUsername"
		// name="toUsernameId"
		if (isset($input['to_type'])) {
			$where[] = "transactions.to_type = ?";
			$values[] = $input['to_type'];
		}

		if (isset($input['toUsernameId'])) {
			$where[] = "(CASE transactions.to_type WHEN ? THEN toUser.userId WHEN ? THEN toPlayer.playerId WHEN ? THEN toAffiliate.affiliateId ELSE NULL END) = ? ";
			$values[] = Transactions::ADMIN;
			$values[] = Transactions::PLAYER;
			$values[] = Transactions::AFFILIATE;
			$values[] = $input['toUsernameId'];
		}

		/// Transaction Amount >=
		// name="amountStart"
		if (isset($input['amountStart'])) {
			$where[] = "transactions.amount >= ?";
			$values[] = $input['amountStart'];
		}

		/// Transaction Amount <=
		// name="amountEnd"
		if (isset($input['amountEnd'])) {
			$where[] = "transactions.amount <= ?";
			$values[] = $input['amountEnd'];
		}



		/// Referrer
		// name="referrer", disable for Not in the requirements.
		// if (isset($input['referrer'])) {
		// 	$joins['player referrer'] = 'toPlayer.refereePlayerId = referrer.playerId';
		// 	$where[] = "referrer.username = ?";
  		// 	$values[] = $input['referrer'];
		// }

		/// Transaction Type in player's info page via transactionTypeFilter
		if (isset($input['transactionTypeFilter'])) {
			$where[] = "transactions.transaction_type in (".$input['transactionTypeFilter'].")";
		}

		/// Transaction Type
		// name="transaction_type"
		//
		//check not all type
		if (isset($input['transaction_type_all']) && $input['transaction_type_all'] == 'checkall') {
			//limit transaction types
			// Transactions::DEPOSIT, Transactions::WITHDRAWAL, Transactions::MANUAL_ADD_BALANCE, Transactions::MANUAL_SUBTRACT_BALANCE

			$where[] = "transactions.transaction_type IN (" . implode(',', array_fill(0, count($input['transaction_type']), '?')) . ")";
			$values = array_merge($values, $input['transaction_type']);

		} else if (isset($input['transaction_type'])) {
			if (is_array($input['transaction_type'])) {
				$where[] = "transactions.transaction_type IN (" . implode(',', array_fill(0, count($input['transaction_type']), '?')) . ")";
				$values = array_merge($values, $input['transaction_type']);
			} else {
				$where[] = "transactions.transaction_type = ?";
				$values[] = $input['transaction_type'];
			}
		}

		// to see test player transaction  on their user information page
		$where[] = "toPlayer.deleted_at IS NULL";

		// pre_processing_end

		$columns = $this->checkIfEnabled($this->utils->isEnabledFeature('enable_adjustment_category'), array('adjustment_category_id'), $columns);
		$columns = $this->checkIfEnabled($this->utils->isEnabledFeature('enable_tag_column_on_transaction'), array('tagName'), $columns);

		// $this->utils->debug_log('balance_transaction_details.Columns', $columns);

		// data_sql_start

		$countOnlyField='transactions.id';
		if( ! empty( $not_datatable ) ){
			if($mobile){ // TODO: If need, check where working for?
				$result = $this->data_tables->get_data(null, $columns, $table, $where, $values, $joins, $group_by, $having, $distinct, $external_order, $not_datatable, $countOnlyField);
			}else{
				$result = $this->data_tables->get_data($request, $columns, $table, $where, $values, $joins, $group_by, $having, $distinct, $external_order, $not_datatable, $countOnlyField);
			}
			return $result;
		}


		if($is_export){
            $this->data_tables->options['is_export']=true;
			// $this->data_tables->options['only_sql']=true;
            if(empty($csv_filename)){
                $csv_filename=$this->utils->create_csv_filename(__FUNCTION__);
            }
            $this->data_tables->options['csv_filename']=$csv_filename;
		}

		// @todo TODO: need check where working in?
		$countOnlyField='transactions.id';
		$result = $this->data_tables->get_data($request, $columns, $table, $where, $values, $joins,
			$group_by, $having, $distinct, $external_order, $not_datatable, $countOnlyField);
		if(!empty($this->data_tables->last_query) ){
			$result['list_last_query'] = $this->data_tables->last_query;
		}
		//
		if($is_export){
		    //drop result if export
			return $csv_filename;
		}

		// data_sql_end

		// summary_sql_start
		unset($joins['transactions_tag']);
		unset($joins['manual_subtract_balance_tag']);
		$summary = $this->data_tables->summary($request, $table, $joins, 'transactions.transaction_type, external_system.system_code, SUM(round(transactions.amount,2)) total_amount', 'transactions.transaction_type, external_system.id', $columns, $where, $values);
		if(!empty($this->data_tables->last_query) ){
			$result['summary_605_last_query'] = $this->data_tables->last_query;
		}
		// summary_sql_end

		if (isset($input['transaction_type'])) {

			// deposit_sql_start $this->benchmark->mark('deposit_sql_start');
			$deposit_summary = $this->data_tables->summary($request, $table, $joins,
				'sale_orders.payment_type_name, sale_orders.payment_account_name, SUM(round(transactions.amount,2)) total_amount, SUBSTR(sale_orders.payment_account_number,-4) account_num', 'sale_orders.payment_account_id, `sale_orders`.`payment_account_number`', $columns, $where, $values);
			if(!empty($this->data_tables->last_query) ){
				$result['summary_613_last_query'] = $this->data_tables->last_query;
			}
			// deposit_sql_end $this->benchmark->mark('deposit_sql_end');

			// summary_processing_start // $this->benchmark->mark('summary_processing_start');
			$result['summary'] = array();
			foreach ($summary as $row) {
				$arr = array(
					'transaction_name' => lang('transaction.transaction.type.' . $row['transaction_type']) . ($row['system_code'] ? (' - ' . $row['system_code']) : ''),
					'amount' => $this->utils->formatCurrencyNoSym($row['total_amount']),
					'transaction_type' => $row['transaction_type'],
				);

				array_push($result['summary'], $arr);
			}

			foreach ($deposit_summary as $row) {
				$result['bank_summary'][lang($row['payment_type_name']) . ' - ' . $row['payment_account_name'] . ($row['account_num'] ? (' - ' . $row['account_num']) : '')] = $this->utils->formatCurrencyNoSym($row['total_amount']);
			}

			// summary_processing_end // $this->benchmark->mark('summary_processing_end');
		} else {

			// deposit_sql_start // $this->benchmark->mark('deposit_sql_start');
			$deposit_summary = $this->data_tables->summary($request, $table, $joins, 'SUM(round(transactions.amount,2)) AS total_amount', 'transactions.transaction_type', $columns, $where, $values);
			if(!empty($this->data_tables->last_query) ){
				$result['summary_636_last_query'] = $this->data_tables->last_query;
			}
			// deposit_sql_end // $this->benchmark->mark('deposit_sql_end');

			// summary_processing_start //$this->benchmark->mark('summary_processing_start');
			$result['summary'] = array();
			foreach ($summary as $row) {
				$arr = array(
					'transaction_name' => lang('transaction.transaction.type.' . $row['transaction_type']) . ($row['system_code'] ? (' - ' . $row['system_code']) : ''),
					'amount' => $this->utils->formatCurrencyNoSym($row['total_amount']),
					'transaction_type' => $row['transaction_type'],
				);
			}
			// summary_processing_end // $this->benchmark->mark('summary_processing_end');
		}

		return $result;
	} // EOF balance_transaction_details

	/**
	 * detail: get transaction details of a certain player
	 *
	 * @param int $player_id
	 * @param array $request
	 * @param Boolean $is_export
	 *
	 * @return array
	 */
	public function transaction_details($player_id, $request, $is_export = false, $not_datatable = '', $where_status = '',$mobile = '',$csv_filename=null) {

		$readOnlyDB = $this->getReadOnlyDB();

		$this->load->library('data_tables', array("DB" => $readOnlyDB));
		$this->load->model(array('transactions', 'player_model', 'affiliatemodel', 'transaction_notes','common_category','walletaccount_notes','promorules'));
		$this->load->helper(['player_helper']);

		$this->data_tables->is_export = $is_export;

		$this->benchmark->mark('pre_processing_start');

		$common_category = $this->common_category;
		$from_player_center = isset($request['from_player_center'])? $request['from_player_center'] : false;
		# START DEFINE COLUMNS #################################################################################################################################################
		$i = 0;
		$columns = array(
			array(
				'alias' => 'id',
				'select' => 'transactions.id',
			),
			array(
				'alias' => 'from_type',
				'select' => 'transactions.from_type',
			),
			array(
				'alias' => 'from_id',
				'select' => 'transactions.from_id',
			),
			array(
				'alias' => 'to_type',
				'select' => 'transactions.to_type',
			),
			array(
				'alias' => 'to_id',
				'select' => 'transactions.to_id',
			),
			array(
				'alias' => 'order_id',
				'select' => 'sale_orders.secure_id',
			),
			array(
				'alias' => 'walletAccountId',
				'select' => 'walletaccount.walletAccountId',
			),
			array(
				'alias' => 'status',
				'select' => 'transactions.status',
			),
            array(
                'alias' => 'promorulesId',
                'select' => 'promorules.promorulesId',
            ),
			array(
				'dt' => $i++,
				'alias' => 'created_at',
				'select' => 'transactions.created_at',
				'formatter' => 'dateTimeFormatter',
				'name' => lang('player.ut01'),
			),
			array(
				'dt' => $i++,
				'alias' => 'transaction_type',
				'select' => 'transactions.transaction_type',
				'formatter' => function ($d, $row) use ($is_export) {
					$val = '';
					if ($is_export) {
						$val = lang('transaction.transaction.type.' . $d) ?: lang('lang.norecyet');
					} else {
						$val = lang('transaction.transaction.type.' . $d) ?: '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
						//sub query
						// "GROUP_CONCAT(manual_subtract_balance_tag.adjust_tag_name ORDER BY transactions.id, manual_subtract_balance_tag.id SEPARATOR ';')"
						$tags=$this->transactions->getManualSubtractBalanceTagsBy($row['id']);
						if(!empty($tags)){
							foreach ($tags as $tag) {
								$val.=' <span class="label label-info">'. lang($tag['adjust_tag_name']) . '</span>';
							}
						}
					}
					return $val;
				},
				'name' => lang('player.ut02'),
			),
			array(
				'dt' => $from_player_center ? null :$i++,
				'alias' => 'from_username',
				'select' => '(CASE transactions.from_type WHEN 1 THEN fromUser.username WHEN 2 THEN fromPlayer.username WHEN 3 THEN fromAffiliate.username ELSE NULL END)',
				'formatter' => function ($d, $row) use ($is_export) {
					if ($is_export) {
						return ($d ? $d : lang('lang.norecyet'));
					} else {
						switch ($row['from_type']) {
						case Transactions::ADMIN:
							return '<i class="fa fa-user-secret" title="' . lang('transaction.from.to.type.' . $row['from_type']) . '"></i> ' . ($d ?: '<i class="text-muted">' . lang('lang.norecyet') . '</i>');
						case Transactions::PLAYER:
							return '<i class="fa fa-user" title="' . lang('transaction.from.to.type.' . $row['from_type']) . '"></i> ' . ($d ? '<a href="/player_management/userInformation/' . $row['from_id'] . '" target="_blank">' . $d . '</a>' : '<i class="text-muted">' . lang('lang.norecyet') . '</i>');
						case Transactions::AFFILIATE:
							return '<i class="fa fa-users" title="' . lang('transaction.from.to.type.' . $row['from_type']) . '"></i> ' . ($d ?: '<i class="text-muted">' . lang('lang.norecyet') . '</i>');
						default:
							return '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
						}
					}
				},
				'name' => lang('player.ut03'),
			),
			array(
				'dt' => $from_player_center ? null : $i++,
				'alias' => 'to_username',
				'select' => '(CASE transactions.to_type WHEN 1 THEN toUser.username WHEN 2 THEN toPlayer.username WHEN 3 THEN toAffiliate.username WHEN 5 THEN to_username ELSE NULL END)',
				'formatter' => function ($d, $row) use ($is_export) {
					if ($is_export) {
						return ($d ? $d : lang('lang.norecyet'));
					} else {
						switch ($row['to_type']) {
						case Transactions::ADMIN:
							return '<i class="fa fa-user-secret" title="' . lang('transaction.from.to.type.' . $row['to_type']) . '"></i> ' . ($d ?: '<i>' . lang('lang.norecyet') . '</i>');
						case Transactions::PLAYER:
							return '<i class="fa fa-user" title="' . lang('transaction.from.to.type.' . $row['to_type']) . '"></i> ' . ($d ? '<a href="/player_management/userInformation/' . $row['to_id'] . '" target="_blank">' . $d . '</a>' : '<i class="text-muted">' . lang('lang.norecyet') . '</i>');
						case Transactions::AFFILIATE:
							return '<i class="fa fa-users" title="' . lang('transaction.from.to.type.' . $row['to_type']) . '"></i> ' . ($d ?: '<i>' . lang('lang.norecyet') . '</i>');
						case Transactions::LIVECHAT_ADMIN:
							return '<i class="fa fa-user" title="' . lang('transaction.from.to.type.' . $row['to_type']) . '"></i> ' . ($d ?  $d  : '<i class="text-muted">' . lang('lang.norecyet') . '</i>');
						default:
							return '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
						}
					}
				},
				'name' => lang('player.ut04'),
			),
			array(
                'dt' => $this->utils->getConfig('enabled_viplevel_filter_in_transactions') ? ($from_player_center ? null : $i++) : null,
                'alias' => 'group_level',
                'select' => 'toPlayer.levelName',
                'formatter' => function ($d, $row) {
                    $getUpdatedGroupAndLevel = $this->player_model->getPlayerCurrentLevel($row['to_id']);
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
            ),
			array(
				'dt' => $from_player_center ? null : $i++,
				'alias' => 'tagName',
				'select' => 'transactions.to_id',
				'name' => lang("player.41"),
				'formatter' => function ($d, $row) use ($is_export, $request, $from_player_center) {
					if ($this->utils->isEnabledFeature('enable_tag_column_on_transaction')) {
						$input = $this->data_tables->extra_search($request);
						if (isset($input['tag_list']) ){
							if ($input['tag_list']!= ''){
								if (!$is_export) {
									return tag_formatted($input['tag_list']);
								} else {
									return strip_tags(tag_formatted($input['tag_list']));
								}
							}
						} else {
							if(!$is_export) {

								return player_tagged_list($row['to_id']);
							} else {
								return player_tagged_list($row['to_id'], true);

							}
						}
					} else {
						return strip_tags(tag_formatted($d));
					}
				},
			),
			array(
				'dt' => $i++,
				'alias' => 'amount',
				'select' => 'round(transactions.amount,2)',//round
				'formatter' => function ($d) use ($is_export) {
					if ($is_export) {
						return $this->utils->formatCurrencyNoSym($d);
					} else {
						return $d == 0 ? '<span class="text-muted">' . $this->utils->formatCurrencyNoSym($d) . '</span>' : '<strong>' . $this->utils->formatCurrencyNoSym($d) . '</strong>';
					}
				},
				'name' => lang('Transaction Amount'),
			),
			array(
				'dt' => $i++,
				'alias' => 'before_balance',
				'select' => 'transactions.before_balance',
				'formatter' => 'currencyFormatter',
				'name' => lang('player.ut06'),
			),
			array(
				'dt' => $i++,
				'alias' => 'after_balance',
				'select' => 'transactions.after_balance',
				'formatter' => 'currencyFormatter',
				'name' => lang('player.ut07'),
			),
			array(
				'dt' => $i++,
				'alias' => 'subwallet',
				'select' => 'external_system.system_code',
				'formatter' => 'defaultFormatter',
				'name' => lang('player.ut08'),
			),
			array(
				'dt' => $i++,
				'alias' => 'promoTypeName',
				'select' => 'promotype.promoTypeName',
				'formatter' => 'languageFormatter',
				'name' => lang('cms.promoCat'),
			),
			array(
				'dt' => $i++,
				'alias' => 'adjustment_category_id',
				'select' => 'transactions.adjustment_category_id',
				'formatter' => function ($d, $row) use ($is_export,$common_category) {
					$category_info = $common_category->getCategoryInfoById($d);
					$category_name = null;

					if(!empty($category_info)){
						if(isset($category_info['category_name'])){
							$category_name = lang($category_info['category_name']);
						}
					}

					if ($is_export) {
						return $category_name ? : lang('lang.norecyet');
					} else {
						return $category_name ?: '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
					}
				},
				'name' => lang('Adjustment Category'),
			),
            array(
                'dt' => $from_player_center ? null : $i++,
                'alias' => 'promo_title',
                'select' => 'promocmssetting.promoName',
                'name' => lang('cms.promotitle'),
                'formatter' => function ($d, $row) use ($is_export) {
                    if($d == Promorules::SYSTEM_MANUAL_PROMO_RULE_NAME){
                        $d = lang('promo.'. Promorules::SYSTEM_MANUAL_PROMO_RULE_NAME);
                    }else if(empty($d)){
                        $d = lang('pay.noPromo');
                    }else{
						if (!$is_export) {

							$d = '<a href="#" data-toggle="modal" data-target="#promoDetails"  onclick="return viewPromoRuleDetails(' . $row['promorulesId'] . ',' . BaseModel::FALSE . ');">' . $d . '</a>';
						}
                    }

                    if (!$is_export) {
                        return $d ?: '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
                    }else{
                        return $d ?: lang('lang.norecyet');
                    }
                },
            ),
			array(
				'dt' => $i++,
				'alias' => 'promo_name',
				'select' => 'promorules.promoName',
				'formatter' => function ($d, $row) use ($is_export) {

					if($d == Promorules::SYSTEM_MANUAL_PROMO_RULE_NAME){
						$d = lang('promo.'. Promorules::SYSTEM_MANUAL_PROMO_RULE_NAME);
					}

					if ($is_export) {
						return $d ?: lang('lang.norecyet');
					} else {
						return $d ?: '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
					}
				},
				'name' => lang('Promo Rule'),
			),
            array(
                'dt' => $from_player_center ? null : $i++,
                'alias' => 'promo_request_id',
                'select' => 'transactions.player_promo_id',
                'name' => lang('Promo Request ID'),
                'formatter' => function ($d, $row) use ($is_export) {
                    if ($is_export) {
                        return $d ?: lang('lang.norecyet');
                    } else {
                        return $d ?: '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
                    }
                },
            ),
			array(
				'dt' => $from_player_center ? null : $i++,
				'alias' => 'total_before_balance',
				'select' => 'transactions.changed_balance',
				// 'formatter' => 'currencyFormatter',
				'formatter' => function ($d, $row) use ($is_export) {
					if ($is_export) {
						return '';
					} else {
						return '<a href="javascript:void(0)" onclick="_pubutils.showDiffBigWallet(this)" data-diffbigwallet="#bal_info_' . $row['id'] . '" class="btn btn-primary btn-xs diff_bigwallet">' . lang('Show') . '</a><div id="bal_info_' . $row['id'] . '" style="display:none">' . $d . '</div>';
					}
				},
				'name' => lang('Changed Balance'),
			),
			array(
				'dt' => $from_player_center ? null : $i++,
				'alias' => 'flag',
				'select' => 'transactions.flag',
				'formatter' => function ($d, $row) use ($is_export) {
					if ($is_export) {
						return lang('transaction.flag.' . $d) ?: lang('lang.norecyet');
					} else {
						return lang('transaction.flag.' . $d) ?: '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
					}
				},
				'name' => lang('player.ut10'),
			),
			array(
				'dt' => $i++,
				'alias' => 'secure_id',
				'select' => 'transactions.request_secure_id',
				'name' => lang('Request ID'),
				'formatter' => function ($d, $row) use ($is_export) {
					return $d;
				},
			),
			array(
				'dt' => $from_player_center ? null : $i++,
				'alias' => 'transaction_id',
				'select' => 'transactions.id',
				'formatter' => 'defaultFormatter',
				'name' => lang('pay.transid'),
			),
			array(
				'dt' => $from_player_center ? null : $i++,
				'alias' => 'external_transaction_id',
				'select' => 'transactions.external_transaction_id',
				'formatter' => 'defaultFormatter',
				'name' => lang('player.ut11'),
			),
			array(
				'dt' => $from_player_center ? null : $i++,
				'alias' => 'note',
				'select' => 'transactions.note',
				'formatter' => 'defaultFormatter',
				'name' => lang('player.ut13'),
				'formatter' => function ($d, $row) use ($is_export) {
					if ($is_export) {
						return strip_tags($d);
					} else {
						return $d;
					}
				}
			),
			array(
				'dt' => $from_player_center ? null : $i++,
				'alias' => 'Remarks',
				'select' => 'sale_orders.reason',
				'name' => lang('Remarks'),
				'formatter' => function ($d, $row) use ($is_export) {

						if ($row['transaction_type'] == Transactions::DEPOSIT) {
							return $d ?: lang('lang.norecyet');
						}

						$allNotes = '';
						if ($row['transaction_type'] == Transactions::WITHDRAWAL) {
							$allNotes = $this->walletaccount_notes->getNotesByNoteType(Walletaccount_notes::ACTION_LOG, $row['walletAccountId']);
						}

						if ($row['transaction_type'] == Transactions::AUTO_ADD_CASHBACK_TO_BALANCE) {
							$allNotes = $this->transaction_notes->getNotesByTransaction(Transactions::AUTO_ADD_CASHBACK_TO_BALANCE, $row['id']);
						}

						if(!empty($allNotes)){
							if($is_export){
								return str_replace(array('</br>','\n'), "\n", $allNotes);
							}
							return  nl2br($allNotes);
						}

						return lang('lang.norecyet');
				},
			),
		);
		# END DEFINE COLUMNS #################################################################################################################################################

		$table = 'transactions';
		$joins = array(
			'adminusers fromUser' => "transactions.from_type = 1 AND fromUser.userId = transactions.from_id",
			'player fromPlayer' => "transactions.from_type = 2 AND fromPlayer.playerId = transactions.from_id",
			'affiliates fromAffiliate' => "transactions.from_type = 3 AND fromAffiliate.affiliateId = transactions.from_id",
			'adminusers toUser' => "transactions.to_type = 1 AND toUser.userId = transactions.to_id",
			'player toPlayer' => "transactions.to_type = 2 AND toPlayer.playerId = transactions.to_id",
			'affiliates toAffiliate' => "transactions.to_type = 3 AND toAffiliate.affiliateId = transactions.to_id",
			'external_system' => "external_system.id = transactions.sub_wallet_id",
			'promotype' => "promotype.promotypeId = transactions.promo_category",
			'playerpromo' => "playerpromo.playerpromoId = transactions.player_promo_id",
			'promorules' => "promorules.promorulesId = playerpromo.promorulesId",
  			'sale_orders' => 'sale_orders.transaction_id = transactions.id',
			'walletaccount' => 'walletaccount.transaction_id = transactions.id',
            'promocmssetting' => 'promocmssetting.promoCmsSettingId = playerpromo.promoCmsSettingId'
		);

		// if ($this->utils->isEnabledFeature('enable_tag_column_on_transaction') && $is_export){
		// 	$joins['playertag toPlayerTag'] = 'toPlayerTag.playerId = transactions.to_id';
		// 	$joins['tag'] = 'tag.tagId = toPlayerTag.tagId';
		// }


		# START PROCESS SEARCH FORM #################################################################################################################################################
		$where = array();
		$values = array();
		$group_by=[];
		$having=[];
		$distinct = false;
		$external_order=[];

		$input = $this->data_tables->extra_search($request);


		// -- Validate if date exists and user permission to load transaction data , OGP-9225
		// -- Check if CLI Exporting, if not then proceed with date validation
		if(!isset($input['CdateRangeValueStart']) && !isset($input['CdateRangeValueEnd'])){

			// -- Check if from and to exists, and Validate permission
			$this->load->library('permissions');

			// Skip permission check when Cli export
			$has_permission = $is_export || (isset($input['is_player_transaction_history']) ? $this->permissions->checkPermissions('transaction_report') : $this->permissions->checkPermissions('report_transactions')) || isset($input['is_aff']);

			$enableTransactionReportInPlayerCenter = $this->utils->getConfig('enable_transaction_report_in_player_center');
			if((!isset($input['dateRangeValueStart'])
				|| !isset($input['dateRangeValueEnd'])
				|| trim($input['dateRangeValueStart']) == ''
				|| trim($input['dateRangeValueEnd']) == ''
				|| !$has_permission)
				&& !($enableTransactionReportInPlayerCenter && $from_player_center)) {
				$result = $this->data_tables->empty_data($request);
				$result['header_data'] = $this->data_tables->get_columns($columns);
				$result['summary'] = array();
				return $result;
			}
		}

		if (isset($input['referrer'])) {
			$joins['player referrer'] = 'toPlayer.refereePlayerId = referrer.playerId';
			$where[] = "referrer.username = ?";
  			$values[] = $input['referrer'];
		}


		if( ! empty( $not_datatable ) ) $input = $request;

		if( ! empty( $where_status ) ){

			$where[] = $where_status;

		}else{

			$where[]="transactions.status = ?";
			$values[]=Transactions::APPROVED;

		}

		if (isset($input['transaction_id'])) {
			$where[] = "transactions.id = ?";
			$values[] = $input['transaction_id'];
		}

		if (isset($input['from_type'])) {
			$where[] = "transactions.from_type = ?";
			$values[] = $input['from_type'];
		}
		//tag id
		if (isset($input['tag_list'])) {
			$joins['playertag toPlayerTag'] = 'toPlayerTag.playerId = transactions.to_id';
			$joins['tag'] = 'tag.tagId = toPlayerTag.tagId';
			$where[] = "toPlayerTag.tagId = ?";
			$values[] = $input['tag_list'];
		}

		if (isset($input['fromUsernameId'])) {
			$where[] = "(CASE transactions.from_type WHEN ? THEN fromUser.userId WHEN ? THEN fromPlayer.playerId WHEN ? THEN fromAffiliate.affiliateId ELSE NULL END) = ?";
			$values[] = Transactions::ADMIN;
			$values[] = Transactions::PLAYER;
			$values[] = Transactions::AFFILIATE;
			$values[] = $input['fromUsernameId'];
		}

		if (isset($input['no_affiliate']) && $input['no_affiliate'] == true) {
			$where[] = "(fromPlayer.affiliateId IS NULL AND toPlayer.affiliateId IS NULL)";
		} else if (isset($input['belongAff']) && !empty($input['belongAff'])) {

			$this->load->model('affiliatemodel');
			$affiliateId = $this->affiliatemodel->getAffiliateIdByUsername($input['belongAff']);
			$affiliateIds=null;

			if (isset($input['aff_include_all_downlines']) && $input['aff_include_all_downlines']
					&& !empty($affiliateId)) {
				$affiliateIds = $this->affiliatemodel->includeAllDownlineAffiliateIds($affiliateId);
			}

			if(!empty($affiliateIds)){
				$where[] = '(fromPlayer.affiliateId IN('.implode(',', $affiliateIds).') OR toPlayer.affiliateId IN('.implode(',', $affiliateIds).'))';
			}else{
				$where[] = "(fromPlayer.affiliateId = ? OR toPlayer.affiliateId = ?)";
				$values[] = $affiliateId;
				$values[] = $affiliateId;
			}

		}
        if (isset($input['no_agent']) && $input['no_agent'] == true) {
            $where[] = "(fromPlayer.agent_id IS NULL AND toPlayer.agent_id IS NULL OR fromPlayer.agent_id = 0 AND toPlayer.agent_id = 0)";
        }

		if (isset($input['to_type'])) {
			$where[] = "transactions.to_type = ?";
			$values[] = $input['to_type'];
		}

		if (isset($input['toUsernameId'])) {
			$where[] = "(CASE transactions.to_type WHEN ? THEN toUser.userId WHEN ? THEN toPlayer.playerId WHEN ? THEN toAffiliate.affiliateId ELSE NULL END) = ? ";
			$values[] = Transactions::ADMIN;
			$values[] = Transactions::PLAYER;
			$values[] = Transactions::AFFILIATE;
			$values[] = $input['toUsernameId'];
		}

		if (isset($input['amountStart'])) {
			$where[] = "transactions.amount >= ?";
			$values[] = $input['amountStart'];
		}

		if (isset($input['amountEnd'])) {
			$where[] = "transactions.amount <= ?";
			$values[] = $input['amountEnd'];
		}

		if (isset($input['transactionTypeFilter'])) {
			$where[] = "transactions.transaction_type in (".$input['transactionTypeFilter'].")";
		}

		if (isset($input['dateRangeValueStart'], $input['dateRangeValueEnd']) && ( ! empty( $input['dateRangeValueStart'] ) && ! empty( $input['dateRangeValueEnd'] ) )) {
			$where[] = "transactions.created_at BETWEEN ? AND ?";
			$values[] = $input['dateRangeValueStart'];
			$values[] = $input['dateRangeValueEnd'];
		}

		 //NOTE: For command line export ONLY !/ ask me: aris
		if (isset($request['CdateRangeValueStart'], $request['CdateRangeValueEnd'])) {
			$where[] = "transactions.created_at >= ? AND transactions.created_at <= ?";
			$values[] = $request['CdateRangeValueStart'];
			$values[] = $request['CdateRangeValueEnd'];
		}

		if (isset($input['flag'])) {
			$where[] = "transactions.flag = ?";
			$values[] = $input['flag'];
		}

		if (isset($input['promo_category'])) {
			$where[] = "transactions.promo_category = ?";
			$values[] = $input['promo_category'];
		}

        if(isset($input['promo_rule'])){
			$where[] = "promorules.promorulesId = ?";
			$values[] = $input['promo_rule'];
		}

		//convert memberUsername to player id
		if (isset($input['memberUsername']) && !empty($input['memberUsername'])) {
			if (isset($input['search_by']) && $input['search_by'] == 2) {
				//search player id first
				$memberUsername=$input['memberUsername'];
				$player_id = $this->player_model->getPlayerIdByUsername($memberUsername);
			} else {
				$where[] = "(fromPlayer.username LIKE ? OR toPlayer.username LIKE ?)";
				$values[] = '%' . $input['memberUsername'] . '%';
				$values[] = '%' . $input['memberUsername'] . '%';
			}
		}

		if (isset($input['player_level'])) {
			$player_level = is_array($input['player_level']) ? implode(',', $input['player_level']) : $input['player_level'];
            $where[] = "toPlayer.levelId in (" . $player_level .")";
        }

		$player_id=intval($player_id);
		if ($player_id>0) {
			$where[] = "((transactions.to_type=2 and transactions.to_id = ?) OR (transactions.from_type=2 and transactions.from_id = ?) )";
			$values[] = $player_id;
			$values[] = $player_id;
		}

		//check not all type
		if (isset($input['transaction_type_all']) && $input['transaction_type_all'] == 'checkall') {
			//ignore transaction type
		} else if (isset($input['transaction_type'])) {
			if (is_array($input['transaction_type'])) {
				$where[] = "transactions.transaction_type IN (" . implode(',', array_fill(0, count($input['transaction_type']), '?')) . ")";
				$values = array_merge($values, $input['transaction_type']);
			} else {
				$where[] = "transactions.transaction_type = ?";
				$values[] = $input['transaction_type'];
			}
		}

		if (isset($input['disabled_transaction_types'])) {
			$where[] = "transactions.transaction_type != ?";
			$values[] = $input['disabled_transaction_types'];
		}

		//this is for filtering the record one by one (withdrawal, deposit, transfer)
		if( isset($input['trans_type']) ){

			switch ( $input['trans_type'] ) {
				case 'transfer':
					$where[] = "transactions.transaction_type IN (?,?)";
					$values[] = Transactions::TRANSFER_FROM_SUB_WALLET_TO_MAIN_WALLET;
					$values[] = Transactions::TRANSFER_TO_SUB_WALLET_FROM_MAIN_WALLET;
					break;
				default:
					$where[] = "transactions.transaction_type = ?";
					$values[] = $input['trans_type_value'] ;
					break;
			}

		}

		// to see test player transaction  on their user information page
		$where[] = "toPlayer.deleted_at IS NULL";


		$this->utils->debug_log('where', $where, 'values', $values);

		$payment_account_all=isset($input['payment_account_all']) ? $input['payment_account_all']=='true' : false ;

		if(!$payment_account_all){
			if (isset($input['payment_account_id'])) {

				if (is_array($input['payment_account_id'])) {
					if (isset($input['payment_account_id_null'])) {
						$where[] = "(transactions.payment_account_id IN (" . implode(',', array_fill(0, count($input['payment_account_id']), '?')) . ") OR transactions.payment_account_id IS NULL)";
					} else {
						$where[] = "transactions.payment_account_id IN (" . implode(',', array_fill(0, count($input['payment_account_id']), '?')) . ")";
					}
					$values = array_merge($values, $input['payment_account_id']);

				} else {
					if (isset($input['payment_account_id_null'])) {
						$where[] = "(transactions.payment_account_id = ? OR transactions.payment_account_id IS NULL)";
					} else {
						$where[] = "transactions.payment_account_id = ?";
					}
					$values[] = $input['payment_account_id'];
				}
			} else if (isset($input['payment_account_id_null'])) {
				$where[] = "transactions.payment_account_id IS NULL";
			}
		}

		if($this->utils->isEnabledFeature('enable_adjustment_category')){
			if(!empty($input['adjustment_category_id'])){
                $where[] = "transactions.adjustment_category_id = ?";
                $values[] = $input['adjustment_category_id'] ;
			}
		}

		$this->benchmark->mark('pre_processing_end');

		# END PROCESS SEARCH FORM #################################################################################################################################################
		$this->benchmark->mark('data_sql_start');

		$columns = $this->checkIfEnabled($this->utils->isEnabledFeature('enable_adjustment_category'), array('adjustment_category_id'), $columns);
		$columns = $this->checkIfEnabled($this->utils->isEnabledFeature('enable_tag_column_on_transaction'), array('tagName'), $columns);

		$this->utils->debug_log('Columns', $columns);

		$countOnlyField='transactions.id';
		if( ! empty( $not_datatable ) ){
			if($mobile){
				$result = $this->data_tables->get_data(null, $columns, $table, $where, $values, $joins, $group_by, $having, $distinct, $external_order, $not_datatable, $countOnlyField);
			}else{
				$result = $this->data_tables->get_data($request, $columns, $table, $where, $values, $joins, $group_by, $having, $distinct, $external_order, $not_datatable, $countOnlyField);
			}
			return $result;
		}

		if($is_export){
            $this->data_tables->options['is_export']=true;
			// $this->data_tables->options['only_sql']=true;
            if(empty($csv_filename)){
                $csv_filename=$this->utils->create_csv_filename(__FUNCTION__);
            }
            $this->data_tables->options['csv_filename']=$csv_filename;
		}

		$countOnlyField='transactions.id';
		$result = $this->data_tables->get_data($request, $columns, $table, $where, $values, $joins,
			$group_by, $having, $distinct, $external_order, $not_datatable, $countOnlyField);

		if($is_export){
		    //drop result if export
			return $csv_filename;
		}

		$this->benchmark->mark('data_sql_end');

		$this->benchmark->mark('summary_sql_start');
		unset($joins['transactions_tag']);
		unset($joins['manual_subtract_balance_tag']);
		$summary = $this->data_tables->summary($request, $table, $joins, 'transactions.transaction_type, external_system.system_code, SUM(round(transactions.amount,2)) total_amount', 'transactions.transaction_type, external_system.id', $columns, $where, $values);
		$this->benchmark->mark('summary_sql_end');


		if (isset($input['transaction_type'])) {

			$this->benchmark->mark('deposit_sql_start');
			$deposit_summary = $this->data_tables->summary($request, $table, $joins,
				'sale_orders.payment_type_name, sale_orders.payment_account_name, SUM(round(transactions.amount,2)) total_amount, SUBSTR(sale_orders.payment_account_number,-4) account_num', 'sale_orders.payment_account_id, `sale_orders`.`payment_account_number`', $columns, $where, $values);
			$this->benchmark->mark('deposit_sql_end');

			$this->benchmark->mark('summary_processing_start');
			$result['summary'] = array();
			foreach ($summary as $row) {
				$arr = array(
					'transaction_name' => lang('transaction.transaction.type.' . $row['transaction_type']) . ($row['system_code'] ? (' - ' . $row['system_code']) : ''),
					'amount' => $this->utils->formatCurrencyNoSym($row['total_amount']),
					'transaction_type' => $row['transaction_type'],
				);

				array_push($result['summary'], $arr);
			}

			foreach ($deposit_summary as $row) {
				$result['bank_summary'][lang($row['payment_type_name']) . ' - ' . $row['payment_account_name'] . ($row['account_num'] ? (' - ' . $row['account_num']) : '')] = $this->utils->formatCurrencyNoSym($row['total_amount']);
			}

			$this->benchmark->mark('summary_processing_end');
		} else {
			$this->benchmark->mark('deposit_sql_start');
			$deposit_summary = $this->data_tables->summary($request, $table, $joins, 'SUM(round(transactions.amount,2)) AS total_amount', 'transactions.transaction_type', $columns, $where, $values);
			$this->benchmark->mark('deposit_sql_end');

			$this->benchmark->mark('summary_processing_start');
			$result['summary'] = array();
			foreach ($summary as $row) {
				$arr = array(
					'transaction_name' => lang('transaction.transaction.type.' . $row['transaction_type']) . ($row['system_code'] ? (' - ' . $row['system_code']) : ''),
					'amount' => $this->utils->formatCurrencyNoSym($row['total_amount']),
					'transaction_type' => $row['transaction_type'],
				);
			}
			$this->benchmark->mark('summary_processing_end');
		}

		return $result;
	}


	/*not real transcation for fast finished only end*/

	public function transactionsByWalletDeposit($player_id, $request, $not_datatable = '') {

		$readOnlyDB = $this->getReadOnlyDB();

		$this->load->library('data_tables', array("DB" => $readOnlyDB));
		$this->load->model(array('sale_order', 'wallet_model', 'player_attached_proof_file_model', 'sale_orders_notes'));

		$this->benchmark->mark('pre_processing_start');

		# START DEFINE COLUMNS #################################################################################################################################################
		$i = 0;
		$columns = array(
			array(
				'dt' => $i++,
				'alias' => 'orderId',
				'select' => 'sale_orders.secure_id',
				'name' => lang('pay.sale_order_id'),
			),
			array(
				'dt' => $i++,
				'alias' => 'created_at',
				'select' => 'sale_orders.created_at',
				'formatter' => 'dateTimeFormatter',
				'name' => lang('pay.reqtime'),
			),
			array(
				'dt' => $i++,
				'alias' => 'second_category_flag',
				'select' => 'payment_account.second_category_flag',
				'formatter' => function ($d) {
					$secondCats = $this->utils->getPaymentAccountSecondCategoryAllFlagsKV();
					$secondCat = isset($secondCats[$d]) ? $secondCats[$d] : lang('N/A');
                    return $secondCat;
				},
				'name' => lang('Type'),
			),
			array(
				'dt' => $i++,
				'alias' => 'status',
				'select' => 'sale_orders.status',
				'formatter' => function ($d) {
				    $status = array(
				        Sale_order::STATUS_PROCESSING => lang('Pending'),
				        Sale_order::STATUS_SETTLED => lang('Approved'),
				        Sale_order::STATUS_BROWSER_CALLBACK => lang('Approved'),
				        Sale_order::STATUS_DECLINED => lang('Declined')
				    );
					return $status[$d];
				},
				'name' => lang('Status'),
			),
			array(
				'dt' => $i++,
				'alias' => 'amount',
				'select' => 'sale_orders.amount',
				'formatter' => function ($d) {
					return $this->utils->toCurrencyNumber($d);
				},
				'name' => lang('Amount'),
			),
			array(
				'dt' => $i++,
				'alias' => 'player_fee',
				'select' => 'sale_orders.player_fee',
				'formatter' => function ($d) {
					return $this->utils->toCurrencyNumber($d);
				},
				'name' => lang('Fee from Player'),
			),
            array(
				'dt' => $i++,
				'alias' => 'content',
				'select' => 'sale_orders.id',
				'name' => lang('Player Deposit Note'),
				'formatter' => function ($sale_order_id){
					$display_last_notes = false;
					$donotShowUsername = true;
					$allNotes = $this->sale_orders_notes->getNotesByNoteType(Sale_orders_notes::PLAYER_NOTES, $sale_order_id, $display_last_notes, $donotShowUsername);
					return $allNotes ?: lang('lang.norecyet');
				},
			),
            array(
				'dt' => $i++,
				'alias' => 'content',
				'select' => 'sale_orders.id',
				'name' => lang('Notes'),
				'formatter' => function ($sale_order_id){
					$display_last_notes = false;
					$donotShowUsername = true;
					$allNotes = $this->sale_orders_notes->getNotesByNoteType(Sale_orders_notes::EXTERNAL_NOTE, $sale_order_id, $display_last_notes, $donotShowUsername);
					return $allNotes ?: lang('lang.norecyet');
				},
			),
            array(
                'dt' => $i++,
                'alias' => 'receipt',
                'name' => lang('cms.uploadBanner'),
                'formatter' => function ($d, $row) use ($player_id){
                    $receipt_info = $this->player_attached_proof_file_model->getDepositReceiptFileList($player_id, $row['sale_orders_id']);
                    if(!empty($receipt_info)){
                        return $receipt_info;
                    }
                    return null;
                },
            ),
            array(
                'dt' => $i++,
                'select' => 'sale_orders.id',
                'alias' => 'sale_orders_id',
            ),
            array(
                'select' => 'sale_orders.show_reason_to_player',
                'alias' => 'show_reason_to_player',
                'name' => lang('sys.gd11'),
            ),
		);
		# END DEFINE COLUMNS #################################################################################################################################################

		$table = 'sale_orders';
		$joins = array(
			'payment_account' => 'sale_orders.payment_account_id = payment_account.id',
			'banktype' => 'payment_account.payment_type_id = banktype.bankTypeId',
		);

		# START PROCESS SEARCH FORM #################################################################################################################################################
		$where = array();
		$values = array();
		$group_by=[];
		$having=[];
		$distinct = true;
		$external_order=[]; //[['column'=>'id', 'dir'=> 'desc']];
		$input = $this->data_tables->extra_search($request);

		// $where[] = "sale_orders.payment_kind = ? ";
		// $values[] = Sale_order::PAYMENT_KIND_DEPOSIT;

		if (isset($input['dateRangeValueStart'], $input['dateRangeValueEnd']) && ( ! empty( $input['dateRangeValueStart'] ) && ! empty( $input['dateRangeValueEnd'] ) ))
		{
			$where[] = "sale_orders.created_at BETWEEN ? AND ?";
			$values[] = $input['dateRangeValueStart'];
			$values[] = $input['dateRangeValueEnd'];
		}

		if ($player_id) {
			$where[] = "sale_orders.player_id = ? ";
			$values[] = $player_id;
		}

		$this->utils->debug_log('where', $where, 'values', $values);

		$this->benchmark->mark('pre_processing_end');

		# END PROCESS SEARCH FORM #################################################################################################################################################
		$this->benchmark->mark('data_sql_start');

		$result = $this->data_tables->get_data($request, $columns, $table, $where, $values, $joins, $group_by, $having, $distinct, $external_order, $not_datatable);

		$this->utils->printLastSQL();

		$this->benchmark->mark('data_sql_end');

		$this->benchmark->mark('summary_sql_start');

		//only count successed
		$where[] = "sale_orders.status = ?";
		$values[] = Sale_order::STATUS_SETTLED;
		$summary = $this->data_tables->summary($request, $table, $joins, 'SUM(sale_orders.amount) total_amount', null, $columns, $where, $values);
		$this->benchmark->mark('summary_sql_end');

		$this->benchmark->mark('summary_processing_start');
		$result['summary'] = array();
		foreach ($summary as $row) {
			$arr = array(
				'amount' => $this->utils->formatCurrencyNoSym($row['total_amount']),
			);

			array_push($result['summary'], $arr);
		}

		$this->benchmark->mark('summary_processing_end');

		return $result;
	}

	public function transactionsByWalletWithdraw($player_id, $request, $not_datatable = '') {


		$readOnlyDB = $this->getReadOnlyDB();

		$this->load->library('data_tables', array("DB" => $readOnlyDB));
		$this->load->model(array('player_model', 'transaction_notes', 'wallet_model','walletaccount_notes'));

		$this->benchmark->mark('pre_processing_start');

		$withdrawalStages=$this->operatorglobalsettings->getCustomWithdrawalProcessingStage();
		$this->utils->debug_log('withdrawalStages', $withdrawalStages);

		$enable_withdrawl_fee_from_player = $this->utils->getConfig('enable_withdrawl_fee_from_player');
		$enabled_player_cancel_pending_withdraw = $this->utils->getConfig('enabled_player_cancel_pending_withdraw');

		# START DEFINE COLUMNS #################################################################################################################################################
		$i = 0;
		$columns = array(
			// array(
			// 	'alias' => 'dwLocation',
			// 	'select' => 'walletaccount.dwLocation',
			// 	'formatter' => 'defaultFormatter',
			// 	'name' => 'dwLocation',
			// ),
			array(
				'alias' => 'walletAccountId',
				'select' => 'walletaccount.walletAccountId',
			),
			array(
				'alias' => 'playerId',
				'select' => 'walletaccount.playerId',
			),
			array(
				'alias' => 'showNotesFlag',
				'select' => 'walletaccount.showNotesFlag',
			),
			array(
				'dt' => $i++,
				'alias' => 'dwDateTime',
				'select' => 'walletaccount.dwDateTime',
				'formatter' => 'dateTimeFormatter',
				'name' => lang('pay.reqtime'),
			),
			array(
				'dt' => $i++,
				'alias' => 'transactionCode',
				'select' => 'walletaccount.transactionCode',
				'formatter' => 'defaultFormatter',
				'name' => lang('Withdrawal Code'),
			),
			array(
				'dt' => $i++,
				'alias' => 'dwStatus',
				'select' => 'walletaccount.dwStatus',
				'formatter' => function ($d) use($withdrawalStages){

					switch ($d) {
		                case Wallet_model::REQUEST_STATUS: return lang('sale_orders.status.3');
		                case Wallet_model::PENDING_REVIEW_STATUS: return lang('sale_orders.status.3');
		                case Wallet_model::PENDING_REVIEW_CUSTOM_STATUS:
		                if($this->utils->getConfig('enable_pending_review_custom')){
							return lang('st.pendingreviewcustom');
		                }else{
							return lang('role.nopermission');
		                }
		                case Wallet_model::LOCK_API_UNKNOWN_STATUS: return lang('sys.vu51');
						case 'approved': return lang('transaction.status.1');
		                case Wallet_model::DECLINED_STATUS: return lang('transaction.status.2');
		                case Wallet_model::PAID_STATUS: return lang('Paid');
		                case Wallet_model::PAY_PROC_STATUS:
							return lang('Payment Processing');
							// return lang(@$withdrawalStages['payProc']['name']);
		                default:
		                	//others
							return lang('sale_orders.status.3');
							// return lang(@$withdrawalStages[str_replace('CS', '',$d)]['name']);
					}
				},
				'name' => lang('Status'),
			),
			array(
				'dt' => $i++,
				'alias' => 'amount',
				'select' => 'walletaccount.amount',
				'formatter' => function ($d) {
					return $this->utils->toCurrencyNumber($d);
				},
				'name' => lang('Amount'),
			),
			array(
				'dt' => $enabled_player_cancel_pending_withdraw ? $i++ : NULL,
				'alias' => 'dwStatus',
				'select' => 'walletaccount.dwStatus',
				'formatter' => function ($d,$row) {
					switch ($d) {
						case Wallet_model::REQUEST_STATUS:

							$options = '<div class="col-md-9" style="padding:0 2px 0 2px"><span class="btn btn-xs btn-info review-btn" onclick="cancel_withdraw(' . $row['walletAccountId'] . ',' . $row['playerId'] . ')">' . lang('lang.cancel') .  '</span></div>';
							break;
						default:
							$options = '<i class="text-muted">' .lang('lang.norecyet') . '</i>';
							break;
					}
					return $options;
				},
				'name' => lang('Action'),
			),
			array(
				'dt' => $enable_withdrawl_fee_from_player ? $i++ : NULL,
				'alias' => 'withdrawal_fee_amount',
				'select' => 'walletaccount.withdrawal_fee_amount',
				'formatter' => function ($d) {
					return $this->utils->toCurrencyNumber($d);
				},
				'name' => lang('fee.withdraw'),
			),
			array(
				'dt' => $i++,
				'alias' => 'content',
				'select' => 'walletaccount.walletAccountId', #'transaction_notes.note',
				'name' => lang('Notes'),
				'formatter' => function ($walletAccountId)  {
					$display_last_notes = false;
					$donotShowUsername = true;
					$allNotes = $this->walletaccount_notes->getNotesByNoteType(Walletaccount_notes::EXTERNAL_NOTE, $walletAccountId, $display_last_notes, $donotShowUsername);
					return $allNotes ?: '<i class="text-muted">' .lang('lang.norecyet') . '</i>';
				},
			),
		);
		# END DEFINE COLUMNS #################################################################################################################################################

		$table = 'walletaccount';
		$joins = array(
			'playeraccount' => 'walletaccount.playerAccountId = playeraccount.playerAccountId',
			'transaction_notes' => 'walletaccount.walletAccountId = transaction_notes.transaction_id'
		);

		# START PROCESS SEARCH FORM #################################################################################################################################################
		$where = array();
		$values = array();
		$group_by=[];
		//$group_by = array('transaction_notes.transaction_id');
		$having=[];
		$distinct = true;
		$external_order=[]; //[['column'=>'id', 'dir'=> 'desc']];

		$input = $this->data_tables->extra_search($request);

		$where[] = "walletaccount.transactionType = ? ";
		$values[] = "withdrawal";

		if (isset($input['dateRangeValueStart'], $input['dateRangeValueEnd']) && ( ! empty( $input['dateRangeValueStart'] ) && ! empty( $input['dateRangeValueEnd'] ) )) {
			$where[] = "walletaccount.dwDateTime BETWEEN ? AND ?";
			$values[] = $input['dateRangeValueStart'];
			$values[] = $input['dateRangeValueEnd'];
		}

		if ($player_id) {
			$where[] = "playeraccount.playerId = ? ";
			$values[] = $player_id;
		}

		$this->utils->debug_log('where', $where, 'values', $values);

		$this->benchmark->mark('pre_processing_end');

		# END PROCESS SEARCH FORM #################################################################################################################################################
		$this->benchmark->mark('data_sql_start');

		$result = $this->data_tables->get_data($request, $columns, $table, $where, $values, $joins, $group_by, $having, $distinct, $external_order, $not_datatable);
		$this->utils->printLastSQL();

		$this->benchmark->mark('data_sql_end');

		$this->utils->debug_log($result);

		$this->benchmark->mark('summary_sql_start');

		$joins_sum = array(
			'playeraccount' => 'walletaccount.playerAccountId = playeraccount.playerAccountId'
		);

		//only count successed
		$where[] = "walletaccount.dwStatus = ?";
		$values[] = "paid";
		$summary = $this->data_tables->summary($request, $table, $joins_sum, 'SUM(walletaccount.amount) total_amount', null, $columns, $where, $values);
		$this->benchmark->mark('summary_sql_end');

		$this->benchmark->mark('summary_processing_start');
		$result['summary'] = array();
		foreach ($summary as $row) {
            // $payment_flag = lang('pay.auto_online_payment');
            // switch ($row['payment_flag']) {
            //     case LOCAL_BANK_OFFLINE:
            //         $payment_flag = lang('pay.local_bank_offline');
            //         break;
            //     case MANUAL_ONLINE_PAYMENT:
            //         $payment_flag = lang('pay.manual_online_payment');
            //         break;
            //     case AUTO_ONLINE_PAYMENT:
            //         $payment_flag = lang('pay.auto_online_payment');
            //         break;
            // }

			$arr = array(
				// 'payment_flag_name' => $payment_flag,
				'amount' => $this->utils->formatCurrencyNoSym($row['total_amount']),
			);

			array_push($result['summary'], $arr);
		}

		$this->benchmark->mark('summary_processing_end');


		return $result;
	}

	public function transactionsByWalletTransfer($player_id, $request, $not_datatable = '') {
		$readOnlyDB = $this->getReadOnlyDB();

		$this->load->library('data_tables', array("DB" => $readOnlyDB));
		$this->load->model(array('player_model'));

		$this->benchmark->mark('pre_processing_start');

		# START DEFINE COLUMNS #################################################################################################################################################
		$i = 0;
		$columns = array(
			array(
				'dt' => $i++,
				'alias' => 'created_at',
				'select' => 'transactions.created_at',
				'formatter' => 'dateTimeFormatter',
				'name' => lang('Transfer time'),
			),
			array(
				'dt' => $i++,
				'alias' => 'transferFrom',
				'select' => "if(transactions.transaction_type=5, 'Main', external_system.system_code )",
				'formatter' => function( $d ) {
					if ($d == "Main")
						return lang('Main Wallet');
					else
						return $d;
				},
				'name' => lang('From'),
			),
			array(
				'dt' => $i++,
				'alias' => 'transferTo',
				'select' => "if(transactions.transaction_type=5, external_system.system_code, 'Main' )",
				'formatter' => function ($d) {
					if ($d == "Main")
						return lang('Main Wallet');
					else
						return $d;
				},
				'name' => lang('player.12'),
			),
			array(
				'dt' => $i++,
				'alias' => 'amount',
				'select' => 'transactions.amount',
				'formatter' => function ($d) {
					return $this->utils->toCurrencyNumber($d);
				},
				'name' => lang('Amount'),
			),
		);
		# END DEFINE COLUMNS #################################################################################################################################################

		$table = 'transactions';
		$joins = array(
			'external_system' => 'external_system.id=transactions.sub_wallet_id',
		);

		# START PROCESS SEARCH FORM #################################################################################################################################################
		$where = array();
		$values = array();
		$group_by=[];
		$having=[];
		$distinct = true;
		$external_order=[]; //[['column'=>'id', 'dir'=> 'desc']];

		$input = $this->data_tables->extra_search($request);

		$where[] = "transactions.from_type = ? ";
		$values[] = Transactions::PLAYER;
		$where[] = "transactions.transaction_type in (?,?) ";
		$values[] = Transactions::TRANSFER_FROM_SUB_WALLET_TO_MAIN_WALLET;
		$values[] = Transactions::TRANSFER_TO_SUB_WALLET_FROM_MAIN_WALLET;

		if (isset($input['dateRangeValueStart'], $input['dateRangeValueEnd']) && ( ! empty( $input['dateRangeValueStart'] ) && ! empty( $input['dateRangeValueEnd'] ) )) {
			$where[] = "transactions.created_at BETWEEN ? AND ?";
			$values[] = $input['dateRangeValueStart'];
			$values[] = $input['dateRangeValueEnd'];
		}

		if ($player_id) {
			$where[] = "transactions.from_id = ? ";
			$values[] = $player_id;
		}

		$this->utils->debug_log('where', $where, 'values', $values);

		$this->benchmark->mark('pre_processing_end');

		# END PROCESS SEARCH FORM #################################################################################################################################################
		$this->benchmark->mark('data_sql_start');

		$result = $this->data_tables->get_data($request, $columns, $table, $where, $values, $joins, $group_by, $having, $distinct, $external_order, $not_datatable);
		$this->utils->printLastSQL();

		$this->benchmark->mark('data_sql_end');

		$this->utils->debug_log($result);

		return $result;
	}

	public function shopHistoryByPlayer($player_id, $request){
		$readOnlyDB = $this->getReadOnlyDB();

		$this->load->library('data_tables', array("DB" => $readOnlyDB));
		$this->load->model(array('player_model', 'shopper_list'));
		$this->benchmark->mark('pre_processing_start');

		$i = 0;
		$columns = array(
			array(
				'dt' => $i++,
				'alias' => 'request_time',
				'select' => 'shopper_list.application_datetime',
				'formatter' => 'dateTimeFormatter',
				'name' => 'Request Time',
			),
			array(
				'dt' => $i++,
				'alias' => 'title',
				'select' => 'shopping_center.title',
				'name' => 'Title',
			),
			array(
				'dt' => $i++,
				'alias' => 'required_points',
				'select' => 'shopper_list.required_points',
				'name' => 'Required Points',
			),
			array(
				'dt' => $i++,
				'alias' => 'request_status',
				'select' => "shopper_list.status",
				'name' => 'Status',
				'formatter' => function ($d) {
					switch($d){
						case(Shopper_list::APPROVED):
							return lang("Approved");
							break;
						case(Shopper_list::DECLINED):
							return lang("Declined");
							break;
						case(Shopper_list::REQUEST):
							return lang("Pending");
							break;
					}
				},
			)
		);

		$table = 'shopper_list';

		$where = array();
		$values = array();

		$input = $this->data_tables->extra_search($request);

		$where[] = "shopper_list.application_datetime BETWEEN ? AND ?";
		$values[] = $input['dateRangeValueStart'];
		$values[] = $input['dateRangeValueEnd'];

		if(!empty($player_id)) {
			$where[] = "shopper_list.player_id = ? ";
			$values[] = $player_id;
		}

		$this->benchmark->mark('pre_processing_end');
		$this->benchmark->mark('data_sql_start');

		$group_by =array();
		$having = array();
		$distinct = true;
		$external_order=array();
		$not_datatable='';
		$joins = array(
			'shopping_center' => 'shopper_list.shopping_item_id=shopping_center.id',
		);
		$result = $this->data_tables->get_data($request, $columns, $table, $where, $values, $joins, $group_by, $having, $distinct, $external_order, $not_datatable);
		$this->utils->printLastSQL();
		$this->benchmark->mark('data_sql_end');

		$this->utils->debug_log($result);
		return $result;
	}

	public function transactionsByWalletCreditMode($player_id, $request, $not_datatable = ''){

		$readOnlyDB = $this->getReadOnlyDB();

		$this->load->library('data_tables', array("DB" => $readOnlyDB));
		$this->load->model(array('transactions'));

		$this->benchmark->mark('pre_processing_start');

		$i = 0;
		$columns = array(
			array(
				'alias' => 'transaction_type',
				'select' => 'agency_creditmode_transactions.transaction_type',
			),
			array(
				'alias' => 'agent_id',
				'select' => 'agency_creditmode_transactions.agent_id',
			),
			array(
                'dt' => $i++,
                'name' => lang('Credit Time'),
                'alias' => 'created_at',
                'select' =>'agency_creditmode_transactions.created_at',
            ),
            array(
                'dt' => $i++,
                'alias' => 'agent_username',
                'select' => 'agency_creditmode_transactions.agent_username',
                'name' => lang('By'),
                'formatter' => function ($d, $row) {
                    if (!empty($d)) {
                        return $d;
                    } else {
                        return lang('N/A');
                    }
                },
            ),
            array(
                'dt' => $i++,
                'alias' => 'amount',
                'select' => 'agency_creditmode_transactions.amount',
                'name' => lang('Amount'),
                'formatter' => function ($d, $row) {
                    if (!empty($d)) {
						if ($row['transaction_type'] == Transactions::DEPOSIT) {
							return sprintf('<span style="font-weight:bold;" class ="text-success">%s</span>',$this->utils->formatCurrencyNoSym($d));
						} else if ($row['transaction_type'] == Transactions::WITHDRAWAL) {
							return sprintf('<span style="font-weight:bold;" class ="text-danger">%s</span>',$this->utils->formatCurrencyNoSym($d));
						}
                    } else {
                        return lang('N/A');
                    }
                },
            ),
			array(
				'dt' => $i++,
				'alias' => 'sub_wallet_id',
				'select' => "external_system.system_code",
				'name' => lang('Wallet'),
                'formatter' => function($d, $row) {
					if (!empty($d))
						return $d;
					else
						return lang('Main Wallet');
				},
			),
		);
		$table = 'agency_creditmode_transactions';
		$joins = array(
			'external_system' => 'external_system.id=agency_creditmode_transactions.sub_wallet_id',
		);

		# START PROCESS SEARCH FORM #################################################################################################################################################
		$where = array();
		$values = array();
		$group_by=[];
		$having=[];
		$distinct = true;
		$external_order=[]; //[['column'=>'id', 'dir'=> 'desc']];

		$input = $this->data_tables->extra_search($request);

		if ( ! empty( $input['dateRangeValueStart'] ) && ! empty( $input['dateRangeValueEnd'] ) ) {
			$where[] = "agency_creditmode_transactions.created_at BETWEEN ? AND ?";
			$values[] = $input['dateRangeValueStart'];
			$values[] = $input['dateRangeValueEnd'];
		}

		if ($player_id) {
			$where[] = "agency_creditmode_transactions.player_id = ? ";
			$values[] = $player_id;
		}

		$where[] = "agency_creditmode_transactions.transaction_type in (?,?) ";
		$values[] = Transactions::DEPOSIT;
		$values[] = Transactions::WITHDRAWAL;

		$this->utils->debug_log('where', $where, 'values', $values);

		$this->benchmark->mark('pre_processing_end');

		# END PROCESS SEARCH FORM #################################################################################################################################################
		$this->benchmark->mark('data_sql_start');

		$result = $this->data_tables->get_data($request, $columns, $table, $where, $values, $joins, $group_by, $having, $distinct, $external_order, $not_datatable);
		$this->utils->printLastSQL();

		$this->benchmark->mark('data_sql_end');

		$this->utils->debug_log($result);

		return $result;

	}

	/**
	 * detail: get summary reports
	 *
	 * @param string $type
	 * @param string $date
	 *
	 * @return array
	 */
	public function report_summary($type = 'YEAR', $date = null, $selected_tag = null) {

		$readOnlyDB = $this->getReadOnlyDB();
		$this->load->model('transactions');

		if ($type == 'DATE') {
			// $readOnlyDB->select("DATE(created_at) AS common_date");
			$readOnlyDB->select("trans_date AS common_date");
		} else if($type == 'YEAR_MONTH') {
			// $readOnlyDB->select("EXTRACT({$type} FROM created_at) AS common_date");
			$readOnlyDB->select("trans_year_month AS common_date");
		}  else if($type == 'YEAR') {
			// $readOnlyDB->select("EXTRACT({$type} FROM created_at) AS common_date");
			$readOnlyDB->select("trans_year AS common_date");
		}
		$readOnlyDB->select_sum(sprintf('IF(transaction_type = %s, amount, 0)', Transactions::DEPOSIT), 'total_deposit');
		$readOnlyDB->select_sum(sprintf('IF(transaction_type = %s, amount, 0)', Transactions::WITHDRAWAL), 'total_withdraw');
		$readOnlyDB->select_sum(sprintf('(CASE WHEN transaction_type IN (%s) THEN amount WHEN transaction_type = %s THEN -amount ELSE 0 END)', implode(',', array(Transactions::ADD_BONUS, Transactions::MEMBER_GROUP_DEPOSIT_BONUS, Transactions::PLAYER_REFER_BONUS, Transactions::RANDOM_BONUS)), Transactions::SUBTRACT_BONUS), 'total_bonus');
		$readOnlyDB->select_sum(sprintf('IF(transaction_type = %s, amount, 0)', Transactions::AUTO_ADD_CASHBACK_TO_BALANCE), 'total_cashback');
		$readOnlyDB->select_sum(sprintf('IF(transaction_type IN (%s), amount, 0)', implode(',', array(Transactions::FEE_FOR_PLAYER, Transactions::FEE_FOR_OPERATOR))), 'total_transaction_fee');
		$readOnlyDB->select_sum(sprintf('(CASE WHEN transaction_type = %s THEN amount WHEN transaction_type = %s THEN -amount ELSE 0 END)', Transactions::DEPOSIT, Transactions::WITHDRAWAL), 'bank_cash_amount');
		$readOnlyDB->from('transactions');
		$readOnlyDB->join('player fromPlayer', 'transactions.from_type = 2 AND fromPlayer.playerId = transactions.from_id', 'left');
		$readOnlyDB->join('player toPlayer', 'transactions.to_type = 2 AND toPlayer.playerId = transactions.to_id', 'left');
        if(!empty($selected_tag)){
            $readOnlyDB->join('playertag', 'playertag.playerId = toPlayer.playerId', 'left');
            $readOnlyDB->where('(playertag.tagId NOT IN ('.implode(',', $selected_tag).") OR playertag.tagId is NULL)");
        }
		$readOnlyDB->where("(fromPlayer.deleted_at IS NULL AND toPlayer.deleted_at IS NULL)");
		$readOnlyDB->where('transactions.status', transactions::APPROVED);

		if ($type == 'DATE') {
			// $readOnlyDB->group_by("DATE(created_at)");
			$readOnlyDB->group_by("trans_date");
		} else if($type == 'YEAR_MONTH') {
			$readOnlyDB->group_by("trans_year_month");
		} else if($type == 'YEAR') {
			$readOnlyDB->group_by("trans_year");
		}

		if ($date) {
			switch ($type) {
			case 'YEAR_MONTH':
				// $readOnlyDB->where(sprintf("EXTRACT(YEAR FROM created_at) = '%s'", $date));
				$readOnlyDB->where(sprintf("trans_year = '%s'", $date));
				break;

			case 'DATE':
				// $readOnlyDB->where(sprintf("EXTRACT(YEAR_MONTH FROM created_at) = '%s'", $date));
				$readOnlyDB->where(sprintf("trans_date = '%s'", $date));
				break;

			default:
				break;
			}
		}


        $this->utils->debug_log('the summary report ------>', $readOnlyDB->_compile_select());

		$query = $readOnlyDB->get();
		return $query->result_array();
	}

	/**
	 * detail: get summary reports 2
	 *
	 * @param string $dateFrom
	 * @param string $dateTo
	 *
	 * @return array
	 */
	public function report_summary_2($dateFrom = null, $dateTo = null) {
		$readOnlyDB = $this->getReadOnlyDB();
		$this->load->model('transactions');

		// $readOnlyDB->select("DATE(created_at) AS common_date");
		$readOnlyDB->select("trans_date AS common_date");
		$readOnlyDB->select_sum(sprintf('IF(transaction_type = %s, amount, 0)', Transactions::DEPOSIT), 'total_deposit');
		$readOnlyDB->select_sum(sprintf('IF(transaction_type = %s, amount, 0)', Transactions::WITHDRAWAL), 'total_withdraw');
		$readOnlyDB->select_sum(sprintf('(CASE WHEN transaction_type IN (%s) THEN amount WHEN transaction_type = %s THEN -amount ELSE 0 END)', implode(',', array(Transactions::ADD_BONUS, Transactions::MEMBER_GROUP_DEPOSIT_BONUS, Transactions::PLAYER_REFER_BONUS, Transactions::RANDOM_BONUS, Transactions::QUEST_BONUS, Transactions::TOURNAMENT_BONUS, Transactions::ROULETTE_BONUS)), Transactions::SUBTRACT_BONUS), 'total_bonus');
		$readOnlyDB->select_sum(sprintf('IF(transaction_type = %s, amount, 0)', Transactions::AUTO_ADD_CASHBACK_TO_BALANCE), 'total_cashback');
		$readOnlyDB->select_sum(sprintf('IF(transaction_type IN (%s), amount, 0)', Transactions::FEE_FOR_OPERATOR), 'total_transaction_fee');
		$readOnlyDB->select_sum(sprintf('IF(transaction_type IN (%s), amount, 0)', Transactions::FEE_FOR_PLAYER), 'total_player_fee');
		$readOnlyDB->select_sum(sprintf('(CASE WHEN transaction_type = %s THEN amount WHEN transaction_type = %s THEN -amount ELSE 0 END)', Transactions::DEPOSIT, Transactions::WITHDRAWAL), 'bank_cash_amount');
		$readOnlyDB->select_sum(sprintf('IF(transaction_type IN (%s), amount, 0)', Transactions::WITHDRAWAL_FEE_FOR_PLAYER), 'total_withdrawal_fee_from_player');
		$readOnlyDB->select_sum(sprintf('IF(transaction_type IN (%s), amount, 0)', Transactions::WITHDRAWAL_FEE_FOR_OPERATOR), 'total_withdrawal_fee_from_operator');

		$readOnlyDB->from('transactions');
		$readOnlyDB->join('player' , "transactions.to_type = 2 AND player.playerId = transactions.to_id");

		// $readOnlyDB->where("DATE(created_at) >=", $dateFrom);
		// $readOnlyDB->where("DATE(created_at) <=", $dateTo);
		$readOnlyDB->where("trans_date >=", $dateFrom);
		$readOnlyDB->where("trans_date <=", $dateTo);
		$readOnlyDB->where('transactions.status', transactions::APPROVED);
		$readOnlyDB->where('player.deleted_at IS NULL');

		// $readOnlyDB->group_by("DATE(created_at)");
		$readOnlyDB->group_by("trans_date");
        $this->utils->debug_log('the summary report 2 2337 ------>', $readOnlyDB->_compile_select());
		$query = $readOnlyDB->get();

		return $query->result_array();
	}

	/**
	 * new summary2 report
	 * @param  string $dateFrom
	 * @param  string $dateTo
	 * @param  bool $month_only
	 * @return array
	 */
    public function report_summary2($dateFrom, $dateTo, $month_only) {

        $_with = $this->utils->getConfig('search_report_summary2_with');
        switch($_with){
            case 'orig':
                $rlt = $this->_report_summary2_in_orig($dateFrom, $dateTo, $month_only);
                break;
            case 'table': // search daily than query daily table, and search monthly than query monthly table.
                $rlt = $this->_report_summary2_with_self_table($dateFrom, $dateTo, $month_only);
                break;
        }
        return $rlt;
    }
    //
    public function _report_summary2_with_self_table($dateFrom, $dateTo, $month_only) {

        $rows = $this->_report_summary2_by_table($dateFrom, $dateTo, $month_only);
        list($summary_table, $dateCol, $_format) = $this->_get_summary_table_dateCol_by_month_only($month_only);
        foreach($rows as $k => $row){
            $_dateStr = $row[$dateCol];
            $rows[$k]['summary_date'] = $_dateStr;
        }
        $retention = $this->_gen_retention_report_summary2_by_table($dateFrom, $dateTo, $month_only);

        foreach($rows as $k => $row){
            $rows[$k]['retention'] = null;
            $_dateStr = $row[$dateCol];
            if( ! empty($retention[$_dateStr]) ){
                $rows[$k]['retention'] = $retention[$_dateStr];
            }
        }
        $retention = [];
        unset($retention);

        $ggr = $this->_gen_ggr_report_summary2_by_table($dateFrom, $dateTo, $month_only);
        foreach($rows as $k => $row){
            $rows[$k]['ggr'] = 0;
            $_dateStr = $row[$dateCol];
            if( ! empty($ggr[$_dateStr]) ){
                $rows[$k]['ggr'] = $ggr[$_dateStr];
            }
        }
        $ggr = [];
        unset($ggr);

        $ret_dp = $this->_gen_ret_dp_report_summary2_by_table($dateFrom, $dateTo, $month_only);
        foreach($rows as $k => $row){
            if($month_only){
                $rows[$k]['ret_dp'] = "0";
            }else{
                $rows[$k]['ret_dp'] = 0;
            }
            $_dateStr = $row[$dateCol];
            if( ! empty($ret_dp[$_dateStr]) ){
                $rows[$k]['ret_dp'] = $ret_dp[$_dateStr];
            }
        }
        $ret_dp = [];
        unset($ret_dp);

        return $rows;
    }
    /**
     * Get monthly rows for report_summary2 from daily data-table.
     *
     * @param string $dateFrom
     * @param string $dateTo
     * @param boolean $month_only
     * @param array $report_summary2_rows
     * @return void
     */
    public function _get_month_only_report_summary2_from_daily($dateFrom, $dateTo){

        $monthly_rows = [];
        $_month_only=true;
        $_dateRangeList = $this->_genDateRangeRows($dateFrom, $dateTo, $_month_only);
        foreach($_dateRangeList as $k => $monthStr){
            $month_dt = $this->_ymDate2dt($monthStr);
            $_dateFrom = $month_dt->format('Y-m-01');
            $_dateTo = $month_dt->format('Y-m-t');
            $_month_only = false;
            $_daily_summary2_rows = $this->_report_summary2_by_table($_dateFrom, $_dateTo, $_month_only);
            $_sum_rows = $this->_sum_rows($_daily_summary2_rows);
            $_max_rows = $this->_max_rows($_daily_summary2_rows);
            // override into count_all_players of $_sum_rows
            $_sum_rows['count_all_players'] = $_max_rows['count_all_players'];
            // adjust dateCol
            if( $this->_ymDate2dt($_sum_rows['summary_date'])->format('Ym') == $monthStr){
                $_monthStr = $monthStr;
            }else{
                $_monthStr = $this->_ymDate2dt($_sum_rows['summary_date'])->format('Ym');
            }
            $_sum_rows['trans_year_month'] = $_monthStr;
            $_sum_rows['summary_trans_year_month'] = $_monthStr;
            unset($_sum_rows['summary_date']);

            $dateFrom_YmStr = $this->_ymDate2dt($dateFrom)->format('Ym');
            $dateTo_YmStr = $this->_ymDate2dt($dateTo)->format('Ym');
            if( $dateFrom_YmStr == $dateTo_YmStr ){
                if(in_array($_sum_rows['trans_year_month'], [$dateFrom_YmStr]) ){
                    array_push($monthly_rows, $_sum_rows);
                }
            }else if( in_array($_sum_rows['trans_year_month'], $_dateRangeList) ){
                array_push($monthly_rows, $_sum_rows);
            }
        }

        /// override the fields,
        // count_deposit_member
        // count_active_member
        // count_new_player
        foreach($monthly_rows as $_index => $monthly_row){
            $year_month = $monthly_row['trans_year_month'];
            $count_deposit_member = $this->get_count_deposit_member('YEAR_MONTH', $year_month);
            $count_active_member = $this->get_count_active_member($year_month, 'total_player_game_month');
            $monthly_rows[$_index]['count_deposit_member'] = $count_deposit_member['count_deposit_member'];
            $monthly_rows[$_index]['count_active_member'] = $count_active_member['count_active_member'];
        }

        return $monthly_rows;
    }
    /**
     * Generate ggr column from the data-table and the existing rows
     *
     * @param string $dateFrom In month_only=1, dateFrom will be Ym format,ex: 202312; In month_only=0,  dateFrom will be Y-m-d format, ex: 2023-12-23.
     * @param string $dateTo In month_only=1, dateTo will be Ym format,ex: 202312; In month_only=0,  dateTo will be Y-m-d format, ex: 2023-12-23.
     * @param boolean $month_only When use in month report, it should assign to true.
     * @param array $report_summary2_rows The rows of summary2_report_monthly or summary2_report_daily.
     * @return array The key-value array,
     * - key will be date format(Ym in month_only=1; Y-m-d in month_only=0 ).
     * - vale will be float format, the value of ggr column.
     */
    public function _gen_ggr_report_summary2_by_table($dateFrom, $dateTo, $month_only, $report_summary2_rows = []){
        $readOnlyDB = $this->getReadOnlyDB();
        /// formula,
        /// month_only=1
        // IF( sum(srd.total_bet) = 0, 0, ( sum(srd.total_payout) / sum(srd.total_bet) ) ) AS ggr
        /// month_only=0
        // IF( (summary2_report_daily.total_bet=0), 0, (summary2_report_daily.total_payout / summary2_report_daily.total_bet) ) AS ggr
        return $this->_apply_formula_report_summary2_by_table( $dateFrom
                                                        , $dateTo
                                                        , $month_only
                                                        , $report_summary2_rows
        , function($_row, $_last_period_row){ // formulaCB
            if( empty($_row['total_payout']) ){
                $_row['total_payout'] = 0;
            }
            // apply to formula,
            if( empty($_row['total_bet']) ){
                $ggr = 0;
            }else{
                $ggr = $_row['total_payout'] / $_row['total_bet'];
            }
            return $ggr;
        });
    }
    public function _gen_ret_dp_report_summary2_by_table($dateFrom, $dateTo, $month_only, $report_summary2_rows = []){
        $readOnlyDB = $this->getReadOnlyDB();

        /// month_only=1
        // IF( (last_srm.count_deposit_member = 0), 0, ( ( srm.count_deposit_member - sum( srd.count_first_deposit ) ) / last_srm.count_deposit_member) ) AS ret_dp,
        //
        /// month_only=0
        // , IF( (last_srd.count_deposit_member=0), 0, ( ( summary2_report_daily.count_deposit_member - summary2_report_daily.count_first_deposit )/ last_srd.count_deposit_member) ) as ret_dp
        return $this->_apply_formula_report_summary2_by_table( $dateFrom
                                                        , $dateTo
                                                        , $month_only
                                                        , $report_summary2_rows
        , function($_row, $_last_period_row){ // formulaCB
            if( empty($_row['count_deposit_member']) ){
                $_row['count_deposit_member'] = 0;
            }
            if( empty($_row['count_first_deposit']) ){
                $_row['count_first_deposit'] = 0;
            }
            // apply to formula,
            if( empty($_last_period_row['count_deposit_member']) ){
                $ret_dp = 0;
            }else{
                $ret_dp = ( $_row['count_deposit_member'] - $_row['count_first_deposit']) / $_last_period_row['count_deposit_member'];
            }
            return $ret_dp;
        });
    }
    public function _gen_retention_report_summary2_by_table($dateFrom, $dateTo, $month_only, $report_summary2_rows = []){

        return $this->_apply_formula_report_summary2_by_table( $dateFrom
                                                        , $dateTo
                                                        , $month_only
                                                        , $report_summary2_rows
        , function($_row, $_last_period_row){ // formulaCB

            if( empty($_row['count_first_deposit']) ){
                $_row['count_first_deposit'] = 0;
            }
            if( empty($_last_period_row['count_active_member']) ){
                $_last_period_row['count_active_member'] = 0;
            }
            if( empty($_row['count_active_member']) ){
                $_row['count_active_member'] = 0;
            }
            // apply to formula,
            if( empty($_last_period_row['count_active_member']) ){
                $retention = 0;
            }else{
                $retention = ($_row['count_active_member'] - $_row['count_first_deposit']) / $_last_period_row['count_active_member'];
            }
            return $retention;
        });
    }
    /**
     * Get the date format,data-table name and date column name by $month_only
     *
     * @param boolean $month_only
     * @return array $summary_table means data-table name; $dateCol means the date column name, and $format means the date format.
     */
    public function _get_summary_table_dateCol_by_month_only($month_only){
        $summary_table = 'summary2_report_daily';
        $dateCol = 'summary_date';
        $format = 'Y-m-d';
        if($month_only){
            $summary_table = 'summary2_report_monthly';
            $dateCol = 'summary_trans_year_month';
            $format = 'Ym';
        }
        return [$summary_table, $dateCol, $format];
    }
    public function _genDateRangeRows($dateFrom, $dateTo, $month_only = false){
        $dateFrom_dt = $this->_ymDate2dt($dateFrom);
        $dateTo_dt = $this->_ymDate2dt($dateTo);

        $do_array_pop = false;
        if($month_only){
            $first = $dateFrom_dt->modify('first day of this month')->format('Y-m-d');
            if($dateFrom_dt->format('Y-m') == $dateTo_dt->format('Y-m')){
                $last = $this->_ymDate2dt($dateTo_dt->format('Y-m'))->modify( 'last day of next month' )->format('Y-m-d');
                $do_array_pop = true; // workaround for get only one of month_only=1.
            }else{
                $last = $dateTo_dt->modify('last day of this month')->format('Y-m-d');
            }
            $format = 'Y-m-d';
        }else{
            $first = $dateFrom_dt->format('Y-m-d').' 00:00:00';
            if($dateFrom_dt->format('Y-m-d') != $dateTo_dt->format('Y-m-d')){
                $last = $dateTo_dt->format('Y-m-d').' 23:59:59';
            }else{
                $do_array_pop = true; // workaround for get only one of month_only=0.
                $last_dt = $this->utils->getNextTime($dateTo_dt, '+ 1 days');
                $last = $last_dt->format('Y-m-d').' 23:59:59';
            }
            $format = 'Y-m-d H:i:s';
        }
        //
        $step = '+1 day';
        if($month_only){
            $step = '+1 month';
        }
        $rangeRows = $this->_getDateRangeRows($first, $last, $step, $format, $do_array_pop);
        return $rangeRows;
    }
    /**
     * For the date column, get the range to rows.
     *
     * @param string $first
     * @param string $last
     * @param string $step
     * @param string $format
     * @param boolean $do_array_pop
     * @return void
     */
    public function _getDateRangeRows($first, $last, $step = '+1 month', $format = 'Ym', $do_array_pop =false){
        $dateRangeRows = $this->utils->dateTimeRangePeriods($first, $last, $step, $format);
        $_count = count($dateRangeRows);
        if($do_array_pop && $_count > 1){
            array_pop($dateRangeRows);
        }
        //
        $rangeRows = []; // for collect
        array_walk($dateRangeRows, function ($row, $key) use ($_count, &$rangeRows) {
            if($key == ($_count-1) ){ // the latest one
                $rangeRows[] = $row['from'];
            	$rangeRows[] = $row['to'];
            }else{
                $rangeRows[$key] = $row['from'];
            }
        });
        return $rangeRows;
    }
    //
    public function _apply_formula_report_summary2_by_table($dateFrom, $dateTo, $month_only, $report_summary2_rows = [], callable $formulaCB){
        /// month_only=1,
        // IF( (last_srm.count_active_member = 0), 0, ( ( srm.count_active_member - sum( srd.count_first_deposit ) ) / last_srm.count_active_member) ) AS retention,
        // (上個月的 count_active_member - 當月的 count_first_deposit) /  上個月的count_active_member
        //
        /// month_only=0,
        // IF( (last_srd.count_active_member=0), 0, ( ( summary2_report_daily.count_active_member - summary2_report_daily.count_first_deposit )/ last_srd.count_active_member) ) as retention

        $readOnlyDB = $this->getReadOnlyDB();
        list($summary_table, $pkCol, $format) = $this->_get_summary_table_dateCol_by_month_only($month_only);

        $rangeRows = $this->_genDateRangeRows($dateFrom, $dateTo, $month_only);

        $summaryPeriods = array_column($report_summary2_rows, $pkCol);

        $retention_rows = [];
        array_walk($rangeRows, function($_period, $k) use( &$summaryPeriods, &$report_summary2_rows, $pkCol, $format, $month_only, &$retention_rows, &$formulaCB) {

            $_period_dt = $this->_ymDate2dt($_period);
            $_period = $_period_dt->format($format);

            // get count_active_member, count_first_deposit
            $_summary2_row = [];
            if( in_array($_period, $summaryPeriods) ){
                // the row is exists in $report_summary2_rows
                $_summary2_row = $this->_extract_row_by_date($_period, $pkCol, $report_summary2_rows);
            }else{ // query rows directly from data table
                $_dateFrom = $_period;
                $_dateTo = $_period;
                $_summary2_rows = $this->_report_summary2_by_table($_dateFrom, $_dateTo, $month_only);
                if( ! empty($_summary2_rows) ){
                    $_summary2_row = $_summary2_rows[0];
                }
                if( ! empty($_summary2_rows) ){ // append to tail of $report_summary2_rows

                    $report_summary2_rows[] = $_summary2_rows[0]; // TODO

                    $summaryPeriods = array_column($report_summary2_rows, $pkCol); // re-assign
                }
            }

            // last_srd / last_srm
            $_last_period = $_period;
            $_dt = $this->_ymDate2dt($_period);
            //
            if($month_only){
                $_last_period = $_dt->modify("-1 month")->format($format);
            }else{
                $_last_period = $_dt->modify("-1 days")->format($format);
            }
            // count_active_member of last_period
            $_last_period_summary2_row = [];
            if( in_array($_last_period, $summaryPeriods) ){
                $_last_period_summary2_row = $this->_extract_row_by_date($_last_period, $pkCol, $report_summary2_rows);
            }else{// query rows directly from data table
                $_dateFrom = $_last_period;
                $_dateTo = $_last_period;
                $_last_period_summary2_rows = $this->_report_summary2_by_table($_dateFrom, $_dateTo, $month_only);
                if( ! empty($_last_period_summary2_rows) ){
                    $_last_period_summary2_row = $_last_period_summary2_rows[0];
                }
                if( ! empty($_last_period_summary2_rows) ){ // append to tail of $report_summary2_rows
                    $report_summary2_rows[] = $_last_period_summary2_rows[0];
                    $summaryPeriods = array_column($report_summary2_rows, $pkCol); // re-assign
                }
            }

            $_rlt = $formulaCB($_summary2_row, $_last_period_summary2_row);
            if( $_rlt !== false){
                $retention_rows[$_period] = $_rlt;
            }

        }, ARRAY_FILTER_USE_BOTH);

        return $retention_rows;
    }
    /**
     * Convert date string( contains year, month and day) to DateTime object
     *
     * @param string $date
     * @param null|string $_specified_format
     * @return DateTime
     */
    public function _ymDate2dt($date, $_specified_format = null){

        $_parsed = [];
        $hasY = false;
        $hasM = false;
        $hasD = false;
        // ref. to https://regex101.com/r/pfxY6B/1
        $re = '/(?P<YYYY>\d{4})-?(?P<mm>\d{2})-?(?P<dd>\d{2})?/';
        preg_match_all($re, $date, $matches, PREG_SET_ORDER, 0);
        if( !empty($matches) ){
            foreach($matches as $matche){
                if(!empty($matche['YYYY'])){
                    $_parsed['YYYY'] = $matche['YYYY'];
                    $hasY = true;
                }
                if(!empty($matche['mm'])){
                    $_parsed['mm'] = $matche['mm'];
                    $hasM = true;
                }
                if(!empty($matche['dd'])){
                    $_parsed['dd'] = $matche['dd'];
                    $hasD = true;
                }
            }
        }
        $dash = '-';
        $hasDash = false;
        if( strpos($date, $dash) !== false){ // contains "-", ex: "Y-m", "Y-m-d"
            $hasDash = true;
        }
        $caseStr = '';
        $caseStr .= ($hasDash)? '1': '0';
        $caseStr .= ($hasY)? '1': '0';
        $caseStr .= ($hasM)? '1': '0';
        $caseStr .= ($hasD)? '1': '0';
        switch($caseStr){
            case '0000';
            $_format4input = '';
            $_date = $date;
            break;
            case '0001';
            $_format4input = 'd';
            $_date = $_parsed['dd'];
            break;
            case '0010';
            $_format4input = 'm';
            $_date = $_parsed['mm'];
            break;
            case '0011';
            $_format4input = 'md';
            $_date = $_parsed['mm']. $_parsed['dd'];
            break;
            case '0100';
            $_format4input = 'Y';
            $_date = $_parsed['YYYY'];
            break;
            case '0101';
            $_format4input = 'Yd';
            $_date = $_parsed['YYYY']. $_parsed['dd'];
            break;
            case '0110';
            $_format4input = 'Ym';
            $_date = $_parsed['YYYY']. $_parsed['mm'];
            break;
            case '0111';
            $_format4input = 'Ymd';
            $_date = $_parsed['YYYY']. $_parsed['mm']. $_parsed['dd'];
            break;

            case '1000';
            $_format4input = '';
            $_date = $date;
            break;
            case '1001';
            $_format4input = 'd';
            $_date = $_parsed['dd'];
            break;
            case '1010';
            $_format4input = 'm';
            $_date = $_parsed['mm'];
            break;
            case '1011';
            $_format4input = 'm-d';
            $_date = $_parsed['mm']. $dash. $_parsed['dd'];
            break;
            case '1100';
            $_format4input = 'Y';
            $_date = $_parsed['YYYY'];
            break;
            case '1101';
            $_format4input = 'Y-d';
            $_date = $_parsed['YYYY']. $dash. $_parsed['dd'];
            break;
            case '1110';
            $_format4input = 'Y-m';
            $_date = $_parsed['YYYY']. $dash. $_parsed['mm'];
            break;
            case '1111';
            $_format4input = 'Y-m-d';
            $_date = $_parsed['YYYY']. $dash. $_parsed['mm']. $dash. $_parsed['dd'];
            break;
        }
        if( empty($_specified_format) ){
            $_dt = DateTime::createFromFormat($_format4input, $_date );
        }else{
            $_dt = DateTime::createFromFormat($_specified_format, $date);
        }
        return $_dt;
    }
    public function _extract_row_by_date($dateStr, $_dateCol, $report_summary2_rows = []) {
        $extracted_row = [];
        $_rows = array_filter($report_summary2_rows, function($_row, $k) use($dateStr, $_dateCol) {
            return ($_row[$_dateCol] == $dateStr);
        }, ARRAY_FILTER_USE_BOTH);
        if(!empty($_rows)){
            $_rows = array_values($_rows);
            $extracted_row = $_rows[0];
        }
        return $extracted_row;
    }
    public function _sum_rows($rows, $except_col_list = ['summary_date', 'summary_trans_year_month']){
        $sum_rows = [];
        foreach($rows as $index => $row){
            foreach($row as $column => $value){
                if( in_array($column, $except_col_list)){
                    if( empty($sum_rows[$column]) ){
                        $sum_rows[$column] = $value;
                    }else if( $sum_rows[$column] == $value ){
                        $sum_rows[$column] = $value;
                    }else{
                        // $this->utils->debug_log('OGP-32069.2945.ignore_sum_rows.column:', $sum_rows[$column], 'column:', $column, 'value:', $value );
                    }
                    continue; //skip this round
                }
                if(empty($sum_rows[$column])){
                    $sum_rows[$column] = 0;
                }
                if(is_numeric($value) ){
                    $sum_rows[$column] += $value;
                }else{
                    $sum_rows[$column] += floatval($value);
                }
            }
        }
        return $sum_rows;
    }
    public function _max_rows($rows, $col_list = ['count_all_players']){
        $max_rows = [];
        $maxCol_list = []; // for compare
        foreach($rows as $index => $row){
            foreach($row as $column => $value){
                $compareColIndex = array_search($column, $col_list);
                if( $compareColIndex !== false){
                    $_columns = array_column($rows, $col_list[$compareColIndex]);
                    $max_rows[$column] = max($_columns);
                }else{
                    $max_rows[$column] = $value;
                }
            }
        }
        return $max_rows;
    }
    //
    /**
     * To directly query by table
     *
     * @param string $dateFrom
     * @param string $dateTo
     * @param boolean $month_only
     * @return array
     */
    public function _report_summary2_by_table($dateFrom, $dateTo, $month_only = false) {
        $readOnlyDB = $this->getReadOnlyDB();

        list($summary_table, $pkCol, $_format) = $this->_get_summary_table_dateCol_by_month_only($month_only);

        if($month_only){ // date(, Y-m-d) convert to Ym
            list($_summary_table4month_only, $_dateCol4month_only, $_format4month_only) = $this->_get_summary_table_dateCol_by_month_only(true);
            $dateFrom_dt = $this->_ymDate2dt($dateFrom);
            $dateTo_dt = $this->_ymDate2dt($dateTo);
            $dateFrom = $dateFrom_dt->format($_format4month_only);
            $dateTo = $dateTo_dt->format($_format4month_only);
        }

        $_select = <<< EOF
$pkCol
, total_bet
, total_win
, total_loss
, total_payout
, total_deposit
, total_withdrawal
, total_bonus
, total_cashback
, total_fee
, total_player_fee
, total_bank_cash_amount
, count_all_players
, count_new_player
, count_first_deposit
, count_second_deposit
, count_deposit_member
, count_active_member
, total_withdrawal_fee_from_player
, total_withdrawal_fee_from_operator
EOF;

        $readOnlyDB->select( $_select, false)
        ->from($summary_table)
            ->where($pkCol. ' >=', $dateFrom)
            ->where($pkCol. ' <=', $dateTo)
            ->order_by($pkCol, 'desc');

        $rows = $this->runMultipleRowArray($readOnlyDB);
        return $rows;
    } // EOF _report_summary2_by_table()
    /**
     * Update/insert data into summary2_report_monthly
     *
     * @param array $data the rows
     * @param callable $getRow4sync The row for sync, array function($trans_year_month, $unique_key, $currencyKey, $row)
     * @param null|array $column_list the columns of the table, summary2_report_monthly
     * @return null|boolean
     */
    public function _syncData2summary2_report_monthly($data, callable $getRow4sync, $column_list = null ){
        $success = null;
        if( is_null($column_list)){
            $column_list = $this->utils->getAllColumnName('summary2_report_monthly');
        }
        $currencyKey=$this->utils->getActiveCurrencyKey();
        if (!empty($data)) {
            //write to db
            foreach ($data as $row) {
                $trans_year_month=$row['trans_year_month'];
                $unique_key=$currencyKey.'-'.$trans_year_month;

                $d=[
                    'summary_trans_year_month'=>$trans_year_month,
                    'unique_key'=>$unique_key,
                    'currency_key'=>$currencyKey,
                    'last_update_time'=>$this->utils->getNowForMysql(),
                ];
                $_d = $getRow4sync($trans_year_month, $unique_key, $currencyKey, $row);
                if( !empty($_d)){
                    $_row = [];
                    foreach($_d as $col => $val){
                        if(in_array($col, $column_list)){
                            $_row[$col] = $val;
                        }
                    }
                    $d = array_merge($d, $_row);
                }
                $this->db->select('id')->from('summary2_report_monthly')->where('summary_trans_year_month', $trans_year_month);
                $id=$this->runOneRowOneField('id');
                if(empty($id)){
                    //insert
                    $success=$this->insertData('summary2_report_monthly', $d);
                }else{
                    //update
                    $this->db->set($d)->where('id', $id);
                    $success=$this->runAnyUpdate('summary2_report_monthly');
                }
                if(!$success){
                    $this->utils->error_log('insert/update summary2 failed', $d, $id);
                    break;
                }

                unset($d);
            }

            unset($data);
        }
        return $success;
    }
    //
	public function _report_summary2_in_orig($dateFrom, $dateTo, $month_only) {
		$readOnlyDB = $this->getReadOnlyDB();

		if($month_only){
			$sql=<<<EOD
SELECT
	DATE_FORMAT( srd.summary_date, "%Y%m" ) AS summary_date,
	sum( srd.total_bet ) AS total_bet,
	sum( srd.total_win ) AS total_win,
	sum( srd.total_loss ) AS total_loss,
	sum( srd.total_payout ) AS total_payout,
	sum( srd.total_deposit ) AS total_deposit,
	sum( srd.total_withdrawal ) AS total_withdrawal,
	sum( srd.total_bonus ) AS total_bonus,
	sum( srd.total_cashback ) AS total_cashback,
	sum( srd.total_fee ) AS total_fee,
	sum( srd.total_player_fee ) AS total_player_fee,
	sum( srd.total_bank_cash_amount ) AS total_bank_cash_amount,
	max( srd.count_all_players ) AS count_all_players, -- aka. total_players
	sum( srd.count_new_player ) AS count_new_player, -- aka. new_players
	sum( srd.count_first_deposit ) AS count_first_deposit,
	sum( srd.count_second_deposit ) AS count_second_deposit,
	sum( srd.total_withdrawal_fee_from_player ) AS total_withdrawal_fee_from_player,
	sum( srd.total_withdrawal_fee_from_operator ) AS total_withdrawal_fee_from_operator,
	srm.count_deposit_member AS count_deposit_member, -- aka. total_deposit_players
	srm.count_active_member AS count_active_member, -- aka. active_member
    IF( (last_srm.count_active_member = 0), 0, ( ( srm.count_active_member - sum( srd.count_first_deposit ) ) / last_srm.count_active_member) ) AS retention,
    IF( (last_srm.count_deposit_member = 0), 0, ( ( srm.count_deposit_member - sum( srd.count_first_deposit ) ) / last_srm.count_deposit_member) ) AS ret_dp,
    IF( sum(srd.total_bet) = 0
        , 0
        , ( sum(srd.total_payout) / sum(srd.total_bet) )
    ) AS ggr
FROM
	summary2_report_daily AS srd
	JOIN summary2_report_monthly AS srm ON DATE_FORMAT( srd.summary_date, "%Y%m" ) = srm.summary_trans_year_month
    LEFT JOIN summary2_report_monthly AS last_srm
        ON DATE_FORMAT( DATE_SUB( srd.summary_date, INTERVAL 1 MONTH), "%Y%m" ) = last_srm.summary_trans_year_month
WHERE
	summary_date >= ?
	AND summary_date <= ?
GROUP BY
	DATE_FORMAT( srd.summary_date, "%Y%m" )
ORDER BY
	DATE_FORMAT( srd.summary_date, "%Y%m" ) DESC

EOD;
// $dateFrom = date("Ym", strtotime($dateFrom));
// $dateTo = date("Ym", strtotime($dateTo));
// $sql=<<<EOD
// SELECT
// 	summary_trans_year_month AS summary_date,
// 	total_bet,
// 	total_win,
// 	total_loss,
// 	total_payout,
// 	total_deposit,
// 	total_withdrawal,
// 	total_bonus,
// 	total_cashback,
// 	total_fee,
// 	total_player_fee,
// 	total_bank_cash_amount,
// 	count_all_players,
// 	count_new_player,
// 	count_first_deposit,
// 	count_second_deposit,
// 	count_deposit_member,
// 	count_active_member,
// 	total_withdrawal_fee_from_player,
// 	total_withdrawal_fee_from_operator
// FROM
// 	summary2_report_monthly
// WHERE
// 	summary_trans_year_month >= ?
// 	AND summary_trans_year_month <= ?
// GROUP BY
// 	summary_trans_year_month
// ORDER BY
// 	summary_trans_year_month DESC
// EOD;
			//group by month
			// $readOnlyDB->select(
			// 	'DATE_FORMAT(summary_date, "%Y%m") as summary_date,'.
			// 	'sum(total_bet) as total_bet,sum(total_win) as total_win,sum(total_loss) as total_loss,sum(total_payout) as total_payout,'.
			// 	'sum(total_deposit) as total_deposit,sum(total_withdrawal) as total_withdrawal,sum(total_bonus) as total_bonus,'.
			// 	'sum(total_cashback) as total_cashback,sum(total_fee) as total_fee,sum(total_bank_cash_amount) as total_bank_cash_amount,'.
			// 	'max(count_all_players) as count_all_players,sum(count_new_player) as count_new_player,sum(count_first_deposit) as count_first_deposit,sum(count_second_deposit) as count_second_deposit',
			// null, false)->from('summary2_report_daily')
			// 	->where('summary_date >=', $dateFrom)
			// 	->where('summary_date <=', $dateTo)
			// 	->group_by('DATE_FORMAT(summary_date, "%Y%m")')
			// 	->order_by('summary_date', 'desc');
			// return $this->runMultipleRowArray($readOnlyDB);

			$rlt = $this->runRawSelectSQLArray($sql, [$dateFrom, $dateTo], $readOnlyDB);
            $this->utils->debug_log('2527._report_summary2.last_query ----->', $readOnlyDB->last_query());
            return $rlt;
		}else{

$_select = <<< EOF
summary2_report_daily.total_bet
, summary2_report_daily.total_win
, summary2_report_daily.total_loss
, summary2_report_daily.total_payout
, summary2_report_daily.summary_date
, summary2_report_daily.total_deposit
, summary2_report_daily.total_withdrawal
, summary2_report_daily.total_bonus
, summary2_report_daily.total_cashback
, summary2_report_daily.total_fee
, summary2_report_daily.total_player_fee
, summary2_report_daily.total_bank_cash_amount
, summary2_report_daily.count_all_players
, summary2_report_daily.count_new_player
, summary2_report_daily.count_first_deposit
, summary2_report_daily.count_second_deposit
, summary2_report_daily.count_deposit_member
, summary2_report_daily.count_active_member
, summary2_report_daily.total_withdrawal_fee_from_player
, summary2_report_daily.total_withdrawal_fee_from_operator
, IF( (last_srd.count_active_member=0), 0, ( ( summary2_report_daily.count_active_member - summary2_report_daily.count_first_deposit )/ last_srd.count_active_member) ) as retention
, IF( (last_srd.count_deposit_member=0), 0, ( ( summary2_report_daily.count_deposit_member - summary2_report_daily.count_first_deposit )/ last_srd.count_deposit_member) ) as ret_dp
, IF( (summary2_report_daily.total_bet=0), 0, (summary2_report_daily.total_payout / summary2_report_daily.total_bet) ) AS ggr
EOF;
			$readOnlyDB->select( $_select, false)
            ->from('summary2_report_daily')
            ->join('summary2_report_daily last_srd', 'DATE_FORMAT( DATE_SUB( summary2_report_daily.summary_date, INTERVAL 1 DAY), "%Y-%m-%d" ) = last_srd.summary_date', 'left')
				->where('summary2_report_daily.summary_date >=', $dateFrom)
				->where('summary2_report_daily.summary_date <=', $dateTo)
				->order_by('summary2_report_daily.summary_date', 'desc');
            $rows = $this->runMultipleRowArray($readOnlyDB);
            $this->utils->debug_log('2562._report_summary2.last_query ----->', $readOnlyDB->last_query());
            return $rows;
		}
	}

	/**
	 * detail: the sum/total of the win and loss payout
	 *
	 * @param string $type
	 * @param string $date
	 * @param array $selected_tag
	 * @return array
	 */
	public function sumBetWinLossPayout($type, $date, $selected_tag = null) {

		$this->load->model(array('game_logs', 'total_player_game_year', 'total_player_game_month', 'total_player_game_day'));

		$this->utils->debug_log('type', $type, 'date', $date);
		$readOnlyDB = $this->getReadOnlyDB();

		$from = $this->utils->getNowForMysql();
		$to = $this->utils->getNowForMysql();
		if ($type == 'YEAR') {
			//get year from $date
			$year = substr($date, 0, 4);
			$from = $year . '-01-01 ' . Utils::FIRST_TIME;
			$to = $year . '-12-31 ' . Utils::LAST_TIME;
			list($totalBet, $totalWin, $totalLoss) = $this->total_player_game_year->sumOperatorBetsWinsLossByDatetime($from, $to, null, null, $readOnlyDB, $selected_tag);

		} else if ($type == 'YEAR_MONTH') {
			//month
			$year_month = substr($date, 0, 7);
			$last_day = (new DateTime($date))->format('t');

			$from = $year_month . '-01 ' . Utils::FIRST_TIME;
			$to = $year_month . '-' . $last_day . ' ' . Utils::LAST_TIME;
			list($totalBet, $totalWin, $totalLoss) = $this->total_player_game_month->sumOperatorBetsWinsLossByDatetime($from, $to, null, null, $readOnlyDB, $selected_tag);
            $this->utils->debug_log('2515.last_query ----->', $readOnlyDB->last_query());
		} else {
			//day
			$from = $date . ' ' . Utils::FIRST_TIME;
			$to = $date . ' ' . Utils::LAST_TIME;
			list($totalBet, $totalWin, $totalLoss) = $this->total_player_game_day->sumOperatorBetsWinsLossByDatetime($from, $to, null, null, $readOnlyDB, $selected_tag);
		}

		$payout = $totalLoss - $totalWin;
		return array(
            'total_bet'     => $this->utils->roundCurrencyForShow($totalBet),
			'total_win'     => $this->utils->roundCurrencyForShow($totalWin),
			'total_loss'    => $this->utils->roundCurrencyForShow($totalLoss),
			'payout'        => $this->utils->roundCurrencyForShow($payout)
        );
	}

	/**
	 * Handle ajax from view, Report_management::conversion_rate_report().
	 *
	 * @param string $summaryBy ex, all, directplayer, affiliate, agency and referrer. default is "all".
	 * @param boolean|array $full_params queue_results.full_params from export_data only.
	 * @return json $return The json string, return from conversion_rate_report_summaryByXXX().
	 */
	public function conversion_rate_report($summaryBy = 'all', $full_params = false) {

		$extra_search = false;
		if( !empty($full_params) ){ // queue_results.full_params
			foreach($full_params['extra_search'] as $key => $value){
				if( ! empty($value['name']) ){
					$extra_search[$value['name']] = $value['value'];
				}
			}
			// $this->utils->debug_log('1440.extra_search:', $extra_search );
		}

		$return = array();
		switch( strtolower($summaryBy) ){
			default:
			case 'all':{
				$return = $this->conversion_rate_report_summaryByAll($extra_search);
				break;
			}
			case 'directplayer':{
				$return = $this->conversion_rate_report_summaryByDirectPlayer($extra_search);
				break;
			}
			case 'affiliate':{
				$return = $this->conversion_rate_report_summaryByAffiliate($extra_search);
				break;
			}
			case 'agency':{
				$return = $this->conversion_rate_report_summaryByAgency($extra_search);
				break;
			}
			case 'referrer':{
				$return = $this->conversion_rate_report_summaryByReferrer($extra_search);
				break;
			}
			case 'referredaffiliate':{
				$return = $this->conversion_rate_report_summaryByReferredAffiliate($extra_search);
				break;
			}
			case 'referredagent':{
				$return = $this->conversion_rate_report_summaryByReferredAgent($extra_search);
				break;
			}
		}

		return $return;
	} // EOF conversion_rate_report

	/**
	 * getWhere4first_deposit_datetime
	 *
	 * @param array $input Recommand  return from data_tables->extra_search().
	 * @example <code>
	 * $request = $this->input->post();
	 * $input = $this->data_tables->extra_search($request);
	 * </code>
	 * @param string $theField for first_deposit_datetime field alias.
	 * @return string $subSql4first_deposit_date
	 */
	public function getWhere4first_deposit_datetime($input, $theField='player_report_hourly.first_deposit_datetime'){
		$subSql4first_deposit_date = '';
		if( ! empty($input['first_deposit_date_from'])
			&& ! empty($input['first_deposit_date_to'])
		){
			$first_deposit_date_from = $input['first_deposit_date_from'];
			$first_deposit_date_to = $input['first_deposit_date_to'];
			$first_deposit_date_from .= ' 00:00:00';
			$first_deposit_date_to .= ' 23:59:59';
			// for player_report_hourly table.
			/* for search form field, first_deposit_date. */
			$subSql4first_deposit_date =<<<EOF
			$theField BETWEEN STR_TO_DATE('$first_deposit_date_from', '%Y-%m-%d %H:%i:%s') AND STR_TO_DATE('$first_deposit_date_to', '%Y-%m-%d %H:%i:%s')
EOF;
			$subSql4first_deposit_date = $this->data_tables->clearBlankSpaceAfterHeredoc($subSql4first_deposit_date);
		}
		return $subSql4first_deposit_date;
	}// EOF getWhere4first_deposit_datetime

	/**
	 * Assemble To Where Codition Sentence
	 *
	 * The sentence will  like,
	 * player.playerId = "123" OR player.playerId = "123" OR ...
	 *
	 *
	 * @param [type] $playerIdRowList
	 * @param string $aliasFieldname
	 * @return void
	 */
	public function getPlayerIdCoditionStr($playerIdRowList, $aliasFieldname=''){
		$playerIdCoditionStr = '';
		$playerIdCoditionList = [];

		$formatStr =<<<EOF
		%s = "%s"
EOF;
		$formatStr = $this->data_tables->clearBlankSpaceAfterHeredoc($formatStr);

		if( empty($playerIdRowList ) ){
			$playerIdRowList = array();
		}

		foreach($playerIdRowList as $keyInteger => $currRow){
			if( empty($aliasFieldname) ){
				$playerIdFieldName = key($currRow);
			}else{
				$playerIdFieldName = $aliasFieldname;
			}

			$playerIdFieldValue = $currRow[key($currRow)];
			$playerIdCoditionList[] =sprintf($formatStr, $playerIdFieldName, $playerIdFieldValue);
		}
		$playerIdCoditionStr = implode(' OR ', $playerIdCoditionList);
		return $playerIdCoditionStr;
	}// EOF getPlayerIdCoditionStr

	/**
	 * Get player.playerId With Where Condition
	 *
	 * The player.playerId is P.K is faster than other fields that Not set index.
	 *
	 * @param string $date_from The start datetime of range.
	 * @param string $date_to The end datetime of range.
	 * @return array $playerIdList The result of getMultipleRowArray().
	 */
	public function getPlayerIdWhereCreatedOnRange($date_from, $date_to){

		$readOnlyDB = $this->getReadOnlyDB();
		$sql = <<<EOF
		SELECT playerId
		FROM player
		WHERE createdOn BETWEEN STR_TO_DATE('$date_from', '%Y-%m-%d %H:%i:%s') AND STR_TO_DATE('$date_to', '%Y-%m-%d %H:%i:%s')
EOF;
		$sql = $this->data_tables->clearBlankSpaceAfterHeredoc($sql);

		$result4PlayerIdList = $readOnlyDB->query( $sql );
		$playerIdList = $this->getMultipleRowArray($result4PlayerIdList);
		return $playerIdList;
	} // EOF getPlayerIdWhereCreatedOnRange

	/**
	 * getWhere4registration_datetime
	 * @param array $input Recommand  return from data_tables->extra_search().
	 * @example <code>
	 * $request = $this->input->post();
	 * $input = $this->data_tables->extra_search($request);
	 * </code>
	 * @param string $theField for createdOn field alias.
	 * @return string $subSql4registration_date
	 */
	public function getWhere4registration_datetime($input, $theField='player.createdOn'){
		$subSql4registration_date = '';

		$date_from_to = $this->convertInput2Date_from_to($input);
		if( ! empty($date_from_to) ){
			$registration_date_from = $date_from_to['date_from'];
			$registration_date_to = $date_from_to['date_to'];
			// for player table.
			/* for search form field, registration_date. */
			$subSql4registration_date =<<<EOF
			$theField BETWEEN STR_TO_DATE('$registration_date_from', '%Y-%m-%d %H:%i:%s') AND STR_TO_DATE('$registration_date_to', '%Y-%m-%d %H:%i:%s')
EOF;
			$subSql4registration_date = $this->data_tables->clearBlankSpaceAfterHeredoc($subSql4registration_date);
		}
		return $subSql4registration_date;
	} // EOF getWhere4registration_datetime

	/**
	 * Get date_from and date_to from $input then append to time string
	 *
	 * @param array $input
	 * @return array $return The fromat,
	 * - $return[date_from] = $input['registration_date_from']. ' 00:00:00'
	 * - $return[date_to] = $input['registration_date_from']. ' 23:59:59'
	 *
	 */
	function convertInput2Date_from_to($input){
		$return = [];
		if( ! empty($input['registration_date_from'])
			&& ! empty($input['registration_date_to'])
		){
			$date_from = $input['registration_date_from'];
			$date_to = $input['registration_date_to'];
			$date_from .= ' 00:00:00';
			$date_to .= ' 23:59:59';

			$return['date_from'] = $date_from;
			$return['date_to'] = $date_to;
		}
		return $return;
	} // EOF convertInput2Date_from_to

	/**
	 * initial the attr. CRR_TIME_LIMIT
	 * Default the attr. is max_execution_time of ini.
	 *
	 * @return void
	 */
	function initialCrrExecutionTime(){
		$max_execution_time = ini_get('max_execution_time');
		if( empty($this->CRR_TIME_LIMIT) ){
			$this->CRR_TIME_LIMIT = $max_execution_time;
		}
	}

	/**
	 * Extend execution time for Conversion Rate Report.
	 *
	 * @return boolean|NULL The max_execution_time changed  success is true and no change is NULL else false.
	 */
	function setCrrExecutionTime(){
		$max_execution_time = ini_get('max_execution_time');
		if($max_execution_time != $this->CRR_TIME_LIMIT){
			return set_time_limit($this->CRR_TIME_LIMIT);
		}
		return NULL;
	}



	/**
	 * conversion_rate_report Summary By All
	 */
	public function conversion_rate_report_summaryByAll($is_export = false){
		$this->setCrrExecutionTime();

		$readOnlyDB = $this->getReadOnlyDB();
		$this->load->library('data_tables', array("DB" => $readOnlyDB));

		$request = $this->input->post();
		$input = $this->data_tables->extra_search($request);

		// enable / disable first_deposit_date_from[to]
		$search_first_deposit_date_switch = 'off'; // default
		if( ! empty($input['search_first_deposit_date_switch']) ){
			$search_first_deposit_date_switch = $input['search_first_deposit_date_switch'];
		}
		if($search_first_deposit_date_switch == 'off'){ // ignore first_deposit_date_from and first_deposit_date_to
			unset($input['first_deposit_date_from']);
			unset($input['first_deposit_date_to']);
		}

		$this->data_tables->options['is_export'] = $is_export;


		/// The cols START
		$colIndex = 0;
		$columns = array();
		$columns[$colIndex]['dt'] = $colIndex;
		$columns[$colIndex]['alias'] = 'Category';
		$columns[$colIndex]['select'] = 'Category';
		$columns[$colIndex]['name'] = lang('Category');
		$columns[$colIndex]['formatter'] = function ($d, $row) {
			return $d;
		};
		$colIndex++;
		$columns[$colIndex]['dt'] = $colIndex;
		$columns[$colIndex]['alias'] = 'counterOfTotalRegistered';
		$columns[$colIndex]['select'] = 'counterOfTotalRegistered';
		$columns[$colIndex]['name'] = lang('Total Registered Players');
		$columns[$colIndex]['formatter'] = function ($d, $row) {
			return $d;
		};
		$colIndex++;
		$columns[$colIndex]['dt'] = $colIndex;
		$columns[$colIndex]['alias'] = 'counterOfFirstDepositedOnRegistered';
		$columns[$colIndex]['select'] = 'counterOfFirstDepositedOnRegistered';
		$columns[$colIndex]['name'] = lang('Total First Deposited Player');
		$columns[$colIndex]['formatter'] = function ($d, $row) {
			return $d;
		};
		$colIndex++;
		$columns[$colIndex]['dt'] = $colIndex;
		$columns[$colIndex]['alias'] = 'percentageOfConversionRate';
		$columns[$colIndex]['select'] = 'percentageOfConversionRate';
		$columns[$colIndex]['name'] = lang('Conversion Rate');
		$columns[$colIndex]['formatter'] = function ($d, $row) {
			$return_d = $d;
			if( is_numeric($d) ){
				// To display to second decimal place.
				$return_d = $this->data_tables->percentageFormatter($d/100);
			}
			return $return_d;

		};
		$colIndex++;
		$columns[$colIndex]['dt'] = $colIndex;
		$columns[$colIndex]['alias'] = 'amountOfFirstDepositedOnRegistered';
		$columns[$colIndex]['select'] = 'amountOfFirstDepositedOnRegistered';
		$columns[$colIndex]['name'] = lang('Total First Deposit Amount');
		$columns[$colIndex]['formatter'] = function ($d, $row) {
			return $d;
		};
		/// EOF cols

		$sqls = array();
		$benchmarks = array();
		$totalOnly = true;

		// store amounts of report_summaryByXXX.
		// $results['directPlayer'] = $this->conversion_rate_report_summaryByAllWithSubSql('directPlayer', $input, $sqls['directPlayer']);
		$json = $this->conversion_rate_report_summaryByDirectPlayer($is_export, $totalOnly);

		$Catrgory = 'directPlayer';
		$results[$Catrgory][0]['Category'] = $Catrgory;
		$results[$Catrgory][0]['counterOfTotalRegistered'] = $json['summaryByAll']['totalRegisteredPlayers'];
		$results[$Catrgory][0]['counterOfFirstDepositedOnRegistered'] = $json['summaryByAll']['totalFirstDepositPlayers'];
		$results[$Catrgory][0]['percentageOfConversionRate'] = $this->data_tables->percentageFormatter($json['summaryByAll']['conversionRate']);
		$results[$Catrgory][0]['amountOfFirstDepositedOnRegistered'] = $json['summaryByAll']['totalFirstDepositedAmount'];
		$sqls[$Catrgory] = array_filter($json['sqls']);
		$benchmarks[$Catrgory] = $json['benchmarks'];

		$this->data_tables->last_query = NULL; // clear for next conversion_rate_report_summaryByXXX().

		$json = $this->conversion_rate_report_summaryByAffiliate($is_export, $totalOnly);

		$Catrgory = 'affiliatePlayer';
		$results[$Catrgory][0]['Category'] = $Catrgory;
		$results[$Catrgory][0]['counterOfTotalRegistered'] = $json['summaryByAll']['totalRegisteredPlayers'];
		$results[$Catrgory][0]['counterOfFirstDepositedOnRegistered'] = $json['summaryByAll']['totalFirstDepositPlayers'];
		$results[$Catrgory][0]['percentageOfConversionRate'] = $this->data_tables->percentageFormatter($json['summaryByAll']['conversionRate']);
		$results[$Catrgory][0]['amountOfFirstDepositedOnRegistered'] = $json['summaryByAll']['totalFirstDepositedAmount'];
		$sqls[$Catrgory] = array_filter($json['sqls']);
		$benchmarks[$Catrgory] = $json['benchmarks'];
		// $results['affiliatePlayer'] = $this->conversion_rate_report_summaryByAllWithSubSql('affiliatePlayer', $input, $sqls['affiliatePlayer']);

		$this->data_tables->last_query = NULL; // clear for next conversion_rate_report_summaryByXXX().

		$json = $this->conversion_rate_report_summaryByAgency($is_export, $totalOnly);
		$Catrgory = 'agencyPlayer';
		$results[$Catrgory][0]['Category'] = $Catrgory;
		$results[$Catrgory][0]['counterOfTotalRegistered'] = $json['summaryByAll']['totalRegisteredPlayers'];
		$results[$Catrgory][0]['counterOfFirstDepositedOnRegistered'] = $json['summaryByAll']['totalFirstDepositPlayers'];
		$results[$Catrgory][0]['percentageOfConversionRate'] = $this->data_tables->percentageFormatter($json['summaryByAll']['conversionRate']);
		$results[$Catrgory][0]['amountOfFirstDepositedOnRegistered'] = $json['summaryByAll']['totalFirstDepositedAmount'];
		$sqls[$Catrgory] = array_filter($json['sqls']);
		$benchmarks[$Catrgory] = $json['benchmarks'];
		// $results['agencyPlayer'] = $this->conversion_rate_report_summaryByAllWithSubSql('agencyPlayer', $input, $sqls['agencyPlayer']);

		$this->data_tables->last_query = NULL; // clear for next conversion_rate_report_summaryByXXX().

		$json = $this->conversion_rate_report_summaryByReferrer($is_export, $totalOnly);
		$Catrgory = 'refererPlayer';
		$results[$Catrgory][0]['Category'] = $Catrgory;
		$results[$Catrgory][0]['counterOfTotalRegistered'] = $json['summaryByAll']['totalRegisteredPlayers'];
		$results[$Catrgory][0]['counterOfFirstDepositedOnRegistered'] = $json['summaryByAll']['totalFirstDepositPlayers'];
		$results[$Catrgory][0]['percentageOfConversionRate'] = $this->data_tables->percentageFormatter($json['summaryByAll']['conversionRate']);
		$results[$Catrgory][0]['amountOfFirstDepositedOnRegistered'] = $json['summaryByAll']['totalFirstDepositedAmount'];
		$sqls[$Catrgory] = array_filter($json['sqls']);
		$benchmarks[$Catrgory] = $json['benchmarks'];
		// $results['refererPlayer'] = $this->conversion_rate_report_summaryByAllWithSubSql('refererPlayer', $input, $sqls['refererPlayer']);

		$this->data_tables->last_query = NULL; // clear for next conversion_rate_report_summaryByXXX().

		$json = $this->conversion_rate_report_summaryByReferredAffiliate($is_export, $totalOnly);
		$Catrgory = 'referredAffiliate';
		$results[$Catrgory][0]['Category'] = $Catrgory;
		$results[$Catrgory][0]['counterOfTotalRegistered'] = $json['summaryByAll']['totalRegisteredPlayers'];
		$results[$Catrgory][0]['counterOfFirstDepositedOnRegistered'] = $json['summaryByAll']['totalFirstDepositPlayers'];
		$results[$Catrgory][0]['percentageOfConversionRate'] = $this->data_tables->percentageFormatter($json['summaryByAll']['conversionRate']);
		$results[$Catrgory][0]['amountOfFirstDepositedOnRegistered'] = $json['summaryByAll']['totalFirstDepositedAmount'];
		$sqls[$Catrgory] = array_filter($json['sqls']);
		$benchmarks[$Catrgory] = $json['benchmarks'];

		$this->data_tables->last_query = NULL; // clear for next conversion_rate_report_summaryByXXX().

		$json = $this->conversion_rate_report_summaryByReferredAgent($is_export, $totalOnly);
		$Catrgory = 'referredAgent';
		$results[$Catrgory][0]['Category'] = $Catrgory;
		$results[$Catrgory][0]['counterOfTotalRegistered'] = $json['summaryByAll']['totalRegisteredPlayers'];
		$results[$Catrgory][0]['counterOfFirstDepositedOnRegistered'] = $json['summaryByAll']['totalFirstDepositPlayers'];
		$results[$Catrgory][0]['percentageOfConversionRate'] = $this->data_tables->percentageFormatter($json['summaryByAll']['conversionRate']);
		$results[$Catrgory][0]['amountOfFirstDepositedOnRegistered'] = $json['summaryByAll']['totalFirstDepositedAmount'];
		$sqls[$Catrgory] = array_filter($json['sqls']);
		$benchmarks[$Catrgory] = $json['benchmarks'];


		$rows = array();
		/// the row of Category ALL.
		$rowIndex = 0;
		$rows[$rowIndex]['Category'] = lang('All');
		$field = 'amountOfFirstDepositedOnRegistered';
		$rows[$rowIndex]['amountOfFirstDepositedOnRegistered'] = $this->helperSumFirstRowByField($results, $field);
		// $rows[$rowIndex]['amountOfFirstDepositedOnRegistered'] = array_sum( array_column($results, 'amountOfFirstDepositedOnRegistered') ) ;
		$field = 'counterOfFirstDepositedOnRegistered';
		$rows[$rowIndex]['counterOfFirstDepositedOnRegistered'] = $this->helperSumFirstRowByField($results, $field);
		// $rows[$rowIndex]['counterOfFirstDepositedOnRegistered'] = array_sum( array_column($results, 'counterOfFirstDepositedOnRegistered') ) ;
		$field = 'counterOfTotalRegistered';
		$rows[$rowIndex]['counterOfTotalRegistered'] = $this->helperSumFirstRowByField($results, $field);
		// $rows[$rowIndex]['counterOfTotalRegistered'] = array_sum( array_column($results, 'counterOfTotalRegistered') ) ;
		$percentageOfConversionRate = 0;
		if( ! empty($rows[$rowIndex]['counterOfTotalRegistered']) ){
			$percentageOfConversionRate = $rows[$rowIndex]['counterOfFirstDepositedOnRegistered'] / $rows[$rowIndex]['counterOfTotalRegistered'];
		}
		$percentageOfConversionRate = $this->data_tables->percentageFormatter($percentageOfConversionRate);
		$rows[$rowIndex]['percentageOfConversionRate'] = $percentageOfConversionRate;
		// $rows[$rowIndex]['percentageOfConversionRate'] = array_sum( array_column($results, 'percentageOfConversionRate') ) ;
		$rowIndex++; /// the row of Category directPlayer.
		$results['directPlayer'][0]['Category'] = lang('Direct Player'); // replace title of row.
		$rows[$rowIndex] = $results['directPlayer'][0];
		$rowIndex++; /// the row of Category affiliatePlayer.
		$results['affiliatePlayer'][0]['Category'] = lang('Affiliate'); // replace title of row.
		$rows[$rowIndex] = $results['affiliatePlayer'][0];
		$rowIndex++; /// the row of Category agencyPlayer.
		$results['agencyPlayer'][0]['Category'] = lang('Agency'); // replace title of row.
		$rows[$rowIndex] = $results['agencyPlayer'][0];
		$rowIndex++; /// the row of Category refererPlayer.
		$results['refererPlayer'][0]['Category'] = lang('Referrer'); // replace title of row.
		$rows[$rowIndex] = $results['refererPlayer'][0];
		$rowIndex++; /// the row of Category referredAffiliate.
		$results['referredAffiliate'][0]['Category'] = lang('Referred + Affiliate'); // replace title of row.
		$rows[$rowIndex] = $results['referredAffiliate'][0];
		$rowIndex++; /// the row of Category referredAgent.
		$results['referredAgent'][0]['Category'] = lang('Referred + Agent'); // replace title of row.
		$rows[$rowIndex] = $results['referredAgent'][0];

		$result = $this->data_tables->_prepareDataForLists($columns, $rows);
		$result['header_data'] = $this->data_tables->get_columns($columns);
		$result['providerMethod'] = __FUNCTION__;
		$result['input'] = $input;
		$result['request'] = $request;
		$result['sqls'] = $sqls;
		$result['benchmarks'] = $benchmarks;

		return $result;
		// $sqls for trace,
	} // EOF conversion_rate_report_summaryByAll

	/**
	 * SUM the $results[xxx][0][$field] for count.
	 *
	 * @param array $results The array for sum, format: $results[xxx][0][$field]
	 * @param string $field The field name.
	 * @return void integer $sum
	 */
	public function helperSumFirstRowByField($results, $field){
		// $field = 'amountOfFirstDepositedOnRegistered';
		$sum = 0;
		foreach($results as $key => $caseResult){
			$sum += $caseResult[0][$field];
		}
		return $sum;
	}// EOF helperSumFirstRowByField

	/**
	 * Get conversion_rate_report of summary By DirectPlayer
	 *
	 *
	 * Check SQL,
	 * - conversionRate.firstDepositCounter: 2301.conversion_rate_report_summaryByDirectPlayer.sql:
	 *
	 * @link datatables, https://datatables.net/reference/option/
	 *
	 * @param boolean|array $extra_search Check called from export. ( entry: Export_data::conversion_rate_report() )
	 *
	 * @param boolean $totalOnly Called from conversion_rate_report_summaryByAll(). For Summary By ALL of reporting.
	 *
	 * @return array $result Base on datatables, and extra elements,
	 * - $result['providerMethod'] This function name for upgrade or debug.
	 * - $result['conversionRate'] The percentage at bottom of list.
	 * - $result['request'] Base on ci::input->post()
	 * - $result['input'] Parsing $request['extra_search'].
	 * - $result['sqls'] The API query SQL(s).
	 * - $result['benchmarks'] The spend time pre query.
	 * - $result['summaryByAll'] for conversion_rate_report_summaryByAll().
	 *
	 */
	public function conversion_rate_report_summaryByDirectPlayer($extra_search = false, $totalOnly = false) {
		$this->setCrrExecutionTime();

		$readOnlyDB = $this->getReadOnlyDB();
		$this->load->library('data_tables', array("DB" => $readOnlyDB));

		$request = $this->input->post();
		$input = $this->data_tables->extra_search($request);
		if( ! empty($extra_search) ){
			$input = $extra_search;
		}
		$is_export = ! empty($extra_search);

		// enable / disable first_deposit_date_from[to]
		$search_first_deposit_date_switch = 'off'; // default
		if( ! empty($input['search_first_deposit_date_switch']) ){
			$search_first_deposit_date_switch = $input['search_first_deposit_date_switch'];
		}
		if($search_first_deposit_date_switch == 'off'){ // ignore first_deposit_date_from and first_deposit_date_to
			unset($input['first_deposit_date_from']);
			unset($input['first_deposit_date_to']);
		}

		$this->data_tables->options['is_export'] = $is_export;
		// Default clause

		$sqls = []; // for trace SQL.

		$subtotals = array();
		$subtotals['firstDepositAmount'] = 0;

		/// The cols START
		$colIndex = 0;
		$columns = array();
		$columns[$colIndex]['dt'] = $colIndex;
		$columns[$colIndex]['alias'] = 'username';
		$columns[$colIndex]['select'] = 'username';
		$columns[$colIndex]['name'] = lang('Direct Player Username');
		$columns[$colIndex]['formatter'] = function ($d, $row) {
			return $d;
		}; // EOF $columns[$colIndex]['formatter']
		$colIndex++;
		$columns[$colIndex]['dt'] = $colIndex;
		$columns[$colIndex]['alias'] = 'createdOn';
		$columns[$colIndex]['select'] = 'createdOn';
		$columns[$colIndex]['name'] = lang('Registration Time');
		// Format: yyyy-mm-dd HH:MM:SS
		$columns[$colIndex]['formatter'] = 'dateTimeFormatter';
		$colIndex++;
		$columns[$colIndex]['dt'] = $colIndex;
		$columns[$colIndex]['alias'] = 'firstDepositTime';
		// $columns[$colIndex]['select'] = '`transactions`.`created_at`'; // ver. transactions
		$columns[$colIndex]['select'] = 'first_deposit_datetime'; // ver. player_report_hourly
		$columns[$colIndex]['name'] = lang('First Deposit Time');
		// Format: yyyy-mm-dd HH:MM:SS
		$columns[$colIndex]['formatter'] = 'dateTimeFormatter'; // directly assign to function.
		$colIndex++;
		$columns[$colIndex]['dt'] = $colIndex;
		$columns[$colIndex]['alias'] = 'firstDepositAmount';
		// $columns[$colIndex]['select'] = '`transactions`.`amount`'; // ver. transactions
		$columns[$colIndex]['select'] = 'first_deposit_amount'; // ver. player_report_hourly
		$columns[$colIndex]['name'] = lang('First Deposit Amount');
		$columns[$colIndex]['formatter'] = function ($d, $row) use ( &$subtotals ){ // 1,234,567.00
			$subtotals['firstDepositAmount'] += $d; // for sub total.
			return $this->data_tables->currencyFormatter($d);
		}; // EOF $columns[$colIndex]['formatter']

		$colIndex++; // just for count player.

		/// disable for OGP-13537 conversion rate report
		// 2. Summary by Direct player on 2019/07/16 -> Fail, the exported report player numbers are wrong, no matter the registered time range setting, the csv always showed all players, and there a column that is not necessary
		// $columns[$colIndex]['dt'] = $colIndex;

		$columns[$colIndex]['alias'] = 'playerId';
		// $columns[$colIndex]['select'] = '`transactions`.`amount`'; // ver. transactions
		$columns[$colIndex]['select'] = 'playerId'; // ver. player_report_hourly
		// $columns[$colIndex]['name'] = lang('First Deposit Amount');
		// $columns[$colIndex]['formatter'] = function ($d, $row) use ( &$subtotals ){ // 1,234,567.00
		// 	$subtotals['firstDepositAmount'] += $d; // for sub total.
		// 	return $this->data_tables->currencyFormatter($d);
		// }; // EOF $columns[$colIndex]['formatter']
		/// EOF cols

		/// disable for OGP-14393 Enhancement of Conversion Rate Report performance
		// $theField4registration_datetime ='createdOn';
		// $where4registration_datetime = $this->getWhere4registration_datetime($input, $theField4registration_datetime);
		// if( ! empty($where4registration_datetime) ){
		// 	$where4registration_datetime = ' AND '. $where4registration_datetime;
		// }

		// player_report_hourly.registered_date range limited for OGP-14393

		// PRH = player_report_hourly
		// RDT = registration_datetime
		$theFileName='player_report_hourly.registered_date';
		$where4PrhRd = $this->getWhere4registration_datetime($input, $theFileName);

		/// Patch for OGP-14393 Enhancement of Conversion Rate Report performance
		// Get the player who created on date range.
		$date_from_to = $this->convertInput2Date_from_to($input);
		$playerIdList = $this->getPlayerIdWhereCreatedOnRange($date_from_to['date_from'], $date_from_to['date_to']);
		// apply to player_report_hourly WHERE condition
		if( ! empty($playerIdList) ){
			// PRH = player_report_hourly
			$PRHplayerIdsCoditionStr = $this->getPlayerIdCoditionStr($playerIdList, 'player_report_hourly.player_id');
		}else{
			$PRHplayerIdsCoditionStr = 0;
		}

		$theField4first_deposit_datetime ='first_deposit_datetime';
		$where4first_deposit_datetime = $this->getWhere4first_deposit_datetime($input, $theField4first_deposit_datetime);
		if( ! empty($where4first_deposit_datetime) ){
			$where4first_deposit_datetime = ' AND '. $where4first_deposit_datetime;
		}

		/// OGP-14393 Enhancement of Conversion Rate Report performance
		$theField4registration_datetime ='created_on';
		$where4registration_datetime = $this->getWhere4registration_datetime($input, $theField4registration_datetime);
		if( ! empty($where4registration_datetime) ){
			$where4registration_datetime = ' AND '. $where4registration_datetime;
		}
		$table = <<<EOF
		( /* as reporting */
			SELECT player_id as playerId
			, username
			, created_on as createdOn
			, ( /* as first_deposit_amount */
				SELECT first_deposit_amount
				FROM player_relay
				WHERE deleted_at IS NULL
					AND agent_id=0 /* filter multi-registered */
					AND affiliate_id=0 /* filter multi-registered */
					AND referee_player_id=0 /* filter multi-registered */
					AND player_relay_list.player_id = player_relay.player_id
					/* $where4registration_datetime */ /* disable for player_relay_list.player_id = player_relay.player_id */
					$where4first_deposit_datetime
			) as first_deposit_amount
			,( /* as first_deposit_datetime */
				SELECT first_deposit_datetime
				FROM player_relay
				WHERE deleted_at IS NULL
					AND agent_id=0 /* filter multi-registered */
					AND affiliate_id=0 /* filter multi-registered */
					AND referee_player_id=0 /* filter multi-registered */
					AND player_relay_list.player_id = player_relay.player_id
					/* $where4registration_datetime */ /* disable for player_relay_list.player_id = player_relay.player_id */
					$where4first_deposit_datetime
			) as first_deposit_datetime
			FROM player_relay as player_relay_list
			WHERE deleted_at IS NULL
				AND agent_id=0 /* filter multi-registered */
				AND affiliate_id=0 /* filter multi-registered */
				AND referee_player_id=0 /* filter multi-registered */
				$where4registration_datetime
				/* $where4first_deposit_datetime */
		) as reporting
EOF;

/// disable for OGP-14393 Enhancement of Conversion Rate Report performance
// 		$table = <<<EOF
// 		( /* as reporting */
// 			SELECT playerId
// 			, username
// 			, agent_id
// 			, affiliateId
// 			, refereePlayerId
// 			, createdOn
// 			, ( /* as first_deposit_amount */
// 				SELECT first_deposit_amount
// 				FROM ( /* as relay_player_report_hourly4cols */
// 					SELECT player.playerId
// 					, relay_player_report_hourly.first_deposit_amount
// 					, player.affiliateId affiliate_id
// 					, IF(player.affiliateId IS NULL or player.affiliateId = "", "0", player.affiliateId) as int_affiliateId
// 					, player.agent_id
// 					, IF(player.agent_id IS NULL or player.agent_id = "", "0", player.agent_id) as int_agent_id /* convert to int while NULL or Zero.*/
// 					, player.refereePlayerId
// 					, IF(player.refereePlayerId IS NULL or player.refereePlayerId = "", "0", player.refereePlayerId) as int_refereePlayerId
// 					FROM player
// 					LEFT OUTER JOIN ( /* as relay_player_report_hourly  */
// 						/* wrapper for last player_report_hourly pre player */
// 						/* START get the last record of the player */
// 						SELECT player_report_hourly.first_deposit_amount
// 						, player_report_hourly.first_deposit_datetime
// 						, player_report_hourly.player_id
// 						, IF(player_report_hourly.agent_id IS NULL or player_report_hourly.agent_id = "", "0", player_report_hourly.agent_id) as int_agent_id
// 						, IF(player_report_hourly.affiliate_id IS NULL or player_report_hourly.affiliate_id = "", "0", player_report_hourly.affiliate_id) as int_affiliate_id
// 						FROM player_report_hourly
// 						INNER JOIN ( /* as last_update_player_report_hourly */
// 							SELECT max(id) as max_id /* get the lastest reocrd for the player.*/
// 							, player_report_hourly.player_id
// 							FROM player_report_hourly
// 							WHERE $PRHplayerIdsCoditionStr  /* Patch for OGP-14393 */
// 								AND first_deposit_amount > 0 /* Patch for OGP-14393 */
// 								AND (agent_id IS NULL OR agent_id = "") /* filter multi-registered */
// 								AND (affiliate_id IS NULL OR affiliate_id = "") /* filter multi-registered */
// 								$where4first_deposit_datetime /* Patch for OGP-14393 */
// 							GROUP by player_id
// 						)as last_update_player_report_hourly
// 						ON last_update_player_report_hourly.player_id = player_report_hourly.player_id
// 						AND last_update_player_report_hourly.max_id = player_report_hourly.id
// 						WHERE first_deposit_amount > 0 /* Patch for OGP-14393 */
// 						$where4first_deposit_datetime /* Patch for OGP-14393 */
// 						/* EOF get the last record of the player */
// 					) as relay_player_report_hourly ON playerId = relay_player_report_hourly.player_id
// 															AND relay_player_report_hourly.first_deposit_amount > 0
// 					WHERE player.deleted_at IS NULL
// 					AND relay_player_report_hourly.first_deposit_amount > 0
// 					AND (agent_id IS NULL OR agent_id = "") /* filter multi-registered */
// 					AND (player.affiliateId IS NULL OR player.affiliateId = "") /* filter multi-registered */
// 					AND (refereePlayerId IS NULL OR refereePlayerId = "") /* filter multi-registered */
// 					$where4registration_datetime
// 					$where4first_deposit_datetime
// 				) as relay_player_report_hourly4cols
// 				WHERE 0 = relay_player_report_hourly4cols.int_affiliateId /* filter multi-registered */
// 				AND 0 = relay_player_report_hourly4cols.int_agent_id /* filter multi-registered */
// 				AND 0 = relay_player_report_hourly4cols.int_refereePlayerId /* filter multi-registered */
// 				AND player4reporting.playerId = relay_player_report_hourly4cols.playerId
// 			) as first_deposit_amount
// 			, ( /* as first_deposit_datetime */
// 				SELECT first_deposit_datetime
// 				FROM ( /* as relay_player_report_hourly4cols */
// 					SELECT player.playerId
// 					, relay_player_report_hourly.first_deposit_datetime
// 					, player.affiliateId affiliate_id
// 					, IF(player.affiliateId IS NULL or player.affiliateId = "", "0", player.affiliateId) as int_affiliateId
// 					, player.agent_id
// 					, IF(player.agent_id IS NULL or player.agent_id = "", "0", player.agent_id) as int_agent_id /* convert to int while NULL or Zero.*/
// 					, player.refereePlayerId
// 					, IF(player.refereePlayerId IS NULL or player.refereePlayerId = "", "0", player.refereePlayerId) as int_refereePlayerId
// 					FROM player
// 					LEFT OUTER JOIN ( /* as relay_player_report_hourly  */
// 						/* wrapper for last player_report_hourly pre player */
// 						/* START get the last record of the player */
// 						SELECT player_report_hourly.first_deposit_amount
// 						, player_report_hourly.first_deposit_datetime
// 						, player_report_hourly.player_id
// 						, IF(player_report_hourly.agent_id IS NULL or player_report_hourly.agent_id = "", 0, player_report_hourly.agent_id) as int_agent_id
// 						, IF(player_report_hourly.affiliate_id IS NULL or player_report_hourly.affiliate_id = "", 0, player_report_hourly.affiliate_id) as int_affiliate_id
// 						FROM player_report_hourly
// 						INNER JOIN ( /* as last_update_player_report_hourly */
// 							SELECT max(id) as max_id /* get the lastest reocrd for the player.*/
// 							, player_report_hourly.player_id
// 							FROM player_report_hourly
// 							WHERE $PRHplayerIdsCoditionStr  /* Patch for OGP-14393 */
// 								AND first_deposit_amount > 0 /* Patch for OGP-14393 */
// 								AND (agent_id IS NULL OR agent_id = "") /* filter multi-registered */
// 								AND (affiliate_id IS NULL OR affiliate_id = "") /* filter multi-registered */
// 								$where4first_deposit_datetime /* Patch for OGP-14393 */
// 							GROUP by player_id
// 						)as last_update_player_report_hourly
// 						ON last_update_player_report_hourly.player_id = player_report_hourly.player_id
// 							AND last_update_player_report_hourly.max_id = player_report_hourly.id
// 						WHERE first_deposit_amount > 0 /* Patch for OGP-14393 */
// 						$where4first_deposit_datetime /* Patch for OGP-14393 */
// 						/* EOF get the last record of the player */
// 					) as relay_player_report_hourly ON playerId = relay_player_report_hourly.player_id
// 															AND relay_player_report_hourly.first_deposit_amount > 0
// 					WHERE player.deleted_at IS NULL
// 					AND relay_player_report_hourly.first_deposit_amount > 0
// 					AND (agent_id IS NULL OR agent_id = "") /* filter multi-registered */
// 					AND (player.affiliateId IS NULL OR player.affiliateId = "") /* filter multi-registered */
// 					AND (refereePlayerId IS NULL OR refereePlayerId = "") /* filter multi-registered */
// 					$where4registration_datetime
// 					$where4first_deposit_datetime
// 				) as relay_player_report_hourly4cols
// 				WHERE 0 = relay_player_report_hourly4cols.int_affiliateId /* filter multi-registered */
// 				AND 0 = relay_player_report_hourly4cols.int_agent_id /* filter multi-registered */
// 				AND 0 = relay_player_report_hourly4cols.int_refereePlayerId /* filter multi-registered */
// 				AND player4reporting.playerId = relay_player_report_hourly4cols.playerId
// 			 )as first_deposit_datetime
// 			FROM player as player4reporting
// 		) as reporting
// EOF;
		$table = $this->data_tables->clearBlankSpaceAfterHeredoc($table);

		$where = array();

		$theField4registration_datetime ='createdOn';
		$where[] = $this->getWhere4registration_datetime($input, $theField4registration_datetime);

		$values = array();
		$joins = array();
		$group_by = array();
		$having = array();
		$distinct = false;
		$external_order=[];
		$notDatatable = '';
		$countOnlyField = 'playerId';
		$innerJoins=[];
		$useIndex=[];

		$markName = 'list';
		$this->utils->markProfilerStart($markName); // list mark.
		// for count records contain "group by" and "having".
		$this->config->set_item('debug_data_table_sql', true);
		if( $totalOnly ) {
			$result = $this->data_tables->empty_data($request);
		}else{
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
		}

		$sql = $this->data_tables->last_query; // for trace
		$this->utils->markProfilerEndAndPrint($markName, $benchmarks[$markName]); // total mark.
		$sqls[$markName] = $sql; // list mark.

		if( ! $is_export ){ // hidden the row in view, kw:"subTotalCol".
			/// subTotal append to $result['data'], rows.
			// MUST before getMultipleRowArray().Because getMultipleRowArray() will formatter data.
			$subTotalRowsIndex = count( $result['data'] );
			$result['data'][$subTotalRowsIndex][0] = '<span class="th subTotalCol">'.lang('Sub Total').'</span>';
			$result['data'][$subTotalRowsIndex][1] = '';
			$result['data'][$subTotalRowsIndex][2] = '';
			$result['data'][$subTotalRowsIndex][3] = '<span class="th subTotalCol">'.$this->data_tables->currencyFormatter($subtotals['firstDepositAmount']).'</span>';
		}

		// for Total, get orig SQL and sum(amount) from that.
		$this->data_tables->options['only_sql'] = true;
		// $request['start']
		$request['length'] = -1; // for ignore LIMIT.
		$result4sql = $this->data_tables->get_data($request // #1
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
// $this->utils->debug_log('1984.conversion_rate_report_summaryByDirectPlayer.result4sql:', $result4sql);
		$this->data_tables->options['only_sql'] = false;
		$tmpSQL = $result4sql['data'];

		$summary = [];
		$markName = 'total2calcRows'; // total mark.
		$this->utils->markProfilerStart($markName); // total mark.
		$result4Total2calcRows = $readOnlyDB->query( $tmpSQL );
		$this->utils->markProfilerEndAndPrint($markName, $benchmarks[$markName]); // total mark.

		$total2calcRows = $this->getMultipleRowArray($result4Total2calcRows);
		if( ! empty($total2calcRows) ){ // for NULL
			$totalFirstDepositedAmount = 0;
			$totalFirstDepositPlayers = 0;
			$totalRegisteredPlayers = 0;

			foreach($total2calcRows as $indexNumber => $result4Total2calcRow){
				$totalFirstDepositedAmount += $result4Total2calcRow['firstDepositAmount'];

				if($result4Total2calcRow['firstDepositAmount'] > 0){
					$totalFirstDepositPlayers++;
				}
				$totalRegisteredPlayers++;
			}
			$summary[0]['totalRegisteredPlayers'] = $totalRegisteredPlayers;
			$summary[0]['totalFirstDepositedAmount'] = $totalFirstDepositedAmount;
			$summary[0]['totalFirstDepositPlayers'] = $totalFirstDepositPlayers;
			$counterHasFirstDeposit[0]['totalFirstDepositPlayers']= $totalFirstDepositPlayers;
		}else{
			$summary[0]['totalRegisteredPlayers'] = 0;
			$summary[0]['totalFirstDepositedAmount'] = 0;
			$summary[0]['totalFirstDepositPlayers']=0;
		}

		$conversionRate = 0;
		$summary[0]['totalFirstDepositPlayers'] = 0;
		$summary[0]['conversionRate']= 0;
		if( ! empty($summary[0]['totalRegisteredPlayers']) ){
			$conversionRate = $counterHasFirstDeposit[0]['totalFirstDepositPlayers'] / $summary[0]['totalRegisteredPlayers'];
			// append totalFirstDepositPlayers,
			$summary[0]['totalFirstDepositPlayers'] = $counterHasFirstDeposit[0]['totalFirstDepositPlayers'];
			$summary[0]['conversionRate'] = $conversionRate;
		}
		$result['conversionRate'] = $this->data_tables->percentageFormatter($conversionRate);

		$result['providerMethod'] = __FUNCTION__;
		$result['input'] = $input;
		$result['request'] = $request;
		$result['sqls'] = $sqls;
		$result['benchmarks'] = $benchmarks;
		$result['summaryByAll'] = $summary[0]; // for conversion_rate_report_summaryByAll().
		return $result;
		// $sqls for trace,
	} // EOF conversion_rate_report_summaryByDirectPlayer
	/**
	 * Get conversion_rate_report of summary By Affiliate
	 *
	 *
	 * Check SQL,
	 * - list: The list of reporting.
	 * - total: The total row at bottom of reporting.
	 *
	 * @link datatables, https://datatables.net/reference/option/
	 *
	 * @param boolean|array $extra_search Check called from export. ( entry: Export_data::conversion_rate_report() )
	 *
	 * @param boolean $totalOnly Called from conversion_rate_report_summaryByAll(). For Summary By ALL of reporting.
	 *
	 * @return array $result Base on datatables, and extra elements,
	 * - $result['providerMethod'] This function name for upgrade or debug.
	 * - $result['conversionRate'] The percentage at bottom of list.
	 * - $result['request'] Base on ci::input->post()
	 * - $result['input'] Parsing $request['extra_search'].
	 * - $result['sqls'] The API query SQL(s).
	 * - $result['benchmarks'] The spend time pre query.
	 * - $result['summaryByAll'] for conversion_rate_report_summaryByAll().
	 *
	 */
	public function conversion_rate_report_summaryByAffiliate($extra_search = false, $totalOnly = false) {
		$this->setCrrExecutionTime();

		// $this->db->select('affiliateId')->from('affiliates')->where('username', $affiliate_username);
		$readOnlyDB = $this->getReadOnlyDB();
		$this->load->library('data_tables', array("DB" => $readOnlyDB));

		$request = $this->input->post();
		$input = $this->data_tables->extra_search($request);
		if( ! empty($extra_search) ){
			$input = $extra_search;
		}
		$is_export = ! empty($extra_search);

		$sqls = []; // for trace
		$benchmarks = [];

		// enable / disable first_deposit_date_from[to]
		$search_first_deposit_date_switch = 'off'; // default
		if( ! empty($input['search_first_deposit_date_switch']) ){
			$search_first_deposit_date_switch = $input['search_first_deposit_date_switch'];
		}
		if($search_first_deposit_date_switch == 'off'){ // ignore first_deposit_date_from and first_deposit_date_to
			unset($input['first_deposit_date_from']);
			unset($input['first_deposit_date_to']);
		}

		$this->data_tables->options['is_export'] = $is_export;

		$subtotals = array();
		$subtotals['conversionRate'] = 0;
		$subtotals['totalRegisteredPlayers'] = 0;
		$subtotals['totalFirstDepositPlayers'] = 0;
		$subtotals['totalFirstDepositedAmount'] = 0;

		/// The cols START
		$colIndex = 0;
		$columns = array();
		$columns[$colIndex]['dt'] = $colIndex;
		$columns[$colIndex]['alias'] = 'affiliateUsername';
		$columns[$colIndex]['select'] = 'username';
		$columns[$colIndex]['name'] = lang('Affiliate Username');
		$columns[$colIndex]['formatter'] = function ($d, $row) {
			return $d;
		}; // EOF $columns[$colIndex]['formatter']
		$colIndex++;
		$columns[$colIndex]['dt'] = $colIndex;
		$columns[$colIndex]['alias'] = 'totalRegisteredPlayers';
		$columns[$colIndex]['select'] = 'totalRegisteredPlayers';
		$columns[$colIndex]['name'] = lang('Total Registered Players');
		$columns[$colIndex]['formatter'] = function ($d, $row) use ( &$subtotals ) {
			$subtotals['totalRegisteredPlayers'] += $d; // for sub total.
			return $d;
		}; // EOF $columns[$colIndex]['formatter']
		$colIndex++;
		$columns[$colIndex]['dt'] = $colIndex;
		$columns[$colIndex]['alias'] = 'totalFirstDepositPlayers';
		// $columns[$colIndex]['select'] = '`transactions`.`created_at`'; // ver. transactions
		$columns[$colIndex]['select'] = 'totalFirstDepositPlayers'; // ver. player_report_hourly
		$columns[$colIndex]['name'] = lang('Total First Deposited Player');
		$columns[$colIndex]['formatter'] = function ($d, $row) use ( &$subtotals ) {
			$subtotals['totalFirstDepositPlayers'] += $d; // for sub total.
			return $d;
		}; // EOF $columns[$colIndex]['formatter']
		$colIndex++;
		$columns[$colIndex]['dt'] = $colIndex;
		$columns[$colIndex]['alias'] = 'conversionRate';
		// $columns[$colIndex]['select'] = '`transactions`.`amount`'; // ver. transactions
		$columns[$colIndex]['select'] = 'totalFirstDepositPlayers/totalRegisteredPlayers'; // ver. player_report_hourly
		$columns[$colIndex]['name'] = lang('Conversion Rate');
		$columns[$colIndex]['formatter'] = function ($d, $row) use ( &$subtotals ){ // 1,234,567.00
			$subtotals['conversionRate'] += $d; // for sub total.
			return $this->data_tables->percentageFormatter($d);
		}; // EOF $columns[$colIndex]['formatter']
		$colIndex++;
		$columns[$colIndex]['dt'] = $colIndex;
		$columns[$colIndex]['alias'] = 'totalFirstDepositedAmount';
		// $columns[$colIndex]['select'] = '`transactions`.`amount`'; // ver. transactions
		$columns[$colIndex]['select'] = 'totalFirstDepositedAmount'; // ver. player_report_hourly
		$columns[$colIndex]['name'] = lang('Total First Deposit Amount');
		$columns[$colIndex]['formatter'] = function ($d, $row) use ( &$subtotals ){ // 1,234,567.00
			$subtotals['totalFirstDepositedAmount'] += $d; // for sub total.
			return $this->data_tables->currencyFormatter($d);
		}; // EOF $columns[$colIndex]['formatter']
		/// EOF cols


		/// Patch for OGP-14393 Enhancement of Conversion Rate Report performance
		// Get the player who created on date range.
		$date_from_to = $this->convertInput2Date_from_to($input);
		$playerIdList = $this->getPlayerIdWhereCreatedOnRange($date_from_to['date_from'], $date_from_to['date_to']);
		// apply to player_report_hourly WHERE condition
		if( ! empty($playerIdList) ){
			// PRH = player_report_hourly
			$PRHplayerIdsCoditionStr = $this->getPlayerIdCoditionStr($playerIdList, 'player_report_hourly.player_id');
		}else{
			$PRHplayerIdsCoditionStr = 0;
		}

		$theField4registration_datetime ='createdOn';
		$where4registration_datetime = $this->getWhere4registration_datetime($input, $theField4registration_datetime);
		if( ! empty($where4registration_datetime) ){
			$where4registration_datetime = ' AND '. $where4registration_datetime;
		}

		$theField4first_deposit_datetime ='first_deposit_datetime';
		$where4first_deposit_datetime = $this->getWhere4first_deposit_datetime($input, $theField4first_deposit_datetime);
		if( ! empty($where4first_deposit_datetime) ){
			$where4first_deposit_datetime = ' AND '. $where4first_deposit_datetime;
		}

		/// disable for OGP-14393 Enhancement of Conversion Rate Report performance
		// $where4affiliate_username = 'totalRegisteredPlayers > 0'; // for ignore row while affiliate_username of search field is empty.
		// if( ! empty($input['affiliate_username']) ){
		// 	$where4affiliate_username = ' username = "'. $input['affiliate_username']. '" ';
		// }

		$this->load->model(array('affiliatemodel'));
		$PRHaffiliateIdCoditionStr = '';
		$affiliatesAffiliateIdCoditionStr = '';
		if( ! empty($input['affiliate_username']) ){
			$affiliateId = $this->affiliatemodel->getAffiliateIdByUsername( $input['affiliate_username'] );
			$PRHaffiliateIdCoditionStr = ' AND affiliate_id = "'. $affiliateId. '"';

			$affiliatesAffiliateIdCoditionStr = ' AND affiliateId = "'. $affiliateId. '"';
		}


		// totalRegisteredPlayers : 註冊人數
		// totalFirstDepositPlayers : 首存人數
		// totalFirstDepositedAmount : 首存金额

		//filter multi-registered: agent_id,affiliateId and refereePlayerId.

		/// disable for OGP-14393 Enhancement of Conversion Rate Report performance
		// $idCondStr = '1';// @rbo =remove before online

		/// OGP-14393 Enhancement of Conversion Rate Report performance
		// ----- ver. player_relay
		$where4affiliate_username = ''; // for ignore row while affiliate_username of search field is empty.
		if( ! empty($input['affiliate_username']) ){
			$where4affiliate_username = ' AND  username = "'. $input['affiliate_username'].'" ';
		}else{
			$where4affiliate_username = '';
		}
		$theField4registration_datetime ='created_on';
		$where4registration_datetime = $this->getWhere4registration_datetime($input, $theField4registration_datetime);
		if( ! empty($where4registration_datetime) ){
			$where4registration_datetime = ' AND '. $where4registration_datetime;
		}
		/// filter agency that without player
		$dofilter4totalRegisteredPlayers = false;
		if( ! empty($input['affiliate_username']) ){
		}else{
			$dofilter4totalRegisteredPlayers = true;
		}
		$idCondStr = '1';
		if($dofilter4totalRegisteredPlayers){ // get registration_datetime condition.
			$sql = <<<EOF
			SELECT affiliate_id
			FROM player_relay
			WHERE deleted_at IS NULL
				AND 0 = player_relay.agent_id /* filter multi-registered */
				AND 0 = player_relay.referee_player_id /* filter multi-registered */
				$where4registration_datetime
			GROUP BY affiliate_id
EOF;
			$result = $this->db->query($sql);
			$rows = $this->getMultipleRowArray($result);
			if( ! empty($rows) ){
				$idCond = [];
				foreach($rows as $keyNumber => $row){
					$tmp4id =$row['affiliate_id'];
					$idCond[] =<<<EOF
					affiliateId = "$tmp4id"
EOF;
 // ref. affiliateList.affiliateId
				}
				$idCondStr = ' ( '. implode(' OR ', $idCond). ' ) ';
			}
		}
		$table = <<<EOF
		( 	/* as reporting */
			SELECT affiliateList.affiliateId
				, affiliateList.username
				,( /* as totalRegisteredPlayers */
					SELECT COUNT( player_id) as counter
					FROM player_relay
					WHERE player_relay.deleted_at IS NULL
					AND affiliateList.affiliateId = player_relay.affiliate_id
					AND 0 = player_relay.agent_id /* filter multi-registered */
					AND 0 = player_relay.referee_player_id /* filter multi-registered */
					$where4registration_datetime
					/* disable for B3.1, where4first_deposit_datetime $where4first_deposit_datetime EOF where4first_deposit_datetime */
				) as totalRegisteredPlayers
				,( /* as totalFirstDepositPlayers */
					SELECT COUNT( player_id) as counter
					FROM player_relay
					WHERE player_relay.deleted_at IS NULL
					AND player_relay.first_deposit_amount > 0
					AND affiliateList.affiliateId = player_relay.affiliate_id
					AND 0 = player_relay.agent_id /* filter multi-registered */
					AND 0 = player_relay.referee_player_id /* filter multi-registered */
					$where4registration_datetime
					$where4first_deposit_datetime
				) as totalFirstDepositPlayers
				,( /* as totalFirstDepositedAmount */
					SELECT SUM( first_deposit_amount ) as amount
					FROM player_relay
					WHERE player_relay.deleted_at IS NULL
					AND player_relay.first_deposit_amount > 0
					AND affiliateList.affiliateId = player_relay.affiliate_id
					AND 0 = player_relay.agent_id /* filter multi-registered */
					AND 0 = player_relay.referee_player_id /* filter multi-registered */
					$where4registration_datetime
					$where4first_deposit_datetime
				) as totalFirstDepositedAmount
			FROM affiliates as affiliateList
			WHERE $idCondStr

			$where4affiliate_username /* Patch for OGP-14393 */
		) as reporting
EOF;
/// disable for OGP-14393 Enhancement of Conversion Rate Report performance
// 		// ----- ver. player_report_hourly
// 		$table = <<<EOF
// 		( 	/* as reporting */
// 			SELECT *
// 			FROM ( /* as relay_affiliates_reporting */
// 				SELECT affiliateList.affiliateId
// 				, affiliateList.username
// 				,( /* as totalRegisteredPlayers */
// 					SELECT COUNT( playerId) as counter
// 					FROM ( /* as relay_player_report_hourly4cols */
// 						SELECT player.playerId
// 						, IF(player.agent_id IS NULL or player.agent_id = "", 0, player.agent_id) as int_agent_id
// 						, IF(player.affiliateId IS NULL or player.affiliateId = "", 0, player.affiliateId) as int_affiliateId
// 						, IF(player.refereePlayerId IS NULL or player.refereePlayerId = "", 0, player.refereePlayerId) as int_refereePlayerId
// 						FROM player
// 						LEFT OUTER JOIN ( /* as relay_player_report_hourly  */
// 							/* wrapper for last player_report_hourly pre player */
// 							/* START get the last record of the player */
// 							SELECT player_report_hourly.player_id
// 							, player_report_hourly.first_deposit_amount
// 							, player_report_hourly.first_deposit_datetime
// 							, IF(player_report_hourly.agent_id IS NULL or player_report_hourly.agent_id = "", 0, player_report_hourly.agent_id) as int_agent_id
// 							, IF(player_report_hourly.affiliate_id IS NULL or player_report_hourly.affiliate_id = "", 0, player_report_hourly.affiliate_id) as int_affiliate_id
// 							FROM player_report_hourly
// 							INNER JOIN (
// 								SELECT max(id) as max_id /* get the lastest reocrd for the player.*/
// 								, player_report_hourly.player_id
// 								FROM player_report_hourly
// 								WHERE $PRHplayerIdsCoditionStr  /* Patch for OGP-14393 */
// 								AND (agent_id IS NULL OR agent_id = "") /* filter multi-registered */
// 								$PRHaffiliateIdCoditionStr /* Patch for OGP-14393 */
// 								GROUP by player_id
// 							)as last_update_player_report_hourly ON last_update_player_report_hourly.player_id = player_report_hourly.player_id
// 																AND last_update_player_report_hourly.max_id = player_report_hourly.id
// 							/* EOF get the last record of the player */
// 						) as relay_player_report_hourly ON playerId = relay_player_report_hourly.player_id
// 						WHERE player.deleted_at IS NULL
// 						AND (agent_id IS NULL OR agent_id = "") /* filter multi-registered */
// 						/* where4registration_datetime */ $where4registration_datetime /* EOF where4registration_datetime */
// 						/* disable for B3.1, where4first_deposit_datetime $where4first_deposit_datetime EOF where4first_deposit_datetime */
// 					) as relay_player_report_hourly4cols
// 					WHERE affiliateList.affiliateId = relay_player_report_hourly4cols.int_affiliateId
// 					AND 0 = relay_player_report_hourly4cols.int_agent_id /* filter multi-registered */
// 					AND 0 = relay_player_report_hourly4cols.int_refereePlayerId /* filter multi-registered */
//
// 				) as totalRegisteredPlayers
// 				,( /* as totalFirstDepositPlayers */
// 					SELECT COUNT( playerId ) as counter
// 					FROM ( /* relay_player_report_hourly4cols */
// 						SELECT player.playerId
// 						, IF(player.agent_id IS NULL or player.agent_id = "", 0, player.agent_id) as int_agent_id
// 						, IF(player.affiliateId IS NULL or player.affiliateId = "", 0, player.affiliateId) as int_affiliateId
// 						, IF(player.refereePlayerId IS NULL or player.refereePlayerId = "", 0, player.refereePlayerId) as int_refereePlayerId
// 						FROM player
// 						LEFT OUTER JOIN ( /* as relay_player_report_hourly  */
// 							/* wrapper for last player_report_hourly pre player */
// 							/* START get the last record of the player */
// 							SELECT player_report_hourly.player_id
// 							, player_report_hourly.first_deposit_amount
// 							, player_report_hourly.first_deposit_datetime
// 							, IF(player_report_hourly.agent_id IS NULL or player_report_hourly.agent_id = "", "0", player_report_hourly.agent_id) as int_agent_id
// 							, IF(player_report_hourly.affiliate_id IS NULL or player_report_hourly.affiliate_id = "", "0", player_report_hourly.affiliate_id) as int_affiliate_id
// 							FROM player_report_hourly
// 							INNER JOIN (
// 								SELECT max(id) as max_id /* get the lastest reocrd for the player.*/
// 								, player_report_hourly.player_id
// 								FROM player_report_hourly
// 								WHERE $PRHplayerIdsCoditionStr  /* Patch for OGP-14393 */
// 								AND first_deposit_amount > 0 /* Patch for OGP-14393 */
// 								AND (agent_id IS NULL OR agent_id = "") /* filter multi-registered */
// 								$where4first_deposit_datetime /* Patch for OGP-14393 */
// 								$PRHaffiliateIdCoditionStr /* Patch for OGP-14393 */
// 								GROUP by player_id
// 							)as last_update_player_report_hourly ON last_update_player_report_hourly.player_id = player_report_hourly.player_id
// 																AND last_update_player_report_hourly.max_id = player_report_hourly.id
// 							WHERE first_deposit_amount > 0
// 							$where4first_deposit_datetime /* Patch for OGP-14393 */
// 							/* EOF get the last record of the player */
// 						) as relay_player_report_hourly ON playerId = relay_player_report_hourly.player_id
// 														AND relay_player_report_hourly.first_deposit_amount > 0
// 														$where4first_deposit_datetime /* Patch for OGP-14393 */
// 						WHERE player.deleted_at IS NULL
// 						AND relay_player_report_hourly.first_deposit_amount > 0
// 						AND (agent_id IS NULL OR agent_id = "") /* filter multi-registered */
// 						$where4registration_datetime
// 						$where4first_deposit_datetime
// 					) as relay_player_report_hourly4cols
// 					WHERE affiliateList.affiliateId = int_affiliateId
// 					AND 0 = relay_player_report_hourly4cols.int_agent_id /* filter multi-registered */
// 					AND 0 = relay_player_report_hourly4cols.int_refereePlayerId /* filter multi-registered */
// 				) as totalFirstDepositPlayers
// 				,( /* as totalFirstDepositedAmount */
// 					SELECT SUM( first_deposit_amount ) as amount
// 					FROM ( /* as relay_player_report_hourly4cols */
// 						SELECT player.playerId
// 						, relay_player_report_hourly.first_deposit_amount
// 						, player.affiliateId affiliate_id
// 						, IF(player.affiliateId IS NULL or player.affiliateId = "", 0, player.affiliateId) as int_affiliateId
// 						, player.agent_id
// 						, IF(player.agent_id IS NULL or player.agent_id = "", 0, player.agent_id) as int_agent_id /* convert to int while NULL or Zero.*/
// 						, player.refereePlayerId
// 						, IF(player.refereePlayerId IS NULL or player.refereePlayerId = "", 0, player.refereePlayerId) as int_refereePlayerId
// 						FROM player
// 						LEFT OUTER JOIN ( /* as relay_player_report_hourly  */
// 							/* wrapper for last player_report_hourly pre player */
// 							/* START get the last record of the player */
// 							SELECT player_report_hourly.player_id
// 							, player_report_hourly.first_deposit_amount
// 							, player_report_hourly.first_deposit_datetime
// 							, IF(player_report_hourly.agent_id IS NULL or player_report_hourly.agent_id = "", 0, player_report_hourly.agent_id) as int_agent_id
// 							, IF(player_report_hourly.affiliate_id IS NULL or player_report_hourly.affiliate_id = "", 0, player_report_hourly.affiliate_id) as int_affiliate_id
// 							FROM player_report_hourly
// 							INNER JOIN ( /* as last_update_player_report_hourly */
// 								SELECT max(id) as max_id /* get the lastest reocrd for the player.*/
// 								, player_report_hourly.player_id
// 								FROM player_report_hourly
// 								WHERE $PRHplayerIdsCoditionStr  /* Patch for OGP-14393 */
// 								AND first_deposit_amount > 0 /* Patch for OGP-14393 */
// 								AND (agent_id IS NULL OR agent_id = "") /* filter multi-registered */
// 								$where4first_deposit_datetime /* Patch for OGP-14393 */
// 								$PRHaffiliateIdCoditionStr /* Patch for OGP-14393 */
// 								GROUP by player_id
// 							)as last_update_player_report_hourly ON last_update_player_report_hourly.player_id = player_report_hourly.player_id
// 																AND last_update_player_report_hourly.max_id = player_report_hourly.id
// 							WHERE first_deposit_amount > 0 /* Patch for OGP-14393 */
// 							$where4first_deposit_datetime /* Patch for OGP-14393 */
// 							/* EOF get the last record of the player */
// 						) as relay_player_report_hourly ON playerId = relay_player_report_hourly.player_id
// 																AND relay_player_report_hourly.first_deposit_amount > 0
// 																$where4first_deposit_datetime /* Patch for OGP-14393 */
// 						WHERE player.deleted_at IS NULL
// 						AND (agent_id IS NULL OR agent_id = "") /* filter multi-registered */
// 						AND relay_player_report_hourly.first_deposit_amount > 0
// 						$where4registration_datetime
// 						$where4first_deposit_datetime
// 					) as relay_player_report_hourly4cols
// 					WHERE affiliateList.affiliateId = int_affiliateId
// 					AND 0 = relay_player_report_hourly4cols.int_agent_id /* filter multi-registered */
// 					AND 0 = relay_player_report_hourly4cols.int_refereePlayerId /* filter multi-registered */
// 				) as totalFirstDepositedAmount
// 				FROM affiliates as affiliateList
// 				WHERE 1 $affiliatesAffiliateIdCoditionStr /* Patch for OGP-14393 */
// 			) as relay_affiliates_reporting
// 			WHERE $where4affiliate_username
// 		) as reporting
// EOF;

		$table = $this->data_tables->clearBlankSpaceAfterHeredoc($table);

		$where = array();
		$values = array();
		$joins = array();
		$group_by = array();
		$having = array();
		$distinct = false;
		$external_order=[];
		$notDatatable = '';
		$countOnlyField = 'affiliateId';
		$innerJoins=[];
		$useIndex=[];

		$markName = 'list';
		$this->utils->markProfilerStart($markName); // list mark.
		$this->config->set_item('debug_data_table_sql', true);

		if( $totalOnly ) {// from SummaryByAll
			$result = $this->data_tables->empty_data($request);
		}else{
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
		}

		$sql = $this->data_tables->last_query;
		$this->utils->markProfilerEndAndPrint($markName, $benchmarks[$markName]); // total mark.

		$sqls[$markName] = $sql; // list mark.

		if( ! $is_export ){
			/// subTotal append to $result['data'], rows.
			// DONT append before getMultipleRowArray().because getMultipleRowArray() will formatter data.
			$subTotalRowsIndex = count( $result['data'] );
			$result['data'][$subTotalRowsIndex][0] = '<span class="th subTotalCol">'.lang('Sub Total').'</span>';
			$result['data'][$subTotalRowsIndex][1] = '<span class="th subTotalCol">'.$this->data_tables->defaultFormatter($subtotals['totalRegisteredPlayers']).'</span>';
			$result['data'][$subTotalRowsIndex][2] = '<span class="th subTotalCol">'.$this->data_tables->defaultFormatter($subtotals['totalFirstDepositPlayers']).'</span>';
			$conversionRate = 0;
			if( $subtotals['totalRegisteredPlayers'] > 0 ){
				$conversionRate = $subtotals['totalFirstDepositPlayers']/$subtotals['totalRegisteredPlayers'];
			}
			$result['data'][$subTotalRowsIndex][3] = '<span class="th subTotalCol">'.$this->data_tables->percentageFormatter($conversionRate).'</span>';
			$result['data'][$subTotalRowsIndex][4] = '<span class="th subTotalCol">'.$this->data_tables->currencyFormatter($subtotals['totalFirstDepositedAmount']).'</span>';
		}

		// for Total, get orig SQL and sum(amount) from that.
		// $this->config->set_item('debug_data_table_sql', true);
		$this->data_tables->options['only_sql'] = true;
		// $request['start']
		$request['length'] = -1; // for ignore LIMIT.
		$result4sql = $this->data_tables->get_data($request // #1
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
		$this->data_tables->options['only_sql'] = false;
		$sql4total = $result4sql['data'];
		$sql = <<<EOF
		SELECT /* summaryByAffiliate */ SUM(totalRegisteredPlayers) as totalRegisteredPlayers
		, SUM(totalFirstDepositPlayers) as totalFirstDepositPlayers
		/* , SUM(totalFirstDepositPlayers)/SUM(totalRegisteredPlayers) as conversionRate */
		, SUM(totalFirstDepositedAmount) as totalFirstDepositedAmount
		FROM ($sql4total) as total;
EOF;
		$sql = $this->data_tables->clearBlankSpaceAfterHeredoc($sql);

		$markName = 'total'; // total mark.
		$this->utils->markProfilerStart($markName); // total mark.
		if( $totalOnly || true) {// from SummaryByAll - SummaryByAffiliate Need
			$result4Total = $readOnlyDB->query( $sql );
			$summary = $this->getMultipleRowArray($result4Total);
		}else{
			$summary = array();
		}
		$this->utils->markProfilerEndAndPrint($markName, $benchmarks[$markName]); // total mark.

		$sqls[$markName] = $sql; // total mark.

		// for Summary by ALL display Zero while NULL.
		if( empty($summary[0]['totalRegisteredPlayers']) ){ // for NULL
			$summary[0]['totalRegisteredPlayers'] = 0;
		}
		if( empty($summary[0]['totalFirstDepositPlayers']) ){ // for NULL
			$summary[0]['totalFirstDepositPlayers'] = 0;
		}
		if( empty($summary[0]['totalFirstDepositedAmount']) ){ // for NULL
			$summary[0]['totalFirstDepositedAmount'] = 0;
		}

		if( ! empty($summary[0]['totalRegisteredPlayers']) ){
			$summary[0]['conversionRate'] = $summary[0]['totalFirstDepositPlayers'] / $summary[0]['totalRegisteredPlayers'];
		}else{
			$summary[0]['conversionRate'] = 0;
		}

		// $this->utils->debug_log('1992.conversion_rate_report_summaryByDirectPlayer.summary:', $summary);
		$totalRowsIndex = count( $result['data'] );
		$result['data'][$totalRowsIndex][0] = '<span class="th totalCol">'.lang('Total').'</span>';
		$result['data'][$totalRowsIndex][1] = '<span class="th totalCol">'.$this->data_tables->defaultFormatter($summary[0]['totalRegisteredPlayers']).'</span>';
		$result['data'][$totalRowsIndex][2] = '<span class="th totalCol">'.$this->data_tables->defaultFormatter($summary[0]['totalFirstDepositPlayers']).'</span>';
		$result['data'][$totalRowsIndex][3] = '<span class="th totalCol">'.$this->data_tables->percentageFormatter($summary[0]['conversionRate']).'</span>';
		$result['data'][$totalRowsIndex][4] = '<span class="th totalCol">'.$this->data_tables->currencyFormatter($summary[0]['totalFirstDepositedAmount']).'</span>';

		if( $is_export ){ //  remove tags of html while export.
			foreach($result['data'][$totalRowsIndex] as $key => $val){
				$result['data'][$totalRowsIndex][$key] = strip_tags($val);
			}
		}

		$result['providerMethod'] = __FUNCTION__;
		$result['input'] = $input;
		$result['request'] = $request;
		$result['sqls'] = $sqls;
		$result['benchmarks'] = $benchmarks;
		$result['summaryByAll'] = $summary[0]; // for conversion_rate_report_summaryByAll().
		return $result;
		// $sqls for trace,

	} // EOF conversion_rate_report_summaryByAffiliate
	/**
	 * Get conversion_rate_report of summary By Agency
	 *
	 *
	 * Check SQL,
	 * - list: The list of reporting.
	 * - total: The total row at bottom of reporting.
	 *
	 * @link datatables, https://datatables.net/reference/option/
	 *
	 * @param boolean|array $extra_search Check called from export. ( entry: Export_data::conversion_rate_report() )
	 *
	 * @param boolean $totalOnly Called from conversion_rate_report_summaryByAll(). For Summary By ALL of reporting.
	 *
	 * @return array $result Base on datatables, and extra elements,
	 * - $result['providerMethod'] This function name for upgrade or debug.
	 * - $result['conversionRate'] The percentage at bottom of list.
	 * - $result['request'] Base on ci::input->post()
	 * - $result['input'] Parsing $request['extra_search'].
	 * - $result['sqls'] The API query SQL(s).
	 * - $result['benchmarks'] The spend time pre query.
	 * - $result['summaryByAll'] for conversion_rate_report_summaryByAll().
	 *
	 */
	public function conversion_rate_report_summaryByAgency( $extra_search = false, $totalOnly = false) {

		$result4SCET = $this->setCrrExecutionTime();

		$readOnlyDB = $this->getReadOnlyDB();
		$this->load->library('data_tables', array("DB" => $readOnlyDB));

		$request = $this->input->post();
		$input = $this->data_tables->extra_search($request);
		if( ! empty($extra_search) ){
			$input = $extra_search;
		}
		$is_export = ! empty($extra_search);

		$sqls = []; // for trace
		// enable / disable first_deposit_date_from[to]
		$search_first_deposit_date_switch = 'off'; // default
		if( ! empty($input['search_first_deposit_date_switch']) ){
			$search_first_deposit_date_switch = $input['search_first_deposit_date_switch'];
		}
		if($search_first_deposit_date_switch == 'off'){ // ignore first_deposit_date_from and first_deposit_date_to
			unset($input['first_deposit_date_from']);
			unset($input['first_deposit_date_to']);
		}

		$this->data_tables->options['is_export'] = $is_export;

		$subtotals = array();
		$subtotals['totalRegisteredPlayers'] = 0;
		$subtotals['totalFirstDepositPlayers'] = 0;
		$subtotals['conversionRate'] = 0;
		$subtotals['totalFirstDepositedAmount'] = 0;


		/// The cols START
		$colIndex = 0;
		$columns = array();
		$columns[$colIndex]['dt'] = $colIndex;
		$columns[$colIndex]['alias'] = 'agent_name';
		$columns[$colIndex]['select'] = 'agent_name';
		$columns[$colIndex]['name'] = lang('Agency Username');
		$columns[$colIndex]['formatter'] = function ($d, $row) {
			return $d;
		}; // EOF $columns[$colIndex]['formatter']
		$colIndex++;
		$columns[$colIndex]['dt'] = $colIndex;
		$columns[$colIndex]['alias'] = 'totalRegisteredPlayers';
		$columns[$colIndex]['select'] = 'totalRegisteredPlayers';
		$columns[$colIndex]['name'] = lang('Total Registered Players');
		// // Format: yyyy-mm-dd HH:MM:SS
		// $columns[$colIndex]['formatter'] = 'dateTimeFormatter';
		$columns[$colIndex]['formatter'] = function ($d, $row) use ( &$subtotals ){
			$subtotals['totalRegisteredPlayers'] += $d;
			return $d;
		}; // EOF $columns[$colIndex]['formatter']
		$colIndex++;
		$columns[$colIndex]['dt'] = $colIndex;
		$columns[$colIndex]['alias'] = 'totalFirstDepositPlayers';
		// $columns[$colIndex]['select'] = '`transactions`.`created_at`'; // ver. transactions
		$columns[$colIndex]['select'] = 'totalFirstDepositPlayers'; // ver. player_report_hourly
		$columns[$colIndex]['name'] = lang('Total First Deposited Player');
		// // Format: yyyy-mm-dd HH:MM:SS
		// $columns[$colIndex]['formatter'] = 'dateTimeFormatter'; // directly assign to function.
		$columns[$colIndex]['formatter'] = function ($d, $row) use ( &$subtotals ){
			$subtotals['totalFirstDepositPlayers'] += $d;
			return $d;
		}; // EOF $columns[$colIndex]['formatter']
		$colIndex++;
		$columns[$colIndex]['dt'] = $colIndex;
		$columns[$colIndex]['alias'] = 'conversionRate';
		// $columns[$colIndex]['select'] = '`transactions`.`amount`'; // ver. transactions
		$columns[$colIndex]['select'] = 'totalFirstDepositPlayers/totalRegisteredPlayers'; // ver. player_report_hourly
		$columns[$colIndex]['name'] = lang('Conversion Rate');
		$columns[$colIndex]['formatter'] = function ($d, $row) use ( &$subtotals ){ // 1,234,567.00
			$subtotals['conversionRate'] += $d; // for sub total.
			return $this->data_tables->percentageFormatter($d);
		}; // EOF $columns[$colIndex]['formatter']
		$colIndex++;
		$columns[$colIndex]['dt'] = $colIndex;
		$columns[$colIndex]['alias'] = 'totalFirstDepositedAmount';
		// $columns[$colIndex]['select'] = '`transactions`.`amount`'; // ver. transactions
		$columns[$colIndex]['select'] = 'totalFirstDepositedAmount'; // ver. player_report_hourly
		$columns[$colIndex]['name'] = lang('Total First Deposit Amount');
		$columns[$colIndex]['formatter'] = function ($d, $row) use ( &$subtotals ){ // 1,234,567.00
			$subtotals['totalFirstDepositedAmount'] += $d; // for sub total.
			return $this->data_tables->currencyFormatter($d);
		}; // EOF $columns[$colIndex]['formatter']
		/// EOF cols


		/// Patch for OGP-14393 Enhancement of Conversion Rate Report performance
		// Get the player who created on date range.
		$date_from_to = $this->convertInput2Date_from_to($input);
		$playerIdList = $this->getPlayerIdWhereCreatedOnRange($date_from_to['date_from'], $date_from_to['date_to']);
		// apply to player_report_hourly WHERE condition
		if( ! empty($playerIdList) ){
			// PRH = player_report_hourly
			$PRHplayerIdsCoditionStr = $this->getPlayerIdCoditionStr($playerIdList, 'player_report_hourly.player_id');
		}else{
			$PRHplayerIdsCoditionStr = 0;
		}

		$theField4registration_datetime ='created_on'; // OGP-14393 Enhancement of Conversion Rate Report performance
		$where4registration_datetime = $this->getWhere4registration_datetime($input, $theField4registration_datetime);
		if( ! empty($where4registration_datetime) ){
			$where4registration_datetime = ' AND '. $where4registration_datetime;
		}

		$theField4first_deposit_datetime ='first_deposit_datetime';
		$where4first_deposit_datetime = $this->getWhere4first_deposit_datetime($input, $theField4first_deposit_datetime);
		if( ! empty($where4first_deposit_datetime) ){
			$where4first_deposit_datetime = ' AND '. $where4first_deposit_datetime;
		}

		/// disable for OGP-14393 Enhancement of Conversion Rate Report performance
		// $where4agency_username = 'totalRegisteredPlayers > 0';
		// if( ! empty($input['agency_username']) ){
		// 	$where4agency_username = ' agent_name = "'. $input['agency_username']. '" ';
		// }


		$this->load->model(array('agency_model'));
		// $PRHagent_idCoditionStr = ''; /// disable for OGP-14393 Enhancement of Conversion Rate Report performance
		$agency_agentsAgent_idCoditionStr = '';
		if( ! empty($input['agency_username']) ){
			$agent_id = $this->agency_model->get_agent_id_by_agent_name( $input['agency_username'] );
			// $PRHagent_idCoditionStr = ' AND agent_id = "'. $agent_id. '"'; /// disable for OGP-14393 Enhancement of Conversion Rate Report performance
			$agency_agentsAgent_idCoditionStr = ' AND agent_id = "'. $agent_id. '"';
		}

		// totalRegisteredPlayers : 註冊人數
		// totalFirstDepositPlayers : 首存人數
		// totalFirstDepositedAmount : 首存金额
		//filter multi-registered: agent_id, affiliateId and refereePlayerId.

		/// OGP-14393 Enhancement of Conversion Rate Report performance
		// ----- ver. player_relay
		/// filter agency that without player
		$dofilter4totalRegisteredPlayers = false;
		if( ! empty($input['agency_username']) ){
		}else{
			$dofilter4totalRegisteredPlayers = true;
		}
		// Get agent_id who has registered player.
		if($dofilter4totalRegisteredPlayers){
			$sql = <<<EOF
			SELECT agent_id
			FROM player_relay
			WHERE deleted_at IS NULL
				AND 0 < player_relay.agent_id /* filter multi-registered */
				AND 0 = player_relay.affiliate_id /* filter multi-registered */
				AND 0 = player_relay.referee_player_id /* filter multi-registered */
				$where4registration_datetime
			GROUP BY agent_id
EOF;
			$result = $this->db->query($sql);
			$rows = $this->getMultipleRowArray($result);
			if( ! empty($rows) ){
				$agent_idCond = [];
				foreach($rows as $keyNumber => $row){
					$tmp4agent_id =$row['agent_id'];
					$agent_idCond[] =<<<EOF
					agent_id = "$tmp4agent_id"
EOF;
				}
				$agent_idCondStr = ' ( '. implode(' OR ', $agent_idCond). ' ) ';
			}else{
				$agent_idCondStr ="0";
			}

		}else{
			$agent_idCondStr = ' agent_name = "'. $input['agency_username'].'" ';
		}
		$table=<<<EOF
		( /* as reporting */
				SELECT agencyAgentList.agent_id
				, agencyAgentList.agent_name
				, ( /* as totalRegisteredPlayers */
					SELECT COUNT( player_id ) as counter
					FROM player_relay
					WHERE deleted_at IS NULL
						AND agencyAgentList.agent_id = player_relay.agent_id
						AND 0 = player_relay.affiliate_id /* filter multi-registered */
						AND 0 = player_relay.referee_player_id /* filter multi-registered */
						$where4registration_datetime
						/* disable for B3.1, $where4first_deposit_datetime */
				) as totalRegisteredPlayers
				,( /* as totalFirstDepositPlayers */
					SELECT count(player_id) as counter
					FROM player_relay
					WHERE deleted_at IS NULL
						AND player_relay.first_deposit_amount > 0 /* Patch for OGP-14393 */
						AND agencyAgentList.agent_id = player_relay.agent_id
						AND 0 = player_relay.affiliate_id /* filter multi-registered */
						AND 0 = player_relay.referee_player_id /* filter multi-registered */
						$where4registration_datetime
						$where4first_deposit_datetime /* Patch for OGP-14393 */ /* first_deposit_datetime, first_deposit_amount */
				) as totalFirstDepositPlayers
				,(/* as totalFirstDepositedAmount */
					SELECT sum( first_deposit_amount ) as amount
					FROM player_relay
					WHERE deleted_at IS NULL
					AND player_relay.first_deposit_amount > 0 /* Patch for OGP-14393 */
						AND agencyAgentList.agent_id = player_relay.agent_id
						AND 0 = player_relay.affiliate_id /* filter multi-registered */
						AND 0 = player_relay.referee_player_id /* filter multi-registered */
						$where4registration_datetime
						$where4first_deposit_datetime /* Patch for OGP-14393 */ /* first_deposit_datetime, first_deposit_amount */
				)as totalFirstDepositedAmount
				FROM agency_agents as agencyAgentList
				WHERE $agent_idCondStr
				$agency_agentsAgent_idCoditionStr /* Patch for OGP-14393 */
		) as reporting
EOF;

/// disable for OGP-14393 Enhancement of Conversion Rate Report performance
//		// ----- ver. player_report_hourly
// 		$table = <<<EOF
// 		( /* as reporting */
// 			SELECT * FROM (  /* as relay_agency_reporting */
// 				SELECT agencyAgentList.agent_id
// 				, agencyAgentList.agent_name
// 				,( /* as totalRegisteredPlayers */
// 					SELECT COUNT( playerId) as counter
// 					FROM ( /* as relay_player_report_hourly4cols */
// 						SELECT player.*
// 						, IF(player.agent_id IS NULL or player.agent_id = "", 0, player.agent_id) as int_agent_id
// 						, IF(player.affiliateId IS NULL or player.affiliateId = "", 0, player.affiliateId) as int_affiliateId
// 						, IF(player.refereePlayerId IS NULL or player.refereePlayerId = "", 0, player.refereePlayerId) as int_refereePlayerId
// 						FROM player
// 						LEFT OUTER JOIN ( /* as relay_player_report_hourly  */
// 							/* wrapper for last player_report_hourly pre player */
// 							/* START get the last record of the player */
// 							SELECT player_report_hourly.player_id
// 							, player_report_hourly.first_deposit_amount
// 							, player_report_hourly.first_deposit_datetime
// 							, IF(player_report_hourly.agent_id IS NULL or player_report_hourly.agent_id = "", 0, player_report_hourly.agent_id) as int_agent_id
// 							, IF(player_report_hourly.affiliate_id IS NULL or player_report_hourly.affiliate_id = "", 0, player_report_hourly.affiliate_id) as int_affiliate_id
// 							FROM player_report_hourly
// 							INNER JOIN ( /* as last_update_player_report_hourly */
// 								SELECT max(id) as max_id /* get the lastest reocrd for the player.*/
// 								, player_report_hourly.player_id
// 								FROM player_report_hourly
// 								WHERE $PRHplayerIdsCoditionStr  /* Patch for OGP-14393 */
// 								AND (affiliate_id IS NULL OR affiliate_id = "") /* filter multi-registered */
// 								$PRHagent_idCoditionStr /* Patch for OGP-14393 */
// 								GROUP by player_id
// 							)as last_update_player_report_hourly
// 							ON last_update_player_report_hourly.player_id = player_report_hourly.player_id
// 							AND last_update_player_report_hourly.max_id = player_report_hourly.id
// 							/* EOF get the last record of the player */
// 						) as relay_player_report_hourly ON playerId = relay_player_report_hourly.player_id
// 						WHERE player.deleted_at IS NULL
// 						AND (player.affiliateId IS NULL OR player.affiliateId = "") /* filter multi-registered */
// 						AND (refereePlayerId IS NULL OR refereePlayerId = "") /* filter multi-registered */
// 						$where4registration_datetime
// 						/* disable for B3.1, $where4first_deposit_datetime */
// 					) as relay_player_report_hourly4cols
// 					WHERE agencyAgentList.agent_id = relay_player_report_hourly4cols.agent_id
// 					AND 0 = relay_player_report_hourly4cols.int_affiliateId /* filter multi-registered */
// 					AND 0 = relay_player_report_hourly4cols.int_refereePlayerId /* filter multi-registered */
// 				) as totalRegisteredPlayers
// 				,( /* as totalFirstDepositPlayers */
// 					SELECT count(playerId) as counter
// 					FROM ( /* as relay_player_report_hourly4cols */
// 						SELECT player.*
// 						, IF(player.agent_id IS NULL or player.agent_id = "", 0, player.agent_id) as int_agent_id
// 						, IF(player.affiliateId IS NULL or player.affiliateId = "", 0, player.affiliateId) as int_affiliateId
// 						, IF(player.refereePlayerId IS NULL or player.refereePlayerId = "", 0, player.refereePlayerId) as int_refereePlayerId
// 						FROM player
// 						LEFT OUTER JOIN ( /* as relay_player_report_hourly  */
// 							/* wrapper for last player_report_hourly pre player */
// 							/* START get the last record of the player */
// 							SELECT player_report_hourly.player_id
// 							, player_report_hourly.first_deposit_amount
// 							, player_report_hourly.first_deposit_datetime
// 							, IF(player_report_hourly.agent_id IS NULL or player_report_hourly.agent_id = "", 0, player_report_hourly.agent_id) as int_agent_id
// 							, IF(player_report_hourly.affiliate_id IS NULL or player_report_hourly.affiliate_id = "", 0, player_report_hourly.affiliate_id) as int_affiliate_id
// 							FROM player_report_hourly
// 							INNER JOIN (
// 								SELECT max(id) as max_id /* get the lastest reocrd for the player.*/
// 								, player_report_hourly.player_id
// 								FROM player_report_hourly
// 								WHERE $PRHplayerIdsCoditionStr  /* Patch for OGP-14393 */
// 								AND first_deposit_amount > 0 /* Patch for OGP-14393 */
// 								AND (affiliate_id IS NULL OR affiliate_id = "") /* filter multi-registered */
// 								$where4first_deposit_datetime /* Patch for OGP-14393 */
// 								$PRHagent_idCoditionStr /* Patch for OGP-14393 */
// 								GROUP by player_id
// 							)as last_update_player_report_hourly
// 							ON last_update_player_report_hourly.player_id = player_report_hourly.player_id
// 								AND last_update_player_report_hourly.max_id = player_report_hourly.id
// 							/* EOF get the last record of the player */
// 						) as relay_player_report_hourly ON playerId = relay_player_report_hourly.player_id
// 														AND relay_player_report_hourly.first_deposit_amount > 0
// 														$where4first_deposit_datetime /* Patch for OGP-14393 */
// 						WHERE player.deleted_at IS NULL
// 						AND relay_player_report_hourly.first_deposit_amount > 0
// 						AND (player.affiliateId IS NULL OR player.affiliateId = "") /* filter multi-registered */
// 						AND (refereePlayerId IS NULL OR refereePlayerId = "") /* filter multi-registered */
// 						$where4registration_datetime
// 						$where4first_deposit_datetime
// 					) as relay_player_report_hourly4cols
// 					WHERE  agencyAgentList.agent_id = relay_player_report_hourly4cols.agent_id
// 					AND 0 = relay_player_report_hourly4cols.int_affiliateId /* filter multi-registered */
// 					AND 0 = relay_player_report_hourly4cols.int_refereePlayerId /* filter multi-registered */
//
// 				) as totalFirstDepositPlayers
// 				,( /* as totalFirstDepositedAmount */
// 					SELECT sum( first_deposit_amount ) as amount
// 					FROM ( /* as relay_player_report_hourly4cols */
// 						SELECT player.playerId
// 						, relay_player_report_hourly.first_deposit_amount
// 						, player.agent_id
// 						, IF(player.agent_id IS NULL or player.agent_id = "", 0, player.agent_id) as int_agent_id /* convert to int while NULL or Zero.*/
// 						, player.refereePlayerId
// 						, IF(player.refereePlayerId IS NULL or player.refereePlayerId = "", 0, player.refereePlayerId) as int_refereePlayerId
// 						, IF(player.affiliateId IS NULL or player.affiliateId = "", 0, player.affiliateId) as int_affiliateId
// 						FROM player
// 						LEFT OUTER JOIN ( /* as relay_player_report_hourly  */
// 							/* wrapper for last player_report_hourly pre player */
// 							/* START get the last record of the player */
// 							SELECT player_report_hourly.player_id
// 							, player_report_hourly.first_deposit_amount
// 							, player_report_hourly.first_deposit_datetime
// 							, IF(player_report_hourly.agent_id IS NULL or player_report_hourly.agent_id = "", 0, player_report_hourly.agent_id) as int_agent_id
// 							, IF(player_report_hourly.affiliate_id IS NULL or player_report_hourly.affiliate_id = "", 0, player_report_hourly.affiliate_id) as int_affiliate_id
// 							FROM player_report_hourly
// 							INNER JOIN (
// 								SELECT max(id) as max_id /* get the lastest reocrd for the player.*/
// 								, player_report_hourly.player_id
// 								FROM player_report_hourly
// 								WHERE $PRHplayerIdsCoditionStr  /* Patch for OGP-14393 */
// 								AND first_deposit_amount > 0 /* Patch for OGP-14393 */
// 								AND (affiliate_id IS NULL OR affiliate_id = "") /* filter multi-registered */
// 								$where4first_deposit_datetime /* Patch for OGP-14393 */
// 								$PRHagent_idCoditionStr /* Patch for OGP-14393 */
// 								GROUP by player_id
// 							)as last_update_player_report_hourly
// 							ON last_update_player_report_hourly.player_id = player_report_hourly.player_id
// 								AND last_update_player_report_hourly.max_id = player_report_hourly.id
// 							/* EOF get the last record of the player */
// 							WHERE first_deposit_amount > 0 /* Patch for OGP-14393 */
// 							$where4first_deposit_datetime /* Patch for OGP-14393 */
// 						) as relay_player_report_hourly ON playerId = relay_player_report_hourly.player_id
// 														AND relay_player_report_hourly.first_deposit_amount > 0
// 														$where4first_deposit_datetime /* Patch for OGP-14393 */
// 						WHERE player.deleted_at IS NULL
// 						AND relay_player_report_hourly.first_deposit_amount > 0
// 						AND (player.affiliateId IS NULL OR player.affiliateId = "") /* filter multi-registered */
// 						AND (refereePlayerId IS NULL OR refereePlayerId = "") /* filter multi-registered */
// 						$where4registration_datetime
// 						$where4first_deposit_datetime
// 					) as relay_player_report_hourly4cols
// 					WHERE agencyAgentList.agent_id = relay_player_report_hourly4cols.agent_id
// 					AND 0 = relay_player_report_hourly4cols.int_affiliateId /* filter multi-registered */
// 					AND 0 = relay_player_report_hourly4cols.int_refereePlayerId /* filter multi-registered */
// 				) as totalFirstDepositedAmount
// 				FROM agency_agents as agencyAgentList
// 				WHERE 1 $agency_agentsAgent_idCoditionStr /* Patch for OGP-14393 */
// 			) as relay_agency_reporting
// 			WHERE $where4agency_username
// 		) as reporting
// EOF;

		$table = $this->data_tables->clearBlankSpaceAfterHeredoc($table);

		$where = array();
		$values = array();
		$joins = array();
		$group_by = array();
		$having = array();
		$distinct = false;
		$external_order=[];
		$notDatatable = '';
		$countOnlyField = 'agent_id';
		$innerJoins=[];
		$useIndex=[];

		$markName = 'list';
		$this->utils->markProfilerStart($markName); // list mark.
		$this->config->set_item('debug_data_table_sql', true);
		if( $totalOnly ) {
			$result = $this->data_tables->empty_data($request);
		}else{
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
		}

		$this->utils->markProfilerEndAndPrint($markName, $benchmarks[$markName] ); // total mark.

		$sqls[$markName] = $this->data_tables->last_query; // total mark.

		if( ! $is_export ){

			/// subTotal append to $result['data'], rows.
			// MUST before getMultipleRowArray().because getMultipleRowArray() will formatter data.
			$subTotalRowsIndex = count( $result['data'] );
			$result['data'][$subTotalRowsIndex][0] = '<span class="th subTotalCol">'.lang('Sub Total').'</span>';
			$result['data'][$subTotalRowsIndex][1] = '<span class="th subTotalCol">'.$this->data_tables->defaultFormatter($subtotals['totalRegisteredPlayers']).'</span>';
			$result['data'][$subTotalRowsIndex][2] = '<span class="th subTotalCol">'.$this->data_tables->defaultFormatter($subtotals['totalFirstDepositPlayers']).'</span>';
			$conversionRate = 0;
			if( $subtotals['totalRegisteredPlayers'] > 0 ){
				$conversionRate = $subtotals['totalFirstDepositPlayers']/$subtotals['totalRegisteredPlayers'];
			}
			$result['data'][$subTotalRowsIndex][3] = '<span class="th subTotalCol">'.$this->data_tables->percentageFormatter($conversionRate).'</span>';
			$result['data'][$subTotalRowsIndex][4] = '<span class="th subTotalCol">'.$this->data_tables->currencyFormatter($subtotals['totalFirstDepositedAmount']).'</span>';
		}

		// for Total, get orig SQL and sum(amount) from that.
		// $this->config->set_item('debug_data_table_sql', true);
		$this->data_tables->options['only_sql'] = true;
		// $request['start']
		$request['length'] = -1; // for ignore LIMIT.
		$result4sql = $this->data_tables->get_data($request // #1
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
		$this->data_tables->options['only_sql'] = false;
		// $this->utils->debug_log('1984.conversion_rate_report_summaryByDirectPlayer.result4sql:', $result4sql);
		// $sql = 'SELECT SUM(totalRegisteredPlayers) as totalRegisteredPlayers FROM ('. $result4sql['data']. ') as tmp';

		$sql4total = $result4sql['data'];
		$sql = <<<EOF
		SELECT  /* summaryByAgency */ SUM(totalRegisteredPlayers) as totalRegisteredPlayers
		, SUM(totalFirstDepositPlayers) as totalFirstDepositPlayers
		/* , SUM(totalFirstDepositPlayers)/SUM(totalRegisteredPlayers)  as conversionRate */
		, SUM(totalFirstDepositedAmount) as totalFirstDepositedAmount
		FROM ($sql4total) as total;
EOF;
		$sql = $this->data_tables->clearBlankSpaceAfterHeredoc($sql);

		$markName = 'total'; // total mark.
		$this->utils->markProfilerStart($markName); // total mark.
		if( $totalOnly || true) {// from SummaryByAll
			$result4Total = $readOnlyDB->query( $sql );
			$summary = $this->getMultipleRowArray($result4Total);
		}else{
			$summary = array();
		}
		$this->utils->markProfilerEndAndPrint($markName, $benchmarks[$markName]); // total mark.

		$sqls[$markName] = $sql;

		// for Summary by ALL display Zero while NULL.
		if( empty($summary[0]['totalRegisteredPlayers']) ){ // for NULL
			$summary[0]['totalRegisteredPlayers'] = 0;
		}
		if( empty($summary[0]['totalFirstDepositPlayers']) ){ // for NULL
			$summary[0]['totalFirstDepositPlayers'] = 0;
		}
		if( empty($summary[0]['totalFirstDepositedAmount']) ){ // for NULL
			$summary[0]['totalFirstDepositedAmount'] = 0;
		}

		if( ! empty($summary[0]['totalRegisteredPlayers']) ){
			$summary[0]['conversionRate'] = $summary[0]['totalFirstDepositPlayers'] / $summary[0]['totalRegisteredPlayers'];
		}else{
			$summary[0]['conversionRate'] = 0;
		}

		// $this->utils->debug_log('1992.conversion_rate_report_summaryByDirectPlayer.summary:', $summary);
		$totalRowsIndex = count( $result['data'] );
		$result['data'][$totalRowsIndex][0] = '<span class="th totalCol">'.lang('Total').'</span>';
		$result['data'][$totalRowsIndex][1] = '<span class="th totalCol">'.$this->data_tables->defaultFormatter($summary[0]['totalRegisteredPlayers']).'</span>';
		$result['data'][$totalRowsIndex][2] = '<span class="th totalCol">'.$this->data_tables->defaultFormatter($summary[0]['totalFirstDepositPlayers']).'</span>';
		$result['data'][$totalRowsIndex][3] = '<span class="th totalCol">'.$this->data_tables->percentageFormatter($summary[0]['conversionRate']).'</span>';
		$result['data'][$totalRowsIndex][4] = '<span class="th totalCol">'.$this->data_tables->currencyFormatter($summary[0]['totalFirstDepositedAmount']).'</span>';

		if( $is_export ){ //  remove tags of html while export.
			foreach($result['data'][$totalRowsIndex] as $key => $val){
				$result['data'][$totalRowsIndex][$key] = strip_tags($val);
			}
		}

		$result['providerMethod'] = __FUNCTION__;
		$result['input'] = $input;
		$result['request'] = $request;
		$result['sqls'] = $sqls;
		$result['benchmarks'] = $benchmarks;
		$result['summaryByAll'] = $summary[0]; // for conversion_rate_report_summaryByAll().
		return $result;
		// $sqls for trace,
	} // EOF conversion_rate_report_summaryByAgency
	/**
	 * Get conversion_rate_report of summary By Referrer
	 *
	 *
	 * Check SQL,
	 * - list: The list of reporting.
	 * - total: The total row at bottom of reporting.
	 *
	 * @link datatables, https://datatables.net/reference/option/
	 *
	 * @param boolean|array $extra_search Check called from export. ( entry: Export_data::conversion_rate_report() )
	 *
	 * @param boolean $totalOnly Called from conversion_rate_report_summaryByAll(). For Summary By ALL of reporting.
	 *
	 * @return array $result Base on datatables, and extra elements,
	 * - $result['providerMethod'] This function name for upgrade or debug.
	 * - $result['conversionRate'] The percentage at bottom of list.
	 * - $result['request'] Base on ci::input->post()
	 * - $result['input'] Parsing $request['extra_search'].
	 * - $result['sqls'] The API query SQL(s).
	 * - $result['benchmarks'] The spend time pre query.
	 * - $result['summaryByAll'] for conversion_rate_report_summaryByAll().
	 */
	public function conversion_rate_report_summaryByReferrer($extra_search = false, $totalOnly = false) {
		$this->setCrrExecutionTime();

		$readOnlyDB = $this->getReadOnlyDB();
		$this->load->library('data_tables', array("DB" => $readOnlyDB));


		$request = $this->input->post();
		$input = $this->data_tables->extra_search($request);
		if( ! empty($extra_search) ){
			$input = $extra_search;
		}
		$is_export = ! empty($extra_search);

		$sqls = []; // for trace

		// enable / disable first_deposit_date_from[to]
		$search_first_deposit_date_switch = 'off'; // default
		if( ! empty($input['search_first_deposit_date_switch']) ){
			$search_first_deposit_date_switch = $input['search_first_deposit_date_switch'];
		}
		if($search_first_deposit_date_switch == 'off'){ // ignore first_deposit_date_from and first_deposit_date_to
			unset($input['first_deposit_date_from']);
			unset($input['first_deposit_date_to']);
		}

		$this->data_tables->options['is_export'] = $is_export;

		$subtotals = array();
		$subtotals['conversionRate'] = 0;
		$subtotals['totalFirstDepositedAmount'] = 0;

		$subtotals = array();
		$subtotals['totalRegisteredPlayers'] = 0;
		$subtotals['totalFirstDepositPlayers'] = 0;
		$subtotals['conversionRate'] = 0;
		$subtotals['totalFirstDepositedAmount'] = 0;



		/// The cols START
		$colIndex = 0;
		$columns = array();
		$columns[$colIndex]['dt'] = $colIndex;
		$columns[$colIndex]['alias'] = 'orefereeUsername';
		$columns[$colIndex]['select'] = 'orefereeUsername';
		$columns[$colIndex]['name'] = lang('Referrers Username');
		$columns[$colIndex]['formatter'] = function ($d, $row) {
			return $d;
		}; // EOF $columns[$colIndex]['formatter']
		$colIndex++;
		$columns[$colIndex]['dt'] = $colIndex;
		$columns[$colIndex]['alias'] = 'totalRegisteredPlayers';
		$columns[$colIndex]['select'] = 'totalRegisteredPlayers';
		$columns[$colIndex]['name'] = lang('Total Registered Players');
		$columns[$colIndex]['formatter'] = function ($d, $row) use ( &$subtotals ){
			$subtotals['totalRegisteredPlayers'] += $d;
			return $d;
		}; // EOF $columns[$colIndex]['formatter']
		$colIndex++;
		$columns[$colIndex]['dt'] = $colIndex;
		$columns[$colIndex]['alias'] = 'totalFirstDepositPlayers';
		// $columns[$colIndex]['select'] = '`transactions`.`created_at`'; // ver. transactions
		$columns[$colIndex]['select'] = 'totalFirstDepositPlayers'; // ver. player_report_hourly
		$columns[$colIndex]['name'] = lang('Total First Deposited Player');
		$columns[$colIndex]['formatter'] = function ($d, $row) use ( &$subtotals ){
			$subtotals['totalFirstDepositPlayers'] += $d;
			return $d;
		}; // EOF $columns[$colIndex]['formatter']
		$colIndex++;
		$columns[$colIndex]['dt'] = $colIndex;
		$columns[$colIndex]['alias'] = 'conversionRate';
		// $columns[$colIndex]['select'] = '`transactions`.`amount`'; // ver. transactions
		$columns[$colIndex]['select'] = 'totalFirstDepositPlayers/totalRegisteredPlayers'; // ver. player_report_hourly
		$columns[$colIndex]['name'] = lang('Conversion Rate');
		$columns[$colIndex]['formatter'] = function ($d, $row) use ( &$subtotals ){ // 1,234,567.00
			$subtotals['conversionRate'] += $d; // for sub total.
			return $this->data_tables->percentageFormatter($d);
		}; // EOF $columns[$colIndex]['formatter']
		$colIndex++;
		$columns[$colIndex]['dt'] = $colIndex;
		$columns[$colIndex]['alias'] = 'totalFirstDepositedAmount';
		// $columns[$colIndex]['select'] = '`transactions`.`amount`'; // ver. transactions
		$columns[$colIndex]['select'] = 'totalFirstDepositedAmount'; // ver. player_report_hourly
		$columns[$colIndex]['name'] = lang('Total First Deposit Amount');
		$columns[$colIndex]['formatter'] = function ($d, $row) use ( &$subtotals ){ // 1,234,567.00
			$subtotals['totalFirstDepositedAmount'] += $d; // for sub total.
			return $this->data_tables->currencyFormatter($d);
		}; // EOF $columns[$colIndex]['formatter']
		/// EOF cols

		/// Patch for OGP-14393 Enhancement of Conversion Rate Report performance
		// Get the player who created on date range.
		$date_from_to = $this->convertInput2Date_from_to($input);
		$playerIdList = $this->getPlayerIdWhereCreatedOnRange($date_from_to['date_from'], $date_from_to['date_to']);
		// apply to player_report_hourly WHERE condition
		if( ! empty($playerIdList) ){
			// PRH = player_report_hourly
			$PRHplayerIdsCoditionStr = $this->getPlayerIdCoditionStr($playerIdList, 'player_report_hourly.player_id');
		}else{
			$PRHplayerIdsCoditionStr = 0;
		}

		/// disable for OGP-14393 Enhancement of Conversion Rate Report performance
		// $theField4registration_datetime ='player.createdOn';
		// $where4registration_datetime = $this->getWhere4registration_datetime($input, $theField4registration_datetime);
		// if( ! empty($where4registration_datetime) ){
		// 	$where4registration_datetime = ' AND '. $where4registration_datetime;
		// }

		$theField4first_deposit_datetime ='first_deposit_datetime';
		$where4first_deposit_datetime = $this->getWhere4first_deposit_datetime($input, $theField4first_deposit_datetime);
		if( ! empty($where4first_deposit_datetime) ){
			$where4first_deposit_datetime = ' AND '. $where4first_deposit_datetime;
		}

		$where4referrers_username = ' totalRegisteredPlayers > 0';
		if( ! empty($input['referrers_username']) ){
			$where4referrers_username =  ' orefereeUsername = "'. $input['referrers_username']. '" ';
		}

		// ----- ver. player_relay
		// reporting = refereePlayer+ [player+relay_player_report_hourly]
		$theField4registration_datetime ='player_relay.created_on';
		$where4registration_datetime = $this->getWhere4registration_datetime($input, $theField4registration_datetime);
		if( ! empty($where4registration_datetime) ){
			$where4registration_datetime = ' AND '. $where4registration_datetime;
		}
		if( empty($input['referrers_username']) ){
			$where4referrers_username = ''; // change default
		}else{
			$where4referrers_username =  ' refereePlayer.username = "'. $input['referrers_username']. '" ';
			$where4referrers_username = ' AND '. $where4referrers_username; // change default
		}
		/// filter agency that without player
		$dofilter4totalRegisteredPlayers = false;
		if( ! empty($input['referrers_username']) ){
		}else{
			$dofilter4totalRegisteredPlayers = true;
		}
		$idCondStr = '';
		if($dofilter4totalRegisteredPlayers){
			$sql = <<<EOF
			SELECT referee_player_id
			FROM player_relay
			WHERE deleted_at IS NULL
				AND 0 < player_relay.referee_player_id
				AND 0 = player_relay.agent_id /* filter multi-registered */
				AND 0 = player_relay.affiliate_id /* filter multi-registered */
				AND referee_player_id > 0
				$where4registration_datetime
			GROUP BY referee_player_id
EOF;
			$result = $this->db->query($sql);
			$rows = $this->getMultipleRowArray($result);
			if( ! empty($rows) ){
				$idCond = [];
				foreach($rows as $keyNumber => $row){
					$tmp4id =$row['referee_player_id'];
					$idCond[] =<<<EOF
					refereePlayer.playerId = "$tmp4id"
EOF;
 // ref. refereePlayer.playerId
				}
				$idCondStr = ' AND ( '. implode(' OR ', $idCond). ' ) ';
			}else{
				$idCondStr = ' AND 0';
			}

		}
		// ----- ver. player_relay
		$table = <<<EOF
		( /* as reporting */
			SELECT refereePlayer.playerId as orefereePlayerId
				, refereePlayer.username as orefereeUsername
				, ( /* as totalRegisteredPlayers */
					SELECT COUNT( player_id) as counter
					FROM player_relay
					WHERE player_relay.deleted_at IS NULL
					AND player.playerId = player_relay.referee_player_id
					AND player_relay.agent_id = 0 /* filter multi-registered */
					AND player_relay.affiliate_id = 0 /* filter multi-registered */
					$where4registration_datetime
					/* disable for B3.1, $where4first_deposit_datetime */
				) as totalRegisteredPlayers /* 註冊人數 */
				,( /* as totalFirstDepositPlayers */
					SELECT COUNT( player_id) as counter
					FROM player_relay
					WHERE player_relay.deleted_at IS NULL
					AND player_relay.first_deposit_amount > 0
					AND player.playerId = player_relay.referee_player_id
					AND player_relay.agent_id = 0 /* filter multi-registered */
					AND player_relay.affiliate_id = 0 /* filter multi-registered */
					$where4registration_datetime
					$where4first_deposit_datetime
				) as totalFirstDepositPlayers /* 首儲玩家數 */
				,( /* as totalFirstDepositedAmount */
					SELECT SUM( first_deposit_amount) as amount
					FROM player_relay
					WHERE player_relay.deleted_at IS NULL
					AND player_relay.first_deposit_amount > 0
					AND player.playerId = player_relay.referee_player_id
					AND player_relay.agent_id = 0 /* filter multi-registered */
					AND player_relay.affiliate_id = 0 /* filter multi-registered */
					$where4registration_datetime
					$where4first_deposit_datetime
				) as totalFirstDepositedAmount /* 首儲玩家總金額 */
			FROM player
			INNER JOIN player as refereePlayer ON refereePlayer.playerId = player.playerId /* player.refereePlayerId */
			WHERE player.deleted_at IS NULL
			$idCondStr
			$where4referrers_username
		) as reporting
EOF;

/// disable for OGP-14393 Enhancement of Conversion Rate Report performance
// ----- ver. player_report_hourly
// 		$table = <<<EOF
// 		( /* as reporting */
// 			SELECT *
// 			FROM ( /* as relay_referer_reporting */
// 				SELECT refereePlayer.playerId as orefereePlayerId
// 				, refereePlayer.username as orefereeUsername
// 				,( /* as totalRegisteredPlayers */
// 					SELECT COUNT( playerId) as counter
// 					FROM ( /* as relay_player_report_hourly4cols */
// 						SELECT player.playerId
// 						, player.agent_id
// 						, IF(player.agent_id IS NULL or player.agent_id = "", "0", player.agent_id) as int_agent_id
// 						, player.refereePlayerId
// 						, IF(player.affiliateId IS NULL or player.affiliateId = "", "0", player.affiliateId) as int_affiliateId
// 						, IF(player.refereePlayerId IS NULL or player.refereePlayerId = "", "0", player.refereePlayerId) as int_refereePlayerId
// 						FROM player
// 						LEFT OUTER JOIN ( /* as relay_player_report_hourly  */
// 							/* wrapper for last player_report_hourly pre player */
// 							/* START get the last record of the player */
// 							SELECT player_report_hourly.*
// 							, IF(player_report_hourly.agent_id IS NULL or player_report_hourly.agent_id = "", "0", player_report_hourly.agent_id) as int_agent_id
// 							, IF(player_report_hourly.affiliate_id IS NULL or player_report_hourly.affiliate_id = "", "0", player_report_hourly.affiliate_id) as int_affiliate_id
// 							FROM player_report_hourly
// 							INNER JOIN (
// 								SELECT max(id) as max_id /* get the lastest reocrd for the player.*/
// 								, player_report_hourly.player_id
// 								FROM player_report_hourly
// 								WHERE $PRHplayerIdsCoditionStr /* Patch for OGP-14393 */
// 								AND (agent_id IS NULL OR agent_id = "") /* filter multi-registered */
// 								AND (affiliate_id IS NULL OR affiliate_id = "") /* filter multi-registered */
// 								GROUP by player_id
// 							)as last_update_player_report_hourly
// 							ON last_update_player_report_hourly.player_id = player_report_hourly.player_id
// 							AND last_update_player_report_hourly.max_id = player_report_hourly.id
// 							/* EOF get the last record of the player */
// 						) as relay_player_report_hourly ON playerId = relay_player_report_hourly.player_id
// 						WHERE player.deleted_at IS NULL
// 						AND (player.agent_id IS NULL OR player.agent_id = "") /* filter multi-registered */
// 						AND (player.affiliateId IS NULL OR player.affiliateId = "") /* filter multi-registered */
// 						$where4registration_datetime
// 						/* disable for B3.1, $where4first_deposit_datetime */
// 					) as relay_player_report_hourly4cols
// 					WHERE refereePlayer.playerId = relay_player_report_hourly4cols.int_refereePlayerId
// 					AND 0 = relay_player_report_hourly4cols.int_agent_id /* filter multi-registered */
// 					AND 0 = relay_player_report_hourly4cols.int_affiliateId /* filter multi-registered */
// 				) as totalRegisteredPlayers /* 註冊人數 */
// 				, ( /* as totalFirstDepositPlayers */
// 					SELECT COUNT( playerId) as counter
// 					FROM ( /* as relay_player_report_hourly4cols */
// 						SELECT player.playerId
// 						, player.agent_id
// 						, IF(player.agent_id IS NULL or player.agent_id = "", "0", player.agent_id) as int_agent_id
// 						, player.affiliateId affiliate_id
// 						, IF(player.affiliateId IS NULL or player.affiliateId = "", "0", player.affiliateId) as int_affiliateId
// 						, IF(player.refereePlayerId IS NULL or player.refereePlayerId = "", "0", player.refereePlayerId) as int_refereePlayerId
// 						FROM player
// 						INNER JOIN player as irefereePlayer ON irefereePlayer.playerId = player.refereePlayerId
// 						LEFT OUTER JOIN (
// 							/* get the last record of the player */
// 							SELECT player_report_hourly.*
// 							FROM player_report_hourly
// 							INNER JOIN (
// 								SELECT max(id) as max_id
// 								, player_report_hourly.player_id
// 								FROM player_report_hourly
// 								WHERE $PRHplayerIdsCoditionStr /* Patch for OGP-14393 */
// 								AND first_deposit_amount > 0 /* Patch for OGP-14393 */
// 								AND (agent_id IS NULL OR agent_id = "") /* filter multi-registered */
// 								AND (affiliate_id IS NULL OR affiliate_id = "") /* filter multi-registered */
// 								GROUP by player_id
// 							)as last_update_player_report_hourly
// 							ON last_update_player_report_hourly.player_id = player_report_hourly.player_id
// 							AND last_update_player_report_hourly.max_id = player_report_hourly.id
// 							/* EOF get the last record of the player */
// 						) as relay_player_report_hourly	ON player.playerId = relay_player_report_hourly.player_id
// 														AND relay_player_report_hourly.first_deposit_amount > 0
// 						WHERE relay_player_report_hourly.first_deposit_amount > 0
// 						AND player.deleted_at IS NULL
// 						AND (player.agent_id IS NULL OR player.agent_id = "") /* filter multi-registered */
// 						AND (player.affiliateId IS NULL OR player.affiliateId = "") /* filter multi-registered */
// 						$where4registration_datetime
// 						$where4first_deposit_datetime
// 					) as relay_player_report_hourly4cols
// 					WHERE refereePlayer.playerId = relay_player_report_hourly4cols.int_refereePlayerId
// 					AND 0 = relay_player_report_hourly4cols.int_agent_id /* filter multi-registered */
// 					AND 0 = relay_player_report_hourly4cols.int_affiliateId /* filter multi-registered */
// 				) as totalFirstDepositPlayers /* 首儲玩家數 */
// 				, ( /* as totalFirstDepositedAmount */
// 					SELECT SUM( first_deposit_amount) as counter
// 					FROM ( /* as relay_player_report_hourly4cols */
// 						SELECT player.playerId
// 						, relay_player_report_hourly.first_deposit_amount
// 						, player.affiliateId affiliate_id
// 						, IF(player.affiliateId IS NULL or player.affiliateId = "", "0", player.affiliateId) as int_affiliateId
// 						, player.agent_id
// 						, IF(player.agent_id IS NULL or player.agent_id = "", "0", player.agent_id) as int_agent_id /* convert to int while NULL or Zero.*/
// 						, player.refereePlayerId
// 						, IF(player.refereePlayerId IS NULL or player.refereePlayerId = "", "0", player.refereePlayerId) as int_refereePlayerId
// 						FROM player
//
// 						INNER JOIN player as irefereePlayer ON irefereePlayer.playerId = player.refereePlayerId
// 						LEFT OUTER JOIN ( /* get the last record of the player */
// 							SELECT player_report_hourly.*
// 							FROM player_report_hourly
// 							INNER JOIN (
// 								SELECT max(id) as max_id
// 								, player_report_hourly.player_id
// 								FROM player_report_hourly
// 								WHERE $PRHplayerIdsCoditionStr /* Patch for OGP-14393 */
// 								AND first_deposit_amount > 0 /* Patch for OGP-14393 */
// 								AND (agent_id IS NULL OR agent_id = "") /* filter multi-registered */
// 								AND (affiliate_id IS NULL OR affiliate_id = "") /* filter multi-registered */
// 								GROUP by player_id
// 							)as last_update_player_report_hourly
// 							ON last_update_player_report_hourly.player_id = player_report_hourly.player_id
// 							AND last_update_player_report_hourly.max_id = player_report_hourly.id
// 							/* EOF get the last record of the player */
// 						) as relay_player_report_hourly	ON player.playerId = relay_player_report_hourly.player_id
// 														AND relay_player_report_hourly.first_deposit_amount > 0
// 						WHERE relay_player_report_hourly.first_deposit_amount > 0
// 						AND player.deleted_at IS NULL
// 						AND (player.agent_id IS NULL OR player.agent_id = "") /* filter multi-registered */
// 						AND (player.affiliateId IS NULL OR player.affiliateId = "") /* filter multi-registered */
// 						$where4registration_datetime
// 						$where4first_deposit_datetime
// 					) as relay_player_report_hourly4cols
// 					WHERE refereePlayer.playerId = relay_player_report_hourly4cols.int_refereePlayerId
// 					AND 0 = relay_player_report_hourly4cols.int_agent_id /* filter multi-registered */
// 					AND 0 = relay_player_report_hourly4cols.int_affiliateId /* filter multi-registered */
// 				) as totalFirstDepositedAmount /* 首儲玩家總金額 */
// 				FROM player
// 				INNER JOIN player as refereePlayer ON refereePlayer.playerId = player.refereePlayerId
// 				WHERE player.deleted_at IS NULL
// 			) as relay_referer_reporting
// 			WHERE $where4referrers_username
// 			GROUP BY orefereePlayerId /*refereePlayer.playerId*/
// 		) as reporting
// EOF;

		$table = $this->data_tables->clearBlankSpaceAfterHeredoc($table);

		$where = array();
		$values = array();
		$joins = array();
		$group_by = array();
		$having = array();
		$distinct = false;
		$external_order=[];
		$notDatatable = '';
		$countOnlyField = 'orefereePlayerId';
		$innerJoins=[];
		$useIndex=[];

		$markName = 'list';
		$this->utils->markProfilerStart($markName); // list mark.
		$orig4debug_data_table_sql =$this->config->item('debug_data_table_sql');
		$this->config->set_item('debug_data_table_sql', true);
		if( $totalOnly ) {
			$result = $this->data_tables->empty_data($request);
		}else{
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
		}


		$sql = $this->data_tables->last_query; // for trace
		$this->config->set_item('debug_data_table_sql', $orig4debug_data_table_sql); // revert debug_data_table_sql
		$this->utils->markProfilerEndAndPrint($markName, $benchmarks[$markName]); // total mark.

		$sqls[$markName] = $sql; // list mark.

		if( ! $is_export ){
			/// subTotal append to $result['data'], rows.
			// MUST before getMultipleRowArray().because getMultipleRowArray() will formatter data.
			$subTotalRowsIndex = count( $result['data'] );
			$result['data'][$subTotalRowsIndex][0] = '<span class="th subTotalCol">'.lang('Sub Total').'</span>';
			$result['data'][$subTotalRowsIndex][1] = '<span class="th subTotalCol">'.$this->data_tables->defaultFormatter($subtotals['totalRegisteredPlayers']).'</span>';
			$result['data'][$subTotalRowsIndex][2] = '<span class="th subTotalCol">'.$this->data_tables->defaultFormatter($subtotals['totalFirstDepositPlayers']).'</span>';
			$conversionRate = 0; // default
			if( $subtotals['totalRegisteredPlayers'] > 0 ){ // avoid division by zero
				$conversionRate = $subtotals['totalFirstDepositPlayers']/$subtotals['totalRegisteredPlayers'];
			}
			$result['data'][$subTotalRowsIndex][3] = '<span class="th subTotalCol">'.$this->data_tables->percentageFormatter($conversionRate).'</span>';
			$result['data'][$subTotalRowsIndex][4] = '<span class="th subTotalCol">'.$this->data_tables->currencyFormatter($subtotals['totalFirstDepositedAmount']).'</span>';
		}

		// for Total, get orig SQL and sum(amount) from that.
		// $this->config->set_item('debug_data_table_sql', true);
		$this->data_tables->options['only_sql'] = true;
		// $request['start']
		$request['length'] = -1; // for ignore LIMIT.
		$result4sql = $this->data_tables->get_data($request // #1
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
		$this->data_tables->options['only_sql'] = false;
		$sql4total = $result4sql['data'];
		$sql = <<<EOF
		SELECT  /* summaryByReferrer */ SUM(totalRegisteredPlayers) as totalRegisteredPlayers
		, SUM(totalFirstDepositPlayers) as totalFirstDepositPlayers
		/* , SUM(totalFirstDepositPlayers)/SUM(totalRegisteredPlayers) as conversionRate */
		, SUM(totalFirstDepositedAmount) as totalFirstDepositedAmount
		FROM ($sql4total) as total;
EOF;
		$sql = $this->data_tables->clearBlankSpaceAfterHeredoc($sql);

		$markName = 'total'; // total mark.
		$this->utils->markProfilerStart($markName); // total mark.
		if( $totalOnly || true) {// from SummaryByAll
			$result4Total = $readOnlyDB->query( $sql );
			$summary = $this->getMultipleRowArray($result4Total);
		}else{
			$summary = array();
		}
		$this->utils->markProfilerEndAndPrint($markName, $benchmarks[$markName]); // total mark.

		$sqls[$markName] = $sql; // total mark.

		// for Summary by ALL display Zero while NULL.
		if( empty($summary[0]['totalRegisteredPlayers']) ){ // for NULL
			$summary[0]['totalRegisteredPlayers'] = 0;
		}
		if( empty($summary[0]['totalFirstDepositPlayers']) ){ // for NULL
			$summary[0]['totalFirstDepositPlayers'] = 0;
		}
		if( empty($summary[0]['totalFirstDepositedAmount']) ){ // for NULL
			$summary[0]['totalFirstDepositedAmount'] = 0;
		}

		if( ! empty($summary[0]['totalRegisteredPlayers']) ){ // for conversionRate
			$summary[0]['conversionRate'] = $summary[0]['totalFirstDepositPlayers'] / $summary[0]['totalRegisteredPlayers'];
		}else{
			$summary[0]['conversionRate'] = 0;
		}

		// $this->utils->debug_log('1992.conversion_rate_report_summaryByDirectPlayer.summary:', $summary);
		$totalRowsIndex = count( $result['data'] );
		$result['data'][$totalRowsIndex][0] = '<span class="th totalCol">'.lang('Total').'</span>';
		$result['data'][$totalRowsIndex][1] = '<span class="th totalCol">'.$this->data_tables->defaultFormatter($summary[0]['totalRegisteredPlayers']).'</span>';
		$result['data'][$totalRowsIndex][2] = '<span class="th totalCol">'.$this->data_tables->defaultFormatter($summary[0]['totalFirstDepositPlayers']).'</span>';
		$result['data'][$totalRowsIndex][3] = '<span class="th totalCol">'.$this->data_tables->percentageFormatter($summary[0]['conversionRate']).'</span>';
		$result['data'][$totalRowsIndex][4] = '<span class="th totalCol">'.$this->data_tables->currencyFormatter($summary[0]['totalFirstDepositedAmount']).'</span>';

		if( $is_export ){ //  remove tags of html while export.
			foreach($result['data'][$totalRowsIndex] as $key => $val){
				$result['data'][$totalRowsIndex][$key] = strip_tags($val);
			}
		}

		$result['providerMethod'] = __FUNCTION__;
		$result['input'] = $input;
		$result['request'] = $request;
		$result['sqls'] = $sqls;
		$result['benchmarks'] = $benchmarks;
		$result['summaryByAll'] = $summary[0]; // for conversion_rate_report_summaryByAll().
		return $result;

		// $sqls for trace,
	} // EOF conversion_rate_report_summaryByReferrer

	public function conversion_rate_report_summaryByReferredAffiliate($extra_search = false, $totalOnly = false) {
		$this->setCrrExecutionTime();

		$readOnlyDB = $this->getReadOnlyDB();
		$this->load->library('data_tables', array("DB" => $readOnlyDB));

		$request = $this->input->post();
		$input = $this->data_tables->extra_search($request);
		if( ! empty($extra_search) ){
			$input = $extra_search;
		}
		$is_export = ! empty($extra_search);

		// enable / disable first_deposit_date_from[to]
		$search_first_deposit_date_switch = 'off'; // default
		if( ! empty($input['search_first_deposit_date_switch']) ){
			$search_first_deposit_date_switch = $input['search_first_deposit_date_switch'];
		}
		if($search_first_deposit_date_switch == 'off'){ // ignore first_deposit_date_from and first_deposit_date_to
			unset($input['first_deposit_date_from']);
			unset($input['first_deposit_date_to']);
		}

		$this->data_tables->options['is_export'] = $is_export;
		// Default clause

		$sqls = []; // for trace SQL.

		$subtotals = array();
		$subtotals['firstDepositAmount'] = 0;

		/// The cols START
		$colIndex = 0;
		$columns = array();
		$columns[$colIndex]['dt'] = $colIndex;
		$columns[$colIndex]['alias'] = 'username';
		$columns[$colIndex]['select'] = 'username';
		$columns[$colIndex]['name'] = lang('Reffered Player Username');
		$columns[$colIndex]['formatter'] = function ($d, $row) {
			return $d;
		}; // EOF $columns[$colIndex]['formatter']
		$colIndex++;
		$columns[$colIndex]['dt'] = $colIndex;
		$columns[$colIndex]['alias'] = 'createdOn';
		$columns[$colIndex]['select'] = 'createdOn';
		$columns[$colIndex]['name'] = lang('Registration Time');
		// Format: yyyy-mm-dd HH:MM:SS
		$columns[$colIndex]['formatter'] = 'dateTimeFormatter';
		$colIndex++;
		$columns[$colIndex]['dt'] = $colIndex;
		$columns[$colIndex]['alias'] = 'firstDepositTime';
		// $columns[$colIndex]['select'] = '`transactions`.`created_at`'; // ver. transactions
		$columns[$colIndex]['select'] = 'first_deposit_datetime'; // ver. player_report_hourly
		$columns[$colIndex]['name'] = lang('First Deposit Time');
		// Format: yyyy-mm-dd HH:MM:SS
		$columns[$colIndex]['formatter'] = 'dateTimeFormatter'; // directly assign to function.
		$colIndex++;
		$columns[$colIndex]['dt'] = $colIndex;
		$columns[$colIndex]['alias'] = 'firstDepositAmount';
		// $columns[$colIndex]['select'] = '`transactions`.`amount`'; // ver. transactions
		$columns[$colIndex]['select'] = 'first_deposit_amount'; // ver. player_report_hourly
		$columns[$colIndex]['name'] = lang('First Deposit Amount');
		$columns[$colIndex]['formatter'] = function ($d, $row) use ( &$subtotals ){ // 1,234,567.00
			$subtotals['firstDepositAmount'] += $d; // for sub total.
			return $this->data_tables->currencyFormatter($d);
		}; // EOF $columns[$colIndex]['formatter']

		$colIndex++; // just for count player.

		$columns[$colIndex]['alias'] = 'playerId';
		$columns[$colIndex]['select'] = 'playerId'; // ver. player_report_hourly


		// PRH = player_report_hourly
		// RDT = registration_datetime
		$theFileName='player_report_hourly.registered_date';
		$where4PrhRd = $this->getWhere4registration_datetime($input, $theFileName);

		/// Patch for OGP-14393 Enhancement of Conversion Rate Report performance
		// Get the player who created on date range.
		$date_from_to = $this->convertInput2Date_from_to($input);
		$playerIdList = $this->getPlayerIdWhereCreatedOnRange($date_from_to['date_from'], $date_from_to['date_to']);
		// apply to player_report_hourly WHERE condition
		if( ! empty($playerIdList) ){
			// PRH = player_report_hourly
			$PRHplayerIdsCoditionStr = $this->getPlayerIdCoditionStr($playerIdList, 'player_report_hourly.player_id');
		}else{
			$PRHplayerIdsCoditionStr = 0;
		}

		$theField4first_deposit_datetime ='first_deposit_datetime';
		$where4first_deposit_datetime = $this->getWhere4first_deposit_datetime($input, $theField4first_deposit_datetime);
		if( ! empty($where4first_deposit_datetime) ){
			$where4first_deposit_datetime = ' AND '. $where4first_deposit_datetime;
		}

		/// OGP-14393 Enhancement of Conversion Rate Report performance
		$theField4registration_datetime ='created_on';
		$where4registration_datetime = $this->getWhere4registration_datetime($input, $theField4registration_datetime);
		if( ! empty($where4registration_datetime) ){
			$where4registration_datetime = ' AND '. $where4registration_datetime;
		}
		$table = <<<EOF
		( /* as reporting */
			SELECT player_id as playerId
			, username
			, created_on as createdOn
			, ( /* as first_deposit_amount */
				SELECT first_deposit_amount
				FROM player_relay
				WHERE deleted_at IS NULL
					AND agent_id=0 /* filter multi-registered */
					AND affiliate_id>0 /* filter multi-registered */
					AND referee_player_id>0 /* filter multi-registered */
					AND player_relay_list.player_id = player_relay.player_id
					/* $where4registration_datetime */ /* disable for player_relay_list.player_id = player_relay.player_id */
					$where4first_deposit_datetime
			) as first_deposit_amount
			,( /* as first_deposit_datetime */
				SELECT first_deposit_datetime
				FROM player_relay
				WHERE deleted_at IS NULL
					AND agent_id=0 /* filter multi-registered */
					AND affiliate_id>0 /* filter multi-registered */
					AND referee_player_id>0 /* filter multi-registered */
					AND player_relay_list.player_id = player_relay.player_id
					/* $where4registration_datetime */ /* disable for player_relay_list.player_id = player_relay.player_id */
					$where4first_deposit_datetime
			) as first_deposit_datetime
			FROM player_relay as player_relay_list
			WHERE deleted_at IS NULL
				AND agent_id=0 /* filter multi-registered */
				AND affiliate_id>0 /* filter multi-registered */
				AND referee_player_id>0 /* filter multi-registered */
				$where4registration_datetime
				$where4first_deposit_datetime
		) as reporting
EOF;

		$table = $this->data_tables->clearBlankSpaceAfterHeredoc($table);

		$where = array();

		$theField4registration_datetime ='createdOn';
		$where[] = $this->getWhere4registration_datetime($input, $theField4registration_datetime);

		$values = array();
		$joins = array();
		$group_by = array();
		$having = array();
		$distinct = false;
		$external_order=[];
		$notDatatable = '';
		$countOnlyField = 'playerId';
		$innerJoins=[];
		$useIndex=[];

		$markName = 'list';
		$this->utils->markProfilerStart($markName); // list mark.
		// for count records contain "group by" and "having".
		$this->config->set_item('debug_data_table_sql', true);
		if( $totalOnly ) {
			$result = $this->data_tables->empty_data($request);
		}else{
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
		}

		$sql = $this->data_tables->last_query; // for trace
		$this->utils->markProfilerEndAndPrint($markName, $benchmarks[$markName]); // total mark.
		$sqls[$markName] = $sql; // list mark.

		if( ! $is_export ){
			$subTotalRowsIndex = count( $result['data'] );
			$result['data'][$subTotalRowsIndex][0] = '<span class="th subTotalCol">'.lang('Sub Total').'</span>';
			$result['data'][$subTotalRowsIndex][1] = '';
			$result['data'][$subTotalRowsIndex][2] = '';
			$result['data'][$subTotalRowsIndex][3] = '<span class="th subTotalCol">'.$this->data_tables->currencyFormatter($subtotals['firstDepositAmount']).'</span>';
		}

		// for Total, get orig SQL and sum(amount) from that.
		$this->data_tables->options['only_sql'] = true;
		// $request['start']
		$request['length'] = -1; // for ignore LIMIT.
		$result4sql = $this->data_tables->get_data($request // #1
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

		$this->data_tables->options['only_sql'] = false;
		$tmpSQL = $result4sql['data'];

		$summary = [];
		$markName = 'total2calcRows'; // total mark.
		$this->utils->markProfilerStart($markName); // total mark.
		$result4Total2calcRows = $readOnlyDB->query( $tmpSQL );
		$this->utils->markProfilerEndAndPrint($markName, $benchmarks[$markName]); // total mark.

		$total2calcRows = $this->getMultipleRowArray($result4Total2calcRows);
		if( ! empty($total2calcRows) ){ // for NULL
			$totalFirstDepositedAmount = 0;
			$totalFirstDepositPlayers = 0;
			$totalRegisteredPlayers = 0;

			foreach($total2calcRows as $indexNumber => $result4Total2calcRow){
				$totalFirstDepositedAmount += $result4Total2calcRow['firstDepositAmount'];

				if($result4Total2calcRow['firstDepositAmount'] > 0){
					$totalFirstDepositPlayers++;
				}
				$totalRegisteredPlayers++;
			}
			$summary[0]['totalRegisteredPlayers'] = $totalRegisteredPlayers;
			$summary[0]['totalFirstDepositedAmount'] = $totalFirstDepositedAmount;
			$summary[0]['totalFirstDepositPlayers'] = $totalFirstDepositPlayers;
			$counterHasFirstDeposit[0]['totalFirstDepositPlayers']= $totalFirstDepositPlayers;
		}else{
			$summary[0]['totalRegisteredPlayers'] = 0;
			$summary[0]['totalFirstDepositedAmount'] = 0;
			$summary[0]['totalFirstDepositPlayers']=0;
		}

		$conversionRate = 0;
		$summary[0]['totalFirstDepositPlayers'] = 0;
		$summary[0]['conversionRate']= 0;
		if( ! empty($summary[0]['totalRegisteredPlayers']) ){
			$conversionRate = $counterHasFirstDeposit[0]['totalFirstDepositPlayers'] / $summary[0]['totalRegisteredPlayers'];
			// append totalFirstDepositPlayers,
			$summary[0]['totalFirstDepositPlayers'] = $counterHasFirstDeposit[0]['totalFirstDepositPlayers'];
			$summary[0]['conversionRate'] = $conversionRate;
		}
		$result['conversionRate'] = $this->data_tables->percentageFormatter($conversionRate);

		$result['providerMethod'] = __FUNCTION__;
		$result['input'] = $input;
		$result['request'] = $request;
		$result['sqls'] = $sqls;
		$result['benchmarks'] = $benchmarks;
		$result['summaryByAll'] = $summary[0]; // for conversion_rate_report_summaryByAll().
		return $result;

	} // EOF conversion_rate_report_summaryByReferredAffiliate

	public function conversion_rate_report_summaryByReferredAgent($extra_search = false, $totalOnly = false) {
		$this->setCrrExecutionTime();

		$readOnlyDB = $this->getReadOnlyDB();
		$this->load->library('data_tables', array("DB" => $readOnlyDB));

		$request = $this->input->post();
		$input = $this->data_tables->extra_search($request);
		if( ! empty($extra_search) ){
			$input = $extra_search;
		}
		$is_export = ! empty($extra_search);

		// enable / disable first_deposit_date_from[to]
		$search_first_deposit_date_switch = 'off'; // default
		if( ! empty($input['search_first_deposit_date_switch']) ){
			$search_first_deposit_date_switch = $input['search_first_deposit_date_switch'];
		}
		if($search_first_deposit_date_switch == 'off'){ // ignore first_deposit_date_from and first_deposit_date_to
			unset($input['first_deposit_date_from']);
			unset($input['first_deposit_date_to']);
		}

		$this->data_tables->options['is_export'] = $is_export;
		// Default clause

		$sqls = []; // for trace SQL.

		$subtotals = array();
		$subtotals['firstDepositAmount'] = 0;

		/// The cols START
		$colIndex = 0;
		$columns = array();
		$columns[$colIndex]['dt'] = $colIndex;
		$columns[$colIndex]['alias'] = 'username';
		$columns[$colIndex]['select'] = 'username';
		$columns[$colIndex]['name'] = lang('Reffered Player Username');
		$columns[$colIndex]['formatter'] = function ($d, $row) {
			return $d;
		}; // EOF $columns[$colIndex]['formatter']
		$colIndex++;
		$columns[$colIndex]['dt'] = $colIndex;
		$columns[$colIndex]['alias'] = 'createdOn';
		$columns[$colIndex]['select'] = 'createdOn';
		$columns[$colIndex]['name'] = lang('Registration Time');
		// Format: yyyy-mm-dd HH:MM:SS
		$columns[$colIndex]['formatter'] = 'dateTimeFormatter';
		$colIndex++;
		$columns[$colIndex]['dt'] = $colIndex;
		$columns[$colIndex]['alias'] = 'firstDepositTime';
		// $columns[$colIndex]['select'] = '`transactions`.`created_at`'; // ver. transactions
		$columns[$colIndex]['select'] = 'first_deposit_datetime'; // ver. player_report_hourly
		$columns[$colIndex]['name'] = lang('First Deposit Time');
		// Format: yyyy-mm-dd HH:MM:SS
		$columns[$colIndex]['formatter'] = 'dateTimeFormatter'; // directly assign to function.
		$colIndex++;
		$columns[$colIndex]['dt'] = $colIndex;
		$columns[$colIndex]['alias'] = 'firstDepositAmount';
		// $columns[$colIndex]['select'] = '`transactions`.`amount`'; // ver. transactions
		$columns[$colIndex]['select'] = 'first_deposit_amount'; // ver. player_report_hourly
		$columns[$colIndex]['name'] = lang('First Deposit Amount');
		$columns[$colIndex]['formatter'] = function ($d, $row) use ( &$subtotals ){ // 1,234,567.00
			$subtotals['firstDepositAmount'] += $d; // for sub total.
			return $this->data_tables->currencyFormatter($d);
		}; // EOF $columns[$colIndex]['formatter']

		$colIndex++; // just for count player.

		$columns[$colIndex]['alias'] = 'playerId';
		$columns[$colIndex]['select'] = 'playerId'; // ver. player_report_hourly


		// PRH = player_report_hourly
		// RDT = registration_datetime
		$theFileName='player_report_hourly.registered_date';
		$where4PrhRd = $this->getWhere4registration_datetime($input, $theFileName);

		/// Patch for OGP-14393 Enhancement of Conversion Rate Report performance
		// Get the player who created on date range.
		$date_from_to = $this->convertInput2Date_from_to($input);
		$playerIdList = $this->getPlayerIdWhereCreatedOnRange($date_from_to['date_from'], $date_from_to['date_to']);
		// apply to player_report_hourly WHERE condition
		if( ! empty($playerIdList) ){
			// PRH = player_report_hourly
			$PRHplayerIdsCoditionStr = $this->getPlayerIdCoditionStr($playerIdList, 'player_report_hourly.player_id');
		}else{
			$PRHplayerIdsCoditionStr = 0;
		}

		$theField4first_deposit_datetime ='first_deposit_datetime';
		$where4first_deposit_datetime = $this->getWhere4first_deposit_datetime($input, $theField4first_deposit_datetime);
		if( ! empty($where4first_deposit_datetime) ){
			$where4first_deposit_datetime = ' AND '. $where4first_deposit_datetime;
		}

		/// OGP-14393 Enhancement of Conversion Rate Report performance
		$theField4registration_datetime ='created_on';
		$where4registration_datetime = $this->getWhere4registration_datetime($input, $theField4registration_datetime);
		if( ! empty($where4registration_datetime) ){
			$where4registration_datetime = ' AND '. $where4registration_datetime;
		}
		$table = <<<EOF
		( /* as reporting */
			SELECT player_id as playerId
			, username
			, created_on as createdOn
			, ( /* as first_deposit_amount */
				SELECT first_deposit_amount
				FROM player_relay
				WHERE deleted_at IS NULL
					AND affiliate_id=0 /* filter multi-registered */
					AND agent_id>0 /* filter multi-registered */
					AND referee_player_id>0 /* filter multi-registered */
					AND player_relay_list.player_id = player_relay.player_id
					/* $where4registration_datetime */ /* disable for player_relay_list.player_id = player_relay.player_id */
					$where4first_deposit_datetime
			) as first_deposit_amount
			,( /* as first_deposit_datetime */
				SELECT first_deposit_datetime
				FROM player_relay
				WHERE deleted_at IS NULL
					AND affiliate_id=0 /* filter multi-registered */
					AND agent_id>0 /* filter multi-registered */
					AND referee_player_id>0 /* filter multi-registered */
					AND player_relay_list.player_id = player_relay.player_id
					/* $where4registration_datetime */ /* disable for player_relay_list.player_id = player_relay.player_id */
					$where4first_deposit_datetime
			) as first_deposit_datetime
			FROM player_relay as player_relay_list
			WHERE deleted_at IS NULL
				AND affiliate_id=0 /* filter multi-registered */
				AND agent_id>0 /* filter multi-registered */
				AND referee_player_id>0 /* filter multi-registered */
				$where4registration_datetime
				$where4first_deposit_datetime
		) as reporting
EOF;

		$table = $this->data_tables->clearBlankSpaceAfterHeredoc($table);

		$where = array();

		$theField4registration_datetime ='createdOn';
		$where[] = $this->getWhere4registration_datetime($input, $theField4registration_datetime);

		$values = array();
		$joins = array();
		$group_by = array();
		$having = array();
		$distinct = false;
		$external_order=[];
		$notDatatable = '';
		$countOnlyField = 'playerId';
		$innerJoins=[];
		$useIndex=[];

		$markName = 'list';
		$this->utils->markProfilerStart($markName); // list mark.
		// for count records contain "group by" and "having".
		$this->config->set_item('debug_data_table_sql', true);
		if( $totalOnly ) {
			$result = $this->data_tables->empty_data($request);
		}else{
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
		}

		$sql = $this->data_tables->last_query; // for trace
		$this->utils->markProfilerEndAndPrint($markName, $benchmarks[$markName]); // total mark.
		$sqls[$markName] = $sql; // list mark.

		if( ! $is_export ){
			$subTotalRowsIndex = count( $result['data'] );
			$result['data'][$subTotalRowsIndex][0] = '<span class="th subTotalCol">'.lang('Sub Total').'</span>';
			$result['data'][$subTotalRowsIndex][1] = '';
			$result['data'][$subTotalRowsIndex][2] = '';
			$result['data'][$subTotalRowsIndex][3] = '<span class="th subTotalCol">'.$this->data_tables->currencyFormatter($subtotals['firstDepositAmount']).'</span>';
		}

		// for Total, get orig SQL and sum(amount) from that.
		$this->data_tables->options['only_sql'] = true;
		// $request['start']
		$request['length'] = -1; // for ignore LIMIT.
		$result4sql = $this->data_tables->get_data($request // #1
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

		$this->data_tables->options['only_sql'] = false;
		$tmpSQL = $result4sql['data'];

		$summary = [];
		$markName = 'total2calcRows'; // total mark.
		$this->utils->markProfilerStart($markName); // total mark.
		$result4Total2calcRows = $readOnlyDB->query( $tmpSQL );
		$this->utils->markProfilerEndAndPrint($markName, $benchmarks[$markName]); // total mark.

		$total2calcRows = $this->getMultipleRowArray($result4Total2calcRows);
		if( ! empty($total2calcRows) ){ // for NULL
			$totalFirstDepositedAmount = 0;
			$totalFirstDepositPlayers = 0;
			$totalRegisteredPlayers = 0;

			foreach($total2calcRows as $indexNumber => $result4Total2calcRow){
				$totalFirstDepositedAmount += $result4Total2calcRow['firstDepositAmount'];

				if($result4Total2calcRow['firstDepositAmount'] > 0){
					$totalFirstDepositPlayers++;
				}
				$totalRegisteredPlayers++;
			}
			$summary[0]['totalRegisteredPlayers'] = $totalRegisteredPlayers;
			$summary[0]['totalFirstDepositedAmount'] = $totalFirstDepositedAmount;
			$summary[0]['totalFirstDepositPlayers'] = $totalFirstDepositPlayers;
			$counterHasFirstDeposit[0]['totalFirstDepositPlayers']= $totalFirstDepositPlayers;
		}else{
			$summary[0]['totalRegisteredPlayers'] = 0;
			$summary[0]['totalFirstDepositedAmount'] = 0;
			$summary[0]['totalFirstDepositPlayers']=0;
		}

		$conversionRate = 0;
		$summary[0]['totalFirstDepositPlayers'] = 0;
		$summary[0]['conversionRate']= 0;
		if( ! empty($summary[0]['totalRegisteredPlayers']) ){
			$conversionRate = $counterHasFirstDeposit[0]['totalFirstDepositPlayers'] / $summary[0]['totalRegisteredPlayers'];
			// append totalFirstDepositPlayers,
			$summary[0]['totalFirstDepositPlayers'] = $counterHasFirstDeposit[0]['totalFirstDepositPlayers'];
			$summary[0]['conversionRate'] = $conversionRate;
		}
		$result['conversionRate'] = $this->data_tables->percentageFormatter($conversionRate);

		$result['providerMethod'] = __FUNCTION__;
		$result['input'] = $input;
		$result['request'] = $request;
		$result['sqls'] = $sqls;
		$result['benchmarks'] = $benchmarks;
		$result['summaryByAll'] = $summary[0]; // for conversion_rate_report_summaryByAll().
		return $result;

		// $sqls for trace,
	} // EOF conversion_rate_report_summaryByReferredAgent

	/**
	 * detail: get new added and total number of players
	 *
	 * @param string $type
	 * @param string $date
	 * @param array $selected_tag
	 *
	 * @return array
	 */
	public function get_new_and_total_players($type = 'YEAR', $date = null, $selected_tag = null) {
		$readOnlyDB = $this->getReadOnlyDB();

		$readOnlyDB->select("COUNT(player.playerId) AS total_players");
		$readOnlyDB->from('player');

        if(!empty($selected_tag)){
            $readOnlyDB->join('playertag', 'playertag.playerId = player.playerId', 'left');
            $readOnlyDB->where('(playertag.tagId NOT IN ('.implode(',', $selected_tag).") OR playertag.tagId is NULL)");
        }

		$readOnlyDB->where('deleted_at IS NULL');

		if ($type == 'DATE') {
			// SUM(player.createdOn BETWEEN '2022-05-03 00:00:00' AND '2022-05-03 23:59:59') as new_players
			$conditionFormat = "player.createdOn BETWEEN '%s' AND '%s'"; // 2 params, begin and end datetime
			$readOnlyDB->select_sum( sprintf($conditionFormat,  $date. ' 00:00:00', $date. ' 23:59:59'), 'new_players');

			if( date("Y-m-d", strtotime($date)) != date("Y-m-d", strtotime('now')) ){ // Not all, must limit the date in createdOn
				$readOnlyDB->where("player.createdOn <=", $date. ' 23:59:59');
			}
		} else if($type == 'YEAR_MONTH'){
            $_dt = $this->_ymDate2dt($date);
			$year = date("Y", $_dt->getTimestamp() );
			$month = date("m", $_dt->getTimestamp());
			$readOnlyDB->select_sum(sprintf("IF(MONTH(player.createdOn) = '%s' and YEAR(player.createdOn) = '%s', 1, 0)", $month, $year), 'new_players');
			$readOnlyDB->where("DATE(player.createdOn) <=", $this->utils->formatDateForMysql($_dt) );
		} else {
			$readOnlyDB->select_sum(sprintf("IF(EXTRACT(%s FROM player.createdOn) = '%s', 1, 0)", $type, $date), 'new_players');
			$readOnlyDB->where(sprintf("EXTRACT(%s FROM player.createdOn) <= '%s'", $type, $date));
		}

		$query = $readOnlyDB->get();
		return $query->row_array();
	}

	/**
	 * detail: get all new players
	 *
	 * @param string $type
	 * @param string $date
     * @param array $tags
     * @param string $dateFrom
     * @param string $dateTo
     * @param array $searchParams
	 *
	 * @return array
	 */
	public function get_new_players($type = 'YEAR', $date = null, $dateFrom = null, $dateTo = null, $tags = [], $searchParams = []) {
		$readOnlyDB = $this->getReadOnlyDB();

        // array of allowed columns to be searched under get_second_deposit() function under report_module_transaction trait
        $ALLOWED_NEW_MEMBERS_SEARCH_COLUMNS = [
            'affiliates.username'
        ];

		$this->utils->debug_log(__METHOD__,'tags', $tags);

		$readOnlyDB->select("player.playerId");
		$readOnlyDB->select("player.username");
		$readOnlyDB->select("player.createdOn");
		$readOnlyDB->select("player.affiliateId");
		$readOnlyDB->select("affiliates.username affiliate_username");
		$readOnlyDB->select("player_tags.player_tags");
		$readOnlyDB->distinct();
		$readOnlyDB->from('player');
		$readOnlyDB->join('affiliates','affiliates.affiliateId = player.affiliateId', 'LEFT');
		$readOnlyDB->join("(
			SELECT playertag.playerId, GROUP_CONCAT(CONCAT(tag.tagName, '||', tag.tagColor)) as player_tags
			from playertag
			left join tag on tag.tagId = playertag.tagId
			WHERE playertag.status =1
			GROUP BY playerId
		) player_tags", 'player_tags.playerId = player.playerId', 'left');
		$readOnlyDB->where('player.deleted_at IS NULL');

		if ($type == 'DATE') {
			if(!empty($dateFrom) && !empty($dateTo)){
				$readOnlyDB->where('DATE(player.createdOn) >=', $dateFrom);
				$readOnlyDB->where('DATE(player.createdOn) <=', $dateTo);
			}else{
				$readOnlyDB->where("DATE(player.createdOn)", $date);
			}
		} else {
			$readOnlyDB->where(sprintf("EXTRACT(%s FROM player.createdOn) = '%s'", $type, $date));
		}

        if(!empty($tags)) {
			$readOnlyDB->join('playertag', 'playertag.playerId = player.playerId', 'left');
			$readOnlyDB->where_in("COALESCE(playertag.tagId, 'notag')", $tags);
		}


        foreach($searchParams as $paramKey => $paramValue){
            if (in_array($paramKey, $ALLOWED_NEW_MEMBERS_SEARCH_COLUMNS)){
                $readOnlyDB->where($paramKey, $paramValue);
            }
        }

		$query = $readOnlyDB->get();
		$res_data = $query->result_array();

		$this->utils->debug_log(__METHOD__,'print sql',$readOnlyDB->last_query());
        $data = [];
		if (!empty($res_data)) {
			foreach ($res_data as $row) {
				$player_tags_html = "";

				if(!empty($row['player_tags'])) {

					$player_tags = explode(',', $row['player_tags']);

					foreach($player_tags as $pt) {
						$ptags = explode('||', $pt);
						$player_tags_html .= "<span class='tag label label-info' style='background-color: $ptags[1]'>$ptags[0]</span> ";
					}
				}

				$data[] = array(
					'playerId' => $row['playerId'],
					'username' => $row['username'],
					'createdOn' => $row['createdOn'],
					'affiliateId' => $row['affiliateId'],
					'affiliate_username' => $row['affiliate_username'],
					'player_tags' => $player_tags_html,
				);
			}
		}
		return $data;
	}

	/**
	 * detail: get total number of players
	 *
	 * @param string $type
	 * @param string $date
	 *
	 * @return array
	 */
	// public function get_total_players($type = 'YEAR', $date = null) {
	public function get_total_players($type = 'YEAR', $date = null, $dateFrom = null, $dateTo = null, $tags = [], $searchParams = []) {
		$readOnlyDB = $this->getReadOnlyDB();

		$readOnlyDB->select("player.playerId");
		$readOnlyDB->select("player.username");
		$readOnlyDB->select("player_tags.player_tags");
		$readOnlyDB->select("player.createdOn");
		$readOnlyDB->distinct();
		$readOnlyDB->from('player');
		$readOnlyDB->join("(
			SELECT playertag.playerId, GROUP_CONCAT(CONCAT(tag.tagName, '||', tag.tagColor)) as player_tags
			from playertag
			left join tag on tag.tagId = playertag.tagId
			WHERE playertag.status =1
			GROUP BY playerId
		) player_tags", 'player_tags.playerId = player.playerId', 'left');
		$readOnlyDB->where('player.deleted_at IS NULL');

		if ($type == 'DATE') {
			$readOnlyDB->where("DATE(player.createdOn) <=", $date);
		} else {
			$readOnlyDB->where(sprintf("EXTRACT(%s FROM player.createdOn) <= '%s'", $type, $date));
		}

        if(!empty($tags)) {
			$readOnlyDB->join('playertag', 'playertag.playerId = player.playerId', 'left');
			$readOnlyDB->where_in("COALESCE(playertag.tagId, 'notag')", $tags);
		}

		$query = $readOnlyDB->get();
		$res_data = $query->result_array();

        $data = [];
		if (!empty($res_data)) {
			foreach ($res_data as $row) {
				$player_tags_html = "";

				if(!empty($row['player_tags'])) {

					$player_tags = explode(',', $row['player_tags']);

					foreach($player_tags as $pt) {
						$ptags = explode('||', $pt);
						$player_tags_html .= "<span class='tag label label-info' style='background-color: $ptags[1]'>$ptags[0]</span> ";
					}
				}

				$data[] = array(
					'playerId' => $row['playerId'],
					'username' => $row['username'],
					'player_tags' => $player_tags_html,
					'createdOn' => $row['createdOn'],
				);
			}
		}
		return $data;
	}

	/**
	 * detail: get total deposit players
	 *
	 * @param string $type
	 * @param string $date
	 *
	 * @return array
	 */
	public function get_total_deposit_players($type = 'YEAR', $date = null) {
		$readOnlyDB = $this->getReadOnlyDB();
		$readOnlyDB->distinct();
		$readOnlyDB->select('player.playerId, player.username');
		$readOnlyDB->from('transactions');
		$readOnlyDB->join('player', 'transactions.to_id = player.playerId');
		$readOnlyDB->where('transactions.to_type', Transactions::PLAYER);
		$readOnlyDB->where('transactions.transaction_type', Transactions::DEPOSIT);
		$readOnlyDB->where('transactions.status', Transactions::APPROVED);
		if ($type == 'DATE') {
			$readOnlyDB->where('DATE(transactions.created_at) =', $date);
		} else {
			$readOnlyDB->where(sprintf("EXTRACT(%s FROM transactions.created_at) = '%s'", $type, $date));
		}

		$query = $readOnlyDB->get();
		return $query->result_array();
	}

    /**
     * detail: get total deposit players v2
     *
     * @param string $type
     * @param string $date
     *
     * @return array
     */
    public function get_total_deposit_players_2($type = 'YEAR', $date = null) {
        return $this->_get_total_deposit_players_2_lite($type, $date);

        $readOnlyDB = $this->getReadOnlyDB();
        $readOnlyDB->distinct();
        $insql = "";
        if ($type == 'DATE') {
            $insql = "AND DATE(t2.created_at) = '$date'";
        } else {
            $insql = "AND " . sprintf("EXTRACT(%s FROM t2.created_at) = '%s'", $type, $date);
        }
        $readOnlyDB->select('player.playerId, player.username, count(*) deposit_count, sum(transactions.amount) deposit_amount, (SELECT CASE WHEN SUM(t2.amount) IS NULL THEN 0 ELSE sum(t2.amount) END withdrawal_amount FROM transactions t2 WHERE t2.transaction_type = '. Transactions::WITHDRAWAL.' AND t2.status = '. Transactions::APPROVED.' AND t2.to_type = ' . Transactions::PLAYER . ' AND t2.to_id = player.playerId ' . $insql. ' ) withdrawal_amount');
        $readOnlyDB->from('transactions');
        $readOnlyDB->join('player', 'transactions.to_id = player.playerId');
        $readOnlyDB->where('transactions.to_type', Transactions::PLAYER);
        $readOnlyDB->where('transactions.transaction_type', Transactions::DEPOSIT);
        $readOnlyDB->where('transactions.status', Transactions::APPROVED);
        $readOnlyDB->group_by('player.playerId');
        if ($type == 'DATE') {
            $readOnlyDB->where('DATE(transactions.created_at) =', $date);
        } else {
            $readOnlyDB->where(sprintf("EXTRACT(%s FROM transactions.created_at) = '%s'", $type, $date));
        }

        //$this->utils->debug_log('get_total_deposit_players_2 ------>', $readOnlyDB->_compile_select());
        $query = $readOnlyDB->get();

        $result = array();
        foreach($query->result_array() as $key=>$row) {
            $row['deposit_amount'] = round($row['deposit_amount'], 2);
            $row['withdrawal_amount'] = round($row['withdrawal_amount'], 2);
            $row['profit_amount'] = $row['deposit_amount'] - $row['withdrawal_amount'];
            $result[] = $row;
        }
        return $result;
    }

    /**
     * detail: get total deposit players v2 lite
     * Patch for performance issue
     * @param string $type
     * @param string $date
     *
     * @return array
     */
    public function _get_total_deposit_players_2_lite($type = 'YEAR', $date = null) {
        $readOnlyDB = $this->getReadOnlyDB();


        $to_type_player = Transactions::PLAYER;
        $status_approved = Transactions::APPROVED;
        $transaction_type_deposit = Transactions::DEPOSIT;
        $transaction_type_withdrawal = Transactions::WITHDRAWAL;

        $insql = "";
        if ($type == 'DATE') {
            // $insql = "AND DATE(transactions.created_at) = '$date'";
            $insql = "AND transactions.trans_date = '$date'";
        } elseif($type == 'YEAR_MONTH'){
			// $insql = "AND " . sprintf("EXTRACT(%s FROM transactions.created_at) = '%s'", $type, $date);
            $insql = "AND transactions.trans_year_month = '$date'";
        } else {
			// $insql = "AND " . sprintf("EXTRACT(%s FROM transactions.created_at) = '%s'", $type, $date);
			$insql = "AND transactions.trans_year = '$date'";
		}

        $sql_formater = <<<EOF
SELECT playerid
, username
, sum( deposit_count) as deposit_count
, round(sum(deposit_amount), 2) as deposit_amount
, round(sum(withdrawal_amount), 2) as withdrawal_amount
, round(sum(deposit_amount) - sum(withdrawal_amount), 2) as profit_amount
FROM (
    SELECT `player`.`playerid`
            , `player`.`username`
            , IF((`transactions`.`transaction_type` = $transaction_type_deposit && transactions.amount > 0), 1, 0) AS deposit_count
            , IF(`transactions`.`transaction_type` = $transaction_type_deposit, transactions.amount, 0) AS deposit_amount
            , IF(`transactions`.`transaction_type` = $transaction_type_withdrawal, transactions.amount, 0) AS withdrawal_amount
    FROM (`transactions`)
    JOIN `player` ON transactions.to_id = player.playerid
    WHERE `transactions`.`to_type` = $to_type_player
    AND `transactions`.`status` = $status_approved
    $insql
) as subtotal_list
GROUP BY playerid HAVING sum( deposit_count) > 0;
;
EOF;
// $this->utils->debug_log('_get_total_deposit_players_2_lite ------>', $sql_formater);
        return $this->runRawSelectSQLArray($sql_formater, []);

// -- -------
// -- Monthly for 202211
// SELECT playerid
// , username
// , sum( deposit_count) as total_deposit_count
// , sum(deposit_amount) as total_deposit_amount
// , sum(withdrawal_amount) as total_withdrawal_amount
// , round(sum(deposit_amount) - sum(withdrawal_amount), 2) as deposit_withdrawal_diff_amount
// FROM (
// SELECT `player`.`playerid`,
//                 `player`.`username`,
//                 IF((`transactions`.`transaction_type` = 1 && transactions.amount > 0), 1, 0) AS deposit_count,
//                 IF(`transactions`.`transaction_type` = 1, transactions.amount, 0) AS deposit_amount,
//                 IF(`transactions`.`transaction_type` = 2, transactions.amount, 0) AS withdrawal_amount
// FROM            (`transactions`)
// JOIN            `player` ON transactions.to_id = player.playerid
// WHERE           `transactions`.`to_type` = 2
// AND             `transactions`.`status` = 1
// AND             extract(year_month FROM transactions.created_at) = '202211'
// ) as subtotal_list
// WHERE username in ('0emii77','0ke777', '123raisya', 'dewojoyo', 'ghembunk17')
// GROUP BY playerid
// ;

    } // EOF _get_total_deposit_players_2_lite

	/**
	 * detail: get the first and second deposit record
	 *
	 * @param string $type
	 * @param string $date
	 * @param array $selected_tag
	 * @return array
	 */
	public function get_first_and_second_deposit($type = 'YEAR', $date = null, $selected_tag = null) {
		$readOnlyDB = $this->getReadOnlyDB();

		// $date_str = $type == 'DATE' ? "trans_date" : "EXTRACT({$type} FROM created_at)";
		$date_str = "trans_year";

		if($type == 'DATE') {
			$date_str = "trans_date";
		}
		else if($type == 'YEAR_MONTH') {
			$date_str = "trans_year_month";
		}

        $playerTagJoin = '';
        $playerTagWhere = '';
        if(!empty($selected_tag)){
            $playerTagJoin = 'LEFT JOIN playertag ON playertag.playerId = player.playerId';
            $playerTagWhere = 'AND (playertag.tagId NOT IN ('.implode(',', $selected_tag).') OR playertag.tagId is NULL)';
        }

		$sql = <<<EOD
SELECT
	to_id,
    COUNT(DISTINCT common_date) number_of_dates,
    SUM(number_of_deposits) number_of_deposits,
	MIN(CONCAT(common_date,'_',number_of_deposits)) min_deposit_date_count,
	MAX(CONCAT(common_date,'_',number_of_deposits)) max_deposit_date_count
FROM
    (SELECT
        to_id,
		{$date_str} common_date,
        COUNT(id) number_of_deposits
    FROM
        transactions
    JOIN
        player ON player.playerId = transactions.to_id AND player.deleted_at IS NULL
        {$playerTagJoin}
    WHERE
        transaction_type = 1 AND
        to_type = 2 AND
        transactions.status = 1 AND
        {$date_str} <= '{$date}'
        {$playerTagWhere}
    GROUP BY
		to_id,
        {$date_str}
    ) a
GROUP BY
	to_id
HAVING
	number_of_dates <= 2 OR number_of_deposits <= 2
EOD;

		$query = $readOnlyDB->query($sql);
		$result = $query->result_array();
		// echo $readOnlyDB->last_query();exit();
		$data = array(
			'first_deposit' => 0,
			'second_deposit' => 0,
		);
		if(!empty($result)){
			foreach ($result as $row) {
				$min_deposit_date_count = explode('_', $row['min_deposit_date_count']);
				$max_deposit_date_count = explode('_', $row['max_deposit_date_count']);
				$min_deposit_date = $min_deposit_date_count[0];
				$min_deposit_count = $min_deposit_date_count[1];
				$max_deposit_date = $max_deposit_date_count[0];
				$max_deposit_count = $max_deposit_date_count[1];

				if ($date == $min_deposit_date) {

					$data['first_deposit']++;

					if ($row['number_of_deposits'] > 1) {
						$data['second_deposit']++;
					}
				} else if ($max_deposit_date == $date && $min_deposit_count == 1) {
					$data['second_deposit']++;
				}
			}
		}
		return $data;
	}

	/**
	 * detail: get first deposit record
	 *
	 * @param string $type
	 * @param string $date
     * @param array $tags
     * @param string $dateFrom YYYY-mm-dd, ex: 2022-12-31
     * @param string $dateTo YYYY-mm-dd, ex: 2022-12-31
     * @param array $searchParams
	 *
	 * @return array
	 */
	public function get_first_deposit($type = 'YEAR', $date = null, $tags = [], $dateFrom = null, $dateTo = null, $searchParams = []) {
		$readOnlyDB = $this->getReadOnlyDB();
		$this->load->library('data_tables', array("DB" => $readOnlyDB));
		$this->data_tables->options['is_export'] = false;

        // array of allowed columns to be searched under get_first_deposit() function under report_module_transaction trait
        $ALLOWED_FIRST_DEPOSIT_SEARCH_COLUMNS = [
            'affiliates.username'
        ];

		if ($type == 'DATE') {
			$date_str = "DATE(created_at)";
			if(!empty($dateFrom) && !empty($dateTo)){
				$where_date_str = "$date_str <= '{$dateTo}'";
				$group_by_str = "to_id";
				$have_date_str = "(MIN(common_date) >= '{$dateFrom}') AND (COUNT(DISTINCT common_date) <= 2 OR SUM(number_of_deposits) <= 2)";
			}else{
				$where_date_str = "$date_str <= '{$date}'";
				$group_by_str = "to_id, {$date_str}";
				$have_date_str = "MIN(common_date) = '{$date}' AND (COUNT(DISTINCT common_date) <= 2 OR SUM(number_of_deposits) <= 2)";
			}
		} else {
			$date_str = "EXTRACT({$type} FROM created_at)";
			$where_date_str = "$date_str <= '{$date}'";
			$group_by_str = "to_id, {$date_str}";
			$have_date_str = "MIN(common_date) = '{$date}' AND (COUNT(DISTINCT common_date) <= 2 OR SUM(number_of_deposits) <= 2)";
		}

		$tags_left_join = "";
		$and_where = "";

		if(!empty($tags)) {

			$tags_left_join = "LEFT JOIN playertag ON player.playerId = playertag.playerId";

			$tags_string = implode(',',$tags);

			$tags_string = str_replace("notag","'notag'",$tags_string);

			$and_where = "and COALESCE(playertag.tagId, 'notag') in ($tags_string)";


		}

        foreach($searchParams as $paramKey => $paramValue){
            if (in_array($paramKey, $ALLOWED_FIRST_DEPOSIT_SEARCH_COLUMNS)){
                $and_where .= " AND " . $paramKey . " = '" . $paramValue . "'";
            }
        }

		$sql = <<<EOD
SELECT
	to_id playerId,
	player.username,
	a.first_deposit,
	affiliates.username AS aff_username,
    player_tags.player_tags
FROM
    (SELECT
        to_id,
		{$date_str} common_date,
		MIN(CONCAT(created_at,'_',id,'_',amount)) first_deposit,
        COUNT(id) number_of_deposits

    FROM
        transactions
    WHERE
        transaction_type = 1 AND
        to_type = 2 AND
        status = 1 AND /* Transactions::APPROVED */
        {$where_date_str}
    GROUP BY
        {$group_by_str}
    ) a
LEFT JOIN
	player ON player.playerId = a.to_id
LEFT JOIN
(
	SELECT playertag.playerId, GROUP_CONCAT(CONCAT(tag.tagName, '||', tag.tagColor)) as player_tags
	from playertag
	left join tag on tag.tagId = playertag.tagId
	WHERE playertag.status =1
	GROUP BY playerId
) player_tags on player_tags.playerId = player.playerId
LEFT JOIN
	affiliates ON player.affiliateId = affiliates.affiliateId
{$tags_left_join}
WHERE
	player.deleted_at IS NULL
	{$and_where}
GROUP BY
	to_id
HAVING
	{$have_date_str}
EOD;

		$sum = 0;
		$data = array();
		$query = $readOnlyDB->query($sql);
		$result = $query->result_array();
        // $this->utils->debug_log('OGP-30539.6143.last_query:',  $readOnlyDB->last_query());
		// echo $readOnlyDB->last_query();exit();
		if(!empty($result)){
			foreach ($result as $row) {
				$first_deposit = explode('_', $row['first_deposit']);
				$player_tags_html = "";

				if(!empty($row['player_tags'])) {

					$player_tags = explode(',', $row['player_tags']);

					foreach($player_tags as $pt) {
						$ptags = explode('||', $pt);

						$player_tags_html .= "<span class='tag label label-info' style='background-color: $ptags[1]'>$ptags[0]</span> ";
					}
				}

				$data[] = array(
					'playerId' => $row['playerId'],
					'username' => $row['username'],
					'aff_username' => $row['aff_username'],
					'date' => $first_deposit[0],
					'amount' => $this->data_tables->currencyFormatter($first_deposit[2]),
					'player_tag' => $player_tags_html,
				);
				$sum += $first_deposit[2];
			}
		}
		$data['totalAmount'] = $sum;

		return $data;
	}

    /**
     * get first deposit with player relay
     *
     * @param string $type The enumerated values: YEAR, YEAR_MONTH and DATE.
     * @param string $date The input formats , null, YYYY-mm-dd, YYYYmm, YYYY.
     * @param array $tags  The PK. of data-table,"tag" list.
     * @param null|string $dateFrom For search with the date range.
     * @param null|string $dateTo For search with the date range.
     * @param array $searchParams So far, providing the param, "affiliate_username". ex: ["affiliate_username": "testaff"]
     * @return array $data
     */
    public function get_first_deposit_with_player_relay($type = 'YEAR', $date = null, $tags = [], $dateFrom = null, $dateTo = null, $searchParams = []) {
		$readOnlyDB = $this->getReadOnlyDB();
		$this->load->library('data_tables', array("DB" => $readOnlyDB));
		$this->data_tables->options['is_export'] = false;
        $this->load->model(array('player_relay'));


        // array of allowed columns to be searched under get_first_deposit() function under report_module_transaction trait
        $ALLOWED_FIRST_DEPOSIT_SEARCH_COLUMNS = [
            'affiliates.username'
        ];

        $where_excluded_player_id = '';
        // $condition_mode = null;
		if ($type == 'DATE') {
			$date_str = "DATE(created_at)";
			if(!empty($dateFrom) && !empty($dateTo)){
				$where_date_str = "$date_str <= '{$dateTo}'";
				// $group_by_str = "to_id";
				$have_date_str = "(MIN(common_date) >= '{$dateFrom}') AND (COUNT(DISTINCT common_date) <= 2 OR SUM(number_of_deposits) <= 2)";
                // $condition_mode = 'used_dateTo_DATE';
			}else{
				$where_date_str = "$date_str <= '{$date}'";
				// $group_by_str = "to_id, {$date_str}";
				$have_date_str = "MIN(common_date) = '{$date}' AND (COUNT(DISTINCT common_date) <= 2 OR SUM(number_of_deposits) <= 2)";
                // $condition_mode = 'used_date_DATE';
			}
		} else {
			$date_str = "EXTRACT({$type} FROM created_at)";
			$where_date_str = "$date_str <= '{$date}'";
			// $group_by_str = "to_id, {$date_str}";
			$have_date_str = "MIN(common_date) = '{$date}' AND (COUNT(DISTINCT common_date) <= 2 OR SUM(number_of_deposits) <= 2)";
            // $condition_mode = 'used_OTHERS';
		}
        $have_date_str = ''; // ignored

        if(!empty($dateFrom) && !empty($dateTo)){
            $dateFrom_dt = new DateTime(date('Y-m-d', strtotime($dateFrom)));
            $dateTo_dt = new DateTime(date('Y-m-d', strtotime($dateTo)));
            list($dateFrom_begin_date, $dateFrom_end_date) = $this->utils->convertDayToStartEnd( $dateFrom_dt->format('Ymd') );
            list($dateTo_begin_date, $dateTo_end_date) = $this->utils->convertDayToStartEnd( $dateTo_dt->format('Ymd') );
            $begin_date = $dateFrom_begin_date;
            $end_date = $dateTo_end_date;
        }else{
            switch($type){
                case 'DATE':
                    $_dt = new DateTime(date('Y-m-d', strtotime($date)));
                    list($begin_date, $end_date) = $this->utils->convertDayToStartEnd( $_dt->format('Ymd') );
                    break;
                case 'YEAR':
                    list($begin_date, $end_date) = $this->utils->convertYearToStartEnd( $date );
                    break;
                case 'YEAR_MONTH':
                    list($begin_date, $end_date) = $this->utils->convertMonthToStartEnd( $date );
                    break;
            }
        }
        $where_date_str = sprintf('player_relay.first_deposit_datetime BETWEEN "%s" and "%s"', $begin_date, $end_date);
        // $this->db->where(sprintf('player_relay.second_deposit_datetime BETWEEN "%s" and "%s"', $begin_date, $end_date), null, false );

		$tags_left_join = "";
		$and_where = "";

		if(!empty($tags)) {

			$tags_left_join = "LEFT JOIN playertag ON player.playerId = playertag.playerId";

			$tags_string = implode(',',$tags);

			$tags_string = str_replace("notag","'notag'",$tags_string);

			$and_where = "and COALESCE(playertag.tagId, 'notag') in ($tags_string)";


		}

        foreach($searchParams as $paramKey => $paramValue){
            if (in_array($paramKey, $ALLOWED_FIRST_DEPOSIT_SEARCH_COLUMNS)){
                $and_where .= " AND " . $paramKey . " = '" . $paramValue . "'";
            }
        }

        $transaction_type_in_deposit =Transactions::DEPOSIT;
        $to_type_in_player = Transactions::PLAYER;
        $status_in_approved = Transactions::APPROVED;
        $main_sql = <<<EOD
        SELECT player_id as to_id
        /* -- {$date_str} common_date, -- Not referenced in used player_relay */
        , CONCAT_WS('_', player_relay.first_deposit_datetime
        , IFNULL(
            (
            SELECT transactions.id
            FROM transactions
            WHERE to_id = player_relay.player_id
            AND to_type = {$to_type_in_player}
            AND transaction_type = {$transaction_type_in_deposit}
            AND transactions.status = {$status_in_approved}
            AND transactions.created_at = player_relay.first_deposit_datetime
        ), 0), player_relay.first_deposit_amount) first_deposit
        FROM player_relay
        WHERE
        {$where_date_str}
EOD;



		$sql = <<<EOD
SELECT
	to_id playerId,
	player.username,
	a.first_deposit,
	affiliates.username AS aff_username,
    player_tags.player_tags
FROM
    ( {$main_sql} ) a
LEFT JOIN
	player ON player.playerId = a.to_id
LEFT JOIN
(
	SELECT playertag.playerId, GROUP_CONCAT(CONCAT(tag.tagName, '||', tag.tagColor)) as player_tags
	from playertag
	left join tag on tag.tagId = playertag.tagId
	WHERE playertag.status =1
	GROUP BY playerId
) player_tags on player_tags.playerId = player.playerId
LEFT JOIN
	affiliates ON player.affiliateId = affiliates.affiliateId
{$tags_left_join}
WHERE
	player.deleted_at IS NULL
	{$and_where}

EOD;

		$sum = 0;
		$data = array();
		$query = $readOnlyDB->query($sql);
		$result = $query->result_array();
        // $this->utils->debug_log('OGP-30539.6402.last_query:',  $readOnlyDB->last_query());
		// echo $readOnlyDB->last_query();exit();
		if(!empty($result)){
			foreach ($result as $row) {
				$first_deposit = explode('_', $row['first_deposit']);
				$player_tags_html = "";

				if(!empty($row['player_tags'])) {

					$player_tags = explode(',', $row['player_tags']);

					foreach($player_tags as $pt) {
						$ptags = explode('||', $pt);

						$player_tags_html .= "<span class='tag label label-info' style='background-color: $ptags[1]'>$ptags[0]</span> ";
					}
				}

				$data[] = array(
					'playerId' => $row['playerId'],
					'username' => $row['username'],
					'aff_username' => $row['aff_username'],
					'date' => $first_deposit[0],
					'amount' => $this->data_tables->currencyFormatter($first_deposit[2]),
					'player_tag' => $player_tags_html,
				);
				$sum += $first_deposit[2];
			}
		}
		$data['totalAmount'] = $sum;

		return $data;
	}

	/**
	 * detail: get the second deposit record
	 *
	 * @param string $type
	 * @param string $date
     * @param array $tags
     * @param string $dateFrom
     * @param string $dateTo
     * @param array $searchParams
	 *
	 * @return array
	 */
	public function get_second_deposit($type = 'YEAR', $date = null, $tags = [], $dateFrom = null, $dateTo = null, $searchParams = []) {


		$readOnlyDB = $this->getReadOnlyDB();
		$sum = 0;
		$date_str = $type == 'DATE' ? "DATE(%s)" : "EXTRACT({$type} FROM %s)";

        // array of allowed columns to be searched under get_second_deposit() function under report_module_transaction trait
        $ALLOWED_SECOND_DEPOSIT_SEARCH_COLUMNS = [
            'affiliates.username'
        ];

		$this->db->select('player.playerId');
		$this->db->select('player.username');
		$this->db->select('affiliates.username AS aff_username');
		$this->db->select("SUBSTRING_INDEX(SUBSTRING_INDEX(group_concat(CONCAT_WS('_',transactions.created_at,transactions.id,transactions.amount) order by transactions.created_at asc, transactions.id asc separator ','),',',2),',', -1) second_deposit", false);
		$this->db->select('player_tags.player_tags');
		$this->db->from('transactions');
		$this->db->join('player', 'player.playerId = transactions.to_id');

		$this->db->join('affiliates', 'player.affiliateId = affiliates.affiliateId', 'left');
		$this->db->join("(
			SELECT playertag.playerId, GROUP_CONCAT(CONCAT(tag.tagName, '||', tag.tagColor)) as player_tags
			from playertag
			left join tag on tag.tagId = playertag.tagId
			WHERE playertag.status =1
			GROUP BY playerId
		) player_tags", 'player_tags.playerId = player.playerId', 'left');
		$this->db->where('transactions.transaction_type', Transactions::DEPOSIT);
		$this->db->where('transactions.to_type', Transactions::PLAYER);
		$this->db->where('transactions.status', Transactions::APPROVED);
		// $this->db->where(sprintf("{$date_str} <=", 'transactions.created_at'), $date);

        if(!empty($dateFrom) && !empty($dateTo)){
			$this->db->where(sprintf("{$date_str} <=", 'transactions.created_at'), $dateTo);
		}else{
			$this->db->where(sprintf("{$date_str} <=", 'transactions.created_at'), $date);
		}

		$this->db->where('player.deleted_at IS NULL');
		$this->db->group_by('transactions.to_id');
		$this->db->having('COUNT(transactions.id) >=', 2);
		if(!empty($dateFrom) && !empty($dateTo)){
			$this->db->having(sprintf("$date_str >=", 'second_deposit'), $dateFrom);
		}else{
			$this->db->having(sprintf("$date_str =", 'second_deposit'), $date);
		}

		if(!empty($tags)) {
			$this->db->join('playertag', 'playertag.playerId = player.playerId', 'left');

			$this->db->where_in("COALESCE(playertag.tagId, 'notag')", $tags);

		}

        foreach($searchParams as $paramKey => $paramValue){
            if (in_array($paramKey, $ALLOWED_SECOND_DEPOSIT_SEARCH_COLUMNS)){
                $this->db->where($paramKey, $paramValue);
            }
        }

		$query = $this->db->get();

		$result = $query->result_array();
        // $last_query = $this->db->last_query();
        // $this->utils->debug_log('OGP-30539.6263.last_query:', $last_query);
		foreach ($result as $row) {
			$second_deposit = explode('_', $row['second_deposit']);

			$player_tags_html = "";

			if(!empty($row['player_tags'])) {

				$player_tags = explode(',', $row['player_tags']);

				foreach($player_tags as $pt) {
					$ptags = explode('||', $pt);

					$player_tags_html .= "<span class='tag label label-info' style='background-color: $ptags[1]'>$ptags[0]</span> ";
				}
			}

			$data[] = array(
				'playerId' => $row['playerId'],
				'username' => $row['username'],
				'aff_username' => $row['aff_username'],
				'date' => $second_deposit[0],
				'amount' => $this->utils->formatCurrencyNoSym($second_deposit[2]),
				'transactionId' => $second_deposit[1],
				'player_tag' => $player_tags_html,
			);
			$sum += $second_deposit[2];
		}
		$data['totalAmount'] = $sum;
		return $data;
	}

    /**
     * get second deposit with player relay
     *
     * @param string $type The enumerated values: YEAR, YEAR_MONTH and DATE
     * @param string $date The input formats , null, YYYY-mm-dd, YYYYmm, YYYY.
     * @param array $tags The PK. of data-table,"tag" list.
     * @param null|string $dateFrom For search with the date range.
     * @param null|string $dateTo For search with the date range.
     * @param array $searchParams So far, providing the param, "affiliate_username". ex: ["affiliate_username": "testaff"]
     * @return array $data
     */
    public function get_second_deposit_with_player_relay($type = 'YEAR', $date = null, $tags = [], $dateFrom = null, $dateTo = null, $searchParams = []) {

        $this->load->model(array('player_relay', 'operatorglobalsettings'));
        $cronjob_sync_exists_player_in_player_relay = $this->operatorglobalsettings->getSettingBooleanValue('cronjob_sync_exists_player_in_player_relay');
        $cronjob_sync_newplayer_into_player_relay = $this->operatorglobalsettings->getSettingBooleanValue('cronjob_sync_newplayer_into_player_relay');
        $do_optimize_with_player_relay = false; // default
        $player_relayTableStatus = $this->player_relay->showTableStatus('player_relay');
        if( $player_relayTableStatus['Rows'] > 0
            && $cronjob_sync_exists_player_in_player_relay
            && $cronjob_sync_newplayer_into_player_relay
        ){
            $do_optimize_with_player_relay = true;
        }

		$readOnlyDB = $this->getReadOnlyDB();
		$sum = 0;
		$date_str = $type == 'DATE' ? "DATE(%s)" : "EXTRACT({$type} FROM %s)";

        // array of allowed columns to be searched under get_second_deposit() function under report_module_transaction trait
        $ALLOWED_SECOND_DEPOSIT_SEARCH_COLUMNS = [
            'affiliates.username'
        ];
        $transaction_type_in_deposit =Transactions::DEPOSIT;
        $to_type_in_player = Transactions::PLAYER;
        $status_in_approved = Transactions::APPROVED;
		$this->db->select('player.playerId');
		$this->db->select('player.username');
		$this->db->select('affiliates.username AS aff_username');
        $this->db->select("CONCAT_WS('_', player_relay.second_deposit_datetime, (
            SELECT transactions.id
            FROM transactions
            WHERE to_id = player.playerId
            AND to_type = {$to_type_in_player}
            AND transaction_type = {$transaction_type_in_deposit}
            AND transactions.status = {$status_in_approved}
            AND transactions.created_at = player_relay.second_deposit_datetime
        ), player_relay.second_deposit_amount ) as second_deposit" , false);
		// $this->db->select("SUBSTRING_INDEX(SUBSTRING_INDEX(group_concat(CONCAT_WS('_',transactions.created_at,transactions.id,transactions.amount) order by transactions.created_at asc, transactions.id asc separator ','),',',2),',', -1) second_deposit", false);
        // SUBSTRING_INDEX(SUBSTRING_INDEX(
        //                         group_concat(
        //                                         CONCAT_WS( '_', transactions.created_at, transactions.id, transactions.amount )
        //                                         order by transactions.created_at asc , transactions.id asc separator ',' )
        //                         ,',',2)
        //                 ,',', -1) second_deposit
		$this->db->select('player_tags.player_tags');
		// $this->db->from('transactions');
		// $this->db->join('player', 'player.playerId = transactions.to_id');
        $this->db->from('player_relay');
        $this->db->join('player', 'player.playerId = player_relay.player_id');

		$this->db->join('affiliates', 'player.affiliateId = affiliates.affiliateId', 'left');
		$this->db->join("(
			SELECT playertag.playerId, GROUP_CONCAT(CONCAT(tag.tagName, '||', tag.tagColor)) as player_tags
			from playertag
			left join tag on tag.tagId = playertag.tagId
			WHERE playertag.status =1
			GROUP BY playerId
		) player_tags", 'player_tags.playerId = player.playerId', 'left');
		// $this->db->where('transactions.transaction_type', Transactions::DEPOSIT);
		// $this->db->where('transactions.to_type', Transactions::PLAYER);
		// $this->db->where('transactions.status', Transactions::APPROVED);
		// $this->db->where(sprintf("{$date_str} <=", 'transactions.created_at'), $date);

        // if(!empty($dateFrom) && !empty($dateTo)){
        //     $this->db->where(sprintf("{$date_str} <=", 'transactions.created_at'), $dateTo);
		// }else{
		// 	$this->db->where(sprintf("{$date_str} <=", 'transactions.created_at'), $date);
		// }
        /// for $begin_date, $end_date
        if(!empty($dateFrom) && !empty($dateTo)){
            $_dt = new DateTime(date('Y-m-d', strtotime($dateTo)));
            $dateFrom_dt = new DateTime(date('Y-m-d', strtotime($dateFrom)));
            $dateTo_dt = new DateTime(date('Y-m-d', strtotime($dateTo)));
            list($dateFrom_begin_date, $dateFrom_end_date) = $this->utils->convertDayToStartEnd( $dateFrom_dt->format('Ymd') );
            list($dateTo_begin_date, $dateTo_end_date) = $this->utils->convertDayToStartEnd( $dateTo_dt->format('Ymd') );
            $begin_date = $dateFrom_begin_date;
            $end_date = $dateTo_end_date;
        }else{
            switch($type){
                case 'DATE':
                    $_dt = new DateTime(date('Y-m-d', strtotime($date)));
                    list($begin_date, $end_date) = $this->utils->convertDayToStartEnd( $_dt->format('Ymd') );
                    break;
                case 'YEAR':
                    list($begin_date, $end_date) = $this->utils->convertYearToStartEnd( $date );
                    break;
                case 'YEAR_MONTH':
                    list($begin_date, $end_date) = $this->utils->convertMonthToStartEnd( $date );
                    break;
            }
        }
        $this->db->where(sprintf('player_relay.second_deposit_datetime BETWEEN "%s" and "%s"', $begin_date, $end_date), null, false );

		$this->db->where('player.deleted_at IS NULL');
        // $this->db->where('player_relay.deleted_at IS NULL');
		// $this->db->group_by('transactions.to_id');
		// $this->db->having('COUNT(transactions.id) >=', 2);
		if(!empty($dateFrom) && !empty($dateTo)){
			$this->db->having(sprintf("$date_str >=", 'second_deposit'), $dateFrom);
		}else{
			$this->db->having(sprintf("$date_str =", 'second_deposit'), $date);
		}

		if(!empty($tags)) {
			$this->db->join('playertag', 'playertag.playerId = player.playerId', 'left');

			$this->db->where_in("COALESCE(playertag.tagId, 'notag')", $tags);

		}

        foreach($searchParams as $paramKey => $paramValue){
            if (in_array($paramKey, $ALLOWED_SECOND_DEPOSIT_SEARCH_COLUMNS)){
                $this->db->where($paramKey, $paramValue);
            }
        }

		$query = $this->db->get();

		$result = $query->result_array();
        // $last_query = $this->db->last_query();
        // $this->utils->debug_log('OGP-30539.6421.last_query:', $last_query);
		foreach ($result as $row) {
			$second_deposit = explode('_', $row['second_deposit']);

			$player_tags_html = "";

			if(!empty($row['player_tags'])) {

				$player_tags = explode(',', $row['player_tags']);

				foreach($player_tags as $pt) {
					$ptags = explode('||', $pt);

					$player_tags_html .= "<span class='tag label label-info' style='background-color: $ptags[1]'>$ptags[0]</span> ";
				}
			}

			$data[] = array(
				'playerId' => $row['playerId'],
				'username' => $row['username'],
				'aff_username' => $row['aff_username'],
				'date' => $second_deposit[0],
				'amount' => $this->utils->formatCurrencyNoSym($second_deposit[2]),
				'transactionId' => $second_deposit[1],
				'player_tag' => $player_tags_html,
			);
			$sum += $second_deposit[2];
		}
		$data['totalAmount'] = $sum;
		return $data;
	} // EOF get_second_deposit_with_player_relay

	/**
	 * detail: get certain payments report
	 *
	 * @param array $param
	 *
	 * @return array
	 */
	public function getPaymentsReport($params) {
		$readOnlyDB = $this->getReadOnlyDB();

		$sql = <<<EOD
SELECT * FROM
(SELECT
	player.username `username`,
	CONCAT(player.groupName, ' ', player.levelName) `member_level`,
	playerlevel.playerGroupId `playerGroupId`,
	'withdrawal' as `transaction`,
	walletaccount.dwStatus as `status`,
	walletaccount.amount as `amount`,
	walletaccount.dwMethod as `payment_method`,
	adminusers.username as `processed_by`,
	walletaccount.processDatetime as `process_time`,
	walletaccount.notes as `notes`
FROM
	walletaccount
LEFT JOIN
	adminusers ON adminusers.userId = walletaccount.processedBy
LEFT JOIN
	playeraccount ON playeraccount.playerAccountId = walletaccount.playerAccountId
LEFT JOIN
	player ON player.playerId = playeraccount.playerId
LEFT JOIN
	playerlevel ON playerlevel.playerId = player.playerId

UNION

SELECT
	player.username `username`,
	CONCAT(player.groupName, ' ', player.levelName) `member_level`,
	playerlevel.playerGroupId `playerGroupId`,
	'deposit' as `transaction`,
	sale_orders.status as `status`,
	sale_orders.amount as `amount`,
	payment_account.flag as `payment_method`,
	adminusers.username as `processed_by`,
	sale_orders.process_time as `process_time`,
	sale_orders.notes as `notes`
FROM
	sale_orders
LEFT JOIN
	player ON player.playerId = sale_orders.player_id
LEFT JOIN
	payment_account ON payment_account.id = sale_orders.payment_account_id
LEFT JOIN
	adminusers ON adminusers.userId = sale_orders.processed_by
LEFT JOIN
	playerlevel ON playerlevel.playerId = player.playerId) a
EOD;

		$where = array();
		$values = array();

		if ($params['dateRangeValueStart'] != null) {
			$where[] = 'process_time >= ?';
			$values[] = $params['dateRangeValueStart'];
		}

		if ($params['dateRangeValueEnd'] != null) {
			$where[] = 'process_time <= ?';
			$values[] = $params['dateRangeValueEnd'];

		}

		if ($params['paymentReportsortByUsername'] != null) {
			$where[] = 'username = ?';
			$values[] = $params['paymentReportsortByUsername'];
		}

		if ($params['paymentReportSortByPlayerLevel'] != null) {
			$where[] = 'playerGroupId = ?';
			$values[] = $params['paymentReportSortByPlayerLevel'];
		}

		if ($params['paymentReportSortByTransaction'] != null) {
			$where[] = 'transaction = ?';
			$values[] = $params['paymentReportSortByTransaction'];
		}

		if ($params['paymentReportSortByTransactionStatus'] != null) {
			switch ($params['paymentReportSortByTransactionStatus']) {
			case 'approved':
				$where[] = "status IN(?,?,?)";
				$values[] = 'approved';
				$values[] = Sale_order::STATUS_BROWSER_CALLBACK;
				$values[] = Sale_order::STATUS_SETTLED;
				break;
			case 'declined':
				$where[] = "status IN(?,?,?)";
				$values[] = 'declined';
				$values[] = Sale_order::STATUS_DECLINED;
				$values[] = Sale_order::STATUS_FAILED;
				break;
			case 'cancelled':
				$where[] = "status IN(?,?)";
				$values[] = 'cancelled';
				$values[] = Sale_order::STATUS_CANCELLED;
				break;
			default:
				break;
			}
		}

		if ($params['paymentReportSortByDWAmountGreaterThan'] != null) {
			$where[] = 'amount >= ?';
			$values[] = $params['paymentReportSortByDWAmountGreaterThan'];
		}

		if ($params['paymentReportSortByDWAmountLessThan'] != null) {
			$where[] = 'amount <= ?';
			$values[] = $params['paymentReportSortByDWAmountLessThan'];
		}

		if ($where) {
			$sql .= ' WHERE ' . implode(' AND ', $where);
		}

		$query = $readOnlyDB->query($sql, $values);
		return $query->result_array();
	}

	//===payment report=========================================================================================

	/**
	 * detail: get payment reports
	 *
	 * @param array $request
	 * @param Boolean $is_export
	 *
	 * @return array
	 */
	public function payment_report($request, $is_export = false) {

		$readOnlyDB = $this->getReadOnlyDB();

		$this->load->library('data_tables', array("DB" => $readOnlyDB));
		// $this->load->library([ 'player_manager' ]);
		$this->load->model(array('transactions', 'player_model'));

		$this->data_tables->is_export = $is_export;

		$input = $this->data_tables->extra_search($request);
		// $this->benchmark->mark('pre_processing_start');
		$where = array();
		$values = array();
		$having = array();
		$group_by = [];


		// if (isset($input['group_by']) && !empty($input['group_by'])) {
		if (!isset($input['group_by']) ) {
			// default group by and where
			$group_by = array('transactions.trans_date', 'transactions.transaction_type', 'transactions.payment_account_id');
			$where[] = "banktype.bankName IS NOT NULL";
		}
		else {
			$this->utils->debug_log('group_by', $input['group_by']);

			switch ($input['group_by']) {
				// case 'by_promotion_category' :
				// 	$group_by = [ 'transactions.trans_date', 'transactions.transaction_type', 'transactions.promo_category' ];
				// 	break;
				case 'by_player' :
					$group_by = [ 'transactions.trans_date', 'transactions.transaction_type', 'transactions.to_id' ];
					break;
				case 'by_level' :
					$group_by = [ 'transactions.trans_date', 'transactions.transaction_type', 'player.levelId' ];
					$where[] = "player.levelName IS NOT NULL";
					break;
				case 'by_admuser' :
					$group_by = [ 'transactions.trans_date', 'transactions.transaction_type', 'transactions.from_id' ];
					$where[] = "from_username IS NOT NULL";
					break;
				case 'by_aff' :
					$group_by = [ 'transactions.trans_date', 'transactions.transaction_type', 'player.affiliateId' ];
					$where[] = "affiliates.username IS NOT NULL";
					break;
				case 'by_agency' :
					$group_by = [ 'transactions.trans_date', 'transactions.transaction_type', 'player.agent_id' ];
					$where[] = "agency_agents.agent_name IS NOT NULL";
					break;
				case 'by_ref' :
					$group_by = [ 'transactions.trans_date', 'transactions.transaction_type', 'playerfriendreferral.playerId' ];
					$where[] = "playerfriendreferral.playerId IS NOT NULL";
					break;
				case 'by_payment_type' : default :
					$group_by = [ 'transactions.trans_date', 'transactions.transaction_type', 'transactions.payment_account_id' ];
					$where[] = "banktype.bankName IS NOT NULL";
					$where[] = "payment_account.payment_account_number IS NOT NULL";
					break;
			}
		}
		$this->utils->debug_log('group_by sql --->', $group_by);

		# START DEFINE COLUMNS #################################################################################################################################################
		// $i = 0;
		// $date_col = 0;
		// $member_col = 1;
		// $user_col = 2;
		// $group_level_col = 3;
		// $trans_type_col = 4;
		// $payment_type_col = 5;
		// $promo_cat_col = 6;
		// $amount_col = 7;

		$col = 0;
		$na = $is_export ? lang('lang.norecyet') : '<i class="text-muted">' . lang('lang.norecyet') . '</i>';

		$columns = array(
			array(
				'alias' => 'to_id',
				'select' => 'transactions.to_id',
			),
			array(
				'alias' => 'from_id',
				'select' => 'transactions.from_id',
			),
			array(
				'alias' => 'to_type',
				'select' => 'transactions.to_type',
			),
			array(
				'alias' => 'from_type',
				'select' => 'transactions.from_type',
			),
			// 0 - date
			array(
				'dt' => $col++,
				'alias' => 'date',
				'select' => 'transactions.trans_date',
				// 'formatter' => 'dateFormatter',
				'name' => lang('Date'),
			),
			array(
				'alias' => 'player_levelName',
				'select' => 'player.levelName'
			),
			array(
				'alias' => 'player_groupName',
				'select' => 'player.groupName'
			),
			// 1 - member (player-username)
			array(
				'dt' => $col++,
				'alias' => 'member',
				'select' => 'transactions.to_username',
				'formatter' => function ($d, $row) use ($is_export, $group_by, $na) {

					$affiliate = ( ! empty( $row['affiliate'] ) ) ? $row['affiliate'] : '';

					// if (in_array('transactions.to_id', $group_by)) {

						if ($is_export) {
							return ($d ? $d : lang('lang.norecyet'));
						} else {

							$to_type_lang = lang('transaction.from.to.type.' . $row['to_type']);
							switch ($row['to_type']) {
							case Transactions::ADMIN:
								// return '<i class="fa fa-user-secret" title="' . lang('transaction.from.to.type.' . $row['to_type']) . '"></i> ' . ($d . '('.$affiliate.')' ?: '<i>' . lang('lang.norecyet') . '</i>');
								return '<i class="fa fa-user-secret" title="' . $to_type_lang . '"></i> ' . ($d ?: $na);
								break;
							case Transactions::PLAYER:
								return '<i class="fa fa-user" title="' . $to_type_lang . '"></i> ' . ($d ? '<a href="/player_management/userInformation/' . $row['to_id'] . '" target="_blank">' . $d .  '</a>' : $na);
								break;
							case Transactions::AFFILIATE:
								return '<i class="fa fa-users" title="' . $to_type_lang . '"></i> ' . ($d  ?: $na);
								break;
							default:
								return $na;
							}
						}
					// }else{
					// 	if ($is_export) {
					// 		return $row['member'];
					// 	} else {
					// 		return '<i class="fa fa-user" title="' . lang('transaction.from.to.type.' . $row['from_type']) . '"></i> ' . ($d ? '<a href="/player_management/userInformation/' . $row['from_id'] . '" target="_blank">' . $row['member'] . '</a>' : '<i class="text-muted">' . lang('lang.norecyet') . '</i>');
					// 	}
					// }
					return '';
				},
				'name' => lang('Player Username'),
			),
			// 2 - affiliate username
			[
				'dt' => $col++,
				'alias' => 'Affiliate',
				'select' => 'affiliates.username',
				'formatter' => function ($d, $row) use ($na) {
					return $d ?: $na;
				},
				'name' => lang('Affiliate Username'),
			],
			// 3 - agency username
			[
				'dt' => $col++,
				'alias' => 'Agency',
				'select' => 'agency_agents.agent_name',
				'formatter' => function ($d, $row) use ($na) {
					// $a = $this->agency_model->get_agent_by_id($d);
					// return $a['agent_name'] ?: $na;
					return $d ?: $na;
				},
				'name' => lang('Agent Username'),
			],
			// 4 - referrer username
			[
				'dt' => $col++,
				'alias' => 'Referrer',
				'select' => 'playerfriendreferral.playerId',
				'formatter' => function ($d, $row) use ($na) {
					// $referrer = $this->player_model->getReferrerByPlayerId($d);
					// return isset($referrer['username']) ? $referrer['username'] : $na;
					$referrer_username = $this->player_model->getUsernameById($d);
					return $referrer_username ?: $na;
				},
				'name' => lang('pay_report.referrer_username'),
			],
			// 5 - adm-user
			array(
				'dt' => $col++,
				'alias' => 'user',
				'select' => 'from_username',
				'formatter' => function ($d, $row) use ($is_export, $group_by, $na) {
					//echo "<pre>";print_r($row);exit;
					$affiliate = ( ! empty( $row['affiliate'] ) ) ? $row['affiliate'] : '';

					if (in_array('transactions.from_id', $group_by)) {
						if ($is_export) {
							return $d;
						} else {

							switch ($row['from_type']) {
							case Transactions::ADMIN:
								return '<i class="fa fa-user-secret" title="' . lang('transaction.from.to.type.' . $row['from_type']) . '"></i> ' . ($d ?: $na);
							case Transactions::PLAYER:
								return '<i class="fa fa-user" title="' . lang('transaction.from.to.type.' . $row['from_type']) . '"></i> ' . ($d ? '<a href="/player_management/userInformation/' . $row['from_id'] . '" target="_blank">' . $d . '</a>' : $na);
							case Transactions::AFFILIATE:
								return '<i class="fa fa-users" title="' . lang('transaction.from.to.type.' . $row['from_type']) . '"></i> ' . ($d ?: $na);
							default:
								return '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
							}
						}
					}else{
						if ($is_export) {
							return $row['user'];
						} else {
							return '<i class="fa fa-user" title="' . lang('transaction.from.to.type.' . $row['from_type']) . '"></i> ' . ($d ?: $na);
						}
					}
					return '';
				},
				'name' => lang('Admin Username'),
			),
			// 6 - group level (vip-level)
			array(
				'dt' => $col++,
				'alias' => 'player_level',
				'select' => 'player.levelName',
				'formatter' => function ($d, $row) use ($is_export, $group_by, $input) {
					if (in_array('player.levelId', $group_by) || isset($input['by_player_level']) ) {
						if($d != 'N/A') {
							return lang($row['player_groupName']).' - '.lang($row['player_levelName']);
						}
						return $d;
					}
					return '';
				},
				// 'formatter' => function ($d, $row) use ($is_export) {
				// 	if ($is_export) {
				// 		return ($d ? $d : lang('lang.norecyet'));
				// 	} else {
				// 		switch ($row['from_type']) {
				// 		case Transactions::ADMIN:
				// 			return '<i class="fa fa-user-secret" title="' . lang('transaction.from.to.type.' . $row['from_type']) . '"></i> ' . ($d ?: '<i class="text-muted">' . lang('lang.norecyet') . '</i>');
				// 		case Transactions::PLAYER:
				// 			return '<i class="fa fa-user" title="' . lang('transaction.from.to.type.' . $row['from_type']) . '"></i> ' . ($d ? '<a href="/player_management/userInformation/' . $row['from_id'] . '" target="_blank">' . $d . '</a>' : '<i class="text-muted">' . lang('lang.norecyet') . '</i>');
				// 		case Transactions::AFFILIATE:
				// 			return '<i class="fa fa-users" title="' . lang('transaction.from.to.type.' . $row['from_type']) . '"></i> ' . ($d ?: '<i class="text-muted">' . lang('lang.norecyet') . '</i>');
				// 		default:
				// 			return '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
				// 		}
				// 	}
				// },
				'name' => lang('Player Level'),
			),
			// 7 - pay type (coll-account)
			array(
				'dt' => $col++,
				'alias' => 'payment_type',
				'select' => 'payment_account.payment_account_name',
				'formatter' => function ($d, $row) use ($is_export, $group_by) {
					if (in_array('transactions.payment_account_id', $group_by)) {
						return lang($row['bankname']) . ' - ' . $d . (!empty($row['payment_account_number']) ? ' - ******'.substr($row['payment_account_number'], -6) : '');
					}
					return '';
				},
				'name' => lang('Payment Type'),
			),
			// 8 - promo-cat
			array(
				'dt' => $col++,
				'alias' => 'promoTypeName',
				'select' => 'promotype.promoTypeName',
				'formatter' => function ($d, $row) use ($is_export, $group_by) {
					if (in_array('transactions.promo_category', $group_by)) {
						return lang($d);
					}
					return '';
				},
				'name' => lang('Promotion Category'),
			),
			// 9 - tx-type
			array(
				'dt' => $col++,
				'alias' => 'transaction_type',
				'select' => 'transactions.transaction_type',
				'formatter' => function ($d, $row) use ($is_export, $group_by) {
					if (in_array('transactions.transaction_type', $group_by)) {
						if ($is_export) {
							return lang('transaction.transaction.type.' . $d) ?: lang('lang.norecyet');
						} else {
							return lang('transaction.transaction.type.' . $d) ?: '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
						}
					}
					return '';
				},
				'name' => lang('Transaction Type'),
			),
			array(
				'alias' => 'bankname',
				'select' => 'banktype.bankName',
			),
			array(
				'alias' => 'payment_account_number',
				'select' => 'payment_account.payment_account_number',
			),
			// 10 - amount
			array(
				'dt' => $col++,
				'alias' => 'amount',
				'select' => 'sum(transactions.amount)',
				'formatter' => function ($d) use ($is_export) {
					if ($is_export) {
						return $this->utils->formatCurrencyNoSym($d);
					} else {
						return $d == 0 ? '<span class="text-muted">' . $this->utils->formatCurrencyNoSym($d) . '</span>' : '<strong>' . $this->utils->formatCurrencyNoSym($d) . '</strong>';
					}
					//return $d;
				},
				'name' => lang('Amount'),
			),
			array(
				'alias' => 'affiliate',
				'select' => 'affiliates.username'
			)
		);
		# END DEFINE COLUMNS #################################################################################################################################################
		#
		if ($is_export) {
			// Exclude unwanted columns for export
			// (Hidden by javascript in html output; In export we exclude columns physically)
			$hide_cols = [];
			$group_by_opt = isset($input['group_by']) ? $input['group_by'] : null;
			switch ($group_by_opt) {
				case 'by_player':
					$hide_cols = [ 5, 6, 7, 8 ];
					break;
				case 'by_level':
					$hide_cols = [ 1, 2, 3, 4, 5 ,7, 8 ];
					break;
				case 'by_admuser':
					$hide_cols = [ 1, 2, 3, 4, 6, 7, 8 ];
					break;
				case 'by_aff' :
					$hide_cols = [ 1, 3, 4, 5, 6, 7, 8 ];
					break;
				case 'by_agency' :
					$hide_cols = [ 1, 2, 4, 5, 6, 7, 8 ];
					break;
				case 'by_ref' :
					$hide_cols = [ 1, 2, 3, 5, 6, 7, 8 ];
					break;
				case 'by_payment_type':
				default:
					$hide_cols = [ 1, 2, 3, 4, 5, 6, 8 ];
					break;
			}

			$hide_cols = array_flip($hide_cols);

			foreach ($columns as $key => & $col) {
				$this->utils->debug_log(__METHOD__, 'key', $key, 'col', $col);
				if (isset($col['dt']) && isset($hide_cols[$col['dt']])) {
					unset($columns[$key]);
				}
			}
		}

		$this->utils->debug_log(__METHOD__, 'columns', $columns);

		$table = 'transactions';
		$joins = array(
			'promotype' => "promotype.promotypeId = transactions.promo_category",
			'payment_account' => "payment_account.id = transactions.payment_account_id",
			'banktype' => "payment_account.payment_type_id = banktype.bankTypeId",
			'player' => "player.playerId = transactions.to_id and transactions.to_type=" . Transactions::PLAYER,
			'affiliates' => 'affiliates.affiliateId = player.affiliateId',
			'agency_agents' => 'agency_agents.agent_id = player.agent_id',
			'playerfriendreferral' => 'playerfriendreferral.invitedPlayerId = player.playerId'
		);

		# START PROCESS SEARCH FORM #################################################################################################################################################
		// $this->utils->debug_log('input', $input);

		// if (isset($input['from_type'])) {
		// 	$where[] = "transactions.from_type = ?";
		// 	$values[] = $input['from_type'];
		// }

		//if (isset($input['fromUsername'])) {
		// if (isset($input['fromUsernameId'])) {
		// 	//$where[] = "(CASE transactions.from_type WHEN ? THEN fromUser.username WHEN ? THEN fromPlayer.username WHEN ? THEN fromAffiliate.username ELSE NULL END) LIKE ?";
		// 	$where[] = "(CASE transactions.from_type WHEN ? THEN fromUser.userId WHEN ? THEN fromPlayer.playerId WHEN ? THEN fromAffiliate.affiliateId ELSE NULL END) = ?";
		// 	$values[] = Transactions::ADMIN;
		// 	$values[] = Transactions::PLAYER;
		// 	$values[] = Transactions::AFFILIATE;
		// 	//$values[] = $input['fromUsername'];
		// 	$values[] = $input['fromUsernameId'];
		// }

		// if (isset($input['no_affiliate']) && $input['no_affiliate'] == true) {
		// 	$where[] = "fromPlayer.affiliateId IS NULL OR toPlayer.affiliateId IS NULL";
		// }

		// if (isset($input['to_type'])) {
		// 	$where[] = "transactions.to_type = ?";
		// 	$values[] = $input['to_type'];
		// }

		//if (isset($input['toUsername'])) {
		// if (isset($input['toUsernameId'])) {
		// 	//$where[] = "(CASE transactions.to_type WHEN ? THEN toUser.username WHEN ? THEN toPlayer.username WHEN ? THEN toAffiliate.username ELSE NULL END) LIKE ?";
		// 	$where[] = "(CASE transactions.to_type WHEN ? THEN toUser.userId WHEN ? THEN toPlayer.playerId WHEN ? THEN toAffiliate.affiliateId ELSE NULL END) = ? ";
		// 	$values[] = Transactions::ADMIN;
		// 	$values[] = Transactions::PLAYER;
		// 	$values[] = Transactions::AFFILIATE;
		// 	//$values[] = '%' . $input['toUsername'] . '%';
		// 	//$values[] = $input['toUsername'];
		// 	$values[] = $input['toUsernameId'];
		// }

		if (isset($input['by_amount_greater_than'])) {
			$having["amount >= "] = $input['by_amount_greater_than'];
			// $values[] = $input['by_amount_greater_than'];
		}

		if (isset($input['by_amount_less_than'])) {
			$having["amount <= "] = $input['by_amount_less_than'];
			// $values[] = $input['by_amount_less_than'];
		}

		$where[]="transactions.status = ?";
		$values[]=Transactions::APPROVED;

		if (isset($input['by_transaction_type'])) {
			if ($input['by_transaction_type'] == -1) {
				$where[] = "transactions.transaction_type in (?,?,?,?)";
				$values[] = Transactions::ADD_BONUS;
				$values[] = Transactions::MEMBER_GROUP_DEPOSIT_BONUS;
				$values[] = Transactions::PLAYER_REFER_BONUS;
				$values[] = Transactions::RANDOM_BONUS;
			} else {
				$where[] = "transactions.transaction_type = ?";
				$values[] = $input['by_transaction_type'];
			}
		} else {
			$where[] = "transactions.transaction_type in (?,?,?,?,?,?,?)";
			$values[] = Transactions::ADD_BONUS;
			$values[] = Transactions::MEMBER_GROUP_DEPOSIT_BONUS;
			$values[] = Transactions::PLAYER_REFER_BONUS;
			$values[] = Transactions::RANDOM_BONUS;
			$values[] = Transactions::DEPOSIT;
			$values[] = Transactions::WITHDRAWAL;
			$values[] = Transactions::AUTO_ADD_CASHBACK_TO_BALANCE;
		}

		if (isset($input['enable_date']) && $input['enable_date']) {
			if (isset($input['by_date_from'], $input['by_date_to'])) {
				$where[] = "transactions.trans_date >=?";
				$where[] = "transactions.trans_date <=?";
				$values[] = $input['by_date_from'];
				$values[] = $input['by_date_to'];
			}
		}

		if (isset($input['by_player_level'])) {
			$where[] = "player.levelId = ?";
			$values[] = $input['by_player_level'];
		}

		// if (isset($input['flag'])) {
		// 	$where[] = "transactions.flag = ?";
		// 	$values[] = $input['flag'];
		// }

		// if (isset($input['promo_category'])) {
		// 	$where[] = "transactions.promo_category = ?";
		// 	$values[] = $input['promo_category'];
		// }

		$player_id = null;
		//convert memberUsername to player id
		// $this->utils->debug_log('memberUsername', $input['memberUsername']);
		// if (isset($input['by_username']) && !empty($input['by_username'])) {
		// 	$memberUsername = $input['by_username'];
		// 	$playerId = $this->player_model->getPlayerIdByUsername($memberUsername);
		// 	// $this->utils->debug_log('memberUsername', $memberUsername, $playerId);
		// 	if (!empty($playerId)) {
		// 		$player_id = $playerId;
		// 	}
		// }

		// if ($player_id) {
		// 	$where[] = "(transactions.to_type = ? AND transactions.to_id = ?)";
		// 	$values[] = Transactions::PLAYER;
		// 	$values[] = $player_id;
		// }

		if (isset($input['by_username'], $input['search_by'])) {
			if ($input['search_by'] == 1) {
				$where[] = "transactions.to_username LIKE ?";
				$values[] = '%' . $input['by_username'] . '%';
			} else if ($input['search_by'] == 2) {
				$where[] = "transactions.to_username = ?";
				$values[] = $input['by_username'];
			}

			$where[] = "(transactions.to_type = ?)";
			$values[] = Transactions::PLAYER;
		}

		if (isset($input['agent_name'])) {
            $show_game_platform = true;
            $show_game_type = true;
            $show_game = true;
            $show_player = true;
            $this->load->model(array('agency_model'));
			$agent_detail = $this->agency_model->get_agent_by_name($input['agent_name']);

			if (isset($input['include_all_downlines']) && $input['include_all_downlines'] == 'on' && !empty($agent_detail)) {
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

		// for affiliate
		if (isset($input['affiliate_username']) && ! empty($input['affiliate_username'])) {
			$where[] = "affiliates.username = ?";
			$values[] = $input['affiliate_username'];
			$show_player = true;
		}

		// referrer
		if (isset($input['referrer_username'])) {
			$referrer_player_id = $this->player_model->getPlayerIdByUsername($input['referrer_username']);
			if (!empty($referrer_player_id)) {
				$where[] = "playerfriendreferral.playerId = ?";
				$values[] = $referrer_player_id;
			}
		}

		// adm-user
		if (isset($input['admin_username']) && !empty($input['admin_username'])) {
			$where[] = 'transactions.from_username = ?';
			$values[] = $input['admin_username'];
		}

		// Default clause
		$where[] = "player.deleted_at IS NULL";


		// $this->benchmark->mark('pre_processing_end');

		# END PROCESS SEARCH FORM #################################################################################################################################################
		if($is_export){
            $this->data_tables->options['is_export']=true;
			// $this->data_tables->options['only_sql']=true;
            if(empty($csv_filename)){
                $csv_filename=$this->utils->create_csv_filename(__FUNCTION__);
            }
            $this->data_tables->options['csv_filename']=$csv_filename;
		}
		$mark = 'data_sql';
		$this->utils->markProfilerStart($mark);
		$result = $this->data_tables->get_data($request, $columns, $table, $where, $values, $joins, $group_by, $having);

		if($is_export){
		    //drop result if export
			return $csv_filename;
		}
		$this->utils->markProfilerEndAndPrint($mark);

		$summary = $this->data_tables->summary($request, $table, $joins, 'SUM(transactions.amount) total_amount', null, $columns, $where, $values);

		$result['summary'][0]['total_amount'] = $this->utils->formatCurrencyNoSym($summary[0]['total_amount']);

		return $result;
	}

     /**
      * detail: get payment status history
      *
      * @param array $request
      * @param Boolean $is_export
      *
      * @return array
      */
     public function payment_status_history_report($request, $is_export = false) {

         $readOnlyDB = $this->getReadOnlyDB();

         $this->load->library('data_tables', array("DB" => $readOnlyDB));
         $this->load->model(array('sale_order'));

         $this->data_tables->is_export = $is_export;

         $input = $this->data_tables->extra_search($request);
         $this->utils->debug_log('search sql --->', $input);
         // $this->benchmark->mark('pre_processing_start');
         $where = array();
         $values = array();
         $having = array();
         $group_by = array("DATE_FORMAT(sale_orders_status_history.created_at,'%Y-%m-%d')", "sale_orders.payment_account_name");
         $this->utils->debug_log('group_by sql --->', $group_by);
         # START DEFINE COLUMNS #################################################################################################################################################
         $i = 0;
         $redirect_or_response_success_status = implode(',' , array(Sale_order::DEPOSIT_STATUS_RERIERCT_URL,Sale_order::DEPOSIT_STATUS_RERIERCT_FORM,Sale_order::DEPOSIT_STATUS_RERIERCT_DIRECT_PAY,Sale_order::DEPOSIT_STATUS_RERIERCT_QRCODE,Sale_order::DEPOSIT_STATUS_RERIERCT_QRCODE_MODAL,Sale_order::DEPOSIT_STATUS_RERIERCT_STATIC));
         $response_fail_status = implode(',', array(Sale_order::DEPOSIT_STATUS_RERIERCT_ERROR,Sale_order::DEPOSIT_STATUS_RERIERCT_ERROR_MODAL));

         $columns = array(
             array(
                 'dt' => $i++,
                 'alias' => 'created_at',
                 'select' => 'DATE_FORMAT(sale_orders_status_history.created_at, \'%Y-%m-%d\')',
                 'name' => lang('Date'),
             ),
             array(
                 'dt' => $i++,
                 'alias' => 'payment_account_name',
                 'select' => 'sale_orders.payment_type_name',
                 'formatter' => function ($d) use ($is_export) {
                     if ($is_export) {
                         return lang($d);
                     } else {
                         return lang($d);
                     }
                 },
                 'name' => lang("pay.payment_account"),
             ),
             array(
                 'dt' => $i++,
                 'alias' => 'request_num',
                 'select' => 'SUM( CASE WHEN sale_orders_status_history.status = ' . Sale_order::DEPOSIT_STATUS_CREATE_ORDER . ' THEN 1 ELSE 0 END )',
                 'formatter' => function ($d) use ($is_export) {
                     if ($is_export) {
						return $d;
					} else {
						return $d ? $d : 0;
					}
                 },
                 'name' => lang("report.p59"),
             ),
             array(
                 'dt' => $i++,
                 'alias' => 'submit_num',
                 'select' => 'SUM( CASE WHEN sale_orders_status_history.status = ' . Sale_order::DEPOSIT_STATUS_SUBMIT_ORDER . ' THEN 1 ELSE 0 END )',
                 'formatter' => function ($d) use ($is_export) {
                     if ($is_export) {
						return $d;
					} else {
						return $d ? $d : 0;
					}
                 },
                 'name' => lang("report.p60"),
             ),
             array(
                 'dt' => $i++,
                 'alias' => 'redirect_or_response_success_num',
                 'select' => 'SUM( CASE WHEN sale_orders_status_history.status IN (' . $redirect_or_response_success_status . ') THEN 1 ELSE 0 END )',
                 'formatter' => function ($d) use ($is_export) {
                     if ($is_export) {
						return $d;
					} else {
						return $d ? $d : 0;
					}
                 },
                 'name' => lang("report.p61"),
             ),
             array(
                 'dt' => $i++,
                 'alias' => 'response_fail_num',
                 'select' => 'SUM( CASE WHEN sale_orders_status_history.status IN (' . $response_fail_status . ') THEN 1 ELSE 0 END )',
                 'formatter' => function ($d) use ($is_export) {
                     if ($is_export) {
						return $d;
					} else {
						return $d ? $d : 0;
					}
                 },
                 'name' => lang("report.p62"),
             ),
             array(
                 'dt' => $i++,
                 'alias' => 'get_callback_num',
                 'select' => 'SUM( CASE WHEN sale_orders_status_history.status = ' . Sale_order::DEPOSIT_STATUS_GET_CALLBACK . ' THEN 1 ELSE 0 END )',
                 'formatter' => function ($d) use ($is_export) {
                     if ($is_export) {
						return $d;
					} else {
						return $d ? $d : 0;
					}
                 },
                 'name' => lang("report.p63"),
             ),
             array(
                 'dt' => $i++,
                 'alias' => 'process_callback_fail_num',
                 'select' => 'SUM( CASE WHEN sale_orders_status_history.status = ' . Sale_order::DEPOSIT_STATUS_CHECK_CALLBACK_ORDER_FAILED . ' THEN 1 ELSE 0 END )',
                 'formatter' => function ($d) use ($is_export) {
                     if ($is_export) {
						return $d;
					} else {
						return $d ? $d : 0;
					}
                 },
                 'name' => lang("report.p64"),
             ),
             array(
                 'dt' => $i++,
                 'alias' => 'approve_sale_order_num',
                 'select' => 'SUM( CASE WHEN sale_orders_status_history.status = ' . Sale_order::DEPOSIT_STATUS_APPROVE_SALE_ORDER . ' THEN 1 ELSE 0 END )',
                 'formatter' => function ($d) use ($is_export) {
                     if ($is_export) {
						return $d;
					} else {
						return $d ? $d : 0;
					}
                 },
                 'name' => lang("report.p65"),
             ),
             array(
                 'dt' => $i++,
                 'alias' => 'success_rate', //(成功上分 / 导页成功 or 即时返回成功)
                 'select' => '(SUM( CASE WHEN sale_orders_status_history.status = ' . Sale_order::DEPOSIT_STATUS_APPROVE_SALE_ORDER . ' THEN 1 ELSE 0 END ) / SUM( CASE WHEN sale_orders_status_history.status IN ('. $redirect_or_response_success_status . ') THEN 1 ELSE 0 END ))*100',
                 'formatter' => function ($d) use ($is_export) {
                     if ($is_export) {
                         return $this->data_tables->percentageFormatter($d/100);
					} else {
						return $this->data_tables->percentageFormatter($d/100);
					}
                 },
                 'name' => lang("report.p66"),
             ),
             array(
                 'dt' => $i++,
                 'alias' => 'failed_rate', //(处理回调失败 / 导页成功 or 即时返回成功)
                 'select' => '(SUM( CASE WHEN sale_orders_status_history.status = ' . Sale_order::DEPOSIT_STATUS_CHECK_CALLBACK_ORDER_FAILED . ' THEN 1 ELSE 0 END ) / SUM( CASE WHEN sale_orders_status_history.status IN ('. $redirect_or_response_success_status . ') THEN 1 ELSE 0 END ))*100',
                 'formatter' => function ($d) use ($is_export) {
                     if ($is_export) {
                         return $this->data_tables->percentageFormatter($d/100);
					} else {
						return $this->data_tables->percentageFormatter($d/100);
					}
                 },
                 'name' => lang("report.p67"),
             )
         );
         # END DEFINE COLUMNS #################################################################################################################################################

         $table = 'sale_orders_status_history';
         $joins = array(
             'sale_orders' => "sale_orders_status_history.order_id = sale_orders.id",
         );

         if (isset($input['enable_date']) && $input['enable_date']) {
             if (isset($input['by_date_from'], $input['by_date_to'])) {
                 $where[] = "sale_orders_status_history.created_at >=?";
                 $where[] = "sale_orders_status_history.created_at <=?";
                 $values[] = $input['by_date_from'];
                 $values[] = $input['by_date_to'];
             }
         }

         if (isset($input['by_accountname'])) {
             $accountname = $input['by_accountname'];
             if (mb_strlen($accountname,"utf-8") != strlen($accountname)){
                 //turn utf8 to unicode
                 $unicode = 0;
                 $unicode = (ord($accountname[0]) & 0x1F) << 12;
                 $unicode |= (ord($accountname[1]) & 0x3F) << 6;
                 $unicode |= (ord($accountname[2]) & 0x3F);
                 $accountname = dechex($unicode);
             }

             $where[] = "sale_orders.payment_type_name LIKE ?";
             $values[] = '%' . $accountname . '%';
         }

         if (isset($input['by_success_rate_greater_than'])) {
             $having_key = ($input['by_success_rate_greater_than'] == 0) ? 'success_rate >= ' : 'success_rate >= ' ;
             $having[$having_key] = (float)$input['by_success_rate_greater_than'];
         }

         if (isset($input['by_success_rate_less_than'])) {
             $having_key = ($input['by_success_rate_less_than'] == 0) ? 'success_rate <= ' : 'success_rate <= ' ;
             $having[$having_key] = (float)$input['by_success_rate_less_than'];
         }

         if (isset($input['by_failed_rate_greater_than'])) {
             $having_key = ($input['by_failed_rate_greater_than'] == 0) ? 'failed_rate >= ' : 'failed_rate >= ' ;
             $having[$having_key] = (float)$input['by_failed_rate_greater_than'];
         }

         if (isset($input['by_failed_rate_less_than'])) {
             $having_key = ($input['by_failed_rate_less_than'] == 0) ? 'failed_rate <= ' : 'failed_rate <= ' ;
             $having[$having_key] = (float)$input['by_failed_rate_less_than'];
         }
         # END PROCESS SEARCH FORM #################################################################################################################################################
         $mark = 'data_sql';
         $this->utils->markProfilerStart($mark);
         $result = $this->data_tables->get_data($request, $columns, $table, $where, $values, $joins, $group_by, $having);
         $this->utils->markProfilerEndAndPrint($mark);
         return $result;
     }

	/**
	 * detail: get balance history of a certain player
	 *
	 * @param int $player_id balance_history player_id
	 * @param array $request
	 * @param Boolean $is_export
	 *
	 * @return array
	 */
	public function balance_history($player_id, $request, $is_export = false) {

		$readOnlyDB = $this->getReadOnlyDB();

		$this->load->library('data_tables', array("DB" => $readOnlyDB));
		$this->load->model(array('transactions', 'player_model', 'affiliatemodel'));

		$this->data_tables->is_export = $is_export;

		$_database = '';
		$_extra_db_name = '';
		$is_balance_history_in_extra_db = $this->utils->_getBalanceHistoryInExtraDbWithMethod( __METHOD__, $this->utils->getActiveTargetDB(), $_extra_db_name );
		if($is_balance_history_in_extra_db){
			$_database = "`{$_extra_db_name}`";
			$_database .= '.'; // ex: "og_OGP-26371_extra."
		}


		$this->benchmark->mark('pre_processing_start');

		# START DEFINE COLUMNS #################################################################################################################################################
		$i = 0;
		$columns = array(
			array(
				'alias' => 'id',
				'select' => 'balance_history.id',
			),
			array(
				'alias' => 'player_id',
				'select' => 'balance_history.player_id',
			),
			array(
				'dt' => $i++,
				'alias' => 'date',
				'select' => 'balance_history.created_at',
				'formatter' => 'dateTimeFormatter',
				'name' => lang('player.ut01'),
			),
			array(
				'dt' => $i++,
				'alias' => 'action_type',
				'select' => 'balance_history.action_type',
				'name' => lang('player.ut01'),
				'formatter' => function ($d, $row) use ($is_export) {

					if ($d < 1000) {
						return lang('transaction.transaction.type.' . $d);
					} else {
						return lang('balance.action.type.' . $d);
					}

				},
			),
			array(
				'dt' => $i++,
				'alias' => 'main_wallet',
				'select' => 'balance_history.main_wallet',
				'formatter' => 'currencyFormatter',
				'name' => lang('Main Wallet'),
			),
			array(
				'dt' => $i++,
				'alias' => 'total_balance',
				'select' => 'balance_history.total_balance',
				'formatter' => 'currencyFormatter',
				'name' => lang('Total Balance'),
			),
			array(
				'dt' => $i++,
				'alias' => 'big_wallet',
				'select' => 'balance_history.big_wallet',
				'formatter' => function($d, $row) use($is_export){
					if($is_export){
						return '';
					}else{
						return '<button class="btn btn-success btn-xs show_bigwallet_details btn-scooter" data-playerid="'.$row['player_id'].'">'.lang('Details').'</button>';
					}
				},
				'name' => lang('Wallet Details'),
			),
		);
		# END DEFINE COLUMNS #################################################################################################################################################

		$table = $_database. 'balance_history';
		$joins = array(
		);

		# START PROCESS SEARCH FORM #################################################################################################################################################
		$where = array();
		$values = array();
		// $request = $this->input->post();
		$input = $this->data_tables->extra_search($request);
		// $this->utils->debug_log('input', $input);

		if (isset($input['dateRangeValueStart'])) {
			$where[] = "balance_history.created_at >= ?";
			$values[] = $input['dateRangeValueStart'];
		}

		if (isset($input['dateRangeValueEnd'])) {
			$where[] = "balance_history.created_at <= ?";
			$values[] = $input['dateRangeValueEnd'];
		}

		//convert memberUsername to player id
		// $this->utils->debug_log('memberUsername', $input['memberUsername']);
		if (isset($input['memberUsername']) && !empty($input['memberUsername'])) {
			$memberUsername = $input['memberUsername'];
			$playerId = $this->player_model->getPlayerIdByUsername($memberUsername);
			// $this->utils->debug_log('memberUsername', $memberUsername, $playerId);
			if (!empty($playerId)) {
				$player_id = $playerId;
			}
		}

		if ($player_id) {
			$where[] = "balance_history.player_id =?";
			$values[] = $player_id;
		}

		$this->benchmark->mark('pre_processing_end');

		# END PROCESS SEARCH FORM #################################################################################################################################################

		$this->benchmark->mark('data_sql_start');
		$result = $this->data_tables->get_data($request, $columns, $table, $where, $values, $joins);
		$this->benchmark->mark('data_sql_end');

		return $result;
	}

  	/**
  	 * detail: get deposit list of a certain player
  	 *
  	 * @param int $playerId
  	 * @param array $request
  	 * @param Boolean $is_export
  	 *
  	 * @return array
  	 */
	public function depositList($playerId=null, $request, $is_export = false, $is_locked = false, $inPlayerCenter = '', $notDatatable = '', $_where = '', $csv_filename=null, $allow_multiple_select = false, $customizationExternalId = null , $playerDetailPermissions = null) {
		$markName = 'depositList';
		$this->utils->markProfilerStart($markName); // list mark.
		$this->load->model(array('sale_order', 'payment_account','player_attached_proof_file_model','sale_orders_notes','users', 'player_model','player_promo','promorules'));
  		$this->load->library(array('data_tables'));
  		$this->load->helper(['player_helper']);
  		$player_attached_proof_file_model = $this->player_attached_proof_file_model;
  		$permDepositDetail = false;
		$this->load->library(array('permissions'));

  		if( empty($inPlayerCenter) && !$is_export ){

  			$this->permissions->setPermissions();

  			$permDepositDetail = $this->permissions->checkPermissions('approve_decline_deposit');

			#OGP-22298
			$singleDepositDetail = $this->permissions->checkPermissions('single_approve_decline_deposit');
  		}

		  $singleDepositDetail = $this->permissions->checkPermissions('single_approve_decline_deposit');

  		$input = $this->data_tables->extra_search($request);
  		$dwStatus = isset($input['dwStatus'])?$input['dwStatus']:'';
  		if(is_array($this->config->item('cryptocurrencies'))){
  			$enabled_crypto = true;
  		}else{
  			$enabled_crypto = false;
  		}

		$enable_split_player_username_and_affiliate = $this->utils->getConfig('enable_split_player_username_and_affiliate');

		$enabled_player_tag_in_deposit = $this->utils->getConfig('enabled_player_tag_in_deposit');

		if($this->utils->getConfig('enable_cpf_number')){
			$enable_cpf_number = true;
		}else{
			$enable_cpf_number = false;
		}

		$this->utils->debug_log('========= depositList customizationExternalId ---->', $customizationExternalId);


  		# START DEFINE COLUMNS #################################################################################################################################################
  		$i = 0;
  		$columns = array(
  			array(
  				'alias' => 'geo_location',
  				'select' => 'sale_orders.geo_location',
  			),
  			array(
				'alias' => 'playerId',
				'select' => 'player.playerId',
			),
			array(
				'alias' => 'system_id',
				'select' => 'sale_orders.system_id',
			),
			array(
				'dt' => (!$is_export) ? $i++ : NULL, // #1
  				'alias' => 'id',
  				'select' => 'sale_orders.id',
				'name' => lang('column.id'),
				'formatter' => function ($d) use ($permDepositDetail, $is_export, $is_locked, $allow_multiple_select, $singleDepositDetail) {
					if($is_locked) {
						return '<input type="checkbox" name="sales_order_id" value="'.$d.'">';

					}
  					if (!$is_export) {
						if ($permDepositDetail) {
  							$action = '<div class="clearfix" style="width:65px;">';
  							if ($allow_multiple_select) {
  								$action .= '<div class="col-md-3" style="padding:5px 1px 0 2px"><input type="checkbox" name="sales_order_id" class="chk-order-id" value="'.$d.'"></div>';
  							}
  							$action .= '<div class="col-md-9" style="padding:0 2px 0 2px"><span class="btn btn-xs btn-info review-btn" onclick="getDepositDetail(' . $d . ')" data-toggle="modal" >' . lang("lang.details") . '</span></div>';
  							$action .= "</div>";

  							return $action;	// data-target="#depositDetailsModal"
						} elseif ($singleDepositDetail) {
							$action = '<div class="clearfix" style="width:65px;">';
							$action .= '<div class="col-md-9" style="padding:0 2px 0 2px"><span class="btn btn-xs btn-info review-btn" onclick="getDepositDetail(' . $d . ')" data-toggle="modal" >' . lang("lang.details") . '</span></div>';
							$action .= "</div>";
							return $action;
						}
						else {
							return '';
  						}
  				    }else{
						return $d;
  				    }
  				},
  			),
			array(
				'dt' => $i++, // #2
				'alias' => 'status',
				'select' => 'sale_orders.status',
				'name' => lang('lang.status'),
				'formatter' => function ($d) use ($is_export) {
					if(!empty($d)){
						if (!$is_export) {
							switch ($d) {
								case Sale_order::STATUS_DECLINED:
									return '<i class="text-danger">' . lang('sale_orders.status.' . $d) . '</i>';
									break;
								case Sale_order::STATUS_SETTLED:
									return '<i class="text-success">' . lang('sale_orders.status.' . $d) . '</i>';
									break;
								default:
									return '<i class="text-default">' . lang('sale_orders.status.' . $d) . '</i>';
							}
							return $payment_flag;
						} else {
							return lang('sale_orders.status.' . $d);
						}
					} else {
						if (!$is_export) {
							return '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
						} else {
							return lang('lang.norecyet');
						}
					}
				},
			),
			array(
				'dt' => $i++, // #3
				'alias' => 'secure_id',
				'select' => 'sale_orders.secure_id',
				'name' => lang('deposit_list.order_id'),
  				'formatter' => function ($d, $row) use ($is_export,$dwStatus)  {
  					if (!$is_export) {
						$action = '';
						$text = '';
  						if($this->utils->isEnabledFeature('highlight_deposit_id_in_list')) {
  							if(!empty($d)){
  								$deposit_id_highlight_sequence = $this->utils->getConfig('deposit_id_highlight_sequence');
	  							if(!empty($deposit_id_highlight_sequence)){
	  								switch ($dwStatus) {
						  				case Sale_order::VIEW_STATUS_REQUEST_ALL:
						  				case Sale_order::VIEW_STATUS_REQUEST_TODAY:
						  				case Sale_order::VIEW_STATUS_REQUEST:
					  						foreach ($deposit_id_highlight_sequence as $key => $value) {
					  							if(isset($value['timeout']) && isset($value['background_color'])){
					  								if($this->utils->isTimeoutNow($row['created_at'], $value['timeout'])){
														$action .= '<span style="background-color: ' . $value['background_color'] . '; color:white">' . $d . '</span>';
														break;
					  								}
					  							}
					  						}
						  					break;
						  				default:
											$action .= '<span>' . $d . '</span>';
						  					break;
						  			}
	  							}
  							}
						}

						if (empty($action)){
							$action .= $d ?: '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
						}
						if (!empty($row['id'])){
							$text = $this->sale_order->getSaleOrderClipboardText($row['id']);
						}

						$action .= "<a href='javascript:void(0)' class='btn btn-primary btn-xs btn-copy' data-toggle='tooltip' data-order-id='$text'><span class='glyphicon glyphicon-share'></span> " . lang('Copy') ." </a>";
						return $action;
  					}else{
  						return $d ?: lang('lang.norecyet');
  					}
  				},
  			),
  			array(
  				'dt' => $i++, // #4
  				'alias' => 'username',
				'select' => 'sale_orders.player_id',
  				'name' => lang('system.word38'),
  				'formatter' => function ($d, $row) use ($is_export, $enable_split_player_username_and_affiliate)  {
					$username = '';
					if( ! empty($d) ){
						$player_id = $d;
						$player = $this->player_model->getPlayerById($player_id);
						$username = $player->username;

						if (!$enable_split_player_username_and_affiliate) {
							$affiliate_username = '';
							if( ! empty($player->affiliateId ) ){
								$affiliate = $this->affiliate->getAffiliateById($player->affiliateId);
								$affiliate_username = $affiliate['username'];
							}
							if( ! empty($affiliate_username) ){
								$username .= ' ('. $affiliate_username. ')';
							}
						}
					}

					if (!$is_export) {
						return sprintf('<a href="/player_management/userInformation/%s">%s</a>', $player_id, $username);
					}else{
						return $username;
					}
				},
			),
			array(
  				'dt' => $i++, //OGP-2814
  				'alias' => 'signup',
  				'select' => 'player.createdOn',
				'name' => lang('player.38'),
  				'formatter' => 'dateTimeFormatter',
  			),
			array(
				'dt' => $enable_split_player_username_and_affiliate ? $i++ : null , // #5
				'alias' => 'affiliate',
				'select' => 'sale_orders.player_id',
				'name' => lang('Affiliate'),
				'formatter' => function ($d)  use ($is_export)  {
					$affiliate_username = '';
					if( ! empty($d) ) {
						$player_id = $d;
						$player = $this->player_model->getPlayerById($player_id);
						if( ! empty($player->affiliateId ) ){
							$affiliate = $this->affiliate->getAffiliateById($player->affiliateId);
							$affiliate_username = $affiliate['username'];
						}
					}

  					if (!$is_export) {
						return !empty($affiliate_username) ? $affiliate_username : '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
  					}else{
						return !empty($affiliate_username) ? $affiliate_username : 'N/A';
  					}
  				},
  			),
  			array(
  				'dt' => $i++, // #6
  				'alias' => 'payment_flag',
  				'select' => 'payment_account.flag',
  				'name' => lang('pay.payment_account_flag'),
  				'formatter' => function ($d, $row) {

  					$payment_flag = lang('pay.auto_online_payment');

  					switch ($row['payment_flag']) {
  					case Payment_account::FLAG_MANUAL_LOCAL_BANK:
  						$payment_flag = lang('pay.local_bank_offline');
  						break;
  					case Payment_account::FLAG_MANUAL_ONLINE_PAYMENT:
  						$payment_flag = lang('pay.manual_online_payment');
  						break;
  					case Payment_account::FLAG_AUTO_ONLINE_PAYMENT:
  						$payment_flag = lang('pay.auto_online_payment');
  						break;
  					}

  					return $payment_flag;

  				},
  			),
  			array(
  				'dt' => $i++, // #7
  				'alias' => 'created_at',
  				'select' => 'sale_orders.created_at',
  				'name' => lang('pay.reqtime'),
  				'formatter' => 'dateTimeFormatter',
  			),
  			array(
  				'dt' => $i++, // #8
  				'alias' => 'deposit_date',
  				'select' => 'sale_orders.player_submit_datetime',
  				'name' => lang('Deposit Datetime'),
  				'formatter' => function ($d,$row)  use ($is_export)  {

  					if($row['status'] == Sale_order::STATUS_DECLINED){
        				if (!$is_export) {
  							return '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
	  					}else{
	  						return lang('lang.norecyet');
	  					}
  					}else{
  						if (!$is_export) {
  							return $d ?: '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
	  					}else{
	  						return $d;
	  					}
  					}
 				},
  			),
			array(
				'dt' => $i++, // #9
				'alias' => 'spentTime',
				// 'select' => 'sale_orders.id',
				'select' => 'IF(sale_orders.status = 5, UNIX_TIMESTAMP(sale_orders.player_submit_datetime) - UNIX_TIMESTAMP(sale_orders.created_at), -1)',
				'name' => lang('pay.spenttime'),
				'formatter' => function ($d, $row) use ($is_export) {
					// $request_time = strtotime($row['created_at']);
					// $paid_time    = strtotime($row['deposit_date']);
					// $spent_time   = $paid_time - $request_time;
					$spent_time   = $d;

					if($row['status'] == Sale_order::STATUS_SETTLED){
						$day    = floor($spent_time/86400);
						$hour   = floor($spent_time%86400/3600);
						$minute = floor($spent_time%86400%3600/60);
						$second = floor($spent_time%86400%3600%60);

						$timeString = sprintf("%d s", $second);
						if($minute > 0){
							$timeString = sprintf("%d m %d s", $minute, $second);
						}
						if($hour > 0){
							$timeString = sprintf("%d h %d m %d s", $hour, $minute, $second);
						}
						if($day > 0){
							$timeString = sprintf("%d d %d h %d m %d s", $day, $hour, $minute, $second);
						}

						return $timeString;
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
  				'dt' => $i++, // #10
  				'alias' => 'real_name',
				  // 'select' => 'CONCAT(ifnull(playerdetails.firstName, ""), \' \', ifnull(playerdetails.lastName, "") )',
				'select' => 'sale_orders.player_id',
  			    'name' => lang('system.word39'),
  				'formatter' => function ($d) use ($is_export)  {
					$realName = '';
					if( ! empty($d) ){
						$playerId = $d;
						$playerDetailObject =  $this->player_model->getPlayerDetailsById($playerId);
						$firstName = ! empty($playerDetailObject->firstName )? $playerDetailObject->firstName: '';
						$lastName = ! empty($playerDetailObject->lastName )? $playerDetailObject->lastName: '';
						$realName = $firstName. ' '. $lastName;
					}

  					if (!$is_export) {
  						return  ($realName == " ") ?  '<i class="text-muted">' . lang('lang.norecyet') . '</i>' : $realName;
  					}else{
  						return ($realName == " ") ? lang('lang.norecyet')  :  $realName ;
  					}
  				},
  			),
  			array(
				'dt' => $enable_split_player_username_and_affiliate ? null : $i++, // #11
  				'alias' => 'affiliate',
				// 'select' => 'affiliates.username',
				'select' => 'sale_orders.player_id',
				'name' => lang('Affiliate'),
  				'formatter' => function ($d)  use ($is_export)  {
					$affiliate_username = '';
					if( ! empty($d) ) {
						$player_id = $d;
						$player = $this->player_model->getPlayerById($player_id);
						if( ! empty($player->affiliateId ) ){
							$affiliate = $this->affiliate->getAffiliateById($player->affiliateId);
							$affiliate_username = $affiliate['username'];
						}
					}

  					if (!$is_export) {
  						return !empty($affiliate_username) ? $affiliate_username : '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
  					}else{
  						return !empty($affiliate_username) ? $affiliate_username : 'N/A';
  					}
  				},
  			),
  			array(
                'dt' => $i++, // #12
                'alias' => 'group_level',
                'select' => 'player.levelName',
                'name' => lang('pay.playerlev'),
                'formatter' => function ($d, $row) {
					$this->load->model(['vipsetting','sale_orders_additional']);
					$player_id = $row['playerId'];
					$sale_orders_id = $row['id'];
					$the_sale_orders_additional = $this->sale_orders_additional->getDetailBySaleOrderId($sale_orders_id);
					$sprintf_format = '%s - %s'; // params: groupName, vipLevelName
					$groupName = lang('N/A'); // defaults
					$vipLevelName = lang('N/A'); // defaults
					if( ! empty($the_sale_orders_additional['vip_level_info']) ){
						$vip_level_info = json_decode($the_sale_orders_additional['vip_level_info'], true);
					}else{
						$vip_level_info = $this->vipsetting->getVipGroupLevelInfoByPlayerId($player_id);
					}
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
				'dt' => $enabled_player_tag_in_deposit ? $i++ : NULL, // #13
				'alias' => 'tag',
				'select' => 'player.playerId',
				'name' => lang('Tag'),
				'formatter' => function ($d, $row) use ($is_export)  {
					return player_tagged_list($row['playerId'], $is_export);
				},
			),
			array(
				'dt' => ($enabled_crypto)? $i++ : NULL, // #14
				'alias' => 'received_crypto',
				'select' => 'crypto_deposit_order.received_crypto',
				'name' => lang('Received crypto'),
				'formatter' => function ($d) use ($is_export) {
					if (!$is_export) {
						return $d ? '<strong>'.$d.'</strong>' : '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
					}else{
						return $d ? : lang('lang.norecyet');
					}
				},
			),
			array(
				'dt' => $i++, // #15
				'alias' => 'amount',
				'select' => 'sale_orders.amount',
				'name' => lang('Deposit Amount'),
				'formatter' => function ($d) use ($is_export) {
					if (!$is_export) {
						if ($this->utils->getConfig('format_deposit_currency_nosym_with_decimal')) {
							return '<strong>'.$this->utils->formatCurrencyNoSymwithDecimal($d, $this->utils->getConfig('default_currency_decimals')).'</strong>';
						}
						return '<strong>'.$this->utils->formatCurrencyNoSym($d).'</strong>';
					}else{
						return $this->utils->formatCurrencyNoSym($d) ;
					}
				},
			),
			array(
				'dt' => ($enable_cpf_number)? $i++ : NULL, // #16
				'alias' => 'cpf_number',
				'select' => 'sale_orders.player_id',
				'name' => lang('financial_account.CPF_number'),
				'formatter' => function ($d)  use ($is_export, $playerDetailPermissions)  {
					$cpf_number = '';
					if(!empty($d)){
						$player_id = $d;
						$playerDetails = $this->player_model->getPlayerDetails($player_id);
						$cpf_number = (isset($playerDetails[0]) && !empty($playerDetails[0]['pix_number']))? $playerDetails[0]['pix_number'] : '';
						if(!empty($cpf_number)){
							if(!$playerDetailPermissions['player_cpf_number']){
								$cpf_number = $this->utils->keepOnlyString($cpf_number, -3);
							}
						}
					}

  					if (!$is_export){
  						if(!empty($cpf_number)){
  							return $cpf_number;
  						}else{
  							return '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
  						}
  					}else{
  						if(!empty($cpf_number)){
  							return $cpf_number;
  						}else{
  							return 'N/A';
  						}
  					}
  				},
  			),
            array(
                'dt' => $i++, // #17
                'alias' => 'player_fee',
                'select' => 'sale_orders.player_fee',
                'name' => lang('transaction.transaction.type.3'),
                'formatter' => function ($d) use ($is_export) {
                    if (!$is_export) {
                        return '<strong>'.$this->utils->formatCurrencyNoSym($d).'</strong>';
                    }else{
                        return $this->utils->formatCurrencyNoSym($d) ;
                    }
                },
            ),
  			array(
  				'dt' => $i++, // #18
  				'alias' => 'payment_type_name',
  				'select' => 'sale_orders.payment_type_name',
  				'name' => lang('pay.collection_name'),
  				'formatter' => 'languageFormatter',
  			),
  			array(
  				'dt' => $i++, // #19
  				'alias' => 'ip',
  				'select' => 'sale_orders.ip',
  				'name' => lang('deposit_list.ip'),
  				'formatter' => function ($d, $row)  use ($is_export)  {
  					if (!$is_export) {
  						return trim($d . ' ' . trim($row['geo_location'], ',')) ?: '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
  					}else{
  						return trim($d . ' ' . trim($row['geo_location'], ',')) ?: lang('lang.norecyet');
  					}

  				},
  			),
  			array(
  				'dt' => $i++, // #20
  				'alias' => 'updated_at',
  				'select' => 'sale_orders.updated_at',
				'name' => lang('pay.updatedon'),
  				'formatter' => 'dateTimeFormatter',
  			),
  			array(
  				'dt' => $i++, // #21
  				'alias' => 'timeout_at',
  				'select' => 'sale_orders.timeout_at',
  				'name' => lang('cms.timeoutAt'),
  				'formatter' => 'dateTimeFormatter',
  			),
			array(
				'dt' => $i++, // #22
				'alias' => 'process_time',
				'select' => 'sale_orders.process_time',
				'name' => lang('pay.procsson'),
				'formatter' => function ($d) use ($is_export)  {
					if (!$is_export) {
						return $d ?: '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
					}else{
						return $d ?: lang('lang.norecyet');
					}
				},
			),
  			array(
  				'dt' => $i++, // #23
  				'alias' => 'payment_account_name',
				// 'select' => 'payment_account.payment_account_name',
				'select' => 'sale_orders.payment_account_id',
  				'name' => lang('pay.collection_account_name'),
  				'formatter' => function ($d) use ($is_export)  {
					$payment_account_name = '';
					if( ! empty($d) ){
						$payment_account_id = $d;
						$paymentAccount = $this->payment_account->getPaymentAccount($payment_account_id);
						$payment_account_name = $paymentAccount->payment_account_name;
					}

  					if (!$is_export) {
  						return !empty($payment_account_name)? $payment_account_name: '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
  					}else{
  						return !empty($payment_account_name)? $payment_account_name: lang('lang.norecyet');
  					}
  				},
			),
			array(
				'dt' => $i++, // #24
				'alias' => 'notes',
				// 'select' => 'payment_account.notes',
				'select' => 'sale_orders.payment_account_id',
				'name' => lang('con.bnk20'),
				'formatter' => function ($d) {
					$paymentAccount_notes = '';
					if( ! empty($d) ){
						$payment_account_id = $d;
						$paymentAccount = $this->payment_account->getPaymentAccount($payment_account_id);
						$paymentAccount_notes = $paymentAccount->notes;
					}

					return !empty($paymentAccount_notes)? $paymentAccount_notes : lang('lang.norecyet');
				},
			),
  			array(
  				'dt' => $i++, // #25
  				'alias' => 'player_payment_type_name',
  				'select' => 'sale_orders.player_payment_type_name',
  				'name' => lang('pay.deposit_payment_name'),
  				'formatter' => function ($d) use ($is_export)  {
  					if (!$is_export) {
  						return lang($d) ?: '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
  					}else{
  						return lang($d) ?: lang('lang.norecyet');
  					}
  				},
  			),
  			array(
  				'dt' => $i++, // #26
  				'alias' => 'player_payment_account_name',
  				'select' => 'sale_orders.player_payment_account_name',
  				'name' => lang('pay.deposit_payment_account_name'),
  				'formatter' => function ($d) use ($is_export)  {
  					if (!$is_export) {
  						return $d ?: '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
  					}else{
  						return $d ?: lang('lang.norecyet');
  					}
  				},
  			),
  			array(
  				'dt' => $i++, // #27
  				'alias' => 'player_payment_account_number',
  				'select' => 'sale_orders.player_payment_account_number',
  				'name' => lang('pay.deposit_payment_account_number'),
  				'formatter' => function ($d) use ($is_export)  {
  					if (!$is_export) {
  						return $d ?: '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
  					}else{
  						return $d ?: lang('lang.norecyet');
  					}
  				},
  			),
  			array(
                'dt' => NULL, // (!$is_export) ? $i++ : NULL, // #28 by OGP-26797
  				'alias' => 'player_deposit_transaction_code',
  				'select' => 'sale_orders.player_deposit_transaction_code',
  				'name' => lang('pay.deposit_transaction_code'),
  				'formatter' => function ($d) use ($is_export)  {
  					if (!$is_export) {
  						return $d ?: '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
  					}else{
  						return $d ?: lang('lang.norecyet');
  					}
  				},
  			),
  			array(
  				'dt' => $i++, // #29
  				'alias' => 'promoName',
				// 'select' => 'promorules.promoName',
				'select' => 'sale_orders.player_promo_id',
  				'name' => lang('cms.promotitle'),
  				'formatter' => function ($d) use ($is_export)  {
					$promoName = '';
					if( ! empty($d) ){
						$player_promo_id = $d;
						$playerPromo = $this->player_promo->getPlayerPromo($player_promo_id);
						if (!empty($playerPromo)) {
							$promorulesId = $playerPromo->promorulesId;
							$promorules = $this->promorules->getPromoruleById($promorulesId);
							$promoName = $promorules['promoName'];
						}
					}

                    if($promoName == Promorules::SYSTEM_MANUAL_PROMO_CMS_NAME){
                        $promoName = lang('promo.'. $promoName);
                        $html = $promoName;
                    }else if( empty($promoName) ){
                        $html = lang('pay.noPromo');
                    }else{
                        $html = '<a href="#" data-toggle="modal" data-target="#promoDetails"  onclick="return viewPromoRuleDetails(' . $promorulesId . ',' . BaseModel::FALSE . ');">' . $promoName . '</a>';
                    }

  					if (!$is_export) {
  						return $html ?: '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
  					}else{
  						return $html ?: lang('lang.norecyet');
  					}
  				},
  			),
            array(
                'dt' => $i++, // #30
                'alias' => 'promoRequestId',
                'select' => 'sale_orders.player_promo_id',
                'name' => lang('Promo Request ID'),
                'formatter' => function ($d) use ($is_export)  {
                    if (!$is_export) {
                        return $d ?: '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
                    }else{
                        return $d ?: lang('lang.norecyet');
                    }
                },
            ),
  			array(
  				'dt' => $i++, // #31
  				'alias' => 'bonusAmount',
				// 'select' => 'playerpromo.bonusAmount',
				'select' => 'sale_orders.player_promo_id',
  				'name' => lang('pay.promobonus'),
  				'formatter' => function ($d) use ($is_export)  {
					$bonusAmount = 0;
					if( ! empty($d) ){
						$player_promo_id = $d;
						$playerPromo = $this->player_promo->getPlayerPromo($player_promo_id);
						if (!empty($playerPromo)) {
							$bonusAmount = $playerPromo->bonusAmount;
						}
					}

  					if (!$is_export) {
  						return '<strong>'.$this->utils->formatCurrencyNoSym($bonusAmount).'</strong>';
  					}else{
  						return $this->utils->formatCurrencyNoSym($bonusAmount);
  					}
  				},
  			),
  			array(
  				'dt' => $i++, // #32
				'alias' => 'paybus_order_id',
				'select' => 'sale_orders.paybus_order_id',
				'name' => lang('Paybus ID'),
				'formatter' => function ($d, $row) use ($is_export){
					if (!$is_export) {
						return $d ?: '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
					}else{
						return $d ?: lang('lang.norecyet');
					}
				},
			),
			array(
				'dt' => $i++, // #33
  				'alias' => 'external_order_id',
  				'select' => 'sale_orders.external_order_id',
  				'name' => lang('External ID'),
				'formatter' => function ($d, $row) use ($is_export, $customizationExternalId){
					if (!$is_export) {
						return $d ?: '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
					}else{
						if (is_array($customizationExternalId) && in_array($row['system_id'], $customizationExternalId)) {
							return $d ? 'A' . $d : lang('lang.norecyet');
						}
						return $d ?: lang('lang.norecyet');
					}
				},
  			),
  			array(
                'dt' =>  $i++, // #34 by OGP-26797
  				'alias' => 'bank_order_id',
  				'select' => 'sale_orders.bank_order_id',
  				'name' => lang('Bank Order ID'),
  				'formatter' => function ($d) use ($is_export)  {
  					if (!$is_export) {
  						return $d ?: '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
  					}else{
  						return $d ?: lang('lang.norecyet');
  					}
  				},
  			),
  			array(
				'dt' => $i++ , // #35
  				'alias' => 'player_deposit_time',
  				'select' => 'sale_orders.player_deposit_time',
  				'name' => lang('Deposit Datetime From Player'),
  				'formatter' => function ($d) use ($is_export)  {
  					if (!$is_export) {
  						return ($d != "0000-00-00 00:00:00") ? $d : '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
  					}else{
  						return ($d != "0000-00-00 00:00:00") ? $d : lang('lang.norecyet');
  					}
  				},
  			),
  			array(
				'dt' => $i++ , // #36
  				'alias' => 'player_mode_of_deposit',
  				'select' => 'sale_orders.player_mode_of_deposit',
  				'name' => lang('Mode of Deposit'),
  				'formatter' => function ($d) use ($is_export)  {
  					if (!$is_export) {
  						return $d ? lang($d) : '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
  					}else{
  						return $d ? lang($d) : lang('lang.norecyet');
  					}
  				},
  			),
			array(
				'dt' => $i++, // #37
				'alias' => 'content',
				'select' => 'sale_orders.id',
				'name' => lang('Player Deposit Note'),
				'formatter' => function ($sale_order_id) use ($is_export)  {
					$display_last_notes = false;
					$donotShowUsername = true;
					$allNotes = $this->sale_orders_notes->getNotesByNoteType(Sale_orders_notes::PLAYER_NOTES, $sale_order_id, $display_last_notes, $donotShowUsername);

					if (!$is_export) {
						return $allNotes ?: '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
					}else{
						return str_replace('</br>', "\n", $allNotes) ?: lang('lang.norecyet');
					}
				},
			),
			array(
				'dt' => $i++, // #38
				'alias' => 'processed_by_name',
				// 'select' => 'adminusers.username',
				'select' => 'sale_orders.processed_by',
				'name' => lang('pay.procssby'),
				'formatter' => function ($d) use ($is_export)  {
					$username = '';
					if( ! empty($d) ){
						$processed_by = $d;
						$username = $this->users->getUsernameById($processed_by);
					}

					if (!$is_export) {
						return !empty($username) ? $username: '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
					}else{
						return !empty($username) ? $username: lang('lang.norecyet');
					}
				},
			),
			array(
				'dt' => $i++, // #39
				'alias' => 'content',
				'select' => 'sale_orders.id',
				'name' => lang('External Note'),
				'formatter' => function ($sale_order_id) use ($is_export)  {
					$display_last_notes = false;
					$limit_the_number_of_words_displayed = true;
					$allNotes = $this->sale_orders_notes->getNotesByNoteType(Sale_orders_notes::EXTERNAL_NOTE, $sale_order_id, $display_last_notes, false, $limit_the_number_of_words_displayed);
					$exportNotes = $this->sale_orders_notes->getNotesByNoteType(Sale_orders_notes::EXTERNAL_NOTE, $sale_order_id, $display_last_notes, false, false);

					if (!$is_export) {
						return $allNotes ?: '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
					}else{
						return str_replace('</br>', "\n", $exportNotes) ?: lang('lang.norecyet');
					}
  				},
  			),
			array(
				'dt' => $i++, // #40
				'alias' => 'content',
				'select' => 'sale_orders.id',
				'name' => lang('Internal Note'),
				'formatter' => function ($sale_order_id) use ($is_export)  {
					$display_last_notes = false;
					$limit_the_number_of_words_displayed = true;
					$allNotes = $this->sale_orders_notes->getNotesByNoteType(Sale_orders_notes::INTERNAL_NOTE, $sale_order_id, $display_last_notes, false, $limit_the_number_of_words_displayed);
					$exportNotes = $this->sale_orders_notes->getNotesByNoteType(Sale_orders_notes::INTERNAL_NOTE, $sale_order_id, $display_last_notes, false,false);

					if (!$is_export) {
						return $allNotes ?: '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
					}else{
						return str_replace('</br>', "\n", $exportNotes) ?: lang('lang.norecyet');
					}
				},
			),
			array(
				'dt' => $i++, // #41
				'alias' => 'content',
				'select' => 'sale_orders.id',
				'name' => lang('Action Log'),
				'formatter' => function ($sale_order_id) use ($is_export)  {
					$display_last_notes = false;

					$allNotes = $this->sale_orders_notes->getNotesByNoteType(Sale_orders_notes::ACTION_LOG, $sale_order_id, $display_last_notes);

					if (!$is_export) {
						return $allNotes ?: '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
					}else{
						return str_replace('</br>', "\n", $allNotes) ?: lang('lang.norecyet');
					}
				},
			),
  		);
  		# END DEFINE COLUMNS #################################################################################################################################################


  		# START PROCESS SEARCH FORM #################################################################################################################################################
  		$where = array();
  		$values = array();

  		if( ! empty( $notDatatable ) ) $input = $request;

  		// $where[] = 'sale_orders.payment_kind = ?';
  		// $values[] = Sale_order::PAYMENT_KIND_DEPOSIT;

  		if (!empty($playerId)) {
  			$where[] = "sale_orders.player_id = ?";
  			$values[] = $playerId;
  		}

  		if (isset($input['username'])) {
  			$where[] = "player.username LIKE ?";
  			$values[] = '%' . $input['username'] . '%';
  		}

		$payment_flag=[];
		$payment_type_flag=[];
		if (isset($input['payment_flag_1']) || isset($input['payment_flag_2']) || isset($input['payment_flag_3']) || isset($input['payment_flag_4'])) {
			if(@$input['payment_flag_1']=='1'){
				$payment_flag[]=1;
			}
			if(@$input['payment_flag_2']=='1'){
				$payment_flag[]=2;
			}
			if(@$input['payment_flag_3']=='1'){
				$payment_flag[]=3;
			}
			if(@$input['payment_flag_4']=='1'){
				$payment_type_flag[]=2;
			}

            if ($this->utils->getConfig('hide_financial_account_ewallet_account_number')) {
				$condition_payment_account_flag = '';
				$condition_payment_type_flag = '';
	            if( ! empty($payment_type_flag)){
	                $condition_payment_type_flag = "banktype.payment_type_flag IN (".implode(',', $payment_type_flag).")";
	            }
	            if( ! empty($payment_flag)){
	                $condition_payment_account_flag = "payment_account.flag IN (".implode(',', $payment_flag).")";
	            }

	            if (!empty($condition_payment_account_flag) && !empty($condition_payment_type_flag)) {
					$where[] = "($condition_payment_account_flag OR $condition_payment_type_flag)";
	            }elseif (!empty($condition_payment_account_flag)) {
					$where[] = $condition_payment_account_flag;
	            }elseif (!empty($condition_payment_type_flag)) {
					$where[] = $condition_payment_type_flag;
	            }
	        }else{
				if( ! empty($payment_flag) && count($payment_flag)!=3){ /// for OGP-15228 Cannot export CSV under Payment (Deposit List)
	                $where[] = "payment_account.flag IN (".implode(',', $payment_flag).")";
	            }
	        }
  		}

  		if (isset($input['realname'])) {
  			$where[] = "CONCAT_WS(' ', playerdetails.firstName, playerdetails.lastName) LIKE ?";
  			$values[] = '%' . $input['realname'] . '%';
  		}

  		if (isset($input['affiliate'])) {
  			$where[] = "affiliates.username LIKE ?";
  			$values[] = '%' . $input['affiliate'] . '%';
  		}

  		if (isset($input['amount'])) {
  			$where[] = "sale_orders.amount = ?";
  			$values[] = $input['amount'];
  		}

  		if (isset($input['amount_from'])) {
  			$where[] = "sale_orders.amount >= ?";
  			$values[] = $input['amount_from'];
  		}

  		if (isset($input['amount_to'])) {
  			$where[] = "sale_orders.amount <= ?";
  			$values[] = $input['amount_to'];
  		}

		if (!empty($input['locked_user_id'])) {
			$where[] = "sale_orders.locked_user_id != ?";
			$values[] = '';
		}

		if (!empty($input['processed_by'])) {
			$where[] = "sale_orders.processed_by = ?";
			$values[] = $input['processed_by'];
		}

		$useIndexStr=null;
		if ($this->safeGetParam($input, 'enable_date') == '1') {

			/// for apply the timezone,
			// override the inputs, deposit_date_from and deposit_date_to.
			if( ! empty($input['timezone']) ){
				$default_timezone = $this->utils->getTimezoneOffset(new DateTime());
				$hours = $default_timezone - intval($input['timezone']);
				$date_from_str = $input['deposit_date_from'];
				$date_to_str = $input['deposit_date_to'];
				$by_date_from = new DateTime($date_from_str);
				$by_date_to = new DateTime($date_to_str);
				$by_date_from = new DateTime($date_from_str);
				$by_date_to = new DateTime($date_to_str);
				if($hours>0){
					$hours='+'.$hours;
				}

				$by_date_from->modify("".$hours." hours");
				$by_date_to->modify("".$hours." hours");
				$input['deposit_date_from'] = $this->utils->formatDateTimeForMysql($by_date_from);
				$input['deposit_date_to'] = $this->utils->formatDateTimeForMysql($by_date_to);
			}

			if (isset($input['deposit_date_from'], $input['deposit_date_to'], $input['search_time'])) {
				if($input['search_time'] =='1') {
					$where[] = "sale_orders.created_at >= ?";
					$where[] = "sale_orders.created_at <= ?";
					$useIndexStr='force index(idx_created_at)';
				}
				else if($input['search_time'] =='2'){
					$where[] = "sale_orders.updated_at >= ?";
					$where[] = "sale_orders.updated_at <= ?";
					$useIndexStr='force index(idx_updated_at)';
				}
				else if($input['search_time'] =='3'){
					$where[] = "sale_orders.process_time >= ?";
					$where[] = "sale_orders.process_time <= ?";
					$useIndexStr='force index(idx_process_time)';
				}
				$values[] = $input['deposit_date_from'];
				$values[] = $input['deposit_date_to'];
			}else{
				if (isset($input['deposit_date_from'], $input['deposit_date_to'])) {
					$where[] = "sale_orders.created_at >= ?";
					$where[] = "sale_orders.created_at <= ?";
					$values[] = $input['deposit_date_from'];
					$values[] = $input['deposit_date_to'];
					$useIndexStr='force index(idx_created_at)';
				}
			}
  	    }

		if (isset($input['dateRangeValueStart'], $input['dateRangeValueEnd'])) {
			$where[] = "sale_orders.created_at >= ?";
			$where[] = "sale_orders.created_at <= ?";
			$values[] = $input['dateRangeValueStart'];
			$values[] = $input['dateRangeValueEnd'];
			$useIndexStr='force index(idx_created_at)';
		}

		if( ! empty( $_where ) ) $where[] = $_where;

		if (isset($input['search_status'])) {
			if($input['searchBtn'] == '1'){
				if ($input['search_status'] != 'allStatus') {
                    if($this->utils->getConfig('enable_async_approve_sale_order') && 
                        $input['search_status'] == Sale_order::STATUS_SETTLED){
                        $where[] = "sale_orders.status IN (?,?,?)";
                        $values[] = Sale_order::STATUS_SETTLED;    
                        $values[] = Sale_order::STATUS_QUEUE_APPROVE;
                        $values[] = Sale_order::STATUS_TRANSFERRING;                            
                    }else{
                        $multiDwStatus = $input['search_status'];
                        $where[] = "sale_orders.status = ?";
                        $values[] = $multiDwStatus;    
                    }
				}       
			}
		}

		if(isset($input['dwStatus'])){
			switch ($input['dwStatus']) {
				case Sale_order::VIEW_STATUS_REQUEST_ALL:
				case Sale_order::VIEW_STATUS_REQUEST_TODAY:
				case Sale_order::VIEW_STATUS_REQUEST:
				case Sale_order::VIEW_STATUS_REQUEST_BANKDEPOSIT:
				case Sale_order::VIEW_STATUS_REQUEST_3RDPARTY:
					$where[] = "sale_orders.status IN (?,?)";
					$values[] = Sale_order::STATUS_PROCESSING;
					$values[] = Sale_order::STATUS_CHECKING;
					break;
				case Sale_order::VIEW_STATUS_APPROVED_ALL:
				case Sale_order::VIEW_STATUS_APPROVED_TODAY:
				case Sale_order::VIEW_STATUS_APPROVED:
                    if($this->utils->getConfig('enable_async_approve_sale_order')){
                        $where[] = "sale_orders.status IN (?,?,?,?)";
                        $values[] = Sale_order::STATUS_BROWSER_CALLBACK;
                        $values[] = Sale_order::STATUS_SETTLED;
                        $values[] = Sale_order::STATUS_QUEUE_APPROVE;
                        $values[] = Sale_order::STATUS_TRANSFERRING;
                    }else{
                        $where[] = "sale_orders.status IN (?,?)";
                        $values[] = Sale_order::STATUS_BROWSER_CALLBACK;
                        $values[] = Sale_order::STATUS_SETTLED;
                    }					
					break;
				case Sale_order::VIEW_STATUS_DECLINED_ALL:
				case Sale_order::VIEW_STATUS_DECLINED_TODAY:
				case Sale_order::VIEW_STATUS_DECLINED:
					$where[] = "sale_orders.status = ?";
					$values[] = Sale_order::STATUS_DECLINED;
					break;
			}
		}

		if (isset($input['excludeTimeout'], $input['search_status'])) {
			if($input['search_status'] == Sale_order::STATUS_PROCESSING) {
				$where[] = "(sale_orders.timeout_at > ? OR sale_orders.timeout_at IS NULL)";
				$values[] = $this->utils->getNowForMysql();
			}
  		}

  		if (isset($input['secure_id'])) {
  			$where[] = "sale_orders.secure_id = ?";
  			$values[] = $input['secure_id'];
  		}

		if (isset($input['paybus_order_id']) && !empty($input['paybus_order_id'])) {
			$where[] = "sale_orders.paybus_order_id = ?";
			$values[] = $input['paybus_order_id'];
		}

  		if (isset($input['external_order_id']) && !empty($input['external_order_id'])) {
  			$where[] = "sale_orders.external_order_id = ?";
  			$values[] = $input['external_order_id'];
  		}

  		if (isset($input['bank_order_id']) && !empty($input['bank_order_id'])) {
  			$where[] = "sale_orders.bank_order_id = ?";
  			$values[] = $input['bank_order_id'];
  		}

  		$select_all_payment_account=@$input['select_all']=='true';

  		if(!$select_all_payment_account){

	  		$payment_account_id_arr=[];

			$payment_account_list = $this->payment_account->getAllPaymentAccountDetails();
			if(is_array($payment_account_list)){
				foreach ($payment_account_list as $payment_account) {
					$id=$payment_account->payment_account_id;
					$key='payment_account_id_'.$id;
					if($select_all_payment_account || @$input[$key]=='true'){
						$payment_account_id_arr[]=intval($id);
					}
				}
			}

			if(!empty($payment_account_id_arr)){
				$where[] = "sale_orders.payment_account_id in (".implode(',',$payment_account_id_arr).")";
			}
  		}

		if (isset($input['tag_list_included'])) {
            $tag_list = $input['tag_list_included'];
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
        } // EOF if (isset($input['tag_list_included'])) {...


        // to see test player deposit on their user information page
        if(empty($playerId)){
        	$where[] = "player.deleted_at IS NULL";
        }

  		# END PROCESS SEARCH FORM #################################################################################################################################################

		$columns = $this->checkIfEnabled($this->utils->isEnabledFeature('enable_deposit_datetime'), array('player_deposit_time'), $columns);

		if($is_export){
            $this->data_tables->options['is_export']=true;
            if(empty($csv_filename)){
                $csv_filename=$this->utils->create_csv_filename(__FUNCTION__);
            }
            $this->data_tables->options['csv_filename']=$csv_filename;
		}

		$table = 'sale_orders';
		if($this->utils->getConfig('force_index_on_saleorders') && !empty($useIndexStr)){
			$table.=' '.$useIndexStr;
		}
  		$joins = array(
  			'player' => 'player.playerId = sale_orders.player_id',
  			'crypto_deposit_order' => 'crypto_deposit_order.sale_order_id = sale_orders.id',
			// 'payment_account' => 'sale_orders.payment_account_id = payment_account.id',
			// 'banktype' => 'payment_account.payment_type_id = banktype.bankTypeId',
  			// 'playerdetails' => 'playerdetails.playerId = sale_orders.player_id',
  			// 'adminusers' => 'sale_orders.processed_by = adminusers.userId',
  			// 'playerpromo' => 'playerpromo.playerpromoId = sale_orders.player_promo_id',
  			// 'promorules' => 'playerpromo.promorulesId = promorules.promorulesId',
  			// 'transactions' => 'transactions.id = sale_orders.transaction_id',
  			// 'payment_account' => 'payment_account.id = sale_orders.payment_account_id', // remove for dynamic join
  			// 'affiliates' => 'affiliates.affiliateId = player.affiliateId',
		  );

  		#add join player details if real name was set
		if (isset($input['realname'])) {
  			$joins['playerdetails'] = 'playerdetails.playerId = sale_orders.player_id';
  		}

  		#add join affiliate details if affiliate  was set
  		if (isset($input['affiliate'])) {
  			$joins['affiliates'] = 'affiliates.affiliateId = player.affiliateId';
  		}



		if (isset($input['referrer'])) {
			$joins['player referrer'] = 'player.refereePlayerId = referrer.playerId';
			$where[] = "referrer.username = ?";
  			$values[] = $input['referrer'];
		}

		$innerJoins=[];
		$innerJoins[] = 'player'; // the data must be have.

		$joins['payment_account'] = 'payment_account.id = sale_orders.payment_account_id';
		$innerJoins[] = 'payment_account';

		if ($this->utils->getConfig('hide_financial_account_ewallet_account_number')) {
			$joins['banktype'] = 'payment_account.payment_type_id = banktype.bankTypeId';
		}

		$group_by=[];
		$having=[];
		$distinct=false;
		$external_order=[];
		$countOnlyField='sale_orders.id';

		$result = $this->data_tables->get_data($request, $columns, $table, $where, $values, $joins,
			$group_by, $having, $distinct, $external_order, $notDatatable, $countOnlyField, $innerJoins);
		$sql = $this->data_tables->last_query;
		$benchmarkInfo = '';
		$this->utils->markProfilerEndAndPrint($markName, $benchmarkInfo );
        if( ! empty($benchmarkInfo)
            && is_array($result) // Patch for the Warning, "Cannot assign an empty string to a string offset"
        ){
            $result['benchmark'] = $benchmarkInfo;
        }
		if($is_export){
			if( ! empty($sql) ){
				$this->utils->debug_log(__METHOD__, 'depositList SQL', $sql);
			}
		    //drop result if export
			return $csv_filename;
		}
		if( ! empty($sql) ){
			$result['last_query'] = $sql;
		}
  		return $result;
}

	/**
	 * detail: get withdraw lists of certain player
	 *
	 * @param int $playerId walletaccount playerId
	 * @param Boolean $enabledAction
	 * @param array $request
	 * @param Boolean $is_export
	 * @return  array
	 */
	public function withdrawList($playerId = null, $enabledAction = 'true', $request, $is_export = false, $is_locked = false, $csv_filename=null, $status_permission = null, $playerDetailPermissions = null) {

		$this->load->model(array('wallet_model', 'transaction_notes', 'payment_account', 'common_category', 'operatorglobalsettings', 'walletaccount_notes','walletaccount_timelog','users','player_model', 'ip_tag_list'));
		$this->load->library(array('data_tables', 'payment_library'));
		$this->load->helper(['player_helper']);

		$input = $this->data_tables->extra_search($request);

		$common_category = $this->common_category;
		$permWithdrawDetail=true;
		$dwStatus = isset($input['dwStatus']) ? $input['dwStatus'] : '';
		$enabledAction = $enabledAction == 'true';

		//use the currency settings.
		$currency_settings = $this->utils->getCurrentCurrency()['currency_code'];
		$processedOnCustomStage = $this->utils->getConfig('enable_processed_on_custom_stage_time');
		$withdrawlFeeFromPlayer = $this->utils->getConfig('enable_withdrawl_fee_from_player');
		$enable_split_player_username_and_affiliate = $this->utils->getConfig('enable_split_player_username_and_affiliate');
		$enable_total_player_withdrawal_requests = $this->utils->getConfig('enable_total_player_withdrawal_requests');
		$enable_total_ip_withdrawal_requests = $this->utils->getConfig('enable_total_ip_withdrawal_requests');
		$enable_crypto_details = !empty($this->utils->getConfig('enable_crypto_details_in_crypto_bank_account'))?true:false;

		if($this->utils->getConfig('enable_cpf_number')){
			$enable_cpf_number = true;
		}else{
			$enable_cpf_number = false;
		}

		if(is_array($this->config->item('cryptocurrencies'))){
  			$enabled_crypto = true;
  		}else{
  			$enabled_crypto = false;
  		}

		$customStage = [];
        $stages = $this->operatorglobalsettings->getCustomWithdrawalProcessingStage();
        foreach ($stages as $key => $value) {
            if(is_array($value) && array_key_exists('enabled', $value)){
                if(is_int($key)){
                    $customStage['CS'.$key] = $value['name'];
                }
            }
        }

        $customStageCount = 0;
		for ($i = 0; $i < count($stages); $i++) {
			if (array_key_exists($i, $stages)) {
				$customStageCount += ($stages[$i]['enabled'] ? 1 : 0);
			}
		}

		if(!$is_export){
			$getSearchStatusPermission = $this->payment_library->getWithdrawalAllStatusPermission($stages, $customStageCount);
		}

		if(!empty($status_permission)){
			$getSearchStatusPermission = $status_permission;
		}
		$this->utils->debug_log('----------------------withdrawList getSearchStatusPermission', $getSearchStatusPermission);

		$enabled_lock_trans_by_singel_role = false;
		if ($this->utils->getConfig('enabled_lock_trans_by_singel_role')) {
			$enabled_lock_trans_by_singel_role = true;
		}

		# START DEFINE COLUMNS #################################################################################################################################################
		$i = 0;
		$columns = array(
			array(
				'alias' => 'playerId',
				'select' => 'player.playerId',
			),
			array(
				'alias' => 'is_checking',
				'select' => 'walletaccount.is_checking',
			),
			array(
				'alias' => 'aff_username',
				'select' => 'affiliates.username',
			),
			array(
				'alias' => 'registrationIP',
				'select' => 'playerdetails.registrationIP',
			),

			array(
				'dt' => ($enabledAction && ($permWithdrawDetail || $dwStatus != 'request' && $dwStatus != 'pending_review')) ? $i++ : NULL,
				'alias' => 'walletAccountId',
				'select' => 'walletaccount.walletAccountId',
				'name' => lang('column.id'),
				'formatter' => function ($d, $row) use ($permWithdrawDetail, $enabledAction, $is_export, $is_locked, $enabled_lock_trans_by_singel_role) {
					if($is_locked) {
						return '<input type="checkbox" name="wallet_account_id" value="'.$d.'">';
					}

					if (!$is_export) {
						if ($permWithdrawDetail && $enabledAction) {

							$btnStyle = 'btn-info';

							if ($enabled_lock_trans_by_singel_role) {
								$lockedByUserId = $this->wallet_model->checkWithdrawLocked($d);
								if ($lockedByUserId) {
									//locked by opreator,
									$btnStyle = 'btn-danger';
								}else{
									//unlock and apid or declined
									if ($row['dwStatus'] == Wallet_model::DECLINED_STATUS || $row['dwStatus'] == Wallet_model::PAID_STATUS) {
										$btnStyle = 'btn-info';
									}else{
										//not being done and not locked by opreator
										$btnStyle = 'btn-success';
									}
								}
							}

							$output = '<div class="clearfix" style="width:65px;">';
							$output_checckbox = $this->utils->isEnabledFeature("enable_batch_withdraw_process_apporve_decline") ?
							'<div class="col-md-3" style="padding:5px 1px 0 2px"><input type="checkbox" class="chk-order-id" data-player_id="' .$row['playerId']. '" data-dwstatus="' .$row['dwStatus']. '" data-withdrawcode="' .$row['withdrawCode']. '" name="wallet_account_id" value="'.$d.'" id="withdrawId_' . $d . '"></div>' : '<div style="display: none;" data-player_id="' .$row['playerId']. '" data-dwstatus="' .$row['dwStatus']. '" data-withdrawcode="' .$row['withdrawCode']. '" name="wallet_account_id" value="'.$d.'" id="withdrawId_' . $d . '"></div>';

							$output .= $output_checckbox;

							$output .= '<div class="col-md-9" style="padding:0 2px 0 2px"><span class="btn btn-xs review-btn ' . $btnStyle . '" data-toggle="modal" onclick="';
							if ($row['dwStatus'] == Wallet_model::APPROVED_STATUS || $row['dwStatus'] == Wallet_model::PAY_PROC_STATUS) {
								$output .= 'getWithdrawalApproved(' . $row['walletAccountId'] .',' .APPROVED_MODAL.','.$row['playerId'].')" >';		// data-target="#approvedDetailsModal"
							} else if ($row['dwStatus'] == Wallet_model::DECLINED_STATUS || $row['dwStatus'] == Wallet_model::PAID_STATUS) {
								# Decline and Paid use a same modal
								$output .= 'getWithdrawalDeclined(' . $row['walletAccountId'] . ',' . $row['playerId'] . ',' .DECLINED_MODAL.')" >';		// data-target="#declinedDetailsModal"
								// } else if ($row['dwStatus'] == Wallet_model::REQUEST_STATUS) {
								// 	$output .= 'getWithdrawalRequest(' . $row['walletAccountId'] . ',' . $row['playerId'] . ')" data-target="#requestDetailsModal">';
							} else {
								// Determines the target modal to be showed
								$customWithdrawalSetting = $this->operatorglobalsettings->getCustomWithdrawalProcessingStage();
								$useApprovedModal = false;

								if ($customWithdrawalSetting['maxCSIndex'] == -1) {
									$useApprovedModal = true;
								} else if (substr($row['dwStatus'], 0, 2) == 'CS') {
									$currentCSIndex = intval(substr($row['dwStatus'], 2));
									$useApprovedModal = $currentCSIndex >= $customWithdrawalSetting['maxCSIndex'];
								}

								if ($useApprovedModal) {
									#$output .= 'getWithdrawalRequest(' . $row['walletAccountId'] . ',' . $row['playerId'] . ',' .APPROVED_MODAL.')" >';	// data-target="#approvedDetailsModal"
									$output .= 'getWithdrawalRequest(' . $row['walletAccountId'] . ',' . $row['playerId'] . ',' .APPROVED_MODAL.')" >'; // data-target="#approvedDetailsModal"
								} else {
									if(($row['dwStatus'] == Wallet_model::PENDING_REVIEW_CUSTOM_STATUS && $this->utils->isEnabledFeature("enable_pending_vip_show_3rd_and_manualpayment_btn"))){
										$output .= 'getWithdrawalRequest(' . $row['walletAccountId'] . ',' . $row['playerId'] . ',' .APPROVED_MODAL.')" >'; // data-target="#approvedDetailsModal"
									}else{
										$output .= 'getWithdrawalRequest(' . $row['walletAccountId'] . ',' . $row['playerId'] . ',' .REQUEST_MODAL.')" >'; // data-target="#requestDetailsModal"
									}
								}
							}
							$output .= lang("lang.details");
							$output .= '</span></div></div>';
							return $output;
						} else {
							return '';
						}

				}else{
					return $d;
				}
			},
			),
			array(
                'dt' => $i++,
                'alias' => 'dwStatus',
                'select' => 'walletaccount.dwStatus',
                'name' => lang('lang.status'),
                'formatter' => function ($d, $row) use ($is_export, $customStage) {
                    if (!$is_export) {
						if ($d == Wallet_model::PENDING_REVIEW_STATUS) {
							if($this->utils->isEnabledFeature("enable_withdrawal_pending_review") && $this->permissions->checkPermissions('view_pending_review_stage')){
								return '<strong class="text-muted">' . lang('st.pendingreview') . '</strong>';
							}else {
								return '<span class="text-muted">' . lang('role.nopermission') . '</span>';
							}
						}

						if ($d == Wallet_model::PAY_PROC_STATUS) {
                            return '<span class="text-success">' . lang('st.processing') . '</span>';
						}

						if ($d == Wallet_model::PENDING_REVIEW_CUSTOM_STATUS) {
							if($this->utils->getConfig('enable_pending_review_custom')){
	                            return '<span class="text-muted">' . lang('st.pendingreviewcustom') . '</span>';
							}else {
								return '<span class="text-muted">' . lang('role.nopermission') . '</span>';
							}
						}

						if ($d == Wallet_model::LOCK_API_UNKNOWN_STATUS) {
                            return '<span class="text-success">' . lang('st.lockedapirequest') . '</span>';
                        }

						if ($d == Wallet_model::REQUEST_STATUS ) {
							return lang('st.pending');
                        } else if ($d == Wallet_model::PAID_STATUS || $d == Wallet_model::APPROVED_STATUS) {
                            return '<span class="text-success">' . lang('st.paid') . '</span>';
                        } else if ($d == Wallet_model::DECLINED_STATUS) {
                            return '<span class="text-danger">' . lang('st.declined') . '</span>';
                        } else if(!empty($customStage)){
                            return $customStage[$d];
                        }
                    }else{
						if ($d == Wallet_model::PENDING_REVIEW_STATUS) {
							if($this->utils->isEnabledFeature("enable_withdrawal_pending_review") && $this->permissions->checkPermissions('view_pending_review_stage')){
								return lang('st.pendingreview');
							}else {
								return lang('role.nopermission');
							}
						}

						if ($d == Wallet_model::PENDING_REVIEW_CUSTOM_STATUS) {
							if($this->utils->getConfig('enable_pending_review_custom')){
	                            return  lang('st.pendingreviewcustom');
							}else {
								return  lang('role.nopermission');
							}
						}

                        if ($d == Wallet_model::PAY_PROC_STATUS) {
                            return lang('st.processing');
						}

						if ($d == Wallet_model::LOCK_API_UNKNOWN_STATUS) {
                            return lang('st.lockedapirequest');
						}

                        if ($d == Wallet_model::REQUEST_STATUS) {
                            return lang('st.pending');
                        } else if ($d == Wallet_model::PAID_STATUS || $d == Wallet_model::APPROVED_STATUS) {
                            return lang('st.paid');
                        } else if ($d == Wallet_model::DECLINED_STATUS) {
                            return lang('st.declined');
                        } else if(!empty($customStage)){
                            return $customStage[$d];
                        }
                    }
                },
            ),
            array(
                'dt' => $i++,
                'alias' => 'withdrawCode',
                'select' => 'walletaccount.transactionCode',
                'name' => lang('Withdraw Code'),
                'formatter' => function ($d,$row) use ($is_export,$dwStatus)  {
                    if (!$is_export) {
                    	if($this->utils->isEnabledFeature('highlight_withdrawal_code_in_list')) {
  							if(!empty($d)){
  								$withdraw_code_highlight_sequence = $this->utils->getConfig('withdraw_code_highlight_sequence');
	  							if(!empty($withdraw_code_highlight_sequence)){
	  								switch ($dwStatus) {
						  				case Wallet_model::REQUEST_STATUS:
					  						foreach ($withdraw_code_highlight_sequence as $key => $value) {
					  							if(isset($value['timeout']) && isset($value['background_color'])){
					  								if($this->utils->isTimeoutNow($row['createdOn'], $value['timeout'])){
					  									return '<span style="background-color: ' . $value['background_color'] . '; color:white">' . $d . '</span>';
					  								}
					  							}
					  						}
						  					break;
						  				default:
						  					return '<span>' . $d . '</span>';
						  					break;
						  			}
	  							}
  							}
						}
                        return $d ?: '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
                    }else{
                        return $d ?: lang('lang.norecyet');
                    }
                },
            ),

            array(
                'dt' => $i++,
                'alias' => 'lockedStatus',
                'select' => 'ad.username',
                'name' => lang('Locked Status'),
                'formatter' => function($d){
                    return !empty($d) ? $d : 'N/A';
                }
            ),
			array(
                'dt' => $i++,
                'alias' => 'risk_check_status',
                'select' => 'walletaccount.transactionCode',
                'name' => lang('Risk Check Status'),
                'formatter' => function($d) use ($is_export) {
					$this->load->library(array('auto_risk_dispatch_withdrawal_lib'));
					$this->load->model(['dispatch_withdrawal_results']);
					$theTransCode = $d;
					$risk_check_status = '';
					$isConditionMet = null;
// $this->utils->debug_log('TT-5404.8708');
					$_result = $this->auto_risk_dispatch_withdrawal_lib->getLatestResultsByTransCode($theTransCode);
// $this->utils->debug_log('TT-5404.8708.8710');
					if(!empty($_result['definition_results']) ){
						/// Proceeded Failed in
						// dispatch_withdrawal_results.after_status = null
						// dispatch_withdrawal_results.result_dw_status varchar(255)
						if( ! empty($_result) ){
							if($_result['definition_results']['finallyResult'] == true){
								$isConditionMet = true;
								$risk_check_status = lang('Condition Met');
							}else{
								$isConditionMet = false;
								$risk_check_status = lang('Condition Not Met');
							}
						}

						$result_dw_status_code = null;
						$result_dw_status_plain_txt = null;
						if( is_null($_result['after_status']) && $isConditionMet == true){
							/// the dw_status should be chenged by met the condition
							// But No changed by auto risk checker
							// that is why Need to get more detail for get the reason.
							$risk_check_status = lang('Proceeded Failed');

							if( ! empty($_result['result_dw_status_code']) ){
								$result_dw_status_code = $_result['result_dw_status_code']; // ref. to Dispatch_withdrawal_results::RESULT_DW_STATUS_CODE_XXX
							}else if( ! empty($_result['result_dw_status_plain_txt']) ){
								$result_dw_status_plain_txt = $_result['result_dw_status_plain_txt']; // for old value, only text.
							}
							if( ! empty($result_dw_status_code) ){
								$risk_check_status = sprintf('<span data-result_dw_status_code="%s">%s</span>', $result_dw_status_code, $risk_check_status);
							}
							if(! empty($result_dw_status_plain_txt) ){
								$risk_check_status = sprintf('<span data-result_dw_status_plain_txt="%s">%s</span>', htmlspecialchars($result_dw_status_plain_txt, ENT_QUOTES), $risk_check_status);
							}
						}

						unset($_result); // clear for Allowed memory size exhausted
					}else{
						// other Failed. thats should be the auto risk checker not yet begun to check the order.
						$risk_check_status = lang('Waiting for Proceeded');
					}

					if($is_export){
						$risk_check_status = strip_tags($risk_check_status);
					}

                    return !empty($risk_check_status) ? $risk_check_status : lang('N/A');
					/// Status	Description
					// Processing	The risk check is still in the queue or processing
					// Manually Stopped	The risk check is manually stopped by clicking the stop button
					// Condition Not Met	The risk check is done, by order does not pass the condition
					// Proceeded Failed	The risk check is failed due to the system error or issue
                }
            ),
			array(
				'dt' => $i++,
				'alias' => 'username',
				'select' => 'player.username',
				'name' => lang('pay.username'),
				'formatter' => function ($d, $row) use ($is_export, $enable_split_player_username_and_affiliate)  {
					if (!$is_export) {
						if (!$enable_split_player_username_and_affiliate) {
							if(!empty($row['aff_username'])){
								$d=$d.' ('.$row['aff_username'].')';
							}
						}
						return sprintf('<a href="/player_management/userInformation/%s">%s</a>', $row['playerId'], $d);
					}else{
						return $d;
					}
				},
			),
			array(
				'dt' => ($enable_crypto_details) ? $i++ : null ,
				'alias' => 'crypto_username',
				'select' => 'playercryptobankdetails.crypto_username',
				'name' => lang('financial_account.cryptousername.list'),
				'formatter' => function($d){
                    return !empty($d) ? $d : 'N/A';
                }
			),
			array(
				'dt' => ($enable_crypto_details) ? $i++ : null ,
				'alias' => 'crypto_email',
				'select' => 'playercryptobankdetails.crypto_email',
				'name' => lang('financial_account.cryptoemail.list'),
				'formatter' => function($d){
                    return !empty($d) ? $d : 'N/A';
                }
			),
			array(
				'dt' => $enable_split_player_username_and_affiliate ? $i++ : null ,
				'alias' => 'affiliate',
				'select' => 'walletaccount.playerId',
				'name' => lang('Affiliate'),
				'formatter' => function ($d)  use ($is_export)  {
					$affiliate_username = '';
					if( ! empty($d) ) {
						$player_id = $d;
						$player = $this->player_model->getPlayerById($player_id);
						if( ! empty($player->affiliateId ) ){
							$affiliate = $this->affiliate->getAffiliateById($player->affiliateId);
							$affiliate_username = $affiliate['username'];
						}
					}

					if (!$is_export) {
						return !empty($affiliate_username) ? $affiliate_username : '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
					}else{
						return !empty($affiliate_username) ? $affiliate_username : 'N/A';
					}
				},
			),
			array(
				'dt' => $i++,
				'alias' => 'createdOn',
				'select' => 'walletaccount.dwDatetime',
				'name' => lang('pay.reqtime'),
				'formatter' => 'defaultFormatter',
			),
			array(
				'dt' => $i++,
				'alias' => 'processTime',
				'select' => '(select walletaccount_timelog.create_date from walletaccount_timelog where walletaccount_timelog.walletAccountId = walletaccount.walletAccountId and walletaccount_timelog.after_status = "'.Wallet_model::PAY_PROC_STATUS.'" ORDER BY walletaccount_timelog.create_date DESC limit 1)',
				'name' => lang('pay.proctime'),
				'formatter' => function ($processTime) use ($is_export) {

					#OGP-18476 use sub select search walletaccount_timelog create_date (processTime)
					#$note = $this->walletaccount_timelog->getWalletAccountTimeLogByWalletAccountId($walletAccountId, Wallet_model::PAY_PROC_STATUS);

					if(!empty($processTime)){
						return date('Y-m-d H:i:s', strtotime($processTime));
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
				'dt' => ($processedOnCustomStage) ? $i++ : NULL,
				'alias' => 'processedOnCustomStage',
				'select' => 'walletaccount.walletAccountId',
				'name' => lang('pay.procstagetmie'),
				'formatter' => function ($walletAccountId) use ($is_export, $stages) {
					$dwStatus = $this->wallet_model->getWalletAccountStatus($walletAccountId);

					if(substr($dwStatus,0, 2) == 'CS'){
						$note = $this->walletaccount_timelog->getWalletAccountTimeLogByWalletAccountId($walletAccountId, $dwStatus);
					}else{
						foreach ($stages as $key => $value) {
				            if(is_array($value) && array_key_exists('enabled', $value)){
				                if(is_int($key) && $value['enabled']){
									$dwStatus = 'CS'.$key;
				                    break;
				                }
				            }
				        }
				        $note = $this->walletaccount_timelog->getWalletAccountTimeLogByWalletAccountId($walletAccountId, $dwStatus);
					}

					if(!empty($note)){
						return date('Y-m-d H:i:s', strtotime($note['create_date']));
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
				'alias' => 'paidTime',
				'select' => 'walletaccount.walletAccountId',
				'name' => lang('pay.paidtime'),
				'formatter' => function ($walletAccountId) use ($is_export) {

					$note = $this->walletaccount_timelog->getWalletAccountTimeLogByWalletAccountId($walletAccountId, Wallet_model::PAID_STATUS);

					if(!empty($note)){
						return date('Y-m-d H:i:s', strtotime($note['create_date']));
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
				'alias' => 'dwDatetime',
				'select' => 'walletaccount.spent_time',
				'name' => lang('pay.spenttime'),
				'formatter' => function ($spent_time, $row) use ($is_export) {
					if($row['dwStatus'] == Wallet_model::PAID_STATUS){
						$day    = floor($spent_time/86400);
						$hour   = floor($spent_time%86400/3600);
						$minute = floor($spent_time%86400%3600/60);
						$second = floor($spent_time%86400%3600%60);
						$timeString = sprintf("%d s", $second);
						if($minute > 0){
							$timeString = sprintf("%d m %d s", $minute, $second);
						}
						if($hour > 0){
							$timeString = sprintf("%d h %d m %d s", $hour, $minute, $second);
						}
						if($day > 0){
							$timeString = sprintf("%d d %d h %d m %d s", $day, $hour, $minute, $second);
						}
						return $timeString;
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
  				'alias' => 'real_name',
  				'select' => 'CONCAT(ifnull(playerdetails.firstName, ""), \' \', ifnull(playerdetails.lastName, "") )',
  			    'name' => lang('sys.vu40'),
  				'formatter' => function ($d) use ($is_export)  {
  					if (!$is_export) {
  						return  ($d == " ") ?  '<i class="text-muted">' . lang('lang.norecyet') . '</i>' : $d;
  					}else{
  						return ($d == " ") ? lang('lang.norecyet')  :  $d ;
  					}
  				},
  			),
  			array(
                'dt' => $i++,
                'alias' => 'group_level',
                'select' => 'player.levelName',
                'name' => lang('pay.playerlev'),
                'formatter' => function ($d, $row) {
					$this->load->model(['vipsetting','walletaccount_additional']);
					$player_id = $row['playerId'];
					$walletAccountId = $row['walletAccountId'];
					$the_additional = $this->walletaccount_additional->getDetailByWalletAccountId($walletAccountId);
					$sprintf_format = '%s - %s'; // params: groupName, vipLevelName
					$groupName = lang('N/A'); // defaults
					$vipLevelName = lang('N/A'); // defaults
					if( ! empty($the_additional['vip_level_info']) ){
						$vip_level_info = json_decode($the_additional['vip_level_info'], true);
					}else{
						$vip_level_info = $this->vipsetting->getVipGroupLevelInfoByPlayerId($player_id);
					}
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
				'alias' => 'tag',
				'select' => 'player.playerId',
				'name' => lang('Tag'),
				'formatter' => function ($d, $row) use ($is_export)  {
					return player_tagged_list($row['playerId'], $is_export);
				},
			),
			array(
				'dt' => $i++,
				'alias' => 'amount',
				'select' => 'walletaccount.amount',
				'name' => lang('pay.withamt'),
				'formatter' => function ($d) use ($is_export)  {
					if (!$is_export) {
						return '<strong>'.$this->utils->formatCurrencyNoSym($d).'</strong>';
					}else{
						return $this->utils->formatCurrencyNoSym($d);
					}
				},
			),
			array(
				'dt' => ($withdrawlFeeFromPlayer)? $i++ : NULL,
				'alias' => 'withdrawal_fee_amount',
				'select' => 'walletaccount.withdrawal_fee_amount',
				'name' => lang('transaction.transaction.type.43'),
				'formatter' => function ($d) use ($is_export)  {
					if (!$is_export) {
						return '<strong>'.$d.'</strong>';
					}else{
						return $d;
					}
				},
			),
			array(
				'dt' => ($enabled_crypto)? $i++ : NULL,
				'alias' => 'transfered_crypto',
				'select' => 'crypto_withdrawal_order.transfered_crypto',
				'name' => lang('Transfered crypto'),
				'formatter' => function ($d) use ($is_export)  {
					if (!$is_export) {
  						return $d ? '<strong>'.$d.'</strong>' : '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
  					}else{
  						return $d ? : lang('lang.norecyet');
  					}
				},
			),
			array(
				'dt' => $i++,
				'alias' => 'bankName',
				'select' => 'IF(ISNULL(walletaccount.bankName), banktype.bankName, walletaccount.bankName)',
				'name' => lang('pay.bankname'),
				'formatter' => function ($d) use ($is_export)  {
					if (!$is_export) {
						return $d ? lang($d) : '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
					}else{
						return $d ? lang($d) : lang('lang.norecyet');
					}
				},
			),
			array(
				'dt' => $i++,
				'alias' => 'bankAccountFullName',
				'select' => 'IF(ISNULL(walletaccount.bankAccountFullName), playerbankdetails.bankAccountFullName, walletaccount.bankAccountFullName)',
				'name' => lang('pay.acctname'),
				'formatter' => function ($d) use ($is_export)  {
					if (!$is_export) {
						return $d ?: '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
					}else{
						return $d ?: lang('lang.norecyet');
					}
				},
			),
			array(
				'dt' => $i++,
				'alias' => 'bankAccountNumber',
				'select' => 'IF(ISNULL(walletaccount.bankAccountNumber), playerbankdetails.bankAccountNumber, walletaccount.bankAccountNumber)',
				'name' => lang('pay.acctnumber'),
				'formatter' => function ($d) use ($is_export)  {
					if (!$is_export) {
						return $d ?: '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
					}else{
						return $d ?: lang('lang.norecyet');
					}
				},
			),
			array(
				'dt' => ($enable_cpf_number)? $i++ : NULL,
  				'alias' => 'cpf_number',
				'select' => 'walletaccount.playerId',
				'name' => lang('financial_account.CPF_number'),
  				'formatter' => function ($d)  use ($is_export, $playerDetailPermissions)  {
					$cpf_number = '';
					if(!empty($d)){
						$player_id = $d;
						$playerDetails = $this->player_model->getPlayerDetails($player_id);
						$cpf_number = (isset($playerDetails[0]) && !empty($playerDetails[0]['pix_number']))? $playerDetails[0]['pix_number'] : '';
						if(!empty($cpf_number)){
							if(!$playerDetailPermissions['player_cpf_number']){
								$cpf_number = $this->utils->keepOnlyString($cpf_number, -3);
							}
						}
					}

  					if (!$is_export){
  						if(!empty($cpf_number)){
  							return $cpf_number;
  						}else{
  							return '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
  						}
  					}else{
  						if(!empty($cpf_number)){
  							return $cpf_number;
  						}else{
  							return 'N/A';
  						}
  					}
  				},
  			),
			array(
				'dt' => $i++,
				'alias' => 'payment_type_flag',
				'select' => 'banktype.payment_type_flag',
				'name' => lang('pay.payment_account_flag'),
				'formatter' => function ($d) {
					$payment_flag = lang('pay.payment_type_bank');
  					switch ($d) {
	  					case Payment_account::PAYMENT_TYPE_FLAG_BANK:
	  						$payment_flag = lang('pay.payment_type_bank');
	  						break;
	  					case Payment_account::PAYMENT_TYPE_FLAG_EWALLET:
	  						$payment_flag = lang('pay.payment_type_ewallet');
	  						break;
	  					case Payment_account::PAYMENT_TYPE_FLAG_CRYPTO:
	  						$payment_flag = lang('pay.payment_type_crypto');
	  						break;
	  					case Payment_account::PAYMENT_TYPE_FLAG_API:
	  						$payment_flag = lang('pay.payment_type_api');
	  						break;
	  					case Payment_account::PAYMENT_TYPE_FLAG_PIX:
	  						$payment_flag = lang('pay.payment_type_pix');
	  						break;
  					}
  					return $payment_flag;
				},
			),
			array(
				'dt' => $i++,
				'alias' => 'branch',
				'select' => 'IF(ISNULL(walletaccount.bankBranch), playerbankdetails.branch, walletaccount.bankBranch)',
				'name' => ($this->utils->getConfig('use_branch_as_ifsc_in_withdrawal_accounts') ? lang('financial_account.ifsc') : lang('pay.acctbranch')),
				'formatter' => function ($d) use ($is_export)  {
					if (!$is_export) {
						return $d ?: '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
					}else{
						return $d ?: lang('lang.norecyet');
					}
				},
			),
			array(
				'dt' => $i++,
				'alias' => 'declined_category',
				'select' => 'walletaccount.withdrawal_declined_category_id',
				'name' => lang('Withdrawal Declined Category'),
				'formatter' => function ($d) use ($is_export,$common_category)  {
					if (!$is_export) {
						return $d ? lang($common_category->getCategoryInfoById($d)['category_name']): '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
					}else{
						return $d ? lang($common_category->getCategoryInfoById($d)['category_name']): lang('lang.norecyet');
					}
				},
			),
			array(
				'dt' => $i++,
				'alias' => 'province',
				'select' => 'IF(ISNULL(walletaccount.bankProvince), playerbankdetails.province, walletaccount.bankProvince)',
				'name' => lang('Province'),
			),
			array(
				'dt' => $i++,
				'alias' => 'city',
				'select' => 'IF(ISNULL(walletaccount.bankCity), playerbankdetails.city, walletaccount.bankCity)',
				'name' => lang('City'),
			),
			array(
				'dt' => $i++,
				'alias' => 'dwIp',
				'select' => 'walletaccount.dwIp',
				'name' => lang('pay.withip'),
				'formatter' => function ($d) use ($is_export)  {
					if (!$is_export) {
						if ($this->utils->getConfig('enabled_dwip_redirect_playerlist')) {
							return sprintf('<a href="/player_management/searchAllPlayer?search_reg_date=off&ip_address=%s">%s</a>', $d, $d);
						}
						return $d ?: '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
					}else{
						return $d ?: lang('lang.norecyet');
					}
				},
			),
			array(
				'dt' => empty( $this->utils->getConfig('hide_iptaglist') )? $i++: NULL,
				'alias' => 'ipTags',
				'select' => 'walletaccount.dwIp',
				'name' => lang('Ip Tags'),
				'formatter' => function ($d, $row) use ($is_export)  {
					// While the player's Register IP or Withdraw Request IP in the IP Tag List should show the IP tag Name with color on the withdraw list IP Tag Column.
					// return player_tagged_list($row['playerId'], $is_export);
					// $this->load->model('ip_tag_list');

					$returnStr = lang('lang.norecyet');

					$_ip_list_condition = [];
					if( ! empty($row['registrationIP']) ) {
						$_ip_list_condition[] = $row['registrationIP'];
					}
					if( ! empty($row['dwIp']) ) {
						$_ip_list_condition[] = $row['dwIp'];
					}

					if ( ! $is_export) {
						// param: the rows in json type
						$_data_json_foramter = <<<EOF
<script type="text/javascript" class="ip_tag_list_json_data">
%s
</script>
EOF;
					} // EOF if ( ! $is_export) {...

					$ip_tag_list_rows = [];
					if( ! empty($_ip_list_condition) ){
						$return_list = [];
						$ip_tag_list_rows = $this->ip_tag_list->getRowsByIp($_ip_list_condition);
					}

					/// for _data_json_foramter
					if( ! empty($ip_tag_list_rows) ){
						if ($is_export) {
							foreach($ip_tag_list_rows as $indexNumber => $row){
								$return_list[] = $row['name'];
							}
							$returnStr = implode(',',$return_list);
						}else{
							// rendering in javascript
							$returnStr = sprintf($_data_json_foramter, json_encode($ip_tag_list_rows) );
						}
					}

					return $returnStr;

				},
			),
			array(
				'dt' => ($enable_total_player_withdrawal_requests) ? $i++ : NULL,
				'alias' => 'totalPlayerRequests',
				'select' => 'walletaccount.playerId',
				'name' => lang('pay.countPlayerWithdrawalRequests'),
				'formatter' => function ($d) use ($is_export, $input)  {
					$countPlayerRequest = $this->wallet_model->countPlayerWithdrawalRequests($d, $input);
					if (!$is_export) {
						return $countPlayerRequest ?: '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
					}else{
						return $countPlayerRequest ?: lang('lang.norecyet');
					}
				},
			),
			array(
				'dt' => ($enable_total_ip_withdrawal_requests) ? $i++ : NULL,
				'alias' => 'totalIpRequests',
				'select' => 'walletaccount.dwIp',
				'name' => lang('pay.countIpWithdrawalRequests'),
				'formatter' => function ($d) use ($is_export, $input)  {
					$countIpRequest = $this->wallet_model->countIpWithdrawalRequests($d, $input);
					if (!$is_export) {
						return $countIpRequest ?: '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
					}else{
						return $countIpRequest ?: lang('lang.norecyet');
					}
				},
			),
			array(
				'dt' => $i++,
				'alias' => 'dwLocation',
				'select' => 'walletaccount.dwLocation',
				'name' => lang('pay.withlocation'),
				'formatter' => function ($d) use ($is_export)  {
					if (!$is_export) {
						return $d ?: '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
					}else{
						return $d ?: lang('lang.norecyet');
					}
				},
			),
			array(
				'dt' => $i++,
				'alias' => 'processedByName',
				'select' => 'adminusers.username',
				'name' => lang('pay.procssby'),
				'formatter' => function ($d) use ($is_export)  {
					if (!$is_export) {
						return $d ?: '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
					}else{
						return $d ?: lang('lang.norecyet');
					}
				},
			),
			array(
				'dt' => $i++,
				'alias' => 'processDatetime',
				'select' => 'walletaccount.processDatetime',
				'name' => lang('pay.updatedon'),
				'formatter' => function ($d, $row) use ($is_export)  {

					if (!$d || strtotime($d) < 0) {
						if (!$is_export) {
							return '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
						}else{
							return $d ?: lang('lang.norecyet');
						}
					} else {
						return date('Y-m-d H:i:s', strtotime($d));
					}
				},
			),
			array(
				'dt' => $i++,
				'alias' => 'withdrawalId',
				'select' => 'walletaccount.walletAccountId',
				'name' => lang('pay.withdrawalId'),
				'formatter' => function ($d) use ($is_export)  {
					if (!$is_export) {
						return $d ?: '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
					}else{
						return $d ?: lang('lang.norecyet');
					}
				},
			),
			array(
				'dt' => $i++,
				'alias' => 'content',
				'select' => 'walletaccount.walletAccountId', #'walletaccount_notes.content' EXTERNAL_NOTE,
				'name' => lang('External Note'),
				'formatter' => function ($walletAccountId) use ($is_export)  {
 					$display_last_notes = false;
 					$limit_the_number_of_words_displayed = true;
					$allNotes = $this->walletaccount_notes->getNotesByNoteType(Walletaccount_notes::EXTERNAL_NOTE, $walletAccountId, $display_last_notes, false, $limit_the_number_of_words_displayed);

					$exportNotes = $this->walletaccount_notes->getNotesByNoteType(Walletaccount_notes::EXTERNAL_NOTE, $walletAccountId, $display_last_notes, false, false);

					if (!$is_export) {
						return $allNotes ?: '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
					}else{
						return str_replace('</br>', "\n", $exportNotes) ?: lang('lang.norecyet');
					}
				},
			),
			array(
				'dt' => $i++,
				'alias' => 'content',
				'select' => 'walletaccount.walletAccountId', #'walletaccount_notes.content' INTERNAL_NOTE,
				'name' => lang('Internal Note'),
				'formatter' => function ($walletAccountId) use ($is_export)  {
					$display_last_notes = false;
					$limit_the_number_of_words_displayed = true;
					$allNotes = $this->walletaccount_notes->getNotesByNoteType(Walletaccount_notes::INTERNAL_NOTE, $walletAccountId, $display_last_notes, false, $limit_the_number_of_words_displayed);

					$exportNotes = $this->walletaccount_notes->getNotesByNoteType(Walletaccount_notes::INTERNAL_NOTE, $walletAccountId, $display_last_notes, false, false);

					if (!$is_export) {
						return $allNotes ?: '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
					}else{
						return str_replace('</br>', "\n", $exportNotes) ?: lang('lang.norecyet');
					}
				},
			),
			array(
				'dt' => $i++,
				'alias' => 'content',
				'select' => 'walletaccount.walletAccountId', #'walletaccount_notes.content' ACTION_LOG,
				'name' => lang('Action Log'),
				'formatter' => function ($walletAccountId) use ($is_export)  {
					$display_last_notes = false;
					$allNotes = $this->walletaccount_notes->getNotesByNoteType(Walletaccount_notes::ACTION_LOG, $walletAccountId, $display_last_notes);

					if (!$is_export) {
						return $allNotes ?: '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
					}else{
						return str_replace('</br>', "\n", $allNotes) ?: lang('lang.norecyet');
					}
				},
			),
			array(
				'dt' => $i++,
				'alias' => 'timeLog',
				'select' => 'walletaccount.walletAccountId',
				'name' => lang('pay.timelog'),
				'formatter' => function ($walletAccountId) use ($is_export) {

					$timelogs   = $this->walletaccount_timelog->getWalletAccountTimeLogByWalletAccountId($walletAccountId);
					$timeString = '';
					if(!empty($timelogs)){
						$setting = $this->operatorglobalsettings->getCustomWithdrawalProcessingStage();

						$statusMap = array();
						for ($i=0; $i < 6; $i++) {
							$stage = 'CS'.$i;
							$statusMap[$stage] = $setting[$i]['name'];
						}
						$statusMap[Wallet_model::REQUEST_STATUS]          	   = lang('pay.penreq');
						$statusMap[Wallet_model::PENDING_REVIEW_STATUS]   	   = lang('pay.penreview');
						$statusMap[Wallet_model::PENDING_REVIEW_CUSTOM_STATUS] = lang('pay.pendingreviewcustom');
						$statusMap[Wallet_model::PAY_PROC_STATUS]              = lang('pay.processing');
						$statusMap[Wallet_model::PAID_STATUS]                  = lang('pay.paid');
						$statusMap[Wallet_model::DECLINED_STATUS]              = lang('pay.decreq');
						$statusMap[Wallet_model::LOCK_API_UNKNOWN_STATUS]      = lang('pay.lockapiunknownreq');

						foreach ($timelogs as $timelog) {
							$getUserName = 'N/A';
							if(!empty($timelog['created_by'])){
								if($timelog['create_type'] == Walletaccount_timelog::ADMIN_USER){
									$getUserName = $this->users->getUsernameById($timelog['created_by']);
								}else{
									$getUserName = $this->player_model->getUsernameById($timelog['created_by']);
								}
							}

							if(!empty($timelog['after_status'])){
								$timeString .= sprintf("[%s] %s:%s</br>", $statusMap[$timelog['after_status']], $getUserName, $timelog['create_date']);
							}else{
								$timeString .= sprintf("[%s] %s:%s</br>", $statusMap[$timelog['before_status']], $getUserName, $timelog['create_date']);
							}
						}
					}

					if (!$is_export) {
						return $timeString;
					}else{
						return str_replace('</br>', "\n", $timeString);
					}
				},
			),
			array(
				'dt' => $i++,
				'alias' => 'currency',
				'select' => 'playeraccount.currency',
				'name' => lang('pay.curr'),
				'formatter' => function ($d) use ($is_export,$currency_settings)  {
					$d = (!empty($currency_settings)) ? $currency_settings : $d;
					if (!$is_export) {
						return $d ? '<strong>' . $d . '</strong>': '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
					}else{

						return $d ? : lang('lang.norecyet');
					}
				},
			),
			array(
				'dt' => $i++,
				'alias' => 'system_code',
				'select' => 'external_system.system_code',
				'name' => lang('sys.ga.systemcode'),
				'formatter' => function ($d, $row)  use ($is_export) {
					if($row['dwStatus'] == Wallet_model::PAID_STATUS) {
						if(!$row['paymentAPI']) {
							return lang('Manual Payment');
						}
						else {
							return $d;
						}
					}
					else {
						if ($is_export) {
							return $d ?: lang('lang.norecyet');
						} else {
							return $d ?: '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
						}
					}
				},
			),
			array(
				'dt' => $i++,
				'alias' => 'paymentAPI',
				'select' => 'walletaccount.paymentAPI',
				'name' => lang('lang.withdrawal_payment_api'),
				'formatter' => 'defaultFormatter',
			),
			array(
				'dt' => $i++,
				'alias' => 'paybus_id',
				'select' => 'walletaccount.paybus_order_id',
				'name' => lang('Paybus ID'),
				'formatter' => function ($d) use ($is_export) {
					if (!$is_export) {
						return $d ?: '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
					}else{
						return $d ?: lang('lang.norecyet');
					}
				},
			),
			array(
				'dt' => $i++,
				'alias' => 'external_id',
				'select' => 'walletaccount.external_order_id',
				'name' => lang('External ID'),
				'formatter' => function ($d) use ($is_export) {
					if (!$is_export) {
						return $d ?: '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
					}else{
						return $d ?: lang('lang.norecyet');
					}
				},
			),
		);
		# END DEFINE COLUMNS #################################################################################################################################################

		$table = 'walletaccount';
		$force_index_withdrawList = $this->config->item('force_index_withdrawList');

		$joins = array(
			'playeraccount' => 'playeraccount.playerAccountId = walletaccount.playerAccountId',
			'player' => 'player.playerId = walletaccount.playerId',
			'playerdetails' => 'playerdetails.playerId = player.playerId',
			'localbankwithdrawaldetails' => 'localbankwithdrawaldetails.walletAccountId = walletaccount.walletAccountId',
			'playerbankdetails' => 'playerbankdetails.playerBankDetailsId = localbankwithdrawaldetails.playerBankDetailsId',
			'banktype' => 'banktype.bankTypeId = playerbankdetails.bankTypeId',
			'adminusers' => 'adminusers.userId = walletaccount.processedBy',
			'adminusers ad' => 'ad.userId = walletaccount.locked_user_id',
			'affiliates' => 'affiliates.affiliateId = player.affiliateId',
			'external_system' => 'walletaccount.paymentAPI = external_system.id',
			# 'walletaccount_timelog' => 'walletaccount_timelog.walletAccountId = walletaccount.walletAccountId',
			'crypto_withdrawal_order' => 'crypto_withdrawal_order.wallet_account_id = walletaccount.walletAccountId',
			'playercryptobankdetails' => 'playercryptobankdetails.player_bank_detailsid = playerbankdetails.playerBankDetailsId'
			#'walletaccount_notes' => 'walletaccount_notes.walletAccountId = walletaccount.walletAccountId'
		);


		# START PROCESS SEARCH FORM #################################################################################################################################################
		$where = array();
		$values = array();
		//$request = $this->input->post();

		//$input = $this->data_tables->extra_search($request);

		$where[] = 'walletaccount.transactionType = ?';
		$where[] = 'walletaccount.status = ?';
		$values[] = 'withdrawal';
		$values[] = 0;

		if (isset($input['withdraw_code'])) {
			$where[] = "walletaccount.transactionCode = ?";
			$values[] = $input['withdraw_code'];
		}

		if (!empty($playerId)) {
			$where[] = "walletaccount.playerId = ?";
			$values[] = $playerId;
		}

		if (isset($input['username'])) {
			$where[] = "player.username LIKE ?";
			$values[] = '%' . $input['username'] . '%';
		}

		if (isset($input['realname'])) {
			$where[] = "CONCAT_WS(' ', playerdetails.firstName, playerdetails.lastName) LIKE ?";
			$values[] = '%' . $input['realname'] . '%';
		}

		if (isset($input['affiliate'])) {
			$where[] = "affiliates.username = ?";
			$values[] = $input['affiliate'];
		}

		if (isset($input['amount_from'])) {
			$where[] = "walletaccount.amount >= ?";
			$values[] = $input['amount_from'];
		}

		if (isset($input['amount_to'])) {
			$where[] = "walletaccount.amount <= ?";
			$values[] = $input['amount_to'];
		}

		if(isset($input['paybus_id'])){
			$where[] = "walletaccount.paybus_order_id = ?";
			$values[] = $input['paybus_id'];
		}

		if (isset($input['external_id'])) {
			$where[] = "walletaccount.external_order_id = ?";
			$values[] = $input['external_id'];
		}

		if (!empty($input['enable_date'])) {

			/// for apply the timezone,
			// override the inputs, deposit_date_from and deposit_date_to.
			if( ! empty($input['timezone']) ){
				$default_timezone = $this->utils->getTimezoneOffset(new DateTime());
				$hours = $default_timezone - intval($input['timezone']);
				$date_from_str = $input['withdrawal_date_from'];
				$date_to_str = $input['withdrawal_date_to'];
				$by_date_from = new DateTime($date_from_str);
				$by_date_to = new DateTime($date_to_str);
				$by_date_from = new DateTime($date_from_str);
				$by_date_to = new DateTime($date_to_str);
				if($hours>0){
					$hours='+'.$hours;
				}

				$by_date_from->modify("".$hours." hours");
				$by_date_to->modify("".$hours." hours");
				$input['withdrawal_date_from'] = $this->utils->formatDateTimeForMysql($by_date_from);
				$input['withdrawal_date_to'] = $this->utils->formatDateTimeForMysql($by_date_to);
			}

			if (isset($input['withdrawal_date_from'], $input['withdrawal_date_to'], $input['search_time'])) {

                if($input['search_time'] == '1'){
					if($force_index_withdrawList) {
						$table = 'walletaccount force INDEX (idx_dwDateTime)';
					}
                    $where[] = "walletaccount.dwDatetime >= ?";
                    $values[] = $input['withdrawal_date_from'];
                    $where[] = "walletaccount.dwDatetime <= ?";
                    $values[] = $input['withdrawal_date_to'];
                } else if($input['search_time'] == '2'){
					if($force_index_withdrawList) {
						$table = 'walletaccount force INDEX (idx_processDatetime)';
					}
                	$where[] = "walletaccount.processDatetime >= ?";
                    $values[] = $input['withdrawal_date_from'];
                    $where[] = "walletaccount.processDatetime <= ?";
                    $values[] = $input['withdrawal_date_to'];
                }
			}else{
		        if (isset($input['dwStatus']) && $input['dwStatus'] == Wallet_model::PAID_STATUS) {
					if($force_index_withdrawList) {
						$table = 'walletaccount force INDEX (idx_processDatetime)';
					}
		            $where[] = "(walletaccount.processDatetime BETWEEN ? AND ?)";
		            $values[] = $input['withdrawal_date_from'];
		            $values[] = $input['withdrawal_date_to'];
		        } else {
					if($force_index_withdrawList) {
						$table = 'walletaccount force INDEX (idx_dwDateTime, idx_processDatetime)';
					}
		            $where[] = "((walletaccount.dwDateTime BETWEEN ? AND ?) OR (walletaccount.processDatetime BETWEEN ? AND ?))";
		            $values[] = $input['withdrawal_date_from'];
		            $values[] = $input['withdrawal_date_to'];
		            $values[] = $input['withdrawal_date_from'];
		            $values[] = $input['withdrawal_date_to'];
		        }
		    }
		}

		if (isset($input['dwStatus'], $input['search_status'])) {
			if($input['dwStatus'] != $input['search_status']){
				if ($input['search_status'] != 'allStatus') {
					$multiDwStatus = $input['search_status'];
					$where[] = "walletaccount.dwStatus = ?";
					$values[] = $multiDwStatus;
				}else{
					$multiDwStatus = '';
					foreach ($getSearchStatusPermission as $status => $statusPermissions) {
						if($statusPermissions[1]){
							$multiDwStatus .= "'$status',";
						}
					}
					$result = rtrim($multiDwStatus, ',');
					$where[] = "walletaccount.dwStatus IN ($result)";
				}
			}else{
				if (!is_array($input['dwStatus'])) {
					$multiDwStatus = $input['dwStatus'];
					$where[] = "walletaccount.dwStatus = ?";
					$values[] = $multiDwStatus;
				} else {
					$multiDwStatus = join('\',\'', $input['dwStatus']);
					$where[] = "walletaccount.dwStatus IN ('$multiDwStatus')";
				}
			}
		}else if(isset($input['dwStatus'])){
			if ($input['dwStatus'] == 'allStatus') {
				$multiDwStatus = '';
				foreach ($getSearchStatusPermission as $status => $statusPermissions) {
					if($statusPermissions[1]){
						$multiDwStatus .= "'$status',";
					}
				}
				$result = rtrim($multiDwStatus, ',');
				$where[] = "walletaccount.dwStatus IN ($result)";
			} else {
				$multiDwStatus = $input['dwStatus'];
				$where[] = "walletaccount.dwStatus = ?";
				$values[] = $multiDwStatus;
			}
		}

		if (isset($input['withdrawAPI'])) {
			if (!is_array($input['withdrawAPI'])) {
				$multiWithdrawAPI = $input['withdrawAPI'];
				$where[] = "walletaccount.paymentAPI = ?";
				$values[] = $multiWithdrawAPI;
			} else {
				$multiWithdrawAPI = implode(',', $input['withdrawAPI']);
				$where[] = "walletaccount.paymentAPI IN ($multiWithdrawAPI)";
			}
		}

		if (!empty($input['locked_user_id'])) {
			$where[] = "walletaccount.locked_user_id != ?";
			$values[] = '';

			// display only request and custom stage withdrawal in locked withdrawal list
			$where[] = "walletaccount.dwStatus NOT IN (?, ?, ?)";
			$values[] = Wallet_model::APPROVED_STATUS;
			$values[] = Wallet_model::DECLINED_STATUS;
			$values[] = Wallet_model::PAID_STATUS;
		}

		if (!empty($input['processed_by'])) {
			$where[] = "processedBy = ?";
			$values[] = $input['processed_by'];
		}

		// to see test player withdrawlist  on their user information page
        if(empty($playerId)){
        	$where[] = "player.deleted_at IS NULL";
        }

		if (isset($input['referrer'])) {
			$joins['player referrer'] = 'player.refereePlayerId = referrer.playerId';
			$where[] = "referrer.username = ?";
  			$values[] = $input['referrer'];
		}

		if (isset($input['tag_list_included'])) {
            $tag_list = $input['tag_list_included'];
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
        } // EOF if (isset($input['tag_list_included'])) {...


		# END PROCESS SEARCH FORM #################################################################################################################################################
		if($is_export){
            $this->data_tables->options['is_export']=true;
			// $this->data_tables->options['only_sql']=true;
            if(empty($csv_filename)){
                $csv_filename=$this->utils->create_csv_filename(__FUNCTION__);
            }
            $this->data_tables->options['csv_filename']=$csv_filename;
		}

		$group_by=[];
		$having=[];
		$distinct=false;
		$external_order=[];
		$not_datatable='';
		$countOnlyField='walletaccount.walletAccountId';
// $this->utils->debug_log('TT-5404.9619');
		$result = $this->data_tables->get_data($request, $columns, $table, $where, $values, $joins,
			$group_by, $having, $distinct, $external_order, $not_datatable, $countOnlyField);
		// $sql = $this->data_tables->last_query;
// $this->utils->debug_log('TT-5404.9619.9623');
		if($is_export){
		    //drop result if export
			return $csv_filename;
		}

		// $result = $this->data_tables->get_data($request, $columns, $table, $where, $values, $joins);

		return $result;
	}

	/**
	 * detail: get deposit checking report
	 *
	 * @return json
	 */
	public function depositCheckingReport($request, $is_export = false) {
		$this->load->model('sale_order');
		$this->load->library(array('data_tables'));
		$input = $this->data_tables->extra_search($request);
		if(!$is_export){
			$this->load->library('permissions');
			$this->permissions->setPermissions();
		}
		$this->utils->debug_log('=========depositCheckingReport input',$input);

		if($this->utils->getConfig('enabled_collection_name_in_deposit_checking_report')){
  			$enabled_collection_name_in_deposit_checking_report = true;
  		}else{
  			$enabled_collection_name_in_deposit_checking_report = false;
  		}
		# START DEFINE COLUMNS #################################################################################################################################################
		$i = 0;
		$columns = array(
			array(
				'alias' => 'playerId',
				'select' => 'player.playerId',
			),
			array(
				'dt' => $i++,
				'alias' => 'secure_id',
				'select' => 'sale_orders.secure_id',
				'name' => lang('deposit_list.order_id'),
				'formatter' => function ($d, $row) use ($is_export) {
					if($is_export){
						return $d;
					}
					return sprintf('<a href="/payment_management/deposit_list?secure_id=%s">%s</a>', $d, $d);
				},
			),
			array(
				'dt' => $i++,
				'alias' => 'username',
				'select' => 'player.username',
				'name' => lang('player.01'),
				'formatter' => function ($d, $row) use ($is_export) {
					if($is_export){
						return $d;
					}
					return sprintf('<a href="/player_management/userInformation/%s">%s</a>', $row['playerId'], $d);
				},
			),
			array(
				'dt' => $i++,
				'alias' => 'real_name',
				'select' => 'CONCAT(ifnull(playerdetails.firstName,""), \' \', ifnull(playerdetails.lastName,"") )',
				'name' => lang('sys.vu19'),
				'formatter' => 'defaultFormatter',
			),
			array(
				'dt' => $i++,
				'alias' => 'amount',
				'select' => 'sale_orders.amount',
				'name' => lang('Deposit Amount'),
				'formatter' => 'defaultFormatter',
			),
			array(
				'dt' => $i++,
				'alias' => 'request_time',
				'select' => 'sale_orders.created_at',
				'name' => lang('pay.reqtime'),
				'formatter' => 'dateTimeFormatter',
			),
			array(
				'dt' => $i++,
				'alias' => 'updated_time',
				'select' => 'sale_orders.updated_at',
				'name' => lang('payment.lastUpdatedTime'),
				'formatter' => 'dateTimeFormatter',
			),
			array(
				'dt' => $i++,
				'alias' => 'approved_time',
				'select' => 'sale_orders.processed_approved_time',
				'name' => lang('payment.settlementTime'),
				'formatter' => 'dateTimeFormatter',
			),
			array(
				'dt' => $i++,
				'alias' => 'processed_time',
				'select' => 'sale_orders.processed_approved_time',
				'name' => lang('payment.totalProcessingTime'),
				'formatter' => function ($d, $row) use ($is_export) {
					if($is_export){
						if (!empty($row['request_time']) && !empty($row['approved_time'])) {
							return $this->utils->dateDiff($row['request_time'], $row['approved_time']);
						} else {
							return lang('lang.norecyet');
						}
					}

					if (!empty($row['request_time']) && !empty($row['approved_time'])) {
						return '<span class="text-info"><b>' . $this->utils->dateDiff($row['request_time'], $row['approved_time']) . '</b></span>';
					} else {
						return '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
					}
				},
			),
			array(
				'dt' => $i++,
				'alias' => 'status',
				'select' => 'sale_orders.status',
				'name' => lang('lang.status'),
				'formatter' => function ($d, $row) use ($is_export) {
					switch ($d) {
						case Sale_order::STATUS_PROCESSING:
						case Sale_order::STATUS_BROWSER_CALLBACK:
							if($is_export){
								return lang('deposit_list.st.pending');
							}
							return '<span class="text-default"><b>' . lang('deposit_list.st.pending'). '</b></span>';
							break;
						case Sale_order::STATUS_SETTLED:
							if($is_export){
								return lang('deposit_list.st.approved');
							}
							return '<span class="text-success"><b>' . lang('deposit_list.st.approved'). '</b></span>';
							break;
						case Sale_order::STATUS_CANCELLED:
						case Sale_order::STATUS_FAILED:
						case Sale_order::STATUS_DECLINED:
							if($is_export){
								return lang('deposit_list.st.declined');
							}
							return '<span class="text-danger"><b>' . lang("deposit_list.st.declined"). '</b></span>';
							break;
						default:
							if($is_export){
								return lang($d);
							}
							return '<span class="text-default"><b>' . lang($d) . '</b></span>';
							break;
					}
				},
			),
			array(
				'dt' => $i++,
				'alias' => 'processed_by',
				'select' => 'adminusers.username',
				'name' => lang('pay.procssby'),
				'formatter' => 'defaultFormatter',
			),
			array(
  				'dt' => ($enabled_collection_name_in_deposit_checking_report)? $i++ : NULL,
  				'alias' => 'payment_type_name',
  				'select' => 'sale_orders.payment_type_name',
  				'name' => lang('pay.collection_name'),
  				'formatter' => 'languageFormatter',
  			),
		);
		# END DEFINE COLUMNS #################################################################################################################################################

		$table = 'sale_orders';
		$joins = array(
			'player' => 'player.playerId = sale_orders.player_id',
			'playerdetails' => 'playerdetails.playerId = sale_orders.player_id',
			'vipsettingcashbackrule' => 'vipsettingcashbackrule.vipsettingcashbackruleId = player.levelId',
			'adminusers' => 'sale_orders.processed_by = adminusers.userId',
			'payment_account' => 'payment_account.id = sale_orders.payment_account_id',
		);

		# START PROCESS SEARCH FORM #################################################################################################################################################
		$where = array();
		$values = array();

		if (isset($input['deposit_date_from'], $input['deposit_date_to'])) {
            $where[] = "sale_orders.created_at >= ?";
            $values[] = $input['deposit_date_from'];
            $where[] = "sale_orders.created_at <= ?";
            $values[] = $input['deposit_date_to'];
		}

		if (isset($input['username'])) {
			$where[] = "player.username LIKE ?";
			$values[] = '%' . $input['username'] . '%';
		}

		if (!empty($input['processed_by'])) {
			$where[] = "adminusers.username LIKE ?";
			$values[] ='%' . $input['processed_by']. '%';
		}

		if (isset($input['search_status'])) {
			if ($input['search_status'] != 'allStatus') {
				$where[] = "sale_orders.status = ?";
				$values[] = $input['search_status'];
			}
		}

		# END PROCESS SEARCH FORM #################################################################################################################################################

		if($is_export){
            $this->data_tables->options['is_export'] = true;
            $this->data_tables->options['csv_filename'] = $this->utils->create_csv_filename(__FUNCTION__);
		}

		$result = $this->data_tables->get_data($request, $columns, $table, $where, $values, $joins);
		return $result;
	}

	/**
	 * detail: get withdraw checking report
	 *
	 * @return json
	 */
	public function withdrawCheckingReport($request, $is_export = false, $status_permission = null) {
		$this->load->model(array('wallet_model','operatorglobalsettings'));
		$this->load->library(array('data_tables','payment_library'));

		$input = $this->data_tables->extra_search($request);

		if(!$is_export ){
			$this->load->library('permissions');
			$this->permissions->setPermissions();
		}

		$customStage = [];
        $stages = $this->operatorglobalsettings->getCustomWithdrawalProcessingStage();

        $customStageCount = 0;
		for ($i = 0; $i < count($stages); $i++) {
			if (array_key_exists($i, $stages)) {
				$customStageCount += ($stages[$i]['enabled'] ? 1 : 0);
			}
		}

		if(!$is_export){
			$getSearchStatusPermission = $this->payment_library->getWithdrawalAllStatusPermission($stages, $customStageCount);
		}
		if(!empty($status_permission)){
			$getSearchStatusPermission = $status_permission;
		}
		$this->utils->debug_log('----------------------withdrawCheckingReport getSearchStatusPermission', $getSearchStatusPermission);

		# START DEFINE COLUMNS #################################################################################################################################################
		$i = 0;
		$columns = array(
			array(
				'alias' => 'playerId',
				'select' => 'player.playerId',
			),
			array(
				'dt' => $i++,
				'alias' => 'transactionCode',
				'select' => 'walletaccount.transactionCode',
				'name' => lang('Withdrawal Code'),
				'formatter' => function ($d, $row) use ($is_export) {
					if($is_export){
						return $d;
					}
					return sprintf('<a href="/payment_management/viewWithdrawalRequestList?withdraw_code=%s">%s</a>', $d, $d);
				},
			),
			array(
				'dt' => $i++,
				'alias' => 'username',
				'select' => 'player.username',
				'name' => lang('player.01'),
				'formatter' => function ($d, $row) use ($is_export) {
					if($is_export){
						return $d;
					}
					return sprintf('<a href="/player_management/userInformation/%s">%s</a>', $row['playerId'], $d);
				},
			),
			array(
				'dt' => $i++,
				'alias' => 'real_name',
				'select' => 'CONCAT(ifnull(playerdetails.firstName,""), \' \', ifnull(playerdetails.lastName,"") )',
				'name' => lang("sys.vu19"),
				'formatter' => 'defaultFormatter',
			),
			array(
				'dt' => $i++,
				'alias' => 'amount',
				'select' => 'walletaccount.amount',
				'name' => lang('Withdraw Amount'),
				'formatter' => 'defaultFormatter',
			),
			array(
				'dt' => $i++,
				'alias' => 'request_time',
				'select' => 'walletaccount.dwDatetime',
				'name' => lang('pay.reqtime'),
				'formatter' => 'dateTimeFormatter',
			),
			array(
				'dt' => $i++,
				'alias' => 'approved_time',
				'select' => 'walletaccount.processDatetime',
				'name' => lang('payment.lastUpdatedTime'),
				'formatter' => 'dateTimeFormatter',
			),
			array(
				'dt' => $i++,
				'alias' => 'processed_time',
				'select' => 'walletaccount.processDatetime',
				'name' => lang('payment.totalProcessingTime'),
				'formatter' => function ($d, $row) use ($is_export) {
					if($is_export){
						if ($row['request_time'] && $row['approved_time']) {
							if ($row['status'] == Wallet_model::DECLINED_STATUS || $row['status'] == Wallet_model::PAID_STATUS) {
								return $this->utils->dateDiff($row['request_time'], $row['approved_time']);
							} else {
								return lang('lang.norecyet');
							}
						} else {
							return lang('lang.norecyet');
						}
					}

					if ($row['request_time'] && $row['approved_time']) {
						if ($row['status'] == Wallet_model::DECLINED_STATUS || $row['status'] == Wallet_model::PAID_STATUS) {
							return '<span class="text-info"><b>' . $this->utils->dateDiff($row['request_time'], $row['approved_time']) . '</b></span>';
						} else {
							return  '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
						}
					} else {
						return '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
					}
				}
			),
			array(
				'dt' => $i++,
				'alias' => 'status',
				'select' => 'walletaccount.dwStatus',
				'name' => lang('lang.status'),
				'formatter' => function ($d, $row) use ($is_export) {
				switch ($d) {
					case Wallet_model::REQUEST_STATUS:
						if($is_export){
							return lang('st.pending');
						}
						return '<span class="text-default"><b>' . lang('st.pending'). '</b></span>';
						break;
					case Wallet_model::PENDING_REVIEW_STATUS:
						if($this->utils->isEnabledFeature("enable_withdrawal_pending_review")){
							if($is_export){
								return lang('st.pendingreview');
							}
							return '<span class="text-warning"><b>' . lang('st.pendingreview'). '</b></span>';
						}else {
							if($is_export){
								return lang('role.nopermission');
							}
							return '<span class="text-warning"><b>' . lang('role.nopermission'). '</b></span>';
						}
						break;
					case Wallet_model::PENDING_REVIEW_CUSTOM_STATUS:
						if($this->utils->getConfig('enable_pending_review_custom')){
							if($is_export){
								return lang('st.pendingreviewcustom');
							}
							return '<span class="text-warning"><b>' . lang('st.pendingreviewcustom'). '</b></span>';
						}else {
							if($is_export){
								return lang('role.nopermission');
							}
							return '<span class="text-warning"><b>' . lang('role.nopermission'). '</b></span>';
						}
						break;
					case Wallet_model::PAY_PROC_STATUS:
						if($is_export){
							return lang('st.processing');
						}
						return '<span class="text-primary"><b><i>' . lang('st.processing'). '</i></b></span>';
						break;
					case Wallet_model::PAID_STATUS:
					case Wallet_model::APPROVED_STATUS:
						if($is_export){
							return lang('st.paid');
						}
						return '<span class="text-success"><b>' . lang('st.paid'). '</b></span>';
						break;
					case Wallet_model::DECLINED_STATUS:
						if($is_export){
							return lang('st.declined');
						}
						return '<span class="text-danger"><b>' . lang('st.declined'). '</b></span>';
						break;
					case Wallet_model::LOCK_API_UNKNOWN_STATUS:
						if($is_export){
							return lang('pay.lockapiunknownreq');
						}
						return '<span class="text-default"><b>' . lang('pay.lockapiunknownreq'). '</b></span>';
						break;
					default:
						if($is_export){
							return lang($d);
						}
						return '<span class="text-default"><b>' . lang($d) . '</b></span>';
						break;
					}
				},
			),
			array(
				'dt' => $i++,
				'alias' => 'processed_by',
				'select' => 'adminusers.username',
				'name' => lang('pay.procssby'),
				'formatter' => 'defaultFormatter',
			)
		);
		# END DEFINE COLUMNS #################################################################################################################################################

		$table = 'walletaccount';
		$joins = array(
			'player' => 'player.playerId = walletaccount.playerId',
			'playerdetails' => 'playerdetails.playerId =  walletaccount.playerId',
			'vipsettingcashbackrule' => 'vipsettingcashbackrule.vipsettingcashbackruleId = player.levelId',
			'adminusers' => 'adminusers.userId = walletaccount.processedBy',
		);

		# START PROCESS SEARCH FORM #################################################################################################################################################
		$where = array();
		$values = array();

		if (isset($input['withdrawal_date_from'], $input['withdrawal_date_to'])) {
            $where[] = "walletaccount.dwDatetime >= ?";
            $values[] = $input['withdrawal_date_from'];
            $where[] = "walletaccount.dwDatetime <= ?";
            $values[] = $input['withdrawal_date_to'];
		}

		if (isset($input['username'])) {
			$where[] = "player.username LIKE ?";
			$values[] = '%' . $input['username'] . '%';
		}

		if (isset($input['processed_by'])) {
			$where[] = "adminusers.username LIKE ?";
			$values[] ='%' . $input['processed_by']. '%';
		}

		if (isset($input['search_status'])) {
			 if ($input['search_status'] != 'allStatus') {
				 $where[] = "walletaccount.dwStatus = ?";
				 $values[] = $input['search_status'];
			}else {
				$multiDwStatus = '';
				foreach ($getSearchStatusPermission as $status => $statusPermissions) {
					if($statusPermissions[1]){
						$multiDwStatus .= "'$status',";
					}
				}
				$result = rtrim($multiDwStatus, ',');
				$where[] = "walletaccount.dwStatus IN ($result)";
			}
		}

		##############################################################################################################################################

		if($is_export){
            $this->data_tables->options['is_export'] = true;
            $this->data_tables->options['csv_filename'] = $this->utils->create_csv_filename(__FUNCTION__);
		}

		$result = $this->data_tables->get_data($request, $columns, $table, $where, $values, $joins);
		return $result;
	}

	public function exception_order_list($request, $is_export=false){

  		$this->load->model(array('sale_order', 'payment_account'));
  		$this->load->library(array('data_tables'));
  		$this->load->library(array('permissions'));
  		$this->permissions->setPermissions();

  		$this->data_tables->is_export=$is_export;

  		# START DEFINE COLUMNS #################################################################################################################################################
  		$i = 0;
  		$columns = array(
  			array(
  				'alias' => 'response_content',
  				'select' => 'exception_order.response_content',
  			),
  			array(
  				'dt' => $i++,
  				'alias' => 'id',
  				'select' => 'exception_order.id',
  				'name' => lang('ID'),
  				'formatter' => function ($d, $row) {
  					return '<a href="javascript:;" onclick="showResponseContent('.$d.')">'.lang('Details').'</a><span class="hidden" id="content_'.$d.'"><pre>'.$row['response_content'].'</pre></span>';
  				},
  			),
  			array(
  				'dt' => $i++,
  				'alias' => 'created_at',
  				'select' => 'exception_order.created_at',
  				'name' => lang('Create Date'),
  				'formatter' => 'dateTimeFormatter',
  			),
  			array(
  				'dt' => $i++,
  				'alias' => 'external_system_code',
  				'select' => 'external_system.system_code',
  				'name' => lang('Payment API'),
  				'formatter' => 'languageFormatter',
  			),
  			array(
  				'dt' => $i++,
  				'alias' => 'amount',
  				'select' => 'exception_order.amount',
  				'name' => lang('Amount'),
  				'formatter' => 'currencyFormatter',
  			),
  			array(
  				'dt' => $i++,
  				'alias' => 'external_order_id',
  				'select' => 'exception_order.external_order_id',
  				'name' => lang('External Order'),
  				'formatter' => 'defaultFormatter',
  			),
  			array(
  				'dt' => $i++,
  				'alias' => 'sale_order_id',
  				'select' => 'sale_orders.secure_id',
  				'name' => lang('Deposit Order'),
  				'formatter' => 'defaultFormatter',
  			),
  			array(
  				'dt' => $i++,
  				'alias' => 'withdrawal_order_id',
  				'select' => 'walletaccount.transactionCode',
  				'name' => lang('Withdrawal Order'),
  				'formatter' => 'defaultFormatter',
  			),
  			array(
  				'dt' => $i++,
  				'alias' => 'player_bank_name',
  				'select' => 'exception_order.player_bank_name',
  				'name' => lang('Player Bank Name'),
  				'formatter' => 'defaultFormatter',
  			),
  			array(
  				'dt' => $i++,
  				'alias' => 'player_bank_account_name',
  				'select' => 'exception_order.player_bank_account_name',
  				'name' => lang('Player Bank Account Name'),
  				'formatter' => 'defaultFormatter',
  			),
  			array(
  				'dt' => $i++,
  				'alias' => 'player_bank_account_number',
  				'select' => 'exception_order.player_bank_account_number',
  				'name' => lang('Player Bank Account Number'),
  				'formatter' => 'defaultFormatter',
  			),
  			array(
  				'dt' => $i++,
  				'alias' => 'player_bank_address',
  				'select' => 'exception_order.player_bank_address',
  				'name' => lang('Player Bank Address'),
  				'formatter' => 'defaultFormatter',
  			),
  			array(
  				'dt' => $i++,
  				'alias' => 'collection_bank_name',
  				'select' => 'exception_order.collection_bank_name',
  				'name' => lang('Collection Bank'),
  				'formatter' => 'defaultFormatter',
  			),
  			array(
  				'dt' => $i++,
  				'alias' => 'collection_bank_account_name',
  				'select' => 'exception_order.collection_bank_account_name',
  				'name' => lang('Collection Bank Account Name'),
  				'formatter' => 'defaultFormatter',
  			),
  			array(
  				'dt' => $i++,
  				'alias' => 'collection_bank_account_number',
  				'select' => 'exception_order.collection_bank_account_number',
  				'name' => lang('Collection Bank Account Number'),
  				'formatter' => 'defaultFormatter',
  			),
  			array(
  				'dt' => $i++,
  				'alias' => 'collection_bank_address',
  				'select' => 'exception_order.collection_bank_address',
  				'name' => lang('Collection Bank Address'),
  				'formatter' => 'defaultFormatter',
  			),
  			array(
  				'dt' => $i++,
  				'alias' => 'remarks',
  				'select' => 'exception_order.remarks',
  				'name' => lang('Remarks'),
  				'formatter' => 'defaultFormatter',
  			),
  		);
  		# END DEFINE COLUMNS #################################################################################################################################################

  		$table = 'exception_order';
  		$joins = array(
  			'external_system'=>'exception_order.external_system_id=external_system.id',
  			'sale_orders'=>'exception_order.sale_order_id=sale_orders.id',
  			'walletaccount'=>'exception_order.withdrawal_order_id=walletaccount.walletAccountId',
  			// 'player' => 'player.playerId = sale_orders.player_id',
  			// 'playerdetails' => 'playerdetails.playerId = sale_orders.player_id',
  			// 'adminusers' => 'sale_orders.processed_by = adminusers.userId',
  			// 'playerpromo' => 'playerpromo.playerpromoId = sale_orders.player_promo_id',
  			// 'promorules' => 'playerpromo.promorulesId = promorules.promorulesId',
  			// 'transactions' => 'transactions.id = sale_orders.transaction_id',
  			// 'payment_account' => 'payment_account.id = sale_orders.payment_account_id',
  			// 'affiliates' => 'affiliates.affiliateId = player.affiliateId'
  		);

  		# START PROCESS SEARCH FORM #################################################################################################################################################
  		$where = array();
  		$values = array();
  		//$request = $this->input->post();
  		$input = $this->data_tables->extra_search($request);

  		if (isset($input['player_bank_account_name'])) {
  			$where[] = "player_bank_account_name LIKE ?";
  			$values[] = '%' . $input['player_bank_account_name'] . '%';
  		}

  		if (isset($input['player_bank_account_number'])) {
  			$where[] = "player_bank_account_number LIKE ?";
  			$values[] = '%' . $input['player_bank_account_number'] . '%';
  		}

  		if (isset($input['collection_bank_account_name'])) {
  			$where[] = "collection_bank_account_name LIKE ?";
  			$values[] = '%' . $input['collection_bank_account_name'] . '%';
  		}

  		if (isset($input['order_secure_id'])) {
  			$where[] = "sale_orders.secure_id = ?";
  			$values[] = $input['order_secure_id'];
  		}

  		// if ($this->safeGetParam($input, 'enable_date') == 'true' ) {
  			if (isset($input['by_date_from'], $input['by_date_to'])) {
  				$where[] = "exception_order.created_at >= ?";
  				$where[] = "exception_order.created_at <= ?";
  				$values[] = $input['by_date_from'];
  				$values[] = $input['by_date_to'];
  			}
  	    // }
  		if (isset($input['withdrawal_order_id'])) {
  			$where[] = "walletaccount.transactionCode = ?";
  			$values[] = $input['withdrawal_order_id'];
  		}
  		# END PROCESS SEARCH FORM #################################################################################################################################################

  		$result = $this->data_tables->get_data($request, $columns, $table, $where, $values, $joins);
  		return $result;
	}

	public function unusual_notification_requests_list($request, $is_export=false){

  		$this->load->model(array('sale_order', 'payment_account'));
  		$this->load->library(array('data_tables'));
  		$this->load->library(array('permissions'));
  		$this->permissions->setPermissions();

  		$this->data_tables->is_export=$is_export;

  		# START DEFINE COLUMNS #################################################################################################################################################
  		$i = 0;
  		$columns = array(
  			array(
  				'dt' => $i++,
  				'alias' => 'id',
  				'select' => 'unusual_notification_requests.id',
  				'name' => lang('ID'),
  				'formatter' => 'defaultFormatter',
  			),
  			array(
  				'dt' => $i++,
  				'alias' => 'created_at',
  				'select' => 'unusual_notification_requests.created_at',
  				'name' => lang('Create Date'),
  				'formatter' => 'dateTimeFormatter',
  			),
  			array(
  				'dt' => $i++,
  				'alias' => 'status_code',
  				'select' => 'unusual_notification_requests.status_code',
  				'name' => lang('unusual_notification_requests_status_code'),
  				'formatter' => 'defaultFormatter',
  			),
  			array(
  				'dt' => $i++,
  				'alias' => 'status_type',
  				'select' => 'unusual_notification_requests.status_type',
  				'name' => lang('unusual_notification_requests_status_type'),
  				'formatter' => 'defaultFormatter',
  			),
  			array(
  				'dt' => $i++,
  				'alias' => 'data_transaction_id',
  				'select' => 'unusual_notification_requests.data_transaction_id',
  				'name' => lang('unusual_notification_requests_data_transaction_id'),
  				'formatter' => 'defaultFormatter',
  			),
  			array(
  				'dt' => $i++,
  				'alias' => 'data_payer_bank',
  				'select' => 'unusual_notification_requests.data_payer_bank',
  				'name' => lang('unusual_notification_requests_data_payer_bank'),
  				'formatter' => 'defaultFormatter',
  			),
  			array(
  				'dt' => $i++,
  				'alias' => 'data_payer_account',
  				'select' => 'unusual_notification_requests.data_payer_account',
  				'name' => lang('unusual_notification_requests_data_payer_account'),
  				'formatter' => 'defaultFormatter',
  			),
  			array(
  				'dt' => $i++,
  				'alias' => 'data_payee_bank',
  				'select' => 'unusual_notification_requests.data_payee_bank',
  				'name' => lang('unusual_notification_requests_data_payee_bank'),
  				'formatter' => 'defaultFormatter',
  			),
  			array(
  				'dt' => $i++,
  				'alias' => 'data_payee_account',
  				'select' => 'unusual_notification_requests.data_payee_account',
  				'name' => lang('unusual_notification_requests_data_payee_account'),
  				'formatter' => 'defaultFormatter',
  			),
  			array(
  				'dt' => $i++,
  				'alias' => 'data_amount',
  				'select' => 'unusual_notification_requests.data_amount',
  				'name' => lang('Amount'),
  				'formatter' => 'currencyFormatter',
  			),
  		);
  		# END DEFINE COLUMNS #################################################################################################################################################

  		$table = 'unusual_notification_requests';
  		$joins = array(
  		);

  		# START PROCESS SEARCH FORM #################################################################################################################################################
  		$where = array();
  		$values = array();
  		//$request = $this->input->post();
  		$input = $this->data_tables->extra_search($request);

  		if (isset($input['data_transaction_id'])) {
  			$where[] = "unusual_notification_requests.data_transaction_id = ?";
  			$values[] = $input['data_transaction_id'];
  		}

		if (isset($input['by_date_from'], $input['by_date_to'])) {
			$where[] = "unusual_notification_requests.created_at >= ?";
			$where[] = "unusual_notification_requests.created_at <= ?";
			$values[] = $input['by_date_from'];
			$values[] = $input['by_date_to'];
		}

  		# END PROCESS SEARCH FORM #################################################################################################################################################

  		$result = $this->data_tables->get_data($request, $columns, $table, $where, $values, $joins);
  		return $result;
	}

	public function transactionsByGetRebate($player_id, $request, $not_datatable = ''){
		$readOnlyDB = $this->getReadOnlyDB();

		$this->load->library('data_tables', array("DB" => $readOnlyDB));
		$this->load->model(array('player_model'));
		$this->benchmark->mark('pre_processing_start');
		# START DEFINE COLUMNS #################################################################################################################################################
		$i = 0;
		# START DEFINE COLUMNS #################################################################################################################################################
		$i = 0;
		$columns = array(
			array(
				'dt' => $i++,
				'alias' => 'receivedOn',
				'select' => 'created_at',
				'formatter' => 'dateTimeFormatter',
				'name' => lang('Transfer time'),
			),
			array(
				'dt' => $i++,
				'alias' => 'amount',
				'select' => "amount",
				'name' => lang('From'),
				'formatter' => function ($d) {
					return $this->utils->toCurrencyNumber($d);
				},
			)

		);
		$table = 'transactions';

		# END DEFINE COLUMNS #################################################################################################################################################

		//define conset  from admin/application/models/transactions.php
		$tPlayer =2;
		$auto_add_cashback_to_balance =13;
		$tstatus = 1;

		# START PROCESS SEARCH FORM #################################################################################################################################################
		$where = array();
		$values = array();
		$group_by=[];
		$having=[];
		$distinct = true;
		$external_order=[]; //[['column'=>'id', 'dir'=> 'desc']];

		$input = $this->data_tables->extra_search($request);
		$where[] = "transactions.to_id = ? ";
		$values[] = $player_id;
		$where[] = "transactions.to_type = ? ";
		$values[] = $tPlayer;
		$where[] = "transactions.transaction_type = ? ";
		$values[] = $auto_add_cashback_to_balance;
		$where[] = "transactions.status = ? ";
		$values[] = $tstatus;

		if (isset($input['dateRangeValueStart'], $input['dateRangeValueEnd'])) {
			$where[] = "transactions.created_at BETWEEN ? AND ?";
			$values[] = $input['dateRangeValueStart'];
			$values[] = $input['dateRangeValueEnd'];
		}

		$this->utils->debug_log('where', $where, 'values', $values);
		$this->benchmark->mark('pre_processing_end');

		# END PROCESS SEARCH FORM #################################################################################################################################################
		$this->benchmark->mark('data_sql_start');
		#set empty

		$group_by =array();
		$having = array();
		$distinct = true;
		$external_order=array();
		$not_datatable='';
		$joins = array();
		$result = $this->data_tables->get_data($request, $columns, $table, $where, $values, $joins, $group_by, $having, $distinct, $external_order, $not_datatable);
 		$this->utils->printLastSQL();
		$this->benchmark->mark('data_sql_end');
		$this->utils->debug_log($result);
		return $result;
	}

	public function playerCashbackHistory($player_id, $request){
		$readOnlyDB = $this->getReadOnlyDB();

		$this->load->library('data_tables', array("DB" => $readOnlyDB));
		$this->load->model(array('player_model'));
		$this->benchmark->mark('pre_processing_start');

		$i = 0;
		$columns = array(
			array(
				'dt' => $i++,
				'alias' => 'receivedOn',
				'select' => 'paid_date',
				'formatter' => 'dateTimeFormatter',
				'name' => lang('Transfer time'),
			),
			array(
				'dt' => $i++,
				'alias' => 'amount',
				'select' => "paid_amount",
				'name' => lang('From'),
				'formatter' => function ($d) {
					return $this->utils->toCurrencyNumber($d);
				},
			)
		);

		if($this->utils->isEnabledFeature('enable_friend_referral_cashback') && false) {
			$cashback_type = array(
				array(
					'dt' => $i++,
					'alias' => 'cashback_type',
					'select' => "cashback_type",
					'name' => lang('Cashback Type'),
					'formatter' => function ($d) {
						switch((int)$d){
							case self::FRIEND_REFERRAL_CASHBACK:
								return lang('Friend Referral Cashback');
								break;
							case self::MANUALLY_ADD_CASHBACK:
								return lang('Manually Add Cashback');
								break;
							case self::NORMAL_CASHBACK:
							default:
								return lang('Normal Cashback');
								break;
						}
					},
				)
			);
			$columns = array_merge($columns, $cashback_type);
		}

		if($this->utils->isEnabledFeature('enable_friend_referral_cashback') && false) {



			$invited_player_id = array(
				array(
					'dt' => $i++,
					'alias' => 'invited_player_id',
					'select' => "invited_player_id",
					'name' => lang('Referred Player for Cashback'),
					'formatter' => function ($d, $row)  use ($readOnlyDB) {
						if($row['cashback_type'] == self::FRIEND_REFERRAL_CASHBACK) {
							$qry = $readOnlyDB->query("SELECT username FROM player where playerId= ?", array($d));
						if ($qry && $qry->num_rows() > 0) {
							$res = $qry->row_array();
							return !empty($res['username']) ? $res['username'] : 'N/A';
						}
							return 'N/A';
						}
							return 'N/A';
					},
				)
		 	);
		 	$columns = array_merge($columns, $invited_player_id);
		}

		$table = 'total_cashback_player_game_daily';

		$where = array();
		$values = array();

		$input = $this->data_tables->extra_search($request);
		$where[] = "total_cashback_player_game_daily.paid_flag = ? ";
		$values[] = Group_level::DB_TRUE;

		$where[] = "total_cashback_player_game_daily.paid_date BETWEEN ? AND ?";
		$values[] = $input['dateRangeValueStart'];
		$values[] = $input['dateRangeValueEnd'];

		if(!empty($player_id)) {
			$where[] = "total_cashback_player_game_daily.player_id = ? ";
			$values[] = $player_id;
		}

		if(!$this->utils->isEnabledFeature('enable_friend_referral_cashback') || true) {
			$where[] = "total_cashback_player_game_daily.cashback_type IN (?, ?)";
			$values[] = self::NORMAL_CASHBACK;
			$values[] = self::MANUALLY_ADD_CASHBACK;
		}

		$where[] = "total_cashback_player_game_daily.cashback_target = ?";
		$values[] = Group_level::CASHBACK_TARGET_PLAYER;

		$this->benchmark->mark('pre_processing_end');
		$this->benchmark->mark('data_sql_start');

		$group_by =array();
		$having = array();
		$distinct = true;
		$external_order=array();
		$not_datatable='';
		$joins = array();
		$result = $this->data_tables->get_data($request, $columns, $table, $where, $values, $joins, $group_by, $having, $distinct, $external_order, $not_datatable);
		$this->utils->printLastSQL();
		$this->benchmark->mark('data_sql_end');
		$this->utils->debug_log($result);
		return $result;
	}

	public function achieve_threshold_report($request, $is_export = false) {

		$readOnlyDB = $this->getReadOnlyDB();

		$this->load->library('data_tables', array("DB" => $readOnlyDB));
		$this->load->model(array('player_dw_achieve_threshold', 'transactions', 'player_model'));

		$this->data_tables->is_export = $is_export;

		$input = $this->data_tables->extra_search($request);
		$where = [];
		$values = [];
		$having = [];
		$group_by = [];
		$col = 0;
		$na = $is_export ? lang('lang.norecyet') : '<i class="text-muted">' . lang('lang.norecyet') . '</i>';

		$columns = array(
			array(
				'alias' => 'player_id',
				'select' => 'player_dw_achieve_threshold_history.player_id',
			),
			// 0 - regdate
			array(
				'dt' => $col++,
				'alias' => 'create_at',
				'select' => 'player_dw_achieve_threshold_history.create_at',
				// 'formatter' => 'dateFormatter',
				'name' => lang("sys.achieve.threshold.datetime"),
			),
			array(
				'dt' => $col++,
				'alias' => 'username',
				'select' => 'player_dw_achieve_threshold_history.username',
				'name' => lang('Username'),
			),
			array(
				'dt' => $col++,
				'alias' => 'achieve_threshold_type',
				'select' => 'player_dw_achieve_threshold_history.achieve_threshold_type',
				'name' => lang("sys.achieve.threshold.transaction.type"),
				'formatter' => function ($d, $row) use ($is_export) {
					if($d==1){
						return lang("sys.achieve.threshold.deposit");
					}else{
						return lang("sys.achieve.threshold.withdrawal");
					}
				}
			),
			array(
				'dt' => $col++,
				'alias' => 'threshol_amount',
				'select' => 'player_dw_achieve_threshold_history.threshold_amount',
				'name' => lang('sys.achieve.threshold.threshold.amount'),
			),
			array(
				'dt' => $col++,
				'alias' => 'achieve_amount',
				'select' => 'player_dw_achieve_threshold_history.achieve_amount',
				'name' => lang('sys.achieve.threshold.achieve.amount'),
			)
		);

		$this->utils->debug_log(__METHOD__, 'columns', $columns);

		$table = 'player_dw_achieve_threshold_history';
		$joins = [];

		if (isset($input['by_status'])) {
			$where[] = "player_dw_achieve_threshold_history.achieve_threshold_type = ?";
			$values[] = $input['by_status'];
		} else {
			$where[] = "player_dw_achieve_threshold_history.achieve_threshold_type in (?,?,?)";
			$values[] = '';
			$values[] = Player_dw_achieve_threshold::ACHIEVE_THRESHOLD_DEPOSIT;
			$values[] = Player_dw_achieve_threshold::ACHIEVE_THRESHOLD_WITHDRAWAL;
		}

		if (isset($input['by_date_from'], $input['by_date_to'])) {
			$where[] = "DATE(player_dw_achieve_threshold_history.create_at) >=?";
			$where[] = "DATE(player_dw_achieve_threshold_history.create_at) <=?";
			$values[] = $input['by_date_from'];
			$values[] = $input['by_date_to'];
		}

		if (isset($input['by_username'])) {
			$where[] = "player_dw_achieve_threshold_history.username LIKE ?";
			$values[] = '%' . $input['by_username'] . '%';
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

		return $result;
	}

	public function abnormal_payment_report($request, $is_export = false) {

		$readOnlyDB = $this->getReadOnlyDB();

		$this->load->library('data_tables', array("DB" => $readOnlyDB));
		$this->load->model(array('payment_abnormal_notification', 'transactions', 'player_model'));

		$this->utils->debug_log(__METHOD__, 'request', $request);

		$this->data_tables->is_export = $is_export;

		$input = $this->data_tables->extra_search($request);
		$where = [];
		$values = [];
		$having = [];
		$group_by = [];
		$i = 0;
		$na = $is_export ? lang('lang.norecyet') : '<i class="text-muted">' . lang('lang.norecyet') . '</i>';

		$this->utils->debug_log(__METHOD__, 'input', $input);

		$columns = array(
			// 0 - regdate
			array(
				'dt'=> (!$is_export) ? $i++ : NULL,
				'alias' => 'id',
				'select' => 'payment_abnormal_notification.id',
				'formatter'=>function ($d,$row) {
                    return '
                        <td style="text-align:center;">
                        <input type="checkbox" data-checked-all-for="checkWhite" class="checkWhite" id="'.$row['id'].'" name="abnormalOrder[]" value="'.$row['id'].'" onclick="uncheckAll(this.id)" data-player-id="' . $row['playerId'] .'" />
                        </td>';
                }
            ),
			array(
				'dt' => $i++,
				'alias' => 'status',
				'select' => 'payment_abnormal_notification.status',
				'name' => lang("cs.abnormal.payment.status"),
				'formatter' => function ($d, $row) use ($is_export,$na) {
					if($d == Payment_abnormal_notification::ABNORMAL_READ){
						return lang("cs.abnormal.payment.read");
					}else if($d == Payment_abnormal_notification::ABNORMAL_UNREAD){
						return lang("cs.abnormal.payment.unread");
					}else{
						return $na;
					}
				}
			),
			array(
				'dt' => $i++,
				'alias' => 'type',
				'select' => 'payment_abnormal_notification.type',
				'name' => lang("cs.abnormal.payment.type"),
				'formatter' => function ($d, $row) use ($is_export,$na) {
					if($d == Payment_abnormal_notification::ABNORMAL_PLAYER){
						return lang("cs.abnormal.payment.player");
					}else if($d == Payment_abnormal_notification::ABNORMAL_PAYMENT){
						return lang("cs.abnormal.payment.payment");
					}else{
						return $na;
					}
				}
			),
			array(
				'dt' => $i++,
				'alias' => 'update_by',
				'select' => 'adminusers.username',
				'name' => lang('cs.abnormal.payment.operator'),
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
				'alias' => 'playerId',
				'select' => 'payment_abnormal_notification.playerId',
				'name' => lang('cs.abnormal.payment.player'),
				'formatter' => function ($d, $row) use ($is_export,$na) {
					if(!empty($d)){
						$username = $this->player_model->getUsernameById($d);
						return $username;
					}else{
						return $na;
					}
				}
			),
			array(
				'dt' => $i++,
				'alias' => 'abnormal_payment_name',
				'select' => 'payment_abnormal_notification.abnormal_payment_name',
				'name' => lang('cs.abnormal.payment.abnormal_payment_name'),
				'formatter' => function ($d) use ($is_export) {
                     if ($is_export) {
                         return lang($d);
                     } else {
                         return lang($d);
                     }
                 }
			),
			array(
				'dt' => $i++,
				'alias' => 'created_at',
				'select' => 'payment_abnormal_notification.created_at',
				'name' => lang("cs.abnormal.payment.created_at"),
			),
			array(
				'dt' => $i++,
				'alias' => 'update_at',
				'select' => 'payment_abnormal_notification.update_at',
				'name' => lang('cs.abnormal.payment.update_at'),
				'formatter' => function ($d, $row) use ($is_export,$na) {
					if(!empty($d)){
						if (strtotime($d) == strtotime($row['created_at'])) {
							return $na;
						}
						return $d;
					}else{
						return $na;
					}
				}
			),
			// array(
			// 	'dt' => $i++,
			// 	'alias' => 'notes',
			// 	'select' => 'payment_abnormal_notification.notes',
			// 	'name' => lang('cs.abnormal.payment.notes'),
			// )
		);

		$this->utils->debug_log(__METHOD__, 'columns', $columns);

		$table = 'payment_abnormal_notification';
		$joins = ['adminusers' => 'payment_abnormal_notification.update_by = adminusers.userId'];

		if (isset($input['by_status'])) {
			$where[] = "payment_abnormal_notification.status = ?";
			$values[] = $input['by_status'];
		} else {
			$where[] = "payment_abnormal_notification.status in (?,?,?)";
			$values[] = '';
			$values[] = Payment_abnormal_notification::ABNORMAL_READ;
			$values[] = Payment_abnormal_notification::ABNORMAL_UNREAD;
		}

		if (isset($input['by_type'])) {
			$where[] = "payment_abnormal_notification.type = ?";
			$values[] = $input['by_type'];
		} else {
			$where[] = "payment_abnormal_notification.type in (?,?,?)";
			$values[] = '';
			$values[] = Payment_abnormal_notification::ABNORMAL_PLAYER;
			$values[] = Payment_abnormal_notification::ABNORMAL_PAYMENT;
		}

		if (isset($input['by_date_from'], $input['by_date_to'])) {
			$where[] = "DATE(payment_abnormal_notification.created_at) >=?";
			$where[] = "DATE(payment_abnormal_notification.created_at) <=?";
			$values[] = $input['by_date_from'];
			$values[] = $input['by_date_to'];
		}

        if (isset($input['update_by'])) {
			$where[] = "adminusers.userId = ?";
			$values[] = $input['update_by'];
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

		return $result;
	}

	public function excessWithdrawalRequestsList($request, $is_export = false) {

		$readOnlyDB = $this->getReadOnlyDB();

		$this->load->library('data_tables', array("DB" => $readOnlyDB));
		$this->load->model(array('payment_abnormal_notification', 'transactions', 'player_model','walletaccount_timelog'));
		$this->load->helper(['player_helper']);
		$this->load->library('permissions');
		$this->utils->debug_log(__METHOD__, 'request', $request);
		$this->data_tables->is_export = $is_export;

		$input = $this->data_tables->extra_search($request);
		$where = [];
		$values = [];
		$having = [];
		$group_by = [];
		$i = 0;
		$na = $is_export ? lang('lang.norecyet') : '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
		$adjust_withdrawal_abnormal = $this->permissions->checkPermissions('adjust_withdrawal_abnormal');

		$this->utils->debug_log(__METHOD__, 'input', $input);

		$columns = array(
			array(
				'alias' => 'walletAccountId',
				'select' => 'walletaccount.walletAccountId',
			),
			array(
				'dt'=> (!$is_export) ? $i++ : NULL,
				'alias' => 'id',
				'select' => 'payment_abnormal_notification.id',
				'formatter'=>function ($d,$row) use ($is_export,$adjust_withdrawal_abnormal) {

					$output = '<div class="clearfix" style="width:65px;">';
					$output_checckbox = $adjust_withdrawal_abnormal ?
					'<div class="col-md-3" style="padding:5px 1px 0 2px">
					<input type="checkbox" data-checked-all-for="checkWhite" class="checkWhite" id="'.$row['id'].'" name="abnormalOrder" value="'.$row['id'].'" onclick="uncheckAll(this.id)" data-playerid="' . $row['playerId']. '" data-dwstatus="' .$row['dwStatus']. '" data-withdrawcode="' .$row['transactionCode']  . '"  />
					</div>' : '';

					$output .= $output_checckbox;

					if ($row['dwStatus'] == Wallet_model::APPROVED_STATUS || $row['dwStatus'] == Wallet_model::PAY_PROC_STATUS) {
						$detail_modal = 'getWithdrawalApproved(' . $row['walletAccountId'] .',' .APPROVED_MODAL.','.$row['playerId'].')';
					} else if ($row['dwStatus'] == Wallet_model::DECLINED_STATUS || $row['dwStatus'] == Wallet_model::PAID_STATUS) {
						$detail_modal = 'getWithdrawalDeclined(' . $row['walletAccountId'] . ',' . $row['playerId'] . ',' .DECLINED_MODAL.')';
					} else {
						// Determines the target modal to be showed
						$customWithdrawalSetting = $this->operatorglobalsettings->getCustomWithdrawalProcessingStage();
						$useApprovedModal = false;

						if ($customWithdrawalSetting['maxCSIndex'] == -1) {
							$useApprovedModal = true;
						} else if (substr($row['dwStatus'], 0, 2) == 'CS') {
							$currentCSIndex = intval(substr($row['dwStatus'], 2));
							$useApprovedModal = $currentCSIndex >= $customWithdrawalSetting['maxCSIndex'];
						}

						if ($useApprovedModal) {
							$detail_modal = 'getWithdrawalRequest(' . $row['walletAccountId'] . ',' . $row['playerId'] . ',' .APPROVED_MODAL.')';
						} else {
							if(($row['dwStatus'] == Wallet_model::PENDING_REVIEW_CUSTOM_STATUS && $this->utils->isEnabledFeature("enable_pending_vip_show_3rd_and_manualpayment_btn"))){
								$detail_modal = 'getWithdrawalRequest(' . $row['walletAccountId'] . ',' . $row['playerId'] . ',' .APPROVED_MODAL.')';
							}else{
								$detail_modal = 'getWithdrawalRequest(' . $row['walletAccountId'] . ',' . $row['playerId'] . ',' .REQUEST_MODAL.')';
							}
						}
					}

					$output .= '<div class="col-md-9" style="padding:0 2px 0 2px"><span class="btn btn-xs btn-info review-btn" data-toggle="modal" onclick="openWithdrawalRequestList(this.id)" data-dwstatus="' . $row['dwStatus']. '" data-withdrawcode="' .$row['transactionCode']  . '"data-playerid="' . $row['playerId']. '" id="detail_'.$row['id'].'"  value="'.$row['id'] .'" data-createdon="'.$row['createdOn'] .'"  data-detail_modal="'.$detail_modal  .'" >';

					$output .= lang("lang.details");
					$output .= '</span></div></div>';

                    return $output;
                }
            ),
			array(
				'dt' => $i++,
				'alias' => 'status',
				'select' => 'payment_abnormal_notification.status',
				'name' => lang("excess.withdrawal.status"),
				'formatter' => function ($d, $row) use ($is_export,$na) {
					if($d == Payment_abnormal_notification::ABNORMAL_READ){
						return $is_export ? lang("cs.abnormal.payment.read") : '<span class="text-success">' . lang("cs.abnormal.payment.read") . '</span>';
					}else if($d == Payment_abnormal_notification::ABNORMAL_UNREAD){
						return $is_export ? lang("cs.abnormal.payment.unread") : '<span class="text-danger">' . lang("cs.abnormal.payment.unread") . '</span>';
					}else{
						return $na;
					}
				}
			),
			array(
				'dt' => $i++,
				'alias' => 'dwStatus',
				'select' => 'walletaccount.dwStatus',
				'name' => lang("lang.status"),
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
				'alias' => 'transactionCode',
				'select' => 'walletaccount.transactionCode',
				'name' => lang("Withdraw Code"),
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
				'alias' => 'playerId',
				'select' => 'payment_abnormal_notification.playerId',
				'name' => lang('pay.username'),
				'formatter' => function ($d, $row) use ($is_export,$na) {
					if(!empty($d)){
						$username = $this->player_model->getUsernameById($d);
						return $username;
					}else{
						return $na;
					}
				}
			),
			array(
				'dt' => $i++,
				'alias' => 'createdOn',
				'select' => 'walletaccount.dwDatetime',
				'name' => lang('pay.reqtime'),
				'formatter' => 'defaultFormatter',
			),
			array(
				'dt' => $i++,
				'alias' => 'processTime',
				'select' => '(select walletaccount_timelog.create_date from walletaccount_timelog where walletaccount_timelog.walletAccountId = walletaccount.walletAccountId and walletaccount_timelog.after_status = "'.Wallet_model::PAY_PROC_STATUS.'" ORDER BY walletaccount_timelog.create_date DESC limit 1)',
				'name' => lang('pay.proctime'),
				'formatter' => function ($processTime) use ($is_export,$na) {

					#OGP-18476 use sub select search walletaccount_timelog create_date (processTime)
					#$note = $this->walletaccount_timelog->getWalletAccountTimeLogByWalletAccountId($walletAccountId, Wallet_model::PAY_PROC_STATUS);

					if(!empty($processTime)){
						return date('Y-m-d H:i:s', strtotime($processTime));
					}
					else{
						return $na;
					}
				},
			),
			array(
				'dt' => $i++,
				'alias' => 'paidTime',
				'select' => 'walletaccount.walletAccountId',
				'name' => lang('pay.paidtime'),
				'formatter' => function ($walletAccountId) use ($is_export,$na) {

					$note = $this->walletaccount_timelog->getWalletAccountTimeLogByWalletAccountId($walletAccountId, Wallet_model::PAID_STATUS);

					if(!empty($note)){
						return date('Y-m-d H:i:s', strtotime($note['create_date']));
					}
					else{
						return $na;
					}
				},
			),
			array(
                'dt' => $i++,
                'alias' => 'group_level',
                'select' => 'player.levelName',
                'name' => lang('pay.playerlev'),
                'formatter' => function ($d, $row) {
		            $player_level = $this->player_model->getPlayerCurrentLevel($row['playerId']);

		            if($player_level){
		                $groupName = lang($player_level[0]['groupName']);
		                $levelName = lang($player_level[0]['vipLevelName']);
						return $groupName . ' - ' .$levelName;
		            }
		            else{
						return null;
		            }
                },
            ),
			array(
				'dt' => $i++,
				'alias' => 'tag',
				'select' => 'player.playerId',
				'name' => lang('Tag'),
				'formatter' => function ($d, $row) use ($is_export)  {
					return player_tagged_list($row['playerId'], $is_export);
				},
			),
			array(
				'dt' => $i++,
				'alias' => 'amount',
				'select' => 'walletaccount.amount',
				'name' => lang('pay.withamt'),
				'formatter' => function ($d) use ($is_export)  {
					if (!$is_export) {
						return '<strong>'.$this->utils->formatCurrencyNoSym($d).'</strong>';
					}else{
						return $this->utils->formatCurrencyNoSym($d);
					}
				},
			),
			array(
				'dt' => $i++,
				'alias' => 'created_at',
				'select' => 'payment_abnormal_notification.created_at',
				'name' => lang("excess.withdrawal.created_at"),
			),
			array(
				'dt' => $i++,
				'alias' => 'update_at',
				'select' => 'payment_abnormal_notification.update_at',
				'name' => lang('excess.withdrawal.update_at'),
				'formatter' => function ($d, $row) use ($is_export,$na) {
					if(!empty($d)){
						if (strtotime($d) == strtotime($row['created_at'])) {
							return $na;
						}
						return $d;
					}else{
						return $na;
					}
				}
			),
			array(
				'dt' => $i++,
				'alias' => 'update_by',
				'select' => 'adminusers.username',
				'name' => lang('excess.withdrawal.operator'),
				'formatter' => function ($d, $row) use ($is_export,$na) {
					if(!empty($d)){
						return $d;
					}else{
						return $na;
					}
				}
			),
			// array(
			// 	'dt' => $i++,
			// 	'alias' => 'notes',
			// 	'select' => 'payment_abnormal_notification.notes',
			// 	'name' => lang('cs.abnormal.payment.notes'),
			// )
		);

		$this->utils->debug_log(__METHOD__, 'columns', $columns);

		$table = 'payment_abnormal_notification';
		$joins = [
			'adminusers' => 'payment_abnormal_notification.update_by = adminusers.userId',
			'walletaccount' => 'payment_abnormal_notification.order_id = walletaccount.walletAccountId',
			'player' => 'player.playerId = payment_abnormal_notification.playerId'
		];

		if (isset($input['by_status'])) {
			$where[] = "payment_abnormal_notification.status = ?";
			$values[] = $input['by_status'];
		} else {
			$where[] = "payment_abnormal_notification.status in (?,?,?)";
			$values[] = '';
			$values[] = Payment_abnormal_notification::ABNORMAL_READ;
			$values[] = Payment_abnormal_notification::ABNORMAL_UNREAD;
		}

		if (isset($input['by_date_from'], $input['by_date_to'])) {
			$where[] = "DATE(payment_abnormal_notification.created_at) >=?";
			$where[] = "DATE(payment_abnormal_notification.created_at) <=?";
			$values[] = $input['by_date_from'];
			$values[] = $input['by_date_to'];
		}

		if (isset($input['username'])) {
			$where[] = "player.username = ?";
			$values[] = $input['username'];
		}

        if (isset($input['update_by'])) {
			$where[] = "adminusers.userId = ?";
			$values[] = $input['update_by'];
		}

		$where[] = "payment_abnormal_notification.type = ?";
		$values[] = Payment_abnormal_notification::ABNORMAL_WITHDRAWAL;

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

		return $result;
	}

	public function hiddenBankTypeList($request, $is_export = false) {

		$this->load->library(array('data_tables'));
		$input = $this->data_tables->extra_search($request);

		$i = 0;
		$where = array();
		$values = array();
		$columns = array();
		$joins = array();
		$joins['adminusers cb'] = 'cb.userId = banktype.createdBy';
		$joins['adminusers ub'] = 'ub.userId = banktype.updatedBy';


		$columns[] = array(
			'dt' => $i++,
			'select' => 'banktype.bankTypeId',
			'alias' => 'action',
			'name' => lang('column.id'),
			'formatter' => function ($d, $row) {
				$action_button = '<a href="javascript:void(0)"  banktype_id="'.$d.'"  data-toggle="tooltip" title="'.lang('Show').'" class="bank_type_actions" action="show"><span  banktype_id="'.$d.'" class="glyphicon glyphicon-eye-open primary"></span></a>&nbsp;';
				return $action_button;
			},
		);
		$columns[] = array(
			'dt' => $i++,
			'select' => 'banktype.bankTypeId',
			'alias' => 'bankTypeId',
			'name' => lang('column.id'),
			'formatter' => function ($d, $row) {
				return $d ?: '-';
			},
		);
		$columns[] = array(
			'dt' => $i++,
			'select' => 'banktype.bankName',
			'alias' => 'bankName',
			'name' => lang('pay.bt.bankname'),
			'formatter' => function ($d, $row) {
				return lang($d) ?: '-';
			},
		);
		$columns[] = array(
			'dt' => $i++,
			'select' => 'banktype.bank_code',
			'alias' => 'bank_code',
			'name' => lang('Bank Code'),
			'formatter' => function ($d, $row) {
				return $d ?: '-';
			},
		);
		$columns[] = array(
			'dt' => $i++,
			'select' => 'banktype.external_system_id',
			'alias' => 'payment_id',
			'name' => lang('pay.bt.payment_api_id'),
			'formatter' => function ($d, $row) {
				return $d ?: '-';
			},
		);
		$columns[] = array(
			'dt' => $i++,
			'select' => 'banktype.enabled_withdrawal',
			'alias' => 'enabled_withdrawal',
			'name' => lang('report.p07'),
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
			'select' => 'banktype.enabled_deposit',
			'alias' => 'live_account',
			'name' => lang('report.p06'),
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
			'select' => 'banktype.createdOn',
			'alias' => 'createdOn',
			'name' => lang('pay.bt.createdon'),
			'formatter' => function ($d, $row) {
				return $d ?: '-';
			},
		);
		$columns[] = array(
			'dt' => $i++,
			'select' => 'banktype.updatedOn',
			'alias' => 'updatedOn',
			'name' => lang('pay.bt.updatedon'),
			'formatter' => function ($d, $row) {
				return $d ?: '-';
			},
		);
		$columns[] = array(
			'dt' => $i++,
			'select' => 'cb.username',
			'alias' => 'createdBy',
			'name' => lang('pay.bt.createdby'),
			'formatter' => function ($d, $row) {
				return $d ?: '-';
			},
		);
		$columns[] = array(
			'dt' => $i++,
			'select' => 'ub.username',
			'alias' => 'updatedBy',
			'name' => lang('pay.bt.updatedby'),
			'formatter' => function ($d, $row) {
				return $d ?: '-';
			},
		);
		$columns[] = array(
			'dt' => $i++,
			'select' => 'banktype.status',
			'alias' => 'status',
			'name' => lang('pay.bt.status'),
			'formatter' => function ($d, $row) use ($is_export) {
				if(!$is_export){
					return $d==Banktype::STATUS_ACTIVE ?  '<span class="text-success">'.lang("lang.active").'</span>' : '<span class="text-danger">'.lang("Blocked").'</span>';
				}
				return $d;
			},
		);

		$table ='banktype';

		if($is_export){
			$this->data_tables->options['is_export']=true;
			if(empty($csv_filename)){
				$csv_filename=$this->utils->create_csv_filename(__FUNCTION__);
			}
			$this->data_tables->options['csv_filename']=$csv_filename;
		}

		$where[] =  "banktype.is_hidden = 1";
		$result = $this->data_tables->get_data($request, $columns, $table, $where, $values, $joins);

		if($is_export){
		    //drop result if export
			return $csv_filename;
		}
		return $result;
	}

	/**
	 * detail: get count deposit member
	 *
	 * @param string $type
	 * @param string $date
	 * @return array
	 */
	public function get_count_deposit_member($type = 'YEAR', $date) {
		if($type == 'DATE') {
			$date_str = "trans_date";
		}
		else if($type == 'YEAR_MONTH') {
			$date_str = "trans_year_month";
		} else {
			$date_str = "trans_year";
		}

		if(is_array($date) && isset($date['dateFrom']) && isset($date['dateTo'])){
			$dateFrom = $date['dateFrom'];
			$dateTo = $date['dateTo'];
		$sql = <<<EOD
SELECT
	count(
	DISTINCT ( to_id )) AS count_deposit_member
FROM
	transactions
	JOIN player ON player.playerId = transactions.to_id
	AND player.deleted_at IS NULL
WHERE
	transaction_type = ?
	AND to_type = ?
	AND transactions.STATUS = ?
	AND {$date_str} >= ?
	AND {$date_str} <= ?
EOD;

			$params=[
	            TRANSACTIONS::DEPOSIT,
	            TRANSACTIONS::PLAYER,
	            self::STATUS_NORMAL,
	            strval($dateFrom),
	            strval($dateTo),
	        ];
		}else{
		$sql = <<<EOD
SELECT
	count(
	DISTINCT ( to_id )) AS count_deposit_member
FROM
	transactions
	JOIN player ON player.playerId = transactions.to_id
	AND player.deleted_at IS NULL
WHERE
	transaction_type = ?
	AND to_type = ?
	AND transactions.STATUS = ?
	AND {$date_str} = ?
EOD;

			$params=[
	            TRANSACTIONS::DEPOSIT,
	            TRANSACTIONS::PLAYER,
	            self::STATUS_NORMAL,
	            strval($date),
	        ];
		}


        $this->utils->debug_log('get_total_deposit_member sql', $sql, $params);
        return $this->runOneRawSelectSQLArray($sql, $params);
	}

	public function get_count_active_member($date, $table_total_player_game = 'total_player_game_day') {
		$where_param = "date";
		if($table_total_player_game == "total_player_game_month"){
			$where_param = "month";
		}

		$sql = <<<EOD
SELECT
	count(
	DISTINCT ( player_id )) AS count_active_member
FROM
	{$table_total_player_game}
WHERE
	id > 0 AND betting_amount > 0
	AND {$where_param} = ?
EOD;
        $params=[
            $date
        ];

        $this->utils->debug_log('get_count_active_member sql', $sql, $params);
        return $this->runOneRawSelectSQLArray($sql, $params);
    }

    public function report_summary_2_year_month($trans_year_month_from, $trans_year_month_to) {
		$trans_year_month_from = date("Ym", strtotime($trans_year_month_from));
		$trans_year_month_to = date("Ym", strtotime($trans_year_month_to));

		$readOnlyDB = $this->getReadOnlyDB();
		$this->load->model('transactions');
		$readOnlyDB->select("trans_year_month");
		$readOnlyDB->select_sum(sprintf('IF(transaction_type = %s, amount, 0)', Transactions::DEPOSIT), 'total_deposit');
		$readOnlyDB->select_sum(sprintf('IF(transaction_type = %s, amount, 0)', Transactions::WITHDRAWAL), 'total_withdraw');
		$readOnlyDB->select_sum(sprintf('(CASE WHEN transaction_type IN (%s) THEN amount WHEN transaction_type = %s THEN -amount ELSE 0 END)', implode(',', array(Transactions::ADD_BONUS, Transactions::MEMBER_GROUP_DEPOSIT_BONUS, Transactions::PLAYER_REFER_BONUS, Transactions::RANDOM_BONUS)), Transactions::SUBTRACT_BONUS), 'total_bonus');
		$readOnlyDB->select_sum(sprintf('IF(transaction_type = %s, amount, 0)', Transactions::AUTO_ADD_CASHBACK_TO_BALANCE), 'total_cashback');
		$readOnlyDB->select_sum(sprintf('IF(transaction_type IN (%s), amount, 0)', Transactions::FEE_FOR_OPERATOR), 'total_transaction_fee');
		$readOnlyDB->select_sum(sprintf('IF(transaction_type IN (%s), amount, 0)', Transactions::FEE_FOR_PLAYER), 'total_player_fee');
		$readOnlyDB->select_sum(sprintf('(CASE WHEN transaction_type = %s THEN amount WHEN transaction_type = %s THEN -amount ELSE 0 END)', Transactions::DEPOSIT, Transactions::WITHDRAWAL), 'bank_cash_amount');
		$readOnlyDB->select_sum(sprintf('IF(transaction_type IN (%s), amount, 0)', Transactions::WITHDRAWAL_FEE_FOR_PLAYER), 'total_withdrawal_fee_from_player');
		$readOnlyDB->select_sum(sprintf('IF(transaction_type IN (%s), amount, 0)', Transactions::WITHDRAWAL_FEE_FOR_OPERATOR), 'total_withdrawal_fee_from_operator');

		$readOnlyDB->from('transactions');
		$readOnlyDB->join('player' , "transactions.to_type = 2 AND player.playerId = transactions.to_id");

		$readOnlyDB->where("trans_year_month >=", $trans_year_month_from);
		$readOnlyDB->where("trans_year_month <=", $trans_year_month_to);
		$readOnlyDB->where('transactions.status', transactions::APPROVED);
		$readOnlyDB->where('player.deleted_at IS NULL');
		$readOnlyDB->group_by("trans_year_month");
        $this->utils->debug_log('the summary report 2 12714 ------>', $readOnlyDB->_compile_select());
		$query = $readOnlyDB->get();

		return $query->result_array();
	}

	public function adjustment_score_report($request, $is_export = false, $player_id = null) {

		$this->load->library('data_tables');
		$this->load->model(array('player_score_model', 'player_model'));

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
				'select' => 'score_history.player_id',
			),
			// 0 - regdate
			array(
				'dt' => $i++,
				'alias' => 'created_at',
				'select' => 'score_history.created_at',
				'name' => lang("score_history.datetime"),
			),
			array(
				'dt' => $i++,
				'alias' => 'username',
				'select' => 'score_history.player_id',
				'name' => lang('Username'),
				'formatter' => function ($d, $row) use ($is_export) {
					$username = $this->player_model->getUsernameById($d);

					if (!$is_export) {
						return sprintf('<a href="/player_management/userInformation/%s">%s</a>', $row['player_id'], $username);
					}else{
						return $d;
					}
				}
			),
			array(
				'dt' => $i++,
				'alias' => 'score',
				'select' => 'score_history.score',
				'name' => lang('score_history.score'),
				'formatter' => function ($d, $row) use ($is_export) {
					if (!$is_export) {
						return '<strong>'.$this->utils->formatCurrencyNoSym($d).'</strong>';
					}else{
						return $this->utils->formatCurrencyNoSym($d);
					}
				}
			),
			array(
				'dt' => $i++,
				'alias' => 'before_score',
				'select' => 'score_history.before_score',
				'name' => lang('score_history.before_score'),
				'formatter' => function ($d, $row) use ($is_export) {
					if (!$is_export) {
						return '<strong>'.$this->utils->formatCurrencyNoSym($d).'</strong>';
					}else{
						return $this->utils->formatCurrencyNoSym($d);
					}
				}
			),
			array(
				'dt' => $i++,
				'alias' => 'after_score',
				'select' => 'score_history.after_score',
				'name' => lang('score_history.after_score'),
				'formatter' => function ($d, $row) use ($is_export) {
					if (!$is_export) {
						return '<strong>'.$this->utils->formatCurrencyNoSym($d).'</strong>';
					}else{
						return $this->utils->formatCurrencyNoSym($d);
					}
				}
			),

			array(
				'dt' => $i++,
				'alias' => 'type',
				'select' => 'score_history.type',
				'name' => lang("score_history.type"),
				'formatter' => function ($d, $row) use ($is_export) {
					if($d == 1){
						return lang("score_history.add");
					}else if ($d == 2){
						return lang("score_history.subtract");
					}
				}
			),
			array(
				'dt' => $i++,
				'alias' => 'note',
				'select' => 'score_history.note',
				'name' => lang('score_history.note'),
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
				'alias' => 'action_log',
				'select' => 'score_history.action_log',
				'name' => lang('score_history.action_log'),
				'formatter' => function ($d, $row) use ($is_export,$na) {
					if(!empty($d)){
						return $d;
					}else{
						return $na;
					}
				}
			)
		);

		$this->utils->debug_log(__METHOD__, 'columns', $columns);

		$table = 'score_history';
		$joins = array(
			'player' => 'player.playerId = score_history.player_id',
		);

		if (isset($input['by_date_from'], $input['by_date_to'])) {
			$where[] = "DATE(score_history.created_at) >=?";
			$where[] = "DATE(score_history.created_at) <=?";
			$values[] = $input['by_date_from'];
			$values[] = $input['by_date_to'];
		}

		// if (isset($input['dateRangeValueStart'], $input['dateRangeValueEnd'])) {
		// 	$where[] = "DATE(score_history.create_at) >=?";
		// 	$where[] = "DATE(score_history.create_at) <=?";
		// 	$values[] = $input['dateRangeValueStart'];
		// 	$values[] = $input['dateRangeValueEnd'];
		// 	$useIndexStr='force index(idx_created_at)';
		// }

		if (isset($input['by_username'], $input['search_by'])) {
            if ($input['search_by'] == 1) {
                $where[] = "player.username LIKE ?";
                $values[] = '%' . $input['by_username'] . '%';
            } else if ($input['search_by'] == 2) {
                $where[] = "player.username = ?";
                $values[] = $input['by_username'];
            }
        }

		if (isset($input['by_score_type'])) {
			$where[] = "score_history.type = ?";
			$values[] = $input['by_score_type'];
		} else {
			$where[] = "score_history.type in (?,?,?)";
			$values[] = '';
			$values[] = Player_score_model::MANUAL_ADD_SCORE;
			$values[] = Player_score_model::MANUAL_SUBTRACT_SCORE;
		}


		if (!empty($player_id)) {
			$where[] = "score_history.player_id = ?";
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
		$result = $this->data_tables->get_data($request, $columns, $table, $where, $values, $joins, $group_by, $having);

		if($is_export){
			return $csv_filename;
		}

		return $result;
	}

	public function remote_wallet_balance_history($player_id , $request, $is_export = false, $csv_filename = null) {
		$readOnlyDB = $this->getReadOnlyDB();
        $this->load->library('data_tables', array("DB" => $readOnlyDB));

		if(!$is_export){
			$request = $this->input->post();
		}
		$input = $this->data_tables->extra_search($request);

		$date=new DateTime();
		$dateStr=$date->format('Y-m-d H:i:s');
		if (isset($input['date_from'])) {
			$date=new DateTime($input['date_from']);
			$dateStr=$date->format('Y-m-d H:i:s');
		}
		$table = $this->utils->getRemoteWalletBalanceHistoryTable($dateStr);

		$this->data_tables->is_export = $is_export;

		$controller = $this;

		# START DEFINE COLUMNS #################################################################################################################################################
		$i = 0;
		$columns = array(
			array(
				'alias' => 'transaction_type',
				'select' => "{$table}.transaction_type",
			),
			array(
				'alias' => 'game_platform_id',
				'select' => "{$table}.game_platform_id",
			),
			array(
				'select' => "{$table}.player_id",
				'alias' => 'player_id',
			),
			array(
				'dt' => $i++,
				'select' => "{$table}.id",
				'alias' => 'action',
				'name' => lang('Action'),
				'formatter' => function ($d, $row) use($is_export) {
					if(!$is_export){
						$str =" <input type='button' class='btn-remote-wallet-query-status btn btn-success btn-xs m-b-5' data-id='" . $d . "'  value='".lang('Query Status')."'>";

						$str.=" <input type='button' class='btn-remote-wallet-auto-fix btn btn-danger btn-xs m-b-5' data-id='" . $d . "' value='".lang('Auto Fix')."'>";
						return $str;
					}
				},
			),
			array(
				'dt' => (!empty($player_id)) ? NULL : $i++,
				'select' => 'player.username',
				'name' => lang('Player Username'),
				'alias' => 'player_username',
				'formatter' => function ($d, $row) use($is_export) {
					if(!$is_export){
						return "<a href='".site_url('/player_management/userInformation/'.$row['player_id'])."' target='_blank'>".$d."</a>";
					}
					return $d;
				},
			),
			array(
				'dt' => $i++,
				'alias' => 'transaction_date',
				'select' => "{$table}.transaction_date",
				'formatter' => 'dateTimeFormatter',
				'name' => lang('Date')
			),
			array(
				'dt' => $i++,
				'alias' => 'amount',
				'select' => "{$table}.amount",
				'name' => lang('Amount'),
				'formatter' => function ($d, $row) use ($is_export) {
					$val = $d;
					$type = $row['transaction_type'];
					switch ($type) {
						case 'increase_balance':
							if($val>0){
								if($is_export){
									return '+ '.$this->data_tables->currencyFormatter($val);
								}
								return sprintf('<span style="font-weight:bold;color:#008000">%s</span>','+ '.$this->data_tables->currencyFormatter($val));
							}
							return $this->data_tables->currencyFormatter(0);
							break;
						case 'decrease_balance':
							if($val>0){
								if($is_export){
									return '- '.$this->data_tables->currencyFormatter($val);
								}
								return sprintf('<span style="font-weight:bold;color:#8B0000">%s</span>','- '.$this->data_tables->currencyFormatter($val));
							}
							return $this->data_tables->currencyFormatter(0);
							break;
						default:
							return $this->data_tables->currencyFormatter(0);
							break;
					}
				},
			),
			array(
				'dt' => $i++,
				'alias' => 'before_balance',
				'select' => "{$table}.before_balance",
				'formatter' => 'currencyFormatter',
				'name' => lang('Before Balance'),
			),
			array(
				'dt' => $i++,
				'alias' => 'after_balance',
				'select' => "{$table}.after_balance",
				'formatter' => 'currencyFormatter',
				'name' => lang('After Balance'),
			),
			array(
				'dt' => $i++,
				'alias' => 'game_platform',
				'select' => 'external_system.system_code',
				'name' => lang('Game Platform'),
                'formatter' => function ($d, $row) {
					$d_parsed = json_decode($d, true);
					$val = $d . " ({$row['game_platform_id']})";

					return $val;
				},
			),
			array(
				'dt' => $i++,
				'alias' => 'external_uniqueid',
				'select' => "{$table}.external_uniqueid",
				'name' => lang('External Unique ID'),
			),
			array(
				'dt' => $i++,
				'alias' => 'status',
				'select' => "{$table}.status",
				'name' => lang('Status'),
				'formatter' => function ($d, $row) use($is_export) {
					if(!is_null($d)){
						if(!empty($d)){
							return lang("Exist");
						} else {
							return lang("Missing");
						}
					}
					return "N/A";
				},
			),
			array(
				'dt' => $i++,
				'alias' => 'transfer_status',
				'select' => "{$table}.transfer_status",
				'name' => lang('Query Status'),
				'formatter' => function ($d, $row) use($is_export) {
					if(!empty($d)){
						return $d;
					}
					return "N/A";
				},
			),
			array(
				'dt' => $i++,
				'alias' => 'reason',
				'select' => "{$table}.reason",
				'name' => lang('Reason'),
				'formatter' => function ($d, $row) use($is_export) {
					if(!empty($d)){
						return $d;
					}
					return "N/A";
				},
			),
			array(
				'dt' => $i++,
				'alias' => 'fix_flag',
				'select' => "{$table}.fix_flag",
				'name' => lang('Fix Flag'),
				'formatter' => function ($d, $row) use($is_export) {
					if(!is_null($d)){
						if(!empty($d)){
							return lang("Fix");
						} else {
							return lang("Not yet fix");
						}
					}
					return "N/A";
				},
			),
		);
		# END DEFINE COLUMNS #################################################################################################################################################

		$joins = array(
			'external_system' => "external_system.original_game_platform_id={$table}.game_platform_id",
			'player' => "player.playerId = {$table}.player_id",
		);

		# START PROCESS SEARCH FORM #################################################################################################################################################
		$where = array();
		$values = array();

		if ($player_id) {
			$where[] = "{$table}.player_id = ?";
			$values[] = $player_id;
		}

		if (isset($input['by_game_platform_id']) && !empty($input['by_game_platform_id'])) {
			$where[] = "{$table}.game_platform_id = ?";
			$game_platform_id = null;
			$api = $this->utils->loadExternalSystemLibObject($input['by_game_platform_id']);
			if($api){
				$game_platform_id = $api->getOriginalPlatformCode();
			}
			$values[] = $game_platform_id;
		}

		if (isset($input['date_from'], $input['date_to'])) {
			$where[] = "{$table}.transaction_date BETWEEN ? AND ?";
			$values[] = $input['date_from'];
			$values[] = $input['date_to'];
		}

		if (isset($input['player_username'])) {
			$where[] = "player.username = ?";
			$values[] = $input['player_username'];
		}

		# END PROCESS SEARCH FORM #################################################################################################################################################
		if($is_export){
            $this->data_tables->options['is_export']=true;
            if(empty($csv_filename)){
                $csv_filename=$this->utils->create_csv_filename(__FUNCTION__);
            }
            $this->data_tables->options['csv_filename']=$csv_filename;
        }
		$result = $this->data_tables->get_data($request, $columns, $table, $where, $values, $joins);
		if($is_export){
			return $csv_filename;
		}

		return $result;
	}
}
////END OF FILE/////////
