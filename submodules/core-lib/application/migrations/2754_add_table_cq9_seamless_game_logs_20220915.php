<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_table_cq9_seamless_game_logs_20220915 extends CI_Migration
{
    private $tableName = 'cq9_seamless_game_logs';

    public function up()
    {
        $fields = [
            'id' => [
                'type' => 'BIGINT',
                'null' => false,
                'auto_increment' => true,
            ],
            'gamehall' => [
                'type' => 'VARCHAR',
                'constraint' => '50',
                'null' => true,
            ],
            'gamecode' => [
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true,
            ],
            'gametype' => [
                'type' => 'VARCHAR',
                'constraint' => '50',
                'null' => true,
            ],
            'gameplat' => [
                'type' => 'VARCHAR',
                'constraint' => '50',
                'null' => true,
            ],
            'account' => [
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true,
            ],
            'round' => [
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true,
            ],
            'bet' => [
                'type' => 'DOUBLE',
                'null' => true,
            ],
            'validbet' => [
                'type' => 'DOUBLE',
                'null' => true,
            ],
            'win' => [
                'type' => 'DOUBLE',
                'null' => true,
            ],
            'jackpot' => [
                'type' => 'DOUBLE',
                'null' => true,
            ],
            'jackpotcontribution' => [
                'type' => 'JSON',
                'null' => true
            ],
            'jackpottype' => [
                'type' => 'VARCHAR',
                'constraint' => '50',
                'null' => true,
            ],
            'balance' => [
                'type' => 'DOUBLE',
                'null' => true,
            ],
            'status' => [
                'type' => 'VARCHAR',
                'constraint' => '50',
                'null' => true,
            ],
            'bettime' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'endroundtime' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'createtime' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'detail' => [
                'type' => 'JSON',
                'null' => true,
            ],
            'rake' => [
                'type' => 'DOUBLE',
                'null' => true,
            ],
            'singlerowbet' => [
                'type' => 'VARCHAR',
                'constraint' => '10',
                'null' => true,
            ],
            'ticketid' => [
                'type' => 'VARCHAR',
                'constraint' => '50',
                'null' => true,
            ],
            'tickettype' => [
                'type' => 'VARCHAR',
                'constraint' => '10',
                'null' => true,
            ],
            'giventype' => [
                'type' => 'VARCHAR',
                'constraint' => '10',
                'null' => true,
            ],
            'ticketbets' => [
                'type' => 'DOUBLE',
                'null' => true,
            ],
            'gamerole' => [
                'type' => 'VARCHAR',
                'constraint' => '50',
                'null' => true,
            ],
            'bankertype' => [
                'type' => 'VARCHAR',
                'constraint' => '50',
                'null' => true,
            ],
            'roomfee' => [
                'type' => 'DOUBLE',
                'null' => true,
            ],
            'bettype' => [
                'type' => 'JSON',
                'null' => true,
            ],
            'gameresult' => [
                'type' => 'JSON',
                'null' => true,
            ],
            'tabletype' => [
                'type' => 'VARCHAR',
                'constraint' => '60',
                'null' => true
            ],
            'tableid' => [
                'type' => 'VARCHAR',
                'constraint' => '60',
                'null' => true
            ],
            'roundnumber' => [
                'type' => 'VARCHAR',
                'constraint' => '60',
                'null' => true
            ],
            'currency' => [
                'type' => 'VARCHAR',
                'constraint' => '15',
                'null' => true
            ],
            # SBE additional info
            'response_result_id' => [
                'type' => 'INT',
                'null' => true
            ],
            'external_unique_id' => [
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
                'null' => true
            ]
        ];

        if (!$this->db->table_exists($this->tableName)) {
            $this->dbforge->add_field($fields);
            $this->dbforge->add_key('id', TRUE);
            $this->dbforge->create_table($this->tableName);

            # add Index
            $this->load->model('player_model');
            $this->player_model->addIndex($this->tableName, 'idx_gamecode', 'gamecode');
            $this->player_model->addIndex($this->tableName, 'idx_account', 'account');
            $this->player_model->addIndex($this->tableName, 'idx_round', 'round');
            $this->player_model->addIndex($this->tableName, 'idx_bettime', 'bettime');
            $this->player_model->addIndex($this->tableName, 'idx_endroundtime', 'endroundtime');
            $this->player_model->addIndex($this->tableName, 'idx_createtime', 'createtime');
            $this->player_model->addIndex($this->tableName, 'idx_created_at', 'created_at');
            $this->player_model->addIndex($this->tableName, 'idx_updated_at', 'updated_at');
            $this->player_model->addUniqueIndex($this->tableName, 'idx_external_unique_id', 'external_unique_id');
        }
    }

    public function down()
    {
        if ($this->db->table_exists($this->tableName)) {
            $this->dbforge->drop_table($this->tableName);
        }
    }
}
