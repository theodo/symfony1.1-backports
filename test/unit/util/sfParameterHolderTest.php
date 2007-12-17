<?php

/*
 * This file is part of the symfony package.
 * (c) 2004-2006 Fabien Potencier <fabien.potencier@symfony-project.com>
 * 
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

require_once(dirname(__FILE__).'/../../bootstrap/unit.php');

$t = new lime_test(32, new lime_output_color());

// ->clear()
$t->diag('->clear()');
$ph = new sfParameterHolder();
$ph->clear();
$t->is($ph->getAll(), null, '->clear() clears all parameters');

$ph->set('foo', 'bar');
$ph->clear();
$t->is($ph->getAll(), null, '->clear() clears all parameters');

// ->get()
$t->diag('->get()');
$ph = new sfParameterHolder();
$ph->set('foo', 'bar');
$t->is($ph->get('foo'), 'bar', '->get() returns the parameter value for the given key');
$t->is($ph->get('bar'), null, '->get() returns null if the key does not exist');

$ph = new sfParameterHolder();
$t->is('default_value', $ph->get('foo1', 'default_value'), '->get() takes the default value as its second argument');

$ph = new sfParameterHolder();
$ph->add(array('foo' => array(
  'bar' => array(
    'baz' => 'foo bar',
  ),
  'bars' => array('foo', 'bar'),
)));
$t->is($ph->get('foo[bar][baz]'), 'foo bar', '->get() can take a multi-array key');
$t->is($ph->get('foo[bars][1]'), 'bar', '->get() can take a multi-array key');
$t->is($ph->get('foo[bars][2]'), null, '->get() returns null if the key does not exist');
$t->is($ph->get('foo[bars][]'), array('foo', 'bar'), '->get() returns an array');
$t->is($ph->get('foo[bars][]'), $ph->get('foo[bars]'), '->get() returns an array even if you omit the []');

// ->getNames()
$t->diag('->getNames()');
$ph = new sfParameterHolder();
$ph->set('foo', 'bar');
$ph->set('yourfoo', 'bar');

$t->is($ph->getNames(), array('foo', 'yourfoo'), '->getNames() returns all key names');

// ->getAll()
$t->diag('->getAll()');
$parameters = array('foo' => 'bar', 'myfoo' => 'bar');
$ph = new sfParameterHolder();
$ph->add($parameters);
$t->is($ph->getAll(), $parameters, '->getAll() returns all parameters');

// ->has()
$t->diag('->has()');
$ph = new sfParameterHolder();
$ph->set('foo', 'bar');
$t->is($ph->has('foo'), true, '->has() returns true if the key exists');
$t->is($ph->has('bar'), false, '->has() returns false if the key does not exist');

$ph = new sfParameterHolder();
$ph->add(array('foo' => array(
  'bar' => array(
    'baz' => 'foo bar',
  ),
  'bars' => array('foo', 'bar'),
)));
$t->is($ph->has('foo[bar][baz]'), true, '->has() can takes a multi-array key');
$t->is($ph->get('foo[bars][1]'), true, '->has() can takes a multi-array key');
$t->is($ph->get('foo[bars][2]'), false, '->has() returns null is the key does not exist');
$t->is($ph->has('foo[bars][]'), true, '->has() returns true if an array exists');
$t->is($ph->get('foo[bars][]'), $ph->has('foo[bars]'), '->has() returns true for an array even if you omit the []');

// ->remove()
$t->diag('->remove()');
$ph = new sfParameterHolder();
$ph->set('foo', 'bar');
$ph->set('myfoo', 'bar');

$ph->remove('foo');
$t->is($ph->has('foo'), false, '->remove() removes the key from parameters');

$ph->remove('myfoo');
$t->is($ph->has('myfoo'), false, '->remove() removes the key from parameters');

$t->is($ph->remove('nonexistant', 'foobar'), 'foobar', '->remove() takes a default value as its second argument');

$t->is($ph->getAll(), null, '->remove() removes the key from parameters');

// ->set()
$t->diag('->set()');
$foo = 'bar';

$ph = new sfParameterHolder();
$ph->set('foo', $foo);
$t->is($ph->get('foo'), $foo, '->set() sets the value for a key');

$foo = 'foo';
$t->is($ph->get('foo'), 'bar', '->set() sets the value for a key, not a reference');

// ->setByRef()
$t->diag('->setByRef()');
$foo = 'bar';

$ph = new sfParameterHolder();
$ph->setByRef('foo', $foo);
$t->is($ph->get('foo'), $foo, '->setByRef() sets the value for a key');

$foo = 'foo';
$t->is($ph->get('foo'), $foo, '->setByRef() sets the value for a key as a reference');

// ->add()
$t->diag('->add()');
$foo = 'bar';
$parameters = array('foo' => $foo, 'bar' => 'bar');
$myparameters = array('myfoo' => 'bar', 'mybar' => 'bar');

$ph = new sfParameterHolder();
$ph->add($parameters);

$t->is($ph->getAll(), $parameters, '->add() adds an array of parameters');

$foo = 'mybar';
$t->is($ph->getAll(), $parameters, '->add() adds an array of parameters, not a reference');

// ->addByRef()
$t->diag('->addByRef()');
$foo = 'bar';
$parameters = array('foo' => &$foo, 'bar' => 'bar');
$myparameters = array('myfoo' => 'bar', 'mybar' => 'bar');

$ph = new sfParameterHolder();
$ph->addByRef($parameters);

$t->is($parameters, $ph->getAll(), '->add() adds an array of parameters');

$foo = 'mybar';
$t->is($parameters, $ph->getAll(), '->add() adds a reference of an array of parameters');

// ->serialize() ->unserialize()
$t->diag('->serialize() ->unserialize()');
$t->ok($ph == unserialize(serialize($ph)), 'sfParameterHolder implements the Serializable interface');
