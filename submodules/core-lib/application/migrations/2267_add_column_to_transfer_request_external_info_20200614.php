<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_to_transfer_request_external_info_20200614 extends CI_Migration {

    private $tableName = 'transfer_request_external_info';

    public function up() {
        $fields = array(
            'related_transfer_request_id' => array(
                'type' => 'INT',
                'null' => true
            ),
        );

        if(!$this->db->field_exists('related_transfer_request_id', $this->tableName)){
            $this->dbforge->add_column($this->tableName, $fields);
            $this->load->model(['player_model']);
            $this->player_model->addIndex($this->tableName, 'idx_related_transfer_request_id', 'related_transfer_request_id');
        }
    }

    public function down() {
        if($this->db->field_exists('related_transfer_request_id', $this->tableName)){
            $this->dbforge->drop_column($this->tableName, 'related_transfer_request_id');
        }
    }
}
