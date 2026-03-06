<x-layouts::app.sidebar :title="$title ?? null">
    <flux:header>
        <flux:navbar>
            <flux:navbar.item href="{{ route('dashboard') }}">Dashboard</flux:navbar.item>
            
            @role('employee')
                <flux:navbar.item href="{{ route('leaves') }}">My Leaves</flux:navbar.item>
            @endrole
            
            @role('hr|team-lead|project-manager')
                <flux:navbar.item href="{{ route('portal') }}">Approval Portal</flux:navbar.item>
            @endrole
            
            @role('hr')
                <flux:navbar.item href="{{ route('register') }}">Register User</flux:navbar.item>
            @endrole

            <flux:spacer />

            <flux:dropdown>
                <flux:button variant="ghost">{{ auth()->user()->name }}</flux:button>
                <flux:menu>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <flux:menu.item type="submit">Logout</flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        </flux:navbar>
    </flux:header>

    <flux:main>
        {{ $slot }}
    </flux:main>
</x-layouts::app.sidebar>
