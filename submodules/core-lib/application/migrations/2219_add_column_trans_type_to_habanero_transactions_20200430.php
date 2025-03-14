<?php

defined("BASEPATH") OR exit("No direct script access allowed");

class Migration_add_column_trans_type_to_habanero_transactions_20200430 extends CI_Migration
{
    private $tableName = 'habanero_transactions';

    public function up() {

        $fields = array(
            'trans_type' => array(
                'type' => 'VARCHAR',
                'constraint' => '20',
                'null' => true
            ),
        );

        if(!$this->db->field_exists('trans_type', $this->tableName)){
            $this->dbforge->add_column($this->tableName, $fields);
        }
    }

    public function down() {
        if($this->db->field_exists('trans_type', $this->tableName)){
            $this->dbforge->drop_column($this->tableName, 'trans_type');
        }
    }
}