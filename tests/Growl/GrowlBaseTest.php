<?php
/**
 * Unit tests for Net_Growl package base class
 *
 * PHP version 5
 *
 * @category Networking
 * @package  Net_Growl
 * @author   Laurent Laville <pear@laurent-laville.org>
 * @license  http://www.opensource.org/licenses/bsd-license.php  BSD
 * @version  SVN: $Id:$
 * @link     http://pear.php.net/package/Net_Growl
 * @since    File available since Release 2.6.0
 */

require_once 'Net/Growl/Autoload.php';

/**
 * Unit test for Net_Growl_Gntp class
 */
class Net_Growl_GrowlBaseTest extends PHPUnit_Framework_TestCase
{
    /**
     * Sets up the fixture.
     * This method is called before a test is executed.
     *
     * @return void
     */
    protected function setUp()
    {
        // @link http://sebastian-bergmann.de/archives/882-guid.html
        //       Testing Code That Uses Singletons
        Net_Growl::reset();
    }

    /**
     * test to retrieve options in used with current Growl object
     */
    public function testGettingOptions()
    {
        $appName        = 'Net_Growl UT';
        $notifications  = array();
        $defaultOptions = array(
            'host' => '127.0.0.1',
            'port' => Net_Growl::UDP_PORT,
            'protocol' => 'udp',
            'timeout' => 30,
            'context' => array(),
            'passwordHashAlgorithm' => 'MD5',
            'encryptionAlgorithm' => 'NONE',
            'debug' => false
        );
        
        $growl = Net_Growl::singleton($appName, $notifications);

        $this->assertEquals(
            $defaultOptions,
            $growl->getOptions()
        );
    }

    /**
     * test of use without need to declare port option
     */
    public function testUseWithOptionalPortOption()
    {
        $appName       = 'Net_Growl UT';
        $notifications = array();
        $password      = '';
        $options       = array(
            'protocol' => 'gntp',
        );
        $growl = Net_Growl::singleton($appName, $notifications, $password, $options);

        $this->assertEquals(
            array(
                'host' => '127.0.0.1',
                'port' => Net_Growl::GNTP_PORT,
                'protocol' => 'gntp',
                'timeout' => 30,
                'context' => array(),
                'passwordHashAlgorithm' => 'MD5',
                'encryptionAlgorithm' => 'NONE',
                'debug' => false
            ),
            $growl->getOptions()
        );
    }
}
