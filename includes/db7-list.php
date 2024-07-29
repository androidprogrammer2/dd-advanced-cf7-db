<?php
//enqueue date time picker CSS in file
wp_enqueue_style('jquery-datetimepicker-css');

wp_enqueue_style('font_awesome_css');

//enqueue Sortable JS in file
wp_enqueue_script('jquery-ui-sortable');

wp_enqueue_script('advanced_cf7_db_admin_js');
wp_enqueue_script('datepicker_min_js');

//Get all existing contact form list
$form_list = vsz_cf7_get_the_form_list();
$url = '';
$fid = '';

$nonce = wp_create_nonce('vsz-cf7-action-nonce');

if (!wp_verify_nonce($nonce, 'vsz-cf7-action-nonce')) {
    echo esc_html('You have no permission to access this page');
    return;
}

//Get selected form Id value
if (isset($_GET['cf7_id']) && !empty($_GET['cf7_id'])) {
    $edit = false;
    $entry_actions = array();
    $fid = intval(sanitize_text_field($_GET['cf7_id']));
    if (!cf7_check_capability('cf7_db_form_view' . $fid) && !cf7_check_capability('cf7_db_form_edit_' . $fid)) {
        wp_die(__('You do not have sufficient permissions to access this page.'));
    }
    if (cf7_check_capability('cf7_db_form_edit_' . $fid)) {
        $edit = true;
        $entry_actions = array(
            'delete' => 'Delete'
        );
    }

    $menu_url = menu_page_url('contact-form-listing', false);
    $url = $menu_url . '&cf7_id=' . $fid;
}

//Get search related value
$search = '';
if (isset($_REQUEST['search_cf7_value']) && !empty($_REQUEST['search_cf7_value'])) {
    $search = addslashes(addslashes(htmlspecialchars(sanitize_text_field($_REQUEST['search_cf7_value']))));
    $_POST['search_cf7_value'] = $_REQUEST['search_cf7_value'];
}

//Get all form names which entry store in DB
global $wpdb;

$cf_fields = array();
foreach ($form_list as $objForm) {
    if (cf7_check_capability('cf7_db_form_view' . $objForm->id()) || cf7_check_capability('cf7_db_form_edit_' . $objForm->id())) {
        $cf7fields =  vsz_cf7_get_db_fields($objForm->id());
        if(is_array($cf7fields) and is_array($cf7fields)) {
            $cf_fields = array_merge($cf_fields , array_keys($cf7fields));
        }
    }
}

$cf_fields = array_unique($cf_fields);

?>
    <div class="wrap">
        <h2><?php
            esc_html_e('View Form Information', VSZ_CF7_TEXT_DOMAIN);
            ?></h2>
    </div>
    <div class="wrap select-specific">
    <table class="form-table inner-row">
        <tr class="form-field form-required select-form">
            <th><?php esc_html_e('Select Form name', VSZ_CF7_TEXT_DOMAIN); ?></th>
            <td>
                <form name="cf7_name" id="cf7_name" action="<?php menu_page_url('all-form-listing'); ?>" method="">
                    <select name="cf7_id" id="cf7_id" onchange="submit_cf7()">
                        <option value=""><?php esc_html_e('Select Form name', VSZ_CF7_TEXT_DOMAIN); ?></option><?php
                        //Display all existing form list here
                        $exist_entry_flag = false;
                        if (!empty($form_list)) {

                            foreach ($form_list as $objForm) {
                                if (cf7_check_capability('cf7_db_form_view' . $objForm->id()) || cf7_check_capability('cf7_db_form_edit_' . $objForm->id())) {
                                    if (!empty($fid) && $fid === $objForm->id()) {
                                        print '<option value="' . $objForm->id() . '" selected>' . esc_html($objForm->title()) . '</option>';
                                    } else {
                                        print '<option value="' . $objForm->id() . '" >' . esc_html($objForm->title()) . '</option>';
                                    }
                                }
                            }//close for each
                        }//close if
                        ?></select>
                </form>
            </td>
        </tr>
    </table>
    </div><?php

