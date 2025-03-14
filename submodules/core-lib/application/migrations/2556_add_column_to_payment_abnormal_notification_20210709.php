<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_to_payment_abnormal_notification_20210709 extends CI_Migration {

    private $tableName = 'payment_abnormal_notification';

    public function up() {
        $field = array(
            'order_id' => array(
                'type' => 'INT',
                'unsigned' => TRUE,
                'null' => true,
            )
        );

        $field2 = array(
            'amount' => array(
                'type' => 'DOUBLE',
                'unsigned' => TRUE,
                'null' => true,
                'default' => 0,
            ),
        );

        if($this->utils->table_really_exists($this->tableName)){
            $this->load->model('player_model');
            if(!$this->db->field_exists('order_id', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $field);
                $this->player_model->addIndex('payment_abnormal_notification', 'idx_order_id', 'order_id');
            }

            if(!$this->db->field_exists('amount', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $field2);
            }
        }
    }

    public function down() {
        if($this->utils->table_really_exists($this->tableName)){

            if($this->db->field_exists('order_id', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'order_id');
            }
            if($this->db->field_exists('amount', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'amount');
            }
        }
    }
}
