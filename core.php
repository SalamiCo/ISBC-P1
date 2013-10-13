<?php
  
  $result = array('status' => 'error_unknown');

  if (!isset($_GET['term'])) {
    $result['status'] = 'error_search_term';
    $result['error'] = 'No search term was specified';

  } else {
    $result['status'] = 'ok';
    $result['summary'] = array(
      'positive' => mt_rand(0, 128),
      'negative' => mt_rand(0, 128)
    );
    $result['tweets'] = array();
  }

  /* Return the result array as JSON */
  header('Content-Type: application/json');
  echo json_encode($result);