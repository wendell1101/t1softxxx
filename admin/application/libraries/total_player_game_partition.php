<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Total_player_game_partition {

    const DIFF_DEGREE_YEAR = 6;
    const DIFF_DEGREE_MONTH = 5;
    const DIFF_DEGREE_DAY = 4;
    const DIFF_DEGREE_HOUR = 3;
    const DIFF_DEGREE_MINUTE = 2;
    const DIFF_DEGREE_SECOND = 1;
    const DIFF_DEGREE_SAME = 0;

	function __construct()
	{
		$this->ci =& get_instance();
		// $this->ci->load->library([]);
		// $this->ci->load->model(array('thirdpartyaccount'));
        $this->utils = $this->ci->utils;
	}

    private function isEnabledPartitionTable($tablename = 'total_player_game_hour'){
        $enablePartitionTables = $this->utils->getConfig('enablePartitionTables4getPlayerTotalBetWinLoss');
        return in_array($tablename, $enablePartitionTables);
    }

    /**
     * Calculate differences between two dates
     *
     * @param \DateTime $beginDT
     * @param \DateTime $endDT
     * @param null|array $absoluted For collect the absolute diffs.
     * @return array $diff
     */
    public static function calcDiff2DateTimes( \DateTime $beginDT, \DateTime $endDT, &$absoluted = null){
        $diff = [];
        $diff['year'] = intval($beginDT->format('Y')) - intval($endDT->format('Y'));
        // n: Numeric representation of a month, without leading zeros
        $diff['month'] = $beginDT->format('n') - $endDT->format('n');
        // j: Day of the month without leading zeros, 1 to 31
        $diff['day'] = $beginDT->format('j') - $endDT->format('j');
        // G: 24-hour format of an hour without leading zeros, 0 through 23
        $diff['hour'] = $beginDT->format('G') - $endDT->format('G');
        // i: Minutes with leading zeros, 00 to 59
        $diff['minute'] = intval($beginDT->format('i')) - intval($endDT->format('i'));
        // s: Seconds with leading zeros, 00 through 59
        $diff['second'] = intval($beginDT->format('s')) - intval($endDT->format('s'));

        if( !is_null($absoluted) ){ // expected in empty array.
            foreach($diff as $datePart => $diffVal ){
                $absoluted[$datePart] = abs($diffVal);
            }
        }

        return $diff;
    } // EOF calcDiff2DateTime


    public static function parse2diffDegreeFromDateTimes(\DateTime $beginDT, \DateTime $endDT){

        $diffDegree = null;

        $calcDiff = self::calcDiff2DateTimes($beginDT, $endDT);
        /// $diffInList, The order cannot be replaced
        $diffInList = [];
        $diffInList[] = empty($calcDiff['year'])? 0: 1; // #1
        $diffInList[] = empty($calcDiff['month'])? 0: 1; // #2
        $diffInList[] = empty($calcDiff['day'])? 0: 1; // #3
        $diffInList[] = empty($calcDiff['hour'])? 0: 1; // #4
        $diffInList[] = empty($calcDiff['minute'])? 0: 1; // #5
        $diffInList[] = empty($calcDiff['second'])? 0: 1; // #6
        $diffDegreeStr = implode(':', $diffInList);
        switch($diffDegreeStr){
            case '0:0:0:0:0:0':
                // diff Degree:s
                $diffDegree = self::DIFF_DEGREE_SAME;
                break;
            case '0:0:0:0:0:1':
                $diffDegree = self::DIFF_DEGREE_SECOND;
                break;

            case '0:0:0:0:1:0':
            case '0:0:0:0:1:1':
                $diffDegree = self::DIFF_DEGREE_MINUTE;
                break;

            case '0:0:0:1:0:0':
            case '0:0:0:1:0:1':
            case '0:0:0:1:0:1':
            case '0:0:0:1:1:0':
            case '0:0:0:1:1:1':
                $diffDegree = self::DIFF_DEGREE_HOUR;
                break;

            case '0:0:1:0:0:0':
            case '0:0:1:0:0:1':
            case '0:0:1:0:1:0':
            case '0:0:1:0:1:1':
            case '0:0:1:1:0:0':
            case '0:0:1:1:0:1':
            case '0:0:1:1:1:0':
            case '0:0:1:1:1:1':
                $diffDegree = self::DIFF_DEGREE_DAY;
                break;

            case '0:1:0:0:0:0':
            case '0:1:0:0:0:1':
            case '0:1:0:0:1:0':
            case '0:1:0:0:1:1':
            case '0:1:0:1:0:0':
            case '0:1:0:1:0:1':
            case '0:1:0:1:1:0':
            case '0:1:0:1:1:1':
            case '0:1:1:0:0:0':
            case '0:1:1:0:0:1':
            case '0:1:1:0:1:0':
            case '0:1:1:0:1:1':
            case '0:1:1:1:0:0':
            case '0:1:1:1:0:1':
            case '0:1:1:1:1:0':
            case '0:1:1:1:1:1':
                $diffDegree = self::DIFF_DEGREE_MONTH;
                break;

            case '1:0:0:0:0:0':
            case '1:0:0:0:0:1':
            case '1:0:0:0:1:0':
            case '1:0:0:0:1:1':
            case '1:0:0:1:0:0':
            case '1:0:0:1:0:1':
            case '1:0:0:1:1:0':
            case '1:0:0:1:1:1':
            case '1:0:1:0:0:0':
            case '1:0:1:0:0:1':
            case '1:0:1:0:1:0':
            case '1:0:1:0:1:1':
            case '1:0:1:1:0:0':
            case '1:0:1:1:0:1':
            case '1:0:1:1:1:0':
            case '1:0:1:1:1:1':
            case '1:1:0:0:0:0':
            case '1:1:0:0:0:1':
            case '1:1:0:0:1:0':
            case '1:1:0:0:1:1':
            case '1:1:0:1:0:0':
            case '1:1:0:1:0:1':
            case '1:1:0:1:1:0':
            case '1:1:0:1:1:1':
            case '1:1:1:0:0:0':
            case '1:1:1:0:0:1':
            case '1:1:1:0:1:0':
            case '1:1:1:0:1:1':
            case '1:1:1:1:0:0':
            case '1:1:1:1:0:1':
            case '1:1:1:1:1:0':
            case '1:1:1:1:1:1':
                $diffDegree = self::DIFF_DEGREE_YEAR;
                break;

        }
        return $diffDegree;
    } // EOF parse2diffDegreeFromDateTimes

    static public function sumMultiOneRowArray($multiRow, $exceptFieldList = null){
        if( is_null($exceptFieldList) ){
            $exceptFieldList = [];
            $exceptFieldList[] = 'player_id';
        }
        $row = [];
        array_walk( $multiRow, function($_row, $indexNumber) use (&$row, $exceptFieldList) {
            if(!empty($_row)){
                foreach( $_row as $fieldName => $fieldNumeric){
                    if( empty($row[$fieldName]) ){
                        $row[$fieldName] = 0; // init
                    }
                    if( is_numeric($fieldNumeric)
                        && ! in_array($fieldName, $exceptFieldList)
                    ){
                        if( is_float($fieldNumeric) ){
                            $row[$fieldName] += floatval($fieldNumeric);
                        }else{
                            $row[$fieldName] += $fieldNumeric;
                        }
                    }else{
                        $row[$fieldName] = $fieldNumeric;
                    }
                } // EOF foreach
            }
        }); // EOF array_walk
        return $row;
    }

    public function getPlayerTotalBetWinLossInDiffDegreeMinute( $player_id // #1
                                                                , $dateTimeFrom // #2
                                                                , $dateTimeTo // #3
                                                                , $where_game_platform_id = null // #4
                                                                , $where_game_type_id = null // #5
                                                                , $db = null // #6
    ){
        $this->ci->load->model(['total_player_game_day']);
        $this->total_player_game_day = $this->ci->total_player_game_day;

        $_total_player_game_table = 'total_player_game_minute';
        $_where_date_field = 'date_minute'; // 202211161700

        // convert format to date_minute field
        $dateTimeFrom = $this->utils->formatDateMinuteForMysql(new DateTime($dateTimeFrom));
        $dateTimeTo = $this->utils->formatDateMinuteForMysql(new DateTime($dateTimeTo));
        $rows = $this->total_player_game_day->getPlayerTotalBetWinLoss( $player_id // #1
                                                , $dateTimeFrom // #2
                                                , $dateTimeTo // #3
                                                , $_total_player_game_table // #4
                                                , $_where_date_field // #5
                                                , $where_game_platform_id // #6
                                                , $where_game_type_id // #7
                                                , $db // #8
                                                , false // #9
                                            );
        return $rows;
    }// EOF getPlayerTotalBetWinLossInDiffDegreeMinute
    //
    public function getPlayerTotalBetWinLossInDiffDegreeHour( $player_id // #1
                                                            , $dateTimeFrom // #2
                                                            , $dateTimeTo // #3
                                                            , $where_game_platform_id = null // #4
                                                            , $where_game_type_id = null // #5
                                                            , $db = null // #6
    ){
        $this->ci->load->model(['total_player_game_day']);
        $this->total_player_game_day = $this->ci->total_player_game_day;

        if(! $this->isEnabledPartitionTable('total_player_game_hour') ){
            return $this->getPlayerTotalBetWinLossInDiffDegreeMinute( $player_id // #1
                                                                    , $dateTimeFrom // #2
                                                                    , $dateTimeTo // #3
                                                                    , $where_game_platform_id = null // #4
                                                                    , $where_game_type_id = null // #5
                                                                    , $db // #6
                                                                );
        } // EOF if(! $this->isEnabledPartitionTable('total_player_game_hour') ){...

        $rows = [];
        $_rowsInDiffDegree = [];
        $beginDT = new DateTime($dateTimeFrom);
        $endDT = new DateTime($dateTimeTo);
        $includeOverOne = true;
        $returnDT = true;
        $interval = 'hour';
        $datePeriod = self::genDatePeriodByInterval($beginDT, $endDT, $interval, $includeOverOne, $returnDT);
        // $this->utils->debug_log( 'OGP-33165.231.dateTimeFrom:', $dateTimeFrom
        //                             , 'dateTimeTo', $dateTimeTo
        //                             , 'datePeriod', $datePeriod
        //                         );
        if( ! empty($datePeriod) ){
            foreach($datePeriod as &$_period){
                if( ! empty($_period['is_full']) ){
                    $_total_player_game_table = 'total_player_game_hour';
                    $_where_date_field = 'date_hour';
                    $_dateTimeFrom = $this->utils->formatDateHourForMysql($_period['startDT']);
                    if( ! empty($_period['cavedEndDT']) ){
                        $_dateTimeTo = $this->utils->formatDateHourForMysql($_period['cavedEndDT']);
                    }else{
                        $_dateTimeTo = $this->utils->formatDateHourForMysql($_period['endDT']);
                    }
                    $_rowsInDiffDegree[] = $this->total_player_game_day->getPlayerTotalBetWinLoss( $player_id // #1
                                                                            , $_dateTimeFrom // #2
                                                                            , $_dateTimeTo // #3
                                                                            , $_total_player_game_table // #4
                                                                            , $_where_date_field // #5
                                                                            , $where_game_platform_id // #6
                                                                            , $where_game_type_id // #7
                                                                            , $db // #8
                                                                            , false // #9
                                                                        );
                }else{
                    $doGetPlayerTotalBetWinLossInDiffDegree = false;
                    if(in_array('begin_end', $_period['not_full_in'])){
                        // The begin and end of query, that both are not in this period.
                    } else if(in_array('begin', $_period['not_full_in'])){
                        $doGetPlayerTotalBetWinLossInDiffDegree = true;
                        $_dateTimeFrom = $beginDT->format('Y-m-d H:i:s');
                        $_dateTimeTo   = $beginDT->format('Y-m-d H:59:59');
                    } else if(in_array('end', $_period['not_full_in'])){
                        $doGetPlayerTotalBetWinLossInDiffDegree = true;
                        $_dateTimeFrom = $endDT->format('Y-m-d H:00:00');
                        $_dateTimeTo   = $endDT->format('Y-m-d H:i:s');
                    }
                    if($doGetPlayerTotalBetWinLossInDiffDegree){
                        // $this->utils->debug_log( 'OGP-33165.262.InDiffDegreeHour.getPlayerTotalBetWinLossInDiffDegreeMinute:'
                        //                         , '_dateTimeFrom:', $_dateTimeFrom
                        //                         , '_dateTimeTo:', $_dateTimeTo);
                        $_rowsInDiffDegree[] = $this->getPlayerTotalBetWinLossInDiffDegreeMinute($player_id // #1
                                                                            , $_dateTimeFrom // #2
                                                                            , $_dateTimeTo // #3
                                                                            , $where_game_platform_id = null // #4
                                                                            , $where_game_type_id = null // #5
                                                                            , $db // #6
                                                                        );

                    }// EOF if($doGetPlayerTotalBetWinLossInDiffDegree){...
                } // EOF if( ! empty($_period['is_full']) ){...
            } // EOF foreach($datePeriod as &$_period){...
        } // EOF if( ! empty($datePeriod) ){...
        $rows = Total_player_game_partition::sumMultiOneRowArray($_rowsInDiffDegree);
        return $rows;
    } // EOF getPlayerTotalBetWinLossInDiffDegreeHour
    //
    public function getPlayerTotalBetWinLossInDiffDegreeDay( $player_id // #1
                                                            , $dateTimeFrom // #2
                                                            , $dateTimeTo // #3
                                                            , $where_game_platform_id = null // #4
                                                            , $where_game_type_id = null // #5
                                                            , $db = null // #6
    ){
        $this->ci->load->model(['total_player_game_day']);
        $this->total_player_game_day = $this->ci->total_player_game_day;

        if(! $this->isEnabledPartitionTable('total_player_game_day') ){
            return $this->getPlayerTotalBetWinLossInDiffDegreeHour( $player_id // #1
                                                                    , $dateTimeFrom // #2
                                                                    , $dateTimeTo // #3
                                                                    , $where_game_platform_id = null // #4
                                                                    , $where_game_type_id = null // #5
                                                                    , $db // #6
                                                                );
        } // EOF if(! $this->isEnabledPartitionTable('total_player_game_hour') ){...

        $rows = [];
        $_rowsInDiffDegree = [];
        $beginDT = new DateTime($dateTimeFrom);
        $endDT = new DateTime($dateTimeTo);
        $includeOverOne = true;
        $returnDT = true;
        $interval = 'day';
        $datePeriod = self::genDatePeriodByInterval($beginDT, $endDT, $interval, $includeOverOne, $returnDT);
        // $this->utils->debug_log( 'OGP-33165.361.dateTimeFrom:', $dateTimeFrom
        //                             , 'dateTimeTo', $dateTimeTo
        //                             , 'datePeriod', $datePeriod
        //                         );
        if( ! empty($datePeriod) ){
            foreach($datePeriod as &$_period){
                if( ! empty($_period['is_full']) ){
                    $_total_player_game_table = 'total_player_game_day';
                    $_where_date_field = 'date';
                    $_dateTimeFrom = $this->utils->formatDateForMysql($_period['startDT']);
                    if( ! empty($_period['cavedEndDT']) ){
                        $_dateTimeTo = $this->utils->formatDateForMysql($_period['cavedEndDT']);
                    }else{
                        $_dateTimeTo = $this->utils->formatDateForMysql($_period['endDT']);
                    }
                    $_rowsInDiffDegree[] = $this->total_player_game_day->getPlayerTotalBetWinLoss( $player_id // #1
                                                                            , $_dateTimeFrom // #2
                                                                            , $_dateTimeTo // #3
                                                                            , $_total_player_game_table // #4
                                                                            , $_where_date_field // #5
                                                                            , $where_game_platform_id // #6
                                                                            , $where_game_type_id // #7
                                                                            , $db // #8
                                                                            , false // #9
                                                                        );

                }else{
                    $doGetPlayerTotalBetWinLossInDiffDegree = false;
                    if(in_array('begin_end', $_period['not_full_in'])){
                        // The begin and end of query, that both are not in this period.
                    } else if(in_array('begin', $_period['not_full_in'])){
                        $doGetPlayerTotalBetWinLossInDiffDegree = true;
                        $_dateTimeFrom = $beginDT->format('Y-m-d H:i:s');
                        $_dateTimeTo   = $beginDT->format('Y-m-d 23:59:59');
                    } else if(in_array('end', $_period['not_full_in'])){
                        $doGetPlayerTotalBetWinLossInDiffDegree = true;
                        $_dateTimeFrom = $endDT->format('Y-m-d 00:00:00');
                        $_dateTimeTo   = $endDT->format('Y-m-d H:i:s');
                    }
                    if($doGetPlayerTotalBetWinLossInDiffDegree){
                        $_rowsInDiffDegree[] = $this->getPlayerTotalBetWinLossInDiffDegreeHour($player_id // #1
                                                                            , $_dateTimeFrom // #2
                                                                            , $_dateTimeTo // #3
                                                                            , $where_game_platform_id = null // #4
                                                                            , $where_game_type_id = null // #5
                                                                            , $db // #6
                                                                        );
                    }
                } // EOF if( ! empty($_period['is_full']) ){...
            } // EOF foreach($datePeriod as &$_period){...
        } // EOF if( ! empty($datePeriod) ){...
        $rows = Total_player_game_partition::sumMultiOneRowArray($_rowsInDiffDegree);
        return $rows;
    } // EOF getPlayerTotalBetWinLossInDiffDegreeDay

/// genDatePeriodByInterval getPlayerTotalBetWinLossInDiffDegreeMonth
    public function getPlayerTotalBetWinLossInDiffDegreeMonth($player_id // #1
                                                            , $dateTimeFrom // #2
                                                            , $dateTimeTo // #3
                                                            , $where_game_platform_id = null // #4
                                                            , $where_game_type_id = null // #5
                                                            , $db // #6
    ){
        $this->ci->load->model(['total_player_game_day']);
        $this->total_player_game_day = $this->ci->total_player_game_day;

        if(! $this->isEnabledPartitionTable('total_player_game_month') ){
            return $this->getPlayerTotalBetWinLossInDiffDegreeDay( $player_id // #1
                                                                    , $dateTimeFrom // #2
                                                                    , $dateTimeTo // #3
                                                                    , $where_game_platform_id = null // #4
                                                                    , $where_game_type_id = null // #5
                                                                    , $db // #6
                                                                );
        } // EOF if(! $this->isEnabledPartitionTable('total_player_game_hour') ){...

        $rows = [];
        $_rowsInDiffDegree = [];
        $beginDT = new DateTime($dateTimeFrom);
        $endDT = new DateTime($dateTimeTo);
        $includeOverOne = true;
        $returnDT = true;
        $interval = 'month';
        $datePeriod = self::genDatePeriodByInterval($beginDT, $endDT, $interval, $includeOverOne, $returnDT);
        // $this->utils->debug_log( 'OGP-33165.621.dateTimeFrom:', $dateTimeFrom
        //                             , 'dateTimeTo', $dateTimeTo
        //                             , 'datePeriod', $datePeriod
        //                         );
        if( ! empty($datePeriod) ){
            foreach($datePeriod as &$_period){
                if( ! empty($_period['is_full']) ){
                    $_total_player_game_table = 'total_player_game_month';
                    $_where_date_field = 'month';
                    $_dateTimeFrom = $this->utils->formatYearMonthForMysql($_period['startDT']);
                    if( ! empty($_period['cavedEndDT']) ){
                        $_dateTimeTo = $this->utils->formatYearMonthForMysql($_period['cavedEndDT']);
                    }else{
                        $_dateTimeTo = $this->utils->formatYearMonthForMysql($_period['endDT']);
                    }
                    $_rowsInDiffDegree[] = $this->total_player_game_day->getPlayerTotalBetWinLoss( $player_id // #1
                                                                            , $_dateTimeFrom // #2
                                                                            , $_dateTimeTo // #3
                                                                            , $_total_player_game_table // #4
                                                                            , $_where_date_field // #5
                                                                            , $where_game_platform_id // #6
                                                                            , $where_game_type_id // #7
                                                                            , $db // #8
                                                                            , false // #9
                                                                        );
                }else{
                    $doGetPlayerTotalBetWinLossInDiffDegree = false;
                    if(in_array('begin_end', $_period['not_full_in'])){
                        // The begin and end of query, that both are not in this period.
                    } else if(in_array('begin', $_period['not_full_in'])){
                        $doGetPlayerTotalBetWinLossInDiffDegree = true;
                        $_dateTimeFrom = $beginDT->format('Y-m-d H:i:s');
                        $_dateTimeTo   = $beginDT->format('Y-m-t 23:59:59');
                    } else if(in_array('end', $_period['not_full_in'])){
                        $doGetPlayerTotalBetWinLossInDiffDegree = true;
                        $_dateTimeFrom = $endDT->format('Y-m-01 00:00:00');
                        $_dateTimeTo   = $endDT->format('Y-m-d H:i:s');
                    }
                    if($doGetPlayerTotalBetWinLossInDiffDegree){
                        // $_dateTimeFrom = $beginDT->format('Y-m-d H:i:s');
                        // $_dateTimeTo   = $beginDT->format('Y-m-t 23:59:59');
                        // $this->utils->debug_log( 'OGP-33165.511.InDiffDegreeMonth.getPlayerTotalBetWinLossInDiffDegreeDay:'
                        //                         , '_dateTimeFrom:', $_dateTimeFrom
                        //                         , '_dateTimeTo:', $_dateTimeTo);
                        $_rowsInDiffDegree[] = $this->getPlayerTotalBetWinLossInDiffDegreeDay($player_id // #1
                                                                            , $_dateTimeFrom // #2
                                                                            , $_dateTimeTo // #3
                                                                            , $where_game_platform_id = null // #4
                                                                            , $where_game_type_id = null // #5
                                                                            , $db // #6
                                                                        );
                    } // EOF if($doGetPlayerTotalBetWinLossInDiffDegree){...
                }
            }/// EOF foreach($datePeriod as &$_period){...
        } // EOF if( ! empty($datePeriod) ){...
        $rows = Total_player_game_partition::sumMultiOneRowArray($_rowsInDiffDegree);
        return $rows;

    } // EOF getPlayerTotalBetWinLossInDiffDegreeMonth
    //
    public function getPlayerTotalBetWinLossInDiffDegreeYear(   $player_id // #1
                                                                , $dateTimeFrom // #2
                                                                , $dateTimeTo // #3
                                                                , $where_game_platform_id = null // #4
                                                                , $where_game_type_id = null // #5
                                                                , $db // #6
    ){
        $this->ci->load->model(['total_player_game_day']);
        $this->total_player_game_day = $this->ci->total_player_game_day;

        if(! $this->isEnabledPartitionTable('total_player_game_year') ){
            return $this->getPlayerTotalBetWinLossInDiffDegreeMonth( $player_id // #1
                                                                    , $dateTimeFrom // #2
                                                                    , $dateTimeTo // #3
                                                                    , $where_game_platform_id = null // #4
                                                                    , $where_game_type_id = null // #5
                                                                    , $db // #6
                                                                );
        } // EOF if(! $this->isEnabledPartitionTable('total_player_game_hour') ){...

        $rows = [];
        $_rowsInDiffDegree = [];
        $beginDT = new DateTime($dateTimeFrom);
        $endDT = new DateTime($dateTimeTo);
        $includeOverOne = true;
        $returnDT = true;
        $interval = 'year';
        $datePeriod = self::genDatePeriodByInterval($beginDT, $endDT, $interval, $includeOverOne, $returnDT);
        // $this->utils->debug_log( 'OGP-33165.857.dateTimeFrom:', $dateTimeFrom
        //                             , 'dateTimeTo', $dateTimeTo
        //                             , 'datePeriod', $datePeriod
        //                         );
        if( ! empty($datePeriod) ){
            foreach($datePeriod as &$_period){
                if( ! empty($_period['is_full']) ){
                    $_total_player_game_table = 'total_player_game_year';
                    $_where_date_field = 'year';
                    $_dateTimeFrom = $this->utils->formatYearForMysql($_period['startDT']);
                    if( ! empty($_period['cavedEndDT']) ){
                        $_dateTimeTo = $this->utils->formatYearForMysql($_period['cavedEndDT']);
                    }else{
                        $_dateTimeTo = $this->utils->formatYearForMysql($_period['endDT']);
                    }
                    $_rowsInDiffDegree[] = $this->total_player_game_day->getPlayerTotalBetWinLoss( $player_id // #1
                                                                            , $_dateTimeFrom // #2
                                                                            , $_dateTimeTo // #3
                                                                            , $_total_player_game_table // #4
                                                                            , $_where_date_field // #5
                                                                            , $where_game_platform_id // #6
                                                                            , $where_game_type_id // #7
                                                                            , $db // #8
                                                                            , false // #9
                                                                        );
                }else{
                    $doGetPlayerTotalBetWinLossInDiffDegree = false;
                    if(in_array('begin_end', $_period['not_full_in'])){
                        // The begin and end of query, that both are not in this period.
                    } else if(in_array('begin', $_period['not_full_in'])){
                        $doGetPlayerTotalBetWinLossInDiffDegree = true;
                        $_dateTimeFrom = $beginDT->format('Y-m-d H:i:s');
                        $_dateTimeTo   = $_period['endDT']->format('Y-m-d H:i:s'); // $beginDT->format('Y-m-d 23:59:59');
                    } else if(in_array('end', $_period['not_full_in'])){
                        $doGetPlayerTotalBetWinLossInDiffDegree = true;
                        $_dateTimeFrom = $_period['startDT']->format('Y-m-d H:i:s');// $endDT->format('Y-m-d 00:00:00');
                        $_dateTimeTo   = $endDT->format('Y-m-d H:i:s');
                    }
                    if($doGetPlayerTotalBetWinLossInDiffDegree){
                        $_rowsInDiffDegree[] = $this->getPlayerTotalBetWinLossInDiffDegreeMonth($player_id // #1
                                                                            , $_dateTimeFrom // #2
                                                                            , $_dateTimeTo // #3
                                                                            , $where_game_platform_id = null // #4
                                                                            , $where_game_type_id = null // #5
                                                                            , $db // #6
                                                                        );
                    }
                } // EOF if( ! empty($_period['is_full']) ){...
            } // EOF foreach($datePeriod as &$_period){...
        } // EOF if( ! empty($datePeriod) ){...

        $rows = Total_player_game_partition::sumMultiOneRowArray($_rowsInDiffDegree);
        return $rows;

    }// EOF getPlayerTotalBetWinLossInDiffDegreeYear
    //
    public function getPlayerTotalBetWinLossWithPartitionTables( $player_id // #1
                                                                , $dateTimeFrom // #2
                                                                , $dateTimeTo // #3
                                                                , $where_game_platform_id = null // #4
                                                                , $where_game_type_id = null // #5
                                                                , $db = null // #6
    ){
        /// aka. $this->total_player_game_partition->parse2diffDegreeFromDateTimes().
        // thats for search keyword, ">parse2diffDegreeFromDateTimes(".
        $diffDegree = self::parse2diffDegreeFromDateTimes(new DateTime($dateTimeFrom), new DateTime($dateTimeTo));
        $rows = [];

        /// override when $dateTimeTo has tail on "00:00", re-assign to one second before.
        // ex: 2021-12-28 18:00:00 in $dateTimeTo,
        // because the total_player_game_minute inlcudes 17:59:00 to 17:59:59, thats only missed the data of 1 sec
        // and Not includes the unexpected data of 1 minute, 2021-12-28 18:00:00 to 2021-12-28 18:59:59.
        // ex: 2021-12-28 18:00:00 will override to 2021-12-28 17:59:59
        $dateTimeTo_dt = new DateTime($dateTimeTo);
        if(substr($this->utils->formatDateTimeForMysql($dateTimeTo_dt), -3) == ':00'){ /// More accurate each minute,
            $dateTimeTo_dt->modify('-1 second');
            $dateTimeTo = $this->utils->formatDateTimeForMysql($dateTimeTo_dt);
        }

        /// override when $dateTimeFrom has tail on "59:59", re-assign to one second after.
        // ex: 2021-12-28 17:59:59 in $dateTimeFrom,
        // because the total_player_game_minute inlcudes 17:59:00 to 17:59:59, thats only missed the data of 1 sec
        // and Not includes the unexpected data of 1 minute, 2021-12-28 17:59:00 to 2021-12-28 17:59:59.
        $dateTimeFrom_dt = new DateTime($dateTimeFrom);
        if(substr($this->utils->formatDateTimeForMysql($dateTimeFrom_dt), -3) == ':59'){ /// More accurate each minute,
            $dateTimeFrom_dt->modify('+1 second');
            $dateTimeFrom = $this->utils->formatDateTimeForMysql($dateTimeFrom_dt);
        }

        // $this->utils->debug_log('OGP-33165.1307.diffDegree:', $diffDegree);
        switch($diffDegree){
            case Total_player_game_partition::DIFF_DEGREE_SAME:
            case Total_player_game_partition::DIFF_DEGREE_SECOND: // because the Second Partition Table is not exists.
                $rows = [];
                break;
            case Total_player_game_partition::DIFF_DEGREE_MINUTE:
                $rows = $this->getPlayerTotalBetWinLossInDiffDegreeMinute( $player_id // #1
                                                , $dateTimeFrom // #2
                                                , $dateTimeTo // #3
                                                , $where_game_platform_id // #4
                                                , $where_game_type_id // #5
                                                , $db // #6
                                            );
                break;
            case Total_player_game_partition::DIFF_DEGREE_HOUR:
                // $total_player_game_table = 'total_player_game_minute';
                $rows = $this->getPlayerTotalBetWinLossInDiffDegreeHour( $player_id // #1
                                                , $dateTimeFrom // #2
                                                , $dateTimeTo // #3
                                                , $where_game_platform_id // #4
                                                , $where_game_type_id // #5
                                                , $db // #6
                                            );
                break;
            case Total_player_game_partition::DIFF_DEGREE_DAY:

                $rows = $this->getPlayerTotalBetWinLossInDiffDegreeDay( $player_id // #1
                                                                        , $dateTimeFrom // #2
                                                                        , $dateTimeTo // #3
                                                                        , $where_game_platform_id // #4
                                                                        , $where_game_type_id // #5
                                                                        , $db // #6
                                                                    );
                break;
            case Total_player_game_partition::DIFF_DEGREE_MONTH:
                // $total_player_game_table
                // $where_date_field
                $rows = $this->getPlayerTotalBetWinLossInDiffDegreeMonth( $player_id // #1
                                                                        , $dateTimeFrom // #2
                                                                        , $dateTimeTo // #3
                                                                        , $where_game_platform_id // #4
                                                                        , $where_game_type_id // #5
                                                                        , $db // #6
                                                                    );
                break;
            case Total_player_game_partition::DIFF_DEGREE_YEAR:
                $rows = $this->getPlayerTotalBetWinLossInDiffDegreeYear( $player_id // #1
                                                                        , $dateTimeFrom // #2
                                                                        , $dateTimeTo // #3
                                                                        , $where_game_platform_id // #4
                                                                        , $where_game_type_id // #5
                                                                        , $db // #6
                                                                    );
                break;
        }
        return $rows;
    } // EOF getPlayerTotalBetWinLossWithPartitionTables


    /**
     * Convert date string( contains year, month and day) to DateTime object
     *
     * @param string $date
     * @param null|string $_specified_format
     * @return DateTime
     */
    public static function _ymDate2dt($date, $_specified_format = null){

        $_parsed = [];
        $hasY = false;
        $hasM = false;
        $hasD = false;
        // ref. to https://regex101.com/r/pfxY6B/1
        $re = '/(?P<YYYY>\d{4})-?(?P<mm>\d{2})-?(?P<dd>\d{2})?/';
        preg_match_all($re, $date, $matches, PREG_SET_ORDER, 0);
        if( !empty($matches) ){
            foreach($matches as $matche){
                if(!empty($matche['YYYY'])){
                    $_parsed['YYYY'] = $matche['YYYY'];
                    $hasY = true;
                }
                if(!empty($matche['mm'])){
                    $_parsed['mm'] = $matche['mm'];
                    $hasM = true;
                }
                if(!empty($matche['dd'])){
                    $_parsed['dd'] = $matche['dd'];
                    $hasD = true;
                }
            }
        }
        $dash = '-';
        $hasDash = false;
        if( strpos($date, $dash) !== false){ // contains "-", ex: "Y-m", "Y-m-d"
            $hasDash = true;
        }
        $caseStr = '';
        $caseStr .= ($hasDash)? '1': '0';
        $caseStr .= ($hasY)? '1': '0';
        $caseStr .= ($hasM)? '1': '0';
        $caseStr .= ($hasD)? '1': '0';
        switch($caseStr){
            case '0000';
            $_format4input = '';
            $_date = $date;
            break;
            case '0001';
            $_format4input = 'd';
            $_date = $_parsed['dd'];
            break;
            case '0010';
            $_format4input = 'm';
            $_date = $_parsed['mm'];
            break;
            case '0011';
            $_format4input = 'md';
            $_date = $_parsed['mm']. $_parsed['dd'];
            break;
            case '0100';
            $_format4input = 'Y';
            $_date = $_parsed['YYYY'];
            break;
            case '0101';
            $_format4input = 'Yd';
            $_date = $_parsed['YYYY']. $_parsed['dd'];
            break;
            case '0110';
            $_format4input = 'Ym';
            $_date = $_parsed['YYYY']. $_parsed['mm'];
            break;
            case '0111';
            $_format4input = 'Ymd';
            $_date = $_parsed['YYYY']. $_parsed['mm']. $_parsed['dd'];
            break;

            case '1000';
            $_format4input = '';
            $_date = $date;
            break;
            case '1001';
            $_format4input = 'd';
            $_date = $_parsed['dd'];
            break;
            case '1010';
            $_format4input = 'm';
            $_date = $_parsed['mm'];
            break;
            case '1011';
            $_format4input = 'm-d';
            $_date = $_parsed['mm']. $dash. $_parsed['dd'];
            break;
            case '1100';
            $_format4input = 'Y';
            $_date = $_parsed['YYYY'];
            break;
            case '1101';
            $_format4input = 'Y-d';
            $_date = $_parsed['YYYY']. $dash. $_parsed['dd'];
            break;
            case '1110';
            $_format4input = 'Y-m';
            $_date = $_parsed['YYYY']. $dash. $_parsed['mm'];
            break;
            case '1111';
            $_format4input = 'Y-m-d';
            $_date = $_parsed['YYYY']. $dash. $_parsed['mm']. $dash. $_parsed['dd'];
            break;
        }
        if( empty($_specified_format) ){
            $_dt = DateTime::createFromFormat($_format4input, $_date );
        }else{
            $_dt = DateTime::createFromFormat($_specified_format, $date);
        }
        return $_dt;
    }
    /**
     * For the date column, get the range to rows.
     *
     * @param string $first
     * @param string $last
     * @param string $step
     * @param string $format
     * @param boolean $do_array_pop
     * @return void
     */
    public function _getDateRangeRows($first, $last, $step = '+1 month', $format = 'Ym', $do_array_pop =false){
        $dateRangeRows = $this->utils->dateTimeRangePeriods($first, $last, $step, $format);


        $_count = count($dateRangeRows);
        if($do_array_pop && $_count > 1){
            array_pop($dateRangeRows);
        }
        //
        $rangeRows = []; // for collect
        array_walk($dateRangeRows, function ($row, $key) use ($_count, &$rangeRows) {
            if($key == ($_count-1) ){ // the latest one
                $rangeRows[] = $row['from'];
            	$rangeRows[] = $row['to'];
            }else{
                $rangeRows[$key] = $row['from'];
            }
        });
        return $rangeRows;
    }

    public static function compareDateTimeWithTimestamp($dt1, $dt2) {
		return $dt1->getTimestamp() - $dt2->getTimestamp();
	}

    public static function genDatePeriodByInterval($startDT, $endDT, $interval = 'minute', $includeOverOne = false, $returnDT = false, $collapse_full = true){
        $rePeriod = [];
        $start = clone $startDT; // new DateTime('2013-01-30 12:00:00');
        $end = clone $endDT; // new DateTime('2013-02-02 21:00:00');

        switch(strtolower($interval) ){
            case 'minute':
                $inc = DateInterval::createFromDateString('+1 Minute');
                $end->modify('+1 Minute');
                $_PeriodFormat = 'YmdHi';
                $_PeriodFormat4start = 'Y-m-d H:i:00';
                $_PeriodFormat4end = 'Y-m-d H:i:59';
            break;
            case 'hour':
                $inc = DateInterval::createFromDateString('+1 hour');
                $end->modify('+1 hour');
                $_PeriodFormat = 'YmdH';
                $_PeriodFormat4start = 'Y-m-d H:00:00';
                $_PeriodFormat4end = 'Y-m-d H:59:59';
            break;
            case 'day':
                $inc = DateInterval::createFromDateString('next day');
                $end->modify('+1 day');
                $_PeriodFormat = 'Ymd';
                $_PeriodFormat4start = 'Y-m-d 00:00:00';
                $_PeriodFormat4end = 'Y-m-d 23:59:59';
            break;
            case 'month':
                $inc = DateInterval::createFromDateString('first day of next month');
                $end->modify('+1 month');
                $_PeriodFormat = 'Ym';
                $_PeriodFormat4start = 'Y-m-01 00:00:00';
                $_PeriodFormat4end = 'Y-m-t 23:59:59';
                // $includeOverOne, work on 2013-02-28 in $endDT
            break;
            case 'year':
                $inc = DateInterval::createFromDateString('Next Year');
                $end->modify('+1 year');
                $_PeriodFormat = 'Y';
                $_PeriodFormat4start = 'Y-01-01 00:00:00';

                // $_PeriodFormat4end = 'Y-12-t 23:59:59';
                $_PeriodFormat4end = function($d){
                    $_dt = clone $d;
                    $_dt->modify('last day of December this year');
                    return $_dt->format('Y-m-t 23:59:59');
                };
            break;
        }


        $p = new DatePeriod($start,$inc,$end);

        foreach ($p as $d){
            $_Period = [];

            $_Period[$_PeriodFormat] = $d->format($_PeriodFormat);
            $_Period['dt'] = $d;
            $_Period['startDT'] = new DateTime( $d->format($_PeriodFormat4start) );
            if( is_callable($_PeriodFormat4end) ){
                $_Period['endDT'] = new DateTime( $_PeriodFormat4end($d) );
            }else if( is_string($_PeriodFormat4end) ){
                $_Period['endDT'] = new DateTime( $d->format($_PeriodFormat4end) );
            }else{
                $_Period['endDT'] = new DateTime( $d->format($_PeriodFormat4end) );
            }

            /// for debug
            // $_Period['start'] = $d->format($_PeriodFormat4start);
            // $_Period['end'] = $d->format($_PeriodFormat4end);

            // $_Period['Y-m-d H:i:s'] = $d->format('Y-m-d  H:i:s');
            $tailDiff = $endDT->getTimestamp() - $d->getTimestamp();
            if($tailDiff>=0 || $includeOverOne){
                $rePeriod[] = $_Period;
            }
        }

        foreach($rePeriod as &$_period){
            /// for debug,
            // $_period['diffDegree'] = self::parse2diffDegreeFromDateTimes($_period['startDT'], $_period['endDT']);

            $diff4begin2startPeriod = self::compareDateTimeWithTimestamp($startDT, $_period['startDT']);
            $diff4begin2endPeriod = self::compareDateTimeWithTimestamp($startDT, $_period['endDT']);
            // Pt:positive
            // $isPt4begin2sPeriod = !!( $diff4begin2startPeriod > 0 );
            // $isPt4begin2ePeriod = !!( $diff4begin2endPeriod > 0 );

            $diff4end2startPeriod = self::compareDateTimeWithTimestamp($endDT, $_period['startDT']);
            $diff4end2endPeriod = self::compareDateTimeWithTimestamp($endDT, $_period['endDT']);
            // $isPt4end2sPeriod = !!( $diff4end2startPeriod > 0 );
            // $isPt4end2ePeriod = !!( $diff4end2endPeriod > 0 );

            if( ($diff4begin2startPeriod < 0 && $diff4begin2endPeriod < 0 )
                || ($diff4begin2startPeriod == 0 && $diff4begin2endPeriod < 0)
                /// begin eq. to start of Period
                // 2024-01-01 00:00:00 ~ 2024-04-15 23:59:59
            ){
                $isPt4begin = false;
            }else{
                $isPt4begin = true;
            }
            if( ($diff4end2startPeriod > 0 && $diff4end2endPeriod > 0)
                || ($diff4end2startPeriod > 0 && $diff4end2endPeriod == 0)
                /// end eq. to end of Period
                // 2024-01-01 00:00:00 ~ 2024-04-30 23:59:59
            ){
                $isPt4end = true;
            }else{
                $isPt4end = false;
            }
            $_period['is_full'] = false;
            // $_period['diff4begin2startPeriod'] = $diff4begin2startPeriod;
            // $_period['diff4begin2endPeriod'] = $diff4begin2endPeriod;
            // $_period['diff4end2startPeriod'] = $diff4end2startPeriod;
            // $_period['diff4end2endPeriod'] = $diff4end2endPeriod;

            if($isPt4begin == false && $isPt4end == true){
                $_period['is_full'] = true;
            }else{
                $_period['not_full_in'] = [];
                if($diff4begin2startPeriod > 0 ){
                    $_period['not_full_in'][] = 'begin';
                }
                if($diff4end2endPeriod < 0){
                    $_period['not_full_in'][] = 'end';
                }
                if( $diff4begin2startPeriod < 0
                    && $diff4begin2endPeriod < 0
                    && $diff4end2startPeriod < 0
                    && $diff4end2endPeriod < 0
                ){ // over last
                    $_period['not_full_in'][] = 'begin_end';
                }
            }

            if(!$returnDT){
                unset($_period['dt']);
                unset($_period['startDT']);
                unset($_period['endDT']);
            }

        } // EOF foreach($rePeriod as &$_period){...

        // $CI = &get_instance();
        // $CI->utils->debug_log( 'OGP-33165.891.rePeriod:', $rePeriod );

        if($collapse_full){
            $cavedFullPeriod = self::collapseFullWithPeriod($rePeriod);
            // re-assign to $rePeriod
            unset($rePeriod);
            $rePeriod = $cavedFullPeriod;
        } // EOF if($collapse_full){...

        return $rePeriod;
    } // EOF genDatePeriodByInterval

    public static function collapseFullWithPeriod($rePeriod){
        $_firstFullPeriod = null;
        $_lastFullPeriod = null;
        foreach($rePeriod as $indexNum => $aPeriod){
            if( $aPeriod['is_full'] ){
                if( is_null($_firstFullPeriod) ){
                    $_firstFullPeriod = $aPeriod;
                    $_firstFullPeriod['indexNum4cave'] = $indexNum;
                }
                $_lastFullPeriod = $aPeriod;
                $_lastFullPeriod['indexNum4cave'] = $indexNum;
            } // EOF if( $aPeriod['is_full'] ){...
        } // EOF foreach($rePeriod as $indexNum => $aPeriod){...

        // $CI = &get_instance();
        // $CI->utils->debug_log( 'OGP-33165.1386.startDT:', $startDT
        //                                 , 'endDT:', $endDT
        //                                 , 'interval:', $interval
        //                                 , 'rePeriod:', $rePeriod
        //                              );
        // $CI->utils->debug_log( 'OGP-33165.1392.startDT:', $startDT
        //                              , 'endDT:', $endDT
        //                              , 'interval:', $interval
        //                              , '_firstFullPeriod:', $_firstFullPeriod
        //                              , '_lastFullPeriod:', $_lastFullPeriod
        //                           );
        $collapsedFullPeriod = [];
        array_walk($rePeriod, function($aPeriod, $indexNum) use ( $_firstFullPeriod, $_lastFullPeriod, &$collapsedFullPeriod) {
            if($aPeriod['is_full']){
                if($_firstFullPeriod['indexNum4cave'] == $indexNum){
                    $collapsedFullPeriod[] = $aPeriod;
                // }else if($_lastFullPeriod['indexNum4cave'] == $indexNum){
                }else{
                    // caved Period
                    if( ! isset($collapsedFullPeriod[$_firstFullPeriod['indexNum4cave']]['caved']) ){
                        // init
                        $collapsedFullPeriod[$_firstFullPeriod['indexNum4cave']]['caved'] = [];
                    }
                    $collapsedFullPeriod[$_firstFullPeriod['indexNum4cave']]['caved'][] = $aPeriod;
                }
                $collapsedFullPeriod[$_firstFullPeriod['indexNum4cave']]['cavedEndDT'] = $aPeriod['endDT'];
            }else{
                $collapsedFullPeriod[] = $aPeriod;
            } // EOF if($aPeriod['is_full']){...
        }); // EOF array_walk($rePeriod, ...
        return $collapsedFullPeriod;
    }// EOF collapseFullWithPeriod

}