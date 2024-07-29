<?php 
function register_my_custom_dashboard_widget() {
    wp_add_dashboard_widget(
        'my_custom_dashboard_widget',
        'Latest Enquiries',
        'custom_dashboard_widget_display'
    );
}
add_action('wp_dashboard_setup', 'register_my_custom_dashboard_widget');

function get_form_entry_count($form_id) {
    global $wpdb;

    $cf7d_entry_order_by = "ASC";
    $offset = 0;
    $items_per_page = 50;

    $start_date = date('Y-m-d', strtotime('-1 day'));
    $query_end_date = date('Y-m-d 23:59:59');
    

        $query = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}cf7_vdata_entry WHERE `cf7_id` = %d AND data_id IN( SELECT * FROM ( SELECT data_id FROM {$wpdb->prefix}cf7_vdata_entry WHERE 1 = 1 AND `cf7_id` = %d AND `name` = 'submit_time' AND value between '".'%s'."' and '".'%s'."' GROUP BY `data_id` ORDER BY %s LIMIT %d,%d ) temp_table) ORDER BY %s", $form_id, $form_id, $start_date, $query_end_date, $cf7d_entry_order_by, $offset, $items_per_page, $cf7d_entry_order_by));

    $data = $query;
    $data_sorted = vsz_cf7_sortdata($data);

    return count($data_sorted);
}

function custom_dashboard_widget_display() {
    // Get all Contact Form 7 forms
    $forms = WPCF7_ContactForm::find();
    
    echo '<ul id="cf7-form-list">';
    foreach ($forms as $form) {
        $entry_count = get_form_entry_count($form->id());
        $dynamic_class = "";
        if($entry_count > 0){
            $dynamic_class = "form_have_data";
        }
        echo '<li><a href="#" class="cf7-form-link" data-form-id="' . esc_attr($form->id()) . '">' . esc_html($form->title()) . '</a> <span class="entry-count '.$dynamic_class.'" style="color:red;">(' . esc_html($entry_count) . ' entries)</span></li>';
    }
    echo '</ul>';

    // Add a container for the popup
    echo '<div id="cf7-form-popup" style="display:none;">
            <div id="cf7-form-popup-content"></div>
            <button id="cf7-form-popup-close" style="position:absolute; top:10px; right:10px; padding:5px 10px; background-color:#f00; color:#fff; border:none; cursor:pointer;">Close</button>
        </div>';
}

