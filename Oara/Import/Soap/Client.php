<?php
/**
 * Oara_Import_Soap_Client
 *
 * @category   Oara
 * @package    Oara_import_Soap
 */
class Oara_Import_Soap_Client extends Zend_Soap_Client {
	/**
	 * Perform a SOAP call
	 *
	 * @param string $name
	 * @param array  $arguments
	 * @return mixed
	 */
	public function __call($name, $arguments) {
		$soapClient = $this->getSoapClient();

		$this->_lastMethod = $name;

		$soapHeaders = array_merge($this->_permanentSoapInputHeaders, $this->_soapInputHeaders);
		
		$error = true;
		$try = 0;
		while ($error) {
			try {
				$result = $soapClient->__soapCall($name, $this->_preProcessArguments($arguments), null, (count($soapHeaders) > 0) ? $soapHeaders : null, $this->_soapOutputHeaders);
				$error = false;
			} catch (SoapFault $e) {
				//echo "Calling the soap again\n\n";
				$try++;
				if ($try == 5) {
					throw new Exception("Soap call error Oara_Import_Soap_Client\n".$e->getMessage());
				}
			}

		}

		// Reset non-permanent input headers
		$this->_soapInputHeaders = array();

		return $this->_preProcessResult($result);
	}

}
