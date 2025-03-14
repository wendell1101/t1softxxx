<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_table_on_casino_game_logs_20240630 extends CI_Migration {

    private $tableName = 'on_casino_game_logs';

    public function up()
    {
        $fields = array(
            'id' => array(
                'type' => 'BIGINT',
                'null' => false,
                'auto_increment' => true,
            ),
            'order_id' => array(
                'type' => 'VARCHAR',
                'constraint' => '120',
                'null' => true,
            ),
            'order_number' => array(
                'type' => 'VARCHAR',
                'constraint' => '120',
                'null' => true,
            ),
            'username' => array(
                'type' => 'VARCHAR',
                'constraint' => '120',
                'null' => true,
            ),
            'stake' => array(
                'type' => 'DOUBLE',
                'null' => true,
            ),
            'play' => array(
                'type' => 'VARCHAR',
                'constraint' => '120',
                'null' => true,
            ),
            'play_id' => array(
                'type' => 'INT',
                'null' => true,
            ),
            'round_number' => array(
                'type' => 'VARCHAR',
                'constraint' => '120',
                'null' => true,
            ),
            'result' => array(
                'type' => 'INT',
                'null' => true,
            ),
            'game_result' => array(
                'type' => 'VARCHAR',
                'constraint' => '120',
                'null' => true,
            ),
            'odds' => array(
                'type' => 'DOUBLE',
                'null' => true,
            ),
            'other_odds' => array(
                'type' => 'VARCHAR',
                'constraint' => '120',
                'null' => true,
            ),
            'gross_win' => array(
                'type' => 'DOUBLE',
                'null' => true,
            ),
            'state' => array(
                'type' => 'INT',
                'null' => true,
            ),
            'valid_bet_amount' => array(
                'type' => 'DOUBLE',
                'null' => true,
            ),
            'currency' => array(
                'type' => 'VARCHAR',
                'constraint' => '120',
                'null' => true,
            ),
            'table_number' => array(
                'type' => 'VARCHAR',
                'constraint' => '120',
                'null' => true,
            ),
            'game_id' => array(
                'type' => 'INT',
                'null' => true,
            ),
            'add_time' => array(
                'type' => 'TIMESTAMP',
                'null' => true,
            ),
            'open_time' => array(
                'type' => 'TIMESTAMP',
                'null' => true,
            ),
            'settle_time' => array(
                'type' => 'TIMESTAMP',
                'null' => true,
            ),
            'ip' => array(
                'type' => 'VARCHAR',
                'constraint' => '120',
                'null' => true,
            ),
            'device' => array(
                'type' => 'INT',
                'null' => true,
            ),
            'win_lose' => array(
                'type' => 'DOUBLE',
                'null' => true,
            ),
            'raw_data' => array(
                'type' => 'JSON',
                'null' => true,
            ),
            'player_id' => array(
                'type' => 'BIGINT',
                'null' => true,
            ),
            'game_username' => array(
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true,
            ),
            'game_platform_id' => array(
                'type' => 'INT',
                'null' => true,
            ),
            'request_id' => array(
                'type' => 'VARCHAR',
                'constraint' => '60',
                'null' => true,
            ),
            'headers' => array(
                'type' => 'TEXT',
                'null' => true,
            ),
            'full_url' => array(
                'type' => 'VARCHAR',
                'constraint' => '200',
                'null' => true,
            ),
            'response_result_id' => array(
                'type' => 'INT',
                'constraint' => '11',
                'null' => true,
            ),
            'external_uniqueid' => array(
                'type' => 'VARCHAR',
                'constraint' => '200',
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
            ),
        );

        if(!$this->utils->table_really_exists($this->tableName)){
            $this->dbforge->add_field($fields);
            $this->dbforge->add_key('id', TRUE);
            $this->dbforge->create_table($this->tableName);

            # Add Index
            $this->load->model('player_model');
            $this->player_model->addIndex($this->tableName, 'idx_order_id', 'order_id');
            $this->player_model->addIndex($this->tableName, 'idx_order_number', 'order_number');
            $this->player_model->addIndex($this->tableName, 'idx_username', 'username');
            $this->player_model->addIndex($this->tableName, 'idx_stake', 'stake');
            $this->player_model->addIndex($this->tableName, 'idx_round_number', 'round_number');
            $this->player_model->addIndex($this->tableName, 'idx_result', 'result');
            $this->player_model->addIndex($this->tableName, 'idx_odds', 'odds');
            $this->player_model->addIndex($this->tableName, 'idx_gross_win', 'gross_win');
            $this->player_model->addIndex($this->tableName, 'idx_state', 'state');
            $this->player_model->addIndex($this->tableName, 'idx_valid_bet_amount', 'valid_bet_amount');
            $this->player_model->addIndex($this->tableName, 'idx_table_number', 'table_number');
            $this->player_model->addIndex($this->tableName, 'idx_game_id', 'game_id');
            $this->player_model->addIndex($this->tableName, 'idx_open_time', 'open_time');
            $this->player_model->addIndex($this->tableName, 'idx_settle_time', 'settle_time');
            $this->player_model->addIndex($this->tableName, 'idx_player_id', 'player_id');
            $this->player_model->addIndex($this->tableName, 'idx_game_username', 'game_username');
            $this->player_model->addIndex($this->tableName, 'idx_game_platform_id', 'game_platform_id');
            $this->player_model->addIndex($this->tableName, 'idx_request_id', 'request_id');
            $this->player_model->addIndex($this->tableName, 'idx_full_url', 'full_url');
            $this->player_model->addIndex($this->tableName, 'idx_response_result_id', 'response_result_id');
            $this->player_model->addIndex($this->tableName, 'idx_created_at', 'created_at');
            $this->player_model->addIndex($this->tableName, 'idx_updated_at', 'updated_at'); 
            $this->player_model->addUniqueIndex($this->tableName, 'idx_external_uniqueid', 'external_uniqueid');
        }
    }

    public function down() {
        $this->dbforge->drop_table($this->tableName);
    }
}