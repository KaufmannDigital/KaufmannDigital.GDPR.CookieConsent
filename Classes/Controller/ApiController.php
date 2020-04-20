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


    public function initializeAction()
    {
        parent::initializeAction();

        //TODO: Make CORS configurable in settings
        //$this->response->setComponentParameter(SetHeaderComponent::class, 'Access-Control-Allow-Origin', $this->request->getHttpRequest()->getHeader('Referer'));
        //$this->response->setComponentParameter(SetHeaderComponent::class, 'Access-Control-Allow-Credentials', 'true');
        $this->response->setComponentParameter(SetHeaderComponent::class, 'Vary', 'Origin');
    }


    public function renderCookieSettingsAction()
    {
        $q = new FlowQuery([$this->contextFactory->create()->getRootNode()]);
        $node = $q->find('[instanceof KaufmannDigital.GDPR.CookieConsent:Content.CookieSettings]')->get(0);

        $view = new FusionView();
        $view->setControllerContext($this->controllerContext);
        $view->setFusionPath('cookieConsentSettings');
        $view->assign('value', $node);
        $view->assign('node', $node);
        $this->view->assign('html', $view->render());


        $needsRenew = true;
        if (isset($this->request->getHttpRequest()->getCookieParams()['KD_GDPR_CC'])) {
            $cookie = json_decode($this->request->getHttpRequest()->getCookieParams()['KD_GDPR_CC']);
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
    public function trackChoiceAction(array $choice)
    {
        $userId = uniqid();
        foreach ($choice['consents'] as $consentIdentifier) {
            $consentLogEntry = new ConsentLogEntry($userId, new \DateTime($choice['consentDate']), $consentIdentifier);
            $this->consentLogRepository->add($consentLogEntry);
        }
    }
}
