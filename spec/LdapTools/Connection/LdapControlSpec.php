<?php
/**
 * This file is part of the LdapTools package.
 *
 * (c) Chad Sikorra <Chad.Sikorra@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace spec\LdapTools\Connection;

use LdapTools\Connection\LdapControlType;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class LdapControlSpec extends ObjectBehavior
{
    function let()
    {
        $this->beConstructedWith(LdapControlType::SUB_TREE_DELETE);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('LdapTools\Connection\LdapControl');
    }

    function it_should_set_the_oid_in_the_constructor()
    {
        $this->getOid()->shouldBeEqualTo(LdapControlType::SUB_TREE_DELETE);
    }

    function it_should_be_able_to_set_the_criticality_and_value_in_the_constructor()
    {
        $this->beConstructedWith(LdapControlType::SUB_TREE_DELETE, true, 'foo');
        $this->getOid()->shouldBeEqualTo(LdapControlType::SUB_TREE_DELETE);
        $this->getCriticality()->shouldBeEqualTo(true);
        $this->getValue()->shouldBeEqualTo('foo');
    }

    function it_should_have_a_default_criticality_of_false()
    {
        $this->getCriticality()->shouldBeEqualTo(false);
    }

    function it_should_have_a_default_value_of_null()
    {
        $this->getValue()->shouldBeNull();
    }

    function it_should_set_the_oid()
    {
        $this->setOid(LdapControlType::SHOW_DELETED);
        $this->getOid()->shouldBeEqualTo(LdapControlType::SHOW_DELETED);
    }

    function it_should_set_the_criticality()
    {
        $this->setCriticality(true);
        $this->getCriticality()->shouldBeEqualTo(true);
    }

    function it_should_set_the_value()
    {
        $this->setValue(false);
        $this->getValue()->shouldBeEqualTo(false);
    }

    function it_should_chain_the_setters()
    {
        $this->setOid(LdapControlType::SHOW_DELETED)->shouldBeAnInstanceOf('LdapTools\Connection\LdapControl');
        $this->setValue(false)->shouldBeAnInstanceOf('LdapTools\Connection\LdapControl');
        $this->setCriticality(true)->shouldBeAnInstanceOf('LdapTools\Connection\LdapControl');
    }

    function it_should_get_the_array_structure_of_the_control()
    {
        $this->toArray()->shouldBeEqualTo([
           'oid' => LdapControlType::SUB_TREE_DELETE,
            'iscritical' => false
        ]);

        $this->setCriticality(true);
        $this->setValue(false);

        $this->toArray()->shouldBeEqualTo([
            'oid' => LdapControlType::SUB_TREE_DELETE,
            'iscritical' => true,
            'value' => false
        ]);
    }
}
