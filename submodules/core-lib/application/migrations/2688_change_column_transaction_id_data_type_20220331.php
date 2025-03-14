<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_change_column_transaction_id_data_type_20220331 extends CI_Migration {

    private $tableName = 'seamless_missing_payout_report';

    public function up() {
        $field = array(
            'transaction_id' => array(
                'type' => 'VARCHAR',
                'constraint' => '255',
            ),
        );
        if($this->utils->table_really_exists($this->tableName)){
            if($this->db->field_exists('transaction_id', $this->tableName)){
                $this->dbforge->modify_column($this->tableName, $field);
            }
        }
    }

    public function down() {

        $field = array(
            'transaction_id' => array(
                'type' => 'BIGINT',
                'null' => true,
            ),
        );
        if($this->utils->table_really_exists($this->tableName)){
            if($this->db->field_exists('transaction_id', $this->tableName)){
                $this->dbforge->modify_column($this->tableName, $field);
            }
        }
    }
}