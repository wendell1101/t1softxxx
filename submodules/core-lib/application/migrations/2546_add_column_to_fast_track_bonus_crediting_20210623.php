<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_to_fast_track_bonus_crediting_20210623 extends CI_Migration {

    private $tableName = 'fast_track_bonus_crediting';

    public function up() {
        $fields = array(
            'request_params' => array(
                'type' => 'json',
                'null' => true
            ),
        );

        if($this->utils->table_really_exists($this->tableName)){
            if(!$this->db->field_exists('request_params', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $fields);
            }
        }
    }

    public function down() {
        if($this->utils->table_really_exists($this->tableName)){
            if($this->db->field_exists('request_params', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'request_params');
            }
        }
    }
}
