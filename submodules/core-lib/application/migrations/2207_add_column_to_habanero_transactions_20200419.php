<?php

defined("BASEPATH") OR exit("No direct script access allowed");

class Migration_add_column_to_habanero_transactions_20200419 extends CI_Migration
{
	private $tableName = 'habanero_transactions';

    public function up() {

        $fields = array(
            'elapsed_time' => array(
                'type' => 'INT',
                'constraint' => '11',
                'null' => true,
            ),
        );

        if(!$this->db->field_exists('elapsed_time', $this->tableName)){
            $this->dbforge->add_column($this->tableName, $fields);                        
        }
    }

    public function down() {
        if($this->db->field_exists('elapsed_time', $this->tableName)){
            $this->dbforge->drop_column($this->tableName, 'elapsed_time');
        }
    }
}