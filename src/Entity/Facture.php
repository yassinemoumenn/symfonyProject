<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use App\Repository\FactureRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ApiResource()
 * @ORM\Entity(repositoryClass=FactureRepository::class)
 */
class Facture
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
    private $datecreation;

    /**
     * @ORM\Column(type="integer")
     */
    private $nembremessageenv;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $typemessageenv;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $unite;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $totale;

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

    public function getDatecreation(): ?\DateTimeInterface
    {
        return $this->datecreation;
    }

    public function setDatecreation(\DateTimeInterface $datecreation): self
    {
        $this->datecreation = $datecreation;

        return $this;
    }

    public function getNembremessageenv(): ?int
    {
        return $this->nembremessageenv;
    }

    public function setNembremessageenv(int $nembremessageenv): self
    {
        $this->nembremessageenv = $nembremessageenv;

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
}
