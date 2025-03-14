<?php

defined('BASEPATH') OR exit('No direct script access allowed');


class Migration_add_column_to_betgames_wallet_transactions_20200910 extends CI_Migration
{
    private $tableName = 'betgames_wallet_transactions';

    public function up()
    {
        $fields = array(
            'odd_name' => array(
                'type' => 'VARCHAR',
                'constraint' => '360',
                'null' => true,
            ),
        );

        if(!$this->db->field_exists('odd_name', $this->tableName)){
            $this->dbforge->add_column($this->tableName, $fields);
        }
    }

    public function down()
    {
        $this->dbforge->drop_column($this->tableName, 'odd_name');
    }
}
