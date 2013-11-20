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
}

function process_tweets (&$tweets, &$lexicon) {
	$processed = array();

	if (is_array($tweets)) {
		foreach ($tweets as $tweet) {
			$encText = mb_convert_encoding($tweet['text'], 'ASCII', mb_internal_encoding());
			$procText = process_tweet_text($encText, $lexicon);

			$procTweet = array(
				'text' => $tweet['text'],
				'textWords' => $procText['words'],
				'user' => array(
					'name' => $tweet['user']['name'],
					'screenName' => $tweet['user']['screen_name'],
					'avatar' => $tweet['user']['profile_image_url_https']
				),
				'geo' => $tweet['coordinates'],
				'words' => array()
			);

			foreach ($procText['positive'] as $word) {
				$procTweet['words'][$word] = +1;
			}
			foreach ($procText['negative'] as $word) {
				$procTweet['words'][$word] = -1;
			}

			$postProcessed = postprocess_tweet($procTweet);
			$processed[] = $postProcessed;
		}
	}
	return $processed;
}

function process_tweet_text($text, &$lexicon){
	$pos = array();
	$neg = array();

	$words = preg_split('/((\p{P}+)|(\p{P}*\s+\p{P}*)|(\p{P}+))/',
		$text, -1, PREG_SPLIT_NO_EMPTY);

	$pwords = array();

	foreach ($words as $word){
		$wordp = mb_strtolower($word);
		$val = lexicon_word_value($lexicon, $wordp);

		if ($val != null){
			if ($val['value'] == VALUE_POSITIVE) {
				$pos[] = $val['stem'];
			} elseif ( $val['value'] == VALUE_NEGATIVE){
				$neg[] = $val['stem'];
			}
		}

		$pwords[] = word_stem($word);
	}

	return array(
		'words' => $pwords,
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
	return stemm_es::stemm($word);
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
		$ret = $lexicon[$stemmed][$word];

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

		$ret = array(
			'value'  => ($pos > $neg) ? VALUE_POSITIVE : VALUE_NEGATIVE,
			'number' => ($pos > $neg) ? $pos - $neg : $neg - $pos
		);
	}

	$ret['stem'] = $stemmed;
	return $ret;
}

function global_wordcount (&$tweets, &$lexicon) {
	$freq = array();
	if (is_array($tweets)) {
		foreach ($tweets as $tweet) {
			$words = preg_split('/((\p{P}+)|(\p{P}*\s+\p{P}*)|(\p{P}+))/',
				$tweet['text'], -1, PREG_SPLIT_NO_EMPTY);

			foreach (array_unique($words) as $word) {
				//add lexicon words not previously added
				$stem = word_stem($word);
				if(isset($lexicon[$stem]) && !isset($freq[$stem])) {
					$freq[$stem] = 1;
				} else if(isset($lexicon[$stem]) && isset($freq[$stem])){
					$freq[$stem]++;
				}
			}
		}
	}
	return $freq;
}

function postprocess_tweet (&$tweet) {

	return $tweet;
}

//TODO: hacer tf_idf para cada tweet => sacar el peso de cada palabra del tweet 
function tf_idf ($term, &$tweets, &$lexicon) {
	if (is_array($tweets)) {		
		$freq = global_wordcount($tweets, $lexicon);
		$filter = array(); 
		$stemmed_term = word_stem($term); //search term stemmed
		$D = count($tweets); //Corpus length
		foreach ($tweets as $tweet) {
			$words = preg_split('/((\p{P}+)|(\p{P}*\s+\p{P}*)|(\p{P}+))/',
				$tweet['text'], -1, PREG_SPLIT_NO_EMPTY);

			$local_freq = array();
			$local_wordcount = count($words);
			foreach ($words as $word) {
				//calculate local frequency for each word
				$stem = word_stem($word);
				if($stem != $stemmed_term){ //we dont want to filter the search term
					if(isset($lexicon[$stem]) && !isset($local_freq[$stem])) {
						$local_freq[$stem] = 1;
					} else if(isset($lexicon[$stem]) && isset($local_freq[$stem])){
						$local_freq[$stem]++;
					}
				}				
			}
			
			//filter the minimum TF-IDF if there are more of than word
			$min_wp = PHP_INT_MAX;
			$filtered = null;
			$keys = array_keys($local_freq);

			if (count($keys) > 1) { //only filter if there are more than one word
				foreach ($keys as $key) {						
					$tf  = $local_freq[$key] / $local_wordcount;
					$idf = log($D / $freq[$key], 2);				
					$wp = $tf * $idf;
					
					if($wp < $min_wp) {
						$min_wp = $wp;
						$filtered = $key;
					}
				}
			}	
			//filter[i] = filtered word for tweet i, else null
			$filter[] = $filtered;		
		}		
	}
	return $filter;
}
