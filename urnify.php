<?php
/*
Plugin Name: URNify
Plugin URI: https://github.com/URN/URNify
Description: This plugin pimps out WordPress to support radio-like features, including support for Shows and Podcasts that users can be added to and then make posts on. Also adds an option to the edit user page to give users committee membership that can be seen on their public user page.
Version: 0.1
Author: James Turner
Author URI: https://github.com/jamesturner
*/

// https://codex.wordpress.org/Writing_a_Plugin#Plugin_Files
defined('ABSPATH') or die('No script kiddies please!');

if (!defined('WPINC')) {
    die;
}

class URNify {
    public $custom_show_options = array();
    public $custom_podcast_options = array();

    public function init() {
        // Initialise shows when WP has started loading
        add_action('init', array($this, 'init_shows'));

        // Add a link to Shows under the dashboard admin menu
        add_action('admin_menu', function() {
            add_menu_page('Shows', 'Shows', 'add_users',
                          'edit-tags.php?taxonomy=shows', null,
                          'dashicons-groups', 21);
        });

        // Initialise podcasts when WP has started loading
        add_action('init', array($this, 'init_podcasts'));

        // Add a link to Podcasts under the dashboard admin menu
        add_action('admin_menu', function() {
            add_menu_page('Podcasts', 'Podcasts', 'add_users',
                          'edit-tags.php?taxonomy=podcasts', null,
                          'dashicons-microphone', 22);
        });

        // Display option to add user to committee on their profile
        add_action('show_user_profile', array($this, 'display_is_committee'));\
        add_action('edit_user_profile', array($this, 'display_is_committee'));

        // Save changes to committee after any update to profile pages are made
        add_action('personal_options_update', array($this, 'save_is_committee'));
        add_action('edit_user_profile_update', array($this, 'save_is_committee'));

        // Display shows and podcasts that the user is part of under their profile
        add_action('show_user_profile', array($this, 'display_shows'));
        add_action('edit_user_profile', array($this, 'display_podcasts'));
        add_action('show_user_profile', array($this, 'display_podcasts'));
        add_action('edit_user_profile', array($this, 'display_shows'));

        // Save any changes to show/podcast membership after any updates to profile
        // pages are made
        add_action('personal_options_update', array($this, 'save_memberships'));
        add_action('edit_user_profile_update', array($this, 'save_memberships'));

        // Fix menu item bug
        // http://justintadlock.com/archives/2011/10/20/custom-user-taxonomies-in-wordpress#comment-568682
        // https://gist.github.com/codekipple/2420042
        add_filter('parent_file', array($this, 'fix_taxonomy_menu_jumping'));
    }

    public function init_shows() {
        $args = array(
            'hierarchical'      => true,
            'show_in_menu'      => false,
            'labels'            => $this->get_custom_taxonomy_lables('Show'),
            'show_ui'           => true,
            'show_admin_column' => true,
            'query_var'         => true,
            'rewrite'           => array( 'slug' => 'show')
        );
     
        // Create a new taxonomy to manage Shows
        register_taxonomy('shows', array('user', 'post'), $args);

        // Add custom fields that users will be able to change for their show page
        // Each option should have a name as well a 'nice' name for presentation
        $this->custom_show_options[] = array('fb_link', 'Facebook Link');
        $this->custom_show_options[] = array('tw_link', 'Twitter Link');
        $this->custom_show_options[] = array('show_category', 'Show Category');

        // Add all custom show options to the 'add show' page
        add_action('shows_add_form_fields', function($term) {
            foreach ($this->custom_show_options as $option) {
                echo '<div class="form-field term-' . $option[0] .'-wrap">
                          <label for="' . $option[0] . '">' . $option[1] . '</label>
                          <input name="' . $option[0] . '" id="' . $option[0] . '" value="" type="text">
                      </div>';
            }
        });

        // Add all custom show options to the 'edit show' page
        add_action('shows_edit_form_fields', function($term) {
            foreach ($this->custom_show_options as $option) {
                $value = get_option('show_' . $term->term_id .'_custom_option_' . $option[0]);
                echo '<tr class="form-field term-' . $option[0] .'-wrap">
                          <th scope="row">
                              <label for="' . $option[0] .'">' . $option[1] .'</label>
                          </th>
                          <td><input name="' . $option[0] .'" id="' . $option[0] .'" value="' . $value . '" type="text">
                      </tr>';
            }
        });

        // Display custom show options on add and edit show pages
        add_action('create_shows', array($this, 'save_custom_show_options'));
        add_action('edited_shows', array($this, 'save_custom_show_options'));
    }
    
