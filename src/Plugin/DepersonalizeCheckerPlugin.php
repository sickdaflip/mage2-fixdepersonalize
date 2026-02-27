<?php

/**
 * Fix: Prevent FPC depersonalization on Hyva Checkout routes.
 * Depersonalizing the customer session during the Forward dispatch
 * to hyva_checkout/index/index causes an empty customer session,
 * resulting in an empty quote and a redirect to checkout/cart.
 */
declare(strict_types=1);

namespace FlipDev\FixDepersonalize\Plugin;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\View\LayoutInterface;
use Magento\PageCache\Model\DepersonalizeChecker;

class DepersonalizeCheckerPlugin
{
    private RequestInterface $request;

    public function __construct(RequestInterface $request)
    {
        $this->request = $request;
    }

    public function afterCheckIfDepersonalize(
        DepersonalizeChecker $subject,
        bool $result,
        LayoutInterface $layout
    ): bool {
        if (!$result) {
            return false;
        }

        $moduleName = $this->request->getModuleName();
        $controllerName = $this->request->getControllerName();

        // Do not depersonalize on customer routes.
        if ($moduleName === 'hyva_checkout' || $moduleName === 'checkout' || $moduleName === 'customer') {
            return false;
        }

        return $result;
    }
}
