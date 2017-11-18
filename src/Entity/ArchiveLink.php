<?php
namespace phpClub\Entity;

/**
 * @Entity(repositoryClass="phpClub\Repository\ArchiveLinkRepository") 
 */
class ArchiveLink
{
    /** @Id @Column(type="integer") @GeneratedValue **/
    protected $id;

    /** @ManyToOne(targetEntity="Thread", inversedBy="archiveLinks") **/
    protected $thread;

    /** @Column(type="string") **/
    protected $link;

    public function __construct(Thread $thread, string $link)
    {
        $this->thread = $thread;
        $this->link = $link;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getThread(): Thread
    {
        return $this->thread;
    }

    public function getLink(): string 
    {
        return $this->link;
    }
}
