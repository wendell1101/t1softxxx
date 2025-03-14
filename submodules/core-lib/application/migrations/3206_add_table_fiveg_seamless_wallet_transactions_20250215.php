<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_table_fiveg_seamless_wallet_transactions_20250215 extends CI_Migration {

    private $tableName = 'fiveg_seamless_wallet_transactions';

    public function up()
    {
        $fields = array(
            'id' => array(
                'type' => 'BIGINT',
                'null' => false,
                'auto_increment' => true,
            ),
            'player_id' => array(
                'type' => 'INT',
                'null' => true,
            ),
            'game_platform_id' => array(
                'type' => 'INT',
                'null' => true,
            ),
            'trans_type' => array(
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true,
            ),
            #request params
            'access_token' => array(
                'type' => 'VARCHAR',
                'constraint' => '300',
                'null' => true,
            ),
            'txn_id' => array(
                'type' => 'BIGINT',
                'null' => true,
            ),
            'total_bet' => array(
                'type' => 'DOUBLE',
                'null' => true,
            ),
            'total_win' => array(
                'type' => 'DOUBLE',
                'null' => true,
            ),
            'bonus_win' => array(
                'type' => 'DOUBLE',
                'null' => true,
            ),
            'game_id' => array(
                'type' => 'VARCHAR',
                'constraint' => '64',
            ),
            'subgame_id' => array(
                'type' => 'INT',
                'null' => true,
            ),
            'ts' => array(
                'type' => 'INT',
                'null' => true,
            ), 
            'round_start_time' => [
                'type' => 'DATETIME',
                'null' => true
            ],
            'bonus_id' => array(
                'type' => 'INT',
                'null' => true,
            ),
            'bonus_reward' => array(
                'type' => 'DOUBLE',
                'null' => true,
            ), 
            'bonus_type' => array(
                'type' => 'VARCHAR',
                'constraint' => '300',
                'null' => true,
            ),
            # SBE additional info
            'bet_amount' => array(
                'type' => 'DOUBLE',
                'null' => true,
            ),
            'payout_amount' => array(
                'type' => 'DOUBLE',
                'null' => true,
            ),
            'jackpot_amount' => array(
                'type' => 'DOUBLE',
                'null' => true,
            ),
            'full_url' => array(
                'type' => 'text',
                'null' => true,
            ),
            'sbe_status' => array(
                'type' => 'SMALLINT',
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
            'elapsed_time' => array(
                'type' => 'INT',
                'constraint' => '12',
                'null' => true,
            ),
            #remote wallet data
            'remote_wallet_status' => array(
                'type' => 'INT',
                'null' => true,
            ),
            'seamless_service_unique_id' => array(
                'type' => 'VARCHAR',
                'constraint' => '150',
                'null' => true,
            ),
            #alternate for response result id
            'request_id' => array(
                'type' => 'VARCHAR',
                'constraint' => '200',
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
            $this->player_model->addIndex($this->tableName, 'idx_player_id', 'player_id');
            $this->player_model->addIndex($this->tableName, 'idx_trans_type', 'trans_type');
            $this->player_model->addIndex($this->tableName, 'idx_txn_id', 'txn_id');
            $this->player_model->addIndex($this->tableName, 'idx_round_start_time', 'round_start_time');
            $this->player_model->addIndex($this->tableName, 'idx_updated_at', 'updated_at');
            # add index unique
            $this->player_model->addUniqueIndex($this->tableName, 'idx_external_uniqueid', 'external_uniqueid');
        }
    }

    public function down() {
        $this->dbforge->drop_table($this->tableName);
    }
}