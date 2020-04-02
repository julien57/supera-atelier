<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\PageRepository")
 */
class Page
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $presentation;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $valeurs;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $avantagesAtelier;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $quipeutdeposer;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $mentions;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $cgv;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $cookies;

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return mixed
     */
    public function getPresentation()
    {
        return $this->presentation;
    }

    /**
     * @param mixed $presentation
     */
    public function setPresentation(string $presentation): void
    {
        $this->presentation = $presentation;
    }

    public function getValeurs(): ?string
    {
        return $this->valeurs;
    }

    public function setValeurs(?string $valeurs): self
    {
        $this->valeurs = $valeurs;

        return $this;
    }

    public function getAvantagesAtelier(): ?string
    {
        return $this->avantagesAtelier;
    }

    public function setAvantagesAtelier(?string $avantagesAtelier): self
    {
        $this->avantagesAtelier = $avantagesAtelier;

        return $this;
    }

    public function getQuipeutdeposer(): ?string
    {
        return $this->quipeutdeposer;
    }

    public function setQuipeutdeposer(?string $quipeutdeposer): self
    {
        $this->quipeutdeposer = $quipeutdeposer;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getMentions()
    {
        return $this->mentions;
    }

    /**
     * @param mixed $mentions
     */
    public function setMentions($mentions): void
    {
        $this->mentions = $mentions;
    }

    /**
     * @return mixed
     */
    public function getCgv()
    {
        return $this->cgv;
    }

    /**
     * @param mixed $cgv
     */
    public function setCgv($cgv): void
    {
        $this->cgv = $cgv;
    }

    /**
     * @return mixed
     */
    public function getCookies()
    {
        return $this->cookies;
    }

    /**
     * @param mixed $cookies
     */
    public function setCookies($cookies): void
    {
        $this->cookies = $cookies;
    }
}
