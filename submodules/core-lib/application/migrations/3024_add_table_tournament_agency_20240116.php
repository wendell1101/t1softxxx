<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_add_table_tournament_agency_20240116 extends CI_Migration {
    private $tableName = 'tournament_agency';
    public function up()
    {   
        $fields = [
            'id' => [
                'type' => 'INT',
                'null' => false,
                'auto_increment' => true
            ],
            'eventId' => [
                'type' => 'INT',
            ],
            'agencyId' => [
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
            $this->player_model->addIndex($this->tableName, 'idx_eventId', 'eventId');
            $this->player_model->addIndex($this->tableName, 'idx_agencyId', 'agencyId');
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
