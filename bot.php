<?php
ob_start();
date_Default_timezone_set("Asia/Tashkent");

require_once "sql.php";

const API_KEY = "7939778114:AAHwxKR-lbuu-IPztG-fbrQ25Au4vwuCSow";
$abduvositin = 6857787949;
$owners = [$abduvositin];

$bot = bot("getme")->result->username;
$bot_id = bot("getMe")->result->id;


function bot($method, $datas = []){
    $url = "https://api.telegram.org/bot" . API_KEY . "/" . $method;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $datas);
    $res = curl_exec($ch);
    if (curl_error($ch)) {
        var_dump(curl_error($ch));
    } else {
        return json_decode($res);
    }
}

function editMessage($id, $mid, $txt, $rm = null){
    return bot("editMessageText", [
        "chat_id" => $id,
        "message_id" => $mid,
        "text" => $txt,
        "parse_mode" => "html",
        "reply_markup" => $rm,
    ]);
}

function sendMessage($id, $txt, $rm=null){
    return bot("sendMessage", [
        "chat_id" => $id,
        "text" => $txt,
        "parse_mode" => "html",
        "disable_web_page_preview" => true,
        "reply_markup" => $rm,
    ]);
}

function replyMessage($id,$mid,$txt, $rm=null){
    return bot("sendMessage", [
        "chat_id" => $id,
        "text" => $txt,
        "parse_mode" => "html",
        "disable_web_page_preview" => true,
        "reply_to_message_id"=>$mid,
        "reply_markup" => $rm,
    ]);
}

function deleteMessage(){
    global $cid, $mid, $mid2;
    return bot("deleteMessage", [
        "chat_id" => "$cid",
        "message_id" => "$mid2$mid",
    ]);
}

function answerCallback($qid, $text, $show = false){
    return bot("answerCallbackQuery", [
        "callback_query_id" => $qid,
        "text" => $text,
        "show_alert" => $show,
    ]);
}

function step($id, $value){
    global $connect;
    mysqli_query($connect, "UPDATE users SET step = '$value' WHERE user_id=$id");
}

function admin($id){
    global $connect, $abduvositin;
    $result = mysqli_query($connect, "SELECT * FROM admins WHERE user_id = '$id'");
    $row = mysqli_fetch_assoc($result);
    if ($row or $id == $abduvositin) {
        return true;
    } else {
        return false;
    }
}

function joinchat($id){
    global $connect;
    $result = $connect->query("SELECT * FROM `channels`");
    if ($result->num_rows > 0 and admin($id) !== true) {
        $no_subs = 0;
        $button = [];
        while ($row = $result->fetch_assoc()) {
            $type = $row["type"];
            $link = $row["link"];
            $channel_id = $row["channel_id"];
            $title = $row["title"];
            $gettitle = bot("getchat", ["chat_id" => $channel_id])->result->title;
            if ($type == "lock" or $type == "request" or $type == "other") {
                if ($type == "request") {
                    $check = $connect->query("SELECT * FROM `requests` WHERE user_id = '$id' AND chat_id = '$channel_id'");
                    if ($check->num_rows > 0) {
                        $button[] = ["text" => "✅ $gettitle", "url" => $link];
                    } else {
                        $button[] = ["text" => "❌ $gettitle", "url" => $link];
                        $no_subs++;
                    }
                } elseif ($type == "lock") {
                    $check = bot("getChatMember", [
                        "chat_id" => $channel_id,
                        "user_id" => $id,
                    ])->result->status;
                    if ($check == "left") {
                        $button[] = ["text" => "❌ $gettitle", "url" => $link];
                        $no_subs++;
                    } else {
                        $button[] = ["text" => "✅ $gettitle", "url" => $link];
                    }
                } elseif ($type == "other") {
                    $button[] = ["text" => "❌ $title", "url" => $link];
                }
            }
        }
        if ($no_subs > 0) {
            $button[] = [
                "text" => "✅ Tekshirish",
                "callback_data" => "checkSub",
            ];
            $keyboard2 = array_chunk($button, 1);
            $keyboard = json_encode([
                "inline_keyboard" => $keyboard2,
            ]);
            bot("sendMessage", [
                "chat_id" => $id,
                "text" =>"<b>❌ Kechirasiz botimizdan foydalanishdan oldin ushbu kanallarga a'zo bo'lishingiz kerak.</b>",
                "parse_mode" => "html",
                "reply_markup" => $keyboard,
            ]);
            exit();
        } else {
            return true;
        }
    } else {
        return true;
    }
}

$update = json_decode(file_get_contents("php://input"));

$message = $update->message;
$callbackQuery = $update->callback_query;
$inlineQuery = $update->inline_query;
$chatJoinRequest = $update->chat_join_request;

if ($message) {
    $cid = $message->chat->id;
    $name = $message->chat->first_name;
    $type = $message->chat->type;
    $uid = $message->from->id;
    $user_name = $message->from->first_name;
    $last_name = $message->from->last_name;
    $username = $message->from->username;
    $text = $message->text;
    $mid = $message->message_id;
    $reply = $message->reply_to_message->text;
    $nameuz = "<a href='tg://user?id=$uid'>$user_name $last_name</a>";
    $photo = $message->photo;
    
}

if ($callbackQuery) {
    $data = $callbackQuery->data;
    $qid = $callbackQuery->id;
    $cid = $callbackQuery->message->chat->id;
    $mid2 = $callbackQuery->message->message_id;
    $callfrid = $callbackQuery->from->id;
    $callname = $callbackQuery->from->first_name;
    $calluser = $callbackQuery->from->username;
    $about = $callbackQuery->from->about;
    $nameuz = "<a href='tg://user?id=$callfrid'>$callname</a>";
}

    if ($chatJoinRequest) {
        $join_chat_id = $chatJoinRequest->chat->id;
        $join_user_id = $chatJoinRequest->from->id;
        $connect->query("INSERT INTO requests (user_id, chat_id) VALUES ('$join_user_id', '$join_chat_id')");
    }


mkdir("step");

if (file_exists("step/counter.txt") == false) {
    file_put_contents("step/counter.txt", 0);
}



$result = $connect->query("SELECT * FROM settings WHERE id = '1'"); 
if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $movieChannel = $row["movieChannel"];
    $bot_status = $row["bot_status"];
    $share_status = $row["share_status"];
    $bot_type = $row["bot_type"];

}

$userResult = mysqli_query($connect, "SELECT * FROM users WHERE user_id = $cid");
while ($ures = mysqli_fetch_assoc($userResult)) {
    $step = $ures["step"];
}

if (isset($message) && $type=="private") {
    $result = mysqli_query($connect,"SELECT * FROM users WHERE user_id = '$cid'");
    $row = mysqli_fetch_assoc($result);
    if (!$row) {
        $registered_date = date("d.m.Y H:i");
        mysqli_query($connect,"INSERT INTO users(`user_id`,`time`,`step`) VALUES ('$cid','$registered_date','none')");
    }
}



if ($text && $type=="private") {
    if ($bot_status == "off" and !admin($cid) == 1) {
        sendMessage($cid,"⛔️ <b>Bot vaqtinchalik o'chirilgan!</b>

<i>Botda ta'mirlash ishlari olib borilayotgan bo'lishi mumkin!</i>",
            json_encode(["remove_keyboard" => true])
        );
        exit();
    }
}

