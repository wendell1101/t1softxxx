<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_to_t1lottery_transactions_20210829 extends CI_Migration {
    
    private $tableName ='t1lottery_transactions';

    public function up() {
        $field = array(
            'number' => array(
                'type' => 'varchar',
                'constraint' => 20,
                'null' => true
            ),
            'opencode' => array(
                'type' => 'varchar',
                'constraint' => 20,
                'null' => true
            ),
        );

        if($this->utils->table_really_exists($this->tableName)){
            if(!$this->db->field_exists('number', $this->tableName)){  
                $this->dbforge->add_column($this->tableName, $field);                      
            }
        }
    }

    public function down() {
        if($this->utils->table_really_exists($this->tableName)){
            if($this->db->field_exists('number', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'number');
            }
            if($this->db->field_exists('opencode', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'opencode');
            }
        }
    }
}