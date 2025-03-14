<?php

defined("BASEPATH") OR exit("No direct script access allowed");

class Migration_add_table_game_logs_table_info_20210205 extends CI_Migration
{

    private $tableName = "game_logs_table_info";

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
            "game_type_id" => array(
                "type" => "INT",
                "null" => false
            ),
            'table_identifier' => [
                'type' => 'VARCHAR',
                'constraint' => '30',
                'null' => false
            ],
            "external_uniqueid" => array(
                "type" => "VARCHAR",
                "constraint" => "100",
                "null" => false
            ),
            "uniqueid" => array(
                "type" => "VARCHAR",
                "constraint" => "100",
                "null" => false
            ),
            "created_at DATETIME DEFAULT CURRENT_TIMESTAMP" => array(
                "null" => false
            ),
            "updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP" => array(
                "null" => false
            ),
        );

        if(! $this->db->table_exists($this->tableName)){
            $this->dbforge->add_field($fields);
            $this->dbforge->add_key("id",true);
            $this->dbforge->create_table($this->tableName);

            # add Index
            $this->load->model("player_model");
            $this->player_model->addIndex($this->tableName,"idx_glti_game_platform_id","game_platform_id");
            $this->player_model->addIndex($this->tableName,"idx_glti_game_type_id","game_type_id");
            $this->player_model->addIndex($this->tableName,"idx_glti_external_uniqueid","external_uniqueid");
            $this->player_model->addIndex($this->tableName,"idx_glti_table_identifier","table_identifier");
            $this->player_model->addUniqueIndex($this->tableName, 'idx_glti_uniqueid', 'uniqueid');
        }
    }

    public function down()
    {
        if($this->db->table_exist($this->tableName)){
            $this->dbforge->drop_table($this->tableName);
        }
    }
}