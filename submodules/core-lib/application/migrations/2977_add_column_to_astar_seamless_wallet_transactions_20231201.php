<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_to_astar_seamless_wallet_transactions_20231201 extends CI_Migration {

    private $tableName = 'astar_seamless_wallet_transactions';

    public function up() {
        $field = array(
            'settlement_amount' => array(
                'type' => 'double',
                'null' => true,
            ),
        );

        if($this->utils->table_really_exists($this->tableName)){
            if(!$this->db->field_exists('settlement_amount', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $field);
            }
        }
    }

    public function down() {
        if($this->utils->table_really_exists($this->tableName)){
            if($this->db->field_exists('settlement_amount', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'settlement_amount');
            }
        }
    }
}