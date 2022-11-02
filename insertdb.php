<?php


//Note: fill this info & uncomment for your own MySql/MariaDB database server.
//$db_host = "XXXXXXXX.XX";
//$db_user = "XXXXX";
//$db_password = "XXXXXX";
//$db_database = "XXXXX";

//Set our own private data password hash here:
//$private_password_hash = 'XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX';

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

function checkPassword($password, $hash)
{
    if (isset($password)) {
        if (password_verify($password, $hash)) {
            return true;
        } else {
            http_response_code(401);
            echo "Authentication failed.";
            exit();
        }
    }
    return false;
}

if (isset($_POST['activity_type']) and
    isset($_POST['start']) and
    isset($_POST['end']) and
    isset($_POST['comment'])) {

    $mysqli = new mysqli($db_host, $db_user, $db_password, $db_database);
    /* check connection */
    if (mysqli_connect_errno()) {
        http_response_code(500);
        printf("Connect failed: %s\n", mysqli_connect_error());
        exit();
    }

    $activity_type = $_POST['activity_type'];
    $start = strftime('%Y-%m-%d %H:%M:%S', strtotime($mysqli->real_escape_string($_POST['start'])));
    $end = strftime('%Y-%m-%d %H:%M:%S', strtotime($mysqli->real_escape_string($_POST['end'])));
    $comment = $_POST['comment'];
    $need_insert = false;

    /* check whether entry exists */
    if ($stmt = $mysqli->prepare(<<<EOD
SELECT COUNT(1) count FROM `activity` WHERE
`activity`.`activity_type` = ? AND
`activity`.`start` = ? AND
`activity`.`end` = ?;
EOD
    )) {
        $stmt->bind_param('iss',
            $activity_type,
            $start,
            $end
        );
        $stmt->execute();
        $row = $stmt->get_result()->fetch_row();
        $count = $row[0];
        $stmt->close();
        if ($count > 0) {
            if ($stmt = $mysqli->prepare(<<<EOD
UPDATE `activity`
SET `comment` = ?
WHERE
`activity`.`activity_type` = ? AND
`activity`.`start` = ? AND
`activity`.`end` = ?;
EOD
            )) {
                $stmt->bind_param('siss',
                    $comment,
                    $activity_type,
                    $start,
                    $end
                );
                $stmt->execute();
                $stmt->close();
                http_response_code(200);
                echo "Entry exists; comment updated.";
            } else {
                http_response_code(500);
                printf("Statement prepare failed: %s\n", $mysqli->error);
            }
        } else {
            $need_insert = true;
        }
    } else {
        http_response_code(500);
        printf("Statement prepare failed: %s\n", $mysqli->error);
    }
    if ($need_insert) {
        if ($stmt = $mysqli->prepare("INSERT INTO activity (activity_type, start, end, comment) " .
            "VALUES (?, ?, ?, ?)")) {

            $stmt->bind_param('isss',
                $activity_type,
                $start,
                $end,
                $comment
            );
            $stmt->execute();
            $stmt->close();
            http_response_code(200);
            echo "Entry inserted.";
        } else {
            http_response_code(500);
            printf("Statement prepare failed: %s\n", $mysqli->error);
        }
    }
} elseif (isset($_POST['short_description']) and
    isset($_POST['long_description']) and
    isset($_POST['screen_used'])) {
    /* INSERTION SCRIPT FOR ADDING NEW ACTIVITY TYPES */

    $mysqli = new mysqli($db_host, $db_user, $db_password, $db_database);
    /* check connection */
    if (mysqli_connect_errno()) {
        http_response_code(500);
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
} elseif (isset($_POST['retrieve_activity_types'])) {
    /* RETRIEVAL SCRIPT FOR GETTING ACTIVITY TYPES */
    $get_private = checkPassword($_POST['private_password'], $private_password_hash);

    $mysqli = new mysqli($db_host, $db_user, $db_password, $db_database);
    /* check connection */
    if (mysqli_connect_errno()) {
        http_response_code(500);
        printf("Connect failed: %s\n", mysqli_connect_error());
        exit();
    }
    if ($get_private) {
        $sql = <<<EOD
SELECT * 
FROM activity_type 
WHERE short_description != 'Miscellaneous' 
  AND short_description != 'Miscellaneous (Screen)'
ORDER BY short_description;
EOD;
    } else {
        $sql = "SELECT * FROM activity_type WHERE private != 1 ORDER BY short_description;";
    }

    if (isset($_POST["short_description"])) {
        $short_description = $mysqli->real_escape_string($_POST['short_description']);
        if ($get_private) {
            $sql = "SELECT * FROM activity_type WHERE short_description='" . $short_description . "';";
        } else {
            $sql = "SELECT * FROM activity_type WHERE short_description='" . $short_description . "' AND private != 1;";
        }
    }
    $result = $mysqli->query($sql);

    $result_array = array();
    while ($row = mysqli_fetch_assoc($result)) {
        $result_array[] = $row;
    }

    echo json_encode($result_array);

} else {
    /* RETRIEVAL SCRIPT FOR GETTING LOG ENTRIES */
    $get_private = checkPassword($_POST['private_password'], $private_password_hash);
    $time_range_clause = "";
    if (isset($_POST['start']) && isset($_POST['end'])) {
        $start = $_POST['start'];
        $end = $_POST['end'];
        $time_range_clause = "AND activity.start >= $start AND activity.end <= $end";
    } else {
        $time_range_clause = "AND activity.start >= SUBDATE(NOW(), INTERVAL 1 YEAR)";
    }
    $mysqli = new mysqli($db_host, $db_user, $db_password, $db_database);
    /* check connection */
    if (mysqli_connect_errno()) {
        http_response_code(500);
        printf("Connect failed: %s\n", mysqli_connect_error());
        exit();
    }
    if ($get_private) {
        // Get entries for all activity types, including private ones
        $sql = <<<EOD
SELECT activity_type.short_description AS activity, activity_type.screen_used AS screen, SUM(TIMESTAMPDIFF(minute, start, end)) AS duration,
STR_TO_DATE(CONCAT(YEARWEEK(start,3),'1'),'%x%v%w') AS week
FROM activity 
INNER JOIN activity_type
ON activity.activity_type = activity_type.activity_type_id $time_range_clause
GROUP BY week, activity, screen
EOD;
    } else {
        // Get entries for all activity types EXCEPT private ones
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
INNER JOIN activity_type
ON activity.activity_type = activity_type.activity_type_id $time_range_clause 
GROUP BY week, activity, screen
EOD;
    }

    $result = $mysqli->query($sql);

    $result_array = array();
    while ($row = mysqli_fetch_assoc($result)) {
        $result_array[] = $row;
    }

    http_response_code(200);
    echo json_encode($result_array);
}
$mysqli->close();
?>