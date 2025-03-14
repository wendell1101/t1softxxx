<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_table_mascot_seamless_wallet_transactions_20240413 extends CI_Migration {

    private $tableName = 'mascot_seamless_wallet_transactions';

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
            'method' => array(
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true,
            ),
            #request params
            'caller_id' => array(
                'type' => 'INT',
                'null' => true,
            ),
            'player_name' => array(
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true,
            ),
            'withdraw_in_cent' => [ #in cent
                'type' => 'DOUBLE',
                'null' => true,
            ],
            'deposit_in_cent' => [#in cent
                'type' => 'DOUBLE',
                'null' => true,
            ],
            'withdraw' => [ #converted from cent to actual currency
                'type' => 'DOUBLE',
                'null' => true,
            ],
            'deposit' => [ #converted from cent to actual currency
                'type' => 'DOUBLE',
                'null' => true,
            ],
            'currency' => array(
                'type' => 'VARCHAR',
                'constraint' => '5',
                'null' => true,
            ),
            'transaction_ref' => array(
                'type' => 'VARCHAR',
                'constraint' => '300',
                'null' => true,
            ),
            'game_round_ref' => array(
                'type' => 'VARCHAR',
                'constraint' => '300',
                'null' => true,
            ),
            'game_id' => array(
                'type' => 'VARCHAR',
                'constraint' => '200',
                'null' => true,
            ),
            'source' => array(
                'type' => 'VARCHAR',
                'constraint' => '200',
                'null' => true,
            ),
            'reason' => array(
                'type' => 'VARCHAR',
                'constraint' => '200',
                'null' => true,
            ),
            'session_id' => array(
                'type' => 'VARCHAR',
                'constraint' => '200',
                'null' => true,
            ),
            'session_alternative_id' => array(
                'type' => 'VARCHAR',
                'constraint' => '200',
                'null' => true,
            ),
            'spin_details' => array(
                'type' => 'JSON',
                'null' => true,
            ),
            'bonus_id' => array(
                'type' => 'VARCHAR',
                'constraint' => '200',
                'null' => true,
            ),
            'bonus_free_rounds' => array(
                'type' => 'DOUBLE',
                'null' => true,
            ),
            'charge_free_rounds' => array(
                'type' => 'DOUBLE',
                'null' => true,
            ),

            # SBE additional info
            'json_request' => [
                'type' => 'JSON',
                'null' => true,
            ],
            'sbe_status' => [
                'type' => 'SMALLINT',
                'null' => true,
            ],
            'before_balance' => [
                'type' => 'DOUBLE',
                'null' => true,
            ],
            'after_balance' => [
                'type' => 'DOUBLE',
                'null' => true,
            ],
            'elapsed_time' => [
                'type' => 'INT',
                'constraint' => '12',
                'null' => true,
            ],
            #alternate for response result id
            'request_id' => [ 
                'type' => 'VARCHAR',
                'constraint' => '200',
                'null' => true,
            ],
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
            )
        );

        if(!$this->utils->table_really_exists($this->tableName)){
            $this->dbforge->add_field($fields);
            $this->dbforge->add_key('id', TRUE);
            $this->dbforge->create_table($this->tableName);

            # Add Index
            $this->load->model('player_model');
            $this->player_model->addIndex($this->tableName, 'idx_player_id', 'player_id');
            $this->player_model->addIndex($this->tableName, 'idx_game_id', 'game_id');
            $this->player_model->addIndex($this->tableName, 'idx_method', 'method');
            $this->player_model->addIndex($this->tableName, 'idx_transaction_ref', 'transaction_ref');
            $this->player_model->addIndex($this->tableName, 'idx_game_round_ref', 'game_round_ref');
            $this->player_model->addIndex($this->tableName, 'idx_created_at', 'created_at');
            $this->player_model->addIndex($this->tableName, 'idx_updated_at', 'updated_at');
            $this->player_model->addUniqueIndex($this->tableName, 'idx_external_uniqueid', 'external_uniqueid');
        }
    }

    public function down() {
        $this->dbforge->drop_table($this->tableName);
    }
}