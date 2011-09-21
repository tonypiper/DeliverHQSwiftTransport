<?php

class tp_DeliverHqTransport implements Swift_Transport
{
  private $username;

  private $password;

  private $identifiers;

  private $statuses;

  private $uri;

  const DELIVERHQ_URI = 'https://api.deliverhq.com/api/send.json';

  public function __construct($uri = null, $username = null, $password = null)
  {
    $this->uri = is_null($uri) ? self::DELIVERHQ_URI : $uri;
    $this->username = $username;
    $this->password = $password;
  }

  public function setUsername($username)
  {
    $this->username = $username;
  }

  public function setPassword($password)
  {
    $this->password = $password;
  }

  public function isStarted()
  {
    return false;
  }

  public function start()
  {

  }

  public function stop()
  {

  }

  /**
   * @param Swift_Mime_Message $message
   * @param string $mime_type
   * @return Swift_Mime_MimePart
   */
  protected function getMIMEPart(Swift_Mime_Message $message, $mimeType = 'text/html')
  {
    $mimePart = NULL;
    foreach ($message->getChildren() as $part)
    {
      if (strpos($part->getContentType(), $mimeType) === 0)
        $mimePart = $part;
    }
    return $mimePart;
  }

  protected function validate(Swift_Mime_Message $message)
  {
    if ($message->getBcc() != null)
    {
      throw new Swift_TransportException('BCC is not supported');
    }

    if ($message->getCc() != null)
    {
      throw new Swift_TransportException('CC is not supported');
    }

    if (count($message->getTo()) != 1)
    {
      throw new Swift_TransportException('only one TO address is supported');
    }

  }

  /**
   * Sends the given message.
   *
   * @param Swift_Mime_Message $message
   * @param string[] &$failedRecipients to collect failures by-reference
   *
   * @return int The number of sent emails
   */
  public function send(Swift_Mime_Message $message, &$failedRecipients = array())
  {
    $this->validate($message);

    $fromAddresses = array_keys($message->getFrom());

    if (!isset($fromAddresses[0]))
    {
      throw new Swift_TransportException('from address must be set');
    }

    $recipients = array_keys($message->getTo());

    $recipient = $message->getHeaders()->get('To')->getFieldBody();
    $sender = $message->getHeaders()->get('From')->getFieldBody();

    $this->identifiers = array();

    $parameters = array('to' => $recipient,
                        'from' => $sender,
                        'subject' => $message->getSubject(),
                        'plain_body' => $message->getBody());

    if (!is_null($htmlPart = $this->getMIMEPart($message, 'text/html')))
    {
      $parameters['html_body'] = $htmlPart->getBody();
    }

    $output = $this->sendToApi($parameters);

    $response = json_decode($output, true);

    if (isset($response['status']) && $response['status'] == 'OK')
    {
      $successCounter = count($recipients);
      foreach ($recipients as $recipient)
      {
        $this->identifiers[$recipient] = $response['identifier'];
      }
    }
    else
    {
      $successCounter = 0;
      $failedRecipients=$recipients;
    }

    return $successCounter;
  }

  protected function sendToApi($parameters)
  {
    if (is_null($this->username) || is_null($this->password))
    {
      throw new Swift_TransportException('username and password must be set');
    }

    if (is_null($this->uri))
    {
      throw new Swift_TransportException('uri must be set');
    }

    try
    {
      $ch = curl_init($this->uri);

      curl_setopt($ch, CURLOPT_POSTFIELDS, $parameters);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
      curl_setopt($ch, CURLOPT_USERPWD, $this->username . ':' . $this->password);
      $output = curl_exec($ch);
      curl_close($ch);
      return $output;
    }
    catch (Exception $e)
    {
      throw new Swift_TransportException($e->getMessage());
    }
  }

  /**
   * Register a plugin.
   *
   * @param Swift_Events_EventListener $plugin
   */
  public function registerPlugin(Swift_Events_EventListener $plugin)
  {

  }

  public function getIdentifiers()
  {
    return $this->identifiers;
  }

  public function getIdentifier($address)
  {
    return isset($this->identifiers[$address]) ? $this->identifiers[$address] : null;
  }

  public function getStatuses()
  {
    return $this->statuses;
  }

  public function getStatus($address)
  {
    return isset($this->statuses[$address]) ? $this->statuses[$address] : null;
  }
}