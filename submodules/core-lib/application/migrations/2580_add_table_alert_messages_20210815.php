<?php

defined("BASEPATH") OR exit("No direct script access allowed");

class Migration_add_table_alert_messages_20210815 extends CI_Migration
{

	private $tableName = "alert_messages";

	public function up()
	{
		$fields = array(
			"id" => array(
				"type" => "BIGINT",
				"null" => false,
				"auto_increment" => true
			),
			"alert_type" => array(
				"type" => "INT",
				"null" => false,
			),
			"from_type" => array(
				"type" => "INT",
				"null" => false,
			),
			"player_id" => array(
				"type" => "INT",
				"null" => true,
			),
			"context" => array(
				"type" => "JSON",
				"null" => true
			),
			"message" => array(
				"type" => "TEXT",
				"null" => true
			),
			'created_at' => array(
				"type" => "DATETIME",
				'null' => false,
			),
		);

		if(! $this->db->table_exists($this->tableName)){
			$this->dbforge->add_field($fields);
			$this->dbforge->add_key("id",true);
			$this->dbforge->create_table($this->tableName);

			// # add Index
			$this->load->model("player_model");
			$this->player_model->addIndex($this->tableName, 'idx_player_id', 'player_id');
			$this->player_model->addIndex($this->tableName, 'idx_created_at', 'created_at');
		}

		$detailTableName='alarm_message_read_info';
		$fields = array(
			"id" => array(
				"type" => "BIGINT",
				"null" => false,
				"auto_increment" => true
			),
			"alert_message_id" => array(
				"type" => "INT",
				"null" => false,
			),
			"admin_user_id" => array(
				"type" => "INT",
				"null" => false
			),
			"status" => array(
				"type" => "INT",
				"null" => true
			),
			'updated_at' => array(
				"type" => "DATETIME",
				'null' => false,
			),
		);
		if(! $this->db->table_exists($detailTableName)){
			$this->dbforge->add_field($fields);
			$this->dbforge->add_key("id",true);
			$this->dbforge->create_table($detailTableName);

			// # add Index
			$this->load->model("player_model");
			$this->player_model->addIndex($detailTableName, 'idx_alert_message_id', 'alert_message_id');
			$this->player_model->addIndex($detailTableName, 'idx_admin_user_id', 'admin_user_id');
			$this->player_model->addIndex($detailTableName, 'idx_updated_at', 'updated_at');
		}
	}

	public function down()
	{
	}
}
