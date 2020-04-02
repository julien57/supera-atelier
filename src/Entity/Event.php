<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="App\Repository\EventRepository")
 */
class Event
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     * @Assert\NotBlank(message="Veuillez définir le titre")
     */
    private $title;

    /**
     * @ORM\Column(type="text")
     * @Assert\NotBlank(message="Veuillez décrire l'événement")
     */
    private $description;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $slug;

    /**
     * @ORM\Column(type="float")
     * @Assert\Positive(message="Chiffre impossible !")
     * @Assert\NotBlank(message="Veuillez entrer le prix de l'atelier")
     */
    private $price;

    /**
     * @ORM\Column(type="string", length=255)
     * @Assert\NotBlank(message="Veuillez entrer le nom du formateur")
     */
    private $formatorName;

    /**
     * @ORM\Column(type="integer")
     * @Assert\NotBlank(message="Veuillez entrer le nombre maximum de participants")
     * @Assert\Positive(message="Chiffre impossible !")
     */
    private $nbMembers;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $note;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\EventType", inversedBy="events", cascade={"persist"})
     * @ORM\JoinColumn(nullable=false)
     */
    private $eventType;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Photo", mappedBy="event", orphanRemoval=true, cascade={"persist", "remove"})
     */
    private $photos;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\User", inversedBy="events")
     */
    private $user;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\WorkshopDate", mappedBy="event", orphanRemoval=true)
     */
    private $workshopDates;

    /**
     * @ORM\ManyToMany(targetEntity="App\Entity\User", inversedBy="workshops")
     * @ORM\JoinColumn(nullable=true)
     */
    private $participants;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Comment", mappedBy="event", cascade={"remove"})
     */
    private $comments;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\GiftAmount", mappedBy="event", cascade={"remove"})
     */
    private $giftAmounts;

    public function __construct()
    {
        $this->photos = new ArrayCollection();
        $this->workshopDates = new ArrayCollection();
        $this->participants = new ArrayCollection();
        $this->comments = new ArrayCollection();
        $this->giftAmounts = new ArrayCollection();
    }


    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): self
    {
        $this->slug = $slug;

        return $this;
    }

    public function getEventType(): ?EventType
    {
        return $this->eventType;
    }

    public function setEventType(?EventType $eventType): self
    {
        $this->eventType = $eventType;

        return $this;
    }

    public function getPrice(): ?float
    {
        return $this->price;
    }

    public function setPrice(float $price): self
    {
        $this->price = $price;

        return $this;
    }

    public function __toString()
    {
        return $this->title;
    }

    /**
     * @return Collection|Photo[]
     */
    public function getPhotos(): Collection
    {
        return $this->photos;
    }

    public function addPhoto(Photo $photo): self
    {
        if (!$this->photos->contains($photo)) {
            $this->photos[] = $photo;
            $photo->setEvent($this);
        }

        return $this;
    }

    public function removePhoto(Photo $photo): self
    {
        if ($this->photos->contains($photo)) {
            $this->photos->removeElement($photo);
            // set the owning side to null (unless already changed)
            if ($photo->getEvent() === $this) {
                $photo->setEvent(null);
            }
        }

        return $this;
    }

    public function getFormatorName(): ?string
    {
        return $this->formatorName;
    }

    public function setFormatorName(string $formatorName): self
    {
        $this->formatorName = $formatorName;

        return $this;
    }

    public function getNbMembers(): ?int
    {
        return $this->nbMembers;
    }

    public function setNbMembers(int $nbMembers): self
    {
        $this->nbMembers = $nbMembers;

        return $this;
    }

    public function getFirstPhoto()
    {
        return $this->photos->first();
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

    public function getNote(): ?float
    {
        return $this->note;
    }

    public function setNote(?float $note): self
    {
        $this->note = $note;

        return $this;
    }

    public function getWorkshopDates()
    {
        return $this->workshopDates;
    }

    public function addWorkshopDate(WorkshopDate $workshopDate): self
    {
        if (!$this->workshopDates->contains($workshopDate)) {
            $this->workshopDates[] = $workshopDate;
            $workshopDate->setEvent($this);
        }

        return $this;
    }

    public function removeWorkshopDate(WorkshopDate $workshopDate): self
    {
        if ($this->workshopDates->contains($workshopDate)) {
            $this->workshopDates->removeElement($workshopDate);
            // set the owning side to null (unless already changed)
            if ($workshopDate->getEvent() === $this) {
                $workshopDate->setEvent(null);
            }
        }

        return $this;
    }

    public function getFirstWorkshopDates()
    {
        if (!$this->workshopDates->first()) {
            return false;
        }
        return $this->workshopDates->first();
    }

    public function getAllWorkshopDates()
    {
        if ($this->workshopDates->count() <= 0) {
            return false;
        }
        return $this->workshopDates;
    }

    /**
     * @return Collection|User[]
     */
    public function getParticipants(): Collection
    {
        return $this->participants;
    }

    public function addParticipant(User $participant): self
    {
        if (!$this->participants->contains($participant)) {
            $this->participants[] = $participant;
        }

        return $this;
    }

    public function removeParticipant(User $participant): self
    {
        if ($this->participants->contains($participant)) {
            $this->participants->removeElement($participant);
        }

        return $this;
    }

    /**
     * @return Collection|Comment[]
     */
    public function getComments(): Collection
    {
        return $this->comments;
    }

    public function addComment(Comment $comment): self
    {
        if (!$this->comments->contains($comment)) {
            $this->comments[] = $comment;
            $comment->setEvent($this);
        }

        return $this;
    }

    public function removeComment(Comment $comment): self
    {
        if ($this->comments->contains($comment)) {
            $this->comments->removeElement($comment);
            // set the owning side to null (unless already changed)
            if ($comment->getEvent() === $this) {
                $comment->setEvent(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|GiftAmount[]
     */
    public function getGiftAmounts(): Collection
    {
        return $this->giftAmounts;
    }

    public function addGiftAmount(GiftAmount $giftAmount): self
    {
        if (!$this->giftAmounts->contains($giftAmount)) {
            $this->giftAmounts[] = $giftAmount;
            $giftAmount->setEvent($this);
        }

        return $this;
    }

    public function removeGiftAmount(GiftAmount $giftAmount): self
    {
        if ($this->giftAmounts->contains($giftAmount)) {
            $this->giftAmounts->removeElement($giftAmount);
            // set the owning side to null (unless already changed)
            if ($giftAmount->getEvent() === $this) {
                $giftAmount->setEvent(null);
            }
        }

        return $this;
    }
}
