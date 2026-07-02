<?php

use Illuminate\Support\Facades\Route;
use Modules\ChattingManagement\Http\Controllers\Api\ChattingController;


Route::group(['prefix' => 'customer'], function () {
    Route::group(['prefix' => 'chat', 'middleware' => ['auth:api', 'maintenance_mode', 'throttle:60,1']], function () {
        Route::controller(ChattingController::class)->group(function () {
            Route::get('find-channel', 'findChannel');
            Route::put('create-channel', 'createChannel');
            // Sends broadcast + push, so cap them tighter than reads (stacks with the group limit).
            Route::put('send-message', 'sendMessage')->middleware('throttle:30,1');
            Route::get('conversation', 'conversation');
            Route::get('channel-list', 'channelList');
        });
    });
});

Route::group(['prefix' => 'driver'], function () {
    Route::group(['prefix' => 'chat', 'middleware' => ['auth:api', 'maintenance_mode', 'throttle:60,1']], function () {
        Route::controller(ChattingController::class)->group(function () {
            Route::get('find-channel', 'findChannel');
            Route::put('create-channel', 'createChannel');
            // Sends broadcast + push, so cap them tighter than reads (stacks with the group limit).
            Route::put('send-message', 'sendMessage')->middleware('throttle:30,1');
            Route::get('conversation', 'conversation');
            Route::get('channel-list', 'channelList');
            Route::put('create-channel-with-admin', 'createChannelWithAdmin');
            Route::put('send-message-to-admin', 'sendMessageToAdminFromDriver')->middleware('throttle:30,1');
            Route::put('send-predefined-question-to-admin', 'sendPredefinedQuestionToAdminFromDriver')->middleware('throttle:30,1');
        });
    });
});
