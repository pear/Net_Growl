<?php
/**
 * Example that send notifications to Growl using the old UDP protocol
 *
 * PHP version 5
 *
 * @category Networking
 * @package  Net_Growl
 * @author   Laurent Laville <pear@laurent-laville.org>
 * @author   Bertrand Mansion <bmansion@mamasam.com>
 * @license  http://www.opensource.org/licenses/bsd-license.php  BSD
 * @version  SVN: Release: @package_version@
 * @link     http://growl.laurent-laville.org/
 * @since    File available since Release 0.9.0
 */

require_once 'Net/Growl/Autoload.php';

// Notification Type definitions
define('GROWL_NOTIFY_STATUS', 'GROWL_NOTIFY_STATUS');
define('GROWL_NOTIFY_PHPERROR', 'GROWL_NOTIFY_PHPERROR');

// define a PHP application that sends notifications to Growl

$appName = 'PHP App Example using UDP';
$notifications = array(
    GROWL_NOTIFY_STATUS => array(),
    GROWL_NOTIFY_PHPERROR => array()
);

$password = 'mamasam';
$options  = array('host' => '127.0.0.1');

try {
    $growl = Net_Growl::singleton($appName, $notifications, $password, $options);
    $growl->register();

    $name        = GROWL_NOTIFY_STATUS;
    $title       = 'Congratulation';
    $description = 'Congratulation! You are successfull install PHP/NetGrowl.';
    $growl->notify($name, $title, $description);

    $name        = GROWL_NOTIFY_PHPERROR;
    $title       = 'PHP Error';
    $description = 'You have a new PHP error in your script P at line N';
    $options     = array(
        'sticky'   => true,
        'priority' => Net_Growl::PRIORITY_HIGH,
    );
    $growl->notify($name, $title, $description, $options);

    $name        = GROWL_NOTIFY_STATUS;
    $title       = 'Welcome';
    $description = "Welcome in PHP/Growl world ! \n"
                 . "Old UDP protocol did not support icons.";
    $growl->notify($name, $title, $description);

    var_export($growl);

} catch (Net_Growl_Exception $e) {
    echo 'Caught Growl exception: ' . $e->getMessage() . PHP_EOL;
}
