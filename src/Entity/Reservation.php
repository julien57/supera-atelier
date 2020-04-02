<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\ReservationRepository")
 */
class Reservation
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\User", inversedBy="reservations")
     * @ORM\JoinColumn(nullable=false)
     */
    private $user;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $chargeId;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $reservedAt;

    /**
     * @ORM\Column(type="string", length=20)
     */
    private $orderNumber;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\WorkshopDate", inversedBy="reservations")
     * @ORM\JoinColumn(nullable=false)
     */
    private $workshopDate;

    public function __construct()
    {
        $this->reservedAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getChargeId(): ?string
    {
        return $this->chargeId;
    }

    public function setChargeId(string $chargeId): self
    {
        $this->chargeId = $chargeId;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getReservedAt(): \DateTime
    {
        return $this->reservedAt;
    }

    /**
     * @param \DateTime $reservedAt
     */
    public function setReservedAt(\DateTime $reservedAt): void
    {
        $this->reservedAt = $reservedAt;
    }

    /**
     * @return mixed
     */
    public function getOrderNumber()
    {
        return $this->orderNumber;
    }

    /**
     * @param mixed $orderNumber
     */
    public function setOrderNumber(string $orderNumber): void
    {
        $this->orderNumber = $orderNumber;
    }

    public function getWorkshopDate(): ?WorkshopDate
    {
        return $this->workshopDate;
    }

    public function setWorkshopDate(?WorkshopDate $workshopDate): self
    {
        $this->workshopDate = $workshopDate;

        return $this;
    }
}
