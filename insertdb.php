<?php
$db_host = 'FIXME';
$db_user = 'FIXME';
$db_password = 'FIXME';
$db_database = 'FIXME';

function getClientIP()
{
    $keys = array('HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR');
    foreach ($keys as $k) {
        if (!empty($_SERVER[$k]) && filter_var($_SERVER[$k], FILTER_VALIDATE_IP)) {
            return $_SERVER[$k];
        }
    }
    return "UNKNOWN";
}

if (isset($_POST['activity_type']) and
    isset($_POST['start']) and
    isset($_POST['end']) and
    isset($_POST['comment'])) {

    $mysqli = new mysqli($db_host, $db_user, $db_password, $db_database);
    /* check connection */
    if (mysqli_connect_errno()) {
        printf("Connect failed: %s\n", mysqli_connect_error());
        exit();
    }


    if ($stmt = $mysqli->prepare("INSERT INTO activity (activity_type, start, end, comment) " .
        "VALUES (?, ?, ?, ?)")) {
        $start = strftime('%Y-%m-%d %H:%M:%S', strtotime($mysqli->real_escape_string($_POST['start'])));
        $end = strftime('%Y-%m-%d %H:%M:%S', strtotime($mysqli->real_escape_string($_POST['end'])));
        $stmt->bind_param('isss',
            $_POST['activity_type'],
            $start,
            $end,
            $_POST['comment']
        );
        $stmt->execute();
        $stmt->close();
    } else {
        printf("Statement prepare failed: %s\n", $mysqli->error);
    }
    $mysqli->close();
} elseif (isset($_POST['short_description']) and
    isset($_POST['long_description']) and
    isset($_POST['screen_used'])) {
    $mysqli = new mysqli($db_host, $db_user, $db_password, $db_database);
    /* check connection */
    if (mysqli_connect_errno()) {
        printf("Connect failed: %s\n", mysqli_connect_error());
        exit();
    }


    if ($stmt = $mysqli->prepare("INSERT INTO activity_type (short_description, long_description, screen_used) " .
        "VALUES (?, ?, ?)")) {
        $stmt->bind_param('ssi',
            $_POST['short_description'],
            $_POST['long_description'],
            $_POST['screen_used']
        );
        $stmt->execute();
        $stmt->close();
    } else {
        printf("Statement prepare failed: %s\n", $mysqli->error);
    }
    $mysqli->close();
} elseif (isset($_GET['retrieve_activity_types'])) {
    $mysqli = new mysqli($db_host, $db_user, $db_password, $db_database);
    /* check connection */
    if (mysqli_connect_errno()) {
        printf("Connect failed: %s\n", mysqli_connect_error());
        exit();
    }
    $sql = "SELECT * FROM activity_type";
    if(isset($_GET["short_description"])){
        $short_description = $mysqli->real_escape_string($_GET['short_description']);
        $sql = "SELECT * FROM activity_type WHERE short_description='" . $short_description . "'";
    }
    $result = $mysqli->query($sql);

    $result_array = array();
    while($row =mysqli_fetch_assoc($result))
    {
        $result_array[] = $row;
    }

    echo json_encode($result_array);

    $mysqli->close();
}
?>
