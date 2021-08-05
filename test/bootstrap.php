<?php
// Kronolith Unit Test Bootstrap. First detect autoloader, then bootstrap Horde_Test
$candidates = [
    // installed as UUT / root project
    dirname(__FILE__, 2) . '/vendor/autoload.php',
    // installed in a deployment under /web/
    dirname(__FILE__, 4) . '/vendor/autoload.php',
];
// Cover root case and library case
foreach ($candidates as $candidate) {
    if (file_exists($candidate)) {
        require_once $candidate;
    }
}
\Horde_Test_Bootstrap::bootstrap(dirname(__FILE__));
