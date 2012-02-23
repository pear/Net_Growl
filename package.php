<?php
/**
 * Unified package script generator.
 * 
 * Build PEAR package easily, quickly.
 * Its own package.ini file looks simple enough to edit and maintain.
 *
 * Credits to https://github.com/c9s/Onion
 * 
 * PHP version 5
 *
 * @category Networking
 * @package  Net_Growl
 * @author   Laurent Laville <pear@laurent-laville.org>
 * @license  http://www.opensource.org/licenses/bsd-license.php  BSD
 * @version  SVN: $Id$
 * @link     http://growl.laurent-laville.org/
 * @link     http://pear.php.net/package/Net_Growl
 */

require_once 'PEAR/PackageFileManager2.php';

/**
 * Build list of a category of maintainer
 *
 * @param array  $authors Maintainers list
 * @param string $type    Maintainer category (lead, helper, contributor)
 * @param object $pfm     Instance of PEAR_PackageFileManager2
 *
 * @return void
 */
function addMaintainer($authors, $type, $pfm)
{
    // author info: {name}, {userid} [, {email} [, {inactive}]]
    foreach ($authors as $author) {
        $matches = explode(',', $author);
        if (count($matches) > 1) {
            $matches = array_map('trim', $matches);
            $active  = 'yes';
            if (isset($matches[3])) {
                $active = ($matches[3] === 'inactive') ? 'no' : $active;
            }
            $email = (isset($matches[2])) ? $matches[2] : '';
            $pfm->addMaintainer($type, $matches[1], $matches[0], $email, $active);
        }
    }
}

$filename = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'package.ini';
if (!file_exists($filename)) {
    echo 'Configuration file "package.ini" does not exist';
    exit(1);
}

$ini = parse_ini_file($filename, true);

if ($ini === false) {
    echo 'Cannot parse configuration file "package.ini"';
    exit(1);
}

//print_r($ini); exit();

PEAR::setErrorHandling(PEAR_ERROR_DIE);

$pfm = new PEAR_PackageFileManager2();

// files to ignore
if (isset($ini['options']['ignores'])) {
    $ignores = (array)$ini['options']['ignores'];
} else {
    $ignores = array();
}
$ignores[] = basename(__FILE__);

// files mapping exception
$exceptions = array();

if (isset($ini['options']['exceptions'])) {
    foreach ($ini['options']['exceptions'] as $exception) {
        $rule = explode(',', $exception);

        if (count($rule)) {
            list($file, $role) = array_map('trim', $rule);
            $exceptions[$file] = $role;
        }
    }
}

$options = array(
    'packagedirectory'  => dirname(__FILE__),
    'baseinstalldir'    => $ini['options']['baseinstalldir'],
    'filelistgenerator' => $ini['options']['filelistgenerator'],
    'simpleoutput'      => $ini['options']['simpleoutput'],
    'clearcontents'     => $ini['options']['clearcontents'],
    'changelogoldtonew' => $ini['options']['changelogoldtonew'],
    'ignore'            => $ignores,
    'exceptions'        => $exceptions,
);
$pfm->setOptions($options);

$pfm->setPackage($ini['package']['name']);
$pfm->setChannel($ini['package']['channel']);
$pfm->setSummary($ini['package']['summary']);
$pfm->setDescription($ini['package']['desc']);

$pfm->setPackageType('php');
$pfm->setReleaseVersion($ini['package']['version']);
$pfm->setReleaseStability($ini['package']['stability.release']);
$pfm->setAPIVersion($ini['package']['version.api']);
$pfm->setAPIStability($ini['package']['stability.api']);
$pfm->setNotes($ini['package']['notes']);

// default license
$license = array('PHP', false);

if (isset($ini['package']['license'])) {
    // license info: {license} [, {uri}]
    $matches = explode(',', $ini['package']['license']);
    $match   = count($matches);
    if ($match > 0) {
        $matches = array_map('trim', $matches);
        if ($match == 1) {
            // without URI
            $license = array($matches[0], false);
        } else {
            // with URI
            $license = array($matches[0], $matches[1]);
        }
    }
}
$pfm->setLicense($license[0], $license[1]);

$pfm->setPhpDep($ini['require']['php']);
$pfm->setPearinstallerDep($ini['require']['pearinstaller']);

// required dependencies
if (isset($ini['require'])) {
    $requires = array_keys($ini['require']);

    foreach ($requires as $dep) {
        if (preg_match('/ext\/(.*)/', $dep, $matches)) {
            $pfm->addExtensionDep('required', $matches[1]);
        }
    }
}

// lead
addMaintainer($ini['package']['authors'], 'lead', $pfm);

// helper
if (isset($ini['package']['helpers'])) {
    addMaintainer($ini['package']['helpers'], 'helper', $pfm);
}

// contributor
if (isset($ini['package']['contributors'])) {
    addMaintainer($ini['package']['contributors'], 'contributor', $pfm);
}

// replaces
$pfm->addGlobalReplacement('package-info', '@package_version@', 'version');

// generates XML
$pfm->generateContents();

if (isset($_GET['make'])
    || (isset($_SERVER['argv']) && @$_SERVER['argv'][1] == 'make')
) {
    $pfm->writePackageFile();
} else {
    $pfm->debugPackageFile();
}
