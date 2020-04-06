<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="App\Repository\UserRepository")
 * @UniqueEntity("email")
 */
class User implements UserInterface
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=180, unique=true)
     * @Assert\NotBlank(message="Veuillez entrer votre adresse mail", groups={"isEnterprise", "notEnterprise"})
     * @Assert\Email(message="L'adresse mail renseignée n'est pas valide", groups={"isEnterprise", "notEnterprise", "isEnterpriseSpace"})
     * @Assert\Unique(message="Cette adresse mail existe déjà chez SuperAtelier")
     */
    private $email;

    /**
     * @ORM\Column(type="string", length=180, nullable=true)
     * @Assert\NotBlank(message="Veuillez entrer votre prénom", groups={"isEnterprise", "notEnterprise", "isEnterpriseSpace"})
     */
    private $firstName;

    /**
     * @ORM\Column(type="string", length=180, nullable=true)
     * @Assert\NotBlank(message="Veuillez entrer votre nom", groups={"isEnterprise", "notEnterprise", "isEnterpriseSpace"})
     */
    private $lastName;

    /**
     * @ORM\Column(type="array")
     */
    private $roles = [];

    /**
     * @var string The hashed password
     * @ORM\Column(type="string")
     * @Assert\NotBlank(message="Veuillez définir un mot de passe", groups={"isEnterprise", "notEnterprise"})
     * @Assert\Length(min=6, minMessage="Veuillez choisir au moins 6 caractères pour votre mot de passe", groups={"isEnterprise", "notEnterprise"})
     */
    private $password;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $entrepriseName;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $entrepriseType;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Assert\NotBlank(message="Veuillez entrer votre adresse", groups={"isEnterprise", "isEnterpriseSpace"})
     */
    private $adress;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Assert\NotBlank(message="Veuillez entrer votre code postal", groups={"isEnterprise", "isEnterpriseSpace"})
     */
    private $zipCode;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Assert\NotBlank(message="Veuillez renseigner votre ville", groups={"isEnterprise", "isEnterpriseSpace"})
     */
    private $city;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $phone;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $website;

    /**
     * @ORM\Column(type="boolean")
     */
    private $isEnterprise;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Event", mappedBy="user")
     */
    private $events;

    /**
     * @ORM\ManyToMany(targetEntity="App\Entity\Event", mappedBy="participants")
     * @ORM\JoinColumn(nullable=true)
     */
    private $workshops;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Reservation", mappedBy="user")
     */
    private $reservations;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Comment", mappedBy="user")
     */
    private $comments;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\GiftAmount", mappedBy="user")
     */
    private $gifts;

    /**
     * @ORM\Column(type="string", length=13, nullable=true)
     */
    private $umberGiftCard;

    /**
     * @ORM\Column(type="float")
     */
    private $moneyGift;

    public function __construct()
    {
        $this->events = new ArrayCollection();
        $this->workshops = new ArrayCollection();
        $this->reservations = new ArrayCollection();
        $this->comments = new ArrayCollection();
        $this->gifts = new ArrayCollection();
        $this->moneyGift = 0;
    }

    /**
     * @return mixed
     */
    public function getFirstName()
    {
        return $this->firstName;
    }

    /**
     * @param mixed $firstName
     * @return User
     */
    public function setFirstName($firstName)
    {
        $this->firstName = $firstName;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getLastName()
    {
        return $this->lastName;
    }

    /**
     * @param mixed $lastName
     * @return User
     */
    public function setLastName(?string $lastName)
    {
        $this->lastName = $lastName;
        return $this;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): self
    {
        $this->email = $email;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUsername(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // give everyone ROLE_USER!
        if (!in_array('ROLE_USER', $roles)) {
            $roles[] = 'ROLE_USER';
        }

        return $roles;
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function getPassword(): string
    {
        return (string) $this->password;
    }

    public function setPassword(?string $password): self
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function getSalt()
    {
        // not needed when using the "bcrypt" algorithm in security.yaml
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials()
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    public function getEntrepriseName(): ?string
    {
        return $this->entrepriseName;
    }

    public function setEntrepriseName(?string $entrepriseName): self
    {
        $this->entrepriseName = $entrepriseName;

        return $this;
    }

    public function getEntrepriseType(): ?string
    {
        return $this->entrepriseType;
    }

    public function setEntrepriseType(?string $entrepriseType): self
    {
        $this->entrepriseType = $entrepriseType;

        return $this;
    }

    public function getAdress(): ?string
    {
        return $this->adress;
    }

    public function setAdress(?string $adress): self
    {
        $this->adress = $adress;

        return $this;
    }

    public function getZipCode(): ?string
    {
        return $this->zipCode;
    }

    public function setZipCode(?string $zipCode): self
    {
        $this->zipCode = $zipCode;

        return $this;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(?string $city): self
    {
        $this->city = $city;

        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): self
    {
        $this->phone = $phone;

        return $this;
    }

    public function getWebsite(): ?string
    {
        return $this->website;
    }

    public function setWebsite(?string $website): self
    {
        $this->website = $website;

        return $this;
    }

    public function getIsEnterprise()
    {
        return $this->isEnterprise;
    }

    public function setIsEnterprise(bool $isEnterprise)
    {
        $this->isEnterprise = $isEnterprise;
        return $this;
    }

    /**
     * @return Collection|Event[]
     */
    public function getEvents(): Collection
    {
        return $this->events;
    }

    public function addEvent(Event $event): self
    {
        if (!$this->events->contains($event)) {
            $this->events[] = $event;
            $event->setUser($this);
        }

        return $this;
    }

    public function removeEvent(Event $event): self
    {
        if ($this->events->contains($event)) {
            $this->events->removeElement($event);
            // set the owning side to null (unless already changed)
            if ($event->getUser() === $this) {
                $event->setUser(null);
            }
        }

        return $this;
    }

    public function getDisplayName()
    {
        if ($this->isEnterprise) {
            return $this->entrepriseName;
        }
        return $this->firstName.' '.$this->lastName;
    }

    /**
     * @return Collection|Event[]
     */
    public function getWorkshops(): Collection
    {
        return $this->workshops;
    }

    public function addWorkshop(Event $workshop): self
    {
        if (!$this->workshops->contains($workshop)) {
            $this->workshops[] = $workshop;
            $workshop->addParticipant($this);
        }

        return $this;
    }

    public function removeWorkshop(Event $workshop): self
    {
        if ($this->workshops->contains($workshop)) {
            $this->workshops->removeElement($workshop);
            $workshop->removeParticipant($this);
        }

        return $this;
    }

    /**
     * @return Collection|Reservation[]
     */
    public function getReservations(): Collection
    {
        return $this->reservations;
    }

    public function addReservation(Reservation $reservation): self
    {
        if (!$this->reservations->contains($reservation)) {
            $this->reservations[] = $reservation;
            $reservation->setUser($this);
        }

        return $this;
    }

    public function removeReservation(Reservation $reservation): self
    {
        if ($this->reservations->contains($reservation)) {
            $this->reservations->removeElement($reservation);
            // set the owning side to null (unless already changed)
            if ($reservation->getUser() === $this) {
                $reservation->setUser(null);
            }
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
            $comment->addUser($this);
        }

        return $this;
    }

    public function removeComment(Comment $comment): self
    {
        if ($this->comments->contains($comment)) {
            $this->comments->removeElement($comment);
            $comment->removeUser($this);
        }

        return $this;
    }

    /**
     * @return Collection|GiftAmount[]
     */
    public function getGifts(): Collection
    {
        return $this->gifts;
    }

    public function addGift(GiftAmount $gift): self
    {
        if (!$this->gifts->contains($gift)) {
            $this->gifts[] = $gift;
            $gift->setUser($this);
        }

        return $this;
    }

    public function removeGift(GiftAmount $gift): self
    {
        if ($this->gifts->contains($gift)) {
            $this->gifts->removeElement($gift);
            // set the owning side to null (unless already changed)
            if ($gift->getUser() === $this) {
                $gift->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return mixed
     */
    public function getUmberGiftCard()
    {
        return $this->umberGiftCard;
    }

    /**
     * @param mixed $umberGiftCard
     */
    public function setUmberGiftCard(string $umberGiftCard): void
    {
        $this->umberGiftCard = $umberGiftCard;
    }

    public function getMoneyGift(): ?float
    {
        return $this->moneyGift;
    }

    public function setMoneyGift(?float $moneyGift): self
    {
        $this->moneyGift = $moneyGift;

        return $this;
    }
}
