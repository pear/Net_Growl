<?php
require_once 'PEAR/PackageFileManager2.php';

PEAR::setErrorHandling(PEAR_ERROR_DIE);

$p2 = new PEAR_PackageFileManager2();

$name        = 'Net_Growl';
$summary     = 'Send notifications to Growl from PHP on MACOSX';
$description = 'Growl is a MACOSX application that listen to notifications sent by
applications and displays them on the desktop using different display
styles. Net_Growl offers the possibility to send notifications to Growl
from your PHP application through network communication using UDP.
';
$channel     = 'pear.php.net';

$release_state   = 'stable';
$release_version = '2.2.0';

$api_state       = 'stable';
$api_version     = '2.2.0';
$release_notes   = "
Version 2 is a PHP5 rewrites without dependency

Full features:
- GNTP adapter support socket and url callbacks
- The supported hashing algorithms are:
  MD5
    (128-bit, 16 byte, 32 character length when hex encoded)
  SHA1
    (160-bit, 20 byte, 40 character length when hex encoded)
  SHA256
    (256-bit, 32 byte, 64 character length when hex encoded)
  SHA512
    (512-bit, 64 byte, 128 character length when hex encoded)
- GNTP adapter support messages encryption (DES, 3DES, AES)

Additions and changes:
- E_STRICT compatible for PHP 5.3 or later
- removes unecessary require_once 
- removes all php close tag
- adds pear project page link to all class headers; since it's also available on PEAR repository 
- adds automated build documentation process with Phing (build-phing.xml). See also README.txt
- reduces size of documentation written with AsciiDoc 8.6.3
- bump copyright year
- adds missing extension dependencies in package.xml

Bug fixes:
- fix script examples\udpAdapterNotifyPEARerrors.php to be compatible PHP 5.3 or later with PEAR error handling

Quality Assurance:
- two basic examples script that show how to use notifications with :
  . UDP (examples/updAdapter.php)
  . GNTP (examples/gntpAdapter.php)
- two advanced examples script that show how to use :
  . socket callbacks (examples/gntpAdapterSocketCallbacks.php)
  . url callbacks (examples/gntpAdapterUrlCallbacks.php)
- one basic example script that show how to use :
  . message encryption (examples/gntpAdapterSecurity.php)
- a full test suite that cover GNTP adapter with the new Mock adapter
- Source code are PHP_CodeSniffer 1.2.2 valid
- User Guide is available into this distribution.
  You can find more format to download on the site
  http://growl.laurent-laville.org/

*** Special Thanks to Brian Dunnington for his help ! ***

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
        'icon.php', 'Thumbs.db', 'build-phing.xml'
        ),
    'exceptions'        => array('README.txt' => 'doc'),
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
$p2->addMaintainer('helper', 'brian', 'Brian Dunnington', 'Brian Dunnington@gmail.com');

$p2->addGlobalReplacement('package-info', '@package_version@', 'version');

$p2->generateContents();

if (isset($_GET['make'])
    || (isset($_SERVER['argv']) && @$_SERVER['argv'][1] == 'make')) {
    $p2->writePackageFile();
} else {
    $p2->debugPackageFile();
}
