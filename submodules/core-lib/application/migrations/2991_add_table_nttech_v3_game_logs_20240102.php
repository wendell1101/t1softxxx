<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_table_nttech_v3_game_logs_20240102 extends CI_Migration {

    private $tableName = 'nttech_v3_game_logs';

    public function up() {
        $fields = [
            'id' => [
                'type' => 'BIGINT',
                'null' => false,
                'auto_increment' => TRUE,
            ],
            'gametype' => [
                'type' => 'VARCHAR',
                'constraint' => '20',
                'null' => true,
            ],
            'comm' => [
                'type' => 'double',
                'null' => true,
            ],
            'txtime' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'bizdate' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'winamt' => [
                'type' => 'double',
                'null' => true,
            ],
            'gameinfo' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'betamt' => [
                'type' => 'double',
                'null' => true,
            ],
            'updatetime' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'jackpotwinamt' => [
                'type' => 'double',
                'null' => true,
            ],
            'turnover' => [
                'type' => 'double',
                'null' => true,
            ],
            'userid' => [
                'type' => 'VARCHAR',
                'constraint' => '60',
                'null' => true,
            ],
            'bettype' => [
                'type' => 'VARCHAR',
                'constraint' => '20',
                'null' => true,
            ],
            'platform' => [
                'type' => 'VARCHAR',
                'constraint' => '20',
                'null' => true,
            ],
            'txstatus' => [
                'type' => 'INT',
                'constraint' => '2',
                'null' => true,
            ],
            'jackpotbetamt' => [
                'type' => 'double',
                'null' => true,
            ],
            'createtime' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'platformtxid' => [
                'type' => 'VARCHAR',
                'constraint' => '30',
                'null' => true,
            ],
            'realbetamt' => [
                'type' => 'double',
                'null' => true,
            ],
            'gamecode' => [
                'type' => 'VARCHAR',
                'constraint' => '30',
                'null' => true,
            ],
            'currency' => [
                'type' => 'VARCHAR',
                'constraint' => '10',
                'null' => true,
            ],
            'transid' => [
                'type' => 'VARCHAR',
                'constraint' => '20',
                'null' => true,
            ],
            'realwinamt' => [
                'type' => 'VARCHAR',
                'constraint' => '20',
                'null' => true,
            ],
            'roundid' => [
                'type' => 'VARCHAR',
                'constraint' => '60',
                'null' => true,
            ],
            'result_amount' => [
                'type' => 'double',
                'null' => true,
            ],
            'settlestatus' => [
                'type' => 'INT',
                'null' => true,
            ],
            'tipInfo' => [
                'type' => 'JSON',
                'null' => true,
            ],
            'tip' => [
                'type' => 'DOUBLE',
                'null' => true,
            ],

            # SBE additional info
            'response_result_id' => [
                'type' => 'INT',
                'constraint' => '11',
                'null' => true,
            ],
            'external_uniqueid' => [
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true,
            ],
            'created_at DATETIME DEFAULT CURRENT_TIMESTAMP' => [
                'null' => false,
            ],
            'updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP' => [
                'null' => false,
            ],
            'md5_sum' => [
                'type' => 'VARCHAR',
                'constraint' => '32',
                'null' => true,
            ],
        ];

        if (!$this->db->table_exists($this->tableName)) {
            $this->dbforge->add_field($fields);
            $this->dbforge->add_key('id', TRUE);
            $this->dbforge->create_table($this->tableName);
            # Add Index
            $this->load->model('player_model');
            $this->player_model->addIndex($this->tableName, 'idx_userid', 'userid');
            $this->player_model->addIndex($this->tableName, 'idx_gamecode', 'gamecode');
            $this->player_model->addIndex($this->tableName, 'idx_txtime', 'txtime');
            $this->player_model->addIndex($this->tableName, 'idx_createtime', 'createtime');
            $this->player_model->addIndex($this->tableName, 'idx_updatetime', 'updatetime');
            $this->player_model->addUniqueIndex($this->tableName, 'idx_platformtxid', 'platformtxid');
            $this->player_model->addUniqueIndex($this->tableName, 'idx_external_uniqueid', 'external_uniqueid');
        }
    }

    public function down() {
        if ($this->db->table_exists($this->tableName)) {
            $this->dbforge->drop_table($this->tableName);
        }
    }
}
