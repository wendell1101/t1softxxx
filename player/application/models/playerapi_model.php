<?php

require_once dirname(__FILE__) . '/base_model.php';
/**
 * Model for player reports used by Api_common (player center API; Common API)
 *
 * @used-by	Api_common::getPlayerReports()
 * @see		Api_common::getPlayerReports()
 */
class Playerapi_model extends BaseModel {

    protected $withdraw_statuses;
    protected $deposit_pay_types;

    const PLAYER_ACCOUNTS_DEPOSIT       = 100;
    const PLAYER_ACCOUNTS_WITHDRAWAL    = 101;

    public function __construct() {
        parent::__construct();
        $this->load->library(['playerapi_lib']);
        $this->load->model(['payment_account', 'sale_order']);
        $this->withdraw_statuses = [
            'request'   => lang('Request') ,
            'approved'  => lang('Approved') ,
            'declined'  => lang('Declined') ,
            'paid'      => lang('Paid') ,
            '__default' => lang('Processing') ,
        ];

        $this->deposit_pay_types = [
            Payment_account::FLAG_MANUAL_LOCAL_BANK => lang('pay.local_bank_offline') ,
            Payment_account::FLAG_MANUAL_ONLINE_PAYMENT => lang('pay.manual_online_payment') ,
            Payment_account::FLAG_AUTO_ONLINE_PAYMENT => lang('pay.auto_online_payment')
        ];
    }

    public function getPaymentRequestsByPlayerId($player_id, $time_start, $time_end, $limit, $sort, $status, $page) {
        $result = $this->getDataWithAPIPagination('sale_orders', function() use($player_id, $time_start, $time_end, $status, $sort){
            $subquery_content = "SELECT playerId, GROUP_CONCAT(playertag.tagId) as tagId, GROUP_CONCAT(tag.tagName) as tagName
                                FROM playertag LEFT JOIN tag on tag.tagId = playertag.tagId GROUP BY playerId";

            $this->db
                // ->from('sale_orders')
                ->join('player', 'player.playerId = sale_orders.player_id', 'left')
                ->join("($subquery_content) tmp_tag", "tmp_tag.playerId = sale_orders.player_id", 'left')
                ->join('payment_account', 'payment_account.id = sale_orders.payment_account_id', 'left')
                ->join('playerdetails', 'playerdetails.playerId = sale_orders.player_id', 'left')
                ->select([
                    'sale_orders.id',
                    'sale_orders.secure_id as secureId',
                    'sale_orders.amount',
                    'sale_orders.processed_approved_time as approvalDate',
                    'sale_orders.timeout_at as expirationDate',
                    'sale_orders.system_id',
                    'sale_orders.external_order_id as externalUid',
                    // 'sale_orders.locked_user_id',
                    // 'player.username as locked_user_name',
                    'payment_account.external_system_id as paymentApiId',
                    'payment_account.id as paymentMethod_id',
                    'payment_account.payment_account_name as paymentMethod_name',
                    'player.playerId as player_id',
                    'player.createdOn as player_createdAt',
                    'player.username as player_user_name',
                    'playerdetails.contactNumber as player_phone_number',
                    'tmp_tag.tagId as player_tag_id_str',
                    'tmp_tag.tagName as player_tag_name_str',
                    'sale_orders.amount as realAmount',
                    'sale_orders.created_at as requestedDate',
                    'sale_orders.updated_at as updatedAt',
                    'sale_orders.status as status'
                ])
                ->where('player.playerId', $player_id)
                ->where('sale_orders.created_at >= ', $time_start)
                ->where('sale_orders.created_at <= ', $time_end);
                if (!empty($status)) {
                    if(is_array($status)){
                        $this->db->where_in('sale_orders.status', $status);
                    }else{
                        $this->db->where('sale_orders.status', $status);
                    }
                }
                $this->db->order_by('sale_orders.created_at', $sort);

                // $rows = $this->runMultipleRowArray();
                // $this->utils->debug_log(__METHOD__, 'sql', $this->db->last_query());
                // $this->utils->debug_log(__METHOD__, 'sql result', $rows);

            }, $limit, $page);

            return $result;
        }

        public function getDepositRequestByOrderId($order_id) {
            $subquery_content = "SELECT playerId, GROUP_CONCAT(playertag.tagId) as tagId, GROUP_CONCAT(tag.tagName) as tagName FROM playertag LEFT JOIN tag on tag.tagId = playertag.tagId GROUP BY playerId";

            $this->db
                ->from('sale_orders')
                ->join('player', 'player.playerId = sale_orders.player_id', 'left')
                ->join("($subquery_content) tmp_tag", "tmp_tag.playerId = sale_orders.player_id", 'left')
                ->join('payment_account', 'payment_account.id = sale_orders.payment_account_id', 'left')
                ->join('playerdetails', 'playerdetails.playerId = sale_orders.player_id', 'left')
                ->select([
                    'sale_orders.id',
                    'sale_orders.secure_id as secureId',
                    'sale_orders.amount',
                    'sale_orders.processed_approved_time as approvalDate',
                    'sale_orders.timeout_at as expirationDate',
                    'sale_orders.system_id',
                    'sale_orders.external_order_id as externalUid',
                    'payment_account.external_system_id as paymentApiId',
                    'payment_account.id as paymentMethod_id',
                    'payment_account.payment_account_name as paymentMethod_name',
                    'player.playerId as player_id',
                    'player.createdOn as player_createdAt',
                    'player.username as player_user_name',
                    'playerdetails.contactNumber as player_phone_number',
                    'tmp_tag.tagId as player_tag_id_str',
                    'tmp_tag.tagName as player_tag_name_str',
                    'sale_orders.amount as realAmount',
                    'sale_orders.created_at as requestedDate',
                    'sale_orders.updated_at as updatedAt',
                    'sale_orders.status as status'
                ])
                ->where('sale_orders.id', $order_id);

            $row = $this->runOneRowArray();
            $this->utils->debug_log(__METHOD__, 'sql', $this->db->last_query());
            $this->utils->debug_log(__METHOD__, 'sql result', $row);
            return $row;
    }