add_action("wp_ajax_fetch_cf7_form_entries", "fetch_cf7_form_entries");
add_action("wp_ajax_nopriv_fetch_cf7_form_entries", "fetch_cf7_form_entries");
function fetch_cf7_form_entries() {
    if (!isset($_POST['form_id'])) {
        wp_send_json_error('Form ID is required.');
    }

    $form_id = intval($_POST['form_id']);
    
    $data_sorted = get_form_entries($form_id);
    $fields = vsz_cf7_get_db_fields($form_id);

    ob_start();
   ?>   

        <table class="wp-list-table widefat fixed striped posts cf7d-admin-table">
            <thead>
                <tr><?php
                if(!empty($data_sorted)){
                    //Define table header section here
                    foreach ($fields as $k => $v){
                        echo '<th class="manage-column" data-key="'.esc_html($v).'">'.vsz_cf7_admin_get_field_name($v).'</th>';
                    }
                }
                ?></tr>
            </thead>
            <tbody><?php
                //Add character count functionalirty here
                $display_character = (int) apply_filters('vsz_display_character_count',30);
                $arr_field_type_info = vsz_field_type_info($fid);

                //Get all fields related information
                if(!empty($data_sorted)){
                    foreach ($data_sorted as $k => $v) {
                        $k = (int)$k;
                        echo '<tr>';
                        $row_id = $k;
                       
                        foreach ($fields as $k2 => $v2) {
                            //Get fields related values
                            $_value = ((isset($v[$k2])) ? $v[$k2] : '&nbsp;');
                            $_value1 = filter_var($_value, FILTER_SANITIZE_URL);

                            //Check value is URL or not
                            if (!filter_var($_value1, FILTER_VALIDATE_URL) === false) {
                                $_value = esc_url($_value);
                                //If value is url then setup anchor tag with value
                                if(!empty($arr_field_type_info) && array_key_exists($k2,$arr_field_type_info) && $arr_field_type_info[$k2] == 'file'){
                                    //Add download attributes in tag if field type is attachement
                                    ?><td data-head="<?php echo vsz_cf7_admin_get_field_name($v2); ?>">
                                        <a href="<?php echo esc_url($_value); ?>" target="_blank" title="<?php echo esc_url($_value); ?>" download ><?php echo esc_html(basename($_value)); ?>
                                        </a>
                                    </td><?php
                                }
                                else{
                                    ?><td data-head="<?php echo vsz_cf7_admin_get_field_name($v2); ?>">
                                        <a href="<?php echo esc_url($_value); ?>" target="_blank" title="<?php echo esc_url($_value); ?>" ><?php echo esc_html(basename($_value)); ?>
                                        </a>
                                    </td><?php
                                }
                            }
                            else{
                                $_value = esc_html(html_entity_decode($_value));
                                //var_dump(($_value)); var_dump(strlen($_value)); exit;
                                if(strlen($_value) > $display_character){

                                    echo '<td data-head="'.vsz_cf7_admin_get_field_name($v2).'">'.esc_html(substr($_value, 0, $display_character)).'...</td>';
                                }else{
                                    echo '<td data-head="'.vsz_cf7_admin_get_field_name($v2).'">'.esc_html($_value).'</td>';
                                }
                            }
                        }//Close foreach
                        echo '</tr>';
                    }//Close foreach
                }
                else{
                    ?><tr><?php
                        $span = count($fields) + 2;
                        ?><td colspan="<?php echo esc_html($span); ?>">
                            <?php esc_html_e('No records found.',VSZ_CF7_TEXT_DOMAIN);  ?>
                        </td><?php
                    ?></tr><?php
                }
            ?></tbody>
            <tfoot>
                <tr><?php
                if(!empty($data_sorted)){
                    //Setup header section in table footer area
                    foreach ($fields as $k => $v){
                        echo '<th class="manage-column" data-key="'.esc_html($v).'">'.vsz_cf7_admin_get_field_name($v).'</th>';
                    }
                }
                ?></tr>
            </tfoot>
        </table>

   <?php 

    wp_die();
}

// Function to fetch form entries from the database
function get_form_entries($form_id) {
    global $wpdb;

    $cf7d_entry_order_by = "ASC";
    $offset = 0;
    $items_per_page = 50;

    $start_date = date('Y-m-d', strtotime('-1 day'));
    $query_end_date = date('Y-m-d 23:59:59');
    

        $query = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}cf7_vdata_entry WHERE `cf7_id` = %d AND data_id IN( SELECT * FROM ( SELECT data_id FROM {$wpdb->prefix}cf7_vdata_entry WHERE 1 = 1 AND `cf7_id` = %d AND `name` = 'submit_time' AND value between '".'%s'."' and '".'%s'."' GROUP BY `data_id` ORDER BY %s LIMIT %d,%d ) temp_table) ORDER BY %s", $form_id, $form_id, $start_date, $query_end_date, $cf7d_entry_order_by, $offset, $items_per_page, $cf7d_entry_order_by));


    //Execute query here
    $data = $query;

    //Get entry wise all fields information
    $data_sorted = vsz_cf7_sortdata($data);

    return $data_sorted;


}

