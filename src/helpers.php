<?
namespace Rhiaro;

use EasyRdf_Graph;
use EasyRdf_Resource;
use EasyRdf_RdfNamespace;
use Requests;

function ns(){
    $ns = new \EasyRdf\RdfNamespace();
    $ns_mapping = array(
        'as' => 'https://www.w3.org/ns/activitystreams#',
        'time' => 'http://www.w3.org/2006/time#',
        'view' => 'https://terms.rhiaro.co.uk/view#'
    );
    foreach($ns_mapping as $prefix => $uri){
      $ns->set($prefix, $uri);
    }
    return $ns;
}

function get_timezone_from_rdf($url){
    global $ns;
    $response = \WpOrg\Requests\Requests::get($url, array('Accept' => 'application/ld+json'));
    $g = new \EasyRdf\Graph();
    $g->parse($response->body, 'jsonld');
    return $g->get($url, 'time:timeZone')->getValue();
}

?>