    public function getUnapprovedDepositAmountByPlayerId($player_id) {
        $this->from('sale_orders')
            ->select('sum(amount) AS amount')
            ->where('status', Sale_order::STATUS_PROCESSING)
            ->where('player_id', $player_id);

        $count = $this->runOneRowOneField('amount');
        return $count;
    }

    public function getWithdrawalConditionsByPlayerId($player_id, $time_start=null, $time_end, $limit, $sort, $page, $status) {
        $result = $this->getDataWithAPIPagination('withdraw_conditions', function() use($player_id, $time_start, $time_end, $sort, $status) {
            $this->db
                ->select([
                    'withdraw_conditions.id',
                    'withdraw_conditions.source_type sourceType',
                    'promorules.promoName promoName',
                    'withdraw_conditions.deposit_amount depositAmount',
                    'withdraw_conditions.bonus_amount bonusAmount',
                    'withdraw_conditions.started_at startedAt',
                    'withdraw_conditions.condition_amount withdrawConditionAmount',
                    'withdraw_conditions.bet_amount betAmount',
                    'withdraw_conditions.is_finished status',
                    'quest_manager.title singleQuestTitle',
                    'quest_job.title multiQuestTitle',
                ]);
            $this->db->join('promorules', 'promorules.promorulesId = withdraw_conditions.promotion_id', 'left');
            $this->db->join('player_quest_job_state', 'player_quest_job_state.withdrawConditionId = withdraw_conditions.id', 'left');
            $this->db->join('quest_manager', 'quest_manager.questManagerId = player_quest_job_state.questManagerId', 'left');
            $this->db->join('quest_job', 'quest_job.questJobId = player_quest_job_state.questJobId', 'left');

            if(!is_null($time_start)) {
                $this->db->where('withdraw_conditions.started_at >= ', $time_start);
            }
            $this->db->where('withdraw_conditions.started_at <= ', $time_end);

            if (!is_null($status)) {
                $this->db->where('withdraw_conditions.is_finished', $status);
            }

            $this->db->where('withdraw_conditions.player_id', $player_id);
            $this->db->where('withdraw_conditions.status', self::STATUS_NORMAL);
            $this->db->where('withdraw_condition_type', Withdraw_condition::WITHDRAW_CONDITION_TYPE_BETTING);
            $this->db->order_by('withdraw_conditions.started_at', $sort);

        }, $limit, $page);
        if( !empty($result['list']) ){ // adjustment in _SYSTEM_MANUAL
            foreach($result['list'] as &$_row){
                if( isset($_row['promoName']) ){
                    if($_row['promoName'] == Promorules::SYSTEM_MANUAL_PROMO_CMS_NAME){
                        $_row['promoName'] = lang('promo.'. $_row['promoName']);
                    }
                }
            }
        }
        $this->utils->printLastSQL();
        return $result;
    }

    public function getWithdrawalRequestsByPlayerId($player_id, $time_start=null, $time_end, $limit, $sort, $status, $page) {
        //add debut log
        $this->utils->debug_log(__METHOD__, 'player_id', $player_id, 'time_start', $time_start, 'time_end', $time_end, 'limit', $limit, 'sort', $sort, 'status', $status, 'page', $page);
        $subquery_content = "SELECT playerId, GROUP_CONCAT(playertag.tagId) as tagId, GROUP_CONCAT(tag.tagName) as tagName FROM playertag LEFT JOIN tag on tag.tagId = playertag.tagId GROUP BY playerId";

        $result = $this->getDataWithAPIPagination('walletaccount', function() use($player_id, $time_start, $time_end, $sort, $status, $subquery_content) {
            $this->db
                ->select([
                    'walletaccount.walletAccountId as id ',
                    'walletaccount.amount as amount',
                    'walletaccount.processDatetime',
                    'walletaccount.bankAccountNumber as bankAccount_accountNumber',
                    'walletaccount.bankAccountFullName as bankAccount_accountHolderName',
                    'walletaccount.bankName as bankAccount_bankName',
                    'playerbankdetails.bankTypeId as bankId',
                    'player.playerId as player_id',
                    'player.createdOn as player_createdAt',
                    'player.username as player_user_name',
                    'playerdetails.contactNumber as player_phone_number',
                    'tmp_tag.tagId as player_tag_id_str',
                    'tmp_tag.tagName as player_tag_name_str',
                    'walletaccount.dwDateTime as requestedDate',
                    'walletaccount.processDatetime as updatedAt',
                    'walletaccount.withdrawal_fee_amount as withdrawCharge',
                    'walletaccount.dwStatus',
                    'walletaccount.transactionCode as withdrawalCode',
                    'walletaccount.withdrawal_bank_fee as withdrawBankCharge',
                ]);
                $this->db->join('player', 'player.playerId = walletaccount.playerId', 'left');
                $this->db->join("($subquery_content) tmp_tag", "tmp_tag.playerId = walletaccount.playerId", 'left');
                $this->db->join('playerbankdetails', 'playerbankdetails.playerBankDetailsId = walletaccount.player_bank_details_id', 'left');
                $this->db->join('playerdetails', 'playerdetails.playerId = walletaccount.playerId', 'left');
                $this->db->where('player.playerId', $player_id);
                if(!is_null($time_start)) {
                    $this->db->where('walletaccount.dwDateTime >= ', $time_start);
                }

                if (!is_null($status)) {
                    if(is_array($status)){
                        $this->db->where_in('walletaccount.dwStatus', $status);
                    }else{
                        $this->db->where('walletaccount.dwStatus', $status);
                    }
                }

                $this->db->where('walletaccount.dwDateTime <= ', $time_end);
                $this->db->order_by('walletaccount.dwDateTime', $sort);
        }, $limit, $page);
        $this->utils->debug_log(__METHOD__, 'sql', $this->db->last_query());
        $this->utils->debug_log(__METHOD__, 'sql result', $result);

        return $result;
    }

