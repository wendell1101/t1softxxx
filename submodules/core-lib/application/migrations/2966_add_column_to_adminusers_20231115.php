<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_to_adminusers_20231115 extends CI_Migration {

    private $tableName = 'adminusers';

    public function up() {
        $field = array(
            'notified_at DATETIME DEFAULT CURRENT_TIMESTAMP' => array(
				'null' => false,
            ),
        );

        if($this->utils->table_really_exists($this->tableName)){
            if(!$this->db->field_exists('notified_at', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $field);
            }
        }
    }

    public function down() {
        if($this->utils->table_really_exists($this->tableName)){
            if($this->db->field_exists('notified_at', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'notified_at');
            }
        }
    }
}