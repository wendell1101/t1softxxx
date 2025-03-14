<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_table_fbsports_seamless_wallet_transactions_20240614 extends CI_Migration {

    private $tableName = 'fbsports_seamless_wallet_transactions';

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
            'transaction_id' => array(
                'type' => 'VARCHAR',
                'constraint' => '120',
                'null' => true,
            ),
            'user_id' => array(
                'type' => 'VARCHAR',
                'constraint' => '120',
                'null' => true,
            ),
            'merchant_id' => array(
                'type' => 'VARCHAR',
                'constraint' => '120',
                'null' => true,
            ),
            'merchant_user_id' => array(
                'type' => 'VARCHAR',
                'constraint' => '60',
                'null' => true,
            ),
            'business_id' => array(
                'type' => 'VARCHAR',
                'constraint' => '120',
                'null' => true,
            ),
            'transaction_type' => array(
                'type' => 'VARCHAR',
                'constraint' => '10',
                'null' => true,
            ),
            'transfer_type' => array(
                'type' => 'VARCHAR',
                'constraint' => '60',
                'null' => true,
            ),
            'currency_id' => array(
                'type' => 'SMALLINT',
                'null' => true,
            ),
            'amount' => [ #in cent
                'type' => 'DOUBLE',
                'null' => true,
            ],
            'status' => array(
                'type' => 'SMALLINT',
                'null' => true,
            ),
            'related_id' => array(
                'type' => 'VARCHAR',
                'constraint' => '120',
                'null' => true,
            ),
            'third_remark' => array(
                'type' => 'VARCHAR',
                'constraint' => '120',
                'null' => true,
            ),

            # SBE additional info
            'json_request' => [
                'type' => 'JSON',
                'null' => true,
            ],
            'json_response' => [
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
            #remote wallet data
            'remote_wallet_status' => [
                'type' => 'INT',
                'null' => true,
            ],
            'is_failed' => array(
                'type' => 'BOOLEAN',
                'null' => true,
                'default' => 0,
            ),
            'seamless_service_unique_id' => [
                'type' => 'VARCHAR',
                'constraint' => '150',
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
            $this->player_model->addIndex($this->tableName, 'idx_merchant_user_id', 'merchant_user_id');
            $this->player_model->addIndex($this->tableName, 'idx_transaction_id', 'transaction_id');
            $this->player_model->addIndex($this->tableName, 'idx_business_id', 'business_id');
            $this->player_model->addIndex($this->tableName, 'idx_related_id', 'related_id');
            $this->player_model->addIndex($this->tableName, 'idx_is_failed', 'is_failed');
            $this->player_model->addIndex($this->tableName, 'idx_remote_wallet_status', 'remote_wallet_status');
            $this->player_model->addIndex($this->tableName, 'idx_created_at', 'created_at');
            $this->player_model->addIndex($this->tableName, 'idx_updated_at', 'updated_at');

            $this->player_model->addUniqueIndex($this->tableName, 'idx_external_uniqueid', 'external_uniqueid');
        }
    }

    public function down() {
        $this->dbforge->drop_table($this->tableName);
    }
}