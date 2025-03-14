<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_table_jq_game_logs_20220121 extends CI_Migration {

    private $tableName = 'jq_game_logs';

    public function up()
    {
        $fields = array(
            'id' => array(
                'type' => 'BIGINT',
                'null' => false,
                'auto_increment' => true,
            ),
            'bet_id' => array(
                'type' => 'VARCHAR',
                'constraint' => '64',
                'null' => true,
            ),
            'game_name' => array(
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true,
            ),
            'game_code' => array(
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true,
            ),
            'account' => array(
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true,
            ),
            'bet_time' => array(
                'type' => 'DATETIME',
                'null' => true,
            ),
            'bet_level' => array(
                'type' => 'DOUBLE',
                'null' => true,
            ),
            'bet_amount' => array(
                'type' => 'DOUBLE',
                'null' => true,
            ),
            'total_bet_amount' => array(
                'type' => 'DOUBLE',
                'null' => true,
            ),
            'total_valid_bet' => array(
                'type' => 'DOUBLE',
                'null' => true,
            ),
            'net_profit' => array(
                'type' => 'DOUBLE',
                'null' => true,
            ),
            'serial_number' => array(
                'type' => 'INT',
                'constraint' => '5',
                'null' => true,
            ),
            'status' => array(
                'type' => 'TINYINT',
                'constraint' => '1',
                'null' => true,
            ),
            'game_type' => array(
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true,
            ),
            'start_balance' => array(
                'type' => 'DOUBLE',
                'null' => true,
            ),
            'end_balance' => array(
                'type' => 'DOUBLE',
                'null' => true,
            ),
            'reel_type' => array(
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true,
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
                'null' => false
            ),
            'updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP' => array(
                'null' => false
            ),
            'md5_sum' => array(
                'type' => 'VARCHAR',
                'constraint' => '32',
                'null' => true,
            )
        );

        if(!$this->utils->table_really_exists($this->tableName)){
            $this->dbforge->add_field($fields);
            $this->dbforge->add_key('id', TRUE);
            $this->dbforge->create_table($this->tableName);

            # Add Index
            $this->load->model('player_model');
            $this->player_model->addIndex($this->tableName, 'idx_bet_id', 'bet_id');
            $this->player_model->addIndex($this->tableName, 'idx_game_name', 'game_name');
            $this->player_model->addIndex($this->tableName, 'idx_game_code', 'game_code');
            $this->player_model->addIndex($this->tableName, 'idx_account', 'account');
            $this->player_model->addIndex($this->tableName, 'idx_bet_time', 'bet_time');

            $this->player_model->addUniqueIndex($this->tableName, 'idx_external_uniqueid', 'external_uniqueid');
        }
    }

    public function down() {
        $this->dbforge->drop_table($this->tableName);
    }
}