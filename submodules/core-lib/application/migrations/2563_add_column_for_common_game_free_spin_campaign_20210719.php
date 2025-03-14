<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_for_common_game_free_spin_campaign_20210719 extends CI_Migration {

    private $tableName ='common_game_free_spin_campaign';

    public function up() {
        $field = array(
            'version' => array(
                'type' => 'smallint',
                'null' => true,
                'default' => 1,
            ),
        );

        if($this->utils->table_really_exists($this->tableName)){
            if(!$this->db->field_exists('version', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $field);
            }
        }
    }

    public function down() {
        if($this->utils->table_really_exists($this->tableName)){
            if($this->db->field_exists('version', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'version');
            }
        }
    }
}