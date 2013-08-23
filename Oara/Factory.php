<?php
/**
 * Implementation Class
 *
 * @author     Carlos Morillo Merino
 * @category   Oara_Factory
 * @copyright  Fubra Limited
 * @version    Release: 01.00
 *
 */
class Oara_Factory {
	/**
	 * Factory create instance function, It returns the specific Affiliate Network
	 *
	 * @param $credentials
	 * @return Oara_Network
	 * @throws Exception
	 */
	public static function createInstance($credentials) {

		$affiliate = null;
		$networkName = $credentials['networkName'];
		try {
			$networkClassName = 'Oara_Network_'.$credentials["type"].'_'.$networkName;
			$affiliate = new $networkClassName($credentials);
		} catch (Exception $e) {
			throw new Exception('No Network Available '.$networkName);
		}
		return $affiliate;

	}

}
