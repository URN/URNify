<?php

class ScheduleEndpoint extends Endpoint {
    protected static function get_show_length($from, $to) {
        $from = strtotime($from);
        $to = strtotime($to);

        if ($from > $to) {
            $to = strtotime('+1 day', $to);
        }

        return round(abs($to - $from) / 60,2);
    }

    protected static function isLive($day, $from, $to) {
        $current_day = date('l');

        if ($day !== $current_day) {
            return false;
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
}
