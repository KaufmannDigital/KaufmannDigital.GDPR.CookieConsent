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

    /**
     * @var string
     */
    protected $userAgent;


    public function __construct(string $userIdentifier, \DateTime $consentDate, string $consentIdentifier, string $userAgent)
    {
        $this->userId = $userIdentifier;
        $this->consentDate = $consentDate;
        $this->consentIdentifier = $consentIdentifier;
        $this->userAgent = $userAgent;
    }

    /**
     * @return \DateTime
     */
    public function getConsentDate(): \DateTime
    {
        return $this->consentDate;
    }

    /**
     * @param \DateTime $consentDate
     */
    public function setConsentDate(\DateTime $consentDate)
    {
        $this->consentDate = $consentDate;
    }

    /**
     * @return string
     */
    public function getConsentIdentifier(): string
    {
        return $this->consentIdentifier;
    }

    /**
     * @param string $consentIdentifier
     */
    public function setConsentIdentifier(string $consentIdentifier)
    {
        $this->consentIdentifier = $consentIdentifier;
    }

    /**
     * @return string
     */
    public function getUserId(): string
    {
        return $this->userId;
    }

    /**
     * @param string $userId
     */
    public function setUserId(string $userId)
    {
        $this->userId = $userId;
    }

    /**
     * @return string
     */
    public function getUserAgent(): string
    {
        return $this->userAgent;
    }

    /**
     * @param string $userAgent
     */
    public function setUserAgent(string $userAgent)
    {
        $this->userAgent = $userAgent;
    }
}
