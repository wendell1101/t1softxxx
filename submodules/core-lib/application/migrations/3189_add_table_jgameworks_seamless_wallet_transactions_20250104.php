<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_table_jgameworks_seamless_wallet_transactions_20250104 extends CI_Migration {

    private $tableName = 'jgameworks_seamless_wallet_transactions';

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
            'u_name' => array(
                'type' => 'VARCHAR',
                'constraint' => '32',
                'null' => true,
            ),
            'token' => array(
                'type' => 'VARCHAR',
                'constraint' => '50',
                'null' => true,
            ),
            'bet_id' => array(
                'type' => 'VARCHAR',
                'constraint' => '300',
                'null' => true,
            ),
            'session_id' => array(
                'type' => 'VARCHAR',
                'constraint' => '300',
                'null' => true,
            ),
            'game_id' => array(
                'type' => 'VARCHAR',
                'constraint' => '200',
                'null' => true,
            ),
            'bet' => array(
                'type' => 'DOUBLE',
                'null' => true,
            ),
            'award' => array(
                'type' => 'DOUBLE',
                'null' => true,
            ),
            'is_end_round' => array(
                'type' => 'SMALLINT',
                'null' => true,
            ),
            'c_time' => array(
                'type' => 'INT',
                'null' => true,
            ),
            'betting_time' => [
                'type' => 'DATETIME',
                'null' => true
            ],

            # SBE additional info
            'result_amount' => array(
                'type' => 'DOUBLE',
                'null' => true,
            ),
            'amount_adjustment' => array(
                'type' => 'DOUBLE',
                'null' => true,
            ),
            'json_request' => array(
                'type' => 'JSON',
                'null' => true,
            ),
            'json_response' => array(
                'type' => 'JSON',
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
            'is_failed' => array(
                'type' => 'BOOLEAN',
                'null' => true,
                'default' => 0,
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
            $this->player_model->addIndex($this->tableName, 'idx_u_name', 'u_name');
            $this->player_model->addIndex($this->tableName, 'idx_bet_id', 'bet_id');
            $this->player_model->addIndex($this->tableName, 'idx_session_id', 'session_id');
            $this->player_model->addIndex($this->tableName, 'idx_betting_time', 'betting_time');
            $this->player_model->addIndex($this->tableName, 'idx_updated_at', 'updated_at');
            # add index unique
            $this->player_model->addUniqueIndex($this->tableName, 'idx_external_uniqueid', 'external_uniqueid');
        }
    }

    public function down() {
        $this->dbforge->drop_table($this->tableName);
    }
}