<?php
declare(strict_types=1);

namespace KaufmannDigital\GDPR\CookieConsent\Eel\Helper;

use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\Eel\FlowQuery\FlowQuery;
use Neos\Eel\ProtectedContextAwareInterface;
use Neos\Flow\Annotations as Flow;

class GTMConsentsHelper implements ProtectedContextAwareInterface {

    public function getAllConfiguredConsents(Node $contextNode)
    {
        $q = new FlowQuery([$contextNode]);
        $nodesWithGtmConsents = $q->find('[instanceof KaufmannDigital.GDPR.CookieConsent:Mixin.GTMConsents]')->get();

        $configuredConsents = [];
        foreach ($nodesWithGtmConsents as $nodesWithGtmConsent) {
            $generalGTMConsents = $nodesWithGtmConsent->getProperty('generalGTMConsents');

            $additionalGTMConsents = preg_split('/\n/', $nodesWithGtmConsent->getProperty('additionalGTMConsents') ?? '');

            $configuredConsents = array_merge($configuredConsents, is_array($generalGTMConsents) ? $generalGTMConsents : [], is_array($additionalGTMConsents) ? $additionalGTMConsents : []);
        }

        return array_values(array_unique(array_filter($configuredConsents)));
    }

    public function getConsentsForNode(Node $node)
    {
        $generalGtmConsents = $node->getProperty('generalGTMConsents') ?? [];
        $additionalGtmConsents = preg_split('/\n/', $node->getProperty('additionalGTMConsents') ?? '');

        return array_values(array_unique(array_filter(array_merge($generalGtmConsents,$additionalGtmConsents))));
    }

    public function allowsCallOfMethod($methodName)
    {
        return true;
    }
}
