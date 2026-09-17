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

// Soal-update notifications for an ujian: only a siswa currently taking that
// specific ujian (peserta berstatus "sedang") may listen, plus admin/guru so
// the monitoring page can also react.
Broadcast::channel('ujian.{ujianId}.soal', function ($user, $ujianId) {
    if ($user->isSuperAdmin() || $user->isAdmin() || $user->isGuru()) {
        return true;
    }

    if ($user->isSiswa() && $user->siswa) {
        return \App\Models\PesertaUjian::where('ujian_id', $ujianId)
            ->where('siswa_id', $user->siswa->id)
            ->where('status', 'sedang')
            ->exists();
    }

    return false;
});
