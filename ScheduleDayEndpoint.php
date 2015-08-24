<?php

class ScheduleDayEndpoint extends ScheduleEndpoint {
    public function get_output($api_day) {
        $all_options = wp_load_alloptions();
        $show_options = array();
        foreach ($all_options as $key => $value) {
            if (preg_match('/^show_\d*_custom_option_\w*$/', $key)) {
                $show_options[$key] = $value;
            }
        }

        $shows = get_terms('shows', array('hide_empty' => 0));
        $response = array(
            'day' => $api_day,
            'shows' => array()
        );

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
                $show_info['length'] = self::get_show_length($from, $to);

                $show_info['live'] = self::isLive($day, $from, $to) ? true : false;

                if (strtolower($day) === $api_day) {
                    $response['shows'][] = $show_info;
                }
            }
        }

        return $response;
    }
}
