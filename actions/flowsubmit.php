<?php

/**@author Derek Crannaford */

function flowsubmit_ALL(Web $w)
{
    if (empty($_GET['code']) || empty($_GET['state'])) {
        ApiOutputService::getInstance($w)->apiFailMessage("oauth flow response", "Flow is invalid");
    }

    $known = OauthFlowService::getInstance($w)->getObject("OauthFlow", ['state' => $_GET['state']]);
    $app = OauthFlowService::getInstance($w)->getOauthAppById($known->app_id ?? null);
    if (empty($known) || empty($app)) {
        ApiOutputService::getInstance($w)->apiFailMessage("oauth flow response", "State is invalid");
    }
    $known->delete();

    $asserted = $w->callHook(
        "oauth",
        "request_code_submit_flow",
        [
            'code' => $_GET['code'],
            'state' => $_GET['state']
        ]
    );

    foreach (($asserted ?? []) as $check) {
        if (!empty($check['access_token'])) {

            $appCheck = TokensService::getInstance($w)->getAppFromJwtPayload($check['access_token']);
            if ($appCheck !== $known->app_id) {
                ApiOutputService::getInstance($w)->apiFailMessage("oauth flow response", "Client conflict");
            }

            $payload = TokensService::getInstance($w)->getJwtPayload($check['access_token']);
            $userDisplay = $payload["username"] ?? null;
            $userDisplay .= (empty($check['email'])) ? "" : ((empty($userDisplay)) ? "" : ":" . $check['email']);
            $grant =                 [
                "bearer" => $userDisplay,
                "app" => $app,
                "flow" => $check,
                "payload" => $payload
            ];

            // the app's config PHP might want this to be a cmfive login! (piggybacked on token)
            if ($app['login'] ?? null) {
                $sessionUser = AuthService::getInstance($w)->getUser($check['auth_user'] ?? null);
                if (empty($sessionUser) || (strtoupper($sessionUser->login) !== strtoupper($payload["username"]))) {
                    ApiOutputService::getInstance($w)->apiFailMessage("oauth flow response", "Flow is invalid");
                }
                AuthService::getInstance($w)->forceLogin($sessionUser->id);
                // if we're redirecting, we don't want token splattered over URL params!
                // So, we can stash into session ...
                $w->session('oauth_grant_bundle', $grant);
                if ($app['redirect']) {
                    $w->redirect($app['redirect']);
                }
            }

            // the app's config PHP might want tidy exchange of token, let's have a template
            if (!empty($app['splashpage'])) {
                $template = OauthFlowService::getInstance($w)->getOauthSplashPageTemplate($app['splashpage']);
                if (!empty($template)) {
                    $splashPage = TemplateService::getInstance($w)->render(
                        $template->id,
                        $grant
                    );
                    ApiOutputService::getInstance($w)->apiReturnCmfiveStyledHtml($w, $splashPage);
                }
            }

            // otherwise, the app will have to settle for raw exchange of token, as basic json
            ApiOutputService::getInstance($w)->apiKeyedResponse($check, "Application API key granted for " . $app['title']);
        }
    }

    ApiOutputService::getInstance($w)->apiFailMessage("oauth flow response", "No handler");
}
