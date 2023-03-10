<!doctype html>
<html>
  <head>
    <title>Burrow</title>
    <link rel="stylesheet" type="text/css" href="../../views/normalize.min.css" />
    <link rel="stylesheet" type="text/css" href="../../views/core.css" />
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style type="text/css">
      main {
        width: 50%; margin-left: auto; margin-right: auto;
      }
      pre {
        max-height: 300px;
        overflow: scroll;
      }
      #reload {
        display: inline-block;
        width: 1em; height: 1em;
        border-radius: 100%;
        background-color: violet;
      }
      #reload:hover {
        cursor: pointer;
      }
    </style>
  </head>
  <body>
    <main>
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
          <p>The response from your endpoint:</p>
          <code><?=$endpoint?></code>
          <?if($result->status_code != "201"):?>
            <p class="fail">Nothing created, error code <strong><?=$result->status_code?></strong></p>
          <?else:?>
            <p class="win">Post created.. <strong><?=$result->headers['location']?></strong></p>
          <?endif?>
          <pre>
            <? var_dump($result); ?>
          </pre>
        </div>
      <?endif?>

      <form method="post" role="form" id="checkin" class="align-center">
        <?foreach($locations as $location):?>
          <p><button class="neat inner color3-bg" style="border: none; width: 100%; padding: 1em;<?=isset($location["color"]) ? " background-color: ".$location["color"].";" : ""?>" type="submit" value="<?=$location['id']?>" name="location"><?=$location['name']?></button></p>
        <?endforeach?>
        <p>
          <select name="year" id="year">
            <?for($i=date("Y");$i>=2018;$i--):?>
              <option value="<?=$i?>"<?=(isset($_POST['year']) && $i==$_POST['year']) ? " selected" : ""?>><?=$i?></option>
            <?endfor?>
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
          <input type="text" name="time" id="time" value="<?=date("H:i:s")?>" size="8" />
          <input type="text" name="zone" id="zone" value="<?=date("P")?>" size="5" />
          <span id="reload"></span>
        </p>
        <hr/>
        <!-- temp -->
        <select name="endpoint_uri">
          <option value="https://rhiaro.co.uk/outgoing/">rhiaro.co.uk</option>
          <option value="http://localhost/outgoing/">localhost</option>
        </select>
        <input type="password" name="endpoint_key" />
        <!--/ temp -->
        <hr/>
      </form>

      <div class="color3-bg inner">

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
      </div>
    </main>
    <footer class="w1of2 center">
      <p><a href="https://github.com/rhiaro/burrow">Code</a> | <a href="https://github.com/rhiaro/burrow/issues">Issues</a>
      <?if(isset($_SESSION['access_token'])):?>
        | <a href="https://apps.rhiaro.co.uk/burrow?token=<?=$_SESSION['access_token']?>">Quicklink</a>
      <?endif?>
      </p>
    </footer>
    <script>

      var reloadBtn = document.getElementById('reload');
      reloadBtn.addEventListener('click', function(e){
        var now = new Date();
        var year = now.getFullYear().toString();
        var month = now.getMonth()+1;
        month = month.toString().padStart(2, '0');
        var day = now.getDate();
        day = day.toString().padStart(2, '0');
        var time = now.getHours().toString().padStart(2, '0') + ':' + now.getMinutes().toString().padStart(2, '0') + ':' + now.getSeconds().toString().padStart(2, '0');
        var zoneDiff = now.getTimezoneOffset() / 60;
        if(zoneDiff <= 0){
          var sign = '+';
        }else{
          var sign = '-';
        }
        var zone = sign + Math.abs(zoneDiff).toString().padStart(2, '0') + ':00';

        var yearEles = document.getElementById('year').getElementsByTagName('option');
        var monthEles = document.getElementById('month').getElementsByTagName('option');
        var dayEles = document.getElementById('day').getElementsByTagName('option');

        for(var i = 0; i < yearEles.length; i=i+1){
          if(yearEles[i].value == year){
            yearEles[i].selected = 'true';
          }
        }
        for(var i = 0; i < monthEles.length; i=i+1){
          if(monthEles[i].value == month){
            monthEles[i].selected = 'true';
          }
        }
        for(var i = 0; i < dayEles.length; i=i+1){
          if(dayEles[i].value == day){
            dayEles[i].selected = 'true';
          }
        }

        document.getElementById('time').value = time;
        document.getElementById('zone').value = zone;

      });


    </script>
  </body>
</html>