    public function getUnapprovedWithdrawalAmountByPlayerId($player_id) {
        $this->from('walletaccount')
            ->select('sum(amount) AS amount')
            ->where_not_in('status', [Wallet_model::PAID_STATUS, Wallet_model::DECLINED_STATUS])
            ->where('playerId', $player_id);

        $amount = $this->runOneRowOneField('amount');
        return $amount;
    }

    public function getPlayerProfileByPlayerId($player_id) {
        $subquery_content = "SELECT playerId, GROUP_CONCAT(playertag.tagId) as tagId, GROUP_CONCAT(tag.tagName) as tagName FROM playertag LEFT JOIN tag on tag.tagId = playertag.tagId GROUP BY playerId";
        $this->db
            ->from('player')
            ->join("($subquery_content) tmp_tag", "tmp_tag.playerId = player.playerId", 'left')
            ->join('playerdetails', 'playerdetails.playerId = player.playerId', 'left')
            ->select([
                // 'player.playerId',
                'player.username',
                'player.password',
                'player.levelId as vipCode',
                'IF(player.disabled_promotion, 0, 1) as campaignEnabled',
                'IF(player.disabled_cashback, 0, 1) as cashbackEnabled',
                'player.enabled_withdrawal as withdrawEnabled',
                'playerdetails.citizenship as countryCode',
                'playerdetails.city',
                'playerdetails.address',
                'playerdetails.address2',
                'playerdetails.address3',
                'playerdetails.birthdate as birthday',
                'playerdetails.firstName',
                'playerdetails.lastName',
                '(CASE playerdetails.gender WHEN "Male" THEN "M" WHEN "Female" THEN "F" ELSE "" END) as gender',
                'playerdetails.language',
                'playerdetails.dialing_code as countryPhoneCode',
                'playerdetails.contactNumber as phoneNumber',
                'playerdetails.imAccount as im1',
                'playerdetails.imAccount2 as im2',
                'playerdetails.imAccount3 as im3',
                'player.invitationCode',
                'player.refereePlayerId as referer_player_id',
                'player.createdOn as createdAt',
                '(CASE player.status WHEN 0 THEN 1 ELSE 2 END) as status',
                'player.email as email',
                'player.verified_email as emailVerified',
                'playerdetails.pix_number as cpfNumber',
                'player.withdraw_password as withdrawalPasswordExists',
                'player.verified_phone as phoneVerified',
            ])
            ->where('player.playerId', $player_id);

        $row = $this->runOneRowArray();
        $this->utils->debug_log(__METHOD__, 'sql', $this->db->last_query());
        $this->utils->debug_log(__METHOD__, 'sql result', $row);
        return $row;
    }

    public function getPlayerInfoByPlayerId($player_id) {
        $this->db
            ->from('player')
            ->join('playerdetails', 'playerdetails.playerId = player.playerId', 'left')
            ->join('playerlevel', 'playerlevel.playerId = player.playerId', 'left')
            ->join('vipsettingcashbackrule', 'vipsettingcashbackrule.vipsettingcashbackruleId = playerlevel.playerGroupId', 'left')
            ->join('vipsetting', 'vipsetting.vipSettingId = vipsettingcashbackrule.vipSettingId', 'left')
            ->select([
                'player.playerId as id',
                'player.username',
                'player.email',
                '(CASE player.status WHEN 0 THEN 1 ELSE 2 END) as status',
                'player.lastLoginTime as lastLoginTime',
                'player.lastLoginIp as lastLoginIp',
                'playerlevel.playerGroupId as vip_level_id',
                'vipsetting.groupName as vip_group_name',
                'vipsettingcashbackrule.vipLevelName as vip_level_name',
                'vipsettingcashbackrule.badge',
                'player.verified_phone as phoneVerified',
                'player.verified_email as emailVerified',
                '(player.withdraw_password IS NOT NULL) as withdrawPasswordExists',
                '(CASE WHEN player.secretQuestion IS NOT NULL AND LTRIM(RTRIM(player.secretQuestion)) != "" THEN 1 ELSE 0 END) as passwordQuestionExists'
            ])
            ->where('player.playerId', $player_id);

        $row = $this->runOneRowArray();
        $this->utils->debug_log(__METHOD__, 'sql', $this->db->last_query());
        $this->utils->debug_log(__METHOD__, 'sql result', $row);
        return $row;
    }

    public function getUsernameByContactInfo($contractInfo){
        if (!empty($contractInfo)) {
            $this->db
            ->from('player')
            ->join('playerdetails', 'playerdetails.playerId = player.playerId', 'left')
            ->select([ 'player.playerId',
                       'player.username'
                    ])
            ->where('player.deleted_at', NULL);
            if(!empty($contractInfo['email'])){
                $this->db->where('player.email', $contractInfo['email']);
            }
            if(!empty($contractInfo['contactNumber'])){
                $this->db->where('playerdetails.contactNumber', $contractInfo['contactNumber']);   
            }
            $rows = $this->runMultipleRowArray();
            return $rows;
		}
		return null;
    }

    public function getPlayerStatsByPlayerId($player_id) {
        $result = [
            'firstDepositDate' => $this->getPlayerFirstDepositDate($player_id),
            'firstWithdrawDate' => $this->getPlayerFirstWithdrawDate($player_id),
            'lastDepositDate' => $this->getPlayerLastDepositDate($player_id),
            'lastWithdrawDate' => $this->getPlayerLastWithdrawDate($player_id),
            'totalCampaignBonus' => $this->getPlayerTotalPromotionBonus($player_id),
            'totalCashbackBonus' => $this->getPlayerTotalCashbackBonus($player_id),
            'totalReferralBonus' => $this->getPlayerTotalReferralBonus($player_id),
            'totalDepositAmount' => $this->getPlayerTotalDepositAmount($player_id),
            'totalDepositCount' => $this->getPlayerTotalDepositCount($player_id),
            'totalWithdrawAmount' => $this->getPlayerTotalWithdrawAmount($player_id),
            'totalWithdrawCount' => $this->getPlayerTotalWithdrawCount($player_id),
        ];
        return $result;
    }

