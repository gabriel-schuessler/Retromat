<?php

// arguments
$TRAVIS_COMMIT = $argv[1];

// local settings
$buildDirPrefix = 'travis-build/';
$buildDir = $TRAVIS_COMMIT;
$artifactFileName = $TRAVIS_COMMIT.'.tar.gz ';

// remote settings
$sshDestination = 'timon@vega.uberspace.de';
$webSpaceDirPrefix = '/var/www/virtual/timon/';
$artifactDestinationDir = $webSpaceDirPrefix.'retromat-artifacts/';
$deploymentDestinationDir = $webSpaceDirPrefix.'retromat-deployments/';
$deploymentDir = $webSpaceDirPrefix.'retromat-deployments/'.$TRAVIS_COMMIT;
$deploymentDomain = 'retromat-branch-backend.timon.vega.uberspace.de ';

// mark deployment
system('echo '.$TRAVIS_COMMIT .' > '.'backend/web/commit.txt');

// create artifact
system('mkdir -p '.$buildDirPrefix.$buildDir);
system('mv * '.$buildDirPrefix.$buildDir);
system('chmod -R 755 '.$buildDirPrefix.$buildDir);
system('cd '.$buildDirPrefix.' ; tar cfz '.$artifactFileName.' '.$buildDir);

// transfer artifact
system('ssh '.$sshDestination.' mkdir -p '.$artifactDestinationDir);
system('rsync '.$buildDirPrefix.$artifactFileName.' '.$sshDestination.':'.$artifactDestinationDir);

// obtain local md5
$output = array();
$exitCode = '';
$command = 'cd '.$buildDirPrefix.' ; md5sum '.$artifactFileName;
exec($command, $output, $exitCode);
if (0 === $exitCode) {
    $md5Local = $output[0];
} else {
    exit(1);
}

// obtain remote md5
$output = array();
$exitCode = '';
$command = 'ssh '.$sshDestination.' "cd '.$artifactDestinationDir.' ; md5sum '.$artifactFileName.' "';
exec($command, $output, $exitCode);
if (0 === $exitCode) {
    $md5Remote = $output[0];
} else {
    exit(2);
}

// notify about success
echo PHP_EOL.'Local md5:  '.$md5Local.PHP_EOL.'Remote md5: '.$md5Remote;
if (0 !== strcmp($md5Local, $md5Remote)) {
    exit(3);
}

// unpack artifact
system('ssh '.$sshDestination.' mkdir -p '.$deploymentDestinationDir);
system('ssh '.$sshDestination.' "cd '.$deploymentDestinationDir . ' ; tar xfz ' . $artifactDestinationDir.$artifactFileName . ' "');

// update DB schema and load fixtures (as long as DB is readonly, this will be O.K.)
system('ssh '.$sshDestination.' "cd '.$deploymentDir . ' ; php backend/bin/console doctrine:schema:update --force --env=dev "');
system('ssh '.$sshDestination.' "cd '.$deploymentDir . ' ; php backend/bin/console doctrine:fixtures:load -n --env=dev "');

// create / update symlink to make backend/web visible to the outside
system('ssh '.$sshDestination.' "cd '.$webSpaceDirPrefix . ' ; rm '.$deploymentDomain.' ; ln -s ' . $deploymentDir . '/backend/web/ '.$deploymentDomain.' "');
