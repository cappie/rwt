<?php

  // set up the connection to the database
  //$dbh = new PDO('mysql:host=localhost;port=3306;dbname=db_name', 'db_username', 'db_password', array( PDO::ATTR_PERSISTENT => false, PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8'"));
  $dbh = new PDO('mysql:host=localhost;port=3306;dbname=rwt', 'rwt', 'rwt', array( PDO::ATTR_PERSISTENT => false, PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8'"));
