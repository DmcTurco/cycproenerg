<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ExcelProcessingFailed implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $error;

    public function __construct($error)
    {
        $this->error = $error;
    }

    public function broadcastOn()
    {
        return new PrivateChannel('excel-processing');
    }

    public function broadcastWith()
{
    return [
        'status' => 'error',
        'message' => $this->error
    ];
}

}