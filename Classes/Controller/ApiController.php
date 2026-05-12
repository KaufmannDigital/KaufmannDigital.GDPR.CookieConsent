<?php

namespace KaufmannDigital\GDPR\CookieConsent\Controller;

use GuzzleHttp\Psr7\Response;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\Eel\FlowQuery\FlowQuery;
use Neos\Flow\Mvc\Controller\RestController;
use Neos\Neos\FrontendRouting\Exception\NodeNotFoundException;
use Neos\Neos\View\FusionView;
use Neos\Flow\Annotations as Flow;

class ApiController extends RestController
{
    /**
     * @Flow\InjectConfiguration(path="cookieName")
     * @var string
     */
    protected $cookieName;

    /**
     * @Flow\InjectConfiguration(path="headerConsent")
     * @var array
     */
    protected $headerConsent;

    /**
     * @var string
     * @Flow\InjectConfiguration(path="allowedOrigins")
     */
    protected $allowedOrigins;

    /**
     * @return array|string[]
     */
    public function getCORSHeaders(): array
    {
        $origin = $_SERVER['HTTP_ORIGIN'] ?? null;
        // Check if request is from an allowed origin
        if ($origin && in_array(parse_url($origin, PHP_URL_HOST), (array)$this->allowedOrigins)) {
            return [
                'Access-Control-Allow-Origin' => $origin,
                'Access-Control-Allow-Credentials' => 'true',
                'Access-Control-Allow-Headers' => 'Content-Type, Cookie, Credentials',
                'Vary' => 'Origin'
            ];
        }

        return [];
    }

    /**
     * @param Node|null $siteNode
     * @return void
     * @throws NodeNotFoundException
     * @throws \DateMalformedStringException
     * @throws \Neos\Eel\Exception|\JsonException
     */
    public function renderCookieSettingsAction(Node $siteNode = null): Response
    {
        if (!$siteNode instanceof Node) {
            throw new NodeNotFoundException('The given site was not found', 1644389565);
        }

        $responseBody = [];

        $q = new FlowQuery([$siteNode]);
        $node = $q->find('[instanceof KaufmannDigital.GDPR.CookieConsent:Content.CookieSettings]')->get(0);

        //Reply with empty string, if there is no configured CookieConsent
        if (!$node instanceof Node) {
            $responseBody['html'] = '';
            $responseBody['needsRenew'] = false;
            return new Response(
                headers: array_merge($this->getCORSHeaders(), ['Content-Type' => 'application/json']),
                body: json_encode($responseBody, JSON_THROW_ON_ERROR)
            );
        }

        // Generate HTML out of CookieSettings node
        $view = new FusionView();
        $view->setControllerContext($this->controllerContext);
        $view->setFusionPath('cookieConsentSettings');
        $view->assign('value', $node);
        $view->assign('node', $node);
        $view->assign('site', $siteNode);
        $responseBody['html'] = $view->render()->getContents();


        // Check if consents need renewal
        $needsRenew = true;
        if (isset($this->request->getHttpRequest()->getCookieParams()[$this->cookieName])) {
            $cookie = json_decode($this->request->getHttpRequest()->getCookieParams()[$this->cookieName]);
            $consentDate = new \DateTime($cookie->consentDate);
            $expireDate = new \DateTime($cookie->expireDate);

            $versionDate = $node->getProperty('versionDate');
            if ($consentDate > $versionDate && $expireDate > new \DateTime()) {
                $needsRenew = false;
            }
        }

        $responseBody['needsRenew'] = $needsRenew;
        $responseBody['headerConsent'] = [
            'acceptAll' => $this->matchHeaders($this->headerConsent['acceptAll']),
            'acceptNecessary' => $this->matchHeaders($this->headerConsent['acceptNecessary'])
        ];

        return new Response(
            headers: array_merge($this->getCORSHeaders(), ['Content-Type' => 'application/json']),
            body: json_encode($responseBody, JSON_THROW_ON_ERROR)
        );
    }

    /**
     * @return Response
     * @throws \JsonException
     */
    public function renderCookieSettingsOptionsAction(): Response
    {
        return new Response(headers: $this->getCORSHeaders(), body: json_encode([], JSON_THROW_ON_ERROR));
    }

    /**
     * @param array $matchers
     * @return bool
     */
    protected function matchHeaders(array $matchers): bool
    {
        foreach ($matchers as $headerName => $headerMatcher) {
            if (fnmatch(
                $headerMatcher,
                current($this->request->getHttpRequest()->getHeader($headerName))
            )) {
                return true;
            }
        }
        return false;
    }
}
