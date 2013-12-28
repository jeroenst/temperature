#!/usr/bin/php
<?php  


{
  $oneWireAddressArray = array( "garage" => "28-000003e6c130",
                                "central_heater_water_out" => "28-000003e6bae1",
                                "freezer" => "28-000003e6ce60",
                                "outside" => "28-000003ebdc35",
                                "livingroom" => "28-000003ebe54b",
                                "fishtank" => "28-000004a81dde",
                                "hal" => "28-000003e6dc2b",
                                "outside_pond" => "28-000003ebc691",
                                "fridge" => "28-000003ebdfde",
                                "bedroom" => "28-000004a78c8a",
                                "bathroom" => "28-000004a84bb8",
                                "attic" => "28-000004a79671",
                              );
  $timeout = 60;
  $cachetimeout = 600;

  $write_database_timeout = 10; // write database every 10 minutes
  $write_database_timer = time();

  $temperatureArray = array_fill_keys(array_keys($oneWireAddressArray), "null");
  $temperatureTimeArray = array_fill_keys(array_keys($oneWireAddressArray), 0);
  $firstRun = 1;
  $con = 0;

  while (1)
  {  
    // Read all sensors or sleep until timeout has exceeded (except on first run)

    $timeOutTime = time() + $timeout;
    if ($firstRun) $timeOutTime = time() + 15;
    $readingFailed = 1;
    $temperatureOkArray = array_fill_keys(array_keys($oneWireAddressArray), 0);
    while (($timeOutTime > time()))
    {
      $readingFailed = 0;
      // If previous reading failed sleep a while before retrying..
      foreach ($oneWireAddressArray as $key => $value)
      {
        // Get temperature if it's not read ok yet...
        if ($temperatureOkArray[$key] == 0)
        {
          echo ("Reading sensor $key ");
          $temperature = getOneWireTemperature ($value);
          if ($temperature != NULL) 
          {
            $temperatureArray[$key] =  sprintf("%01.1f", $temperature);
            $temperatureTimeArray[$key] = time();
            echo ("Ok, value is $temperature\n");
            //$temperatureOkArray[$key] = 1;
          }
          else
          {
            $readingFailed = 1;
            $temperatureOkArray[$key] = 0;
            // If reading failed and may not be cached anymore clear it
            if (($temperatureTimeArray[$key] + $cachetimeout) < time()) 
            {
              if ($temperatureTimeArray[$key] > 0)
              {
                echo ("FAILED, clearing cached value (cache timeout exceeded)\n");
              }
              else
              {
                echo ("FAILED, no cached value available\n");
              }
              $temperatureArray[$key] = "null";
            }
            else
            {
              if ($temperatureArray[$key] != "null")
              {
                $timeoutleft=($temperatureTimeArray[$key] + $cachetimeout) - time();
                echo ("FAILED, keeping cached value $temperatureArray[$key] (Timeout in $timeoutleft seconds) \n");
              }
              else
              {
                echo ("FAILED, no cached value available\n");
              }
            }
          }
        }
        // Let bus come to rest
        sleep (1);
      }
//      if ($readingFailed)
//      {
  //     sleep (20);
//      }
    }

    // If all sensors are refreshed goto sleep until timeout is finished
    //if (!$firstRun) sleep (max($timeOutTime - time(),0));
      // else $firstRun = 0;

   $temperatureArray["minutetimestamp"] = round (time()/60) * 60;
    
    // print_r ($temperatureArray);
    echo "Writing values to xml file...\n";

    $xml = new SimpleXMLElement('<root/>');
   foreach ($temperatureArray as $key => $value) if ($value != "null") $xml->addChild($key, $value); else $xml->addChild($key);

    $dom = dom_import_simplexml($xml)->ownerDocument;
    $dom->formatOutput = true;

    // We create new file with content and move it over old one after writing to prevent disturbing readers of old file (http://unix.stackexchange.com/questions/41668/what-happens-when-you-read-a-file-while-it-is-overwritten)
    file_put_contents('/tmp/temperature.xml.tmp', $dom->saveXML(), LOCK_EX);
    exec ('mv /tmp/temperature.xml.tmp /tmp/temperature.xml');

    if ($write_database_timer < time() )
    {
      echo "Writing values database...\n";
      $write_database_timer = time() + ($write_database_timeout * 60);

      $con = mysql_connect("172.20.0.1","domotica","dom8899");
     
      if ($con) 
      {
        mysql_select_db("domotica", $con) or mysql_close($con);
      }
      
      if ($con)
      {
        mysql_query("INSERT INTO temperature (livingroom, hal, outside, fishtank, outside_pond, central_heater_water_out, freezer, garage, fridge, bedroom, bathroom, attic)
        VALUES ($temperatureArray[livingroom], $temperatureArray[hal], $temperatureArray[outside], $temperatureArray[fishtank], $temperatureArray[outside_pond], $temperatureArray[central_heater_water_out], $temperatureArray[freezer], $temperatureArray[garage], $temperatureArray[fridge], $temperatureArray[bedroom], $temperatureArray[bathroom], $temperatureArray[attic])") or die( mysql_error());
        mysql_close($con);
      }
      else
      {
        echo ("Writing to mysql failed...\n");
      }
    }
  }
}

function matchdiv($needle, $file, $dividevalue)
{
  $ret = NULL;
  $lines = file($file);

      foreach ( $lines as $line )
      {
        list($key, $val) = explode('=', $line);
        $ret = $key==$needle ? $val/$dividevalue : NULL;
        if ( $ret )
        {
          echo ($needle."=".$ret."\n");
          break;
        }
      }
   return $ret;
}

function getOneWireTemperature ($sensorId)
{
  exec ("echo $sensorId > /sys/bus/w1/devices/w1_bus_master1/w1_master_add");

  $retry = 3;
  
  while ($retry--)
  {
    if (file_exists ("/sys/bus/w1/devices/".$sensorId."/w1_slave"))
    {
      $oneWireData = file("/sys/bus/w1/devices/".$sensorId."/w1_slave", FILE_IGNORE_NEW_LINES);
      if (($oneWireData !== FALSE) and (count($oneWireData) > 1))
      {
        $crcOk = ((strpos($oneWireData[0], " YES")) !== false);
        if ($crcOk)
        {
          $temperature = explode ("=", $oneWireData[1])[1] / 1000;
          if (85 == $temperature) return NULL;
          return round($temperature,1);
        }
      }
    }
    sleep (1);
    echo ("failed, retrying ");
  }
  return NULL;
}
                        
?>  
