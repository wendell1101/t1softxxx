<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_table_game_logs_export_hour_20241211 extends CI_Migration {
	private $tableName = 'game_logs_export_hour';
	public function up() {
		$fields = array(
			'id' => array(
				'type' => 'BIGINT',
				'null' => false,
				'auto_increment' => TRUE,
            ),
            'date' => array(
				'type' => 'VARCHAR',
				'constraint' => '20',
				'null' => true,
			),
            'date_hour' => array(
				'type' => 'VARCHAR',
				'constraint' => '20',
				'null' => true,
			),
            'file_name' => array(
				'type' => 'VARCHAR',
				'constraint' => '300',
				'null' => true,
			),
            'created_at DATETIME DEFAULT CURRENT_TIMESTAMP' => array(
                'null' => false,
            ),
            'updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP' => array(
                'null' => false,
            ),
		);

		if(!$this->db->table_exists($this->tableName)){
			$this->dbforge->add_field($fields);
			$this->dbforge->add_key('id', TRUE);
			$this->dbforge->create_table($this->tableName);
			# Add Index
	        $this->load->model('player_model');	        
            $this->player_model->addIndex($this->tableName, 'idx_date_hour', 'date_hour');
            $this->player_model->addIndex($this->tableName, 'idx_date', 'date');       
	    }
	}

	public function down() {
		if($this->db->table_exists($this->tableName)){
			$this->dbforge->drop_table($this->tableName);
		}
	}
}
