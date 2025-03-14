<?php

trait monthly_partition_module {

    public function createSampleTableCallbackWithMonthlyPartitionByDate($tableName){
        if (!$this->utils->table_really_exists($tableName)) {
			try{
                $this->CI->load->dbforge();

                $fields=[
                    'logId' => [
                        'type' => 'INT',
                        'null' => false,
                        'auto_increment' => TRUE,
                        'unsigned' => true,
                    ],
                    'username' => [
                        'type' => 'VARCHAR',
                        'constraint'=>32,
                        'null'=> false,
                    ],
                    'logDate' => [
                        'type' => 'DATETIME',
                        'null'=> false,
                    ],
                    'status' => [
                        'type' => "enum('0','1')",
                        'null'=> false,
                        'default'=>'0',
                    ],
                ];
                $this->CI->dbforge->add_field($fields);
                $this->CI->dbforge->add_key('logId', TRUE); // P.K.
                $this->CI->dbforge->add_key('logDate');
                $this->CI->dbforge->add_key('username');
                $this->CI->dbforge->create_table($tableName);

			}catch(Exception $e){
				$this->error_log('create table failed: '.$tableName, $e);
			}
		} // EOF if (!$this->table_really_exists($tableName)) {...
        return $tableName;
    }
    public function combineMonthlyTableName($tableName, $dateTimeStr = 'now'){
        if( ! empty($dateTimeStr) ){
            $d=new DateTime($dateTimeStr);
            $yearMonthStr = $d->format('Ym');
            $_tableName = $tableName. '_'. $yearMonthStr;
        }else{
            $_tableName = $tableName;
        }

        return $_tableName;
    } // EOF combineMonthlyTableName
    public function parserMonthlyTableName($tableName){
        $rlt_list = [];
        $rlt_list['tableName'] = null;
        $rlt_list['suffix'] = null;

        $regex = '/(?P<tableName>[^\d{6}]+)(_(?P<Ym>\d{6}))?$/';
        preg_match_all($regex, $tableName, $matches, PREG_SET_ORDER, 0);
        if( !empty($matches) ){
            foreach($matches as $matche){
                if( ! empty($matche['tableName'])){
                    $rlt_list['tableName'] = $matche['tableName'];
                }
                if( ! empty($matche['Ym'])){
                    $rlt_list['suffix'] = $matche['Ym'];
                }
            } // EOF foreach($matches as $matche){...
        } // EOF if( !empty($matches) ){...
        return $rlt_list;
    } // EOF parserMonthlyTableName

    public function createTableWithMonthlyPartitionByDate($tableName, $dateTimeStr = 'now', callable $createTableCallback, &$hadExistsTableWithMonthlyPartition = null) {
        $_tableName = $this->combineMonthlyTableName($tableName, $dateTimeStr);
        if( ! empty($dateTimeStr) ){
            if ( ! $this->utils->table_really_exists($_tableName) ) {
                $hadExistsTableWithMonthlyPartition = false;
                try{
                    $createTableCallback($_tableName);
                }catch(Exception $e){
                    $this->utils->error_log('create table failed: '.$_tableName, $e);
                }
            }else{
                $hadExistsTableWithMonthlyPartition = true;
            }
        }
        return $_tableName;
    } // EOF createTableWithMonthlyPartitionByDate

    public function getTablenameWithMonthlyPartitionByDate($tableName, $dateTimeStr = 'now', callable $createTableCallback, $precreateNext = true) {
        if( empty($dateTimeStr) ){
            $_tablename = $tableName;
        }else{
            $d=new DateTime($dateTimeStr);
            $yearMonthStr = $d->format('Ym');
            $hadExistsTableWithMonthlyPartition = null; // for collect exists status, before create table.
            $_tablename = $this->createTableWithMonthlyPartitionByDate($tableName, $yearMonthStr, $createTableCallback, $hadExistsTableWithMonthlyPartition);
            $this->utils->debug_log('created the currect monthly table: ', $_tablename, 'precreateNext:', $precreateNext, 'hadExistsTableWithMonthlyPartition:', $hadExistsTableWithMonthlyPartition);
            if($precreateNext){ // pre-create Next monthly table
                $d->modify('+1 month');
                $dateTimeStr = $this->utils->formatDateTimeForMysql($d);
                $this->utils->debug_log('Will created the next month table, dateTimeStr: ', $dateTimeStr);
                $hadExistsTableWithMonthlyPartition = null;
                $_tablename4next =$this->createTableWithMonthlyPartitionByDate($tableName, $dateTimeStr, $createTableCallback, $hadExistsTableWithMonthlyPartition);
                if( ! $hadExistsTableWithMonthlyPartition ){
                    $this->utils->debug_log('created the next month table: ', $_tablename4next, 'hadExistsTableWithMonthlyPartition:', $hadExistsTableWithMonthlyPartition);
                }

            } // if($precreateNext){

        }
        return $_tablename;
    } // EOF getTablenameWithMonthlyPartitionByDate

