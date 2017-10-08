<?php

namespace Tests;

use Behat\MinkExtension\Context\RawMinkContext;
use Symfony\Component\HttpKernel\Profiler\Profiler;

class EmailContext extends RawMinkContext
{
    /**
     * @var Profiler
     */
    private $profiler;

    /**
     * EmailContext constructor.
     */
    public function __construct(Profiler $profiler)
    {
        $this->profiler = $profiler;
    }

    /** @BeforeScenario */
    public function before($event)
    {
        $client = $this->getMink()->getSession()->getDriver()->getClient();
        $client->disableReboot();
    }

    /** @AfterScenario */
    public function after($event)
    {
        $client = $this->getMink()->getSession()->getDriver()->getClient();
        $client->enableReboot();
    }

    /**
     * @BeforeScenario @enableProfiler
     */
    public function enableProfiler()
    {

        $this->profiler->enable();
    }

    /**
     * @BAfterScenario @enableProfiler
     */
    public function disableProfiler()
    {
        $this->profiler->disable();
    }


    /**
     * @Then /^no email should have been sent$/
     */
    public function noEmailShouldHaveBeenSent()
    {
        if (0 < $count = $this->loadProfile()->getCollector('swiftmailer')->getMessageCount()) {
            throw new \RuntimeException(sprintf('Expected no email to be sent, but %d emails were sent.', $count));
        }
    }

    /**
     * @Then email should be sent to :to
     * @Then email with subject :subject should be sent
     * @Then email with subject :subject should be sent to :to
     */
    public function emailWithSubjectShouldHaveBeenSent($subject = null, $to = null)
    {
        $mailer = $this->loadProfile()->getCollector('swiftmailer');
        if (0 === $mailer->getMessageCount()) {
            throw new \RuntimeException('No emails have been sent.');
        }
        $recipients = $to ? array_flip(explode(',', str_replace(' ', '', $to))) : null;
        $foundToAddresses = [];
        $foundSubjects = [];
        foreach ($mailer->getMessages('default') as $message) {
            /** @var \Swift_Message $message */
            $foundSubjects[$message->getSubject()] = true;
            $foundToAddresses = array_replace($foundToAddresses, $message->getTo());
        }

        if (null !== $subject && !isset($foundSubjects[$subject])) {
            if (!empty($foundSubjects)) {
                throw new \RuntimeException(sprintf('Subject "%s" was not found, but only these subjects: "%s"', $subject, implode('", "', array_keys($foundSubjects))));
            }
            // not found
            throw new \RuntimeException(sprintf('No message with subject "%s" found.', $subject));
        }

        if (null !== $recipients) {
            $diff = array_diff_key($recipients, $foundToAddresses);
            if (count($diff) > 0) {
                throw new \RuntimeException(sprintf('Subject found, but "%s" is not among to-addresses: "%s"', $to, implode('"", "', array_keys($foundToAddresses))));
            }
        }
    }

    /**
     * @Given /^should sent "([^"]*)" email$/
     * @Given /^should sent "([^"]*)" emails$/
     */
    public function shouldSentEmail($amount)
    {
        $mailer = $this->loadProfile()->getCollector('swiftmailer');
        if ($amount != $mailer->getMessageCount()) {
            throw new \RuntimeException(sprintf('Expected "%s" email to be sent, but %d emails were sent.', $amount ,$mailer->getMessageCount()));

        }
    }

    /**
     * Loads the profiler's profile.
     *
     * If no token has been given, the debug token of the last request will
     * be used.
     *
     * @param string $token
     * @return \Symfony\Component\HttpKernel\Profiler\Profile
     * @throws \RuntimeException
     */
    public function loadProfile($token = null)
    {
        if (null === $token) {
            $headers = $this->getSession()->getResponseHeaders();
            if (!isset($headers['X-Debug-Token']) && !isset($headers['x-debug-token'])) {
                throw new \RuntimeException('Debug-Token not found in response headers. Have you turned on the debug flag?');
            }
            $token = isset($headers['X-Debug-Token']) ? $headers['X-Debug-Token'] : $headers['x-debug-token'];
            if (is_array($token)) {
                $token = end($token);
            }
        }
        return $this->profiler->loadProfile($token);
    }

}
