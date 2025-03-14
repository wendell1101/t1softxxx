<?php

defined("BASEPATH") OR exit("No direct script access allowed");

class Migration_add_table_speed_detect_log_20211126 extends CI_Migration
{

    private $tableName = "speed_detect_log";

    public function up()
    {
        $fields = array(
            'id' => array(
                'type' => 'INT',
                'unsigned' => TRUE,
                'auto_increment' => TRUE,
            ),
            "player_id" => array(
                "type" => "BIGINT",
                'null' => true,
            ),
            'user_agent' => array(
				'type' => 'VARCHAR',
				'constraint' => '500',
				'null' => true,
			),
            'ip' => array(
				'type' => 'VARCHAR',
				'constraint' => '32',
				'null' => false,
			),
            'domain' => array(
				'type' => 'VARCHAR',
				'constraint' => '50',
				'null' => false,
			),
            'spent_ms' => array(
				'type' => 'INT',
				'null' => true,
			),
            'created_at DATETIME DEFAULT CURRENT_TIMESTAMP' => array(
                'null' => false,
            )
        );

        if(! $this->db->table_exists($this->tableName)){
            $this->dbforge->add_field($fields);
            $this->dbforge->add_key("id",true);
            $this->dbforge->create_table($this->tableName);

            // add Index
            $this->load->model("player_model");
            $this->player_model->addIndex($this->tableName, 'idx_player_id', 'player_id');
            $this->player_model->addIndex($this->tableName, 'idx_ip', 'ip');
            $this->player_model->addIndex($this->tableName, 'idx_domain', 'domain');
            $this->player_model->addIndex($this->tableName, 'idx_spent_ms', 'spent_ms');
            $this->player_model->addIndex($this->tableName, 'idx_created_at', 'created_at');

        }
    }

    public function down()
    {
        if($this->db->table_exists($this->tableName)){
            $this->dbforge->drop_table($this->tableName);
        }
    }
}