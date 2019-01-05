<?
require 'vendor/autoload.php';
$ns = Rhiaro\ns();

$tz = Rhiaro\get_timezone_from_rdf("https://rhiaro.co.uk/tz");
date_default_timezone_set($tz);

if(isset($_POST['locations_source'])){
    $locations = Rhiaro\get_locations($_POST['locations_source']);
}else{
    $locations = Rhiaro\set_default_locations();
}

include('templates/index.php');
?>