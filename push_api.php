<?php
if ($_REQUEST['main_page'] == 'checkout_success') {
    sendNotifications();
}

function sendNotifications() {
    $order_sql = "SELECT 
    o.orders_id AS order_id, 
    ot.value AS total,
    (SELECT configuration_value FROM " . TABLE_CONFIGURATION . " WHERE configuration_key='DEFAULT_CURRENCY' LIMIT 1) AS currency_code  
    FROM " . TABLE_ORDERS . " o 
    INNER JOIN " . TABLE_ORDERS_TOTAL . " ot ON o.orders_id = ot.orders_id AND ot.class = 'ot_total' 
    WHERE o.orders_id = (SELECT MAX(orders_id) FROM orders)";


    tep_db_fetch_array(tep_db_query(""))['configuration_value'];

    $order = tep_db_fetch_array(tep_db_query($order_sql));

    $devices_query = tep_db_query("SELECT * FROM user_device_mob_api GROUP BY device_token"); 

    $devices = [];
    for ($i = 1; $i <= $devices_query->num_rows; $i++) {
        $devices[] = tep_db_fetch_array($devices_query);
    }

    $ids = [];
    foreach ($devices as $device){
        if ('ios' == strtolower($device['os_type'])) {
            $ids['ios'][] = $device['device_token'];
        }
        else {
            $ids['android'][] = $device['device_token'];
        }
    }

    if (0 < count($order)) {
        $msg = [
            'body'       => number_format( $order['total'], 2, '.', '' ),
            'title'      => "http://" . $_SERVER['HTTP_HOST'],
            'vibrate'    => 1,
            'sound'      => 1,
            'priority'   => 'high',
            'new_order'  => [
                'order_id'      => $order['order_id'],
                'total'         => number_format( $order['total'], 2, '.', '' ),
                'currency_code' => $order['currency_code'],
                'site_url'      => "http://" . $_SERVER['HTTP_HOST'],
            ],
            'event_type' => 'new_order'
        ];
        $msg_android = [
            'new_order'  => [
                'order_id'      => $order['order_id'],
                'total'         => number_format( $order['total'], 2, '.', '' ),
                'currency_code' => $order['currency_code'],
                'site_url'      => "http://" . $_SERVER['HTTP_HOST'],
            ],
            'event_type' => 'new_order'
        ];
        foreach ( $ids as $k => $mas ) {
            if ( $k == 'ios' ) {
                $fields = [
                    'registration_ids' => $ids[$k],
                    'notification'     => $msg,
                ];
            }
            else {
                $fields = [
                    'registration_ids' => $ids[$k],
                    'data'             => $msg_android
                ];
            }
            sendCurl($fields);
        }
    }
}

function sendCurl($fields){
    $API_ACCESS_KEY = 'AAAAlhKCZ7w:APA91bFe6-ynbVuP4ll3XBkdjar_qlW5uSwkT5olDc02HlcsEzCyGCIfqxS9JMPj7QeKPxHXAtgjTY89Pv1vlu7sgtNSWzAFdStA22Ph5uRKIjSLs5z98Y-Z2TCBN3gl2RLPDURtcepk';
    $headers = [
        'Authorization: key=' . $API_ACCESS_KEY,
        'Content-Type: application/json'
    ];
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://fcm.googleapis.com/fcm/send');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
    curl_exec($ch);
    curl_close($ch);
}