    public function getPlayerFirstDepositDate($player_id) {
        $this->db
            ->from('transactions')
            ->join('player', 'player.playerId = transactions.to_id', 'left')
            ->select('transactions.created_at AS firstDepositDate')
            ->where('transactions.transaction_type', Transactions::DEPOSIT)
            ->where('transactions.status ', Transactions::APPROVED)
            ->where('transactions.to_type', Transactions::PLAYER)
            ->where('player.deleted_at', NULL)
            ->where('transactions.to_id', $player_id)
            ->order_by('transactions.created_at', 'ASC')
            ->limit(1);
        $first_deposit_date = $this->runOneRowOneField('firstDepositDate');
        return $first_deposit_date;
    }

    public function getPlayerFirstWithdrawDate($player_id) {
        $this->db
            ->from('transactions')
            ->join('player', 'player.playerId = transactions.to_id', 'left')
            ->select('transactions.created_at AS firstWithdrawDate')
            ->where('transactions.transaction_type', Transactions::WITHDRAWAL)
            ->where('transactions.status ', Transactions::APPROVED)
            ->where('transactions.to_type', Transactions::PLAYER)
            ->where('player.deleted_at', NULL)
            ->where('transactions.to_id', $player_id)
            ->order_by('transactions.created_at', 'ASC')
            ->limit(1);
        $first_withdraw_date = $this->runOneRowOneField('firstWithdrawDate');
        return $first_withdraw_date;
    }

    public function getPlayerLastDepositDate($player_id) {
        $this->db
            ->from('transactions')
            ->join('player', 'player.playerId = transactions.to_id', 'left')
            ->select('transactions.created_at AS lastDepositDate')
            ->where('transactions.transaction_type', Transactions::DEPOSIT)
            ->where('transactions.status ', Transactions::APPROVED)
            ->where('transactions.to_type', Transactions::PLAYER)
            ->where('player.deleted_at', NULL)
            ->where('transactions.to_id', $player_id)
            ->order_by('transactions.created_at', 'DESC')
            ->limit(1);
        $last_deposit_date = $this->runOneRowOneField('lastDepositDate');
        return $last_deposit_date;
    }

    public function getPlayerLastWithdrawDate($player_id) {
        $this->db
            ->from('transactions')
            ->join('player', 'player.playerId = transactions.to_id', 'left')
            ->select('transactions.created_at AS lastWithdrawDate')
            ->where('transactions.transaction_type', Transactions::WITHDRAWAL)
            ->where('transactions.status ', Transactions::APPROVED)
            ->where('transactions.to_type', Transactions::PLAYER)
            ->where('player.deleted_at', NULL)
            ->where('transactions.to_id', $player_id)
            ->order_by('transactions.created_at', 'DESC')
            ->limit(1);
        $last_withdraw_date = $this->runOneRowOneField('lastWithdrawDate');
        return $last_withdraw_date;
    }

    public function getPlayerTotalPromotionBonus($player_id) {
        $this->db
            ->from('transactions')
            ->select('SUM(amount) AS totalCampaignBonus')
            ->where_in('transactions.transaction_type', array(Transactions::MEMBER_GROUP_DEPOSIT_BONUS, Transactions::PLAYER_REFER_BONUS, Transactions::ADD_BONUS))
            ->where('transactions.to_id', $player_id);
        $total_promotion_bonus = $this->runOneRowOneField('totalCampaignBonus');
        $total_promotion_bonus = !empty($total_promotion_bonus) ? $total_promotion_bonus : 0;
        return $total_promotion_bonus;
    }

    public function getPlayerTotalCashbackBonus($player_id) {
        $this->db
            ->from('transactions')
            ->select('SUM(amount) AS totalCashbackBonus')
            ->where('transactions.transaction_type', Transactions::AUTO_ADD_CASHBACK_TO_BALANCE)
            ->where('transactions.to_id', $player_id);
        $total_cashback_bonus = $this->runOneRowOneField('totalCashbackBonus');
        $total_cashback_bonus = !empty($total_cashback_bonus) ? $total_cashback_bonus : 0;
        return $total_cashback_bonus;
    }

    public function getPlayerTotalReferralBonus($player_id) {
        $this->db
            ->from('transactions')
            ->select('SUM(amount) AS totalReferralBonus')
            ->where('transactions.transaction_type', Transactions::PLAYER_REFER_BONUS)
            ->where('transactions.to_id', $player_id);
        $total_referral_bonus = $this->runOneRowOneField('totalReferralBonus');
        $total_referral_bonus = !empty($total_referral_bonus) ? $total_referral_bonus : 0;
        return $total_referral_bonus;
    }

    public function getPlayerTotalDepositAmount($player_id) {
        $this->db
            ->from('transactions')
            ->select('SUM(amount) AS totalDepositAmount')
            ->where('transactions.transaction_type', Transactions::DEPOSIT)
            ->where('transactions.status ', Transactions::APPROVED)
            ->where('transactions.to_type', Transactions::PLAYER)
            ->where('transactions.to_id', $player_id);
        $total_deposit_amount = $this->runOneRowOneField('totalDepositAmount');
        $total_deposit_amount = !empty($total_deposit_amount) ? $total_deposit_amount : 0;
        return $total_deposit_amount;
    }

