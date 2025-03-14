<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_alter_column_on_beter_seamless_wallet_transactions_20230420 extends CI_Migration {

	private $tableName = 'beter_seamless_wallet_transactions';

	public function up() {

        $fields = array(
            'game_id' => array(    
                'type' => 'VARCHAR',            
                'constraint' => '50',
                'null' => true,
            )
        );
        if($this->utils->table_really_exists($this->tableName)){
            if($this->db->field_exists('game_id', $this->tableName)){
                $this->dbforge->modify_column($this->tableName, $fields);
            }
        }

	}

	public function down() {}
}