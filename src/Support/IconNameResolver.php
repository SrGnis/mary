<?php

namespace Mary\Support;

use Illuminate\Support\Str;

class IconNameResolver
{
    public static function resolve(string $name): string
    {
        $iconName = Str::of($name);
        $iconPrefix = config('mary.icons.prefix', 'heroicon');

        if (Str::startsWith($iconName, 'common-')) {
            $iconKey = Str::after($iconName, 'common-');
            $result = config("mary.icons.common.{$iconKey}", $iconKey);

            return "{$iconPrefix}-{$result}";
        }

        if (config("mary.icons.common.{$name}") !== null) {
            $result = config("mary.icons.common.{$name}", $name);

            return "{$iconPrefix}-{$result}";
        }

        if (Str::startsWith($iconName, $iconPrefix . '-')) {
            return (string) $iconName;
        }

        return $iconName->contains('.') ? (string) $iconName->replace('.', '-') : "{$iconPrefix}-{$name}";
    }
}