add_action('admin_enqueue_scripts', 'enqueue_custom_dashboard_scripts');
function enqueue_custom_dashboard_scripts() {
    // Enqueue jQuery
    wp_enqueue_script('jquery');

    // Inline JavaScript
    $custom_js = '
    jQuery(document).ready(function($) {
        $(document).on("click" , ".cf7-form-link" , function(e) {
            e.preventDefault();
            
            var formId = $(this).data("form-id");

            // Fetch the form entries
            $.ajax({
                url: ajaxurl,
                method: "POST",
                data: {
                    action: "fetch_cf7_form_entries",
                    form_id: formId
                },
                success: function(response) {
                    if (response) {
                        $("#cf7-form-popup-content").html(response);
                        console.log(response);
                        $("#cf7-form-popup").fadeIn();
                        $("body").addClass("enquiries-open"); // Add class when popup opens
                    }
                }
            });
        });

        // Close the popup when clicking outside of it or the close button
        $(document).on("click", ".cf7-form-link" , function(e) {
            if (!$(e.target).closest("#cf7-form-popup-content").length && !$(e.target).closest(".cf7-form-link").length && !$(e.target).closest("#cf7-form-popup-close").length) {
                $("#cf7-form-popup").fadeOut();
                $("body").addClass("enquiries-open"); // Remove class when popup closes
            }
        });

        $("#cf7-form-popup-close").on("click", function() {
            $("#cf7-form-popup").fadeOut();
            $("body").removeClass("enquiries-open"); // Remove class when popup closes
        });
    });
    ';
    wp_add_inline_script('jquery', $custom_js);

    // Inline CSS
    $custom_css = '
    #cf7-form-popup {
        position: fixed;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        width: 80%;
        height: 80%;
        background: #fff;
        box-shadow: 0 0 10px rgba(0,0,0,0.5);
        z-index: 999 !important;
        overflow: auto;
        padding: 20px;
    }

    #cf7-form-popup-content {
        max-height: 100%;
        overflow: auto;
    }
    
    /* Example styling for the class */
    .enquiries-open {
        overflow: hidden; /* Example of what you might want to do when the popup is open */
    }
button#cf7-form-popup-close{font-size:0;background-color:transparent!important;padding:0!important;width: 40px;height: 40px;opacity:0.7;}
button#cf7-form-popup-close:hover {
    opacity: 1;
}
button#cf7-form-popup-close:after,
button#cf7-form-popup-close:before {
    position: absolute;
    content: "";
    height: 33px;
    width: 2px;
    background-color: #333;
    content: "";
    position: absolute;
    background: #767676;
    border-radius: 1px;
    left: 20px;
    right:auto;
    top: 5px;
    height: 25px;
    -webkit-transform: rotate(45deg);
    -ms-transform: rotate(45deg);
    transform: rotate(45deg);
    transition: all .1s ease-in;
    -moz-transition: all .1s ease-in;
    -webkit-transition: all .1s ease-in;
    -o-transition: all .1s ease-in;
}
button#cf7-form-popup-close:after{-webkit-transform:rotate(-45deg);-ms-transform:rotate(-45deg);transform:rotate(-45deg)}
#cf7-form-popup{padding:50px 40px;height:auto;left:50%;z-index:9999!important;-webkit-box-shadow:none!important;box-shadow:none!important}
.enquiries-open:before{content:"";position:absolute;left:0;top:0;display:inline-block;background-color:rgb(0 0 0 / 70%);width:100%;height:100%;z-index:9999}
span.entry-count {
    color: green !important;
    font-weight: 600;
    text-transform: capitalize;
}
.form_have_data{
    animation: pulse 1s ease infinite;
}
.widefat th {
    font-weight: 600;
    text-transform: uppercase;
}

.widefat td {
    font-size: 14px;
}

a.cf7-form-link {
    box-shadow: none;
    font-size: 15px;
    padding: 5px 0 !important;
    display: inline-block;
}


ul#cf7-form-list li:before {
        content: counter(my-sec-counter) ")";
        counter-increment: my-sec-counter;
        position: relative;
        left: 0;
        margin-right: 5px;
        color: #2271b1;
        transition-duration: .05s;
        transition-timing-function: ease-in-out;
        font-size: 15px;
}


div#my_custom_dashboard_widget .inside {
    counter-reset: my-sec-counter;
    padding: 0;
    margin: 0;
}

ul#cf7-form-list li {
    padding: 5px 12px;
    margin: 0;
}

ul#cf7-form-list li:hover:before {
    color: #135e96;
}

ul#cf7-form-list li:focus:before{
    color: #043959;
}

ul#cf7-form-list li:not(:last-child) {border-bottom: 1px solid #c3c4c7;}

div#my_custom_dashboard_widget .inside ul#cf7-form-list {
    margin: 0;
}
    

@-webkit-keyframes pulse {
  0% {
    transform: scale(1);
    opacity: 1;
  }
  50% {
    transform: scale(1.1);
    opacity: 0;
  }
  100% {
    transform: scale(1);
    opacity: 1;
}

@media (max-width: 1399px){

.widefat td,.widefat th{
    font-size: 13px;
}
}
    ';
    wp_add_inline_style('wp-admin', $custom_css);
}

?>
