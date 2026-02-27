<?php

declare(strict_types=1);

/**
 * FlipDev_FixDepersonalize
 *
 * Plugin on Magento\PageCache\Model\DepersonalizeChecker.
 *
 * Problem:
 * Hyva Checkout's plugin on Magento\Checkout\Controller\Index\Index uses
 * ResultFactory::TYPE_FORWARD to internally forward the request to
 * hyva_checkout/index/index. This forward triggers a second dispatch cycle.
 * During that second cycle, DepersonalizePlugin::afterDispatch() fires and
 * calls clearStorage() on the customer session — because the checkout layout
 * XML sets cacheable="false" but layout XML is only parsed *after* the
 * controller dispatch completes, so the non-cacheable flag is unknown at the
 * time DepersonalizeChecker::needToProcess() runs.
 *
 * Result: getCustomerId() returns null → hasItems() returns false → the
 * customer is redirected back to cart.
 *
 * Fix:
 * Return false from needToProcess() for the checkout, hyva_checkout and
 * customer module routes, preventing depersonalization on those pages
 * regardless of FPC state.
 *
 * @author    Philipp Breitsprecher
 * @copyright Copyright (c) 2026 FlipDev
 * @license   https://opensource.org/licenses/MIT MIT License
 */

namespace FlipDev\FixDepersonalize\Plugin;

use Magento\Framework\App\RequestInterface;
use Magento\PageCache\Model\DepersonalizeChecker;

class DepersonalizeCheckerPlugin
{
    /**
     * Module routes that must never have their customer session cleared.
     */
    private const PROTECTED_ROUTES = [
        'checkout',
        'hyva_checkout',
        'customer',
    ];

    public function __construct(
        private readonly RequestInterface $request,
    ) {
    }

    /**
     * Suppress depersonalization for checkout and customer routes.
     *
     * Returning false skips all subsequent plugins and the original method,
     * ensuring the customer session survives the TYPE_FORWARD second dispatch.
     *
     * @param DepersonalizeChecker $subject
     * @param callable             $proceed
     * @return bool
     */
    public function aroundNeedToProcess(
        DepersonalizeChecker $subject,
        callable $proceed,
    ): bool {
        $moduleName = (string) $this->request->getModuleName();

        if (in_array($moduleName, self::PROTECTED_ROUTES, true)) {
            return false;
        }

        return (bool) $proceed();
    }
}
