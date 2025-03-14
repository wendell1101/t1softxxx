<?php
require_once dirname(__FILE__) . '/base_model.php';

class Withdraw_condition_deducted_process extends BaseModel {

    protected $table = 'withdraw_condition_deducted_process';

	public function __construct() {
		parent::__construct();
	}

    public function getAllPlayersDeductedList(&$wc_amount_map, $total_date = null){
        if(!empty($wc_amount_map)) {
            //check if last day have recalculate data or not
            $recalculate_table = $this->existRecalculateTableLastDay($total_date);

            /*
            original wc arr by deducted flag
                $wc_amount_map[$row->player_id][] = [
                    "id" => $row->id,
                    "amount" => $row->amount,
                    "is_deducted" => false,
                    "is_deducted_from_calc_cashback", $row->is_deducted_from_calc_cashback
                ];
             */


            // if wc do not deduct betting amount before, then will use original data from original $wc_amount_map above
            foreach ($wc_amount_map as $wc_player_id => &$wc) {
                foreach ($wc as &$row) {
                    // if wc have duducted record, then will get wc from deduected table
                    $wc_id = $row['wc_id'];
                    $last_deducted_record = $this->getDeductedListByWithdrawConditionId($wc_id, $recalculate_table);
                    if (!empty($last_deducted_record)) {
                        /*
                        will get player deducted list by date when recalculate cashback by total date
                            $last_deducted_record (withdraw_condition_deducted_process arr)
                            [
                                'id' => '3', //wc_deducted_process_id
                                'player_id' => '112'
                                'withdraw_condition_id' => '166', //wc_id
                                'before_amount' => '120',
                                'after_amount' => '110', // will become condition_amount
                                'cashback_total_date' => '2021-09-10'
                                'created_at' => '2021-09-10 15:53:08',
                                'game_platform_id' => 5,
                                'game_type_id' => 9,
                                'game_description_id' => 1000
                            ];
                         */
                        $this->checkIsDeductedStatus($last_deducted_record);    // if wc's after_amount still greater than zero
                        $row = $last_deducted_record;
                    }

                }
            }

            $this->utils->debug_log('get all players deducted list result', $wc_amount_map);
        }
        return $wc_amount_map;

    }

    public function getAllPlayersDeductedListByRecalculateTable(&$wc_amount_map, $total_date = null, $recalculate_deducted_process_table = null){
        // check if recalculate table have date
        $this->db->from($recalculate_deducted_process_table);
        $row = $this->runOneRowArray();

        if(empty($row)){
            $target_table = $this->table;
            $target_date = $total_date;
            $recalculate_table = $this->existRecalculateTableLastDay($total_date);

            if(!empty($recalculate_table)){
                //check if last day have recalculate data or not
                $yesterday = $this->utils->getLastDay($total_date);
                $target_date = $yesterday;
                $target_table = $recalculate_table;
            }

            // first time recalculate cashback start from $target_date, then need to get original deducted record from original deducted $target_table by $target_date
            $this->db->distinct()
                     ->select('withdraw_condition_id')
                     ->from($target_table)
                     ->where('cashback_total_date', $target_date);

            $this->utils->debug_log('get all players deducted list by recalculate table by empty-recalculate_deducted_process_table', $target_table, $target_date);

            $deducted_list = $this->runMultipleRowArray();
            $this->utils->debug_log('wc_id of all deducted list by total date', $deducted_list);

            $player_list = [];
            if(!empty($deducted_list)){
                foreach($deducted_list as $deducted_row){
                    $last_deducted_record = $this->getDeductedListByWithdrawConditionIdByRecalculateDate($deducted_row['withdraw_condition_id'], $total_date);
                    // if wc have duducted record, then will get wc from deduected table
                    if(!empty($last_deducted_record)){
                        $this->checkIsDeductedStatus($last_deducted_record);    //  if before_amount still greater than zero
                        $player_list[$last_deducted_record['player_id']][] = $last_deducted_record;
                    }
                }
            }

            $wc_amount_map = $player_list;
            $this->utils->debug_log('get all players deducted list from original deducted table by total_date while recalculate cashback', $wc_amount_map);
        }else{
            //get rows from original recalculate deducted table
            $this->db->distinct()
                     ->select('withdraw_condition_id')
                     ->from($recalculate_deducted_process_table);

            $deducted_list = $this->runMultipleRowArray();
            $this->utils->debug_log('wc_id of all deducted list by total date', $deducted_list);

            $player_list = [];
            if(!empty($deducted_list)){
                foreach($deducted_list as $deducted_row){
                    $last_deducted_record = $this->getDeductedListByWithdrawConditionId($deducted_row['withdraw_condition_id'], $recalculate_deducted_process_table);
                    // if wc have duducted record, then will get wc from deduected table
                    if(!empty($last_deducted_record)){
                        $this->checkIsDeductedStatus($last_deducted_record);    // if wc's after_amount still greater than zero
                        $player_list[$last_deducted_record['player_id']][] = $last_deducted_record;
                    }
                }
            }

            $wc_amount_map = $player_list;
            $this->utils->debug_log('get all players deducted list from original recalculate deducted table by ecalculate_deducted_process_table while recalculate cashback', $wc_amount_map);
        }

        return $wc_amount_map;

    }

