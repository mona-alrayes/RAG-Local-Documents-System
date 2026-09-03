<?php

use App\Http\Controllers\DocumentController;
use App\Http\Controllers\WorkspaceController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/workspace', WorkspaceController::class)
        ->name('workspace');

    Route::view('/settings/account', 'settings.account')
        ->name('settings.account');

    Route::get('/documents', [DocumentController::class, 'index'])
        ->name('documents.index');

    Route::get('/documents/{document}', [DocumentController::class, 'show'])
        ->name('documents.show');

    Route::post('/documents', [DocumentController::class, 'store'])
        ->name('documents.store');

    Route::get('/documents/{document}/download', [DocumentController::class, 'download'])
        ->name('documents.download');

    Route::post(
        '/documents/{document}/reprocess',
        [DocumentController::class, 'reprocess'],
    )->name('documents.reprocess');

    Route::delete(
        '/documents/{document}',
        [DocumentController::class, 'destroy'],
    )->name('documents.destroy');
});
