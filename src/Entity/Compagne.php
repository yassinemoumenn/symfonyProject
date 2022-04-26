<?php

namespace App\Entity;

use App\Repository\CompagneRepository;
use Doctrine\ORM\Mapping as ORM;
use ApiPlatform\Core\Annotation\ApiResource;

/**
 * @ApiResource()
 * @ORM\Entity(repositoryClass=CompagneRepository::class)
 */
class Compagne
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
    private $nom;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $type;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $typemessagewathssap;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $messagesimple;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $messagevariable;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $messagepiece;

    /**
     * @ORM\Column(type="date")
     */
    private $datecreation;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): self
    {
        $this->nom = $nom;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getTypemessagewathssap(): ?string
    {
        return $this->typemessagewathssap;
    }

    public function setTypemessagewathssap(string $typemessagewathssap): self
    {
        $this->typemessagewathssap = $typemessagewathssap;

        return $this;
    }

    public function getMessagesimple(): ?string
    {
        return $this->messagesimple;
    }

    public function setMessagesimple(string $messagesimple): self
    {
        $this->messagesimple = $messagesimple;

        return $this;
    }

    public function getMessagevariable(): ?string
    {
        return $this->messagevariable;
    }

    public function setMessagevariable(string $messagevariable): self
    {
        $this->messagevariable = $messagevariable;

        return $this;
    }

    public function getMessagepiece(): ?string
    {
        return $this->messagepiece;
    }

    public function setMessagepiece(string $messagepiece): self
    {
        $this->messagepiece = $messagepiece;

        return $this;
    }

    public function getDatecreation(): ?\DateTimeInterface
    {
        return $this->datecreation;
    }

    public function setDatecreation(\DateTimeInterface $datecreation): self
    {
        $this->datecreation = $datecreation;

        return $this;
    }
}
