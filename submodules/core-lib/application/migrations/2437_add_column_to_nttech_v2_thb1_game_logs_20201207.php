<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_to_nttech_v2_thb1_game_logs_20201207 extends CI_Migration {

    private $tableName = 'nttech_v2_thb1_game_logs';

    public function up() {
        $fields1 = array(
            'settlestatus' => array(
                'type' => 'INT',
                'null' => true,
            ),
        );
        if(!$this->db->field_exists('settlestatus', $this->tableName)){
            $this->dbforge->add_column($this->tableName, $fields1);
        }
    }

    public function down() {

        if($this->db->field_exists('settlestatus', $this->tableName)){
            $this->dbforge->drop_column($this->tableName, 'settlestatus');
        }
    }
}