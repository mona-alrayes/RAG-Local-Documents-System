<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('/workspace', 'workspace.index')
        ->name('workspace');

    Route::view('/settings/account', 'settings.account')
        ->name('settings.account');
});
