<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_add_table_player_quest_invitations_20240522 extends CI_Migration {
    private $tableName = 'player_quest_invitations';
    public function up()
    {
        $fields = [
            'id' => array(
                'type' => 'INT',
                'unsigned' => true,
                'auto_increment' => true,
            ),
            'playerId' => [
                'type' => 'INT',
                'constraint' => '12',
            ],
            'questCategoryId' => [
                'type' => 'INT', 
                'null' => false
            ],
            'totalValidInvites' => [
                'type' => 'INT',
                'constraint' => 11,
            ],
            'questStartAt' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'questEndAt' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'createdAt DATETIME DEFAULT CURRENT_TIMESTAMP' => [
                'null' => false,
            ],
            'updatedAt' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'lastSyncAt' => [
                'type' => 'DATETIME',
                'null' => true,
            ]
        ];

        if (!$this->db->table_exists($this->tableName)) {
            $this->dbforge->add_field($fields);
            $this->dbforge->add_key('id', true);
            $this->dbforge->create_table($this->tableName);

            # add Index
            $this->load->model('player_model');
            $this->player_model->addIndex($this->tableName, 'idx_playerId', 'playerId');
            $this->player_model->addIndex($this->tableName, 'idx_questCategoryId', 'questCategoryId');
            $this->player_model->addIndex($this->tableName, 'idx_totalValidInvites', 'totalValidInvites');
            $this->player_model->addIndex($this->tableName, 'idx_lastSyncAt', 'lastSyncAt');
        }
    }

    public function down()
    {
        if ($this->db->table_exists($this->tableName)) {
            $this->dbforge->drop_table($this->tableName);
        }
    }
}
