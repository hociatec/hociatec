<?php

declare(strict_types=1);

namespace App\Module\Rating\Application\Projection;

use App\Module\Rating\Domain\Entity\ProductRating;

final class ProductReviewFormatter
{
    private function __construct()
    {
    }

    /**
     * @return array<string, mixed>
     */
    public static function formatRating(ProductRating $rating, bool $withOrderItem = false): array
    {
        $comment = $rating->getComment();
        $user = $rating->getUser();
        $firstName = $user->getFirstName();
        $lastName = $user->getLastName();
        if ('' !== $lastName) {
            $lastName = function_exists('mb_substr')
                ? mb_substr($lastName, 0, 1)
                : substr($lastName, 0, 1);
        }
        $displayName = '' !== $lastName
            ? trim(sprintf('%s %s.', $firstName, $lastName))
            : $firstName;

        $data = [
            'id' => $rating->getId(),
            'score' => $rating->getScore(),
            'status' => $rating->getStatus(),
            'comment' => $comment?->getBody(),
            'createdAt' => $rating->getCreatedAt()->format(DATE_ATOM),
            'publishedAt' => $rating->getPublishedAt()?->format(DATE_ATOM),
            'author' => [
                'id' => $user->getId(),
                'displayName' => '' !== $displayName ? $displayName : 'Client',
            ],
        ];

        if ($withOrderItem) {
            $data['orderItemId'] = $rating->getOrderItem()->getId();
        }

        return $data;
    }
}
