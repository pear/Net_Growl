<?php
/**
 * Copyright (c) 2009-2011, Laurent Laville <pear@laurent-laville.org>
 *                          Bertrand Mansion <bmansion@mamasam.com>
 *
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 *
 *     * Redistributions of source code must retain the above copyright
 *       notice, this list of conditions and the following disclaimer.
 *     * Redistributions in binary form must reproduce the above copyright
 *       notice, this list of conditions and the following disclaimer in the
 *       documentation and/or other materials provided with the distribution.
 *     * Neither the name of the authors nor the names of its contributors
 *       may be used to endorse or promote products derived from this software
 *       without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
 * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE
 * ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS
 * BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR
 * CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
 * SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
 * INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
 * CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 *
 * PHP version 5
 *
 * @category Networking
 * @package  Net_Growl
 * @author   Laurent Laville <pear@laurent-laville.org>
 * @author   Bertrand Mansion <bmansion@mamasam.com>
 * @license  http://www.opensource.org/licenses/bsd-license.php  BSD
 * @version  SVN: $Id$
 * @link     http://growl.laurent-laville.org/
 * @link     http://pear.php.net/package/Net_Growl
 * @since    File available since Release 0.9.0
 */

 // Lazy loading allowed by a custom __autoload function
spl_autoload_register(array('Net_Growl', 'autoload'));

/**
 * Sends notifications to {@link http://growl.info Growl}
 *
 * This package makes it possible to easily send a notification from
 * your PHP script to {@link http://growl.info Growl}.
 *
 * Growl is a global notification system for Mac OS X.
 * Any application can send a notification to Growl, which will display
 * an attractive message on your screen. Growl currently works with a
 * growing number of applications.
 *
 * The class provides the following capabilities:
 * - Register your PHP application in Growl.
 * - Let Growl know what kind of notifications to expect.
 * - Notify Growl.
 * - Set a maximum number of notifications to be displayed (beware the loops !).
 *
 * @category Networking
 * @package  Net_Growl
 * @author   Laurent Laville <pear@laurent-laville.org>
 * @author   Bertrand Mansion <bmansion@mamasam.com>
 * @license  http://www.opensource.org/licenses/bsd-license.php  BSD
 * @version  Release: @package_version@
 * @link     http://growl.laurent-laville.org/
 * @link     http://pear.php.net/package/Net_Growl
 * @link     http://growl.info Growl Homepage
 * @since    Class available since Release 0.9.0
 */
class Net_Growl
{
    /**
     * Growl default UDP port
     */
    const UDP_PORT = 9887;

    /**
     * Growl default GNTP port
     */
    const GNTP_PORT = 23053;

    /**
     * Growl priorities
     */
    const PRIORITY_LOW = -2;
    const PRIORITY_MODERATE = -1;
    const PRIORITY_NORMAL = 0;
    const PRIORITY_HIGH = 1;
    const PRIORITY_EMERGENCY = 2;

    /**
     * PHP application object
     *
     * This is usually a Net_Growl_Application object but can really be
     * any other object as long as Net_Growl_Application methods are
     * implemented.
     *
     * @var object
     */
    private $_application;

    /**
     * Application is registered
     * @var bool
     */
    protected $isRegistered = false;

    /**
     * Net_Growl connection options
     * @var array
     */
    protected $options = array(
        'host' => '127.0.0.1',
        'port' => self::UDP_PORT,
        'protocol' => 'udp',
        'timeout' => 30,
        'context' => array(),
        'passwordHashAlgorithm' => 'MD5',
        'encryptionAlgorithm' => 'NONE',
        'debug' => false
    );

    /**
     * Current number of notification being displayed on user desktop
     * @var int
     */
    protected $growlNotificationCount = 0;

    /**
     * Maximum number of notification to be displayed on user desktop
     * @var int
     */
    private $_growlNotificationLimit = 0;

    /**
     * Handle to the log file.
     * @var resource
     * @since 2.0.0b2
     */
    private $_fp = false;

    /**
     * Notification callback results
     *
     * @var array
     * @since 2.0.0b2
     */
    protected $growlNotificationCallback = array();

    /**
     * Notification unique instance
     * @var   object
     * @since 2.1.0
     * @see   singleton, reset
     */
    protected static $instance = null;

