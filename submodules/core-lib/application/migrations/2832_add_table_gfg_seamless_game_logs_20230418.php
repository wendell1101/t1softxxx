<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_add_table_gfg_seamless_game_logs_20230418 extends CI_Migration {
    private $tableName = 'gfg_seamless_game_logs';

    public function up() {
        $fields = [
            'id' => [
                'type' => 'BIGINT',
                'null' => false,
                'auto_increment' => true
            ],
            'gameId' => [
                'type' => 'INT',
                'null' => true,
            ],
            'account' => [
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true
            ],
            'accountId' => [
                'type' => 'INT',
                'null' => true,
            ],
            'platform' => [
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true
            ],
            'roundId' => [
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true
            ],
            'gameResult' => [
                'type' => 'VARCHAR',
                'constraint' => '5',
                'null' => true
            ],
            'fieldId' => [
                'type' => 'SMALLINT',
                'null' => true,
            ],
            'filedName' => [
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true
            ],
            'tableId' => [
                'type' => 'SMALLINT',
                'null' => true,
            ],
            'chair' => [
                'type' => 'VARCHAR',
                'constraint' => '20',
                'null' => true
            ],
            'bet' => [
                'type' => 'DOUBLE',
                'null' => true
            ],
            'validBet' => [
                'type' => 'DOUBLE',
                'null' => true
            ],
            'win' => [
                'type' => 'DOUBLE',
                'null' => true
            ],
            'lose' => [
                'type' => 'DOUBLE',
                'null' => true
            ],
            'fee' => [
                'type' => 'DOUBLE',
                'null' => true
            ],
            'enterMoney' => [
                'type' => 'DOUBLE',
                'null' => true
            ],
            'createTime' => [
                'type' => 'DATETIME',
                'null' => true
            ],
            'roundBeginTime' => [
                'type' => 'DATETIME',
                'null' => true
            ],
            'roundEndTime' => [
                'type' => 'DATETIME',
                'null' => true
            ],
            'ip' => [
                'type' => 'VARCHAR',
                'constraint' => '20',
                'null' => true
            ],
            'uid' => [
                'type' => 'INT',
                'null' => true,
            ],
            'orderId' => [
                'type' => 'INT',
                'null' => true,
            ],
            'adjustInfo' => [
                'type' => 'JSON',
                'null' => true
            ],

            #default
            'responseResultId' => [
                'type' => 'INT',
                'null' => true
            ],
            'externalUniqueId' => [
                'type' => 'VARCHAR',
                'constraint' => '150',
                'null' => true
            ],
            'createdAt DATETIME DEFAULT CURRENT_TIMESTAMP' => [
                'null' => false
            ],
            'updatedAt DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP' => [
                'null' => false
            ],
            'md5Sum' => [
                'type' => 'VARCHAR',
                'constraint' => '32',
                'null' => true
            ]
        ];

        if (!$this->utils->table_really_exists($this->tableName)) {
            $this->dbforge->add_field($fields);
            $this->dbforge->add_key('id', TRUE);
            $this->dbforge->create_table($this->tableName);

            # add Index
            $this->load->model('player_model');
            $this->player_model->addIndex($this->tableName, 'idx_gameId', 'gameId');
            $this->player_model->addIndex($this->tableName, 'idx_account', 'account');
            $this->player_model->addIndex($this->tableName, 'idx_accountId', 'accountId');
            $this->player_model->addIndex($this->tableName, 'idx_roundId', 'roundId');
            $this->player_model->addIndex($this->tableName, 'idx_createTime', 'createTime');
            $this->player_model->addIndex($this->tableName, 'idx_roundBeginTime', 'roundBeginTime');
            $this->player_model->addIndex($this->tableName, 'idx_roundEndTime', 'roundEndTime');
            $this->player_model->addUniqueIndex($this->tableName, 'idx_externalUniqueId', 'externalUniqueId');
        }
    }

    public function down() {
        if ($this->utils->table_really_exists($this->tableName)) {
            $this->dbforge->drop_table($this->tableName);
        }
    }
}