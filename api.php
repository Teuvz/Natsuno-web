<?php
      
	$config = parse_ini_file( 'data.ini', true );
   
    $db = mysqli_connect($config['database']['server'],$config['database']['user'],$config['database']['pass'],$config['database']['database']);
    $token = $config['users']['token'];
	
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
   
   function shareAction() {
	   global $token, $db;
	   
	   if ( !isset($_REQUEST['token']) || $_REQUEST['token'] != $token )
			return array('success' => false, 'error' => 'false or missing token' );
		
		$sender = $_REQUEST['sender'];
		$receiver = $_REQUEST['receiver'];
		$gif = $_REQUEST['gif'];
		
		$filesDb = $db->query( 'SELECT id FROM gif_img WHERE name = "'.$gif.'"' );
		
		foreach( $filesDb as $file ){};
				
		$db->query( 'INSERT INTO gif_share (sender,receiver,id_gif,date) VALUES ("'.$sender.'","'.$receiver.'",'.$file['id'].',NOW())' );
		
		return array('success' => true, 'share' => $db->insert_id );
   }
   
   function getsharedAction() {
		global $token, $db;
	   
		if ( !isset($_REQUEST['token']) || $_REQUEST['token'] != $token )
			return array('success' => false, 'error' => 'false or missing token' );
		
		$response = array('success' => true);
		$user = $_REQUEST['user'];
		
		$files = array();
		$filesDb = $db->query( 'SELECT s.id, s.sender, s.date, i.name, i.url, i.details, i.adult FROM gif_share s LEFT JOIN gif_img i ON i.id = s.id_gif WHERE receiver = "'.$user.'" ORDER BY date DESC LIMIT 1' );
		
		foreach( $filesDb as $file ) {
			
			$alt = 'http://www.matthewpcharlton.com/gifs/image?'.$file['name'];
			
			if ( $file['adult'] == 1 ) {
				$hashString = $file['name'] . $file['url'] . 'true';
					
				if ( $file['details'] == null )
					$hashString .= 'null';
				else
					$hashString .= $file['details'];
				
				$hash = base64_encode( $hashString );
				
				$alt .= '&hash='.$hash;
			}
			
			$files[] = array(
				'src' => $file['url'],
				'name' => $file['name'],
				'alt' => $alt,
				'details' => $file['details'],
				'adult' => (bool)$file['adult'],
				'tags' => array(),
				'series' => array(),
				'comments' => array(),
				'format' => 'gif'
			);
		}
		
		$response['files'] = $files;
	   
	   return $response;
   }
   
   function removeAction() {
	   global $token, $db;
	   
	   if ( !isset($_REQUEST['token']) || $_REQUEST['token'] != $token )
			return array('success' => false, 'error' => 'false or missing token' );
		
		$db->query('DELETE FROM gif_img WHERE name = "'.$_REQUEST['id'].'"');
	   
	   return array('success' => true );
   }
   
   function downloadAction() {
		global $token, $db;
       
		if ( !isset($_REQUEST['token']) || $_REQUEST['token'] != $token )
			return array('success' => false, 'error' => 'false or missing token' );
    
		$url = $_REQUEST['url'];
       	
		$counter = $db->query('SELECT MAX(name) as nb FROM gif_img');
		$nbImages = null;
		
		foreach( $counter as $iCounter )
			$nbImages = (int)$iCounter['nb'];			
       
	   $nbImages += 1;
	   $nameFile = (string) $nbImages;
	   
		while( strlen($nameFile) < 5 )
			$nameFile = '0'.$nameFile;
        
		if ( isset($_REQUEST['adult']) && $_REQUEST['adult'] == 'true' )
			$adult = 1;
		else
			$adult = 0;
				
		$db->query( "INSERT INTO gif_img (name,url,adult) VALUES ('".$nameFile."','".$url."',".$adult.")" );
		$img = $db->insert_id;
		
		if ( isset($_REQUEST['live']) && $_REQUEST['live'] == 'true' ){
			$tagLive = null;
			$tagsDb = $db->query('SELECT id, label FROM gif_tag WHERE label = "live"');
			foreach( $tagsDb as $tagLive ){}; 
			
			$db->query( 'INSERT INTO gif_img_tag (id_img,id_tag,date) VALUES ('.$img.','.$tagLive['id'].',NOW())' );
		}
		
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
		
		$seriesDb = $db->query( "SELECT id, label FROM gif_serie WHERE label = '".$value."'" );
		
		foreach( $seriesDb as $serie ) {};
		
		if ( $serie === null ) {
			$db->query( "INSERT INTO gif_serie (label) VALUES ('".$value."')" );
			
			$serie = array(
				'id' => $db->insert_id,
				'label' => $value
			);
		}
		
		$imgDb = $db->query( "SELECT id FROM gif_img WHERE name = '".$imgName."'" );		
		foreach( $imgDb as $img ) {};
		
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
		
		$tagsDb = $db->query( "SELECT id, label FROM gif_tag WHERE label = '".$value."'" );
		
		foreach( $tagsDb as $tag ) {};
		
		if ( $tag === null ) {
			$db->query( "INSERT INTO gif_tag (label) VALUES ('".$value."')" );
			
			$tag = array(
				'id' => $db->insert_id,
				'label' => $value
			);
		}
		
		$imgDb = $db->query( "SELECT id FROM gif_img WHERE name = '".$imgName."'" );
		foreach( $imgDb as $img ) {};
		
		$db->query( "INSERT INTO gif_img_tag (id_img,id_tag,date) VALUES (".$img['id'].",".$tag['id'].",NOW())" );
		
		return array('success'=>true);
	}
	
	function loginAction() {  
	   global $token, $config;
        
		if ( !isset($_REQUEST['username']) || $_REQUEST['username'] == '' || !isset($_REQUEST['password']) || $_REQUEST['password'] == '' )
			return array('success' => false);
		
		$username = $_REQUEST['username'];
		$password = $_REQUEST['password'];
		
		$configUsers = explode( ',', $config['users']['names'] );
		$configPasswords = explode( ',', $config['users']['passwords'] );
		
		if ( in_array($username,$configUsers,true) && in_array($password,$configPasswords,true) )
			return array('success' => true, 'token' => $token);
		else
			return array('success' => false);       
   }
     
	function listAction($noFilter = false) {
		global $token, $db, $server;
		
		$response = array('success' => true);
		$files = array();

		$isConnected = isset($_REQUEST['token']) && $_REQUEST['token'] == $token;
		
		if ( !isset($_REQUEST['adult']) )
			$adultOnly = 0;
		else
			$adultOnly = $_REQUEST['adult'];
		
		$sql = 'SELECT i.id, i.name, i.url, i.adult, i.details, GROUP_CONCAT(t.label) as tags, GROUP_CONCAT(s.label) as series
			FROM gif_img i 
			LEFT JOIN gif_img_tag git ON git.id_img = i.id
			LEFT JOIN gif_tag t ON t.id = git.id_tag
			LEFT JOIN gif_img_serie gis ON gis.id_img = i.id
			LEFT JOIN gif_serie s ON s.id = gis.id_serie';
			
		if ( !$noFilter ){ 
		
			$sql .= ' WHERE 1';
		
			if ( !$isConnected || $adultOnly == 1 )
				$sql .= ' AND i.adult = 0';
			else if ( $isConnected && $adultOnly == 2 )
				$sql .= ' AND i.adult = 1';
			
			if ( isset($_REQUEST['tag']) && $_REQUEST['tag'] != "" )
			{
				$tag = $_REQUEST['tag'];
				$sql .= " AND t.label = '".$tag."'";				
			}
			
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
			
			$alt = 'http://www.matthewpcharlton.com/gifs/image?'.$file['name'];
			
			if ( $file['adult'] == 1 ) {
				$hashString = $file['name'] . $file['url'] . 'true';
					
				if ( $file['details'] == null )
					$hashString .= 'null';
				else
					$hashString .= $file['details'];
				
				$hash = base64_encode( $hashString );
				
				$alt .= '&hash='.$hash;
			}
			
			$files[] = array(
				'src' => $file['url'],
				'name' => $file['name'],
				'alt' => $alt,
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
     
   function randomAction()
   {
        $response = array('success' => true, 'files' => array(), 'test' => 'plop');       
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
		global $db, $token;
		
		if ( !isset($_REQUEST['id']) || !isset($_REQUEST['value']) || !isset($_REQUEST['token']) || $_REQUEST['token'] != $token )
			return array('success' => false);
		
		$adult = 0;
		if ( $_REQUEST['value'] == 'true' )
			$adult = 1;
		
		$db->query( 'UPDATE gif_img SET adult = '.$adult.' WHERE name = "'.$_REQUEST['id'].'"' );
		
		return array('success' => true );		
   }
   
   function bananaAction()
   {
		$banana = file_get_contents('banana.txt');
		echo $banana;
		exit();
   }
   
	$db->close();
   
?>