<?php
require '/var/www/vendor/autoload.php';
$app = require '/var/www/bootstrap/app.php';

echo "=== DEBUG getNamespace() ===\n";
echo "basePath: " . $app->basePath() . "\n";
echo "path: " . $app->path() . "\n";
echo "realpath(basePath): " . (realpath($app->basePath()) ?: 'NULL') . "\n";
echo "realpath(path): " . (realpath($app->path()) ?: 'NULL') . "\n";

$composer = json_decode(file_get_contents($app->basePath('composer.json')), true);
echo "\nautoload.psr-4:\n";
foreach ((array) data_get($composer, 'autoload.psr-4') as $namespace => $path) {
    foreach ((array) $path as $pathChoice) {
        $basePathResolved = realpath($app->basePath($pathChoice)) ?: 'NULL';
        $pathResolved = realpath($app->path()) ?: 'NULL';
        $match = ($basePathResolved === $pathResolved) ? 'MATCH!' : 'no match';
        echo "  namespace=$namespace pathChoice=$pathChoice\n";
        echo "    realpath(basePath('$pathChoice')) = $basePathResolved\n";
        echo "    realpath(path())         = $pathResolved\n";
        echo "    $match\n";
    }
}
echo "\n=== END ===\n";
