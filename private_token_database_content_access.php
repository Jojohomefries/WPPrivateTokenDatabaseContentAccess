<?php
add_action( 'init', 'jdh_download_key_clean');
function jdh_download_key_clean() {
    global $wpdb;
    $wpdb->query("DELETE FROM ".$wpdb->prefix."download_key WHERE date_gen < (NOW() - INTERVAL 7 DAY)");
}

//create database table for document GET keys on wordpress init
add_action( 'init', 'jdh_download_key_create');
function jdh_download_key_create() {



    global $wpdb;
    $table_name = $wpdb->prefix. "download_key";
    global $charset_collate;
    $charset_collate = $wpdb->get_charset_collate();
    global $db_version;

    if( $wpdb->get_var("SHOW TABLES LIKE '" . $table_name . "'") !=  $table_name)
    {   
        $create_sql = "CREATE TABLE " . $table_name . " (
            id INT(11) NOT NULL AUTO_INCREMENT,
            download_key VARCHAR(200) NOT NULL,
            post_id INT(11) NOT NULL,
            date_gen TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id)
        ) ". $charset_collate .";";
    }
    require_once(ABSPATH . "wp-admin/includes/upgrade.php");
    dbDelta( $create_sql );

    //register the new table with the wpdb object
    if (!isset($wpdb->download_key))
    {
        $wpdb->download_key = $table_name;
        //add the shortcut so you can use $wpdb->stats
        $wpdb->tables[] = str_replace($wpdb->prefix, '', $table_name);
    }

}



add_action('wp_ajax_nopriv_submitContact', 'submitContact_cb');
add_action('wp_ajax_submitContact', 'submitContact_cb');
function submitContact_cb() {
    $contact_name = $_POST['contact_name'];
    $contact_email = $_POST['contact_email'];
    $msg = $_POST['message'];

$header = array();
    $header[] = "MIME-Version: 1.0\n";
    $header[] = "Content-Type: text/html; charset=utf-8\n";
    $header[] = $contact_email."\n";

    $message = "Contact Submission from Thought Leaders site:<br><br>";
    $message .= "Name: ".$contact_name.'<br>';
    $message .= "Email: ".$contact_email.'<br>';
    $message .= "Name: ".$msg.'<br>';

    $subject = "Contact Submission from Thought Leaders site - ".$contact_name;

    // send the email
    wp_mail('info@mni.com', $subject, $message, $header);

//echo $_POST['g-recaptcha-response'];
echo 'sent';
wp_die();
}



add_action('wp_ajax_nopriv_generateDownload', 'generateDownload_cb');
add_action('wp_ajax_generateDownload', 'generateDownload_cb');
function generateDownload_cb() {
    global $wpdb;
  
    $lead = array(
    'post_title' => $_POST['first_name'].' '.$_POST['last_name'],
    'post_status' => 'publish',
    'post_type' => 'jdh_lead',
    );
    $lead_id = wp_insert_post( $lead );

    foreach ($_POST as $key => $value) {
        if ($key !== 'first_name' || $key !== 'last_name' || $key !== 'action') {
            update_post_meta( $lead_id, $key, $value );
        }
    }
    
    $msg = 'Lead ID is: '.$lead_id.' and posted values: ';
    foreach ($_POST as $key => $value) {
        $msg.= 'Key: '.$key.' Value: '.$value.' --- ';
    }

    //get array of download keys
    $download_keys = $wpdb->get_col( "SELECT download_key FROM $wpdb->download_key" );

    $i = generate_uuid();
    do {
        $i = generate_uuid();
    } while (in_array($i, $download_keys));

    $wpdb->insert($wpdb->prefix.'download_key', array(
        'download_key' => $i,
        'post_id' => $_POST['document_id'],
    ));

    //$file = get_field( "file_attachment", $_POST['document_id'] );

    //$downloadlink = $file['url'].'?download_key='.$i;

    $downloadlink = get_permalink($_POST['document_id']).'?download_key='.$i.'&document_id='.$_POST['document_id'];

    // get the posted data
    $name = $_POST["first_name"].' '.$_POST["last_name"];
    $email_address = $_POST["email"];

    // write the email content
    $header = array();
    $header[] = "MIME-Version: 1.0\n";
    $header[] = "Content-Type: text/html; charset=utf-8\n";
    $header[] = "From: Thought Leadership <downloads@timemni.com>\n";

    $message = "Hello $name,<br><br>";
    $message .= "Please click this link to access your requested download. This link is valid for 24 hours.<br><br>";
    $message .="<a href='".$downloadlink."'>".$downloadlink."</a>";

    $subject = "Your download link is ready";

    // send the email
    wp_mail($email_address, $subject, $message, $header);

    echo $msg;
    wp_die();
}

