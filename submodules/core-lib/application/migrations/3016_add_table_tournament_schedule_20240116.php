<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_add_table_tournament_schedule_20240116 extends CI_Migration {
    private $tableName = 'tournament_schedule';
    public function up() {
        $fields = [
            'id' => [
                'type' => 'BIGINT',
                'auto_increment' => true,
            ],
            'scheduleName' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
                'null' => true,
            ],
            'tournamentId' => [
                'type' => 'INT',
            ],
            'periods' => [
                'type' => 'INT',
                'null' => true,
            ],
            'status' => [
                'type' => 'INT',
                'default' => 0,
            ],
            'tournamentStartedAt' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'tournamentEndedAt' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'applyStartedAt' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'applyEndedAt' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'contestStartedAt' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'contestEndedAt' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'distributionTime' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'bonusType' => [
                'type' => 'INT',
                'default' => 1,
            ],
            'systemBonusAmount' => [
                'type' => 'DECIMAL',
                'constraint' => '10,4',
                'null' => true,
            ],
            'accumulateRegistrationFee' => [
                'type' => 'DECIMAL',
                'constraint' => '10,4',
                'null' => true,
            ],
            'releasedSystemBonus' => [
                'type' => 'DECIMAL',
                'constraint' => '10,4',
                'null' => true,
            ],
            'releasedRegistrationFee' => [
                'type' => 'DECIMAL',
                'constraint' => '10,4',
                'null' => true,
            ],            
            'description' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'icon' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
                'null' => true,
            ],
            'banner' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
                'null' => true,
            ],
            'releaseType' => [
                'type' => 'INT',
                'default' => 1,
            ],
            'notifyType' => [
                'type' => 'VARCHAR',
                'constraint' => '10',
                'null' => true,
            ],
            'createdBy' => [
                'type' => 'INT',
                'null' => true,
            ],
            'createdAt DATETIME DEFAULT CURRENT_TIMESTAMP' => [
                'null' => false,
            ],
            'updateBy' => [
                'type' => 'INT',
                'null' => true,
            ],
            'updatedAt DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP' => [
                'null' => false,
            ],
            'deletedBy' => [
                'type' => 'INT',
                'null' => true,
            ],
            'deletedAt' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ];

        if (!$this->db->table_exists($this->tableName)) {
            $this->dbforge->add_field($fields);
            $this->dbforge->add_key('id', TRUE);
            $this->dbforge->create_table($this->tableName);

            # add Index
            $this->load->model('player_model');            
            // default
            $this->player_model->addIndex($this->tableName, 'idx_scheduleName', 'scheduleName');
            $this->player_model->addIndex($this->tableName, 'idx_tournamentId', 'tournamentId');
            $this->player_model->addIndex($this->tableName, 'idx_periods', 'periods');
            $this->player_model->addIndex($this->tableName, 'idx_status', 'status');
            $this->player_model->addIndex($this->tableName, 'idx_applyStartedAt', 'applyStartedAt');
            $this->player_model->addIndex($this->tableName, 'idx_applyEndedAt', 'applyEndedAt');
            $this->player_model->addIndex($this->tableName, 'idx_contestStartedAt', 'contestStartedAt');
            $this->player_model->addIndex($this->tableName, 'idx_contestEndedAt', 'contestEndedAt');
            $this->player_model->addIndex($this->tableName, 'idx_bonusType', 'bonusType');
            $this->player_model->addIndex($this->tableName, 'idx_releaseType', 'releaseType');
            $this->player_model->addIndex($this->tableName, 'idx_createdAt', 'createdAt');
            $this->player_model->addIndex($this->tableName, 'idx_updatedAt', 'updatedAt');
        }
    }

    public function down() {
        if ($this->db->table_exists($this->tableName)) {
            $this->dbforge->drop_table($this->tableName);
        }
    }
}