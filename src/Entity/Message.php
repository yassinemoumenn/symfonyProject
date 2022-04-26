<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use App\Repository\MessageRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ApiResource()
 * @ORM\Entity(repositoryClass=MessageRepository::class)
 */
class Message
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $idcompagne;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $typemessage;

    /**
     * @ORM\Column(type="string", length=10000)
     */
    private $content;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $langue;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $iduser;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $sendermessage;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getIdcompagne(): ?string
    {
        return $this->idcompagne;
    }

    public function setIdcompagne(string $idcompagne): self
    {
        $this->idcompagne = $idcompagne;

        return $this;
    }

    public function getTypemessage(): ?string
    {
        return $this->typemessage;
    }

    public function setTypemessage(string $typemessage): self
    {
        $this->typemessage = $typemessage;

        return $this;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(string $content): self
    {
        $this->content = $content;

        return $this;
    }

    public function getLangue(): ?string
    {
        return $this->langue;
    }

    public function setLangue(string $langue): self
    {
        $this->langue = $langue;

        return $this;
    }

    public function getIduser(): ?string
    {
        return $this->iduser;
    }

    public function setIduser(string $iduser): self
    {
        $this->iduser = $iduser;

        return $this;
    }

    public function getSendermessage(): ?string
    {
        return $this->sendermessage;
    }

    public function setSendermessage(string $sendermessage): self
    {
        $this->sendermessage = $sendermessage;

        return $this;
    }
}