    /**
     * Singleton
     *
     * Makes sure there is only one Growl connection open.
     *
     * @param mixed  &$application  Can be either a Net_Growl_Application object
     *                              or the application name string
     * @param array  $notifications List of notification types
     * @param string $password      (optional) Password for Growl
     * @param array  $options       (optional) List of options : 'host', 'port',
     *                              'protocol', 'timeout' for Growl socket server.
     *                              'passwordHashAlgorithm', 'encryptionAlgorithm'
     *                              to secure communications.
     *                              'debug' to know what data are sent and received.
     *
     * @return object Net_Growl
     * @throws Net_Growl_Exception if class handler does not exists
     */
    public static final function singleton(&$application, $notifications,
        $password = '', $options = array()
    ) {
        if (isset($options['errorHandler']) && $options['errorHandler'] === true) {
            // Converts standard error into exception
            set_error_handler(array('Net_Growl', 'errorHandler'));
        }

        if (self::$instance === null) {
            if (isset($options['protocol'])) {
                if ($options['protocol'] == 'tcp') {
                    $protocol = 'gntp';
                } else {
                    $protocol = $options['protocol'];
                }
            } else {
                $protocol = 'udp';
            }
            $class = 'Net_Growl_' . ucfirst($protocol);

            if (class_exists($class, true)) {
                self::$instance = new $class(
                    $application, $notifications, $password, $options
                );
            } else {
                $message = 'Cannot find class "'.$class.'"';
                throw new Net_Growl_Exception($message);
            }
        }
        return self::$instance;
    }

    /**
     * Resettable Singleton Solution
     *
     * @return void
     * @link http://sebastian-bergmann.de/archives/882-guid.html
     *       Testing Code That Uses Singletons
     * @since 2.1.0
     */
    public static final function reset()
    {
        self::$instance = null;
    }

    /**
     * Constructor
     *
     * This method instantiate a new Net_Growl object and opens a socket connection
     * to the specified Growl socket server.
     * Currently, only UDP is supported by Growl.
     * The constructor registers a shutdown function {@link Net_Growl::_Net_Growl()}
     * that closes the socket if it is open.
     *
     * Example 1.
     * <code>
     * require_once 'Net/Growl.php';
     *
     * $notifications = array('Errors', 'Messages');
     * $growl = Net_Growl::singleton('My application', $notification);
     * $growl->notify( 'Messages',
     *                 'My notification title',
     *                 'My notification description');
     * </code>
     *
     * @param mixed  &$application  Can be either a Net_Growl_Application object
     *                              or the application name string
     * @param array  $notifications (optional) List of notification types
     * @param string $password      (optional) Password for Growl
     * @param array  $options       (optional) List of options : 'host', 'port',
     *                              'protocol', 'timeout' for Growl socket server.
     *                              'passwordHashAlgorithm', 'encryptionAlgorithm'
     *                              to secure communications.
     *                              'debug' to know what data are sent and received.
     *
     * @return void
     */
    protected function __construct(&$application, $notifications = array(),
        $password = '', $options = array()
    ) {
        foreach ($options as $k => $v) {
            if (isset($this->options[$k])) {
                $this->options[$k] = $v;
            }
        }
        $timeout = $this->options['timeout'];
        if (!is_int($timeout)) {
            // get default timeout (in seconds) for socket based streams.
            $timeout = ini_get('default_socket_timeout');
        }
        if (!is_int($timeout)) {
            // if default timeout not available on php.ini, then use this one
            $timeout = 30;
        }
        $this->options['timeout'] = $timeout;

        if (is_string($application)) {
            if (isset($options['AppIcon'])) {
                $icon = $options['AppIcon'];
            } else {
                $icon = '';
            }
            $this->_application = new Net_Growl_Application(
                $application, $notifications, $password, $icon
            );
        } elseif (is_object($application)) {
            $this->_application = $application;
        }

        if (is_string($this->options['debug'])) {
            $this->_fp = fopen($this->options['debug'], 'a');
        }
    }

    /**
     * Destructor
     *
     * @since 2.0.0b2
     */
    public function __destruct()
    {
        if (is_resource($this->_fp)) {
            fclose($this->_fp);
        }
    }

