<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_table_email_template_20200629 extends CI_Migration {

    private $tableName = 'email_template';

    public function up() {

        if( $this->db->table_exists($this->tableName)){
            $this->load->library("email_manager");
            $this->load->model('email_template_model');

            # Player
            $this->email_manager->createTemplate(email_template_model::PLAYER_PLATFORM_TYPE, email_template_model::SYSTEM_NOTIFICATION, 'vip_level_upgraded_notification');
        }
    }

    public function down() {
        if($this->db->table_exists($this->tableName)){
            $this->dbforge->drop_table($this->tableName);
        }
    }
}
