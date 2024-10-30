<?php
/*
  Plugin Name: IBS GCal Events
  Plugin URI: http://wordpress.org/extend/plugins/
  Description: Lists Google Calendar V3 Events plugin
  Author: HMoore71
  Version: 2.1
  Author URI: http://indianbendsolutions.net
  License: GPL2
  License URI: none
 */

/*
  This program is distributed in the hope that it will be useful, but
  WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 */

define('IBS_GCAL_EVENTS_VERSION', '2.1');
register_activation_hook(__FILE__, 'ibs_gcal_events_defaults');

function ibs_gcal_events_defaults() {
    IBS_GCAL_EVENTS::defaults();
}

register_deactivation_hook(__FILE__, 'ibs_gcal_events_deactivate');

function ibs_gcal_events_deactivate() {
    delete_option('ibs_gcal_events_options');
}

class IBS_GCAL_EVENTS {

    static $add_script = 0;
    static $options = array();
    static $options_defaults = array(
        "version" => IBS_GCAL_EVENTS_VERSION,
        "feedCount" => 3,
        "feeds" => array(
            "feed_1" => array('name' => 'Google Holidays', 'enabled' => false, 'url' => 'en.usa#holiday@group.v.calendar.google.com', 'key' => '', 'text_color' => 'white', 'background_color' => '#5484ed', 'nolink' => false, 'nodesc' => false, 'altlink' => ''),
            "feed_2" => array('name' => '', 'enabled' => false, 'url' => '', 'key' => '', 'text_color' => 'white', 'background_color' => '#5484ed', 'nolink' => false, 'nodesc' => false, 'altlink' => ''),
            "feed_3" => array('name' => '', 'enabled' => false, 'url' => '', 'key' => '', 'text_color' => 'white', 'background_color' => '#5484ed', 'nolink' => false, 'nodesc' => false, 'altlink' => '')),
        "width" => "100%",
        "height" => "auto",
        "align" => "alignleft",
        "dateFormat" => "MMM DD, YYYY",
        "timeFormat" => "HH:mm",
        "max" => 100,
        "descending" => false,
        "legend"=> false,
        "start" => 'now',
        "qtip" => array(
            'style' => "qtip-bootstrap",
            'rounded' => false,
            'shadow' => false,
            'title' => '<p>%title%</p>',
            'location' => '<p>%location%</p>',
            'time' => '<p>%time%</p>',
            'description' => '<p>%description%</p>',
            'order' => '%title% %location% %description% %time%'
        )
    );

    static function extendA($a, &$b) {
        foreach ($a as $key => $value) {
            if (!isset($b[$key])) {
                $b[$key] = $value;
            }
            if (is_array($value)) {
                self::extendA($value, $b[$key]);
            }
        }
    }

    static function fixBool(&$item, $key) {
        switch (strtolower($item)) {
            case "null" : $item = null;
                break;
            case "true" :
            case "yes" : $item = true;
                break;
            case "false" :
            case "no" : $item = false;
                break;
            default :
        }
    }

    static function init() {
        self::$options = get_option('ibs_gcal_events_options');
        if (isset(self::$options['version']) === false || self::$options['version'] !== IBS_GCAL_EVENTS_VERSION) {
            self::defaults();  //development set new options
        } else {
            self::extendA(self::$options_defaults, self::$options);
            array_walk_recursive(self::$options, array(__CLASS__, 'fixBool'));
        }
        add_action('admin_init', array(__CLASS__, 'admin_options_init'));
        add_action('admin_menu', array(__CLASS__, 'admin_add_page'));
        add_action('wp_enqueue_scripts', array(__CLASS__, 'enqueue_scripts'));
        add_action('admin_enqueue_scripts', array(__CLASS__, 'admin_enqueue_scripts'));
        add_shortcode('ibs-gcal-events', array(__CLASS__, 'handle_shortcode'));
        add_action('init', array(__CLASS__, 'register_script'));
        add_action('wp_head', array(__CLASS__, 'print_script_header'));
        add_action('wp_footer', array(__CLASS__, 'print_script_footer'));
        add_action('admin_print_scripts', array(__CLASS__, 'print_admin_scripts'));
    }

