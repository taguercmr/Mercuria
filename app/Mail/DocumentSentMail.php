<?php

namespace App\Mail;

use App\Models\Document;
use App\Models\Tenant;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DocumentSentMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Document $document,
        public Tenant $tenant,
        public ?string $customMessage = null,
    ) {}

    public function envelope(): Envelope
    {
        $label = $this->document->typeLabel();
        $number = $this->document->number ?: '#'.$this->document->id;

        return new Envelope(
            subject: "{$label} {$number} — {$this->tenant->name}",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.document-sent',
            with: [
                'document' => $this->document,
                'tenant' => $this->tenant,
                'customMessage' => $this->customMessage,
                'typeLabel' => $this->document->typeLabel(),
            ],
        );
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        $pdf = Pdf::loadView('pdf.document', [
            'document' => $this->document->loadMissing(['client', 'lines']),
            'tenant' => $this->tenant,
            'typeLabel' => $this->document->typeLabel(),
        ])->setPaper('a4')->output();

        $filename = ($this->document->number ?: 'document-'.$this->document->id).'.pdf';

        return [
            Attachment::fromData(fn () => $pdf, $filename)
                ->withMime('application/pdf'),
        ];
    }
}
