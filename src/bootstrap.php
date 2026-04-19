<?php

/**
 * Filament 3 → 5 namespace shim.
 *
 * Loaded once at composer autoload time (via composer.json "files"), so any
 * Filament 3 namespace referenced inside this package resolves to its
 * Filament 5 equivalent BEFORE Filament's own autoloader gets first crack.
 *
 * If the consumer is on Filament 3, both old and new classes exist (or the
 * new ones don't), so this is a no-op.
 */

$aliases = [
    \Filament\Forms\Form::class => \Filament\Schemas\Schema::class,
    \Filament\Forms\Components\Grid::class => \Filament\Schemas\Components\Grid::class,
    \Filament\Tables\Actions\Action::class => \Filament\Actions\Action::class,
    \Filament\Tables\Actions\ActionGroup::class => \Filament\Actions\ActionGroup::class,
    \Filament\Tables\Actions\CreateAction::class => \Filament\Actions\CreateAction::class,
    \Filament\Tables\Actions\EditAction::class => \Filament\Actions\EditAction::class,
    \Filament\Tables\Actions\DeleteAction::class => \Filament\Actions\DeleteAction::class,
];

foreach ($aliases as $legacy => $modern) {
    // CRITICAL: do NOT pass `false` to class_exists here — that would skip
    // the autoloader and return false even when the legacy class genuinely
    // exists on disk (Filament 3 ships real Filament\Tables\Actions\Action
    // etc). The resulting bogus alias would make Filament 3's table builder
    // try to call ->table() on the v5 Filament\Actions\Action, which has
    // no such method — exploding every table render with
    // "Method Filament\Actions\Action::table does not exist".
    if (class_exists($legacy) || interface_exists($legacy)) {
        continue; // legacy already real (Filament 3 install) — no-op
    }
    if (! class_exists($modern)) {
        continue; // modern not available either — nothing to alias
    }
    class_alias($modern, $legacy);
}
