<?php
require_once dirname(__FILE__) . '/abstract_customized_definition.php';

require_once dirname(__FILE__) . '/../modules/customized_definition_module.php';

class Customized_definition_default extends Abstract_customized_definition {

    use customized_definition_module;

    function __construct() {
        parent::__construct();
    }

    /**
     * The class name
     *
     * @return string The class name string
     */
    public function getClassName(){
        return 'Customized_definition_default';
    } // EOF getClassName

    /**
     * Do something after initialized.
     *
     * @return void
     */
    public function init(){

    }// EOF init

    /**
     * Do customized process in Pre-Checker.
     * Pls reference to the props,
     * - $this->builtInPreCheckerResults['finallyResult'] bool The result of contditions under the definition.
     *
     * @return array $return The format,
     * - $return['dwStatus'] string Pls reference to Wallet_model.XXX_STATUS.
     *
     */
    public function runPreChecker($resultBuiltIn = null){

        /// Need to log results into dispatch_withdrawal_results.
        // Pls Must be Specified F.K., "wallet_account_id", "definition_id" and fill definition_results to insert.
        //
        /// wallet_account_id
        // To access $this->walletAccountDeatil[walletAccountId] for wallet_account_id.
        //
        /// definition_id
        // To access $this->definitionDetail['id'] for definition_id.
        //
        /// The inserted by contdtionId,"dispatch_withdrawal_contdtion.id" from attr.,"builtInPreCheckerResults".
        // $this->utils->debug_log('OGP-18088 Customized_definition_default.runPreChecker.builtInPreCheckerResults:', $this->builtInPreCheckerResults);
        // $resultsId = $this->builtInPreCheckerResults['contdtionId_N']['resultsId']; // for results log

        if($resultBuiltIn === true){
            // do something under met builtIn conditions.
            $return['dwStatus'] = $this->definitionDetail['eligible2dwStatus']; //  'custom_stage_1';
            $return['dbgNo'] = 49;
            $return['dbgDefinitionId'] = $this->definitionDetail['id'];

            /// Recommand do extra conditions here,and results log

        }else{
            // do something under Non-met builtIn conditions.
            $return['dwStatus'] = ''; // keep the current dwStatus.
            $return['dbgNo'] = 53;
            $return['dbgDefinitionId'] = $this->definitionDetail['id'];
        }

        $doResultsLog = false; // for RD, switch to true for view the result log in "Withdrawal List" of SBE.
        if($doResultsLog){
            $data = [];
            $data['wallet_account_id'] = $this->walletAccountDeatil['walletAccountId'];
            $data['definition_id'] = $this->definitionDetail['id'];
            $data['definition_results'] = json_encode($return); // Result col.
            $data['dispatch_order'] = $this->definitionDetail['dispatch_order'];
            $resultsId = $this->dispatch_withdrawal_results->add($data);
        }

        return $return;
    } // EOF runPreChecker

} // EOF Customized_definition_default