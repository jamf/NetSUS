<?php	// connect to database

if( isset( $CONNECT ) ) {
    return;
}

$CONNECT=1;
$db = null;

try
{
	$db = new PDO("sqlite:/var/db/appliance.sdb");
}
catch (PDOException $e)
{
	die("Database Initialization Error: " + $e->getMessage());
}

// foreach ($dbh->query('SELECT * FROM bar') as $row)