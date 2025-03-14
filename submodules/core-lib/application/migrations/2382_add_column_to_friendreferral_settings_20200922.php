<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_to_friendreferral_settings_20200922 extends CI_Migration {

    private $tableName = 'friendreferralsettings';

	public function up() {
		$fields1 = array(
			'max_referral_count' => array(
				'type' => 'INT',
				'null' => TRUE,
			),
        );
		$fields2 = array(
			'max_referral_released' => array(
				'type' => 'INT',
				'null' => TRUE,
			),
        );
        if(!$this->db->field_exists('max_referral_count', $this->tableName)){
		    $this->dbforge->add_column($this->tableName, $fields1);
        }
        if(!$this->db->field_exists('max_referral_released', $this->tableName)){
		    $this->dbforge->add_column($this->tableName, $fields2);
        }
	}

	public function down() {
        
        if($this->db->field_exists('max_referral_count', $this->tableName)){
            $this->dbforge->drop_column($this->tableName, 'max_referral_count');
        }
        if($this->db->field_exists('max_referral_released', $this->tableName)){
            $this->dbforge->drop_column($this->tableName, 'max_referral_released');
        }
	}
}