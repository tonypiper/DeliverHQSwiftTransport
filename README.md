This is a simple Transport for using SwiftMailer (http://swiftmailer.org/) with Deliver HQ (http://www.deliverhq.com/).

To use it with Symfony 1.x store the tp_DeliverHqTransport.class.php in your lib directory, and edit your factories.yml:

```yaml
mailer:
  class: sfMailer
  param:
    delivery_strategy: realtime
    transport:
      class: tp_DeliverHqTransport
      param:
        username:   <the smtp username shown in the DeliverHQ SMTP settings>
        password:   <the smtp password shown in the DeliverHQ SMTP settings>
```

Then you can use it like a standard SMTP transport.

When you've sent your message(s) you can get Deliver HQ's unique message identifier by using

```php
$identifier=$mailer->getTransport()->getIdentifier(<email address>)
```

You can then use this identifier with their notifiation API to see when a message was delievered or bounced.