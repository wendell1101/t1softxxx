<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_modify_column_for_sbo_game_report_20231206 extends CI_Migration {

    private $table='sbo_game_report';     

    public function up() {
        $column = array(
            'ref_no' => array(
                'type' => 'VARCHAR',
                'constraint' => '300',
                'null' => true
            ),
        );

        if($this->utils->table_really_exists($this->table)){
            if($this->db->field_exists('ref_no', $this->table)){
                $this->dbforge->modify_column($this->table, $column);
            }
        }

    }

    public function down() {
    }
}