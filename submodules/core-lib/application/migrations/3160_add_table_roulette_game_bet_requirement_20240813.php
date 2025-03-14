<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_add_table_roulette_game_bet_requirement_20240813 extends CI_Migration {
    private $tableName = 'roulette_game_bet_requirement';
    public function up()
    {
        $fields = [
            'id' => array(
                'type' => 'INT',
                'unsigned' => TRUE,
                'auto_increment' => TRUE,
            ),
            'rouletteId' => array(
                'type' => 'INT',
                'unsigned' => TRUE,
                'null' => FALSE
            ),
            'gameDescriptionId' => array(
                'type' => 'INT',
                'unsigned' => TRUE,
                'null' => FALSE
            ),
        ];

        if (!$this->db->table_exists($this->tableName)) {
            $this->dbforge->add_field($fields);
            $this->dbforge->add_key('id', true);
            $this->dbforge->add_key('rouletteId', false);
            $this->dbforge->add_key('gameDescriptionId', false);
            $this->dbforge->create_table($this->tableName);
        }
    }

    public function down()
    {
        if ($this->db->table_exists($this->tableName)) {
            $this->dbforge->drop_table($this->tableName);
        }
    }
}
