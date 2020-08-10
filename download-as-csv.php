<?php
// http://stackoverflow.com/questions/125113/php-code-to-convert-a-mysql-query-to-csv
require_once( dirname( dirname( dirname( dirname( __FILE__ )))) . '/wp-load.php' );
require_once( dirname( __FILE__ ) . '/level-assessment.php' );
global $wpdb;

$table_name = $wpdb->prefix.'level_assessment';

// Create connection
$db_con = new mysqli( DB_HOST, DB_USER, DB_PASSWORD, DB_NAME );

if( !isset($_GET['type']) ) die();

$query = "SELECT
	name AS '".__( 'Name', 'level-assessment' )."',
	email AS '".__( 'Email', 'level-assessment' )."',
	language AS '".__( 'Language', 'level-assessment' )."',
	level AS '".__( 'Level', 'level-assessment' )."',
	time AS '".__( 'Date', 'level-assessment' )."'
	FROM $table_name
";

if( $_GET['type'] === 'list' ) {
	$query .= "WHERE type = 'test';";
	$output_name = 'imi-list-'.date('y-m-d').'.csv';
} elseif( $_GET['type'] === 'time' ) {
	$query .= "WHERE type = 'time';";
	$output_name = 'imi-requested-times-'.date('y-m-d').'.csv';
} else {
	die();
}

$result = $db_con->query( $query );
if ( !$result ) die('Couldn\'t fetch records');


//open file pointer to standard output
$fp = fopen('php://output', 'w');

if ($fp && $result) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="'.$output_name.'"');
    header('Pragma: no-cache');
    header('Expires: 0');

	// add a Heading
	//fputcsv( $fp, array( 'IMI Level Assessment List' ), ';', ' ' );

	// output header row (if at least one row exists)
	$row = $result->fetch_assoc();
	if( $row ) {
		fputcsv( $fp, array_keys($row), ';' );
		// reset pointer back to beginning
		$result->data_seek ( 0 );
	}

	// write rows
	while( $row = $result->fetch_assoc() ) {
		fputcsv( $fp, $row, ';' );
	}

	fclose($fp);
    die;
}
