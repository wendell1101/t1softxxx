<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_add_table_questgamebetrule_20240408 extends CI_Migration {

    private $tableName = 'quest_game_bet_rule';

    public function up() {
        $fields = [
            'questgametypeId' => [
                'type' => 'INT',
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'managerId' => [
                'type' => 'INT',
                'constraint' => '12',
                'null' => false,
            ],
            'game_description_id' => [
                'type' => 'INT',
                'constraint' => '12',
                'null' => false,
            ],
            'betrequirement' => [
                'type' => 'DOUBLE',
                'null' => true,
                'default' => '0'
            ]
        ];

        if (!$this->db->table_exists($this->tableName)) {
            $this->dbforge->add_field($fields);
            $this->dbforge->add_key('questgametypeId', TRUE);
            $this->dbforge->create_table($this->tableName);

            # add Index
            $this->load->model('player_model');
            // default
            $this->player_model->addIndex($this->tableName, 'idx_questgametypeId', 'questgametypeId');
            $this->player_model->addIndex($this->tableName, 'idx_managerId', 'managerId');
            $this->player_model->addIndex($this->tableName, 'idx_game_description_id', 'game_description_id');
            $this->player_model->addIndex($this->tableName, 'idx_betrequirement', 'betrequirement');
        }
    }

    public function down() {
        if ($this->db->table_exists($this->tableName)) {
            $this->dbforge->drop_table($this->tableName);
        }
    }
}