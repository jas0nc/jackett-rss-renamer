# jackett-rss-renamer
a php script to modify jackett's rss for use in sonarr, like removing Chinese character, rename English title, modify episode number

installation (synology):
1) download and unzip the file in the web folder
2) checked the absolute path of the jackettrssrenamer.php
3) in "Task Scheduler", create a scheduled task, enter user-defined script: php [absolute path]
   eg. php /volume1/web/jackett-rss-renamer/jackettrssrenamer.php
4) Open Dictionay.csv edit it for your needs (first col is FIND, second col is REPLACE)
5) run it in task scheduler
6) open [server ip]/jackett-rss-renamer/jackettrssrenamer.php to see your rss feed for use in Sonarr
   eg. http://192.168.1.100/jackett-rss-renamer/jackettrssrenamer.php
