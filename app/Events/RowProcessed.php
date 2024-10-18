<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RowProcessed implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $batchId;
    public $rowIndex;
    public $totalRows;

    /**
     * Create a new event instance.
     */
    public function __construct($batchId, $rowIndex, $totalRows)
    {
        $this->batchId = $batchId;
        $this->rowIndex = $rowIndex;
        $this->totalRows = $totalRows;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn()
    {
        return new Channel('excel-import');
        // return [
        //     new PrivateChannel('channel-name'),
        // ];
    }
}
