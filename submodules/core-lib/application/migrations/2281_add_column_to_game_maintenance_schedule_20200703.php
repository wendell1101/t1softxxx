<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_to_game_maintenance_schedule_20200703 extends CI_Migration {

    private $tableName = 'game_maintenance_schedule';

    public function up() {

        $fields = array(
            'hide_wallet' => array(
                'type' => 'BOOLEAN',
                'null' => false,
                'default' => 1,
            ),
        );

        if(!$this->db->field_exists('hide_wallet', $this->tableName)){
            $this->dbforge->add_column($this->tableName, $fields);
        }
    }

    public function down() {
        if($this->db->field_exists('hide_wallet', $this->tableName)){
            $this->dbforge->drop_column($this->tableName, 'hide_wallet');
        }
    }
}