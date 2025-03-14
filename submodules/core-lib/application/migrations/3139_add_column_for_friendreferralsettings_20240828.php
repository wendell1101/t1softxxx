<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_for_friendreferralsettings_20240828 extends CI_Migration {

    private $tableName='friendreferralsettings';

    public function up() {
        $column = array(
            'disabled_same_ips_with_inviter' => array(
                'type' => 'TINYINT',
                'constraint' => 1,
                'null' => false,
                'default' => 0
            )
        );

        if($this->utils->table_really_exists($this->tableName)){
            if(!$this->db->field_exists('disabled_same_ips_with_inviter', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $column);
            }
        }
    }

    public function down() {
        if($this->utils->table_really_exists($this->tableName)){
            if($this->db->field_exists('disabled_same_ips_with_inviter', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'disabled_same_ips_with_inviter');
            }
        }
    }
}