    public function getPlayerTotalDepositCount($player_id) {
        $this->db
            ->from('transactions')
            ->select('COUNT(id) AS totalDepositCount')
            ->where('transactions.transaction_type', Transactions::DEPOSIT)
            ->where('transactions.status ', Transactions::APPROVED)
            ->where('transactions.to_type', Transactions::PLAYER)
            ->where('transactions.to_id', $player_id);
        $total_deposit_count = $this->runOneRowOneField('totalDepositCount');
        return $total_deposit_count;
    }

    public function getPlayerTotalWithdrawAmount($player_id) {
        $this->db
            ->from('transactions')
            ->select('SUM(amount) AS totalWithdrawAmount')
            ->where('transactions.transaction_type', Transactions::DEPOSIT)
            ->where('transactions.status ', Transactions::APPROVED)
            ->where('transactions.to_type', Transactions::PLAYER)
            ->where('transactions.to_id', $player_id);
        $total_withdraw_amount = $this->runOneRowOneField('totalWithdrawAmount');
        $total_withdraw_amount = !empty($total_withdraw_amount) ? $total_withdraw_amount : 0;
        return $total_withdraw_amount;
    }

    public function getPlayerTotalWithdrawCount($player_id) {
        $this->db
            ->from('transactions')
            ->select('COUNT(id) AS totalWithdrawCount')
            ->where('transactions.transaction_type', Transactions::DEPOSIT)
            ->where('transactions.status ', Transactions::APPROVED)
            ->where('transactions.to_type', Transactions::PLAYER)
            ->where('transactions.to_id', $player_id);
        $total_withdraw_count = $this->runOneRowOneField('totalWithdrawCount');
        return $total_withdraw_count;
    }

    public function getAnnouncements($sort, $limit, $page, $lang = null) {
        $nowDate = date("Y-m-d H:i:s");
        $result = $this->getDataWithAPIPagination('cmsnews', function() use($nowDate, $sort, $lang) {
            $this->db->select([
                    'cmsnews.newsId as id',
                    'cmsnews.title',
                    'cmsnews.start_date as startAt',
                    'cmsnews.end_date as endAt',
                    'cmsnews.date as createdAt',
                    'cmsnews.up_date as updatedAt',
                ]);

            if (!empty($lang)) {
                $this->db->join('cmsnewscategory', 'cmsnewscategory.id = cmsnews.categoryId', 'left');
                $this->db->where('cmsnewscategory.language', $lang);
            }
            $defaultSearchCondition = "cmsnews.is_daterange = 0 OR ( ";
            $defaultSearchCondition .= "cmsnews.is_daterange = 1 and ";
            $defaultSearchCondition .= "cmsnews.start_date <= '$nowDate' and ";
            $defaultSearchCondition .= "cmsnews.end_date   >= '$nowDate' )";
            $this->db->where($defaultSearchCondition);
            $this->db->order_by('cmsnews.date', $sort);

        }, $limit, $page);
        $this->utils->debug_log(__METHOD__, 'sql', $this->db->last_query());
        $this->utils->debug_log(__METHOD__, 'sql result', $result);
        return $result;
    }

    public function getAnnouncementByNewsId($news_id) {
        $this->db
            ->from('cmsnews')
            ->select([
                'cmsnews.newsId as id',
                'cmsnews.title',
                'cmsnews.content',
                'cmsnews.start_date as startAt',
                'cmsnews.end_date as endAt',
                'cmsnews.date as createdAt',
                'cmsnews.up_date as updatedAt',
            ])
            ->where('cmsnews.newsId', $news_id);
        $row = $this->runOneRowArray();
        // $this->utils->debug_log(__METHOD__, 'sql', $this->db->last_query());
        // $this->utils->debug_log(__METHOD__, 'sql result', $row);
        return $row;
    }

    public function getPlayerAllMessagesDetails($player_id, $message_id = null, $date_start = null, $date_end = null) {
        $this->db->select([
            'messagesdetails.messageDetailsId as id',
            'messages.messageId as threadId',
            'messages.subject',
            'messagesdetails.message as content',
            'messages.playerId',
            'player.username as playerUsername',
            'messages.adminId as operatorId',
            'adminusers.username as operatorUsername',
            'IF(messagesdetails.adminId > 0 , messages.adminId, messages.playerId) as sender',
            'IF(messagesdetails.adminId > 0 , true, false) as system',
            'messagesdetails.date as createdAt',
            'messagesdetails.date as updatedAt',
            'messagesdetails.status as "read"',
        ]);
        $this->db->from('messagesdetails');
        $this->db->join('messages', 'messages.messageId = messagesdetails.messageId', 'left');
        $this->db->join('player', 'player.playerId = messages.playerId', 'left');
        $this->db->join('adminusers', 'adminusers.userId = messagesdetails.adminId', 'left');
        $this->db->where('messages.playerId', $player_id);
        $this->db->where('messages.deleted', 0);

        if(!is_null($message_id)) {
            $this->db->where('messages.messageId', $message_id);
        }
        if(!is_null($date_start)) {
            $this->db->where('messagesdetails.date >= ', $date_start);
        }
        if(!is_null($date_end)) {
            $this->db->where('messagesdetails.date <= ', $date_end);
        }
        $this->db->order_by('messages.messageId', 'DESC');
        $this->db->order_by('messagesdetails.date', 'ASC');
        $rows = $this->runMultipleRowArray();
        $this->utils->debug_log(__METHOD__, 'sql', $this->db->last_query());
        $this->utils->debug_log(__METHOD__, 'sql result', $rows);
        return $rows;
    }

    public function getPlayerAllMessages($player_id, $message_id = null) {
        $this->db->select([
            'messages.messageId as threadId',
            'messages.subject',
        ]);
        $this->db->from('messages');
        $this->db->where('messages.playerId', $player_id);
        if(!is_null($message_id)) {
            $this->db->where('messages.messageId', $message_id);
        }
        $rows = $this->runMultipleRowArray();
        $this->utils->debug_log(__METHOD__, 'sql', $this->db->last_query());
        $this->utils->debug_log(__METHOD__, 'sql result', $rows);
        return $rows;
    }