    static function defaults() { //json_encode requires double quotes
        $options = get_option('ibs_gcal_events_options');
        self::extendA(self::$options_defaults, $options);
        array_walk_recursive($options, array(__CLASS__, 'fixBool'));
        if (IBS_GCAL_EVENTS_VERSION === '2.1' && $options['version'] !== '2.1') {
            $options['feeds']['feed_2'] = array(
                'name' => 'unkown',
                'enabled' => true,
                'url' => $options['calendar'],
                'key' => $options['api'],
                'text_color' => 'white',
                'background_color' => '#5484ed',
                'nolink' => $options['no_link'],
                'nodesc' => $options['no_desc'],
                'altlink' => $options['alt_link']
            );
        }
        $options['version'] = IBS_GCAL_EVENTS_VERSION;
        self::$options = $options;
        update_option('ibs_gcal_events_options', $options);
    }

    static function admin_options_init() {
        register_setting('ibs_gcal_events_options', 'ibs_gcal_events_options');
        add_settings_section('section-gcal', '', array(__CLASS__, 'admin_general_header'), 'gcal-events');
        add_settings_field('feedCount', 'Feed count', array(__CLASS__, 'field_feedCount'), 'gcal-events', 'section-gcal');
        add_settings_field('feeds', 'Feeds', array(__CLASS__, 'field_feeds'), 'gcal-events', 'section-gcal');
        add_settings_field('start', 'Starting Date', array(__CLASS__, 'field_start'), 'gcal-events', 'section-gcal');
        add_settings_field('max', 'Max Events', array(__CLASS__, 'field_max'), 'gcal-events', 'section-gcal');
        add_settings_field('align', 'Align', array(__CLASS__, 'field_align'), 'gcal-events', 'section-gcal');
        add_settings_field('height', 'Height', array(__CLASS__, 'field_height'), 'gcal-events', 'section-gcal');
        add_settings_field('width', 'Width', array(__CLASS__, 'field_width'), 'gcal-events', 'section-gcal');
        add_settings_field('timeFormat', 'Time Format', array(__CLASS__, 'field_timeFormat'), 'gcal-events', 'section-gcal');
        add_settings_field('dateFormat', 'Date Format', array(__CLASS__, 'field_dateFormat'), 'gcal-events', 'section-gcal');
        add_settings_field('descending', 'Sort descending', array(__CLASS__, 'field_descending'), 'gcal-events', 'section-gcal');
        add_settings_field('legend', 'Show legend', array(__CLASS__, 'field_legend'), 'gcal-events', 'section-gcal');

        add_settings_section('section-gcal-qtip', '', array(__CLASS__, 'admin_general_qtip_header'), 'gcal-events-qtip');
        add_settings_field('rounded', 'Rounded', array(__CLASS__, 'field_qtip_rounded'), 'gcal-events-qtip', 'section-gcal-qtip');
        add_settings_field('shadow', 'Shadow', array(__CLASS__, 'field_qtip_shadow'), 'gcal-events-qtip', 'section-gcal-qtip');
        add_settings_field('style', 'Style', array(__CLASS__, 'field_qtip_style'), 'gcal-events-qtip', 'section-gcal-qtip');

        add_settings_field('content', 'Content', array(__CLASS__, 'field_qtip_content_bar'), 'gcal-events-qtip', 'section_gcal-qtip');
        add_settings_field('title', 'Title', array(__CLASS__, 'field_qtip_content_title'), 'gcal-events-qtip', 'section-gcal-qtip');
        add_settings_field('location', 'Location', array(__CLASS__, 'field_qtip_content_location'), 'gcal-events-qtip', 'section-gcal-qtip');
        add_settings_field('description', 'Description', array(__CLASS__, 'field_qtip_content_description'), 'gcal-events-qtip', 'section-gcal-qtip');
        add_settings_field('time', 'Time', array(__CLASS__, 'field_qtip_content_time'), 'gcal-events-qtip', 'section-gcal-qtip');
        add_settings_field('order', 'Order', array(__CLASS__, 'field_qtip_content_order'), 'gcal-events-qtip', 'section-gcal-qtip');
    }

    static function admin_general_header() {
        echo '<div class="ibs-admin-bar">Default settings</div>';
    }

    static function admin_general_qtip_header() {
        echo '<div class="ibs-admin-bar">Qtip settings</div>';
    }

