<?php
  
  require_once('functions.php');

  error_reporting(0);
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
	    $global_frecuencies = global_frec($statuses);
	    echo '<pre>';
	    print_r($global_frecuencies);
	    echo '</pre>';

	    foreach ($result['tweets'] as $tweet) {
	    	$pos = $tweet['positive'];
	    	$neg = $tweet['negative'];

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
  echo json_encode($result, JSON_PRETTY_PRINT);