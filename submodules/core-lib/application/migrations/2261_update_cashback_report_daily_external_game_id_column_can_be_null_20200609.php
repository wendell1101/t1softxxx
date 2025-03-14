<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_update_cashback_report_daily_external_game_id_column_can_be_null_20200609 extends CI_Migration {

    private $tableName = 'cashback_report_daily';

    public function up() {
        //modify column not null
        $fields = array(
            'external_game_id' => array(
                'type' => 'VARCHAR',
                'constraint' => '300',
                'null' => true,
            ),
        );
        $this->dbforge->modify_column($this->tableName, $fields);
    }

    public function down() {
        // not able to rollback due to data truncation
    }
}