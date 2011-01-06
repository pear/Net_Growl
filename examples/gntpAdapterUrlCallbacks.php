<?php
/**
 * URL callbacks example
 * that send notifications to Growl using the new GNTP/1.0 protocol
 *
 * The callback url will be opened in the user's default browser.
 * Unlike socket callbacks, URL callbacks are only triggered if the notification
 * is clicked (CLICK|CLICKED), not for CLOSE|CLOSED or TIMEOUT|TIMEDOUT actions.
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

require_once 'Net/Growl.php';

// Notification Type definitions
define('GROWL_NOTIFY_STATUS', 'GROWL_NOTIFY_STATUS');
define('GROWL_NOTIFY_PHPERROR', 'GROWL_NOTIFY_PHPERROR');

// define a PHP application that sends notifications to Growl

$app = new Net_Growl_Application(
    'PHP App Example using GNTP', 
    array(
        GROWL_NOTIFY_STATUS => array(
            'display' => 'Status',
        ),

        GROWL_NOTIFY_PHPERROR => array(
            'icon' => 'http://www.laurent-laville.org/growl/images/firephp.png',
            'display' => 'Error-Log'
        )
    ),
    'mamasam'
);


try {
    $growl = Net_Growl::singleton(
        $app, 
        null, null, 
        array(
            'host'     => '192.168.1.2',
            'protocol' => 'tcp', 'port' => Net_Growl::GNTP_PORT,
            'AppIcon'  => 'http://www.laurent-laville.org/growl/images/Help.png',
            'encryptionAlgorithm'   => 'AES',
            'passwordHashAlgorithm' => 'SHA256',
            'debug' => dirname(__FILE__) . DIRECTORY_SEPARATOR . 'netgrowl.log'
        )
    );
    $growl->register();

    $name        = GROWL_NOTIFY_STATUS;
    $title       = 'Congratulation';
    $description = "Congratulation! You are successfull install PHP/NetGrowl.";
    $options     = array(
        'ID'                  => 123456,
        'CallbackContext'     => 'this is my context',
        'CallbackContextType' => 'STRING',
        'CallbackTarget'      => 'http://growl.laurent-laville.org/parseUrl.php'
                               . '?hello=world',
        'sticky'              => true,
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
