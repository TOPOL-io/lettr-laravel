<?php

namespace Lettr\Laravel\Transport;

use Exception;
use Lettr\Dto\SendEmailData;
use Lettr\Lettr;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\MessageConverter;

class LettrTransportFactory extends AbstractTransport
{
    /**
     * Create a new Lettr transport instance.
     */
    public function __construct(
        protected Lettr $lettr,
        protected array $config = []
    ) {
        parent::__construct();
    }

    /**
     * {@inheritDoc}
     */
    protected function doSend(SentMessage $message): void
    {
        $email = MessageConverter::toEmail($message->getOriginalMessage());
        $envelope = $message->getEnvelope();

        try {
            $emailData = new SendEmailData(
                from: $envelope->getSender()->toString(),
                to: $this->stringifyAddresses($this->getRecipients($email, $envelope)),
                subject: $email->getSubject() ?? '',
                text: $email->getTextBody(),
                html: $email->getHtmlBody(),
            );

            $result = $this->lettr->emails()->send($emailData);
        } catch (Exception $exception) {
            throw new TransportException(
                sprintf('Request to the Lettr API failed. Reason: %s', $exception->getMessage()),
                is_int($exception->getCode()) ? $exception->getCode() : 0,
                $exception
            );
        }

        $messageId = $result->id;

        $email->getHeaders()->addHeader('X-Lettr-Email-ID', $messageId);
    }

    /**
     * Get the recipients without CC or BCC.
     */
    protected function getRecipients(Email $email, Envelope $envelope): array
    {
        return array_filter($envelope->getRecipients(), function (Address $address) use ($email) {
            return in_array($address, array_merge($email->getCc(), $email->getBcc()), true) === false;
        });
    }

    /**
     * Get the string representation of the transport.
     */
    public function __toString(): string
    {
        return 'lettr';
    }
}

