<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_to_hogaming_idr1_game_logs_20220124 extends CI_Migration {
    private $tableName = 'hogaming_idr1_game_logs';

    public function up() {

        $field = array(
            'valid_bet' => array(
                'type' => 'DOUBLE',
                'null' => true,
            )

        );

        if($this->utils->table_really_exists($this->tableName)){
            if(!$this->db->field_exists('valid_bet', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $field);
            }
        }
    }

    public function down() {
        if($this->utils->table_really_exists($this->tableName)){
            if($this->db->field_exists('valid_bet', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'valid_bet');
            }
        }
    }
}
