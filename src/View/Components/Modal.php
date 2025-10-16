<?php

namespace Mary\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Modal extends Component
{
    public function __construct(
        public ?string $id = '',
        public ?string $title = null,
        public ?string $subtitle = null,
        public ?string $boxClass = null,
        public ?bool $separator = false,
        public ?bool $persistent = false,
        public ?bool $withoutTrapFocus = false,
        public ?string $xShow = null, // Alpine.js x-show variable name

        // Slots
        public ?string $actions = null
    ) {
        //
    }

    public function render(): View|Closure|string
    {
        return <<<'HTML'
                <dialog
                    {{ $attributes->except(['wire:model', 'x-show'])->class(["modal"]) }}

                    @if($id)
                        id="{{ $id }}"
                    @elseif($xShow || $attributes->has('x-show'))
                        @php
                            $xShowValue = $xShow ?: $attributes->get('x-show');
                        @endphp
                        x-show="{{ $xShowValue }}"
                        x-cloak
                        :class="{'modal-open': {{ $xShowValue }}}"
                        @if(!$persistent)
                            @keydown.escape.window="{{ $xShowValue }} = false"
                        @endif
                    @else
                        x-data="{open: @entangle($attributes->wire('model')).live }"
                        x-init="$watch('open', value => { if (!value){ $dispatch('close') }else{ $dispatch('open') } })"
                        :class="{'modal-open !animate-none': open}"
                        :open="open"
                        @if(!$persistent)
                            @keydown.escape.window = "$wire.{{ $attributes->wire('model')->value() }} = false"
                        @endif
                    @endif

                    @if(!$withoutTrapFocus)
                        @if($xShow || $attributes->has('x-show'))
                            @php
                                $xShowValue = $xShow ?: $attributes->get('x-show');
                            @endphp
                            x-trap="{{ $xShowValue }}" x-bind:inert="!{{ $xShowValue }}"
                        @else
                            x-trap="open" x-bind:inert="!open"
                        @endif
                    @endif
                >
                    <div class="modal-box {{ $boxClass }}">
                        @if(!$persistent)
                            <form method="dialog" tabindex="-1">
                                @if ($id)
                                    <x-mary-button class="btn-circle btn-sm btn-ghost absolute end-2 top-2 z-[999]" icon="common-close" type="submit" tabindex="-1" />
                                @elseif($xShow || $attributes->has('x-show'))
                                    @php
                                        $xShowValue = $xShow ?: $attributes->get('x-show');
                                    @endphp
                                    <x-mary-button class="btn-circle btn-sm btn-ghost absolute end-2 top-2 z-[999]" icon="common-close" @click="{{ $xShowValue }} = false" tabindex="-1" />
                                @else
                                    <x-mary-button class="btn-circle btn-sm btn-ghost absolute end-2 top-2 z-[999]" icon="common-close" @click="$wire.{{ $attributes->wire('model')->value() }} = false" tabindex="-1" />
                                @endif
                            </form>
                        @endif

                        @if($title)
                            <x-mary-header :title="$title" :subtitle="$subtitle" size="text-xl" :separator="$separator" class="!mb-5" />
                        @endif

                        <div>
                            {{ $slot }}
                        </div>

                        @if($separator && $actions)
                            <hr class="border-t-[length:var(--border)] border-base-content/10 mt-5" />
                        @endif

                        @if($actions)
                            <div class="modal-action">
                                {{ $actions }}
                            </div>
                        @endif
                    </div>

                    @if(!$persistent)
                        <form class="modal-backdrop" method="dialog">
                            @if ($id)
                                <button type="submit">close</button>
                            @elseif($xShow || $attributes->has('x-show'))
                                @php
                                    $xShowValue = $xShow ?: $attributes->get('x-show');
                                @endphp
                                <button @click="{{ $xShowValue }} = false" type="button">close</button>
                            @else
                                <button @click="$wire.{{ $attributes->wire('model')->value() }} = false" type="button">close</button>
                            @endif
                        </form>
                    @endif
                </dialog>
                HTML;
    }
}