    public function getDeductedListByWithdrawConditionId($withdraw_condition_id, $recalculate_deducted_process_table = null){
	    $this->db->select('player_id, withdraw_condition_id as wc_id, after_amount as amount');

	    $target_table = $this->table;
        if(!empty($recalculate_deducted_process_table)){
            $target_table = $recalculate_deducted_process_table;
        }

        $this->db->from($target_table);
        $this->utils->debug_log('get deducted list by withdraw condition id from', $target_table);

         $this->db->where('withdraw_condition_id', $withdraw_condition_id)
                  ->order_by('id', 'desc')
                  ->limit(1);

        return $this->runOneRowArray();
    }

    public function getDeductedListByWithdrawConditionIdByRecalculateDate($withdraw_condition_id, $total_date = null){
        $this->db->select('player_id, withdraw_condition_id as wc_id, before_amount as amount')
                 ->from($this->table)
                 ->where('cashback_total_date', $total_date)
                 ->where('withdraw_condition_id', $withdraw_condition_id)
                 ->order_by('id', 'asc') // in order to get first deduected record
                 ->limit(1);

        return $this->runOneRowArray();
    }

    public function insertDeductedProcess($player_id, $total_date, $withdraw_condition_id, $before_amount, $after_amount, $game_platform_id, $game_type_id, $game_description_id){
        $params = [
            'player_id' => $player_id,
            'withdraw_condition_id' => $withdraw_condition_id,
            'before_amount' => $before_amount,
            'after_amount' => $after_amount,
            'cashback_total_date' => $total_date,
            //'created_at' => $this->utils->getNowForMysql(),
            'game_platform_id' => $game_platform_id,
            'game_type_id' => $game_type_id,
            'game_description_id' => $game_description_id,
        ];

        $this->db->insert($this->table, $params);

        return $this->db->insert_id();
    }

    public function insertDeductedProcessToRecalculateTable($player_id, $total_date, $withdraw_condition_id, $before_amount, $after_amount, $game_platform_id, $game_type_id, $game_description_id, $recalculate_deducted_process_table = null){
        $params = [
            'player_id' => $player_id,
            'withdraw_condition_id' => $withdraw_condition_id,
            'before_amount' => $before_amount,
            'after_amount' => $after_amount,
            'cashback_total_date' => $total_date,
            //'created_at' => $this->utils->getNowForMysql(),
            'game_platform_id' => $game_platform_id,
            'game_type_id' => $game_type_id,
            'game_description_id' => $game_description_id,
        ];

        $this->db->insert($recalculate_deducted_process_table, $params);

        return $this->db->insert_id();
    }

    public function existRecalculateTableLastDay($total_date){
        $recalculate_table = null;
        $yesterday = $this->utils->getLastDay($total_date);

        $this->db->from('recalculate_cashback')
                 ->where('total_date', $yesterday);

        $row = $this->runOneRowArray();

        if(!empty($row['uniqueid'])){
            $recalculate_table = 'withdraw_condition_deducted_process_' . $row['uniqueid'];
        }

        $this->utils->debug_log('exist recalculate table last day', $row);
        return $recalculate_table;
    }

    public function checkIsDeductedStatus(&$last_deducted_record){
        if((float)$last_deducted_record['amount'] > 0){
            $last_deducted_record['is_deducted'] = false;   // if wc's after_amount still greater than zero
        }else{
            $last_deducted_record['is_deducted'] = true;
        }
    }
}

///END OF FILE
