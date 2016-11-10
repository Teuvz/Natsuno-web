<?php

    header('Content-type:image/gif');

    $data = file_get_contents( 'http://matthewpcharlton.com/gifs/api.php?random' );
    $data = json_decode($data,true);
    
	$file = array_shift($data['files']);
	
    $img = file_get_contents($file['src']);
    echo $img;

?>