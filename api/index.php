<?php

// The function filename is internal. Laravel must match the public URL,
// without treating /api as the directory containing its entry point.
$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['PHP_SELF'] = '/index.php';

require __DIR__.'/../public/index.php';
