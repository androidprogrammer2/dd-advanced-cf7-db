<?php 
function ddacf7db_register_latest_entries_widget() {
    register_widget('DDACF7DB_Latest_Entries_Widget');
}
add_action('widgets_init', 'ddacf7db_register_latest_entries_widget');

class DDACF7DB_Latest_Entries_Widget extends WP_Widget {

    public function __construct() {
        parent::__construct(
            'ddacf7db_latest_entries',
            'Latest CF7 Entries',
            array('description' => __('Displays the latest 10 Contact Form 7 entries.', 'text_domain')) // Args
        );
    }

    public function widget($args, $instance) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'cf7_data';

        $results = $wpdb->get_results("SELECT * FROM $table_name ORDER BY submitted_at DESC LIMIT 10");

        echo $args['before_widget'];
        echo $args['before_title'] . 'Latest CF7 Entries' . $args['after_title'];

        if ($results) {
            echo '<ul>';
            foreach ($results as $row) {
                $submission_data = json_decode($row->submission_data, true);
                $formatted_data = '';

                if (is_array($submission_data)) {
                    foreach ($submission_data as $key => $value) {
                        $formatted_data .= esc_html($key) . ': ' . (is_array($value) ? implode(', ', $value) : esc_html($value)) . '<br>';
                    }
                } else {
                    $formatted_data = esc_html($row->submission_data);
                }

                echo '<li>';
                echo '<strong>Form ID:</strong> ' . esc_html($row->form_id) . '<br>';
                echo '<strong>Submitted At:</strong> ' . esc_html($row->submitted_at) . '<br>';
                echo '<div>' . $formatted_data . '</div>';
                echo '</li>';
            }
            echo '</ul>';
        } else {
            echo '<p>No entries found.</p>';
        }

        echo $args['after_widget'];
    }

    public function form($instance) {
    }

    
    public function update($new_instance, $old_instance) {
        $instance = array();
        return $instance;
    }
}

function ddacf7db_add_dashboard_widget() {
    wp_add_dashboard_widget(
        'ddacf7db_dashboard_widget',          
        'Latest CF7 Entries',                 
        'ddacf7db_display_dashboard_widget' 
    );
}
add_action('wp_dashboard_setup', 'ddacf7db_add_dashboard_widget');

function ddacf7db_display_dashboard_widget() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'cf7_data';

    $results = $wpdb->get_results("SELECT * FROM $table_name ORDER BY submitted_at DESC LIMIT 10");

    if ($results) {
        echo '<ul>';
        foreach ($results as $row) {
            $submission_data = json_decode($row->submission_data, true);
            $formatted_data = '';

            if (is_array($submission_data)) {
                foreach ($submission_data as $key => $value) {
                    $formatted_data .= esc_html($key) . ': ' . (is_array($value) ? implode(', ', $value) : esc_html($value)) . '<br>';
                }
            } else {
                $formatted_data = esc_html($row->submission_data);
            }

            echo '<li>';
            echo '<strong>Form ID:</strong> ' . esc_html($row->form_id) . '<br>';
            echo '<strong>Submitted At:</strong> ' . esc_html($row->submitted_at) . '<br>';
            echo '<div>' . $formatted_data . '</div>';
            echo '</li>';
        }
        echo '</ul>';
    } else {
        echo '<p>No entries found.</p>';
    }
}