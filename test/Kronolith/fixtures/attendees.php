<?php

$prefs = $this->getMockBuilder('Horde_Prefs')
    ->disableOriginalConstructor()
    ->getMock();
$prefs->method('getValue')->will($this->returnValueMap([
    ['from_addr', 'user@example.com'],
    ['fullname', 'User Name'],
]));
$factory = $this->getMockBuilder('Horde_Core_Factory_Identity')
    ->disableOriginalConstructor()
    ->getMock();
$factory->method('create')->willReturn(new Horde_Prefs_Identity(
    ['prefs' => $prefs, 'user' => 'username']
));

return [
    new Kronolith_Attendee([
        'email' => 'juergen@example.com',
        'role' => Kronolith::PART_REQUIRED,
        'response' => Kronolith::RESPONSE_NONE,
        'name' => 'Jürgen Doe',
    ]),
    new Kronolith_Attendee([
        'role' => Kronolith::PART_OPTIONAL,
        'response' => Kronolith::RESPONSE_ACCEPTED,
        'name' => 'Jane Doe',
    ]),
    new Kronolith_Attendee([
        'email' => 'jack@example.com',
        'role' => Kronolith::PART_NONE,
        'response' => Kronolith::RESPONSE_DECLINED,
        'name' => 'Jack Doe',
    ]),
    new Kronolith_Attendee([
        'email' => 'jenny@example.com',
        'role' => Kronolith::PART_NONE,
        'response' => Kronolith::RESPONSE_TENTATIVE,
    ]),
    new Kronolith_Attendee([
        'user' => 'username',
        'role' => Kronolith::PART_NONE,
        'response' => Kronolith::RESPONSE_TENTATIVE,
        'identities' => $factory,
    ]),
    new Kronolith_Attendee([
        'user' => 'username2',
        'role' => Kronolith::PART_NONE,
        'response' => Kronolith::RESPONSE_TENTATIVE,
        'name' => 'Another User',
    ]),
];
