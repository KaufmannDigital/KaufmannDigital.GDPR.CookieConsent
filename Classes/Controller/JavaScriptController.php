<?php

namespace KaufmannDigital\GDPR\CookieConsent\Controller;

use GuzzleHttp\Psr7\Response;
use KaufmannDigital\GDPR\CookieConsent\Mvc\View\CustomTemplateView;
use Neos\Cache\Frontend\StringFrontend;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\Eel\FlowQuery\FlowQuery;
use Neos\Flow\Mvc\Controller\RestController;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Mvc\Exception\StopActionException;
use Neos\Neos\Fusion\Cache\CacheTag;
use Neos\Neos\Fusion\Helper\CachingHelper;


class JavaScriptController extends RestController
{

    /**
     * @var CustomTemplateView
     */
    protected $view;

    protected $defaultViewObjectName = CustomTemplateView::class;

    #[Flow\Inject]
    protected ContentRepositoryRegistry $contentRepositoryRegistry;

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
        $this->response->setHttpHeader('Cache-Control', 'max-age=0, private, must-revalidate');
    }


    /**
     * @param Node $site
     * @return void
     */
    public function renderJavaScriptAction(Node $site)
    {
        try {
            // dimensions that have configuration
            $filteredDimensions =
                array_filter(
                    $site->dimensionSpacePoint->coordinates,
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

            $cacheIdentifier = 'kd_gdpr_cc_' . sha1(implode('_', [
                    CacheTag::forNodeAggregateFromNode($site)->value,
                    json_encode($consents),
                    $dimensionIdentifier,
                ]));

            $q = new FlowQuery([$site]);

            if (isset($cookie['consentDates'][$dimensionIdentifier])) {
                $consentDate = new \DateTime($cookie['consentDates'][$dimensionIdentifier]);
            } elseif (isset($cookie['consentDate'])) {
                $consentDate = new \DateTime($cookie['consentDate']);
            } else {
                $consentDate = new \DateTime('now');
            }

            $cookieSettings = $q->find('[instanceof KaufmannDigital.GDPR.CookieConsent:Content.CookieSettings]')->get(0);

            if (!$cookieSettings instanceof Node) {
                throw new \Exception('No cookie settings could be found', 1634843525);
            }

            //Do not load anything, if decisionTtl (configured in Neos) is over
            //Neos should now render the CookieConsent again.
            $decisionTtl = $cookieSettings->getProperty('decisionTtl') ?? 0;
            $expireDate = clone $consentDate;
            $expireDate->add(\DateInterval::createFromDateString($decisionTtl . ' seconds'));
            if ($decisionTtl > 0 && $expireDate < new \DateTime('now')) {
                $this->response->addHttpHeader('Content-Type', 'text/javascript;charset=UTF8');
                $this->response->replaceHttpResponse(new Response());
                return;
            }

            if ($this->cache->has($cacheIdentifier)) {
                $this->redirect('downloadGeneratedJavaScript', null, null, ['hash' => $cacheIdentifier]);
                return;
            }

            $cookieNodes = $q->find('[instanceof KaufmannDigital.GDPR.CookieConsent:Content.Cookie][javaScriptCode != ""]')->sort('priority', 'DESC')->get();

            $javaScript = '';
            /** @var Node $cookieNode */
            foreach ($cookieNodes as $cookieNode) {
                $cookieJs = '';
                if (strlen($cookieNode->getProperty('identifier')) > 0 && in_array($cookieNode->getProperty('identifier'), $consents)) {
                    $cookieJs = preg_replace('/<script.*src=.*><\/script>/', 'document.head.appendChild(document.createRange().createContextualFragment(\'$0\'));', $cookieNode->getProperty('javaScriptCode'));
                    $cookieJs = preg_replace('/(<noscript>.*?<\/noscript>)/s', '', $cookieJs);
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
                    $this->cachingHelper->nodeTypeTag($cookieNode->nodeTypeName->value, $cookieNode)
                )
            );
            $this->redirect('downloadGeneratedJavaScript', null, null, ['hash' => $cacheIdentifier]);
            return;


        } catch (StopActionException $stopActionException) {
            throw $stopActionException;
        } catch (\Exception $e) {
            $this->response->addHttpHeader('Content-Type', 'text/javascript;charset=UTF8');
            $this->response->replaceHttpResponse(new Response());
        }
    }


    /**
     * Return rendered JS from cache
     * @param string $hash
     */
    public function downloadGeneratedJavaScriptAction(string $hash)
    {
        $this->response->addHttpHeader('Content-Type', 'text/javascript;charset=UTF8');

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
