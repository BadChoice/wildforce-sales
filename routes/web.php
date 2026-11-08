<?php

use App\Http\Controllers\DiscoveryCampaignController;
use App\Http\Controllers\ProspectController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
});

Route::middleware(['auth', 'verified', 'can:access-growth'])
    ->prefix('discovery')
    ->as('discovery.')
    ->group(function (): void {
        Route::get('campaigns', [DiscoveryCampaignController::class, 'index'])->name('campaigns.index');
        Route::get('campaigns/create', [DiscoveryCampaignController::class, 'create'])->name('campaigns.create');
        Route::post('campaigns', [DiscoveryCampaignController::class, 'store'])->name('campaigns.store');
        Route::post('campaigns/{campaign}/run', [DiscoveryCampaignController::class, 'run'])->name('campaigns.run');
        Route::get('prospects', [ProspectController::class, 'index'])->name('prospects.index');
        Route::get('prospects/{prospect}', [ProspectController::class, 'show'])->name('prospects.show');
        Route::post('prospects/{prospect}/enrich', [ProspectController::class, 'enrich'])->name('prospects.enrich');
        Route::post('prospects/{prospect}/outreach', [ProspectController::class, 'generateOutreach'])->name('prospects.outreach.generate');
        Route::post('prospects/{prospect}/replies', [ProspectController::class, 'storeReply'])->name('prospects.replies.store');
        Route::post('prospects/{prospect}/pause-follow-ups', [ProspectController::class, 'pauseFollowUps'])->name('prospects.follow-ups.pause');
        Route::post('outreaches/{outreach}/approve', [ProspectController::class, 'approveOutreach'])->name('outreaches.approve');
        Route::post('outreaches/{outreach}/mark-sent', [ProspectController::class, 'markOutreachSent'])->name('outreaches.mark-sent');
    });

require __DIR__.'/settings.php';
