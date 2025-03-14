<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_add_table_cmspopup_20211112 extends CI_Migration
{
    private $tableName = 'cmspopup';

    public function up()
    {
        $fields = array(
            'id' => array(
                'type' => 'BIGINT',
                'null' => false,
                'auto_increment' => true,
            ),
            'title' => array(
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true,
            ),
             'content' => array(
                'type' => 'TEXT',
                'null' => true,
            ),
            'categoryId' => array(
                'type' => 'INT',
                'null' => false
            ),
            'creator_user_id' => array(
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true,
            ),
            'set_visible' => array(
                'type' => 'BOOLEAN',
                'null' => false,
                'default' => 0
            ),
            'display_in' => array(
                'type' => 'VARCHAR',
                'constraint' => '45',
                'null' => true,
            ),
            'display_freq' => array(
                'type' => 'VARCHAR',
                'constraint' => '45',
                'null' => true,
            ),
            'redirect_to' => array(
                'type' => 'VARCHAR',
                'constraint' => '45',
                'null' => false,
                'default' => 'disable',
            ),
            'redirect_type' => array(
                'type' => 'INT',
                'null' => true
            ),
            'redirect_btn_name' => array(
                'type' => 'VARCHAR',
                'constraint' => '200',
                'null' => true,
            ),
            'is_daterange' => array(
                'type' => 'BOOLEAN',
                'null' => false,
                'default' => 0
            ),
            'start_date' => array(
                'type' => 'DATETIME',
                'null' => true
            ),
            'end_date' => array(
                'type' => 'DATETIME',
                'null' => true
            ),
            'is_default_banner' => array(
                'type' => 'BOOLEAN',
                'null' => false,
                'default' => 1
            ),
            'banner_url' => array(
                'type' => 'VARCHAR',
                'constraint' => '200',
                'null' => true,
            ),
            'created_at DATETIME DEFAULT CURRENT_TIMESTAMP' => array(
                'null' => false,
            ),
            'updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP' => array(
                'null' => false,
            ),
            'deleted_on DATETIME' => array(
                'null' => true
            ),
        );

        if (!$this->utils->table_really_exists($this->tableName)) {
            $this->dbforge->add_field($fields);
            $this->dbforge->add_key('id', true);
            $this->dbforge->create_table($this->tableName);

            $this->load->model('player_model'); # Any model class will do
            $this->player_model->addIndex($this->tableName, 'idx_creator_user_id', 'creator_user_id');
            $this->player_model->addIndex($this->tableName, 'idx_start_date', 'start_date');
            $this->player_model->addIndex($this->tableName, 'idx_end_date', 'end_date');
        }
    }

    public function down()
    {
    }
}
