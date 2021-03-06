<?php

class CustomOptionCheckbox {
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
        $html = '<div class="form-field term-' . $this->get_slug() .'-wrap">
                    <label for="' . $this->get_slug() . '" style="display:inline-block;margin-right:10px">' . $this->get_name() . '</label>
                    <input name="' . $this->get_slug() . '" id="' . $this->get_slug() . '" value="true" type="checkbox">
                    <p>' . $this->get_desc() . '</p>
                </div>';

        return $html;
    }

    public function get_edit_form_output($current_value) {
        $checked = $current_value === 'true' ? 'checked' : '';

        $html = '<tr class="form-field term-' . $this->get_slug() .'-wrap">
                    <th scope="row"><label for="' . $this->get_slug() .'">' . $this->get_name() .'</label></th>
                    <td><input ' . $checked . ' name="' . $this->get_slug() .'" id="' . $this->get_slug() .'" value="' . $current_value . '" type="checkbox">
                    <p class="description" style="display:inline-block;">' . $this->get_desc() . '</p></td>
                </tr>';

        return $html;
    }

    public function save($taxonomy_name, $term_id, $post_data) {
        if ($post_data[$this->get_slug()] !== null) {
            update_option($taxonomy_name . '_' . $term_id .'_custom_option_' . $this->get_slug(), 'true');
        }
        else {
            update_option($taxonomy_name . '_' . $term_id .'_custom_option_' . $this->get_slug(), 'false');
        }
    }
}
