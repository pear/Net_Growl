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
 * @version  SVN: $Id:$
 * @link     http://growl.laurent-laville.org/
 * @link     http://pear.php.net/package/Net_Growl
 * @since    File available since Release 0.9.0
 */

/**
 * Application object for {@link Net_Growl}
 *
 * This object represents an application containing the notifications
 * to be registered by {@link http://growl.info Growl}. Feel free to use
 * your own application object as long as it implements the few public
 * getter methods:
 * - {@link Net_Growl_Application::getGrowlNotifications()}
 * - {@link Net_Growl_Application::getGrowlName()}
 * - {@link Net_Growl_Application::getGrowlPassword()}
 *
 * @category Networking
 * @package  Net_Growl
 * @author   Laurent Laville <pear@laurent-laville.org>
 * @author   Bertrand Mansion <bmansion@mamasam.com>
 * @license  http://www.opensource.org/licenses/bsd-license.php  BSD
 * @version  Release: @package_version@
 * @link     http://growl.laurent-laville.org/
 * @link     http://pear.php.net/package/Net_Growl
 * @since    Class available since Release 0.9.0
 */
class Net_Growl_Application
{
    /**
     * Name of application to be registered by Growl
     * @var string
     */
    private $_growlAppName;

    /**
     * Password for notifications
     * @var string
     */
    private $_growlAppPassword = '';

    /**
     * Name of application to be registered by Growl
     * @var string
     */
    private $_growlAppIcon = '';

    /**
     * Array of notifications
     * @var array
     */
    private $_growlNotifications = array();

    /**
     * Constructor
     * Constructs a new application to be registered by Growl
     *
     * @param string $appName       Application name
     * @param array  $notifications Array of notifications
     * @param string $password      (optional) Password to be used to notify Growl
     * @param string $appIcon       (optional) Application icon
     *
     * @return void
     * @see    addGrowlNotifications()
     */
    public function __construct($appName, $notifications, $password = '',
        $appIcon = ''
    ) {
        $this->_growlAppName = $appName;
        $this->_growlAppPassword = (empty($password)) ? '' : $password;
        $this->_growlAppIcon = $appIcon;
        if (!empty($notifications) && is_array($notifications)) {
            $this->addGrowlNotifications($notifications);
        }
    }

    /**
     * Adds notifications supported by this application
     *
     * Expected array format is:
     * <pre>
     * array('notification name' => array('option name' => 'option value'))
     * </pre>
     * At the moment, only option name 'enabled' is supported for UDP. Example:
     * <code>
     * $notifications = array('Test Notification' => array('enabled' => true));
     * </code>
     *
     * @param array $notifications Array of notifications to support
     *
     * @return void
     */
    public function addGrowlNotifications($notifications)
    {
        if (!is_array($notifications)) {
            // wrong parameter
            return;
        }

        $default = array('enabled' => true);
        foreach ($notifications as $name => $options) {
            if (is_int($name)) {
                $name = $options;
                $options = $default;
            } elseif (is_array($options)) {
                $options = array_merge($default, $options);
            }
            $this->_growlNotifications[$name] = $options;
        }
    }

    /**
     * Returns the notifications accepted by Growl for this application
     *
     * Expected array format is:
     * <pre>
     * array('notification name' => array('option name' => 'option value'))
     * </pre>
     * At the moment, only option name 'enabled' is supported. Example:
     * <code>
     * $notifications = array('Test Notification' => array('enabled' => true));
     * return $notifications;
     * </code>
     *
     * @return array notifications
     */
    public function getGrowlNotifications()
    {
        return $this->_growlNotifications;
    }

    /**
     * Returns the application name for registration in Growl
     *
     * @return string application name
     */
    public function getGrowlName()
    {
        return $this->_growlAppName;
    }

    /**
     * Returns the password to be used by Growl to accept notification packets
     *
     * @return string password
     */
    public function getGrowlPassword()
    {
        return $this->_growlAppPassword;
    }

    /**
     * Returns the application icon for registration in Growl
     *
     * @return string application icon (valid url) or empty if default image
     */
    public function getGrowlIcon()
    {
        return $this->_growlAppIcon;
    }
}