    static function colors($feed, $color) {
        $template = array(
            '<div class="feed-color-box %c" rel="%f" style="background-color:#5484ed;"></div>',
            '<div class="feed-color-box %c" rel="%f" style="background-color:#a4bdfc;"></div>',
            '<div class="feed-color-box %c" rel="%f" style="background-color:#46d6db;"></div>',
            '<div class="feed-color-box %c" rel="%f" style="background-color:#7ae7bf;"></div>',
            '<div class="feed-color-box %c" rel="%f" style="background-color:#51b749;"></div>',
            '<div class="feed-color-box %c" rel="%f" style="background-color:#fbd75b;"></div>',
            '<div class="feed-color-box %c" rel="%f" style="background-color:#ffb878;"></div>',
            '<div class="feed-color-box %c" rel="%f" style="background-color:#ff887c;"></div>',
            '<div class="feed-color-box %c" rel="%f" style="background-color:#dc2127;"></div>',
            '<div class="feed-color-box %c" rel="%f" style="background-color:#dbadff;"></div>',
            '<div class="feed-color-box %c" rel="%f" style="background-color:#e1e1e1;"></div>'
        );
        $cstr = $template;
        for ($str = 0; $str < count($cstr); $str++) {
            $cstr[$str] = str_replace('%f', $feed, $cstr[$str]);
            if (strpos($cstr[$str], $color) > 0) {
                $cstr[$str] = str_replace('%c', 'feed-color-box-selected', $cstr[$str]);
            } else {
                $cstr[$str] = str_replace('%c', '', $cstr[$str]);
            }
        }
        return implode('', $cstr);
    }

    static function field_feedCount() {
        $value = self::$options['feedCount'];
        echo '<input name="ibs_gcal_events_options[feedCount]" min="1" max="10" step="1" placeholder="number of feeds" type="number" value="' . $value . '" />';
    }

    static function field_feeds() {
        for ($feed = 1; $feed <= self::$options['feedCount']; $feed++) {
            $curr_feed = "feed_" . $feed;
            $bg = isset(self::$options['feeds'][$curr_feed]['backgroundColor']) ? self::$options['feeds'][$curr_feed]['backgroundColor'] : '#5484ed';
            $fg = isset(self::$options['feeds'][$curr_feed]['textColor']) ? self::$options['feeds'][$curr_feed]['textColor'] : '#ffffff';
            $color = "style='background-color:$bg; color:$fg;'";
            $value = isset(self::$options['feeds'][$curr_feed]['name']) ? self::$options['feeds'][$curr_feed]['name'] : '';
            echo "<div class='ibs-admin-bar' ><span>&nbsp;Feed $feed</span></div>";
            echo "<div class='feed-div'><span>Name</span><input id='ibs-feed-name-$feed' name='ibs_gcal_events_options[feeds][$curr_feed][name]' type='text' placeholder='feed name' size='25' " . $color . " value='$value' />" . self::colors($feed, $bg) . "</div>";

            $checked = isset(self::$options['feeds'][$curr_feed]['enabled']) && self::$options['feeds'][$curr_feed]['enabled'] == 'yes' ? 'checked' : '';
            echo "<div class='feed-div'><span>Enabled</span><input name='ibs_gcal_events_options[feeds][$curr_feed][enabled]' value='yes' $checked type='checkbox'/></div>";

            $value = isset(self::$options['feeds'][$curr_feed]['url']) ? self::$options['feeds'][$curr_feed]['url'] : '';
            echo "<div class='feed-div' ><span>ID</span><input id='ibs-feed-url-$feed'name='ibs_gcal_events_options[feeds][$curr_feed][url]' type='text' placeholder='Google Calendar Address (XML or Calendar ID)' size='100' value='$value' /></div>";

            $value = isset(self::$options['feeds'][$curr_feed]['key']) ? self::$options['feeds'][$curr_feed]['key'] : '';
            echo "<div class='feed-div' ><span>Key</span><input id='ibs-feed-key-$feed'name='ibs_gcal_events_options[feeds][$curr_feed][key]' type='text' placeholder='Optional Google API Key' size='100' value='$value' /></div>";

            $checked = isset(self::$options['feeds'][$curr_feed]['nolink']) && self::$options['feeds'][$curr_feed]['nolink'] == 'yes' ? 'checked' : '';
            echo "<div class='feed-div'><span>No-link</span><input name='ibs_gcal_events_options[feeds][$curr_feed][nolink]' value='yes' $checked type='checkbox'/><span style='width:auto;'> suppress linking</span></div>";

            $checked = isset(self::$options['feeds'][$curr_feed]['nodesc']) && self::$options['feeds'][$curr_feed]['nodesc'] == 'yes' ? 'checked' : '';
            echo "<div class='feed-div'><span>No-desc</span><input name='ibs_gcal_events_options[feeds][$curr_feed][nodesc]' value='yes' $checked type='checkbox'/><span style='width:auto;'> suppress display of description</span></div>";

            $value = isset(self::$options['feeds'][$curr_feed]['altlink']) ? self::$options['feeds'][$curr_feed]['altlink'] : '';
            echo "<div class='feed-div' ><span>Alt-Link</span><input name='ibs_gcal_events_options[feeds][$curr_feed][altlink]' type='text' placeholder='Event alternate link' size='100' value='$value' /></div>";

            $value = isset(self::$options['feeds'][$curr_feed]['color']) ? self::$options['feeds'][$curr_feed]['textColor'] : '#ffffff';
            echo "<input id='colorpicker-fg-$feed' type='hidden' feed='#ibs-feed-url-$feed' css='color' name='ibs_gcal_events_options[feeds][$curr_feed][textColor]' value='" . $value . "' />";
            $value = isset(self::$options['feeds'][$curr_feed]['backgroundColor']) ? self::$options['feeds'][$curr_feed]['backgroundColor'] : '#5484ed';
            echo "<input id='colorpicker-bg-$feed'type='hidden' class='ibs-colorpicker' feed='#ibs-feed-url-$feed' css='background-color' name='ibs_gcal_events_options[feeds][$curr_feed][backgroundColor]' value='$value' />";
            echo "<div style='width:100%; height:20px; margin-bottom:30px';> </div>";
        }
    }