if ($data) {
    if ($bot_status == "off" and !admin($cid) == 1) {
        answerCallback( $qid,"⛔️ Bot vaqtinchalik o'chirilgan!

Botda ta'mirlash ishlari olib borilayotgan bo'lishi mumkin!", 1);
        exit();
    }
}

$menu = json_encode([
    "inline_keyboard" => [
        [["text" => "🎬 Kino kodlari", "url" => "https://t.me/" . str_ireplace("@", null, $movieChannel),]]
    ],
]);

$orqaga = json_encode([
    "inline_keyboard" => [
   [["text" => "🔙 Orqaga", "callback_data" => "orqaga"]]]
]);

$panel = json_encode([
    "resize_keyboard" => true,
    "keyboard" => [
        [["text" => "🎬 Kino"], ["text" => "📢 Kanallar"]],
        [["text" => "📊 Statistika"], ["text" => "👨🏻‍💻 Adminlar"]],
        [["text" => "✉️ Xabar jo‘natish"], ["text" => "⚙️ Sozlamalar"]],
        [["text" => "🤖 Bot holati"],["text" => "◀️ Chiqish"]],
    ]
]);

$back_panel = json_encode([
    "resize_keyboard" => true,
    "keyboard" => [
        [["text" => "◀️ Orqaga"]],
    ]
]);

$backmovie_panel = json_encode([
    "resize_keyboard" => true,
    "keyboard" => [
        [["text" => "Noma'lum"]],
        [["text" => "◀️ Orqaga"]],
    ]
]);

$panel_movie = json_encode([
    "resize_keyboard" => true,
    "keyboard" => [
        [["text" => "🎬 Kino yuklash"], ["text" => "🎬 Qism yuklash"]],
        [["text" => "✏️ Tahrirlash"], ["text" => "🗑O'chirish"]],
        [["text" => "◀️ Orqaga"]],
    ]
]);

$movie_edit = json_encode([
    "resize_keyboard" => true,
    "keyboard" => [
        [["text" => "📝 Kino nomini o'zgartirish"], ["text" => "🎬 Malumotlarini o'zgaritirish"]],
        [["text" => "🎬 Kinoni o'zgartirish"],["text" => "◀️ Orqaga"]],
    ],
]);

$panel_channel = json_encode([
    "resize_keyboard" => true,
    "keyboard" => [
        [["text" => "➕ Kanal qo'shish"], ["text" => "🗑️ Kanal o'chirish"]],
        [["text" => "◀️ Orqaga"]],
    ],
]);

$panel_admin = json_encode([
    "resize_keyboard" => true,
    "keyboard" => [
        [["text" => "➕ Qo'shish"],["text" => "🗑️ O‘chirish"]],
        [["text" => "📋 Ro'yxat"],["text" => "◀️ Orqaga"]]
    ],
]);

if ($data == "checkSub") {
    deleteMessage();
    if (joinchat($cid) == true) {
    replyMessage($cid,$mid,"<b>👋 Assalomu alaykum, $nameuz!

🆔 Kinoni kod orqali yuklashingiz ham mumkin. Marhamat, kino kodini yuboring:</b>",$menu);
        exit();
    } else {
        exit();
    }
}

if (($text == "/start" && $type == "private") && joinchat($cid) == 1) {
    replyMessage($cid,$mid,"<b>👋 Assalomu alaykum, $nameuz!

🆔 Kinoni kod orqali yuklashingiz ham mumkin. Marhamat, kino kodini yuboring:</b>",$menu);
}

if($data=="orqaga"){
deleteMessage();
sendMessage($cid,"<b>👋 Assalomu aleykum $nameuz !

🆔 Kinoni kod orqali yuklashingiz ham mumkin. Marhamat, kino kodini yuboring:</b>",$menu);
}

if (($text && is_numeric($text) && $step == "none") && joinchat($cid) == 1){
    $result = $connect->query("SELECT * FROM movie_data WHERE movie_id = $text");

    if ($result && $result->num_rows > 0) {
        if ($result->num_rows > 1) {
            $texts = "🔍 Bir nechta natija topildi ({$result->num_rows}):\n\n";
            $counter = 1;
            $buttons = [];

            while ($row = $result->fetch_assoc()) {
                $title = base64_decode($row["movie_name"]);
                $texts .= "<b>$counter.</b> {$title}\n";
                $buttons[] = [
                    "text" => (string)$counter,
                    "callback_data" => "serie_" . $row["id"],
                ];
                $counter++;
            }

            $keyboard = array_chunk($buttons, 5);
            $keyboard[] = [["text" => "🔙 Orqaga", "callback_data" => "orqaga"]];
            
            bot("sendMessage", [
                "chat_id" => $cid,
                "text" => $texts,
                "parse_mode" => "html",
                "reply_markup" => json_encode(["inline_keyboard" => $keyboard]),
            ]);
        } else {
            $row = $result->fetch_assoc(); 
            $title = base64_decode($row["movie_name"]);
            $about = base64_decode($row["about"]);
            $file_id = $row["file_id"];

            bot("sendVideo", [
                "chat_id" => $cid,
                "video" => $file_id,
                "caption" => "<b>🎬 Nomi:</b> $title\n\n$about",
                "parse_mode" => "html",
                "protect_content" => $protect_content,
                "reply_to_message_id" => $mid,
                "reply_markup" => json_encode([
                    "inline_keyboard" => [
                        [["text" => "♻️ Do'stlarga ulashish", "url" => "https://t.me/share/url?url=https://t.me/$bot?start=$text"]],
                        [["text" => "❌", "callback_data" => "orqaga"]],
                    ],
                ]),
            ]);
        }
    } else {
        replyMessage($cid, $mid, "<b>❌ Film mavjud emas yoki o'chirib tashlangan!</b>");
    }
}



if (mb_stripos($text, "/start ") !== false and joinchat($cid) == 1) {
    $text = str_replace("/start ", "", $text); 
    $result = $connect->query("SELECT * FROM movie_data WHERE movie_id = $text");

    if ($result && $result->num_rows > 0) {
        if ($result->num_rows > 1) {
            $texts = "🔍 Bir nechta natija topildi ({$result->num_rows}):\n\n";
            $counter = 1;
            $buttons = [];

            while ($row = $result->fetch_assoc()) {
                $title = base64_decode($row["movie_name"]);
                $texts .= "<b>$counter.</b> {$title}\n";
                $buttons[] = [
                    "text" => (string)$counter,
                    "callback_data" => "serie_" . $row["id"],
                ];
                $counter++;
            }

            $keyboard = array_chunk($buttons, 5);
            $keyboard[] = [["text" => "🔙 Orqaga", "callback_data" => "orqaga"]];
            
            bot("sendMessage", [
                "chat_id" => $cid,
                "text" => $texts,
                "parse_mode" => "html",
                "reply_markup" => json_encode(["inline_keyboard" => $keyboard]),
            ]);
        } else {
            $row = $result->fetch_assoc(); 
            $title = base64_decode($row["movie_name"]);
            $about = base64_decode($row["about"]);
            $file_id = $row["file_id"];

            bot("sendVideo", [
                "chat_id" => $cid,
                "video" => $file_id,
                "caption" => "<b>🎬 Nomi:</b> $title\n\n$about",
                "parse_mode" => "html",
                "protect_content" => $protect_content,
                "reply_to_message_id" => $mid,
                "reply_markup" => json_encode([
                    "inline_keyboard" => [
                        [["text" => "♻️ Do'stlarga ulashish", "url" => "https://t.me/share/url?url=https://t.me/$bot?start=$text"]],
                        [["text" => "❌", "callback_data" => "orqaga"]],
                    ],
                ]),
            ]);
        }
    } else {
        replyMessage($cid, $mid, "<b>❌ Film mavjud emas yoki o'chirib tashlangan!</b>");
    }

    
}






if (strpos($data, "serie_") === 0) { 
    $movie_id = str_replace("serie_", "", $data); 
    $result = $connect->query("SELECT * FROM movie_data WHERE id = '$movie_id'");
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $title = base64_decode($row["movie_name"]);
        $about = base64_decode($row["about"]);
        $file_id = $row["file_id"];
        $kino_id = $row["movie_id"];

        bot("sendVideo", [
            "chat_id" => $cid,
            "video" => $file_id,
            "caption" => "<b>🎬 Nomi:</b> $title\n\n$about",
            "parse_mode" => "html",
            "reply_to_message_id" => $mid2,
            "reply_markup" => json_encode([
                "inline_keyboard" => [
                    [["text" => "♻️ Do'stlarga ulashish", "url" => "https://t.me/share/url?url=https://t.me/$bot?start=$kino_id"]],
                    [["text" => "❌", "callback_data" => "orqaga"]],
                ],
         ]),
    
        ]);
    } else {
        bot("sendMessage", [
            "chat_id" => $cid,
            "text" => "❌ Kino topilmadi.\n\n♻️ Qayta urinib ko'ring",
            "reply_to_message_id" => $mid2
        ]);
    }
}



//panel

if (($text == "◀️ Chiqish" && $type == "private" ) and admin($cid) == 1){
    replyMessage($cid, $mid, "<b>❗️ Admin paneldan chiqdingiz /start ni yuboring \n\n🚀 Qaytish uchun /panel ni yuboring</b>",json_encode(["remove_keyboard" => true]));
    step($cid,"none");
}

if (($text == "◀️ Orqaga" && $type == "private") and admin($cid) == 1) {
    replyMessage($cid, $mid, "🛠 Admin paneliga xush kelibsiz!", $panel);

    $files = [
        "step/movie_name$cid.txt",
        "step/movie_about$cid.txt",
        "step/smsChannel$cid.txt",
        "step/series_id$cid.txt",
        "step/series_name$cid.txt",
        "step/series_about$cid.txt",
        "step/edit_movie$cid.txt",
        "step/$cid.type",
        "step/$cid.link",
        "step/sendType.txt"
    ];

    foreach ($files as $file) {
        if (file_exists($file)) {
            unlink($file);
        }
    }

    step($cid, "none");
    return;
}

if($data == "cancelStep") {
    if (file_exists("step/sendType.txt")) {
        unlink("step/sendType.txt");
    }
    step($cid, "none");
    deleteMessage();
    replyMessage($cid, $mid, "🛠 Admin paneliga xush kelibsiz!", $panel);
}

if (($text == "/panel" && $type == "private" ) and admin($cid) == 1){
    replyMessage($cid, $mid, "🛠 Admin paneliga xush kelibsiz!", $panel);
}

if (($text == "🎬 Kino" && $type == "private") and admin($cid) == 1) {
    replyMessage($cid,$mid, "<b>🎬  Kino sozlamalari bo'limi!</b>",$panel_movie);
    step($cid, "none");
}




if (($text == "🎬 Kino yuklash"  && $type == "private") and admin($cid) == 1) {
    sendMessage($cid, "<b>🍿 Kino nomini kiriting:</b>\n\n✏️ Masalan: \n<code>Qasoskorlar</code> yoki <code>Loki 1-qism</code>", $backmovie_panel);
    step($cid, "movie_name");
}

if ($step == "movie_name") {
    sendMessage($cid, "<b>✅ $text qabul qilindi!</b>\n\n<b>🍿 Kino haqida ma’lumotlarni kiriting:</b>\n\nMasalan:\n<code>🌍 Davlati: Davlati
🌐 Tili: O'zbek tilida
🎭 Janr: #Jangari, #Sarguzasht
💿 Sifati: 720p
📆 Yili: 2024</code>", $backmovie_panel);
file_put_contents("step/movie_name$cid.txt", $text);
step($cid, "movie_about");
}

if ($step == "movie_about") {
        sendMessage($cid, "<b>✅ $text qabul qilindi!</b>\n\n<i>Kinoni yuboring</i>",$back_panel);
        file_put_contents("step/movie_about$cid.txt", $text);
        step($cid, "movie");
}

if ($step == "movie") {
    
    if (isset($message->video)) {
        $video = $message->video;
        $file_id = $message->video->file_id;
        $title = base64_encode(file_get_contents("step/movie_name$cid.txt"));
        $about = base64_encode(file_get_contents("step/movie_about$cid.txt"));
    
        $kino_id = file_get_contents("step/counter.txt") + 1;
        file_put_contents("step/counter.txt", $kino_id);

        $sql = "INSERT INTO movie_data (movie_id, movie_name, about, file_id) 
                VALUES ('$kino_id', '$title','$about', '$file_id');";

        if ($connect->query($sql) === true) {
            $s = $kino_id;
            sendMessage($cid, "✅ <b>Kino botga joylandi!</b>\n\n<b>🆔 Film IDsi:</b> <code>$kino_id</code>", $panel_movie);
            sendMessage($cid, "<b>🔗 Ushbu kinoni kanalga tashlamoqchimisiz?</b>\n\n<i>Agar, «✅ Yuborish» tugmasini bossangiz $kanalcha'ga yuboradi.</i>",
                json_encode([
                    "inline_keyboard" => [
                        [["text" => "✅ Yuborish", "callback_data" => "sms_{$s}"]],
                    ],
                ])
            );
        } else {
            sendMessage($cid, "⚠️ <b>Xatolik!</b>\n\n<code>{$connect->error}</code>", $panel_movie);
            $kino_id--; 
            file_put_contents("step/counter.txt", $kino_id);
        }
        step($cid, "none");
        unlink("step/movie_name$cid.txt");
        unlink("step/movie_about$cid.txt");
    } else {
        sendMessage($cid, "⚠️ <b>Video yuborilmadi!</b>\n\n<i>Iltimos, kino videosini yuboring yoki asosiy boshqaruvga qaytish uchun pasdagi tugmani bosing</i>", $back_panel);
    }
}

if (mb_stripos($data, "sms_") !== false) {
    $s = str_ireplace("sms_", "", $data);
    deleteMessage();
    sendMessage($cid,"<b>$movieChannel ga yubormoqchi bo'lgan postingizni yuboring</b>",$back_panel);
    file_put_contents("step/smsChannel$cid.txt", $s);
    step($cid,"sendSmsChannel");
}

if ($step=="sendSmsChannel") {
    $s = file_get_contents("step/smsChannel$cid.txt");
    bot("copyMessage", [
        "chat_id" => $movieChannel,
        "from_chat_id" => $cid,
        "message_id" => $mid,
        "reply_markup" => json_encode([
            "inline_keyboard" => [
                [["text" => "📥 Yuklab olish","url" => "https://t.me/$bot?start=$s"]],
            ],
        ]),
    ]);
    sendMessage($cid, "<b>✅ $movieChannel kanaliga post yuborildi!</b>", $panel_movie);
    unlink("step/smsChannel$cid.txt");
    step($cid,"none");
}

if (($text == "🎬 Qism yuklash" && $type == "private") and admin($cid) == 1) {
    replyMessage($cid,$mid,"<b>🔍 Qism qo‘shish uchun serial ID raqamini kiriting:</b>",$back_panel);
    step($cid, "series_id");
}

if ($step == "series_id" && is_numeric($text)) {
    $checkSql = "SELECT * FROM movie_data WHERE movie_id = '$text'";
    $checkResult = $connect->query($checkSql);

    if ($checkResult && $checkResult->num_rows > 0) {
        file_put_contents("step/series_id$cid.txt", $text);
        sendMessage($cid, "<b>🍿 Serial nomi va qismni kiriting:</b>\n\n✏️ Masalan: \n<code>Flesh 123-qism</code>", $back_panel);
        step($cid, "name_series");
    } else {
        sendMessage($cid, "<b>❌ Bunday serial mavjud emas!</b>\n\nIltimos, mavjud serial ID raqamini kiriting yoki qayta urinib ko'ring.", $panel_movie);
        step($cid, "none");
    }
}

if ($step == "name_series") {
file_put_contents("step/series_name$cid.txt", $text);

sendMessage($cid, "<b>✅ $text qabul qilindi!</b>\n\n<b>🍿 Serial haqida ma’lumotlarni kiriting:</b>\n\nMasalan:\n<code>🌍 Davlati: Davlati
🌐 Tili: O'zbek tilida
🎭 Janr: #Jangari, #Sarguzasht
💿 Sifati: 720p
📆 Yili: 2024</code>", json_encode([
    "resize_keyboard" => true,
    "keyboard" => [
        [["text" => "⬇️ Oldingi qisimdan yuklash"]],
        [["text" => "◀️ Orqaga"]],
    ]
]));
    
step($cid, "about_series");
}


if ($step == "about_series") {
    if ($text == "⬇️ Oldingi qisimdan yuklash") {
        $kino_id = file_get_contents("step/series_id$cid.txt");
        $checkSql = "SELECT * FROM movie_data WHERE movie_id = '$kino_id' ORDER BY id DESC LIMIT 1";
        $checkResult = $connect->query($checkSql);

        if ($checkResult && $checkResult->num_rows > 0) {
            $row = $checkResult->fetch_assoc();
            $lastabout = base64_decode($row['about']); 
            file_put_contents("step/series_about$cid.txt", $lastabout);
            sendMessage($cid, "<i>✅ Serial ma'lumotlari qabul qilindi!</i>\n\n<i>Endi qismni yuboring:</i>",$back_panel);
            step($cid, "add_series");
        } else {
            sendMessage($cid, "<i>❌ Oldingi qism topilmadi!</i>");
        }
    } else {
        file_put_contents("step/series_about$cid.txt", $text);
        sendMessage($cid, "<i>✅ Serial ma'lumotlari qabul qilindi!</i>\n\n<i>Endi qismni yuboring:</i>",$back_panel);
        step($cid, "add_series");
    }
}


if ($step == "add_series") {
    if (isset($message->video)) {
        $video = $message->video;
        $file_id = $message->video->file_id;

        $title = base64_encode(file_get_contents("step/series_name$cid.txt"));
        $about = base64_encode(file_get_contents("step/series_about$cid.txt"));
        $kino_id = file_get_contents("step/series_id$cid.txt");

        if (empty($kino_id)) {
            sendMessage($cid, "⚠️ <b>Serial ID raqami topilmadi!</b>\n\nIltimos, serialni tanlang yoki qayta urinib ko'ring.", $panel_movie);
            step($cid, "none");
            return;
        }

        $sql = "INSERT INTO movie_data (movie_id, movie_name, about, file_id) VALUES ('$kino_id', '$title','$about', '$file_id')";

        if ($connect->query($sql) === true) {
            sendMessage($cid, "✅ <b>Kino botga joylandi!</b>\n\n<b>🆔 Serial IDsi:</b> <code>$kino_id</code>", $panel_movie);
        } else {
            sendMessage($cid, "⚠️ <b>Xatolik!</b>\n\n<code>{$connect->error}</code>", $panel_movie);
        } 

        step($cid, "none");
        unlink("step/series_id$cid.txt");
        unlink("step/series_name$cid.txt");
        unlink("step/series_about$cid.txt");
    } else {
        sendMessage($cid, "⚠️ <b>Video yuborilmadi!</b>\n\n<i>Iltimos, kino videosini yuboring yoki asosiy boshqaruvga qaytish uchun /start ni kiriting.</i>", $back_panel);
    }
}

if ($text == "✏️ Tahrirlash" && $type == "private" && admin($cid) == 1) {
    replyMessage($cid,$mid, "🚀 Marhamat tanlang:", $movie_edit);
}

if ($text == "📝 Kino nomini o'zgartirish" && $type == "private" && admin($cid) == 1) {
    replyMessage($cid, $mid, "Kino yoki serial ID sini kiriting:", $back_panel);
    step($cid, "name_edit");
}

if ($step == "name_edit" && is_numeric($text)) {
    $checkSql = "SELECT * FROM movie_data WHERE movie_id = '$text'";
    $checkResult = $connect->query($checkSql);

    if ($checkResult && $checkResult->num_rows > 0) {
        if ($checkResult->num_rows > 1) {
            $texts = "<b>🔍 Bir nechta kino/seriallar topildi, o'zgartirish uchun birini tanlang:</b>\n\n";
            $buttons = [];
            $counter = 1;

            while ($row = $checkResult->fetch_assoc()) {
                $title = base64_decode($row["movie_name"]);
                $texts .= "<b>$counter.</b> $title\n";
                $buttons[] = [
                    "text" => (string)$counter,
                    "callback_data" => "editserie_" . $row["id"]
                ];
                $counter++;
            }

            $keyboard = array_chunk($buttons, 5);
            $keyboard[] = [["text" => "🔙 Orqaga", "callback_data" => "cancelStep"]];

            replyMessage($cid, $mid, $texts, json_encode([
                "inline_keyboard" => $keyboard
            ]));
        } else {
            $row = $checkResult->fetch_assoc();
            $current_name = base64_decode($row['movie_name']);
            $movie_id = $row['id'];

            replyMessage($cid, $mid, "<b>🔧 Tanlangan kino/serial nomi: $current_name</b>\n\nIltimos, yangi nomni kiriting:", $back_panel);
            file_put_contents("step/edit_movie$cid.txt",$movie_id);
            step($cid, "name_update");
        }
    } else {
        sendMessage($cid, "<b>❌ Ushbu ID bo‘yicha kino yoki serial topilmadi!</b>\n\nIltimos, to‘g‘ri kino yoki serial ID kiriting.", $back_panel);
    }
}

if (mb_stripos($data, "editserie_") !== false) {
    $editId = explode("_", $data)[1];
    
    file_put_contents("step/edit_movie$cid.txt", $editId);
    
    $sql = "SELECT * FROM movie_data WHERE id = '$editId'";
    $result = $connect->query($sql);
    
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $movieName = base64_decode($row["movie_name"]);
        
        replyMessage($cid, $mid, "<b>Nomi:</b> $movieName\n<b>Yangi nomni kiriting:</b>", $back_panel);
        
        step($cid, "name_update");
    } else {
        replyMessage($cid, $mid, "<b>❌ Kino yoki serial topilmadi.</b>", $back_panel);
    }
}

if ($step == "name_update") {
    if (isset($text) && !empty($text)) {
        $movie_id = file_get_contents("step/edit_movie$cid.txt");

        if ($movie_id === false) {
            replyMessage($cid, $mid, "<b>❌ Xatolik yuz berdi! Kino ID topilmadi.</b>", $back_panel);
            step($cid, "none");
            return;
        }
        $textEncode = base64_encode($text);

        $sql = "UPDATE movie_data SET movie_name = '$textEncode' WHERE id = '$movie_id'";

        if ($connect->query($sql) === true) {
            replyMessage($cid, $mid, "<b>✅ Kino yoki serial nomi muvaffaqiyatli yangilandi!</b>", $panel_movie);
        } else {
            replyMessage($cid, $mid, "<b>❌ Kino yoki serial nomini yangilashda xatolik yuz berdi!</b>\n\n<code>{$connect->error}</code>", $back_panel);
        }
        unlink("step/edit_movie$cid.txt");
        step($cid, "none");
    } else {
        replyMessage($cid, $mid, "<b>❌ Iltimos, yangi nom kiriting.</b>", $back_panel);
    }
}

if ($text == "🎬 Malumotlarini o'zgaritirish" && $type == "private" && admin($cid) == 1) {
    replyMessage($cid, $mid, "Kino yoki serial ID sini kiriting:", $back_panel);
    step($cid, "about_edit");
}

if ($step == "about_edit" && is_numeric($text)) {
    $checkSql = "SELECT * FROM movie_data WHERE movie_id = '$text'";
    $checkResult = $connect->query($checkSql);

    if ($checkResult && $checkResult->num_rows > 0) {
        if ($checkResult->num_rows > 1) {
            $texts = "<b>🔍 Bir nechta kino/seriallar topildi, o'zgartirish uchun birini tanlang:</b>\n\n";
            $buttons = [];
            $counter = 1;

            while ($row = $checkResult->fetch_assoc()) {
                $title = base64_decode($row["about"]);
                $texts .= "<b>$counter.</b> $title\n";
                $buttons[] = [
                    "text" => (string)$counter,
                    "callback_data" => "editAbouSerie_" . $row["id"]
                ];
                $counter++;
            }

            $keyboard = array_chunk($buttons, 5);
            $keyboard[] = [["text" => "🔙 Orqaga", "callback_data" => "cancelStep"]];

            replyMessage($cid, $mid, $texts, json_encode([
                "inline_keyboard" => $keyboard
            ]));
        } else {
            $row = $checkResult->fetch_assoc();
            $current_about = base64_decode($row['movie_name']);
            $movie_id = $row['id'];

            replyMessage($cid, $mid, "<b>🔧 Tanlangan kino malumoti: $current_about</b>\n\nIltimos, yangi malumotni kiriting:", $back_panel);
            file_put_contents("step/edit_movie$cid.txt",$movie_id);
            step($cid, "about_update");
        }
    } else {
        sendMessage($cid, "<b>❌ Ushbu ID bo‘yicha kino yoki serial topilmadi!</b>\n\nIltimos, to‘g‘ri kino yoki serial ID kiriting.", $back_panel);
    }
}

if (mb_stripos($data, "editAbouSerie_") !== false) {
    $editId = explode("_", $data)[1];
    
    file_put_contents("step/edit_movie$cid.txt", $editId);
    
    $sql = "SELECT * FROM movie_data WHERE id = '$editId'";
    $result = $connect->query($sql);
    
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $movieName = base64_decode($row["about"]);
        
        replyMessage($cid, $mid, "<b>Malumotlari:</b> $movieName\n<b>Yangi malumotlarni kiriting:</b>", $back_panel);
        
        step($cid, "about_update");
    } else {
        replyMessage($cid, $mid, "<b>❌ Topilmadi.</b>", $back_panel);
    }
}

