<?php
header('Content-Type: text/plain; charset=UTF-8');

echo "PHP_VERSION=" . PHP_VERSION . "\n";
echo "PDO=" . (extension_loaded('pdo') ? 'yes' : 'no') . "\n";
echo "PDO_MYSQL=" . (extension_loaded('pdo_mysql') ? 'yes' : 'no') . "\n";
echo "OPENSSL=" . (extension_loaded('openssl') ? 'yes' : 'no') . "\n";
