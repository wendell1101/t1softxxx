<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_add_table_tracking_platform_20240424 extends CI_Migration {
    private $tableName = 'tracking_platform';
    public function up()
    {
        $fields = [
            'platformId' => [
                'type' => 'BIGINT',
                'auto_increment' => true,
            ],
            'platformType' => [
                'type' => 'INT',
                'constraint' => 11,
            ],
            'scope' => [
                'type' => 'INT',
                'constraint' => 11,
            ],
            'domain' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
            ],
            'trackingId' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
            ],
            'clickId' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
            ],
            'token' => [
                'type' => 'VARCHAR',
                'constraint' => 500,
                'null' => true,
            ],
            'status' => [
                'type' => 'INT',
                'default' => 1,
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
            'extraInfo' => [
                'type' => 'JSON',
                'null' => true,
            ],
        ];

        if (!$this->db->table_exists($this->tableName)) {
            $this->dbforge->add_field($fields);
            $this->dbforge->add_key('platformId', true);
            $this->dbforge->create_table($this->tableName);

            # add Index
            $this->load->model('player_model');
            $this->player_model->addIndex($this->tableName, 'idx_platformType', 'platformType');
            $this->player_model->addIndex($this->tableName, 'idx_domain', 'domain');
            $this->player_model->addIndex($this->tableName, 'idx_trackingId', 'trackingId');
            $this->player_model->addIndex($this->tableName, 'idx_clickId', 'clickId');
            $this->player_model->addIndex($this->tableName, 'idx_token', 'token');
            $this->player_model->addIndex($this->tableName, 'idx_status', 'status');
            $this->player_model->addIndex($this->tableName, 'idx_createdAt', 'createdAt');
            $this->player_model->addIndex($this->tableName, 'idx_updatedAt', 'updatedAt');
        }
    }

    public function down()
    {
        if ($this->db->table_exists($this->tableName)) {
            $this->dbforge->drop_table($this->tableName);
        }
    }
}
