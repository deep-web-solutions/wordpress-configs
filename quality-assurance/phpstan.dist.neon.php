<?php declare( strict_types = 1 );

$config = array();
$workingDirectory = getcwd();

foreach ( array( 'dependencies', 'vendor/johnpbloch/wordpress-core' ) as $discoverDirectory ) {
	if ( is_dir( $workingDirectory . '/' . $discoverDirectory ) ) {
		$config['parameters']['scanDirectories'][] = $workingDirectory . '/' . $discoverDirectory;
	}
}

foreach ( array( 'src', 'includes', 'tests' ) as $analyzeDirectory ) {
	if ( is_dir( $workingDirectory . '/' . $analyzeDirectory ) ) {
		$config['parameters']['paths'][] = $workingDirectory . '/' . $analyzeDirectory;
	}
}
foreach ( array( 'bootstrap.php', 'functions.php' ) as $analyzeFile ) {
	if ( is_file( $workingDirectory . '/' . $analyzeFile ) ) {
		$config['parameters']['paths'][] = $workingDirectory . '/' . $analyzeFile;
	}
}

$config['excludePaths'][] = $workingDirectory . '/tests/*/_generated/*';

return $config;
