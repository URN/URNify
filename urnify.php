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

        $this->init_api();
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
        $this->custom_show_options[] = array(
            'type' => 'text',
            'slug' => 'name_prelude',
            'name' => 'Name Prelude'
        );
        $this->custom_show_options[] = array(
            'type' => 'text',
            'slug' => 'fb_link',
            'name' => 'Facebook Link'
        );
        $this->custom_show_options[] = array(
            'type' => 'text',
            'slug' => 'tw_link',
            'name' => 'Twitter Link'
        );
        $this->custom_show_options[] = array(
            'type' => 'text',
            'slug' => 'show_category',
            'name' => 'Show Category'
        );
        $this->custom_show_options[] = array(
            'type' => 'checkbox',
            'slug' => 'ended',
            'name' => 'Ended',
            'desc' => 'Has the podcast ended?'
        );
        $this->custom_show_options[] = array(
            'type' => 'checkbox',
            'slug' => 'hidden',
            'name' => 'Hidden',
            'desc' => 'Hide this podcast from view?'
        );
        $this->custom_show_options[] = array(
            'type' => 'show_slots',
            'slug' => 'slot',
            'name' => 'Time Slots',
            'desc' => 'When is this show on air?',
            'label1' => 'Day',
            'label2' => 'From',
            'label3' => 'To',
            'add_new_text' => 'Add slot'
        );

        // Add all custom show options to the 'add show' page
        add_action('shows_add_form_fields', function($term) {
            foreach ($this->custom_show_options as $option) {
                if ($option['type'] === 'text') {
                    echo '<div class="form-field term-' . $option['slug'] .'-wrap">
                              <label for="' . $option['slug'] . '">' . $option['name'] . '</label>
                              <input name="' . $option['slug'] . '" id="' . $option['slug'] . '" value="" type="text">
                          </div>';
                }
                else if ($option['type'] === 'checkbox') {
                    echo '<div class="form-field term-' . $option['slug'] .'-wrap">
                              <label for="' . $option['slug'] . '" style="display:inline-block;margin-right:10px">' . $option['name'] . '</label>
                              <input name="' . $option['slug'] . '" id="' . $option['slug'] . '" value="true" type="checkbox">
                              <p>' . $option['desc'] . '</p>
                          </div>';
                }
                else if ($option['type'] === 'show_slots') {
                    echo '<div class="show_slots form-field term-' . $option['slug'] .'-wrap">
                              <h4>Time Slots</h4>
                              <table>
                                  <thead>
                                      <tr>
                                          <td></td>
                                          <td><label for="' . $option['slug'] . '-' . $option['label1'] . '">' . $option['label1'] . '</label></td>
                                          <td><label for="' . $option['slug'] . '-' . $option['label2'] . '">' . $option['label2'] . '</label></td>
                                          <td><label for="' . $option['slug'] . '-' . $option['label3'] . '">' . $option['label3'] . '</label></td>
                                          <td></td>
                                      </tr>
                                  </thead>
                                  <tbody>
                                  </tbody>
                              </table>
                          </div>';

                    wp_enqueue_script('jquery_datepair', plugins_url('js/jquery.datepair.min.js', __FILE__ ), array('jquery'), null, true);
                    wp_enqueue_script('jquery_timepicker', plugins_url('js/jquery.timepicker.min.js', __FILE__ ), array('jquery', 'jquery_datepair'), null, true);
                    wp_enqueue_script('show_slots', plugins_url('js/show_slots.js', __FILE__ ), array('jquery', 'jquery_timepicker'), null, true);

                    wp_enqueue_style('jquery_timepicker', plugins_url('css/jquery.timepicker.css', __FILE__ ));
                    wp_enqueue_style('show_slots', plugins_url('css/show_slots.css', __FILE__ ));
                }
            }
        });

        // Add all custom show options to the 'edit show' page
        add_action('shows_edit_form_fields', function($term) {
            foreach ($this->custom_show_options as $option) {
                $value = get_option('show_' . $term->term_id .'_custom_option_' . $option['slug']);

                if ($option['type'] === 'text') {
                    echo '<tr class="form-field term-' . $option['slug'] .'-wrap">
                              <th scope="row">
                                  <label for="' . $option['slug'] .'">' . $option['name'] .'</label>
                              </th>
                              <td><input name="' . $option['slug'] .'" id="' . $option['slug'] .'" value="' . $value . '" type="text">
                          </tr>';
                }
                else if ($option['type'] === 'checkbox') {
                    $checked = $value === 'true' ? 'checked' : '';
                    echo '<tr class="form-field term-' . $option['slug'] .'-wrap">
                              <th scope="row"><label for="' . $option['slug'] .'">' . $option['name'] .'</label></th>
                              <td><input ' . $checked . ' name="' . $option['slug'] .'" id="' . $option['slug'] .'" value="' . $value . '" type="checkbox">
                              <p class="description" style="display:inline-block;">' . $option['desc'] . '</p></td>
                          </tr>';
                }
                else if ($option['type'] === 'show_slots') {
                    $json = json_encode($value);
                    echo '<script>var jsonSlots = ' . $json . ';</script>';

                    echo '<tr class="show_slots form-field term-' . $option['slug'] .'-wrap">
                              <th scope="row"><label for="' . $option['slug'] .'">' . $option['name'] .'</label></th>
                              <td>
                                  <table>
                                      <thead>
                                          <tr>
                                              <td></td>
                                              <td><label for="' . $option['slug'] . '-' . $option['label1'] . '">' . $option['label1'] . '</label></td>
                                              <td><label for="' . $option['slug'] . '-' . $option['label2'] . '">' . $option['label2'] . '</label></td>
                                              <td><label for="' . $option['slug'] . '-' . $option['label3'] . '">' . $option['label3'] . '</label></td>
                                              <td></td>
                                          </tr>
                                      </thead>
                                      <tbody>
                                      </tbody>
                                  </table>
                                  <p class="description" style="display:inline-block;">' . $option['desc'] . '</p>
                              </td>
                          </tr>';

                    wp_enqueue_script('jquery_datepair', plugins_url('js/jquery.datepair.min.js', __FILE__ ), array('jquery'), null, true);
                    wp_enqueue_script('jquery_timepicker', plugins_url('js/jquery.timepicker.min.js', __FILE__ ), array('jquery', 'jquery_datepair'), null, true);
                    wp_enqueue_script('show_slots', plugins_url('js/show_slots.js', __FILE__ ), array('jquery', 'jquery_timepicker'), null, true);

                    wp_enqueue_style('jquery_timepicker', plugins_url('css/jquery.timepicker.css', __FILE__ ));
                    wp_enqueue_style('show_slots', plugins_url('css/show_slots.css', __FILE__ ));
                }
            }
        });

        // Display custom show options on add and edit show pages
        add_action('create_shows', array($this, 'save_custom_show_options'));
        add_action('edited_shows', array($this, 'save_custom_show_options'));
    }
    
    // Save the show's custom options (e.g Facebook link) to the database.
    public function save_custom_show_options($term_id) {
        foreach($this->custom_show_options as $option) {
            if ($option['type'] === 'text') {
                update_option('show_' . $term_id .'_custom_option_' . $option['slug'], $_POST[$option['slug']]);
            }
            else if ($option['type'] === 'checkbox') {
                if (isset($_POST[$option['slug']])) {
                    update_option('show_' . $term_id .'_custom_option_' . $option['slug'], $_POST[$option['slug']]);
                }
                else {
                    update_option('show_' . $term_id .'_custom_option_' . $option['slug'], 'false');
                }
            }
            else if ($option['type'] === 'show_slots') {
                $rawSlots = array($_POST['slot-Day'], $_POST['slot-From'], $_POST['slot-To']);

                $biggestArraySize = 0;
                foreach ($rawSlots as $array) {
                    if (count($array) > $biggestArraySize) {
                        $biggestArraySize = count($array);
                    }
                }

                $slots = array();

                for ($i = 0; $i < $biggestArraySize; $i++) {
                    if (!isset($_POST["slot-Day"][$i]) || !isset($_POST["slot-From"][$i]) || !isset($_POST["slot-To"][$i])) {
                        continue;
                    }

                    $day = trim($_POST["slot-Day"][$i]);
                    $from = trim($_POST["slot-From"][$i]);
                    $to = trim($_POST["slot-To"][$i]);

                    if ($day != "" && $from != "" && $to != "") {
                        $slots[] = array("day" => $day, "from" => $from, "to" => $to);
                    }
                }

                update_option('show_' . $term_id .'_custom_option_' . $option['slug'], $slots);
            }
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
        $this->custom_podcast_options[] = array(
            'type' => 'text',
            'slug' => 'fb_link',
            'name' => 'Facebook Link'
        );
        $this->custom_podcast_options[] = array(
            'type' => 'text',
            'slug' => 'tw_link',
            'name' => 'Twitter Link'
        );
        $this->custom_podcast_options[] = array(
            'type' => 'text',
            'slug' => 'itunes_link',
            'name' => 'iTunes Link'
        );
        $this->custom_podcast_options[] = array(
            'type' => 'checkbox',
            'slug' => 'ended',
            'name' => 'Ended',
            'desc' => 'Has the podcast ended?'
        );
        $this->custom_podcast_options[] = array(
            'type' => 'checkbox',
            'slug' => 'hidden',
            'name' => 'Hidden',
            'desc' => 'Hide this podcast from view?'
        );

        // Add all custom podcast options to the 'add podcast' page
        add_action('podcasts_add_form_fields', function($term) {
            foreach ($this->custom_podcast_options as $option) {
                if ($option['type'] === 'text') {
                    echo '<div class="form-field term-' . $option['slug'] .'-wrap">
                              <label for="' . $option['slug'] . '">' . $option['name'] . '</label>
                              <input name="' . $option['slug'] . '" id="' . $option['slug'] . '" value="" type="text">
                          </div>';
                }
                else if ($option['type'] === 'checkbox') {
                    echo '<div class="form-field term-' . $option['slug'] .'-wrap">
                              <label for="' . $option['slug'] . '" style="display:inline-block;margin-right:10px">' . $option['name'] . '</label>
                              <input name="' . $option['slug'] . '" id="' . $option['slug'] . '" value="true" type="checkbox">
                              <p>' . $option['desc'] . '</p>
                          </div>';
                }
            }
        });

        // Add all custom podcast options to the 'edit podcast' page
        add_action('podcasts_edit_form_fields', function($term) {
            foreach ($this->custom_podcast_options as $option) {
                $value = get_option('podcast_' . $term->term_id .'_custom_option_' . $option['slug']);

                if ($option['type'] === 'text') {
                    echo '<tr class="form-field term-' . $option['slug'] .'-wrap">
                              <th scope="row">
                                  <label for="' . $option['slug'] .'">' . $option['name'] .'</label>
                              </th>
                              <td><input name="' . $option['slug'] .'" id="' . $option['slug'] .'" value="' . $value . '" type="text">
                          </tr>';
                }
                else if ($option['type'] === 'checkbox') {
                    $checked = $value === 'true' ? 'checked' : '';
                    echo '<tr class="form-field term-' . $option['slug'] .'-wrap">
                              <th scope="row"><label for="' . $option['slug'] .'">' . $option['name'] .'</label></th>
                              <td><input ' . $checked . ' name="' . $option['slug'] .'" id="' . $option['slug'] .'" value="' . $value . '" type="checkbox">
                              <p class="description" style="display:inline-block;">' . $option['desc'] . '</p></td>
                          </tr>';
                }
            }
        });

        // Display custom podcast options on add and edit podcast pages
        add_action('create_podcasts', array($this, 'save_custom_podcast_options'));
        add_action('edited_podcasts', array($this, 'save_custom_podcast_options'));
    }

    // Save the podcast's custom options (e.g iTunes link) to the database.
    public function save_custom_podcast_options($term_id) {
        foreach($this->custom_podcast_options as $option) {
            if ($option['type'] === 'text') {
                update_option('podcast_' . $term_id .'_custom_option_' . $option['slug'], $_POST[$option['slug']]);
            }
            else if ($option['type'] === 'checkbox') {
                if (isset($_POST[$option['slug']])) {
                    update_option('podcast_' . $term_id .'_custom_option_' . $option['slug'], $_POST[$option['slug']]);
                }
                else {
                    update_option('podcast_' . $term_id .'_custom_option_' . $option['slug'], 'false');
                }
            }
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

    function init_api() {
        
        add_action( 'init', function () {
            add_rewrite_rule('api/schedule/week', 'index.php?api=schedule_week', 'top' );
            add_rewrite_rule('api/schedule/day/(monday|tuesday|wednesday|thursday|friday)', 'index.php?api=schedule_$matches[1]', 'top');
        });

        add_filter('query_vars', function ($query_vars) {
            $query_vars[] = 'api';
            return $query_vars;
        });

        add_filter('template_include', function ($template) {
            global $wp_query;

            // You could normally swap out the template WP wants to use here, but we'll just die
            if ( isset( $wp_query->query_vars['api'] ) && !is_page() && !is_single() ) {
                $wp_query->is_404 = false;
                $wp_query->is_archive = true;
                $wp_query->is_category = true;

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

                    foreach ($options['slot'] as $key => $slot) {
                        $day = $slot['day'];
                        $from = $slot['from'];
                        $to = $slot['to'];

                        $show_info['from'] = $from;
                        $show_info['to'] = $to;

                        $show_info['live'] = live($day, $from, $to) ? true : false;
                        $schedule[strtolower($day)][] = $show_info;
                    }
                }

                header('Content-Type: application/json');
                die(json_encode($schedule));
            } else {
                return $template;
            }
        });

        function live($day, $from, $to) {
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
    }
}

$urnify = new URNify();
$urnify->init();

?>
