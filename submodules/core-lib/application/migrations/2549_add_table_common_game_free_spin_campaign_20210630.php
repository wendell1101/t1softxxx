<?php

defined("BASEPATH") OR exit("No direct script access allowed");

class Migration_add_table_common_game_free_spin_campaign_20210630 extends CI_Migration
{

    private $tableName = "common_game_free_spin_campaign";

    public function up()
    {
        $fields = array(
            "id" => array(
                "type" => "BIGINT",
                "null" => false,
                "auto_increment" => true
            ),
            "game_platform_id" => array(
                "type" => "INT",
                "null" => false
            ),
            "campaign_id" => array(
                "type" => "INT",
                "null" => true
            ),
            "name" => array(
                'type' => 'VARCHAR',
                'constraint' => '50',
                "null" => true
            ),
            "num_of_games" => array(
                "type" => "INT",
                "null" => true
            ),
            "status" => array(
                'type' => 'VARCHAR',
                'constraint' => '30',
                "null" => true
            ),
            "currency" => array(
                'type' => 'VARCHAR',
                'constraint' => '10',
                "null" => true
            ),
            "start_time" => array(
                'type' => 'DATETIME',
                'null' => true,
            ),
            "end_time" => array(
                'type' => 'DATETIME',
                'null' => true,
            ),
            "is_for_new_player" => array(
                'type' => 'BOOLEAN',
                'null' => true,
                'default' => 0,
            ),
            "extra" => array(
                'type' => 'JSON',
                'null' => true,
            ),
            # SBE additional info
            'response_result_id' => array(
                'type' => 'INT',
                'constraint' => '11',
                'null' => true,
            ),
            'external_uniqueid' => array(
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true,
            ),
            'created_at DATETIME DEFAULT CURRENT_TIMESTAMP' => array(
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

            # add Index
            $this->load->model("player_model");
            $this->player_model->addIndex($this->tableName, 'idx_campaign_id', 'campaign_id');
            $this->player_model->addIndex($this->tableName, 'idx_game_platform_id', 'game_platform_id');
            $this->player_model->addIndex($this->tableName, 'idx_external_uniqueid', 'external_uniqueid',true);
            $this->player_model->addIndex($this->tableName, 'idx_start_time', 'start_time');
            $this->player_model->addIndex($this->tableName, 'idx_end_time', 'end_time');
        }
    }

    public function down()
    {
        if($this->db->table_exists($this->tableName)){
            $this->dbforge->drop_table($this->tableName);
        }
    }
}