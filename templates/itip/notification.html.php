<div class="kronolith-itip-notification" style="font-family: Arial, Helvetica, sans-serif; max-width: 640px; color: #222;">
  <h2 style="margin: 0 0 12px; font-size: 20px; font-weight: 600;"><?php echo $this->h($this->header) ?></h2>

  <table style="width: 100%; border-collapse: collapse; margin: 0 0 16px;">
    <tr>
      <td style="padding: 8px 12px 8px 0; vertical-align: top; width: 110px; color: #555; font-size: 14px;"><?php echo _("When") ?></td>
      <td style="padding: 8px 0; font-size: 14px;">
        <strong><?php echo (new IntlDateFormatter($GLOBALS['language'], IntlDateFormatter::SHORT, IntlDateFormatter::MEDIUM))->format($this->event->start->timestamp()) ?></strong>
        –
        <strong><?php echo (new IntlDateFormatter($GLOBALS['language'], IntlDateFormatter::SHORT, IntlDateFormatter::MEDIUM))->format($this->event->end->timestamp()) ?></strong>
      </td>
    </tr>
    <?php if (strlen($this->recurrence)): ?>
    <tr>
      <td style="padding: 8px 12px 8px 0; vertical-align: top; color: #555; font-size: 14px;"><?php echo _("Recurrence") ?></td>
      <td style="padding: 8px 0; font-size: 14px;"><?php echo nl2br($this->h($this->recurrence)) ?></td>
    </tr>
    <?php endif ?>
    <?php if (strlen($this->event->location)): ?>
    <tr>
      <td style="padding: 8px 12px 8px 0; vertical-align: top; color: #555; font-size: 14px;"><?php echo _("Location") ?></td>
      <td style="padding: 8px 0; font-size: 14px;"><strong><?php echo $this->h($this->event->location) ?></strong></td>
    </tr>
    <?php endif ?>
    <?php if (!empty($this->organizer)): ?>
    <tr>
      <td style="padding: 8px 12px 8px 0; vertical-align: top; color: #555; font-size: 14px;"><?php echo _("Organizer") ?></td>
      <td style="padding: 8px 0; font-size: 14px;"><strong><?php echo $this->h($this->organizer) ?></strong></td>
    </tr>
    <?php endif ?>
    <?php if (count($this->event->attendees)): ?>
    <tr>
      <td style="padding: 8px 12px 8px 0; vertical-align: top; color: #555; font-size: 14px;"><?php echo _("Attendees") ?></td>
      <td style="padding: 8px 0; font-size: 14px;">
        <?php foreach ($this->event->attendees as $attendee): ?>
        <div>
          <?php if (is_null($attendee->addressObject->host)): ?>
          <?php echo $this->h($attendee->displayName) ?>
          <?php else: ?>
          <a href="mailto:<?php echo $this->h($attendee->email) ?>"><?php echo $this->h($attendee->displayName) ?></a>
          <?php endif ?>
        </div>
        <?php endforeach ?>
      </td>
    </tr>
    <?php endif ?>
    <?php if (strlen($this->event->description)): ?>
    <tr>
      <td style="padding: 8px 12px 8px 0; vertical-align: top; color: #555; font-size: 14px;"><?php echo _("Description") ?></td>
      <td style="padding: 8px 0; font-size: 14px;">
        <?php echo $GLOBALS['injector']->getInstance('Horde_Core_Factory_TextFilter')->filter($this->event->description, 'text2html', ['parselevel' => Horde_Text_Filter_Text2html::MICRO, 'callback' => null]) ?>
      </td>
    </tr>
    <?php endif ?>
  </table>

  <?php if ($this->linkAccept): ?>
  <p style="font-size: 14px; margin: 16px 0 0;">
    <?php printf(
        _("If your email client does not show invitation actions, you can %saccept%s, respond %stentatively%s, or %sdecline%s online."),
        '<a href="' . htmlspecialchars($this->linkAccept) . '"><strong>',
        '</strong></a>',
        '<a href="' . htmlspecialchars($this->linkTentative) . '"><strong>',
        '</strong></a>',
        '<a href="' . htmlspecialchars($this->linkDecline) . '"><strong>',
        '</strong></a>'
    ) ?>
  </p>
  <?php endif ?>
</div>
