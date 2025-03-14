<?php

defined("BASEPATH") OR exit("No direct script access allowed");

class Migration_add_tables_dispatch_withdrawal_series_20200720 extends CI_Migration
{

    private $tableName4definition = "dispatch_withdrawal_definition";
    private $tableName4conditions = "dispatch_withdrawal_conditions";
    private $tableName4included_game_type = "dispatch_withdrawal_conditions_included_game_description";

    public function up()
    {

        // symbol:
        // 1:greaterThanOrEqualTo, ≥
        // 2:greaterThan, >
        // 0:equalTo, =
        // -1:lessThanOrEqualTo, ≤
        // -2:lessThan, <



        // for dispatch_withdrawal_conditions_included_game_description
        $fields4included_game_type = array(
            'id' => array(
				'type' => 'INT',
				'unsigned' => TRUE,
				'auto_increment' => TRUE,
			),
            "dispatch_withdrawal_conditions_id" => array(  // F.K. dispatch_withdrawal_conditions.id
                "type" => "BIGINT",
                "null" => false
            ),
            "game_description_id" => array( // F.K. game_description.id
                'type' => 'INT',
                "constraint" => "11",
                'null' => true,
            ),
        );

        if( ! $this->db->table_exists($this->tableName4included_game_type) ){
            $this->dbforge->add_field($fields4included_game_type);
            $this->dbforge->add_key("id",true); // for P.K.
            $this->dbforge->create_table($this->tableName4included_game_type);

            # add Index
            $this->load->model("player_model");
            // DWDIGY = dispatch_withdrawal_conditions_included_game_description
            $this->player_model->addIndex($this->tableName4included_game_type,"idx_DWDIGY_dispatch_withdrawal_conditions_id","dispatch_withdrawal_conditions_id");
        }


        //  for dispatch_withdrawal_conditions
        $fields4conditions = array(
            'id' => array(
				'type' => 'INT',
				'unsigned' => TRUE,
				'auto_increment' => TRUE,
            ),
            "name" => array(
                "type" => "VARCHAR",
                "constraint" => "255",
                "null" => true
            ),
            "dispatch_withdrawal_definition_id" => array( // F.K. dispatch_withdrawal_definition.id
                "type" => "BIGINT",
                "null" => true
            ),
            "status" => array(
                "type" => "INT",
                "constraint" => "11",
                "null" => true
            ),

            // for Betting And Withdrawal Rate
            'betAndWithdrawalRate_isEnable' => array(
                "type" => "INT",
                "constraint" => "11",
                'default' => 0,
                "null" => true
            ),
            'betAndWithdrawalRate_rate' => array(
                "type" => "DOUBLE",
                "null" => true,
            ),
            'betAndWithdrawalRate_symbol' => array(
                "type" => "INT",
                "constraint" => "11",
                'default' => 0,
                "null" => true
            ),

            // for Game_type
            "includedGameType_isEnable" => array(// for (1:n) dispatch_withdrawal_conditions_included_game_description
                'type' => 'INT',
                "constraint" => "11",
                'default' => 0,
                'null' => true,
            ),

            // for Player Tags excluded
            // "includedPlayerTag_isEnable" => array(
            //     'type' => 'INT',
            //     "constraint" => "11",
            //     'default' => 0,
            //     'null' => true,
            // ),
            // "includedPlayerTag_list" => array(
            //     "type" => "VARCHAR",
            //     "constraint" => "255",
            //     "null" => true
            // ),
            "excludedPlayerTag_isEnable" => array(
                'type' => 'INT',
                "constraint" => "11",
                'default' => 0,
                'null' => true,
            ),
            "excludedPlayerTag_list" => array(
                "type" => "VARCHAR",
                "constraint" => "255",
                "null" => true
            ),


            // ignore, same as calcAvailableBetOnly_isEnable
            // "availableBetOnly_isEnable" => array(
            //     'type' => 'INT',
            //     "constraint" => "11",
            //     'default' => 0,
            //     'null' => true,
            // ),


            "gameRevenuePercentage_isEnable" => array(
                'type' => 'INT',
                "constraint" => "11",
                'default' => 0,
                'null' => true,
            ),
            'gameRevenuePercentage_rate' => array(
                "type" => "DOUBLE",
                "null" => true,
            ),
            'gameRevenuePercentage_symbol' => array(
                "type" => "INT",
                "constraint" => "11",
                'default' => 0,
                "null" => true
            ),

            // for Today Withdrawal Count
            "todayWithdrawalCount_isEnable" => array(
                'type' => 'INT',
                "constraint" => "11",
                'default' => 0,
                'null' => true,
            ),
            "todayWithdrawalCount_limit" => array(
                'type' => 'INT',
                "constraint" => "11",
                'default' => 0,
                'null' => true,
            ),
            'todayWithdrawalCount_symbol' => array(
                "type" => "INT",
                "constraint" => "11",
                'default' => 0,
                "null" => true
            ),

            // for Withdrawal Amount
            "withdrawalAmount_isEnable" => array(
                'type' => 'INT',
                "constraint" => "11",
                'default' => 0,
                'null' => true,
            ),
            "withdrawalAmount_limit" => array(
                'type' => 'DOUBLE',
                'null' => true,
            ),
            'withdrawalAmount_symbol' => array(
                "type" => "INT",
                "constraint" => "11",
                'default' => 0,
                "null" => true
            ),

            "winAndDepositRate_isEnable" => array(
                'type' => 'INT',
                "constraint" => "11",
                'default' => 0,
                'null' => true,
            ),
            'winAndDepositRate_rate' => array(
                "type" => "DOUBLE",
                "null" => true,
            ),
            'winAndDepositRate_symbol' => array(
                "type" => "INT",
                "constraint" => "11",
                'default' => 0,
                "null" => true
            ),

            "totalDepositCount_isEnable" => array(
                'type' => 'INT',
                "constraint" => "11",
                'default' => 0,
                'null' => true,
            ),
            "totalDepositCount_limit" => array(
                'type' => 'INT',
                "constraint" => "11",
                'default' => 0,
                'null' => true,
            ),
            'totalDepositCount_symbol' => array(
                "type" => "INT",
                "constraint" => "11",
                'default' => 0,
                "null" => true
            ),

            // total_player_game_minute.real_betting_amount > 0
            "calcAvailableBetOnly_isEnable" => array(
                'type' => 'INT',
                "constraint" => "11",
                'default' => 0,
                'null' => true,
            ),

            // If checked, just calc with enabled game. (ignore disabled)
            "calcEnabledGameOnly_isEnable" => array(
                'type' => 'INT',
                "constraint" => "11",
                'default' => 0,
                'null' => true,
            ),

            // If checked, calc deposit items with the promo condition.
            "calcPromoDepositOnly_isEnable" => array(
                'type' => 'INT',
                "constraint" => "11",
                'default' => 0,
                'null' => true,
            ),

            /// If checked, allow Game_logs.status in the following values,
            // - STATUS_SETTLED
            // - STATUS_ACCEPTED
            // - STATUS_SETTLED
            "ignoreCanceledGameLogs_isEnable" => array(
                'type' => 'INT',
                "constraint" => "11",
                'default' => 0,
                'null' => true,
            ),
            'created_at DATETIME DEFAULT CURRENT_TIMESTAMP' => array(
                'null' => false,
            ),
            'updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP' => array(
                'null' => false,
            ),
        );

        if( ! $this->db->table_exists($this->tableName4conditions) ){
            $this->dbforge->add_field($fields4conditions);
            $this->dbforge->add_key("id",true); // for P.K.
            $this->dbforge->create_table($this->tableName4conditions);

            # add Index
        }


        //  for dispatch_withdrawal_definition
        $fields4definition = array(
            'id' => array(
				'type' => 'INT',
				'unsigned' => TRUE,
				'auto_increment' => TRUE,
			),
            "name" => array(
                "type" => "VARCHAR",
                "constraint" => "255",
                "null" => true
            ),
            "extra" => array(
                "type" => "JSON",
                "null" => true
            ),
            'status' => array(
                "type" => "INT",
                "constraint" => "11",
                "null" => true
            ),
            'eligible2dwStatus' => array(
                "type" => "VARCHAR",
                "constraint" => "255",
                "null" => true
            ),
            "eligible2external_system_id" => array(
                'type' => 'INT',
                "constraint" => "11",
				'unsigned' => TRUE,
                'null' => true,
            ),
            "dispatch_order" => array( // like  payment_account.payment_order
                'type' => 'INT',
                "constraint" => "11",
                'default' => 100,
                'null' => true,
            ),
            'created_at DATETIME DEFAULT CURRENT_TIMESTAMP' => array(
                'null' => false,
            ),
            'updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP' => array(
                'null' => false,
            ),
        );

        if( ! $this->db->table_exists($this->tableName4definition) ){
            $this->dbforge->add_field($fields4definition);
            $this->dbforge->add_key("id",true); // for P.K.
            $this->dbforge->create_table($this->tableName4definition);

            # add Index
        }
    }

    public function down()
    {
        if($this->db->table_exist($this->tableName4definition)){
            $this->dbforge->drop_table($this->tableName4definition);
        }
        if($this->db->table_exist($this->tableName4conditions)){
            $this->dbforge->drop_table($this->tableName4conditions);
        }
        if($this->db->table_exist($this->tableName4included_game_type)){
            $this->dbforge->drop_table($this->tableName4included_game_type);
        }
    }
}