
<?php

require_once('TwitterQuery.php');

$query = array( // query parameters
    'q' => 'ucm',
    'count' => '200'
);

var_dump( queryTwitterAPI($query) );

?>

