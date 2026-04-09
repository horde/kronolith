<?xml version="1.0" encoding="utf-8"?>
<?xml-stylesheet href="<?php echo $this->xsl ?>" type="text/xsl"?>
<feed xmlns="http://www.w3.org/2005/Atom">

  <title><?php echo $this->calendar_name ?></title>
<?php if (!empty($this->calendar_desc)): ?>
  <subtitle><?php echo $this->calendar_desc ?></subtitle>
<?php endif ?>
  <id><?php echo $this->self_url ?></id>
  <link rel="self" href="<?php echo $this->self_url ?>"/>
  <author>
    <name><?php echo $this->calendar_owner ?></name>
<?php if (!empty($this->calendar_email)): ?>
    <email><?php echo $this->calendar_email ?></email>
<?php endif ?>
  </author>
  <generator uri="<?php echo $this->kronolith_uri ?>" version="<?php echo $this->kronolith_version ?>"><?php echo $this->kronolith_name ?></generator>
  <icon><?php echo $this->kronolith_icon ?></icon>
  <updated><?php echo $this->updated ?></updated>

<?php foreach ($this->entries as $entry): ?>
  <entry>
    <title><?php echo $entry['title'] ?></title>
    <link href="<?php echo $entry['url'] ?>"/>
    <id><?php echo $entry['url'] ?></id>
    <updated><?php echo $entry['modified'] ?></updated>
    <summary type="html"><?php echo $entry['desc'] ?></summary>
<?php if (!empty($entry['category'])): ?>
    <category term="<?php echo $entry['category'] ?>" />
<?php endif ?>
  </entry>
<?php endforeach ?>

</feed>
