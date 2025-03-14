<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_add_table_redemption_code_20220518 extends CI_Migration
{

    private $tableName = 'redemption_code';

    public function up()
    {
        $fields = array(
            'id' => array(
                'type' => 'INT',
                'null' => false,
                'auto_increment' => TRUE,
            ),
            'category_id' => array(
                'type' => 'VARCHAR',
                'constraint' => '200',
                'null' => false,
            ),
            'redemption_code' => array(
                'type' => 'VARCHAR',
                'constraint' => '200',
                'null' => false,
            ),
            'current_bonus' => array(
                'type' => 'DOUBLE',
                'null' => true,
            ),
            'current_withdrawal_rules' => array(
                'type' => 'JSON',
                'null' => true,
            ),
            'created_by' => array(
                'type' => 'VARCHAR',
                'constraint' => '50',
                'null' => false,
            ),
            'created_at DATETIME DEFAULT CURRENT_TIMESTAMP' => array(
                'null' => false
            ),
            'updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP' => array(
                'null' => false
            ),
            'player_id' => array(
                'type' => 'VARCHAR',
                'constraint' => '200',
                'null' => true,
            ),
            'expires_at' => array(
                'type' => 'DATETIME',
                'null' => true,
            ),
            'request_at' => array(
                'type' => 'DATETIME',
                'null' => true,
            ),
            'status' => array(
                'type' => 'SMALLINT',
                'null' => false
            ),
            'promo_cms_id' => array(
                'type' => 'BIGINT',
                'null' => true,
            ),
            'notes' => array(
                'type' => 'TEXT',
                'null' => true,
            ),
            'action_logs' => array(
                'type' => 'TEXT',
                'null' => true,
            ),
            'is_deleted' => array(
                'type' => 'SMALLINT',
                'null' => true,
            ),
            'deleted_on' => array(
                'type' => 'DATETIME',
                'null' => true,
            ),
        );

        if (!$this->utils->table_really_exists($this->tableName)) {
            $this->dbforge->add_field($fields);
            $this->dbforge->add_key('id', true);
            $this->dbforge->create_table($this->tableName);

            # add Index
            $this->load->model('player_model');
            $this->player_model->addUniqueIndex($this->tableName, 'idx_redemption_code', 'redemption_code');
            $this->player_model->addIndex($this->tableName, 'idx_created_at', 'created_at');
            $this->player_model->addIndex($this->tableName, 'idx_updated_at', 'updated_at');
            // $this->player_model->addIndex($this->tableName, 'idx_expires_at', 'expires_at');
            $this->player_model->addIndex($this->tableName, 'idx_player_id', 'player_id');
            $this->player_model->addIndex($this->tableName, 'idx_status', 'status');
            $this->player_model->addIndex($this->tableName, 'idx_promo_cms_id', 'promo_cms_id');
        }
    }

    public function down()
    {
        if ($this->utils->table_really_exists($this->tableName)) {
            $this->dbforge->drop_table($this->tableName);
        }
    }
}