if ($step == "about_update") {
    if (isset($text) && !empty($text)) {
        $movie_id = file_get_contents("step/edit_movie$cid.txt");

        if ($movie_id === false) {
            replyMessage($cid, $mid, "<b>❌ Xatolik yuz berdi! Kino ID topilmadi.</b>", $back_panel);
            step($cid, "none");
            return;
        }
        $textEncode = base64_encode($text);

        $sql = "UPDATE movie_data SET about = '$textEncode' WHERE id = '$movie_id'";

        if ($connect->query($sql) === true) {
            replyMessage($cid, $mid, "<b>✅ Malumotlari muvaffaqiyatli yangilandi!</b>", $panel_movie);
        } else {
            replyMessage($cid, $mid, "<b>❌ Malumotini yangilashda xatolik yuz berdi!</b>\n\n<code>{$connect->error}</code>", $back_panel);
        }
        unlink("step/edit_movie$cid.txt");
        step($cid, "none");
    } else {
        replyMessage($cid, $mid, "<b>❌ Iltimos, yangi malumotlarni kiriting.</b>", $back_panel);
    }
}

if ($text == "🎬 Kinoni o'zgartirish" && $type == "private" && admin($cid) == 1) {
    replyMessage($cid, $mid, "🎥 Kino yoki serial ID sini kiriting:", $back_panel);
    step($cid, "video_edit");
}

