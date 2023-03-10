<?php
namespace Rhiaro;

use Requests;
use EasyRdf_Graph;
use EasyRdf_Literal;
use ML\JsonLD\JsonLD;

function set_default_locations(){
    $_SESSION['locations'] = array(
        array("name" => "Home", "id" => "https://apps.rhiaro.co.uk/burrow#home"),
        array("name"=>"Work", "id" => "https://apps.rhiaro.co.uk/burrow#work"),
        array("name"=>"Mortal Peril", "id" => "https://apps.rhiaro.co.uk/burrow#peril")
    );
    return $_SESSION['locations'];
}

function get_locations($url){
    $response = \WpOrg\Requests\Requests::get($url, array('Accept' => 'application/ld+json'));
    $g = new \EasyRdf\Graph($url);
    $g->parse($response->body, 'jsonld');

    if(isset($_SESSION['locations'])){
        unset($_SESSION['locations']);
    }

    $resources = $g->resources();
    foreach($resources as $uri => $resource){
        if($resource->isA('as:Place')){
            $name = $g->get($uri, 'as:name')->getValue();
            $color = $g->get($uri, 'view:color')->getValue();
            $_SESSION['locations'][] = array("id" => $uri, "name" => $name, "color" => $color);
        }
    }
    return $_SESSION['locations'];
}

function make_payload($form_request){
    global $ns;
    $location = $form_request['location'];
    $date_str = $form_request['year']."-".$form_request['month']."-".$form_request['day']."T".$form_request['time'].$form_request['zone'];
    $date = new \EasyRdf\Literal($date_str, null, 'xsd:dateTime');
    $g = new \EasyRdf\Graph();
    $node = $g->newBNode();
    $g->addType($node, 'as:Arrive');
    $g->addResource($node, 'as:location', $location);
    $g->addLiteral($node, 'as:published', $date);
    $jsonld = $g->serialise('jsonld');

    $context = $ns->get('as');
    $options = array('compactArrays' => true);
    $compacted = JsonLD::compact($jsonld, $context, $options);

    return JsonLD::toString($compacted, true);
}

function post_to_endpoint($form_request){
    $endpoint = $form_request['endpoint_uri'];
    $key = $form_request['endpoint_key'];
    $headers = array('Content-Type' => 'application/ld+json', 'Authorization' => $key);
    $payload = make_payload($form_request);
    $response = \WpOrg\Requests\Requests::post($endpoint, $headers, $payload);
    return $response;
}

?>