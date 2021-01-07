<?php

namespace KaufmannDigital\GDPR\CookieConsent\Controller;

use Neos\Cache\Frontend\StringFrontend;
use Neos\ContentRepository\Domain\Service\ContextFactoryInterface;
use Neos\Eel\FlowQuery\FlowQuery;
use Neos\Flow\Http\Component\SetHeaderComponent;
use Neos\Flow\Http\ContentStream;
use Neos\Flow\Mvc\Controller\RestController;
use Neos\Flow\Mvc\View\SimpleTemplateView;
use Neos\Flow\Annotations as Flow;
use Neos\Neos\Fusion\Helper\CachingHelper;

class JavaScriptController extends RestController
{

    /**
     * @var SimpleTemplateView
     */
    protected $view;

    protected $defaultViewObjectName = SimpleTemplateView::class;

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


    public function initializeRenderJavaScriptAction() {

        $this->response->setComponentParameter(SetHeaderComponent::class, 'Cache-Control', 'max-age=0, private, must-revalidate');
    }

    public function renderJavaScriptAction()
    {
        try {
            $cookie = json_decode($this->request->getHttpRequest()->getCookieParams()['KD_GDPR_CC']);
            $cacheIdentifier = 'KD_GDPR_CC_' . sha1(json_encode($cookie->consents));

            if ($this->cache->has($cacheIdentifier)) {
                $this->redirect('downloadGeneratedJavaScript', null, null, ['hash' => $cacheIdentifier]);
                return;
            }

            $q = new FlowQuery([$this->contextFactory->create()->getCurrentSiteNode()]);
            $cookieNodes = $q->find('[instanceof KaufmannDigital.GDPR.CookieConsent:Content.Cookie][javaScriptCode != ""]')->get();

            $javaScript = '';
            foreach ($cookieNodes as $cookieNode) {
                if (strlen($cookieNode->getProperty('identifier')) > 0 && in_array($cookieNode->getProperty('identifier'), $cookie->consents)) {
                    $javaScript .= $cookieNode->getProperty('javaScriptCode');
                }
            }

            $javaScript = $this->minifyJs($javaScript);

            //TODO: Add minifiy/uglify for JS here. I haven't found a good composer-lib while initial development.

            $this->cache->set(
                strtoupper($cacheIdentifier),
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
            $this->response->setContent('');
        }
    }


    /**
     * @param string $hash
     */
    public function downloadGeneratedJavaScriptAction(string $hash)
    {
        $this->response->setComponentParameter(SetHeaderComponent::class, 'Content-Type', 'text/javascript;charset=UTF-8');

        $hash = strtoupper($hash);

        if ($this->cache->has($hash)) {
            die($this->cache->get($hash));
            $this->response->setContent($this->cache->get($hash));
            return;
        }

        $this->response->setContent('');
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
                '#([a-z0-9_\)\]])\[([\'"])([a-z_][a-z0-9_]*)\2\]#i'
            ),
            array(
                '$1',
                '$1$2',
                '}',
                '$1$3',
                '$1.$3'
            ),
            $input);
    }
}
