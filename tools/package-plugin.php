<?php
declare(strict_types=1);
/** Build the installable ZIP without developer tools, nested archives or local fixtures. */
if ( PHP_SAPI !== 'cli' ) { exit; }
$project_dir = dirname( __DIR__ );
$plugin_dir = $project_dir . '/src';
$version = json_decode( file_get_contents( $plugin_dir . '/package.json' ), true, 512, JSON_THROW_ON_ERROR )['version'];
$destination = $project_dir . '/dist';
if ( ! is_dir( $destination ) ) { mkdir( $destination, 0755, true ); }
$archive_path = $destination . '/online-scout-manager-' . $version . '.zip';
$zip = new ZipArchive();
if ( true !== $zip->open( $archive_path, ZipArchive::CREATE | ZipArchive::OVERWRITE ) ) { throw new RuntimeException( 'Cannot open the plugin archive.' ); }
foreach ( [ 'includes', 'assets', 'build', 'templates' ] as $directory ) {
    $iterator = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $plugin_dir . '/' . $directory, FilesystemIterator::SKIP_DOTS ) );
    foreach ( $iterator as $file ) {
        if ( ! $file->isFile() || '.DS_Store' === $file->getFilename() || 'zip' === $file->getExtension() ) { continue; }
        $relative_path = substr( $file->getPathname(), strlen( $plugin_dir ) + 1 );
        $zip->addFile( $file->getPathname(), 'online-scout-manager/' . $relative_path );
    }
}
foreach ( [ 'online-scout-manager.php', 'readme.txt' ] as $file ) { $zip->addFile( $plugin_dir . '/' . $file, 'online-scout-manager/' . $file ); }
$zip->addFile( $project_dir . '/LICENCE', 'online-scout-manager/LICENCE' );
$zip->close();
echo 'Built ' . $archive_path . PHP_EOL;
