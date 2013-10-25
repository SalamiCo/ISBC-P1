<?php

require_once('TwitterQuery.php');
require_once('stemm_es.php');

define('VALUE_POSITIVE', 'pos');
define('VALUE_NEGATIVE', 'neg');

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

function process_tweets ($tweets, &$lexicon) {
	$processed = array();

	if (is_array($tweets)) {
		foreach ($tweets as $tweet) {
			$procText = process_tweet_text($tweet['text'], $lexicon);

			$processed[] = array(
				'text' => $tweet['text'],
				'positive' => $procText['positive'],
				'negative' => $procText['negative']
			);
		}
	}
	return $processed;
}

function process_tweet_text($text, &$lexicon){
	$pos = array();
	$neg = array();

	$words = preg_split('/((\p{P}+)|(\p{P}*\s+\p{P}*)|(\p{P}+))/',
		$text, -1, PREG_SPLIT_NO_EMPTY);
	foreach ($words as $word){
		$wordp = strtolower(iconv('ISO-8859-1','ASCII//TRANSLIT', $word));
		$val = lexicon_word_value($lexicon, $wordp);
		// echo "<pre>[$word] => ";
		// print_r($val);
		// echo '</pre>';

		if ($val != null){
			if ($val['value'] == VALUE_POSITIVE) {
				$pos[] = $word;
			} elseif ( $val['value'] == VALUE_NEGATIVE){
				$neg[] = $word;
			}
		}
	}
	return array(
		'positive' => $pos,
		'negative' => $neg
	);
}

function lexicon_read($file_name){
	$file = fopen($file_name, "r");
	
	if ($file === false) {
		return null;
	}

	$lexicon = array();
	while (!feof($file)) {
		$line = fgets($file);
		$exp = explode("\t", $line);

		$word = $exp[0];
		$number = (int) $exp[1];
		$value = $exp[2];

		$lexicon[$word] = array(
			'number'=> $number,
			'value' => $value
		);
	}
	fclose($file);

	return $lexicon;
}

function word_stem($word){
	$stemmer = new stemm_es();
	return $stemmer->stemm($word);
}

function lexicon_stem ($lexicon) {
	$stemmedLex = array();

	foreach ($lexicon as $word=>$data) {
		$stemmedWord = word_stem($word);

		if (!isset($stemmedLex[$stemmedWord])) {
			$stemmedLex[$stemmedWord] = array();
		}

		$stemmedLex[$stemmedWord][$word] = $data;
	}

	return $stemmedLex;
}

function lexicon_word_value (&$lexicon, $word) {
	$stemmed = word_stem($word);

	if (!isset($lexicon[$stemmed])) {
		return null;
	}

	if (isset($lexicon[$stemmed][$word])) {
		return $lexicon[$stemmed][$word];

	} else {
		$pos = 0;
		$neg = 0;

		foreach ($lexicon[$stemmed] as $data) {
			if ($data['value'] == VALUE_POSITIVE) {
				$pos += $data['number'];
				
			} else if ($data['value'] == VALUE_NEGATIVE) {
				$neg += $data['number'];
			}
		}

		return array(
			'value'  => ($pos > $neg) ? VALUE_POSITIVE : VALUE_NEGATIVE,
			'number' => ($pos > $neg) ? $pos - $neg : $neg - $pos
		);
	}
}