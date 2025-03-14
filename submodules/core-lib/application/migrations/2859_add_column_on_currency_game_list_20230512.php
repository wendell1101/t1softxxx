<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_on_currency_game_list_20230512 extends CI_Migration {

    private $tableName = "game_description_currency_list";

	public function up() {
		$fields = [
			'game_name_json' => [
                "type" => "JSON",
                'null' => false
            ],
        ];

		if($this->utils->table_really_exists($this->tableName)){
            if(!$this->db->field_exists('game_name_json', $this->tableName)){
				$this->dbforge->add_column($this->tableName, $fields);
            }
        }

	}

	public function down() {
	}
}
