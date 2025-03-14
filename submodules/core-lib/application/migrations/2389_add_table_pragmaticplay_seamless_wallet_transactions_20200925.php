<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_table_pragmaticplay_seamless_wallet_transactions_20200925 extends CI_Migration {

    private $tableNames = [
        'pragmaticplay_seamless_idr2_wallet_transactions',
        'pragmaticplay_seamless_idr3_wallet_transactions',
        'pragmaticplay_seamless_idr4_wallet_transactions',
        'pragmaticplay_seamless_idr5_wallet_transactions',

        'pragmaticplay_seamless_myr2_wallet_transactions',
        'pragmaticplay_seamless_myr3_wallet_transactions',
        'pragmaticplay_seamless_myr4_wallet_transactions',
        'pragmaticplay_seamless_myr5_wallet_transactions',

        'pragmaticplay_seamless_thb2_wallet_transactions',
        'pragmaticplay_seamless_thb3_wallet_transactions',
        'pragmaticplay_seamless_thb4_wallet_transactions',
        'pragmaticplay_seamless_thb5_wallet_transactions',

        'pragmaticplay_seamless_usd2_wallet_transactions',
        'pragmaticplay_seamless_usd3_wallet_transactions',
        'pragmaticplay_seamless_usd4_wallet_transactions',
        'pragmaticplay_seamless_usd5_wallet_transactions',

        'pragmaticplay_seamless_vnd2_wallet_transactions',
        'pragmaticplay_seamless_vnd3_wallet_transactions',
        'pragmaticplay_seamless_vnd4_wallet_transactions',
        'pragmaticplay_seamless_vnd5_wallet_transactions',

        'pragmaticplay_seamless_cny1_wallet_transactions',
        'pragmaticplay_seamless_cny2_wallet_transactions',
        'pragmaticplay_seamless_cny3_wallet_transactions',
        'pragmaticplay_seamless_cny4_wallet_transactions',
        'pragmaticplay_seamless_cny5_wallet_transactions',
    ];

    public function up() {
        $fields = array(
            'id' => array(
                'type' => 'BIGINT',
                'null' => false,
                'auto_increment' => TRUE,
            ),
            'user_id' => array(
                'type' => 'VARCHAR',
                'constraint' => '30',
                'null' => true,
            ),
            'game_id' => array(
                'type' => 'VARCHAR',
                'constraint' => '30',
                'null' => true,
            ),
            'round_id' => array(
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true,
            ),
            'amount' => array(
                'type' => 'double',
                'null' => true,
            ),
            'transaction_id' => array(
                'type' => 'VARCHAR',
                'constraint' => '50',
                'null' => true,
            ),
            'bonus_code' => array(
                'type' => 'VARCHAR',
                'constraint' => '10',
                'null' => true,
            ),
            'transaction_type' => array(
                'type' => 'VARCHAR',
                'constraint' => '10',
                'null' => true,
            ),
            'provider_id' => array(
                'type' => 'VARCHAR',
                'constraint' => '50',
                'null' => true,
            ),
            "timestamp" => array(
                "type" => "DATETIME",
                "null" => true
            ),
            'round_details' => array(
                'type' => 'VARCHAR',
                'constraint' => '50',
                'null' => true,
            ),

            # SBE additional info
            'before_balance' => array(
                'type' => 'double',
                'null' => true,
            ),
            'after_balance' => array(
                'type' => 'double',
                'null' => true,
            ),
            'external_uniqueid' => array(
                'type' => 'VARCHAR',
                'constraint' => '150',
                'null' => true,
            ),
            'campaign_id' => array(
                'type' => 'VARCHAR',
                'constraint' => '50',
                'null' => true,
            ),
            'campaign_type' => array(
                'type' => 'VARCHAR',
                'constraint' => '50',
                'null' => true,
            ),
            'currency' => array(
                'type' => 'VARCHAR',
                'constraint' => '10',
                'null' => true,
            ),
            'created_at DATETIME DEFAULT CURRENT_TIMESTAMP' => array(
                'null' => false,
            ),
            'updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP' => array(
                'null' => false,
            ),
            'jackpot_id' => array(
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true,
            ),
        );

        foreach($this->tableNames as $tableName) {
            if(!$this->utils->table_really_exists($tableName)) {

                $this->dbforge->add_field($fields);
                $this->dbforge->add_key("id",true);
                $this->dbforge->create_table($tableName);

                # add index
                $this->player_model->addIndex($tableName, 'idx_timestamp', 'timestamp');
                $this->player_model->addIndex($tableName, 'idx_transaction_id', 'transaction_id');
                $this->player_model->addIndex($tableName, 'idx_user_id', 'user_id');
                $this->player_model->addIndex($tableName, 'idx_game_id', 'game_id');
                $this->player_model->addIndex($tableName, 'idx_round_id', 'round_id');
                $this->player_model->addUniqueIndex($tableName, 'idx_uexternal_uniqueid', 'external_uniqueid');
        
                $this->player_model->addIndex($tableName, 'idx_created_at', 'created_at');
                $this->player_model->addIndex($tableName, 'idx_updated_at', 'updated_at');
            }
        }
    }

    public function down() {
        //
    }
}
