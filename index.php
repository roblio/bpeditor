<?php
/*
 * BPeditor
 * https://github.com/roblio/bpeditor
 * by BradiPanda
 * Release under MIT license
 * Forked from Pheditor by Hamid Samak
 * https://github.com/hamidsamak/pheditor
 */
define('VERSION', '3.0');

require_once("bpeditor.config.php");

$CDN_codemirror = "https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.15";
$CDN_boostrap = "https://cdnjs.cloudflare.com/ajax/libs/bootstrap/4.6.2";
$CDN_jquery = "https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.4";
$CDN_jstree = "https://cdnjs.cloudflare.com/ajax/libs/jstree/3.3.15";
$CDN_izitoast = "https://cdnjs.cloudflare.com/ajax/libs/izitoast/1.4.0";
$CDN_fontawesome = "https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4";

$aboutme =  "BP editor ".VERSION." (github.com/roblio/bpeditor)\\n\\n".
	"a BradiPanda web code editor\\n".
	"Licensed under MIT license\\n".
	"Forked from Pheditor (github.com/pheditor)\\n".
	"Based on: Bootstrap (getbootstrap.com),\\n".
	"CodeMirror (codemirror.net),\\n".
	"Font Awesome (fontawesome.com),\\n".
	"iziToast (github.com/marcelodolza/iziToast,)\\n".
	"jQuery (jquery.com), Popper (popper.js.org),\\n".
	"jsTree (jstree.com), jsTree Bootstrap Theme\\n".
	"(github.com/orangehill/jstree-bootstrap-theme)";

if (empty(ACCESS_IP) === false && ACCESS_IP != $_SERVER['REMOTE_ADDR']) {
	die('Your IP address is not allowed to access this page.');
}
if (!is_dir(HISTORY_PATH)) { 
	mkdir(HISTORY_PATH ); 
}
if (!(file_exists(HISTORY_PATH . DIRECTORY_SEPARATOR . ".htaccess"))){ 
	file_put_contents(HISTORY_PATH . DIRECTORY_SEPARATOR . ".htaccess", "Require all denied"); 
}
if (!is_dir(SNIPPETS_PATH)) { 
	mkdir(SNIPPETS_PATH ); 
}
if (!(file_exists(SNIPPETS_PATH . DIRECTORY_SEPARATOR . ".htaccess"))){ 
	file_put_contents(SNIPPETS_PATH . DIRECTORY_SEPARATOR . ".htaccess", "Require all denied"); 
}
if (!file_exists(LOG_FILE)){ 
	file_put_contents(LOG_FILE, ""); 
}
if (file_exists(LOG_FILE)) {
	$log = unserialize(file_get_contents(LOG_FILE));
	if (empty($log)) {
		$log = [];
	}
	if (isset($log[$_SERVER['REMOTE_ADDR']]) && $log[$_SERVER['REMOTE_ADDR']]['num'] > 10 && time() - $log[$_SERVER['REMOTE_ADDR']]['time'] < 86400) {
		die('This IP address is blocked due to unsuccessful login attempts.');
	}
	foreach ($log as $key => $value) {
		if (time() - $value['time'] > 86400) {
			unset($log[$key]);
			$log_updated = true;
		}
	}
	if (isset($log_updated)) {
		file_put_contents(LOG_FILE, serialize($log));
	}
}

session_set_cookie_params(86400, dirname($_SERVER['REQUEST_URI']));
session_name('bpeditor');
session_start();

