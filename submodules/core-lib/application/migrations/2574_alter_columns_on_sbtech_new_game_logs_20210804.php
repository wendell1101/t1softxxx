<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_alter_columns_on_sbtech_new_game_logs_20210804 extends CI_Migration {

	private $tableName = 'sbtech_new_game_logs';

	public function up() {

        $fields = array(
            'bet_type_id' => array(
                'type' => 'INT',
                'constraint' => '5',
                'null' => true,
            )
        );
        $this->dbforge->modify_column($this->tableName, $fields);

	}

	public function down() {}
}