<?php

defined('BASEPATH') OR exit('No direct script access allowed');


class Migration_drop_table_hogaming_seamless_idr1to7_game_logs_20200915 extends CI_Migration
{
    private $table_idr = ['hogaming_seamless_idr1_game_logs','hogaming_seamless_idr2_game_logs','hogaming_seamless_idr3_game_logs','hogaming_seamless_idr4_game_logs','hogaming_seamless_idr5_game_logs','hogaming_seamless_idr6_game_logs','hogaming_seamless_idr7_game_logs'];

    public function up()
    {
        // if(!empty($this->table_idr)){
        //     foreach ($this->table_idr as $table) {
        //         #to remove wrong table migration
        //         // if ($this->db->table_exists($table)) {
        //         if($this->utils->table_really_exists($table)) {
        //             $this->dbforge->drop_table($table);
        //         }
        //     }
        // }
    }

    public function down()
    {

    }
}
