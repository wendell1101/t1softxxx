<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_to_affiliates_20210718 extends CI_Migration {

    private $tableName = 'affiliates';

    public function up() {
        $field = array(
            'domainUpdateOn' => array(
                'type' => 'DATETIME',
                'null' => true
            )
        );

        if($this->utils->table_really_exists($this->tableName)){
            $this->load->model('player_model');

            if(!$this->db->field_exists('domainUpdateOn', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $field, 'affdomain');
            }
        }
    }

    public function down()
    {}
}
