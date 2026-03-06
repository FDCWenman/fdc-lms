<x-layouts::app.sidebar :title="$title ?? null">
    {{-- Brand / Logo --}}
    <flux:brand href="{{ route('dashboard') }}" logo="{{ asset('images/fdc-logo.png') }}" name="FDC LMS" class="px-2" />

    {{-- Navigation --}}
    <flux:navlist>
        <flux:navlist.item icon="home" href="{{ route('dashboard') }}">Dashboard</flux:navlist.item>

        @can('view-employees')
            <flux:navlist.item icon="users" href="{{ route('employees.index') }}">Employee Management</flux:navlist.item>
        @endcan

        @role('employee')
            <flux:navlist.item icon="calendar" href="{{ route('leaves') }}">My Leaves</flux:navlist.item>
        @endrole
        
        @role('hr|team-lead|project-manager')
            <flux:navlist.item icon="clipboard-check" href="{{ route('portal') }}">Approval Portal</flux:navlist.item>
        @endrole
        
        @role('hr')
            <flux:navlist.item icon="user-plus" href="{{ route('register') }}">Register User</flux:navlist.item>
        @endrole

        @can('view-roles')
            <flux:navlist.group expandable heading="Administration" icon="cog">
                <flux:navlist.item href="{{ route('admin.roles.index') }}">Roles</flux:navlist.item>
                <flux:navlist.item href="{{ route('admin.roles.index') }}">Permissions</flux:navlist.item>
            </flux:navlist.group>
        @endcan
    </flux:navlist>

    <flux:spacer />

    {{-- User Menu --}}
    <flux:navlist variant="outline">
        <flux:dropdown position="top" align="start">
            <flux:navlist.item icon="user-circle">
                {{ auth()->user()->first_name ?? auth()->user()->name }} {{ auth()->user()->last_name ?? '' }}
            </flux:navlist.item>

            <flux:menu>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <flux:menu.item type="submit" icon="arrow-right-start-on-rectangle">Logout</flux:menu.item>
                </form>
            </flux:menu>
        </flux:dropdown>
    </flux:navlist>

    <flux:main>
        {{ $slot }}
    </flux:main>
</x-layouts::app.sidebar>
