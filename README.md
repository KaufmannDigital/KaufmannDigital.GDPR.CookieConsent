

# Installation
It's easier than you probably think!  
Just run `composer require kaufmanndigital/gdpr-cookieconsent`

# Configuration
Since this package is ready-to-run, you can configure your cookie banner in just a few Steps.

1. Create a new **Cookie-Settings Page** (shipped inside the Package) somewhere inside your Site-Tree.
2. Switch to the newly created page and edit the cookie banner contents to your wishes.
3. Add cookie-groups and cookies to the banner.  

## React to the user's cookie decision
You can use one of these Methods to react on the user's decision on which Cookies are accepted:

### Load JavaScript dynamically
You can paste your JavaScript-Code while adding Cookies to the banner. The package will evaluate the user's decision and merge the required JavaScript dynamically for each user on the fly.
You don't have to take care of anything. JavaScript gets loaded completely automatic. Cool, isn't it? 😎  
*Ah! And don't worry about performance. All JS gets minified and cached for every single visitor individually.*


### Read cookie-identifiers from cookie
If you are already using another way to include your JavaScript, you can depend on the value of the Choice-Cookie.  
It's named `KD_GDPR_CC` and contains all identifiers of groups and cookies you defined in Backend while configuration. The payload of that cookie could look like this:
```json
{   "consents":[
      "necessary",
      "analytics",
      "marketing"

],
   "consentDate":"Tue, 11 Feb 2020 11:35:23 GMT",
   "expireDate":"Wed, 10 Feb 2021 23:00:00 GMT"
}
```

So just check *consents* and load the needed JavaScript.  
*Pro-Tip: If you are using Google Tag Manager to add your JS-Tags, you can define a custom variable of type "First-Party-Cookie", which can be used as condition inside triggers then.*


# Roadmap / Planned Features

* Presets for popular cookies. For example:
  * Google Analytics
  * Matomo (Piwik)
  * Intercom Support-Chat
  * ...

* Adjustable CI/Colors inside the Backend



# Sponsors
We would like to thank our sponsors, who supported us financially during the development:  

[![Mittwald Logo](Documentation/Sponsors/Mittwald/logo-mittwald.png)](https://www.mittwald.de/?utm_source=github&utm_medium=banner&utm_campaign=cookie-consent-manager-package)


Are you missing a feature in our solution? You wan't to support the development of this Package? Please don't hesitate to contact us!  
Email: [support@kaufmann.digital](mailto:support@kaufmann.digital)


# Maintainer

This package is maintained by [Kaufmann Digital](https://www.kaufmann.digital).  
Feel free to send us your questions or requests to [support@kaufmann.digital](mailto:support@kaufmann.digital)

# License

Licensed under GPL-3, see [LICENSE](LICENSE)
