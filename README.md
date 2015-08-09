# dolumar
Hosting on Heroku:

First, make you sure you have the heroku toolbelt installed. Then run:
* heroku create
* heroku addons:create cleardb
* heroku addons:create memcachier:dev
* Navigate to /setup.php

## Set up SMTP server
You need to set SMTP credentials in order to get the email validation working:
In heroku, set:
* EMAIL_SMTP_SERVER: smtp.mandrillapp.com
* EMAIL_SMTP_SECURE: tls
* EMAIL_SMTP_PORT: 587
* EMAIL_SMTP_USERNAME: abc
* EMAIL_SMTP_PASSWORD: abc

Optionally, you can also set:
* AIRBRAKE_TOKEN (airbrake api token, to gather errors)
* SERVERLIST_URL (api that keeps track of all your servers)
* CREDITS_GAME_TOKEN (your game token on the catlab credits framework)
* CREDITS_PRIVATE_KEY (your private key to access catlab credits framework)

## CatLab Credits
If you want to offer paid features on your server, you will need to setup an account on the CatLab credits framework. 
Contact us at support@catlab.be in order to get you up and running.