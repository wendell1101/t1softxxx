<?php

defined("BASEPATH") OR exit("No direct script access allowed");

class Migration_add_table_hp_lottery_game_logs_20231228 extends CI_Migration {
    private $tableName = 'hp_lottery_game_logs';

    public function up() {
        $fields = [
            'id' => [
                'type' => 'BIGINT',
                'null' => false,
                'auto_increment' => true
            ],
            'betId' => [
                'type' => 'VARCHAR',
                'constraint' => '150',
                'null' => true,
            ],
            'betOrderNo' => [
                'type' => 'VARCHAR',
                'constraint' => '15',
                'null' => true,
            ],
            'betStatusId' => [
                'type' => 'INT',
                'constraint' => '2',
                'null' => true,
            ],
            'betResultId' => [
                'type' => 'INT',
                'constraint' => '2',
                'null' => true,
            ],
            'eventReferenceDate' => [
                'type' => 'DATE',
                'null' => true,
            ],
            'gameCode' => [
                'type' => 'VARCHAR',
                'constraint' => '75',
                'null' => true,
            ],
            'eventNo' => [
                'type' => 'VARCHAR',
                'constraint' => '50',
                'null' => true,
            ],
            'betTypeCode' => [
                'type' => 'VARCHAR',
                'constraint' => '50',
                'null' => true,
            ],
            'selectionCode' => [
                'type' => 'VARCHAR',
                'constraint' => '150',
                'null' => true,
            ],
            'userCode' => [
                'type' => 'VARCHAR',
                'constraint' => '50',
                'null' => true,
            ],
            'currencyCode' => [
                'type' => 'VARCHAR',
                'constraint' => '50',
                'null' => true,
            ],
            'wagerCount' => [
                'type' => 'INT',
                'constraint' => '25',
                'null' => true,
            ],
            'winningDeduction' => [
                'type' => 'DOUBLE',
                'null' => true,
            ],
            'unitCost' => [
                'type' => 'DOUBLE',
                'null' => true,
            ],
            'unitServiceFee' => [
                'type' => 'DOUBLE',
                'null' => true,
            ],
            'unitAmount' => [
                'type' => 'DOUBLE',
                'null' => true,
            ],
            'unitPrizeAmount' => [
                'type' => 'DOUBLE',
                'null' => true,
            ],
            'unitReceivablePrizeAmount' => [
                'type' => 'DOUBLE',
                'null' => true,
            ],
            'totalCost' => [
                'type' => 'DOUBLE',
                'null' => true,
            ],
            'totalServiceFee' => [
                'type' => 'DOUBLE',
                'null' => true,
            ],
            'totalAmount' => [
                'type' => 'DOUBLE',
                'null' => true,
            ],
            'totalPrizeAmount' => [
                'type' => 'DOUBLE',
                'null' => true,
            ],
            'totalReceivablePrizeAmount' => [
                'type' => 'DOUBLE',
                'null' => true,
            ],
            'overallResultAmount' => [
                'type' => 'DOUBLE',
                'null' => true,
            ],
            'insertedDateTime' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updatedDateTime' => [
                'type' => 'DATETIME',
                'null' => true,
            ],

            # SBE additional info
            'extra_info' => [
                'type' => 'TEXT',
                'null' => true
            ],
            'response_result_id' => [
                'type' => 'INT',
                'null' => true
            ],
            'external_uniqueid' => [
                'type' => 'VARCHAR',
                'constraint' => '150',
                'null' => true
            ],
            'created_at DATETIME DEFAULT CURRENT_TIMESTAMP' => [
                'null' => false
            ],
            'updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP' => [
                'null' => false
            ],
            'md5_sum' => [
                'type' => 'VARCHAR',
                'constraint' => '32',
                'null' => true,
            ]
        ];

        if (!$this->utils->table_really_exists($this->tableName)) {
            $this->dbforge->add_field($fields);
            $this->dbforge->add_key('id', true);
            $this->dbforge->create_table($this->tableName);

            # add Index
            $this->load->model('player_model');
            $this->player_model->addIndex($this->tableName, 'idx_betId', 'betId');
            $this->player_model->addIndex($this->tableName, 'idx_gameCode', 'gameCode');
            $this->player_model->addIndex($this->tableName, 'idx_selectionCode', 'selectionCode');
            $this->player_model->addIndex($this->tableName, 'idx_userCode', 'userCode');
            $this->player_model->addIndex($this->tableName, 'idx_insertedDateTime', 'insertedDateTime');
            $this->player_model->addIndex($this->tableName, 'idx_updatedDateTime', 'updatedDateTime');
            $this->player_model->addUniqueIndex($this->tableName, 'idx_external_uniqueid', 'external_uniqueid');
        }
    }

    public function down() {
        if ($this->db->table_exist($this->tableName)) {
            $this->dbforge->drop_table($this->tableName);
        }
    }
}