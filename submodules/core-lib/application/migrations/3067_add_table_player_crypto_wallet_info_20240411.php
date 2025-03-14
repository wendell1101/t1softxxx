<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_add_table_player_crypto_wallet_info_20240411 extends CI_Migration {
    private $tableName = 'player_crypto_wallet_info';
    public function up()
    {   
        $fields = [
            'id' => [
                'type' => 'INT',
                'auto_increment' => true
            ],
            'playerId' => [
                'type' => 'INT',
            ],
            'token' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
            ],
            'chain' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
            ],
            'network' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
            ],
            'address' => [
                'type' => 'VARCHAR',
                'constraint' => 500,
            ],
            'externalSystemId' => [
                'type' => 'INT',
                'constraint' => 10,
            ],
            'status' => [
                'type' => 'TINYINT',
            ],
            'createdAt DATETIME DEFAULT CURRENT_TIMESTAMP' => [
                'null' => false,
            ],
            'updatedAt DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP' => [
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
            $this->player_model->addIndex($this->tableName, 'idx_playerId', 'playerId');
            $this->player_model->addIndex($this->tableName, 'idx_token', 'token');
            $this->player_model->addIndex($this->tableName, 'idx_chain', 'chain');
            $this->player_model->addIndex($this->tableName, 'idx_network', 'network');
            $this->player_model->addIndex($this->tableName, 'idx_address', 'address');
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
