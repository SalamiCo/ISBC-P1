<?php
  
  require_once('functions.php');

  $result = array('status' => 'error_unknown');

  if (!isset($_GET['term'])) {
    $result['status'] = 'error_search_term';
    $result['error'] = 'No search term was specified';

  } else {
    $queryResult = twitter_query($_GET['term']);

    $result['status'] = 'ok';
    $result['summary'] = array(
      'positive' => 0,
      'neutral'  => 0,
      'negative' => 0
    );
    $result['tweets'] = process_tweets($queryResult['statuses']);

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

  /* Return the result array as JSON */
  header('Content-Type: application/json');
  echo json_encode($result);