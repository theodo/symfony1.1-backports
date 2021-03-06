<?php

/**
 * sfMessageSource_Creole class file.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the BSD License.
 *
 * Copyright(c) 2004 by Qiang Xue. All rights reserved.
 *
 * To contact the author write to {@link mailto:qiang.xue@gmail.com Qiang Xue}
 * The latest version of PRADO can be obtained from:
 * {@link http://prado.sourceforge.net/}
 *
 * @author     Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @version    $Id$
 * @package    symfony
 * @subpackage i18n
 */

/*
CREATE TABLE `catalogue` (
  `cat_id` int(11) NOT NULL auto_increment,
  `name` varchar(100) NOT NULL default '',
  `source_lang` varchar(100) NOT NULL default '',
  `target_lang` varchar(100) NOT NULL default '',
  `date_created` int(11) NOT NULL default '0',
  `date_modified` int(11) NOT NULL default '0',
  `author` varchar(255) NOT NULL default '',
  PRIMARY KEY  (`cat_id`)
);

CREATE TABLE `trans_unit` (
  `msg_id` int(11) NOT NULL auto_increment,
  `cat_id` int(11) NOT NULL default '1',
  `source` text NOT NULL,
  `target` text NOT NULL,
  `comments` text NOT NULL,
  `date_added` int(11) NOT NULL default '0',
  `date_modified` int(11) NOT NULL default '0',
  `author` varchar(255) NOT NULL default '',
  `translated` tinyint(1) NOT NULL default '0',
  PRIMARY KEY  (`msg_id`)
);

*/

/**
 * sfMessageSource_Creole class.
 *
 * Retrieve the message translation from a Creole supported database.
 *
 * See the MessageSource::factory() method to instantiate this class.
 *
 * @author RoVeRT <symfony[at]rovert[dot]net>
 */
class sfMessageSource_Creole extends sfMessageSource_Database
{
  /**
   * A resource link to the database
   * @var db
   */
  protected $db;

  /**
   * Constructor.
   * Create a new message source using Creole.
   * @param string $source Creole datasource.
   * @see MessageSource::factory();
   */
  public function __construct($source)
  {
    $this->db = sfContext::getInstance()->getDatabaseConnection($source);
    if ($this->db == null || !$this->db instanceof Connection)
    {
      throw new sfDatabaseException('Creole dabatase connection doesn\'t exist. Unable to open session.');
    }
  }

  /**
   * Destructor, close the database connection.
   */
  public function __destruct()
  {
  }

  /**
   * Get the database connection.
   * @return db database connection.
   */
  public function connection()
  {
    return $this->db;
  }

  /**
   * Get an array of messages for a particular catalogue and cultural
   * variant.
   * @param string $variant the catalogue name + variant
   * @return array translation messages.
   */
  public function &loadData($variant)
  {
    $sql = 'SELECT t.source, t.target, t.comments '.
           'FROM trans_unit t, catalogue c '.
           'WHERE c.cat_id =  t.cat_id AND c.name = ? '.
           'ORDER BY msg_id ASC';

    $stmt = $this->db->prepareStatement($sql);

    $rs = $stmt->executeQuery(array($variant), ResultSet::FETCHMODE_NUM);

    $result = array();

    $count = 0;
    while ($rs->next())
    {
      $source = $rs->getString(1);
      $result[$source][] = $rs->getString(2); //target
      $result[$source][] = $count++;          //id
      $result[$source][] = $rs->getString(3); //comments
    }

    return $result;
  }

  /**
   * Get the last modified unix-time for this particular catalogue+variant.
   * We need to query the database to get the date_modified.
   *
   * @param string $source catalogue+variant
   * @return int last modified in unix-time format.
   */
  protected function getLastModified($source)
  {
    $sql = 'SELECT date_modified FROM catalogue WHERE name = ?';

    $stmt = $this->db->prepareStatement($sql);

    $rs = $stmt->executeQuery(array($source), ResultSet::FETCHMODE_NUM);

    $result = $rs->next() ? $rs->getInt(1) : 0;

    return $result;
  }

  /**
   * Check if a particular catalogue+variant exists in the database.
   *
   * @param string $variant catalogue+variant
   * @return boolean true if the catalogue+variant is in the database, false otherwise.
   */
  public function isValidSource($variant)
  {
    $sql = 'SELECT COUNT(*) FROM catalogue WHERE name = ?';

    $stmt = $this->db->prepareStatement($sql);

    $rs = $stmt->executeQuery(array($variant), ResultSet::FETCHMODE_NUM);

    $result = $rs->next() ? $rs->getInt(1) == 1 : false;

    return $result;
  }