    static function field_start() {
        $value = self::$options['start'];
        echo "<input id='gcal-start-date' type='text' name='ibs_gcal_events_options[start]'  value='$value'  />" . '<a href="#" class="gcal-help" rel="start">help</a>';
    }

    static function field_descending() {
        $checked = self::$options['descending'] ? "checked" : '';
        echo '<input type="checkbox" name="ibs_gcal_events_options[descending]" value="true"' . $checked . ' / >';
    }

    static function field_legend() {
        $checked = self::$options['legend'] ? "checked" : '';
        echo '<input type="checkbox" name="ibs_gcal_events_options[legend]" value="true"' . $checked . ' / >';
    }

    static function field_align() {
        echo '<select name = "ibs_gcal_events_options[align]" />';
        $selected = self::$options['align'] == "alignleft" ? 'selected' : '';
        echo '<option value = "alignleft" ' . $selected . '>left</option>';
        $selected = self::$options['align'] == "aligncenter" ? 'selected' : '';
        echo '<option value = "aligncenter" ' . $selected . '>center</option>';
        $selected = self::$options['align'] == "alignright" ? 'selected' : '';
        echo '<option value="alignright" ' . $selected . '>right</option>';
        echo '</select>';
    }

    static function field_max() {
        $value = self::$options['max'];
        echo "<input type='number' name='ibs_gcal_events_options[max]' value='$value' />";
    }

    static function field_width() {
        $value = self::$options['width'];
        echo '<input name = "ibs_gcal_events_options[width]" type = "text" size = "25" value = "' . $value . '"/>';
    }

    static function field_height() {
        $value = self::$options['height'];
        echo '<input name = "ibs_gcal_events_options[height]" type = "text" size = "25" value = "' . $value . '"/>';
    }

    static function field_dateFormat() {
        $value = self::$options['dateFormat'];
        echo '<input name = "ibs_gcal_events_options[dateFormat]" type = "text" size = "25" value = "' . $value . '"/><a href="#" class="gcal-help" rel="date-help">help</a>';
    }

    static function field_timeFormat() {
        $value = self::$options['timeFormat'];
        echo '<input name = "ibs_gcal_events_options[timeFormat]" type = "text" size = "25" value = "' . $value . '"/><a href="#" class="gcal-help" rel="time-help">help</a>';
    }

    static function field_qtip_rounded() {
        $checked = self::$options['qtip']['shadow'] ? "checked" : '';
        echo '<input type="checkbox" name="ibs_gcal_events_options[qtip][shadow]" value="qtip-rounded"' . $checked . '/>';
    }

    static function field_qtip_shadow() {
        $checked = self::$options['qtip']['rounded'] ? "checked" : '';
        echo '<input type="checkbox" name="ibs_gcal_events_options[qtip][rounded]" value="qtip-shadow"' . $checked . '/>';
    }

