<?php
namespace Rhiaro;

use EasyRdf_Graph;
use Requests;

function set_default_locations(){
    $_SESSION['locations'] = array(
        array("name" => "Home", "id" => "https://apps.rhiaro.co.uk/burrow#home"), 
        array("name"=>"Work", "id" => "https://apps.rhiaro.co.uk/burrow#work"), 
        array("name"=>"Mortal Peril", "id" => "https://apps.rhiaro.co.uk/burrow#peril")
    );
    return $_SESSION['locations'];
}

function get_locations($url){
    $response = Requests::get($url, array('Accept' => 'application/ld+json'));
    $g = new EasyRdf_Graph($url);
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

?>