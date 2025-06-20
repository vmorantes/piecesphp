<?php

/**
 * AvatarModel.php
 */
namespace App\Model;

use Psr\Http\Message\UploadedFileInterface;
use Slim\Http\UploadedFile;

/**
 * AvatarModel.
 *
 * Modelo de avatars.
 *
 * @package     PiecesPHP\Core
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2018
 */
class AvatarModel
{
    /**
     * @var string
     */
    const AVATAR_DIR = 'avatars';
    /**
     * @var string
     */
    const NAME_AVATAR = 'avatar';
    /**
     * @var string
     */
    const EXTENSION_AVATAR = 'jpg';

    /**
     * @param int $id
     * @return string|null
     */
    public static function getAvatar(int $id)
    {
        $avatar = null;

        if (self::hasUserAvatar($id)) {
            $avatar = self::getUserAvatarNameURL($id);
        }

        return $avatar;
    }

    /**
     * @param int $id
     * @param UploadedFileInterface  $file
     * @return bool
     */
    public static function save(int $id, UploadedFileInterface $file)
    {
        $upload_dir = self::getFolderUser($id);

        if ($file->getError() === UPLOAD_ERR_OK) {
            $filename = move_uploaded_file_to($upload_dir, $file, self::NAME_AVATAR, self::EXTENSION_AVATAR);
            return is_string($filename);
        } else {
            return false;
        }
    }

    /**
     * @param int $id
     * @return string
     */
    public static function getFolderUser(int $id)
    {
        return append_to_path_system(get_config('upload_dir'), self::AVATAR_DIR . '/' . $id);
    }

    /**
     * @param int $id
     * @return string
     */
    public static function getFolderUserURL(int $id)
    {
        return append_to_url(get_config('upload_dir_url'), self::AVATAR_DIR . '/' . $id);
    }

    /**
     * @param int $id
     * @return string
     */
    public static function getUserAvatarName(int $id)
    {
        $filename = append_to_path_system(self::getFolderUser($id), self::NAME_AVATAR . '.' . self::EXTENSION_AVATAR);
        return $filename;
    }

    /**
     * @param int $id
     * @return string
     */
    public static function getUserAvatarNameURL(int $id)
    {
        $route = append_to_url(self::getFolderUserURL($id), self::NAME_AVATAR . '.' . self::EXTENSION_AVATAR);
        return $route;
    }

    /**
     * @param int $id
     * @param string|null $onDefault
     * @return string
     */
    public static function getUserAvatarNameURLOrDefault(int $id, string | null $onDefault = 'statics/images/default-avatar.png')
    {
        if (self::hasUserAvatar($id)) {
            $route = append_to_url(self::getFolderUserURL($id), self::NAME_AVATAR . '.' . self::EXTENSION_AVATAR);
        } else {
            $route = $onDefault;
        }
        return $route;
    }

    /**
     * @param int $id
     * @return bool
     */
    public static function hasUserAvatar(int $id)
    {
        $filename = self::getUserAvatarName($id);
        $exists = file_exists($filename);
        return $exists;
    }
}
