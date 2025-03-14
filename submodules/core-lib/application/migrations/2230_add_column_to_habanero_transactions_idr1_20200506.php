<?php

defined("BASEPATH") OR exit("No direct script access allowed");

class Migration_add_column_to_habanero_transactions_idr1_20200506 extends CI_Migration
{
	private $tableName = 'habanero_transactions_idr1';

    public function up() {

        $fields1 = array(
            'altcredittype' => array(
                "type" => "TINYINT",
                "null" => true		
            ),
        );

        $fields2 = array(
            'description' => array(
                "type" => "TEXT",
                "null" => true		
            ),
        );

        $fields3 = array(
            'tournamentdetails_score' => array(
                'type' => 'double',
                'null' => true,	
            ),
        );

        $fields4 = array(
            'tournamentdetails_rank' => array(
                'type' => 'INT',
                'constraint' => '11',
                'null' => true,		
            ),
        );

        $fields5 = array(
            'tournamentdetails_tournamenteventid' => array(
                'type' => 'INT',
                'constraint' => '11',
                'null' => true,
            ),
        );

        $fields6 = array(
            'fundinfo_accounttransactiontype' => array(
                'type' => 'INT',
                'constraint' => '11',
                'null' => true,
            ),
        );

        $fields7 = array(
            'fundinfo_gameinfeature' => array(
                "type" => "TINYINT",
                "null" => true
            ),
        );

        $fields8 = array(
            'fundinfo_lastbonusaction' => array(
                "type" => "TINYINT",
                "null" => true
            ),
        );

        
        

        if($this->utils->table_really_exists($this->tableName)){
            if(!$this->db->field_exists('altcredittype', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $fields1);
            }
            if(!$this->db->field_exists('description', $this->tableName)){
                $this->dbforge->add_column($this->tableName,$fields2);
            }
            if(!$this->db->field_exists('tournamentdetails_score', $this->tableName)){
                $this->dbforge->add_column($this->tableName,$fields3);
            }
            if(!$this->db->field_exists('tournamentdetails_rank', $this->tableName)){
                $this->dbforge->add_column($this->tableName,$fields4);
            }
            if(!$this->db->field_exists('tournamentdetails_tournamenteventid', $this->tableName)){
                $this->dbforge->add_column($this->tableName,$fields5);
            }
            if(!$this->db->field_exists('fundinfo_accounttransactiontype', $this->tableName)){
                $this->dbforge->add_column($this->tableName,$fields6);
            }
            if(!$this->db->field_exists('fundinfo_gameinfeature', $this->tableName)){
                $this->dbforge->add_column($this->tableName,$fields7);
            }
            if(!$this->db->field_exists('fundinfo_lastbonusaction', $this->tableName)){
                $this->dbforge->add_column($this->tableName,$fields8);
            }
        }
    }

    public function down() {
        if( $this->utils->table_really_exists($this->tableName)){
            if($this->db->field_exists('altcredittype', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'altcredittype');
            }
            if($this->db->field_exists('description', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'description');
            }
            if($this->db->field_exists('tournamentdetails_score', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'tournamentdetails_score');
            }
            if($this->db->field_exists('tournamentdetails_rank', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'tournamentdetails_rank');
            }
            if($this->db->field_exists('tournamentdetails_tournamenteventid', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'tournamentdetails_tournamenteventid');
            }
            if($this->db->field_exists('fundinfo_accounttransactiontype', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'fundinfo_accounttransactiontype');
            }
            if($this->db->field_exists('fundinfo_gameinfeature', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'fundinfo_gameinfeature');
            }
            if($this->db->field_exists('fundinfo_lastbonusaction', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'fundinfo_lastbonusaction');
            }
        }
    }
}