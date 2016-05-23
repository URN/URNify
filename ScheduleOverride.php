<?php

class ScheduleOverride {
    private static $database_table_name = 'urnify_schedule_override';

    public static function init() {
        add_action('admin_menu', function () {
            add_menu_page(
                'Schedule Override',
                'Sched. Override',
                'upload_files',
                'schedule-override',
                'ScheduleOverride::get_page_content',
                'dashicons-slides',
                22
            );
        });

        register_activation_hook( __FILE__, 'ScheduleOverride::create_database_table');

        add_action('admin_post_add_schedule_override', function () {
            $valid = true;
            foreach (array(
                    'title',
                    'description',
                    'startDate',
                    'startTime',
                    'endTime',
                    'endDate') as $param) {
                if (!isset($_POST[$param])) {
                    $valid = false;
                }

                $_POST[$param] = trim($_POST[$param]);

                if ($_POST[$param] == null || $_POST[$param] == '') {
                    $valid = false;
                }
            }

            if (!$valid) {
                wp_redirect(admin_url('admin.php?page=schedule-override&error_message=Not%20all%20fields%20were%20completed'));
                return;
            }

            $title = trim($_POST['title']);
            $description = trim($_POST['description']);

            $start = DateTime::createFromFormat('d/m/Y', trim($_POST['startDate']));

            if (!$start) {
                wp_redirect(admin_url('admin.php?page=schedule-override&error_message=Invalid%20start%20date'));
                return;
            }

            $startTime = strtotime($_POST['startTime']);
            $start->setTime(date('H', $startTime), date('i', $startTime));

            $end = DateTime::createFromFormat('d/m/Y', trim($_POST['endDate']));

            if (!$end) {
                wp_redirect(admin_url('admin.php?page=schedule-override&error_message=Invalid%20end%20date'));
                return;
            }

            $endTime = strtotime($_POST['endTime']);
            $end->setTime(date('H', $endTime), date('i', $endTime));

            if (self::create_schedule_item(
                $title, $description,
                $start->format('Y-m-d H:i:s'),
                $end->format('Y-m-d H:i:s'))) {

                wp_redirect(admin_url('admin.php?page=schedule-override&success_message=Override%20created%20successfully'));
            }
            else {
                wp_redirect(admin_url('admin.php?page=schedule-override&error_message=Override%20could%20not%20be%20created'));
            }
        });

        add_action('admin_post_remove_schedule_override', function () {
            if (isset($_POST['deleteId'])) {
                if (self::remove_schedule_item(intval($_POST['deleteId']))) {
                    wp_redirect(admin_url('admin.php?page=schedule-override&success_message=Override%20deleted'));
                }
                else {
                    wp_redirect(admin_url('admin.php?page=schedule-override&error_message=Override%20could%20not%20be%20deleted'));
                }
            }
        });
    }

    public static function get_monday_this_week() {
        $dayNumber = date('N');
        $mondayThisWeek = new DateTime('now');

        $mondayThisWeek->sub(new DateInterval('P' . ($dayNumber - 1) . 'D'));
        $mondayThisWeek->setTime(0, 0, 0);

        return $mondayThisWeek;
    }

    public static function get_sunday_this_week() {
        $dayNumber = date('N');
        $sundayThisWeek = new DateTime('now');

        $sundayThisWeek->add(new DateInterval('P' . (7 - $dayNumber) . 'D'));
        $sundayThisWeek->setTime(24, 0, 0);

        return $sundayThisWeek;
    }

