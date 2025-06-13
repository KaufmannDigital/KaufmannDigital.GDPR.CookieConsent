<?php

namespace KaufmannDigital\GDPR\CookieConsent\Controller;

use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\ContentRepository\Domain\Service\ContextFactoryInterface;
use Neos\Eel\FlowQuery\FlowQuery;

use Neos\Flow\Mvc\Controller\RestController;
use Neos\Flow\Mvc\View\JsonView;
use Neos\Neos\Controller\Exception\NodeNotFoundException;
use Neos\Neos\View\FusionView;
use Neos\Flow\Annotations as Flow;

class ApiController extends RestController
{

    /**
     * @var JsonView
     */
    protected $view;

    protected $defaultViewObjectName = JsonView::class;

    /**
     * @Flow\Inject
     * @var ContextFactoryInterface
     */
    protected $contextFactory;

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

    public function initializeAction()
    {
        parent::initializeAction();
        $this->handleCorsHeaders();
    }

    /**
     * @return void
     */
    private function handleCorsHeaders(): void
    {
        $origin = $_SERVER['HTTP_ORIGIN'] ?? null;
        // Check if request is from an allowed origin
        if ( $origin && in_array(parse_url($origin, PHP_URL_HOST), (array)$this->allowedOrigins)) {
            $this->setCORSHeaders($origin);
        }
    }

    /**
     * @param string $origin
     * @return void
     */
    private function setCORSHeaders(string $origin): void
    {
        if (method_exists($this->response, 'setHttpHeader')) {
            $this->response->setHttpHeader('Access-Control-Allow-Origin', $origin);
            $this->response->setHttpHeader('Access-Control-Allow-Credentials', 'true');
            $this->response->setHttpHeader('Access-Control-Allow-Headers', 'Content-Type, Cookie, Credentials');
            $this->response->setHttpHeader('Vary', 'Origin');
        } else {
            $this->response->setHttpHeader('Access-Control-Allow-Origin', $origin);
            $this->response->setHttpHeader('Access-Control-Allow-Credentials', 'true');
            $this->response->setHttpHeader('Access-Control-Allow-Headers', 'Content-Type, Cookie, Credentials');
            $this->response->setHttpHeader('Vary', 'Origin');
        }
    }

    /**
     * @param NodeInterface|null $siteNode The current SiteNode for multisite support
     * @return void
     * @throws NodeNotFoundException
     * @throws \Neos\ContentRepository\Exception\NodeException
     * @throws \Neos\Eel\Exception
     *
     * Search for CookieSettings node, render and output its HTML and if consent needs renewal
     */
    public function renderCookieSettingsAction(NodeInterface $siteNode = null)
    {
        if (!$siteNode instanceof NodeInterface) {
            throw new NodeNotFoundException('The given site was not found', 1644389565);
        }

        $this->view->setVariablesToRender(['html', 'needsRenew']);

        $q = new FlowQuery([$siteNode]);
        $node = $q->find('[instanceof KaufmannDigital.GDPR.CookieConsent:Content.CookieSettings]')->get(0);

        //Reply with empty string, if there is no configured CookieConsent
        if (!$node instanceof NodeInterface || !$node->getParent() instanceof NodeInterface) {
            $this->view->assign('html', '');
            $this->view->assign('needsRenew', false);
            return;
        }

        // Generate HTML out of CookieSettings node
        $view = new FusionView();
        $view->setControllerContext($this->controllerContext);
        $view->setFusionPath('cookieConsentSettings');
        $view->assign('value', $node);
        $view->assign('node', $node);
        $view->assign('site', $siteNode);
        $this->view->assign('html', $view->render());


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

        $this->view->assign('needsRenew', $needsRenew);

        $this->view->assign('headerConsent',
            [
                'acceptAll' => $this->matchHeaders($this->headerConsent['acceptAll']),
                'acceptNecessary' => $this->matchHeaders($this->headerConsent['acceptNecessary'])
            ]
        );


        $this->view->setVariablesToRender(['html', 'needsRenew', 'headerConsent']);
    }

    public function renderCookieSettingsOptionsAction() {

    }

    /**
     * @param array $matchers
     * @return bool
     */
    protected function matchHeaders(array $matchers) {
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
