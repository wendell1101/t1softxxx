<?php

defined('BASEPATH') OR exit('No direct script access allowed');

//class Migration_add_table_total_player_game_day_timezone_20201110 extends CI_Migration {
class Migration_add_table_indian_bank_ifsc_info_20201216 extends CI_Migration {

	private $tableName = 'indian_bank_ifsc_info';
	public function up() {
		$fields = array(
			'id'			=> [ 'type' => 'BIGINT', 'null' => false, 'auto_increment' => TRUE, ],
			'deleted'		=> [ 'type' => 'boolean', 'null' => true, 'default' => 0 ] ,
			'ifsc_code'		=> [ 'type' => 'VARCHAR', 'constraint' => 32, 'null' => false ] ,
			'source_url'	=> [ 'type' => 'VARCHAR', 'constraint' => 255, 'null' => true ] ,
			'response'		=> [ 'type' => 'JSON', 'null' => true ] ,
			'ttl'			=> [ 'type' => 'INT' , 'null' => true ] ,
			'updated_at'	=> [ 'type' => 'DATETIME', 'null' => true ] ,
		);

		if (!$this->db->table_exists($this->tableName)) {
			$this->dbforge->add_field($fields);
			$this->dbforge->add_key('id', TRUE);
			$this->dbforge->create_table($this->tableName);
			// Add Index
	        $this->load->model('player_model');
	        $this->player_model->addIndex($this->tableName, 'ifsc1', 'ifsc_code');
	    }
	}

	public function down() {
		if($this->db->table_exists($this->tableName)){
			$this->dbforge->drop_table($this->tableName);
		}
	}
}
