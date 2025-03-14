<?php

defined('BASEPATH') or exit('No direct script access allowed');
class Migration_add_table_adminuser_telesale_20240430 extends CI_Migration
{
    private $tableName = 'adminuser_telesale';

    public function up()
    {
        $fields = array(
            'id' => array(
                'type' => 'INT',
                'unsigned' => true,
                'auto_increment' => true,
            ),
            'userId' => array(
                'type' => 'INT',
				'null' => true,
            ),
            'systemCode' => array(
                'type' => 'INT',
				'null' => true,
                'unsigned' => true,
            ),
            'tele_id' => array(
                'type' => 'VARCHAR',
                'constraint' => '50',
                'null' => true,
            ),
            'createBy' => array(
                'type' => 'INT',
                'unsigned' => true,
                'null' => true,
            ),
            'created_at DATETIME DEFAULT CURRENT_TIMESTAMP' => array(
                'null' => false
            ),
            'updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP' => array(
                'null' => false
            )
        );

        if (!$this->utils->table_really_exists($this->tableName)) {
            $this->dbforge->add_field($fields);
            $this->dbforge->add_key('id', true);
            $this->dbforge->create_table($this->tableName);

			# Add Index
            $this->load->model('player_model');
            $this->player_model->addIndex($this->tableName, 'idx_userId', 'userId');
            $this->player_model->addIndex($this->tableName, 'idx_systemCode', 'systemCode');

        }
    }

    public function down()
    {
        if ($this->utils->table_really_exists($this->tableName)) {
            $this->dbforge->drop_table($this->tableName);
        }
    }
}