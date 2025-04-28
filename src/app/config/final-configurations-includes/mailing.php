<?php

//Crear logo para mailing
try {
    $mailingLogoRelativePath = "statics/images/mailing-logo.png";
    $mailingLogoPath = basepath($mailingLogoRelativePath);
    if (!file_exists($mailingLogoPath)) {
        resizeAndCenterImage(basepath(get_config('logo')), $mailingLogoPath, 500, 172);
    }
    set_config('mailing_logo', $mailingLogoRelativePath);
} catch (\Exception $e) {
    set_config('mailing_logo', get_config('logo'));
}
