<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_to_beter_sports_seamless_wallet_transactions_20230619 extends CI_Migration {

	private $tableName = 'beter_sports_seamless_wallet_transactions';

	public function up() {
		$fields = array(
			'coefficient' => array(
                'type' => 'DOUBLE',
                'null' => true
			),
			'result_type' => array(
                'type' => 'VARCHAR',
				'constraint' => '10',
                'null' => true
			),
			'game_type' => array(
                'type' => 'VARCHAR',
				'constraint' => '20',
                'null' => true
			),
			'bonus_id' => array(
                'type' => 'INT',
				'constraint' => '10',
                'null' => true
			),
			'bonus_template_id' => array(
                'type' => 'INT',
				'constraint' => '10',
                'null' => true
			),
		);
		

		if($this->utils->table_really_exists($this->tableName)){
            if(!$this->db->field_exists('coefficient', $this->tableName)){
				$this->dbforge->add_column($this->tableName, $fields);
            }
        }
	}

	public function down() {
		if($this->utils->table_really_exists($this->tableName)){
            if($this->db->field_exists('coefficient', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'coefficient');
            }
        }
	}
}