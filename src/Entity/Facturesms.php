<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use App\Repository\FacturesmsRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ApiResource()
 * @ORM\Entity(repositoryClass=FacturesmsRepository::class)
 */
class Facturesms
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
    private $iduser;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $idboss;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $nomuser;

    /**
     * @ORM\Column(type="date")
     */
    private $datecration;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $nombremessagenv;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $typemessageenv;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $taille;

     /**
     * @ORM\Column(type="string", length=255)
     */
    private $unite;

     /**
     * @ORM\Column(type="string", length=255)
     */
    private $totale;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $idmessage;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getIdboss(): ?string
    {
        return $this->idboss;
    }

    public function setIdboss(string $idboss): self
    {
        $this->idboss = $idboss;

        return $this;
    }

    public function getNomuser(): ?string
    {
        return $this->nomuser;
    }

    public function setNomuser(string $nomuser): self
    {
        $this->nomuser = $nomuser;

        return $this;
    }

    public function getDatecration(): ?\DateTimeInterface
    {
        return $this->datecration;
    }

    public function setDatecration(\DateTimeInterface $datecration): self
    {
        $this->datecration = $datecration;

        return $this;
    }

    public function getNombremessagenv(): ?string
    {
        return $this->nombremessagenv;
    }

    public function setNombremessagenv(string $nombremessagenv): self
    {
        $this->nombremessagenv = $nombremessagenv;

        return $this;
    }

    public function getTypemessageenv(): ?string
    {
        return $this->typemessageenv;
    }

    public function setTypemessageenv(string $typemessageenv): self
    {
        $this->typemessageenv = $typemessageenv;

        return $this;
    }

    public function getTaille(): ?string
    {
        return $this->taille;
    }

    public function setTaille(string $taille): self
    {
        $this->taille = $taille;

        return $this;
    }

    public function getUnite(): ?string
    {
        return $this->unite;
    }

    public function setUnite(string $unite): self
    {
        $this->unite = $unite;

        return $this;
    }

    public function getTotale(): ?string
    {
        return $this->totale;
    }

    public function setTotale(string $totale): self
    {
        $this->totale = $totale;

        return $this;
    }

    public function getIdmessage(): ?string
    {
        return $this->idmessage;
    }

    public function setIdmessage(string $idmessage): self
    {
        $this->idmessage = $idmessage;

        return $this;
    }
}
