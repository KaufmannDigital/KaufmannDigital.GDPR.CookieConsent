function loadCookiebannerHtml() {
    var xhr = new XMLHttpRequest();
    xhr.addEventListener('load', function() {
        var cookieBar = document.createElement('div');
        cookieBar.innerHTML = JSON.parse(xhr.responseText).html;
        document.body.appendChild(cookieBar);
        var scriptTags = cookieBar.getElementsByTagName('script');
        for (var n = 0; n < scriptTags.length; n++) {
            eval(scriptTags[n].innerHTML);
        }
        if (typeof initializeCookieConsent === 'function') {
            initializeCookieConsent();
        }
    });

    xhr.open('GET', KD_GDPR_CC.apiUrl);
    xhr.send();
}

if (document.cookie.indexOf(KD_GDPR_CC.cookieName) >= 0) {
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
    var cookieConsentDate = cookieObject.consentDates[KD_GDPR_CC.dimensionsIdentifier]
        ? new Date(cookieObject.consentDates[KD_GDPR_CC.dimensionsIdentifier])
        : new Date(cookieObject.consentDate);

    if (versionDate > cookieConsentDate && window.neos === undefined) {
        loadCookiebannerHtml();
    }

    if (!cookieObject.consents[KD_GDPR_CC.dimensionsIdentifier]) {
        loadCookiebannerHtml();
    }

    window.dataLayer.push({
        event: 'KD_GDPR_CC_consent',
        KD_GDPR_CC: {
            consents: cookieObject.consents,
        },
    });
} else if (document.getElementsByClassName('gdpr-cookieconsent-settings').length === 0 && window.neos === undefined) {
    /*No Cookie set, not in backend & not on cookie page*/
    loadCookiebannerHtml();
}

var links = document.querySelectorAll('a[href*=\"#GDPR-CC-open-settings\"]');
[].slice.call(links).forEach(function(link) {
    link.addEventListener('click', function(event) {
        event.preventDefault();
        loadCookiebannerHtml();
    });
});
