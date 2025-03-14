<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_add_table_tournament_game_20240116 extends CI_Migration {
    private $tableName = 'tournament_game';
    public function up()
    {   
        $fields = [
            'id' => [
                'type' => 'INT',
                'auto_increment' => true
            ],
            'tournamentId' => [
                'type' => 'INT',
            ],
            'gameTagId' => [
                'type' => 'INT',
            ],
            'gameTypeId' => [
                'type' => 'INT',
            ],
            'gamePlatformId' => [
                'type' => 'INT',
            ],
            'gameDescriptionId' => [
                'type' => 'INT',
            ],
            'createdAt DATETIME DEFAULT CURRENT_TIMESTAMP' => [
                'null' => false,
            ],
        ];

        if (!$this->db->table_exists($this->tableName)) {
            $this->dbforge->add_field($fields);
            $this->dbforge->add_key('id', true);
            $this->dbforge->create_table($this->tableName);

            # add Index
            $this->load->model('player_model');
            // default
            $this->player_model->addIndex($this->tableName, 'idx_tournamentId', 'tournamentId');
            $this->player_model->addIndex($this->tableName, 'idx_gameTagId', 'gameTagId');
            $this->player_model->addIndex($this->tableName, 'idx_gameTypeId', 'gameTypeId');
            $this->player_model->addIndex($this->tableName, 'idx_gamePlatformId', 'gamePlatformId');
            $this->player_model->addIndex($this->tableName, 'idx_gameDescriptionId', 'gameDescriptionId');
            $this->player_model->addIndex($this->tableName, 'idx_createdAt', 'createdAt');
        }
    }

    public function down()
    {
        if ($this->db->table_exists($this->tableName)) {
            $this->dbforge->drop_table($this->tableName);
        }
    }
    }
