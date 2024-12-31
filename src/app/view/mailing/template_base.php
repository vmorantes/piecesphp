<?php
$langGroup = isset($langGroup) && is_string($langGroup) ? $langGroup : LANG_GROUP;
$header_image = isset($header_image) && is_string($header_image) ? $header_image : (
    is_local() ?
    "https://via.placeholder.com/500x172.png?text=" . urlencode(get_config('title_app')) :
    base_url(get_config('mailing_logo'))
);
$text = isset($text) && is_string($text) ? $text : '';
$hasCode = isset($code) && is_string($code);
$note = isset($note) && is_string($note) ? $note : '';
$hasURL = isset($url) && is_string($url);
$text_button = isset($text_button) && is_string($text_button) ? $text_button : __($langGroup, 'Clic aquí');
$text_footer = isset($text_footer) && is_string($text_footer) ? $text_footer : get_config('owner');
?>
<!DOCTYPE html>
<html>

<head>
    <style>
    body {
        font-family: Arial, sans-serif;
        margin: 0;
        padding: 0;
        background-color: #f5f5f5;
    }

    .container {
        max-width: 600px;
        margin: 20px auto;
        background-color: #ffffff;
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
    }

    .header {
        text-align: center;
        padding: 20px 0;
    }

    .header img {
        width: 100%;
        max-width: 500px;
    }

    .content {
        text-align: center;
        padding: 20px;
    }

    .content p {
        font-size: 14px;
        color: #777;
        margin: 10px 0 20px;
    }

    .content .code {
        font-size: 30px;
        color: black;
        padding: 0px;
        margin: 10px auto;
        text-align: center;
    }

    .button,
    a.button {
        display: inline-block;
        background-color: #28a745;
        color: white;
        padding: 12px 20px;
        text-decoration: none;
        border-radius: 5px;
        font-size: 16px;
    }

    a.button:visited {
        color: white;
    }

    .footer {
        background-color: #444;
        color: white;
        text-align: center;
        padding: 15px 0;
        font-size: 12px;
    }
    </style>
</head>

<body>
    <div class="container">
        <!-- Header Section -->
        <div class="header">
            <img src="<?=$header_image;?>" alt="Logo">
            <p>
                <?=$text;?>
            </p>
        </div>

        <!-- Content Section -->
        <div class="content">
            <?php if ($hasCode): ?>
            <p class="code">
                <?=$code;?>
            </p>
            <?php endif;?>
            <p>
                <?=$note;?>
            </p>
            <?php if ($hasURL): ?>
            <a href="<?=$url;?>" target="_blank" class="button">
                <?=$text_button;?>
            </a>
            <?php endif;?>
        </div>

        <!-- Footer Section -->
        <div class="footer">
            <p><?=$text_footer;?></p>
        </div>
    </div>
</body>

</html>
