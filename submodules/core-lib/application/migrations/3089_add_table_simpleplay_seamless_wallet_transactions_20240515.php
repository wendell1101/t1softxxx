<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_table_simpleplay_seamless_wallet_transactions_20240515 extends CI_Migration {

    private $tableName = 'simpleplay_seamless_wallet_transactions';

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
            'username' => array(
                'type' => 'VARCHAR',
                'constraint' => '28',
                'null' => true,
            ),
            'currency' => array(
                'type' => 'VARCHAR',
                'constraint' => '16',
                'null' => true,
            ),
            'amount' => [ #in cent
                'type' => 'DOUBLE',
                'null' => true,
            ],
            'txnid' => array(
                'type' => 'VARCHAR',
                'constraint' => '16',
                'null' => true,
            ),
            'txn_reverse_id' => array(
                'type' => 'VARCHAR',
                'constraint' => '16',
                'null' => true,
            ),
            'timestamp' => array(
                'type' => 'DATETIME',
                'null' => true,
            ),
            'Payouttime' => array(
                'type' => 'DATETIME',
                'null' => true,
            ),
            'ip' => array(
                'type' => 'VARCHAR',
                'constraint' => '30',
                'null' => true,
            ),
            'gametype' => array(
                'type' => 'VARCHAR',
                'constraint' => '30',
                'null' => true,
            ),
            'hostid' => array(
                'type' => 'INT',
                'null' => true,
            ),
            'platform' => array(
                'type' => 'SMALLINT',
                'null' => true,
            ),
            'gamecode' => array(
                'type' => 'VARCHAR',
                'constraint' => '50',
                'null' => true,
            ),
            'gameid' => array(
                'type' => 'VARCHAR',
                'constraint' => '50',
                'null' => true,
            ),
            'JackpotType' => array(
                'type' => 'VARCHAR',
                'constraint' => '50',
                'null' => true,
            ),
            'JackpotContribution' => [ #in cent
                'type' => 'DOUBLE',
                'null' => true,
            ],
            'JackpotWin' => array(
                'type' => 'BOOLEAN',
                'null' => true,
            ),
            'betdetails' => array(
                'type' => 'JSON',
                'null' => true,
            ),
            'payoutdetails' => array(
                'type' => 'JSON',
                'null' => true,
            ),
            'request' => array(
                'type' => 'TEXT',
                'null' => true,
            ),
            'request_encoded' => array(
                'type' => 'TEXT',
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
            $this->player_model->addIndex($this->tableName, 'idx_method', 'method');
            $this->player_model->addIndex($this->tableName, 'idx_gamecode', 'gamecode');
            $this->player_model->addIndex($this->tableName, 'idx_txnid', 'txnid');
            $this->player_model->addIndex($this->tableName, 'idx_gameid', 'gameid');
            $this->player_model->addIndex($this->tableName, 'idx_txn_reverse_id', 'txn_reverse_id');
            $this->player_model->addIndex($this->tableName, 'idx_created_at', 'created_at');
            $this->player_model->addIndex($this->tableName, 'idx_updated_at', 'updated_at');
            $this->player_model->addUniqueIndex($this->tableName, 'idx_external_uniqueid', 'external_uniqueid');
        }
    }

    public function down() {
        $this->dbforge->drop_table($this->tableName);
    }
}