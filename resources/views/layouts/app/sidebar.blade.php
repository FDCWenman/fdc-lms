<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white dark:bg-zinc-800">
        <flux:sidebar side="left" sticky collapsible="mobile" class="border-e border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:sidebar.header>
                <x-app-logo :sidebar="true" href="{{ route('dashboard') }}" wire:navigate />
                <flux:sidebar.collapse class="lg:hidden" />
            </flux:sidebar.header>

            <flux:sidebar.nav>
                <flux:sidebar.group :heading="__('Platform')" class="grid">
                    <flux:sidebar.item icon="home" :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate>
                        {{ __('Dashboard') }}
                    </flux:sidebar.item>

                    @can('view-employees')
                        <flux:sidebar.item icon="users" :href="route('employees.index')" :current="request()->routeIs('employees.*')" wire:navigate>
                            {{ __('Employee Management') }}
                        </flux:sidebar.item>
                    @endcan

                    @role('employee')
                        <flux:sidebar.item icon="calendar" :href="route('leaves')" :current="request()->routeIs('leaves*')" wire:navigate>
                            {{ __('My Leaves') }}
                        </flux:sidebar.item>
                    @endrole
                    
                    @role('hr|team-lead|project-manager')
                        <flux:sidebar.item icon="clipboard-check" :href="route('portal')" :current="request()->routeIs('portal*')" wire:navigate>
                            {{ __('Approval Portal') }}
                        </flux:sidebar.item>
                    @endrole
                    
                    @role('hr')
                        <flux:sidebar.item icon="user-plus" :href="route('register')" :current="request()->routeIs('register')" wire:navigate>
                            {{ __('Register User') }}
                        </flux:sidebar.item>
                    @endrole
                </flux:sidebar.group>

                @can('view-roles')
                    <flux:sidebar.group expandable :heading="__('Administration')" icon="cog" class="grid" :expanded="request()->routeIs('admin.roles.*')">
                        <flux:sidebar.item :href="route('admin.roles.index')" :current="request()->routeIs('admin.roles.index')" wire:navigate>
                            {{ __('Roles') }}
                        </flux:sidebar.item>
                        <flux:sidebar.item :href="route('admin.roles.permissions')" :current="request()->routeIs('admin.roles.permissions')" wire:navigate>
                            {{ __('Permissions') }}
                        </flux:sidebar.item>
                    </flux:sidebar.group>
                @endcan
            </flux:sidebar.nav>

            <flux:spacer />

            <x-desktop-user-menu class="hidden lg:block" :name="auth()->user()->name" />
        </flux:sidebar>

        <!-- Mobile User Menu -->
        <flux:header class="lg:hidden">
            <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

            <flux:spacer />

            <flux:dropdown position="top" align="end">
                <flux:profile
                    :initials="auth()->user()->initials()"
                    icon-trailing="chevron-down"
                />

                <flux:menu>
                    <flux:menu.radio.group>
                        <div class="p-0 text-sm font-normal">
                            <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                                <flux:avatar
                                    :name="auth()->user()->name"
                                    :initials="auth()->user()->initials()"
                                />

                                <div class="grid flex-1 text-start text-sm leading-tight">
                                    <flux:heading class="truncate">{{ auth()->user()->name }}</flux:heading>
                                    <flux:text class="truncate">{{ auth()->user()->email }}</flux:text>
                                </div>
                            </div>
                        </div>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <flux:menu.radio.group>
                        <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>
                            {{ __('Settings') }}
                        </flux:menu.item>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <flux:menu.item
                            as="button"
                            type="submit"
                            icon="arrow-right-start-on-rectangle"
                            class="w-full cursor-pointer"
                            data-test="logout-button"
                        >
                            {{ __('Log out') }}
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        </flux:header>

        {{ $slot }}

        @fluxScripts
    </body>
</html>
