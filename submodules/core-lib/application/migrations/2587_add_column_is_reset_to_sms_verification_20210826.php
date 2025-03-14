<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_is_reset_to_sms_verification_20210826 extends CI_Migration {

    private $tableName = 'sms_verification';

    public function up() {
        
        $field = array(
            'is_reset' => array(
                'type' => 'TINYINT',
                'null' => false,
                'default' => 0
            )
        );

        if($this->utils->table_really_exists($this->tableName)){
            if(!$this->db->field_exists('is_reset', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $field);
            }
        }
    }

    public function down() {
        if($this->utils->table_really_exists($this->tableName)){
            if($this->db->field_exists('is_reset', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'is_reset');
            }
        }
    }
}