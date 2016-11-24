<?php

	$config = parse_ini_file( 'data.ini', true );

	$db = mysqli_connect($config['database']['server'],$config['database']['user'],$config['database']['pass'],$config['database']['database']);
    $token = $config['users']['token'];
	
	$id = array_shift(array_keys($_REQUEST));
	
	$file = null;
	$dbFiles = $db->query('SELECT id, name, url, adult, details FROM gif_img WHERE name = "'.$id.'"');
	foreach( $dbFiles as $file ){};
		
	$hashString = $file['name'] . $file['url'];

	if ( $file['adult'] == 1 )
		$hashString .= 'true';
	else
		$hashString .= 'false';
		
	if ( $file['details'] == null )
		$hashString .= 'null';
	else
		$hashString .= $file['details'];
	
	$hash = base64_encode( $hashString );

	if ( $file['adult'] == 1 && (!isset($_REQUEST['hash']) || $hash != $_REQUEST['hash']) ) {
		if ( isset($_REQUEST['debug']) ){
			echo '<pre>';
			var_dump( isset($_REQUEST['hash']) );
			var_dump( $hashString );
			var_dump( base64_decode($_REQUEST['hash']) );
			var_dump($hash);
			var_dump($_REQUEST['hash']);		
			exit();
		}
		header('Content-type:image/gif');
		$data = file_get_contents('img/00000.gif');
		echo $data;
		exit();
	}
		
	$data = file_get_contents( $file['url'] );
	
	header('Content-type:image/gif');
	echo $data;
	
?>