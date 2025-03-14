<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_to_total_cashback_player_game_daily_20210726 extends CI_Migration {

    private $tableName = 'total_cashback_player_game_daily';

    public function up() {
        $fields = array(
            'vip_level_info' => array(
                'type' => 'JSON',
                'null' => true
            ),
        );

        if($this->utils->table_really_exists($this->tableName)){
            if(!$this->db->field_exists('vip_level_info', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $fields);
            }
        }
    }

    public function down() {
        if($this->utils->table_really_exists($this->tableName)){
            if($this->db->field_exists('vip_level_info', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'vip_level_info');
            }
        }
    }
}
