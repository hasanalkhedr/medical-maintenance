<?php
namespace App\Mail;

use App\Models\Quote;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class QuoteMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Quote $quote,
        public string $pdfPath,
        public ?string $message = null
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Quote #'.$this->quote->quote_number.' from '.$this->quote->company->trade_name,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.quote',
            with: [
                'quote' => $this->quote,
                'message' => $this->message,
            ],
        );
    }

    public function attachments(): array
    {
        return [
            Attachment::fromPath($this->pdfPath)
                ->as('Quote-'.$this->quote->quote_number.'.pdf')
                ->withMime('application/pdf'),
        ];
    }
}
