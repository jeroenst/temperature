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
            $temperatureArray[$key] = $temperature;
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
    echo "Writing values to database and xml file...\n";

    $xml = new SimpleXMLElement('<root/>');
    foreach ($temperatureArray as $key => $value) $xml->addChild($key, $value);

    $dom = dom_import_simplexml($xml)->ownerDocument;
    $dom->formatOutput = true;
    file_put_contents('/tmp/temperature.xml', $dom->saveXML());

    if (!$con)
    {
      $con = mysql_connect("nas","domotica","b-2020");
    }
     
    if ($con) 
    {
      mysql_select_db("domotica", $con) or die( mysql_error());

      mysql_query("INSERT INTO temperature (livingroom, hal, outside, fishtank, outside_pond, central_heater_water_out, freezer, garage, fridge, bedroom, bathroom, attic)
       VALUES ($temperatureArray[livingroom], $temperatureArray[hal], $temperatureArray[outside], $temperatureArray[fishtank], $temperatureArray[outside_pond], $temperatureArray[central_heater_water_out], $temperatureArray[freezer], $temperatureArray[garage], $temperatureArray[fridge], $temperatureArray[bedroom], $temperatureArray[bathroom], $temperatureArray[attic])") or die( mysql_error());
    }
  }
  mysql_close($con);
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
  if (!file_exists ("/sys/bus/w1/devices/".$sensorId."/w1_slave"))
  {
       exec ("echo $sensorId > /sys/bus/w1/devices/w1_bus_master1/w1_master_add");
       sleep (1);
  }

  if (file_exists ("/sys/bus/w1/devices/".$sensorId."/w1_slave"))
  {
    $oneWireData = file("/sys/bus/w1/devices/".$sensorId."/w1_slave", FILE_IGNORE_NEW_LINES);
    if (($oneWireData !== FALSE) and (count($oneWireData) > 1))
    {
      $crcOk = ((strpos($oneWireData[0], " YES")) !== false);
      if ($crcOk)
      {
        $temperature = explode ("=", $oneWireData[1])[1] / 1000;
  //      echo ("$temperature\n");
        return $temperature;
      }
    }
  }
//  echo ("Error\n");
  return NULL;
}
                        
?>  
