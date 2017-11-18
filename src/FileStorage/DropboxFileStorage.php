<?php

declare(strict_types=1);

namespace phpClub\FileStorage;

use Spatie\Dropbox\Client;
use Spatie\Dropbox\Exceptions\BadRequest;
use function GuzzleHttp\Psr7\try_fopen;

class DropboxFileStorage implements FileStorageInterface
{
    const DIRECT_DOWNLOAD_PARAM = '?dl=1';
    const PREVIEW_PARAM = '?dl=0';

    /**
     * @var Client
     */
    private $dropboxClient;

    /**
     * @param Client $dropboxClient
     */
    public function __construct(Client $dropboxClient)
    {
        $this->dropboxClient = $dropboxClient;
    }

    /**
     * @param string $path
     * @param string $directory
     * @return string
     * @throws \Exception
     */
    public function put(string $path, string $directory): string
    {
        $saveAs = '/' . $directory . '/' . basename($path);
        
        // When fopen fails, PHP normally raises a warning. Function try_fopen throws an exception instead
        $this->dropboxClient->upload($saveAs, try_fopen($path, 'r'));

        return $this->createSharedLink($saveAs);
    }

    /**
     * @see https://stackoverflow.com/questions/31292106/how-to-check-whether-a-file-already-exists-in-dropbox
     * @param string $path
     * @param string $directory
     * @return bool
     */
    public function isFileExist(string $path, string $directory): bool
    {
        try {
            return !! $this->dropboxClient->getMetadata('/' . $directory . '/'. basename($path));
        } catch (BadRequest $e) {
            return false;
        }
    }

    /**
     * Creates a publicly accessible link
     *
     * @param string $savedAs
     * @return string
     * @throws \Exception
     */
    private function createSharedLink(string $savedAs): string
    {
        $responseArray = $this->dropboxClient->createSharedLinkWithSettings($savedAs, [
            'requested_visibility' => 'public',
        ]);

        if (!isset($responseArray['url'])) {
            throw new \Exception('Key url must be presented in the response');
        }

        return str_replace(self::PREVIEW_PARAM, self::DIRECT_DOWNLOAD_PARAM, $responseArray['url']);
    }
}