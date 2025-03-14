<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_table_backend_ip_history_20210307 extends CI_Migration {

    public function up() {

        if(!$this->utils->table_really_exists('backend_ip_history')){

            $this->dbforge->add_field(array(
                'id' => array(
                    'type' => 'INT',
                    'unsigned' => TRUE,
                    'auto_increment' => TRUE,
                ),
                'admin_user_id' => array(
                    'type' => 'INT',
                    'null' => true,
                ),
                'created_at' => array(
                    'type' => 'DATETIME',
                    'null' => true,
                ),
                'history' => array(
                    'type' => 'JSON',
                    'null' => true,
                ),
            ));
            $this->dbforge->add_key('id', TRUE);
            $this->dbforge->add_key('created_at');
            $this->dbforge->add_key('admin_user_id');

            $this->dbforge->create_table('backend_ip_history');
        }
    }

    public function down() {
    }
}
