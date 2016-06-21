<?php

require_once DIR . '/includes/model/IParser.php';
require_once DIR . '/includes/ApiError.php';

/**
 * Xml & Json Parser
 *
 * Xml Parser requires PHP >= 5.0
 * Json Parser requires PHP >= 5.2.0
 *
 * @author      Thomas Nicolai (thomas.nicolai@sociomantic.com)
 * @author      Lars Kirchhoff (lars.kirchhoff@sociomantic.com)
 *
 * @see         http://wiki.zanox.com/en/Web_Services
 * @see         http://apps.zanox.com
 *
 * @package     ApiClient
 * @version     2009-09-01
 * @copyright   Copyright (c) 2007-2011 zanox.de AG
 */
class Parser implements IParser
{

    /**
     * Contructor
     *
     * @access     public
     *
     * @return     void
     */
    function __construct() {}



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
    public function serializeJson( $rootName, $itemArray, $attributes )
    {
        if ( !function_exists("json_encode") )
        {
            throw new ApiClientException("Json parser requires PHP >= 5.2.0");
        }

        $json[$rootName] = $itemArray;

        foreach ($attributes as $name => $value )
        {
            $json['@' . $name] = $value;
        }

        $result = json_encode($json);

        $result = preg_replace("/#text/", "\$", $result);

        if ( $result )
        {
            return $result;
        }

        return false;
    }



    /**
     * Unserialize json string to array.
     *
     * @param      string      $json           json string
     *
     * @access     private
     *
     * @return     array                       array or false
     */
    public function unserializeJson( $json )
    {
        if ( !function_exists("json_decode") )
        {
            throw new ApiClientException("Json parser requires PHP >= 5.2.0");
        }

        $result = json_decode($json, true);

        if ( $result )
        {
            return $result;
        }

        return false;
    }



    /**
     * Serializes array to XML.
     *
     * @param      string      $rootName       name of sub-root node
     * @param      array       $itemArray      properties of item
     * @param      array       $attributes     root node attributes
     *
     * @access     private
     *
     * @return     string                      xml or false
     */
    public function serializeXml( $rootName, $itemArray, $attributes )
    {
        $dom = new DOMDocument('1.0', 'utf-8');

        $root = $dom->appendChild($dom->createElement($rootName, false));

        foreach ( $attributes as $name => $value )
        {
           $root->setAttribute($name, $value);
        }

        $this->createXmlNode($dom, $root, $rootName, $itemArray);

        return $dom->saveXML();
    }



    /**
     * Unserialize XML to array.
     *
     * @param      string      $xml            xml data to serialize
     *
     * @access     private
     *
     * @return     array                       array or false
     */
    public function unserializeXml( $xml )
    {
        $result = $this->parse_xml($xml);

        if (isset($result['response']))
        {
            return $result['response'];
        }

        return false;
    }



    /**
     * Transforms XML into Array Structure.
     *
     * @param      dom object     $xml           xml data to serialize
     *
     * @access     private
     *
     * @return     array                         array or false
     */
    private function parse_xml( $xml )
    {
        $p = xml_parser_create('UTF-8');

        xml_parser_set_option($p, XML_OPTION_SKIP_WHITE, 1);

        if ( xml_parse_into_struct($p, $xml, $values, $index) )
        {
            xml_parser_free($p);

            $level = 0;
            $return = array();

            foreach ($values as $key => $value)
            {
                if ($value['type'] == 'open')
                {
                    $level++;

                    $open[$level] = strtolower($value['tag']);
                    if (isset($value['attributes']))
                    {
                        foreach ( $value['attributes'] as $k => $v)
                        {
                            $return[$level]['_attr'][strtolower($k)] = $v;
                        }
                    }

                }
                else if ($value['type'] == 'close')
                {
                    $tmp  = $return[$level];
                    $item = $open[$level];

                    $level--;

                    if ( count($index[$value['tag']]) > 2 )
                    {
                        $return[$level][$item][] = $tmp;
                    }
                    else
                    {
                        $return[$level][$item] = $tmp;
                    }

                    foreach (array_keys($tmp) as $key)
                    {
                        unset($return[ $level + 1 ][$key]);
                    }
                }
                else
                {
                    $tag = strtolower($value['tag']);

                    if ( isset($value['value']) )
                    {
                        if ( !isset($return[$level][$tag]) )
                        {
                            $return[$level][$tag] = $value['value'];
                        }
                        else
                        {
                            if ( !is_array($return[$level][$tag]) )
                            {
                                $tmp = $return[$level][$tag];

                                $return[$level][$tag] = array();
                                $return[$level][$tag][] = $tmp;
                            }

                            $return[$level][$tag][] = $value['value'];
                        }
                    }
                    else
                    {
                        $return[$level][$tag][] = false;
                    }
                }

            }

            return $return[0];
        }

        return false;
    }



    /**
     * Recursively creates Xml Nodes.
     *
     * @param      object      $dom            dom object
     * @param      object      $root           root node object
     * @param      string      $name           root node name
     * @param      array       $array          array elements
     *
     * @access     private
     *
     * @return     void
     */
    private function createXmlNode ( $dom, $root, $name, $array )
    {
        foreach( $array as $element => $value )
        {
            $numeric = false;

            if ( is_array($value) )
            {
                foreach ( $value as $k => $v )
                {
                    if ( is_numeric($k) )
                    {
                        $numeric = true;
                    }
                }

                if ( $numeric )
                {
                    $this->createXmlNode($dom, $root, $element, $value);
                }
                else
                {
                    $element = is_numeric( $element ) ? $name : $element;

                    $child = $dom->createElement($element, (is_array($value) ? null : $value));
                    $root->appendChild($child);

                    $this->createXmlNode($dom, $child, $element, $value);
                }
            }
            else
            {
                if (preg_match("/^#text/", $element))
                {
                    $root->nodeValue = $value;
                }
                else if (preg_match("/^@/", $element))
                {
                    $root->setAttribute(str_replace("@", "", $element), $value);
                }
                else
                {
                    $element = is_numeric( $element ) ? $name : $element;

                    $child = $dom->createElement($element, (is_array($value) ? null : $value));
                    $root->appendChild($child);
                }
            }
        }
    }


}

?>