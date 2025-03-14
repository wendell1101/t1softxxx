<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_cancel_time_on_ebet_game_logs_20241014 extends CI_Migration {
    private $tableName = 'ebet_game_logs';

    public function up()
    {
        # Add column
        $field = array(
            'cancelTime' => array(
				'type' => 'TIMESTAMP',
				'null' => true,
			),
        );
        if(!$this->db->field_exists('cancelTime', $this->tableName)){
            $this->dbforge->add_column($this->tableName, $field);
        }

        $this->player_model->addIndex($this->tableName, 'idx_cancel_time', 'cancelTime');
    }

    public function down()
    {
        $this->dbforge->drop_column($this->tableName, 'cancelTime');
    }
}
