<?php

defined("BASEPATH") OR exit("No direct script access allowed");

class Migration_add_column_to_playerdetails_20221111 extends CI_Migration
{
	private $tableName = 'player_preference';


    public function up() {
        $this->load->model('player_model');


        $fields = array(
            'username_on_register' => array(
                'type' => 'VARCHAR',
                'constraint' => '200',
                'null' => true
            )
        );

        if($this->utils->table_really_exists($this->tableName)){

            if(!$this->db->field_exists('username_on_register', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $fields);

                $this->player_model->addIndex($this->tableName,'idx_username_on_register','username_on_register');
            }

        }

    }

    public function down() {
        if($this->utils->table_really_exists($this->tableName)){
            if($this->db->field_exists('username_on_register', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'username_on_register');
            }
        }



    }
}