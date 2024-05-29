<?php
namespace PHPSTORM_META {

    $STATIC_METHOD_TYPES = [
        \AcVendor\Psr\Container\ContainerInterface::get('') => [
            "" == "@",
        ],
        \AcVendor\Interop\Container\ContainerInterface::get('') => [
            "" == "@",
        ],
        \AcVendor\DI\Container::get('') => [
            "" == "@",
        ],
        \EasyMock\EasyMock::easyMock('') => [
            "" == "@",
        ],
        \EasyMock\EasyMock::easySpy('') => [
            "" == "@",
        ],
    ];
}