    /**
     * Limit the number of notifications
     *
     * This method limits the number of notifications to be displayed on
     * the Growl user desktop. By default, there is no limit. It is used
     * mostly to prevent problem with notifications within loops.
     *
     * @param int $max Maximum number of notifications
     *
     * @return void
     */
    public function setNotificationLimit($max)
    {
        $this->_growlNotificationLimit = $max;
    }

    /**
     * Returns the registered application object
     *
     * @return object Application
     * @see Net_Growl_Application
     */
    public function getApplication()
    {
        return $this->_application;
    }

    /**
     * Sends a application register to Growl
     *
     * @return Net_Growl_Response
     * @throws Net_Growl_Exception if REGISTER failed
     */
    public function register()
    {
        return $this->sendRegister();
    }

    /**
     * Sends a notification to Growl
     *
     * Growl notifications have a name, a title, a description and
     * a few options, depending on the kind of display plugin you use.
     * The bubble plugin is recommended, until there is a plugin more
     * appropriate for these kind of notifications.
     *
     * The current options supported by most Growl plugins are:
     * <pre>
     * array('priority' => 0, 'sticky' => false)
     * </pre>
     * - sticky: whether the bubble stays on screen until the user clicks on it.
     * - priority: a number from -2 (low) to 2 (high), default is 0 (normal).
     *
     * @param string $name        Notification name
     * @param string $title       Notification title
     * @param string $description (optional) Notification description
     * @param string $options     (optional) few Notification options
     *
     * @return Net_Growl_Response | FALSE
     * @throws Net_Growl_Exception if NOTIFY failed
     */
    public function notify($name, $title, $description = '', $options = array())
    {
        if ($this->_growlNotificationLimit > 0
            && $this->growlNotificationCount >= $this->_growlNotificationLimit
        ) {
            // limit reached: no more notification displayed on user desktop
            return false;
        }

        if (!$this->isRegistered) {
            $this->sendRegister();
        }
        return $this->sendNotify($name, $title, $description, $options);
    }

