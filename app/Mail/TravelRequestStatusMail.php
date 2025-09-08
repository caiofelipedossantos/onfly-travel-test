<?php

namespace App\Mail;

use App\Models\TravelRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TravelRequestStatusMail extends Mailable
{
    use Queueable, SerializesModels;

    public TravelRequest $travelRequest;
    public string $action;

    /**
     * Create a new message instance.
     */
    public function __construct(TravelRequest $travelRequest, string $action)
    {
        $this->travelRequest = $travelRequest;
        $this->action = $action;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $subject = $this->action === 'approved'
            ? "Seu pedido de viagem foi aprovado"
            : "Seu pedido de viagem foi cancelado";

        return new Envelope(
            subject: $subject,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.travel_request_status',
            with: [
                'travelRequest' => $this->travelRequest,
                'action' => $this->action,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
