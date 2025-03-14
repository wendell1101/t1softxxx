<?php

require_once dirname(__FILE__) . '/TestController.php';

class Testing_lepay extends TestController {
	/**
	 * Generate a signature to postman test
	 *
	 * @param int $systemId
	 * @return void
	 */
	public function signature($systemId) {
		list($loaded, $managerName) = $this->utils->loadExternalSystemLib($systemId);

		$params = $this->getInputGetAndPost();

		if(!$loaded){
			return;
		}
		$this->$managerName->getSignature($params);

		return $this->returnJsonResult($params);
	}
}

/// END OF FILE//////