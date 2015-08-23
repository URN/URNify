<?php

class ScheduleEndpoint extends Endpoint {
    private static function isLive($day, $from, $to) {
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

    private static function get_user_info_from_user_ids($user_ids) {
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

    public function get_output() {
        $all_options = wp_load_alloptions();
        $show_options = array();
        foreach ($all_options as $key => $value) {
            if (preg_match('/^show_\d*_custom_option_\w*$/', $key)) {
                $show_options[$key] = $value;
            }
        }

        $shows = get_terms('shows', array('hide_empty' => 0));
        $schedule = array();
        $schedule['monday'] = array();
        $schedule['tuesday'] = array();
        $schedule['wednesday'] = array();
        $schedule['thursday'] = array();
        $schedule['friday'] = array();
        $schedule['saturday'] = array();
        $schedule['sunday'] = array();

        foreach ($shows as $key => $show) {
            $options = array();
            foreach ($show_options as $key => $option) {
                if (preg_match('/^show_' . $show->term_id . '_custom_option_\w*$/', $key)) {
                    if ($option === 'false') {
                        $option = false;
                    }
                    if ($option === 'true') {
                        $option = true;
                    }
                    $options[str_replace('show_' . $show->term_id . '_custom_option_', '', $key)] = $option;
                }
            }

            if ($options['ended'] || $options['hidden']) {
                continue;
            }

            $options['slot'] = unserialize($options['slot']);

            $show_info = array();
            $show_info['name'] = $show->name;
            $show_info['slug'] = $show->slug;
            $show_info['description'] = $show->description;

            $host_ids = get_objects_in_term($show->term_id, array('shows'));
            $show_info['hosts'] = self::get_user_info_from_user_ids($host_ids);
            foreach ($options['slot'] as $key => $slot) {
                $day = $slot['day'];
                $from = $slot['from'];
                $to = $slot['to'];

                $show_info['from'] = $from;
                $show_info['to'] = $to;

                $show_info['live'] = self::isLive($day, $from, $to) ? true : false;
                $schedule[strtolower($day)][] = $show_info;
            }
        }

        return $schedule;
    }
}
