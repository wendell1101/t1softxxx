<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_table_payment_abnormal_notification_20201223 extends CI_Migration {

    private $tableName = 'payment_abnormal_notification';

    public function up() {
        $fields=array(
            'id' => array(
                'type' => 'INT',
                'unsigned' => TRUE,
                'auto_increment' => TRUE,
            ),
            'playerId' => array(
                'type' => 'INT',
                'unsigned' => TRUE,
                'null' => true,
            ),
            'type' => array(
                'type' => 'TINYINT',
                'null' => false,
            ),
            'status' => array(
                'type' => 'TINYINT',
                'null' => false,
            ),
            'created_at' => array(
                'type' => 'DATETIME DEFAULT CURRENT_TIMESTAMP',
                'null' => false,
            ),
            'update_at' => array(
                'type' => 'DATETIME DEFAULT CURRENT_TIMESTAMP',
                'null' => false,
            ),
            'update_by' => array(
                'type' => 'VARCHAR',
                'constraint' => '45',
                'null' => true,
            ),
            'abnormal_payment_name' => array(
                'type' => 'VARCHAR',
                'constraint' => '300',
                'null' => true,
            ),
            'notes' => array(
                'type' => 'TEXT',
                'null' => false,
            )
        );

        if(!$this->db->table_exists($this->tableName)){
            $this->dbforge->add_field($fields);
            $this->dbforge->add_key('id', TRUE);
            $this->dbforge->create_table($this->tableName);
            # Add Index
            $this->load->model('player_model');
            $this->player_model->addIndex('payment_abnormal_notification','idx_playerId' , 'playerId');
            $this->player_model->addIndex('payment_abnormal_notification','idx_type' , 'type');
            $this->player_model->addIndex('payment_abnormal_notification','idx_status' , 'status');
            $this->player_model->addIndex('payment_abnormal_notification','idx_created_at' , 'created_at');
            $this->player_model->addIndex('payment_abnormal_notification','idx_update_by' , 'update_by');
        }
    }

    public function down() {
        if($this->db->table_exists($this->tableName)){
            $this->dbforge->drop_table($this->tableName);
        }
    }
}
