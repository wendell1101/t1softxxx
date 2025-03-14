<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_to_table_hogaming_game_logs_20210916 extends CI_Migration {

	private $tableName = 'hogaming_game_logs';

    public function up() {

        $fields = array(
            'valid_bet' => array(
                'type' => 'DOUBLE',
                'null' => true
            ),
        );

        if(!$this->db->field_exists('valid_bet', $this->tableName)){
            $this->dbforge->add_column($this->tableName, $fields);
        }
    }

    public function down() {
        if($this->db->field_exists('valid_bet', $this->tableName)){
            $this->dbforge->drop_column($this->tableName, 'valid_bet');
        }
    }
}