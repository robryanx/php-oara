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
class Oara_Factory{
	/**
	 * Factory create instance function, It returns the specific Affiliate Network
	 * @param $affiliateNetwork
	 * @return Oara_Factory_Interface
	 */
    public static function createInstance($credentials) {

    	$affiliate = null;
    	try{
    		$networkName = $credentials['networkName'];
	        $networkClassName = 'Oara_Network_'.$networkName;
	    	$affiliate = new $networkClassName($credentials);
    	} catch (Exception $e){
    		throw new Exception ('No Network Aviable');
    	}
        return $affiliate;  
        
    }
	
}