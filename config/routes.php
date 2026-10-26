<?php

/*
 * Route table: 'url' => 'Controller@method'
 * Pages are opened as index.php?url=<route>
 */

return [

    /* ---------- Auth ---------- */
    'auth/login'                  => 'AuthController@login',
    'auth/authenticate'           => 'AuthController@authenticate',
    'auth/register'               => 'AuthController@register',
    'auth/store'                  => 'AuthController@store',
    'auth/logout'                 => 'AuthController@logout',
    'auth/forgot'                 => 'AuthController@forgot',

    /* ---------- User ---------- */
    'dashboard'                   => 'UserController@dashboard',
    'profile'                     => 'UserController@profile',
    'notifications'               => 'NotificationController@index',
    'notifications/user'          => 'NotificationController@user',

    /* ---------- Items ---------- */
    'items/create'                => 'ItemController@create',
    'items/mine'                  => 'ItemController@mine',
    'items/edit'                  => 'ItemController@edit',
    'items/delete'                => 'ItemController@delete',
    'items/delete-donate'         => 'ItemController@deleteDonate',
    'items/view'                  => 'ItemController@show',
    'items/browse'                => 'ItemController@browse',
    'items/search'                => 'ItemController@search',
    'items/sell'                  => 'ItemController@sell',
    'items/donate'                => 'ItemController@donate',
    'items/buy'                   => 'ItemController@buy',

    /* ---------- Requests / Payments ---------- */
    'requests/create'             => 'RequestController@create',
    'requests/mine'               => 'RequestController@mine',
    'requests/action'             => 'RequestController@action',
    'transactions'                => 'TransactionController@index',
    'payment'                     => 'TransactionController@payment',

    /* ---------- Wanted posts ---------- */
    'wanted/create'               => 'WantedController@create',
    'wanted/mine'                 => 'WantedController@mine',
    'wanted/posts'                => 'WantedController@posts',
    'wanted/view'                 => 'WantedController@show',
    'wanted/edit'                 => 'WantedController@edit',
    'wanted/delete'               => 'WantedController@delete',

    /* ---------- Offers ---------- */
    'offers/create'               => 'OfferController@create',
    'offers/action'               => 'OfferController@action',
    'offers/view'                 => 'OfferController@show',
    'offers/list'                 => 'OfferController@list',

    /* ---------- Admin ---------- */
    'admin/dashboard'             => 'AdminController@dashboard',
    'admin/users'                 => 'AdminController@users',
    'admin/users-action'          => 'AdminController@usersAction',
    'admin/items'                 => 'AdminController@items',
    'admin/items-action'          => 'AdminController@itemsAction',
    'admin/ngos'                  => 'AdminController@ngos',
    'admin/ngos-action'           => 'AdminController@ngosAction',
    'admin/delivery-men'          => 'AdminController@deliveryMen',
    'admin/delivery-men-action'   => 'AdminController@deliveryMenAction',
    'admin/requests'              => 'AdminController@requests',
    'admin/requests-action'       => 'AdminController@requestsAction',

    /* ---------- NGO ---------- */
    'ngo/dashboard'               => 'NgoController@dashboard',
    'ngo/profile'                 => 'NgoController@profile',
    'ngo/browse-wanted'           => 'NgoController@browseWanted',
    'ngo/create-post'             => 'NgoController@createPost',
    'ngo/donation-requests'       => 'NgoController@donationRequests',
    'ngo/request-action'          => 'RequestController@ngoAction',
    'ngo/accepted-donations'      => 'NgoController@acceptedDonations',
    'ngo/arrange-pickup'          => 'NgoController@arrangePickup',
    'ngo/pickups'                 => 'NgoController@pickups',
    'ngo/assign-delivery'         => 'NgoController@assignDelivery',
    'ngo/notifications'           => 'NgoController@notifications',
    'ngo/notification-view'       => 'NgoController@notificationView',

    /* ---------- Delivery ---------- */
    'delivery/dashboard'          => 'DeliveryController@dashboard',
    'delivery/jobs'               => 'DeliveryController@jobs',
    'delivery/assigned'           => 'DeliveryController@assigned',
    'delivery/assigned-list'      => 'DeliveryController@assignedList',
    'delivery/history'            => 'DeliveryController@history',
    'delivery/profile'            => 'DeliveryController@profile',
    'delivery/earnings'           => 'DeliveryController@earnings',
    'delivery/availability'       => 'DeliveryController@availability',
    'delivery/notifications'      => 'DeliveryController@notifications',
    'delivery/job-response'       => 'DeliveryController@jobResponse',
    'delivery/charge'             => 'DeliveryController@charge',
    'delivery/progress'           => 'DeliveryController@progress',
    'delivery/track'              => 'TrackingController@index',
    'delivery/charge-response'    => 'TrackingController@chargeResponse',
];
