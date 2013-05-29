#!php
<?php  
{
//  exec ('/usr/domotica/temperature/pcsensor/pcsensor -n2 | /bin/sed "s/.* //;s/C//"',$output) or 
//  exec ('/usr/domotica/temperature/pcsensor/pcsensor -n2 | /bin/sed "s/.* //;s/C//"',$output) or die ("exec failed") or die ("pcsensor failure");
  $crcOk = 0;
  $busError = 0;
  $retryCounter = 0;
//  while (!$crcOk or $busError)
//  {
//    exec ('grep = /sys/bus/w1/devices/*/w1_slave > /tmp/w1.dat');
//    exec ('grep t= /tmp/w1.dat | sed "s/.*e1\/.* t/cv/;s/.*30\/.* t/garage/;s/.*60\/.* t/freezer/;s/.*2b\/.* t/hal/;s/.*67\/.* t/aquarium/;s/.*91\/.* t/vijver/;s/.*35\/.* t/buiten/;s/.*4b\/.* t/huiskamer/;s/.*de\/.* t/fridge/" > /tmp/temperature.dat');
//    exec ('grep NO /tmp/w1.dat', $dummy, $crcOk);
//    exec ('grep t= /tmp/w1.dat', $dummy, $busError);
//    if ($retryCounter++ > 2) 
//    {
//      echo ("To many retries, application terminated..");
//      exit(1);
//    }
//    if (!$crcOk) echo ("CRC Error Retrying!!!!");
//    if ($busError) 
//    {
//      echo ("Bus Error Retrying!!!!");
//      sleep (5);
//    }
//  }

  $temperatureArray = array();
  getOneWireTemperature ("28-000003e6bae1", "central_heater_water_out", $temperatureArray);
  getOneWireTemperature ("28-000003e6ce60", "freezer", $temperatureArray);
  getOneWireTemperature ("28-000003ebbc67", "fishtank", $temperatureArray);
  getOneWireTemperature ("28-000003ebdc35", "outside", $temperatureArray);
  getOneWireTemperature ("28-000003ebe54b", "livingroom", $temperatureArray);
  getOneWireTemperature ("28-000003e6c130", "garage", $temperatureArray);
  getOneWireTemperature ("28-000003e6dc2b", "hal", $temperatureArray);
  getOneWireTemperature ("28-000003ebc691", "outside_pond", $temperatureArray);
  getOneWireTemperature ("28-000003ebdfde", "fridge", $temperatureArray);  
  
  print_r ($temperatureArray);

  $con = mysql_connect("nas","domotica","b-2020");

  if (!$con)
  {
    die('Could not connect: ' . mysql_error());
  }
     
  mysql_select_db("domotica", $con) or die( mysql_error());

  mysql_query("INSERT INTO temperature (livingroom, hal, outside, fishtank, outside_pond, central_heater_water_out, freezer, garage, fridge)
   VALUES ($temperatureArray[livingroom], $temperatureArray[hal], $temperatureArray[outside], $temperatureArray[fishtank], $temperatureArray[outside_pond], $temperatureArray[central_heater_water_out], $temperatureArray[freezer], $temperatureArray[garage], $temperatureArray[fridge])") or die( mysql_error());

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

function getOneWireTemperature ($sensorId, $sensorName = "", &$temperatureArray = NULL)
{
  $nrOfRetries = 3;
  while ($nrOfRetries-- > 0)
  {
    if (file_exists ("/sys/bus/w1/devices/".$sensorId."/w1_slave"))
    {
      $oneWireData = file("/sys/bus/w1/devices/".$sensorId."/w1_slave", FILE_IGNORE_NEW_LINES);
      if (($oneWireData !== FALSE) and (count($oneWireData) > 1))
      {
        $crcOk = strpos($oneWireData[0], " YES") !== FALSE;
        if ($crcOk)
        {
          $temperature = explode ("=", $oneWireData[1])[1] / 1000;
          echo ($sensorName." (".$sensorId.")=".$temperature."\n");
          if ($temperatureArray !== NULL) $temperatureArray[$sensorName] = $temperature;
          return $temperature;
        }
      }
      else
      {
        if ($nrOfRetries > 0)
        {
          // If sensor read failure wait 5 sensor for sensor to come back...
          echo ("Sensor ".$sensorName." (".$sensorId.") read failure, waiting 5 seconds before retrying...\n");
          sleep (5);
          }
      }
    }
    else
    {
      if ($nrOfRetries > 0)
      {
        // If sensor not found wait 5 sensor for sensor to come back...
        echo ("Sensor ".$sensorName." (".$sensorId.") not found, waiting 15 seconds before retrying...\n");
        sleep (15);
      }
    }
  }

  echo ("Error while reading sensor ".$sensorName." (".$sensorId.")\n");
  if ($temperatureArray !== NULL) $temperatureArray[$sensorName] = "null";
  return NULL;
}

                        
?>  
