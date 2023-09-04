<?php
if ($_SERVER['SCRIPT_FILENAME'] != (__DIR__ .  DIRECTORY_SEPARATOR . "index.php")){
	die('It is not allowed to access this page.');
}
define('PASSWORD', 'c7ad44cbad762a5da0a452f9e854fdc1e0e7a52a38015f23f3eab1d80b931dd472634dfac71cd34ebc35d16ab7fb8a90c81f975113d6c7538dc69dd8de9077ec');
define('MAIN_DIR','/var/www/html');
define('SHOW_PHP_SELF', false);
define('SHOW_HIDDEN_FILES', false);
define('ACCESS_IP', '');
define('LOG_FILE', MAIN_DIR . DIRECTORY_SEPARATOR . '.bplog');
define('HISTORY_PATH', MAIN_DIR . DIRECTORY_SEPARATOR . 'AAA_backup-me');
define('SNIPPETS_PATH', MAIN_DIR . DIRECTORY_SEPARATOR . 'AAA_snippets');

define('PERMISSIONS', 'newfile,newdir,editfile,deletefile,deletedir,renamefile,renamedir,uploadfile,downloadfile,changepassword'); // empty means all
#define('PATTERN_FILES', '/^[A-Za-z0-9-_.\/]*\.(txt|php|htm|html|js|css|scss|tpl|md|xml|json|log|inc|ico|pdf|jpg|png|gif|eot|ttf|woff|woff2|svg|zip|gz|mdb)$/i'); // empty means no pattern
define('PATTERN_FILES', '');
define('PATTERN_EDITABLES', '/^[A-Za-z0-9-_.\/]*\.(txt|php|htm|html|js|css|scss|tpl|md|xml|json|log|inc)$/i');
define('PATTERN_DIRECTORIES', '/^(.)*$/i'); // empty means no pattern

define('EDITOR_THEME', 'monokai'); // e.g. monokai seti
define('JSTREE_THEME', 'jstree-bootstrap-theme'); 

?>
