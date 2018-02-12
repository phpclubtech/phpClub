<?php

namespace phpClub\Entity;

/**
 * @Entity(repositoryClass="phpClub\Repository\UserRepository")
 **/
class User
{
    /** @Id @Column(type="integer") @GeneratedValue **/
    protected $id;

    /** @Column(type="string") **/
    protected $name;

    /** @Column(type="string") **/
    protected $email;

    /** @Column(type="string") **/
    protected $hash;

    /** @Column(type="string") **/
    protected $salt;

    public function getId()
    {
        return $this->id;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    public function getEmail()
    {
        return $this->email;
    }

    public function setEmail($email)
    {
        $this->email = $email;

        return $this;
    }

    public function getHash()
    {
        return $this->hash;
    }

    public function setHash($hash)
    {
        $this->hash = $hash;

        return $this;
    }

    public function getSalt()
    {
        return $this->salt;
    }

    public function setSalt($salt)
    {
        $this->salt = $salt;

        return $this;
    }
}
