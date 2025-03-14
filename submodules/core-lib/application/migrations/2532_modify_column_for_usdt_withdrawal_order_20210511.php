<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_modify_column_for_usdt_withdrawal_order_20210511 extends CI_Migration {

    private $tableName = 'usdt_withdrawal_order';

    public function up() {

        $field_modify_column = array(
            'transfered_usdt' => array(
                'name' => 'transfered_crypto',
                "type" => "DOUBLE",
                "null" => true
            ),
        );

        $field_add_column = array(
            'crypto_currency' => array(
                'type' => 'VARCHAR',
                'constraint' => '16',
                'default' => 'USDT',
            ),
        );

        if($this->utils->table_really_exists($this->tableName)){
            if(!$this->db->field_exists('crypto_currency', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $field_add_column);
            }
        }

        if($this->utils->table_really_exists($this->tableName)){
            if($this->db->field_exists('transfered_usdt', $this->tableName)){
                $this->dbforge->modify_column($this->tableName, $field_modify_column);
            }
        }
    }

    public function down() {
        if($this->utils->table_really_exists($this->tableName)){
            if($this->db->field_exists('transfered_usdt', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'transfered_usdt');
            }
        }
    }
}