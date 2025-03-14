<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_to_total_additional_table_20210303 extends CI_Migration {

    public function up() {
        $fields = array(
            'agent_id' => array(
                'type' => 'INT',
                'null' => true,
            ),
            'total_bets' => array(
                'type' => 'INT',
                'null' => true,
            ),
            'total_game_logs' => array(
                'type' => 'INT',
                'null' => true,
            ),
        );

        if(!$this->db->field_exists('total_bets', 'total_player_game_minute_additional')){
            $this->dbforge->add_column('total_player_game_minute_additional', $fields);
        }

        if(!$this->db->field_exists('total_bets', 'total_player_game_hour_additional')){
            $this->dbforge->add_column('total_player_game_hour_additional', $fields);
        }

    }

    public function down() {
    }
}