function loadCookiebannerHtml(openSettings, showImmediately, openedManually)
{
    if (document.body.classList.contains('neos-backend')) return;

    var xhr = new XMLHttpRequest();
    xhr.addEventListener('load', function() {
        var cookieBar = document.createElement('div');
        let response = JSON.parse(xhr.responseText);
        cookieBar.innerHTML = response.html;
        let autoAccept = 'none';
        if (response.headerConsent.acceptNecessary === true) autoAccept = 'necessary';
        if (response.headerConsent.acceptAll === true) autoAccept = 'all';

        const parameterAccept = KD_GDPR_CC.acceptConfiguration.parameterAccept;
        const acceptNecessaryParams = Object.getOwnPropertyNames(parameterAccept.acceptNecessary);
        if (acceptNecessaryParams.length > 0) {
            [].slice.call(acceptNecessaryParams).forEach(function (parameterName) {
                const acceptAllValue = parameterAccept.acceptNecessary[parameterName];
                if (getUrlParameter(parameterName) === acceptAllValue) {
                    autoAccept = 'necessary';
                }
            });
        }

        const acceptAllParams = Object.getOwnPropertyNames(parameterAccept.acceptAll);
        if (acceptAllParams.length > 0) {
            [].slice.call(acceptAllParams).forEach(function (parameterName) {
                const acceptAllValue = parameterAccept.acceptAll[parameterName];
                if (getUrlParameter(parameterName) === acceptAllValue) {
                    autoAccept = 'all';
                }
            });
        }
        console.log(autoAccept);

        if (showImmediately === false && KD_GDPR_CC.hideBeforeInteraction) {
            window.addEventListener(
                'scroll',
                function () {
                    appendHtmlAndInitialize(cookieBar.firstChild, autoAccept);
                    },
                {
                    passive: true,
                    once: true
                }
            );
        } else {
            appendHtmlAndInitialize(cookieBar.firstChild,  autoAccept);
        }
    });

    xhr.open('GET', KD_GDPR_CC.apiUrl);
    xhr.send();

    function appendHtmlAndInitialize(cookieBar, autoAccept) {
        document.body.appendChild(cookieBar);
        var scriptTags = cookieBar.getElementsByTagName('script');
        for (var n = 0; n < scriptTags.length; n++) {
            eval(scriptTags[n].innerHTML);
        }
        if (typeof initializeCookieConsent === 'function') {
            initializeCookieConsent(openSettings, openedManually, autoAccept);
        }
    }
}

function getUrlParameter(parameterName) {
        parameterName = parameterName.replace(/[\[]/,"\\\[").replace(/[\]]/,"\\\]");
        var regexS = "[\\?&]"+parameterName+"=([^&#]*)";
        var regex = new RegExp(regexS);
        var results = regex.exec(window.location.href);
        return results == null ? null : results[1];
}


if (typeof KD_GDPR_CC !== 'undefined' && KD_GDPR_CC.documentNodeDisabled === false && document.cookie.indexOf(KD_GDPR_CC.cookieName) >= 0) {
    /*Cookie set*/
    window.dataLayer = window.dataLayer || [];
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
        //Re-Open Cookie-Consent, if TTL is expired
        loadCookiebannerHtml(true, false, false);
    }


    window.dataLayer.push({
        event: 'KD_GDPR_CC_consent',
        KD_GDPR_CC: {
            consents: cookieObject.consents,
        },
    });
} else if (typeof KD_GDPR_CC !== 'undefined' && KD_GDPR_CC.documentNodeDisabled === false && document.getElementsByClassName('gdpr-cookieconsent-settings').length === 0 && window.neos === undefined) {
    /*No Cookie set, not in backend & not on cookie page*/
    loadCookiebannerHtml(false, false, false);
}

var links = document.querySelectorAll('a[href*=\"#GDPR-CC-open-settings\"]');
[].slice.call(links).forEach(function(link) {
    link.addEventListener('click', function(event) {
        event.preventDefault();
        loadCookiebannerHtml(true, true, true);
    });
});
