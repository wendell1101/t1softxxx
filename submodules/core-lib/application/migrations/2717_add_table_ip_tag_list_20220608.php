<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_table_ip_tag_list_20220608 extends CI_Migration {

    private $tableName = 'ip_tag_list';

    public function up() {
        $fields = array(
            'id' => array(
                'type' => 'INT',
                'null' => false,
                'auto_increment' => TRUE,
            ),
            'name' => array(
                'type' => 'varchar',
                'constraint' => '255',
                'null' => false,
            ),
            'description' => array(
                'type' => 'varchar',
                'constraint' => '500',
                'null' => false,
            ),
            'ip' => array(
				'type' => 'VARCHAR',
				'constraint' => '32',
				'null' => false,
			),
            'color' => array(
				'type' => 'VARCHAR',
                'constraint' => '12',
				'null' => TRUE,
			),
            'created_at DATETIME DEFAULT CURRENT_TIMESTAMP' => array(
                'null' => false,
            ),
            'created_by' => array(
                'type' => 'INT',
                'null' => false,
                'default' => 0
            ),
            'updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP' => array(
                'null' => false,
            ),
        );

        if(!$this->db->table_exists($this->tableName)){
            $this->dbforge->add_field($fields);
            $this->dbforge->add_key('id', TRUE);
            $this->dbforge->create_table($this->tableName);
            # Add Index
            $this->load->model('player_model');
            $this->player_model->addIndex($this->tableName, 'idx_name', 'name');
            $this->player_model->addIndex($this->tableName, 'idx_color', 'color');
            $this->player_model->addIndex($this->tableName, 'idx_description', 'description');
            $this->player_model->addIndex($this->tableName, 'idx_ip', 'ip');
            $this->player_model->addIndex($this->tableName, 'idx_created_at', 'created_at');
            $this->player_model->addIndex($this->tableName, 'idx_created_by', 'created_by');
            $this->player_model->addIndex($this->tableName, 'idx_updated_at', 'updated_at');

        }

    }

    public function down() {
        $this->dbforge->drop_table($this->tableName);
    }
}
