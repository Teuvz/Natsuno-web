<?php
    $data = file_get_contents('http://www.matthewpcharlton.com/gifs/api?random');
    $data = json_decode($data,true);
    $data = $data['img'];
?>
<html>

    <head>
        <title>Random GIF</title>
    </head>
    
    <body>
        <img src="<?php echo $data['src']; ?>" /><br />
        <span><?php echo $data['name']; ?></span><br />
        <a href="<?php echo $data['src']; ?>" target="_blank"><button>open in new tab</button></a>
        
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.1.1/jquery.min.js"></script>
        <script>
            console.log('hello');
            $('img').on('load', function() {
                console.log('image loaded, reload in 3s');
                setTimeout( function() { console.log('good bye');location.reload(); }, 3000 );
            });
        </script>
        
    </body>
    
</html>