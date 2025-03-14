<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_table_player_points_20220318 extends CI_Migration {
    
	private $tableName = 'player_points';

	public function up() {
		$fields = array(
			'id' => array(
				'type' => 'BIGINT',
				'null' => false,
				'auto_increment' => TRUE,
			),
            'player_id' => array(
                'type' => 'BIGINT',				
				'null' => false,
			),
            'type' => array( 
                'type' => 'VARCHAR',
				'constraint' => '20',
				'null' => true,
            ),
            'points' => array( 
                'type' => 'DOUBLE',
                'null' => true
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
            $this->player_model->addIndex($this->tableName, 'idx_player_id', 'player_id');
            $this->player_model->addIndex($this->tableName, 'idx_type', 'type');            
            $this->player_model->addIndex($this->tableName, 'idx_updated_at', 'updated_at');            	        
	    }
	}

	public function down() {
		if(!$this->db->table_exists($this->tableName)){
			$this->dbforge->drop_table($this->tableName);
		}
	}
}
