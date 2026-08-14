<?php

namespace App\Listeners;

use App\Events\OrderReceivedNotifySellerEvent;
use Illuminate\Support\Facades\Mail;
use Illuminate\Contracts\Queue\ShouldQueue;

class OrderReceivedNotifySellerListener implements ShouldQueue
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(OrderReceivedNotifySellerEvent $event): void
    {
        $this->sendMail($event);
    }

    private function sendMail(OrderReceivedNotifySellerEvent $event):void{
        $orderId = $event->orderId;
        $email = $event->email;
        try{
            Mail::to($email)->send(new \App\Mail\OrderReceivedNotifySeller($orderId));
        }catch(\Exception $exception) {
            info($exception);
        }
    }
}
