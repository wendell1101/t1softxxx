<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_add_table_tournament_event_20240116 extends CI_Migration {
    private $tableName = 'tournament_event';
    public function up()
    {
        $fields = [
            'id' => [
                'type' => 'BIGINT',
                'auto_increment' => true,
            ],
            'scheduleId' => [
                'type' => 'INT',
            ],
            'eventName' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
            ],
            'targetPlayerType' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
            ],
            'applyConditionDepositAmount' => [
                'type' => 'DECIMAL',
                'constraint' => '10,4',
            ],
            'applyConditionCountPeriod' => [
                'type' => 'INT',
                'default' => 1,
            ],
            'applyConditionCountPeriodStartAt' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'applyConditionCountPeriodEndAt' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'applyCountThreshold' => [
                'type' => 'INT',
                'constraint' => 11,
            ],
            'registrationFee' => [
                'type' => 'DECIMAL',
                'constraint' => '10,4',
            ],
            'status' => [
                'type' => 'INT',
                'default' => 0,
            ],
            'order' => [
                'type' => 'INT',
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
            'lastSyncAt' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'releaseAt' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ];

        if (!$this->db->table_exists($this->tableName)) {
            $this->dbforge->add_field($fields);
            $this->dbforge->add_key('id', true);
            $this->dbforge->create_table($this->tableName);

            # add Index
            $this->load->model('player_model');
            // default
            $this->player_model->addIndex($this->tableName, 'idx_scheduleId', 'scheduleId');
            $this->player_model->addIndex($this->tableName, 'idx_eventName', 'eventName');
            $this->player_model->addIndex($this->tableName, 'idx_applyConditionDepositAmount', 'applyConditionDepositAmount');
            $this->player_model->addIndex($this->tableName, 'idx_applyConditionCountPeriod', 'applyConditionCountPeriod');
            $this->player_model->addIndex($this->tableName, 'idx_applyConditionCountPeriodStartAt', 'applyConditionCountPeriodStartAt');
            $this->player_model->addIndex($this->tableName, 'idx_applyConditionCountPeriodEndAt', 'applyConditionCountPeriodEndAt');
            $this->player_model->addIndex($this->tableName, 'idx_applyCountThreshold', 'applyCountThreshold');
            $this->player_model->addIndex($this->tableName, 'idx_registrationFee', 'registrationFee');
            $this->player_model->addIndex($this->tableName, 'idx_status', 'status');
            $this->player_model->addIndex($this->tableName, 'idx_createdAt', 'createdAt');
            $this->player_model->addIndex($this->tableName, 'idx_updatedAt', 'updatedAt');
            $this->player_model->addIndex($this->tableName, 'idx_lastSyncAt', 'lastSyncAt');
            $this->player_model->addIndex($this->tableName, 'idx_releaseAt', 'releaseAt');
        }
    }

    public function down()
    {
        if ($this->db->table_exists($this->tableName)) {
            $this->dbforge->drop_table($this->tableName);
        }
    }
}
