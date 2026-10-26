<?php

/*
 * Single shared mysqli connection
 */
class Database
{
    private static ?mysqli $conn = null;

    public static function connection(): mysqli
    {
        if (self::$conn === null) {
            $conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

            if (!$conn) {
                die('Database Connection Failed');
            }

            mysqli_set_charset($conn, 'utf8mb4');

            self::$conn = $conn;
        }

        return self::$conn;
    }
}
