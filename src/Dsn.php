<?php

namespace App;

use ZBateson\MailMimeParser\MailMimeParser;

/**
 * A Delivery Status Notification (DSN), or simply a bounce,
 * is an automated electronic mail message from a mail system informing
 * the sender of another message about a delivery problem.
 */
class Dsn
{
    private $dsnHeaders = [];

    public function getAllHeaders(): array
    {
        return $this->dsnHeaders;
    }

    /**
     * Is this a hard bounce? false = soft
     */
    public function isHard(): bool
    {
        return substr($this->dsnHeaders['Status'], 0, 1) == '5';
    }

    /**
     * Is this an outgoing or incoming bounce?
     */
    public function isOutgoing(): bool
    {
        if (isset($this->dsnHeaders['Return-Path'])) {
            return true; //isset($this->dsnHeaders['Return-Path']);
        } else {
            return isset($this->dsnHeaders['original']['Return-Path']);
        }
    }

    public function parse($email): self
    {
        $parser = new MailMimeParser();
        $handle = fopen($email, 'r');
        $message = $parser->parse($handle, false);
        $original = null;

        foreach ($message->getAllParts() as $part) {
            switch ($part->getContentType()) {
                case 'message/delivery-status':
                    $dsn = $parser->parse($part->getContentStream(), false);
                    $report = $parser->parse($dsn->getContentStream(), false);
                    break;
                case 'text/rfc822-headers':
                    $original = $parser->parse($part->getContentStream(), false);
                    break;
                case 'message/rfc822':
                    $original = $parser->parse($part->getContentStream(), false);
                    break;
            }
        }

        if ($original) {
            foreach ($original->getAllHeaders() as $header) {
                if ($header->getName() == 'X-Mailgun-Variables') {
                    $this->dsnHeaders['original'][$header->getName()] = $header->getRawValue();
                } else {
                    $this->dsnHeaders['original'][$header->getName()] = $header->getValue();
                }
            }
        }

        foreach ($report->getAllHeaders() as $header) {
            $this->dsnHeaders[$header->getName()] = $header->getValue();
        }
        fclose($handle);

        return $this;
    }
}
