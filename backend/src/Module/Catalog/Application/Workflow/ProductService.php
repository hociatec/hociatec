<?php

declare(strict_types=1);

namespace App\Module\Catalog\Application\Workflow;

use App\Module\Catalog\Application\DTO\ProductWriteCommand;
use App\Module\Catalog\Application\Handler\ProductWriteHandler;
use App\Module\Catalog\Domain\Entity\Product;

final readonly class ProductService
{
    public function __construct(private ProductWriteHandler $writer)
    {
    }

    public function create(ProductWriteCommand $command): Product
    {
        return $this->writer->create($command);
    }

    public function update(ProductWriteCommand $command): Product
    {
        return $this->writer->update($command);
    }

    public function delete(Product $product): void
    {
        $this->writer->delete($product);
    }
}
