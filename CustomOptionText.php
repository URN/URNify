<?php

class CustomOptionText {
    private $name;
    private $slug;

    public function __construct($name, $slug) {
        $this->name = $name;
        $this->slug = $slug;
    }

    public function get_name() {
        return $this->name;
    }

    public function get_slug() {
        return $this->slug;
    }

    public function get_add_form_output() {
        $html = '<div class="form-field term-' . $this->get_slug() .'-wrap">
                    <label for="' . $this->get_slug() . '">' . $this->get_name() . '</label>
                    <input name="' . $this->get_slug() . '" id="' . $this->get_slug() . '" value="" type="text">
                </div>';

        return $html;
    }

    public function get_edit_form_output($current_value) {
        $html = '<tr class="form-field term-' . $this->get_slug() .'-wrap">
                    <th scope="row">
                        <label for="' . $this->get_slug() .'">' . $this->get_name() .'</label>
                    </th>
                    <td><input name="' . $this->get_slug() .'" id="' . $this->get_slug() .'" value="' . $current_value . '" type="text">
                </tr>';

        return $html;
    }

    public function save($taxonomy_name, $term_id, $post_data) {
        if ($post_data !== null) {
            update_option($taxonomy_name . '_' . $term_id .'_custom_option_' . $this->get_slug(), sanitize_text_field($post_data[$this->get_slug()]));
        }
        else {
            update_option($taxonomy_name . '_' . $term_id .'_custom_option_' . $this->get_slug(), 'false');
        }
    }
}
