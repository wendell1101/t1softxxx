<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_update_pt_v3_game_logs_gamecode_and_sessionid_column_length_20211029 extends CI_Migration 
{
    private $tableName = 'pt_v3_game_logs';

    public function up() 
    {
        //modify column size
        $fields = array(
            'gamecode' => array(
				'type' => 'BIGINT',
				'null' => true,
			),
            'sessionid' => array(
				'type' => 'BIGINT',
				'null' => true,
			),
        );

        $this->dbforge->modify_column($this->tableName, $fields);
    }

    public function down() 
    {
        $fields = array(
            'gamecode' => array(
				'type' => 'INT',
				'constraint' => '15',
				'null' => true,
			),
            'sessionid' => array(
				'type' => 'INT',
				'constraint' => '15',
				'null' => true,
			),
        );

        $this->dbforge->modify_column($this->tableName, $fields);
    }
}
