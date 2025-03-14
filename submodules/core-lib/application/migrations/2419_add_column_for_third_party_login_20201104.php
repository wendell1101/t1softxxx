<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_for_third_party_login_20201104 extends CI_Migration {

    private $tableName='third_party_login';

    public function up() {
        $field = array(
            'pre_register_form' => array(
                "type" => "JSON",
                'null' => true
            )
        );

        if($this->utils->table_really_exists($this->tableName)){
            if( ! $this->db->field_exists('pre_register_form', $this->tableName) ){
                $this->dbforge->add_column($this->tableName, $field);
            }
        }

    }

    public function down() {
        if( $this->utils->table_really_exists($this->tableName)){
            if($this->db->field_exists('pre_register_form', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'pre_register_form');
            }
        }

    }
}