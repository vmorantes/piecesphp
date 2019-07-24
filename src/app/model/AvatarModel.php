<?php

/**
 * AvatarModel.php
 */
namespace App\Model;

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
     * getAvatar
     *
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
     * save
     *
     * @param int $id
     * @param UploadedFile $file
     * @return bool
     */
    public static function save(int $id, UploadedFile $file)
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
     * getFolderUser
     *
     * @param int $id
     * @return string
     */
    public static function getFolderUser(int $id)
    {
        return get_config('upload_dir') . '/' . self::AVATAR_DIR . '/' . $id;
    }

    /**
     * getFolderUser
     *
     * @param int $id
     * @return string
     */
    public static function getFolderUserURL(int $id)
    {
        return get_config('upload_dir_url') . '/' . self::AVATAR_DIR . '/' . $id;
    }

    /**
     * getUserAvatarName
     *
     * @param int $id
     * @return string
     */
    public static function getUserAvatarName(int $id)
    {
        $filename = self::getFolderUser($id) . '/' . self::NAME_AVATAR . '.' . self::EXTENSION_AVATAR;
		return $filename;
    }

    /**
     * getUserAvatarNameURL
     *
     * @param int $id
     * @return string
     */
    public static function getUserAvatarNameURL(int $id)
    {
		$route = self::getFolderUserURL($id) . '/' . self::NAME_AVATAR . '.' . self::EXTENSION_AVATAR;
		return $route;
    }

    /**
     * hasUserAvatar
     *
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
