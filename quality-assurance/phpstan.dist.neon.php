<?php declare( strict_types = 1 );

$config = array();
$workingDirectory = getcwd();

foreach ( array( 'dependencies' ) as $discoverDirectory ) {
	if ( is_dir( $workingDirectory . '/' . $discoverDirectory ) ) {
		$config['parameters']['scanDirectories'][] = $workingDirectory . '/' . $discoverDirectory;
	}
}

foreach ( array( 'bootstrap.php', 'functions.php' ) as $analyzeFile ) {
	if ( is_file( $workingDirectory . '/' . $analyzeFile ) ) {
		$config['parameters']['paths'][] = $workingDirectory . '/' . $analyzeFile;
	}
}
foreach ( array( 'src', 'includes' ) as $analyzeDirectory ) {
	if ( is_dir( $workingDirectory . '/' . $analyzeDirectory ) ) {
		$config['parameters']['paths'][] = $workingDirectory . '/' . $analyzeDirectory;
	}
}

if ( is_file( $workingDirectory . '/bootstrap.php' ) ) {
	$config['parameters']['WPCompat']['pluginFile'] = $workingDirectory . '/bootstrap.php';
}

return $config;
