<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_add_table_jumbo_seamless_wallet_transactions_20221020 extends CI_Migration
{
    private $tableName = 'jumbo_seamless_wallet_transactions';

    public function up()
    {
        $fields = [
            'id' => [
                'type' => 'BIGINT',
                'null' => false,
                'auto_increment' => true
            ],
            'game_platform_id' => [
                'type' => 'INT',
                'constraint' => '10'
            ],
            'amount' => [
                'type' => 'DOUBLE',
                'null' => true
            ],
            'before_balance' => [
                'type' => 'DOUBLE',
                'null' => true
            ],
            'after_balance' => [
                'type' => 'DOUBLE',
                'null' => true
            ],
            'player_id' => [
                'type' => 'INT',
                'constraint' => '12',
                'null' => true
            ],
            'game_id' => [
                'type' => 'VARCHAR',
                'constraint' => '50',
                'null' => true
            ],
            'transaction_type' => [
                'type' => 'VARCHAR',
                'constraint' => '25',
                'null' => true
            ],
            'game_status' => [
                'type' => 'VARCHAR',
                'constraint' => '25',
                'null' => true
            ],
            'response_result_id' => [
                'type' => 'INT',
                'null' => true
            ],
            'external_unique_id' => [
                'type' => 'VARCHAR',
                'constraint' => '150',
                'null' => true
            ],
            'extra_info' => [
                'type' => 'JSON',
                'null' => true
            ],
            'start_at' => [
                'type' => 'DATETIME',
                'null' => true
            ],
            'end_at' => [
                'type' => 'DATETIME',
                'null' => true
            ],
            'created_at DATETIME DEFAULT CURRENT_TIMESTAMP' => [
                'null' => false
            ],
            'updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP' => [
                'null' => false
            ],
            'md5_sum' => [
                'type' => 'VARCHAR',
                'constraint' => '32',
                'null' => true,
            ],
            'transaction_id' => [
                'type' => 'VARCHAR',
                'constraint' => '50',
                'null' => true
            ],
            'elapsed_time' => [
                'type' => 'INT',
                'constraint' => '11',
                'null' => true
            ],
            'round_id' => [
                'type' => 'VARCHAR',
                'constraint' => '50',
                'null' => true
            ],
            'bet_amount' => [
                'type' => 'DOUBLE',
                'null' => true
            ],
            'result_amount' => [
                'type' => 'DOUBLE',
                'null' => true
            ],
            'flag_of_updated_result' => [
                'type' => 'BOOLEAN',
                'null' => true,
                'default' => 0
            ],
            #Other column needed
            'game_seq_no' => [
                'type' => 'VARCHAR',
                'constraint' => '50',
                'null' => true
            ],
            'game_type' => [
                'type' => 'INT',
                'constraint' => '2',
                'null' => true
            ],
            'report_date' => [
                'type' => 'DATE',
                'null' => true
            ],
            'currency' => [
                'type' => 'VARCHAR',
                'constraint' => '10',
                'null' => true
            ],
            'win_amount' => [
                'type' => 'DOUBLE',
                'null' => true
            ],
            'net_win' => [
                'type' => 'DOUBLE',
                'null' => true
            ],
            'demon' => [
                'type' => 'DOUBLE',
                'null' => true
            ],
            'client_type' => [
                'type' => 'VARCHAR',
                'constraint' => '10',
                'null' => true
            ],
            'system_take_win' => [
                'type' => 'INT',
                'null' => true
            ],
            'jackpot_win' => [
                'type' => 'DOUBLE',
                'null' => true
            ],
            'jackpot_contribute' => [
                'type' => 'DOUBLE',
                'null' => true
            ],
            'has_free_game' => [
                'type' => 'INT',
                'default' => 0
            ],
            'has_gameble' => [
                'type' => 'INT',
                'default' => 0
            ],
        ];

        if (!$this->db->table_exists($this->tableName)) {
            $this->dbforge->add_field($fields);
            $this->dbforge->add_key('id', TRUE);
            $this->dbforge->create_table($this->tableName);

            # add Index
            $this->load->model('player_model');
            $this->player_model->addIndex($this->tableName, 'idx_game_platform_id', 'game_platform_id');
            $this->player_model->addIndex($this->tableName, 'idx_player_id', 'player_id');
            $this->player_model->addIndex($this->tableName, 'idx_transaction_type', 'transaction_type');
            $this->player_model->addIndex($this->tableName, 'idx_transaction_id', 'transaction_id');
            $this->player_model->addIndex($this->tableName, 'idx_game_id', 'game_id');
            $this->player_model->addIndex($this->tableName, 'idx_round_id', 'round_id');
            $this->player_model->addIndex($this->tableName, 'idx_start_at', 'start_at');
            $this->player_model->addIndex($this->tableName, 'idx_end_at', 'end_at');
            $this->player_model->addIndex($this->tableName, 'idx_game_status', 'game_status');
            $this->player_model->addIndex($this->tableName, 'idx_flag_of_updated_result', 'flag_of_updated_result');
            $this->player_model->addIndex($this->tableName, 'idx_created_at', 'created_at');
            $this->player_model->addIndex($this->tableName, 'idx_updated_at', 'updated_at');
            $this->player_model->addUniqueIndex($this->tableName, 'idx_external_unique_id', 'external_unique_id');
        }
    }

    public function down()
    {
        if ($this->db->table_exists($this->tableName)) {
            $this->dbforge->drop_table($this->tableName);
        }
    }
}