<?php

namespace KaufmannDigital\GDPR\CookieConsent\Mvc\View;

use Neos\Flow\Mvc\Exception;
use Neos\Flow\Mvc\View\AbstractView;

/**
 * An abstract View
 *
 * @api
 */
class CustomTemplateView extends AbstractView
{
    /**
     * @var array
     */
    protected $supportedOptions = [
        'templateSource' => ['', 'Source of the template to render', 'string'],
        'templatePathAndFilename' => [null, 'path and filename where the template source is found', 'string'],
    ];

    /**
     * Renders the view
     *
     * @return string The rendered view
     * @throws Exception
     * @api
     */
    public function render()
    {
        $source = $this->getOption('templateSource');
        $templatePathAndFilename = $this->getOption('templatePathAndFilename');
        if ($templatePathAndFilename !== null) {
            $source = file_get_contents($templatePathAndFilename);
        }
        return $source;
    }
}
