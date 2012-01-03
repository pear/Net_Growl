<?php
/**
 * Copyright (c) 2009-2012, Laurent Laville <pear@laurent-laville.org>
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
     * @param bool   $return (optional) If used and set to FALSE,
     *                       getDefaultGrowlIcon() will output the binary
     *                       representation instead of return it
     * @param string $ver    Icon version 
     *
     * @return string
     */
    public static function getDefaultGrowlIcon($return = true, $ver = '2')
    {
        $growl_logo = ('1' == $ver) 
            ? self::_getGrowlIconV1() : self::_getGrowlIconV2();

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
     * Growl default icon logo v1
     * binary data MIME base64 encoded
     *
     * @return string
     */
    private static function _getGrowlIconV1()
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

        return $growl_logo;
    }

    /**
     * Growl default icon logo v2
     * binary data MIME base64 encoded
     *
     * @return string
     */
    private static function _getGrowlIconV2()
    {
        $growl_logo
            = 'iVBORw0KGgoAAAANSUhEUgAAADAAAAAwCAYAAABXAvmHAAAACXBIWXMAAAsSAAAL'
            . 'EgHS3X78AAAWKElEQVRogdWaabBlV3WYv733me783n1jj6/n1oQmrAEkJAhCCDBI'
            . 'GKdwypQglJPYJCFAGTyk4qo4cRzsVIyLoqhQhYwdR2BjjAEVkgySEBJILakl6BZq'
            . 'Wk13q8c3vzvfM+698uO9d6Vg4wr8cJVX1a579rlnr73WXsNZw1Eiwj9l8AA+/elP'
            . 'Mz8/TxAE/KwM/Uzr3E+3RmtNHMccPHiQu9/3XkQEJSJcdtllHDt2DK01zrmfnpCf'
            . 'ATyluWJyK0YbBAEBlGLjAkRgc8r6f1prep0ut9z+Rj7z5b/AWrsuAa31PwrRm6QA'
            . '2incUmSU8Yw4ty6KdSkqlFIbj7F+LeDE4Xmadgqt8GV6vaIomJubo1Qq4Xke1WqV'
            . '4XBIGIbkeU4URWRZNpKM7/sAOOdIkoQoiqhUKsRxTBzHhGGI7/uICEVR4JxDKUVR'
            . 'FKO1RV7Yqdlp4gjJi0IFYYAtLFEpwlqL1pokSfA9nyRNMNpQLpdJ0gTnHOPbpvjW'
            . 'I9/i6muuxsuyjF27dnHzzTdjrWViYoI8zzHGsLS0hOd5TE5O0m636Xa71Ot1PM9D'
            . 'RBgMBoyPj4+eVxtiBkiShDiOqdfrFEVBHA+01gbnRG659fUfPnDJFXfbLL3wxOOP'
            . '/ptTp05dqFQrKs9y2cS1icPzPJIkYWJigm63S7lUotfvc/8D97Nn7x48pRTOOfI8'
            . 'xznH4uIiSZKMDLrT6dDtdgmCgDiOERGstYRhiIiwsLBAEARorUnTFN/38TyP4XCI'
            . '7/sbkhmq1EXKU4W9/NID/9PifViEvJtkV09u3X7PmQsX3nzu4kVKpQjPeGgEWxSU'
            . 'q2VarTa1apXjJ08wPTVNP4nxSxGz42OIyLoNAOR5PrJy5xzD4ZDx8XHK5TJpmjIz'
            . 'M0On06FcLqO1HjEcxzGNRoOiKPA8jziO8TyPUqlEURSAKM8vc8B7Jhor5//2mVOl'
            . 'D+7ae8Bt3zKhVtdWiicPPX372vzpf1ZtjD8cJ7mp13xrtCZ3DltYhoMBSRyzfdt2'
            . 'nHMURUESxwz6/XWJAxhj8H0frTXVapUwDBkfHx9JYmJigk1J9Xo9Neh3te9pnaWx'
            . 'Ems5f/4cWZaNmAfIspxGvaJ6WSCz9snxW8t/84WXzp77+PPHX6JWLWvAW15cVI9/'
            . '9zDu4jc/tL20rJrNplVKkSQxSina7TbNZpPx8XH6/T5aazzPY2xsjCAIyLKMkTln'
            . 'WYbWmqIoUEoRBAG+72OtxVpLq9UyIqJFRHJddZ00cP0ikFyHSittjF53gs1mk0ql'
            . 'gsKyuJYyrV7kuto3/nNRLv/88+elOPHDH+iHHnqEr93/DR544OvmB88fcUq5t19l'
            . 'P/+vdLoAOtBaKQaDwUj/lVKUSqWRCvu+T6fTWVchERn5/yRJqNfrrK6u4nke5XJ5'
            . '04Oo5eVl22w2mZqe1c3ONw4W7QvlPI/O2fLO5faWa2xSaEyAHg76KK1clotpVFN7'
            . 'rXnwLk/l/y6zni0KTK/bUd9+9BEOHTrEyR/9kDRNqVU8Kt7gI6Xe8S8Oaltbke8p'
            . '3/elXC6T5/nItvI8p9FojOwuCIKXbaBWqzEcDhkMBhRFQZ7nZFkGwOTkpNx51zv/'
            . '5dyuve9DmeaJp5Kt4alPNqbz0+dby+q5Vu+JvzrN/m9Gu16zuDisoMmNCT3bLJ7R'
            . '28bO/1Y7q1AxVhk/VJOTdYwxxPEApRRjY3U912g5KdzBOb73zpX0qnt0pa7HGoGN'
            . 'k4R+v0+1WsXzPIIgYHV1lSiKRnR6m94jyzJKpRJJkhCGIZOTk6ysrJg0Te2b73jr'
            . 'ByZnd36q3em6PEt0XL+OR3p389rS5+cu3X1yrtY9fdeW4cl5u/LYF9rlfZ9+Kn7D'
            . 'qaHdwq6J1evjgus8IzRrufJ9oVKuUCqVUGmK8Utsm0jZP92SVAJmKp1batK/Z2EN'
            . 'aVTD0QGuOwPo9/v4vk+1Wh05Ew1grSVNUwaD9VOZmJhgcXGRer3uxhoNPD/8hcGg'
            . 'R6MWFaVS5EomkWGSy6HVaySMjJvbbZjdW55tTrsP7zTff/YN4b0fu6R8gmaw8Bab'
            . 'a+VpCmOsCnRCuVpnZmaaUikizjSv29Nn32yiKnVNqSSXkvdVtVZ3/V5PZVlGFEUU'
            . 'RYExhmazyeTkJFmWsba2hnMOzzlHqVQa+XnP8+h2u2RZxurqqlJKydNPP5X/4rve'
            . 'SRQG5uLSmu73Bwx7q6SiafmTavfcmoyHgbhcS3upXOHM8L9dP7jnep1EYzkRnjid'
            . 'W82rd5zj3NExFpc0Fy8scGD6Iu+/o8d0NWBwFoqi05zwVsstqwaTE03iNKcoCqrV'
            . 'KkmS0Gg06Ha7I2+U5/m6CiVJQpqmBEHApkQmJycREb24uOgeefibh6981VV3zGzZ'
            . 'IoefOcxzh58hiVOKtEdMCGFN0agr4wwTpUL8IHNnz5buSnsDZ0KFKKWdM7zukoQg'
            . 'Os7xi2e57HKPW36uwtyl47iBp8qrCUnbr6ODsNtqD0gd1VoD5xxZlhEEAf1+HxEh'
            . 'DEOMMS8bsbV29PJZD6Sg1+thjHFTU1PYhaXP/vmf/tG7RPQlL55eFd+gev2UPFkh'
            . 'yYWsKOObLeCH4OWqrtpqm+24c+eUKooEJ0K77zE9o3jXbYrElfCqWwkrVcQN0ASY'
            . 'oI3LeoF11tdaUy5HtNttarUaxhiSJBnRORwOR3GWtxnxbQZtvu+TbFj/lq1bHEVh'
            . 'xrbvOv22yw/9ytnv3vf4g2evV4HJGXTbbJtIidM+TraCTCF+A+Vb0HXGt17QveEa'
            . 'a8sWrR39Iaie4StHfI5erLNvV5V3vanB1EQFyR1OElA6VqpItDZYa6lWq7TbbcIw'
            . 'ZGxsjFarRb/fZ2xsbORwPBEhCALyfF3ftNaICLVajTzLyfNUBIMZnl06sCPpXDl2'
            . 'onF03pN9k5m68bIhM40Al1fBllGqgRONDkIYyxmbzFhdzrHOUgktn/sGfPYhj+3b'
            . 'Ak4s1xlv1nn37QlIJCYwypWDpSwZG1QrJXzfUljHzMwMvV5vXd89jzRNR1IIggAt'
            . 'IkRRNLKBPM+ZnJykWq1uxjcuTWL13MWrTkzNRs/edeMKv/bmVffma3vccqXDBCX+'
            . '/LGCx74XUyQ52niIVMA0qE+XaE54FLnHMFd8+/uWvHBUSkIlcjx+pGBhoUCJkzS2'
            . 'JHm00GFHMTFW1U5ERISVlRXGxsa0UkqLyCjc33Q+njGGtbU19u3bR6PRGIlJKUWW'
            . 'ZUw0x9AGs9qmcDt49OBO84a5RJBcWOsYfuueIadXWuzbXeb2YxV+4RbNvp3gaw2+'
            . 'T3PG0GtBP7OcW3ZoCjrdAbVul7wQVpZjZnWfJMvxPPdQZ6VFu2+NZyAvnBobGxdj'
            . 'jGu1WoiIaTabTiklw+EQYwx6kxNYz8yUUhshc0EQeHp5bajAuD67uNjd9VcVkw91'
            . 'oU1VGzn+kuOJozHkXVaWl/jmE/P83ucWOH1qBWwfyTNKfk6t6qhVLYFvSZKUQb/P'
            . '0uIiZ89doLeygI17Kh+sMS8HnhuYWZTxXeECF0aRDXzt8rzYdWD/gf3jY2N2OByS'
            . 'ZRlpmtLpdNZtIAxDlFKsrKwwPT3N8tISGE/1+qnbWl9UtfCMbrPVtbvVc2OefDcq'
            . 'BbdlqciNe0XduC/jqdPDjXiqoNPt8b/+2uMP3p9iVA9VJERRRrXmuHyH48S5hEG5'
            . 'Q5zkTI0HbKsWDLoFaEfJXbjiOnXvU8rp2d07fNVtJ1c/fP6N7z5w5Y13XPvqa8Nq'
            . 'pfSpv33g6x9ZWVkpfN9XYRiKp7Wm3+8zMTGx7lc9o4zvm+EgK26cfuSmg+GTH6zW'
            . 'w+1eaPqI3pVnU1uyIkJ8pWteztV7Yl5czOgPBtiiYDiM+frjHv/61oKDuxO0i6mG'
            . 'OUY7fuMXHY8eyVhYEcRlXLtLs60K5xfQcQJTwYX/sXWr/XUnSiqqaL7QvbbcdWMY'
            . 'z6M/zOzs1h3//s13vM3d+3/+7ENKaW2Msd6mEVtrKZdLamF+3gxTV7xxx4Ov2h9+'
            . '76vOVpvbZ0SCIFRE4/SSGmkWkqWQZxl3XD/k9GqHb35vyCAekqcxd9yg2T8jEBdA'
            . 'gR840gRu2A9/8RvCg88VTDUs/+JWyDLFIPbXszDxjOf725rVjFQaPHlxu8zubbiD'
            . 'l1yip6ZnWV5ZJfTUL22ZnfmdVrvTNcaoUTRaqVTUYBAjonjrHa//wPXXvO8/Hnvq'
            . 'oebU6u/bIGpDrQq1MVVVU6pmqgqryPtD8Ht8KPK4aqfmzGIfRcb7bndoK/RjiGNF'
            . 'XhiMB0km3HypcNvVgoosCCzNezjrQIHNhKgubutszucO7VKHjrbU23f2zSUH9qKV'
            . '1k/+6EdEgVdBR7WVlZPdPM+Vp5QiTmIVJ6m8NH9h6tbX3vTF/fsvu/X+R4+5F4+e'
            . 'kzvnKiYrBngqQJsKyq9DMAX4+OGQneEic9tTbr4iJusnFGlOEMDZBU0SG0QUQaQo'
            . 'eYLRQu4sxln8FLJc0R8q4gGghbAh1CZyPXARh0820KrgxIvH8bXCOsdLL52hEmT9'
            . 'one8W1iFOCeeE6cqYUXGL4yr5WDx3uePHrv12We/Hz//wg9NTa96192RqhlfkBSk'
            . 'LTw7L6zFjmsPanbPBnhehHg+5SpExrG8pFjpenhaUS4ZoopHFBkCT+H5Dm0KKFLE'
            . 'KdJMyHKF9iHwFUGgCMuaUiljWzNmvDnLxQvn+dhv/jZbt85w4kyLW/ecjQ763999'
            . 'pnT3Ed83ylMo47DFWY6/Y2x84rZut++OHTtWuvTAPr5zKOZbz81LE6fCUs7Zfo9P'
            . '/K2H8i0PPhlx7R7456/pM1NLyVJHv61RxmNmCvzQRwcRJiih/BC0ASkgHYLt4+wQ'
            . 'VIEohRLF156yPPKC5e7bPO68NeGanQs8fW4/LYl57NGHyaxm544d7Nn9pMxn13SS'
            . 'NF9/O4sIxjNEE2NXNcfHybPsiWuuvup5Z/P2TQdXX51k3m0XW+L2lYZ6pi7020NW'
            . 'h136nZAXjisOH0n4+Hu6TFYTorIjCjU6KkFUh7COmDoiASIGlaco20Y0mKBAp5Yi'
            . 'hzRXfOlJy7OnCo6dc4RRwJX757liy2ke70xRqowzFTl5+8Ejas9sd/7Y4ty8pwVt'
            . 'jHgCVivDvrldn19YXf5ynqVHt8xO64vLPbet0t1xcGrhsBcEU+22LapV8T78poQ/'
            . 'vK/HCycMzsHzP3S85zU5b7wmIwwEXSpDvQn+JM5MoP0m6AiVFRS2C8phoj5u2FvP'
            . 't52iXhZevVfz7ClYbDt+9Y8L/ugDhp+/8ns4u5uisFw5My+XTgzop82vdu3ODDcw'
            . 'RWGtZ7SRbm/A6XNnT4RhCKAWFpd1rVz2ZfIj50L7xx/a0zj5mb4tV1ZWrds7rvUn'
            . '7tY8fMRxfk3QwHQk9FuOMPQwxgddQrwJdGmaJKlx5ozm+EsJp84oBh3htXuF1+5V'
            . 'iICvIbfw9usMX3pCsdYXWj3HZ+5T3PObwrtvPI6y2pL4xqTy/Sfar/+vrYEjCj3n'
            . 'nOApBXmeYq3V9XqDtbU1V61Wi0ajXgyTgXqi/Y57V7PDz9wwc/hTeWhvW247F/pK'
            . '33k1RJFgnSYvhFZPMeWBb/W6uviKYy/mfPmxmPkVaHX7LC62mJ/v8JcPDvi123N+'
            . '+bVCORLWuppdTcVH7wz4vb/O6MeCLTRlJeSZlovLvhkP42zBXfofzueXD0Lf6Xpt'
            . '3JXLZbz1eug4xhjX6bSJogjnHOfPXyAKQ8nw9FH7jhdn4uydl5af/JSjdPdgKHZx'
            . 'GaMjCI2lHIErBIvCpSl6OOC+x1f5k4cKssKgJKff79Fut0kGLVZW+3zssyk37LZc'
            . 'thNqDUuvq3n3zYrr9gesDC2X7XTUvFx+tGRcQGpW4/H/9HT+1kf9sKqFnkvT9XjI'
            . '832fpaVlqtUaYRhirR0Vj/KioF6rOhiYw4M39Zd181cvUw+3S9gPDjDW02iHUkoJ'
            . '1gnxwOIHKUHS5i/vz3nmWIutE5o4yRnGQ/q9Ab1BTL+XUisXhGUhLAmhVeiKIBpe'
            . 't0UIPGGpI/LcSVVUfOerYOKTT3Tf8wdFsEWrYug8L3g5I/N9n7e+5S2cOXuWzTJj'
            . 'v9/fiIs8giBCa2UXFxfVWnhLXISVj73Kfnm65Llf6sa6ANHDIbpRaLQWPJMQBAV3'
            . '3zTkO0c9jhxbL4tkaQ6S06hY3nGD5UN3CpfthCIRktiw2jYUBZxfhrWOc0s9YfuU'
            . '9s9ctH/y37+WffTGt/nsm7Oysprg++tFN9hoMW3ZupUfvPDCKCotlUrroarnIeJw'
            . 'TnDOSlW19Ul+Lk3ipfcfsN++OD0VfmQhFmnFWOehraBEHEjKG6/O+OpvK77zvBDn'
            . 'QmEdW8eFGw4Iu3cISgtJVxOnmlZX0+ooBimy0hOxaD23heF3j8l/+d0v2D8syOUN'
            . 'fqEWFlfEWotSESsrKy9Xp4fDIe12m1KphLWWOI4JgoB6vY5Sil6vR61Wp1KpuDxL'
            . '1eeeVMn5o/Lrv3KbO3LrFep3vAp7zi85MUbJWgeZrKNKS6ImKsJ7XrdOrKcF4wlO'
            . 'hKQPvaGh09d0hopWR7l+LKrIYXocnWt++PtfLD56/2H7gIKgVJIsispuy5ZZOp3u'
            . 'KCOLomidgc0keTM/7nQ6owpwURSICHEcrxdttZHZiap6qof3u1/O/3T7t3nkl1/v'
            . 'feB1l6u7rZUti21hpa9U6IGnkEpJiVGoakOohGBQxImmO9Sy1laqcKjIE13zoA+r'
            . 'X3nafeXzj9k/W+nKjzyjmoWVrnUi1lp6vf6oSl6pVFBKrTNQFAVRFBGGIUVRUK/X'
            . '6XQ6DIdDGo0GjUaDwWDAcDikXC6z/8BB0eo+B1K7sMbg41/KP/GF76iH33Gjvuva'
            . '/fqW2XG3J08kMkqpvEANMqG/BlmqyTJF4Cu0RvmeoKxk51ty7rvH5NlvPO8eX+nJ'
            . 'C0rR15rQWhmsd7OcpGlKkiTkeT6qSGRZts6AiIwyMs/zCMOQSqWC1pp2u02j0RhV'
            . '7DZL7k4QINMKozTVMwty8ZN/Y/934NtHds3q/Tun2b6jyfad02p6rKzGyr4qWUHj'
            . 'kG7bDc4vy+pqT1qHT8nJk4ty1jpWgK7WFCIMnWOolCoQcVEU0Ww2KZfLrK2tURTF'
            . 'qA2gRIRut8vi4iKbpXalFGEYjjo2xpjRf0eOHOG9730vnU5HOecMEAAlragpxbh1'
            . '1IEqUAFKGyPyPAJfY6xDsoIMXh5KkWhFzwltEVpABxgopRIRyev1upuampJ7772X'
            . 'ubk5BoMBvu8zMzOzLoF6vU69XucfAmstxhhOnTpFq9XabMQ5IAeUE0AogFgr+kqt'
            . 'M+CEUASvKDAFaECUQpSiUJCLkDhhaIUBjMYQSEWkAFy325Vut8vY2BgzMzM450bN'
            . 'xJEK/X2d9s0KxabhbHYPN2Bzgd04SQcUQOaEAUK4IZ1gYx+tQAmwsV2x+fzGSID0'
            . 'FXO7gfPvELbZuh0Z8ebk74PN+z+hGb6JvNjYbJOZGPABs0G8AbRsdq/X120SmG9c'
            . '5xt4Nu//xE8GNtWcDeQ/FbySoQ2pbTKxuekmMSnrKrM5Xm6/r69xr/i1P3bvlXjX'
            . 'Jxua8OPwUzOwqWrOuR//ruKVKsUriH0l4X8H3U8i+B/a+5Wg/n+/Mtk8gVarxaFD'
            . 'h0bd+n9MuOmmmyiXy/+PNP4vayCEVf5dLz8AAAAASUVORK5CYII=';

        return $growl_logo;
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
