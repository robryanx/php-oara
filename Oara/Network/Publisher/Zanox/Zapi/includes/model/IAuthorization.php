<?php

/**
 * IAuthorization Interface
 *
 * The IAuthorization Interface defines the methods that need to be implemented
 * in order to support the required hash-based signing of messages.
 *
 * Supported Version: PHP >= 5.0
 *
 * @author      Thomas Nicolai (thomas.nicolai@sociomantic.com)
 * @author      Lars Kirchhoff (lars.kirchhoff@sociomantic.com)
 *
 * @see         http://wiki.zanox.com/en/Web_Services
 * @see         http://apps.zanox.com
 *
 * @category    Web Services
 * @package     ApiClient
 * @version     2009-09-01
 * @copyright   Copyright (c) 2007-2011 zanox.de AG
 */
interface IAuthorization
{

    /**
     * Set connectId
     *
     * @access     public
     *
     * @param      string      $timestamp      time stamp
     *
     * @return     void
     */
    public function setTimestamp( $timestamp );



    /**
     * Returns the current REST timestamp.
     *
     * If there hasn't already been set a datetime we create one automatically.
     * As a format the HTTP Header protocol RFC format is taken.
     *
     * @see        see HTTP RFC for the datetime format
     * @access     public
     *
     * @return     string                      message timestamp
     */
    public function getTimestamp();



    /**
     * Set connectId
     *
     * @access     public
     *
     * @param      string      $connectId      zanox connectId
     *
     * @return     void
     */
    public function setConnectId( $connectId );



    /**
     * Returns connectId
     *
     * @access     public
     *
     * @return     string                      zanox connect id
     */
    public function getConnectId();



    /**
     * Sets the public key.
     *
     * @access     public
     *
     * @param      string      $publicKey      public key
     *
     * @return     void
     */
    public function setPublicKey( $publicKey );



    /**
     * Returns the public key
     *
     * @access     public
     *
     * @return     string                      zanox public key
     */
    public function getPublicKey();



    /**
     * Set SecretKey
     *
     * @access     public
     *
     * @param      string      $secretKey      zanox secret key
     *
     * @return     void
     */
    public function setSecretKey( $secretKey );



    /**
     * Sets the API version to use.
     *
     * @param      string      $version        API version
     *
     * @return     void
     *
     * @access     public
     */
    public function setVersion( $version );



    /**
     * Enables the API authentication.
     *
     * Authentication is only required and therefore enabled for some privacy
     * related methods like accessing your profile or reports.
     *
     * @access     private
     *
     * @return     void
     */
    public function setSecureApiCall( $status = false );



    /**
     * Returns message security status.
     *
     * Method returns true if message needs to signed with crypted hmac
     * string and nonce. Otherwise false is returned.
     *
     * @access     private
     *
     * @return     bool                        true if secure message
     */
    public function isSecureApiCall();


    
    /**
     * Returns the crypted hash signature for a api message.
     *
     * Builds the signed string consisting of the rest action verb, the uri used
     * and the timestamp of the message. Be aware of the 15 minutes timeframe
     * when setting the time manually.
     *
     * @access     private
     *
     * @param      string      $service        service name or restful action
     * @param      string      $method         method or uri
     * @param      string      $nonce          nonce of request
     *
     * @return     string                      encoded string
     */
    public function getSignature( $service, $method, $nonce );


    
    /**
     * Returns nonce.
     *
     * @see         http://en.wikipedia.org/wiki/Cryptographic_nonce
     *
     * @access      public
     *
     * @return      string                      nonce
     */
    public function getNonce();

}

?>