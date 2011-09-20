<?php

class tp_DeliverHqTransport implements Swift_Transport
{
  private $username;

  private $password;

  private $identifiers;

  private $statuses;

  private $uri;

  const DELIVERHQ_URI = 'https://api.deliverhq.com/api/send.json';

  public function __construct($uri=null)
  {
    $this->uri = is_null($uri) ? self::DELIVERHQ_URI : $uri;
  }

  public function setUsername($username)
  {
    $this->username=$username;
  }

  public function setPassword($password)
  {
    $this->password=$password;
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
   * Sends the given message.
   *
   * @param Swift_Mime_Message $message
   * @param string[] &$failedRecipients to collect failures by-reference
   *
   * @return int The number of sent emails
   */
  public function send(Swift_Mime_Message $message, &$failedRecipients = array())
  {

    $fromAddresses=array_keys($message->getFrom());
    $recipients = array_keys($message->getTo());

    $this->identifiers=array();

    $successCounter=0;

    foreach ($recipients as $recipient)
    {

      $parameters = array('to' => $recipient,
                          'from' => $fromAddresses[0],
                          'subject' => $message->getSubject(),
                          'plain_body' => $message->getBody()
      );

      $ch = curl_init($this->uri);

      curl_setopt($ch, CURLOPT_POSTFIELDS, $parameters);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
      curl_setopt($ch, CURLOPT_USERPWD, $this->username . ':' . $this->password);
      $output = curl_exec($ch);
      curl_close($ch);

      $response = json_decode($output, true);

      $status = $response['status'];
      if($status=='OK')
      {
        $successCounter++;
      }
      $this->identifiers[$recipient]= $response['identifier'];
    }


    return $successCounter;
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