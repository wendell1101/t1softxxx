<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_to_oneworks_related_table_20210428 extends CI_Migration {

    private $tables = ["onebook_thb1_game_logs","ibc_onebook_game_logs","onebook_game_logs","oneworks_game_logs"];

    public function up() {
        $field = array(
            'percentage' => array(
                'type' => 'double',
                'null' => true,
            ),
        );

        foreach ($this->tables as $table) {
            if($this->utils->table_really_exists($table)){
                if(!$this->db->field_exists('percentage', $table)){
                    $this->dbforge->add_column($table, $field);
                }
            }
        }
    }

    public function down() {
        foreach ($this->tables as $table) {
            if($this->utils->table_really_exists($table)){
                if($this->db->field_exists('percentage', $table)){
                    $this->dbforge->drop_column($table, $field);
                }
            }
        }
    }
}