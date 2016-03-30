<?php

class Committee {
    public function __construct() {
        // Display option to add user to committee on their profile
        add_action('show_user_profile', array($this, 'display_is_committee'));
        add_action('edit_user_profile', array($this, 'display_is_committee'));

        // Save changes to committee after any update to profile pages are made
        add_action('personal_options_update', array($this, 'save_is_committee'));
        add_action('edit_user_profile_update', array($this, 'save_is_committee'));
    }

    // Display a checkbox for toggling whether the user is a member of URN comittee or not
    // and display a text field for naming their committee position
    public function display_is_committee($user) {
        if (esc_attr(get_the_author_meta('is_committee', $user->ID)) !== 'true') {
            $checked = '';
            $role = '';
        }
        else {
            $checked = 'checked';
            $role = esc_attr(get_the_author_meta('committee_role', $user->ID));
        }

        $disabled = current_user_can('administrator') ? '' : 'disabled';

        $html = '<h3>Committee</h3>
                <table class="form-table">
                <tbody>
                    <tr>
                        <th>Status</th>
                        <td>
                            <label for="is_committee">
                                <input ' . $disabled . ' autocomplete="off"
                                    name="is_committee" value="true"
                                    id="is_committee" ' . $checked . ' type="checkbox">
                                Is a member of committee?
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="committee_role">Role</label></th>
                        <td><input ' . $disabled . ' autocomplete="off"
                                name="committee_role" id="committee_role"
                                value="' . $role .'" class="regular-text code"
                                type="text"></td>
                    </tr>
                </tbody>
                </table>';

        echo $html;

    }

    // Update user's committee membership info
    public function save_is_committee($user_id) {
        if (current_user_can('administrator')) {
            if (isset($_POST['is_committee']) && isset($_POST['committee_role'])) {
                update_user_meta($user_id, 'is_committee', $_POST['is_committee']);
                update_user_meta($user_id, 'committee_role', $_POST['committee_role']);
            }
            else {
                update_user_meta($user_id, 'is_committee', 'false');
                update_user_meta($user_id, 'committee_role', '');
            }
        }
    }
}
