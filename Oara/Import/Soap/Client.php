<?php
/**
   The goal of the Open Affiliate Report Aggregator (OARA) is to develop a set 
   of PHP classes that can download affiliate reports from a number of affiliate networks, and store the data in a common format.
   
    Copyright (C) 2014  Fubra Limited
    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU Affero General Public License as published by
    the Free Software Foundation, either version 3 of the License, or any later version.
    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU Affero General Public License for more details.
    You should have received a copy of the GNU Affero General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.
	
	Contact
	------------
	Fubra Limited <support@fubra.com> , +44 (0)1252 367 200
**/	
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
