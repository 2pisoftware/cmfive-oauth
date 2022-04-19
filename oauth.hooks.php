<?php

function oauth_oauth_request_app_id_flow(Web $w, $requested)
{
    CognitoFlowService::getInstance($w)->attemptCognitoFlow($requested);
}


function oauth_oauth_request_code_submit_flow(Web $w, $requested)
{
    return CognitoFlowService::getInstance($w)->attemptCognitoAccess($requested);
}

// Your app should elaborate these!

// function [myapp]_auth_get_auth_token_validation(Web $w, $jwt)
// {
//     return TokensService::getInstance($w)->getCoreTokenCheck($jwt);
// }


// function [myapp]_tokens_get_roles_from_policy(Web $w, $policy)
// {
//     if ($policy->_validator == 'CMFIVE') {
//         return TokensService::getInstance($w)->getCoreRolesByDayDateUserPolicy($policy);
//     }
// }
