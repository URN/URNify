<?php

class ScheduleWeekEndpoint extends ScheduleEndpoint {
    public function get_output($match) {
        $all_options = wp_load_alloptions();
        $show_options = array();
        foreach ($all_options as $key => $value) {
            if (preg_match('/^show_\d*_custom_option_\w*$/', $key)) {
                $show_options[$key] = $value;
            }
        }

        $shows = get_terms('shows', array('hide_empty' => 0));
        $response = array();
        $response['monday'] = array();
        $response['tuesday'] = array();
        $response['wednesday'] = array();
        $response['thursday'] = array();
        $response['friday'] = array();
        $response['saturday'] = array();
        $response['sunday'] = array();

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
            $show_info['category'] = $options['show_category'];

            $host_ids = get_objects_in_term($show->term_id, array('shows'));
            $show_info['hosts'] = self::get_user_info_from_user_ids($host_ids);

            foreach ($options['slot'] as $key => $slot) {
                $day = $slot['day'];
                $from = date("H:i", strtotime($slot['from']));
                $to = date("H:i", strtotime($slot['to']));

                $show_info['from'] = $from;
                $show_info['to'] = $to;
                $show_info['duration'] = self::get_show_length($from, $to);

                $show_info['live'] = self::isLive($day, $from, $to) ? true : false;
                $response[strtolower($day)][] = $show_info;
            }
        }

        $response['monday'] = self::order_show_slots($response['monday']);
        $response['tuesday'] = self::order_show_slots($response['tuesday']);
        $response['wednesday'] = self::order_show_slots($response['wednesday']);
        $response['thursday'] = self::order_show_slots($response['thursday']);
        $response['friday'] = self::order_show_slots($response['friday']);
        $response['saturday'] = self::order_show_slots($response['saturday']);
        $response['sunday'] = self::order_show_slots($response['sunday']);

        return $response;
    }
}
