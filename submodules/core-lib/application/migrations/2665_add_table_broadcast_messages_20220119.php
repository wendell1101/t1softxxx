<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_table_broadcast_messages_20220119 extends CI_Migration {

	private $tableName = 'broadcast_messages';

	public function up() {
		$fields=array(
            'id' => array(
                'type' => 'BIGINT',
                'null' => false,
                'auto_increment' => TRUE,
            ),
            'adminId' => array(
                'type' => 'INT',
                'unsigned' => TRUE,
                'null' => false,
            ),
            'date' => array(
                'type' => 'DATETIME DEFAULT CURRENT_TIMESTAMP',
                'null' => false,
            ),
			'status' => array(
                'type' => 'TINYINT',
                'null' => false,
            ),
            'subject' => array(
                'type' => 'VARCHAR',
                'constraint' => '45',
                'null' => true,
            ),
            'message' => array(
                'type' => 'text',
                'null' => true,
            ),
            'isDeleted' => array(
                'type' => 'INT',
                'null' => false,
                'default' => 0,
            ),
            'deletedAt' => array(
                'type' => 'TIMESTAMP',
                'null' => true,
            ),
            'deletedBy' => array(
                'type' => 'INT',
                'null' => true,
            ),
            'externalId' => array(
                'type' => 'INT',
                'unsigned' => TRUE,
                'null' => true,
            ),
        );

        if(!$this->db->table_exists($this->tableName)){
            $this->dbforge->add_field($fields);
            $this->dbforge->add_key('id', TRUE);
            $this->dbforge->create_table($this->tableName);
            # Add Index
            $this->load->model('player_model');
            $this->player_model->addIndex('broadcast_messages','idx_date' , 'date');
            $this->player_model->addIndex('broadcast_messages','idx_status' , 'status');
            $this->player_model->addIndex('broadcast_messages','idx_isDeleted' , 'isDeleted');
            $this->player_model->addIndex('broadcast_messages','idx_externalId' , 'externalId');
        }
	}

	public function down() {
        if($this->db->table_exists($this->tableName)){
            $this->dbforge->drop_table($this->tableName);
        }
    }
}
