<?php
  
  require_once('functions.php');

  //error_reporting(0);
  $result = array('status' => 'error_unknown');

  if (!isset($_GET['term'])) {
    $result['status'] = 'error_search_term';
    $result['error'] = 'No search term was specified';

  } else {
    $queryResult = twitter_query($_GET['term']);

    if (isset($queryResult['errors'])) {
	    $result['status'] = 'error_api';
	    $result['error'] = 'API returned errors';
	    $result['api_errors'] = $queryResult['errors'];

    } else {
	    $result['status'] = 'ok';
	    $result['summary'] = array(
	      'positive' => 0,
	      'neutral'  => 0,
	      'negative' => 0
	    );

	    $lexicon = lexicon_stem(lexicon_read('lexicon.txt'));
	    $statuses = $queryResult['statuses'];
	    $result['tweets'] = process_tweets($statuses, $lexicon);
	    $global_frecuencies = global_wordcount($statuses);

	    foreach ($result['tweets'] as $tweet) {
	    	$pos = count($tweet['positive']);
	    	$neg = count($tweet['negative']);

	    	if ($pos > $neg) {
	    		$result['summary']['positive']++;
	    	} else if ($neg > $pos) {
	    		$result['summary']['negative']++;
	    	} else {
	    		$result['summary']['neutral']++;
	    	}
	    }
		}
  }

  /* Return the result array as JSON */
  header('Content-Type: application/json');
  echo json_encode($result, JSON_UNESCAPED_UNICODE);

  /* Something happened... */
  $jsonErr = json_last_error();
  if ($jsonErr != JSON_ERROR_NONE) {

    $jsonErrStr = 'UNKNOWN';
    switch ($jsonErr) {
      case JSON_ERROR_DEPTH: $jsonErrStr = 'DEPTH'; break;
      case JSON_ERROR_STATE_MISMATCH: $jsonErrStr = 'STATE_MISMATCH'; break;
      case JSON_ERROR_CTRL_CHAR: $jsonErrStr = 'CTRL_CHAR'; break;
      case JSON_ERROR_SYNTAX: $jsonErrStr = 'SYNTAX'; break;
      case JSON_ERROR_UTF8: $jsonErrStr = 'UTF8'; break;
      case JSON_ERROR_RECURSION: $jsonErrStr = 'RECURSION'; break;
      case JSON_ERROR_INF_OR_NAN: $jsonErrStr = 'INF_OR_NAN'; break;
      case JSON_ERROR_UNSUPPORTED_TYPE: $jsonErrStr = 'UNSUPPORTED_TYPE'; break;
    }

    echo json_encode(array(
      'status' => 'error_json',
      'code' => $jsonErrStr
    ));
  }