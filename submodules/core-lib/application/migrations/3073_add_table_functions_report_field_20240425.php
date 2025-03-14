<?php

defined("BASEPATH") OR exit("No direct script access allowed");

class Migration_add_table_functions_report_field_20240425 extends CI_Migration
{

    private $tableName = "functions_report_field";

    public function up()
    {
        $fields = [
            "id" => [
                "type" => "BIGINT",
                "null" => false,
                "auto_increment" => true
            ],
            "roleId" => [
                "type" => "INT",
                "constraint" => "10",
                "null" => false
            ],
            "funcCode" => [
                "type" => "VARCHAR",
                "constraint" => "255",
                "null" => false
            ],
            "fields" => [
                "type" => "TEXT",
            ],
            "created_at DATETIME DEFAULT CURRENT_TIMESTAMP" => [
                "null" => false
            ],
            "updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP" => [
                "null" => false
            ],
        ];

        if(!$this->db->table_exists($this->tableName)){
            $this->dbforge->add_field($fields);
            $this->dbforge->add_key("id",true);
            $this->dbforge->create_table($this->tableName);

            # add Index
            $this->load->model("player_model");
            $this->player_model->addIndex($this->tableName,"idx_roleId_funcCode","roleId,funcCode");
            $this->player_model->addUniqueIndex($this->tableName,"idx_unique_roleId_funcCode","roleId,funcCode");
        }
    }

    public function down()
    {
        if($this->db->table_exist($this->tableName)){
            $this->dbforge->drop_table($this->tableName);
        }
    }
}
