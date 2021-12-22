<?php


//Note: fill this info for your own MySql/MariaDB database server.
$db_host = "XXXXXXXX.XX";
$db_user = "XXXXX";
$db_password = "XXXXXX";
$db_database = "XXXXX";

//Set our own private data password hash here:
$private_password_hash = 'XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX';

// You can also include all of this info from a separate PHP file:
include "protected_info.php";

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


    if ($stmt = $mysqli->prepare("INSERT INTO activity_type (short_description, long_description, screen_used, color) " .
        "VALUES (?, ?, ?, ?)")) {
        $color = substr(str_shuffle('ABCDEF0123456789'), 0, 6);
        $stmt->bind_param('ssis',
            $_POST['short_description'],
            $_POST['long_description'],
            $_POST['screen_used'],
            $color
        );
        $stmt->execute();
        $stmt->close();
    } else {
        printf("Statement prepare failed: %s\n", $mysqli->error);
    }
    $mysqli->close();
} elseif (isset($_POST['retrieve_activity_types'])) {
    $get_private = false;
    if (isset($_POST['private_password']) && password_hash($_POST['private_password'], PASSWORD_DEFAULT) == $private_password_hash) {
        $get_private = true;
    }
    $mysqli = new mysqli($db_host, $db_user, $db_password, $db_database);
    /* check connection */
    if (mysqli_connect_errno()) {
        printf("Connect failed: %s\n", mysqli_connect_error());
        exit();
    }
    if ($get_private) {
        $sql = "SELECT * FROM activity_type WHERE short_description != Miscellaneous";
    } else {
        $sql = "SELECT * FROM activity_type WHERE private != 1";
    }

    if (isset($_POST["short_description"])) {
        $short_description = $mysqli->real_escape_string($_POST['short_description']);
        if ($get_private) {
            $sql = "SELECT * FROM activity_type WHERE short_description='" . $short_description . "'";
        } else {
            $sql = "SELECT * FROM activity_type WHERE short_description='" . $short_description . "' AND private != 1";
        }
    }
    $result = $mysqli->query($sql);

    $result_array = array();
    while ($row = mysqli_fetch_assoc($result)) {
        $result_array[] = $row;
    }

    echo json_encode($result_array);

    $mysqli->close();
} else {
    $get_private = false;
    if (isset($_POST['private_password']) && password_hash($_POST['private_password'], PASSWORD_DEFAULT) == $private_password_hash) {
        $get_private = true;
    }
    $mysqli = new mysqli($db_host, $db_user, $db_password, $db_database);
    /* check connection */
    if (mysqli_connect_errno()) {
        printf("Connect failed: %s\n", mysqli_connect_error());
        exit();
    }
    if ($get_private) {
        $sql = <<<EOD
SELECT activity_type.short_description AS activity, activity_type.screen_used AS screen, SUM(TIMESTAMPDIFF(minute, start, end)) AS duration,
STR_TO_DATE(CONCAT(YEARWEEK(start,3),'1'),'%x%v%w') AS week
FROM activity 
LEFT JOIN activity_type
ON activity.activity_type = activity_type.activity_type_id
GROUP BY week, activity, screen
EOD;
    } else {
        $sql = <<<EOD
SELECT 
CASE 
	WHEN activity_type.private != 1 THEN activity_type.short_description 
    ELSE "Miscellaneous"
END
AS activity, 
activity_type.screen_used 
AS screen, 
SUM(TIMESTAMPDIFF(minute, start, end)) AS duration,
STR_TO_DATE(CONCAT(YEARWEEK(start,3),'1'),'%x%v%w') AS week
FROM activity 
LEFT JOIN activity_type
ON activity.activity_type = activity_type.activity_type_id
GROUP BY week, activity, screen
EOD;
    }

    $result = $mysqli->query($sql);

    $result_array = array();
    while ($row = mysqli_fetch_assoc($result)) {
        $result_array[] = $row;
    }

    echo json_encode($result_array);
    $mysqli->close();
}
?>
