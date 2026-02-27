<?php

declare(strict_types=1);

/**
 * FlipDev_FixDepersonalize
 *
 * Plugin on Magento\Checkout\CustomerData\LastOrderedItems.
 *
 * Problem:
 * When a product that was part of a previous order has been deleted from the
 * catalog, Magento\Checkout\CustomerData\LastOrderedItems::getSectionData()
 * throws a NoSuchEntityException while building the mini-cart customer data.
 * This exception bubbles up during checkout initialisation and can cause an
 * additional redirect or a blank page.
 *
 * Fix:
 * Catch NoSuchEntityException around getSectionData() and return an empty
 * items array so the rest of checkout can continue normally.
 *
 * @author    Philipp Breitsprecher
 * @copyright Copyright (c) 2026 FlipDev
 * @license   https://opensource.org/licenses/MIT MIT License
 */

namespace FlipDev\FixDepersonalize\Plugin;

use Magento\Checkout\CustomerData\LastOrderedItems;
use Magento\Framework\Exception\NoSuchEntityException;
use Psr\Log\LoggerInterface;

class LastOrderedItemsPlugin
{
    public function __construct(
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * Catch NoSuchEntityException caused by deleted products in past orders.
     *
     * @param LastOrderedItems $subject
     * @param callable         $proceed
     * @return array<string, mixed>
     */
    public function aroundGetSectionData(
        LastOrderedItems $subject,
        callable $proceed,
    ): array {
        try {
            return (array) $proceed();
        } catch (NoSuchEntityException $e) {
            $this->logger->warning(
                'FlipDev_FixDepersonalize: LastOrderedItems skipped — product no longer exists: '
                . $e->getMessage()
            );

            return ['items' => []];
        }
    }
}
