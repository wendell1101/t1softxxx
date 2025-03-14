<?php

defined("BASEPATH") OR exit("No direct script access allowed");

class Migration_add_table_request_cost_hourly_report_20211112 extends CI_Migration
{

    private $tableName = "request_cost_hourly_report";

    public function up()
    { 
        $fields = array(
            "id" => array(
                "type" => "BIGINT",
                "null" => false,
                "auto_increment" => true
            ),
            'date' => array(
                'type' => 'DATETIME',
                'null' => true,
            ),
            "unique_id" => array(
                'type' => 'BIGINT',
                'null' => true,
            ),
            "external_system_id" => array(
                'type' => 'INT',
                'null' => true,
            ),
            'min_ms' => array(
				'type' => 'INT',
				'null' => true,
			),
            'max_ms' => array(
                'type' => 'INT',
                'null' => true,
            ),
            'avg_ms' => array(
                'type' => 'DECIMAL',
                'constraint' => '10,4',
                'null' => true,
            ),
            'count' => array(
                'type' => 'INT',
                'null' => true,
            ),
            'slow_3s' => array(
                'type' => 'INT',
                'null' => true,
            ),
            'p_3s' => array(
                'type' => 'DECIMAL',
                'constraint' => '10,4',
                'null' => true,
            ),
            'create_at DATETIME DEFAULT CURRENT_TIMESTAMP' => array(
                'null' => false,
            ),
            'updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP' => array(
                'null' => false,
            )
        );

        if(! $this->db->table_exists($this->tableName)){
            $this->dbforge->add_field($fields);
            $this->dbforge->add_key("id",true);
            $this->dbforge->create_table($this->tableName);

            // # add Index
            $this->load->model("player_model");
            $this->player_model->addIndex($this->tableName, 'idx_date', 'date');
            $this->player_model->addIndex($this->tableName, 'idx_unique_id', 'unique_id', true);
        }
    }

    public function down()
    {
        if($this->db->table_exists($this->tableName)){
            $this->dbforge->drop_table($this->tableName);
        }
    }
}