if ($step == "video_edit" && is_numeric($text)) {
    $checkSql = "SELECT * FROM movie_data WHERE movie_id = '$text'";
    $checkResult = $connect->query($checkSql);

    if ($checkResult && $checkResult->num_rows > 0) {
        if ($checkResult->num_rows > 1) {
            $texts = "<b>🔍 Bir nechta kino/seriallar topildi, o'zgartirish uchun birini tanlang:</b>\n\n";
            $buttons = [];
            $counter = 1;

            while ($row = $checkResult->fetch_assoc()) {
                $title = base64_decode($row["about"]);
                $texts .= "<b>$counter.</b> $title\n";
                $buttons[] = [
                    "text" => (string)$counter,
                    "callback_data" => "editVideoSerie_" . $row["id"]
                ];
                $counter++;
            }

            $keyboard = array_chunk($buttons, 5);
            $keyboard[] = [["text" => "🔙 Orqaga", "callback_data" => "cancelStep"]];

            replyMessage($cid, $mid, $texts, json_encode([
                "inline_keyboard" => $keyboard
            ]));
        } else {
            $row = $checkResult->fetch_assoc();
            $file_id = $row['file_id'];
            $movie_id = $row['id'];
            
            bot("sendVideo", [
                "chat_id" => $cid,
                "video" => $file_id,
                "caption" => "<b>🔧 Tanlangan kino : </b>\n\nIltimos, yangi kinoni kiriting:",
                "parse_mode" => "html",
            ]);

            file_put_contents("step/edit_movie$cid.txt",$movie_id);
            step($cid, "video_update");
        }
    } else {
        sendMessage($cid, "<b>❌ Ushbu ID bo‘yicha kino yoki serial topilmadi!</b>\n\nIltimos, to‘g‘ri kino yoki serial ID kiriting.", $back_panel);
    }
}

if (mb_stripos($data, "editVideoSerie_") !== false) {
    $editId = explode("_", $data)[1];
    file_put_contents("step/edit_movie$cid.txt", $editId);
    $sql = "SELECT * FROM movie_data WHERE id = '$editId'";
    $result = $connect->query($sql);
    
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $file_id = $row["file_id"];
        
            bot("sendVideo", [
                "chat_id" => $cid,
                "video" => $file_id,
                "caption" => "<b>🔧 Tanlangan kino : </b>\n\nIltimos, yangi kinoni kiriting:",
                "parse_mode" => "html",
            ]);
        
        step($cid, "video_update");
    } else {
        replyMessage($cid, $mid, "<b>❌ Topilmadi.</b>", $back_panel);
    }
}