    /**
     * Send request to remote server
     *
     * @param string $method   Either REGISTER, NOTIFY
     * @param mixed  $data     Data block to send
     * @param bool   $callback (optional) Socket callback request
     *
     * @return Net_Growl_Response | TRUE
     * @throws Net_Growl_Exception if remote server communication failure
     */
    protected function sendRequest($method, $data, $callback = false)
    {
        // @codeCoverageIgnoreStart
        $addr = $this->options['protocol'] . '://' . $this->options['host'];

        $this->debug(
            $addr . ':' .
            $this->options['port'] . ' ' .
            $this->options['timeout']
        );

        // open connection
        if (is_array($this->options['context'])
            && function_exists('stream_context_create')
        ) {
            $context = stream_context_create($this->options['context']);

            if (function_exists('stream_socket_client')) {
                $flags = STREAM_CLIENT_CONNECT;
                $addr  = $addr . ':' . $this->options['port'];
                $sh = @stream_socket_client(
                    $addr, $errno, $errstr,
                    $this->options['timeout'], $flags, $context
                );
            } else {
                $sh = @fsockopen(
                    $addr, $this->options['port'],
                    $errno, $errstr, $$this->options['timeout'], $context
                );
            }
        } else {
            $sh = @fsockopen(
                $addr, $this->options['port'],
                $errno, $errstr, $$this->options['timeout']
            );
        }

        if ($sh === false) {
            $this->debug($errstr, 'error');
            $error = 'Could not connect to Growl Server.';
            throw new Net_Growl_Exception($error);
        }
        stream_set_timeout($sh, $this->options['timeout'], 0);

        $this->debug($data);
        $res = fwrite($sh, $data, $this->strByteLen($data));

        if ($res === false) {
            $error = 'Could not send data to Growl Server.';
            throw new Net_Growl_Exception($error);
        }

        switch ($this->options['protocol']) {
        case 'tcp':
            // read GNTP response
            $line = $this->_readLine($sh);
            $this->debug($line);
            $response = new Net_Growl_Response($line);
            $statusOK = ($response->getStatus() == 'OK');
            while ($this->strByteLen($line) > 0) {
                $line = $this->_readLine($sh);
                $response->appendBody($line."\r\n");
                if (is_resource($this->_fp)) {
                    $this->debug($line);
                }
            }

            if ($statusOK
                && $callback === true
                && $method == 'NOTIFY'
            ) {
                // read GNTP socket Callback response
                $line = $this->_readLine($sh);
                $this->debug($line);
                if (preg_match('/^GNTP\/1.0 -(\w+).*$/', $line, $resp)) {
                    $res = ($resp[1] == 'CALLBACK');
                    if ($res) {
                        while ($this->strByteLen($line) > 0) {
                            $line = $this->_readLine($sh);
                            $this->debug($line);
                            $eon = true;

                            $nid = preg_match(
                                '/^Notification-ID: (.*)$/',
                                $line, $resp
                            );
                            if ($nid) {
                                $eon = false;
                            }

                            $ncr = preg_match(
                                '/^Notification-Callback-Result: (.*)$/',
                                $line, $resp
                            );
                            if ($ncr) {
                                $this->growlNotificationCallback[] = $resp[1];
                                $eon = false;
                            }

                            $ncc = preg_match(
                                '/^Notification-Callback-Context: (.*)$/',
                                $line, $resp
                            );
                            if ($ncc) {
                                $this->growlNotificationCallback[] = $resp[1];
                                $eon = false;
                            }

                            $ncct = preg_match(
                                '/^Notification-Callback-Context-Type: (.*)$/',
                                $line, $resp
                            );
                            if ($ncct) {
                                $this->growlNotificationCallback[] = $resp[1];
                                $eon = false;
                            }

                            $nct = preg_match(
                                '/^Notification-Callback-Timestamp: (.*)$/',
                                $line, $resp
                            );
                            if ($nct) {
                                $this->growlNotificationCallback[] = $resp[1];
                                $eon = false;
                            }

                            if ($eon) {
                                break;
                            }
                        }
                    }
                }

                if (is_resource($this->_fp)) {
                    while ($this->strByteLen($line) > 0) {
                        $line = $this->_readLine($sh);
                        $this->debug($line);
                    }
                }
            }
            break;
        case 'udp':
            $statusOK = $response = true;
            break;
        }

        switch (strtoupper($method)) {
        case 'REGISTER':
            if ($statusOK) {
                $this->isRegistered = true;
            }
            break;
        case 'NOTIFY':
            if ($statusOK) {
                $this->growlNotificationCount++;
            }
            break;
        }

        // close connection
        fclose($sh);

        return $response;
        // @codeCoverageIgnoreEnd
    }

