<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_to_total_score_20211228 extends CI_Migration {

    private $tableName = 'total_score';

    public function up() {
        $fields = array(
            'action_log' => array(
                'type' => 'JSON',
                'null' => true
            ),
        );

        if($this->utils->table_really_exists($this->tableName)){
            if(!$this->db->field_exists('action_log', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $fields);
            }
        }
    }

    public function down() {
        if($this->utils->table_really_exists($this->tableName)){
            if($this->db->field_exists('action_log', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'action_log');
            }
        }
    }
}