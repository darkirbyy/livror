<?php

declare(strict_types=1);

namespace App\Security\Voter;

use App\Entity\Account\User;
use App\Entity\Main\Review;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class ReviewVoter extends Voter
{
    public const EDIT = 'edit';
    public const DELETE = 'delete';

    // Cache this voter depending on the attribute value
    public function supportsAttribute(string $attribute): bool
    {
        return in_array($attribute, [self::EDIT, self::DELETE], true);
    }

    // Cache this voter depending on the object type
    public function supportsType(string $subjectType): bool
    {
        return is_a($subjectType, Review::class, true);
    }

    // Only vote for the defined attribute and one a Review object
    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::EDIT, self::DELETE]) && $subject instanceof Review;
    }

    // Access granted if the review has been created by the user
    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        /** @var Review $review */
        $review = $subject;
        $user = $token->getUser();

        return $review->getUserId() === $user->getId();
    }
}
