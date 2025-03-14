<?php

defined('BASEPATH') OR exit('No direct script access allowed');


class Migration_add_column_to_dispatch_withdrawal_conditions_20200831 extends CI_Migration
{
    private $tableName = 'dispatch_withdrawal_conditions';

    public function up()
    {


        if( ! $this->db->field_exists('extra', $this->tableName) ){
            $fields = array(
                "extra" => array(
                    "type" => "JSON",
                    "null" => true
                ),
            );
            $this->dbforge->add_column($this->tableName, $fields);
            // $this->player_model->addIndex($this->tableName, 'idx_extra', 'extra');
        }

    }

    public function down()
    {
        $this->dbforge->drop_column($this->tableName, 'extra');
    }
}
