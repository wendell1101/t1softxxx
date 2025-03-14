<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_columns_to_game_currency_list_20230429 extends CI_Migration {

    private $tableName1 = "game_platform_currency_list";
    private $tableName2 = "game_description_currency_list";
    private $tableName3 = "game_type_currency_list";

	public function up() {
		$fields = [
			'currency_list' => [
                'type' => 'JSON',
                'null' => true
            ],
        ];

		if($this->utils->table_really_exists($this->tableName1)){
            if(!$this->db->field_exists('currency_list', $this->tableName1)){
				$this->dbforge->add_column($this->tableName1, $fields);
            }
        }

		$fields = [
			'currency_list' => [
                'type' => 'JSON',
                'null' => true
            ],
			'game_type_code'=>[
                "type" => "VARCHAR",
                "constraint" => "100",
                "null" => true,
            ],
		];

		if($this->utils->table_really_exists($this->tableName2)){
            if(!$this->db->field_exists('currency_list', $this->tableName2)){
				$this->dbforge->add_column($this->tableName2, $fields);
            }
        }

        $fields = [
            'id' => [
                'type' => 'INT',
                'null' => false,
                'auto_increment' => true
            ],
            'game_platform_id' => [
                'type' => 'INT',
                'null' => false,
            ],
            'game_type_lang' => [
                "type" => "VARCHAR",
                "constraint" => "2000",
                "null" => false,
            ],
			'game_type_code'=>[
                "type" => "VARCHAR",
                "constraint" => "100",
                "null" => true,
            ],
			'currency_list' => [
                'type' => 'JSON',
                'null' => true
            ],
			'status'=>[
                'type' => 'tinyint',
                'null' => false,
            ],
            "created_at DATETIME DEFAULT CURRENT_TIMESTAMP" => [
                "null" => false
            ],
            "updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP" => [
                "null" => false
            ],
        ];

        if(! $this->db->table_exists($this->tableName3)){
            $this->dbforge->add_field($fields);
            $this->dbforge->add_key("id",true);
            $this->dbforge->create_table($this->tableName3);
            $this->player_model->addIndex($this->tableName3,"idx_game_type_code","game_type_code");
            $this->player_model->addIndex($this->tableName3,"idx_game_platform_id","game_platform_id");
        }

	}

	public function down() {
	}
}