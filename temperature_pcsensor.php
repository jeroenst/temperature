#!php
<?php  
{
  exec ('/usr/domotica/temperature/pcsensor/pcsensor -n2 | /bin/sed "s/.* //;s/C//"',$output) or 
  exec ('/usr/domotica/temperature/pcsensor/pcsensor -n2 | /bin/sed "s/.* //;s/C//"',$output) or die ("exec failed") or die ("pcsensor failure");
  
  $temp_outside=file("http://nas/od/wte")[0];
  writeTempDatabase ($output[0], $output[1], $temp_outside);
}

function writeTempDatabase($temp_livingroom, $temp_hal, $temp_outside)
{
$con = mysql_connect("nas","domotica","b-2020");
if (!$con)
  {
    die('Could not connect: ' . mysql_error());
      }
      
      mysql_select_db("domotica", $con) or die( mysql_error());
      
      mysql_query("INSERT INTO temperature (temp_livingroom, temp_hal, temp_outside)
      VALUES ($temp_livingroom, $temp_hal, $temp_outside)") or die( mysql_error());
      echo "Temp_Livingroom=$temp_livingroom, Temp_Hal=$temp_hal, Temp_Outside=$temp_outside\n";
      
      mysql_close($con);
}

?>  
