<?php

require_once dirname(__FILE__) . '/base_model.php';

require_once dirname(__FILE__) . '/modules/report_module_player.php';
require_once dirname(__FILE__) . '/modules/report_module_player_center.php';
require_once dirname(__FILE__) . '/modules/report_module_transaction.php';
require_once dirname(__FILE__) . '/modules/report_module_game.php';
require_once dirname(__FILE__) . '/modules/report_module_aff.php';
require_once dirname(__FILE__) . '/modules/report_module_agency.php';
require_once dirname(__FILE__) . '/modules/super_report_module.php';
require_once dirname(__FILE__) . '/modules/report_module_generate.php';
require_once dirname(__FILE__) . '/modules/kingrich_summary_report_generate.php';
require_once dirname(__FILE__) . '/modules/kingrich_send_data_scheduler_generate.php';
require_once dirname(__FILE__) . '/modules/iovation_module.php';
require_once dirname(__FILE__) . '/modules/hedge_in_ag_module.php';
require_once dirname(__FILE__) . '/modules/seamless_balance_history_module.php';
require_once dirname(__FILE__) . '/modules/report_module_player_login_via_same_ip.php';
require_once dirname(__FILE__) . '/modules/report_module_player_remarks_list.php';


require_once dirname(__FILE__) . '/modules/report_module_player_basic_amount_list.php';
require_once dirname(__FILE__) . '/modules/report_module_custom_list.php';
require_once dirname(__FILE__) . '/modules/report_module_messages_list.php';

/**
 *
 * General behaviors include
 * * duplicate account report
 * * get promotion report
 * * get cashback report
 *
 * @category report_model
 * @version 1.8.10
 * @copyright 2013-2022 tot
 */

class Report_model extends BaseModel {

	public function __construct() {
		parent::__construct();

		$this->initialCrrExecutionTime();
	}

	const EXPORT_FORMAT_EXCEL = 'excel';
	const EXPORT_FORMAT_CSV = 'csv';

	const EXPORT_TYPE_DIRECT = 'direct';
	const EXPORT_TYPE_QUEUE = 'queue';

	const EXPORT_CSV_EOL = PHP_EOL;

	const FILTER_EMPTY_DATA = 1;
	const ONLY_EMPTY_DATA = 2;

	public $CRR_TIME_LIMIT = 480; // 8* 60 sec

	public $export_token;
	public $export_pid;


	use report_module_player;
	use report_module_player_center;
	use report_module_transaction;
	use report_module_game;
	use report_module_aff;
	use super_report_module;
	use report_module_generate;
	use kingrich_summary_report_generate;
	use kingrich_send_data_scheduler_generate;
	use iovation_module;
	use hedge_in_ag_module;
	use seamless_balance_history_module;
    use report_module_agency;
	use report_module_player_login_via_same_ip;
	use report_module_player_basic_amount_list;
	use report_module_custom_list;
	use report_module_messages_list;
	use report_module_player_remarks_list;

	protected function safeGetParam($input, $name) {
		return isset($input[$name]) ? $input[$name] : null;
	}

	/**
	 * detail: duplicate account report
	 *
	 * @param array $request
	 * @param Boolean $is_export
	 *
	 * @return array
	 */
	public function duplicateAccountReport($request, $is_export = false) {
		$this->load->library(array('duplicate_account', 'data_tables'));
		$this->load->model(array('player_model'));

		$input = $this->data_tables->extra_search($request);
		$this->utils->debug_log('input', $input);

		$player_id = null;
		if (isset($input['by_username'])) {
			$player_id = $this->player_model->getPlayerIdByUsername($input['by_username']);
		}

		if ($player_id != null) {
			$data = $this->duplicate_account->getDuplicateAccountsJSON($player_id, 1);
		} else {
			$data = array();
		}
		$result = array(
			"draw" => '',
			"recordsFiltered" => count($data),
			"recordsTotal" => count($data),
			"data" => $data,
			"header_data" => '',
		);
		return $result;
	}

	/**
	 * Datatable.js datasource for table duplicate_account_total
	 *
	 * @param	array	$request	Datatable search form
	 * @param	boolean	$is_export	export flag, used by export data methods (see below)
	 * @see		/api/duplicate_account_total
	 * @see		/export_data/duplicate_account_total
	 * @return	array	server-side data pack for datatable.js
	 */
	public function duplicateAccountTotal($request, $is_export = false) {
		$readOnlyDB = $this->getReadOnlyDB();
		$this->load->library('data_tables', array("DB" => $readOnlyDB));
		$this->data_tables->is_export = $is_export;

		$request['draw'] = '';
		// Show only top 2000 players (That should be enough)
		$request['start'] = 0;
		$request['length'] = 2000;
		$external_order = 'total_rate DESC';

		$input 		= $this->data_tables->extra_search($request);
		$table 		= 'duplicate_account_total';
		$joins		= [];
		$where		= [];
		$values		= [];
		$group_by	= [];
		$having		= [];

		$columns = [
			[ 'dt' => 0 , 'alias' => 'username'	, 'select' => 'username' , 'name' => lang('player.01') ,
				'formatter' => function ($d, $row) use ($is_export) {
					if ($is_export) { return $d; }
					$title = lang('Click to show duplicate account details for player');
					return "<a href='/report_management/duplicate_account_detail_by_username/{$d}' title='{$title}' class='dup_total dup_details'>{$d}</a>";
				}
			] ,
			[ 'dt' => 1 , 'alias' => 'total_rate' , 'select' => 'total_rate' , 'name' => lang('Total Rate')] ,

			[ 'dt' => 2 , 'alias' => 'updated_at' , 'select' => 'updated_at' , 'name' => lang('Last Updated On')],
		];

		if (isset($input['by_username'])) {
			$where[]	= 'username LIKE ?';
			$values[]	= "%{$input['by_username']}%";
		}

		$result = $this->data_tables->get_data($request, $columns, $table, $where, $values, $joins, $group_by, $having, $external_order);

		return $result;
	}

	public function usernameExistsInDuplicateAccountInfo($username) {
		if (empty($username)) {
			return false;
		}
		$this->db->from('duplicate_account_info')
			->where('userName', $username);

		return $this->runExistsResult();
	}

	public function duplicateAccountInfo($username, $for_player_info = false, $is_request_for_modal=false, $is_count_related_total_rate=false) {
		$readOnlyDB = $this->getReadOnlyDB();
		$this->load->library('data_tables', array("DB" => $readOnlyDB));
		$this->load->library('player_manager');

		$request['draw'] = '';

		$table 		= 'duplicate_account_info';
		$joins		= [];
		$where		= [];
		$values		= [];
		$group_by	= [];
		$having		= [];

		$i = 0;
		$columns = [];
		if ($for_player_info == true) {
			$columns[] = [ 'dt' => $i++ , 'alias' => 'setTag', 'select' => 'dup_userName' ,
				'formatter' => function ($d, $row) {
					$player_id = $this->player_manager->getPlayerIdByUsername($d);
					return "<input type='checkbox' id='tags' name='set_tag[]' class='checktags' value='{$player_id}' onclick='countTagBoxes()'>";

				}
			];

			# OGP-3524
			if($this->utils->isEnabledFeature('link_account_in_duplicate_account_list')){
				$columns[] = [ 'dt' => $i++ , 'alias' => 'setLinkAcct', 'select' => 'dup_userName' ,
					'formatter' => function ($d, $row) use($username) {
						$player_id = $this->player_manager->getPlayerIdByUsername($d);
						$this->load->model('linked_account_model');
                        if (!empty($this->linked_account_model->getPlayerLinkedAccountLinkIdByUsername($username))) {
                            $linkId = $this->linked_account_model->getPlayerLinkedAccountLinkIdByUsername($username)['link_id'];
                            $isAcctLinkAlready = $this->linked_account_model->isPlayerAcctWasLinkedAccountAlready($d, $linkId) ? "checked" : "";
                            return "<input type='checkbox' id='linkaccts' name='link_acct[]' class='checkaccts' value='{$player_id}' username_val='{$d}' {$isAcctLinkAlready} onclick='countLinkBoxes()'>";
                        }
					}
				];
			}
		}
		$columns = array_merge($columns,
		[
			[ 'dt' => $i++ , 'alias' => 'userName'             , 'select' => 'userName'           ] ,
			[ 'dt' => $i++ , 'alias' => 'total_rate'           , 'select' => 'total_rate'         ] ,
			[ 'dt' => $i++ , 'alias' => 'dup_userName'         , 'select' => 'dup_userName',
				'formatter' => function ($d, $row) use ($is_request_for_modal) {
					$title = lang('Click to show duplicate account details for player');
					if($is_request_for_modal) {
						return $d;
					}
					else {
						return "<a href='#' title='{$title}' class='dup_total dup_details dup_modal_trigger' data-username={$d}>{$d}</a>";
					}
				}
			]
		]);

		if($is_count_related_total_rate) {
			$columns = array_merge($columns,
			[
				[ 'dt' => $i++ , 'alias' => 'related_total_rate'         , 'select' => 'dup_userName',
					'formatter' => function ($dup_userName, $row) use ($username) {
						$this->db->select('SUM(total_rate) as related_total_rate');
						$this->db->from('duplicate_account_info')
							->where('userName', $dup_userName)
							->where('dup_userName != ', $username);
						return $this->runOneRowOneField('related_total_rate');
					}
				]
			]);
		}

		$dupSearchColumn = [
            ['alias' => 'dup_regIp'            , 'select' => 'dup_regIp'          ] ,
            ['alias' => 'dup_loginIp'          , 'select' => 'dup_loginIp'        ] ,
            ['alias' => 'dup_depositIp'        , 'select' => 'dup_depositIp'      ] ,
            ['alias' => 'dup_withDrawIp'       , 'select' => 'dup_withDrawIp'     ] ,
            ['alias' => 'dup_TranMain2SubIp'   , 'select' => 'dup_TranMain2SubIp' ] ,
            ['alias' => 'dup_TranSub2MainIp'   , 'select' => 'dup_TranSub2MainIp' ] ,
            ['alias' => 'dup_realName'         , 'select' => 'dup_realName'       ] ,
            ['alias' => 'dup_passwd'           , 'select' => 'dup_passwd'         ] ,
            ['alias' => 'dup_email'            , 'select' => 'dup_email'          ] ,
            ['alias' => 'dup_mobile'           , 'select' => 'dup_mobile'         ] ,
            ['alias' => 'dup_address'          , 'select' => 'dup_address'        ] ,
            ['alias' => 'dup_city'             , 'select' => 'dup_city'           ] ,
            ['alias' => 'dup_country'          , 'select' => 'dup_country'        ] ,
            ['alias' => 'dup_cookie'           , 'select' => 'dup_cookie'         ] ,
            ['alias' => 'dup_referrer'         , 'select' => 'dup_referrer'       ] ,
            ['alias' => 'dup_device'           , 'select' => 'dup_device'         ] ,
        ];
        $dupEnabledColumn = $this->utils->getConfig('duplicate_account_info_enalbed_condition');

        foreach ($dupSearchColumn as $key => $row) {
            $_del = true;
            foreach ($dupEnabledColumn as $column) {
                $_rowName = $row['select'];
                if ($column == 'ip' && strpos($_rowName, 'Ip') !== false) {
                    $_del = false; break;
                }
                if ($column == 'password' && strpos($_rowName, 'passwd') !== false) {
                    $_del = false; break;
                }
                if ($column == 'realname' && strpos($_rowName, 'realName') !== false) {
                    $_del = false; break;
                }
                $_dupColumn = 'dup_' . $column;
                if ($_dupColumn == $_rowName) {
                    $_del = false; break;
                }
            }
            if ($_del) {
                unset($dupSearchColumn[$key]);
                continue;
            }
            $dupSearchColumn[$key]['dt'] = $i++;
        }

        $columns = array_merge($columns, $dupSearchColumn);
		$where[]	= 'userName = ?';
		$values[]	= $username;

        $is_exclude_ip_enabled = $this->utils->isEnabledFeature('exclude_ips_in_duplicate_account');
        if($is_exclude_ip_enabled){
            $list_of_ips_to_be_excluded_in_duplicate_account = $this->utils->getConfig('list_of_ips_to_be_excluded_in_duplicate_account');
            $list_of_ips_to_be_excluded_in_duplicate_account = implode(',', $list_of_ips_to_be_excluded_in_duplicate_account);

            $this->utils->debug_log("Duplicate account excluded IP's ===========>", $list_of_ips_to_be_excluded_in_duplicate_account);
            if(!empty($list_of_ips_to_be_excluded_in_duplicate_account)){
                $where[]    = '(dup_regIp NOT IN (?) AND dup_loginIp NOT IN (?) AND dup_depositIp NOT IN (?) AND dup_withDrawIp NOT IN (?) AND dup_TranMain2SubIp NOT IN (?) AND dup_TranSub2MainIp NOT IN (?))';
                $values[]   = $list_of_ips_to_be_excluded_in_duplicate_account;
                $values[]   = $list_of_ips_to_be_excluded_in_duplicate_account;
                $values[]   = $list_of_ips_to_be_excluded_in_duplicate_account;
                $values[]   = $list_of_ips_to_be_excluded_in_duplicate_account;
                $values[]   = $list_of_ips_to_be_excluded_in_duplicate_account;
                $values[]   = $list_of_ips_to_be_excluded_in_duplicate_account;
            }
        }

        $result = $this->data_tables->get_data($request, $columns, $table, $where, $values, $joins, $group_by, $having);

		return $result;
	}

