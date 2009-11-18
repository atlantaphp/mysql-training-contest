<?php

$db = new SQLite3('mysql-contest.db');

$db->exec('
    CREATE TABLE entries (
        name STRING NOT NULL,
        email STRING NOT NULL,
        ip_addr STRING NOT NULL,
        time INTEGER NOT NULL,
        PRIMARY KEY(email))');

echo "\n\nmysql-contest.db SQLite3 database created with table 'entries'\n\n";
