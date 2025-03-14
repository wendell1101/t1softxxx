<?php
trait customized_definition_module {

    /**
     * Convert symbol Int to symbol flag.
     * symbol:
     * - 1:greaterThanOrEqualTo, ≥
     * - 2:greaterThan, >
     * - 0:equalTo, =
     * - -1:lessThanOrEqualTo, ≤
     * - -2:lessThan, <
     *
     * @param [type] $symbolInt
     * @return void
     */
    function symbolIntToMathSymbol($symbolInt){
        $mathSymbol = null;

        switch( $symbolInt){
            case "2": // 2:greaterThan, >
                $mathSymbol = '>';
            break;
            case "1": // 1:greaterThanOrEqualTo, ≥
                $mathSymbol = '>=';
            break;
            case "0": // 0:equalTo, =
                $mathSymbol = '=';
            break;
            case "-1": // -1:lessThanOrEqualTo, ≤
                $mathSymbol = '<=';
            break;
            case "-2": // -2:lessThan, <
                $mathSymbol = '<';
            break;

        }
        return $mathSymbol;
    } // EOF symbolIntToMathSymbol

    /**
     * Process the compare condition, that's like "1 < 12, (2020-12-22 12:43:23 ~ 2020-12-23 01:23:45)".
     *
     * @param integer $count The counter after calc.
     * @param integer $symbol The compare symbol, pls reference to customized_definition_module::symbolIntToMathSymbol().
     * @param integer $limit The limit amount of the compare condition.
     * @param boolean $isEnable If false than ignore the condition. and $results['result'] = null in the return.
     * @param string $from_datetime For formula format, the begin datetime of calc.
     * @param string $to_datetime For formula format, the end datetime of calc.
     * @return array $results If process the compare than return the following,
     * - $results['count'] integer|float The count after calc.
     * - $results['formula'] string The formula after apply the format, ex: "1 < 12, (2020-12-22 12:43:23 ~ 2020-12-23 01:23:45)".
     * - $results['result'] boolean|null IF NULL means ignore the condition. If TRUE mean met the condition else FALSE.
     */
    function processCompareCondition($count, $symbol, $limit, $isEnable, $from_datetime = null, $to_datetime = null){
        $results = null;
        if($isEnable){
            $formulaFormat = '%s %s %s, ( %s ~ %s )'; // 5 params, ex: 1 < 12, (2020-12-22 12:43:23 ~ 2020-12-23 01:23:45)
            if( empty($from_datetime) && empty($to_datetime) ){
                $formulaFormat = '%s %s %s';// 3 params, ex: 1 < 12
            }
            // $count = $this->dispatch_withdrawal_definition->getTotalDepositCountFromLastWithdrawDatetime($this->playerId, $from_datetime, $hasPlayerPromoIdCondition);
            // $limit = $contdtionDetail['totalDepositCount_limit'];
            // $symbol = $this->symbolIntToMathSymbol($contdtionDetail['totalDepositCount_symbol']);
            $results['count'] = $count;
            $results['formula'] = sprintf($formulaFormat, $count, $symbol, $limit, $from_datetime, $to_datetime);
            $results['result'] = $this->utils->compareResultFloat($count, $symbol, $limit);
        }else{
            $results['formula'] = 'isEnable is false, ignore.';
            $results['result'] = null; // ignore
        }
        $results['isEnable'] = $isEnable;
        return $results;
    }// EOF processCompareCondition
}