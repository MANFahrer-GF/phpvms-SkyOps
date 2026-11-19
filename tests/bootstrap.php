<?php
/**
 * Test bootstrap: boots an existing phpVMS 7 install (default ~/Claude/phpvms-7, override with PHPVMS_ROOT)
 * and loads SkyOps from THIS repository instead of the copy under phpvms/modules.
 */
$root = getenv('PHPVMS_ROOT') ?: ((getenv('HOME') ?: '') . '/Claude/phpvms-7');
if (!is_file($root . '/vendor/autoload.php')) {
    fwrite(STDERR, "PHPVMS_ROOT not found: $root\n");
    exit(2);
}
chdir($root);
$loader = require $root . '/vendor/autoload.php';
$loader->setPsr4('Modules\\SkyOps\\', dirname(__DIR__));
$GLOBALS['skyops_app'] = require $root . '/bootstrap/app.php';
$GLOBALS['skyops_app']->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
