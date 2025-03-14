<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_table_adminuser_login_history_20210310 extends CI_Migration {

    public function up() {

        if(!$this->utils->table_really_exists('adminuser_login_history')){

            $this->dbforge->add_field(array(
                'id' => array(
                    'type' => 'INT',
                    'unsigned' => TRUE,
                    'auto_increment' => TRUE,
                ),
                'admin_username' => array(
                    'type' => 'VARCHAR',
                    'constraint' => '200',
                    'null' => false,
                ),
                'otp_code' => array(
                    'type' => 'VARCHAR',
                    'constraint' => '50',
                    'null' => true,
                ),
                'login_ip' => array(
                    'type' => 'VARCHAR',
                    'constraint' => '50',
                    'null' => true,
                ),
                'remote_addr' => array(
                    'type' => 'VARCHAR',
                    'constraint' => '50',
                    'null' => true,
                ),
                'x_forwarded_for' => array(
                    'type' => 'VARCHAR',
                    'constraint' => '200',
                    'null' => true,
                ),
                'session_id' => array(
                    'type' => 'VARCHAR',
                    'constraint' => '100',
                    'null' => true,
                ),
                'login_url' => array(
                    'type' => 'VARCHAR',
                    'constraint' => '200',
                    'null' => true,
                ),
                'referrer' => array(
                    'type' => 'VARCHAR',
                    'constraint' => '200',
                    'null' => true,
                ),
                'login_result' => array(
                    'type' => 'INT',
                    'null' => true,
                ),
                'created_at' => array(
                    'type' => 'DATETIME',
                    'null' => true,
                ),
            ));
            $this->dbforge->add_key('id', TRUE);
            $this->dbforge->add_key('created_at');
            $this->dbforge->add_key('admin_username');
            $this->dbforge->add_key('login_ip');

            $this->dbforge->create_table('adminuser_login_history');
        }
    }

    public function down() {
    }
}
