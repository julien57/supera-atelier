<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="App\Repository\GiftAmountRepository")
 */
class GiftAmount
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $code;

    /**
     * @ORM\Column(type="integer")
     */
    private $amount;

    /**
     * @ORM\Column(type="boolean")
     */
    private $isValid;

    /**
     * @ORM\Column(type="datetime")
     */
    private $createdAt;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private $chargeId;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\User", inversedBy="gifts")
     * @ORM\JoinColumn(nullable=false)
     */
    private $user;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Assert\NotBlank(message="Veuillez définir le prénom du destinataire")
     */
    private $forName;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Assert\NotBlank(message="Veuillez définir votre prénom")
     */
    private $ofName;

    /**
     * @ORM\Column(type="text", nullable=true)
     * @Assert\NotBlank(message="Veuillez définir un message")
     */
    private $message;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Event", inversedBy="giftAmounts")
     */
    private $event;

    public function __construct()
    {
        $this->isValid = false;
        $this->createdAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(string $code): self
    {
        $this->code = $code;

        return $this;
    }

    public function getAmount(): ?int
    {
        return $this->amount;
    }

    public function setAmount(int $amount): self
    {
        $this->amount = $amount;

        return $this;
    }

    public function getIsValid(): ?bool
    {
        return $this->isValid;
    }

    public function setIsValid(bool $isValid): self
    {
        $this->isValid = $isValid;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * @param mixed $createdAt
     */
    public function setCreatedAt($createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getChargeId()
    {
        return $this->chargeId;
    }

    /**
     * @param mixed $chargeId
     */
    public function setChargeId(?string $chargeId): void
    {
        $this->chargeId = $chargeId;
    }

    public function getForName(): ?string
    {
        return $this->forName;
    }

    public function setForName(?string $forName): self
    {
        $this->forName = $forName;

        return $this;
    }

    public function getOfName(): ?string
    {
        return $this->ofName;
    }

    public function setOfName(?string $ofName): self
    {
        $this->ofName = $ofName;

        return $this;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(?string $message): self
    {
        $this->message = $message;

        return $this;
    }

    public function getEvent(): ?Event
    {
        return $this->event;
    }

    public function setEvent(?Event $event): self
    {
        $this->event = $event;

        return $this;
    }
}