    public static function get_page_content() {
        $description = 'The schedule override utility is used for creating one-off slots in the schedule. It\'s useful for events such as Varsity where you might want to add a temporary slot in the schedule for a certain day and time to specify a game/fixture. After the event has passed, it will no longer be displayed on the schedule.';

        wp_enqueue_script('jquery_datepair', plugins_url('js/jquery.datepair.min.js', __FILE__ ), array('jquery'), null, true);
        wp_enqueue_script('jquery_timepicker', plugins_url('js/jquery.timepicker.min.js', __FILE__ ), array('jquery', 'jquery_datepair'), null, true);
        wp_enqueue_script('bootstrap', plugins_url('js/bootstrap.min.js', __FILE__ ), array('jquery'), null, true);
        wp_enqueue_script('bootstrap-datepicker', plugins_url('js/bootstrap-datepicker.min.js', __FILE__ ), array('bootstrap'), null, true);
        wp_enqueue_script('schedule_override', plugins_url('js/schedule_override.js', __FILE__ ), array('jquery', 'jquery_timepicker'), null, true);

        wp_enqueue_style('jquery_timepicker', plugins_url('css/jquery.timepicker.css', __FILE__ ));
        wp_enqueue_style('bootstrap', plugins_url('css/bootstrap.min.css', __FILE__ ));
        wp_enqueue_style('bootstrap-datepicker', plugins_url('css/bootstrap-datepicker.min.css', __FILE__ ));
        wp_enqueue_style('schedule_override', plugins_url('css/schedule_override.css', __FILE__ ));

        if (isset($_GET['error_message'])) {
            echo '<div class="error notice-error">' . $_GET['error_message'] . '</div>';
        }

        if (isset($_GET['success_message'])) {
            echo '<div class="updated notice-success">' . $_GET['success_message'] . '</div>';
        }

        // Create a header in the default WordPress 'wrap' container
        $output = '<div class="wrap">';
        $output .= '<h2>Schedule override</h2>
                    <p>' . $description . '</p>';

        $output .= '<h3>All schedule overrides</h3>
                    <form action="' . admin_url('admin-post.php') . '" method="post">
                    <input type="hidden" name="action" value="remove_schedule_override">
                    <table class="wp-list-table widefat striped tags">
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Description</th>
                                <th>Start date</th>
                                <th>End date</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>';

        foreach (self::get_all_items() as $obj) {
            $output .= "<tr>
                        <td>$obj->title</td>
                        <td>$obj->description</td>
                        <td>$obj->startDateTime</td>
                        <td>$obj->endDateTime</td>
                        <td>
                            <button class='button' name='deleteId' value='$obj->id' type='submit'>Delete</button>
                        </td>
                        </tr>";
        }

        $output .= '    </tbody>
                    </table>
                    </form>';

        $output .= '<h3>Create new schedule override</h3>
                    <form class="new-override-form" action="' . admin_url('admin-post.php') . '" method="post">

                    <div class="form-field">
                        <label for="override-title">Title</label><br>
                        <input name="title" id="override-title" type="text">
                    </div>

                    <div class="form-field">
                        <label for="override-description">Description</label><br>
                        <textarea name="description" id="override-description"></textarea>
                    </div>

                    <p class="datetime-inputs">
                        <input name="startDate" type="text" autocomplete="off" placeholder="Start date" class="date start" />
                        <input name="startTime" type="text" autocomplete="off" placeholder="Start time" class="time start" /> to
                        <input name="endTime" type="text" autocomplete="off" placeholder="End time" class="time end" />
                        <input name="endDate" type="text" autocomplete="off" placeholder="End date" class="date end" />
                    </p>

                    <input type="hidden" name="action" value="add_schedule_override">
                    <input type="submit" class="button button-primary" value="Save">

                    </form>';

        $output .= '</div>';

        echo $output;
    }

    public static function get_all_items() {
        global $wpdb;
        date_default_timezone_set('Europe/London');

        $table = $wpdb->prefix . self::$database_table_name;
        return $wpdb->get_results('SELECT * FROM ' . $table);
    }

    public static function get_this_weeks_items() {
        global $wpdb;
        date_default_timezone_set('Europe/London');

        $monday = self::get_monday_this_week()->format('Y-m-d H:i:s');
        $sunday = self::get_sunday_this_week()->format('Y-m-d H:i:s');
        $table = $wpdb->prefix . self::$database_table_name;

        $sql = $wpdb->prepare(
            'SELECT * FROM ' . $table . ' WHERE endDateTime > startDateTime AND (
                startDateTime <= %s AND endDateTime > %s OR
                startDateTime >= %s AND startDateTime <= %s
            )',
            $monday,
            $monday,
            $monday,
            $sunday
        );

        return $wpdb->get_results($sql);
    }

    public static function create_schedule_item($title, $desc, $start, $end) {
        global $wpdb;
        return $wpdb->insert(
            $wpdb->prefix . self::$database_table_name,
            array(
                'startDateTime' => $start,
                'endDateTime' => $end,
                'title' => $title,
                'description' => $desc
            )
        );
    }

    public static function remove_schedule_item($id) {
        global $wpdb;
        return $wpdb->delete($wpdb->prefix . self::$database_table_name, array('id' => $id), array('%d'));
    }

    public static function create_database_table() {
        global $wpdb;

        $table = $wpdb->prefix . $database_table_name;
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table (
            id int NOT NULL AUTO_INCREMENT,
            startDateTime datetime NOT NULL,
            endDateTime datetime NOT NULL,
            title varchar(100) NOT NULL,
            description text NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
}
