<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_table_isbseamless_wallet_transactions_20201005 extends CI_Migration {

    private $tableName = [
        'isbseamless_cny7_wallet_transactions',
        'isbseamless_idr7_wallet_transactions',
        'isbseamless_myr7_wallet_transactions',
        'isbseamless_thb7_wallet_transactions',
        'isbseamless_usd7_wallet_transactions',
        'isbseamless_vnd7_wallet_transactions'
    ];

    public function up() {
        $fields = array(
            'id' => array(
                'type' => 'int',
                'auto_increment' => TRUE,
                'unsigned' => TRUE,
            ),
            'username' => array(
                'type' => 'VARCHAR',
                'constraint' => '64',
                'null' => true,
            ),
            'transactionid' => array(
                'type' => 'VARCHAR',
                'constraint' => '64',
                'null' => true,
            ),
            'roundid' => array(
                'type' => 'VARCHAR',
                'constraint' => '64',
                'null' => true,
            ),
            'amount' => array(
                'type' => 'DOUBLE',
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
            'jpc' => array(
                'type' => 'DOUBLE',
                'null' => true,
            ),
            'froundid' => array(
                'type' => 'INT',
                'constraint' => '32',
                'null' => true,
            ),
            'fround_coin_value' => array(
                'type' => 'INT',
                'constraint' => '32',
                'null' => true,
            ),
            'fround_lines' => array(
                'type' => 'INT',
                'constraint' => '32',
                'null' => true,
            ),
            'fround_line_bet' => array(
                'type' => 'DOUBLE',
                'null' => true,
            ),
            'timestamp' => array(
                'type' => 'INT',
                'constraint' => '32',
                'null' => true,
            ),
            'closeround' => array(
                'type' => 'BOOLEAN',
                'null' => true,
            ),
            'jpw' => array(
                'type' => 'DOUBLE',
                'null' => true,
            ),
            'jpw_from_jpc' => array(
                'type' => 'DOUBLE',
                'null' => true,
            ),
            'command' => array(
                'type' => 'VARCHAR',
                'constraint' => '32',
                'null' => true,
            ),
            'sessionid' => array(
                'type' => 'VARCHAR',
                'constraint' => '48',
                'null' => true,
            ),
            'skinid' => array(
                'type' => 'VARCHAR',
                'constraint' => '32',
                'null' => true,
            ),
            'operator' => array(
                'type' => 'VARCHAR',
                'constraint' => '64',
                'null' => true,
            ),
            'transaction_status' => array(
                'type' => 'VARCHAR',
                'constraint' => '64',
                'null' => true,
            ),
            'currency' => array(
                'type' => 'VARCHAR',
                'constraint' => '10',
                'null' => true,
            ),
            'external_uniqueid' => array(
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true,
            ),
            'response_result_id' => array(
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true,
            ),
            'created_at' => array(
                'type' => 'DATETIME',
                'null' => true,
            ),
            'updated_at' => array(
                'type' => 'DATETIME',
                'null' => true,
            )
        );

        foreach ($this->tableName as $tableName) {
            if(!$this->utils->table_really_exists($tableName)){

                $this->dbforge->add_field($fields);
                $this->dbforge->add_key('id', TRUE);
                $this->dbforge->create_table($tableName);

                $this->load->model('player_model');
                $this->player_model->addUniqueIndex($tableName, 'idx_external_uniqueid', 'external_uniqueid');
                $this->player_model->addIndex($tableName, 'idx_transactionid', 'transactionid');
                $this->player_model->addIndex($tableName, 'idx_roundid', 'roundid');
                $this->player_model->addIndex($tableName,'idx_username' , 'username');
                $this->player_model->addIndex($tableName, 'idx_froundid', 'froundid');
                $this->player_model->addIndex($tableName,'idx_timestamp' , 'timestamp');
            }
        }


    }

    public function down() {
        foreach ($this->tableName as $tableName) {
            $this->dbforge->drop_table($tableName);
        }
    }
}