if (empty(PASSWORD) === false && (isset($_SESSION['bpeditor_admin']) === false || $_SESSION['bpeditor_admin'] !== true)) {
	if (isset($_POST['bpeditor_password']) && empty($_POST['bpeditor_password']) === false) {
		if (hash('sha512', $_POST['bpeditor_password']) === PASSWORD) {
			$_SESSION['bpeditor_admin'] = true;

			redirect();
		} else {
			$error = 'The entry password is not correct.';
			$log = file_exists(LOG_FILE) ? unserialize(file_get_contents(LOG_FILE)) : array();
			if (isset($log[$_SERVER['REMOTE_ADDR']]) === false) {
				$log[$_SERVER['REMOTE_ADDR']] = array('num' => 0, 'time' => 0);
			}
			$log[$_SERVER['REMOTE_ADDR']]['num'] += 1;
			$log[$_SERVER['REMOTE_ADDR']]['time'] = time();
			file_put_contents(LOG_FILE, serialize($log));
		}
	} else if (isset($_POST['action'])) {
		header('HTTP/1.0 403 Forbidden');
		die('Your session has expired.');
	}
	ob_start();
?>
<!DOCTYPE html>
<html>
	<head>
		<title>BPeditor - <?= $_SERVER['SERVER_NAME'];?></title>
		<link rel="shortcut icon" href="<?= dirname(htmlspecialchars($_SERVER["PHP_SELF"]));?>/favicon.ico" />
		<style type="text/css">
			.bp_button {
				font-family: Roboto, Verdana, sans-serif;
				font-size: 18px;
				text-align: center; 
				padding: 10px;
				border: 0;
				margin: 0;
				color: white; 
				cursor: pointer;
				border-radius: 10px; 
				background: linear-gradient(#d16ba5, #c761b0, #b75bbc, #a058ca, #7e58d9, #666ce9, #457ff6, #008fff, #00afff, #00ccff, #00e5fd, #5ffbf1);
			}
		</style>
	</head>
	<body style="background-image: url('rainbow.png');background-repeat: no-repeat;background-size: cover;">
		<div style="text-align:center">
			<br><br>
			<img src="unicorn.png" alt="BPeditor">
<?php
	if (isset($error) && $error!="") {
		echo "<p style='color:#dd0000'>" . $error . "</p>";
	}
?>
			<form method="post">
				<input id="bpeditor_password" name="bpeditor_password" type="password" value="" placeholder="Password&hellip;" tabindex="1">
				<br><br>
				<input class="bp_button" type="submit" value="&nbsp;Login&nbsp;" tabindex="2">
			</form>
			<script type="text/javascript">document.getElementById('bpeditor_password').focus();</script>
		</div>
	</body>
</html>
<?php
	$login_page = ob_get_contents();
	ob_end_clean();
	die ($login_page);
}
if (isset($_GET['logout'])) {
	unset($_SESSION['bpeditor_admin']);
	redirect();
}
$permissions = explode(',', PERMISSIONS);
$permissions = array_map('trim', $permissions);
$permissions = array_filter($permissions);
if (count($permissions) < 1) {
	$permissions = explode(',', 'newfile,newdir,editfile,deletefile,deletedir,renamefile,renamedir,changepassword,uploadfile,downloadfile');
}
if (isset($_POST['action'])) {
	if ($_POST['action'] != "download-file"){
		header('Content-Type: application/json');
	}
	if (isset($_POST['file']) && empty($_POST['file']) === false) {
		$_POST['file'] = urldecode($_POST['file']);
		if (is_file(MAIN_DIR . $_POST['file'])){
			if (empty(PATTERN_FILES) === false && !preg_match(PATTERN_FILES, basename($_POST['file']))) {
				die(json_error('Invalid file pattern for action for ' . $_POST['action']));
			}
		}else if (is_dir(MAIN_DIR . $_POST['file']) && ($_POST['action'] == 'save' || $_POST['action'] == 'download-file')){
			die(json_error('Invalid action for a directory'));
		}
	}
	foreach (['file', 'dir', 'path', 'name', 'destination'] as $value) {
		if (isset($_POST[$value]) && empty($_POST[$value]) === false) {
			$value = urldecode($_POST[$value]);
			if (strpos($value, '../') !== false || strpos($value, '..\\') !== false) {
				die(json_error('Invalid path'));
			}
		}
	}
	switch ($_POST['action']) {
		case 'open':
			//$_POST['file'] = urldecode($_POST['file']);
			if (isset($_POST['file'])) {
				if (empty(PATTERN_EDITABLES) || strval(preg_match(PATTERN_EDITABLES, basename($_POST['file']))) == '1') {
					$file = MAIN_DIR . $_POST['file'];
					if (isset($_POST['file']) && file_exists($file)) {
						die(json_success('OK', ['data' => file_get_contents($file),]));
					} else {
						die(json_success('OK', ['data' => 'File not found in the file system.',]));
					}
				} else {
					die(json_success('OK', ['data' => 'This file is not editable, but yo can download, rename, delete or backup it.',]));
				}
			}
			break;

		case 'save':
			if (isset($_POST['file'])) {
				if (!empty(PATTERN_EDITABLES) && strval(preg_match(PATTERN_EDITABLES, basename(urldecode($_POST['file'])))) != '1') {
					die(json_error('File not editable...'));
				} else {
					$file = MAIN_DIR . $_POST['file'];
					if (isset($_POST['file']) && isset($_POST['data'])){ // && (file_exists($file) === false || is_writable($file))) {
						if (file_exists($file) === false) {
							if (in_array('newfile', $permissions) !== true) {
								die(json_error('Permission denied'));
							}
							if (!preg_match("/^[a-zA-Z0-9-_.\/]+$/", $file)) {
								die(json_error('No space or special char on name please'));
							}
						} else if (is_writable($file) === false) {
							die(json_error('File is not writable'));
						} else {
							if (in_array('editfile', $permissions) !== true) {
								die(json_error('Permission denied'));
							}
						}
						if (file_put_contents($file, $_POST['data']) === false) {
							die(json_error('Error during saving of file'));
						} else {
							echo json_success('File saved successfully');
						}
					}
				}
			}
			break;

		case 'backup':
			if (isset($_POST['file'])) {
				$file = MAIN_DIR . $_POST['file'];
				if (file_exists($file) === false) {
					die(json_error('No file selected', true));
				} else if (is_readable($file) === false) {
					die(json_error('File is not readable'));
				} else {
					if (in_array('editfile', $permissions) !== true) {
						die(json_error('Permission denied'));
					}
					file_to_history($file);
					echo json_success('File backed up successfully');
				}
			}
			break;

		case 'make-dir':
			if (isset($_POST['dir']) && $_POST['dir'] != '/') {
				$dir = MAIN_DIR . $_POST['dir'];
				if (in_array('newdir', $permissions) !== true) {
					die(json_error('Permission denied'));
				} else if (file_exists($dir)) {
					die(json_error('Directory exist'));
				} else if (!is_writable(dirname($dir))) {
					die(json_error('Unable to create directory'));
				} else if (!preg_match("/^[a-zA-Z0-9-_.\/]+$/", $dir)) {
					die(json_error('No space or special char on name please'));
				} else {
					if (mkdir($dir)){
						echo json_success('Directory created successfully');
					} else {
						echo json_error('Unable to create directory');
					}
				}
			}
			break;

		case 'reload':
			echo json_success('OK', [
				'data' => files(MAIN_DIR,true),
			]);
			break;

		case 'password':
			if (in_array('changepassword', $permissions) !== true) {
				die(json_error('Permission denied'));
			}
			if (isset($_POST['password']) && empty($_POST['password']) === false) {
				$contents = file(__DIR__ .  DIRECTORY_SEPARATOR . "bpeditor.config.php");
				foreach ($contents as $key => $line) {
					if (strpos($line, 'define(\'PASSWORD\'') !== false) {
						$contents[$key] = "define('PASSWORD', '" . hash('sha512', $_POST['password']) . "');\n";
						break;
					}
				}
				if (is_writable(__DIR__ .  DIRECTORY_SEPARATOR . "bpeditor.config.php") === false) {
					die(json_error('File is not writable'));
				}
				file_put_contents(__DIR__ .  DIRECTORY_SEPARATOR . "bpeditor.config.php", implode($contents));
				echo json_success('Password changed successfully');
			}
			break;

		case 'delete':
			if (isset($_POST['path'])) {
				$path = MAIN_DIR . $_POST['path'];
				if (file_exists($path)) {
					if ($path == '/') {
						die(json_error('Unable to delete main directory'));
					}
					if (is_dir($path) && count(scandir($path)) !== 2) {
						die(json_error('Directory is not empty'));
					}
					if (is_writable($path) === false) {
						die(json_error('Unable to delete'));
					}
					if (is_dir($path)){
						if (in_array('deletedir', $permissions) !== true) {
							die(json_error('Permission denied'));
						}
						if (rmdir($path)){
							echo json_success('Directory deleted successfully');
						} else {
							die(json_error('Unable to delete directory'));
						}
					} else if (is_file($path)) {
						//file_to_history($path);
						if (in_array('deletefile', $permissions) !== true) {
							die(json_error('Permission denied'));
						}
						if (unlink($path)){
							echo json_success('File deleted successfully');
						} else {
							die(json_error('Unable to delete file'));
						}
					}
				}
			}
			break;

		case 'rename':
			if (isset($_POST['path']) && isset($_POST['name']) && empty($_POST['name']) === false) {
				$path = MAIN_DIR . $_POST['path'];
				$new_path = str_replace(basename($path), '', dirname($path)) . DIRECTORY_SEPARATOR . $_POST['name'];
				if (file_exists($path) && !file_exists($new_path)) {
					if ($_POST['path'] == '/') {
						echo json_error('Unable to rename main directory');
					} else if (is_dir($path)) {
						if (in_array('renamedir', $permissions) !== true) {
							die(json_error('Permission denied'));
						} else if (!is_writable($path)) {
							die(json_error('Unable to rename directory'));
						} else if (!preg_match("/^[a-zA-Z0-9-_.\/]+$/", $new_path)) {
							die(json_error('No space or special char on name please'));
						} else {
							if (rename($path, $new_path)){
								echo json_success('Directory renamed successfully');
							} else {
								die(json_error('Unable to rename directory'));
							}
						}
					} else {
						if (in_array('renamefile', $permissions) !== true) {
							die(json_error('Permission denied'));
						} else if (!is_writable($path)) {
							die(json_error('Unable to rename file'));
						} else if (!preg_match("/^[a-zA-Z0-9-_.\/]+$/", $new_path)) {
							die(json_error('No space or special char on name please'));
						} else if (empty(PATTERN_FILES) === false && !preg_match(PATTERN_FILES, $_POST['name'])) {
							die(json_error('Invalid file pattern: ' . htmlspecialchars($_POST['name'])));
						} else {
							//file_to_history($path);
							if (rename($path, $new_path)){
								echo json_success('File renamed successfully');
							} else {
								die(json_error('Unable to rename file'));
							}
						}
					}
				}
			}
			break;

		case 'paste':
			if (isset($_POST['path']) && isset($_POST['dir']) && isset($_POST['pasteaction'])) {
				$path = MAIN_DIR . $_POST['path'];
				$dir = MAIN_DIR . $_POST['dir'];
				if (file_exists($path) && file_exists($dir) && is_dir($dir)){
					$new_path = $dir. DIRECTORY_SEPARATOR .basename($path);
					if (in_array('renamedir', $permissions) !== true || in_array('renamefile', $permissions) !== true) {
						die(json_error('Permission denied'));
					}
					if (file_exists($new_path)) {
						die(json_error('File already exists in the destination'));
					}
					if ($_POST['pasteaction'] == "copy"){
						if (is_dir($path)){
							die(json_error('Cannot copy an entire directory'));
						}
						if (copy($path, $new_path)){
							echo json_success('File copy+pasted successfully');
						} else {
							die(json_error('Unable to copy+paste file'));
						}
					}else if ($_POST['pasteaction'] == "cut"){
						if (rename($path, $new_path)){
							echo json_success('File cut+pasted successfully');
						} else {
							die(json_error('Unable to cut+paste file'));
						}
					} else {
						die(json_error('Unkown action to paste'));
					}
				}
			}
			break;

		case 'upload-file':
			$files = isset($_FILES['uploadfile']) ? $_FILES['uploadfile'] : [];
			$destination = isset($_POST['destination']) ? rtrim($_POST['destination']) : null;
			if (empty($destination) === false && (strpos($destination, '/..') !== false || strpos($destination, '\\..') !== false)) {
				die(json_error('Invalid file destination'));
			}
			$destination = MAIN_DIR . $destination;
			if (file_exists($destination) === false || is_dir($destination) === false) {
				die(json_error('File destination does not exists'));
			}
			if (is_writable($destination) !== true) {
				die(json_error('File destination is not writable'));
			}
			if (is_array($files) && count($files) > 0) {
				for ($i = 0; $i < count($files['name']); $i += 1) {
					if (empty(PATTERN_FILES) === false && !preg_match(PATTERN_FILES, $files['name'][$i])) {
						die(json_error('Invalid file pattern: ' . htmlspecialchars($files['name'][$i])));
					}
					move_uploaded_file($files['tmp_name'][$i], $destination . '/' . $files['name'][$i]);
				}
				echo json_success('File(s) uploaded successfully on ' . $destination);
			}
			break;

		case 'download-file':
			if (isset($_POST['file']) && file_exists(MAIN_DIR . $_POST['file'])) {
				$path = MAIN_DIR . $_POST['file'];
				if (in_array('downloadfile', $permissions) !== true) {
					echo "Permission denied!";
				} else 	if (is_readable($path) === false) {
					echo "Unable to read file!";
				} else {
					header('Content-Description: File Transfer');
					header('Content-Type: application/octet-stream');
					header('Content-Disposition: attachment; filename="'.rawurlencode(basename($path)).'"');
					header('Content-Transfer-Encoding: binary');
					header('Expires: 0');
					header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
					header('Pragma: public');
					header('Content-Length: '. filesize($path)); //Absolute URL
					ob_clean();
					flush();
					readfile($path);
					exit;
				}
			}
			break;

		case 'readsnippet':
			$_POST['file'] = urldecode($_POST['file']);
			if (empty(PATTERN_EDITABLES) === true || strval(preg_match(PATTERN_EDITABLES, basename($_POST['file']))) == '1') {
				if (isset($_POST['file']) && file_exists(SNIPPETS_PATH . DIRECTORY_SEPARATOR . $_POST['file'])) {
					die(json_success('OK', [
						'data' => file_get_contents(SNIPPETS_PATH . DIRECTORY_SEPARATOR . $_POST['file']),
					]));
				} else {
					die(json_error('File snippet does not exists'));
				}
			} else {
				die(json_error('File snippet is not readable'));
			}
			break;

	}
	exit;
}

function files($dir, $first = false)
{
	$data = '';
	if ($first === true) {
		$data .= '<ul>\n<li data-jstree=\'{ "opened" : true }\'><a href="#/" class="open-dir" data-dir="/"></a>\n';
	}
	$data .= '<ul class="files">';
	$files = array_slice(scandir($dir), 2);
	$dirs_html="";
	$files_html ="";
	asort($files);
	foreach ($files as $key => $file) {
		if ((SHOW_PHP_SELF === false && $dir . DIRECTORY_SEPARATOR . $file == __FILE__) || (SHOW_HIDDEN_FILES === false && substr($file, 0, 1) === '.')) {
			continue;
		}
		if (is_dir($dir . DIRECTORY_SEPARATOR . $file) && (empty(PATTERN_DIRECTORIES) || preg_match(PATTERN_DIRECTORIES, $file))) {
			$dir_path = str_replace(MAIN_DIR . DIRECTORY_SEPARATOR, '', $dir . DIRECTORY_SEPARATOR . $file);
			$dirs_html .= "<li class='dir'><a href='#/" . $dir_path . "/' class='open-dir' data-dir='/" . $dir_path . "/'>" . $file . "</a>\n" . files($dir . DIRECTORY_SEPARATOR . $file, false) . "</li>\n";
		} else if (empty(PATTERN_FILES) || preg_match(PATTERN_FILES, $file)) {
			$file_path = str_replace(MAIN_DIR . DIRECTORY_SEPARATOR, '', $dir . DIRECTORY_SEPARATOR . $file);
			//$files_html .= "<li class='file " . (is_writable($file_path) ? "editable" : null) . "' data-jstree='{ \"icon\" : \"jstree-file\" }'>
			//<a href='#/" . $file_path . "' data-file='/" . $file_path . "' class='open-file'>" . $file . "</a></li>\n";
			$files_html .= "<li class='file " . (is_writable($file_path) ? "editable" : null) . "' data-jstree='{ \"icon\" : \"far fa-file\" }'><a href='#/" .
				str_replace(" ","%20",$file_path) . "' data-file='/" . $file_path . "' class='open-file' title='".date("Y/m/d H:i:s",filemtime($dir . DIRECTORY_SEPARATOR . $file))."'>" . $file ."</a></li>\n";
		}
	}
	$data .= $dirs_html . $files_html . '</ul>\n';
	if ($first === true) {
		$data .= '</li>\n</ul>\n';
	}
	return $data;
}

function redirect($address = null)
{
	if (empty($address)) {
		$address = htmlspecialchars($_SERVER['PHP_SELF']);
	}
	header('Location: ' . $address);
	exit;
}
/*
 * PHP: Recursively Backup Files & Folders to ZIP-File
 * (c) 2012-2014: Marvin Menzerath - http://menzerath.eu
 * contribution: Drew Toddsby
*/
function zipData($source, $destination) {
	if (extension_loaded('zip')) {
		if (file_exists($source)) {
			$zip = new ZipArchive();
			if ($zip->open($destination, ZIPARCHIVE::CREATE)) {
				$source = realpath($source);
				if (is_dir($source)) {
					$iterator = new RecursiveDirectoryIterator($source);
					// skip dot files while iterating 
					$iterator->setFlags(RecursiveDirectoryIterator::SKIP_DOTS);
					$files = new RecursiveIteratorIterator($iterator, RecursiveIteratorIterator::SELF_FIRST);
					foreach ($files as $file) {
						$file = realpath($file);
						if (is_dir($file)) {
							$zip->addEmptyDir(str_replace($source . '/', '', $file . '/'));
						} else if (is_file($file)) {
							$zip->addFromString(str_replace($source . '/', '', $file), file_get_contents($file));
						}
					}
				} else if (is_file($source)) {
					$zip->addFromString(basename($source), file_get_contents($source));
				}
				$zip->close();
				return true;
			} else {
				return false;
			}
		} else {
			return false;
		}
	} else {
		return false;
	}
}

function dir_to_snippet($dir) {
	$result = array();
	$snippets = scandir($dir);
	foreach ($snippets as $snippet){
		if (!in_array($snippet,array(".","..",".htaccess",".htpasswd"))) {
			if (is_dir($dir . DIRECTORY_SEPARATOR . $snippet)){
				echo "<li class='dropdown-submenu dropleft'>";
				echo "<a class='dropdown-item dropdown-toggle' href='#'>".$snippet."</a>\n";
				echo "<ul class='dropdown-menu'>\n";
				dir_to_snippet($dir . DIRECTORY_SEPARATOR . $snippet);
				echo "</ul>\n</li>\n";
			} else {
				echo "<li><a class='dropdown-item' href='#' onclick=\"insertsnippet('".
					substr(str_replace(SNIPPETS_PATH,"",$dir) . DIRECTORY_SEPARATOR . $snippet, 1).
					"');\">".
					str_replace("_"," ",pathinfo($snippet, PATHINFO_FILENAME))."</a></li>\n";
			} 
		}
	}
}

function file_to_history($file) {
	$file_dir = dirname($file);
	$file_name = basename($file);
	$file_history_dir = HISTORY_PATH . str_replace(MAIN_DIR, '', $file_dir);
	foreach ([HISTORY_PATH, $file_history_dir] as $dir) {
		if (file_exists($dir) === false || is_dir($dir) === false) {
			mkdir($dir, 0777, true);
		}
	}
	if (is_file($file)){
		copy($file, $file_history_dir . DIRECTORY_SEPARATOR . pathinfo($file_name, PATHINFO_FILENAME)."_".date("Ymd_Hi").".".pathinfo($file_name, PATHINFO_EXTENSION));
	} else if (is_dir($file)){
		$file_zip = basename($file) . "_" . date("Ymd_Hi") . ".zip";
		if (zipData($file,$file_history_dir . DIRECTORY_SEPARATOR . $file_zip) !== true){
			die(json_error('Unable to create zip file'));

		}
		die(json_success('Zip file created'));
	}
}

function json_error($message, $params = [])
{
	return json_encode(array_merge([
		'error' => true,
		'message' => $message,
	], $params), JSON_UNESCAPED_UNICODE);
}

function json_success($message, $params = [])
{
	return json_encode(array_merge([
		'error' => false,
		'message' => $message,
	], $params), JSON_UNESCAPED_UNICODE);
}
if (!isset($_POST['action'])) {
?><!DOCTYPE html>
<html>
	<head>
		<meta charset="UTF-8">
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<link rel="shortcut icon" href="<?= dirname(htmlspecialchars($_SERVER['PHP_SELF'])) ?>/favicon.ico" />
		<title>BPeditor - <?= $_SERVER['SERVER_NAME'];?></title>
		<link rel="stylesheet" href="<?php echo $CDN_boostrap;?>/css/bootstrap.min.css" />
		<link rel="stylesheet" href="<?php echo $CDN_jstree;?>/themes/default/style.min.css" />
		<link rel="stylesheet" href="<?php echo $CDN_codemirror;?>/codemirror.min.css" />
		<link rel="stylesheet" href="<?php echo $CDN_codemirror;?>/addon/dialog/dialog.min.css" />
		<link rel="stylesheet" href="<?php echo $CDN_codemirror;?>/addon/scroll/simplescrollbars.min.css" />
		<!--<link rel="stylesheet" href="<?php echo $CDN_codemirror;?>/addon/fold/foldgutter.min.css" />-->
<?php 
if (empty(EDITOR_THEME) === false) { 
	echo "<link rel='stylesheet' href='".$CDN_codemirror."/theme/".EDITOR_THEME.".css' />\n";
}
if (empty(JSTREE_THEME) === false && JSTREE_THEME == "jstree-bootstrap-theme"){ 
	echo "<link rel='stylesheet' href='https://cdn.jsdelivr.net/npm/jstree-bootstrap-theme@1.0.1/dist/themes/proton/style.min.css' />\n";
}
?>
		<link rel="stylesheet" href="<?php echo $CDN_izitoast;?>/css/iziToast.min.css" />
		<link rel="stylesheet" href="<?php echo $CDN_fontawesome;?>/css/all.min.css" />
		<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Roboto+Mono" /> 
		<link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Titillium+Web:400" />

		<link rel="stylesheet" href="bpeditor.css" />

		<script src="<?php echo $CDN_jquery;?>/jquery.min.js"></script>
		<script src="<?php echo $CDN_boostrap;?>/js/bootstrap.bundle.min.js"></script>
		<script src="<?php echo $CDN_jstree;?>/jstree.min.js"></script>

		<script src="<?php echo $CDN_codemirror;?>/codemirror.min.js"></script>
		<script src="<?php echo $CDN_codemirror;?>/mode/php/php.min.js"></script>
		<script src="<?php echo $CDN_codemirror;?>/mode/xml/xml.min.js"></script>
		<script src="<?php echo $CDN_codemirror;?>/mode/css/css.min.js"></script>
		<script src="<?php echo $CDN_codemirror;?>/mode/javascript/javascript.min.js"></script>
		<script src="<?php echo $CDN_codemirror;?>/mode/clike/clike.min.js"></script>
		<script src="<?php echo $CDN_codemirror;?>/mode/htmlmixed/htmlmixed.js"></script>
<!--
<link rel="stylesheet" href="<?php echo $CDN_codemirror;?>/addon/lint/lint.min.css">
<script src="<?php echo $CDN_codemirror;?>/addon/lint/lint.min.js"></script>
<script src="<?php echo $CDN_codemirror;?>/addon/hint/html-hint.min.js"></script>
<script src="<?php echo $CDN_codemirror;?>/addon/lint/html-lint.min.js"></script>
<script src="https://unpkg.com/jshint@2.13.2/dist/jshint.js"></script>
<script src="https://unpkg.com/csslint@1.0.5/dist/csslint.js"></script>
<script src="<?php echo $CDN_codemirror;?>/addon/lint/css-lint.min.js"></script>
<script src="<?php echo $CDN_codemirror;?>/addon/hint/css-hint.min.js"></script>
<script src="<?php echo $CDN_codemirror;?>/addon/hint/javascript-hint.min.js"></script>
<script src="<?php echo $CDN_codemirror;?>/addon/lint/javascript-lint.min.js"></script>
-->
		<script src="<?php echo $CDN_codemirror;?>/addon/dialog/dialog.min.js"></script>
		<script src="<?php echo $CDN_codemirror;?>/addon/search/search.min.js"></script>
		<script src="<?php echo $CDN_codemirror;?>/addon/search/searchcursor.min.js"></script>
		<script src="<?php echo $CDN_codemirror;?>/addon/search/jump-to-line.min.js"></script>
		<script src="<?php echo $CDN_codemirror;?>/addon/fold/xml-fold.min.js"></script>
		<script src="<?php echo $CDN_codemirror;?>/addon/fold/foldcode.min.js"></script>
<!--<script src="<?php echo $CDN_codemirror;?>/addon/fold/foldgutter.min.js"></script>-->
		<script src="<?php echo $CDN_codemirror;?>/addon/fold/brace-fold.min.js"></script>
		<script src="<?php echo $CDN_codemirror;?>/addon/edit/closetag.min.js"></script>
		<script src="<?php echo $CDN_codemirror;?>/addon/edit/matchtags.min.js"></script>
		<script src="<?php echo $CDN_codemirror;?>/addon/edit/matchbrackets.min.js"></script>
		<script src="<?php echo $CDN_codemirror;?>/addon/scroll/simplescrollbars.min.js"></script>
		<script src="<?php echo $CDN_izitoast;?>/js/iziToast.min.js"></script>

		<script type="text/javascript">
			function alertBox(title, message, color) {
				iziToast.show({
					title: title,
					message: message,
					color: color,
					position: "bottomRight",
					transitionIn: "fadeInUp",
					transitionOut: "fadeOutRight",
				});
			}

			function reloadFiles(hash) {
				$.post("<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>", {
					action: "reload"
				}, function(data) {
					//$("#files > div").html(data.data);
					//$("#files > div").jstree(true).refresh();
					//$("#files > div").jstree(true).save_state();
					$("#files > div").jstree("destroy");
					$("#files > div").html(data.data);
					$("#files > div").jstree({
						state: { "key": "bpeditor" },
						core : { 
							"check_callback" : true ,
<?php 
	if (empty(JSTREE_THEME) === false && JSTREE_THEME == "jstree-bootstrap-theme"){ 
?>
							themes: {
								"name" : "proton",
								"responsive" : true,
								//"variant" : "large",
							},
<?php 
	} 
?>
						},
						plugins: ["state", "search"],// "wholerow", ,"contextmenu"]
					});
					//$("#files > div").jstree(true).select_node('" + hash + "',false,false);
					//if (typeof $("#files a[data-file='" + hash + "']") !== "undefined") {
					//$("#files a[data-file='" + hash + "']").click();
					//alert("#files a[data-file='" + hash + "'] trovato");
					//}
					//if ($("#files a[data-dir='" + hash + "']") !== "undefined") {
					//$("#files a[data-dir='" + hash + "']").click();
					//alert("#files a[data-dir='" + hash + "'] trovata");
					//}
					//$("#files > div").jstree(true).restore_state();
					//			window.location.hash = hash || "/";
					//$("#files > div a:first").click();
					//$("#path").html("");
					//if (hash) {
					//hash = hash.substring(1);
					//$("#files a[data-file=\"" + hash + "\"], #files a[data-dir=\"" + hash + "\"]").click();
					//}
				});
			}

			function sha512(string) {
				return crypto.subtle.digest("SHA-512", new TextEncoder("UTF-8").encode(string)).then(buffer => {
					return Array.prototype.map.call(new Uint8Array(buffer), x => (("00" + x.toString(16)).slice(-2))).join("");
				});
			}

			function setCookie(name, value, timeout) {
				if (timeout) {
					var date = new Date();
					date.setTime(date.getTime() + (timeout * 1000));
					timeout = "; expires=" + date.toUTCString();
				} else {
					timeout = "";
				}
				document.cookie = name + "=" + encodeURIComponent(value) + timeout + "; path=/";
			}

			function getCookie(name) {
				var cookies = document.cookie.split(';');
				for (var i = 0; i < cookies.length; i++) {
					if (cookies[i].trim().indexOf(name + "=") == 0) {
						return decodeURIComponent(cookies[i].trim().substring(name.length + 1).trim());
					}
				}
				return false;
			}
			
			var editor,
					modes = {
						"php": "application/x-httpd-php",
						"css": "css",
						"js": "javascript",
						"json": "javascript",
						"htm": "text/html",
						"html": "text/html",
						//"md": "text/x-markdown"
					};
/*
	last_keyup_press = false,
	last_keyup_double = false,
	terminal_history = 1;
*/
			$(function() {
				editor = CodeMirror.fromTextArea($("#editor")[0], {
					<?php if (empty(EDITOR_THEME) === false){ ?>
					theme: "<?= EDITOR_THEME ?>",
					<?php } ?>
					lineNumbers: true,
					mode: "application/x-httpd-php",
					//mode: "text/html",
					indentUnit: 2,
					tabSize: 2,
					indentWithTabs: true,
					lineWrapping: false,
					autoCloseTags: true,
					matchTags: false, 
					matchBrackets: true,
					scrollbarStyle: "overlay",
					//gutters: ["CodeMirror-linenumbers", "CodeMirror-foldgutter"],
					//gutters: ["CodeMirror-lint-markers","CodeMirror-linenumbers", "CodeMirror-foldgutter"],
					//lint: true,
				});

				$("#files > div").jstree({
					state: { "key": "bpeditor" },
					core : { "check_callback" : true ,
									<?php if (empty(JSTREE_THEME) === false && JSTREE_THEME == "jstree-bootstrap-theme"){ ?>
									themes: {
										"name" : "proton",
										"responsive" : true,
										//"variant" : "large",
									},
									<?php };?>
								 },
					plugins: ["state", "search"],// "wholerow", ,"contextmenu"]
				});
				
				var to = false;
				$('#files_search').keyup(function () {
					if (to) { clearTimeout(to); }
					to = setTimeout(function () {
						var v = $('#files_search').val();
						$('#files > div').jstree(true).search(v);
					}, 250);
				});
/*
		$("#files").on("dblclick", "a[data-file]", function(event) {
			event.preventDefault();
<?php
	$base_dir = str_replace($_SERVER['DOCUMENT_ROOT'], '', str_replace(DIRECTORY_SEPARATOR, '/', MAIN_DIR));
	if (substr($base_dir, 0, 1) !== '/') {
		$base_dir = '/' . $base_dir;
	}
?>
			window.open("<?= $base_dir ?>" + $(this).attr("data-file"));
		});
*/
				$("a.change-password").click(function() {
					var password = prompt("Please enter new password:");
					if (password != null && password.length > 0) {
						$.post("<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>", {
							action: "password",
							password: password
						}, function(data) {
							alertBox(data.error ? "Error" : "Success", data.message, data.error ? "red" : "green");
						});
					}
				});

				$(".dropdown .new-file").click(function() {
					var path = $("#path").html();
					if (path.length > 0) {
						var name = prompt("Please enter file name:", "new-file.php"),
								end = path.substring(path.length - 1),
								file = "";

						if (name != null && name.length > 0) {
							if (end == "/") {
								file = path + name;
							} else {
								file = path.substring(0, path.lastIndexOf("/") + 1) + name;
							}

							$.post("<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>", {
								action: "save",
								file: file,
								data: ""
							}, function(data) {
								alertBox(data.error ? "Error" : "Success", data.message, data.error ? "red" : "green");

								if (data.error == false) {
									reloadFiles(window.location.hash);
								}
							});
						}
					} else {
						alertBox("Warning", "Please select a file or directory", "yellow");
					}
				});

				$(".dropdown .new-dir").click(function() {
					var path = $("#path").html();
					if (path.length > 0) {
						var name = prompt("Please enter directory name:", "new-dir"),
								end = path.substring(path.length - 1),
								dir = "";
						if (name != null && name.length > 0) {
							if (end == "/") {
								dir = path + name;
							} else {
								dir = path.substring(0, path.lastIndexOf("/") + 1) + name;
							}
							$.post("<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>", {
								action: "make-dir",
								dir: dir
							}, function(data) {
								alertBox(data.error ? "Error" : "Success", data.message, data.error ? "red" : "green");
								if (data.error == false) {
									reloadFiles(window.location.hash);
								}
							});
						}
					} else {
						alertBox("Warning", "Please select a file or directory", "yellow");
					}
				});

				$(".save").click(function() {
					var path = $("#path").html(),
							data = editor.getValue();
					if (path.length > 0) {
						sha512(data).then(function(digest) {
							$("#digest").val(digest);
						});
						$.post("<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>", {
							action: "save",
							file: path,
							data: data
						}, function(data) {
							alertBox(data.error ? "Error" : "Success", data.message, data.error ? "red" : "green");
							if (document.getElementById("previewFrame")){ 
								reloadPreviewFrame(); 
							}
						});
					} else {
						alertBox("Warning", "Please select a file", "yellow");
					}
				});

				$(".backup").click(function() {
					var path = $("#path").html(),
							data = editor.getValue();
					if (path.length > 0) {
						sha512(data).then(function(digest) {
							$("#digest").val(digest);
						});
						$.post("<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>", {
							action: "backup",
							file: path
						}, function(data) {
							alertBox(data.error ? "Error" : "Success", data.message, data.error ? "red" : "green");
							if (data.error == false) {
								reloadFiles(window.location.hash);
							}
						});
					} else {
						alertBox("Warning", "Please select a file", "yellow");
					}
				});

				$(".dropdown .close").click(function() {
					editor.setValue("");
					//$("#files > div a:first").click();
					window.location.hash = "/";
					reloadFiles(window.location.hash);
                                        $(".dropdown").find(".download-file, .save, .reopen, .close").addClass("disabled");
                                        $(".dropdown").find(".new-file, .new-dir, .upload-file, .delete, .rename, .backup, .copy, .cut").removeClass("disabled");
				});

				$(".dropdown .delete").click(function() {
					var path = $("#path").html();
					if (path.length > 0) {
						if (confirm("Do i have to delete file\n " + path + " ?")) {
							$.post("<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>", {
								action: "delete",
								path: path
							}, function(data) {
								alertBox(data.error ? "Error" : "Success", data.message, data.error ? "red" : "green");
								if (data.error == false) {
									reloadFiles(window.location.hash);
								}
							});
						}
					} else {
						alertBox("Warning", "Please select a file or directory", "yellow");
					}
				});

				$(".dropdown .rename").click(function() {
					var path = $("#path").html(),
							split = path.split("/"),
							file = split[split.length - 1],
							dir = split[split.length - 2],
							new_file_name;
					if (path.length > 0) {
						if (file.length > 0) {
							new_file_name = file;
						} else if (dir.length > 0) {
							new_file_name = dir;
						} else {
							new_file_name = "new-file";
						}
						var name = prompt("Please enter new name:", new_file_name);
						if (name != null && name.length > 0) {
							$.post("<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>", {
								action: "rename",
								path: path,
								name: name
							}, function(data) {
								alertBox(data.error ? "Error" : "Success", data.message, data.error ? "red" : "green");
								if (data.error == false) {
									reloadFiles(path.substring(0, path.lastIndexOf("/")) + "/" + name);
								}
							});
						}
					} else {
						alertBox("Warning", "Please select a file or directory", "yellow");
					}
				});

				$(".dropdown .reopen").click(function() {
					var path = $("#path").html();
					if (path.length > 0) {
						$(window).trigger("hashchange");
					}
				});

				var copypath;
				var cutpath;
				$(".dropdown .copy").click(function() {
					copypath = $("#path").html();
					cutpath = "";
					alertBox("Warning", "Copying " + $("#path").html() ,"yellow");
					$(".dropdown").find(".paste").removeClass("disabled");
				});
				$(".dropdown .cut").click(function() {
					copypath = "";
					cutpath = $("#path").html();
					alertBox("Warning", "Cuting " + $("#path").html() ,"yellow");
					$(".dropdown").find(".paste").removeClass("disabled");
				});
				$(".dropdown .paste").click(function() {
					var pasteaction;
					var oldpath;
					if ( copypath != "" ) {
						pasteaction = "copy";
						oldpath = copypath;
					} else if ( cutpath != "" ) {
						pasteaction = "cut";
						oldpath = cutpath;
					} else {
						cutpath = "";
						copypath = "";
						$(".dropdown").find(".paste").addClass("disabled");
						alertBox("Warning", "No file o directory or action selected", "yellow");
						return;
					}
					var path = $("#path").html();
					if (path.length > 0) {
						if (confirm("Do i have to " + pasteaction + " \n" + oldpath + "\nto\n" + path + " ?" )) {
							$.post("<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>", {
								action: "paste",
								pasteaction: pasteaction,
								path: oldpath,
								dir: path
							}, function(data) {
								alertBox(data.error ? "Error" : "Success", data.message, data.error ? "red" : "green");
								if (data.error == false) {
									reloadFiles(window.location.hash);
								}
							});
						}
					} else {
						alertBox("Warning", "Please select a file or directory", "yellow");
					}
					cutpath = "";
					copypath = "";
					$(".dropdown").find(".paste").addClass("disabled");
				});

				$(window).resize(function() {
					if (window.innerWidth >= 720) {
						//var terminalHeight = $("#terminal").length > 0 ? $("#terminal").height() : 0,
						//height = window.innerHeight - $(".CodeMirror")[0].getBoundingClientRect().top - terminalHeight - 30;
						height = window.innerHeight - $(".CodeMirror")[0].getBoundingClientRect().top;
						$("#files, .CodeMirror").css({
							"height": height + "px"
						});
					} else {
						$("#files > div, .CodeMirror").css({
							"height": ""
						});
					}
					if (document.fullscreen) {
						$("#prompt pre").height($(window).height() - $("#prompt input.command").height() - 20);
					}
				});
				$(window).resize();
/*
		$(document).bind("keyup", function(event) {
			if ((event.ctrlKey || event.metaKey) && event.shiftKey) {
				if (event.keyCode == 78) {
					$(".dropdown .new-file").click();
					event.preventDefault();
					return false;
				} else if (event.keyCode == 83) {
					$(".dropdown .save").click();
					event.preventDefault();
					return false;
				} else if (event.keyCode == 76) {
					//$("#terminal .toggle").click();
					event.preventDefault();
					return false;
				}
			}
		});
		$(document).bind("keyup", function(event) {
			if (event.keyCode == 27) {
				if (last_keyup_press == true) {
					last_keyup_double = true;
					$("#fileMenu").click();
					$("body").focus();
				} else {
					last_keyup_press = true;
					setTimeout(function() {
						if (last_keyup_double === false) {
							if (document.activeElement.tagName.toLowerCase() == "textarea") {
								//if ($("#terminal #prompt").hasClass("show")) {
									//$("#terminal .command").focus();
								//} else {
									$(".jstree-clicked").focus();
								//}
							} else if (document.activeElement.tagName.toLowerCase() == "input") {
								$(".jstree-clicked").focus();
							} else {
								editor.focus();
							}
						}
						last_keyup_press = false;
						last_keyup_double = false;
					}, 250);
				}
			}
		});
*/
				$(window).on("hashchange", function() {
					var hash = window.location.hash.substring(1); //.replace(" ","%20");
					data = editor.getValue();
					if (hash.length > 0) {
						sha512(data).then(function(digest) {
							if ($("#digest").val().length < 1 || $("#digest").val() == digest) {
								if (hash.substring(hash.length - 1) == "/") {
									var dir = $("a[data-dir='" + hash + "']");
									if (dir.length > 0) {
										editor.setValue("");
										$("#digest").val("");
										$("#path").html(hash);
										$("#file-to-download").val(hash);
										$(".dropdown").find(".download-file, .save, .reopen").addClass("disabled");
										$(".dropdown").find(".new-file, .new-dir, .upload-file, .delete, .rename, .backup, .copy, .cut, .close").removeClass("disabled");
									}
								} else {
									var file = $("a[data-file='" + hash + "']");
									if (file.length > 0) {
										$("#file-to-download").val(hash);
										$(".dropdown").find(".new-file, .new-dir, .upload-file, .save, .backup, .reopen, .close").addClass("disabled");
										$(".dropdown").find(".download-file, .delete, .rename, .copy, .cut").removeClass("disabled");
										$("#loading").fadeIn(250);
										$.post("<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>", {
											action: "open",
											file: encodeURIComponent(hash)
										}, function(data) {
											if (data.error == true) {
												alertBox("Error", data.message, "red");
												return false;
											}
											editor.setValue(data.data);
											editor.setOption("mode", "application/x-httpd-php");
											sha512(data.data).then(function(digest) {
												$("#digest").val(digest);
											});
											if (hash.lastIndexOf(".") > 0) {
												var extension = hash.substring(hash.lastIndexOf(".") + 1);
												if (modes[extension]) {
													editor.setOption("mode", modes[extension]);
												}
											}
											$("#editor").attr("data-file", hash);
											$("#path").html(hash).hide().fadeIn(250);
											$(".dropdown").find(".save, .backup, .reopen, .close").removeClass("disabled");
											$("#loading").fadeOut(250);
											//if (document.getElementById("previewFrame")){ 
											//resizePreviewFrame(document.getElementById("selectWidthPreview").value);
											//}
										});
									}
								}
							} else if (confirm("Discard changes?")) {
								$("#digest").val("");
								$(window).trigger("hashchange");
							}
						});
					}
				});

				if (window.location.hash.length < 1) {
					window.location.hash = "/";
				} else {
					$(window).trigger("hashchange");
				}

				$("#files").on("click", ".jstree-anchor", function() {
					location.href = $(this).attr("href");
				});

				$(document).ajaxError(function(event, request, settings) {
					var message = "An error occurred with this request.";
					if (request.responseText.length > 0) {
						message = request.responseText;
					}
					if (confirm(message + " Do you want to reload the page?")) {
						location.reload();
					}
					$("#loading").fadeOut(250);
				});
/*
		$(window).keydown(function(event) {
			if ($("#fileMenu[aria-expanded='true']").length > 0) {
				var code = event.keyCode;
				if (code == 78) {
					$(".new-file").click();
				} else if (code == 83) {
					$(".save").click();
				} else if (code == 68) {
					$(".delete").click();
				} else if (code == 82) {
					$(".rename").click();
				} else if (code == 79) {
					$(".reopen").click();
				} else if (code == 67) {
					$(".close").click();
				} else if (code == 85) {
					$(".upload-file").click();
				}
			}
		});
*/

				$(".dropdown .upload-file").click(function() {
					$("#uploadFileModal").modal("show");
					$("#uploadfile").val("");
					$("#uploadFileModal input").focus();
				});
				$("#uploadFileModal #upload-button").click(function() {
					var form = $(this).closest("form"),
							formdata = false;
					form.find("input[name=destination]").val(window.location.hash.substring(1));
					if (window.FormData) {
						formdata = new FormData(form[0]);
					}
					$.ajax({
						url: "<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>",
						data: formdata ? formdata : form.serialize(),
						cache: false,
						contentType: false,
						processData: false,
						type: "POST",
						success: function(data, textStatus, jqXHR) {
							alertBox(data.error ? "Error" : "Success", data.message, data.error ? "red" : "green");
							if (data.error == false) {
								reloadFiles(window.location.hash);
							}
						}
					});
				});

				$(window).on("fullscreenchange", function() {
					if (document.fullscreenElement == null) {
						$(window).resize();
					}
				});
			});

			function insertsnippet(path){
				if (path.length > 0) {
					var data = '';
					$.post("<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>", {
						action: "readsnippet",
						file: path,
						data: data
					}, function(data) {
						if (data.error == true) {
							alertBox("Error", data.message, "red");
							return false;
						}
						editor.replaceSelection(data.data);
					});
				} else {
					alertBox("Warning", "Please select a snippet", "yellow");
				}
			}

			function switchTheme(){
				if (editor.getOption('theme') == '<?php echo EDITOR_THEME;?>'){
					editor.setOption('theme','default');
				} else {
					editor.setOption('theme','<?php echo EDITOR_THEME;?>');
				}
			}
		</script>
	</head>

	<body>
		<div id="filesPanel" class="filesPanel">
			<div class="row p-0 m-0 mh-100 no-gutters">
				<div class="col-12 p-1">
					<div class="input-group">
						<div class="input-group-prepend">
							<span class="input-group-text">Search me...</span>
						</div>
						<input id="files_search" type="text" class="form-control imput-sm">
					</div>
				</div>

				<div class="w-100"></div>

				<div class="col-12 p-1">
					<div id="files" class="card" >
						<div class="card-block" style="font-size: larger;"><?= files(MAIN_DIR,true) ?></div>
					</div>
				</div>
			</div>
		</div>
		<div id="main" >
			<div class="row p-0 m-0 mh-100 no-gutters">
				<div class="d-flex col-12 p-1">
					<div class="inlb" style="font-size: 4px; padding: 0px; margin: 0px;">
						<button id="filesHamburger" type="button" class="btn btn-dark inlb" onclick="openFilesPanel()" data-toggle="tooltip" data-placement="bottom" 
										title="Open/Close file explorer">&#9776;</button>
						<div class="dropdown inlb">
							<button class="btn btn-primary dropdown-toggle"  type="button" id="fileMenu" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">File</button>
							<div class="dropdown-menu" aria-labelledby="fileMenu">
								<a class="dropdown-item new-file" href="javascript:void(0);">New File <span class="float-right text-secondary"></span></a>
								<a class="dropdown-item new-dir" href="javascript:void(0);">New Directory</a>
								<a class="dropdown-item upload-file" href="javascript:void(0);">Upload File <span class="float-right text-secondary"></span></a>
								<a class="dropdown-item download-file disabled" href="#" onclick="document.getElementById('post-download').submit();">Download <span class="float-right text-secondary"></span></a>
								<div class="dropdown-divider"></div>
								<a class="dropdown-item delete disabled" href="javascript:void(0);">Delete <span class="float-right text-secondary"></span></a>
								<a class="dropdown-item rename disabled" href="javascript:void(0);">Rename <span class="float-right text-secondary"></span></a>
								<div class="dropdown-divider"></div>
								<a class="dropdown-item copy disabled" href="javascript:void(0);">Copy <span class="float-right text-secondary"></span></a>
								<a class="dropdown-item cut disabled" href="javascript:void(0);">Cut <span class="float-right text-secondary"></span></a>
								<a class="dropdown-item paste disabled" href="javascript:void(0);">Paste <span class="float-right text-secondary"></span></a>
								<div class="dropdown-divider"></div>
								<a class="dropdown-item save disabled" href="javascript:void(0);">Save <span class="float-right text-secondary"></span></a>
								<a class="dropdown-item backup disabled" href="javascript:void(0);">Backup <span class="float-right text-secondary"></span></a>
								<a class="dropdown-item reopen disabled" href="javascript:void(0);">Re-open <span class="float-right text-secondary"></span></a>
								<a class="dropdown-item close disabled" href="javascript:void(0);">Close <span class="float-right text-secondary"></span></a>
							</div>
						</div>
<?php if (in_array('newfile', $permissions) || in_array('editfile', $permissions)) { ?>
						<button type="button" class="backup btn btn-warning inlb" data-toggle="tooltip" data-placement="bottom" title="Save a copy in <?php echo HISTORY_PATH;?>">Backup me?</button>
						<button type="button" class="save btn btn-danger inlb" data-toggle="tooltip" data-placement="bottom" title="Save editor content">Save me!</button>
<?php } ?>
						<button type="button" id="path" class="btn btn-success inlb" onclick="window.open(this.textContent || this.innerText, '_blank');" data-toggle="tooltip" data-placement="bottom" title="View this file through the web">/</button>
					</div>
					<div class="inlb flex-grow-1" style="font-size: 4px; padding: 0px 1px 0px 1px; margin: 0px;">
						<button type="button" class="btn btn-info inlb rainbow-bar" data-toggle="tooltip" data-placement="bottom">&nbsp;</button>
					</div>
					<div class="dropdown inlb">
						<img src="unicorn-icon.png" class="inlb" style="vertical-align: middle; cursor: pointer;" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" data-toggle="tooltip" data-placement="bottom" title="BPeditor">
						<div class="dropdown-menu dropdown-menu-center" aria-labelledby="dropdownMenuButton">
<?php if (in_array('changepassword', $permissions)) { ?>
							<a href="javascript:void(0);" class="change-password dropdown-item">Change my password</a>
<?php } ?>
							<a class="dropdown-item" href="#" onclick="window.location.href='<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>?logout=1'">Logout me</a>
							<div class="dropdown-divider"></div>
							<a class="dropdown-item" href="https://www.w3schools.com/html/" target="_blank">HTML Tutorial</a>
							<a class="dropdown-item" href="https://www.w3schools.com/css/" target="_blank">CSS Tutorial</a>
							<a class="dropdown-item" href="https://www.w3schools.com/js/" target="_blank">JavaScript Tutorial</a>
							<a class="dropdown-item" href="https://www.w3schools.com/php/" target="_blank">PHP Tutorial</a>
							<a class="dropdown-item" href="#" onClick="openVideoPanel();">Video Tutorials</a>
							<!--<a class="dropdown-item" href="https://www.medicalnewstoday.com/articles/321536" target="_blank">About 20-20-20 rule</a>-->
							<div class="dropdown-divider"></div>
							<a class="dropdown-item" href="#" onclick="alert('<?= $aboutme?>');">About BP editor</a>
						</div>
					</div>
					<div class="inlb flex-grow-1" style="font-size: 4px; padding: 0px 1px 0px 1px; margin: 0px;">
						<button type="button" class="btn btn-info inlb rainbow-bar" data-toggle="tooltip" data-placement="bottom">&nbsp;</button>
					</div>
					<div class="float-right" style="font-size: 4px; padding: 0px; margin: 0px;">
						<button type="button" class="btn btn-info inlb" onclick="editor.execCommand('selectAll');editor.execCommand('indentAuto');" 
										data-toggle="tooltip" data-placement="bottom" title="Auto indent editor content"><i class="fas fa-indent" alt="Auto indent all page"></i></button>
						<div class="dropdown inlb">
							<button class="btn btn-info dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">View</button>
							<div class="dropdown-menu dropdown-menu-right" aria-labelledby="dropdownMenuButton">
								<a class="dropdown-item" href="#" onclick="editor.execCommand('selectAll');">Select all</a>
								<div class="dropdown-divider"></div>
								<a class="dropdown-item" href="#" onclick="editor.execCommand('indentAuto');">Indent selection</a>
								<a class="dropdown-item" href="#" onclick="editor.execCommand('indentLess');"><i class="fas fa-angle-double-left" alt="Indent left"></i> Indent left</a>
								<a class="dropdown-item" href="#" onclick="editor.execCommand('indentMore');"><i class="fas fa-angle-double-right" alt="Indent right"></i> Indent right</a>
								<div class="dropdown-divider"></div>
								<a class="dropdown-item" href="#" onclick="editor.setOption('matchTags',{bothTags: !(editor.getOption('matchTags')['bothTags'])});editor.setOption('matchBrackets', !editor.getOption('matchBrackets') );editor.refresh();">Show/hide match</a>
								<!--<a class="dropdown-item" href="#" onclick="editor.setOption('foldGutter', !editor.getOption('foldGutter'));editor.refresh();">Show/hide fold</a>-->
								<!--<a class="dropdown-item" href="#" onclick="editor.setOption('foldGutter',true);editor.refresh();editor.execCommand('foldAll');">Fold all</a>-->
								<!--<a class="dropdown-item" href="#" onclick="editor.setOption('foldGutter',false);editor.refresh();editor.execCommand('unfoldAll');">Unfold all</a>-->
								<div class="dropdown-divider"></div>
								<a class="dropdown-item" href="#" onclick="switchTheme();">Light/Dark theme</a>
							</div>
						</div>
						<div class="dropdown inlb">
							<button class="btn btn-info dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">Edit</button>
							<div class="dropdown-menu dropdown-menu-right" aria-labelledby="dropdownMenuButton">
								<a class="dropdown-item" href="#" onclick="editor.execCommand('undo');"><i class="fas fa-undo" alt="Undo"></i> Undo</a>
								<a class="dropdown-item" href="#" onclick="editor.execCommand('redo');"><i class="fas fa-redo" alt="Redo"></i> Redo</a>
								<div class="dropdown-divider"></div>
								<a class="dropdown-item" href="#" onclick="editor.execCommand('jumpToLine');">Go to line</a>
								<a class="dropdown-item" href="#" onclick="editor.execCommand('findPersistent');">Find</a>
								<a class="dropdown-item" href="#" onclick="editor.execCommand('replace');">Replace</a>
								<a class="dropdown-item" href="#" onclick="editor.execCommand('replaceAll');">Replace all</a>
							</div>
						</div>
						<div class="dropdown inlb">
							<button class="btn btn-info dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">Snippets</button>
							<div class="dropdown-menu dropdown-menu-right" aria-labelledby="dropdownMenuButton">
<?php
dir_to_snippet(SNIPPETS_PATH);
?>
							</div>
						</div>
						<div class="dropdown inlb">
							<button class="btn btn-info dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" title="Zoom editor content">
								<i class="fas fa-text-height"></i></button>
							<div class="dropdown-menu" aria-labelledby="dropdownMenuButton" style="text-align: center;">
								<input id="font-slider" type="range" class="form-control-range inlb align-middle" data-placement="bottom" style="width: 120px;" min="12" max="24" value="18">
							</div>
						</div>
						<button id="previewHamburger" type="button" class="btn btn-dark inlb" onclick='openPreviewPanel()' data-toggle="tooltip" data-placement="bottom" 
										title="Open/Close preview frame">&#9776;</button>
					</div>
				</div>

				<div class="w-100"></div>

				<div class="col-12 p-0 m-0">
					<div id="loading">
						<div class="lds-ring">
							<div></div>
							<div></div>
							<div></div>
						</div>
					</div>
					<textarea id="editor" data-file="" class="form-control"></textarea>
					<input id="digest" type="hidden" readonly>
				</div>
			</div>
		</div>

		<div class="previewPanel" id="previewPanel" name="previewPanel">
			<div class="row p-0 m-0 mh-100 no-gutters">
				<div class="flex col-12 p-0" id="previewBar" name="previewBar" style="font-size: 4px; padding: 2px; margin: 0px;">
					<select type="button" class="btn btn-light inlb" style="text-align:left;" name="selectWidthPreview" id="selectWidthPreview" onChange="resizePreviewFrame(this.value)">
						<option value="482px">XS screen(480px)</option>
						<option value="578px">Small screen (576px)</option>
						<option value="770px">Medium screen (768px)</option>
						<option value="994px" selected>Large screen (992px)</option>
						<option value="1202px">XL screen (1200px)</option>
						<option value="1402px">XXL screen (1400px)</option>   
						<option value="1922px">FullHD screen (1920px)</option> 
					</select>
<!--
					<div class="dropdown inlb">
						<button class="btn btn-light dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">Check me</button>
						<div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
							<a class="dropdown-item" href="#" onclick="window.open('https://validator.w3.org/nu/?doc=<?php echo urlencode("https://".$_SERVER["SERVER_NAME"]);?> +	document.getElementById('path').innerText + '&showsource=yes&showoutline=yes', '_blank')">W3C HTML checker</a>
							<a class="dropdown-item" href="#" onclick="window.open('https://jigsaw.w3.org/css-validator/validator?uri=<?php echo urlencode("https://".$_SERVER["SERVER_NAME"]);?> + document.getElementById('path').innerText + '&profile=css3svg&usermedium=all&warning=1&vextwarning=', '_blank')">W3C CSS validator</a>
							<a class="dropdown-item" href="#" onclick="window.open('https://pagespeed.web.dev/report?form_factor=desktop&url=<?php echo urlencode("https://".$_SERVER['SERVER_NAME']);?> + document.getElementById('path').innerText + '&profile=css3svg&usermedium=all&warning=1&vextwarning=', '_blank')">Google PageSpeed Insights</a>
						</div>
					</div>
-->
					<button type="button" class="btn btn-light inlb" onclick="document.getElementById('previewFrame').contentWindow.history.back();" 
									title="Preview history back" data-toggle="tooltip" data-placement="bottom" title=""><i class="fas fa-arrow-left"></i></button>

					<button type="button" class="btn btn-light inlb" onclick="document.getElementById('previewFrame').contentWindow.history.forward();" 
									title="Preview istory forward" data-toggle="tooltip" data-placement="bottom" title=""><i class="fas fa-arrow-right"></i></button>

					<button type="button" class="btn btn-light inlb" onclick="reloadPreviewFrame()" 
									title="Reload preview window" data-toggle="tooltip" data-placement="bottom"><i class="fas fa-sync"></i></button>

					<button type="button" class="btn btn-light inlb" id="previewPath" name="previewPath" style="pointer-events:none;"  
									title="" data-toggle="tooltip" data-placement="bottom"></button>
				</div>
				<div class="w-100"></div>
				<div class="col-12 p-0 m-0" id="previewDiv" name="previewDiv"></div>
			</div>
		</div>

		<form method="post" enctype="multipart/form-data" style="padding: 0; margin: 0;">
			<input name="action" type="hidden" value="upload-file">
			<input name="destination" type="hidden" value="">
			<div class="modal" id="uploadFileModal">
				<div class="modal-dialog">
					<div class="modal-content">
						<div class="modal-header">
							<h4 class="modal-title">Upload File</h4>
							<button type="button" class="close" data-dismiss="modal">&times;</button>
						</div>
						<div class="modal-body">
							<div>
								<input name="uploadfile[]" id="uploadfile" type="file" value="" multiple>
							</div>
<?php
if (function_exists('ini_get')) {
	$sizes = [ini_get('post_max_size'),ini_get('upload_max_filesize')];
	$max_size = max($sizes);
	echo '<small class="text-muted">Maximum file size: ' . $max_size . '</small>';
}
?>
						</div>
						<div class="modal-footer">
							<button id="upload-button" type="button" class="btn btn-success" data-dismiss="modal">Upload</button>
						</div>
					</div>
				</div>
			</div>
		</form>
		<form method="post" id="post-download" target="_blank" style="padding: 0p; margin: 0;">
			<input name="destination" type="hidden" value="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>">
			<input name="action" type="hidden" value="download-file">
			<input id="file-to-download" name="file" type="hidden" value="">
		</form>
		<script src="bpeditor.js"></script>
	</body>
</html>
<?php }?>
