<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_row_to_registration_fields_20231116 extends CI_Migration {

    private $tableName = 'registration_fields';

    public function up() {

        $this->db->where('alias', 'player_preference_imaccount');
        $this->db->where('type', '1');
        $this->db->delete($this->tableName);

        $this->db->where('alias', 'player_preference_imaccount2');
        $this->db->where('type', '1');
        $this->db->delete($this->tableName);

        $this->db->where('alias', 'player_preference_imaccount3');
        $this->db->where('type', '1');
        $this->db->delete($this->tableName);

        $this->db->where('alias', 'player_preference_imaccount4');
        $this->db->where('type', '1');
        $this->db->delete($this->tableName);

        $this->db->where('alias', 'player_preference_imaccount5');
        $this->db->where('type', '1');
        $this->db->delete($this->tableName);

        $this->db->where('alias', 'player_preference_time_preference');
        $this->db->where('type', '1');
        $this->db->delete($this->tableName);

        $data = array(
            array(
                'registrationFieldId' => 70,
                'field_order' => 70,
                'type' => '1',
                'field_name' => 'Player Preference ImAccount',
                'alias' => 'player_preference_imaccount',
                'visible' => '1',
                'required' => '1',
                'updatedOn' => date("Y-m-d H:i:s"),
            ),
            array(
                'registrationFieldId' => 71,
                'field_order' => 71,
                'type' => '1',
                'field_name' => 'Player Preference ImAccount2',
                'alias' => 'player_preference_imaccount2',
                'visible' => '1',
                'required' => '1',
                'updatedOn' => date("Y-m-d H:i:s"),
            ),
            array(
                'registrationFieldId' => 72,
                'field_order' => 72,
                'type' => '1',
                'field_name' => 'Player Preference ImAccount3',
                'alias' => 'player_preference_imaccount3',
                'visible' => '1',
                'required' => '1',
                'updatedOn' => date("Y-m-d H:i:s"),
            ),
            array(
                'registrationFieldId' => 73,
                'field_order' => 73,
                'type' => '1',
                'field_name' => 'Player Preference ImAccount4',
                'alias' => 'player_preference_imaccount4',
                'visible' => '1',
                'required' => '1',
                'updatedOn' => date("Y-m-d H:i:s"),
            ),
            array(
                'registrationFieldId' => 74,
                'field_order' => 74,
                'type' => '1',
                'field_name' => 'Player Preference ImAccount5',
                'alias' => 'player_preference_imaccount5',
                'visible' => '1',
                'required' => '1',
                'updatedOn' => date("Y-m-d H:i:s"),
            ),
            array(
                'registrationFieldId' => 75,
                'field_order' => 75,
                'type' => '1',
                'field_name' => 'Player Preference Time Preference',
                'alias' => 'player_preference_time_preference',
                'visible' => '1',
                'required' => '1',
                'updatedOn' => date("Y-m-d H:i:s"),
            )
        );

        $this->db->insert_batch('registration_fields', $data);
	}

    public function down() {}
}
