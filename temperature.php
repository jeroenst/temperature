#!php
<?php  
{
//  exec ('/usr/domotica/temperature/pcsensor/pcsensor -n2 | /bin/sed "s/.* //;s/C//"',$output) or 
//  exec ('/usr/domotica/temperature/pcsensor/pcsensor -n2 | /bin/sed "s/.* //;s/C//"',$output) or die ("exec failed") or die ("pcsensor failure");
  $crcNoNotFound = 0;
  while (!$crcNoNotFound)
  {
    exec ('grep = /sys/bus/w1/devices/*/w1_slave > /tmp/w1.dat');
    exec ('grep t= /tmp/w1.dat | sed "s/.*e1\/.* t/cv/;s/.*30\/.* t/garage/;s/.*60\/.* t/freezer/;s/.*2b\/.* t/hal/;s/.*67\/.* t/aquarium/;s/.*91\/.* t/vijver/;s/.*35\/.* t/buiten/;s/.*4b\/.* t/huiskamer/;s/.*de\/.* t/fridge/" > /tmp/temperature.dat');
    exec ('grep NO /tmp/w1.dat', $dummy, $crcNoNotFound);
    if (!$crcNoNotFound) echo ("CRC Error Retrying!!!!");
  }

  $temp_livingroom=matchdiv("huiskamer", "/tmp/temperature.dat", 1000);
  $temp_hal=matchdiv("hal", "/tmp/temperature.dat", 1000);
  $temp_outside=matchdiv("buiten", "/tmp/temperature.dat", 1000);
  $temp_fishtank=matchdiv("aquarium", "/tmp/temperature.dat", 1000);
  $temp_outside_pond=matchdiv("vijver", "/tmp/temperature.dat", 1000);
  $temp_cv=matchdiv("cv", "/tmp/temperature.dat", 1000);
  $temp_freezer=matchdiv("freezer", "/tmp/temperature.dat", 1000);
  $temp_garage=matchdiv("garage", "/tmp/temperature.dat", 1000);
  $temp_fridge=matchdiv("fridge", "/tmp/temperature.dat", 1000);
  
  echo "Temp_Livingroom=$temp_livingroom\nTemp_Hal=$temp_hal\nTemp_Outside=$temp_outside\nFreezer=$temp_freezer\nCv=$temp_cv\nFishtank=$temp_fishtank\nGarage=$temp_garage\nFridge=$temp_fridge";

  $con = mysql_connect("nas","domotica","b-2020");

  if (!$con)
  {
    die('Could not connect: ' . mysql_error());
  }
     
  mysql_select_db("domotica", $con) or die( mysql_error());

  mysql_query("INSERT INTO temperature (livingroom, hal, outside, fishtank, outside_pond, central_heater_water_out, freezer, garage, fridge)
   VALUES ($temp_livingroom, $temp_hal, $temp_outside, $temp_fishtank, $temp_outside_pond, $temp_cv, $temp_freezer, $temp_garage, $temp_fridge)") or die( mysql_error());

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
        if ( $ret ) break;
      }
   return $ret;
}
                        
?>  