    /**
     * Returns Growl default icon logo binary data
     * Decodes data encoded with MIME base64
     *
     * @param bool $return (optional) If used and set to FALSE,
     *                     getDefaultGrowlIcon() will output the binary
     *                     representation instead of return it
     *
     * @return string
     */
    public static function getDefaultGrowlIcon($return = true)
    {
        $growl_logo
            = 'iVBORw0KGgoAAAANSUhEUgAAADAAAAAwCAYAAABXAvmHAAAAAXNSR0IArs4c6QAA'
            . 'AARnQU1BAACxjwv8YQUAAAAgY0hSTQAAeiYAAICEAAD6AAAAgOgAAHUwAADqYAAA'
            . 'OpgAABdwnLpRPAAAAAlwSFlzAAALEgAACxIB0t1+/AAACthJREFUaEPtmAlUlPUa'
            . 'h1UUkX0RFBEQwaUCd1D2xWGbGWTYZd9lkFUEEQzFJTKyrFtamZZbmZaZpbaZaZqV'
            . 'W4taaqam5kIqatpyQZ/7zpCde7rZ7Z60vOfwnfOe/8c5H9/8nnef6dCh/Wr3QLsH'
            . '2j3waw9ER0whOmyC3jQRVcSqn+D/xksxyhmEB2hRhZQzJrRCbAKxkWVihaRqqu98'
            . 'kOSYKUyZOI+spHqiQsvRRJYTp6oSkFQSQ4zJT0i4syEWPL6SK5cvcPTgl2x6eT0r'
            . '5i1jfFYN8VElxI4eSF2GMYXJuXc2RFGGhoqcMJY9XM5nby7jw1fXUlv2AFkaP05t'
            . '82R1o+OdDaAr2LjQwcSFDqJ+Qh4tVy5weO8R1i0fC9cS+P4LbxoK+93ZEAXpZWQn'
            . 'JvDEo0tou85Da5WcWrgSzdE3BlCdl/T3QuTGKchL0PymiKzYMSSrPHl7/VZOn/iC'
            . 'H67OEfGVYhXQkgHN4ax/zPWvA0iMWYBydBUKPy1B3kWEjFIQH2REaqgxKQojksMc'
            . 'yYhJ/0VQVrQvyaE2TMhR8cJTobS2lIv4yT9DlML1XC7uGUyNtvj2Q0SH1xLql89o'
            . 'Xy2B3oX4exXhMzwLpa8jSaOtxGyIDbIgyrcLaj87UtQRZKmdyVZak6M058h+yX1q'
            . 'xSaJ6dLo5/Oiimdnjrm9AEkxj6DwySfAcxzKkGJSYgqJDFDiM1RN0PABxIdYkBBi'
            . 'g9rXklBPC0KGmaD2MSJLaUO2qjtPT3cXwRPEqn8WfwNAonEtk40LA28vQFRoFQXp'
            . 'M3lu0Wt8dfAoLT9c4uAnm3lr7XIq8pUoRxoR629NRWIvVs0ZwuQcV5TeFmQqbckR'
            . 'gIUCcPzjdL7Znc3xHWnseyuJs3vzpA6kFshj1yrv2wswuXweTSeP0Xz2AAc+eZdN'
            . 'r6/k6KG9+r5yX00aqQoL1sz34dzeMSIqlxOfxktt2JAe0Z34YGs8+poQ7u1GjGIw'
            . 'kb79ifAdSEGCDx+skrS6lMHBV28zgK6nL130HtrMyYxVBZOuHkLF+Eo2rF1DpsqJ'
            . 'HRvCBKVYrEQKU86ftEwe50p8oBUqH0tMu3XC1NgQe1sLbCxNcOhhhcddfZmYHUzT'
            . 'Jg3nN3rd3gj81haZnjCOSG8HGie6tfV1ytoALmu53lzIlheCJa0s9RFw7tmVzgYd'
            . 'MDMWEIGxMuuCq3NPEtXebFukpGXPqL8eIC+lgqTATmxbOYrWM2nsWafmxTkBzC71'
            . 'ojLTiyfv9SU13I64YCsiRllgZ9UZI8OOmHTriJV5V9xcHAj3H8zWpVFwMpzVD//3'
            . 'gZYS10hB9qJbBztujC07n/Vg3gQ3nOy6YWFiKCliiqO9LXe5OeDtYUPQUDOSFDYC'
            . 'YYmDrSGWpgZYWxjRy86StEg3Dr/kC58Hcm5nIDMqZ9xUXNt6XoIqWEtOyvQ/B1GY'
            . 'UUlOwlgyIu1ZUOUk26U95sYd6dqlA9bmhpIedtzd34l+fXrg4WpCpEQgcbQ10dKp'
            . 'fDzMpMCteKTEmc+WDObaTgE4nybpV86XH6koTosgN2XyLwILUsZRmpPPh9s+Y9+n'
            . 'h3nq0ecoyCijsvTp/w0iLXm5rAVhMnGNRUAXxvgaydCylF5vy9xiJ33B6lLEsLNA'
            . 'WBjT37W3mBMDXaz1z8UH2+gjERtkzdwSJ9jtDV9Hyk6U01Y/16R+KGXbmiDCRxig'
            . '8Tckxt+AREUPUqL82Lp5i77rXTnzNdMm1lGunfnHAKIjphIwMp/hHpkytLzkC4kZ'
            . 'Y/wsCR9pjmKEOVHSZdLCuvNYiSNztA4ovcxwdzJkoLMFI9z7oI0dwIbGu9Fq7IgR'
            . 'yJhAax4tFYCvpHO1jmvrXq3jf7H5s0YwepiZ/lm1nxXKgAHS9dyp1caw8tnnObH7'
            . 'PZ6eM5eKotm/D5CVuojAkbkybTNR+OcToywkITKMsQoZWMmOVKW7MHfiPezZEMr+'
            . '9yP57J3RcErDlS/U7HvNn/cWDmHbYj8ubFfDwXCW17miCRCAAGupGWc4Kt5vzRfP'
            . 'FwqEzkrYujZCP8l1Ez1eij8m0J7CjHSqCpJoKFVQXVzOrCkzmT2tAW1K4s0B8jIX'
            . '4zssg/SEKaxY+ioH9u3j8sVv2bj+eR6vu4cfm5JpbUqRlpmuX8i4JlP1O7FmMb0Y'
            . 'sWsi7qds+CELzsVx6GVP8qNs9Sk0NbMXx9eNhG81cFXe810qO14PJ0Nlr5/gCSHW'
            . 'AmrBrHJ3Vi6toFRbzcTCIiaXTWLyhHoeqq9iyUz3mwMEeOZSWtBI8/lL+ry7cX15'
            . 'YB/vrg6WP3WhF7HfZ9O8J5Fty9S8MjeCzQvVfL0lgX+eFLB/yjOtBTKZBeRSMj9+'
            . 'rGBJjZu+XlZNd2PnU3dz/o0RHN/gxTO1bgQOMsXXw1SfYmOlVmICLJk3xYPrPxSw'
            . 'euUkyfkGxudMo2H6VA7tSObkluG/DVA2fhUN9fO51NxM0+njbN+0kU3rN7Br+0ds'
            . 'fec9/jHVh8tNcXqIA2/HUZTkhcJ3EH6eAxnu7kzAMCcaS4ZzdX+SQEiBfi8RaJb7'
            . 'I0qOvuhF09s+tO4KYOcCd2pS7PF3N8XcpBPdDDvJaYBLr676ljtW1vDx0T05/mmM'
            . 'fNYsjhxaz8a1RTR/FQ0XfNm8LOzmEdi+9QtefnEXs+pXU1u5/JcH6+99lZJsLfcV'
            . '2rDlmSFkqvvT16kXjr1ssLMxpYdYzx42DBrQmzWNo+BELFxMhCY5T0gt7FcIiIrt'
            . 'S4PI1dwlbdYOU5Nuuvdj0KnNOorZWnbWR6Iktgcnd4RIOqZKuorwpgA448+P+wez'
            . 'sHHWH+tCN/sxSqtxYZBLB1kNDPTrgbmxAWYmXelhbSKpYMU66Twck0I9J+JPyYd/'
            . 'IwDHVXyw2J/oEHdGDnPX70M2VmYYyJqhWzV0prvXAQ3tb0KVROijxe5c3D2S64d8'
            . '5P8DaDngxevz/f6c+BtQ1flqNH6GBMi0VUs7nZHrwOr73Tj9+nA4JF3phFK8JuJP'
            . 'y7pwRs31g5FMyx2Iax8HBt/dl7v6OeoBboi/cXaSKOj2Jl0aVSTZU5NqzxypnTcf'
            . '6c+Kul63RvwNiLqieAo1pkwSb13cNEK8Hii7TUSb6NPi9TM/n01RXP1E2nCoE7bd'
            . 'LenTuzu9e1phZmr0HwC6VNJFwXOgCTXpvbi/0Ill01xEvAMPT516awF0INV5YRRp'
            . 'TDj22jDxeDicVYl4MfE6p3T3EomzEoGTKp6sdqenrTnWslZbmHbFsEsnPUCXf0sf'
            . 'nfhBfbux9oH+XN4jE/uID8c2eLDowTm3XvyNSFSke/JYmTWH1g7j+ufSas9I/jfp'
            . 'IET8KbnXnQJ2dXeIpEUfrGTd6GxgQKeOHfSm83qXzh1lKTQkLbQ72+brashfJvYI'
            . 'Plr2Oz3/Vv5iXFOYwISE7iyaZM/hNUNp3Svp9KV0kYNSD8dkdTgSKoJC+W6rPw+O'
            . 'd2Wwm7RQyXVr8870czSSCWzF4+XOnHxpCGwfxuFXBrBitur2ef1m8JPyIpmYaMW8'
            . 'MjvelcI7snIIV94ZScv7Plz/QLbPXf60bPbmwyfv4alKF6ZmO3BvlgNPVDrzxoMu'
            . 'vPmgAwtrh/71wn8NVFccR/lYJ2rTLHi02JrnptjzRmMfts/TTeJ+vD/PlXWznXly'
            . 'oh0zcy2ZmWfPrCJvHpre8PeL/zVMQ0059WXJ1BVGUKcNpn58EDOKFbLvjGH2pBzm'
            . 'zrj5l5lbmebt72r3QLsH2j3Q7oF2D9xxHvgXsaxDNYPEU7QAAAAASUVORK5CYII=';

        $data = base64_decode($growl_logo);

        if ($return === false) {
            // @codeCoverageIgnoreStart
            if (headers_sent()) {
                return;
            }
            header('content-type: image/png');
            echo $data;
            exit();
            // @codeCoverageIgnoreEnd
        } else {
            return $data;
        }
    }

