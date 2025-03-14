<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_to_total_additional_table_20201231 extends CI_Migration {

    public function up() {
        $fields = array(
            'date_minute' => array(
                'type' => 'varchar',
                'constraint' => '20',
                'null' => true,
            ),
            'player_id' => array(
                'type' => 'INT',
                'null' => true,
            ),
            'game_platform_id' => array(
                'type' => 'INT',
                'null' => true,
            ),
        );

        if(!$this->db->field_exists('date_minute', 'total_player_game_minute_additional')){
            $this->dbforge->add_column('total_player_game_minute_additional', $fields);
        }

        $fields = array(
            'date_hour' => array(
                'type' => 'varchar',
                'constraint' => '20',
                'null' => true,
            ),
            'player_id' => array(
                'type' => 'INT',
                'null' => true,
            ),
            'game_platform_id' => array(
                'type' => 'INT',
                'null' => true,
            ),
        );

        if(!$this->db->field_exists('date_hour', 'total_player_game_hour_additional')){
            $this->dbforge->add_column('total_player_game_hour_additional', $fields);
        }

    }

    public function down() {
    }
}