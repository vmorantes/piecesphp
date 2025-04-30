<?php
    use App\Controller\PublicAreaController;
    $langGroup = isset($langGroup) && is_string($langGroup) ? $langGroup : MAILING_GENERAL_LANG_GROUP;
    $header_image = isset($header_image) && is_string($header_image) ? $header_image : (
        is_local() ?
        "https://placehold.co/500x172.png?text=" . urlencode(get_config('title_app')) :
        base_url(get_config('mailing_logo'))
    );
    $text = isset($text) && is_string($text) ? $text : '';
    $hasCode = isset($code) && is_string($code);
    $note = isset($note) && is_string($note) ? $note : '';
    $hasURL = isset($url) && is_string($url);
    $text_button = isset($text_button) && is_string($text_button) ? $text_button : __(MAILING_GENERAL_LANG_GROUP, 'Clic aquí');
    $text_footer = isset($text_footer) && is_string($text_footer) ? $text_footer : "<p><span class='owner'>" . get_config('owner') . "</span></p>";
    $unsuscriptionURL = isset($unsuscriptionURL) && is_string($unsuscriptionURL) ? $unsuscriptionURL : PublicAreaController::routeName('unsubscribe', ['identifier' => \PiecesPHP\Core\StringManipulate::urlSafeB64Encode(uniqid())]);
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
        padding: 20px 0px 0px 0px;
        border-top: <?=get_config('main_brand_color')?> 10px solid;
    }

    .header img {
        width: 100%;
        max-width: 500px;
    }

    .content {
        text-align: center;
        padding: 20px 30px;
    }

    .content .note {
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

    a {
        display: inline-block;
        color: <?=get_config('main_brand_color')?>;
        text-decoration: underline dotted <?=get_config('main_brand_color')?>;
    }

    .button,
    a.button {
        display: inline-block;
        background-color: <?=get_config('main_brand_color')?>;
        color: white;
        padding: 12px 20px;
        text-decoration: none;
        border-radius: 5px;
        font-size: 16px;
        margin-bottom: 64px;
    }

    a.button:visited {
        color: white;
    }

    .footer {
        background-color: <?=get_config('main_brand_color')?>;
        color: white;
        text-align: center;
        padding: 15px 0;
        font-size: 12px;
    }

    .footer .owner {
        display: inline-block;
        padding: 0px 5px;
        font-size: 160%;
        font-weight: bold;
    }

    .footer .unsuscribe a {
        color: white !important;
        text-decoration: underline;
    }
    </style>
</head>

<body>
    <div class="container">

        <!-- Header Section -->
        <div class="header">
            <img src="<?=$header_image;?>" alt="Logo">
        </div>

        <!-- Content Section -->
        <div class="content">
            <div class="message">
                <?=$text;?>
            </div>
            <?php if ($hasCode): ?>
            <p class="code">
                <?=$code;?>
            </p>
            <?php endif; ?>
            <p class="note">
                <?=$note;?>
            </p>
            <?php if ($hasURL): ?>
            <a href="<?=$url;?>" target="_blank" class="button">
                <?=$text_button;?>
            </a>
            <?php endif; ?>
        </div>

        <!-- Footer Section -->
        <div class="footer">
            <?=$text_footer;?>
            <p class="unsuscribe">
                <?=strReplaceTemplate(__(MAILING_GENERAL_LANG_GROUP, 'UNSUSCRIBE_TEXT'), ['{{url}}' => $unsuscriptionURL]);?>
            </p>
        </div>
    </div>
</body>

</html>
