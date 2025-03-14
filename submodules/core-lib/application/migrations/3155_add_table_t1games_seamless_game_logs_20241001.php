<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_table_t1games_seamless_game_logs_20241001 extends CI_Migration {

    private $tableName = 't1games_seamless_game_logs';

    public function up()
    {
        $fields = array(
            'id' => array(
                'type' => 'BIGINT',
                'null' => false,
                'auto_increment' => true,
            ),
            'uniqueid' => array(
                'type' => 'INT',
                'null' => true,
            ),
            'external_uniqueid' => array(
                'type' => 'VARCHAR',
                'constraint' => '300',
                'null' => true,
            ),
            'game_external_uniqueid' => array(
                'type' => 'VARCHAR',
                'constraint' => '300',
                'null' => true,
            ),
            'username' => array(
                'type' => 'VARCHAR',
                'constraint' => '120',
                'null' => true,
            ),
            'player_username' => array(
                'type' => 'VARCHAR',
                'constraint' => '120',
                'null' => true,
            ),
            'merchant_code' => array(
                'type' => 'VARCHAR',
                'constraint' => '60',
                'null' => true,
            ),
            'game_platform_id' => array(
                'type' => 'SMALLINT',
                'null' => true,
            ),
            'game_code' => array(
                'type' => 'VARCHAR',
                'constraint' => '120',
                'null' => true,
            ),
            'game_name' => array(
                'type' => 'TEXT',
                'null' => true,
            ),
            'game_finish_time' => array(
                'type' => 'DATETIME',
                'null' => true,
            ),
            'game_details' => array(
                'type' => 'VARCHAR',
                'constraint' => '120',
                'null' => true,
            ),
            'bet_time' => array(
                'type' => 'DATETIME',
                'null' => true,
            ),
            'payout_time' => array(
                'type' => 'DATETIME',
                'null' => true,
            ),
            'round_number' => array(
                'type' => 'VARCHAR',
                'constraint' => '300',
                'null' => true,
            ),
            'real_bet_amount' => array(
                'type' => 'DOUBLE',
                'null' => true,
            ),
            'effective_bet_amount' => array(
                'type' => 'DOUBLE',
                'null' => true,
            ),
            'result_amount' => array(
                'type' => 'DOUBLE',
                'null' => true,
            ),
            'payout_amount' => array(
                'type' => 'DOUBLE',
                'null' => true,
            ),
            'after_balance' => array(
                'type' => 'DOUBLE',
                'null' => true,
            ),
            'bet_details' => array(
                'type' => 'TEXT',
                'null' => true,
            ),
            'md5_sum' => array(
                'type' => 'VARCHAR',
                'constraint' => '32',
                'null' => true,
            ),
            'ip_address' => array(
                'type' => 'VARCHAR',
                'constraint' => '32',
                'null' => true,
            ),
            'bet_type' => array(
                'type' => 'VARCHAR',
                'constraint' => '50',
                'null' => true,
            ),
            'odds_type' => array(
                'type' => 'VARCHAR',
                'constraint' => '32',
                'null' => true,
            ),
            'odds' => array(
                'type' => 'VARCHAR',
                'constraint' => '32',
                'null' => true,
            ),
            'rent' => array(
                'type' => 'VARCHAR',
                'constraint' => '60',
                'null' => true,
            ),
            'response_result_id' => array(
                'type' => 'INT',
                'null' => true,
            ),
            'update_version' => array(
                'type' => 'INT',
                'null' => true,
            ),
            'game_status' => array(
                'type' => 'VARCHAR',
                'constraint' => '32',
                'null' => true,
            ),
            'detail_status' => array(
                'type' => 'VARCHAR',
                'constraint' => '32',
                'null' => true,
            ),
            'created_at' => array(
                'type' => 'DATETIME',
                'null' => true,
            ),
            'updated_at' => array(
                'type' => 'DATETIME',
                'null' => true,
            ),
        );

        if(!$this->utils->table_really_exists($this->tableName)){
            $this->dbforge->add_field($fields);
            $this->dbforge->add_key('id', TRUE);
            $this->dbforge->create_table($this->tableName);

            # Add Index
            $this->load->model('player_model');
            $this->player_model->addIndex($this->tableName, 'idx_player_username', 'player_username');
            $this->player_model->addIndex($this->tableName, 'idx_username', 'username');
            $this->player_model->addIndex($this->tableName, 'idx_payout_time', 'payout_time');
            $this->player_model->addIndex($this->tableName, 'idx_bet_time', 'bet_time');
            $this->player_model->addIndex($this->tableName, 'idx_game_platform_id', 'game_platform_id');
            $this->player_model->addIndex($this->tableName, 'idx_round_number', 'round_number');
            
            $this->player_model->addUniqueIndex($this->tableName, 'idx_external_uniqueid', 'external_uniqueid');
        }
    }

    public function down() {
        $this->dbforge->drop_table($this->tableName);
    }
}