<?php

/**@author Derek Crannaford */

function flowinit_ALL(Web $w)
{

    $app = $w->pathMatch('app')['app'] ?? null;
    if (empty($app)) {
        ApiOutputService::getInstance($w)->apiFailMessage("oauth flow initialisation", "App not valid");
    }

    $w->callHook(
        "oauth",
        "request_app_id_flow",
        [
            'app_id' => $app
        ]
    );

    ApiOutputService::getInstance($w)->apiFailMessage("auth flow initialisation", "No handler");
}
