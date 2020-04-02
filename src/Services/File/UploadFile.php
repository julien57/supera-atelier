<?php

namespace App\Services\File;

use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class UploadFile
{
    const IMAGE_UPLOAD_DIR = 'img/upload/event/';
    const ICON_UPLOAD_DIR = 'img/upload/icons/';

    /**
     * @param UploadedFile $file
     * @return string
     * @throws \Exception
     */
    public static function uploadImageEvent(UploadedFile $file): string
    {
        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        // this is needed to safely include the file name as part of the URL
        $safeFilename = transliterator_transliterate('Any-Latin; Latin-ASCII; [^A-Za-z0-9_] remove; Lower()', $originalFilename);
        $newFilename = $safeFilename.'-'.uniqid().'.'.$file->guessExtension();

        try {
            $file->move(
                self::IMAGE_UPLOAD_DIR,
                $newFilename
            );
        } catch (FileException $e) {
            throw new \Exception('Error upload file');
        }

        return $newFilename;
    }

    public static function uploadIconCategory(UploadedFile $file): string
    {
        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        // this is needed to safely include the file name as part of the URL
        $safeFilename = transliterator_transliterate('Any-Latin; Latin-ASCII; [^A-Za-z0-9_] remove; Lower()', $originalFilename);
        $newFilename = $safeFilename.'-'.uniqid().'.'.$file->guessExtension();

        try {
            $file->move(
                self::ICON_UPLOAD_DIR,
                $newFilename
            );
        } catch (FileException $e) {
            throw new \Exception('Error upload file');
        }

        return $newFilename;
    }
}