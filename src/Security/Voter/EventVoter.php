<?php

namespace App\Security\Voter;

use App\Entity\Event;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

class EventVoter extends Voter
{
    protected function supports($attribute, $subject)
    {
        // replace with your own logic
        // https://symfony.com/doc/current/security/voters.html
        return in_array($attribute, ['EDIT', 'VIEW'])
            && $subject instanceof \App\Entity\Event;
    }

    protected function voteOnAttribute($attribute, $subject, TokenInterface $token)
    {
        $user = $token->getUser();

        // if the user is anonymous, do not grant access
        if (!$user instanceof UserInterface) {
            return false;
        }

        $event = $subject;

        switch ($attribute) {
            case 'EDIT':
                return $this->canView($event, $user);
            case 'VIEW':
                return $this->canEdit($event, $user);
        }

        return false;
    }

    private function canView(Event $event, User $user)
    {
        if ($event->getUser()->getEmail() !== $user->getEmail()) {
            return false;
        }

        return true;
    }

    private function canEdit(Event $event, User $user)
    {
        if ($event->getUser()->getEmail() !== $user->getEmail()) {
            return false;
        }

        return true;
    }
}
