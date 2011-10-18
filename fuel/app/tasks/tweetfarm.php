<?php

/**
 * TweetFarm
 * Twitter Aggregator Thread Manager
 * /app/tasks/cron.php
 *
 * @author SilenceIT, Jascha McDonald 
 */
 
// Define the constants
const MAXPROCESS = 10;
const MAXCOLLECTIONS = 5;

function findCollections()
{
	// get all collections that are out of date
	// Might want to wait somewhere in here so we don't hammer the DB
	return rand(5,50);
}

// Define some variables
$running_children = 0;
$harvesters = 0;
$collections_toprocess = array();

// Loop forever
while (true){
	// Find all the out of date collections

	$pid = pcntl_fork();
	
	if ($pid == -1)
	{
		die("could not fork\n");
	} else if ($pid) {
		if ($harvesters == 0 && $running_children == 0){
			// First run, or everyone is done, look for some collections, $collections_toprocess should also be blank
			$collections_toprocess = findCollections();
			$harvesters = round($collections_toprocess / MAXCOLLECTIONS);
			print "First run or finished, need ".$harvesters." new harvesters to process ".$collections_toprocess." collections\n";
		}
		
	    if ( $running_children >= $harvesters ){
	    	print "Waiting to spawn more harvesters, enough are running\n";
	        pcntl_wait($status);
	        $running_children--;
	    } else if ( $running_children < $harvesters) {
		    // Get MAXCOLLECTIONS number of collections to process WHERE the collections are not being processed by other harvesters and add to $collections_toprocess array
	    	print "There are ".$running_children." children running".$harvesters." harvesters, checking if we need more\n";
	    	//$collections = findCollections();
			//$harvesters += round($collections / MAXCOLLECTIONS) - $harvesters;

	    	// findCollections();
	    	
	    	// Allow another child to run
			$running_children++;
	    }
	} else {
		// unset collections_toprocess, only the parent needs it and this will save memory
		unset($collections_toprocess);
		sleep(rand(2,4));
		// I'm a child Spawn a harvester with a set of collections do the work and exit
		exit;
	}
}

/* End of tweetfarm.php */
