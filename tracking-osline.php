<?php

/**
 * Tracking OSLine
 *
 * @package     TrackingOSLine
 * @author      Henri Susanto
 * @copyright   2022 Henri Susanto
 * @license     GPL-2.0-or-later
 *
 * @wordpress-plugin
 * Plugin Name: Tracking OSLine
 * Plugin URI:  https://github.com/susantohenri/tracking-osline
 * Description: WordPress Plugin for tracking OSLine
 * Version:     1.0.0
 * Author:      Henri Susanto
 * Author URI:  https://github.com/susantohenri/
 * Text Domain: TrackingOSLine
 * License:     GPL v2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 */

add_shortcode('tracking-osline', function () {
    @session_start();
    $message = '';

    if (isset($_POST['login-osline'])) {
        $mysqli = new mysqli('osline.cloud', 'stuffing_admin', 'sonicist25', 'stuffing_gateway_2023');
        if ($mysqli->connect_errno) return "Failed to connect to MySQL: " . $mysqli->connect_error;

        $stmt = $mysqli->prepare("SELECT shipper_id FROM mstshipper WHERE shipper_id = ? AND password_no_encript = ?");
        $stmt->bind_param('ss', $_POST['username'], $_POST['password']);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        if (!is_null($user)) $_SESSION['osline-username'] = $user['shipper_id'];
        else $message = 'Error: login fail!<br>';
        $stmt->close();
        $mysqli->close();
    } else if (isset($_POST['logout'])) $_SESSION['osline-username'] = null;

    $username = isset($_SESSION['osline-username']) ? $_SESSION['osline-username'] : null;
    if (is_null($username)) {
        return "
            <form method='POST' style='text-align: center'>
                {$message}
                <input type='text' name='username' placeholder='username' required>
                <br>
                <input type='password' name='password' placeholder='password' required>
                <br>
                <input type='submit' name='login-osline' value='Login'>
            </form>
        ";
    } else {
        return "
            <table>
                <tr>
                    <td colspan='2'>
                        <form method='POST'>
                            <input type='text' name='code' placeholder='Tracking Code'>
                            <input type='submit' name='search' value='Search'>
                        </form>
                    </td>
                    <td>
                        <form method='POST'>
                            <input type='submit' name='logout' value='Log Out'>
                        </form>
                    </td>
                </tr>
            </table>
        ";
    }
});
