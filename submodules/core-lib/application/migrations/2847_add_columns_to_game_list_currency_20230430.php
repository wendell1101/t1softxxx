<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_columns_to_game_list_currency_20230430 extends CI_Migration {

    private $tableName = "game_description_currency_list";

	public function up() {
		$fields = [
			'unique_id' => [
                "type" => "VARCHAR",
                "constraint" => "320",
                'null' => true
            ],
        ];

		if($this->utils->table_really_exists($this->tableName)){
            if(!$this->db->field_exists('unique_id', $this->tableName)){
				$this->dbforge->add_column($this->tableName, $fields);
            }
        }
	}

	public function down() {
	}
}