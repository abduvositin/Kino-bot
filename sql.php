<?php

$connect = mysqli_connect("localhost", "DB_USER", "DB_PAROL", "DB_NAME");

if ($connect) {
    echo "Ulandi<br>";
} else {
    die("Ulanmadi<br>" . mysqli_connect_error());
}

mysqli_query($connect, "CREATE TABLE IF NOT EXISTS admins (
    id INT(20) NOT NULL AUTO_INCREMENT,
    user_id VARCHAR(200) NOT NULL,
    PRIMARY KEY (id)
)");

mysqli_query($connect, "CREATE TABLE IF NOT EXISTS users (
    id INT(20) NOT NULL AUTO_INCREMENT,
    user_id VARCHAR(200) NOT NULL,
    time VARCHAR(100) NOT NULL,
    step VARCHAR(100) NOT NULL,
    PRIMARY KEY (id)
)");

mysqli_query($connect, "CREATE TABLE IF NOT EXISTS movie_data (
    id INT(20) NOT NULL AUTO_INCREMENT,
    movie_id VARCHAR(256) NOT NULL, 
    movie_name VARCHAR(256) NOT NULL,
    about VARCHAR(256) NOT NULL,
    file_id VARCHAR(256) NOT NULL,
    PRIMARY KEY (id)
    )
");


mysqli_query($connect, "CREATE TABLE IF NOT EXISTS send (
  send_id INT(11) NOT NULL AUTO_INCREMENT,
  time1 TEXT NOT NULL,
  time2 TEXT NOT NULL,
  start_id TEXT NOT NULL,
  stop_id TEXT NOT NULL,
  admin_id TEXT NOT NULL,
  message_id TEXT NOT NULL,
  reply_markup TEXT NOT NULL,
  edit_mess_id TEXT DEFAULT NULL,
  sends_count VARCHAR(255) DEFAULT '0',
  receive_count VARCHAR(255) DEFAULT '0',
  statistics TEXT NOT NULL,
  status TEXT DEFAULT NULL,
  step TEXT NOT NULL,
  PRIMARY KEY (send_id)
);");



mysqli_query($connect, "CREATE TABLE IF NOT EXISTS channels (
    id INT(20) NOT NULL AUTO_INCREMENT,
    channel_id VARCHAR(255) NOT NULL,
    title TEXT DEFAULT NULL,
    link VARCHAR(255) NOT NULL,
    type VARCHAR(255) NOT NULL,
    PRIMARY KEY (id)
)");





mysqli_query($connect, "CREATE TABLE IF NOT EXISTS requests (
    id INT(20) NOT NULL AUTO_INCREMENT,
    user_id VARCHAR(255) NOT NULL,
    chat_id VARCHAR(255) NOT NULL,
    PRIMARY KEY (id)
)");


mysqli_query($connect, "CREATE TABLE IF NOT EXISTS settings (
    id INT(11) NOT NULL,
    bot_status TEXT NOT NULL,
    movieChannel TEXT NOT NULL
)");

$setSettings = mysqli_query($connect, "SELECT * FROM settings WHERE id = '1'");
if (mysqli_num_rows($setSettings) == 0) { 
    $sql = "INSERT INTO settings (id, bot_status, movieChannel) VALUES ('1', 'on', '@abduvositin')";
    if ($connect->query($sql)) {
        echo "Muvoffaqiyatli<br>";
    } else {
        echo "Allaqachon mavjud 'settings': " . mysqli_error($connect);
    }
} else {
    echo "Ma'lumot allaqachon kiritilgan<br>";
}

?>
