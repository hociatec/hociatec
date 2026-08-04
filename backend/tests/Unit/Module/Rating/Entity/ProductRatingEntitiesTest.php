<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Rating\Entity;

use App\Module\Catalog\Domain\Entity\Category;
use App\Module\Catalog\Domain\Entity\Product;
use App\Module\Comment\Domain\Entity\ProductComment;
use App\Module\Order\Domain\Entity\OrderItem;
use App\Module\Rating\Domain\Entity\ProductRating;
use App\Module\Rating\Application\Projection\ProductReviewFormatter;
use App\Module\User\Domain\Entity\User;
use PHPUnit\Framework\TestCase;

final class ProductRatingEntitiesTest extends TestCase
{
    public function testRatingCommentAndFormatterExposeExpectedFields(): void
    {
        $user = new User('ada@example.com', 'Ada', 'Lovelace', new \DateTimeImmutable('1990-01-01'), '0102030405', 'female');
        $this->setEntityId($user, 77);

        $category = new Category('Phones', 'phones');
        $product = new Product('Phone', 'phone', 'PH-1', 'Desc', 10000, 5, $category);
        $this->setEntityId($product, 11);

        $orderItem = new OrderItem('Phone', 'PH-1', 10000, 1);
        $this->setEntityId($orderItem, 101);

        $rating = new ProductRating($product, $orderItem, $user, 4);
        $this->setEntityId($rating, 501);
        $originalUpdatedAt = $rating->getUpdatedAt();

        self::assertSame($product, $rating->getProduct());
        self::assertSame($orderItem, $rating->getOrderItem());
        self::assertSame($user, $rating->getUser());
        self::assertSame(4, $rating->getScore());
        self::assertSame(ProductRating::STATUS_PUBLISHED, $rating->getStatus());

        $rating->setScore(5);
        self::assertSame(5, $rating->getScore());

        $comment = new ProductComment($rating, 'Excellent');
        $this->setEntityId($comment, 601);
        $rating->setComment($comment);
        $rating->publish();

        self::assertSame($comment, $rating->getComment());
        self::assertInstanceOf(\DateTimeImmutable::class, $rating->getPublishedAt());

        usleep(1000);
        $rating->touch();
        self::assertGreaterThanOrEqual($originalUpdatedAt, $rating->getUpdatedAt());

        $formatted = ProductReviewFormatter::formatRating($rating, true);

        self::assertSame(501, $formatted['id']);
        self::assertSame(5, $formatted['score']);
        self::assertSame(ProductRating::STATUS_PUBLISHED, $formatted['status']);
        self::assertSame('Excellent', $formatted['comment']);
        self::assertSame(101, $formatted['orderItemId']);
        self::assertSame(['id' => 77, 'displayName' => 'Ada L.'], $formatted['author']);
    }

    public function testFormatterFallsBackToClientWhenAuthorNamesAreBlank(): void
    {
        $user = new User('blank@example.com', '', '', new \DateTimeImmutable('1990-01-01'), '0102030405', 'other');
        $this->setEntityId($user, 78);

        $category = new Category('Phones', 'phones');
        $product = new Product('Phone', 'phone', 'PH-1', 'Desc', 10000, 5, $category);
        $orderItem = new OrderItem('Phone', 'PH-1', 10000, 1);
        $rating = new ProductRating($product, $orderItem, $user, 3);

        $formatted = ProductReviewFormatter::formatRating($rating);

        self::assertSame('Client', $formatted['author']['displayName']);
        self::assertArrayNotHasKey('orderItemId', $formatted);
    }

    public function testProductCommentMutatorsAndTouch(): void
    {
        $user = new User('ada@example.com', 'Ada', 'Lovelace', new \DateTimeImmutable('1990-01-01'), '0102030405', 'female');
        $category = new Category('Phones', 'phones');
        $product = new Product('Phone', 'phone', 'PH-1', 'Desc', 10000, 5, $category);
        $orderItem = new OrderItem('Phone', 'PH-1', 10000, 1);
        $rating = new ProductRating($product, $orderItem, $user, 4);
        $comment = new ProductComment($rating, 'Initial');
        $originalUpdatedAt = $comment->getUpdatedAt();

        self::assertNull($comment->getId());
        $comment->setBody('Updated')->setIsVisible(false);

        self::assertSame($rating, $comment->getRating());
        self::assertSame('Updated', $comment->getBody());
        self::assertFalse($comment->isVisible());
        self::assertInstanceOf(\DateTimeImmutable::class, $comment->getCreatedAt());

        usleep(1000);
        $comment->touch();
        self::assertGreaterThanOrEqual($originalUpdatedAt, $comment->getUpdatedAt());
    }

    private function setEntityId(object $entity, int $id): void
    {
        $reflection = new \ReflectionObject($entity);
        $property = $reflection->getProperty('id');
        $property->setValue($entity, $id);
    }
}
