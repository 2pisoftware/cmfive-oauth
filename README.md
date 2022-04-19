# cmfive-oauth     
Open ended oauth support for cmfive with Amazon Cognito biases     
     
This module contains service models for outh token grants from providers     
It includes a helpful/extensible action for use as callback url [oauth/flowsubmit]     
     
There is bias to 'apps' from a Cognito user pool. (ie: additional/specific service methods)     
   
Actions:

     - flowinit/[app_client_id] --> initiates oauth token grant   
     - fires hook "request_app_id_flow"   
     - for "COGNITO" configured apps, will call to CognitoUserPool as identity provider, with a state parameter and PKCE provisioning   
   
     - flowsubmit?code&state --> handles callback to resubmit an authorisation token in return for access token from provider   
     - fires hook "request_code_submit_flow"   
     - for "COGNITO" configured apps, will call back to CognitoUserPool with PKCE validation   
     - if access token is granted, will check for a redirect into cmfive (with login) OR HTML template output (Splahspage) OR simple JSON return of JWT   
   
Extension:   
     - non Cognito apps will need to provision their own flow handlers   
     - flowsubmit expects a key array object returned by handler, suitable to:     
         $check['access_token'] --> JWT header.payload.sig     
         $check['email'] --> to anchor external user identity     
         $check['auth_user'] --> cmfive user_id, if appropriate     
         ... any other fields as your app desires! ...     
     - flowsubmit builds a helpful $grant object, for display through Splashpage template, or pushed into $w->session for login auth:   
         $grant =                 [     
                "bearer" => $userDisplay, --> username:email       
                "app" => $app, --> the app config     
                "flow" => $check, --> code_submit_flow results     
                "payload" => $payload --> access token payload     
            ];   
   
   
 Config:   
   
You need to register your app into config.oauth, per 'client_id' etc       
eg:

    - 'apps' => [     
    -   'cognito' => [     
    - 'client_id#1' => [   
    -     'client_secret' => "abcdeXYZ123",   
    -     'title' => "MyAppIsCalled",   
    -     'domain' => "2pi-something.somewhere.auth.ap-southeast-2.amazoncognito.com",   
    -     'scope' => "asSetForApp",   
    -     'callback' => "https://pi-HostingSomething.somewhere/oauth/flowsubmit",   
    -     'login' => true/false if avails user to FORCE_LOGIN (cmfive),   
    -     'redirect' => final landing page (as opposed to 'flowsubmit' JSON or splashpageTemplate),   
    -     You can make a twigTemplate to offer a confirmation splash page (or not)     
    -     'splashpage' => "TemplateTitle" (module:oauth, category:splashpage)   
    - ],   
    - 'client_id#2' => [   
    -     'client_secret' => "abcdeXYZ123",   
    -     'title' => "MyOtherAppIsCalled",   
    -     'domain' => "2pi-something.somewhere.auth.ap-southeast-2.amazoncognito.com",   
    -     'scope' => "asSetForApp2",   
    -     'callback' => "https://pi-HostingSomething.somewhere/oauth/flowsubmit",   
    -     'login' => true/false if avails user to FORCE_LOGIN (cmfive),   
    -     'redirect' => final landing page (as opposed to 'flowsubmit' JSON or splashpageTemplate),   
    -     'splashpage' => "IgnoreIfYouJustWantJsonReturn" (module:oauth, category:splashpage)   
    - ],   
    -   ],     
    - ]     
     
     


Applications:

To use your tokens for an 'app' model, make module actions leaning on core:auth:tokens     
ie, by implementing hooks:     
    
    A request with auth token bearer and no user session state, triggers token handling from auth module:     
         - A hook fires to have the token validated      
            - your APP MODULE should claim *** [module]_auth_get_auth_token_validation ***     
            - auth module accepts the token if a hook handler yields a TokensPolicy     
            - The policy is internally 'stateless' & never persisted in cmfive DB     
            - then auth module requests for TokensPolicy to allow the current request action     
         - A hook fires from TokensPolicy seeking an application model to implement the policy roles     
            - your APP MODULE should claim *** [module]__tokens_get_roles_from_policy  ***     
            - the listening APP will materialise these in standard manner of user->roles->allowed     
            - TokensPolicy GetBearersRoles assists with collation     
            - APP can interpret profile identifier freely (eg: as user_id, group_policy, code-baked app_actions etc)     
    
         
     
