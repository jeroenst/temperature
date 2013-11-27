#!/bin/bash
checktemp()
{
echo "$1 temperature = $4";


if [ $(echo " $4 < $2 " | bc) -eq 1 ]; then
	echo "Fishtank to cold ($4)"
	echo -ne "From: \"RaspberryPi\" <rpi@jeroensteenhuis.nl>\nTo: jeroensteenhuis80@gmail.com\nSubject: Fishtank to cold ($4)\n\n.\n" | /usr/sbin/sendmail jeroensteenhuis80@gmail.com
else
	if [ $(echo " $4 > $3 " | bc) -eq 1 ]; then
		echo "Fishtank to hot ($4)"
		echo -ne "From: \"RaspberryPi\" <rpi@jeroensteenhuis.nl>\nTo: jeroensteenhuis80@gmail.com\nSubject: Fishtank to hot ($4)\n\n.\n" | /usr/sbin/sendmail jeroensteenhuis80@gmail.com
	else
		echo "Fishtank ok ($4)"
	fi
fi
}        
        

checktemp "Aquarium" 24 26 `cat /tmp/temperature.xml | grep fishtank | sed "s/<\/.*//;s/.*>//"`
checktemp "Huiskamer" 10 30 `cat /tmp/temperature.xml | grep livingroom | sed "s/<\/.*//;s/.*>//"`
checktemp "Slaapkamer" 8 30 `cat /tmp/temperature.xml | grep bedroom | sed "s/<\/.*//;s/.*>//"`


        
        