    // Save the show's custom options (e.g Facebook link) to the database.
    public function save_custom_show_options($term_id) {
        foreach($this->custom_show_options as $option) {
            update_option('show_' . $term_id .'_custom_option_' . $option[0], $_POST[$option[0]]);
        }
    }

    public function init_podcasts() {
        $args = array(
            'hierarchical'      => true,
            'show_in_menu'      => false,
            'labels'            => $this->get_custom_taxonomy_lables('Podcast'),
            'show_ui'           => true,
            'show_admin_column' => true,
            'query_var'         => true,
            'rewrite'           => array( 'slug' => 'podcast')
        );
     
        register_taxonomy('podcasts', array('user', 'post'), $args);

        // Add custom fields that users will be able to change for their podcast page
        // Each option should have a name as well a 'nice' name for presentation
        $this->custom_podcast_options[] = array('fb_link', 'Facebook Link');
        $this->custom_podcast_options[] = array('tw_link', 'Twitter Link');
        $this->custom_podcast_options[] = array('itunes_link', 'iTunes Link');

        // Add all custom podcast options to the 'add podcast' page
        add_action('podcasts_add_form_fields', function($term) {
            foreach ($this->custom_podcast_options as $option) {
                echo '<div class="form-field term-' . $option[0] .'-wrap">
                          <label for="' . $option[0] . '">' . $option[1] . '</label>
                          <input name="' . $option[0] . '" id="' . $option[0] . '" value="" type="text">
                      </div>';
            }
        });

        // Add all custom podcast options to the 'edit podcast' page
        add_action('podcasts_edit_form_fields', function($term) {
            foreach ($this->custom_podcast_options as $option) {
                $value = get_option('podcast_' . $term->term_id .'_custom_option_' . $option[0]);
                echo '<tr class="form-field term-' . $option[0] .'-wrap">
                          <th scope="row">
                              <label for="' . $option[0] .'">' . $option[1] .'</label>
                          </th>
                          <td><input name="' . $option[0] .'" id="' . $option[0] .'" value="' . $value . '" type="text">
                      </tr>';
            }
        });

        // Display custom podcast options on add and edit podcast pages
        add_action('create_podcasts', array($this, 'save_custom_podcast_options'));
        add_action('edited_podcasts', array($this, 'save_custom_podcast_options'));
    }

    // Save the podcast's custom options (e.g iTunes link) to the database.
    public function save_custom_podcast_options($term_id) {
        foreach($this->custom_podcast_options as $option) {
            update_option('podcast_' . $term_id .'_custom_option_' . $option[0], $_POST[$option[0]]);
        }
    }

    // Get an array of required labels for a new taxonomy
    // $name must be singular and capitalised, eg. Show or Podcast, not shows or Podcasts
    public function get_custom_taxonomy_lables($name) {
        $lower_case = strtolower($name);

        $labels = array(
            "name"              => "${name}s",
            "singular_name"     => "${name}",
            "search_items"      => "Search ${name}s",
            "all_items"         => "All ${name}s",
            "parent_item"       => "Parent ${name}",
            "parent_item_colon" => "Parent ${name}:",
            "edit_item"         => "Edit ${name}",
            "add_new_item"      => "Add New ${name}",
            "new_item_name"     => "New ${name} name",
            "view_item"         => "View ${name}",
            "update_item"       => "Update ${name}",
            "menu_name"         => "${name}s",
            "popular_items"         => null,
            "add_or_remove_items"   => "Add or remove ${lower_case}s",
            "choose_from_most_used" => "Choose from the most used ${lower_case}s",
            "not_found"             => "No ${lower_case}s found"
        );

        return $labels;
    }

