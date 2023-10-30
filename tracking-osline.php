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
    $tracking_result = "";

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
    if (isset($_POST['search'])) {
        $json = file_get_contents("https://gateway-cl.com/api/track_local?X-API-KEY=gateway-fms&si_number={$_POST['code']}&shipper={$username}");
        $json = json_decode($json);
        if (is_null($json)) $message = "Error: not found!";
        else {
            $last_status = '';
            $last_update = '';
            $departure_from = '';
            $arrival_at = '';
            $vessel_name = '';
            $from = '';
            $to = '';

            if (isset($json->header)) {
                if (isset($json->header[0])) {
                    $last_status = isset($json->header[0]->status) ? $json->header[0]->status : '';
                    $last_update = isset($json->header[0]->last_update) ? date('M d, Y H:i', strtotime($json->header[0]->last_update)) : '';
                }
            }
            if (isset($json->routing)) {
                if (isset($json->routing[0])) {
                    $from = isset($json->routing[0]->port_of_loading) ? strtoupper($json->routing[0]->port_of_loading) : '';
                    $to = isset($json->routing[0]->port_of_discharge) ? strtoupper($json->routing[0]->port_of_discharge) : '';
                    $departure_from = isset($json->routing[0]->time_of_departure) ? $json->routing[0]->time_of_departure : '';
                    $arrival_at = isset($json->routing[0]->time_of_discharge) ? $json->routing[0]->time_of_discharge : '';
                    $vessel_name = isset($json->routing[0]->vessel) ? $json->routing[0]->vessel : '';
                }
            }

            $tracking_result = "
                <tr>
                    <td colspan='3'>&nbsp;</td>
                </tr>
                <tr style='color: #5984b7'>
                    <td colspan='3'>Info Events</td>
                </tr>
                <tr>
                    <td>
                        Last Status<br>
                        <input type='text' value='{$last_status}' disabled>
                    </td>
                    <td>
                        Last Update<br>
                        <input type='text' value='{$last_update}' disabled>
                    </td>
                    <td>&nbsp;</td>
                </tr>
                <tr>
                    <td colspan='3'>&nbsp;</td>
                </tr>
                <tr style='color: #5984b7'>
                    <td colspan='3'>Routing Data</td>
                </tr>
                <tr style='background-color: #1e367c; color: white; text-align: center;'>
                    <td>Departure From</td>
                    <td>Arrival At</td>
                    <td>Vessel Name</td>
                </tr>
                <tr>
                    <td>
                        {$from}<br>
                        {$departure_from}
                    </td>
                    <td>
                        {$to}<br>
                        {$arrival_at}
                    </td>
                    <td>{$vessel_name}<br>&nbsp;</td>
                </tr>
                <tr>
                    <td colspan='3'>&nbsp;</td>
                </tr>
                <tr style='text-align:center'>
                    <td><div style='height: 25px;width: 25px;background-color: #f56502;border-radius: 50%;display: inline-block;'></div><br>{$from}</td>
                    <td><hr></td>
                    <td><div style='height: 25px;width: 25px;background-color: #f56502;border-radius: 50%;display: inline-block;'></div><br>{$to}</td>
                </tr>
            ";
        }
    }

    if (is_null($username)) {
        return "
            <form method='POST'>
                <table style='margin-left: auto; margin-right: auto'>
                    <tr style='background-color: #1e367c; color: white; text-align: center;'>
                        <td>Login</td>
                    </tr>
                    <tr style='background-color: #1e367c; color: white; text-align: center;'>
                        <td>
                            {$message}
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <input type='text' name='username' placeholder='username' required>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <input type='password' name='password' placeholder='password' required>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <input type='submit' name='login-osline' value='Login' style='float: right;background-color: #1e367c;color: white;padding: 5px'>
                        </td>
                    </tr>
                </table>
            </form>
        ";
    } else {
        return "
            <style>table[id=tracking-osline] td, table[id=tracking-osline] td input {padding: 5px;}</style>
            <table width='100%' id='tracking-osline'>
                <tr style='background-color: #1e367c; color: #d2ce60'>
                    <td colspan='3'>Tracking Shipment</td>
                </tr>
                <tr style='background-color: #1e367c;'>
                    <td colspan='3'>
                        <form method='POST'>
                            <input type='text' name='code' placeholder='Tracking Code'>
                            <input type='submit' name='search' value='Search'>
                            <input type='submit' name='logout' value='Log Out'>
                        </form>
                    </td>
                </tr>
                <tr style='background-color: #1e367c; color:white;'>
                    <td colspan='3'>{$message}</td>
                </tr>
                {$tracking_result}
            </table>
        ";
    }
});
