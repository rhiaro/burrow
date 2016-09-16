<?
session_start();
date_default_timezone_set(file_get_contents("https://rhiaro.co.uk/tz"));
if(isset($_GET['logout'])){ session_unset(); session_destroy(); header("Location: /burrow"); }
if(isset($_GET['reset'])) { $_SESSION['locations'] = set_default_locations(); header("Location: /burrow"); }

include "link-rel-parser.php";

$base = "https://apps.rhiaro.co.uk/burrow";
if(isset($_GET['code'])){
  $auth = auth($_GET['code'], $_GET['state']);
  if($auth !== true){ $errors = $auth; }
  else{
    $response = get_access_token($_GET['code'], $_GET['state']);
    if($response !== true){ $errors = $auth; }
    else {
      header("Location: ".$_GET['state']);
    }
  }
}

// VIP cache
$vips = array("https://rhiaro.co.uk", "https://rhiaro.co.uk/", "http://tigo.rhiaro.co.uk/");
if(isset($_SESSION['me']) && in_array($_SESSION['me'], $vips) && !isset($_SESSION['locations'])){
  $locations = get_locations("https://rhiaro.co.uk/locations");
}elseif(!isset($_SESSION['locations'])){
  $locations = set_default_locations();
}else{
  $locations = $_SESSION['locations'];
}

if(isset($_POST['locations_source'])){
  $fetch = get_locations($_POST['locations_source']);
  if(!$fetch){
    $errors["Problem fetching locations"] = "The locations url needs to return a single page AS2 Collection as JSON.";
  }else{
    $locations = $fetch;
  }
}
if(isset($locations["id"])){
  $locations_source = $locations["id"];
}

function dump_headers($curl, $header_line ) {
  echo "<br>YEAH: ".$header_line; // or do whatever
  return strlen($header_line);
}

function auth($code, $state, $client_id="https://apps.rhiaro.co.uk/burrow"){
  
  $params = "code=".$code."&redirect_uri=".urlencode($state)."&state=".urlencode($state)."&client_id=".$client_id;
  $ch = curl_init("https://indieauth.com/auth");
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/x-www-form-urlencoded", "Accept: application/json"));
  curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
  //curl_setopt($ch, CURLOPT_HEADERFUNCTION, "dump_headers");
  $response = curl_exec($ch);
  $response = json_decode($response, true);
  $_SESSION['me'] = $response['me'];
  $info = curl_getinfo($ch);
  curl_close($ch);
  
  if(isset($response) && ($response === false || $info['http_code'] != 200)){
    $errors["Login error"] = $info['http_code'];
    if(curl_error($ch)){
      $errors["curl error"] = curl_error($ch);
    }
    return $errors;
  }else{
    return true;
  }
}

function get_access_token($code, $state, $client_id="https://apps.rhiaro.co.uk/burrow"){
  
  $params = "me={$_SESSION['me']}&code=$code&redirect_uri=".urlencode($state)."&state=".urlencode($state)."&client_id=$client_id";
  $token_ep = discover_endpoint($_SESSION['me'], "token_endpoint");
  $ch = curl_init($token_ep);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/x-www-form-urlencoded"));
  curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
  $response = Array();
  parse_str(curl_exec($ch), $response);
  $info = curl_getinfo($ch);
  curl_close($ch);
  
  if(isset($response) && ($response === false || $info['http_code'] != 200)){
    $errors["Login error"] = $info['http_code'];
    if(curl_error($ch)){
      $errors["curl error"] = curl_error($ch);
    }
    return $errors;
  }else{
    $_SESSION['access_token'] = $response['access_token'];
    return true;
  }
  
}

function discover_endpoint($url, $rel="micropub"){
  if(isset($_SESSION[$rel])){
    return $_SESSION[$rel];
  }else{
    $res = head_http_rels($url);
    $rels = $res['rels'];
    if(!isset($rels[$rel][0])){
      $parsed = json_decode(file_get_contents("https://pin13.net/mf2/?url=".$url), true);
      if(isset($parsed['rels'])){ $rels = $parsed['rels']; }
    }
    if(!isset($rels[$rel][0])){
      // TODO: Try in body
      return "Not found";
    }
    $_SESSION[$rel] = $rels[$rel][0];
    return $rels[$rel][0];
  }
}

