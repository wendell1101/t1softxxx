<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_update_sgwin_game_logs_bid_column_length_20210929 extends CI_Migration {
    
    private $tableName = 'sgwin_game_logs';

    public function up() {
        //modify column size
        $fields = array(
            'bid' => array(
				'type' => 'BIGINT',
				'null' => true,
			),
        );
        $this->dbforge->modify_column($this->tableName, $fields);
    }

    public function down() {
        $fields = array(
            'bid' => array(
				'type' => 'INT',
				'constraint' => '20',
				'null' => true,
			),
        );

        $this->dbforge->modify_column($this->tableName, $fields);
    }
}
