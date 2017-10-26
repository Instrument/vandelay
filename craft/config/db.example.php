<?php

/**
 * Database Configuration
 *
 * All of your system's database configuration settings go in here.
 * You can see a list of the default settings in craft/app/etc/config/defaults/db.php
 */

return array(


  // replace unixSocket Instance Connection name below (everything afer "/cloudsql/")
  // you can find the Instance Connection name in the Instance Details for your Cloud SQL instance
    'unixSocket' => '/cloudsql/snapchat-ads:us-west1:mysecuresql-01',

  // 'server' and 'port' COMMENTED OUT BECAUSE SNAPCHAT USES CLOUD PROXY SOCKET INSTEAD
  // 'server' => '35.197.99.247',
  // 'port' => '3306',

  // The name of the database to select.
    'database' => 'craftcms',

  // The database username to connect with.
    'user' => 'DATABASE_USERNAME',

  // The database password to connect with.
    'password' => 'DATABASE_PASSWORD',

  // The prefix to use when naming tables. This can be no more than 5 characters.
    'tablePrefix' => 'craft',

);
