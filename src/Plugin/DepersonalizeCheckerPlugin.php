<?php
/**
 * Fix: Prevent FPC depersonalization on routes where customer session integrity
 * is required. Depersonalizing clears is_logged_in from the customer session,
 * causing Hyva sections to render the guest state even for logged-in customers.
 *
 * Affected routes without this fix:
 * - catalog: product/category pages show guest UI to logged-in customers
 * - checkout: empty quote, redirect to cart
 * - customer: account pages lose session
 * - hyva_checkout: Hyva checkout loses customer context
 */
declare(strict_types=1);

namespace FlipDev\FixDepersonalize\Plugin;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\View\LayoutInterface;
use Magento\PageCache\Model\DepersonalizeChecker;

class DepersonalizeCheckerPlugin
{
    /**
     * Routes on which depersonalization must be suppressed.
     * Depersonalizing clears the customer session mid-request; Hyva cannot
     * reliably restore all UI state from section/load on these routes.
     */
    private const PROTECTED_MODULES = [
        'catalog',
        'checkout',
        'customer',
        'hyva_checkout',
    ];

    private RequestInterface $request;

    public function __construct(RequestInterface $request)
    {
        $this->request = $request;
    }

    /**
     * Suppress depersonalization on protected routes.
     *
     * @param DepersonalizeChecker $subject
     * @param bool                 $result
     * @param LayoutInterface      $layout
     * @return bool
     */
    public function afterCheckIfDepersonalize(
        DepersonalizeChecker $subject,
        bool $result,
        LayoutInterface $layout
    ): bool {
        if (!$result) {
            return false;
        }

        if (in_array($this->request->getModuleName(), self::PROTECTED_MODULES, true)) {
            return false;
        }

        return $result;
    }
}
