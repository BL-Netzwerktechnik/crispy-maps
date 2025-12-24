<?php

namespace blfilme\lostplaces\Controllers;

use blfilme\lostplaces\Interfaces\IconInterface;
use crisp\api\Config;

class IconProviderController
{
    public function __construct(private string|IconInterface $iconProvider)
    {
        if (is_string($this->iconProvider)) {
            if (!class_exists($this->iconProvider)) {
                throw new \InvalidArgumentException('Icon provider class does not exist');
            }
            $this->iconProvider = new $this->iconProvider();
        }
        if (!($this->iconProvider instanceof IconInterface)) {
            throw new \InvalidArgumentException('Icon provider must be a string or an instance of IconInterface');
        }
    }

    public function getProvider(): IconInterface
    {
        return $this->iconProvider;
    }

    public static function fetchFromConfig(?string $name = null, ?string $color = null): IconInterface
    {
        $iconProvider = Config::get('LostPlaces_IconClass');
        if (is_null($iconProvider)) {
            throw new \InvalidArgumentException('Icon provider is not set in the config');
        }

        return (new self($iconProvider))->getProvider()->setName($name)
            ->setColor($color);
    }
}
