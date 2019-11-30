<?php

declare(strict_types=1);

namespace phpClub\Repository;

use Doctrine\ORM\EntityRepository;
use phpClub\Entity\Post;

/**
 * @method Post|null find($id, $lockMode = null, $lockVersion = null)
 */
class PostRepository extends EntityRepository
{
}
