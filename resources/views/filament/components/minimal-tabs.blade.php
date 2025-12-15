<x-dynamic-component
    :component="$getFieldWrapperView()"
    :field="$field"
>
    <div
        x-data="{
            tab: @if ($isTabPersistedInQueryString())
                $queryString('{{ $getTabQueryStringKey() }}')
            @elseif ($isTabPersistedInLocalStorage())
                $persist(null).as('{{ $getLocalStorageKey() }}')
            @else
                null
            @endif
        }"
        x-init="
            if (! tab || ! $refs.tabsData.querySelector('[x-bind:id=\\'' + tab + '\\']')) {
                tab = @js($getActiveTab())
            }
        "
        x-cloak
        {{
            $attributes
                ->merge([
                    'id' => $getId(),
                ], escape: false)
                ->merge($getExtraAttributes(), escape: false)
                ->class([
                    'fi-fo-tabs',
                    'minimal-tabs' => true,
                ])
        }}
    >
        <div
            {{
                $getExtraTabListAttributeBag()
                    ->class([
                        'fi-fo-tabs-list',
                        'minimal-tabs-list',
                        'flex gap-x-1 overflow-x-auto border-b border-gray-200 dark:border-white/10',
                    ])
            }}
            role="tablist"
        >
            @foreach ($getChildComponentContainers() as $tab)
                @php
                    $tabId = $tab->getStatePath();
                @endphp

                <button
                    type="button"
                    aria-controls="{{ $tabId }}"
                    x-bind:aria-selected="tab === @js($tabId)"
                    x-bind:tabindex="tab === @js($tabId) ? 0 : -1"
                    x-on:click="tab = @js($tabId)"
                    x-on:keydown.right.prevent="$focus.wrap().next()"
                    x-on:keydown.left.prevent="$focus.wrap().previous()"
                    role="tab"
                    {{
                        $tab->getExtraAttributeBag()
                            ->class([
                                'fi-fo-tabs-tab',
                                'minimal-tabs-tab',
                                'flex items-center gap-x-2 px-3 py-2 text-sm font-medium transition',
                                'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200',
                                'border-b-2 border-transparent',
                                'focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2',
                            ])
                    }}
                    x-bind:class="{
                        'text-primary-600 dark:text-primary-400 border-primary-600 dark:border-primary-400': tab === @js($tabId),
                    }"
                >
                    @if ($icon = $tab->getIcon())
                        <x-filament::icon
                            :icon="$icon"
                            class="h-4 w-4"
                        />
                    @endif

                    <span>{{ $tab->getLabel() }}</span>

                    @if ($badge = $tab->getBadge())
                        <x-filament::badge
                            :color="$tab->getBadgeColor()"
                            size="sm"
                        >
                            {{ $badge }}
                        </x-filament::badge>
                    @endif
                </button>
            @endforeach
        </div>

        <div
            x-ref="tabsData"
            class="fi-fo-tabs-content minimal-tabs-content mt-4"
        >
            @foreach ($getChildComponentContainers() as $tab)
                @php
                    $tabId = $tab->getStatePath();
                @endphp

                <div
                    aria-labelledby="{{ $tabId }}"
                    x-bind:id="@js($tabId)"
                    role="tabpanel"
                    tabindex="0"
                    x-show="tab === @js($tabId)"
                    {{
                        $tab->getExtraAttributeBag()
                            ->class([
                                'fi-fo-tabs-panel',
                                'minimal-tabs-panel',
                            ])
                    }}
                >
                    {{ $tab }}
                </div>
            @endforeach
        </div>
    </div>
</x-dynamic-component>
