# Welcome To This Telegram-Push-Bot 

------

## Use Guide

1.add [botfather](https://telegram.me/botfather) on telegram

2.input `/newbot` 

3.input you bot name (For example：`VpsStockBot`)

4.this time system will can reply you `token`  

5.replace the `token` in bot.php

6.set web hook : access this url  (https://api.telegram.org/bot[Token]/setWebhook?url=https://[domain]/bot.php)

7.begin use

-----

send `/start` after adding robot will get sckey  
adding a robot to the channel and setting an administrator to send `/start` will get sckey

Send a message
POST http://[domain]/bot.php

method=send&sckey=[XXX]&content=[CONTENT]


