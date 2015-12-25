<?
session_start();
if(isset($_GET['logout'])){ session_unset(); session_destroy(); header("Location: /burrow"); }

include "link-rel-parser.php";

$base = "localhost";
if(isset($_GET['code'])){
  $auth = auth($_GET['code'], $_GET['state']);
  if($auth !== true){ $errors = $auth; }
  else{
    $response = get_access_token($_GET['code'], $_GET['state']);
    if($response !== true){ $errors = $auth; }
    else { header("Location: ".$_GET['state']); }
  }
}

function auth($code, $state, $client_id="https://apps.rhiaro.co.uk/burrow"){
  
  $params = "code=".$code."&redirect_uri=".urlencode($state)."&state=".urlencode($state)."&client_id=".$client_id;
  $ch = curl_init("https://indieauth.com/auth");
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/x-www-form-urlencoded"));
  curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
  $response = Array();
  parse_str(curl_exec($ch), $response);
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

function get_locations(){
  return array("home", "office", "transit", "other", "food", "meeting", "seminar", "event", "exercise", "volunteer");
}

function form_to_json($post){
  $data = as2();
  if(in_array($post['location'], get_locations())){
    $data['location'] = "http://rhiaro.co.uk/location/".$post['location'];
    if(isset($post['published'])){
      $data['published'] = $post['published'];
    }else{
      $data['published'] = date(DATE_ATOM);
    }
  }
  $json = stripslashes(json_encode($data, JSON_PRETTY_PRINT));
  return $json;
}

function post_to_endpoint($json, $endpoint){
  return $json;
}

$locations = get_locations();

if(isset($_POST['location'])){
  if(isset($_SESSION['me'])){
    $endpoint = discover_endpoint($_SESSION['me']);
    $result = post_to_endpoint(form_to_json($_POST), $endpoint);
  }else{
    $errors["Not signed in"] = "You need to sign in to post.";
  }
}
var_dump($_SESSION);

?>
<!doctype html>
<html>
  <head>
    <title>Burrow</title>
    <link rel="stylesheet" type="text/css" href="http://rhiaro.co.uk/css/normalise.min.css" />
    <link rel="stylesheet" type="text/css" href="http://rhiaro.co.uk/css/main.css" />
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
          <p>The following will be posted to your micropub endpoint:</p>
          <code><?=$endpoint?></code>
          <pre>
            <?=$result?>
          </pre>
        </div>
      <?endif?>
      
      <form method="post" role="form" id="checkin" class="align-center">
        <?foreach($locations as $location):?>
          <p><input class="neat inner color3-bg" type="submit" value="<?=$location?>" name="location" /></p>
        <?endforeach?>
      </form>
      
      <div class="color3-bg inner">
        <?if(isset($_SESSION['me'])):?>
          <p class="wee">You are logged in as <strong><?=$_SESSION['me']?></strong> <a href="?logout=1">Logout</a></p>
        <?else:?>
          <form action="https://indieauth.com/auth" method="get" class="inner clearfix">
            <label for="indie_auth_url">Domain:</label>
            <input id="indie_auth_url" type="text" name="me" placeholder="yourdomain.com" />
            <input type="submit" value="signin" />
            <input type="hidden" name="client_id" value="http://rhiaro.co.uk" />
            <input type="hidden" name="redirect_uri" value="<?=$base?><?=$_SERVER["REQUEST_URI"]?>" />
            <input type="hidden" name="state" value="<?=$base?><?=$_SERVER["REQUEST_URI"]?>" />
            <input type="hidden" name="scope" value="post" />
          </form>
        <?endif?>
      </div>
    </main>
  </body>
</html>