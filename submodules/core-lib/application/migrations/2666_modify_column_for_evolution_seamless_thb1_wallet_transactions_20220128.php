<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_modify_column_for_evolution_seamless_thb1_wallet_transactions_20220128 extends CI_Migration {

    private $tableName='evolution_seamless_thb1_wallet_transactions';    

    public function up() {
        $field1 = array(
            'external_uniqueid' => array(
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true
            ),
        );
        $field2 = array(
            'gameId' => array(
                'type' => 'VARCHAR',
                'constraint' => '50',
                'null' => true
            ),
        );

        if($this->utils->table_really_exists($this->tableName)){
            if($this->db->field_exists('external_uniqueid', $this->tableName)){
                $this->dbforge->modify_column($this->tableName, $field1);
            }
            if($this->db->field_exists('gameId', $this->tableName)){
                $this->dbforge->modify_column($this->tableName, $field2);
            }
        }

    }

    public function down() {
    }
}