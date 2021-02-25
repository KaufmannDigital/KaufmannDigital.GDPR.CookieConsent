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
        #TODO: Make configurable
        $this->response->setComponentParameter(SetHeaderComponent::class, 'Access-Control-Allow-Origin', current($this->request->getHttpRequest()->getHeader('Origin')));
        $this->response->setComponentParameter(SetHeaderComponent::class, 'Access-Control-Allow-Credentials', 'true');
        $this->response->setComponentParameter(SetHeaderComponent::class, 'Access-Control-Allow-Headers', 'Content-Type, Cookie, Credentials');
        $this->response->setComponentParameter(SetHeaderComponent::class, 'Vary', 'Origin');
    }

    /**
     * @param NodeInterface $siteNode
     * @throws \Neos\ContentRepository\Exception\NodeException
     * @throws \Neos\Eel\Exception
     */
    public function renderCookieSettingsAction(NodeInterface $siteNode)
    {
        $this->view->setVariablesToRender(['html', 'needsRenew']);

        $q = new FlowQuery([$siteNode]);
        $node = $q->find('[instanceof KaufmannDigital.GDPR.CookieConsent:Content.CookieSettings]')->get(0);

        //Reply with empty string, if there is no configured CookieConsent
        if (!$node instanceof NodeInterface || !$node->getParent() instanceof NodeInterface) {
            $this->view->assign('html', '');
            $this->view->assign('needsRenew', false);
            return;
        }

        $view = new FusionView();
        $view->setControllerContext($this->controllerContext);
        $view->setFusionPath('cookieConsentSettings');
        $view->assign('value', $node);
        $view->assign('node', $node);
        $view->assign('site', $siteNode);
        $this->view->assign('html', $view->render());


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

    /**
     * @param array $choice
     * @throws \Neos\Flow\Persistence\Exception\IllegalObjectTypeException
     */
    public function trackChoiceAction(array $choice = [])
    {
        $userId = $choice['userId'] ?? '';
        $userAgent = current($this->request->getHttpRequest()->getHeader('User-Agent'));
        if (empty($userId)) {
            $userId = uniqid();
            $consentLogEntry = new ConsentLogEntry($userId, new \DateTime(), '#init#', $userAgent);
            $this->consentLogRepository->add($consentLogEntry);
        } else {
            /** @var ConsentLogEntry $logEntry */
            $logEntry = $this->consentLogRepository->findOneByUserId($userId);
            if ($logEntry instanceof ConsentLogEntry) {
                $this->consentLogRepository->remove($logEntry);
            }
            foreach ($choice['consents'] as $consentIdentifier) {
                $consentLogEntry = new ConsentLogEntry($userId, new \DateTime($choice['consentDate']), $consentIdentifier, $userAgent);
                $this->consentLogRepository->add($consentLogEntry);
            }
        }

        $this->view->assign('success', true);
        $this->view->assign('userId', $userId);
        $this->view->setVariablesToRender(['success', 'userId']);
    }
}
