# Modifying LDAP Objects
------------------------

Modifying an existing LDAP object is as easy as searching for it, making your changes to the object via either the 
setters/properties then sending the object back to the `LdapManager` class with the `persist()` method. All the changes
you make to the object are tracked and when you call `persist()` it will only update the attributes you actually changed.

Please keep in mind that this is not an active record design. The `LdapObject` instance is only aware of properties
you explicitly selected as part of the LDAP query. So if you want to check if an attribute exists, or has a specific value,
then make sure to actually select the attribute when you query LDAP. If you selected the attribute as part of the query,
but the `has($attribute)` method returns false, then the attribute is not set.

A simple example:

```php
use LdapTools\Object\LdapObjectType;

//...

$repository = $ldap->getRepository(LdapObjectType::USER);

$user = $repository->findOneByUsername('chad');

// Replaces the current title with whatever you choose.
$user->setTitle('CEO');
// Resets whatever value might have been set to this attribute.
$user->resetMobilePhone();
// Remove a specific value from a multi-valued attribute. Careful! If it doesn't exist LDAP will complain.
$user->removePhoneNumber('555-5555');
// Remove multiple values for a multi-valued attribute at once (Using splat notation)...
$user->removePhoneNumber(...['555-5555', '123-4567', '765-4321']);
// Adds a value to an attribute in addition to what it might already have
$user->addIpPhone('#001-1000');
// Add multiple values at once...
$user->addIpPhone('#001-1234','#002-5678', '#003-1001');

// Check if a specific attribute exists
if ($user->hasEmailAddress()) {
    // do something....
}

// Check if an attribute with a specific value exists
if ($user->hasState('WI')) {
    // do something else....
}

// Now actually save the changes back to LDAP
try {
    $ldap->persist($user);
} catch (\Exception $e) {
    echo "Error updating user! ".$e->getMessage();
}
```

## Automatic Setters and Getters

When you search for and retrieve an object in LDAP you will get an `LdapObject` instance by default. This class has
several "magic" PHP methods defined to make your life easier. You can get/set any attribute by its actual schema name
just as if it had an actual setter defined. So to get the `firstName` attribute you can call `getFirstName()`. And
to change it you simply call `setFirstName($firstName)`.

However, be sure to check if the attribute exists in the returned object first by calling `has($attributeName)`
before trying to actually get its value.

## Automatic Property Access

In addition to the automatic setters/getters mentioned above, you can also access attributes by simply accessing them as
if they were public properties on the object. Behind the scenes it still tracks any changes you might make by setting a 
property this way.

```
// Instead of '$user->getFirstName()' ...
echo "First Name:".$user->firstName.PHP_EOL;
echo "Last Name:".$user->lastName.PHP_EOL;

// Or set them this way too, which is equivalent to calling: $user->setFirstName('Some Dude')
$user->firstName = 'Fred';
$user->lastName = 'Fuchs';

// You can also call isset on a property to determine if it exists on the object...
if (isset($user>phoneNumber)) {
    echo "Phone: ".$user->phoneNumber.PHP_EOL; 
}
```

------------------------
## Standard Method Access

In Addition to all of the easy automatic property/setters/getters described above, you can also do it through more
verbose methods. All of the below methods are ultimately what the automatic methods use to do everything behind the
scenes.

------------------------
#### set($attribute, $value)

Replaces any value that might exist in the attribute with the value you specify.

------------------------
#### add($attribute, ...$value)

Adds a value to an attribute in addition to what it already may have. It will keep the existing value intact. This
method is also variadic, so you can pass as many values you want as arguments to this function.

------------------------
#### remove($attribute, ...$value)

Remove a specific value from an attribute. Be careful with this. If the value does not actually exist within the
attribute then an exception will be thrown during persist. It's best to wrap persist in a try/catch block. This
method is also variadic, so you can pass as many values you want as arguments to this function.

------------------------
#### reset($attribute)

Resets an attribute by removing any values it may contain.

------------------------
#### has($attribute, $value = null)

Check for the existence of an attribute before you attempt to retrieve its value. If you attempt to get the value of a
non-existent attribute then it will throw an exception. You can optionally specify a value check as well. In that case 
the attribute must exist and it must have the specified value for it to return `true`.