  /**
   * Retrieve catalogue details, array($catId, $variant, $count).
   *
   * @param string $catalogue catalogue
   * @return array catalogue details, array($catId, $variant, $count).
   */
  protected function getCatalogueDetails($catalogue = 'messages')
  {
    if (empty($catalogue))
    {
      $catalogue = 'messages';
    }

    $variant = $catalogue.'.'.$this->culture;

    $name = $this->getSource($variant);

    $sql = 'SELECT cat_id FROM catalogue WHERE name = ?';

    $stmt = $this->db->prepareStatement($sql);

    $rs = $stmt->executeQuery(array($name), ResultSet::FETCHMODE_NUM);

    if ($rs->getRecordCount() != 1)
    {
      return false;
    }

    $rs->next();

    $catId = $rs->getInt(1);

    //first get the catalogue ID
    $sql = 'SELECT count(msg_id) FROM trans_unit WHERE cat_id = ?';

    $stmt = $this->db->prepareStatement($sql);

    $rs = $stmt->executeQuery(array($catId), ResultSet::FETCHMODE_NUM);

    $rs->next();
    $count = $rs->getInt(1);

    return array($catId, $variant, $count);
  }

  /**
   * Update the catalogue last modified time.
   *
   * @return boolean true if updated, false otherwise.
   */
  protected function updateCatalogueTime($catId, $variant)
  {
    $time = time();

    $sql = 'UPDATE catalogue SET date_modified = ? WHERE cat_id = ?';

    $stmt = $this->db->prepareStatement($sql);

    $result = $stmt->executeUpdate(array($time, $catId));

    if (!empty($this->cache))
    {
      $this->cache->clean($variant, $this->culture);
    }

    return true;
  }

  /**
   * Save the list of untranslated blocks to the translation source.
   * If the translation was not found, you should add those
   * strings to the translation source via the <b>append()</b> method.
   *
   * @param string $catalogue the catalogue to add to
   * @return boolean true if saved successfuly, false otherwise.
   */
  function save($catalogue='messages')
  {
    $messages = $this->untranslated;

    if (count($messages) <= 0)
    {
      return false;
    }

    $details = $this->getCatalogueDetails($catalogue);

    if ($details)
    {
      list($catId, $variant, $count) = $details;
    }
    else
    {
      return false;
    }

    if ($catId <= 0)
    {
      return false;
    }
    $inserted = 0;

    $time = time();

    try
    {
      $sql = 'SELECT msg_id FROM trans_unit WHERE source = ?';

      $stmt = $this->db->prepareStatement($sql);

      foreach($messages as $key => $message)
      {
        $rs = $stmt->executeQuery(array($message), ResultSet::FETCHMODE_NUM);
        if ($rs->next())
        {
           unset($messages[$key]);
        }
      }
    }
    catch (Exception $e)
    {
    }

    try
    {
      $this->db->begin();

      $sql = 'INSERT INTO trans_unit (cat_id, source, target, comments, date_added, date_modified) VALUES (?, ?, ?, ?, ?, ?)';

      $stmt = $this->db->prepareStatement($sql);

      foreach ($messages as $message)
      {
        $stmt->executeUpdate(array($catId, $message, '', '', $time, $time));
        ++$inserted;
      }

      $this->db->commit();
    }
    catch (Exception $e)
    {
      $this->db->rollback();
    }

    if ($inserted > 0)
    {
      $this->updateCatalogueTime($catId, $variant);
    }

    return $inserted > 0;
  }

  /**
   * Delete a particular message from the specified catalogue.
   *
   * @param string $message   the source message to delete.
   * @param string $catalogue the catalogue to delete from.
   * @return boolean true if deleted, false otherwise.
   */
  function delete($message, $catalogue='messages')
  {
    $details = $this->getCatalogueDetails($catalogue);

    if ($details)
    {
      list($catId, $variant, $count) = $details;
    }
    else
    {
      return false;
    }

    $deleted = false;

    $sql = 'DELETE FROM trans_unit WHERE cat_id = ? AND source = ?';

    $stmt = $this->db->prepareStatement($sql);

    $rows = $stmt->executeUpdate(array($catId, $message));

    if ($rows == 1)
    {
      $deleted = $this->updateCatalogueTime($catId, $variant);
    }

    return $deleted;
  }

  /**
   * Update the translation.
   *
   * @param string $text      the source string.
   * @param string $target    the new translation string.
   * @param string $comments  comments
   * @param string $catalogue the catalogue of the translation.
   * @return boolean true if translation was updated, false otherwise.
   */
  function update($text, $target, $comments, $catalogue = 'messages')
  {
    $details = $this->getCatalogueDetails($catalogue);
    if ($details)
    {
      list($catId, $variant, $count) = $details;
    }
    else
    {
      return false;
    }

    $time = time();

    $sql = 'UPDATE trans_unit SET target = ?, comments = ?, date_modified = ? WHERE cat_id = ? AND source = ?';

    $updated = false;

    $stmt = $this->db->prepareStatement($sql);

    $rows = $stmt->executeUpdate(array($target, $comments, $time, $catId, $text));

    if ($rows == 1)
    {
      $updated = $this->updateCatalogueTime($catId, $variant);
    }

    return $updated;
  }

  /**
   * Returns a list of catalogue as key and all it variants as value.
   *
   * @return array list of catalogues
   */
  function catalogues()
  {
    $sql = 'SELECT name FROM catalogue ORDER BY name';

    $rs = $this->db->executeQuery($sql, ResultSet::FETCHMODE_NUM);

    $result = array();
    while ($rs->next())
    {
      $details = explode('.', $rs->getString(1));
      if (!isset($details[1]))
      {
        $details[1] = null;
      }

      $result[] = $details;
    }

    return $result;
  }
}
