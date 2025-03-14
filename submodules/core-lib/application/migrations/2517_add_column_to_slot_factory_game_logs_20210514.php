<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_to_slot_factory_game_logs_20210514 extends CI_Migration {

    private $tableName = 'slot_factory_game_logs';

    public function up() {
        $fields = array(
            'bonus_date' => array(
                "type" => "DATETIME",
                "null" => true
            ),
        );

        if($this->utils->table_really_exists($this->tableName)){
            if(!$this->db->field_exists('bonus_date', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $fields);
            }
        }
    }

    public function down() {
        if($this->utils->table_really_exists($this->tableName)){
            if($this->db->field_exists('bonus_date', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'bonus_date');
            }
        }
    }
}