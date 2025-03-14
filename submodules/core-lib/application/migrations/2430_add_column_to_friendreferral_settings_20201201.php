<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_to_friendreferral_settings_20201201 extends CI_Migration {

    private $tableName = 'friendreferralsettings';

    public function up() {
        $fields1 = array(
            'enabled_referral_limit_monthly' => array(
                'type' => 'TINYINT',
                'constraint' => 1,
                'null' => false,
                'default' => 0
            ),
        );
        if(!$this->db->field_exists('enabled_referral_limit_monthly', $this->tableName)){
            $this->dbforge->add_column($this->tableName, $fields1);
        }
    }

    public function down() {

        if($this->db->field_exists('enabled_referral_limit_monthly', $this->tableName)){
            $this->dbforge->drop_column($this->tableName, 'enabled_referral_limit_monthly');
        }
    }
}