<?php
Config::set(
  'oauth',
  [
    'active' => true,
    'path' => 'modules',
    'topmenu' => false,
    'dependencies' => [
      'aws/aws-sdk-php' => '^3.55'
    ],
    'hooks' => [
      'auth',
      'tokens',
      'oauth'
    ],
    'apps' => [
      'cognito' => [
        // 'client_id#1' => [
        //     'client_secret' => "abcdeXYZ123",
        //     'title' => "MyAppIsCalled",
        //     'domain' => "2pi-something.somewhere.auth.ap-southeast-2.amazoncognito.com",
        //     'scope' => "asSetForApp",
        //     'callback' => "https://pi-HostingSomething.somewhere/oauth/flowsubmit",
        //     'login' => true/false if avails user to FORCE_LOGIN (cmfive),
        //     'redirect' => final landing page (as opposed to 'flowsubmit' JSON or splashpageTemplate),
        //     'splashpage' => "TemplateTitle" (module:oauth, category:splashpage)
        // ],
        // 'client_id#2' => [
        //     'client_secret' => "abcdeXYZ123",
        //     'title' => "MyOtherAppIsCalled",
        //     'domain' => "2pi-something.somewhere.auth.ap-southeast-2.amazoncognito.com",
        //     'scope' => "asSetForApp2",
        //     'callback' => "https://pi-HostingSomething.somewhere/oauth/flowsubmit",
        //     'login' => true/false if avails user to FORCE_LOGIN (cmfive),
        //     'redirect' => final landing page (as opposed to 'flowsubmit' JSON or splashpageTemplate),
        //     'splashpage' => "IgnoreIfYouJustWantJsonReturn" (module:oauth, category:splashpage)
        // ],
      ],
    ]
  ]
);

/*
/////////
// example: 'splashpage' => "Prover" -> templates -> oauth -> splashpage
// A simple splash coordinating Cognito biased returns
/////////
<div>
<br><br>
<h2 align="center">
  API application:
</h2>
<h1 align="center">
    {{app["title"]}}
    </h1>
<h2 align="center">
  Grants access for:
</h2>
<h1 align="center">
    {{bearer}}
</h1>
<h2 align="center">
  Token is:
</h2>
<div style="padding: 0 20% 0 20%;">
  <code style="display: block;overflow-wrap: break-word;text-align: left;">
    {{flow["access_token"]}}
  </code>
</div>
<br>
</div>
*/