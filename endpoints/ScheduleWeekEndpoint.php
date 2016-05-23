<?php

class ScheduleWeekEndpoint extends ScheduleEndpoint {
    public function get_output($match) {
        date_default_timezone_set("Europe/London");

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
            $show_info['image'] = isset($options['image']) ? $options['image'] : "";

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
                // $response[strtolower($day)][] = $show_info;
            }
        }

        $response['monday'] = self::order_show_slots($response['monday']);
        $response['tuesday'] = self::order_show_slots($response['tuesday']);
        $response['wednesday'] = self::order_show_slots($response['wednesday']);
        $response['thursday'] = self::order_show_slots($response['thursday']);
        $response['friday'] = self::order_show_slots($response['friday']);
        $response['saturday'] = self::order_show_slots($response['saturday']);
        $response['sunday'] = self::order_show_slots($response['sunday']);

        // Load overrides
        $overrideItems = ScheduleOverride::get_this_weeks_items();

        // Get the datetime of the monday for this week
        $mondayThisWeek = ScheduleOverride::get_monday_this_week();
        $sundayThisWeek = ScheduleOverride::get_sunday_this_week();

        foreach ($overrideItems as $item) {
            $start = new DateTime($item->startDateTime);
            $end = new DateTime($item->endDateTime);

            if ($start < $mondayThisWeek) {
                $diff = $start->diff($mondayThisWeek);
                $start->add($diff);
            }

            if ($end > $sundayThisWeek) {
                $diff = $sundayThisWeek->diff($end);
                $end->sub($diff);
            }

            $startMidnight = new DateTime($start->format('Y-m-d') . '00:00:00');
            $endMidnight = new DateTime($end->format('Y-m-d') . '00:00:00');

            // Get the number of days this week that the item will occur on
            $days = $startMidnight->diff($endMidnight)->days + 1;

            $days = $days > 7 ? 7 : $days;

            if ($days == 1) {
                $dayName = strtolower($start->format('l'));
                $from = $start->format('H:i');
                $to = $end->format('H:i');

                $slot = self::create_slot();
                $slot['name'] = $item->title;
                $slot['description'] = $item->description;
                $slot['from'] = $from;
                $slot['to'] = $to;
                $slot['live'] = self::isLive($dayName, $from, $to) ? true : false;
                $slot['override'] = true;

                $diff = $start->diff($end);
                $slot['duration'] = ($diff->days * 24 * 60) + ($diff->h * 60) + $diff->i;

                $response[$dayName][] = $slot;
            }
            else if ($days >= 2) {
                $from = $start->format('H:i');
                $to = $end->format('H:i');

                for ($i = 0; $i < $days; $i++) {
                    $dayName = strtolower($start->format('l'));

                    $slot = self::create_slot();
                    $slot['name'] = $item->title;
                    $slot['description'] = $item->description;

                    $slot['live'] = self::isLive($dayName, $from, $to) ? true : false;
                    $slot['override'] = true;

                    if ($i == 0) {
                        $slot['from'] = $from;

                        // Set the duration as the difference between the start time and
                        // 24:00 that day
                        $startToMidnight = $start->diff(new DateTime($start->format('Y-m-d') . '24:00:00'));
                        $slot['duration'] = ($startToMidnight->days * 24 * 60) + ($startToMidnight->h * 60) + $startToMidnight->i;
                    }
                    else if ($i == $days - 1) {
                        // If the slot is 0 length then don't include it
                        if ($from == '00:00' && $to == '00:00') {
                            continue;
                        }

                        $midnightToEnd = $end->diff(new DateTime($start->format('Y-m-d') . '00:00:00'));
                        $slot['duration'] = ($midnightToEnd->days * 24 * 60) + ($midnightToEnd->h * 60) + $midnightToEnd->i;

                        $slot['to'] = $to;
                    }

                    $response[$dayName][] = $slot;

                    // Add one to the day, for next iteration
                    $start->add(new DateInterval('P1D'));
                }
            }
        }


        //run items through func to truncate overlaps


        return $response;
    }
}