    static function field_qtip_style() {
        echo "<select name='ibs_events_options[list][qtip][style]'> ";
        $value = self::$options['qtip']['style'];
        $selected = $value === '' ? "selected" : '';
        echo "<option id='qtip-none'     $selected  value=''  selected >none</option>";
        $selected = $value === 'qtip-light' ? "selected" : '';
        echo "<option id='qtip-light'    $selected value='qtip-light' >light coloured style</option>";
        $selected = $value === 'qtip-dark' ? "selected" : '';
        echo "<option id='qtip-dark'     $selected value='qtip-dark' >dark style</option>";
        $selected = $value === 'qtip-cream' ? "selected" : '';
        echo "<option id='qtip-cream'    $selected value='qtip-cream' >cream</option>";
        $selected = $value === 'qtip-red' ? "selected" : '';
        echo "<option id='qtip-red'      $selected value='qtip-red' >Alert-ful red style </option>";
        $selected = $value === 'qtip-green' ? "selected" : '';
        echo "<option id='qtip-green'   $selected value='qtip-green' >Positive green style </option>";
        $selected = $value === 'qtip-blue' ? "selected" : '';
        echo "<option id='qtip-blue'     $selected value='qtip-blue' >Informative blue style </option>";
        $selected = $value === 'qtip-bootstrap' ? "selected" : '';
        echo "<option id='qtip-bootstrap'$selected value='qtip-bootstrap' >Twitter Bootstrap style </option>";
        $selected = $value === 'qtip-youtube' ? "selected" : '';
        echo "<option id='qtip-youtube'  $selected value='qtip-youtube' >Google's new YouTube style</option>";
        $selected = $value === 'qtip-tipsy' ? "selected" : '';
        echo "<option id='qtip-tipsy'    $selected value='qtip-tipsy' >Minimalist Tipsy style </option>";
        $selected = $value === 'qtip-tipped' ? "selected" : '';
        echo "<option id='qtip-tipped'   $selected value='qtip-tipped' >Tipped libraries</option>";
        $selected = $value === 'qtip-jtools' ? "selected" : '';
        echo "<option id='qtip-jtools'   $selected value='qtip-jtools' >Tools tooltip style </option>";
        $selected = $value === 'qtip-cluetip' ? "selected" : '';
        echo "<option id='qtip-cluetip'  $selected value='qtip-cluetip' >Good ole'' ClueTip style </option>";
        echo "</select>";
    }

    static function field_qtip_content_bar() {
        echo '<div class="ibs-admin-bar" >Qtip content settings</div>';
    }

    static function field_qtip_content_title() {
        if (isset(self::$options['qtip']['title'])) {
            $value = self::$options['qtip']['title'];
        } else {
            $value = '<p>%title%</p>';
        }
        echo '<input name="ibs_gcal_events_options[qtip][title]" type="text" size="100" value="' . $value . '" />';
    }

    static function field_qtip_content_location() {
        if (isset(self::$options['qtip']['location'])) {
            $value = self::$options['qtip']['location'];
        } else {
            $value = '<p>%location%</p>';
        }
        echo '<input name="ibs_gcal_events_options[qtip][location]" type="text" size="100" value="' . $value . '" />';
    }

    static function field_qtip_content_description() {
        if (isset(self::$options['qtip']['description'])) {
            $value = self::$options['qtip']['description'];
        } else {
            $value = '<p>%description%</p>';
        }
        echo '<input name="ibs_gcal_events_options[qtip][description]" type="text" size="100" value="' . $value . '" />';
    }

    static function field_qtip_content_time() {
        if (isset(self::$options['qtip']['time'])) {
            $value = self::$options['qtip']['time'];
        } else {
            $value = '<p>%time%</p>';
        }
        echo '<input name="ibs_gcal_events_options[qtip][time]" type="text" size="100" value="' . $value . '" />';
    }

    static function field_qtip_content_order() {
        if (isset(self::$options['qtip']['order'])) {
            $value = self::$options['qtip']['order'];
        } else {
            $value = '%title% %location% %description% %time%';
        }
        echo '<input name="ibs_gcal_events_options[qtip][order]" type="text" size="100" value="' . $value . '" />';
    }

    static function admin_add_page() {
        add_options_page('IBS GCAL Events', 'IBS GCAL Events', 'manage_options', 'ibs_gcal_events', array(__CLASS__, 'admin_options_page'));
    }

