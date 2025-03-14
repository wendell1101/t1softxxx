<?php
require_once dirname(__FILE__) . '/base_model.php';

/**
 * Create Sale Orders abnormal History for deposit
 *
 * General behaviors include
 * * updatePaymentAbnormalHistory
 *
 * @category Payment Model
 * @version 1.0.1
 * @copyright 2013-2022 tot
 */

class Payment_abnormal_notification extends BaseModel {

	protected $tableName = 'payment_abnormal_notification';

    const ABNORMAL_PLAYER     = 1;
    const ABNORMAL_PAYMENT    = 2;
    const ABNORMAL_WITHDRAWAL = 3;

    const ABNORMAL_READ      = 1;
    const ABNORMAL_UNREAD    = 2;
    const ABNORMAL_TIMES     = 8;
    const ABNORMAL_TIMES_STR = '00000000';

    const WITHDRAWAL_ABNORMAL_TIMES = 7;

    /**
     * detail: update Payment Abnorma lStatus
     *
     * @param int $update_by
     * @param int $status read/unread
     * @param int $abnormal_id
     * @param string $notes
     *
     */
    public function updatePaymentAbnormalStatus($update_by, $status, $abnormal_id, $notes = null){
        $this->load->model('sale_order');
        $data = array(
            'status' => $status,
            'update_at' => $this->utils->getNowForMysql(),
            'update_by' => $update_by,
        );

        // if (!empty($notes)) {
        //     $data['notes'] = $notes;
        // }

        $this->utils->debug_log('========================updatePaymentAbnormalStatus ', $data);

        $this->db->where('id', $abnormal_id);
        $this->db->update($this->tableName, $data);
    }

    public function paymentAbnormaList($abnormaType = self::ABNORMAL_PLAYER, $abnormaStatus = self::ABNORMAL_UNREAD, $fromDatetime = null, $toDatetime = null, $limit = 4, $sort = 'ASC'){
        $this->db->select('pan.*, p.username');
        $this->db->from($this->tableName . ' as pan')
        ->join("player as p", "p.playerId = pan.playerId", 'left')
        ->where('pan.type', $abnormaType)
        ->where('pan.status', $abnormaStatus)
        ->order_by('pan.created_at', $sort)
        ->limit($limit);

        if (!empty($fromDatetime) && !empty($toDatetime)) {
            $this->db->where('pan.created_at >=', $fromDatetime)
                ->where('pan.created_at <=', $toDatetime);
        }

        return $this->runMultipleRowArray();
    }

    public function countPaymentAbnormaList($abnormaStatus = self::ABNORMAL_UNREAD, $fromDatetime = null, $toDatetime = null){

        $this->db->select('count(id) as cnt', false)
        ->from($this->tableName)
        ->where('status', $abnormaStatus);

        if (!empty($fromDatetime) && !empty($toDatetime)) {
            $this->db->where('created_at >=', $fromDatetime)
                ->where('created_at <=', $toDatetime);
        }

        return $this->runOneRowOneField('cnt');
    }

