<?php

namespace KaufmannDigital\GDPR\CookieConsent\Controller;

use KaufmannDigital\GDPR\CookieConsent\Mvc\View\CustomTemplateView;
use Neos\Cache\Frontend\StringFrontend;
use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\ContentRepository\Domain\Service\ContextFactoryInterface;
use Neos\Eel\FlowQuery\FlowQuery;
use Neos\Flow\Http\Component\SetHeaderComponent;
use Neos\Flow\Mvc\Controller\RestController;
use Neos\Flow\Annotations as Flow;
use Neos\Neos\Fusion\Helper\CachingHelper;


class JavaScriptController extends RestController
{

    /**
     * @var CustomTemplateView
     */
    protected $view;

    protected $defaultViewObjectName = CustomTemplateView::class;

    /**
     * @Flow\Inject
     * @var ContextFactoryInterface
     */
    protected $contextFactory;

    /**
     * @var StringFrontend
     * @Flow\Inject
     */
    protected $cache;

    /**
     * @Flow\Inject
     * @var CachingHelper
     */
    protected $cachingHelper;

    /**
     * @Flow\InjectConfiguration(path="consentDimensions")
     * @var string[]
     */
    protected $consentDimensions;

    /**
     * @Flow\InjectConfiguration(path="cookieName")
     * @var string
     */
    protected $cookieName;

    public function initializeRenderJavaScriptAction()
    {
        $this->response->setComponentParameter(SetHeaderComponent::class, 'Cache-Control', 'max-age=0, private, must-revalidate');
    }


    /**
     * Concatinate scripts of cookies and write to cache
     * @param array $dimensions
     * @return void
     */
    public function renderJavaScriptAction(array $dimensions = [])
    {
        try {
            // dimensions that have configuration
            $filteredDimensions =
                array_filter(
                    $dimensions,
                    function ($key) {
                        return in_array($key, $this->consentDimensions);
                    },
                    ARRAY_FILTER_USE_KEY
                );

            // turn configured dimensions into a identifier like "deu_de"
            $dimensionIdentifier = implode(
                '_',
                array_map(function ($dimension) { return current($dimension);}, $filteredDimensions)
            );

            $cookie = !empty($this->request->getHttpRequest()->getCookieParams()) && isset($this->request->getHttpRequest()->getCookieParams()[$this->cookieName]) ? json_decode($this->request->getHttpRequest()->getCookieParams()[$this->cookieName], true) : null;
            $consents = $cookie['consents'][$dimensionIdentifier] ?? $cookie['consents']['default'] ?? $cookie['consents'] ?? [];
            $siteNode = $this->contextFactory->create(['dimensions' => $dimensions])->getCurrentSiteNode();

            $cacheIdentifier = 'kd_gdpr_cc_' . sha1(json_encode($consents) . $dimensionIdentifier . $siteNode->getIdentifier());

            $q = new FlowQuery([$siteNode]);

            if (isset($cookie['consentDates'][$dimensionIdentifier])) {
                $consentDate = new \DateTime($cookie['consentDates'][$dimensionIdentifier]);
            } elseif (isset($cookie['consentDate'])) {
                $consentDate = new \DateTime($cookie['consentDate']);
            } else {
                $consentDate = new \DateTime('now');
            }

            $cookieSettings = $q->find('[instanceof KaufmannDigital.GDPR.CookieConsent:Content.CookieSettings]')->get(0);

            if (!$cookieSettings instanceof NodeInterface) {
                throw new \Exception('No cookie settings could be found', 1634843525);
            }

            $decisionTtl = $cookieSettings->getProperty('decisionTtl') ?? 0;
            $expireDate = clone $consentDate;
            $expireDate->add(\DateInterval::createFromDateString($decisionTtl . ' seconds'));
            if ($decisionTtl > 0 && $expireDate < new \DateTime('now')) {
                $this->response->setContentType('text/javascript');
                $this->response->setContent('');
                return;
            }

            if ($this->cache->has($cacheIdentifier)) {
                $this->redirect('downloadGeneratedJavaScript', null, null, ['hash' => $cacheIdentifier]);
                return;
            }

            $cookieNodes = $q->find('[instanceof KaufmannDigital.GDPR.CookieConsent:Content.Cookie][javaScriptCode != ""]')->sort('priority', 'DESC')->get();

            $javaScript = '';
            foreach ($cookieNodes as $cookieNode) {
                $cookieJs = '';
                if (strlen($cookieNode->getProperty('identifier')) > 0 && in_array($cookieNode->getProperty('identifier'), $consents)) {
                    $cookieJs = preg_replace('/<script.*src=.*><\/script>/', 'document.head.appendChild(document.createRange().createContextualFragment(\'$0\'));', $cookieNode->getProperty('javaScriptCode'));
                    $cookieJs = preg_replace('/<script>((.*\s.*)*)<\/script>/', '$1', $cookieJs);
                }
                $javaScript .= $cookieJs;
            }
            $javaScript = $this->minifyJs($javaScript);

            $this->cache->set(
                $cacheIdentifier,
                $javaScript,
                array_merge(
                    $this->cachingHelper->nodeTag($cookieNodes),
                    $this->cachingHelper->descendantOfTag($cookieNodes),
                    $this->cachingHelper->nodeTypeTag($cookieNodes)
                )
            );
            $this->redirect('downloadGeneratedJavaScript', null, null, ['hash' => $cacheIdentifier]);
            return;


        } catch (\Exception $e) {
            $this->response->setContentType('text/javascript');
            $this->response->setContent('');
        }
    }


    /**
     * Return rendered JS from cache
     * @param string $hash
     */
    public function downloadGeneratedJavaScriptAction(string $hash)
    {
        $this->response->setContentType('text/javascript');


        if ($this->cache->has($hash) !== false) {
            $this->view->setOption('templateSource', $this->cache->get($hash));
            return;
        }

        $this->view->setOption('templateSource', '');
    }

    protected function minifyJs($input) {
        if(trim($input) === "") return $input;
        return preg_replace(
            array(
                // Remove comment(s)
                '#\s*("(?:[^"\\\]++|\\\.)*+"|\'(?:[^\'\\\\]++|\\\.)*+\')\s*|\s*\/\*(?!\!|@cc_on)(?>[\s\S]*?\*\/)\s*|\s*(?<![\:\=])\/\/.*(?=[\n\r]|$)|^\s*|\s*$#',
                // Remove white-space(s) outside the string and regex
                '#("(?:[^"\\\]++|\\\.)*+"|\'(?:[^\'\\\\]++|\\\.)*+\'|\/\*(?>.*?\*\/)|\/(?!\/)[^\n\r]*?\/(?=[\s.,;]|[gimuy]|$))|\s*([!%&*\(\)\-=+\[\]\{\}|;:,.<>?\/])\s*#s',
                // Remove the last semicolon
                '#;+\}#',
                // Minify object attribute(s) except JSON attribute(s). From `{'foo':'bar'}` to `{foo:'bar'}`
                '#([\{,])([\'])(\d+|[a-z_][a-z0-9_]*)\2(?=\:)#i',
                // --ibid. From `foo['bar']` to `foo.bar`
                '#([a-z0-9_\)\]])\[([\'"])([a-z_][a-z0-9_]*)\2\]#i',
                //Remove comments
                '#<!--.*-->#'
            ),
            array(
                '$1',
                '$1$2',
                '}',
                '$1$3',
                '$1.$3',
                ''
            ),
            $input);
    }
}