	/**
	 * detail: get promotion report
	 *
	 * @param array $request
	 * @param Boolean $is_export
	 * @return array
	 */
	public function promotionReport($request, $is_export = false) {

		$readOnlyDB = $this->getReadOnlyDB();

		$this->load->library('data_tables', array("DB" => $readOnlyDB));
		$this->load->model(array('transactions', 'player_model', 'player_promo', 'promorules', 'withdraw_condition'));
        $this->load->helper(['player_helper']);
		$promorules = $this->promorules;
		$this->data_tables->is_export = $is_export;

		$transaction_types = array(Transactions::ADD_BONUS, Transactions::MEMBER_GROUP_DEPOSIT_BONUS,
			Transactions::PLAYER_REFER_BONUS, Transactions::RANDOM_BONUS);

		# START DEFINE COLUMNS #################################################################################################################################################
		// $i = 0;
		$date_column = 0;
		$username_column = 1;
		$tag_column = 2;
		$affiliate_column = 3;
		$group_level_column = 4;
		$promotion_column = 5;
		$promo_status_column = 6;
		$amount_column = 7;
		$signup_date_column = 8;
		$columns = array(
			array(
				'alias' => 'player_id',
				'select' => 'playerpromo.playerId',
			),
			array(
				'alias' => 'promoTypeName',
				'select' => 'promotype.promoTypeName',
			),
            array(
                'select' => 'playerpromo.playerpromoId',
                'alias' => 'playerpromoId',
            ),
			array(
				'alias' => 'transaction_type',
				'select' => 'transactions.transaction_type',
			),
			array(
				'alias' => 'detail_status',
				'select' => 'withdraw_conditions.detail_status',
			),
			array(
				'dt' => $date_column,
				'alias' => 'created_at',
				'select' => 'transactions.created_at',
				'formatter' => 'dateTimeFormatter',
				'name' => lang('Released Date'),
			),
			array(
				'dt' => $username_column,
				'alias' => 'username',
				'select' => 'player.username',
				'formatter' => function ($d, $row) use ($is_export) {
					if ($is_export) {
						return ($d ? $d : lang('lang.norecyet'));
					} else {
						return '<i class="fa fa-user" ></i> ' . ($d ? '<a href="/player_management/userInformation/' . $row['player_id'] . '" target="_blank">' . $d . '</a>' : '<i class="text-muted">' . lang('lang.norecyet') . '</i>');
					}
				},
				'name' => lang('report.pr01'),
			),
            array(
                'dt' => $tag_column,
                'alias' => 'tagName',
                'select' => 'playerpromo.playerId',
                'name' => lang("player.41"),
                'formatter' => function ($d) use ($is_export) {
                    return player_tagged_list($d, $is_export);
                },
            ),
            array(
                'dt' => $affiliate_column,
                'alias' => 'affiliate',
                'select' => 'affiliates.username',
                'formatter' => function ($d, $row) use ($is_export) {
                    if ($is_export) {
                        return ($d ? $d : lang('N/A'));
                    } else {
                        return $d ? $d : '<i class="text-muted">' . lang('N/A') . '</i>';
                    }
                },
                'name' => lang('a_header.affiliate'),
            ),
			array(
				'dt' => $group_level_column,
				'alias' => 'group_level',
				'select' => 'CONCAT(vipsetting.groupName, \'|\', vipsettingcashbackrule.vipLevelName )',
				'formatter' => function ($d) use ($is_export) {
                    $d = (explode("|",$d));
                    if(isset($d[1])){
                        return lang($d[0]).' - '.lang($d[1]);
                    }else{
                        return lang($d[0]);
                    }
				},
				'name' => lang('VIP Level'),
			),
			array(
				'alias' => 'transPromoTypeName',
				'select' => 'transpromotype.promoTypeName',
			),
			array(
				'alias' => 'promorulesId',
				'select' => 'promorules.promorulesId',
			),
			array(
				'dt' => $promotion_column,
				'alias' => 'promotion',
				'select' => 'promorules.promoName',
				'formatter' => function ($d, $row) use ($is_export, $promorules) {
					list($promoName, $promoType, $promoDetails) = $promorules->getPromoNameAndType($row['transaction_type'], $row['transPromoTypeName'],
						$row['promoTypeName'], $d, null, $row['group_level']);

					$fullPromoDesc = implode(' - ', array(lang($promoType), $promoName));
					if ($is_export) {
						return $fullPromoDesc;
					} else {
						$showPromoRuleDetailsText = lang('cms.showPromoRuleDetails');
						$promolink = <<<EOD
<a href='###' data-toggle='modal' data-target='#promoDetails' onclick='return viewPromoRuleDetails({$row['promorulesId']})'>
<span data-toggle='tooltip' data-original-title='$showPromoRuleDetailsText' data-placement="top">
   {$fullPromoDesc}
</span>
</a>
EOD;
						return !empty($fullPromoDesc) ? $promolink : '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
					}
					//return $d;
				},
				'name' => lang('Promo Rule'),
			),
			array(
				'dt' => $promo_status_column,
				'alias' => 'promo_status',
				'select' => 'playerpromo.transactionStatus',
				'name' => lang('Promotion Status'),
				'formatter' => function ($d, $row) use ($is_export) {
					if ($d == Player_promo::TRANS_STATUS_APPROVED) {
						$d = lang('Approved');
					} else if ($d == Player_promo::TRANS_STATUS_DECLINED) {
						$d = lang('Declined');
					// if the status is 9
					} else if ($d == Player_promo::TRANS_STATUS_FINISHED_WITHDRAW_CONDITION) {
						$d = lang('Finished Withdraw Condition');
					} else if ($d == Player_promo::TRANS_STATUS_FINISHED_MANUALLY_CANCELLED_WITHDRAW_CONDITION) {
						$d = lang('Manually Cancelled');
					} else if ($d == Player_promo::TRANS_STATUS_FINISHED_AUTOMATICALLY_CANCELLED_WITHDRAW_CONDITION) {
						$d = lang('Automatically Cancelled');
					}

					if($row['detail_status']==Withdraw_condition::DETAIL_STATUS_CANCELLED_MANUALLY){
						$d = lang('Manually Cancelled');
					}elseif($row['detail_status']==Withdraw_condition::DETAIL_STATUS_CANCELLED_DUE_TO_SMALL_BALANCE){
						$d = lang('Cancelled due to small balance');
					}

					if ($is_export) {
						return $d;
					} else {
						return $d ? $d : '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
					}
				},
			),
			array(
				'dt' => $amount_column,
				'alias' => 'amount',
				'select' => 'transactions.amount',
				'formatter' => function ($d) use ($is_export) {
					if ($is_export) {
						return $this->utils->formatCurrencyNoSym($d);
					} else {
						return $d == 0 ? '<span class="text-muted">' . $this->utils->formatCurrencyNoSym($d) . '</span>' : '<strong>' . $this->utils->formatCurrencyNoSym($d) . '</strong>';
					}
				},
				'name' => lang('player.ut05'),
			),
			array(
                'dt' => $signup_date_column,
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
            )
		);
		# END DEFINE COLUMNS #################################################################################################################################################

		$table = 'playerpromo';
		$joins = array(
			'transactions' => "playerpromo.playerpromoId = transactions.player_promo_id and transactions.transaction_type IN (" . implode(',', $transaction_types) . ")",
			'player' => "playerpromo.playerId = player.playerId",
			'promorules' => "promorules.promorulesId = playerpromo.promorulesId",
			'promotype as transpromotype' => "transpromotype.promotypeId = transactions.promo_category",
			'promotype' => "promotype.promotypeId = promorules.promoCategory",
			'affiliates' => 'affiliates.affiliateId = player.affiliateId',
			'vipsettingcashbackrule' => 'vipsettingcashbackrule.vipsettingcashbackruleId = player.levelId',
			'vipsetting' => 'vipsetting.vipSettingId = vipsettingcashbackrule.vipSettingId',
			'withdraw_conditions' => "playerpromo.playerpromoid = withdraw_conditions.player_promo_id and withdraw_condition_type = 1 and playerpromo.playerId = withdraw_conditions.player_id",
		);

		# START PROCESS SEARCH FORM #################################################################################################################################################
		$where = array();
		$values = array();
		// $request = $this->input->post();
		$input = $this->data_tables->extra_search($request);
		$this->utils->debug_log('input', $input);

		if (isset($input['byBonusAmountLessThan'])) {
			$where[] = "transactions.amount <= ?";
			$values[] = $input['byBonusAmountLessThan'];
		}

		if (isset($input['byBonusAmountGreaterThan'])) {
			$where[] = "transactions.amount >= ?";
			$values[] = $input['byBonusAmountGreaterThan'];
		}

		if (isset($input['byPlayerLevel'])) {
			$where[] = "player.levelId = ?";
			$values[] = $input['byPlayerLevel'];
		}

		if (isset($input['byPromotionType'])) {
			$where[] = "transactions.promo_category = ?";
			$values[] = $input['byPromotionType'];
		}

		if (isset($input['byPromotionId'])) {
			$where[] = "promorules.promorulesId = ?";
			$values[] = $input['byPromotionId'];
		}

		if (isset($input['byPromotionStatus'])) {
			if ($input['byPromotionStatus'] == Player_promo::TRANS_STATUS_FINISHED_MANUALLY_CANCELLED_WITHDRAW_CONDITION) {
				$where[] = "withdraw_conditions.detail_status = ?";
				$values[] = Withdraw_condition::DETAIL_STATUS_CANCELLED_MANUALLY;
			} else if ($input['byPromotionStatus'] == Player_promo::TRANS_STATUS_FINISHED_AUTOMATICALLY_CANCELLED_WITHDRAW_CONDITION) {
				$where[] = "withdraw_conditions.detail_status = ?";
				$values[] = Withdraw_condition::DETAIL_STATUS_CANCELLED_DUE_TO_SMALL_BALANCE;
			}else{
				$where[] = "playerpromo.transactionStatus = ?";
				$values[] = $input['byPromotionStatus'];

				$where[] = "withdraw_conditions.detail_status <> ?";
				$values[] = Withdraw_condition::DETAIL_STATUS_CANCELLED_DUE_TO_SMALL_BALANCE;

				$where[] = "withdraw_conditions.detail_status <> ?";
				$values[] = Withdraw_condition::DETAIL_STATUS_CANCELLED_MANUALLY;
			}
		}

		if ($this->safeGetParam($input, 'enableDate') == 'true') {

			if (isset($input['byBonusPeriodJoinedFrom'])) {
				$where[] = "transactions.created_at >= ?";
				$values[] = $input['byBonusPeriodJoinedFrom'] . ' 00:00:00';
			}

			if (isset($input['byBonusPeriodJoinedTo'])) {
				$where[] = "transactions.created_at <= ?";
				$values[] = $input['byBonusPeriodJoinedTo'] . ' 23:59:59';
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

		if (isset($input['byUsername'])) {
			$where[] = "player.username LIKE ?";
			$values[] = '%' . $input['byUsername'] . '%';
		}

        if (!empty($input['tag_list'])) {
            $tagList = is_array($input['tag_list']) ? implode(',', $input["tag_list"]) : $input["tag_list"];
            $where[] = 'playerpromo.playerId NOT IN (SELECT DISTINCT playerId FROM playertag WHERE playertag.tagId IN ('.$tagList.'))';
        }

		$where[] = "player.deleted_at IS NULL";

		//==where condition=================================


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
		    //drop result if export
			return $csv_filename;
		}

		$summary = $this->data_tables->summary($request, $table, $joins, 'SUM(transactions.amount) total_amount', null, $columns, $where, $values);

		$result['summary'][0]['total_amount'] = $this->utils->formatCurrencyNoSym($summary[0]['total_amount']);

		return $result;
	}

	/**
	 * detail: get cashback report
	 *
	 * @param array $request
	 * @param Boolean $is_export
	 *
	 * @return array
	 */
	public function cashbackReport($request, $is_export = false) {

		$readOnlyDB = $this->getReadOnlyDB();

		$this->load->library('data_tables', array("DB" => $readOnlyDB));
		$this->load->model(array('transactions', 'player_model', 'group_level'));
        $this->load->helper(['player_helper']);

		$this->data_tables->is_export = $is_export;

		$player_model = $this->player_model;

		// $this->benchmark->mark('pre_processing_start');

		# START DEFINE COLUMNS #################################################################################################################################################
		// $i = 0;
		$date_column = 0;
		$username_column = 1;
		$affiliate_column = 2;
		$player_tag_column = 3;
		$group_level_column = 4;
		$amount_column = 5;
		$bet_amount_column = 6;
		$origin_bet_amount_column = 7;
		$paid_flag_column = 8;
		$game_platform_column = 9;
		$game_type_column = 10;
		$game_description_column = 11;
		$updated_at_column = 12;
		$paid_date_column = 13;
		$signup_date_column = 14;
		$paid_amount_column = 15;
		$withdraw_condition_amount_column = 16;
		$cashback_type = 17;
		$friend_referral_cashback = 18;

		$columns = array(
			array(
				'alias' => 'player_id',
				'select' => 'total_cashback_player_game_daily.player_id',
			),
			array(
				'alias' => 'id',
				'select' => 'total_cashback_player_game_daily.id',
			),
			array(
				'alias' => 'applied_info',
				'select' => 'total_cashback_player_game_daily.applied_info',
			),
			array(
				'alias' => 'appoint_id',
				'select' => 'total_cashback_player_game_daily.appoint_id',
			),
			array(
				'alias' => 'cashback_percentage',
				'select' => 'total_cashback_player_game_daily.cashback_percentage',
			),
			array(
				'dt' => $date_column,
				'alias' => 'total_date',
				'select' => 'total_cashback_player_game_daily.total_date',
				'formatter' => 'dateFormatter',
				'name' => lang('Date'),
			),
			array(
				'dt' => $username_column,
				'alias' => 'username',
				'select' => 'player.username',
				'formatter' => function ($d, $row) use ($is_export) {
					if ($is_export) {
						return ($d ? $d : lang('N/A'));
					} else {
						$str = '<i class="fa fa-user" ></i> ' . ($d ? '<a href="/player_management/userInformation/' . $row['player_id'] . '" target="_blank">' . $d . '</a>' : '<i class="text-muted">' . lang('N/A') . '</i>');
						if ($row['paid_flag'] != '1') {
							$str = '<input type="checkbox" class="chk_row" value="' . $row['id'] . '" onchange="changeSelectRow()"> ' . $str;
						}
						return $str;
					}
				},
				'name' => lang('Player Username'),
			),
            array(
                'dt' => $affiliate_column,
                'alias' => 'affiliate',
                'select' => 'affiliates.username',
                'formatter' => function ($d, $row) use ($is_export) {
                    if ($is_export) {
                        return ($d ? $d : lang('N/A'));
                    } else {
                        return $d ? $d : '<i class="text-muted">' . lang('N/A') . '</i>';
                    }
                },
                'name' => lang('a_header.affiliate'),
            ),
			//OGP-25040
			array(
                'dt' => $player_tag_column,
                'alias' => 'player_tag',
                'select' =>  'player.playerId',
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
				'dt' => $group_level_column,
				'alias' => 'group_level',
				'select' => 'total_cashback_player_game_daily.vip_level_info',
				'formatter' => function ($d, $row) {
					$sprintf_format = '%s - %s - %s'; // params: groupName, vipLevelName and cashback_percentage.
					$cashback_percentage = $row['cashback_percentage']. '%';
					$groupName = lang('N/A'); // defaults
					$vipLevelName = lang('N/A'); // defaults
					$vip_level_info = $this->utils->json_decode_handleErr($d, true);
					if( ! empty($vip_level_info['vipsetting']['groupName']) ){
						$groupName = lang($vip_level_info['vipsetting']['groupName']);
					}
					if( ! empty($vip_level_info['vipsettingcashbackrule']['vipLevelName']) ){
						$vipLevelName =  lang($vip_level_info['vipsettingcashbackrule']['vipLevelName']);
					}
					return sprintf($sprintf_format, $groupName, $vipLevelName, $cashback_percentage);
				},
				'name' => lang('VIP Level'),
			),
			array(
				'dt' => $amount_column,
				'alias' => 'amount',
				'select' => 'total_cashback_player_game_daily.amount',
				'name' => lang('Amount'),
				'formatter' => function ($d, $row) use ($is_export) {

					$applied_info = json_decode($row['applied_info'], true);
					$isTierMode = false;
					if( ! empty($applied_info['common_cashback_multiple_range_rules']) ){
						foreach( $applied_info['common_cashback_multiple_range_rules'] as $rule_id_key => $multiple_range_rules ) {
							if( ! empty($multiple_range_rules['resultsByTier']) ){
								$isTierMode = true;
								break;
							}
						}
					}
					$val = $d;
					if($isTierMode){
						$html = '';
						$html .= '<div class="btn btn-xs btn-toolbar" data-appoint_id="'. $row['appoint_id']. '"><span class="glyphicon glyphicon-list-alt"></span></div>&nbsp;';
						$html .= '<i class="text-success">'. $this->data_tables->currencyFormatter($val). '</i>';
						$val = $html;
					}else{
						$val = $this->data_tables->currencyFormatter($val);
					}

					if ($is_export) {
						return $d;
					} else {
						return $val;
					}
				},
			),
			array(
				'dt' => $bet_amount_column,
				'alias' => 'bet_amount',
				'select' => 'total_cashback_player_game_daily.bet_amount',
				'name' => lang('Bet Amount'),
				'formatter' => 'currencyFormatter',
			),
			array(
				'dt' => $origin_bet_amount_column,
				'alias' => 'original_bet_amount',
				'select' => 'total_cashback_player_game_daily.original_bet_amount',
				'name' => lang('Original Bet Amount'),
				'formatter' => 'currencyFormatter',
			),
			array(
				'dt' => $paid_flag_column,
				'alias' => 'paid_flag',
				'select' => 'total_cashback_player_game_daily.paid_flag',
				'name' => lang('Paid'),
				'formatter' => function ($d, $row) use ($is_export) {
					return $d == '1' ? lang('Paid') : lang('Not pay');
				},
			),
			array(
				'dt' => $game_platform_column,
				'alias' => 'game_platform',
				'select' => 'external_system.system_code',
				'formatter' => function ($d, $row) use ($is_export) {
					if ($is_export) {
						return $d;
					} else {
						return $d ? $d : '<i class="text-muted">' . lang('N/A') . '</i>';
					}
				},
				'name' => lang('Game Platform'),
			),
			array(
				'dt' => $game_type_column,
				'alias' => 'game_type',
				'select' => 'game_type.game_type_lang',
				'formatter' => function ($d) use ($is_export) {
					if ($is_export) {
						return $d ? lang($d) : lang('N/A');
					} else {
						return $d ? lang($d) : '<i class="text-muted">' . lang('N/A') . '</i>';
					}
				},
				'name' => lang('Game Type'),
			),
			array(
				'dt' => $game_description_column,
				'alias' => 'game_description',
				'select' => 'game_description.game_name',
				'formatter' => function ($d) use ($is_export) {
					if ($is_export) {
						return $d ? lang($d) : lang('N/A');
					} else {
						return $d ? lang($d) : '<i class="text-muted">' . lang('N/A') . '</i>';
					}
				},
				'name' => lang('Game'),
			),
			array(
				'dt' => $updated_at_column,
				'alias' => 'updated_at',
				'select' => 'total_cashback_player_game_daily.updated_at',
				'formatter' => 'dateTimeFormatter',
				'name' => lang('Updated at'),
			),
			array(
				'dt' => $paid_date_column,
				'alias' => 'paid_date',
				'select' => 'total_cashback_player_game_daily.paid_date',
				'formatter' => 'dateTimeFormatter',
				'name' => lang('Paid date'),
			),
			array(
                'dt' => $signup_date_column,
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
            ),
			array(
				'dt' => $paid_amount_column,
				'alias' => 'paid_amount',
				'select' => 'total_cashback_player_game_daily.paid_amount',
				'formatter' => 'currencyFormatter',
				'name' => lang('Paid amount'),
			),
			array(
				'dt' => $withdraw_condition_amount_column,
				'alias' => 'withdraw_condition_amount',
				'select' => 'total_cashback_player_game_daily.withdraw_condition_amount',
				'formatter' => 'currencyFormatter',
				'name' => lang('Withdraw Condition amount'),
			),
		);

		if($this->utils->isEnabledFeature('enable_friend_referral_cashback') || false) {
			$cashback_type = array(
				array(
					'dt' => $cashback_type,
					'alias' => 'cashback_type',
					'select' => 'total_cashback_player_game_daily.cashback_type',
					'formatter' => function($d, $row) {
					    switch((int)$d){
                            case self::FRIEND_REFERRAL_CASHBACK:
                                return lang('Friend Referral Cashback');
                                break;
                            case self::NORMAL_CASHBACK:
                            default:
                                return lang('Normal Cashback');
                                break;
                        }
					},
					'name' => lang('Cashback Type')
				),
				array(
					'dt' => $friend_referral_cashback,
					'alias' => 'invited_player_id',
					'select' => 'total_cashback_player_game_daily.invited_player_id',
					'formatter' => function($d, $row) use ($readOnlyDB, $player_model) {
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
					'name' => lang('Referred Player for Cashback')
				),
			);
			$columns = array_merge($columns, $cashback_type);
		}

		# END DEFINE COLUMNS #################################################################################################################################################

		$table = 'total_cashback_player_game_daily';
		$joins = array(
			'player' => "total_cashback_player_game_daily.player_id = player.playerId",
			'game_description' => "game_description.id = total_cashback_player_game_daily.game_description_id",
			'game_type' => "game_type.id = total_cashback_player_game_daily.game_type_id",
			'external_system' => "external_system.id = total_cashback_player_game_daily.game_platform_id",
			'affiliates' => 'affiliates.affiliateId = player.affiliateId',
		);

		# START PROCESS SEARCH FORM #################################################################################################################################################
		$where = array();
		$values = array();
		$input = $this->data_tables->extra_search($request);
		// $this->utils->debug_log('input', $input);
		if (!empty($input['search_reg_date']) && $input['search_reg_date'] == 'on') {
			if (isset($input['registration_date_from'], $input['registration_date_to'])) {
				$where[] = "player.createdOn >= ?";
				$where[] = "player.createdOn <= ?";
				$values[] = $input['registration_date_from'];
				$values[] = $input['registration_date_to'];
			}
		}

		if (isset($input['by_amount_less_than'])) {
			$where[] = "total_cashback_player_game_daily.amount <= ?";
			$values[] = $input['by_amount_less_than'];
		}

		if (isset($input['by_amount_greater_than'])) {
			$where[] = "total_cashback_player_game_daily.amount >= ?";
			$values[] = $input['by_amount_greater_than'];
		}

		if (isset($input['by_player_level'])) {
			$where[] = "player.levelId = ?";
			$values[] = $input['by_player_level'];
		}

		if (isset($input['by_paid_flag'])) {
			$where[] = "total_cashback_player_game_daily.paid_flag = ?";
			$values[] = $input['by_paid_flag'];
		}

		if (isset($input['by_cashback_type'])) {
			$where[] = "total_cashback_player_game_daily.cashback_type = ?";
			$values[] = $input['by_cashback_type'];
		}

		// only default to normal if not enable referral cashback
		if(!$this->utils->isEnabledFeature('enable_friend_referral_cashback') && false) {
			$where[] = "total_cashback_player_game_daily.cashback_type = ?";
			$values[] = self::NORMAL_CASHBACK;
		}

		if ($this->safeGetParam($input, 'enable_date') == 'true') {

			if (isset($input['by_date_from'])) {
				$where[] = "total_cashback_player_game_daily.total_date >= ?";
				$values[] = $input['by_date_from'];
			}

			if (isset($input['by_date_to'])) {
				$where[] = "total_cashback_player_game_daily.total_date <= ?";
				$values[] = $input['by_date_to'];
			}
		}

		if (isset($input['by_username'])) {
			$where[] = "player.username LIKE ?";
			$values[] = '%' . $input['by_username'] . '%';
		}

		if (isset($input['affiliate_username'])) {
			$where[] = "affiliates.username LIKE ?";
			$values[] = '%' . $input['affiliate_username'] . '%';
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

		$where[] = "player.deleted_at IS NULL";

		if($is_export){
            $this->data_tables->options['is_export']=true;
            if(empty($csv_filename)){
                $csv_filename=$this->utils->create_csv_filename(__FUNCTION__);
            }
            $this->data_tables->options['csv_filename']=$csv_filename;
		}
		$result = $this->data_tables->get_data($request, $columns, $table, $where, $values, $joins);
		// $this->benchmark->mark('data_sql_end');
		$this->utils->debug_log($result);
		if($is_export){
		    //drop result if export
			return $csv_filename;
		}

		if($this->utils->getConfig('enabled_display_cashback_report_total_amount')){
			if (isset($input['by_paid_flag']) && $input['by_paid_flag'] == 1) {
				$group_by = 'total_cashback_player_game_daily.player_id, total_cashback_player_game_daily.total_date';
				$summary = $this->data_tables->summary($request, $table, $joins, 'total_cashback_player_game_daily.paid_amount paid_amount', $group_by, $columns, $where, $values);
				$total_paid_amount = 0;
				foreach ($summary as $s) {
					$total_paid_amount += $s['paid_amount'];
				}
				$result['summary'][0]['total_amount'] = $this->utils->formatCurrencyNoSym($total_paid_amount);
			}else {
				$summary = $this->data_tables->summary($request, $table, $joins, 'SUM(total_cashback_player_game_daily.amount) total_amount', null, $columns, $where, $values);
				$result['summary'][0]['total_amount'] = $this->utils->formatCurrencyNoSym($summary[0]['total_amount']);
			}
		}

		return $result;

	}

    public function getRecalculateCashbackReport($request, $is_export = false, $tempCashbackTable = null){
	    if(empty($tempCashbackTable)){
	        // use default value when export csv
            $tempCashbackTable = "recalculate_cashback_temp_".date("Ymd");
        }

        $readOnlyDB = $this->getReadOnlyDB();

        $this->load->library('data_tables', array("DB" => $readOnlyDB));
        $this->load->model(array('transactions', 'player_model', 'group_level'));

        $this->data_tables->is_export = $is_export;

        $player_model = $this->player_model;

        // $this->benchmark->mark('pre_processing_start');

        # START DEFINE COLUMNS #################################################################################################################################################
        // $i = 0;
        $date_column = 0;
        $username_column = 1;
        $affiliate_column = 2;
        $group_level_column = 3;
        $amount_column = 4;
        $bet_amount_column = 5;
        $origin_bet_amount_column = 6;
        $paid_flag_column = 7;
        $game_platform_column = 8;
        $game_type_column = 9;
        $game_description_column = 10;
        $updated_at_column = 11;
        $paid_date_column = 12;
        $paid_amount_column = 13;
        $withdraw_condition_amount_column = 14;
        $cashback_type = 15;
        $friend_referral_cashback = 16;
        $columns = array(
            array(
                'alias' => 'player_id',
                'select' => $tempCashbackTable.'.player_id',
            ),
            array(
                'alias' => 'id',
                'select' => $tempCashbackTable.'.id',
            ),
            array(
                'alias' => 'applied_info',
                'select' => $tempCashbackTable.'.applied_info',
            ),
            array(
                'alias' => 'appoint_id',
                'select' => $tempCashbackTable.'.appoint_id',
            ),
            array(
                'alias' => 'cashback_percentage',
                'select' => $tempCashbackTable.'.cashback_percentage',
            ),
            array(
                'dt' => $date_column,
                'alias' => 'total_date',
                'select' => $tempCashbackTable.'.total_date',
                'formatter' => 'dateFormatter',
                'name' => lang('Date'),
            ),
            array(
                'dt' => $username_column,
                'alias' => 'username',
                'select' => 'player.username',
                'formatter' => function ($d, $row) use ($is_export) {
                    if ($is_export) {
                        return ($d ? $d : lang('N/A'));
                    } else {
                        $str = '<i class="fa fa-user" ></i> ' . ($d ? '<a href="/player_management/userInformation/' . $row['player_id'] . '" target="_blank">' . $d . '</a>' : '<i class="text-muted">' . lang('N/A') . '</i>');
//                        if ($row['paid_flag'] != '1') {
//                            $str = '<input type="checkbox" class="chk_row" value="' . $row['id'] . '" onchange="changeSelectRow()"> ' . $str;
//                        }
                        return $str;
                    }
                },
                'name' => lang('Player Username'),
            ),
            array(
                'dt' => $affiliate_column,
                'alias' => 'affiliate',
                'select' => 'affiliates.username',
                'formatter' => function ($d, $row) use ($is_export) {
                    if ($is_export) {
                        return ($d ? $d : lang('N/A'));
                    } else {
                        return $d ? $d : '<i class="text-muted">' . lang('N/A') . '</i>';
                    }
                },
                'name' => lang('a_header.affiliate'),
            ),
            array(
                'dt' => $group_level_column,
                'alias' => 'group_level',
                'select' => $tempCashbackTable.'.vip_level_info',
                'formatter' => function ($d, $row) {
                    $sprintf_format = '%s - %s - %s'; // params: groupName, vipLevelName and cashback_percentage.
                    $cashback_percentage = $row['cashback_percentage']. '%';
                    $groupName = lang('N/A'); // defaults
                    $vipLevelName = lang('N/A'); // defaults
                    $vip_level_info = $this->utils->json_decode_handleErr($d, true);
                    if( ! empty($vip_level_info['vipsetting']['groupName']) ){
                        $groupName = lang($vip_level_info['vipsetting']['groupName']);
                    }
                    if( ! empty($vip_level_info['vipsettingcashbackrule']['vipLevelName']) ){
                        $vipLevelName =  lang($vip_level_info['vipsettingcashbackrule']['vipLevelName']);
                    }
                    return sprintf($sprintf_format, $groupName, $vipLevelName, $cashback_percentage);
                },
                'name' => lang('VIP Level'),
            ),
            array(
                'dt' => $amount_column,
                'alias' => 'amount',
                'select' => $tempCashbackTable.'.amount',
                'name' => lang('Amount'),
                'formatter' => function ($d, $row) use ($is_export) {

                    $applied_info = json_decode($row['applied_info'], true);
                    $isTierMode = false;
                    if( ! empty($applied_info['common_cashback_multiple_range_rules']) ){
                        foreach( $applied_info['common_cashback_multiple_range_rules'] as $rule_id_key => $multiple_range_rules ) {
                            if( ! empty($multiple_range_rules['resultsByTier']) ){
                                $isTierMode = true;
                                break;
                            }
                        }
                    }
                    $val = $d;
                    if($isTierMode){
                        $html = '';
                        $html .= '<div class="btn btn-xs btn-toolbar" data-appoint_id="'. $row['appoint_id']. '"><span class="glyphicon glyphicon-list-alt"></span></div>&nbsp;';
                        $html .= '<i class="text-success">'. $this->data_tables->currencyFormatter($val). '</i>';
                        $val = $html;
                    }else{
                        $val = $this->data_tables->currencyFormatter($val);
                    }

                    if ($is_export) {
                        return $d;
                    } else {
                        return $val;
                    }
                },
            ),
            array(
                'dt' => $bet_amount_column,
                'alias' => 'bet_amount',
                'select' => $tempCashbackTable.'.bet_amount',
                'name' => lang('Bet Amount'),
                'formatter' => 'currencyFormatter',
            ),
            array(
                'dt' => $origin_bet_amount_column,
                'alias' => 'original_bet_amount',
                'select' => $tempCashbackTable.'.original_bet_amount',
                'name' => lang('Original Bet Amount'),
                'formatter' => 'currencyFormatter',
            ),
            array(
                'dt' => $paid_flag_column,
                'alias' => 'paid_flag',
                'select' => $tempCashbackTable.'.paid_flag',
                'name' => lang('Paid'),
                'formatter' => function ($d, $row) use ($is_export) {
                    return $d == '1' ? lang('Paid') : lang('Not pay');
                },
            ),
            array(
                'dt' => $game_platform_column,
                'alias' => 'game_platform',
                'select' => 'external_system.system_code',
                'formatter' => function ($d, $row) use ($is_export) {
                    if ($is_export) {
                        return $d;
                    } else {
                        return $d ? $d : '<i class="text-muted">' . lang('N/A') . '</i>';
                    }
                },
                'name' => lang('Game Platform'),
            ),
            array(
                'dt' => $game_type_column,
                'alias' => 'game_type',
                'select' => 'game_type.game_type_lang',
                'formatter' => function ($d) use ($is_export) {
                    if ($is_export) {
                        return $d ? lang($d) : lang('N/A');
                    } else {
                        return $d ? lang($d) : '<i class="text-muted">' . lang('N/A') . '</i>';
                    }
                },
                'name' => lang('Game Type'),
            ),
            array(
                'dt' => $game_description_column,
                'alias' => 'game_description',
                'select' => 'game_description.game_name',
                'formatter' => function ($d) use ($is_export) {
                    if ($is_export) {
                        return $d ? lang($d) : lang('N/A');
                    } else {
                        return $d ? lang($d) : '<i class="text-muted">' . lang('N/A') . '</i>';
                    }
                },
                'name' => lang('Game'),
            ),
            array(
                'dt' => $updated_at_column,
                'alias' => 'updated_at',
                'select' => $tempCashbackTable.'.updated_at',
                'formatter' => 'dateTimeFormatter',
                'name' => lang('Updated at'),
            ),
            array(
                'dt' => $paid_date_column,
                'alias' => 'paid_date',
                'select' => $tempCashbackTable.'.paid_date',
                'formatter' => 'dateTimeFormatter',
                'name' => lang('Paid date'),
            ),
            array(
                'dt' => $paid_amount_column,
                'alias' => 'paid_amount',
                'select' => $tempCashbackTable.'.paid_amount',
                'formatter' => 'currencyFormatter',
                'name' => lang('Paid amount'),
            ),
            array(
                'dt' => $withdraw_condition_amount_column,
                'alias' => 'withdraw_condition_amount',
                'select' => $tempCashbackTable.'.withdraw_condition_amount',
                'formatter' => 'currencyFormatter',
                'name' => lang('Withdraw Condition amount'),
            )
        );

        if($this->utils->isEnabledFeature('enable_friend_referral_cashback') || false) {
            $cashback_type = array(
                array(
                    'dt' => $cashback_type,
                    'alias' => 'cashback_type',
                    'select' => $tempCashbackTable.'.cashback_type',
                    'formatter' => function($d, $row) {
                        switch((int)$d){
                            case self::FRIEND_REFERRAL_CASHBACK:
                                return lang('Friend Referral Cashback');
                                break;
                            case self::NORMAL_CASHBACK:
                            default:
                                return lang('Normal Cashback');
                                break;
                        }
                    },
                    'name' => lang('Cashback Type')
                ),
                array(
                    'dt' => $friend_referral_cashback,
                    'alias' => 'invited_player_id',
                    'select' => $tempCashbackTable.'.invited_player_id',
                    'formatter' => function($d, $row) use ($readOnlyDB, $player_model) {
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
                    'name' => lang('Referred Player for Cashback')
                ),
            );
            $columns = array_merge($columns, $cashback_type);
        }

        # END DEFINE COLUMNS #################################################################################################################################################
        $input = $this->data_tables->extra_search($request);
        if($this->safeGetParam($input, 'enable_date') == 'true'){
            if (isset($input['by_date_from']) && isset($input['by_date_to'])) {
                $fromDate = $input['by_date_from'];
                $toDate = $input['by_date_to'];
            }

        }

        $table = $tempCashbackTable;
        $joins = array(
            'player' => $tempCashbackTable.".player_id = player.playerId",
            'game_description' => "game_description.id = ".$tempCashbackTable.".game_description_id",
            'game_type' => "game_type.id = ".$tempCashbackTable.".game_type_id",
            'external_system' => "external_system.id = ".$tempCashbackTable.".game_platform_id",
            'affiliates' => 'affiliates.affiliateId = player.affiliateId',
        );

        # START PROCESS SEARCH FORM #################################################################################################################################################
        $where = array();
        $values = array();

        // $this->utils->debug_log('input', $input);

        if (isset($input['by_amount_less_than'])) {
            $where[] = $tempCashbackTable.".amount <= ?";
            $values[] = $input['by_amount_less_than'];
        }

        if (isset($input['by_amount_greater_than'])) {
            $where[] = $tempCashbackTable.".amount >= ?";
            $values[] = $input['by_amount_greater_than'];
        }

        if (isset($input['by_player_level'])) {
            $where[] = "player.levelId = ?";
            $values[] = $input['by_player_level'];
        }

        if (isset($input['by_paid_flag'])) {
            $where[] = $tempCashbackTable.".paid_flag = ?";
            $values[] = $input['by_paid_flag'];
        }

        if (isset($input['by_cashback_type'])) {
            $where[] = $tempCashbackTable.".cashback_type = ?";
            $values[] = $input['by_cashback_type'];
        }

        // only default to normal if not enable referral cashback
        if(!$this->utils->isEnabledFeature('enable_friend_referral_cashback') && false) {
            $where[] = $tempCashbackTable.".cashback_type = ?";
            $values[] = self::NORMAL_CASHBACK;
        }

        if ($this->safeGetParam($input, 'enable_date') == 'true') {

            if (isset($input['by_date_from'])) {
                $where[] = $tempCashbackTable.".total_date >= ?";
                $values[] = $input['by_date_from'];
            }

            if (isset($input['by_date_to'])) {
                $where[] = $tempCashbackTable.".total_date <= ?";
                $values[] = $input['by_date_to'];
            }
        }

        if (isset($input['by_username'])) {
            $where[] = "player.username LIKE ?";
            $values[] = '%' . $input['by_username'] . '%';
        }

        $where[] = "player.deleted_at IS NULL";

        if($is_export){
            $this->data_tables->options['is_export']=true;
            if(empty($csv_filename)){
                $csv_filename=$this->utils->create_csv_filename(__FUNCTION__);
            }
            $this->data_tables->options['csv_filename']=$csv_filename;
        }
        $result = $this->data_tables->get_data($request, $columns, $table, $where, $values, $joins);
        // $this->benchmark->mark('data_sql_end');
        // $this->utils->debug_log($result);
        if($is_export){
            //drop result if export
            return $csv_filename;
        }

        if (isset($input['by_paid_flag']) && $input['by_paid_flag'] == 1) {
            $group_by = $tempCashbackTable.'.player_id, '.$tempCashbackTable.'.total_date';
            $summary = $this->data_tables->summary($request, $table, $joins, $tempCashbackTable.'.paid_amount paid_amount', $group_by, $columns, $where, $values);
            $total_paid_amount = 0;
            foreach ($summary as $s) {
                $total_paid_amount += $s['paid_amount'];
            }
            $result['summary'][0]['total_amount'] = $this->utils->formatCurrencyNoSym($total_paid_amount);
        }else {

            $summary = $this->data_tables->summary($request, $table, $joins, 'SUM('.$tempCashbackTable.'.amount) total_amount', null, $columns, $where, $values);
            $result['summary'][0]['total_amount'] = $this->utils->formatCurrencyNoSym($summary[0]['total_amount']);
        }

        return $result;
	}

    public function getRecalculteWcDeductionProcessReport($request, $is_export = false, $temp_WCDP_Table = null){
        if(empty($temp_WCDP_Table)){
            // use default value when export csv
            $temp_WCDP_Table = "withdraw_condition_deducted_process_temp_".date("Ymd");
        }

        $readOnlyDB = $this->getReadOnlyDB();

        $this->load->library('data_tables', array("DB" => $readOnlyDB));
        $this->load->model(array('transactions', 'player_model', 'group_level'));

        $this->data_tables->is_export = $is_export;

        # START DEFINE COLUMNS #################################################################################################################################################
        $i = 0;

        $columns = array(
            array(
                'alias' => 'player_id',
                'select' => $temp_WCDP_Table.'.player_id',
            ),
            array(
                'alias' => 'id',
                'select' => $temp_WCDP_Table.'.id',
            ),
            array(
                'dt' => $i++,
                'alias' => 'total_date',
                'select' => $temp_WCDP_Table.'.cashback_total_date',
                'name' => lang('wc_dudection_process.cashback_date'),
                'formatter' => 'dateFormatter',
            ),
            array(
                'dt' => $i++,
                'alias' => 'username',
                'select' => 'player.username',
                'name' => lang('wc_dudection_process.username'),
                'formatter' => function ($d, $row) use ($is_export) {
                    if ($is_export) {
                        return ($d ? $d : lang('N/A'));
                    } else {
                        $str = '<i class="fa fa-user" ></i> ' . ($d ? '<a href="/player_management/userInformation/' . $row['player_id'] . '" target="_blank">' . $d . '</a>' : '<i class="text-muted">' . lang('N/A') . '</i>');
                        return $str;
                    }
                },
            ),
            array(
                'dt' => $i++,
                'alias' => 'wc_id',
                'select' => $temp_WCDP_Table.'.withdraw_condition_id',
                'name' => lang('wc_dudection_process.wc_id'),
                'formatter' => function ($d) use ($is_export) {
                    return $d;
                },
            ),
            array(
                'dt' => $i++,
                'alias' => 'promoName',
                'select' => 'promocmssetting.promoName',
                'name' => lang('cms.promoname'),
                'formatter' => function ($d) use ($is_export) {
                    if($d == Promorules::SYSTEM_MANUAL_PROMO_CMS_NAME){
                        $promoName = lang('promo.'. $d);
                    }else{
                        $promoName = $d;
                    }
                    return !empty($promoName) ? $promoName : '<i class="text-muted">' . lang('N/A') . '</i>';
                },
            ),
            array(
                'dt' => $i++,
                'alias' => 'started_at',
                'select' => 'withdraw_conditions'.'.started_at',
                'name' => lang('pay.startedAt'),
                'formatter' => 'dateTimeFormatter',
            ),
            array(
                'dt' => $i++,
                'alias' => 'condition_amount',
                'select' => 'withdraw_conditions'.'.condition_amount',
                'name' => lang('pay.withdrawalAmountCondition'),
                'formatter' => 'currencyFormatter',
            ),
            array(
                'dt' => $i++,
                'alias' => 'before_amount',
                'select' => $temp_WCDP_Table.'.before_amount',
                'name' => lang('wc_dudection_process.before_deduct_amount'),
                'formatter' => 'currencyFormatter',
            ),
            array(
                'dt' => $i++,
                'alias' => 'after_amount',
                'select' => $temp_WCDP_Table.'.after_amount',
                'name' => lang('wc_dudection_process.after_deduct_amount'),
                'formatter' => 'currencyFormatter',
            ),
            array(
                'dt' => $i++,
                'alias' => 'total_deducted_amount',
                'select' => "{$temp_WCDP_Table}.before_amount - {$temp_WCDP_Table}.after_amount",
                'name' => lang('wc_dudection_process.deduct_amount'),
                'formatter' => 'currencyFormatter',
            ),
            array(
                'dt' => $i++,
                'alias' => 'game_platform',
                'select' => 'external_system.system_code',
                'name' => lang('Game Platform'),
                'formatter' => function ($d) use ($is_export) {
                    if ($is_export) {
                        return $d;
                    } else {
                        return $d ? $d : '<i class="text-muted">' . lang('N/A') . '</i>';
                    }
                },
            ),
            array(
                'dt' => $i++,
                'alias' => 'game_type',
                'select' => 'game_type.game_type_lang',
                'name' => lang('Game Type'),
                'formatter' => function ($d) use ($is_export) {
                    if ($is_export) {
                        return $d ? lang($d) : lang('N/A');
                    } else {
                        return $d ? lang($d) : '<i class="text-muted">' . lang('N/A') . '</i>';
                    }
                },
            ),
            array(
                'dt' => $i++,
                'alias' => 'game_description',
                'select' => 'game_description.game_name',
                'name' => lang('wc_dudection_process.game'),
                'formatter' => function ($d) use ($is_export) {
                    if ($is_export) {
                        return $d ? lang($d) : lang('N/A');
                    } else {
                        return $d ? lang($d) : '<i class="text-muted">' . lang('N/A') . '</i>';
                    }
                },
            )
        );

        # END DEFINE COLUMNS #################################################################################################################################################
        $input = $this->data_tables->extra_search($request);

        $table = $temp_WCDP_Table;
        $joins = array(
            'player' => $temp_WCDP_Table.".player_id = player.playerId",
            'game_description' => "game_description.id = ".$temp_WCDP_Table.".game_description_id",
            'game_type' => "game_type.id = ".$temp_WCDP_Table.".game_type_id",
            'external_system' => "external_system.id = ".$temp_WCDP_Table.".game_platform_id",
            'withdraw_conditions' => $temp_WCDP_Table.".withdraw_condition_id = withdraw_conditions.id and withdraw_condition_type = 1",
            'playerpromo' => 'withdraw_conditions.player_promo_id = playerpromo.playerpromoId',
            'promocmssetting' => 'promocmssetting.promoCmsSettingId = playerpromo.promoCmsSettingId',
        );

        # START PROCESS SEARCH FORM #################################################################################################################################################
        $where = array();
        $values = array();

        if ($this->safeGetParam($input, 'enable_date') == 'true') {

            if (isset($input['by_date_from'])) {
                $where[] = $temp_WCDP_Table.".cashback_total_date >= ?";
                $values[] = $input['by_date_from'];
            }

            if (isset($input['by_date_to'])) {
                $where[] = $temp_WCDP_Table.".cashback_total_date <= ?";
                $values[] = $input['by_date_to'];
            }
        }

        if (isset($input['by_username'])) {
            $where[] = "player.username LIKE ?";
            $values[] = '%' . $input['by_username'] . '%';
        }

        $where[] = "player.deleted_at IS NULL";

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

        return $result;
    }

    /**
     * detail: get recalculate cashback report
     *
     * @param array $request
     * @param Boolean $is_export
     *
     * @return array
     */
    public function recalculateCashbackReport($request, $is_export = false) {
        $result = [];
        $tempRecalculateCashbackReportTable = "recalculate_cashback_temp_".date("Ymd");

        if($this->utils->table_really_exists($tempRecalculateCashbackReportTable)){
            $result = $this->getRecalculateCashbackReport($request, $is_export, $tempRecalculateCashbackReportTable);
        }

        return $result;
    }

    /**
     * detail: get withdraw condition deduction process report
     *
     * @param array $request
     * @param Boolean $is_export
     *
     * @return array
     */
    public function getWcDeductionProcessReport($request, $is_export = false) {
        $readOnlyDB = $this->getReadOnlyDB();

        $this->load->library('data_tables', array("DB" => $readOnlyDB));
        $this->load->model(array('transactions', 'player_model', 'group_level'));

        $this->data_tables->is_export = $is_export;

        # START DEFINE COLUMNS #################################################################################################################################################
        $i = 0;

        $columns = array(
            array(
                'alias' => 'player_id',
                'select' => 'withdraw_condition_deducted_process.player_id',
            ),
            array(
                'alias' => 'id',
                'select' => 'withdraw_condition_deducted_process.id',
            ),
            array(
                'dt' => $i++,
                'alias' => 'total_date',
                'select' => 'withdraw_condition_deducted_process.cashback_total_date',
                'name' => lang('wc_dudection_process.cashback_date'),
                'formatter' => 'dateFormatter',
            ),
            array(
                'dt' => $i++,
                'alias' => 'username',
                'select' => 'player.username',
                'name' => lang('wc_dudection_process.username'),
                'formatter' => function ($d, $row) use ($is_export) {
                    if ($is_export) {
                        return ($d ? $d : lang('N/A'));
                    } else {
                        $str = '<i class="fa fa-user" ></i> ' . ($d ? '<a href="/player_management/userInformation/' . $row['player_id'] . '" target="_blank">' . $d . '</a>' : '<i class="text-muted">' . lang('N/A') . '</i>');
                        return $str;
                    }
                },
            ),
            array(
                'dt' => $i++,
                'alias' => 'wc_id',
                'select' => 'withdraw_condition_deducted_process.withdraw_condition_id',
                'name' => lang('wc_dudection_process.wc_id'),
                'formatter' => function ($d) use ($is_export) {
                    return $d;
                },
            ),
            array(
                'dt' => $i++,
                'alias' => 'promoName',
                'select' => 'promocmssetting.promoName',
                'name' => lang('cms.promoname'),
                'formatter' => function ($d) use ($is_export) {
                    if($d == Promorules::SYSTEM_MANUAL_PROMO_CMS_NAME){
                        $promoName = lang('promo.'. $d);
                    }else{
                        $promoName = $d;
                    }
                    return !empty($promoName) ? $promoName : '<i class="text-muted">' . lang('N/A') . '</i>';
                },
            ),
            array(
                'dt' => $i++,
                'alias' => 'started_at',
                'select' => 'withdraw_conditions'.'.started_at',
                'name' => lang('pay.startedAt'),
                'formatter' => 'dateTimeFormatter',
            ),
            array(
                'dt' => $i++,
                'alias' => 'condition_amount',
                'select' => 'withdraw_conditions'.'.condition_amount',
                'name' => lang('pay.withdrawalAmountCondition'),
                'formatter' => 'currencyFormatter',
            ),
            array(
                'dt' => $i++,
                'alias' => 'before_amount',
                'select' => 'withdraw_condition_deducted_process.before_amount',
                'name' => lang('wc_dudection_process.before_deduct_amount'),
                'formatter' => 'currencyFormatter',
            ),
            array(
                'dt' => $i++,
                'alias' => 'after_amount',
                'select' => 'withdraw_condition_deducted_process.after_amount',
                'name' => lang('wc_dudection_process.after_deduct_amount'),
                'formatter' => 'currencyFormatter',
            ),
            array(
                'dt' => $i++,
                'alias' => 'total_deducted_amount',
                'select' => "withdraw_condition_deducted_process.before_amount - withdraw_condition_deducted_process.after_amount",
                'name' => lang('wc_dudection_process.deduct_amount'),
                'formatter' => 'currencyFormatter',
            ),
            array(
                'dt' => $i++,
                'alias' => 'game_platform',
                'select' => 'external_system.system_code',
                'name' => lang('Game Platform'),
                'formatter' => function ($d) use ($is_export) {
                    if ($is_export) {
                        return $d;
                    } else {
                        return $d ? $d : '<i class="text-muted">' . lang('N/A') . '</i>';
                    }
                },
            ),
            array(
                'dt' => $i++,
                'alias' => 'game_type',
                'select' => 'game_type.game_type_lang',
                'name' => lang('Game Type'),
                'formatter' => function ($d) use ($is_export) {
                    if ($is_export) {
                        return $d ? lang($d) : lang('N/A');
                    } else {
                        return $d ? lang($d) : '<i class="text-muted">' . lang('N/A') . '</i>';
                    }
                },
            ),
            array(
                'dt' => $i++,
                'alias' => 'game_description',
                'select' => 'game_description.game_name',
                'name' => lang('wc_dudection_process.game'),
                'formatter' => function ($d) use ($is_export) {
                    if ($is_export) {
                        return $d ? lang($d) : lang('N/A');
                    } else {
                        return $d ? lang($d) : '<i class="text-muted">' . lang('N/A') . '</i>';
                    }
                },
            )
        );

        # END DEFINE COLUMNS #################################################################################################################################################
        $input = $this->data_tables->extra_search($request);

        $table = 'withdraw_condition_deducted_process';
        $joins = array(
            'player' => "withdraw_condition_deducted_process.player_id = player.playerId",
            'game_description' => "game_description.id = withdraw_condition_deducted_process.game_description_id",
            'game_type' => "game_type.id = withdraw_condition_deducted_process.game_type_id",
            'external_system' => "external_system.id = withdraw_condition_deducted_process.game_platform_id",
            'withdraw_conditions' => "withdraw_condition_deducted_process.withdraw_condition_id = withdraw_conditions.id and withdraw_condition_type = 1",
            'playerpromo' => 'withdraw_conditions.player_promo_id = playerpromo.playerpromoId',
            'promocmssetting' => 'promocmssetting.promoCmsSettingId = playerpromo.promoCmsSettingId',
        );

        # START PROCESS SEARCH FORM #################################################################################################################################################
        $where = array();
        $values = array();

        if ($this->safeGetParam($input, 'enable_date') == 'true') {

            if (isset($input['by_date_from'])) {
                $where[] = "withdraw_condition_deducted_process.cashback_total_date >= ?";
                $values[] = $input['by_date_from'];
            }

            if (isset($input['by_date_to'])) {
                $where[] = "withdraw_condition_deducted_process.cashback_total_date <= ?";
                $values[] = $input['by_date_to'];
            }
        }

        if (isset($input['by_username'])) {
            $where[] = "player.username LIKE ?";
            $values[] = '%' . $input['by_username'] . '%';
        }

        $where[] = "player.deleted_at IS NULL";

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

        return $result;
    }

    /**
     * detail: get recalculate withdraw condition deduction process report
     *
     * @param array $request
     * @param Boolean $is_export
     *
     * @return array
     */
    public function recalculateWcDeductionProcessReport($request, $is_export = false) {
        $result = [];
        $tempRecalculateWcDeductionProcessReportTable = "withdraw_condition_deducted_process_temp_".date("Ymd");

        if($this->utils->table_really_exists($tempRecalculateWcDeductionProcessReportTable)){
            $result = $this->getRecalculteWcDeductionProcessReport($request, $is_export, $tempRecalculateWcDeductionProcessReportTable);
        }

        return $result;
    }

	public function agency_get_logs($request, $is_export) {

		$this->load->model(['agency_model']);
		$result = $this->agency_model->get_logs($request, $is_export);

		return $result;
	}

	public function agency_get_transactions($request, $is_export) {

		$this->load->model(['agency_model']);
		$result = $this->agency_model->get_transactions($request, $is_export);

		return $result;
	}

	public function get_agent_list($request, $is_export) {

		$this->load->model(['agency_model']);
		$result = $this->agency_model->get_agent_list($request, $is_export);

		return $result;
	}

	public function get_structure_list($request, $is_export) {

		$this->load->model(['agency_model']);
		$result = $this->agency_model->get_structure_list($request, $is_export);

		return $result;
	}

	public function currencyFormatter($d, $is_export = null) {
		if ($is_export) {
			return $this->utils->formatCurrencyNoSym($d);
		} else {
			return $d == 0 ? '<span class="text-muted">' . $this->utils->formatCurrencyNoSym($d) . '</span>' : '<strong>' . $this->utils->formatCurrencyNoSym($d) . '</strong>';
		}
	}

	//===agency========================================================

	/**
	 *  return player rolling comm info
	 *
	 *  @param
	 *  @return
	 */
	public function player_rolling_comm($request, $logged_agent_id, $is_export = false) {
		$this->load->library(array('data_tables'));
		$this->load->model(['agency_model']);

		$input = $this->data_tables->extra_search($request);
		// $this->utils->debug_log('get_settlement request', $request);
		$table = 'agency_player_rolling_comm';
		$where = array();
		$values = array();
		$joins = array();
		$joins['player'] = 'player.playerId = agency_player_rolling_comm.player_id';
		// $joins['agency_settlement'] = 'agency_settlement.settlement_id = agency_player_rolling_comm.settlement_id';

		// $settlement_id = $input['settlement_id'];
		// $where[] = "agency_settlement.settlement_id = ?";
		// $values[] = $settlement_id;

		if (isset($input['status']) && !empty($input['status'])) {
			$where[] = "agency_player_rolling_comm.payment_status = ?";
			$values[] = $input['status'];
		}

		if (isset($input['search_on_date']) && $input['search_on_date'] == 'true') {
			$where[] = "agency_player_rolling_comm.start_at >= ? and agency_player_rolling_comm.start_at <= ?";
			$values[] = $input['date_from'];
			$values[] = $input['date_to'];
		}

		if (isset($input['player_username']) && !empty($input['player_username'])) {
			$where[] = "player.username like ?";
			$values[] = '%' . $input['player_username'] . '%';
		}

		$search_agent_id = $logged_agent_id;

		$search_agent_id_arr = [$search_agent_id];

		if (isset($input['sub_agent_username']) && !empty($input['sub_agent_username'])) {
			$subagent = $this->agency_model->get_agent_by_name($input['sub_agent_username']);
			if (!empty($subagent)) {
				$search_agent_id = $subagent['agent_id'];
				$search_agent_id_arr = [$search_agent_id];
			}
		}

		if (isset($input['include_all_downlines']) && $input['include_all_downlines'] == 'true') {
			//search sub agent ids by search_agent_id
			$search_agent_id_arr = $this->agency_model->get_all_downline_arr($search_agent_id);
			$search_agent_id_arr[] = $search_agent_id;
			$search_agent_id_arr = array_unique($search_agent_id_arr);
		}

		$where[] = "agency_player_rolling_comm.agent_id in (" . implode(',', $search_agent_id_arr) . ")";
		// $values[] = '';

		$i = 0;
		$columns = array(
			array(
				'alias' => 'player_id',
				'select' => 'player.playerId',
			),
			array(
				'alias' => 'agency_player_rolling_comm_id',
				'select' => 'agency_player_rolling_comm.id',
			),
			array(
				'alias' => 'agent_id',
				'select' => 'agency_player_rolling_comm.agent_id',
			),
			array(
				'dt' => $i++,
				'alias' => 'username',
				'select' => 'player.username',
				'formatter' => function ($d, $row) use ($is_export) {
					if ($is_export) {
						return $d;
					} else {
						$player_id = $row['player_id'];
						$ret = "<i class='fa fa-user' ></i> ";
						$title = lang('Show Player Info');
						$ret .= "<a href='javascript:void(0)' class='agent-oper' data-toggle='tooltip' title='$title' onclick='show_player_info($player_id)'>$d</a> ";
						return $ret;
					}
				},
			),
			array(
				'dt' => $i++,
				'alias' => 'payment_status',
				'select' => 'agency_player_rolling_comm.payment_status',
				'name' => lang('Status'),
			),
			array(
				'dt' => $i++,
				'alias' => 'start_at',
				'select' => 'agency_player_rolling_comm.start_at',
				'name' => lang('Date From'),
			),
			array(
				'dt' => $i++,
				'alias' => 'end_at',
				'select' => 'agency_player_rolling_comm.end_at',
				'name' => lang('Date To'),
			),
			array(
				'dt' => $i++,
				'alias' => 'real_bets',
				'select' => 'agency_player_rolling_comm.real_bets',
				'name' => lang('Real Bet'),
			),
			array(
				'dt' => $i++,
				'alias' => 'total_bets',
				'select' => 'agency_player_rolling_comm.total_bets',
				'name' => lang('Total Bet'),
			),
			array(
				'dt' => $i++,
				'alias' => 'rolling_rate',
				'select' => 'agency_player_rolling_comm.rolling_rate',
				'name' => lang('Rolling Rate'),
			),
			array(
				'dt' => $i++,
				'alias' => 'rolling_comm_amt',
				'select' => 'agency_player_rolling_comm.rolling_comm_amt',
				'name' => lang('Rolling Comm Amt'),
			),
			array(
				'dt' => $i++,
				'alias' => 'notes',
				'select' => 'agency_player_rolling_comm.notes',
				'name' => lang('Notes'),
			),
			array(
				'dt' => $i++,
				'alias' => 'action',
				'select' => '""',
				'name' => lang('Action'),
				'formatter' => function ($d, $row) use ($is_export, $logged_agent_id) {
					if ($is_export || $logged_agent_id != $row['agent_id']) {
						return '';
					} else {

						//by status
						$status = $row['payment_status'];
						$id = $row['agency_player_rolling_comm_id'];
						$amount = $row['rolling_comm_amt'];
						$username = $row['username'];
						$show_settle = false;
						$show_pending = false;
						if ($status == 'pending') {
							$show_settle = true;
						} elseif ($status == 'settled') {
							//none
						} elseif ($status == 'current') {
							$show_settle = true;
							$show_pending = true;
						}
						$str = '';
						if ($show_settle) {
							$str .= ' <input type="button" class="settle btn btn-sm btn-primary" '
							. 'onclick="settle_rolling(' . $id . ', \'' . $amount . '\', \'' . $username . '\')" value="' . lang('Settle') . '">';
						}
						if ($show_pending) {
							$str .= ' <input type="button" class="pending btn btn-sm btn-success" '
							. 'onclick="pending_rolling(' . $id . ', \'' . $amount . '\', \'' . $username . '\')" value="' . lang('Pending') . '">';
						}

						return $str;

					}
				},
			),
		);
		//order by date from
		$external_order = [['column' => 2, 'dir' => 'desc']];
		//$request, $columns, $table, $form_search = array(), $values = array(), $joins = array(), $group_by = array(), $having = array(), $distinct = true, $external_order
		$result = $this->data_tables->get_data($request, $columns, $table, $where, $values, $joins, null, null, true, $external_order);
		// $this->utils->debug_log('GET_PLAYER_ROLLING_COMM_INFO result', $result);
		$summary = $this->data_tables->summary($request, $table, $joins,
			'SUM( real_bets ) total_real_bet, SUM(total_bets) total_avail_bet, SUM(rolling_comm_amt) total_amount',
			null, $columns, $where, $values);

		$result['summary'][0]['total_real_bet'] = $this->utils->formatCurrencyNoSym($summary[0]['total_real_bet']);
		$result['summary'][0]['total_avail_bet'] = $this->utils->formatCurrencyNoSym($summary[0]['total_avail_bet']);
		$result['summary'][0]['total_amount'] = $this->utils->formatCurrencyNoSym($summary[0]['total_amount']);

		$result['sub_summary'][0]['sub_total_real_bet'] = $this->utils->formatCurrencyNoSym(array_sum(array_map(function ($row) {return str_replace(',', '', $row[4]);}, $result['data'])));
		$result['sub_summary'][0]['sub_total_avail_bet'] = $this->utils->formatCurrencyNoSym(array_sum(array_map(function ($row) {return str_replace(',', '', $row[5]);}, $result['data'])));
		$result['sub_summary'][0]['sub_total_amount'] = $this->utils->formatCurrencyNoSym(array_sum(array_map(function ($row) {return str_replace(',', '', $row[7]);}, $result['data'])));

		return $result;
	}

	//===agency========================================================


	public function task_list($request, $user_id, $isAdminUser, $is_export=false){
		$readOnlyDB = $this->getReadOnlyDB();

		$this->load->library('data_tables', array("DB" => $readOnlyDB));
		$this->load->model(array('queue_result','users'));
		// $request = $this->input->post();
		$use_export_csv_with_progress_template =$this->utils->getConfig('use_export_csv_with_progress');
		$input = $this->data_tables->extra_search($request);

		$table = 'queue_results';

		$joins = array();
		$where = array();
		$values = array();
		$group_by = array();
		$having = array();


		# DEFINE TABLE COLUMNS ########################################################################################################################################################################
		$i = 0;
		$columns = [
			[
				'alias' => 'id',
				'select' => 'queue_results.id'
			],
			[
				'alias' => 'status',
				'select' => 'queue_results.status'
			],
			[
				'alias' => 'created_at',
				'select' => 'queue_results.created_at'
			],
			[
				'alias' => 'func_name',
				'select' => 'queue_results.func_name'
			],
			[
				'alias' => 'result_json',
				'select' => 'queue_results.result'
			],
			[
				'alias' => 'full_params',
				'select' => 'queue_results.full_params'
			],
			[
				'dt' => $i++,
				'alias' => 'updated_at',
				'select' => 'queue_results.updated_at',
				'formatter' => 'dateTimeFormatter',
				'name' => lang('Date'),
			],
			[
				'dt' => $i++,
				'alias' => 'token',
				'select' => 'queue_results.token',
				'formatter' => function($d, $row) use ($is_export,$use_export_csv_with_progress_template) {
					if(!$is_export) {

						$funcName = $row['func_name'];
						$use_export_csv_with_progress  = false;
						$is_remote_export = $funcName == 'remote_export_csv';
						if(in_array($funcName, $use_export_csv_with_progress_template['admin'])){
					    	$use_export_csv_with_progress =  true;
				     	}

                        if($use_export_csv_with_progress || $is_remote_export){
                        	$d='<a href="'.site_url('/export_data/queue/'.$d).'" target="_blank">'.$d.'</a>';
						}else{
							$d='<a href="'.site_url('/system_management/common_queue/'.$d).'" target="_blank">'.$d.'</a>';
						}
					}
					return $d;
				},
				'name' => lang('ID'),
			],
			[
				'dt' => $i++,
				'alias' => 'func_name',
				'select' => 'queue_results.func_name',
				'name' => lang('Type'),
				'formatter' => function ($d, $row)  use ($is_export,$use_export_csv_with_progress_template) {

					if($row['func_name'] == 'remote_export_csv'){
						$full_params = json_decode($row['full_params'],true);
						$no_of_tries = 5;
                        //find the index of extra_search array
						$extra_search_index= 0;
						$target_func_name = null;

						for ($i=0; $i < $no_of_tries ; $i++) {
							if(isset($full_params[$i]['extra_search'])){
								$extra_search_index = $i;
							}
						}
						$extra_search = $full_params[$extra_search_index]['extra_search'];

						if($row['func_name'] == 'remote_export_csv'){
							if($is_export){
								$target_func_name = '-'.$extra_search['target_func_name'];
							}else{
								$target_func_name = '<br><span class="text-info" style="font-size:12px;">('.$extra_search['target_func_name'].')</span>';
							}

						}

						return $d.$target_func_name;
					}
					return $d;

				}
			],
			[
				'dt' => $i++,
				'alias' => 'status',
				'select' => 'queue_results.status',
				'formatter' => function($d, $row) use ($is_export) {

					$queue_row =  $this->queue_result->getResult($row['token']);
					$rlt = (array)json_decode($queue_row['result']);
					$use_export_csv_with_progress_template =$this->utils->getConfig('use_export_csv_with_progress');
					$use_export_csv_with_progress = false;
					$funcName = $queue_row['func_name'];
					$is_remote_export = $funcName == 'remote_export_csv';
					$progress = '';
					if(in_array($funcName, $use_export_csv_with_progress_template['admin']) || $is_remote_export){
						$use_export_csv_with_progress =  true;
						$progress_stat = isset($rlt['progress']) ? $rlt['progress'] : '0';
						$written = isset($rlt['written']) ? $rlt['written'] : '';
						$total_count = isset($rlt['total_count']) ? $rlt['total_count'] : '';
						$progress .='<span data-toggle="tooltip" title="Written: '.number_format((int)$written).' Total rows: '.number_format((int)$total_count).'">('.$progress_stat.'%)</span>';
						// $progress .='<div class="progress" data-toggle="tooltip" title="Written: '.number_format((int)$written).' Total rows: '.number_format((int)$total_count).'">';
						// $progress .='<div class="progress-bar progress-bar-striped active" style="width:'.$progress_stat.'%;min-width:40%" role="progressbar" aria-valuenow="1" aria-valuemin="20" aria-valuemax="100" >';
						// $progress .='<span class="progresslabel">'.$progress_stat.'%</span>';
						// $progress .='</div>';
						// $progress .='</div>';
					}
					$created_at = strtotime($row['created_at']);
					$current_time = strtotime(date('Y-m-d H:i:s'));
					$difference = abs($current_time - $created_at)/3600;

					$csv_filename=null;
					switch ($d) {
						case Queue_result::STATUS_NEW_JOB:
							if(!$is_export){
								if($use_export_csv_with_progress || $is_remote_export){
								//$d=lang('Processing')."(".$rlt['progress']."%)".$progress;
								//$d=lang('Processing').$progress;
								$d='<span data-toggle="tooltip" title=""style="font-size:15px;" class="glyphicon  glyphicon glyphicon-hourglass text-success" data-placement="top" data-original-title="'.lang('Processing').'"></span>'.$progress;
								}else{
									$d='<span data-toggle="tooltip" title=""style="font-size:15px;" class="glyphicon  glyphicon glyphicon-hourglass text-success" data-placement="top" data-original-title="'.lang('Processing').'"></span>';
								}
								//if greater than 1 hout it means there something happen
								if($difference > 1){
									$d='<span data-toggle="tooltip" title=""style="font-size:15px;"  class="glyphicon  glyphicon-warning-sign text-danger" data-placement="top" data-original-title="'.lang('Something happened on remote server').'"></span>';
								}
							}else{
								$d=lang('Processing');
							}

							break;

						case Queue_result::STATUS_DONE:
							    if(!$is_export){
							    	$d='<span data-toggle="tooltip" title="" style="font-size:15px;" class="glyphicon glyphicon-ok text-success" data-placement="top" data-original-title="'.lang('Done').'"></span>';
								    $result_json = json_decode($row['result_json'],true);
								    if($row['func_name'] == 'remote_export_csv'){
									$csv_filename = '<a style="font-size:15px;" class="text-right" href="'.site_url().'reports/'.$result_json['filename'].'"><span data-toggle="tooltip" title=""  class="glyphicon glyphicon glyphicon-download text-right" data-placement="top" data-original-title="'.lang('Download').'"></span></a>';
								    }
								}else{
									$d=lang('Done');
								}

							break;

						case Queue_result::STATUS_ERROR:
							if(!$is_export){
								$d='<span data-toggle="tooltip" title=""style="font-size:15px;" class="glyphicon glyphicon-warning-sign text-danger" data-placement="top" data-original-title="'.lang('Failed').'"></span>';
							}else{
								$d=lang('Failed');
							}

							break;

						case Queue_result::STATUS_STOPPED:
							if(!$is_export){
								$d='<span data-toggle="tooltip" title=""style="font-size:15px;"  class="glyphicon  glyphicon-warning-sign text-warning" data-placement="top" data-original-title="'.lang('Stopped').'"></span>';
							}else{
								$d=lang('Stopped');
							}

							break;
					}

					if(!$is_export){
						return $d.'&nbsp;&nbsp;'.$csv_filename;
					}else{
						return $d;
					}

				},
				'name' => lang('Status'),
			],
			[
				'dt' => $i++,
				'alias' => 'result',
				'select' => 'queue_results.token',
				'formatter' => function ($d, $row)  use ($is_export)  {
					if(!$is_export) {
						$d='<a href="'.site_url('/system_management/common_queue/'.$d).'" target="_blank">'.lang('Show').'</a>';
						// $id="_result_".$row['id'];
						// return "<a class='btn btn-xs btn-primary' onclick='showContent(\"".$id."\")'>".lang('Show')."</a><div style='display:none;' id='".$id."'><pre>".$this->utils->encodeJson($this->utils->decodeJson($d), true)."</pre></div>";
						// return "<pre>".$this->utils->encodeJson($this->utils->decodeJson($d), true)."</pre>";
					}
					return $d;
				},
				'name' => lang('Result'),
			],
			[
				'dt' => $i++,
				'alias' => 'created_at',
				'select' => 'queue_results.created_at',
				'name' => lang('Created At'),
			],
			[
				'dt' => $i++,
				'alias' => 'admin_user',
				'select' => 'adminusers.username',
				'name' => lang('Admin User'),
			],
			[
				'dt' => $i++,
				'alias' => 'player_username',
				'select' => 'player.username',
				'name' => lang('Player'),
			],
			[
				'dt' => $i++,
				'alias' => 'language',
				'select' => 'queue_results.lang',
				'formatter' => function ($d, $row)  use ($is_export)  {

					switch ($d) {
						case 1:
						$d=lang('English');
						break;
						case 2:
						$d=lang('Chinese');
						break;
						case 3:
						$d=lang('Indonesian');
						break;
						case 4:
						$d=lang('Vietnamese');
						break;
						case 5:
						$d=lang('Korean');
						break;
						case 6:
						$d=lang('Thai');
						break;
						default:
						$d=lang('English');
						break;
					}

					return $d;
				},
				'name' => lang('Language'),
			],
		];

		$joins['adminusers'] = 'adminusers.userId = queue_results.caller and queue_results.caller_type='.Queue_result::CALLER_TYPE_ADMIN;
		$joins['player'] = 'player.playerId = queue_results.caller and queue_results.caller_type='.Queue_result::CALLER_TYPE_PLAYER;
		// $joins['game_type'] = 'game_type.id = total_player_game_hour.game_type_id';
		// $joins['game_description'] = 'game_description.id = total_player_game_hour.game_description_id';
		// $joins['external_system'] = 'external_system.id = total_player_game_hour.game_platform_id';
		// $joins['player'] = 'player.playerId = total_player_game_hour.player_id';
		// $joins['affiliates'] = 'affiliates.affiliateId = player.affiliateId';

		# FILTER ######################################################################################################################################################################################

		if($isAdminUser){

			$user_id = null;
			if (isset($input['username'])) {
				$user_id = $this->users->getIdByUsername($input['username']);

				$where[] = "queue_results.caller = ? and queue_results.caller_type=?";
				$values[] = $user_id;
				$values[] = Queue_result::CALLER_TYPE_ADMIN;
			}

		}


		if (isset($input['datetime_from'], $input['datetime_to'])) {

			$where[] ='queue_results.updated_at >= ?';
			$where[] ='queue_results.updated_at <= ?';

			$values[] = $input['datetime_from'];
			$values[] = $input['datetime_to'];

		}

		# OUTPUT ######################################################################################################################################################################################
        // $this->utils->debug_log('GAME_REPORT where values', $where, $values, $group_by, $having, $joins);
		$result = $this->data_tables->get_data($request, $columns, $table, $where, $values, $joins, $group_by, $having, false);

		return $result;
	}

	public function response_result_list($request, $is_export=true){
		$readOnlyDB = $this->getReadOnlyDB();

		$this->load->library('data_tables', array("DB" => $readOnlyDB));
		$this->load->model(array('response_result','player_model'));

		$input = $this->data_tables->extra_search($request);
		$table = 'response_results';

		$joins = array();
		$where = array();
		$values = array();
		$group_by = array();
		$having = array();

		$apiMap=$this->utils->getAllSystemMap();
		$abstractApi=$this->utils->loadAnyGameApiObject();

		# DEFINE TABLE COLUMNS ########################################################################################################################################################################
		$i = 0;
		$columns = [
			[
				'dt' => $i++,
				'alias' => 'created_at',
				'select' => 'response_results.created_at',
				'formatter' => 'dateTimeFormatter',
				'name' => lang('Date'),
			],
			[
				'dt' => $i++,
				'alias' => 'id',
				'select' => 'response_results.id',
				'name' => lang('ID'),
			],
			[
				'dt' => $i++,
				'alias' => 'system_type_id',
				'select' => 'response_results.system_type_id',
				'formatter' => function($d, $row) use ($is_export, $apiMap) {
					if(isset($apiMap[$d])){
						return $apiMap[$d];
					}
					else if($d == SMS_API){
						return 'SMS';
					}
					else if($d == VOICE_API){
						return 'VOICE';
					}
                    else if($d == NOTIFY_IN_APP_API){
						return 'NOTIFY IN APP';
					}

					return $d;
				},
				'name' => lang('API ID'),
			],
			[
				'dt' => $i++,
				'alias' => 'related_id2',
				'select' => 'response_results.related_id2',
				'formatter' => function($d, $row) use ($is_export) {
					if(!$is_export) {
						$resentFlag = ''; // append resend flag
						if($row['extra']){
							$resentFlag .= '<div class="glyphicon glyphicon-repeat resendFlag" data-toggle="tooltip" data-placement="right" title="resent"></div>';
						}

						if(substr($d, 0, 1) == 'W') {
							return empty($d) ? '' : '<a href="'.site_url('/payment_management/viewWithdrawalRequestList?dwStatus=payProc&withdraw_code='.$d).'" target="_blank">'.$d.'</a>'. $resentFlag;
						} else {
							return empty($d) ? '' : '<a href="'.site_url('/payment_management/deposit_list/?secure_id='.$d).'" target="_blank">'.$d.'</a>'. $resentFlag;
						}
					}
					return $d;
				},
				'name' => lang('Secure Id'),
			],
			[
				'dt' => $i++,
				'alias' => 'related_id3',
				'select' => 'response_results.related_id3',
				'formatter' => function($d, $row) use ($is_export) {
					if($row['request_api'] == 'SMS' || $row['request_api'] == 'telesales_response' || $row['request_api'] == 'VOICE')
					{
						if(!$is_export) {
							return empty($d) ? '' : '<a href="'.site_url('/player_management/searchAllPlayer?contactNumber='.$d).'" target="_blank">'.$d.'</a>';
						}
						return $d;
					}
					else
                        return lang('N/A');
				},
				'name' => lang('Mobile'),
			],
			[
				'dt' => $i++,
				'alias' => 'related_id3',
				'select' => 'response_results.related_id3',
				'formatter' => function($d, $row) use ($is_export) {

					return $row['system_type_id'] == SMTP_API ? $d : lang('N/A');

				},
				'name' => lang('Email Address'),
			],
			[
				'dt' => $i++,
				'alias' => 'content',
				'select' => 'response_results.filepath',
				'formatter' => function($d, $row) use ($is_export) {
					if(!$is_export) {
						return empty($d) ? '' : '<a href="'.site_url('/system_management/show_response_content/'.$d).'" target="_blank">'.lang('Show').'</a>';
					}
					return $d;
				},
				'name' => lang('Content'),
			],
			[
				'dt' => $i++,
				'alias' => 'request_api',
				'select' => 'response_results.request_api',
				'name' => lang('Method'),
			],
			[
				'dt' => $i++,
				'alias' => 'status_code',
				'select' => 'response_results.status_code',
				'name' => lang('Status Code'),
			],
			[
				'dt' => $i++,
				'alias' => 'status_text',
				'select' => 'response_results.status_text',
				'name' => lang('Status Text'),
			],
			[
				'dt' => $i++,
				'alias' => 'player_id',
				'select' => 'response_results.player_id',
				'name' => lang('Player'),
				'formatter' => function($d, $row) use ($is_export) {
					if($is_export){
						return $d;
					}
					if(!empty($d)){
						return '<a href="/player_management/userInformation/'.$d.'" target="_blank">'.$d.'</a>';
					}

					return lang('N/A');
				},
			],
			[
				'dt' => $i++,
				'alias' => 'flag',
				'select' => 'response_results.flag',
				'formatter' => function($d, $row) use ($is_export) {

					switch ($d) {
						case 1:
							return lang('Normal');
							break;

						case 2:
							return lang('Error');
							break;

						default:
							return lang('Unknown');
							break;
					}
				},
				'name' => lang('Flag'),
			],
			[
				'dt' => $i++,
				'alias' => 'filepath',
				'select' =>'response_results.filepath',
				'formatter' => function($d, $row) use ($is_export) {
					if(!$is_export) {
						return empty($d) ? '' : '<a href="'.site_url('/system_management/download_response_result/'.$d).'" target="_blank">'.lang('Download').'</a>';
					}
					return $d;
				},
				'name' => lang('Result'),
			],
			[ // for resend flag referenced.
				'dt' => $i++,
				'alias' => '',
				'select' =>'response_results.extra',
				'formatter' => function($d, $row) use ($is_export) {
					// if(!$is_export) {
					// 	return empty($d) ? '' : '<a href="'.site_url('/system_management/download_response_result/'.$d).'" target="_blank">'.lang('Download').'</a>';
					// }
					return $d;
				},
				'name' => lang('Result'),
			],
		];

		// $joins['adminusers'] = 'adminusers.userId = queue_results.caller and queue_results.caller_type='.Queue_result::CALLER_TYPE_ADMIN;
		// $joins['player'] = 'player.playerId = queue_results.caller and queue_results.caller_type='.Queue_result::CALLER_TYPE_PLAYER;
		// $joins['game_type'] = 'game_type.id = total_player_game_hour.game_type_id';
		// $joins['game_description'] = 'game_description.id = total_player_game_hour.game_description_id';
		// $joins['external_system'] = 'external_system.id = total_player_game_hour.game_platform_id';
		// $joins['player'] = 'player.playerId = total_player_game_hour.player_id';
		// $joins['affiliates'] = 'affiliates.affiliateId = player.affiliateId';

		# FILTER ######################################################################################################################################################################################
		
		if(isset($input['response_table'])){
			if($input['response_table'] == 1){
				$dateStr = date("Ym", strtotime($input['datetime_to']));
				$table = 'resp_cashier_'.$dateStr.' as response_results';
			}
		}

		if (isset($input['username'])) {
			$player_id = $this->player_model->getPlayerIdByUsername($input['username']);
			if(!empty($player_id)){
				$where[]='response_results.player_id = ?';
				$values[]=$player_id;
			}
		}

		if (isset($input['order_id']) && !empty($input['order_id'])) {
			$where[]='response_results.related_id2 = ?';
			$values[]=$input['order_id'];
		}

		if(isset($input['mobile']) && !empty($input['mobile']) && isset($input['email']) && !empty($input['email'])){
			$where[]='response_results.related_id3 = ? || response_results.related_id3 = ?';
			$values[]=$input['mobile'];
			$values[]=$input['email'];
		}
		else
		{
			if (isset($input['mobile']) && !empty($input['mobile'])) {
				$where[]='response_results.related_id3 = ?';
				$values[]=$input['mobile'];

				if (!isset($input['api_id'])) {
					$where[]='response_results.system_type_id = ?';
					$values[]=SMS_API;
				}
			}

			if (isset($input['email']) && !empty($input['email'])) {
				$where[]='response_results.related_id3 = ?';
				$values[]=$input['email'];

				if (!isset($input['api_id'])) {
					$where[]='response_results.system_type_id = ?';
					$values[]=SMTP_API;
				}
			}

		}

		if (isset($input['api_id']) && isset($input['show_gamegateway_api']) && $input['show_gamegateway_api']=='true') {
			$where[]='response_results.system_type_id IN (?,?)';
			$values[]=$input['api_id'];
			$values[]=GAMEGATEWAY_API;
		} elseif (isset($input['api_id'])) {
			$where[]='response_results.system_type_id = ?';
			$values[]=$input['api_id'];
		} elseif (isset($input['show_gamegateway_api']) && $input['show_gamegateway_api']=='true') {
			$where[]='response_results.system_type_id = ?';
			$values[]=GAMEGATEWAY_API;
		}

		if (isset($input['flag'])) {
			$where[]='response_results.flag = ?';
			$values[]=$input['flag'];
		}

		if (isset($input['method']) && !empty($input['method'])) {
			$where[]='response_results.request_api = ?';
			$values[]=$input['method'];
		}else{
			if(isset($input['no_query_balance']) && $input['no_query_balance']=='true'){
				//ignore function
				$where[]='response_results.request_api != ?';
				$values[]=Abstract_game_api::API_queryPlayerBalance;
			}
			if(!isset($input['show_sync_data']) || $input['show_sync_data']!='true'){
				//ignore function
				$where[]='response_results.request_api != ?';
				$where[]='response_results.request_api != ?';
				$where[]='response_results.request_api != ?';
				$where[]='response_results.request_api != ?';
				$values[]=Abstract_game_api::API_syncGameRecords;
				$values[]=Abstract_game_api::API_syncBalance;
				$values[]=Abstract_game_api::API_syncGameRecordsByPlayer;
				$values[]='syncMultiplePlatformGameRecords';
			}
		}

		if (isset($input['result_id']) && !empty($input['result_id'])) {
			$where[]='response_results.id = ?';
			$values[]=$input['result_id'];
		}else{
			if (isset($input['datetime_from'], $input['datetime_to'])) {

				$where[] ='response_results.created_at >= ?';
				$where[] ='response_results.created_at <= ?';

				$values[] = $input['datetime_from'];
				$values[] = $input['datetime_to'];

			}
		}

		# OUTPUT ######################################################################################################################################################################################
        // $this->utils->debug_log('GAME_REPORT where values', $where, $values, $group_by, $having, $joins);
		$distinct=false;
        $external_order=[];
        $not_datatable='';
        $countOnlyField='response_results.id';
		$result = $this->data_tables->get_data($request, $columns, $table, $where, $values, $joins, $group_by,
			$having, $distinct, $external_order, $not_datatable, $countOnlyField);

            return $result;
	} // EOF response_result_list

	public function sms_report_list($request, $is_export=true){
		$readOnlyDB = $this->getReadOnlyDB();

		$this->load->library('data_tables', array("DB" => $readOnlyDB));
		$this->load->model('response_result');

		$input = $this->data_tables->extra_search($request);
		$table = 'response_results';

		$joins = array();
		$where = array();
		$values = array();
		$group_by = array();
		$having = array();

		# DEFINE TABLE COLUMNS ########################################################################################################################################################################
		$i = 0;
		$columns = [
			[
				'dt' => $i++,
				'alias' => 'created_at',
				'select' => 'response_results.created_at',
				'formatter' => 'dateTimeFormatter',
				'name' => lang('Date'),
			],
			[
				'dt' => $i++,
				'alias' => 'id',
				'select' => 'response_results.id',
				'name' => lang('ID'),
			],
			[
				'dt' => $i++,
				'alias' => 'system_type_id',
				'select' => 'response_results.system_type_id',
				'formatter' => function($d, $row){
					if($d == SMS_API){
						return 'SMS';
					}
                    else if($d == VOICE_API){
                        return 'VOICE';
                    }
					return $d;
				},
				'name' => lang('API ID'),
			],
			[
				'dt' => $i++,
				'alias' => 'related_id3',
				'select' => 'response_results.related_id3',
				'formatter' => function($d, $row) use ($is_export) {
					if(!$is_export) {
						return empty($d) ? '' : '<a href="'.site_url('/player_management/searchAllPlayer?contactNumber='.$d).'" target="_blank">'.$d.'</a>';
					}
					return $d;
				},
				'name' => lang('Mobile'),
			],
			[
				'dt' => $i++,
				'alias' => 'status_code',
				'select' => 'response_results.status_code',
				'name' => lang('Status Code'),
			],
			[
				'dt' => $i++,
				'alias' => 'status_text',
				'select' => 'response_results.status_text',
				'name' => lang('Status Text'),
			],
			[
				'dt' => $i++,
				'alias' => 'flag',
				'select' => 'response_results.flag',
				'formatter' => function($d, $row) use ($is_export) {

					switch ($d) {
						case 1:
							return lang('Normal');
							break;

						case 2:
							return lang('Error');
							break;

						default:
							return lang('Unknown');
							break;
					}
				},
				'name' => lang('Flag'),
			],
			[
				'dt' => $i++,
				'alias' => 'content',
				'select' => 'response_results.filepath',
				'formatter' => function($d, $row) use ($is_export) {
					if(!$is_export) {
						return empty($d) ? '' : '<a href="'.site_url('/system_management/show_sms_content/'.$d).'" target="_blank">'.lang('Show').'</a>';
					}
					return $d;
				},
				'name' => lang('Content'),
			],
		];

		# FILTER ######################################################################################################################################################################################
		$where[]='response_results.system_type_id IN (?,?)';
		$values[]=SMS_API;
        $values[]=VOICE_API;

		if (isset($input['mobile']) && !empty($input['mobile'])) {
			$where[]='response_results.related_id3 = ?';
			$values[]=$input['mobile'];
		}

		if (isset($input['flag'])) {
			$where[]='response_results.flag = ?';
			$values[]=$input['flag'];
		}

		if (isset($input['result_id']) && !empty($input['result_id'])) {
			$where[]='response_results.id = ?';
			$values[]=$input['result_id'];
		}else{
			if (isset($input['datetime_from'], $input['datetime_to'])) {

				$where[] ='response_results.created_at >= ?';
				$where[] ='response_results.created_at <= ?';

				$values[] = $input['datetime_from'];
				$values[] = $input['datetime_to'];

			}
		}

		# OUTPUT ######################################################################################################################################################################################
		$distinct=false;
        $external_order=[];
        $not_datatable='';
        $countOnlyField='response_results.id';
		$result = $this->data_tables->get_data($request, $columns, $table, $where, $values, $joins, $group_by,
			$having, $distinct, $external_order, $not_datatable, $countOnlyField);

		return $result;
	}

	public function smtp_report_list($request, $is_export=true){
		$readOnlyDB = $this->getReadOnlyDB();

		$this->load->library('data_tables', array("DB" => $readOnlyDB));
		$this->load->model('response_result');

		$input = $this->data_tables->extra_search($request);
		$table = 'response_results';

		$joins = array();
		$where = array();
		$values = array();
		$group_by = array();
		$having = array();

		# DEFINE TABLE COLUMNS ########################################################################################################################################################################
		$i = 0;
		$columns = [
			[
				'dt' => $i++,
				'alias' => 'created_at',
				'select' => 'response_results.created_at',
				'formatter' => 'dateTimeFormatter',
				'name' => lang('Date'),
			],
			[
				'dt' => $i++,
				'alias' => 'id',
				'select' => 'response_results.id',
				'name' => lang('ID'),
			],
			[
				'dt' => $i++,
				'alias' => 'system_type_id',
				'select' => 'response_results.system_type_id',
				'formatter' => function($d, $row){
					if($d == SMTP_API){
						return 'SMTP';
					}
					return $d;
				},
				'name' => lang('API ID'),
			],
			[
				'dt' => $i++,
				'alias' => 'player_id',
				'select' => 'response_results.player_id',
				'formatter' => function($d, $row) use ($is_export) {

					$username = '';

					if(!empty($d))
					{
						$this->load->model('player_model');
						$username = $this->player_model->getUsernameById($d);
					}

					if(!$is_export) {
						return empty($d) || empty($username) ? '' : '<a href="'.site_url('/player_management/userInformation/'.$d).'" target="_blank">'.$username.'</a>';
					}

					return $username;
				},
				'name' => lang('Username'),
			],
			[
				'dt' => $i++,
				'alias' => 'related_id3',
				'select' => 'response_results.related_id3',
				'formatter' => function($d, $row) use ($is_export) {
					return $d;
				},
				'name' => lang('Email Address'),
			],
			[
				'dt' => $i++,
				'alias' => 'status_code',
				'select' => 'response_results.status_code',
				'name' => lang('Status Code'),
			],
			[
				'dt' => $i++,
				'alias' => 'status_text',
				'select' => 'response_results.status_text',
				'name' => lang('Status Text'),
			],
			[
				'dt' => $i++,
				'alias' => 'flag',
				'select' => 'response_results.flag',
				'formatter' => function($d, $row) use ($is_export) {

					switch ($d) {
						case 1:
							return lang('Normal');
							break;

						case 2:
							return lang('Error');
							break;

						default:
							return lang('Unknown');
							break;
					}
				},
				'name' => lang('Flag'),
			],
			[
				'dt' => $i++,
				'alias' => 'content',
				'select' => 'response_results.filepath',
				'formatter' => function($d, $row) use ($is_export) {
					if(!$is_export) {
						return empty($d) ? '' : '<a href="'.site_url('/system_management/show_smtp_api_content/'.$d).'" target="_blank">'.lang('Show').'</a>';
					}
					return $d;
				},
				'name' => lang('Content'),
			],
		];

		# FILTER ######################################################################################################################################################################################
		$where[]='response_results.system_type_id = ?';
		$values[]=SMTP_API;


		if (isset($input['email']) && !empty($input['email'])) {
			$where[]='response_results.related_id3 = ?';
			$values[]=$input['email'];
		}

		if (isset($input['username']) && !empty($input['username'])) {

			$this->load->model('player_model');
			$player_id = $this->player_model->getPlayerIdByUsername($input['username']);

			$where[]='response_results.player_id = ?';
			$values[]=$player_id;
		}

		if (isset($input['flag'])) {
			$where[]='response_results.flag = ?';
			$values[]=$input['flag'];
		}

		if (isset($input['result_id']) && !empty($input['result_id'])) {
			$where[]='response_results.id = ?';
			$values[]=$input['result_id'];
		}else{
			if (isset($input['datetime_from'], $input['datetime_to'])) {

				$where[] ='response_results.created_at >= ?';
				$where[] ='response_results.created_at <= ?';

				$values[] = $input['datetime_from'];
				$values[] = $input['datetime_to'];

			}
		}

		# OUTPUT ######################################################################################################################################################################################
		$distinct=false;
        $external_order=[];
        $not_datatable='';
        $countOnlyField='response_results.id';
		$result = $this->data_tables->get_data($request, $columns, $table, $where, $values, $joins, $group_by,
			$having, $distinct, $external_order, $not_datatable, $countOnlyField);

		return $result;
	}

	public function responsibleGamingReport($request, $is_export = false){
        $readOnlyDB = $this->getReadOnlyDB();

        $this->load->library('data_tables', array("DB" => $readOnlyDB));
		$this->load->library('player_manager');

		$this->load->model(array('responsible_gaming','player_model','transactions','game_logs','risk_score_model'));

        $this->load->helper(['player_helper']);
        $rgArr = $this->responsible_gaming->getAllTypeReport();
        //$request = $this->input->post();
		$input = $this->data_tables->extra_search($request);


		$table =<<<EOF
(  /* START as responsible_gaming_reporting */
	SELECT
		R.player_id player_id,
		R.player_id id,
		R.id request_id,
		P.username username, /* sol */

		P.levelId,
        P.groupName,
        P.levelName,

		R.type `TYPE`,
		R.status as `status`,
		R.created_at created_at,
		R.amount amount,
		R.date_from date_from,
		R.date_to date_to,
		DATEDIFF(R.date_to, R.date_from) days_total,
		DATEDIFF(R.date_to, NOW()) days_left,

        P.playerId tag, /* will be return of player_tagged_list(player_id as tag) */
		P.playerId playerId,

		R.player_id risk_score, /* will be return of generate_total_risk_score(player_id as risk_score) */
		R.player_id kyc_lvl, /* will be return of getPlayerCurrentRiskLevel(player_id as kyc_lvl) */
		IFNULL(R.updated_at, R.created_at) updated_at
	FROM `responsible_gaming` AS R
	LEFT OUTER JOIN `player` AS P ON P.playerId = R.player_id
) as responsible_gaming_reporting
EOF;
		$table = $this->data_tables->clearBlankSpaceAfterHeredoc($table);

        $where = array();
        $values = array();
        $group_by = array();
        $having = array();

        // JOIN /////
		$joins = [];

        // DEFINE TABLE COLUMNS /////
        $i = 0;
        $columns = [
            // Checkbox
            [
                'dt' => !$is_export ? $i++ : null, // #1
                'alias' => 'id',
                'select' => 'player_id',
                'formatter' => function ($d, $row) use($is_export)  {
                    if($is_export){
                        return $row['id'];
                    }else{
                        return '<input type="checkbox" id ="tag-user-id-'. $row['id'] .'"  class="clickTag" onclick="chkTag();"  value="' . $row['id'] . ' "/>';
                    }
                },
                'name' => lang('#'),
            ],
            // Checkbox
            [
                'dt' => $i++, // #2
                'alias' => 'request_id',
                'select' => 'request_id',
                'name' => lang('ID'),
            ],
            // Player username
            [
                'dt' => $i++, // #3
                'alias' => 'player_username',
                'select' => 'username',
                'formatter' => function ($d, $row) use($is_export)  {
                    if($is_export){
                        return $row['player_username'];
                    }else{
                        return '<a href="/player_management/userInformation/'.$row['id'].'">'.$row['player_username'].'</a>';
                    }
                },
                'name' => lang('Username'),
            ],
            // Player level
            [
                'dt' => $i++, // #4
                'alias' => 'group_level',
                'select' => 'CONCAT(groupName, \'|\', levelName )',
				'formatter' => function ($d, $row) {
                    // $this->utils->debug_log('the data ---->', $d);
                    if(!empty($d)){
					    $d = (explode("|",$d));
                        return lang($d[0]).' - '.lang($d[1]);
                    }else{
                        return 'N/A';
                    }
				},
                'name' => lang('Player Level'),
            ],
            // Type
            [
            	'dt'		=> $i++ , // #5
                'alias'		=> 'type',
                'select'	=> 'type',
                'formatter'	=> function ($d, $row) { return $this->responsible_gaming->type_to_string($d); },
                'name'      => lang('Type of Exclusion')
            ],
            // Status
            [
            	'dt'		=> $i++ , // #6
            	'alias'		=> 'responsible_gaming_status' ,
            	'select'	=> 'status' ,
            	'formatter'	=> function ($d, $row) { return $this->responsible_gaming->status_to_string($d); },
                'name'      => lang('Status')
            ],
            // Created At
            [
                'dt'		=> $i++ , // #7
                'alias'		=> 'created_at' ,
                'select'	=> 'created_at' ,
                'formatter'	=> function ($d, $row) { return date('Y-m-d H:i', strtotime($d)); },
                'name'      => lang('Date of Request')
            ],
            // Requested Amount
            [
                'dt'		=> $i++ , // #8
                'alias'		=> 'amount' ,
                'select'	=> 'amount',
                'formatter'	=> function ($d, $row) {
                    $allow_type = [Responsible_gaming::DEPOSIT_LIMITS,Responsible_gaming::WAGERING_LIMITS];
                    if(in_array($row['type'],$allow_type)){
                        return $d;
                    }else{
                        return lang('N/A');
                    }

                },
                'name'      => lang('Requested Amount')
            ],
            // Start time
			[
            	'dt'		=> $i++ , // #9
            	'alias'		=> 'date_from' ,
            	'select'	=> 'date_from' ,
            	'formatter'	=> function ($d, $row) { return date('Y-m-d H:i', strtotime($d)); },
                'name'      => lang('Start Time')
            ],
            // End time
            [
            	'dt'		=> $i++ , // #10
            	'alias'		=> 'date_to' ,
            	'select'	=> 'date_to' ,
            	'formatter'	=> function ($d, $row) {
            		if ($row['type'] == Responsible_gaming::SELF_EXCLUSION_PERMANENT) {
            			return '—';
            		}
            		return date('Y-m-d H:i', strtotime($d));
            	},
                'name'      => lang('End Time')
            ],
            // Total days
            [
            	'dt'		=> $i++ , // #11
            	'alias'		=> 'days_total' ,
            	'select'	=> 'DATEDIFF(date_to, date_from)' ,
            	'formatter'	=> function ($d, $row) use($is_export){
            		if ($d < 0)
            			{ $d = 0; }

            		if ($row['type'] == Responsible_gaming::SELF_EXCLUSION_PERMANENT) {
                        return '—';
            		}
            		return sprintf('%d %s', $d, lang('day'));
            	},
                'name'      => lang('Total Days')
            ],
            // Days left
            [
            	'dt'		=> $i++ , // #12
            	'alias'		=> 'days_left' ,
            	'select'	=> 'DATEDIFF(date_to, NOW())' ,
            	'formatter'	=> function ($d, $row) use($is_export) {
            		if ($d > $row['days_total'])
            			{ $d = $row['days_total']; }
            		if ($d < 0)
            			{ $d =  0; }
            		if ($row['type'] == Responsible_gaming::SELF_EXCLUSION_PERMANENT) {
                        return '—';
            		}
            		return $d == 0 ? '—' : sprintf('%d %s', $d, lang('Day'));
            	},
                'name'      => lang('Days Left')
            ],
            // Tag
            [
                'dt' => $i++, // #13
                'alias' => 'tag',
                'select' => 'tag',
                'formatter' => function ($d, $row) use($is_export){
					if ( ! empty($row['tag']) ){
						if($is_export){
							return player_tagged_list($row['tag'], true);
						}else{
							return player_tagged_list($row['tag']);
						}
					}
                },
                'name' => lang('Tag'),
            ],
            // Risk Score
            [
                'dt' => $i++, // #14
                'alias' => 'risk_score',
                'select' => 'risk_score',
                'formatter' => function ($d, $row) {
					if ( ! empty($row['risk_score']) ){
						return $this->risk_score_model->generate_total_risk_score($row['risk_score']);
					}
                },
                'name' => lang('risk score'),
            ],
            // KYC Level
            [
                'dt' => $i++, // #15
                'alias' => 'kyc_lvl',
                'select' => 'kyc_lvl',
                'formatter' => function ($d, $row) {
					if ( ! empty($row['kyc_lvl']) ){
						return $this->risk_score_model->getPlayerCurrentRiskLevel($row['kyc_lvl']);
					}
                },
                'name' => lang('KYC Level'),
            ],
            // Updated At
            [
                'dt' => $i++, // #16
                'alias' => 'updated_at',
                'select' => 'IFNULL(updated_at, created_at)',
                'name' => lang('Updated at'),
            ],

        ];

        // FILTER /////

		$this->utils->debug_log('RG Report ----->', 'input', $input);

		/// Patch for OGP-14359 : [Responsible Gaming Report]Inconsistent number of rows in export csv.
		if( isset($input['username'])
			&& $input['username'] ==''
		){
			unset($input['username']);
		}

		if( isset($input['player_level'])
			&& $input['player_level'] ==''
		){
			unset($input['player_level']);
		}

		if( isset($input['tag_id'])
			&& $input['tag_id'] ==''
		){
			unset($input['tag_id']);
		}

		if( isset($input['days_left_min'])
			&& $input['days_left_min'] ==''
		){
			unset($input['days_left_min']);
		}

		if( isset($input['days_left_max'])
			&& $input['days_left_max'] ==''
		){
			unset($input['days_left_max']);
		}

        // Player username
        if (isset($input['username'], $input['search_by'])) {
        	switch ($input['search_by']) {
        		case 1 :
        			$where[] = "username LIKE ?";
        			$values[] = "%{$input['username']}%";
        			break;
        		case 2 :
        			$where[] = "username = ?";
        			$values[] = $input['username'];
        			break;
        		default :
        	}
		}

        // Start Time
        if (isset($input['search_start_time_date'], $input['start_at_from'], $input['start_at_to']) && $input['search_start_time_date']=='on') {
            $where[] = "date_from >= ?";
            $values[] = $input['start_at_from'];
            $where[] = "date_from <= ?";
            $values[] = $input['start_at_to'];
		}

        // Updated_at
        if (isset($input['search_reg_date'], $input['update_at_from'], $input['update_at_to']) && $input['search_reg_date']=='on') {
            $where[] = 'IF(updated_at IS NULL, created_at, updated_at) BETWEEN ? AND ? ';
            $values[] = $input['update_at_from'];
            $values[] = $input['update_at_to'];
        }



        // Player level
        if (isset($input['player_level'])) {
            $where[] = "levelId = ?";
            $values[] = $input['player_level'];
        }

        // Days_left max/min
        if (isset($input['days_left_min'])) {
        	$where[] = 'DATEDIFF(date_to, NOW()) >= ?';
        	$values[] = $input['days_left_min'];
        }

        if (isset($input['days_left_max'])) {
        	$where[] = 'DATEDIFF(date_to, NOW()) <= ?';
        	$values[] = $input['days_left_max'];
        }

        // Types
        if (isset($input['rg_type'])) {
			$wherein_rg_type = is_array($input['rg_type']) ? implode(',', $input['rg_type']) : $input['rg_type'];
        	$where[] = "type IN ( {$wherein_rg_type} )";
        } else { // Return nothing when no type is selected
        	$where[] = "type < 0";
        }

        // Statuses
        if (isset($input['rg_status'])) {
			$wherein_rg_status = is_array($input['rg_status']) ? implode(',', $input['rg_status']) : $input['rg_status'];
        	$where[] = "`status` IN ( {$wherein_rg_status} )";
        } else { // Return nothing when no status is selected
        	$where[] = "status < 0";
		}

		/// tag_id // Tag
		if( ! empty($input['tag_id']) ){
			$this->player_manager->getPlayerTagById($input['tag_id'], $query);
			$clause = [];
			// foreach ($rows as $indexNumber => $currRow){
			if ($query->num_rows() > 0) {
				foreach ($query->result_array() as $currRow){
					$currPlayerId = $currRow['playerId'];
					$currClause =<<<EOF
					player_id = "$currPlayerId"
EOF;
					$clause[] = $this->data_tables->clearBlankSpaceAfterHeredoc($currClause);
				}
			}

			if( ! empty($clause) ){
				$where[] = '( /* START as tag_id */'
						. implode('||', $clause)
						. ' /* EOF as tag_id */ )';
			}else{ // for no player has the tag.
				$where[] = '( /* START as tag_id */ player_id = 0 /* EOF as tag_id */ )';
			}
		}

        $having=[];
        $distinct=false;
        //$external_order = [['column' => 2, 'dir' => 'desc']];
        $external_order =[];
        $not_datatable='';
        $countOnlyField='request_id';

		/// for $this->data_tables->last_query to debug.
		// $this->config->set_item('debug_data_table_sql',true);

		$result = $this->data_tables->get_data(	$request // #1
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
		/// for debug
		// $sql = $this->data_tables->last_query;
		// $this->utils->debug_log('OGP-14359:$sql:', $sql);
		// $result['sql'] = $sql;

        return $result;

    } // EOF responsibleGamingReport

    /**
     * Data provider for api/bonus_games_report (api_report_module::bonus_games_report())
     *
     * @param	array 	$request	full post array sent by Datatable client.
     * @param	boolean	$is_export	Output mode, remove html and extra formatting if true.
     * @return	JSON	JSON array,custom format for Datatable.
     */
    public function bonus_games_report($request, $is_export = false) {
		$readOnlyDB = $this->getReadOnlyDB();
		$this->load->library('data_tables', array("DB" => $readOnlyDB));
		$this->data_tables->is_export = $is_export;
		$this->load->model(['player_model']);

		$request['draw'] = '';
		$external_order = '';

		$input 		= $this->data_tables->extra_search($request);
		$this->utils->debug_log('bonus_games_report-input', $input);
		$table 		= 'promo_game_player_game_history AS H';
		$joins		= [
			'player AS P'				=> 'H.player_id = P.playerId' ,
			'promo_game_games AS G'		=> 'H.game_id = G.id' ,
			'promo_game_gametypes AS T'	=> 'G.gametype_id = T.id' ,
			'promorules AS PR'			=> 'H.promorule_id = PR.promorulesId' ,
			'promotype AS PT'			=> 'PR.promoCategory = PT.promotypeId'
		];
		$where		= [];
		$values		= [];
		$group_by	= [];
		$having		= [];

		$i = 0;
		$columns = [
			[  'alias' => 'promorule_id'	, 'select' => 'H.promorule_id'	] ,
			[  'alias' => 'player_id'		, 'select' => 'H.player_id'	] ,
			[  'alias' => 'game_id'			, 'select' => 'H.game_id'	] ,
			[ 'dt' => $i++ , 'alias' => 'date'			, 'select' => 'IF (H.realized_at IS NULL, H.updated_at, H.realized_at)' ] ,
			[ 'dt' => $i++ , 'alias' => 'player'		, 'select' => 'P.username'	,
				'formatter' => function ($d, $row) {
					return "<a href='/player_management/userInformation/{$row['player_id']}'>$d</a>";
			} ] ,
			[ 'dt' => $i++ , 'alias' => 'group_level'	, 'select' => 'P.username'	,
				'formatter' => function ($d, $row) {
					$player_group_level = $this->player_model->getPlayerCurrentLevel($row['player_id']);
		            if($player_group_level){
		            	$player_group = lang($player_group_level[0]['groupName']);
		            	$player_level = lang($player_group_level[0]['vipLevelName']);
		                return "{$player_group} - {$player_level}";
		            }
		            return '$mdash;';
			} ] ,
			[ 'dt' => $i++ , 'alias' => 'gametype'		, 'select' => 'T.gametype'	,
				'formatter' => function ($d, $row) {
					return lang($d);
			} ] ,
			[ 'dt' => $i++ , 'alias' => 'gamename'		, 'select' => 'G.gamename'	,
				'formatter' => function ($d, $row) {
					return "<a href='/marketing_management/bonusGameSettings/{$row['game_id']}'>$d</a>";
			} ] ,
			[ 'dt' => $i++ , 'alias' => 'promo_type'	, 'select' => 'PT.promoTypeName'	,
				'formatter' => function ($d, $row) {
					if (substr($d, 0, 5) == '_json') {
						$langs = json_decode(substr($d, 6), 'as_array');
						return $langs[2];
					}
					return $d;
			} ] ,
			[ 'dt' => $i++ , 'alias' => 'promo_rule'	, 'select' => 'PR.promoName'	,
				'formatter' => function ($d, $row) {
					return "<a href='/marketing_management/editPromoRule/{$row['promorule_id']}'>$d</a>";
			} ] ,
			[ 'dt' => $i++ , 'alias' => 'bonus_type_raw', 'select' => 'H.bonus_type'	] ,
			[ 'dt' => $i++ , 'alias' => 'bonus_type'	, 'select' => 'H.bonus_type'	,
				'formatter' => function ($d, $row) {
					return lang($d);
			} ] ,
			[ 'dt' => $i++ , 'alias' => 'bonus_amount'	, 'select' => 'H.bonus_amount'	,
				'formatter' => function ($d, $row) {
					return $row['bonus_type'] == 'nothing' ? '&mdash;' : $d;
			} ] ,
		];

		// Default clause: show only closed games
		$where[] = "H.status = ?";
		$values[] = 'closed';

		// Date from/Date to
		if (isset($input['enable_date'], $input['date_from'], $input['date_to'])) {
			$where[] = 'IF(H.realized_at IS NULL, H.updated_at, H.realized_at) >= ?';
			$values[] = $input['date_from'];
			$where[] = 'IF(H.realized_at IS NULL, H.updated_at, H.realized_at) <= ?';
			$values[] = $input['date_to'];
		}

		// Player username
		if (isset($input['player_username'])) {
			$match = isset($input['player_match']) ? $input['player_match'] : 'similar';

			switch ($match) {
				case 'exact' :
					$where[] = 'P.username = ?';
					$values[] = $input['player_username'];
					break;
				case 'similar' : default :
					$where[] = 'P.username LIKE ?';
					$values[] = "%{$input['player_username']}%";
					break;
			}
		}

		// Player Level
		// TODO: implement clause
		if (isset($input['player_level_id'])) {
			$where[] = 'P.levelId = ?';
			$values[] = $input['player_level_id'];
		}

		// Gametype
		if (isset($input['game_type'])) {
			$where[] = 'G.gametype_id = ?';
			$values[] = $input['game_type'];
		}

		// Promo type
		if (isset($input['promo_type'])) {
			$where[] = 'PR.promoCategory = ?';
			$values[] = $input['promo_type'];
		}

		// Promo rule
		if (isset($input['promo_rule'])) {
			$where[] = 'H.promorule_id = ?';
			$values[] = $input['promo_rule'];
		}

		// Bonus type
		if (isset($input['bonus_type'])) {
			$where[] = 'H.bonus_type = ?';
			$values[] = $input['bonus_type'];
		}

		// Min amount
		if (isset($input['amount_min'])) {
			$where[] = 'H.bonus_amount >= ?';
			$values[] = $input['amount_min'];
		}

		// Max amount
		if (isset($input['amount_max']) && $input['amount_max'] > 0) {
			$where[] = 'H.bonus_amount <= ?';
			$values[] = $input['amount_max'];
		}

		$result = $this->data_tables->get_data($request, $columns, $table, $where, $values, $joins, $group_by, $having, $external_order);

		// Generate summary fields (DISUSED)
		// For this only generates grandtotal of all rows, not by-page subtotals.

		// $summary_selects = "
		// 	SUM(IF(H.bonus_type = 'cash', bonus_amount, 0)) AS sum_bonus_cash ,
		// 	SUM(IF(H.bonus_type = 'vip_exp', bonus_amount, 0)) AS sum_bonus_vip_exp ,
		// 	SUM(IF(H.bonus_type = 'cash', 1, 0)) AS count_rounds_bonus ,
		// 	SUM(IF(H.bonus_type = 'vip_exp', 1, 0)) AS count_rounds_vip_exp ,
		// 	SUM(IF(H.bonus_type = 'nothing', 1, 0)) AS count_rounds_nothing ,
		// ";

		// $summary = $this->data_tables->summary($request, $table, $joins, $summary_selects, null, $columns, $where, $values);

		// $sumrow = $summary[0];
		// $sumrow['sum_bonus_cash'] = $this->utils->formatCurrencyNoSym($sumrow['sum_bonus_cash']);
		// $sumrow['sum_bonus_vip_exp'] = $this->utils->formatCurrencyNoSym($sumrow['sum_bonus_vip_exp']);
		// $sumrow['count_rounds_bonus'] = $this->utils->formatInt($sumrow['count_rounds_bonus']);
		// $sumrow['count_rounds_vip_exp'] = $this->utils->formatInt($sumrow['count_rounds_vip_exp']);
		// $sumrow['count_rounds_nothing'] = $this->utils->formatInt($sumrow['count_rounds_nothing']);

		// $result['summary'][0] = $sumrow;

		return $result;
	}

	/**
     * Data provider for api/player_analysis_report (api_report_module::player_analysis_report())
     *
     * @param	array 	$request	full post array sent by Datatable client.
     * @param	boolean	$is_export	Output mode, remove html and extra formatting if true.
     * @return	JSON	JSON array,custom format for Datatable.
     */
    public function player_analysis_report($request, $permissions, $is_export = false) {
    	$session_timeout =  $this->utils->getConfig('session_timeout');
		$dateTimeNow =  $this->utils->getNowDateTime();
		$dateTimeNow = $dateTimeNow->modify($session_timeout);
		$dateTimeNow = $dateTimeNow->getTimestamp();
		$readOnlyDB = $this->getReadOnlyDB();

		$this->load->library('data_tables', array("DB" => $readOnlyDB));
		$this->load->helper(['player_helper']);

		$viewVerifiedStatus = false;
		// $this->load->library(array('permissions'));
		$this->load->model(array('player_model', 'agency_model', 'wallet_model', 'game_logs','risk_score_model','player_kyc','player_attached_proof_file_model','kyc_status_model','transactions','game_provider_auth'));

		$this->data_tables->is_export = $is_export;
		$wallet_model = $this->wallet_model;
		$risk_score_model = $this->risk_score_model;
		$player_attached_proof_file_model = $this->player_attached_proof_file_model;
		$player_kyc = $this->player_kyc;
		$kyc_status_model = $this->kyc_status_model;
		$game_provider_auth = $this->game_provider_auth;
		$game_logs = $this->game_logs;
		$model = $this;

		$getGameSystemMap = $this->utils->getGameSystemMap();
		$promoTransactionStatus = Player_promo::TRANS_STATUS_APPROVED . ','. Player_promo::TRANS_STATUS_FINISHED_WITHDRAW_CONDITION;
		# START DEFINE COLUMNS #################################################################################################################################################

		$input = $this->data_tables->extra_search($request);

		$i = 0;
		$columns = array();
			$columns[] =  array(
				'select' => 'player.playerId',
				'alias' => 'playerId',
				'name' => lang('lang.action'),
			);
			$columns[] =  array(
				'select' => 'player.verified_email',
				'alias' => 'verified_email',
				'name' => lang("player.06"),
			);
			$columns[] =  array(
				'select' => 'player.verified_phone',
				'alias' => 'verified_phone',
				'name' => lang("Phone / mobile number"),
			);
			$columns[] = array(
				'dt' => $i++,
				'alias' => 'username',
				'select' => 'player.username',//'( CASE WHEN affiliates.username IS NULL THEN player.username ELSE CONCAT(player.username, \' (\', affiliates.username,  \')\' ) END )',
				'formatter' => ($is_export) ? 'defaultFormatter' : function ($d, $row) {
					return '<a href="/player_management/userInformation/' . $row['playerId'] . '">' . $d . '</a>';
				},
				'name' => lang('player.01'),
			);
			$columns[] = array(
				'dt' => $i++,
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
				'dt' => $i++,
				'alias' => 'birthdate',
				'select' => 'playerdetails.birthdate',
				'name' => lang("date.of.birth"),
				'formatter' => function ($d, $row) use ($is_export) {
					if($is_export){
						return trim(trim($d), ',') ?: lang('lang.norecyet');
					}else{
						return trim(trim($d), ',') ?: '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
					}
				},
			);
			$columns[] = array(
				'dt' => $i++,
				'alias' => 'email',
				'select' => 'player.email',
				'name' => lang("player.06"),
				'formatter' => function ($d, $row) use ($permissions, $viewVerifiedStatus, $is_export) {
					$str = $d;
					if ($permissions['view_player_detail_contactinfo_em'] ) {

						if ($is_export) {
							$str = $row['verified_email'] == self::DB_TRUE ? $str . '  ' . lang('Verified') : $str . '  ' . lang('Not verified');
						} else {
							if ($viewVerifiedStatus) {
								if ($row['verified_email'] == self::DB_TRUE) {
									$str = '<span class="text-success"><i class="fa fa-envelope"></i> ' . $str . '</a></span>';
								} else {
									$str = '<span class="text-danger"><i class="fa fa-envelope"></i> ' . $str . '</a></span>';
								}
							} else {
								$str = '<span class="text-default"><i class="fa fa-envelope"></i> ' . $str . '</a></span>';
							}
						}

					} else {

						$str = $this->utils->maskMiddleString($str,0,strlen($str)-4, 4);
						if (!$is_export) {
							$str = '<span title="' . lang('con.aff01') . '">' . $str . '</span>';
						}
					}

					return $str;
				},
			);
			$columns[] = array(
				'dt' => $i++,
				'alias' => 'contactNumber',
				'select' => 'playerdetails.contactNumber',
				'name' => lang("Phone / mobile number"),
				'formatter' => function ($d, $row) use ($permissions, $viewVerifiedStatus, $is_export) {

					$str = $d;
					if ($permissions['view_player_detail_contactinfo_cn'] ) {
                        if ($is_export) {
							$str = $row['verified_phone'] == self::DB_TRUE ? $str . '   ' . lang('Verified') : $str . '  ' . lang('Not verified');
						} else {
							if ($viewVerifiedStatus) {
								if ($row['verified_phone'] == self::DB_TRUE) {
									$str = '<span class="text-success"><i class="fa fa-phone"></i> ' . $str . '</a></span>';
								} else {
									$str = '<span class="text-danger"><i class="fa fa-phone"></i> ' . $str . '</a></span>';
								}
							} else {
								$str = '<span class="text-default"><i class="fa fa-phone"></i> ' . $str . '</a></span>';
							}
						}

					} else {
						$str = $this->utils->maskMiddleString($str,0,strlen($str)-4, 4);
						if (!$is_export) {
							if ($row['verified_phone'] == self::DB_TRUE) {
								$str = '<span class="text-success"><i class="fa fa-phone"></i><span title="' . lang('con.aff01') . '">' . $str . '</span>';
							} else {
								$str = '<span class="text-danger"><i class="fa fa-phone"></i><span title="' . lang('con.aff01') . '">' . $str . '</span>';
							}
						}

					}
					return $str;
				},

			);
			$columns[] = array(
				'dt' => $i++,
				'alias' => 'group_level',
				'select' => 'CONCAT(player.groupName, \'|\',player.levelName )',
				'formatter' => function ($d) use ($is_export) {
					if(!empty($d)){
						$d = (explode("|",$d));
					}

					if ($is_export) {
						return !empty($d) ? lang($d[0]).' - '.lang($d[1]) : lang('lang.norecyet');
					} else {
						return !empty($d) ? lang($d[0]).' - '.lang($d[1]) : '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
					}
				},
				'name' => lang("player.07"),
			);
			$columns[] = array(
				'dt' => $i++,
				'alias' => 'tagName',
				'select' => 'player.playerId',
				'name' => lang("player.41"),
				'formatter' => function ($d, $row) use ($is_export) {
					return player_tagged_list($row['playerId'], $is_export);
				},
			);
			if ($this->utils->isEnabledFeature('ignore_player_analysis_permissions') || $permissions['reset_player_login_password'] ) {
	        	$columns[] = array(
	                'dt' => $i++,
	                'alias' => 'password',
	                'select' => 'player.password',
	                'name' => lang("Password"),
	            );
	        }
	        $columns[] = array(
				'dt' => $i++,
				'alias' => 'ChangedTimes',
				'select' => '(select count(player_password_history.player_id) from player_password_history
							WHERE player_password_history.player_id = player.playerId)',
				'name' => lang("Changed Times"),
				'formatter' => function ($d, $row) use ($is_export) {
					return !empty($d) ? $d :lang('lang.norecyet');
				},
			);
			$columns[] = array(
				'dt' => $i++,
				'alias' => 'passwordUpdatedAt',
				'select' => '(select player_password_history.updated_at from player_password_history
							WHERE player_password_history.player_id = player.playerId
							order by player_password_history.updated_at desc limit 1)',
				'name' => lang("Last Changed Time"),
				'formatter' => function ($d, $row) use ($is_export) {
					return !empty($d) ? $d :lang('lang.norecyet');
				},
			);
	        if ($this->utils->isEnabledFeature('ignore_player_analysis_permissions') || $permissions['player_verification_question'] ) {
	        	 $columns[] = array(
	                'dt' => $i++,
	                'alias' => 'secretQuestion',
	                'select' => 'player.secretQuestion',
	                'name' => lang("Verification Question"),
	                'formatter' => function($d) use ($permissions){
	                	return lang($d);
	                }
	            );
	        } if ($this->utils->isEnabledFeature('ignore_player_analysis_permissions') || $permissions['player_verification_questions_answer'] ) {
            $columns[] = array(
                'dt' => $i++,
                'alias' => 'secretAnswer',
                'select' => 'player.secretAnswer',
                'name' => lang("Verification Answer"),
                );
        	}
			$columns[] = array(
				'dt' => $i++,
				'alias' => '',
				'select' => '',
				'name' => lang("Linked Accounts"),
				'formatter' => function ($d, $row) use ($is_export) {
					$this->load->model('linked_account_model');
					$linkedAccounts = $this->linked_account_model->getLinkedAccount(array("username"=>$row['username'],
																   "search_type"=>Linked_account_model::SEARCH_TYPE_EXACT_USERNAME,
																   "link_datetime"=>null));

					if($is_export || (!$this->utils->isEnabledFeature('ignore_player_analysis_permissions') && !$this->permissions->checkPermissions('linked_account')) || !$this->utils->isEnabledFeature('linked_account') || empty($linkedAccounts) || empty($linkedAccounts[0]['linked_accounts'])) return lang('lang.norecyet');

					$output = '<a onclick="modal(\'/player_management/showLinkedAccountsModal/' . $row['username'] . '\',\'' . lang('Linked Accounts') . '\')" href="javascript:void(0);" data-toggle="tooltip" title="'.lang('Linked Accounts').'"><i class="fa fa-search"></i></a>';

  					$output .= 'Y';

                    return $output;


				},

			);
			$columns[] = array(
                'dt' => $i++,
                'alias' => 'blocked',
                'select' => 'player.blocked' ,
                'name' => lang("lang.status"),
                'formatter' => function ($d, $row) use ($is_export){
                    $formatter = 1;
                    return  $this->utils->getPlayerStatus($row['playerId'],$formatter,$d,$is_export);
                },
            );
			$columns[] = array(
				'dt' => $i++,
				'alias' => 'risk_level',
				'select' => 'playerdetails.playerId',
				'formatter' => function ($d) use ($is_export,$risk_score_model) {
					if ($is_export) {
						return $risk_score_model->getPlayerCurrentRiskLevel($d) && $risk_score_model->generate_total_risk_score($d) ? $risk_score_model->getPlayerCurrentRiskLevel($d) .' / '. $risk_score_model->generate_total_risk_score($d) : lang('lang.norecyet');
					}else{
						return $risk_score_model->getPlayerCurrentRiskLevel($d) && $risk_score_model->generate_total_risk_score($d) ? $risk_score_model->getPlayerCurrentRiskLevel($d) .' / '. $risk_score_model->generate_total_risk_score($d) : '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
					}
				},
				'name' => lang("Risk Level/Score"),
			);
			$columns[] = array(
				'dt' => $i++,
				'alias' => 'kyc_level',
				'select' => 'playerdetails.playerId',
				'formatter' => function ($d) use ($is_export,$player_kyc,$kyc_status_model) {
					if ($is_export) {
						return $player_kyc->getPlayerCurrentKycLevel($d) && $kyc_status_model->getPlayerCurrentStatus($d) ? $player_kyc->getPlayerCurrentKycLevel($d) .' / '. $kyc_status_model->getPlayerCurrentStatus($d): lang('lang.norecyet');
					}else{
						return $player_kyc->getPlayerCurrentKycLevel($d) && $kyc_status_model->getPlayerCurrentStatus($d) ? $player_kyc->getPlayerCurrentKycLevel($d) .' / '. $kyc_status_model->getPlayerCurrentStatus($d): '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
					}
				},
				'name' => lang("KYC Level / Rate Code"),
			);
			$columns[] = array(
				'dt' => $i++,
				'alias' => 'ip_address',
				'select' => 'player_ip_last_request.ip',
				'name' => lang('player.10'),
				'formatter' => function ($d, $row) use ($is_export) {
					if ($is_export) {
						return trim(trim($d), ',') ?: lang('lang.norecyet');
					} else {
						$output = '<a onclick="modal(\'/player_management/showIPHistoryModal/' . $row['playerId'] . '\',\'' . lang('player.ui75') . '\')" href="javascript:void(0);" data-toggle="tooltip" title="'.lang('player.ui75').'"><i class="fa fa-search"></i></a>';

	  					$output .= 'Y';

	                    return $output;
					}
				},
			);
			$columns[] = array(
                'dt' => $i++,
                'alias' => 'lastDepositDateTime',
                'select' => 'player_last_transactions.last_deposit_date',
                'name' => lang('player.105'),
                'formatter' => function ($d, $row) use ($is_export) {
                    return !empty($d) ? $d :lang('lang.norecyet');
                },
            );
			$columns[] = array(
				'dt' => $i++,
				'alias' => 'total_deposit_amount',
				'select' => 'player.totalDepositAmount',
				'name' => lang('report.in03'),
				'formatter' => 'currencyFormatter',
			);
			$columns[] = array(
				'dt' => $i++,
				'alias' => 'total_withdrawal_amount',
				'select' => 'player.approvedWithdrawAmount',
				'name' => lang('report.sum10'),
				'formatter' => function ($d, $row) use ($readOnlyDB, $is_export) {
					$query = $readOnlyDB->query("SELECT sum(transactions.amount) as total_withdrawal FROM transactions WHERE transactions.to_id = ? AND transactions.to_type = ? AND transactions.transaction_type = ? AND transactions.status = ?  ", array(
						$row['playerId'],
						Transactions::PLAYER,
						Transactions::WITHDRAWAL,
						Transactions::APPROVED,
					));
					$r = $query->row_array();

					return $this->data_tables->currencyFormatter(isset($r['total_withdrawal']) ? $r['total_withdrawal'] : 0);
				},
			);
			$columns[] = array(
				'dt' => $i++,
				'alias' => 'total_betting_amount',
				'select' => 'player.playerId',
				'name' => lang('player.ui31'),
				'formatter' => function ($d, $row) use ($is_export,$game_provider_auth,$game_logs,$getGameSystemMap,$input) {
					$total_bet = 0;
					if(isset($input['game_list_search'])){
						$game_platforms = $this->game_provider_auth->getGamePlatforms($d);
						$game_logs = $this->game_logs->getSummary($d);
						$game_list_search = is_array($input['game_list_search']) ? $input['game_list_search'] : array($input['game_list_search']);

						foreach ($getGameSystemMap as $gameKey => $value) {
							foreach ($game_platforms as $game_platform) {
								if($game_platform['id'] == $gameKey){
									$game_platform_id = $game_platform['id'];
                                    if(in_array($game_platform_id, $game_list_search)){
                                        if (isset($game_logs[$game_platform_id])) {
                                            $total_bet += $game_logs[$game_platform_id]['bet']['sum'];
                                        }
                                    }
								}
							}
						}
					}
					return $this->data_tables->currencyFormatter(isset($total_bet) ? $total_bet : 0);
					}
			);
			$columns[] = array(
				'dt' => $i++,
				'alias' => 'total_main_balance',
				'select' => 'player.playerId',
				'name' => lang("cashier.05"),
				'formatter' => function ($d, $row) use ($is_export,$wallet_model) {
					$totalBalance = 0;
					$balanceDetails = $this->wallet_model->getBalanceDetails($d);
					if(!empty($balanceDetails)){
						if(isset($balanceDetails['total_balance'])){
							$totalBalance = $balanceDetails['total_balance'];
						}
					}
					return $this->data_tables->currencyFormatter($totalBalance);
					}
			);
			$columns[] = array(
				'dt' => $i++,
				'alias' => 'promo_approved_times',
				'select' => "(select count(playerpromo.playerpromoId) from playerpromo
					WHERE playerpromo.playerId = player.playerId
					and playerpromo.transactionStatus in ($promoTransactionStatus))",
				'name' => lang("promo_approved_times"),
				'formatter' => function ($d, $row) use ($is_export) {
					return !empty($d) ? $d :lang('lang.norecyet');
				}
			);

			foreach ($getGameSystemMap as $gameKey => $value) {
				$columns[] = array(
					'dt' => $i++,
					'alias' => 'total_total_nofrozen',
					'select' => 'player.playerId',
					'name' => lang("Overall Winloss ").$value,
					'formatter' => function ($d, $row) use ($is_export,$game_provider_auth,$game_logs,$gameKey,$input) {
						$gain_loss_sum = 0;
						if(isset($input['game_list_search'])){
							$game_platforms = $this->game_provider_auth->getGamePlatforms($d);
							$game_logs = $this->game_logs->getSummary($d);
							$game_list_search = is_array($input['game_list_search']) ? $input['game_list_search'] : array($input['game_list_search']);

							foreach ($game_platforms as &$game_platform) {
								if($game_platform['id'] == $gameKey){
									$game_platform_id = $game_platform['id'];
                                    if (in_array($game_platform_id, $game_list_search)) {
                                        if (isset($game_logs[$game_platform_id])) {
                                            $game_platform = array_merge($game_platform, $game_logs[$game_platform_id]);
                                            $gain_loss_sum = isset($game_logs[$game_platform_id]['gain_loss']['sum']) ? $game_logs[$game_platform_id]['gain_loss']['sum'] : 0;
                                        }
                                    }
								}
							}
						}
						return $this->data_tables->currencyFormatter($gain_loss_sum);
						}
				);

			}


		# END DEFINE COLUMNS #################################################################################################################################################
		$this->benchmark->mark('data_gathering_start');
		$promoTransactionStatus = Player_promo::TRANS_STATUS_APPROVED . ','. Player_promo::TRANS_STATUS_FINISHED_WITHDRAW_CONDITION;
		$table = 'player';
		$joins = array(
			'playerdetails' => 'playerdetails.playerId = player.playerId',
			// 'player_runtime' => 'player_runtime.playerId = player.playerId',
			// 'playertag' => 'playertag.playerId = player.playerId',
			// 'affiliates' => 'affiliates.affiliateId = player.affiliateId',
			'player_ip_last_request' => 'player_ip_last_request.player_id = player.playerId',
			'player_last_transactions' => 'player_last_transactions.player_id = player.playerId'
			// 'player_device_last_request' => 'player_device_last_request.player_id = player.playerId',
			// 'playerpromo' => 'playerpromo.playerId = player.playerId and playerpromo.transactionStatus in ('.$promoTransactionStatus.')',
			// 'player_password_history' => 'player_password_history.player_id = player.playerId'
		);
		$group_by = []; // array('player.playerId');

		# START PROCESS SEARCH FORM #################################################################################################################################################
		$where = array();
		$values = array();
		$usernames = null;
		if (isset($input['username'])) {
			$usernames = $input['username'];

			if(!empty($usernames)){
				if(is_array($usernames)){
					// foreach ($usernames as $key => $value) {
					// 	$usernames[$key] = $this->player_model->getUsernameById($value);
					// }
					$where[] = "player.playerId IN ('". implode("','" , $usernames ) ."')";
				} else {
					// $username = $this->player_model->getUsernameById($usernames);
					// if(!empty($username)){
						$where[] = "player.playerId = ?";
						$values[] = $usernames;
					// }
				}
			}
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
		$countOnlyField='player.playerId';
		$this->benchmark->mark('data_gathering_end');

		$this->benchmark->mark('playerList_result_start');

		$columns = $this->checkIfEnabled($this->utils->isEnabledFeature('show_risk_score'), array('risk_level'), $columns);
		$columns = $this->checkIfEnabled($this->utils->isEnabledFeature('show_kyc_status'), array('kyc_level'), $columns);
		$columns = $this->checkIfEnabled(!$this->utils->isEnabledFeature('close_aff_and_agent'), array('affiliate','agent'), $columns);
		if(!empty($values) || is_array($usernames)){
			$result = $this->data_tables->get_data($request, $columns, $table, $where, $values, $joins,
						$group_by, $having, $distinct, $external_order, $not_datatable, $countOnlyField);
		} else {
			$result = $this->data_tables->get_data($request, $columns, $table);
		}

		if($is_export){
		    //drop result if export
			return $csv_filename;
		}
		$this->benchmark->mark('playerList_result_end');

		$this->utils->debug_log(array(
			'player_analysis_report data gathering================================' => $this->benchmark->elapsed_time('data_gathering_start', 'data_gathering_end'),
		 	'player_analysis_report result=============================' => $this->benchmark->elapsed_time('playerList_result_start', 'playerList_result_end'),
		 	'player_analysis_report Summary============================' => $this->benchmark->elapsed_time('playerList_summary_start', 'playerList_summary_end'),
		));
		return $result;
    }

    public function getUserLogs( $request, $is_export = false ){

        $this->load->library('data_tables');
        $input = $this->data_tables->extra_search($request);

        $targetDate = null;
        if(isset($input['log_date_from']) && !empty($input['log_date_from'])){
            $fromDate=new DateTime($input['log_date_from']);
            $targetDate=$fromDate->format('Y-m-d');
        }

		# START DEFINE COLUMNS #################################################################################################################################################
    	$i = 0;
    	$columns = array(
    		array(
    			'dt' => $i++,
    			'alias' => 'logDate',
    			'select' => 'logs.logDate',
    			'name' => lang('report.log05'),
    			'formatter' => function($d, $row) use ($is_export){

    				if ($is_export) {
    					return (!$d || strtotime($d) < 0) ? lang('lang.norecyet') : date('Y-m-d H:i:s', strtotime($d));
    				} else {
    					return (!$d || strtotime($d) < 0) ? '<i>' . lang('lang.norecyet') . '</i>' : date('Y-m-d H:i:s', strtotime($d));
    				}

    			},
    		),
    		array(
    			'dt' => $i++,
    			'alias' => 'username',
    			'select' => 'logs.username',
    			'name' => lang('report.log02'),
    			'formatter' => 'languageFormatter'
    		),
    		array(
    			'dt' => $i++,
    			'alias' => 'userRole',
    			'select' => 'logs.userRole',
    			'name' => lang('report.log04'),
    			'formatter' => 'languageFormatter'
    		),
    		array(
    			'dt' => $i++,
    			'alias' => 'management',
    			'select' => 'logs.management',
    			'name' => lang('report.log03'),
    			'formatter' => 'languageFormatter'
    		),
    		array(
    			'dt' => $i++,
    			'alias' => 'action',
    			'select' => 'logs.action',
    			'name' => lang('lang.action'),
    			'formatter' => 'defaultFormatter'
    		),
    		array(
    			'dt' => $i++,
    			'alias' => 'referrer',
    			'select' => 'logs.referrer',
    			'name' => 'Referrer',
    			'formatter' => function ($d) use ($is_export) {
    				if( ! $is_export ){
    					return $d ? '<a href="' . $d . '">' . $d . '</a>' : '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
    				} else{

    					return ( $d ) ? $d : lang('lang.norecyet');
    				}

    			},
    		),
    		array(
    			'dt' => $i++,
    			'alias' => 'uri',
    			'select' => 'logs.uri',
    			'name' => lang('link'),
    			'formatter' => function ($d) use ($is_export) {
    				if( ! $is_export ){
    					return $d ? '<a href="' . $d . '">' . $d . '</a>' : '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
    				} else{

    					return ( $d ) ? $d : lang('lang.norecyet');
    				}

    			},
    		),
    		array(
    			'dt' => $i++,
    			'alias' => 'data',
    			'select' => 'logs.data',
    			'name' => lang('lang.details'),
    			'formatter' => function ($d) use ($is_export) {
    				$data = str_replace('\n', "\n", strip_tags($d));
    				if( ! $is_export ){
    					if ($data) {
    						return "<pre style='margin-bottom: 0;'>stripslashes($data)</pre>";
    					} else {
    						return '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
    					}
    				}else{
    					return ($data) ? stripslashes($data) : lang('lang.norecyet');
    				}

    			},
    		),
    		array(
    			'dt' => $i++,
    			'alias' => 'ip',
    			'select' => 'logs.ip',
    			'name' => lang('sys.ip08'),
    			'formatter' => function($d, $row) use ($is_export){

    				if ($is_export) {
    					return trim(trim(strip_tags($d)), ',') ?: lang('lang.norecyet');
    				} else {
    					return trim(trim($d), ',') ?: '<i>' . lang('lang.norecyet') . '</i>';
    				}

    			},
    		),
    		array(
    			'dt' => $i++,
    			'alias' => 'status',
    			'select' => 'logs.status',
    			'name' => lang('lang.status'),
    			'formatter' => function ($d) use ($is_export) {
    				if( ! $is_export ){
    					return $d ? '<i class="glyphicon glyphicon-remove text-danger"></i>' : '<i class="glyphicon glyphicon-ok text-success"></i>';
    				}else{
    					return ( $d ) ? '✗' : '✓';
    				}

    			},
    		),
    		array(
    			'dt' => $i++,
    			'alias' => 'extra',
    			'select' => 'logs.extra',
    			'name' => lang('Data'),
    			'formatter' => function ($d) use ($is_export) {

    				$data = str_replace('\n', "\n", strip_tags($d));

    				if( ! $is_export ){
    					if ($data) {
    						return "<pre style='margin-bottom: 0;'>".stripslashes($data)."</pre>";
    					} else {
    						return '<i class="text-muted">' . lang('lang.norecyet') . '</i>';
    					}
    				}else{
    					return ($data) ? stripslashes($data) : lang('lang.norecyet');
    				}
    			},
    		),
    		array(
    			'dt' => $i++,
    			'alias' => 'logId',
    			'select' => 'logs.logId',
    			'name' => lang('File'),
    			'formatter' => function ($d, $row) use ($is_export, $targetDate){
    				if($is_export){
    					return ( $d ) ? strip_tags($d) : lang('lang.norecyet');
    				}else{
    					if(!empty($row['extra'])) {
                            // $this->utils->debug_log('download_user_logs', $d, $targetDate);
    						return empty($d) ? ''
    						: '<a href="'.site_url('/user_management/download_user_logs/'.$d.'/'.$targetDate).'" target="_blank" data-placement="left"
    						data-toggle="tooltip" title="Download data"><i class="fa fa-download"></i></a>';
    					}
    				}
    			}
    		),
    		array(
    			'dt' => $i++,
    			'alias' => 'description',
    			'select' => 'logs.description',
    			'name' => lang('player.tm04'),
    		),
    	);
		# END DEFINE COLUMNS #################################################################################################################################################
        $switch_to_old_logs=false;
        if(isset($input['switch_to_old_logs'])){
            $switch_to_old_logs=$input['switch_to_old_logs']=='true';
        }
        $table = $this->utils->getAdminLogsMonthlyTable($targetDate).' as logs';
        if($switch_to_old_logs){
            $table = 'logs';
        }
        $this->utils->debug_log('switch_to_old_logs', $switch_to_old_logs, 'table', $table);
    	$joins = array();

		# START PROCESS SEARCH FORM #################################################################################################################################################
    	$where = array();
    	$values = array();

    	if (isset($input['log_date_from'], $input['log_date_to'])) {
    		$where[] = "logs.logDate BETWEEN ? AND ?";
    		$values[] = $input['log_date_from'];
    		$values[] = $input['log_date_to'];
    	}

    	if (isset($input['username'])) {
    		$where[] = "logs.username LIKE ?";
    		$values[] = '%' . $input['username'] . '%';
    	}

    	if (isset($input['userRole'])) {
    		$where[] = "logs.userRole = ?";
    		$values[] = $input['userRole'];
    	}

    	if (isset($input['management'])) {
    		$where[] = "logs.management = ?";
    		$values[] = $input['management'];
    	}

    	if (isset($input['action'])) {
    		$where[] = "logs.action = ?";
    		$values[] = $input['action'];
    	}

    	if (isset($input['extra'])) {
    		if($input['extra'] == self::FILTER_EMPTY_DATA) {
    			$where[] = "logs.extra != ?";
    			$values[] = '';
    		} elseif($input['extra'] == self::ONLY_EMPTY_DATA) {
    			$where[] = "logs.extra IS NULL";
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

    	$result = $this->data_tables->get_data($request, $columns, $table, $where, $values, $joins);

    	if($is_export){
    			    //drop result if export
    		return $csv_filename;
    	}


    	return $result;

	}

	/**
	 * detail: get Player Risk Score History Logs
	 *
	 * @param string player_id
	 * @param datetime create_date
	 * @return json
	 */
	public function risk_score_history_logs($request, $player_id = '', $is_export = false ) {

        $this->load->library(['data_tables']);
		$i = 0;

		$input = $this->data_tables->extra_search($request);

		$columns = array(
			array(
				'alias' => 'id',
				'select' => 'risk_score_history_logs.id',
				'formatter' => function($d, $row) use ($is_export){
						return $d;
				},
			),
			array(
				'alias' => 'category_description',
				'select' => 'risk_score.category_description',
				'formatter' => function($d, $row) use ($is_export){
						return $d;
				},
			),
			array(
				'alias' => 'result_changes_to',
				'select' => 'risk_score_history_logs.result_change_to'
			),
			array(
				'alias' => 'change_of_score_to',
				'select' => 'risk_score_history_logs.score_to',
			),
			array(
				'alias' => 'change_of_total_score_to',
				'select' => 'risk_score_history_logs.total_score_to',
			),
			array(
				'dt' => $i++,
				'alias' => 'created_at',
				'select' => 'risk_score_history_logs.created_at',
				'formatter' => function($d, $row) use ($is_export){
						return ($d) ? : lang('N/A');
				},
				'name' => lang('Date'),
			),
			array(
				'dt' => $i++,
				'alias' => 'risk_score_category',
				'select' => 'risk_score_history_logs.risk_score_category',
				'formatter' => function($d, $row) use ($is_export){
						return ($d) ? $d.' / '.$row['category_description'] : lang('N/A');
				},
				'name' => lang('Category / Description'),
			),
			array(
				'dt' => $i++,
				'alias' => 'result_changes_from',
				'select' => 'risk_score_history_logs.result_change_from',
				'formatter' => function($d, $row) use ($is_export){
						return sprintf(lang('risk_score_result_changes'), $d , $row['result_changes_to']);
				},
				'name' => lang('Result Changes'),
			),
			array(
				'dt' => $i++,
				'alias' => 'change_of_score_from',
				'select' => 'risk_score_history_logs.score_from',
				'formatter' => function($d, $row) use ($is_export){
						return sprintf(lang('risk_score_change_score'), $d , $row['change_of_score_to']);
				},
				'name' => lang('Change of Score'),
			),
			array(
				'dt' => $i++,
				'alias' => 'change_of_total_score_from',
				'select' => 'risk_score_history_logs.total_score_from',
				'formatter' => function($d, $row) use ($is_export){
						return sprintf(lang('risk_score_change_score'), $d , $row['change_of_total_score_to']);
				},
				'name' => lang('Change of Total Score'),
			),
			array(
				'dt' => $i++,
				'alias' => 'risk_score_level',
				'select' => 'risk_score_history_logs.risk_score_level_to',
				'formatter' => function($d, $row) use ($is_export){
						return ($d) ? : lang('N/A');
				},
				'name' => lang('Risk Score Level'),
			)
		);



		$table = 'risk_score_history_logs';

		$joins = array();
		$joins['risk_score'] = 'risk_score.category_name = risk_score_history_logs.risk_score_category';

		# START PROCESS SEARCH FORM #################################################################################################################################################
		$where = array();
		$values = array();
		$group_by=[];
        $having=[];

		if (isset($input['dateRangeValueStart'])) {
			$where[] = "risk_score_history_logs.created_at >= ?";
			$values[] = $input['dateRangeValueStart'];
		}

		if (isset($input['dateRangeValueEnd'])) {
			$where[] = "risk_score_history_logs.created_at <= ?";
			$values[] = $input['dateRangeValueEnd'];
		}

		if (!empty($player_id)) {
			$where[] = "risk_score_history_logs.player_id = ?";
			$values[] = $player_id;
		}


		# END PROCESS SEARCH FORM #################################################################################################################################################

        $distinct=true;
        $external_order= 'risk_score_history_logs.id DESC';
        $not_datatable='';
		$countOnlyField='risk_score_history_logs.id';


		$result = $this->data_tables->get_data($request, $columns, $table, $where, $values, $joins, $group_by, $having,	 $distinct, $external_order, $not_datatable, $countOnlyField);


		return $result;
	}

	public function getMgQuickFireReport($limit = 20, $start, $playerId = null, $dateFrom = null, $dateTo = null, $filter = false) {
		$datefrom =  date('Y-m-d 00:00:00',strtotime($dateFrom));
		$dateTo =  date('Y-m-d 23:59:59',strtotime($dateTo));

		$this->db->limit($limit, $start);
		$this->db->select('mg.transaction, mg.session, mg.time, mg.description, mg.wagered, mg.payout, mg.win_loss, p.username');
		$this->db->join('player p', 'p.playerId=mg.player_id');
		$this->db->where('time >=', $datefrom);
		$this->db->where('time <=', $dateTo);
		$this->db->order_by("transaction", "desc");

		if($filter){
			$this->db->where('mg.player_id', $playerId);
		}

		$this->db->from('mg_quickfire_game_reports mg');
		return $this->runMultipleRowArray();
	}

	public function getMgQuickFireReportSummary($playerId = null, $dateFrom = null, $dateTo = null, $filter = false){
		$datefrom =  date('Y-m-d 00:00:00',strtotime($dateFrom));
		$dateTo =  date('Y-m-d 23:59:59',strtotime($dateTo));

		$this->db->select('count(*) as count, sum(wagered) as totalWagered, sum(payout) as totalPayout, sum(win_loss) as totalWinloss');
		$this->db->join('player p', 'p.playerId=mg_quickfire_game_reports.player_id');
		$this->db->where('time >=', $datefrom);
		$this->db->where('time <=', $dateTo);

		if($filter){
			$this->db->where('player_id', $playerId);
		}

		$this->db->from('mg_quickfire_game_reports');

		// $sql = $this->db->last_query();
		$sql = $this->db->_compile_select();
		$this->utils->debug_log('getMgQuickFireReport sql : ', $sql);
		return $this->runOneRowArray();
	}

	/**
	 * detail: get transactions summary report
	 *
	 * @param array $request
	 * @param Boolean $is_export
	 *
	 * @return array
	 */
	public function transactionsDailySummaryReport($request, $is_export = false) {

		$readOnlyDB = $this->getReadOnlyDB();

		$this->load->library('data_tables', array("DB" => $readOnlyDB));
		$this->load->model(array('transactions', 'player_model', 'group_level'));

		$this->data_tables->is_export = $is_export;

		$player_model = $this->player_model;

		// $this->benchmark->mark('pre_processing_start');

		# START DEFINE COLUMNS #################################################################################################################################################
		// $i = 0;
		$date_column = 0;
		$username_column = 1;
		$affiliate_column = 2;
		$initial_balance_column = 3;
		$total_deposit_column = 4;
		$total_add_bonus_column = 5;
		$total_add_cashback_column = 6;
		$total_referral_bonus_column = 7;
		$total_vip_bonus_column = 8;
		$total_manual_add_balance_column = 9;
		$total_withdrawal_column = 10;
		$total_subtract_bonus_column = 11;
		$total_manual_subtract_balance_column = 12;
		$total_win_column = 13;
		$total_loss_column = 14;
		$end_balance_column = 15;
		$latest_balance_record_column = 16;
		$balance_validation_column = 17;
		$columns = array(
			array(
				'alias' => 'player_id',
				'select' => 'transactions_daily_summary_report.player_id',
			),
			array(
				'alias' => 'id',
				'select' => 'transactions_daily_summary_report.id',
			),
			array(
				'alias' => 'initial_balance',
				'select' => 'transactions_daily_summary_report.total_initial_balance',
			),
			array(
				'dt' => $date_column,
				'alias' => 'sync_date',
				'select' => 'transactions_daily_summary_report.sync_date',
				'formatter' => 'dateFormatter',
				'name' => lang('Date'),
			),
			array(
				'dt' => $username_column,
				'alias' => 'username',
				'select' => 'player.username',
				'formatter' => function ($d, $row) use ($is_export) {
					if ($is_export) {
						return ($d ? $d : lang('N/A'));
					} else {
						$str = '<i class="fa fa-user" ></i> ' . ($d ? '<a href="/player_management/userInformation/' . $row['player_id'] . '" target="_blank">' . $d . '</a>' : '<i class="text-muted">' . lang('N/A') . '</i>');
						return $str;
					}
				},
				'name' => lang('report.username'),
			),
            array(
                'dt' => $affiliate_column,
                'alias' => 'affiliate',
                'select' => 'affiliates.username',
                'formatter' => function ($d, $row) use ($is_export) {
                    if ($is_export) {
                        return ($d ? $d : lang('N/A'));
                    } else {
                        return $d ? $d : '<i class="text-muted">' . lang('N/A') . '</i>';
                    }
                },
                'name' => lang('a_header.affiliate'),
            ),
			array(
				'dt' => $initial_balance_column,
				'alias' => 'total_initial_balance',
				'select' => 'transactions_daily_summary_report.total_initial_balance',
				'name' => lang('report.initial_balance'),
				'formatter' => function($d, $row) use ($is_export){
						if($d){
							$balanceDetails = json_decode($d,true);
							return $this->data_tables->currencyFormatter($balanceDetails['total_balance']);
						}else{
							return lang('N/A');
						}
				},
			),
			array(
				'dt' => $total_deposit_column,
				'alias' => 'amount',
				'select' => 'transactions_daily_summary_report.total_deposit',
				'name' => lang('report.total_deposit'),
				'formatter' => 'currencyFormatter',
			),
			array(
				'dt' => $total_add_bonus_column,
				'alias' => 'total_add_bonus',
				'select' => 'transactions_daily_summary_report.total_add_bonus',
				'name' => lang('report.total_add_bonus'),
				'formatter' => 'currencyFormatter',
			),
			array(
				'dt' => $total_add_cashback_column,
				'alias' => 'total_add_cashback',
				'select' => 'transactions_daily_summary_report.total_add_cashback',
				'name' => lang('report.total_add_cashback'),
				'formatter' => 'currencyFormatter',
			),
			array(
				'dt' => $total_referral_bonus_column,
				'alias' => 'total_referral_bonus',
				'select' => 'transactions_daily_summary_report.total_referral_bonus',
				'name' => lang('report.total_referral_bonus'),
				'formatter' => 'currencyFormatter',
			),
			array(
				'dt' => $total_vip_bonus_column,
				'alias' => 'total_vip_bonus',
				'select' => 'transactions_daily_summary_report.total_vip_bonus',
				'name' => lang('report.total_vip_bonus'),
				'formatter' => 'currencyFormatter',
			),
			array(
				'dt' => $total_manual_add_balance_column,
				'alias' => 'total_manual_add_balance',
				'select' => 'transactions_daily_summary_report.total_manual_add_balance',
				'name' => lang('report.total_manual_add_balance'),
				'formatter' => 'currencyFormatter',
			),
			array(
				'dt' => $total_withdrawal_column,
				'alias' => 'total_withdrawal',
				'select' => 'transactions_daily_summary_report.total_withdrawal',
				'name' => lang('report.total_withdrawal'),
				'formatter' => 'currencyFormatter',
			),
			array(
				'dt' => $total_subtract_bonus_column,
				'alias' => 'total_subtract_bonus',
				'select' => 'transactions_daily_summary_report.total_subtract_bonus',
				'name' => lang('report.total_subtract_bonus'),
				'formatter' => 'currencyFormatter',
			),
			array(
				'dt' => $total_manual_subtract_balance_column,
				'alias' => 'total_manual_subtract_balance',
				'select' => 'transactions_daily_summary_report.total_manual_subtract_balance',
				'name' => lang('report.total_subtract_balance'),
				'formatter' => 'currencyFormatter',
			),
			array(
				'dt' => $total_win_column,
				'alias' => 'total_win',
				'select' => 'transactions_daily_summary_report.total_win',
				'name' => lang('report.total_win'),
				'formatter' => 'currencyFormatter',
			),
			array(
				'dt' => $total_loss_column,
				'alias' => 'total_loss',
				'select' => 'transactions_daily_summary_report.total_loss',
				'name' => lang('report.total_loss'),
				'formatter' => 'currencyFormatter',
			),
			array(
				'dt' => $end_balance_column,
				'alias' => 'end_balance',
				'select' => 'transactions_daily_summary_report.end_balance',
				'name' => lang('report.end_balance'),
				'formatter' => 'currencyFormatter',
			),
			array(
				'dt' => $latest_balance_record_column,
				'alias' => 'latest_balance_record',
				'select' => 'transactions_daily_summary_report.latest_balance_record',
				'name' => lang('report.latest_balance_record'),
				'formatter' => function($d, $row) use ($is_export){
						if($d){
							$balanceDetails = json_decode($d,true);
							return $this->data_tables->currencyFormatter($balanceDetails['total_balance']);
						}else{
							return lang('N/A');
						}
				},
			),
			array(
				'dt' => $balance_validation_column,
				'alias' => 'balance_validation',
				'select' => 'transactions_daily_summary_report.balance_validation',
				'name' => lang('report.latest_balance_validation'),
				'formatter' => function($d, $row) use ($is_export){
						if($d){
							return $d == "Tallied" ? lang('report.tallied') :
							lang('report.not_tallied');
						}else{
							return lang('N/A');
						}
				},
			),
		);

		$result['cols_names_aliases'] = $this->get_dt_column_names_and_aliases($columns);

		# END DEFINE COLUMNS #################################################################################################################################################

		$table = 'transactions_daily_summary_report';
		$joins = array(
			'player' => "transactions_daily_summary_report.player_id = player.playerId",
			'affiliates' => 'affiliates.affiliateId = player.affiliateId',
		);

		# START PROCESS SEARCH FORM #################################################################################################################################################
		$where = array();
		$values = array();
		// $request = $this->input->post();
		$input = $this->data_tables->extra_search($request);
		// $this->utils->debug_log('input', $input);

		if (isset($input['by_transaction_date'])) {
			$where[] = "transactions_daily_summary_report.sync_date = ?";
			$values[] = $input['by_transaction_date'];
		}

		if (isset($input['by_balance_validation'])) {
			$where[] = "transactions_daily_summary_report.balance_validation = ?";
			$values[] = $input['by_balance_validation'];
		}

		if (isset($input['by_username'])) {
			$where[] = "player.username LIKE ?";
			$values[] = '%' . $input['by_username'] . '%';
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
		$result = $this->data_tables->get_data($request, $columns, $table, $where, $values, $joins);

		// $this->utils->debug_log($result);
		if($is_export){
		    //drop result if export
			return $csv_filename;
		}

		return $result;
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

	public function player_roulette_report($request, $is_export = false, $player_id = null) {

		$readOnlyDB = $this->getReadOnlyDB();

		$this->load->library('data_tables', array("DB" => $readOnlyDB));
		$this->load->model(array('roulette_api_record', 'player_model'));

		$this->data_tables->is_export = $is_export;

		$enabled_roulette_transactions = $this->utils->getConfig('enabled_roulette_transactions');

		$input = $this->data_tables->extra_search($request);
		$where = [];
		$values = [];
		$having = [];
		$group_by = [];
		$i = 0;
		$na = $is_export ? lang('lang.norecyet') : '<i class="text-muted">' . lang('lang.norecyet') . '</i>';

		$columns = array();
			$columns[] =  array(
				'alias' => 'player_id',
				'select' => 'roulette_api_record.player_id',
			);
			// 0 - regdate
			$columns[] = array(
				'dt' => $i++,
				'alias' => 'created_at',
				'select' => 'roulette_api_record.created_at',
				'name' => lang("roulette_report.datetime"),
			);
			$columns[] = array(
				'dt' => $i++,
				'alias' => 'username',
				'select' => 'player.username',
				'name' => lang('Username'),
				'formatter' => function ($d, $row) use ($is_export,$na) {
					if ($is_export) {
						return ($d ? $d : $na);
					} else {
						return '<i class="fa fa-user" ></i> ' . ($d ? '<a href="/player_management/userInformation/' . $row['player_id'] . '" target="_blank">' . $d . '</a>' : '<i class="text-muted">' . $na . '</i>');
					}
				}
			);
			if ($this->utils->getConfig('enable_show_and_search_affiliate_field')) {
	        	$columns[] = array(
					'dt' => $i++,
					'alias' => 'affiliates',
					'select' => 'affiliates.username',
					'name' => lang('Affiliate'),
					'formatter' => function ($d, $row) use ($is_export,$na) {
						return !empty($d) ? $d : $na;
					},
				);
	        }
			// $columns[] = array(
			// 	'dt' => $i++,
			// 	'alias' => 'deposit_amount',
			// 	'select' => 'roulette_api_record.deposit_amount',
			// 	'name' => lang("roulette_report.deposit_amount"),
			// );
			$columns[] = array(
				'dt' => $i++,
				'select' => 'promocmssetting.promoName',
				'alias' => 'promoTitle',
				'name' => lang('cms.promotitle'),
				'formatter' => function ($d, $row) use ($is_export, $na) {

					if (empty($d)) {
						return $na;
					}

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
			);
			$columns[] = array(
				'dt' => $i++,
				'alias' => 'type',
				'select' => 'roulette_api_record.type',
				'name' => lang("roulette_report.roulette_name"),
				'formatter' => function ($d, $row) use ($is_export) {

					$r_name = '<b class="text-success">' .lang('roulette_name_'.$d).'</b>';

                    if ($is_export) {
						return lang('roulette_name_'.$d);
                    }
                    return $r_name;
				}
			);
			$columns[] = array(
				'dt' => $i++,
				'alias' => 'updated_at',
				'select' => 'roulette_api_record.updated_at',
				'name' => lang("roulette_report.prize_release_time"),
			);
			$columns[] = array(
				'dt' => $i++,
				'alias' => 'prize',
				'select' => 'roulette_api_record.prize',
				'name' => lang("roulette_report.prize"),
				'formatter' => function ($d, $row) use ($is_export) {

					$prize = json_decode($d,true);
					$prize_name = '<span class="text-success">' .lang($prize['prize']).'</span>';

                    if ($is_export) {
						return $prize['prize'];
                    }
                    return $prize_name;
				}
			);
			$columns[] = array(
				'dt' => $i++,
				'alias' => 'total_times',
				'select' => 'roulette_api_record.total_times',
				'name' => lang('roulette_report.spin_limit'),
				'formatter' => function ($d, $row) use ($is_export,$na) {
					return !empty($d) ? $d : $na;
				}
			);
			$columns[] = array(
				'dt' => $i++,
				'alias' => 'used_times',
				'select' => 'roulette_api_record.used_times',
				'name' => lang('roulette_report.spin_count'),
				'formatter' => function ($d, $row) use ($is_export,$na) {
					return !empty($d) ? $d : $na;
				}
			);
			$columns[] = array(
				'alias' => 'playerpromoId',
				'select' => 'playerpromo.playerpromoId',
			);
			$columns[] = array(
				'alias' => 'promoCmsSettingId',
				'select' => 'playerpromo.promoCmsSettingId',
			);
			$columns[] = array(
				//get WC by transaction
				'alias' => 'condition_amount',
				'select' => 'withdraw_conditions.condition_amount',
			);
			$columns[] = array(
				//get WC by playerpromo
				'alias' => 'withdrawConditionAmount',
				'select' => 'playerpromo.withdrawConditionAmount',
			);
			$columns[] = array(
				'dt' => $i++,
				'select' => 'roulette_api_record.id',
				'alias' => 'rid',
				'name' => lang('Withdraw Condition'),
				'formatter' => function ($d, $row) use ($is_export,$na) {
					$wc_by_playerpromo = $row['withdrawConditionAmount'];
					$wc_by_transaction = $row['condition_amount'];

					$res = empty($wc_by_playerpromo) ? empty($wc_by_transaction) ? $na : $wc_by_transaction : $wc_by_playerpromo;
					if (!$is_export) {
						return '<strong>' . $res . '</strong>';
					} else {
						return $res;
					}
				}
			);
			$columns[] = array(
				'dt' => $i++,
				'alias' => 'notes',
				'select' => 'roulette_api_record.notes',
				'name' => lang('roulette_report.note'),
				'formatter' => function ($d, $row) use ($is_export,$na) {
					return !empty($d) ? $d : $na;
				}
			);
			$columns[] = array(
				'dt' => $i++,
				'alias' => 'amount',
				'select' => 'roulette_api_record.bonus_amount',
				'name' => lang("Amount"),
			);

		$this->utils->debug_log(__METHOD__, 'columns', $columns);

		$table = 'roulette_api_record';
		$joins = array(
			'player' => 'player.playerId = roulette_api_record.player_id',
			'playerpromo' => 'playerpromo.playerpromoId = roulette_api_record.player_promo_id',
			'promocmssetting' => 'promocmssetting.promoCmsSettingId = playerpromo.promoCmsSettingId',
			'withdraw_conditions' => 'withdraw_conditions.source_id = roulette_api_record.transaction_id',
			'affiliates' => 'affiliates.affiliateId = player.affiliateId'
		);

		if (isset($input['by_date_from'], $input['by_date_to'])) {
			$where[] = "roulette_api_record.created_at >=?";
			$where[] = "roulette_api_record.created_at <=?";
			$values[] = $input['by_date_from'];
			$values[] = $input['by_date_to'];
		}

		if (isset($input['dateRangeValueStart'], $input['dateRangeValueEnd'])) {
			$where[] = "roulette_api_record.created_at >=?";
			$where[] = "roulette_api_record.created_at <=?";
			$values[] = $input['dateRangeValueStart'];
			$values[] = $input['dateRangeValueEnd'];
		}

        if (isset($input['by_username'])) {
			$where[] = "player.username = ?";
			$values[] = $input['by_username'];
		}

		if (isset($input['promoCmsSettingId'])) {
			$where[] = "playerpromo.promoCmsSettingId = ?";
			$values[] = $input['promoCmsSettingId'];
		}


		if (isset($input['by_roulette_name'])) {
			$where[] = "roulette_api_record.type = ?";
			$values[] = $input['by_roulette_name'];
		}

		if (isset($input['by_product_id'])) {
			if ($input['by_product_id'] != 'allStatus') {
			$where[] = "roulette_api_record.product_id = ?";
			$values[] = $input['by_product_id'];
			}
		}

		if (isset($input['by_affiliate'])) {
			$where[] = "affiliates.username = ?";
			$values[] = $input['by_affiliate'];
		}

		if (!empty($player_id)) {
			$where[] = "roulette_api_record.player_id = ?";
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

		$summary = $this->data_tables->summary($request, $table, $joins, 'SUM(roulette_api_record.bonus_amount) total_amount', null, $columns, $where, $values);

		$result['summary'][0]['total_amount'] = $this->utils->formatCurrencyNoSym($summary[0]['total_amount']);

		return $result;
	}
	
}

////END OF FILE

