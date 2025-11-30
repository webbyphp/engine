<?php

/**
 * This file is part of WebbyPHP Framework.
 *
 * (c) Kwame Oteng Appiah-Nti <developerkwame@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Clean Expired Sessions for WebbyPHP
 * 
 */

namespace Base\Session;


class Prune
{
    /**
     * Force cleanup expired sessions
     * This happens when using session from database
     */
    public function databaseSessions()
    {
        if (config_item('sess_driver') != 'database') {
            return;
        }

        $expire = (time() - config_item('sess_expiration'));

        get_instance()->load->database();
        get_instance()->db->where("timestamp < {$expire}");
        $delete = get_instance()->db->delete(config_item('sess_save_path'));

        log_message('debug', 'Session garbage collection performed on database.');

        return ($delete) ? true : false;
    }

    /**
     * Force cleanup expired sessions
     * This happens when using session from files
     */
    public function fileSessions()
    {

        $sessionPath = SESSION_SAVE_PATH . DS;

        $handle = opendir($sessionPath);

        while (($file = readdir($handle)) !== false) {
            //Leave the directory protection alone
            if ($file == '.htaccess' || $file == 'index.html') {
                continue;
            }

            $lastmodified = filemtime($sessionPath . $file);

            //24 hours in a day * 3600 seconds per hour
            if ((time() - $lastmodified) > config_item('sess_expiration')) {
                @unlink($sessionPath . $file);
            }
        }

        closedir($handle);

        log_message('debug', 'Session garbage collection performed on files.');

        return true;
    }
}
/* end of file ./engine/Core/Session/Session.php */
