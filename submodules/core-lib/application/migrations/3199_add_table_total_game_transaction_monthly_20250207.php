<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_table_total_game_transaction_monthly_20250207 extends CI_Migration {

    private $tableName = 'total_game_transaction_monthly';

    public function up()
    {
        $fields = array(
            'id' => array(
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'auto_increment' => true,
            ),
            'player_id' => array(
                'type' => 'INT',
                'constraint' => 11,
                'null' => true,
            ),
            'player_username' => array(
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true,
            ),
            'total_amount_deposit' => array(
                'type' => 'DOUBLE',
                'null' => true,
            ),
            'total_amount_withdraw' => array(
                'type' => 'DOUBLE',
                'null' => true,
            ),
            'total_bonus' => array(
                'type' => 'DOUBLE',
                'null' => true,
            ),
            'total_bet_amount' => array(
                'type' => 'DOUBLE',
                'null' => true,
            ),
            'total_net_loss' => array(
                'type' => 'DOUBLE',
                'null' => true,
            ),
            'year_month' => array(
                'type' => 'INT',
                'constraint' => 6,
                'null' => false,
            ),
            'unique_id' => array(
                'type' => 'VARCHAR',
                'constraint' => '300',
                'null' => false,
            )
        );

        if (!$this->db->table_exists($this->tableName)) {
            $this->dbforge->add_field($fields);
            $this->dbforge->add_key('id', true); // Set PRIMARY KEY for AUTO_INCREMENT column
            $this->dbforge->add_key('player_id'); // Add index for player_id
            $this->dbforge->add_key('year_month'); // Add index for year_month
            $this->dbforge->create_table($this->tableName);

            # Add Index
            $this->load->model('player_model');
            # Add unique index for unique_id
            $this->player_model->addUniqueIndex($this->tableName, 'idx_unique_id', 'unique_id');
        }
    }

    public function down() {
        $this->dbforge->drop_table($this->tableName, TRUE);
    }
}
