<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_to_player_runtime_20201126 extends CI_Migration {

    private $tableName = 'player_runtime';

    public function up() {
        $fields = array(
            'beforeLastLoginTime' => array(
                'type' => 'DATETIME',
                'null' => true,
            ),
        );

        if(!$this->db->field_exists('beforeLastLoginTime', $this->tableName)){
            $this->dbforge->add_column($this->tableName, $fields);
        }
    }

    public function down() {
        if($this->db->field_exists('beforeLastLoginTime', $this->tableName)){
            $this->dbforge->drop_column($this->tableName, 'beforeLastLoginTime');
        }
    }
}