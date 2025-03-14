<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_to_payment_account_20220107 extends CI_Migration {
	private $tableName = 'payment_account';

    public function up() {
        $fields = array(
            'exchange' => array(
                'type' => 'VARCHAR',
                'constraint' => '200',
                'null' => true
            ),
        );

        if($this->utils->table_really_exists($this->tableName)){
            if(!$this->db->field_exists('exchange', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $fields);
            }
        }
    }

    public function down() {
        if($this->utils->table_really_exists($this->tableName)){
            if($this->db->field_exists('exchange', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'exchange');
            }
        }
    }
}
///END OF FILE/////