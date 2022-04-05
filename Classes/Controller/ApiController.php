<?php

namespace KaufmannDigital\GDPR\CookieConsent\Controller;

use KaufmannDigital\GDPR\CookieConsent\Domain\Model\ConsentLogEntry;
use KaufmannDigital\GDPR\CookieConsent\Domain\Repository\ConsentLogEntryRepository;
use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\ContentRepository\Domain\Service\ContextFactoryInterface;
use Neos\Eel\FlowQuery\FlowQuery;
use Neos\Flow\Http\Component\SetHeaderComponent;
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
     * @Flow\Inject
     * @var ConsentLogEntryRepository
     */
    protected $consentLogRepository;

    /**
     * @Flow\InjectConfiguration(path="cookieName")
     * @var string
     */
    protected $cookieName;


    public function initializeAction()
    {
        parent::initializeAction();
        #TODO: Make configurable, add if clause for neos versions (Min 5.3 LTS)
        $this->response->setComponentParameter(SetHeaderComponent::class, 'Access-Control-Allow-Origin', current($this->request->getHttpRequest()->getHeader('Origin')));
        $this->response->setComponentParameter(SetHeaderComponent::class, 'Access-Control-Allow-Credentials', 'true');
        $this->response->setComponentParameter(SetHeaderComponent::class, 'Access-Control-Allow-Headers', 'Content-Type, Cookie, Credentials');
        $this->response->setComponentParameter(SetHeaderComponent::class, 'Vary', 'Origin');
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


        $this->view->setVariablesToRender(['html', 'needsRenew']);
    }

    public function renderCookieSettingsOptionsAction() {

    }
}
