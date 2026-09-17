<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Queue\SerializesModels;

/**
 * Broadcast to students currently taking an ujian when one of its soal is
 * edited by an admin/guru, so the client can pull the fresh content without
 * a page reload. Only the ids are sent — the client re-fetches the soal
 * through the authenticated exam endpoint, which applies the same per-peserta
 * opsi shuffling used on the initial page load.
 */
class SoalUpdated implements ShouldBroadcastNow
{
    use InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $ujianId,
        public int $bankSoalId,
    ) {
    }

    /**
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("ujian.{$this->ujianId}.soal"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'soal.updated';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'bank_soal_id' => $this->bankSoalId,
        ];
    }
}
