<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_to_summary2_report_daily_and_monthly_20211012 extends CI_Migration {

    private $tableDaily = 'summary2_report_daily';
    private $tableMonthly = 'summary2_report_monthly';

    public function up() {
        
        $column1 = array(
            'total_withdrawal_fee_from_player' => array(
				'type' => 'DOUBLE',
				'null' => true,
				'default' => 0.00,
			),
        );

        $column2 = array(
            'total_withdrawal_fee_from_operator' => array(
                'type' => 'DOUBLE',
                'null' => true,
                'default' => 0.00,
            ),
        );

        if($this->utils->table_really_exists($this->tableDaily)){
            if(!$this->db->field_exists('total_withdrawal_fee_from_player', $this->tableDaily)){
                $this->dbforge->add_column($this->tableDaily, $column1);
            }
            if(!$this->db->field_exists('total_withdrawal_fee_from_operator', $this->tableDaily)){
                $this->dbforge->add_column($this->tableDaily, $column2);
            }
        }

        if($this->utils->table_really_exists($this->tableMonthly)){
            if(!$this->db->field_exists('total_withdrawal_fee_from_player', $this->tableMonthly)){
                $this->dbforge->add_column($this->tableMonthly, $column1);
            }
            if(!$this->db->field_exists('total_withdrawal_fee_from_operator', $this->tableMonthly)){
                $this->dbforge->add_column($this->tableMonthly, $column2);
            }
        }
    }

    public function down() {
        if($this->utils->table_really_exists($this->tableDaily)){
            if($this->db->field_exists('total_withdrawal_fee_from_player', $this->tableDaily)){
                $this->dbforge->drop_column($this->tableDaily, 'total_withdrawal_fee_from_player');
            }
            if($this->db->field_exists('total_withdrawal_fee_from_operator', $this->tableDaily)){
                $this->dbforge->drop_column($this->tableDaily, 'total_withdrawal_fee_from_operator');
            }
        }

        if($this->utils->table_really_exists($this->tableMonthly)){
            if($this->db->field_exists('total_withdrawal_fee_from_player', $this->tableMonthly)){
                $this->dbforge->drop_column($this->tableMonthly, 'total_withdrawal_fee_from_player');
            }
            if($this->db->field_exists('total_withdrawal_fee_from_operator', $this->tableMonthly)){
                $this->dbforge->drop_column($this->tableMonthly, 'total_withdrawal_fee_from_operator');
            }
        }
    }
}