function as2(){
  return array(
      "@context" => "https://www.w3.org/ns/activitystreams#"
    );
}

function get_locations($source=null){
  if($source){
    $ch = curl_init($source);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array("Accept: application/activity+json"));
    $response = curl_exec($ch);

    $_SESSION['locations'] = json_decode($response, true);
    if(is_array($_SESSION['locations']) && !empty($_SESSION['locations'])){
      
      foreach($_SESSION['locations']['items'] as $i => $location){
        if(!is_array($location)){
          $_SESSION['locations']['items'][$i] = array("id" => $location);
        }
        if(!isset($_SESSION['locations']['items'][$i]['name'])){
          $data = get_location($_SESSION['locations']['items'][$i]['id']);
          unset($data['@context']);
          $_SESSION['locations']['items'][$i] = $data;
        }
      }

      return $_SESSION['locations'];
    }
    curl_close($ch);
  }
  return false;
}

function get_location($location){
  $ch = curl_init($location);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  curl_setopt($ch, CURLOPT_HTTPHEADER, array("Accept: application/activity+json"));
  $response = curl_exec($ch);

  return json_decode($response, true);
  curl_close($ch);
}

function set_default_locations(){
  $_SESSION['locations'] = array("items" => array(array("name" => "Home", "id" => "https://apps.rhiaro.co.uk/burrow#home"), array("name"=>"Work", "id" => "https://apps.rhiaro.co.uk/burrow#work"), array("name"=>"Mortal Peril", "id" => "https://apps.rhiaro.co.uk/burrow#peril")));
}

function form_to_json($post){
  $data = as2();
  $data['location'] = array("id" => $post['location']);
  $data['published'] = $post['year']."-".$post['month']."-".$post['day']."T".$post['time'].$post['zone'];
  $json = stripslashes(json_encode($data, JSON_PRETTY_PRINT));
  return $json;
}

function post_to_endpoint($json, $endpoint){
  $ch = curl_init($endpoint);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/activity+json"));
  curl_setopt($ch, CURLOPT_HTTPHEADER, array("Authorization: Bearer ".$_SESSION['access_token']));
  curl_setopt($ch, CURLOPT_HEADER, true);
  curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
  $response = curl_exec($ch);
  $info = curl_getinfo($ch);
  curl_close($ch);
  
  $code = $info['http_code'];
  if($code == "201") {
    $matches = array();
    preg_match('/Location:(.*?)\n/', $response, $matches);
  }
  
  return array("location"=>$matches, "code"=>$code, "response"=>$response);
}

if(isset($_POST['location'])){
  if(isset($_SESSION['me'])){
    $endpoint = discover_endpoint($_SESSION['me']);
    $result = post_to_endpoint(form_to_json($_POST), $endpoint);
  }else{
    $errors["Not signed in"] = "You need to sign in to post.";
  }
}

