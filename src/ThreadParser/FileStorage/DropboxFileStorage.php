<?php

declare(strict_types=1);

namespace phpClub\ThreadParser\FileStorage;

use phpClub\Entity\File;
use phpClub\ThreadParser\Helper\{LocalFileFinder, UploadPathHelper};
use Spatie\Dropbox\Client as DropboxClient;
use Spatie\Dropbox\Exceptions\BadRequest;

class DropboxFileStorage implements FileStorageInterface
{
    const DIRECT_DOWNLOAD_PARAM = '?dl=1';
    const PREVIEW_PARAM = '?dl=0';

    /**
     * @var DropboxClient
     */
    private $dropboxClient;

    /**
     * @var LocalFileFinder
     */
    private $fileFinder;
    
    /**
     * @var UploadPathHelper
     */
    private $uploadPathHelper;

    /**
     * @param DropboxClient $dropboxClient
     * @param LocalFileFinder $fileFinder
     * @param UploadPathHelper $uploadPathHelper
     */
    public function __construct(
        DropboxClient $dropboxClient,
        LocalFileFinder $fileFinder,
        UploadPathHelper $uploadPathHelper
    ) {
        $this->dropboxClient = $dropboxClient;
        $this->fileFinder = $fileFinder;
        $this->uploadPathHelper = $uploadPathHelper;
    }

    /**
     * @param File $file
     * @throws \Exception
     */
    public function put(File $file)
    {
        if ($file->isRemote()) {
            if ($this->alreadyUploaded($file)) {
                return;
            }

            $resourceName = $file->getRemoteUrl();
            $thumbResourceName = $file->getThumbnailRemoteUrl();
        } else {
            $resourceName = $this->fileFinder->findAbsolutePathForFile($file);
            $thumbResourceName = $this->fileFinder->findThumbAbsolutePathForFile($file);
        }
        
        $uploadAs = $this->uploadPathHelper->generateUploadPath($file);
        $thumbUploadAs = $this->uploadPathHelper->generateThumbUploadPath($file);

        // When fopen fails, PHP normally raises a warning. Function try_fopen throws an exception instead
        $this->dropboxClient->upload($uploadAs, \GuzzleHttp\Psr7\try_fopen($resourceName, 'r'));
        $this->dropboxClient->upload($thumbUploadAs, \GuzzleHttp\Psr7\try_fopen($thumbResourceName, 'r'));

        $newRemoteUrl = $this->getSharedLink($uploadAs);
        $newThumbRemoteUrl = $this->getSharedLink($thumbUploadAs);

        $file->changeRemoteUrl($newRemoteUrl, $newThumbRemoteUrl);
    }

    /**
     * @see https://stackoverflow.com/questions/31292106/how-to-check-whether-a-file-already-exists-in-dropbox
     * @param File $file
     * @return bool
     */
    private function alreadyUploaded(File $file): bool
    {
        try {
            $uploadPath = $this->uploadPathHelper->generateUploadPath($file);
            return !! $this->dropboxClient->getMetadata($uploadPath);
        } catch (BadRequest $e) {
            return false;
        }
    }

    /**
     * @param string $saveAs
     * @return string
     * @throws \Exception
     */
    private function getSharedLink(string $saveAs): string
    {
        $responseArray = $this->dropboxClient->createSharedLinkWithSettings($saveAs, [
            'requested_visibility' => 'public',
        ]);

        if (!isset($responseArray['url'])) {
            throw new \Exception('Key url must be presented in the response');
        }

        return str_replace(self::PREVIEW_PARAM, self::DIRECT_DOWNLOAD_PARAM, $responseArray['url']);
    }
}