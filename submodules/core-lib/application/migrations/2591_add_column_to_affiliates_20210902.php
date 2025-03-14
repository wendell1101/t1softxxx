<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_to_affiliates_20210902 extends CI_Migration {

    private $tableName='affiliates';

    public function up() {
        $fields = array(
            'vip_level_id' => array(
                'type' => 'INT',
                'null' => true,
            ),
        );
        if($this->utils->table_really_exists($this->tableName)){
            if(!$this->db->field_exists('vip_level_id', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $fields);
            }
        }
    }

    public function down() {
        if( $this->utils->table_really_exists($this->tableName)){
            if($this->db->field_exists('vip_level_id', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'vip_level_id');
            }
        }
    }
}