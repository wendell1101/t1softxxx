<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_modify_pragmatic_play_seamless_transaction_tables_20210119 extends CI_Migration {

    private $table_array = [
        'pragmaticplay_livedealer_seamless_idr1_wallet_transactions',
        'pragmaticplay_livedealer_seamless_myr1_wallet_transactions',
        'pragmaticplay_livedealer_seamless_thb1_wallet_transactions',
        'pragmaticplay_livedealer_seamless_usd1_wallet_transactions',
        'pragmaticplay_livedealer_seamless_vnd1_wallet_transactions',
        'pragmaticplay_seamless_cny1_wallet_transactions',
        'pragmaticplay_seamless_cny2_wallet_transactions',
        'pragmaticplay_seamless_cny3_wallet_transactions',
        'pragmaticplay_seamless_cny4_wallet_transactions',
        'pragmaticplay_seamless_cny5_wallet_transactions',
        'pragmaticplay_seamless_idr1_wallet_transactions',
        'pragmaticplay_seamless_idr2_wallet_transactions',
        'pragmaticplay_seamless_idr3_wallet_transactions',
        'pragmaticplay_seamless_idr4_wallet_transactions',
        'pragmaticplay_seamless_idr5_wallet_transactions',
        'pragmaticplay_seamless_myr1_wallet_transactions',
        'pragmaticplay_seamless_myr2_wallet_transactions',
        'pragmaticplay_seamless_myr3_wallet_transactions',
        'pragmaticplay_seamless_myr4_wallet_transactions',
        'pragmaticplay_seamless_myr5_wallet_transactions',
        'pragmaticplay_seamless_thb1_wallet_transactions',
        'pragmaticplay_seamless_thb2_wallet_transactions',
        'pragmaticplay_seamless_thb3_wallet_transactions',
        'pragmaticplay_seamless_thb4_wallet_transactions',
        'pragmaticplay_seamless_thb5_wallet_transactions',
        'pragmaticplay_seamless_usd1_wallet_transactions',
        'pragmaticplay_seamless_usd2_wallet_transactions',
        'pragmaticplay_seamless_usd3_wallet_transactions',
        'pragmaticplay_seamless_usd4_wallet_transactions',
        'pragmaticplay_seamless_usd5_wallet_transactions',
        'pragmaticplay_seamless_vnd1_wallet_transactions',
        'pragmaticplay_seamless_vnd2_wallet_transactions',
        'pragmaticplay_seamless_vnd3_wallet_transactions',
        'pragmaticplay_seamless_vnd4_wallet_transactions',
        'pragmaticplay_seamless_vnd5_wallet_transactions',
        'pragmaticplay_seamless_wallet_transactions',
    ];

    public function up() {
        $fields = array(
            'request_id' => array(
                'type' => 'VARCHAR',
                'constraint' => '64',
                'null' => true,
            ),
            'response_result_id' => array(
                'type' => 'BIGINT',
                'null' => true,
            )
        );

        foreach($this->table_array as $tableName) {
            if($this->utils->table_really_exists($tableName)) {
                if(!$this->db->field_exists('request_id', $tableName) && !$this->db->field_exists('response_result_id', $tableName)) {
                    $this->dbforge->add_column($tableName, $fields);
                }
            }
        }
    }

    public function down() {
    }
}