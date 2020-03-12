<?php

namespace KaufmannDigital\GDPR\CookieConsent\Domain\Model;


use Neos\Flow\Annotations as Flow;

/**
 * Class ConsentLogEntry
 * @package KaufmannDigital\GDPR\CookieConsent\Domain\Model
 * @author Niklas Droste <nd@kaufmann.digital>
 *
 * @Flow\Entity
 */
class ConsentLogEntry
{

    /**
     * @var \DateTime
     */
    protected $consentDate;

    /**
     * @var string
     */
    protected $consentIdentifier;

    /**
     * @var string
     */
    protected $userId;


    public function __construct(string $userIdentifier, \DateTime $consentDate, string $consentIdentifier)
    {
        $this->userId = $userIdentifier;
        $this->consentDate = $consentDate;
        $this->consentIdentifier = $consentIdentifier;
    }
}
