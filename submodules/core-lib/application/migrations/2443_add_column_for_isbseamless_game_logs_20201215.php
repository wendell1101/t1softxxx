<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_for_isbseamless_game_logs_20201215 extends CI_Migration {

    private $tableName = [
        'isbseamless_game_logs',
        'isbseamless_cny1_game_logs',
        'isbseamless_idr1_game_logs',
        'isbseamless_myr1_game_logs',
        'isbseamless_thb1_game_logs',
        'isbseamless_usd1_game_logs',
        'isbseamless_vnd1_game_logs',

        'isbseamless_cny2_game_logs',
        'isbseamless_idr2_game_logs',
        'isbseamless_myr2_game_logs',
        'isbseamless_thb2_game_logs',
        'isbseamless_usd2_game_logs',
        'isbseamless_vnd2_game_logs',

        'isbseamless_cny3_game_logs',
        'isbseamless_idr3_game_logs',
        'isbseamless_myr3_game_logs',
        'isbseamless_thb3_game_logs',
        'isbseamless_usd3_game_logs',
        'isbseamless_vnd3_game_logs',

        'isbseamless_cny4_game_logs',
        'isbseamless_idr4_game_logs',
        'isbseamless_myr4_game_logs',
        'isbseamless_thb4_game_logs',
        'isbseamless_usd4_game_logs',
        'isbseamless_vnd4_game_logs',

        'isbseamless_cny5_game_logs',
        'isbseamless_idr5_game_logs',
        'isbseamless_myr5_game_logs',
        'isbseamless_thb5_game_logs',
        'isbseamless_usd5_game_logs',
        'isbseamless_vnd5_game_logs',

        'isbseamless_cny6_game_logs',
        'isbseamless_idr6_game_logs',
        'isbseamless_myr6_game_logs',
        'isbseamless_thb6_game_logs',
        'isbseamless_usd6_game_logs',
        'isbseamless_vnd6_game_logs',

        'isbseamless_cny7_game_logs',
        'isbseamless_idr7_game_logs',
        'isbseamless_myr7_game_logs',
        'isbseamless_thb7_game_logs',
        'isbseamless_usd7_game_logs',
        'isbseamless_vnd7_game_logs',
    ];

    public function up()
    {
        foreach ($this->tableName as $tableName) {
            if($this->utils->table_really_exists($tableName)){
                $fields = array(
                    'transaction_status' => array(
                        'type' => 'VARCHAR',
                        'constraint' => '64',
                        'null' => true,
                    )
                );

                if(!$this->db->field_exists('transaction_status', $tableName)){
                    $this->dbforge->add_column($tableName, $fields);
                }
            }
        }
    }

    public function down()
    {
        foreach ($this->tableName as $tableName) {
            $this->dbforge->drop_column($tableName, 'transaction_status');
        }
    }
}