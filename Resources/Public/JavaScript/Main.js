//Polyfill remove() method
(function (arr) {
    arr.forEach(function (item) {
        if (item.hasOwnProperty('remove')) {
            return;
        }
        Object.defineProperty(item, 'remove', {
            configurable: true,
            enumerable: true,
            writable: true,
            value: function remove() {
                if (this.parentNode === null) {
                    return;
                }
                this.parentNode.removeChild(this);
            }
        });
    });
})([Element.prototype, CharacterData.prototype, DocumentType.prototype]);

function initializeCookieConsent(openSettings, openedManually) {

    var kd_gdpr_cc_userid;
    var cookieSettingsContainer = document.querySelector('.gdpr-cookieconsent-container');
    var txtMainDescription = document.querySelector('.gdpr-cookieconsent-settings__content__info__description--main');
    var txtIndividualSettingsDescription = document.querySelector('.gdpr-cookieconsent-settings__content__info__description--settings');
    var individualSettingsContainer = document.querySelector('.gdpr-cookieconsent-settings__content__settings');
    var btnIndividualSettingsEnable = document.querySelector('#gdpr-cc-btn-individual-settings-enable');
    var btnIndividualSettingsDisable = document.querySelector('#gdpr-cc-btn-individual-settings-disable');
    var btnAcceptAll = document.querySelector('#gdpr-cc-btn-accept');
    var btnSaveSettings = document.querySelector('#gdpr-cc-btn-save');
    var btnAcceptNecessaryCookies = document.querySelector('#gdpr-cc-btn-accept-necessary');
    var btnCloseCookieSettings = document.querySelector('#kd_gdpr_cc-close');


    btnIndividualSettingsEnable.addEventListener('click', function() {
        individualSettingsContainer.classList.remove('hidden');
        txtMainDescription.classList.add('hidden');
        txtIndividualSettingsDescription.classList.remove('hidden');
        btnIndividualSettingsEnable.classList.add('hidden');
        btnIndividualSettingsDisable.classList.remove('hidden');
        if(btnAcceptNecessaryCookies) btnAcceptNecessaryCookies.classList.add('hidden');
        btnAcceptAll.classList.add('hidden');
        btnSaveSettings.classList.remove('hidden');
    });

    btnIndividualSettingsDisable.addEventListener('click', function() {
        individualSettingsContainer.classList.add('hidden');
        txtMainDescription.classList.remove('hidden');
        txtIndividualSettingsDescription.classList.add('hidden');
        btnIndividualSettingsEnable.classList.remove('hidden');
        btnIndividualSettingsDisable.classList.add('hidden');
        if(btnAcceptNecessaryCookies) btnAcceptNecessaryCookies.classList.remove('hidden');
        btnAcceptAll.classList.remove('hidden');
        btnSaveSettings.classList.add('hidden');
    });

    btnAcceptAll.addEventListener('click', function() {
        var selectedInputs = document.querySelectorAll('.gdpr-cookieconsent-switch input'); //Select all inputs

        //Save to cookie
        saveConsentToCookie(selectedInputs, kd_gdpr_cc_userid);

        //Dispatch events
        dispatchEventsForCookies(selectedInputs);

        //Remove CookieConsent from HTML
        cookieSettingsContainer.remove();
        loadGeneratedJavaScript();
    });

    btnSaveSettings.addEventListener('click', function() {
        var selectedInputs = document.querySelectorAll('.gdpr-cookieconsent-switch input:checked');

        //Save to cookie
        saveConsentToCookie(selectedInputs, kd_gdpr_cc_userid);

        //Dispatch events
        dispatchEventsForCookies(selectedInputs);

        //Remove CookieConsent from HTML
        cookieSettingsContainer.remove();
        loadGeneratedJavaScript();
    });

    if (btnAcceptNecessaryCookies) {
        btnAcceptNecessaryCookies.addEventListener('click', function() {
            acceptNecessaryCookies(kd_gdpr_cc_userid);
            //Remove CookieConsent from HTML
            cookieSettingsContainer.remove();
            loadGeneratedJavaScript();
        });
    }

    if (btnCloseCookieSettings) {
        btnCloseCookieSettings.addEventListener('click', function (e) {
            e.preventDefault();
            cookieSettingsContainer.remove();
        });
    }

    [].slice.call(document.querySelectorAll('.gdpr-cookieconsent-setting-group__details--open')).forEach(function(detailsLink) {
        detailsLink.addEventListener('click', function(e) {
            e.preventDefault();
            detailsLink.parentElement.querySelector('.gdpr-cookieconsent-setting-group__switch input').focus()
            detailsLink.classList.add('hidden');
            detailsLink.parentElement.querySelector('.gdpr-cookieconsent-setting-group__cookies').classList.remove('hidden');
            detailsLink.parentElement.querySelector('.gdpr-cookieconsent-setting-group__details--close').classList.remove('hidden');
        });
    });

    [].slice.call(document.querySelectorAll('.gdpr-cookieconsent-setting-group__details--close')).forEach(function(detailsLink) {
        detailsLink.addEventListener('click', function(e) {
            e.preventDefault();
            detailsLink.classList.add('hidden');
            detailsLink.parentElement.querySelector('.gdpr-cookieconsent-setting-group__cookies').classList.add('hidden');
            detailsLink.parentElement.querySelector('.gdpr-cookieconsent-setting-group__details--open').classList.remove('hidden');
        });
    });

    [].slice.call(document.querySelectorAll('.gdpr-cookieconsent-setting-group__switch .gdpr-cookieconsent-switch > input')).forEach(function(groupCheckbox) {
        groupCheckbox.addEventListener('change', function(event) {
            var cookies = event.target.parentElement.parentElement.parentElement.querySelector('.gdpr-cookieconsent-setting-group__cookies').children;
            [].slice.call(cookies).forEach(function(cookie) {
                cookie.querySelector('.gdpr-cookieconsent-switch > input').checked = groupCheckbox.checked;
            });
        })
    });

    [].slice.call(document.querySelectorAll('.gdpr-cookieconsent-cookie__switch .gdpr-cookieconsent-switch > input')).forEach(function(cookieCheckbox) {
        cookieCheckbox.addEventListener('change', function() {
            var groupCheckbox = cookieCheckbox.parentElement.parentElement.parentElement.parentElement.parentElement.querySelector('.gdpr-cookieconsent-setting-group__switch .gdpr-cookieconsent-switch > input');
            if (cookieCheckbox.checked === true && groupCheckbox.checked === false) {
                groupCheckbox.checked = true;
            }

            if (cookieCheckbox.checked === false) {
                var activeCheckboxInGroupCount = cookieCheckbox.parentElement.parentElement.parentElement.parentElement.querySelectorAll('.gdpr-cookieconsent-cookie__switch .gdpr-cookieconsent-switch > input:checked').length
                if (activeCheckboxInGroupCount === 0) {
                    groupCheckbox.checked = false;
                }
            }

        })
    });

    var cookie = decodeCookie();
    var consents = cookie && cookie.consents ? cookie.consents : [];

    if (KD_GDPR_CC.dimensionsIdentifier !== '' && !Array.isArray(consents)) {
        consents = consents[KD_GDPR_CC.dimensionsIdentifier];
    }

    if (consents) {
        consents.forEach(function (consentIdentifier) {
            if (consentIdentifier.length != '') {
                document.querySelector('.gdpr-cookieconsent-settings__content input[value=' + consentIdentifier + ']').checked = true;
            }
        });
    }
    var clickEvent;
    if (typeof (Event) === 'function') {
        clickEvent = new Event('click');
    } else {
        clickEvent = document.createEvent('Event');
        clickEvent.initEvent('click', true, true);
    }

    if (openSettings === true) {
        btnIndividualSettingsEnable.dispatchEvent(clickEvent);
    }

    if (
        openedManually !== true
        && typeof KD_GDPR_CC_ACCEPT_NECESSARY !== 'undefined'
        && KD_GDPR_CC_ACCEPT_NECESSARY === true
    ) {
        btnAcceptNecessaryCookies.dispatchEvent(clickEvent);
    }

    //focus first button when initialized
    btnAcceptAll.focus();
}


