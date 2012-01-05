<?php
require_once 'PEAR/PackageFileManager2.php';

PEAR::setErrorHandling(PEAR_ERROR_DIE);

$p2 = new PEAR_PackageFileManager2();

$name        = 'Net_Growl';
$summary     = 'Send notifications to Growl from PHP on MACOSX and WINDOWS';
$description = 'Growl is a MACOSX application that listen to notifications sent by
applications and displays them on the desktop using different display
styles. Net_Growl offers the possibility to send notifications to Growl
from your PHP application through network communication using UDP.
';
$channel     = 'pear.php.net';

$release_state   = 'stable';
$release_version = '2.5.0';

$api_state       = 'stable';
$api_version     = '2.5.0';
$release_notes   = "
Additions and changes:
- Update Net_Growl::getDefaultGrowlIcon() method to return either 
old or new Growl claw icons to match Mac icon.
- Use PHPUnit/Autoload.php instead of requiring the files manually.
- phing build documentation script is now easily reuseable (configuration through an external properties file)

Bug fixes:
- Require the autoloader in the test files so the class files will be found (Daniel Convissor)
";
$license = array('BSD License', 'http://www.opensource.org/licenses/bsd-license.php');

$p2->setOptions(array(
    'packagedirectory'  => dirname(__FILE__),
    'baseinstalldir'    => 'Net',
    'filelistgenerator' => 'file',
    'simpleoutput'      => true,
    'clearcontents'     => false,
    'changelogoldtonew' => false,
    'ignore'            => array(basename(__FILE__),
        '*.tgz', '*.log', '*.html', '*.pdf', '*.chm', '*.zip',
        'icon.php', 'Thumbs.db', 'docs/js/*.js', 'docs/styles/*.css'
        ),
    'exceptions'        => array('README.txt' => 'doc', 'build-phing.xml' => 'doc'),
    ));

$p2->setPackage($name);
$p2->setChannel($channel);
$p2->setSummary($summary);
$p2->setDescription($description);

$p2->setPackageType('php');
$p2->setReleaseVersion($release_version);
$p2->setReleaseStability($release_state);
$p2->setAPIVersion($api_version);
$p2->setAPIStability($api_state);
$p2->setNotes($release_notes);
$p2->setLicense($license[0], $license[1]);

$p2->setPhpDep('5.2.0');
$p2->setPearinstallerDep('1.5.4');

$p2->addExtensionDep('required', 'pcre');
$p2->addExtensionDep('required', 'spl');

$p2->addMaintainer('lead', 'farell', 'Laurent Laville', 'pear@laurent-laville.org');
$p2->addMaintainer('lead', 'mansion', 'Bertrand Mansion', 'bmansion@mamasam.com', 'no');
$p2->addMaintainer('helper', '<brian>', 'Brian Dunnington', 'Brian Dunnington@gmail.com');
$p2->addMaintainer('contributor', 'ariela', 'Takeshi Kawamoto', 'yuki@transrain.net');

$p2->addGlobalReplacement('package-info', '@package_version@', 'version');

$p2->generateContents();

if (isset($_GET['make'])
    || (isset($_SERVER['argv']) && @$_SERVER['argv'][1] == 'make')) {
    $p2->writePackageFile();
} else {
    $p2->debugPackageFile();
}
