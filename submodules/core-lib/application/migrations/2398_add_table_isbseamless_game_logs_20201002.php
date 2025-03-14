<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_table_isbseamless_game_logs_20201002 extends CI_Migration {

    private $tableName = [
        'isbseamless_cny3_game_logs',
        'isbseamless_idr3_game_logs',
        'isbseamless_myr3_game_logs',
        'isbseamless_thb3_game_logs',
        'isbseamless_usd3_game_logs',
        'isbseamless_vnd3_game_logs',
        'isbseamless_cny4_game_logs',
        'isbseamless_idr4_game_logs',
        'isbseamless_myr4_game_logs',
        'isbseamless_thb4_game_logs',
        'isbseamless_usd4_game_logs',
        'isbseamless_vnd4_game_logs',
        'isbseamless_cny5_game_logs',
        'isbseamless_idr5_game_logs',
        'isbseamless_myr5_game_logs',
        'isbseamless_thb5_game_logs',
        'isbseamless_usd5_game_logs',
        'isbseamless_vnd5_game_logs',
        'isbseamless_cny6_game_logs',
        'isbseamless_idr6_game_logs',
        'isbseamless_myr6_game_logs',
        'isbseamless_thb6_game_logs',
        'isbseamless_usd6_game_logs',
        'isbseamless_vnd6_game_logs',
    ];

    public function up() {
        $fields = array(
            'id' => array(
                'type' => 'int',
                'auto_increment' => TRUE,
                'unsigned' => TRUE,
            ),
            'username' => array(
                'type' => 'VARCHAR',
                'constraint' => '64',
                'null' => true,
            ),
            'transactionid' => array(
                'type' => 'VARCHAR',
                'constraint' => '64',
                'null' => true,
            ),
            'roundid' => array(
                'type' => 'VARCHAR',
                'constraint' => '64',
                'null' => true,
            ),
            'amount' => array(
                'type' => 'DOUBLE',
                'null' => true,
            ),
            'result_amount' => array(
                'type' => 'DOUBLE',
                'null' => true,
            ),
            'before_balance' => array(
                'type' => 'DOUBLE',
                'null' => true,
            ),
            'after_balance' => array(
                'type' => 'DOUBLE',
                'null' => true,
            ),
            'jpc' => array(
                'type' => 'DOUBLE',
                'null' => true,
            ),
            'froundid' => array(
                'type' => 'INT',
                'constraint' => '32',
                'null' => true,
            ),
            'fround_coin_value' => array(
                'type' => 'INT',
                'constraint' => '32',
                'null' => true,
            ),
            'fround_lines' => array(
                'type' => 'INT',
                'constraint' => '32',
                'null' => true,
            ),
            'fround_line_bet' => array(
                'type' => 'DOUBLE',
                'null' => true,
            ),
            'timestamp' => array(
                'type' => 'INT',
                'constraint' => '32',
                'null' => true,
            ),
            'closeround' => array(
                'type' => 'BOOLEAN',
                'null' => true,
            ),
            'jpw' => array(
                'type' => 'DOUBLE',
                'null' => true,
            ),
            'jpw_from_jpc' => array(
                'type' => 'DOUBLE',
                'null' => true,
            ),
            'command' => array(
                'type' => 'VARCHAR',
                'constraint' => '32',
                'null' => true,
            ),
            'sessionid' => array(
                'type' => 'VARCHAR',
                'constraint' => 48,
                'null' => true,
            ),
            'skinid' => array(
                'type' => 'VARCHAR',
                'constraint' => 32,
                'null' => true,
            ),
            'operator' => array(
                'type' => 'VARCHAR',
                'constraint' => '64',
                'null' => true,
            ),
            'start_at' => array(
                'type' => 'VARCHAR',
                'constraint' => '30',
                'null' => true,
            ),
            'end_at' => array(
                'type' => 'VARCHAR',
                'constraint' => '30',
                'null' => true,
            ),
            
            'external_uniqueid' => array(
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true,
            ),
            'response_result_id' => array(
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true,
            ),
            "created_at DATETIME DEFAULT CURRENT_TIMESTAMP" => array(
                "null" => false
            ),
            "updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP" => array(
                "null" => false
            ),
            'md5_sum' => array(
                'type' => 'VARCHAR',
                'constraint' => '32',
                'null' => true,
            )
        );

        foreach ($this->tableName as $tableName) {
            if(!$this->utils->table_really_exists($tableName)){

                $this->dbforge->add_field($fields);
                $this->dbforge->add_key('id', TRUE);
                $this->dbforge->create_table($tableName);

                $this->load->model('player_model');
                $this->player_model->addUniqueIndex($tableName, 'idx_external_uniqueid', 'external_uniqueid');
                $this->player_model->addIndex($tableName, 'idx_transactionid', 'transactionid');
                $this->player_model->addIndex($tableName, 'idx_roundid', 'roundid');
                $this->player_model->addIndex($tableName,'idx_username' , 'username');
                $this->player_model->addIndex($tableName, 'idx_froundid', 'froundid');
                $this->player_model->addIndex($tableName,'idx_timestamp' , 'timestamp');
            }
        }


    }

    public function down() {
        foreach ($this->tableName as $tableName) {
            $this->dbforge->drop_table($tableName);
        }
    }
}