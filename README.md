# dolumar
Hosting on Heroku:
* heroku create
* heroku addons:create cleardb
* heroku addons:create memcachier:dev
* Navigate to /setup.php

## Set up SMTP server
You need to set SMTP credentials in order to get the email validation working:
In heroku, set:
EMAIL_SMTP_SERVER: smtp.mandrillapp.com
EMAIL_SMTP_SECURE: tls
EMAIL_SMTP_PORT: 587
EMAIL_SMTP_USERNAME: abc
EMAIL_SMTP_PASSWORD: abc