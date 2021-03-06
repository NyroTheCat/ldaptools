<?php
/**
 * This file is part of the LdapTools package.
 *
 * (c) Chad Sikorra <Chad.Sikorra@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace spec\LdapTools\AttributeConverter;

use LdapTools\DomainConfiguration;
use LdapTools\Object\LdapObject;
use LdapTools\Operation\QueryOperation;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class ConvertValueToDnSpec extends ObjectBehavior
{
    protected $entry = [
        'count' => 1,
        0 => [
            "distinguishedname" => [
                "count" => 1,
                0 => "CN=Foo,DC=bar,DC=foo",
            ],
            0 => "distinguishedName",
            'count' => 2,
            'dn' => "CN=Foo,DC=bar,DC=foo",
        ],
    ];

    function it_is_initializable()
    {
        $this->shouldHaveType('LdapTools\AttributeConverter\ConvertValueToDn');
    }

    function it_should_implement_AttributeConverterInterface()
    {
        $this->shouldImplement('\LdapTools\AttributeConverter\AttributeConverterInterface');
    }

    function it_should_convert_a_dn_to_a_normal_name()
    {
        $this->setOptions(['foo' =>[ 'filter' => ['objectClass' => 'bar'],  'attribute' => 'foo']]);
        $this->setAttribute('foo');
        $this->fromLdap('cn=Foo,dc=bar,dc=foo')->shouldBeEqualTo('Foo');
    }

    /**
     * @param \LdapTools\Connection\LdapConnectionInterface $connection
     */
    function it_should_convert_a_name_back_to_a_dn($connection)
    {
        $connection->getConfig()->willReturn(new DomainConfiguration('example.local'));
        $connection->execute(Argument::that(function($operation) {
            return $operation->getFilter() == '(&(&(objectClass=bar))(cn=Foo))' && $operation->getAttributes() == ['dn'];
        }))->willReturn($this->entry);
        $this->setOptions(['foo' => [
            'attribute' => 'cn',
            'filter' => [
                'objectClass' => 'bar'
            ],
        ]]);
        $this->setLdapConnection($connection);
        $this->setAttribute('foo');

        $this->toLdap('Foo')->shouldBeEqualTo($this->entry[0]['distinguishedname'][0]);
    }

    /**
     * @param \LdapTools\Connection\LdapConnectionInterface $connection
     */
    function it_should_convert_a_GUID_back_to_a_dn($connection)
    {
        $guid = 'a1131cd3-902b-44c6-b49a-1f6a567cda25';
        $guidHex = '\d3\1c\13\a1\2b\90\c6\44\b4\9a\1f\6a\56\7c\da\25';

        $connection->getConfig()->willReturn(new DomainConfiguration('example.local'));
        $connection->execute(Argument::that(function($operation) use ($guidHex, $guid) {
            return $operation->getFilter() == '(&(&(objectClass=bar))(|(objectGuid='.$guidHex.')(cn='.$guid.')))';
        }))->willReturn($this->entry);
        $this->setOptions(['foo' => [
            'attribute' => 'cn',
            'filter' => [
                'objectClass' => 'bar'
            ],
        ]]);
        $this->setAttribute('foo');
        $this->setLdapConnection($connection);
        $this->toLdap($guid)->shouldBeEqualTo($this->entry[0]['distinguishedname'][0]);
    }

    /**
     * @param \LdapTools\Connection\LdapConnectionInterface $connection
     */
    function it_should_convert_a_SID_back_to_a_dn($connection)
    {
        $sid = 'S-1-5-21-1004336348-1177238915-682003330-512';
        $sidHex = '\01\05\00\00\00\00\00\05\15\00\00\00\dc\f4\dc\3b\83\3d\2b\46\82\8b\a6\28\00\02\00\00';

        $connection->getConfig()->willReturn(new DomainConfiguration('example.local'));
        $connection->execute(Argument::that(function($operation) use ($sid, $sidHex) {
            return $operation->getFilter() == '(&(&(objectClass=bar))(|(objectSid='.$sidHex.')(cn='.$sid.')))';
        }))->willReturn($this->entry);
        $this->setOptions(['foo' => [
            'attribute' => 'cn',
            'filter' => [
                'objectClass' => 'bar'
            ],
        ]]);
        $this->setAttribute('foo');
        $this->setLdapConnection($connection);
        $this->toLdap($sid)->shouldBeEqualTo($this->entry[0]['distinguishedname'][0]);
    }

    /**
     * @param \LdapTools\Connection\LdapConnectionInterface $connection
     */
    function it_should_convert_a_LdapObject_back_to_a_dn($connection)
    {
        $dn = 'CN=Chad,OU=Employees,DC=example,DC=com';
        $ldapObject = new LdapObject(['dn' => $dn], ['user'], 'user', 'user');
        $this->setOptions(['foo' => [
            'attribute' => 'cn',
            'filter' => [
                'objectClass' => 'bar'
            ],
        ]]);
        $this->setAttribute('foo');
        $this->setLdapConnection($connection);
        $this->toLdap($ldapObject)->shouldBeEqualTo($dn);
    }

    /**
     * @param \LdapTools\Connection\LdapConnectionInterface $connection
     */
    function it_should_error_if_a_LdapObject_is_missing_a_DN($connection)
    {
        $dn = 'CN=Chad,OU=Employees,DC=example,DC=com';
        $ldapObject = new LdapObject(['cn' => 'foo'], ['user'], 'user', 'user');
        $this->setOptions(['foo' => [
            'attribute' => 'cn',
            'filter' => [
                'objectClass' => 'bar'
            ],
        ]]);
        $this->setAttribute('foo');
        $this->setLdapConnection($connection);
        $this->shouldThrow('\LdapTools\Exception\AttributeConverterException')->duringToLdap($ldapObject);
    }

    /**
     * @param \LdapTools\Connection\LdapConnectionInterface $connection
     */
    function it_should_convert_a_dn_back_to_a_dn($connection)
    {
        $dn = $this->entry[0]['distinguishedname'][0];
        $connection->getConfig()->willReturn(new DomainConfiguration('bar.foo'));
        $connection->execute(Argument::any())->shouldNotBeCalled();
        $this->setOptions(['foo' => [
            'attribute' => 'cn',
            'filter' => [
                'objectClass' => 'bar'
            ],
        ]]);
        $this->setAttribute('foo');
        $this->setLdapConnection($connection);
        $this->toLdap($dn)->shouldBeEqualTo($dn);
    }

    function it_should_convert_a_dn_into_its_common_name()
    {
        $this->setOptions(['foo' =>[ 'filter' => ['objectClass' => 'bar'],  'attribute' => 'foo']]);
        $this->setAttribute('foo');
        $this->fromLdap('cn=Foo\,\=bar,dc=foo,dc=bar')->shouldBeEqualTo('Foo,=bar');
    }

    /**
     * @param \LdapTools\Connection\LdapConnectionInterface $ldap
     */
    function it_should_throw_an_error_if_no_options_exist_for_the_current_attribute($ldap)
    {
        $this->setLdapConnection($ldap);
        $this->shouldThrow('\LdapTools\Exception\AttributeConverterException')->duringToLdap('foo');
    }

    function it_should_display_the_dn_from_ldap_if_specified()
    {
        $this->setOptions(['foo' =>[ 'filter' => ['objectClass' => 'bar'],  'attribute' => 'foo', 'display_dn' => true]]);
        $this->setAttribute('foo');
        $this->fromLdap('cn=Foo,dc=bar,dc=foo')->shouldBeEqualTo('cn=Foo,dc=bar,dc=foo');
    }

    /**
     * @param \LdapTools\Connection\LdapConnectionInterface $connection
     */
    function it_should_allow_an_or_filter_for_an_attribute($connection)
    {
        $connection->getConfig()->willReturn(new DomainConfiguration('example.local'));
        $connection->execute(Argument::that(function($operation) {
            return $operation->getFilter() == '(&(|(objectClass=bar)(objectClass=foo))(cn=Foo))';
        }))->willReturn($this->entry);
        $this->setOptions(['foo' => [
            'attribute' => 'cn',
            'filter' => [
                'objectClass' => ['bar', 'foo'],
            ],
            'or_filter' => true,
        ]]);
        $this->setAttribute('foo');
        $this->setLdapConnection($connection);
        $this->toLdap('Foo')->shouldBeEqualTo($this->entry[0]['distinguishedname'][0]);
    }
}
