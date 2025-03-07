<?php
declare(strict_types=1);

namespace PeachCode\GoogleOneTap\Plugin;

use Closure;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\Request\CsrfValidator;
use Magento\Framework\App\RequestInterface;

class DisableCsrfValidation
{

    /**
     * @param CsrfValidator $subject
     * @param Closure $proceed
     * @param RequestInterface $request
     * @param ActionInterface $action
     * @return void
     */
    public function aroundValidate(
        CsrfValidator $subject,
        Closure $proceed,
        RequestInterface $request,
        ActionInterface $action
    ) {
        if ($request->getModuleName() == 'PeachCode_GoogleOneTap') {
            return; // Skip CSRF validation
        }

        if (strpos($request->getOriginalPathInfo(), 'response') !== false) {
            return; // Skip CSRF validation
        }
        $proceed($request, $action);
    }
}