if(!empty($form_list) and is_array($form_list)) {

    $form_query = ' (1=1) ';
    if(!empty($_REQUEST['cf7_id'])) {
        $form_query.= " AND ( main_entry.cf7_id = ".intval($_REQUEST['cf7_id'])." ) ";
    }

    if(!empty($_REQUEST['daterange'])) {
        $daterange = sanitize_text_field($_REQUEST['daterange']);
            
        $start_date = @date_create_from_format("m/d/Y",current( explode(' - ',$daterange) ) );
        $end_date = @date_create_from_format("m/d/Y", current( array_reverse(explode(' - ',$daterange)) ));

        if (!empty($start_date and !empty($end_date))) {
            $date_limit_ids = $wpdb->get_col("SELECT data_id FROM {$wpdb->prefix}cf7_vdata_entry WHERE name='submit_time' AND  
                                               CAST({$wpdb->prefix}cf7_vdata_entry.value AS DATE) >= '" . date_format($start_date, "Y-m-d") . "' AND 
                                               CAST({$wpdb->prefix}cf7_vdata_entry.value AS DATE) <= '" . date_format($end_date, "Y-m-d") . " 23:59:59' ");

            if (!empty($date_limit_ids) and is_array($date_limit_ids)) {
                $form_query .= " AND ( main_entry.data_id IN ('" . implode("','", $date_limit_ids) . "') )";
            } else {
                $form_query .= " AND ( main_entry.data_id IN ('-1') )";
            }
        }
    } else {

        if (!empty($_REQUEST['start_date'])) {
            $start_date = @date_create_from_format("d/m/Y", sanitize_text_field($_REQUEST['start_date']));
            if (!empty($start_date)) {
                $start_date_limit_ids = $wpdb->get_col("SELECT data_id FROM {$wpdb->prefix}cf7_vdata_entry WHERE name='submit_time' AND  CAST({$wpdb->prefix}cf7_vdata_entry.value AS DATE) >= '" . date_format($start_date, "Y-m-d") . "'");
                if (!empty($start_date_limit_ids) and is_array($start_date_limit_ids)) {
                    $form_query .= " AND ( main_entry.data_id IN ('" . implode("','", $start_date_limit_ids) . "') )";
                }
            }
        }

        if (!empty($_REQUEST['end_date'])) {
            $end_date = @date_create_from_format("d/m/Y", sanitize_text_field($_REQUEST['end_date']));
            if (!empty($end_date)) {
                $end_date_limit_ids = $wpdb->get_col("SELECT data_id FROM {$wpdb->prefix}cf7_vdata_entry WHERE name='submit_time' AND  CAST({$wpdb->prefix}cf7_vdata_entry.value AS DATE) <= '" . date_format($end_date, "Y-m-d") . "'");
                if (!empty($end_date_limit_ids) and is_array($end_date_limit_ids)) {
                    $form_query .= " AND ( main_entry.data_id IN ('" . implode("','", $end_date_limit_ids) . "') )";
                }
            }
        }
    }

    if(!empty($search)){
        $search_ids = $wpdb->get_col("SELECT DISTINCT data_id FROM {$wpdb->prefix}cf7_vdata_entry WHERE name IN ('YourName','your-name','EnterYourName','Name','email','your-email','Email','submit_ip','Mobile','number-372','tel') AND value LIKE '%".$search."%' ");
        if(!empty($search_ids) and is_array($search_ids)) {
            $form_query.=" AND ( main_entry.data_id IN ('".implode("','",$search_ids)."') )";
        }
    }
    $data = ($wpdb->get_results("SELECT main_entry.* FROM {$wpdb->prefix}cf7_vdata_entry AS main_entry LEFT JOIN {$wpdb->prefix}cf7_vdata_entry AS ventry ON main_entry.ID=ventry.ID where main_entry.name in('". implode("','",$cf_fields) ."') AND {$form_query}",ARRAY_A));
    echo $wpdb->last_error;

    $processed_data = array();
    foreach($data as $data_item) {

        $data_id = $data_item['data_id'];

        if(empty($processed_data[ $data_id ])) {
            $processed_data[ $data_id ]['cf7_id'] = $data_item['cf7_id'];
        }

        if( in_array( $data_item['name'] , array('YourName','your-name','EnterYourName','Name') ) ) {
            $processed_data[ $data_id ] ['name'] = $data_item['value'];
        } elseif( in_array( $data_item['name'] , array('email','your-email','Email') ) ) {
            $processed_data[ $data_id ] ['email'] = $data_item['value'];
        } elseif( in_array( $data_item['name'] , array('Mobile','number-372','tel') ) ) {
            $processed_data[ $data_id ] ['tel'] = $data_item['value'];
        } elseif( $data_item['name'] === 'submit_ip' ) {
            $processed_data[ $data_id ] ['submit_ip'] = $data_item['value'];
        } elseif( $data_item['name'] === 'submit_time' ) {
            $processed_data[ $data_id ] ['submit_time'] = strtotime( $data_item['value'] );
        } elseif( $data_item['name'] === 'submit_time' ) {
            $processed_data[ $data_id ] ['submit_time'] = $data_item['value'];
        } else {
            $processed_data[ $data_id ] [ $data_item['name'] ] = $data_item['value'];
        }
    }

    uksort($processed_data,function ($a,$b) use($processed_data){
        //print_r([$a,$b]);
        if($processed_data[$a]['submit_time']===$processed_data[$b]['submit_time']) { return 0; }
        return (($processed_data[$a]['submit_time'] > $processed_data[$b]['submit_time']) ? -1 : 1);
    });

    $total = count($processed_data);
    $items_per_page = 10;
    $page = max(1, empty($_REQUEST['cpage'])?0:intval($_REQUEST['cpage']));

    ?><div class="wrap our-class">
            <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
            <script type="text/javascript" src="https://cdn.jsdelivr.net/jquery/latest/jquery.min.js"></script>
            <script type="text/javascript" src="https://cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>
            <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
            <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />

            <form class="vsz-cf7-listing row" action="<?php print esc_url($url);?>" method="get" id="cf7d-admin-action-frm" >
                <input type="hidden" name="page" value="all-form-listing">
                <input type="hidden" name="fid" value="<?php echo esc_html($fid); ?>">
                <input type="hidden" name="_wpnonce" value="<?php echo esc_html(wp_create_nonce('vsz-cf7-action-nonce')); ?>"><?php
                //Display setting screen button
                //do_action('vsz_cf7_display_settings_btn', $fid);
                ?><div class="span12">
                    <div class="date-filter from-to" style="display: flex;justify-content: space-between;;">
                        <div class="from-to-date-search">
                            <input type="text" name="daterange" id="daterange" placeholder="select Date Range" value="<?php echo(empty($_REQUEST['daterange'])?'':$_REQUEST['daterange']); ?>" />

                            <!--<input type="text" name="start_date" id="start_date" placeholder="From" value="<?php /*print isset($_REQUEST['start_date']) ? esc_attr(sanitize_text_field($_REQUEST['start_date'])) : '';*/?>" class="input-cf-date">
                            <input type="text" name="end_date" id="end_date" placeholder="To" value="<?php /*print isset($_REQUEST['end_date']) ? esc_attr(sanitize_text_field($_REQUEST['end_date'])) : '';*/?>" class="input-cf-date" >-->
                            <input type="submit" name="search_date" id="search_date" value="<?php esc_html_e('Search By Date',VSZ_CF7_TEXT_DOMAIN);  ?>" title="<?php esc_html_e('Search By Date',VSZ_CF7_TEXT_DOMAIN);  ?>" class="button action" >
                        </div>
                        <div style="display: flex;column-gap: 1rem;">
                            <div class="type-something"><?php
                                //Display Search section here
                                do_action('vsz_cf7_after_datesection_btn', $fid);
                                ?></div>
                            <div class="reset-class"><a href="<?php print esc_url($url);?>" title="<?php esc_html_e('Reset All',VSZ_CF7_TEXT_DOMAIN);  ?>" class="button"><?php esc_html_e('Reset All',VSZ_CF7_TEXT_DOMAIN); ?></a></div>
                        </div>
                    </div>
                    <div class="clear"></div>
                </div>
                <div class="span12 bulk-actions">
                    <div class="tablenav top">
                        <div class="actions bulkactions">


                            <?php

                            ?><div class="tablenav-pages">
                            <span class="displaying-num"><?php echo (($total == 1) ?
                                    '1 ' . __('item') :
                                    $total . ' ' . __('items')) ?></span>
                                <span class="pagination-links"><?php
                                    //Setup pagination structure
                                    print ( paginate_links(array(
                                        'base' => add_query_arg('cpage', '%#%'),
                                        'format' => '',
                                        'prev_text' => __('&laquo;'),
                                        'next_text' => __('&raquo;'),
                                        'total' => ceil($total / $items_per_page),
                                        'current' => $page,
                                    )));


                                    ?></span>
                            </div>
                        </div>
                        <br class="clear">
                    </div>
                </div>
                <div class="span12 table-structure">
                    <div class="table-inner-structure">
                        <table class="wp-list-table widefat fixed striped posts cf7d-admin-table">
                            <thead>
                                <tr>
                                    <td id="cb" class="manage-column column-cb check-column"><input type="checkbox" id="cb-select-all-1" /></td>
                                    <td class="manage-column column-view check-column"></td>
                                    <td class="manage-column column-name">Name</td>
                                    <td class="manage-column column-email">Email</td>
                                    <td class="manage-column column-tel">Tel</td>
                                    <td class="manage-column column-ip">Form</td>
                                    <td class="manage-column column-ip">IP</td>
                                    <td class="manage-column column-date">Date/Time</td>
                                </tr>
                            </thead>
                            <tbody><?php
                                $processed_data = array_slice($processed_data,($page-1)*$items_per_page,($page)*$items_per_page);
                                //Get all fields related information
                                if(!empty($processed_data)){
                                    foreach ($processed_data as $k => $v) {
                                        $getDatanonce = wp_create_nonce( 'vsz-cf7-get-entry-nonce-'.intval($v['cf7_id']) );

                                        $k = (int)$k;
                                        ?>
                                        <tr>
                                            <th class="check-column" scope="row"><input id="cb-select-<?php echo(esc_html($k)); ?>" type="checkbox" title="Check" name="del_id[]" value="<?php echo(esc_html($k)) ?>" /></th>
                                            <td class="column-view check-column" data-info='<?php echo(htmlspecialchars(wp_json_encode($v),ENT_QUOTES)); ?>'><span class="dashicons dashicons-welcome-view-site"></span></td>
                                            <td class="column-name"><?php echo($v['name']); ?></td>
                                            <td class="column-email"><?php echo($v['email']); ?></td>
                                            <td class="column-tel"><?php echo(empty($v['tel'])?'-':$v['tel']); ?></td>
                                            <td class="column-tel"><?php echo(get_the_title($v['cf7_id'])); ?></td>
                                            <td class="column-ip"><?php echo($v['submit_ip']); ?></td>
                                            <td class="column-date"><?php echo(wp_date('F j, Y H:i:s',$v['submit_time'])); ?></td>
                                        </tr><?php
                                    }
                                } else {
                                    ?><tr>
                                        <td colspan="6">
                                            <?php esc_html_e('No records found.',VSZ_CF7_TEXT_DOMAIN);  ?>
                                        </td>
                                    </tr><?php
                                }
                            ?></tbody>
                            <tfoot>
                                <tr>
                                    <td id="cb" class="manage-column column-cb check-column"><input type="checkbox" id="cb-select-all-2" /></td>
                                    <td class="manage-column column-view check-column"></td>
                                    <td class="manage-column column-name">Name</td>
                                    <td class="manage-column column-email">Email</td>
                                    <td class="manage-column column-tel">Tel</td>
                                    <td class="manage-column column-ip">Form</td>
                                    <td class="manage-column column-ip">IP</td>
                                    <td class="manage-column column-date">Date/Time</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                <input type="hidden" name="cpage" value="<?php echo intval($page);?>" id="cpage">
                <input type="hidden" name="totalPage" value="<?php print ceil($total / $items_per_page);?>" id="totalPage">
                <?php $list_nonce = wp_create_nonce( 'vsz-cf7-form-list-nonce' ); ?>
                <input type="hidden" name="vsz_cf7_form_list_nonce"  value="<?php esc_html_e($list_nonce); ?>" />

            </form>



            <style>
                td.column-view.check-column .dashicons {
                    margin-top: 4px;
                    margin-left: 5px;
                    cursor: pointer;
                }
                .swal2-container {z-index: 9999999999999999;}
                .cf7details-modal { border-collapse: collapse;}
                .cf7details-modal th.details-key { width: 30%; text-align: right;padding-right: 0.5rem;border: 1px solid grey;}
                .cf7details-modal td.details-value {text-align: left;
                    padding-left: 2rem;
                    border: 1px solid grey;
                    padding-top: 0.5rem;
                    padding-bottom: 0.5rem;}
                td.manage-column {
                    font-weight: 600 !important;
                    text-transform: uppercase;
                }

                .span12.bulk-actions .tablenav.top .page-numbers {
                    display: inline-block;
                    vertical-align: baseline;
                    min-width: 30px;
                    min-height: 30px;
                    margin: 0;
                    padding: 0 4px;
                    font-size: 16px;
                    line-height: 1.625;
                    text-align: center;
                    color: #2271b1;
                    border-color: #2271b1;
                    background: #f6f7f7;
                    cursor: pointer;
                    border-width: 1px;
                    border-style: solid;
                    border-radius: 3px;
                    box-sizing: border-box;
                    text-decoration: none;
                    box-shadow: none;
                }
                .span12.bulk-actions .tablenav.top .page-numbers:hover{
                    background: #f0f0f1;
                    border-color: #0a4b78;
                    color: #0a4b78;
                }
                .span12.bulk-actions .tablenav.top span.page-numbers.current {
                    border: 0;
                    text-align: center;
                }

                .sp an12.bulk-actions .tablenav.top input[type=number].tiny-text {
                    width: 50px;
                    text-align: center;
                    padding: 0 0 0 12px;
                    box-shadow: none;
                }
                form#cf7d-admin-action-frm .span12 .date-filter.from-to .from-to-date-search {
                    margin-right: 5px;
                }
                form#cf7d-admin-action-frm .span12 {
                    float: left;
                }

                form#cf7d-admin-action-frm .span12.bulk-actions {
                    float: right;
                }

                form#cf7d-admin-action-frm .span12.bulk-actions .tablenav {
                    margin: 0;
                    padding-top: 0;
                }
                .form#cf7d-admin-action-frm .span12.bulk-actions .tablenav .actions{
                    padding-right:0;
                }
                table.cf7details-modal {
                    margin: 20px auto 0;
                    width: 100%;
                }

                table.cf7details-modal tbody tr:first-child {
                    display: none;
                }

                .cf7details-modal th.details-key {
                    text-transform: capitalize;
                    color: #000;
                    font-size: 15px;
                    padding: 10px;
                    text-align: left;
                    width: 40%;
                }

                .cf7details-modal td.details-value {
                    width: 60%;
                    padding: 10px 15px;
                    font-size: 16px;
                    color: #000;
                }

                .swal2-actions button {
                    border-radius: 0;
                    background-color: #000;
                    outline: 0 !important;
                    box-shadow: none !important;
                }

                .swal2-show {
                    width: 50% !important;
                    border-radius: 0;
                }
            </style>
            <script>
                //Setup pagination related functionality when click on page link then form submitted
                jQuery(".pagination-links a").on('click',function(){
                    var final_id;
                    var url = jQuery(this).attr('href');
                    var id_check = /[?&]cpage=([^&]+)/i;
                    var match = id_check.exec(url);
                    if(match != null){
                        final_id = parseInt(match[1]);
                    }
                    if(final_id != ''){
                        jQuery(this).attr("href","javascript:void(0)");
                        jQuery('#cpage').val(final_id);
                        document.getElementById('cf7d-admin-action-frm').submit();
                    }
                });

                //Add custom class in body tag when click on Setting button
                jQuery('#cf7d_setting_form').click(function(){
                    jQuery('body').addClass('our-body-class');
                });
                //Updating record
                jQuery(document).on('click','#update_cf7_value',function(){
                    var filterdata = jQuery('.vsz-cf7-listing').html();
                    jQuery('.cf7d-modal-form').append('<div style="display:none">'+filterdata+'</div>');
                });


                jQuery(document).ready(function($) {
                    $('input[name="daterange"]').daterangepicker({
                        opens: 'right',
                        maxDate: moment(),
                        autoApply: false,
                        singleDatePicker: false,
                        autoUpdateInput: false, 
                        locale: {
                            cancelLabel: 'Clear'
                        }
                    }, function(start, end, label) {
                        // Function called when dates are selected, if you want to perform any actions on date selection
                    });

                    $('input[name="daterange"]').attr('placeholder', 'Select a date range');

                    $('input[name="daterange"]').on('cancel.daterangepicker', function(ev, picker) {
                        $(this).val('');
                    });
                });


                jQuery('[data-info] .dashicons.dashicons-welcome-view-site').click(function (){
                    let _data = jQuery(this).parent().attr('data-info');
                    if(_data) {
                        _data = JSON.parse(_data);
                        if(_data) {
                            let _html = '<table class="cf7details-modal"><tbody>';
                            Object.keys(_data).forEach(function(index) {
                                _html+=`<tr><th class="details-key">${index}</th><td class="details-value">${_data[index]}</td></tr>`;
                            });
                            _html+=`</tbody></table>`;

                            Swal.fire({
                                width: '80%',
                                height: 'auto',
                                title: "Preview",
                                html: _html,
                                showCloseButton: true,

                            }).then((result) => {

                            });
                        }
                    }
                });
            </script>
    </div><?php
}

//Get form Id related fields information
$fields = vsz_cf7_get_db_fields($fid);

