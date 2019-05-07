<?php

namespace phpClub\Controller;

use phpClub\Repository\PostRepository;
use Psr\Http\Message\ResponseInterface;
use Slim\Http\Request;
use Slim\Http\Response;

class ApiController
{
    private $postRepository;

    public function __construct(PostRepository $postRepository)
    {
        $this->postRepository = $postRepository;
    }

    public function getPost(Request $request, Response $response, array $args): ResponseInterface
    {
        $id = (int) $args['id'];

        if (!$id) {
            return $response->withJson(['status' => 'No id value'], 400);
        }

        $post = $this->postRepository->find($id);

        if (!$post) {
            return $response->withJson(['status' => 'The Post with that id not found'], 404);
        }

        $json = [
            'data' => [
                'id'          => $post->getId(),
                'thread'      => $post->getThread()->getId(),
                'title'       => $post->getTitle(),
                'author'      => $post->getAuthor(),
                'email'       => $post->getEmail(),
                'date'        => $post->getDate(),
                'text'        => $post->getText(),
                'files'       => [],
                'replies'     => [],
                'isOpPost'    => $post->isOpPost(),
                'isFirstPost' => $post->isFirstPost(),
                'isOld'       => $post->isOld(),
            ],

            'status' => 'OK',
        ];

        foreach ($post->getFiles() as $file) {
            $json['data']['files'][] = [
                'id'        => $file->getId(),
                'name'      => $file->getName(),
                'size'      => $file->getSize(),
                'width'     => $file->getWidth(),
                'height'    => $file->getHeight(),
                'path'      => $file->getPath(),
                'thumbPath' => $file->getThumbPath(),
            ];
        }

        foreach ($post->getReplies() as $reply) {
            $json['data']['replies'][] = [
                'id'     => $reply->getReference()->getId(),
                'thread' => $reply->getReference()->getThread()->getId(),
            ];
        }

        return $response->withJson($json, 200, JSON_PRETTY_PRINT);
    }
}
