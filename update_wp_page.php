<?php
/*
  Plugin Name: Update WP Page
  Plugin URI: https://github.com/hiorgserver/update_wp_page
  Description: Ersetzt Seiteninhalte
  Version: 0.1
  Author: HiOrg Server GmbH
  Author URI: http://www.hiorg-server.de
  License: MIT
 */

add_action('init', 'update_wp_page_init');

function update_wp_page_init() {
    add_shortcode("update_wp_page","update_wp_page_process");
}

function update_wp_page_fetchdata() {
//    $url = "http://www.hiorg-server.de/info2/referenzen.php?json=1";
    $url = "https://my-json-server.typicode.com/hiorgserver/update_wp_page/db";
    $response = wp_remote_get($url);

    if (is_array($response) && !is_wp_error($response)) {
        $data = $response["body"];
        if (!empty($data)) {
            $ret = json_decode($data);
            if (JSON_ERROR_NONE === json_last_error()) {
                return $ret;
            }
        }
    }
    return false;
}

function update_wp_page_buildreplaceary($json) {
    if ($json["status"]=="OK") {
        $zuordnung = ["ue821" => "orga", "ue80b" => "user", "ue83d" => "veranst"];
        $ret = [];
        foreach ($zuordnung as $icon => $key) {
            $pattern = "/(\\[av_animated_numbers[^\\]]* number=')([^']+)('[^\\]]* icon='$icon')/i";
            $num = number_format($json[$key], 0, ",", ".");
            $ret[$pattern] = '${1}' . $num . '$3';
        }
        return $ret;
    }
    return false;
}

function update_wp_page_replacedata($page_id, $replace) {
    $post = get_post($page_id);
    if ($post instanceof WP_Post) {
        $content = $post->post_content;
        if (!empty($content)) {
            foreach ($replace as $pattern => $replacement) {
                $content = preg_replace($pattern, $replacement, $content);
            }
            $id = wp_update_post(array("ID" => $page_id, "post_content" => $content), true);
            return !is_wp_error($id);
        }
    }
    return false;
}

function update_wp_page_process($atts) {
    extract(shortcode_atts(array("page_id" => "x"), $atts));
    if (!is_numeric($page_id)) {
        return "ERROR: invalid page_id";
    }

    $data = update_wp_page_fetchdata();

    if (false === $data) {
        return "ERROR: konnte Daten nicht abrufen";
    }

    $replace = update_wp_page_buildreplaceary($data);

    if (!is_array($replace)) {
        return "ERROR: Daten konnten nicht gelesen werden";
    }

    return update_wp_page_replacedata($page_id, $replace);
}