    public function setAllMessagesDetailsAsReadByPlayerId($player_id, $message_thread_id = null, $message_details_id = null) {
        $this->db->set('messagesdetails.status', Internal_message::MESSAGE_DETAILS_READ);
        $this->db->where('messages.playerId', $player_id);
        $this->db->where('messagesdetails.flag', 'admin');
        $this->db->where('messagesdetails.status', Internal_message::MESSAGE_DETAILS_UNREAD);
        $this->db->where('messages.admin_unread_count > ', 0);
        if(!is_null($message_thread_id)) {
            $this->db->where('messagesdetails.messageId', $message_thread_id);
        }
        if(!is_null($message_details_id)) {
            $this->db->where('messagesdetails.messageDetailsId', $message_details_id);
        }
        $this->db->update('messagesdetails LEFT JOIN messages on messages.messageId = messagesdetails.messageId');
        $this->utils->debug_log(__METHOD__, 'sql', $this->db->last_query());
        return ($this->db->affected_rows());
    }

    public function setAllMessagesAsReadByPlayerId($player_id, $message_thread_id = null, $message_details_id = null, $total_unreads_count=null) {
        $this->db->set('messages.status', Internal_message::STATUS_READ);
        if(is_null($total_unreads_count)) {
            $this->db->set('messages.admin_unread_count', 0);
        }
        else {
            $this->db->set('messages.admin_unread_count', $total_unreads_count);
        }

        $this->db->where('messages.playerId', $player_id);
        $this->db->where('messages.admin_unread_count > ', 0);
        if(!is_null($message_thread_id)) {
            $this->db->where('messagesdetails.messageId', $message_thread_id);
        }
        $this->db->update('messages LEFT JOIN messagesdetails on messagesdetails.messageId = messages.messageId');
        $this->utils->debug_log(__METHOD__, 'sql', $this->db->last_query());
        return ($this->db->affected_rows());
    }

    public function addNewMessage($player_id, $subject, $sender, $message, $drafts = FALSE) {
        $this->startTrans();

        $ticket_number = 'Ticket#' . random_string('numeric', 16);

        $messageId = $this->insertData('messages', array(
            'message_type' => Internal_message::MESSAGE_TYPE_NORMAL,
            'adminId' => Internal_message::ADMIN_ID,
            'playerId' => $player_id,
            'session' => $ticket_number,
            'subject' => $subject,
            'date' => $this->utils->getNowForMysql(),
            'is_system_message' => FALSE,
            'disabled_replay' => FALSE,
            'player_last_reply_dt' => $this->utils->getNowForMysql(),
            'player_unread_count' => 1,
            'status' => (!empty($drafts)) ? Internal_message::STATUS_DRAFTS : Internal_message::STATUS_NEW,
        ));

        if(empty($messageId)){
            $this->rollbackTrans();
            return FALSE;
        }

        $messageDetailsId = $this->addNewMessageDetail($messageId, $sender, $message);

        if(empty($messageDetailsId)){
            $this->rollbackTrans();
            return FALSE;
        }
        $this->endTransWithSucc();

        return $messageDetailsId;
    }

    public function addNewMessageDetail($messageId, $sender, $message) {
        $data = array(
            'messageId' => $messageId,
            'sender' => (empty($sender)) ? '' : $sender,
            'recipient' => '',
            'message' => $message,
            'date' => $this->utils->getNowForMysql(),
            'status' => Internal_message::MESSAGE_DETAILS_UNREAD,
            'flag' => 'player',
        );

        return $this->insertData('messagesdetails', $data);
    }

    public function getPromotionReuquetsByPlayerId($player_id, $date_start=null, $date_end=null, $promo_rule_type=null, $rule_id=null, $sort='DESC', $status=null, $promoTypes= null, $limit=null, $page=null) {
        $result = $this->getDataWithAPIPagination('playerpromo', function() use($player_id, $rule_id, $promo_rule_type, $date_start, $date_end, $status, $sort, $promoTypes) {

            $this->db->select([
                'playerpromo.playerpromoId id',
                'playerpromo.bonusAmount',
                'playerpromo.dateApply as bonusDate',
                'playerpromo.playerId',
                'player.username',
                'promorules.promoName as promoRuleName',
                'promorules.promoType',
                'playerpromo.depositAmount as referenceAmount',
                'promorules.promorulesId as ruleId',
                '(CASE playerpromo.transactionStatus WHEN 0 THEN 0 WHEN 1 THEN 1 WHEN 2 THEN 11
                                                     WHEN 3 THEN 10 WHEN 7 THEN 1 WHEN 8 THEN 10
                                                     WHEN 9 THEN 1 WHEN 10 THEN 1 WHEN 11 THEN 1
                                                     WHEN 12 THEN 1 ELSE 9999 END) as status',
                'playerpromo.withdrawConditionAmount'
            ]);
            // $this->db->from('playerpromo');
            $this->db->join('player', 'playerpromo.playerId = player.playerId', 'left');
            $this->db->join('promorules', 'playerpromo.promorulesId = promorules.promorulesId', 'left');
            $this->db->where('playerpromo.playerId', $player_id);
            if(!is_null($rule_id)) {
                $this->db->where('playerpromo.promorulesId', $rule_id);
            }
            if(!is_null($promo_rule_type)) {
                $this->db->where('promorules.promoType', $promo_rule_type);
            }
            if(!is_null($date_start)) {
                $this->db->where('playerpromo.dateApply >= ', $date_start);
            }
            if(!is_null($date_end)) {
                $this->db->where('playerpromo.dateApply <= ', $date_end);
            }
            if(!is_null($status)) {
                if(is_array($status)) {
                    $this->db->where_in('playerpromo.transactionStatus', $status);
                }
                else {
                    $this->db->where('playerpromo.transactionStatus', $status);
                }
            }
            if(!is_null($promoTypes)) {
                $this->db->where_in('promorules.promoType', $promoTypes);
            }
            // $rows = $this->runMultipleRowArray();
            // $this->utils->debug_log(__METHOD__, 'sql', $this->db->last_query());
            // $this->utils->debug_log(__METHOD__, 'sql result', $rows);
        }, $limit, $page);

