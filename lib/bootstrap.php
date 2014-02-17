<?php

require_once ROOT . '/lib/Core/ClassLoader.php';
$dirs = array(
    SYS . '/Controller'
,   SYS . '/Core'
,   SYS . '/Model'
,   SYS . '/View'
,   SYS . '/Component'
,   APP . '.Config'
,   APP . '/Model'
,   APP . '/Controller'
,   APP . '/lib'
,   APP . '/Utility'
,   APP . '/View'
,   SMARTY_DIR
);
$loader = new ClassLoader();
foreach($dirs as $dir) {
    $loader->registerDir($dir);
}

$loader->register();
