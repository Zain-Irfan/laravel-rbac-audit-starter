<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ $user->exists ? "Edit {$user->name}" : 'New user' }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
            <form method="POST"
                  action="{{ $user->exists ? route('admin.users.update', $user) : route('admin.users.store') }}"
                  class="bg-white shadow-sm sm:rounded-lg p-6 space-y-5">
                @csrf
                @if ($user->exists)
                    @method('PUT')
                @endif

                <div>
                    <x-input-label for="name" value="Name" />
                    <x-text-input id="name" name="name" class="mt-1 block w-full" :value="old('name', $user->name)" required autofocus />
                    <x-input-error :messages="$errors->get('name')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="email" value="Email" />
                    <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $user->email)" required />
                    <x-input-error :messages="$errors->get('email')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="password" :value="$user->exists ? 'New password (leave blank to keep the current one)' : 'Password'" />
                    <x-text-input id="password" name="password" type="password" class="mt-1 block w-full" autocomplete="new-password" :required="! $user->exists" />
                    <x-input-error :messages="$errors->get('password')" class="mt-2" />
                </div>

                <fieldset>
                    <legend class="block font-medium text-sm text-gray-700">Roles</legend>
                    @php($selected = collect(old('roles', $user->roles->pluck('id')->all()))->map(fn ($id) => (int) $id))
                    <div class="mt-2 space-y-2">
                        @foreach ($roles as $role)
                            <label class="flex items-start gap-3 rounded-md border border-gray-200 p-3 hover:bg-gray-50">
                                <input type="checkbox" name="roles[]" value="{{ $role->id }}" @checked($selected->contains($role->id))
                                       class="mt-0.5 rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                <span>
                                    <span class="block text-sm font-medium text-gray-800">{{ $role->label }}</span>
                                    <span class="block text-xs text-gray-500">{{ $role->description }}</span>
                                </span>
                            </label>
                        @endforeach
                    </div>
                    <x-input-error :messages="$errors->get('roles')" class="mt-2" />
                </fieldset>

                <div class="flex items-center justify-end gap-3 pt-2">
                    <a href="{{ route('admin.users.index') }}" class="text-sm text-gray-600 hover:text-gray-900">Cancel</a>
                    <x-primary-button>{{ $user->exists ? 'Save changes' : 'Create user' }}</x-primary-button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
