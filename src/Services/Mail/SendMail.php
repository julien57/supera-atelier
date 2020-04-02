<?php

namespace App\Services\Mail;

use App\Entity\User;
use Twig\Environment;

class SendMail
{
    /**
     * @var \Swift_Mailer
     */
    private $mailer;

    public function __construct(\Swift_Mailer $mailer)
    {
        $this->mailer = $mailer;
    }

    public function forgotPassword(string $password, User $user)
    {
    }
}