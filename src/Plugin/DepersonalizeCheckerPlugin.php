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
 * time DepersonalizeChecker::checkIfDepersonalize() runs.
 *
 * Result: getCustomerId() returns null → hasItems() returns false → the
 * customer is redirected back to cart.
 *
 * Fix:
 * Return false from checkIfDepersonalize() for the checkout, hyva_checkout and
 * customer module routes, preventing depersonalization on those pages
 * regardless of FPC state.
 *
 * @author    Philipp Breitsprecher
 * @copyright Copyright (c) 2026 FlipDev
 * @license   https://opensource.org/licenses/MIT MIT License
 */

namespace FlipDev\FixDepersonalize\Plugin;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\View\LayoutInterface;
use Magento\PageCache\Model\DepersonalizeChecker;

class DepersonalizeCheckerPlugin
{
    public function __construct(
        private readonly RequestInterface $request,
    ) {
    }

    /**
     * Suppress depersonalization for checkout and customer routes.
     *
     * Uses an after plugin so the original checkIfDepersonalize() logic runs
     * first. If it already returned false, we respect that. Otherwise we
     * override the result for protected routes to prevent the customer session
     * from being cleared during the TYPE_FORWARD second dispatch.
     *
     * @param DepersonalizeChecker $subject
     * @param bool                 $result
     * @param LayoutInterface      $layout
     * @return bool
     */
    public function afterCheckIfDepersonalize(
        DepersonalizeChecker $subject,
        bool $result,
        LayoutInterface $layout,
    ): bool {
        if (!$result) {
            return false;
        }

        $moduleName = $this->request->getModuleName();

        if ($moduleName === 'hyva_checkout' || $moduleName === 'checkout' || $moduleName === 'customer') {
            return false;
        }

        return $result;
    }
}
