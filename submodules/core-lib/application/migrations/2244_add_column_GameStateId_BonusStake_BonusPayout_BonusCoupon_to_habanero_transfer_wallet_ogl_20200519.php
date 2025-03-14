<?php

defined("BASEPATH") OR exit("No direct script access allowed");

class Migration_add_column_GameStateId_BonusStake_BonusPayout_BonusCoupon_to_habanero_transfer_wallet_ogl_20200519 extends CI_Migration
{
	private $tableName = [
        'haba88_cny1_game_logs',
        'haba88_cny2_game_logs',
        'haba88_game_logs',
        'haba88_idr1_game_logs',
        'haba88_idr2_game_logs',
        'haba88_idr3_game_logs',
        'haba88_idr4_game_logs',
        'haba88_idr5_game_logs',
        'haba88_idr6_game_logs',
        'haba88_idr7_game_logs',
        'haba88_myr1_game_logs',
        'haba88_myr2_game_logs',
        'haba88_thb1_game_logs',
        'haba88_thb2_game_logs',
        'haba88_vnd1_game_logs',
        'haba88_vnd2_game_logs',
        'haba88_vnd3_game_logs'
    ];

    private $fields = array(
        'GameStateId' => array(
            'type' => 'INT',
            'constraint' => '3',
            'null' => true,
        ),
        'BonusStake' => array(
            'type' => 'DOUBLE',
            'null' => true,
        ),
        'BonusPayout' => array(
            'type' => 'DOUBLE',
            'null' => true,
        ),
        'BonusCoupon' => array(
            'type' => 'VARCHAR',
            'constraint' => '25',
            'null' => true,
        ),
    );

    public function up()
    {
        $this->migrateProcess();
    }

    public function down() {
        $this->migrateProcess('drop_column');
    }

    public function migrateProcess($action='add_column')
    {
        foreach($this->tableName as $table){
            if($this->utils->table_really_exists($table)){
                if(!$this->db->field_exists('GameStateId', $table)&&
                    !$this->db->field_exists('BonusStake', $table)&&
                    !$this->db->field_exists('BonusPayout', $table)&&
                    !$this->db->field_exists('BonusCoupon', $table)
                ){
                    $this->dbforge->$action($table, $this->fields);
                }
            }
        }
    }
}