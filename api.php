<?php
   
    $db = mysqli_connect('matthewpsql.mysql.db','matthewpsql','xBo4esIP','matthewpsql');
    $token = 'abcdefGhIjklmnOpqrStuVWxyz';

    error_reporting(-1);
	ini_set('error_reporting', E_ALL);
    ini_set('display_startup_errors', 1);
    ini_set('display_errors', '1');

    header("Access-Control-Allow-Origin: *");
	header('Content-Type: application/json');

	$method = null;

	if ( isset($_REQUEST['method']) )
		$method = $_REQUEST['method'];
	else if ( count($_GET) > 0 ) {
        $keys = array_keys($_GET);
		$method = array_shift($keys);
    }
		
	if ( $method == null ) {
		$response = array(
			'success' => false,
			'error' => 'missing method'
		);
		echo json_encode($response);
		exit();
	}
	
   $method .= 'Action';
   $result = $method($_REQUEST);
      
   echo json_encode( $result );
   
   function downloadAction() {
		global $token, $db;
       
		if ( !isset($_REQUEST['token']) || $_REQUEST['token'] != $token )
			return array('success' => false, 'error' => 'false or missing token' );
    
		$url = $_REQUEST['url'];
       
		$files = listAction(true);
		$files = $files['files'];
		$nameFile = count($files);
       
		while( strlen($nameFile) < 5 )
			$nameFile = '0'.$nameFile;
        
		// adult ?
		if ( isset($_REQUEST['adult']) && $_REQUEST['adult'] == 'true' )
			$adult = 1;
		else
			$adult = 0;
				
		// save
		$db->query( "INSERT INTO gif_img (name,url,adult) VALUES ('".$nameFile."','".$url."',".$adult.")" );
		$img = $db->insert_id;
		
		// live ?
		if ( isset($_REQUEST['live']) && $_REQUEST['live'] == 'true' ){
			$tagLive = null;
			$tagsDb = $db->query('SELECT id, label FROM gif_tag WHERE label = "live"');
			foreach( $tagsDb as $tagLive ){}; 
			
			$db->query( 'INSERT INTO gif_img_tag (id_img,id_tag,date) VALUES ('.$img.','.$tagLive['id'].',NOW())' );
		}
		
		// series ?
		if ( isset($_REQUEST['series']) && $_REQUEST['series'] != '' ) {
			
			$series = $_REQUEST['series'];
			$series = explode(',', $series);
			foreach( $series as $key => $serie ) {
				$series[$key] = '"'.$serie.'"';
			}
			$series = implode(',',$series);
			
			$seriesDb = $db->query('SELECT id, label FROM gif_serie WHERE label IN ('.$series.')');
			
			$sql = "INSERT INTO gif_img_serie (id_img,id_serie,date) VALUES ";
			
			foreach( $seriesDb as $serie ) {
				
				$sql .= '('.$img.','.$serie['id'].',NOW())';
				
			}
			
			$db->query($sql);
		}
		
		return array('success' => true, 'img' => $nameFile );
    }

   function allseriesAction() {
		global $db;
	
		$series = array();
		$seriesDb = $db->query( "SELECT s.id, s.label, count(si.id_img) as nb FROM gif_serie s LEFT JOIN gif_img_serie si ON s.id = si.id_serie GROUP BY s.id ORDER BY nb DESC" );
	
		foreach( $seriesDb as $serie ) {
			$series[ $serie['id'] ] = $serie;
		}
	
		return array( 'success' => true, 'series' => $series );
   }
   
   function serieAction() {
		global $token, $db;
		
		$value = $_REQUEST['value'];
		$imgName = $_REQUEST['id'];
		$serie = null;
		$img = null;
		
		// get serie
		$seriesDb = $db->query( "SELECT id, label FROM gif_serie WHERE label = '".$value."'" );
		
		foreach( $seriesDb as $serie ) {};
		
		if ( $serie === null ) {
			$db->query( "INSERT INTO gif_serie (label) VALUES ('".$value."')" );
			
			$serie = array(
				'id' => $db->insert_id,
				'label' => $value
			);
		}
		
		// get img
		$imgDb = $db->query( "SELECT id FROM gif_img WHERE name = '".$imgName."'" );		
		foreach( $imgDb as $img ) {};
		
		// create link
		$db->query( "INSERT INTO gif_img_serie (id_img,id_serie,date) VALUES (".$img['id'].",".$serie['id'].",NOW())" );
		
		return array('success'=>true);
   }
   
   function alltagsAction() {
	   global $db;
	   
	   $tags = array();
	   $tagsDb = $db->query( "SELECT t.id, t.label, count(ti.id_img) as nb FROM gif_tag t LEFT JOIN gif_img_tag ti ON t.id = ti.id_tag GROUP BY t.id ORDER BY nb DESC" );
	   
	   foreach( $tagsDb as $tag ) {
		   $tags[ $tag['id'] ] = $tag;
	   }
	   
	   return array( 'success'=> true, 'tags'=> $tags );
   }
   
	function tagAction() {
		global $token, $db;
		
		$value = $_REQUEST['value'];
		$imgName = $_REQUEST['id'];
		$tag = null;
		$img = null;
		
		// get tag
		$tagsDb = $db->query( "SELECT id, label FROM gif_tag WHERE label = '".$value."'" );
		
		foreach( $tagsDb as $tag ) {};
		
		if ( $tag === null ) {
			$db->query( "INSERT INTO gif_tag (label) VALUES ('".$value."')" );
			
			$tag = array(
				'id' => $db->insert_id,
				'label' => $value
			);
		}
		
		// get img
		$imgDb = $db->query( "SELECT id FROM gif_img WHERE name = '".$imgName."'" );
		foreach( $imgDb as $img ) {};
		
		// create link
		$db->query( "INSERT INTO gif_img_tag (id_img,id_tag,date) VALUES (".$img['id'].",".$tag['id'].",NOW())" );
		
		return array('success'=>true);
	}
	
	function loginAction() {
       global $token;
        
       if ( isset($_REQUEST['username']) && $_REQUEST['username'] == 'matt' 
			&& isset($_REQUEST['password']) && $_REQUEST['password'] == 'maiden' )
           return array('success' => true, 'token' => $token);
       else
           return array('success' => false);
       
   }
     
	function listAction($noFilter = false) {
		global $token, $db, $server;
		
		$response = array('success' => true);
		$files = array();

		$isConnected = isset($_REQUEST['token']) && $_REQUEST['token'] == $token;
		$adultOnly = isset($_REQUEST['adult']) && $_REQUEST['adult'] == 'true';
		
		$sql = 'SELECT i.id, i.name, i.url, i.adult, i.details, GROUP_CONCAT(t.label) as tags, GROUP_CONCAT(s.label) as series
			FROM gif_img i 
			LEFT JOIN gif_img_tag git ON git.id_img = i.id
			LEFT JOIN gif_tag t ON t.id = git.id_tag
			LEFT JOIN gif_img_serie gis ON gis.id_img = i.id
			LEFT JOIN gif_serie s ON s.id = gis.id_serie';
			
		if ( !$noFilter ){ 
			if ( !$isConnected )
				$sql .= ' WHERE i.adult = 0';
			else if ( $adultOnly )
				$sql .= ' WHERE i.adult = 1';
		}
			
		$sql .= ' GROUP BY i.id
			ORDER BY i.id';
		
		$filesDb = $db->query($sql);
			
		foreach( $filesDb as $file ) {
			
			/*if ( !$isConnected && (bool)$file['adult'] === true )
				continue;*/
			
			$tags = array();
			$series = array();
			
			if ( $file['tags'] != null )	$tags = explode( ',', $file['tags'] );
			if ( $file['series'] != null )	$series = explode( ',', $file['series'] );
			
			$files[] = array(
				'src' => $file['url'],
				'name' => $file['name'],
				'details' => $file['details'],
				'adult' => (bool)$file['adult'],
				'tags' => $tags,
				'series' => $series,
				'comments' => array(),
				'format' => 'gif'
			);
		}
		
		$response['length'] = count($files);
		$response['files'] = $files;
		return $response;
	}
	 
   function listOldAction() {
       global $token, $db, $server;
       
       $filesDetails = array();
       $filesDb = $db->query('SELECT i.name, i.adult, i.details FROM gif_img i ORDER BY id');
              
       foreach( $filesDb as $file )
       {
           $filesDetails[$file['name']] = $file;
       }
       
       $isAdult = ( isset($_REQUEST['token']) && $_REQUEST['token'] == $token );
       
       $imgFolder = 'img';
       $response = array('success' => true);
       
       $filesRaw = scandir($imgFolder);
       $files = array();
       foreach( $filesRaw as $file )
       {
           
           if ( !is_file($imgFolder.'/'.$file) )
               continue;
           
           $name = str_replace('.gif','',$file);
           $name = str_replace('.webm','',$name);
           
           $format = 'gif';
           
           if ( strpos($file,'.webm') !== false )
               $format = 'webm';
           
           $cleanFile = array(
                'src' => $server.$imgFolder.'/'.$file,
                'name' => $name,
                'details' => null,
                'tags' => array(),
                'adult' => false,
                'series' => array(),
                'comments' => array(),
                'indb' => false,
                'format' => $format
           );
           
            if ( isset($filesDetails[$name]) ){
                $cleanFile['indb'] = true;
                foreach( $filesDetails[$name] as $key => $detail ) {
                    $cleanFile[$key] = $detail;
                }
            }
           
            $cleanFile['adult'] = ($cleanFile['adult'] == 1);
            
            if ( $cleanFile['adult'] && !$isAdult )
                continue;
           
            $files[$cleanFile['name']] = $cleanFile;
       }
       
       // get tags
	   $tagsDb = $db->query('SELECT i.name, t.id, t.label FROM gif_img_tag it LEFT JOIN gif_img i ON i.id = it.id_img LEFT JOIN gif_tag t ON t.id = it.id_tag');
	   
	   foreach( $tagsDb as $tag ) {
			if ( isset($files[$tag['name']]) )
				$files[$tag['name']]['tags'][] = $tag['label'];
	   }
	   
       // get series
       
	   $seriesDb = $db->query('SELECT i.name, s.id, s.label FROM gif_img_serie iss LEFT JOIN gif_img i ON i.id = iss.id_img LEFT JOIN gif_serie s ON s.id = iss.id_serie');
	   
	   foreach( $seriesDb as $serie ) {
			if ( isset($files[$serie['name']]) )
				$files[$serie['name']]['series'][] = $serie['label'];
	   }
	   
       $response['length'] = count($files);
       $response['files'] = $files;
       return $response;
   }
     
   function randomAction()
   {
        $response = array('success' => false, 'files' => array(), 'test' => 'plop');       
        $list = listAction();                
        $random = $list['files'][array_rand($list['files'])];
        
        $response['files'][] = $random;
        return $response;
   }
   
   function pageAction()
   {
		$page = 1;
		
		if ( isset($_REQUEST['nb'] ) )
			$page = $_REQUEST['nb'];
			
		$list = listAction();		
		$files = array_slice( $list['files'], ($page - 1) * 9 , 9, true );
		
		return array(
			'success' => true,
			'files' => $files,
			'pages' => ceil( count($list['files']) / 9)
		);
   }
   
   function adultAction()
   {
		global $db;
		
		if ( !isset($_REQUEST['id']) || !isset($_REQUEST['value']) )
			return array('success' => false);
		
		$adult = 0;
		if ( $_REQUEST['value'] == 'true' )
			$adult = 1;
		
		$db->query( 'UPDATE gif_img SET adult = '.$adult.' WHERE name = "'.$_REQUEST['id'].'"' );
		
		return array('success' => true );		
   }
   
	$db->close();
   
?>