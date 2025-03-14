<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_recreate_table_amg_game_logs_20200407 extends CI_Migration {

    private $tableName = 'amg_game_logs';

    public function up() {
        $fields = array(
            'id' => array(
                'type' => 'BIGINT',
                'null' => false,
                'auto_increment' => TRUE,
            ),
            "timestamp" => array(
                "type" => "DATETIME",
                "null" => true
            ),
            "external_id" => array(
                "type" => "VARCHAR",
                "constraint" => "12",
                "null" => true
            ),
            "transaction_type" => array(
                "type" => "VARCHAR",
                "constraint" => "10",
                "null" => true
            ),
            "currency" => array(
                "type" => "VARCHAR",
                "constraint" => "3",
                "null" => true
            ),
            "base_currency" => array(
                "type" => "VARCHAR",
                "constraint" => "2",
                "null" => true
            ),
            "amount" => array(
                "type" => "DOUBLE",
                "null" => true
            ),
            "base_currency_amount" => array(
                "type" => "DOUBLE",
                "null" => true
            ),
            "last_cash_balance" => array(
                "type" => "DOUBLE",
                "null" => true
            ),
            "last_bonus_balance" => array(
                "type" => "DOUBLE",
                "null" => true
            ),
            "round_id" => array(
                'type' => 'BIGINT',
                "null" => true
            ),
            "player_id" => array(
                "type" => "VARCHAR",
                "constraint" => "60",
                "null" => true
            ),
            "player_name" => array(
                "type" => "VARCHAR",
                "constraint" => "60",
                "null" => true
            ),
            "round_ended" => array(
                "type" => "TINYINT",
                "constraint" => "1",
                "null" => true
            ),
            "round_start_time" => array(
                "type" => "DATETIME",
                "null" => true
            ),
            "round_end_time" => array(
                "type" => "DATETIME",
                "null" => true
            ),
            "game_id" => array(
                "type" => "VARCHAR",
                "constraint" => "60",
                "null" => true
            ),
            "game_name" => array(
                "type" => "VARCHAR",
                "constraint" => "255",
                "null" => true
            ),

            # SBE additional info
            'response_result_id' => array(
                'type' => 'INT',
                'constraint' => '11',
                'null' => true,
            ),
            'external_uniqueid' => array(
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true,
            ),
            'created_at DATETIME DEFAULT CURRENT_TIMESTAMP' => array(
                'null' => false,
            ),
            'updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP' => array(
                'null' => false,
            ),
            'md5_sum' => array(
                'type' => 'VARCHAR',
                'constraint' => '32',
                'null' => true,
            )
        );

        if($this->db->table_exists($this->tableName)){

            if(!$this->db->field_exists('base_currency_amount', $this->tableName)) { // DROP table if old
                $this->dbforge->drop_table($this->tableName);
                $this->db->data_cache = [];
                $this->dbforge->add_field($fields);
                $this->dbforge->add_key('id', TRUE);
                $this->dbforge->create_table($this->tableName);

                # Add Index
                $this->load->model("player_model");
                $this->player_model->addIndex($this->tableName,"idx_round_id","round_id");
                $this->player_model->addIndex($this->tableName,"idx_round_start_time","round_start_time");
                $this->player_model->addIndex($this->tableName,"idx_round_end_time","round_end_time");
                $this->player_model->addUniqueIndex($this->tableName,"idx_external_id","external_id");
                $this->player_model->addUniqueIndex($this->tableName,"idx_external_uniqueid","external_uniqueid");
            }
        }
    }

    public function down() {

    }
}
