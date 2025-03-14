<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_to_game_logs_stream_20240705 extends CI_Migration {

    private $tableName = 'game_logs_stream';

    public function up() {
        $field = array(
           'additional_details' => array(
                'type' => 'JSON',
                'null' => true
            ),
        );

        if($this->utils->table_really_exists($this->tableName)){
            if(!$this->db->field_exists('additional_details', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $field);
            }
        }
    }

    public function down() {
        if($this->utils->table_really_exists($this->tableName)){
            if($this->db->field_exists('additional_details', $this->tableName)) {
                $this->dbforge->drop_column($this->tableName, 'additional_details');
            }
        }
    }
}