        return $result;
    }

    public function getCashbackReuquetsByPlayerId($player_id, $date_start=null, $date_end=null, $sort='DESC', $status=null, $limit=null, $page=null) {
        $result = $this->getDataWithAPIPagination('total_cashback_player_game_daily', function() use($player_id, $date_start, $date_end, $status, $sort) {

            $this->db->select([
                'total_cashback_player_game_daily.id',
                'total_cashback_player_game_daily.total_date as bonusDate',
                'total_cashback_player_game_daily.amount as bonusAmount',
                'total_cashback_player_game_daily.player_id',
                'player.username',
                'total_cashback_player_game_daily.bet_amount as referenceAmount',
                'total_cashback_player_game_daily.withdraw_condition_amount as withdrawConditionAmount',
                'total_cashback_player_game_daily.paid_flag as status'
            ]);

            $this->db->join('player', 'total_cashback_player_game_daily.player_id = player.playerId', 'left');
            $this->db->where('total_cashback_player_game_daily.player_id', $player_id);
            // if(!is_null($rule_id)) {
            //     $this->db->where('playerpromo.promorulesId', $rule_id);
            // }
            // if(!is_null($promo_rule_type)) {
            //     $this->db->where('promorules.promoType', $promo_rule_type);
            // }
            if(!is_null($date_start)) {
                $this->db->where('total_cashback_player_game_daily.total_date >= ', $date_start);
            }
            if(!is_null($date_end)) {
                $this->db->where('total_cashback_player_game_daily.total_date <= ', $date_end);
            }
            if(!is_null($status)) {
                $this->db->where('total_cashback_player_game_daily.paid_flag', $status);
            }

        }, $limit, $page);

        return $result;
    }

    public function getAvailableBankTypes() {
        $this->db->select([
            'bankTypeId as id',
            'bank_code as code',
            'bankName',
            'bankIcon as icon',
            'payment_type_flag',
            '(CASE status WHEN "active" THEN 1 WHEN "inactive" THEN 0 ELSE 0 END) as enabled',
            'enabled_withdrawal',
            'enabled_deposit'
        ]);
        $this->db->from('banktype');
        $this->db->where('status', 'active');
        $this->db->where('(enabled_withdrawal = 1 OR enabled_deposit = 1)');
        $rows = $this->runMultipleRowArray();
        $this->utils->debug_log(__METHOD__, 'sql', $this->db->last_query(), 'result', $rows);
        return $rows;
    }

    function getPlayerTransactionsByPlayerId($currency, $player_id, $time_start=null, $time_end=null, $limit, $sort, $transactions_type=null, $page) {
        $result = $this->getDataWithAPIPagination('transactions', function() use($player_id, $time_start, $time_end, $sort, $transactions_type) {
            $this->db->select([
                'transactions.id',
                'transactions.amount',
                'transactions.before_balance as balanceBefore',
                'transactions.after_balance as balanceAfter',
                'transactions.created_at as createdAt',
                'transactions.external_transaction_id as externalTransactionId',
                'transactions.note',
                'transactions.from_id as operatorId',
                'adminusers.username as operatorUsername',
                'transactions.transaction_type as type',
                'transactions.status'
            ]);
            $this->db->join('adminusers', 'adminusers.userId = transactions.from_id', 'left');
            $this->db->where('transactions.to_id', $player_id);
            if(!is_null($transactions_type)) {
                if(is_array($transactions_type)) {
                    $this->db->where_in('transactions.transaction_type', $transactions_type);
                }
                else {
                    $this->db->where('transactions.transaction_type', $transactions_type);
                }
            }
            if(!is_null($time_start)) {
                $this->db->where('transactions.created_at >= ', $time_start);
            }
            if(!is_null($time_end)) {
                $this->db->where('transactions.created_at <= ', $time_end);
            }
        }, $limit, $page, $currency);
        return $result;
    }

    function getPlayerPromoByPlayerId($currency, $player_id, $time_start=null, $time_end=null, $limit, $sortKey, $sortType, $page) {
        $result = $this->getDataWithAPIPagination('playerpromo', function() use($player_id, $time_start, $time_end, $sortKey, $sortType) {
            $this->db->select([
                'playerpromo.dateApply',
                'playerpromo.promoCmsSettingId',
                'promocmssetting.promoName',
                'playerpromo.transactionStatus',
                'playerpromo.bonusAmount',
            ]);
            $this->db->join('promocmssetting', 'promocmssetting.promoCmsSettingId = playerpromo.promoCmsSettingId', 'left');
            $this->db->where('playerpromo.playerId', $player_id);
            
            if(!is_null($time_start)) {
                $this->db->where('playerpromo.dateApply >= ', $time_start);
            }
            if(!is_null($time_end)) {
                $this->db->where('playerpromo.dateApply <= ', $time_end);
            }
           
            if (!empty($sortKey) && !empty($sortType)) {
                $this->db->order_by($sortKey, $sortType);
            }

        }, $limit, $page, $currency);
        return $result;
    }

    public function getPlayerVipList($playerId) {
        $this->db->select([
            'p.levelId',
            'vscb.vipLevelName',
            'vscb.vip_upgrade_id',
            'vscb.vip_downgrade_id',
            'vscb.period_down',
            'vscb.period_up_down_2',
            'vscb.vipLevel',
            'vs.groupName',
        ]);
        $this->db->from('player as p');
        $this->db->join('vipsettingcashbackrule as vscb', 'p.levelId = vscb.vipsettingcashbackruleId', 'left');
        $this->db->join('vipsetting as vs','vscb.vipSettingId = vs.vipSettingId','left');
        $this->db->where("p.playerId", $playerId);
        $row = $this->runOneRowArray();
        return $row;
    }

    public function getGroupIdByPlayerId($playerId){
        $this->db->select(['vscb.vipSettingId']);
        $this->db->from('vipsettingcashbackrule as vscb');
        $this->db->join('player as p', 'p.levelId = vscb.vipsettingcashbackruleId', 'left');
        $this->db->where("p.playerId", $playerId);
        return $this->runOneRowOneField('vipSettingId');
    }

    public function getVipGroupInfo($vipSettingId) {
        $this->db->select([
            'vscb.vipsettingcashbackruleId',
            'vscb.vipLevelName',
            'vscb.vip_upgrade_id',
            'vscb.vip_downgrade_id',
            'vscb.period_down',
            'vscb.period_up_down_2',
            'vscb.vipLevel',
            'vs.groupName',
        ]);
        $this->db->from('vipsettingcashbackrule as vscb');
        $this->db->join('vipsetting as vs','vscb.vipSettingId = vs.vipSettingId','left');
        $this->db->where("vscb.vipSettingId", $vipSettingId);
        $rows = $this->runMultipleRowArray();
        return $rows;
    }

    public function getVipUpGroupSetting($upgradeId) {
        $this->db->select("vip_upgrade_setting.*");
        $this->db->from('vip_upgrade_setting')->where("upgrade_id",$upgradeId);
        $row = $this->runOneRowArray();
        return $row;
    }

    public function getVipGroupLevelDetails($vipLevelId) {
		$this->db->select('vipsettingcashbackrule.*,vipsetting.groupName')->from('vipsettingcashbackrule');
		$this->db->join('vipsetting', 'vipsettingcashbackrule.vipSettingId = vipsetting.vipSettingId');
		$this->db->where('vipsettingcashbackrule.vipsettingcashbackruleId', $vipLevelId);
		$row = $this->runOneRowArray();
        return $row;
	}

    public function getBankTypeByBankCode($bankCode) {
        $this->db->select([
            'banktype.bankTypeId',
            'banktype.bank_code',
            'banktype.bankName',
            'banktype.createdOn',
            'banktype.payment_type_flag',
            'banktype.enabled_withdrawal',
            'banktype.enabled_deposit',
            'banktype.status',
        ]);
        $this->db->from('banktype');
        $this->db->where('banktype.bank_code', $bankCode);
        $this->db->where('banktype.status', 'active');
        $this->db->where('banktype.enabled_withdrawal', 1);
        $this->db->where('banktype.enabled_deposit', 1);

        $row = $this->runOneRowArray();
        return $row;
    }

    public function getQuestCategorylist($categoryId = null) {
        $this->db->select([
            'quest_category.questCategoryId',
            'quest_category.title',
            'quest_category.description',
            // 'quest_category.sort',
            'quest_category.iconPath',
            'quest_category.bannerPath',
            'quest_category.showTimer',
            'quest_category.coverQuestTime',
            'quest_category.startAt',
            'quest_category.endAt',
            'quest_category.period',
            'quest_category.status',
        ]);
        $this->db->from('quest_category');
        $this->db->where('quest_category.status', 1);
        $this->db->where('quest_category.deleted', 0);
        if(!is_null($categoryId)) {
            $this->db->where('quest_category.questCategoryId', $categoryId);
        }
        $this->db->order_by('quest_category.sort', 'ASC');

        $result = $this->runMultipleRowArray();
        $this->utils->debug_log(__METHOD__, 'sql', $this->db->last_query(), 'result', $result);
        return $result;
    }

    public function getQuestManagerlistById($categoryId, $hierarchy, $limit, $page) {
        $this->utils->debug_log(__METHOD__, 'categoryId', $categoryId, 'hierarchy', $hierarchy, 'limit', $limit, 'page', $page);
        $now = $this->utils->getNowForMysql();
        $result = $this->getDataWithAPIPagination('quest_manager', function() use($categoryId, $now, $hierarchy) {
            $this->db->select([
                'quest_manager.questManagerId',
                'quest_manager.levelType',
                'quest_manager.questManagerType',
                'quest_manager.title',
                'quest_manager.description',
                'quest_manager.iconPath',
                'quest_manager.bannerPath',
                'quest_manager.period',
                'quest_manager.startAt',
                'quest_manager.endAt',
                'quest_manager.displayPanel',
                'quest_manager.showOneClick',
                'quest_manager.showTimer',
                'quest_manager.claimOtherUrl',
                'quest_rule.questConditionType',
                'quest_rule.questConditionValue',
                'quest_rule.personalInfoType',
                'quest_rule.bonusConditionType',
                'quest_rule.bonusConditionValue',
                'quest_rule.withdrawalConditionType',
                'quest_rule.withdrawReqBonusTimes',
                'quest_rule.withdrawReqBetAmount',
                'quest_rule.withdrawReqBettingTimes',
                'quest_rule.communityOptions',
                'quest_rule.extraRules',
            ]);
            $this->db->join('quest_rule', 'quest_manager.questRuleId = quest_rule.questRuleId', 'left');
            $this->db->where('quest_manager.questCategoryId', $categoryId);

            if($hierarchy === 'true') {
                $this->db->where('quest_manager.levelType', 2);
            }else if($hierarchy === 'false') {
                $this->db->where('quest_manager.levelType', 1);
            }

            $this->db->where('quest_manager.status', 1);
            $this->db->where('quest_manager.deleted', 0);
        }, $limit, $page);
        $this->utils->debug_log(__METHOD__, 'sql', $this->db->last_query(), 'result', $result);
        return $result;
    }

    public function checkQuestManagerExist($questManagerId) {
        $this->db->from("quest_manager")->where('questManagerId', $questManagerId);
		return $this->runExistsResult();
    }
} // End class Comapi_reports