    /**
     * Combine Union Subquery
     *
     *
     * @param array|null $_selectFields When empty() than its means all fields.
     * @param array $_tableNames The table name list for union.
     * @param array $_whereClauses The condition list in where clause.
     * @param boolean $do_select_splitted_table When true, then add the "splitted_table" field for output the partition table name.
     * @return string The Union Subquery
     */
    public function unionSubqueryWithSelectFromWhere($_selectFields = [], $_tableNames = [], $_whereClauses = [], $do_select_splitted_table = true){

        $uinonSubquery = '';
        $uinonSubqueryList = [];
        $_subqueryFormat = "SELECT  %s FROM %s WHERE %s "; // 3 params: fields, table and where.

        $_selectFieldsStr = $this->combineWithImplode($_selectFields, '*');
        $_whereClausesStr = $this->combineWithImplode($_whereClauses, '1');

        foreach($_tableNames as $indexNumber => $_tableName){
            if ( ! $this->utils->table_really_exists($_tableName) ) {
                break; // skip
            }
            if($do_select_splitted_table){
                $__selectFieldsStr = $_selectFieldsStr;
                $__selectFieldsStr .= sprintf(", '%s' as splitted_table", $_tableName);// for condition to partition
            }else{
                $__selectFieldsStr = $_selectFieldsStr;
            }

            $_subqueryStr = sprintf($_subqueryFormat, $__selectFieldsStr, $_tableName, $_whereClausesStr);
            array_push($uinonSubqueryList, $_subqueryStr );
        }
        if( ! empty($uinonSubqueryList) ){
            $uinonSubquery  = '';
            $uinonSubquery  .= ' /* Union Subquery BEGIN */ ';
            $uinonSubquery  .= ' ( ';
            $uinonSubquery  .= implode(' ) UNION ( ', $uinonSubqueryList);
            $uinonSubquery  .= ' ) ';
            $uinonSubquery  .= ' /* EOF Union Subquery */ ';
        }else{

        }
        return $uinonSubquery;
    }
    /**
     * combine array with $implodeStr
     *
     * @param string|array|null $_selectFields
     * @param string $defaultStr When empty() then assign this string. The string, '*' means all files in SQL.
     * @param string $implodeStr The join string for implode().
     * @return string The string in the select clause,
     * Or for where clause, assign '1' and ' AND ' to $defaultStr and $implodeStr.
     */
    public function combineWithImplode($_selectFields = null, $defaultStr = '*', $implodeStr = ', '){
        if( is_string($_selectFields) ){
            $_selectFieldsStr = $_selectFields;
        } else if( is_array($_selectFields) ){
            $_selectFieldsStr = implode($implodeStr, $_selectFields);
        }else if( empty($_selectFields) ){
            $_selectFieldsStr = $defaultStr; // for dev
        }
        return $_selectFieldsStr;
    }
    /**
     * Collect some informat of $columns, and call user function,"callbackInForeach()" in each column.
     *
     * @param array $columns The params for data_tables::get_data()
     * @param callable $callbackInForeach The user function to parses/returns the needed value.
     * @return array
     */
    public function collectColumnsOfDataTables($columns, callable $callbackInForeach){
        $collect_list = [];
        foreach ($columns as $keyNumber => $_column) {
            $_clause = '';
            $_clause = $callbackInForeach($_column);
            array_push($collect_list, $_clause);
        }
        return  $collect_list;
    } // EOF collectColumnsOfDataTables
}