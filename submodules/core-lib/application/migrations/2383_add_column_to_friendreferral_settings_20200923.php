<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_to_friendreferral_settings_20200923 extends CI_Migration {

    private $tableName = 'friendreferralsettings';

	public function up() {
		$fields1 = array(
			'enabled_referral_limit' => array(
                'type' => 'TINYINT',
                'null' => true,
			),
        );
        if(!$this->db->field_exists('enabled_referral_limit', $this->tableName)){
		    $this->dbforge->add_column($this->tableName, $fields1);
        }
	}

	public function down() {
        
        if($this->db->field_exists('enabled_referral_limit', $this->tableName)){
            $this->dbforge->drop_column($this->tableName, 'enabled_referral_limit');
        }
	}
}