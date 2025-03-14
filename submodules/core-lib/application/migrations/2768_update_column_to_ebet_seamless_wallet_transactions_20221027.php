<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_update_column_to_ebet_seamless_wallet_transactions_20221027 extends CI_Migration {

    private $tableName = 'ebet_seamless_wallet_transactions';

    public function up() {

        $fields = array(
            'round_id' => array(    
                'type' => 'VARCHAR',            
                'constraint' => '100',
                'null' => true,
            )
        );
        if($this->utils->table_really_exists($this->tableName)){
            if($this->db->field_exists('round_id', $this->tableName)){
                $this->dbforge->modify_column($this->tableName, $fields);
            }
        }
    }

    public function down() {
        
    }
}