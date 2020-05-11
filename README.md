
# KaufmannDigital.GDPR.CookieConsent
A ready-to-run package, that integrates an advanced cookie consent banner into your Neos CMS site.  
This is a further development of our previous [cookie consent package](https://github.com/KaufmannDigital/KaufmannDigital.CookieConsent). Through the individual configuration per service (cookie), this package is a perfect basis for creating GDPR compliant websites.

## Installation
It's easier than you probably think!  
Just run `composer require kaufmanndigital/gdpr-cookieconsent`

## Configuration
Since this package is ready-to-run, you can configure your cookie banner in just a few Steps.

1. Create a new **Cookie-Settings Page** (shipped inside the Package) somewhere inside your Site-Tree.
2. Switch to the newly created page and edit the cookie banner contents to your wishes.
3. Add cookie-groups and cookies to the banner.  

### React to the user's cookie decision
You can use one of these Methods to react on the user's decision on which Cookies are accepted:

#### Load JavaScript dynamically
You can paste your JavaScript-Code while adding Cookies to the banner. The package will evaluate the user's decision and merge the required JavaScript dynamically for each user on the fly.
You don't have to take care of anything. JavaScript gets loaded completely automatic. Cool, isn't it? 😎  
*Ah! And don't worry about performance. All JS gets minified and cached for every single visitor individually.*


#### Read cookie-identifiers from cookie
If you are already using another way to include your JavaScript, you can depend on the value of the Choice-Cookie.  
It's named `KD_GDPR_CC` and contains all identifiers of groups and cookies you defined in Backend while configuration. The payload of that cookie could look like this:
```json
{
   "consents": [
      "necessary",
      "analytics",
      "marketing"
   ],
   "consentDate": "Tue, 11 Feb 2020 11:35:23 GMT",
   "expireDate": "Wed, 10 Feb 2021 23:00:00 GMT"
}
```
So just check *consents* and load the needed JavaScript.  
*Pro-Tip: If you are using Google Tag Manager to add your JS-Tags, you can define a custom variable of type "First-Party-Cookie", which can be used as condition inside triggers then.*

#### React to datalayer event in Google Tag Manager
In order to react to the user's decision in the Google Tag Manager, not much is needed. This package pushes information into the dataLayer variable, which can be easily used in Google Tag Manager. All you have to do is create a user defined variable. As path you can use 'KD_GDPR_CC.consents'. Now this variable can be queried in the trigger. It contains all identifiers of the cookies/groups defined in the backend, which are accepted by the user.


#### Add a Re-open link
To create a link for reopening the banner, you only have to place a link with `#GDPR-CC-open-settings` as target: 
```html
<a href="#GDPR-CC-open-settings">Cookie-Settings</a>
``` 
After clicking on such a link, the cookie-banner will be loaded via API. Old settings are used as presets.

#### Versioning
In some cases, it may be necessary to show the cookie-banner to people who have actually already accepted it. For example, if a new cookie has been added.  
To do this, you only need to edit the version date. You can find it in the inspector of the cookie-settings NodeType:   
![version-date setting](Documentation/Images/version-date.png)  

After the date has been changed, the banner will be shown again to all visitors, who have submitted the cookie banner before this date. Old settings are used as presets.


### Styling
#### Custom Banner-Styles
The banner comes with a few basic-styles for positioning, which are getting included inline. To add your custom styles, just put a CSS-Files somewhere in your Resources-Folder and include it using Settings.yaml:   
```yaml
KaufmannDigital:
  GDPR:
    CookieConsent:
      customCSSFilepath: 'resource://Vendor.Package/Private/Styles/cookie-consent.css' #You can also use the public-path, of course
```
To get an idea of the CSS-styling and class-names, you can have a look [into our SCSS](Resources/Private/Styles/Main.scss).  

#### Site-Styles on Cookie-Page
If you rely on your Site-Package Styles inside of the banner, you can just include them into the (otherwise unstyled) Cookie-Page for configuring the banner inside the Backend. To do so, just add it to your Settings:
```yaml
KaufmannDigital:
  GDPR:
    CookieConsent:
      siteCSSFilepath: 'resource://Vendor.Package/Public/Stylesheets/Site.css'
```

*Hint: We are working on advanced styling options. Different style- and positioning presets will be available in future. If you have any wishes or created a cool design for this banner yourself, please contact us.*


## Roadmap / Planned Features

* Presets for popular cookies. For example:
  * Google Analytics
  * Matomo (Piwik)
  * Intercom Support-Chat
  * ...

* Adjustable CI/Colors inside the Backend



## Sponsors
We would like to thank our sponsors, who supported us financially during the development:  

[![Mittwald Logo](Documentation/Sponsors/Mittwald/logo-mittwald.png)](https://www.mittwald.de/?utm_source=github&utm_medium=banner&utm_campaign=cookie-consent-manager-package)


Are you missing a feature in our solution? You wan't to support the development of this Package? Please don't hesitate to contact us!  
Email: [support@kaufmann.digital](mailto:support@kaufmann.digital)


## Maintainer

This package is maintained by the [Neos Agency Kaufmann Digital](https://www.kaufmann.digital).  
Feel free to send us your questions or requests to [support@kaufmann.digital](mailto:support@kaufmann.digital)

### Issues and Pull-Requests are welcome!
You got stuck while installing or configuring? You are missing something? You found a bug?  
No problem, just create an issue or open a pull request. We'll have a look at it ASAP.

## License

Licensed under GPL-3, see [LICENSE](LICENSE)
