<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_to_promocmssetting_20230217 extends CI_Migration {

    private $tableName = 'promocmssetting';

    public function up() {
        $fields = array(
            'claim_button_url' => array(
                'type' => 'varchar',
                'constraint' => '100',
                'null' => true,
            ),
        );

        if($this->utils->table_really_exists($this->tableName)){
            if(!$this->db->field_exists('claim_button_url', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $fields);
            }
        }
    }

    public function down() {
        if($this->utils->table_really_exists($this->tableName)){
            if($this->db->field_exists('claim_button_url', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'claim_button_url');
            }
        }
    }
}