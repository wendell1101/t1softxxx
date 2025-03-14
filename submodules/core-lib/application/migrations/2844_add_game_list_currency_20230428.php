<?php

defined("BASEPATH") OR exit("No direct script access allowed");

class Migration_add_game_list_currency_20230428 extends CI_Migration
{

    private $tableName1 = "game_platform_currency_list";
    private $tableName2 = "game_description_currency_list";

    public function up()
    {
        $fields = [
            'id' => [
                'type' => 'INT',
                'null' => false,
                'auto_increment' => true
            ],
            'system_code' => [
                "type" => "VARCHAR",
                "constraint" => "200",
                "null" => true
            ],
            "created_at DATETIME DEFAULT CURRENT_TIMESTAMP" => [
                "null" => false
            ],
            "updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP" => [
                "null" => false
            ],
        ];

        if(! $this->db->table_exists($this->tableName1)){
            $this->dbforge->add_field($fields);
            $this->dbforge->add_key("id",true);
            $this->dbforge->create_table($this->tableName1);
        }

        $fields = [
            'id' => [
                'type' => 'INT',
                'null' => false,
                'auto_increment' => true
            ],
            'game_platform_id'=>[
                'type' => 'INT',
                'null' => false,
            ],
            'external_game_id'=> [
                "type" => "VARCHAR",
                "constraint" => "300",
                "null" => false,
            ],
            'game_name' => [
                "type" => "VARCHAR",
                "constraint" => "500",
                "null" => false,
            ],
            'game_code' => [
                "type" => "VARCHAR",
                "constraint" => "200",
                "null" => false,
            ],
            'demo_link' => [
                "type" => "VARCHAR",
                "constraint" => "500",
                "null" => true,
            ],
            'flash_enabled' => [
                'type' => 'tinyint',
                "null" => true,
            ],
            'html_five_enabled' => [
                'type' => 'tinyint',
                "null" => true,
            ],
            'mobile_enabled' => [
                'type' => 'tinyint',
                "null" => true,
            ],
            'enabled_freespin' => [
                'type' => 'tinyint',
                "null" => true,
            ],
            'enabled_on_ios' => [
                'type' => 'tinyint',
                "null" => true,
            ],
            'enabled_on_android' => [
                'type' => 'tinyint',
                "null" => true,
            ],
            'release_date' => [
                'type' => 'datetime',
                "null" => true,
            ],
            'attributes' => [
                "type" => "JSON",
                "null" => true,
            ],
            'tags' => [
                "type" => "JSON",
                "null" => true,
            ],
            'status' => [
                'type' => 'tinyint',
                "null" => false,
            ],
            "created_at DATETIME DEFAULT CURRENT_TIMESTAMP" => [
                "null" => false
            ],
            "updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP" => [
                "null" => false
            ],
        ];

        if(! $this->db->table_exists($this->tableName2)){
            $this->dbforge->add_field($fields);
            $this->dbforge->add_key("id",true);
            $this->dbforge->create_table($this->tableName2);

            // add Index
            $this->load->model("player_model");
            $this->player_model->addIndex($this->tableName2,"idx_unique_game_id","game_platform_id, external_game_id");
            $this->player_model->addIndex($this->tableName2,"idx_external_game_id","external_game_id");
        }

    }

    public function down() {
    }
}

