<?php

function ajax_revoke_token_POST(Web $w)
{
    $w->setLayout(null);

    /*
      $grant =                 [
                "bearer"  
                "app"  
                "flow"  
                "payload" 
            ];
                    */

    $request_data = json_decode(file_get_contents("php://input") ,true)['grant']??[];
    if (empty($request_data) 
    || empty($request_data["bearer"]) 
    || empty($request_data["app"]) 
    || empty($request_data["flow"])
    || empty($request_data["payload"]) ) {
        $w->out((new AxiosResponse())->setErrorResponse("Request data missing", null));
        return;
    }
    
    $refreshToken =  ($request_data["flow"]["refresh_token"]);
    if (empty($refreshToken)) {
        $w->out((new AxiosResponse())->setErrorResponse("Unable to act on token", null));
        return;
    }

    $app = ($request_data["app"]["provider"]);
    if (empty($app)) {
        $w->out((new AxiosResponse())->setErrorResponse("Unable to identify app", null));
        return;
    }

    $secret = ($request_data["app"]["client_secret"]);
    if (empty($secret)) {
        $w->out((new AxiosResponse())->setErrorResponse("Unable to identify app", null));
        return;
    }    

    $done = CognitoFlowService::getInstance($w)->revokeAccessByRefreshToken($refreshToken, $app, $secret);

    if (!$done) {
        $w->out((new AxiosResponse())->setErrorResponse("Failed to revoke token", null));
        return;
    }

    $w->out((new AxiosResponse())->setSuccessfulResponse("Access token revoked", null));
}
