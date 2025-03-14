<?php

/**
 * Trait Year_month_table_module
 *
 * This trait provides reusable methods and functionality related to 
 * handling tables that are organized by year and month. 
 * It can be included in any class to simplify operations such as 
 * querying or managing year-month structured data.
 * 
 * @author Melvin (melvin.php.ph)
 */
trait Year_month_table_module {
    public $ymt_original_table_name;

    public function ymt_initialize($table_name, $initialize_tables = false) {
        $this->ymt_original_table_name = $table_name;

        if ($initialize_tables) {
            $this->ymt_initialize_tables($table_name);
        }
    }

    public function ymt_initialize_tables($table_name = null) {
        $this->ymt_get_current_year_month_table($table_name);
        $this->ymt_get_previous_year_month_table($table_name);
        $this->ymt_get_next_year_month_table($table_name);
    }

    public function ymt_create_year_month_table($table_name = null, $year_month = null) {
        $this->CI->load->model('Original_seamless_wallet_transactions', 'ymt_base_model');

        if (empty($table_name)) {
            $table_name = $this->ymt_original_table_name;
        }

        /* if (!$this->utils->isTableExists($table_name)) {
            $this->utils->debug_log(__CLASS__, __METHOD__, 'original table name not exists', $table_name);
            return false;
        } */

        if (empty($year_month)) {
            // default current month
            $year_month = $this->utils->getThisYearMonth();
        }

        $ym_table_name = "{$table_name}_{$year_month}";

        if (!$this->utils->isTableExists($ym_table_name)) {
            try {
                $this->utils->debug_log(__CLASS__, __METHOD__, 'original table name', $table_name, 'create table: ' . $ym_table_name);
                $this->CI->ymt_base_model->runRawUpdateInsertSQL("CREATE TABLE {$ym_table_name} LIKE {$table_name}");
            } catch(Exception $e) {
                $this->utils->debug_log(__CLASS__, __METHOD__, 'original table name', $table_name, 'create table failed: ' . $ym_table_name, $e);
            }
        }

        return $ym_table_name;
    }

    public function ymt_get_current_year_month_table($table_name = null) {
        if (empty($table_name)) {
            $table_name = $this->ymt_original_table_name;
        }

        return $this->ymt_create_year_month_table($table_name, $this->utils->getThisYearMonth());
    }

    public function ymt_get_previous_year_month_table($table_name = null, $date = null) {
        if (empty($table_name)) {
            $table_name = $this->ymt_original_table_name;
        }

        if (empty($date)) {
            $date = $this->utils->getNowForMysql();
        }

        return $this->ymt_create_year_month_table($table_name, $this->utils->getPreviousYearMonthByDate($date));
    }

    public function ymt_get_next_year_month_table($table_name = null, $date = null) {
        if (empty($table_name)) {
            $table_name = $this->ymt_original_table_name;
        }

        if (empty($date)) {
            $date = $this->utils->getNowForMysql();
        }

        return $this->ymt_create_year_month_table($table_name, $this->utils->getNextYearMonthByDate($date));
    }

    public function ymt_check_previous_year_month_data($force_check = false) {
        if ($force_check) {
            return true;
        }

        if ($this->utils->isFirstDateOfCurrentMonth()) {
            return true;
        }

        return false;
    }

    public function ymt_get_year_month_table_by_date($table_name = null, $date) {
        if (empty($table_name)) {
            $table_name = $this->ymt_original_table_name;
        }

        $ym_table_name = $this->ymt_get_current_year_month_table($table_name);
        $ym = $this->utils->getYearMonthByDate($date);
        $temp_table_name = $table_name . '_' .  $ym;

        if ($this->utils->isTableExists($temp_table_name)) {
            $ym_table_name = $temp_table_name;
        }

        return $ym_table_name;
    }
}