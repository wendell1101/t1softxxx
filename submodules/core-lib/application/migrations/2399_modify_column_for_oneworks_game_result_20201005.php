<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_modify_column_for_oneworks_game_result_20201005 extends CI_Migration {

    private $tableName='oneworks_game_result';    

    public function up() {
        $field = array(
            'first_ball' => array(
                'type' => 'VARCHAR',
                'constraint' => '50',
                'null' => true
            ),
            'second_ball' => array(
                'type' => 'VARCHAR',
                'constraint' => '50',
                'null' => true
            ),
            'third_ball' => array(
                'type' => 'VARCHAR',
                'constraint' => '50',
                'null' => true
            )
        );

        if($this->utils->table_really_exists($this->tableName)){
            if($this->db->field_exists('first_ball', $this->tableName) && $this->db->field_exists('second_ball', $this->tableName) && $this->db->field_exists('third_ball', $this->tableName)){
                $this->dbforge->modify_column($this->tableName, $field);
            }
        }

    }

    public function down() {
    }
}