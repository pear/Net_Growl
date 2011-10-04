<?php
/**
 * Socket callbacks example
 * that send notifications to Growl using the new GNTP/1.0 protocol
 *
 * Callbacks are sent back to the sending application
 * when an action is taken in response to a notification.
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
 * @since    File available since Release 2.0.0b2
 */

require_once 'Net/Growl/Autoload.php';

/**
 * Callback function when notification response is returned
 *
 * @param string $result    Notification-Callback-Result: header, result
 *                          [CLICKED|CLOSED|TIMEDOUT] | [CLICK|CLOSE|TIMEOUT]
 * @param string $context   Notification-Callback-Context: header, result
 *                          from the original request
 * @param string $type      Notification-Callback-Context-Type: header, result
 *                          from the original request
 * @param string $timestamp Notification-Callback-Timestamp: header, result
 *                          The date and time the callback occurred
 *
 * @return void
 */
function cbNotify($result, $context, $type, $timestamp)
{
    printf(
        "Notification Callback Result => %s: %s (%s) at %s \n\n",
        $result, $context, $type, $timestamp
    );
}

// Notification Type definitions
define('GROWL_NOTIFY_STATUS', 'GROWL_NOTIFY_STATUS');
define('GROWL_NOTIFY_PHPERROR', 'GROWL_NOTIFY_PHPERROR');

// define a PHP application that sends notifications to Growl

$appName = 'PHP App Example using GNTP';
$notifications = array(
    GROWL_NOTIFY_STATUS => array(
        'display' => 'Status',
    ),

    GROWL_NOTIFY_PHPERROR => array(
        'icon'    => 'http://www.laurent-laville.org/growl/images/firephp.png',
        'display' => 'Error-Log'
    )
);

$password = 'mamasam';
$options  = array(
    'host'     => '127.0.0.1',
    'protocol' => 'tcp', 'port' => Net_Growl::GNTP_PORT,
    'AppIcon'  => 'http://www.laurent-laville.org/growl/images/Help.png',
    'debug'    => dirname(__FILE__) . DIRECTORY_SEPARATOR . 'netgrowl.log'
);

try {
    $growl = Net_Growl::singleton($appName, $notifications, $password, $options);
    $growl->register();

    $name        = GROWL_NOTIFY_STATUS;
    $title       = 'Congratulation';
    $description = 'Congratulation! You are successfull install PHP/NetGrowl.';
    $options     = array(
        'ID'                  => 123456,
        'CallbackContext'     => 'this is my context',
        'CallbackContextType' => 'STRING',
        'CallbackFunction'    => 'cbNotify'
    );
    $growl->notify($name, $title, $description, $options);

    $name        = GROWL_NOTIFY_PHPERROR;
    $title       = 'PHP Error';
    $description = 'You have a new PHP error in your script P at line N';
    $options     = array(
        'priority' => Net_Growl::PRIORITY_HIGH,
    );
    $growl->notify($name, $title, $description, $options);

    $name        = GROWL_NOTIFY_STATUS;
    $title       = 'Welcome';
    $description = "Welcome in PHP/GNTP world ! \n"
                 . "New GNTP protocol add icon support.";
    $options     = array(
        'icon'   => 'http://www.laurent-laville.org/growl/images/unknown.png',
        'sticky' => false,
    );
    $growl->notify($name, $title, $description, $options);

    var_export($growl);

} catch (Net_Growl_Exception $e) {
    echo 'Caught Growl exception: ' . $e->getMessage() . PHP_EOL;
}
