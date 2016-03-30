<?php

class CustomOptionTimeSlots {
    private $name;
    private $slug;
    private $desc;

    public function __construct($name, $slug, $desc) {
        $this->name = $name;
        $this->slug = $slug;
        $this->desc = $desc;
    }

    public function get_name() {
        return $this->name;
    }

    public function get_slug() {
        return $this->slug;
    }

    public function get_desc() {
        return $this->desc;
    }

    public function get_add_form_output() {
        wp_enqueue_script('jquery_datepair', plugins_url('js/jquery.datepair.min.js', __FILE__ ), array('jquery'), null, true);
        wp_enqueue_script('jquery_timepicker', plugins_url('js/jquery.timepicker.min.js', __FILE__ ), array('jquery', 'jquery_datepair'), null, true);
        wp_enqueue_script('show_slots', plugins_url('js/show_slots.js', __FILE__ ), array('jquery', 'jquery_timepicker'), null, true);

        wp_enqueue_style('jquery_timepicker', plugins_url('css/jquery.timepicker.css', __FILE__ ));
        wp_enqueue_style('show_slots', plugins_url('css/show_slots.css', __FILE__ ));

        $slug = $this->get_slug();

        $html = '<div class="show_slots form-field term-' . $slug .'-wrap">
                    <h4>Time Slots</h4>
                    <table>
                        <thead>
                            <tr>
                                <td><input type="hidden" name="' . $slug .'"></td>
                                <td><label for="' . $slug . '-Day">Day</label></td>
                                <td><label for="' . $slug . '-From">From</label></td>
                                <td><label for="' . $slug . '-To">To</label></td>
                                <td></td>
                            </tr>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
                </div>';

        return $html;
    }

    public function get_edit_form_output($current_value) {
        $slug = $this->get_slug();

        wp_enqueue_script('jquery_datepair', plugins_url('js/jquery.datepair.min.js', __FILE__ ), array('jquery'), null, true);
        wp_enqueue_script('jquery_timepicker', plugins_url('js/jquery.timepicker.min.js', __FILE__ ), array('jquery', 'jquery_datepair'), null, true);
        wp_enqueue_script('show_slots', plugins_url('js/show_slots.js', __FILE__ ), array('jquery', 'jquery_timepicker'), null, true);

        wp_enqueue_style('jquery_timepicker', plugins_url('css/jquery.timepicker.css', __FILE__ ));
        wp_enqueue_style('show_slots', plugins_url('css/show_slots.css', __FILE__ ));

        $html = '';

        if ($current_value !== false) {
            $json = json_encode($current_value);
            $html .= '<script>var jsonSlots = ' . $json . ';</script>';
        }

        $html .= '<tr class="show_slots form-field term-' . $slug .'-wrap">
                    <th scope="row"><label for="' . $slug .'">' . $this->get_name() .'</label></th>
                    <td>
                        <table>
                            <thead>
                                <tr>
                                    <td><input type="hidden" name="' . $slug .'"></td>
                                    <td><label for="' . $slug . '-Day">Day</label></td>
                                    <td><label for="' . $slug . '-From">From</label></td>
                                    <td><label for="' . $slug . '-To">To</label></td>
                                    <td></td>
                                </tr>
                            </thead>
                            <tbody>
                            </tbody>
                        </table>
                        <p class="description" style="display:inline-block;">' . $this->get_desc() . '</p>
                    </td>
                </tr>';

        return $html;
    }

    public function save($taxonomy_name, $term_id, $post_data) {
        if ($post_data === null) {
            return;
        }

        $rawSlots = array($post_data['slot-Day'], $post_data['slot-From'], $post_data['slot-To']);

        $biggestArraySize = 0;
        foreach ($rawSlots as $array) {
            if (count($array) > $biggestArraySize) {
                $biggestArraySize = count($array);
            }
        }

        $slots = array();

        for ($i = 0; $i < $biggestArraySize; $i++) {
            if (!isset($post_data["slot-Day"][$i]) || !isset($post_data["slot-From"][$i]) || !isset($post_data["slot-To"][$i])) {
                continue;
            }

            $day = trim($post_data["slot-Day"][$i]);
            $from = trim($post_data["slot-From"][$i]);
            $to = trim($post_data["slot-To"][$i]);

            if ($day != "" && $from != "" && $to != "") {
                $slots[] = array("day" => $day, "from" => $from, "to" => $to);
            }
        }

        update_option($taxonomy_name . '_' . $term_id .'_custom_option_' . $this->get_slug(), $slots);
    }
}
