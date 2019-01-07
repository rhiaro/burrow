<?
session_start();
require 'vendor/autoload.php';
$ns = Rhiaro\ns();

$tz = Rhiaro\get_timezone_from_rdf("https://rhiaro.co.uk/tz");
date_default_timezone_set($tz);

if(isset($_POST['locations_source'])){
    $locations = Rhiaro\get_locations($_POST['locations_source']);
}elseif(isset($_SESSION['locations'])){
    $locations = $_SESSION['locations'];
}else{
    $locations = Rhiaro\set_default_locations();
}

if(isset($_POST['location'])){
    $endpoint = $_POST['endpoint_uri'];
    $result = Rhiaro\post_to_endpoint($_POST);
}

include('templates/index.php');
?>