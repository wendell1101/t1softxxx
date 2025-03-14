<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_row_to_registration_fields_20201222 extends CI_Migration {

    private $tableName = 'registration_fields';

	public function up() {

        $this->db->where('alias', 'player_preference_site_notification');
        $this->db->where('type', '1');
        $this->db->delete($this->tableName);

        $this->db->where('alias', 'player_preference_push_notification');
        $this->db->where('type', '1');
        $this->db->delete($this->tableName);

        $data = array(
            array(
                'registrationFieldId' => 59,
                'field_order' => 59,
                'type' => '1',
                'field_name' => 'Player Preference Site Notification',
                'alias' => 'player_preference_site_notification',
                'visible' => '1',
                'required' => '1',
                'updatedOn' => date("Y-m-d H:i:s"),
            ),
            array(
                'registrationFieldId' => 60,
                'field_order' => 60,
                'type' => '1',
                'field_name' => 'Player Preference Push Notification',
                'alias' => 'player_preference_push_notification',
                'visible' => '1',
                'required' => '1',
                'updatedOn' => date("Y-m-d H:i:s"),
            ),
        );

        $this->db->insert_batch('registration_fields', $data);
	}

	public function down() {}
}
