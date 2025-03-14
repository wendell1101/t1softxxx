<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_modify_column_for_evolution_seamless_thb1_wallet_transactions_20230614 extends CI_Migration {

    private $tableName='evolution_seamless_thb1_wallet_transactions';    

    public function up() {
        $field1 = array(
            'transactionId' => array(
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true
            ),
        );
        $field2 = array(
            'transactionRefId' => array(
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true
            ),
        );
        $field3 = array(
            'refundedTransactionId' => array(
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true
            ),
        );

        if($this->utils->table_really_exists($this->tableName)){
            if($this->db->field_exists('transactionId', $this->tableName)){
                $this->dbforge->modify_column($this->tableName, $field1);
            }
            if($this->db->field_exists('transactionRefId', $this->tableName)){
                $this->dbforge->modify_column($this->tableName, $field2);
            }
            if($this->db->field_exists('refundedTransactionId', $this->tableName)){
                $this->dbforge->modify_column($this->tableName, $field3);
            }
        }

    }

    public function down() {
    }
}