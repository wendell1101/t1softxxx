<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_modify_column_for_player_20200928 extends CI_Migration {

    private $tableName1='player';    
    private $tableName2='point_transactions';    

    public function up() {
        $field = array(
            'point' => array(
                'type' => 'DOUBLE',
                'null' => true
            ),
        );

        if($this->utils->table_really_exists($this->tableName1)){
            if($this->db->field_exists('point', $this->tableName1)){
                $this->dbforge->modify_column($this->tableName1, $field);
            }
        }
        if($this->utils->table_really_exists($this->tableName2)){
            if($this->db->field_exists('point', $this->tableName2)){
                $this->dbforge->modify_column($this->tableName2, $field);
            }
        }
    }

    public function down() {
    }
}