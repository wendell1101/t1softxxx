<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_to_sexy_baccarat_transactions_20210915 extends CI_Migration {

    private $tableName = 'sexy_baccarat_transactions';

    public function up() {
        
        $field = array(
            'tip_amount' => array(
                'type' => 'DOUBLE',
                'null' => true
            )
        );

        if($this->utils->table_really_exists($this->tableName)){
            if(!$this->db->field_exists('tip_amount', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $field);
            }
        }
    }

    public function down() {
        if($this->utils->table_really_exists($this->tableName)){
            if($this->db->field_exists('tip_amount', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'tip_amount');
            }
        }
    }
}