(function () {
    // ---------------- DOM readiness queue ----------------
    var domReadyResolved = false;
    var domReadyQueue = [];

    function onDomReady(fn) {
        if (domReadyResolved || document.readyState !== 'loading') {
            domReadyResolved = true;
            try {
                fn();
            } catch (e) {
                console.error(e);
            }
            return;
        }
        domReadyQueue.push(fn);
    }

    function flushDomReadyQueue() {
        domReadyResolved = true;
        var q = domReadyQueue.slice();
        domReadyQueue.length = 0;
        for (var i = 0; i < q.length; i++) {
            try {
                q[i]();
            } catch (e) {
                console.error(e);
            }
        }
    }

    if (document.readyState !== 'loading') {
        domReadyResolved = true;
    } else {
        document.addEventListener('DOMContentLoaded', flushDomReadyQueue, {once: true});
    }

    // ---------------- Utils ----------------
    function getUrlParameter(parameterName) {
        parameterName = parameterName.replace(/[\[]/, "\\\[").replace(/[\]]/, "\\\]");
        var regexS = "[\\?&]" + parameterName + "=([^&#]*)";
        var regex = new RegExp(regexS);
        var results = regex.exec(window.location.href);
        return results == null ? null : results[1];
    }

    function pushDataLayer(obj) {
        window.dataLayer = window.dataLayer || [];
        window.dataLayer.push(obj);
    }

    // ---------------- Prefetch cache ----------------
    // One request per page load (idempotent).
    var bannerJsonPromise = null;

    function prefetchCookieBannerJson() {
        if (bannerJsonPromise) return bannerJsonPromise;
        if (typeof KD_GDPR_CC === 'undefined' || !KD_GDPR_CC.apiUrl) {
            // Cannot prefetch yet; return a promise that rejects so callers can handle
            bannerJsonPromise = Promise.reject(new Error('KD_GDPR_CC.apiUrl not available'));
            return bannerJsonPromise;
        }

        var url = KD_GDPR_CC.apiUrl;

        bannerJsonPromise = new Promise(function (resolve, reject) {
            if (typeof window.fetch === 'function') {
                fetch(url, {
                    method: 'GET',
                    credentials: 'same-origin',
                    cache: 'no-store',
                    priority: 'high' // best-effort
                })
                    .then(function (res) {
                        if (!res.ok) throw new Error('Cookie banner request failed: ' + res.status);
                        return res.json();
                    })
                    .then(resolve)
                    .catch(function () {
                        // fallback to XHR if fetch fails
                        loadViaXhr(url, resolve, reject);
                    });
            } else {
                loadViaXhr(url, resolve, reject);
            }

            function loadViaXhr(u, ok, fail) {
                var xhr = new XMLHttpRequest();
                xhr.addEventListener('load', function () {
                    try {
                        ok(JSON.parse(xhr.responseText));
                    } catch (e) {
                        fail(e);
                    }
                });
                xhr.addEventListener('error', function () {
                    fail(new Error('XHR failed'));
                });
                xhr.open('GET', u);
                xhr.send();
            }
        });

        return bannerJsonPromise;
    }

    // ---------------- Banner injection (DOM-queued) ----------------
    function loadCookiebannerHtml(openSettings, showImmediately, openedManually) {
        if (window.neos !== undefined) return;

        // Ensure request is started immediately (or already in-flight)
        prefetchCookieBannerJson()
            .then(function (response) {
                // Non-DOM compute now
                var cookieBarContainer = document.createElement('div');
                cookieBarContainer.innerHTML = response.html;

                var autoAccept = 'none';
                if (response.headerConsent && response.headerConsent.acceptNecessary === true) autoAccept = 'necessary';
                if (response.headerConsent && response.headerConsent.acceptAll === true) autoAccept = 'all';

                var parameterAccept = KD_GDPR_CC.acceptConfiguration.parameterAccept;

                var acceptNecessaryParams = Object.getOwnPropertyNames(parameterAccept.acceptNecessary || {});
                if (acceptNecessaryParams.length > 0) {
                    acceptNecessaryParams.forEach(function (parameterName) {
                        var v = parameterAccept.acceptNecessary[parameterName];
                        if (getUrlParameter(parameterName) === v) autoAccept = 'necessary';
                    });
                }

                var acceptAllParams = Object.getOwnPropertyNames(parameterAccept.acceptAll || {});
                if (acceptAllParams.length > 0) {
                    acceptAllParams.forEach(function (parameterName) {
                        var v = parameterAccept.acceptAll[parameterName];
                        if (getUrlParameter(parameterName) === v) autoAccept = 'all';
                    });
                }

                // Queue DOM work
                var doAppend = function () {
                    appendHtmlAndInitialize(cookieBarContainer.firstChild, autoAccept);
                };

                if (showImmediately === false && KD_GDPR_CC.hideBeforeInteraction) {
                    window.addEventListener(
                        'scroll',
                        function () {
                            onDomReady(doAppend);
                        },
                        {passive: true, once: true}
                    );
                } else {
                    onDomReady(doAppend);
                }

                function appendHtmlAndInitialize(cookieBar, autoAcceptFinal) {
                    document.body.appendChild(cookieBar);

                    var scriptTags = cookieBar.getElementsByTagName('script');
                    for (var n = 0; n < scriptTags.length; n++) {
                        eval(scriptTags[n].innerHTML);
                    }

                    if (typeof initializeCookieConsent === 'function') {
                        initializeCookieConsent(openSettings, openedManually, autoAcceptFinal);
                    }
                }
            })
            .catch(function (e) {
                console.error('Cookie banner prefetch/inject failed', e);
            });
    }

    // ---------------- Click handling (delegation) ----------------
    document.addEventListener(
        'click',
        function (event) {
            var target = event.target;
            if (!target) return;

            var link = (typeof target.closest === 'function')
                ? target.closest('a[href*="#GDPR-CC-open-settings"]')
                : null;

            if (!link) return;

            event.preventDefault();
            if (typeof KD_GDPR_CC === 'undefined') return;
            if (KD_GDPR_CC.documentNodeDisabled === true) return;

            loadCookiebannerHtml(true, true, true);
        },
        true
    );

    // ---------------- Auto-run logic + EARLY PREFETCH ----------------
    if (typeof KD_GDPR_CC !== 'undefined' && KD_GDPR_CC.documentNodeDisabled === false) {
        // Start loading immediately no matter what (your requested behavior)
        prefetchCookieBannerJson();

        var hasCookie = document.cookie.indexOf(KD_GDPR_CC.cookieName) >= 0;

        if (hasCookie) {
            var cookieObject = JSON.parse(
                decodeURIComponent(
                    document.cookie
                        .substr(
                            document.cookie.indexOf(KD_GDPR_CC.cookieName) + KD_GDPR_CC.cookieName.length + 1
                        )
                        .split('; ')[0]
                )
            );

            var versionDate = new Date(KD_GDPR_CC.versionTimestamp);
            var cookieConsentDate = cookieObject.consentDates && cookieObject.consentDates[KD_GDPR_CC.dimensionsIdentifier]
                ? new Date(cookieObject.consentDates[KD_GDPR_CC.dimensionsIdentifier])
                : new Date(cookieObject.consentDate);
            var decisionExpiry = cookieConsentDate.getTime() + KD_GDPR_CC.decisionTtl;

            if (versionDate > cookieConsentDate && window.neos === undefined) {
                loadCookiebannerHtml(false, false, false);
            } else if (!Array.isArray(cookieObject.consents) && !cookieObject.consents[KD_GDPR_CC.dimensionsIdentifier]) {
                loadCookiebannerHtml(false, false, false);
            } else if (KD_GDPR_CC.decisionTtl > 0 && decisionExpiry < new Date()) {
                loadCookiebannerHtml(true, false, false);
            }

            pushDataLayer({
                event: 'KD_GDPR_CC_consent',
                KD_GDPR_CC: {consents: cookieObject.consents}
            });

            if (cookieObject.gtmConsents) {
                if (typeof window.gtag === 'function') {
                    window.gtag(
                        'consent',
                        'update',
                        (cookieObject.gtmConsents[KD_GDPR_CC.dimensionsIdentifier] ?? cookieObject.gtmConsents)
                    );
                }
                pushDataLayer({event: 'gtm.init_consent'});
            }
        } else if (window.neos === undefined) {
            // DOM-dependent check must be deferred
            onDomReady(function () {
                if (document.getElementsByClassName('gdpr-cookieconsent-settings').length === 0) {
                    loadCookiebannerHtml(false, false, false);
                }
            });
        }
    }

    // Backend: keep safe init
    if (window.neos) {
        onDomReady(function () {
            if (typeof initializeCookieConsent === 'function') {
                initializeCookieConsent(false, false);
            }
        });
    }

    // Optional globals if other scripts depend on them
    window.loadCookiebannerHtml = loadCookiebannerHtml;
    window.getUrlParameter = getUrlParameter;
    window.KD_GDPR_CC_prefetchCookieBannerJson = prefetchCookieBannerJson;
})();
