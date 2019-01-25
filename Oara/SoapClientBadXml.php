<?php
namespace Oara;

use SoapClient;

/**
 * UtilitSoapClientBadXmlies Class
 *
 * Like the regular SoapClient but ignores invalid UTF-8 characters in the response
 *
 * @author     Stickee Technology Limited
 * @category   Oara
 * @copyright  Fubra Limited
 * @version    Release: 01.00
 *
 */
class SoapClientBadXml extends SoapClient
{
    public function __doRequest($request, $location, $action, $version, $one_way = 0)
    {
        $response = parent::__doRequest($request, $location, $action, $version, $one_way);

        // This is the best way but doesn't work on all systems...
        $response = iconv('UTF-8','UTF-8//IGNORE', $response);

        // Try through mb_string as well...
        $oldSubstituteCharacter = mb_substitute_character();
        mb_substitute_character('none');
        $response = mb_convert_encoding($response, 'UTF-8', 'UTF-8');
        //ini_set('mbstring.substitute_character', $oldSubstituteCharacter);
        mb_substitute_character($oldSubstituteCharacter);

        // In testing, neither caught this character to manually replace it
        $response = str_replace('', '', $response);

        return $response;
    }
}