if ($step == "video_update") {
    $movie_id = file_get_contents("step/edit_movie$cid.txt");
    if ($movie_id === false) {
        sendMessage($cid,  "<b>❌ Xatolik yuz berdi! Kino ID topilmadi.</b>", $back_panel);
        step($cid, "none");
        return;
    }

    if (isset($message->video)) {
        $video = $message->video;
        $file_id = $video->file_id;  

        $update = $connect->query("UPDATE movie_data SET file_id = '$file_id' WHERE id = '$movie_id'");

        if ($update) {
        sendMessage($cid, "♻️ Kino muvaffaqiyatli yangilandi:\n ", $panel_movie);
    } else {
        sendMessage($cid, "❌ Kino yangilashda xato yuz berdi. Keyinroq qayta urinib ko'ring.", $panel);
    }

        step($cid, "none");
        unlink("step/edit_movie$cid.txt");
    } else {
        replyMessage($cid, $mid, "⚠️ <b>Faqat video yuboring</b>", $back_panel);
    }
}

if (($text == "🗑O'chirish" && $type == "private") and admin($cid) == 1) {
    replyMessage($cid, $mid, "<b>🆔 Iltimos, o‘chirish uchun kino yoki serial IDisini yuboring:</b>", $back_panel);
    step($cid, "deleteMov");
}

if ($step == "deleteMov" && is_numeric($text)) {
    $checkSql = "SELECT * FROM movie_data WHERE movie_id = '$text'";
    $checkResult = $connect->query($checkSql);

    if ($checkResult && $checkResult->num_rows > 0) {
        if ($checkResult->num_rows > 1) {
            $texts = "<b>🔍 Bir nechta kino topildi, o‘chirish uchun birini tanlang:</b>\n\n";
            $buttons = [];
            $counter = 1;

            while ($row = $checkResult->fetch_assoc()) {
                $title = base64_decode($row["movie_name"]);
                $texts .= "<b>$counter.</b> $title\n";
                $buttons[] = [
                    "text" => (string)$counter,
                    "callback_data" => "deleteserie_" . $row["id"]
                ];
                $counter++;
            }
            
            $keyboard = array_chunk($buttons, 5);
            $keyboard[] = [["text" => "🗑 Hammasini o'chirish", "callback_data" => "deleteAll_" . $text]];
            $keyboard[] = [["text" => "🔙 Orqaga", "callback_data" => "cancelStep"]];

            replyMessage($cid, $mid, $texts, json_encode([
                "inline_keyboard" => $keyboard
            ]));
        } else {
            $row = $checkResult->fetch_assoc();
            $sql = "DELETE FROM movie_data WHERE id = '{$row['id']}'";
            if ($connect->query($sql) === true) {
                replyMessage($cid, $mid, "<b>✅ Kino muvaffaqiyatli o‘chirildi!</b>", $panel_movie);
            } else {
                replyMessage($cid, $mid, "<b>❌ Kino o‘chirishda xatolik yuz berdi!</b>\n\n<code>{$connect->error}</code>", $panel_movie);
            }
        }
    } else {
        sendMessage($cid, "<b>❌ Ushbu ID bo‘yicha kino topilmadi!</b>\n\nIltimos, to‘g‘ri kino ID kiriting.", $panel_movie);
    }
    step($cid, "none");
}

if (mb_stripos($data, "deleteserie_") !== false) {
    $deleteId = explode("_", $data)[1];
    deleteMessage(); 

    $sql = "DELETE FROM movie_data WHERE id = '$deleteId'";
    if ($connect->query($sql) === true) {
        sendMessage($cid, "<b>✅ Serial muvaffaqiyatli o‘chirildi!</b>", $panel_movie);
    } else {
        sendMessage($cid, "<b>❌ Kino o‘chirishda xatolik yuz berdi!</b>\n\n<code>{$connect->error}</code>", $panel_movie);
    }
    step($cid, "none");
}

if (mb_stripos($data, "deleteAll_") !== false) {
    $movie_id = explode("_", $data)[1];
    deleteMessage(); 
    $sql = "DELETE FROM movie_data WHERE movie_id = '$movie_id'";
    if ($connect->query($sql) === true) {
        sendMessage($cid, "<b>✅ Barcha kinolar muvaffaqiyatli o‘chirildi!</b>", $panel_movie);
    } else {
        sendMessage($cid, "<b>❌ Barcha kinolarni o‘chirishda xatolik yuz berdi!</b>\n\n<code>{$connect->error}</code>", $back_panel);
    }
    step($cid, "none");
}

if (($text == "📊 Statistika" && $type == "private" ) and admin($cid) == 1){
    
    $today_date = date("d.m.Y");
    $month_date = date("m.Y");

    $statSql = "
    SELECT 
        COUNT(*) AS total_users,
        (SELECT COUNT(*) FROM movie_data) AS movie_count,
        (SELECT COUNT(*) FROM users WHERE time LIKE '%$today_date%') AS joined_today,
        (SELECT COUNT(*) FROM users WHERE time LIKE '%$month_date%') AS joined_this_month
    FROM users
    ";

    $result = $connect->query($statSql);
    
    if ($result && $row = $result->fetch_assoc()) {
        $stat = $row['total_users'];
        $movie_count = $row['movie_count'];
        $joined_today = $row['joined_today'];
        $joinedThisMonth = $row['joined_this_month'];

        $ping = sys_getloadavg();  // Server yuklanishini tekshirish
        $current_time = date("H:i:s");
        $current_date = date("d.m.Y");

        replyMessage($cid, $mid, "💡 <b>O'rtacha yuklanish:</b> <code>$ping[0]</code>

• <b>Barcha odamlar:</b> $stat ta 
• <b>Bugun qo'shilganlar:</b> $joined_today ta
• <b>Shu oy qo'shilganlar:</b> $joinedThisMonth ta

• <b>Yuklangan kinolar:</b> $movie_count ta
<b>⏰ Soat:</b> $current_time | <b>📆 Sana:</b> $current_date", json_encode([
            "inline_keyboard" => [
                [["text" => "🔄 Yangilash", "callback_data" => "upstat"]],
            ],
        ]));
    }
    exit();
}

if ($data == "upstat" and admin($cid) == 1){
    $today_date = date("d.m.Y");
    $month_date = date("m.Y");

    $statSql = "
    SELECT 
        COUNT(*) AS total_users,
        (SELECT COUNT(*) FROM movie_data) AS movie_count,
        (SELECT COUNT(*) FROM users WHERE time LIKE '%$today_date%') AS joined_today,
        (SELECT COUNT(*) FROM users WHERE time LIKE '%$month_date%') AS joined_this_month
    FROM users
    ";

    $result = $connect->query($statSql);

    if ($result && $row = $result->fetch_assoc()) {
        $stat = $row['total_users'];
        $movie_count = $row['movie_count'];
        $joined_today = $row['joined_today'];
        $joinedThisMonth = $row['joined_this_month'];

        $ping = sys_getloadavg();  
        $current_time = date("H:i:s");
        $current_date = date("d.m.Y");

        editMessage($cid, $mid2, "💡 <b>O'rtacha yuklanish:</b> <code>$ping[0]</code>

• <b>Barcha odamlar:</b> $stat ta 
• <b>Bugun qo'shilganlar:</b> $joined_today ta
• <b>Shu oy qo'shilganlar:</b> $joinedThisMonth ta

• <b>Yuklangan kinolar:</b> $movie_count ta
<b>⏰ Soat:</b> $current_time | <b>📆 Sana:</b> $current_date", json_encode([
                "inline_keyboard" => [
                    [["text" => "🔄 Yangilash", "callback_data" => "upstat"]],
                ],
            ])
        );
    }
    exit();
}

if (($text == "🤖 Bot holati" && $type == "private" ) and admin($cid) == 1) {
    $result = $connect->query("SELECT bot_status FROM settings LIMIT 1");
    $row = $result->fetch_assoc();
    $holat = $row["bot_status"];
    if ($holat == "on") {
        $xolat = "❌ O'chirish";
        $holat = "✅ Yoqilgan";
    } elseif ($holat == "off") {
        $xolat = "✅ Yoqish";
        $holat = "❌ O'chiq";
    }
    replyMessage($cid, $mid, "*️⃣ Hozirgi holati: $holat", json_encode([
        "inline_keyboard" => [
            [["text" => $xolat, "callback_data" => "change_status=bot_status"]],
        ],
    ]));
}

if (mb_stripos($data, "change_status=") !== false and in_array($cid, $owners)) {
    $change = explode("=", $data)[1]; 
    $result = $connect->query("SELECT * FROM `settings` WHERE id = 1");

    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $bot_status = $row["bot_status"];

        if($change == "bot_status") {
            if ($bot_status == "on") {
                $xolat = "off";
                $holat = "❌ O'chiq";
            } else if ($bot_status == "off") {
                $xolat = "on";
                $holat = "✅ Yoniq";
            }

            if (mysqli_query($connect, "UPDATE settings SET bot_status = '$xolat' WHERE id=1")) {
                editMessage($cid, $mid2, "<b>Yangi holat o'rnatildi: $holat</b>", json_encode([
                    "inline_keyboard" => [
                        [["text" => $xolat == "on" ? "❌ O'chiq" : "✅ Yoniq", "callback_data" => "change_status=bot_status"]],
                    ],
                ]));
            } else {
                replyMessage($cid, $mid, "<b>❌ Xatolik yuz berdi! Bot holati o'zgartirilmadi.</b>");
            }
        }
    } else {
        replyMessage($cid, $mid, "<b>❌ Bot holatini o'zgartirishda xatolik yuz berdi.</b>");
    }
}

