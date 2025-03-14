<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_table_player_chat_access_tokens_20241128 extends CI_Migration {
	private $tableName = 'player_chat_access_tokens';
	public function up() {
		$fields = [
			"id" => [
				"type" => "VARCHAR",
				'constraint' => '100',
				"null" => false,
			],
			"user_id" => [
				'type' => 'VARCHAR',
				'constraint' => '100',
			],
			"room_id" => [
				'type' => 'VARCHAR',
				'constraint' => '100',
			],
			"revoked" => [
				'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 0,
                'null' => true,
			],
			"created_at" => [
				'type' => 'DATETIME',
				"null" => true
			],
			"updated_at" => [
				'type' => 'DATETIME',
				"null" => true
			],
			"expires_at" => [
				'type' => 'DATETIME',
				"null" => true
			],
		];
        if(! $this->db->table_exists($this->tableName)){
			$this->dbforge->add_field($fields);
			$this->dbforge->add_key("id",true);
			$this->dbforge->create_table($this->tableName);

			# add Index
			$this->load->model("player_model");
			$this->player_model->addIndex($this->tableName, 'idx_user_id', 'user_id');
			$this->player_model->addIndex($this->tableName, 'idx_expires_at', 'expires_at');
		}
	}

	public function down() {
		if($this->db->table_exists($this->tableName)){
			$this->dbforge->drop_table($this->tableName);
		}
	}
}