?>
<!doctype html>
<html>
  <head>
    <title>Burrow</title>
    <link rel="stylesheet" type="text/css" href="https://apps.rhiaro.co.uk/css/normalize.min.css" />
    <link rel="stylesheet" type="text/css" href="https://apps.rhiaro.co.uk/css/main.css" />
    <meta name="viewport" content="width=device-width, initial-scale=1">
  </head>
  <body>
    <main class="w1of2 center">
      <h1>Burrow</h1>
      
      <?if(isset($errors)):?>
        <div class="fail">
          <?foreach($errors as $key=>$error):?>
            <p><strong><?=$key?>: </strong><?=$error?></p>
          <?endforeach?>
        </div>
      <?endif?>
      
      <?if(isset($result)):?>
        <div>
          <p>The response from you your micropub endpoint:</p>
          <code><?=$endpoint?></code>
          <?if($result['code'] != "201"):?>
            <p class="fail">Nothing created, error code <strong><?=$result['code']?></strong></p>
          <?else:?>
            <p class="win">Post created.. <strong><?=$result['location'][0]?></strong></p>
          <?endif?>
          <pre>
            <? var_dump($result['response']); ?>
          </pre>
        </div>
      <?endif?>
      
      <form method="post" role="form" id="checkin" class="align-center">
        <?foreach($locations['items'] as $location):?>
          <p><button class="neat inner color3-bg" style="border: none; width: 100%;<?=isset($location["https://terms.rhiaro.co.uk/view#color"]) ? " background-color: ".$location["https://terms.rhiaro.co.uk/view#color"].";" : ""?>" type="submit" value="<?=$location['id']?>" name="location"><?=$location['name']?></button></p>
        <?endforeach?>
        <p>
          <select name="year" id="year">
            <option value="2016" selected>2016</option>
            <option value="2016">2015</option>
          </select>
          <select name="month" id="month">
            <?for($i=1;$i<=12;$i++):?>
              <option value="<?=date("m", strtotime("2016-$i-01"))?>"<?=(date("n") == $i) ? " selected" : ""?>><?=date("M", strtotime("2016-$i-01"))?></option>
            <?endfor?>
          </select>
          <select name="day" id="day">
            <?for($i=1;$i<=31;$i++):?>
              <option value="<?=date("d", strtotime("2016-01-$i"))?>"<?=(date("j") == $i) ? " selected" : ""?>><?=date("d", strtotime("2016-01-$i"))?></option>
            <?endfor?>
          </select>
          <input type="text" name="time" id="time" value="<?=date("H:i:s")?>" />
          <input type="text" name="zone" id="zone" value="<?=date("P")?>" />
        </p>
      </form>
      
      <div class="color3-bg inner">
        <?if(isset($_SESSION['me'])):?>
          <p class="wee">You are logged in as <strong><?=$_SESSION['me']?></strong> <a href="?logout=1">Logout</a></p>
        <?else:?>
          <form action="https://indieauth.com/auth" method="get" class="inner clearfix">
            <label for="indie_auth_url">Domain:</label>
            <input id="indie_auth_url" type="text" name="me" placeholder="yourdomain.com" />
            <input type="submit" value="signin" />
            <input type="hidden" name="client_id" value="https://rhiaro.co.uk" />
            <input type="hidden" name="redirect_uri" value="<?=$base?>" />
            <input type="hidden" name="state" value="<?=$base?>" />
            <input type="hidden" name="scope" value="post" />
          </form>
        <?endif?>
        
        <h2>Customise</h2>
        <h3>Locations</h3>
        <?if(isset($locations_source)):?>
          <p class="wee">Your locations are from <strong><?=$locations_source?></strong> <a href="?reset=1">Reset</a></p>
        <?else:?>
          <form method="post" class="inner wee clearfix">
            <p>If you have or know a webpage with an archive of locations/venues you'd like on this list, enter the URL here. Currently can only read AS2 Collections, unpaged, returned in JSON or JSON-LD.</p>
            <label for="locations_source">URL of a list of locations:</label>
            <input id="locations_source" name="locations_source" value="https://rhiaro.co.uk/locations" />
            <input type="submit" value="Fetch" />
          </form>
        <?endif?>
        <h3>Post...</h3>
        <form method="post" class="inner wee clearfix">
          <select name="posttype">
            <option value="as2" selected>AS2 JSON</option>
            <option value="mp" disabled>Micropub (form-encoded)</option>
            <option value="mp" disabled>Micropub (JSON)</option>
            <option value="ttl" disabled>Turtle</option>
          </select>
          <input type="submit" value="Save" />
        </form>
      </div>
    </main>
    <footer class="w1of2 center">
      <p><a href="https://github.com/rhiaro/burrow">Code</a> | <a href="https://github.com/rhiaro/burrow/issues">Issues</a>
      <?if(isset($_SESSION['access_token'])):?>
        | <a href="https://apps.rhiaro.co.uk/burrow?token=<?=$_SESSION['access_token']?>">Quicklink</a>
      <?endif?>
      </p>
    </footer>
  </body>
</html>