if (($text == "📢 Kanallar" && $type == "private" ) and admin($cid) == 1){
    replyMessage($cid, $mid, "🛠 Kanal sozlamalari!", $panel_channel);
}

if (($text == "➕ Kanal qo'shish" && $type == "private" ) and admin($cid) == 1) {
    sendMessage($cid,"<b>👉 Qo‘shmoqchi bo‘lgan kanal turini tanlang:</b>",json_encode([
            "inline_keyboard" => [
                [["text" => "🌐 Ommaviy", "callback_data" => "request-false"]],
                [["text" => "🔐 So‘rov qabul qiluvchi", "callback_data" => "request-true"]],
                [["text" => "⁉️Boshqa", "callback_data" => "request-other"]],
            ],
        ])
    );
}

if (mb_stripos($data, "request-") !== false) {
    $type = explode("-", $data)[1];
    file_put_contents("step/$cid.type", $type);
    if($type=="false"){
        step($cid,"addChannel");
       sendMessage($cid, "<b>🔗 Iltimos, kanalingizga botni admin qilib qo'ying va kanaldan \"Forward\" xabar yuboring:</b>", $back_panel);
    } else if($type=="true"){
        step($cid,"addChannel");
       sendMessage($cid, "<b>🔗 Iltimos, kanalingizga botni admin qilib qo'ying va kanaldan \"Forward\" xabar yuboring:</b>", $back_panel);
    } elseif($type=="other"){
       sendMessage($cid, "<b>🔗 Iltimos, Telegram botning referal yoki Instagram, TikTok kabi ijtimoiy tarmoqlardan biror havolani kiriting:</b>", $back_panel);
        step($cid,"addOtherChannel");
    }
}

if ($step == "addChannel" && isset($message->forward_origin)) {
    $kanal_id = $message->forward_origin->chat->id;
    $type = file_get_contents("step/$cid.type");

    if ($type == "true") {
        $link = bot("createChatInviteLink", [
            "chat_id" => $kanal_id,
            "creates_join_request" => true,
        ])->result->invite_link;
        $sql = "INSERT INTO `channels` (`channel_id`, `link`, `type`) VALUES ('$kanal_id', '$link', 'request')";
    } else if ($type == "false") {
        $link = "https://t.me/" . $message->forward_origin->chat->username;
        $sql = "INSERT INTO `channels` (`channel_id`, `link`, `type`) VALUES ('$kanal_id', '$link', 'lock')";
    }
    if ($connect->query($sql)) {
        sendMessage($cid, "<b>✅ Kanal muvaffaqiyatli qo‘shildi</b>", $panel);
    } else {
        sendMessage($cid, "<b>⚠️ Kanalni qo‘shishda xatolik yuz berdi!</b>\n\n<code>{$connect->error}</code>",$panel);
    }

    unlink("step/$cid.type");
    step($cid, "none");
}

if ($step == "addOtherChannel") {
    if (strpos($text, "http") !== false) { 
        $type = file_get_contents("step/$cid.type");
        
        if ($type == "other") {
            $check_sql = "SELECT COUNT(*) FROM `channels` WHERE `link` = '$text' AND `type` = 'other'";
            $result = $connect->query($check_sql);
            $row = $result->fetch_row();

            if ($row[0] > 0) {
                sendMessage($cid, "<b>⚠️ Ushbu link allaqachon qo‘shilgan!</b>", $panel);
            } else {
                file_put_contents("step/$cid.link", $text);
                
                sendMessage($cid, "<b>📌 Iltimos, ushbu link uchun nom kiriting:</b>", $back_panel);
                
                step($cid, "addOtherChannelName");
            }
        } else {
            sendMessage($cid, "<b>⚠️ Noto'g'ri holat. Iltimos, boshqa holatni tanlang.</b>", $panel);
        }

        unlink("step/$cid.type");
    } else {
        sendMessage($cid, "<b>⚠️ Noto'g'ri havola\n\n♻️ Qayta urunib ko'ring.</b>", $panel);
        unlink("step/$cid.type");
    }
}

if ($step == "addOtherChannelName") {
    $link = file_get_contents("step/$cid.link"); 
    $randomNumber = rand(1, 99999);
    $sql = "INSERT INTO channels (channel_id, title, link, type) VALUES ('$randomNumber', '$text', '$link', 'other')";
    
    if ($connect->query($sql) === true) {
        sendMessage($cid, "<b>✅ Kanal muvaffaqiyatli qo'shildi!</b>", $panel);
    } else {
        sendMessage($cid, "<b>❌ Xatolik yuz berdi!</b>\n\n<code>{$connect->error}</code>", $panel);
    }

    unlink("step/$cid.link");
    step($cid, "none");
}

if (($text == "🗑️ Kanal o'chirish" && $type == "private" ) and admin($cid) == 1){
    $result = $connect->query("SELECT * FROM `channels`");
    if ($result->num_rows > 0) {
        $button = [];
        while ($row = $result->fetch_assoc()) {
            $type = $row["type"];
            $channel_id = $row["channel_id"];
            if ($type == "lock" or $type == "request") {
                $gettitle = bot("getchat", ["chat_id" => $channel_id])->result->title;
                $button[] = [
                    "text" => "🗑️ " . $gettitle,
                    "callback_data" => "delChan=" . $channel_id,
                ];
            } else {
                $gettitle = $row["title"];
                $button[] = [
                    "text" => "🗑 " . $gettitle,
                    "callback_data" => "delChan=" . $channel_id,
                ];
            }
        }
        $keyboard2 = array_chunk($button, 1);
        $keyboard2[] = [
            ["text" => "◀️ Orqaga", "callback_data" => "cancelStep"],
        ];
        $keyboard = json_encode([
            "inline_keyboard" => $keyboard2,
        ]);
        sendMessage(
            $cid,
            "<b>Kerakli kanalni tanlang va u o‘chiriladi:</b>",
            $keyboard
        );
    } else {
        sendMessage($cid, "<b>Hech qanday kanal ulanmagan!</b>");
    }
}

if (stripos($data, "delChan=") !== false) {
    $chnId = explode("=", $data)[1];
    $result = $connect->query(
        "SELECT * FROM `channels` WHERE channel_id = '$chnId'"
    );
    $row = $result->fetch_assoc();
    if ($row["requestchannel"] == "true") {
        $connect->query("DELETE FROM requests WHERE chat_id = '$chnId'");
    }
    $connect->query("DELETE FROM channels WHERE channel_id = '$chnId'");
    editMessage($cid,$mid2,"<b>✅ Kanal o'chirildi!</b>");
}

if (($text == "👨🏻‍💻 Adminlar" && $type == "private") and admin($cid) == 1 ) {
    if (admin($cid) == 1) {
        replyMessage($cid,$mid,"<b>👨🏻‍💻 Adminlar  bo'limi:</b>",$panel_admin);
    }
}

if (($text == "➕ Qo'shish" && $type == "private" ) and admin($cid) == 1){
    if (in_array($cid, $owners)) {
        replyMessage($cid,$mid, "<b>Kerakli foydalanuvchi ID raqamini yuboring:</b>", $aort);
        step($cid, "add-admin");
    } else {
        replyMessage($cid,$mid,"<b>Ushbu bo'limdan foydalanish siz uchun taqiqlangan!</b>",);
    }
}

if ($step == "add-admin" and in_array($cid, $owners)) {
    if (!is_numeric($text)) { 
        sendMessage($cid, "<b>⚠️ Noto‘g‘ri ID!</b>\n\nIltimos, faqat raqam kiriting.", $back_panel);
        return; 
    }

    $result = mysqli_query($connect, "SELECT * FROM users WHERE user_id = '$text'");
    $row = mysqli_fetch_assoc($result);

    if (!$row) {
        sendMessage($cid, "<b>Ushbu foydalanuvchi botdan foydalanmaydi!</b>\n\nBoshqa ID raqamni kiriting:", $back_panel);
    } elseif (!in_array($text, $owners)) {
        $insert = $connect->query("INSERT INTO admins (user_id) VALUES ('$text')");
        if ($insert) {
            sendMessage($cid, "<code>$text</code> <b>adminlar ro'yxatiga qo'shildi!</b>", $panel_admin);
            
        } else {
            sendMessage($cid, "<b>❌ Xatolik yuz berdi:</b>\n\n<code>{$connect->error}</code>", $panel_admin);
        }
     
    } else {
        sendMessage($cid, "<b>Ushbu foydalanuvchi allaqachon adminlar ro'yxatida mavjud!</b>", $panel_admin);
    }
    step($cid, "none");
}