    /**
     * Logs GNTP IN/OUT messages
     *
     * @param string $message  String containing the message to log
     * @param string $priority (optional) String containing a priority name
     *
     * @return void
     */
    protected function debug($message, $priority = 'debug')
    {
        if (is_resource($this->_fp)
            && $this->strByteLen($message) > 0
        ) {
            fwrite(
                $this->_fp,
                date("Y-m-d H:i:s") . " [$priority] - " . $message . "\n"
            );
        }
    }

    /**
     * Converts standard error into exception
     *
     * @param int    $errno   contains the level of the error raised
     * @param string $errstr  contains the error message
     * @param string $errfile contains the filename that the error was raised in
     * @param int    $errline contains the line number the error was raised at
     *
     * @return void
     * @throws ErrorException when a standard error occured with severity level
     *                        we are asking for (uses error_reporting)
     * @since 2.1.0
     */
    public static function errorHandler($errno, $errstr, $errfile, $errline)
    {
        // Only catch errors we are asking for
        if ((error_reporting() & $errno) == 0) {
            return;
        }
        throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
    }

    /**
     * Autoloader for PEAR compatible classes
     *
     * @param string $className Class name
     *
     * @return void
     */
    public static function autoload($className)
    {
        static $classes = null;
        static $path    = null;

        if ($classes === null) {
            $classes = array(
                'Net_Growl'             => 'Net/Growl.php',
                'Net_Growl_Application' => 'Net/Growl/Application.php',
                'Net_Growl_Exception'   => 'Net/Growl/Exception.php',
                'Net_Growl_Gntp'        => 'Net/Growl/Gntp.php',
                'Net_Growl_GntpMock'    => 'Net/Growl/GntpMock.php',
                'Net_Growl_Response'    => 'Net/Growl/Response.php',
                'Net_Growl_Udp'         => 'Net/Growl/Udp.php',
            );
            $path = dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR;
        }

        if (isset($classes[$className])) {
            include $path . $classes[$className];
        }
    }

