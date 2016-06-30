<?php
/*
 * This autoloader loads dependency libraries for the Application
 */
$mapping = array(
		// Dindent
		'Gajus\Dindent\Indenter' => __DIR__ . '\Gajus\Dindent\Indenter.php',
		'Gajus\Dindent\Exception\DindentException' => __DIR__ . '/Gajus/dindent/Exception/DindentException.php',
		'Gajus\Dindent\Exception\InvalidArgumentException' => __DIR__ . '/Gajus/dindent/Exception/InvalidArgumentException.php',
		'Gajus\Dindent\Exception\RuntimeException' => __DIR__ . '/Gajus/dindent/Exception/RuntimeException.php',
);

spl_autoload_register(function ($class) use ($mapping) {
	if (isset($mapping[$class])) {
		require $mapping[$class];
	}
}, true);