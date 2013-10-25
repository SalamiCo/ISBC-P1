<?php

require_once('TwitterQuery.php');

function twitter_query ($term) {
	$query = array( // query parameters
	    'q'     => $term,
	    'count' => 200,
	    'result_type' => 'mixed',
	    'lang' => 'es'
	);

	return queryTwitterAPI($query);
	// foreach($results['statuses'] as $tweet){
	// 	echo "<img src=\"".  $tweet['user']['profile_image_url']."\"".">"."<br>"; 	//getting the profile image
	// 	echo "Name: ".       $tweet['user']['name']."<br>"; 						//getting the username
	// 	echo "Location: ".   $tweet['user']['location']."<br>";						//user location
	// 	echo "Language: ".   $tweet['metadata']['iso_language_code']."<br>";		//language code
	// 	echo "Text: ".   	 $tweet['text']."<br>";									//tweet text
	// 	echo "Coord0: ".   	 $tweet['geo']['coordinates'][0]."<br>";				//coordinates
	// 	echo "Coord1: ".   	 $tweet['geo']['coordinates'][1]."<br>";				//coordinates
	// }
}

function process_tweets ($tweets) {
	$processed = array();

	if (is_array($tweets)) {
		foreach ($tweets as $tweet) {
			$processed[] = array(
				'text' => $tweet['text'],
				'positive' => mt_rand(0, 8),
				'negative' => mt_rand(0, 8),
			);
		}
	}
	return $processed;
}

function read_lexicon($file_name){
	$file = fopen($file_name, "r");
	
	if($file === false){
		return null
	} else {
		$lexicon = array();
		while(!feof($file)){
			$line = fgets($file);
			$exp = explode("\t", $line);
			$word = $exp[0];
			$number = $exp[1];
			$value = $exp[2];
			$lexicon[$word] = array(
				'number'=>$number, 'value'=>$value);
		}
	}
	fclose($file);

	return $lexicon;
}