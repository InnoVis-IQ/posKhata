@props([
    'text' => null,
    'dark' => false,
])

@php
    $tip = trim((string) ($text ?? $slot));
@endphp

@if ($tip !== '')
    <span
        {{ $attributes->class('relative inline-flex h-5 w-5 shrink-0 align-middle') }}
        x-data="{
            open: false,
            alignLeft: false,
            place() {
                const tipWidth = 288;
                const pad = 16;
                const left = this.$el.getBoundingClientRect().left;
                this.alignLeft = left + tipWidth + pad > window.innerWidth;
            },
            show() {
                this.place();
                this.open = true;
            },
            hide() {
                this.open = false;
            },
            toggle() {
                if (this.open) {
                    this.hide();
                } else {
                    this.show();
                }
            },
        }"
        @keydown.escape.window="hide()"
        @mouseenter="show()"
        @mouseleave="hide()"
    >
        <button
            type="button"
            class="{{ $dark
                ? 'flex h-5 w-5 items-center justify-center text-white/90 hover:text-white focus:outline-none focus-visible:ring-2 focus-visible:ring-white/40'
                : 'flex h-5 w-5 items-center justify-center text-accent hover:opacity-80 focus:outline-none focus-visible:ring-2 focus-visible:ring-accent/40' }}"
            aria-label="More information"
            :aria-expanded="open.toString()"
            @click.stop="toggle()"
            @focus="show()"
            @blur="hide()"
        >
            <svg class="pointer-events-none h-5 w-5" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                <circle cx="10" cy="10" r="8.25" stroke="currentColor" stroke-width="1.5"/>
                <circle cx="10" cy="6.25" r="1" fill="currentColor"/>
                <path d="M10 9v5.25" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
            </svg>
        </button>
        <span
            x-cloak
            x-show="open"
            x-transition.opacity.duration.150ms
            role="tooltip"
            class="absolute top-full z-50 mt-1.5 rounded-lg bg-ink px-4 py-2.5 text-left text-xs font-normal leading-relaxed text-white shadow-lg"
            :class="alignLeft ? 'right-0 left-auto' : 'left-0 right-auto'"
            style="width: 18rem; max-width: min(18rem, calc(100vw - 2rem));"
        >{{ $tip }}</span>
    </span>
@endif
