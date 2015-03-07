<?php
	$servername = "localhost";
	$username = "root";
	$password = "ubuntu";
	try {
	    $conn = new PDO("mysql:host=".$servername.";dbname=yelpc", $username, $password);
	    // set the PDO error mode to exception
	    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	    //echo "Connected successfully"; 
	    }
	catch(PDOException $e)
	    {
	    echo "Connection failed: " . $e->getMessage();
	    }

	    try {
			$sth = $conn->prepare('SELECT * FROM address');
			$sth->execute();
		}
		catch(Exception $e)
		{
			echo $e->getMessage();
		}

		$rows = $sth->fetchAll();

	$locations = array();
	 
	foreach($rows as $location){
	    array_push($locations, $location);
	}
	 
	//Output JSON
	file_put_contents("locations.json",json_encode($locations));

?>