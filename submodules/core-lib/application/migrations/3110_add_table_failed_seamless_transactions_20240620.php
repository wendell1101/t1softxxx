<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_table_failed_seamless_transactions_20240620 extends CI_Migration {

    private $tableName = 'failed_remote_common_seamless_transactions';

    public function up()
    {
        $fields = array(
            'id' => array(
                'type' => 'BIGINT',
                'null' => false,
                'auto_increment' => true,
            ),
            'transaction_id' => array(
                'type' => 'VARCHAR',
                'constraint' => '120',
                'null' => true,
            ),
            'round_id' => array(
                'type' => 'VARCHAR',
                'constraint' => '120',
                'null' => true,
            ),
            'external_game_id' => array(
                'type' => 'VARCHAR',
                'constraint' => '60',
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
            'amount' => array(
                'type' => 'DOUBLE',
                'null' => true,
            ),
            'balance_adjustment_type' => array(
                'type' => 'VARCHAR',
                'constraint' => '20',
                'null' => true,
            ),
            'action' => array(
                'type' => 'VARCHAR',
                'constraint' => '60',
                'null' => true,
            ),
            'game_platform_id' => array(
                'type' => 'INT',
                'null' => true,
            ),
            'transaction_raw_data' => array(
                'type' => 'TEXT',
                'null' => true,
            ),
            'remote_raw_data' => array(
                'type' => 'JSON',
                'null' => true,
            ),
            'remote_wallet_status' => array(
                'type' => 'INT',
                'null' => true,
            ),
            'transaction_date' => array(
                'type' => 'DATETIME',
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
            $this->player_model->addIndex($this->tableName, 'idx_transaction_id', 'transaction_id');
            $this->player_model->addIndex($this->tableName, 'idx_round_id', 'round_id');
            $this->player_model->addIndex($this->tableName, 'idx_player_id', 'player_id');
            $this->player_model->addIndex($this->tableName, 'idx_game_platform_id', 'game_platform_id');
            $this->player_model->addIndex($this->tableName, 'idx_remote_wallet_status', 'remote_wallet_status');
            $this->player_model->addIndex($this->tableName, 'idx_transaction_date', 'transaction_date');
            $this->player_model->addIndex($this->tableName, 'idx_created_at', 'created_at');
            $this->player_model->addIndex($this->tableName, 'idx_updated_at', 'updated_at');

            
            $this->player_model->addUniqueIndex($this->tableName, 'idx_external_uniqueid', 'external_uniqueid');
        }
    }

    public function down() {
        $this->dbforge->drop_table($this->tableName);
    }
}