function dispatchEventsForCookies(inputs) {
    [].slice.call(inputs).forEach(function(input) {
        if (input.value) {
            var customEvent = document.createEvent("CustomEvent");
            customEvent.initCustomEvent('GDPR-CC-consent', false, false, input.value);
            document.dispatchEvent(customEvent);
        }
    });
}


function saveConsentToCookie(inputs, userId) {
    var cookie = decodeCookie();
    var currentDate = new Date();
    if (KD_GDPR_CC.decisionTtl && KD_GDPR_CC.decisionTtl > 0) {
        expireDate = new Date(currentDate.getTime() + KD_GDPR_CC.decisionTtl);
    } else {
        var expireDate = new Date(currentDate.getFullYear() + 1, currentDate.getMonth(), currentDate.getDate());
    }

    var consents = cookie && cookie.consents ? cookie.consents : {};
    consents[KD_GDPR_CC.dimensionsIdentifier] = [];
    [].slice.call(inputs).forEach(function(input) {
        consents[KD_GDPR_CC.dimensionsIdentifier].push(input.value);
    });

    var consentDates = cookie && cookie.consentDates ? cookie.consentDates : {};
    consentDates[KD_GDPR_CC.dimensionsIdentifier] = currentDate.toUTCString();


    var cookieData = {
        userId: userId,
        consents: consents,
        consentDates: consentDates,
        consentDate: currentDate.toUTCString(),
        expireDate: expireDate.toUTCString()
    };

    var cookieParams = encodeURI(JSON.stringify(cookieData)) + "; expires=" + new Date(currentDate.getTime() + 315360000000).toUTCString() + "; path=/; " + (KD_GDPR_CC.cookieDomainName ? ('domain=' + KD_GDPR_CC.cookieDomainName + ';') : '') + (location.protocol.substring(0,5) === 'https' ? " Secure;" : '');
    document.cookie = KD_GDPR_CC.cookieName + "=" + cookieParams;

    window.dataLayer = window.dataLayer || [];
    window.dataLayer.push({
        event: 'KD_GDPR_CC_consent',
        KD_GDPR_CC: {
            consents: cookieData.consents
        }
    });
}



function acceptNecessaryCookies(userId) {
    var necessaryGroupInputs = document.querySelectorAll('.gdpr-cookieconsent-setting-group--necessary .gdpr-cookieconsent-setting-group__switch input');
    var necessaryInputs = document.querySelectorAll('.gdpr-cookieconsent-setting-group--necessary .gdpr-cookieconsent-cookie input');

    //Save to cookie
    saveConsentToCookie([].slice.call(necessaryGroupInputs).concat([].slice.call(necessaryInputs)), userId);

    //Dispatch events
    dispatchEventsForCookies([].slice.call(necessaryGroupInputs).concat([].slice.call(necessaryInputs)), userId);
}

function loadGeneratedJavaScript() {
    var tag = document.createElement('script');
    tag.src = generatedJsUrl;
    document.getElementsByTagName('head')[0].appendChild(tag);
}

function decodeCookie() {
    var value = "; " + document.cookie;
    var parts = value.split("; " + KD_GDPR_CC.cookieName + "=");
    if (parts.length === 2) {
        var cookieValue = parts.pop().split(";").shift();
        return JSON.parse(decodeURI(cookieValue));
    }
}

document.addEventListener("DOMContentLoaded", function() {
    initializeCookieConsent();
});
