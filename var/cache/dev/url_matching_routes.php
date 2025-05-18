<?php

/**
 * This file has been auto-generated
 * by the Symfony Routing Component.
 */

return [
    false, // $matchHost
    [ // $staticRoutes
        '/price' => [[['_route' => 'price', '_controller' => 'App\\Controller\\PriceController::getPrice'], null, ['GET' => 0], null, false, false, null]],
        '/orders/stats' => [[['_route' => 'order_stats', '_controller' => 'App\\Controller\\OrderController::getOrderStats'], null, ['GET' => 0], null, false, false, null]],
        '/soap' => [[['_route' => 'soap_create_order', '_controller' => 'App\\Controller\\OrderController::createOrder'], null, ['POST' => 0], null, false, false, null]],
        '/search' => [[['_route' => 'search_orders', '_controller' => 'App\\Controller\\OrderController::searchOrders'], null, ['GET' => 0], null, false, false, null]],
        '/debug/check-privileges' => [[['_route' => 'debug_check_privileges', '_controller' => 'App\\Controller\\DebugController::checkPrivileges'], null, ['GET' => 0], null, false, false, null]],
    ],
    [ // $regexpList
        0 => '{^(?'
                .'|/_error/(\\d+)(?:\\.([^/]++))?(*:35)'
                .'|/orders/([^/]++)(*:58)'
            .')/?$}sDu',
    ],
    [ // $dynamicRoutes
        35 => [[['_route' => '_preview_error', '_controller' => 'error_controller::preview', '_format' => 'html'], ['code', '_format'], null, null, false, true, null]],
        58 => [
            [['_route' => 'get_order', '_controller' => 'App\\Controller\\OrderController::getOrder'], ['id'], ['GET' => 0], null, false, true, null],
            [null, null, null, null, false, false, 0],
        ],
    ],
    null, // $checkCondition
];
