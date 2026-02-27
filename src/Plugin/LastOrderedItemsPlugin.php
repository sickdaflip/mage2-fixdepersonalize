<?php

declare(strict_types=1);

namespace FlipDev\FixDepersonalize\Plugin;

use Magento\Sales\CustomerData\LastOrderedItems;

class LastOrderedItemsPlugin
{
    public function aroundGetSectionData(
        LastOrderedItems $subject,
        callable $proceed
    ): array {
        try {
            return $proceed();
        } catch (\Exception $e) {
            return ['items' => []];
        }
    }
}