    /**
     * detail: get Payment Abnormal
     * @param string $date_base
     * @return boolean
     */
    public function generatePaymentAbnormalHistory($date_base){
        $date_base = isset($dates['date_base']) ? date('Y-m-d', strtotime($dates['date_base'])) : $this->utils->getNowForMysql();
        $this->utils->debug_log('========================get generatePaymentAbnormalHistory date_base ' . $date_base);
        $date_from = date('Y-m-d H:i:s', strtotime("{$date_base} -1 hours"));
        // $date_from = '2020-10-16 00:00:00';
        $date_to   = $date_base;

        # has 200 status order and no 600 status order payment
        $this->db->distinct();
        $this->db->select('
            sale_orders_status_history.created_at as created_at,
            sale_orders.payment_type_name as abnormal_payment_name,
            (SUM( CASE WHEN sale_orders_status_history.status = ' . Sale_order::DEPOSIT_STATUS_APPROVE_SALE_ORDER . ' THEN 1 ELSE 0 END ) / SUM( CASE WHEN sale_orders_status_history.status = '. Sale_order::DEPOSIT_STATUS_SUBMIT_ORDER . ' THEN 1 ELSE 0 END )) as success_times,
            ');
        $this->db->from('sale_orders_status_history');
        $this->db->join('sale_orders', 'sale_orders_status_history.order_id = sale_orders.id', 'left');
        $this->db->where('sale_orders_status_history.created_at >= ', $date_from);
        $this->db->where('sale_orders_status_history.created_at <= ', $date_to);
        $this->db->group_by('sale_orders.payment_account_name');
        $this->db->order_by('created_at', 'desc');

        $query = $this->runMultipleRow();
        $sql = $this->db->last_query();
        $this->utils->debug_log('========================get generatePaymentAbnormalHistory sql ' . $sql);
        $this->utils->debug_log('========================get generatePaymentAbnormalHistory query', $query);

        $data = [];

        if (empty($query)) {
            $this->utils->debug_log('========================get generatePaymentAbnormalHistory query empty ', $query);
            return false;
        }

        foreach ($query as $order) {
            $abnormal_payment_name = $order->abnormal_payment_name;
            $success_times        = $order->success_times;

            $this->utils->getNowForMysql();

            if (!empty($abnormal_payment_name) && $success_times != null) {
                if ($success_times == 0) {
                    $abnormal = array(
                        // 'playerId' => $id,
                        'type' => self::ABNORMAL_PAYMENT,
                        'status' => self::ABNORMAL_UNREAD,
                        'created_at' => $this->utils->getNowForMysql(),
                        // 'update_at' => $this->utils->getNowForMysql(),
                        // 'update_by' => $this->authentication->getUsername(),
                        'abnormal_payment_name' => $abnormal_payment_name
                    );
                    $data[] = $abnormal;
                }
            }
        }

        if (empty($data)) {
            $this->utils->debug_log('========================get generatePlayerAbnormalHistory date ', $date);
            return false;
        }

        $this->startTrans();
        $this->db->insert_batch($this->tableName, $data);
        $success = $this->endTransWithSucc();

        return $success;
    }

    /**
     * detail: get player Abnormal
     * @param string $date_base
     * @return boolean
     */
    public function generatePlayerAbnormalHistory($date_base){
        $date_base = isset($dates['date_base']) ? date('Y-m-d', strtotime($dates['date_base'])) : $this->utils->getNowForMysql();
        $this->utils->debug_log('========================get generatePlayerAbnormalHistory date_base ' . $date_base);
        $date_from = date('Y-m-d H:i:s', strtotime("{$date_base} -1 hours"));
        // $date_from = '2020-10-16 00:00:00';
        $date_to   = $date_base;

        # has 200 status order and no 600 status order player
        $this->db->select('
            GROUP_CONCAT(case when approve_order.order_id is null THEN 0 ELSE 1 end order by `sale_orders`.`created_at` desc separator "") as order_count,
            submit_order.order_id as submit_order_order_id,
            approve_order.order_id as approve_order_order_id,
            sale_orders.player_id as player_id,
            sale_orders.payment_type_name as abnormal_payment_name,'
            );
        $this->db->from('
            (
                select * from 
                    sale_orders_status_history
                WHERE 
                `sale_orders_status_history`.`created_at` >= "' .  $date_from . '"
                AND `sale_orders_status_history`.`created_at` <= "' . $date_to . '"
                AND sale_orders_status_history.status = '. Sale_order::DEPOSIT_STATUS_SUBMIT_ORDER . '
            ) as submit_order
                LEFT JOIN 
            (
                select * from
                    sale_orders_status_history
                WHERE 
                `sale_orders_status_history`.`created_at` >= "' . $date_from . '"
                AND `sale_orders_status_history`.`created_at` <= "' . $date_to . '"
                AND sale_orders_status_history.status = '. Sale_order::DEPOSIT_STATUS_APPROVE_SALE_ORDER . '
            ) as approve_order
            ON submit_order.order_id = approve_order.order_id
            ');
        $this->db->join('sale_orders', 'submit_order.order_id = sale_orders.id', 'left');
        $this->db->group_by('sale_orders.player_id');
        $this->db->having('count(sale_orders.player_id) >= '.self::ABNORMAL_TIMES);
        // $this->db->order_by('sale_orders.created_at', 'desc');

        $query = $this->runMultipleRow();
        $sql = $this->db->last_query();
        $this->utils->debug_log('========================get generatePlayerAbnormalHistory sql ' . $sql);
        $this->utils->debug_log('========================get generatePlayerAbnormalHistory query', $query);

        $data = [];

        if (empty($query)) {
            $this->utils->debug_log('========================get generatePlayerAbnormalHistory query empty ', $query);
            return false;
        }

        foreach ($query as $order) {
            $abnormal_payment_name  = $order->abnormal_payment_name;
            $order_count            = $order->order_count;
            $player_id              = $order->player_id;
            $approve_order_order_id = $order->approve_order_order_id;

            if (!empty($player_id) && empty($approve_order_order_id)) {
                if (strpos((string)$order_count, self::ABNORMAL_TIMES_STR) !== false) {
                    $abnormal = array(
                        'playerId' => $player_id,
                        'type' => self::ABNORMAL_PLAYER,
                        'status' => self::ABNORMAL_UNREAD,
                        'created_at' => $this->utils->getNowForMysql(),
                        // 'update_at' => $this->utils->getNowForMysql(),
                        'abnormal_payment_name' => $abnormal_payment_name
                    );
                    $data[] = $abnormal;
                }
            }
        }

        if (empty($data)) {
            $this->utils->debug_log('========================get generatePlayerAbnormalHistory date ', $date);
            return false;
        }

        $this->startTrans();
        $this->db->insert_batch($this->tableName, $data);
        $success = $this->endTransWithSucc();

        return $success;
    }

    public function checkOrderExist($orderId) {
        $this->db->from($this->tableName)->where('order_Id', $orderId);
        return $this->runExistsResult();
    }

    /**
     * detail: withdrawal Abnormal
     * @param string $date_base
     * @return boolean
     */
    public function generateWithdrawalAbnormalHistory($player, $walletAccount, $dates = null){
        $date_base = isset($dates['date_base']) ? date('Y-m-d', strtotime($dates['date_base'])) : $this->utils->getNowForMysql();
        $date_from = date('Y-m-d H:i:s', strtotime("{$date_base} -1 days"));#24h
        $date_to   = $date_base;
        $playerId  = $player['playerId'];
        $levelId   = $player['levelId'];
        $abnormal  = [];
        $this->utils->info_log(__METHOD__,'date_from ', $date_from, 'date_to', $date_to, 'playerId', $playerId, 'levelId', $levelId);
        $setting = $this->utils->getConfig('withdrawal_abnormal_setting');
        $this->utils->debug_log(__METHOD__,'withdrawal_abnormal_setting', $setting);

        $this->load->model(array('transactions','wallet_model'));

        if (!empty($playerId)) {

            $vipSetting = empty($setting[$levelId]) ? null : $setting[$levelId];
            if(!empty($vipSetting)){
                $times = $vipSetting['times'];
                $this->utils->debug_log(__METHOD__,'times', $times);
            }else{
                $this->utils->error_log(__METHOD__,'Not exist this level in vipSetting', $vipSetting);
                return false;
            }

            #transactions
            $this->db->select('count(id) as cnt, to_id as pId')
            ->from('transactions')
            ->where('transaction_type', Transactions::WITHDRAWAL)
            ->where('status', Transactions::APPROVED)
            ->where('to_type', Transactions::PLAYER)
            ->where('to_id',$playerId)
            ->where('created_at >=', $date_from)
            ->where('created_at <=', $date_to)
            ->group_by('pId')
            ->having('count(id) >='.$times);

            $query = $this->runMultipleRow();
            $sql = $this->db->last_query();
            $this->utils->info_log(__METHOD__,'sql', $sql);
            $this->utils->info_log(__METHOD__,'query', $query);

            if (empty($query)) {
                $this->utils->debug_log(__METHOD__,'query empty ', $query);
                return false;
            }

            $walletAccountId = $walletAccount['walletAccountId'];
            if (!$this->checkOrderExist($walletAccountId)) {
                $abnormal = array(
                    'playerId' => $playerId,
                    'type' => self::ABNORMAL_WITHDRAWAL,
                    'status' => self::ABNORMAL_UNREAD,
                    'created_at' => $this->utils->getNowForMysql(),
                    'order_id' => $walletAccountId,
                    'amount' => $walletAccount['amount']
                );
            } else {
                $this->utils->info_log(__METHOD__,'this withdrawal is exist', $walletAccountId);
            }
        }

        $this->utils->debug_log(__METHOD__,'abnormal result ', $abnormal);

        if (empty($abnormal)) {
            $this->utils->debug_log('========================get generatePlayerAbnormalHistory abnormal ', $abnormal);
            return false;
        }

        $this->startTrans();
        $insert_res = $this->insertData($this->tableName, $abnormal);
        $success = $this->endTransWithSucc();

        return $success && $insert_res;
    }

    public function generateWithdrawalAbnormalHistoryAllPlayer($dates = null){
        $date_base = isset($dates['date_base']) ? date('Y-m-d', strtotime($dates['date_base'])) : $this->utils->getNowForMysql();
        $date_from = date('Y-m-d H:i:s', strtotime("{$date_base} -1 days"));
        $date_to   = $date_base;
        $abnormal  = [];

        $this->utils->debug_log(__METHOD__,'date_from ', $date_from, 'date_to', $date_to);
        $setting = $this->utils->getConfig('withdrawal_abnormal_setting');
        $this->utils->info_log(__METHOD__,'withdrawal_abnormal_setting', $setting);

        $this->load->model(array('transactions','wallet_model'));

        #transactions
        $this->db->select('count(transactions.id) as cnt, transactions.to_id as pId, player.levelId')
        ->from('transactions')
        ->where('transactions.transaction_type', Transactions::WITHDRAWAL)
        ->where('transactions.status', Transactions::APPROVED)
        ->where('transactions.to_type', Transactions::PLAYER)
        ->where('transactions.created_at >=', $date_from)
        ->where('transactions.created_at <=', $date_to)
        ->join('player', 'player.playerId = transactions.to_id', 'left')
        ->group_by('pId')
        ->having('count(transactions.id) >= '.self::WITHDRAWAL_ABNORMAL_TIMES);

        $query = $this->runMultipleRow();
        $sql = $this->db->last_query();
        $this->utils->info_log(__METHOD__,'sql', $sql);
        $this->utils->debug_log(__METHOD__,'query', $query);

        $data = [];

        if (empty($query)) {
            $this->utils->debug_log(__METHOD__,'query empty ', $query);
            return false;
        }

        #wallet_model
        // $this->db->select('count(walletaccount.walletAccountId) as cnt, walletaccount.playerId as pId, player.levelId')
        // ->from('walletaccount')
        // // ->where('walletaccount.dwStatus', Wallet_model::APPROVED)
        // ->where('walletaccount.dwDateTime >=', $date_from)
        // ->where('walletaccount.dwDateTime <=', $date_to)
        // ->join('player', 'player.playerId = walletaccount.playerId', 'left')
        // ->group_by('pId')
        // ->having('count(walletaccount.walletAccountId) >= '.self::WITHDRAWAL_ABNORMAL_TIMES);

        // $query = $this->runMultipleRow();
        // $sql = $this->db->last_query();
        // $this->utils->info_log(__METHOD__,'sql', $sql);
        // $this->utils->debug_log(__METHOD__,'query', $query);

        foreach ($query as $player) {

            $order_count = $player->cnt;
            $player_id   = $player->pId;
            $levelId = $player->levelId;
            $this->utils->info_log('--------------------------player info', $player);

            if (!empty($player_id)) {

                $vipSetting = empty($setting[$levelId]) ? null : $setting[$levelId];
                if(!empty($vipSetting)){
                    $times = $vipSetting['times'];
                    $this->utils->debug_log(__METHOD__,'times', $times);
                }else{
                    $this->utils->error_log(__METHOD__,'Not exist this level in vipSetting ', $vipSetting);
                    return false;
                }

                if ($order_count >= $times) {

                    $status_list = array(Wallet_model::REQUEST_STATUS, Wallet_model::PENDING_REVIEW_STATUS, Wallet_model::PAY_PROC_STATUS, Wallet_model::LOCK_API_UNKNOWN_STATUS, 'CS0', 'CS1', 'CS2', 'CS3', 'CS4', 'CS5');

                    $this->db->select('*')
                    ->from('walletaccount')
                    ->where_in('dwStatus', $status_list)
                    ->where('dwDateTime >=', $date_from)
                    ->where('dwDateTime <=', $date_to)
                    ->where('playerId', $player_id)
                    ->order_by('dwDateTime','desc');
                    // ->limit($limit);

                    #query result will display the latest order
                    $orders = $this->runMultipleRow();
                    $sql = $this->db->last_query();
                    $this->utils->info_log('---------------------------sql', $sql);
                    $this->utils->debug_log('--------------------------orders ', $orders);

                    foreach ($orders as $order) {

                        $walletAccountId = $order->walletAccountId;
                        if (!$this->checkOrderExist($walletAccountId)) {
                            $abnormal = array(
                                'playerId' => $player_id,
                                'type' => self::ABNORMAL_WITHDRAWAL,
                                'status' => self::ABNORMAL_UNREAD,
                                'created_at' => $this->utils->getNowForMysql(),
                                'order_id' => $walletAccountId,
                                'amount' => $order->amount
                            );
                        } else {
                            $this->utils->info_log(__METHOD__,'this withdrawal is exist', $walletAccountId);
                            continue;
                        }

                        $data[] = $abnormal;
                    }
                }else{
                    $this->utils->info_log(__METHOD__,'the times is Ineligible', 'times:', $times, 'order_count:', $order_count);
                }
            }
        }

        $this->utils->debug_log(__METHOD__,'data result ', $data);

        if (empty($data)) {
            $this->utils->debug_log('========================get generatePlayerAbnormalHistory date ', $data);
            return false;
        }

        $this->startTrans();
        $insert_res = $this->db->insert_batch($this->tableName, $data);
        $success = $this->endTransWithSucc();

        return $success && $insert_res;
    }
}

/////end of file///////