    /**
     * Read until either the end of the socket or a newline, whichever
     * comes first. Strips the trailing newline from the returned data.
     *
     * @param mixed $fp a file pointer resource
     *
     * @return All available data up to a newline, without that
     *         newline, or until the end of the socket,
     * @throws Net_Growl_Exception if not connected
     */
    private function _readLine($fp)
    {
        // @codeCoverageIgnoreStart
        if (!is_resource($fp)) {
            throw new Net_Growl_Exception('not connected');
        }

        $line = '';
        $timeout = time() + $this->options['timeout'];
        while (!feof($fp) && (time() < $timeout)) {
            $line .= @fgets($fp);
            if (mb_substr($line, -1) == "\n" && $this->strByteLen($line) > 0) {
                break;
            }
        }
        return rtrim($line, "\r\n");
        // @codeCoverageIgnoreEnd
    }

    /**
     * Encodes a detect_order string to UTF-8
     *
     * @param string $data an intended string.
     *
     * @return Returns of the UTF-8 translation of $data.
     *
     * @see http://www.php.net/manual/en/function.mb-detect-encoding.php
     * @see http://www.php.net/manual/en/function.mb-convert-encoding.php
     */
    protected function utf8Encode($data)
    {
        if (extension_loaded('mbstring')) {
            return mb_convert_encoding($data, 'UTF-8', 'auto');
        } else {
            return utf8_encode($data);
        }
    }

    /**
     * Get string byte length
     *
     * @param string $string The string being measured for byte length.
     *
     * @return The byte length of the $string.
     */
    protected function strByteLen($string)
    {
        return strlen(bin2hex($string)) / 2;
    }

}
