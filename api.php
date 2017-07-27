<?php

define('API_VERSION', "2.0.1");
define('FINAL_ORDER_STATUS', 3); // ID of the finally succeed status of the order

require('includes/application_top.php');
require('admin/includes/filenames.php');
require('admin/' . DIR_WS_LANGUAGES . $language . '/' . FILENAME_CATEGORIES);

tep_db_query("CREATE TABLE IF NOT EXISTS " . "user_token_mob_api (id INT NOT NULL PRIMARY KEY AUTO_INCREMENT, user_id INT NOT NULL, token VARCHAR(32) NOT NULL )");
tep_db_query("CREATE TABLE IF NOT EXISTS " . "user_device_mob_api (id INT NOT NULL PRIMARY KEY AUTO_INCREMENT, user_id INT NOT NULL, device_token VARCHAR(500) , os_type VARCHAR(20))");

header('Content-Type: application/json');

if (isset($_GET['route'])) {
    switch ($_GET['route']) {
        case 'login':
            echo login();
            break;
        case 'deletedevicetoken':
            echo deleteUserDeviceToken();
            break;
        case 'updatedevicetoken':
            echo updateUserDeviceToken();
            break;
        case 'statistic':
            echo statistic();
            break;
        case 'orders';
            echo orders();
            break;
        case 'getorderinfo':
            echo getorderinfo();
            break;
        case 'paymentanddelivery':
            echo paymentanddelivery();
            break;
        case 'orderproducts':
            echo orderproducts();
            break;
        case 'orderhistory':
            echo orderhistory();
            break;
        case 'clients':
            echo clients();
            break;
        case 'clientinfo':
            echo clientinfo();
            break;
        case 'clientorders':
            echo clientorders();
            break;
        case 'products':
            echo products();
            break;
        case 'productinfo':
            echo productinfo();
            break;
        case 'changestatus':
            echo changestatus();
            break;
        case 'delivery':
            echo delivery();
            break;
        case 'updateproduct':
            echo updateproduct();
            break;
        case 'mainimage':
            echo mainimage();
            break;
        case 'deleteimage':
            echo deleteimage();
            break;
        case 'getcategories':
            echo getcategories();
            break;
        case 'getsubstatus':
            echo getsubstatus();
            break;
        default:
            break;
    }
}

/**
* 
* @api {post} api.php?route=login Login
* @apiName Login
* @apiVersion 2.0.1
* @apiGroup Auth
*
* @apiParam {String} username     User unique username.
* @apiParam {String} password     User's  password.
* @apiParam {String} os_type      User's device's os_type for firebase notifications.
* @apiParam {String} device_token User's device's token for firebase notifications.
*
* @apiSuccess {Number} version Current API version.
* @apiSuccess {String} token   Token.
*
* @apiSuccessExample Success-Response:
* HTTP/1.1 200 OK
* {
*     "response":
*     {
*         "token": "e9cf23a55429aa79c3c1651fe698ed7b",
*     }
*     "version": 2,
*     "status": true
* }
*
* @apiErrorExample Error-Response:
*
* {
*     "error": "Incorrect username or password",
*     "version": 2,
*     "status": false
* }
*
*/
function login() {
    if (isset($_POST['username']) && isset($_POST['password']) && !isset($_POST['token'])) {
        $accept = false;
        $username = tep_db_prepare_input($_POST['username']);
        $password = tep_db_prepare_input($_POST['password']);

        // email existing check
        $check_customer_query = tep_db_query("SELECT id, user_password, user_name FROM " . TABLE_ADMINISTRATORS . " WHERE user_name = '" . $username . "'");
        if (0 < $check_customer_query->num_rows) {
            $check_customer = tep_db_fetch_array($check_customer_query);
            $customer_id = (int)$check_customer['id'];
            $dbPassword = $check_customer['user_password'];

            // password check
            $password_accepted = tep_validate_password($password, $dbPassword);;
            if ($password_accepted){
                $check_user_token_query = tep_db_query("SELECT token FROM user_token_mob_api WHERE user_id='" . $customer_id . "'");
                if (0 < $check_user_token_query->num_rows) {
                    $check_user_token = tep_db_fetch_array($check_user_token_query);
                    $token = $check_user_token['token'];
                }
                else {
                    $token = md5(mt_rand());
                    tep_db_query("INSERT INTO user_token_mob_api (user_id, token) VALUES ( " . $customer_id . ", '" . $token . "')");
                }
                if (isset($_POST['device_token'])) {
                    $device_token =$_POST['device_token'];
                    $os_type = $_POST['os_type'] ? $_POST['os_type'] : '';
                    $device_token_exist_query = tep_db_query("SELECT device_token FROM user_device_mob_api WHERE device_token = '" . $device_token . "'");
                    if (0 == $device_token_exist_query->num_rows){
                        $device_token_exist = tep_db_fetch_array($device_token_exist_query);
                        tep_db_query("INSERT INTO user_device_mob_api (user_id, device_token, os_type) VALUES (" . $customer_id . ", '" . $device_token . "', '" . $os_type . "')");
                    }
                }
                $accept = true;
            }
        }
        if ($accept) {
            return json_encode(['response' => ['token' => $token], 'version' => API_VERSION, 'status' => true]);
        }
        else {
            return json_encode(['error' => 'Incorrect email or password', 'version' => API_VERSION, 'status' => false]);
        }
    }
    else {
        return json_encode(['error' => 'Parameters error', 'version' => API_VERSION, 'status' => false]);
    }
}

/**
* 
* @api {post} api.php?route=deletedevicetoken deleteUserDeviceToken
* @apiName deleteUserDeviceToken
* @apiGroup Auth
* @apiVersion 2.0.1
*
* @apiParam {String} old_token User's device's token for firebase notifications.
*
* @apiSuccess {Number}  version Current API version.
* @apiSuccess {Boolean} status  true.
*
* @apiSuccessExample Success-Response:
* HTTP/1.1 200 OK
* {
*     "response":
*     {
*        "version": 2,
*        "status": true
*     }
* }
*
* @apiErrorExample Error-Response:
* 
* {
*     "error": "Missing some params",
*     "version": 2,
*     "status": false
* }
*
*/
function deleteUserDeviceToken() {
    $old_token = $_POST['old_token'];
    if(tep_db_fetch_array(tep_db_query("SELECT * FROM user_device_mob_api WHERE device_token='" . $old_token . "'"))){
        tep_db_query("DELETE FROM user_device_mob_api WHERE device_token='" . $old_token . "'");
        return json_encode(['response' => ['version' => API_VERSION, 'status' => true]]);
    }
    else {
        return json_encode(['error' => 'Missing some params', 'version' => API_VERSION, 'status' => false]);
    }
}

/**
* 
* @api {post} api.php?route=updatedevicetoken updateUserDeviceToken
* @apiName updateUserDeviceToken
* @apiGroup Auth
* @apiVersion 2.0.1
*
* @apiParam {String} new_token User's device's new token for firebase notifications.
* @apiParam {String} old_token User's device's old token for firebase notifications.
*
* @apiSuccess {Number}  version Current API version.
* @apiSuccess {Boolean} status  true.
*
* @apiSuccessExample Success-Response:
* HTTP/1.1 200 OK
* {
*     "response":
*     {
*        "version": 2,
*        "status": true
*     }
* }
*
* @apiErrorExample Error-Response:
*
* {
*     "error": "Missing some params",
*     "version": 2,
*     "status": false
* }
*
*/
function updateUserDeviceToken() {
    $old_token = $_POST['old_token'];
    $new_token = $_POST['new_token'];
    if(tep_db_fetch_array(tep_db_query("SELECT * FROM user_device_mob_api WHERE device_token='" . $old_token . "'"))) {
        tep_db_query("UPDATE user_device_mob_api SET device_token='" . $new_token . "' WHERE device_token='" . $old_token . "'");
        return json_encode(['response' => ['version' => API_VERSION, 'status' => true]]);
    }
    else {
        return json_encode(['error' => 'Missing some params', 'version' => API_VERSION, 'status' => false]);
    }
}

