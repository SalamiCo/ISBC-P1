<?php
  
  require_once('functions.php');

  $result = array('status' => 'error_unknown');

  if (!isset($_GET['term'])) {
    $result['status'] = 'error_search_term';
    $result['error'] = 'No search term was specified';

  } else {
    $queryResult = twitter_query($_GET['term']);

    if (isset($queryResult['errors'])) {
	    $result['status'] = 'error_api';
	    $result['error'] = 'API returned errors';

    } else {
	    $result['status'] = 'ok';
	    $result['summary'] = array(
	      'positive' => 0,
	      'neutral'  => 0,
	      'negative' => 0
	    );

	    $lexicon = lexicon_stem(lexicon_read('lexicon.txt'));
	    echo '<pre>';
	    print_r($lexicon, false);
	    echo '</pre>';

	    $result['tweets'] = process_tweets($queryResult['statuses'], $lexicon);

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
  echo json_encode($result);