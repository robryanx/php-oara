<?php

/**
 * IParser Interface
 *
 * The IParser interface defines the required methods that should be implemented
 * by an api parser. The parser itself should be able to transform various
 * input formats into one common output format e.g. xml into object or array.
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
interface IParser
{

    /**
     * Serializes array to json string.
     *
     * @param      string      $rootName       name of root node
     * @param      array       $itemArray      properties of item
     * @param      array       $attributes     root node attributes
     *
     * @access     public
     *
     * @return     string                      json string or false
     */
    public function serializeJson( $rootName, $itemArray, $attributes );



    /**
     * Unserialize json string to array.
     *
     * @param      string      $jsonString     json string
     *
     * @access     public
     *
     * @return     array                       array or false
     */
    public function unserializeJson( $jsonString );



    /**
     * Serializes array to XML.
     *
     * @param      string      $rootName       name of sub-root node (e.g. adspaceItem)
     * @param      array       $itemArray      properties of item
     * @param      array       $attributes     root node attributes
     *
     * @access     private
     *
     * @return     string                      xml or false
     */
    public function serializeXml( $rootName, $itemArray, $attributes );



    /**
     * Unserialize xml to array.
     *
     * @param      string      $xml            xml data to serialize
     *
     * @access     private
     *
     * @return     array                       array or false
     */
    public function unserializeXml( $xml );

}

?>