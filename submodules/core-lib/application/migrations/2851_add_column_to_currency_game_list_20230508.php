<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_to_currency_game_list_20230508 extends CI_Migration {

    private $tableName = "game_description_currency_list";

	public function up() {
		$fields = [
			'flag_show_in_site' => [
                "type" => "TINYINT",
                'null' => true
            ],
        ];

		if($this->utils->table_really_exists($this->tableName)){
            if($this->db->field_exists('flag_show_in_site', $this->tableName)){
				$this->dbforge->modify_column($this->tableName, $fields);
            }
        }
	}

	public function down() {
	}
}
