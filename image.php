<?php

	header('Content-type:image/gif');

	$db = mysqli_connect('matthewpsql.mysql.db','matthewpsql','xBo4esIP','matthewpsql');
	$token = 'abcdefGhIjklmnOpqrStuVWxyz';
	
	$id = array_shift(array_keys($_REQUEST));
	
	$file = null;
	$dbFiles = $db->query('SELECT id, name, url, adult, details FROM gif_img WHERE name = "'.$id.'"');
	foreach( $dbFiles as $file ){};
	
	$data = file_get_contents( $file['url'] );
	echo $data;
	
?>