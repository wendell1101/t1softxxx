<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_table_player_profile_update_log_20241218 extends CI_Migration {
	private $tableName = 'player_profile_update_log';
	public function up() {
		$fields = [
			'id' => [
				'type' => 'BIGINT',
				'auto_increment' => TRUE,
            ],
			"player_id" => [
                'type' => 'INT',
                'constraint' => '12',
                'null' => false,
			],
			"field_name" => [
				'type' => 'VARCHAR',
				'constraint' => '100',
                'null' => false,
			],
			"update_count" => [
				'type' => 'TINYINT',
                'default' => 0,
			],
            'created_at DATETIME DEFAULT CURRENT_TIMESTAMP' => [
                'null' => false,
            ],
            'updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP' => [
                'null' => false,
            ]
		];
        
        if(! $this->db->table_exists($this->tableName)){
			$this->dbforge->add_field($fields);
			$this->dbforge->add_key("id",true);
			$this->dbforge->create_table($this->tableName);

			# add Index
			$this->load->model("player_model");
			$this->player_model->addIndex($this->tableName, 'idx_player_id', 'player_id');
			$this->player_model->addIndex($this->tableName, 'idx_field_name', 'field_name');
            $this->player_model->addIndex($this->tableName, 'idx_created_at', 'created_at');
            $this->player_model->addIndex($this->tableName, 'idx_updated_at', 'updated_at');
		}
	}

	public function down() {
		if($this->db->table_exists($this->tableName)){
			$this->dbforge->drop_table($this->tableName);
		}
	}
}
