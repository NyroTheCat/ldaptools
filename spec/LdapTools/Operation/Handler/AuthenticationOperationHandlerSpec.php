<?php
/**
 * This file is part of the LdapTools package.
 *
 * (c) Chad Sikorra <Chad.Sikorra@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace spec\LdapTools\Operation\Handler;

use LdapTools\Connection\LdapConnectionInterface;
use LdapTools\Event\Event;
use LdapTools\Event\EventDispatcherInterface;
use LdapTools\Event\LdapAuthenticationEvent;
use LdapTools\Exception\LdapBindException;
use LdapTools\Operation\AddOperation;
use LdapTools\Operation\AuthenticationOperation;
use LdapTools\Operation\AuthenticationResponse;
use LdapTools\Operation\BatchModifyOperation;
use LdapTools\Operation\DeleteOperation;
use LdapTools\Operation\QueryOperation;
use LdapTools\Operation\RenameOperation;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class AuthenticationOperationHandlerSpec extends ObjectBehavior
{
    /**
     * @var LdapConnectionInterface
     */
    protected $connection;

    /**
     * @var EventDispatcherInterface
     */
    protected $dispatcher;

    /**
     * @param \LdapTools\Connection\LdapConnectionInterface $connection
     * @param \LdapTools\Event\EventDispatcherInterface $dispatcher
     */
    function let($connection, $dispatcher)
    {
        $this->connection = $connection;
        $this->dispatcher = $dispatcher;
        $this->setConnection($connection);
        $this->setEventDispatcher($dispatcher);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('LdapTools\Operation\Handler\AuthenticationOperationHandler');
    }

    function it_should_NOT_support_add_delete_rename_query_or_modify_operation()
    {
        $this->supports(new AddOperation('foo'))->shouldBeEqualTo(false);
        $this->supports(new DeleteOperation('foo'))->shouldBeEqualTo(false);
        $this->supports(new RenameOperation('foo'))->shouldBeEqualTo(false);
        $this->supports(new BatchModifyOperation('foo'))->shouldBeEqualTo(false);
        $this->supports(new QueryOperation())->shouldBeEqualTo(false);
    }

    function it_should_support_authentication_operations()
    {
        $this->supports(new AuthenticationOperation())->shouldBeEqualTo(true);
    }

    function it_should_authenticate_a_user()
    {
        $operation = (new AuthenticationOperation())->setUsername('foo')->setPassword('bar');
        $this->connection->connect('foo', 'bar', false, null)->willReturn($this->connection);
        $this->connection->isBound()->willReturn(true);
        $this->connection->close()->willReturn($this->connection);
        $this->connection->connect()->willReturn($this->connection);

        $this->execute($operation)->shouldReturnAnInstanceOf('\LdapTools\Operation\AuthenticationResponse');
        $this->execute($operation)->isAuthenticated()->shouldBeEqualTo(true);
    }

    function it_should_authenticate_a_user_but_not_reconnect_if_the_connection_wasnt_bound()
    {
        $operation = (new AuthenticationOperation())->setUsername('foo')->setPassword('bar');
        $this->connection->connect('foo', 'bar', false, null)->willReturn($this->connection);
        $this->connection->isBound()->willReturn(false);
        $this->connection->close()->willReturn($this->connection);

        $this->execute($operation)->shouldReturnAnInstanceOf('\LdapTools\Operation\AuthenticationResponse');
    }

    /**
     * @param \LdapTools\DomainConfiguration $config
     */
    function it_should_authenticate_a_user_and_stay_connected_as_them_if_specified($config)
    {
        $operation = (new AuthenticationOperation())->setUsername('foo')->setPassword('bar')->setSwitchToCredentials(true);
        $this->connection->connect('foo', 'bar', false, null)->willReturn($this->connection);
        $this->connection->isBound()->willReturn(true);
        $this->connection->close()->willReturn($this->connection);
        $this->connection->getConfig()->willReturn($config);

        // The credentials are switched in the config itself...
        $config->setUsername('foo')->shouldBeCalled();
        $config->setPassword('bar')->shouldBeCalled();

        $this->execute($operation)->shouldReturnAnInstanceOf('\LdapTools\Operation\AuthenticationResponse');
    }

    function it_should_not_authenticate_if_a_bind_exception_is_thrown()
    {
        $ex = new LdapBindException('Foo');
        $operation = (new AuthenticationOperation())->setUsername('foo')->setPassword('bar');
        $this->connection->connect('foo', 'bar', false, null)->willThrow($ex);
        $this->connection->getLastError()->willReturn('foo');
        $this->connection->getExtendedErrorNumber()->willReturn(99);
        $this->connection->isBound()->willReturn(true);
        $this->connection->close()->willReturn($this->connection);
        $this->connection->connect()->willReturn($this->connection);

        $this->execute($operation)->shouldReturnAnInstanceOf('\LdapTools\Operation\AuthenticationResponse');
        $this->execute($operation)->isAuthenticated()->shouldBeEqualTo(false);
        $this->execute($operation)->getErrorMessage()->shouldBeEqualTo('foo');
        $this->execute($operation)->getErrorCode()->shouldBeEqualTo(99);
    }

    function it_should_not_switch_credentials_on_an_authentication_failure()
    {
        $ex = new LdapBindException('Foo');
        $operation = (new AuthenticationOperation())->setUsername('foo')->setPassword('bar')->setSwitchToCredentials(true);
        $this->connection->connect('foo', 'bar', false, null)->willThrow($ex);
        $this->connection->getLastError()->willReturn('foo');
        $this->connection->getExtendedErrorNumber()->willReturn(99);
        $this->connection->isBound()->willReturn(true);
        $this->connection->close()->willReturn($this->connection);
        $this->connection->connect()->willReturn($this->connection);

        // This is a sufficient check, as it must get the config to change the user/pass.
        $this->connection->getConfig()->shouldNotBeCalled();

        $this->execute($operation)->shouldReturnAnInstanceOf('\LdapTools\Operation\AuthenticationResponse');
    }

    function it_should_call_the_event_dispatchers()
    {
        $operation = (new AuthenticationOperation())->setUsername('foo')->setPassword('bar');
        $this->connection->connect('foo', 'bar', false, null)->willReturn($this->connection);
        $this->connection->isBound()->willReturn(true);
        $this->connection->close()->willReturn($this->connection);
        $this->connection->connect()->willReturn($this->connection);

        $this->dispatcher->dispatch(new LdapAuthenticationEvent(Event::LDAP_AUTHENTICATION_BEFORE, $operation))->shouldBeCalled();
        $this->dispatcher->dispatch(new LdapAuthenticationEvent(Event::LDAP_AUTHENTICATION_AFTER, $operation, new AuthenticationResponse(true)))->shouldBeCalled();

        $this->execute($operation)->shouldReturnAnInstanceOf('\LdapTools\Operation\AuthenticationResponse');
    }
}
