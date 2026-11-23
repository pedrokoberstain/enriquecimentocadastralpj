<?php

use App\Http\Controllers\CompanyController;
use App\Http\Middleware\EnsureTeamMembership;
use Illuminate\Support\Facades\Route;

$middleware = app()->isLocal()
    ? []
    : ['web', 'auth', 'verified', EnsureTeamMembership::class];

Route::middleware($middleware)
    ->prefix('teams/{team}/companies')
    ->group(function () {
        Route::get('/', [CompanyController::class, 'index'])->name('api.companies.index');
        Route::post('/', [CompanyController::class, 'store'])->name('api.companies.store');
        Route::get('/{cnpj}', [CompanyController::class, 'show'])->name('api.companies.show');
        Route::post('/{cnpj}/refresh', [CompanyController::class, 'refresh'])->name('api.companies.refresh');
        Route::delete('/{cnpj}', [CompanyController::class, 'destroy'])->name('api.companies.destroy');
    });