if ($text == "🗑️ O‘chirish" && $type == "private" ) {
    if (in_array($cid, $owners)) {
        $result = $connect->query("SELECT * FROM admins");
        if ($result->num_rows > 0) {
            $i = 1;
            $response = "";
            while ($row = $result->fetch_assoc()) {
                $get = bot("getchat", ["chat_id" => $row["user_id"]])->result->first_name;
                $response .= "<b>$i.</b> <a href='tg://user?id=" .
                    $row["user_id"] ."'>$get</a>\n";
                $uz[] = [
                    "text" => $i,
                    "callback_data" => "remove-admin=" . $row["user_id"],
                ];
                $i++;
            }
            $keyboard2 = array_chunk($uz, 3);
            $kb = json_encode(["inline_keyboard" => $keyboard2]);
            replyMessage($cid,$mid,"<b>👉 O'chirmoqchi bo'lgan administratorni tanlang:</b>\n\n$response",$kb);
        } else {
            replyMessage($cid,$mid,"<b>Administratorlar mavjud emas</b>");
        }
    } else {
        replyMessage($cid,$mid,"<b>Ushbu bo'limdan foydalanish siz uchun taqiqlangan!</b>",);
    }
}

if (mb_stripos($data, "remove-admin=") !== false and in_array($cid, $owners)) {
    $user_id = explode("=", $data)[1]; 

    if ($user_id <= 0) { 
        answerCallback($qid, "❌ Noto‘g‘ri administrator ID raqami!", true);
        return;
    }

    $result = $connect->query("SELECT * FROM admins WHERE user_id = '$user_id'");

    if ($result && $result->num_rows > 0) {
        $delete = $connect->query("DELETE FROM admins WHERE user_id = '$user_id'");

        if ($delete) {
            deleteMessage(); 
            sendMessage($cid, "<code>$user_id</code> <b>adminlar ro'yxatidan olib tashlandi!</b>", $panel_admin);
        } else {
            sendMessage($cid, "<b>⚠️ Administratorni o‘chirishda xatolik yuz berdi:</b>\n\n<code>{$connect->error}</code>", $panel_admin);
        }
    } else {
        answerCallback($qid, "❌ Ushbu foydalanuvchi administratorlar ro'yxatida mavjud emas!", true);
    }
}

if (($text == "📋 Ro'yxat" && $type == "private" ) and admin($cid) == 1) {
    $res = mysqli_query($connect, "SELECT * FROM admins");
    if ($res->num_rows > 0) {
        while ($a = mysqli_fetch_assoc($res)) {
            $user = $a["user_id"];
            $get = bot("getchat", ["chat_id" => $user])->result->first_name;
            $name = strip_tags($get);
            $key[] = ["text" => "$name", "url" => "tg://user?id=$user"];
        }
        $keyboard2 = array_chunk($key, 1);
        $admins_id = json_encode([
            "inline_keyboard" => $keyboard2,
        ]);
        replyMessage($cid,$mid, "<b>👉 Barcha adminlar ro'yxati:</b>", $admins_id);
    } else {
        replyMessage($cid,$mid, "<b>Administratorlar mavjud emas</b>");
    }
}

if ($_GET["update"] == "send") {
    $rm = json_decode(file_get_contents("step/smskey.json"), true);
    $smsstatus = file_get_contents("step/smskeyst.txt");
    $smsType = file_get_contents("step/sendType.txt");

    $result = mysqli_query($connect, "SELECT * FROM `send`");
    $row = mysqli_fetch_assoc($result);
    $time = date("H:i");

    if ($row["status"] == "resume") {
        $row1 = $row["time1"];
        $row2 = $row["time2"];
        $start_id = $row["start_id"];
        $stop_id = $row["stop_id"];
        $admin_id = $row["admin_id"];
        $mied = $row["message_id"];
        $edit_mess_id = $row["edit_mess_id"];
        $sends_count = $row["sends_count"] ?? 0;
        $receive_count = $row["receive_count"] ?? 0;
        $statistics = $row["statistics"];
        $repl_markup = base64_decode($row["reply_markup"]);
        $time1 = date("H:i", strtotime("+1 minutes"));
        $time2 = date("H:i", strtotime("+2 minutes"));
        $limit = 800;
        
        if ($repl_markup == "null") {
            $repl_markup = json_encode(["inline_keyboard" => [[["text" => $rm["text"], "url" => $rm["url"]]]]]);
        }
        
        if ($time == $row1 or $time == $row2) {
            $sql = "SELECT * FROM `users` LIMIT $start_id, $limit";
            $res = mysqli_query($connect, $sql);
            $not_received_count = 0;

            while ($a = mysqli_fetch_assoc($res)) {
                $id = $a["user_id"];
                
                if ($smsType == "simple") {
                    $params = [
                        "chat_id"       => $id,
                        "from_chat_id"  => $admin_id,
                        "message_id"    => $mied,
                    ];
                    if (trim($smsstatus) == "on") {
                        $params["reply_markup"] = $repl_markup;
                    }
                    $receive_check = bot("CopyMessage", $params);
                }
                else if($smsType=="forward"){
                $receive_check = bot("forwardMessage", [
                        "chat_id" => $id,
                        "from_chat_id" => $admin_id,
                        "message_id" => $mied,
                        "disable_web_page_preview"=>true,
                    ]);
                }
                $sends_count++;
                if ($receive_check->ok) {
                    $receive_count++;
                } 

                if ($id == $stop_id) {
                    bot('deleteMessage', [
                        'chat_id' => $admin_id,
                        'message_id' => $edit_mess_id,
                    ]);

                    bot("sendMessage", [
                        "chat_id" => $admin_id,
                        "text" => "<b>✅ Habar yuborish yakunlandi</b>\n\n<b>✅ Yuborildi:</b> <code>$sends_count/$statistics</code>",
                        "parse_mode" => "html",
                        "reply_markup" => $panel,
                    ]);
                    mysqli_query($connect, "DELETE FROM `send`");
                    unlink("step/sendType.txt");
                    break;
                }
            }

            mysqli_query($connect, "UPDATE `send` SET `time1` = '$time1'");
            mysqli_query($connect, "UPDATE `send` SET `time2` = '$time2'");
            $get_id = $start_id + $limit;
            mysqli_query($connect, "UPDATE `send` SET `start_id` = '$get_id'");
            mysqli_query($connect,"UPDATE `send` SET `sends_count` = '$sends_count'");
            mysqli_query($connect,"UPDATE `send` SET `receive_count` = '$receive_count'");
            $edit = bot("editMessageText", [
                "chat_id" => $admin_id,
                "message_id" => $edit_mess_id,
                "text" => "<b>✅ Yuborildi:</b> <code>$sends_count/$statistics</code>
<b>📥 Qabul qilindi:</b> <code>$receive_count</code>
<b>🔰 Status</b>: <code>resume</code>",
                "parse_mode" => "html",
                "reply_markup" => json_encode([
                    "inline_keyboard" => [
                        [["text" => "To'xtatish ⏸️","callback_data" => "sendstatus=stopped"]],
                        [["text" => "🗑 O'chirish","callback_data" => "bekorqilish_send"]],
                    ],
                ]),
            ]);

            if ($edit->ok) {
                $edit_mess_id = $edit->result->message_id;
                mysqli_query(
                    $connect,
                    "UPDATE `send` SET `edit_mess_id` = '$edit_mess_id'"
                );
            }
        }
        echo json_encode(["status" => true, "cron" => "Sending message"]);
    }
}

