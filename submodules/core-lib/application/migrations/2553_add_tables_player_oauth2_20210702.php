<?php

defined("BASEPATH") OR exit("No direct script access allowed");

class Migration_add_tables_player_oauth2_20210702 extends CI_Migration
{

	public function up()
	{
		// ====player_oauth2_clients=================================
		$tableName='player_oauth2_clients';
		$fields = [
			"id" => [
				"type" => "varchar",
				'constraint' => '36',
				"null" => false,
			],
			"user_id" => [
				'type' => 'BIGINT',
				'null' => true,
				'unsigned' => true,
			],
			"name" => [
				'type' => 'VARCHAR',
				'constraint' => '255',
				"null" => false
			],
			"secret" => [
				'type' => 'VARCHAR',
				'constraint' => '100',
				"null" => true
			],
			"provider" => [
				'type' => 'VARCHAR',
				'constraint' => '255',
				"null" => true
			],
			"redirect" => [
				'type' => 'TEXT',
				"null" => true
			],
			"personal_access_client" => [
				'type' => 'boolean',
				"null" => true
			],
			"password_client" => [
				'type' => 'boolean',
				"null" => true
			],
			"revoked" => [
				'type' => 'boolean',
				"null" => true
			],
			"confidential" => [
				'type' => 'boolean',
				"null" => true
			],
			"created_at" => [
				'type' => 'DATETIME',
				"null" => true
			],
			"updated_at" => [
				'type' => 'DATETIME',
				"null" => true
			],
		];

		if(! $this->db->table_exists($tableName)){
			$this->dbforge->add_field($fields);
			$this->dbforge->add_key("id",true);
			$this->dbforge->create_table($tableName);

			# add Index
			$this->load->model("player_model");
			$this->player_model->addIndex($tableName, 'idx_user_id', 'user_id');
		}
		// ====player_oauth2_clients=================================

		// ====player_oauth2_access_tokens=================================
		$tableName='player_oauth2_access_tokens';
		$fields = [
			"id" => [
				"type" => "VARCHAR",
				'constraint' => '100',
				"null" => false,
			],
			"user_id" => [
				'type' => 'BIGINT',
				'null' => true,
				'unsigned' => true,
			],
			"client_id" => [
				'type' => 'VARCHAR',
				'constraint' => '36',
				"null" => false,
			],
			"name" => [
				'type' => 'VARCHAR',
				'constraint' => '255',
				"null" => false
			],
			"scopes" => [
				'type' => 'json',
				"null" => true
			],
			"revoked" => [
				'type' => 'boolean',
				"null" => true
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

		if(! $this->db->table_exists($tableName)){
			$this->dbforge->add_field($fields);
			$this->dbforge->add_key("id",true);
			$this->dbforge->create_table($tableName);

			# add Index
			$this->load->model("player_model");
			$this->player_model->addIndex($tableName, 'idx_user_id', 'user_id');
		}
		// ====player_oauth2_access_tokens=================================

		// ====player_oauth2_personal_access_clients=================================
		$tableName='player_oauth2_personal_access_clients';
		$fields = [
			"id" => [
				'type' => 'BIGINT',
				'null' => false,
				'auto_increment' => TRUE,
				'unsigned' => true,
			],
			"client_id" => [
				'type' => 'VARCHAR',
				'constraint' => '36',
				"null" => false,
			],
			"created_at" => [
				'type' => 'DATETIME',
				"null" => true
			],
			"updated_at" => [
				'type' => 'DATETIME',
				"null" => true
			],
		];

		if(! $this->db->table_exists($tableName)){
			$this->dbforge->add_field($fields);
			$this->dbforge->add_key("id",true);
			$this->dbforge->create_table($tableName);
		}
		// ====player_oauth2_personal_access_clients=================================

		// ====player_oauth2_refresh_tokens=================================
		$tableName='player_oauth2_refresh_tokens';
		$fields = [
			"id" => [
				'type' => 'BIGINT',
				'null' => false,
				'auto_increment' => TRUE,
				'unsigned' => true,
			],
			"access_token_id" => [
				'type' => 'VARCHAR',
				'constraint' => '100',
				"null" => false,
			],
			"revoked" => [
				'type' => 'boolean',
				"null" => true
			],
			"expires_at" => [
				'type' => 'DATETIME',
				"null" => true
			],
		];

		if(! $this->db->table_exists($tableName)){
			$this->dbforge->add_field($fields);
			$this->dbforge->add_key("id",true);
			$this->dbforge->create_table($tableName);

			# add Index
			$this->load->model("player_model");
			$this->player_model->addIndex($tableName, 'idx_access_token_id', 'access_token_id');
		}
		// ====player_oauth2_refresh_tokens=================================

	}

	public function down()
	{
	}
}
