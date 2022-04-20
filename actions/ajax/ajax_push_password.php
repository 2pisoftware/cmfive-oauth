<?php

function ajax_push_password_POST(Web $w)
{
    $w->setLayout(null);

    /*
      id: _this.user.id,
                    user_pool: _this.user.pool.user_pool,
                    new_password: _this.user.security.new_password,
                    repeat_new_password: _this.user.security.repeat_new_password
                    */

    $request_data = json_decode(file_get_contents("php://input"), true);
    if (empty($request_data) 
    || empty($request_data["id"]) 
    || empty($request_data["new_password"]) 
    || empty($request_data["repeat_new_password"])
    || empty($request_data["user_pool"]) ) {
        $w->out((new AxiosResponse())->setErrorResponse("Request data missing", null));
        return;
    }

    if ($request_data["new_password"] !== $request_data["repeat_new_password"]) {
        $w->out((new AxiosResponse())->setErrorResponse("Passwords don't match", null));
        return;
    }

    $user = AuthService::getInstance($w)->getUser($request_data["id"]);
    if (empty($user)) {
        $w->out((new AxiosResponse())->setErrorResponse("Unable to find user", null));
        return;
    }

    if ($user->id != AuthService::getInstance($w)->loggedIn()) {
        $w->out((new AxiosResponse())->setErrorResponse("User mismatch", null));
        return;
    }


    $done = CognitoFlowService::getInstance($w)->setUserPasswordByUsername(
        $user->login,
         $request_data["new_password"], 
         $request_data["user_pool"]
    );

    if (!$done) {
        $w->out((new AxiosResponse())->setErrorResponse("Failed to update password", null));
        return;
    }

    $w->out((new AxiosResponse())->setSuccessfulResponse("User Pool password updated", null));
}