if ($text == "✉️ Xabar jo‘natish" and admin($cid) == 1) {
    $result = mysqli_query($connect, "SELECT * FROM `send`");
    $row = mysqli_fetch_assoc($result);
    $status = $row["status"];
    $sends_count = $row["sends_count"];
    $statistics = $row["statistics"];
    $receive_count = $row["receive_count"];
    if (!$row) {
    $testsms=sendMessage($cid,"<b>Tayyor:</b>",json_encode(["remove_keyboard" => true]))->result->message_id;
    bot("deleteMessage", [
        "chat_id" => $cid,
        "message_id" => $testsms,
    ]);
        sendMessage($cid,"<b>📬 Foydalanuvchilarga yuboriladigan turini tanlang:</b>",json_encode([
            "inline_keyboard" => [
                [["text" => "Oddiy", "callback_data" => "setSms-simple"]],
                [["text" => "Forward", "callback_data" => "setSms-forward"]],
                [["text" => "🔙 Orqaga", "callback_data" => "cancelStep"]]
            ]
        ]));
    } else {
        if ($status == "resume") {
            $kb = json_encode([
                "inline_keyboard" => [
                    [["text" => "To'xtatish ⏸","callback_data" => "sendstatus=stopped",]],
                    [["text" => "🗑 O'chirish","callback_data" => "bekorqilish_send", ]],
                ],
            ]);
        } else if ($status == "stopped") {
            $kb = json_encode([
                "inline_keyboard" => [
                    [[ "text" => "Davom ettirish ▶️","callback_data" => "sendstatus=resume"]],
                    [["text" => "🗑 O'chirish","callback_data" => "bekorqilish_send" ]],
                ],
            ]);
        }
        sendMessage($cid,"<b>✅ Yuborildi:</b> <code>$sends_count/$statistics</code>
<b>📥 Qabul qilindi:</b> <code>$receive_count</code>
<b>🔰 Status</b>: <code>$status</code>", $kb);
    }
}

if (mb_stripos($data, "setSms-") !== false and admin($cid) == 1) {
    $smsType = explode("-", $data)[1];
    deleteMessage();
    if($smsType=="simple"){
        sendMessage($cid,"<b>📬 Foydalanuvchilarga yuboriladigan xabarni kiriting:</b>");
        step($cid,"send");
    }
    elseif($smsType=="forward"){
        sendMessage($cid,"<b>📬 Foydalanuvchilarga yuboriladigan xabarni kiriting:</b>");
        step($cid,"send");
    }
    file_put_contents("step/sendType.txt","$smsType");
}

if ($step == "send" and admin($cid) == 1) {
    $res = mysqli_query($connect, "SELECT * FROM `users` ORDER BY `id` DESC LIMIT 1;");
    $row = mysqli_fetch_assoc($res);
    
    if (!$row) {
        sendMessage($cid,"❌ Xato: `users` jadvalidan ma'lumot olinmadi!",$panel);
        step($cid,"none");
        exit();
    }

    $stop_id = $row["user_id"]; 
    $time1 = date("H:i", strtotime("+1 minutes"));
    $time2 = date("H:i", strtotime("+2 minutes"));
    $tugma = json_encode($update->message->reply_markup);
    $reply_markup = base64_encode($tugma);
    $stat = $connect->query("SELECT * FROM users")->num_rows;

    $edit_mess_id = sendMessage(
        $cid,
        "<b>✅ Yuborildi:</b> <code>0/$stat</code>
<b>📥 Qabul qilindi:</b> <code>0</code>
<b>🔰 Status</b>: <code>resume</code>",
        json_encode([
            "inline_keyboard" => [
                [["text" => "To'xtatish ⏸", "callback_data" => "sendstatus=stopped"]],
                [["text" => "🗑 O'chirish", "callback_data" => "bekorqilish_send"]],
            ],
        ])
    )->result->message_id;

    mysqli_query(
        $connect,
        "INSERT INTO `send` (`time1`,`time2`,`start_id`,`stop_id`,`admin_id`,`message_id`,`reply_markup`,`step`,`edit_mess_id`,`status`,`statistics`,`sends_count`,`receive_count`)
        VALUES ('$time1','$time2','0','$stop_id','$cid','$mid','$reply_markup','send','$edit_mess_id','resume','$stat',0,0)"
    );
    sendMessage($cid, "<b>🔄️ Qabul qilindi, bir necha daqiqadan keyin yuborish boshlanadi!</b>", $panel);
    step($cid, "none");
}

if ($data == "bekorqilish_send" and admin($cid) == 1) {
    mysqli_query($connect, "DELETE FROM `send`");
    deleteMessage();
    answerCallback($qid, "Xabar yuborish bekor qilindi!");
    sendMessage($cid, "<b>Admin paneliga xush kelibsiz!</b>", $panel);
    step($cid, "none");
    exit();
}

if (mb_stripos($data, "sendstatus=") !== false and admin($cid) == 1) {
    $up_stat = explode("=", $data)[1];
    $result = mysqli_query($connect, "SELECT * FROM `send`");
    $row = mysqli_fetch_assoc($result);
    if ($row["status"] == $up_stat) {
        answerCallback($qid, "Xabar yuborish xolati $up_stat ga o'zgartirolmaysiz.", 1);
    } else {
        if ($up_stat == "resume") {
            $time1 = date("H:i", time() + 60);
            $time2 = date("H:i", time() + 120);
            mysqli_query(
                $connect,
                "UPDATE `send` SET time1 = '$time1', `time2` = '$time2'"
            );
        }
        if ($up_stat == "resume") {
            $kb = json_encode([
                "inline_keyboard" => [
                    [["text" => "To'xtatish ⏸","callback_data" => "sendstatus=stopped"]],
                    [["text" => "🗑 O'chirish","callback_data" => "bekorqilish_send"]],
                ],
            ]);
        } elseif ($up_stat == "stopped") {
            $kb = json_encode([
                "inline_keyboard" => [
                    [["text" => "Davom ettirish ▶️", "callback_data" => "sendstatus=resume"]],
                    [["text" => "🗑 O'chirish","callback_data" => "bekorqilish_send"]],
                ],
            ]);
        }
        $edit_mess_id = editMessage($cid, $mid2, "<b>✅ Yuborildi:</b> <code>" .
                $row["sends_count"] .
                "/" .
                $row["statistics"] .
                "</code>
<b>📥 Qabul qilindi:</b> <code>" .
                $row["receive_count"] .
                "</code>
<b>🔰 Status</b>: <code>$up_stat</code>",
            $kb
        )->result->message_id;
        mysqli_query($connect, "UPDATE `send` SET edit_mess_id = '$edit_mess_id', `status` = '$up_stat'");
    }
}

if ($text == "⚙️ Sozlamalar" and admin($cid) == 1) { 
    $rmarkupstatus = file_exists("step/smskeyst.txt") ? file_get_contents("step/smskeyst.txt") : "off";
    
    replyMessage($cid, $mid, "⤵️ Kerakli menyuni tanlang:", json_encode([
        "inline_keyboard" => [
            [["text" => "🎬 Kino Kanal", "callback_data" => "changeChannel"]],
            [["text" => "📝 Habarni pasidagi tugma", "callback_data" => "changeKey"]],
            [["text" => "📝 Tugma xolati:\n\n $rmarkupstatus", "callback_data" => "changeStatus"]],
        ],
    ]));
}


if ($data == "changeChannel" and admin($cid) == 1) {
    deleteMessage();
    sendMessage($cid, "⏳ <b>Botni kanalingizga admin qilib kanal usernamesini yuboring\n\nMasalan: @username</b>", $repl_markup);
    step($cid,"new_movie_chan");
}

if ($step == "new_movie_chan") {
    if ($text) {
        if ($text[0] !== '@') {
            sendMessage($cid, "❌ Kino kanal nomi @ bilan boshlanishi kerak!", $panel);
        } else {
            $safe_text = mysqli_real_escape_string($connect, $text);
            
            $channel_id = str_replace("@", "", $safe_text);

            $checkAdmin = bot("getChatMember", [
                "chat_id" => "@$channel_id",
                "user_id" => $bot_id
            ]);

            if (!$checkAdmin || !$checkAdmin->ok || !in_array($checkAdmin->result->status, ['administrator', 'creator'])) {
                sendMessage($cid, "❌ Bot ushbu kanalda admin emas yoki kanalga qo‘shilmagan!", $panel);
            } else {
                $update = $connect->query("UPDATE settings SET movieChannel = '$safe_text' WHERE id = '1'");

                if ($update) {
                    sendMessage($cid, "♻️ Kino kanal muvaffaqiyatli yangilandi:\n$safe_text", $panel);
                } else {
                    sendMessage($cid, "❌ Kino kanal yangilashda xato yuz berdi. Keyinroq qayta urinib ko'ring.", $panel);
                }
            }
        }

        step($cid, "none");
    }
}



if ($data == "changeStatus" and admin($cid) == 1) {
    $filePath = "step/smskeyst.txt";

    $currentStatus = file_exists($filePath) ? trim(file_get_contents($filePath)) : "off";
    $newStatus = ($currentStatus == "on") ? "off" : "on";
    file_put_contents($filePath, $newStatus);

    editmessage($cid, $mid2,"⤵️ Kerakli menyuni tanlang:", json_encode([
        "inline_keyboard" => [
            [["text" => "📝 Habarni pasidagi tugma", "callback_data" => "changeKey"]],
            [["text" => "📝 Tugma xolati:\n\n $newStatus", "callback_data" => "changeStatus"]],
        ],
    ]));
}



if($data=="changeKey" and admin($cid) == 1){
    deleteMessage();
    $rmarkup = json_decode(file_get_contents("step/smskey.json"), true);
    $repl_markup= json_encode(["inline_keyboard" => [[["text" => $rmarkup["text"], "url" =>$rmarkup["url"]]]]]);
    sendMessage($cid, "⏳ <b>Hozirgi holatni ko'rishingiz mumkin 👇:</b>", $repl_markup);
    sendMessage($cid, "✍️ <b>O'zgartirish kiritish uchun quyidagi shaklda ma'lumotlarni yuboring:</b>\n\n🔄 <b>Masalan:</b>\n<code>Yangi matn\nhttps://</code>\n\n<i>‼️ Faqat bitta tugma qosha olasiz</i>", $back_panel);
    step($cid,"new_keysms");
}

if ($step == "new_keysms" and admin($cid) == 1) {
    if (isset($text)) {
        $parts = explode("\n", $text); 
        if (count($parts) == 2) {
            $new_text = trim($parts[0]); 
            $new_url = trim($parts[1]); 
            if (filter_var($new_url, FILTER_VALIDATE_URL) && (strpos($new_url, "https://") === 0 || strpos($new_url, "http://") === 0)) {
                $rmarkup = json_decode(file_get_contents("step/smskey.json"), true); 
                $rmarkup['text'] = $new_text; 
                $rmarkup['url'] = $new_url;
                file_put_contents("step/smskey.json", json_encode($rmarkup, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)); 

                sendMessage($cid, "✅ <b>Matn va URL muvaffaqiyatli yangilandi:</b>\n\n📋 <b>Yangi matn:</b> <code>$new_text</code>\n🔗 <b>Yangi URL:</b> $new_url",$panel);
                step($cid,"none");
            } else {
                sendMessage($cid, "❌ <b>Xato URL:</b> URL <code>https://</code> yoki <code>http://</code> bilan boshlanishi kerak!");
            }
        }
        else {
            sendMessage($cid, "⚠️ <b>Xato format:</b> Iltimos, matnni quyidagi shaklda yuboring:\n\n<code>Matn\nhttps://</code>");
        }
    
    }

}


    




