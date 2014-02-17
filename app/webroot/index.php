<?php
define('ROOT', dirname(dirname(dirname(__FILE__))));
define('APP_DIR', basename(dirname(dirname(__FILE__))));
define('APP', ROOT. '/' . APP_DIR);
define('WEBROOT_DIR', 'webroot');
define('WEB_ROOT', APP . '/' . WEBROOT_DIR );
define('SYS', ROOT . '/lib');
define('SMARTY_DIR', SYS.'/View/Smarty/libs/');

require SYS.'/bootstrap.php';

$application = Application::getInstance(false);
$application->run();