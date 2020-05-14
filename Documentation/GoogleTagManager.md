# KaufmannDigital.GDPR.UserManagement
## React to User-Decision using Google Tag Manager
Only a few configurations are necessary to use the Tag Manager. The necessary steps are described in detail below.

### 1. Create custom variable
First we have to create a custom datalayer-variable to check which cookies are accepted by the user.  
Logged in to Tag Manger, click on "Variables" and select "New" under "Userdefined variables".
![New userdefined variable](Images/GoogleTagManager/variable_new.png)

Give the variable a recognizable name and select "Datalayer variable" as type.
![Select variable type](Images/GoogleTagManager/variable_type.png)

Now add `KD_GDPR_CC.consents` as path for the variable and save the new variable.
![Configure variable](Images/GoogleTagManager/variable_configure.png)


### 2. Create custom event trigger
Now we have to create a new trigger listening to the event, which is triggered by the package.  
To do so, switch to the "Trigger"-Menu and add a new one.  
![Create new trigger](Images/GoogleTagManager/trigger_new.png)

As type we have to select the "userdefined event".
![Select trigger type](Images/GoogleTagManager/trigger_type.png)

Now we have to configure the trigger. As name we can use `KD_GDPR_CC_consent`.  
The trigger should only be triggered, if the variable `CookieConsent` (configured in step 1) should `contain` the identifier configured in the Neos Backend. In this example `google-analytics`.
![Configure trigger](Images/GoogleTagManager/trigger_configure.png)
Don't forget to save your changes 🙂

### 3. Add trigger to existing Tags
As last step, we only have to remove other triggers from our existing Tags and add the just created one.  
![Adjust Tags](Images/GoogleTagManager/tag_adjust.png)

After testing and publishing changes, we are done 🎉   
You can repeat Step 2 and 3 for other Cookies and Groups you defined, if needed. The variable in step 1 can be reused in all triggers.