    // Get an array containing the IDs of all the terms (shows/podcasts) the user
    // is a member of
    public function get_users_taxonomy_ids($user_id, $taxonomy) {
        $terms = wp_get_object_terms($user_id, $taxonomy);
        $term_ids = array();
        foreach ($terms as $term) {
            $term_ids[] = $term->term_id;
        }
        return $term_ids;
    }

    // Display list of shows that the user is a member of
    public function display_shows($user) {
        $this->display_membership($user, 'shows');
    }

    // Display list of podcasts that the user is a member of
    public function display_podcasts($user) {
        $this->display_membership($user, 'podcasts');
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

        echo '<h3>Committee</h3>
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

    }

    // Update user's committee membership info
    public function save_is_committee($user_id) {
        if (current_user_can('administrator') && 
            isset($_POST['is_committee']) &&
            isset($_POST['committee_role'])) {
            update_usermeta($user_id, 'is_committee', $_POST['is_committee']);
            update_usermeta($user_id, 'committee_role', $_POST['committee_role']);
        }
    }

    // Display a list of taxonomy terms (shows/podcasts etc.) that the user is a member of
    public function display_membership($user, $taxonomy) {
        $users_term_ids = $this->get_users_taxonomy_ids($user->ID, $taxonomy);
        $all_terms = get_terms($taxonomy, array('hide_empty'=>false));
        echo "<h3>" . ucfirst($taxonomy) . "</h3>";

        if (current_user_can('editor') || current_user_can('administrator')) {
            // For every term (show/podcast), print a label and checkbox of user membership
            foreach ($all_terms as $term) {
                $term_id = $term->term_id;
                $is_member_of_taxonomy = in_array($term->term_id, $users_term_ids); ?>
                <input type="checkbox"
                       id="user-<?php echo $taxonomy; ?>-<?php echo $term_id ?>"
                       <?php if($is_member_of_taxonomy) echo 'checked=checked';?> 
                       name="user_<?php echo $taxonomy; ?>[]"
                       value="<?php echo $term_id;?>"> 
                <?php
                echo "<label for=\"user-${taxonomy}-${term_id}\">{$term->name}</label>";
                echo '<br>';
            }
            if (count($all_terms) == 0) {
                echo '<i>No shows have been created</i>';
            }
        }
        else {
            // If the user isn't an editor or admin, only show the shows/podcasts that
            // their a member of, and make it un-editable (no checkboxes)
            echo '<ul>';
            $hasMembership = false;
            foreach( $all_terms as $term ) {
                if(in_array( $term->term_id, $users_term_ids )) {
                    echo "<li>{$term->name}</li>";
                    $hasMembership = true;
                }
            }
            if (!$hasMembership || count($all_terms) == 0) {
                echo "<li><i>Not a member of any ${taxonomy}</i></li>";
            }
            echo '</ul>';
        }
    }

    // Save POSTED memberships of shows/podcasts to the database
    public function save_memberships($user_id) {
        if ( current_user_can('editor') || current_user_can('administrator') ) {
            $user_show_terms = $_POST['user_shows'];
            $user_podcast_terms = isset($_POST['user_podcasts']) ? $_POST['user_podcasts'] : array();

            $show_terms = array_unique(array_map('intval', $user_show_terms));
            $podcast_terms = array_unique(array_map('intval', $user_podcast_terms));

            wp_set_object_terms($user_id, $show_terms, 'shows', false);
            wp_set_object_terms($user_id, $podcast_terms, 'podcasts', false);
         
            //make sure you clear the term cache
            clean_object_term_cache($user_id, 'shows');
            clean_object_term_cache($user_id, 'podcasts');
        }
    }

    // The highlighted 'current item' doesn't stick on Shows like it should
    function fix_taxonomy_menu_jumping($parent_file = '') {
        global $pagenow;
     
        if (!empty($_GET['taxonomy']) && $pagenow == 'edit-tags.php') {
            if ($_GET['taxonomy'] == 'shows') {
                $parent_file = 'edit-tags.php?taxonomy=shows';
            }
            else if ($_GET['taxonomy'] == 'podcasts') {
                $parent_file = 'edit-tags.php?taxonomy=podcasts';   
            }
        }
     
        return $parent_file;
    }
}

$urnify = new URNify();
$urnify->init();

?>
