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

function initializeCookieConsent(openSettings = false) {

    var kd_gdpr_cc_userid;
    var cookieSettingsContainer = document.querySelector('.gdpr-cookieconsent-settings');
    var darkOverlay = document.querySelector('.gdpr-cookieconsent-overlay');
    var txtMainDescription = document.querySelector('.gdpr-cookieconsent-settings__content__info__main-description');
    var txtIndividualSettingsDescription = document.querySelector('.gdpr-cookieconsent-settings__content__info__settings-description');
    var individualSettingsContainer = document.querySelector('.gdpr-cookieconsent-settings__content__settings');
    var btnIndividualSettingsEnable = document.querySelector('#gdpr-cc-btn-individual-settings-enable');
    var btnIndividualSettingsDisable = document.querySelector('#gdpr-cc-btn-individual-settings-disable');
    var btnAcceptAll = document.querySelector('#gdpr-cc-btn-accept');
    var btnSaveSettings = document.querySelector('#gdpr-cc-btn-save');
    var btnAcceptNecessaryCookies = document.querySelector('#gdpr-cc-btn-accept-necessary');
    var btnCloseCookieSettings = document.querySelector('#kd_gdpr_cc-close');

    //Create log in BE and store userID
    if (consentLogEnabled) {
        var xhr = new XMLHttpRequest();
        xhr.open('POST', trackChoiceUrl);
        xhr.setRequestHeader("Content-Type", "application/json;charset=UTF-8");
        xhr.onreadystatechange = function() {
            if(xhr.readyState === 4) {
                kd_gdpr_cc_userid = JSON.parse(xhr.response).userId;
            }
        };
        xhr.send();
    }

    btnIndividualSettingsEnable.addEventListener('click', function() {
        individualSettingsContainer.style.display = 'block';
        txtMainDescription.style.display = 'none';
        txtIndividualSettingsDescription.style.display = 'block';
        btnIndividualSettingsEnable.style.display = 'none';
        btnIndividualSettingsDisable.style.display = 'block';
        if(btnAcceptNecessaryCookies) btnAcceptNecessaryCookies.style.display = 'none';
        btnAcceptAll.style.display = 'none';
        btnSaveSettings.style.display = 'block';
    });
    if (openSettings) {
        let clickEvent = new MouseEvent('click');
        btnIndividualSettingsEnable.dispatchEvent(clickEvent);
    }

    btnIndividualSettingsDisable.addEventListener('click', function() {
        individualSettingsContainer.style.display = 'none';
        txtMainDescription.style.display = 'block';
        txtIndividualSettingsDescription.style.display = 'none';
        btnIndividualSettingsEnable.style.display = 'block';
        btnIndividualSettingsDisable.style.display = 'none';
        if(btnAcceptNecessaryCookies) btnAcceptNecessaryCookies.style.display = 'block';
        btnAcceptAll.style.display = 'block';
        btnSaveSettings.style.display = 'none';
    });

    btnAcceptAll.addEventListener('click', function() {
        var selectedInputs = document.querySelectorAll('.gdpr-cookieconsent-switch input'); //Select all inputs

        //Save to cookie
        saveConsentToCookie(selectedInputs, kd_gdpr_cc_userid);

        //Dispatch events
        dispatchEventsForCookies(selectedInputs);

        //Remove CookieConsent from HTML
        cookieSettingsContainer.remove();
        darkOverlay.remove();
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
        darkOverlay.remove();
        loadGeneratedJavaScript();
    });


    if (btnAcceptNecessaryCookies) {
        btnAcceptNecessaryCookies.addEventListener('click', function() {
            acceptNecessaryCookies(kd_gdpr_cc_userid);
            //Remove CookieConsent from HTML
            cookieSettingsContainer.remove();
            darkOverlay.remove();
            loadGeneratedJavaScript();
        });
    }

    if (btnCloseCookieSettings) {
        btnCloseCookieSettings.addEventListener('click', function (e) {
            e.preventDefault();
            cookieSettingsContainer.remove();
            darkOverlay.remove();
        });
    }

    [].slice.call(document.querySelectorAll('.gdpr-cookieconsent-setting-group__details-open')).forEach(function(detailsLink) {
        detailsLink.addEventListener('click', function() {
            detailsLink.style.display = 'none';
            detailsLink.parentElement.querySelector('.gdpr-cookieconsent-setting-group__cookies').style.display = 'block';
            detailsLink.parentElement.querySelector('.gdpr-cookieconsent-setting-group__details-close').style.display = 'block';
        });
    });

    [].slice.call(document.querySelectorAll('.gdpr-cookieconsent-setting-group__details-close')).forEach(function(detailsLink) {
        detailsLink.addEventListener('click', function() {
            detailsLink.style.display = 'none';
            detailsLink.parentElement.querySelector('.gdpr-cookieconsent-setting-group__cookies').style.display = 'none';
            detailsLink.parentElement.querySelector('.gdpr-cookieconsent-setting-group__details-open').style.display = 'block';
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

    var cookieParams = encodeURI(JSON.stringify(cookieData)) + "; expires=" + new Date(currentDate.getTime() + 315360000000).toUTCString() + "; path=/; " + (KD_GDPR_CC.cookieDomainName ? ('domain=' + KD_GDPR_CC.cookieDomainName + ';') : '') +" Secure;";
    document.cookie = KD_GDPR_CC.cookieName + "=" + cookieParams;

    window.dataLayer = window.dataLayer || [];
    window.dataLayer.push({
        event: 'KD_GDPR_CC_consent',
        KD_GDPR_CC: {
            consents: cookieData.consents
        }
    });

    if (consentLogEnabled) {
        var xhr = new XMLHttpRequest();
        xhr.open('POST', trackChoiceUrl, false);
        xhr.setRequestHeader("Content-Type", "application/json;charset=UTF-8");
        xhr.send(JSON.stringify({'choice': cookieData}));
    }
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
