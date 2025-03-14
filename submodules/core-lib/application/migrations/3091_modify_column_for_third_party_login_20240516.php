<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_modify_column_for_third_party_login_20240516 extends CI_Migration
{
    private $tableName = 'third_party_login';

    public function up()
    {
        $tableName = 'third_party_login';
        if ($this->utils->table_really_exists($tableName)) {
            if ($this->db->field_exists('extra_info', $tableName)) {
                $this->dbforge->modify_column($tableName, [
                    'extra_info' => [
                        'type' => 'JSON',
                        'null' => true,
                    ],
                ]);
            }
        }

        $tableName = 'event_hooks';
        if ($this->utils->table_really_exists($tableName)) {
            if ($this->db->field_exists('eventConditions', $tableName)) {
                $this->dbforge->modify_column($tableName, [
                    'eventConditions' => [
                        'type' => 'JSON',
                        'null' => true,
                    ],
                ]);
            }
            if ($this->db->field_exists('hookConditions', $tableName)) {
                $this->dbforge->modify_column($tableName, [
                    'hookConditions' => [
                        'type' => 'JSON',
                        'null' => true,
                    ],
                ]);
            }
            if ($this->db->field_exists('params', $tableName)) {
                $this->dbforge->modify_column($tableName, [
                    'params' => [
                        'type' => 'JSON',
                        'null' => true,
                    ],
                ]);
            }
        }
    }
    public function down()
    {
    }
}
