<?php

namespace App\Entity;

use App\Repository\CourrierRepository;
use Doctrine\ORM\Mapping as ORM;

use ApiPlatform\Core\Annotation\ApiResource;


/**
 * @ApiResource()
 * @ORM\Entity(repositoryClass=CourrierRepository::class)
 */
class Courrier
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    public $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    public $titre;

    /**
     * @ORM\Column(type="string", length=255)
     */
    public $objet;

    /**
     * @ORM\Column(type="string", length=255)
     */
    public $entet;

    /**
     * @ORM\Column(type="string", length=255)
     */
    public $pied;

    /**
     * @ORM\Column(type="string", length=100000000)
     */
    public $content;

    /**
     * @ORM\Column(type="string", length=1000000000)
     */
    public $logo;

    /**
     * @ORM\Column(type="string", length=255)
     */
    public $langue;

    /**
     * @ORM\Column(type="string", length=255)
     */
    public $identifier;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitre(): ?string
    {
        return $this->titre;
    }

    public function setTitre(string $titre): self
    {
        $this->titre = $titre;

        return $this;
    }

    public function getObjet(): ?string
    {
        return $this->objet;
    }

    public function setObjet(string $objet): self
    {
        $this->objet = $objet;

        return $this;
    }

    public function getEntet(): ?string
    {
        return $this->entet;
    }

    public function setEntet(string $entet): self
    {
        $this->entet = $entet;

        return $this;
    }

    public function getPied(): ?string
    {
        return $this->pied;
    }

    public function setPied(string $pied): self
    {
        $this->pied = $pied;

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

    public function getLogo(): ?string
    {
        return $this->logo;
    }

    public function setLogo(string $logo): self
    {
        $this->logo = $logo;

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

    public function getIdentifier(): ?string
    {
        return $this->identifier;
    }

    public function setIdentifier(string $identifier): self
    {
        $this->identifier = $identifier;

        return $this;
    }
}
