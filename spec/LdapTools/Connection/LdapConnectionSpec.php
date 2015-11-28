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

use LdapTools\Event\SymfonyEventDispatcher;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use \LdapTools\DomainConfiguration;

class LdapConnectionSpec extends ObjectBehavior
{
    function let()
    {
        $config = new DomainConfiguration('example.com');
        $config->setServers(['test'])
            ->setBaseDn('dc=example,dc=local')
            ->setLazyBind(true);
        $this->beConstructedWith($config);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('LdapTools\Connection\LdapConnection');
    }

    function it_should_accept_an_event_dispatcher_as_a_second_constructor_argument()
    {
        $config = new DomainConfiguration('example.com');
        $config->setServers(['test'])
            ->setBaseDn('dc=example,dc=local')
            ->setLazyBind(true);

        $this->beConstructedWith($config, new SymfonyEventDispatcher());
    }

    function it_should_error_when_authenticate_is_called_without_username_or_password()
    {
        $this->shouldThrow('\Exception')->duringAuthenticate();
        $this->shouldThrow('\Exception')->duringAuthenticate('foo');
        $this->shouldThrow('\Exception')->duringAuthenticate('','bar');
    }

    function it_should_return_false_when_calling_isBound_and_there_is_no_connection_yet()
    {
        $this->isBound()->shouldBeEqualTo(false);
    }

    function it_should_honor_an_explicitly_set_schema_name_if_present()
    {
        $config = new DomainConfiguration('example.com');
        $config->setServers(['test'])
            ->setBaseDn('dc=example,dc=local')
            ->setLazyBind(true)
            ->setSchemaName('foo');
        $this->beConstructedWith($config);

        $this->getConfig()->getSchemaName()->shouldBeEqualTo('foo');
    }

    function it_should_have_a_page_size_as_specified_from_the_config()
    {
        $config = new DomainConfiguration('example.com');
        $config->setServers(['test'])
            ->setBaseDn('dc=example,dc=local')
            ->setLazyBind(true)
            ->setPageSize(250);
        $this->beConstructedWith($config);

        $this->getConfig()->getPageSize()->shouldBeEqualTo(250);
    }

    function it_should_have_the_base_dn_from_the_config()
    {
        $this->getBaseDn()->shouldBeEqualTo('dc=example,dc=local');
    }

    function it_should_get_the_current_server()
    {
        $this->getServer()->shouldBeEqualTo(null);
    }

    function it_should_have_a_method_to_get_the_connection_resource()
    {
        $this->getConnection()->shouldBeEqualTo(null);
    }
}
