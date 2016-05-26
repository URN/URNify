<?php

class ScheduleEndpoint extends Endpoint {
    protected static function remove_overlaps($slots) {
        $startSlot1 = new DateTime();
        $endSlot1 = new DateTime();
        $startSlot2 = new DateTime();
        $endSlot2 = new DateTime();

        foreach ($slots as $dayIndex => $day) {
            $slotIndexesToRemove = [];
            $processedDay = [];

            for ($i = 0; $i < count($day); $i++) {
                $slot1 = $day[$i];

                $startSlot1->setTime(substr($slot1['from'], 0, 2), substr($slot1['from'], 3, 5), 0);
                $endSlot1->setTime(substr($slot1['to'], 0, 2), substr($slot1['to'], 3, 5), 0);

                for ($j = $i + 1; $j < count($day); $j++) {
                    $slot2 = $day[$j];

                    if ($slot1 == $slot2) {
                        continue;
                    }

                    $overlap = false;

                    $startSlot2->setTime(substr($slot2['from'], 0, 2), substr($slot2['from'], 3, 5), 0);
                    $endSlot2->setTime(substr($slot2['to'], 0, 2), substr($slot2['to'], 3, 5), 0);

                    // If the two slots intersect...
                    if ($startSlot1 <= $endSlot2 && $endSlot1 >= $startSlot2) {
                        $overlap = true;

                        if ($slot1['override'] || !$slot2['override']) {
                            // Truncate slot2 so that it's not overlapping slot1
                            $slotToTruncate = $slot2;
                            $slotToTruncateStart = $startSlot2;
                            $slotToTruncateEnd = $endSlot2;
                            $otherSlotStart = $startSlot1;
                            $otherSlotEnd = $endSlot1;
                        }
                        else {
                            // Truncate slot1 so that it's not overlapping slot2
                            $slotToTruncate = $slot1;
                            $slotToTruncateStart = $startSlot1;
                            $slotToTruncateEnd = $endSlot1;
                            $otherSlotStart = $startSlot2;
                            $otherSlotEnd = $endSlot2;
                        }

                        // If the main slot starts before and ends after the other slot,
                        // just remove the other slot completely
                        if ($slotToTruncateStart > $otherSlotStart && $slotToTruncateEnd < $otherSlotEnd) {
                            $slotIndexesToRemove[] = $slotToTruncate == $slot1 ? $i : $j;
                            continue;
                        }

                        // [------ slot 1 ------]
                        //    [------ slot 2a ------]
                        //    [------ slot 2b --]
                        if ($slotToTruncateStart > $otherSlotStart) {
                            $diff = $slotToTruncateStart->diff($otherSlotEnd);
                            $slotToTruncateStart->add($diff);
                        }
                        //      [------ slot 1a ------]
                        //    [----- slot 1b ---]
                        // [------ slot 2a -----]
                        // [------ slot 2a --------------]
                        else if ($otherSlotStart > $slotToTruncateStart) {
                            // If the truncated slot falls both sides of the other,
                            // duplicate it and place the main slot in between the two
                            if ($otherSlotEnd < $slotToTruncateEnd) {
                                $copy = $slotToTruncate;
                                $diff = $otherSlotEnd->diff($slotToTruncateEnd);
                                $copy['duration'] = ($diff->h * 60) + $diff->i;
                                $copy['from'] = $otherSlotEnd->format('H:i');
                                $processedDay[] = $copy;
                            }

                            $diff = $otherSlotStart->diff($slotToTruncateEnd);
                            $slotToTruncateEnd->sub($diff);
                        }
                        else {
                            // [------ slot 1 ---]
                            // [------ slot 2 ------]
                            if ($slotToTruncateEnd > $otherSlotEnd) {
                                $diff = $slotToTruncateStart->diff($otherSlotEnd);
                                $slotToTruncateStart->add($diff);
                            }
                            // [------ slot 1 ------]
                            // [------ slot 2a -----]
                            // [------ slot 2b --]
                            else {
                                $slotIndexesToRemove[] = $slotToTruncate == $slot1 ? $i : $j;
                            }
                        }
                    }

                    if (!$overlap) {
                        continue;
                    }

                    $slot1Duration = $startSlot1->diff($endSlot1);
                    $slot1Duration = ($slot1Duration->h * 60) + $slot1Duration->i;

                    $slot2Duration = $startSlot2->diff($endSlot2);
                    $slot2Duration = ($slot2Duration->h * 60) + $slot2Duration->i;

                    $slot1['from'] = $startSlot1->format('H:i');
                    $slot1['to'] = $endSlot1->format('H:i');
                    $slot1['duration'] = $slot1Duration;
                    $slot2['from'] = $startSlot2->format('H:i');
                    $slot2['to'] = $endSlot2->format('H:i');
                    $slot2['duration'] = $slot2Duration;

                    $day[$i] = $slot1;
                    $day[$j] = $slot2;
                }
            }

            foreach ($day as $i => $slot) {
                if (!in_array($i, $slotIndexesToRemove)) {
                    $processedDay[] = $slot;
                }
            }

            $slots[$dayIndex] = $processedDay;

        }

        return $slots;
    }

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
