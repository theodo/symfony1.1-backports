<?php

/*
 * This file is part of the symfony package.
 * (c) 2004-2006 Fabien Potencier <fabien.potencier@symfony-project.com>
 * (c) 2004-2006 Sean Kerr.
 * 
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * sfFilter provides a way for you to intercept incoming requests or outgoing responses.
 *
 * @package    symfony
 * @subpackage filter
 * @author     Fabien Potencier <fabien.potencier@symfony-project.com>
 * @author     Sean Kerr <skerr@mojavi.org>
 * @version    SVN: $Id$
 */
abstract class sfFilter
{
  protected
    $parameterHolder = null,
    $context         = null;

  public static
    $filterCalled    = array();

  /**
   * Class constructor.
   *
   * @see initialize()
   */
  public function __construct($context, $parameters = array())
  {
    $this->initialize($context, $parameters);
  }

  /**
   * Initializes this Filter.
   *
   * @param sfContext The current application context
   * @param array   An associative array of initialization parameters
   *
   * @return boolean true, if initialization completes successfully, otherwise false
   *
   * @throws <b>sfInitializationException</b> If an error occurs while initializing this Filter
   */
  public function initialize($context, $parameters = array())
  {
    $this->context = $context;

    $this->parameterHolder = new sfParameterHolder();
    $this->parameterHolder->add($parameters);

    return true;
  }

  /**
   * Returns true if this is the first call to the sfFilter instance.
   *
   * @return boolean true if this is the first call to the sfFilter instance, false otherwise
   */
  protected function isFirstCall()
  {
    $class = get_class($this);
    if (isset(self::$filterCalled[$class]))
    {
      return false;
    }
    else
    {
      self::$filterCalled[$class] = true;

      return true;
    }
  }

  /**
   * Retrieves the current application context.
   *
   * @return sfContext The current sfContext instance
   */
  public final function getContext()
  {
    return $this->context;
  }

  /**
   * Gets the parameter holder for this object.
   *
   * @return sfParameterHolder A sfParameterHolder instance
   */
  public function getParameterHolder()
  {
    return $this->parameterHolder;
  }

  /**
   * Gets the parameter associated with the given key.
   *
   * This is a shortcut for:
   *
   * <code>$this->getParameterHolder()->get()</code>
   *
   * @param string The key name
   * @param string The default value
   *
   * @return string The value associated with the key
   *
   * @see sfParameterHolder
   */
  public function getParameter($name, $default = null)
  {
    return $this->parameterHolder->get($name, $default);
  }

  /**
   * Returns true if the given key exists in the parameter holder.
   *
   * This is a shortcut for:
   *
   * <code>$this->getParameterHolder()->has()</code>
   *
   * @param string The key name
   *
   * @return boolean true if the given key exists, false otherwise
   *
   * @see sfParameterHolder
   */
  public function hasParameter($name)
  {
    return $this->parameterHolder->has($name);
  }

  /**
   * Sets the value for the given key.
   *
   * This is a shortcut for:
   *
   * <code>$this->getParameterHolder()->set()</code>
   *
   * @param string The key name
   * @param string The value
   *
   * @see sfParameterHolder
   */
  public function setParameter($name, $value)
  {
    return $this->parameterHolder->set($name, $value);
  }
}
