<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_to_affiliates_20220712 extends CI_Migration {

    private $tableName = 'affiliates';

    public function up() {
        $field = array(
            'dispatch_account_level_id_on_registering' => array(
                'type' => 'INT',
                'null' => false,
                'default' => 0
            )
        );

        if($this->utils->table_really_exists($this->tableName)){
            $this->load->model('player_model');

            if(!$this->db->field_exists('dispatch_account_level_id_on_registering', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $field);
                // $this->player_model->addIndex($this->tableName, 'idx_dispatch_account_level_id_on_registering', 'dispatch_account_level_id_on_registering');
            }
        }
    }

    public function down() {
        if($this->utils->table_really_exists($this->tableName)){
            if($this->db->field_exists('dispatch_account_level_id_on_registering', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'dispatch_account_level_id_on_registering');
            }
        }
    }
}
