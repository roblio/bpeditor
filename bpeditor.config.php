<?php
if ($_SERVER['SCRIPT_FILENAME'] != (__DIR__ .  DIRECTORY_SEPARATOR . "index.php")){
	die('It is not allowed to access this page.');
}
define('PASSWORD', 'd18fe57bb21ec0d886a3fb8782539e23ccb2608f68ae65d7f174076f940829443962291a71e6ac17c4840de5098bd526404612b43b9fa501d6c4b4b7d8447d6d');
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
