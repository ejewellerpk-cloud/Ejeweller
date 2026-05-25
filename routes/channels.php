<?php

use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('analytics.site.{siteId}', function ($user, $siteId) {
    return \App\Analytics\Models\AnalyticsSite::query()
        ->where('id', (int) $siteId)
        ->where(function ($q) use ($user) {
            $q->whereHas('members', fn ($m) => $m->where('user_id', $user->id))
                ->orWhereHas('workspace', fn ($w) => $w->where('owner_id', $user->id));
        })
        ->exists();
});
