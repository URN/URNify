<?php

class ScheduleEndpoint extends Endpoint {
    protected static function create_slot() {
        $slot = array();
        $slot['name'] = '';
        $slot['slug'] = '';
        $slot['description'] = '';
        $slot['category'] = '';
        $slot['image'] = '';
        $slot['hosts'] = array();
        $slot['from'] = '00:00';
        $slot['to'] = '24:00';
        $slot['duration'] = '1440';
        $slot['live'] = false;
        $slot['override'] = false;
        return $slot;
    }

    protected static function get_show_length($from, $to) {
        $from = strtotime($from);
        $to = strtotime($to);

        if ($from > $to) {
            $to = strtotime('+1 day', $to);
        }

        return round(abs($to - $from) / 60,2);
    }

    protected static function isLive($day, $from, $to) {
        date_default_timezone_set("Europe/London");

        $current_day = strtolower(date('l'));

        if (strtolower($day) !== $current_day) {
            return false;
        }

        // If the slot is all day, it must be live
        if ($from == '00:00' && $to == '00:00') {
            return true;
        }

        $from = strtotime($from);
        $to = strtotime($to);
        $current = strtotime('now');

        // Stop it thinking 'to' is at the start of the day if it's 'before' the start time
        if ($from > $to) {
            $to = strtotime('+1 day', $to);
        }

        if ($current < $to && $current > $from) {
            return true;
        }
    }

    protected static function get_user_info_from_user_ids($user_ids) {
        $users = array();

        foreach ($user_ids as $id) {
            $user = array();

            $firstname = get_user_meta($id, 'first_name', true);
            $lastname = get_user_meta($id, 'last_name', true);
            if ($firstname !== '' && $lastname !== '') {
                $user['name'] = $firstname . ' ' . $lastname;
            }
            else {
                $user['name'] = get_user_meta($id, 'nickname', true);
            }

            $user['link'] = '#';
            $users[] = $user;
        }

        return $users;
    }

    protected static function order_show_slots($shows) {
        $startTimes = array();
        foreach ($shows as $key => $show) {
            $startTimes[$key] = $show['from'];
        }
        array_multisort($startTimes, SORT_ASC, $shows);

        return $shows;
    }
}