/**
* 
* @api {get} api.php?route=statistic getDashboardStatistic
* @apiName getDashboardStatistic
* @apiGroup Statistic
* @apiVersion 2.0.1
*
* @apiParam {String} filter Period for filter(day/week/month/year).
* @apiParam {Token}  token  Your unique token.
*
* @apiSuccess {Array}   xAxis           Period of the selected filter.
* @apiSuccess {Array}   clients         Clients for the selected period.
* @apiSuccess {Array}   orders          Orders for the selected period.
* @apiSuccess {Number}  total_sales     Sum of sales of the shop.
* @apiSuccess {Number}  sale_year_total Sum of sales of the current year.
* @apiSuccess {String}  currency_code   Default currency of the shop.
* @apiSuccess {Number}  orders_total    Total orders of the shop.
* @apiSuccess {Number}  clients_total   Total clients of the shop.
* @apiSuccess {Number}  version         Current API version.
* @apiSuccess {Boolean} status          true.
*
* @apiSuccessExample Success-Response:
* HTTP/1.1 200 OK
* {
*     "response": {
*         "xAxis": [
*             1,
*             2,
*             3,
*             4,
*             5,
*             6,
*             7
*        ],
*        "clients": [
*             0,
*             0,
*             0,
*             0,
*             0,
*             0,
*             0
*        ],
*        "orders": [
*             1,
*             0,
*             0,
*             0,
*             0,
*             0,
*             0
*        ],
*        "total_sales": "1920.00",
*        "sale_year_total": "305.00",
*        "currency_code": "RUR",
*        "orders_total": "4",
*        "clients_total": "3"
*     },
*     "version": 2,
*     "status": true
* }
*
* @apiErrorExample Error-Response:
*
*     {
*       "error": "Unknown filter set",
*       "version": 2,
*       "status": false
*     }
*
*/
function statistic() {
    $error_echo = errorToken();
    if ($error_echo) {
        return $error_echo;
    }
    $xAxis = [];
    $orders = [];
    $clients = [];
    $filter = isset($_REQUEST['filter']) ?  $_REQUEST['filter'] : 'day';
    $shift = 0;

    switch ($filter) {
        case 'day':
            $start = 0;
            $stop = 23;
            $orders_where = 'DAY(date_purchased) = DAY(NOW()) AND HOUR(date_purchased) = ' ;
            $customers_where = 'DAY(customers_info_date_account_created) = DAY(NOW()) AND HOUR(customers_info_date_account_created) = ';
            break;
        case 'week':
            $start = 0;
            $stop = 6;
            $shift = 1;
            $orders_where = 'WEEKOFYEAR(date_purchased) = WEEKOFYEAR(NOW()) AND WEEKDAY(date_purchased) = ' ;
            $customers_where = 'WEEKOFYEAR(customers_info_date_account_created) = WEEKOFYEAR(NOW()) AND WEEKDAY(customers_info_date_account_created) = ';
            break;
        case 'month':  
            $start = 1;
            $stop = date('t');
            $orders_where = 'MONTH(date_purchased) = MONTH(NOW()) AND DAY(date_purchased) = ' ;
            $customers_where = 'MONTH(customers_info_date_account_created) = MONTH(NOW()) AND DAY(customers_info_date_account_created) = ';
            break;
        case 'year':  
            $start = 1;
            $stop = 12;
            $orders_where = 'YEAR(date_purchased) = YEAR(NOW()) AND MONTH(date_purchased) = ' ;
            $customers_where = 'YEAR(customers_info_date_account_created) = YEAR(NOW()) AND MONTH(customers_info_date_account_created) = ';
            break;
        default:
            return json_encode(['error' => 'Unknown filter set', 'status' => false, 'version' => API_VERSION]);
    }

    for ($i = $start; $i <= $stop; $i++) {
        $xAxis[] = $i + $shift;
        $orders[] = tep_db_num_rows(tep_db_query("SELECT * FROM " . TABLE_ORDERS . " WHERE " . $orders_where . $i));
        $clients[] = tep_db_num_rows(tep_db_query("SELECT * FROM " . TABLE_CUSTOMERS_INFO . " WHERE " . $customers_where . $i));
    }

    $sale_year_total = tep_db_fetch_array(tep_db_query("
        SELECT SUM(ot.value) AS total FROM " . TABLE_ORDERS . " o 
        INNER JOIN " . TABLE_ORDERS_TOTAL . " ot ON o.orders_id = ot.orders_id 
        WHERE ot.class = 'ot_total' AND YEAR(o.date_purchased) = YEAR(NOW())
    "))['total'];
    $clients_total = tep_db_fetch_array(tep_db_query("SELECT COUNT(*) AS count FROM " . TABLE_CUSTOMERS))['count'];

    return json_encode([
        "response" => [
            'xAxis'           => $xAxis, 
            'clients'         => $clients, 
            'orders'          => $orders, 
            'total_sales'     => getTotalSum(),
            'sale_year_total' => $sale_year_total, 
            'currency_code'   => getCurrency(), 
            'orders_total'    => getOrdersCount(),
            'clients_total'   => $clients_total
        ],
        "status"   => true, 'version' => API_VERSION
    ]);
}

/**
* 
* @api {get} api.php?route=orders getOrders
* @apiName getOrders
* @apiGroup Orders
* @apiVersion 2.0.1
*
* @apiParam {Token}  token           Your unique token.
* @apiParam {Number} page            Number of the page.
* @apiParam {Number} limit           Limit of the orders for the page.
* @apiParam {String} fio             Full name of the client.
* @apiParam {Number} order_status_id Unique id of the order.
* @apiParam {Number} min_price       Min price of order.
* @apiParam {Number} max_price       Max price of order.
* @apiParam {Date}   date_min min    Date adding of the order.
* @apiParam {Date}   date_max max    Date adding of the order.
*
* @apiSuccess {Array}   orders                 Array of the orders.
* @apiSuccess {Number}  order[order_id]        Unique order ID.
* @apiSuccess {Number}  order[order_number]    Number of the order.
* @apiSuccess {String}  order[fio]             Client name.
* @apiSuccess {String}  order[status]          Status of the order.
* @apiSuccess {Number}  order[total]           Total sum of the order.
* @apiSuccess {Date}    order[date_added]      Date added of the order.
* @apiSuccess {String}  order[currency_code]   Default currency of the shop.
* @apiSuccess {Array}   statuses               Array of the order statuses.
* @apiSuccess {Number}  status[order_staus_id] ID of the order status.
* @apiSuccess {String}  status[name]           Order status name.
* @apiSuccess {Number}  status[language_id]    ID of the language.
* @apiSuccess {String}  currency_code          Default currency of the shop.
* @apiSuccess {Date}    total_quantity         Total quantity of the orders.
* @apiSuccess {Number}  total_sum              Total sum of the orders.
* @apiSuccess {Number}  version                Current API version.
* @apiSuccess {Boolean} status                 true.
*
* @apiSuccessExample Success-Response:
* HTTP/1.1 200 OK
* {
*     "response": {
*         "orders": [
*             {
*                 "order_id": "2",
*                 "order_number": "2",
*                 "fio": "Виталий Громов",
*                 "status": "Ожидает",
*                 "total": "79.9800",
*                 "date_added": "2017-07-12 16:06:03",
*                 "currency_code": "RUR"
*             },
*             {
*                 "order_id": "1",
*                 "order_number": "1",
*                 "fio": "Альберт Бойко",
*                 "status": "Обрабатывается",
*                 "total": "540.9800",
*                 "date_added": "2017-07-11 13:13:16",
*                 "currency_code": "RUR"
*             }
*         ],
*         "statuses": [
*             {
*                 "order_status_id": "3",
*                 "name": "Доставляется",
*                 "language_id": "2"
*             },
*             {
*                 "order_status_id": "2",
*                 "name": "Обрабатывается",
*                 "language_id": "2"
*             },
*             {
*                 "order_status_id": "1",
*                 "name": "Ожидает",
*                 "language_id": "2"
*             }
*         ],
*         "currency_code": "RUR",
*         "total_quantity": 2,
*         "total_sum": "620.9600",
*         "max_price": "540.9800"
*     },
*     "version": 2,
*     "status": true
* }
*
* @apiErrorExample Error-Response:
*
* {
*     "version": 2,
*     "status": false
* }
*
*/
function orders() {
    global $languages_id;
    $error_echo = errorToken();
    if ($error_echo) {
        return $error_echo;
    }

    $sql = "SELECT 
    o.orders_id AS order_id, 
    o.orders_id AS order_number, 
    o.customers_name AS fio, 
    s.orders_status_name AS status, 
    ot.value AS total, 
    o.date_purchased AS date_added 
    FROM " . TABLE_ORDERS . " o 
    INNER JOIN " . TABLE_ORDERS_STATUS . " s ON o.orders_status=s.orders_status_id AND s.language_id = '" . (int)$languages_id . "' 
    INNER JOIN " . TABLE_ORDERS_TOTAL . " ot ON o.orders_id = ot.orders_id AND ot.class = 'ot_total' 
    WHERE o.orders_id > 0";
    if (isset($_REQUEST['fio']) && !empty($_REQUEST['fio'])) {
        $sql .= " AND o.customers_name LIKE '%" . $_REQUEST['fio'] . "%'";
    }
    if (isset($_REQUEST['order_status_id']) && !empty($_REQUEST['order_status_id'])) {
        $sql .= " AND o.orders_status = " . (int)$_REQUEST['order_status_id'];
    }
    if (isset($_REQUEST['min_price']) && !empty($_REQUEST['min_price'])) {
        $sql .= " AND ot.value >= " . (float)$_REQUEST['min_price'];
    }
    if (isset($_REQUEST['max_price']) && !empty($_REQUEST['max_price'])) {
        $sql .= " AND ot.value <= " . (float)$_REQUEST['max_price'];
    }
    if (isset($_REQUEST['date_min']) && !empty($_REQUEST['date_min'])) {
        $sql .= " AND DATE_FORMAT(o.date_purchased,'%y-%m-%d') >= '" . date('y-m-d', strtotime($_REQUEST['date_min'])) . "'";
    } 
    if (isset($_REQUEST['date_max']) && !empty($_REQUEST['date_max'])) {
        $sql .= " AND DATE_FORMAT(o.date_purchased,'%y-%m-%d') <= '" . date('y-m-d', strtotime($_REQUEST['date_max'])) . "'";
    }
    $sql .= " ORDER BY o.orders_id DESC";
    if (isset($_REQUEST['limit'])) {
        $sql .= queryLimitString();
    }

    $orders_query = tep_db_query($sql);
    if (0 < $orders_query->num_rows) {
        $orders = [];
        $currency = getCurrency();
        for ($i = 0; $i <= $orders_query->num_rows - 1; $i++) {
            $orders[] = tep_db_fetch_array($orders_query);
            $orders[$i]['currency_code'] = $currency;
        }
        $max_price = tep_db_fetch_array(tep_db_query("SELECT MAX(value) AS max FROM " . TABLE_ORDERS_TOTAL . " WHERE class = 'ot_total'"))['max'];
        return json_encode([
            'response' => [
                'orders'         => $orders,
                'statuses'       => getStatuses(),
                'currency_code'  => getCurrency(),
                'total_quantity' => (int)getOrdersCount(),
                'total_sum'      => getTotalSum(),
                'max_price'      => $max_price
            ],
            'version'  => API_VERSION,
            'status'   => true
        ]);
    }
    else {
        return json_encode(['version' => API_VERSION, 'status' => false]);
    }
}

/**
* 
* @api {get} api.php?route=getorderinfo getOrderInfo
* @apiName getOrderInfo
* @apiGroup Orders
* @apiVersion 2.0.1
*
* @apiParam {Token}  token    Your unique token.
* @apiParam {Number} order_id Unique order ID.
*
* @apiSuccess {Number}  order_number           Number of the order.
* @apiSuccess {String}  fio                    Client's FIO.
* @apiSuccess {String}  status                 Status of the order.
* @apiSuccess {String}  email                  Client's email.
* @apiSuccess {Number}  telephone              Client's phone.
* @apiSuccess {Number}  total                  Total sum of the order.
* @apiSuccess {String}  currency_code          Default currency of the shop.
* @apiSuccess {Date}    date_added             Date added of the order.
* @apiSuccess {Array}   statuses               Array of the order statuses.
* @apiSuccess {Number}  status[order_staus_id] ID of the order status.
* @apiSuccess {String}  status[name]           Order status name.
* @apiSuccess {Number}  status[language_id]    ID of the language.
* @apiSuccess {Number}  version                Current API version.
* @apiSuccess {Boolean} status                 true.
*
* @apiSuccessExample Success-Response:
* HTTP/1.1 200 OK
* {
*     "response": {
*         "order_number": "1",
*         "fio": "Альберт Бойко",
*         "status": "Обрабатывается",
*         "email": "t-shop@i.ua",
*         "telephone": "222-22-22",
*         "total": "540.9800",
*         "date_added": "2017-07-11 13:13:16",
*         "currency_code": "RUR",
*         "statuses": [
*             {
*                 "order_status_id": "3",
*                 "name": "Доставляется",
*                 "language_id": "2"
*             },
*             {
*                 "order_status_id": "2",
*                 "name": "Обрабатывается",
*                 "language_id": "2"
*             },
*             {
*                 "order_status_id": "1",
*                 "name": "Ожидает",
*                 "language_id": "2"
*             }
*         ]
*     },
*     "version": 2,
*     "status": true
* }
*
* @apiErrorExample Error-Response:
*
* {
*     "error": "Can not found order with id = 5",
*     "version": 2,
*     "status": false
* }
*
*/
function getorderinfo() {
    global $languages_id;
    $error_echo = errorToken();
    if ($error_echo) {
        return $error_echo;
    }
    if (!isset($_REQUEST['order_id'])){
        return json_encode(['error' => "Can not find order with id = 0", 'version' => API_VERSION, 'status' => false]);
    }
    $order_id = (int)$_REQUEST['order_id'];
    $sql = "SELECT 
    o.orders_id AS order_number,
    o.customers_name AS fio, 
    s.orders_status_name AS status,
    o.customers_email_address AS email,
    o.customers_telephone AS telephone, 
    ot.value AS total, 
    o.date_purchased AS date_added 
    FROM " . TABLE_ORDERS . " o 
    INNER JOIN " . TABLE_ORDERS_STATUS . " s ON o.orders_status=s.orders_status_id AND s.language_id = '" . (int)$languages_id . "' 
    INNER JOIN " . TABLE_ORDERS_TOTAL . " ot ON o.orders_id = ot.orders_id AND ot.class = 'ot_total' 
    WHERE o.orders_id = " . $order_id;

    $order_query = tep_db_query($sql);
    if (0 < $order_query->num_rows) {
        $order_info = tep_db_fetch_array($order_query);
        $order_info['currency_code'] = getCurrency();
        $order_info['statuses'] = getStatuses();
        return json_encode(['response' => $order_info, 'status' => true, 'version' => API_VERSION]);
    }
    else {
        return json_encode(['error' => "Can not find order with id = $order_id", 'version' => API_VERSION, 'status' => false]);
    }
}

/**
* 
* @api {get} api.php?route=paymentanddelivery getOrderPaymentAndDelivery
* @apiName getOrderPaymentAndDelivery
* @apiGroup Orders
* @apiVersion 2.0.1
*
* @apiParam {Token}  token    Your unique token.
* @apiParam {Number} order_id Unique order ID.
*
* @apiSuccess {String}  payment_method   Payment method.
* @apiSuccess {String}  shipping_method  Shipping method.
* @apiSuccess {String}  shipping_address Shipping address.
* @apiSuccess {Number}  version          Current API version.
* @apiSuccess {Boolean} status           true.
*
* @apiSuccessExample Success-Response:
* HTTP/1.1 200 OK
* {
*     "response": {
*          "payment_method": "Cash on Delivery",
*          "shipping_method": "Flat Rate ()",
*          "shipping_address": "пр. Поля 1, Днепр, Днепропетровская обл., Украина."
*     },
*     "version": 2,
*     "status": true
* }
*
* @apiErrorExample Error-Response:
*
* {
*     "error": "Can not found order with id = 5",
*     "version": 2,
*     "status": false
* }
*
*/
function paymentanddelivery() {
    global $languages_id;
    $error_echo = errorToken();
    if ($error_echo) {
        return $error_echo;
    }
    if (!isset($_REQUEST['order_id'])){
        return json_encode(['error' => "Can not find order with id = 0", 'version' => API_VERSION, 'status' => false]);
    }

    $order_id = (int)$_REQUEST['order_id'];
    $sql = "SELECT 
    o.payment_method, 
    ot.title AS shipping_method, 
    o.delivery_street_address AS str, 
    o.delivery_city AS city, 
    o.delivery_state AS state, 
    o.delivery_country AS country 
    FROM " . TABLE_ORDERS . " o 
    INNER JOIN " . TABLE_ORDERS_TOTAL . " ot ON o.orders_id = ot.orders_id AND ot.class = 'ot_shipping'
    WHERE o.orders_id=" . $order_id;

    $order_query = tep_db_query($sql);
    if (0 < $order_query->num_rows) {
        $order = tep_db_fetch_array($order_query);
        // cut sign ':' at the end of shipping method title
        if (':' == substr($order['shipping_method'],-1)) {
            $order['shipping_method'] = substr($order['shipping_method'],0,strlen($order['shipping_method'])-1);
        }
        $order_info = [
            'payment_method' => $order['payment_method'], 
            'shipping_method' => $order['shipping_method'], 
            'shipping_address' => $order['str'] . ', ' . $order['city'] . ', ' . $order['state'] . ', ' . $order['country'] . '.'
        ];
        return json_encode(['response' => $order_info, 'status' => true, 'version' => API_VERSION]);
    }
    else {
        return json_encode(['error' => "Can not find order with id = $order_id", 'status' => false, 'version' => API_VERSION]);
    }
}

/**
* 
* @api {get} api.php?route=orderproducts getOrderProducts
* @apiName getOrderProducts
* @apiGroup Orders
* @apiVersion 2.0.1
*
* @apiParam {Token}  token    Your unique token.
* @apiParam {Number} order_id Unique order ID.
*
* @apiSuccess {Array}   products          Array of the order products.
* @apiSuccess {Url}     image             Picture of the product.
* @apiSuccess {String}  name              Name of the product.
* @apiSuccess {String}  model             Model of the product. 
* @apiSuccess {Number}  quantity          Quantity of the product.
* @apiSuccess {Number}  price             Price of the product.
* @apiSuccess {Number}  poduct_id         Unique product id.
* @apiSuccess {Array}   total_order_price Array of the order totals.
* @apiSuccess {Number}  total_price       Sum of product's prices.
* @apiSuccess {Number}  shipping_price    ost of the shipping.
* @apiSuccess {Number}  total             Total order sum.
* @apiSuccess {String}  currency_code     Currency of the order.
* @apiSuccess {Number}  version           Current API version.
* @apiSuccess {Boolean} status            true.
*
* @apiSuccessExample Success-Response:
* HTTP/1.1 200 OK
* {
*     "response": {
*         "products": [
*             {
*                 "image": "http://myshop.com/images/dvd/a_bugs_life.gif",
*                 "name": "A Bug's Life",
*                 "model": "DVD-ABUG",
*                 "quantity": "1",
*                 "price": "35.9900",
*                 "product_id": "8"
*             },
*             {
*                 "image": "http://myshop.com/images/matrox/mg400-32mb.gif",
*                 "name": "Matrox G400 32MB",
*                 "model": "MG400-32MB",
*                 "quantity": "1",
*                 "price": "499.9900",
*                 "product_id": "2"
*             }
*         ],
*         "total_order_price": {
*             "total_discount": 0,
*             "total_price": 535.98,
*             "shipping_price": 5,
*             "total": 540.98,
*             "currency_code": "RUR"
*         }
*     },
*     "version": 2,
*     "status": true
* }
*
* @apiErrorExample Error-Response:
*
* {
*     "error": "Can not found any products in order with id = 5",
*     "version": 2,
*     "status" : false
* }
*
*/
function orderproducts() {
    $error_echo = errorToken();
    if ($error_echo) {
        return $error_echo;
    }
    if (!isset($_REQUEST['order_id'])){
        return json_encode(['error' => "Can not found any products in order with id = 0", 'version' => API_VERSION, 'status' => false]);
    }

    $order_id = (int)$_REQUEST['order_id'];
    $sql = "SELECT
    IFNULL(CONCAT('http://', '" . $_SERVER['SERVER_NAME'] . "', '/images/', p.products_image), '') AS image, 
    o.products_name AS name, 
    o.products_model AS model,
    o.products_quantity AS quantity, 
    o.products_price AS price, 
    o.products_id AS product_id
    FROM " . TABLE_ORDERS_PRODUCTS . " o 
    INNER JOIN " . TABLE_PRODUCTS . " p ON p.products_id=o.products_id 
    WHERE o.orders_id = " . $order_id;

    $products_query = tep_db_query($sql);
    if (0 < $products_query->num_rows) {
        $products = [];
        for ($i = 1; $i <= $products_query->num_rows; $i++) {
            $products[] = tep_db_fetch_array($products_query);
        }

        $totals_query = tep_db_query("SELECT class, value FROM " . TABLE_ORDERS_TOTAL . " WHERE orders_id = " . $order_id);
        $totals = [];
        for ($i = 1; $i <= $totals_query->num_rows; $i++) {
            $row = tep_db_fetch_array($totals_query);
            $totals[$row['class']] = $row['value'];
        }

        return json_encode([
            'response' => [
                'products' => $products, 
                'total_order_price' => [
                    'total_discount' => 0,
                    'total_price' => (float)$totals['ot_subtotal'],
                    'shipping_price' => (float)$totals['ot_shipping'],
                    'total' => (float)$totals['ot_total'],
                    'currency_code' => getCurrency()
                ]
            ], 
            'version' => API_VERSION,
            'status' => true
        ]);
    }
    else {
        return json_encode(['error' => "Can not found any products in order with id = $order_id", 'version' => API_VERSION, 'status' => false]);
    }
}

/**
* 
* @api {get} api.php?route=orderhistory getOrderHistory
* @apiName getOrderHistory
* @apiGroup Orders
* @apiVersion 2.0.1
*
* @apiParam {Token}  token    Your unique token.
* @apiParam {Number} order_id Unique order ID.
*
* @apiSuccess {Array}   orders                 Array of the orders.
* @apiSuccess {String}  order[name]            Status of the order.
* @apiSuccess {Number}  order[order_status_id] ID of the status of the order.
* @apiSuccess {Date}    order[date_added]      Date of adding status of the order.
* @apiSuccess {String}  order[comment]         Some comment added from manager.
* @apiSuccess {Array}   statuses               Array of the order statuses.
* @apiSuccess {Number}  status[order_staus_id] ID of the order status.
* @apiSuccess {String}  status[name]           Order status name.
* @apiSuccess {Number}  status[language_id]    ID of the language.
* @apiSuccess {Number}  version                Current API version.
* @apiSuccess {Boolean} status                 true.
*
* @apiSuccessExample Success-Response:
* HTTP/1.1 200 OK
* {
*     "response": {
*         "orders": [
*             {
*                 "name": "Обрабатывается",
*                 "order_status_id": "2",
*                 "date_added": "2017-07-11 13:13:59",
*                 "comment": "OK"
*             },
*             {
*                 "name": "Ожидает",
*                 "order_status_id": "1",
*                 "date_added": "2017-07-11 13:13:16",
*                 "comment": ""
*             }
*         ],
*         "statuses": [
*             {
*                 "order_status_id": "3",
*                 "name": "Доставляется",
*                 "language_id": "2"
*             },
*             {
*                 "order_status_id": "2",
*                 "name": "Обрабатывается",
*                 "language_id": "2"
*             },
*             {
*                 "order_status_id": "1",
*                 "name": "Ожидает",
*                 "language_id": "2"
*             }
*         ]
*     },
*     "version": 2,
*     "status": true
* }
*
* @apiErrorExample Error-Response:
*
* {
*     "error": "Can not found any statuses for order with id = 5",
*     "version": 2,
*     "status" : false
* }
*
*/
function orderhistory() {
    global $languages_id;
    $error_echo = errorToken();
    if ($error_echo) {
        return $error_echo;
    }
    if (!isset($_REQUEST['order_id'])){
        return json_encode(['error' => "Can not found any statuses for order with id = 0", 'version' => API_VERSION, 'status' => false]);
    }
    $order_id = (int)$_REQUEST['order_id'];
    $sql = "SELECT 
    s.orders_status_name AS name, 
    h.orders_status_id AS order_status_id, 
    h.date_added, 
    h.comments AS comment 
    FROM " . TABLE_ORDERS_STATUS_HISTORY . " h 
    INNER JOIN " . TABLE_ORDERS_STATUS . " s ON h.orders_status_id = s.orders_status_id AND s.language_id = '" . (int)$languages_id . "' 
    WHERE h.orders_id=" . $order_id ." 
    ORDER BY h.orders_status_history_id DESC";

    $orders_query = tep_db_query($sql);
    if (0 < $orders_query->num_rows) {
        $orders = [];
        for ($i = 1; $i <= $orders_query->num_rows; $i++) {
            $orders[] = tep_db_fetch_array($orders_query);
        }
        return json_encode([
            'response' => [
                'orders'   => $orders,
                'statuses' => getStatuses(),
            ],
            'version'  => API_VERSION,
            'status'   => true
        ]);
    }
    else {
        return json_encode(['error' => "Can not found any statuses for order with id = $order_id", 'version' => API_VERSION, 'status' => false]);
    }
}

/**
* 
* @api {get} api.php?route=clients getClients
* @apiName getClients
* @apiGroup Clients
* @apiVersion 2.0.1
*
* @apiParam {Token}  token Your unique token.
* @apiParam {Number} page  Number of the page.
* @apiParam {Number} limit Limit of the orders for the page.
* @apiParam {String} fio   Client first name and last name separated by a space.
* @apiParam {String} sort  Param for sorting clients (sum|quantity|date_added).
*
* @apiSuccess {Array}   clients       Array of the clients.
* @apiSuccess {Number}  client_id     Unique client ID.
* @apiSuccess {String}  fio           Client name.
* @apiSuccess {Number}  total         Total sum of client's orders.
* @apiSuccess {Number}  quantity      Total quantity of client's orders.
* @apiSuccess {String}  currency_code Default currency of the shop.
* @apiSuccess {Number}  version       Current API version.
* @apiSuccess {Boolean} status        true.
*
* @apiSuccessExample Success-Response:
* HTTP/1.1 200 OK
* {
*     "response": {
*         "clients": [
*             {
*                 "client_id": "2",
*                 "fio": "Виталий Громов",
*                 "total": "1274.9700",
*                 "quantity": "1",
*                 "currency_code": "RUR"
*             },
*             {
*                 "client_id": "1",
*                 "fio": "Альберт Бойко",
*                 "total": "902.9100",
*                 "quantity": "5",
*                 "currency_code": "RUR"
*             }
*         ]
*     },
*     "version": 2,
*     "status": true
* }
*
* @apiErrorExample Error-Response:
*
* {
*     "error": "Not one client found",
*     "version": 2,
*     "status" : false
* }
*
*/
function clients() {
    $error_echo = errorToken();
    if ($error_echo) {
        return $error_echo;
    }

    $sql = "SELECT 
    c.customers_id AS client_id, 
    CONCAT(c.customers_firstname, ' ', c.customers_lastname) AS fio, 
    SUM(ot.value) AS total, 
    COUNT(ot.orders_id) AS quantity
    FROM " . TABLE_CUSTOMERS . " c 
    INNER JOIN " . TABLE_ORDERS . " o ON c.customers_id = o.customers_id 
    INNER JOIN " . TABLE_ORDERS_TOTAL . " ot ON o.orders_id = ot.orders_id AND ot.class = 'ot_total' 
    WHERE c.customers_id > 0 ";
    if (isset($_REQUEST['fio']) && !empty($_REQUEST['fio'])) {
        $name = explode(' ', $_REQUEST['fio']);
        $sql .= "AND c.customers_firstname LIKE '%" . $name[0] . "%' AND c.customers_lastname LIKE '%" . $name[1] . "%' ";
    }
    $sql .= " GROUP BY c.customers_id";
    $sql .= " HAVING client_id > 0";
    $sort = isset($_REQUEST['sort']) ? $_REQUEST['sort'] : '';
    switch ($sort) {
        case 'sum':
            $sql .= " ORDER BY total DESC";
            break;
        case 'quantity':
            $sql .= " ORDER BY quantity DESC";
            break;
        default:
            $sql .= " ORDER BY c.customers_id DESC";
    }
    if (isset($_REQUEST['limit'])) {
        $sql .= queryLimitString();
    }

    $clients_query = tep_db_query($sql);
    if (0 < $clients_query->num_rows) {
        $clients = [];
        $currency = getCurrency();
        for ($i = 0; $i <= $clients_query->num_rows - 1; $i++) {
            $clients[] = tep_db_fetch_array($clients_query);
            $clients[$i]['currency_code'] = $currency;
        }
        $max_price = tep_db_fetch_array(tep_db_query("SELECT MAX(value) AS max FROM " . TABLE_ORDERS_TOTAL . " WHERE class = 'ot_total'"))['max'];
        return json_encode(['response' => ['clients' => $clients], 'version' => API_VERSION, 'status' => true]);
    }
    else {
        return json_encode(['error' => "Not one client found", 'version' => API_VERSION, 'status' => false]);
    }
}

/**
* 
* @api {get} api.php?route=clientinfo getClientInfo
* @apiName getClientInfo
* @apiGroup Clients
* @apiVersion 2.0.1
*
* @apiParam {Token}  token     Your unique token.
* @apiParam {Number} client_id Unique client ID.
*
* @apiSuccess {Number}  client_id     Unique client ID.
* @apiSuccess {String}  fio           Client name.
* @apiSuccess {Number}  total         Total sum of client's orders.
* @apiSuccess {Number}  quantity      Total quantity of client's orders.
* @apiSuccess {String}  email         Client's email.
* @apiSuccess {Number}  telephone     Client's phone.
* @apiSuccess {String}  currency_code Default currency of the shop.
* @apiSuccess {Number}  completed     Total quantity of completed orders.
* @apiSuccess {Number}  version       Current API version.
* @apiSuccess {Boolean} status        true.
*
* @apiSuccessExample Success-Response:
* HTTP/1.1 200 OK
* {
*     "response": {
*         "client_id": "1",
*         "fio": "Temp Shop",
*         "total": "902.9100",
*         "quantity": "5",
*         "email": "t-shop@i.ua",
*         "telephone": "222-22-22",
*         "currency_code": "RUR"
*         "compleated": "1",
*     },
*     "version": 2,
*     "status": true
* }
*
* @apiErrorExample Error-Response:
*
* {
*     "error": "Can not find client with id = 5",
*     "version": 2,
*     "status" : false
* }
*
*/
function clientinfo() {
    $error_echo = errorToken();
    if ($error_echo) {
        return $error_echo;
    }
    if (!isset($_REQUEST['client_id'])){
        return json_encode(['error' => "Can not find client with id = 0", 'version' => API_VERSION, 'status' => false]);
    }
    $client_id = (int)$_REQUEST['client_id'];

    $sql = "SELECT 
    c.customers_id as client_id, 
    CONCAT(c.customers_firstname, ' ', c.customers_lastname) AS fio, 
    SUM(ot.value) AS total, 
    COUNT(ot.orders_id) AS quantity, 
    c.customers_email_address AS email,
    c.customers_telephone AS telephone 
    FROM " . TABLE_CUSTOMERS . " c 
    INNER JOIN " . TABLE_ORDERS . " o ON c.customers_id=o.customers_id 
    INNER JOIN " . TABLE_ORDERS_TOTAL . " ot ON o.orders_id = ot.orders_id AND ot.class = 'ot_total' 
    WHERE o.customers_id = " . $client_id . "
    HAVING client_id = " . $client_id;

    $client_query = tep_db_query($sql);
    if (0 < $client_query->num_rows) {
        $client_info = tep_db_fetch_array($client_query);
        $client_info['currency_code'] = getCurrency();

        $client_info['compleated'] = tep_db_fetch_array(tep_db_query("SELECT COUNT(*) AS count FROM " . TABLE_ORDERS . " WHERE orders_status = " . FINAL_ORDER_STATUS . " AND customers_id = " . $client_id))['count'];

        return json_encode(['response' => $client_info, 'status' => true, 'version' => API_VERSION]);
    }
    else {
        return json_encode(['error' => "Can not find client with id = " . $client_id, 'version' => API_VERSION, 'status' => false]);
    }
}

/**
* 
* @api {get} api.php?route=clientorders getClientOrders
* @apiName getClientOrders
* @apiGroup Clients
* @apiVersion 2.0.1
*
* @apiParam {Token}  token     Your unique token.
* @apiParam {Number} client_id Unique client ID.
* @apiParam {String} sort      Param for sorting orders (total|date_added|completed).
*
* @apiSuccess {Number}  order_id      Unique order ID.
* @apiSuccess {Number}  order_number  Number of the order.
* @apiSuccess {String}  status        Status of the order.
* @apiSuccess {Number}  total         Total sum of the order.
* @apiSuccess {Date}    date_added    Date added of the order.
* @apiSuccess {String}  currency_code Default currency of the shop.
* @apiSuccess {Number}  version       Current API version.
* @apiSuccess {Boolean} status        true.
*
* @apiSuccessExample Success-Response:
* HTTP/1.1 200 OK
* {
*     "response": {
*         "orders": [
*             {
*                 "order_id": "2",
*                 "order_number": "2",
*                 "status": "Доставляется",
*                 "total": "79.9800",
*                 "date_added": "2017-07-13 16:06:03",
*                 "currency_code": "RUR"
*             },
*             {
*                 "order_id": "1",
*                 "order_number": "1",
*                 "status": "Обрабатывается",
*                 "total": "540.9800",
*                 "date_added": "2017-07-11 13:13:16",
*                 "currency_code": "RUR"
*             }
*         ]
*     },
*     "version": 2,
*     "status": true
* }
*
* @apiErrorExample Error-Response:
*
* {
*     "error": "Can not find client with id = 5",
*     "version": 2,
*     "status" : false
* }
*
*/
function clientorders() {
    global $languages_id;   
    $error_echo = errorToken();
    if ($error_echo) {
        return $error_echo;
    }
    if (!isset($_REQUEST['client_id'])){
        return json_encode(['error' => "Can not find client with id = 0", 'version' => API_VERSION, 'status' => false]);
    }
    $client_id = (int)$_REQUEST['client_id'];

    $sql = "SELECT 
    o.orders_id AS order_id, 
    o.orders_id AS order_number, 
    s.orders_status_name AS status, 
    ot.value AS total, 
    o.date_purchased AS date_added
    FROM " . TABLE_ORDERS . " o 
    INNER JOIN " . TABLE_ORDERS_STATUS . " s ON o.orders_status=s.orders_status_id AND s.language_id = '" . (int)$languages_id . "' 
    INNER JOIN " . TABLE_ORDERS_TOTAL . " ot ON o.orders_id = ot.orders_id AND ot.class = 'ot_total' 
    WHERE o.customers_id = " . $client_id;
    $sort = isset($_REQUEST['sort']) ? $_REQUEST['sort'] : '';
    switch ($sort) {
        case 'total':
            $sql .= " ORDER BY total DESC";
            break;
        case 'completed':
            $sql .= " ORDER BY (CASE WHEN s.orders_status_id = " . FINAL_ORDER_STATUS . " THEN 1 ELSE 2 END), order_id DESC";
            break;
        default:
            $sql .= " ORDER BY order_id DESC";
    }

    $orders_query = tep_db_query($sql);
    if (0 < $orders_query->num_rows) {
        $orders = [];
        $currency = getCurrency();
        for ($i = 0; $i <= $orders_query->num_rows - 1; $i++) {
            $orders[] = tep_db_fetch_array($orders_query);
            $orders[$i]['currency_code'] = $currency;
        }
        return json_encode(['response' => ['orders' => $orders], 'version' => API_VERSION, 'status' => true]);
    }
    else {
        return json_encode(['error' => "Can not find client with id = " . $client_id, 'version' => API_VERSION, 'status' => false]);
    }
}

/**
* 
* @api {get} api.php?route=products getProductsList
* @apiName getProductsList
* @apiGroup Products
* @apiVersion 2.0.1
*
* @apiParam {Token}  token Your unique token.
* @apiParam {Number} page  Number of the page.
* @apiParam {Number} limit Limit of the orders for the page.
* @apiParam {String} name  Name of the product.
*
* @apiSuccess {Array}   products      Array of the order products.
* @apiSuccess {Number}  poduct_id     Unique product id.
* @apiSuccess {String}  name          Name of the product.
* @apiSuccess {String}  model         Model of the product. 
* @apiSuccess {Number}  quantity      Quantity of the product.
* @apiSuccess {Number}  price         Price of the product.
* @apiSuccess {Url}     image         Picture of the product.
* @apiSuccess {String}  category      Category name.
* @apiSuccess {String}  currency_code Default currency of the shop.
* @apiSuccess {Number}  version       Current API version.
* @apiSuccess {Boolean} status        true.
*
* @apiSuccessExample Success-Response:
* HTTP/1.1 200 OK
* {
*     "response": {
*         "products": [
*              {
*                 "product_id": "28",
*                 "name": "Samsung Galaxy Tab",
*                 "model": "GT-P1000",
*                 "quantity": "99",
*                 "price": "749.9900",
*                 "image": "http://myshop.com/images/samsung/galaxy_tab.gif",
*                 "category": "Gadgets",
*                 "currency_code": "RUR"
*             },
*             {
*                 "product_id": "27",
*                 "name": "Hewlett Packard LaserJet 1100Xi",
*                 "model": "HPLJ1100XI",
*                 "quantity": "8",
*                 "price": "499.9900",
*                 "image": "http://myshop.com/images/hewlett_packard/lj1100xi.gif",
*                 "category": "Hardware",
*                 "currency_code": "RUR"
*             }
*         ]
*     },
*     "version": 2,
*     "status": true
* }
*
* @apiErrorExample Error-Response:
*
* {
*     "error": "Not one product found",
*     "version": 2,
*     "status" : false
* }
*
*/
function products() {
    global $languages_id;
    $error_echo = errorToken();
    if ($error_echo) {
        return $error_echo;
    }

    $sql = "SELECT
    p.products_id as product_id, 
    d.products_name as name, 
    p.products_model as model, 
    p.products_quantity as quantity, 
    p.products_price as price, 
    IFNULL(CONCAT('http://', '" . $_SERVER['SERVER_NAME'] . "', '/images/', p.products_image), '') AS image, 
    IFNULL(c.categories_id, 0) as category 
    FROM " . TABLE_PRODUCTS . " p 
    INNER JOIN " . TABLE_PRODUCTS_TO_CATEGORIES . " c ON p.products_id = c.products_id
    INNER JOIN " . TABLE_PRODUCTS_DESCRIPTION . " d ON p.products_id = d.products_id AND language_id = " . (int)$languages_id . "
    WHERE p.products_id > 0";
    if (isset($_REQUEST['name']) && !empty($_REQUEST['name'])) {
        $sql .= " AND d.products_name LIKE '%" . $_REQUEST['name'] . "%' ";
    }
    $sql .= " ORDER BY p.products_id DESC";
    if (isset($_REQUEST['limit'])) {
        $sql .= queryLimitString();
    }

    $products_query = tep_db_query($sql);
    if (0 < $products_query->num_rows) {
        $products = [];
        $currency = getCurrency();
        for ($i = 0; $i <= $products_query->num_rows - 1; $i++) {
            $products[] = tep_db_fetch_array($products_query);
            if (!empty($products[$i]['category'])) {
                $products[$i]['category'] = getParentCategoryID($products[$i]['category']);
            }
            $products[$i]['currency_code'] = $currency;
        }
        return json_encode(['response' => ['products' => $products], 'version' => API_VERSION, 'status' => true]);
    }
    else {
        return json_encode(['error' => "Not one product found", 'version' => API_VERSION, 'status' => false]);
    }
}

/**
* 
* @api {get} api.php?route=productinfo getProductInfo
* @apiName getProductInfo
* @apiGroup Products
* @apiVersion 2.0.1
*
* @apiParam {Token}  token     Your unique token.
* @apiParam {Number} poduct_id Unique product id.
* 
* @apiSuccess {Number}  poduct_id      Unique product id.
* @apiSuccess {String}  name           Name of the product.
* @apiSuccess {String}  model          Model of the product. 
* @apiSuccess {Number}  quantity       Quantity of the product.
* @apiSuccess {Number}  price          Price of the product.
* @apiSuccess {String}  description    Detail description of the product.
* @apiSuccess {String}  status_name    Satus of the product (Enabled|Disabled). 
* @apiSuccess {Number}  sku            SKU of the product.
* @apiSuccess {Array}   images         Array of the pictures of the product.
* @apiSuccess {Array}   categories     Array of the categories of the product.
* @apiSuccess {Array}   stock_statuses Array of the stock statuses of the product.
* @apiSuccess {String}  currency_code  Default currency of the shop.
* @apiSuccess {Number}  version        Current API version.
* @apiSuccess {Boolean} status         true.
*
* @apiSuccessExample Success-Response:
* HTTP/1.1 200 OK
* {
*     "response": {
*         "product_id": "28",
*         "name": "Samsung Galaxy Tab",
*         "model": "GT-P1000",
*         "quantity": "99",
*         "price": "749.9900",
*         "description": "Powered by a Cortex A8 1.0GHz application processor, the Samsung GALAXY Tab is designed to deliver high performance whenever and wherever you are.",
*         "status_name": "Enabled",
*         "sku": "28",
*         "images": [
*             {
*                 "image_id": -1
*                 "image": "http://oscommerce.local/images/samsung/galaxy_tab.gif",
*             },
*             {
*                 "image_id": "1"
*                 "image": "http://oscommerce.local/images/samsung/galaxy_tab_1.jpg",
*             },
*             {
*                 "image_id": "2"
*                 "image": "http://oscommerce.local/images/samsung/galaxy_tab_2.jpg",
*             },
*         ],
*         "categories": [
*             {
*                 "category_id": "21",
*                 "name": "Gadgets"
*             }
*         ],
*         "stock_statuses": [
*             {
*                 "status_id": "0",
*                 "name": "Нет в наличии"
*             },
*             {
*                 "status_id": "1",
*                 "name": "В наличии"
*             }
*         ],
*         "stock_status_name": "В наличии",
*         "currency_code": "RUR"
*     },
*     "version": 2,
*     "status": true
* }
*
* @apiErrorExample Error-Response:
*
* {
*     "error": "Can not find product with id = 5",
*     "version": 2,
*     "status" : false
* }
*
*/
function productinfo() {
    global $languages_id;
    $error_echo = errorToken();
    if ($error_echo) {
        return $error_echo;
    }
    if (!isset($_REQUEST['product_id'])){
        return json_encode(['error' => "Can not find product with id = 0", 'version' => API_VERSION, 'status' => false]);
    }
    $product_id = (int)$_REQUEST['product_id'];

    $sql = "SELECT
    p.products_id as product_id, 
    d.products_name as name, 
    p.products_model as model, 
    p.products_quantity as quantity, 
    p.products_price as price, 
    d.products_description as description,
    p.products_status as status_name,
    p.products_id as sku,
    IFNULL(CONCAT('http://', '" . $_SERVER['SERVER_NAME'] . "', '/images/', p.products_image), '') AS images, 
    IFNULL(c.categories_id, 0) as categories 
    FROM " . TABLE_PRODUCTS . " p 
    INNER JOIN " . TABLE_PRODUCTS_TO_CATEGORIES . " c ON p.products_id = c.products_id
    INNER JOIN " . TABLE_PRODUCTS_DESCRIPTION . " d ON p.products_id = d.products_id AND language_id = " . (int)$languages_id . "
    WHERE p.products_id = " . $product_id;
    if (isset($_REQUEST['limit'])) {
        $sql .= queryLimitString();
    }

    $products_query = tep_db_query($sql);
    if (0 < $products_query->num_rows) {
        $product = tep_db_fetch_array($products_query);
        $images = [];
        $images[] = ['image_id' => -1, 'image' => $product['images']];
        $images_query = tep_db_query("
            SELECT 
            id AS image_id, 
            CONCAT('http://', '" . $_SERVER['SERVER_NAME'] . "', '/images/', image) AS image 
            FROM " . TABLE_PRODUCTS_IMAGES . " 
            WHERE products_id = " . $product_id
        );        
        for ($i = 1; $i <= $images_query->num_rows; $i++) {
            $images[] = tep_db_fetch_array($images_query);
        }
        $product['description'] = strip_tags($product['description']);
        $product['categories'] = getProductCategories($product['categories']);
        if (empty($product['categories'])) {
            unset($product['categories']);
        }
        $product['images'] = $images;
        $product['stock_statuses'] = getStockStatuses('');
        $product['stock_status_name'] = (0 < $product['quantity']) ? TEXT_PRODUCT_AVAILABLE : TEXT_PRODUCT_NOT_AVAILABLE;
        $product['status_name'] = ($product['status_name']) ? 'Enabled' : 'Disabled';
        $product['currency_code'] = getCurrency();

        return json_encode(['response' => $product, 'version' => API_VERSION, 'status' => true]);
    }
    else {
        return json_encode(['error' => "Can not find product with id = " . $product_id, 'version' => API_VERSION, 'status' => false]);
    }
}

/**
* 
* @api {post} api.php?route=changestatus changeStatus
* @apiName changeStatus
* @apiGroup Orders
* @apiVersion 2.0.1
*
* @apiParam {Token}   token     Your unique token.
* @apiParam {Number}  order_id  Unique order ID.
* @apiParam {Number}  status_id Unique status ID.
* @apiParam {String}  comment   New comment for order status.
* @apiParam {Boolean} inform    Status of the informing client.
*
* @apiSuccess {String}  name       Name of the new status.
* @apiSuccess {String}  date_added Date of adding status.
* @apiSuccess {Number}  version    Current API version.
* @apiSuccess {Boolean} status     true.
*
* @apiSuccessExample Success-Response:
* HTTP/1.1 200 OK
* {
*     "response": {
*         "name": "Доставляется",
*         "date_added": "2017-07-21 12:08:07"
*     },
*     "version": 2,
*     "status": true
* }
*
* @apiErrorExample Error-Response:
*
* {
*     "error": "Missing some params",
*     "version": 2,
*     "status": false
* }
*
*/
function changestatus() {
    global $languages_id;
    $error_echo = errorToken();
    if ($error_echo) {
        return $error_echo;
    }
    if ((!isset($_POST['order_id']) && empty($_POST['order_id'])) || (!isset($_POST['status_id'])) && empty($_REQUEST['status_id'])) {
        return json_encode(['error' => "Missing some params", 'version' => API_VERSION, 'status' => false]);
    }
    $status_id = (int)$_POST['status_id'];
    $status_query = tep_db_query("SELECT orders_status_id FROM " . TABLE_ORDERS_STATUS . " WHERE orders_status_id = " . $status_id);
    if (0 == $status_query->num_rows) {
        return json_encode(['error' => "Can not find status with id = " . $status_id, 'version' => API_VERSION, 'status' => false]);
    }
    $order_id = (int)$_POST['order_id'];
    $order_query = tep_db_query("SELECT orders_id FROM " . TABLE_ORDERS . " WHERE orders_id = " . $order_id);
    if (0 == $order_query->num_rows) {
        return json_encode(['error' => "Can not find order with id = " . $order_id, 'version' => API_VERSION, 'status' => false]);
    }
    $comment = isset($_POST['comment']) ? $_POST['comment'] : '';
    $inform = isset($_POST['inform']) ? (int)$_POST['inform'] : 0;

    $query_update_order_status = "UPDATE " . TABLE_ORDERS . " SET orders_status = " . $status_id . " WHERE orders_id = " . $order_id;
    $query_update_status_history = "INSERT INTO " . TABLE_ORDERS_STATUS_HISTORY . " (orders_id, orders_status_id, date_added, customer_notified, comments) 
    VALUES (" . $order_id . ", " . $status_id . ", NOW(), " . $inform . ", '" . $comment . "')";

    if (tep_db_query($query_update_order_status) && tep_db_query($query_update_status_history)) {
        $sql = "SELECT s.orders_status_name AS name, h.date_added AS date_added 
        FROM " . TABLE_ORDERS_STATUS_HISTORY . " h 
        INNER JOIN " . TABLE_ORDERS_STATUS . " s ON h.orders_status_id = s.orders_status_id AND s.language_id = '" . (int)$languages_id . "' 
        WHERE h.orders_id = " . $order_id . " 
        ORDER BY h.orders_status_history_id DESC LIMIT 1";
        $response = tep_db_fetch_array(tep_db_query($sql));

        return json_encode(['response' => $response, 'status' => true, 'version' => API_VERSION]);
    }
    else {
        return json_encode(['error' => "Missing some params", 'status' => false, 'version' => API_VERSION]);
    }   
}

/**
* 
* @api {post} api.php?route=delivery changeOrderDelivery
* @apiName changeOrderDelivery
* @apiGroup Orders
* @apiVersion 2.0.1
*
* @apiParam {Token}  token    Your unique token.
* @apiParam {Number} order_id Unique order ID.
* @apiParam {String} address  New shipping address.
* @apiParam {String} city     New shipping city.
*
* @apiSuccess {Number}  version Current API version.
* @apiSuccess {Boolean} status  true.
*
* @apiSuccessExample Success-Response:
* HTTP/1.1 200 OK
* {
*     "version": 2,
*     "status": true
* }
*
* @apiErrorExample Error-Response:
*
* {
*     "error": "Missing some params",
*     "version": 2,
*     "status": false
* }
*
*/
function delivery() {
    global $languages_id;
    $error_echo = errorToken();
    if ($error_echo) {
        return $error_echo;
    }
    if ((!isset($_POST['order_id']) && empty($_POST['order_id'])) || (!isset($_POST['city']) && empty($_POST['city']) && !isset($_POST['address']) && empty($_POST['address']))) {
        return json_encode(['error' => "Missing some params", 'version' => API_VERSION, 'status' => false]);
    }
    $order_id = (int)$_POST['order_id'];
    $order_query = tep_db_query("SELECT delivery_city, delivery_street_address FROM " . TABLE_ORDERS . " WHERE orders_id = " . $order_id);
    if (0 == $order_query->num_rows) {
        return json_encode(['error' => "Can not find order with id = " . $order_id, 'version' => API_VERSION, 'status' => false]);
    }
    $delivery = tep_db_fetch_array($order_query);
    $city = $delivery['delivery_city'];
    $address = $delivery['delivery_street_address'];
    if (isset($_POST['city']) && !empty($_POST['city'])) {
        $city = $_POST['city'];
    }
    if (isset($_POST['address']) && !empty($_POST['address'])) {
        $address = $_POST['address'];
    }

    $sql = "UPDATE " . TABLE_ORDERS . " SET delivery_street_address='" . $address . "', delivery_city='" . $city . "' WHERE orders_id=$order_id";

    if (tep_db_query($sql)) {
        return json_encode(['status' => true, 'version' => API_VERSION]);
    }
    else {
        return json_encode(['error' => "Can not change address", 'status' => false, 'version' => API_VERSION]);
    }
}

/**
* 
* @api {post} api.php?route=updateproduct updateProduct
* @apiName updateProduct
* @apiGroup Products
* @apiVersion 2.0.1
*
* @apiParam {Token}  token       Your unique token.
* @apiParam {Number} product_id  Unique product ID.
* @apiParam {Number} price       Price of the product.
* @apiParam {String} name        Name of the product.
* @apiParam {Number} quantity    Quantity of the product.
* @apiParam {String} description Description of the product.
* @apiParam {String} model       Product model.
* @apiParam {Number} status      Product is enabled or disabled (1|0).
* @apiParam {Array}  categories  Array of categories of the produc.
* @apiParam {Files}  image       Array of the files of the pictures of the product.
*
* @apiSuccess {Number}  poduct_id Unique product id.
* @apiSuccess {Array}   images    Array of the pictures of the product.
* @apiSuccess {Number}  version   Current API version.
* @apiSuccess {Boolean} status    true.
*
* @apiSuccessExample Success-Response:
* HTTP/1.1 200 OK
* {
*     "response": {
*         "product_id": 41,
*         "images": [
*             {
*                 "image_id": "-1",
*                 "image": "http://oscommerce.local/images/a.jpg"
*             },
*             {
*                 "image_id": "96",
*                 "image": "http://oscommerce.local/images/a.jpg"
*             },
*             {
*                 "image_id": "97",
*                 "image": "http://oscommerce.local/images/b.jpg"
*             }
*         ]
*     },
*     "version": 2,
*     "status": true
* }
*
* @apiErrorExample Error-Response:
*
* {
*     "error": "Missing some params",
*     "version": 2,
*     "status" : false
* }
*
*/
function updateproduct() { 
    $error_echo = errorToken();
    if ($error_echo) {
        return $error_echo;
    }
    $success = false;
    $product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;

    if (0 < $product_id) {
        $product_query = tep_db_query("SELECT products_id FROM " . TABLE_PRODUCTS . " WHERE products_id = " . $product_id);
        if (0 == $product_query->num_rows) {
            return json_encode(['error' => "Can not find product with id = " . $product_id, 'version' => API_VERSION, 'status' => false]);
        }
    }
    $product = [];
    if (isset($_POST['model']) && !empty($_POST['model'])) {
        $product['products_model'] = $_POST['model'];
    }
    elseif (0 == $product_id) {
        $product['products_model'] = '';
    }
    if (isset($_POST['quantity']) && !empty($_POST['quantity'])) {
        $product['products_quantity'] = (int)$_POST['quantity'];
    }
    if (isset($_POST['price']) && !empty($_POST['price'])) {
        $product['products_price'] = (float)$_POST['price'];
    }
    if (isset($_POST['status']) && !empty($_POST['status'])) {
        $product['products_status'] = (int)$_POST['status'];
    }
    elseif (0 == $product_id) {
        $product['products_status'] = 1;
    }
    if (isset($_FILES['image']) && !empty($_FILES['image'])) {
        $files_images = $_FILES['image'];
        $images_count = count($files_images['name']);
        $shift = 0;
        for ($i = 0; $i < $images_count; $i++) {
            if (file_exists(DIR_WS_IMAGES.$files_images['name'][$i])) {
                $time = time();
                $files_images['name'][$i] = $time . '_' . $files_images['name'][$i];
            }
            move_uploaded_file($files_images['tmp_name'][$i], DIR_WS_IMAGES.$files_images['name'][$i]);
        }
        if (0 == $product_id) {
            $product['products_image'] = $files_images['name'][0];
            $shift = 1;
        }
    }

    $description = [];
    if (isset($_POST['name']) && !empty($_POST['name'])) {
        $description['products_name'] = $_POST['name'];
    }
    if (isset($_POST['description']) && !empty($_POST['description'])) {
        $description['products_description'] = $_POST['description'];
    }

    $category_id = (isset($_POST['categories']) && is_array($_POST['categories'])) ? (int)($_POST['categories'][0]) : 0;

    if (0 == $product_id) {
        if ((0 < count($product)) && (0 < count($description)) && (0 < $category_id)) {
            $product_id = dbProductAdd($product, $description, $category_id);
            $success = $product_id;
        }
    }
    else {
        $success = dbProductUpdate($product_id, $product, $description, $category_id);
    }

    if (isset($files_images)) {
        for ($i = 0 + $shift; $i < $images_count; $i++) {
            tep_db_query("INSERT INTO ". TABLE_PRODUCTS_IMAGES ." (products_id, image) VALUES ('" . $product_id . "', '" . $files_images['name'][$i] . "')");
        }
        tep_db_query("UPDATE ". TABLE_PRODUCTS_IMAGES ." SET sort_order = id WHERE sort_order = 0");
    }

    if ($success) {
        $images = [];
        $images_query = tep_db_query("
            SELECT -1 AS image_id, 
            CONCAT('http://', '" . $_SERVER['SERVER_NAME'] . "', '/images/', products_image) AS image
            FROM " . TABLE_PRODUCTS . " 
            WHERE products_id = " . $product_id);
        if (0 <= $images_query->num_rows) {
            $images[] = tep_db_fetch_array($images_query);
        }
        $images_query = tep_db_query("
            SELECT 
            id AS image_id, 
            CONCAT('http://', '" . $_SERVER['SERVER_NAME'] . "', '/images/', image) AS image 
            FROM " . TABLE_PRODUCTS_IMAGES . " 
            WHERE products_id = " . $product_id
        );
        for ($i = 1; $i <= $images_query->num_rows; $i++) {
            $images[] = tep_db_fetch_array($images_query);
        }
        return json_encode([
            'response' => [
                'product_id' => $product_id,
                'images'     => $images
            ],
            'version' => API_VERSION,
            'status' => true]);
    }
    else {
        return json_encode(['error' => "Missing some params", 'version' => API_VERSION, 'status' => false]);
    }
}

/**
* 
* @api {post} api.php?route=mainimage mainImage
* @apiName mainImage
* @apiGroup Products
* @apiVersion 2.0.1
*
* @apiParam {Token}  token       Your unique token.
* @apiParam {Number} product_id  Unique product ID.
* @apiParam {Number} image_id    Unique image ID.
*
* @apiSuccess {Number}  version   Current API version.
* @apiSuccess {Boolean} status    true.
*
* @apiSuccessExample Success-Response:
* HTTP/1.1 200 OK
* {
*     "version": 2,
*     "status": true
* }
*
* @apiErrorExample Error-Response:
*
* {
*     "error": "Missing some params",
*     "version": 2,
*     "status" : false
* }
*
*/
function mainimage() { 
    $error_echo = errorToken();
    if ($error_echo) {
        return $error_echo;
    }
    $success = false;
    $product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
    if (0 < $product_id) {
        $product_query = tep_db_query("SELECT products_image FROM " . TABLE_PRODUCTS . " WHERE products_id = " . $product_id);
        if (0 == $product_query->num_rows) {
            return json_encode(['error' => "Can not find product with id = " . $product_id, 'version' => API_VERSION, 'status' => false]);
        }
        $image_id = isset($_POST['image_id']) ? (int)$_POST['image_id'] : 0;
        if (0 < $image_id) {
            $image_query = tep_db_query("SELECT image FROM " . TABLE_PRODUCTS_IMAGES . " WHERE id = " . $image_id);
            if (0 == $image_query->num_rows) {
                return json_encode(['error' => "Can not find image with id = " . $image_id, 'version' => API_VERSION, 'status' => false]);
            }
            else {
                $product = [];
                $product['products_image'] = tep_db_fetch_array($image_query)['image'];
                $success = dbProductUpdate($product_id, $product, [], 0);
            }

        }
    }
    if ($success) {
        $old_main_image = tep_db_fetch_array($product_query)['products_image'];
        if (!empty($old_main_image)){
            tep_db_query("INSERT INTO ". TABLE_PRODUCTS_IMAGES ." (products_id, image) VALUES ('" . $product_id . "', '" . $old_main_image . "')");
        }
        tep_db_query("DELETE FROM " . TABLE_PRODUCTS_IMAGES . " WHERE id = " . $image_id);
        return json_encode(['version' => API_VERSION, 'status' => true]);
    }
    else {
        return json_encode(['error' => "Missing some params", 'version' => API_VERSION, 'status' => false]);
    }
}

/**
* 
* @api {post} api.php?route=deleteimage deleteImage
* @apiName deleteImage
* @apiGroup Products
* @apiVersion 2.0.1
*
* @apiParam {Token}  token       Your unique token.
* @apiParam {Number} product_id  Unique product ID.
* @apiParam {Number} image_id    Unique image ID.
*
* @apiSuccess {Number}  version   Current API version.
* @apiSuccess {Boolean} status    true.
*
* @apiSuccessExample Success-Response:
* HTTP/1.1 200 OK
* {
*     "version": 2,
*     "status": true
* }
*
* @apiErrorExample Error-Response:
*
* {
*     "error": "Missing some params",
*     "version": 2,
*     "status" : false
* }
*
*/
function deleteimage() { 
    $error_echo = errorToken();
    if ($error_echo) {
        return $error_echo;
    }
    $success = false;
    $product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
    if (0 < $product_id) {
        $product_query = tep_db_query("SELECT products_image FROM " . TABLE_PRODUCTS . " WHERE products_id = " . $product_id);
        if (0 == $product_query->num_rows) {
            return json_encode(['error' => "Can not find product with id = " . $product_id, 'version' => API_VERSION, 'status' => false]);
        }
        $main_image_name = tep_db_fetch_array($product_query)['products_image'];
        $image_id = isset($_POST['image_id']) ? (int)$_POST['image_id'] : 0;
        if (0 < $image_id) {
            $image_query = tep_db_query("SELECT image FROM " . TABLE_PRODUCTS_IMAGES . " WHERE id = " . $image_id);
            if (0 == $image_query->num_rows) {
                return json_encode(['error' => "Can not find image with id = " . $image_id, 'version' => API_VERSION, 'status' => false]);
            }
            else {
                $image_name = tep_db_fetch_array($image_query)['image'];
                $success = tep_db_query("DELETE FROM " . TABLE_PRODUCTS_IMAGES . " WHERE id = " . $image_id);
                if (($main_image_name <> $image_name) && (file_exists(DIR_WS_IMAGES . $image_name))) {
                    unlink(DIR_WS_IMAGES . $image_name);
                }
            }
        }
        elseif ((-1 == $image_id) && (!empty($main_image_name))) {
            $product['products_image'] = '';
            $success = dbProductUpdate($product_id, $product, [], 0);
            // main image delete from folder only if image absent in `porducts_images` table
            $image_query = tep_db_query("SELECT image FROM " . TABLE_PRODUCTS_IMAGES . " WHERE image = '" . $main_image_name . "'");
            if ((0 == $image_query->num_rows) && (file_exists(DIR_WS_IMAGES . $main_image_name))) {
                unlink(DIR_WS_IMAGES . $main_image_name);
            }
        }
    }
    if ($success) {
        return json_encode(['version' => API_VERSION, 'status' => true]);
    }
    else {
        return json_encode(['error' => "Missing some params", 'version' => API_VERSION, 'status' => false]);
    }
}

/**
* 
* @api {get} api.php?route=getcategories getCategories
* @apiName getCategories
* @apiGroup Products
* @apiVersion 2.0.1
*
* @apiParam {Token}  token       Your unique token.
* @apiParam {Number} category_id Unique category ID.
*
* @apiSuccess {Array}   categories Array of the child categories of the category.
* @apiSuccess {Number}  version    Current API version.
* @apiSuccess {Boolean} status     true.
*
* @apiSuccessExample Success-Response:
* HTTP/1.1 200 OK
* {
*     "response": {
*         "categories": [
*             {
*                 "category_id": "1",
*                 "name": "Hardware",
*                 "parent": true
*             },
*             {
*                 "category_id": "2",
*                 "name": "Software",
*                 "parent": true
*             },
*             {
*                 "category_id": "21",
*                 "name": "Gadgets",
*                 "parent": false
*             }
*         ]
*     },
*     "version": 2,
*     "status": true
* }
*
* @apiErrorExample Error-Response:
*
* {
*     "error": "Missing some params",
*     "version": 2,
*     "status" : false
* }
*
*/
function getcategories() {
    global $languages_id; 
    $error_echo = errorToken();
    if ($error_echo) {
        return $error_echo;
    }
    if (isset($_REQUEST['category_id'])) {
        $category_id = (0 < (int)$_REQUEST['category_id']) ? (int)$_REQUEST['category_id'] : 0;
        $categories = [];
        $categories_query = tep_db_query("
            SELECT c.categories_id AS category_id,
            d.categories_name AS name 
            FROM " . TABLE_CATEGORIES . " c 
            INNER JOIN " . TABLE_CATEGORIES_DESCRIPTION . " d ON c.categories_id = d.categories_id AND d.language_id = '" . (int)$languages_id . "'
            WHERE c.parent_id = " . (int)$category_id
        );
        // if the category has no child, then show this category
        if (0 == $categories_query->num_rows){
            $categories_query = tep_db_query("
                SELECT c.categories_id AS category_id,
                d.categories_name AS name 
                FROM " . TABLE_CATEGORIES . " c 
                INNER JOIN " . TABLE_CATEGORIES_DESCRIPTION . " d ON c.categories_id = d.categories_id AND d.language_id = '" . (int)$languages_id . "'
                WHERE c.categories_id = " . (int)$category_id
            );
        }
        for ($i = 0; $i <= $categories_query->num_rows-1; $i++) {
            $categories[] = tep_db_fetch_array($categories_query);
            $childs_query = tep_db_query("SELECT categories_id FROM " . TABLE_CATEGORIES . " WHERE parent_id = " . (int)$categories[$i]['category_id']);
            $categories[$i]['parent'] = (0 < $childs_query->num_rows) ? true : false;
        }

        if (0 < count($categories)) {
            return json_encode(['response' => ['categories' => $categories], 'version' => API_VERSION, 'status' => true]);
        }
    }
    return json_encode(['error' => "Missing some params", 'version' => API_VERSION, 'status' => false]);
}

/**
* 
* @api {get} api.php?route=getsubstatus getSubstatus
* @apiName getSubstatus
* @apiGroup Products
* @apiVersion 2.0.1
*
* @apiParam {Token} token Your unique token.
*
* @apiSuccess {Array}   stock_statuses Array of the categories of the product.
* @apiSuccess {Number}  version        Current API version.
* @apiSuccess {Boolean} status         true.
*
* @apiSuccessExample Success-Response:
* HTTP/1.1 200 OK
* {
*     "response": {
*         "stock_statuses": [
*             {
*                 "stock_status_id": "0",
*                 "name": "Нет в наличии"
*             },
*             {
*                 "stock_status_id": "1",
*                 "name": "В наличии"
*             }
*         ],
*     },
*     "version": 2,
*     "status": true
* }
*
* @apiErrorExample Error-Response:
*
* {
*     "error": "You need to be logged!",
*     "version": 2,
*     "status" : false
* }
*
*/
function getsubstatus() { 
    $error_echo = errorToken();
    if ($error_echo) {
        return $error_echo;
    }
    return json_encode(['response' => ['stock_statuses' => getStockStatuses('stock_')], 'version' => API_VERSION, 'status' => true]);
}


// ADDITIONAL FUNCTIONS //

function errorToken() {
    $error = false;
    $token  = isset($_REQUEST['token']) ? $_REQUEST['token'] : '';
    if (empty($token)) {
        $error = 'You need to be logged!';
    }
    else {
        $check_token_query = tep_db_query("SELECT * FROM user_token_mob_api WHERE token='" . $token . "'");
        if ( 0 == $check_token_query->num_rows) {
            $error = 'Your token is no longer relevant!';
        }
    }
    if ($error) {
        return json_encode(['error' => $error, 'version' => API_VERSION, 'status' => false]);
    }
    return false;
}

function queryLimitString() {
    $limit_per_page = (int)$_REQUEST['limit'];
    if (0 < $limit_per_page) {
        $page = (isset($_REQUEST['page']) && !empty($_REQUEST['page'])) ?  (int)$_REQUEST['page'] : 1;
        $page = (0 < $page) ? $page : 1;
        $records_on_page = $limit_per_page * $page - $limit_per_page;
        return " LIMIT " . $records_on_page . ", " . $limit_per_page;
    }
    else {
        return "";
    }
}

function getCurrency() {
    return tep_db_fetch_array(tep_db_query("SELECT configuration_value FROM " . TABLE_CONFIGURATION . " WHERE configuration_key='DEFAULT_CURRENCY' LIMIT 1"))['configuration_value'];
}

function getTotalSum() {
    return tep_db_fetch_array(tep_db_query("SELECT SUM(value) AS total FROM " . TABLE_ORDERS_TOTAL . " WHERE class = 'ot_total'"))['total'];
}

function getOrdersCount() {
    return tep_db_fetch_array(tep_db_query("SELECT COUNT(*) AS count FROM " . TABLE_ORDERS))['count'];;
}

function getStatuses() {
    global $languages_id;
    $statuses = [];
    $statuses_query = tep_db_query("SELECT orders_status_id AS order_status_id, orders_status_name AS name, language_id FROM " . TABLE_ORDERS_STATUS . " WHERE language_id = '" . (int)$languages_id . "'");
    for ($i = 1; $i <= $statuses_query->num_rows; $i++) {
        $statuses[] = tep_db_fetch_array($statuses_query);
    }
    return $statuses;
}

function getParentCategoryID($category_id) {
    global $languages_id;
    $category = tep_db_fetch_array(tep_db_query("SELECT categories_id, parent_id FROM " . TABLE_CATEGORIES . " WHERE categories_id = " . (int)$category_id));
    if (0 == $category['parent_id']) {
        return tep_db_fetch_array(tep_db_query("SELECT categories_name FROM " . TABLE_CATEGORIES_DESCRIPTION . " WHERE categories_id = " . (int)$category_id . " AND language_id = " . (int)$languages_id))['categories_name'];
    }
    else {
        return getParentCategoryID($category['parent_id']);
    }
}

function getProductCategories($categories_id) {
    global $languages_id;
    if (0 == (int)$categories_id) {
        return null;
    }
    $categories = [];
    do {
        extract(tep_db_fetch_array(tep_db_query("SELECT categories_id, parent_id FROM " . TABLE_CATEGORIES . " WHERE categories_id = " . (int)$categories_id)));
        if (isset($parent_id)) {
            $name = tep_db_fetch_array(tep_db_query("SELECT categories_name FROM " . TABLE_CATEGORIES_DESCRIPTION . " WHERE categories_id = " . (int)$categories_id . " AND language_id = " . (int)$languages_id))['categories_name'];
            $categories[] = ['category_id' => $categories_id, 'name' => $name];
            $categories_id = $parent_id;
        }
    } while (0 <> $parent_id);
    return $categories;
}

function getStockStatuses($prefix = '') {
    return [
        [
            $prefix . "status_id" => "0",
            "name" => TEXT_PRODUCT_NOT_AVAILABLE
        ],
        [
            $prefix . "status_id" => "1",
            "name" => TEXT_PRODUCT_AVAILABLE
        ]
    ];
}

function dbProductUpdate($product_id, $product, $description, $category_id) {
    $stat_first = $stat_second = $stat_third = 1;
    if (0 < count($product)) {
        $stat_first = 0;
        $sql = "UPDATE " . TABLE_PRODUCTS . " SET ";
        $i = 0;
        foreach ($product as $field => $value ) {
            if (0 < $i) $sql .= ", "; 
            $sql .= $field . " = '" . $value . "'";
            $i = 1;
        }
        $sql .= " WHERE products_id = " . $product_id;
        $stat_first = tep_db_query($sql);
    }

    if (0 < count($description)) {
        $stat_second = 0;
        $sql = "UPDATE " . TABLE_PRODUCTS_DESCRIPTION . " SET ";
        $i = 0;
        foreach ($description as $field => $value ) {
            if (0 < $i) $sql .= ", "; 
            $sql .= $field . " = '" . $value . "'";
            $i = 1;
        }
        $sql .= " WHERE products_id = " . $product_id;
        $stat_second = tep_db_query($sql);
    }

    if (0 < $category_id) {
        $stat_third = 0;
        $sql = "UPDATE " . TABLE_PRODUCTS_TO_CATEGORIES . " SET categories_id=$category_id WHERE products_id = " . $product_id;
        $stat_third = tep_db_query($sql);
    }

    return ($stat_first && $stat_second && $stat_third);
}

function dbProductAdd($product, $description, $category_id) {
    $stat_first = $stat_second = $stat_third = 0;
    if (0 < count($product)) {
        $sql = "INSERT INTO " . TABLE_PRODUCTS . " (";
        $i = 0;
        foreach ($product as $field => $value ) {
            if (0 < $i) $sql .= ", "; 
            $sql .= $field;
            $i = 1;
        }
        $sql .= ") VALUES (";
        $i = 0;
        foreach ($product as $field => $value ) {
            if (0 < $i) $sql .= ", "; 
            $sql .= "'" . $value . "'";
            $i = 1;
        }
        $sql .= " )";
        $stat_first = tep_db_query($sql);
        $product_id = tep_db_insert_id();
    }

    $languages_query = (tep_db_query("SELECT languages_id FROM " . TABLE_LANGUAGES));
    $languages = [];
    for ($i = 1; $i <= $languages_query->num_rows; $i++) {
        $languages[] = tep_db_fetch_array($languages_query)['languages_id'];
    }
    foreach ($languages as $lang ) {
        if (0 < count($description)) {
            $sql = "INSERT INTO " . TABLE_PRODUCTS_DESCRIPTION . " (products_id, language_id";
            foreach ($description as $field => $value ) {
                $sql .= ", " . $field;
            }
            $sql .= ") VALUES (" . $product_id . ", " . $lang;
            foreach ($description as $field => $value ) {
                $sql .= ", '" . $value . "'";
            }
            $sql .= " )";
            $stat_second = tep_db_query($sql);
        }
    }

    if (0 < $category_id) {
        $sql = "INSERT INTO " . TABLE_PRODUCTS_TO_CATEGORIES . " (products_id, categories_id) VALUES (" . $product_id . ", " . $category_id . ")";
        $stat_third = tep_db_query($sql);
    }

    if ($stat_first && $stat_second && $stat_third) {
        return $product_id;
    }
    else {
        return false;
    }
}