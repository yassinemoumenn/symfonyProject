<?php

namespace App\Entity;

use App\Repository\CompagnecsvRepository;
use Doctrine\ORM\Mapping as ORM;
use ApiPlatform\Core\Annotation\ApiResource;

/**
 * @ApiResource()
 * @ORM\Entity(repositoryClass=CompagnecsvRepository::class)
 */
class Compagnecsv
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
    private $messagesimple;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $messagepiece;

    /**
     * @ORM\Column(type="datetime")
     */
    private $datecreation;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $iduser;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $nomfilecsv;

    /**
     * @ORM\Column(type="string", length=10000000)
     */
    private $datafilecsv;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $typemessage;

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

    public function getMessagesimple(): ?string
    {
        return $this->messagesimple;
    }

    public function setMessagesimple(string $messagesimple): self
    {
        $this->messagesimple = $messagesimple;

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

    public function getIduser(): ?string
    {
        return $this->iduser;
    }

    public function setIduser(string $iduser): self
    {
        $this->iduser = $iduser;

        return $this;
    }

    public function getNomfilecsv(): ?string
    {
        return $this->nomfilecsv;
    }

    public function setNomfilecsv(string $nomfilecsv): self
    {
        $this->nomfilecsv = $nomfilecsv;

        return $this;
    }

    public function getDatafilecsv(): ?string
    {
        return $this->datafilecsv;
    }

    public function setDatafilecsv(string $datafilecsv): self
    {
        $this->datafilecsv = $datafilecsv;

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
}