    static function admin_options_page() {
        ?>
        <script type="text/javascript">
            jQuery(document).ready(function ($) {
                $('#gcal-start-date').datepicker({dateFormat: 'yy-mm-dd'})
            });
        </script> 
        <form action="options.php" method="post">
            <?php settings_fields('ibs_gcal_events_options'); ?>
            <div>
                <?php do_settings_sections('gcal-events'); ?>
                <?php do_settings_sections('gcal-events-qtip'); ?>
                <?php submit_button(); ?>
            </div>
        </form>
        <?PHP
    }

    static function handle_shortcode($atts, $content = null) {
        self::$add_script += 1;
        $args = self::$options;
        if (is_array($atts)) {
            foreach ($args as $key => $value) {
                if (isset($atts[strtolower($key)])) {
                    $args[$key] = $atts[strtolower($key)];
                }
            }
        }
        $args['feeds_set'] = '1,2,3';
        $args['id'] = self::$add_script;
        $id = self::$add_script;
        ob_start();
        echo '<div id="ibs-gcal-events-' . $id . '" class="' . $args['align'] . ' gcal-events" style="width:' . $args['width'] . '; height:auto" ></div>';
        ?>
        <script type="text/javascript">
            jQuery(document).ready(function ($) {
                new IBS_GCAL_EVENTS(<?PHP echo json_encode($args); ?>, 'shortcode');
            });
        </script> 
        <?PHP
        $output = ob_get_contents();
        ob_end_clean();
        return $output;
    }

    static function register_script() {
        $min = '.min';
        wp_register_style('ibs-map-ui-theme-style', plugins_url("css/smoothness/jquery-ui.min.css", __FILE__));
        wp_register_style('gcal-events-style', plugins_url("css/gcal-events.css", __FILE__));
        wp_register_script("gcal-admin-script", plugins_url("js/admin$min.js", __FILE__));
        wp_register_script('gcal-events-script', plugins_url("js/gcal-events$min.js", __FILE__), self::$core_handles);
        wp_register_script('ibs-moment-script', plugins_url("js/moment.min.js", __FILE__));
        wp_register_style('gcal-admin-style', plugins_url("css/admin.css", __FILE__));
        wp_register_style('ibs-qtip_style', plugins_url("css/jquery.qtip.css", __FILE__));
        wp_register_script('ibs-qtip-script', plugins_url("js/jquery.qtip.min.js", __FILE__));
    }

    static $core_handles = array(
        'jquery',
        'json2',
        'jquery-ui-core',
        'jquery-ui-dialog',
        'jquery-ui-datepicker',
        'jquery-ui-widget',
        'jquery-ui-sortable',
        'jquery-ui-draggable',
        'jquery-ui-droppable',
        'jquery-ui-selectable',
        'jquery-ui-position',
        'jquery-ui-tabs'
    );
    static $script_handles = array(
        'gcal-events-script',
        'ibs-moment-script',
        'ibs-qtip-script'
    );
    static $admin_scripts = array('ibs-dropdown-script', 'gcal-admin-script');
    static $admin_styles = array('ibs-map-ui-theme-style', 'ibs-dropdown-style', 'gcal-admin-style');
    static $style_handles = array(
        'ibs-dropdown-style',
        'gcal-events-style',
        'ibs-qtip-style'
    );

    static function enqueue_scripts() {
        foreach (self::$core_handles as $handle) {
            wp_enqueue_script($handle);
        }
        if (is_active_widget('', '', 'ibs_wgcal_events', true)) {
            self::print_admin_scripts();
            wp_enqueue_style(self::$style_handles);
            wp_enqueue_script(self::$script_handles);
        }
    }

    static function admin_enqueue_scripts($page) {
        if ($page === 'settings_page_ibs_gcal_events') {
            wp_enqueue_style(self::$admin_styles);
            wp_enqueue_style(self::$style_handles);
            wp_enqueue_script(self::$script_handles);
            wp_enqueue_script(self::$admin_scripts);
        }
    }

    static function print_admin_scripts() {
        ?>
        <?PHP
    }

    static function print_script_header() {
        
    }

    static function print_script_footer() {
        if (self::$add_script > 0) {
            self::print_admin_scripts();
            wp_print_styles(self::$style_handles);
            wp_print_scripts(self::$script_handles);
        }
    }

}

IBS_GCAL_EVENTS::init();
include( 'lib/widget-gcal-events.php' );
