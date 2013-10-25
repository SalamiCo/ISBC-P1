<?php

require_once('TwitterQuery.php');
require_once('stemm_es.php');

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

function process_tweets ($tweets, $lexicon) {
	$processed = array();

	if (is_array($tweets)) {
		foreach ($tweets as $tweet) {
			process_tweet_text($tweet['text']);

			$processed[] = array(
				'text' => $tweet['text'],
				'positive' => mt_rand(0, 8),
				'negative' => mt_rand(0, 8),
			);
		}
	}
	return $processed;
}

function word_stem($word){
	return stemm_es::stemm($word);
}

function lexicon_stem ($lexicon) {
	$stemmedLex = array();

	foreach ($lexicon as $word=>$data) {
		$data['word'] = $word;
		$stemmedWord = word_stem($word);

		if (isset($stemmedLex[$stemmedWord])) {
			$stemmedLex[$stemmedWord][] = $data;
		} else {
			$stemmedLex[$stemmedWord] = array($data);
		}
	}

	return $stemmedLex;
}